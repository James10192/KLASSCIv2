@extends('layouts.app')

@section('title', 'Configuration des Frais - KLASSCI')

@push('styles')
<style>
:root {
    --fc-primary: #0453cb;
    --fc-primary-d: #033a8e;
    --fc-secondary: #5e91de;
    --fc-text: #1e293b;
    --fc-muted: #64748b;
    --fc-success: #10b981;
    --fc-warning: #f59e0b;
    --fc-danger: #ef4444;
    --fc-surface: #f8fafc;
    --fc-white: #ffffff;
    --fc-border: #e2e8f0;
    --fc-shadow-sm: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    --fc-shadow-lg: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
}
.fc-shell { padding: 1.25rem 0 2rem; }
.fc-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%);
    border-radius: 18px; padding: 1.5rem 1.75rem; color: #fff; margin-bottom: 1rem;
}
.fc-hero h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .25rem; }
.fc-hero p { margin: 0; color: rgba(255,255,255,.76); font-size: .9rem; }
.fc-info-bar, .fc-toolbar {
    background: var(--fc-white); border: 1px solid var(--fc-border); border-radius: 14px;
    padding: 1rem 1.1rem; box-shadow: var(--fc-shadow-sm); margin-bottom: 1rem;
}
.fc-toolbar { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: flex-end; }
.fc-tabs { display: flex; gap: .6rem; flex-wrap: wrap; margin-bottom: 1rem; }
.fc-tab-btn {
    border: 1px solid var(--fc-border); background: var(--fc-white); color: var(--fc-text);
    border-radius: 999px; padding: .55rem 1rem; font-size: .8rem; font-weight: 700; cursor: pointer;
}
.fc-tab-btn.is-active { background: var(--fc-primary); color: #fff; border-color: var(--fc-primary); }
.fc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; }
.fc-card {
    background: var(--fc-white); border: 1px solid var(--fc-border); border-radius: 16px;
    padding: 1.1rem; box-shadow: var(--fc-shadow-sm); position: relative;
}
.fc-card:hover { box-shadow: var(--fc-shadow-lg); transform: translateY(-2px); transition: .2s ease; }
.fc-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 16px 16px 0 0; }
.fc-card.is-complete::before { background: var(--fc-success); }
.fc-card.is-partial::before { background: var(--fc-warning); }
.fc-card.is-empty::before { background: var(--fc-danger); }
.fc-card-header { display: flex; gap: .75rem; margin-bottom: .9rem; }
.fc-card-icon {
    width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, var(--fc-primary), var(--fc-secondary));
    color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.fc-card-name { font-size: .92rem; font-weight: 700; color: var(--fc-text); line-height: 1.25; }
