@extends('layouts.app')

@section('title', 'Modifier la présence')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-calendar-check me-2"></i>Modifier la présence</h1>
                <p class="header-subtitle">Modification de la présence de {{ $attendance->etudiant->nom_complet }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.attendances.show', $attendance) }}" class="btn-acasi secondary me-2">
                    <i class="fas fa-eye"></i>Voir les détails
                </a>
                <a href="{{ route('esbtp.attendances.index') }}" class="btn-acasi primary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-edit"></i>
                            Modifier la présence
                        </div>
                        <div class="main-card-subtitle">{{ $attendance->etudiant->nom_complet }}</div>
                    </div>
                    <div class="main-card-body">
                        <form action="{{ route('esbtp.attendances.update', $attendance) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="form-group-moderne">
                                <label class="form-label-moderne">
                                    <i class="fas fa-user"></i>
                                    Étudiant
                                </label>
                                <input type="text" class="form-input-moderne" value="{{ $attendance->etudiant->nom_complet }}" disabled>
                            </div>

                            <div class="form-group-moderne">
                                <label class="form-label-moderne">
                                    <i class="fas fa-users"></i>
                                    Classe
                                </label>
                                <input type="text" class="form-input-moderne" value="{{ $attendance->seanceCours->emploiTemps->classe->name }}" disabled>
                            </div>

                            <div class="form-group-moderne">
                                <label class="form-label-moderne">
                                    <i class="fas fa-book"></i>
                                    Matière
                                </label>
                                <input type="text" class="form-input-moderne" value="{{ $attendance->seanceCours->matiere->name }}" disabled>
                            </div>

                            <div class="form-group-moderne">
                                <label class="form-label-moderne">
                                    <i class="fas fa-clock"></i>
                                    Séance
                                </label>
                                <input type="text" class="form-input-moderne" value="{{ $attendance->seanceCours->jour }} - {{ $attendance->seanceCours->plage_horaire }}" disabled>
                            </div>

                            <div class="form-group-moderne">
                                <label class="form-label-moderne">
                                    <i class="fas fa-calendar"></i>
                                    Date
                                </label>
                                <input type="text" class="form-input-moderne" value="{{ $attendance->date->format('d/m/Y') }}" disabled>
                            </div>

                            <div class="form-group-moderne">
                                <label class="form-label-moderne">
                                    <i class="fas fa-check-circle"></i>
                                    Statut
                                </label>
                                <div class="status-radio-group">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="statut" id="present" value="present"
                                            {{ old('statut', $attendance->statut) == 'present' ? 'checked' : '' }} required>
                                        <label class="form-check-label status-label-success" for="present">
                                            <i class="fas fa-check-circle"></i> Présent
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="statut" id="absent" value="absent"
                                            {{ old('statut', $attendance->statut) == 'absent' ? 'checked' : '' }} required>
                                        <label class="form-check-label status-label-danger" for="absent">
                                            <i class="fas fa-times-circle"></i> Absent
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="statut" id="retard" value="retard"
                                            {{ old('statut', $attendance->statut) == 'retard' || old('statut', $attendance->statut) == 'late' ? 'checked' : '' }} required>
                                        <label class="form-check-label status-label-warning" for="retard">
                                            <i class="fas fa-exclamation-circle"></i> En retard
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="statut" id="excuse" value="excuse"
                                            {{ old('statut', $attendance->statut) == 'excuse' ? 'checked' : '' }} required>
                                        <label class="form-check-label status-label-info" for="excuse">
                                            <i class="fas fa-info-circle"></i> Excusé
                                        </label>
                                    </div>
                                </div>
                                @error('statut')
                                    <div class="text-danger mt-2">
                                        <i class="fas fa-exclamation-triangle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label class="form-label-moderne" for="commentaire">
                                    <i class="fas fa-comment"></i>
                                    Commentaire
                                </label>
                                <textarea name="commentaire" id="commentaire" class="form-input-moderne" rows="3"
                                    placeholder="Ajoutez un commentaire (optionnel)...">{{ old('commentaire', $attendance->commentaire) }}</textarea>
                                @error('commentaire')
                                    <div class="text-danger mt-2">
                                        <i class="fas fa-exclamation-triangle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-acasi primary">
                                    <i class="fas fa-save"></i>
                                    <span>Enregistrer les modifications</span>
                                </button>
                                <a href="{{ route('esbtp.attendances.index') }}" class="btn-acasi secondary">
                                    <i class="fas fa-times"></i>
                                    <span>Annuler</span>
                                </a>
                            </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="main-card">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-user-circle"></i>
                        Informations sur l'étudiant
                    </div>
                </div>
                <div class="main-card-body">
                    <div class="student-photo-section" style="text-align: center; margin-bottom: 1.5rem;">
                        @if($attendance->etudiant->photo)
                            <img src="{{ asset('storage/' . $attendance->etudiant->photo) }}"
                                 alt="Photo de {{ $attendance->etudiant->nom_complet }}"
                                 class="student-photo"
                                 style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary); box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                        @else
                            <div class="student-photo-placeholder" style="width: 150px; height: 150px; margin: 0 auto; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent-blue)); display: flex; align-items: center; justify-content: center; border: 4px solid var(--primary); box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                <i class="fas fa-user" style="font-size: 4rem; color: white;"></i>
                            </div>
                        @endif
                    </div>

                    <div class="student-info-list">
                        <div class="info-item" style="padding: 1rem; margin-bottom: 0.75rem; background: var(--light-bg); border-radius: 8px; border-left: 4px solid var(--primary);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--text-muted); font-weight: 500;">
                                    <i class="fas fa-id-card" style="margin-right: 0.5rem; color: var(--primary);"></i>
                                    Matricule
                                </span>
                                <span class="status-badge-primary" style="background: var(--primary); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-weight: 600; font-size: 0.875rem;">
                                    {{ $attendance->etudiant->matricule }}
                                </span>
                            </div>
                        </div>

                        <div class="info-item" style="padding: 1rem; margin-bottom: 0.75rem; background: var(--light-bg); border-radius: 8px; border-left: 4px solid var(--accent-blue);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--text-muted); font-weight: 500;">
                                    <i class="fas fa-phone" style="margin-right: 0.5rem; color: var(--accent-blue);"></i>
                                    Téléphone
                                </span>
                                <span style="color: var(--text-primary); font-weight: 500;">
                                    {{ $attendance->etudiant->telephone ?? 'Non renseigné' }}
                                </span>
                            </div>
                        </div>

                        <div class="info-item" style="padding: 1rem; background: var(--light-bg); border-radius: 8px; border-left: 4px solid var(--success);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--text-muted); font-weight: 500;">
                                    <i class="fas fa-envelope" style="margin-right: 0.5rem; color: var(--success);"></i>
                                    Email
                                </span>
                                <span style="color: var(--text-primary); font-weight: 500; font-size: 0.875rem;">
                                    {{ $attendance->etudiant->email_personnel ?? 'Non renseigné' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
