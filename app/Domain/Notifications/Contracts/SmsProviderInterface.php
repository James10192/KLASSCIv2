<?php

namespace App\Domain\Notifications\Contracts;

/**
 * Contract SMS provider pour Phase 9 Plan v4 (module SMS multi-providers).
 *
 * Permet d'abstraire les différents providers SMS Côte d'Ivoire / Afrique :
 *  - Orange CI (OAuth2, contrat client requis, ~6-7 FCFA/SMS)
 *  - Beem Africa (REST API, ~5-7 FCFA/SMS, populaire Afrique)
 *  - SMS.to (REST API, ~7-8 FCFA/SMS, global)
 *  - MTN MoMo Messaging (futur)
 *
 * Chaque implémentation est sélectionnée via setting tenant `sms.provider`.
 * Fallback en cascade si provider down (configurable settings).
 */
interface SmsProviderInterface
{
    /**
     * Envoie un SMS texte simple.
     *
     * @param string $phoneNumber Format E.164 (+225XXXXXXXXX)
     * @param string $message Texte SMS (max 160 chars pour 1 SMS standard)
     * @return array{success: bool, message_id?: string, error?: string, cost_fcfa?: float}
     */
    public function send(string $phoneNumber, string $message): array;

    /**
     * Identifiant unique du provider (orange, beem, smsto, ...).
     */
    public function name(): string;

    /**
     * Vérifie si le provider est configuré et disponible (credentials valides).
     */
    public function isAvailable(): bool;

    /**
     * Coût indicatif par message en FCFA (pour cost ledger).
     */
    public function costPerMessageFcfa(): float;
}
