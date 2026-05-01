@extends('layouts.app')

@section('title', 'Gestion des Étudiants et Inscriptions - KLASSCI')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Gestion des Étudiants et Inscriptions</h5>
                    @can('inscriptions.create')
                    <a href="{{ route('esbtp.inscriptions.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i>Ajouter un étudiant
                    </a>
                    @endcan
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Onglets -->
                    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="etudiants-tab" data-bs-toggle="tab" data-bs-target="#etudiants" type="button" role="tab" aria-controls="etudiants" aria-selected="true">
                                <i class="fas fa-user-graduate me-1"></i>Étudiants
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="inscriptions-tab" data-bs-toggle="tab" data-bs-target="#inscriptions" type="button" role="tab" aria-controls="inscriptions" aria-selected="false">
                                <i class="fas fa-user-plus me-1"></i>Inscriptions
                            </button>
                        </li>
                    </ul>

                    <!-- Contenu des onglets -->
                    <div class="tab-content" id="myTabContent">
                        <!-- Onglet Étudiants -->
                        <div class="tab-pane fade show active" id="etudiants" role="tabpanel" aria-labelledby="etudiants-tab">
                            <!-- Filtres de recherche des étudiants -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-filter me-2"></i>Filtres de recherche
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="GET" action="{{ route('esbtp.etudiants-inscriptions.index') }}" id="search-form-etudiants">
                                        <input type="hidden" name="tab" value="etudiants">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="search_etudiants" class="form-label">Recherche</label>
                                                <input type="text" class="form-control" id="search_etudiants" name="search_etudiants" value="{{ $searchEtudiants ?? '' }}" placeholder="Matricule, nom, prénom, téléphone...">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="filiere_etudiants" class="form-label">Filière</label>
                                                <select class="form-select" id="filiere_etudiants" name="filiere_etudiants">
                                                    <option value="">Toutes les filières</option>
                                                    @foreach($filieres as $f)
                                                        <option value="{{ $f->id }}" {{ isset($filiereEtudiants) && $filiereEtudiants == $f->id ? 'selected' : '' }}>
                                                            {{ $f->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="niveau_etudiants" class="form-label">Niveau d'études</label>
                                                <select class="form-select" id="niveau_etudiants" name="niveau_etudiants">
                                                    <option value="">Tous les niveaux</option>
                                                    @foreach($niveaux as $n)
                                                        <option value="{{ $n->id }}" {{ isset($niveauEtudiants) && $niveauEtudiants == $n->id ? 'selected' : '' }}>
                                                            {{ $n->name }} ({{ $n->type }} - Année {{ $n->year }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="annee_etudiants" class="form-label">Année universitaire</label>
                                                <select class="form-select" id="annee_etudiants" name="annee_etudiants">
                                                    <option value="">Toutes les années</option>
                                                    @foreach($annees as $a)
                                                        <option value="{{ $a->id }}" {{ isset($anneeEtudiants) && $anneeEtudiants == $a->id ? 'selected' : '' }}>
                                                            {{ $a->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="status_etudiants" class="form-label">Statut</label>
                                                <select class="form-select" id="status_etudiants" name="status_etudiants">
                                                    <option value="">Tous les statuts</option>
                                                    <option value="actif" {{ isset($statusEtudiants) && $statusEtudiants == 'actif' ? 'selected' : '' }}>Actif</option>
                                                    <option value="inactif" {{ isset($statusEtudiants) && $statusEtudiants == 'inactif' ? 'selected' : '' }}>Inactif</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end mb-3">
                                                <button type="submit" class="btn btn-primary me-2">
                                                    <i class="fas fa-search me-1"></i>Filtrer
                                                </button>
                                                <a href="{{ route('esbtp.etudiants-inscriptions.index') }}?tab=etudiants" class="btn btn-secondary">
                                                    <i class="fas fa-redo-alt me-1"></i>Réinitialiser
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Tableau des étudiants -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped datatable">
                                    <thead>
                                        <tr>
                                            <th>Matricule</th>
                                            <th>Photo</th>
                                            <th>Nom complet</th>
                                            <th>Genre</th>
                                            <th>Contact</th>
                                            <th>Classe actuelle</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($etudiants as $etudiant)
                                            <tr>
                                                <td>{{ $etudiant->matricule }}</td>
                                                <td class="text-center">
                                                    @if($etudiant->photo)
                                                        <img src="{{ asset('storage/photos/etudiants/'.$etudiant->photo) }}" alt="Photo" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                    @else
                                                        <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                            <i class="fas fa-user text-secondary"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</td>
                                                <td>{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</td>
                                                <td>
                                                    {{ $etudiant->telephone }}<br>
                                                    <small>{{ $etudiant->email }}</small>
                                                </td>
                                                <td>
                                                    @if($etudiant->inscriptions->count() > 0)
                                                        <?php $derniere = $etudiant->inscriptions->sortByDesc('created_at')->first(); ?>
                                                        {{ $derniere->classe ? $derniere->classe->name : 'Non assigné' }}
                                                        <br>
                                                        <small>
                                                            {{ $derniere->filiere ? $derniere->filiere->name : '' }}
                                                            {{ $derniere->niveau ? ' - '.$derniere->niveau->name : '' }}
                                                        </small>
                                                    @else
                                                        <span class="text-muted">Non inscrit</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($etudiant->statut == 'actif')
                                                        <span class="badge bg-success">Actif</span>
                                                    @else
                                                        <span class="badge bg-danger">Inactif</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('esbtp.etudiants.show', $etudiant) }}" class="btn btn-sm btn-info" title="Voir les détails">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('esbtp.etudiants.edit', $etudiant) }}" class="btn btn-sm btn-warning" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $etudiant->id }}" title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Modal de suppression -->
                                                    <div class="modal fade" id="deleteModal{{ $etudiant->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $etudiant->id }}" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel{{ $etudiant->id }}">Confirmation de suppression</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Êtes-vous sûr de vouloir supprimer l'étudiant <strong>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</strong> ({{ $etudiant->matricule }}) ?</p>
                                                                    <p class="text-danger"><strong>Attention :</strong> Cette action est irréversible et supprimera également toutes les inscriptions et données associées à cet étudiant.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                    <form action="{{ route('esbtp.etudiants.destroy', $etudiant) }}" method="POST" class="d-inline">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">Aucun étudiant trouvé.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                {{ $etudiants->appends(['tab' => 'etudiants', 'search_etudiants' => $searchEtudiants, 'filiere_etudiants' => $filiereEtudiants, 'niveau_etudiants' => $niveauEtudiants, 'annee_etudiants' => $anneeEtudiants, 'status_etudiants' => $statusEtudiants])->links() }}
                            </div>
                        </div>

                        <!-- Onglet Inscriptions -->
                        <div class="tab-pane fade" id="inscriptions" role="tabpanel" aria-labelledby="inscriptions-tab">
                            <!-- Filtres de recherche des inscriptions -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-filter me-2"></i>Filtres de recherche
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="GET" action="{{ route('esbtp.etudiants-inscriptions.index') }}" id="search-form-inscriptions">
                                        <input type="hidden" name="tab" value="inscriptions">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="search_inscriptions">Recherche par nom ou matricule</label>
                                                <input type="text" class="form-control" id="search_inscriptions" name="search_inscriptions" value="{{ $searchInscriptions ?? '' }}">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="filiere_inscriptions">Filière</label>
                                                <select class="form-select" id="filiere_inscriptions" name="filiere_inscriptions">
                                                    <option value="">Toutes les filières</option>
                                                    @foreach($filieres as $fil)
                                                        <option value="{{ $fil->id }}" {{ isset($filiereInscriptions) && $filiereInscriptions == $fil->id ? 'selected' : '' }}>
                                                            {{ $fil->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="niveau_inscriptions">Niveau d'études</label>
                                                <select class="form-select" id="niveau_inscriptions" name="niveau_inscriptions">
                                                    <option value="">Tous les niveaux</option>
                                                    @foreach($niveaux as $niv)
                                                        <option value="{{ $niv->id }}" {{ isset($niveauInscriptions) && $niveauInscriptions == $niv->id ? 'selected' : '' }}>
                                                            {{ $niv->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="annee_inscriptions">Année universitaire</label>
                                                <select class="form-select" id="annee_inscriptions" name="annee_inscriptions">
                                                    <option value="">Toutes les années</option>
                                                    @foreach($annees as $an)
                                                        <option value="{{ $an->id }}" {{ isset($anneeInscriptions) && $anneeInscriptions == $an->id ? 'selected' : '' }}>
                                                            {{ $an->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="status_inscriptions">Statut</label>
                                                <select class="form-select" id="status_inscriptions" name="status_inscriptions">
                                                    <option value="active" {{ isset($statusInscriptions) && $statusInscriptions == 'active' ? 'selected' : '' }}>Actives</option>
                                                    <option value="all" {{ isset($statusInscriptions) && $statusInscriptions == 'all' ? 'selected' : '' }}>Toutes</option>
                                                    <option value="pending" {{ isset($statusInscriptions) && $statusInscriptions == 'pending' ? 'selected' : '' }}>En attente</option>
                                                    <option value="validated" {{ isset($statusInscriptions) && $statusInscriptions == 'validated' ? 'selected' : '' }}>Validées</option>
                                                    <option value="cancelled" {{ isset($statusInscriptions) && $statusInscriptions == 'cancelled' ? 'selected' : '' }}>Annulées</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1 mb-3 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary">Filtrer</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Tableau des inscriptions -->
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>N° Inscription</th>
                                            <th>Matricule</th>
                                            <th>Étudiant</th>
                                            <th>Filière</th>
                                            <th>Niveau</th>
                                            <th>Année Universitaire</th>
                                            <th>Statut</th>
                                            <th>Date d'inscription</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($inscriptions as $inscription)
                                        <tr>
                                            <td>{{ $inscription->numero_inscription }}</td>
                                            <td>{{ $inscription->etudiant->matricule ?? 'N/A' }}</td>
                                            <td>{{ $inscription->etudiant->nom ?? '' }} {{ $inscription->etudiant->prenoms ?? '' }}</td>
                                            <td>{{ $inscription->filiere->name ?? 'N/A' }}</td>
                                            <td>{{ $inscription->niveau->name ?? 'N/A' }}</td>
                                            <td>{{ $inscription->anneeUniversitaire->name ?? 'N/A' }}</td>
                                            <td>
                                                @if($inscription->status == 'pending')
                                                    <span class="badge bg-warning">En attente</span>
                                                @elseif($inscription->status == 'validated')
                                                    <span class="badge bg-success">Validée</span>
                                                @elseif($inscription->status == 'cancelled')
                                                    <span class="badge bg-danger">Annulée</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $inscription->status }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $inscription->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    @can('inscriptions.view')
                                                    <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" class="btn btn-info btn-sm" title="Détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @endcan

                                                    @can('inscriptions.edit')
                                                    @if($inscription->status == 'pending')
                                                    <a href="{{ route('esbtp.inscriptions.edit', $inscription->id) }}" class="btn btn-primary btn-sm" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @endif
                                                    @endcan

                                                    @if($inscription->status == 'pending')
                                                        @can('inscriptions.validate')
                                                        <button type="button" class="btn btn-success btn-sm valider-btn"
                                                                data-id="{{ $inscription->id }}" title="Valider l'inscription">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <form id="valider-form-{{ $inscription->id }}" action="{{ route('esbtp.inscriptions.valider', $inscription->id) }}" method="POST" style="display: none;">
                                                            @csrf
                                                            @method('PUT')
                                                        </form>
                                                        @endcan
                                                    @endif

                                                    @if($inscription->status == 'pending')
                                                        @can('inscriptions.cancel')
                                                        <button type="button" class="btn btn-warning btn-sm annuler-btn"
                                                                data-id="{{ $inscription->id }}" data-bs-toggle="modal"
                                                                data-bs-target="#annulerModal{{ $inscription->id }}" title="Annuler l'inscription">
                                                            <i class="fas fa-times"></i>
                                                        </button>

                                                        <!-- Modal d'annulation -->
                                                        <div class="modal fade" id="annulerModal{{ $inscription->id }}" tabindex="-1" role="dialog" aria-labelledby="annulerModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="annulerModalLabel">Annulation d'inscription</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p>Êtes-vous sûr de vouloir annuler l'inscription de <strong>{{ $inscription->etudiant->nom ?? '' }} {{ $inscription->etudiant->prenoms ?? '' }}</strong> ?</p>
                                                                        <form action="{{ route('esbtp.inscriptions.annuler', $inscription->id) }}" method="POST">
                                                                            @csrf
                                                                            @method('PUT')
                                                                            <div class="form-group">
                                                                                <label for="motif">Motif d'annulation</label>
                                                                                <textarea class="form-control" id="motif" name="motif" rows="3" required></textarea>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                            <button type="submit" class="btn btn-warning">Confirmer l'annulation</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endcan
                                                    @endif

                                                    @can('inscriptions.delete')
                                                    <button type="button" class="btn btn-danger btn-sm delete-btn"
                                                            data-id="{{ $inscription->id }}" data-bs-toggle="modal"
                                                            data-bs-target="#deleteModalInscription{{ $inscription->id }}" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>

                                                    <!-- Modal de suppression -->
                                                    <div class="modal fade" id="deleteModalInscription{{ $inscription->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel">Confirmation de suppression</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Êtes-vous sûr de vouloir supprimer cette inscription ?</p>
                                                                    <p><strong>Étudiant:</strong> {{ $inscription->etudiant->nom ?? '' }} {{ $inscription->etudiant->prenoms ?? '' }}</p>
                                                                    <p><strong>Filière:</strong> {{ $inscription->filiere->name ?? 'N/A' }}</p>
                                                                    <p><strong>Niveau:</strong> {{ $inscription->niveau->name ?? 'N/A' }}</p>
                                                                    <p class="text-danger"><strong>Attention:</strong> Cette action est irréversible.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                    <form action="{{ route('esbtp.inscriptions.destroy', $inscription->id) }}" method="POST">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                {{ $inscriptions->appends(['tab' => 'inscriptions', 'search_inscriptions' => $searchInscriptions, 'filiere_inscriptions' => $filiereInscriptions, 'niveau_inscriptions' => $niveauInscriptions, 'annee_inscriptions' => $anneeInscriptions, 'status_inscriptions' => $statusInscriptions])->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des onglets avec l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');

        if (tab === 'inscriptions') {
            const inscriptionsTab = document.getElementById('inscriptions-tab');
            if (inscriptionsTab) {
                inscriptionsTab.click();
            }
        }

        // Gestion des boutons de validation d'inscription
        const validerBtns = document.querySelectorAll('.valider-btn');
        validerBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                if (confirm('Êtes-vous sûr de vouloir valider cette inscription ?')) {
                    document.getElementById('valider-form-' + id).submit();
                }
            });
        });
    });
</script>
@endpush
