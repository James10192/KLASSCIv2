<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use App\Models\User;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPDepense;
use App\Models\ESBTPFacture;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ESBTPAuditController extends Controller
{
    /**
     * Constructeur avec middleware de permissions
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:security.audit.view')->only(['index', 'show', 'getAuditData']);
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
        $users = User::select('id', 'name', 'email')->get();

        return view('esbtp.audit.index', compact('stats', 'auditableModels', 'users'));
    }

    /**
     * Obtenir les données d'audit via AJAX
     */
    public function getAuditData(Request $request)
    {
        $query = Audit::with(['user'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('model_type')) {
            $query->where('auditable_type', $request->model_type);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

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

        // Formatage des données pour l'affichage
        $audits->getCollection()->transform(function ($audit) {
            return [
                'id' => $audit->id,
                'event' => $this->formatEvent($audit->event),
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

        return view('esbtp.audit.show', compact('audit'));
    }

    /**
     * Audits spécifiques à la comptabilité
     */
    public function comptabiliteAudits(Request $request)
    {
        $this->authorize('comptabilite.audit.view');

        $query = Audit::whereIn('auditable_type', [
            'App\Models\ESBTPPaiement',
            'App\Models\ESBTPDepense',
            'App\Models\ESBTPFacture',
            'App\Models\ESBTPFactureDetail',
            'App\Models\ESBTPFraisScolarite',
            'App\Models\ESBTPSalaire',
            'App\Models\ESBTPBourse'
        ])->with(['user'])
          ->orderBy('created_at', 'desc');

        // Filtres spécifiques comptabilité
        if ($request->filled('montant_min')) {
            $query->where(function ($q) use ($request) {
                $q->where('old_values', 'like', '%"montant":' . $request->montant_min . '%')
                  ->orWhere('new_values', 'like', '%"montant":' . $request->montant_min . '%');
            });
        }

        if ($request->filled('type_operation')) {
            $query->where('event', $request->type_operation);
        }

        $audits = $query->paginate(25);

        return view('esbtp.audit.comptabilite', compact('audits'));
    }

    /**
     * Surveiller l'activité des utilisateurs
     */
    public function userActivity(Request $request)
    {
        $this->authorize('security.users.monitor');

        $userId = $request->get('user_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = Audit::with(['user'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $activities = $query->paginate(50);

        // Statistiques d'activité
        $stats = [
            'total_actions' => $query->count(),
            'unique_users' => Audit::whereBetween('created_at', [$dateFrom, $dateTo])
                                   ->distinct('user_id')->count('user_id'),
            'suspicious_activities' => $this->getSuspiciousActivities($dateFrom, $dateTo),
            'peak_hours' => $this->getPeakHours($dateFrom, $dateTo),
        ];

        $users = User::select('id', 'name', 'email')->get();

        return view('esbtp.audit.user-activity', compact('activities', 'stats', 'users'));
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
        $oldValues = json_decode($audit->old_values, true) ?? [];
        $newValues = json_decode($audit->new_values, true) ?? [];

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
     * Obtenir les heures de pointe
     */
    private function getPeakHours($dateFrom, $dateTo)
    {
        return Audit::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select(DB::raw('HOUR(created_at) as hour, COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->get()
            ->pluck('count', 'hour')
            ->toArray();
    }

    /**
     * Obtenir les audits filtrés
     */
    private function getFilteredAudits($request)
    {
        $query = Audit::with(['user'])->orderBy('created_at', 'desc');

        // Appliquer les filtres du request
        if ($request->filled('model_type')) {
            $query->where('auditable_type', $request->model_type);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query->get();
    }
}
