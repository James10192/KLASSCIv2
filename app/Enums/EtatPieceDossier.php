<?php

namespace App\Enums;

/**
 * Où en est une pièce dans un dossier d'inscription.
 *
 * Cinq états, et non un booléen « fournie ». Un booléen ne sait pas distinguer
 * une pièce jamais apportée d'une pièce apportée puis refusée : dans les deux
 * cas il vaut faux, et le guichet rappelle un étudiant qui s'est déjà déplacé.
 * Il ne sait pas davantage dire qu'une pièce du catalogue ne concerne pas cet
 * étudiant-là, qui resterait alors éternellement en défaut.
 *
 * DEPOSEE n'est pas VALIDEE : l'étudiant a remis quelque chose, personne n'a
 * encore dit que c'était la bonne pièce. Confondre les deux, c'est laisser
 * partir au ministère un dossier que nul n'a relu.
 */
enum EtatPieceDossier: string
{
    case ATTENDUE = 'attendue';
    case DEPOSEE = 'deposee';
    case VALIDEE = 'validee';
    case REFUSEE = 'refusee';
    case NON_APPLICABLE = 'non_applicable';

    public function label(): string
    {
        return match ($this) {
            self::ATTENDUE => 'Attendue',
            self::DEPOSEE => 'Déposée, à vérifier',
            self::VALIDEE => 'Validée',
            self::REFUSEE => 'Refusée',
            self::NON_APPLICABLE => 'Sans objet',
        };
    }

    /**
     * Un motif écrit est-il exigé pour cet état ?
     *
     * Refuser sans écrire pourquoi produit un dossier que personne ne peut
     * débloquer : ni l'étudiant, qui ignore ce qu'on lui reproche, ni le
     * collègue qui reprendra le guichet demain.
     */
    public function exigeUnMotif(): bool
    {
        return $this === self::REFUSEE;
    }

    /**
     * Cet état solde-t-il la pièce ?
     *
     * Une pièce validée est réglée ; une pièce sans objet l'est aussi, puisque
     * l'école a décidé qu'elle ne la réclamait pas à cet étudiant. Les trois
     * autres restent au tableau des dossiers à finir.
     */
    public function estSoldee(): bool
    {
        return $this === self::VALIDEE || $this === self::NON_APPLICABLE;
    }

    /** Valeurs brutes, pour Rule::in(). */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Options ['valeur' => 'Libellé'] pour un sélecteur premium. */
    public static function selectOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
