@extends('layouts.app')

@section('title', 'Configuration des Échéanciers - KLASSCI')

@push('styles')
<style>
.ech-grid {
    display: grid;
    grid-template-columns: 1.05fr 1.25fr;
    gap: 1rem;
}
.ech-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
}
.ech-card-head {
    padding: .9rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
}
.ech-card-title {
    margin: 0;
    font-size: .9rem;
    font-weight: 700;
    color: #1e293b;
}
.ech-card-body {
    padding: .9rem 1rem;
}
.ech-table-wrap {
    max-height: 300px;
    overflow: auto;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}
.ech-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .78rem;
}
.ech-table th,
.ech-table td {
    padding: .55rem .6rem;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
}
.ech-table th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    color: #475569;
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.ech-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .18rem .45rem;
    border-radius: 999px;
    font-size: .66rem;
    font-weight: 700;
    border: 1px solid transparent;
    margin-right: .2rem;
    margin-bottom: .2rem;
}
.ech-badge-on {
    background: rgba(16, 185, 129, .08);
    color: #047857;
    border-color: rgba(16, 185, 129, .22);
}
.ech-badge-off {
    background: rgba(100, 116, 139, .10);
    color: #334155;
    border-color: rgba(100, 116, 139, .22);
}
.ech-note {
    font-size: .75rem;
    color: #64748b;
}
.ech-selected {
    border-left: 3px solid #0453cb;
    background: rgba(4, 83, 203, .03);
}
.ech-lines-table {
    width: 100%;
    border-collapse: collapse;
}
.ech-lines-table th,
.ech-lines-table td {
    border: 1px solid #e2e8f0;
    padding: .45rem;
}
.ech-lines-table th {
    background: #f8fafc;
    color: #475569;
    font-size: .66rem;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.ech-input,
.ech-select,
.ech-textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: .45rem .55rem;
    font-size: .78rem;
    color: #0f172a;
    background: #fff;
}
.ech-textarea {
    min-height: 68px;
    resize: vertical;
}
.ech-inline {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .65rem;
}
.ech-actions {
    display: flex;
    align-items: center;
    gap: .5rem;
}
@media (max-width: 1100px) {
    .ech-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Échéanciers de Paiement</h1>
                <p class="header-subtitle">Configuration des tranches de paiement par configuration de frais et assignation optionnelle.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi secondary">
                    <i class="fas fa-layer-group"></i>Frais par Classe
                </a>
                <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi secondary">
                    <i class="fas fa-puzzle-piece"></i>Optionnels
                </a>
                <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="btn-acasi primary">
                    <i class="fas fa-chart-line"></i>Dashboard Compta
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert-kl alert-kl-success mb-3" style="border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="alert-kl alert-kl-danger mb-3" style="border-radius:10px;padding:12px 14px;display:flex;align-items:flex-start;gap:8px;">
                <i class="fas fa-exclamation-triangle" style="margin-top:2px;"></i>
                <div>
                    <strong>Veuillez corriger les erreurs du formulaire.</strong>
                    <ul style="margin:.35rem 0 0 1rem;padding:0;">
                        @foreach($errors->all() as $error)
                            <li style="font-size:.8rem;">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="ech-grid">
            <div>
                <div class="ech-card mb-3">
                    <div class="ech-card-head">
                        <h3 class="ech-card-title">Scopes Obligatoires (Configurations)</h3>
                        <span class="ech-note">{{ $configurations->count() }} scopes</span>
                    </div>
                    <div class="ech-card-body">
                        <div class="ech-table-wrap">
                            <table class="ech-table">
                                <thead>
                                    <tr>
                                        <th>Classe / Frais</th>
                                        <th>Règles</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($configurations as $configuration)
                                        @php
                                            $scopeKey = 'configuration:' . $configuration->id;
                                            $scopeRules = $rulesByScope->get($scopeKey, collect());
                                            $isSelected = $selectedScopeType === 'configuration' && (int) $selectedScopeId === (int) $configuration->id;
                                        @endphp
                                        <tr class="{{ $isSelected ? 'ech-selected' : '' }}">
                                            <td>
                                                <div style="font-weight:700;color:#1e293b;">{{ $configuration->fraisCategory->name ?? 'Frais' }}</div>
                                                <div class="ech-note">{{ $configuration->filiere->name ?? 'N/A' }} / {{ $configuration->niveau->name ?? 'N/A' }}</div>
                                            </td>
                                            <td>
                                                @forelse($scopeRules as $rule)
                                                    <span class="ech-badge {{ $rule->is_active ? 'ech-badge-on' : 'ech-badge-off' }}">
                                                        {{ $rule->affectation_status }} · {{ $rule->lines->count() }}
                                                    </span>
                                                @empty
                                                    <span class="ech-note">Aucune règle</span>
                                                @endforelse
                                            </td>
                                            <td style="white-space:nowrap;">
                                                <a href="{{ route('esbtp.comptabilite.echeanciers.index', ['scope_type' => 'configuration', 'scope_id' => $configuration->id, 'affectation_status' => $selectedStatus]) }}"
                                                   class="btn-acasi primary" style="padding:.35rem .6rem;font-size:.72rem;">
                                                    <i class="fas fa-edit"></i>Configurer
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="ech-note">Aucune configuration active trouvée.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="ech-card">
                    <div class="ech-card-head">
                        <h3 class="ech-card-title">Scopes Optionnels (Assignations)</h3>
                        <span class="ech-note">{{ $optionAssignments->count() }} scopes</span>
                    </div>
                    <div class="ech-card-body">
                        <div class="ech-table-wrap">
                            <table class="ech-table">
                                <thead>
                                    <tr>
                                        <th>Option / Assignation</th>
                                        <th>Règles</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($optionAssignments as $assignment)
                                        @php
                                            $scopeKey = 'option_assignment:' . $assignment->id;
                                            $scopeRules = $rulesByScope->get($scopeKey, collect());
                                            $isSelected = $selectedScopeType === 'option_assignment' && (int) $selectedScopeId === (int) $assignment->id;
                                        @endphp
                                        <tr class="{{ $isSelected ? 'ech-selected' : '' }}">
                                            <td>
                                                <div style="font-weight:700;color:#1e293b;">{{ $assignment->option->fraisCategory->name ?? 'Option' }} - {{ $assignment->option->name ?? 'N/A' }}</div>
                                                <div class="ech-note">{{ $assignment->display_label }}</div>
                                            </td>
                                            <td>
                                                @forelse($scopeRules as $rule)
                                                    <span class="ech-badge {{ $rule->is_active ? 'ech-badge-on' : 'ech-badge-off' }}">
                                                        {{ $rule->affectation_status }} · {{ $rule->lines->count() }}
                                                    </span>
                                                @empty
                                                    <span class="ech-note">Aucune règle</span>
                                                @endforelse
                                            </td>
                                            <td style="white-space:nowrap;">
                                                <a href="{{ route('esbtp.comptabilite.echeanciers.index', ['scope_type' => 'option_assignment', 'scope_id' => $assignment->id, 'affectation_status' => $selectedStatus]) }}"
                                                   class="btn-acasi primary" style="padding:.35rem .6rem;font-size:.72rem;">
                                                    <i class="fas fa-edit"></i>Configurer
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="ech-note">Aucune assignation active trouvée.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="ech-card">
                    <div class="ech-card-head">
                        <h3 class="ech-card-title">Éditeur de Règle</h3>
                        @if($selectedScopeDescriptor)
                            <span class="ech-note">{{ $selectedScopeDescriptor['title'] }}</span>
                        @endif
                    </div>
                    <div class="ech-card-body">
                        @if(!$selectedScopeType || !$selectedScopeId)
                            <div class="ech-note" style="padding:.8rem;border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc;">
                                Sélectionnez un scope à gauche pour créer ou modifier une règle d'échéancier.
                            </div>
                        @else
                            @php
                                $initialLines = old('lines');
                                if (!is_array($initialLines) || count($initialLines) === 0) {
                                    if ($selectedRule) {
                                        $initialLines = $selectedRule->lines->map(function ($line) {
                                            return [
                                                'label' => $line->label,
                                                'sort_order' => $line->sort_order,
                                                'amount_mode' => $line->amount_mode,
                                                'amount_value' => $line->amount_value,
                                                'due_mode' => $line->due_mode,
                                                'due_value' => $line->due_value,
                                                'grace_days' => $line->grace_days,
                                                'is_active' => $line->is_active,
                                            ];
                                        })->toArray();
                                    } else {
                                        $initialLines = [[
                                            'label' => 'Tranche 1',
                                            'sort_order' => 1,
                                            'amount_mode' => 'percent',
                                            'amount_value' => 100,
                                            'due_mode' => 'days_after_inscription',
                                            'due_value' => 30,
                                            'grace_days' => 0,
                                            'is_active' => true,
                                        ]];
                                    }
                                }
                            @endphp

                            <div class="mb-3">
                                <div style="font-size:.82rem;font-weight:700;color:#1e293b;">{{ $selectedScopeDescriptor['title'] ?? 'Scope sélectionné' }}</div>
                                <div class="ech-note">{{ $selectedScopeDescriptor['subtitle'] ?? '' }}</div>
                            </div>

                            <form method="POST" action="{{ route('esbtp.comptabilite.echeanciers.upsert') }}">
                                @csrf
                                <input type="hidden" name="scope_type" value="{{ $selectedScopeType }}">
                                <input type="hidden" name="scope_id" value="{{ $selectedScopeId }}">

                                <div class="ech-inline mb-2">
                                    <div>
                                        <label style="font-size:.72rem;font-weight:700;color:#334155;">Statut d'affectation</label>
                                        <select name="affectation_status" class="ech-select">
                                            @foreach(['all', 'affecté', 'réaffecté', 'non_affecté'] as $status)
                                                <option value="{{ $status }}" {{ old('affectation_status', $selectedStatus) === $status ? 'selected' : '' }}>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-size:.72rem;font-weight:700;color:#334155;">Priorité</label>
                                        <input type="number" name="priority" class="ech-input" min="1" max="9999"
                                               value="{{ old('priority', $selectedRule->priority ?? 100) }}">
                                    </div>
                                </div>

                                <div class="ech-inline mb-2">
                                    <div>
                                        <label style="font-size:.72rem;font-weight:700;color:#334155;">Actif dès le</label>
                                        <input type="date" name="effective_from" class="ech-input"
                                               value="{{ old('effective_from', optional($selectedRule?->effective_from)->format('Y-m-d')) }}">
                                    </div>
                                    <div>
                                        <label style="font-size:.72rem;font-weight:700;color:#334155;">Actif jusqu'au</label>
                                        <input type="date" name="effective_to" class="ech-input"
                                               value="{{ old('effective_to', optional($selectedRule?->effective_to)->format('Y-m-d')) }}">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label style="display:inline-flex;align-items:center;gap:.4rem;font-size:.76rem;color:#334155;font-weight:600;">
                                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $selectedRule->is_active ?? true) ? 'checked' : '' }}>
                                        Règle active
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label style="font-size:.72rem;font-weight:700;color:#334155;">Notes</label>
                                    <textarea name="notes" class="ech-textarea" placeholder="Commentaire interne (optionnel)">{{ old('notes', $selectedRule->notes ?? '') }}</textarea>
                                </div>

                                <div class="mb-2" style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                                    <label style="font-size:.74rem;font-weight:700;color:#334155;margin:0;">Tranches</label>
                                    <button type="button" id="addLineBtn" class="btn-acasi secondary" style="padding:.35rem .65rem;font-size:.72rem;">
                                        <i class="fas fa-plus"></i>Ajouter une tranche
                                    </button>
                                </div>

                                <div style="overflow:auto;">
                                    <table class="ech-lines-table" id="linesTable">
                                        <thead>
                                            <tr>
                                                <th>Libellé</th>
                                                <th>Ordre</th>
                                                <th>Montant</th>
                                                <th>Valeur</th>
                                                <th>Échéance</th>
                                                <th>Valeur</th>
                                                <th>Grâce (j)</th>
                                                <th>Actif</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="linesBody">
                                            @foreach($initialLines as $index => $line)
                                                <tr>
                                                    <td><input type="text" class="ech-input" name="lines[{{ $index }}][label]" value="{{ $line['label'] ?? '' }}" required></td>
                                                    <td><input type="number" class="ech-input" name="lines[{{ $index }}][sort_order]" min="1" max="99" value="{{ $line['sort_order'] ?? ($index + 1) }}"></td>
                                                    <td>
                                                        <select class="ech-select" name="lines[{{ $index }}][amount_mode]">
                                                            <option value="percent" {{ ($line['amount_mode'] ?? '') === 'percent' ? 'selected' : '' }}>percent</option>
                                                            <option value="fixed" {{ ($line['amount_mode'] ?? '') === 'fixed' ? 'selected' : '' }}>fixed</option>
                                                        </select>
                                                    </td>
                                                    <td><input type="number" step="0.01" min="0" class="ech-input" name="lines[{{ $index }}][amount_value]" value="{{ $line['amount_value'] ?? '' }}" required></td>
                                                    <td>
                                                        <select class="ech-select" name="lines[{{ $index }}][due_mode]">
                                                            <option value="days_after_inscription" {{ ($line['due_mode'] ?? '') === 'days_after_inscription' ? 'selected' : '' }}>days_after_inscription</option>
                                                            <option value="fixed_mm_dd" {{ ($line['due_mode'] ?? '') === 'fixed_mm_dd' ? 'selected' : '' }}>fixed_mm_dd</option>
                                                        </select>
                                                    </td>
                                                    <td><input type="text" class="ech-input" name="lines[{{ $index }}][due_value]" value="{{ $line['due_value'] ?? '' }}" required></td>
                                                    <td><input type="number" min="0" max="365" class="ech-input" name="lines[{{ $index }}][grace_days]" value="{{ $line['grace_days'] ?? 0 }}"></td>
                                                    <td style="text-align:center;"><input type="checkbox" name="lines[{{ $index }}][is_active]" value="1" {{ !isset($line['is_active']) || $line['is_active'] ? 'checked' : '' }}></td>
                                                    <td style="text-align:center;"><button type="button" class="btn-acasi secondary remove-line-btn" style="padding:.2rem .42rem;"><i class="fas fa-times"></i></button></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="ech-actions mt-3">
                                    <button type="submit" class="btn-acasi primary">
                                        <i class="fas fa-save"></i>Enregistrer la règle
                                    </button>
                                    <span class="ech-note">Pour <code>fixed_mm_dd</code>, format attendu: <code>MM-DD</code> (ex: <code>10-15</code>).</span>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const linesBody = document.getElementById('linesBody');
    const addBtn = document.getElementById('addLineBtn');

    if (!linesBody || !addBtn) {
        return;
    }

    function nextIndex() {
        return linesBody.querySelectorAll('tr').length;
    }

    function lineRow(index) {
        return `
            <tr>
                <td><input type="text" class="ech-input" name="lines[${index}][label]" value="Tranche ${index + 1}" required></td>
                <td><input type="number" class="ech-input" name="lines[${index}][sort_order]" min="1" max="99" value="${index + 1}"></td>
                <td>
                    <select class="ech-select" name="lines[${index}][amount_mode]">
                        <option value="percent">percent</option>
                        <option value="fixed">fixed</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" min="0" class="ech-input" name="lines[${index}][amount_value]" value="0" required></td>
                <td>
                    <select class="ech-select" name="lines[${index}][due_mode]">
                        <option value="days_after_inscription">days_after_inscription</option>
                        <option value="fixed_mm_dd">fixed_mm_dd</option>
                    </select>
                </td>
                <td><input type="text" class="ech-input" name="lines[${index}][due_value]" value="30" required></td>
                <td><input type="number" min="0" max="365" class="ech-input" name="lines[${index}][grace_days]" value="0"></td>
                <td style="text-align:center;"><input type="checkbox" name="lines[${index}][is_active]" value="1" checked></td>
                <td style="text-align:center;"><button type="button" class="btn-acasi secondary remove-line-btn" style="padding:.2rem .42rem;"><i class="fas fa-times"></i></button></td>
            </tr>
        `;
    }

    addBtn.addEventListener('click', function () {
        linesBody.insertAdjacentHTML('beforeend', lineRow(nextIndex()));
    });

    linesBody.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-line-btn');
        if (!button) {
            return;
        }

        if (linesBody.querySelectorAll('tr').length <= 1) {
            return;
        }

        button.closest('tr').remove();
    });
});
</script>
@endpush
