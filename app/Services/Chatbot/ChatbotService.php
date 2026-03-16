<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ChatbotActionLog;
use App\Models\ChatbotUserPreference;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Service principal du Chatbot KLASSCI.
 *
 * Orchestration simplifiée avec ClaudeAgentService :
 * 1. Gestion conversation (création, historique)
 * 2. Appel agent Gemini (tool calling natif)
 * 3. Persistence messages + audit
 * 4. Formulaires intégrés (frais, inscriptions)
 */
class ChatbotService
{
    protected ClaudeAgentService $agent;
    protected ChatbotSetupGuideService $setupGuide;

    public function __construct(
        ClaudeAgentService $agent,
        ChatbotSetupGuideService $setupGuide
    ) {
        $this->agent = $agent;
        $this->setupGuide = $setupGuide;
    }

    /**
     * Envoyer un message et obtenir une réponse.
     */
    public function sendMessage(string $message, ?string $sessionId = null, ?array $clientContext = null): array
    {
        $user = Auth::user();
        $startTime = microtime(true);

        Log::info('ChatbotService: sendMessage', [
            'user_id' => $user->id,
            'message' => mb_substr($message, 0, 100),
        ]);

        try {
            // 1. Conversation
            $conversation = $this->getOrCreateConversation($user->id, $sessionId);
            $preferences = $this->getUserPreferences($user->id);

            // 2. Détecter nom préféré
            $preferredNameCandidate = $this->detectPreferredName($message);
            $memoryAction = $this->buildMemoryAction($preferredNameCandidate, $preferences);

            // 3. Sauvegarder le message utilisateur
            ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $message,
                'display_type' => 'text',
            ]);

            // 4. Mettre à jour le contexte de page
            if ($clientContext) {
                $conversation->update([
                    'context' => array_filter(array_merge($conversation->context ?? [], [
                        'last_page_url' => $clientContext['current_url'] ?? null,
                        'last_page_path' => $clientContext['current_path'] ?? null,
                        'last_page_title' => $clientContext['page_title'] ?? null,
                    ])),
                ]);
            }

            // 5. Appel agent Gemini (tool calling)
            $agentResponse = $this->agent->chat(
                $conversation,
                $message,
                $user,
                $preferences,
                $clientContext
            );

            // 6. Construire les display_data finales
            $displayData = $agentResponse['display_data'];
            if ($memoryAction) {
                $displayData = $displayData ?? [];
                $displayData['follow_up_actions'] = array_values(array_filter(
                    array_merge($displayData['follow_up_actions'] ?? [], [$memoryAction])
                ));
            }

