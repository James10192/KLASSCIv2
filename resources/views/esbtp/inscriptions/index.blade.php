@extends('layouts.app')

@section('title', 'Gestion des Inscriptions')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<!-- Debug: Afficher les données de session -->
@if(session('inscriptions_problemes'))
    <script>debugLog('Inscriptions avec problèmes:', @json(session('inscriptions_problemes')));</script>
@endif

<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-user-graduate me-2"></i>Gestion des Inscriptions</h1>
                <p class="header-subtitle">Consultez et gérez toutes les inscriptions de l'établissement</p>
            </div>
            <div class="header-actions">
                <input type="search" class="search-bar" placeholder="Rechercher une inscription..." value="{{ request('search') }}">
                @can('inscriptions.validate')
                <a href="{{ route('esbtp.inscriptions.administration') }}" class="btn-acasi secondary">
                    <i class="fas fa-user-check"></i>Administration
                </a>
                @endcan
                @can('inscriptions.create')
                <a href="{{ route('esbtp.inscriptions.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Nouvelle Inscription
                </a>
                @endcan
            </div>
        </div>

        <!-- Filtre année académique courante -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-calendar-alt me-2"></i>Année Académique Active
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ $anneeEnCours->id ?? '' }}" selected>
                                {{ $anneeEnCours->name ?? 'Aucune année définie' }} (Année en cours)
                            </option>
                        </select>
                    </div>
                    <button type="button" class="btn-acasi secondary" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                        <i class="fas fa-info-circle"></i>Changer d'année
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Les inscriptions affichées correspondent à l'année académique courante. 
                        @if($inscriptions->isEmpty())
                            <strong class="text-warning">Aucune inscription trouvée pour cette année.</strong>
                        @endif
                    </small>
                </div>
                @if($inscriptions->isEmpty())
                    <div class="mt-3">
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <strong>Aucune inscription pour l'année {{ $anneeEnCours->name ?? 'courante' }}</strong><br>
                                <small>Il y a {{ \App\Models\ESBTPInscription::count() }} inscriptions au total dans la base, mais aucune pour l'année académique active. 
                                Utilisez le bouton "Changer d'année" pour consulter les inscriptions d'autres années.</small>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Section principale des inscriptions -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-filter"></i>
                    Filtres de recherche
                </div>
                <div class="main-card-subtitle">Filtrer les inscriptions par critères spécifiques</div>
            </div>

            <div class="main-card-body">
                <form method="GET" action="{{ route('esbtp.inscriptions.index') }}" id="inscriptions-filter-form" class="mb-4">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="filter-search" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="filter-search" name="search" value="{{ request('search') }}" placeholder="Matricule, nom, numéro d'inscription...">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="filiere" class="form-label">Filière</label>
                            <select class="form-select" id="filiere" name="filiere">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres as $fil)
                                    <option value="{{ $fil->id }}" {{ request('filiere') == $fil->id ? 'selected' : '' }}>
                                        {{ $fil->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="niveau" class="form-label">Niveau d'études</label>
                            <select class="form-select" id="niveau" name="niveau">
                                <option value="">Tous les niveaux</option>
                                @foreach($niveaux as $niv)
                                    <option value="{{ $niv->id }}" {{ request('niveau') == $niv->id ? 'selected' : '' }}>
                                        {{ $niv->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="annee" class="form-label">Année universitaire</label>
                            <select class="form-select" id="annee" name="annee">
                                <option value="">Toutes les années</option>
                                @foreach($annees as $an)
                                    <option value="{{ $an->id }}" {{ request('annee') == $an->id ? 'selected' : '' }}>
                                        {{ $an->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" {{ request('status', 'active') == 'all' ? 'selected' : '' }}>Toutes</option>
                                <option value="active" {{ request('status', 'active') == 'active' ? 'selected' : '' }}>Actives (validées)</option>
                                <option value="non_validee" {{ request('status') == 'non_validee' ? 'selected' : '' }}>Non validées ({{ $stats['non_validees'] ?? 0 }})</option>
                                <option value="en_attente" {{ request('status') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                                <option value="annulée" {{ request('status') == 'annulée' ? 'selected' : '' }}>Annulées</option>
                                <option value="terminée" {{ request('status') == 'terminée' ? 'selected' : '' }}>Terminées</option>
                            </select>
                        </div>
                        <div class="col-md-1 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn-acasi primary w-100">Filtrer</button>
                        </div>
                    </div>
                </form>

                <div id="inscriptions-results">
                    @include('esbtp.inscriptions.partials.results', ['inscriptions' => $inscriptions])
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Barre d'actions groupées pour validation -->
@can('inscriptions.validate')
<div id="bulk-actions-bar" style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
     background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; padding: 15px 30px;
     border-radius: 50px; box-shadow: 0 10px 40px rgba(4, 83, 203, 0.4); z-index: 1050;
     animation: slideUp 0.3s ease-out;">
    <div style="display: flex; align-items: center; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
            <span id="selected-count" style="font-weight: 600; font-size: 1.1rem;">0</span>
            <span style="opacity: 0.9;">inscription(s) sélectionnée(s)</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-light btn-sm" onclick="bulkValiderInscriptions()"
                    style="padding: 8px 20px; border-radius: 25px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <i class="fas fa-check-double me-1"></i>Valider la sélection
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" onclick="clearInscriptionSelection()"
                    style="padding: 8px 20px; border-radius: 25px; font-weight: 600;">
                <i class="fas fa-times me-1"></i>Annuler
            </button>
        </div>
    </div>
</div>

<style>
@keyframes slideUp {
    from {
        bottom: -100px;
        opacity: 0;
    }
    to {
        bottom: 20px;
        opacity: 1;
    }
}
</style>
@endcan

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close btn-close" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les inscriptions d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les inscriptions affichées se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des inscriptions dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Actuellement :</strong><br>
                    • Année courante = {{ $anneeEnCours->name ?? 'Non définie' }}<br>
                    • Inscriptions visibles = {{ $inscriptions->count() }} sur {{ \App\Models\ESBTPInscription::count() }} au total
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#yearChangeModal').modal('hide');">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Styles pour le filtre année */
.year-selector {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    color: #374151;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
}

/* Variables CSS pour compatibilité */
:root {
    --space-sm: 0.5rem;
    --space-md: 1rem;
    --space-lg: 1.5rem;
    --text-small: 12px;
    --text-secondary: #6b7280;
}

.card-moderne {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.p-lg {
    padding: 1.5rem;
}

.mb-lg {
    margin-bottom: 1.5rem;
}

.mb-md {
    margin-bottom: 1rem;
}

.mt-3 {
    margin-top: 1rem;
}

.me-1 {
    margin-right: 0.25rem;
}

.me-2 {
    margin-right: 0.5rem;
}

.text-muted {
    color: #6b7280;
}

.text-warning {
    color: #f59e0b;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid transparent;
}

.alert-warning {
    background-color: #fef3c7;
    border-color: #f59e0b;
    color: #92400e;
}

.d-flex {
    display: flex;
}

.align-items-center {
    align-items: center;
}

tr[data-inscription-id] {
    position: relative;
    overflow: hidden;
}

tr[data-inscription-id].is-loading {
    opacity: 0.85;
}

.inscription-actions-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.inscription-actions-buttons {
    display: inline-flex;
}

.inscription-actions-spinner {
    display: none;
    min-width: 32px;
}

.inscription-actions-wrapper.is-loading .inscription-actions-buttons {
    display: none !important;
}

.inscription-actions-wrapper.is-loading .inscription-actions-spinner {
    display: flex !important;
    align-items: center;
    justify-content: center;
}

.inscription-row-highlight {
    position: absolute;
    top: 0;
    left: -80%;
    width: 160%;
    height: 100%;
    opacity: 0;
    pointer-events: none;
    transform: translateX(-65%) skewX(-12deg);
    background: linear-gradient(90deg, rgba(40, 167, 69, 0) 0%, rgba(40, 167, 69, 0.75) 50%, rgba(40, 167, 69, 0) 100%);
    transition: opacity 0.2s ease;
    z-index: 5;
}

.inscription-row-highlight.reject {
    background: linear-gradient(90deg, rgba(220, 53, 69, 0) 0%, rgba(220, 53, 69, 0.75) 50%, rgba(220, 53, 69, 0) 100%);
}

.inscription-row-highlight.animate {
    animation: inscription-row-highlight-move 3.2s ease-out forwards;
}

.inscription-row-flash {
    animation: inscription-row-flash 0.8s ease-in-out;
}

@keyframes inscription-row-highlight-move {
    0% {
        opacity: 0;
        transform: translateX(-65%) skewX(-12deg);
    }
    18% {
        opacity: 0.92;
    }
    55% {
        opacity: 0.7;
    }
    100% {
        opacity: 0;
        transform: translateX(115%) skewX(-12deg);
    }
}

@keyframes inscription-row-flash {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(40, 167, 69, 0.12);
    }
    100% {
        background-color: transparent;
    }
}

.inscription-row-flash.reject {
    animation-name: inscription-row-flash-reject;
}

@keyframes inscription-row-flash-reject {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(220, 53, 69, 0.12);
    }
    100% {
        background-color: transparent;
    }
}
</style>
@endpush

@push('scripts')
<script>
const INSCRIPTION_HIGHLIGHT_DURATION = 3200;
const INSCRIPTION_STATUS_PASS_RATIO = 0.8;

function setInscriptionRowLoadingState(inscriptionId, isLoading) {
    const row = document.querySelector(`tr[data-inscription-id="${inscriptionId}"]`);
    if (!row) {
        return;
    }

    row.classList.toggle('is-loading', Boolean(isLoading));

    const actionsWrapper = row.querySelector('.inscription-actions-wrapper');
    if (actionsWrapper) {
        actionsWrapper.classList.toggle('is-loading', Boolean(isLoading));
    }
}
window.setInscriptionRowLoadingState = setInscriptionRowLoadingState;

function triggerInscriptionRowHighlight(row, actionType = 'update', options = {}) {
    if (!row) {
        return;
    }

    const onStatusPassed = typeof options.onStatusPassed === 'function' ? options.onStatusPassed : null;
    const isReject = ['reject', 'cancel', 'danger', 'delete'].includes(actionType);

    row.classList.remove('inscription-row-flash', 'reject');
    void row.offsetWidth;

    const highlight = document.createElement('div');
    highlight.className = 'inscription-row-highlight';
    if (isReject) {
        highlight.classList.add('reject');
    }

    row.appendChild(highlight);

    requestAnimationFrame(() => {
        highlight.classList.add('animate');
    });

    if (onStatusPassed) {
        setTimeout(() => {
            onStatusPassed(highlight);
        }, INSCRIPTION_HIGHLIGHT_DURATION * INSCRIPTION_STATUS_PASS_RATIO);
    }

    const cleanup = () => {
        highlight.removeEventListener('animationend', cleanup);
        highlight.remove();
    };

    highlight.addEventListener('animationend', cleanup);

    row.classList.add('inscription-row-flash');
    if (isReject) {
        row.classList.add('reject');
    }

    setTimeout(() => {
        row.classList.remove('inscription-row-flash', 'reject');
    }, 1200);
}
window.triggerInscriptionRowHighlight = triggerInscriptionRowHighlight;

function showYearChangeInfo() {
    debugLog('Tentative ouverture modal');
    
    // Essayer avec différentes méthodes Bootstrap
    if (typeof bootstrap !== 'undefined') {
        // Bootstrap 5
        const modal = new bootstrap.Modal(document.getElementById('yearChangeModal'));
        modal.show();
    } else if (typeof $ !== 'undefined' && $.fn.modal) {
        // Bootstrap 4 avec jQuery
        $('#yearChangeModal').modal('show');
    } else {
        // Fallback - afficher directement
        const modal = document.getElementById('yearChangeModal');
        if (modal) {
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
            
            // Ajouter backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modal-backdrop';
            document.body.appendChild(backdrop);
        }
    }
}

// Fermer le modal manuellement si nécessaire
function closeYearModal() {
    const modal = document.getElementById('yearChangeModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        
        // Supprimer backdrop
        const backdrop = document.getElementById('modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }
}

// Gérer les événements de fermeture
document.addEventListener('DOMContentLoaded', function() {
    // Fermeture avec X
    const closeButton = document.querySelector('#yearChangeModal .close');
    if (closeButton) {
        closeButton.addEventListener('click', closeYearModal);
    }
    
    // Fermeture avec bouton Fermer
    const cancelButton = document.querySelector('#yearChangeModal .btn-secondary');
    if (cancelButton) {
        cancelButton.addEventListener('click', closeYearModal);
    }
    
    // Fermeture en cliquant sur le backdrop
    const modal = document.getElementById('yearChangeModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeYearModal();
            }
        });
    }
});

function initInscriptionsSelect2() {
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        $('#filiere, #niveau, #annee, #status').select2({
            placeholder: 'Sélectionnez une option',
            allowClear: true
        });
    }
}

function initInscriptionsTooltips(context = document) {
    if (typeof bootstrap === 'undefined') {
        return;
    }

    const tooltipTriggerList = [].slice.call(context.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        bootstrap.Tooltip.getInstance(tooltipTriggerEl)?.dispose();
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// ============================================================================
// GESTION DES ACTIONS GROUPÉES POUR LES INSCRIPTIONS
// ============================================================================

// Fonction pour mettre à jour le compteur et afficher/masquer la barre d'actions
function updateInscriptionSelectionCount() {
    const checkboxes = document.querySelectorAll('.inscription-checkbox:checked');
    const count = checkboxes.length;
    const bulkBar = document.getElementById('bulk-actions-bar');
    const selectedCountSpan = document.getElementById('selected-count');

    if (selectedCountSpan) {
        selectedCountSpan.textContent = count;
    }

    if (bulkBar) {
        if (count > 0) {
            bulkBar.style.display = 'block';
        } else {
            bulkBar.style.display = 'none';
        }
    }
}

function bindBulkSelectionHandlers() {
    const selectAllCheckbox = document.getElementById('select-all-inscriptions');
    if (selectAllCheckbox) {
        selectAllCheckbox.onchange = function () {
            const checkboxes = document.querySelectorAll('.inscription-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateInscriptionSelectionCount();
        };
    }

    document.querySelectorAll('.inscription-checkbox').forEach(checkbox => {
        checkbox.onchange = function () {
            updateInscriptionSelectionCount();

            const allCheckboxes = document.querySelectorAll('.inscription-checkbox');
            const checkedCheckboxes = document.querySelectorAll('.inscription-checkbox:checked');
            const selectAll = document.getElementById('select-all-inscriptions');

            if (selectAll) {
                selectAll.checked = allCheckboxes.length === checkedCheckboxes.length && allCheckboxes.length > 0;
            }
        };
    });
}

// Fonction pour effacer la sélection
function clearInscriptionSelection() {
    document.querySelectorAll('.inscription-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    const selectAll = document.getElementById('select-all-inscriptions');
    if (selectAll) {
        selectAll.checked = false;
    }
    updateInscriptionSelectionCount();
}

// Fonction pour valider les inscriptions sélectionnées (AJAX)
function bulkValiderInscriptions() {
    const checkboxes = document.querySelectorAll('.inscription-checkbox:checked');
    const inscriptionIds = Array.from(checkboxes).map(cb => cb.value);

    if (inscriptionIds.length === 0) {
        alert('Veuillez sélectionner au moins une inscription à valider.');
        return;
    }

    const confirmMessage = `Êtes-vous sûr de vouloir valider ${inscriptionIds.length} inscription(s) ?\n\nLe système va automatiquement :\n• Valider les inscriptions avec paiements validés\n• Auto-valider les paiements en attente si nécessaire\n• Valider les inscriptions sans paiement (aligné sur validation unitaire)\n• Envoyer les notifications aux étudiants concernés`;

    if (!confirm(confirmMessage)) {
        return;
    }

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    inscriptionIds.forEach(id => formData.append('inscription_ids[]', id));

    // Mettre toutes les lignes en loading
    inscriptionIds.forEach(id => {
        const row = document.querySelector(`tr[data-inscription-id="${id}"]`);
        if (row) {
            row.classList.add('is-loading');
            const wrapper = row.querySelector('.inscription-actions-wrapper');
            if (wrapper) wrapper.classList.add('is-loading');
        }
    });

    fetch("{{ route('esbtp.inscriptions.bulk-valider') }}", {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (!response.ok) throw new Error('Erreur lors de la validation.');
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Validation échouée.');
            inscriptionIds.forEach(id => {
                const row = document.querySelector(`tr[data-inscription-id="${id}"]`);
                if (row) {
                    row.classList.remove('is-loading');
                    const wrapper = row.querySelector('.inscription-actions-wrapper');
                    if (wrapper) wrapper.classList.remove('is-loading');
                }
            });
            return;
        }

        if (data.message) {
            alert(data.message);
        }

        const problems = data.inscriptions_problemes || {};

        // Rafraîchir chaque ligne avec highlight
        inscriptionIds.forEach(id => {
            const actionType = problems[id] ? 'reject' : 'validate';
            refreshInscriptionLigne(id, actionType);
        });

        clearInscriptionSelection();
    })
    .catch(error => {
        alert(error.message || 'Erreur lors de la validation.');
        inscriptionIds.forEach(id => {
            const row = document.querySelector(`tr[data-inscription-id="${id}"]`);
            if (row) {
                row.classList.remove('is-loading');
                const wrapper = row.querySelector('.inscription-actions-wrapper');
                if (wrapper) wrapper.classList.remove('is-loading');
            }
        });
    });
}

function refreshInscriptionLigne(inscriptionId, actionType) {
    fetch(`/esbtp/inscriptions/${inscriptionId}/refresh-ligne?context=index`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Erreur rafraîchissement ligne.');
        return response.json();
    })
    .then(data => {
        if (!data.success || !data.html) return;

        const template = document.createElement('template');
        template.innerHTML = data.html.trim();
        const newRow = template.content.querySelector('tr');
        if (!newRow) return;

        const existingRow = document.querySelector(`tr[data-inscription-id="${inscriptionId}"]`);
        if (existingRow) {
            existingRow.replaceWith(newRow);
            // Appeler le highlight depuis le DOMContentLoaded scope si disponible
            if (typeof window._triggerInscriptionRowHighlight === 'function') {
                window._triggerInscriptionRowHighlight(newRow, actionType);
            }
        }
    })
    .catch(() => {});
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('inscriptions-filter-form');
    const resultsContainer = document.getElementById('inscriptions-results');
    const submitButton = form ? form.querySelector('button[type=\"submit\"]') : null;
    const filterSelects = form ? form.querySelectorAll('select') : [];
    const headerSearch = document.querySelector('.dashboard-header .search-bar');
    const formSearchInput = form ? form.querySelector('#filter-search') : null;

    const INSCRIPTION_HIGHLIGHT_DURATION = 3200;
    const INSCRIPTION_STATUS_PASS_RATIO = 0.8;

    function setInscriptionRowLoadingState(inscriptionId, isLoading) {
        const row = document.querySelector(`tr[data-inscription-id=\"${inscriptionId}\"]`);
        if (!row) {
            return;
        }

        row.classList.toggle('is-loading', Boolean(isLoading));

        const actionsWrapper = row.querySelector('.inscription-actions-wrapper');
        if (actionsWrapper) {
            actionsWrapper.classList.toggle('is-loading', Boolean(isLoading));
        }
    }

    function triggerInscriptionRowHighlight(row, actionType = 'update', options = {}) {
        if (!row) {
            return;
        }

        const onStatusPassed = typeof options.onStatusPassed === 'function' ? options.onStatusPassed : null;
        const isReject = ['reject', 'cancel', 'danger'].includes(actionType);

        row.classList.remove('inscription-row-flash', 'reject');
        void row.offsetWidth;

        const highlight = document.createElement('div');
        highlight.className = 'inscription-row-highlight';
        if (isReject) {
            highlight.classList.add('reject');
        }

        row.appendChild(highlight);

        requestAnimationFrame(() => {
            highlight.classList.add('animate');
        });

        if (onStatusPassed) {
            setTimeout(() => {
                onStatusPassed(highlight);
            }, INSCRIPTION_HIGHLIGHT_DURATION * INSCRIPTION_STATUS_PASS_RATIO);
        }

        const cleanup = () => {
            highlight.removeEventListener('animationend', cleanup);
            highlight.remove();
        };

        highlight.addEventListener('animationend', cleanup);

        row.classList.add('inscription-row-flash');
        if (isReject) {
            row.classList.add('reject');
        }

        setTimeout(() => {
            row.classList.remove('inscription-row-flash', 'reject');
        }, 1200);
    }

    // Exposer pour refreshInscriptionLigne (hors scope DOMContentLoaded)
    window._triggerInscriptionRowHighlight = triggerInscriptionRowHighlight;

    initInscriptionsSelect2();
    initInscriptionsTooltips();
    bindBulkSelectionHandlers();
    bindPaginationLinks();

    if (headerSearch && formSearchInput) {
        headerSearch.value = headerSearch.value || formSearchInput.value || '';

        headerSearch.addEventListener('input', function () {
            formSearchInput.value = headerSearch.value;
        });

        headerSearch.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                formSearchInput.value = headerSearch.value;
                submitFilterForm();
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();
            submitFilterForm();
            return false;
        });
    }

    filterSelects.forEach(select => {
        select.addEventListener('change', () => submitFilterForm());
    });

    function submitFilterForm() {
        if (!form) {
            return;
        }

        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        const targetUrl = `${form.action}?${params.toString()}`;
        fetchResults(targetUrl, { pushState: true });
    }

    function bindPaginationLinks() {
        resultsContainer.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                fetchResults(this.href, { pushState: true });
            });
        });
    }

    function setLoading(isLoading) {
        if (submitButton) {
            submitButton.disabled = isLoading;
        }

        if (isLoading) {
            resultsContainer.classList.add('opacity-50');
        } else {
            resultsContainer.classList.remove('opacity-50');
        }
    }

    function fetchResults(url, options = {}) {
        if (!url) {
            return;
        }

        setLoading(true);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des inscriptions.');
            }
            return response.json();
        })
        .then(data => {
            resultsContainer.innerHTML = data.html;
            if (options.pushState !== false) {
                window.history.pushState({ url: data.url }, '', data.url);
            }
            initInscriptionsTooltips(resultsContainer);
            bindBulkSelectionHandlers();
            bindPaginationLinks();
            clearInscriptionSelection();
        })
        .catch(error => {
            debugError(error);
            alert('Impossible de charger les inscriptions. Veuillez réessayer.');
        })
        .finally(() => setLoading(false));
    }

    if (window.history && window.history.replaceState) {
        window.history.replaceState({ url: window.location.href }, '', window.location.href);
    }

    window.addEventListener('popstate', function (event) {
        const targetUrl = event.state?.url || window.location.href;
        fetchResults(targetUrl, { pushState: false });
    });
});

