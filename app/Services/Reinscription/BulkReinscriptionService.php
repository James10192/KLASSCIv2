<?php

namespace App\Services\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Services\ReeinscriptionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BulkReinscriptionService — orchestration des réinscriptions en masse.
 *
 * Responsabilités :
 * - Lister les étudiants éligibles à la réinscription (année N-1 active + pas d'inscription N)
 * - Pré-calculer la décision et le solde pour chaque étudiant (preview)
 * - Exécuter une batch de réinscriptions en transaction atomique (rollback global si échec)
 * - Logger l'opération avec metadata pour audit
 *
 * Sépare la logique de batch des préoccupations 1-à-1 de ReeinscriptionService
 * qui gère les détails métier (frais, reliquats, notifications).
 */
class BulkReinscriptionService
{
    public function __construct(
        private readonly ReeinscriptionService $reeinscriptionService,
    ) {}

    /**
     * Liste les étudiants éligibles à réinscription pour l'année courante.
     *
     * Critères :
     * - Inscription N-1 active (status=active + workflow_step=etudiant_cree)
     * - Aucune inscription N (pas déjà réinscrit)
     * - Optionnellement filtrés par décision (passage/rattrapage/redoublement)
     */
    public function listEligibleStudents(?string $decisionFilter = null): Collection
    {
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$anneeCourante) {
            return collect();
        }

        $anneePrecedente = ESBTPAnneeUniversitaire::where('end_date', '<', $anneeCourante->start_date)
            ->orderBy('end_date', 'desc')
            ->first();
        if (!$anneePrecedente) {
            return collect();
        }

        $base = ESBTPEtudiant::query()
            ->select('id', 'matricule', 'nom', 'prenoms', 'telephone', 'email')
            ->whereHas('inscriptions', function ($q) use ($anneePrecedente) {
                $q->where('annee_universitaire_id', $anneePrecedente->id)
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree');
            })
            ->whereDoesntHave('inscriptions', function ($q) use ($anneeCourante) {
                $q->where('annee_universitaire_id', $anneeCourante->id);
            })
            ->with(['inscriptions' => function ($q) use ($anneePrecedente) {
                $q->where('annee_universitaire_id', $anneePrecedente->id)
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree')
                    ->with(['classe.niveau', 'classe.filiere']);
            }])
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        if (!$decisionFilter) {
            return $base;
        }

        return $base->filter(function ($etudiant) use ($decisionFilter, $anneePrecedente) {
            $inscription = $etudiant->inscriptions->first();
            if (!$inscription) {
                return false;
            }
            try {
                $analyse = $this->reeinscriptionService->analyserSituationEtudiantParInscription(
                    $inscription,
                    $anneePrecedente->name ?? (string) $anneePrecedente->id
                );
                return ($analyse['decision'] ?? null) === $decisionFilter;
            } catch (\Throwable $e) {
                Log::warning('BulkReinscription: analyse decision failed', [
                    'etudiant_id' => $etudiant->id,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        })->values();
    }

    /**
     * Pré-calcule un payload "preview" pour N étudiants avant validation utilisateur.
     * Retourne pour chacun : decision, moyenne, solde, peut_reinscrire, fiche_complete.
     */
    public function preview(array $etudiantIds): array
    {
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneePrecedente = $anneeCourante
            ? ESBTPAnneeUniversitaire::where('end_date', '<', $anneeCourante->start_date)
                ->orderBy('end_date', 'desc')
                ->first()
            : null;

        $stats = [
            'total' => count($etudiantIds),
            'eligible' => 0,
            'by_decision' => ['passage' => 0, 'rattrapage' => 0, 'redoublement' => 0, 'inconnu' => 0],
            'blocked' => ['solde' => 0, 'fiche_incomplete' => 0, 'analyse_error' => 0],
        ];

        $rows = [];
        foreach ($etudiantIds as $id) {
            $etudiant = ESBTPEtudiant::with(['inscriptions' => function ($q) use ($anneePrecedente) {
                if ($anneePrecedente) {
                    $q->where('annee_universitaire_id', $anneePrecedente->id)
                        ->where('status', 'active')
                        ->where('workflow_step', 'etudiant_cree')
                        ->with(['classe.niveau', 'classe.filiere']);
                }
            }])->find($id);

            if (!$etudiant) {
                $rows[] = ['etudiant_id' => $id, 'error' => 'Étudiant introuvable'];
                continue;
            }

            // Defense in depth : refuser tout étudiant ayant déjà une inscription en année courante.
            // Évite la corruption si le frontend list est stale ou contourné.
            if ($anneeCourante) {
                $aDejaInscrit = \App\Models\ESBTPInscription::where('etudiant_id', $id)
                    ->where('annee_universitaire_id', $anneeCourante->id)
                    ->exists();
                if ($aDejaInscrit) {
                    $rows[] = [
                        'etudiant_id' => $id,
                        'matricule' => $etudiant->matricule,
                        'nom_complet' => trim($etudiant->nom . ' ' . $etudiant->prenoms),
                        'error' => 'Déjà inscrit pour ' . $anneeCourante->name . ' — non éligible à la réinscription',
                    ];
                    $stats['blocked']['analyse_error']++;
                    continue;
                }
            }

            $inscription = $etudiant->inscriptions->first();
            if (!$inscription) {
                $rows[] = [
                    'etudiant_id' => $id,
                    'matricule' => $etudiant->matricule,
                    'nom_complet' => trim($etudiant->nom . ' ' . $etudiant->prenoms),
                    'error' => 'Aucune inscription active année précédente',
                ];
                $stats['blocked']['analyse_error']++;
                continue;
            }

            $row = [
                'etudiant_id' => $etudiant->id,
                'matricule' => $etudiant->matricule,
                'nom_complet' => trim($etudiant->nom . ' ' . $etudiant->prenoms),
                'classe_origine' => $inscription->classe?->name,
                'filiere' => $inscription->classe?->filiere?->name,
                'niveau' => $inscription->classe?->niveau?->name,
                'fiche_complete' => !empty($etudiant->telephone) && !empty($etudiant->email),
            ];

            try {
                $analyse = $this->reeinscriptionService->analyserSituationEtudiantParInscription(
                    $inscription,
                    $anneePrecedente->name ?? (string) $anneePrecedente->id
                );
                $row['moyenne'] = $analyse['moyenne_generale'] ?? null;
                $row['decision'] = $analyse['decision'] ?? 'inconnu';
                $row['matieres_echouees'] = collect($analyse['matieres_echouees'] ?? [])
                    ->map(fn ($m) => ['name' => $m['matiere']['name'] ?? null, 'moyenne' => $m['moyenne'] ?? null])
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                $row['decision'] = 'inconnu';
                $row['analyse_error'] = $e->getMessage();
                $stats['blocked']['analyse_error']++;
            }

            // Suggestions de classes cibles selon la décision (1-3 options typiquement)
            try {
                $suggestedClasses = $this->reeinscriptionService->proposerNouvellesClasses(
                    $etudiant->id,
                    $row['decision'] ?? 'redoublement'
                );
                $row['suggested_classes'] = collect($suggestedClasses)->map(fn ($c) => [
                    'id' => is_object($c) ? $c->id : $c,
                    'name' => is_object($c) ? $c->name : 'Classe #' . $c,
                    'filiere' => is_object($c) ? (optional($c->filiere)->name ?? '—') : null,
                    'niveau' => is_object($c) ? (optional($c->niveau)->name ?? '—') : null,
                ])->values()->all();
                $row['target_classe_id'] = $row['suggested_classes'][0]['id'] ?? null;
            } catch (\Throwable $e) {
                $row['suggested_classes'] = [];
                $row['target_classe_id'] = null;
            }
            $row['affectation_status'] = \App\Models\ESBTPInscription::DEFAULT_AFFECTATION_STATUS;
            $row['observations'] = null;

            $row['solde_restant'] = (float) $this->reeinscriptionService->calculerSoldeInscription($inscription);
            $row['peut_reinscrire'] = $row['solde_restant'] <= 0
                || (auth()->check() && auth()->user()->hasRole('superAdmin'));

            if (!$row['peut_reinscrire']) {
                $stats['blocked']['solde']++;
            }
            if (!$row['fiche_complete']) {
                $stats['blocked']['fiche_incomplete']++;
            }

            $key = $row['decision'] ?? 'inconnu';
            if (isset($stats['by_decision'][$key])) {
                $stats['by_decision'][$key]++;
            }
            if ($row['peut_reinscrire'] && ($row['decision'] ?? null) !== 'inconnu') {
                $stats['eligible']++;
            }

            $rows[] = $row;
        }

        return [
            'stats' => $stats,
            'rows' => $rows,
            'context' => [
                'annee_precedente' => $anneePrecedente?->name,
                'annee_courante' => $anneeCourante?->name,
            ],
        ];
    }

    /**
     * Exécute une batch de réinscriptions en transaction atomique.
     *
     * $items = array<int, [
     *   'etudiant_id' => int,
     *   'decision' => 'passage'|'rattrapage'|'redoublement',
     *   'classe_id' => int (cible),
     *   'affectation_status' => 'affecté'|'non-affecté'|'réaffecté',
     *   'observations' => ?string,
     * ]>
     *
     * @return array{success: bool, success_count: int, errors: array, batch_id: string}
     */
    public function executeBulk(array $items, ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        $batchId = (string) \Illuminate\Support\Str::uuid();
        $successItems = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($items as $idx => $item) {
                if (empty($item['etudiant_id']) || empty($item['classe_id']) || empty($item['decision'])) {
                    $errors[] = [
                        'index' => $idx,
                        'etudiant_id' => $item['etudiant_id'] ?? null,
                        'message' => 'Payload incomplet (etudiant_id, classe_id ou decision manquant)',
                    ];
                    continue;
                }

                try {
                    $inscription = $this->reeinscriptionService->effectuerReinscription(
                        $item['etudiant_id'],
                        $item['classe_id'],
                        $item['decision'],
                        $item['observations'] ?? null,
                        $item['selected_optionals'] ?? [],
                        $item['affectation_status'] ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
                        null, // anneeUniversitaireId
                        $item['action_reliquat'] ?? null,
                        true,  // skipTransaction — la batch gère la transaction globale
                        false  // sendNotification — queued à la fin de la batch
                    );
                    $successItems[] = [
                        'etudiant_id' => $item['etudiant_id'],
                        'inscription_id' => $inscription->id,
                        'decision' => $item['decision'],
                    ];
                } catch (\Throwable $e) {
                    $errors[] = [
                        'index' => $idx,
                        'etudiant_id' => $item['etudiant_id'],
                        'message' => $e->getMessage(),
                    ];
                }
            }

            // Strategy : si ANY échec → rollback global. Permet à l'opérateur de corriger
            // puis relancer. Évite état partiel impossible à débugger.
            if (!empty($errors)) {
                DB::rollBack();
                Log::warning('BulkReinscription: rollback due to errors', [
                    'batch_id' => $batchId,
                    'errors_count' => count($errors),
                    'success_attempts' => count($successItems),
                ]);
                return [
                    'success' => false,
                    'success_count' => 0,
                    'errors' => $errors,
                    'batch_id' => $batchId,
                    'message' => 'Aucune réinscription effectuée — corrigez les erreurs et relancez',
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('BulkReinscription: critical error', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'success_count' => 0,
                'errors' => [['message' => 'Erreur critique : ' . $e->getMessage()]],
                'batch_id' => $batchId,
            ];
        }

        // Queue notifications APRÈS commit pour éviter d'envoyer si rollback
        $this->queueNotifications($successItems, $batchId);

        Log::info('BulkReinscription: success', [
            'batch_id' => $batchId,
            'user_id' => $userId,
            'success_count' => count($successItems),
            'decisions_breakdown' => collect($successItems)->groupBy('decision')->map->count()->all(),
        ]);

        return [
            'success' => true,
            'success_count' => count($successItems),
            'errors' => [],
            'batch_id' => $batchId,
            'message' => count($successItems) . ' réinscription(s) effectuée(s)',
        ];
    }

    /**
     * Dispatch les notifications mail aux parents en queue (rate-limited).
     */
    private function queueNotifications(array $successItems, string $batchId): void
    {
        foreach ($successItems as $item) {
            try {
                \App\Jobs\SendReinscriptionMailJob::dispatch(
                    $item['inscription_id'],
                    $item['decision'],
                    $batchId
                )->onQueue('notifications');
            } catch (\Throwable $e) {
                Log::warning('BulkReinscription: notification dispatch failed', [
                    'batch_id' => $batchId,
                    'inscription_id' => $item['inscription_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
