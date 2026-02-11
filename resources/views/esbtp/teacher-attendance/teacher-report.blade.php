@extends('layouts.app')

@section('title', 'Détail émargement enseignant - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Hero gradient */
    .teacher-hero {
        background: linear-gradient(135deg, #0453cb, #1b64d4);
        border-radius: 16px;
        padding: 28px 32px;
        color: white;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 20px rgba(4,83,203,.25);
    }
    .teacher-avatar-hero {
        width: 72px; height: 72px;
        border-radius: 50%;
        background: rgba(255,255,255,.2);
        border: 2px solid rgba(255,255,255,.4);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 1.4rem;
        flex-shrink: 0;
    }

    /* KPI cards */
    .th-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    .th-kpi-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        position: relative;
        overflow: hidden;
        transition: box-shadow .2s;
    }
    .th-kpi-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.09); }
    .th-kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: var(--kpi-accent, #0453cb);
        border-radius: 14px 14px 0 0;
    }
    .th-kpi-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem;
        margin-bottom: 10px;
    }
    .th-kpi-value {
        font-size: 1.8rem;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }
    .th-kpi-label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-top: 4px;
    }
    .th-kpi-sub {
        font-size: .8rem;
        color: #64748b;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
    }

    /* Status badges in table */
    .status-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 99px;
        font-size: .78rem; font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- Hero --}}
        <div class="teacher-hero">
            @php
                $parts = preg_split('/\s+/', trim($teacher->user?->name ?? 'Enseignant'));
                $initials = strtoupper(collect($parts)->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode(''));
                $rateColor = $attendanceRate >= 70 ? '#10b981' : ($attendanceRate >= 30 ? '#f59e0b' : '#ef4444');
            @endphp
            <div class="teacher-avatar-hero">{{ $initials }}</div>
            <div class="flex-grow-1">
                <h2 class="mb-1 fw-bold" style="color:white; font-size:1.4rem;">{{ $teacher->user?->name ?? 'Enseignant' }}</h2>
                @if($teacher->user?->email)
                    <div style="color:rgba(255,255,255,.7); font-size:.9rem;">
                        <i class="fas fa-envelope me-1" style="font-size:.8rem;"></i>{{ $teacher->user->email }}
                    </div>
                @endif
            </div>
            <div class="ms-auto flex-shrink-0">
                <a href="{{ route('esbtp.teacher-attendance.report') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-2"></i>Retour rapport
                </a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="th-kpi-grid">
            {{-- Taux de présence --}}
            <div class="th-kpi-card" style="--kpi-accent: {{ $rateColor }}">
                <div class="th-kpi-icon" style="background: {{ $attendanceRate >= 70 ? 'rgba(16,185,129,.12)' : ($attendanceRate >= 30 ? 'rgba(245,158,11,.12)' : 'rgba(239,68,68,.12)') }}; color: {{ $rateColor }};">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="th-kpi-value" style="color: {{ $rateColor }};">{{ $attendanceRate }}%</div>
                <div class="th-kpi-label">Taux de présence</div>
            </div>

            {{-- Séances --}}
            <div class="th-kpi-card" style="--kpi-accent: #0453cb">
                <div class="th-kpi-icon" style="background: rgba(4,83,203,.1); color:#0453cb;">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="th-kpi-value">{{ $stats['total'] }}</div>
                <div class="th-kpi-label">Séances planifiées</div>
            </div>

            {{-- Présents --}}
            <div class="th-kpi-card" style="--kpi-accent: #10b981">
                <div class="th-kpi-icon" style="background: rgba(16,185,129,.1); color:#10b981;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="th-kpi-value" style="color:#10b981;">{{ $stats['present'] }}</div>
                <div class="th-kpi-label">Présents</div>
                <div class="th-kpi-sub">
                    <i class="fas fa-clock me-1" style="color:#f59e0b;"></i>Retards : <strong>{{ $stats['late'] }}</strong>
                </div>
            </div>

            {{-- Absents --}}
            <div class="th-kpi-card" style="--kpi-accent: #ef4444">
                <div class="th-kpi-icon" style="background: rgba(239,68,68,.1); color:#ef4444;">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="th-kpi-value" style="color:#ef4444;">{{ $stats['absent'] }}</div>
                <div class="th-kpi-label">Absents</div>
                <div class="th-kpi-sub">
                    <i class="fas fa-minus-circle me-1" style="color:#94a3b8;"></i>Non émargé : <strong>{{ $stats['not_signed'] }}</strong>
                </div>
            </div>
        </div>

        {{-- Chart Card --}}
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-chart-line"></i>
                    Évolution mensuelle des émargements
                </div>
            </div>
            <div class="main-card-body">
                <script type="application/json" id="monthlyStatsData">{!! json_encode($monthlyStats) !!}</script>
                <canvas id="attendanceChart" height="100"></canvas>
            </div>
        </div>

        {{-- Historique séances --}}
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-history"></i>
                    Historique des séances
                </div>
                <div class="main-card-subtitle">{{ $seances->total() }} séance(s)</div>
            </div>
            <div class="main-card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
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
                                    <td>
                                        <span style="font-weight:600; color:#0453cb;">
                                            {{ $seance->matiere?->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ $seance->emploiTemps?->classe?->name ?? 'N/A' }}</td>
                                    <td>
                                        <span style="font-size:.85rem; color:#64748b;">
                                            <i class="fas fa-clock me-1" style="font-size:.75rem;"></i>
                                            {{ \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') }} – {{ \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') }}
                                        </span>
                                    </td>
                                    <td style="font-size:.85rem;">{{ $seance->getDateCompleteFormattee() }}</td>
                                    <td>
                                        @if($status === 'present')
                                            <span class="status-badge" style="background:rgba(16,185,129,.1); color:#059669;">
                                                <i class="fas fa-check-circle" style="font-size:.8rem;"></i>Présent
                                            </span>
                                        @elseif($status === 'late')
                                            <span class="status-badge" style="background:rgba(245,158,11,.1); color:#d97706;">
                                                <i class="fas fa-clock" style="font-size:.8rem;"></i>Retard
                                            </span>
                                        @elseif($status === 'absent')
                                            <span class="status-badge" style="background:rgba(239,68,68,.1); color:#dc2626;">
                                                <i class="fas fa-times-circle" style="font-size:.8rem;"></i>Absent
                                            </span>
                                        @else
                                            <span class="status-badge" style="background:rgba(100,116,139,.1); color:#475569;">
                                                <i class="fas fa-minus-circle" style="font-size:.8rem;"></i>Non émargé
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block" style="color:#cbd5e1;"></i>
                                        Aucune séance trouvée.
                                    </td>
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