// === Fonctions pour actions rapides ===

/**
 * Ouvrir modal pour valider un paiement en attente
 */
function ouvrirModalValiderPaiement(inscriptionId) {
    fetch(`/esbtp/inscriptions/${inscriptionId}/paiement-en-attente`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.paiement) {
                document.getElementById('valider_inscription_id').value = inscriptionId;
                document.getElementById('valider_paiement_id').value = data.paiement.id;
                document.getElementById('valider_montant').value = new Intl.NumberFormat('fr-FR').format(data.paiement.montant) + ' FCFA';
                document.getElementById('valider_mode').value = data.paiement.mode_paiement || 'N/A';
                document.getElementById('valider_reference').value = data.paiement.reference_paiement || 'N/A';
                document.getElementById('validerPaiementInfo').textContent = `Paiement de ${data.paiement.etudiant.nom} ${data.paiement.etudiant.prenoms}`;

                // Configurer l'action du formulaire
                document.getElementById('formValiderPaiement').action = `/esbtp/paiements/${data.paiement.id}/valider-rapide`;

                const modal = new bootstrap.Modal(document.getElementById('modalValiderPaiement'));
                modal.show();
            } else {
                alert('Impossible de récupérer les informations du paiement: ' + (data.message || ''));
            }
        })
        .catch(error => {
            debugError(error);
            alert('Erreur lors du chargement des données');
        });
}

