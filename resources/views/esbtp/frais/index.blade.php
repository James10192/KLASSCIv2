@extends('layouts.app')

@section('title', 'Gestion des Frais - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* Système d'onglets moderne */
.tabs-container {
    background: white;
    border-radius: var(--radius-medium);
    box-shadow: var(--shadow-card);
    overflow: hidden;
    margin-bottom: var(--space-lg);
}

.tabs-navigation {
    display: flex;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tab-button {
    flex: 1;
    padding: var(--space-lg);
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.8);
    font-size: var(--text-normal);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
    position: relative;
}

.tab-button:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
}

.tab-button.active {
    color: white;
    background: rgba(255, 255, 255, 0.15);
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: white;
}

.tab-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius-full);
    font-size: var(--text-small);
    font-weight: 600;
}

.tab-content {
    padding: var(--space-lg);
    min-height: 400px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.tab-header {
    margin-bottom: var(--space-lg);
    text-align: center;
}

.tab-title {
    font-size: var(--title-main);
    font-weight: 700;
    color: var(--primary);
    margin-bottom: var(--space-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
}

.tab-subtitle {
    color: var(--text-secondary);
    font-size: var(--text-normal);
}

.add-category-btn {
    position: absolute;
    top: var(--space-md);
    right: var(--space-md);
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--space-lg);
}

.category-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius-medium);
    box-shadow: var(--shadow-card);
    transition: all 0.3s ease;
    overflow: hidden;
}

.category-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
    border-color: var(--primary);
}

.category-header {
    padding: var(--space-lg);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    background: linear-gradient(135deg, rgba(30, 58, 138, 0.05), rgba(30, 64, 175, 0.02));
}

.category-name {
    font-size: var(--text-large);
    font-weight: 700;
    color: var(--primary);
    margin-bottom: var(--space-xs);
}

.category-description {
    font-size: var(--text-small);
    color: var(--text-secondary);
    margin: 0;
}

.category-body {
    padding: var(--space-lg);
}

.category-amount {
    font-size: var(--amount-large);
    font-weight: 700;
    color: var(--primary);
    margin-bottom: var(--space-md);
    text-align: center;
}

.category-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-md);
}

