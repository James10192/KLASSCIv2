<?php

namespace App\Services\RendezVous\Rattrapage;

use App\Models\ESBTPRdvReservation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Models\Audit;

/**
 * Trace chaque convocation rattrapee dans le journal d'audit : quelle
 * reservation, quel identifiant MailPulse, d'ou il vient, et qui l'a lance.
 * La reservation n'est pas auditee d'elle-meme (ecriture conditionnelle par
 * requete) : on l'ecrit ici.
 */
class JournalRattrapage
{
    public function ligneEcrite(int $reservationId, MessageConvocation $message, ?Model $auteur): void
    {
        try {
            Audit::query()->create([
                'user_type' => $auteur?->getMorphClass(),
                'user_id' => $auteur?->getKey(),
                'event' => 'rattrapage_convocation',
                'auditable_type' => (new ESBTPRdvReservation)->getMorphClass(),
                'auditable_id' => $reservationId,
                'old_values' => ['convocation_message_id' => null],
                'new_values' => [
                    'convocation_message_id' => $message->messageId,
                    'convocation_envoyee_at' => $message->envoyeAt->toIso8601String(),
                    'source' => RattrapageConvocations::SOURCE,
                ],
                'url' => request()?->fullUrl(),
                'ip_address' => request()?->ip(),
                'user_agent' => mb_substr((string) request()?->userAgent(), 0, 1023),
                'tags' => 'cli,'.RattrapageConvocations::SOURCE,
            ]);
        } catch (\Throwable $e) {
            // L'ecriture est faite et reste relisible par l'identifiant : on
            // signale l'audit manquant sans annuler le rattrapage.
            Log::error('Rattrapage convocation : audit non ecrit', ['reservation_id' => $reservationId, 'erreur' => $e->getMessage()]);
        }
    }
}
