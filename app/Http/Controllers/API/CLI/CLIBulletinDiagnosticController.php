<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNote;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Diagnostic d'un bulletin : ce qu'il a fige contre ce qui est recalcule.
 *
 * Un bulletin conserve la classe, l'effectif, la moyenne et le rang tels
 * qu'ils etaient au moment de sa generation. Les statistiques de classe et le
 * rang, eux, sont recalcules a chaque lecture contre l'etat courant des
 * inscriptions. Quand un etudiant change de classe entre les deux, les deux
 * sources divergent sans que rien ne le signale.
 *
 * Lecture seule. Aucune correction n'est appliquee ici : on etablit les faits.
 */
class CLIBulletinDiagnosticController extends BaseApiController
{
    /**
     * GET /api/cli/diagnostics/bulletins
     *
     * Retrouve des bulletins par nom de classe FIGE. La liste des classes
     * ordinaire masque les classes sans inscription active : elle ne permet
     * donc pas d'atteindre une classe videe par les reorientations, qui est
     * precisement le cas a instruire.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $limite = min(50, max(1, (int) $request->query('limit', 10)));

        $bulletins = ESBTPBulletin::query()
            ->with([
                'etudiant:id,nom,prenoms,matricule',
                'classe:id,name',
                'anneeUniversitaire:id,name',
            ])
            ->when($request->filled('classe'), function ($q) use ($request) {
                $motif = '%'.$request->query('classe').'%';
                $q->whereHas('classe', fn ($c) => $c->where('name', 'like', $motif));
            })
            ->when($request->filled('etudiant_id'), fn ($q) => $q->where('etudiant_id', (int) $request->query('etudiant_id')))
            ->when($request->filled('periode'), fn ($q) => $q->where('periode', $request->query('periode')))
            ->when($request->filled('annee_id'), fn ($q) => $q->where('annee_universitaire_id', (int) $request->query('annee_id')))
            ->orderByDesc('id')
            ->limit($limite)
            ->get()
            ->map(fn (ESBTPBulletin $b) => [
                'bulletin_id' => $b->id,
                'etudiant' => $this->nomComplet($b),
                'matricule' => $b->etudiant->matricule ?? null,
                'classe_figee' => $b->classe->name ?? null,
                'periode' => $b->periode,
                'annee' => $b->anneeUniversitaire->name ?? null,
                'moyenne_figee' => $b->moyenne_generale,
                'rang_fige' => $b->rang,
                'effectif_fige' => $b->effectif_classe,
            ]);

        return $this->successResponse([
            'bulletins' => $bulletins,
            'total' => $bulletins->count(),
        ]);
    }

    /**
     * GET /api/cli/diagnostics/bulletins/config
     *
     * Coefficients, type general/technique et professeurs figes, pour
     * les 11 matieres S1 de 1ere annee BTS (hors MGP).
     */
    public function configCoverage(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $annee = $request->filled('annee_id')
            ? ESBTPAnneeUniversitaire::find((int) $request->query('annee_id'))
            : ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (! $annee) {
            return $this->errorResponse('Aucune annee universitaire courante configuree.', ['code' => 'NO_ACADEMIC_YEAR'], 422);
        }

        $periode = $request->query('periode', 'semestre1');
        $year = (int) $request->query('year', 1);

        $classes = ESBTPClasse::query()
            ->where('systeme_academique', 'BTS')
            ->whereHas('niveau', fn ($q) => $q->where('year', $year))
            ->with(['filiere:id,name', 'niveau:id,name,year'])
            ->withCount(['inscriptions as effectif' => function ($q) use ($annee) {
                $q->where('annee_universitaire_id', $annee->id)
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree');
            }])
            ->orderBy('filiere_id')
            ->orderBy('name')
            ->get()
            ->filter(function (ESBTPClasse $c) {
                $filiere = mb_strtoupper((string) ($c->filiere->name ?? ''));

                return $c->effectif > 0
                    && ! str_contains(mb_strtolower($c->name), 'soir')
                    && ! str_contains(mb_strtolower($c->name), 'test')
                    && ! str_contains($filiere, 'MINE')
                    && ! str_contains($filiere, 'PETROLE');
            })
            ->values();

        $classIds = $classes->pluck('id');
        $configs = ESBTPConfigMatiere::query()
            ->with('matiere:id,name')
            ->whereIn('classe_id', $classIds)
            ->where('annee_universitaire_id', $annee->id)
            ->whereIn('periode', [$periode, str_replace('semestre', '', $periode)])
            ->get()
            ->groupBy('classe_id');

        $bulletins = ESBTPBulletin::query()
            ->whereIn('classe_id', $classIds)
            ->where('annee_universitaire_id', $annee->id)
            ->where('periode', $periode)
            ->whereNotNull('professeurs')
            ->where('professeurs', '!=', '')
            ->where('professeurs', '!=', '{}')
            ->orderByDesc('updated_at')
            ->get(['classe_id', 'professeurs', 'config_matieres'])
            ->unique('classe_id')
            ->keyBy('classe_id');

        $filiereNiveauPairs = $classes->map(fn (ESBTPClasse $c) => $c->filiere_id.'|'.$c->niveau_etude_id)->unique()->values();
        $coefficients = ESBTPMatiereCoefficient::query()
            ->where('annee_universitaire_id', $annee->id)
            ->where('periode', $periode)
            ->get()
            ->groupBy(fn ($row) => $row->filiere_id.'|'.$row->niveau_etude_id);

        $planifs = ESBTPPlanificationAcademique::query()
            ->with('enseignantPrincipal:id,name')
            ->where('annee_universitaire_id', $annee->id)
            ->whereIn('semestre', $periode === 'semestre2' ? [2] : [1])
            ->whereNotNull('enseignant_principal_id')
            ->get(['filiere_id', 'niveau_etude_id', 'matiere_id', 'enseignant_principal_id']);

        $rows = $classes->map(function (ESBTPClasse $classe) use ($configs, $bulletins, $coefficients, $planifs) {
            $classConfigs = $configs->get($classe->id, collect());
            $bulletin = $bulletins->get($classe->id);
            $profsBulletin = [];
            if ($bulletin) {
                $raw = is_string($bulletin->professeurs) ? json_decode($bulletin->professeurs, true) : $bulletin->professeurs;
                $profsBulletin = is_array($raw) ? $raw : [];
            }
            $coefKey = $classe->filiere_id.'|'.$classe->niveau_etude_id;
            $coefs = ($coefficients->get($coefKey, collect()))->keyBy('matiere_id');
            $profsPlanif = $planifs
                ->where('filiere_id', $classe->filiere_id)
                ->where('niveau_etude_id', $classe->niveau_etude_id)
                ->filter(fn ($p) => trim((string) ($p->enseignantPrincipal->name ?? '')) !== '')
                ->mapWithKeys(fn ($p) => [(int) $p->matiere_id => trim($p->enseignantPrincipal->name)]);

            $matieres = $classConfigs->map(function (ESBTPConfigMatiere $row) use ($coefs, $profsBulletin, $profsPlanif) {
                $cfg = is_array($row->config) ? $row->config : [];
                $type = $cfg['type'] ?? null;
                $matiereId = (int) $row->matiere_id;
                $canon = $this->canonS1($row->matiere->name ?? '');
                $prof = trim((string) ($profsBulletin[$matiereId] ?? $profsBulletin[(string) $matiereId] ?? $profsPlanif[$matiereId] ?? ''));
                $coef = $coefs->get($matiereId)?->coefficient;

                return [
                    'matiere_id' => $matiereId,
                    'matiere' => $row->matiere->name ?? null,
                    'canon' => $canon,
                    'type' => $type,
                    'coefficient' => $coef !== null ? (float) $coef : null,
                    'professeur' => $prof !== '' ? $prof : null,
                ];
            })->values();

            $canonRows = $matieres->filter(fn ($m) => $m['canon'] !== null)->values();
            $sansProf = $canonRows->filter(fn ($m) => $m['professeur'] === null)->pluck('canon')->values();
            $sansCoef = $canonRows->filter(fn ($m) => $m['coefficient'] === null)->pluck('canon')->values();
            $presentes = $canonRows->pluck('canon')->unique()->values();
            $manquantes = collect($this->canonAttendues())->diff($presentes)->values();

            return [
                'classe_id' => (int) $classe->id,
                'classe' => $classe->name,
                'filiere' => $classe->filiere->name ?? null,
                'effectif' => (int) $classe->effectif,
                'canon_presentes' => $presentes->count(),
                'canon_manquantes' => $manquantes->all(),
                'sans_coefficient' => $sansCoef->all(),
                'sans_professeur' => $sansProf->all(),
                'pret_config' => $manquantes->isEmpty() && $sansCoef->isEmpty() && $sansProf->isEmpty(),
                'matieres' => $canonRows->all(),
            ];
        });

        return $this->successResponse([
            'annee' => ['id' => $annee->id, 'name' => $annee->name ?? $annee->libelle],
            'periode' => $periode,
            'norme' => $this->canonAttendues(),
            'summary' => [
                'classes' => $rows->count(),
                'pret_config' => $rows->where('pret_config', true)->count(),
                'sans_professeur' => $rows->filter(fn ($r) => $r['sans_professeur'] !== [])->count(),
                'sans_coefficient' => $rows->filter(fn ($r) => $r['sans_coefficient'] !== [])->count(),
                'canon_incomplete' => $rows->filter(fn ($r) => $r['canon_manquantes'] !== [])->count(),
            ],
            'classes' => $rows->all(),
        ]);
    }

