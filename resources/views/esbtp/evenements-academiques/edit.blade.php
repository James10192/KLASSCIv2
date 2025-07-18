@extends('layouts.app')

@section('title', 'Modifier l\'Événement Académique - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .form-section {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        margin-bottom: var(--space-lg);
        border: 1px solid var(--border);
    }
    
    .form-section h6 {
        color: var(--primary);
        margin-bottom: var(--space-md);
        font-weight: 600;
    }
    
    .color-picker {
        display: inline-block;
        width: 40px;
        height: 40px;
        border: 2px solid var(--border);
        border-radius: var(--radius-small);
        cursor: pointer;
        margin-right: var(--space-xs);
    }
    
    .color-picker.selected {
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.2);
    }
    
    .icon-picker {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: var(--space-sm);
        margin-top: var(--space-sm);
    }
    
    .icon-option {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: var(--space-sm);
        border: 1px solid var(--border);
        border-radius: var(--radius-small);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .icon-option:hover {
        background: var(--hover);
        border-color: var(--primary);
    }
    
    .icon-option.selected {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Modifier l'Événement Académique</h1>
                <p class="header-subtitle">Modification de l'événement : {{ $evenement->titre }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.evenements-academiques.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
                <a href="{{ route('esbtp.evenements-academiques.show', $evenement) }}" class="btn-acasi info">
                    <i class="fas fa-eye me-1"></i>Voir
                </a>
            </div>
        </div>

        <!-- Formulaire d'édition -->
        <form method="POST" action="{{ route('esbtp.evenements-academiques.update', $evenement) }}">
            @csrf
            @method('PUT')
            
            <!-- Informations de base -->
            <div class="form-section">
                <h6><i class="fas fa-info-circle me-2"></i>Informations de Base</h6>
                <div class="row">
                    <div class="col-md-6">
                        <label for="titre" class="form-label">Titre de l'événement *</label>
                        <input type="text" 
                               class="form-control @error('titre') is-invalid @enderror" 
                               id="titre" 
                               name="titre" 
                               value="{{ old('titre', $evenement->titre) }}" 
                               required>
                        @error('titre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="annee_universitaire_id" class="form-label">Année Universitaire *</label>
                        <select class="form-select @error('annee_universitaire_id') is-invalid @enderror" 
                                id="annee_universitaire_id" 
                                name="annee_universitaire_id" 
                                required>
                            <option value="">Sélectionner une année</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id }}" 
                                        {{ old('annee_universitaire_id', $evenement->annee_universitaire_id) == $annee->id ? 'selected' : '' }}>
                                    {{ $annee->name }}
                                    @if($annee->is_current) (En cours) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('annee_universitaire_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3" 
                                  required>{{ old('description', $evenement->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Dates et heures -->
            <div class="form-section">
                <h6><i class="fas fa-calendar me-2"></i>Dates et Heures</h6>
                <div class="row">
                    <div class="col-md-3">
                        <label for="date_debut" class="form-label">Date de début *</label>
                        <input type="date" 
                               class="form-control @error('date_debut') is-invalid @enderror" 
                               id="date_debut" 
                               name="date_debut" 
                               value="{{ old('date_debut', $evenement->date_debut->format('Y-m-d')) }}" 
                               required>
                        @error('date_debut')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3">
                        <label for="date_fin" class="form-label">Date de fin</label>
                        <input type="date" 
                               class="form-control @error('date_fin') is-invalid @enderror" 
                               id="date_fin" 
                               name="date_fin" 
                               value="{{ old('date_fin', $evenement->date_fin ? $evenement->date_fin->format('Y-m-d') : '') }}">
                        @error('date_fin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3">
                        <label for="heure_debut" class="form-label">Heure de début</label>
                        <input type="time" 
                               class="form-control @error('heure_debut') is-invalid @enderror" 
                               id="heure_debut" 
                               name="heure_debut" 
                               value="{{ old('heure_debut', $evenement->heure_debut ? $evenement->heure_debut->format('H:i') : '') }}">
                        @error('heure_debut')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3">
                        <label for="heure_fin" class="form-label">Heure de fin</label>
                        <input type="time" 
                               class="form-control @error('heure_fin') is-invalid @enderror" 
                               id="heure_fin" 
                               name="heure_fin" 
                               value="{{ old('heure_fin', $evenement->heure_fin ? $evenement->heure_fin->format('H:i') : '') }}">
                        @error('heure_fin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Type et apparence -->
            <div class="form-section">
                <h6><i class="fas fa-palette me-2"></i>Type et Apparence</h6>
                <div class="row">
                    <div class="col-md-4">
                        <label for="type" class="form-label">Type d'événement *</label>
                        <select class="form-select @error('type') is-invalid @enderror" 
                                id="type" 
                                name="type" 
                                required>
                            <option value="">Sélectionner un type</option>
                            <option value="rentree" {{ old('type', $evenement->type) == 'rentree' ? 'selected' : '' }}>Rentrée</option>
                            <option value="orientation" {{ old('type', $evenement->type) == 'orientation' ? 'selected' : '' }}>Orientation</option>
                            <option value="examens" {{ old('type', $evenement->type) == 'examens' ? 'selected' : '' }}>Examens</option>
                            <option value="vacances" {{ old('type', $evenement->type) == 'vacances' ? 'selected' : '' }}>Vacances</option>
                            <option value="reprise" {{ old('type', $evenement->type) == 'reprise' ? 'selected' : '' }}>Reprise</option>
                            <option value="soutenances" {{ old('type', $evenement->type) == 'soutenances' ? 'selected' : '' }}>Soutenances</option>
                            <option value="ceremonie" {{ old('type', $evenement->type) == 'ceremonie' ? 'selected' : '' }}>Cérémonie</option>
                            <option value="fermeture" {{ old('type', $evenement->type) == 'fermeture' ? 'selected' : '' }}>Fermeture</option>
                            <option value="stage" {{ old('type', $evenement->type) == 'stage' ? 'selected' : '' }}>Stage</option>
                            <option value="reunion" {{ old('type', $evenement->type) == 'reunion' ? 'selected' : '' }}>Réunion</option>
                            <option value="formation" {{ old('type', $evenement->type) == 'formation' ? 'selected' : '' }}>Formation</option>
                            <option value="conference" {{ old('type', $evenement->type) == 'conference' ? 'selected' : '' }}>Conférence</option>
                            <option value="autre" {{ old('type', $evenement->type) == 'autre' ? 'selected' : '' }}>Autre</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4">
                        <label for="statut" class="form-label">Statut *</label>
                        <select class="form-select @error('statut') is-invalid @enderror" 
                                id="statut" 
                                name="statut" 
                                required>
                            <option value="planifie" {{ old('statut', $evenement->statut) == 'planifie' ? 'selected' : '' }}>Planifié</option>
                            <option value="confirme" {{ old('statut', $evenement->statut) == 'confirme' ? 'selected' : '' }}>Confirmé</option>
                            <option value="annule" {{ old('statut', $evenement->statut) == 'annule' ? 'selected' : '' }}>Annulé</option>
                            <option value="reporte" {{ old('statut', $evenement->statut) == 'reporte' ? 'selected' : '' }}>Reporté</option>
                            <option value="termine" {{ old('statut', $evenement->statut) == 'termine' ? 'selected' : '' }}>Terminé</option>
                        </select>
                        @error('statut')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4">
                        <label for="lieu" class="form-label">Lieu</label>
                        <input type="text" 
                               class="form-control @error('lieu') is-invalid @enderror" 
                               id="lieu" 
                               name="lieu" 
                               value="{{ old('lieu', $evenement->lieu) }}">
                        @error('lieu')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Couleur</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['primary', 'secondary', 'success', 'info', 'warning', 'danger', 'light', 'dark'] as $color)
                                <div class="color-picker bg-{{ $color }} {{ old('couleur', $evenement->couleur) == $color ? 'selected' : '' }}" 
                                     data-color="{{ $color }}" 
                                     title="{{ ucfirst($color) }}"></div>
                            @endforeach
                        </div>
                        <input type="hidden" name="couleur" id="couleur" value="{{ old('couleur', $evenement->couleur) }}">
                        @error('couleur')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Icône</label>
                        <div class="icon-picker">
                            @foreach(['calendar', 'graduation-cap', 'book', 'users', 'clipboard', 'trophy', 'bell', 'star', 'heart', 'home', 'briefcase', 'cog'] as $icon)
                                <div class="icon-option {{ old('icone', $evenement->icone) == $icon ? 'selected' : '' }}" 
                                     data-icon="{{ $icon }}">
                                    <i class="fas fa-{{ $icon }}"></i>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="icone" id="icone" value="{{ old('icone', $evenement->icone) }}">
                        @error('icone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Options d'affichage -->
            <div class="form-section">
                <h6><i class="fas fa-eye me-2"></i>Options d'Affichage</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="afficher_calendrier" 
                                   name="afficher_calendrier" 
                                   value="1"
                                   {{ old('afficher_calendrier', $evenement->afficher_calendrier) ? 'checked' : '' }}>
                            <label class="form-check-label" for="afficher_calendrier">
                                Afficher dans le calendrier
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="afficher_timeline" 
                                   name="afficher_timeline" 
                                   value="1"
                                   {{ old('afficher_timeline', $evenement->afficher_timeline) ? 'checked' : '' }}>
                            <label class="form-check-label" for="afficher_timeline">
                                Afficher dans la timeline
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="notification_active" 
                                   name="notification_active" 
                                   value="1"
                                   {{ old('notification_active', $evenement->notification_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notification_active">
                                Activer les notifications
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="jours_notification" class="form-label">Jours avant notification</label>
                        <input type="number" 
                               class="form-control @error('jours_notification') is-invalid @enderror" 
                               id="jours_notification" 
                               name="jours_notification" 
                               value="{{ old('jours_notification', $evenement->jours_notification) }}" 
                               min="0" 
                               max="365">
                        @error('jours_notification')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="form-section">
                <h6><i class="fas fa-sticky-note me-2"></i>Notes Additionnelles</h6>
                <div class="row">
                    <div class="col-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" 
                                  name="notes" 
                                  rows="3">{{ old('notes', $evenement->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="form-section">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('esbtp.evenements-academiques.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-times me-1"></i>Annuler
                    </a>
                    <button type="submit" class="btn-acasi success">
                        <i class="fas fa-save me-1"></i>Mettre à jour
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Gestion des couleurs
    $('.color-picker').on('click', function() {
        $('.color-picker').removeClass('selected');
        $(this).addClass('selected');
        $('#couleur').val($(this).data('color'));
    });
    
    // Gestion des icônes
    $('.icon-option').on('click', function() {
        $('.icon-option').removeClass('selected');
        $(this).addClass('selected');
        $('#icone').val($(this).data('icon'));
    });
    
    // Validation des dates
    $('#date_debut, #date_fin').on('change', function() {
        var dateDebut = $('#date_debut').val();
        var dateFin = $('#date_fin').val();
        
        if (dateDebut && dateFin && dateDebut > dateFin) {
            alert('La date de fin doit être postérieure à la date de début');
            $('#date_fin').val('');
        }
    });
    
    // Validation des heures
    $('#heure_debut, #heure_fin').on('change', function() {
        var heureDebut = $('#heure_debut').val();
        var heureFin = $('#heure_fin').val();
        var dateDebut = $('#date_debut').val();
        var dateFin = $('#date_fin').val();
        
        if (heureDebut && heureFin && (!dateFin || dateDebut === dateFin) && heureDebut >= heureFin) {
            alert('L\'heure de fin doit être postérieure à l\'heure de début');
            $('#heure_fin').val('');
        }
    });
});
</script>
@endpush