.category-variants {
    font-size: var(--text-small);
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

.category-actions {
    display: flex;
    gap: var(--space-sm);
    justify-content: center;
    padding-top: var(--space-md);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

/* Modal centering fix - ensures modals are centered regardless of scroll position */
.modal {
    display: flex !important;
    align-items: center;
    justify-content: center;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 1050;
}

.modal.show {
    display: flex !important;
}

.modal-dialog {
    margin: 0 !important;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.modal-content {
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-body {
    overflow-y: auto;
    max-height: calc(90vh - 120px); /* Adjust based on header/footer height */
}

/* Ensure backdrop covers entire viewport */
.modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 1040;
}

@media (max-width: 768px) {
    .tabs-navigation {
        flex-direction: column;
    }
    
    .category-grid {
        grid-template-columns: 1fr;
    }
    
    .category-actions {
        flex-direction: column;
    }
    
    .add-category-btn {
        position: static;
        margin-top: var(--space-md);
        align-self: center;
    }
    
    .modal-dialog {
        width: 95vw !important;
        max-width: 95vw !important;
        margin: 0 !important;
    }
    
    .modal-content {
        max-height: 95vh;
    }
    
    .modal-body {
        max-height: calc(95vh - 120px);
    }
}
</style>
@endsection

@section('content')
<div class="dashboard-main-grid">
    <!-- Header moderne -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1>
                <i class="fas fa-euro-sign me-2"></i>
                Gestion des Frais
            </h1>
            <p class="header-subtitle">Configuration et gestion des frais scolaires par catégorie et type</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi accent-blue">
                <i class="fas fa-globe"></i>Services Optionnels
            </a>
            <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi secondary">
                <i class="fas fa-cogs"></i>Configuration par Classe
            </a>
            <a href="{{ route('esbtp.frais.create') }}" class="btn-acasi primary">
                <i class="fas fa-plus"></i>Nouvelle Catégorie
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistiques KPI -->
    <div class="kpi-grid">
        <div class="kpi-card card-moderne">
            <div class="kpi-value color-primary">{{ $stats['total_categories'] }}</div>
            <div class="kpi-title">Total Catégories</div>
            <div class="kpi-trend">
                <i class="fas fa-layer-group me-1"></i>
                Toutes confondues
            </div>
        </div>
        <div class="kpi-card card-moderne">
            <div class="kpi-value color-success">{{ $stats['mandatory_categories'] }}</div>
            <div class="kpi-title">Frais Obligatoires</div>
            <div class="kpi-trend">
                <i class="fas fa-exclamation-circle me-1"></i>
                À payer par tous
            </div>
        </div>
        <div class="kpi-card card-moderne">
            <div class="kpi-value color-warning">{{ $stats['optional_categories'] }}</div>
            <div class="kpi-title">Services Optionnels</div>
            <div class="kpi-trend">
                <i class="fas fa-star me-1"></i>
                Cantine & Transport
            </div>
        </div>
        <div class="kpi-card card-moderne">
            <div class="kpi-value color-accent">{{ $stats['active_categories'] }}</div>
            <div class="kpi-title">Catégories Actives</div>
            <div class="kpi-trend">
                <i class="fas fa-check-circle me-1"></i>
                Configurées
            </div>
        </div>
    </div>

    <!-- Système d'onglets pour les catégories -->
    <div class="tabs-container">
        <!-- Navigation des onglets -->
        <div class="tabs-navigation">
            <button class="tab-button active" data-tab="academic">
                <i class="fas fa-graduation-cap"></i>
                Frais Académiques
                <span class="tab-badge">{{ $categoriesByType['academic']->count() }}</span>
            </button>
            <button class="tab-button" data-tab="service">
                <i class="fas fa-cogs"></i>
                Services Optionnels
                <span class="tab-badge">{{ $categoriesByType['service']->count() }}</span>
            </button>
            <button class="tab-button" data-tab="administrative">
                <i class="fas fa-file-alt"></i>
                Frais Administratifs
                <span class="tab-badge">{{ $categoriesByType['administrative']->count() }}</span>
            </button>
        </div>

        <!-- Contenu des onglets -->
        <div class="tab-content">
            <!-- Onglet Frais Académiques -->
            <div class="tab-pane active" id="academic">
                <div class="tab-header">
                    <h2 class="tab-title">
                        <i class="fas fa-graduation-cap color-success"></i>
                        Frais Académiques
                    </h2>
                    <p class="tab-subtitle">Frais d'inscription et de scolarité obligatoires selon la filière et le niveau</p>
                    <button class="btn-acasi primary add-category-btn" onclick="addCategoryForType('academic')">
                        <i class="fas fa-plus"></i>Ajouter Frais Académique
                    </button>
                </div>

                @if($categoriesByType['academic']->count() > 0)
                    <div class="category-grid">
                        @foreach($categoriesByType['academic'] as $category)
                            <div class="category-card">
                                <div class="category-header">
                                    <div class="category-name">{{ $category->name }}</div>
                                    <p class="category-description">{{ $category->description }}</p>
                                </div>
                                <div class="category-body">
                                    @if($category->is_mandatory)
                                        <div class="category-amount">
                                            <i class="fas fa-graduation-cap"></i>
                                            Configuré par classe
                                        </div>
                                        <div class="category-meta">
                                            <div class="category-variants">
                                                <i class="fas fa-cogs"></i>
                                                {{ $category->configuration_status['message'] ?? 'Aucune configuration' }}
                                            </div>
                                            <span class="badge success">Obligatoire</span>
                                        </div>
                                    @else
                                        @if($category->configuration_status['is_configured'] ?? false)
                                            <div class="category-amount">
                                                <i class="fas fa-check-circle color-success"></i>
                                                Service configuré
                                            </div>
                                        @else
                                            <div class="category-amount">
                                                <i class="fas fa-exclamation-triangle color-warning"></i>
                                                À configurer
                                            </div>
                                        @endif
                                        <div class="category-meta">
                                            <div class="category-variants">
                                                <i class="fas fa-globe"></i>
                                                {{ $category->configuration_status['message'] ?? 'Aucune option' }}
                                            </div>
                                            <span class="badge warning">Service Global</span>
                                        </div>
                                    @endif
                                    <div class="category-actions">
                                        <a href="{{ route('esbtp.frais.show', $category->id) }}" class="btn-acasi secondary" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.frais.edit', $category->id) }}" class="btn-acasi primary" title="Modifier catégorie">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($category->is_mandatory)
                                            <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi accent-blue" title="Configurer par classe">
                                                <i class="fas fa-graduation-cap"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi success" title="Services optionnels">
                                                <i class="fas fa-globe"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-graduation-cap"></i>
                        <p>Aucun frais académique configuré</p>
                        <button class="btn-acasi primary" onclick="addCategoryForType('academic')">
                            <i class="fas fa-plus"></i>Ajouter le premier frais académique
                        </button>
                    </div>
                @endif
            </div>

            <!-- Onglet Services Optionnels -->
            <div class="tab-pane" id="service">
                <div class="tab-header">
                    <h2 class="tab-title">
                        <i class="fas fa-cogs color-warning"></i>
                        Services Optionnels
                    </h2>
                    <p class="tab-subtitle">Services de cantine, transport et autres prestations avec variants selon les besoins</p>
                    <button class="btn-acasi primary add-category-btn" onclick="addCategoryForType('service')">
                        <i class="fas fa-plus"></i>Ajouter Service
                    </button>
                </div>

                @if($categoriesByType['service']->count() > 0)
                    <div class="category-grid">
                        @foreach($categoriesByType['service'] as $category)
                            <div class="category-card">
                                <div class="category-header">
                                    <div class="category-name">{{ $category->name }}</div>
                                    <p class="category-description">{{ $category->description }}</p>
                                </div>
                                <div class="category-body">
                                    @if($category->is_mandatory)
                                        <div class="category-amount">
                                            <i class="fas fa-graduation-cap"></i>
                                            Configuré par classe
                                        </div>
                                        <div class="category-meta">
                                            <div class="category-variants">
                                                <i class="fas fa-cogs"></i>
                                                {{ $category->configuration_status['message'] ?? 'Aucune configuration' }}
                                            </div>
                                            <span class="badge success">Obligatoire</span>
                                        </div>
                                    @else
                                        @if($category->configuration_status['is_configured'] ?? false)
                                            <div class="category-amount">
                                                <i class="fas fa-check-circle color-success"></i>
                                                Service configuré
                                            </div>
                                        @else
                                            <div class="category-amount">
                                                <i class="fas fa-exclamation-triangle color-warning"></i>
                                                À configurer
                                            </div>
                                        @endif
                                        <div class="category-meta">
                                            <div class="category-variants">
                                                <i class="fas fa-globe"></i>
                                                {{ $category->configuration_status['message'] ?? 'Aucune option' }}
                                            </div>
                                            <span class="badge warning">Service Global</span>
                                        </div>
                                    @endif
                                    <div class="category-actions">
                                        <a href="{{ route('esbtp.frais.show', $category->id) }}" class="btn-acasi secondary" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.frais.edit', $category->id) }}" class="btn-acasi primary" title="Modifier catégorie">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($category->is_mandatory)
                                            <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi accent-blue" title="Configurer par classe">
                                                <i class="fas fa-graduation-cap"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi success" title="Services optionnels">
                                                <i class="fas fa-globe"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-cogs"></i>
                        <p>Aucun service optionnel configuré</p>
                        <button class="btn-acasi primary" onclick="addCategoryForType('service')">
                            <i class="fas fa-plus"></i>Ajouter le premier service
                        </button>
                    </div>
                @endif
            </div>

            <!-- Onglet Frais Administratifs -->
            <div class="tab-pane" id="administrative">
                <div class="tab-header">
                    <h2 class="tab-title">
                        <i class="fas fa-file-alt color-accent"></i>
                        Frais Administratifs
                    </h2>
                    <p class="tab-subtitle">Frais de documentation, examens et autres démarches administratives</p>
                    <button class="btn-acasi primary add-category-btn" onclick="addCategoryForType('administrative')">
                        <i class="fas fa-plus"></i>Ajouter Frais Administratif
                    </button>
                </div>

                @if($categoriesByType['administrative']->count() > 0)
                    <div class="category-grid">
                        @foreach($categoriesByType['administrative'] as $category)
                            <div class="category-card">
                                <div class="category-header">
                                    <div class="category-name">{{ $category->name }}</div>
                                    <p class="category-description">{{ $category->description }}</p>
                                </div>
                                <div class="category-body">
                                    @if($category->is_mandatory)
                                        <div class="category-amount">
                                            <i class="fas fa-graduation-cap"></i>
                                            Configuré par classe
                                        </div>
                                        <div class="category-meta">
                                            <div class="category-variants">
                                                <i class="fas fa-cogs"></i>
                                                {{ $category->configuration_status['message'] ?? 'Aucune configuration' }}
                                            </div>
                                            <span class="badge success">Obligatoire</span>
                                        </div>
                                    @else
                                        @if($category->configuration_status['is_configured'] ?? false)
                                            <div class="category-amount">
                                                <i class="fas fa-check-circle color-success"></i>
                                                Service configuré
                                            </div>
                                        @else
                                            <div class="category-amount">
                                                <i class="fas fa-exclamation-triangle color-warning"></i>
                                                À configurer
                                            </div>
                                        @endif
                                        <div class="category-meta">
                                            <div class="category-variants">
                                                <i class="fas fa-globe"></i>
                                                {{ $category->configuration_status['message'] ?? 'Aucune option' }}
                                            </div>
                                            <span class="badge warning">Service Global</span>
                                        </div>
                                    @endif
                                    <div class="category-actions">
                                        <a href="{{ route('esbtp.frais.show', $category->id) }}" class="btn-acasi secondary" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.frais.edit', $category->id) }}" class="btn-acasi primary" title="Modifier catégorie">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($category->is_mandatory)
                                            <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi accent-blue" title="Configurer par classe">
                                                <i class="fas fa-graduation-cap"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi success" title="Services optionnels">
                                                <i class="fas fa-globe"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <p>Aucun frais administratif configuré</p>
                        <button class="btn-acasi primary" onclick="addCategoryForType('administrative')">
                            <i class="fas fa-plus"></i>Ajouter le premier frais administratif
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="quick-actions-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-bolt me-2"></i>
                Actions Rapides
            </h2>
        </div>
        <div class="quick-actions-grid">
            <a href="{{ route('esbtp.frais.configure') }}" class="quick-action-card">
                <i class="fas fa-cogs"></i>
                <span>Configuration par Classe</span>
            </a>
            
            <a href="{{ route('esbtp.frais.create') }}" class="quick-action-card">
                <i class="fas fa-plus"></i>
                <span>Nouvelle Catégorie</span>
            </a>
            
            <a href="{{ route('esbtp.paiements.index') }}" class="quick-action-card">
                <i class="fas fa-credit-card"></i>
                <span>Suivi des Paiements</span>
            </a>
            
            <button type="button" class="quick-action-card" onclick="showGlobalStats()">
                <i class="fas fa-chart-bar"></i>
                <span>Statistiques Globales</span>
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        });
    }, 5000);

    // Système d'onglets
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Désactiver tous les onglets
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Activer l'onglet cliqué
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
});

// Fonction pour ajouter une catégorie avec un type pré-sélectionné
function addCategoryForType(categoryType) {
    window.location.href = `{{ route('esbtp.frais.create') }}?category_type=${categoryType}`;
}

// Fonction pour afficher les statistiques globales
function showGlobalStats() {
    alert('Fonctionnalité de statistiques globales à implémenter');
}

</script>
@endpush