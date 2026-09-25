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
                            <tbody id="bpa-tbody">
                                @forelse($bulletins as $bulletin)
                                    @include('esbtp.bulletins._ligne-attente')
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Aucun bulletin en attente trouvé</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-liste-infinie :paginateur="$bulletins" cible="#bpa-tbody" libelle="bulletins" />
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
