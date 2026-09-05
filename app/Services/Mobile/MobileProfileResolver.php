<?php

namespace App\Services\Mobile;

use App\Helpers\SettingsHelper;
use App\Models\User;

/**
 * Quel profil mobile (barre d'onglets, feuilles) sert-on a cet utilisateur ?
 *
 * Le profil ne se lit JAMAIS sur le nom d'un role : une ecole cree ses propres
 * roles (rule customizable-roles). Il se deduit des PERMISSIONS, dans l'ordre
 * exact du dispatcher de tableau de bord (DashboardController::index) :
 * caisse, puis comptabilite, puis enseignant, puis etudiant. Un role custom qui
 * ne porte aucune de ces permissions peut tout de meme declarer son profil
 * (colonne roles.mobile_profile, choisie par l'ecole dans le formulaire du role).
 *
 * Le superAdmin voit tout : il recoit « comptable » par defaut et peut basculer
 * de profil pour la session (route POST mobile.profil) afin de verifier chacun.
 *
 * Le calcul est memoise pour la duree de la requete : il est appele par un
 * composer de vue sur '*', donc potentiellement des dizaines de fois par page.
 */
class MobileProfileResolver
{
    public const REGLAGE_ACTIF = 'ui.mobile_shell.enabled';

    /** Cle de session portant le profil force par un superAdmin. */
    public const CLE_SESSION = 'mobile_profile';

    public const CAISSIER = 'caissier';
    public const COMPTABLE = 'comptable';
    public const ENSEIGNANT = 'enseignant';
    public const ETUDIANT = 'etudiant';

    public const PROFILS = [
        self::CAISSIER,
        self::COMPTABLE,
        self::ENSEIGNANT,
        self::ETUDIANT,
    ];

    /**
     * Ordre STRICT du dispatcher de tableau de bord — ne pas reordonner sans
     * reordonner DashboardController::index en meme temps.
     */
    private const PROFIL_PAR_PERMISSION = [
        'module.caisse.access' => self::CAISSIER,
        'comptabilite.access' => self::COMPTABLE,
        'identity.teach' => self::ENSEIGNANT,
        'identity.student' => self::ETUDIANT,
    ];

    /** @var array<int, string|null> memo par instance d'utilisateur, pour la requete */
    private static array $memo = [];

    /**
     * Memo du reglage d'instance pour la requete : le composer sur '*' appelle
     * actif() a chaque vue rendue (des dizaines par page), et chaque lecture de
     * reglage passe par Cache::remember (un fichier lu avec le pilote « file »).
     */
    private static ?bool $memoActif = null;

    /**
     * Libelles affiches a l'ecole quand elle choisit le profil d'un role.
     *
     * @return array<string, string>
     */
    public static function libelles(): array
    {
        return [
            self::CAISSIER => 'Caisse (encaissements)',
            self::COMPTABLE => 'Comptabilité',
            self::ENSEIGNANT => 'Enseignant',
            self::ETUDIANT => 'Étudiant',
        ];
    }

    public static function estUnProfil(?string $valeur): bool
    {
        return $valeur !== null && in_array($valeur, self::PROFILS, true);
    }

    /**
     * Page d'ouverture de l'application installee (start_url du manifest).
     *
     * /dashboard aiguille deja chaque personne selon ses permissions ; le profil
     * n'y est ajoute qu'en indication, pour que le shell sache d'emblee quelle
     * barre d'onglets ouvrir sans attendre un premier rendu.
     */
    public static function startUrl(?string $profil): string
    {
        if (! self::estUnProfil($profil)) {
            return '/dashboard';
        }

        return '/dashboard?profil=' . $profil;
    }

    /** Vide la memo — a appeler apres avoir change le profil force en session. */
    public static function oublier(): void
    {
        self::$memo = [];
        self::$memoActif = null;
    }

    /**
     * @return string|null l'un de self::PROFILS, ou null (pas de shell mobile)
     */
    public function resolve(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $cle = spl_object_id($user);
        if (array_key_exists($cle, self::$memo)) {
            return self::$memo[$cle];
        }

        return self::$memo[$cle] = $this->calculer($user);
    }

    /** Le shell mobile est-il active sur cette instance ? */
    public function actif(): bool
    {
        return $this->shellActif();
    }

    private function calculer(User $user): ?string
    {
        if (! $this->shellActif()) {
            return null;
        }

        // Seule exception toleree a l'interdiction de hasRole() : le superAdmin
        // possede toutes les permissions, la cascade ci-dessous le rangerait
        // toujours en caisse.
        if ($user->hasRole('superAdmin')) {
            $force = $this->profilForce();

            return self::estUnProfil($force) ? $force : self::COMPTABLE;
        }

        foreach (self::PROFIL_PAR_PERMISSION as $permission => $profil) {
            // $user->can() passe par le Gate, comme le dispatcher : un role
            // custom, un alias legacy ou un Gate::before y sont tous vus.
            if ($user->can($permission)) {
                return $profil;
            }
        }

        return $this->profilDesRoles($user);
    }

    /**
     * Premier role de l'utilisateur ayant declare un profil mobile.
     *
     * getRelationValue('roles') rend la relation deja chargee (hasRole vient de
     * la charger) sans requete supplementaire. La colonne mobile_profile est
     * ajoutee par migration : pendant la fenetre « pull puis migrate », elle
     * manque et l'attribut vaut null — pas de shell, pas d'erreur.
     */
    protected function profilDesRoles(User $user): ?string
    {
        $roles = $user->getRelationValue('roles');

        foreach ($roles ?? [] as $role) {
            $profil = $role->getAttribute('mobile_profile');

            if (is_string($profil) && self::estUnProfil($profil)) {
                return $profil;
            }
        }

        return null;
    }

    protected function shellActif(): bool
    {
        if (self::$memoActif === null) {
            self::$memoActif = filter_var(SettingsHelper::get(self::REGLAGE_ACTIF, true), FILTER_VALIDATE_BOOLEAN);
        }

        return self::$memoActif;
    }

    protected function profilForce(): ?string
    {
        $valeur = session(self::CLE_SESSION);

        return is_string($valeur) ? $valeur : null;
    }
}