    /**
     * GET /api/cli/diagnostics/bulletin/{id}
     *
     * Confronte les valeurs figees du bulletin a ce que le code recalculerait
     * aujourd'hui, et retrace l'origine de chaque matiere affichee.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $bulletin = ESBTPBulletin::with([
            'etudiant:id,nom,prenoms,matricule',
            'classe:id,name',
            'anneeUniversitaire:id,name',
        ])->find($id);

        if (! $bulletin) {
            return $this->errorResponse("Bulletin {$id} introuvable.", ['code' => 'BULLETIN_NOT_FOUND'], 404);
        }

        $divergences = [];

        $inscription = $this->inscriptionCourante($bulletin);
        if ($inscription && (int) $inscription->classe_id !== (int) $bulletin->classe_id) {
            $divergences[] = "L'etudiant est aujourd'hui inscrit en "
                .($inscription->classe->name ?? 'classe inconnue')
                .', alors que le bulletin a fige '
                .($bulletin->classe->name ?? 'classe inconnue').'.';
        }

        $populations = $this->populationsDeLaClasse($bulletin);
        if ($populations['cohorte_du_semestre'] === 0) {
            $divergences[] = 'Aucun etudiant n\'appartient a la cohorte de ce semestre pour la '
                .'classe figee. Les statistiques et le rang portent donc sur une population vide, '
                .'ce qui produit les zeros constates a l\'affichage.';
        } elseif ($bulletin->effectif_classe && $populations['cohorte_du_semestre'] !== (int) $bulletin->effectif_classe) {
            $divergences[] = "L'effectif imprime est {$bulletin->effectif_classe}, "
                ."alors que la cohorte du semestre en compte {$populations['cohorte_du_semestre']}. "
                .'Le rang affiche et l\'effectif imprime ne portent pas sur la meme population.';
        }

        $matieres = $this->matieresAffichees($bulletin);
        $etrangeres = array_values(array_map(
            fn (array $m) => $m['matiere'],
            array_filter($matieres, fn (array $m) => $m['vient_d_une_autre_classe'])
        ));

        if ($etrangeres !== []) {
            $divergences[] = 'Ces matieres proviennent d\'evaluations rattachees a une autre classe '
                .'que celle du bulletin : '.implode(', ', $etrangeres).'. '
                .'La requete des notes ne filtre pas par classe.';
        }

        return $this->successResponse([
            'bulletin' => [
                'id' => $bulletin->id,
                'etudiant' => $this->nomComplet($bulletin),
                'matricule' => $bulletin->etudiant->matricule ?? null,
                'periode' => $bulletin->periode,
                'annee' => $bulletin->anneeUniversitaire->name ?? null,
                'classe_figee' => [
                    'id' => $bulletin->classe_id,
                    'nom' => $bulletin->classe->name ?? null,
                ],
                'effectif_fige' => $bulletin->effectif_classe,
                'moyenne_figee' => $bulletin->moyenne_generale,
                'rang_fige' => $bulletin->rang,
                'mention_figee' => $bulletin->mention,
            ],
            'generation' => $this->etatDeGeneration($bulletin),
            'etat_actuel' => [
                'inscription_active' => $inscription ? [
                    'classe_id' => $inscription->classe_id,
                    'classe' => $inscription->classe->name ?? null,
                    'workflow_step' => $inscription->workflow_step,
                ] : null,
                'populations' => $populations,
            ],
            'matieres_affichees' => $matieres,
            'divergences' => $divergences,
            'verdict' => $divergences === []
                ? 'Aucune divergence detectee sur ce bulletin.'
                : count($divergences).' divergence(s) detectee(s).',
        ]);
    }

    private function nomComplet(ESBTPBulletin $bulletin): string
    {
        return trim(($bulletin->etudiant->nom ?? '').' '.($bulletin->etudiant->prenoms ?? ''));
    }

    /**
     * Une ligne de bulletin est d'abord creee pour porter la configuration des
     * matieres, puis renseignee par la generation officielle. Une moyenne nulle
     * ne signale donc pas une anomalie : elle dit que la generation n'a jamais
     * ete lancee, et que tout est recalcule a chaque affichage.
     *
     * C'est la distinction que fait deja le controleur des bulletins, qui
     * compte les bulletins generes avec whereNotNull('moyenne_generale').
     *
     * @return array<string, mixed>
     */
    private function etatDeGeneration(ESBTPBulletin $bulletin): array
    {
        $config = $bulletin->config_matieres;
        if (is_string($config)) {
            $config = json_decode($config, true);
        }
        $config = is_array($config) ? $config : [];

        $genere = $bulletin->moyenne_generale !== null;

        return [
            'genere' => $genere,
            'publie' => (bool) $bulletin->is_published,
            'matieres_configurees' => count($config['generales'] ?? []) + count($config['techniques'] ?? []),
            'lecture' => $genere
                ? 'Le bulletin a ete genere : ses valeurs sont figees.'
                : 'Le bulletin n\'a jamais ete genere. Moyenne, rang et effectif sont '
                    .'recalcules a chaque affichage, donc contre l\'etat courant des inscriptions.',
        ];
    }

