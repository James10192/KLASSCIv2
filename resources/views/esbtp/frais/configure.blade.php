@extends('layouts.app')

@section('title', 'Configuration des Frais - ESBTP-yAKRO')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* Styles spécifiques pour la configuration des frais */
.configuration-card {
    border: 2px solid transparent;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.configuration-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, transparent, var(--primary), transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.configuration-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-elevated);
}

.configuration-card:hover::before {
    opacity: 1;
}

.configuration-card.configured {
    border-color: rgba(16, 185, 129, 0.3);
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.02));
}

.configuration-card.configured::before {
    background: linear-gradient(90deg, transparent, var(--success), transparent);
}

.configuration-card.partial {
    border-color: rgba(245, 158, 11, 0.3);
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(245, 158, 11, 0.02));
}

.configuration-card.partial::before {
    background: linear-gradient(90deg, transparent, var(--warning), transparent);
}

.configuration-card.not-configured {
    border-color: rgba(239, 68, 68, 0.3);
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.08), rgba(239, 68, 68, 0.02));
}

.configuration-card.not-configured::before {
    background: linear-gradient(90deg, transparent, var(--danger), transparent);
}

.class-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: var(--radius-circle);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    margin-bottom: var(--space-md);
}

.progress-ring {
    width: 60px;
    height: 60px;
    margin: var(--space-sm) auto;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-ring circle {
    transition: stroke-dashoffset 0.6s ease-in-out;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
}

.progress-ring svg {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.stats-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius-small);
    font-size: var(--text-small);
    font-weight: 600;
}

.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.8) !important;
}

.configuration-modal {
    backdrop-filter: blur(8px);
}

.category-config-card {
    border: 2px solid rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}

.category-config-card:hover {
    border-color: var(--primary);
    box-shadow: var(--shadow-elevated);
}

.category-config-card.mandatory {
    border-left: 4px solid var(--danger);
}

.category-config-card.optional {
    border-left: 4px solid var(--accent-blue);
}

/* Styles pour les onglets de configuration */
.nav-tabs {
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: var(--space-lg);
}

.nav-tabs .nav-link {
    border: none;
    padding: var(--space-md) var(--space-lg);
    color: var(--text-secondary);
    font-weight: 500;
    border-radius: var(--radius-small) var(--radius-small) 0 0;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.nav-tabs .nav-link:hover {
    background-color: rgba(30, 58, 138, 0.05);
    color: var(--primary);
    border-color: transparent;
}

.nav-tabs .nav-link.active {
    background-color: var(--primary);
    color: white;
    border-color: var(--primary);
    position: relative;
}

.nav-tabs .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: var(--primary);
}

.nav-tabs .nav-link .badge {
    font-size: var(--text-small);
    padding: 2px 6px;
    border-radius: var(--radius-small);
}

.nav-tabs .nav-link.active .badge {
    background-color: rgba(255, 255, 255, 0.2) !important;
    color: white !important;
}

/* Configuration forms */
.config-category-card {
    background: var(--surface);
    border: 1px solid #e5e7eb;
    border-radius: var(--radius-medium);
    padding: var(--space-lg);
    margin-bottom: var(--space-md);
    transition: all 0.2s ease;
}

.config-category-card:hover {
    box-shadow: var(--shadow-elevated);
    transform: translateY(-1px);
}

.config-category-card.mandatory {
    border-left: 4px solid var(--danger);
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(239, 68, 68, 0.01));
}

.config-category-card.optional {
    border-left: 4px solid var(--success);
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.01));
}

.config-option-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-sm);
    border: 1px solid #e5e7eb;
    border-radius: var(--radius-small);
    margin-bottom: var(--space-sm);
    background: var(--background);
    transition: all 0.2s ease;
}

.config-option-item:hover {
    background: rgba(30, 58, 138, 0.05);
    border-color: var(--primary);
}

.config-option-item:last-child {
    margin-bottom: 0;
}

.option-details {
    flex: 1;
}

.option-name {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--space-xs);
}

.option-description {
    font-size: var(--text-small);
    color: var(--text-secondary);
}

.option-price {
    font-weight: 700;
    color: var(--primary);
    margin-left: var(--space-md);
}

.add-option-btn {
    width: 100%;
    padding: var(--space-md);
    border: 2px dashed #d1d5db;
    background: transparent;
    border-radius: var(--radius-small);
    color: var(--text-secondary);
    font-size: var(--text-small);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.add-option-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(30, 58, 138, 0.05);
}

/* Modal Configuration Fix - Flexbox Centering */
#configurationModal.modal.show {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 1055 !important;
    padding: 1rem !important;
    box-sizing: border-box !important;
}

