<?php

namespace App\Services\Portail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class ReferencePublique
{
    public const LONGUEUR = 12;

    public function assurer(Model $porteur): string
    {
        if (is_string($porteur->reference_publique) && $porteur->reference_publique !== '') {
            return $porteur->reference_publique;
        }

        for ($essai = 0; $essai < 8; $essai++) {
            $reference = self::tirer();

            try {
                $porteur->forceFill(['reference_publique' => $reference])->save();

                return $reference;
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Impossible d\'émettre une référence publique unique.');
    }

    public function formater(string $reference): string
    {
        $brut = $this->normaliser($reference);

        return $brut === '' ? '' : implode('-', str_split($brut, 4));
    }

    public function normaliser(string $reference): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $reference) ?? '');
    }

    public static function tirer(): string
    {
        return strtoupper(Str::random(self::LONGUEUR));
    }
}
