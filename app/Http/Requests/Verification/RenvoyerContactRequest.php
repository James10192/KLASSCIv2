<?php

namespace App\Http\Requests\Verification;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * `{"canal": "email"|"telephone", "demande_id": "..."}`. Canal absent = e-mail.
 *
 * Seul un canal inconnu est refuse (422) : c'est une erreur du site vitrine,
 * pas une information sur la demande. Un identifiant absent ou mal forme rend
 * la reponse d'une demande inconnue (202), pour ne rien reveler.
 */
class RenvoyerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['canal' => ['nullable', 'in:email,telephone']];
    }

    public function canal(): string
    {
        return (string) ($this->input('canal') ?? 'email');
    }

    /** L'identifiant recu, ou null s'il ne peut designer aucune demande. */
    public function demandeId(): ?string
    {
        $id = $this->input('demande_id');

        return is_string($id) && $id !== '' && strlen($id) <= 64 ? $id : null;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['envoye' => false, 'motif' => 'canal_invalide'], 422));
    }
}
