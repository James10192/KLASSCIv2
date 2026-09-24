<?php

namespace App\Services\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\MailPulse\RefusMailPulse;
use App\Services\MailPulse\MailPulseStatutsMessages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Relit chez MailPulse ce qu'est devenue chaque convocation « envoyee ».
 *
 * « Envoyee » ne voulait dire que « MailPulse a accepte » : sur esbtp-abidjan,
 * 78 convocations comptees envoyees avaient rebondi, et aucune famille
 * n'etait dans la liste d'appel. Desormais :
 * - rebond, suppression, plainte, echec → la reservation passe en « echec »
 *   avec le motif, et la famille entre dans « Familles a prevenir » ;
 * - remise confirmee → la date de delivrance est notee ;
 * - encore en transit → seule la date de synchronisation bouge.
 *
 * Fenetre : les 30 derniers jours. Au-dela, le creneau est passe.
 */
class SynchroStatutsConvocations
{
    public const FENETRE_JOURS = 30;

    /** Motif d'arret quand le budget de temps est epuise : le reste attend le passage suivant. */
    public const BUDGET_EPUISE = 'budget_temps_epuise';

    /** Statuts MailPulse : remis a la famille. */
    private const DELIVRES = ['delivered', 'read'];

    /** Statuts MailPulse : definitivement non remis. Le detail (rebond, plainte) est dans error_code. */
    private const ECHOUES = ['failed', 'cancelled', 'template_required'];

    /**
     * Statuts MailPulse definitifs qui ne disent rien de la remise : le message
     * a ete fusionne avec un autre. Notes, et plus jamais relus.
     */
    public const NEUTRES_DEFINITIFS = ['reconciled', 'duplicate_confirmed'];

    public function __construct(private readonly MailPulseStatutsMessages $statuts) {}

    /**
     * Ce que la synchronisation sait relire : envoyee, avec un identifiant
     * MailPulse, pas encore remise, sans verdict neutre definitif, envoyee dans
     * la fenetre. Partage avec le diagnostic (ConvocationsNonSynchronisees),
     * qui ne peut donc pas promettre une relecture qui n'aura jamais lieu.
     *
     * @param  Builder<ESBTPRdvReservation>  $reservations
     * @return Builder<ESBTPRdvReservation>
     */
    public static function selectionnables(Builder $reservations): Builder
    {
        return $reservations
            ->where('convocation_statut', StatutConvocationRdv::Envoyee->value)
            ->whereNotNull('convocation_message_id')
            ->whereNull('convocation_delivree_at')
            ->where(fn ($q) => $q->whereNull('convocation_code_distant')->orWhereNotIn('convocation_code_distant', self::NEUTRES_DEFINITIFS))
            ->where('convocation_envoyee_at', '>=', now()->subDays(self::FENETRE_JOURS));
    }

    /**
     * `rebonds` et `supprimees` detaillent `echecs` d'apres le code distant.
     *
     * Chaque lecture est un appel reseau : au-dela de `$budgetSecondes`, le lot
     * s'arrete (`bloque` = BUDGET_EPUISE) pour rester sous le max_execution_time
     * d'une requete HTTP. Les convocations non relues passent en tete au suivant.
     *
     * @return array{lues: int, delivrees: int, echecs: int, rebonds: int, supprimees: int, en_transit: int, erreurs: int, bloque: ?string}
     */
    public function synchroniser(int $maximum = 100, float $budgetSecondes = 20.0): array
    {
        $debut = microtime(true);
        $rapport = ['lues' => 0, 'delivrees' => 0, 'echecs' => 0, 'rebonds' => 0, 'supprimees' => 0, 'en_transit' => 0, 'erreurs' => 0, 'bloque' => null];

        $reservations = self::selectionnables(ESBTPRdvReservation::query())
            ->orderByRaw('convocation_synchro_at IS NOT NULL, convocation_synchro_at')
            ->limit($maximum)
            ->get();

        foreach ($reservations as $reservation) {
            if (microtime(true) - $debut >= $budgetSecondes) {
                $rapport['bloque'] = self::BUDGET_EPUISE;
                break;
            }
            $etat = $this->statuts->lire((string) $reservation->convocation_message_id);

            if (is_string($etat)) {
                // Configuration ou service indisponible : les lectures suivantes
                // echoueraient pareil. On arrete le lot sans rien dater.
                if (RefusMailPulse::bloquant($etat)) {
                    $rapport['bloque'] = $etat;
                    break;
                }
                $rapport['erreurs']++;
                Log::warning('Synchro convocation rdv : lecture impossible', ['reservation_id' => $reservation->id, 'code' => $etat]);
                // Datee quand meme : sinon un message illisible (404) resterait en
                // tete de file a chaque passage et bloquerait tous les autres.
                $this->ecrire($reservation, ['convocation_synchro_at' => now(), 'convocation_code_distant' => mb_substr($etat, 0, 60)]);

                continue;
            }

            $rapport['lues']++;
            $issue = $this->appliquer($reservation, $etat);
            $rapport[$issue]++;
            if ($issue === 'echecs') {
                match (MotifsRemiseConvocation::famille($etat['error_code'] ?: $etat['status'])) {
                    'rebond' => $rapport['rebonds']++,
                    'suppression' => $rapport['supprimees']++,
                    default => null,
                };
            }
        }

        return $rapport;
    }

    /**
     * @param  array{status: string, error_code: ?string, delivered_at: ?string}  $etat
     * @return 'delivrees'|'echecs'|'en_transit'
     */
    private function appliquer(ESBTPRdvReservation $reservation, array $etat): string
    {
        // Un statut neutre definitif est note tel quel : c'est lui qui l'exclut des relectures.
        $code = in_array($etat['status'], self::NEUTRES_DEFINITIFS, true) ? $etat['status'] : ($etat['error_code'] ?: $etat['status']);
        $champs = ['convocation_synchro_at' => now(), 'convocation_code_distant' => mb_substr($code, 0, 60)];

        if (in_array($etat['status'], self::DELIVRES, true)) {
            $this->ecrire($reservation, $champs + ['convocation_delivree_at' => $this->date($etat['delivered_at'])]);

            return 'delivrees';
        }

        if (in_array($etat['status'], self::ECHOUES, true)) {
            $this->ecrire($reservation, $champs + [
                'convocation_statut' => StatutConvocationRdv::Echec->value,
                'convocation_erreur' => mb_substr(MotifsRemiseConvocation::lisible($code), 0, 255),
            ]);
            Log::info('Convocation rdv non remise', ['reservation_id' => $reservation->id, 'code' => $code]);

            return 'echecs';
        }

        $this->ecrire($reservation, $champs);

        return 'en_transit';
    }

    /**
     * Ecriture CONDITIONNELLE : la lecture chez MailPulse peut prendre des
     * secondes, pendant lesquelles la convocation a pu etre replanifiee (autre
     * message, autre etat). On n'ecrit que si c'est toujours celle qu'on a lue.
     *
     * @param  array<string, mixed>  $champs
     */
    private function ecrire(ESBTPRdvReservation $reservation, array $champs): void
    {
        ESBTPRdvReservation::query()
            ->whereKey($reservation->id)
            ->where('convocation_message_id', $reservation->convocation_message_id)
            ->where('convocation_statut', StatutConvocationRdv::Envoyee->value)
            ->update($champs + ['updated_at' => now()]);
    }

    private function date(?string $iso): \Illuminate\Support\Carbon
    {
        try {
            return $iso ? \Illuminate\Support\Carbon::parse($iso) : now();
        } catch (\Throwable) {
            return now();
        }
    }
}
