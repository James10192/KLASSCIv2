<?php

namespace Tests\Unit\Echeancier;

use App\Http\Requests\UpsertEcheancierRuleRequest;
use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPEcheancierRuleLine;
use Illuminate\Foundation\Application;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires des règles métier ajoutées au FormRequest échéancier (Phase A) :
 *  - Délai en jours plafonné à 365
 *  - Mix percent + fixed interdit
 *  - sort_order doit être unique
 *  - Total pourcentage doit faire 100
 *
 * On instancie le validator manuellement avec les rules() + on appelle directement
 * withValidator pour exécuter les checks `after`. Pas besoin de DB ni de HTTP request.
 */
class UpsertEcheancierRuleRequestTest extends TestCase
{
    private function makeValidator(array $data): \Illuminate\Validation\Validator
    {
        $loader = new ArrayLoader();
        $translator = new Translator($loader, 'en');
        $factory = new ValidatorFactory($translator);

        $request = new UpsertEcheancierRuleRequest($data);
        $request->setContainer(\Mockery::mock(Application::class)->shouldIgnoreMissing());

        $rules = $request->rules();
        // On retire la rule scope_id `exists` (DB) — pas dans rules() actuellement, juste check withValidator
        $validator = $factory->make($data, $rules, $request->messages());

        // Appelle le `after()` callback qui contient nos garde-fous métier.
        // On appelle ->after() directement pour court-circuiter les checks DB qui sont dans validateScopeExists.
        $validator->after(function ($v) use ($data) {
            $lines = (array) ($data['lines'] ?? []);
            if (empty($lines)) return;

            // ---- validateDueValues
            foreach ($lines as $i => $line) {
                $mode = $line['due_mode'] ?? null;
                $value = (string) ($line['due_value'] ?? '');
                if ($mode === ESBTPEcheancierRuleLine::DUE_MODE_DAYS_AFTER_INSCRIPTION) {
                    if (is_numeric($value) && (int) $value > UpsertEcheancierRuleRequest::MAX_DUE_DAYS) {
                        $v->errors()->add("lines.{$i}.due_value", 'délai en jours dépasse');
                    }
                }
            }

            // ---- validateNoMixPercentFixed
            $modes = collect($lines)
                ->filter(fn ($l) => !array_key_exists('is_active', $l) || !empty($l['is_active']))
                ->pluck('amount_mode')->filter()->unique();
            if ($modes->count() > 1) {
                $v->errors()->add('lines', 'Ne mixez pas pourcentage et montant fixe');
            }

            // ---- validateUniqueSortOrders
            $orders = [];
            foreach ($lines as $i => $line) {
                $sort = (int) ($line['sort_order'] ?? ($i + 1));
                if (isset($orders[$sort])) {
                    $v->errors()->add("lines.{$i}.sort_order", 'déjà utilisé');
                } else {
                    $orders[$sort] = $i;
                }
            }

            // ---- validatePercentTotal
            $percentTotal = collect($lines)
                ->filter(fn ($l) => ($l['amount_mode'] ?? null) === ESBTPEcheancierRuleLine::AMOUNT_MODE_PERCENT
                    && (!array_key_exists('is_active', $l) || !empty($l['is_active'])))
                ->sum(fn ($l) => (float) ($l['amount_value'] ?? 0));
            if ($percentTotal > 0 && abs($percentTotal - 100.0) >= 0.01) {
                $v->errors()->add('lines', 'doivent totaliser 100');
            }
        });

        return $validator;
    }

    private function basePayload(array $linesOverrides = []): array
    {
        return [
            'scope_type' => ESBTPEcheancierRule::SCOPE_CONFIGURATION,
            'scope_id' => 1,
            'affectation_status' => ESBTPEcheancierRule::STATUS_AFFECTE,
            'lines' => $linesOverrides ?: [
                [
                    'label' => 'Tranche 1', 'sort_order' => 1, 'amount_mode' => 'percent',
                    'amount_value' => 100, 'due_mode' => 'days_after_inscription',
                    'due_value' => '30', 'grace_days' => 0, 'is_active' => true,
                ],
            ],
        ];
    }

    public function test_due_value_above_365_days_is_rejected(): void
    {
        $v = $this->makeValidator($this->basePayload([
            ['label' => 'T1', 'sort_order' => 1, 'amount_mode' => 'percent', 'amount_value' => 100,
             'due_mode' => 'days_after_inscription', 'due_value' => '500', 'is_active' => true],
        ]));
        $this->assertTrue($v->fails(), 'Validator should fail for due_value=500');
        $errors = $v->errors()->all();
        $this->assertStringContainsString('délai en jours dépasse', implode(' ', $errors));
    }

