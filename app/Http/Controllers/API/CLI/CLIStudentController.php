<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Services\ESBTPInscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CLIStudentController extends BaseApiController
{
    protected ESBTPInscriptionService $inscriptionService;

    public function __construct(ESBTPInscriptionService $inscriptionService)
    {
        parent::__construct();
        $this->inscriptionService = $inscriptionService;
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

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenoms', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
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
        $inscription = $annee
            ? $etudiant->inscriptions()
                ->where('annee_universitaire_id', $annee->id)
                ->with(['classe:id,name', 'filiere:id,nom', 'niveau:id,nom'])
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
                ->with('matiere:id,nom')
                ->limit(20)
                ->get(['id', 'matiere_id', 'evaluation_id', 'note', 'note_rattrapage', 'is_absent'])
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
                'filiere' => $inscription->filiere?->nom,
                'niveau' => $inscription->niveau?->nom,
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
                'matiere' => $n->matiere?->nom,
                'evaluation' => $n->evaluation?->titre,
                'type' => $n->evaluation?->type,
                'note' => $n->note,
                'note_rattrapage' => $n->note_rattrapage,
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
}
