<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CLIDataController extends BaseApiController
{
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

        $classes = ESBTPClasse::withCount(['inscriptions as effectif' => function ($q) use ($annee) {
                $q->where('annee_universitaire_id', $annee->id)
                  ->where('status', 'active')
                  ->where('workflow_step', 'etudiant_cree');
            }])
            ->whereHas('inscriptions', function ($q) use ($annee) {
                $q->where('annee_universitaire_id', $annee->id)
                  ->where('status', 'active')
                  ->where('workflow_step', 'etudiant_cree');
            })
            ->with(['filiere:id,name', 'niveau:id,name'])
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'code' => $c->code,
                    'filiere' => $c->filiere?->name,
                    'niveau' => $c->niveau?->name,
                    'systeme' => $c->systeme_academique,
                    'capacite' => $c->places_totales,
                    'effectif' => $c->effectif,
                    'places_disponibles' => max(0, ($c->places_totales ?? 0) - $c->effectif),
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
            Log::error('CLI: setting update failed', ['key' => $key, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Operation failed. Check server logs for details.', [], 500);
        }
    }
}
