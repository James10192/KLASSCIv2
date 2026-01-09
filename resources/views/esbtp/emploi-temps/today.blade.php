@extends('layouts.app')

@section('title', 'Emplois du temps d\'aujourd\'hui - ' . $jourActuelTexte . ' - KLASSCI')

@section('styles')
<style>
    .session-card {
        border-left: 4px solid #3498db;
        transition: transform 0.2s;
    }

    .session-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .session-time {
        font-weight: bold;
        color: #2c3e50;
    }

    .session-matiere {
        font-weight: bold;
        font-size: 1.1rem;
    }

    .session-enseignant {
        color: #7f8c8d;
    }

    .session-salle {
        background-color: #f1f5f9;
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 0.9rem;
    }

    .classe-header {
        background-color: #f8f9fa;
        border-left: 4px solid #2ecc71;
        padding: 10px 15px;
        margin-bottom: 15px;
        border-radius: 4px;
    }

    .no-sessions {
        padding: 30px;
        text-align: center;
        background-color: #f8f9fa;
        border-radius: 8px;
        margin: 20px 0;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day me-2"></i>
                        Emplois du temps d'aujourd'hui - {{ $jourActuelTexte }} ({{ \Carbon\Carbon::parse($dateActuelle)->format('d/m/Y') }})
                    </h5>
                    <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour aux emplois du temps
                    </a>
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

                    <!-- Statistiques -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Séances aujourd'hui</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalSeancesAujourdhui }}</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Classes concernées</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalClassesAujourdhui }}</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($totalSeancesAujourdhui > 0)
                        <!-- Affichage des séances par classe -->
                        @foreach($seancesParClasse as $classe => $seances)
                            <div class="classe-header">
                                <h5 class="mb-0">{{ $classe }}</h5>
                            </div>

                            <div class="row">
                                @foreach($seances as $seance)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card session-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="session-time">
                                                        {{ \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') }} -
                                                        {{ \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') }}
                                                    </span>
                                                    <span class="session-salle">
                                                        <i class="fas fa-door-open me-1"></i>{{ $seance->salle ?? 'Non définie' }}
                                                    </span>
                                                </div>

                                                <h6 class="session-matiere">{{ $seance->matiere->name ?? 'Matière non définie' }}</h6>

                                                <div class="session-enseignant">
                                                    <i class="fas fa-user-tie me-1"></i>
                                                    {{ $seance->enseignantName }}
                                                </div>

                                                @if($seance->description)
                                                    <div class="mt-2 small">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        {{ $seance->description }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        <div class="no-sessions">
                            <i class="fas fa-calendar-times fa-3x mb-3 text-muted"></i>
                            <h4>Aucune séance de cours programmée pour aujourd'hui</h4>
                            <p class="text-muted">Il n'y a pas de cours prévus pour {{ $jourActuelTexte }} {{ \Carbon\Carbon::parse($dateActuelle)->format('d/m/Y') }}.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
