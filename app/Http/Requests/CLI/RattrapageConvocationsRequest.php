<?php

namespace App\Http\Requests\CLI;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * `POST /api/cli/rendez-vous/rattrapage-convocations`
 * `{"execute": false, "coupure_max": null, "messages": [{"reference", "message_id", "envoye_at",
 *   "destinataire_sha256", "destinataire_domaine", "action"}]}`
 *
 * `execute` doit etre un vrai booleen JSON : une chaine « false » ne doit pas
 * declencher d'ecriture. 2000 courriels au plus par appel, identifiants
 * distincts, dates ISO 8601 pas dans le futur. Un courriel sans reference
 * n'invalide pas le lot : il est ecarte seul (`sans_reference`). Les erreurs
 * gardent l'enveloppe des routes CLI.
 */
class RattrapageConvocationsRequest extends FormRequest
{
    public const MESSAGES_MAX = 2000;

    /** `2026-09-10T09:00:00Z`, `2026-09-10T09:00:00.123+00:00` : fuseau obligatoire. */
    private const ISO_8601 = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?(Z|[+-]\d{2}:\d{2})$/';

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
            'coupure_max' => ['nullable', 'string', 'regex:'.self::ISO_8601],
            'messages' => ['required', 'array', 'max:'.self::MESSAGES_MAX],
            'messages.*' => ['array'],
            'messages.*.reference' => ['nullable', 'string', 'max:40'],
            'messages.*.message_id' => ['required', 'string', 'max:100', 'distinct'],
            'messages.*.envoye_at' => ['required', 'string', 'regex:'.self::ISO_8601, 'before_or_equal:now'],
            'messages.*.destinataire_sha256' => ['present', 'nullable', 'string', 'regex:/^[0-9a-fA-F]{64}$/'],
            'messages.*.destinataire_domaine' => ['required', 'string', 'max:255'],
            'messages.*.action' => ['required', 'in:confirme,deplace,annule'],
        ];
    }

    /** Borne superieure optionnelle de la coupure du suivi (voir ContextesRattrapage). */
    public function coupureMax(): ?\Carbon\CarbonImmutable
    {
        $valeur = $this->validated('coupure_max');

        return is_string($valeur) ? \Carbon\CarbonImmutable::parse($valeur) : null;
    }

    public function executer(): bool
    {
        return $this->json('execute') === true;
    }

    /** @return list<array<string, mixed>> */
    public function courriels(): array
    {
        return array_values($this->validated('messages'));
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
