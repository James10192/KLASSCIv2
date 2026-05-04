@extends('layouts.app')

@section('title', 'Configuration des echeanciers - KLASSCI')

@push('styles')
<style>
.ech-shell{position:relative}.ech-grid{display:grid;grid-template-columns:minmax(320px,.9fr) minmax(540px,1.35fr);gap:1.1rem;align-items:start}.ech-stack{display:grid;gap:1rem}.ech-card{background:rgba(255,255,255,.95);border:1px solid rgba(148,163,184,.3);border-radius:10px;box-shadow:0 14px 34px rgba(15,23,42,.07);overflow:hidden}.ech-card-head{padding:1rem 1.1rem;border-bottom:1px solid rgba(226,232,240,.95);display:flex;align-items:center;justify-content:space-between;gap:.8rem}.ech-title{margin:0;font-size:.92rem;font-weight:800;color:#0f172a}.ech-sub{font-size:.75rem;color:#64748b}.ech-body{padding:1rem 1.1rem}.ech-scope-list{max-height:390px;overflow:auto;display:grid;gap:.45rem;padding-right:.2rem}.ech-scope{display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:center;padding:.78rem;border:1px solid #e2e8f0;border-radius:9px;background:#fff;color:inherit;text-decoration:none;transition:background .15s ease,border-color .15s ease,box-shadow .15s ease}.ech-scope:hover{background:#f8fbff;border-color:rgba(4,83,203,.3);box-shadow:0 8px 20px rgba(15,23,42,.06);text-decoration:none}.ech-scope.is-selected{background:rgba(4,83,203,.06);border-color:rgba(4,83,203,.38);box-shadow:inset 3px 0 0 #0453cb}.ech-scope-name{font-weight:800;color:#0f172a;font-size:.82rem}.ech-scope-meta{font-size:.73rem;color:#64748b;margin-top:.12rem}.ech-badges{display:flex;flex-wrap:wrap;gap:.25rem;margin-top:.45rem}.ech-badge{display:inline-flex;align-items:center;gap:.25rem;padding:.18rem .45rem;border-radius:999px;font-size:.66rem;font-weight:800;border:1px solid transparent}.ech-badge-on{background:rgba(16,185,129,.08);color:#047857;border-color:rgba(16,185,129,.24)}.ech-badge-off{background:rgba(100,116,139,.1);color:#334155;border-color:rgba(100,116,139,.22)}.ech-scope-icon{width:2rem;height:2rem;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;background:rgba(4,83,203,.08);color:#0453cb}.ech-empty{border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc;padding:1rem;color:#64748b;font-size:.78rem}.ech-editor-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem}.ech-kicker{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0;color:#0453cb}.ech-editor-title{font-size:1rem;font-weight:900;color:#0f172a;margin:.15rem 0}.ech-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}.ech-field label,.ech-label{display:block;font-size:.72rem;font-weight:800;color:#334155;margin-bottom:.32rem}.ech-input,.ech-select,.ech-textarea{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:.52rem .65rem;font-size:.78rem;color:#0f172a;background:#fff;min-height:2.35rem}.ech-select{padding-right:2rem;text-overflow:ellipsis}.ech-textarea{min-height:72px;resize:vertical}.ech-toggle{display:inline-flex;align-items:center;gap:.45rem;font-size:.77rem;font-weight:700;color:#334155}.ech-lines-wrap{overflow:auto;border:1px solid #e2e8f0;border-radius:10px;background:#fff}.ech-lines{width:100%;min-width:940px;border-collapse:collapse;table-layout:fixed}.ech-lines th,.ech-lines td{border-bottom:1px solid #eef2f7;padding:.55rem}.ech-lines th{background:#f8fbff;color:#475569;font-size:.66rem;font-weight:900;text-transform:uppercase;letter-spacing:0;text-align:left}.ech-lines th:nth-child(2),.ech-lines th:nth-child(4),.ech-lines th:nth-child(6),.ech-lines th:nth-child(7),.ech-lines th:nth-child(8),.ech-lines th:nth-child(9){text-align:center}.ech-lines td:nth-child(2),.ech-lines td:nth-child(7),.ech-lines td:nth-child(8),.ech-lines td:nth-child(9){text-align:center}.ech-lines .ech-input,.ech-lines .ech-select{min-height:2.2rem;padding:.45rem .55rem}.ech-actions{display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;margin-top:1rem}.ech-icon-btn{width:2rem;height:2rem;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#64748b;display:inline-flex;align-items:center;justify-content:center}.ech-icon-btn:hover{color:#dc2626;border-color:rgba(220,38,38,.25);background:rgba(220,38,38,.06)}.ech-loading{position:absolute;inset:0;z-index:20;display:none;align-items:flex-start;justify-content:center;padding-top:7rem;background:rgba(241,245,249,.66);backdrop-filter:blur(3px)}.ech-shell.is-loading .ech-loading{display:flex}.ech-loader{display:inline-flex;align-items:center;gap:.55rem;padding:.72rem .95rem;border:1px solid rgba(148,163,184,.3);border-radius:10px;background:#fff;color:#0f172a;font-size:.78rem;font-weight:800;box-shadow:0 16px 36px rgba(15,23,42,.12)}.ech-loader i{color:#0453cb}.ech-help{font-size:.74rem;color:#64748b}.ech-help code{color:#be123c;background:rgba(244,63,94,.08);padding:.1rem .25rem;border-radius:5px}@media(max-width:1100px){.ech-grid{grid-template-columns:1fr}.ech-form-grid{grid-template-columns:1fr}}
.ech-grid{grid-template-columns:minmax(430px,.95fr) minmax(560px,1.25fr)}.ech-card-head{padding:.85rem 1rem}.ech-body{padding:.8rem 1rem}.ech-scope-list{max-height:calc(100vh - 315px);min-height:260px;gap:.28rem}.ech-scope{grid-template-columns:1fr 2rem;padding:.52rem .6rem;border-radius:8px}.ech-scope-name{font-size:.78rem}.ech-scope-meta{font-size:.68rem;margin-top:.05rem}.ech-badges{margin-top:.25rem}.ech-scope-icon{width:1.75rem;height:1.75rem}.ech-scope-tools{display:grid;grid-template-columns:1fr auto;gap:.5rem;align-items:center;margin-bottom:.65rem}.ech-search{position:relative}.ech-search i{position:absolute;left:.65rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.76rem}.ech-search input{width:100%;height:2.15rem;border:1px solid #dbe3ef;border-radius:8px;padding:.45rem .7rem .45rem 1.9rem;font-size:.76rem;background:#f8fbff;color:#0f172a}.ech-count-pill{display:inline-flex;align-items:center;height:2.15rem;padding:0 .65rem;border-radius:8px;background:#eef5ff;color:#0453cb;font-size:.72rem;font-weight:800;white-space:nowrap}.ech-hidden{display:none!important}@media(max-width:1180px){.ech-grid{grid-template-columns:1fr}.ech-scope-list{max-height:430px}}
</style>
@endpush

@section('content')
@php
    $statusLabels = ['all' => 'Tous les statuts', 'affecté' => 'Affecte', 'réaffecté' => 'Reaffecte', 'non_affecté' => 'Non affecte'];
    $amountModeLabels = ['percent' => 'Pourcentage', 'fixed' => 'Montant fixe'];
    $dueModeLabels = ['days_after_inscription' => 'Apres inscription', 'fixed_mm_dd' => 'Date fixe'];
@endphp

<div class="dashboard-acasi">
    <div class="main-content ech-shell" data-ech-page>
        <div class="ech-loading" data-ech-loading>
            <div class="ech-loader"><i class="fas fa-circle-notch fa-spin"></i><span>Chargement du scope</span></div>
        </div>

        <div class="dashboard-header">
            <div class="header-left">
                <h1>Echeanciers de paiement</h1>
                <p class="header-subtitle">Tranches de paiement par frais obligatoire et optionnel.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi secondary"><i class="fas fa-layer-group"></i>Frais par classe</a>
                <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi secondary"><i class="fas fa-puzzle-piece"></i>Optionnels</a>
                <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="btn-acasi primary"><i class="fas fa-chart-line"></i>Dashboard</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert-kl alert-kl-success mb-3" style="border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-check-circle"></i><span>{{ session('success') }}</span>
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
            <div class="ech-stack">
                <div class="ech-card">
                    <div class="ech-card-head">
                        <h3 class="ech-title">Configurations obligatoires</h3>
                        <span class="ech-sub">{{ $configurations->count() }} scopes</span>
                    </div>
                    <div class="ech-body">
                        <div class="ech-scope-tools">
                            <label class="ech-search">
                                <i class="fas fa-search"></i>
                                <input type="search" data-scope-search data-target="mandatory" placeholder="Rechercher frais, filiere, niveau">
                            </label>
                            <span class="ech-count-pill" data-scope-count="mandatory">{{ $configurations->count() }}</span>
                        </div>
                        <div class="ech-scope-list">
                            @forelse($configurations as $configuration)
                                @php
                                    $scopeKey = 'configuration:' . $configuration->id;
                                    $scopeRules = $rulesByScope->get($scopeKey, collect());
                                    $isSelected = $selectedScopeType === 'configuration' && (int) $selectedScopeId === (int) $configuration->id;
                                @endphp
                                <a class="ech-scope {{ $isSelected ? 'is-selected' : '' }}" data-scope-item="mandatory" data-search-text="{{ Str::lower(($configuration->fraisCategory->name ?? '') . ' ' . ($configuration->filiere->name ?? '') . ' ' . ($configuration->niveau->name ?? '')) }}" data-ech-scope-link href="{{ route('esbtp.comptabilite.echeanciers.index', ['scope_type' => 'configuration', 'scope_id' => $configuration->id, 'affectation_status' => $selectedStatus]) }}">
                                    <span>
                                        <span class="ech-scope-name">{{ $configuration->fraisCategory->name ?? 'Frais' }}</span>
                                        <span class="ech-scope-meta d-block">{{ $configuration->filiere->name ?? 'N/A' }} / {{ $configuration->niveau->name ?? 'N/A' }}</span>
                                        <span class="ech-badges">
                                            @forelse($scopeRules as $rule)
                                                <span class="ech-badge {{ $rule->is_active ? 'ech-badge-on' : 'ech-badge-off' }}">{{ $statusLabels[$rule->affectation_status] ?? $rule->affectation_status }} · {{ $rule->lines->count() }}</span>
                                            @empty
                                                <span class="ech-sub">Aucune regle</span>
                                            @endforelse
                                        </span>
                                    </span>
                                    <span class="ech-scope-icon"><i class="fas fa-sliders-h"></i></span>
                                </a>
                            @empty
                                <div class="ech-empty">Aucune configuration active trouvee.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="ech-card">
                    <div class="ech-card-head">
                        <h3 class="ech-title">Assignations optionnelles</h3>
                        <span class="ech-sub">{{ $optionAssignments->count() }} scopes</span>
                    </div>
                    <div class="ech-body">
                        <div class="ech-scope-tools">
                            <label class="ech-search">
                                <i class="fas fa-search"></i>
                                <input type="search" data-scope-search data-target="optional" placeholder="Rechercher option, filiere, niveau">
                            </label>
                            <span class="ech-count-pill" data-scope-count="optional">{{ $optionAssignments->count() }}</span>
                        </div>
                        <div class="ech-scope-list">
                            @forelse($optionAssignments as $assignment)
                                @php
                                    $scopeKey = 'option_assignment:' . $assignment->id;
                                    $scopeRules = $rulesByScope->get($scopeKey, collect());
                                    $isSelected = $selectedScopeType === 'option_assignment' && (int) $selectedScopeId === (int) $assignment->id;
                                @endphp
                                <a class="ech-scope {{ $isSelected ? 'is-selected' : '' }}" data-scope-item="optional" data-search-text="{{ Str::lower(($assignment->option->fraisCategory->name ?? '') . ' ' . ($assignment->option->name ?? '') . ' ' . ($assignment->display_label ?? '')) }}" data-ech-scope-link href="{{ route('esbtp.comptabilite.echeanciers.index', ['scope_type' => 'option_assignment', 'scope_id' => $assignment->id, 'affectation_status' => $selectedStatus]) }}">
                                    <span>
                                        <span class="ech-scope-name">{{ $assignment->option->fraisCategory->name ?? 'Option' }} - {{ $assignment->option->name ?? 'N/A' }}</span>
                                        <span class="ech-scope-meta d-block">{{ $assignment->display_label }}</span>
                                        <span class="ech-badges">
                                            @forelse($scopeRules as $rule)
                                                <span class="ech-badge {{ $rule->is_active ? 'ech-badge-on' : 'ech-badge-off' }}">{{ $statusLabels[$rule->affectation_status] ?? $rule->affectation_status }} · {{ $rule->lines->count() }}</span>
                                            @empty
                                                <span class="ech-sub">Aucune regle</span>
                                            @endforelse
                                        </span>
                                    </span>
                                    <span class="ech-scope-icon"><i class="fas fa-sliders-h"></i></span>
                                </a>
                            @empty
                                <div class="ech-empty">Aucune assignation active trouvee.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="ech-card">
                <div class="ech-card-head">
                    <h3 class="ech-title">Editeur de regle</h3>
                    @if($selectedScopeDescriptor)<span class="ech-sub">{{ $selectedScopeDescriptor['title'] }}</span>@endif
                </div>
                <div class="ech-body">
                    @if(!$selectedScopeType || !$selectedScopeId)
                        <div class="ech-empty">Selectionnez un scope a gauche pour creer ou modifier une regle d'echeancier.</div>
                    @else
                        @php
                            $initialLines = old('lines');
                            if (!is_array($initialLines) || count($initialLines) === 0) {
                                $initialLines = $selectedRule
                                    ? $selectedRule->lines->map(fn ($line) => [
                                        'label' => $line->label,
                                        'sort_order' => $line->sort_order,
                                        'amount_mode' => $line->amount_mode,
                                        'amount_value' => $line->amount_value,
                                        'due_mode' => $line->due_mode,
                                        'due_value' => $line->due_value,
                                        'grace_days' => $line->grace_days,
                                        'is_active' => $line->is_active,
                                    ])->toArray()
                                    : [[
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
                        @endphp

                        <div class="ech-editor-head">
                            <div>
                                <div class="ech-kicker">{{ $selectedScopeDescriptor['title'] ?? 'Scope selectionne' }}</div>
                                <div class="ech-editor-title">{{ $selectedScopeDescriptor['subtitle'] ?? '' }}</div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('esbtp.comptabilite.echeanciers.upsert') }}">
                            @csrf
                            <input type="hidden" name="scope_type" value="{{ $selectedScopeType }}">
                            <input type="hidden" name="scope_id" value="{{ $selectedScopeId }}">

                            <div class="ech-form-grid mb-2">
                                <div class="ech-field">
                                    <label>Statut d'affectation</label>
                                    <select name="affectation_status" class="ech-select">
                                        @foreach(['all', 'affecté', 'réaffecté', 'non_affecté'] as $status)
                                            <option value="{{ $status }}" {{ old('affectation_status', $selectedStatus) === $status ? 'selected' : '' }}>{{ $statusLabels[$status] ?? $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ech-field">
                                    <label>Priorite</label>
                                    <input type="number" name="priority" class="ech-input" min="1" max="9999" value="{{ old('priority', $selectedRule->priority ?? 100) }}">
                                </div>
                            </div>

                            <div class="ech-form-grid mb-2">
                                <div class="ech-field">
                                    <label>Actif des le</label>
                                    <input type="date" name="effective_from" class="ech-input" value="{{ old('effective_from', optional($selectedRule?->effective_from)->format('Y-m-d')) }}">
                                </div>
                                <div class="ech-field">
                                    <label>Actif jusqu'au</label>
                                    <input type="date" name="effective_to" class="ech-input" value="{{ old('effective_to', optional($selectedRule?->effective_to)->format('Y-m-d')) }}">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="ech-toggle"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $selectedRule->is_active ?? true) ? 'checked' : '' }}>Regle active</label>
                            </div>

                            <div class="ech-field mb-3">
                                <label>Notes</label>
                                <textarea name="notes" class="ech-textarea" placeholder="Commentaire interne optionnel">{{ old('notes', $selectedRule->notes ?? '') }}</textarea>
                            </div>

                            <div class="mb-2" style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                                <label class="ech-label" style="margin:0;">Tranches</label>
                                <button type="button" data-add-line class="btn-acasi secondary" style="padding:.38rem .68rem;font-size:.72rem;"><i class="fas fa-plus"></i>Ajouter</button>
                            </div>

                            <div class="ech-lines-wrap">
                                <table class="ech-lines">
                                    <thead>
                                        <tr>
                                            <th style="width:145px;">Libelle</th>
                                            <th style="width:75px;">Ordre</th>
                                            <th style="width:145px;">Montant</th>
                                            <th style="width:115px;">Valeur</th>
                                            <th style="width:160px;">Echeance</th>
                                            <th style="width:115px;">Valeur</th>
                                            <th style="width:90px;">Grace</th>
                                            <th style="width:70px;">Actif</th>
                                            <th style="width:62px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody data-lines-body>
                                        @foreach($initialLines as $index => $line)
                                            <tr>
                                                <td><input type="text" class="ech-input" name="lines[{{ $index }}][label]" value="{{ $line['label'] ?? '' }}" required></td>
                                                <td><input type="number" class="ech-input" name="lines[{{ $index }}][sort_order]" min="1" max="99" value="{{ $line['sort_order'] ?? ($index + 1) }}"></td>
                                                <td><select class="ech-select" name="lines[{{ $index }}][amount_mode]"><option value="percent" {{ ($line['amount_mode'] ?? '') === 'percent' ? 'selected' : '' }}>{{ $amountModeLabels['percent'] }}</option><option value="fixed" {{ ($line['amount_mode'] ?? '') === 'fixed' ? 'selected' : '' }}>{{ $amountModeLabels['fixed'] }}</option></select></td>
                                                <td><input type="number" step="0.01" min="0" class="ech-input" name="lines[{{ $index }}][amount_value]" value="{{ $line['amount_value'] ?? '' }}" required></td>
                                                <td><select class="ech-select" name="lines[{{ $index }}][due_mode]"><option value="days_after_inscription" {{ ($line['due_mode'] ?? '') === 'days_after_inscription' ? 'selected' : '' }}>{{ $dueModeLabels['days_after_inscription'] }}</option><option value="fixed_mm_dd" {{ ($line['due_mode'] ?? '') === 'fixed_mm_dd' ? 'selected' : '' }}>{{ $dueModeLabels['fixed_mm_dd'] }}</option></select></td>
                                                <td><input type="text" class="ech-input" name="lines[{{ $index }}][due_value]" value="{{ $line['due_value'] ?? '' }}" required></td>
                                                <td><input type="number" min="0" max="365" class="ech-input" name="lines[{{ $index }}][grace_days]" value="{{ $line['grace_days'] ?? 0 }}"></td>
                                                <td><input type="checkbox" name="lines[{{ $index }}][is_active]" value="1" {{ !isset($line['is_active']) || $line['is_active'] ? 'checked' : '' }}></td>
                                                <td><button type="button" class="ech-icon-btn" data-remove-line title="Retirer"><i class="fas fa-times"></i></button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="ech-actions">
                                <button type="submit" class="btn-acasi primary"><i class="fas fa-save"></i>Enregistrer la regle</button>
                                <span class="ech-help">Pour une date fixe, format attendu: <code>MM-DD</code> (ex: <code>10-15</code>).</span>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function initEcheanciersPage(root) {
        root = root || document;
        const shell = root.matches && root.matches('[data-ech-page]') ? root : document.querySelector('[data-ech-page]');
        const linesBody = shell ? shell.querySelector('[data-lines-body]') : null;
        const addBtn = shell ? shell.querySelector('[data-add-line]') : null;

        if (addBtn && linesBody && !addBtn.dataset.bound) {
            addBtn.dataset.bound = '1';
            addBtn.addEventListener('click', function () {
                const index = linesBody.querySelectorAll('tr').length;
                linesBody.insertAdjacentHTML('beforeend', lineRow(index));
            });
        }

        if (linesBody && !linesBody.dataset.bound) {
            linesBody.dataset.bound = '1';
            linesBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-remove-line]');
                if (!button || linesBody.querySelectorAll('tr').length <= 1) return;
                button.closest('tr').remove();
            });
        }

        root.querySelectorAll('[data-scope-search]').forEach(function (input) {
            if (input.dataset.bound) return;
            input.dataset.bound = '1';
            input.addEventListener('input', function () {
                filterScopes(root, input.dataset.target, input.value);
            });
        });
    }

    function filterScopes(root, target, value) {
        const query = (value || '').trim().toLowerCase();
        let visible = 0;

        root.querySelectorAll(`[data-scope-item="${target}"]`).forEach(function (item) {
            const haystack = item.dataset.searchText || item.textContent.toLowerCase();
            const match = !query || haystack.includes(query);
            item.classList.toggle('ech-hidden', !match);
            if (match) visible++;
        });

        const counter = root.querySelector(`[data-scope-count="${target}"]`);
        if (counter) counter.textContent = visible;
    }

    function lineRow(index) {
        return `
            <tr>
                <td><input type="text" class="ech-input" name="lines[${index}][label]" value="Tranche ${index + 1}" required></td>
                <td><input type="number" class="ech-input" name="lines[${index}][sort_order]" min="1" max="99" value="${index + 1}"></td>
                <td><select class="ech-select" name="lines[${index}][amount_mode]"><option value="percent">Pourcentage</option><option value="fixed">Montant fixe</option></select></td>
                <td><input type="number" step="0.01" min="0" class="ech-input" name="lines[${index}][amount_value]" value="0" required></td>
                <td><select class="ech-select" name="lines[${index}][due_mode]"><option value="days_after_inscription">Apres inscription</option><option value="fixed_mm_dd">Date fixe</option></select></td>
                <td><input type="text" class="ech-input" name="lines[${index}][due_value]" value="30" required></td>
                <td><input type="number" min="0" max="365" class="ech-input" name="lines[${index}][grace_days]" value="0"></td>
                <td><input type="checkbox" name="lines[${index}][is_active]" value="1" checked></td>
                <td><button type="button" class="ech-icon-btn" data-remove-line title="Retirer"><i class="fas fa-times"></i></button></td>
            </tr>
        `;
    }

    document.addEventListener('click', async function (event) {
        const link = event.target.closest('[data-ech-scope-link]');
        if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        event.preventDefault();
        const shell = document.querySelector('[data-ech-page]');
        if (!shell) {
            window.location.href = link.href;
            return;
        }

        shell.classList.add('is-loading');
        shell.querySelectorAll('a,button,input,select,textarea').forEach(el => el.disabled = true);

        try {
            const response = await fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const next = doc.querySelector('[data-ech-page]');
            if (!next) throw new Error('Invalid response');

            shell.replaceWith(next);
            window.history.pushState({}, '', link.href);
            initEcheanciersPage(next);
        } catch (error) {
            window.location.href = link.href;
        }
    });

    window.addEventListener('popstate', function () {
        window.location.reload();
    });

    document.addEventListener('DOMContentLoaded', function () {
        initEcheanciersPage(document);
    });
})();
</script>
@endpush
