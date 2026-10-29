<?php

namespace App\Jobs\Annonces;

use App\Models\ESBTPAnnonce;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Une seule action reprogramme toute la file, par tranches et de facon cadencée.
 * Ne cree pas de nouveaux destinataires et ne remet jamais une soumission
 * indeterminee, acceptee, retardee, rejetee ou deja delivree.
 */
class RelancerCampagneCourriels implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $annonceId, public int $apresId = 0, public int $programmes = 0)
    {
    }

    public static function cle(int $annonceId): string
    {
        return 'annonces:reprise:'.$annonceId;
    }

    public function handle(): void
    {
        $annonce = ESBTPAnnonce::find($this->annonceId);
        if (! $annonce || ! $annonce->email_notification_enabled || ! $annonce->isActive()) {
            Cache::forget(self::cle($this->annonceId));
            return;
        }

        $tranche = max(1, min(1000, (int) config('services.mailpulse.announcement_resume_max', 100)));
        $cadence = max(1, (int) config('services.mailpulse.mail_per_minute', 30));
        $lignes = DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $this->annonceId)
            ->where('id', '>', $this->apresId)
            ->whereIn('status', ['pending', 'retrying'])
            ->whereNull('submitted_at')
            ->whereNull('mailpulse_message_id')
            ->orderBy('id')
            ->limit($tranche)->pluck('id');

        $numero = $this->programmes;
        foreach ($lignes as $id) {
            EnvoyerAnnonceCourriel::dispatch((int) $id)
                ->delay(now()->addSeconds(intdiv($numero, $cadence) * 60));
            $numero++;
        }

        if ($lignes->count() === $tranche) {
            // Le curseur avance sur des IDs stables, pas sur des offsets
            // susceptibles de sauter des lignes modifiees entre deux batches.
            self::dispatch($this->annonceId, (int) $lignes->last(), $numero);
        } else {
            Cache::forget(self::cle($this->annonceId));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Cache::forget(self::cle($this->annonceId));
    }
}
