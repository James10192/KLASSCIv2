<?php

namespace App\Jobs\Annonces;

use App\Mail\Transport\CadenceMailPulse;
use App\Models\ESBTPAnnonce;
use App\Models\Setting;
use App\Services\Annonces\CourrielAnnonce;
use App\Services\MailPulse\MailPulseClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/** A new provider submission can only originate from an immutable attempt. */
final class EnvoyerTentativeAnnonceCourriel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 12;
    public array $backoff = [60, 180, 600];

    public function __construct(public int $tentativeId) {}

    public function handle(MailPulseClient $client, CadenceMailPulse $cadence): void
    {
        if (config('queue.default') === 'sync') return;
        $attente = $cadence->attenteAvantEnvoi();
        if ($attente !== null) {
            $this->release(max(60, $attente));
            return;
        }

        // One irreversible boundary. An interrupted worker can never infer
        // that a prior HTTP request was not sent.
        $capture = DB::transaction(function (): ?array {
            $essai = DB::table('esbtp_annonce_email_attempts')
                ->where('id', $this->tentativeId)->lockForUpdate()->first();
            if (! $essai || $essai->status !== 'queued' || (int) $essai->attempt_number < 2) return null;
            $ligne = DB::table('esbtp_annonce_email_deliveries')
                ->where('id', $essai->delivery_id)->where('annonce_id', $essai->annonce_id)
                ->lockForUpdate()->first();
            if (! $ligne || $ligne->status !== 'failed'
                || $ligne->email !== $essai->recipient_email
                || $ligne->provider_delivered_at !== null) return null;
            $annonce = ESBTPAnnonce::find($essai->annonce_id);
            if (! $annonce || ! $annonce->is_published
                || ! $annonce->email_notification_enabled || $annonce->isExpired()) return null;

            $previous = $essai->previous_reference;
            $last = DB::table('esbtp_annonce_email_attempts')
                ->where('delivery_id', $essai->delivery_id)
                ->where('attempt_number', (int) $essai->attempt_number - 1)->first();
            if (! $last || $last->external_reference !== $previous
                || ! in_array($last->error_code, ['template_not_configured', 'channel_not_configured'], true)
                || $last->status !== 'failed') return null;

            DB::table('esbtp_annonce_email_attempts')->where('id', $essai->id)
                ->where('status', 'queued')->update([
                    'status' => 'awaiting_dispatch',
                    'dispatch_started_at' => now(), 'updated_at' => now(),
                ]);
            DB::table('esbtp_annonce_email_deliveries')->where('id', $ligne->id)
                ->where('status', 'failed')->update([
                    'status' => 'awaiting_dispatch',
                    'error_code' => 'submission_outcome_unknown',
                    'mailpulse_request_id' => $essai->external_reference,
                    'mailpulse_message_id' => null,
                    'submitted_at' => null, 'updated_at' => now(),
                ]);
            DB::table('esbtp_annonce_email_attempt_events')->insert([
                'attempt_id' => $essai->id, 'event' => 'dispatch_boundary',
                'created_at' => now(),
            ]);
            return ['essai' => $essai, 'annonce' => $annonce];
        }, 3);

        if (! $capture) return;
        $essai = $capture['essai'];
        $annonce = $capture['annonce'];
        try {
            $ecole = (string) Setting::get('school_name', config('app.name'));
            $contenu = trim(html_entity_decode(strip_tags((string) $annonce->contenu), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $texte = $annonce->titre."\n\n".$contenu
                ."\n\nConsultez l'annonce dans votre espace étudiant : "
                .route('esbtp.mes-annonces.index')."\n\n".$ecole;
            $metadata = array_filter([
                'source' => 'klassci',
                'workflow_event' => 'announcement_email',
                'external_tenant_id' => (string) config('app.tenant_code', ''),
                'subject' => 'Annonce - '.$annonce->titre,
                'sender_email' => $client->getSetting('mailpulse_sender_email', 'sender_email', ''),
                'sender_name' => $client->getSetting('mailpulse_sender_name', 'sender_name', 'KLASSCI'),
                'external_reference' => $essai->external_reference,
                'retry_of' => $essai->previous_reference,
            ], fn ($v) => $v !== '');
            $html = app(CourrielAnnonce::class)->htmlPourMailPulse($annonce, $metadata);
            if ($html !== null) $metadata['email_html'] = $html;

            $resultat = $client->sendEmailMessage([
                'channel' => 'email',
                'recipient' => ['type' => 'email', 'value' => $essai->recipient_email],
                'content' => ['type' => 'text', 'text' => $texte],
                'metadata' => $metadata,
            ], $essai->external_reference);

            $accepte = $resultat->isDispatchAccepted()
                && is_string($resultat->id) && trim($resultat->id) !== '';
            // Only an explicit 429 before a claim can be safely released to
            // the same attempt. 5xx/timeout remain unknown, never auto-retry.
            $reessayer = ! $resultat->ok && $resultat->status === 'rate_limited';

            $etat = $accepte ? 'submitted'
                : ($reessayer ? 'queued'
                    : ((! $resultat->ok && ! in_array($resultat->status, [
                        'request_timeout', 'connection_failed', 'provider_unavailable',
                        'invalid_dispatch_contract',
                    ], true)) ? 'failed' : 'awaiting_dispatch'));

            $this->terminer($essai, $etat, $resultat->id,
                $accepte ? null : ($resultat->errorCode ?: $resultat->status));

            if ($reessayer) $this->release(max(60, $resultat->retryAfter ?? 60));
        } catch (Throwable $exception) {
            // It might have crossed the provider boundary. Preserve the same
            // attempt and require GET reconciliation, never replay this job.
            $this->terminer($essai, 'awaiting_dispatch', null, 'submission_outcome_unknown');
            Log::warning('Résultat incertain de relance annonce', ['attempt_id' => $this->tentativeId]);
        }
    }

    private function terminer(object $essai, string $etat, ?string $messageId, ?string $erreur): void
    {
        DB::transaction(function () use ($essai, $etat, $messageId, $erreur): void {
            $count = DB::table('esbtp_annonce_email_attempts')->where('id', $essai->id)
                ->where('status', 'awaiting_dispatch')->update([
                    'status' => $etat,
                    'provider_message_id' => $messageId,
                    'error_code' => $erreur ? mb_substr($erreur, 0, 64) : null,
                    'submitted_at' => $etat === 'submitted' ? now() : null,
                    'failed_at' => $etat === 'failed' ? now() : null,
                    'updated_at' => now(),
                ]);
            if ($count !== 1) return;
            DB::table('esbtp_annonce_email_deliveries')
                ->where('id', $essai->delivery_id)
                ->where('mailpulse_request_id', $essai->external_reference)
                ->where('status', 'awaiting_dispatch')
                ->update([
                    'status' => $etat === 'queued' ? 'failed' : $etat,
                    'mailpulse_message_id' => $messageId,
                    'submitted_at' => $etat === 'submitted' ? now() : null,
                    'error_code' => $erreur ? mb_substr($erreur, 0, 64) : null,
                    'updated_at' => now(),
                ]);
            DB::table('esbtp_annonce_email_attempt_events')->insert([
                'attempt_id' => $essai->id,
                'event' => $etat,
                'detail_code' => $erreur ? mb_substr($erreur, 0, 64) : null,
                'created_at' => now(),
            ]);
        });
    }

    public function failed(Throwable $exception): void
    {
        DB::table('esbtp_annonce_email_attempts')
            ->where('id', $this->tentativeId)
            ->whereIn('status', ['queued', 'awaiting_dispatch'])
            ->update(['status' => 'awaiting_dispatch',
                'error_code' => 'queue_outcome_unknown', 'updated_at' => now()]);
    }
}
