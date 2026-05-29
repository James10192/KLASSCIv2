<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\TenantConfigResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Meta WhatsApp Cloud API — Phase 3 Plan v4.
 *
 * Gère 2 types de payloads entrants de Meta :
 *
 *  1. STATUSES — delivery / read receipts pour les messages sortants
 *     → met à jour parent_notification_logs.status (sent → delivered → read)
 *
 *  2. MESSAGES — messages entrants des parents (chat 2-way Phase 7)
 *     → crée whatsapp_inbound_messages, route vers parent → étudiant
 *     → gère STOP keyword (opt-out auto)
 *
 * Sécurité :
 * - Verify GET endpoint (Meta validates URL ownership via verify_token)
 * - HMAC SHA-256 signature verification (X-Hub-Signature-256) — middleware
 * - Idempotency par message_id Meta (évite double-traitement sur retry)
 * - CSRF exempt (Meta n'envoie pas de CSRF token)
 *
 * Endpoint : GET/POST /api/webhooks/whatsapp
 *
 * @see app/Http/Middleware/VerifyMetaWebhookSignature.php (HMAC verify)
 * @see Phase 7 — chat 2-way inbox UI premium consommateur
 */
class MetaWhatsAppWebhookController extends Controller
{
    public function __construct(
        protected TenantConfigResolver $configResolver,
    ) {
    }

    /**
     * Verify GET — Meta valide l'URL du webhook lors du setup.
     *
     * Meta envoie : GET /api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=XYZ&hub.challenge=ABC
     * On répond avec hub.challenge en plain text si verify_token matche.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $config = $this->configResolver->getConfig();
        $expectedToken = $config['webhook_verify_token'] ?? null;

        if ($mode === 'subscribe' && $token === $expectedToken && $challenge) {
            Log::info('[whatsapp-webhook] Verify GET success');

            return response($challenge, 200, ['Content-Type' => 'text/plain']);
        }

        Log::warning('[whatsapp-webhook] Verify GET failed', [
            'mode' => $mode,
            'token_match' => $token === $expectedToken,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle POST — Meta envoie status updates ET messages entrants.
     *
     * Structure payload (simplifiée) :
     * {
     *   "object": "whatsapp_business_account",
     *   "entry": [{
     *     "id": "BUSINESS_ACCOUNT_ID",
     *     "changes": [{
     *       "value": {
     *         "messaging_product": "whatsapp",
     *         "metadata": {...},
     *         "statuses": [...],   // delivery/read receipts
     *         "messages": [...]    // inbound messages
     *       },
     *       "field": "messages"
     *     }]
     *   }]
     * }
     *
     * Toujours retourner 200 OK rapidement (Meta retry agressif sur non-2xx).
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            $entries = $payload['entry'] ?? [];
            $statusesProcessed = 0;
            $messagesProcessed = 0;

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];

                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];

                    // 1. Status updates (delivery/read/failed)
                    foreach ($value['statuses'] ?? [] as $status) {
                        $this->processStatus($status);
                        $statusesProcessed++;
                    }

                    // 2. Inbound messages (chat 2-way Phase 7)
                    foreach ($value['messages'] ?? [] as $message) {
                        $this->processInboundMessage($message, $value['metadata'] ?? []);
                        $messagesProcessed++;
                    }
                }
            }

            Log::info('[whatsapp-webhook] Payload processed', [
                'statuses' => $statusesProcessed,
                'messages' => $messagesProcessed,
            ]);

            return response()->json(['received' => true]);
        } catch (\Throwable $e) {
            Log::error('[whatsapp-webhook] Exception handling payload', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Toujours 200 OK pour éviter retry Meta sur erreur transitoire serveur
            return response()->json(['received' => true]);
        }
    }

    /**
     * Met à jour parent_notification_logs.status depuis le status webhook Meta.
     * Idempotent : si déjà à un statut plus avancé (delivered → read), pas de regression.
     */
    private function processStatus(array $status): void
    {
        $messageId = $status['id'] ?? null;
        $newStatus = $status['status'] ?? null; // sent / delivered / read / failed

        if (! $messageId || ! $newStatus) {
            return;
        }

        // Mapping vers statuts internes parent_notification_logs
        $internalStatus = match ($newStatus) {
            'sent' => 'sent',
            'delivered' => 'delivered',
            'read' => 'read',
            'failed' => 'failed',
            default => null,
        };

        if (! $internalStatus) {
            return;
        }

        try {
            DB::table('parent_notification_logs')
                ->where('external_id', $messageId)
                ->whereNotIn('status', $this->statusesAfter($internalStatus))
                ->update([
                    'status' => $internalStatus,
                    'delivered_at' => $internalStatus === 'delivered' ? now() : DB::raw('delivered_at'),
                    'read_at' => $internalStatus === 'read' ? now() : DB::raw('read_at'),
                    'failed_at' => $internalStatus === 'failed' ? now() : DB::raw('failed_at'),
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            Log::error('[whatsapp-webhook] Status update failed', [
                'message_id' => $messageId,
                'status' => $internalStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Crée un whatsapp_inbound_messages + gère STOP keyword (opt-out).
     * Phase 7 — chat 2-way inbox UI premium.
     */
    private function processInboundMessage(array $message, array $metadata): void
    {
        $messageId = $message['id'] ?? null;
        $from = $message['from'] ?? null;
        $type = $message['type'] ?? 'text';
        $body = $message['text']['body'] ?? null;
        $timestamp = $message['timestamp'] ?? time();

        if (! $messageId || ! $from) {
            return;
        }

        // Idempotency : skip si déjà traité
        $exists = DB::table('whatsapp_inbound_messages')
            ->where('message_id', $messageId)
            ->exists();

        if ($exists) {
            return;
        }

        try {
            DB::table('whatsapp_inbound_messages')->insert([
                'message_id' => $messageId,
                'from_phone' => $from,
                'to_number' => $metadata['display_phone_number'] ?? null,
                'type' => $type,
                'body' => $body,
                'received_at' => date('Y-m-d H:i:s', (int) $timestamp),
                'status' => 'unread',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // STOP keyword → désactive opt-in WhatsApp pour ce numéro
            if ($body && in_array(strtoupper(trim($body)), ['STOP', 'ARRET', 'ARRÊT', 'UNSUBSCRIBE'], true)) {
                $this->handleStopKeyword($from);
            }
        } catch (\Throwable $e) {
            Log::error('[whatsapp-webhook] Inbound message insert failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gère le mot-clé STOP — désactive le canal WhatsApp dans les préférences du parent.
     */
    private function handleStopKeyword(string $phone): void
    {
        try {
            $parent = DB::table('esbtp_parents')->where('telephone', $phone)->first();

            if (! $parent) {
                Log::info('[whatsapp-webhook] STOP reçu mais parent introuvable', ['phone' => $phone]);
                return;
            }

            DB::table('parent_notification_preferences')
                ->where('parent_id', $parent->id)
                ->update([
                    'preferred_channels' => json_encode(['app', 'email']),
                    'updated_at' => now(),
                ]);

            Log::info('[whatsapp-webhook] Opt-out WhatsApp via STOP', [
                'parent_id' => $parent->id,
                'phone' => $phone,
            ]);
        } catch (\Throwable $e) {
            Log::error('[whatsapp-webhook] STOP keyword handling failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Retourne les statuts "plus avancés" (pour idempotency anti-regression).
     */
    private function statusesAfter(string $status): array
    {
        return match ($status) {
            'sent' => ['delivered', 'read', 'failed'],
            'delivered' => ['read'],
            'read' => [],
            'failed' => [],
            default => [],
        };
    }
}
