<?php

namespace App\Domain\Assistant\Fournisseurs;

use Psr\Http\Message\StreamInterface;

/**
 * Lecteur SSE commun aux trois API (Anthropic, OpenAI, Gemini).
 *
 * Les morceaux lus sur le réseau coupent les événements n'importe où : le
 * lecteur garde le reste en tampon jusqu'à la ligne vide qui clôt l'événement.
 * Une donnée qui n'est pas du JSON (le `[DONE]` d'OpenAI) est ignorée.
 */
final class LecteurSse
{
    private string $tampon = '';

    /**
     * Lit un corps HTTP par morceaux et rend les événements au fil de l'eau.
     *
     * @param callable():bool $arreter
     * @return \Generator<int, array{event: ?string, data: array}>
     */
    public static function depuisCorps(StreamInterface $corps, callable $arreter, int $taille = 8192): \Generator
    {
        $lecteur = new self();

        try {
            while (!$corps->eof()) {
                if ($arreter()) {
                    return;
                }
                $morceau = $corps->read($taille);
                if ($morceau === '') {
                    continue;
                }
                foreach ($lecteur->alimenter($morceau) as $evenement) {
                    yield $evenement;
                }
            }
            foreach ($lecteur->vider() as $evenement) {
                yield $evenement;
            }
        } finally {
            $corps->close();
        }
    }

    /**
     * @return array<int, array{event: ?string, data: array}>
     */
    public function alimenter(string $morceau): array
    {
        $this->tampon .= str_replace(["\r\n", "\r"], "\n", $morceau);

        $evenements = [];
        while (($pos = strpos($this->tampon, "\n\n")) !== false) {
            $bloc = substr($this->tampon, 0, $pos);
            $this->tampon = (string) substr($this->tampon, $pos + 2);

            $evenement = $this->analyser($bloc);
            if ($evenement !== null) {
                $evenements[] = $evenement;
            }
        }

        return $evenements;
    }

    /** Dernier événement éventuel, arrivé sans ligne vide de clôture. */
    public function vider(): array
    {
        $reste = trim($this->tampon);
        $this->tampon = '';
        $evenement = $reste === '' ? null : $this->analyser($reste);

        return $evenement === null ? [] : [$evenement];
    }

    private function analyser(string $bloc): ?array
    {
        $nom = null;
        $lignes = [];

        foreach (explode("\n", $bloc) as $ligne) {
            if ($ligne === '' || str_starts_with($ligne, ':')) {
                continue;
            }
            [$champ, $valeur] = array_pad(explode(':', $ligne, 2), 2, '');
            $valeur = ltrim($valeur, ' ');

            if ($champ === 'event') {
                $nom = $valeur;
            } elseif ($champ === 'data') {
                $lignes[] = $valeur;
            }
        }

        if ($lignes === []) {
            return null;
        }

        $donnees = json_decode(implode("\n", $lignes), true);
        if (!is_array($donnees)) {
            return null;
        }

        return ['event' => $nom ?? ($donnees['type'] ?? null), 'data' => $donnees];
    }
}
