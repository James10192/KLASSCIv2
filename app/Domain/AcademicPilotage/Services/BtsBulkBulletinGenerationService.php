<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\BulkBulletinGenerationResult;
use App\Domain\AcademicPilotage\DTO\BulletinPreparationResult;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Exceptions\BulletinConfigurationException;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
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
    public function __construct(
        private readonly BulletinService $bulletinService,
        private readonly BulletinGenerationReadinessService $readiness,
    ) {}

    public function preflight(
        ESBTPClasse $classe,
        int $academicYearId,
        string $period,
        ?User $actor,
        bool $recalculate = false,
    ): array {
        $period = $this->bulletinService->normalizePeriode($period);
        $students = $this->activeStudentsForClass($classe->id, $academicYearId);
        $skipped = [];
        $blockingErrors = [];
        $missingCoefficientBuckets = [];
        $configurationUrl = $this->configurationUrl($classe->id, $academicYearId, $period);
        $hasSubjectConfiguration = $this->hasSubjectConfiguration($classe->id, $academicYearId, $period);

        foreach ($students as $student) {
            $existing = $this->findBulletin((int) $student->id, $classe->id, $academicYearId, $period);

            if ($existing && ! $recalculate) {
                $skipped[] = $this->studentPayload($student) + [
                    'code' => 'bulletin_exists',
                    'message' => 'Bulletin deja genere pour cette periode.',
                    'bulletin_id' => $existing->id,
                ];
            }

            if ($existing && $recalculate && $this->isProtected($existing)) {
                $blockingErrors[] = $this->studentPayload($student) + [
                    'code' => 'bulletin_locked',
                    'message' => 'Bulletin publie ou signe : le recalcul est bloque.',
                    'bulletin_id' => $existing->id,
                ];
            }

            if (! $hasSubjectConfiguration) {
                $blockingErrors[] = $this->studentPayload($student) + [
                    'code' => 'missing_subject_configuration',
                    'message' => 'La configuration des matieres du bulletin est manquante.',
                    'configuration_url' => $configurationUrl,
                ];
            }

            $preparation = $this->readiness->inspect('BTS', (int) $student->id, $classe->id, $academicYearId, $period);

            foreach ($this->missingCoefficientRows($preparation) as $missing) {
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
                $blockingErrors[] = $this->studentPayload($student) + [
                    'code' => 'incomplete_academic_data',
                    'message' => 'Donnees academiques incompletes.',
                    'preparation' => $preparation->toArray(),
                    'missing_coefficients' => $this->missingCoefficientRows($preparation),
                    'configuration_url' => $configurationUrl,
                ];
            }
        }

        $blockingErrors = $this->deduplicateStudentBlocks($blockingErrors);
        $canOverrideIncomplete = (bool) ($actor?->can('bulletins.generate_incomplete') ?? false);

        return [
            'ok' => $blockingErrors === [],
            'can_generate' => $blockingErrors === [],
            'can_generate_incomplete' => $canOverrideIncomplete,
            'requires_incomplete_reason' => $blockingErrors !== [] && $canOverrideIncomplete,
            'classe' => [
                'id' => $classe->id,
                'name' => $classe->name,
            ],
            'annee_universitaire_id' => $academicYearId,
            'periode' => $period,
            'students_count' => $students->count(),
            'existing_count' => collect($skipped)->where('code', 'bulletin_exists')->count(),
            'recalculer' => $recalculate,
            'skipped' => $skipped,
            'blocking_errors' => $blockingErrors,
            'missing_coefficients' => array_values($missingCoefficientBuckets),
            'configuration_url' => $configurationUrl,
            'message' => $this->preflightMessage($students->count(), $blockingErrors, $skipped, $canOverrideIncomplete),
        ];
    }

    public function generate(
        ESBTPClasse $classe,
        int $academicYearId,
        string $period,
        ?User $actor,
        bool $recalculate = false,
        ?string $incompleteReason = null,
    ): BulkBulletinGenerationResult {
        $period = $this->bulletinService->normalizePeriode($period);
        $preflight = $this->preflight($classe, $academicYearId, $period, $actor, $recalculate);
        $students = $this->activeStudentsForClass($classe->id, $academicYearId);
        $created = 0;
        $regenerated = 0;
        $skipped = [];
        $blockingErrors = [];
        $errors = [];

        foreach ($students as $student) {
            $existing = $this->findBulletin((int) $student->id, $classe->id, $academicYearId, $period);

            if ($existing && ! $recalculate) {
                $skipped[] = $this->studentPayload($student) + [
                    'code' => 'bulletin_exists',
                    'message' => 'Bulletin deja genere pour cette periode.',
                    'bulletin_id' => $existing->id,
                ];
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

        return new BulkBulletinGenerationResult(
            created: $created,
            regenerated: $regenerated,
            skipped: $skipped,
            blockingErrors: $this->deduplicateStudentBlocks($blockingErrors),
            errors: $errors,
            preflight: $preflight,
        );
    }

    private function activeStudentsForClass(int $classeId, int $academicYearId): Collection
    {
        return ESBTPInscription::query()
            ->with(['etudiant:id,nom,prenoms,matricule'])
            ->where('classe_id', $classeId)
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

    private function findBulletin(int $studentId, int $classeId, int $academicYearId, string $period): ?ESBTPBulletin
    {
        return ESBTPBulletin::where('etudiant_id', $studentId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $academicYearId)
            ->whereIn('periode', $this->periodAliases($period))
            ->latest('updated_at')
            ->first();
    }

    private function periodAliases(string $period): array
    {
        $period = $this->bulletinService->normalizePeriode($period);

        return match ($period) {
            'semestre1' => ['semestre1', '1'],
            'semestre2' => ['semestre2', '2'],
            default => [$period],
        };
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
        $template = ESBTPBulletin::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $academicYearId)
            ->whereIn('periode', $this->periodAliases($period))
            ->whereNotNull('professeurs')
            ->where('professeurs', '!=', '')
            ->where('professeurs', '!=', '{}')
            ->latest('updated_at')
            ->value('professeurs');

        return $this->decodeJsonToArray($template);
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

        $names = ESBTPMatiere::whereIn('id', $ids)->pluck('name', 'id');

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

    private function preflightMessage(int $studentsCount, array $blockingErrors, array $skipped, bool $canOverrideIncomplete): string
    {
        if ($studentsCount === 0) {
            return 'Aucun etudiant actif et valide dans cette classe pour cette annee.';
        }

        if ($blockingErrors === []) {
            return 'Pre-controle valide : la generation peut etre lancee.';
        }

        if ($canOverrideIncomplete) {
            return 'Pre-controle bloque : completez la configuration ou renseignez un motif pour generer un bulletin incomplet.';
        }

        return 'Pre-controle bloque : completez la configuration academique avant de generer.';
    }

    private function configurationUrl(int $classeId, int $academicYearId, string $period): string
    {
        return route('esbtp.bulletins.config-matieres', [
            'classe_id' => $classeId,
            'periode' => $period,
            'annee_universitaire_id' => $academicYearId,
        ]);
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
