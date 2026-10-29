<?php

namespace App\Services\Annonces;

use App\Jobs\Annonces\EnvoyerAnnonceCourriel;
use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\DB;

/**
 * Relance uniquement les remises NON soumises.
 * Les e-mails delayed/submitted/awaiting_dispatch/delivered sont intouchables.
 * Ni fanout d'un nouveau public ni renvoi massif des campagnes historiques.
 */
final class RelancerCourrielsAnnonce
{
    public function reprendre(ESBTPAnnonce $annonce): array
    {
        if (! $annonce->is_published || ! $annonce->email_notification_enabled
            || ! $annonce->isActive()) {
            return ['enfile' => 0, 'raison' => 'Annonce non publiée, expirée ou envoi e-mail désactivé.'];
        }

        $limite = max(1, min(1000, (int) config('services.mailpulse.announcement_resume_max', 100)));
        $lignes = DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $annonce->id)
            ->whereIn('status', ['pending', 'retrying'])
            ->whereNull('submitted_at')
            ->whereNull('mailpulse_message_id')
            ->orderBy('id')->limit($limite)->pluck('id');
        $total = 0;
        $cadence = max(1, (int) config('services.mailpulse.mail_per_minute', 30));
        foreach ($lignes as $id) {
            // Les jobs en doublon ne soumettent pas deux fois : compare-and-set
            // status pending/retrying -> processing dans EnvoyerAnnonceCourriel.
            EnvoyerAnnonceCourriel::dispatch((int) $id)
                ->delay(now()->addSeconds(intdiv($total, $cadence) * 60));
            $total++;
        }

        return [
            'enfile' => $total,
            'raison' => $total === 0
                ? 'Aucun destinataire non soumis à reprendre. Les messages différés sont rapprochés, jamais renvoyés automatiquement.'
                : 'Reprise en file programmée pour les adresses non soumises, sous réserve du worker et des quotas.',
        ];
    }
}
