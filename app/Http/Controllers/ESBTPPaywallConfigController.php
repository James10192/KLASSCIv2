<?php

namespace App\Http\Controllers;

use App\Models\ESBTPSystemSetting;
use App\Models\ESBTPEtablissement;
use App\Services\Master\AbonnementDeLInstance;
use App\Services\Paywall\CodesDUrgence;
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

        // ACCÈS RÉSERVÉ EXCLUSIVEMENT AU SERVICE TECHNIQUE D'AFRICAN DIGIT CONSULTING
        // Seul le rôle serviceTechnique est autorisé, pas les superAdmin
        $hasAccess = $user->can('paywall.manage');

        if (!$hasAccess) {
            // Rediriger vers la page de blocage avec message d'erreur
            return redirect()->route('esbtp.paywall-config.blocked')
                ->with('error', 'Accès refusé : Cette section est réservée au Service Technique d\'African Digit Consulting')
                ->with('paywall_blocked', true);
        }

        return null; // Accès autorisé
    }

    /**
     * L'abonnement de l'instance, lu chez adminKlassci (secours local dit).
     */
    public function index(AbonnementDeLInstance $abonnement, CodesDUrgence $codes)
    {
        $accessCheck = $this->checkServiceTechniqueAccess();
        if ($accessCheck) {
            return $accessCheck;
        }

        return view('esbtp.paywall-config.index', [
            'etat' => $abonnement->etat(),
            'codesActifs' => $codes->actifs(),
            'reglagesLocaux' => $this->reglagesLocaux(),
            'etablissement' => ESBTPEtablissement::find(ESBTPSystemSetting::getCurrentEtablissementId()),
        ]);
    }

    /**
     * « Actualiser depuis adminKlassci » : oublie le cache, relit le master
     * et rend le bloc d'etat a jour. Aucun rechargement de page.
     */
    public function refresh(AbonnementDeLInstance $abonnement)
    {
        $accessCheck = $this->checkServiceTechniqueAccess();
        if ($accessCheck) {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $etat = $abonnement->rafraichir();

        return response()->json([
            'success' => true,
            'source' => $etat['source'],
            'master_joignable' => $etat['master_joignable'],
            'message' => $etat['source'] === 'master'
                ? 'Valeurs relues dans adminKlassci.'
                : ($etat['master_configure']
                    ? 'adminKlassci ne répond pas : valeurs locales de secours affichées.'
                    : 'adminKlassci n\'est pas configuré sur cette instance.'),
            'html' => view('esbtp.paywall-config.partials._etat', ['etat' => $etat])->render(),
        ]);
    }

    /**
     * Page vue par une ecole bloquee par le paywall.
     */
    public function upgrade(AbonnementDeLInstance $abonnement)
    {
        return view('esbtp.paywall-config.upgrade', $this->donneesEcole($abonnement));
    }

    /**
     * Page de blocage d'acces (y compris l'acces refuse aux pages du service technique).
     */
    public function blocked(AbonnementDeLInstance $abonnement)
    {
        return view('esbtp.paywall-config.blocked', $this->donneesEcole($abonnement));
    }

    private function donneesEcole(AbonnementDeLInstance $abonnement): array
    {
        $etat = $abonnement->etat();

        return [
            'etat' => $etat,
            'reasons' => $etat['statut']['reasons'],
            'etablissement' => ESBTPEtablissement::find(ESBTPSystemSetting::getCurrentEtablissementId()),
            'contactEmail' => config('app.support_email'),
            'contactTelephone' => AbonnementDeLInstance::TELEPHONE_EDITEUR,
        ];
    }

    /** Les reglages locaux de secours, pour le formulaire quand le master n'est pas configure. */
    private function reglagesLocaux(): array
    {
        return [
            'is_active' => (bool) ESBTPSystemSetting::getValue('paywall_active', false),
            'subscription_end' => ESBTPSystemSetting::getValue('subscription_end_date', null),
            'max_users' => ESBTPSystemSetting::getValue('paywall_max_users', 50),
            'max_inscriptions_per_year' => ESBTPSystemSetting::getValue('paywall_max_inscriptions_per_year', 500),
            'plan_name' => ESBTPSystemSetting::getValue('paywall_plan_name', 'Plan Standard'),
            'plan_price' => ESBTPSystemSetting::getValue('paywall_plan_price', 0),
        ];
    }

    /** Le master porte la verite des limites : on ne les ecrit plus en local. */
    private function masterConfigure(): bool
    {
        return app(\App\Services\Master\LimitesDuMaster::class)->estConfigure();
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

        // Le master configure, seul l'interrupteur d'application reste local :
        // plan, echeance et limites se modifient dans la fiche adminKlassci.
        // Les ecrire ici recreait la divergence que cet ecran a supprimee.
        if ($this->masterConfigure()) {
            $request->validate(['is_active' => 'required|boolean']);

            if ($request->hasAny(['subscription_end', 'max_users', 'max_inscriptions_per_year', 'plan_name', 'plan_price', 'features'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le plan, l\'échéance et les limites se modifient dans adminKlassci.',
                ], 409);
            }

            ESBTPSystemSetting::setValue('paywall_active', $request->boolean('is_active') ? '1' : '0');

            return response()->json([
                'success' => true,
                'message' => $request->boolean('is_active') ? 'Paywall appliqué sur cette instance.' : 'Paywall suspendu sur cette instance.',
            ]);
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
            ESBTPSystemSetting::setValue('paywall_active', $request->boolean('is_active') ? '1' : '0');
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
     * Statut JSON (meme calcul que le middleware).
     */
    public function checkStatus(AbonnementDeLInstance $abonnement)
    {
        $statut = $abonnement->statutDeBlocage();

        return response()->json([
            'is_blocked' => $statut['is_blocked'],
            'reasons' => $statut['reasons'],
            'warnings' => $statut['warnings'],
        ]);
    }

    /**
     * Prolonger l'abonnement
     */
    public function extendSubscription(Request $request)
    {
        // Seule action de ce contrôleur qui ne vérifiait pas l'accès elle-même :
        // `index()`, `store()` et `generateEmergencyCode()` le font toutes.
        // Elle ne dépendait donc que de la garde de route — et prolonger un
        // abonnement est précisément le geste qu'on ne veut pas laisser à
        // l'établissement lui-même.
        $accessCheck = $this->checkServiceTechniqueAccess();
        if ($accessCheck) {
            return $accessCheck; // Redirection si accès refusé
        }

        if ($this->masterConfigure()) {
            return response()->json([
                'success' => false,
                'message' => 'L\'échéance se prolonge dans adminKlassci.',
            ], 409);
        }

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

    /**
     * Générer un code d'urgence temporaire
     */
    public function generateEmergencyCode(Request $request)
    {
        // Vérifier l'accès service technique
        $accessCheck = $this->checkServiceTechniqueAccess();
        if ($accessCheck) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé : Cette section est réservée au Service Technique d\'African Digit Consulting'
            ], 403);
        }

        try {
            // Générer un code unique et sécurisé
            $timestamp = time();
            $random = bin2hex(random_bytes(4)); // 8 caractères hexadécimaux
            $emergencyCode = 'EMERGENCY' . $timestamp . strtoupper($random);

            // Stocker le code temporairement avec expiration (1 heure)
            $codeData = [
                'code' => $emergencyCode,
                'created_at' => $timestamp,
                'expires_at' => $timestamp + 3600, // 1 heure
                'created_by' => auth()->user()->email,
                'used' => false
            ];

            // Sauvegarder dans les settings système (sera nettoyé automatiquement à l'expiration)
            ESBTPSystemSetting::setValue('emergency_code_' . $emergencyCode, json_encode($codeData));

            // Générer l'URL d'accès d'urgence
            $baseUrl = request()->getSchemeAndHttpHost();
            $emergencyUrl = $baseUrl . '/esbtp?emergency_code=' . $emergencyCode;

            // Log de sécurité
            \Log::warning('Code d\'urgence généré', [
                'code' => $emergencyCode,
                'generated_by' => auth()->user()->email,
                'expires_at' => date('Y-m-d H:i:s', $codeData['expires_at']),
                'ip' => request()->ip()
            ]);

            return response()->json([
                'success' => true,
                'code' => $emergencyCode,
                'url' => $emergencyUrl,
                'expires_in' => '1 heure',
                'message' => 'Code d\'urgence généré avec succès'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur génération code d\'urgence', [
                'error' => $e->getMessage(),
                'user' => auth()->user()->email ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du code: ' . $e->getMessage()
            ], 500);
        }
    }
}
