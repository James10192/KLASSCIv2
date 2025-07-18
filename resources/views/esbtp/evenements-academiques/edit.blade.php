@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .form-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: var(--space-xl);
        margin-top: var(--space-md);
    }
    
    .form-main {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
    }
    
    .form-sidebar {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
        padding: var(--space-lg);
        background: var(--background);
        border-radius: var(--radius-medium);
        height: fit-content;
    }
    
    .form-group-moderne {
        margin-bottom: var(--space-lg);
    }
    
    .form-group-moderne:last-child {
        margin-bottom: 0;
    }
    
    .form-label-moderne {
        font-size: var(--text-small);
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: var(--space-xs);
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-select-moderne,
    .form-input-moderne {
        padding: var(--space-sm) var(--space-md);
        border: 1px solid #e5e7eb;
        border-radius: var(--radius-small);
        font-size: var(--text-normal);
        background: var(--surface);
        transition: all 0.2s ease;
        width: 100%;
        font-family: var(--font-family);
    }
    
    .form-input-moderne[type="date"],
    .form-input-moderne[type="time"] {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }
    
    textarea.form-input-moderne {
        resize: vertical;
        min-height: 80px;
        line-height: 1.5;
    }
    
    .form-select-moderne:focus,
    .form-input-moderne:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }
    
    .form-input-moderne:invalid {
        border-color: var(--danger);
    }
    
    .form-input-moderne:invalid:focus {
        border-color: var(--danger);
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
    
    .form-dates-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-md);
    }
    
    .form-footer {
        display: flex;
        gap: var(--space-md);
        justify-content: flex-end;
        padding-top: var(--space-lg);
        border-top: 1px solid #e5e7eb;
        margin-top: var(--space-lg);
    }
    
    .form-check-moderne {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        margin-bottom: var(--space-sm);
    }
    
    .form-check-moderne input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: var(--primary);
    }
    
    .form-check-moderne label {
        font-size: var(--text-normal);
        color: var(--text-primary);
        cursor: pointer;
    }
    
    .text-danger {
        color: var(--danger);
    }
    
    .invalid-feedback {
        display: block;
        font-size: var(--text-small);
        color: var(--danger);
        margin-top: var(--space-xs);
    }
    
    .form-text-muted {
        font-size: var(--text-small);
        color: var(--text-muted);
        margin-top: var(--space-xs);
    }
    
    @media (max-width: 768px) {
        .form-layout {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }
        
        .form-dates-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="card-moderne">
            <div class="card-header-moderne">
                <h1 class="section-title">
                    <i class="fas fa-edit me-2"></i>
                    Modifier l'Événement Académique
                </h1>
                <div class="actions-top">
                    <a href="{{ route('esbtp.evenements-academiques.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Retour
                    </a>
                </div>
            </div>
                
            <div class="card-body-moderne">
                <form action="{{ route('esbtp.evenements-academiques.update', $evenementAcademique) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-layout">
                        <div class="form-main">
                            <!-- Informations de base -->
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

                                <div class="row">
                                    <div class="col-md-6">
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
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group-moderne">
                                        <label for="date_fin" class="form-label-moderne">Date de fin (optionnel)</label>
                                            <input type="date" name="date_fin" id="date_fin"
                                                   class="form-input-moderne @error('date_fin') is-invalid @enderror"
                                                   value="{{ old('date_fin', $evenementAcademique->date_fin?->format('Y-m-d')) }}">
                                            <small class="form-text text-muted">
                                                Laissez vide pour un événement d'une journée
                                            </small>
                                            @error('date_fin')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
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
                                <label for="notes" class="form-label-moderne">Notes supplémentaires</label>
                                    <textarea name="notes" id="notes" rows="3"
                                              class="form-input-moderne @error('notes') is-invalid @enderror">{{ old('notes', $evenementAcademique->notes) }}</textarea>
                                    @error('notes')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>
                        
                        <div class="form-sidebar">
                            <!-- Configuration -->
                                <div class="form-group-moderne">
                                <label for="type">
                                        Type d'événement <span class="text-danger">*</span>
                                    </label>
                                    <select name="type" id="type" 
                                            class="form-input-moderne @error('type') is-invalid @enderror" required>
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
                                <label for="icone">
                                        Icône <span class="text-danger">*</span>
                                    </label>
                                    <select name="icone" id="icone" 
                                            class="form-input-moderne @error('icone') is-invalid @enderror" required>
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
                                <label for="couleur">
                                        Couleur <span class="text-danger">*</span>
                                    </label>
                                    <select name="couleur" id="couleur" 
                                            class="form-input-moderne @error('couleur') is-invalid @enderror" required>
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
                                <label for="statut">
                                        Statut <span class="text-danger">*</span>
                                    </label>
                                    <select name="statut" id="statut" 
                                            class="form-input-moderne @error('statut') is-invalid @enderror" required>
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
                                    <label>Options d'affichage</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="afficher_calendrier" id="afficher_calendrier"
                                               class="form-check-input" value="1" 
                                               {{ old('afficher_calendrier', $evenementAcademique->afficher_calendrier) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="afficher_calendrier">
                                            Afficher dans le calendrier
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="afficher_timeline" id="afficher_timeline"
                                               class="form-check-input" value="1"
                                               {{ old('afficher_timeline', $evenementAcademique->afficher_timeline) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="afficher_timeline">
                                            Afficher dans la timeline
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-footer">
                        <button type="submit" class="btn-acasi primary">
                            <i class="fas fa-save me-2"></i>
                            Mettre à jour
                        </button>
                        <a href="{{ route('esbtp.evenements-academiques.index') }}" class="btn-acasi secondary">
                            <i class="fas fa-times me-2"></i>
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
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
            
            if (icones[type] && !$('#icone').val()) {
                $('#icone').val(icones[type]);
            }
            if (couleurs[type] && !$('#couleur').val()) {
                $('#couleur').val(couleurs[type]);
            }
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
});
</script>
@endpush