<?php

namespace App\Jobs;

use App\Models\WhatsAppInboundMessage;
use App\Services\WhatsApp\Chatbot\ChatbotGeminiService;
use App\Services\WhatsApp\FaqRouter;
use App\Services\WhatsApp\PhoneToParentResolver;
use App\Services\WhatsApp\PiiMasker;
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
        FaqRouter $faqRouter,
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

        $body = $message->body ?? '';

        // 2. Phase 11 — FAQ pattern matching d'abord (gratuit + instantané)
        // Si match : auto-reply immédiat, économise un appel Gemini IA payant
        $faqMatch = $faqRouter->route($body);

        if ($faqMatch['matched'] && ! empty($faqMatch['response'])) {
            Log::info('[wa-inbound] FAQ match — auto-reply pattern', [
                'message_id' => $message->id,
                'intent' => $faqMatch['intent'] ?? 'general',
                'from_phone' => PiiMasker::phone($message->from_phone),
                'preview' => PiiMasker::messagePreview($body),
            ]);

            // Auto-reply via WhatsAppReplyService (Phase 7 step 2/2 — placeholder)
            // app(WhatsAppReplyService::class)->reply($message, $faqMatch['response'], 'system_faq');

            // Marquer comme replied
            $message->update([
                'status' => 'replied',
                'replied_at' => now(),
            ]);
            return;
        }

        // 3. Phase 10 — Chatbot Gemini IA (escalation FAQ vers IA)
        $aiAnswer = $chatbot->answer($body, $resolved['primary_etudiant']);

        if (! $aiAnswer['escalate'] && ! empty($aiAnswer['response'])) {
            // Auto-reply via WhatsAppReplyService (à implémenter Phase 7 step 2/2)
            Log::info('[wa-inbound] Auto-reply IA prêt (envoi pending Phase 7 step 2/2)', [
                'message_id' => $message->id,
                'intent' => $aiAnswer['intent'],
                'confidence' => $aiAnswer['confidence'],
                'preview' => PiiMasker::messagePreview($aiAnswer['response']),
            ]);
            return;
        }

        // 4. Escalation manuelle : message reste unread, secrétaire à notifier (Phase 7 inbox UI)
        Log::info('[wa-inbound] Escalation manuelle requise', [
            'message_id' => $message->id,
            'from_phone' => PiiMasker::phone($message->from_phone),
            'reason' => $aiAnswer['reason'] ?? 'low_confidence',
            'intent' => $aiAnswer['intent'] ?? 'unknown',
        ]);
    }
}
