<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\ChatbotSystemPrompt;
use App\Models\ChatbotUserPreference;
use App\Services\Chatbot\Tools\ChatbotTool;
use App\Services\Chatbot\Tools\GetSetupGuideTool;
use App\Services\Chatbot\Tools\NavigateToPageTool;
use App\Services\Chatbot\Tools\SearchClassesTool;
use App\Services\Chatbot\Tools\SearchFeesTool;
use App\Services\Chatbot\Tools\SearchInscriptionsTool;
use App\Services\Chatbot\Tools\SearchPaymentsTool;
use App\Services\Chatbot\Tools\SearchStudentsTool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service agent IA basé sur Gemini 2.0 Flash avec function calling natif.
 *
 * Remplace ChatbotLLMService (JSON classification) par un vrai agent
 * qui décide quels outils appeler et génère des réponses naturelles.
 */
class GeminiAgentService
{
    /** @var ChatbotTool[] */
    protected array $tools = [];

    protected int $contextWindow = 20;
    protected int $maxToolRounds = 3;

    public function __construct(ChatbotSetupGuideService $setupGuide)
    {
        $this->registerTools($setupGuide);
    }

    /**
     * Enregistrer tous les outils disponibles.
     */
    protected function registerTools(ChatbotSetupGuideService $setupGuide): void
    {
        $this->tools = [
            new SearchStudentsTool(),
            new SearchPaymentsTool(),
            new SearchInscriptionsTool(),
            new SearchFeesTool(),
            new SearchClassesTool(),
            new GetSetupGuideTool($setupGuide),
            new NavigateToPageTool(),
        ];
    }

    /**
     * Obtenir un outil par son nom.
     */
    public function getTool(string $name): ?ChatbotTool
    {
        foreach ($this->tools as $tool) {
            if ($tool->name() === $name) {
                return $tool;
            }
        }
        return null;
    }

    /**
     * Point d'entrée principal : envoyer un message et obtenir la réponse de l'agent.
     *
     * @return array{text:string,tool_calls:array,display_type:string,display_data:?array,deep_link:?string}
     */
    public function chat(
        ChatbotConversation $conversation,
        string $userMessage,
        $user,
        ?ChatbotUserPreference $preferences = null,
        ?array $clientContext = null
    ): array {
        if (!config('gemini.api_key')) {
            Log::warning('GeminiAgentService: GEMINI_API_KEY manquant.');
            return $this->fallbackResponse();
        }

        $systemInstruction = $this->buildSystemInstruction($user, $preferences, $clientContext);
        $history = $this->buildHistory($conversation);
        $toolDeclarations = $this->buildToolDeclarations($user);

        // Boucle agent : envoyer message → recevoir tool calls → exécuter → renvoyer résultats → ...
        $contents = array_merge($history, [
            ['role' => 'user', 'parts' => [['text' => $userMessage]]],
        ]);

        $allToolCalls = [];
        $lastToolResult = null;
        $displayType = 'text';
        $displayData = null;
        $deepLink = null;

        for ($round = 0; $round < $this->maxToolRounds; $round++) {
            $response = $this->callGemini($systemInstruction, $contents, $toolDeclarations);

            if (!$response) {
                return $this->fallbackResponse();
            }

            $parts = $response['candidates'][0]['content']['parts'] ?? [];

            // Vérifier s'il y a des function calls
            $functionCalls = array_filter($parts, fn ($p) => isset($p['functionCall']));

            if (empty($functionCalls)) {
                // Pas de function call → réponse texte finale
                $text = $this->extractText($parts);
                break;
            }

            // Exécuter chaque function call
            $functionResponses = [];
            foreach ($functionCalls as $part) {
                $call = $part['functionCall'];
                $toolName = $call['name'];
                $toolArgs = $call['args'] ?? [];

                Log::info("GeminiAgent: tool call", ['tool' => $toolName, 'args' => $toolArgs]);

                $tool = $this->getTool($toolName);
                $result = $tool
                    ? $this->executeToolSafely($tool, $toolArgs, $user)
                    : ['error' => "Outil \"{$toolName}\" non trouvé."];

                // Capturer les métadonnées d'affichage du dernier outil exécuté
                if ($tool && !isset($result['error'])) {
                    $allToolCalls[] = ['tool' => $toolName, 'args' => $toolArgs, 'result_count' => $result['count'] ?? null];
                    $lastToolResult = $result;

                    if (isset($result['display_type'])) {
                        $displayType = $result['display_type'];
                    }
                    if (isset($result['deep_link'])) {
                        $deepLink = $result['deep_link'];
                    }
                    if (isset($result['guide'])) {
                        $displayData = $result['guide'];
                        $displayType = 'checklist';
                    }
                }

                $functionResponses[] = [
                    'functionResponse' => [
                        'name' => $toolName,
                        'response' => $result,
                    ],
                ];
            }

            // Ajouter le tour model (function calls) + function responses
            $contents[] = ['role' => 'model', 'parts' => $parts];
            $contents[] = ['role' => 'function', 'parts' => $functionResponses];
        }

        $text = $text ?? $this->extractText($parts ?? []);

        // Construire display_data pour les résultats tabulaires/cartes
        if ($displayType !== 'checklist' && $lastToolResult) {
            $displayData = $this->buildDisplayData($lastToolResult, $displayType);
        }

        return [
            'text' => $text,
            'tool_calls' => $allToolCalls,
            'display_type' => $displayType,
            'display_data' => $displayData,
            'deep_link' => $deepLink,
        ];
    }

