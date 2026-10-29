<?php

namespace App\Domain\Assistant\Actions;

/**
 * Ce qu'une action VA faire, avant de le faire : ce que l'utilisateur lit et
 * valide. Rien n'est écrit tant qu'il n'a pas cliqué « Valider ».
 *
 *  - manques        : ce qui empêche de valider (une information absente ou ambiguë).
 *                     L'assistant doit poser la question ; jamais deviner.
 *  - avertissements : à lire avant de valider, sans bloquer.
 *  - donnees        : ce que l'exécution écrira, déjà résolu (identifiants).
 *  - etat           : ce qu'il y a en base au moment de la proposition ; l'empreinte
 *                     le couvre, donc un changement entre-temps annule la validation.
 *  - sansObjet      : la demande est déjà satisfaite (notes déjà à ces valeurs,
 *                     année déjà courante). Ce n'est pas une question à poser :
 *                     l'assistant le dit, et ne propose rien.
 */
final class Proposition
{
    /**
     * @param array{colonnes: string[], lignes: array<int, string[]>} $tableau
     * @param string[] $manques
     * @param string[] $avertissements
     */
    public function __construct(
        public readonly string $titre,
        public readonly string $resume,
        public readonly array $tableau = ['colonnes' => [], 'lignes' => []],
        public readonly array $manques = [],
        public readonly array $avertissements = [],
        public readonly array $donnees = [],
        public readonly array $etat = [],
        public readonly string $risque = 'moyen',
        public readonly ?string $sansObjet = null,
    ) {
    }

    /** La demande est déjà satisfaite : il n'y a rien à proposer. */
    public static function sansObjet(string $titre, string $raison): self
    {
        return new self(titre: $titre, resume: '', sansObjet: $raison);
    }

    public function estComplete(): bool
    {
        return $this->manques === [] && $this->sansObjet === null;
    }

    public function empreinte(): string
    {
        return hash('sha256', json_encode([$this->donnees, $this->etat], JSON_UNESCAPED_UNICODE));
    }
}
