<?php

namespace App\Domain\Admissions;

use App\Domain\Notifications\PhoneFormatter;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\AccueilRdv;
use Carbon\Carbon;

/**
 * Une famille attendue aujourd'hui, telle que le guichet la lit : qui, pour
 * quoi, ou elle en est, et le seul geste utile a cet instant.
 *
 * L'etat vient de AccueilRdv (reçue, attendue, non venue, dossier traite) ; il
 * est seulement precise ici : un dossier traite est-il inscrit ou rejete, une
 * famille attendue est-elle en retard.
 */
final class FamilleDuJour
{
    public const INSCRITE = 'inscrite';

    public const REJETEE = 'rejetee';

    public const EN_RETARD = 'en_retard';

    private function __construct(
        public readonly ESBTPRdvReservation $reservation,
        public readonly string $type,
        public readonly string $nom,
        public readonly string $parcours,
        public readonly string $etat,
        public readonly int $retardMinutes,
        public readonly ?string $lienDossier,
        public readonly ?string $lienFinaliser,
    ) {
    }

    /**
     * @param  array{voir: list<string>, finaliser: list<string>}  $droits  types lisibles et types que l'agent peut inscrire
     */
    public static function depuis(ESBTPRdvReservation $r, AccueilRdv $accueil, array $droits, Carbon $maintenant): self
    {
        $candidature = $r->candidature;
        $type = $candidature !== null ? FileDesDemandes::TYPE_NOUVELLE : FileDesDemandes::TYPE_REINSCRIPTION;
        $porteur = $candidature ?? $r->demande;
        $etat = $accueil->etat($r);
        $statut = (string) ($porteur?->statut ?? '');

        $etat = match (true) {
            $statut === ESBTPCandidature::STATUT_CONVERTIE => self::INSCRITE,
            $etat === AccueilRdv::TRAITEE => self::REJETEE,
            $accueil->enRetard($r) => self::EN_RETARD,
            default => $etat,
        };
        $ouvert = ! in_array($etat, [self::INSCRITE, self::REJETEE], true);
        $voir = in_array($type, $droits['voir'], true);

        return new self(
            reservation: $r,
            type: $type,
            nom: $r->nomComplet() !== '' ? $r->nomComplet() : 'Famille sans nom',
            parcours: self::parcours($r),
            etat: $etat,
            retardMinutes: $etat === self::EN_RETARD ? (int) $r->creneau->debut()->diffInMinutes($maintenant) : 0,
            lienDossier: $voir ? DemandeDInscription::lien($r->candidature_id, $r->reinscription_demande_id) : null,
            lienFinaliser: $voir && $ouvert && in_array($type, $droits['finaliser'], true)
                ? DemandeDInscription::lien($r->candidature_id, $r->reinscription_demande_id, true)
                : null,
        );
    }

    public function estNouvelle(): bool
    {
        return $this->type === FileDesDemandes::TYPE_NOUVELLE;
    }

    /** Reçue, et le dossier attend encore son inscription : la famille est au guichet. */
    public function estRecue(): bool
    {
        return $this->etat === AccueilRdv::RECUE;
    }

    /** La case « Reçue » ne se touche que sur un dossier ouvert. */
    public function peutEtreCochee(): bool
    {
        return ! in_array($this->etat, [self::INSCRITE, self::REJETEE], true);
    }

    /** @return array{0: string, 1: string} texte, ton (neutre, primaire, alerte, succes) */
    public function badge(): array
    {
        return match ($this->etat) {
            AccueilRdv::RECUE => ['Au guichet'.($this->reservation->accueilli_at ? ' · '.$this->reservation->accueilli_at->format('H:i') : ''), 'primaire'],
            self::INSCRITE => [$this->estNouvelle() ? 'Inscrite' : 'Réinscrite', 'succes'],
            self::REJETEE => ['Dossier rejeté', 'neutre'],
            self::EN_RETARD => ['En retard · '.$this->retardMinutes.' min', 'alerte'],
            AccueilRdv::NON_VENUE => ['Non venue', 'alerte'],
            default => ['Attendue', 'neutre'],
        };
    }

    public function telephone(): string
    {
        return (string) $this->reservation->telephone;
    }

    public function telephoneLisible(): string
    {
        return PhoneFormatter::toReadable($this->telephone()) ?: $this->telephone();
    }

    /** Le texte sur lequel porte la recherche de la page, en minuscules. */
    public function indexRecherche(): string
    {
        return mb_strtolower($this->nom.' '.preg_replace('/\D/', '', $this->telephone()), 'UTF-8');
    }

    private static function parcours(ESBTPRdvReservation $r): string
    {
        if ($r->candidature !== null) {
            $voeu = $r->candidature->voeu();

            return $voeu !== '' ? $voeu : 'Vœu non précisé';
        }

        return $r->demande?->classeSouhaitee?->name ?? 'Classe à choisir';
    }
}