    /**
     * Générer un titre de conversation.
     */
    public function generateTitle(string $message): ?string
    {
        if (!config('gemini.api_key')) {
            return null;
        }

        $response = $this->callGemini(
            'Génère un titre court (max 40 caractères) pour cette conversation. Réponds uniquement le titre, sans guillemets ni ponctuation finale.',
            [['role' => 'user', 'parts' => [['text' => $message]]]],
            []
        );

        $text = $this->extractText($response['candidates'][0]['content']['parts'] ?? []);
        return $text ? mb_substr(trim($text, " \t\n\r\"'"), 0, 40, 'UTF-8') : null;
    }

    /**
     * Construire l'instruction système.
     */
    protected function buildSystemInstruction($user, ?ChatbotUserPreference $preferences, ?array $clientContext): string
    {
        $domainContext = $this->getDomainContext();
        $userName = $preferences?->preferred_name ?? $user->name ?? 'utilisateur';
        $roleName = $user->roles?->first()?->name ?? 'utilisateur';

        $styleInstructions = '';
        if ($preferences) {
            $style = $preferences->response_style ?? 'standard';
            $tone = $preferences->response_tone ?? 'pedagogique';
            $styleInstructions = match ($style) {
                'court' => 'Réponses très concises (1-2 phrases max).',
                'detaille' => 'Réponses détaillées avec explications.',
                default => 'Réponses de longueur standard.',
            };
            $styleInstructions .= ' ' . match ($tone) {
                'direct' => 'Ton direct et professionnel.',
                'chaleureux' => 'Ton chaleureux et encourageant.',
                default => 'Ton pédagogique et bienveillant.',
            };
        }

        $pageContext = '';
        if ($clientContext) {
            $pageName = $clientContext['page_title'] ?? $clientContext['current_path'] ?? null;
            if ($pageName) {
                $pageContext = "\nL'utilisateur est actuellement sur la page : {$pageName}.";
            }
        }

        $notesUtilisateur = '';
        if ($preferences?->notes) {
            $notesUtilisateur = "\nNotes personnelles de l'utilisateur : {$preferences->notes}";
        }

        return <<<PROMPT
Tu es l'assistant IA de KLASSCI, un système de gestion d'établissement scolaire professionnel (BTS, Licence) en Côte d'Ivoire.

{$domainContext}

L'utilisateur s'appelle {$userName} et a le rôle "{$roleName}".
{$styleInstructions}{$pageContext}{$notesUtilisateur}

RÈGLES IMPORTANTES :
1. Réponds toujours en français.
2. Utilise les outils (functions) pour récupérer des données réelles. NE JAMAIS inventer de données.
3. Si l'utilisateur pose une question sur des données (étudiants, paiements, inscriptions, frais, classes), appelle l'outil approprié.
4. Pour les salutations ou questions générales, réponds directement sans outil.
5. Après avoir reçu les résultats d'un outil, formule une réponse naturelle et utile.
6. Si un outil retourne 0 résultat, dis-le clairement et suggère des alternatives.
7. Propose toujours 2-3 suggestions de suivi pertinentes à la fin.
8. Quand tu affiches des données (tableau, cartes), ajoute un court texte introductif.
9. Ne génère JAMAIS de code, de JSON brut, ou de markdown technique. Réponds en langage naturel.
10. Si l'utilisateur demande comment faire quelque chose (créer une inscription, saisir des notes...), utilise navigate_to_page pour lui donner un lien direct.
PROMPT;
    }

