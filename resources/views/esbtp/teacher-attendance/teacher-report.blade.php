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

    /* Bulk nav */
    #bulkNavBar {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        z-index: 1050;
        background: linear-gradient(135deg, #0453cb, #5e91de);
        color: white;
        border-radius: 99px;
        padding: 12px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 8px 32px rgba(4,83,203,.45);
        transition: transform .3s cubic-bezier(.34,1.56,.64,1);
        white-space: nowrap;
    }
    #bulkNavBar.visible {
        transform: translateX(-50%) translateY(0);
    }
    #bulkNavBar .bulk-count {
        background: rgba(255,255,255,.25);
        border-radius: 99px;
        padding: 2px 10px;
        font-weight: 700;
        font-size: .85rem;
    }
    #bulkNavBar .btn-bulk {
        background: rgba(255,255,255,.15);
        border: 1px solid rgba(255,255,255,.35);
        color: white;
        border-radius: 99px;
        padding: 6px 16px;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }
    #bulkNavBar .btn-bulk:hover { background: rgba(255,255,255,.28); }
    #bulkNavBar .btn-bulk-danger {
        background: rgba(239,68,68,.25);
        border-color: rgba(239,68,68,.5);
    }
    #bulkNavBar .btn-bulk-danger:hover { background: rgba(239,68,68,.4); }
    #bulkNavBar .btn-bulk-close {
        background: transparent;
        border: none;
        color: rgba(255,255,255,.7);
        font-size: 1.1rem;
        cursor: pointer;
        padding: 0 4px;
        line-height: 1;
    }
    #bulkNavBar .btn-bulk-close:hover { color: white; }

    /* Future row */
    tr.seance-future { opacity: .72; }

    /* Rapport modal */
    .rapport-section-title {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #94a3b8;
        margin-bottom: 6px;
        margin-top: 12px;
    }
    .rapport-section-body {
        background: #f8fafc;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: .9rem;
        color: #334155;
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
                                <th style="width:36px;">
                                    <input type="checkbox" id="selectAllSeances" class="form-check-input"
                                           title="Tout sélectionner" onchange="toggleSelectAll(this)">
                                </th>
                                <th>Matière</th>
                                <th>Classe</th>
                                <th>Horaires</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($seances as $seance)
                                @php
                                    $today2 = \Carbon\Carbon::today();
                                    $seanceEstFuture = false;
                                    if ($seance->date_seance) {
                                        $dateSeance = \Carbon\Carbon::parse($seance->date_seance)->startOfDay();
                                        if ($dateSeance->gt($today2)) {
                                            $seanceEstFuture = true;
                                        } elseif ($dateSeance->eq($today2) && $seance->heure_fin) {
                                            $seanceEstFuture = \Carbon\Carbon::parse($seance->heure_fin)->gt(now());
                                        }
                                    }

                                    // Résoudre le statut d'émargement
                                    $attendance = $seance->teacherAttendances
                                        ->first(function($a) use ($today2) {
                                            $d = $a->date instanceof \Carbon\Carbon ? $a->date : \Carbon\Carbon::parse($a->date);
                                            return $d->isSameDay($today2);
                                        });
                                    if (!$attendance) {
                                        $attendance = $seance->teacherAttendances
                                            ->first(function($a) use ($seance) {
                                                $d = $a->date instanceof \Carbon\Carbon ? $a->date : \Carbon\Carbon::parse($a->date);
                                                return $d->isSameDay(\Carbon\Carbon::parse($seance->date_seance));
                                            });
                                    }
                                    if (!$attendance) {
                                        $attendance = $seance->teacherAttendances->sortByDesc('created_at')->first();
                                    }
                                    $status = $attendance?->status ?? 'not_signed';

                                    $hasSessionReport = isset($seance->sessionReport) && $seance->sessionReport && $seance->sessionReport->status === 'submitted';
                                @endphp
                                <tr class="{{ $seanceEstFuture ? 'seance-future' : '' }}" data-seance-id="{{ $seance->id }}">
                                    <td>
                                        @if(!$seanceEstFuture)
                                            <input type="checkbox" class="seance-checkbox form-check-input"
                                                   value="{{ $seance->id }}" onchange="updateBulkBar()">
                                        @endif
                                    </td>
                                    <td>
                                        <span style="font-weight:600; color:#0453cb;">
                                            {{ $seance->matiere?->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ $seance->emploiTemps?->classe?->name ?? 'N/A' }}</td>
                                    <td>
                                        <span style="font-size:.85rem; color:#64748b;">
                                            <i class="fas fa-clock me-1" style="font-size:.75rem;"></i>
                                            {{ $seance->heure_debut ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') : 'N/A' }} –
                                            {{ $seance->heure_fin ? \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td style="font-size:.85rem;">
                                        {{ $seance->getDateCompleteFormattee() }}
                                        @if($seanceEstFuture)
                                            <span class="badge mt-1 d-inline-block" style="background: linear-gradient(135deg, #0453cb, #5e91de); color: white; font-size:.7rem;">
                                                <i class="fas fa-clock me-1"></i>À venir
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($seanceEstFuture)
                                            <span class="status-badge" style="background:rgba(100,116,139,.08); color:#94a3b8;">
                                                <i class="fas fa-hourglass-half" style="font-size:.78rem;"></i>En attente
                                            </span>
                                        @elseif($status === 'present')
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
                                    <td>
                                        <div class="d-flex gap-1 align-items-center">
                                            @if(!$seanceEstFuture)
                                                {{-- Bouton marquer présent --}}
                                                @if($status !== 'present')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-success mark-status-btn"
                                                        data-seance-id="{{ $seance->id }}"
                                                        data-status="present"
                                                        title="Marquer présent">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                @endif
                                                {{-- Bouton marquer absent --}}
                                                @if($status !== 'absent')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger mark-status-btn"
                                                        data-seance-id="{{ $seance->id }}"
                                                        data-status="absent"
                                                        title="Marquer absent">
                                                    <i class="fas fa-user-times"></i>
                                                </button>
                                                @endif
                                            @endif

                                            {{-- Bouton rapport de cours --}}
                                            @if(!$seanceEstFuture && $hasSessionReport)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rapportModal{{ $seance->id }}"
                                                        title="Voir le rapport de cours">
                                                    <i class="fas fa-file-alt"></i>
                                                </button>
                                            @endif

                                            {{-- Lien vers la séance --}}
                                            <a href="{{ route('esbtp.seances-cours.show', $seance->id) }}"
                                               class="btn btn-sm btn-outline-info"
                                               title="Voir la séance">
                                                <i class="fas fa-calendar-day"></i>
                                            </a>

                                            {{-- Spinner --}}
                                            <div class="seance-spinner d-none" id="spinner-{{ $seance->id }}">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                    <span class="visually-hidden">Chargement...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
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

{{-- Modals rapport de cours --}}
@foreach($seances as $seance)
    @if(isset($seance->sessionReport) && $seance->sessionReport && $seance->sessionReport->status === 'submitted')
        @php $rapport = $seance->sessionReport; @endphp
        <div class="modal fade" id="rapportModal{{ $seance->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #0453cb, #5e91de); color: white;">
                        <div>
                            <h5 class="modal-title mb-1">
                                <i class="fas fa-file-alt me-2"></i>Rapport de cours
                            </h5>
                            <div style="font-size:.85rem; opacity:.85;">
                                {{ $seance->matiere?->name ?? 'N/A' }} — {{ $seance->emploiTemps?->classe?->name ?? 'N/A' }}
                                — {{ $seance->getDateCompleteFormattee() }}
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Badges --}}
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-success">
                                <i class="fas fa-check me-1"></i>Rapport soumis
                            </span>
                            @if($rapport->submitted_at ?? $rapport->created_at)
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-calendar me-1"></i>
                                    {{ ($rapport->submitted_at ?? $rapport->created_at)?->format('d/m/Y à H:i') }}
                                </span>
                            @endif
                            @if($rapport->behavior_rating ?? null)
                                <span class="badge" style="background: rgba(4,83,203,.1); color:#0453cb;">
                                    <i class="fas fa-star me-1"></i>Comportement : {{ $rapport->behavior_rating }}/5
                                </span>
                            @endif
                        </div>

                        {{-- Contenu --}}
                        @if($rapport->content_summary ?? null)
                            <div class="rapport-section-title">Résumé du cours</div>
                            <div class="rapport-section-body">{{ $rapport->content_summary }}</div>
                        @endif

                        @if($rapport->teaching_methods ?? null)
                            <div class="rapport-section-title">Méthodes pédagogiques</div>
                            <div class="rapport-section-body">{{ $rapport->teaching_methods }}</div>
                        @endif

                        @if($rapport->difficulties ?? null)
                            <div class="rapport-section-title">Difficultés rencontrées</div>
                            <div class="rapport-section-body">{{ $rapport->difficulties }}</div>
                        @endif

                        @if($rapport->homework_assigned ?? null)
                            <div class="rapport-section-title">Devoirs assignés</div>
                            <div class="rapport-section-body">{{ $rapport->homework_assigned }}</div>
                        @endif

                        @if($rapport->next_session_plan ?? null)
                            <div class="rapport-section-title">Prochaine séance</div>
                            <div class="rapport-section-body">{{ $rapport->next_session_plan }}</div>
                        @endif

                        @if($rapport->notes ?? null)
                            <div class="rapport-section-title">Notes complémentaires</div>
                            <div class="rapport-section-body">{{ $rapport->notes }}</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('esbtp.seances-cours.show', $seance->id) }}"
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-calendar-day me-1"></i>Voir la séance
                        </a>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- Bulk nav bar --}}
