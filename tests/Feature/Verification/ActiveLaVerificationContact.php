<?php

namespace Tests\Feature\Verification;

use App\Models\Setting;
use App\Services\TenantScolariteSettings;
use Illuminate\Support\Facades\Cache;

/** Pose le reglage d'instance « Verifier le contact des demandes en ligne ». */
trait ActiveLaVerificationContact
{
    protected function reglerVerificationContact(bool $active): void
    {
        Setting::updateOrCreate(['key' => TenantScolariteSettings::VERIFICATION_CONTACT], [
            'value' => $active ? '1' : '0',
            'type' => 'boolean',
            'group' => 'scolarite',
            'is_active' => true,
        ]);
        // Setting::get met la valeur en cache : sans cela le reglage precedent resterait lu.
        Cache::flush();
    }
}
