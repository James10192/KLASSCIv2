<?php

namespace App\Domain\Assistant\Pieces;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Tableaux extraits des fichiers joints, gardés deux heures pour la personne qui
 * les a envoyés. Le fichier lui-même n'est pas conservé : seul son contenu
 * tabulaire l'est, le temps de la conversation.
 */
class PiecesJointes
{
    private const DUREE_SECONDES = 7200;

    /** @param array{colonnes: string[], lignes: array<int, string[]>} $tableau */
    public function garder(int $userId, string $nom, array $tableau): string
    {
        $id = (string) Str::uuid();
        Cache::put($this->cle($id), ['user_id' => $userId, 'nom' => mb_substr($nom, 0, 120)] + $tableau, self::DUREE_SECONDES);

        return $id;
    }

    /** @return array{user_id:int, nom:string, colonnes: string[], lignes: array<int, string[]>}|null */
    public function pour(int $userId, ?string $id): ?array
    {
        if (! $id || ! Str::isUuid($id)) {
            return null;
        }
        $piece = Cache::get($this->cle($id));

        // Une pièce n'est lisible que par qui l'a envoyée.
        return is_array($piece) && (int) ($piece['user_id'] ?? 0) === $userId ? $piece : null;
    }

    private function cle(string $id): string
    {
        return 'assistant.piece.' . $id;
    }
}
