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

    public function mobileMoneyModes(): array
    {
        return [
            ModePaiement::MOBILE_MONEY->value,
            ModePaiement::WAVE->value,
            ModePaiement::ORANGE_MONEY->value,
            ModePaiement::MTN_MONEY->value,
            ModePaiement::MOOV_MONEY->value,
        ];
    }
}