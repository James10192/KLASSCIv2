<?php

namespace App\Services\RendezVous\Rattrapage;

use App\Models\ESBTPRdvReservation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;

/**
 * Les bornes et reperes de chaque reservation eligible (ContexteReservation).
 *
 * Coupure du suivi : le premier envoi SUIVI, c'est-a-dire le plus ancien
 * `convocation_envoyee_at` d'une reservation qui porte un identifiant MailPulse
 * pose par l'envoi lui-meme (les lignes deja rattrapees, tracees
 * `rattrapage_convocation` dans l'audit, sont exclues : sinon la coupure
 * reculerait a chaque passage). Rien n'est code en dur : a partir de cet
 * instant, KLASSCI enregistre l'identifiant de chaque courriel, donc un
 * courriel posterieur ne peut pas etre une convocation d'avant le suivi. Sans
 * aucun envoi suivi, la coupure est maintenant.
 *
 * `coupure_max` (optionnel, fourni par l'appelant, par exemple l'heure du
 * deploiement du suivi) ne peut que l'avancer : la coupure retenue est la
 * plus ancienne des deux.
 */
class ContextesRattrapage
{
    public const SOURCE_PREMIER_ENVOI_SUIVI = 'premier_envoi_suivi';

    public const SOURCE_COUPURE_MAX = 'coupure_max';

    public const SOURCE_MAINTENANT = 'maintenant';

    /** @var array<int, string>|null */
    private ?array $anciennes = null;

    public function __construct(private readonly AnciennesAdresses $adresses) {}

    /** @return array{0: CarbonImmutable, 1: string} la coupure et d'ou elle vient */
    public function coupure(?CarbonImmutable $maximum = null): array
    {
        $premier = ESBTPRdvReservation::query()
            ->whereNotNull('convocation_message_id')
            ->whereNotNull('convocation_envoyee_at')
            ->whereNotIn('id', Audit::query()
                ->select('auditable_id')
                ->where('event', 'rattrapage_convocation')
                ->where('auditable_type', (new ESBTPRdvReservation)->getMorphClass()))
            ->min('convocation_envoyee_at');
        $candidates = [self::SOURCE_MAINTENANT => CarbonImmutable::now()];
        if ($premier !== null) {
            $candidates[self::SOURCE_PREMIER_ENVOI_SUIVI] = CarbonImmutable::parse($premier);
        }
        if ($maximum !== null) {
            $candidates[self::SOURCE_COUPURE_MAX] = $maximum;
        }
        $source = array_keys($candidates, min($candidates))[0];

        return [$candidates[$source], $source];
    }

    /**
     * @param  Collection<int, ESBTPRdvReservation>  $lot
     * @return array<int, ContexteReservation> par identifiant de reservation
     */
    public function pour(Collection $lot, CarbonInterface $coupure): array
    {
        $this->anciennes ??= $this->adresses->empreintesDesReservations();
        [$suivantes, $precedees] = $this->voisines($lot);

        $contextes = [];
        foreach ($lot as $r) {
            $porteur = $r->candidature ?? $r->demande;
            $suivante = $suivantes[$r->id] ?? null;
            $contextes[$r->id] = new ContexteReservation(
                $r->convocation_action ?: 'confirme',
                $r->email,
                $this->anciennes[$r->id] ?? null,
                $porteur?->rdv_invite_at,
                CarbonImmutable::parse($r->created_at),
                isset($precedees[$r->id]),
                $suivante === null ? $coupure : $suivante->min($coupure),
            );
        }

        return $contextes;
    }

    /**
     * Pour chaque reservation : la creation de la suivante du meme dossier, et
     * si une precedente existe, toutes reservations confondues (annulees
     * comprises).
     *
     * @param  Collection<int, ESBTPRdvReservation>  $lot
     * @return array{0: array<int, CarbonImmutable>, 1: array<int, true>}
     */
    private function voisines(Collection $lot): array
    {
        $candidatures = $lot->pluck('candidature_id')->filter()->unique()->values()->all();
        $demandes = $lot->pluck('reinscription_demande_id')->filter()->unique()->values()->all();

        $parDossier = ESBTPRdvReservation::query()
            ->select(['id', 'candidature_id', 'reinscription_demande_id', 'created_at'])
            ->where(fn ($q) => $q->whereIn('candidature_id', $candidatures)->orWhereIn('reinscription_demande_id', $demandes))
            ->orderBy('created_at')->orderBy('id')
            ->get()
            ->groupBy(fn ($r) => $r->candidature_id ? 'c'.$r->candidature_id : 'd'.$r->reinscription_demande_id);

        $suivantes = [];
        $precedees = [];
        foreach ($parDossier as $reservations) {
            $liste = $reservations->values();
            foreach ($liste as $i => $r) {
                if (isset($liste[$i + 1])) {
                    $suivantes[$r->id] = CarbonImmutable::parse($liste[$i + 1]->created_at);
                }
                if ($i > 0) {
                    $precedees[$r->id] = true;
                }
            }
        }

        return [$suivantes, $precedees];
    }
}
