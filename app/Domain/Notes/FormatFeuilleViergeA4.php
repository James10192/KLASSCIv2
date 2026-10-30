<?php

namespace App\Domain\Notes;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Limites de lisibilité d'une feuille A4 imprimée, à typographie inchangée.
 * Un seul référentiel pour les liens UI et pour les deux endpoints PDF.
 */
final class FormatFeuilleViergeA4
{
    public const LIMITES = [
        'portrait' => 4,
        'landscape' => 8,
    ];

    /**
     * @return array{0: 'portrait'|'landscape', 1: int}
     */
    public static function depuisRequete(Request $request): array
    {
        $data = $request->validate([
            'orientation' => ['sometimes', 'string', Rule::in(array_keys(self::LIMITES))],
            'colonnes' => ['sometimes', 'integer', 'min:1', 'max:'.max(self::LIMITES)],
        ]);

        $orientation = $data['orientation'] ?? 'portrait';
        $colonnes = (int) ($data['colonnes'] ?? 1);
        if ($colonnes > self::LIMITES[$orientation]) {
            throw ValidationException::withMessages([
                'colonnes' => sprintf(
                    "Le format A4 %s accepte au maximum %d colonnes sans réduire la lisibilité.",
                    $orientation === 'portrait' ? 'portrait' : 'paysage',
                    self::LIMITES[$orientation]
                ),
            ]);
        }

        return [$orientation, $colonnes];
    }
}
