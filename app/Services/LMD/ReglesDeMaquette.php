<?php

namespace App\Services\LMD;

use App\Enums\NatureComposante;
use App\Models\ESBTPNiveauEtude;
use App\Rules\AnneeDuCycleLmd;
use Illuminate\Validation\Rule;

/**
 * Les regles de validation d'une hierarchie et d'une maquette LMD, en un seul
 * endroit : la CLI (`/api/cli/lmd/setup`, `/api/cli/lmd/import`) et Nanan
 * valident la meme chose, avec les memes bornes.
 */
final class ReglesDeMaquette
{
    /** @return array<string, mixed> */
    public static function hierarchie(): array
    {
        return [
            'domaine.name' => 'required|string|max:255',
            'domaine.code' => 'nullable|string|max:50',
            'domaine.nature' => ['nullable', Rule::in(NatureComposante::values())],
            'domaine.description' => 'nullable|string|max:1000',
            'mention.name' => 'required|string|max:255',
            'mention.code' => 'nullable|string|max:50',
            'parcours.name' => 'required|string|max:255',
            'parcours.code' => 'nullable|string|max:50',
            'parcours.credits_licence' => 'nullable|integer|min:30|max:360',
            'parcours.credits_master' => 'nullable|integer|min:30|max:240',
            'filiere.name' => 'nullable|string|max:255',
            'filiere.code' => 'nullable|string|max:50',
        ];
    }

    /** @return array<string, mixed> */
    public static function import(): array
    {
        return [
            'domaine.name' => 'required|string|max:255',
            'domaine.code' => 'nullable|string|max:50',
            'domaine.nature' => ['nullable', Rule::in(NatureComposante::values())],
            'mention.name' => 'required|string|max:255',
            'mention.code' => 'nullable|string|max:50',
            'parcours.name' => 'required|string|max:255',
            'parcours.code' => 'nullable|string|max:50',
            'parcours.credits_licence' => 'nullable|integer|min:0|max:600',
            'parcours.credits_master' => 'nullable|integer|min:0|max:600',
            'filiere.name' => 'nullable|string|max:255',
            'filiere.code' => 'nullable|string|max:50',
            'niveaux' => 'required|array|min:1',
            'niveaux.*.name' => 'required|string|max:50',
            // Le type et le code etaient lus par l'import mais absents des
            // regles, donc retires par validate() : tout niveau importe
            // devenait une Licence. L'annee doit appartenir au cycle annonce.
            'niveaux.*.type' => ['nullable', 'string', Rule::in(array_keys(ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD))],
            'niveaux.*.code' => 'nullable|string|max:50',
            'niveaux.*.libelle' => 'nullable|string|max:255',
            'niveaux.*.year' => ['required', 'integer', 'between:1,8', new AnneeDuCycleLmd()],
            'ues' => 'required|array|min:1',
            // Le tilde est reserve aux cles internes (CodeDeMaquette).
            'ues.*.code' => 'nullable|string|max:50|not_regex:/~/',
            'ues.*.name' => 'required|string|max:255',
            // L'ecole dit qu'une UE est propre a ce parcours meme si un autre
            // parcours imprime le meme code : ses UE et ECUE recoivent une cle
            // interne suffixee, le releve imprime le code tel quel.
            'ues.*.propre_au_parcours' => 'sometimes|boolean',
            'ues.*.type_ue' => 'required|string',
            'ues.*.credit' => 'required|integer|min:0|max:60',
            'ues.*.niveau_year' => 'required|integer|between:1,8',
            'ues.*.semestre' => 'required|integer|between:1,10',
            'ues.*.is_optional' => 'sometimes|boolean',
            'ues.*.ordre' => 'sometimes|integer|min:0|max:65535',
            'ues.*.ecues' => 'required|array|min:1',
            'ues.*.ecues.*.code' => 'nullable|string|max:50|not_regex:/~/',
            'ues.*.ecues.*.name' => 'required|string|max:255',
            'ues.*.ecues.*.credit_ecue' => 'required|integer|min:0|max:60',
            'ues.*.ecues.*.cm' => 'sometimes|integer|min:0|max:1000',
            'ues.*.ecues.*.td' => 'sometimes|integer|min:0|max:1000',
            'ues.*.ecues.*.tp' => 'sometimes|integer|min:0|max:1000',
            'ues.*.ecues.*.projet' => 'sometimes|integer|min:0|max:1000',
            'ues.*.ecues.*.tpe' => 'sometimes|integer|min:0|max:1000',
        ];
    }
}
