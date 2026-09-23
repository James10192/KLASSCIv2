<?php

namespace App\Domain\Permissions;

use App\Helpers\SettingsHelper;
use App\Models\TemporaryPermissionGrant;
use App\Models\User;
use App\Services\PermissionRegistry;
use Carbon\CarbonInterface;
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
 * `permission:` (Spatie v5 appelle `can()`). Ce que cela ne couvre PAS : les
 * lectures directes `hasPermissionTo()` / `hasAnyPermission()`. D'ou la liste
 * des permissions qu'on refuse d'accorder ici — elles sont lues de cette facon,
 * un acces temporaire y serait accorde sans jamais servir.
 */
class AccesTemporaires
{
    /** Prefixes et noms qu'on n'ouvre jamais pour un temps limite. */
    public const NON_ACCORDABLES_PREFIXES = ['identity.'];

    public const NON_ACCORDABLES = [
        '*',
        'admin.access',
        'module.technical_support.access',
        'permissions.temporaires.manage',
    ];

    /** Duree maximale par defaut, en jours, si l'ecole n'en a pas fixe. */
    public const DUREE_MAX_JOURS_DEFAUT = 90;

    /** @var array<int, array<int, string>> permissions actives par utilisateur, pour la requete en cours */
    private array $memo = [];

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
            // une fois par requete, sans faire tomber chaque verification d'acces.
            Log::warning('Acces temporaires illisibles, table absente ?', ['erreur' => $e->getMessage()]);
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
        // On ne donne pas ce qu'on n'a pas : sans cette garde, un acces
        // temporaire servirait a s'elever soi-meme par personne interposee.
        // Et on ne redistribue pas un acces qu'on tient soi-meme pour un temps :
        // il survivrait a sa propre echeance chez le beneficiaire.
        if (! $auteur->hasRole('superAdmin') && ! $this->detientDeFaconPermanente($auteur, $permission)) {
            throw new AccesTemporaireRefuse('Vous ne pouvez pas accorder une permission que vous ne détenez pas.');
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
