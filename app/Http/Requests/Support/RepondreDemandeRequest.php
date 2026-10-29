<?php

namespace App\Http\Requests\Support;

use App\Services\Care\ClientMasterSupport;
use Illuminate\Foundation\Http\FormRequest;

class RepondreDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Les limites du Master : la reponse a son propre minimum, plus court
        // qu'un signalement (« Merci, c'est reglé. » suffit).
        $l = app(ClientMasterSupport::class)->limites();

        return [
            'corps' => ['required', 'string', 'min:'.$l['reponse_min'], 'max:'.$l['description_max']],
            'cle' => ['required', 'string', 'regex:/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'],
        ];
    }

    public function messages(): array
    {
        return [
            'corps.required' => 'Écrivez votre réponse.',
            'corps.min' => 'Votre réponse est trop courte.',
            'corps.max' => 'Votre réponse dépasse :max caractères.',
        ];
    }
}