    /**
     * Construire l'historique de conversation au format Gemini.
     */
    protected function buildHistory(ChatbotConversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->limit($this->contextWindow)
            ->get();

        $contents = [];
        foreach ($messages as $msg) {
            $role = $msg->role === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg->content ?? '']],
            ];
        }

        return $contents;
    }

    /**
     * Construire les déclarations d'outils au format Gemini.
     */
    protected function buildToolDeclarations($user): array
    {
        $declarations = [];
        foreach ($this->tools as $tool) {
            // Vérifier les permissions
            if ($requiredPerms = $tool->requiredPermissions()) {
                $hasAll = collect($requiredPerms)->every(fn ($p) => $user->can($p));
                if (!$hasAll) {
                    continue;
                }
            }

            // Vérifier les rôles
            if ($allowedRoles = $tool->allowedRoles()) {
                $hasRole = $user->roles->pluck('name')->intersect($allowedRoles)->isNotEmpty();
                if (!$hasRole) {
                    continue;
                }
            }

            $declarations[] = $tool->toGeminiDeclaration();
        }

        return $declarations;
    }

    /**
     * Appeler l'API Gemini generateContent.
     */
    protected function callGemini(string $systemInstruction, array $contents, array $toolDeclarations): ?array
    {
        $apiKey = config('gemini.api_key');
        $model = config('gemini.model', 'gemini-2.0-flash-exp');
        $baseUrl = config('gemini.base_url') ?? 'https://generativelanguage.googleapis.com/v1beta/';
        $timeout = (int) config('gemini.request_timeout', 30);

        $url = rtrim($baseUrl, '/') . "/models/{$model}:generateContent?key={$apiKey}";

        $payload = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'topP' => 0.9,
                'maxOutputTokens' => 2048,
            ],
        ];

        if (!empty($toolDeclarations)) {
            $payload['tools'] = [
                ['functionDeclarations' => $toolDeclarations],
            ];
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if (!$response->ok()) {
                Log::warning('GeminiAgent: HTTP error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('GeminiAgent: call failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Exécuter un outil de manière sécurisée.
     */
    protected function executeToolSafely(ChatbotTool $tool, array $args, $user): array
    {
        try {
            return $tool->execute($args, $user);
        } catch (\Throwable $e) {
            Log::error('GeminiAgent: tool execution failed', [
                'tool' => $tool->name(),
                'error' => $e->getMessage(),
            ]);
            return ['error' => 'Erreur lors de l\'exécution: ' . $e->getMessage()];
        }
    }

    /**
     * Extraire le texte des parts Gemini.
     */
    protected function extractText(array $parts): string
    {
        $texts = [];
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $texts[] = $part['text'];
            }
        }
        return implode("\n", $texts) ?: 'Je suis là pour vous aider. Que souhaitez-vous savoir ?';
    }

    /**
     * Construire les données d'affichage à partir des résultats d'un outil.
     */
    protected function buildDisplayData(array $toolResult, string $displayType): ?array
    {
        $results = $toolResult['results'] ?? [];
        if (empty($results)) {
            return null;
        }

        if ($displayType === 'table') {
            return $this->buildTableDisplayData($results, $toolResult);
        }

        if ($displayType === 'cards') {
            return $this->buildCardsDisplayData($results, $toolResult);
        }

        return null;
    }

    /**
     * Construire un tableau d'affichage.
     */
    protected function buildTableDisplayData(array $results, array $toolResult): array
    {
        if (empty($results)) {
            return ['columns' => [], 'rows' => [], 'column_count' => 0];
        }

        // Détecter les colonnes à partir du premier résultat
        $first = $results[0];
        $skipKeys = ['id', 'lien', 'lien_inscription', 'montant_brut', 'deep_link'];
        $columns = [];
        foreach (array_keys($first) as $key) {
            if (in_array($key, $skipKeys, true)) {
                continue;
            }
            $columns[] = ['label' => $this->humanizeColumnName($key)];
        }

        $rows = [];
        foreach ($results as $result) {
            $cells = [];
            foreach (array_keys($first) as $key) {
                if (in_array($key, $skipKeys, true)) {
                    continue;
                }
                $value = $result[$key] ?? 'N/A';
                $cell = ['value' => (string) $value];

                // Badges pour les statuts
                if (in_array($key, ['statut', 'active', 'actif'], true)) {
                    $cell['badge'] = $this->getBadgeClass((string) $value);
                }

                $cells[] = $cell;
            }

            $row = ['cells' => $cells, 'column_count' => count($columns)];

            // Actions (liens)
            $actions = [];
            if (!empty($result['lien'])) {
                $actions[] = ['label' => 'Voir', 'url' => $result['lien'], 'icon' => 'fas fa-eye'];
            }
            if (!empty($result['lien_inscription'])) {
                $actions[] = ['label' => 'Inscription', 'url' => $result['lien_inscription'], 'icon' => 'fas fa-file-invoice'];
            }
            if (!empty($actions)) {
                $row['actions'] = $actions;
            }

            $rows[] = $row;
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'column_count' => count($columns),
            'total_count' => count($results),
            'total_available' => $toolResult['total'] ?? count($results),
            'deep_link' => $toolResult['deep_link'] ?? null,
        ];
    }

    /**
     * Construire des cartes d'affichage.
     */
    protected function buildCardsDisplayData(array $results, array $toolResult): array
    {
        $cards = [];
        foreach ($results as $result) {
            $card = [
                'title' => $result['etudiant'] ?? $result['nom'] ?? 'N/A',
                'subtitle' => $result['classe'] ?? $result['filiere'] ?? '',
                'meta' => [],
                'badges' => [],
            ];

            if (isset($result['type'])) {
                $card['meta'][] = ['label' => 'Type', 'value' => $result['type']];
            }
            if (isset($result['statut'])) {
                $card['meta'][] = ['label' => 'Statut', 'value' => $result['statut']];
                $card['badges'][] = ['label' => $result['statut'], 'style' => $this->getBadgeClass($result['statut'])];
            }
            if (isset($result['date'])) {
                $card['meta'][] = ['label' => 'Date', 'value' => $result['date']];
            }

            if (!empty($result['lien'])) {
                $card['actions'] = [['label' => 'Voir', 'url' => $result['lien'], 'icon' => 'fas fa-eye']];
            }

            $cards[] = $card;
        }

        return [
            'cards' => $cards,
            'total_count' => count($cards),
            'total_available' => $toolResult['total'] ?? count($cards),
            'deep_link' => $toolResult['deep_link'] ?? null,
        ];
    }

    protected function humanizeColumnName(string $key): string
    {
        $map = [
            'nom' => 'Nom',
            'matricule' => 'Matricule',
            'classe' => 'Classe',
            'filiere' => 'Filière',
            'niveau' => 'Niveau',
            'effectif' => 'Effectif',
            'active' => 'Active',
            'actif' => 'Actif',
            'statut' => 'Statut',
            'etudiant' => 'Étudiant',
            'montant' => 'Montant',
            'date' => 'Date',
            'mode' => 'Mode',
            'type' => 'Type',
            'code' => 'Code',
            'categorie' => 'Catégorie',
            'type_tarif' => 'Type tarif',
        ];

        return $map[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    protected function getBadgeClass(string $value): string
    {
        $lower = mb_strtolower($value, 'UTF-8');
        return match (true) {
            str_contains($lower, 'valid'), str_contains($lower, 'activ'), $lower === 'oui' => 'success',
            str_contains($lower, 'attente') => 'warning',
            str_contains($lower, 'rejet'), str_contains($lower, 'annul'), $lower === 'non' => 'danger',
            default => 'info',
        };
    }

    protected function getDomainContext(): string
    {
        try {
            $prompt = ChatbotSystemPrompt::active()->default()->highestPriority()->first();
            return $prompt ? trim($prompt->prompt) : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function fallbackResponse(): array
    {
        return [
            'text' => 'Désolé, le service IA est temporairement indisponible. Vérifiez que GEMINI_API_KEY est configuré.',
            'tool_calls' => [],
            'display_type' => 'text',
            'display_data' => null,
            'deep_link' => null,
        ];
    }
}
