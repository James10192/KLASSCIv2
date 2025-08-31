@extends('layouts.app')

@section('title', 'Tableau de bord étudiant')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card" style="border-color: #01632f;">
                <div class="card-body">
                    <h5 class="card-title" style="color: #01632f;">Bienvenue dans le tableau de bord étudiant ESBTP</h5>
                    <p class="card-text">Système de gestion universitaire pour l'École Supérieure du Bâtiment et des Travaux Publics</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card text-white" style="background-color: #01632f;">
                <div class="card-body">
                    <h5 class="card-title">Présence</h5>
                    <p class="card-text display-4">{{ $attendancePercentage ?? 0 }}%</p>
                    <a href="{{ route('attendances.student') }}" class="btn" style="background-color: #f29400; color: white;">Voir détails</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card text-white" style="background-color: #f29400;">
                <div class="card-body">
                    <h5 class="card-title">Moyenne générale</h5>
                    <p class="card-text display-4">{{ $averageGrade ?? 'N/A' }}</p>
                    <a href="{{ route('grades.student') }}" class="btn" style="background-color: #01632f; color: white;">Voir notes</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card text-white" style="background-color: #01632f;">
                <div class="card-body">
                    <h5 class="card-title">Notifications</h5>
                    <p class="card-text display-4">{{ $unreadNotifications ?? 0 }}</p>
                    <a href="{{ route('notifications.index') }}" class="btn" style="background-color: #f29400; color: white;">Voir toutes</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card" style="border-color: #01632f;">
                <div class="card-header" style="background-color: #01632f; color: white;">
                    Emploi du temps d'aujourd'hui
                </div>
                <div class="card-body">
                    @if(isset($todayTimetable) && $todayTimetable->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($todayTimetable as $entry)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $entry->subject->name }}</strong> - 
                                            {{ $entry->teacher->name }}
                                        </div>
                                        <div>
                                            {{ \Carbon\Carbon::parse($entry->start_time)->format('H:i') }} - 
                                            {{ \Carbon\Carbon::parse($entry->end_time)->format('H:i') }}
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">Aucun cours programmé aujourd'hui</p>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card" style="border-color: #f29400;">
                <div class="card-header" style="background-color: #f29400; color: white;">
                    Devoirs à rendre
                </div>
                <div class="card-body">
                    @if(isset($upcomingAssignments) && $upcomingAssignments->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($upcomingAssignments as $assignment)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $assignment->title }}</strong> - 
                                            {{ $assignment->subject->name }}
                                        </div>
                                        <div>
                                            <span class="badge {{ \Carbon\Carbon::parse($assignment->due_date)->isPast() ? 'bg-danger' : 'bg-warning' }}">
                                                Date limite: {{ \Carbon\Carbon::parse($assignment->due_date)->format('d/m/Y') }}
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">Aucun devoir à rendre</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card" style="border-color: #01632f;">
                <div class="card-header" style="background-color: #01632f; color: white;">
                    Mes notes récentes
                </div>
                <div class="card-body">
                    @if(isset($recentGrades) && $recentGrades->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Matière</th>
                                        <th>Évaluation</th>
                                        <th>Note</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentGrades as $grade)
                                        <tr>
                                            <td>{{ $grade->subject->name }}</td>
                                            <td>{{ $grade->exam->name }}</td>
                                            <td>
                                                <span class="badge {{ $grade->score >= 10 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $grade->score }}/20
                                                </span>
                                            </td>
                                            <td>{{ $grade->created_at->format('d/m/Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">Aucune note récente</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 