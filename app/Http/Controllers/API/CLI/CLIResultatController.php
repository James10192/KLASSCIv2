<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\BulletinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CLIResultatController extends BaseApiController
{
    public function __construct(private BulletinService $bulletinService)
    {
        parent::__construct();
    }

    /**
     * GET /api/cli/resultats/etudiant/{id}/diagnose
     */
    public function studentDiagnose(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $etudiant = ESBTPEtudiant::find($id);
        if (! $etudiant) {
            return $this->errorResponse('Student not found', [], 404);
        }

        $requestedAnneeId = $request->filled('annee_universitaire_id')
            ? (int) $request->input('annee_universitaire_id')
            : $this->getAnneeCouraante()?->id;
        $requestedClasseId = $request->filled('classe_id')
            ? (int) $request->input('classe_id')
            : null;
        $includeAllStatuses = $request->boolean('include_all_statuses', true);
        $requestedPeriode = $this->normalizePeriode($request->input('periode'));

        $annee = $requestedAnneeId ? ESBTPAnneeUniversitaire::find($requestedAnneeId) : null;

        $inscriptionsQuery = $etudiant->inscriptions()
            ->with(['classe.filiere:id,name', 'classe.niveau:id,name'])
            ->when($requestedAnneeId, fn ($q) => $q->where('annee_universitaire_id', $requestedAnneeId));

        $currentControllerInscriptionQuery = $etudiant->inscriptions()
            ->when($requestedAnneeId, fn ($q) => $q->where('annee_universitaire_id', $requestedAnneeId));
        if (! $includeAllStatuses) {
            $currentControllerInscriptionQuery->where('status', 'active');
        }

        $currentControllerInscription = $currentControllerInscriptionQuery->first();
        $requestedClasseInscription = $requestedClasseId
            ? (clone $inscriptionsQuery)->where('classe_id', $requestedClasseId)->first()
            : null;
        $inscriptions = (clone $inscriptionsQuery)
            ->orderByDesc('date_inscription')
            ->orderByDesc('id')
            ->get();

        $currentControllerClasseId = $currentControllerInscription?->classe_id ?: $requestedClasseId;
        $expectedClasseId = $requestedClasseId ?: $currentControllerInscription?->classe_id;
        $effectiveDetailPeriode = $requestedPeriode ?: 'semestre1';

        $notes = ESBTPNote::query()
            ->where('etudiant_id', $etudiant->id)
            ->when($requestedAnneeId, function ($query) use ($requestedAnneeId) {
                $query->whereHas('evaluation', fn ($q) => $q->where('annee_universitaire_id', $requestedAnneeId));
            })
            ->with(['evaluation:id,classe_id,periode,annee_universitaire_id,matiere_id,bareme,coefficient', 'evaluation.classe:id,name'])
            ->get();

        $resultats = ESBTPResultat::query()
            ->where('etudiant_id', $etudiant->id)
            ->when($requestedAnneeId, fn ($query) => $query->where('annee_universitaire_id', $requestedAnneeId))
            ->with(['classe:id,name', 'matiere:id,name'])
            ->orderBy('classe_id')
            ->orderBy('periode')
            ->get();

        $bulletins = ESBTPBulletin::query()
            ->where('etudiant_id', $etudiant->id)
            ->when($requestedAnneeId, fn ($query) => $query->where('annee_universitaire_id', $requestedAnneeId))
            ->with(['classe:id,name'])
            ->orderBy('classe_id')
            ->orderBy('periode')
            ->get();

        $weights = $this->bulletinService->getSemesterWeights();

        $requestedClassAverages = $this->buildClassAverageSnapshot($etudiant->id, $requestedClasseId, $requestedAnneeId, $weights);
        $currentControllerClassAverages = $this->buildClassAverageSnapshot($etudiant->id, $currentControllerClasseId, $requestedAnneeId, $weights);
        $expectedClassAverages = $this->buildClassAverageSnapshot($etudiant->id, $expectedClasseId, $requestedAnneeId, $weights);

        $warnings = [];
        if (! $requestedPeriode) {
            $warnings[] = 'La page détail BTS force actuellement semestre1 quand aucun paramètre periode/semestre n’est fourni.';
        }
        if ($requestedClasseId && $currentControllerClasseId && $requestedClasseId !== $currentControllerClasseId) {
            $warnings[] = 'Le contrôleur résultat étudiant résout actuellement la classe depuis la première inscription avant de prioriser la classe demandée dans l’URL.';
        }
        if ($requestedClassAverages['annual_list_logic'] !== null && $requestedClassAverages['semestre2_effective'] === null) {
            $warnings[] = 'La logique annuelle liste a déjà fait un fallback vers le seul semestre disponible.';
        }

        return $this->successResponse([
            'student' => [
                'id' => $etudiant->id,
                'matricule' => $etudiant->matricule,
                'nom_complet' => trim($etudiant->nom . ' ' . $etudiant->prenoms),
            ],
            'requested_context' => [
                'classe_id' => $requestedClasseId,
                'annee_universitaire_id' => $requestedAnneeId,
                'annee_label' => $this->formatAnneeLabel($annee),
                'periode' => $requestedPeriode,
                'include_all_statuses' => $includeAllStatuses,
            ],
            'controller_context' => [
                'detail_default_periode_when_missing' => 'semestre1',
                'detail_effective_periode' => $effectiveDetailPeriode,
                'current_behavior_classe_id' => $currentControllerClasseId,
                'current_behavior_classe' => $this->formatClasseSummary($currentControllerClasseId),
                'expected_if_request_wins_classe_id' => $expectedClasseId,
                'expected_if_request_wins_classe' => $this->formatClasseSummary($expectedClasseId),
            ],
            'inscriptions' => $inscriptions->map(function (ESBTPInscription $inscription) {
                return [
                    'id' => $inscription->id,
                    'classe_id' => $inscription->classe_id,
                    'classe' => $inscription->classe?->name,
                    'filiere' => $inscription->classe?->filiere?->name,
                    'niveau' => $inscription->classe?->niveau?->name,
                    'status' => $inscription->status,
                    'workflow_step' => $inscription->workflow_step,
                    'date_inscription' => optional($inscription->date_inscription)->format('Y-m-d'),
                    'created_at' => optional($inscription->created_at)->format('Y-m-d H:i:s'),
                ];
            })->values(),
            'selected_inscriptions' => [
                'current_controller_behavior' => $currentControllerInscription ? [
                    'id' => $currentControllerInscription->id,
                    'classe_id' => $currentControllerInscription->classe_id,
                    'status' => $currentControllerInscription->status,
                    'workflow_step' => $currentControllerInscription->workflow_step,
                ] : null,
                'matching_requested_class' => $requestedClasseInscription ? [
                    'id' => $requestedClasseInscription->id,
                    'classe_id' => $requestedClasseInscription->classe_id,
                    'status' => $requestedClasseInscription->status,
                    'workflow_step' => $requestedClasseInscription->workflow_step,
                ] : null,
            ],
            'notes_summary' => [
                'total' => $notes->count(),
                'entries' => $notes->map(function (ESBTPNote $note) {
                    $bareme = $note->evaluation?->bareme ?: 20;
                    $value = is_numeric($note->note) ? (float) $note->note : (is_numeric($note->valeur) ? (float) $note->valeur : null);
                    $normalized = ($value !== null && $bareme > 0) ? round(($value / $bareme) * 20, 2) : null;

                    return [
                        'id' => $note->id,
                        'classe_id' => $note->evaluation?->classe_id,
                        'classe' => $note->evaluation?->classe?->name,
                        'evaluation_periode' => $note->evaluation?->periode,
                        'semestre' => $note->semestre,
                        'matiere_id' => $note->matiere_id ?: $note->evaluation?->matiere_id,
                        'note' => $note->note,
                        'valeur' => $note->valeur,
                        'bareme' => $bareme,
                        'coefficient_evaluation' => $note->evaluation?->coefficient,
                        'normalized_on_20' => $normalized,
                    ];
                })->values(),
                'by_class_and_periode' => $notes
                    ->groupBy(fn ($note) => ($note->evaluation?->classe_id ?: 'none') . '|' . ($note->evaluation?->periode ?: $note->semestre ?: 'none'))
                    ->map(function ($group) {
                        $first = $group->first();

                        return [
                            'classe_id' => $first?->evaluation?->classe_id,
                            'classe' => $first?->evaluation?->classe?->name,
                            'evaluation_periode' => $first?->evaluation?->periode,
                            'note_semestre_values' => $group->pluck('semestre')->filter()->unique()->values(),
                            'count' => $group->count(),
                            'matiere_ids' => $group->map(fn ($note) => $note->matiere_id ?: $note->evaluation?->matiere_id)->filter()->unique()->values(),
                        ];
                    })
                    ->values(),
            ],
            'resultats_summary' => [
                'total' => $resultats->count(),
                'by_class_and_periode' => $resultats
                    ->groupBy(fn ($resultat) => ($resultat->classe_id ?: 'none') . '|' . ($resultat->periode ?: 'none'))
                    ->map(function ($group) {
                        $first = $group->first();

                        return [
                            'classe_id' => $first?->classe_id,
                            'classe' => $first?->classe?->name,
                            'periode' => $first?->periode,
                            'count' => $group->count(),
                            'matieres' => $group->map(fn ($resultat) => [
                                'matiere_id' => $resultat->matiere_id,
                                'matiere' => $resultat->matiere?->name,
                                'moyenne' => $resultat->moyenne,
                                'coefficient' => $resultat->coefficient,
                            ])->values(),
                        ];
                    })
                    ->values(),
            ],
            'bulletins_summary' => [
                'total' => $bulletins->count(),
                'entries' => $bulletins->map(function (ESBTPBulletin $bulletin) {
                    return [
                        'id' => $bulletin->id,
                        'classe_id' => $bulletin->classe_id,
                        'classe' => $bulletin->classe?->name,
                        'periode' => $bulletin->periode,
                        'moyenne_generale' => $bulletin->moyenne_generale,
                        'note_assiduite' => $bulletin->note_assiduite,
                        'effective_total' => $bulletin->moyenne_generale !== null
                            ? round($bulletin->moyenne_generale + ($bulletin->note_assiduite ?? 0), 2)
                            : null,
                        'rang' => $bulletin->rang,
                        'effectif_classe' => $bulletin->effectif_classe,
                    ];
                })->values(),
            ],
            'averages' => [
                'semester_weights' => $weights,
                'requested_class' => $requestedClassAverages,
                'current_controller_class' => $currentControllerClassAverages,
                'expected_if_request_wins' => $expectedClassAverages,
            ],
            'warnings' => $warnings,
        ], 'Student results diagnostic generated');
    }

    private function normalizePeriode(?string $periode): ?string
    {
        if ($periode === null || $periode === '') {
            return null;
        }

        return match ($periode) {
            '1' => 'semestre1',
            '2' => 'semestre2',
            default => $periode,
        };
    }

    private function buildClassAverageSnapshot(int $etudiantId, ?int $classeId, ?int $anneeId, array $weights): array
    {
        if (! $classeId || ! $anneeId) {
            return [
                'classe_id' => $classeId,
                'classe' => $this->formatClasseSummary($classeId),
                'semestre1_effective' => null,
                'semestre2_effective' => null,
                'annual_weighted' => null,
                'annual_list_logic' => null,
            ];
        }

        $s1 = $this->bulletinService->getBulletinAverageForPeriode(
            $etudiantId,
            $classeId,
            $anneeId,
            'semestre1',
            '__none__',
            0,
            0
        );
        $s2 = $this->bulletinService->getBulletinAverageForPeriode(
            $etudiantId,
            $classeId,
            $anneeId,
            'semestre2',
            '__none__',
            0,
            0
        );
        $annualWeighted = $this->bulletinService->calculateAnnualAverage($s1, $s2, $weights);

        return [
            'classe_id' => $classeId,
            'classe' => $this->formatClasseSummary($classeId),
            'semestre1_effective' => $s1 !== null ? round($s1, 2) : null,
            'semestre2_effective' => $s2 !== null ? round($s2, 2) : null,
            'annual_weighted' => $annualWeighted !== null ? round($annualWeighted, 2) : null,
            'annual_list_logic' => $annualWeighted !== null
                ? round($annualWeighted, 2)
                : ($s1 !== null ? round($s1, 2) : ($s2 !== null ? round($s2, 2) : null)),
        ];
    }

    private function formatClasseSummary(?int $classeId): ?array
    {
        if (! $classeId) {
            return null;
        }

        $classe = ESBTPClasse::with(['filiere:id,name', 'niveau:id,name'])->find($classeId);
        if (! $classe) {
            return null;
        }

        return [
            'id' => $classe->id,
            'name' => $classe->name,
            'code' => $classe->code,
            'filiere' => $classe->filiere?->name,
            'niveau' => $classe->niveau?->name,
        ];
    }

    private function formatAnneeLabel(?ESBTPAnneeUniversitaire $annee): ?string
    {
        if (! $annee) {
            return null;
        }

        return $annee->name
            ?? $annee->nom
            ?? ((isset($annee->annee_debut, $annee->annee_fin)) ? "{$annee->annee_debut}-{$annee->annee_fin}" : null);
    }
}
