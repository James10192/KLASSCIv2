@extends('layouts.app')

@section('title', 'Détails - ' . $fraisCategory->name . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>
                    @if($fraisCategory->icon)
                        <i class="{{ $fraisCategory->icon }} me-2 color-{{ $fraisCategory->color ?? 'primary' }}"></i>
                    @endif
                    {{ $fraisCategory->name }}
                </h1>
                <p class="header-subtitle">Détails complets de la catégorie de frais</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.frais.edit', $fraisCategory->id) }}" class="btn-acasi primary">
                    <i class="fas fa-edit"></i>Modifier
                </a>
                @if($fraisCategory->is_mandatory)
                    <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi accent-blue">
                        <i class="fas fa-graduation-cap"></i>Configuration
                    </a>
                @else
                    <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi success">
                        <i class="fas fa-tasks"></i>Assignations
                    </a>
                @endif
                <a href="{{ route('esbtp.frais.index') }}" class="btn-acasi secondary">
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

        <!-- Informations principales -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-lg); margin-bottom: var(--space-lg);">
            <div class="card-moderne" style="padding: var(--space-lg);">
                <div class="section-title">
                    <i class="fas fa-info-circle me-2"></i>Informations Générales
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg); margin-top: var(--space-lg);">
                    <div>
                        <div style="margin-bottom: var(--space-md);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs);">Code</div>
                            <div style="font-family: 'Courier New', monospace; padding: var(--space-xs) var(--space-sm); background: rgba(30, 58, 138, 0.1); border-radius: var(--radius-small); color: var(--primary);">{{ $fraisCategory->code }}</div>
                        </div>
                        <div style="margin-bottom: var(--space-md);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs);">Type</div>
                            <div>
                                @if($fraisCategory->is_mandatory)
                                    <span class="badge danger">
                                        <i class="fas fa-graduation-cap"></i>Obligatoire
                                    </span>
                                @else
                                    <span class="badge warning">
                                        <i class="fas fa-tasks"></i>Optionnel
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div style="margin-bottom: var(--space-md);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs);">Statut</div>
                            <div>
                                @if($fraisCategory->is_active)
                                    <span class="badge success">
                                        <i class="fas fa-check-circle"></i>Active
                                    </span>
                                @else
                                    <span class="badge secondary">
                                        <i class="fas fa-pause-circle"></i>Inactive
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div>
                        <div style="margin-bottom: var(--space-md);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs);">Montant par défaut</div>
                            <div style="font-size: var(--amount-large); font-weight: 700; color: var(--primary);">{{ number_format($fraisCategory->default_amount, 0, ',', ' ') }} F CFA</div>
                        </div>
                        <div style="margin-bottom: var(--space-md);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs);">Délai de paiement</div>
                            <div style="color: var(--text-primary);">{{ $fraisCategory->payment_deadline_days }} jours</div>
                        </div>
                        <div style="margin-bottom: var(--space-md);">
                            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs);">Date de création</div>
                            <div style="color: var(--text-primary);">{{ $fraisCategory->created_at->format('d/m/Y à H:i') }}</div>
                        </div>
                    </div>
                </div>
                
                @if($fraisCategory->description)
                    <div style="margin-top: var(--space-lg); padding-top: var(--space-lg); border-top: 1px solid rgba(0,0,0,0.1);">
                        <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-sm);">Description</div>
                        <div style="border-left: 4px solid var(--primary); padding-left: var(--space-md); color: var(--text-primary); line-height: 1.6;">
                            {{ $fraisCategory->description }}
                        </div>
                    </div>
                @endif
            </div>

            <!-- Statistiques modernes -->
            <div class="card-moderne" style="padding: var(--space-lg); margin-bottom: var(--space-lg);">
                <div class="section-title">
                    <i class="fas fa-chart-bar me-2"></i>Statistiques de Configuration
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md); margin-top: var(--space-lg);">
                    <div style="text-align: center; padding: var(--space-md); background: rgba(30, 58, 138, 0.1); border-radius: var(--radius-small);">
                        <div style="font-size: var(--amount-large); font-weight: 700; color: var(--primary);">{{ $stats['total_configurations'] }}</div>
                        <div style="font-size: var(--text-small); color: var(--primary); font-weight: 600;">Configurations</div>
                    </div>
                    <div style="text-align: center; padding: var(--space-md); background: rgba(16, 185, 129, 0.1); border-radius: var(--radius-small);">
                        <div style="font-size: var(--amount-large); font-weight: 700; color: var(--success);">{{ $stats['active_configurations'] }}</div>
                        <div style="font-size: var(--text-small); color: var(--success); font-weight: 600;">Actives</div>
                    </div>
                </div>
                
                <div style="margin-top: var(--space-md); padding-top: var(--space-md); border-top: 1px solid rgba(0,0,0,0.1);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                        <div style="text-align: center; padding: var(--space-md); background: rgba(59, 130, 246, 0.1); border-radius: var(--radius-small);">
                            <div style="font-size: var(--amount-medium); font-weight: 700; color: var(--accent-blue);">{{ $stats['total_options'] }}</div>
                            <div style="font-size: var(--text-small); color: var(--accent-blue); font-weight: 600;">Options</div>
                        </div>
                        <div style="text-align: center; padding: var(--space-md); background: rgba(245, 158, 11, 0.1); border-radius: var(--radius-small);">
                            <div style="font-size: var(--amount-medium); font-weight: 700; color: var(--warning);">{{ $stats['coverage_percentage'] }}%</div>
                            <div style="font-size: var(--text-small); color: var(--warning); font-weight: 600;">Couverture</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides modernes -->
            <div class="card-moderne" style="padding: var(--space-lg);">
                <div class="section-title">
                    <i class="fas fa-bolt me-2"></i>Actions Rapides
                </div>
                
                <div style="display: flex; flex-direction: column; gap: var(--space-sm); margin-top: var(--space-lg);">
                    <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi primary" style="justify-content: center;">
                        <i class="fas fa-cogs"></i>Configuration Classes
                    </a>
                    @if($fraisCategory->is_mandatory)
                        <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi accent-blue" style="justify-content: center;">
                            <i class="fas fa-graduation-cap"></i>Frais Obligatoires
                        </a>
                    @else
                        <a href="{{ route('esbtp.frais.index') }}" class="btn-acasi success" style="justify-content: center;">
                            <i class="fas fa-tasks"></i>Frais Optionnels
                        </a>
                    @endif
                    <a href="{{ route('esbtp.frais.edit', $fraisCategory->id) }}" class="btn-acasi warning" style="justify-content: center;">
                        <i class="fas fa-edit"></i>Modifier Catégorie
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Options modernes -->
    @if($options->count() > 0)
        <div class="card-moderne" style="padding: var(--space-lg); margin-bottom: var(--space-lg);">
            <div class="section-title">
                <i class="fas fa-list me-2"></i>Options Disponibles ({{ $options->count() }})
            </div>
            
            <div style="margin-top: var(--space-lg);">
                <div class="resultats-grid">
                    @foreach($options as $option)
                        <div class="card-moderne" style="padding: var(--space-md); border-left: 4px solid var(--{{ $option->is_default ? 'success' : 'accent-blue' }});">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--space-sm);">
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; color: var(--text-primary); margin-bottom: var(--space-xs);">{{ $option->name }}</div>
                                    @if($option->description)
                                        <div style="font-size: var(--text-small); color: var(--text-secondary); margin-bottom: var(--space-sm);">{{ $option->description }}</div>
                                    @endif
                                </div>
                                <div style="text-align: right;">
                                    @if($option->is_default)
                                        <span class="badge success" style="margin-bottom: var(--space-xs);">Défaut</span>
                                    @endif
                                    <span class="badge {{ $option->is_active ? 'primary' : 'secondary' }}">
                                        {{ $option->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-size: var(--amount-medium); font-weight: 700; color: var(--primary);">
                                    +{{ number_format($option->additional_amount, 0, ',', ' ') }} F CFA
                                </div>
                                <div style="display: flex; gap: var(--space-xs);">
                                    <button class="btn-acasi primary" style="padding: var(--space-xs) var(--space-sm); font-size: var(--text-small);" 
                                            onclick="editOptionInline({{ $option->id }}, '{{ $option->name }}', '{{ $option->description }}', {{ $option->additional_amount }}, {{ $option->is_default ? 'true' : 'false' }})" 
                                            title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-acasi {{ $option->is_active ? 'warning' : 'success' }}" style="padding: var(--space-xs) var(--space-sm); font-size: var(--text-small);" 
                                            onclick="toggleOption({{ $option->id }}, {{ $option->is_active ? 'false' : 'true' }})"
                                            title="{{ $option->is_active ? 'Désactiver' : 'Activer' }}">
                                        <i class="fas fa-{{ $option->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                    <button class="btn-acasi danger" style="padding: var(--space-xs) var(--space-sm); font-size: var(--text-small);" 
                                            onclick="deleteOption({{ $option->id }})" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div style="text-align: center; margin-top: var(--space-lg);">
                    <button class="btn-acasi primary" onclick="addOption({{ $fraisCategory->id }}, '{{ $fraisCategory->name }}')">
                        <i class="fas fa-plus"></i>Ajouter une Option
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Configuration par classe moderne -->
    @if($configurations->count() > 0)
        <div class="card-moderne" style="padding: var(--space-lg); margin-bottom: var(--space-lg);">
            <div class="section-title">
                <i class="fas fa-cogs me-2"></i>Configuration par Classes ({{ $configurations->count() }})
            </div>
            
            <div style="margin-top: var(--space-lg);">
                <div class="resultats-grid">
                    @foreach($configurations as $config)
                        <div class="card-moderne" style="padding: var(--space-md); border-left: 4px solid var(--{{ $config->is_active ? 'success' : 'secondary' }});">
                            <!-- Header de la classe -->
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--space-md);">
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; color: var(--text-primary); margin-bottom: var(--space-xs);">
                                        {{ $config->filiere->name ?? 'N/A' }} - {{ $config->niveau->name ?? 'N/A' }}
                                    </div>
                                    <div style="font-size: var(--text-small); color: var(--text-secondary);">
                                        <i class="fas fa-graduation-cap me-1"></i>Configuration pour cette classe
                                    </div>
                                </div>
                                <span class="badge {{ $config->is_active ? 'success' : 'secondary' }}">
                                    {{ $config->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            <!-- Détails de configuration -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md); margin-bottom: var(--space-md);">
                                <div style="text-align: center; padding: var(--space-sm); background: rgba(30, 58, 138, 0.1); border-radius: var(--radius-small);">
                                    <div style="font-size: var(--amount-medium); font-weight: 700; color: var(--primary);">
                                        {{ number_format($config->amount, 0, ',', ' ') }} F CFA
                                    </div>
                                    <div style="font-size: var(--text-small); color: var(--primary); font-weight: 600;">Montant</div>
                                </div>
                                <div style="text-align: center; padding: var(--space-sm); background: rgba(245, 158, 11, 0.1); border-radius: var(--radius-small);">
                                    <div style="font-size: var(--amount-medium); font-weight: 700; color: var(--warning);">
                                        {{ $config->payment_deadline_days }} jours
                                    </div>
                                    <div style="font-size: var(--text-small); color: var(--warning); font-weight: 600;">Délai</div>
                                </div>
                            </div>
                            
                            <!-- Informations supplémentaires -->
                            <div style="font-size: var(--text-small); color: var(--text-muted); margin-bottom: var(--space-md);">
                                <div style="margin-bottom: var(--space-xs);">
                                    <i class="fas fa-calendar me-1"></i>Effective depuis : {{ $config->effective_date ? $config->effective_date->format('d/m/Y') : 'N/A' }}
                                </div>
                                @if($config->options->count() > 0)
                                    <div>
                                        <i class="fas fa-list me-1"></i>{{ $config->options->count() }} option(s) disponible(s)
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Actions d'édition -->
                            <div style="display: flex; gap: var(--space-xs); justify-content: end;">
                                <button class="btn-acasi primary" style="padding: var(--space-xs) var(--space-sm); font-size: var(--text-small);" 
                                        onclick="editConfiguration({{ $config->id }}, '{{ $config->filiere->name ?? 'N/A' }}', '{{ $config->niveau->name ?? 'N/A' }}', {{ $config->amount }}, {{ $config->payment_deadline_days }})"
                                        title="Modifier cette configuration">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-acasi {{ $config->is_active ? 'warning' : 'success' }}" style="padding: var(--space-xs) var(--space-sm); font-size: var(--text-small);" 
                                        onclick="toggleConfiguration({{ $config->id }}, {{ $config->is_active ? 'false' : 'true' }})"
                                        title="{{ $config->is_active ? 'Désactiver' : 'Activer' }} cette configuration">
                                    <i class="fas fa-{{ $config->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                                @if($config->options->count() > 0)
                                    <button class="btn-acasi accent-blue" style="padding: var(--space-xs) var(--space-sm); font-size: var(--text-small);" 
                                            onclick="manageOptions({{ $config->id }}, '{{ $config->filiere->name ?? 'N/A' }}', '{{ $config->niveau->name ?? 'N/A' }}')"
                                            title="Gérer les options">
                                        <i class="fas fa-list"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div style="text-align: center; margin-top: var(--space-lg);">
                    <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi primary">
                        <i class="fas fa-plus"></i>Configurer d'autres Classes
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="card-moderne" style="padding: var(--space-lg); background: rgba(59, 130, 246, 0.1); border-left: 4px solid var(--accent-blue);">
            <div style="text-align: center;">
                <i class="fas fa-info-circle" style="font-size: 48px; color: var(--accent-blue); margin-bottom: var(--space-md);"></i>
                <div style="font-weight: 700; color: var(--accent-blue); margin-bottom: var(--space-sm);">Configuration manquante</div>
                <div style="color: var(--text-secondary); margin-bottom: var(--space-lg);">
                    Cette catégorie n'est pas encore configurée pour des classes spécifiques.
                </div>
                <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi primary">
                    <i class="fas fa-cogs"></i>Configurer maintenant
                </a>
            </div>
        </div>
    @endif
</div>

@endsection

<!-- Modal d'édition de configuration -->
<div class="modal fade" id="editConfigModal" tabindex="-1" aria-labelledby="editConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editConfigModalLabel">
                    <i class="fas fa-edit me-2"></i>Modifier Configuration
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editConfigForm">
                    <input type="hidden" id="configId">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Classe :</strong> <span id="configClassInfo">-</span>
                    </div>
                    
                    <div class="mb-3">
                        <label for="configAmount" class="form-label">Montant (F CFA)</label>
                        <input type="number" class="form-control" id="configAmount" required min="0" step="1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="configDeadline" class="form-label">Délai de paiement (jours)</label>
                        <input type="number" class="form-control" id="configDeadline" required min="1" max="365">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveConfiguration()">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'édition d'option -->
<div class="modal fade" id="editOptionModal" tabindex="-1" aria-labelledby="editOptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="editOptionModalLabel">
                    <i class="fas fa-edit me-2"></i>Modifier Option
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editOptionForm">
                    <input type="hidden" id="optionId">
                    
                    <div class="mb-3">
                        <label for="optionName" class="form-label">Nom de l'option</label>
                        <input type="text" class="form-control" id="optionName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="optionDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="optionDescription" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="optionAmount" class="form-label">Montant supplémentaire (F CFA)</label>
                        <input type="number" class="form-control" id="optionAmount" required min="0" step="1">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="optionIsDefault">
                            <label class="form-check-label" for="optionIsDefault">
                                Option par défaut
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" onclick="saveOption()">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de gestion des options pour une configuration -->
<div class="modal fade" id="manageOptionsModal" tabindex="-1" aria-labelledby="manageOptionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="manageOptionsModalLabel">
                    <i class="fas fa-list me-2"></i>Gérer les Options
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Classe :</strong> <span id="optionsClassInfo">-</span>
                </div>
                
                <div id="optionsContainer">
                    <!-- Les options seront chargées ici -->
                </div>
                
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-success" onclick="addNewOption()">
                        <i class="fas fa-plus me-1"></i>Ajouter une Option
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Variables globales
let currentConfigId = null;
let currentOptionId = null;

// ========== GESTION DES CONFIGURATIONS ==========

// Éditer une configuration
function editConfiguration(configId, filiereName, niveauName, amount, deadline) {
    currentConfigId = configId;
    
    document.getElementById('configId').value = configId;
    document.getElementById('configClassInfo').textContent = `${filiereName} - ${niveauName}`;
    document.getElementById('configAmount').value = amount;
    document.getElementById('configDeadline').value = deadline;
    
    new bootstrap.Modal(document.getElementById('editConfigModal')).show();
}

// Sauvegarder une configuration
function saveConfiguration() {
    const formData = {
        amount: document.getElementById('configAmount').value,
        payment_deadline_days: document.getElementById('configDeadline').value,
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    };
    
    fetch(`{{ url('/esbtp/frais/configurations') }}/${currentConfigId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': formData._token,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editConfigModal')).hide();
            showSuccess('Configuration mise à jour avec succès!');
            location.reload();
        } else {
            showError('Erreur: ' + (data.message || 'Impossible de sauvegarder'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Erreur de connexion');
    });
}

// Activer/désactiver une configuration
function toggleConfiguration(configId, newStatus) {
    fetch(`{{ url('/esbtp/frais/configurations') }}/${configId}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ is_active: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(newStatus === 'true' ? 'Configuration activée!' : 'Configuration désactivée!');
            location.reload();
        } else {
            showError('Erreur lors du changement de statut');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Erreur de connexion');
    });
}

// ========== GESTION DES OPTIONS ==========

// Éditer une option inline
function editOptionInline(optionId, name, description, amount, isDefault) {
    currentOptionId = optionId;
    
    document.getElementById('optionId').value = optionId;
    document.getElementById('optionName').value = name;
    document.getElementById('optionDescription').value = description || '';
    document.getElementById('optionAmount').value = amount;
    document.getElementById('optionIsDefault').checked = isDefault === 'true';
    
    new bootstrap.Modal(document.getElementById('editOptionModal')).show();
}

// Sauvegarder une option
function saveOption() {
    const formData = {
        name: document.getElementById('optionName').value,
        description: document.getElementById('optionDescription').value,
        additional_amount: document.getElementById('optionAmount').value,
        is_default: document.getElementById('optionIsDefault').checked,
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    };
    
    fetch(`{{ url('/esbtp/frais/options') }}/${currentOptionId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': formData._token,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editOptionModal')).hide();
            showSuccess('Option mise à jour avec succès!');
            location.reload();
        } else {
            showError('Erreur: ' + (data.message || 'Impossible de sauvegarder'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Erreur de connexion');
    });
}

// Activer/désactiver une option
function toggleOption(optionId, newStatus) {
    fetch(`{{ url('/esbtp/frais/options') }}/${optionId}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ is_active: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(newStatus === 'true' ? 'Option activée!' : 'Option désactivée!');
            location.reload();
        } else {
            showError('Erreur lors du changement de statut');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Erreur de connexion');
    });
}

