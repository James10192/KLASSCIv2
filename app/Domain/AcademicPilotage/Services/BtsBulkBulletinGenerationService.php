<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\AcademicPilotage\DTO\BulkBulletinGenerationResult;
use App\Domain\AcademicPilotage\DTO\BulletinPreparationResult;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Exceptions\BulletinConfigurationException;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\User;
use App\Services\BulletinService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class BtsBulkBulletinGenerationService
{
    /**
     * Blocages qu'aucun motif de bulletin incomplet ne peut lever : la génération
     * échouerait quand même (config/coefficients/professeurs manquants, bulletin
     * verrouillé). Seul un blocage `soft` (aucune note exploitable) est overridable.
     */
    public const HARD_BLOCK_CODES = [
        'missing_subject_configuration',
        'professeurs_missing',
        'coefficients_missing',
        'bulletin_locked',
    ];

    /** Un bulletin porte deja une moyenne : il n'y a rien a refaire. */
    public const ECART_DEJA_GENERE = 'bulletin_exists';

    /** Bulletin sans moyenne, mais publie ou signe : le verrou interdit de le reprendre. */
    public const ECART_VIDE_VERROUILLE = 'bulletin_exists_empty_locked';

    public function __construct(
        private readonly BulletinService $bulletinService,
        private readonly BulletinGenerationReadinessService $readiness,
        private readonly BtsClassCohortCounter $cohortCounter,
    ) {}

    public function preflight(
        ESBTPClasse $classe,
        int $academicYearId,
        string $period,
        ?User $actor,
        bool $recalculate = false,
        ?array $studentIds = null,
    ): array {
        $period = $this->bulletinService->normalizePeriode($period);
        $students = $this->activeStudentsForClass($classe->id, $academicYearId, $period);

        // Quand la generation traite une tranche, inspecter toute la classe
        // reviendrait a payer le pre-controle complet a chaque requete : c'est
        // le cout fixe qui dominait le temps d'execution. Les controles de
        // configuration de classe (professeurs, matieres) restent evalues, ils
        // ne dependent pas de la liste d'etudiants.
        if ($studentIds !== null) {
            $wanted = array_map('intval', $studentIds);
            $students = $students->filter(
                static fn ($student) => in_array((int) $student->id, $wanted, true)
            )->values();
        }
        $skipped = [];
        $blockingErrors = [];
        $missingCoefficientBuckets = [];
        $existingEmptyCount = 0;
        $generatableCount = 0;
        $configurationUrl = $this->configurationUrl($classe->id, $academicYearId, $period);
        $hasSubjectConfiguration = $this->hasSubjectConfiguration($classe->id, $academicYearId, $period);
        $missingProfesseurs = $this->missingProfesseurRows($classe->id, $academicYearId, $period);

        if ($missingProfesseurs !== []) {
            $blockingErrors[] = [
                'code' => 'professeurs_missing',
                'message' => 'Les professeurs du bulletin sont a completer.',
                'missing_professeurs' => $missingProfesseurs,
                'configuration_url' => $configurationUrl,
            ];
        }

        foreach ($students as $student) {
            $existing = $this->findBulletin((int) $student->id, $classe->id, $academicYearId, $period);

            if ($ecart = $this->ecartPourBulletinExistant($existing, $recalculate)) {
                $skipped[] = $this->studentPayload($student) + $ecart + ['bulletin_id' => $existing->id];

                continue;
            }

            // Un bulletin vide est a refaire, mais il ne sera repris que s'il
            // franchit tous les controles qui suivent. Le compter ici, avant
            // eux, ferait promettre a l'ecran des bulletins que la generation
            // laissera dans les blocages : le comptage attend la fin de boucle.
            $videAReprendre = $existing && ! $recalculate && $existing->moyenne_generale === null;

            if ($existing && $this->isProtected($existing)) {
                $blockingErrors[] = $this->studentPayload($student) + [
                    'code' => 'bulletin_locked',
                    'message' => 'Bulletin publie ou signe : le recalcul est bloque.',
                    'bulletin_id' => $existing->id,
                ];
                continue;
            }

            if (! $hasSubjectConfiguration) {
                $blockingErrors[] = $this->studentPayload($student) + [
                    'code' => 'missing_subject_configuration',
                    'message' => 'La configuration des matieres du bulletin est manquante.',
                    'configuration_url' => $configurationUrl,
                ];
                continue;
            }

            $preparation = $this->readiness->inspect('BTS', (int) $student->id, $classe->id, $academicYearId, $period);
            $missingCoefficients = $this->missingCoefficientRows($preparation);

            foreach ($missingCoefficients as $missing) {
                $key = (string) $missing['matiere_id'];
                $missingCoefficientBuckets[$key] ??= [
                    'matiere_id' => $missing['matiere_id'],
                    'matiere' => $missing['matiere'],
                    'students_count' => 0,
                    'students' => [],
                ];
                $missingCoefficientBuckets[$key]['students_count']++;
                $missingCoefficientBuckets[$key]['students'][] = $this->studentPayload($student);
            }

            if (! $preparation->ready) {
                // Le code encode la sévérité : `coefficients_missing` est hard (la
                // génération lèverait une RuntimeException) et figure dans HARD_BLOCK_CODES ;
                // `incomplete_academic_data` (aucune note) est soft, donc overridable par motif.
                $isHard = $missingCoefficients !== [];
                $blockingErrors[] = $this->studentPayload($student) + [
                    'code' => $isHard ? 'coefficients_missing' : 'incomplete_academic_data',
                    'message' => $isHard
                        ? 'Coefficients manquants : completez la configuration du bulletin.'
                        : 'Aucune note exploitable pour cette periode.',
                    'preparation' => $preparation->toArray(),
                    'missing_coefficients' => $missingCoefficients,
                    'configuration_url' => $configurationUrl,
                ];
                continue;
            }

            $generatableCount++;
            $existingEmptyCount += $videAReprendre ? 1 : 0;
        }

        // Un seul compteur est tenu a la main, celui des bulletins vides qui
        // seront effectivement repris. Les verrouilles se lisent dans `skipped`,
        // qui les porte deja : deux comptabilites de la meme verite derivent.
        $verrouillesVides = collect($skipped)->where('code', self::ECART_VIDE_VERROUILLE)->count();

        $blockingErrors = $this->deduplicateStudentBlocks($blockingErrors);

        // Les etudiants qu'aucun blocage individuel n'ecarte. Le front decoupe
        // la classe sur cette liste : lui donner la cohorte entiere faisait
        // partir des tranches composees uniquement d'etudiants que le serveur
        // refuse, qui repondaient 422 et arretaient la boucle.
        //
        // Derive de ce que la boucle a deja produit, sans accumulateur : tout
        // etudiant non traite est pousse soit dans `skipped`, soit dans
        // `blockingErrors`. Un compteur a la main aurait redit la regle une
        // troisieme fois, en creux, dans les branches qui n'ajoutent rien.
        // La severite vient de HARD_BLOCK_CODES, seule table qui la definit :
        // un blocage souple reste envoyable, puisque le motif le leve.
        $horsJeu = collect($skipped)
            ->concat(collect($blockingErrors)->whereIn('code', self::HARD_BLOCK_CODES))
            ->pluck('student_id')
            ->filter() // le blocage « professeurs » vise la classe, pas un etudiant
            ->map(static fn ($id) => (int) $id)
            ->all();

        $aTraiter = $students->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->diff($horsJeu)
            ->values()
            ->all();

        $canOverrideIncomplete = (bool) ($actor?->can('bulletins.generate_incomplete') ?? false);
        $hasHardBlocks = collect($blockingErrors)->contains(fn ($b) => in_array($b['code'] ?? '', self::HARD_BLOCK_CODES, true));
        $status = $this->resolveStatus($students->count(), $blockingErrors, $hasHardBlocks, $generatableCount, $canOverrideIncomplete);

        return [
            'status' => $status,
            'ok' => $status === 'ready',
            'can_generate' => $status === 'ready',
            'can_generate_incomplete' => $canOverrideIncomplete,
            'requires_incomplete_reason' => $status === 'needs_reason',
            'has_hard_blocks' => $hasHardBlocks,
            'nothing_to_generate' => $status === 'nothing_to_generate',
            'classe' => [
                'id' => $classe->id,
                'name' => $classe->name,
            ],
            'annee_universitaire_id' => $academicYearId,
            'periode' => $period,
            'students_count' => $students->count(),
            'generatable_count' => $generatableCount,
            'student_ids' => $aTraiter,
            'existing_count' => collect($skipped)
                ->whereIn('code', [self::ECART_DEJA_GENERE, self::ECART_VIDE_VERROUILLE])
                ->count(),
            'existing_empty_count' => $existingEmptyCount,
            'existing_empty_locked_count' => $verrouillesVides,
            'recalculer' => $recalculate,
            'skipped' => $skipped,
            'blocking_errors' => $blockingErrors,
            'missing_coefficients' => array_values($missingCoefficientBuckets),
            'missing_professeurs' => $missingProfesseurs,
            'configuration_url' => $configurationUrl,
            'message' => $this->preflightMessage($status, $verrouillesVides, $hasHardBlocks, $classe),
        ];
    }

    /**
     * Statut unique et faisant autorité du pré-contrôle. L'UI et les messages
     * dérivent de cette valeur plutôt que de recombiner plusieurs booléens.
     */
    private function resolveStatus(
        int $studentsCount,
        array $blockingErrors,
        bool $hasHardBlocks,
        int $generatableCount,
        bool $canOverrideIncomplete,
    ): string {
        if ($studentsCount === 0) {
            return 'no_students';
        }

        if ($blockingErrors !== []) {
            // Blocages uniquement `soft` + droit d'override → un motif débloque.
            // Sinon 'blocked' : soit des blocages hard, soit soft mais sans le droit d'override.
            return (! $hasHardBlocks && $canOverrideIncomplete) ? 'needs_reason' : 'blocked';
        }

        if ($generatableCount === 0) {
            return 'nothing_to_generate';
        }

        return 'ready';
    }

    public function generate(
        ESBTPClasse $classe,
        int $academicYearId,
        string $period,
        ?User $actor,
        bool $recalculate = false,
        ?string $incompleteReason = null,
        ?array $studentIds = null,
    ): BulkBulletinGenerationResult {
        $period = $this->bulletinService->normalizePeriode($period);
        $preflight = $this->preflight($classe, $academicYearId, $period, $actor, $recalculate, $studentIds);
        $classConfigurationBlocks = collect($preflight['blocking_errors'] ?? [])
            ->whereIn('code', ['professeurs_missing'])
            ->values()
            ->all();

        if ($classConfigurationBlocks !== []) {
            return new BulkBulletinGenerationResult(
                created: 0,
                regenerated: 0,
                skipped: $preflight['skipped'] ?? [],
                blockingErrors: $classConfigurationBlocks,
                errors: [],
                preflight: $preflight,
            );
        }

        $students = $this->activeStudentsForClass($classe->id, $academicYearId, $period);

        // Traitement par lots : la generation coute O(N^2) et l'hebergement
        // coupe a 30 secondes. Restreindre le lot permet de tenir dans le
        // budget et de reprendre la ou on s'est arrete. Le recalcul des rangs
        // en fin de methode reste calcule sur la cohorte entiere, le resultat
        // final est donc identique a un traitement en une passe.
        $totalStudents = $students->count();
        if ($studentIds !== null) {
            $wanted = array_map('intval', $studentIds);
            $students = $students->filter(
                static fn ($student) => in_array((int) $student->id, $wanted, true)
            )->values();
        }

        $created = 0;
        $regenerated = 0;
        $skipped = [];
        $blockingErrors = [];
        $errors = [];

        foreach ($students as $student) {
            $existing = $this->findBulletin((int) $student->id, $classe->id, $academicYearId, $period);

            if ($ecart = $this->ecartPourBulletinExistant($existing, $recalculate)) {
                $skipped[] = $this->studentPayload($student) + $ecart + ['bulletin_id' => $existing->id];
                continue;
            }

            if ($existing && $this->isProtected($existing)) {
                $blockingErrors[] = $this->studentPayload($student) + [
                    'code' => 'bulletin_locked',
                    'message' => 'Bulletin publie ou signe : le recalcul est bloque.',
                    'bulletin_id' => $existing->id,
                ];
                continue;
            }

            try {
                $this->readiness->assertReady(
                    'BTS',
                    (int) $student->id,
                    $classe->id,
                    $academicYearId,
                    $period,
                    $actor,
                    $incompleteReason
                );

                $wasCreated = false;

                DB::transaction(function () use ($student, $classe, $academicYearId, $period, $actor, &$wasCreated): void {
                    $bulletin = $this->findBulletin((int) $student->id, $classe->id, $academicYearId, $period);

                    if (! $bulletin) {
                        $bulletin = new ESBTPBulletin;
                        $bulletin->etudiant_id = (int) $student->id;
                        $bulletin->classe_id = $classe->id;
                        $bulletin->annee_universitaire_id = $academicYearId;
                        $bulletin->periode = $period;
                        $bulletin->appreciation_generale = null;
                        $bulletin->decision_conseil = null;
                        $bulletin->user_id = $actor?->id;
                        $wasCreated = true;
                    }

                    $this->syncBulletinConfiguration($bulletin, $classe->id, $academicYearId, $period);
                    $bulletin->save();

                    $this->bulletinService->genererDonneesBulletin(
                        (int) $student->id,
                        $classe->id,
                        $academicYearId,
                        $period
                    );
                });

                if ($wasCreated) {
                    $created++;
                } else {
                    $regenerated++;
                }
            } catch (AcademicPilotageException $e) {
                $blockingErrors[] = $this->studentPayload($student) + [
                    'code' => $e->errorCode,
                    'message' => $e->getMessage(),
                    'details' => $e->details,
                ];
                Log::warning('Generation bulletin BTS bloquee.', [
                    'student_id' => (int) $student->id,
                    'class_id' => $classe->id,
                    'academic_year_id' => $academicYearId,
                    'period' => $period,
                    'error' => $e->errorCode,
                    'details' => $e->details,
                ]);
            } catch (BulletinConfigurationException $e) {
                $blockingErrors[] = $this->studentPayload($student) + [
                    'code' => 'missing_subject_configuration',
                    'message' => $e->getMessage(),
                    'details' => $e->getContext(),
                ];
            } catch (\RuntimeException $e) {
                $blockingErrors[] = $this->studentPayload($student) + [
                    'code' => str_contains($e->getMessage(), 'Coefficient manquant') ? 'coefficients_missing' : 'generation_blocked',
                    'message' => $e->getMessage(),
                ];
            } catch (\Throwable $e) {
                $errors[] = $this->studentPayload($student) + [
                    'code' => 'generation_failed',
                    'message' => $e->getMessage(),
                ];
                Log::error('Erreur generation bulletin BTS.', [
                    'student_id' => (int) $student->id,
                    'class_id' => $classe->id,
                    'academic_year_id' => $academicYearId,
                    'period' => $period,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        if ($created > 0 || $regenerated > 0) {
            $this->bulletinService->calculerRangsPourClasse($classe->id, $academicYearId, $period);
        }

        return new BulkBulletinGenerationResult(
            created: $created,
            regenerated: $regenerated,
            skipped: $skipped,
            blockingErrors: $this->deduplicateStudentBlocks($blockingErrors),
            errors: $errors,
            preflight: $preflight,
        );
    }

    /**
     * Etudiants dont le bulletin de CE semestre appartient a CETTE classe.
     *
     * La selection passe par la cohorte de phases et non par l'inscription
     * courante. Un etudiant de BTS suit le tronc commun au semestre 1 puis sa
     * specialite au semestre 2, avec une seule inscription, deplacee vers la
     * nouvelle classe. Chercher par `inscriptions.classe_id` revenait donc a
     * chercher les eleves du semestre 1 dans une classe qu'ils ont quittee :
     * la generation repondait « rien a generer » et les bulletins de tronc
     * commun restaient sans valeurs figees.
     *
     * Constate sur esbtp-yakro : TRONC COMMUN K comptait 3 inscrits courants
     * pour une cohorte de 70 au semestre 1.
     */
    private function activeStudentsForClass(int $classeId, int $academicYearId, string $period): Collection
    {
        $etudiantIds = $this->cohortCounter->etudiantIds($classeId, $academicYearId, $period);

        if ($etudiantIds === []) {
            return collect();
        }

        return ESBTPInscription::query()
            ->with(['etudiant:id,nom,prenoms,matricule'])
            ->whereIn('etudiant_id', $etudiantIds)
            ->where('annee_universitaire_id', $academicYearId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->get()
            ->pluck('etudiant')
            ->filter()
            ->unique('id')
            ->sortBy([
                ['nom', 'asc'],
                ['prenoms', 'asc'],
            ])
            ->values();
    }

    /**
     * Le sort d'un bulletin qui existe deja, decide en un seul endroit.
     *
     * Un bulletin SANS MOYENNE n'est pas un travail fait : il est a refaire,
     * que le recalcul soit demande ou non. Le traiter comme « deja genere »
     * laissait des classes entieres avec soixante-dix bulletins vides et un
     * ecran qui annoncait « rien a generer ».
     *
     * Un bulletin publie ou signe reste intouchable, meme vide : on l'ecarte
     * sans bloquer le reste de la classe. Le pre-controle et la generation
     * lisent cette meme methode : quand ils portaient chacun leur version de
     * la regle, une tranche composee uniquement de vides-verrouilles renvoyait
     * 422, la boucle du front s'arretait, et les tranches suivantes ne
     * partaient jamais, a chaque relance.
     *
     * @return array{code: string, message: string}|null null = a (re)faire
     */
    private function ecartPourBulletinExistant(?ESBTPBulletin $bulletin, bool $recalculate): ?array
    {
        if (! $bulletin || $recalculate) {
            return null;
        }

        if ($bulletin->moyenne_generale !== null) {
            return [
                'code' => self::ECART_DEJA_GENERE,
                'message' => 'Bulletin deja genere pour cette periode.',
            ];
        }

        if (! $this->isProtected($bulletin)) {
            return null;
        }

        return [
            'code' => self::ECART_VIDE_VERROUILLE,
            'message' => 'Bulletin publie ou signe mais sans moyenne : deverrouillez-le pour le regenerer.',
        ];
    }

    private function findBulletin(int $studentId, int $classeId, int $academicYearId, string $period): ?ESBTPBulletin
    {
        return ESBTPBulletin::where('etudiant_id', $studentId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $academicYearId)
            ->whereIn('periode', $this->bulletinService->periodeAliases($period))
            ->latest('updated_at')
            ->first();
    }

    private function isProtected(ESBTPBulletin $bulletin): bool
    {
        return (bool) $bulletin->is_published
            || (bool) $bulletin->signature_responsable
            || (bool) $bulletin->signature_directeur
            || (bool) $bulletin->signature_parent;
    }

    private function syncBulletinConfiguration(ESBTPBulletin $bulletin, int $classeId, int $academicYearId, string $period): void
    {
        $config = $this->configMatieresPayload($classeId, $academicYearId, $period);

        if ($config === ['generales' => [], 'techniques' => []]) {
            throw new BulletinConfigurationException(
                'La configuration des matieres du bulletin est manquante.',
                [
                    'classe_id' => $classeId,
                    'annee_universitaire_id' => $academicYearId,
                    'periode' => $period,
                    'configuration_url' => $this->configurationUrl($classeId, $academicYearId, $period),
                ]
            );
        }

        $currentConfig = $this->decodeJsonToArray($bulletin->config_matieres);
        if (empty($currentConfig['generales']) && empty($currentConfig['techniques'])) {
            $bulletin->config_matieres = $config;
        }

        if (trim((string) $bulletin->professeurs) === '') {
            $bulletin->professeurs = json_encode($this->professeursTemplate($classeId, $academicYearId, $period));
        }
    }

    private function hasSubjectConfiguration(int $classeId, int $academicYearId, string $period): bool
    {
        return $this->configMatieresPayload($classeId, $academicYearId, $period) !== ['generales' => [], 'techniques' => []];
    }

    private function configMatieresPayload(int $classeId, int $academicYearId, string $period): array
    {
        $payload = ['generales' => [], 'techniques' => []];

        $rows = ESBTPConfigMatiere::query()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $academicYearId)
            ->whereIn('periode', $this->configPeriods($period))
            ->get(['matiere_id', 'config']);

        foreach ($rows as $row) {
            $config = is_array($row->config) ? $row->config : $this->decodeJsonToArray($row->config);
            $type = $config['type'] ?? null;

            if (in_array($type, ['general', 'generale'], true)) {
                $payload['generales'][] = (int) $row->matiere_id;
            }

            if (in_array($type, ['technique', 'technologique_professionnelle'], true)) {
                $payload['techniques'][] = (int) $row->matiere_id;
            }
        }

        $payload['generales'] = array_values(array_unique($payload['generales']));
        $payload['techniques'] = array_values(array_unique($payload['techniques']));

        return $payload;
    }

    private function configPeriods(string $period): array
    {
        $period = $this->bulletinService->normalizePeriode($period);

        return $period === 'annuel' ? ['semestre1', 'semestre2'] : [$period];
    }

    private function professeursTemplate(int $classeId, int $academicYearId, string $period): array
    {
        foreach ($this->configPeriods($period) as $targetPeriod) {
            $raw = SettingsHelper::get($this->professeursTemplateKey($classeId, $academicYearId, $targetPeriod), null);
            $template = is_string($raw) ? $this->decodeJsonToArray($raw) : (array) $raw;
            $template = array_filter($template, fn ($value) => trim((string) $value) !== '');

            if ($template !== []) {
                return $template;
            }
        }

        $template = ESBTPBulletin::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $academicYearId)
            ->whereIn('periode', $this->bulletinService->periodeAliases($period))
            ->whereNotNull('professeurs')
            ->where('professeurs', '!=', '')
            ->where('professeurs', '!=', '{}')
            ->latest('updated_at')
            ->value('professeurs');

        return $this->decodeJsonToArray($template);
    }

    private function professeursTemplateKey(int $classeId, int $academicYearId, string $period): string
    {
        return "bulletin_professeurs_template.{$classeId}.{$academicYearId}.{$period}";
    }

    private function missingProfesseurRows(int $classeId, int $academicYearId, string $period): array
    {
        // Seules les matières réellement retenues au bulletin (générales/techniques)
        // exigent un professeur ; celles marquées « Ignorer » (type none) sont exclues.
        $payload = $this->configMatieresPayload($classeId, $academicYearId, $period);
        $subjectIds = collect(array_merge($payload['generales'], $payload['techniques']))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($subjectIds->isEmpty()) {
            return [];
        }

        // On n'exige un professeur que pour les matieres reellement notees sur
        // ce semestre dans cette classe. Le bulletin construit sa liste a partir
        // des notes : une matiere configuree mais jamais evaluee n'y figurera
        // pas, et bloquer la generation pour elle n'a aucun sens. C'est ce qui
        // faisait echouer le pre-controle sur des matieres de l'autre semestre,
        // et jusqu'a des identifiants sans matiere correspondante.
        $noteesIds = $this->matieresNoteesIds($classeId, $academicYearId, $period);
        $subjectIds = $subjectIds->intersect($noteesIds)->values();

        if ($subjectIds->isEmpty()) {
            return [];
        }

        $professeurs = $this->professeursTemplate($classeId, $academicYearId, $period);
        $missingIds = $subjectIds
            ->filter(fn (int $id) => trim((string) ($professeurs[$id] ?? $professeurs[(string) $id] ?? '')) === '')
            ->values();

        if ($missingIds->isEmpty()) {
            return [];
        }

        // withTrashed : une matiere en corbeille garde ses notes et ses resultats
        // manuels. Sans elle, le libelle retombait sur « Matiere #56 », introuvable
        // pour qui doit corriger. Un identifiant nu ne doit jamais atteindre un ecran.
        $names = ESBTPMatiere::withTrashed()->whereIn('id', $missingIds)->pluck('name', 'id');

        return $missingIds
            ->map(fn (int $id) => [
                'matiere_id' => $id,
                'matiere' => $names[$id] ?? "Matiere #{$id}",
            ])
            ->all();
    }

    /**
     * Matieres qui portent au moins une note sur ce semestre dans cette classe.
     *
     * Meme source que le bulletin lui-meme, qui construit sa liste de matieres
     * a partir des notes et non de la configuration. Les evaluations annulees
     * sont exclues, et les alias de periode heritees prises en compte.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function matieresNoteesIds(int $classeId, int $academicYearId, string $period): Collection
    {
        return ESBTPEvaluation::query()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $academicYearId)
            ->where('status', '!=', 'cancelled')
            ->whereIn('periode', ESBTPEvaluation::aliasDePeriode($period))
            ->whereHas('notes')
            // Une matiere supprimee garde ses evaluations et ses notes, mais le
            // bulletin l'ignore : il construit sa liste depuis la relation, qui
            // ne rend rien pour une matiere en corbeille. Exiger un professeur
            // pour elle bloquait la classe sur « Matiere #56 », un libelle que
            // personne ne peut retrouver et une case que rien ne permet de
            // remplir. La relation exclut la corbeille : le blocage disparait.
            ->whereHas('matiere')
            ->distinct()
            ->pluck('matiere_id')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    private function missingCoefficientRows(BulletinPreparationResult $preparation): array
    {
        $missingItems = $preparation->evidence['missing_configuration'] ?? [];
        $ids = collect($missingItems)
            ->map(fn ($item) => preg_match('/coefficient:matiere:(\d+)/', (string) $item, $matches) ? (int) $matches[1] : null)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        // withTrashed : une matiere en corbeille garde ses notes et ses resultats
        // manuels. Sans elle, le libelle retombait sur « Matiere #56 », introuvable
        // pour qui doit corriger. Un identifiant nu ne doit jamais atteindre un ecran.
        $names = ESBTPMatiere::withTrashed()->whereIn('id', $ids)->pluck('name', 'id');

        return $ids
            ->map(fn (int $id) => [
                'matiere_id' => $id,
                'matiere' => $names[$id] ?? "Matiere #{$id}",
            ])
            ->values()
            ->all();
    }

    private function deduplicateStudentBlocks(array $blocks): array
    {
        $seen = [];
        $deduped = [];

        foreach ($blocks as $block) {
            $key = ($block['student_id'] ?? 'na').'|'.($block['code'] ?? 'na');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $deduped[] = $block;
        }

        return $deduped;
    }

    private function preflightMessage(string $status, int $verrouillesVides, bool $hasHardBlocks, ?ESBTPClasse $classe = null): string
    {
        return match ($status) {
            'no_students' => $this->noStudentsMessage($classe),
            // Conseiller « Recalculer » a une classe entierement verrouillee
            // enverrait l'utilisateur dans le mur : le recalcul y produit des
            // blocages durs. Le seul geste utile est de deverrouiller.
            'nothing_to_generate' => $verrouillesVides > 0
                ? 'Aucun bulletin ne peut etre repris : '.$verrouillesVides.' bulletin(s) sans moyenne sont publies ou signes. Deverrouillez-les avant de relancer.'
                : 'Tous les bulletins existent deja pour cette periode : cochez « Recalculer » pour les mettre a jour.',
            'needs_reason' => 'Donnees academiques incompletes : renseignez un motif (8 caracteres minimum) pour generer des bulletins incomplets.',
            'blocked' => $hasHardBlocks
                ? 'Pre-controle bloque : completez les matieres, coefficients et professeurs requis avant de generer.'
                : 'Pre-controle bloque : donnees academiques incompletes et vous n\'avez pas le droit de generer un bulletin incomplet.',
            'ready' => 'Pre-controle valide : la generation peut etre lancee.',
            default => 'Pre-controle indisponible.',
        };
    }

    private function noStudentsMessage(?ESBTPClasse $classe): string
    {
        // Une classe de tronc commun vide n'est pas une anomalie : ses etudiants ont ete
        // orientes en specialite. La generation annuelle (S1 TC + S2 specialite) se lance
        // depuis la classe de specialite, ou le S1 du tronc commun est agrege automatiquement.
        if ($classe?->filiere?->isTroncCommun()) {
            return 'Aucun étudiant actif dans cette classe de tronc commun : ils ont été orientés en spécialité. '
                .'Générez les bulletins depuis chaque classe de spécialité (le semestre 1 du tronc commun y est agrégé automatiquement).';
        }

        return 'Aucun etudiant actif et valide dans cette classe pour cette annee.';
    }

    private function configurationUrl(int $classeId, int $academicYearId, string $period): string
    {
        $params = [
            'classe_id' => $classeId,
            'periode' => $period,
            'annee_universitaire_id' => $academicYearId,
        ];

        $sampleStudentId = ESBTPInscription::query()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $academicYearId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->orderBy('date_inscription')
            ->orderBy('id')
            ->value('etudiant_id');

        if ($sampleStudentId) {
            $params['bulletin'] = (int) $sampleStudentId;
            $params['etudiant_id'] = (int) $sampleStudentId;
        }

        return route('esbtp.bulletins.config-matieres', $params);
    }

    private function studentPayload(object $student): array
    {
        return [
            'student_id' => (int) $student->id,
            'student' => trim(($student->nom ?? '').' '.($student->prenoms ?? '')),
            'matricule' => $student->matricule,
        ];
    }

    private function decodeJsonToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
