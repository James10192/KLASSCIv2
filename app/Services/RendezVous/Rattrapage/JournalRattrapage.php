<?php

namespace App\Services\RendezVous\Rattrapage;

use App\Models\ESBTPRdvReservation;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Models\Audit;

/**
 * Trace chaque convocation rattrapee dans le journal d'audit : quelle
 * reservation, les colonnes changees (avant, apres), la source, et qui l'a
 * lance. La reservation n'est pas auditee d'elle-meme (ecriture
 * conditionnelle par requete) : on l'ecrit ici.
 *
 * Leve en cas d'echec : l'appelant annule alors l'ecriture de la ligne.
 */
class JournalRattrapage
{
    public function ligneEcrite(int $reservationId, MessageConvocation $message, ?Model $auteur): void
    {
        Audit::query()->create([
            'user_type' => $auteur?->getMorphClass(),
            'user_id' => $auteur?->getKey(),
            'event' => 'rattrapage_convocation',
            'auditable_type' => (new ESBTPRdvReservation)->getMorphClass(),
            'auditable_id' => $reservationId,
            // Eligibilite et ecriture l'exigent : les deux colonnes etaient vides.
            'old_values' => ['convocation_message_id' => null, 'convocation_envoyee_at' => null],
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
    }
}
