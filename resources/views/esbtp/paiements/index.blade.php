@extends('layouts.app')

@section('title', 'Suivi des Paiements - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/cursor-fix.css') }}">
<style>
    .btn-acasi.small {
        padding: var(--space-xs) var(--space-sm);
        font-size: var(--text-small);
        border-radius: var(--radius-small);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Suivi des Paiements</h1>
                <p class="header-subtitle">Monitoring des paiements étudiants et relances automatiques</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-acasi secondary" id="paiements-refresh-btn" title="Rafraîchir les données">
                    <i class="fas fa-sync-alt"></i>Rafraîchir
                </button>
                <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="btn-acasi secondary">
                    <i class="fas fa-chart-bar"></i>Suivi par Catégorie
                </a>
                @can('create-paiements')
                <a href="{{ route('esbtp.paiements.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus"></i>Nouveau Paiement
                </a>
                @endcan
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

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
                        Les paiements affichés correspondent à l'année académique courante.
                    </small>
                </div>
            </div>
        </div>

        <!-- KPI Cards Harmonisées avec le Système de Catégories -->
        <div id="paiements-metrics-container">
            @include('esbtp.paiements.partials.metrics', ['stats' => $stats])
        </div>

        <!-- Filtres et Actions -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <form action="{{ route('esbtp.paiements.index') }}" method="GET" id="paiements-filter-form">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Matricule, nom, n° reçu..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Statut</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Tous</option>
                                <option value="en_attente" {{ request('status') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                                <option value="validé" {{ request('status') == 'validé' ? 'selected' : '' }}>Validé</option>
                                <option value="rejeté" {{ request('status') == 'rejeté' ? 'selected' : '' }}>Rejeté</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ request('date_debut') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ request('date_fin') }}">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn-acasi primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau des Paiements -->
        <div id="paiements-table-container"
             data-refresh-url="{{ route('esbtp.paiements.refresh') }}"
             data-last-updated="{{ optional($lastUpdatedAt)->toIso8601String() }}">
            @include('esbtp.paiements.partials.table', ['paiements' => $paiements])
        </div>

        @if(auth()->user()->hasRole('superAdmin'))
        <div id="bulk-actions-bar" style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
             background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; padding: 15px 30px;
             border-radius: 50px; box-shadow: 0 10px 40px rgba(4, 83, 203, 0.4); z-index: 1050;
             animation: slideUp 0.3s ease-out;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
                    <span id="selected-count" style="font-weight: 600; font-size: 1.1rem;">0</span>
                    <span style="opacity: 0.9;">paiement(s) sélectionné(s)</span>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-light btn-sm" onclick="bulkValider()"
                            style="padding: 8px 20px; border-radius: 25px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <i class="fas fa-check-double me-1"></i>Valider la sélection
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="openBulkRejetModal()"
                            style="padding: 8px 20px; border-radius: 25px; font-weight: 600;">
                        <i class="fas fa-times me-1"></i>Rejeter la sélection
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="clearSelection()"
                            style="padding: 8px 20px; border-radius: 25px; font-weight: 600;">
                        <i class="fas fa-times-circle me-1"></i>Annuler
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
}

.table th {
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid #e5e7eb;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Styles pour les dropdowns PDF compacts */
.pdf-dropdown .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    min-width: auto;
}

.pdf-dropdown .dropdown-menu {
    min-width: 140px;
    font-size: 0.875rem;
}