/**
 * Ouvrir modal pour changer la classe
 */
function ouvrirModalChangerClasse(inscriptionId) {
    fetch(`/esbtp/inscriptions/${inscriptionId}/classes-alternatives`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('changer_inscription_id').value = inscriptionId;
                document.getElementById('changer_ancienne_classe').value = data.classeActuelle.name;

                // Remplir le select des classes disponibles
                const select = document.getElementById('changer_nouvelle_classe');
                select.innerHTML = '<option value="">Sélectionnez une classe</option>';

                data.classesAlternatives.forEach(classe => {
                    const option = document.createElement('option');
                    option.value = classe.id;

                    // Afficher statut de disponibilité
                    if (classe.is_available) {
                        option.textContent = `${classe.name} (${classe.places_disponibles}/${classe.places_totales} places disponibles)`;
                    } else {
                        option.textContent = `${classe.name} (COMPLET - ${classe.places_disponibles}/${classe.places_totales})`;
                        option.style.color = '#dc3545'; // Rouge pour classe pleine
                        option.style.fontWeight = 'bold';
                    }

                    option.dataset.placesDisponibles = classe.places_disponibles;
                    option.dataset.isAvailable = classe.is_available;
                    select.appendChild(option);
                });

                // Event listener pour afficher les places disponibles et alerter si plein
                select.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption.value) {
                        const isAvailable = selectedOption.dataset.isAvailable === 'true';
                        const placesDisponibles = selectedOption.dataset.placesDisponibles;

                        document.getElementById('classeDispoInfo').style.display = 'block';

                        if (isAvailable) {
                            document.getElementById('classeDispoText').textContent =
                                `✓ Places disponibles: ${placesDisponibles}`;
                            document.getElementById('classeDispoText').style.color = '#28a745';
                        } else {
                            document.getElementById('classeDispoText').textContent =
                                `⚠ Classe complète (${placesDisponibles} places disponibles)`;
                            document.getElementById('classeDispoText').style.color = '#dc3545';
                        }
                    } else {
                        document.getElementById('classeDispoInfo').style.display = 'none';
                    }
                });

                // Configurer l'action du formulaire
                document.getElementById('formChangerClasse').action = `/esbtp/inscriptions/${inscriptionId}/changer-classe-rapide`;

                const modal = new bootstrap.Modal(document.getElementById('modalChangerClasse'));
                modal.show();
            } else {
                alert(data.message || 'Impossible de récupérer les classes alternatives');
            }
        })
        .catch(error => {
            debugError(error);
            alert('Erreur lors du chargement des données');
        });
}

