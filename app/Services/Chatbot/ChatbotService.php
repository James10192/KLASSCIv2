<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ChatbotActionLog;
use App\Models\ChatbotDisplayTemplate;
use App\Models\ChatbotKnowledgeBase;
use App\Models\ChatbotUserPreference;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Service principal du Chatbot KLASSCI
 * 
 * Orchestration complète :
 * 1. Détection intent depuis message utilisateur
 * 2. Récupération connaissance (cache ou exploration)
 * 3. Vérification permissions Spatie
 * 4. Appel Groq API avec function calling
 * 5. Exécution requêtes Eloquent
 * 6. Formatage réponse avec templates
 * 7. Logging audit trail
 */
class ChatbotService
{
    protected ChatbotExplorerService $explorer;
    protected ChatbotLLMService $llm;
    protected ChatbotNavigationService $navigation;
    protected ChatbotSetupGuideService $setupGuide;
    protected ?string $previousIntent = null;

    public function __construct(
        ChatbotExplorerService $explorer,
        ChatbotLLMService $llm,
        ChatbotNavigationService $navigation,
        ChatbotSetupGuideService $setupGuide
    ) {
        $this->explorer = $explorer;
        $this->llm = $llm;
        $this->navigation = $navigation;
        $this->setupGuide = $setupGuide;
    }

    /**
     * Envoyer un message et obtenir une réponse
     */
    public function sendMessage(string $message, ?string $sessionId = null, ?array $clientContext = null): array
    {
        $user = Auth::user();
        $startTime = microtime(true);

        Log::info('🤖 ChatbotService - START sendMessage', [
            'user_id' => $user->id,
            'message' => $message,
            'session_id' => $sessionId,
        ]);

        try {
            // 1. Récupérer ou créer la conversation
            $conversation = $this->getOrCreateConversation($user->id, $sessionId);

            $this->previousIntent = $conversation->context['last_intent'] ?? null;
            $preferences = $this->getUserPreferences($user->id);
            $preferredNameCandidate = $this->detectPreferredName($message);
            $memoryAction = $this->buildMemoryAction($preferredNameCandidate, $preferences);

            // 2. Enregistrer le message utilisateur
            $userMessage = ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $message,
                'display_type' => 'text',
            ]);

            if ($clientContext || $preferences) {
                $conversation->update([
                    'context' => array_filter(array_merge($conversation->context ?? [], [
                        'last_page_url' => $clientContext['current_url'] ?? null,
                        'last_page_path' => $clientContext['current_path'] ?? null,
                        'last_page_title' => $clientContext['page_title'] ?? null,
                        'user_preferences' => $preferences ? $preferences->only([
                            'preferred_name',
                            'response_style',
                            'response_tone',
                            'clarification_mode',
                            'notes',
                        ]) : null,
                    ])),
                ]);
            }

            $pendingAction = $conversation->context['pending_action'] ?? null;
            if ($pendingAction) {
                if ($this->isAffirmativeMessage($message)) {
                    return $this->respondWithPendingAction($conversation, $pendingAction, $memoryAction);
                }

                if ($this->isNegativeMessage($message)) {
                    $this->clearPendingAction($conversation);
                }
            }

            if ($this->shouldPromptForHelp($message)) {
                return $this->respondWithHelpPrompt($conversation, $clientContext, $preferences, $memoryAction, $message);
            }

            // 3. Décision LLM (intent, filtres, affichage)
            $llmDecision = $this->llm->decide($conversation, $message);

            // 4. Déterminer l'intent
            $intent = $llmDecision['intent'] ?? $this->detectIntent($message, $conversation);
            Log::info('🎯 Intent detected', ['intent' => $intent]);

            if ($intent === 'setup_guide' && ! $this->isExplicitGuideRequest($message)) {
                $intent = $this->detectIntent($message, $conversation);
                if ($intent === 'setup_guide') {
                    $intent = 'help_context';
                }
            }

            if ($intent === 'help_context' && $this->isHelpMessage($message)) {
                return $this->respondWithHelpPrompt($conversation, $clientContext, $preferences, $memoryAction, $message);
            }

            if ($intent === 'setup_guide') {
                $scope = $this->resolveSetupScope($message, $llmDecision['filters'] ?? [], $clientContext, $conversation);
                $fullGuide = $this->shouldShowFullGuide($message, $llmDecision['filters'] ?? []);
                $stepLimit = $preferences->response_style === 'court' ? 1 : 3;
                return $this->respondWithSetupGuide($conversation, $user, $scope, $fullGuide, $memoryAction, $stepLimit, $message, $preferences, $intent);
            }

            if ($this->isActionRequest($message)) {
                $actionResponse = $this->respondWithActionGuidance($conversation, $user, $intent, $message, $preferences, $memoryAction);
                if ($actionResponse) {
                    return $actionResponse;
                }

                $fallbackText = $llmDecision['response_text'] ?? "D'accord. Dis-moi ce que tu veux faire exactement et je te guide.";
                return $this->respondWithSimpleMessage($conversation, $fallbackText, $memoryAction, $intent, $message, $preferences);
            }

            // Répondre immédiatement aux salutations pour éviter une exploration inutile
            if ($intent === 'greeting' && empty($llmDecision['intent'])) {
                return $this->respondWithHelpPrompt($conversation, $clientContext, $preferences, $memoryAction, $message);
            }

            // Réponse textuelle directe (pas de récupération de données)
            if (($llmDecision['display'] ?? null) === 'text' && empty($llmDecision['intent'])) {
                $textResponse = $llmDecision['response_text'] ?? "Compris.";
                return $this->respondWithSimpleMessage($conversation, $textResponse, $memoryAction, $intent, $message, $preferences);
            }

            // 5. Récupérer la connaissance (cache ou exploration)
            $knowledge = $this->getOrExploreKnowledge($intent, $message, $user);

            if (!$knowledge) {
                return $this->createErrorResponse(
                    $conversation,
                    "Désolé, je ne sais pas encore comment récupérer cette information. Pouvez-vous reformuler ou demander autre chose ?",
                    $memoryAction
                );
            }

            // 5. Vérifier les permissions
            $userRoles = $user->roles->pluck('name')->toArray();
            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

            if (!$this->checkPermissions($knowledge, $userRoles, $userPermissions)) {
                return $this->createErrorResponse(
                    $conversation,
                    "Vous n'avez pas l'autorisation d'accéder à cette information.",
                    $memoryAction
                );
            }

            $llmFilters = $llmDecision['filters'] ?? [];
            $requestedLimit = $llmDecision['limit'] ?? null;

            // Ajouter le message raw pour détecter type de frais
            $llmFilters['_raw_message'] = $message;

            // 6. Vérification anti-hallucination : vérifier que les données existent réellement
            $verification = $this->navigation->verifyDataExists($intent, $llmFilters, $knowledge);

