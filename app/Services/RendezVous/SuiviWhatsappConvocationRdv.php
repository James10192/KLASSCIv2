<?php

namespace App\Services\RendezVous;

use App\Enums\StatutWhatsappRdv;
use App\Models\ESBTPRdvReservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ce que MailPulse rend compte d'une convocation WhatsApp : accord demande,
 * donne, refuse, expire, puis envoi, remise, lecture ou echec du document.
 *
 * Idempotent par construction. La reservation se retrouve par la cle
 * d'idempotence de SA tentative en cours : un evenement d'une tentative
 * anterieure ne touche plus rien. Et un evenement ne s'applique que s'il fait
 * avancer la ligne : un accuse rejoue, ou arrive dans le desordre, est sans effet.
 */
class SuiviWhatsappConvocationRdv
{
    public const APPLIQUE = 'applique';

    public const DEJA_VU = 'deja_vu';

    public const INCONNU = 'inconnu';

    /** @var array<string, StatutWhatsappRdv> */
    private const CIBLES = [
        'consent.requested' => StatutWhatsappRdv::AccordDemande,
        'consent.granted' => StatutWhatsappRdv::Accordee,
        'consent.refused' => StatutWhatsappRdv::Refusee,
        'consent.expired' => StatutWhatsappRdv::SansReponse,
        'message.sent' => StatutWhatsappRdv::Envoyee,
        'message.delivered' => StatutWhatsappRdv::Remise,
        'message.read' => StatutWhatsappRdv::Lue,
        'message.failed' => StatutWhatsappRdv::Echec,
    ];

    /** @return list<string> */
    public static function evenements(): array
    {
        return array_keys(self::CIBLES);
    }

    /**
     * @param  array{event: string, idempotencyKey: string, operationId?: ?string, occurredAt?: ?string, failureCode?: ?string}  $evenement
     * @return self::APPLIQUE|self::DEJA_VU|self::INCONNU
     */
    public function appliquer(array $evenement): string
    {
        $cible = self::CIBLES[$evenement['event']] ?? null;
        if ($cible === null) {
            return self::INCONNU;
        }

        return DB::transaction(function () use ($evenement, $cible) {
            $r = ESBTPRdvReservation::query()
                ->where('whatsapp_idempotency_key', $evenement['idempotencyKey'])
                ->lockForUpdate()
                ->first();
            if ($r === null) {
                return self::INCONNU;
            }

            // Un OUI apres un refus ou apres l'echeance : MailPulse a abandonne le
            // document, mais la famille accepte de nouveau. On ne l'envoie pas
            // d'office — on la rend de nouveau proposable, l'ecole renvoie.
            if ($cible === StatutWhatsappRdv::Accordee
                && in_array($r->whatsapp_statut, [StatutWhatsappRdv::Refusee, StatutWhatsappRdv::SansReponse], true)) {
                $r->forceFill([
                    'whatsapp_statut' => StatutWhatsappRdv::Echec,
                    'whatsapp_accord_at' => $this->instant($evenement['occurredAt'] ?? null),
                    'whatsapp_erreur' => 'La famille a accepté WhatsApp après coup : renvoyez la convocation.',
                ])->save();

                return self::APPLIQUE;
            }

            if (! $this->faitAvancer($r->whatsapp_statut, $cible)) {
                return self::DEJA_VU;
            }

            $quand = $this->instant($evenement['occurredAt'] ?? null);
            $r->forceFill([
                'whatsapp_statut' => $cible,
                'whatsapp_operation_id' => $evenement['operationId'] ?? $r->whatsapp_operation_id,
                'whatsapp_erreur' => $this->motif($cible, $evenement['failureCode'] ?? null),
                ...$this->horodatages($r, $cible, $quand),
            ])->save();

            Log::info('Convocation rdv WhatsApp : evenement applique', [
                'reservation_id' => $r->id,
                'event' => $evenement['event'],
            ]);

            return self::APPLIQUE;
        });
    }

    private function faitAvancer(?StatutWhatsappRdv $actuel, StatutWhatsappRdv $cible): bool
    {
        if ($actuel === null) {
            return true;
        }
        if ($actuel === $cible || ($actuel->aAtteintLaFamille() && $cible->estUnRetourALAppel())) {
            return false;
        }
        // Un etat final sans remise ne se leve que par une remise effective
        // (un accuse arrive apres un « echec » provisoire du transport).
        if ($actuel->estUnRetourALAppel()) {
            return $cible->aAtteintLaFamille();
        }

        return $cible->rang() > $actuel->rang();
    }

    /** @return array<string, Carbon> */
    private function horodatages(ESBTPRdvReservation $r, StatutWhatsappRdv $cible, Carbon $quand): array
    {
        return match ($cible) {
            StatutWhatsappRdv::AccordDemande => ['whatsapp_accord_demande_at' => $quand],
            StatutWhatsappRdv::Accordee => ['whatsapp_accord_at' => $quand],
            StatutWhatsappRdv::Envoyee => ['whatsapp_envoyee_at' => $quand, 'whatsapp_accord_at' => $r->whatsapp_accord_at ?? $quand],
            StatutWhatsappRdv::Remise, StatutWhatsappRdv::Lue => [
                'whatsapp_remise_at' => $r->whatsapp_remise_at ?? $quand,
                'whatsapp_envoyee_at' => $r->whatsapp_envoyee_at ?? $quand,
            ],
            default => [],
        };
    }

    private function motif(StatutWhatsappRdv $cible, ?string $code): ?string
    {
        return match ($cible) {
            StatutWhatsappRdv::Refusee => 'La famille a refusé WhatsApp (NON ou STOP).',
            StatutWhatsappRdv::SansReponse => 'Pas de réponse à la demande d\'accord sous 48 h.',
            StatutWhatsappRdv::Echec => mb_substr('WhatsApp n\'a pas pu remettre la convocation'.($code ? ' ('.$code.')' : '').'.', 0, 255),
            default => null,
        };
    }

    private function instant(?string $brut): Carbon
    {
        try {
            return $brut ? Carbon::parse($brut)->setTimezone(config('app.timezone')) : now();
        } catch (\Throwable) {
            return now();
        }
    }
}
