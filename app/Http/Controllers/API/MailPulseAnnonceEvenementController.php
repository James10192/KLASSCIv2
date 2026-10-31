<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Annonces\ReferenceCourrielAnnonce;
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
        $signature = (string) $request->header('X-MailPulse-Signature', '');
        $expected = hash_hmac('sha256', $stamp.'.'.$request->getContent(), $secret);
        $validSignature = false;
        foreach (explode(',', $signature) as $part) {
            $part = trim($part);
            if (str_starts_with($part, 'v1=')) {
                $part = substr($part, 3);
            }
            if (preg_match('/^[a-f0-9]{64}$/i', $part) && hash_equals($expected, strtolower($part))) {
                $validSignature = true;
            }
        }
        if ($secret === '' || ! ctype_digit((string) $stamp)
            || abs(time() - (int) $stamp) > 300 || ! $validSignature) {
            return response()->json(['message' => 'Signature invalide.'], 401);
        }

        if ($request->has('event_id') || $request->has('type')) {
            $valide = $this->standardEvent($request);
            if ($valide === null) {
                return response()->json(['ok' => true], 202);
            }
        } else {
            $valide = $request->validate([
                'external_reference' => ['required', 'string', 'max:191'],
                'message_id' => ['required', 'string', 'max:191'],
                'state' => ['required', 'in:delivered,delivery_delayed,bounced,failed,complained,suppressed'],
                'occurred_at' => ['nullable', 'date'],
            ]);
        }
        $reference = (string) $valide['external_reference'];
        $referenceLue = ReferenceCourrielAnnonce::extraire(
            $reference, trim((string) config('app.tenant_code', ''))
        );
        if ($referenceLue === null) {
            return response()->json(['ok' => true], 202);
        }

        // A signed event must also belong to the precise MailPulse message:
        // a stale callback from another delivery or attempt has no authority.
        $base = DB::table('esbtp_annonce_email_deliveries')
            ->where('id', $referenceLue['livraison_id'])
            ->where('annonce_id', $referenceLue['annonce_id'])
            ->where('mailpulse_message_id', $valide['message_id']);
        if ($referenceLue['v2']) {
            $base->where('mailpulse_request_id', $reference);
        } else {
            // An old callback must not be used to settle a newer v2 delivery,
            // even when numeric IDs coincide between two school instances.
            $base->where(function ($query): void {
                $query->whereNull('mailpulse_request_id')
                    ->orWhere('mailpulse_request_id', 'not like', 'klassci-v2-%');
            });
        }
        if (isset($valide['recipient_email'])) {
            $base->where('email', strtolower($valide['recipient_email']));
        }
        if (! (clone $base)->exists()) {
            // No hints about whether another instance/recipient has this ID.
            return response()->json(['ok' => true], 202);
        }

        $dateEvenement = isset($valide['occurred_at'])
            ? \Illuminate\Support\Carbon::parse($valide['occurred_at'])
                ->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s')
            : now();

        // Compare-and-set at the SQL layer: concurrent or replayed callbacks
        // cannot regress a delivered/failed message or overwrite its timestamp.
        $mutable = (clone $base)->whereIn('status', ['submitted', 'awaiting_dispatch', 'delayed']);
        if ($valide['state'] === 'delivered') {
            $mutable->update([
                'status' => 'delivered',
                'provider_delivered_at' => $dateEvenement,
                'error_code' => null,
                'updated_at' => now(),
            ]);
        } elseif ($valide['state'] === 'delivery_delayed') {
            $mutable->update([
                'status' => 'delayed',
                'provider_delayed_at' => $dateEvenement,
                'error_code' => null,
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

    /** Standard MailPulse v1 webhook; no outgoing email is ever dispatched here.
     * The per-instance HMAC must match the real MailPulse webhook secret.
     *
     * @return array<string,string>|null
     */
    private function standardEvent(Request $request): ?array
    {
        $data = $request->validate([
            'event_id' => ['required', 'string', 'max:191'],
            'tenant_id' => ['required', 'string', 'max:191'],
            'type' => ['required', 'in:message.delivered,message.delivery_delayed,message.failed,message.bounced,message.complained,message.suppressed'],
            'created_at' => ['required', 'date'],
            'data.occurred_at' => ['nullable', 'date'],
            'data.message.id' => ['required', 'string', 'max:191'],
            'data.message.channel' => ['required', 'in:email'],
            'data.message.status' => ['required', 'in:sent,delivered,read,failed'],
            'data.message.external_tenant_id' => ['required', 'string', 'max:191'],
            'data.message.recipient.type' => ['required', 'in:email'],
            'data.message.recipient.value' => ['required', 'email', 'max:254'],
            'data.message.metadata.source' => ['required', 'in:klassci'],
            'data.message.metadata.workflow_event' => ['required', 'in:announcement_email'],
            'data.message.metadata.external_reference' => ['required', 'string', 'max:191'],
        ]);
        $message = $data['data']['message'];
        $school = trim((string) config('app.tenant_code', ''));
        $organization = trim((string) config('services.mailpulse.external_organization_id', ''));
        if ($school === '' || $school === 'default' || ! hash_equals($school, $message['external_tenant_id'])
            || ($organization !== '' && ! hash_equals($organization, $data['tenant_id']))) {
            return null;
        }
        $states = [
            'message.delivered' => ['delivered', ['delivered', 'read']],
            'message.delivery_delayed' => ['delivery_delayed', ['sent']],
            'message.failed' => ['failed', ['failed']],
            'message.bounced' => ['bounced', ['failed']],
            'message.complained' => ['complained', ['failed']],
            'message.suppressed' => ['suppressed', ['failed']],
        ];
        [$state, $statuses] = $states[$data['type']];
        if (! in_array($message['status'], $statuses, true)) {
            return null; // The envelope contradicts the provider status.
        }
        return [
            'external_reference' => $message['metadata']['external_reference'],
            'message_id' => $message['id'],
            'state' => $state,
            'recipient_email' => $message['recipient']['value'],
            'occurred_at' => $data['data']['occurred_at'] ?? $data['created_at'],
        ];
    }

}