/**
 * Ouvrir modal pour créer un paiement
 */
function ouvrirModalCreerPaiement(inscriptionId) {
    fetch(`/esbtp/inscriptions/${inscriptionId}/data`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.inscription) {
                document.getElementById('creer_inscription_id').value = inscriptionId;
                document.getElementById('creer_etudiant_id').value = data.inscription.etudiant_id;
                document.getElementById('creer_annee_id').value = data.inscription.annee_universitaire_id;
                document.getElementById('creerPaiementInfo').textContent =
                    `Créer un paiement pour ${data.inscription.etudiant.nom} ${data.inscription.etudiant.prenoms}`;

                // Configurer l'action du formulaire pour utiliser valider-avec-paiement
                document.getElementById('formCreerPaiement').action = `/esbtp/inscriptions/${inscriptionId}/valider-avec-paiement`;

                const modal = new bootstrap.Modal(document.getElementById('modalCreerPaiement'));
                modal.show();
            } else {
                alert('Impossible de récupérer les informations de l\'inscription: ' + (data.message || ''));
            }
        })
        .catch(error => {
            debugError(error);
            alert('Erreur lors du chargement des données');
        });
}

// Gestion de la soumission des formulaires avec AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Formulaire validation paiement
    (() => {
        let isSubmitting = false;
        const $form = document.getElementById('formValiderPaiement');

        $form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Protection double-clic
            if (isSubmitting) {
                debugWarn('⚠️ formValiderPaiement - Soumission déjà en cours, blocage du double-clic');
                return false;
            }

            isSubmitting = true;
            debugLog('🔒 formValiderPaiement - Formulaire verrouillé');

            // Désactiver le bouton submit
            const $submitBtn = this.querySelector('button[type="submit"]');
            const originalText = $submitBtn.innerHTML;
            $submitBtn.disabled = true;
            $submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Validation en cours...';

            const formData = new FormData(this);
            const actionUrl = this.action;
            const inscriptionId = document.getElementById('valider_inscription_id').value;

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalValiderPaiement')).hide();
                    // ✅ Refresh uniquement la ligne au lieu de recharger toute la page
                    window.refreshInscriptionLigne(inscriptionId, 'validate');
                } else {
                    alert(data.message || 'Erreur lors de la validation');
                }
            })
            .catch(error => {
                debugError(error);
                alert('Erreur lors de la validation du paiement');
            })
            .finally(() => {
                // Réactiver le bouton
                isSubmitting = false;
                $submitBtn.disabled = false;
                $submitBtn.innerHTML = originalText;
            });
        });
    })();

    // Formulaire changement de classe
    document.getElementById('formChangerClasse').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const actionUrl = this.action;
        const inscriptionId = document.getElementById('changer_inscription_id').value;

        fetch(actionUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalChangerClasse')).hide();
                // ✅ Refresh uniquement la ligne au lieu de recharger toute la page
                window.refreshInscriptionLigne(inscriptionId, 'update');
            } else {
                alert(data.message || 'Erreur lors du changement de classe');
            }
        })
        .catch(error => {
            debugError(error);
            alert('Erreur lors du changement de classe');
        });
    });

    // Formulaire création de paiement (AJAX pour éviter rechargement page)
    (() => {
        let isSubmitting = false;
        const $form = document.getElementById('formCreerPaiement');

        $form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Protection double-clic
            if (isSubmitting) {
                debugWarn('⚠️ formCreerPaiement - Soumission déjà en cours, blocage du double-clic');
                return false;
            }

            isSubmitting = true;
            debugLog('🔒 formCreerPaiement - Formulaire verrouillé');

            // Désactiver le bouton submit
            const $submitBtn = this.querySelector('button[type="submit"]');
            const originalText = $submitBtn.innerHTML;
            $submitBtn.disabled = true;
            $submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Création en cours...';

            const formData = new FormData(this);
            const actionUrl = this.action;
            const inscriptionId = document.getElementById('creer_inscription_id').value;

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalCreerPaiement')).hide();
                    // ✅ Refresh uniquement la ligne au lieu de recharger toute la page
                    window.refreshInscriptionLigne(inscriptionId, 'update');
                } else {
                    alert(data.message || 'Erreur lors de la création du paiement');
                }
            })
            .catch(error => {
                debugError(error);
                alert('Erreur lors de la création du paiement');
            })
            .finally(() => {
                // Réactiver le bouton
                isSubmitting = false;
                $submitBtn.disabled = false;
                $submitBtn.innerHTML = originalText;
            });
        });
    })();

    /**
     * Fonction pour rafraîchir une ligne d'inscription spécifique sans recharger la page
     * Inclut spinner de chargement et sauvegarde de l'état du checkbox
     */
    window.refreshInscriptionLigne = function(inscriptionId, actionType = 'update') {
        debugLog('🔄 refreshInscriptionLigne() appelé pour ID:', inscriptionId);

        const row = document.querySelector(`tr[data-inscription-id="${inscriptionId}"]`);

        if (!row) {
            debugError('❌ Ligne non trouvée pour inscription ID:', inscriptionId);
            location.reload();
            return;
        }

        const checkbox = row.querySelector('.inscription-checkbox');
        const wasChecked = checkbox ? checkbox.checked : false;

        setInscriptionRowLoadingState(inscriptionId, true);

        fetch(`/esbtp/inscriptions/${inscriptionId}/refresh-ligne`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            debugLog('📥 Réponse reçue, status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Réponse serveur invalide');
            }

            const template = document.createElement('template');
            template.innerHTML = data.html.trim();

            let rowFragment = template.content.querySelector(`tr[data-inscription-id="${inscriptionId}"]`);
            if (!rowFragment) {
                rowFragment = template.content.querySelector('tr[data-inscription-id]');
            }

            if (!rowFragment) {
                debugError('❌ Contenu HTML reçu:', data.html);
                throw new Error('HTML retourné invalide (pas de TR)');
            }

            const newRow = rowFragment.cloneNode(true);
            const newCells = Array.from(newRow.children).map(cell => cell.cloneNode(true));
            const newAttributes = Array.from(newRow.attributes);

            let contentUpdated = false;

            const applyUpdatedContent = (highlightEl = null) => {
                if (contentUpdated) {
                    return;
                }
                contentUpdated = true;

                const highlightNode = highlightEl instanceof HTMLElement ? highlightEl : row.querySelector('.inscription-row-highlight');

                const classesToPreserve = [];
                if (row.classList.contains('inscription-row-flash')) {
                    classesToPreserve.push('inscription-row-flash');
                }
                if (row.classList.contains('reject')) {
                    classesToPreserve.push('reject');
                }
                if (row.classList.contains('is-loading')) {
                    classesToPreserve.push('is-loading');
                }

                const newClassName = newRow.getAttribute('class') || '';
                row.setAttribute('class', newClassName);

                newAttributes.forEach(attr => {
                    if (attr.name !== 'class') {
                        row.setAttribute(attr.name, attr.value);
                    }
                });

                classesToPreserve.forEach(cls => row.classList.add(cls));

                const currentCells = Array.from(row.children).filter(child => child !== highlightNode);

                currentCells.forEach((cell, index) => {
                    const replacement = newCells[index];
                    if (replacement) {
                        cell.replaceWith(replacement);
                    } else {
                        cell.remove();
                    }
                });

                const extraCells = newCells.slice(currentCells.length);
                if (extraCells.length) {
                    const fragment = document.createDocumentFragment();
                    extraCells.forEach(node => fragment.appendChild(node));

                    if (highlightNode && highlightNode.parentNode === row) {
                        row.insertBefore(fragment, highlightNode);
                    } else {
                        row.appendChild(fragment);
                    }
                }

                if (highlightNode && highlightNode.parentNode !== row) {
                    row.appendChild(highlightNode);
                }

                if (wasChecked) {
                    const restoredCheckbox = row.querySelector('.inscription-checkbox');
                    if (restoredCheckbox) {
                        restoredCheckbox.checked = true;
                        restoredCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                setInscriptionRowLoadingState(inscriptionId, false);
                updateInscriptionSelectionCount();

                debugLog('🎉 Ligne rafraîchie avec succès:', inscriptionId);
            };

            triggerInscriptionRowHighlight(row, actionType, {
                onStatusPassed: (highlightEl) => {
                    applyUpdatedContent(highlightEl);
                }
            });

            setTimeout(() => {
                if (!contentUpdated) {
                    applyUpdatedContent();
                }
            }, INSCRIPTION_HIGHLIGHT_DURATION + 150);
        })
        .catch(error => {
            debugError('❌ Erreur refresh ligne:', error);
            debugError('❌ Message d\'erreur:', error.message);
            debugError('❌ Stack trace:', error.stack);

            setInscriptionRowLoadingState(inscriptionId, false);

            alert('Erreur lors de la mise à jour: ' + error.message + '. La page va se recharger.');
            location.reload();
        });
    };
});
</script>

