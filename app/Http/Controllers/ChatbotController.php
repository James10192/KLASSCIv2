<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\ChatbotSetupGuideService;
use App\Models\ChatbotActionLog;
use App\Models\ChatbotConversation;
use App\Models\ChatbotUserPreference;
use App\Models\ChatbotMessage;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    protected ChatbotService $chatbotService;
    protected ChatbotSetupGuideService $setupGuide;

    public function __construct(ChatbotService $chatbotService, ChatbotSetupGuideService $setupGuide)
    {
        $this->middleware('auth');
        $this->chatbotService = $chatbotService;
        $this->setupGuide = $setupGuide;
    }

    /**
     * Envoyer un message au chatbot
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|string',
            'current_url' => 'nullable|string|max:2048',
            'current_path' => 'nullable|string|max:1024',
            'page_title' => 'nullable|string|max:255',
        ]);

        $response = $this->chatbotService->sendMessage(
            $validated['message'],
            $validated['conversation_id'] ?? null,
            [
                'current_url' => $validated['current_url'] ?? null,
                'current_path' => $validated['current_path'] ?? null,
                'page_title' => $validated['page_title'] ?? null,
            ]
        );

        return response()->json($response);
    }

    /**
     * Envoyer un message avec streaming SSE
     */
    public function sendMessageStream(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|string',
            'current_url' => 'nullable|string|max:2048',
            'current_path' => 'nullable|string|max:1024',
            'page_title' => 'nullable|string|max:255',
        ]);

        return response()->stream(function () use ($validated) {
            $result = $this->chatbotService->sendMessageStream(
                $validated['message'],
                $validated['conversation_id'] ?? null,
                [
                    'current_url' => $validated['current_url'] ?? null,
                    'current_path' => $validated['current_path'] ?? null,
                    'page_title' => $validated['page_title'] ?? null,
                ],
                function (string $event, array $data) {
                    echo "event: {$event}\ndata: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
            );
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Récupérer l'historique d'une conversation
     */
    public function getHistory(Request $request, string $conversationId)
    {
        $history = $this->chatbotService->getHistory($conversationId, Auth::id());

        return response()->json($history);
    }

    /**
     * Lister les conversations actives de l'utilisateur
     */
    public function listConversations(Request $request)
    {
        $sanitize = function ($value) {
            if ($value === null) {
                return null;
            }

            $text = (string) $value;

            if (function_exists('iconv')) {
                $clean = iconv('UTF-8', 'UTF-8//IGNORE', $text);
                return $clean === false ? $text : $clean;
            }

            return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        };

        $conversations = ChatbotConversation::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('last_activity_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($conv) use ($sanitize) {
                $lastMessage = $conv->messages()->latest()->first();
                $title = $conv->title ?? 'Conversation sans titre';
                $title = $sanitize($title);
                $lastContent = $lastMessage ? $sanitize($lastMessage->content) : null;

                return [
                    'id' => $conv->session_id,
                    'title' => $title,
                    'last_activity' => $conv->last_activity_at->diffForHumans(),
                    'last_message' => $lastContent ? mb_substr($lastContent, 0, 50) . '...' : null,
                ];
            });

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Supprimer une conversation
     */
    public function deleteConversation(string $conversationId)
    {
        $conversation = ChatbotConversation::where('session_id', $conversationId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $conversation->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation supprimée',
        ]);
    }

    /**
     * Mettre à jour le titre d'une conversation
     */
    public function updateConversationTitle(Request $request, string $conversationId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:40',
        ]);

        $conversation = ChatbotConversation::where('session_id', $conversationId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $conversation->update([
            'title' => trim($validated['title']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Titre mis à jour.',
            'conversation' => [
                'id' => $conversation->session_id,
                'title' => $conversation->title,
            ],
        ]);
    }

    /**
     * Récupérer les préférences utilisateur du chatbot
     */
    public function getPreferences()
    {
        $preferences = ChatbotUserPreference::firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'response_style' => 'standard',
                'response_tone' => 'pedagogique',
                'clarification_mode' => 'auto',
            ]
        );

        return response()->json([
            'success' => true,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Mettre à jour les préférences utilisateur
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'preferred_name' => 'nullable|string|max:80',
            'response_style' => 'required|in:court,standard,detaille',
            'response_tone' => 'required|in:direct,pedagogique,chaleureux',
            'clarification_mode' => 'required|in:auto,always,never',
            'notes' => 'nullable|string|max:500',
        ]);

        $preferences = ChatbotUserPreference::firstOrCreate(['user_id' => Auth::id()]);
        $preferences->fill($validated);
        $preferences->save();

        return response()->json([
            'success' => true,
            'message' => 'Préférences mises à jour.',
            'preferences' => $preferences,
        ]);
    }

    /**
     * Sauvegarder une information mémoire (nom préféré)
     */
    public function saveMemory(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:preferred_name',
            'value' => 'required|string|max:80',
        ]);

        $preferences = ChatbotUserPreference::firstOrCreate(['user_id' => Auth::id()]);
        $preferences->preferred_name = $validated['value'];
        $preferences->save();

        return response()->json([
            'success' => true,
            'message' => 'Nom préféré sauvegardé.',
            'preferences' => $preferences,
        ]);
    }

    public function getMandatoryFraisCategoryForm(Request $request)
    {
        $sessionId = $request->input('conversation_id');
        $formData = $this->chatbotService->buildMandatoryFraisCategoryFormData($sessionId);

        return response()->json([
            'success' => true,
            'message' => 'Remplis ce formulaire pour créer une catégorie de frais obligatoire.',
            'display_type' => 'form',
            'display_data' => $formData,
        ]);
    }

    public function storeMandatoryFraisCategory(Request $request)
    {
        if (!$request->user()->can('frais.create')) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de créer des frais.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|string',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_frais_categories,code',
            'description' => 'nullable|string',
            'default_amount' => 'required|numeric|min:0',
            'payment_deadline_days' => 'required|integer|min:1|max:365',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $conversation = $this->resolveConversation($request->input('conversation_id'));
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation introuvable.',
            ], 404);
        }

        $payload = [
            'conversation_id' => $conversation->session_id,
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'default_amount' => (float) $request->default_amount,
            'payment_deadline_days' => (int) $request->payment_deadline_days,
            'icon' => $request->icon,
            'color' => $request->color,
        ];
        $idempotencyKey = $request->header('Idempotency-Key')
            ?: 'chatbot:frais-category:' . sha1($conversation->id . '|' . $payload['code'] . '|' . $payload['default_amount']);

        $action = ChatbotActionLog::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'action_type' => 'create',
                'model_type' => ESBTPFraisCategory::class,
                'action_data' => [
                    'intent' => 'create_mandatory_frais_category',
                    'payload' => $payload,
                    'summary' => 'Créer la catégorie obligatoire ' . $payload['name'] . ' (' . $payload['code'] . ')',
                ],
                'status' => 'proposed',
                'expires_at' => now()->addHours(24),
            ]
        );

        $displayData = [
            'action_id' => $action->id,
            'status' => $action->status,
            'summary' => $action->action_data['summary'] ?? null,
            'expires_at' => optional($action->expires_at)->toIso8601String(),
            'payload' => $payload,
            'approval' => [
                'approve_url' => route('chatbot.actions.approve', $action),
                'reject_url' => route('chatbot.actions.reject', $action),
                'method' => 'POST',
            ],
        ];

        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => "Action prête à approuver : création de la catégorie obligatoire {$payload['name']}.",
            'display_type' => 'approval_request',
            'display_data' => $displayData,
        ]);

        $context = $conversation->context ?? [];
        $context['pending_action'] = 'approve_chatbot_action';
        $context['pending_action_payload'] = ['action_id' => $action->id];
        $context['last_display'] = 'approval_request';
        $conversation->update([
            'last_activity_at' => now(),
            'context' => $context,
        ]);

        return response()->json([
            'success' => true,
            'approval_required' => true,
            'action_id' => $action->id,
            'message' => $assistantMessage->content,
            'display_type' => $assistantMessage->display_type,
            'display_data' => $assistantMessage->display_data,
            'conversation_id' => $conversation->session_id,
        ], 202);
    }

    public function getFraisConfigForm(Request $request)
    {
        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;
        $sessionId = $request->input('conversation_id');
        $formData = $this->chatbotService->buildFraisConfigFormData($categoryId, $sessionId);

        if (!$formData) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune catégorie de frais obligatoire trouvée.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Configure les montants pour les frais.",
            'display_type' => 'form',
            'display_data' => $formData,
        ]);
    }

    public function approveAction(Request $request, ChatbotActionLog $action)
    {
        if ($action->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action introuvable.',
            ], 404);
        }

        $permission = $this->requiredPermissionForAction($action);
        if (!$permission || !$request->user()->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation d\'approuver cette action.',
            ], 403);
        }

        if ($action->status === 'executed') {
            return response()->json($this->buildExecutedActionResponse($action));
        }

        if ($action->status !== 'proposed') {
            return response()->json([
                'success' => false,
                'message' => 'Cette action ne peut plus être approuvée.',
                'status' => $action->status,
            ], 409);
        }

        if ($action->expires_at && $action->expires_at->isPast()) {
            $action->update(['status' => 'expired']);

            return response()->json([
                'success' => false,
                'message' => 'Cette proposition a expiré.',
                'status' => 'expired',
            ], 409);
        }

        $action->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json($this->executeApprovedAction($action->fresh(), $request));
    }

    public function rejectAction(Request $request, ChatbotActionLog $action)
    {
        if ($action->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action introuvable.',
            ], 404);
        }

        if (!in_array($action->status, ['proposed', 'approved'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Cette action ne peut plus être rejetée.',
                'status' => $action->status,
            ], 409);
        }

        $action->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Action rejetée. Aucune donnée métier n\'a été modifiée.',
            'status' => 'rejected',
        ]);
    }

    public function getInscriptionsFilterForm(Request $request)
    {
        $focusField = $request->input('focus_field');
        $sessionId = $request->input('conversation_id');
        $formData = $this->chatbotService->buildInscriptionsFilterFormData($focusField, $sessionId);

        return response()->json([
            'success' => true,
            'message' => 'Donne-moi les filtres à appliquer.',
            'display_type' => 'form',
            'display_data' => $formData,
        ]);
    }

    public function storeInscriptionsFilter(Request $request)
    {
        if (!$request->user()->can('inscriptions.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de consulter les inscriptions.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|string',
            'search' => 'nullable|string|max:120',
            'filiere_id' => 'nullable|exists:esbtp_filieres,id',
            'niveau_id' => 'nullable|exists:esbtp_niveau_etudes,id',
            'annee_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'status' => 'nullable|string|in:all,active,en_attente,annulée,terminée',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $conversation = $this->resolveConversation($request->input('conversation_id'));
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation introuvable.',
            ], 404);
        }

        $newFilters = [
            'search' => $request->input('search') ?: null,
            'filiere' => $request->input('filiere_id') ?: null,
            'niveau' => $request->input('niveau_id') ?: null,
            'annee' => $request->input('annee_id') ?: null,
            'status' => $request->input('status') ?: null,
        ];

        $existingFilters = $conversation->context['last_filters'] ?? [];
        $mergedFilters = array_filter(array_merge($existingFilters, array_filter($newFilters, static function ($value) {
            return $value !== null && $value !== '';
        })), static function ($value) {
            return $value !== null && $value !== '';
        });

        $deepLink = route('esbtp.inscriptions.index', $mergedFilters);

        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Filtres appliqués. Je te mets le bouton pour ouvrir la liste filtrée.',
            'display_type' => 'text',
            'deep_link' => $deepLink,
        ]);

        $context = $conversation->context ?? [];
        $context['pending_action'] = 'open_page';
        $context['pending_action_payload'] = ['deep_link' => $deepLink];
        $context['last_intent'] = 'get_inscriptions';
        $context['last_filters'] = $mergedFilters;
        $context['last_display'] = 'text';
        $conversation->update([
            'last_activity_at' => now(),
            'context' => $context,
        ]);

        return response()->json([
            'success' => true,
            'message' => $assistantMessage->content,
            'display_type' => $assistantMessage->display_type,
            'deep_link' => $assistantMessage->deep_link,
            'conversation_id' => $conversation->session_id,
            'form_message' => 'Filtres enregistrés. Liste prête.',
        ]);
    }

    public function storeFraisConfig(Request $request)
    {
        if (!$request->user()->can('frais.configure')) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de configurer les frais.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|string',
            'category_id' => 'required|exists:esbtp_frais_categories,id',
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
            'amount_affecte' => 'required|numeric|min:0',
            'amount_reaffecte' => 'required|numeric|min:0',
            'amount_non_affecte' => 'required|numeric|min:0',
            'deadline_days' => 'required|integer|min:1|max:365',
            'installments_allowed' => 'nullable|boolean',
            'max_installments' => 'nullable|integer|min:1|max:12',
            'early_payment_discount' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $conversation = $this->resolveConversation($request->input('conversation_id'));
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation introuvable.',
            ], 404);
        }

        $payload = [
            'conversation_id' => $conversation->session_id,
            'category_id' => (int) $request->category_id,
            'filiere_id' => (int) $request->filiere_id,
            'niveau_id' => (int) $request->niveau_id,
            'amount_affecte' => (float) $request->amount_affecte,
            'amount_reaffecte' => (float) $request->amount_reaffecte,
            'amount_non_affecte' => (float) $request->amount_non_affecte,
            'deadline_days' => (int) $request->deadline_days,
            'installments_allowed' => (bool) $request->installments_allowed,
            'max_installments' => (int) ($request->max_installments ?? 1),
            'early_payment_discount' => (float) ($request->early_payment_discount ?? 0),
        ];
        $mainAmount = $payload['amount_affecte'] > 0
            ? $payload['amount_affecte']
            : ($payload['amount_reaffecte'] > 0 ? $payload['amount_reaffecte'] : $payload['amount_non_affecte']);
        $idempotencyKey = $request->header('Idempotency-Key')
            ?: 'chatbot:frais-config:' . sha1($conversation->id . '|' . $payload['category_id'] . '|' . $payload['filiere_id'] . '|' . $payload['niveau_id'] . '|' . $mainAmount);

        $action = ChatbotActionLog::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'action_type' => 'update',
                'model_type' => ESBTPFraisConfiguration::class,
                'action_data' => [
                    'intent' => 'configure_mandatory_frais',
                    'payload' => $payload,
                    'summary' => 'Configurer le montant de frais pour la filière et le niveau sélectionnés',
                ],
                'status' => 'proposed',
                'expires_at' => now()->addHours(24),
            ]
        );

        $displayData = [
            'action_id' => $action->id,
            'status' => $action->status,
            'summary' => $action->action_data['summary'] ?? null,
            'expires_at' => optional($action->expires_at)->toIso8601String(),
            'payload' => [
                'name' => 'Configuration des frais',
                'code' => 'FRAIS-CONFIG',
                'default_amount' => $mainAmount,
            ],
            'approval' => [
                'approve_url' => route('chatbot.actions.approve', $action),
                'reject_url' => route('chatbot.actions.reject', $action),
                'method' => 'POST',
            ],
        ];

        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Action prête à approuver : configuration des montants de frais.',
            'display_type' => 'approval_request',
            'display_data' => $displayData,
        ]);

        $context = $conversation->context ?? [];
        $context['pending_action'] = 'approve_chatbot_action';
        $context['pending_action_payload'] = ['action_id' => $action->id];
        $context['last_display'] = 'approval_request';
        $conversation->update([
            'last_activity_at' => now(),
            'context' => $context,
        ]);

        return response()->json([
            'success' => true,
            'approval_required' => true,
            'action_id' => $action->id,
            'message' => $assistantMessage->content,
            'display_type' => $assistantMessage->display_type,
            'display_data' => $assistantMessage->display_data,
            'conversation_id' => $conversation->session_id,
        ], 202);
    }

    protected function executeApprovedAction(ChatbotActionLog $action, Request $request): array
    {
        $data = $action->action_data ?? [];

        $intent = $data['intent'] ?? null;
        if (!in_array($intent, ['create_mandatory_frais_category', 'configure_mandatory_frais'], true)) {
            $action->update([
                'status' => 'failed',
                'error_message' => 'Type d\'action IA non supporté.',
            ]);

            return [
                'success' => false,
                'message' => 'Type d\'action IA non supporté.',
                'status' => 'failed',
            ];
        }

        if ($action->status === 'executed') {
            return $this->buildExecutedActionResponse($action);
        }

        if ($intent === 'configure_mandatory_frais') {
            return $this->executeFraisConfigAction($action, $request);
        }

        $payload = $data['payload'] ?? [];
        $conversation = $action->conversation;

        try {
            DB::beginTransaction();

            $category = ESBTPFraisCategory::where('code', $payload['code'])->first();
            if (!$category) {
                $category = ESBTPFraisCategory::create([
                    'name' => $payload['name'],
                    'code' => $payload['code'],
                    'description' => $payload['description'] ?? null,
                    'is_mandatory' => true,
                    'is_active' => true,
                    'category_type' => 'academic',
                    'sort_order' => (ESBTPFraisCategory::max('sort_order') ?? 0) + 1,
                    'default_amount' => $payload['default_amount'],
                    'payment_deadline_days' => $payload['payment_deadline_days'],
                    'icon' => $payload['icon'] ?? null,
                    'color' => $payload['color'] ?? null,
                ]);

                ESBTPFraisOption::create([
                    'configuration_id' => null,
                    'name' => 'Standard',
                    'description' => 'Option standard pour ' . $category->name,
                    'additional_amount' => 0,
                    'is_default' => true,
                    'is_active' => true,
                    'available_from' => now(),
                    'sort_order' => 1,
                ]);
            }

            $action->update([
                'status' => 'executed',
                'model_id' => $category->id,
                'error_message' => null,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $action->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'exécution de l\'action approuvée.',
                'status' => 'failed',
            ];
        }

        $missingPreview = $this->setupGuide->buildMissingStepsPreview(
            $request->user(),
            'financier',
            ['frais_mandatory_configs'],
            1
        );

        $displayData = $missingPreview ?: null;
        if ($displayData) {
            $displayData['follow_up_actions'] = [
                [
                    'label' => 'Configurer par classe ici',
                    'action' => 'open_form',
                    'value' => 'frais_config:' . $category->id,
                ]
            ];
        }

        ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => "Catégorie obligatoire créée après approbation. Veux-tu que je configure les montants par classe maintenant ?",
            'display_type' => $displayData ? 'checklist' : 'text',
            'display_data' => $displayData,
        ]);

        $context = $conversation->context ?? [];
        $context['pending_action'] = 'frais_config_form';
        $context['pending_action_payload'] = ['category_id' => $category->id];
        $context['last_display'] = $displayData ? 'checklist' : 'text';
        $conversation->update([
            'last_activity_at' => now(),
            'context' => $context,
        ]);

        return [
            'success' => true,
            'message' => 'Action approuvée et exécutée.',
            'status' => 'executed',
            'model_id' => $category->id,
            'display_type' => $displayData ? 'checklist' : 'text',
            'display_data' => $displayData,
            'conversation_id' => $conversation->session_id,
        ];
    }

    protected function executeFraisConfigAction(ChatbotActionLog $action, Request $request): array
    {
        $payload = $action->action_data['payload'] ?? [];
        $conversation = $action->conversation;

        try {
            DB::beginTransaction();

            $mainAmount = ($payload['amount_affecte'] ?? 0) > 0
                ? $payload['amount_affecte']
                : (($payload['amount_reaffecte'] ?? 0) > 0 ? $payload['amount_reaffecte'] : ($payload['amount_non_affecte'] ?? 0));

            $configuration = ESBTPFraisConfiguration::updateOrCreate(
                [
                    'frais_category_id' => $payload['category_id'],
                    'filiere_id' => $payload['filiere_id'],
                    'niveau_id' => $payload['niveau_id'],
                    'annee_universitaire_id' => null,
                ],
                [
                    'amount' => $mainAmount,
                    'amount_affecte' => $payload['amount_affecte'],
                    'amount_reaffecte' => $payload['amount_reaffecte'],
                    'amount_non_affecte' => $payload['amount_non_affecte'],
                    'payment_deadline_days' => $payload['deadline_days'],
                    'installments_allowed' => (bool) ($payload['installments_allowed'] ?? false),
                    'max_installments' => $payload['max_installments'] ?? 1,
                    'early_payment_discount' => $payload['early_payment_discount'] ?? 0,
                    'is_active' => true,
                    'effective_date' => now(),
                    'created_by' => $request->user()->id,
                ]
            );

            $action->update([
                'status' => 'executed',
                'model_id' => $configuration->id,
                'error_message' => null,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $action->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'exécution de la configuration approuvée.',
                'status' => 'failed',
            ];
        }

        $stepContext = $this->setupGuide->getStepContext($request->user(), 'financier', 'inscriptions');
        $missingPreview = $this->setupGuide->buildMissingStepsPreview(
            $request->user(),
            'financier',
            $stepContext['missing_prerequisite_ids'] ?? [],
            2
        );

        $displayData = $missingPreview ?: null;

        ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Configuration enregistrée après approbation. On peut passer à l\'inscription.',
            'display_type' => $displayData ? 'checklist' : 'text',
            'display_data' => $displayData,
        ]);

        $context = $conversation->context ?? [];
        $context['pending_action'] = null;
        $context['pending_action_payload'] = null;
        $context['last_display'] = $displayData ? 'checklist' : 'text';
        $conversation->update([
            'last_activity_at' => now(),
            'context' => $context,
        ]);

        return [
            'success' => true,
            'message' => 'Configuration approuvée et exécutée.',
            'status' => 'executed',
            'model_id' => $configuration->id,
            'display_type' => $displayData ? 'checklist' : 'text',
            'display_data' => $displayData,
            'conversation_id' => $conversation->session_id,
        ];
    }

    protected function requiredPermissionForAction(ChatbotActionLog $action): ?string
    {
        return match ($action->action_data['intent'] ?? null) {
            'create_mandatory_frais_category' => 'frais.create',
            'configure_mandatory_frais' => 'frais.configure',
            default => null,
        };
    }

    protected function buildExecutedActionResponse(ChatbotActionLog $action): array
    {
        return [
            'success' => true,
            'message' => 'Action déjà exécutée.',
            'status' => 'executed',
            'model_id' => $action->model_id,
            'conversation_id' => $action->conversation?->session_id,
        ];
    }

    protected function resolveConversation(?string $conversationId): ?ChatbotConversation
    {
        if (!$conversationId) {
            return null;
        }

        return ChatbotConversation::where('session_id', $conversationId)
            ->where('user_id', Auth::id())
            ->first();
    }
}
