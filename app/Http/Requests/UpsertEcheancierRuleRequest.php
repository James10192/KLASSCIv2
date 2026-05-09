<?php

namespace App\Http\Requests;

use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPEcheancierRuleLine;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPOptionAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation centralisée des règles d'échéancier (extraction du controller god méthode).
 * Garde-fous métier ajoutés au-delà de la validation Laravel basique :
 *  - délai en jours plafonné à 365
 *  - mix percent + fixed interdit dans la même règle
 *  - sort_order unique
 *  - total des pourcentages = 100 (déjà fait avant, conservé)
 *  - format MM-DD pour date fixe
 *  - confirm_overwrite : flag optionnel pour valider l'écrasement d'une règle existante
 */
class UpsertEcheancierRuleRequest extends FormRequest
{
    public const MAX_DUE_DAYS = 365;

    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('comptabilite.frais.configure');
    }

    public function rules(): array
    {
        return [
            'scope_type' => ['required', Rule::in([
                ESBTPEcheancierRule::SCOPE_CONFIGURATION,
                ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT,
            ])],
            'scope_id' => ['required', 'integer', 'min:1'],
            'affectation_status' => ['required', Rule::in([
                ESBTPEcheancierRule::STATUS_ALL,
                ESBTPEcheancierRule::STATUS_AFFECTE,
                ESBTPEcheancierRule::STATUS_REAFFECTE,
                ESBTPEcheancierRule::STATUS_NON_AFFECTE,
            ])],
            'priority'        => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active'       => ['nullable', 'boolean'],
            'effective_from'  => ['nullable', 'date'],
            'effective_to'    => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes'           => ['nullable', 'string', 'max:2000'],
            'confirm_overwrite' => ['nullable', 'boolean'],

            'lines'                       => ['required', 'array', 'min:1', 'max:24'],
            'lines.*.label'               => ['required', 'string', 'max:120'],
            'lines.*.sort_order'          => ['nullable', 'integer', 'min:1', 'max:99'],
            'lines.*.amount_mode'         => ['required', Rule::in([
                ESBTPEcheancierRuleLine::AMOUNT_MODE_PERCENT,
                ESBTPEcheancierRuleLine::AMOUNT_MODE_FIXED,
            ])],
            'lines.*.amount_value'        => ['required', 'numeric', 'min:0'],
            'lines.*.due_mode'            => ['required', Rule::in([
                ESBTPEcheancierRuleLine::DUE_MODE_DAYS_AFTER_INSCRIPTION,
                ESBTPEcheancierRuleLine::DUE_MODE_FIXED_MM_DD,
            ])],
            'lines.*.due_value'           => ['required', 'string', 'max:20'],
            'lines.*.grace_days'          => ['nullable', 'integer', 'min:0', 'max:365'],
            'lines.*.is_active'           => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $lines = (array) $this->input('lines', []);
            if (empty($lines)) return;

            $this->validateScopeExists($v);
            $this->validateDueValues($v, $lines);
            $this->validateNoMixPercentFixed($v, $lines);
            $this->validateUniqueSortOrders($v, $lines);
            $this->validatePercentTotal($v, $lines);
            $this->validateMaxLinesPerScope($v, $lines);
        });
    }

    private function validateScopeExists(Validator $v): void
    {
        $type = $this->input('scope_type');
        $id   = (int) $this->input('scope_id');
        $exists = match ($type) {
            ESBTPEcheancierRule::SCOPE_CONFIGURATION    => ESBTPFraisConfiguration::whereKey($id)->exists(),
            ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT => ESBTPOptionAssignment::whereKey($id)->exists(),
            default                                      => false,
        };
        if (!$exists) {
            $v->errors()->add('scope_id', 'Le scope sélectionné est introuvable.');
        }
    }

    private function validateDueValues(Validator $v, array $lines): void
    {
        foreach ($lines as $i => $line) {
            $mode  = $line['due_mode']  ?? null;
            $value = (string) ($line['due_value'] ?? '');

            if ($mode === ESBTPEcheancierRuleLine::DUE_MODE_FIXED_MM_DD
                && !preg_match('/^(\d{2})-(\d{2})$/', $value)) {
                $v->errors()->add("lines.{$i}.due_value", 'Format attendu pour date fixe : MM-DD (ex: 10-15).');
                continue;
            }

            if ($mode === ESBTPEcheancierRuleLine::DUE_MODE_DAYS_AFTER_INSCRIPTION) {
                if (!is_numeric($value)) {
                    $v->errors()->add("lines.{$i}.due_value", 'Pour un délai en jours, entrez une valeur numérique.');
                    continue;
                }
                $days = (int) $value;
                if ($days < 0) {
                    $v->errors()->add("lines.{$i}.due_value", 'Le délai en jours doit être positif.');
                }
                if ($days > self::MAX_DUE_DAYS) {
                    $v->errors()->add("lines.{$i}.due_value", sprintf(
                        'Le délai en jours dépasse %d (1 an). Vérifiez la valeur — au-delà la tranche tomberait l\'année universitaire suivante.',
                        self::MAX_DUE_DAYS
                    ));
                }
            }
        }
    }

    /**
     * Mix percent + fixed dans la même règle = lecture du total ininterprétable.
     * Soit toutes les lignes sont en pourcentage, soit toutes en montant fixe.
     */
    private function validateNoMixPercentFixed(Validator $v, array $lines): void
    {
        $modes = collect($lines)
            ->filter(fn ($line) => !empty($line['is_active']) || !array_key_exists('is_active', $line))
            ->pluck('amount_mode')
            ->filter()
            ->unique();
        if ($modes->count() > 1) {
            $v->errors()->add('lines', 'Ne mixez pas pourcentage et montant fixe dans la même règle. Choisissez l\'un ou l\'autre.');
        }
    }

    private function validateUniqueSortOrders(Validator $v, array $lines): void
    {
        $orders = [];
        foreach ($lines as $i => $line) {
            $sort = (int) ($line['sort_order'] ?? ($i + 1));
            if (isset($orders[$sort])) {
                $v->errors()->add("lines.{$i}.sort_order", "Ordre {$sort} déjà utilisé sur la tranche " . ($orders[$sort] + 1) . '.');
            } else {
                $orders[$sort] = $i;
            }
        }
    }

    private function validatePercentTotal(Validator $v, array $lines): void
    {
        $percentTotal = collect($lines)
            ->filter(fn ($line) => ($line['amount_mode'] ?? null) === ESBTPEcheancierRuleLine::AMOUNT_MODE_PERCENT
                && (!array_key_exists('is_active', $line) || !empty($line['is_active'])))
            ->sum(fn ($line) => (float) ($line['amount_value'] ?? 0));

        if ($percentTotal > 0 && abs($percentTotal - 100.0) >= 0.01) {
            $v->errors()->add('lines', sprintf(
                'Les tranches actives en pourcentage doivent totaliser 100 %% (actuellement : %.2f %%).',
                $percentTotal
            ));
        }
    }

    private function validateMaxLinesPerScope(Validator $v, array $lines): void
    {
        // 24 max via rules() ; ici on warn si > 8 (pas bloquant — bonne pratique)
        // Pas d'erreur, juste un signal pour le contrôleur via session après save (cf. Phase B UI).
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Au moins une tranche est obligatoire.',
            'lines.max'      => 'Trop de tranches (24 maximum). Au-delà la lecture devient illisible.',
        ];
    }
}
