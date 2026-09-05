<?php

namespace App\Enums;

/**
 * Où en est UN dépôt de pièce.
 *
 * Un booléen « fournie » ne suffit pas, et c'est le manque central des essais
 * précédents : il ne distingue pas une pièce jamais apportée d'une pièce
 * apportée puis refusée. Dans les deux cas il vaut faux, et le guichet rappelle
 * un étudiant qui s'est déjà déplacé.
 *
 * `non_applicable` NE FIGURE PAS ici. C'était le cinquième cas des versions
 * précédentes, et c'est le seul qui soit vraiment ANNUEL : une pièce du
 * catalogue peut ne pas concerner un étudiant cette année-là — un transfert sans
 * certificat de scolarité du même établissement — sans que cela dise quoi que ce
 * soit de son dépôt. Il vit donc en drapeau sur la ligne de CONSOMMATION, et
 * cette énumération ne décrit plus que le dépôt.
 */
enum EtatPieceDossier: string
{
    case ATTENDUE = 'attendue';
    case DEPOSEE = 'deposee';
    case VALIDEE = 'validee';
    case REFUSEE = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::ATTENDUE => 'Attendue',
            self::DEPOSEE => 'Déposée',
            self::VALIDEE => 'Validée',
            self::REFUSEE => 'Refusée',
        };
    }

    /** Ce que la ligne dit à qui la lit en diagonale. */
    public function aide(): string
    {
        return match ($this) {
            self::ATTENDUE => "L'étudiant ne l'a pas encore apportée",
            self::DEPOSEE => 'Remise au guichet, pas encore relue',
            self::VALIDEE => 'Relue et acceptée',
            self::REFUSEE => 'Remise puis écartée — le motif dit pourquoi',
        };
    }

    public function exigeUnMotif(): bool
    {
        return $this === self::REFUSEE;
    }

    /**
     * Ce dépôt compte-t-il dans le stock disponible ?
     *
     * DEPOSEE n'est PAS soldée : l'étudiant a remis quelque chose, personne n'a
     * encore dit que c'était la bonne pièce. Confondre les deux, c'est laisser
     * partir au ministère un dossier que nul n'a relu.
     */
    public function estSoldee(): bool
    {
        return $this === self::VALIDEE;
    }

    /** Couleur de pastille — sémantique, pas décorative. */
    public function ton(): string
    {
        return match ($this) {
            self::ATTENDUE => 'attente',
            self::DEPOSEE => 'info',
            self::VALIDEE => 'succes',
            self::REFUSEE => 'danger',
        };
    }

    /** Valeurs brutes, pour Rule::in() et pour la contrainte CHECK. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function selectOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * Lecture tolérante. Rend null sur l'inconnu : c'est à l'appelant de dire ce
     * qu'il fait d'une valeur qu'il ne reconnaît pas.
     */
    public static function tryFromLibre(?string $brut): ?self
    {
        return self::tryFrom(strtolower(trim((string) $brut)));
    }
}
