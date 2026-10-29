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
            'message_id' => ['required', 'string', 'max:191'],
            'state' => ['required', 'in:delivered,delivery_delayed,bounced,failed,complained,suppressed'],
            'occurred_at' => ['nullable', 'date'],
        ]);
        preg_match('/^klassci-annonce-(\\d+)-courriel-(\\d+)$/', $valide['external_reference'], $matches);

        // A signed event must also belong to the precise MailPulse message:
        // a stale callback from another delivery or attempt has no authority.
        $base = DB::table('esbtp_annonce_email_deliveries')
            ->where('id', (int) ($matches[2] ?? 0))
            ->where('annonce_id', (int) ($matches[1] ?? 0))
            ->where('mailpulse_message_id', $valide['message_id']);
        if (! (clone $base)->exists()) {
            // No hints about whether another instance/recipient has this ID.
            return response()->json(['ok' => true], 202);
        }

        $dateEvenement = isset($valide['occurred_at'])
            ? \\Illuminate\\Support\\Carbon::parse($valide['occurred_at'])
                ->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s')
            : now();

        // Compare-and-set at the SQL layer: concurrent or replayed callbacks
        // cannot regress a delivered/failed message or overwrite its timestamp.
        $mutable = (clone $base)->whereIn('status', ['submitted', 'awaiting_dispatch', 'delayed']);
        if ($valide['state'] === 'delivered') {
            $mutable->update([
                'status' => 'delivered',
                'provider_delivered_at' => $dateEvenement,
                'updated_at' => now(),
            ]);
        } elseif ($valide['state'] === 'delivery_delayed') {
            $mutable->update([
                'status' => 'delayed',
                'provider_delayed_at' => $dateEvenement,
                'updated_at' => now(),
            ]);
        } else {
            $mutable->update([
                'status' => 'failed',
                'error_code' => $valide['state'],
                'provider_failed_at' => $dateEvenement,
                'updated_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
