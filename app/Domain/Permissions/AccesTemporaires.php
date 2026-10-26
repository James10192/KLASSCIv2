<?php

namespace App\Domain\Permissions;

use App\Helpers\SettingsHelper;
use App\Models\TemporaryPermissionGrant;
use App\Models\User;
use App\Services\PermissionRegistry;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Permissions individuelles temporaires ou definitives, sans modifier les roles.
 *
 * Lu depuis le Gate::after d'AuthServiceProvider, donc a chaque `can()` qui n'a
 * pas deja repondu oui. L'acces cesse a l'heure dite sans aucune tache planifiee :
 * c'est la date qui decide, pas un traitement qui pourrait ne pas tourner.
 *
 * Ce que cela couvre : tout ce qui passe par `can()`, `@can` et le middleware
 * `permission:` (Spatie v5 appelle `can()`). Ce que cela ne couvre PAS :
 *  - les lectures directes `hasPermissionTo()` / `hasAnyPermission()` ;
 *  - une ability posee par `Gate::define` qui repond `false` : le Gate::after ne
 *    renverse qu'un resultat nul (`$result ??= $afterResult`).
 *
 * D'ou deux familles de permissions qu'on refuse d'accorder ici :
 *  - celles lues de cette facon (identity.*, admin.access) : l'acces ne
 *    servirait jamais ;
 *  - celles qui modifient les comptes, les roles, les reglages ou l'abonnement :
 *    le beneficiaire pourrait s'en servir pour se donner un acces qui survit a
 *    l'echeance — la promesse de ce service ne tiendrait plus.
 */
class AccesTemporaires
{
    public const MODE_TEMPORAIRE = 'temporaire';
    public const MODE_DEFINITIF = 'definitif';
    public const MODES = [self::MODE_TEMPORAIRE, self::MODE_DEFINITIF];

    /** Prefixes et noms qu'on n'ouvre jamais pour un temps limite. */
    public const NON_ACCORDABLES_PREFIXES = [
        'identity.',   // lues par hasAnyPermission
        'system.',     // configuration et acces de secours
        'paywall.',    // abonnement de l'instance
        'personnel.roles.', // attribution et modification durable des rôles
        'personnel.users.', // opérations sur identifiants et cycle de vie des comptes
        'superadmin.accounts.', // création de comptes privilégiés
        'module.',     // couche abonnement : un module non souscrit ne s'ouvre pas ainsi
    ];

    /**
     * Familles de comptes du personnel. Tout ce qui y cree, modifie ou supprime
     * un compte pose un role qui resterait apres l'echeance ; seule la
     * consultation (`.view`) s'accorde. Liste tenue a jour par
     * AccesTemporairesNonAccordablesTest, qui la deduit des controleurs.
     */
    public const FAMILLES_DE_COMPTES = [
        'directeurs_etudes',
        'responsables_scolarite',
        'services_scolarite',
        'agents_inscription',
        'secretaires',
        'comptables',
        'caissiers',
        'coordinateurs',
        'teachers',
    ];

    public const NON_ACCORDABLES = [
        '*',
        'admin.access',
        'admin.system.security',
        'security.users.monitor',
        'permissions.temporaires.manage',
        'permissions.individuels.permanents.manage',
        'sod.bypass',
        'paiements.validate.self_override',
        'comptabilite.period.bypass_lock',
        'comptabilite.reconciliation.bypass_lock',
        'users.manage',        // comptes et roles
        'personnel.manage',    // ouvre les roles personnalises
        'settings.edit',       // reglages de l'instance, dont la duree maximale ici
        'settings.pdf.manage',
        'security.backup.restore', // reecrit aussi les tables des roles et permissions
    ];

    /** Duree maximale par defaut, en jours, si l'ecole n'en a pas fixe. */
    public const DUREE_MAX_JOURS_DEFAUT = 90;

    /** @var array<int, array<int, string>> permissions actives par utilisateur, pour la requete en cours */
    private array $memo = [];

    /** Une trace par processus suffit : le defaut dure jusqu'a la migration. */
    private static bool $tableAbsenteSignalee = false;

    public function __construct(private readonly PermissionRegistry $registry)
    {
    }

    public function detient(User $user, string $ability): bool
    {
        $actives = $this->permissionsActives($user);
        if ($actives === []) {
            return false;
        }

        return in_array($this->registry->canonicalize($ability), $actives, true);
    }

