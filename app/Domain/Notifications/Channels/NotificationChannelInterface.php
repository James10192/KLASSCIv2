<?php

namespace App\Domain\Notifications\Channels;

use App\Domain\Notifications\ChannelDispatch;
use App\Domain\Notifications\EtudiantContact;

/**
 * Contrat d'un canal de notification de relance. Strategy pattern ouvert pour
 * extensions futures (WhatsApp Business API, SMS gateway, push, etc.) sans
 * modification du Predictor / Controller / Logger.
 *
 * Les implémentations doivent être déterministes (même input = même output).
 */
interface NotificationChannelInterface
{
    /**
     * Identifiant snake_case du canal — utilisé en colonne `canal` table relances
     * et en clé settings.
     */
    public function name(): string;

    /**
     * True si le canal envoie automatiquement (Business API, SMS gateway).
     * False si le canal génère un lien que l'utilisateur clique (deeplink wa.me, mailto).
     */
    public function isAutomated(): bool;

    /**
     * Construit (mode manuel) ou exécute (mode automatique) l'envoi de la
     * notification. Le caller persiste le résultat via RelanceActionLogger.
     */
    public function dispatch(EtudiantContact $contact, string $message): ChannelDispatch;
}
