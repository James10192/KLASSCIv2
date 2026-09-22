<?php

namespace App\Domain\Support\Services;

use App\Helpers\SettingsHelper;
use App\Services\Care\ClientMasterSupport;

/**
 * KLASSCI Care est-il ouvert sur cette instance, et pour quoi ?
 *
 * Deux clefs, et les deux doivent etre tournees :
 *  - le Master (tenant_features) decide du deploiement, ecole par ecole ;
 *  - le reglage local `support.widget.enabled` est l'interrupteur de l'ecole
 *    et de l'exploitation : il coupe tout sans attendre le Master.
 * Sans MASTER_SUPPORT_TOKEN, rien ne s'affiche et le Master n'est pas appele.
 */
class DisponibiliteSupport
{
    public function __construct(private readonly ClientMasterSupport $master)
    {
    }

    public function signalement(): bool
    {
        return $this->interrupteurLocal() && $this->master->fonctionnaliteActive('support_widget');
    }

    public function suivi(): bool
    {
        return $this->interrupteurLocal() && $this->master->fonctionnaliteActive('support_customer_portal');
    }

    private function interrupteurLocal(): bool
    {
        if (! $this->master->estConfigure()) {
            return false;
        }

        return filter_var(SettingsHelper::get('support.widget.enabled', true), FILTER_VALIDATE_BOOLEAN);
    }
}
