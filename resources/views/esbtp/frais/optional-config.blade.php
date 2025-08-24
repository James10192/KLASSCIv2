@extends('layouts.app')

@section('title', 'Configuration des Frais Optionnels - ESBTP-yAKRO')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
.option-card {
    border: 2px solid transparent;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.option-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, transparent, var(--accent-blue), transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.option-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-elevated);
    border-color: rgba(59, 130, 246, 0.3);
}

.option-card:hover::before {
    opacity: 1;
}

.option-item {
    background: var(--surface);
    border: 1px solid #e5e7eb;
    border-radius: var(--radius-small);
    padding: var(--space-md);
    margin-bottom: var(--space-sm);
    transition: all 0.2s ease;
}

.option-item:hover {
    background: rgba(59, 130, 246, 0.05);
    border-color: var(--accent-blue);
}

.option-price {
    font-weight: 700;
    color: var(--primary);
    font-size: var(--text-large);
}

.add-option-form {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(59, 130, 246, 0.02));
    border: 2px dashed var(--accent-blue);
    border-radius: var(--radius-medium);
    padding: var(--space-lg);
    margin-top: var(--space-md);
}

/* === CORRECTION SPÉCIFIQUE MODALS FRAIS OPTIONNELS === */

/* Forcer tous les modals de cette page au premier plan */
#assignModal.modal,
#editModal.modal,
#deleteModal.modal,
#addFeeModal.modal {
    z-index: 9999 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

#assignModal .modal-dialog,
#editModal .modal-dialog,
#deleteModal .modal-dialog,
#addFeeModal .modal-dialog {
    z-index: 10000 !important;
    position: relative !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

#assignModal .modal-content,
#editModal .modal-content,
#deleteModal .modal-content,
#addFeeModal .modal-content {
    z-index: 10001 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    background: white !important;
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important;
}

/* Désactiver animations sur modals show */
#assignModal.modal.fade .modal-dialog,
#editModal.modal.fade .modal-dialog,
#deleteModal.modal.fade .modal-dialog,
#addFeeModal.modal.fade .modal-dialog {
    transition: none !important;
    transform: none !important;
}

