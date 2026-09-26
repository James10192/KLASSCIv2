<?php

namespace App\Domain\Assistant\Consommation;

use App\Domain\Assistant\Modeles\ModeleIa;

/**
 * Additionne, modèle par modèle, ce que coûtent les appels d'un échange.
 *
 * Le coût vient du fournisseur quand il le donne (OpenRouter renvoie
 * `usage.cost`, en dollars) ; sinon il est calculé sur le tarif déclaré dans
 * config/assistant.php. Un appel en échec est compté aussi : il a été facturé.
 */
final class Compteur
{
    /** @var array<string, array> */
    private array $lignes = [];

    public function ajouter(ModeleIa $modele, int $entree, int $sortie, int $cache = 0, ?float $coutExact = null, int $latenceMs = 0, bool $echec = false): void
    {
        $ligne = $this->lignes[$modele->cle] ??= [
            'modele' => $modele->cle,
            'fournisseur' => $modele->fournisseur,
            'identifiant_modele' => $modele->identifiant,
            'appels' => 0, 'tokens_entree' => 0, 'tokens_sortie' => 0, 'tokens_cache' => 0,
            'cout_usd' => 0.0, 'cout_exact' => true, 'latence_ms' => 0, 'echec' => false,
        ];

        $cout = $coutExact ?? Tarifs::coutUsd($modele->cle, $entree, $sortie, $cache);

        $ligne['appels']++;
        $ligne['tokens_entree'] += $entree;
        $ligne['tokens_sortie'] += $sortie;
        $ligne['tokens_cache'] += $cache;
        $ligne['cout_usd'] += $cout;
        // Une ligne n'est « exacte » que si tous ses appels l'étaient.
        $ligne['cout_exact'] = $ligne['cout_exact'] && $coutExact !== null;
        $ligne['latence_ms'] += $latenceMs;
        $ligne['echec'] = $ligne['echec'] || $echec;

        $this->lignes[$modele->cle] = $ligne;
    }

    /** @return array<int, array> */
    public function lignes(): array
    {
        return array_values($this->lignes);
    }

    public function coutUsd(): float
    {
        return array_sum(array_column($this->lignes, 'cout_usd'));
    }

    public function estVide(): bool
    {
        return $this->lignes === [];
    }
}
