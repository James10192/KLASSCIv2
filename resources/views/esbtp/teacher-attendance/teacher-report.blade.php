@extends('layouts.app')

@section('title', 'Détail émargement enseignant - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .teacher-hero {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px 24px;
        background: linear-gradient(135deg, #0f3f87 0%, #0453cb 100%);
        border-radius: 16px;
        color: #fff;
        margin-bottom: 20px;
    }

    .teacher-avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.4rem;
        letter-spacing: 1px;
    }

    .teacher-kpi {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .teacher-kpi-card {
        background: #fff;
        border-radius: 14px;
        padding: 16px;
        border: 1px solid #e5e7eb;
    }

    .teacher-kpi-card .label {
        color: #64748b;
        font-size: 0.85rem;
    }

    .teacher-kpi-card .value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #0f172a;
    }

    .teacher-metric-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.9rem;
        margin-top: 8px;
    }

    .teacher-chart-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px;
        border: 1px solid #e5e7eb;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="teacher-hero">
            @php
                $parts = preg_split('/\s+/', trim($teacher->user?->name ?? 'Enseignant'));
                $initials = strtoupper(collect($parts)->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode(''));
            @endphp
            <div class="teacher-avatar">{{ $initials }}</div>
            <div>
                <h2 class="mb-1">{{ $teacher->user?->name ?? 'Enseignant' }}</h2>
                <div class="text-white-50">{{ $teacher->user?->email ?? '' }}</div>
            </div>
            <div class="ms-auto">
                <a href="{{ route('esbtp.teacher-attendance.report') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Retour rapport
                </a>
            </div>
        </div>

        <div class="teacher-kpi">
            <div class="teacher-kpi-card">
                <div class="label">Taux de présence</div>
                <div class="value">{{ $attendanceRate }}%</div>
            </div>
            <div class="teacher-kpi-card">
                <div class="label">Séances</div>
                <div class="value">{{ $stats['total'] }}</div>
            </div>
            <div class="teacher-kpi-card">
                <div class="label">Présents</div>
                <div class="value" style="color:#10b981;">{{ $stats['present'] }}</div>
                <div class="teacher-metric-row"><span>Retards</span><strong>{{ $stats['late'] }}</strong></div>
            </div>
            <div class="teacher-kpi-card">
                <div class="label">Absents</div>
                <div class="value" style="color:#ef4444;">{{ $stats['absent'] }}</div>
                <div class="teacher-metric-row"><span>Non émargé</span><strong>{{ $stats['not_signed'] }}</strong></div>
            </div>
        </div>

        <div class="teacher-chart-card mb-4">
            <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Évolution mensuelle des émargements</h6>
            <script type="application/json" id="monthlyStatsData">{!! json_encode($monthlyStats) !!}</script>
            <canvas id="attendanceChart" height="120"></canvas>
        </div>

        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Historique des séances
                </div>
            </div>
            <div class="main-card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Matière</th>
                                <th>Classe</th>
                                <th>Horaires</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($seances as $seance)
                                @php
                                    $status = $seance->teacherAttendances->sortByDesc('created_at')->first()?->status ?? 'not_signed';
                                @endphp
                                <tr>
                                    <td>{{ $seance->matiere?->name ?? 'N/A' }}</td>
                                    <td>{{ $seance->emploiTemps?->classe?->name ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') }} - {{ \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') }}</td>
                                    <td>{{ $seance->getDateCompleteFormattee() }}</td>
                                    <td>
                                        @if($status === 'present')
                                            <span class="badge bg-success">Présent</span>
                                        @elseif($status === 'late')
                                            <span class="badge bg-warning text-dark">Retard</span>
                                        @elseif($status === 'absent')
                                            <span class="badge bg-danger">Absent</span>
                                        @else
                                            <span class="badge bg-secondary">Non émargé</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Aucune séance trouvée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $seances->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const monthlyStats = JSON.parse(document.getElementById('monthlyStatsData').textContent);
    const labels = monthlyStats.map(item => item.label);
    const presentData = monthlyStats.map(item => item.present + item.late);
    const absentData = monthlyStats.map(item => item.absent);
    const notSignedData = monthlyStats.map(item => item.not_signed);

    const ctx = document.getElementById('attendanceChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Présent + Retard',
                        data: presentData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.15)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Absent',
                        data: absentData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Non émargé',
                        data: notSignedData,
                        borderColor: '#64748b',
                        backgroundColor: 'rgba(100, 116, 139, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
</script>
@endpush
