<?php

namespace App\Domain\Assistant\Harnais;

/**
 * Ce qu'une réponse a montré, dans l'ordre : étapes (appels d'outil), widgets,
 * blocs de texte. C'est ce qui est enregistré avec le message, pour que
 * l'historique rouvre la réponse telle qu'elle s'est affichée.
 *
 * Une étape s'enregistre plusieurs fois (en cours, puis terminée) : elle garde
 * sa place d'origine et prend son dernier état.
 */
final class FilDeReponse
{
    /** @var array<int, array> */
    private array $parties = [];

    /** @var array<string, int> position de chaque étape par identifiant */
    private array $etapes = [];

    public function etape(string $id, array $donnees): void
    {
        if (isset($this->etapes[$id])) {
            $this->parties[$this->etapes[$id]] = array_merge($this->parties[$this->etapes[$id]], $donnees);
            return;
        }

        $this->etapes[$id] = count($this->parties);
        $this->parties[] = ['type' => 'etape', 'id' => $id] + $donnees;
    }

    public function retirerEtape(string $id): void
    {
        if (!isset($this->etapes[$id])) {
            return;
        }
        unset($this->parties[$this->etapes[$id]], $this->etapes[$id]);
        $this->parties = array_values($this->parties);
        $this->etapes = [];
        foreach ($this->parties as $i => $partie) {
            if ($partie['type'] === 'etape') {
                $this->etapes[$partie['id']] = $i;
            }
        }
    }

    public function widget(string $id, array $widget): void
    {
        $kind = $widget['kind'] ?? 'inconnu';
        unset($widget['kind']);
        $this->parties[] = ['type' => 'widget', 'id' => $id, 'kind' => $kind, 'data' => $widget];
    }

    public function texte(string $texte): void
    {
        if (trim($texte) === '') {
            return;
        }
        $dernier = end($this->parties);
        if ($dernier && $dernier['type'] === 'texte') {
            // Deux blocs de texte consécutifs n'en font qu'un à l'écran.
            $this->parties[array_key_last($this->parties)]['texte'] .= "\n\n" . $texte;
            return;
        }
        $this->parties[] = ['type' => 'texte', 'texte' => $texte];
    }

    public function ajouter(array $partie): void
    {
        $this->parties[] = $partie;
    }

    public function aDesWidgets(): bool
    {
        foreach ($this->parties as $partie) {
            if ($partie['type'] === 'widget') {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array
    {
        return array_values($this->parties);
    }
}
