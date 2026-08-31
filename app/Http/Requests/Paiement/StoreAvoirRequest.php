<?php

namespace App\Http\Requests\Paiement;

use App\Services\AvoirService;
use Illuminate\Foundation\Http\FormRequest;

class StoreAvoirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('paiements.avoir') ?? false;
    }

    public function rules(): array
    {
        return [
            'montant' => 'required|numeric|min:1',
            'avoir_kind' => 'required|in:'.AvoirService::KIND_CREDIT.','.AvoirService::KIND_REFUND,
            'motif' => 'required|string|min:5|max:500',
        ];
    }
}
