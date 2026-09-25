<?php

namespace App\Http\Requests\CLI;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * `POST /api/cli/rendez-vous/convocations/renvoyer`
 * `{"execute": false, "reservations": [12, 34], "motif": "adresse_corrigee"}`
 *
 * 1 a 50 identifiants distincts : un renvoi cible, jamais toute l'ecole.
 * `execute` doit etre un vrai booleen JSON s'il est fourni. Les erreurs
 * gardent l'enveloppe des routes CLI.
 */
class RenvoyerConvocationsRequest extends FormRequest
{
    public const MAX = 50;

    public const MOTIFS = ['adresse_corrigee', 'domaine_piege', 'demande_famille'];

    public function authorize(): bool
    {
        return (bool) $this->user()?->tokenCan('cli:admin');
    }

    public function rules(): array
    {
        return [
            'execute' => [function (string $attribut, mixed $valeur, \Closure $echec) {
                if (! is_bool($this->json('execute'))) {
                    $echec('« execute » doit être un booléen JSON (true ou false).');
                }
            }],
            'reservations' => ['required', 'array', 'min:1', 'max:'.self::MAX],
            'reservations.*' => ['required', 'integer', 'min:1', 'distinct'],
            'motif' => ['required', 'string', 'in:'.implode(',', self::MOTIFS)],
        ];
    }

    public function executer(): bool
    {
        return $this->json('execute') === true;
    }

    /** @return list<int> */
    public function reservations(): array
    {
        return array_values(array_map('intval', $this->validated('reservations')));
    }

    public function motif(): string
    {
        return (string) $this->validated('motif');
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Token missing cli:admin ability',
        ], 403));
    }
}
