@extends('layouts.app')

@section('title', 'Bulletins en attente - KLASSCI')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Statistiques -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Bulletins non publiés
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalPending }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Bulletins publiés non signés
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalNonSigned }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-signature fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des bulletins en attente -->
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Bulletins en attente de traitement</h6>
                    <div>
                        <a href="{{ route('esbtp.bulletins.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la liste complète
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

                    <!-- Tableau des bulletins en attente -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="bulletins-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Période</th>
                                    <th>Moyenne</th>
                                    <th>Statut</th>
                                    <th>Signatures</th>
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
                                                    {{ number_format($bulletin->moyenne_generale, 2) }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">Non calculée</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($bulletin->is_published)
                                                <span class="badge bg-success">Publié</span>
                                            @else
                                                <span class="badge bg-warning">Non publié</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <span class="badge {{ $bulletin->signature_directeur ? 'bg-success' : 'bg-danger' }}" title="Directeur">
                                                    <i class="fas {{ $bulletin->signature_directeur ? 'fa-check' : 'fa-times' }}"></i> Dir
                                                </span>
                                                <span class="badge {{ $bulletin->signature_responsable ? 'bg-success' : 'bg-danger' }}" title="Responsable">
                                                    <i class="fas {{ $bulletin->signature_responsable ? 'fa-check' : 'fa-times' }}"></i> Resp
                                                </span>
                                                <span class="badge {{ $bulletin->signature_parent ? 'bg-success' : 'bg-danger' }}" title="Parent">
                                                    <i class="fas {{ $bulletin->signature_parent ? 'fa-check' : 'fa-times' }}"></i> Par
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('esbtp.bulletins.show', $bulletin) }}" class="btn btn-sm btn-info" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.bulletins.edit', $bulletin) }}" class="btn btn-sm btn-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @php $_pdfParams = ['bulletin' => $bulletin->etudiant_id, 'classe_id' => $bulletin->classe_id, 'periode' => $bulletin->periode, 'annee_universitaire_id' => $bulletin->annee_universitaire_id]; @endphp
                                                <a href="{{ route('esbtp.bulletins.pdf-params-preview', $_pdfParams) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Aperçu PDF">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.bulletins.pdf-params', $_pdfParams) }}" class="btn btn-sm btn-secondary" target="_blank" title="Télécharger PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>

                                                @if(!$bulletin->is_published)
                                                <form action="{{ route('esbtp.bulletins.toggle-publication', $bulletin) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-success" title="Publier">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                </form>
                                                @endif

                                                @if(!$bulletin->signature_responsable)
                                                <form action="{{ route('esbtp.bulletins.signer', ['bulletin' => $bulletin, 'role' => 'responsable']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary" title="Signer (Responsable)">
                                                        <i class="fas fa-signature"></i> R
                                                    </button>
                                                </form>
                                                @endif

                                                @if(!$bulletin->signature_directeur)
                                                <form action="{{ route('esbtp.bulletins.signer', ['bulletin' => $bulletin, 'role' => 'directeur']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary" title="Signer (Directeur)">
                                                        <i class="fas fa-signature"></i> D
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Aucun bulletin en attente trouvé</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $bulletins->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialisation des tooltips
        $('[title]').tooltip();
    });
</script>
@endpush
