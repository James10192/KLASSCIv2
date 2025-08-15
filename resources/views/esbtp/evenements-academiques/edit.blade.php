@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .event-form-container {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        margin: var(--space-lg) 0;
    }
    
    .event-form-header {
        padding: var(--space-xl);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }
    
    .event-form-header h1 {
        margin: 0;
        font-size: var(--title-main);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .event-form-header p {
        margin: var(--space-sm) 0 0 0;
        opacity: 0.9;
        font-size: var(--text-normal);
    }
    
    .event-form-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: var(--space-xl);
        padding: var(--space-xl);
    }
    
    .event-form-main {
        display: flex;
        flex-direction: column;
        gap: var(--space-lg);
    }
    
    .event-form-sidebar {
        background: var(--background);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        height: fit-content;
        position: sticky;
        top: var(--space-lg);
    }
    
    .form-section {
        margin-bottom: var(--space-xl);
    }
    
    .form-section:last-child {
        margin-bottom: 0;
    }
    
    .form-section-title {
        font-size: var(--title-section);
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-lg);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        padding-bottom: var(--space-sm);
        border-bottom: 2px solid var(--primary);
    }
    
    .form-section-title i {
        color: var(--primary);
    }
    
    .event-form-footer {
        padding: var(--space-lg) var(--space-xl);
        background: var(--background);
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 0 0 var(--radius-medium) var(--radius-medium);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .event-form-actions {
        display: flex;
        gap: var(--space-md);
    }
    
    @media (max-width: 1024px) {
        .event-form-layout {
            grid-template-columns: 1fr;
            gap: var(--space-lg);
        }
        
        .event-form-sidebar {
            position: static;
        }
    }
    
    @media (max-width: 768px) {
        .event-form-header,
        .event-form-layout,
        .event-form-footer {
            padding: var(--space-lg);
        }
        
        .form-grid-2 {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="event-form-container">
            <div class="event-form-header">
                <h1>
                    <i class="fas fa-edit"></i>
                    Modifier l'Événement Académique
                </h1>
                <p>Modifiez les détails de cet événement dans le calendrier académique.</p>
            </div>
            
            <form action="{{ route('esbtp.evenements-academiques.update', $evenementAcademique) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="event-form-layout">
                    <div class="event-form-main">
                        <!-- Section Informations de base -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-info-circle"></i>
                                Informations de base
                            </div>
                            
                            <div class="form-group-moderne">
                                <label for="annee_universitaire_id" class="form-label-moderne">
                                    Année Universitaire <span class="text-danger">*</span>
                                </label>
                                <select name="annee_universitaire_id" id="annee_universitaire_id" 
                                        class="form-select-moderne @error('annee_universitaire_id') is-invalid @enderror" required>
                                    <option value="">Sélectionner une année</option>
                                    @foreach($annees as $annee)
                                        <option value="{{ $annee->id }}" 
                                                {{ old('annee_universitaire_id', $evenementAcademique->annee_universitaire_id) == $annee->id ? 'selected' : '' }}>
                                            {{ $annee->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('annee_universitaire_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="titre" class="form-label-moderne">
                                    Titre <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="titre" id="titre" 
                                       class="form-input-moderne @error('titre') is-invalid @enderror"
                                       value="{{ old('titre', $evenementAcademique->titre) }}" required>
                                @error('titre')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="description" class="form-label-moderne">
                                    Description <span class="text-danger">*</span>
                                </label>
                                <textarea name="description" id="description" rows="4"
                                          class="form-input-moderne @error('description') is-invalid @enderror" 
                                          required>{{ old('description', $evenementAcademique->description) }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Section Dates et horaires -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-calendar"></i>
                                Dates et horaires
                            </div>
                            
                            <div class="form-grid-2">
                                <div class="form-group-moderne">
                                    <label for="date_debut" class="form-label-moderne">
                                        Date de début <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" name="date_debut" id="date_debut"
                                           class="form-input-moderne @error('date_debut') is-invalid @enderror"
                                           value="{{ old('date_debut', $evenementAcademique->date_debut?->format('Y-m-d')) }}" required>
                                    @error('date_debut')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                
                                <div class="form-group-moderne">
                                    <label for="date_fin" class="form-label-moderne">Date de fin</label>
                                    <input type="date" name="date_fin" id="date_fin"
                                           class="form-input-moderne @error('date_fin') is-invalid @enderror"
                                           value="{{ old('date_fin', $evenementAcademique->date_fin?->format('Y-m-d')) }}">
                                    <small class="form-text-muted">
                                        Laissez vide pour un événement d'une journée
                                    </small>
                                    @error('date_fin')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group-moderne">
                                    <label for="heure_debut" class="form-label-moderne">Heure de début</label>
                                    <input type="time" name="heure_debut" id="heure_debut"
                                           class="form-input-moderne @error('heure_debut') is-invalid @enderror"
                                           value="{{ old('heure_debut', $evenementAcademique->heure_debut?->format('H:i')) }}">
                                    @error('heure_debut')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                
                                <div class="form-group-moderne">
                                    <label for="heure_fin" class="form-label-moderne">Heure de fin</label>
                                    <input type="time" name="heure_fin" id="heure_fin"
                                           class="form-input-moderne @error('heure_fin') is-invalid @enderror"
                                           value="{{ old('heure_fin', $evenementAcademique->heure_fin?->format('H:i')) }}">
                                    @error('heure_fin')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Section Détails supplémentaires -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-edit"></i>
                                Détails supplémentaires
                            </div>
                            
                            <div class="form-group-moderne">
                                <label for="lieu" class="form-label-moderne">Lieu</label>
                                <input type="text" name="lieu" id="lieu"
                                       class="form-input-moderne @error('lieu') is-invalid @enderror"
                                       value="{{ old('lieu', $evenementAcademique->lieu) }}" placeholder="Salle, amphithéâtre, etc.">
                                @error('lieu')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="notes" class="form-label-moderne">Notes et observations</label>
                                <textarea name="notes" id="notes" rows="3"
                                          class="form-input-moderne @error('notes') is-invalid @enderror" 
                                          placeholder="Informations complémentaires, consignes particulières...">{{ old('notes', $evenementAcademique->notes) }}</textarea>
                                @error('notes')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="event-form-sidebar">
                        <!-- Configuration -->
                        <div class="form-section-title">
                            <i class="fas fa-cogs"></i>
                            Configuration
                        </div>
                        
                        <div class="form-group-moderne">
                            <label for="type" class="form-label-moderne">
                                Type d'événement <span class="text-danger">*</span>
                            </label>
                            <select name="type" id="type" 
                                    class="form-select-moderne @error('type') is-invalid @enderror" required>
                                <option value="">Choisir un type</option>
                                @foreach(\App\Models\ESBTPEvenementAcademique::TYPES as $key => $label)
                                    <option value="{{ $key }}" 
                                            {{ old('type', $evenementAcademique->type) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group-moderne">
                            <label for="icone" class="form-label-moderne">
                                Icône <span class="text-danger">*</span>
                            </label>
                            <select name="icone" id="icone" 
                                    class="form-select-moderne @error('icone') is-invalid @enderror" required>
                                <option value="">Choisir une icône</option>
                                @foreach(\App\Models\ESBTPEvenementAcademique::ICONES_TYPES as $type => $icone)
                                    <option value="{{ $icone }}" 
                                            {{ old('icone', $evenementAcademique->icone) == $icone ? 'selected' : '' }}>
                                        {{ \App\Models\ESBTPEvenementAcademique::TYPES[$type] ?? $type }}
                                    </option>
                                @endforeach
                            </select>
                            @error('icone')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group-moderne">
                            <label for="couleur" class="form-label-moderne">
                                Couleur <span class="text-danger">*</span>
                            </label>
                            <select name="couleur" id="couleur" 
                                    class="form-select-moderne @error('couleur') is-invalid @enderror" required>
                                <option value="">Choisir une couleur</option>
                                @foreach(\App\Models\ESBTPEvenementAcademique::COULEURS as $key => $label)
                                    <option value="{{ $key }}" 
                                            {{ old('couleur', $evenementAcademique->couleur) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('couleur')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group-moderne">
                            <label for="statut" class="form-label-moderne">
                                Statut <span class="text-danger">*</span>
                            </label>
                            <select name="statut" id="statut" 
                                    class="form-select-moderne @error('statut') is-invalid @enderror" required>
                                @foreach(\App\Models\ESBTPEvenementAcademique::STATUTS as $key => $label)
                                    <option value="{{ $key }}" 
                                            {{ old('statut', $evenementAcademique->statut) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('statut')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group-moderne">
                            <label class="form-label-moderne">Options d'affichage</label>
                            <div class="form-check-moderne">
                                <input type="checkbox" name="afficher_calendrier" id="afficher_calendrier"
                                       value="1" {{ old('afficher_calendrier', $evenementAcademique->afficher_calendrier) ? 'checked' : '' }}>
                                <label for="afficher_calendrier" class="form-check-label-moderne">
                                    Afficher dans le calendrier
                                </label>
                            </div>
                            <div class="form-check-moderne">
                                <input type="checkbox" name="afficher_timeline" id="afficher_timeline"
                                       value="1" {{ old('afficher_timeline', $evenementAcademique->afficher_timeline) ? 'checked' : '' }}>
                                <label for="afficher_timeline" class="form-check-label-moderne">
                                    Afficher dans la timeline
                                </label>
                            </div>
                        </div>

                        <div class="form-group-moderne">
                            <label class="form-label-moderne">Notifications</label>
                            <div class="form-check-moderne">
                                <input type="checkbox" name="notification_active" id="notification_active"
                                       value="1" {{ old('notification_active', $evenementAcademique->notification_active) ? 'checked' : '' }}>
                                <label for="notification_active" class="form-check-label-moderne">
                                    Activer les notifications
                                </label>
                            </div>
                            <div class="form-group-moderne" id="notification_days_group" style="display: none; margin-top: var(--space-sm);">
                                <label for="jours_notification" class="form-label-moderne">Jours avant notification</label>
                                <input type="number" name="jours_notification" id="jours_notification"
                                       class="form-input-moderne" min="1" max="30"
                                       value="{{ old('jours_notification', $evenementAcademique->jours_notification ?? 7) }}">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="event-form-footer">
                    <div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Les champs marqués d'un * sont obligatoires
                        </small>
                    </div>
                    <div class="event-form-actions">
                        <a href="{{ route('esbtp.evenements-academiques.index') }}" class="btn-acasi secondary">
                            <i class="fas fa-times me-2"></i>
                            Annuler
                        </a>
                        <button type="submit" class="btn-acasi primary">
                            <i class="fas fa-save me-2"></i>
                            Mettre à jour l'événement
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-sélection de l'icône et couleur selon le type
    $('#type').change(function() {
        const type = $(this).val();
        if (type) {
            const icones = @json(\App\Models\ESBTPEvenementAcademique::ICONES_TYPES);
            const couleurs = @json(\App\Models\ESBTPEvenementAcademique::COULEURS_TYPES);
            
            if (icones[type]) {
                $('#icone').val(icones[type]);
            }
            if (couleurs[type]) {
                $('#couleur').val(couleurs[type]);
            }
        }
    });

    // Afficher/masquer le champ jours de notification
    $('#notification_active').change(function() {
        if ($(this).is(':checked')) {
            $('#notification_days_group').show();
        } else {
            $('#notification_days_group').hide();
        }
    });

    // Validation des dates
    $('#date_debut, #date_fin').change(function() {
        const dateDebut = $('#date_debut').val();
        const dateFin = $('#date_fin').val();
        
        if (dateDebut && dateFin && dateFin < dateDebut) {
            alert('La date de fin doit être postérieure à la date de début');
            $('#date_fin').val('');
        }
    });

    // Validation des heures
    $('#heure_debut, #heure_fin').change(function() {
        const heureDebut = $('#heure_debut').val();
        const heureFin = $('#heure_fin').val();
        
        if (heureDebut && heureFin && heureFin <= heureDebut) {
            alert('L\'heure de fin doit être postérieure à l\'heure de début');
            $('#heure_fin').val('');
        }
    });

    // Initialiser l'affichage des notifications
    $('#notification_active').trigger('change');
});
</script>
@endpush