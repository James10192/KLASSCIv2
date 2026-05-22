<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use App\Models\User;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPDepense;
use App\Models\ESBTPFacture;
use App\Services\Audit\AuditEntityResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\AuditExport;

class ESBTPAuditController extends Controller
{
    /**
     * Constructeur avec middleware de permissions
     */
    public function __construct(private readonly AuditEntityResolver $entityResolver = new AuditEntityResolver())
    {
        $this->middleware('auth');
        $this->middleware('permission:security.audit.view')->only(['index', 'show', 'getAuditData', 'relatedLinks']);
        $this->middleware('permission:security.audit.export')->only(['export', 'exportPdf', 'exportExcel']);
        $this->middleware('permission:comptabilite.audit.view')->only(['comptabiliteAudits']);
        $this->middleware('permission:security.users.monitor')->only(['userActivity']);
    }

    /**
     * Afficher la page principale d'audit
     */
    public function index(Request $request)
    {
        // Statistiques générales d'audit
        $stats = $this->getAuditStats();

        // Modèles auditables
        $auditableModels = $this->getAuditableModels();

        // Utilisateurs pour les filtres
        $users = User::select('id', 'name', 'email', 'username')->with('roles:id,name')->get();

        return view('esbtp.audit.index', compact('stats', 'auditableModels', 'users'));
    }