#configurationModal.modal.show .modal-dialog {
    position: static !important;
    margin: 0 !important;
    max-width: 90vw !important;
    width: 900px !important;
    max-height: 90vh !important;
    transform: none !important;
    display: flex !important;
    flex-direction: column !important;
}

#configurationModal .modal-content {
    background: white !important;
    border-radius: var(--radius-medium) !important;
    box-shadow: var(--shadow-elevated) !important;
    max-height: 100% !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
}

#configurationModal .modal-body {
    overflow-y: auto !important;
    flex-grow: 1 !important;
}

#configurationModal .modal-backdrop {
    z-index: 1054 !important;
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Configuration des Frais</h1>
                <p class="header-subtitle">Configuration des tarifs par classe et filière</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi accent-blue" title="Configurer les frais optionnels (transport, cantine)">
                    <i class="fas fa-sliders-h"></i>Frais Optionnels
                </a>
                <a href="{{ route('esbtp.frais.index') }}" class="btn-acasi primary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
            </div>
        </div>

        <!-- Messages d'état -->
        @if(session('success'))
            <div class="card-moderne" style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--success); margin-bottom: var(--space-lg);">
                <div style="padding: var(--space-md);">
                    <div class="color-success font-semibold">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="card-moderne" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger); margin-bottom: var(--space-lg);">
                <div style="padding: var(--space-md);">
                    <div class="color-danger font-semibold">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    </div>
                </div>
            </div>
        @endif

        <!-- Section instructions moderne -->
        <div class="soldes-section">
            <div class="soldes-grid">
                <div class="card-moderne solde-card">
                    <div class="solde-title">
                        <i class="fas fa-info-circle me-2"></i>Guide de Configuration
                    </div>
                    <div style="color: var(--text-primary); font-size: var(--text-normal); line-height: 1.6;">
                        <div class="mb-sm"><strong>Classe = Filière + Niveau d'étude</strong></div>
                        <div class="mb-sm">• Configurez les frais pour chaque classe</div>
                        <div class="mb-sm">• Les frais <span class="badge danger">obligatoires</span> doivent être configurés</div>
                        <div>• Les frais <span class="badge primary">optionnels</span> sont facultatifs</div>
                    </div>
                </div>
                
                <div class="card-moderne solde-card">
                    <div class="solde-title">
                        <i class="fas fa-tools me-2"></i>Actions Rapides
                    </div>
                    <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
                        <a href="{{ route('esbtp.frais.index') }}" class="btn-acasi secondary" style="justify-content: center;">
                            <i class="fas fa-list"></i>Gérer les Catégories
                        </a>
                        <a href="{{ route('esbtp.frais.create') }}" class="btn-acasi primary" style="justify-content: center;">
                            <i class="fas fa-plus"></i>Nouvelle Catégorie
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des classes moderne -->
        <div class="card-moderne" style="padding: var(--space-lg);">
            <div class="section-title">
                <i class="fas fa-graduation-cap me-2"></i>Classes et Configuration des Frais
            </div>
            
            @if($classes->count() > 0)
                <div class="resultats-grid" style="margin-top: var(--space-lg);">
                    @foreach($classes as $classe)
                        @php
                            $statusClass = '';
                            $statusIcon = '';
                            $statusText = '';
                            
                            if($classe->obligatoires_configures == $classe->total_obligatoires) {
                                $statusClass = 'configured';
                                $statusIcon = 'fa-check-circle';
                                $statusText = 'Complet';
                            } elseif($classe->obligatoires_configures > 0) {
                                $statusClass = 'partial';
                                $statusIcon = 'fa-exclamation-triangle';
                                $statusText = 'Partiel';
                            } else {
                                $statusClass = 'not-configured';
                                $statusIcon = 'fa-times-circle';
                                $statusText = 'Non configuré';
                            }
                        @endphp
                        
                        <div class="card-moderne configuration-card {{ $statusClass }} animate-slide-up">
                            <div class="resultat-card" style="padding: var(--space-lg);">
                                <!-- Header avec icône de classe -->
                                <div style="display: flex; align-items: center; margin-bottom: var(--space-lg);">
                                    <div class="class-icon">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div class="resultat-title" style="margin-bottom: var(--space-xs); color: var(--primary);">
                                            {{ $classe->name }}
                                        </div>
                                        <div style="font-size: var(--text-small); color: var(--text-secondary);">
                                            {{ $classe->filiere->name }} • {{ $classe->niveau->name }}
                                        </div>
                                        <div style="font-size: var(--text-small); color: var(--text-muted); margin-top: var(--space-xs);">
                                            <i class="fas fa-users me-1"></i>{{ $classe->effectif }} étudiants
                                        </div>
                                    </div>
                                </div>

                                <!-- Statistiques de configuration -->
                                <div style="margin-bottom: var(--space-lg);">
                                    <div class="resultat-title" style="margin-bottom: var(--space-sm);">Configuration des frais</div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md); margin-bottom: var(--space-md);">
                                        <div style="text-align: center; padding: var(--space-sm); background: rgba(239, 68, 68, 0.1); border-radius: var(--radius-small);">
                                            <div style="font-size: var(--amount-medium); font-weight: 700; color: var(--danger);">
                                                {{ $classe->obligatoires_configures }}/{{ $classe->total_obligatoires }}
                                            </div>
                                            <div style="font-size: var(--text-small); color: var(--danger); font-weight: 600;">Obligatoires</div>
                                        </div>
                                        <div style="text-align: center; padding: var(--space-sm); background: rgba(59, 130, 246, 0.1); border-radius: var(--radius-small);">
                                            @if($classe->optionnels_configures > 0)
                                                <div style="font-size: var(--amount-medium); font-weight: 700; color: var(--accent-blue);">
                                                    {{ $classe->optionnels_configures }}
                                                </div>
                                                <div style="font-size: var(--text-small); color: var(--accent-blue); font-weight: 600;">Assignés</div>
                                            @else
                                                <div style="font-size: var(--amount-medium); font-weight: 700; color: var(--text-muted);">
                                                    <i class="fas fa-minus"></i>
                                                </div>
                                                <div style="font-size: var(--text-small); color: var(--text-muted); font-weight: 600;">Non assignés</div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Progress ring pour le statut général -->
                                    @php
                                        $totalRequired = $classe->total_obligatoires;
                                        $totalConfigured = $classe->obligatoires_configures;
                                        $percentage = $totalRequired > 0 ? ($totalConfigured / $totalRequired) * 100 : 0;
                                        $circumference = 2 * 3.14159 * 25; // rayon de 25
                                        $strokeDashoffset = $circumference - ($percentage / 100) * $circumference;
                                    @endphp
                                    
                                    <div style="display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-md);">
                                        <div class="progress-ring">
                                            <svg width="60" height="60">
                                                <circle cx="30" cy="30" r="25" stroke="#e5e7eb" stroke-width="4" fill="transparent"/>
                                                <circle cx="30" cy="30" r="25" 
                                                        stroke="{{ $percentage == 100 ? '#10b981' : ($percentage > 0 ? '#f59e0b' : '#ef4444') }}" 
                                                        stroke-width="4" 
                                                        fill="transparent"
                                                        stroke-dasharray="{{ $circumference }}"
                                                        stroke-dashoffset="{{ $strokeDashoffset }}"/>
                                            </svg>
                                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: var(--text-small); font-weight: 700; color: var(--text-primary);">
                                                {{ number_format($percentage, 0) }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Badges de statut -->
                                <div style="display: flex; justify-content: center; margin-bottom: var(--space-lg);">
                                    <span class="stats-badge {{ $statusClass == 'configured' ? 'bg-success' : ($statusClass == 'partial' ? 'bg-warning' : 'bg-danger') }}" 
                                          style="color: white;">
                                        <i class="fas {{ $statusIcon }}"></i>{{ $statusText }}
                                    </span>
                                </div>

                                <!-- Actions -->
                                <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
                                    <button type="button" 
                                            class="btn-acasi primary configure-btn" 
                                            style="width: 100%; justify-content: center;" 
                                            title="Configurer les frais pour cette classe"
                                            data-filiere-id="{{ $classe->filiere->id }}"
                                            data-niveau-id="{{ $classe->niveau->id }}"
                                            data-filiere-name="{{ $classe->filiere->name }}"
                                            data-niveau-name="{{ $classe->niveau->name }}"
                                            onclick="openConfigurationModal(this)">
                                        <i class="fas fa-cogs"></i>
                                        <span class="configure-text">Configurer les Frais</span>
                                    </button>
                                    
                                    @if($classe->configurations->count() > 0)
                                        <button type="button" 
                                                class="btn-acasi secondary" 
                                                style="width: 100%; justify-content: center;" 
                                                title="Voir le détail des configurations"
                                                onclick="viewConfigurationDetails({{ $classe->filiere->id }}, {{ $classe->niveau->id }})">
                                            <i class="fas fa-eye"></i>
                                            <span>Voir Détails</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-graduation-cap"></i>
                    <p>Aucune classe trouvée</p>
                    <div style="font-size: var(--text-small); color: var(--text-muted); margin-top: var(--space-sm);">
                        Vérifiez que vous avez des filières et niveaux d'étude actifs
                    </div>
                </div>
            @endif
        </div>

        <!-- Modal Simple pour Configuration des Frais par Classe -->
        <div class="modal fade" id="configurationModal" tabindex="-1" aria-labelledby="configurationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <!-- Header simple -->
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="configurationModalLabel">
                            <i class="fas fa-graduation-cap me-2"></i>Configuration des Frais par Classe
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <!-- Body simple -->
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Classe :</strong> <span id="modalClasseInfo">-</span><br>
                            <small>Configurez les frais obligatoires pour cette classe (inscription, scolarité, examens).</small>
                        </div>

                        <!-- Formulaire simple -->
                        <form id="configurationForm" method="POST" action="{{ route('esbtp.frais.update-configuration') }}">
                            @csrf
                            <input type="hidden" id="modalFiliereId" name="filiere_id">
                            <input type="hidden" id="modalNiveauId" name="niveau_id">

                            <div id="categoriesContainer">
                                <!-- Les catégories seront chargées ici par AJAX -->
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                    <p class="mt-2 text-muted">Chargement des catégories...</p>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Footer simple -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="button" id="saveConfigurationBtn" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
/* Styles spécifiques pour améliorer l'intégration avec dashboard-moderne.css */

/* Styles supplémentaires pour la responsivité du modal */
@media (max-width: 576px) {
    #configurationModal.modal.show {
        padding: 0.5rem !important;
    }
    
    #configurationModal.modal.show .modal-dialog {
        max-width: 95vw !important;
        width: 95vw !important;
    }
}