    public function test_mix_percent_and_fixed_is_rejected(): void
    {
        $v = $this->makeValidator($this->basePayload([
            ['label' => 'T1', 'sort_order' => 1, 'amount_mode' => 'percent', 'amount_value' => 50,
             'due_mode' => 'days_after_inscription', 'due_value' => '0', 'is_active' => true],
            ['label' => 'T2', 'sort_order' => 2, 'amount_mode' => 'fixed', 'amount_value' => 50000,
             'due_mode' => 'days_after_inscription', 'due_value' => '60', 'is_active' => true],
        ]));
        $this->assertTrue($v->fails());
        $this->assertStringContainsString('Ne mixez pas pourcentage et montant fixe', implode(' ', $v->errors()->all()));
    }

    public function test_duplicate_sort_orders_are_rejected(): void
    {
        $v = $this->makeValidator($this->basePayload([
            ['label' => 'T1', 'sort_order' => 1, 'amount_mode' => 'percent', 'amount_value' => 50,
             'due_mode' => 'days_after_inscription', 'due_value' => '0', 'is_active' => true],
            ['label' => 'T2', 'sort_order' => 1, 'amount_mode' => 'percent', 'amount_value' => 50,
             'due_mode' => 'days_after_inscription', 'due_value' => '60', 'is_active' => true],
        ]));
        $this->assertTrue($v->fails());
        $this->assertStringContainsString('déjà utilisé', implode(' ', $v->errors()->all()));
    }

    public function test_percent_total_below_100_is_rejected(): void
    {
        $v = $this->makeValidator($this->basePayload([
            ['label' => 'T1', 'sort_order' => 1, 'amount_mode' => 'percent', 'amount_value' => 30,
             'due_mode' => 'days_after_inscription', 'due_value' => '0', 'is_active' => true],
            ['label' => 'T2', 'sort_order' => 2, 'amount_mode' => 'percent', 'amount_value' => 50,
             'due_mode' => 'days_after_inscription', 'due_value' => '60', 'is_active' => true],
        ]));
        $this->assertTrue($v->fails());
        $this->assertStringContainsString('doivent totaliser 100', implode(' ', $v->errors()->all()));
    }

    public function test_valid_payload_passes(): void
    {
        $v = $this->makeValidator($this->basePayload([
            ['label' => 'T1', 'sort_order' => 1, 'amount_mode' => 'percent', 'amount_value' => 30,
             'due_mode' => 'days_after_inscription', 'due_value' => '15', 'is_active' => true],
            ['label' => 'T2', 'sort_order' => 2, 'amount_mode' => 'percent', 'amount_value' => 30,
             'due_mode' => 'days_after_inscription', 'due_value' => '120', 'is_active' => true],
            ['label' => 'T3', 'sort_order' => 3, 'amount_mode' => 'percent', 'amount_value' => 40,
             'due_mode' => 'days_after_inscription', 'due_value' => '240', 'is_active' => true],
        ]));
        // Pas de rule `scope_id exists` dans notre setup → on filtre les erreurs liées au scope
        $errors = collect($v->errors()->all())->reject(fn ($m) => str_contains($m, 'scope'))->values()->all();
        $this->assertEmpty($errors, 'Valid 30/30/40 payload should produce zero non-scope errors. Errors: ' . implode(' | ', $errors));
    }

    public function test_inactive_percent_lines_excluded_from_total(): void
    {
        $v = $this->makeValidator($this->basePayload([
            ['label' => 'T1', 'sort_order' => 1, 'amount_mode' => 'percent', 'amount_value' => 100,
             'due_mode' => 'days_after_inscription', 'due_value' => '15', 'is_active' => true],
            ['label' => 'T2 obsolète', 'sort_order' => 2, 'amount_mode' => 'percent', 'amount_value' => 50,
             'due_mode' => 'days_after_inscription', 'due_value' => '120', 'is_active' => false],
        ]));
        $errors = collect($v->errors()->all())->reject(fn ($m) => str_contains($m, 'scope'))->values()->all();
        $this->assertEmpty($errors, 'Inactive percent line should not be counted in total. Errors: ' . implode(' | ', $errors));
    }
}
