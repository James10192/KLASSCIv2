<?php

namespace App\Http\Requests\CLI;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * `POST /api/cli/emails/corriger-fautes`
 * `{"execute": false, "inclure_comptes": false, "inclure_probables": false,
 *   "corrections": [{"cle": "esbtp_candidatures:email:42", "domaine_propose": "gmail.com", "domaine_actuel": "gmai.com"}]}`
 *
 * `execute`, `inclure_comptes`, `inclure_probables` : vrais booleens JSON s'ils sont
 * fournis. `corrections` n'est exige qu'en execution. Les erreurs gardent
 * l'enveloppe des routes CLI.
 */
class CorrigerFautesRequest extends FormRequest
{
    public const CORRECTIONS_MAX = 500;

    public function authorize(): bool
    {
        return (bool) $this->user()?->tokenCan('cli:admin');
    }

    public function rules(): array
    {
        $booleen = fn (string $champ) => function (string $attribut, mixed $valeur, \Closure $echec) use ($champ) {
            if (! is_bool($this->json($champ))) {
                $echec(sprintf('« %s » doit être un booléen JSON (true ou false).', $champ));
            }
        };

        return [
            'execute' => [$booleen('execute')],
            'inclure_comptes' => [$booleen('inclure_comptes')],
            'inclure_probables' => [$booleen('inclure_probables')],
            'corrections' => ['required_if:execute,true', 'array', 'max:'.self::CORRECTIONS_MAX],
            'corrections.*.cle' => ['required', 'string', 'max:120', 'distinct'],
            'corrections.*.domaine_propose' => ['required', 'string', 'max:255'],
            'corrections.*.domaine_actuel' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function executer(): bool
    {
        return $this->json('execute') === true;
    }

    public function inclureComptes(): bool
    {
        return $this->json('inclure_comptes') === true;
    }

    /** Fautes probables (distance d'edition) : jamais sans demande expresse. */
    public function inclureProbables(): bool
    {
        return $this->json('inclure_probables') === true;
    }

    /** @return list<array{cle: string, domaine_propose: string, domaine_actuel?: ?string}> */
    public function corrections(): array
    {
        return array_values($this->validated('corrections') ?? []);
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
