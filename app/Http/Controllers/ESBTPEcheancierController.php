<?php

namespace App\Http\Controllers;

use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPEcheancierRuleLine;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPOptionAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ESBTPEcheancierController extends Controller
{
    public function index(Request $request)
    {
        $selectedScopeType = $request->query('scope_type');
        $selectedScopeId = $request->query('scope_id');
        $selectedStatus = ESBTPEcheancierRule::normalizeStatus($request->query('affectation_status', ESBTPEcheancierRule::STATUS_ALL));

        if ((!$selectedScopeType || !$selectedScopeId)
            && $request->filled(['filiere_id', 'niveau_id', 'frais_category_id'])) {
            $configuration = ESBTPFraisConfiguration::query()
                ->where('filiere_id', (int) $request->query('filiere_id'))
                ->where('niveau_id', (int) $request->query('niveau_id'))
                ->where('frais_category_id', (int) $request->query('frais_category_id'))
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();

            if ($configuration) {
                $selectedScopeType = ESBTPEcheancierRule::SCOPE_CONFIGURATION;
                $selectedScopeId = (string) $configuration->id;
            }
        }

        $configurations = ESBTPFraisConfiguration::query()
            ->with(['fraisCategory', 'filiere', 'niveau'])
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->limit(300)
            ->get();

        $optionAssignments = ESBTPOptionAssignment::query()
            ->with(['option.fraisCategory', 'filiere', 'niveau'])
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->limit(300)
            ->get();

        $scopeFilters = [];
        foreach ($configurations as $configuration) {
            $scopeFilters[] = [
                'scope_type' => ESBTPEcheancierRule::SCOPE_CONFIGURATION,
                'scope_id' => (int) $configuration->id,
            ];
        }
        foreach ($optionAssignments as $assignment) {
            $scopeFilters[] = [
                'scope_type' => ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT,
                'scope_id' => (int) $assignment->id,
            ];
        }

        $rules = ESBTPEcheancierRule::query()
            ->with(['lines' => fn ($q) => $q->orderBy('sort_order')])
            ->when(!empty($scopeFilters), function ($query) use ($scopeFilters) {
                $query->where(function ($q) use ($scopeFilters) {
                    foreach ($scopeFilters as $filter) {
                        $q->orWhere(function ($sub) use ($filter) {
                            $sub->where('scope_type', $filter['scope_type'])
                                ->where('scope_id', $filter['scope_id']);
                        });
                    }
                });
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->get();

        $rulesByScope = $rules->groupBy(fn ($rule) => $rule->scope_type . ':' . $rule->scope_id);

        $selectedRule = null;
        $selectedScopeDescriptor = null;

        if ($selectedScopeType && $selectedScopeId && $this->scopeExists($selectedScopeType, (int) $selectedScopeId)) {
            $selectedScopeDescriptor = $this->buildScopeDescriptor($selectedScopeType, (int) $selectedScopeId);

            $selectedRule = ESBTPEcheancierRule::query()
                ->with(['lines' => fn ($q) => $q->orderBy('sort_order')])
                ->where('scope_type', $selectedScopeType)
                ->where('scope_id', (int) $selectedScopeId)
                ->where('affectation_status', $selectedStatus)
                ->first();
        }

        return view('esbtp.comptabilite.echeanciers.index', [
            'configurations' => $configurations,
            'optionAssignments' => $optionAssignments,
            'rulesByScope' => $rulesByScope,
            'selectedScopeType' => $selectedScopeType,
            'selectedScopeId' => $selectedScopeId,
            'selectedStatus' => $selectedStatus,
            'selectedRule' => $selectedRule,
            'selectedScopeDescriptor' => $selectedScopeDescriptor,
        ]);
    }

    public function upsert(Request $request)
    {
        $validated = $request->validate([
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
            'priority' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.label' => ['required', 'string', 'max:120'],
            'lines.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:99'],
            'lines.*.amount_mode' => ['required', Rule::in([
                ESBTPEcheancierRuleLine::AMOUNT_MODE_PERCENT,
                ESBTPEcheancierRuleLine::AMOUNT_MODE_FIXED,
            ])],
            'lines.*.amount_value' => ['required', 'numeric', 'min:0'],
            'lines.*.due_mode' => ['required', Rule::in([
                ESBTPEcheancierRuleLine::DUE_MODE_DAYS_AFTER_INSCRIPTION,
                ESBTPEcheancierRuleLine::DUE_MODE_FIXED_MM_DD,
            ])],
            'lines.*.due_value' => ['required', 'string', 'max:20'],
            'lines.*.grace_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'lines.*.is_active' => ['nullable', 'boolean'],
        ]);

        $scopeType = $validated['scope_type'];
        $scopeId = (int) $validated['scope_id'];
        if (!$this->scopeExists($scopeType, $scopeId)) {
            return redirect()->back()->withErrors([
                'scope_id' => 'Le scope sélectionné est introuvable.',
            ])->withInput();
        }

        foreach ($validated['lines'] as $index => $line) {
            if (
                $line['due_mode'] === ESBTPEcheancierRuleLine::DUE_MODE_FIXED_MM_DD
                && !preg_match('/^(\d{2})-(\d{2})$/', $line['due_value'])
            ) {
                return redirect()->back()->withErrors([
                    'lines.' . $index . '.due_value' => 'Format attendu pour date fixe: MM-DD (ex: 10-15).',
                ])->withInput();
            }

            if ($line['due_mode'] === ESBTPEcheancierRuleLine::DUE_MODE_DAYS_AFTER_INSCRIPTION && !is_numeric($line['due_value'])) {
                return redirect()->back()->withErrors([
                    'lines.' . $index . '.due_value' => 'Pour un délai en jours, entrez une valeur numérique.',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($validated, $scopeType, $scopeId) {
            $rule = ESBTPEcheancierRule::updateOrCreate(
                [
                    'scope_type' => $scopeType,
                    'scope_id' => $scopeId,
                    'affectation_status' => $validated['affectation_status'],
                ],
                [
                    'priority' => (int) ($validated['priority'] ?? 100),
                    'is_active' => (bool) ($validated['is_active'] ?? false),
                    'effective_from' => $validated['effective_from'] ?? null,
                    'effective_to' => $validated['effective_to'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => auth()->id(),
                    'created_by' => auth()->id(),
                ]
            );

            $rule->lines()->delete();

            foreach ($validated['lines'] as $index => $line) {
                $rule->lines()->create([
                    'label' => trim($line['label']),
                    'sort_order' => (int) ($line['sort_order'] ?? ($index + 1)),
                    'amount_mode' => $line['amount_mode'],
                    'amount_value' => round((float) $line['amount_value'], 2),
                    'due_mode' => $line['due_mode'],
                    'due_value' => trim((string) $line['due_value']),
                    'grace_days' => (int) ($line['grace_days'] ?? 0),
                    'is_active' => (bool) ($line['is_active'] ?? true),
                ]);
            }
        });

        return redirect()->route('esbtp.comptabilite.echeanciers.index', [
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'affectation_status' => $validated['affectation_status'],
        ])->with('success', 'Règle d\'échéancier enregistrée avec succès.');
    }

    private function scopeExists(string $scopeType, int $scopeId): bool
    {
        if ($scopeType === ESBTPEcheancierRule::SCOPE_CONFIGURATION) {
            return ESBTPFraisConfiguration::whereKey($scopeId)->exists();
        }

        if ($scopeType === ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT) {
            return ESBTPOptionAssignment::whereKey($scopeId)->exists();
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildScopeDescriptor(string $scopeType, int $scopeId): ?array
    {
        if ($scopeType === ESBTPEcheancierRule::SCOPE_CONFIGURATION) {
            $configuration = ESBTPFraisConfiguration::with(['fraisCategory', 'filiere', 'niveau'])->find($scopeId);
            if (!$configuration) {
                return null;
            }

            return [
                'title' => 'Configuration obligatoire',
                'subtitle' => ($configuration->fraisCategory->name ?? 'Frais') . ' - ' . ($configuration->filiere->name ?? 'N/A') . ' / ' . ($configuration->niveau->name ?? 'N/A'),
            ];
        }

        if ($scopeType === ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT) {
            $assignment = ESBTPOptionAssignment::with(['option.fraisCategory', 'filiere', 'niveau'])->find($scopeId);
            if (!$assignment) {
                return null;
            }

            return [
                'title' => 'Assignation optionnelle',
                'subtitle' => ($assignment->option->fraisCategory->name ?? 'Option') . ' - ' . ($assignment->option->name ?? 'N/A') . ' (' . $assignment->display_label . ')',
            ];
        }

        return null;
    }
}
