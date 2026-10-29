<?php

namespace App\Jobs\Annonces;

use App\Mail\Transport\CadenceMailPulse;
use App\Models\ESBTPAnnonce;
use App\Models\Setting;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\RefusMailPulse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Un message, une adresse et une cle d'idempotence stable SANS fenetre horaire.
 *
 * La soumission MailPulse ne prouve jamais la livraison dans la boite du destinataire.
 * Un refus passager est relache dans la MEME queue, sans outbox parallele.
 */
class EnvoyerAnnonceCourriel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 12;
    public array $backoff = [60, 180, 600];

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(MailPulseClient $client, CadenceMailPulse $cadence): void
    {
        $ligne = DB::table('esbtp_annonce_email_deliveries')->where('id', $this->deliveryId)->first();
        if (! $ligne || $ligne->submitted_at !== null || in_array($ligne->status, ['submitted', 'awaiting_dispatch', 'failed'], true)) {
            return;
        }

        $annonce = ESBTPAnnonce::find($ligne->annonce_id);
        if (! $annonce || ! $annonce->is_published || ! $annonce->email_notification_enabled
            || $annonce->date_publication->isFuture() || $annonce->isExpired()) {
            return;
        }

        // Un seul worker prend la ligne; la prochaine relance est permise depuis retrying.
        $pris = DB::table('esbtp_annonce_email_deliveries')
            ->where('id', $this->deliveryId)
            ->whereIn('status', ['pending', 'retrying'])
            ->whereNull('submitted_at')
            ->update([
                'status' => 'processing', 'processing_at' => now(),
                'attempts' => DB::raw('attempts + 1'), 'updated_at' => now(),
            ]);
        if ($pris !== 1) {
            return;
        }

        $reference = 'klassci-annonce-'.$annonce->id.'-courriel-'.$ligne->id;
        // After crossing the HTTP boundary, a lost response cannot prove non-delivery.
        $soumissionCommencee = false;
        try {
            $attente = $cadence->attenteAvantEnvoi();
            if ($attente !== null) {
                $this->reporter($attente, 'local_rate_limit');
                return;
            }

            $ecole = (string) Setting::get('school_name', config('app.name'));
            $contenu = trim(html_entity_decode(strip_tags((string) $annonce->contenu), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $texte = $annonce->titre."\n\n".$contenu."\n\nConsultez l'annonce dans votre espace étudiant : "
                .route('esbtp.mes-annonces.index')."\n\n".$ecole;

            $metadata = array_filter([
                'source' => 'klassci',
                'workflow_event' => 'announcement_email',
                'external_tenant_id' => (string) config('app.tenant_code', ''),
                'subject' => 'Annonce - '.$annonce->titre,
                'sender_email' => $client->getSetting('mailpulse_sender_email', 'sender_email', ''),
                'sender_name' => $client->getSetting('mailpulse_sender_name', 'sender_name', 'KLASSCI'),
                'external_reference' => $reference,
            ], fn ($v) => $v !== '');

            // Même HTML que l'aperçu KLASSCI ; le texte reste un fallback.
            $html = app(\App\Services\Annonces\CourrielAnnonce::class)
                ->htmlPourMailPulse($annonce, $metadata);
            if ($html !== null) {
                $metadata['email_html'] = $html;
            }

            $soumissionCommencee = true;
            $resultat = $client->sendEmailMessage([
                'channel' => 'email',
                'recipient' => ['type' => 'email', 'value' => $ligne->email],
                'content' => ['type' => 'text', 'text' => $texte],
                'metadata' => $metadata,
            ], $reference);

            if (! $resultat->ok) {
                // Timeout/5xx or an unusable successful response may have accepted
                // the message. Do not blindly retry; reconcile the stable key first.
                if (in_array($resultat->status, ['connection_failed', 'request_timeout', 'provider_unavailable', 'invalid_dispatch_contract'], true)) {
                    $this->statut('awaiting_dispatch', [
                        'error_code' => 'submission_outcome_unknown',
                        'mailpulse_request_id' => $resultat->requestId ?? $reference,
                        'mailpulse_message_id' => $resultat->id,
                    ]);
                    Log::warning('Annonce : soumission MailPulse indeterminee, relance bloquee', [
                        'annonce_id' => $annonce->id, 'livraison_id' => $ligne->id,
                        'statut' => $resultat->status,
                    ]);
                    return;
                }
                if (in_array($resultat->status, RefusMailPulse::PASSAGERS, true)) {
                    $this->reporter($resultat->retryAfter ?? 60, $resultat->status);
                    return;
                }

                $this->statut('failed', [
                    'error_code' => substr((string) ($resultat->errorCode ?: $resultat->status), 0, 64),
                    'provider_failed_at' => now(),
                ]);
                Log::warning('Annonce : MailPulse a refuse un courriel', [
                    'annonce_id' => $annonce->id, 'livraison_id' => $ligne->id,
                    'statut' => $resultat->status, 'http' => $resultat->httpStatus,
                ]);
                return;
            }

            // Even an accepted response needs a durable message ID for tracking.
            $accepteAvecId = $resultat->isDispatchAccepted() && is_string($resultat->id)
                && trim($resultat->id) !== '';
            $etat = $accepteAvecId ? 'submitted' : 'awaiting_dispatch';
            $this->statut($etat, [
                'error_code' => $accepteAvecId ? null : 'submission_outcome_unknown',
                'mailpulse_message_id' => $resultat->id,
                'mailpulse_request_id' => $resultat->requestId,
                'dispatch_state' => $resultat->dispatchState,
                'submitted_at' => $accepteAvecId ? now() : null,
            ]);
        } catch (Throwable $e) {
            if ($soumissionCommencee) {
                // Includes unexpected HTTP-client exceptions and failures while
                // recording a successful response: do not send again on next job.
                $this->statut('awaiting_dispatch', [
                    'error_code' => 'submission_outcome_unknown',
                    'mailpulse_request_id' => $reference,
                ]);
                Log::warning('Annonce : resultat HTTP indetermine, reemission interdite', [
                    'livraison_id' => $this->deliveryId,
                ]);
                return;
            }
            $this->statut('retrying', ['error_code' => 'pre_submission_exception']);
            throw $e; // no HTTP submission has started; queue backoff applies
        }
    }

    private function reporter(int $attente, string $code): void
    {
        $this->statut('retrying', ['error_code' => $code]);
        $this->release(max(60, $attente));
    }

    private function statut(string $statut, array $attributs = []): void
    {
        DB::table('esbtp_annonce_email_deliveries')->where('id', $this->deliveryId)
            ->update($attributs + [
                'status' => $statut,
                'processing_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function failed(Throwable $exception): void
    {
        DB::table('esbtp_annonce_email_deliveries')->where('id', $this->deliveryId)
            ->whereIn('status', ['processing', 'retrying'])
            ->update([
                // A local timeout / exhausted worker cannot prove that the
                // upstream provider did not accept the email. Quarantine it
                // until reconciliation, never offer it for blind resending.
                'status' => 'awaiting_dispatch',
                'error_code' => 'queue_outcome_unknown',
                'processing_at' => null,
                'updated_at' => now(),
            ]);
    }
}
