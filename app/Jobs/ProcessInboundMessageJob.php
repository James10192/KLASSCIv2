<?php

namespace App\Jobs;

use App\Models\WhatsAppInboundMessage;
use App\Services\WhatsApp\Chatbot\ChatbotGeminiService;
use App\Services\WhatsApp\PhoneToParentResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job queue traitant un message entrant WhatsApp (Phase 7 + 10 Plan v4).
 *
 * Pipeline :
 *  1. Récupère WhatsAppInboundMessage by id
 *  2. PhoneToParentResolver → parent + étudiant (peut être null si inconnu)
 *  3. Met à jour message.parent_id + etudiant_id
 *  4. Si chatbot activé : ChatbotGeminiService.answer()
 *     - Si confidence ≥ threshold → auto-reply via WhatsAppReplyService
 *     - Sinon → message reste en status='unread' → escalation manuel UI
 *  5. Si chatbot disabled → escalation directe (notif secrétaire)
 *
 * Queue dédiée 'whatsapp_inbound' isolée du flow outbound.
 */
class ProcessInboundMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(public readonly int $inboundMessageId)
    {
        $this->onQueue('whatsapp_inbound');
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(
        PhoneToParentResolver $resolver,
        ChatbotGeminiService $chatbot,
    ): void {
        $message = WhatsAppInboundMessage::find($this->inboundMessageId);

        if (! $message) {
            Log::warning('[wa-inbound] Message introuvable', ['id' => $this->inboundMessageId]);
            return;
        }

        // 1. Lookup parent + étudiant
        $resolved = $resolver->resolve($message->from_phone);

        if ($resolved['parent']) {
            $message->update([
                'parent_id' => $resolved['parent']->id,
                'etudiant_id' => $resolved['primary_etudiant']?->id,
            ]);
        }

        // 2. Chatbot auto-reply (Phase 10)
        $aiAnswer = $chatbot->answer($message->body ?? '', $resolved['primary_etudiant']);

        if (! $aiAnswer['escalate'] && ! empty($aiAnswer['response'])) {
            // Auto-reply via WhatsAppReplyService (à implémenter Phase 7 step 2/2)
            // Pour l'instant on log uniquement — l'envoi réel viendra avec
            // app(WhatsAppReplyService::class)->reply($message, $aiAnswer['response'], system_user_id);
            Log::info('[wa-inbound] Auto-reply prêt (envoi pending Phase 7 step 2/2)', [
                'message_id' => $message->id,
                'intent' => $aiAnswer['intent'],
                'confidence' => $aiAnswer['confidence'],
                'response_preview' => substr($aiAnswer['response'], 0, 80),
            ]);
            return;
        }

        // 3. Escalation : message reste unread + assignation auto (à venir Phase 11)
        Log::info('[wa-inbound] Escalation manuelle requise', [
            'message_id' => $message->id,
            'reason' => $aiAnswer['reason'] ?? 'low_confidence',
            'intent' => $aiAnswer['intent'] ?? 'unknown',
        ]);
    }
}
