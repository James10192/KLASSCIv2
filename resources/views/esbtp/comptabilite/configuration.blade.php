@extends('layouts.app')

@section('title', 'Configuration Comptabilité - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .config-header {
        background: linear-gradient(135deg, var(--success), var(--accent-blue));
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
    }
    
    .config-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 150px;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transform: skewX(-15deg);
        transform-origin: top;
    }
    
    .config-form {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        box-shadow: var(--shadow-card);
        border: 1px solid var(--border);
    }
    
    .config-section {
        margin-bottom: var(--space-xl);
        padding-bottom: var(--space-lg);
        border-bottom: 1px solid var(--border);
    }
    
    .config-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .section-title {
        color: var(--primary);
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: var(--space-lg);
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }
    
    .section-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-circle);
        background: rgba(var(--primary-rgb), 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 1.1rem;
    }
    
    .config-item {
        background: var(--background);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-md);
        transition: all 0.3s ease;
    }
    
    .config-item:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-card);
    }
    
    .config-item-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: var(--space-sm);
    }
    
    .config-label {
        font-weight: 600;
        color: var(--text-primary);
        font-size: var(--text-normal);
    }
    
    .config-key {
        font-family: monospace;
        background: rgba(var(--neutral-rgb), 0.1);
        color: var(--neutral);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-small);
    }
    
    .config-description {
        color: var(--text-secondary);
        font-size: var(--text-small);
        margin-bottom: var(--space-md);
        line-height: 1.5;
    }
    
    .config-control {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .form-control, .form-select {
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-sm) var(--space-md);
        font-size: var(--text-normal);
        transition: all 0.3s ease;
        background: var(--surface);
        min-width: 200px;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        transform: translateY(-1px);
    }
    
    .config-value-display {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-small);
        padding: var(--space-sm);
        font-family: monospace;
        color: var(--text-primary);
        font-size: var(--text-small);
    }
    
    .config-type-badge {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .type-string {
        background: rgba(var(--info-rgb), 0.1);
        color: var(--info);
    }
    
    .type-number {
        background: rgba(var(--warning-rgb), 0.1);
        color: var(--warning);
    }
    
    .type-boolean {
        background: rgba(var(--success-rgb), 0.1);
        color: var(--success);
    }
    
    .type-json {
        background: rgba(var(--secondary-rgb), 0.1);
        color: var(--secondary);
    }
    
    .action-buttons {
        display: flex;
        gap: var(--space-md);
        justify-content: center;
        padding-top: var(--space-xl);
        border-top: 1px solid var(--border);
    }
    
    .info-alert {
        background: rgba(var(--info-rgb), 0.1);
        border: 1px solid var(--info);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        border-left: 4px solid var(--info);
    }
    
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--border);
        transition: .4s;
        border-radius: 24px;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .slider {
        background-color: var(--success);
    }
    
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    
    .empty-state {
        text-align: center;
        padding: var(--space-xl);
        color: var(--text-secondary);
    }
    
    .empty-state .icon {
        font-size: 3rem;
        margin-bottom: var(--space-md);
        opacity: 0.5;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .stat-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        text-align: center;
        box-shadow: var(--shadow-card);
        border: 1px solid var(--border);
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary);
        display: block;
        margin-bottom: var(--space-xs);
    }
    
    .stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header -->
        <div class="config-header">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div style="display: flex; align-items: center; gap: var(--space-lg); position: relative; z-index: 2;">
                        <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--accent-blue); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; box-shadow: var(--shadow-elevated);">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div>
                            <h1 style="color: white; margin: 0; font-size: var(--title-main); font-weight: 700;">
                                Configuration Comptabilité
                            </h1>
                            <p style="color: rgba(255,255,255,0.8); margin: var(--space-xs) 0 0 0;">
                                Paramètres et configuration du module comptabilité
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    <div style="position: relative; z-index: 2;">
                        <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="btn-acasi secondary">
                            <i class="fas fa-arrow-left"></i> Retour au dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages de succès -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-lg" style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); border-radius: var(--radius-medium); padding: var(--space-lg);">
            <div style="display: flex; align-items: center; gap: var(--space-md);">
                <i class="fas fa-check-circle fa-2x" style="color: var(--success);"></i>
                <div>
                    <h6 style="color: var(--success); margin: 0;">{{ session('success') }}</h6>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Messages d'erreur -->
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-lg" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); border-radius: var(--radius-medium); padding: var(--space-lg);">
            <div style="display: flex; align-items: center; gap: var(--space-md);">
                <i class="fas fa-exclamation-triangle fa-2x" style="color: var(--danger);"></i>
                <div>
                    <h6 style="color: var(--danger); margin: 0 0 var(--space-sm) 0;">Erreurs de validation</h6>
                    <ul style="margin: 0; color: var(--danger);">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Information -->
        <div class="info-alert">
            <div style="display: flex; align-items: center; gap: var(--space-sm);">
                <i class="fas fa-info-circle" style="color: var(--info);"></i>
                <div>
                    <strong>Configuration du système comptable :</strong>
                    Modifiez les paramètres ci-dessous pour personnaliser le comportement du module comptabilité. 
                    Les changements sont appliqués immédiatement après sauvegarde.
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value">{{ $configurations->count() }}</span>
                <div class="stat-label">Paramètres configurables</div>
            </div>
            <div class="stat-card">
                <span class="stat-value">{{ $configurations->where('type', 'boolean')->count() }}</span>
                <div class="stat-label">Options activables</div>
            </div>
            <div class="stat-card">
                <span class="stat-value">{{ $configurations->where('type', 'number')->count() }}</span>
                <div class="stat-label">Valeurs numériques</div>
            </div>
            <div class="stat-card">
                <span class="stat-value">{{ $configurations->where('type', 'string')->count() }}</span>
                <div class="stat-label">Textes configurables</div>
            </div>
        </div>

        <!-- Formulaire de configuration -->
        @if($configurations->count() > 0)
        <form action="{{ route('esbtp.comptabilite.configuration.update') }}" method="POST" class="config-form" id="configForm">
            @csrf

            <!-- Section Générale -->
            @php
                $generalConfigs = $configurations->filter(function($config) {
                    return str_starts_with($config->cle, 'general_') || str_starts_with($config->cle, 'comptabilite_');
                });
            @endphp
            
            @if($generalConfigs->count() > 0)
            <div class="config-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    Paramètres Généraux
                </div>

                @foreach($generalConfigs as $config)
                <div class="config-item">
                    <div class="config-item-header">
                        <div class="config-label">
                            {{ $config->nom ?? str_replace('_', ' ', ucwords($config->cle, '_')) }}
                        </div>
                        <div style="display: flex; gap: var(--space-xs);">
                            <span class="config-type-badge type-{{ $config->type ?? 'string' }}">
                                {{ strtoupper($config->type ?? 'string') }}
                            </span>
                            <span class="config-key">{{ $config->cle }}</span>
                        </div>
                    </div>
                    
                    @if($config->description)
                    <div class="config-description">{{ $config->description }}</div>
                    @endif
                    
                    <div class="config-control">
                        @if(($config->type ?? 'string') === 'boolean')
                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       name="configurations[{{ $config->id }}]" 
                                       value="1"
                                       {{ $config->valeur == '1' || $config->valeur === 'true' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <input type="hidden" name="configurations[{{ $config->id }}]" value="0">
                        @elseif(($config->type ?? 'string') === 'number')
                            <input type="number" 
                                   class="form-control"
                                   name="configurations[{{ $config->id }}]" 
                                   value="{{ $config->valeur }}"
                                   step="0.01">
                        @elseif(($config->type ?? 'string') === 'select' && isset($config->options))
                            <select class="form-select" name="configurations[{{ $config->id }}]">
                                @foreach(json_decode($config->options, true) as $value => $label)
                                <option value="{{ $value }}" {{ $config->valeur == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" 
                                   class="form-control"
                                   name="configurations[{{ $config->id }}]" 
                                   value="{{ $config->valeur }}"
                                   placeholder="Saisir {{ strtolower($config->nom ?? $config->cle) }}">
                        @endif
                        
                        @if($config->unite)
                        <span style="color: var(--text-secondary); font-size: var(--text-small);">{{ $config->unite }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Section Frais et Paiements -->
            @php
                $fraisConfigs = $configurations->filter(function($config) {
                    return str_starts_with($config->cle, 'frais_') || str_starts_with($config->cle, 'paiement_');
                });
            @endphp
            
            @if($fraisConfigs->count() > 0)
            <div class="config-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    Frais et Paiements
                </div>

                @foreach($fraisConfigs as $config)
                <div class="config-item">
                    <div class="config-item-header">
                        <div class="config-label">
                            {{ $config->nom ?? str_replace('_', ' ', ucwords($config->cle, '_')) }}
                        </div>
                        <div style="display: flex; gap: var(--space-xs);">
                            <span class="config-type-badge type-{{ $config->type ?? 'string' }}">
                                {{ strtoupper($config->type ?? 'string') }}
                            </span>
                            <span class="config-key">{{ $config->cle }}</span>
                        </div>
                    </div>
                    
                    @if($config->description)
                    <div class="config-description">{{ $config->description }}</div>
                    @endif
                    
                    <div class="config-control">
                        @if(($config->type ?? 'string') === 'boolean')
                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       name="configurations[{{ $config->id }}]" 
                                       value="1"
                                       {{ $config->valeur == '1' || $config->valeur === 'true' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <input type="hidden" name="configurations[{{ $config->id }}]" value="0">
                        @elseif(($config->type ?? 'string') === 'number')
                            <input type="number" 
                                   class="form-control"
                                   name="configurations[{{ $config->id }}]" 
                                   value="{{ $config->valeur }}"
                                   step="0.01">
                        @else
                            <input type="text" 
                                   class="form-control"
                                   name="configurations[{{ $config->id }}]" 
                                   value="{{ $config->valeur }}"
                                   placeholder="Saisir {{ strtolower($config->nom ?? $config->cle) }}">
                        @endif
                        
                        @if($config->unite)
                        <span style="color: var(--text-secondary); font-size: var(--text-small);">{{ $config->unite }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Section Rapports -->
            @php
                $rapportsConfigs = $configurations->filter(function($config) {
                    return str_starts_with($config->cle, 'rapport_') || str_starts_with($config->cle, 'export_');
                });
            @endphp
            
            @if($rapportsConfigs->count() > 0)
            <div class="config-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    Rapports et Exports
                </div>

                @foreach($rapportsConfigs as $config)
                <div class="config-item">
                    <div class="config-item-header">
                        <div class="config-label">
                            {{ $config->nom ?? str_replace('_', ' ', ucwords($config->cle, '_')) }}
                        </div>
                        <div style="display: flex; gap: var(--space-xs);">
                            <span class="config-type-badge type-{{ $config->type ?? 'string' }}">
                                {{ strtoupper($config->type ?? 'string') }}
                            </span>
                            <span class="config-key">{{ $config->cle }}</span>
                        </div>
                    </div>
                    
                    @if($config->description)
                    <div class="config-description">{{ $config->description }}</div>
                    @endif
                    
                    <div class="config-control">
                        @if(($config->type ?? 'string') === 'boolean')
                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       name="configurations[{{ $config->id }}]" 
                                       value="1"
                                       {{ $config->valeur == '1' || $config->valeur === 'true' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <input type="hidden" name="configurations[{{ $config->id }}]" value="0">
                        @elseif(($config->type ?? 'string') === 'number')
                            <input type="number" 
                                   class="form-control"
                                   name="configurations[{{ $config->id }}]" 
                                   value="{{ $config->valeur }}"
                                   step="0.01">
                        @else
                            <input type="text" 
                                   class="form-control"
                                   name="configurations[{{ $config->id }}]" 
                                   value="{{ $config->valeur }}"
                                   placeholder="Saisir {{ strtolower($config->nom ?? $config->cle) }}">
                        @endif
                        
                        @if($config->unite)
                        <span style="color: var(--text-secondary); font-size: var(--text-small);">{{ $config->unite }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Autres configurations -->
            @php
                $autresConfigs = $configurations->filter(function($config) {
                    return !str_starts_with($config->cle, 'general_') && 
                           !str_starts_with($config->cle, 'comptabilite_') &&
                           !str_starts_with($config->cle, 'frais_') && 
                           !str_starts_with($config->cle, 'paiement_') &&
                           !str_starts_with($config->cle, 'rapport_') && 
                           !str_starts_with($config->cle, 'export_');
                });
            @endphp
            
            @if($autresConfigs->count() > 0)
            <div class="config-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-ellipsis-h"></i>
                    </div>
                    Autres Paramètres
                </div>

                @foreach($autresConfigs as $config)
                <div class="config-item">
                    <div class="config-item-header">
                        <div class="config-label">
                            {{ $config->nom ?? str_replace('_', ' ', ucwords($config->cle, '_')) }}
                        </div>
                        <div style="display: flex; gap: var(--space-xs);">
                            <span class="config-type-badge type-{{ $config->type ?? 'string' }}">
                                {{ strtoupper($config->type ?? 'string') }}
                            </span>
                            <span class="config-key">{{ $config->cle }}</span>
                        </div>
                    </div>
                    
                    @if($config->description)
                    <div class="config-description">{{ $config->description }}</div>
                    @endif
                    
                    <div class="config-control">
                        @if(($config->type ?? 'string') === 'boolean')
                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       name="configurations[{{ $config->id }}]" 
                                       value="1"
                                       {{ $config->valeur == '1' || $config->valeur === 'true' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <input type="hidden" name="configurations[{{ $config->id }}]" value="0">
                        @elseif(($config->type ?? 'string') === 'number')
                            <input type="number" 
                                   class="form-control"
                                   name="configurations[{{ $config->id }}]" 
                                   value="{{ $config->valeur }}"
                                   step="0.01">
                        @else
                            <input type="text" 
                                   class="form-control"
                                   name="configurations[{{ $config->id }}]" 
                                   value="{{ $config->valeur }}"
                                   placeholder="Saisir {{ strtolower($config->nom ?? $config->cle) }}">
                        @endif
                        
                        @if($config->unite)
                        <span style="color: var(--text-secondary); font-size: var(--text-small);">{{ $config->unite }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Boutons d'action -->
            <div class="action-buttons">
                <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>

                <button type="reset" class="btn-acasi" style="background-color: var(--warning); color: white;">
                    <i class="fas fa-undo"></i> Réinitialiser
                </button>

                <button type="submit" class="btn-acasi primary" id="submitBtn">
                    <i class="fas fa-save"></i> Sauvegarder la configuration
                </button>
            </div>
        </form>
        @else
        <div class="config-form">
            <div class="empty-state">
                <div class="icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <h5>Aucune configuration disponible</h5>
                <p>Il n'y a actuellement aucun paramètre de configuration défini pour le module comptabilité.</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Animation d'entrée des éléments
    $('.config-item').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        });
        
        setTimeout(() => {
            $(this).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 300);
        }, index * 50);
    });
    
    // Validation et soumission du formulaire
    $('#configForm').on('submit', function(e) {
        const submitBtn = $('#submitBtn');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sauvegarde...');
        
        // Animation de confirmation
        setTimeout(() => {
            if (!$(this).find(':invalid').length) {
                // Si pas d'erreurs, continuer
            }
        }, 100);
    });
    
    // Effets sur les contrôles
    $('.form-control, .form-select').on('focus', function() {
        $(this).closest('.config-item').css({
            'transform': 'translateY(-2px)',
            'box-shadow': 'var(--shadow-hover)'
        });
    }).on('blur', function() {
        $(this).closest('.config-item').css({
            'transform': 'translateY(0)',
            'box-shadow': 'none'
        });
    });
    
    // Confirmation pour la réinitialisation
    $('button[type="reset"]').on('click', function(e) {
        if (!confirm('Êtes-vous sûr de vouloir réinitialiser tous les paramètres ? Les modifications non sauvegardées seront perdues.')) {
            e.preventDefault();
        }
    });
    
    // Indication des modifications non sauvegardées
    let formModified = false;
    $('#configForm input, #configForm select').on('change', function() {
        formModified = true;
        $('#submitBtn').addClass('btn-pulse');
    });
    
    // Avertissement avant fermeture si modifications non sauvegardées
    $(window).on('beforeunload', function() {
        if (formModified) {
            return 'Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir quitter cette page ?';
        }
    });
    
    // Supprimer l'avertissement après sauvegarde
    $('#configForm').on('submit', function() {
        formModified = false;
    });
});
</script>

<style>
.btn-pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(var(--primary-rgb), 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(var(--primary-rgb), 0); }
    100% { box-shadow: 0 0 0 0 rgba(var(--primary-rgb), 0); }
}
</style>
@endpush