<div id="bulkNavBar">
    <span style="font-size:.85rem; font-weight:600;">
        <i class="fas fa-check-square me-2"></i>
        <span class="bulk-count" id="bulkCount">0</span> sélectionnée(s)
    </span>
    <button class="btn-bulk" onclick="bulkMarkStatus('present')">
        <i class="fas fa-check me-1"></i>Marquer présent
    </button>
    <button class="btn-bulk btn-bulk-danger" onclick="bulkMarkStatus('absent')">
        <i class="fas fa-user-times me-1"></i>Marquer absent
    </button>
    <button class="btn-bulk-close" onclick="clearBulkSelection()" title="Annuler">
        <i class="fas fa-times"></i>
    </button>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    /* -------------------------------------------------- */
    /* Chart                                              */
    /* -------------------------------------------------- */
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
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    /* -------------------------------------------------- */
    /* Mark status (single)                               */
    /* -------------------------------------------------- */
    document.querySelectorAll('.mark-status-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const seanceId = this.dataset.seanceId;
            const status = this.dataset.status;
            markSeanceStatus([seanceId], status, () => {
                // Reload row via page refresh partiel si besoin
                location.reload();
            });
        });
    });

    /* -------------------------------------------------- */
    /* Bulk selection                                     */
    /* -------------------------------------------------- */
    let selectedSeances = new Set();

    function toggleSelectAll(masterCb) {
        document.querySelectorAll('.seance-checkbox').forEach(cb => {
            cb.checked = masterCb.checked;
            if (masterCb.checked) {
                selectedSeances.add(cb.value);
            } else {
                selectedSeances.delete(cb.value);
            }
        });
        updateBulkBar();
    }

    function updateBulkBar() {
        selectedSeances = new Set();
        document.querySelectorAll('.seance-checkbox:checked').forEach(cb => {
            selectedSeances.add(cb.value);
        });
        const count = selectedSeances.size;
        document.getElementById('bulkCount').textContent = count;
        const bar = document.getElementById('bulkNavBar');
        if (count > 0) {
            bar.classList.add('visible');
        } else {
            bar.classList.remove('visible');
            const masterCb = document.getElementById('selectAllSeances');
            if (masterCb) masterCb.checked = false;
        }
    }

    function clearBulkSelection() {
        document.querySelectorAll('.seance-checkbox').forEach(cb => cb.checked = false);
        const masterCb = document.getElementById('selectAllSeances');
        if (masterCb) masterCb.checked = false;
        selectedSeances.clear();
        updateBulkBar();
    }

    function bulkMarkStatus(status) {
        if (selectedSeances.size === 0) return;
        const label = status === 'present' ? 'présent' : 'absent';
        if (!confirm(`Marquer ${selectedSeances.size} séance(s) comme ${label} ?`)) return;

        const ids = Array.from(selectedSeances);
        markSeanceStatus(ids, status, () => {
            clearBulkSelection();
            location.reload();
        });
    }

    /* -------------------------------------------------- */
    /* Core AJAX call                                     */
    /* -------------------------------------------------- */
    function markSeanceStatus(seanceIds, status, onSuccess) {
        // Afficher spinners
        seanceIds.forEach(id => {
            const spinner = document.getElementById('spinner-' + id);
            if (spinner) spinner.classList.remove('d-none');
        });

        fetch('{{ url("/esbtp/teacher-attendance/bulk-update-status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ seance_ids: seanceIds, status: status })
        })
        .then(r => r.json())
        .then(data => {
            seanceIds.forEach(id => {
                const spinner = document.getElementById('spinner-' + id);
                if (spinner) spinner.classList.add('d-none');
            });
            if (data.success) {
                if (typeof onSuccess === 'function') onSuccess();
            } else {
                alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
            }
        })
        .catch(err => {
            seanceIds.forEach(id => {
                const spinner = document.getElementById('spinner-' + id);
                if (spinner) spinner.classList.add('d-none');
            });
            console.error(err);
            alert('Erreur réseau. Veuillez réessayer.');
        });
    }
</script>
@endpush
