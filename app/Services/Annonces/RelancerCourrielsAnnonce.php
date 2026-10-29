<?php

namespace App\Services\Annonces;

use App\Jobs\Annonces\RelancerCampagneCourriels;
use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Controle d'une relance explicite sans reexpedition des anciens courriels.
 */
final class RelancerCourrielsAnnonce
{
    public function reprendre(ESBTPAnnonce $annonce): array
    {
        if (! $annonce->is_published || ! $annonce->email_notification_enabled || ! $annonce->isActive()) {
            return ['enfile' => 0, 'raison' => 'Annonce non publiée, expirée ou envoi e-mail désactivé.'];
        }

        $eligibles = DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $annonce->id)
            ->whereIn('status', ['pending', 'retrying'])
            ->whereNull('submitted_at')
            ->whereNull('mailpulse_message_id')
            ->count();

        if ($eligibles === 0) {
            return ['enfile' => 0,
                'raison' => 'Aucun courriel non soumis. Les messages livrés, soumis ou retardés ne sont jamais réexpédiés.'];
        }

        $cle = RelancerCampagneCourriels::cle($annonce->id);
        if (! Cache::add($cle, true, now()->addMinutes(5))) {
            return ['enfile' => 0,
                'raison' => 'Une reprise est déjà programmée. Consultez les états avant toute nouvelle tentative.'];
        }

        try {
            RelancerCampagneCourriels::dispatch($annonce->id);
        } catch (\Throwable $exception) {
            Cache::forget($cle);
            throw $exception;
        }

        return ['enfile' => $eligibles,
            'raison' => "Reprise de {$eligibles} adresse(s) non soumise(s) programmée par tranches. Vérifiez le worker et les quotas MailPulse."];
    }
}
