<?php

namespace App\Http\Requests\Reconciliation;

use App\Enums\ModePaiement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveDiscrepancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('comptabilite.reconciliation.resolve') ?? false;
    }

    public function rules(): array
    {
        return [
            'resolution_type' => ['required', Rule::in(['adjust_payment', 'create_corrective', 'cancel_payment', 'no_action'])],
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
            'payload' => ['nullable', 'array'],
            // Ecart auto-detecte : DetectDiscrepancies ne lie aucun paiement, c'est
            // l'ecran qui designe celui a corriger (cf. ResolveDiscrepancy::paiementCible).
            'payload.paiement_id' => ['nullable', 'integer', 'exists:esbtp_paiements,id'],
            'payload.montant' => ['nullable', 'numeric', 'min:0'],
            'payload.mode_paiement' => ['nullable', Rule::in(ModePaiement::values())],
            'payload.date_paiement' => ['nullable', 'date'],
            'payload.motif' => ['nullable', 'string', 'max:255'],
            'payload.etudiant_id' => ['nullable', 'integer', 'exists:esbtp_etudiants,id'],
            'payload.inscription_id' => ['nullable', 'integer', 'exists:esbtp_inscriptions,id'],
            'payload.annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annees_universitaires,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.min' => 'Le motif de résolution doit faire au moins 10 caractères.',
        ];
    }
}
