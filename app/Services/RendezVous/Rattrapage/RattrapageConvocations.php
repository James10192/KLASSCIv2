<?php

namespace App\Services\RendezVous\Rattrapage;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\Portail\ReferencePublique;
use App\Services\RendezVous\PerimetreRdv;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rattache aux convocations d'avant le suivi (22/09) l'identifiant et la date
 * du courriel MailPulse qui les a portees, pour que la synchronisation puisse
 * enfin relire leur remise.
 *
 * Eligibles : reservations « envoyee » sans identifiant, dans PerimetreRdv.
 * Un courriel deja rattache a une reservation n'est jamais reutilise ; un
 * courriel que deux reservations choisiraient est ambigu pour les deux.
 *
 * Simulation par defaut. En execution, chaque ecriture est conditionnelle
 * (identifiant toujours vide) : relancer ne change rien et n'ecrase jamais une
 * valeur. Chaque ligne ecrite est tracee dans le journal d'audit.
 */
class RattrapageConvocations
{
    public const SOURCE = 'rattrapage_mailpulse';

    private const EXEMPLES_MAX = 20;

    public function __construct(
        private readonly PerimetreRdv $perimetre,
        private readonly ApparieurConvocation $apparieur,
        private readonly ReferencePublique $references,
        private readonly JournalRattrapage $journal,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $bruts
     * @return array{execute: bool, eligibles: int, appariees: int, ambigues: int, sans_message: int, deja_renseignees: int, ecrites: int, exemples: list<array{reference_masquee: string, motif: string}>}
     */
    public function traiter(array $bruts, bool $executer, ?Model $auteur = null): array
    {
        $messages = collect($bruts)->map(fn (array $m) => MessageConvocation::depuis($m, $this->references));
        $dejaRattaches = $this->dejaRattaches($messages);
        $parReference = $messages
            ->reject(fn (MessageConvocation $m) => isset($dejaRattaches[$m->messageId]))
            ->groupBy(fn (MessageConvocation $m) => $m->reference);

        $decisions = $this->decider($parReference);
        $rapport = [
            'execute' => $executer,
            'eligibles' => count($decisions),
            'appariees' => 0, 'ambigues' => 0, 'sans_message' => 0,
            'deja_renseignees' => count($dejaRattaches),
            'ecrites' => 0,
            'exemples' => [],
        ];

        foreach ($decisions as $decision) {
            $rapport[match ($decision['motif']) {
                ApparieurConvocation::APPARIEE => 'appariees',
                ApparieurConvocation::AMBIGUE => 'ambigues',
                default => 'sans_message',
            }]++;
            if ($decision['motif'] !== ApparieurConvocation::APPARIEE && count($rapport['exemples']) < self::EXEMPLES_MAX) {
                $rapport['exemples'][] = ['reference_masquee' => $this->masquer($decision['reference']), 'motif' => $decision['motif']];
            }
            if ($executer && $decision['message'] !== null && $this->ecrire($decision['reservation'], $decision['message'], $auteur)) {
                $rapport['ecrites']++;
            }
        }

        return $rapport;
    }

    /**
     * @param  Collection<string, Collection<int, MessageConvocation>>  $parReference
     * @return list<array{reservation: int, reference: string, motif: string, message: ?MessageConvocation}>
     */
    private function decider(Collection $parReference): array
    {
        $decisions = [];
        $this->perimetre->reservations()
            ->select(['id', 'creneau_id', 'candidature_id', 'reinscription_demande_id', 'email', 'convocation_action'])
            ->with(['candidature:id,reference_publique,rdv_invite_at', 'demande:id,reference_publique,rdv_invite_at'])
            ->where('convocation_statut', StatutConvocationRdv::Envoyee->value)
            ->whereNull('convocation_message_id')
            ->chunkById(500, function (Collection $lot) use (&$decisions, $parReference) {
                foreach ($lot as $reservation) {
                    $decisions[] = $this->deciderPour($reservation, $parReference);
                }
            });

        return $this->exclureLesPartages($decisions);
    }

    /** @param  Collection<string, Collection<int, MessageConvocation>>  $parReference */
    private function deciderPour(ESBTPRdvReservation $reservation, Collection $parReference): array
    {
        $porteur = $reservation->candidature ?? $reservation->demande;
        $reference = $this->references->normaliser((string) $porteur?->reference_publique);
        [$motif, $message] = $reference === ''
            ? [ApparieurConvocation::SANS_MESSAGE, null]
            : $this->apparieur->choisir(
                $parReference->get($reference, collect())->all(),
                $reservation->convocation_action ?: 'confirme',
                $reservation->email,
                $porteur?->rdv_invite_at,
            );

        return ['reservation' => (int) $reservation->id, 'reference' => $reference, 'motif' => $motif, 'message' => $message];
    }

    /**
     * Un courriel ne convoque qu'une reservation : choisi par deux, il est
     * ambigu pour les deux.
     *
     * @param  list<array{reservation: int, reference: string, motif: string, message: ?MessageConvocation}>  $decisions
     * @return list<array{reservation: int, reference: string, motif: string, message: ?MessageConvocation}>
     */
    private function exclureLesPartages(array $decisions): array
    {
        $usages = array_count_values(array_map(fn ($d) => $d['message']?->messageId ?? '', $decisions));

        return array_map(fn ($d) => $d['message'] !== null && $usages[$d['message']->messageId] > 1
            ? ['motif' => ApparieurConvocation::AMBIGUE, 'message' => null] + $d
            : $d, $decisions);
    }

    /**
     * @param  Collection<int, MessageConvocation>  $messages
     * @return array<string, true>
     */
    private function dejaRattaches(Collection $messages): array
    {
        return ESBTPRdvReservation::query()
            ->whereIn('convocation_message_id', $messages->pluck('messageId')->unique()->all())
            ->pluck('convocation_message_id')
            ->mapWithKeys(fn ($id) => [(string) $id => true])
            ->all();
    }

    private function ecrire(int $reservationId, MessageConvocation $message, ?Model $auteur): bool
    {
        return DB::transaction(function () use ($reservationId, $message, $auteur) {
            $ecrite = ESBTPRdvReservation::query()
                ->whereKey($reservationId)
                ->whereNull('convocation_message_id')
                ->where('convocation_statut', StatutConvocationRdv::Envoyee->value)
                ->update([
                    'convocation_message_id' => mb_substr($message->messageId, 0, 100),
                    'convocation_envoyee_at' => $message->envoyeAt->setTimezone(config('app.timezone'))->toDateTimeString(),
                    'updated_at' => now(),
                ]) === 1;
            if ($ecrite) {
                $this->journal->ligneEcrite($reservationId, $message, $auteur);
            }

            return $ecrite;
        });
    }

    private function masquer(string $reference): string
    {
        return $reference === '' ? '(sans référence)' : mb_substr($reference, 0, 4).'-****-**'.mb_substr($reference, -2);
    }
}
