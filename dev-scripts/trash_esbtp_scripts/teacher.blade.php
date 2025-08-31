@extends('layouts.app')

@section('title', 'Tableau de bord enseignant')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card" style="border-color: #01632f;">
                <div class="card-body">
                    <h5 class="card-title" style="color: #01632f;">Bienvenue dans le tableau de bord enseignant ESBTP</h5>
                    <p class="card-text">Système de gestion universitaire pour l'École Supérieure du Bâtiment et des Travaux Publics</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card text-white" style="background-color: #01632f;">
                <div class="card-body">
                    <h5 class="card-title">Mes classes</h5>
                    <p class="card-text display-4">{{ $classesTaught ?? 0 }}</p>
                    <a href="{{ route('timetables.index') }}" class="btn" style="background-color: #f29400; color: white;">Voir l'emploi du temps</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card text-white" style="background-color: #f29400;">
                <div class="card-body">
                    <h5 class="card-title">Étudiants</h5>
                    <p class="card-text display-4">{{ $totalStudents ?? 0 }}</p>
                    <a href="{{ route('students.index') }}" class="btn" style="background-color: #01632f; color: white;">Voir tous</a>
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
                                            {{ $entry->class->name }} ({{ $entry->section->name }})
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
                    Notifications récentes
                </div>
                <div class="card-body">
                    @if(isset($recentNotifications) && $recentNotifications->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($recentNotifications as $notification)
                                <li class="list-group-item">
                                    <strong>{{ $notification->title }}</strong>
                                    <span class="float-end text-muted">{{ $notification->created_at->format('d/m/Y') }}</span>
                                    <p class="mb-0">{{ Str::limit($notification->message, 100) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">Aucune notification récente</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card" style="border-color: #01632f;">
                <div class="card-header" style="background-color: #01632f; color: white;">
                    Actions rapides
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('attendances.mark') }}" class="btn btn-lg w-100" style="background-color: #f29400; color: white;">
                                <i class="fas fa-clipboard-check"></i> Faire l'appel
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('grades.index') }}" class="btn btn-lg w-100" style="background-color: #01632f; color: white;">
                                <i class="fas fa-graduation-cap"></i> Saisir des notes
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('notifications.create') }}" class="btn btn-lg w-100" style="background-color: #f29400; color: white;">
                                <i class="fas fa-bell"></i> Envoyer notification
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('timetables.index') }}" class="btn btn-lg w-100" style="background-color: #01632f; color: white;">
                                <i class="fas fa-calendar-alt"></i> Emploi du temps
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 