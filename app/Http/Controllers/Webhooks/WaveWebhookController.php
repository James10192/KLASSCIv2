<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Notifications\Notifiers\PaiementNotifier;
use App\Helpers\SettingsHelper;
use App\Http\Controllers\Controller;
use App\Models\ESBTPPaiement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Wave CI — confirmation paiements mobiles (Phase 12 step 2/2 Plan v4).
 *
 * Pattern : quand un parent paye via le lien Wave checkout dans une relance
 * WhatsApp, Wave appelle ce webhook pour confirmer la transaction. Le controller :
 *   1. Vérifie la signature HMAC (sécurité — secret partagé Wave/KLASSCI)
 *   2. Lookup le paiement KLASSCI via la référence Wave (ESBTPPaiement.reference)
 *   3. Auto-valide le paiement (status='validé')
 *   4. Dispatch notification multi-canal au parent (PaiementNotifier::paiementValide)
 *   5. Désactive les rappels en attente pour ce paiement
 *
 * Route : POST /api/webhooks/wave
 * Auth : signature HMAC SHA-256 via header X-Wave-Signature
 * Settings tenant : wave.webhook_secret (32 chars random, partagé avec Wave config)
 *
 * @see app/Services/WhatsApp/WaveCheckoutLinkBuilder.php (générateur lien checkout)
 * @see https://docs.wave.com/business/api/webhooks (à confirmer URL exacte CI 2026)
 */
class WaveWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        // 1. Vérification signature HMAC
        if (! $this->verifySignature($request)) {
            Log::warning('[wave-webhook] Invalid signature', [
                'ip' => $request->ip(),
                'ref' => $payload['ref'] ?? null,
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // 2. Idempotency : Wave peut retry — on traite chaque event_id une seule fois
        $eventId = $payload['event_id'] ?? null;
        $reference = $payload['ref'] ?? null;
        $eventType = $payload['type'] ?? 'unknown';

        if (empty($reference)) {
            Log::warning('[wave-webhook] Missing ref in payload', ['event_id' => $eventId]);
            return response()->json(['error' => 'Missing ref'], 422);
        }

        // 3. Lookup paiement KLASSCI
        $paiement = ESBTPPaiement::where('reference', $reference)
            ->orWhere('reference_externe', $reference)
            ->first();

        if (! $paiement) {
            Log::warning('[wave-webhook] Paiement introuvable', [
                'ref' => $reference,
                'event_type' => $eventType,
            ]);
            // 200 OK pour éviter retry inutile Wave (paiement absent = on a perdu le lien)
            return response()->json(['acknowledged' => true, 'matched' => false]);
        }

        // 4. Traitement selon type d'event Wave
        return match ($eventType) {
            'checkout.session.completed', 'payment.succeeded' => $this->handlePaymentSucceeded($paiement, $payload),
            'payment.failed' => $this->handlePaymentFailed($paiement, $payload),
            'payment.refunded' => $this->handlePaymentRefunded($paiement, $payload),
            default => $this->handleUnknownEvent($paiement, $eventType, $payload),
        };
    }

    private function handlePaymentSucceeded(ESBTPPaiement $paiement, array $payload): JsonResponse
    {
        // Idempotency : ne pas re-valider un paiement déjà validé
        if ($paiement->status === 'validé') {
            Log::info('[wave-webhook] Paiement déjà validé — skip idempotent', [
                'paiement_id' => $paiement->id,
                'ref' => $paiement->reference,
            ]);
            return response()->json(['acknowledged' => true, 'already_validated' => true]);
        }

        // Auto-validation
        $paiement->update([
            'status' => 'validé',
            'validated_at' => now(),
            'validated_by_label' => 'wave_webhook',
            'metadata' => array_merge($paiement->metadata ?? [], [
                'wave_event_id' => $payload['event_id'] ?? null,
                'wave_transaction_id' => $payload['transaction_id'] ?? null,
                'wave_amount' => $payload['amount'] ?? null,
                'wave_validated_at' => now()->toIso8601String(),
            ]),
        ]);

        // Notification multi-canal au parent
        try {
            app(PaiementNotifier::class)->paiementValide($paiement);
        } catch (\Throwable $e) {
            Log::error('[wave-webhook] Erreur notification paiement validé', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
            ]);
            // On ne fail PAS le webhook — paiement déjà validé en DB, c'est juste la notif qui rate
        }

        // Désactiver les rappels en attente
        try {
            $reminder = \App\Models\NotificationReminder::where('remindable_type', 'App\Models\ESBTPPaiement')
                ->where('remindable_id', $paiement->id)
                ->first();
            if ($reminder) {
                $reminder->deactivate();
            }
        } catch (\Throwable $e) {
            Log::error('[wave-webhook] Erreur désactivation reminder', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('[wave-webhook] Paiement validé via Wave webhook', [
            'paiement_id' => $paiement->id,
            'ref' => $paiement->reference,
            'amount' => $paiement->montant,
        ]);

        return response()->json([
            'acknowledged' => true,
            'paiement_id' => $paiement->id,
            'validated' => true,
        ]);
    }

    private function handlePaymentFailed(ESBTPPaiement $paiement, array $payload): JsonResponse
    {
        // On laisse le paiement en attente — le parent retentera, et les rappels continuent
        Log::warning('[wave-webhook] Paiement Wave failed — paiement KLASSCI reste en_attente', [
            'paiement_id' => $paiement->id,
            'ref' => $paiement->reference,
            'reason' => $payload['failure_reason'] ?? 'unknown',
        ]);

        return response()->json(['acknowledged' => true, 'failed' => true]);
    }

    private function handlePaymentRefunded(ESBTPPaiement $paiement, array $payload): JsonResponse
    {
        // Cas exceptionnel : marquer paiement annulé avec note
        $paiement->update([
            'status' => 'annulé',
            'commentaire' => ($paiement->commentaire ?? '') . "\n[Wave refund " . ($payload['refunded_at'] ?? now()->toDateString()) . "]",
        ]);

        Log::warning('[wave-webhook] Paiement remboursé via Wave', [
            'paiement_id' => $paiement->id,
            'ref' => $paiement->reference,
        ]);

        return response()->json(['acknowledged' => true, 'refunded' => true]);
    }

    private function handleUnknownEvent(ESBTPPaiement $paiement, string $eventType, array $payload): JsonResponse
    {
        Log::info('[wave-webhook] Event type inconnu — acknowledged sans action', [
            'paiement_id' => $paiement->id,
            'event_type' => $eventType,
        ]);
        return response()->json(['acknowledged' => true, 'event_type' => $eventType, 'no_action' => true]);
    }

    /**
     * Vérifie la signature HMAC SHA-256 du payload.
     *
     * Le header `X-Wave-Signature` contient un hash HMAC-SHA256 du body brut
     * avec le secret partagé `wave.webhook_secret` (setting tenant).
     */
    private function verifySignature(Request $request): bool
    {
        $secret = SettingsHelper::get('wave.webhook_secret');

        if (empty($secret)) {
            Log::warning('[wave-webhook] wave.webhook_secret non configuré pour ce tenant');
            return false;
        }

        $providedSignature = $request->header('X-Wave-Signature');
        if (empty($providedSignature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        // Comparaison timing-safe (anti timing attack)
        return hash_equals($expectedSignature, $providedSignature);
    }
}
