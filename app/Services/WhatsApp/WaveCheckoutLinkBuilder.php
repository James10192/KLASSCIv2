<?php

namespace App\Services\WhatsApp;

use App\Helpers\SettingsHelper;

/**
 * Builder de liens de paiement Wave CI à intégrer dans les templates WhatsApp
 * (Phase 12 Plan v4) — frictionless paiement pour les relances.
 *
 * Pattern : la relance WhatsApp utility inclut un lien wave.com/pay?... qui ouvre
 * directement l'app Wave native sur le smartphone du parent → paiement en 1 tap.
 *
 * Settings tenant requis (table esbtp_settings) :
 *   - wave.merchant_id : ID marchand Wave de l'école
 *   - wave.enabled : toggle module
 *   - wave.callback_url : webhook KLASSCI pour confirmation paiement
 *
 * @see https://docs.wave.com/business (à confirmer URL exacte CI 2026)
 */
class WaveCheckoutLinkBuilder
{
    /**
     * Construit un lien de paiement Wave CI pour un montant + référence donnés.
     *
     * Retourne null si module Wave désactivé pour le tenant courant.
     *
     * @param int $amountXof Montant en FCFA (Wave accepte entier)
     * @param string $reference Référence unique paiement (id inscription, relance, etc.)
     * @param string|null $description Libellé visible parent dans l'app Wave
     */
    public function build(int $amountXof, string $reference, ?string $description = null): ?string
    {
        if (! SettingsHelper::get('wave.enabled', false)) {
            return null;
        }

        $merchantId = SettingsHelper::get('wave.merchant_id');

        if (empty($merchantId)) {
            return null;
        }

        $params = [
            'amount' => $amountXof,
            'currency' => 'XOF',
            'ref' => $reference,
            'merchant_id' => $merchantId,
        ];

        if ($description) {
            $params['description'] = $description;
        }

        return SettingsHelper::get('wave.checkout_url', 'https://pay.wave.com/c')
            . '?' . http_build_query($params);
    }

    /**
     * Vérifie si le module Wave est configuré pour ce tenant.
     */
    public function isEnabled(): bool
    {
        return (bool) SettingsHelper::get('wave.enabled', false)
            && ! empty(SettingsHelper::get('wave.merchant_id'));
    }
}
