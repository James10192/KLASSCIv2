@extends('layouts.app')

@section('title', 'Configuration des Frais - ESBTP-yAKRO')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
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
                <a href="{{ route('esbtp.frais.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-list"></i>Catégories
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
                <div class="kpi-grid" style="margin-top: var(--space-lg);">
                    @foreach($classes as $classe)
                        <div class="card-moderne kpi-card animate-slide-up" style="border: 1px solid #e5e7eb;">
                            <!-- En-tête classe -->
                            <div style="display: flex; align-items: center; margin-bottom: var(--space-md);">
                                <div style="width: 40px; height: 40px; background: var(--primary); border-radius: var(--radius-circle); display: flex; align-items: center; justify-content: center; margin-right: var(--space-sm);">
                                    <i class="fas fa-graduation-cap" style="color: white; font-size: 16px;"></i>
                                </div>
                                <div>
                                    <div class="font-bold color-primary">{{ $classe->name }}</div>
                                    <div style="font-size: var(--text-small); color: var(--text-secondary);">
                                        {{ $classe->filiere->name }} - {{ $classe->niveau->name }}
                                    </div>
                                </div>
                            </div>

                            <!-- Effectif -->
                            <div class="kpi-title">Effectif Inscrit</div>
                            <div class="kpi-value color-accent">{{ $classe->effectif }}</div>
                            <div style="font-size: var(--text-small); color: var(--text-secondary); margin-bottom: var(--space-md);">étudiants actifs</div>

                            <!-- Frais configurés -->
                            <div style="display: flex; flex-wrap: wrap; gap: var(--space-xs); margin-bottom: var(--space-md);">
                                @if($classe->obligatoires_configures > 0)
                                    <span class="badge danger">
                                        {{ $classe->obligatoires_configures }}/{{ $classe->total_obligatoires }} obligatoires
                                    </span>
                                @endif
                                @if($classe->optionnels_configures > 0)
                                    <span class="badge success">
                                        {{ $classe->optionnels_configures }}/{{ $classe->total_optionnels }} optionnels
                                    </span>
                                @endif
                                @if($classe->frais_configures->count() == 0)
                                    <span class="badge" style="background: var(--neutral); color: white;">Aucun frais configuré</span>
                                @endif
                            </div>

                            <!-- Statut et action en vertical -->
                            <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
                                <!-- Statut -->
                                <div style="display: flex; justify-content: center;">
                                    @if($classe->obligatoires_configures == $classe->total_obligatoires)
                                        <span class="badge success">
                                            <i class="fas fa-check-circle me-1"></i>Complet
                                        </span>
                                    @elseif($classe->obligatoires_configures > 0)
                                        <span class="badge warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Partiel
                                        </span>
                                    @else
                                        <span class="badge danger">
                                            <i class="fas fa-times-circle me-1"></i>Non configuré
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- Bouton action -->
                                <button type="button" 
                                        class="btn-acasi primary configure-btn" 
                                        style="padding: var(--space-xs) var(--space-sm); width: 100%;" 
                                        title="Configurer les frais"
                                        data-filiere-id="{{ $classe->filiere->id }}"
                                        data-niveau-id="{{ $classe->niveau->id }}"
                                        data-filiere-name="{{ $classe->filiere->name }}"
                                        data-niveau-name="{{ $classe->niveau->name }}"
                                        onclick="openConfigurationModal(this)">
                                    <i class="fas fa-cogs"></i>
                                    <span class="configure-text">Configurer</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center" style="padding: var(--space-xl); color: var(--text-secondary);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: var(--space-lg); color: var(--neutral);"></i>
                    <h5 style="color: var(--text-secondary); margin-bottom: var(--space-sm);">Aucune classe trouvée</h5>
                    <p style="color: var(--text-muted);">Vérifiez que vous avez des filières et niveaux d'étude actifs.</p>
                </div>
            @endif
        </div>

        <!-- Modal de Configuration des Frais -->
        <div class="modal fade" id="configurationModal" tabindex="-1" aria-labelledby="configurationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <!-- Header du Modal -->
                    <div class="modal-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--accent-blue) 100%); color: white; border-bottom: none; border-radius: var(--radius-medium) var(--radius-medium) 0 0; padding: var(--space-lg);">
                        <div>
                            <h4 class="modal-title" id="configurationModalLabel" style="margin: 0; font-weight: 700;">
                                <i class="fas fa-cogs me-2"></i>Configuration des Frais
                            </h4>
                            <div id="modalClassInfo" style="margin-top: var(--space-sm); font-size: var(--text-small); opacity: 0.9;">
                                <i class="fas fa-graduation-cap me-1"></i><span id="modalFiliere"></span> - <span id="modalNiveau"></span>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer" style="filter: brightness(0) invert(1);"></button>
                    </div>

                    <!-- Body du Modal -->
                    <div class="modal-body" style="padding: var(--space-lg); background-color: var(--background);">
                        <div style="margin-bottom: var(--space-lg); padding: var(--space-md); background: rgba(6, 182, 212, 0.1); border-radius: var(--radius-small); border-left: 4px solid var(--accent-blue);">
                            <div style="font-size: var(--text-small); color: var(--text-primary); line-height: 1.6;">
                                <i class="fas fa-info-circle me-1" style="color: var(--accent-blue);"></i>
                                <strong>Configurez les montants et échéances spécifiques pour cette classe.</strong>
                                Les frais <span class="badge danger" style="font-size: var(--text-small);">obligatoires</span> doivent être configurés.
                            </div>
                        </div>

                        <form id="configurationForm" method="POST" action="{{ route('esbtp.frais.update-configuration') }}">
                            @csrf
                            <input type="hidden" id="modalFiliereId" name="filiere_id">
                            <input type="hidden" id="modalNiveauId" name="niveau_id">

                            <div id="categoriesContainer" class="resultats-grid">
                                <!-- Les catégories seront chargées ici dynamiquement -->
                            </div>
                        </form>
                    </div>

                    <!-- Footer du Modal -->
                    <div class="modal-footer" style="background-color: #f8fafc; border-top: 1px solid #e5e7eb; border-radius: 0 0 var(--radius-medium) var(--radius-medium); padding: var(--space-lg);">
                        <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal" style="padding: var(--space-md) var(--space-lg);">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="button" id="saveConfigurationBtn" class="btn-acasi primary" style="padding: var(--space-md) var(--space-lg);">
                            <i class="fas fa-save me-1"></i>Enregistrer la Configuration
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