<!-- Modals d'actions rapides -->
<!-- Modal: Valider Paiement -->
<div class="modal fade" id="modalValiderPaiement" tabindex="-1" aria-labelledby="modalValiderPaiementLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; border-radius: 16px 16px 0 0; padding: 24px;">
                <h5 class="modal-title" id="modalValiderPaiementLabel">
                    <i class="fas fa-check-circle me-2"></i>Valider le paiement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 32px;">
                <form id="formValiderPaiement" method="POST">
                    @csrf
                    <input type="hidden" name="inscription_id" id="valider_inscription_id">
                    <input type="hidden" name="paiement_id" id="valider_paiement_id">

                    <div class="alert alert-info mb-4" style="background: #E3F2FD; border: none; border-radius: 12px; padding: 16px;">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="validerPaiementInfo">Paiement à valider...</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Montant</label>
                        <input type="text" class="form-control" id="valider_montant" readonly style="background: #f8f9fa; border-radius: 8px; font-size: 1.1rem; font-weight: 600;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Mode de paiement</label>
                        <input type="text" class="form-control" id="valider_mode" readonly style="background: #f8f9fa; border-radius: 8px;">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Référence</label>
                        <input type="text" class="form-control" id="valider_reference" readonly style="background: #f8f9fa; border-radius: 8px;">
                    </div>

                    <div class="d-flex gap-3">
                        <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal" style="border-radius: 10px; padding: 12px; font-weight: 600;">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-success flex-fill" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); border: none; border-radius: 10px; padding: 12px; font-weight: 600;">
                            <i class="fas fa-check me-2"></i>Valider le paiement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Changer la Classe -->
