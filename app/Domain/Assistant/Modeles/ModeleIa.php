<?php

namespace App\Domain\Assistant\Modeles;

/**
 * Un modèle déclaré dans config/assistant.php, résolu avec son fournisseur.
 *
 * La clé d'API vit ici en mémoire seulement : elle ne sort jamais vers le
 * navigateur (versPublic()) ni vers les journaux.
 */
final class ModeleIa
{
    public function __construct(
        public readonly string $cle,
        public readonly string $fournisseur,
        public readonly string $adaptateur,
        public readonly string $identifiant,
        public readonly string $libelle,
        public readonly bool $outils,
        public readonly bool $diffusion,
        private readonly ?string $cleApi,
        public readonly string $url,
    ) {
    }

    public function cleApi(): string
    {
        return (string) $this->cleApi;
    }

    public function estConfigure(): bool
    {
        return is_string($this->cleApi) && trim($this->cleApi) !== '';
    }

    /** Ce que le navigateur peut connaître d'un modèle (sélecteur). */
    public function versPublic(): array
    {
        return ['cle' => $this->cle, 'libelle' => $this->libelle, 'fournisseur' => $this->fournisseur];
    }
}
