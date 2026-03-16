<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\ChatbotSetupGuideService;
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

        try {
            DB::beginTransaction();

            $category = ESBTPFraisCategory::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'is_mandatory' => true,
                'is_active' => true,
                'category_type' => 'academic',
                'sort_order' => (ESBTPFraisCategory::max('sort_order') ?? 0) + 1,
                'default_amount' => $request->default_amount,
                'payment_deadline_days' => $request->payment_deadline_days,
                'icon' => $request->icon,
                'color' => $request->color,
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

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la catégorie.',
            ], 500);
        }

        $missingPreview = $this->setupGuide->buildMissingStepsPreview(
            $request->user(),
            'financier',
            ['frais_mandatory_configs'],
            1
        );

        if (!$missingPreview) {
            $missingPreview = null;
        }

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

        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => "Catégorie obligatoire créée. Veux-tu que je configure les montants par classe maintenant ?",
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

        return response()->json([
            'success' => true,
            'message' => $assistantMessage->content,
            'display_type' => $assistantMessage->display_type,
            'display_data' => $assistantMessage->display_data,
            'conversation_id' => $conversation->session_id,
        ]);
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
        if (!$request->user()->can('view_inscriptions')) {
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

        try {
            DB::beginTransaction();

            $mainAmount = $request->amount_affecte > 0
                ? $request->amount_affecte
                : ($request->amount_reaffecte > 0 ? $request->amount_reaffecte : $request->amount_non_affecte);

            ESBTPFraisConfiguration::updateOrCreate(
                [
                    'frais_category_id' => $request->category_id,
                    'filiere_id' => $request->filiere_id,
                    'niveau_id' => $request->niveau_id,
                    'annee_universitaire_id' => null,
                ],
                [
                    'amount' => $mainAmount,
                    'amount_affecte' => $request->amount_affecte,
                    'amount_reaffecte' => $request->amount_reaffecte,
                    'amount_non_affecte' => $request->amount_non_affecte,
                    'payment_deadline_days' => $request->deadline_days,
                    'installments_allowed' => (bool) $request->installments_allowed,
                    'max_installments' => $request->max_installments ?? 1,
                    'early_payment_discount' => $request->early_payment_discount ?? 0,
                    'is_active' => true,
                    'effective_date' => now(),
                    'created_by' => $request->user()->id,
                ]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la configuration des frais.',
            ], 500);
        }

        $stepContext = $this->setupGuide->getStepContext($request->user(), 'financier', 'inscriptions');
        $missingPreview = $this->setupGuide->buildMissingStepsPreview(
            $request->user(),
            'financier',
            $stepContext['missing_prerequisite_ids'] ?? [],
            2
        );

        $displayData = $missingPreview ?: null;
        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Configuration enregistrée. On peut passer à l\'inscription. Tu veux que je te guide pour la première inscription ?',
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

        return response()->json([
            'success' => true,
            'message' => $assistantMessage->content,
            'display_type' => $assistantMessage->display_type,
            'display_data' => $assistantMessage->display_data,
            'conversation_id' => $conversation->session_id,
        ]);
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