.fc-card-meta, .fc-card-students { font-size: .72rem; color: var(--fc-muted); }
.fc-stats { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-bottom: .9rem; }
.fc-stat { background: var(--fc-surface); border-radius: 10px; text-align: center; padding: .6rem .5rem; }
.fc-stat-value { font-size: 1.05rem; font-weight: 800; color: var(--fc-primary); }
.fc-stat-label { font-size: .65rem; text-transform: uppercase; color: var(--fc-muted); }
.fc-badge {
    display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .65rem; border-radius: 999px;
    font-size: .68rem; font-weight: 700; margin-bottom: .8rem;
}
.fc-badge.is-complete { background: rgba(16,185,129,.08); color: #059669; }
.fc-badge.is-partial { background: rgba(245,158,11,.08); color: #b45309; }
.fc-badge.is-empty { background: rgba(239,68,68,.08); color: #dc2626; }
.fc-btn-config {
    width: 100%; border: none; border-radius: 10px; background: var(--fc-primary); color: #fff;
    padding: .68rem 1rem; font-size: .82rem; font-weight: 700; display: inline-flex; gap: .45rem;
    align-items: center; justify-content: center;
}
.fc-btn-config:hover { background: var(--fc-primary-d); }
.fc-section-title { font-size: .92rem; font-weight: 700; color: var(--fc-text); margin-bottom: 1rem; display: flex; gap: .5rem; align-items: center; }
.fc-section-title i {
    width: 32px; height: 32px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center;
    background: rgba(4,83,203,.08); color: var(--fc-primary);
}
.fc-loading-state { display: grid; gap: .9rem; }
.fc-loading-card { background: var(--fc-white); border: 1px solid var(--fc-border); border-radius: 14px; padding: 1rem 1.1rem; }
.fc-loading-head { display: flex; gap: .75rem; align-items: center; margin-bottom: .9rem; }
.fc-skeleton {
    position: relative; overflow: hidden; background: linear-gradient(90deg, #edf2f7 0%, #f8fafc 50%, #edf2f7 100%);
    background-size: 200% 100%; animation: fc-skeleton 1.1s linear infinite; border-radius: 10px;
}
.fc-skeleton.is-icon { width: 38px; height: 38px; }
.fc-skeleton.is-title { height: 14px; width: 42%; margin-bottom: .45rem; }
.fc-skeleton.is-subtitle { height: 10px; width: 58%; }
.fc-skeleton.is-block { height: 58px; margin-bottom: .75rem; }
.fc-skeleton.is-line { height: 12px; width: 100%; }
.fc-loading-caption { display: flex; justify-content: center; align-items: center; gap: .45rem; color: var(--fc-muted); font-size: .8rem; font-weight: 600; }
.fc-target-list { display: grid; gap: .55rem; max-height: 220px; overflow: auto; }
.fc-target-item {
    border: 1px solid var(--fc-border); border-radius: 10px; padding: .7rem .8rem; display: flex; gap: .6rem;
    align-items: flex-start; background: var(--fc-surface);
}
.fc-target-item strong { color: var(--fc-text); font-size: .78rem; }
.fc-target-item small { color: var(--fc-muted); }
.fc-target-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .75rem; }
.fc-target-actions .fc-tab-btn { padding: .45rem .8rem; }
.fc-target-actions .fc-tab-btn.is-active { background: var(--fc-primary); border-color: var(--fc-primary); color: #fff; }
.fc-empty { background: #fff; border: 1px dashed var(--fc-border); border-radius: 14px; padding: 1.4rem; text-align: center; color: var(--fc-muted); }
#configurationModal .modal-dialog { max-width: 980px; }
#configurationModal .modal-header { background: linear-gradient(135deg, #071631 0%, #0453cb 100%); color: #fff; }
#configurationModal .modal-body { background: var(--fc-surface); }
.fc-modal-info { background: rgba(4,83,203,.05); border: 1px solid rgba(4,83,203,.12); border-radius: 12px; padding: .85rem 1rem; margin-bottom: 1rem; }
@keyframes fc-skeleton { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
@media (max-width: 768px) { .fc-toolbar { align-items: stretch; } }
</style>
@endpush

@section('content')
<div class="container-fluid fc-shell">
    <div class="fc-hero">
        <h1>Configuration des frais</h1>
        <p>Templates globaux et surcharges annuelles avec propagation par niveau BTS/LMD.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="fc-info-bar">
        <strong>Règle métier :</strong> `frais.configure` pilote le global. En mode année universitaire, vous créez uniquement des surcharges; le runtime retombe sur le global quand aucune surcharge n'existe.
    </div>

    <div class="fc-toolbar">
        <div>
            <div style="font-size:.72rem;font-weight:700;color:var(--fc-text);margin-bottom:.35rem;">Mode de configuration</div>
            <div class="fc-tabs" style="margin-bottom:0;">
                <button type="button" class="fc-tab-btn {{ ($configurationMode ?? 'global') === 'global' ? 'is-active' : '' }}" data-mode-target="global">Global</button>
                <button type="button" class="fc-tab-btn {{ ($configurationMode ?? 'global') === 'annual' ? 'is-active' : '' }}" data-mode-target="annual">Année universitaire</button>
            </div>
        </div>
        <div id="annualModeSelector" style="{{ ($configurationMode ?? 'global') === 'annual' ? '' : 'display:none;' }}">
            <label for="annualYearSelect" style="display:block;font-size:.72rem;font-weight:700;color:var(--fc-text);margin-bottom:.35rem;">Année cible</label>
            <select id="annualYearSelect" class="form-select form-select-sm">
                @foreach($anneesUniversitaires ?? collect() as $annee)
                    <option value="{{ $annee->id }}" {{ (int) ($anneeUniversitaireId ?? 0) === (int) $annee->id ? 'selected' : '' }}>{{ $annee->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="max-width:430px;font-size:.76rem;color:var(--fc-muted);">
            Le même modal permet maintenant soit d'éditer une combinaison, soit d'appliquer la configuration obligatoire à toutes les combinaisons d'un niveau.
        </div>
    </div>

    <div class="fc-section-title"><i class="fas fa-layer-group"></i>Configuration rapide par niveau</div>
    <div class="fc-grid" style="margin-bottom:1.25rem;">
        @foreach(($btsLevels ?? collect())->concat($lmdLevels ?? collect()) as $levelCard)
            <div class="fc-card is-partial" data-systeme="{{ $levelCard->systeme }}">
                <div class="fc-card-header">
                    <div class="fc-card-icon"><i class="fas fa-sitemap"></i></div>
                    <div>
                        <div class="fc-card-name">{{ $levelCard->label }}</div>
                        <div class="fc-card-meta">{{ $levelCard->systeme }} · {{ $levelCard->target_count }} combinaisons</div>
                    </div>
                </div>
                <button type="button"
                        class="fc-btn-config configure-level-btn"
                        data-systeme="{{ $levelCard->systeme }}"
                        data-niveau-id="{{ $levelCard->niveau->id }}"
                        data-niveau-name="{{ $levelCard->niveau->name }}"
                        data-label-scope="Toutes les combinaisons de {{ $levelCard->niveau->name }} ({{ $levelCard->systeme }})">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    <span>Configurer tout le niveau</span>
                </button>
            </div>
        @endforeach
    </div>

    <div class="fc-tabs" role="tablist" aria-label="Systèmes académiques">
        <button type="button" class="fc-tab-btn is-active" data-tab-target="ALL">Tous ({{ $classes->count() }})</button>
        <button type="button" class="fc-tab-btn" data-tab-target="BTS">BTS ({{ $btsClasses->count() }})</button>
        <button type="button" class="fc-tab-btn" data-tab-target="LMD">LMD ({{ $lmdClasses->count() }})</button>
    </div>

    <div class="fc-section-title"><i class="fas fa-graduation-cap"></i>Combinaisons configurables</div>
    @if($classes->count() > 0)
        <div class="fc-grid">
            @foreach($classes as $classe)
                @php
                    $totalRequired = $classe->total_obligatoires;
                    $totalConfigured = $classe->obligatoires_configures;
                    if ($totalConfigured == $totalRequired && $totalRequired > 0) {
                        $statusClass = 'is-complete';
                        $statusIcon = 'fa-check-circle';
                        $statusText = 'Complet';
                    } elseif ($totalConfigured > 0) {
                        $statusClass = 'is-partial';
                        $statusIcon = 'fa-exclamation-triangle';
                        $statusText = 'Partiel';
                    } else {
                        $statusClass = 'is-empty';
                        $statusIcon = 'fa-times-circle';
                        $statusText = 'Non configuré';
                    }
                @endphp
                <div class="fc-card {{ $statusClass }}" data-systeme="{{ $classe->scope['systeme'] ?? 'BTS' }}">
                    <div class="fc-card-header">
                        <div class="fc-card-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <div class="fc-card-name">{{ $classe->name }}</div>
                            <div class="fc-card-meta">{{ $classe->meta_line }}</div>
                            <div class="fc-card-students">{{ $classe->effectif }} étudiants</div>
                            @if(($configurationMode ?? 'global') === 'annual')
                                <div class="fc-card-students">{{ $classe->annual_overrides_count ?? 0 }} surcharge(s) annuelle(s)</div>
                            @endif
                        </div>
                    </div>
                    <div class="fc-stats">
                        <div class="fc-stat">
                            <div class="fc-stat-value">{{ $totalConfigured }}/{{ $totalRequired }}</div>
                            <div class="fc-stat-label">Obligatoires</div>
                        </div>
                        <div class="fc-stat">
                            <div class="fc-stat-value">{{ $classe->optionnels_configures > 0 ? $classe->optionnels_configures : '—' }}</div>
                            <div class="fc-stat-label">Optionnels</div>
                        </div>
                    </div>
                    <div style="text-align:center;">
                        <span class="fc-badge {{ $statusClass }}"><i class="fas {{ $statusIcon }}"></i>{{ $statusText }}</span>
                    </div>
                    <button type="button"
                            class="fc-btn-config configure-btn"
                            data-systeme="{{ $classe->scope['systeme'] ?? 'BTS' }}"
                            data-filiere-id="{{ $classe->scope['filiere_id'] ?? $classe->filiere->id }}"
                            data-parcours-id="{{ $classe->scope['parcours_id'] ?? '' }}"
                            data-niveau-id="{{ $classe->niveau->id }}"
                            data-filiere-name="{{ $classe->filiere->name }}"
                            data-parcours-name="{{ $classe->scope['parcours'] ?? '' }}"
                            data-niveau-name="{{ $classe->niveau->name }}"
                            data-label-scope="{{ $classe->scope['label_scope'] ?? $classe->name }}">
                        <i class="fas fa-cogs"></i>
                        <span>Configurer les frais</span>
                    </button>
                </div>
            @endforeach
        </div>
    @else
        <div class="fc-empty">Aucune combinaison disponible.</div>
    @endif

    <div class="modal fade" id="configurationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-cogs me-2"></i>Configuration des frais</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="fc-modal-info">
                        <strong>Contexte :</strong> <span id="modalClasseInfo">-</span><br>
                        <small id="modalModeInfo" style="color:var(--fc-muted);"></small>
                    </div>

                    <form id="configurationForm" method="POST" action="{{ route('esbtp.frais.update-configuration') }}">
                        @csrf
                        <input type="hidden" id="modalMode" name="mode" value="{{ $configurationMode ?? 'global' }}">
                        <input type="hidden" id="modalAnneeUniversitaireId" name="annee_universitaire_id" value="{{ $anneeUniversitaireId }}">
                        <input type="hidden" id="modalSysteme" name="systeme" value="BTS">
                        <input type="hidden" id="modalFiliereId" name="filiere_id">
                        <input type="hidden" id="modalParcoursId" name="parcours_id">
                        <input type="hidden" id="modalNiveauId" name="niveau_id">

                        <div class="fc-info-bar">
                            <div style="font-size:.76rem;font-weight:700;color:var(--fc-text);margin-bottom:.45rem;">Portée de l'enregistrement</div>
                            <div class="fc-tabs" style="margin-bottom:0;">
                                <button type="button" class="fc-tab-btn is-active" data-save-scope="single">Cette combinaison</button>
                                <button type="button" class="fc-tab-btn" data-save-scope="level">Tout le niveau</button>
                            </div>
                        </div>

                        <div id="bulkLevelPanel" class="fc-info-bar" style="display:none;">
                            <div style="width:100%;">
                                <div style="font-size:.8rem;font-weight:700;color:var(--fc-text);margin-bottom:.2rem;">Combinaisons ciblées</div>
                                <div style="font-size:.72rem;color:var(--fc-muted);margin-bottom:.6rem;">Toutes les combinaisons du niveau sont pré-cochées. Vous pouvez en retirer avant validation.</div>
                                <div id="bulkTargetsContainer"></div>
                                <div class="fc-target-actions">
                                    <button type="button" class="fc-tab-btn is-active" data-conflict-strategy="overwrite_all">Écraser tout</button>
                                    <button type="button" class="fc-tab-btn" data-conflict-strategy="create_missing_only">Créer seulement les manquantes</button>
                                </div>
                            </div>
                        </div>

                        <div id="categoriesContainer"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="saveConfigurationBtn" class="btn btn-primary">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<x-fab-encaisser />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = Array.from(document.querySelectorAll('[data-tab-target]'));
    const cards = Array.from(document.querySelectorAll('.fc-card[data-systeme]'));
    const modeButtons = Array.from(document.querySelectorAll('[data-mode-target]'));
    const annualModeSelector = document.getElementById('annualModeSelector');
    const annualYearSelect = document.getElementById('annualYearSelect');
    const categoriesContainer = document.getElementById('categoriesContainer');
    const bulkTargetsContainer = document.getElementById('bulkTargetsContainer');
    const saveScopeButtons = Array.from(document.querySelectorAll('[data-save-scope]'));
    const conflictButtons = Array.from(document.querySelectorAll('[data-conflict-strategy]'));
    const bulkLevelPanel = document.getElementById('bulkLevelPanel');
    const modalElement = document.getElementById('configurationModal');
    const modal = new bootstrap.Modal(modalElement);
    const state = {
        mode: document.getElementById('modalMode').value || 'global',
        actionType: 'single',
        conflictStrategy: 'overwrite_all',
        activeScope: null,
    };

    function renderLoadingState(message) {
        return `
            <div class="fc-loading-state" aria-live="polite" aria-busy="true">
                <div class="fc-loading-card">
                    <div class="fc-loading-head">
                        <div class="fc-skeleton is-icon"></div>
                        <div style="flex:1;">
                            <div class="fc-skeleton is-title"></div>
                            <div class="fc-skeleton is-subtitle"></div>
                        </div>
                    </div>
                    <div class="fc-skeleton is-block"></div>
                    <div class="fc-skeleton is-line"></div>
                </div>
                <div class="fc-loading-caption"><i class="fas fa-circle-notch fa-spin"></i>${message}</div>
            </div>
        `;
    }

    function setActiveTab(target) {
        tabButtons.forEach(button => button.classList.toggle('is-active', button.dataset.tabTarget === target));
        cards.forEach(card => {
            const systeme = card.dataset.systeme || 'BTS';
            card.style.display = target === 'ALL' || systeme === target ? '' : 'none';
        });
    }

    function updateUrlMode(nextMode) {
        const url = new URL(window.location.href);
        url.searchParams.set('mode', nextMode);
        if (nextMode === 'annual' && annualYearSelect && annualYearSelect.value) {
            url.searchParams.set('annee_universitaire_id', annualYearSelect.value);
        } else {
            url.searchParams.delete('annee_universitaire_id');
        }
        window.location.href = url.toString();
    }

    function setSaveScope(scope) {
        state.actionType = scope;
        saveScopeButtons.forEach(button => button.classList.toggle('is-active', button.dataset.saveScope === scope));
        bulkLevelPanel.style.display = scope === 'level' ? '' : 'none';
        if (scope === 'level' && state.activeScope) {
            loadBulkTargets();
        }
    }

    function setConflictStrategy(strategy) {
        state.conflictStrategy = strategy;
        conflictButtons.forEach(button => button.classList.toggle('is-active', button.dataset.conflictStrategy === strategy));
    }

    function currentAnnualYear() {
        return state.mode === 'annual' ? (annualYearSelect ? annualYearSelect.value : document.getElementById('modalAnneeUniversitaireId').value) : '';
    }

    function openConfigurationModal(button, actionType = 'single') {
        const systeme = button.dataset.systeme || 'BTS';
        const filiereId = button.dataset.filiereId || '';
        const parcoursId = button.dataset.parcoursId || '';
        const niveauId = button.dataset.niveauId;
        const labelScope = button.dataset.labelScope;

        state.activeScope = { systeme, filiereId, parcoursId, niveauId, labelScope };
        document.getElementById('modalMode').value = state.mode;
        document.getElementById('modalAnneeUniversitaireId').value = currentAnnualYear();
        document.getElementById('modalSysteme').value = systeme;
        document.getElementById('modalFiliereId').value = filiereId;
        document.getElementById('modalParcoursId').value = parcoursId;
        document.getElementById('modalNiveauId').value = niveauId;
        document.getElementById('modalClasseInfo').textContent = labelScope;
        document.getElementById('modalModeInfo').textContent = state.mode === 'annual'
            ? `Mode annuel · surcharge sur l'année ${annualYearSelect ? annualYearSelect.options[annualYearSelect.selectedIndex]?.text : ''}`
            : 'Mode global · template permanent';
        categoriesContainer.innerHTML = renderLoadingState('Chargement des catégories...');
        bulkTargetsContainer.innerHTML = renderLoadingState('Préparation des combinaisons du niveau...');
        setSaveScope(actionType);
        modal.show();
        loadCategories();
    }

    function loadCategories() {
        const params = new URLSearchParams({
            systeme: state.activeScope.systeme,
            niveau_id: state.activeScope.niveauId,
            type: 'mandatory',
            mode: state.mode,
        });
        if (state.activeScope.filiereId) params.set('filiere_id', state.activeScope.filiereId);
        if (state.activeScope.parcoursId) params.set('parcours_id', state.activeScope.parcoursId);
        if (currentAnnualYear()) params.set('annee_universitaire_id', currentAnnualYear());

        categoriesContainer.innerHTML = renderLoadingState('Chargement des catégories...');
        fetch(`{{ route('esbtp.frais.get-categories') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    categoriesContainer.innerHTML = '<div class="alert alert-warning">Impossible de charger les catégories.</div>';
                    return;
                }
                categoriesContainer.innerHTML = data.html;
            })
            .catch(() => {
                categoriesContainer.innerHTML = '<div class="alert alert-danger">Erreur de chargement des catégories.</div>';
            });
    }

    function loadBulkTargets() {
        const params = new URLSearchParams({
            systeme: state.activeScope.systeme,
            niveau_id: state.activeScope.niveauId,
            mode: state.mode,
        });
        if (currentAnnualYear()) params.set('annee_universitaire_id', currentAnnualYear());

        bulkTargetsContainer.innerHTML = renderLoadingState('Préparation des combinaisons du niveau...');
        fetch(`{{ url('/esbtp/frais/preview-level-targets') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    bulkTargetsContainer.innerHTML = '<div class="alert alert-warning">Impossible de charger les combinaisons du niveau.</div>';
                    return;
                }

                bulkTargetsContainer.innerHTML = `
                    <div style="margin-bottom:.55rem;">
                        <button type="button" class="fc-tab-btn" id="selectAllTargets">Tout cocher</button>
                        <button type="button" class="fc-tab-btn" id="clearAllTargets">Tout décocher</button>
                    </div>
                    <div class="fc-target-list">
                        ${data.targets.map(target => `
                            <label class="fc-target-item">
                                <input type="checkbox" class="bulk-target-checkbox" checked
                                    data-systeme="${target.systeme}"
                                    data-niveau-id="${target.niveau_id}"
                                    data-filiere-id="${target.filiere_id ?? ''}"
                                    data-parcours-id="${target.parcours_id ?? ''}">
                                <div>
                                    <strong>${target.label_scope}</strong><br>
                                    <small>${target.configured_count}/${target.mandatory_total} obligatoires${target.has_override ? ' · surcharge annuelle existante' : ''}</small>
                                </div>
                            </label>
                        `).join('')}
                    </div>
                `;

                document.getElementById('selectAllTargets')?.addEventListener('click', () => {
                    document.querySelectorAll('.bulk-target-checkbox').forEach(input => input.checked = true);
                });
                document.getElementById('clearAllTargets')?.addEventListener('click', () => {
                    document.querySelectorAll('.bulk-target-checkbox').forEach(input => input.checked = false);
                });
            })
            .catch(() => {
                bulkTargetsContainer.innerHTML = '<div class="alert alert-danger">Erreur de chargement des combinaisons du niveau.</div>';
            });
    }

    function collectCheckedTargets() {
        return Array.from(document.querySelectorAll('.bulk-target-checkbox:checked')).map(input => ({
            systeme: input.dataset.systeme,
            niveau_id: input.dataset.niveauId,
            filiere_id: input.dataset.filiereId || null,
            parcours_id: input.dataset.parcoursId || null,
        }));
    }

    function buildFormData() {
        const formData = new FormData(document.getElementById('configurationForm'));
        formData.set('mode', state.mode);
        if (currentAnnualYear()) {
            formData.set('annee_universitaire_id', currentAnnualYear());
        }
        return formData;
    }

    function refreshCards(affectedTargets) {
        (affectedTargets || []).forEach(target => {
            const selector = `.configure-btn[data-systeme="${target.systeme}"][data-niveau-id="${target.niveau_id}"]`
                + (target.filiere_id ? `[data-filiere-id="${target.filiere_id}"]` : '')
                + (target.parcours_id ? `[data-parcours-id="${target.parcours_id}"]` : '');
            const button = document.querySelector(selector);
            const card = button ? button.closest('.fc-card') : null;
            if (!card) return;
            card.classList.remove('is-empty', 'is-partial');
            card.classList.add('is-complete');
            const badge = card.querySelector('.fc-badge');
            if (badge) {
                badge.className = 'fc-badge is-complete';
                badge.innerHTML = '<i class="fas fa-check-circle"></i>Complet';
            }
        });
    }

    window.copyToAll = function (categoryId, sourceField) {
        const sourceInput = document.getElementById(`${sourceField}_${categoryId}`);
        if (!sourceInput || !sourceInput.value) return;
        ['amount_affecte', 'amount_reaffecte', 'amount_non_affecte'].forEach(field => {
            const input = document.getElementById(`${field}_${categoryId}`);
            if (input) input.value = sourceInput.value;
        });
    };

    tabButtons.forEach(button => button.addEventListener('click', () => setActiveTab(button.dataset.tabTarget)));
    modeButtons.forEach(button => button.addEventListener('click', () => updateUrlMode(button.dataset.modeTarget)));
    annualYearSelect?.addEventListener('change', () => updateUrlMode('annual'));
    saveScopeButtons.forEach(button => button.addEventListener('click', () => setSaveScope(button.dataset.saveScope)));
    conflictButtons.forEach(button => button.addEventListener('click', () => setConflictStrategy(button.dataset.conflictStrategy)));
    document.querySelectorAll('.configure-btn').forEach(button => button.addEventListener('click', () => openConfigurationModal(button, 'single')));
    document.querySelectorAll('.configure-level-btn').forEach(button => button.addEventListener('click', () => openConfigurationModal(button, 'level')));

    document.getElementById('saveConfigurationBtn').addEventListener('click', function () {
        const endpoint = state.actionType === 'level'
            ? `{{ url('/esbtp/frais/apply-level-configuration') }}`
            : `{{ route('esbtp.frais.update-configuration') }}`;
        const formData = buildFormData();

        if (state.actionType === 'level') {
            const targets = collectCheckedTargets();
            if (!targets.length) {
                alert('Sélectionnez au moins une combinaison du niveau.');
                return;
            }
            formData.set('conflict_strategy', state.conflictStrategy);
            targets.forEach((target, index) => {
                formData.append(`targets[${index}][systeme]`, target.systeme);
                formData.append(`targets[${index}][niveau_id]`, target.niveau_id);
                if (target.filiere_id) formData.append(`targets[${index}][filiere_id]`, target.filiere_id);
                if (target.parcours_id) formData.append(`targets[${index}][parcours_id]`, target.parcours_id);
            });
        }

        fetch(endpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Impossible d’enregistrer la configuration.');
                    return;
                }
                modal.hide();
                refreshCards(data.summary?.affected_targets || [state.activeScope]);
            })
            .catch(() => alert('Erreur de connexion'));
    });

    setActiveTab('ALL');
    setConflictStrategy('overwrite_all');
});
</script>
@endpush
