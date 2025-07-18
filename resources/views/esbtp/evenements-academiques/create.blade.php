@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>
                        Créer un Événement Académique
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.evenements-academiques.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Retour
                        </a>
                    </div>
                </div>
                
                <form action="{{ route('esbtp.evenements-academiques.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <!-- Informations de base -->
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="annee_universitaire_id">
                                        Année Universitaire <span class="text-danger">*</span>
                                    </label>
                                    <select name="annee_universitaire_id" id="annee_universitaire_id" 
                                            class="form-control @error('annee_universitaire_id') is-invalid @enderror" required>
                                        <option value="">Sélectionner une année</option>
                                        @foreach($annees as $annee)
                                            <option value="{{ $annee->id }}" 
                                                    {{ old('annee_universitaire_id', $anneeSelectionnee?->id) == $annee->id ? 'selected' : '' }}>
                                                {{ $annee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('annee_universitaire_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="titre">
                                        Titre <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="titre" id="titre" 
                                           class="form-control @error('titre') is-invalid @enderror"
                                           value="{{ old('titre') }}" required>
                                    @error('titre')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="description">
                                        Description <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="description" id="description" rows="4"
                                              class="form-control @error('description') is-invalid @enderror" 
                                              required>{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="date_debut">
                                                Date de début <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" name="date_debut" id="date_debut"
                                                   class="form-control @error('date_debut') is-invalid @enderror"
                                                   value="{{ old('date_debut') }}" required>
                                            @error('date_debut')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="date_fin">Date de fin (optionnel)</label>
                                            <input type="date" name="date_fin" id="date_fin"
                                                   class="form-control @error('date_fin') is-invalid @enderror"
                                                   value="{{ old('date_fin') }}">
                                            <small class="form-text text-muted">
                                                Laissez vide pour un événement d'une journée
                                            </small>
                                            @error('date_fin')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="heure_debut">Heure de début (optionnel)</label>
                                            <input type="time" name="heure_debut" id="heure_debut"
                                                   class="form-control @error('heure_debut') is-invalid @enderror"
                                                   value="{{ old('heure_debut') }}">
                                            @error('heure_debut')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="heure_fin">Heure de fin (optionnel)</label>
                                            <input type="time" name="heure_fin" id="heure_fin"
                                                   class="form-control @error('heure_fin') is-invalid @enderror"
                                                   value="{{ old('heure_fin') }}">
                                            @error('heure_fin')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="lieu">Lieu</label>
                                    <input type="text" name="lieu" id="lieu"
                                           class="form-control @error('lieu') is-invalid @enderror"
                                           value="{{ old('lieu') }}" placeholder="Salle, amphithéâtre, etc.">
                                    @error('lieu')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="notes">Notes supplémentaires</label>
                                    <textarea name="notes" id="notes" rows="3"
                                              class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Configuration -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="type">
                                        Type d'événement <span class="text-danger">*</span>
                                    </label>
                                    <select name="type" id="type" 
                                            class="form-control @error('type') is-invalid @enderror" required>
                                        <option value="">Choisir un type</option>
                                        @foreach(\App\Models\ESBTPEvenementAcademique::TYPES as $key => $label)
                                            <option value="{{ $key }}" 
                                                    {{ old('type') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="icone">
                                        Icône <span class="text-danger">*</span>
                                    </label>
                                    <select name="icone" id="icone" 
                                            class="form-control @error('icone') is-invalid @enderror" required>
                                        <option value="">Choisir une icône</option>
                                        @foreach(\App\Models\ESBTPEvenementAcademique::ICONES_TYPES as $type => $icone)
                                            <option value="{{ $icone }}" 
                                                    {{ old('icone') == $icone ? 'selected' : '' }}>
                                                {{ \App\Models\ESBTPEvenementAcademique::TYPES[$type] ?? $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('icone')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="couleur">
                                        Couleur <span class="text-danger">*</span>
                                    </label>
                                    <select name="couleur" id="couleur" 
                                            class="form-control @error('couleur') is-invalid @enderror" required>
                                        <option value="">Choisir une couleur</option>
                                        @foreach(\App\Models\ESBTPEvenementAcademique::COULEURS as $key => $label)
                                            <option value="{{ $key }}" 
                                                    {{ old('couleur') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('couleur')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Options d'affichage</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="afficher_calendrier" id="afficher_calendrier"
                                               class="form-check-input" value="1" 
                                               {{ old('afficher_calendrier', 1) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="afficher_calendrier">
                                            Afficher dans le calendrier
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="afficher_timeline" id="afficher_timeline"
                                               class="form-check-input" value="1"
                                               {{ old('afficher_timeline', 1) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="afficher_timeline">
                                            Afficher dans la timeline
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Notifications</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="notification_active" id="notification_active"
                                               class="form-check-input" value="1"
                                               {{ old('notification_active') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notification_active">
                                            Activer les notifications
                                        </label>
                                    </div>
                                    <div class="form-group mt-2" id="notification_days_group" style="display: none;">
                                        <label for="jours_notification">Jours avant notification</label>
                                        <input type="number" name="jours_notification" id="jours_notification"
                                               class="form-control" min="1" max="30"
                                               value="{{ old('jours_notification', 7) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Créer l'événement
                        </button>
                        <a href="{{ route('esbtp.evenements-academiques.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>
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
            // Mettre à jour l'icône automatiquement
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