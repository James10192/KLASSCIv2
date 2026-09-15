<?php

namespace App\Services\Security;

final class AttributionSensible
{
    public const PERMISSIONS = [
        'paiements.validate',
        'lmd.jury.publish',
        'paywall.manage',
        'sod.bypass',
    ];

    /** @param  array<int, string>  $permissions */
    public static function dans(array $permissions): array
    {
        return array_values(array_intersect($permissions, self::PERMISSIONS));
    }

    /** @param  array<int, string>  $permissions */
    public static function messageSiAutoAttribution(bool $memePersonne, array $permissions): ?string
    {
        if (! $memePersonne) {
            return null;
        }

        if (self::dans($permissions) === []) {
            return null;
        }

        return 'Vous ne pouvez pas vous attribuer le droit de valider un paiement, de publier un jury, de gérer l\'abonnement ou de contourner la séparation des devoirs.';
    }
}
