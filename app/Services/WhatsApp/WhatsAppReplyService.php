<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppInboundMessage;
use App\Models\WhatsAppOutboundReply;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'envoi de réponses WhatsApp manuelles ou auto-IA (Phase 7 + 10 Plan v4).
 *
 * Gère la fenêtre service 24h Meta :
 *  - Dans les 24h après dernier message parent → reply texte libre GRATUIT
 *  - Hors fenêtre 24h → obligatoire template UTILITY pré-approuvé (~2.4 FCFA)
 *
 * Logue dans whatsapp_outbound_replies pour audit + cost tracking + KPI.
 *
 * @see app/Http/Controllers/Webhooks/MetaWhatsAppWebhookController.php (consumer ack)
 * @see app/Services/WhatsApp/WhatsAppService.php (sendTemplateMessage fallback)
 */
class WhatsAppReplyService
{
    public function __construct(
        protected TenantConfigResolver $configResolver,
    ) {
    }

    /**
     * Envoie une réponse texte libre dans la fenêtre service 24h Meta.
     *
     * @return array{success: bool, reply_id?: int, meta_message_id?: string, error?: string}
     */
    public function replyText(WhatsAppInboundMessage $inbound, string $body, User $sentBy): array
    {
        if (! $inbound->isWithinServiceWindow()) {
            return [
                'success' => false,
                'error' => 'Hors fenêtre service 24h — utiliser un template via replyTemplate()',
            ];
        }

        $reply = WhatsAppOutboundReply::create([
            'inbound_message_id' => $inbound->id,
            'sent_by_user_id' => $sentBy->id,
            'body' => $body,
            'type' => 'text',
            'status' => 'pending',
            'cost_fcfa' => 0, // Gratuit dans service window
        ]);

        $config = $this->configResolver->getConfig();

        if (! ($config['enabled'] ?? false)) {
            $reply->update(['status' => 'failed', 'error_message' => 'WhatsApp disabled for tenant']);
            return ['success' => false, 'error' => 'WhatsApp disabled for tenant', 'reply_id' => $reply->id];
        }

        try {
            $response = Http::withToken($config['access_token'])
                ->timeout(10)
                ->post("https://graph.facebook.com/v18.0/{$config['phone_number_id']}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $inbound->from_phone,
                    'type' => 'text',
                    'text' => ['body' => $body],
                ]);

            if (! $response->successful()) {
                $reply->update([
                    'status' => 'failed',
                    'error_message' => "Meta HTTP {$response->status()}: " . $response->body(),
                    'failed_at' => now(),
                ]);
                return ['success' => false, 'error' => "Meta API status {$response->status()}", 'reply_id' => $reply->id];
            }

            $metaId = $response->json('messages.0.id');

            $reply->update([
                'status' => 'sent',
                'meta_message_id' => $metaId,
                'sent_at' => now(),
            ]);

            $inbound->markAsReplied($sentBy->id);

            return ['success' => true, 'reply_id' => $reply->id, 'meta_message_id' => $metaId];
        } catch (\Throwable $e) {
            $reply->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            Log::error('[wa-reply] Exception envoi', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage(), 'reply_id' => $reply->id];
        }
    }
}
