@extends('layouts.app')

@section('title', 'Suivi des Paiements par Catégorie - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .btn-acasi.small {
        padding: var(--space-xs) var(--space-sm);
        font-size: var(--text-small);
        border-radius: var(--radius-small);
    }
    
    /* Styles pour les cartes d'étudiants */
    .student-card {
        padding: var(--space-md);
        border-left: 4px solid transparent;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    
    .student-card.success {
        border-left-color: var(--success);
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.03) 0%, rgba(16, 185, 129, 0.08) 100%);
        border: 1px solid rgba(16, 185, 129, 0.1);
    }
    .student-card.warning {
        border-left-color: var(--warning);
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.03) 0%, rgba(245, 158, 11, 0.08) 100%);
        border: 1px solid rgba(245, 158, 11, 0.1);
    }
    .student-card.danger {
        border-left-color: var(--danger);
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.03) 0%, rgba(239, 68, 68, 0.08) 100%);
        border: 1px solid rgba(239, 68, 68, 0.1);
    }
    
    .student-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .student-info {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        margin-bottom: var(--space-sm);
    }
    
    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-circle);
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }
    
    .student-details h6 {
        font-weight: 600;
        margin: 0 0 var(--space-xs) 0;
        color: var(--text-primary);
    }
    
    .student-details p {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin: 0;
    }
    
    .payment-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: var(--text-small);
    }
    
    .amount-info {
        text-align: right;
    }
    
    .amount-paid {
        font-weight: 600;
        color: #059669;
        font-size: 14px;
    }

    .amount-due {
        color: #374151;
        font-weight: 500;
        font-size: 14px;
    }
    
    .percentage-badge {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: var(--text-small);
        font-weight: 600;
    }
    
    .percentage-badge.success { 
        background: rgba(16, 185, 129, 0.1); 
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    .percentage-badge.warning { 
        background: rgba(245, 158, 11, 0.1); 
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    .percentage-badge.danger { 
        background: rgba(239, 68, 68, 0.1); 
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .students-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--space-md);
    }
    
    /* Styles pour les catégories - ancien style visuel */
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: var(--space-xl);
        margin-bottom: var(--space-xl);
    }
    
    .category-card {
        position: relative;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        background: linear-gradient(135deg, var(--surface) 0%, rgba(255, 255, 255, 0.95) 100%);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
    }
    
    .category-card:hover {
        transform: translateY(-6px) rotate(0.5deg);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        border-color: rgba(99, 102, 241, 0.2);
    }
    
    .category-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--space-lg);
        position: relative;
        z-index: 2;
    }
    
    .category-icon {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-medium);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }
    
    .category-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
        position: relative;
        z-index: 2;
    }
    
    .mini-stat {
        text-align: center;
        padding: var(--space-md);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.4));
        border-radius: var(--radius-medium);
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }
    
    .mini-stat:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.6));
    }
    
    .mini-stat-value {
        font-size: var(--amount-medium);
        font-weight: 800;
        display: block;
        margin-bottom: var(--space-xs);
    }
    
    .mini-stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-top: var(--space-xs);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Barre de progression moderne */
    .progress-bar-modern {
        height: 12px;
        background: linear-gradient(90deg, rgba(0, 0, 0, 0.05) 0%, rgba(0, 0, 0, 0.08) 100%);
        border-radius: 8px;
        overflow: hidden;
        margin: var(--space-lg) 0;
        position: relative;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .progress-fill-modern {
        height: 100%;
        border-radius: 8px;
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .progress-fill-modern.success { 
        background: linear-gradient(135deg, #10b981, #34d399, #6ee7b7);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    
    .progress-fill-modern.warning { 
        background: linear-gradient(135deg, #f59e0b, #fbbf24, #fcd34d);
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
    }
    
    .progress-fill-modern.danger {
        background: linear-gradient(135deg, #ef4444, #f87171, #fca5a5);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }

    /* Styles pour les onglets étudiants avec lazy loading */
    /* Tabs avec ombres et positionnement amélioré */
    .student-tabs-container {
        position: relative;
        margin-top: 2rem;
        margin-bottom: 0;
    }

    .student-tabs-container .nav-tabs {
        border: none;
        margin-bottom: 0;
        position: relative;
        z-index: 10;
        display: flex;
        gap: 8px;
        padding-left: 20px;
    }

    .student-tabs-container .nav-item {
        border: none;
        margin-bottom: 0;
    }

    .student-tabs-container .nav-link {
        border: none !important;
        border-radius: 12px 12px 0 0 !important;
        padding: 12px 20px 12px 20px !important;
        color: #6b7280 !important;
        background: #f8fafc !important;
        font-weight: 500 !important;
        transition: all 0.3s ease !important;
        position: relative;
        margin-bottom: 0 !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        border-bottom: 1px solid #e5e7eb !important;
    }

    .student-tabs-container .nav-link:hover {
        background: #f1f5f9 !important;
        color: #374151 !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12) !important;
    }

    .student-tabs-container .nav-link.active {
        background: #ffffff !important;
        color: #1f2937 !important;
        font-weight: 600 !important;
        box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.10), 2px 0 8px rgba(0, 0, 0, 0.10), -2px 0 8px rgba(0, 0, 0, 0.10) !important;
        transform: translateY(0px) !important;
        border-bottom: none !important;
        z-index: 15;
        position: relative;
    }

    .student-tabs-container .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 2px;
        background: #ffffff;
        z-index: 20;
    }

    .tab-content {
        position: relative;
        z-index: 5;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0 12px 12px 12px;
        margin-top: -1px;
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.12);
        border-top: none;
    }

    .tab-pane {
        background: transparent;
        border: none;
        border-radius: 0;
        padding: 24px;
        margin-top: 0;
        box-shadow: none;
        min-height: 200px;
    }

    .loading-spinner {
        text-align: center;
        padding: 40px;
    }

    .spinner-border {
        width: 2rem;
        height: 2rem;
    }

    /* SPINNER ISOLÉ - Force tous les styles - COPIÉ DE RÉINSCRIPTIONS */
    .paiement-spinner {
        position: relative !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        background: white !important;
        border: none !important;
        margin: 0 !important;
        padding: 40px !important;
    }

    .paiement-spinner.hidden {
        display: none !important;
    }

    .paiement-spinner-icon {
        display: block !important;
        margin-bottom: 20px !important;
        text-align: center !important;
    }

    .paiement-spinner-icon i {
        font-size: 48px !important;
        color: #3b82f6 !important;
        animation: paiement-spin 1s linear infinite !important;
        transform-origin: center center !important;
    }

    .paiement-spinner-text {
        display: block !important;
        position: static !important;
        animation: none !important;
        font-size: 16px !important;
        color: #374151 !important;
        font-weight: 500 !important;
        margin: 0 !important;
    }

    /* Animation spinner EXACTEMENT comme réinscriptions */
    @keyframes paiement-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Suivi des Paiements par Catégorie</h1>
                <p class="header-subtitle">Vue d'ensemble des frais académiques et de leur recouvrement</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.paiements.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-list"></i>Liste des paiements
                </a>
                <a href="{{ route('esbtp.paiements.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus"></i>Nouveau paiement
                </a>
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
                            <option value="{{ date('Y') . '-' . (date('Y') + 1) }}" selected>
                                {{ date('Y') . '-' . (date('Y') + 1) }} (Année en cours)
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
                        Les paiements par catégorie affichés correspondent à l'année académique courante.
                    </small>
                </div>
            </div>
        </div>

        <!-- Filtres et Actions -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <form id="suivi-filter-form" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-2">
                            <label for="filiere_id" class="form-label">Filière</label>
                            <select name="filiere_id" id="filiere_id" class="form-select">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ $filiereId == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="niveau_id" class="form-label">Niveau d'étude</label>
                            <select name="niveau_id" id="niveau_id" class="form-select">
                                <option value="">Tous les niveaux</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ $niveauId == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="category_id" class="form-label">Catégorie détaillée</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">Vue d'ensemble</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn-acasi primary w-100">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- KPI Cards -->
        <div id="suivi-metrics-container">
            @include('esbtp.paiements.partials.suivi-metrics')
        </div>

        <!-- Content Section -->
        <div id="suivi-content-container">
            @include('esbtp.paiements.partials.suivi-content')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ===== AJAX FILTERING SYSTEM =====
(function() {
    'use strict';

    // Build refresh URL with current filters
    function buildRefreshUrl() {
        const form = document.getElementById('suivi-filter-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        return '{{ route("esbtp.paiements.suivi-categories.refresh") }}?' + params.toString();
    }

    // Build index URL with current filters
    function buildIndexUrl() {
        const form = document.getElementById('suivi-filter-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        return '{{ route("esbtp.paiements.suivi-categories") }}?' + params.toString();
    }

    // Fetch and update content via AJAX
    function fetchSuiviData(url, { pushState = true } = {}) {
        const metricsContainer = document.getElementById('suivi-metrics-container');
        const contentContainer = document.getElementById('suivi-content-container');

        if (!metricsContainer || !contentContainer) {
            return;
        }

        // Show loading state - griser les sections
        metricsContainer.style.opacity = '0.5';
        metricsContainer.style.pointerEvents = 'none';
        metricsContainer.style.transition = 'opacity 0.2s ease';
        contentContainer.style.opacity = '0.5';
        contentContainer.style.pointerEvents = 'none';
        contentContainer.style.transition = 'opacity 0.2s ease';

        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des données.');
            }
            return response.json();
        })
        .then(data => {
            // Update metrics
            if (data.metrics && metricsContainer) {
                metricsContainer.innerHTML = data.metrics;
            }

            // Update content
            if (data.content && contentContainer) {
                contentContainer.innerHTML = data.content;

                // Re-bind category card clicks after content update
                bindCategoryCardClicks();
            }

            // Update URL if needed
            if (data.url && pushState) {
                window.history.pushState({ url: data.url }, '', data.url);
            }

            // Reinitialize tooltips if needed
            if (typeof $ !== 'undefined' && typeof $.fn.tooltip !== 'undefined') {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        })
        .catch(error => {
            debugError(error);
            alert(error.message || 'Impossible de charger les données pour le moment.');
        })
        .finally(() => {
            // Remove loading state
            metricsContainer.style.opacity = '1';
            metricsContainer.style.pointerEvents = 'auto';
            contentContainer.style.opacity = '1';
            contentContainer.style.pointerEvents = 'auto';
        });
    }

    // Bind clicks on category cards to filter by category via AJAX
    function bindCategoryCardClicks() {
        const categoryCards = document.querySelectorAll('.category-card-ajax');
        categoryCards.forEach(card => {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                const categoryId = this.dataset.categoryId;

                // Update the category_id select
                const categorySelect = document.getElementById('category_id');
                if (categorySelect) {
                    categorySelect.value = categoryId;
                }

                // Trigger form submission via AJAX
                document.getElementById('suivi-filter-form').dispatchEvent(new Event('submit'));
            });
        });
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('suivi-filter-form');

        if (!form) {
            return;
        }

        // Bind form submission
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const url = buildRefreshUrl();
            fetchSuiviData(url, { pushState: true });
        });

        // Bind filter selects to auto-submit
        const filterSelects = form.querySelectorAll('select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                form.dispatchEvent(new Event('submit'));
            });
        });

        // Initial binding of category cards
        bindCategoryCardClicks();

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.url) {
                fetchSuiviData(event.state.url, { pushState: false });
            }
        });

        // Set initial history state
        if (window.history && window.history.replaceState) {
            window.history.replaceState({ url: window.location.href }, '', window.location.href);
        }

        // ===== LAZY LOADING DES ONGLETS =====
        function initStudentTabsLazyLoading() {
            const tabLinks = document.querySelectorAll('.students-tabs a[data-statut]');

            tabLinks.forEach(tabLink => {
                tabLink.addEventListener('shown.bs.tab', function(event) {
                    const targetId = event.target.getAttribute('href');
                    const targetPane = document.querySelector(targetId);

                    // Vérifier si déjà chargé
                    if (targetPane && targetPane.getAttribute('data-loaded') === 'false') {
                        const statut = event.target.getAttribute('data-statut');
                        const categoryId = event.target.getAttribute('data-category-id');
                        const count = parseInt(event.target.getAttribute('data-count'));

                        // Si count = 0, afficher message vide directement
                        if (count === 0) {
                            const container = targetPane.querySelector('.students-list-container');
                            container.innerHTML = `
                                <div style="padding: 40px; text-align: center; color: #9ca3af;">
                                    <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 16px; color: #10b981;"></i>
                                    <p style="font-size: 16px; font-weight: 500;">Aucun étudiant dans cette catégorie</p>
                                </div>
                            `;
                            targetPane.setAttribute('data-loaded', 'true');
                            return;
                        }

                        // Charger les étudiants via AJAX
                        loadStudentsByStatut(statut, categoryId, targetPane);
                    }
                });
            });

            // Charger automatiquement le premier onglet actif
            const firstActiveTab = document.querySelector('.students-tabs a.active[data-statut]');
            if (firstActiveTab) {
                const targetId = firstActiveTab.getAttribute('href');
                const targetPane = document.querySelector(targetId);
                const statut = firstActiveTab.getAttribute('data-statut');
                const categoryId = firstActiveTab.getAttribute('data-category-id');
                const count = parseInt(firstActiveTab.getAttribute('data-count'));

                if (targetPane && targetPane.getAttribute('data-loaded') === 'false') {
                    if (count === 0) {
                        const container = targetPane.querySelector('.students-list-container');
                        container.innerHTML = `
                            <div style="padding: 40px; text-align: center; color: #9ca3af;">
                                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 16px; color: #10b981;"></i>
                                <p style="font-size: 16px; font-weight: 500;">Aucun étudiant dans cette catégorie</p>
                            </div>
                        `;
                        targetPane.setAttribute('data-loaded', 'true');
                    } else {
                        loadStudentsByStatut(statut, categoryId, targetPane);
                    }
                }
            }
        }

        function loadStudentsByStatut(statut, categoryId, targetPane) {
            const container = targetPane.querySelector('.students-list-container');
            const currentPage = parseInt(targetPane.getAttribute('data-current-page') || '0');
            const nextPage = currentPage + 1;

            // Construire l'URL avec les filtres actuels
            const urlParams = new URLSearchParams(window.location.search);
            const url = `/esbtp/paiements/suivi-categories/load/${statut}?` + new URLSearchParams({
                category_id: categoryId,
                page: nextPage,
                per_page: 20,
                filiere_id: urlParams.get('filiere_id') || '',
                niveau_id: urlParams.get('niveau_id') || '',
                annee_id: urlParams.get('annee_id') || ''
            }).toString();

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (nextPage === 1) {
                    // Première page : remplacer tout le contenu
                    container.innerHTML = data.html;
                } else {
                    // Pages suivantes : ajouter à la fin
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.html;
                    container.querySelector('.students-list').appendChild(...tempDiv.children);
                }

                // Mettre à jour l'état
                targetPane.setAttribute('data-loaded', 'true');
                targetPane.setAttribute('data-current-page', data.current_page);
                targetPane.setAttribute('data-has-more', data.has_more);

                // Ajouter bouton "Charger plus" si nécessaire
                if (data.has_more) {
                    addLoadMoreButton(container, statut, categoryId, targetPane);
                }
            })
            .catch(error => {
                debugError('Erreur lors du chargement des étudiants:', error);
                container.innerHTML = `
                    <div class="alert alert-danger" style="margin: 20px;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erreur lors du chargement des étudiants. Veuillez réessayer.
                    </div>
                `;
            });
        }

        function addLoadMoreButton(container, statut, categoryId, targetPane) {
            // Supprimer l'ancien bouton s'il existe
            const oldBtn = container.querySelector('.load-more-btn');
            if (oldBtn) oldBtn.remove();

            const btn = document.createElement('div');
            btn.className = 'load-more-btn';
            btn.style.cssText = 'text-align: center; margin-top: 24px; padding: 20px;';
            btn.innerHTML = `
                <button class="btn btn-primary" style="padding: 12px 32px; font-weight: 600;">
                    <i class="fas fa-chevron-down me-2"></i>
                    Charger plus d'étudiants
                </button>
            `;

            btn.querySelector('button').addEventListener('click', function() {
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Chargement...';
                loadStudentsByStatut(statut, categoryId, targetPane);
            });

            container.appendChild(btn);
        }

        // Initialiser après le premier chargement
        initStudentTabsLazyLoading();

        // Réinitialiser après chaque refresh AJAX
        const originalFetchSuiviData = fetchSuiviData;
        fetchSuiviData = function(url, options = {}) {
            return originalFetchSuiviData(url, options).then(() => {
                // Attendre que le DOM soit mis à jour
                setTimeout(() => {
                    initStudentTabsLazyLoading();
                }, 100);
            });
        };
    });
})();