/* Centrer le modal sur le viewport actuel (zone visible) */
#configurationModal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 1055 !important;
}

#configurationModal .modal-dialog {
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    margin: 0 !important;
    max-height: 90vh !important;
    width: 90vw !important;
    max-width: 1140px !important;
}

#configurationModal .modal-content {
    max-height: 90vh !important;
    border: none !important;
    border-radius: var(--radius-medium) !important;
    box-shadow: var(--shadow-elevated) !important;
}

#configurationModal .modal-body {
    max-height: calc(90vh - 200px) !important;
    overflow-y: auto !important;
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
    // Fonction pour ouvrir le modal de configuration
    window.openConfigurationModal = function(button) {
        const filiereId = button.dataset.filiereId;
        const niveauId = button.dataset.niveauId;
        const filiereName = button.dataset.filiereName;
        const niveauName = button.dataset.niveauName;
        
        // Mettre à jour les informations du modal
        document.getElementById('modalFiliereId').value = filiereId;
        document.getElementById('modalNiveauId').value = niveauId;
        document.getElementById('modalFiliere').textContent = filiereName;
        document.getElementById('modalNiveau').textContent = niveauName;
        
        // Charger les catégories pour cette classe
        loadCategoriesForClass(filiereId, niveauId);
        
        // Ouvrir le modal centré sur le viewport actuel
        const modal = new bootstrap.Modal(document.getElementById('configurationModal'));
        const modalElement = document.getElementById('configurationModal');
        
        // Positionner le modal par rapport au viewport actuel
        modalElement.addEventListener('show.bs.modal', function() {
            // Empêcher le body de scroller pendant l'affichage du modal
            document.body.style.overflow = 'hidden';
            
            // Forcer le modal à se positionner par rapport au viewport
            modalElement.style.position = 'fixed';
            modalElement.style.top = '0';
            modalElement.style.left = '0';
            modalElement.style.width = '100vw';
            modalElement.style.height = '100vh';
            modalElement.style.zIndex = '1055';
        });
        
        // Restaurer le scroll du body à la fermeture
        modalElement.addEventListener('hidden.bs.modal', function() {
            document.body.style.overflow = '';
        });
        
        modal.show();
        
        // Effet visuel sur le bouton
        const icon = button.querySelector('i');
        const text = button.querySelector('.configure-text');
        
        const originalIcon = icon.className;
        const originalText = text ? text.textContent : '';
        
        icon.className = 'fas fa-spinner fa-spin';
        if (text) text.textContent = 'Ouverture...';
        
        setTimeout(() => {
            icon.className = originalIcon;
            if (text) text.textContent = originalText;
        }, 1000);
    };
    
    // Fonction pour charger les catégories
    function loadCategoriesForClass(filiereId, niveauId) {
        const container = document.getElementById('categoriesContainer');
        container.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><small class="text-muted mt-2 d-block">Chargement des catégories...</small></div>';
        
        // Simuler le chargement des catégories (vous devrez implémenter l'endpoint AJAX)
        fetch(`{{ route('esbtp.frais.get-categories') }}?filiere_id=${filiereId}&niveau_id=${niveauId}`)
            .then(response => response.json())
            .then(data => {
                container.innerHTML = data.html;
                // Réappliquer les animations
                container.querySelectorAll('.animate-slide-up').forEach((el, index) => {
                    el.style.animationDelay = `${index * 0.1}s`;
                });
            })
            .catch(error => {
                console.error('Erreur:', error);
                container.innerHTML = '<div class="text-center p-4 text-danger"><i class="fas fa-exclamation-triangle fa-2x"></i><br><small class="mt-2 d-block">Erreur lors du chargement</small></div>';
            });
    }
    
    // Gestionnaire de sauvegarde
    document.getElementById('saveConfigurationBtn').addEventListener('click', function() {
        const form = document.getElementById('configurationForm');
        const saveBtn = this;
        
        // État de chargement
        const originalContent = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...';
        saveBtn.disabled = true;
        
        // Soumettre le formulaire
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer le modal
                bootstrap.Modal.getInstance(document.getElementById('configurationModal')).hide();
                
                // Afficher un message de succès
                showNotification('Configuration enregistrée avec succès!', 'success');
                
                // Recharger la page pour mettre à jour les statuts
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(data.message || 'Erreur lors de l\'enregistrement', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de l\'enregistrement', 'error');
        })
        .finally(() => {
            saveBtn.innerHTML = originalContent;
            saveBtn.disabled = false;
        });
    });
    
    // Fonction pour afficher les notifications
    function showNotification(message, type) {
        const notificationContainer = document.getElementById('notification-container') || createNotificationContainer();
        
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        notificationContainer.appendChild(notification);
        
        // Auto-remove après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    function createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notification-container';
        document.body.appendChild(container);
        return container;
    }
});
</script>
@endpush