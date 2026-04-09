<?php

namespace App\Http\Controllers\API;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPClasse;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPNote;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\Setting;
use App\Models\User;
use App\Services\ESBTPInscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CLIController extends BaseApiController
{
    protected ESBTPInscriptionService $inscriptionService;

    public function __construct(ESBTPInscriptionService $inscriptionService)
    {
        parent::__construct();
        $this->inscriptionService = $inscriptionService;
    }

    // =========================================================================
    // READ ENDPOINTS (cli:read ability)
    // =========================================================================

    /**
     * GET /api/cli/stats — Dashboard KPIs
     */
    public function stats(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $annee = $this->getAnneeCouraante();
        if (!$annee) {
            return $this->errorResponse('Aucune annee universitaire courante configuree. Creez-en une avec: klassci annee:create <tenant> "2025-2026" 2025-09-15 2026-07-31', ['code' => 'NO_ACADEMIC_YEAR'], 422);
        }

        $activeStudents = ESBTPInscription::where('annee_universitaire_id', $annee->id)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->count();

        $totalClasses = ESBTPClasse::whereHas('inscriptions', function ($q) use ($annee) {
                $q->where('annee_universitaire_id', $annee->id)
                  ->where('status', 'active')
                  ->where('workflow_step', 'etudiant_cree');
            })->count();

        $pendingInscriptions = ESBTPInscription::where('annee_universitaire_id', $annee->id)
            ->where(function ($q) {
                $q->where('status', 'en_attente')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'active')
                         ->where('workflow_step', '!=', 'etudiant_cree');
                  });
            })
            ->count();

        $totalRevenue = ESBTPPaiement::where('annee_universitaire_id', $annee->id)
            ->where('status', 'validé')
            ->sum('montant');

        $totalPayments = ESBTPPaiement::where('annee_universitaire_id', $annee->id)->count();
        $validatedPayments = ESBTPPaiement::where('annee_universitaire_id', $annee->id)
            ->where('status', 'validé')
            ->count();
        $pendingPayments = ESBTPPaiement::where('annee_universitaire_id', $annee->id)
            ->where('status', 'en_attente')
            ->count();

        return $this->successResponse([
            'active_students' => $activeStudents,
            'total_classes' => $totalClasses,
            'pending_inscriptions' => $pendingInscriptions,
            'revenue' => [
                'total' => $totalRevenue,
                'currency' => 'FCFA',
            ],
            'payments' => [
                'total' => $totalPayments,
                'validated' => $validatedPayments,
                'pending' => $pendingPayments,
            ],
            'annee_universitaire' => $annee->name ?? $annee->libelle ?? "{$annee->annee_debut}-{$annee->annee_fin}",
            'annee_universitaire_id' => $annee->id,
        ], 'Dashboard KPIs');
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
            });

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

        // Enrich with classe name from active inscription
        $students = collect($paginated->items())->map(function ($etudiant) use ($annee) {
            $inscription = $etudiant->inscriptions()
                ->where('annee_universitaire_id', $annee->id)
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree')
                ->with('classe:id,name')
                ->first();

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
     * GET /api/cli/classes — List classes with available places
     */
    public function classes(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $annee = $this->getAnneeCouraante();
        if (!$annee) {
            return $this->errorResponse('Aucune annee universitaire courante configuree. Creez-en une avec: klassci annee:create <tenant> "2025-2026" 2025-09-15 2026-07-31', ['code' => 'NO_ACADEMIC_YEAR'], 422);
        }

        $classes = ESBTPClasse::whereHas('inscriptions', function ($q) use ($annee) {
                $q->where('annee_universitaire_id', $annee->id)
                  ->where('status', 'active')
                  ->where('workflow_step', 'etudiant_cree');
            })
            ->with(['filiere:id,name', 'niveau:id,name'])
            ->get()
            ->map(function ($c) use ($annee) {
                $effectif = ESBTPInscription::where('classe_id', $c->id)
                    ->where('annee_universitaire_id', $annee->id)
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree')
                    ->count();

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'code' => $c->code,
                    'filiere' => $c->filiere?->name,
                    'niveau' => $c->niveau?->name,
                    'systeme' => $c->systeme_academique,
                    'capacite' => $c->places_totales,
                    'effectif' => $effectif,
                    'places_disponibles' => max(0, ($c->places_totales ?? 0) - $effectif),
                ];
            });

        return $this->successResponse([
            'classes' => $classes,
            'total' => $classes->count(),
        ]);
    }

    /**
     * GET /api/cli/payments — List payments
     */
    public function payments(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $annee = $this->getAnneeCouraante();
        if (!$annee) {
            return $this->errorResponse('Aucune annee universitaire courante configuree. Creez-en une avec: klassci annee:create <tenant> "2025-2026" 2025-09-15 2026-07-31', ['code' => 'NO_ACADEMIC_YEAR'], 422);
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = ESBTPPaiement::where('annee_universitaire_id', $annee->id)
            ->with(['inscription:id,etudiant_id', 'inscription.etudiant:id,matricule,nom,prenoms']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('mode')) {
            $query->where('mode_paiement', $request->input('mode'));
        }

        $paginated = $query->orderBy('date_paiement', 'desc')->paginate($perPage);

        $payments = collect($paginated->items())->map(fn ($p) => [
            'id' => $p->id,
            'montant' => $p->montant,
            'status' => $p->status,
            'mode_paiement' => $p->mode_paiement,
            'motif' => $p->motif,
            'date_paiement' => $p->date_paiement?->format('Y-m-d'),
            'reference' => $p->reference_paiement,
            'etudiant' => $p->inscription?->etudiant
                ? trim($p->inscription->etudiant->nom . ' ' . $p->inscription->etudiant->prenoms)
                : null,
            'matricule' => $p->inscription?->etudiant?->matricule,
        ]);

        return $this->successResponse([
            'payments' => $payments,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/cli/settings — Read tenant settings
     */
    public function settings(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $settings = Setting::where('is_active', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get(['key', 'value', 'type', 'group', 'description'])
            ->groupBy('group')
            ->map(fn ($group) => $group->mapWithKeys(fn ($s) => [
                $s->key => [
                    'value' => Setting::get($s->key, $s->value),
                    'type' => $s->type,
                    'description' => $s->description,
                ],
            ]));

        return $this->successResponse(['settings' => $settings]);
    }

    /**
     * GET /api/cli/annee — Current academic year + list all
     */
    public function annee(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $current = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $all = ESBTPAnneeUniversitaire::orderBy('id', 'desc')->get();

        return $this->successResponse([
            'current' => $current ? [
                'id' => $current->id,
                'name' => $current->name ?? $current->libelle,
                'annee_debut' => $current->annee_debut,
                'annee_fin' => $current->annee_fin,
                'start_date' => $current->start_date?->format('Y-m-d'),
                'end_date' => $current->end_date?->format('Y-m-d'),
                'is_current' => true,
            ] : null,
            'all' => $all->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name ?? $a->libelle,
                'annee_debut' => $a->annee_debut,
                'annee_fin' => $a->annee_fin,
                'start_date' => $a->start_date?->format('Y-m-d'),
                'end_date' => $a->end_date?->format('Y-m-d'),
                'is_current' => (bool) $a->is_current,
                'is_active' => (bool) $a->is_active,
            ]),
        ]);
    }

    /**
     * POST /api/cli/annee/set/{id} — Set current academic year
     */
    public function anneeSet(Request $request, $id): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $annee = ESBTPAnneeUniversitaire::find($id);
        if (!$annee) {
            return $this->errorResponse("Academic year #{$id} not found", [], 404);
        }

        // Unset all current
        ESBTPAnneeUniversitaire::where('is_current', true)->update(['is_current' => false]);

        // Set the new one
        $annee->update(['is_current' => true]);

        return $this->successResponse([
            'id' => $annee->id,
            'name' => $annee->name ?? $annee->libelle,
            'is_current' => true,
        ], "Academic year '{$annee->name}' is now current");
    }

    /**
     * POST /api/cli/annee/create — Create a new academic year
     */
    public function anneeCreate(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'set_current' => 'nullable|boolean',
        ]);

        try {
            $setCurrent = $validated['set_current'] ?? true;

            // Si set_current, retirer le flag des autres
            if ($setCurrent) {
                ESBTPAnneeUniversitaire::where('is_current', true)->update(['is_current' => false]);
            }

            $annee = ESBTPAnneeUniversitaire::create([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_current' => $setCurrent,
                'is_active' => true,
            ]);

            return $this->successResponse([
                'id' => $annee->id,
                'name' => $annee->name,
                'start_date' => $annee->start_date->format('Y-m-d'),
                'end_date' => $annee->end_date->format('Y-m-d'),
                'is_current' => (bool) $annee->is_current,
            ], "Academic year '{$annee->name}' created" . ($setCurrent ? ' and set as current' : ''));
        } catch (\Exception $e) {
            Log::error('CLI: annee creation failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create academic year: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * POST /api/cli/user/{id}/reset-password-expiry — Mark password as just changed
     */
    public function userResetPasswordExpiry(Request $request, $id): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse("User #{$id} not found", [], 404);
        }

        $user->password_changed_at = now();
        $user->must_change_password = false;
        $user->save();

        return $this->successResponse([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password_changed_at' => $user->password_changed_at->toIso8601String(),
            'must_change_password' => false,
        ], "Password expiry reset for {$user->name}");
    }

    /**
     * POST /api/cli/user/create — Create a user with a role
     */
    public function userCreate(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'username' => 'required|string|max:100|unique:users,username',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'phone' => 'nullable|string|max:20',
        ]);

        $validRoles = ['superAdmin', 'admin', 'secretaire', 'coordinateur', 'enseignant',
                        'etudiant', 'parent', 'comptable', 'caissier', 'teacher'];

        if (!in_array($validated['role'], $validRoles)) {
            return $this->errorResponse("Invalid role '{$validated['role']}'. Valid: " . implode(', ', $validRoles), [], 422);
        }

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'username' => $validated['username'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
                'must_change_password' => true,
                'created_by' => $request->user()->id,
            ]);

            $user->assignRole($validated['role']);

            return $this->successResponse([
                'user_id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $validated['role'],
            ], "User '{$user->name}' created with role '{$validated['role']}'");
        } catch (\Exception $e) {
            Log::error('CLI: user creation failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('User creation failed: ' . $e->getMessage(), [], 500);
        }
    }

    // =========================================================================
    // WRITE ENDPOINTS (cli:write ability)
    // =========================================================================

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
            ]);
            return $this->errorResponse('Validation failed: ' . $e->getMessage(), [], 500);
        }
    }

    // =========================================================================
    // ADMIN ENDPOINTS (cli:admin ability)
    // =========================================================================

    /**
     * POST /api/cli/cache/clear — Clear all caches
     */
    public function cacheClear(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $output = [];

        try {
            Artisan::call('config:clear');
            $output[] = 'config:clear OK';

            Artisan::call('cache:clear');
            $output[] = 'cache:clear OK';

            Artisan::call('view:clear');
            $output[] = 'view:clear OK';

            Artisan::call('permission:cache-reset');
            $output[] = 'permission:cache-reset OK';

            // Also clear settings cache
            Setting::clearCache();
            $output[] = 'settings cache cleared';
        } catch (\Exception $e) {
            Log::error('CLI: cache clear failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Cache clear partially failed: ' . $e->getMessage(), ['completed' => $output], 500);
        }

        return $this->successResponse([
            'commands' => $output,
        ], 'All caches cleared successfully');
    }

    /**
     * POST /api/cli/permissions/fix — Sync all permissions and roles
     */
    public function permissionsFix(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        try {
            // Reset permission cache
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            // All permissions from the canonical list
            $permissions = [
                'view_dashboard', 'access_admin',
                'view_students', 'create_students', 'edit_students', 'delete_students', 'view_own_students',
                'view_inscriptions', 'create_inscriptions', 'edit_inscriptions', 'approve_inscriptions', 'reject_inscriptions',
                'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.delete', 'inscriptions.validate',
                'edit inscriptions', 'valider inscriptions', 'annuler inscriptions', 'delete inscriptions',
                'paiements.view', 'paiements.create', 'paiements.edit', 'paiements.delete', 'paiements.validate',
                'frais.view', 'frais.create', 'frais.edit', 'frais.delete', 'frais.configure',
                'security.audit.view', 'security.audit.export', 'comptabilite.audit.view', 'security.users.monitor',
                'generate-attendance-codes',
                'manage-planning', 'view-all-timetables', 'view_timetables', 'create_timetable', 'edit_timetables', 'delete_timetables', 'view_own_timetable',
                'view cycles', 'create cycles', 'edit cycles', 'delete cycles', 'restore cycles', 'force delete cycles',
                'view_classes', 'create_classes', 'edit_classes', 'delete_classes',
                'view_filieres', 'create_filieres', 'edit_filieres',
                'view_niveaux_etudes', 'create_niveaux_etudes', 'edit_niveaux_etudes', 'delete_niveaux_etudes',
                'view_matieres', 'create_matieres', 'edit_matieres', 'delete_matieres',
                'view_notes', 'create_notes', 'edit_notes', 'edit_existing_notes', 'view_own_notes', 'manage_own_notes',
                'view_grades', 'view_own_grades', 'create_grade', 'edit_grades', 'delete_grades',
                'view_evaluations', 'view_own_exams', 'create_evaluations', 'edit_evaluations',
                'view_bulletins', 'generate_bulletins', 'edit_bulletins', 'view_own_bulletin',
                'view_attendances', 'create_attendance', 'create_attendances', 'edit_attendances', 'delete_attendances',
                'view_own_attendances', 'sign_attendance', 'view_own_attendance',
                'view_payments', 'create_payments', 'edit_payments', 'view_comptabilite', 'manage_comptabilite',
                'view_teachers', 'create_teachers', 'edit_teachers', 'view_personnel', 'manage_personnel', 'view_own_profile',
                'view_coordinateurs', 'create_coordinateurs', 'edit_coordinateurs', 'delete_coordinateurs',
                'view_schedules', 'create_schedules', 'edit_schedules', 'view_own_schedule',
                'send_messages', 'receive_messages', 'view_annonces', 'create_annonces', 'edit_annonces',
                'view_reports', 'generate_reports',
                'view_settings', 'edit_settings', 'manage_system',
                'view_planning_general', 'edit_planning_general', 'view_resultats', 'edit_resultats',
                'module.enseignants.access', 'module.notes_evaluations.access', 'module.emploi_temps.access',
                'module.presences.access', 'module.lmd.access', 'module.academique.access',
                'module.etudiants.access', 'module.comptabilite.access', 'module.communication.access',
                'manage-users', 'edit_enseignants', 'edit_bulletins',
                'paywall.configure', 'paywall.manage_subscriptions', 'paywall.extend_subscriptions', 'paywall.view_all_stats',
                'system.technical_access', 'system.emergency_override',
                'comptabilite.access', 'comptabilite.dashboard.view', 'comptabilite.relances.send',
                'comptabilite.reports.export', 'comptabilite.config.manage',
                'comptabilite.paiements.view', 'comptabilite.paiements.validate',
                'comptabilite.frais.view', 'comptabilite.frais.configure',
            ];

            $createdPermissions = 0;
            foreach ($permissions as $permName) {
                Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                $createdPermissions++;
            }

            // Roles
            $roles = [
                'superAdmin', 'admin', 'secretaire', 'coordinateur',
                'enseignant', 'etudiant', 'parent', 'serviceTechnique',
                'teacher', 'comptable', 'caissier',
            ];

            $createdRoles = 0;
            foreach ($roles as $roleName) {
                Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                $createdRoles++;
            }

            // Sync superAdmin with all permissions
            $superAdminRole = Role::findByName('superAdmin');
            $superAdminRole->syncPermissions($permissions);

            // Sync admin with all permissions
            $adminRole = Role::findByName('admin');
            $adminRole->syncPermissions($permissions);

            // Reset cache after sync
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            return $this->successResponse([
                'permissions_synced' => $createdPermissions,
                'roles_synced' => $createdRoles,
                'superadmin_permissions' => count($permissions),
            ], 'Permissions and roles synced successfully');
        } catch (\Exception $e) {
            Log::error('CLI: permissions fix failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Permissions fix failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * PUT /api/cli/settings/{key} — Update a setting
     */
    public function settingsUpdate(Request $request, $key): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        if (!$request->has('value')) {
            return $this->errorResponse('Missing required field: value', [], 422);
        }

        $setting = Setting::where('key', $key)->first();
        if (!$setting) {
            return $this->errorResponse("Setting '{$key}' not found", [], 404);
        }

        try {
            Setting::set($key, $request->input('value'), $request->user()->id);

            return $this->successResponse([
                'key' => $key,
                'value' => $request->input('value'),
                'previous_value' => $setting->value,
            ], "Setting '{$key}' updated successfully");
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update setting: ' . $e->getMessage(), [], 500);
        }
    }
}
