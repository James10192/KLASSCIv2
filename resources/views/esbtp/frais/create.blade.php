@extends('layouts.app')

@section('title', 'Nouvelle Catégorie de Frais - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .form-floating-modern {
        position: relative;
        margin-bottom: var(--space-lg);
    }
    
    .form-floating-modern input,
    .form-floating-modern select,
    .form-floating-modern textarea {
        width: 100%;
        padding: 15px var(--space-md);
        border: 2px solid #e5e7eb;
        border-radius: var(--radius-medium);
        font-size: var(--text-normal);
        background: var(--surface);
        transition: all 0.3s ease;
        font-family: var(--font-family);
    }
    
    .form-floating-modern input:focus,
    .form-floating-modern select:focus,
    .form-floating-modern textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        transform: translateY(-1px);
    }
    
    .form-floating-modern label {
        position: absolute;
        top: 15px;
        left: var(--space-md);
        font-size: var(--text-normal);
        color: var(--text-secondary);
        background: var(--surface);
        padding: 0 var(--space-xs);
        transition: all 0.3s ease;
        pointer-events: none;
        font-weight: 500;
    }
    
    .form-floating-modern input:focus + label,
    .form-floating-modern input:not(:placeholder-shown) + label,
    .form-floating-modern select:focus + label,
    .form-floating-modern select:not(:placeholder-shown) + label,
    .form-floating-modern textarea:focus + label,
    .form-floating-modern textarea:not(:placeholder-shown) + label {
        top: -8px;
        font-size: var(--text-small);
        color: var(--primary);
        font-weight: 600;
    }
    
    .form-floating-modern .form-text {
        font-size: var(--text-small);
        color: var(--text-muted);
        margin-top: var(--space-xs);
    }
    
    .form-floating-modern .invalid-feedback {
        font-size: var(--text-small);
        color: var(--danger);
        margin-top: var(--space-xs);
        display: block;
    }
    
    .form-floating-modern input.is-invalid,
    .form-floating-modern select.is-invalid,
    .form-floating-modern textarea.is-invalid {
        border-color: var(--danger);
    }
    
    .form-floating-modern input.is-invalid:focus,
    .form-floating-modern select.is-invalid:focus,
    .form-floating-modern textarea.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
    
    .checkbox-modern {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        padding: var(--space-md);
        border: 2px solid #e5e7eb;
        border-radius: var(--radius-medium);
        background: var(--surface);
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: var(--space-lg);
    }
    
    .checkbox-modern:hover {
        border-color: var(--primary);
        background: rgba(30, 58, 138, 0.02);
    }
    
    .checkbox-modern input[type="checkbox"] {
        width: 20px;
        height: 20px;
        accent-color: var(--primary);
        cursor: pointer;
    }
    
    .checkbox-modern label {
        flex: 1;
        font-weight: 600;
        color: var(--text-primary);
        cursor: pointer;
        margin: 0;
    }
    
    .checkbox-modern .checkbox-description {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-top: var(--space-xs);
    }
    
    .icon-preview {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: var(--radius-small);
        background: var(--primary);
        color: white;
        margin-left: var(--space-sm);
        transition: all 0.3s ease;
    }
    
    .color-preview {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: var(--radius-circle);
        margin-left: var(--space-sm);
        border: 2px solid #e5e7eb;
    }
    
    .examples-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-md);
    }
    
    .example-item {
        padding: var(--space-md);
        background: rgba(30, 58, 138, 0.05);
        border: 1px solid rgba(30, 58, 138, 0.1);
        border-radius: var(--radius-small);
    }
    
    .help-section {
        background: linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(16, 185, 129, 0.1));
        border: none;
        border-radius: var(--radius-medium);
    }
    
    .help-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-md);
        margin: 0;
        padding: 0;
        list-style: none;
    }
    
    .help-item {
        display: flex;
        align-items: flex-start;
        gap: var(--space-sm);
        padding: var(--space-sm);
        background: var(--surface);
        border-radius: var(--radius-small);
        box-shadow: var(--shadow-card);
    }
    
    .help-icon {
        color: var(--primary);
        margin-top: 2px;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Nouvelle Catégorie de Frais</h1>
                <p class="header-subtitle">Création d'une nouvelle catégorie pour organiser les frais scolaires</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.frais.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <!-- Messages d'alerte -->
        @if(session('success'))
        <div class="card-moderne mb-lg" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)); border: 1px solid rgba(16, 185, 129, 0.2);">
            <div class="p-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle color-success me-3" style="font-size: 20px;"></i>
                    <span class="color-success font-medium">{{ session('success') }}</span>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="card-moderne mb-lg" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05)); border: 1px solid rgba(239, 68, 68, 0.2);">
            <div class="p-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle color-danger me-3" style="font-size: 20px;"></i>
                    <span class="color-danger font-medium">{{ session('error') }}</span>
                </div>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('esbtp.frais.store') }}">
            @csrf
            
            <!-- Informations principales -->
            <div class="card-moderne mb-lg">
                <div class="p-lg">
                    <div class="section-title mb-lg">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations Principales
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-lg);">
                        <div class="form-floating-modern">
                            <input type="text" 
                                   class="@error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   placeholder=" ">
                            <label for="name">Nom de la catégorie <span class="color-danger">*</span></label>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-floating-modern">
                            <input type="text" 
                                   class="@error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   value="{{ old('code') }}" 
                                   required 
                                   placeholder=" ">
                            <label for="code">Code unique <span class="color-danger">*</span></label>
                            <div class="form-text">Le code sera automatiquement converti en majuscules</div>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="form-floating-modern">
                        <textarea class="@error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="4" 
                                  placeholder=" ">{{ old('description') }}</textarea>
                        <label for="description">Description détaillée</label>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <!-- Configuration financière -->
            <div class="card-moderne mb-lg">
                <div class="p-lg">
                    <div class="section-title mb-lg">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        Configuration Financière
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-lg);">
                        <div class="form-floating-modern">
                            <input type="number" 
                                   class="@error('default_amount') is-invalid @enderror" 
                                   id="default_amount" 
                                   name="default_amount" 
                                   value="{{ old('default_amount') }}" 
                                   min="0" 
                                   step="1" 
                                   required 
                                   placeholder=" ">
                            <label for="default_amount">Montant par défaut (FCFA) <span class="color-danger">*</span></label>
                            @error('default_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-floating-modern">
                            <input type="number"
                                   class="@error('payment_deadline_days') is-invalid @enderror"
                                   id="payment_deadline_days"
                                   name="payment_deadline_days"
                                   value="{{ old('payment_deadline_days', 30) }}"
                                   min="1"
                                   max="365"
                                   required
                                   placeholder=" ">
                            <label for="payment_deadline_days">Délai de paiement (jours) <span class="color-danger">*</span></label>
                            <small style="display:block;margin-top:.3rem;color:#64748b;font-size:.75rem;">
                                <i class="fas fa-info-circle" style="margin-right:.25rem;"></i>
                                Nombre de jours accordés après l'inscription pour régler ces frais. Passé ce délai, l'étudiant sera considéré en retard.
                            </small>
                            @error('payment_deadline_days')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Personnalisation visuelle -->
            <div class="card-moderne mb-lg">
                <div class="p-lg">
                    <div class="section-title mb-lg">
                        <i class="fas fa-palette me-2"></i>
                        Personnalisation Visuelle
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-lg);">
                        <div class="form-floating-modern">
                            <input type="text" 
                                   class="@error('icon') is-invalid @enderror" 
                                   id="icon" 
                                   name="icon" 
                                   value="{{ old('icon') }}" 
                                   placeholder=" ">
                            <label for="icon">Icône (classe CSS)</label>
                            <div class="form-text">Utilisez les classes FontAwesome (ex: fas fa-money-bill)</div>
                            <span class="icon-preview" id="icon-preview">
                                <i class="fas fa-money-bill"></i>
                            </span>
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-floating-modern">
                            <select class="@error('color') is-invalid @enderror" 
                                    id="color" 
                                    name="color">
                                <option value="">Couleur par défaut</option>
                                <option value="primary" {{ old('color') == 'primary' ? 'selected' : '' }}>Bleu (Primary)</option>
                                <option value="success" {{ old('color') == 'success' ? 'selected' : '' }}>Vert (Success)</option>
                                <option value="info" {{ old('color') == 'info' ? 'selected' : '' }}>Cyan (Info)</option>
                                <option value="warning" {{ old('color') == 'warning' ? 'selected' : '' }}>Orange (Warning)</option>
                                <option value="danger" {{ old('color') == 'danger' ? 'selected' : '' }}>Rouge (Danger)</option>
                                <option value="secondary" {{ old('color') == 'secondary' ? 'selected' : '' }}>Gris (Secondary)</option>
                            </select>
                            <label for="color">Couleur thématique</label>
                            <span class="color-preview bg-primary" id="color-preview"></span>
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Configuration avancée -->
            <div class="card-moderne mb-lg">
                <div class="p-lg">
                    <div class="section-title mb-lg">
                        <i class="fas fa-cogs me-2"></i>
                        Configuration Avancée
                    </div>
                    
                    <div class="checkbox-modern">
                        <input class="@error('is_mandatory') is-invalid @enderror" 
                               type="checkbox" 
                               id="is_mandatory" 
                               name="is_mandatory" 
                               value="1" 
                               {{ old('is_mandatory') ? 'checked' : '' }}>
                        <div>
                            <label for="is_mandatory">Frais obligatoire</label>
                            <div class="checkbox-description">Les frais obligatoires doivent être configurés pour toutes les classes et filières</div>
                        </div>
                        @error('is_mandatory')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <!-- Exemples et aide -->
            <div class="card-moderne mb-lg help-section">
                <div class="p-lg">
                    <div class="section-title mb-lg">
                        <i class="fas fa-lightbulb me-2"></i>
                        Exemples de Catégories Courantes
                    </div>
                    
                    <div class="examples-grid">
                        <div class="example-item">
                            <h6 class="font-semibold color-primary mb-sm">
                                <i class="fas fa-bus me-2"></i>Transport
                            </h6>
                            <p class="mb-sm font-medium">Code: TRANSPORT</p>
                            <p class="text-small color-secondary mb-0">Avec variants par arrêt ou zone géographique</p>
                        </div>
                        
                        <div class="example-item">
                            <h6 class="font-semibold color-success mb-sm">
                                <i class="fas fa-utensils me-2"></i>Cantine
                            </h6>
                            <p class="mb-sm font-medium">Code: CANTINE</p>
                            <p class="text-small color-secondary mb-0">Avec variants par type de menu (standard, premium)</p>
                        </div>
                        
                        <div class="example-item">
                            <h6 class="font-semibold color-warning mb-sm">
                                <i class="fas fa-bed me-2"></i>Hébergement
                            </h6>
                            <p class="mb-sm font-medium">Code: HEBERGEMENT</p>
                            <p class="text-small color-secondary mb-0">Avec variants par type de chambre (simple, double)</p>
                        </div>
                        
                        <div class="example-item">
                            <h6 class="font-semibold color-accent mb-sm">
                                <i class="fas fa-tools me-2"></i>Matériel
                            </h6>
                            <p class="mb-sm font-medium">Code: MATERIEL</p>
                            <p class="text-small color-secondary mb-0">Optionnel par filière (informatique, laboratoire, etc.)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-md);">
                <button type="submit" class="btn-acasi primary" style="padding: var(--space-md) var(--space-xl);">
                    <i class="fas fa-save me-2"></i>Créer la catégorie
                </button>
                <a href="{{ route('esbtp.frais.index') }}" class="btn-acasi secondary" style="padding: var(--space-md) var(--space-xl);">
                    <i class="fas fa-times me-2"></i>Annuler
                </a>
            </div>
        </form>
        
        <!-- Section d'aide détaillée -->
        <div class="card-moderne mt-xl">
            <div class="p-lg">
                <div class="section-title mb-lg">
                    <i class="fas fa-question-circle me-2"></i>
                    Guide d'Utilisation des Catégories
                </div>
                
                <p class="color-secondary mb-lg">Une fois votre catégorie créée, vous aurez accès aux fonctionnalités avancées suivantes :</p>
                
                <ul class="help-list">
                    <li class="help-item">
                        <i class="fas fa-layer-group help-icon"></i>
                        <div>
                            <strong>Gestion des variants</strong>
                            <p class="text-small color-secondary mb-0">Créez différentes options pour une même catégorie (ex: différents arrêts de transport)</p>
                        </div>
                    </li>
                    
                    <li class="help-item">
                        <i class="fas fa-table help-icon"></i>
                        <div>
                            <strong>Configuration matricielle</strong>
                            <p class="text-small color-secondary mb-0">Définissez des montants spécifiques par filière et niveau d'étude</p>
                        </div>
                    </li>
                    
                    <li class="help-item">
                        <i class="fas fa-bell help-icon"></i>
                        <div>
                            <strong>Relances automatiques</strong>
                            <p class="text-small color-secondary mb-0">Configurez des rappels pour les retards de paiement</p>
                        </div>
                    </li>
                    
                    <li class="help-item">
                        <i class="fas fa-chart-bar help-icon"></i>
                        <div>
                            <strong>Statistiques détaillées</strong>
                            <p class="text-small color-secondary mb-0">Suivez les taux de recouvrement et les tendances de paiement</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Auto-générer le code basé sur le nom
    $('#name').on('input', function() {
        const codeField = $('#code');
        if (!codeField.val()) {
            const name = $(this).val();
            const code = name.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 20);
            codeField.val(code);
        }
    });
    
    // Prévisualisation de l'icône
    $('#icon').on('input', function() {
        const iconClass = $(this).val() || 'fas fa-money-bill';
        $('#icon-preview i').attr('class', iconClass);
    });
    
    // Prévisualisation de la couleur
    $('#color').on('change', function() {
        const color = $(this).val() || 'primary';
        const colorPreview = $('#color-preview');
        
        // Supprimer toutes les classes de couleur existantes
        colorPreview.removeClass('bg-primary bg-success bg-info bg-warning bg-danger bg-secondary');
        
        // Ajouter la nouvelle classe de couleur
        colorPreview.addClass('bg-' + color);
    });
    
    // Animation des cartes au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observer toutes les cartes
    $('.card-moderne').each(function() {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)',
            'transition': 'all 0.6s ease-out'
        });
        observer.observe(this);
    });
});
</script>
@endpush