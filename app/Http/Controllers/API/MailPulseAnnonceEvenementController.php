<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contrat avec MailPulse, PAS avec Resend directement.
 * HMAC(timestamp.body) et horodatage limite pour eviter les faux statuts.
 * Endpoint inactif tant que le secret n'est pas configure.
 */
class MailPulseAnnonceEvenementController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $secret = (string) config('services.mailpulse.announcement_webhook_secret');
        $stamp = $request->header('X-MailPulse-Timestamp', '');
        $signature = $request->header('X-MailPulse-Signature', '');
        if ($secret === '' || ! ctype_digit((string) $stamp)
            || abs(time() - (int) $stamp) > 300 || ! is_string($signature)
            || ! preg_match('/^[a-f0-9]{64}$/i', $signature)
            || ! hash_equals(hash_hmac('sha256', $stamp.'.'.$request->getContent(), $secret), strtolower($signature))) {
            return response()->json(['message' => 'Signature invalide.'], 401);
        }

        $valide = $request->validate([
            'external_reference' => ['required', 'string', 'regex:/^klassci-annonce-[1-9][0-9]*-courriel-[1-9][0-9]*$/D'],
            'state' => ['required', 'in:delivered,delivery_delayed,bounced,failed,complained,suppressed'],
        ]);
        preg_match('/^klassci-annonce-(\d+)-courriel-(\d+)$/', $valide['external_reference'], $matches);

        $ligne = DB::table('esbtp_annonce_email_deliveries')
            ->where('id', (int) ($matches[2] ?? 0))
            ->where('annonce_id', (int) ($matches[1] ?? 0))->first();
        if (! $ligne) {
            return response()->json(['ok' => true], 202);
        }

        $base = DB::table('esbtp_annonce_email_deliveries')->where('id', $ligne->id);
        // Duplicats et evenements en desordre : un delayed ne retrograde pas
        // delivered, et un failed reste terminal pour le meme message.
        if ($valide['state'] === 'delivered') {
            if ($ligne->status !== 'failed') {
                $base->update(['status' => 'delivered', 'provider_delivered_at' => now(), 'updated_at' => now()]);
            }
        } elseif ($valide['state'] === 'delivery_delayed') {
            if (! in_array($ligne->status, ['delivered', 'failed'], true)) {
                $base->update(['status' => 'delayed', 'provider_delayed_at' => now(), 'updated_at' => now()]);
            }
        } elseif ($ligne->status !== 'delivered') {
            $base->update([
                'status' => 'failed', 'error_code' => $valide['state'],
                'provider_failed_at' => now(), 'updated_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
