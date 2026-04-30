@extends('layouts.app')

@section('title', 'Matières pour ' . $classe->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
.classe-matieres-page .table tbody tr[data-linked="1"] {
    background-color: rgba(4, 83, 203, 0.05);
}

.classe-matieres-page .table tbody tr[data-linked="0"] {
    background-color: rgba(148, 163, 184, 0.08);
}

.classe-matieres-page .table tbody tr td {
    vertical-align: middle;
}

.classe-matieres-page .filter-select {
    max-width: 280px;
}

.classe-matieres-page tr[data-matiere-id] {
    position: relative;
    transition: background-color 0.3s ease;
}

.classe-matieres-page .matieres-row-highlight {
    position: absolute;
    top: 0;
    left: -65%;
    width: 150%;
    height: 100%;
    pointer-events: none;
    opacity: 0;
    transform: translateX(-65%) skewX(-12deg);
    background: linear-gradient(90deg, rgba(4, 83, 203, 0) 0%, rgba(4, 83, 203, 0.7) 50%, rgba(4, 83, 203, 0) 100%);
    z-index: 5;
}

.classe-matieres-page .matieres-row-highlight.detach {
    background: linear-gradient(90deg, rgba(220, 53, 69, 0) 0%, rgba(220, 53, 69, 0.7) 50%, rgba(220, 53, 69, 0) 100%);
}

.classe-matieres-page .matieres-row-highlight.animate {
    animation: matieres-row-highlight-move 2.4s ease-out forwards;
}

@keyframes matieres-row-highlight-move {
    0% { opacity: 0; transform: translateX(-65%) skewX(-12deg); }
    18% { opacity: 0.9; }
    55% { opacity: 0.7; }
    100% { opacity: 0; transform: translateX(115%) skewX(-12deg); }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi classe-matieres-page">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-graduation-cap me-2"></i>Matières de {{ $classe->name }}</h1>
                <p class="header-subtitle">
                    Gestion des matières rattachées à la combinaison
                    <strong>{{ optional($classe->filiere)->name ?? '—' }}</strong> /
                    <strong>{{ optional($classe->niveau)->name ?? '—' }}</strong>
                </p>
            </div>
            <div class="header-actions">
                <input type="search" id="classe-matieres-search" class="search-bar"
                       placeholder="Rechercher une matière (code, nom...)" />
                <a href="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour à la classe
                </a>
                @if(auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager']))
                    <a href="{{ route('esbtp.matieres.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-cogs me-1"></i>Gestion globale des matières
                    </a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="kpi-grid mb-lg">
            <div class="card-moderne kpi-card">
                <div class="kpi-title"><i class="fas fa-check-circle me-1"></i>Matières actives pour {{ $classe->name }}</div>
                <div class="kpi-value color-primary">{{ $stats['used_by_class'] }}</div>
                <div class="kpi-trend"><i class="fas fa-info-circle me-1"></i>Inclues dans les bulletins de la classe</div>
            </div>
            <div class="card-moderne kpi-card">
                <div class="kpi-title"><i class="fas fa-archive me-1"></i>Matières du catalogue non liées</div>
                <div class="kpi-value color-primary">{{ $stats['catalog_available'] }}</div>
                <div class="kpi-trend"><i class="fas fa-lightbulb me-1"></i>Disponibles pour étendre la formation</div>
            </div>
            <div class="card-moderne kpi-card">
                <div class="kpi-title"><i class="fas fa-users me-1"></i>Étudiants concernés</div>
                <div class="kpi-value color-primary">{{ $classe->nombre_etudiants ?? $classe->etudiants->count() }}</div>
                <div class="kpi-trend"><i class="fas fa-calendar me-1"></i>{{ optional($classe->annee)->name ?? 'Année courante' }}</div>
            </div>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Le bouton « Ajouter à la classe » dans la colonne Actions relie la matière à la combinaison
            <strong>{{ optional($classe->filiere)->name ?? '—' }}</strong> /
            <strong>{{ optional($classe->niveau)->name ?? '—' }}</strong> du catalogue global.<br>
            Toutes les classes de {{ optional($classe->niveau)->name ?? '—' }}
            {{ optional($classe->filiere)->name ?? '—' }} partageront automatiquement cette matière.
        </div>

        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="d-flex flex-wrap gap-3 align-items-end justify-content-between">
                    <div>
                        <label class="form-label text-muted text-uppercase fw-semibold">Affichage</label>
                        <select id="filter-link" class="form-select filter-select">
                            <option value="all" selected>Toutes les matières</option>
                            <option value="linked">Matières actives pour {{ $classe->name }}</option>
                            <option value="available">Matières du catalogue à ajouter</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div id="classe-matieres-results">
            @include('esbtp.classes.matieres.partials.results', [
                'matieres' => $matieres,
                'availableMatieres' => $availableMatieres,
                'stats' => $stats,
                'classe' => $classe,
            ])
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CLASSE_FILIERE_ID = @json($classe->filiere_id);
const CLASSE_NIVEAU_ID = @json($classe->niveau_etude_id);
const CLASSE_FILIERE_LABEL = @json(optional($classe->filiere)->code ?? optional($classe->filiere)->name ?? 'Filière');
const CLASSE_NIVEAU_LABEL = @json(optional($classe->niveau)->code ?? optional($classe->niveau)->name ?? 'Niveau');
const CLASSE_COMBO_LABEL = `${CLASSE_FILIERE_LABEL} · ${CLASSE_NIVEAU_LABEL}`;

function showToast(type, message) {
    if (window.toastr && typeof window.toastr[type] === 'function') {
        window.toastr[type](message);
    } else {
        console[type === 'error' ? 'error' : 'log'](message);
    }
}

async function toggleCombination(matiereId, action, button) {
    if (CLASSE_FILIERE_ID === null || CLASSE_NIVEAU_ID === null) {
        showToast('error', 'Cette classe n\'a pas de filière ou de niveau défini.');
        return;
    }

    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Traitement…';

    try {
        const liaisonResponse = await fetch(`/esbtp/matieres/${matiereId}/liaisons`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!liaisonResponse.ok) {
            throw new Error(`HTTP ${liaisonResponse.status}`);
        }

        const liaisonData = await liaisonResponse.json();
        if (!liaisonData.success) {
            throw new Error(liaisonData.message || 'Impossible de récupérer les liaisons.');
        }

        // liaisonData.liaisons = [{filiere_id, niveau_id}, ...]
        let liaisons = (liaisonData.liaisons || []).map(l => ({
            filiere_id: l.filiere_id,
            niveau_id: l.niveau_id
        }));

        const comboKey = l => `${l.filiere_id}-${l.niveau_id}`;
        const thisKey = `${CLASSE_FILIERE_ID}-${CLASSE_NIVEAU_ID}`;

        if (action === 'add') {
            if (!liaisons.some(l => comboKey(l) === thisKey)) {
                liaisons.push({ filiere_id: CLASSE_FILIERE_ID, niveau_id: CLASSE_NIVEAU_ID });
            }
        } else if (action === 'remove') {
            liaisons = liaisons.filter(l => comboKey(l) !== thisKey);
        }

        const updateResponse = await fetch(`/esbtp/matieres/${matiereId}/update-liaisons`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ liaisons })
        });

        if (!updateResponse.ok) {
            const errorPayload = await updateResponse.json().catch(() => ({}));
            throw new Error(errorPayload.message || `HTTP ${updateResponse.status}`);
        }

        const updateData = await updateResponse.json();
        if (!updateData.success) {
            throw new Error(updateData.message || 'La mise à jour a échoué.');
        }

        showToast('success', updateData.message || 'Mise à jour enregistrée.');

        const row = document.querySelector(`tr[data-matiere-id="${matiereId}"]`);
        if (row) {
            row.dataset.linked = action === 'add' ? '1' : '0';
            row.classList.toggle('is-linked', action === 'add');
            const toggleBtn = row.querySelector('.toggle-combination-btn');
            if (toggleBtn) {
                toggleBtn.dataset.action = action === 'add' ? 'remove' : 'add';
                toggleBtn.classList.toggle('btn-outline-primary', action !== 'add');
                toggleBtn.classList.toggle('btn-outline-danger', action === 'add');
                toggleBtn.innerHTML = `<i class="fas ${action === 'add' ? 'fa-minus-circle' : 'fa-plus-circle'} me-1"></i>` + (action === 'add' ? 'Retirer de la classe' : 'Ajouter à la classe');
                toggleBtn.disabled = false;
            }

            const statusBadge = row.querySelector('[data-role="class-status"]');
            if (statusBadge) {
                statusBadge.className = action === 'add' ? 'badge bg-primary' : 'badge bg-secondary text-dark';
                statusBadge.textContent = action === 'add' ? 'Enseignée dans cette classe' : 'Disponible dans le catalogue';
            }

            const combosContainer = row.querySelector('.combo-badges');
            if (combosContainer) {
                let combos = [];
                try {
                    combos = JSON.parse(combosContainer.dataset.combos || '[]');
                } catch (e) {
                    combos = [];
                }
                const comboKey = `${CLASSE_FILIERE_ID}-${CLASSE_NIVEAU_ID}`;

                if (action === 'add') {
                    const exists = combos.some(c => `${c.filiere_id}-${c.niveau_id}` === comboKey);
                    if (!exists) {
                        combos.push({
                            filiere_id: CLASSE_FILIERE_ID,
                            niveau_id: CLASSE_NIVEAU_ID,
                            label: CLASSE_COMBO_LABEL
                        });
                    }
                } else {
                    combos = combos.filter(c => `${c.filiere_id}-${c.niveau_id}` !== comboKey);
                }

                combosContainer.dataset.combos = JSON.stringify(combos);
                renderCombos(combosContainer);
            }

            triggerRowHighlight(row, action === 'add' ? 'attach' : 'detach');
            button.disabled = false;
        } else {
            button.disabled = false;
            button.innerHTML = originalHtml;
        }
        if (typeof window.filterClasseMatieresRows === 'function') {
            window.filterClasseMatieresRows();
        }
    } catch (error) {
        debugError(error);
        showToast('error', error.message || 'Erreur lors de la mise à jour.');
        button.disabled = false;
        button.innerHTML = originalHtml;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('classe-matieres-search');
    const filterLink = document.getElementById('filter-link');
    const rows = Array.from(document.querySelectorAll('.classe-matieres-table tbody tr'));

    function filterRows() {
        const term = (searchInput?.value || '').toLowerCase().trim();
        const linkFilter = filterLink?.value || 'all';

        rows.forEach(row => {
            const name = row.dataset.name || '';
            const isLinked = row.dataset.linked === '1';

            const matchesSearch = term === '' || name.includes(term);
            const matchesLink = linkFilter === 'all'
                ? true
                : linkFilter === 'linked'
                    ? isLinked
                    : !isLinked;

            row.style.display = matchesSearch && matchesLink ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', filterRows);
    filterLink?.addEventListener('change', filterRows);

    document.querySelectorAll('.toggle-combination-btn').forEach(button => {
        button.addEventListener('click', () => toggleCombination(button.dataset.matiereId, button.dataset.action, button));
    });

    document.querySelectorAll('.combo-badges').forEach(container => renderCombos(container));
    filterRows();
    window.filterClasseMatieresRows = filterRows;
});

function triggerRowHighlight(row, action = 'attach') {
    const highlight = document.createElement('div');
    highlight.className = 'matieres-row-highlight';
    if (action === 'detach') {
        highlight.classList.add('detach');
    }
    row.appendChild(highlight);
    requestAnimationFrame(() => highlight.classList.add('animate'));
    highlight.addEventListener('animationend', () => highlight.remove());
}

function renderCombos(container) {
    let combos = [];
    try {
        combos = JSON.parse(container.dataset.combos || '[]');
    } catch (e) {
        combos = [];
    }

    container.innerHTML = '';
    if (!combos.length) {
        container.innerHTML = '<span class="text-muted">Aucune combinaison enregistrée</span>';
        return;
    }

    const maxVisible = 3;
    combos.slice(0, maxVisible).forEach(combo => {
        const badge = document.createElement('span');
        badge.className = 'badge bg-light text-muted me-1';
        badge.dataset.combo = `${combo.filiere_id}-${combo.niveau_id}`;
        badge.textContent = combo.label;
        container.appendChild(badge);
    });

    if (combos.length > maxVisible) {
        const remain = combos.length - maxVisible;
        const badge = document.createElement('span');
        badge.className = 'badge bg-info text-white';
        badge.textContent = `+${remain}`;
        container.appendChild(badge);
    }
}
</script>
@endpush
