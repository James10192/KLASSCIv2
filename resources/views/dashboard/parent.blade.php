@extends('layouts.app')

@section('title', 'Tableau de bord parent | ESBTP-yAKRO')

@section('content')
<div class="container-fluid px-4">
    <h1 class="my-4">Bienvenue, {{ $user->name }}</h1>
    <p class="text-muted">Récapitulatif des informations concernant vos enfants</p>

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

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Mes enfants</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalEtudiants }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <a href="{{ route('parent.etudiants.index') }}" class="btn btn-sm btn-primary mt-3">Voir mes enfants</a>
                </div>
            </div>
        </div>

        @if(isset($unreadNotifications))
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Notifications non lues</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $unreadNotifications }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bell fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <a href="{{ route('parent.notifications.index') }}" class="btn btn-sm btn-warning mt-3">Voir toutes les notifications</a>
                </div>
            </div>
        </div>
        @endif

        @if(isset($recentAbsences) && $recentAbsences->count() > 0)
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Absences récentes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $recentAbsences->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <a href="{{ route('parent.attendances.index') }}" class="btn btn-sm btn-danger mt-3">Voir les présences</a>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Liste des enfants -->
    @if(isset($etudiants) && $etudiants->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Mes enfants</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Matricule</th>
                                    <th>Classe</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($etudiants as $etudiant)
                                <tr>
                                    <td>{{ $etudiant->nom }}</td>
                                    <td>{{ $etudiant->prenoms }}</td>
                                    <td>{{ $etudiant->matricule }}</td>
                                    <td>{{ $etudiant->classe->nom ?? 'Non assigné' }}</td>
                                    <td>
                                        <a href="{{ route('parent.etudiants.show', $etudiant->id) }}" class="btn btn-sm btn-info">Détails</a>
                                        <a href="{{ route('parent.bulletins.index', ['etudiant_id' => $etudiant->id]) }}" class="btn btn-sm btn-primary">Bulletins</a>
                                        <a href="{{ route('parent.attendances.show', $etudiant->id) }}" class="btn btn-sm btn-warning">Présences</a>
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
    @endif

    <!-- Bulletins récents -->
    @if(isset($recentBulletins) && $recentBulletins->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Bulletins récents</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Période</th>
                                    <th>Moyenne</th>
                                    <th>Rang</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentBulletins as $bulletin)
                                <tr>
                                    <td>{{ $bulletin->etudiant->nom }} {{ $bulletin->etudiant->prenom }}</td>
                                    <td>{{ $bulletin->classe->nom ?? 'N/A' }}</td>
                                    <td>{{ $bulletin->periode }}</td>
                                    <td>{{ number_format($bulletin->moyenne_generale, 2) }}/20</td>
                                    <td>{{ $bulletin->rang ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('parent.bulletins.show', $bulletin->id) }}" class="btn btn-sm btn-info">Voir</a>
                                        <a href="{{ route('parent.bulletins.download', $bulletin->id) }}" class="btn btn-sm btn-danger">PDF</a>
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
    @endif

    <!-- Absences récentes -->
    @if(isset($recentAbsences) && $recentAbsences->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Absences récentes</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Date</th>
                                    <th>Cours</th>
                                    <th>Justifié</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentAbsences as $absence)
                                <tr>
                                    <td>{{ $absence->etudiant->nom }} {{ $absence->etudiant->prenom }}</td>
                                    <td>{{ $absence->date->format('d/m/Y') }}</td>
                                    <td>{{ $absence->matiere->nom ?? 'N/A' }}</td>
                                    <td>
                                        @if($absence->justified)
                                            <span class="badge bg-success">Oui</span>
                                        @else
                                            <span class="badge bg-danger">Non</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$absence->justified)
                                            <a href="{{ route('parent.attendances.justify', $absence->id) }}" class="btn btn-sm btn-warning">Justifier</a>
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
    </div>
    @endif

    <!-- Messages récents -->
    @if(isset($recentMessages) && $recentMessages->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Messages récents</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach($recentMessages as $message)
                        <a href="{{ route('parent.messages.show', $message->id) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">{{ $message->subject }}</h5>
                                <small>{{ $message->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-1">{{ Str::limit($message->content, 100) }}</p>
                            <small>De: {{ $message->sender->name ?? 'Système' }}</small>
                        </a>
                        @endforeach
                    </div>
                    <div class="text-center mt-3">
                        <a href="{{ route('parent.messages.index') }}" class="btn btn-primary">Voir tous les messages</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    // Graphiques et scripts spécifiques au tableau de bord
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser les tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
<style>
    .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .bg-light-green {
        background-color: rgba(25, 135, 84, 0.1);
    }

    .bg-light-orange {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .bg-light-blue {
        background-color: rgba(13, 110, 253, 0.1);
    }

    .bg-light-primary {
        background-color: rgba(4, 83, 203, 0.1);
    }

    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transition: box-shadow 0.3s ease-in-out;
    }
</style>
@endsection
