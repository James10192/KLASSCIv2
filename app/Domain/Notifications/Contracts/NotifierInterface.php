<?php

namespace App\Domain\Notifications\Contracts;

/**
 * Contract des notifiers métier (strangler fig de NotificationService).
 *
 * Chaque notifier expose des méthodes d'événement métier (ex: relanceCreee, paiementValide)
 * et orchestre les canaux (email, WhatsApp, SMS, in-app) via les services dédiés.
 *
 * Les notifiers sont enregistrés dans NotificationDomainServiceProvider et résolus
 * via le container Laravel pour permettre injection de dépendances + remplacement
 * en test.
 */
interface NotifierInterface
{
    /**
     * Identifiant unique du domaine de notifications gérées par ce notifier.
     *
     * Utilisé pour le routing depuis NotificationDispatcher et le tagging
     * dans les logs d'audit (parent_notification_logs.notification_type prefix).
     */
    public function domain(): string;
}
