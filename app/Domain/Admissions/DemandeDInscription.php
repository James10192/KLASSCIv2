<?php

namespace App\Domain\Admissions;

use App\Domain\Notifications\PhoneFormatter;
use App\Enums\StatutReservationRdv;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use Illuminate\Database\Eloquent\Model;

/**
 * Une ligne de la file, quelle que soit sa table d'origine.
 *
 * Elle porte ce que l'ecran affiche et la prochaine etape du dossier, calculee
 * une fois ici : la vue n'a pas a savoir qu'une candidature « acceptee » attend
 * son inscription alors qu'une demande de reinscription n'a pas cet etat.
 */
final class DemandeDInscription
{
    /** Etapes suivantes possibles. `voir` : le dossier est clos. */
    public const ETAPE_INSCRIRE = 'inscrire';

    public const ETAPE_EXAMINER = 'examiner';

    public const ETAPE_REINSCRIRE = 'reinscrire';

    public const ETAPE_VOIR = 'voir';

    private function __construct(
        public readonly string $type,
        public readonly int $id,
        public readonly Model $modele,
        public readonly string $nom,
        public readonly string $sousTitre,
        public readonly string $parcours,
        public readonly string $parcoursDetail,
        public readonly string $statut,
        public readonly string $etape,
        public readonly ?ESBTPRdvReservation $rendezVous,
        public readonly string $telephone,
        public readonly ?string $obstacle = null,
    ) {
    }

    public static function deCandidature(ESBTPCandidature $c): self
    {
        $ouverte = ! $c->dossierClos();
        $rdv = $c->relationLoaded('reservations') ? $c->reservations->first() : null;
        $recue = $rdv?->statut === StatutReservationRdv::Honoree;
        $bac = trim(collect([$c->serie_bac ? 'Bac '.$c->serie_bac : null, $c->annee_bac])->filter()->join(' '));

        return new self(
            type: FileDesDemandes::TYPE_NOUVELLE,
            id: (int) $c->id,
            modele: $c,
            nom: $c->nomComplet(),
            sousTitre: 'déposée le '.($c->created_at?->format('d/m') ?? '—'),
            parcours: $c->voeu() !== '' ? $c->voeu() : 'Vœu non précisé',
            parcoursDetail: $c->est_transfert
                ? 'transfert'.($c->etablissement_sup_origine ? ' · '.$c->etablissement_sup_origine : '')
                : ($bac !== '' ? $bac : (string) $c->anneeUniversitaire?->name),
            statut: (string) $c->statut,
            etape: match (true) {
                ! $ouverte => self::ETAPE_VOIR,
                $c->statut === ESBTPCandidature::STATUT_ACCEPTEE, $recue => self::ETAPE_INSCRIRE,
                default => self::ETAPE_EXAMINER,
            },
            rendezVous: $rdv,
            telephone: (string) $c->telephone,
        );
    }

    /**
     * @param  bool|null  $dejaInscrit  calcule en lot par la file ; null : le lire ici
     */
    public static function deReinscription(ESBTPReinscriptionDemande $d, ?bool $dejaInscrit = null): self
    {
        $e = $d->etudiant;
        $classe = $d->inscription?->classe?->name ?? $d->classeSouhaitee?->name;
        $obstacle = $d->estTraitable() ? self::obstacle($d, $dejaInscrit) : null;

        return new self(
            type: FileDesDemandes::TYPE_REINSCRIPTION,
            id: (int) $d->id,
            modele: $d,
            nom: trim(($e?->nom ?? '').' '.($e?->prenoms ?? '')) ?: 'Étudiant introuvable',
            sousTitre: (string) ($e?->matricule ?? ''),
            parcours: $classe ? $classe : 'Classe à choisir',
            parcoursDetail: (string) $d->anneeUniversitaire?->name,
            statut: (string) $d->statut,
            etape: match (true) {
                ! $d->estTraitable() => self::ETAPE_VOIR,
                $obstacle !== null => self::ETAPE_EXAMINER,
                default => self::ETAPE_REINSCRIRE,
            },
            rendezVous: $d->relationLoaded('reservations') ? $d->reservations->first() : null,
            telephone: (string) ($e?->telephone ?? ''),
            obstacle: $obstacle,
        );
    }