<div class="modal fade" id="modalChangerClasse" tabindex="-1" aria-labelledby="modalChangerClasseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; border-radius: 16px 16px 0 0; padding: 24px;">
                <h5 class="modal-title" id="modalChangerClasseLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Changer la classe
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 32px;">
                <form id="formChangerClasse" method="POST">
                    @csrf
                    <input type="hidden" name="inscription_id" id="changer_inscription_id">

                    <div class="alert alert-warning mb-4" style="background: #FFF3E0; border: none; border-radius: 12px; padding: 16px;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        La classe actuelle est pleine. Veuillez sélectionner une nouvelle classe.
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Classe actuelle</label>
                            <input type="text" class="form-control" id="changer_ancienne_classe" readonly style="background: #ffebee; border-radius: 8px; border: 2px solid #ef5350;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nouvelle classe <span class="text-danger">*</span></label>
                            <select class="form-select" name="nouvelle_classe_id" id="changer_nouvelle_classe" required style="border-radius: 8px; border: 2px solid #4caf50;">
                                <option value="">Sélectionnez une classe</option>
                            </select>
                        </div>
                    </div>

                    <div id="classeDispoInfo" class="alert alert-success" style="background: #E8F5E9; border: none; border-radius: 12px; padding: 16px; display: none;">
                        <i class="fas fa-check-circle me-2"></i>
                        <span id="classeDispoText">Places disponibles: ...</span>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal" style="border-radius: 10px; padding: 12px; font-weight: 600;">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-danger flex-fill" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); border: none; border-radius: 10px; padding: 12px; font-weight: 600;">
                            <i class="fas fa-exchange-alt me-2"></i>Changer la classe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Créer un Paiement -->