.pdf-dropdown .dropdown-item {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.pdf-dropdown .dropdown-item i {
    width: 14px;
    text-align: center;
}

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

<script>
function showYearChangeInfo() {
    $('#yearChangeModal').modal('show');
}

(function () {
    const pollingInterval = 30000; // 30 secondes
    let pollingTimer = null;
    let lastUpdatedAt = null;
    let currentPaiementsCount = 0; // Nombre actuel de paiements
    let currentUrl = window.location.href;

    /**
     * Met à jour l'affichage de la barre d'actions groupées
     * et le compteur de paiements sélectionnés
     */
    window.updateBulkActionsBar = function() {
        const checkedCount = $('.paiement-checkbox:checked').length;
        const $bulkActionsBar = $('#bulk-actions-bar');
        const $selectedCountSpan = $('#selected-count');

        console.log('📊 updateBulkActionsBar appelée, checkboxes cochées:', checkedCount);

        if (checkedCount > 0) {
            console.log('✅ Affichage de la barre d\'actions groupées');
            $bulkActionsBar.show();  // Utiliser .show() au lieu de .addClass('show')
            $selectedCountSpan.text(checkedCount);
        } else {
            console.log('❌ Masquage de la barre d\'actions groupées');
            $bulkActionsBar.hide();  // Utiliser .hide() au lieu de .removeClass('show')
        }

        // Mettre à jour l'état de la checkbox "Tout sélectionner"
        const totalCheckboxes = $('.paiement-checkbox').length;
        $('#select-all').prop('checked', checkedCount === totalCheckboxes && totalCheckboxes > 0);
    };

    /**
     * Initialiser les écouteurs d'événements pour les checkboxes
     */
    function initCheckboxListeners() {
        console.log('🔧 Initialisation des listeners de checkboxes...');

        // Checkbox "Tout sélectionner"
        $(document).off('change', '#select-all').on('change', '#select-all', function() {
            const isChecked = $(this).prop('checked');
            console.log('☑️ Select-all changé:', isChecked);
            $('.paiement-checkbox').prop('checked', isChecked);
            updateBulkActionsBar();
        });

        // Checkboxes individuelles
        $(document).off('change', '.paiement-checkbox').on('change', '.paiement-checkbox', function() {
            console.log('☑️ Checkbox individuelle changée');
            updateBulkActionsBar();
        });

        console.log('✅ Listeners de checkboxes initialisés');
    }

    /**
     * Fetch les données depuis le serveur et met à jour le DOM
     */
    function fetchPaiementsData(showLog = true) {
        const spinner = document.getElementById('paiements-refresh-spinner');
        const btn = document.getElementById('paiements-refresh-btn');
        const tableContainer = document.getElementById('paiements-table-container');

        // Afficher le spinner du bouton
        if (spinner && btn) {
            btn.style.display = 'none';
            spinner.classList.remove('d-none');
        }

        // 🎨 Ajouter overlay de chargement sur le tableau
        if (tableContainer) {
            tableContainer.style.position = 'relative';

            const loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'table-loading-overlay';
            loadingOverlay.style.position = 'absolute';
            loadingOverlay.style.top = '0';
            loadingOverlay.style.left = '0';
            loadingOverlay.style.width = '100%';
            loadingOverlay.style.height = '100%';
            loadingOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.alignItems = 'center';
            loadingOverlay.style.justifyContent = 'center';
            loadingOverlay.style.zIndex = '10';
            loadingOverlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';

            tableContainer.appendChild(loadingOverlay);
        }

        const params = new URLSearchParams(window.location.search);
        const refreshUrl = '{{ route('esbtp.paiements.refresh') }}?' + params.toString();

        fetch(refreshUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Vérifier si on a reçu les données attendues
            if (data.table && data.metrics) {
                // Mettre à jour les KPI metrics
                if (data.metrics) {
                    document.getElementById('paiements-metrics-container').innerHTML = data.metrics;
                }

                // Mettre à jour le tableau
                if (data.table) {
                    document.getElementById('paiements-table-container').innerHTML = data.table;
                    // Réinitialiser les listeners
                    initCheckboxListeners();

                    // Compter le nombre de lignes de paiements dans le tableau
                    currentPaiementsCount = document.querySelectorAll('tr[data-paiement-id]').length;
                    console.log('📊 Nombre de paiements affichés:', currentPaiementsCount);
                }

                // Mettre à jour l'URL sans recharger la page
                if (data.url) {
                    history.pushState({}, '', data.url);
                    currentUrl = data.url;
                }

                // Mettre à jour le timestamp
                if (data.last_updated_at) {
                    lastUpdatedAt = data.last_updated_at;
                }

                if (showLog) {
                    console.log('✅ Données rafraîchies avec succès');
                }
            } else {
                console.error('❌ Réponse invalide du serveur:', data);
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors du refresh:', error);
        })
        .finally(() => {
            // Masquer le spinner du bouton
            if (spinner && btn) {
                spinner.classList.add('d-none');
                btn.style.display = 'flex';
            }

            // 🎨 Retirer l'overlay de chargement
            const overlay = document.getElementById('table-loading-overlay');
            if (overlay) {
                overlay.remove();
            }

            if (tableContainer) {
                tableContainer.style.position = '';
            }
        });
    }

    /**
     * Vérifie s'il y a des changements sans charger toutes les données
     */
    function checkForUpdates() {
        const params = new URLSearchParams(window.location.search);
        const checkUrl = '{{ route('esbtp.paiements.check-updates') }}?' + params.toString();

        fetch(checkUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Comparer avec l'état actuel
            const hasChanges = (
                data.count !== currentPaiementsCount ||
                data.last_updated_at !== lastUpdatedAt
            );

            if (hasChanges) {
                console.log('🆕 Changements détectés! Count:', currentPaiementsCount, '→', data.count, '| Last update:', lastUpdatedAt, '→', data.last_updated_at);

                // Mettre à jour les valeurs courantes
                currentPaiementsCount = data.count;
                lastUpdatedAt = data.last_updated_at;

                // Rafraîchir les données
                fetchPaiementsData(false);
            } else {
                console.log('✓ Pas de changements (count:', data.count, ')');
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors de la vérification des mises à jour:', error);
        });
    }

    /**
     * Démarre le polling automatique intelligent
     */
    function startPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
        }

        pollingTimer = setInterval(() => {
            console.log('🔄 Vérification des changements...');
            checkForUpdates();
        }, pollingInterval);

        console.log(`✅ Polling intelligent démarré (intervalle: ${pollingInterval}ms)`);
    }

    /**
     * Arrête le polling automatique
     */
    function stopPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
            pollingTimer = null;
            console.log('⏸️ Polling arrêté');
        }
    }

    /**
     * Intercepte la soumission du formulaire de filtres
     * pour faire une requête AJAX au lieu de recharger la page
     */
    $('#paiements-filter-form').off('submit').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const params = new URLSearchParams(formData);

        // Construire l'URL avec les paramètres de filtres
        const newUrl = '{{ route('esbtp.paiements.index') }}?' + params.toString();

        // Mettre à jour l'URL dans le navigateur
        history.pushState({}, '', newUrl);
        currentUrl = newUrl;

        // Fetch les données
        fetchPaiementsData();
    });

    /**
     * Intercepte les clics sur les liens de pagination
     * pour faire une requête AJAX au lieu de recharger la page
     */
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();

        const url = $(this).attr('href');

        if (!url || url === '#') {
            return;
        }

        // Mettre à jour l'URL dans le navigateur
        history.pushState({}, '', url);
        currentUrl = url;

        // Fetch les données
        fetchPaiementsData();
    });

    /**
     * Gestion du bouton "Rafraîchir" manuel
     */
    $('#paiements-refresh-btn').off('click').on('click', function() {
        console.log('🔄 Refresh manuel déclenché');
        fetchPaiementsData();
    });

    /**
     * Gestion du bouton retour/avancer du navigateur
     */
    window.addEventListener('popstate', function() {
        console.log('⬅️ Navigation navigateur détectée');
        fetchPaiementsData();
    });

    /**
     * Rafraîchit une ligne spécifique de paiement après validation/rejet
     * avec animation de lumière verte/rouge qui parcourt la ligne
     */
    window.refreshPaiementLigne = function(paiementId, actionType = 'validate') {
        console.log('🔄 Refresh ligne paiement:', paiementId, 'action:', actionType);

        const row = document.querySelector(`tr[data-paiement-id="${paiementId}"]`);

        if (!row) {
            console.error('❌ Ligne paiement introuvable:', paiementId);
            return;
        }

        // Sauvegarder l'état de la checkbox avant refresh
        const checkbox = row.querySelector('.paiement-checkbox');
        const wasChecked = checkbox ? checkbox.checked : false;

        // Afficher un spinner dans la ligne
        const actionCell = row.querySelector('td:last-child');
        if (actionCell) {
            const originalContent = actionCell.innerHTML;
            actionCell.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
        }

        // Fetch la ligne mise à jour
        const refreshUrl = `/esbtp/paiements/${paiementId}/refresh-ligne`;

        fetch(refreshUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.html) {
                console.log('✅ HTML reçu, parsing...');

                // Parser le HTML en utilisant un tbody temporaire
                const tbody = document.createElement('tbody');
                tbody.innerHTML = data.html;

                // Extraire le TR
                const newRow = tbody.querySelector('tr');

                if (newRow) {
                    console.log('✅ TR extrait avec succès');

                    // ✨ Animation lumière (verte ou rouge) qui parcourt la ligne de gauche à droite
                    newRow.style.position = 'relative';
                    newRow.style.overflow = 'hidden';

                    // Créer l'overlay animé selon le type d'action
                    const overlay = document.createElement('div');
                    overlay.style.position = 'absolute';
                    overlay.style.top = '0';
                    overlay.style.left = '-100%';  // Commence hors écran à gauche
                    overlay.style.width = '100%';
                    overlay.style.height = '100%';

                    // Couleur selon l'action (vert pour validate, rouge pour reject)
                    if (actionType === 'validate') {
                        overlay.style.background = 'linear-gradient(to right, rgba(40, 167, 69, 0), rgba(40, 167, 69, 0.7), rgba(40, 167, 69, 0))';
                    } else if (actionType === 'reject') {
                        overlay.style.background = 'linear-gradient(to right, rgba(220, 53, 69, 0), rgba(220, 53, 69, 0.7), rgba(220, 53, 69, 0))';
                    }

                    overlay.style.pointerEvents = 'none';
                    overlay.style.transition = 'left 3s ease-out';
                    overlay.style.zIndex = '1';

                    newRow.appendChild(overlay);

                    // Remplacer l'ancienne ligne
                    row.replaceWith(newRow);

                    // Déclencher l'animation après un petit délai
                    setTimeout(() => {
                        overlay.style.left = '100%';  // Se déplace vers la droite
                    }, 10);

                    // Nettoyer après l'animation
                    setTimeout(() => {
                        overlay.remove();
                        newRow.style.position = '';
                        newRow.style.overflow = '';
                    }, 3100);

                    // Restaurer l'état de la checkbox si elle était cochée
                    if (wasChecked) {
                        const newCheckbox = newRow.querySelector('.paiement-checkbox');
                        if (newCheckbox) {
                            newCheckbox.checked = true;
                            console.log('✅ Checkbox restauré');
                        }
                    }

                    // Toujours mettre à jour la barre d'actions après refresh
                    updateBulkActionsBar();

                    console.log('🎉 Ligne rafraîchie avec succès:', paiementId);
                } else {
                    throw new Error('HTML retourné invalide (pas de TR)');
                }
            } else {
                throw new Error(data.message || 'Réponse serveur invalide');
            }
        })
        .catch(error => {
            console.error('❌ Erreur refresh ligne:', error);
            console.error('❌ Message d\'erreur:', error.message);

            // Fallback: recharger la page en cas d'erreur
            alert('Erreur lors de la mise à jour: ' + error.message + '. La page va se recharger.');
            location.reload();
        });
    };

    /**
     * Initialisation au chargement de la page
     */
    $(document).ready(function() {
        console.log('✅ Scripts paiements initialisés');

        // Initialiser les listeners de checkboxes
        initCheckboxListeners();

        // Démarrer le polling automatique
        startPolling();

        // Vérifier combien de checkboxes existent au chargement
        console.log('🔍 Vérification checkboxes au chargement:');
        console.log('   - Total checkboxes paiement:', $('.paiement-checkbox').length);
        console.log('   - Select-all existe:', $('#select-all').length > 0);
        console.log('   - Bulk actions bar existe:', $('#bulk-actions-bar').length > 0);

        // Initialiser l'état de la barre d'actions groupées
        updateBulkActionsBar();

        // Auto-submit quand on change un select ou une date
        $('#status, #date_debut, #date_fin').off('change').on('change', function() {
            console.log('📝 Changement détecté, soumission automatique du formulaire');
            $('#paiements-filter-form').submit();
        });

        /**
         * Intercepter les clics sur les boutons de validation de paiement
         * Utilisation de addEventListener avec capture phase pour intercepter AVANT les autres handlers
         */
        console.log('🎯 Installation du handler de validation avec capture phase...');

        document.addEventListener('click', function(e) {
            // Vérifier si le clic est sur un bouton de validation ou un de ses enfants
            let btn = e.target.closest('.valider-paiement-btn');

            // Fallback: vérifier aussi btn-outline-success (au cas où la classe serait différente)
            if (!btn) {
                btn = e.target.closest('.btn-outline-success[data-paiement-id]');
            }

            if (btn) {
                console.log('🔘 Clic détecté sur bouton valider (CAPTURE PHASE)');
                console.log('🎯 Bouton trouvé:', btn);

                // STOP IMMÉDIATEMENT tout avant même de vérifier quoi que ce soit
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const paiementId = btn.getAttribute('data-paiement-id');
                console.log('📋 Paiement ID:', paiementId);

                if (!paiementId) {
                    console.error('❌ Pas de paiement ID trouvé sur le bouton');
                    return false;
                }

                if (!confirm('Êtes-vous sûr de vouloir valider ce paiement ?')) {
                    console.log('⏸️ Validation annulée par l\'utilisateur');
                    return false;
                }

                console.log('🔄 Lancement validation AJAX pour paiement:', paiementId);

                // Récupérer l'URL depuis l'attribut data
                const actionUrl = btn.getAttribute('data-action-url');
                console.log('🌐 URL d\'action:', actionUrl);

                if (!actionUrl) {
                    console.error('❌ Pas d\'URL d\'action trouvée sur le bouton');
                    return false;
                }

                fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => {
                    console.log('📡 Réponse serveur reçue:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('📦 Données JSON:', data);
                    if (data.success) {
                        console.log('✅ Paiement validé, lancement refresh ligne');
                        // Rafraîchir la ligne avec animation verte
                        window.refreshPaiementLigne(paiementId, 'validate');
                    } else {
                        alert('Erreur: ' + (data.message || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    console.error('❌ Erreur validation:', error);
                    alert('Erreur lors de la validation du paiement: ' + error.message);
                });

                return false;
            }
        }, true); // true = capture phase (s'exécute AVANT le bubbling)

        console.log('✅ Handler de validation installé avec capture phase');
    });
})();
</script>

<!-- Fonctions globales pour actions groupées (hors IIFE) -->
<script>
// Note: updateBulkActionsBar() est déjà définie dans le IIFE principal ci-dessus
// Pas besoin de la redéfinir ici

function bulkValider() {
    const selectedIds = getSelectedPaiementIds();

    if (selectedIds.length === 0) {
        alert('Veuillez sélectionner au moins un paiement.');
        return;
    }

    if (!confirm(`Êtes-vous sûr de vouloir valider ${selectedIds.length} paiement(s) ?`)) {
        return;
    }

    console.log('🔄 Validation en masse de', selectedIds.length, 'paiements:', selectedIds);

    // Requête AJAX au lieu de form submit
    fetch('{{ route('esbtp.paiements.bulk-valider') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            paiements: selectedIds
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ Réponse bulk validation:', data);

        if (data.success) {
            // Rafraîchir chaque ligne validée avec animation
            selectedIds.forEach((id, index) => {
                setTimeout(() => {
                    if (typeof window.refreshPaiementLigne === 'function') {
                        window.refreshPaiementLigne(id, 'validate');
                    }
                }, index * 100); // Décalage de 100ms entre chaque ligne
            });

            // Message de succès
            alert(data.message || 'Paiements validés avec succès !');

            // Masquer la barre d'actions
            $('#bulk-actions-bar').hide();
        } else {
            alert(data.message || 'Erreur lors de la validation.');
        }
    })
    .catch(error => {
        console.error('❌ Erreur bulk validation:', error);
        alert('Erreur lors de la validation. Veuillez réessayer.');
    });
}