    /**
     * Ce qui empecherait la conversion, dit AVANT que l'agent remplisse la
     * fenetre : les deux refus de ESBTPReinscriptionDemandeController::convertir()
     * qui ne dependent que du dossier.
     */
    private static function obstacle(ESBTPReinscriptionDemande $d, ?bool $dejaInscrit): ?string
    {
        if (! optional($d->anneeUniversitaire)->is_current) {
            return "Cette demande vise une année qui n'est plus en cours : rejetez-la et invitez l'étudiant à déposer de nouveau.";
        }

        $dejaInscrit ??= \App\Models\ESBTPInscription::aUneInscriptionVivantePour((int) $d->etudiant_id, (int) $d->annee_universitaire_id);

        return $dejaInscrit
            ? 'Cet étudiant est déjà inscrit pour '.$d->anneeUniversitaire?->name.' : rejetez la demande plutôt que de la convertir.'
            : null;
    }

    /**
     * L'adresse qui ouvre ce dossier dans la file, depuis n'importe quel ecran.
     * `agir` enchaine sur l'etape suivante (inscrire, reinscrire) des l'ouverture.
     */
    public static function lien(?int $candidatureId, ?int $demandeId, bool $agir = false): string
    {
        $cle = $candidatureId ? FileDesDemandes::TYPE_NOUVELLE.'-'.$candidatureId : FileDesDemandes::TYPE_REINSCRIPTION.'-'.$demandeId;

        return route('esbtp.demandes.index', array_filter(['etat' => 'toutes', 'ouvrir' => $cle, 'agir' => $agir ? 1 : null]));
    }

    /** Cle unique dans la file : l'id seul se repete d'une table a l'autre. */
    public function cle(): string
    {
        return $this->type.'-'.$this->id;
    }

    public function estNouvelle(): bool
    {
        return $this->type === FileDesDemandes::TYPE_NOUVELLE;
    }

    public function estOuverte(): bool
    {
        return $this->etape !== self::ETAPE_VOIR;
    }

    /** Acceptee, pas encore inscrite : n'existe que pour une candidature. */
    public function estAcceptee(): bool
    {
        return $this->estNouvelle() && $this->statut === ESBTPCandidature::STATUT_ACCEPTEE;
    }

    /** Inscrite ou reinscrite : les deux tables nomment ce statut de la meme facon. */
    public function estInscrite(): bool
    {
        return $this->statut === $this->modele::STATUT_CONVERTIE;
    }

    public function estRejetee(): bool
    {
        return $this->statut === $this->modele::STATUT_REJETEE;
    }

    public function initiales(): string
    {
        $mots = preg_split('/\s+/u', trim($this->nom)) ?: [];

        return mb_strtoupper(mb_substr($mots[0] ?? '', 0, 1, 'UTF-8').mb_substr($mots[1] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
    }

    public function recueAuGuichet(): bool
    {
        return $this->rendezVous?->statut === StatutReservationRdv::Honoree;
    }

    public function telephoneLisible(): string
    {
        return PhoneFormatter::toReadable($this->telephone) ?: $this->telephone;
    }

    public function contactAVerifier(): ?string
    {
        return StatutVerificationContact::badge($this->modele->verification_contact ?? null);
    }

    public function reference(): string
    {
        return (string) ($this->modele->referencePubliqueAffichee() ?? '');
    }

    /** Libelle du statut, dit du point de vue de l'accueil. */
    public function libelleStatut(): string
    {
        return match (true) {
            $this->estAcceptee() => 'Acceptée · à inscrire',
            $this->estInscrite() => $this->estNouvelle() ? 'Inscrite' : 'Réinscrite',
            $this->estRejetee() => 'Rejetée',
            $this->estOuverte() => $this->recueAuGuichet() ? 'Reçue au guichet' : 'À examiner',
            default => (string) $this->statut,
        };
    }

    /** Le rendez-vous en une ligne, et son detail. @return array{0: string, 1: string, 2: string} texte, detail, ton */
    public function rendezVousResume(): array
    {
        $r = $this->rendezVous;
        if ($r === null || $r->creneau === null) {
            return $this->estOuverte() ? ['Aucun rendez-vous', 'à proposer', 'aucun'] : ['—', '', 'neutre'];
        }
        if ($r->statut === StatutReservationRdv::Honoree) {
            return ['Reçue au guichet'.($r->accueilli_at ? ' · '.$r->accueilli_at->format('H:i') : ''),
                'rendez-vous de '.$r->creneau->heureDebutHi().($r->accueilliPar ? ' · par '.$r->accueilliPar->name : ''), 'recue'];
        }
        $jour = $r->creneau->date;
        $texte = ($jour->isToday() ? "Aujourd'hui" : ucfirst($jour->translatedFormat('D j M'))).' · '.$r->creneau->heureDebutHi();

        if ($r->creneau->estTermine()) {
            return [$texte, 'non venue · à reprogrammer', 'retard'];
        }

        return [$texte, mb_strtolower($r->convocation_statut?->label() ?? 'convocation à envoyer', 'UTF-8'), 'fixe'];
    }
}