// ===== EXISTING SCRIPTS =====
$(function() {
    // Tooltip pour les pourcentages
    $('.percentage-badge').each(function() {
        const percentage = $(this).text();
        $(this).attr('title', `Taux de paiement: ${percentage}`);
    });

    // Animation simple au hover des cartes
    $('.card').hover(
        function() { $(this).addClass('shadow-lg'); },
        function() { $(this).removeClass('shadow-lg'); }
    );

    // ===== SYSTÈME LAZY LOADING - COPIÉ EXACT DES RÉINSCRIPTIONS =====
    @if($detailsCategorie)

    // Variables pour le système de lazy loading - EXACT COMME RÉINSCRIPTIONS
    let loadedTabs = {};
    let currentPage = {};

    const categoryId = {{ $categoryId ?? 'null' }};
    const baseParams = {
        @if(request('filiere_id'))
        filiere_id: {{ request('filiere_id') }},
        @endif
        @if(request('niveau_id'))
        niveau_id: {{ request('niveau_id') }},
        @endif
        @if(request('annee_id'))
        annee_id: {{ request('annee_id') }},
        @endif
        category_id: categoryId
    };

    debugLog("🚀 DEBUG PAIEMENTS: Page ready, initialisation du lazy loading");

    // Auto-charger le premier onglet avec des étudiants - EXACT COMME RÉINSCRIPTIONS
    const statistiques = {
        'non_payes': {{ $detailsCategorie['etudiants_non_payes']->count() }},
        'en_retard': {{ $detailsCategorie['etudiants_en_retard']->count() }},
        'a_jour': {{ $detailsCategorie['etudiants_a_jour']->count() }}
    };

    debugLog("📊 DEBUG PAIEMENTS: Statistiques disponibles:", statistiques);

    // Trouver la catégorie avec le plus d'étudiants
    let maxCategory = null;
    let maxCount = 0;

    Object.keys(statistiques).forEach(category => {
        if (statistiques[category] > maxCount) {
            maxCount = statistiques[category];
            maxCategory = category;
        }
    });

    debugLog(`🎯 DEBUG PAIEMENTS: Catégorie avec le plus d'étudiants: "${maxCategory}" (${maxCount} étudiants)`);

    if (maxCategory && maxCount > 0) {
        debugLog(`🚀 DEBUG PAIEMENTS: Activation de l'onglet "${maxCategory}"`);

        const tabLink = $(`.student-tab[data-statut="${maxCategory}"]`);
        const tabPane = $(`#${maxCategory}`);

        tabLink.addClass('active');
        tabPane.addClass('show active');

        // Cacher le spinner de cette catégorie car elle va être chargée
        const maxTabPane = $(`#${maxCategory}`);
        const maxSpinner = maxTabPane.find('.paiement-spinner');
        maxSpinner.addClass('hidden');

        debugLog(`📞 DEBUG PAIEMENTS: Appel loadTabContent("${maxCategory}")`);
        loadTabContent(maxCategory);
    }

    // Gestionnaire des clics sur les onglets - EXACT COMME RÉINSCRIPTIONS
    $('.student-tab').on('click', function(e) {
        e.preventDefault();

        const statut = $(this).data('statut');
        debugLog(`🖱️  DEBUG PAIEMENTS: Clic sur onglet "${statut}"`);

        // Gérer les onglets Bootstrap
        $('.student-tab').removeClass('active');
        $(this).addClass('active');

        $('.tab-pane').removeClass('show active');
        $(`#${statut}`).addClass('show active');

        // Charger le contenu si pas encore fait
        if (loadedTabs[statut]) {
            debugLog(`✅ DEBUG PAIEMENTS: Statut "${statut}" déjà en cache, pas de rechargement`);
        } else {
            debugLog(`🚀 DEBUG PAIEMENTS: Chargement par clic du statut "${statut}"`);
            loadTabContent(statut);
        }
    });

    // FONCTION PRINCIPALE - COPIE EXACTE DES RÉINSCRIPTIONS
    function loadTabContent(statut, page = 1) {
        debugLog(`🔥 DEBUG PAIEMENTS: loadTabContent("${statut}", ${page})`);

        const tabPane = $(`[data-statut="${statut}"]`);
        const loadingSpinner = tabPane.find('.paiement-spinner');
        const contentContainer = tabPane.find('.content-container');

        debugLog(`🔍 DEBUG PAIEMENTS: Éléments trouvés:`);
        debugLog(`  - tabPane:`, tabPane.length > 0, tabPane);
        debugLog(`  - loadingSpinner:`, loadingSpinner.length > 0, loadingSpinner);
        debugLog(`  - contentContainer:`, contentContainer.length > 0, contentContainer);

        // Afficher le spinner si c'est la première page
        if (page === 1) {
            debugLog(`🔄 DEBUG PAIEMENTS: Affichage du spinner pour page 1`);
            loadingSpinner.removeClass('hidden');
            contentContainer.hide();
        }

        const ajaxUrl = `{{ route('esbtp.paiements.suivi-categories.load', 'STATUT_PLACEHOLDER') }}`
            .replace('STATUT_PLACEHOLDER', statut);

        const params = {
            ...baseParams,
            page: page,
            per_page: 20
        };

        debugLog(`📡 DEBUG PAIEMENTS: AJAX vers ${ajaxUrl} avec params:`, params);

        $.ajax({
            url: ajaxUrl,
            method: 'GET',
            data: params,
            success: function(response) {
                debugLog(`✅ DEBUG PAIEMENTS: AJAX Success pour "${statut}", page ${page}`);
                debugLog(`📊 DEBUG PAIEMENTS: Réponse:`, response);

                if (page === 1) {
                    debugLog(`🎯 DEBUG PAIEMENTS: Traitement première page`);
                    // Première page : remplacer le contenu
                    debugLog(`🚫 DEBUG PAIEMENTS: Masquage du spinner`);
                    loadingSpinner.addClass('hidden');

                    // Gérer les statuts vides
                    if (response.total === 0) {
                        debugLog(`🔍 DEBUG PAIEMENTS: Statut vide pour "${statut}"`);
                        contentContainer.html(`
                            <div style="text-align: center; padding: 40px; color: #64748b;">
                                <i class="fas fa-info-circle fa-3x mb-3"></i>
                                <p>Aucun étudiant dans cette catégorie</p>
                            </div>
                        `);
                    } else {
                        contentContainer.html(response.html);
                    }

                    contentContainer.show();
                    loadedTabs[statut] = true;
                    currentPage[statut] = 1;

                } else {
                    debugLog(`➕ DEBUG PAIEMENTS: Ajout page ${page}`);
                    // Pages suivantes : ajouter le contenu directement
                    const studentsList = contentContainer.find('.students-list');
                    const newCards = $(response.html);
                    studentsList.append(newCards);
                    currentPage[statut] = page;
                    debugLog(`➕ ${newCards.length} nouvelles cartes ajoutées`);
                }

                // Mettre à jour le bouton "Charger plus"
                updateLoadMoreButton(statut, response);
            },
            error: function(xhr, status, error) {
                debugError(`❌ DEBUG PAIEMENTS: Erreur AJAX pour "${statut}":`, error);
                showErrorState(statut);
            }
        });
    }

    function loadMore(statut, nextPage) {
        debugLog(`🔄 DEBUG PAIEMENTS: loadMore("${statut}", ${nextPage})`);
        loadTabContent(statut, nextPage);
    }

    function updateLoadMoreButton(statut, response) {
        const container = $(`#${statut} .content-container`);
        container.find('.load-more-container').remove();

        if (response.has_more) {
            const nextPage = response.current_page + 1;
            const remaining = response.total - (response.current_page * 20);
            const loadMoreHtml = `
                <div class="load-more-container" style="text-align: center; margin: 30px 0 10px 0;">
                    <button class="btn btn-primary load-more-btn" data-statut="${statut}"
                            onclick="loadMore('${statut}', ${nextPage})"
                            style="padding: 12px 24px; border-radius: 8px; font-weight: 500; background: linear-gradient(135deg, #0453cb, #3b82f6); border: none; box-shadow: 0 4px 15px rgba(4, 83, 203, 0.3); transition: all 0.3s ease;">
                        <i class="fas fa-plus me-2"></i>Charger plus d'étudiants (${remaining} restants)
                    </button>
                </div>
            `;
            container.append(loadMoreHtml);
        }
    }

    function showErrorState(statut) {
        const tabPane = $(`[data-statut="${statut}"]`);
        const loadingSpinner = tabPane.find('.paiement-spinner');
        const contentContainer = tabPane.find('.content-container');

        debugLog(`🛑 DEBUG PAIEMENTS: Masquage spinner et affichage erreur`);
        loadingSpinner.addClass('hidden');
        contentContainer.html(`
            <div style="text-align: center; padding: 40px; color: #dc2626;">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <p>Erreur lors du chargement des étudiants.</p>
                <button class="btn btn-outline-danger" onclick="loadTabContent('${statut}', 1)">
                    <i class="fas fa-redo me-2"></i>Réessayer
                </button>
            </div>
        `).show();
    }

    @endif
});

