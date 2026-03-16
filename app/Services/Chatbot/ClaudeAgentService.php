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
 * Service agent IA basé sur Claude (Anthropic) avec tool use natif.
 *
 * Remplace GeminiAgentService — même interface publique (chat, generateTitle, getTool).
 */
class ClaudeAgentService
{
    /** @var ChatbotTool[] */
    protected array $tools = [];

    protected int $contextWindow = 20;
    protected int $maxToolRounds = 3;

    public function __construct(ChatbotSetupGuideService $setupGuide)
    {
        $this->registerTools($setupGuide);
    }

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
        if (!config('anthropic.api_key')) {
            Log::warning('ClaudeAgentService: ANTHROPIC_API_KEY manquant.');
            return $this->fallbackResponse();
        }

        $systemPrompt = $this->buildSystemInstruction($user, $preferences, $clientContext);
        $history = $this->buildHistory($conversation);
        $toolDefinitions = $this->buildToolDefinitions($user);

        // Construire les messages : historique + nouveau message user
        $messages = array_merge($history, [
            ['role' => 'user', 'content' => $userMessage],
        ]);

        $allToolCalls = [];
        $lastToolResult = null;
        $displayType = 'text';
        $displayData = null;
        $deepLink = null;
        $text = null;

        for ($round = 0; $round < $this->maxToolRounds; $round++) {
            $response = $this->callClaude($systemPrompt, $messages, $toolDefinitions);

            if (!$response) {
                return $this->fallbackResponse();
            }

            $stopReason = $response['stop_reason'] ?? 'end_turn';
            $contentBlocks = $response['content'] ?? [];

            if ($stopReason !== 'tool_use') {
                // Réponse texte finale
                $text = $this->extractText($contentBlocks);
                break;
            }

            // Claude veut appeler des outils
            $toolUseBlocks = array_filter($contentBlocks, fn ($b) => ($b['type'] ?? '') === 'tool_use');
            $toolResultParts = [];

            foreach ($toolUseBlocks as $block) {
                $toolId = $block['id'];
                $toolName = $block['name'];
                $toolArgs = $block['input'] ?? [];

                Log::info('ClaudeAgent: tool call', ['tool' => $toolName, 'args' => $toolArgs]);

                $tool = $this->getTool($toolName);
                $result = $tool
                    ? $this->executeToolSafely($tool, $toolArgs, $user)
                    : ['error' => "Outil \"{$toolName}\" non trouvé."];

                // Capturer les métadonnées d'affichage
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

                $toolResultParts[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $toolId,
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }

            // Ajouter le tour assistant (tool_use) + user (tool_result)
            $messages[] = ['role' => 'assistant', 'content' => $contentBlocks];
            $messages[] = ['role' => 'user', 'content' => $toolResultParts];
        }

        $text = $text ?? $this->extractText($contentBlocks ?? []);

        // Construire display_data pour les résultats tabulaires/cartes
        if ($displayType !== 'checklist' && $lastToolResult) {
            $displayData = $this->buildDisplayData($lastToolResult, $displayType);
        }

        // Ajouter des suggestions de suivi automatiques
        if ($displayData && $allToolCalls) {
            $lastTool = end($allToolCalls)['tool'] ?? null;
            $displayData['follow_up'] = $this->generateFollowUpSuggestions($lastTool);
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
        if (!config('anthropic.api_key')) {
            return null;
        }

        $response = $this->callClaude(
            'Génère un titre court (max 40 caractères) pour cette conversation. Réponds uniquement le titre, sans guillemets ni ponctuation finale.',
            [['role' => 'user', 'content' => $message]],
            []
        );

        $text = $this->extractText($response['content'] ?? []);
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
2. Utilise les outils (tools) pour récupérer des données réelles. NE JAMAIS inventer de données.
3. Si l'utilisateur pose une question sur des données (étudiants, paiements, inscriptions, frais, classes), appelle l'outil approprié.
4. Pour les salutations ou questions générales, réponds directement sans outil.
5. Après avoir reçu les résultats d'un outil, écris UNIQUEMENT un court résumé (1-3 phrases). NE REPRODUIS JAMAIS les données brutes, les tableaux ou les listes dans ta réponse — le frontend affiche automatiquement les résultats sous forme de widgets visuels (tableaux, cartes). Ton rôle est juste d'introduire et contextualiser.
6. Si un outil retourne 0 résultat, dis-le clairement et suggère des alternatives.
7. Ne génère JAMAIS de tableaux markdown, de listes de données, de code, ou de JSON. Réponds en langage naturel concis.
8. Si l'utilisateur demande comment faire quelque chose (créer une inscription, saisir des notes...), utilise navigate_to_page pour lui donner un lien direct.
9. NE METS PAS de suggestions dans ta réponse texte. Le système les génère automatiquement sous forme de boutons cliquables. Ta réponse doit contenir UNIQUEMENT le résumé introductif (1-3 phrases max).
PROMPT;
    }

    /**
     * Construire l'historique de conversation au format Claude Messages.
     */
    protected function buildHistory(ChatbotConversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->limit($this->contextWindow)
            ->get();

        $contents = [];
        foreach ($messages as $msg) {
            $role = $msg->role === 'assistant' ? 'assistant' : 'user';
            $contents[] = [
                'role' => $role,
                'content' => $msg->content ?? '',
            ];
        }

        return $contents;
    }

    /**
     * Construire les définitions d'outils au format Claude.
     */
    protected function buildToolDefinitions($user): array
    {
        $definitions = [];
        foreach ($this->tools as $tool) {
            if ($requiredPerms = $tool->requiredPermissions()) {
                $hasAll = collect($requiredPerms)->every(fn ($p) => $user->can($p));
                if (!$hasAll) {
                    continue;
                }
            }

            if ($allowedRoles = $tool->allowedRoles()) {
                $hasRole = $user->roles->pluck('name')->intersect($allowedRoles)->isNotEmpty();
                if (!$hasRole) {
                    continue;
                }
            }

            $definitions[] = $tool->toToolDefinition();
        }

        return $definitions;
    }

    /**
     * Appeler l'API Claude Messages.
     */
    protected function callClaude(string $systemPrompt, array $messages, array $toolDefinitions): ?array
    {
        $apiKey = config('anthropic.api_key');
        $model = config('anthropic.model', 'claude-haiku-4-5');
        $baseUrl = rtrim(config('anthropic.base_url', 'https://api.anthropic.com/v1/'), '/');
        $timeout = (int) config('anthropic.request_timeout', 30);

        $url = $baseUrl . '/messages';

        $payload = [
            'model' => $model,
            'max_tokens' => 2048,
            'system' => $systemPrompt,
            'messages' => $messages,
        ];

        if (!empty($toolDefinitions)) {
            $payload['tools'] = $toolDefinitions;
        }

        try {
            // OAuth token (sk-ant-oat01-*) : Bearer + beta header
            // API key (sk-ant-api03-*) : x-api-key header
            $isOAuth = str_starts_with($apiKey, 'sk-ant-oat');
            $headers = [
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ];

            if ($isOAuth) {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
                $headers['anthropic-beta'] = 'oauth-2025-04-20';
            } else {
                $headers['x-api-key'] = $apiKey;
            }

            $response = Http::timeout($timeout)
                ->withHeaders($headers)
                ->post($url, $payload);

            if (!$response->ok()) {
                Log::warning('ClaudeAgent: HTTP error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('ClaudeAgent: call failed', ['error' => $e->getMessage()]);
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
            Log::error('ClaudeAgent: tool execution failed', [
                'tool' => $tool->name(),
                'error' => $e->getMessage(),
            ]);
            return ['error' => 'Erreur lors de l\'exécution: ' . $e->getMessage()];
        }
    }

    /**
     * Extraire le texte des content blocks Claude.
     */
    protected function extractText(array $contentBlocks): string
    {
        $texts = [];
        foreach ($contentBlocks as $block) {
            if (is_string($block)) {
                $texts[] = $block;
            } elseif (isset($block['type']) && $block['type'] === 'text' && isset($block['text'])) {
                $texts[] = $block['text'];
            }
        }
        return implode("\n", $texts) ?: 'Je suis là pour vous aider. Que souhaitez-vous savoir ?';
    }

    // ── Display data builders (identiques à GeminiAgentService) ──

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

    protected function buildTableDisplayData(array $results, array $toolResult): array
    {
        if (empty($results)) {
            return ['columns' => [], 'rows' => [], 'column_count' => 0];
        }

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

                if (in_array($key, ['statut', 'active', 'actif'], true)) {
                    $cell['badge'] = $this->getBadgeClass((string) $value);
                }

                $cells[] = $cell;
            }

            $row = ['cells' => $cells, 'column_count' => count($columns)];

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

    protected function generateFollowUpSuggestions(?string $toolName): array
    {
        return match ($toolName) {
            'search_inscriptions' => [
                'Voir les paiements associés',
                'Lister les classes actives',
                'Chercher un étudiant précis',
            ],
            'search_students' => [
                'Voir ses paiements',
                'Voir ses résultats',
                'Lister toutes les inscriptions',
            ],
            'search_payments' => [
                'Paiements en attente',
                'Voir les inscriptions',
                'Accéder à la comptabilité',
            ],
            'search_classes' => [
                'Voir les étudiants d\'une classe',
                'Consulter les inscriptions',
                'Voir l\'emploi du temps',
            ],
            'search_fees' => [
                'Voir les paiements',
                'Configurer les frais',
                'Lister les inscriptions',
            ],
            'get_setup_guide' => [
                'Créer une filière',
                'Ajouter un étudiant',
                'Configurer les frais',
            ],
            'navigate_to_page' => [
                'Voir les inscriptions',
                'Chercher un étudiant',
                'Consulter les paiements',
            ],
            default => [
                'Voir les inscriptions',
                'Chercher un étudiant',
                'Consulter les paiements',
            ],
        };
    }

    protected function fallbackResponse(): array
    {
        return [
            'text' => 'Désolé, le service IA est temporairement indisponible. Vérifiez que ANTHROPIC_API_KEY est configuré.',
            'tool_calls' => [],
            'display_type' => 'text',
            'display_data' => null,
            'deep_link' => null,
        ];
    }
}
