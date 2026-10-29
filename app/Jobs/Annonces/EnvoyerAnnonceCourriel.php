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

            $html = \App\Mail\Transport\CorpsPourMailPulse::resserrer(
                app(\App\Services\Annonces\ApercuCourrielAnnonce::class)->html($annonce)
            );
            $metadata = array_filter([
                'source' => 'klassci',
                'workflow_event' => 'announcement_email',
                'external_tenant_id' => (string) config('app.tenant_code', ''),
                'subject' => 'Annonce - '.$annonce->titre,
                'email_html' => $html,
                'sender_email' => $client->getSetting('mailpulse_sender_email', 'sender_email', ''),
                'sender_name' => $client->getSetting('mailpulse_sender_name', 'sender_name', 'KLASSCI'),
                'external_reference' => $reference,
            ], fn ($v) => $v !== '');

            // MailPulse limite le JSON metadata a 16 KiB. Ne pas remplacer
            // silencieusement l'HTML apercu par un e-mail brut different.
            if (\App\Mail\Transport\CorpsPourMailPulse::octetsJson($metadata) > 16384) {
                $this->statut('failed', ['error_code' => 'announcement_html_too_large',
                    'provider_failed_at' => now()]);
                return;
            }

            $resultat = $client->sendEmailMessage([
                'channel' => 'email',
                'recipient' => ['type' => 'email', 'value' => $ligne->email],
                'content' => ['type' => 'text', 'text' => $texte],
                'metadata' => $metadata,
            ], $reference);

            if (! $resultat->ok) {
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

            // pending_reconciliation n'est NI soumis avec certitude NI delivre.
            $etat = $resultat->isDispatchAccepted() ? 'submitted' : 'awaiting_dispatch';
            $this->statut($etat, [
                'mailpulse_message_id' => $resultat->id,
                'mailpulse_request_id' => $resultat->requestId,
                'dispatch_state' => $resultat->dispatchState,
                'submitted_at' => $resultat->isDispatchAccepted() ? now() : null,
            ]);
        } catch (Throwable $e) {
            $this->statut('retrying', ['error_code' => 'transport_exception']);
            throw $e; // la queue applique son backoff et garde la cle stable
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
                'status' => 'failed',
                'error_code' => 'queue_exhausted',
                'processing_at' => null,
                'provider_failed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
