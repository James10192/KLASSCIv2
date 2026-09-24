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
 */
class ContextesRattrapage
{
    /** Tolerance entre la creation de la reservation et le depart du courriel. */
    private const TOLERANCE_SECONDES = 60;

    /** @var array<int, string>|null */
    private ?array $anciennes = null;

    public function __construct(private readonly AnciennesAdresses $adresses) {}

    public function coupure(): CarbonImmutable
    {
        $premier = ESBTPRdvReservation::query()
            ->whereNotNull('convocation_message_id')
            ->whereNotNull('convocation_envoyee_at')
            ->whereNotIn('id', Audit::query()
                ->select('auditable_id')
                ->where('event', 'rattrapage_convocation')
                ->where('auditable_type', (new ESBTPRdvReservation)->getMorphClass()))
            ->min('convocation_envoyee_at');
        $maintenant = CarbonImmutable::now();

        return $premier === null ? $maintenant : CarbonImmutable::parse($premier)->min($maintenant);
    }

    /**
     * @param  Collection<int, ESBTPRdvReservation>  $lot
     * @return array<int, ContexteReservation> par identifiant de reservation
     */
    public function pour(Collection $lot, CarbonInterface $coupure): array
    {
        $this->anciennes ??= $this->adresses->empreintesDesReservations();
        $suivantes = $this->creationDesSuivantes($lot);

        $contextes = [];
        foreach ($lot as $r) {
            $porteur = $r->candidature ?? $r->demande;
            $suivante = $suivantes[$r->id] ?? null;
            $contextes[$r->id] = new ContexteReservation(
                $r->convocation_action ?: 'confirme',
                $r->email,
                $this->anciennes[$r->id] ?? null,
                $porteur?->rdv_invite_at,
                CarbonImmutable::parse($r->created_at)->subSeconds(self::TOLERANCE_SECONDES),
                $suivante === null ? $coupure : $suivante->min($coupure),
            );
        }

        return $contextes;
    }

    /**
     * La creation de la reservation suivante du meme dossier, toutes
     * reservations confondues (annulees comprises).
     *
     * @param  Collection<int, ESBTPRdvReservation>  $lot
     * @return array<int, CarbonImmutable>
     */
    private function creationDesSuivantes(Collection $lot): array
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
        foreach ($parDossier as $reservations) {
            $liste = $reservations->values();
            foreach ($liste as $i => $r) {
                if (isset($liste[$i + 1])) {
                    $suivantes[$r->id] = CarbonImmutable::parse($liste[$i + 1]->created_at);
                }
            }
        }

        return $suivantes;
    }
}
