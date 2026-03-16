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
use App\Services\Chatbot\Tools\SearchEvaluationsTool;
use App\Services\Chatbot\Tools\SearchNotesTool;
use App\Services\Chatbot\Tools\SearchAttendancesTool;
use App\Services\Chatbot\Tools\SearchTeachersTool;
use App\Services\Chatbot\Tools\GetDashboardKpisTool;
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

    protected int $contextWindow = 10;
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
            new SearchEvaluationsTool(),
            new SearchNotesTool(),
            new SearchAttendancesTool(),
            new SearchTeachersTool(),
            new GetDashboardKpisTool(),
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
        $contentBlocks = [];

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

                    $resultDisplayType = $result['display_type'] ?? 'text';

                    // Fee groups : fusionner les groupes si même tool appelé plusieurs fois
                    if ($resultDisplayType === 'fee_groups' && $lastToolResult && ($lastToolResult['display_type'] ?? '') === 'fee_groups') {
                        $lastToolResult['results'] = array_merge($lastToolResult['results'] ?? [], $result['results'] ?? []);
                        $lastToolResult['count'] = ($lastToolResult['count'] ?? 0) + ($result['count'] ?? 0);
                    } else {
                        // Pour les autres types : garder le résultat avec le plus de données
                        $resultCount = $result['count'] ?? count($result['results'] ?? []);
                        $lastCount = $lastToolResult ? ($lastToolResult['count'] ?? 0) : 0;
                        if (!$lastToolResult || $resultCount > 0 || $lastCount === 0) {
                            $lastToolResult = $result;
                        }
                    }

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

        // Si navigate_to_page a retourné un page_guide, l'injecter dans la réponse
        // car Haiku ignore souvent le guide dans le tool_result
        if ($lastToolResult && isset($lastToolResult['page_guide']) && $lastToolResult['page_guide']) {
            $guide = $lastToolResult['page_guide'];
            // Si Claude n'a pas généré de texte utile, utiliser le guide directement
            $isGenericResponse = !$text || mb_strlen($text) < 50 || str_contains($text, 'Je suis là pour vous aider');
            if ($isGenericResponse) {
                $text = $guide;
            } elseif (!str_contains($text, '1.') && !str_contains($text, '- ')) {
                // Si Claude a répondu mais sans détails, ajouter le guide
                $text .= "\n\n" . $guide;
            }
        }

        // Construire display_data pour les résultats tabulaires/cartes
        if ($displayType === 'fee_groups' && $lastToolResult) {
            // Fee groups : données pré-structurées par le tool
            $displayData = [
                'groups' => $lastToolResult['results'] ?? [],
                'deep_link' => $lastToolResult['deep_link'] ?? null,
                'total_count' => $lastToolResult['count'] ?? 0,
            ];
        } elseif ($displayType !== 'checklist' && $lastToolResult) {
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

WORKFLOW EMPLOI DU TEMPS :
- La page "Emplois du temps" (index) liste tous les emplois du temps + raccourci pour créer rapidement + bouton "Modifier rapidement" (multi-sélection)
- Créer un emploi du temps = créer le socle (classe, dates, semestre). C'est un conteneur vide.
- Ensuite, on ajoute des séances de cours dessus depuis la vue détaillée (show) : matière, enseignant, jour, horaire, salle
- "Modifier rapidement" = sélectionner plusieurs emplois du temps et les voir/éditer en même temps (vue accordéon)
- Quand l'outil navigate_to_page retourne un champ "page_guide", utilise-le comme base pour ton guide détaillé.

RÈGLES IMPORTANTES :
1. Réponds toujours en français.
2. Utilise les outils (tools) pour récupérer des données réelles. NE JAMAIS inventer de données. NE JAMAIS répondre de mémoire ou à partir de l'historique de conversation — appelle TOUJOURS l'outil même si tu penses déjà connaître la réponse.
3. Si l'utilisateur pose une question sur des données (étudiants, paiements, inscriptions, frais, classes), appelle OBLIGATOIREMENT l'outil approprié, même si une recherche similaire a déjà été faite dans la conversation.
4. Pour les salutations ou questions générales, réponds directement sans outil.
5. INTERDIT ABSOLU : après un outil, ne reproduis JAMAIS les données (noms, montants, listes, formules) dans ton texte. Le frontend affiche un widget visuel EN DESSOUS. Écris SEULEMENT 1-2 phrases d'introduction. Exemple CORRECT : "Voici les frais optionnels configurés. Les détails s'affichent ci-dessous." Exemple INTERDIT : "Cantine : - Repas complet : 455 000 FCFA..." ← NE FAIS JAMAIS ÇA.
6. Si un outil retourne 0 résultat, dis-le clairement et suggère des alternatives.
7. Ne génère JAMAIS de tableaux markdown, de listes de données, de code, ou de JSON. Réponds en langage naturel concis.
8. Si l'utilisateur demande comment faire quelque chose (créer une inscription, saisir des notes...), utilise navigate_to_page pour lui donner un lien direct. Pour ces réponses de navigation, fournis un guide détaillé avec les étapes numérotées que l'utilisateur devra suivre sur la page (champs à remplir, options à sélectionner, etc.). Le bouton "Ouvrir la page" s'affiche automatiquement en dessous.
9. NE METS PAS de suggestions de suivi dans ta réponse texte (ex: "Tu veux aussi voir les paiements ?"). Le système les génère automatiquement sous forme de boutons cliquables. Par contre, pour les données (règle 5), ta réponse doit contenir UNIQUEMENT le résumé introductif (1-3 phrases max).
PROMPT;
    }

    /**
     * Construire l'historique de conversation au format Claude Messages.
     */
    protected function buildHistory(ChatbotConversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit($this->contextWindow)
            ->get()
            ->reverse()
            ->values();

        $contents = [];
        foreach ($messages as $msg) {
            $role = $msg->role === 'assistant' ? 'assistant' : 'user';
            $text = $msg->content ?? '';

            // Pour les réponses assistant qui avaient des données, remplacer par un placeholder
            // pour forcer Claude à appeler les outils au lieu de répondre de mémoire
            if ($role === 'assistant' && $msg->display_type !== 'text') {
                $text = '[Résultats affichés via widget - appeler l\'outil pour des données fraîches]';
            }

            $contents[] = [
                'role' => $role,
                'content' => $text,
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
        $maxTokens = (int) config('anthropic.max_tokens', 2048);
        $temperature = (float) config('anthropic.temperature', 0.2);
        $retryAttempts = (int) config('anthropic.retry_attempts', 2);
        $retryDelay = (int) config('anthropic.retry_delay_ms', 1000);

        $url = $baseUrl . '/messages';
        $isOAuth = str_starts_with($apiKey, 'sk-ant-oat');

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'system' => $isOAuth
                ? $systemPrompt
                : [['type' => 'text', 'text' => $systemPrompt, 'cache_control' => ['type' => 'ephemeral']]],
            'messages' => $messages,
        ];

        if (!empty($toolDefinitions)) {
            $payload['tools'] = $toolDefinitions;
            $payload['tool_choice'] = ['type' => 'auto'];
        }

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ];

            if ($isOAuth) {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
                $headers['anthropic-beta'] = 'oauth-2025-04-20';
            } else {
                $headers['x-api-key'] = $apiKey;
                $headers['anthropic-beta'] = 'prompt-caching-2024-07-31';
            }

            $response = Http::timeout($timeout)
                ->retry($retryAttempts, $retryDelay, function ($e) {
                    return $e instanceof \Illuminate\Http\Client\ConnectionException
                        || ($e instanceof \Illuminate\Http\Client\RequestException && $e->response?->status() >= 500);
                }, throw: false)
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
     * Chat avec streaming SSE.
     * Tool rounds = non-streaming (rapide). Réponse finale = streaming texte.
     * Appelle $onEvent(string $type, mixed $data) pour chaque événement SSE.
     *
     * @return array{text: string, tool_calls: array, display_type: string, display_data: ?array, deep_link: ?string}
     */
    public function chatStream(
        ChatbotConversation $conversation,
        string $message,
        $user,
        ?ChatbotUserPreference $preferences = null,
        ?array $clientContext = null,
        callable $onEvent = null,
    ): array {
        $emit = $onEvent ?? fn() => null;
        $emit('status', ['message' => 'Analyse de votre demande...']);

        $result = $this->chat($conversation, $message, $user, $preferences, $clientContext);

        // Vérifier si c'est une erreur (fallback response)
        $isFallback = empty($result['tool_calls']) && str_contains($result['text'] ?? '', 'temporairement indisponible');
        if ($isFallback) {
            $emit('error', ['message' => $result['text']]);
            return $result;
        }

        // Streaming texte mot par mot
        $text = $result['text'] ?? '';
        if ($text) {
            $words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
            $buffer = '';
            foreach ($words as $word) {
                $buffer .= $word;
                $emit('text_delta', ['text' => $word, 'full_text' => $buffer]);
                usleep(15000); // 15ms entre chaque mot pour un effet visuel
            }
        }

        $emit('done', [
            'display_type' => $result['display_type'],
            'display_data' => $result['display_data'],
            'deep_link' => $result['deep_link'],
        ]);

        return $result;
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
                'initials' => $result['initials'] ?? null,
                'meta' => [],
                'badges' => [],
            ];

            if (isset($result['matricule']) && $result['matricule'] !== 'N/A') {
                $card['meta'][] = ['label' => 'Matricule', 'value' => $result['matricule']];
            }
            if (isset($result['filiere']) && !isset($result['etudiant'])) {
                $card['meta'][] = ['label' => 'Filière', 'value' => $result['filiere']];
            }
            if (isset($result['type'])) {
                $card['meta'][] = ['label' => 'Type', 'value' => $result['type']];
            }
            if (isset($result['statut'])) {
                $card['badges'][] = ['label' => $result['statut'], 'style' => $this->getBadgeClass($result['statut'])];
            }
            if (isset($result['date'])) {
                $card['meta'][] = ['label' => 'Date', 'value' => $result['date']];
            }
            if (isset($result['montant'])) {
                $card['meta'][] = ['label' => 'Montant', 'value' => $result['montant']];
            }

            if (!empty($result['lien'])) {
                $card['actions'] = [['label' => 'Voir profil', 'url' => $result['lien'], 'icon' => 'fas fa-user']];
            } elseif (!empty($result['lien_inscription'])) {
                $card['actions'] = [['label' => 'Voir', 'url' => $result['lien_inscription'], 'icon' => 'fas fa-eye']];
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
            'affectes' => 'Affectés',
            'reaffectes' => 'Réaffectés',
            'non_affectes' => 'Non affectés',
            'formule' => 'Formule',
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