// Définition globale de la fonction loadMore pour les boutons onclick
@if($detailsCategorie)
window.loadMore = function(statut, nextPage) {
    debugLog(`🔄 DEBUG PAIEMENTS GLOBAL: loadMore("${statut}", ${nextPage})`);

    // Éviter les clics multiples
    const loadMoreBtn = event ? $(event.target) : $(`.load-more-btn[data-statut="${statut}"]`);
    if (loadMoreBtn.prop('disabled')) {
        debugLog('🚫 Bouton déjà en cours de chargement, abandon');
        return;
    }

    // Désactiver temporairement le bouton
    loadMoreBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Chargement...');

    const categoryId = {{ $categoryId ?? 'null' }};
    const baseParams = {
        @if(request('filiere_id'))
        filiere_id: {{ request('filiere_id') }},
        @endif
        @if(request('niveau_id'))
        niveau_id: {{ request('niveau_id') }},
        @endif
        @if(request('annee_id'))
        annee_id: {{ request('annee_id') }},
        @endif
        category_id: categoryId
    };

    const ajaxUrl = `{{ route('esbtp.paiements.suivi-categories.load', 'STATUT_PLACEHOLDER') }}`
        .replace('STATUT_PLACEHOLDER', statut);

    const params = {
        ...baseParams,
        page: nextPage,
        per_page: 20
    };

    debugLog(`📡 DEBUG PAIEMENTS GLOBAL: AJAX vers ${ajaxUrl}`);
    debugLog(`📋 DEBUG PAIEMENTS GLOBAL: Params:`, params);

    $.ajax({
        url: ajaxUrl,
        method: 'GET',
        data: params,
        success: function(response) {
            debugLog(`✅ SUCCESS: Page ${nextPage} chargée pour "${statut}"`);

            const contentContainer = $(`#${statut} .content-container`);
            const newStudents = $(response.html);

            // Ajouter les nouveaux étudiants à la liste existante
            const studentsList = contentContainer.find('.students-list');
            if (studentsList.length > 0) {
                // Les pages suivantes retournent directement les student-cards, pas dans un conteneur
                const newCards = $(response.html);
                studentsList.append(newCards);
                debugLog(`➕ ${newCards.length} nouvelles cartes ajoutées à .students-list`);
            } else {
                contentContainer.append(response.html);
                debugLog(`📄 Contenu ajouté directement au conteneur`);
            }

            // Supprimer l'ancien bouton charger plus
            contentContainer.find('.load-more-container').remove();

            // Ajouter le nouveau bouton si il y a plus de données
            if (response.has_more) {
                const nextPage = response.current_page + 1;
                const remaining = response.total - (response.current_page * 20);
                const loadMoreBtn = `
                    <div class="load-more-container" style="text-align: center; margin: 30px 0;">
                        <button class="btn btn-primary" onclick="loadMore('${statut}', ${nextPage})"
                                style="padding: 12px 24px; border-radius: 8px; font-weight: 500;">
                            <i class="fas fa-plus me-2"></i>Charger plus d'étudiants (${remaining} restants)
                        </button>
                    </div>
                `;
                contentContainer.append(loadMoreBtn);
            }
        },
        error: function(xhr, status, error) {
            debugError('❌ ERREUR AJAX:', {
                status: xhr.status,
                statusText: xhr.statusText,
                error: error,
                response: xhr.responseText
            });

            // Réactiver le bouton en cas d'erreur
            loadMoreBtn.prop('disabled', false).html('<i class="fas fa-plus me-2"></i>Charger plus d\'étudiants');

            let errorMessage = 'Erreur lors du chargement';
            try {
                const errorData = JSON.parse(xhr.responseText);
                if (errorData.error) {
                    errorMessage = errorData.error;
                }
            } catch (e) {
                errorMessage = `Erreur ${xhr.status}: ${xhr.statusText}`;
            }

            alert(errorMessage);
        }
    });
};
@endif

</script>
@endpush

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
                    <li><strong>Revenir ici</strong> : Les paiements par catégorie se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des paiements dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les paiements de 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les paiements de 2023-2024
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function showYearChangeInfo() {
    $('#yearChangeModal').modal('show');
}

// Gérer la fermeture de la modal d'info année
$(document).ready(function() {
    // Gérer la fermeture avec le bouton X
    $('#yearChangeModal .close[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });
    
    // Gérer la fermeture avec le bouton Fermer
    $('#yearChangeModal button[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });
});
</script>