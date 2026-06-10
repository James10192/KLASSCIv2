<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Services\ClassStudentService;
use App\Services\ESBTPInscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CLIStudentController extends BaseApiController
{
    protected ESBTPInscriptionService $inscriptionService;
    protected ClassStudentService $classStudentService;

    public function __construct(ESBTPInscriptionService $inscriptionService, ClassStudentService $classStudentService)
    {
        parent::__construct();
        $this->inscriptionService = $inscriptionService;
        $this->classStudentService = $classStudentService;
    }

    /**
     * GET /api/cli/students — List students
     */
    public function students(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $annee = $this->getAnneeCouraante();
        if (!$annee) {
            return $this->errorResponse('Aucune annee universitaire courante configuree. Creez-en une avec: klassci annee:create <tenant> "2025-2026" 2025-09-15 2026-07-31', ['code' => 'NO_ACADEMIC_YEAR'], 422);
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = ESBTPEtudiant::query()
            ->select('esbtp_etudiants.id', 'esbtp_etudiants.matricule', 'esbtp_etudiants.nom', 'esbtp_etudiants.prenoms', 'esbtp_etudiants.email', 'esbtp_etudiants.telephone', 'esbtp_etudiants.statut')
            ->whereHas('inscriptions', function ($q) use ($annee, $request) {
                $q->where('annee_universitaire_id', $annee->id)
                  ->where('status', 'active')
                  ->where('workflow_step', 'etudiant_cree');

                if ($request->filled('class_id')) {
                    $q->where('classe_id', $request->input('class_id'));
                }
            })
            ->with(['inscriptions' => function ($q) use ($annee) {
                $q->where('annee_universitaire_id', $annee->id)
                  ->where('status', 'active')
                  ->where('workflow_step', 'etudiant_cree')
                  ->with('classe:id,name');
            }]);

        // Search filter — tokenized for multi-word queries (e.g. "KADJO ME ARIELLE DIVINE")
        if ($request->filled('search')) {
            $search = $request->input('search');
            $searchTokens = collect(preg_split('/[\s,]+/u', $search, -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($token) => trim($token))
                ->filter();

            $query->where(function ($q) use ($search, $searchTokens) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenoms', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT_WS(' ', nom, prenoms) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT_WS(' ', prenoms, nom) LIKE ?", ["%{$search}%"]);

                if ($searchTokens->count() > 1) {
                    $q->orWhere(function ($subQuery) use ($searchTokens) {
                        foreach ($searchTokens as $token) {
                            $subQuery->where(function ($inner) use ($token) {
                                $inner->where('nom', 'like', "%{$token}%")
                                      ->orWhere('prenoms', 'like', "%{$token}%")
                                      ->orWhere('matricule', 'like', "%{$token}%");
                            });
                        }
                    });
                }
            });
        }

        $paginated = $query->orderBy('nom')->paginate($perPage);

        // Enrich with classe name from active inscription (already eager-loaded)
        $students = collect($paginated->items())->map(function ($etudiant) use ($annee) {
            $inscription = $etudiant->inscriptions->first();

            return [
                'id' => $etudiant->id,
                'matricule' => $etudiant->matricule,
                'nom' => $etudiant->nom,
                'prenoms' => $etudiant->prenoms,
                'email' => $etudiant->email,
                'telephone' => $etudiant->telephone,
                'classe' => $inscription && $inscription->classe ? $inscription->classe->name : null,
                'classe_id' => $inscription ? $inscription->classe_id : null,
            ];
        });

        return $this->successResponse([
            'students' => $students,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/cli/students/{id} — Student detail
     */
    public function studentShow(Request $request, $id): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $etudiant = ESBTPEtudiant::find($id);
        if (!$etudiant) {
            return $this->errorResponse('Student not found', [], 404);
        }

        $annee = $this->getAnneeCouraante();

        // Current inscription
        // FIX audit 2026-06-04 : colonnes filiere/niveau sont 'name' (anglais),
        // pas 'nom' (français). Causait SQL error 'Unknown column nom'.
        $inscription = $annee
            ? $etudiant->inscriptions()
                ->where('annee_universitaire_id', $annee->id)
                ->with(['classe:id,name', 'filiere:id,name', 'niveau:id,name'])
                ->first()
            : null;

        // Recent payments
        $paiements = $etudiant->paiements()
            ->orderBy('date_paiement', 'desc')
            ->limit(10)
            ->get(['id', 'montant', 'status', 'mode_paiement', 'date_paiement', 'motif']);

        // Recent notes
        $notes = $annee
            ? ESBTPNote::where('etudiant_id', $etudiant->id)
                ->whereHas('evaluation', function ($q) use ($annee) {
                    $q->where('annee_universitaire_id', $annee->id);
                })
                ->with('evaluation:id,titre,type')
                ->with('matiere:id,name')
                ->limit(20)
                ->get(['id', 'matiere_id', 'evaluation_id', 'note', 'is_absent'])
            : collect();

        return $this->successResponse([
            'id' => $etudiant->id,
            'matricule' => $etudiant->matricule,
            'nom' => $etudiant->nom,
            'prenoms' => $etudiant->prenoms,
            'email' => $etudiant->email,
            'telephone' => $etudiant->telephone,
            'date_naissance' => $etudiant->date_naissance?->format('Y-m-d'),
            'sexe' => $etudiant->sexe,
            'statut' => $etudiant->statut,
            'inscription' => $inscription ? [
                'id' => $inscription->id,
                'status' => $inscription->status,
                'workflow_step' => $inscription->workflow_step,
                'classe' => $inscription->classe?->name,
                'filiere' => $inscription->filiere?->name,
                'niveau' => $inscription->niveau?->name,
                'date_inscription' => $inscription->date_inscription?->format('Y-m-d'),
            ] : null,
            'paiements' => $paiements->map(fn ($p) => [
                'id' => $p->id,
                'montant' => $p->montant,
                'status' => $p->status,
                'mode' => $p->mode_paiement,
                'date' => $p->date_paiement?->format('Y-m-d'),
                'motif' => $p->motif,
            ]),
            'notes' => $notes->map(fn ($n) => [
                'id' => $n->id,
                'matiere' => $n->matiere?->name,
                'evaluation' => $n->evaluation?->titre,
                'type' => $n->evaluation?->type,
                'note' => $n->note,
                'is_absent' => $n->is_absent,
            ]),
        ]);
    }

    /**
     * GET /api/cli/inscriptions — List inscriptions
     */
    public function inscriptions(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $annee = $this->getAnneeCouraante();
        if (!$annee) {
            return $this->errorResponse('Aucune annee universitaire courante configuree. Creez-en une avec: klassci annee:create <tenant> "2025-2026" 2025-09-15 2026-07-31', ['code' => 'NO_ACADEMIC_YEAR'], 422);
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = ESBTPInscription::where('annee_universitaire_id', $annee->id)
            ->with([
                'etudiant:id,matricule,nom,prenoms',
                'classe:id,name',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('class_id')) {
            $query->where('classe_id', $request->input('class_id'));
        }

        if ($request->filled('workflow_step')) {
            $query->where('workflow_step', $request->input('workflow_step'));
        }

        $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $inscriptions = collect($paginated->items())->map(fn ($i) => [
            'id' => $i->id,
            'etudiant_id' => $i->etudiant_id,
            'matricule' => $i->etudiant?->matricule,
            'nom_complet' => trim(($i->etudiant?->nom ?? '') . ' ' . ($i->etudiant?->prenoms ?? '')),
            'classe' => $i->classe?->name,
            'classe_id' => $i->classe_id,
            'status' => $i->status,
            'workflow_step' => $i->workflow_step,
            'date_inscription' => $i->date_inscription?->format('Y-m-d'),
            'date_validation' => $i->date_validation?->format('Y-m-d'),
        ]);

        return $this->successResponse([
            'inscriptions' => $inscriptions,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/cli/inscriptions/{id}/validate — Validate inscription
     */
    public function validateInscription(Request $request, $id): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:write')) {
            return $this->errorResponse('Token missing cli:write ability', [], 403);
        }

        $inscription = ESBTPInscription::find($id);
        if (!$inscription) {
            return $this->errorResponse('Inscription not found', [], 404);
        }

        // Already fully validated
        if ($inscription->status === 'active' && $inscription->workflow_step === 'etudiant_cree') {
            return $this->errorResponse('Inscription already validated', [], 422);
        }

        // Check payment — NEVER auto-validate if payment is en_attente
        $hasValidPayment = $inscription->paiements()
            ->where('status', 'validé')
            ->exists();

        if (!$hasValidPayment) {
            $pendingPayment = $inscription->paiements()
                ->where('status', 'en_attente')
                ->exists();

            $reason = $pendingPayment
                ? 'Cannot validate: payment is still pending (en_attente)'
                : 'Cannot validate: no payment found for this inscription';

            return $this->errorResponse($reason, [], 422);
        }

        try {
            $result = $this->inscriptionService->validerInscription(
                $inscription->id,
                $request->user()->id
            );

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 422);
            }

            return $this->successResponse([
                'inscription_id' => $inscription->id,
                'status' => 'active',
                'workflow_step' => 'etudiant_cree',
            ], 'Inscription validated successfully');
        } catch (\Exception $e) {
            Log::error('CLI: inscription validation failed', [
                'inscription_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Operation failed. Check server logs for details.', [], 500);
        }
    }

    /**
     * POST /api/cli/inscriptions/move — Move students to correct class
     */
    public function moveStudents(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:write')) {
            return $this->errorResponse('Token missing cli:write ability', [], 403);
        }

        $moves = $request->input('moves', []);
        if (empty($moves)) {
            return $this->errorResponse('No moves provided. Expected: {moves: [{etudiant_id, from_classe_id, to_classe_id}, ...]}', [], 422);
        }

        $annee = $this->getAnneeCouraante();
        if (!$annee) {
            return $this->errorResponse('No active academic year', [], 422);
        }

        $moved = [];
        $warnings = [];
        $skipped = [];
        $errors = [];

        foreach ($moves as $move) {
            $etudiantId = $move['etudiant_id'] ?? null;
            $fromClasseId = $move['from_classe_id'] ?? null;
            $toClasseId = $move['to_classe_id'] ?? null;

            if (!$etudiantId || !$fromClasseId || !$toClasseId) {
                $errors[] = ['etudiant_id' => $etudiantId, 'reason' => 'missing_fields'];
                continue;
            }

            // Pre-check: inscription exists in source class
            $inscription = ESBTPInscription::where('etudiant_id', $etudiantId)
                ->where('annee_universitaire_id', $annee->id)
                ->where('classe_id', $fromClasseId)
                ->first();

            if (!$inscription) {
                $errors[] = ['etudiant_id' => $etudiantId, 'reason' => 'no_inscription_in_source_class', 'from' => $fromClasseId];
                continue;
            }

            if ($inscription->status !== 'active') {
                $errors[] = ['etudiant_id' => $etudiantId, 'reason' => 'inscription_not_active', 'status' => $inscription->status];
                continue;
            }

            // Pre-check: no existing inscription in target class
            $existsInTarget = ESBTPInscription::where('etudiant_id', $etudiantId)
                ->where('annee_universitaire_id', $annee->id)
                ->where('classe_id', $toClasseId)
                ->exists();

            if ($existsInTarget) {
                $skipped[] = ['etudiant_id' => $etudiantId, 'reason' => 'already_in_target_class', 'to' => $toClasseId];
                continue;
            }

            // Check for existing data (notes/resultats/bulletins) in source class
            $fromClasse = ESBTPClasse::find($fromClasseId);
            $toClasse = ESBTPClasse::find($toClasseId);

            if (!$fromClasse || !$toClasse) {
                $errors[] = ['etudiant_id' => $etudiantId, 'reason' => 'classe_not_found'];
                continue;
            }

            $dataCheck = $this->classStudentService->checkStudentData($fromClasse, [$etudiantId]);
            $hasData = $dataCheck['has_any_data'] ?? false;

            if ($hasData) {
                $studentData = $dataCheck['students'][0] ?? [];
                $warnings[] = [
                    'etudiant_id' => $etudiantId,
                    'nom' => $studentData['nom'] ?? '',
                    'notes' => $studentData['notes_count'] ?? 0,
                    'resultats' => $studentData['resultats_count'] ?? 0,
                    'bulletins' => $studentData['bulletins_count'] ?? 0,
                    'message' => 'Student has data in source class — will be archived',
                ];
            }

            // Execute move: call addStudents with single student (per-student transaction)
            try {
                $result = $this->classStudentService->addStudents($toClasse, [$etudiantId]);

                if ($result['added'] > 0) {
                    $etudiant = ESBTPEtudiant::find($etudiantId);
                    $moved[] = [
                        'etudiant_id' => $etudiantId,
                        'nom' => $etudiant ? trim($etudiant->nom . ' ' . $etudiant->prenoms) : '',
                        'from' => $fromClasse->name,
                        'to' => $toClasse->name,
                        'had_data' => $hasData,
                    ];
                } else {
                    $errors[] = [
                        'etudiant_id' => $etudiantId,
                        'reason' => 'service_error',
                        'details' => $result['errors'],
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = ['etudiant_id' => $etudiantId, 'reason' => 'exception', 'message' => $e->getMessage()];
            }
        }

        Log::info('CLI: moveStudents completed', [
            'moved' => count($moved),
            'warnings' => count($warnings),
            'skipped' => count($skipped),
            'errors' => count($errors),
            'user_id' => $request->user()->id,
        ]);

        return $this->successResponse([
            'moved' => $moved,
            'warnings' => $warnings,
            'skipped' => $skipped,
            'errors' => $errors,
            'summary' => [
                'moved' => count($moved),
                'warnings' => count($warnings),
                'skipped' => count($skipped),
                'errors' => count($errors),
            ],
        ], count($moved) . ' student(s) moved');
    }

    /**
     * POST /api/cli/inscriptions/validate-bulk — Bulk validate inscriptions
     */
    public function bulkValidate(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:write')) {
            return $this->errorResponse('Token missing cli:write ability', [], 403);
        }

        $annee = $this->getAnneeCouraante();
        if (!$annee) {
            return $this->errorResponse('No active academic year', [], 422);
        }

        // Get inscriptions to validate: by IDs or by class
        $inscriptionIds = $request->input('inscription_ids', []);

        if (empty($inscriptionIds) && $request->filled('classe_id')) {
            $inscriptionIds = ESBTPInscription::where('annee_universitaire_id', $annee->id)
                ->where('classe_id', $request->input('classe_id'))
                ->where(function ($q) {
                    $q->where('status', '!=', 'active')
                      ->orWhere('workflow_step', '!=', 'etudiant_cree');
                })
                ->pluck('id')
                ->toArray();
        }

        if (empty($inscriptionIds)) {
            return $this->successResponse(['validated' => [], 'skipped' => [], 'errors' => []], 'No inscriptions to validate');
        }

        $validated = [];
        $skipped = [];
        $errors = [];
        $userId = $request->user()->id;

        foreach ($inscriptionIds as $inscriptionId) {
            $inscription = ESBTPInscription::with('etudiant:id,nom,prenoms')->find($inscriptionId);

            if (!$inscription) {
                $errors[] = ['id' => $inscriptionId, 'reason' => 'not_found'];
                continue;
            }

            // Already validated
            if ($inscription->status === 'active' && $inscription->workflow_step === 'etudiant_cree') {
                $skipped[] = [
                    'id' => $inscriptionId,
                    'nom' => trim(($inscription->etudiant?->nom ?? '') . ' ' . ($inscription->etudiant?->prenoms ?? '')),
                    'reason' => 'already_validated',
                ];
                continue;
            }

            // Check payment
            $hasValidPayment = $inscription->paiements()->where('status', 'validé')->exists();
            if (!$hasValidPayment) {
                $hasPending = $inscription->paiements()->where('status', 'en_attente')->exists();
                $skipped[] = [
                    'id' => $inscriptionId,
                    'nom' => trim(($inscription->etudiant?->nom ?? '') . ' ' . ($inscription->etudiant?->prenoms ?? '')),
                    'reason' => $hasPending ? 'paiement_en_attente' : 'sans_paiement',
                ];
                continue;
            }

            try {
                $result = $this->inscriptionService->validerInscription($inscriptionId, $userId);

                if ($result['success']) {
                    $validated[] = [
                        'id' => $inscriptionId,
                        'nom' => trim(($inscription->etudiant?->nom ?? '') . ' ' . ($inscription->etudiant?->prenoms ?? '')),
                    ];
                } else {
                    $errors[] = ['id' => $inscriptionId, 'reason' => 'service_error', 'message' => $result['message']];
                }
            } catch (\Exception $e) {
                $errors[] = ['id' => $inscriptionId, 'reason' => 'exception', 'message' => $e->getMessage()];
            }
        }

        Log::info('CLI: bulkValidate completed', [
            'validated' => count($validated),
            'skipped' => count($skipped),
            'errors' => count($errors),
            'user_id' => $userId,
        ]);

        return $this->successResponse([
            'validated' => $validated,
            'skipped' => $skipped,
            'errors' => $errors,
            'summary' => [
                'validated' => count($validated),
                'skipped' => count($skipped),
                'errors' => count($errors),
            ],
        ], count($validated) . ' inscription(s) validated');
    }
}
