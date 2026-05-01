<?php

namespace App\DTOs\Comptabilite;

use Illuminate\Http\Request;

final class ComptabiliteFilters
{
    public function __construct(
        public readonly ?int $anneeId,
        public readonly ?int $filiereId,
        public readonly ?int $classeId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            anneeId: self::nullableInt($request->get('annee')),
            filiereId: self::nullableInt($request->get('filiere')),
            classeId: self::nullableInt($request->get('classe')),
        );
    }

    public static function empty(): self
    {
        return new self(anneeId: null, filiereId: null, classeId: null);
    }

    private static function nullableInt(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
