<?php

namespace App\Casts;

use App\Enums\TypeSeance;
use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Cast tolerant pour les anciennes valeurs de type_seance (ex: "cours").
 */
class TypeSeanceCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof TypeSeance) {
            return $value;
        }

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return TypeSeance::tryFrom((string) $value)
            ?? TypeSeance::fromLegacy((string) $value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null || $value === '') {
            return TypeSeance::AUTRE->value;
        }

        if ($value instanceof TypeSeance) {
            return $value->value;
        }

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return (TypeSeance::tryFrom((string) $value)
            ?? TypeSeance::fromLegacy((string) $value))->value;
    }
}