<div class="modal fade" id="modalCreerPaiement" tabindex="-1" aria-labelledby="modalCreerPaiementLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; border-radius: 16px 16px 0 0; padding: 24px;">
                <h5 class="modal-title" id="modalCreerPaiementLabel">
                    <i class="fas fa-plus-circle me-2"></i>Créer un paiement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 32px;">
                <form id="formCreerPaiement" method="POST">
                    @csrf
                    <input type="hidden" name="inscription_id" id="creer_inscription_id">
                    <input type="hidden" name="etudiant_id" id="creer_etudiant_id">
                    <input type="hidden" name="annee_universitaire_id" id="creer_annee_id">

                    <div class="alert alert-info mb-4" style="background: #E3F2FD; border: none; border-radius: 12px; padding: 16px;">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="creerPaiementInfo">Aucun paiement associé à cette inscription</span>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Montant (FCFA) <span class="text-danger">*</span></label>
                            <input type="number" name="montant" class="form-control" id="creer_montant" required min="0" step="0.01" placeholder="Ex: 50000" style="border-radius: 8px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Catégorie de frais <span class="text-danger">*</span></label>
                            <select name="fee_category_id" class="form-select" id="creer_categorie" required style="border-radius: 8px;">
                                <option value="">Sélectionnez une catégorie</option>
                                @php
                                    $categoriesfrais = \App\Models\ESBTPFraisCategory::where('is_active', true)->orderBy('name')->get();
                                @endphp
                                @foreach($categoriesfrais as $categorie)
                                    <option value="{{ $categorie->id }}">
                                        {{ $categorie->name }}
                                        @if($categorie->is_mandatory)
                                            (Obligatoire)
                                        @else
                                            (Optionnel)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mode de paiement <span class="text-danger">*</span></label>
                            <select name="mode_paiement" class="form-select" id="creer_mode" required style="border-radius: 8px;">
                                <option value="">Sélectionnez un mode</option>
                                <option value="especes">Espèces</option>
                                <option value="cheque">Chèque</option>
                                <option value="virement">Virement bancaire</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Référence de paiement</label>
                            <input type="text" name="reference_paiement" class="form-control" id="creer_reference" placeholder="Ex: REF2025-001" style="border-radius: 8px;">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date du paiement <span class="text-danger">*</span></label>
                            <input type="date" name="date_paiement" class="form-control" id="creer_date" required style="border-radius: 8px;" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Observations</label>
                            <textarea name="observations" class="form-control" id="creer_observations" rows="2" placeholder="Commentaires sur le paiement..." style="border-radius: 8px;"></textarea>
                        </div>
                    </div>

                    <div class="form-check mb-4" style="padding-left: 2rem;">
                        <input class="form-check-input" type="checkbox" name="valider_immediatement" id="creer_valider_immediatement" value="1" style="width: 20px; height: 20px;">
                        <label class="form-check-label fw-bold" for="creer_valider_immediatement" style="margin-left: 8px;">
                            Valider le paiement immédiatement
                        </label>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal" style="border-radius: 10px; padding: 12px; font-weight: 600;">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-primary flex-fill" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); border: none; border-radius: 10px; padding: 12px; font-weight: 600;">
                            <i class="fas fa-plus me-2"></i>Créer le paiement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endpush 