    /**
     * Obtenir les données d'audit via AJAX
     */
    public function getAuditData(Request $request)
    {
        $query = Audit::with(['user'])->orderBy('created_at', 'desc');
        $this->applyCommonFilters($query, $request);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('auditable_id', 'like', "%{$search}%")
                  ->orWhere('old_values', 'like', "%{$search}%")
                  ->orWhere('new_values', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Pagination
        $audits = $query->paginate(50);

        // Formatage des données pour l'affichage. On envoie `event_raw` (slug
        // brut Eloquent : created/updated/...) en plus de `event` (label FR)
        // pour que le frontend puisse choisir la classe CSS sans reverse-map.
        $audits->getCollection()->transform(function ($audit) {
            return [
                'id' => $audit->id,
                'event' => $this->formatEvent($audit->event),
                'event_raw' => $audit->event,
                'auditable_type' => $this->formatModelType($audit->auditable_type),
                'auditable_id' => $audit->auditable_id,
                'user' => $audit->user ? $audit->user->name : 'Système',
                'ip_address' => $audit->ip_address,
                'user_agent' => $this->formatUserAgent($audit->user_agent),
                'created_at' => $audit->created_at->format('d/m/Y H:i:s'),
                'changes' => $this->formatChanges($audit),
                'risk_level' => $this->calculateRiskLevel($audit),
            ];
        });

        return response()->json($audits);
    }

    /**
     * Afficher les détails d'un audit spécifique
     */
    public function show($id)
    {
        $audit = Audit::with(['user'])->findOrFail($id);

        // Vérifier les permissions pour l'accès aux données sensibles
        if ($this->isSensitiveData($audit) && !auth()->user()->can('comptabilite.sensitive.access')) {
            abort(403, 'Accès aux données sensibles non autorisé');
        }

        // Audits liés (même entité, antérieurs à celui-ci)
        $relatedAudits = Audit::with('user')
            ->where('auditable_type', $audit->auditable_type)
            ->where('auditable_id', $audit->auditable_id)
            ->where('id', '!=', $audit->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Calcul niveau de risque pour la vue
        $riskLevel = $this->calculateRiskLevel($audit);

        // URL vers l'entité auditée si possible
        $entityUrl = $this->resolveEntityUrl($audit);

        // Diff field-by-field
        $changes = $this->formatChanges($audit);

        // Liens vers les entités liées (étudiant, inscription, catégorie de frais, …)
        $entityLinks = $this->entityResolver->resolve($audit);

        return view('esbtp.audit.show', compact('audit', 'relatedAudits', 'riskLevel', 'entityUrl', 'changes', 'entityLinks'));
    }

    /**
     * Endpoint AJAX — retourne les liens entités liées pour un audit donné.
     * Utilisé par le modal "Aperçu rapide" de /esbtp/audit/index pour ne pas
     * résoudre les relations en bulk sur 50 lignes paginées (coût N+1 fort).
     */
    public function relatedLinks($id)
    {
        $audit = Audit::findOrFail($id);

        if ($this->isSensitiveData($audit) && !auth()->user()->can('comptabilite.sensitive.access')) {
            abort(403, 'Accès aux données sensibles non autorisé');
        }

        return response()->json([
            'audit_id' => $audit->id,
            'links' => $this->entityResolver->resolve($audit),
        ]);
    }

    /**
     * Audits spécifiques à la comptabilité
     */
    public function comptabiliteAudits(Request $request)
    {
        $this->authorize('comptabilite.audit.view');

        $financialModels = [
            'App\Models\ESBTPPaiement',
            'App\Models\ESBTPDepense',
            'App\Models\ESBTPFacture',
            'App\Models\ESBTPFactureDetail',
            'App\Models\ESBTPFraisScolarite',
            'App\Models\ESBTPSalaire',
            'App\Models\ESBTPBourse',
        ];

        $query = Audit::whereIn('auditable_type', $financialModels)
            ->with(['user'])
            ->orderBy('created_at', 'desc');

        // Filtres spécifiques comptabilité
        if ($request->filled('montant_min')) {
            $query->where(function ($q) use ($request) {
                $q->where('old_values', 'like', '%"montant":' . $request->montant_min . '%')
                  ->orWhere('new_values', 'like', '%"montant":' . $request->montant_min . '%');
            });
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('model_type')) {
            $query->where('auditable_type', $request->model_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $audits = $query->paginate(25)->withQueryString();

        // KPIs financiers (sur 30 derniers jours)
        $since = Carbon::now()->subDays(30);
        $weekStart = Carbon::now()->startOfWeek();
        $kpis = [
            'paiements_modifies' => Audit::where('auditable_type', 'App\Models\ESBTPPaiement')
                ->where('event', 'updated')
                ->where('created_at', '>=', $since)
                ->count(),
            'factures_modifiees' => Audit::where('auditable_type', 'App\Models\ESBTPFacture')
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
        ];

        $financialModelsLabels = [
            'App\Models\ESBTPPaiement' => 'Paiements',
            'App\Models\ESBTPDepense' => 'Dépenses',
            'App\Models\ESBTPFacture' => 'Factures',
            'App\Models\ESBTPFactureDetail' => 'Détails Factures',
            'App\Models\ESBTPFraisScolarite' => 'Frais Scolarité',
            'App\Models\ESBTPSalaire' => 'Salaires',
            'App\Models\ESBTPBourse' => 'Bourses',
        ];

        // Précalcule les liens entités liées pour chaque audit de la page courante.
        // Le resolver eager-load les relations par audit ; sur 25 lignes c'est
        // acceptable et évite de bombarder le serveur depuis le client.
        $entityLinksMap = [];
        foreach ($audits as $a) {
            $entityLinksMap[$a->id] = $this->entityResolver->resolve($a);
        }

        return view('esbtp.audit.comptabilite', compact('audits', 'kpis', 'financialModelsLabels', 'entityLinksMap'));
    }

    /**
     * Surveiller l'activité des utilisateurs
     */
    public function userActivity(Request $request)
    {
        $this->authorize('security.users.monitor');

        $userId = $request->get('user_id');
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->get('date_from'))
            : now()->subDays(30);
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->get('date_to'))->endOfDay()
            : now();

        $baseQuery = fn () => Audit::whereBetween('created_at', [$dateFrom, $dateTo]);

        $query = Audit::with(['user'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $activities = $query->paginate(50)->withQueryString();

        // Top modèles touchés (sur la fenêtre, scoped par user si filtré)
        $topModelsQuery = $baseQuery();
        if ($userId) {
            $topModelsQuery->where('user_id', $userId);
        }
        $topModels = $topModelsQuery
            ->select('auditable_type', DB::raw('COUNT(*) as total'))
            ->groupBy('auditable_type')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'label' => $this->formatModelType($r->auditable_type),
                'count' => $r->total,
            ]);

        // Top IPs
        $topIpsQuery = $baseQuery();
        if ($userId) {
            $topIpsQuery->where('user_id', $userId);
        }
        $topIps = $topIpsQuery
            ->select('ip_address', DB::raw('COUNT(*) as total'))
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Heures de pointe (24 buckets)
        $peakHoursQuery = $baseQuery();
        if ($userId) {
            $peakHoursQuery->where('user_id', $userId);
        }
        $hoursRaw = $peakHoursQuery
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->pluck('total', 'hour')
            ->toArray();
        $hourlyDistribution = [];
        for ($h = 0; $h < 24; $h++) {
            $hourlyDistribution[$h] = (int) ($hoursRaw[$h] ?? 0);
        }
        $peakHour = array_search(max($hourlyDistribution), $hourlyDistribution);

        // Statistiques d'activité (recompute totals after applying user filter if any)
        $totalActionsQuery = $baseQuery();
        if ($userId) {
            $totalActionsQuery->where('user_id', $userId);
        }
        $stats = [
            'total_actions' => $totalActionsQuery->count(),
            'unique_users' => $baseQuery()->distinct('user_id')->count('user_id'),
            'unique_ips' => $baseQuery()->whereNotNull('ip_address')->distinct('ip_address')->count('ip_address'),
            'peak_hour' => $peakHour !== false ? sprintf('%02dh', $peakHour) : '—',
            'suspicious_activities' => $this->getSuspiciousActivities($dateFrom, $dateTo),
        ];

        $users = User::select('id', 'name', 'email', 'username')->with('roles:id,name')->orderBy('name')->get();
        $selectedUser = $userId ? User::find($userId) : null;

        // Précalcule les liens entités liées pour chaque audit de la page (50 max).
        $entityLinksMap = [];
        foreach ($activities as $a) {
            $entityLinksMap[$a->id] = $this->entityResolver->resolve($a);
        }

        return view('esbtp.audit.user-activity', compact(
            'activities',
            'stats',
            'users',
            'selectedUser',
            'topModels',
            'topIps',
            'hourlyDistribution',
            'dateFrom',
            'dateTo',
            'entityLinksMap'
        ));
    }

    /**
     * Exporter les audits en Excel
     */
    public function exportExcel(Request $request)
    {
        $this->authorize('security.audit.export');

        $audits = $this->getFilteredAudits($request);

        return Excel::download(new AuditExport($audits), 'audit_trail_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
    }

    /**
     * Exporter les audits en PDF
     */
    public function exportPdf(Request $request)
    {
        $this->authorize('security.audit.export');

        $audits = $this->getFilteredAudits($request)->take(100); // Limiter pour PDF

        $pdf = Pdf::loadView('esbtp.audit.export-pdf', compact('audits'));

        return $pdf->download('audit_trail_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * Obtenir les statistiques d'audit
     */
    private function getAuditStats()
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'total_audits' => Audit::count(),
            'today_audits' => Audit::whereDate('created_at', $today)->count(),
            'week_audits' => Audit::where('created_at', '>=', $thisWeek)->count(),
            'month_audits' => Audit::where('created_at', '>=', $thisMonth)->count(),
            'financial_audits' => Audit::whereIn('auditable_type', [
                'App\Models\ESBTPPaiement',
                'App\Models\ESBTPDepense',
                'App\Models\ESBTPFacture'
            ])->count(),
            'critical_events' => Audit::whereIn('event', ['deleted', 'restored'])->count(),
            'unique_users' => Audit::distinct('user_id')->count('user_id'),
        ];
    }

    /**
     * Obtenir les modèles auditables
     */
    private function getAuditableModels()
    {
        return [
            'App\Models\ESBTPPaiement' => 'Paiements',
            'App\Models\ESBTPDepense' => 'Dépenses',
            'App\Models\ESBTPFacture' => 'Factures',
            'App\Models\ESBTPFactureDetail' => 'Détails Factures',
            'App\Models\ESBTPFraisScolarite' => 'Frais Scolarité',
            'App\Models\ESBTPSalaire' => 'Salaires',
            'App\Models\ESBTPBourse' => 'Bourses',
            'App\Models\User' => 'Utilisateurs',
        ];
    }

    /**
     * Formater le type d'événement
     */
    private function formatEvent($event)
    {
        $events = [
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            'restored' => 'Restauration',
            'retrieved' => 'Consultation',
        ];

        return $events[$event] ?? ucfirst($event);
    }

    /**
     * Formater le type de modèle
     */
    private function formatModelType($type)
    {
        $models = $this->getAuditableModels();
        return $models[$type] ?? class_basename($type);
    }

    /**
     * Formater les changements
     */
    private function formatChanges($audit)
    {
        $oldValues = $this->normalizeAuditValues($audit->old_values);
        $newValues = $this->normalizeAuditValues($audit->new_values);

        $changes = [];

        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;

            if ($oldValue !== $newValue) {
                $changes[] = [
                    'field' => $this->formatFieldName($field),
                    'old' => $this->formatValue($oldValue),
                    'new' => $this->formatValue($newValue),
                ];
            }
        }

        return $changes;
    }

    /**
     * Normalise old_values / new_values d'un audit en array.
     * Le model OwenIt\Auditing\Models\Audit cast déjà ces colonnes via Eloquent,
     * donc selon le contexte on reçoit un array (Eloquent hydrate) ou un string
     * (lecture brute via builder/raw query).
     */
    private function normalizeAuditValues($value): array
    {
        if (empty($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Calculer le niveau de risque
     */
    private function calculateRiskLevel($audit)
    {
        $riskFactors = 0;

        // Événements critiques
        if (in_array($audit->event, ['deleted', 'restored'])) {
            $riskFactors += 3;
        }

        // Modèles financiers
        if (in_array($audit->auditable_type, [
            'App\Models\ESBTPPaiement',
            'App\Models\ESBTPDepense',
            'App\Models\ESBTPFacture'
        ])) {
            $riskFactors += 2;
        }

        // Modifications en dehors des heures de bureau
        $hour = $audit->created_at->hour;
        if ($hour < 8 || $hour > 18) {
            $riskFactors += 1;
        }

        // Accès depuis IP externe
        if (!$this->isInternalIP($audit->ip_address)) {
            $riskFactors += 1;
        }

        if ($riskFactors >= 4) return 'Critique';
        if ($riskFactors >= 2) return 'Élevé';
        if ($riskFactors >= 1) return 'Moyen';
        return 'Faible';
    }

    /**
     * Vérifier si les données sont sensibles
     */
    private function isSensitiveData($audit)
    {
        $sensitiveModels = [
            'App\Models\ESBTPPaiement',
            'App\Models\ESBTPDepense',
            'App\Models\ESBTPFacture',
            'App\Models\ESBTPSalaire',
        ];

        return in_array($audit->auditable_type, $sensitiveModels);
    }

    /**
     * Formater le nom de champ
     */
    private function formatFieldName($field)
    {
        $fieldNames = [
            'montant' => 'Montant',
            'statut' => 'Statut',
            'reference' => 'Référence',
            'date_paiement' => 'Date Paiement',
            'validateur_id' => 'Validateur',
            'created_at' => 'Date Création',
            'updated_at' => 'Date Modification',
        ];

        return $fieldNames[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Formater une valeur pour l'affichage
     */
    private function formatValue($value)
    {
        if (is_null($value)) {
            return 'N/A';
        }

        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        if (is_numeric($value) && strlen($value) > 6) {
            return number_format($value, 0, ',', ' ') . ' FCFA';
        }

        return $value;
    }

    /**
     * Formater le User Agent
     */
    private function formatUserAgent($userAgent)
    {
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        }

        return 'Autre';
    }

    /**
     * Résoudre l'URL de l'entité auditée si elle existe encore.
     * Retourne null si modèle inconnu ou entité supprimée.
     */
    private function resolveEntityUrl($audit): ?string
    {
        if (!class_exists($audit->auditable_type)) {
            return null;
        }
        $modelClass = $audit->auditable_type;
        $instance = $modelClass::find($audit->auditable_id);
        if (!$instance) {
            return null;
        }

        // Mapping route show par modèle (best-effort, retourne null si pas de route)
        try {
            return match ($audit->auditable_type) {
                'App\\Models\\ESBTPPaiement' => route('esbtp.paiements.show', $instance->id),
                'App\\Models\\ESBTPEtudiant' => route('esbtp.etudiants.show', $instance->id),
                'App\\Models\\ESBTPInscription' => route('esbtp.inscriptions.show', $instance->id),
                'App\\Models\\User' => route('esbtp.users.show', $instance->id),
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Vérifier si l'IP est interne
     */
    private function isInternalIP($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Obtenir les activités suspectes
     */
    private function getSuspiciousActivities($dateFrom, $dateTo)
    {
        return Audit::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where(function ($query) {
                $query->whereIn('event', ['deleted', 'restored'])
                      ->orWhere('created_at', '<', now()->setHour(8))
                      ->orWhere('created_at', '>', now()->setHour(18));
            })->count();
    }

    /**
     * Obtenir les audits filtrés (utilisé par les exports).
     */
    private function getFilteredAudits($request)
    {
        $query = Audit::with(['user'])->orderBy('created_at', 'desc');
        $this->applyCommonFilters($query, $request);
        return $query->get();
    }

    /**
     * Applique les filtres standards (model_type, event, user_id, date_from, date_to)
     * partagés entre `getAuditData()` (UI AJAX) et `getFilteredAudits()` (exports).
     */
    private function applyCommonFilters($query, Request $request): void
    {
        $filters = [
            'model_type' => 'auditable_type',
            'event' => 'event',
            'user_id' => 'user_id',
        ];
        foreach ($filters as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, $request->input($param));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }
}
