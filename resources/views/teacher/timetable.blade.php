@extends('layouts.app')

@section('title', 'Emploi du temps - Enseignant')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Mon emploi du temps</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Emploi du temps</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-calendar-alt me-1"></i>
                Mon emploi du temps
            </div>
            <div>
                <a href="{{ route('teacher.dashboard') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Horaire</th>
                            @foreach($joursSemaine as $jour)
                                <th>{{ $jour }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($creneaux as $creneau)
                            @php
                                [$start, $end] = explode('-', $creneau);
                            @endphp
                            <tr>
                                <td class="fw-bold align-middle" style="font-size:0.95em;">{{ $creneau }}</td>
                                @foreach($joursSemaine as $jourIndex => $jourNom)
                                    @php
                                        $seance = $emploiTempsSemaine[$jourIndex]->first(function($s) use ($start, $end) {
                                            $debut = \Carbon\Carbon::parse($s->heure_debut)->format('H:i');
                                            $fin = \Carbon\Carbon::parse($s->heure_fin)->format('H:i');
                                            return ($start >= $debut && $start < $fin);
                                        });
                                    @endphp
                                    <td class="p-1 align-middle" style="line-height:1.1;">
                                        @if($seance)
                                            <div class="tt-card-cours d-flex flex-column align-items-center justify-content-center h-100" style="min-width:60px; min-height:60px; font-size:0.92em;">
                                                <span class="fw-bold text-primary">{{ $seance->matiere->name ?? 'Matière' }}</span>
                                                <span class="text-muted small">{{ $seance->classe->name ?? '' }}</span>
                                                <span class="badge bg-opacity-10 text-info border border-info mt-1">{{ $seance->salle ?? '' }}</span>
                                            </div>
                                        @else
                                            <div class="tt-card-vide d-flex flex-column align-items-center justify-content-center h-100" style="min-width:60px; min-height:60px; font-size:0.92em;">
                                                <i class="fas fa-coffee fa-sm text-muted mb-1"></i>
                                                <span class="text-muted small">Pause</span>
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .table-responsive { overflow-x: auto; }
    .table thead th {
        position: sticky;
        top: 0;
        background: #f8fafc;
        z-index: 2;
    }
    .table {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(99,102,241,0.07);
        overflow: hidden;
    }
    .tt-card {
        background: #fff;
        border-left: 4px solid var(--nextadmin-primary, #6366f1);
        min-height: 60px;
        min-width: 60px;
        transition: box-shadow 0.2s;
        font-size: 0.92em;
    }
    .tt-card:hover {
        box-shadow: 0 4px 16px rgba(99,102,241,0.12);
    }
    .tt-cours { border-color: #6366f1; background: rgba(99,102,241,0.07); }
    .tt-td { border-color: #22c55e; background: rgba(34,197,94,0.07); }
    .tt-tp { border-color: #f59e0b; background: rgba(245,158,11,0.07); }
    .tt-badge-cours { background: #6366f1; color: #fff; }
    .tt-badge-td { background: #22c55e; color: #fff; }
    .tt-badge-tp { background: #f59e0b; color: #fff; }
    .tt-card-vide {
        background: #f3f4f6;
        border-radius: 8px;
        min-height: 60px;
        min-width: 60px;
        font-size: 0.92em;
    }
    @media (max-width: 991.98px) {
        .table-responsive { font-size: 0.92rem; }
        .tt-card, .tt-card-vide { min-width: 48px; min-height: 48px; font-size: 0.88em; }
    }
</style>
@endsection
