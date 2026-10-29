<?php

namespace App\Domain\Support\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Résout localement une demande d'intervention du Master.
 *
 * La permission du Master est une exigence, jamais une autorisation.
 * On évalue la permission effective de chaque utilisateur ACTIF dans l'instance.
 * Aucun choix implicite si plusieurs personnes sont habilitées : l'opérateur
 * doit spécifier une personne ou suivre une procédure d'attribution dédiée.
 */
final class ResoudreDestinataireIntervention
{
    public const INTROUVABLE = 'INTROUVABLE';
    public const AMBIGU = 'ATTRIBUTION_REQUISE';
    public const RESOLU = 'RESOLU';

    /**
     * @return array{etat: string, utilisateur: User|null}
     */
    public function executer(string $permission, ?int $idExterne = null): array
    {
        $permission = trim($permission);

        // Ne pas interpréter des permissions vides / joker / non définies.
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9._:-]{2,159}$/D', $permission)
            || ! \Spatie\Permission\Models\Permission::query()
                ->where('name', $permission)->where('guard_name', 'web')->exists()) {
            return ['etat' => self::INTROUVABLE, 'utilisateur' => null];
        }

        if ($idExterne !== null) {
            // Refuser explicitement : ne jamais retomber sur un autre agent.
            $user = User::query()->active()->find($idExterne);

            return $user !== null && $user->can($permission)
                ? ['etat' => self::RESOLU, 'utilisateur' => $user]
                : ['etat' => self::INTROUVABLE, 'utilisateur' => null];
        }

        // Arrêt à deux : si plus d'un agent est habilité, personne n'est
        // choisi arbitrairement ni notifié en masse.
        $candidats = User::query()->active()->orderBy('id')->cursor()
            ->filter(fn (User $user) => $user->can($permission))
            ->take(2)->values();

        if ($candidats->isEmpty()) {
            return ['etat' => self::INTROUVABLE, 'utilisateur' => null];
        }

        if ($candidats->count() > 1) {
            return ['etat' => self::AMBIGU, 'utilisateur' => null];
        }

        return ['etat' => self::RESOLU, 'utilisateur' => $candidats->first()];
    }
}
