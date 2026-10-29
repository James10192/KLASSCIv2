<?php

namespace App\Http\Requests\Reinscription;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Requete de consultation du portail public, et socle du depot.
 *
 * L'echec de validation rend un corps volontairement laconique : « requete
 * invalide », sans dire quel champ. Ce n'est pas la meme reponse que celle
 * d'une identification infructueuse — la validation ne depend d'aucune donnee
 * etudiante, donc elle ne peut rien reveler — mais detailler l'erreur
 * renseignerait un appelant qui sonde le contrat.
 */
class PortailRequest extends FormRequest
{
    /** L'authentification est portee par la signature, verifiee en amont. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'matricule' => ['required', 'string', 'max:50'],
            // `date` accepterait « now » ou « +1 week ». Sur la moitie d'un
            // facteur d'identification, le format exact est le contrat.
            'date_naissance' => ['required', 'date_format:Y-m-d'],
            // Adresse du VISITEUR, transmise par le site vitrine dans le corps
            // signe. Celle que verrait Laravel est celle du site, identique
            // pour toute l'ecole : elle ne borne rien et n'identifie rien.
            'ip_client' => ['required', 'ip'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'trouve' => false,
            'message' => 'Requête invalide.',
        ], 422));
    }
}
