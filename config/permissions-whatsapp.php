<?php

/**
 * Permissions WhatsApp à fusionner dans config/permissions.php (Phase 7 + Phase 18 sécu).
 *
 * Note Plan v4 : ces permissions devraient être MERGE dans le registry principal
 * config/permissions.php clé `permissions`. Ce fichier sert de séparation logique
 * pour le PR Phase 7 — à intégrer manuellement ou via un loader composite.
 *
 * Convention canonique KLASSCI : domaine.action[.qualifier] snake_case.
 * Voir .claude/rules/permissions.md
 */
return [
    // Module toggle (couche abonnement instance)
    'module.whatsapp.access' => [
        'label' => 'Accès au module WhatsApp',
        'description' => 'Active toutes les fonctionnalités WhatsApp pour ce tenant',
        'group' => 'Modules',
        'icon' => 'fa-toggle-on',
    ],

    // Inbox chat 2-way (Phase 7)
    'whatsapp.inbox.view' => [
        'label' => "Voir l'inbox WhatsApp",
        'description' => 'Consulter les messages entrants des parents via WhatsApp',
        'group' => 'WhatsApp',
        'icon' => 'fa-inbox',
    ],
    'whatsapp.inbox.reply' => [
        'label' => 'Répondre aux messages WhatsApp',
        'description' => 'Envoyer une réponse manuelle à un parent depuis inbox',
        'group' => 'WhatsApp',
        'icon' => 'fa-reply',
    ],
    'whatsapp.inbox.assign' => [
        'label' => 'Assigner un message à un agent',
        'description' => "Réaffecter un message orphelin à un membre de l'équipe",
        'group' => 'WhatsApp',
        'icon' => 'fa-user-tag',
    ],
    'whatsapp.inbox.archive' => [
        'label' => 'Archiver un message',
        'description' => 'Marquer un thread comme traité et le déplacer en archive',
        'group' => 'WhatsApp',
        'icon' => 'fa-archive',
    ],

    // Templates Meta (Phase 2 UI Filament adminKlassci consume aussi)
    'whatsapp.templates.view' => [
        'label' => 'Voir les templates WhatsApp',
        'group' => 'WhatsApp',
        'icon' => 'fa-list-alt',
    ],
    'whatsapp.templates.manage' => [
        'label' => 'Gérer les templates WhatsApp (sync Meta)',
        'group' => 'WhatsApp',
        'icon' => 'fa-cog',
    ],

    // Cost & monitoring (Phase 16)
    'whatsapp.cost.view' => [
        'label' => 'Voir le coût WhatsApp du tenant',
        'group' => 'WhatsApp',
        'icon' => 'fa-coins',
    ],
    'whatsapp.metrics.view' => [
        'label' => 'Voir les statistiques WhatsApp',
        'group' => 'WhatsApp',
        'icon' => 'fa-chart-line',
    ],

    // Configuration (Phase 1 superAdmin uniquement par défaut)
    'whatsapp.config.view' => [
        'label' => 'Voir la configuration WhatsApp du tenant',
        'group' => 'WhatsApp',
        'icon' => 'fa-eye',
    ],
    'whatsapp.config.edit' => [
        'label' => 'Modifier la configuration WhatsApp',
        'description' => 'Réservé superAdmin — éditer credentials Meta ne devrait être qu\'en cas exceptionnel',
        'group' => 'WhatsApp',
        'icon' => 'fa-key',
    ],

    // Chatbot IA (Phase 10)
    'whatsapp.chatbot.toggle' => [
        'label' => 'Activer/désactiver le chatbot IA WhatsApp',
        'group' => 'WhatsApp',
        'icon' => 'fa-robot',
    ],
    'whatsapp.chatbot.review' => [
        'label' => 'Reviewer les réponses IA avant envoi (modération)',
        'group' => 'WhatsApp',
        'icon' => 'fa-eye-slash',
    ],

    // Send manual (envois manuels — sécurité Phase 18)
    'whatsapp.send.manual' => [
        'label' => 'Envoyer un message WhatsApp manuel ad-hoc',
        'description' => 'Permet aux comptables/secrétaires d\'envoyer une notification one-off',
        'group' => 'WhatsApp',
        'icon' => 'fa-paper-plane',
    ],

    // Webhook config (réservé serviceTechnique)
    'whatsapp.webhook.manage' => [
        'label' => 'Gérer la configuration webhook Meta',
        'description' => 'Réservé Service Technique uniquement',
        'group' => 'WhatsApp',
        'icon' => 'fa-network-wired',
    ],
];
