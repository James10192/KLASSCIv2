<?php

/**
 * Nanan Connect : tous les accès externes sont désactivés par défaut.
 *
 * L'URL MCP et les JWT OAuth sont validés par la passerelle externe.
 * Laravel vérifie indépendamment chaque requête signée, organisation,
 * liaison de compte et permission métier.
 */
return [
    'read_only' => [
        'enabled' => env('MCP_READ_ONLY_ENABLED', false),
    ],
    'bridge' => [
        'enabled' => env('MCP_BRIDGE_ENABLED', false),
        // Secret unique par établissement, au moins 32 caractères aléatoires.
        'secret' => env('MCP_BRIDGE_SECRET', ''),
        // Identifiant immuable Auth0 Organization associé à CETTE instance.
        'organization' => env('MCP_TENANT_ORGANIZATION', ''),
        // Issuer EXACT tel qu'il figure dans le JWT validé par la passerelle.
        'issuer' => env('MCP_OAUTH_ISSUER', ''),
    ],
];
