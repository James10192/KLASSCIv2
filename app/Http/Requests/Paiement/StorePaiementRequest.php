<?php

namespace App\Http\Requests\Paiement;

use App\Helpers\SettingsHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inscription_id' => 'required|exists:esbtp_inscriptions,id',
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'frais_category_id' => 'required|exists:esbtp_frais_categories,id',
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|string',
            'reference_paiement' => 'nullable|string',
            'tranche' => 'nullable|string',
            'commentaire' => 'nullable|string',
            'confirmed_unusual_amount' => 'nullable|in:0,1',
            'confirmed_zero_amount' => 'nullable|in:0,1',
        ];
    }

    /**
     * QW3 — défense en profondeur backend : si montant > seuil tenant
     * ET pas de confirmation explicite, on refuse le paiement.
     *
     * Le seuil 'comptabilite.unusual_amount_threshold' est configurable par l'école
     * via /esbtp/settings (default 500 000 FCFA).
     *
     * Audit 2026-06-04 §2.13 : on ajoute aussi un garde-fou montant=0 qui exige
     * une confirmation explicite (cas exonération uniquement). Empêche les 18
     * paiements zéro accidentels constatés en prod yakro.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $montant = (int) $this->input('montant', 0);
            $threshold = (int) SettingsHelper::get('comptabilite.unusual_amount_threshold', 500000);
            $confirmed = $this->input('confirmed_unusual_amount') === '1' || $this->input('confirmed_unusual_amount') === 1;

            if ($montant > $threshold && !$confirmed) {
                $v->errors()->add(
                    'montant',
                    sprintf(
                        'Montant inhabituel détecté (%s FCFA, supérieur au seuil de %s FCFA configuré). Cochez la case de confirmation pour valider ce paiement.',
                        number_format($montant, 0, ',', ' '),
                        number_format($threshold, 0, ',', ' '),
                    ),
                );
            }

            // Garde-fou paiement 0 FCFA — typique d'une erreur de saisie.
            $confirmedZero = $this->input('confirmed_zero_amount') === '1' || $this->input('confirmed_zero_amount') === 1;
            if ($montant === 0 && !$confirmedZero) {
                $v->errors()->add(
                    'montant',
                    'Montant à 0 FCFA détecté. Ce paiement ne sera enregistré que si vous confirmez qu\'il s\'agit d\'une exonération (cochez la case dédiée).',
                );
            }
        });
    }
}
