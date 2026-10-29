<?php

namespace App\Domain\Notifications\Channels;

use App\Domain\Notifications\ChannelDispatch;
use App\Domain\Notifications\EtudiantContact;
use App\Domain\Notifications\PhoneNormalizer;

/**
 * Canal WhatsApp deeplink (wa.me) — manuel et gratuit. Génère un lien
 * `https://wa.me/<E164>?text=<encoded>` que le comptable clique pour ouvrir
 * son WhatsApp avec message pré-rempli.
 *
 * Coût : 0. Quota : illimité. Pas d'appel API.
 *
 * Quand adminKlassci paywall sera ready, un sibling
 * `WhatsAppBusinessApiChannel` sera ajouté avec `isAutomated() = true` pour
 * l'envoi automatique facturé.
 */
class WhatsAppDeeplinkChannel implements NotificationChannelInterface
{
    private const NAME = 'whatsapp_deeplink';
    private const BASE_URL = 'https://wa.me/';

    public function name(): string
    {
        return self::NAME;
    }

    public function isAutomated(): bool
    {
        return false;
    }

    public function dispatch(EtudiantContact $contact, string $message): ChannelDispatch
    {
        $whatsappId = PhoneNormalizer::toWhatsAppId($contact->phone);
        if ($whatsappId === null) {
            return ChannelDispatch::unavailable(
                self::NAME,
                'Numéro de téléphone invalide ou manquant',
            );
        }

        $url = self::BASE_URL . $whatsappId . '?text=' . rawurlencode($message);

        return ChannelDispatch::manual(self::NAME, $url);
    }
}