            if (!$verification['exists']) {
                // Données n'existent pas → réponse contextuelle avec suggestion
                $responseText = $verification['suggestion'];
                $actionLabel = $verification['action_label'] ?? 'En savoir plus';
                $deepLink = $verification['deep_link'];

                $assistantMessage = ChatbotMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $responseText,
                    'display_type' => 'text',
                    'deep_link' => $deepLink,
                    'display_data' => $this->attachMemoryAction(null, $memoryAction),
                    'metadata' => [
                        'intent' => $intent,
                        'verification_level' => $verification['level'],
                        'action_label' => $actionLabel,
                        'partial_data' => $verification['data'] ?? null,
                    ],
                ]);

                return [
                    'success' => true,
                    'message' => $assistantMessage->content,
                    'display_type' => 'text',
                    'display_data' => $assistantMessage->display_data,
                    'deep_link' => $deepLink,
                    'action_label' => $actionLabel,
                    'conversation_id' => $conversation->session_id,
                ];
            }

            // 7. Exécuter la requête pour récupérer les données vérifiées
            $data = $this->executeDataRetrieval($knowledge, $message, $user, $llmFilters, $requestedLimit, $verification);

            if (empty($data['results'])) {
                // Construire un message explicite avec les critères recherchés
                $criteriaText = $this->buildCriteriaText($intent, $data['filters'] ?? $llmFilters);
                $noResultMessage = "Je n'ai trouvé aucun résultat pour votre recherche";
                if ($criteriaText) {
                    $noResultMessage .= " : " . $criteriaText . ".";
                } else {
                    $noResultMessage .= ".";
                }

                $assistantMessage = ChatbotMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $noResultMessage,
                    'display_type' => 'text',
                    'display_data' => $this->attachMemoryAction(null, $memoryAction),
                ]);

                $this->updateConversationTitleIfNeeded($conversation, $intent, $message, $preferences);

                return [
                    'success' => true,
                    'message' => $assistantMessage->content,
                    'display_type' => 'text',
                    'display_data' => $assistantMessage->display_data,
                    'conversation_id' => $conversation->session_id,
                ];
            }

            // 7. Formater la réponse avec template
            $formattedResponse = $this->formatResponse($data, $knowledge, $llmDecision);
            $formattedResponse['display_data'] = $this->attachMemoryAction($formattedResponse['display_data'] ?? null, $memoryAction);

            // 8. Enregistrer le message assistant
            $assistantMessage = ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $formattedResponse['text'],
                'display_type' => $formattedResponse['display_type'],
                'display_data' => $formattedResponse['display_data'] ?? null,
                'deep_link' => $formattedResponse['deep_link'] ?? null,
                'metadata' => [
                    'intent' => $intent,
                    'knowledge_id' => $knowledge->id,
                    'results_count' => count($data['results']),
                    'filters' => $data['filters'] ?? [],
                    'display' => $formattedResponse['display_type'],
                    'follow_up' => $formattedResponse['follow_up'] ?? null,
                ],
            ]);

            // 9. Logger l'action
            ChatbotActionLog::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'action_type' => 'retrieve',
                'model_type' => $knowledge->model,
                'action_data' => [
                    'intent' => $intent,
                    'filters' => $data['filters'] ?? [],
                    'results_count' => count($data['results']),
                ],
                'status' => 'success',
            ]);

            // 10. Mettre à jour usage de la connaissance
            $knowledge->incrementUsage();

            // 11. Mettre à jour la conversation
            $conversation->update([
                'last_activity_at' => now(),
                'context' => array_filter([
                    'last_intent' => $intent,
                    'last_filters' => $data['filters'] ?? [],
                    'last_display' => $formattedResponse['display_type'] ?? null,
                ]),
            ]);

            $this->updateConversationTitleIfNeeded($conversation, $intent, $message, $preferences);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('✅ ChatbotService - SUCCESS', [
                'duration_ms' => $duration,
                'results_count' => count($data['results']),
            ]);

            return [
                'success' => true,
                'message' => $assistantMessage->content,
                'display_type' => $assistantMessage->display_type,
                'display_data' => $assistantMessage->display_data,
                'deep_link' => $assistantMessage->deep_link,
                'conversation_id' => $conversation->session_id,
            ];

        } catch (\Exception $e) {
            Log::error('❌ ChatbotService - ERROR', [
                'user_id' => $user->id,
                'message' => $message,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => "Désolé, une erreur s'est produite. Veuillez réessayer.",
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Détecter l'intent depuis le message utilisateur
     */
    protected function detectIntent(string $message, ChatbotConversation $conversation): string
    {
        $messageLower = strtolower($message);

        // Gestion des salutations basiques
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $messageLower);
        $words = array_filter(explode(' ', $normalized));
        $greetingWords = ['salut', 'bonjour', 'bonsoir', 'coucou', 'hello', 'hi', 'yo'];

        if (!empty($words) && count($words) <= 3) {
            $allGreetings = collect($words)->every(function ($word) use ($greetingWords) {
                return in_array($word, $greetingWords, true);
            });

            if ($allGreetings) {
                return 'greeting';
            }
        }

        if ($this->isHelpMessage($messageLower)) {
            return 'help_context';
        }

        // Mapping mots-clés → intents
            $intentMap = [
                'paiement' => 'get_paiements',
                'paye' => 'get_paiements',
                'payment' => 'get_paiements',
                'étudiant' => 'get_etudiants',
                'etudiant' => 'get_etudiants',
            'élève' => 'get_etudiants',
            'eleve' => 'get_etudiants',
            'inscription' => 'get_inscriptions',
            'inscrit' => 'get_inscriptions',
                'classe' => 'get_classes',
                'frais' => 'get_frais',
                'tarif' => 'get_frais',
                'categorie' => 'get_frais',
                'setup' => 'setup_guide',
                'démarrage' => 'setup_guide',
                'demarrage' => 'setup_guide',
                'mise en route' => 'setup_guide',
                'guide' => 'setup_guide',
                'étapes' => 'setup_guide',
                'etapes' => 'setup_guide',
                'checklist' => 'setup_guide',
        ];

        foreach ($intentMap as $keyword => $intent) {
            if (str_contains($messageLower, $keyword)) {
                return $intent;
            }
        }

        $lastAssistantIntent = $conversation->messages()
            ->where('role', 'assistant')
            ->latest()
            ->value('metadata->intent');

        if ($lastAssistantIntent) {
            return $lastAssistantIntent;
        }

        // Par défaut
        return 'get_paiements';
    }

    protected function isHelpMessage(string $message): bool
    {
        $normalized = trim(mb_strtolower($message, 'UTF-8'));
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalized) ?: '';

        return in_array($normalized, ['aide', 'help', 'assistance', 'besoin daide', 'aidez moi'], true)
            || str_contains($normalized, 'aide')
            || str_contains($normalized, 'help')
            || str_contains($normalized, 'assistance');
    }

    protected function isExplicitGuideRequest(string $message): bool
    {
        $normalized = mb_strtolower($message, 'UTF-8');

        return str_contains($normalized, 'guide')
            || str_contains($normalized, 'checklist')
            || str_contains($normalized, 'étapes')
            || str_contains($normalized, 'etapes')
            || str_contains($normalized, 'mise en route')
            || str_contains($normalized, 'démarrer')
            || str_contains($normalized, 'demarrer')
            || str_contains($normalized, 'plan de démarrage')
            || str_contains($normalized, 'plan de demarrage');
    }

    protected function isActionRequest(string $message): bool
    {
        $normalized = mb_strtolower($message, 'UTF-8');

        $verbs = [
            'je veux',
            'je dois',
            'comment',
            'faire',
            'créer',
            'creer',
            'ajouter',
            'inscrire',
            'enregistrer',
            'programmer',
            'saisir',
        ];

        foreach ($verbs as $verb) {
            if (str_contains($normalized, $verb)) {
                return true;
            }
        }

        return false;
    }

    protected function isAffirmativeMessage(string $message): bool
    {
        $normalized = trim(mb_strtolower($message, 'UTF-8'));
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalized) ?? '';

        $keywords = [
            'oui',
            'ok',
            'okay',
            'daccord',
            'd accord',
            'oui stp',
            'vas y',
            'allons y',
            'je veux',
            'oui je veux',
        ];

        foreach ($keywords as $keyword) {
            if ($normalized === $keyword || str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function isNegativeMessage(string $message): bool
    {
        $normalized = trim(mb_strtolower($message, 'UTF-8'));
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalized) ?? '';

        $keywords = [
            'non',
            'pas maintenant',
            'plus tard',
            'annule',
            'stop',
            'laisse',
        ];

        foreach ($keywords as $keyword) {
            if ($normalized === $keyword || str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function clearPendingAction(ChatbotConversation $conversation): void
    {
        $context = $conversation->context ?? [];
        $context['pending_action'] = null;
        $context['pending_action_payload'] = null;
        $conversation->update(['context' => $context]);
    }

    protected function respondWithPendingAction(
        ChatbotConversation $conversation,
        string $pendingAction,
        ?array $memoryAction
    ): ?array {
        $payload = $conversation->context['pending_action_payload'] ?? [];

        return match ($pendingAction) {
            'frais_category_form' => $this->buildFormResponse($conversation, 'frais_category', $payload, $memoryAction),
            'frais_config_form' => $this->buildFormResponse($conversation, 'frais_config', $payload, $memoryAction),
            default => null,
        };
    }

    protected function shouldPromptForHelp(string $message): bool
    {
        if (!$this->isHelpMessage($message)) {
            return false;
        }

        $normalized = mb_strtolower($message, 'UTF-8');

        return !(
            str_contains($normalized, 'setup')
            || str_contains($normalized, 'configuration')
            || str_contains($normalized, 'configurer')
            || str_contains($normalized, 'démarrage')
            || str_contains($normalized, 'demarrage')
            || str_contains($normalized, 'commencer')
            || str_contains($normalized, 'guide')
            || str_contains($normalized, 'mise en route')
        );
    }

    protected function getUserPreferences(int $userId): ChatbotUserPreference
    {
        return ChatbotUserPreference::firstOrCreate(
            ['user_id' => $userId],
            [
                'response_style' => 'standard',
                'response_tone' => 'pedagogique',
                'clarification_mode' => 'auto',
            ]
        );
    }

    protected function detectPreferredName(string $message): ?string
    {
        $patterns = [
            '/\bappelle\s*moi\s+([\p{L}\p{M}\-\s]{2,50})/iu',
            '/\bje\s*m\'appelle\s+([\p{L}\p{M}\-\s]{2,50})/iu',
            '/\bmon\s+nom\s+est\s+([\p{L}\p{M}\-\s]{2,50})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    protected function buildMemoryAction(?string $preferredName, ChatbotUserPreference $preferences): ?array
    {
        if (!$preferredName) {
            return null;
        }

        if ($preferences->preferred_name && mb_strtolower($preferences->preferred_name, 'UTF-8') === mb_strtolower($preferredName, 'UTF-8')) {
            return null;
        }

        return [
            'label' => "Sauvegarder \"{$preferredName}\"",
            'action' => 'save_preferred_name',
            'value' => $preferredName,
        ];
    }

    protected function resolveSetupScope(
        string $message,
        array $filters,
        ?array $clientContext = null,
        ?ChatbotConversation $conversation = null
    ): string {
        if (!empty($filters['setup_scope'])) {
            return (string) $filters['setup_scope'];
        }

        $contextScope = $conversation?->context['last_filters']['setup_scope'] ?? null;
        if ($contextScope) {
            return (string) $contextScope;
        }

        $messageLower = mb_strtolower($message, 'UTF-8');

        if (str_contains($messageLower, 'financ') || str_contains($messageLower, 'frais') || str_contains($messageLower, 'paiement')) {
            return 'financier';
        }

        if (str_contains($messageLower, 'acad') || str_contains($messageLower, 'planning') || str_contains($messageLower, 'emploi du temps')) {
            return 'academique';
        }

        if (str_contains($messageLower, 'evaluation') || str_contains($messageLower, 'notes') || str_contains($messageLower, 'absence') || str_contains($messageLower, 'bulletin')) {
            return 'pedagogie';
        }

        $pagePath = $clientContext['current_path'] ?? null;
        if ($pagePath) {
            $pagePath = mb_strtolower($pagePath, 'UTF-8');
            if (str_contains($pagePath, 'inscriptions') || str_contains($pagePath, 'paiements') || str_contains($pagePath, 'frais')) {
                return 'financier';
            }
            if (str_contains($pagePath, 'emploi-temps') || str_contains($pagePath, 'planning') || str_contains($pagePath, 'classes')) {
                return 'academique';
            }
            if (str_contains($pagePath, 'evaluations') || str_contains($pagePath, 'notes') || str_contains($pagePath, 'attendances')) {
                return 'pedagogie';
            }
        }

        return 'global';
    }

    protected function shouldShowFullGuide(string $message, array $filters): bool
    {
        if (!empty($filters['full_guide'])) {
            return (bool) $filters['full_guide'];
        }

        $normalized = mb_strtolower($message, 'UTF-8');

        return str_contains($normalized, 'tout le guide')
            || str_contains($normalized, 'guide complet')
            || str_contains($normalized, 'phase complète')
            || str_contains($normalized, 'toute la phase')
            || str_contains($normalized, 'tout afficher');
    }

    protected function respondWithActionGuidance(
        ChatbotConversation $conversation,
        $user,
        string $intent,
        string $message,
        ChatbotUserPreference $preferences,
        ?array $memoryAction
    ): ?array {
        $actionMap = [
            'get_inscriptions' => ['scope' => 'financier', 'step' => 'inscriptions', 'route' => 'esbtp.inscriptions.create'],
            'get_paiements' => ['scope' => 'financier', 'step' => 'paiements', 'route' => 'esbtp.paiements.index'],
            'get_evaluations' => ['scope' => 'pedagogie', 'step' => 'evaluations', 'route' => 'esbtp.evaluations.index'],
            'get_notes' => ['scope' => 'pedagogie', 'step' => 'notes', 'route' => 'esbtp.notes.index'],
            'get_attendances' => ['scope' => 'pedagogie', 'step' => 'absences', 'route' => 'esbtp.attendances.index'],
        ];

        if (!isset($actionMap[$intent])) {
            return null;
        }

        $config = $actionMap[$intent];
        $stepContext = $this->setupGuide->getStepContext($user, $config['scope'], $config['step']);
        $deepLink = $stepContext['deep_link'] ?? ($config['route'] ? route($config['route']) : null);
        $missingPrerequisites = $stepContext['missing_prerequisite_ids'] ?? [];
        $missingPreviewLimit = $preferences->response_style === 'court' ? 1 : 3;
        $missingPreview = $this->setupGuide->buildMissingStepsPreview(
            $user,
            $config['scope'],
            $missingPrerequisites,
            $missingPreviewLimit
        );

        $pendingAction = null;
        $pendingPayload = null;
        $followUpActions = [];

        if ($intent === 'get_inscriptions' && $missingPreview) {
            if (in_array('frais_categories', $missingPrerequisites, true)) {
                $pendingAction = 'frais_category_form';
                $followUpActions[] = [
                    'label' => 'Créer la catégorie ici',
                    'action' => 'open_form',
                    'value' => 'frais_category',
                ];
            } elseif (in_array('frais_mandatory_configs', $missingPrerequisites, true)) {
                $pendingAction = 'frais_config_form';
                $followUpActions[] = [
                    'label' => 'Configurer par classe ici',
                    'action' => 'open_form',
                    'value' => 'frais_config',
                ];
            }
        }

        $deepLink = $this->resolveActionDeepLink($deepLink, $missingPreview);
        $responseText = $this->buildActionResponseText(
            $intent,
            $stepContext,
            $missingPreview,
            $conversation,
            $message,
            $preferences
        );

        if (!$responseText) {
            $stepTitle = $stepContext['step']['title'] ?? 'cette action';
            $missingTitles = $stepContext['missing_prerequisite_titles'] ?? [];
            $missingTitles = array_values(array_filter($missingTitles));

            if (!empty($missingTitles)) {
                $responseText = sprintf(
                    'Avant de %s, il faut d\'abord compléter : %s. Je t\'affiche la checklist juste en dessous. Tu veux que j\'ouvre la page ?'
                    ,
                    mb_strtolower($stepTitle, 'UTF-8'),
                    implode(', ', $missingTitles)
                );
            } else {
                $responseText = sprintf(
                    'Tu peux lancer %s depuis la page dédiée. Je t\'ai mis le bouton d\'accès. Tu veux que je détaille un champ du formulaire ?'
                    ,
                    mb_strtolower($stepTitle, 'UTF-8')
                );
            }
        }

        $displayType = $missingPreview ? 'checklist' : 'text';
        $displayData = $missingPreview ?: null;
        if (!empty($followUpActions)) {
            $displayData['follow_up_actions'] = array_values($followUpActions);
        }
        $displayData = $this->attachMemoryAction($displayData, $memoryAction);

        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $responseText,
            'display_type' => $displayType,
            'display_data' => $displayData,
            'deep_link' => $deepLink,
        ]);

        $conversation->update([
            'last_activity_at' => now(),
            'context' => array_filter([
                'last_intent' => $intent,
                'last_filters' => [],
                'last_display' => $displayType,
                'pending_action' => $pendingAction,
                'pending_action_payload' => $pendingPayload,
            ]),
        ]);

        $this->updateConversationTitleIfNeeded($conversation, $intent, $message, $preferences);

        return [
            'success' => true,
            'message' => $assistantMessage->content,
            'display_type' => $displayType,
            'display_data' => $assistantMessage->display_data,
            'deep_link' => $assistantMessage->deep_link,
            'conversation_id' => $conversation->session_id,
        ];
    }

    protected function resolveActionDeepLink(?string $deepLink, ?array $missingPreview): ?string
    {
        if (!$missingPreview) {
            return $deepLink;
        }

        $firstStep = $missingPreview['sections'][0]['steps'][0] ?? null;
        if ($firstStep && !empty($firstStep['deep_link'])) {
            return $firstStep['deep_link'];
        }

        return $deepLink;
    }

    protected function buildActionResponseText(
        string $intent,
        array $stepContext,
        ?array $missingPreview,
        ChatbotConversation $conversation,
        string $message,
        ?ChatbotUserPreference $preferences
    ): ?string {
        $missingTitles = $stepContext['missing_prerequisite_titles'] ?? [];
        $missingTitles = array_values(array_filter($missingTitles));

        if (!empty($missingTitles)) {
            $missingList = implode(', ', $missingTitles);
            $baseResponse = "Avant de continuer, il manque encore : {$missingList}. Je te mets la checklist juste en dessous. Tu veux que je fasse l'étape manquante ici ?";
            $rewritten = $this->llm->rewriteBackendResponse(
                $conversation,
                $message,
                $intent,
                $baseResponse,
                $stepContext,
                $preferences
            );

            return $rewritten ?: $baseResponse;
        }

        $response = $this->llm->generateActionGuidance(
            $conversation,
            $message,
            $intent,
            $stepContext,
            $preferences
        );

        if ($response) {
            return $response;
        }

        if ($intent === 'get_inscriptions') {
            return $this->buildInscriptionActionText($stepContext, $missingPreview);
        }

        return null;
    }

    protected function buildInscriptionActionText(array $stepContext, ?array $missingPreview): string
    {
        $missingIds = $stepContext['missing_prerequisite_ids'] ?? [];

        if ($missingPreview && in_array('frais_categories', $missingIds, true)) {
            return "Avant l'inscription, il faut d'abord créer une catégorie de frais obligatoire. "
                . "Menu Comptabilité > Gestion des frais, puis bouton \"Nouvelle Catégorie\" "
                . "(ou \"Ajouter le premier frais académique\"). "
                . "Je peux la créer pour toi ici, tu veux que j'ouvre le formulaire ?";
        }

        if ($missingPreview && in_array('frais_mandatory_configs', $missingIds, true)) {
            return "Les catégories obligatoires existent, il reste à configurer les montants par classe. "
                . "Va dans Gestion des frais > Configuration par classe. "
                . "Je peux aussi le faire ici, tu veux le formulaire ?";
        }

        if ($missingPreview) {
            return "Il manque encore une ou deux étapes avant l'inscription. "
                . "Je te mets la checklist juste en dessous. Tu veux que j'ouvre la première page ?";
        }

        return "Pour créer une inscription, ouvre Étudiants > Nouvelle inscription dans la barre latérale. "
            . "Renseigne les infos personnelles, puis choisis la classe (filière/niveau/année se remplissent), "
            . "et sélectionne le statut d'affectation. Tu veux que je détaille un champ du formulaire ?";
    }

    public function buildFormResponse(
        ChatbotConversation $conversation,
        string $formKey,
        array $payload,
        ?array $memoryAction
    ): ?array {
        $form = match ($formKey) {
            'frais_category' => $this->buildMandatoryFraisCategoryFormData($conversation),
            'frais_config' => $this->buildFraisConfigFormData($conversation, $payload),
            default => null,
        };

        if (!$form) {
            return null;
        }

        $displayData = $this->attachMemoryAction($form['display_data'], $memoryAction);

        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $form['message'],
            'display_type' => 'form',
            'display_data' => $displayData,
        ]);

        $context = $conversation->context ?? [];
        $context['pending_action'] = null;
        $context['pending_action_payload'] = null;
        $context['last_display'] = 'form';

        $conversation->update([
            'last_activity_at' => now(),
            'context' => $context,
        ]);

        return [
            'success' => true,
            'message' => $assistantMessage->content,
            'display_type' => 'form',
            'display_data' => $assistantMessage->display_data,
            'conversation_id' => $conversation->session_id,
        ];
    }

    protected function buildMandatoryFraisCategoryFormData(ChatbotConversation $conversation): array
    {
        $fields = [
            [
                'name' => 'name',
                'label' => 'Nom de la catégorie',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Frais d\'inscription',
            ],
            [
                'name' => 'code',
                'label' => 'Code unique',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'INSCRIPTION',
                'help' => 'Le code sera converti en majuscules.',
            ],
            [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'textarea',
                'placeholder' => 'Frais d\'inscription obligatoire',
            ],
            [
                'name' => 'default_amount',
                'label' => 'Montant par défaut (FCFA)',
                'type' => 'number',
                'required' => true,
                'min' => 0,
                'step' => 1,
            ],
            [
                'name' => 'payment_deadline_days',
                'label' => 'Délai de paiement (jours)',
                'type' => 'number',
                'required' => true,
                'min' => 1,
                'max' => 365,
                'value' => 30,
            ],
        ];

        return [
            'message' => "Parfait. Remplis ce formulaire pour créer une catégorie de frais obligatoire.",
            'display_data' => [
                'title' => 'Créer une catégorie obligatoire',
                'description' => 'Catégorie obligatoire pour les inscriptions.',
                'action_url' => Route::has('chatbot.forms.frais-category.store')
                    ? route('chatbot.forms.frais-category.store')
                    : null,
                'action_method' => 'POST',
                'submit_label' => 'Créer la catégorie',
                'fields' => $fields,
                'hidden_fields' => [
                    'conversation_id' => $conversation->session_id,
                ],
            ],
        ];
    }

    protected function buildFraisConfigFormData(ChatbotConversation $conversation, array $payload): ?array
    {
        $categoryId = $payload['category_id'] ?? null;
        $category = $categoryId
            ? ESBTPFraisCategory::find($categoryId)
            : ESBTPFraisCategory::mandatory()->orderBy('id')->first();

        if (!$category) {
            return null;
        }

        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $fields = [
            [
                'name' => 'filiere_id',
                'label' => 'Filière',
                'type' => 'select',
                'required' => true,
                'options' => $filieres->map(fn ($item) => ['value' => $item->id, 'label' => $item->name])->toArray(),
            ],
            [
                'name' => 'niveau_id',
                'label' => 'Niveau',
                'type' => 'select',
                'required' => true,
                'options' => $niveaux->map(fn ($item) => ['value' => $item->id, 'label' => $item->name])->toArray(),
            ],
            [
                'name' => 'amount_affecte',
                'label' => 'Montant affecté (FCFA)',
                'type' => 'number',
                'required' => true,
                'min' => 0,
                'step' => 1,
            ],
            [
                'name' => 'amount_reaffecte',
                'label' => 'Montant réaffecté (FCFA)',
                'type' => 'number',
                'required' => true,
                'min' => 0,
                'step' => 1,
            ],
            [
                'name' => 'amount_non_affecte',
                'label' => 'Montant non affecté (FCFA)',
                'type' => 'number',
                'required' => true,
                'min' => 0,
                'step' => 1,
            ],
            [
                'name' => 'deadline_days',
                'label' => 'Délai de paiement (jours)',
                'type' => 'number',
                'required' => true,
                'min' => 1,
                'max' => 365,
                'value' => 30,
            ],
            [
                'name' => 'installments_allowed',
                'label' => 'Paiement en plusieurs fois',
                'type' => 'checkbox',
            ],
            [
                'name' => 'max_installments',
                'label' => 'Nombre max de tranches',
                'type' => 'number',
                'min' => 1,
                'max' => 12,
                'value' => 1,
            ],
            [
                'name' => 'early_payment_discount',
                'label' => 'Remise paiement anticipé (%)',
                'type' => 'number',
                'min' => 0,
                'max' => 100,
                'value' => 0,
            ],
        ];

        return [
            'message' => "D'accord. Configure les montants pour \"{$category->name}\".",
            'display_data' => [
                'title' => 'Configuration par classe',
                'description' => "Frais obligatoire : {$category->name}",
                'action_url' => Route::has('chatbot.forms.frais-config.store')
                    ? route('chatbot.forms.frais-config.store')
                    : null,
                'action_method' => 'POST',
                'submit_label' => 'Enregistrer la configuration',
                'fields' => $fields,
                'hidden_fields' => [
                    'conversation_id' => $conversation->session_id,
                    'category_id' => $category->id,
                ],
            ],
        ];
    }

    protected function respondWithSetupGuide(
        ChatbotConversation $conversation,
        $user,
        string $scope,
        bool $fullGuide,
        ?array $memoryAction,
        int $stepLimit,
        string $message,
        ChatbotUserPreference $preferences,
        ?string $intent
    ): array {
        $guide = $fullGuide
            ? $this->setupGuide->buildGuide($user, $scope)
            : $this->setupGuide->buildGuidePreview($user, $scope, $stepLimit);

        $displayData = $guide;
        if (!empty($guide['is_preview'])) {
            $displayData['follow_up'] = [
                'Voir toute la phase',
                'Voir tout le guide',
            ];
        }

        $displayData = $this->attachMemoryAction($displayData, $memoryAction);

        $introText = $this->llm->generateGuideIntro($conversation, $message, $scope, $displayData, $preferences);
        if (!$introText) {
            $introText = 'Voici les prochaines étapes que je vous recommande :';
        }

        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $introText,
            'display_type' => 'checklist',
            'display_data' => $displayData,
        ]);

        $conversation->update([
            'last_activity_at' => now(),
            'context' => array_filter([
                'last_intent' => 'setup_guide',
                'last_filters' => [
                    'setup_scope' => $scope,
                    'full_guide' => $fullGuide,
                ],
                'last_display' => 'checklist',
            ]),
        ]);

        $this->updateConversationTitleIfNeeded($conversation, $intent, $message, $preferences);

        return [
            'success' => true,
            'message' => $assistantMessage->content,
            'display_type' => $assistantMessage->display_type,
            'display_data' => $assistantMessage->display_data,
            'conversation_id' => $conversation->session_id,
        ];
    }

    protected function respondWithHelpPrompt(
        ChatbotConversation $conversation,
        ?array $clientContext = null,
        ?ChatbotUserPreference $preferences = null,
        ?array $memoryAction = null,
        ?string $message = null
    ): array {
        $pageTitle = $clientContext['page_title'] ?? null;
        $pagePath = $clientContext['current_path'] ?? null;

        $contextLine = $pageTitle ? "Je vois que vous êtes sur : {$pageTitle}." : null;
        if (!$contextLine && $pagePath) {
            $contextLine = "Je vois que vous êtes sur : {$pagePath}.";
        }

        $followUp = $this->buildHelpSuggestions($pagePath);
        $prompt = $this->llm->generateHelpPrompt($conversation, (string) $message, $contextLine, $followUp, $preferences);
        if (!$prompt) {
            $preferredName = $preferences?->preferred_name;
            $prompt = 'Dites-moi ce que vous voulez faire et je vous guide étape par étape.';
            if ($preferredName) {
                $prompt = "Bonjour {$preferredName}, " . $prompt;
            }
            if ($contextLine) {
                $prompt = $contextLine . ' ' . $prompt;
            }
        }

        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $prompt,
            'display_type' => 'text',
            'display_data' => $this->attachMemoryAction([
                'follow_up' => $followUp,
            ], $memoryAction),
        ]);

        $this->updateConversationTitleIfNeeded($conversation, 'help_context', (string) $message, $preferences);

        return [
            'success' => true,
            'message' => $assistantMessage->content,
            'display_type' => $assistantMessage->display_type,
            'display_data' => $assistantMessage->display_data,
            'conversation_id' => $conversation->session_id,
        ];
    }

    protected function buildHelpSuggestions(?string $pagePath): array
    {
        if ($pagePath) {
            $path = mb_strtolower($pagePath, 'UTF-8');
            if (str_contains($path, 'inscriptions')) {
                return ['Nouvelle inscription', 'Valider une inscription', 'Paiement associé'];
            }
            if (str_contains($path, 'paiements')) {
                return ['Créer un paiement', 'Valider paiements', 'Suivi par catégorie'];
            }
            if (str_contains($path, 'emploi-temps') || str_contains($path, 'planning')) {
                return ['Créer un planning', 'Générer emploi du temps', 'Assigner enseignants'];
            }
            if (str_contains($path, 'evaluations') || str_contains($path, 'notes')) {
                return ['Programmer évaluation', 'Saisie rapide notes', 'Publier notes'];
            }
        }

        return ['Configuration académique', 'Frais & inscriptions', 'Évaluations & notes'];
    }

    protected function attachMemoryAction(?array $displayData, ?array $memoryAction): ?array
    {
        if (!$memoryAction) {
            return $displayData;
        }

        $displayData = $displayData ?? [];
        $displayData['follow_up_actions'] = array_values(array_filter(array_merge(
            $displayData['follow_up_actions'] ?? [],
            [$memoryAction]
        )));

        return $displayData;
    }

    protected function updateConversationTitleIfNeeded(
        ChatbotConversation $conversation,
        ?string $intent,
        string $message,
        ?ChatbotUserPreference $preferences = null
    ): void {
        if (!$intent || in_array($intent, ['greeting', 'help_context'], true)) {
            return;
        }

        $previousIntent = $this->previousIntent;
        $titleExists = !empty($conversation->title);

        if ($titleExists && $previousIntent === $intent) {
            return;
        }

        $newTitle = $this->llm->generateConversationTitle($conversation, $message, $intent, $preferences);
        $newTitle = $this->sanitizeTitle($newTitle ?: $message);

        if ($newTitle && $newTitle !== $conversation->title) {
            $conversation->update(['title' => $newTitle]);
        }
    }

    protected function sanitizeTitle(string $title): string
    {
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? '');
        $title = trim($title, " \t\n\r\0\x0B\"'");

        if (mb_strlen($title, 'UTF-8') > 40) {
            $title = mb_substr($title, 0, 40, 'UTF-8');
            $title = rtrim($title);
        }

        return $title;
    }

    /**
     * Récupérer la connaissance (cache ou exploration)
     */
    protected function getOrExploreKnowledge(string $intent, string $message, $user): ?ChatbotKnowledgeBase
    {
        // 1. Chercher dans le cache
        $knowledge = $this->explorer->getKnowledge($intent);

        if ($knowledge) {
            Log::info('💾 Knowledge found in cache', ['intent' => $intent]);
            return $knowledge;
        }

        // 2. Sinon, explorer le code
        Log::info('🔍 Knowledge NOT in cache - Starting exploration', ['intent' => $intent]);

        $userRoles = $user->roles->pluck('name')->toArray();
        $exploredData = $this->explorer->explore($intent, $message, $userRoles);

        if (!$exploredData) {
            Log::warning('⚠️ Exploration failed', ['intent' => $intent]);
            return null;
        }

        // 3. Sauvegarder la nouvelle connaissance
        $knowledge = $this->explorer->saveKnowledge($exploredData);
        Log::info('✅ New knowledge saved', ['intent' => $intent, 'knowledge_id' => $knowledge->id]);

        return $knowledge;
    }

    /**
     * Vérifier les permissions
     */
    protected function checkPermissions(ChatbotKnowledgeBase $knowledge, array $userRoles, array $userPermissions): bool
    {
        // Vérifier les rôles
        if (!empty($knowledge->allowed_roles)) {
            $hasRole = false;
            foreach ($userRoles as $role) {
                if ($knowledge->isRoleAllowed($role)) {
                    $hasRole = true;
                    break;
                }
            }
            if (!$hasRole) {
                Log::warning('🚫 Role check failed', [
                    'required_roles' => $knowledge->allowed_roles,
                    'user_roles' => $userRoles,
                ]);
                return false;
            }
        }

        // Vérifier les permissions
        if (!empty($knowledge->required_permissions)) {
            if (!$knowledge->hasRequiredPermissions($userPermissions)) {
                Log::warning('🚫 Permission check failed', [
                    'required_permissions' => $knowledge->required_permissions,
                    'user_permissions' => $userPermissions,
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Exécuter la récupération des données
     */
    protected function executeDataRetrieval(
        ChatbotKnowledgeBase $knowledge,
        string $message,
        $user,
        array $llmFilters = [],
        ?int $requestedLimit = null,
        ?array $verification = null
    ): array
    {
        // Si la vérification a retourné des données directement, les utiliser
        if ($verification && isset($verification['data']['configurations'])) {
            // Cas spécial pour get_frais : utiliser les configurations trouvées
            $configurations = $verification['data']['configurations'];

            // Regrouper par combinaison unique (filiere_id, niveau_id, categorie_id)
            // Une combinaison = 1 configuration avec 3 types de tarifs (affecté, réaffecté, non affecté)
            $grouped = collect($configurations)->groupBy(function($config) {
                return implode('|', [
                    $config['config']->filiere_id,
                    $config['config']->niveau_id,
                    $config['config']->frais_category_id,
                ]);
            });

            // Pour chaque combinaison unique, prendre la première configuration
            // (toutes les configurations d'une même combinaison ont les mêmes montants affecté/réaffecté/non-affecté)
            $results = collect();
            foreach ($grouped as $key => $group) {
                $firstConfig = $group->first();
                $config = $firstConfig['config'];

                $categorieName = \DB::table('esbtp_frais_categories')
                    ->where('id', $config->frais_category_id)
                    ->value('name');

                // Vérifier si l'utilisateur a spécifié un type d'affectation
                // Le LLM utilise maintenant TOUJOURS la clé standardisée 'type_affectation'
                $typeAffectationFilter = isset($llmFilters['type_affectation'])
                    ? strtolower($llmFilters['type_affectation'])
                    : null;

                // Créer 3 objets pour les 3 types de tarifs
                // Afficher TOUS les types si non spécifié, sinon filtrer
                $affecteObj = (object) [
                    'id' => $config->id . '_affecte',
                    'categorie_id' => $config->frais_category_id,
                    'categorie_name' => $categorieName,
                    'type_tarif' => 'Affectés',
                    'filiere_id' => $config->filiere_id,
                    'niveau_id' => $config->niveau_id,
                    'amount' => $config->amount_affecte,
                    'effective_date' => $config->effective_date,
                    'is_active' => $config->is_active,
                ];

                $reaffecteObj = (object) [
                    'id' => $config->id . '_reaffecte',
                    'categorie_id' => $config->frais_category_id,
                    'categorie_name' => $categorieName,
                    'type_tarif' => 'Réaffectés',
                    'filiere_id' => $config->filiere_id,
                    'niveau_id' => $config->niveau_id,
                    'amount' => $config->amount_reaffecte,
                    'effective_date' => $config->effective_date,
                    'is_active' => $config->is_active,
                ];

                $nonAffecteObj = (object) [
                    'id' => $config->id . '_non_affecte',
                    'categorie_id' => $config->frais_category_id,
                    'categorie_name' => $categorieName,
                    'type_tarif' => 'Non affectés',
                    'filiere_id' => $config->filiere_id,
                    'niveau_id' => $config->niveau_id,
                    'amount' => $config->amount_non_affecte,
                    'effective_date' => $config->effective_date,
                    'is_active' => $config->is_active,
                ];

                // Filtrer selon la demande utilisateur
                if (!$typeAffectationFilter) {
                    // Pas de filtre → afficher tous les types
                    $results->push($affecteObj);
                    $results->push($reaffecteObj);
                    $results->push($nonAffecteObj);
                } elseif (str_contains($typeAffectationFilter, 'affecté') && !str_contains($typeAffectationFilter, 'non')) {
                    // "affectés" uniquement
                    $results->push($affecteObj);
                } elseif (str_contains($typeAffectationFilter, 'réaffecté') || str_contains($typeAffectationFilter, 'reaffecté')) {
                    // "réaffectés" uniquement
                    $results->push($reaffecteObj);
                } elseif (str_contains($typeAffectationFilter, 'non affecté')) {
                    // "non affectés" uniquement
                    $results->push($nonAffecteObj);
                } else {
                    // Fallback : afficher tous
                    $results->push($affecteObj);
                    $results->push($reaffecteObj);
                    $results->push($nonAffecteObj);
                }
            }

            return [
                'results' => $results,
                'total_count' => $results->count(),
                'filters' => $llmFilters,
                'from_verification' => true,
            ];
        }

        $modelClass = "App\\Models\\{$knowledge->model}";

        if (!class_exists($modelClass)) {
            throw new \Exception("Model {$knowledge->model} not found");
        }

        $query = $modelClass::query();

        if ($knowledge->model === 'ESBTPPaiement') {
            $query->with(['etudiant']);
        } elseif ($knowledge->model === 'ESBTPInscription') {
            $query->with(['etudiant', 'classe']);
        } elseif ($knowledge->model === 'ESBTPEtudiant') {
            $query->with(['inscriptions.classe']);
        }

        // Extraire les filtres depuis le message
        $messageFilters = $this->extractFiltersFromMessage($message, $knowledge->columns_mapping ?? []);
        $filters = $this->mergeFilters($llmFilters, $messageFilters);

        $appliedFilters = $this->applyFiltersToQuery($query, $knowledge->model, $filters);

        // Limiter les résultats (pour le chat)
        $limit = $filters['limit'] ?? $requestedLimit ?? 5;
        $limit = max(1, min((int) $limit, 25));

        $results = $query->limit($limit)->get();
        $appliedFilters['limit'] = $limit;

        // Compter le total disponible (pour "X sur Y résultats")
        $totalQuery = $modelClass::query();
        $this->applyFiltersToQuery($totalQuery, $knowledge->model, $filters);
        $totalCount = $totalQuery->count();

        return [
            'results' => $results,
            'total_count' => $totalCount,
            'filters' => $appliedFilters,
        ];
    }

    /**
     * Extraire les filtres depuis le message utilisateur
     */
    protected function extractFiltersFromMessage(string $message, array $columnsMapping): array
    {
        $filters = [];
        $messageLower = strtolower($message);

        // Extraire statut
        if (str_contains($messageLower, 'en attente') || str_contains($messageLower, 'attente')) {
            if (isset($columnsMapping['statut']) || isset($columnsMapping['status'])) {
                $filters['status'] = 'en_attente';
            }
        } elseif (str_contains($messageLower, 'validé') || str_contains($messageLower, 'valide')) {
            if (isset($columnsMapping['statut']) || isset($columnsMapping['status'])) {
                $filters['status'] = 'validé';
            }
        } elseif (str_contains($messageLower, 'rejeté') || str_contains($messageLower, 'rejete')) {
            if (isset($columnsMapping['statut']) || isset($columnsMapping['status'])) {
                $filters['status'] = 'rejeté';
            }
        }

        // Extraire mois
        $mois = [
            'janvier' => 1, 'février' => 2, 'fevrier' => 2, 'mars' => 3,
            'avril' => 4, 'mai' => 5, 'juin' => 6, 'juillet' => 7,
            'août' => 8, 'aout' => 8, 'septembre' => 9, 'octobre' => 10,
            'novembre' => 11, 'décembre' => 12, 'decembre' => 12,
        ];

        foreach ($mois as $moisNom => $moisNum) {
            if (str_contains($messageLower, $moisNom)) {
                if (isset($columnsMapping['month'])) {
                    $filters['month'] = [
                        'column' => $columnsMapping['month'],
                        'value' => $moisNum,
                    ];
                }
                break;
            }
        }

        // Si "ce mois-ci" ou "ce mois"
        if (str_contains($messageLower, 'ce mois')) {
            if (isset($columnsMapping['month'])) {
                $filters['month'] = [
                    'column' => $columnsMapping['month'],
                    'value' => now()->month,
                ];
            }
        }

        return $filters;
    }

    protected function mergeFilters(array $primary, array $secondary): array
    {
        $merged = array_replace_recursive($secondary, $primary);

        if (isset($merged['statut']) && !isset($merged['status'])) {
            $merged['status'] = $merged['statut'];
            unset($merged['statut']);
        }

        if (isset($merged['status']) && is_string($merged['status'])) {
            $merged['status'] = strtolower($merged['status']);
        }

        return $merged;
    }

    protected function applyFiltersToQuery($query, string $model, array $filters): array
    {
        $applied = [];

        if (isset($filters['status'])) {
            $status = $filters['status'];
            if ($status === 'pending') {
                $status = 'en_attente';
            } elseif (in_array($status, ['validated', 'valide', 'validée', 'validee'], true)) {
                $status = 'validé';
            } elseif (in_array($status, ['cancelled', 'canceled', 'annule', 'annulée', 'annulee'], true)) {
                $status = 'annulé';
            }
            $query->where('status', $status);
            $applied['status'] = $status;
        }

        if (isset($filters['type_inscription'])) {
            $type = $filters['type_inscription'];
            $query->where('type_inscription', $type);
            $applied['type_inscription'] = $type;
        }

        // Gérer le filtre "classe" (nom de classe → classe_id)
        if (isset($filters['classe'])) {
            $classeName = $filters['classe'];
            $classe = \DB::table('esbtp_classes')
                ->where(function ($q) use ($classeName) {
                    $q->where('name', 'like', '%' . $classeName . '%')
                      ->orWhere('code', 'like', '%' . $classeName . '%');
                })
                ->whereNull('deleted_at')  // Exclure classes supprimées
                ->first();

            if ($classe) {
                $query->where('classe_id', $classe->id);
                $applied['classe'] = $classeName;
                $applied['classe_id'] = $classe->id;
            }
        } elseif (isset($filters['classe_id'])) {
            $classeId = $filters['classe_id'];
            $query->where('classe_id', $classeId);
            $applied['classe_id'] = $classeId;
        }

        if (isset($filters['etudiant_id'])) {
            $studentId = $filters['etudiant_id'];
            $query->where('etudiant_id', $studentId);
            $applied['etudiant_id'] = $studentId;
        }

        // Filtre spécial : inscriptions SANS paiements
        if (isset($filters['without_paiements']) && $filters['without_paiements'] === true) {
            $query->whereDoesntHave('paiements');
            $applied['without_paiements'] = true;
        }

        if (isset($filters['payment_status'])) {
            $status = $filters['payment_status'];
            $query->where('status', $status);
            $applied['payment_status'] = $status;
        }

        if (isset($filters['month']) && is_array($filters['month'])) {
            $monthFilter = $filters['month'];
            if (isset($monthFilter['column'], $monthFilter['value'])) {
                $query->whereMonth($monthFilter['column'], $monthFilter['value']);
                $applied['month'] = $monthFilter;
            }
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('date_inscription', '>=', $filters['date_from']);
            $applied['date_from'] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date_inscription', '<=', $filters['date_to']);
            $applied['date_to'] = $filters['date_to'];
        }

        if (isset($filters['order_by']) && is_array($filters['order_by'])) {
            $column = $filters['order_by']['column'] ?? null;
            $direction = strtolower($filters['order_by']['direction'] ?? 'desc');
            if ($column) {
                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
                $query->orderBy($column, $direction);
                $applied['order_by'] = compact('column', 'direction');
            }
        } else {
            // Ordre par défaut selon le modèle
            if ($model === 'ESBTPInscription') {
                $query->orderByDesc('date_inscription');
                $applied['order_by'] = ['column' => 'date_inscription', 'direction' => 'desc'];
            } elseif ($model === 'ESBTPPaiement') {
                $query->orderByDesc('date_paiement');
                $applied['order_by'] = ['column' => 'date_paiement', 'direction' => 'desc'];
            } else {
                $query->latest();
            }
        }

        if (isset($filters['limit'])) {
            $applied['limit'] = (int) $filters['limit'];
        }

        return $applied;
    }

    /**
     * Formater la réponse avec template
     */
    protected function formatResponse(array $data, ChatbotKnowledgeBase $knowledge, array $decision): array
    {
        $displayPreference = $decision['display'] ?? null;
        $text = $decision['response_text'] ?? null;
        $followUp = $decision['follow_up'] ?? null;

        $templateName = $this->getTemplateName($knowledge->intent, $displayPreference);
        $template = ChatbotDisplayTemplate::byName($templateName)->active()->first();

        if (!$template) {
            // Fallback: réponse texte simple
            $fallbackText = $text ?? ("J'ai trouvé " . count($data['results']) . " résultat(s) sur " . $data['total_count'] . " disponible(s).");
            return [
                'text' => $fallbackText,
                'display_type' => 'text',
            ];
        }

        // Formater les données pour le template
        $formatted = $this->formatDataForTemplate(
            $data['results'],
            $knowledge,
            $template->type,
            $decision
        );

        // Construire le deep link avec les filtres
        $deepLinkFilters = $this->sanitizeFiltersForDeepLink($data['filters'] ?? []);
        $deepLink = $this->buildDeepLink($knowledge->deep_link_pattern, $deepLinkFilters);

        // Construire le label du bouton deep link
        $deepLinkLabel = $this->buildDeepLinkLabel($data['filters'] ?? [], $knowledge->intent);

        $introText = $text ?? "Voici les " . count($data['results']) . " premiers résultats :";

        return [
            'text' => $introText,
            'display_type' => $template->type,
            'display_data' => [
                'template_name' => $template->name,
                'template_html' => $template->html_template,
                'rows' => $formatted['rows'] ?? null,
                'cards' => $formatted['cards'] ?? null,
                'columns' => $formatted['columns'] ?? null,
                'column_count' => $formatted['column_count'] ?? null,
                'total_count' => count($data['results']),
                'total_available' => $data['total_count'],
                'deep_link' => $deepLink,
                'deep_link_label' => $deepLinkLabel,
                'follow_up' => $followUp,
            ],
            'deep_link' => $deepLink,
            'follow_up' => $followUp,
        ];
    }

    /**
     * Déterminer le nom du template depuis l'intent
     */
    protected function getTemplateName(string $intent, ?string $display = null): string
    {
        if ($display === 'cards') {
            return 'cards_generic';
        }

        if ($display === 'kpi') {
            return 'cards_generic';
        }

        if ($display === 'table') {
            return 'table_generic';
        }

        $templateMap = [
            'get_paiements' => 'table_generic',
            'get_etudiants' => 'table_generic',
            'get_inscriptions' => $display === 'table' ? 'table_generic' : 'cards_generic',
        ];

        return $templateMap[$intent] ?? 'table_generic';
    }

    /**
     * Formater les données pour le template
     */
    protected function formatDataForTemplate($results, ChatbotKnowledgeBase $knowledge, string $displayType, array $decision): array
    {
        return match ($displayType) {
            'cards' => $this->formatCardData($results, $knowledge),
            'kpi' => $this->formatCardData($results, $knowledge),
            default => $this->formatTableData($results, $knowledge, $decision),
        };
    }

    protected function formatTableData($results, ChatbotKnowledgeBase $knowledge, array $decision): array
    {
        $columns = [];
        $rows = [];

        // Cas spécial pour get_frais (données de verification, pas de modèle)
        if ($knowledge->intent === 'get_frais' && $results->isNotEmpty() && isset($results->first()->categorie_id)) {
            $columns = [
                ['label' => 'Catégorie'],
                ['label' => 'Type de tarif'],
                ['label' => 'Montant'],
                ['label' => 'Statut'],
            ];

            foreach ($results as $result) {
                $rows[] = [
                    'cells' => [
                        ['value' => $result->categorie_name ?? 'N/A'],
                        ['value' => $result->type_tarif ?? 'Standard'],
                        ['value' => isset($result->amount) ? number_format($result->amount, 0, ',', ' ') . ' FCFA' : 'N/A'],
                        ['value' => $result->is_active ? 'Actif' : 'Inactif', 'badge' => $result->is_active ? 'success' : 'secondary'],
                    ],
                    'column_count' => count($columns),
                ];
            }

            return [
                'columns' => $columns,
                'rows' => $rows,
                'column_count' => count($columns),
            ];
        }

        if ($knowledge->model === 'ESBTPPaiement') {
            $columns = [
                ['label' => 'Étudiant'],
                ['label' => 'Montant'],
                ['label' => 'Statut'],
                ['label' => 'Date'],
            ];

            foreach ($results as $result) {
                $etudiant = $result->etudiant ?? null;
                $statut = $result->status ?? null;

                $row = [
                    'cells' => [
                        ['value' => $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenom ?? '')) : 'Étudiant inconnu'],
                        ['value' => isset($result->montant) ? number_format($result->montant, 0, ',', ' ') . ' FCFA' : 'N/A'],
                        ['value' => $statut ? ucfirst(str_replace('_', ' ', $statut)) : 'Inconnu', 'badge' => $this->getStatutBadgeClass($statut)],
                        ['value' => $result->date_paiement ? $result->date_paiement->format('d/m/Y') : 'N/A'],
                    ],
                    'column_count' => count($columns),
                ];

                $actions = $this->buildPaymentActions($result);
                if (!empty($actions)) {
                    $row['actions'] = $actions;
                }

                $rows[] = $row;
            }
        } elseif ($knowledge->model === 'ESBTPInscription') {
            $columns = [
                ['label' => 'Étudiant'],
                ['label' => 'Classe'],
                ['label' => 'Type'],
                ['label' => 'Statut'],
                ['label' => 'Date'],
            ];

            foreach ($results as $result) {
                $etudiant = $result->etudiant ?? null;
                $classe = $result->classe ?? null;
                $statut = $result->status ?? null;
                $typeInscription = $result->type_inscription ?? null;

                $row = [
                    'cells' => [
                        ['value' => $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenom ?? '')) : 'Étudiant inconnu'],
                        ['value' => $classe->name ?? 'Non affectée'],
                        ['value' => $typeInscription ? ucfirst(str_replace('_', ' ', $typeInscription)) : 'N/A'],
                        ['value' => $statut ? ucfirst(str_replace('_', ' ', $statut)) : 'N/A', 'badge' => $this->getInscriptionBadgeClass($statut)],
                        ['value' => $result->date_inscription ? $result->date_inscription->format('d/m/Y') : 'N/A'],
                    ],
                    'column_count' => count($columns),
                ];

                $actions = $this->buildInscriptionActions($result);
                if (!empty($actions)) {
                    $row['actions'] = $actions;
                }

                $rows[] = $row;
            }
        } elseif ($knowledge->model === 'ESBTPEtudiant') {
            $columns = [
                ['label' => 'Nom complet'],
                ['label' => 'Matricule'],
                ['label' => 'Classe'],
                ['label' => 'Statut'],
            ];

            foreach ($results as $result) {
                $firstInscription = $result->inscriptions->first();
                $rows[] = [
                    'cells' => [
                        ['value' => trim(($result->nom ?? '') . ' ' . ($result->prenom ?? ''))],
                        ['value' => $result->matricule ?? 'N/A'],
                        ['value' => $firstInscription?->classe->name ?? 'N/A'],
                        ['value' => $firstInscription?->status ?? 'N/A'],
                    ],
                    'column_count' => count($columns),
                ];
            }
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'column_count' => max(1, count($columns)),
        ];
    }

    protected function formatCardData($results, ChatbotKnowledgeBase $knowledge): array
    {
        $cards = [];

        foreach ($results as $result) {
            if ($knowledge->model === 'ESBTPInscription') {
                $etudiant = $result->etudiant ?? null;
                $classe = $result->classe ?? null;
                $statut = $result->status ?? null;
                $typeInscription = $result->type_inscription ?? null;

                $cards[] = [
                    'title' => $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenom ?? '')) : 'Étudiant inconnu',
                    'subtitle' => $classe->name ?? 'Classe non affectée',
                    'meta' => [
                        ['label' => 'Type', 'value' => $typeInscription ? ucfirst(str_replace('_', ' ', $typeInscription)) : 'N/A'],
                        ['label' => 'Statut', 'value' => $statut ? ucfirst(str_replace('_', ' ', $statut)) : 'N/A'],
                        ['label' => 'Inscription', 'value' => $result->date_inscription ? $result->date_inscription->format('d/m/Y') : 'N/A'],
                    ],
                    'badges' => [
                        ['label' => $statut ? ucfirst(str_replace('_', ' ', $statut)) : '—', 'style' => $this->getInscriptionBadgeClass($statut)],
                    ],
                ];

                $actions = $this->buildInscriptionActions($result);
                if (!empty($actions)) {
                    $cards[array_key_last($cards)]['actions'] = $actions;
                }
            } elseif ($knowledge->model === 'ESBTPPaiement') {
                $etudiant = $result->etudiant ?? null;
                $statut = $result->status ?? null;

                $cards[] = [
                    'title' => $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenom ?? '')) : 'Étudiant inconnu',
                    'subtitle' => $result->date_paiement ? $result->date_paiement->format('d/m/Y') : 'Date inconnue',
                    'meta' => [
                        ['label' => 'Montant', 'value' => isset($result->montant) ? number_format($result->montant, 0, ',', ' ') . ' FCFA' : 'N/A'],
                        ['label' => 'Statut', 'value' => $statut ? ucfirst(str_replace('_', ' ', $statut)) : 'Inconnu'],
                    ],
                    'badges' => [
                        ['label' => isset($result->mode_paiement) ? ucfirst($result->mode_paiement) : 'Paiement', 'style' => 'secondary'],
                        ['label' => $statut ? ucfirst(str_replace('_', ' ', $statut)) : '—', 'style' => $this->getStatutBadgeClass($statut)],
                    ],
                ];

                $actions = $this->buildPaymentActions($result);
                if (!empty($actions)) {
                    $cards[array_key_last($cards)]['actions'] = $actions;
                }
            }
        }

        return ['cards' => $cards];
    }

    protected function buildInscriptionActions($inscription): array
    {
        $actions = [];

        if ($url = $this->buildRouteUrl('esbtp.inscriptions.show', $inscription->id)) {
            $actions[] = [
                'label' => 'Voir inscription',
                'url' => $url,
                'icon' => 'fas fa-id-card',
            ];
        }

        if ($inscription->etudiant && ($url = $this->buildRouteUrl('esbtp.etudiants.show', $inscription->etudiant->id))) {
            $actions[] = [
                'label' => 'Voir étudiant',
                'url' => $url,
                'icon' => 'fas fa-user-graduate',
            ];
        }

        return $actions;
    }

    protected function buildPaymentActions($paiement): array
    {
        $actions = [];

        if ($paiement->inscription_id && ($url = $this->buildRouteUrl('esbtp.inscriptions.show', $paiement->inscription_id))) {
            $actions[] = [
                'label' => 'Voir inscription',
                'url' => $url,
                'icon' => 'fas fa-file-invoice',
            ];
        }

        if ($paiement->etudiant && ($url = $this->buildRouteUrl('esbtp.etudiants.show', $paiement->etudiant->id))) {
            $actions[] = [
                'label' => 'Voir étudiant',
                'url' => $url,
                'icon' => 'fas fa-user-graduate',
            ];
        }

        return $actions;
    }

    protected function buildRouteUrl(string $routeName, mixed $identifier): ?string
    {
        if (! Route::has($routeName)) {
            return null;
        }

        $route = Route::getRoutes()->getByName($routeName);

        if (! $route) {
            return null;
        }

        $parameterNames = method_exists($route, 'parameterNames') ? $route->parameterNames() : [];

        if (! empty($parameterNames)) {
            return route($routeName, [$parameterNames[0] => $identifier]);
        }

        return route($routeName, $identifier);
    }

    /**
     * Badge class pour statut
     */
    protected function getStatutBadgeClass(?string $statut): string
    {
        if (!$statut) {
            return 'info';
        }

        $normalized = mb_strtolower($statut, 'UTF-8');

        return match($normalized) {
            'validé', 'valide' => 'success',
            'en_attente' => 'warning',
            'rejeté', 'rejete' => 'danger',
            'annulé', 'annule' => 'secondary',
            default => 'info',
        };
    }

    /**
     * Badge class pour statut d'inscription
     */
    protected function getInscriptionBadgeClass(?string $status): string
    {
        if (!$status) {
            return 'info';
        }

        $normalized = mb_strtolower($status, 'UTF-8');

        return match ($normalized) {
            'en_attente', 'pending' => 'warning',
            'active', 'validée', 'validee', 'valide' => 'success',
            'annulée', 'annulee', 'annule' => 'danger',
            'suspendue', 'suspendu' => 'secondary',
            default => 'info',
        };
    }

    /**
     * Construire le deep link avec les filtres
     */
    protected function buildDeepLink(string $pattern, array $filters): string
    {
        $link = $pattern;

        // Si le filtre 'classe' est présent, convertir en filiere + niveau (IDs directs pour URL)
        if (isset($filters['classe']) && !isset($filters['filiere'])) {
            $classe = \DB::table('esbtp_classes')
                ->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['classe'] . '%')
                      ->orWhere('code', 'like', '%' . $filters['classe'] . '%');
                })
                ->whereNull('deleted_at')  // Exclure classes supprimées
                ->first();

            if ($classe) {
                $filters['filiere'] = $classe->filiere_id;
                $filters['niveau'] = $classe->niveau_etude_id;
            }
        }

        foreach ($filters as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (is_array($value)) {
                // Pour month qui a 'column' et 'value'
                $link = str_replace($placeholder, $value['value'], $link);
            } else {
                $link = str_replace($placeholder, $value, $link);
            }
        }

        // Supprimer les placeholders non remplacés
        $link = preg_replace('/[?&]\w+={[^}]+}/', '', $link);

        return $link;
    }

    protected function sanitizeFiltersForDeepLink(array $filters): array
    {
        $sanitized = [];

        foreach ($filters as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $sanitized[$key] = $value;
                continue;
            }

            if ($key === 'month' && is_array($value) && isset($value['column'], $value['value'])) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Créer une réponse d'erreur
     */
    protected function createErrorResponse(
        ChatbotConversation $conversation,
        string $errorMessage,
        ?array $memoryAction = null
    ): array {
        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $errorMessage,
            'display_type' => 'text',
            'display_data' => $this->attachMemoryAction(null, $memoryAction),
        ]);

        return [
            'success' => true,
            'message' => $assistantMessage->content,
            'display_type' => 'text',
            'display_data' => $assistantMessage->display_data,
            'conversation_id' => $conversation->session_id,
        ];
    }

    /**
     * Réponse texte simple (sans récupération).
     */
    protected function respondWithSimpleMessage(
        ChatbotConversation $conversation,
        string $content,
        ?array $memoryAction = null,
        ?string $intent = null,
        ?string $message = null,
        ?ChatbotUserPreference $preferences = null
    ): array {
        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $content,
            'display_type' => 'text',
            'display_data' => $this->attachMemoryAction(null, $memoryAction),
        ]);

        $conversation->update([
            'last_activity_at' => now(),
            'context' => array_filter([
                'last_intent' => $intent ?? 'conversation_smalltalk',
                'last_filters' => [],
                'last_display' => 'text',
            ]),
        ]);

        $this->updateConversationTitleIfNeeded($conversation, $intent, (string) $message, $preferences);

        return [
            'success' => true,
            'message' => $assistantMessage->content,
            'display_type' => 'text',
            'display_data' => $assistantMessage->display_data,
            'conversation_id' => $conversation->session_id,
        ];
    }

    /**
     * Récupérer ou créer une conversation
     */
    protected function getOrCreateConversation(int $userId, ?string $sessionId): ChatbotConversation
    {
        if ($sessionId) {
            $conversation = ChatbotConversation::where('session_id', $sessionId)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        // Créer nouvelle conversation
        return ChatbotConversation::create([
            'user_id' => $userId,
            'session_id' => \Str::uuid(),
            'last_activity_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Récupérer l'historique d'une conversation
     */
    public function getHistory(string $sessionId, int $userId): array
    {
        $conversation = ChatbotConversation::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'display_type' => $message->display_type,
                    'display_data' => $message->display_data,
                    'deep_link' => $message->deep_link,
                    'created_at' => $message->created_at->toIso8601String(),
                ];
            });

        return [
            'success' => true,
            'messages' => $messages,
        ];
    }

    /**
     * Construire un texte lisible des critères de recherche
     */
    protected function buildCriteriaText(string $intent, array $filters): string
    {
        $parts = [];

        // Pour "get_inscriptions"
        if ($intent === 'get_inscriptions') {
            if (isset($filters['without_paiements']) && $filters['without_paiements'] === true) {
                $parts[] = "inscriptions sans aucun paiement";
            }
            if (isset($filters['status'])) {
                $statusLabel = match($filters['status']) {
                    'en_attente' => 'en attente',
                    'validé' => 'validées',
                    'rejeté' => 'rejetées',
                    'active' => 'actives',
                    default => $filters['status']
                };
                $parts[] = "statut : $statusLabel";
            }
            if (isset($filters['classe'])) {
                $parts[] = "classe : " . $filters['classe'];
            }
        }

        // Pour "get_paiements"
        if ($intent === 'get_paiements') {
            if (isset($filters['status'])) {
                $statusLabel = match($filters['status']) {
                    'en_attente' => 'en attente',
                    'validé' => 'validés',
                    'rejeté' => 'rejetés',
                    default => $filters['status']
                };
                $parts[] = "paiements $statusLabel";
            }
            if (isset($filters['month'])) {
                $parts[] = "mois : " . ($filters['month']['value'] ?? 'actuel');
            }
        }

        // Pour "get_frais"
        if ($intent === 'get_frais') {
            if (isset($filters['categorie_frais'])) {
                $parts[] = "catégorie : " . $filters['categorie_frais'];
            }
            if (isset($filters['filiere'])) {
                $parts[] = "filière : " . $filters['filiere'];
            }
            if (isset($filters['niveau'])) {
                $parts[] = "niveau : " . $filters['niveau'];
            }
            if (isset($filters['type_affectation'])) {
                $parts[] = "type : " . $filters['type_affectation'];
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Construire le label du bouton deep link de manière explicite
     */
    protected function buildDeepLinkLabel(array $filters, string $intent): string
    {
        // Si le filtre 'classe' est présent, récupérer filière et niveau
        if (isset($filters['classe'])) {
            $classe = \DB::table('esbtp_classes')
                ->select('esbtp_classes.*', 'esbtp_filieres.name as filiere_name', 'esbtp_niveau_etudes.name as niveau_name')
                ->leftJoin('esbtp_filieres', 'esbtp_classes.filiere_id', '=', 'esbtp_filieres.id')
                ->leftJoin('esbtp_niveau_etudes', 'esbtp_classes.niveau_etude_id', '=', 'esbtp_niveau_etudes.id')
                ->where(function ($q) use ($filters) {
                    $q->where('esbtp_classes.name', 'like', '%' . $filters['classe'] . '%')
                      ->orWhere('esbtp_classes.code', 'like', '%' . $filters['classe'] . '%');
                })
                ->whereNull('esbtp_classes.deleted_at')  // Exclure classes supprimées
                ->first();

            if ($classe && $classe->filiere_name && $classe->niveau_name) {
                return "Voir la page (Filière : {$classe->filiere_name}, Niveau : {$classe->niveau_name})";
            }
        }

        // Sinon, label par défaut
        return "Ouvrir la page";
    }
}
