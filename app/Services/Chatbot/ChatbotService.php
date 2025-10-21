<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ChatbotActionLog;
use App\Models\ChatbotSystemPrompt;
use App\Models\ChatbotDisplayTemplate;
use App\Models\ChatbotKnowledgeBase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Data\Content;
use Gemini\Enums\Role;

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

    public function __construct(ChatbotExplorerService $explorer)
    {
        $this->explorer = $explorer;
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

            // 3. Détecter l'intent depuis le message
            $intent = $this->detectIntent($message);
            Log::info('🎯 Intent detected', ['intent' => $intent]);

            // 4. Récupérer la connaissance (cache ou exploration)
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

            // 6. Exécuter la requête pour récupérer les données
            $data = $this->executeDataRetrieval($knowledge, $message, $user);

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
            $formattedResponse = $this->formatResponse($data, $knowledge);

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
            $conversation->update(['last_activity_at' => now()]);

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
    protected function detectIntent(string $message): string
    {
        $messageLower = strtolower($message);

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
    protected function executeDataRetrieval(ChatbotKnowledgeBase $knowledge, string $message, $user): array
    {
        $modelClass = "App\\Models\\{$knowledge->model}";

        if (!class_exists($modelClass)) {
            throw new \Exception("Model {$knowledge->model} not found");
        }

        $query = $modelClass::query();

        // Extraire les filtres depuis le message
        $filters = $this->extractFiltersFromMessage($message, $knowledge->columns_mapping ?? []);

        // Appliquer les filtres
        foreach ($filters as $column => $value) {
            if ($column === 'month') {
                $query->whereMonth($value['column'], $value['value']);
            } else {
                $query->where($column, $value);
            }
        }

        // Limiter les résultats (pour le chat)
        $limit = 5;
        $results = $query->limit($limit)->get();

        // Compter le total disponible (pour "X sur Y résultats")
        $totalQuery = $modelClass::query();
        foreach ($filters as $column => $value) {
            if ($column === 'month') {
                $totalQuery->whereMonth($value['column'], $value['value']);
            } else {
                $totalQuery->where($column, $value);
            }
        }
        $totalCount = $totalQuery->count();

        return [
            'results' => $results,
            'total_count' => $totalCount,
            'filters' => $filters,
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
            if (isset($columnsMapping['statut'])) {
                $filters['statut'] = 'en_attente';
            }
        } elseif (str_contains($messageLower, 'validé') || str_contains($messageLower, 'valide')) {
            if (isset($columnsMapping['statut'])) {
                $filters['statut'] = 'validé';
            }
        } elseif (str_contains($messageLower, 'rejeté') || str_contains($messageLower, 'rejete')) {
            if (isset($columnsMapping['statut'])) {
                $filters['statut'] = 'rejeté';
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

    /**
     * Formater la réponse avec template
     */
    protected function formatResponse(array $data, ChatbotKnowledgeBase $knowledge): array
    {
        // Déterminer le template à utiliser
        $templateName = $this->getTemplateName($knowledge->intent);
        $template = ChatbotDisplayTemplate::byName($templateName)->active()->first();

        if (!$template) {
            // Fallback: réponse texte simple
            $text = "J'ai trouvé " . count($data['results']) . " résultat(s) sur " . $data['total_count'] . " disponible(s).";
            return [
                'text' => $text,
                'display_type' => 'text',
            ];
        }

        // Formater les données pour le template
        $rows = $this->formatDataForTemplate($data['results'], $knowledge);

        // Construire le deep link avec les filtres
        $deepLink = $this->buildDeepLink($knowledge->deep_link_pattern, $data['filters']);

        $text = "Voici les " . count($data['results']) . " premiers résultats :";

        return [
            'text' => $text,
            'display_type' => $template->type,
            'display_data' => [
                'template_name' => $template->name,
                'template_html' => $template->html_template,
                'rows' => $rows,
                'total_count' => count($data['results']),
                'total_available' => $data['total_count'],
                'deep_link' => $deepLink,
            ],
            'deep_link' => $deepLink,
        ];
    }

    /**
     * Déterminer le nom du template depuis l'intent
     */
    protected function getTemplateName(string $intent): string
    {
        $templateMap = [
            'get_paiements' => 'paiements_table',
            'get_etudiants' => 'etudiants_table',
            'get_inscriptions' => 'inscriptions_table',
            'get_classes' => 'classes_table',
            'get_frais' => 'frais_table',
        ];

        return $templateMap[$intent] ?? 'default_table';
    }

    /**
     * Formater les données pour le template
     */
    protected function formatDataForTemplate($results, ChatbotKnowledgeBase $knowledge): array
    {
        $rows = [];

        foreach ($results as $result) {
            $row = [];

            // Formater selon le modèle
            if ($knowledge->model === 'ESBTPPaiement') {
                $row = [
                    'etudiant_nom' => $result->etudiant->nom . ' ' . $result->etudiant->prenom,
                    'montant_formatte' => number_format($result->montant, 0, ',', ' ') . ' FCFA',
                    'statut' => ucfirst($result->statut),
                    'statut_class' => $this->getStatutBadgeClass($result->statut),
                    'date_paiement' => $result->date_paiement ? $result->date_paiement->format('d/m/Y') : 'N/A',
                ];
            } elseif ($knowledge->model === 'ESBTPEtudiant') {
                $row = [
                    'nom_complet' => $result->nom . ' ' . $result->prenom,
                    'matricule' => $result->matricule ?? 'N/A',
                    'classe' => $result->inscriptions->first()->classe->name ?? 'N/A',
                    'statut' => $result->inscriptions->first()->status ?? 'N/A',
                ];
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Badge class pour statut
     */
    protected function getStatutBadgeClass(string $statut): string
    {
        return match($statut) {
            'validé' => 'success',
            'en_attente' => 'warning',
            'rejeté' => 'danger',
            'annulé' => 'secondary',
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
