<?php

namespace App\Domain\Permissions;

use App\Helpers\SettingsHelper;
use App\Models\TemporaryPermissionGrant;
use App\Models\User;
use App\Services\PermissionRegistry;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Permissions ouvertes a une personne pour un temps limite.
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
    /** Prefixes et noms qu'on n'ouvre jamais pour un temps limite. */
    public const NON_ACCORDABLES_PREFIXES = [
        'identity.',   // lues par hasAnyPermission
        'system.',     // configuration et acces de secours
        'paywall.',    // abonnement de l'instance
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

        return $this->memo[$user->id] = array_values(array_unique(
            array_map(fn (string $n) => $this->registry->canonicalize($n), $noms)
        ));
    }

    public function dureeMaxJours(): int
    {
        $valeur = (int) SettingsHelper::get('permissions.temporaires.duree_max_jours', self::DUREE_MAX_JOURS_DEFAUT);

        return $valeur > 0 ? $valeur : self::DUREE_MAX_JOURS_DEFAUT;
    }

    public function estAccordable(string $permission): bool
    {
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
        CarbonInterface $fin,
        string $motif,
        User $auteur,
    ): TemporaryPermissionGrant {
        $permission = $this->registry->canonicalize($permission);
        // Les heures s'ecrivent dans le fuseau de l'application : une date
        // portant son propre decalage serait sinon enregistree telle quelle,
        // une heure a cote sur une instance a UTC.
        $fuseau = config('app.timezone');
        $debut = Carbon::instance($debut)->setTimezone($fuseau);
        $fin = Carbon::instance($fin)->setTimezone($fuseau);

        if (! $this->estAccordable($permission)) {
            throw new AccesTemporaireRefuse("La permission « {$permission} » ne peut pas être accordée pour un temps limité.");
        }
        if ($fin->lte($debut) || $fin->isPast()) {
            throw new AccesTemporaireRefuse('La fin de l\'accès doit être postérieure à son début et dans le futur.');
        }
        $max = $this->dureeMaxJours();
        if ($debut->diffInMinutes($fin) > $max * 24 * 60) {
            throw new AccesTemporaireRefuse("Un accès temporaire ne dépasse pas {$max} jours sur cette instance.");
        }
        // Le droit de l'auteur ne se verifie qu'aujourd'hui : un acces programme
        // loin dans le futur s'ouvrirait meme s'il l'avait perdu entre-temps.
        if ($debut->gt(now()->addDays($max))) {
            throw new AccesTemporaireRefuse("Un accès temporaire commence dans les {$max} prochains jours.");
        }
        // On ne donne pas ce qu'on n'a pas : sans cette garde, un acces
        // temporaire servirait a s'elever soi-meme par personne interposee.
        // Et on ne redistribue pas un acces qu'on tient soi-meme pour un temps :
        // il survivrait a sa propre echeance chez le beneficiaire.
        if (! $auteur->hasRole('superAdmin') && ! $this->detientDeFaconPermanente($auteur, $permission)) {
            throw new AccesTemporaireRefuse('Vous ne pouvez pas accorder une permission que vous ne détenez pas.');
        }
        // Le compte etudiant est partage avec les parents : on n'y ouvre rien.
        if ($beneficiaire->hasRole('etudiant') || $this->detientDeFaconPermanente($beneficiaire, 'identity.student')) {
            throw new AccesTemporaireRefuse('Les accès temporaires sont réservés au personnel, pas aux comptes étudiants.');
        }
        if ($this->detientDeFaconPermanente($beneficiaire, $permission)) {
            throw new AccesTemporaireRefuse("{$beneficiaire->name} détient déjà cette permission par son rôle.");
        }

        $chevauchement = TemporaryPermissionGrant::query()
            ->where('user_id', $beneficiaire->id)
            ->where('permission', $permission)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', $debut)
            ->where('starts_at', '<', $fin)
            ->first();
        if ($chevauchement) {
            throw new AccesTemporaireRefuse(sprintf(
                'Un accès à cette permission couvre déjà cette période (jusqu\'au %s). Retirez-le d\'abord.',
                $chevauchement->expires_at->format('d/m/Y H:i')
            ));
        }

        $acces = TemporaryPermissionGrant::create([
            'user_id' => $beneficiaire->id,
            'permission' => $permission,
            'starts_at' => $debut,
            'expires_at' => $fin,
            'motif' => $motif,
            'granted_by' => $auteur->id,
        ]);

        unset($this->memo[$beneficiaire->id]);
        Log::info('Acces temporaire accorde', [
            'grant_id' => $acces->id,
            'user_id' => $beneficiaire->id,
            'permission' => $permission,
            'expires_at' => $acces->expires_at->toIso8601String(),
            'granted_by' => $auteur->id,
        ]);

        return $acces;
    }

    public function retirer(TemporaryPermissionGrant $acces, User $auteur): TemporaryPermissionGrant
    {
        if ($acces->revoked_at === null) {
            $acces->forceFill(['revoked_at' => now(), 'revoked_by' => $auteur->id])->save();
            Log::info('Acces temporaire retire', ['grant_id' => $acces->id, 'revoked_by' => $auteur->id]);
        }
        unset($this->memo[$acces->user_id]);

        return $acces;
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
