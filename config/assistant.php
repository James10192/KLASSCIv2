<?php

/*
 * Assistant IA : fournisseurs, modèles et garde-fous.
 *
 * Aucun modèle n'est écrit dans le code : on déclare ici ce qui existe, et
 * chaque école choisit par son .env ou ses réglages d'instance
 * (`assistant.modele_defaut`, `assistant.modeles_autorises`, qui priment sur
 * les valeurs ci-dessous). Les clés d'API restent dans le .env.
 *
 * Un modèle sans clé n'est jamais proposé ni essayé.
 */

$liste = static fn (?string $valeur): array => array_values(array_filter(array_map('trim', explode(',', (string) $valeur))));

return [

    // Modèle utilisé quand l'utilisateur n'en choisit pas.
    'modele_defaut' => env('ASSISTANT_MODELE', 'claude-haiku'),

    // Modèles proposés à l'école (clés de « modeles », séparées par des virgules). Vide = tous.
    'modeles_autorises' => $liste(env('ASSISTANT_MODELES_AUTORISES', '')),

    // Essayés dans cet ordre quand le modèle choisi tombe ou n'a pas de clé.
    'repli' => $liste(env('ASSISTANT_REPLI', 'claude-haiku,gpt-4o-mini,gemini-flash,mistral-small')),

    'limites' => [
        'tours' => (int) env('ASSISTANT_MAX_TOURS', 4),
        'budget_tokens' => (int) env('ASSISTANT_BUDGET_TOKENS', 60000),
        'delai_secondes' => (int) env('ASSISTANT_DELAI', 90),
        'delai_connexion' => (int) env('ASSISTANT_DELAI_CONNEXION', 15),
        // Nouvelles tentatives sur le même modèle après 429, 5xx ou coupure réseau.
        'tentatives' => (int) env('ASSISTANT_TENTATIVES', 3),
        'pause_ms' => (int) env('ASSISTANT_PAUSE_MS', 600),
        'max_tokens' => (int) env('ASSISTANT_MAX_TOKENS', env('ANTHROPIC_MAX_TOKENS', 2048)),
        'temperature' => (float) env('ASSISTANT_TEMPERATURE', env('ANTHROPIC_TEMPERATURE', 0.2)),
    ],

    // Adaptateur = format d'API. Plusieurs fournisseurs partagent le format OpenAI.
    'adaptateurs' => [
        'anthropic' => App\Domain\Assistant\Fournisseurs\Anthropic::class,
        'openai' => App\Domain\Assistant\Fournisseurs\OpenAiCompatible::class,
        'gemini' => App\Domain\Assistant\Fournisseurs\Gemini::class,
    ],

    'fournisseurs' => [
        'anthropic' => [
            'adaptateur' => 'anthropic',
            'cle' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1/'),
        ],
        'openai' => [
            'adaptateur' => 'openai',
            'cle' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1/'),
        ],
        'mistral' => [
            'adaptateur' => 'openai',
            'cle' => env('MISTRAL_API_KEY'),
            'url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1/'),
        ],
        'deepseek' => [
            'adaptateur' => 'openai',
            'cle' => env('DEEPSEEK_API_KEY'),
            'url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1/'),
        ],
        'openrouter' => [
            'adaptateur' => 'openai',
            'cle' => env('OPENROUTER_API_KEY'),
            'url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1/'),
        ],
        'gemini' => [
            'adaptateur' => 'gemini',
            'cle' => env('GEMINI_API_KEY'),
            'url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/'),
        ],
    ],

    // Registre : clé interne => fournisseur, identifiant chez le fournisseur, libellé, capacités.
    'modeles' => [
        'claude-haiku' => [
            'fournisseur' => 'anthropic',
            'modele' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
            'libelle' => 'Claude Haiku',
            'outils' => true,
            'diffusion' => true,
        ],
        'claude-sonnet' => [
            'fournisseur' => 'anthropic',
            'modele' => env('ANTHROPIC_MODEL_AVANCE', 'claude-sonnet-4-5'),
            'libelle' => 'Claude Sonnet',
            'outils' => true,
            'diffusion' => true,
        ],
        'gpt-4o-mini' => [
            'fournisseur' => 'openai',
            'modele' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'libelle' => 'GPT-4o mini',
            'outils' => true,
            'diffusion' => true,
        ],
        'mistral-small' => [
            'fournisseur' => 'mistral',
            'modele' => env('MISTRAL_MODEL', 'mistral-small-latest'),
            'libelle' => 'Mistral Small',
            'outils' => true,
            'diffusion' => true,
        ],
        'deepseek-chat' => [
            'fournisseur' => 'deepseek',
            'modele' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'libelle' => 'DeepSeek',
            'outils' => true,
            'diffusion' => true,
        ],
        'gemini-flash' => [
            'fournisseur' => 'gemini',
            'modele' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'libelle' => 'Gemini Flash',
            'outils' => true,
            'diffusion' => true,
        ],
    ],
];
