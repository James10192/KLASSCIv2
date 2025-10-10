@extends('layouts.app')

@section('title', 'Mes Bulletins')

@push('styles')
<style>
    .progress {
        background-color: #f8f9fa;
        height: 8px;
    }
    .card-header {
        font-weight: 600;
    }
    .badge-pill {
        padding: 0.35em 0.65em;
        border-radius: 10rem;
    }
    .text-mention {
        font-weight: 600;
        font-size: 0.9rem;
    }

    /* Responsive fixes for mobile (390x844) */
    @media (max-width: 768px) {
        .container-fluid {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            max-width: 100vw;
            overflow-x: hidden;
        }

        * {
            max-width: 100%;
        }

        .d-flex.justify-content-between {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 1rem;
        }

        h1 {
            font-size: 1.5rem !important;
            margin: 1rem 0 !important;
        }

        .breadcrumb {
            font-size: 0.8rem;
        }

        .row {
            margin-left: 0 !important;
            margin-right: 0 !important;
            max-width: 100%;
        }

        .row > [class*='col-'] {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            max-width: 100%;
        }

        .card {
            margin-bottom: 1rem !important;
            max-width: 100%;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }

        .table {
            font-size: 0.8rem;
            min-width: 700px;
        }

        .table th,
        .table td {
            padding: 0.5rem 0.35rem;
            white-space: nowrap;
        }

        .btn-sm {
            font-size: 0.75rem;
            padding: 0.35rem 0.6rem;
        }

        .btn-group {
            flex-direction: column !important;
            gap: 0.25rem;
        }

        .btn-group .btn {
            width: 100%;
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.35em 0.6em;
        }

        .alert {
            font-size: 0.85rem;
            padding: 0.75rem;
        }
    }

    @media (max-width: 400px) {
        .container-fluid {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }

        h1 {
            font-size: 1.3rem !important;
        }

        .table {
            font-size: 0.75rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="my-4">Mes Bulletins</h1>
            <p class="text-muted">Consultez et téléchargez vos bulletins de notes</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mes Bulletins</li>
            </ol>
        </nav>
    </div>

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

    @if($bulletins->isEmpty())
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-file-alt fa-4x text-muted"></i>
                </div>
                <h5 class="fw-bold">Aucun bulletin disponible</h5>
                <p class="text-muted">Vos bulletins apparaîtront ici une fois qu'ils seront publiés par l'administration</p>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2 text-primary"></i>Liste de mes bulletins</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Année Universitaire</th>
                                        <th>Semestre</th>
                                        <th>Classe</th>
                                        <th class="text-center">Moyenne Générale</th>
                                        <th class="text-center">Rang</th>
                                        <th class="text-center">Mention</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bulletins as $bulletin)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 p-2 rounded me-2">
                                                        <i class="fas fa-calendar-alt text-primary"></i>
                                                    </div>
                                                    <span>{{ $bulletin->anneeUniversitaire->annee_debut ?? '' }}-{{ $bulletin->anneeUniversitaire->annee_fin ?? '' }}</span>
                                                </div>
                                            </td>
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
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-info bg-opacity-10 p-2 rounded me-2">
                                                        <i class="fas fa-graduation-cap text-info"></i>
                                                    </div>
                                                    <span>{{ $bulletin->classe->name ?? 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill {{ $bulletin->moyenne_generale >= 10 ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                                                    {{ number_format($bulletin->moyenne_generale, 2) }}/20
                                                </span>
                                                <div class="progress mt-1 mx-auto" style="width: 80%;">
                                                    <div class="progress-bar {{ $bulletin->moyenne_generale >= 10 ? 'bg-success' : 'bg-danger' }}" role="progressbar" style="width: {{ min($bulletin->moyenne_generale * 5, 100) }}%" aria-valuenow="{{ $bulletin->moyenne_generale }}" aria-valuemin="0" aria-valuemax="20"></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill bg-secondary">
                                                    {{ $bulletin->rang ?? 'N/A' }}<small>/{{ $bulletin->effectif_classe ?? 'N/A' }}</small>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if($bulletin->mention)
                                                    @if($bulletin->mention == 'Très Bien' || $bulletin->mention == 'Excellent')
                                                        <span class="badge rounded-pill bg-primary px-3 py-2">{{ $bulletin->mention }}</span>
                                                    @elseif($bulletin->mention == 'Bien')
                                                        <span class="badge rounded-pill bg-info px-3 py-2">{{ $bulletin->mention }}</span>
                                                    @elseif($bulletin->mention == 'Assez Bien')
                                                        <span class="badge rounded-pill bg-success px-3 py-2">{{ $bulletin->mention }}</span>
                                                    @elseif($bulletin->mention == 'Passable')
                                                        <span class="badge rounded-pill bg-warning text-dark px-3 py-2">{{ $bulletin->mention }}</span>
                                                    @else
                                                        <span class="badge rounded-pill bg-danger px-3 py-2">{{ $bulletin->mention }}</span>
                                                    @endif
                                                @else
                                                    <span class="badge rounded-pill bg-secondary px-3 py-2">Non définie</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('esbtp.bulletins.show', $bulletin->id) }}" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye me-1"></i>Voir
                                                    </a>
                                                    <a href="{{ route('esbtp.bulletins.download', $bulletin->id) }}" class="btn btn-sm btn-success">
                                                        <i class="fas fa-download me-1"></i>Télécharger
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques et résumé -->
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Évolution des moyennes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless">
                                <thead>
                                    <tr>
                                        <th>Période</th>
                                        <th class="text-center">Moyenne</th>
                                        <th class="text-center">Progression</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $previousMoyenne = null;
                                        $bulletinsGrouped = $bulletins->sortBy('created_at');
                                    @endphp
                                    @foreach($bulletinsGrouped as $bulletin)
                                        @php
                                            $periodeText = 'N/A';
                                            if($bulletin->periode == 'semestre1') $periodeText = 'Semestre 1';
                                            elseif($bulletin->periode == 'semestre2') $periodeText = 'Semestre 2';
                                            elseif($bulletin->periode == 'annuel') $periodeText = 'Annuel';

                                            $progression = null;
                                            if ($previousMoyenne !== null && $bulletin->moyenne_generale !== null) {
                                                $progression = $bulletin->moyenne_generale - $previousMoyenne;
                                            }
                                            $previousMoyenne = $bulletin->moyenne_generale;
                                        @endphp
                                        <tr>
                                            <td>{{ $periodeText }}</td>
                                            <td class="text-center">{{ number_format($bulletin->moyenne_generale, 2) }}</td>
                                            <td class="text-center">
                                                @if($progression !== null)
                                                    @if($progression > 0)
                                                        <span class="text-success"><i class="fas fa-arrow-up me-1"></i>+{{ number_format($progression, 2) }}</span>
                                                    @elseif($progression < 0)
                                                        <span class="text-danger"><i class="fas fa-arrow-down me-1"></i>{{ number_format($progression, 2) }}</span>
                                                    @else
                                                        <span class="text-secondary"><i class="fas fa-equals me-1"></i>0</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!--<div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Résumé des décisions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @php
                                $latestBulletin = $bulletins->sortByDesc('created_at')->first();
                            @endphp
                            @if($latestBulletin)
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="card-title">Dernier bulletin</h6>
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="me-3">
                                                    <span class="badge rounded-pill {{ $latestBulletin->moyenne_generale >= 10 ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                                                        {{ number_format($latestBulletin->moyenne_generale, 2) }}/20
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="d-block">{{ $latestBulletin->classe->name ?? 'N/A' }}</span>
                                                    <small class="text-muted">
                                                        @if($latestBulletin->periode == 'semestre1')
                                                            Premier Semestre
                                                        @elseif($latestBulletin->periode == 'semestre2')
                                                            Deuxième Semestre
                                                        @elseif($latestBulletin->periode == 'annuel')
                                                            Annuel
                                                        @else
                                                            {{ $latestBulletin->periode }}
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                            <p class="mb-1"><strong>Décision du conseil :</strong> {{ $latestBulletin->decision_conseil ?? 'Non spécifiée' }}</p>
                                            <p class="mb-0"><strong>Appréciation :</strong> {{ $latestBulletin->appreciation_generale ?? 'Non spécifiée' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="card-title">Signatures</h6>
                                            <div class="d-flex flex-column">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span>Directeur</span>
                                                    @if($latestBulletin->signature_directeur)
                                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Signé</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>En attente</span>
                                                    @endif
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span>Responsable pédagogique</span>
                                                    @if($latestBulletin->signature_responsable)
                                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Signé</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>En attente</span>
                                                    @endif
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span>Parent</span>
                                                    @if($latestBulletin->signature_parent)
                                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Signé</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>En attente</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="col-12">
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Aucun bulletin n'est disponible pour afficher un résumé.
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>-->
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Activer les tooltips Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endpush
