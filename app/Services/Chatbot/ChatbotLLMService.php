<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\ChatbotKnowledgeBase;
use App\Models\ChatbotSystemPrompt;
use App\Models\ChatbotUserPreference;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotLLMService
{
    protected string $model;
    protected ?string $fallbackModel;

    /**
     * Nombre maximal de messages de contexte envoyés au LLM.
     */
    protected int $contextWindow = 10;

    public function __construct()
    {
        $this->model = config('groq.model', 'llama-3.1-70b-versatile');
        $this->fallbackModel = config('groq.fallback_model');
    }

    /**
     * Décider de l'action à effectuer via Gemini.
     *
     * @return array{
     *     intent?: string,
     *     filters?: array<string,mixed>,
     *     display?: string,
     *     response_text?: string|null,
     *     limit?: int|null,
     *     follow_up?: array<int,string>|null
     * }
     */
    public function decide(ChatbotConversation $conversation, string $userMessage): array
    {
        if (! config('groq.api_key')) {
            Log::warning('ChatbotLLMService: GROQ_API_KEY manquant, retour heuristique.');
            return [];
        }

        $knowledge = $this->buildKnowledgeSnapshot();
        $history = $this->buildHistory($conversation);
        $contextSummary = $this->buildContextSummary($conversation);

        $prompt = $this->buildPrompt($userMessage, $knowledge, $contextSummary);

        try {
            Log::info('ChatbotLLMService: appel Groq', [
                'conversation_id' => $conversation->id,
                'user_message' => $userMessage,
            ]);

            $messages = array_merge([
                ['role' => 'system', 'content' => $this->systemInstruction()],
            ], $history, [
                ['role' => 'user', 'content' => $prompt],
            ]);

            $responseText = $this->callGroq($messages, $this->model);

            if (!$responseText && $this->fallbackModel) {
                Log::warning('ChatbotLLMService: fallback Groq déclenché', [
                    'model' => $this->fallbackModel,
                ]);
                $responseText = $this->callGroq($messages, $this->fallbackModel);
            }

            if (!$responseText) {
                return [];
            }

            Log::debug('ChatbotLLMService: réponse brute Groq', [
                'text' => $responseText,
            ]);

            $decision = $this->parseDecision($responseText);

            if (!empty($decision['filters']) && !is_array($decision['filters'])) {
                $decision['filters'] = [];
            }

            if (!empty($decision['follow_up']) && !is_array($decision['follow_up'])) {
                $decision['follow_up'] = [];
            }

            if (isset($decision['limit']) && $decision['limit'] !== null) {
                $decision['limit'] = (int) $decision['limit'];
            }

            return $decision;
        } catch (\Throwable $e) {
            Log::error('ChatbotLLMService: Groq call failed', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Construire la liste des connaissances disponibles.
     */
    protected function buildKnowledgeSnapshot(): array
    {
        return ChatbotKnowledgeBase::query()
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get()
            ->map(function (ChatbotKnowledgeBase $knowledge) {
                return [
                    'intent' => $knowledge->intent,
                    'route' => $knowledge->route,
                    'model' => $knowledge->model,
                    'table' => $knowledge->table_name,
                    'filters' => array_keys((array) $knowledge->columns_mapping),
                    'deep_link' => $knowledge->deep_link_pattern,
                ];
            })
            ->toArray();
    }

    /**
     * Construire l'historique de conversation envoyé au LLM.
     *
     * @return array<int,array{role:string,content:string}>
     */
    protected function buildHistory(ChatbotConversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->take($this->contextWindow)
            ->get();

        return $messages->map(function ($message) {
            $role = $message->role === 'assistant' ? 'assistant' : 'user';

            return [
                'role' => $role,
                'content' => $message->content,
            ];
        })->toArray();
    }

    /**
     * Résumé du contexte stocké.
     */
    protected function buildContextSummary(ChatbotConversation $conversation): array
    {
        $context = $conversation->context ?? [];

        return [
            'last_intent' => Arr::get($context, 'last_intent'),
            'last_filters' => Arr::get($context, 'last_filters', []),
            'last_display' => Arr::get($context, 'last_display'),
            'last_page_path' => Arr::get($context, 'last_page_path'),
            'last_page_title' => Arr::get($context, 'last_page_title'),
            'user_preferences' => Arr::get($context, 'user_preferences', []),
        ];
    }

    protected function buildPrompt(string $userMessage, array $knowledge, array $contextSummary): string
    {
        $knowledgeLines = collect($knowledge)->map(function (array $entry) {
            $filters = empty($entry['filters']) ? '—' : implode(', ', $entry['filters']);

            return sprintf(
                "- intent: %s | modèle: %s | table: %s | route: %s | filtres: %s",
                $entry['intent'],
                $entry['model'] ?? 'n/a',
                $entry['table'] ?? 'n/a',
                $entry['route'] ?? 'n/a',
                $filters
            );
        })->implode("\n");

        $contextLine = sprintf(
            "Contexte précédent : intent=%s | display=%s | filtres=%s",
            $contextSummary['last_intent'] ?? 'aucun',
            $contextSummary['last_display'] ?? 'aucun',
            empty($contextSummary['last_filters']) ? 'aucun' : json_encode($contextSummary['last_filters'], JSON_UNESCAPED_UNICODE)
        );

        $pageContext = null;
        if (!empty($contextSummary['last_page_title'])) {
            $pageContext = 'Page actuelle : ' . $contextSummary['last_page_title'];
        } elseif (!empty($contextSummary['last_page_path'])) {
            $pageContext = 'Page actuelle : ' . $contextSummary['last_page_path'];
        }

        $preferencesContext = null;
        if (!empty($contextSummary['user_preferences'])) {
            $preferencesContext = 'Préférences utilisateur : ' . json_encode($contextSummary['user_preferences'], JSON_UNESCAPED_UNICODE);
        }

        return <<<PROMPT
Historique conversation (résumé) :
{$contextLine}

{$pageContext}

{$preferencesContext}

Intents et sources disponibles :
{$knowledgeLines}

Utilisateur :
{$userMessage}
PROMPT;
    }

    protected function systemInstruction(): string
    {
        $domainContext = $this->getDomainContext();
        $domainSection = $domainContext ? "\nContexte metier KLASSCI :\n{$domainContext}\n" : '';

        return <<<PROMPT
Tu es l'assistant interne KLASSCI. Tu dois répondre en français et uniquement avec un JSON valide respectant strictement ce schéma :
{
  "intent": "string ou null",
  "filters": { ... },
  "display": "table" | "cards" | "kpi" | "text" | "checklist" | null,
  "response_text": "string ou null",
  "limit": nombre entier ou null,
  "follow_up": ["..."] ou []
}

Règles :
- `intent` doit correspondre à l'intent le plus approprié même s'il n'existe pas encore dans les intents disponibles.
  Exemples d'intents valides : get_paiements, get_inscriptions, get_etudiants, get_frais, get_classes, get_categories_frais, get_tarifs, setup_guide
  Si la question porte sur les frais de scolarité ou tarifs, utilise "get_frais" ou "get_categories_frais".
  Si la question porte sur les paiements effectués, utilise "get_paiements".
  Utilise "setup_guide" uniquement si l'utilisateur demande explicitement un guide, des étapes ou une checklist.
  Mets null uniquement si la question est purement conversationnelle (salutation, aide, etc.).

- `filters` est un objet JSON avec les filtres utiles. UTILISE TOUJOURS CES NOMS DE CLÉS STANDARDISÉS :
  * Pour les catégories de frais : "categorie_frais" (exemples: "inscription", "scolarité", "cantine", "transport")
  * Pour le type d'affectation : "type_affectation" (exemples: "affectés", "réaffectés", "non affectés")
  * Pour la filière : "filiere" (exemple: "BTS Bâtiment", "Génie Civil")
  * Pour le niveau : "niveau" (exemples: "Première Année", "Deuxième Année", "L3")
  * Pour le statut général : "status" (exemples: "en_attente", "validé", "rejeté", "active")
  * Pour la classe : "classe" (exemple: "L3 GC - 2024/2025")
  * Pour l'année universitaire : "annee_universitaire" (exemple: "2024/2025")
  * Pour les dates : "month", "date_debut", "date_fin"
  * Pour la recherche textuelle : "search"
  * Pour les relations manquantes : "without_paiements" (true/false), "without_inscriptions" (true/false)
  * Pour les guides de mise en route : "setup_scope" ("academique", "financier", "pedagogie", "global"), "full_guide" (true/false)

EXEMPLES D'EXTRACTION DE FILTRES :

Exemple 1 - Question : "Montre-moi les frais de scolarité pour Première Année BTS Bâtiment"
Réponse correcte :
{
  "intent": "get_frais",
  "filters": {
    "categorie_frais": "scolarité",
    "filiere": "BTS Bâtiment",
    "niveau": "Première Année"
  },
  "display": "table",
  "response_text": "Voici les frais de scolarité pour Première Année BTS Bâtiment :",
  "limit": null,
  "follow_up": ["Frais d'inscription ?", "Deuxième Année ?", "Autres filières ?"]
}

Exemple 2 - Question : "Quels sont les frais d'inscription pour les non affectés ?"
Réponse correcte :
{
  "intent": "get_frais",
  "filters": {
    "categorie_frais": "inscription",
    "type_affectation": "non affectés"
  },
  "display": "table",
  "response_text": "Voici les frais d'inscription pour les étudiants non affectés :",
  "limit": null,
  "follow_up": ["Frais de scolarité ?", "Affectés ?", "Réaffectés ?"]
}

Exemple 3 - Question : "Frais de scolarité affectés Première Année BTS Bâtiment"
Réponse correcte :
{
  "intent": "get_frais",
  "filters": {
    "categorie_frais": "scolarité",
    "type_affectation": "affectés",
    "filiere": "BTS Bâtiment",
    "niveau": "Première Année"
  },
  "display": "table",
  "response_text": "Voici les frais de scolarité pour les affectés en Première Année BTS Bâtiment :",
  "limit": null,
  "follow_up": ["Réaffectés ?", "Non affectés ?", "Deuxième Année ?"]
}

Exemple 4 - Question : "Paiements en attente de ce mois"
Réponse correcte :
{
  "intent": "get_paiements",
  "filters": {
    "status": "en_attente",
    "month": "current"
  },
  "display": "table",
  "response_text": "Voici les paiements en attente de ce mois :",
  "limit": null,
  "follow_up": ["Valider tout ?", "Septembre ?", "Validés ?"]
}

Exemple 5 - Question : "Les inscriptions sans paiements s'il te plait ?"
Réponse correcte :
{
  "intent": "get_inscriptions",
  "filters": {
    "without_paiements": true
  },
  "display": "cards",
  "response_text": "Voici les inscriptions sans aucun paiement :",
  "limit": null,
  "follow_up": ["Avec paiements ?", "En attente ?", "Validées ?"]
}

- `display` = "cards" pour les inscriptions, "table" pour paiements/étudiants/frais, "text" si réponse purement conversationnelle.
- Utilise "checklist" pour les réponses de type guide d'installation / mise en route.

IMPORTANT : respecter les préférences utilisateur si elles sont présentes dans le contexte :
- response_style : court | standard | detaille (adapter la longueur)
- response_tone : direct | pedagogique | chaleureux (adapter le ton)
- clarification_mode : auto | always | never
  * always : poser une question de clarification avant une réponse longue
  * never : répondre directement, sans question

Progressive disclosure : ne propose pas de longues listes d'étapes. Par défaut, donne 1 à 3 étapes maximum.
Si l'utilisateur demande explicitement "tout le guide" ou "guide complet", utilise full_guide=true.

{$domainSection}

- `response_text` peut contenir un court texte introductif ou null.

- `limit` borne le nombre de résultats (ex: 5) ou null.

- `follow_up` est un tableau de suggestions courtes (3-4 mots max par suggestion, peut être vide).

- Ne renvoie aucun texte hors JSON, pas de commentaire, pas de Markdown.

- Utilise le contexte précédent s'il existe (intent + filtres) pour interpréter les pronoms ("ces", "les mêmes", etc.).

IMPORTANT:
1. Choisis TOUJOURS l'intent qui correspond exactement à ce que demande l'utilisateur, même si cet intent n'apparaît pas dans la liste des intents disponibles. Le système saura explorer le code pour trouver comment récupérer les données.
2. RESPECTE STRICTEMENT LES NOMS DE CLÉS STANDARDISÉS dans `filters`. N'utilise JAMAIS d'autres noms comme "affectation", "categorie", "type", etc.
3. Pour le type d'affectation, extrais TOUJOURS la valeur complète : "affectés", "réaffectés", ou "non affectés" (jamais juste "non").
PROMPT;
    }

    /**
     * @return array<string,mixed>
     */
    protected function parseDecision(string $text): array
    {
        try {
            // Nettoyer les backticks markdown si présents
            $text = preg_replace('/^```json\s*/m', '', $text);
            $text = preg_replace('/```\s*$/m', '', $text);
            $text = trim($text);

            // Parser le JSON
            $decoded = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode error: ' . json_last_error_msg());
            }
        } catch (\Throwable $exception) {
            Log::warning('ChatbotLLMService: réponse non JSON', [
                'error' => $exception->getMessage(),
                'raw_text' => substr($text ?? '', 0, 200),
            ]);
            return [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        return [
            'intent' => $decoded['intent'] ?? null,
            'filters' => $decoded['filters'] ?? [],
            'display' => $decoded['display'] ?? null,
            'response_text' => $decoded['response_text'] ?? null,
            'limit' => $decoded['limit'] ?? null,
            'follow_up' => $decoded['follow_up'] ?? null,
        ];
    }

    public function generateHelpPrompt(
        ChatbotConversation $conversation,
        string $userMessage,
        ?string $contextLine,
        array $suggestions,
        ?ChatbotUserPreference $preferences
    ): ?string {
        if (! config('groq.api_key')) {
            return null;
        }

        $preferencesLine = $this->buildPreferenceContext($preferences);
        $domainContext = $this->getDomainContext();
        $suggestionsLine = empty($suggestions) ? '' : 'Suggestions possibles: ' . implode(' | ', $suggestions);
        $contextLine = $contextLine ? "Contexte page: {$contextLine}" : '';

        $system = "Tu es un assistant KLASSCI. Écris 1 à 2 phrases max, naturelles et humaines. Pose une question courte pour clarifier. Ne liste pas d'étapes. Pas de puces.";

        $prompt = trim(implode("\n", array_filter([
            $contextLine,
            $preferencesLine,
            $domainContext ? "Contexte metier: {$domainContext}" : null,
            $suggestionsLine,
            "Message utilisateur: {$userMessage}",
        ])));

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];

        $text = $this->callGroq($messages, $this->model);
        $text = $text ? trim($text) : null;

        if ($this->isActionGuidanceValid($text)) {
            return $text;
        }

        $system = "Tu es un assistant KLASSCI. Réponds en une seule phrase, ton humain. Interdits: listes, tableaux, URLs, titres, puces. Mentionne simplement les pré-requis manquants et renvoie vers la checklist affichée. Termine par une question courte.";
        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];

        $retry = $this->callGroq($messages, $this->model);
        $retry = $retry ? trim($retry) : null;

        return $this->isActionGuidanceValid($retry) ? $retry : null;
    }

    public function generateGuideIntro(
        ChatbotConversation $conversation,
        string $userMessage,
        string $scope,
        array $guide,
        ?ChatbotUserPreference $preferences
    ): ?string {
        if (! config('groq.api_key')) {
            return null;
        }

        $preferencesLine = $this->buildPreferenceContext($preferences);
        $domainContext = $this->getDomainContext();
        $scopeLabel = match ($scope) {
            'academique' => 'configuration académique',
            'financier' => 'frais et inscriptions',
            'pedagogie' => 'évaluations et suivi',
            default => 'mise en route',
        };

        $preview = !empty($guide['is_preview']) ? 'aperçu' : 'guide complet';

        $system = "Tu es un assistant KLASSCI. Écris une phrase courte et engageante pour introduire un {$preview} du guide. Pas de puces.";

        $prompt = trim(implode("\n", array_filter([
            $preferencesLine,
            $domainContext ? "Contexte metier: {$domainContext}" : null,
            "Contexte: {$scopeLabel}",
            "Message utilisateur: {$userMessage}",
        ])));

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];

        $text = $this->callGroq($messages, $this->model);
        $text = $text ? trim($text) : null;

        if ($this->isActionGuidanceValid($text)) {
            return $text;
        }

        $system = "Tu es un assistant KLASSCI. Réponds en une seule phrase, ton humain. Interdits: listes, tableaux, URLs, titres, puces. Cite les pré-requis manquants et renvoie vers la checklist affichée. Termine par une question courte.";
        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];

        $retry = $this->callGroq($messages, $this->model);
        $retry = $retry ? trim($retry) : null;

        return $this->isActionGuidanceValid($retry) ? $retry : null;
    }

    public function rewriteBackendResponse(
        ChatbotConversation $conversation,
        string $userMessage,
        string $intent,
        string $baseResponse,
        array $stepContext,
        ?ChatbotUserPreference $preferences
    ): ?string {
        if (! config('groq.api_key')) {
            return null;
        }

        $preferencesLine = $this->buildPreferenceContext($preferences);
        $domainContext = $this->getDomainContext();
        $missing = $stepContext['missing_prerequisites'] ?? [];
        $missingTitles = array_values(array_map(static function ($item) {
            return is_array($item) ? ($item['title'] ?? null) : null;
        }, $missing));
        $missingTitles = array_values(array_filter($missingTitles));
        $missingLine = empty($missingTitles) ? '' : 'Pré-requis manquants: ' . implode(', ', $missingTitles);

        $system = "Tu es un assistant KLASSCI. Ta tache: reformuler le message de base sans changer le sens. 1 a 2 phrases, ton humain. Interdits: listes, tableaux, puces, URLs, titres. Ne pas ajouter d'etapes ni d'informations. Si le message de base mentionne une checklist, conserve cette mention; sinon ne l'invente pas. Termine par une question courte.";

        $prompt = trim(implode("\n", array_filter([
            $preferencesLine,
            $domainContext ? "Contexte metier: {$domainContext}" : null,
            $missingLine,
            "Intent: {$intent}",
            "Message utilisateur: {$userMessage}",
            "Message de base a reformuler: {$baseResponse}",
        ])));

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];

        $text = $this->callGroq($messages, $this->model);
        $text = $text ? trim($text) : null;

        $requiredTerms = [];
        if (stripos($baseResponse, 'checklist') !== false) {
            $requiredTerms[] = 'checklist';
        }
        if (strpos($baseResponse, '?') !== false) {
            $requiredTerms[] = '?';
        }

        return $this->isRephraseValid($text, $requiredTerms) ? $text : null;
    }

    public function rewriteBaseResponse(
        ChatbotConversation $conversation,
        string $userMessage,
        string $intent,
        string $baseResponse,
        array $stepContext,
        ?ChatbotUserPreference $preferences
    ): ?string {
        if (! config('groq.api_key')) {
            return null;
        }

        $preferencesLine = $this->buildPreferenceContext($preferences);
        $domainContext = $this->getDomainContext();
        $stepTitle = $stepContext['step']['title'] ?? null;

        $system = "Tu es un assistant KLASSCI. Ta tache: reformuler le message de base sans changer le sens. 1 a 2 phrases, ton humain. Interdits: listes, tableaux, puces, URLs, titres. Ne pas ajouter d'etapes ni d'informations. Termine par une question courte.";

        $prompt = trim(implode("\n", array_filter([
            $preferencesLine,
            $domainContext ? "Contexte metier: {$domainContext}" : null,
            $stepTitle ? "Etape: {$stepTitle}" : null,
            "Intent: {$intent}",
            "Message utilisateur: {$userMessage}",
            "Message de base a reformuler: {$baseResponse}",
        ])));

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];

        $text = $this->callGroq($messages, $this->model);
        $text = $text ? trim($text) : null;

        if ($text && strpos($text, '?') !== false) {
            return null;
        }

        return $this->isRephraseValid($text) ? $text : null;
    }

    public function inferFilterFocusField(string $message): ?string
    {
        if (! config('groq.api_key')) {
            return null;
        }

        $system = "Tu es un assistant KLASSCI. Ta tache: detecter si l'utilisateur veut appliquer un filtre sur les inscriptions. Reponds uniquement en JSON strict: {\"open_form\":true|false,\"focus_field\":\"filiere_id|niveau_id|status|\"}. Si l'utilisateur demande d'ajouter un filtre sans precision, open_form=true et focus_field vide. Ne mets rien d'autre.";
        $prompt = "Message utilisateur: {$message}";

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];

        $text = $this->callGroq($messages, $this->model);
        $text = $text ? trim($text) : null;

        if (! $text) {
            return null;
        }

        $payload = json_decode($text, true);
        if (! is_array($payload)) {
            return null;
        }

        if (empty($payload['open_form'])) {
            return null;
        }

        $focusField = $payload['focus_field'] ?? '';
        if (!in_array($focusField, ['filiere_id', 'niveau_id', 'status', ''], true)) {
            return null;
        }

        return $focusField;
    }

    public function generateActionGuidance(
        ChatbotConversation $conversation,
        string $userMessage,
        string $intent,
        array $stepContext,
        ?ChatbotUserPreference $preferences
    ): ?string {
        if (! config('groq.api_key')) {
            return null;
        }

        $preferencesLine = $this->buildPreferenceContext($preferences);
        $domainContext = $this->getDomainContext();
        $stepTitle = $stepContext['step']['title'] ?? null;
        $stepDescription = $stepContext['step']['description'] ?? null;
        $missing = $stepContext['missing_prerequisites'] ?? [];
        $missingTitles = array_values(array_map(static function ($item) {
            return is_array($item) ? ($item['title'] ?? null) : null;
        }, $missing));
        $missingTitles = array_values(array_filter($missingTitles));
        $missingLine = empty($missingTitles) ? '' : 'Pré-requis manquants: ' . implode(', ', $missingTitles);

        $system = "Tu es un assistant KLASSCI. Réponds en 1 à 3 phrases naturelles, ton humain. Utilise uniquement les informations de contexte fournies (pas d'invention). Si des pré-requis manquent, cite-les brièvement et renvoie vers la checklist affichée juste en dessous. Ne détaille pas d'autres étapes, ne fais pas de tableau, ne donne pas d'URL, ne cite pas de page complète. Termine par une question courte du type: \"Tu veux que je fasse l'étape manquante ici ?\".";

        $prompt = trim(implode("\n", array_filter([
            $preferencesLine,
            $domainContext ? "Contexte metier: {$domainContext}" : null,
            $missingLine,
            $stepTitle ? "Contexte action: {$stepTitle}" : null,
            $stepDescription ? "Details etape: {$stepDescription}" : null,
            "Intent: {$intent}",
            "Message utilisateur: {$userMessage}",
        ])));

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];

        $text = $this->callGroq($messages, $this->model);

        return $text ? trim($text) : null;
    }

    public function generateConversationTitle(
        ChatbotConversation $conversation,
        string $message,
        string $intent,
        ?ChatbotUserPreference $preferences
    ): ?string {
        if (! config('groq.api_key')) {
            return null;
        }

        $preferencesLine = $this->buildPreferenceContext($preferences);
        $system = "Génère un titre court (max 40 caractères), clair, sans guillemets. Pas de ponctuation finale.";
        $prompt = trim(implode("\n", array_filter([
            $preferencesLine,
            "Intent: {$intent}",
            "Message: {$message}",
        ])));

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];

        $text = $this->callGroq($messages, $this->model);

        return $text ? trim($text) : null;
    }

    protected function buildPreferenceContext(?ChatbotUserPreference $preferences): string
    {
        if (!$preferences) {
            return '';
        }

        if ($this->isDefaultPreferences($preferences)) {
            return '';
        }

        $parts = [];
        if ($preferences->preferred_name) {
            $parts[] = 'Nom préféré: ' . $preferences->preferred_name;
        }
        if ($preferences->response_style) {
            $parts[] = 'Style: ' . $preferences->response_style;
        }
        if ($preferences->response_tone) {
            $parts[] = 'Ton: ' . $preferences->response_tone;
        }
        if ($preferences->clarification_mode) {
            $parts[] = 'Clarification: ' . $preferences->clarification_mode;
        }
        if ($preferences->notes) {
            $parts[] = 'Notes: ' . $preferences->notes;
        }

        return empty($parts) ? '' : 'Préférences utilisateur: ' . implode(' | ', $parts);
    }

    protected function isDefaultPreferences(ChatbotUserPreference $preferences): bool
    {
        $hasCustomName = !empty($preferences->preferred_name);
        $hasNotes = !empty($preferences->notes);

        $styleDefault = $preferences->response_style === 'standard';
        $toneDefault = $preferences->response_tone === 'pedagogique';
        $clarifyDefault = $preferences->clarification_mode === 'auto';

        if ($hasCustomName || $hasNotes) {
            return false;
        }

        return $styleDefault && $toneDefault && $clarifyDefault;
    }

    protected function isActionGuidanceValid(?string $text): bool
    {
        if (!$text) {
            return false;
        }

        if (preg_match('/https?:\/\//i', $text)) {
            return false;
        }

        if (preg_match('/\|\s*---|\|\s*Étape|\|/', $text)) {
            return false;
        }

        if (preg_match('/^\s*[-*]\s/m', $text)) {
            return false;
        }

        if (preg_match('/\[[^\]]+\]\([^\)]+\)/', $text)) {
            return false;
        }

        if (strpos($text, "\n") !== false) {
            return false;
        }

        return true;
    }

    protected function isRephraseValid(?string $text, array $requiredTerms = []): bool
    {
        if (! $this->isActionGuidanceValid($text)) {
            return false;
        }

        foreach ($requiredTerms as $term) {
            if ($term === '?' && strpos($text, '?') === false) {
                return false;
            }
            if ($term !== '?' && stripos($text, $term) === false) {
                return false;
            }
        }

        return true;
    }

    protected function getDomainContext(): string
    {
        $fallback = "KLASSCI est un outil de gestion d'etablissement scolaire. Une inscription correspond a l'inscription d'un etudiant dans l'annee et la classe, pas a un evenement ou un cours.";

        try {
            $prompt = ChatbotSystemPrompt::active()->default()->highestPriority()->first();
            if (!$prompt) {
                return $fallback;
            }

            return trim($prompt->prompt) ?: $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    protected function callGroq(array $messages, string $model): ?string
    {
        $apiKey = config('groq.api_key');
        $baseUrl = rtrim(config('groq.base_url', 'https://api.groq.com/openai/v1'), '/');

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => config('groq.temperature', 0.2),
            'top_p' => config('groq.top_p', 0.9),
            'max_tokens' => config('groq.max_tokens', 600),
            'stream' => false,
        ];

        $timeout = (int) config('groq.request_timeout', 20);

        $response = Http::withToken($apiKey)
            ->timeout($timeout)
            ->post($baseUrl . '/chat/completions', $payload);

        if (! $response->ok()) {
            Log::warning('ChatbotLLMService: Groq HTTP error', [
                'status' => $response->status(),
                'model' => $model,
                'body' => $response->body(),
            ]);
            return null;
        }

        $data = $response->json();

        $content = $data['choices'][0]['message']['content'] ?? null;
        if (! $content) {
            Log::warning('ChatbotLLMService: Groq réponse vide', [
                'model' => $model,
                'response' => $data,
            ]);
            return null;
        }

        return $content;
    }
}