// Supprimer une option
function deleteOption(optionId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette option ? Cette action est irréversible.')) {
        fetch(`{{ url('/esbtp/frais/options') }}/${optionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('Option supprimée avec succès!');
                location.reload();
            } else {
                showError('Erreur lors de la suppression : ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Erreur de connexion lors de la suppression');
        });
    }
}

// Gérer les options d'une configuration
function manageOptions(configId, filiereName, niveauName) {
    document.getElementById('optionsClassInfo').textContent = `${filiereName} - ${niveauName}`;
    
    // Charger les options pour cette configuration
    fetch(`{{ url('/esbtp/frais/configurations') }}/${configId}/options`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderOptionsInModal(data.options);
                new bootstrap.Modal(document.getElementById('manageOptionsModal')).show();
            } else {
                showError('Erreur lors du chargement des options');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Erreur de connexion');
        });
}

// Rendre les options dans le modal
function renderOptionsInModal(options) {
    const container = document.getElementById('optionsContainer');
    
    if (options.length === 0) {
        container.innerHTML = '<div class="alert alert-info">Aucune option configurée pour cette classe.</div>';
        return;
    }
    
    let html = '<div class="list-group">';
    options.forEach(option => {
        html += `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${option.name}</h6>
                        <p class="mb-1 text-muted">${option.description || 'Aucune description'}</p>
                        <small class="text-primary">+${formatNumber(option.additional_amount)} F CFA</small>
                        ${option.is_default ? '<span class="badge bg-success ms-2">Défaut</span>' : ''}
                    </div>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="editOptionInline(${option.id}, '${option.name}', '${option.description || ''}', ${option.additional_amount}, ${option.is_default ? 'true' : 'false'})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteOption(${option.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// ========== FONCTIONS UTILITAIRES ==========

// Afficher un message de succès
function showSuccess(message) {
    // Création d'une notification simple
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 5000);
}

// Afficher un message d'erreur
function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 5000);
}

// Formater un nombre
function formatNumber(number) {
    return new Intl.NumberFormat('fr-FR').format(number);
}

// Animation au chargement
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card-moderne');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
});
</script>
@endpush