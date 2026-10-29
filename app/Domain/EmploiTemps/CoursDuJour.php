<?php

namespace App\Domain\EmploiTemps;

use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacherAttendance;
use Carbon\Carbon;

/**
 * Un cours de l'enseignant, aujourd'hui, avec ce qu'il y a à faire dessus.
 *
 * L'état est décidé une seule fois ici, à partir des délais réglés par l'école
 * (`FenetresDEmargement`). Le tableau de bord et l'écran d'émargement le lisent
 * tous les deux : ils ne peuvent plus afficher deux vérités sur le même cours,
 * ce qui arrivait quand chacun refaisait son calcul dans sa vue.
 */
final class CoursDuJour
{
    public const A_VENIR = 'a_venir';
    public const OUVERT = 'ouvert';
    public const RETARD = 'retard';
    public const MOTIF_REQUIS = 'motif_requis';
    public const DEPASSE = 'depasse';
    public const ABSENT = 'absent';
    public const EN_COURS = 'en_cours';
    public const FIN_OUVERTE = 'fin_ouverte';
    public const FIN_MANQUEE = 'fin_manquee';
    public const TERMINE = 'termine';

    public function __construct(
        public readonly ESBTPSeanceCours $seance,
        public readonly Carbon $debut,
        public readonly Carbon $fin,
        public readonly Carbon $ouverture,
        public readonly Carbon $limitePresent,
        public readonly Carbon $limiteRetard,
        public readonly Carbon $finOuverture,
        public readonly Carbon $finFermeture,
        public readonly ?ESBTPTeacherAttendance $emargementDebut,
        public readonly ?ESBTPTeacherAttendance $emargementFin,
        public readonly bool $appelFait,
        public readonly bool $finAutorisee,
        public readonly string $etat,
    ) {
    }

    public function matiere(): string
    {
        return $this->seance->matiere->name ?? 'Matière non définie';
    }

    public function classe(): ?string
    {
        return $this->seance->emploiTemps->classe->name ?? $this->seance->classe->name ?? null;
    }

    /** « Salle B12 », sans doubler le mot quand l'école l'a déjà saisi. */
    public function salle(): ?string
    {
        return self::libelleSalle($this->seance->salle);
    }

    public static function libelleSalle(?string $salle): ?string
    {
        $salle = trim((string) $salle);
        if ($salle === '') {
            return null;
        }

        return preg_match('/^(salle|amphi|labo|atelier)\b/iu', $salle) ? $salle : 'Salle '.$salle;
    }

    /**
     * Le cours attend-il un émargement maintenant ?
     *
     * La fin ne compte que si `sign()` l'accepterait : il exige l'appel de
     * début (`ESBTPSessionWorkflow::call_start_done`). Sans lui, proposer
     * « Émarger la fin » mènerait à un refus — c'est l'appel qui est dû.
     */
    public function demandeUnEmargement(): bool
    {
        if ($this->etat === self::FIN_OUVERTE) {
            return $this->finAutorisee;
        }

        return in_array($this->etat, [self::OUVERT, self::RETARD, self::MOTIF_REQUIS], true);
    }

    /** L'appel est dû : cours émargé, pas d'appel aujourd'hui, ou fin bloquée faute d'appel. */
    public function appelAFaire(): bool
    {
        if (! $this->estEmarge() || ! in_array($this->etat, [self::EN_COURS, self::FIN_OUVERTE], true)) {
            return false;
        }

        return ! $this->appelFait || ($this->etat === self::FIN_OUVERTE && ! $this->finAutorisee);
    }

    public function estEmarge(): bool
    {
        return $this->emargementDebut !== null && $this->emargementDebut->status !== 'absent';
    }

    /** Libellé court de l'état, pour une puce. */
    public function libelle(): string
    {
        return match ($this->etat) {
            self::A_VENIR => 'Ouvre à '.$this->ouverture->format('H:i'),
            self::OUVERT => 'Ouvert — présent jusqu’à '.$this->limitePresent->format('H:i'),
            self::RETARD => 'En retard — jusqu’à '.$this->limiteRetard->format('H:i'),
            self::MOTIF_REQUIS => 'Délai dépassé — motif demandé',
            self::DEPASSE => 'Délai dépassé',
            self::ABSENT => 'Absence enregistrée',
            self::EN_COURS => 'Émargé à '.($this->emargementDebut?->validated_at?->format('H:i') ?? '--:--').' — fin à '.$this->finOuverture->format('H:i'),
            self::FIN_OUVERTE => $this->finAutorisee
                ? 'Fin ouverte jusqu’à '.$this->finFermeture->format('H:i')
                : 'Appel à faire avant la fin',
            self::FIN_MANQUEE => 'Fin non émargée',
            self::TERMINE => 'Séance complète',
        };
    }

    /** Ton sémantique de l'état : success, warning, danger, primary ou muted. */
    public function ton(): string
    {
        return match ($this->etat) {
            self::FIN_OUVERTE => $this->finAutorisee ? 'success' : 'warning',
            self::OUVERT, self::TERMINE => 'success',
            self::RETARD, self::MOTIF_REQUIS => 'warning',
            self::DEPASSE, self::ABSENT, self::FIN_MANQUEE => 'danger',
            self::EN_COURS => 'primary',
            default => 'muted',
        };
    }
}
