<?php

namespace App\Http\Requests\Inscription;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Candidature d'un nouvel eleve.
 *
 * Contrairement a la reinscription, l'echec de validation peut ici etre
 * EXPLICITE : il n'y a personne a identifier, donc dire « le telephone est
 * invalide » ne revele l'existence de personne. Un bachelier qui se trompe de
 * format doit pouvoir le corriger, pas deviner.
 */
class PortailCandidatureRequest extends FormRequest
{
    /** L'authentification est portee par la signature, verifiee en amont. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'date_naissance' => ['required', 'date_format:Y-m-d', 'before:today'],
            'sexe' => ['nullable', Rule::in(['M', 'F'])],

            // Le telephone est la cle d'unicite : c'est par lui que l'ecole
            // rappelle, et c'est lui qui evite les doublons dans la corbeille.
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:150'],

            // Voeu : soit un choix dans la liste publiee, soit du texte libre.
            // Un bachelier ne connait pas toujours le nom exact d'une filiere.
            'filiere_id' => ['nullable', 'integer', 'exists:esbtp_filieres,id'],
            'niveau_id' => ['nullable', 'integer', 'exists:esbtp_niveau_etudes,id'],
            'voeu_libre' => ['nullable', 'string', 'max:255'],

            'serie_bac' => ['nullable', 'string', 'max:60'],
            'etablissement_origine' => ['nullable', 'string', 'max:150'],
            'annee_bac' => ['nullable', 'integer', 'min:1980', 'max:'.(date('Y') + 1)],

            'message' => ['nullable', 'string', 'max:2000'],

            // Obligation de la loi ivoirienne 2013-450 sur les donnees a
            // caractere personnel.
            'consentement' => ['required', 'accepted'],

            // Adresse du VISITEUR, transmise dans le corps signe par le site
            // vitrine. Celle que verrait Laravel est celle du site, identique
            // pour toute l'ecole : elle ne borne rien et n'identifie rien.
            'ip_client' => ['required', 'ip'],
        ];
    }

    /**
     * Au moins un voeu, d'une facon ou d'une autre.
     *
     * Une candidature sans voeu oblige la scolarite a rappeler pour demander
     * « vous voulez faire quoi ? ». Autant le demander tout de suite.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $sansListe = ! $this->filled('filiere_id') && ! $this->filled('niveau_id');
            $sansTexte = trim((string) $this->input('voeu_libre')) === '';

            if ($sansListe && $sansTexte) {
                $validator->errors()->add('voeu_libre', 'Indiquez la filière ou la formation qui vous intéresse.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'enregistre' => false,
            'message' => 'Certaines informations sont incomplètes ou invalides.',
            'champs' => $validator->errors()->toArray(),
        ], 422));
    }
}
