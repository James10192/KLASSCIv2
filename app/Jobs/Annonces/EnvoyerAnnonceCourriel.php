<?php

namespace App\Jobs\Annonces;

use App\Models\ESBTPAnnonce;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/** Un courriel par adresse via le transport MailPulse, avec registre anti-doublons. */
class EnvoyerAnnonceCourriel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public array $backoff = [60, 180, 600];

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(): void
    {
        $ligne = DB::table('esbtp_annonce_email_deliveries')->where('id', $this->deliveryId)->first();
        if (!$ligne || $ligne->submitted_at !== null) {
            return;
        }

        $annonce = ESBTPAnnonce::find($ligne->annonce_id);
        if (!$annonce || !$annonce->is_published || !$annonce->email_notification_enabled) {
            return;
        }

        // Si deux distributeurs ont planifié le même envoi, un seul le prend.
        $pris = DB::table('esbtp_annonce_email_deliveries')
            ->where('id', $this->deliveryId)->where('status', 'pending')->whereNull('submitted_at')
            ->update([
                'status' => 'processing',
                'processing_at' => now(),
                'attempts' => DB::raw('attempts + 1'),
                'updated_at' => now(),
            ]);
        if ($pris !== 1) {
            return;
        }

        try {
            $ecole = (string) Setting::get('school_name', config('app.name'));
            $contenu = trim(html_entity_decode(strip_tags((string) $annonce->contenu), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $message = $annonce->titre."\n\n".$contenu."\n\nConsultez l'annonce dans votre espace étudiant : "
                .route('esbtp.mes-annonces.index')
                ."\n\n".$ecole;

            // Aucun SMTP ni Resend : on utilise la cadence et les reports du transport MailPulse.
            // Les pièces jointes restent disponibles sur la fiche de l'annonce, pas dans le mail.
            Mail::mailer('mailpulse')->raw($message, fn ($mail) => $mail
                ->to($ligne->email)->subject('Annonce - '.$annonce->titre));

            DB::table('esbtp_annonce_email_deliveries')->where('id', $this->deliveryId)->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'processing_at' => null,
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            DB::table('esbtp_annonce_email_deliveries')->where('id', $this->deliveryId)->update([
                'status' => 'pending', 'processing_at' => null, 'updated_at' => now(),
            ]);
            throw $e;
        }
    }
}
