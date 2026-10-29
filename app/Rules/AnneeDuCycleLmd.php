<?php

namespace App\Rules;

use App\Models\ESBTPNiveauEtude;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

/**
 * L'annee d'un niveau LMD doit appartenir au cycle saisi a cote : l'annee se
 * compte depuis la Licence, un Master 1 est en annee 4.
 *
 * Saisi en annee 1, un Master etait range en S1-S2 et recevait les unites de
 * Licence sans qu'aucune erreur ne le signale.
 *
 * Le type se lit dans le champ voisin : `type` pour un formulaire, la meme
 * ligne pour un tableau (`niveaux.3.year` → `niveaux.3.type`). Sans effet hors
 * LMD.
 *
 * Usage : 'niveau' => ['required', 'integer', new AnneeDuCycleLmd()]
 */
class AnneeDuCycleLmd implements Rule, DataAwareRule
{
    private array $donnees = [];

    private string $message = '';

    public function __construct(private string $champType = 'type') {}

    public function setData($data): static
    {
        $this->donnees = $data;

        return $this;
    }

    public function passes($attribute, $value): bool
    {
        $prefixe = str_contains($attribute, '.') ? substr($attribute, 0, strrpos($attribute, '.') + 1) : '';
        $type = (string) Arr::get($this->donnees, $prefixe.$this->champType);
        $annees = ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD[$type] ?? null;

        if ($annees === null || in_array((int) $value, $annees, true)) {
            return true;
        }

        $this->message = sprintf(
            "Un niveau %s porte l'année %s : l'année se compte depuis la Licence (Licence 1 à 3 = années 1 à 3, Master 1 et 2 = années 4 et 5, Doctorat = années 6 à 8).",
            $type,
            implode(' ou ', $annees)
        );

        return false;
    }

    public function message(): string
    {
        return $this->message;
    }
}
