<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnnonce;
use App\Models\WhatsAppInboundMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hub Communications — vue unifiée des 4 canaux (Phase 13 Plan v4 WhatsApp).
 *
 * Centralise dans une seule page premium tabs :
 *  1. Annonces (broadcast école → étudiants/parents)
 *  2. Messages app (in-app notifications/threads)
 *  3. WhatsApp Inbox (chat 2-way parents — Phase 7)
 *  4. Chatbot review (queue modération IA — Phase 10)
 *
 * Permissions : module.communication.access requis pour l'accès au hub.
 * Chaque tab a sa permission propre (annonces.view, messages.receive,
 * whatsapp.inbox.view, whatsapp.chatbot.review) — tabs cachés si user n'a pas.
 *
 * Namespace CSS : `ch-*` (rule premium-redesign.md — préfixe 2-3 lettres unique)
 */
class ESBTPCommunicationsHubController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        // KPIs hub
        $kpis = $this->buildKpis();

        // Permissions par onglet (le Blade filtre l'affichage selon @can)
        $perms = [
            'annonces' => $user?->can('annonces.view') ?? false,
            'messages' => $user?->can('messages.receive') ?? false,
            'whatsapp_inbox' => $user?->can('whatsapp.inbox.view') ?? false,
            'chatbot_review' => $user?->can('whatsapp.chatbot.review') ?? false,
        ];

        return view('esbtp.communications.hub', [
            'kpis' => $kpis,
            'perms' => $perms,
        ]);
    }

    /**
     * Construit les KPIs affichés dans le hero du hub.
     *
     * Sources :
     *  - annonces : count des annonces is_published des 7 derniers jours
     *  - messages : count des notifications custom is_read=false pour user courant
     *  - whatsapp_inbox : count des messages entrants status='unread' (table whatsapp_inbound_messages)
     *  - chatbot_review : count des AI réponses pending review (placeholder Phase 10)
     */
    private function buildKpis(): array
    {
        $userId = Auth::id();

        $annoncesRecent = ESBTPAnnonce::query()
            ->where('is_published', true)
            ->where('date_publication', '>=', now()->subDays(7))
            ->count();

        $messagesUnread = DB::table('custom_notifications')
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        // WhatsApp inbox — table peut ne pas exister sur tous les tenants (migration pending)
        $whatsappUnread = 0;
        if (Schema::hasTable('whatsapp_inbound_messages')) {
            $whatsappUnread = WhatsAppInboundMessage::query()
                ->where('status', 'unread')
                ->count();
        }

        // Chatbot review queue — Phase 10 future (table whatsapp_outbound_replies status='pending_review')
        $chatbotPending = 0;
        if (Schema::hasTable('whatsapp_outbound_replies')) {
            $chatbotPending = DB::table('whatsapp_outbound_replies')
                ->where('status', 'pending_review')
                ->count();
        }

        return [
            'annonces_recent' => $annoncesRecent,
            'messages_unread' => $messagesUnread,
            'whatsapp_unread' => $whatsappUnread,
            'chatbot_pending' => $chatbotPending,
        ];
    }
}
