<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ChatbotService;
use App\Models\ChatbotConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    protected ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->middleware('auth');
        $this->chatbotService = $chatbotService;
    }

    /**
     * Envoyer un message au chatbot
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|string',
        ]);

        $response = $this->chatbotService->sendMessage(
            $validated['message'],
            $validated['conversation_id'] ?? null
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
        $conversations = ChatbotConversation::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('last_activity_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($conv) {
                $lastMessage = $conv->messages()->latest()->first();

                return [
                    'id' => $conv->session_id,
                    'title' => $conv->title ?? 'Conversation sans titre',
                    'last_activity' => $conv->last_activity_at->diffForHumans(),
                    'last_message' => $lastMessage ? substr($lastMessage->content, 0, 50) . '...' : null,
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
}