function openBulkRejetModal() {
    const selectedIds = getSelectedPaiementIds();

    if (selectedIds.length === 0) {
        alert('Veuillez sélectionner au moins un paiement.');
        return;
    }

    $('#bulk-rejet-count').text(selectedIds.length);

    const container = $('#bulk-selected-paiements');
    container.empty();

    selectedIds.forEach(function(id) {
        container.append($('<input>', {
            type: 'hidden',
            name: 'paiements[]',
            value: id
        }));
    });

    $('#bulk_motif_rejet').val('');
    $('#bulk_confirmer_rejet').prop('checked', false);

    // Bootstrap 5 modal
    const modal = new bootstrap.Modal(document.getElementById('bulkRejetModal'));
    modal.show();
}

function clearSelection() {
    $('.paiement-checkbox').prop('checked', false);
    $('#select-all').prop('checked', false);
    updateBulkActionsBar();
}

function getSelectedPaiementIds() {
    const ids = [];
    $('.paiement-checkbox:checked').each(function() {
        ids.push($(this).val());
    });
    return ids;
}

// Intercepter la soumission du formulaire de rejet en masse
$(document).ready(function() {
    $('#bulk-rejet-form').off('submit').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const motifRejet = $('#bulk_motif_rejet').val();
        const selectedIds = getSelectedPaiementIds();

        if (!motifRejet.trim()) {
            alert('Veuillez saisir un motif de rejet.');
            return;
        }

        if (!$('#bulk_confirmer_rejet').is(':checked')) {
            alert('Veuillez confirmer le rejet.');
            return;
        }

        console.log('🔄 Rejet en masse de', selectedIds.length, 'paiements:', selectedIds);

        // Requête AJAX
        fetch('{{ route('esbtp.paiements.bulk-rejeter') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                paiements: selectedIds,
                motif_rejet: motifRejet
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Réponse bulk rejet:', data);

            if (data.success) {
                // Fermer le modal
                bootstrap.Modal.getInstance(document.getElementById('bulkRejetModal')).hide();

                // Rafraîchir chaque ligne rejetée avec animation
                selectedIds.forEach((id, index) => {
                    setTimeout(() => {
                        if (typeof window.refreshPaiementLigne === 'function') {
                            window.refreshPaiementLigne(id, 'reject');
                        }
                    }, index * 100); // Décalage de 100ms
                });

                // Message de succès
                alert(data.message || 'Paiements rejetés avec succès !');

                // Masquer la barre d'actions
                $('#bulk-actions-bar').hide();
            } else {
                alert(data.message || 'Erreur lors du rejet.');
            }
        })
        .catch(error => {
            console.error('❌ Erreur bulk rejet:', error);
            alert('Erreur lors du rejet. Veuillez réessayer.');
        });
    });
});
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
                    <li><strong>Revenir ici</strong> : Les paiements affichés se mettront à jour automatiquement</li>
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

<!-- Modal de rejet groupé -->
@if(auth()->user()->hasRole('superAdmin'))
<div class="modal fade" id="bulkRejetModal" tabindex="-1" aria-labelledby="bulkRejetModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulk-rejet-form" method="POST" action="{{ route('esbtp.paiements.bulk-rejeter') }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkRejetModalLabel">
                        <i class="fas fa-times-circle me-2"></i>
                        Rejeter les paiements sélectionnés
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Vous êtes sur le point de rejeter <strong><span id="bulk-rejet-count">0</span> paiement(s)</strong>.
                    </div>

                    <div class="mb-3">
                        <label for="bulk_motif_rejet" class="form-label">Motif du rejet <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="bulk_motif_rejet" name="motif_rejet" rows="4"
                                  placeholder="Expliquez pourquoi ces paiements sont rejetés..." required></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="bulk_confirmer_rejet" required>
                        <label class="form-check-label" for="bulk_confirmer_rejet">
                            Je confirme le rejet de ces paiements
                        </label>
                    </div>

                    <div id="bulk-selected-paiements"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i>Rejeter les paiements
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

