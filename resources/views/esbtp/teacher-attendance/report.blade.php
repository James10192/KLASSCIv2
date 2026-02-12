@extends('layouts.app')

@section('title', 'Rapport d\'émargement des enseignants - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* === Teacher Stat Cards === */
.teacher-stat-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    position: relative;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 14px;
    transition: box-shadow .2s;
}
.teacher-stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.09); }
.teacher-stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--card-accent, #0453cb);
    border-radius: 14px 14px 0 0;
}
.tsc-avatar {
    width: 44px; height: 44px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .9rem;
    flex-shrink: 0;
}
.tsc-rate-value {
    font-size: 2rem; font-weight: 800; line-height: 1;
}
.tsc-progress-track {
    height: 6px; background: #f1f5f9; border-radius: 99px; overflow: hidden;
}
.tsc-progress-bar {
    height: 100%; border-radius: 99px;
    transition: width .6s ease;
}
.tsc-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; border-radius: 99px;
    font-size: .72rem; font-weight: 600;
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-clipboard-check me-2"></i>Rapport d'émargement des cours</h1>
                <p class="header-subtitle">Suivi des présences et émargements des enseignants pour les séances de cours uniquement</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-acasi secondary" data-bs-toggle="modal" data-bs-target="#generateCodeModal">
                    <i class="fas fa-qrcode"></i>Générer Code du Jour
                </button>
                <a href="{{ route('esbtp.admin.attendance.generate-code') }}" class="btn-acasi warning">
                    <i class="fas fa-download"></i>Exporter
                </a>
            </div>
        </div>

        <!-- Année universitaire courante -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-calendar-alt"></i>
                    Année universitaire en cours
                </div>
                <div class="main-card-subtitle">{{ $anneeEnCours->name ?? 'Non définie' }}</div>
            </div>
            <div class="main-card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        <span>
                            Du {{ $anneeEnCours?->start_date ? $anneeEnCours->start_date->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-times text-primary me-2"></i>
                        <span>
                            Au {{ $anneeEnCours?->end_date ? $anneeEnCours->end_date->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                    <div class="ms-auto">
                        <span class="badge bg-primary">Année courante</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Séances Planifiées</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalSeances ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calendar-week"></i>
                    Toutes les séances
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Émargements validés</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalAttendances ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-clipboard-check"></i>
                    Présences confirmées (hors absents)
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Présents (retards inclus)</div>
                <div class="kpi-value" style="color: #10b981; font-size: 2.5rem; font-weight: bold;">{{ $attendancesPresent ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-check-circle"></i>
                    Dont {{ $attendancesLate ?? 0 }} retard(s)
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">En Retard</div>
                <div class="kpi-value" style="color: #f59e0b; font-size: 2.5rem; font-weight: bold;">{{ $attendancesLate ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-clock"></i>
                    Retards signalés (inclus dans les présents)
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Absents</div>
                <div class="kpi-value" style="color: #ef4444; font-size: 2.5rem; font-weight: bold;">{{ $attendancesAbsent ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-user-times"></i>
                    Séances clôturées sans émargement
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Aujourd'hui</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $attendancesToday ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calendar-day"></i>
                    Émargements du jour
                </div>
            </div>
        </div>

        @if(isset($teacherStats) && $teacherStats->count() > 0)
            <div class="main-card mb-4">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-user-check"></i>
                        Statistiques par enseignant
                    </div>
                    <div class="main-card-subtitle">Résumé individuel des émargements</div>
                </div>
                <div class="main-card-body">
                    <div class="row g-3">
                        @foreach($teacherStats as $stat)
                            @php
                                $parts = preg_split('/\s+/', trim($stat['name']));
                                $initials = strtoupper(collect($parts)->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode(''));
                                $taux = $stat['taux'];
                                $accentColor = $taux >= 70 ? '#10b981' : ($taux >= 30 ? '#f59e0b' : '#ef4444');
                                $avatarBg    = $taux >= 70 ? 'rgba(16,185,129,.12)' : ($taux >= 30 ? 'rgba(245,158,11,.12)' : 'rgba(239,68,68,.12)');
                            @endphp
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="teacher-stat-card" style="--card-accent: {{ $accentColor }}">
                                    {{-- Header : Avatar + Nom + Séances --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="tsc-avatar" style="background: {{ $avatarBg }}; color: {{ $accentColor }};">
                                            {{ $initials }}
                                        </div>
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="fw-semibold text-truncate" style="color:#1e293b; font-size:.95rem;">{{ $stat['name'] }}</div>
                                            <div style="color:#94a3b8; font-size:.78rem;">{{ $stat['total'] }} séance(s) planifiée(s)</div>
                                        </div>
                                    </div>

                                    {{-- Taux de présence --}}
                                    <div>
                                        <div class="d-flex align-items-baseline gap-2 mb-1">
                                            <span class="tsc-rate-value" style="color: {{ $accentColor }}">{{ $taux }}%</span>
                                            <span style="color:#94a3b8; font-size:.78rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em;">Taux présence</span>
                                        </div>
                                        <div class="tsc-progress-track">
                                            <div class="tsc-progress-bar" style="width: {{ min($taux, 100) }}%; background: {{ $accentColor }};"></div>
                                        </div>
                                    </div>

                                    {{-- Pills compteurs --}}
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="tsc-pill" style="background:rgba(16,185,129,.1); color:#059669;">
                                            <i class="fas fa-check" style="font-size:.65rem;"></i>{{ $stat['present'] }} Présent
                                        </span>
                                        <span class="tsc-pill" style="background:rgba(245,158,11,.1); color:#d97706;">
                                            <i class="fas fa-clock" style="font-size:.65rem;"></i>{{ $stat['late'] }} Retard
                                        </span>
                                        <span class="tsc-pill" style="background:rgba(239,68,68,.1); color:#dc2626;">
                                            <i class="fas fa-user-times" style="font-size:.65rem;"></i>{{ $stat['absent'] }} Absent
                                        </span>
                                        <span class="tsc-pill" style="background:rgba(100,116,139,.1); color:#475569;">
                                            <i class="fas fa-minus" style="font-size:.65rem;"></i>{{ $stat['not_signed'] }} N/É
                                        </span>
                                    </div>

                                    {{-- Footer action --}}
                                    <div class="mt-auto pt-1">
                                        <a href="{{ route('esbtp.teacher-attendance.teacher-report', ['teacher' => $stat['teacher_id']]) }}"
                                           class="btn btn-sm btn-outline-primary w-100"
                                           style="font-size:.8rem; border-radius:8px;">
                                            <i class="fas fa-chart-line me-1"></i>Voir le détail
                                            <i class="fas fa-arrow-right ms-1" style="font-size:.7rem;"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Filters Section -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-filter"></i>
                    Filtres de recherche
                </div>
                <div class="main-card-subtitle">Affinez votre recherche d'émargements</div>
            </div>
            <div class="main-card-body">
                <form method="GET" action="{{ route('esbtp.teacher-attendance.report') }}" class="filter-form">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" value="{{ request('date') }}">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Enseignant</label>
                            <select class="form-select" name="teacher_id">
                                <option value="">Tous les enseignants</option>
                                @foreach($teachers ?? [] as $teacher)
                                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Matière</label>
                            <select class="form-select" name="matiere_id">
                                <option value="">Toutes les matières</option>
                                @foreach($matieres ?? [] as $matiere)
                                    <option value="{{ $matiere->id }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                        {{ $matiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Classe</label>
                            <select class="form-select" name="classe_id">
                                <option value="">Toutes les classes</option>
                                @foreach($classes ?? [] as $classe)
                                    <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                        {{ $classe->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Statut d'émargement</label>
                            <select class="form-select" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Présent</option>
                                <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>En retard</option>
                                <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="not_signed" {{ request('status') == 'not_signed' ? 'selected' : '' }}>Non émargé</option>
                            </select>
                        </div>
                        
                        <div class="col-md-1">
                            <label class="form-label">Emplois</label>
                            <select class="form-select" name="emploi_status">
                                <option value="" {{ request('emploi_status') == '' ? 'selected' : '' }}>Tous</option>
                                <option value="active_only" {{ request('emploi_status') == 'active_only' ? 'selected' : '' }}>Actifs</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn-acasi primary">
                                    <i class="fas fa-search"></i>Filtrer
                                </button>
                                <a href="{{ route('esbtp.teacher-attendance.report') }}" class="btn-acasi secondary">
                                    <i class="fas fa-times"></i>Réinitialiser
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des cours planifiés -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste des cours planifiés
                </div>
                <div class="main-card-subtitle">{{ $seances->total() ?? 0 }} cours trouvé(s) - Année {{ $anneeEnCours->name ?? 'Non définie' }}</div>
            </div>
            <div class="main-card-body">
                @if(isset($seances) && $seances->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="select-all-seances" class="form-check-input" title="Sélectionner toutes les séances passées">
                                    </th>
                                    <th>Enseignant</th>
                                    <th>Matière</th>
                                    <th>Classe</th>
                                    <th>Séance</th>
                                    <th>Date/Heure</th>
                                    <th>Emploi du temps</th>
                                    <th>Statut d'émargement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($seances as $seance)
                                    @include('esbtp.teacher-attendance.partials.seance-row', ['seance' => $seance])
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">
                            Affichage de {{ $seances->firstItem() ?? 0 }} à {{ $seances->lastItem() ?? 0 }} 
                            sur {{ $seances->total() ?? 0 }} résultats
                        </div>
                        {{ $seances->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-week fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune séance de cours trouvée</h5>
                        <p class="text-muted">Modifiez vos critères de recherche ou vérifiez les filtres appliqués.<br>
                        <small class="text-info">Note: Seuls les cours sont affichés (pas les devoirs, récréations, etc.)</small></p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modals pour les détails (redesign moderne) -->
<style>
.detail-modal-hero {
    background: linear-gradient(135deg, #0453cb 0%, #1b64d4 60%, #5e91de 100%);
    border-radius: 0;
    padding: 24px 28px 20px;
    color: white;
    position: relative; overflow: hidden;
}
.detail-modal-hero::after {
    content: ''; position: absolute; right: -50px; top: -50px;
    width: 180px; height: 180px; border-radius: 50%;
    background: rgba(255,255,255,.06); pointer-events: none;
}
.detail-modal-hero .teacher-initials {
    width: 54px; height: 54px; border-radius: 50%;
    background: rgba(255,255,255,.2); border: 2px solid rgba(255,255,255,.35);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1.15rem; flex-shrink: 0;
}
.detail-modal-hero-name { font-size: 1.15rem; font-weight: 800; line-height: 1.2; }
.detail-modal-hero-sub  { font-size: .82rem; opacity: .78; margin-top: 2px; }
.detail-modal-status-pill {
    display: inline-flex; align-items: center; gap: 6px;
    border-radius: 99px; padding: 5px 14px;
    font-size: .82rem; font-weight: 700;
    transition: background .2s;
}
.detail-modal-status-pill.present    { background:rgba(16,185,129,.22); border:1px solid rgba(16,185,129,.45); }
.detail-modal-status-pill.late       { background:rgba(245,158,11,.22); border:1px solid rgba(245,158,11,.45); }
.detail-modal-status-pill.absent     { background:rgba(239,68,68,.22);  border:1px solid rgba(239,68,68,.45); }
.detail-modal-status-pill.not_signed { background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3); }
.detail-modal-action-btn {
    background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3);
    color: white; border-radius: 99px; padding: 5px 14px;
    font-size: .78rem; font-weight: 600; cursor: pointer; transition: background .15s;
}
.detail-modal-action-btn:hover { background: rgba(255,255,255,.28); }
.detail-modal-action-btn.danger { background: rgba(239,68,68,.25); border-color: rgba(239,68,68,.5); }
.detail-modal-action-btn.danger:hover { background: rgba(239,68,68,.4); }

.detail-info-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 12px; padding: 20px 24px;
}
.detail-info-item {
    display: flex; align-items: flex-start; gap: 12px;
    background: #f8fafc; border-radius: 10px; padding: 12px 14px;
    border: 1px solid #e2e8f0;
}
.detail-info-item.full { grid-column: 1 / -1; }
.detail-info-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; flex-shrink: 0;
}
.detail-info-label { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: 2px; }
.detail-info-value { font-size: .88rem; font-weight: 600; color: #1e293b; }
@media (max-width: 576px) {
    .detail-info-grid { grid-template-columns: 1fr; }
    .detail-info-item.full { grid-column: 1; }
}
</style>

@if(isset($seances))
@foreach($seances as $seance)
    @php
        // Même logique de priorité que seance-row.blade.php
        $today3 = \Carbon\Carbon::today();
        $attDetail = $seance->teacherAttendances->first(function($a) use ($today3) {
            $d = $a->date instanceof \Carbon\Carbon ? $a->date : \Carbon\Carbon::parse($a->date);
            return $d->isSameDay($today3);
        });
        if (!$attDetail) {
            $attDetail = $seance->teacherAttendances->first(function($a) use ($seance) {
                $d = $a->date instanceof \Carbon\Carbon ? $a->date : \Carbon\Carbon::parse($a->date);
                return $d->isSameDay(\Carbon\Carbon::parse($seance->date_seance));
            });
        }
        if (!$attDetail) {
            $attDetail = $seance->teacherAttendances->sortByDesc('created_at')->first();
        }

        if (!$attDetail) continue; // pas d'attendance = pas de modal détail

        $detailStatus = $attDetail->status ?? 'not_signed';
        $detailStatusMap = [
            'present'    => ['icon'=>'check-circle',  'label'=>'Présent',    'cls'=>'present'],
            'late'       => ['icon'=>'clock',         'label'=>'En retard',  'cls'=>'late'],
            'absent'     => ['icon'=>'times-circle',  'label'=>'Absent',     'cls'=>'absent'],
            'not_signed' => ['icon'=>'minus-circle',  'label'=>'Non émargé', 'cls'=>'not_signed'],
        ];
        $dsm = $detailStatusMap[$detailStatus] ?? $detailStatusMap['not_signed'];

        // Initiales enseignant
        $nameParts = preg_split('/\s+/', trim($seance->teacher?->user?->name ?? 'NA'));
        $initials2 = strtoupper(collect($nameParts)->filter()->take(2)->map(fn($p) => mb_substr($p,0,1))->implode(''));
    @endphp
    <div class="modal fade" id="detailModal{{ $seance->id }}" tabindex="-1"
         data-seance-id="{{ $seance->id }}" data-initial-status="{{ $detailStatus }}">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content" style="border:none; border-radius:16px; overflow:hidden;">

                {{-- Hero header --}}
                <div class="detail-modal-hero">
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal" style="position:absolute;top:16px;right:20px;z-index:2;"></button>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="teacher-initials">{{ $initials2 }}</div>
                        <div>
                            <div class="detail-modal-hero-name">{{ $seance->teacher?->user?->name ?? 'N/A' }}</div>
                            <div class="detail-modal-hero-sub">
                                {{ $seance->matiere?->name ?? 'N/A' }}
                                · {{ $seance->emploiTemps?->classe?->name ?? 'N/A' }}
                            </div>
                        </div>
                    </div>

                    {{-- Statut + boutons action --}}
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="detail-modal-status-pill {{ $dsm['cls'] }}" id="detailStatusBadge{{ $seance->id }}">
                            <i class="fas fa-{{ $dsm['icon'] }}" style="font-size:.78rem;"></i>
                            <span>{{ $dsm['label'] }}</span>
                        </span>

                        {{-- Actions rapides dans le modal --}}
                        <div class="d-flex gap-2" id="detailModalActions{{ $seance->id }}">
                            @if($detailStatus !== 'present')
                            <button type="button" class="detail-modal-action-btn modal-mark-btn"
                                    data-seance-id="{{ $seance->id }}" data-status="present" data-type="start"
                                    title="Marquer présent">
                                <i class="fas fa-check me-1"></i>Présent
                            </button>
                            @endif
                            @if($detailStatus !== 'absent')
                            <button type="button" class="detail-modal-action-btn danger modal-mark-btn"
                                    data-seance-id="{{ $seance->id }}" data-status="absent" data-type="start"
                                    title="Marquer absent">
                                <i class="fas fa-user-times me-1"></i>Absent
                            </button>
                            @endif
                        </div>
                        <div class="detail-modal-spinner d-none" id="detailSpinner{{ $seance->id }}">
                            <div class="spinner-border spinner-border-sm text-white" role="status"></div>
                        </div>
                    </div>
                </div>

                {{-- Info grid --}}
                <div class="detail-info-grid">
                    {{-- Date séance --}}
                    <div class="detail-info-item">
                        <div class="detail-info-icon" style="background:rgba(4,83,203,.1);color:#0453cb;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div>
                            <div class="detail-info-label">Date de la séance</div>
                            <div class="detail-info-value">{{ $seance->getDateCompleteFormattee() }}</div>
                        </div>
                    </div>

                    {{-- Horaires --}}
                    <div class="detail-info-item">
                        <div class="detail-info-icon" style="background:rgba(245,158,11,.1);color:#d97706;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <div class="detail-info-label">Horaires</div>
                            <div class="detail-info-value">
                                {{ $seance->heure_debut ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') : 'N/A' }}
                                – {{ $seance->heure_fin ? \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') : 'N/A' }}
                            </div>
                        </div>
                    </div>

                    {{-- Date émargement --}}
                    <div class="detail-info-item">
                        <div class="detail-info-icon" style="background:rgba(16,185,129,.1);color:#059669;">
                            <i class="fas fa-signature"></i>
                        </div>
                        <div>
                            <div class="detail-info-label">Date d'émargement</div>
                            <div class="detail-info-value" id="detailDate{{ $seance->id }}">
                                {{ $attDetail->validated_at?->format('d/m/Y H:i') ?? ($attDetail->created_at?->format('d/m/Y H:i') ?? 'N/A') }}
                            </div>
                        </div>
                    </div>

                    @if($seance->salle)
                    {{-- Salle --}}
                    <div class="detail-info-item">
                        <div class="detail-info-icon" style="background:rgba(100,116,139,.1);color:#475569;">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <div>
                            <div class="detail-info-label">Salle</div>
                            <div class="detail-info-value">{{ $seance->salle }}</div>
                        </div>
                    </div>
                    @endif

                    {{-- IP --}}
                    <div class="detail-info-item">
                        <div class="detail-info-icon" style="background:rgba(99,102,241,.1);color:#4f46e5;">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <div>
                            <div class="detail-info-label">Adresse IP</div>
                            <div class="detail-info-value">{{ $attDetail->ip_address ?? 'N/A' }}</div>
                        </div>
                    </div>

                    {{-- Appareil --}}
                    <div class="detail-info-item">
                        <div class="detail-info-icon" style="background:rgba(239,68,68,.08);color:#dc2626;">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div>
                            <div class="detail-info-label">Appareil</div>
                            <div class="detail-info-value" style="word-break:break-all;">
                                {{ $attDetail->device_info ?? 'N/A' }}
                            </div>
                        </div>
                    </div>

                    @if($attDetail->latitude && $attDetail->longitude)
                    <div class="detail-info-item full">
                        <div class="detail-info-icon" style="background:rgba(245,158,11,.1);color:#d97706;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <div class="detail-info-label">Localisation GPS</div>
                            <div class="detail-info-value">{{ $attDetail->latitude }}, {{ $attDetail->longitude }}</div>
                        </div>
                    </div>
                    @endif

                    @if($attDetail->notes)
                    <div class="detail-info-item full">
                        <div class="detail-info-icon" style="background:rgba(4,83,203,.1);color:#0453cb;">
                            <i class="fas fa-sticky-note"></i>
                        </div>
                        <div>
                            <div class="detail-info-label">Notes</div>
                            <div class="detail-info-value" style="font-weight:400;color:#334155;">{{ $attDetail->notes }}</div>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:12px 24px;">
                    <a href="{{ route('esbtp.seances-cours.show', $seance->id) }}"
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-calendar-day me-1"></i>Voir la séance
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endif

<!-- Modals rapport de cours (ESBTPSessionReport) -->
@if(isset($seances))
@foreach($seances as $seance)
    @if($seance->sessionReport && $seance->sessionReport->status === 'submitted')
    @php $report = $seance->sessionReport; @endphp
    <div class="modal fade" id="rapportModal{{ $seance->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #0453cb, #5e91de); color: white;">
                    <div>
                        <h5 class="modal-title mb-0">
                            <i class="fas fa-file-alt me-2"></i>Rapport de cours
                        </h5>
                        <small style="opacity:0.85;">
                            {{ $seance->matiere?->name }} · {{ $seance->emploiTemps?->classe?->name }}
                            · {{ \Carbon\Carbon::parse($seance->date_seance)->format('d/m/Y') }}
                        </small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-2 mb-4 flex-wrap">
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Rapport soumis</span>
                        <span class="badge bg-{{ $report->behavior_badge_color ?? 'secondary' }}">
                            Comportement : {{ $report->student_behavior_label ?? $report->student_behavior }}
                        </span>
                        <span class="badge bg-secondary">
                            Soumis le {{ $report->submitted_at ? \Carbon\Carbon::parse($report->submitted_at)->format('d/m/Y à H:i') : '-' }}
                        </span>
                    </div>

                    @if($report->content_summary)
                    <div class="mb-3 p-3 rounded-3 border-start border-4 border-primary bg-light">
                        <h6 class="fw-bold text-primary mb-2"><i class="fas fa-book-open me-2"></i>Contenu enseigné</h6>
                        <p class="mb-0">{{ $report->content_summary }}</p>
                    </div>
                    @endif

                    @if($report->teaching_methods)
                    <div class="mb-3 p-3 rounded-3 border-start border-4 border-info bg-light">
                        <h6 class="fw-bold text-info mb-2"><i class="fas fa-chalkboard-teacher me-2"></i>Méthodes pédagogiques</h6>
                        <p class="mb-0">{{ $report->teaching_methods }}</p>
                    </div>
                    @endif

                    @if($report->difficulties_encountered)
                    <div class="mb-3 p-3 rounded-3 border-start border-4 border-warning bg-light">
                        <h6 class="fw-bold text-warning mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Difficultés rencontrées</h6>
                        <p class="mb-0">{{ $report->difficulties_encountered }}</p>
                    </div>
                    @endif

                    @if($report->homework_assigned)
                    <div class="mb-3 p-3 rounded-3 border-start border-4 border-success bg-light">
                        <h6 class="fw-bold text-success mb-2"><i class="fas fa-tasks me-2"></i>Devoirs assignés</h6>
                        <p class="mb-0">{{ $report->homework_assigned }}</p>
                    </div>
                    @endif

                    @if($report->next_session_preparation)
                    <div class="p-3 rounded-3 border-start border-4 border-secondary bg-light">
                        <h6 class="fw-bold text-secondary mb-2"><i class="fas fa-forward me-2"></i>Préparation prochaine séance</h6>
                        <p class="mb-0">{{ $report->next_session_preparation }}</p>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach
@endif

<!-- Barre d'actions groupées (bulk edit) -->
<div id="bulk-actions-bar" style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
     background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; padding: 15px 30px;
     border-radius: 50px; box-shadow: 0 10px 40px rgba(4, 83, 203, 0.4); z-index: 1050;
     animation: slideUp 0.3s ease-out; white-space: nowrap;">
    <div style="display: flex; align-items: center; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
            <span id="selected-count" style="font-weight: 600; font-size: 1.1rem;">0</span>
            <span style="opacity: 0.9;">séance(s) sélectionnée(s)</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-light btn-sm" onclick="bulkMarkStatus('present')"
                    style="padding: 8px 20px; border-radius: 25px; font-weight: 600;">
                <i class="fas fa-check me-1"></i>Marquer Présent
            </button>
            <button type="button" onclick="bulkMarkStatus('absent')"
                    style="padding: 8px 20px; border-radius: 25px; font-weight: 600; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; cursor: pointer;">
                <i class="fas fa-user-times me-1"></i>Marquer Absent
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" onclick="clearBulkSelection()"
                    style="padding: 8px 20px; border-radius: 25px; font-weight: 600;">
                <i class="fas fa-times me-1"></i>Annuler
            </button>
        </div>
    </div>
</div>

<!-- Modal de génération de code -->
<div class="modal fade" id="generateCodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('esbtp.admin.attendance.generate-code') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Générer un nouveau code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Date d'expiration</label>
                        <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" 
                               value="{{ now()->addDay()->format('Y-m-d\TH:i') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (optionnel)</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Ex: Code pour le cours de mathématiques..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Générer le code</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
/* Animation travelling light pour les lignes de séance */
tr[data-seance-id] {
    position: relative;
    overflow: hidden;
}

tr[data-seance-id].is-loading {
    opacity: 0.85;
}

.seance-actions-wrapper {
    position: relative;
}

.seance-actions-wrapper.is-loading .seance-status-badges,
.seance-actions-wrapper.is-loading .seance-quick-actions {
    display: none !important;
}

.seance-actions-wrapper.is-loading .seance-actions-spinner {
    display: flex !important;
    align-items: center;
    justify-content: center;
}

/* Travelling light effect */
.seance-row-highlight {
    position: absolute;
    top: 0;
    left: -80%;
    width: 160%;
    height: 100%;
    opacity: 0;
    pointer-events: none;
    transform: translateX(-65%) skewX(-12deg);
    background: linear-gradient(90deg, rgba(40, 167, 69, 0) 0%, rgba(40, 167, 69, 0.75) 50%, rgba(40, 167, 69, 0) 100%);
    transition: opacity 0.2s ease;
    z-index: 5;
}

.seance-row-highlight.absent {
    background: linear-gradient(90deg, rgba(220, 53, 69, 0) 0%, rgba(220, 53, 69, 0.75) 50%, rgba(220, 53, 69, 0) 100%);
}

.seance-row-highlight.animate {
    animation: seance-row-highlight-move 3.2s ease-out forwards;
}

.seance-row-flash {
    animation: seance-row-flash 0.8s ease-in-out;
}

@keyframes seance-row-highlight-move {
    0% {
        opacity: 0;
        transform: translateX(-65%) skewX(-12deg);
    }
    18% {
        opacity: 0.92;
    }
    55% {
        opacity: 0.72;
    }
    100% {
        opacity: 0;
        transform: translateX(115%) skewX(-12deg);
    }
}

@keyframes seance-row-flash {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(40, 167, 69, 0.12);
    }
    100% {
        background-color: transparent;
    }
}

.seance-row-flash.absent {
    animation-name: seance-row-flash-absent;
}

@keyframes seance-row-flash-absent {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(220, 53, 69, 0.12);
    }
    100% {
        background-color: transparent;
    }
}
</style>

<script>
(function() {
    const SEANCE_HIGHLIGHT_DURATION = 3200;
    const SEANCE_STATUS_PASS_RATIO = 0.8;

    /**
     * Met à jour l'état de chargement d'une ligne de séance
     */
    function setSeanceRowLoadingState(seanceId, isLoading) {
        const row = document.querySelector(`tr[data-seance-id="${seanceId}"]`);
        if (!row) return;

        const actionsWrapper = row.querySelector('.seance-actions-wrapper');
        if (actionsWrapper) {
            actionsWrapper.classList.toggle('is-loading', Boolean(isLoading));
        }
        row.classList.toggle('is-loading', Boolean(isLoading));
    }

    /**
     * Déclenche l'animation travelling light sur une ligne de séance
     */
    function triggerSeanceRowHighlight(row, actionType, options = {}) {
        if (!row) return;

        const { onStatusPassed } = options;

        row.classList.remove('seance-row-flash', 'absent');
        void row.offsetWidth; // Force reflow

        const highlight = document.createElement('div');
        highlight.className = 'seance-row-highlight';
        if (actionType === 'absent') {
            highlight.classList.add('absent');
        }

        row.appendChild(highlight);

        requestAnimationFrame(() => {
            highlight.classList.add('animate');
        });

        if (typeof onStatusPassed === 'function') {
            setTimeout(() => {
                onStatusPassed(highlight);
            }, SEANCE_HIGHLIGHT_DURATION * SEANCE_STATUS_PASS_RATIO);
        }

        const cleanup = () => {
            highlight.removeEventListener('animationend', cleanup);
            highlight.remove();
        };

        highlight.addEventListener('animationend', cleanup);

        row.classList.add('seance-row-flash');
        if (actionType === 'absent') {
            row.classList.add('absent');
        }

        setTimeout(() => {
            row.classList.remove('seance-row-flash', 'absent');
        }, 1200);
    }

    /**
     * Rafraîchit une ligne de séance après update statut
     */
    window.refreshSeanceLigne = function(seanceId, actionType = 'present') {
        debugLog('🔄 Refresh ligne séance:', seanceId, 'action:', actionType);

        const refreshUrl = `{{ url('/esbtp/teacher-attendance/seance') }}/${seanceId}/refresh-ligne`;
        const existingRow = document.querySelector(`tr[data-seance-id="${seanceId}"]`);

        setSeanceRowLoadingState(seanceId, true);

        fetch(refreshUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Réponse serveur invalide');
            }

            const template = document.createElement('template');
            template.innerHTML = data.html.trim();

            let rowFragment = template.content.querySelector(`tr[data-seance-id="${seanceId}"]`);
            if (!rowFragment) {
                rowFragment = template.content.querySelector('tr[data-seance-id]');
            }

            if (!rowFragment) {
                throw new Error('HTML retourné sans ligne de séance valide');
            }

            const newRow = rowFragment.cloneNode(true);
            const clonedCells = Array.from(newRow.children).map(cell => cell.cloneNode(true));

            if (!existingRow || !existingRow.parentNode) {
                const tbody = document.querySelector('tbody');
                if (tbody) {
                    tbody.appendChild(newRow);
                }
                setSeanceRowLoadingState(seanceId, false);
                triggerSeanceRowHighlight(newRow, actionType);
                debugLog('🎉 Ligne rafraîchie (nouvelle ligne ajoutée):', seanceId);
                return;
            }

            let contentUpdated = false;

            const applyUpdatedContent = (highlightEl = null) => {
                if (contentUpdated) return;
                contentUpdated = true;

                const highlightNode = highlightEl && highlightEl instanceof Node ? highlightEl : existingRow.querySelector('.seance-row-highlight');
                const existingCells = Array.from(existingRow.children).filter(child => child !== highlightNode);

                existingCells.forEach((cell, index) => {
                    const replacement = clonedCells[index];
                    if (replacement) {
                        cell.replaceWith(replacement);
                    } else {
                        cell.remove();
                    }
                });

                const extraCells = clonedCells.slice(existingCells.length);
                if (extraCells.length > 0) {
                    const fragment = document.createDocumentFragment();
                    extraCells.forEach(node => fragment.appendChild(node));

                    if (highlightNode && highlightNode.parentNode) {
                        highlightNode.parentNode.insertBefore(fragment, highlightNode);
                    } else {
                        existingRow.appendChild(fragment);
                    }
                }

                if (highlightNode && highlightNode.parentNode !== existingRow) {
                    existingRow.appendChild(highlightNode);
                }

                setSeanceRowLoadingState(seanceId, false);

                existingRow.classList.add('seance-row-flash');
                if (actionType === 'absent') {
                    existingRow.classList.add('absent');
                }
                setTimeout(() => {
                    existingRow.classList.remove('seance-row-flash', 'absent');
                }, 1200);
            };

            triggerSeanceRowHighlight(existingRow, actionType, {
                onStatusPassed: (highlightEl) => {
                    applyUpdatedContent(highlightEl);
                }
            });

            // Fallback
            setTimeout(() => {
                if (!contentUpdated) {
                    applyUpdatedContent();
                }
            }, SEANCE_HIGHLIGHT_DURATION + 100);

            debugLog('🎉 Ligne rafraîchie avec succès:', seanceId);
        })
        .catch(error => {
            debugError('❌ Erreur refresh ligne:', error);
            setSeanceRowLoadingState(seanceId, false);
            alert('Erreur lors de la mise à jour: ' + error.message);
        });
    };

    /**
     * Initialisation au chargement
     */
    document.addEventListener('DOMContentLoaded', function() {
        debugLog('✅ Scripts séances initialisés');

        // Auto-submit form when filters change (optional)
        const filterSelects = document.querySelectorAll('.filter-form select, .filter-form input[type="date"]');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                // Uncomment to enable auto-submit
                // this.form.submit();
            });
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Event delegation pour les boutons mark-status
        document.addEventListener('click', function(e) {
            debugLog('🖱️ Click détecté sur:', e.target);
            const btn = e.target.closest('.mark-status-btn');
            debugLog('🔍 Bouton trouvé:', btn);
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            const seanceId = btn.getAttribute('data-seance-id');
            const status = btn.getAttribute('data-status');
            const type = btn.getAttribute('data-type') || 'start';

            if (!seanceId || !status) {
                debugError('❌ Pas de seance ID ou status sur le bouton');
                return;
            }

            const actionLabel = status === 'present' ? 'présent' : 'absent';
            if (!confirm(`Êtes-vous sûr de vouloir marquer cet enseignant ${actionLabel} ?`)) {
                return;
            }

            debugLog('🔄 Marquage statut:', { seanceId, status, type });

            setSeanceRowLoadingState(seanceId, true);

            const updateUrl = `{{ url('/esbtp/teacher-attendance/seance') }}/${seanceId}/update-status`;

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status, type })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                debugLog('📦 Réponse serveur:', data);
                if (data.success) {
                    debugLog('✅ Statut mis à jour, refresh ligne');
                    // Rafraîchir la ligne avec animation
                    window.refreshSeanceLigne(seanceId, status === 'absent' ? 'absent' : 'present');
                    // Mettre à jour le modal détail si ouvert
                    window.updateDetailModalStatus && window.updateDetailModalStatus(seanceId, status);
                } else {
                    setSeanceRowLoadingState(seanceId, false);
                    alert('Erreur: ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                debugError('❌ Erreur update statut:', error);
                setSeanceRowLoadingState(seanceId, false);
                alert('Erreur lors de la mise à jour: ' + error.message);
            });
        }, true); // Capture phase

        // ── Boutons action rapide dans le modal détail ──────────────────
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.modal-mark-btn');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();

            const seanceId = btn.dataset.seanceId;
            const status   = btn.dataset.status;
            const type     = btn.dataset.type || 'start';
            const label    = status === 'present' ? 'présent' : 'absent';

            if (!confirm(`Marquer cet enseignant ${label} ?`)) return;

            // Spinner dans le modal
            const actionsDiv = document.getElementById('detailModalActions' + seanceId);
            const spinner    = document.getElementById('detailSpinner' + seanceId);
            if (actionsDiv) actionsDiv.classList.add('d-none');
            if (spinner)    spinner.classList.remove('d-none');

            const url = `{{ url('/esbtp/teacher-attendance/seance') }}/${seanceId}/update-status`;
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status, type })
            })
            .then(r => r.json())
            .then(data => {
                if (spinner) spinner.classList.add('d-none');
                if (data.success) {
                    // 1. Mettre à jour le badge statut dans le modal
                    updateDetailModalStatus(seanceId, status);
                    // 2. Mettre à jour la ligne dans le tableau (fonction existante)
                    window.refreshSeanceLigne && window.refreshSeanceLigne(seanceId, status === 'absent' ? 'absent' : 'present');
                } else {
                    if (actionsDiv) actionsDiv.classList.remove('d-none');
                    alert('Erreur : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(err => {
                if (spinner)    spinner.classList.add('d-none');
                if (actionsDiv) actionsDiv.classList.remove('d-none');
                alert('Erreur réseau : ' + err.message);
            });
        }, true);

        debugLog('✅ Event listeners installés');
    });
})();

/**
 * Met à jour le badge statut + boutons d'action dans le modal détail
 * Appelée aussi depuis refreshSeanceLigne via l'event custom
 */
window.updateDetailModalStatus = function(seanceId, status) {
    const statusMap = {
        present:    { icon: 'check-circle',  label: 'Présent',    cls: 'present' },
        late:       { icon: 'clock',         label: 'En retard',  cls: 'late' },
        absent:     { icon: 'times-circle',  label: 'Absent',     cls: 'absent' },
        not_signed: { icon: 'minus-circle',  label: 'Non émargé', cls: 'not_signed' },
    };
    const m = statusMap[status] || statusMap.not_signed;

    // Badge statut
    const badge = document.getElementById('detailStatusBadge' + seanceId);
    if (badge) {
        badge.className = 'detail-modal-status-pill ' + m.cls;
        badge.innerHTML = `<i class="fas fa-${m.icon}" style="font-size:.78rem;"></i><span>${m.label}</span>`;
    }

    // Reconstruire les boutons d'action
    const actionsDiv = document.getElementById('detailModalActions' + seanceId);
    if (actionsDiv) {
        let html = '';
        if (status !== 'present') {
            html += `<button type="button" class="detail-modal-action-btn modal-mark-btn"
                data-seance-id="${seanceId}" data-status="present" data-type="start" title="Marquer présent">
                <i class="fas fa-check me-1"></i>Présent</button>`;
        }
        if (status !== 'absent') {
            html += `<button type="button" class="detail-modal-action-btn danger modal-mark-btn"
                data-seance-id="${seanceId}" data-status="absent" data-type="start" title="Marquer absent">
                <i class="fas fa-user-times me-1"></i>Absent</button>`;
        }
        actionsDiv.innerHTML = html;
        actionsDiv.classList.remove('d-none');
    }
};
</script>

<style>
@keyframes slideUp {
    from { transform: translateX(-50%) translateY(20px); opacity: 0; }
    to   { transform: translateX(-50%) translateY(0); opacity: 1; }
}
</style>
<script>
// === BULK EDIT ===
let selectedSeances = new Set();

window.toggleSeanceSelection = function() {
    selectedSeances.clear();
    document.querySelectorAll('.seance-checkbox:checked').forEach(cb => {
        selectedSeances.add(cb.value);
    });
    updateBulkBar();
};

document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all-seances');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.seance-checkbox').forEach(cb => {
                cb.checked = this.checked;
            });
            toggleSeanceSelection();
        });
    }
});

function updateBulkBar() {
    const bar = document.getElementById('bulk-actions-bar');
    const count = document.getElementById('selected-count');
    if (bar) {
        if (selectedSeances.size > 0) {
            bar.style.display = 'block';
            if (count) count.textContent = selectedSeances.size;
        } else {
            bar.style.display = 'none';
        }
    }
}

window.clearBulkSelection = function() {
    document.querySelectorAll('.seance-checkbox').forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('select-all-seances');
    if (selectAll) selectAll.checked = false;
    selectedSeances.clear();
    updateBulkBar();
};

window.bulkMarkStatus = function(status) {
    if (selectedSeances.size === 0) return;

    const ids = Array.from(selectedSeances);
    const label = status === 'present' ? 'présent' : 'absent';

    if (!confirm(`Marquer ${ids.length} séance(s) comme ${label} ?`)) return;

    fetch('{{ url("/esbtp/teacher-attendance/bulk-update-status") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ seance_ids: ids, status: status })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (typeof showNotification === 'function') {
                showNotification(data.message, 'success');
            }
            // Rafraîchir chaque ligne
            ids.forEach(id => window.refreshSeanceLigne && window.refreshSeanceLigne(id, status));
            clearBulkSelection();
        } else {
            alert(data.message || 'Erreur lors de la mise à jour');
        }
    })
    .catch(() => alert('Erreur réseau. Veuillez réessayer.'));
};
</script>
@endpush
