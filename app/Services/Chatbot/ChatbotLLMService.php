<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\ChatbotKnowledgeBase;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Responses\GenerativeModel\GenerateContentResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ChatbotLLMService
{
    protected string $model;

    /**
     * Nombre maximal de messages de contexte envoyés au LLM.
     */
    protected int $contextWindow = 10;

    public function __construct()
    {
        $this->model = config('gemini.model', 'models/gemini-1.5-flash-latest');
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
        if (! config('gemini.api_key')) {
            Log::warning('ChatbotLLMService: GEMINI_API_KEY manquant, retour heuristique.');
            return [];
        }

        $knowledge = $this->buildKnowledgeSnapshot();
        $history = $this->buildHistory($conversation);
        $contextSummary = $this->buildContextSummary($conversation);

        $prompt = $this->buildPrompt($userMessage, $knowledge, $contextSummary);

        try {
            Log::info('ChatbotLLMService: appel Gemini', [
                'conversation_id' => $conversation->id,
                'user_message' => $userMessage,
            ]);

            $model = Gemini::generativeModel($this->model)
                ->withSystemInstruction($this->systemInstruction());

            $response = $model->generateContent(...array_merge($history, [Content::parse($prompt, Role::USER)]));

            Log::info('ChatbotLLMService: réponse Gemini reçue', [
                'conversation_id' => $conversation->id,
                'prompt_tokens' => $response->usageMetadata->promptTokenCount ?? null,
                'candidates' => count($response->candidates ?? []),
            ]);

            // Debug: Log raw response
            Log::debug('ChatbotLLMService: réponse brute Gemini', [
                'text' => $response->text(),
            ]);

            $decision = $this->parseDecision($response);

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
            Log::error('ChatbotLLMService: Gemini call failed', [
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
     * @return array<Content>
     */
    protected function buildHistory(ChatbotConversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->take($this->contextWindow)
            ->get();

        return $messages->map(function ($message) {
            $role = $message->role === 'assistant' ? Role::MODEL : Role::USER;

            return Content::parse($message->content, $role);
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

        return <<<PROMPT
Historique conversation (résumé) :
{$contextLine}

Intents et sources disponibles :
{$knowledgeLines}

Utilisateur :
{$userMessage}
PROMPT;
    }

    protected function systemInstruction(): Content
    {
        $text = <<<'PROMPT'
Tu es l'assistant interne KLASSCI. Tu dois répondre en français et uniquement avec un JSON valide respectant strictement ce schéma :
{
  "intent": "string ou null",
  "filters": { ... },
  "display": "table" | "cards" | "kpi" | "text" | null,
  "response_text": "string ou null",
  "limit": nombre entier ou null,
  "follow_up": ["..."] ou []
}

Règles :
- `intent` doit correspondre à l'intent le plus approprié même s'il n'existe pas encore dans les intents disponibles.
  Exemples d'intents valides : get_paiements, get_inscriptions, get_etudiants, get_frais, get_classes, get_categories_frais, get_tarifs
  Si la question porte sur les frais de scolarité ou tarifs, utilise "get_frais" ou "get_categories_frais".
  Si la question porte sur les paiements effectués, utilise "get_paiements".
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

        return Content::parse($text, Role::MODEL);
    }

    /**
     * @return array<string,mixed>
     */
    protected function parseDecision(GenerateContentResponse $response): array
    {
        try {
            // Récupérer le texte brut
            $text = $response->text();

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
}
