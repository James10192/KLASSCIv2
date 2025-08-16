@extends('layouts.app')

@section('title', 'Modifier Catégorie de Frais - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* Fix pour les modales parfaitement centrées sur l'écran */
.modal {
    z-index: 1055 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    overflow: hidden !important;
    /* NE PAS mettre display: flex ici - ça casse les interactions de la page */
}

.modal.show {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.modal-dialog {
    position: relative !important;
    margin: 30px auto !important;
    width: auto !important;
    max-width: 500px !important;
    max-height: calc(100vh - 60px) !important;
    /* pointer-events normal pour permettre les clics */
}

.modal.show .modal-dialog {
    margin: 0 !important;
    transform: none !important;
    top: auto !important;
}

.modal-dialog.modal-lg {
    max-width: 800px !important;
}

.modal-content {
    position: relative !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3) !important;
    margin: 20px !important;
}

.modal-backdrop {
    z-index: 1050 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Modifier la Catégorie de Frais</h1>
                <p class="header-subtitle">{{ $fraisCategory->name }} - Édition des informations</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.frais.show', $fraisCategory) }}" class="btn-acasi secondary" title="Voir détails">
                    <i class="fas fa-eye"></i>Voir
                </a>
                @if($fraisCategory->is_mandatory)
                    <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi accent-blue" title="Configurer par classe">
                        <i class="fas fa-graduation-cap"></i>Configuration
                    </a>
                @else
                    <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi success" title="Gérer assignations">
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

        @if(session('error'))
            <div class="card-moderne" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger); margin-bottom: var(--space-lg);">
                <div style="padding: var(--space-md);">
                    <div class="color-danger font-semibold">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    </div>
                </div>
            </div>
        @endif

        <!-- Formulaire principal -->
        <div class="card-moderne" style="padding: var(--space-lg);">
            <div class="section-title">
                <i class="fas fa-edit me-2"></i>Informations de la Catégorie
            </div>
                    <form method="POST" action="{{ route('esbtp.frais.update', $fraisCategory->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-grid-2">
                            <div class="form-group-moderne">
                                <label for="name" class="form-label-moderne">
                                    <i class="fas fa-tag me-1"></i>Nom de la catégorie <span class="color-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-input-moderne @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $fraisCategory->name) }}" 
                                       placeholder="Nom de la catégorie de frais"
                                       required>
                                @error('name')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group-moderne">
                                <label for="code" class="form-label-moderne">
                                    <i class="fas fa-code me-1"></i>Code <span class="color-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-input-moderne @error('code') is-invalid @enderror" 
                                       id="code" 
                                       name="code" 
                                       value="{{ old('code', $fraisCategory->code) }}" 
                                       placeholder="CODE_FRAIS"
                                       required>
                                @error('code')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                                <div style="color: var(--text-muted); font-size: var(--text-small); margin-top: var(--space-xs);">Le code sera automatiquement converti en majuscules</div>
                            </div>
                        </div>

                        <div class="form-group-moderne">
                            <label for="description" class="form-label-moderne">
                                <i class="fas fa-align-left me-1"></i>Description
                            </label>
                            <textarea class="form-textarea-moderne @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Description détaillée de la catégorie de frais">{{ old('description', $fraisCategory->description) }}</textarea>
                            @error('description')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group-moderne">
                                <label for="default_amount" class="form-label-moderne">
                                    <i class="fas fa-coins me-1"></i>Montant par défaut (FCFA) <span class="color-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-input-moderne @error('default_amount') is-invalid @enderror" 
                                       id="default_amount" 
                                       name="default_amount" 
                                       value="{{ old('default_amount', $fraisCategory->default_amount) }}" 
                                       min="0" 
                                       step="1000"
                                       placeholder="50000"
                                       required>
                                @error('default_amount')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group-moderne">
                                <label for="payment_deadline_days" class="form-label-moderne">
                                    <i class="fas fa-calendar-alt me-1"></i>Délai de paiement (jours) <span class="color-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-input-moderne @error('payment_deadline_days') is-invalid @enderror" 
                                       id="payment_deadline_days" 
                                       name="payment_deadline_days" 
                                       value="{{ old('payment_deadline_days', $fraisCategory->payment_deadline_days) }}" 
                                       min="1" 
                                       max="365"
                                       placeholder="30"
                                       required>
                                @error('payment_deadline_days')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group-moderne">
                                <label for="icon" class="form-label-moderne">
                                    <i class="fas fa-icons me-1"></i>Icône (classe CSS)
                                </label>
                                <input type="text" 
                                       class="form-input-moderne @error('icon') is-invalid @enderror" 
                                       id="icon" 
                                       name="icon" 
                                       value="{{ old('icon', $fraisCategory->icon) }}" 
                                       placeholder="fas fa-money-bill">
                                @error('icon')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                                <div style="color: var(--text-muted); font-size: var(--text-small); margin-top: var(--space-xs);">Utilisez les classes FontAwesome (ex: fas fa-money-bill)</div>
                            </div>
                            <div class="form-group-moderne">
                                <label for="color" class="form-label-moderne">
                                    <i class="fas fa-palette me-1"></i>Couleur thématique
                                </label>
                                <select class="form-select-moderne @error('color') is-invalid @enderror" 
                                        id="color" 
                                        name="color">
                                    <option value="">Couleur par défaut</option>
                                    <option value="primary" {{ old('color', $fraisCategory->color) == 'primary' ? 'selected' : '' }}>🔵 Bleu (Primary)</option>
                                    <option value="success" {{ old('color', $fraisCategory->color) == 'success' ? 'selected' : '' }}>🟢 Vert (Success)</option>
                                    <option value="info" {{ old('color', $fraisCategory->color) == 'info' ? 'selected' : '' }}>🔵 Cyan (Info)</option>
                                    <option value="warning" {{ old('color', $fraisCategory->color) == 'warning' ? 'selected' : '' }}>🟡 Orange (Warning)</option>
                                    <option value="danger" {{ old('color', $fraisCategory->color) == 'danger' ? 'selected' : '' }}>🔴 Rouge (Danger)</option>
                                    <option value="secondary" {{ old('color', $fraisCategory->color) == 'secondary' ? 'selected' : '' }}>⚫ Gris (Secondary)</option>
                                </select>
                                @error('color')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group-moderne">
                            <div class="form-check-moderne">
                                <input class="form-check-input-moderne @error('is_mandatory') is-invalid @enderror" 
                                       type="checkbox" 
                                       id="is_mandatory" 
                                       name="is_mandatory" 
                                       value="1" 
                                       {{ old('is_mandatory', $fraisCategory->is_mandatory) ? 'checked' : '' }}>
                                <label class="form-check-label-moderne" for="is_mandatory">
                                    <strong>Frais obligatoire</strong>
                                </label>
                                @error('is_mandatory')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>
                            <div style="color: var(--text-muted); font-size: var(--text-small); margin-top: var(--space-xs);">Les frais obligatoires doivent être configurés pour toutes les classes</div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: 1px solid rgba(0,0,0,0.1);">
                            <div style="display: flex; gap: var(--space-md);">
                                <button type="submit" class="btn-acasi primary">
                                    <i class="fas fa-save"></i>Mettre à jour
                                </button>
                                <a href="{{ route('esbtp.frais.index') }}" class="btn-acasi secondary">
                                    <i class="fas fa-times"></i>Annuler
                                </a>
                            </div>
                            <div>
                                @if(!$fraisCategory->is_mandatory)
                                    <button type="button" class="btn-acasi" style="background-color: var(--danger); color: white;" onclick="deleteCategory()">
                                        <i class="fas fa-trash"></i>Supprimer
                                    </button>
                                @endif
                            </div>
                        </div>
                    </form>

                    @if(!$fraisCategory->is_mandatory)
                        <form id="deleteForm" action="{{ route('esbtp.frais.destroy', $fraisCategory->id) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                </div>

        <!-- Information sur les configurations -->
        <div class="card-moderne" style="padding: var(--space-lg); margin-top: var(--space-lg);">
            <div class="section-title">
                <i class="fas fa-info-circle me-2"></i>Informations de Configuration
            </div>
            
            <div class="kpi-grid">
                @if($fraisCategory->is_mandatory)
                    <div class="kpi-card card-moderne">
                        <div class="kpi-title">Type de Frais</div>
                        <div class="kpi-value color-danger">
                            <i class="fas fa-graduation-cap"></i> Obligatoire
                        </div>
                        <div style="font-size: var(--text-small); color: var(--text-secondary);">
                            Doit être configuré par classe
                        </div>
                    </div>
                    <div class="kpi-card card-moderne">
                        <div class="kpi-title">Configurations</div>
                        <div class="kpi-value color-primary">
                            {{ \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $fraisCategory->id)->count() }}
                        </div>
                        <div style="font-size: var(--text-small); color: var(--text-secondary);">
                            Classes configurées
                        </div>
                    </div>
                @else
                    <div class="kpi-card card-moderne">
                        <div class="kpi-title">Type de Frais</div>
                        <div class="kpi-value color-warning">
                            <i class="fas fa-tasks"></i> Optionnel
                        </div>
                        <div style="font-size: var(--text-small); color: var(--text-secondary);">
                            Assignable aux classes
                        </div>
                    </div>
                    <div class="kpi-card card-moderne">
                        <div class="kpi-title">Assignations</div>
                        <div class="kpi-value color-success">
                            {{ \App\Models\ESBTPOptionAssignment::count() }}
                        </div>
                        <div style="font-size: var(--text-small); color: var(--text-secondary);">
                            Total assignations
                        </div>
                    </div>
                @endif
            </div>
            
            <div style="text-align: center; margin-top: var(--space-lg);">
                @if($fraisCategory->is_mandatory)
                    <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi accent-blue">
                        <i class="fas fa-graduation-cap"></i>Configurer par Classe
                    </a>
                @else
                    <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi success">
                        <i class="fas fa-tasks"></i>Gérer les Assignations
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deleteCategory() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie de frais ?\n\nCette action supprimera également tous les frais configurés pour cette catégorie.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>
@endpush