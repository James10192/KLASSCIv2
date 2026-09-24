<?php

namespace App\Services\RendezVous\Rattrapage;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\Portail\ReferencePublique;
use App\Services\RendezVous\PerimetreRdv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rattache aux convocations d'avant le suivi (22/09) l'identifiant et la date
 * du courriel MailPulse qui les a portees, pour que la synchronisation puisse
 * enfin relire leur remise.
 *
 * Eligibles : reservations confirmees, convocation « envoyee » sans
 * identifiant ni date d'envoi, dans PerimetreRdv. Un courriel deja rattache
 * n'est jamais reutilise ; un courriel que deux reservations choisiraient est
 * ambigu pour les deux. Un courriel sans reference est ecarte seul.
 *
 * Simulation par defaut. En execution, chaque ecriture est conditionnelle
 * (identifiant et date encore vides) et tracee dans l'audit, dans la meme
 * transaction : sans trace, pas d'ecriture. Relancer ne change rien.
 */
class RattrapageConvocations
{
    public const SOURCE = 'rattrapage_mailpulse';

    private const EXEMPLES_MAX = 20;

    public function __construct(
        private readonly PerimetreRdv $perimetre,
        private readonly ApparieurConvocation $apparieur,
        private readonly ContextesRattrapage $contextes,
        private readonly ReferencePublique $references,
        private readonly JournalRattrapage $journal,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $bruts
     * @return array{execute: bool, eligibles: int, appariees: int, ambigues: int, sans_message: int, sans_reference: int, deja_renseignees: int, ecrites: int, exemples: list<array{reference_masquee: string, motif: string}>}
     */
    public function traiter(array $bruts, bool $executer, ?Model $auteur = null): array
    {
        [$sansReference, $avecReference] = collect($bruts)
            ->map(fn (array $m) => MessageConvocation::depuis($m, $this->references))
            ->partition(fn (MessageConvocation $m) => $m->reference === '');
        $dejaRattaches = $this->dejaRattaches($avecReference);
        $parReference = $avecReference
            ->reject(fn (MessageConvocation $m) => isset($dejaRattaches[$m->messageId]))
            ->groupBy(fn (MessageConvocation $m) => $m->reference);

        $decisions = $this->decider($parReference);
        $rapport = ['execute' => $executer, 'eligibles' => count($decisions), 'appariees' => 0, 'ambigues' => 0,
            'sans_message' => 0, 'sans_reference' => $sansReference->count(), 'deja_renseignees' => count($dejaRattaches),
            'ecrites' => 0, 'exemples' => []];

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

    /** @return Builder<ESBTPRdvReservation> les reservations eligibles, ou encore ecrivables */
    public static function eligibles(Builder $reservations): Builder
    {
        return $reservations
            ->where('statut', StatutReservationRdv::Confirmee->value)
            ->where('convocation_statut', StatutConvocationRdv::Envoyee->value)
            ->whereNull('convocation_message_id')
            ->whereNull('convocation_envoyee_at');
    }

    /**
     * @param  Collection<string, Collection<int, MessageConvocation>>  $parReference
     * @return list<array{reservation: int, reference: string, motif: string, message: ?MessageConvocation}>
     */
    private function decider(Collection $parReference): array
    {
        $coupure = $this->contextes->coupure();
        $decisions = [];
        self::eligibles($this->perimetre->reservations())
            ->select(['id', 'creneau_id', 'candidature_id', 'reinscription_demande_id', 'email', 'convocation_action', 'created_at'])
            ->with(['candidature:id,reference_publique,rdv_invite_at', 'demande:id,reference_publique,rdv_invite_at'])
            ->chunkById(500, function (Collection $lot) use (&$decisions, $parReference, $coupure) {
                $contextes = $this->contextes->pour($lot, $coupure);
                foreach ($lot as $reservation) {
                    $reference = $this->references->normaliser((string) ($reservation->candidature ?? $reservation->demande)?->reference_publique);
                    [$motif, $message] = $reference === ''
                        ? [ApparieurConvocation::SANS_MESSAGE, null]
                        : $this->apparieur->choisir($parReference->get($reference, collect())->all(), $contextes[$reservation->id]);
                    $decisions[] = ['reservation' => (int) $reservation->id, 'reference' => $reference, 'motif' => $motif, 'message' => $message];
                }
            });

        return $this->exclureLesPartages($decisions);
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
            ->whereIn('convocation_message_id', $messages->pluck('messageId')->unique()->values()->all())
            ->pluck('convocation_message_id')
            ->mapWithKeys(fn ($id) => [(string) $id => true])
            ->all();
    }

    /** Ecriture et trace d'audit ensemble : l'une sans l'autre est annulee, et la ligne comptee non ecrite. */
    private function ecrire(int $reservationId, MessageConvocation $message, ?Model $auteur): bool
    {
        try {
            return DB::transaction(function () use ($reservationId, $message, $auteur) {
                $envoyeLe = $message->envoyeAt->setTimezone(config('app.timezone'))->toDateTimeString();
                $ecrite = self::eligibles(ESBTPRdvReservation::query()->whereKey($reservationId))->update([
                    'convocation_message_id' => mb_substr($message->messageId, 0, 100),
                    'convocation_envoyee_at' => $envoyeLe,
                    'updated_at' => now(),
                ]) === 1;
                if ($ecrite) {
                    $this->journal->ligneEcrite($reservationId, $message, $auteur);
                }

                return $ecrite;
            });
        } catch (\Throwable $e) {
            Log::error('Rattrapage convocation : ligne annulee', ['reservation_id' => $reservationId, 'erreur' => $e->getMessage()]);

            return false;
        }
    }

    private function masquer(string $reference): string
    {
        return $reference === '' ? '(sans référence)' : mb_substr($reference, 0, 2).'**-****-**'.mb_substr($reference, -2);
    }
}
