@extends('layouts.app')

@section('title', 'Modification du bulletin de ' . $bulletin->etudiant->nom . ' ' . $bulletin->etudiant->prenom . ' - KLASSCI')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Modification du bulletin de {{ $bulletin->etudiant->nom }} {{ $bulletin->etudiant->prenom }}</h5>
                    <div>
                        <a href="{{ route('esbtp.bulletins.show', $bulletin) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye me-1"></i>Voir le bulletin
                        </a>
                        <a href="{{ route('esbtp.bulletins.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <!-- Informations sur l'étudiant -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">Informations sur l'étudiant</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th width="35%">Matricule</th>
                                                <td>{{ $bulletin->etudiant->matricule }}</td>
                                            </tr>
                                            <tr>
                                                <th>Nom complet</th>
                                                <td>{{ $bulletin->etudiant->nom }} {{ $bulletin->etudiant->prenom }}</td>
                                            </tr>
                                            <tr>
                                                <th>Classe</th>
                                                <td>{{ $bulletin->classe->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>Période</th>
                                                <td>
                                                    @if($bulletin->periode == 'semestre1')
                                                        Premier Semestre
                                                    @elseif($bulletin->periode == 'semestre2')
                                                        Deuxième Semestre
                                                    @elseif($bulletin->periode == 'annuel')
                                                        Annuel
                                                    @else
                                                        {{ $bulletin->periode }}
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Informations du bulletin -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">Informations générales du bulletin</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('bulletins.update', $bulletin) }}" method="POST">
                                        @csrf
                                        @method('PUT')

                                        <div class="mb-3">
                                            <label for="moyenne_generale" class="form-label">Moyenne générale</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control @error('moyenne_generale') is-invalid @enderror"
                                                       id="moyenne_generale" name="moyenne_generale"
                                                       value="{{ old('moyenne_generale', $bulletin->moyenne_generale) }}"
                                                       step="0.01" min="0" max="20">
                                                <span class="input-group-text">/20</span>
                                                @error('moyenne_generale')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <small class="form-text text-muted">Laissez vide pour un calcul automatique.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="rang" class="form-label">Rang</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control @error('rang') is-invalid @enderror"
                                                       id="rang" name="rang"
                                                       value="{{ old('rang', $bulletin->rang) }}"
                                                       min="1" max="{{ $bulletin->total_etudiants }}">
                                                <span class="input-group-text">/ {{ $bulletin->total_etudiants }}</span>
                                                @error('rang')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <small class="form-text text-muted">Laissez vide pour un classement automatique.</small>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1"
                                                       {{ old('is_published', $bulletin->is_published) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_published">Publier le bulletin</label>
                                                <small class="form-text text-muted d-block">Un bulletin publié est visible par l'étudiant et ses parents.</small>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Mettre à jour les informations générales
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assiduité et appréciation -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Assiduité et appréciation générale</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('bulletins.update', $bulletin) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="absences_justifiees" class="form-label">Absences justifiées (heures)</label>
                                        <input type="number" class="form-control @error('absences_justifiees') is-invalid @enderror"
                                                id="absences_justifiees" name="absences_justifiees"
                                                value="{{ old('absences_justifiees', $bulletin->absences_justifiees) }}"
                                                min="0">
                                        @error('absences_justifiees')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="absences_non_justifiees" class="form-label">Absences non justifiées (heures)</label>
                                        <input type="number" class="form-control @error('absences_non_justifiees') is-invalid @enderror"
                                                id="absences_non_justifiees" name="absences_non_justifiees"
                                                value="{{ old('absences_non_justifiees', $bulletin->absences_non_justifiees) }}"
                                                min="0">
                                        @error('absences_non_justifiees')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="retards" class="form-label">Nombre de retards</label>
                                        <input type="number" class="form-control @error('retards') is-invalid @enderror"
                                                id="retards" name="retards"
                                                value="{{ old('retards', $bulletin->retards) }}"
                                                min="0">
                                        @error('retards')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="appreciation_generale" class="form-label">Appréciation générale</label>
                                    <textarea class="form-control @error('appreciation_generale') is-invalid @enderror"
                                              id="appreciation_generale" name="appreciation_generale"
                                              rows="3">{{ old('appreciation_generale', $bulletin->appreciation_generale) }}</textarea>
                                    @error('appreciation_generale')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-info text-white">
                                        <i class="fas fa-save me-1"></i>Mettre à jour l'assiduité et l'appréciation
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Résultats par matière -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">Résultats par matière</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('bulletins.update', $bulletin) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%">ID</th>
                                                <th width="15%">Code</th>
                                                <th width="25%">Matière</th>
                                                <th width="10%">Coefficient</th>
                                                <th width="15%">Moyenne</th>
                                                <th width="30%">Appréciation</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($bulletin->resultats as $resultat)
                                                <tr>
                                                    <td>
                                                        {{ $resultat->id }}
                                                        <input type="hidden" name="resultats[{{ $resultat->id }}][id]" value="{{ $resultat->id }}">
                                                    </td>
                                                    <td>{{ $resultat->matiere->code }}</td>
                                                    <td>{{ $resultat->matiere->name }}</td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm @error('resultats.' . $resultat->id . '.coefficient') is-invalid @enderror"
                                                               name="resultats[{{ $resultat->id }}][coefficient]"
                                                               value="{{ old('resultats.' . $resultat->id . '.coefficient', $resultat->coefficient) }}"
                                                               step="0.1" min="0.1" max="20">
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" class="form-control form-control-sm @error('resultats.' . $resultat->id . '.moyenne') is-invalid @enderror"
                                                                   name="resultats[{{ $resultat->id }}][moyenne]"
                                                                   value="{{ old('resultats.' . $resultat->id . '.moyenne', $resultat->moyenne) }}"
                                                                   step="0.01" min="0" max="20">
                                                            <span class="input-group-text">/20</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm @error('resultats.' . $resultat->id . '.appreciation') is-invalid @enderror"
                                                               name="resultats[{{ $resultat->id }}][appreciation]"
                                                               value="{{ old('resultats.' . $resultat->id . '.appreciation', $resultat->commentaire) }}"
                                                               maxlength="255">
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">Aucun résultat disponible pour cette période.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>Mettre à jour les résultats par matière
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Action supplémentaires -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">Recalcul automatique</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('esbtp.bulletins.recalculer', $bulletin) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <p>Vous pouvez recalculer automatiquement les moyennes et le classement de ce bulletin à partir des notes existantes.</p>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-calculator me-1"></i>Recalculer le bulletin
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0">Supprimer le bulletin</h6>
                                </div>
                                <div class="card-body">
                                    <p>Attention : La suppression d'un bulletin est une action irréversible qui supprimera toutes les données associées.</p>
                                    <div class="d-grid">
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash me-1"></i>Supprimer ce bulletin
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce bulletin ?</p>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention :</strong> Cette action est irréversible et supprimera définitivement ce bulletin ainsi que tous ses détails associés.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('esbtp.bulletins.destroy', $bulletin) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
