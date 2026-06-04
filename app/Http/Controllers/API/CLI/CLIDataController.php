<?php

namespace App\Http\Controllers\API\CLI;

use App\Actions\Comptabilite\GetImpayesAgingAction;
use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\Predictors\DefaultRiskPredictor;
use App\DTOs\Comptabilite\ComptabiliteFilters;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPRelance;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Models\Audit;

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
     * GET /api/cli/relances — Summary KPIs + latest rows for reminder workflows
     */
    public function relances(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $annee = $this->getAnneeCouraante();
        if (!$annee) {
            return $this->errorResponse('Aucune annee universitaire courante configuree.', ['code' => 'NO_ACADEMIC_YEAR'], 422);
        }

        $filters = new ComptabiliteFilters($annee->id, null, null);
        $aging = app(GetImpayesAgingAction::class);
        $agingBuckets = $aging($filters);
        $totalImpaye = (float) array_sum(array_column($agingBuckets, 'amount'));
        $etudiantsEnRetard = (int) array_sum(array_column($agingBuckets, 'count'));
        $pendingValidation = (float) ESBTPPaiement::query()
            ->where('annee_universitaire_id', $annee->id)
            ->where('status', 'en_attente')
            ->whereNull('deleted_at')
            ->sum('montant');

        $recentRelances = ESBTPRelance::query()
            ->with(['etudiant:id,nom,prenoms,matricule'])
            ->orderByDesc('created_at')
            ->limit(min((int) $request->input('limit', 10), 25))
            ->get()
            ->map(fn (ESBTPRelance $relance) => [
                'id' => $relance->id,
                'statut' => $relance->statut,
                'niveau' => $relance->niveau,
                'canal' => $relance->canal,
                'type' => $relance->type,
                'date_envoi' => $relance->date_envoi?->toISOString(),
                'etudiant' => trim(($relance->etudiant?->nom ?? '') . ' ' . ($relance->etudiant?->prenoms ?? '')),
                'matricule' => $relance->etudiant?->matricule,
            ]);

        return $this->successResponse([
            'kpis' => [
                'total_impaye' => $totalImpaye,
                'currency' => 'FCFA',
                'etudiants_en_retard' => $etudiantsEnRetard,
                'en_attente_validation' => $pendingValidation,
                'bucket_counts' => collect($agingBuckets)->map(fn (array $bucket) => $bucket['count'])->all(),
            ],
            'rows' => $recentRelances,
        ], 'Relances summary');
    }

    /**
     * GET /api/cli/recouvrement — Summary KPIs + top at-risk rows
     */
    public function recouvrement(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $annee = $this->getAnneeCouraante();
        if (!$annee) {
            return $this->errorResponse('Aucune annee universitaire courante configuree.', ['code' => 'NO_ACADEMIC_YEAR'], 422);
        }

        try {
            /** @var DefaultRiskPredictor $predictor */
            $predictor = app(DefaultRiskPredictor::class);
            $prediction = $predictor->predict(new AnalyticsContext(
                anneeId: $annee->id,
                filiereId: null,
                classeId: null,
                etudiantId: null,
            ));
            $metadata = $prediction->metadata ?? [];
        } catch (\Throwable $e) {
            Log::error('CLI: recouvrement summary failed', ['error' => $e->getMessage()]);

            return $this->errorResponse('Operation failed. Check server logs for details.', [], 500);
        }

        $rows = collect($metadata['top_at_risk'] ?? [])
            ->take(min((int) $request->input('limit', 10), 25))
            ->values()
            ->all();

        return $this->successResponse([
            'kpis' => [
                'total_actifs' => $metadata['total_actifs'] ?? 0,
                'buckets' => $metadata['buckets'] ?? [],
                'taux_risque_pct' => $metadata['taux_risque_pct'] ?? 0.0,
                'total_solde_haut_risque' => $metadata['total_solde_haut_risque'] ?? 0.0,
                'thresholds' => $metadata['thresholds'] ?? [],
                'auto_calibrated' => $metadata['auto_calibrated'] ?? false,
            ],
            'rows' => $rows,
        ], 'Recouvrement summary');
    }

    /**
     * GET /api/cli/journal-caisse — Totals + sample rows for OHADA cash journal
     */
    public function journalCaisse(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $dateDebut = $request->filled('date_debut')
            ? Carbon::parse($request->input('date_debut'))->startOfDay()
            : Carbon::now()->startOfMonth();
        $dateFin = $request->filled('date_fin')
            ? Carbon::parse($request->input('date_fin'))->endOfDay()
            : Carbon::now()->endOfDay();
        if ($dateDebut->gt($dateFin)) {
            [$dateDebut, $dateFin] = [$dateFin->copy()->startOfDay(), $dateDebut->copy()->endOfDay()];
        }

        $query = ESBTPPaiement::query()
            ->with([
                'inscription.etudiant:id,nom,prenoms,matricule',
                'inscription.classe:id,name,filiere_id',
                'fraisCategory:id,name',
                'createdBy:id,name',
                'validatedBy:id,name',
            ])
            ->whereNull('deleted_at')
            ->whereDate('date_paiement', '>=', $dateDebut->format('Y-m-d'))
            ->whereDate('date_paiement', '<=', $dateFin->format('Y-m-d'));

        if ($request->filled('statut')) {
            $query->where('status', $request->input('statut'));
        } else {
            $query->where('status', 'validé');
        }

        if ($request->filled('mode_paiement')) {
            $query->where('mode_paiement', $request->input('mode_paiement'));
        }

        if ($request->filled('classe_id')) {
            $query->whereHas('inscription', fn ($q) => $q->where('classe_id', $request->integer('classe_id')));
        } elseif ($request->filled('filiere_id')) {
            $query->whereHas('inscription.classe', fn ($q) => $q->where('filiere_id', $request->integer('filiere_id')));
        }

        $rows = (clone $query)
            ->orderBy('date_paiement')
            ->orderBy('id')
            ->limit(min((int) $request->input('limit', 20), 50))
            ->get()
            ->map(fn (ESBTPPaiement $paiement) => [
                'id' => $paiement->id,
                'date_paiement' => $paiement->date_paiement?->format('Y-m-d'),
                'reference' => $paiement->numero_recu ?: $paiement->reference_paiement,
                'etudiant' => trim(($paiement->inscription?->etudiant?->nom ?? '') . ' ' . ($paiement->inscription?->etudiant?->prenoms ?? '')),
                'matricule' => $paiement->inscription?->etudiant?->matricule,
                'categorie' => $paiement->fraisCategory?->name ?? $paiement->motif,
                'mode_paiement' => $paiement->mode_paiement,
                'montant' => (float) $paiement->montant,
                'encaisse_par' => $paiement->createdBy?->name,
                'valide_par' => $paiement->validatedBy?->name,
                'status' => $paiement->status,
            ]);

        $totalsByMode = (clone $query)
            ->selectRaw('mode_paiement, COUNT(*) as nb, COALESCE(SUM(montant), 0) as total')
            ->groupBy('mode_paiement')
            ->get()
            ->map(fn ($row) => [
                'mode_paiement' => $row->mode_paiement,
                'count' => (int) $row->nb,
                'total' => (float) $row->total,
            ])
            ->values();

        return $this->successResponse([
            'filters' => [
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin->format('Y-m-d'),
                'statut' => $request->input('statut', 'validé'),
                'mode_paiement' => $request->input('mode_paiement'),
                'filiere_id' => $request->input('filiere_id'),
                'classe_id' => $request->input('classe_id'),
            ],
            'totals' => [
                'count' => (clone $query)->count(),
                'total' => (float) (clone $query)->sum('montant'),
                'currency' => 'FCFA',
                'by_mode' => $totalsByMode,
            ],
            'rows' => $rows,
        ], 'Journal caisse summary');
    }

    /**
     * GET /api/cli/audit-comptable — Financial audit KPIs + recent audit rows
     */
    public function auditComptable(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $financialModels = [
            'App\Models\ESBTPPaiement',
            'App\Models\ESBTPDepense',
            'App\Models\ESBTPFacture',
            'App\Models\ESBTPFactureDetail',
            'App\Models\ESBTPFraisScolarite',
            'App\Models\ESBTPSalaire',
            'App\Models\ESBTPBourse',
        ];

        $query = Audit::query()
            ->with('user:id,name')
            ->whereIn('auditable_type', $financialModels)
            ->orderByDesc('created_at');

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        $since = Carbon::now()->subDays(30);
        $weekStart = Carbon::now()->startOfWeek();

        return $this->successResponse([
            'kpis' => [
                'paiements_modifies_30j' => Audit::where('auditable_type', 'App\Models\ESBTPPaiement')
                    ->where('event', 'updated')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'factures_modifiees_30j' => Audit::where('auditable_type', 'App\Models\ESBTPFacture')
                    ->where('event', 'updated')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'annulations_semaine' => Audit::whereIn('auditable_type', $financialModels)
                    ->where('event', 'deleted')
                    ->where('created_at', '>=', $weekStart)
                    ->count(),
                'validations_semaine' => Audit::whereIn('auditable_type', $financialModels)
                    ->where('event', 'created')
                    ->where('created_at', '>=', $weekStart)
                    ->count(),
            ],
            'rows' => $query
                ->limit(min((int) $request->input('limit', 15), 50))
                ->get()
                ->map(fn (Audit $audit) => [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'auditable_type' => class_basename((string) $audit->auditable_type),
                    'auditable_id' => $audit->auditable_id,
                    'user' => $audit->user?->name ?? 'Système',
                    'created_at' => $audit->created_at?->toISOString(),
                ]),
        ], 'Audit comptable summary');
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

        // Upsert : créer la ligne si elle n'existe pas (utile pour provisionner de
        // nouveaux settings via CLI sans avoir à faire un git deploy + UI visit
        // d'abord). Le type est inféré du request param ?type=float|int|bool|string
        // (default 'string') pour le firstOrCreate.
        $setting = Setting::where('key', $key)->first();
        $created = false;
        if (!$setting) {
            $setting = Setting::create([
                'key' => $key,
                'value' => $request->input('value'),
                'type' => $request->input('type', 'string'),
                'group' => $request->input('group', 'general'),
                'description' => $request->input('description', "CLI-provisioned: {$key}"),
                'is_required' => false,
            ]);
            $created = true;
        }

        try {
            $previousValue = $setting->value;
            if (!$created) {
                Setting::set($key, $request->input('value'), $request->user()->id);
            }

            return $this->successResponse([
                'key' => $key,
                'value' => $request->input('value'),
                'previous_value' => $previousValue,
                'created' => $created,
            ], $created
                ? "Setting '{$key}' created with value"
                : "Setting '{$key}' updated successfully"
            );
        } catch (\Exception $e) {
            Log::error('CLI: setting update failed', ['key' => $key, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Operation failed. Check server logs for details.', [], 500);
        }
    }

    /**
     * GET /api/cli/analytics/diagnose — Diagnostic complet du sous-système Analytics
     * (couverture règles, snapshots, distribution mensuelle, saturation risque).
     */
    public function analyticsDiagnose(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        try {
            // Réutilise la commande artisan en mode JSON — pas de duplication de logique.
            $exit = \Artisan::call('analytics:diagnose', ['--json' => true]);
            $output = trim(\Artisan::output());
            $report = json_decode($output, true);

            if ($exit !== 0 || !is_array($report)) {
                return $this->errorResponse('Diagnose command failed', ['exit_code' => $exit, 'raw_output' => $output], 500);
            }

            return $this->successResponse($report, 'Analytics diagnostic generated');
        } catch (\Throwable $e) {
            Log::error('CLI: analytics diagnose failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Operation failed. Check server logs for details.', [], 500);
        }
    }
}
