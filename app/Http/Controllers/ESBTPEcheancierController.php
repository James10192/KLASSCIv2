<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertEcheancierRuleRequest;
use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPEcheancierRuleLine;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPOptionAssignment;
use App\Services\EcheancierAdminService;
use App\Services\RelanceCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ESBTPEcheancierController extends Controller
{
    public function index(Request $request, EcheancierAdminService $echeancierAdmin)
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
            ->with(['option.fraisCategory', 'option.configuration.fraisCategory', 'filiere', 'niveau'])
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
        $scopeDiagnostics = $echeancierAdmin->buildDiagnostics($configurations, $optionAssignments, $rulesByScope);

        $selectedRule = null;
        $selectedScopeDescriptor = null;
        $selectedPreviewAmount = 150000.0;
        $selectedAffectedCount = 0;
        $selectedScopeStatusesActive = [];

        if ($selectedScopeType && $selectedScopeId && $this->scopeExists($selectedScopeType, (int) $selectedScopeId)) {
            $selectedScopeDescriptor = $this->buildScopeDescriptor($selectedScopeType, (int) $selectedScopeId);
            $selectedPreviewAmount = $echeancierAdmin->resolvePreviewAmount($selectedScopeType, (int) $selectedScopeId, $selectedStatus);
            $selectedAffectedCount = $echeancierAdmin->countAffectedInscriptions($selectedScopeType, (int) $selectedScopeId, $selectedStatus);
            $selectedScopeStatusesActive = $echeancierAdmin->activeStatusesForScope($selectedScopeType, (int) $selectedScopeId);

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
            'selectedPreviewAmount' => $selectedPreviewAmount,
            'selectedAffectedCount' => $selectedAffectedCount,
            'selectedScopeStatusesActive' => $selectedScopeStatusesActive,
            'scopeDiagnostics' => $scopeDiagnostics,
            'filieres' => ESBTPFiliere::orderBy('name')->get(['id', 'name']),
            'niveaux' => ESBTPNiveauEtude::orderBy('name')->get(['id', 'name']),
            'fraisCategories' => ESBTPFraisCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function upsert(UpsertEcheancierRuleRequest $request, EcheancierAdminService $echeancierAdmin)
    {
        $validated = $request->validated();
        $scopeType = $validated['scope_type'];
        $scopeId   = (int) $validated['scope_id'];
        $status    = $validated['affectation_status'];
        $confirmOverwrite = (bool) ($validated['confirm_overwrite'] ?? false);

        // Garde-fou overwrite : si une règle ACTIVE existe déjà sur ce scope+statut et que
        // l'utilisateur n'a pas confirmé, on retourne avec un drapeau pour afficher le modal.
        $existingActive = ESBTPEcheancierRule::query()
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('affectation_status', $status)
            ->where('is_active', true)
            ->first();

        if ($existingActive && !$confirmOverwrite) {
            $existingLinesCount = $existingActive->lines()->count();
            return redirect()->back()
                ->with('overwrite_warning', [
                    'scope_type'        => $scopeType,
                    'scope_id'          => $scopeId,
                    'affectation_status' => $status,
                    'existing_lines'    => $existingLinesCount,
                    'updated_at'        => $existingActive->updated_at?->format('d/m/Y H:i'),
                ])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $scopeType, $scopeId, $status) {
            $rule = ESBTPEcheancierRule::updateOrCreate(
                [
                    'scope_type'        => $scopeType,
                    'scope_id'          => $scopeId,
                    'affectation_status' => $status,
                ],
                [
                    'priority'       => (int) ($validated['priority'] ?? 100),
                    'is_active'      => (bool) ($validated['is_active'] ?? false),
                    'effective_from' => $validated['effective_from'] ?? null,
                    'effective_to'   => $validated['effective_to'] ?? null,
                    'notes'          => $validated['notes'] ?? null,
                    'updated_by'     => auth()->id(),
                    'created_by'     => auth()->id(),
                ]
            );

            $rule->lines()->delete();

            foreach ($validated['lines'] as $index => $line) {
                $rule->lines()->create([
                    'label'        => trim($line['label']),
                    'sort_order'   => (int) ($line['sort_order'] ?? ($index + 1)),
                    'amount_mode'  => $line['amount_mode'],
                    'amount_value' => round((float) $line['amount_value'], 2),
                    'due_mode'     => $line['due_mode'],
                    'due_value'    => trim((string) $line['due_value']),
                    'grace_days'   => (int) ($line['grace_days'] ?? 0),
                    'is_active'    => (bool) ($line['is_active'] ?? true),
                ]);
            }

            Log::info('echeancier rule upserted', [
                'rule_id'       => $rule->id,
                'scope_type'    => $scopeType,
                'scope_id'      => $scopeId,
                'status'        => $status,
                'lines_count'   => count($validated['lines']),
                'is_active'     => $rule->is_active,
                'user_id'       => auth()->id(),
                'overwrite'     => false,
            ]);
        });

        $echeancierAdmin->forgetAffectedCache($scopeType, $scopeId, $status);

        return redirect()->route('esbtp.comptabilite.echeanciers.index', [
            'scope_type'        => $scopeType,
            'scope_id'          => $scopeId,
            'affectation_status' => $status,
        ])->with('success', 'Règle d\'échéancier enregistrée avec succès.');
    }

    public function copy(Request $request, EcheancierAdminService $echeancierAdmin)
    {
        $validated = $request->validate([
            'source_scope_type' => ['required', Rule::in([
                ESBTPEcheancierRule::SCOPE_CONFIGURATION,
                ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT,
            ])],
            'source_scope_id' => ['required', 'integer', 'min:1'],
            'affectation_status' => ['required', Rule::in([
                ESBTPEcheancierRule::STATUS_ALL,
                ESBTPEcheancierRule::STATUS_AFFECTE,
                ESBTPEcheancierRule::STATUS_REAFFECTE,
                ESBTPEcheancierRule::STATUS_NON_AFFECTE,
            ])],
            'copy_mode' => ['required', Rule::in(['same_filiere', 'same_niveau', 'all_unconfigured'])],
        ]);

        $sourceRule = ESBTPEcheancierRule::query()
            ->with(['lines' => fn ($q) => $q->orderBy('sort_order')])
            ->where('scope_type', $validated['source_scope_type'])
            ->where('scope_id', (int) $validated['source_scope_id'])
            ->where('affectation_status', $validated['affectation_status'])
            ->firstOrFail();

        $copied = $echeancierAdmin->copyRule($sourceRule, $validated['copy_mode'], (int) $validated['source_scope_id'], auth()->id());

        // copyRule duplique vers d'autres scopes — invalider le cache de la source au moins,
        // et idéalement de tous les targets (mais copyRule ne renvoie pas leur liste actuellement).
        $echeancierAdmin->forgetAffectedCache($validated['source_scope_type'], (int) $validated['source_scope_id'], $validated['affectation_status']);

        return redirect()->route('esbtp.comptabilite.echeanciers.index', [
            'scope_type' => $validated['source_scope_type'],
            'scope_id' => $validated['source_scope_id'],
            'affectation_status' => $validated['affectation_status'],
        ])->with('success', "{$copied} regle(s) copiee(s).");
    }

    public function simulate(Request $request, RelanceCalculationService $relances)
    {
        $validated = $request->validate([
            'inscription_id' => ['required', 'integer', 'min:1'],
        ]);

        $inscription = ESBTPInscription::with([
            'etudiant:id,nom,prenoms',
            'classe:id,name',
            'fraisSubscriptions.selectedOption.assignments',
            'paiements' => fn ($q) => $q->whereIn('status', ['validé', 'en_attente'])->whereNull('deleted_at'),
        ])->findOrFail((int) $validated['inscription_id']);

        $relances->preloadForSingle($inscription);
        $row = $relances->buildRow($inscription);

        return response()->json([
            'success' => true,
            'student' => trim(($inscription->etudiant?->prenoms ?? '') . ' ' . ($inscription->etudiant?->nom ?? '')),
            'classe' => $inscription->classe?->name,
            'total_due' => $row->totalDu,
            'remaining_total' => $row->remainingTotal,
            'expected_due_to_date' => $row->expectedDueToDate,
            'paid_due_to_date' => $row->paidDueToDate,
            'overdue_amount' => $row->overdueAmount,
            'overdue_days' => $row->overdueDays,
            'risk_label' => $row->riskLabel,
        ]);
    }

    public function bulkStatus(Request $request, EcheancierAdminService $echeancierAdmin)
    {
        $validated = $request->validate([
            'affectation_status' => ['required', Rule::in([
                ESBTPEcheancierRule::STATUS_ALL,
                ESBTPEcheancierRule::STATUS_AFFECTE,
                ESBTPEcheancierRule::STATUS_REAFFECTE,
                ESBTPEcheancierRule::STATUS_NON_AFFECTE,
            ])],
            'is_active' => ['required', 'boolean'],
            'targets' => ['required', 'array', 'min:1'],
            'targets.*' => ['required', 'string'],
        ]);

        $updated = 0;
        foreach ($validated['targets'] as $target) {
            [$scopeType, $scopeId] = array_pad(explode(':', $target, 2), 2, null);
            if (!$scopeType || !$scopeId || !$this->scopeExists($scopeType, (int) $scopeId)) {
                continue;
            }

            $updated += ESBTPEcheancierRule::query()
                ->where('scope_type', $scopeType)
                ->where('scope_id', (int) $scopeId)
                ->where('affectation_status', $validated['affectation_status'])
                ->update([
                    'is_active' => (bool) $validated['is_active'],
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
            $echeancierAdmin->forgetAffectedCache($scopeType, (int) $scopeId, $validated['affectation_status']);
        }

        return redirect()->back()->with('success', "{$updated} regle(s) mise(s) a jour.");
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
            $assignment = ESBTPOptionAssignment::with(['option.fraisCategory', 'option.configuration.fraisCategory', 'filiere', 'niveau'])->find($scopeId);
            if (!$assignment) {
                return null;
            }

            return [
                'title' => 'Assignation optionnelle',
                'subtitle' => ($assignment->option?->resolved_category?->name ?? 'Option') . ' - ' . ($assignment->option->name ?? 'N/A') . ' (' . $assignment->display_label . ')',
            ];
        }

        return null;
    }
}
