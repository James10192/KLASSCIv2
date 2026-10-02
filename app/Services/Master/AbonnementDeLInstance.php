<?php

namespace App\Services\Master;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPSystemSetting;
use App\Models\User;
use Carbon\Carbon;

/**
 * L'abonnement de l'instance tel que le paywall l'applique et que le service
 * technique le lit.
 *
 * La source de verite est la fiche du tenant dans adminKlassci, lue par
 * {@see LimitesDuMaster}. Les anciens reglages locaux (`paywall_max_users`,
 * `subscription_end_date`, `paywall_plan_name`…) ne servent plus que de
 * SECOURS : quand le master n'est pas configure, ou ne repond pas. L'ecran le
 * dit alors en toutes lettres, au lieu de presenter ces valeurs comme si
 * elles faisaient foi — c'est exactement ainsi qu'esbtp-abidjan affichait
 * « Offre Partenaire, 0 FCFA » pendant que sa fiche portait l'offre Elite.
 *
 * Un seul calcul du blocage, utilise par {@see \App\Http\Middleware\PaywallMiddleware}
 * ET par l'ecran : ce que l'ecran annonce est ce que le middleware applique.
 */
class AbonnementDeLInstance
{
    /** Au-dela, la limite est l'infini des plans « Elite » (999 999). */
    public const SEUIL_ILLIMITE = 999999;

    /** Part d'une limite a partir de laquelle on previent. */
    public const SEUIL_ALERTE_PCT = 90;

    /** Le contact de l'editeur, montre aux ecoles bloquees. */
    public const TELEPHONE_EDITEUR = '+2250595459843';

    private const LIBELLES_FONCTIONNALITES = [
        'create_user' => 'Création d\'utilisateurs',
        'create_staff' => 'Création de personnel',
        'create_student_account' => 'Création de comptes étudiants',
        'create_inscription' => 'Nouvelles inscriptions',
        'create_reinscription' => 'Réinscriptions',
        'upload_file' => 'Envoi de fichiers',
    ];

    private const LIBELLES_STATUT = [
        'active' => 'Active',
        'suspended' => 'Suspendue',
        'archived' => 'Archivée',
    ];

    public function __construct(private LimitesDuMaster $limites)
    {
    }

    /** Oublie le cache et relit adminKlassci. */
    public function rafraichir(): array
    {
        $this->limites->oublier();

        return $this->etat();
    }

    /**
     * Tout ce qu'il faut pour afficher l'abonnement, depuis le master ou, a
     * defaut, depuis les reglages locaux — avec la provenance dite.
     */
    public function etat(): array
    {
        $donnees = $this->limites->lire();
        $configure = $this->limites->estConfigure();

        $etat = $donnees !== null
            ? $this->depuisLeMaster($donnees)
            : $this->depuisLesReglagesLocaux();

        $echec = $donnees === null ? $this->limites->dernierEchec() : null;
        $luA = $donnees !== null ? $this->limites->derniereLecture() : null;

        return $etat + [
            'master_configure' => $configure,
            'master_joignable' => $donnees !== null ? true : ($configure ? false : null),
            'erreur_master' => $echec['erreur'] ?? null,
            'echec_a' => isset($echec['a']) ? Carbon::parse($echec['a']) : null,
            'lu_a' => $luA ? Carbon::parse($luA) : null,
            'paywall_actif' => (bool) ESBTPSystemSetting::getValue('paywall_active', false),
            'fiche_url' => $this->adresseDeLaFiche($donnees),
            'statut' => $this->statutDeBlocage($etat),
        ];
    }

