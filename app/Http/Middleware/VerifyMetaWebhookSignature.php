<?php

namespace App\Http\Middleware;

use App\Services\WhatsApp\TenantConfigResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware HMAC SHA-256 signature verification pour webhooks Meta.
 *
 * Meta signe chaque POST webhook avec X-Hub-Signature-256 (HMAC SHA-256 du raw body
 * en utilisant l'app_secret comme clé). Sans cette vérif, n'importe qui peut spoofer
 * des delivery receipts ou des messages entrants → corruption parent_notification_logs.
 *
 * Documentation Meta :
 * https://developers.facebook.com/docs/graph-api/webhooks/getting-started#payload
 *
 * Applique sur : POST /api/webhooks/whatsapp (Phase 3 Plan v4)
 * GET (verify endpoint) skip cette vérif car pas de body signé.
 */
class VerifyMetaWebhookSignature
{
    public function __construct(
        protected TenantConfigResolver $configResolver,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // GET = verify endpoint (Meta validates URL ownership), pas de body à signer
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (empty($signature)) {
            Log::warning('[meta-webhook] Missing X-Hub-Signature-256 header');

            return response('Forbidden — missing signature', 403);
        }

        $config = $this->configResolver->getConfig();
        $appSecret = $config['access_token'] ?? null;

        if (empty($appSecret)) {
            Log::warning('[meta-webhook] Tenant whatsapp not configured');

            return response('Forbidden — tenant not configured', 403);
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('[meta-webhook] Invalid HMAC signature', [
                'received_prefix' => substr($signature, 0, 16),
            ]);

            return response('Forbidden — invalid signature', 403);
        }

        return $next($request);
    }
}