    /** @param array<int, string> $abilities */
    public function detientUneDe(User $user, array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if ($this->detient($user, $ability)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> noms canoniques */
    public function permissionsActives(User $user): array
    {
        // Revalider l'identite AVANT le memo : une promotion vers le role
        // etudiant au cours de la meme requete doit fermer aussitot les acces.
        if ($user->hasRole('etudiant') || $this->detientDeFaconPermanente($user, 'identity.student')) {
            return $this->memo[$user->id] = [];
        }

        if (array_key_exists($user->id, $this->memo)) {
            return $this->memo[$user->id];
        }

        try {
            $noms = TemporaryPermissionGrant::query()
                ->actives()
                ->where('user_id', $user->id)
                ->pluck('permission')
                ->all();
        } catch (QueryException $e) {
            // Instance mise a jour avant sa migration : la table n'existe pas
            // encore. Personne n'a donc d'acces temporaire — on le dit au journal
            // une fois par processus, sans faire tomber chaque verification d'acces.
            if (! self::$tableAbsenteSignalee) {
                self::$tableAbsenteSignalee = true;
                Log::warning('Acces temporaires illisibles, table absente ?', ['erreur' => $e->getMessage()]);
            }
            $noms = [];
        }

        // Une ancienne délégation stockée avant durcissement des règles ne
        // redevient jamais utilisable à la lecture. Également valable pour les
        // droits définitifs déjà enregistrés : aucun effet permanent déguisé.
        $canoniques = array_unique(array_map(
            fn (string $n) => $this->registry->canonicalize($n), $noms
        ));

        return $this->memo[$user->id] = array_values(array_filter(
            $canoniques,
            fn (string $nom) => $this->estAccordable($nom)
        ));
    }

    public function dureeMaxJours(): int
    {
        $valeur = (int) SettingsHelper::get('permissions.temporaires.duree_max_jours', self::DUREE_MAX_JOURS_DEFAUT);

        return $valeur > 0 ? $valeur : self::DUREE_MAX_JOURS_DEFAUT;
    }

    public function estAccordable(string $permission): bool
    {
        // Un alias legacy (`create_coordinateurs`) se juge sous son nom canonique,
        // comme accorder() l'enregistrerait.
        $permission = $this->registry->canonicalize($permission);
        if (in_array($permission, self::NON_ACCORDABLES, true)) {
            return false;
        }
        foreach (self::NON_ACCORDABLES_PREFIXES as $prefixe) {
            if (str_starts_with($permission, $prefixe)) {
                return false;
            }
        }
        [$famille] = explode('.', $permission, 2);
        if (in_array($famille, self::FAMILLES_DE_COMPTES, true) && $permission !== $famille.'.view') {
            return false;
        }

        return $this->registry->permissionMeta($permission) !== null;
    }

    public function accorder(
        User $beneficiaire,
        string $permission,
        CarbonInterface $debut,
        ?CarbonInterface $fin,
        string $motif,
        User $auteur,
        bool $permanent = false,
    ): TemporaryPermissionGrant {
        $permission = $this->registry->canonicalize($permission);
        // Les heures s'ecrivent dans le fuseau de l'application : une date
        // portant son propre decalage serait sinon enregistree telle quelle,
        // une heure a cote sur une instance a UTC.
        $fuseau = config('app.timezone');
        $debut = Carbon::instance($debut)->setTimezone($fuseau);
        $fin = $fin === null ? null : Carbon::instance($fin)->setTimezone($fuseau);

        if (! $this->estAccordable($permission)) {
            throw new AccesTemporaireRefuse("La permission « {$permission} » ne peut pas être accordée individuellement.");
        }
        $max = $this->dureeMaxJours();
        if ($permanent) {
            if ($fin !== null || $debut->isFuture()) {
                throw new AccesTemporaireRefuse('Un accès définitif commence maintenant et ne comporte aucune date de fin.');
            }
            if (! $this->peutGererPermanents($auteur)) {
                throw new AccesTemporaireRefuse('Vous ne disposez pas du droit d’accorder des accès définitifs.');
            }
        } else {
            if ($fin === null || $fin->lte($debut) || $fin->isPast()) {
                throw new AccesTemporaireRefuse('La fin de l\'accès doit être postérieure à son début et dans le futur.');
            }
            if ($debut->diffInMinutes($fin) > $max * 24 * 60) {
                throw new AccesTemporaireRefuse("Un accès temporaire ne dépasse pas {$max} jours sur cette instance.");
            }
            // Une programmation lointaine survivrait a la perte du droit de l'auteur.
            if ($debut->gt(now()->addDays($max))) {
                throw new AccesTemporaireRefuse("Un accès temporaire commence dans les {$max} prochains jours.");
            }
        }
        if ($beneficiaire->is($auteur)) {
            throw new AccesTemporaireRefuse('Vous ne pouvez pas vous accorder un accès individuel.');
        }
        // On ne donne pas ce qu'on n'a pas : sans cette garde, un acces
        // temporaire servirait a s'elever soi-meme par personne interposee.
        // Et on ne redistribue pas un acces qu'on tient soi-meme pour un temps :
        // il survivrait a sa propre echeance chez le beneficiaire.
        if (! $this->detientDeFaconPermanente($auteur, $permission)) {
            throw new AccesTemporaireRefuse('Vous ne pouvez pas accorder une permission que vous ne détenez pas.');
        }
        // Le compte etudiant est partage avec les parents : on n'y ouvre rien.
        if ($beneficiaire->hasRole('etudiant') || $this->detientDeFaconPermanente($beneficiaire, 'identity.student')) {
            throw new AccesTemporaireRefuse('Les accès individuels sont réservés au personnel, pas aux comptes étudiants.');
        }
        if ($this->detientDeFaconPermanente($beneficiaire, $permission)) {
            throw new AccesTemporaireRefuse("{$beneficiaire->name} détient déjà cette permission par son rôle.");
        }

        $acces = DB::transaction(function () use ($beneficiaire, $permission, $debut, $fin, $motif, $auteur, $permanent) {
            // Un verrou sur le beneficiaire serialise les octrois concurrents.
            User::query()->whereKey($beneficiaire->id)->lockForUpdate()->firstOrFail();

            $chevauchement = TemporaryPermissionGrant::query()
                ->where('user_id', $beneficiaire->id)
                ->where('permission', $permission)
                ->whereNull('revoked_at')
                ->where(function ($q) use ($debut) {
                    $q->where(function ($p) {
                        $p->where('is_permanent', true)->whereNull('expires_at');
                    })->orWhere('expires_at', '>', $debut);
                })
                ->when(! $permanent, fn ($q) => $q->where('starts_at', '<', $fin))
                ->first();

            if ($chevauchement) {
                $jusqua = $chevauchement->is_permanent
                    ? 'sans date de fin'
                    : 'jusqu’au '.$chevauchement->expires_at?->format('d/m/Y H:i');
                throw new AccesTemporaireRefuse("Un accès à cette permission couvre déjà cette période ({$jusqua}). Retirez-le d'abord.");
            }

            return TemporaryPermissionGrant::create([
                'user_id' => $beneficiaire->id,
                'permission' => $permission,
                'starts_at' => $debut,
                'expires_at' => $fin,
                'is_permanent' => $permanent,
                'motif' => $motif,
                'granted_by' => $auteur->id,
            ]);
        }, 3);

        unset($this->memo[$beneficiaire->id]);
        Log::info('Acces individuel accorde', [
            'grant_id' => $acces->id,
            'user_id' => $beneficiaire->id,
            'permission' => $permission,
            'expires_at' => $acces->expires_at?->toIso8601String(),
            'is_permanent' => $acces->is_permanent,
            'granted_by' => $auteur->id,
        ]);

        return $acces;
    }

    public function retirer(TemporaryPermissionGrant $acces, User $auteur): TemporaryPermissionGrant
    {
        if ($acces->is_permanent && ! $this->peutGererPermanents($auteur)) {
            throw new AccesTemporaireRefuse('Vous ne disposez pas du droit de retirer un accès définitif.');
        }
        $retire = TemporaryPermissionGrant::whereKey($acces->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'revoked_by' => $auteur->id]);
        if ($retire) {
            Log::info('Acces individuel retire', ['grant_id' => $acces->id, 'revoked_by' => $auteur->id]);
        }
        $acces->refresh();
        unset($this->memo[$acces->user_id]);

        return $acces;
    }

    public function peutGererPermanents(User $auteur): bool
    {
        // Une delegation individuelle ne peut pas en engendrer une autre.
        return $this->detientDeFaconPermanente($auteur, 'permissions.individuels.permanents.manage')
            && $this->detientDeFaconPermanente($auteur, 'permissions.temporaires.manage')
            && $this->detientDeFaconPermanente($auteur, 'personnel.manage');
    }

    private function detientDeFaconPermanente(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
