@extends('layouts.app')

@section('title', 'Modifier Coordinateur - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .edit-header {
        background: linear-gradient(135deg, var(--warning), var(--secondary));
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
    }
    
    .edit-header::before {
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
    
    .coordinator-form {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        box-shadow: var(--shadow-card);
        border: 1px solid var(--border);
    }
    
    .form-section {
        margin-bottom: var(--space-xl);
        padding-bottom: var(--space-lg);
        border-bottom: 1px solid var(--border);
    }
    
    .form-section:last-child {
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
    
    .form-group {
        margin-bottom: var(--space-lg);
    }
    
    .form-label {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .form-control, .form-select {
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        font-size: var(--text-normal);
        transition: all 0.3s ease;
        background: var(--surface);
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        transform: translateY(-1px);
    }
    
    .form-help {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-top: var(--space-xs);
    }
    
    .action-buttons {
        display: flex;
        gap: var(--space-md);
        justify-content: center;
        padding-top: var(--space-xl);
        border-top: 1px solid var(--border);
    }
    
    .current-avatar {
        width: 100px;
        height: 100px;
        border-radius: var(--radius-circle);
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        margin: 0 auto var(--space-md);
        border: 4px solid rgba(var(--primary-rgb), 0.2);
    }
    
    .info-card {
        background: var(--background);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-lg);
        border-left: 4px solid var(--info);
    }
    
    .required-field::after {
        content: ' *';
        color: var(--danger);
        font-weight: bold;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header -->
        <div class="edit-header">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div style="display: flex; align-items: center; gap: var(--space-lg); position: relative; z-index: 2;">
                        <div class="current-avatar">
                            {{ strtoupper(substr($coordinateur->name, 0, 2)) }}
                        </div>
                        <div>
                            <h1 style="color: white; margin: 0; font-size: var(--title-main); font-weight: 700;">
                                <i class="fas fa-user-edit me-2"></i>Modifier le Coordinateur
                            </h1>
                            <p style="color: rgba(255,255,255,0.8); margin: var(--space-xs) 0 0 0;">
                                Modification du profil de <strong>{{ $coordinateur->name }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    <div style="position: relative; z-index: 2;">
                        <a href="{{ route('esbtp.coordinateurs.show', $coordinateur) }}" class="btn-acasi secondary" style="margin-right: var(--space-md);">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-acasi">
                            <i class="fas fa-list"></i> Liste
                        </a>
                    </div>
                </div>
            </div>
        </div>

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

        <!-- Informations système -->
        <div class="info-card">
            <div style="display: flex; align-items: center; gap: var(--space-sm);">
                <i class="fas fa-info-circle" style="color: var(--info);"></i>
                <div>
                    <strong>Informations de modification :</strong>
                    Créé le {{ $coordinateur->created_at->format('d/m/Y à H:i') }} - 
                    Dernière modification le {{ $coordinateur->updated_at->format('d/m/Y à H:i') }}
                </div>
            </div>
        </div>

        <!-- Formulaire -->
        <form action="{{ route('esbtp.coordinateurs.update', $coordinateur) }}" method="POST" class="coordinator-form" id="editForm">
            @csrf
            @method('PUT')

            <!-- Section Informations personnelles -->
            <div class="form-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    Informations Personnelles
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name" class="form-label required-field">
                                <i class="fas fa-user" style="color: var(--primary);"></i>
                                Nom complet
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $coordinateur->name) }}" 
                                   placeholder="Nom et prénoms du coordinateur"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-help">Le nom complet tel qu'il apparaîtra dans le système</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email" class="form-label required-field">
                                <i class="fas fa-envelope" style="color: var(--primary);"></i>
                                Adresse email
                            </label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $coordinateur->email) }}" 
                                   placeholder="coordinateur@esbtp-yakro.ci"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-help">Email professionnel pour la connexion et les notifications</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone" style="color: var(--primary);"></i>
                                Téléphone
                            </label>
                            <input type="tel" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $coordinateur->phone) }}" 
                                   placeholder="+225 XX XX XX XX XX">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-help">Numéro de téléphone de contact</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="specialite" class="form-label">
                                <i class="fas fa-graduation-cap" style="color: var(--primary);"></i>
                                Spécialité
                            </label>
                            <input type="text" 
                                   class="form-control @error('specialite') is-invalid @enderror" 
                                   id="specialite" 
                                   name="specialite" 
                                   value="{{ old('specialite', $coordinateur->specialite) }}" 
                                   placeholder="Ex: Génie Civil, Architecture, etc.">
                            @error('specialite')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-help">Domaine de spécialisation principal</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Informations de connexion -->
            <div class="form-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    Informations de Connexion
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="username" class="form-label">
                                <i class="fas fa-at" style="color: var(--primary);"></i>
                                Nom d'utilisateur
                            </label>
                            <input type="text" 
                                   class="form-control @error('username') is-invalid @enderror" 
                                   id="username" 
                                   name="username" 
                                   value="{{ old('username', $coordinateur->username) }}" 
                                   placeholder="nom.prenom"
                                   readonly>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-help">Le nom d'utilisateur ne peut pas être modifié</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock" style="color: var(--primary);"></i>
                                Nouveau mot de passe
                            </label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Laisser vide pour garder l'actuel">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-help">Laisser vide si vous ne souhaitez pas changer le mot de passe</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password_confirmation" class="form-label">
                                <i class="fas fa-lock" style="color: var(--primary);"></i>
                                Confirmer le mot de passe
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   placeholder="Retaper le nouveau mot de passe">
                            <div class="form-help">Confirmation du nouveau mot de passe</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Paramètres -->
            <div class="form-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    Paramètres du Compte
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div style="display: flex; align-items: center; gap: var(--space-md); padding: var(--space-md); background: var(--background); border-radius: var(--radius-medium); border: 1px solid var(--border);">
                                <input type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1" 
                                       {{ old('is_active', $coordinateur->is_active) ? 'checked' : '' }}
                                       style="width: 20px; height: 20px; accent-color: var(--primary);">
                                <label for="is_active" style="margin: 0; font-weight: 600; color: var(--text-primary); cursor: pointer;">
                                    <i class="fas fa-user-check" style="color: var(--success); margin-right: var(--space-xs);"></i>
                                    Compte actif
                                </label>
                            </div>
                            <div class="form-help">Décochez pour désactiver temporairement ce coordinateur</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="action-buttons">
                <a href="{{ route('esbtp.coordinateurs.show', $coordinateur) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>

                <button type="button" class="btn-acasi" style="background-color: var(--danger); color: white;" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="fas fa-trash"></i> Supprimer
                </button>

                <button type="submit" class="btn-acasi primary" id="submitBtn">
                    <i class="fas fa-save"></i> Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-moderne">
            <div class="modal-header" style="background-color: var(--danger); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Confirmation de suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-lg">
                <i class="fas fa-user-times fa-3x mb-lg" style="color: var(--danger);"></i>
                <h6>Êtes-vous sûr de vouloir supprimer ce coordinateur ?</h6>
                <p style="color: var(--text-secondary); margin: var(--space-md) 0;">
                    <strong>{{ $coordinateur->name }}</strong><br>
                    Cette action est irréversible et supprimera toutes les données associées.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <form action="{{ route('esbtp.coordinateurs.destroy', $coordinateur) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-acasi" style="background-color: var(--danger); color: white;">
                        <i class="fas fa-trash"></i> Confirmer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Validation en temps réel
    $('#editForm').on('submit', function(e) {
        let isValid = true;
        const requiredFields = ['name', 'email'];
        
        requiredFields.forEach(function(fieldName) {
            const field = $('#' + fieldName);
            if (!field.val().trim()) {
                field.addClass('is-invalid');
                isValid = false;
            } else {
                field.removeClass('is-invalid');
            }
        });
        
        // Validation email
        const email = $('#email').val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            $('#email').addClass('is-invalid');
            isValid = false;
        }
        
        // Validation confirmation mot de passe
        const password = $('#password').val();
        const passwordConfirm = $('#password_confirmation').val();
        if (password && password !== passwordConfirm) {
            $('#password_confirmation').addClass('is-invalid');
            isValid = false;
            alert('Les mots de passe ne correspondent pas.');
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Animation du bouton de soumission
        const submitBtn = $('#submitBtn');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mise à jour...');
    });
    
    // Effets focus
    $('.form-control, .form-select').on('focus', function() {
        $(this).parent().find('.form-label').css('color', 'var(--primary)');
    }).on('blur', function() {
        $(this).parent().find('.form-label').css('color', 'var(--text-primary)');
    });
    
    // Auto-focus sur le premier champ
    $('#name').focus();
});
</script>
@endpush