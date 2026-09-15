<?php

namespace App\Services;

use App\Enums\ModePaiement;
use App\Models\User;

class MobileMoneyPaymentGuard
{
    public function canCreate(User $user): bool
    {
        return $user->can('paiements.create') || $user->can('paiements.create.mobile_money');
    }

    public function allowedModes(User $user): array
    {
        if ($user->can('paiements.create')) {
            return ModePaiement::values();
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