<?php

namespace App\Domain\Assistant\Pieces;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Tableaux extraits des fichiers joints, gardés deux heures pour la personne qui
 * les a envoyés. Le fichier lui-même n'est pas conservé : seul son contenu
 * tabulaire, ou les octets d'une image, le sont deux heures pour l'analyse.
 */
class PiecesJointes
{
    private const DUREE_SECONDES = 7200;

    /** @param array{colonnes: string[], lignes: array<int, string[]>} $tableau */
    public function garder(int $userId, string $nom, array $tableau): string
    {
        $id = (string) Str::uuid();
        Cache::put($this->cle($id), ['type' => 'tableau', 'user_id' => $userId, 'nom' => mb_substr($nom, 0, 120)] + $tableau, self::DUREE_SECONDES);

        return $id;
    }

    /** Garde une image uniquement pour son auteur, le temps de l'échange. */
    public function garderImage(int $userId, string $nom, string $mime, string $octets): string
    {
        $id = (string) Str::uuid();
        Cache::put($this->cle($id), [
            'type' => 'image', 'user_id' => $userId, 'nom' => mb_substr($nom, 0, 120),
            'mime' => $mime, 'base64' => base64_encode($octets),
        ], self::DUREE_SECONDES);

        return $id;
    }

    /** @return array{user_id:int, nom:string, colonnes?: string[], lignes?: array<int, string[]>}|null */
    public function pour(int $userId, ?string $id): ?array
    {
        if (! $id || ! Str::isUuid($id)) {
            return null;
        }
        $piece = Cache::get($this->cle($id));

        // Une pièce n'est lisible que par qui l'a envoyée.
        return is_array($piece) && (int) ($piece['user_id'] ?? 0) === $userId ? $piece : null;
    }

    /** @return array<int, array{mime:string,base64:string,nom:string}> */
    public function imagesPour(int $userId, array $ids): array
    {
        $images = [];
        foreach (array_slice(array_unique($ids), -3) as $id) {
            $piece = $this->pour($userId, $id);
            if (($piece['type'] ?? 'tableau') !== 'image' || empty($piece['base64']) || empty($piece['mime'])) {
                continue;
            }
            $images[] = ['mime' => $piece['mime'], 'base64' => $piece['base64'], 'nom' => $piece['nom']];
        }

        return $images;
    }

    private function cle(string $id): string
    {
        return 'assistant.piece.' . $id;
    }
}
