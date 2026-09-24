<?php

namespace App\Services;

use App\Enums\ModePaiement;
use App\Models\User;

class MobileMoneyPaymentGuard
{
    /** Les permissions qui ouvrent l'écran d'encaissement, pour au moins un mode. */
    public const PERMISSIONS_ENCAISSEMENT = ['paiements.create', 'paiements.create.non_cash', 'paiements.create.mobile_money'];

    public function canCreate(User $user): bool
    {
        return $this->allowedModes($user) !== [];
    }

    /**
     * - `paiements.create` : tous les modes (la caisse, qui tient le tiroir) ;
     * - `paiements.create.non_cash` : tout sauf les espèces ;
     * - `paiements.create.mobile_money` : les portefeuilles mobiles seulement.
     *
     * @return list<string>
     */
    public function allowedModes(User $user): array
    {
        if ($user->can('paiements.create')) {
            return ModePaiement::values();
        }

        if ($user->can('paiements.create.non_cash')) {
            return array_values(array_map(
                fn (ModePaiement $mode) => $mode->value,
                array_filter(ModePaiement::cases(), fn (ModePaiement $mode) => ! $mode->isDrawer()),
            ));
        }

        if ($user->can('paiements.create.mobile_money')) {
            return $this->mobileMoneyModes();
        }

        return [];
    }

    public function allowsMode(User $user, ?string $mode): bool
    {
        $canonical = ModePaiement::fromLegacy($mode)?->value;
        if ($canonical === null) {
            return false;
        }

        return in_array($canonical, $this->allowedModes($user), true);
    }

    /**
     * Dérivé de l'enum, et non recopié : cette liste avait déjà divergé (ni
     * Djamo ni Celtiis Cash), et un mode absent d'ici est REFUSÉ au caissier
     * qui n'a que la permission « mobile money ».
     *
     * @return list<string>
     */
    public function mobileMoneyModes(): array
    {
        return array_values(array_map(
            fn (ModePaiement $mode) => $mode->value,
            array_filter(ModePaiement::cases(), fn (ModePaiement $mode) => $mode->estMobile()),
        ));
    }
}