@extends('layouts.app')

@section('title', 'Liste des évaluations - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-clipboard-list me-2"></i>Gestion des évaluations</h1>
                <p class="header-subtitle">Consultez et gérez toutes les évaluations de l'établissement</p>
            </div>
            <div class="header-actions">
                <input type="search"
                       class="search-bar"
                       id="evaluations-search"
                       placeholder="Rechercher une évaluation..."
                       autocomplete="off"
                       value="{{ $filters['search'] ?? '' }}">
                <a href="{{ route('esbtp.evaluations.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Nouvelle évaluation
                </a>
            </div>
        </div>
        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Évaluations</div>
                <div class="kpi-value" data-summary-key="total" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalEvaluations }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-file-alt"></i>
                    Toutes les évaluations
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Évaluations Publiées</div>
                <div class="kpi-value" data-summary-key="published" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $evaluationsPubliees }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-check-circle"></i>
                    Actives
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Examens</div>
                <div class="kpi-value" data-summary-key="examens" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $examens }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-graduation-cap"></i>
                    Examens officiels
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Devoirs</div>
                <div class="kpi-value" data-summary-key="devoirs" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $devoirs }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-pencil-alt"></i>
                    Travaux dirigés
                </div>
            </div>
        </div>

        <!-- Information année académique courante -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-calendar me-2"></i>Contexte d'affichage
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ $anneeAcademique }}" selected>
                                {{ $anneeAcademique }} (Année en cours)
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
                        Les évaluations affichées correspondent uniquement à l'année académique courante.
                    </small>
                </div>
            </div>
        </div>

        <!-- Section de gestion des liens externes (pour admins/secrétaires uniquement) -->
        @if(auth()->check() && auth()->user() && !auth()->user()->hasRole(['teacher', 'enseignant', 'etudiant']))
        <div class="main-card">
            @if($evaluationsForExternalLinks->isNotEmpty())
                @include('components.external-links-manager', ['evaluations' => $evaluationsForExternalLinks])
            @else
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-link"></i>
                        Gestion des liens externes
                    </div>
                    <div class="main-card-subtitle">Génération de liens temporaires pour enseignants externes</div>
                </div>
                <div class="main-card-body">
                    <div class="empty-state">
                        <i class="fas fa-link-slash"></i>
                        <p>
                            Aucune évaluation disponible pour la génération de liens externes.<br>
                            <small class="text-muted">Les évaluations doivent être publiées et sans enseignant assigné.</small>
                        </p>
                    </div>
                </div>
            @endif
        </div>
        @endif

        <!-- Section principale des évaluations -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste des évaluations
                </div>
                <div class="main-card-subtitle">Gestion complète de toutes les évaluations de l'établissement</div>
            </div>

            <div class="main-card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                    <form id="evaluations-filter-form" class="filters-grid mb-4" autocomplete="off">
                        <div class="filter-field">
                            <label for="classe_filter" class="form-label text-uppercase text-muted small fw-semibold">Classe</label>
                            <select class="form-select filter-select" name="classe_id" id="classe_filter">
                                <option value="">Toutes les classes</option>
                                @foreach($classes as $classe)
                                    <option value="{{ $classe->id }}" {{ ($filters['classe_id'] ?? null) == $classe->id ? 'selected' : '' }}>
                                        {{ $classe->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="matiere_filter" class="form-label text-uppercase text-muted small fw-semibold">Matière</label>
                            <select class="form-select filter-select" name="matiere_id" id="matiere_filter">
                                <option value="">Toutes les matières</option>
                                @foreach($matieres as $matiere)
                                    <option value="{{ $matiere->id }}" {{ ($filters['matiere_id'] ?? null) == $matiere->id ? 'selected' : '' }}>
                                        {{ $matiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="type_filter" class="form-label text-uppercase text-muted small fw-semibold">Type</label>
                            <select class="form-select filter-select" name="type" id="type_filter">
                                <option value="">Tous les types</option>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ ($filters['type'] ?? null) === $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="date_debut_filter" class="form-label text-uppercase text-muted small fw-semibold">Date début</label>
                            <input type="date"
                                   class="form-control filter-select"
                                   name="date_debut"
                                   id="date_debut_filter"
                                   value="{{ $filters['date_debut'] ?? '' }}">
                        </div>

                        <div class="filter-field">
                            <label for="date_fin_filter" class="form-label text-uppercase text-muted small fw-semibold">Date fin</label>
                            <input type="date"
                                   class="form-control filter-select"
                                   name="date_fin"
                                   id="date_fin_filter"
                                   value="{{ $filters['date_fin'] ?? '' }}">
                        </div>

                        <div class="filters-actions">
                            <button type="button" class="btn btn-outline-secondary w-100" id="evaluations-clear-filters">
                                <i class="fas fa-undo me-1"></i>Réinitialiser
                            </button>
                        </div>

                        <input type="hidden" name="search" id="filter-search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="per_page" id="filter-per-page" value="{{ $filters['per_page'] ?? 15 }}">
                    </form>

                    <div id="evaluations-results"
                         data-refresh-url="{{ route('esbtp.evaluations.index') }}"
                         data-row-url-template="{{ route('esbtp.evaluations.refresh-row', ['evaluation' => '__ID__']) }}"
                         data-summary='@json($summary)'
                    >
                        @include('esbtp.evaluations.partials.results', ['evaluations' => $evaluations])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les évaluations affichées se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois.
                    Changer l'année courante affecte l'affichage des évaluations dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les évaluations créées en 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les évaluations créées en 2023-2024
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

@push('styles')
<style>
.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    align-items: flex-end;
}

.filter-field .form-label {
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

.filter-select {
    background-color: #fff;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 0.5rem;
    padding: 0.55rem 0.75rem;
    font-size: 0.95rem;
    box-shadow: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.filter-select:focus {
    border-color: rgba(37, 99, 235, 0.7);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}

.filters-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

#evaluations-results .table tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.05);
}

.evaluation-actions-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 32px;
}

.evaluation-actions-wrapper.is-loading .evaluation-actions-buttons {
    display: none !important;
}

.evaluation-actions-spinner {
    display: none;
    align-items: center;
    justify-content: center;
    min-width: 32px;
}

.evaluation-actions-wrapper.is-loading .evaluation-actions-spinner {
    display: inline-flex !important;
}

.evaluation-row-highlight {
    position: absolute;
    top: 0;
    left: -65%;
    width: 150%;
    height: 100%;
    pointer-events: none;
    opacity: 0;
    transform: translateX(-65%) skewX(-12deg);
    background: linear-gradient(90deg, rgba(37, 99, 235, 0) 0%, rgba(37, 99, 235, 0.5) 50%, rgba(37, 99, 235, 0) 100%);
    z-index: 2;
}

.evaluation-row-highlight.reject {
    background: linear-gradient(90deg, rgba(220, 38, 38, 0) 0%, rgba(220, 38, 38, 0.55) 50%, rgba(220, 38, 38, 0) 100%);
}

.evaluation-row-highlight.animate {
    animation: evaluation-row-highlight-move 2.4s ease-out forwards;
}

.evaluation-row-flash {
    animation: evaluation-row-flash 0.8s ease-in-out;
}

.evaluation-row-flash.reject {
    animation-name: evaluation-row-flash-reject;
}

@keyframes evaluation-row-highlight-move {
    0% {
        opacity: 0;
        transform: translateX(-65%) skewX(-12deg);
    }
    18% {
        opacity: 0.9;
    }
    55% {
        opacity: 0.7;
    }
    100% {
        opacity: 0;
        transform: translateX(115%) skewX(-12deg);
    }
}

@keyframes evaluation-row-flash {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(59, 130, 246, 0.15);
    }
    100% {
        background-color: transparent;
    }
}

@keyframes evaluation-row-flash-reject {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(220, 38, 38, 0.2);
    }
    100% {
        background-color: transparent;
    }
}

#evaluations-results .pagination {
    margin-bottom: 0;
}
</style>
@endpush





