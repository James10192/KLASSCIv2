@extends('layouts.app')

@section('title', 'Gestion des bulletins - KLASSCI')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Statistiques -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total des bulletins
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Publiés
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['published'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                En attente
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Semestres
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['periodes'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des bulletins -->
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Liste des bulletins</h6>
                    <div>
                        <a href="{{ route('esbtp.bulletins.select') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus-circle me-1"></i>Générer de nouveaux bulletins
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

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Filtres -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Filtres</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('esbtp.bulletins.index') }}" method="GET" id="filter-form">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="classe_id" class="form-label">Classe</label>
                                        <select class="form-select select2" id="classe_id" name="classe_id">
                                            <option value="">Toutes les classes</option>
                                            @foreach($classes as $classe)
                                                <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                                    {{ $classe->name }} ({{ $classe->filiere->name }} - {{ $classe->niveau->name }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="periode_id" class="form-label">Période</label>
                                        <select class="form-select select2" id="periode_id" name="periode_id">
                                            <option value="">Toutes les périodes</option>
                                            @foreach($periodes as $periode)
                                                <option value="{{ $periode->id }}" {{ request('periode_id') == $periode->id ? 'selected' : '' }}>
                                                    {{ $periode->nom }} ({{ $periode->annee_scolaire }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="published" class="form-label">Statut</label>
                                        <select class="form-select" id="published" name="published">
                                            <option value="">Tous les statuts</option>
                                            <option value="1" {{ request('published') === '1' ? 'selected' : '' }}>Publiés</option>
                                            <option value="0" {{ request('published') === '0' ? 'selected' : '' }}>Non publiés</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="search" class="form-label">Recherche</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Nom, matricule...">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('esbtp.bulletins.index') }}" class="btn btn-secondary me-2">Réinitialiser</a>
                                    <button type="submit" class="btn btn-primary">Filtrer</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tableau des bulletins -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="bulletins-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Période</th>
                                    <th>Moyenne</th>
                                    <th>Rang</th>
                                    <th>Statut</th>
                                    <th>Date de génération</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bulletins as $bulletin)
                                    <tr>
                                        <td>{{ $bulletin->id }}</td>
                                        <td>
                                            <div>{{ $bulletin->etudiant->nom }} {{ $bulletin->etudiant->prenom }}</div>
                                            <small class="text-muted">{{ $bulletin->etudiant->matricule }}</small>
                                        </td>
                                        <td>{{ $bulletin->classe->name }}</td>
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
                                        <td>
                                            @if($bulletin->moyenne_generale !== null)
                                                <span class="badge {{ $bulletin->moyenne_generale >= 10 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ number_format($bulletin->moyenne_generale, 2) }}/20
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">Non calculée</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($bulletin->rang)
                                                {{ $bulletin->rang }}<sup>{{ $bulletin->rang == 1 ? 'er' : 'ème' }}</sup> / {{ $bulletin->total_etudiants }}
                                            @else
                                                <span class="badge bg-secondary">Non classé</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($bulletin->is_published)
                                                <span class="badge bg-success">Publié</span>
                                            @else
                                                <span class="badge bg-warning">Non publié</span>
                                            @endif
                                        </td>
                                        <td>{{ date('d/m/Y', strtotime($bulletin->created_at)) }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('esbtp.bulletins.show', $bulletin) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.bulletins.edit', $bulletin) }}" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('esbtp.bulletins.download', $bulletin) }}" class="btn btn-sm btn-secondary" target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce bulletin ?')) document.getElementById('delete-form-{{ $bulletin->id }}').submit();">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <form id="delete-form-{{ $bulletin->id }}" action="{{ route('esbtp.bulletins.destroy', $bulletin) }}" method="POST" style="display: none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Aucun bulletin trouvé.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $bulletins->appends(request()->query())->links() }}
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
        // Initialisation de Select2
        $('.select2').select2({
            theme: 'bootstrap-5'
        });

        // Soumission automatique du formulaire lors du changement d'un filtre
        $('#classe_id, #periode_id, #published').change(function() {
            $('#filter-form').submit();
        });
    });
</script>
@endsection