            // 7. Sauvegarder la réponse assistant
            $assistantMessage = ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $agentResponse['text'],
                'display_type' => $agentResponse['display_type'],
                'display_data' => $displayData,
                'deep_link' => $agentResponse['deep_link'],
                'metadata' => [
                    'tool_calls' => $agentResponse['tool_calls'],
                    'engine' => 'gemini',
                ],
            ]);

            // 8. Audit log
            if (!empty($agentResponse['tool_calls'])) {
                $lastCall = end($agentResponse['tool_calls']);
                ChatbotActionLog::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                    'action_type' => 'retrieve',
                    'model_type' => $lastCall['tool'] ?? 'unknown',
                    'action_data' => [
                        'tool_calls' => $agentResponse['tool_calls'],
                    ],
                    'status' => 'success',
                ]);
            }

            // 9. Mettre à jour la conversation
            $conversation->update([
                'last_activity_at' => now(),
                'context' => array_filter([
                    'last_display' => $agentResponse['display_type'],
                    'last_tool_calls' => $agentResponse['tool_calls'],
                ]),
            ]);

            // 10. Titre auto
            $this->updateConversationTitleIfNeeded($conversation, $message);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('ChatbotService: success', ['duration_ms' => $duration]);

            return [
                'success' => true,
                'message' => $assistantMessage->content,
                'display_type' => $assistantMessage->display_type,
                'display_data' => $assistantMessage->display_data,
                'deep_link' => $assistantMessage->deep_link,
                'conversation_id' => $conversation->session_id,
            ];

        } catch (\Exception $e) {
            Log::error('ChatbotService: error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => "Désolé, une erreur s'est produite. Veuillez réessayer.",
                'error' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    /**
     * Récupérer l'historique d'une conversation.
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

    // ─── Formulaires intégrés ───────────────────────────────

    /**
     * Construire les données du formulaire de création de catégorie de frais.
     */
    public function buildMandatoryFraisCategoryFormData(?string $sessionId = null): array
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
            'title' => 'Créer une catégorie obligatoire',
            'description' => 'Catégorie obligatoire pour les inscriptions.',
            'action_url' => Route::has('chatbot.forms.frais-category.store')
                ? route('chatbot.forms.frais-category.store') : null,
            'action_method' => 'POST',
            'submit_label' => 'Créer la catégorie',
            'fields' => $fields,
            'hidden_fields' => $sessionId ? ['conversation_id' => $sessionId] : [],
        ];
    }

    /**
     * Construire les données du formulaire de configuration de frais.
     */
    public function buildFraisConfigFormData(?int $categoryId = null, ?string $sessionId = null): ?array
    {
        $category = $categoryId
            ? ESBTPFraisCategory::find($categoryId)
            : ESBTPFraisCategory::where('is_mandatory', true)->orderBy('id')->first();

        if (!$category) {
            return null;
        }

        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $fields = [
            ['name' => 'filiere_id', 'label' => 'Filière', 'type' => 'select', 'required' => true,
                'options' => $filieres->map(fn ($f) => ['value' => $f->id, 'label' => $f->name])->toArray()],
            ['name' => 'niveau_id', 'label' => 'Niveau', 'type' => 'select', 'required' => true,
                'options' => $niveaux->map(fn ($n) => ['value' => $n->id, 'label' => $n->name])->toArray()],
            ['name' => 'amount_affecte', 'label' => 'Montant affecté (FCFA)', 'type' => 'number', 'required' => true, 'min' => 0],
            ['name' => 'amount_reaffecte', 'label' => 'Montant réaffecté (FCFA)', 'type' => 'number', 'required' => true, 'min' => 0],
            ['name' => 'amount_non_affecte', 'label' => 'Montant non affecté (FCFA)', 'type' => 'number', 'required' => true, 'min' => 0],
            ['name' => 'deadline_days', 'label' => 'Délai de paiement (jours)', 'type' => 'number', 'required' => true, 'min' => 1, 'max' => 365, 'value' => 30],
            ['name' => 'installments_allowed', 'label' => 'Paiement en plusieurs fois', 'type' => 'checkbox'],
            ['name' => 'max_installments', 'label' => 'Nombre max de tranches', 'type' => 'number', 'min' => 1, 'max' => 12, 'value' => 1],
        ];

        return [
            'title' => 'Configuration par classe',
            'description' => "Frais obligatoire : {$category->name}",
            'action_url' => Route::has('chatbot.forms.frais-config.store')
                ? route('chatbot.forms.frais-config.store') : null,
            'action_method' => 'POST',
            'submit_label' => 'Enregistrer la configuration',
            'fields' => $fields,
            'hidden_fields' => array_filter([
                'conversation_id' => $sessionId,
                'category_id' => $category->id,
            ]),
        ];
    }

    /**
     * Construire les données du formulaire de filtre d'inscriptions.
     */
    public function buildInscriptionsFilterFormData(?string $focusField = null, ?string $sessionId = null): array
    {
        $filieres = ESBTPFiliere::orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get();
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();

        $allFields = [
            'search' => ['name' => 'search', 'label' => 'Recherche (nom, matricule, classe)', 'type' => 'text',
                'placeholder' => 'Ex: KONAN, MBTS2025/001'],
            'filiere_id' => ['name' => 'filiere_id', 'label' => 'Filière', 'type' => 'select',
                'options' => array_merge([['value' => '', 'label' => 'Toutes']],
                    $filieres->map(fn ($f) => ['value' => $f->id, 'label' => $f->name])->toArray())],
            'niveau_id' => ['name' => 'niveau_id', 'label' => 'Niveau', 'type' => 'select',
                'options' => array_merge([['value' => '', 'label' => 'Tous']],
                    $niveaux->map(fn ($n) => ['value' => $n->id, 'label' => $n->name])->toArray())],
            'annee_id' => ['name' => 'annee_id', 'label' => 'Année universitaire', 'type' => 'select',
                'options' => array_merge([['value' => '', 'label' => 'Toutes']],
                    $annees->map(fn ($a) => ['value' => $a->id, 'label' => $a->name])->toArray())],
            'status' => ['name' => 'status', 'label' => 'Statut', 'type' => 'select',
                'options' => [
                    ['value' => 'all', 'label' => 'Toutes'],
                    ['value' => 'active', 'label' => 'Actives'],
                    ['value' => 'en_attente', 'label' => 'En attente'],
                    ['value' => 'annulée', 'label' => 'Annulées'],
                    ['value' => 'terminée', 'label' => 'Terminées'],
                ]],
        ];

        $fields = $focusField && isset($allFields[$focusField])
            ? [$allFields[$focusField]]
            : array_values($allFields);

        return [
            'title' => 'Filtrer les inscriptions',
            'description' => 'Laissez un champ vide pour ne pas filtrer.',
            'focus_field' => $focusField,
            'action_url' => Route::has('chatbot.forms.inscriptions-filter.store')
                ? route('chatbot.forms.inscriptions-filter.store') : null,
            'action_method' => 'POST',
            'submit_label' => 'Appliquer les filtres',
            'fields' => $fields,
            'hidden_fields' => $sessionId ? ['conversation_id' => $sessionId] : [],
        ];
    }

    // ─── Helpers privés ────────────────────────────────────

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

        return ChatbotConversation::create([
            'user_id' => $userId,
            'session_id' => \Str::uuid(),
            'last_activity_at' => now(),
            'is_active' => true,
        ]);
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

    protected function updateConversationTitleIfNeeded(ChatbotConversation $conversation, string $message): void
    {
        if (!empty($conversation->title)) {
            return;
        }

        $newTitle = $this->agent->generateTitle($message);
        $newTitle = $this->sanitizeTitle($newTitle ?: $message);

        if ($newTitle) {
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
}