@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filtersForm = document.getElementById('evaluations-filter-form');
    const resultsContainer = document.getElementById('evaluations-results');
    if (!filtersForm || !resultsContainer) {
        return;
    }

    const searchInput = document.getElementById('evaluations-search');
    const hiddenSearch = document.getElementById('filter-search');
    const clearFiltersBtn = document.getElementById('evaluations-clear-filters');
    const perPageInput = document.getElementById('filter-per-page');
    const perPageDefault = perPageInput ? perPageInput.value : '15';
    const FILTER_DEBOUNCE = 350;
    let filterTimer;
    const selectedIds = new Set();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const rowUrlTemplate = resultsContainer.dataset.rowUrlTemplate || '';

    let yearModalInstance = null;
    window.showYearChangeInfo = () => {
        const modalElement = document.getElementById('yearChangeModal');
        if (!modalElement || typeof bootstrap === 'undefined') {
            return;
        }
        if (!yearModalInstance) {
            yearModalInstance = new bootstrap.Modal(modalElement);
        }
        yearModalInstance.show();
    };

    function showToast(message, type = 'success') {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message);
        } else {
            if (type === 'error') {
                debugError(message);
            } else {
                debugLog(message);
            }
        }
    }

    function updateSummary(summary = {}) {
        const counts = summary.counts || {};
        document.querySelectorAll('[data-summary-key]').forEach((node) => {
            const key = node.getAttribute('data-summary-key');
            if (key && Object.prototype.hasOwnProperty.call(counts, key)) {
                node.textContent = counts[key];
            }
        });

        const pagination = summary.pagination || {};
        const rangeNode = resultsContainer.querySelector('[data-summary-range]');
        if (rangeNode) {
            if (pagination.total && pagination.total > 0) {
                rangeNode.textContent = `${pagination.first_item ?? 0} - ${pagination.last_item ?? 0} sur ${pagination.total} évaluation(s)`;
            } else {
                rangeNode.textContent = 'Aucune évaluation ne correspond à vos filtres.';
            }
        }
    }

    function setRowLoadingState(id, isLoading) {
        const row = resultsContainer.querySelector(`tr[data-evaluation-id="${id}"]`);
        if (!row) {
            return;
        }
        row.classList.toggle('is-loading', Boolean(isLoading));
        const wrapper = row.querySelector('.evaluation-actions-wrapper');
        if (wrapper) {
            wrapper.classList.toggle('is-loading', Boolean(isLoading));
        }
    }

    function triggerEvaluationRowHighlight(row, actionType = 'update') {
        if (!row) {
            return;
        }
        const isReject = actionType === 'delete' || actionType === 'cancel';
        row.classList.remove('evaluation-row-flash', 'reject');
        void row.offsetWidth;
        const highlight = document.createElement('div');
        highlight.className = 'evaluation-row-highlight';
        if (isReject) {
            highlight.classList.add('reject');
        }
        row.appendChild(highlight);
        requestAnimationFrame(() => highlight.classList.add('animate'));
        const cleanup = () => {
            highlight.removeEventListener('animationend', cleanup);
            highlight.remove();
        };
        highlight.addEventListener('animationend', cleanup);
        row.classList.add('evaluation-row-flash');
        if (isReject) {
            row.classList.add('reject');
        }
        setTimeout(() => row.classList.remove('evaluation-row-flash', 'reject'), 1200);
    }

    function resolveRowUrl(id) {
        return rowUrlTemplate.replace('__ID__', id);
    }

    function refreshEvaluationRow(id, actionType = 'update') {
        if (!rowUrlTemplate) {
            return Promise.resolve();
        }
        setRowLoadingState(id, true);
        return fetch(resolveRowUrl(id), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!data.success || !data.html) {
                    throw new Error(data.message || 'Réponse invalide');
                }
                const template = document.createElement('template');
                template.innerHTML = data.html.trim();
                const newRow = template.content.querySelector(`tr[data-evaluation-id="${id}"]`);
                const existingRow = resultsContainer.querySelector(`tr[data-evaluation-id="${id}"]`);
                if (existingRow && newRow) {
                    existingRow.replaceWith(newRow);
                    triggerEvaluationRowHighlight(newRow, actionType);
                }
            })
            .catch((error) => {
                showToast(error.message || 'Erreur lors de la mise à jour de la ligne', 'error');
            })
            .finally(() => {
                setRowLoadingState(id, false);
                initRowInteractions();
            });
    }

    function showResultsOverlay() {
        const overlay = document.createElement('div');
        overlay.style.position = 'absolute';
        overlay.style.inset = '0';
        overlay.style.background = 'rgba(255,255,255,0.75)';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.zIndex = '10';
        overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
        resultsContainer.style.position = 'relative';
        resultsContainer.appendChild(overlay);
        return overlay;
    }

    function fetchSummaryOnly() {
        const formData = new FormData(filtersForm);
        const params = new URLSearchParams(formData);
        const url = `${resultsContainer.dataset.refreshUrl}?${params.toString()}`;
        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                resultsContainer.dataset.summary = JSON.stringify(data.summary || {});
                updateSummary(data.summary || {});
            })
            .catch((error) => {
                showToast(error.message || 'Erreur lors de la mise à jour des statistiques', 'error');
            });
    }

    function submitFilterForm(pushHistory = true) {
        const formData = new FormData(filtersForm);
        const params = new URLSearchParams(formData);
        const url = `${resultsContainer.dataset.refreshUrl}?${params.toString()}`;
        const overlay = showResultsOverlay();

        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!data.html) {
                    throw new Error('Template manquant dans la réponse');
                }
                resultsContainer.innerHTML = data.html;
                resultsContainer.dataset.summary = JSON.stringify(data.summary || {});
                if (pushHistory && data.url) {
                    history.pushState({}, '', data.url);
                }
                updateSummary(data.summary || {});
                initRowInteractions();
            })
            .catch((error) => {
                showToast(error.message || 'Erreur lors du chargement des évaluations', 'error');
            })
            .finally(() => {
                overlay.remove();
            });
    }

    function handleRowSelection(event) {
        const checkbox = event.target;
        const id = Number(checkbox.value);
        if (checkbox.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        const selectAll = resultsContainer.querySelector('#evaluations-select-all');
        if (selectAll) {
            const total = resultsContainer.querySelectorAll('.evaluation-checkbox').length;
            selectAll.indeterminate = selectedIds.size > 0 && selectedIds.size < total;
            selectAll.checked = selectedIds.size > 0 && selectedIds.size === total;
        }
    }

    function initRowInteractions() {
        resultsContainer.querySelectorAll('.evaluation-checkbox').forEach((checkbox) => {
            checkbox.addEventListener('change', handleRowSelection);
            if (selectedIds.has(Number(checkbox.value))) {
                checkbox.checked = true;
            }
        });

        const selectAll = resultsContainer.querySelector('#evaluations-select-all');
        if (selectAll) {
            const checkboxes = resultsContainer.querySelectorAll('.evaluation-checkbox');
            selectAll.indeterminate = selectedIds.size > 0 && selectedIds.size < checkboxes.length;
            selectAll.checked = selectedIds.size > 0 && selectedIds.size === checkboxes.length;
            selectAll.addEventListener('change', () => {
                const checked = selectAll.checked;
                resultsContainer.querySelectorAll('.evaluation-checkbox').forEach((checkbox) => {
                    checkbox.checked = checked;
                    const id = Number(checkbox.value);
                    if (checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
            });
        }

        resultsContainer.querySelectorAll('.pagination a').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const href = link.getAttribute('href');
                if (!href || href === '#') {
                    return;
                }
                const url = new URL(href, window.location.origin);
                filtersForm.querySelectorAll('input[name], select[name]').forEach((field) => {
                    const name = field.getAttribute('name');
                    if (!name) {
                        return;
                    }
                    if (url.searchParams.has(name)) {
                        field.value = url.searchParams.get(name);
                    }
                });
                submitFilterForm();
            });
        });

        resultsContainer.querySelectorAll('[data-evaluation-action]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                handleAction(button);
            });
        });

        const perPageSelect = document.getElementById('evaluations-per-page');
        if (perPageSelect) {
            perPageSelect.value = perPageInput.value || perPageSelect.value;
            perPageSelect.addEventListener('change', () => {
                perPageInput.value = perPageSelect.value;
                submitFilterForm();
            });
        }
    }

    function handleAction(button) {
        const action = button.getAttribute('data-evaluation-action');
        const url = button.getAttribute('data-url');
        const method = button.getAttribute('data-method') || 'POST';
        const row = button.closest('tr[data-evaluation-id]');
        const evaluationId = row ? row.getAttribute('data-evaluation-id') : null;

        if (!action || !url || !evaluationId) {
            return;
        }

        if (action === 'delete' && !window.confirm('Confirmez-vous la suppression de cette évaluation ?')) {
            return;
        }

        if (action === 'cancel' && !window.confirm("Confirmez-vous l'annulation de cette évaluation ?")) {
            return;
        }

        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };

        if (method !== 'GET' && csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        setRowLoadingState(evaluationId, true);

        fetch(url, {
            method,
            headers
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.message || 'Action impossible');
                }
                if (data.message) {
                    showToast(data.message, 'success');
                }
                if (data.deleted || action === 'delete') {
                    selectedIds.delete(Number(evaluationId));
                    submitFilterForm(false);
                } else {
                    const highlightType = action === 'cancel' ? 'cancel' : 'update';
                    refreshEvaluationRow(evaluationId, highlightType).then(() => fetchSummaryOnly());
                }
            })
            .catch((error) => {
                showToast(error.message || "Erreur lors du traitement de l'action", 'error');
                setRowLoadingState(evaluationId, false);
            });
    }

    function syncFiltersFromUrl() {
        const params = new URLSearchParams(window.location.search);
        filtersForm.querySelectorAll('select[name], input[name]').forEach((field) => {
            const name = field.getAttribute('name');
            if (!name || name === 'search') {
                return;
            }
            field.value = params.get(name) ?? '';
        });
        const searchValue = params.get('search') ?? '';
        if (hiddenSearch) {
            hiddenSearch.value = searchValue;
        }
        if (searchInput) {
            searchInput.value = searchValue;
        }
        if (perPageInput) {
            perPageInput.value = params.get('per_page') ?? perPageDefault;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (hiddenSearch) {
                hiddenSearch.value = searchInput.value.trim();
            }
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => submitFilterForm(), FILTER_DEBOUNCE);
        });
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                if (hiddenSearch) {
                    hiddenSearch.value = searchInput.value.trim();
                }
                submitFilterForm();
            }
        });
    }

    filtersForm.querySelectorAll('select[name], input[type="date"]').forEach((field) => {
        field.addEventListener('change', () => {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => submitFilterForm(), FILTER_DEBOUNCE);
        });
    });

    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', () => {
            filtersForm.reset();
            selectedIds.clear();
            if (hiddenSearch) {
                hiddenSearch.value = '';
            }
            if (searchInput) {
                searchInput.value = '';
            }
            if (perPageInput) {
                perPageInput.value = perPageDefault;
            }
            submitFilterForm();
        });
    }

    window.addEventListener('popstate', () => {
        syncFiltersFromUrl();
        submitFilterForm(false);
    });

    syncFiltersFromUrl();
    updateSummary(JSON.parse(resultsContainer.dataset.summary || '{}'));
    initRowInteractions();
});
</script>
@endpush
