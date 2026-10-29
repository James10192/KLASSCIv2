<?php

namespace App\Jobs\Annonces;

use App\Models\ESBTPAnnonce;
use App\Services\Annonces\CiblageCourrielsAnnonce;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Initialise le registre unique de destinataires.
 * Reexecuter le fanout ne reinitialise JAMAIS une livraison deja soumise.
 */
class DiffuserAnnonceParCourriel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $annonceId)
    {
    }

    public function handle(CiblageCourrielsAnnonce $ciblage): void
    {
        $annonce = ESBTPAnnonce::find($this->annonceId);
        if (! $annonce || ! $annonce->is_published || ! $annonce->email_notification_enabled
            || $annonce->date_publication->isFuture() || $annonce->isExpired()) {
            return;
        }

        $maintenant = now();
        $ciblage->etudiants($annonce)->chunkById(200, function ($lot) use ($annonce, $maintenant): void {
            $lignes = [];
            foreach ($lot as $etudiant) {
                $email = CiblageCourrielsAnnonce::adresse($etudiant);
                if ($email !== null) {
                    $lignes[] = [
                        'annonce_id' => $annonce->id,
                        'email' => $email,
                        'status' => 'pending',
                        'created_at' => $maintenant,
                        'updated_at' => $maintenant,
                    ];
                }
            }
            if ($lignes) {
                DB::table('esbtp_annonce_email_deliveries')->insertOrIgnore($lignes);
            }
        });

        // Echelonner une campagne au lieu de saturer Resend en quelques secondes.
        // Le garde MailPulse reste actif contre les autres flux du tenant.
        $cadence = max(1, (int) config('services.mailpulse.mail_per_minute', 30));
        $numero = 0;
        DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $annonce->id)->where('status', 'pending')
            ->orderBy('id')->chunkById(200, function ($lot) use ($cadence, &$numero): void {
                foreach ($lot as $livraison) {
                    $delai = intdiv($numero, $cadence) * 60;
                    EnvoyerAnnonceCourriel::dispatch((int) $livraison->id)->delay(now()->addSeconds($delai));
                    $numero++;
                }
            });
    }
}
