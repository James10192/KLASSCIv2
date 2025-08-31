@extends('layouts.app')

@section('title', 'Tableau de bord administrateur')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card" style="border-color: #01632f;">
                <div class="card-body">
                    <h5 class="card-title" style="color: #01632f;">Bienvenue dans le tableau de bord ESBTP</h5>
                    <p class="card-text">Système de gestion universitaire pour l'École Supérieure du Bâtiment et des Travaux Publics</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card text-white" style="background-color: #01632f;">
                <div class="card-body">
                    <h5 class="card-title">Étudiants</h5>
                    <p class="card-text display-4">{{ $totalStudents ?? 0 }}</p>
                    <a href="{{ route('students.index') }}" class="btn" style="background-color: #f29400; color: white;">Voir tous</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card text-white" style="background-color: #f29400;">
                <div class="card-body">
                    <h5 class="card-title">Enseignants</h5>
                    <p class="card-text display-4">{{ $totalTeachers ?? 0 }}</p>
                    <a href="{{ route('teachers.index') }}" class="btn" style="background-color: #01632f; color: white;">Voir tous</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card text-white" style="background-color: #01632f;">
                <div class="card-body">
                    <h5 class="card-title">Présences aujourd'hui</h5>
                    <p class="card-text display-4">{{ App\Models\Attendance::whereDate('created_at', today())->count() ?? 0 }}</p>
                    <a href="{{ route('attendances.index') }}" class="btn" style="background-color: #f29400; color: white;">Voir toutes</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card text-white" style="background-color: #f29400;">
                <div class="card-body">
                    <h5 class="card-title">Notifications</h5>
                    <p class="card-text display-4">{{ App\Models\Notification::count() ?? 0 }}</p>
                    <a href="{{ route('notifications.index') }}" class="btn" style="background-color: #01632f; color: white;">Voir toutes</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card" style="border-color: #01632f;">
                <div class="card-header" style="background-color: #01632f; color: white;">
                    Certificats récents
                </div>
                <div class="card-body">
                    @if(isset($recentCertificates) && $recentCertificates->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($recentCertificates as $certificate)
                                <li class="list-group-item">
                                    <strong>{{ $certificate->student->user->name }}</strong> - 
                                    {{ $certificate->certificateType->name }}
                                    <span class="float-end text-muted">{{ $certificate->created_at->format('d/m/Y') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">Aucun certificat récent</p>
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

    @if(isset($attendanceStats) && $attendanceStats->count() > 0)
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card" style="border-color: #01632f;">
                <div class="card-header" style="background-color: #01632f; color: white;">
                    Statistiques de présence (7 derniers jours)
                </div>
                <div class="card-body">
                    <canvas id="attendanceChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
@if(isset($attendanceStats) && $attendanceStats->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        
        // Préparer les données
        const dates = @json($attendanceStats->pluck('attendance_date'));
        const present = @json($attendanceStats->pluck('present'));
        const total = @json($attendanceStats->pluck('total'));
        const absent = total.map((t, i) => t - present[i]);
        
        // Créer le graphique
        const attendanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Présents',
                        data: present,
                        backgroundColor: '#01632f',
                        borderColor: '#01632f',
                        borderWidth: 1
                    },
                    {
                        label: 'Absents',
                        data: absent,
                        backgroundColor: '#f29400',
                        borderColor: '#f29400',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endif
@endsection 