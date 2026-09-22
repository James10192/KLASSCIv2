<?php

namespace App\Domain\Support\Services;

use App\Helpers\SettingsHelper;
use App\Services\Care\ClientMasterSupport;

/**
 * KLASSCI Care est-il ouvert sur cette instance, et pour quoi ?
 *
 * Deux clefs, et les deux doivent etre tournees :
 *  - le Master (tenant_features) decide du deploiement, ecole par ecole ;
 *  - le reglage local `support.widget.enabled` est un interrupteur
 *    d'EXPLOITATION : il coupe tout sans attendre le Master. Aucun ecran ne
 *    l'expose, il se pose en base ou par le CLI.
 * Sans MASTER_SUPPORT_TOKEN, rien ne s'affiche et le Master n'est pas appele.
 *
 * Lie en scoped() : le layout le consulte a plusieurs endroits d'une meme page.
 * L'interrupteur local est lu une fois par requete ; les fonctionnalites du
 * Master viennent du cache de ClientMasterSupport, relu a chaque appel.
 */
class DisponibiliteSupport
{
    private ?bool $interrupteur = null;

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
        return $this->interrupteur ??= $this->master->estConfigure()
            && filter_var(SettingsHelper::get('support.widget.enabled', true), FILTER_VALIDATE_BOOLEAN);
    }
}
