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

                        <div class="form-group row mb-3">
                            <label class="col-sm-3 col-form-label">Classe</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $attendance->seanceCours->emploiTemps->classe->name }}" disabled>
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-3 col-form-label">Matière</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $attendance->seanceCours->matiere->name }}" disabled>
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-3 col-form-label">Séance</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $attendance->seanceCours->jour }} - {{ $attendance->seanceCours->plage_horaire }}" disabled>
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-3 col-form-label">Date</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $attendance->date->format('d/m/Y') }}" disabled>
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-3 col-form-label">Statut</label>
                            <div class="col-sm-9">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="statut" id="present" value="present" {{ $attendance->statut == 'present' ? 'checked' : '' }}>
                                    <label class="form-check-label text-success" for="present">Présent</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="statut" id="absent" value="absent" {{ $attendance->statut == 'absent' ? 'checked' : '' }}>
                                    <label class="form-check-label text-danger" for="absent">Absent</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="statut" id="retard" value="retard" {{ $attendance->statut == 'retard' ? 'checked' : '' }}>
                                    <label class="form-check-label text-warning" for="retard">En retard</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="statut" id="excuse" value="excuse" {{ $attendance->statut == 'excuse' ? 'checked' : '' }}>
                                    <label class="form-check-label text-info" for="excuse">Excusé</label>
                                </div>
                                @error('statut')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="commentaire" class="col-sm-3 col-form-label">Commentaire</label>
                            <div class="col-sm-9">
                                <textarea name="commentaire" id="commentaire" class="form-control" rows="3" placeholder="Commentaire (optionnel)">{{ $attendance->commentaire }}</textarea>
                                @error('commentaire')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-gradient-primary">
                                <i class="mdi mdi-content-save"></i> Enregistrer les modifications
                            </button>
                            <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-light">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Informations sur l'étudiant</h4>

                    <div class="text-center mb-4">
                        @if($attendance->etudiant->photo)
                            <img src="{{ asset('storage/' . $attendance->etudiant->photo) }}" alt="Photo de l'étudiant" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        @else
                            <img src="{{ asset('assets/images/avatar.jpg') }}" alt="Photo par défaut" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        @endif
                    </div>

                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Matricule</span>
                            <span class="badge badge-primary">{{ $attendance->etudiant->matricule }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Téléphone</span>
                            <span>{{ $attendance->etudiant->telephone }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Email</span>
                            <span>{{ $attendance->etudiant->email_personnel }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
