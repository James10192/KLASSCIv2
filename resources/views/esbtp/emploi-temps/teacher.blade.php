@extends('layouts.app')

@section('title', 'Mon emploi du temps')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Mon emploi du temps</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Mon emploi du temps</li>
    </ol>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-calendar-alt me-1"></i>
                            Emploi du temps hebdomadaire
                        </div>
                        <div>
                            <span class="badge bg-primary">{{ Auth::user()->name }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Emploi du temps hebdomadaire -->
                    <div class="table-responsive">
                        <table class="table table-bordered text-center">
                            <thead class="bg-light">
                                <tr>
                                    <th width="14%">Horaire</th>
                                    @foreach($joursSemaine as $index => $jour)
                                        <th width="12%" style="background-color: {{ $index == (Carbon\Carbon::now()->dayOfWeek ?: 7) ? 'rgba(99, 102, 241, 0.1)' : '' }}">{{ $jour }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Définir les créneaux horaires (de 8h à 18h par tranches de 2h)
                                    $creneaux = [
                                        ['08:00', '10:00'],
                                        ['10:00', '12:00'],
                                        ['12:00', '14:00'],
                                        ['14:00', '16:00'],
                                        ['16:00', '18:00'],
                                        ['18:00', '20:00'],
                                    ];
                                @endphp

                                @foreach($creneaux as $creneau)
                                    <tr>
                                        <td class="align-middle fw-semibold">
                                            {{ $creneau[0] }} - {{ $creneau[1] }}
                                        </td>
                                        
                                        @foreach($joursSemaine as $jour_index => $jour)
                                            <td class="{{ $jour_index == (Carbon\Carbon::now()->dayOfWeek ?: 7) ? 'bg-light' : '' }}">
                                                @php
                                                    // Trouver les séances qui correspondent à ce créneau pour ce jour
                                                    $seancesDuCreneau = $timetable[$jour_index]->filter(function($seance) use ($creneau) {
                                                        $heureDebut = Carbon\Carbon::parse($seance->heure_debut)->format('H:i');
                                                        $heureFin = Carbon\Carbon::parse($seance->heure_fin)->format('H:i');
                                                        
                                                        return ($heureDebut >= $creneau[0] && $heureDebut < $creneau[1]) || 
                                                               ($heureFin > $creneau[0] && $heureFin <= $creneau[1]) ||
                                                               ($heureDebut <= $creneau[0] && $heureFin >= $creneau[1]);
                                                    });
                                                @endphp

                                                @if($seancesDuCreneau->isNotEmpty())
                                                    @foreach($seancesDuCreneau as $seance)
                                                        <div class="course-card p-2 mb-1 rounded"
                                                            style="background-color: rgba(4,83,203,.06); border-left: 3px solid rgba(4,83,203,.3)">
                                                            <div class="course-title fw-semibold">
                                                                {{ $seance->matiere->name ?? 'Matière non définie' }}
                                                            </div>
                                                            <div class="course-details small">
                                                                @php $ts = $seance->type_seance instanceof \App\Enums\TypeSeance ? $seance->type_seance : \App\Enums\TypeSeance::fromLegacy($seance->getRawOriginal('type_seance')); @endphp
                                                                <span class="badge" style="{{ $ts->badgeInlineStyle() }}" title="{{ $ts->label() }}">
                                                                    <i class="fas {{ $ts->badgeIcon() }} me-1"></i>{{ $ts->value }}
                                                                </span>
                                                                <div class="mt-1">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    {{ Carbon\Carbon::parse($seance->heure_debut)->format('H:i') }} - 
                                                                    {{ Carbon\Carbon::parse($seance->heure_fin)->format('H:i') }}
                                                                </div>
                                                                <div class="mt-1">
                                                                    <i class="fas fa-users me-1"></i>
                                                                    {{ $seance->emploiTemps->classe->name ?? 'Classe non définie' }}
                                                                </div>
                                                                <div class="mt-1">
                                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                                    {{ $seance->salle ?? 'Salle non définie' }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="text-muted">-</div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Légende -->
                    <div class="mt-3">
                        <h6>Légende</h6>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-1">COURS</span>
                                <span class="small">Cours magistral</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-secondary me-1">TD</span>
                                <span class="small">Travaux dirigés</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-1">TP</span>
                                <span class="small">Travaux pratiques</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-danger me-1">EXAMEN</span>
                                <span class="small">Examen</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .course-card {
        border-left: 4px solid var(--nextadmin-primary);
        transition: all 0.3s ease;
    }
    
    .course-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    
    .table th, .table td {
        vertical-align: middle;
    }
    
    .table th {
        font-weight: 600;
    }
    
    /* Surligner le jour actuel */
    .today-highlight {
        background-color: rgba(99, 102, 241, 0.1);
    }
</style>
@endsection 