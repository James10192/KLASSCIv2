<?php

/**
 * Exposition future des outils KLASSCI aux clients MCP externes.
 *
 * Défaut fermé : cette configuration n'installe NI endpoint public, NI OAuth,
 * NI jeton d'accès. Elle contrôle uniquement l'adaptateur interne.
 * Le serveur MCP et le fournisseur OAuth devront être revus séparément.
 */
return [
    'read_only' => [
        'enabled' => env('MCP_READ_ONLY_ENABLED', false),
    ],
];