    /**
     * Le blocage tel que le middleware l'applique : ['is_blocked', 'reasons', 'warnings'].
     */
    public function statutDeBlocage(?array $etat = null): array
    {
        if ($etat === null) {
            $donnees = $this->limites->lire();
            $etat = $donnees !== null ? $this->depuisLeMaster($donnees) : $this->depuisLesReglagesLocaux();
        }

        $statut = ['is_blocked' => false, 'reasons' => [], 'warnings' => []];
        $abonnement = $etat['abonnement'];

        if ($abonnement['expire']) {
            $statut['is_blocked'] = true;
            $statut['reasons'][] = 'Abonnement expiré le ' . ($abonnement['fin'] ? $abonnement['fin']->format('d/m/Y') : 'date inconnue');
        } elseif ($abonnement['jours_restants'] !== null && $abonnement['jours_restants'] <= 7) {
            $statut['warnings'][] = 'Abonnement expire dans ' . $abonnement['jours_restants'] . ' jour(s)';
        }

        foreach ($etat['usages'] as $usage) {
            if ($usage['actuel'] === null || $usage['max'] === null || $usage['illimite']) {
                continue;
            }

            $chiffres = $this->nombre($usage['actuel']) . '/' . $this->nombre($usage['max']) . ($usage['unite'] ? ' ' . $usage['unite'] : '');

            if ($usage['depasse']) {
                $statut['is_blocked'] = true;
                $statut['reasons'][] = $usage['libelle_limite'] . ' dépassée (' . $chiffres . ')';
            } elseif ($usage['pct'] !== null && $usage['pct'] >= self::SEUIL_ALERTE_PCT) {
                $statut['warnings'][] = 'Proche de la ' . mb_strtolower($usage['libelle_limite'], 'UTF-8') . ' (' . $chiffres . ')';
            }
        }

        // Master configure mais injoignable : les reglages locaux sont peut-etre
        // perimes depuis que la fiche adminKlassci fait foi. Une panne reseau ne
        // doit pas fermer l'ecole : on previent, on ne bloque pas.
        if ($statut['is_blocked'] && ($etat['source'] ?? null) === 'local' && $this->limites->estConfigure()) {
            $statut['warnings'] = array_merge(
                array_map(fn ($r) => $r . ' (valeur locale, adminKlassci injoignable)', $statut['reasons']),
                $statut['warnings']
            );
            $statut['reasons'] = [];
            $statut['is_blocked'] = false;
        }

        return $statut;
    }

    private function depuisLeMaster(array $d): array
    {
        $fin = ! empty($d['subscription']['end_date']) ? Carbon::parse($d['subscription']['end_date'])->startOfDay() : null;
        $debut = ! empty($d['subscription']['start_date']) ? Carbon::parse($d['subscription']['start_date'])->startOfDay() : null;

        $usages = [
            'users' => $this->usage('Utilisateurs', 'Limite d\'utilisateurs', 'fa-users',
                $d['current_usage']['users'] ?? null, $d['limits']['max_users'] ?? null),
            'staff' => $this->usage('Personnel', 'Limite de personnel', 'fa-user-tie',
                $d['current_usage']['staff'] ?? null, $d['limits']['max_staff'] ?? null),
            'students' => $this->usage('Étudiants avec compte', 'Limite d\'étudiants', 'fa-user-graduate',
                $d['current_usage']['students'] ?? null, $d['limits']['max_students'] ?? null),
            'inscriptions' => $this->usage('Inscriptions de l\'année', 'Limite d\'inscriptions pour l\'année', 'fa-file-signature',
                $d['current_usage']['inscriptions_per_year'] ?? null, $d['limits']['max_inscriptions_per_year'] ?? null),
            'storage' => $this->usage('Stockage', 'Limite de stockage', 'fa-database',
                $d['current_usage']['storage_mb'] ?? null, $d['limits']['max_storage_mb'] ?? null, 'Mo'),
        ];

        $plan = $d['plan'] ?? null;

        return [
            'source' => 'master',
            'code' => $d['tenant_code'] ?? config('app.tenant_code'),
            'nom' => $d['tenant_name'] ?? null,
            'plan' => $plan,
            'plan_label' => $d['plan_label'] ?? ($plan ? ucfirst((string) $plan) : null),
            'tarif_mensuel' => isset($d['monthly_fee']) ? (int) $d['monthly_fee'] : null,
            'statut_tenant' => $d['status'] ?? null,
            'statut_tenant_label' => self::LIBELLES_STATUT[$d['status'] ?? ''] ?? ($d['status'] ?? null),
            'abonnement' => $this->abonnement($debut, $fin, (bool) ($d['subscription']['is_expired'] ?? false)),
            'usages' => $usages,
            'fonctionnalites_bloquees' => $this->libellesFonctionnalites($d['blocked_features'] ?? []),
            'releve_a' => ! empty($d['last_stats_update']) ? Carbon::parse($d['last_stats_update']) : null,
        ];
    }

