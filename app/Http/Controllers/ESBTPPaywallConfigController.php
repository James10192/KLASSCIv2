<?php

namespace App\Http\Controllers;

use App\Models\ESBTPSystemSetting;
use App\Models\ESBTPEtablissement;
use App\Models\User;
use App\Models\ESBTPEtudiant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ESBTPPaywallConfigController extends Controller
{
    /**
     * Vérifier si l'utilisateur a accès aux configurations paywall
     */
    protected function checkServiceTechniqueAccess()
    {
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Non authentifié');
        }

        // Vérifier si l'utilisateur a le rôle serviceTechnique OU les permissions spéciales
        $hasAccess = $user->hasRole('serviceTechnique') ||
                     $user->can('paywall.configure') ||
                     $user->can('system.technical_access');

        if (!$hasAccess) {
            // Rediriger vers la page de blocage avec message d'erreur
            return redirect()->route('esbtp.paywall-config.blocked')
                ->with('error', 'Accès refusé : Cette section est réservée au Service Technique d\'African Digit Consulting')
                ->with('paywall_blocked', true);
        }

        return null; // Accès autorisé
    }

    /**
     * Afficher la page de configuration du paywall
     */
    public function index()
    {
        // Vérifier l'accès service technique
        $accessCheck = $this->checkServiceTechniqueAccess();
        if ($accessCheck) {
            return $accessCheck; // Redirection si accès refusé
        }

        $currentEtablissementId = ESBTPSystemSetting::getCurrentEtablissementId();
        $etablissement = ESBTPEtablissement::find($currentEtablissementId);

        // Récupérer les paramètres du paywall
        $paywallConfig = [
            'is_active' => ESBTPSystemSetting::getValue('paywall_active', false),
            'subscription_end' => ESBTPSystemSetting::getValue('subscription_end_date', null),
            'max_users' => ESBTPSystemSetting::getValue('paywall_max_users', 50),
            'max_inscriptions_per_year' => ESBTPSystemSetting::getValue('paywall_max_inscriptions_per_year', 500),
            'plan_name' => ESBTPSystemSetting::getValue('paywall_plan_name', 'Plan Standard'),
            'plan_price' => ESBTPSystemSetting::getValue('paywall_plan_price', 0),
            'features' => json_decode(ESBTPSystemSetting::getValue('paywall_features', '[]'), true),
        ];

        // Calculer les statistiques actuelles
        $currentStats = $this->getCurrentStats($currentEtablissementId);

        // Vérifier le statut
        $status = $this->checkPaywallStatus($paywallConfig, $currentStats);

        return view('esbtp.paywall-config.index', compact(
            'paywallConfig',
            'currentStats',
            'status',
            'etablissement'
        ));
    }

    /**
     * Afficher la page d'upgrade pour les établissements
     */
    public function upgrade()
    {
        $currentEtablissementId = ESBTPSystemSetting::getCurrentEtablissementId();
        $etablissement = ESBTPEtablissement::find($currentEtablissementId);

        // Récupérer les paramètres du paywall
        $paywallConfig = [
            'is_active' => ESBTPSystemSetting::getValue('paywall_active', false),
            'subscription_end' => ESBTPSystemSetting::getValue('subscription_end_date', null),
            'max_users' => ESBTPSystemSetting::getValue('paywall_max_users', 50),
            'max_inscriptions_per_year' => ESBTPSystemSetting::getValue('paywall_max_inscriptions_per_year', 500),
            'plan_name' => ESBTPSystemSetting::getValue('paywall_plan_name', 'Plan Standard'),
            'plan_price' => ESBTPSystemSetting::getValue('paywall_plan_price', 0),
        ];

        // Calculer les statistiques actuelles
        $currentStats = $this->getCurrentStats($currentEtablissementId);

        // Vérifier le statut
        $status = $this->checkPaywallStatus($paywallConfig, $currentStats);

        return view('esbtp.paywall-config.upgrade', [
            'config' => $paywallConfig,
            'stats' => $currentStats,
            'reasons' => $status['reasons'],
            'etablissement' => $etablissement
        ]);
    }

    /**
     * Afficher la page de blocage d'accès
     */
    public function blocked()
    {
        $currentEtablissementId = ESBTPSystemSetting::getCurrentEtablissementId();
        $etablissement = ESBTPEtablissement::find($currentEtablissementId);

        // Récupérer les paramètres du paywall pour affichage
        $paywallConfig = [
            'is_active' => ESBTPSystemSetting::getValue('paywall_active', false),
            'subscription_end' => ESBTPSystemSetting::getValue('subscription_end_date', null),
            'max_users' => ESBTPSystemSetting::getValue('paywall_max_users', 50),
            'max_inscriptions_per_year' => ESBTPSystemSetting::getValue('paywall_max_inscriptions_per_year', 500),
            'plan_name' => ESBTPSystemSetting::getValue('paywall_plan_name', 'Plan Standard'),
            'plan_price' => ESBTPSystemSetting::getValue('paywall_plan_price', 0),
        ];

        // Calculer les statistiques actuelles
        $currentStats = $this->getCurrentStats($currentEtablissementId);

        // Vérifier le statut
        $status = $this->checkPaywallStatus($paywallConfig, $currentStats);

        return view('esbtp.paywall-config.blocked', [
            'config' => $paywallConfig,
            'stats' => $currentStats,
            'reasons' => $status['reasons'],
            'etablissement' => $etablissement
        ]);
    }

    /**
     * Mettre à jour la configuration du paywall
     */
    public function store(Request $request)
    {
        // Vérifier l'accès service technique
        $accessCheck = $this->checkServiceTechniqueAccess();
        if ($accessCheck) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé : Cette section est réservée au Service Technique d\'African Digit Consulting'
            ], 403);
        }

        $request->validate([
            'is_active' => 'required|boolean',
            'subscription_end' => 'nullable|date',
            'max_users' => 'required|integer|min:1',
            'max_inscriptions_per_year' => 'required|integer|min:1',
            'plan_name' => 'required|string|max:255',
            'plan_price' => 'required|numeric|min:0',
            'features' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Sauvegarder les paramètres
            ESBTPSystemSetting::setValue('paywall_active', $request->is_active ? '1' : '0');
            ESBTPSystemSetting::setValue('subscription_end_date', $request->subscription_end ?: '');
            ESBTPSystemSetting::setValue('paywall_max_users', $request->max_users);
            ESBTPSystemSetting::setValue('paywall_max_inscriptions_per_year', $request->max_inscriptions_per_year);
            ESBTPSystemSetting::setValue('paywall_plan_name', $request->plan_name);
            ESBTPSystemSetting::setValue('paywall_plan_price', $request->plan_price);
            ESBTPSystemSetting::setValue('paywall_features', json_encode($request->features ?? []));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Configuration du paywall sauvegardée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques actuelles de l'école
     */
    private function getCurrentStats($etablissementId)
    {
        // Compter les utilisateurs (enseignants, coordinateurs, secrétaires)
        $totalUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['enseignant', 'coordinateur', 'secretaire']);
        })->count();

        // Compter les inscriptions de l'année universitaire courante
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', 1)->first();
        $totalInscriptionsAnnee = 0;

        if ($anneeCourante) {
            $totalInscriptionsAnnee = \App\Models\ESBTPInscription::where('annee_universitaire_id', $anneeCourante->id)
                ->where('status', 'active')
                ->count();
        }

        return [
            'total_users' => $totalUsers,
            'total_inscriptions_current_year' => $totalInscriptionsAnnee,
            'current_year_name' => $anneeCourante ? $anneeCourante->nom : 'Aucune année courante',
        ];
    }

    /**
     * Vérifier le statut du paywall
     */
    private function checkPaywallStatus($config, $stats)
    {
        $status = [
            'is_blocked' => false,
            'reasons' => [],
            'warnings' => [],
            'is_expired' => false,
            'days_remaining' => null,
        ];

        // Vérifier si le paywall est actif
        if (!$config['is_active']) {
            return $status;
        }

        // Vérifier l'expiration de l'abonnement
        if ($config['subscription_end']) {
            $endDate = Carbon::parse($config['subscription_end']);
            $now = Carbon::now();

            if ($now->gt($endDate)) {
                $status['is_blocked'] = true;
                $status['is_expired'] = true;
                $status['reasons'][] = 'Abonnement expiré le ' . $endDate->format('d/m/Y');
            } else {
                $status['days_remaining'] = $now->diffInDays($endDate);

                if ($status['days_remaining'] <= 7) {
                    $status['warnings'][] = 'Abonnement expire dans ' . $status['days_remaining'] . ' jour(s)';
                }
            }
        }

        // Vérifier les limites d'utilisateurs
        if ($stats['total_users'] > $config['max_users']) {
            $status['is_blocked'] = true;
            $status['reasons'][] = 'Limite d\'utilisateurs dépassée (' . $stats['total_users'] . '/' . $config['max_users'] . ')';
        } elseif ($stats['total_users'] >= $config['max_users'] * 0.9) {
            $status['warnings'][] = 'Proche de la limite d\'utilisateurs (' . $stats['total_users'] . '/' . $config['max_users'] . ')';
        }

        // Vérifier les limites d'inscriptions par année
        if ($stats['total_inscriptions_current_year'] > $config['max_inscriptions_per_year']) {
            $status['is_blocked'] = true;
            $status['reasons'][] = 'Limite d\'inscriptions dépassée pour l\'année (' . $stats['total_inscriptions_current_year'] . '/' . $config['max_inscriptions_per_year'] . ')';
        } elseif ($stats['total_inscriptions_current_year'] >= $config['max_inscriptions_per_year'] * 0.9) {
            $status['warnings'][] = 'Proche de la limite d\'inscriptions pour l\'année (' . $stats['total_inscriptions_current_year'] . '/' . $config['max_inscriptions_per_year'] . ')';
        }

        return $status;
    }

    /**
     * API pour vérifier le statut (utilisé par le middleware)
     */
    public function checkStatus()
    {
        $currentEtablissementId = ESBTPSystemSetting::getCurrentEtablissementId();

        $paywallConfig = [
            'is_active' => ESBTPSystemSetting::getValue('paywall_active', false),
            'subscription_end' => ESBTPSystemSetting::getValue('subscription_end_date', null),
            'max_users' => ESBTPSystemSetting::getValue('paywall_max_users', 50),
            'max_inscriptions_per_year' => ESBTPSystemSetting::getValue('paywall_max_inscriptions_per_year', 500),
        ];

        $currentStats = $this->getCurrentStats($currentEtablissementId);
        $status = $this->checkPaywallStatus($paywallConfig, $currentStats);

        return response()->json([
            'is_blocked' => $status['is_blocked'],
            'reasons' => $status['reasons'],
            'warnings' => $status['warnings'],
        ]);
    }

    /**
     * Prolonger l'abonnement
     */
    public function extendSubscription(Request $request)
    {
        $request->validate([
            'months' => 'required|integer|min:1|max:24'
        ]);

        try {
            $currentEnd = ESBTPSystemSetting::getValue('subscription_end_date', null);
            $startDate = $currentEnd ? Carbon::parse($currentEnd) : Carbon::now();
            $newEndDate = $startDate->addMonths($request->months);

            ESBTPSystemSetting::setValue('subscription_end_date', $newEndDate->format('Y-m-d'));

            return response()->json([
                'success' => true,
                'message' => 'Abonnement prolongé jusqu\'au ' . $newEndDate->format('d/m/Y'),
                'new_end_date' => $newEndDate->format('Y-m-d')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prolongation: ' . $e->getMessage()
            ], 500);
        }
    }
}
