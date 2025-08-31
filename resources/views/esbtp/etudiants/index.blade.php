@extends('layouts.app')

@section('title', 'Gestion des étudiants - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Étudiants</h1>
                <p class="header-subtitle">Gestion des étudiants de l'établissement</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Ajouter un étudiant
                </a>
                @if(auth()->user()->hasRole(['superAdmin', 'secretaire', 'coordinateur']))
                <a href="{{ route('esbtp.reinscription.index') }}" class="btn-acasi success">
                    <i class="fas fa-user-graduate"></i>Réinscriptions
                </a>
                @endif
            </div>
        </div>

        <div class="card-moderne">
            <div class="p-lg">
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


                <!-- Filtres de recherche -->
                <div class="section-title mb-md">
                    <i class="fas fa-filter me-2"></i>Filtres de recherche
                </div>
                            <form method="GET" action="{{ route('esbtp.etudiants.index') }}" id="search-form">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="search" class="form-label">Recherche</label>
                                        <input type="text" class="form-control search-bar" id="search" name="search" value="{{ $search ?? '' }}" placeholder="Matricule, nom, prénom, téléphone...">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="filiere" class="form-label">Filière</label>
                                        <select class="form-select year-selector" id="filiere" name="filiere">
                                            <option value="">Toutes les filières</option>
                                            @foreach($filieres as $f)
                                                <option value="{{ $f->id }}" {{ isset($filiere) && $filiere == $f->id ? 'selected' : '' }}>
                                                    {{ $f->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="niveau" class="form-label">Niveau d'études</label>
                                        <select class="form-select year-selector" id="niveau" name="niveau">
                                            <option value="">Tous les niveaux</option>
                                            @foreach($niveaux as $n)
                                                <option value="{{ $n->id }}" {{ isset($niveau) && $niveau == $n->id ? 'selected' : '' }}>
                                                    {{ $n->name }} ({{ $n->type }} - Année {{ $n->year }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="annee" class="form-label">Année universitaire</label>
                                        <select class="form-select year-selector" id="annee" name="annee">
                                            <option value="">Toutes les années</option>
                                            @foreach($annees as $a)
                                                <option value="{{ $a->id }}" {{ isset($annee) && $annee == $a->id ? 'selected' : '' }}>
                                                    {{ $a->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="status" class="form-label">Statut</label>
                                        <select class="form-select year-selector" id="status" name="status">
                                            <option value="">Tous les statuts</option>
                                            <option value="actif" {{ isset($status) && $status == 'actif' ? 'selected' : '' }}>Actif</option>
                                            <option value="inactif" {{ isset($status) && $status == 'inactif' ? 'selected' : '' }}>Inactif</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end mb-3">
                                        <button type="submit" class="btn-acasi primary me-2">
                                            <i class="fas fa-search"></i>Filtrer
                                        </button>
                                        <a href="{{ route('esbtp.etudiants.index') }}" class="btn-acasi secondary">
                                            <i class="fas fa-redo-alt"></i>Réinitialiser
                                        </a>
                                    </div>
                                </div>
                            </form>
            </div>
        </div>

        <!-- Tableau des étudiants -->
        <div class="card-moderne">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-list"></i>Liste des étudiants
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-primary text-white">
                                <tr>
                                    <th>Matricule</th>
                                    <th>Photo</th>
                                    <th>Nom complet</th>
                                    <th>Genre</th>
                                    <th>Contact</th>
                                    <th>Résidence</th>
                                    <th>Classe actuelle</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($etudiants as $etudiant)
                                    @php $pendingInscription = $etudiant->pending_inscriptions->first(); @endphp
                                    <tr @if($pendingInscription) class="table-warning" @endif>
                                        <td>{{ $etudiant->matricule }}</td>
                                        <td class="text-center">
                                            @if($etudiant->photo_url)
                                                <img src="{{ $etudiant->photo_url }}" alt="Photo" class="img-thumbnail rounded-circle shadow" style="width: 50px; height: 50px; object-fit: cover;">
                                            @else
                                                <div class="bg-light d-flex align-items-center justify-content-center rounded-circle shadow" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user text-secondary"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                                            @if($pendingInscription)
                                                <span class="badge bg-warning text-dark ms-2">Inscription en attente</span>
                                            @endif
                                        </td>
                                        <td>{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</td>
                                        <td>
                                            {{ $etudiant->telephone }}<br>
                                            <small>{{ $etudiant->email }}</small>
                                        </td>
                                        <td>
                                            @if($etudiant->ville || $etudiant->commune)
                                                {{ $etudiant->ville }} {{ $etudiant->commune ? ', '.$etudiant->commune : '' }}
                                            @else
                                                <span class="text-muted">Non renseignée</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($etudiant->inscriptions->count() > 0)
                                                <?php $derniere = $etudiant->inscriptions->sortByDesc('created_at')->first(); ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        {{ $derniere->classe ? $derniere->classe->name : 'Non assigné' }}
                                                        <br>
                                                        <small>
                                                            {{ $derniere->filiere ? $derniere->filiere->name : '' }}
                                                            {{ $derniere->niveau ? ' - '.$derniere->niveau->name : '' }}
                                                        </small>
                                                    </div>
                                                    @if($derniere->status == 'pending' || $derniere->status == 'en_attente')
                                                        <div class="ms-2" title="Inscription en attente de validation">
                                                            <i class="fas fa-hourglass-half text-warning"></i>
                                                        </div>
                                                    @elseif($derniere->status == 'active')
                                                        <div class="ms-2" title="Inscription validée">
                                                            <i class="fas fa-check-circle text-success"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">Non inscrit</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($etudiant->statut == 'actif')
                                                <span class="badge bg-success px-3 py-2">Actif</span>
                                            @else
                                                <span class="badge bg-danger px-3 py-2">Inactif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('esbtp.etudiants.show', $etudiant) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.etudiants.edit', $etudiant) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($pendingInscription)
                                                    @can('inscriptions.validate')
                                                    <button type="button" class="btn btn-success btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" data-bs-toggle="modal" data-bs-target="#validationModal{{ $pendingInscription->id }}" title="Valider l'inscription">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <!-- Modal de validation -->
                                                    @includeIf('esbtp.etudiants._validation_modal', ['pendingInscription' => $pendingInscription, 'etudiant' => $etudiant])
                                                    @endcan
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Aucun étudiant trouvé</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $etudiants->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialisation de Select2 pour les filtres si disponible
        if (typeof $.fn.select2 !== 'undefined') {
            $('#filiere, #niveau, #annee, #status').select2({
                theme: 'bootstrap4',
                placeholder: 'Sélectionner une option',
                allowClear: true
            });
        }

        // Initialisation de DataTables avec pagination côté serveur désactivée
        $('.datatable').DataTable({
            "paging": false,
            "ordering": true,
            "info": false,
            "searching": false,
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/French.json"
            }
        });
    });
</script>
@endsection