    private function inscriptionCourante(ESBTPBulletin $bulletin): ?ESBTPInscription
    {
        return ESBTPInscription::with('classe:id,name')
            ->where('etudiant_id', $bulletin->etudiant_id)
            ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Deux facons de compter une classe, et leur ecart.
     *
     * `par_inscription` compte les etudiants actuellement rattaches a la
     * classe. C'est ce que faisait le calcul des statistiques avant d'etre
     * corrige : sur un bulletin de semestre 1, il cherchait les eleves dans une
     * classe que la reorientation avait videe, d'ou des zeros.
     *
     * `cohorte_du_semestre` resout la classe depuis l'historique de phases.
     * C'est ce que le calcul utilise desormais, et ce sur quoi le rang portait
     * deja.
     *
     * Les deux sont exposees parce que leur ecart est le symptome : une seule
     * ne dirait pas d'ou vient le probleme.
     *
     * @return array<string, int|string>
     */
    private function populationsDeLaClasse(ESBTPBulletin $bulletin): array
    {
        $parInscription = ESBTPEtudiant::whereHas('inscriptions', fn ($q) => $q
            ->where('classe_id', $bulletin->classe_id)
            ->where('annee_universitaire_id', $bulletin->annee_universitaire_id))
            ->count();

        $cohorte = app(BtsClassCohortCounter::class)->countPourPeriode(
            (int) $bulletin->classe_id,
            (int) $bulletin->annee_universitaire_id,
            (string) $bulletin->periode
        );

        return [
            'par_inscription' => $parInscription,
            'cohorte_du_semestre' => $cohorte,
            'lecture' => $parInscription === $cohorte
                ? 'Les deux comptes concordent.'
                : 'Les deux comptes different : des etudiants ont change de classe '
                    .'depuis ce semestre. Seule la cohorte du semestre fait foi.',
        ];
    }

    /**
     * Le bulletin construit sa liste de matieres a partir des notes de
     * l'etudiant, sans filtrer par classe. Une note prise ailleurs remonte donc
     * sur ce bulletin : la colonne classes_des_evaluations le montre.
     *
     * @return array<int, array<string, mixed>>
     */
    private function matieresAffichees(ESBTPBulletin $bulletin): array
    {
        $notes = ESBTPNote::with([
            'evaluation:id,titre,matiere_id,classe_id,periode',
            'evaluation.matiere:id,name',
            'evaluation.classe:id,name',
        ])
            ->where('etudiant_id', $bulletin->etudiant_id)
            ->whereHas('evaluation', fn ($q) => $q
                ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
                ->where('status', '!=', 'cancelled')
                ->whereIn('periode', ESBTPEvaluation::aliasDePeriode((string) $bulletin->periode)))
            ->get();

        $parMatiere = [];

        foreach ($notes as $note) {
            $evaluation = $note->evaluation;
            if (! $evaluation || ! $evaluation->matiere) {
                continue;
            }

            $nom = $evaluation->matiere->name;
            $classeEvaluation = $evaluation->classe->name ?? null;
            $ailleurs = $evaluation->classe_id
                && (int) $evaluation->classe_id !== (int) $bulletin->classe_id;

            if (! isset($parMatiere[$nom])) {
                $parMatiere[$nom] = [
                    'matiere' => $nom,
                    'nb_notes' => 0,
                    'classes_des_evaluations' => [],
                    'vient_d_une_autre_classe' => false,
                ];
            }

            $parMatiere[$nom]['nb_notes']++;

            if ($classeEvaluation && ! in_array($classeEvaluation, $parMatiere[$nom]['classes_des_evaluations'], true)) {
                $parMatiere[$nom]['classes_des_evaluations'][] = $classeEvaluation;
            }

            if ($ailleurs) {
                $parMatiere[$nom]['vient_d_une_autre_classe'] = true;
            }
        }

        return array_values($parMatiere);
    }

    /**
     * @return list<string>
     */
    private function canonAttendues(): array
    {
        return [
            'Maths',
            'Expression',
            'Eco',
            'Entrepr',
            'Droit',
            'Chimie',
            'Physique',
            'Anglais',
            'Dessin',
            'Info',
            'Secu',
        ];
    }

    private function canonS1(?string $name): ?string
    {
        $n = mb_strtolower((string) $name);
        $n = strtr($n, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'à' => 'a', 'î' => 'i', 'ô' => 'o', 'ù' => 'u', 'ç' => 'c']);

        return match (true) {
            str_contains($n, 'mathematiques') => 'Maths',
            str_contains($n, 'expression') && ! str_contains($n, 'anglais') => 'Expression',
            str_contains($n, 'economie') => 'Eco',
            str_contains($n, 'entrepreneuriat') => 'Entrepr',
            $n === 'droit' || str_starts_with($n, 'droit ') => 'Droit',
            $n === 'chimie' || str_starts_with($n, 'chimie ') => 'Chimie',
            $n === 'physique' || str_starts_with($n, 'physique ') => 'Physique',
            str_contains($n, 'anglais technique') => 'Anglais',
            str_contains($n, 'dessin technique') && str_contains($n, 'lecture') => 'Dessin',
            str_contains($n, 'informatique') => 'Info',
            str_contains($n, 'securite') => 'Secu',
            default => null,
        };
    }
}
