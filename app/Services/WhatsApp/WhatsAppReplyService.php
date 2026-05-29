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
    private const SERVICE_WINDOW_HOURS = 24;

    public function __construct(
        protected TenantConfigResolver $configResolver,
    ) {
    }

    /**
     * Retourne le temps restant dans la fenêtre service 24h en minutes.
     *
     * @return int 0 si fenêtre expirée, sinon minutes restantes (max 1440)
     */
    public function minutesRemainingInWindow(WhatsAppInboundMessage $inbound): int
    {
        if (! $inbound->received_at) {
            return 0;
        }

        $minutesElapsed = $inbound->received_at->diffInMinutes(now());
        $minutesMax = self::SERVICE_WINDOW_HOURS * 60;

        return max(0, $minutesMax - $minutesElapsed);
    }

    /**
     * Status visuel fenêtre service pour UI countdown (Phase 7 inbox).
     *
     * @return array{minutes_remaining: int, status: string, color: string, label: string}
     *   status : 'open' (>4h restantes) | 'warning' (1-4h) | 'critical' (<1h) | 'expired'
     */
    public function windowStatus(WhatsAppInboundMessage $inbound): array
    {
        $minutes = $this->minutesRemainingInWindow($inbound);

        $status = match (true) {
            $minutes === 0 => 'expired',
            $minutes < 60 => 'critical',
            $minutes < 240 => 'warning',
            default => 'open',
        };

        $color = match ($status) {
            'expired' => '#94a3b8', // gray
            'critical' => '#dc2626', // red
            'warning' => '#f59e0b', // orange
            'open' => '#10b981', // green
            default => '#94a3b8',
        };

        $label = match ($status) {
            'expired' => 'Fenêtre 24h expirée — template requis',
            'critical' => sprintf('Reste %d min — répondre vite', $minutes),
            'warning' => sprintf('Reste %d h %d min', intdiv($minutes, 60), $minutes % 60),
            'open' => sprintf('Fenêtre ouverte : %d h restantes', intdiv($minutes, 60)),
            default => '',
        };

        return [
            'minutes_remaining' => $minutes,
            'status' => $status,
            'color' => $color,
            'label' => $label,
        ];
    }

    /**
     * Helper : la fenêtre est-elle encore active ?
     * Wrapper du model->isWithinServiceWindow() pour accès depuis le service.
     */
    public function isInServiceWindow(WhatsAppInboundMessage $inbound): bool
    {
        return $this->minutesRemainingInWindow($inbound) > 0;
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
