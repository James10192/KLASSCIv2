<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ChatbotActionLog;
use App\Models\ChatbotDisplayTemplate;
use App\Models\ChatbotKnowledgeBase;
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
 * 4. Appel Gemini API avec function calling
 * 5. Exécution requêtes Eloquent
 * 6. Formatage réponse avec templates
 * 7. Logging audit trail
 */
class ChatbotService
{
    protected ChatbotExplorerService $explorer;
    protected ChatbotLLMService $llm;
    protected ChatbotNavigationService $navigation;

    public function __construct(
        ChatbotExplorerService $explorer,
        ChatbotLLMService $llm,
        ChatbotNavigationService $navigation
    ) {
        $this->explorer = $explorer;
        $this->llm = $llm;
        $this->navigation = $navigation;
    }

    /**
     * Envoyer un message et obtenir une réponse
     */
    public function sendMessage(string $message, ?string $sessionId = null): array
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

            // 2. Enregistrer le message utilisateur
            $userMessage = ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $message,
                'display_type' => 'text',
            ]);

            // 3. Décision LLM (intent, filtres, affichage)
            $llmDecision = $this->llm->decide($conversation, $message);

            // 4. Déterminer l'intent
            $intent = $llmDecision['intent'] ?? $this->detectIntent($message, $conversation);
            Log::info('🎯 Intent detected', ['intent' => $intent]);

            // Répondre immédiatement aux salutations pour éviter une exploration inutile
            if ($intent === 'greeting' && empty($llmDecision['intent'])) {
                return $this->respondWithSimpleMessage(
                    $conversation,
                    "Bonjour ! Comment puis-je vous aider aujourd'hui ? 😊"
                );
            }

            // Réponse textuelle directe (pas de récupération de données)
            if (($llmDecision['display'] ?? null) === 'text' && empty($llmDecision['intent'])) {
                $textResponse = $llmDecision['response_text'] ?? "Compris.";
                return $this->respondWithSimpleMessage($conversation, $textResponse);
            }

            // 5. Récupérer la connaissance (cache ou exploration)
            $knowledge = $this->getOrExploreKnowledge($intent, $message, $user);

            if (!$knowledge) {
                return $this->createErrorResponse(
                    $conversation,
                    "Désolé, je ne sais pas encore comment récupérer cette information. Pouvez-vous reformuler ou demander autre chose ?"
                );
            }

            // 5. Vérifier les permissions
            $userRoles = $user->roles->pluck('name')->toArray();
            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

            if (!$this->checkPermissions($knowledge, $userRoles, $userPermissions)) {
                return $this->createErrorResponse(
                    $conversation,
                    "Vous n'avez pas l'autorisation d'accéder à cette information."
                );
            }

            $llmFilters = $llmDecision['filters'] ?? [];
            $requestedLimit = $llmDecision['limit'] ?? null;

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
                    'deep_link' => $deepLink,
                    'action_label' => $actionLabel,
                    'conversation_id' => $conversation->session_id,
                ];
            }

            // 7. Exécuter la requête pour récupérer les données vérifiées
            $data = $this->executeDataRetrieval($knowledge, $message, $user, $llmFilters, $requestedLimit, $verification);

            if (empty($data['results'])) {
                $assistantMessage = ChatbotMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => "Aucun résultat trouvé pour votre recherche.",
                    'display_type' => 'text',
                ]);

                return [
                    'success' => true,
                    'message' => $assistantMessage->content,
                    'display_type' => 'text',
                    'conversation_id' => $conversation->session_id,
                ];
            }

            // 7. Formater la réponse avec template
            $formattedResponse = $this->formatResponse($data, $knowledge, $llmDecision);

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

            // Convertir les configurations en objets pour uniformité
            $allConfigs = collect($configurations)->map(function($config) {
                return (object) [
                    'id' => $config['config']->id,
                    'categorie_id' => $config['config']->frais_category_id,
                    'categorie_name' => $config['config']->frais_category_id ?
                        \DB::table('esbtp_frais_categories')->where('id', $config['config']->frais_category_id)->value('name') : 'N/A',
                    'classe_name' => $config['classe']->name ?? 'N/A',
                    'filiere_id' => $config['config']->filiere_id,
                    'niveau_id' => $config['config']->niveau_id,
                    'amount' => $config['config']->amount,
                    'effective_date' => $config['config']->effective_date,
                    'is_active' => $config['config']->is_active,
                ];
            });

            // DÉDUPLICATION : Grouper par (filiere_id, niveau_id, categorie_id, amount)
            // L'utilisateur demande pour UNE combinaison (filière + niveau) → montants UNIQUES seulement
            $results = $allConfigs->unique(function($config) {
                return implode('|', [
                    $config->filiere_id,
                    $config->niveau_id,
                    $config->categorie_id,
                    $config->amount,
                    $config->effective_date
                ]);
            })->values();

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

        if (isset($filters['classe_id'])) {
            $classeId = $filters['classe_id'];
            $query->where('classe_id', $classeId);
            $applied['classe_id'] = $classeId;
        }

        if (isset($filters['etudiant_id'])) {
            $studentId = $filters['etudiant_id'];
            $query->where('etudiant_id', $studentId);
            $applied['etudiant_id'] = $studentId;
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
                ['label' => 'Montant'],
                ['label' => 'Date effet'],
                ['label' => 'Statut'],
            ];

            foreach ($results as $result) {
                $rows[] = [
                    'cells' => [
                        ['value' => $result->categorie_name ?? 'N/A'],
                        ['value' => isset($result->amount) ? number_format($result->amount, 0, ',', ' ') . ' FCFA' : 'N/A'],
                        ['value' => $result->effective_date ?? 'N/A'],
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

        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                // Pour month qui a 'column' et 'value'
                $link = str_replace("{{$key}}", $value['value'], $link);
            } else {
                $link = str_replace("{{$key}}", $value, $link);
            }
        }

        // Supprimer les placeholders non remplacés
        $link = preg_replace('/[?&]\w+={{\w+}}/', '', $link);

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
    protected function createErrorResponse(ChatbotConversation $conversation, string $errorMessage): array
    {
        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $errorMessage,
            'display_type' => 'text',
        ]);

        return [
            'success' => true,
            'message' => $assistantMessage->content,
            'display_type' => 'text',
            'conversation_id' => $conversation->session_id,
        ];
    }

    /**
     * Réponse texte simple (sans récupération).
     */
    protected function respondWithSimpleMessage(ChatbotConversation $conversation, string $content): array
    {
        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $content,
            'display_type' => 'text',
        ]);

        $conversation->update([
            'last_activity_at' => now(),
            'context' => array_filter([
                'last_intent' => 'conversation_smalltalk',
                'last_filters' => [],
                'last_display' => 'text',
            ]),
        ]);

        return [
            'success' => true,
            'message' => $assistantMessage->content,
            'display_type' => 'text',
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
}
