<?php

namespace App\Services\Security;

use App\Helpers\SettingsHelper;
use App\Models\User;

final class SeparationOfDutiesService
{
    public function violation(string $rule, ?int $previousActorId, ?User $actor): ?string
    {
        if (! $this->enabled($rule) || ! $actor || ! $previousActorId) {
            return null;
        }

        if ((int) $previousActorId !== (int) $actor->id) {
            return null;
        }

        if ($actor->can((string) config('sod.bypass_permission', 'sod.bypass'))) {
            return null;
        }

        return (string) config("sod.rules.{$rule}.message", 'Separation des devoirs requise pour cette action.');
    }

    public function enabled(string $rule): bool
    {
        $default = (bool) config("sod.rules.{$rule}.enabled", false);
        $setting = config("sod.rules.{$rule}.setting");

        if (! is_string($setting) || $setting === '') {
            return $default;
        }

        try {
            $value = SettingsHelper::get($setting, $default ? '1' : '0');
        } catch (\Throwable) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}