/* États d'affichage forcés */
#assignModal.modal.show,
#editModal.modal.show,
#deleteModal.modal.show,
#addFeeModal.modal.show {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Anti-curseur erratique quand modals ouverts */
body.modal-open * {
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* Empêcher mouvements de curseur */
body.modal-open .btn,
body.modal-open .card,
body.modal-open .form-control {
    animation: none !important;
    transition: none !important;
}

body.modal-open .btn:hover,
body.modal-open .card:hover {
    transform: none !important;
}

/* Backdrop spécifique */
.modal-backdrop {
    z-index: 1040 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* Style pour les badges d'assignation */
.assignment-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.assignment-badge.success {
    background: rgba(16, 185, 129, 0.1);
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.assignment-badge.secondary {
    background: rgba(107, 114, 128, 0.1);
    color: #374151;
    border: 1px solid rgba(107, 114, 128, 0.2);
}

/* Style pour les boutons de fermeture des modals */
.modal-header .btn-close {
    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='m.235 1.027 1.027-.235 6.738 6.738 6.738-6.738 1.027.235-.235 1.027L8.792 8.792l6.738 6.738-.235 1.027-1.027-.235L7.53 9.584.792 16.322l-1.027-.235.235-1.027L6.738 8.322.235 1.027z'/%3e%3c/svg%3e") center/1em auto no-repeat;
    border: 0;
    border-radius: 0.375rem;
    opacity: 0.8;
    padding: 0.375rem;
    width: 1.5em;
    height: 1.5em;
    color: #000;
    background-size: 0.75em;
}

.modal-header .btn-close:hover,
.modal-header .btn-close:focus {
    opacity: 1;
    background-color: rgba(0, 0, 0, 0.1);
    transform: scale(1.1);
    transition: all 0.2s ease;
}

.modal-header .btn-close:focus {
    outline: 2px solid var(--accent-blue);
    outline-offset: 2px;
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Configuration des Frais Optionnels</h1>
                <p class="header-subtitle">Gestion des options de transport, cantine et autres services</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Frais par Classe
                </a>
                <a href="{{ route('esbtp.frais.index') }}" class="btn-acasi primary">
                    <i class="fas fa-list"></i>Catégories
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

        <!-- Section statistiques -->
        <div class="soldes-section">
            <div class="soldes-grid">
                <div class="card-moderne solde-card">
                    <div class="solde-title">
                        <i class="fas fa-sliders-h me-2"></i>Frais Optionnels
                    </div>
                    <div class="solde-amount">{{ $stats['total_optional'] }}</div>
                    <div class="solde-subtitle">Catégories configurées</div>
                </div>
                
                <div class="card-moderne solde-card">
                    <div class="solde-title">
                        <i class="fas fa-bus me-2"></i>Transport
                    </div>
                    <div class="solde-amount">{{ $stats['transport_stops'] }}</div>
                    <div class="solde-subtitle">Arrêts configurés</div>
                </div>
                
                <div class="card-moderne solde-card">
                    <div class="solde-title">
                        <i class="fas fa-utensils me-2"></i>Cantine
                    </div>
                    <div class="solde-amount">{{ $stats['cantine_menus'] }}</div>
                    <div class="solde-subtitle">Menus configurés</div>
                </div>
            </div>
        </div>

        <!-- Guide d'utilisation -->
        <div class="card-moderne" style="margin-bottom: var(--space-lg); padding: var(--space-lg);">
            <div class="section-title">
                <i class="fas fa-info-circle me-2"></i>Guide de Configuration
            </div>
            <div style="color: var(--text-primary); font-size: var(--text-normal); line-height: 1.6; margin-top: var(--space-md);">
                <div class="mb-sm"><strong>Frais optionnels :</strong> ne dépendent pas de la classe (filière + niveau)</div>
                <div class="mb-sm">• <strong>Transport :</strong> configurez les différents arrêts avec leurs tarifs</div>
                <div class="mb-sm">• <strong>Cantine :</strong> configurez les menus disponibles et leurs prix</div>
                <div>• <strong>Autres services :</strong> activités extrascolaires, équipements, etc.</div>
            </div>
        </div>

        <!-- Liste des catégories optionnelles -->
        @if($optionalCategories->count() > 0)
            <div style="display: flex; flex-direction: column; gap: var(--space-lg);">
                @foreach($optionalCategories as $category)
                    <div class="card-moderne option-card animate-slide-up">
                        <div style="padding: var(--space-lg);">
                            <!-- Header avec icône -->
                            <div style="display: flex; align-items: center; margin-bottom: var(--space-lg);">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--accent-blue), var(--primary)); border-radius: var(--radius-circle); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; margin-right: var(--space-md);">
                                    <i class="fas {{ $category->category_type == 'transport' ? 'fa-bus' : ($category->category_type == 'cantine' ? 'fa-utensils' : 'fa-star') }}"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div class="resultat-title" style="color: var(--primary);">
                                        {{ $category->name }}
                                    </div>
                                    <div style="font-size: var(--text-small); color: var(--text-secondary);">
                                        {{ ucfirst($category->category_type) }}
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <span class="badge {{ $category->is_active ? 'success' : 'secondary' }}">
                                        {{ $category->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Description -->
                            @if($category->description)
                                <div style="margin-bottom: var(--space-md); color: var(--text-secondary);">
                                    {{ $category->description }}
                                </div>
                            @endif

                            <!-- Options existantes avec assignations -->
                            <div style="margin-bottom: var(--space-lg);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-md);">
                                    <div style="font-weight: 600; color: var(--text-primary);">
                                        Options configurées ({{ $category->options->count() }})
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleOptionsView({{ $category->id }})">
                                        <i class="fas fa-eye" id="view-icon-{{ $category->id }}"></i>
                                        <span id="view-text-{{ $category->id }}">Vue détaillée</span>
                                    </button>
                                </div>
                                
                                @if($category->options->count() > 0)
                                    <!-- Vue simple (par défaut) -->
                                    <div id="simple-view-{{ $category->id }}" class="options-view">
                                        @foreach($category->options as $option)
                                            <div class="option-item" data-option-id="{{ $option->id }}">
                                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                                    <div style="flex: 1;">
                                                        <div style="font-weight: 600; color: var(--text-primary);">
                                                            {{ $option->name }}
                                                        </div>
                                                        @if($option->description)
                                                            <div style="font-size: var(--text-small); color: var(--text-secondary);">
                                                                {{ $option->description }}
                                                            </div>
                                                        @endif
                                                        <!-- Badges d'assignation -->
                                                        <div style="margin-top: var(--space-xs);" id="assignment-badges-{{ $option->id }}">
                                                            @php
                                                                $assignments = $option->assignments ?? collect();
                                                                $assignmentCount = $assignments->count();
                                                            @endphp
                                                            
                                                            @if($assignmentCount > 0)
                                                                @foreach($assignments as $assignment)
                                                                    <span class="assignment-badge success" style="margin-right: var(--space-xs); margin-bottom: var(--space-xs);">
                                                                        <i class="fas fa-users"></i>{{ $assignment->display_label }}
                                                                    </span>
                                                                @endforeach
                                                            @else
                                                                <span class="assignment-badge secondary">
                                                                    <i class="fas fa-users"></i>Non assigné
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="option-price">
                                                        {{ number_format($option->additional_amount, 0, ',', ' ') }} F CFA
                                                    </div>
                                                    <div style="margin-left: var(--space-md); display: flex; gap: var(--space-xs);">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editOption({{ $option->id }}, '{{ $option->name }}', {{ $option->additional_amount }}, '{{ $option->description ?? '' }}')" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="manageAssignments({{ $option->id }}, '{{ $option->name }}')" title="Gérer assignations">
                                                            <i class="fas fa-users"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteOption({{ $option->id }})" title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Vue détaillée -->
                                    <div id="detailed-view-{{ $category->id }}" class="options-view" style="display: none;">
                                        @foreach($category->options as $option)
                                            <div class="option-card-detailed" data-option-id="{{ $option->id }}" style="background: white; border: 1px solid #e5e7eb; border-radius: var(--radius-medium); padding: var(--space-md); margin-bottom: var(--space-md); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-md);">
                                                    <div style="flex: 1;">
                                                        <h4 style="margin: 0; color: var(--primary); font-size: var(--text-large);">
                                                            {{ $option->name }}
                                                        </h4>
                                                        @if($option->description)
                                                            <p style="margin: var(--space-xs) 0 0 0; color: var(--text-secondary); font-size: var(--text-small);">
                                                                {{ $option->description }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                    <div style="text-align: right;">
                                                        <div style="font-size: var(--text-xl); font-weight: 700; color: var(--primary); margin-bottom: var(--space-xs);">
                                                            {{ number_format($option->additional_amount, 0, ',', ' ') }} F CFA
                                                        </div>
                                                        <div style="display: flex; gap: var(--space-xs);">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editOption({{ $option->id }}, '{{ $option->name }}', {{ $option->additional_amount }}, '{{ $option->description ?? '' }}')">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-info" onclick="manageAssignments({{ $option->id }}, '{{ $option->name }}')">
                                                                <i class="fas fa-users"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteOption({{ $option->id }})">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Section détaillée des assignations -->
                                                <div style="margin-top: var(--space-md); padding-top: var(--space-md); border-top: 1px solid #e5e7eb;">
                                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-sm);">
                                                        <h6 style="margin: 0; color: var(--text-primary); font-size: var(--text-normal);">
                                                            <i class="fas fa-users me-2"></i>Assignations Détaillées
                                                        </h6>
                                                        <button type="button" class="btn btn-sm btn-primary" onclick="manageAssignments({{ $option->id }}, '{{ $option->name }}')">
                                                            <i class="fas fa-cog"></i> Gérer
                                                        </button>
                                                    </div>
                                                    
                                                    @php
                                                        $assignments = $option->assignments ?? collect();
                                                    @endphp
                                                    
                                                    @if($assignments->count() > 0)
                                                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-xs);">
                                                            @foreach($assignments as $assignment)
                                                                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: var(--radius-small); padding: var(--space-sm); margin-bottom: var(--space-xs);">
                                                                    <div style="font-weight: 600; color: #065f46; font-size: var(--text-small);">
                                                                        {{ $assignment->display_label }}
                                                                    </div>
                                                                    @if($assignment->assignment_type !== 'all')
                                                                        <div style="font-size: 11px; color: #064e3b; margin-top: 2px;">
                                                                            Type: {{ ucfirst($assignment->assignment_type) }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        
                                                        <!-- Statistiques -->
                                                        <div style="margin-top: var(--space-sm); padding: var(--space-sm); background: rgba(59, 130, 246, 0.05); border-radius: var(--radius-small);">
                                                            <div style="font-size: var(--text-small); color: var(--text-secondary);">
                                                                <strong>{{ $assignments->count() }}</strong> assignation(s) active(s)
                                                                • Dernière mise à jour: {{ $assignments->max('updated_at')?->format('d/m/Y H:i') ?: 'N/A' }}
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div style="text-align: center; padding: var(--space-md); background: rgba(107, 114, 128, 0.05); border: 2px dashed #d1d5db; border-radius: var(--radius-small);">
                                                            <div style="color: var(--text-muted); font-style: italic;">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                Aucune assignation configurée
                                                            </div>
                                                            <div style="font-size: var(--text-small); color: var(--text-muted); margin-top: var(--space-xs);">
                                                                Cette option n'est assignée à aucune filière ou niveau
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Informations supplémentaires -->
                                                <div style="margin-top: var(--space-md); padding-top: var(--space-md); border-top: 1px solid #e5e7eb;">
                                                    <h6 style="margin: 0 0 var(--space-sm) 0; color: var(--text-primary); font-size: var(--text-normal);">
                                                        <i class="fas fa-info-circle me-2"></i>Informations Complémentaires
                                                    </h6>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div style="margin-bottom: var(--space-sm);">
                                                                <strong style="color: var(--text-primary);">ID Option:</strong>
                                                                <span style="color: var(--text-secondary);">#{{ $option->id }}</span>
                                                            </div>
                                                            <div style="margin-bottom: var(--space-sm);">
                                                                <strong style="color: var(--text-primary);">Statut:</strong>
                                                                <span class="badge {{ $option->is_active ? 'success' : 'secondary' }}">
                                                                    {{ $option->is_active ? 'Actif' : 'Inactif' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div style="margin-bottom: var(--space-sm);">
                                                                <strong style="color: var(--text-primary);">Créé le:</strong>
                                                                <span style="color: var(--text-secondary);">{{ $option->created_at?->format('d/m/Y H:i') ?: 'N/A' }}</span>
                                                            </div>
                                                            <div style="margin-bottom: var(--space-sm);">
                                                                <strong style="color: var(--text-primary);">Modifié le:</strong>
                                                                <span style="color: var(--text-secondary);">{{ $option->updated_at?->format('d/m/Y H:i') ?: 'N/A' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div style="text-align: center; padding: var(--space-lg); color: var(--text-muted);">
                                        <i class="fas fa-plus-circle fa-2x" style="margin-bottom: var(--space-sm);"></i>
                                        <p>Aucune option configurée</p>
                                        <small>Ajoutez des options comme les arrêts de transport ou les menus de cantine</small>
                                    </div>
                                @endif
                            </div>


                            <!-- Formulaire d'ajout d'option -->
                            <div class="add-option-form">
                                <div style="font-weight: 600; color: var(--accent-blue); margin-bottom: var(--space-md);">
                                    <i class="fas fa-plus me-2"></i>Ajouter une option
                                </div>
                                <form method="POST" action="{{ route('esbtp.frais.variants.store') }}">
                                    @csrf
                                    <input type="hidden" name="category_id" value="{{ $category->id }}">
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label style="font-weight: 600; color: var(--text-primary);">Nom</label>
                                                <input type="text" name="name" class="form-control" placeholder="Ex: Arrêt Centre-ville" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label style="font-weight: 600; color: var(--text-primary);">Prix (F CFA)</label>
                                                <input type="number" name="additional_amount" class="form-control" placeholder="15000" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label style="font-weight: 600; color: var(--text-primary);">Action</label>
                                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                                    <i class="fas fa-plus me-1"></i>Ajouter
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-top: var(--space-sm);">
                                        <label style="font-weight: 600; color: var(--text-primary);">Description (optionnel)</label>
                                        <input type="text" name="description" class="form-control" placeholder="Description de l'option">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card-moderne" style="padding: var(--space-xl);">
                <div class="empty-state">
                    <i class="fas fa-sliders-h"></i>
                    <p>Aucune catégorie optionnelle trouvée</p>
                    <div style="font-size: var(--text-small); color: var(--text-muted); margin-top: var(--space-sm);">
                        Créez d'abord des catégories de frais optionnels dans la gestion des catégories
                    </div>
                    <a href="{{ route('esbtp.frais.create') }}" class="btn-acasi primary" style="margin-top: var(--space-md);">
                        <i class="fas fa-plus"></i>Créer une Catégorie
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>

<!-- Modal de modification d'option -->
<div class="modal fade" id="editOptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content modal-moderne">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Modifier l'option
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editOptionForm">
                <div class="modal-body">
                    <input type="hidden" id="editOptionId" name="option_id">
                    
                    <div class="form-group-moderne">
                        <label for="editOptionName" class="form-label-moderne">Nom de l'option</label>
                        <input type="text" class="form-input-moderne" id="editOptionName" name="name" required>
                    </div>
                    
                    <div class="form-group-moderne">
                        <label for="editOptionDescription" class="form-label-moderne">Description</label>
                        <textarea class="form-textarea-moderne" id="editOptionDescription" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group-moderne">
                        <label for="editOptionAmount" class="form-label-moderne">Montant (F CFA)</label>
                        <input type="number" class="form-input-moderne" id="editOptionAmount" name="additional_amount" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>Annuler
                    </button>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save"></i>Sauvegarder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de gestion des assignations -->
<div class="modal fade" id="assignmentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-moderne">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-users me-2"></i>
                    Gérer les assignations - <span id="assignmentOptionName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assignmentOptionId" name="option_id">
                
                <!-- Type d'assignation -->
                <div style="margin-bottom: var(--space-lg);">
                    <label style="font-weight: 600; color: var(--text-primary); display: block; margin-bottom: var(--space-sm);">Type d'assignation</label>
                    <div style="display: flex; gap: var(--space-md); flex-wrap: wrap;">
                        <label style="display: flex; align-items: center; gap: var(--space-xs);">
                            <input type="radio" name="modal_assignment_type" value="all">
                            <span>Tous les étudiants</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: var(--space-xs);">
                            <input type="radio" name="modal_assignment_type" value="filiere">
                            <span>Par filière</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: var(--space-xs);">
                            <input type="radio" name="modal_assignment_type" value="niveau">
                            <span>Par niveau</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: var(--space-xs);">
                            <input type="radio" name="modal_assignment_type" value="classe">
                            <span>Par classe (filière + niveau)</span>
                        </label>
                    </div>
                </div>

                <!-- Détails d'assignation -->
                <div id="modal_assignment_details" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight: 600; color: var(--text-primary);">Filières</label>
                                <select multiple class="form-control" id="modal_filieres" style="height: 120px;">
                                    @php
                                        $filieres = \App\Models\ESBTPFiliere::where('is_active', true)->get();
                                    @endphp
                                    @foreach($filieres as $filiere)
                                        <option value="{{ $filiere->id }}">{{ $filiere->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Maintenez Ctrl pour sélectionner plusieurs filières</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight: 600; color: var(--text-primary);">Niveaux d'étude</label>
                                <select multiple class="form-control" id="modal_niveaux" style="height: 120px;">
                                    @php
                                        $niveaux = \App\Models\ESBTPNiveauEtude::where('is_active', true)->get();
                                    @endphp
                                    @foreach($niveaux as $niveau)
                                        <option value="{{ $niveau->id }}">{{ $niveau->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Maintenez Ctrl pour sélectionner plusieurs niveaux</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignations actuelles -->
                <div id="currentAssignments" style="margin-top: var(--space-lg);">
                    <h6 style="font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-sm);">
                        <i class="fas fa-list me-2"></i>Assignations actuelles
                    </h6>
                    <div id="assignmentsList">
                        <!-- Contenu chargé dynamiquement -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>Fermer
                </button>
                <button type="button" class="btn-acasi danger" onclick="clearAllAssignments()" id="clearAssignmentsBtn" style="display: none;">
                    <i class="fas fa-trash"></i>Supprimer toutes
                </button>
                <button type="button" class="btn-acasi primary" onclick="saveOptionAssignment()">
                    <i class="fas fa-save"></i>Sauvegarder les assignations
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Gérer l'affichage des détails d'assignation dans la modal
    document.querySelectorAll('input[name="modal_assignment_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const detailsDiv = document.getElementById('modal_assignment_details');
            
            if (this.value === 'all') {
                detailsDiv.style.display = 'none';
            } else {
                detailsDiv.style.display = 'block';
                
                // Gérer la visibilité des select selon le type
                const filieresSelect = document.getElementById('modal_filieres');
                const niveauxSelect = document.getElementById('modal_niveaux');
                
                if (this.value === 'filiere') {
                    filieresSelect.parentElement.parentElement.style.display = 'block';
                    niveauxSelect.parentElement.parentElement.style.display = 'none';
                } else if (this.value === 'niveau') {
                    filieresSelect.parentElement.parentElement.style.display = 'none';
                    niveauxSelect.parentElement.parentElement.style.display = 'block';
                } else if (this.value === 'classe') {
                    filieresSelect.parentElement.parentElement.style.display = 'block';
                    niveauxSelect.parentElement.parentElement.style.display = 'block';
                }
            }
        });
    });

    // Gérer la soumission du formulaire d'édition d'option
    const editOptionForm = document.getElementById('editOptionForm');
    if (editOptionForm) {
        editOptionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const optionId = document.getElementById('editOptionId').value;
            
            fetch(`/esbtp/frais/variants/${optionId}`, {
                method: 'PUT',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editOptionModal')).hide();
                    location.reload();
                } else {
                    alert('Erreur lors de la modification : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            });
        });
    }
});

// Fonction pour basculer entre les vues simple et détaillée
function toggleOptionsView(categoryId) {
    const simpleView = document.getElementById(`simple-view-${categoryId}`);
    const detailedView = document.getElementById(`detailed-view-${categoryId}`);
    const viewIcon = document.getElementById(`view-icon-${categoryId}`);
    const viewText = document.getElementById(`view-text-${categoryId}`);
    
    if (simpleView.style.display !== 'none') {
        // Passer à la vue détaillée
        simpleView.style.display = 'none';
        detailedView.style.display = 'block';
        viewIcon.className = 'fas fa-list';
        viewText.textContent = 'Vue simple';
    } else {
        // Passer à la vue simple
        simpleView.style.display = 'block';
        detailedView.style.display = 'none';
        viewIcon.className = 'fas fa-eye';
        viewText.textContent = 'Vue détaillée';
    }
}

// Fonction pour éditer une option
function editOption(optionId, name, amount, description) {
    document.getElementById('editOptionId').value = optionId;
    document.getElementById('editOptionName').value = name;
    document.getElementById('editOptionAmount').value = amount;
    document.getElementById('editOptionDescription').value = description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editOptionModal'));
    modal.show();
}

// Fonction pour gérer les assignations d'une option
function manageAssignments(optionId, optionName) {
    document.getElementById('assignmentOptionId').value = optionId;
    document.getElementById('assignmentOptionName').textContent = optionName;
    
    // Charger les assignations existantes
    loadCurrentAssignments(optionId);
    
    const modal = new bootstrap.Modal(document.getElementById('assignmentsModal'));
    modal.show();
}

// Fonction pour charger les assignations actuelles
function loadCurrentAssignments(optionId) {
    const assignmentsList = document.getElementById('assignmentsList');
    const clearBtn = document.getElementById('clearAssignmentsBtn');
    
    assignmentsList.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
    
    fetch(`{{ url('esbtp/frais/options') }}/${optionId}/assignments`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.assignments.length > 0) {
                let html = '<div style="display: flex; flex-wrap: wrap; gap: var(--space-xs);">';
                
                data.assignments.forEach(assignment => {
                    html += `
                        <span class="assignment-badge success" style="position: relative; padding-right: 25px;">
                            ${assignment.display_label}
                            <button type="button" onclick="removeAssignment(${assignment.id})" style="position: absolute; right: 5px; top: 2px; background: none; border: none; color: #065f46; font-size: 10px;">
                                <i class="fas fa-times"></i>
                            </button>
                        </span>
                    `;
                });
                
                html += '</div>';
                assignmentsList.innerHTML = html;
                clearBtn.style.display = 'inline-block';
            } else {
                assignmentsList.innerHTML = '<div style="color: var(--text-muted); font-style: italic; text-align: center; padding: var(--space-sm);">Aucune assignation configurée</div>';
                clearBtn.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            assignmentsList.innerHTML = '<div class="text-danger">Erreur lors du chargement des assignations</div>';
            clearBtn.style.display = 'none';
        });
}

// Fonction pour supprimer une assignation spécifique
function removeAssignment(assignmentId) {
    if (confirm('Supprimer cette assignation ?')) {
        fetch(`{{ url('esbtp/frais/assignments') }}/${assignmentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const optionId = document.getElementById('assignmentOptionId').value;
                loadCurrentAssignments(optionId);
                refreshOptionAssignments(optionId);
                
                // Afficher un message de succès
                showSuccessMessage(data.message || 'Assignation supprimée avec succès !');
            } else {
                alert('Erreur lors de la suppression: ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion');
        });
    }
}

// Fonction pour supprimer toutes les assignations
function clearAllAssignments() {
    if (confirm('Supprimer toutes les assignations de cette option ?')) {
        const optionId = document.getElementById('assignmentOptionId').value;
        
        fetch(`{{ url('esbtp/frais/options') }}/${optionId}/assignments`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCurrentAssignments(optionId);
                refreshOptionAssignments(optionId);
                // Réinitialiser le formulaire
                document.querySelectorAll('input[name="modal_assignment_type"]').forEach(radio => radio.checked = false);
                document.getElementById('modal_assignment_details').style.display = 'none';
                
                showSuccessMessage(data.message || 'Toutes les assignations ont été supprimées !');
            } else {
                alert('Erreur lors de la suppression: ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion');
        });
    }
}

// Fonction pour sauvegarder les assignations d'une option
function saveOptionAssignment() {
    const optionId = document.getElementById('assignmentOptionId').value;
    const assignmentType = document.querySelector('input[name="modal_assignment_type"]:checked');
    
    if (!assignmentType) {
        alert('Veuillez sélectionner un type d\'assignation');
        return;
    }
    
    let data = {
        option_id: optionId,
        assignment_type: assignmentType.value,
        _token: document.querySelector('meta[name="csrf-token"]').content
    };
    
    if (assignmentType.value !== 'all') {
        const filieresSelect = document.getElementById('modal_filieres');
        const niveauxSelect = document.getElementById('modal_niveaux');
        
        if (assignmentType.value === 'filiere' || assignmentType.value === 'classe') {
            data.filieres = Array.from(filieresSelect.selectedOptions).map(option => option.value);
            if (data.filieres.length === 0) {
                alert('Veuillez sélectionner au moins une filière');
                return;
            }
        }
        
        if (assignmentType.value === 'niveau' || assignmentType.value === 'classe') {
            data.niveaux = Array.from(niveauxSelect.selectedOptions).map(option => option.value);
            if (data.niveaux.length === 0) {
                alert('Veuillez sélectionner au moins un niveau');
                return;
            }
        }
    }
    
    fetch('{{ url("esbtp/frais/options/assignments") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': data._token
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('assignmentsModal'));
            modal.hide();
            
            // Rafraîchir seulement l'option concernée
            refreshOptionAssignments(optionId);
            
            showSuccessMessage(data.message || 'Assignations sauvegardées avec succès !');
        } else {
            alert('Erreur : ' + (data.message || 'Impossible de sauvegarder'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
    });
}

// Fonction helper pour afficher les messages de succès
function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    // Auto-supprimer l'alerte après 3 secondes
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

// Fonction pour rafraîchir les assignations d'une option spécifique
function refreshOptionAssignments(optionId) {
    fetch(`{{ url('esbtp/frais/options') }}/${optionId}/assignments`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour les badges d'assignation dans la vue simple
                const assignmentBadgesContainer = document.getElementById(`assignment-badges-${optionId}`);
                if (assignmentBadgesContainer) {
                    let badgesHtml = '';
                    
                    if (data.assignments.length > 0) {
                        data.assignments.forEach(assignment => {
                            badgesHtml += `
                                <span class="assignment-badge success" style="margin-right: var(--space-xs); margin-bottom: var(--space-xs);">
                                    <i class="fas fa-users"></i>${assignment.display_label}
                                </span>
                            `;
                        });
                    } else {
                        badgesHtml = `
                            <span class="assignment-badge secondary">
                                <i class="fas fa-users"></i>Non assigné
                            </span>
                        `;
                    }
                    
                    assignmentBadgesContainer.innerHTML = badgesHtml;
                }
                
                // Si on est en vue détaillée, rafraîchir aussi cette partie
                const detailedView = document.getElementById(`detailed-view-${getCategoryIdForOption(optionId)}`);
                if (detailedView && detailedView.style.display !== 'none') {
                    // Pour la vue détaillée, on pourrait recharger juste cette carte
                    // Pour l'instant, on laisse comme ça car c'est plus complexe à implémenter
                    console.log('Vue détaillée nécessite un rafraîchissement complet');
                }
            }
        })
        .catch(error => {
            console.error('Erreur lors du rafraîchissement des assignations:', error);
        });
}

// Fonction helper pour trouver l'ID de catégorie d'une option (approximatif)
function getCategoryIdForOption(optionId) {
    // Cette fonction pourrait être améliorée en stockant l'ID de catégorie dans les attributs HTML
    // Pour l'instant, on retourne une valeur par défaut
    return 1; // Placeholder
}


function deleteOption(optionId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette option ?')) {
        // Créer un formulaire pour la suppression
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/esbtp/frais/variants/${optionId}`;
        
        // Ajouter le token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
        
        // Ajouter la méthode DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Ajouter au DOM et soumettre
        document.body.appendChild(form);
        form.submit();
    }
}

// === FIX MODAL Z-INDEX DYNAMIQUE ===
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation du fix des modals pour les frais optionnels');
    
    // Liste des modals à corriger
    const modals = ['assignModal', 'editModal', 'deleteModal', 'addFeeModal'];
    
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            // Événement pour forcer z-index correct à l'ouverture
            modal.addEventListener('show.bs.modal', function(e) {
                console.log(`🔧 Préparation modal ${modalId}`);
                
                // Désactiver toutes les animations pendant l'ouverture
                document.body.style.setProperty('overflow', 'hidden', 'important');
                
                // Ajouter style anti-cursor
                const antiCursorStyle = document.createElement('style');
                antiCursorStyle.id = `anti-cursor-${modalId}`;
                antiCursorStyle.textContent = `
                    * { animation: none !important; transition: none !important; }
                    *:hover { transform: none !important; }
                `;
                document.head.appendChild(antiCursorStyle);
            });
            
            modal.addEventListener('shown.bs.modal', function(e) {
                console.log(`✅ Modal ${modalId} ouvert - Application des corrections`);
                
                // Forcer z-index très élevé
                modal.style.setProperty('z-index', '9999', 'important');
                modal.style.setProperty('backdrop-filter', 'none', 'important');
                modal.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                
                const modalDialog = modal.querySelector('.modal-dialog');
                const modalContent = modal.querySelector('.modal-content');
                
                if (modalDialog) {
                    modalDialog.style.setProperty('z-index', '10000', 'important');
                    modalDialog.style.setProperty('backdrop-filter', 'none', 'important');
                    modalDialog.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                }
                
                if (modalContent) {
                    modalContent.style.setProperty('z-index', '10001', 'important');
                    modalContent.style.setProperty('backdrop-filter', 'none', 'important');
                    modalContent.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                    modalContent.style.setProperty('background', 'white', 'important');
                }
                
                // Forcer backdrop en arrière
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.style.setProperty('z-index', '1040', 'important');
                    backdrop.style.setProperty('backdrop-filter', 'none', 'important');
                    backdrop.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                }
            });
            
            // Nettoyer à la fermeture
            modal.addEventListener('hidden.bs.modal', function(e) {
                console.log(`🧹 Nettoyage modal ${modalId}`);
                
                // Supprimer style anti-cursor
                const antiCursorStyle = document.getElementById(`anti-cursor-${modalId}`);
                if (antiCursorStyle) {
                    antiCursorStyle.remove();
                }
                
                // Rétablir overflow
                document.body.style.overflow = '';
            });
        }
    });
    
    console.log('✅ Fix modals configuré pour:', modals);
});
</script>
@endpush