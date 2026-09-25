<?php

/*
 * Assistant IA : fournisseurs, modèles et garde-fous.
 *
 * Aucun modèle n'est écrit dans le code : on déclare ici ce qui existe, et
 * chaque école choisit par son .env ou ses réglages d'instance
 * (`assistant.modele_defaut`, `assistant.modeles_autorises`, qui priment sur
 * les valeurs ci-dessous). Les clés d'API viennent du .env, ou de l'école
 * (écran des réglages, klassci-cli), chiffrées en base par CoffreDesCles.
 *
 * Un modèle sans clé n'est jamais proposé ni essayé.
 */

$liste = static fn (?string $valeur): array => array_values(array_filter(array_map('trim', explode(',', (string) $valeur))));

return [

    // Modèle utilisé quand l'utilisateur n'en choisit pas.
    'modele_defaut' => env('ASSISTANT_MODELE', 'or-gemini-flash'),

    // Modèles proposés à l'école (clés de « modeles », séparées par des virgules). Vide = tous.
    'modeles_autorises' => $liste(env('ASSISTANT_MODELES_AUTORISES', '')),

    // Essayés dans cet ordre quand le modèle choisi tombe ou n'a pas de clé.
    'repli' => $liste(env('ASSISTANT_REPLI', 'or-gemini-flash,or-gpt-4.1-mini,or-gpt-4o-mini,or-claude-haiku,claude-haiku,gpt-4o-mini,gemini-flash,mistral-small')),

    /*
     * Routage automatique : l'utilisateur ne choisit pas de modèle. Chaque échange
     * part sur le palier le moins cher qui suffit, et monte si le résultat trahit
     * un modèle trop faible (App\Domain\Assistant\Routage\Routeur). Paliers du
     * moins cher au plus fort ; dans un palier, les modèles sont essayés dans l'ordre.
     * Ils se revoient à chaque sortie de modèle, par le banc d'essai.
     */
    'paliers' => [
        // Les modèles en accès direct (clé Anthropic, OpenAI, Gemini, Mistral) figurent aussi,
        // à leur prix : une école sans OpenRouter ne doit pas sauter droit au palier avancé.
        'economique' => $liste(env('ASSISTANT_PALIER_ECONOMIQUE', 'or-gemini-flash-lite,or-gpt-4o-mini,or-gpt-4.1-nano,gpt-4o-mini,gemini-flash,mistral-small')),
        'standard' => $liste(env('ASSISTANT_PALIER_STANDARD', 'or-gemini-flash,or-gpt-4.1-mini,or-claude-haiku,claude-haiku,deepseek-chat')),
        'avance' => $liste(env('ASSISTANT_PALIER_AVANCE', 'or-claude-sonnet,claude-sonnet')),
    ],

    /*
     * Budget mensuel de l'école, en francs CFA. Vide ou 0 = sans limite. Le réglage
     * d'instance `assistant.budget_mensuel_fcfa` prime sur le .env. À 100 % l'assistant
     * ne prend plus que le palier économique ; à 120 % il se met en pause jusqu'au mois suivant.
     */
    'budget' => [
        'mensuel_fcfa' => (float) env('ASSISTANT_BUDGET_MENSUEL_FCFA', 0),
        'seuil_economique' => (float) env('ASSISTANT_BUDGET_SEUIL_ECONOMIQUE', 100),
        'seuil_pause' => (float) env('ASSISTANT_BUDGET_SEUIL_PAUSE', 120),
        // Conversion des coûts (facturés en dollars) ; conservée sur chaque ligne de consommation.
        'taux_usd_fcfa' => (float) env('ASSISTANT_TAUX_USD_FCFA', 600),
    ],

    'limites' => [
        'tours' => (int) env('ASSISTANT_MAX_TOURS', 8),
        'budget_tokens' => (int) env('ASSISTANT_BUDGET_TOKENS', 150000),
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
        'openrouter' => [
            'libelle' => 'OpenRouter',
            'adaptateur' => 'openai',
            'cle' => env('OPENROUTER_API_KEY'),
            'url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1/'),
            // Identification de l'application, recommandée par OpenRouter.
            'entetes' => ['HTTP-Referer' => env('APP_URL', ''), 'X-Title' => 'KLASSCI'],
        ],
        'anthropic' => [
            'libelle' => 'Anthropic (Claude)',
            'adaptateur' => 'anthropic',
            'cle' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1/'),
        ],
        'openai' => [
            'libelle' => 'OpenAI',
            'adaptateur' => 'openai',
            'cle' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1/'),
        ],
        'mistral' => [
            'libelle' => 'Mistral',
            'adaptateur' => 'openai',
            'cle' => env('MISTRAL_API_KEY'),
            'url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1/'),
        ],
        'deepseek' => [
            'libelle' => 'DeepSeek',
            'adaptateur' => 'openai',
            'cle' => env('DEEPSEEK_API_KEY'),
            'url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1/'),
        ],
        'gemini' => [
            'libelle' => 'Google Gemini',
            'adaptateur' => 'gemini',
            'cle' => env('GEMINI_API_KEY'),
            'url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/'),
        ],
    ],

    // Registre : clé interne => fournisseur, identifiant chez le fournisseur, libellé, capacités.
    // `tarif` : dollars par million de jetons (entrée, sortie, entrée lue en cache), relevés
    // sur l'API OpenRouter le 25 septembre 2026. Ne sert qu'aux fournisseurs qui ne donnent
    // pas le coût réel : OpenRouter le renvoie à chaque appel, c'est lui qui est enregistré.
    'modeles' => [
        // Via OpenRouter : une seule clé, des modèles économiques choisis pour
        // l'appel d'outils en français. Identifiants surchargeables par le .env.
        'or-gpt-4o-mini' => [
            'fournisseur' => 'openrouter',
            'modele' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
            'libelle' => 'GPT-4o mini (OpenRouter)',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 0.15, 'sortie' => 0.6, 'cache' => 0.075],
        ],
        'or-gemini-flash-lite' => [
            'fournisseur' => 'openrouter',
            'modele' => env('OPENROUTER_MODEL_GEMINI', 'google/gemini-3.1-flash-lite'),
            'libelle' => 'Gemini Flash Lite (OpenRouter)',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 0.25, 'sortie' => 1.5, 'cache' => 0.025],
        ],
        'or-deepseek' => [
            'fournisseur' => 'openrouter',
            'modele' => env('OPENROUTER_MODEL_DEEPSEEK', 'deepseek/deepseek-v3.2'),
            'libelle' => 'DeepSeek V3.2 (OpenRouter)',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 0.269, 'sortie' => 0.4, 'cache' => 0.1345],
        ],
        // Comparés le 25 septembre 2026 sur presentation (6 questions réelles, 30 réponses,
        // aucune erreur) : Gemini Flash donne les analyses les plus justes et exploitables,
        // d'où le défaut ; GPT-4.1 mini est le repli le plus sûr ; DeepSeek recopiait
        // les exemples du prompt, Claude Haiku annonçait ses recherches avant d'agir.
        'or-gemini-flash' => [
            'fournisseur' => 'openrouter',
            'modele' => env('OPENROUTER_MODEL_GEMINI_FLASH', 'google/gemini-3.8-flash'),
            'libelle' => 'Gemini Flash (OpenRouter)',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 0.75, 'sortie' => 3.75, 'cache' => 0.075],
        ],
        'or-gpt-4.1-mini' => [
            'fournisseur' => 'openrouter',
            'modele' => env('OPENROUTER_MODEL_GPT41', 'openai/gpt-4.1-mini'),
            'libelle' => 'GPT-4.1 mini (OpenRouter)',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 0.4, 'sortie' => 1.6, 'cache' => 0.1],
        ],
        'or-claude-haiku' => [
            'fournisseur' => 'openrouter',
            'modele' => env('OPENROUTER_MODEL_HAIKU', 'anthropic/claude-haiku-4.5'),
            'libelle' => 'Claude Haiku 4.5 (OpenRouter)',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 1.0, 'sortie' => 5.0, 'cache' => 0.1],
        ],
        'or-gpt-4.1-nano' => [
            'fournisseur' => 'openrouter',
            'modele' => env('OPENROUTER_MODEL_GPT41_NANO', 'openai/gpt-4.1-nano'),
            'libelle' => 'GPT-4.1 nano (OpenRouter)',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 0.1, 'sortie' => 0.4, 'cache' => 0.025],
        ],
        'or-claude-sonnet' => [
            'fournisseur' => 'openrouter',
            'modele' => env('OPENROUTER_MODEL_SONNET', 'anthropic/claude-sonnet-4.5'),
            'libelle' => 'Claude Sonnet 4.5 (OpenRouter)',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 3.0, 'sortie' => 15.0, 'cache' => 0.3],
        ],
        'claude-haiku' => [
            'fournisseur' => 'anthropic',
            'modele' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
            'libelle' => 'Claude Haiku',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 1.0, 'sortie' => 5.0, 'cache' => 0.1],
        ],
        'claude-sonnet' => [
            'fournisseur' => 'anthropic',
            'modele' => env('ANTHROPIC_MODEL_AVANCE', 'claude-sonnet-4-5'),
            'libelle' => 'Claude Sonnet',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 3.0, 'sortie' => 15.0, 'cache' => 0.3],
        ],
        'gpt-4o-mini' => [
            'fournisseur' => 'openai',
            'modele' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'libelle' => 'GPT-4o mini',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 0.15, 'sortie' => 0.6, 'cache' => 0.075],
        ],
        'mistral-small' => [
            'fournisseur' => 'mistral',
            'modele' => env('MISTRAL_MODEL', 'mistral-small-latest'),
            'libelle' => 'Mistral Small',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 0.1, 'sortie' => 0.3],
        ],
        'deepseek-chat' => [
            'fournisseur' => 'deepseek',
            'modele' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'libelle' => 'DeepSeek',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 0.27, 'sortie' => 1.1],
        ],
        'gemini-flash' => [
            'fournisseur' => 'gemini',
            'modele' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'libelle' => 'Gemini Flash',
            'outils' => true,
            'diffusion' => true,
            'tarif' => ['entree' => 0.1, 'sortie' => 0.4],
        ],
    ],
];