    private function depuisLesReglagesLocaux(): array
    {
        $finBrute = ESBTPSystemSetting::getValue('subscription_end_date', null);
        $fin = $finBrute ? Carbon::parse($finBrute)->startOfDay() : null;

        $utilisateurs = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['enseignant', 'coordinateur', 'secretaire']))->count();

        $annee = ESBTPAnneeUniversitaire::where('is_current', 1)->first();
        $inscriptions = $annee
            ? ESBTPInscription::where('annee_universitaire_id', $annee->id)->where('status', 'active')->count()
            : null;

        $maxUsers = (int) ESBTPSystemSetting::getValue('paywall_max_users', 50);
        $maxInscriptions = (int) ESBTPSystemSetting::getValue('paywall_max_inscriptions_per_year', 500);
        $prix = ESBTPSystemSetting::getValue('paywall_plan_price', null);

        // Personnel, comptes etudiants et stockage ne sont pas mesures en local :
        // ils restent « non mesures » (null) plutot que d'afficher un zero faux.
        $usages = [
            'users' => $this->usage('Utilisateurs', 'Limite d\'utilisateurs', 'fa-users', $utilisateurs, $maxUsers),
            'staff' => $this->usage('Personnel', 'Limite de personnel', 'fa-user-tie', null, null),
            'students' => $this->usage('Étudiants avec compte', 'Limite d\'étudiants', 'fa-user-graduate', null, null),
            'inscriptions' => $this->usage('Inscriptions de l\'année', 'Limite d\'inscriptions pour l\'année', 'fa-file-signature',
                $inscriptions, $maxInscriptions),
            'storage' => $this->usage('Stockage', 'Limite de stockage', 'fa-database', null, null, 'Mo'),
        ];

        return [
            'source' => 'local',
            'code' => config('app.tenant_code'),
            'nom' => null,
            'plan' => null,
            'plan_label' => ESBTPSystemSetting::getValue('paywall_plan_name', null),
            'tarif_mensuel' => $prix !== null && $prix !== '' ? (int) $prix : null,
            'statut_tenant' => null,
            'statut_tenant_label' => null,
            'abonnement' => $this->abonnement(null, $fin, $fin !== null && $fin->lt(now()->startOfDay())),
            'usages' => $usages,
            'fonctionnalites_bloquees' => [],
            'releve_a' => null,
        ];
    }

    private function abonnement(?Carbon $debut, ?Carbon $fin, bool $expire): array
    {
        $jours = $fin ? now()->startOfDay()->diffInDays($fin, false) : null;

        return [
            'debut' => $debut,
            'fin' => $fin,
            'expire' => $expire || ($jours !== null && $jours < 0),
            'jours_restants' => $jours !== null ? max(0, (int) $jours) : null,
            // Duree totale connue : sert a la jauge du temps ecoule.
            'pct_ecoule' => ($debut && $fin && $fin->gt($debut))
                ? (int) round(min(100, max(0, $debut->diffInDays(now()->startOfDay(), false) / $debut->diffInDays($fin) * 100)))
                : null,
        ];
    }

    /**
     * « Dépassée » veut dire strictement au-dessus de la limite, comme
     * is_over_quota cote master et comme l'ancien middleware. Les drapeaux
     * *_over_limit du master comptent l'egalite (>=) : une ecole a 30/30
     * utilisateurs n'est pas en faute, elle est a la limite. Ils ne servent
     * donc pas au blocage.
     */
    private function usage(string $libelle, string $libelleLimite, string $icone, $actuel, $max, ?string $unite = null): array
    {
        $actuel = is_numeric($actuel) ? (int) $actuel : null;
        $max = is_numeric($max) ? (int) $max : null;
        $illimite = $max !== null && $max >= self::SEUIL_ILLIMITE;
        $depasse = $actuel !== null && $max !== null && ! $illimite && $actuel > $max;
        $pct = ($actuel !== null && $max !== null && $max > 0 && ! $illimite) ? round($actuel / $max * 100, 1) : null;

        return [
            'libelle' => $libelle,
            'libelle_limite' => $libelleLimite,
            'icone' => $icone,
            'actuel' => $actuel,
            'max' => $max,
            'illimite' => $illimite,
            'pct' => $pct,
            'depasse' => $depasse,
            'unite' => $unite,
        ];
    }

    private function libellesFonctionnalites(array $cles): array
    {
        return collect($cles)->unique()->map(fn ($cle) => self::LIBELLES_FONCTIONNALITES[$cle] ?? $cle)->values()->all();
    }

    /**
     * La fiche du tenant dans adminKlassci : l'adresse que le master donne,
     * sinon la liste du panneau filtree sur le code, deduite de MASTER_API_URL.
     * Jamais de domaine ecrit ici.
     */
    private function adresseDeLaFiche(?array $donnees): ?string
    {
        if (! empty($donnees['admin_url'])) {
            return (string) $donnees['admin_url'];
        }

        $api = (string) config('services.master.api_url');
        $code = config('app.tenant_code');
        if ($api === '' || ! $code) {
            return null;
        }

        $base = preg_replace('#/api/?$#', '', rtrim($api, '/'));

        return $base . '/admin/tenants?tableSearch=' . rawurlencode((string) $code);
    }

    private function nombre(int $n): string
    {
        return number_format($n, 0, ',', ' ');
    }
}