/* Assurer que le backdrop couvre tout le viewport */
#configurationModal .modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
}

.me-1 {
    margin-right: 0.25rem;
}

.me-2 {
    margin-right: 0.5rem;
}

.ms-2 {
    margin-left: 0.5rem;
}

/* Amélioration responsive pour les grilles */
@media (max-width: 768px) {
    .resultats-grid {
        grid-template-columns: 1fr !important;
    }
    
    .kpi-grid {
        grid-template-columns: 1fr !important;
    }
    
    .soldes-grid {
        grid-template-columns: 1fr !important;
    }
}

/* Effets hover pour les cards de classe */
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

/* Animation pour les inputs de configuration */
input[type="number"]:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(30, 58, 138, 0.1);
}

/* Configuration section highlight */
#configuration-section {
    position: relative;
}

#configuration-section::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, var(--accent-blue), var(--primary), var(--accent-blue));
    border-radius: var(--radius-medium);
    z-index: -1;
    opacity: 0.3;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 0.3; }
    50% { opacity: 0.6; }
    100% { opacity: 0.3; }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM ready - Simple modal system');
    
    // Fonction simple pour ouvrir le modal
    window.openConfigurationModal = function(button) {
        console.log('Opening configuration modal...');
        
        const filiereId = button.dataset.filiereId;
        const niveauId = button.dataset.niveauId;
        const filiereName = button.dataset.filiereName;
        const niveauName = button.dataset.niveauName;
        
        // Vérifier Bootstrap
        if (typeof bootstrap === 'undefined') {
            alert('Bootstrap non disponible');
            return;
        }
        
        // Mettre à jour les informations du modal
        document.getElementById('modalFiliereId').value = filiereId;
        document.getElementById('modalNiveauId').value = niveauId;
        document.getElementById('modalClasseInfo').textContent = `${filiereName} - ${niveauName}`;
        
        // Ouvrir le modal
        const modalElement = document.getElementById('configurationModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        // Charger les catégories après ouverture
        modalElement.addEventListener('shown.bs.modal', function() {
            loadCategories(filiereId, niveauId);
        }, { once: true });
    };
    
    // Fonction pour charger les catégories
    function loadCategories(filiereId, niveauId) {
        const container = document.getElementById('categoriesContainer');
        const url = `{{ route('esbtp.frais.get-categories') }}?filiere_id=${filiereId}&niveau_id=${niveauId}&type=mandatory`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    container.innerHTML = data.html;
                } else {
                    container.innerHTML = '<div class="alert alert-warning">Aucune catégorie trouvée</div>';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                container.innerHTML = '<div class="alert alert-danger">Erreur de chargement</div>';
            });
    }
    
    // Gestionnaire de sauvegarde
    document.getElementById('saveConfigurationBtn').addEventListener('click', function() {
        const form = document.getElementById('configurationForm');
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('configurationModal')).hide();
                location.reload();
            } else {
                alert('Erreur: ' + (data.message || 'Impossible d\'enregistrer'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion');
        });
    });
});
</script>
@endpush