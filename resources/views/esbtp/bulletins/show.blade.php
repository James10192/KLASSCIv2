@extends('layouts.app')

@section('title', 'Bulletin de ' . $bulletin->etudiant->nom . ' ' . $bulletin->etudiant->prenom . ' - KLASSCI')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Bulletin de {{ $bulletin->etudiant->nom }} {{ $bulletin->etudiant->prenom }}</h5>
                    <div>
                        <a href="{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $bulletin->etudiant_id, 'classe_id' => $bulletin->classe_id, 'periode' => $bulletin->periode, 'annee_universitaire_id' => $bulletin->annee_universitaire_id]) }}" class="btn btn-secondary me-2" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>Télécharger PDF
                        </a>
                        @if(auth()->check() && auth()->user() && auth()->user()->hasRole('superAdmin'))
                        <a href="{{ route('esbtp.bulletins.config-matieres', ['bulletin' => $bulletin->etudiant_id, 'classe_id' => $bulletin->classe_id, 'periode' => $bulletin->periode, 'annee_universitaire_id' => $bulletin->annee_universitaire_id]) }}" class="btn btn-info me-2">
                            <i class="fas fa-cog me-1"></i>Configurer Matières
                        </a>
                        @endif
                        <a href="{{ route('esbtp.bulletins.edit', $bulletin) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </a>
                        <a href="{{ route('esbtp.bulletins.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <!-- Informations sur l'étudiant -->
                        <div class="col-md-6">
                            <div class="card">
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
                                                <th>Nom</th>
                                                <td>{{ $bulletin->etudiant->nom }}</td>
                                            </tr>
                                            <tr>
                                                <th>Prénom</th>
                                                <td>{{ $bulletin->etudiant->prenom }}</td>
                                            </tr>
                                            <tr>
                                                <th>Date de naissance</th>
                                                <td>{{ $bulletin->etudiant->date_naissance ? date('d/m/Y', strtotime($bulletin->etudiant->date_naissance)) : 'Non renseignée' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Classe</th>
                                                <td>{{ $bulletin->classe->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>Filière</th>
                                                <td>{{ $bulletin->classe->filiere->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>Niveau d'étude</th>
                                                <td>{{ $bulletin->classe->niveau->name }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Informations sur le bulletin -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">Informations sur le bulletin</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th width="35%">Période</th>
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
                                            <tr>
                                                <th>Année scolaire</th>
                                                <td>{{ $bulletin->anneeUniversitaire ? $bulletin->anneeUniversitaire->annee_debut . '-' . $bulletin->anneeUniversitaire->annee_fin : date('Y') . '-' . (date('Y') + 1) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Moyenne générale</th>
                                                <td>
                                                    @if($bulletin->moyenne_generale !== null)
                                                        <span class="badge {{ $bulletin->moyenne_generale >= 10 ? 'bg-success' : 'bg-danger' }} p-2">
                                                            {{ number_format($bulletin->moyenne_generale, 2) }}/20
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary p-2">Non calculée</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Rang</th>
                                                <td>
                                                    @if($bulletin->rang)
                                                        {{ $bulletin->rang }}<sup>{{ $bulletin->rang == 1 ? 'er' : 'ème' }}</sup> / {{ $bulletin->total_etudiants }}
                                                    @else
                                                        <span class="badge bg-secondary">Non classé</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Résultat</th>
                                                <td>
                                                    @if($bulletin->moyenne_generale !== null)
                                                        @if($bulletin->moyenne_generale >= 12)
                                                            <span class="badge bg-success p-2">Très bien</span>
                                                        @elseif($bulletin->moyenne_generale >= 10)
                                                            <span class="badge bg-primary p-2">Passable</span>
                                                        @elseif($bulletin->moyenne_generale >= 8)
                                                            <span class="badge bg-warning p-2">Insuffisant</span>
                                                        @else
                                                            <span class="badge bg-danger p-2">Faible</span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-secondary p-2">Non évalué</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Statut</th>
                                                <td>
                                                    @if($bulletin->is_published)
                                                        <span class="badge bg-success p-2">Publié</span>
                                                    @else
                                                        <span class="badge bg-warning p-2">Non publié</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Date de génération</th>
                                                <td>{{ date('d/m/Y H:i', strtotime($bulletin->created_at)) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Résultats par matière -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Résultats par matière</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Code</th>
                                            <th>Matière</th>
                                            <th>Enseignant</th>
                                            <th>Coefficient</th>
                                            <th>Moyenne</th>
                                            <th>Mention</th>
                                            <th>Appréciation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($bulletin->resultats as $resultat)
                                            <tr>
                                                <td>{{ $resultat->matiere->code }}</td>
                                                <td>{{ $resultat->matiere->name }}</td>
                                                <td>
                                                    @if($resultat->matiere->enseignants->count() > 0)
                                                        {{ $resultat->matiere->enseignants->first()->nom }} {{ $resultat->matiere->enseignants->first()->prenom }}
                                                    @else
                                                        <span class="text-muted">Non assigné</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $resultat->coefficient }}</td>
                                                <td class="text-center">
                                                    <span class="badge {{ $resultat->moyenne >= 10 ? 'bg-success' : 'bg-danger' }} p-2">
                                                        {{ number_format($resultat->moyenne, 2) }}/20
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @if($resultat->moyenne >= 16)
                                                        <span class="badge bg-success">Excellent</span>
                                                    @elseif($resultat->moyenne >= 14)
                                                        <span class="badge bg-info">Très bien</span>
                                                    @elseif($resultat->moyenne >= 12)
                                                        <span class="badge bg-primary">Bien</span>
                                                    @elseif($resultat->moyenne >= 10)
                                                        <span class="badge bg-secondary">Passable</span>
                                                    @elseif($resultat->moyenne >= 8)
                                                        <span class="badge bg-warning">Insuffisant</span>
                                                    @else
                                                        <span class="badge bg-danger">Faible</span>
                                                    @endif
                                                </td>
                                                <td>{{ $resultat->commentaire ?? 'Aucune appréciation' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">Aucun résultat disponible pour cette période.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Commentaires et observations -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Assiduité</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">Absences justifiées</th>
                                            <td>{{ $bulletin->absences_justifiees ?? 0 }} heures</td>
                                        </tr>
                                        <tr>
                                            <th>Absences non justifiées</th>
                                            <td>{{ $bulletin->absences_non_justifiees ?? 0 }} heures</td>
                                        </tr>
                                        <tr>
                                            <th>Retards</th>
                                            <td>{{ $bulletin->retards ?? 0 }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">Appréciation générale</h6>
                                </div>
                                <div class="card-body">
                                    @if($bulletin->appreciation_generale)
                                        <p class="mb-0">{{ $bulletin->appreciation_generale }}</p>
                                    @else
                                        <p class="text-muted mb-0">Aucune appréciation générale</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Signatures du bulletin -->
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Signatures du bulletin</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header {{ $bulletin->signature_directeur ? 'bg-success' : 'bg-secondary' }} text-white">
                                            <h6 class="mb-0">Directeur</h6>
                                        </div>
                                        <div class="card-body text-center">
                                            @if($bulletin->signature_directeur)
                                                <div class="mb-2">
                                                    <i class="fas fa-check-circle text-success fa-3x"></i>
                                                </div>
                                                <p>Signé le {{ date('d/m/Y à H:i', strtotime($bulletin->date_signature_directeur)) }}</p>
                                            @else
                                                <div class="mb-2">
                                                    <i class="fas fa-times-circle text-danger fa-3x"></i>
                                                </div>
                                                <p>Non signé</p>
                                                <form action="{{ route('esbtp.bulletins.signer', ['bulletin' => $bulletin, 'role' => 'directeur']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-signature me-1"></i>Signer
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header {{ $bulletin->signature_responsable ? 'bg-success' : 'bg-secondary' }} text-white">
                                            <h6 class="mb-0">Responsable pédagogique</h6>
                                        </div>
                                        <div class="card-body text-center">
                                            @if($bulletin->signature_responsable)
                                                <div class="mb-2">
                                                    <i class="fas fa-check-circle text-success fa-3x"></i>
                                                </div>
                                                <p>Signé le {{ date('d/m/Y à H:i', strtotime($bulletin->date_signature_responsable)) }}</p>
                                            @else
                                                <div class="mb-2">
                                                    <i class="fas fa-times-circle text-danger fa-3x"></i>
                                                </div>
                                                <p>Non signé</p>
                                                <form action="{{ route('esbtp.bulletins.signer', ['bulletin' => $bulletin, 'role' => 'responsable']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-signature me-1"></i>Signer
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header {{ $bulletin->signature_parent ? 'bg-success' : 'bg-secondary' }} text-white">
                                            <h6 class="mb-0">Parent/Tuteur</h6>
                                        </div>
                                        <div class="card-body text-center">
                                            @if($bulletin->signature_parent)
                                                <div class="mb-2">
                                                    <i class="fas fa-check-circle text-success fa-3x"></i>
                                                </div>
                                                <p>Signé le {{ date('d/m/Y à H:i', strtotime($bulletin->date_signature_parent)) }}</p>
                                            @else
                                                <div class="mb-2">
                                                    <i class="fas fa-times-circle text-danger fa-3x"></i>
                                                </div>
                                                <p>Non signé</p>
                                                <form action="{{ route('esbtp.bulletins.signer', ['bulletin' => $bulletin, 'role' => 'parent']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-signature me-1"></i>Signer
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash me-1"></i>Supprimer ce bulletin
                        </button>

                        <form action="{{ route('esbtp.bulletins.toggle-publication', $bulletin) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @if($bulletin->is_published)
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-eye-slash me-1"></i>Dépublier ce bulletin
                                </button>
                            @else
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-eye me-1"></i>Publier ce bulletin
                                </button>
                            @endif
                        </form>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="d-flex justify-content-between mt-3">
                        <div>
                            <a href="{{ route('esbtp.bulletins.edit', $bulletin) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            @if(auth()->check() && auth()->user() && auth()->user()->hasRole('superAdmin'))
                                <a href="{{ route('esbtp.bulletins.migrate-resultats-to-details', $bulletin) }}" class="btn btn-info ml-2">
                                    <i class="fas fa-sync"></i> Migrer résultats vers détails
                                </a>
                                <a href="{{ route('esbtp.bulletins.edit-professeurs') }}?bulletin={{ $bulletin->etudiant_id }}&classe_id={{ $bulletin->classe_id }}&periode={{ $bulletin->periode }}&annee_universitaire_id={{ $bulletin->annee_universitaire_id }}" class="btn btn-info ml-2">
                                    <i class="fas fa-chalkboard-teacher"></i> Éditer professeurs
                                </a>
                                <a href="{{ route('esbtp.bulletins.config-matieres') }}?classe_id={{ $bulletin->classe_id }}&periode={{ $bulletin->periode }}&annee_universitaire_id={{ $bulletin->annee_universitaire_id }}&bulletin_id={{ $bulletin->id }}&bulletin={{ $bulletin->etudiant_id }}" class="btn btn-warning ml-2">
                                    <i class="fas fa-cogs"></i> Configurer matières
                                </a>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('esbtp.bulletins.pdf', $bulletin) }}" class="btn btn-success" target="_blank">
                                <i class="fas fa-file-pdf"></i> Générer PDF
                            </a>
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
