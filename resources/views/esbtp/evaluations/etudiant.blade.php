@extends('layouts.app')

@section('title', 'Mes Examens')

@section('page-title', 'Mes Examens')

@section('content')
<div class="container-fluid">
    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('esbtp.mes-evaluations.index') }}" method="GET" class="row">
                <div class="col-md-3 mb-2">
                    <label for="annee_universitaire_id">Année Universitaire</label>
                    <select name="annee_universitaire_id" id="annee_universitaire_id" class="form-control">
                        @foreach($anneesUniversitaires as $annee)
                            <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                                {{ $annee->annee_debut }}-{{ $annee->annee_fin }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="periode">Période</label>
                    <select name="periode" id="periode" class="form-control">
                        <option value="">Toutes les périodes</option>
                        <option value="S1" {{ $periode == 'S1' ? 'selected' : '' }}>Semestre 1</option>
                        <option value="S2" {{ $periode == 'S2' ? 'selected' : '' }}>Semestre 2</option>
                        <option value="Rattrapage" {{ $periode == 'Rattrapage' ? 'selected' : '' }}>Rattrapage</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="statut">Statut</label>
                    <select name="statut" id="statut" class="form-control">
                        <option value="">Tous les examens</option>
                        <option value="passees" {{ $statut == 'passees' ? 'selected' : '' }}>Examens passés</option>
                        <option value="a_venir" {{ $statut == 'a_venir' ? 'selected' : '' }}>Examens à venir</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('esbtp.mes-evaluations.index') }}" class="btn btn-secondary ml-2">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Ma classe</h5>
                    <h2 class="mb-0">{{ $classe->nom }}</h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <small class="text-white">{{ $classe->filiere->nom ?? 'N/A' }}</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Examens passés</h5>
                    <h2 class="mb-0">{{ $evaluationsPassees }}</h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <small class="text-white">Examens déjà effectués</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Examens à venir</h5>
                    <h2 class="mb-0">{{ $evaluationsAVenir }}</h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <small class="text-white">Examens programmés</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Prochain examen</h5>
                    @if($prochaineEvaluation)
                        <p class="mb-0">{{ $prochaineEvaluation->matiere->nom ?? 'N/A' }}</p>
                        <small>{{ $prochaineEvaluation->date_evaluation ? $prochaineEvaluation->date_evaluation->format('d/m/Y H:i') : 'N/A' }}</small>
                    @else
                        <p class="mb-0">Aucun examen programmé</p>
                    @endif
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    @if($prochaineEvaluation)
                        <small class="text-white">Dans {{ now()->diffInDays($prochaineEvaluation->date_evaluation) }} jours</small>
                    @else
                        <small class="text-white">-</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des examens -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i>Liste de mes examens</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Matière</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Durée</th>
                            <th>Salle</th>
                            <th>Surveillance</th>
                            <th>Statut</th>
                            <th>Période</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($evaluations as $evaluation)
                            <tr class="{{ $evaluation->date_evaluation < now() ? 'bg-light' : '' }}">
                                <td>{{ $evaluation->matiere->nom ?? 'N/A' }}</td>
                                <td>{{ $evaluation->type }}</td>
                                <td>{{ $evaluation->date_evaluation ? $evaluation->date_evaluation->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>{{ $evaluation->duree }} min</td>
                                <td>{{ $evaluation->salle }}</td>
                                <td>{{ $evaluation->surveillant }}</td>
                                <td>
                                    @if($evaluation->date_evaluation < now())
                                        <span class="badge badge-success">Passé</span>
                                    @elseif($evaluation->date_evaluation->isToday())
                                        <span class="badge badge-danger">Aujourd'hui</span>
                                    @else
                                        <span class="badge badge-warning">À venir</span>
                                    @endif
                                </td>
                                <td>{{ $evaluation->periode }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Aucun examen trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $evaluations->appends(request()->query())->links() }}
            </div>
        </div>
    </div>

    <!-- Calendrier des examens -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Calendrier de mes examens</h5>
        </div>
        <div class="card-body">
            <div id="calendar-container" style="height: 500px;">
                <!-- Calendrier FullCalendar sera inséré ici via JavaScript -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar-container');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'fr',
            events: [
                @foreach($evaluations as $evaluation)
                {
                    title: '{{ $evaluation->matiere->nom ?? "N/A" }} ({{ $evaluation->type }})',
                    start: '{{ $evaluation->date_evaluation ? $evaluation->date_evaluation->format("Y-m-d\TH:i:s") : "" }}',
                    end: '{{ $evaluation->date_evaluation && $evaluation->duree ? $evaluation->date_evaluation->addMinutes($evaluation->duree)->format("Y-m-d\TH:i:s") : "" }}',
                    color: '{{ $evaluation->date_evaluation < now() ? "#6c757d" : "#007bff" }}',
                    description: 'Salle: {{ $evaluation->salle }}, Surveillant: {{ $evaluation->surveillant }}'
                },
                @endforeach
            ],
            eventDidMount: function(info) {
                $(info.el).tooltip({
                    title: info.event.extendedProps.description,
                    placement: 'top',
                    trigger: 'hover',
                    container: 'body'
                });
            }
        });
        calendar.render();
    });
</script>
@endsection
