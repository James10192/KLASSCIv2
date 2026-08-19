@extends('layouts.app')

@section('title', 'Dashboard Directeur des études - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    body { background-color: var(--background); }
    .de-header {
        background: var(--primary);
        color: #fff;
        border-radius: var(--radius-medium);
        padding: var(--space-xl) var(--space-lg);
        margin-bottom: var(--space-lg);
    }
    .de-header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: var(--space-md);
    }
    .de-header-left { display: flex; align-items: center; gap: var(--space-lg); }
    .de-avatar {
        width: 64px; height: 64px;
        border-radius: var(--radius-circle);
        background: rgba(255,255,255,0.16);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
    }
    .de-header h1 { color: #fff; margin: 0; font-size: 1.35rem; font-weight: 700; }
    .de-header .header-sub { color: rgba(255,255,255,0.82); margin: 4px 0 0; font-size: 0.88rem; }
    .de-header-actions { display: flex; align-items: center; gap: var(--space-sm); flex-wrap: wrap; }
    .de-badge {
        background: rgba(255,255,255,0.14);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.28);
        border-radius: 20px;
        padding: 6px 14px;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .de-btn-refresh {
        width: 38px; height: 38px;
        border-radius: var(--radius-small);
        border: 1px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.1);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
    }
    .de-btn-refresh.de-spinning i { animation: de-spin 0.8s linear infinite; }
    @keyframes de-spin { to { transform: rotate(360deg); } }
    .de-alert {
        display: flex; align-items: center; gap: var(--space-md);
        background: var(--surface);
        border: 1px solid var(--border);
        border-left-width: 4px;
        border-radius: var(--radius-medium);
        padding: var(--space-md) var(--space-lg);
        margin-bottom: var(--space-md);
    }
    .de-alert--warning { border-left-color: #d97706; }
    .de-alert--danger { border-left-color: #dc2626; }
    .de-alert-title { font-weight: 700; font-size: 0.95rem; }
    .de-alert-text { font-size: 0.82rem; color: var(--text-secondary); }
    .de-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }
    .de-kpi {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        min-height: 148px;
        display: flex;
        flex-direction: column;
    }
    .de-kpi-label { font-size: 0.78rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
    .de-kpi-value { font-size: 1.8rem; font-weight: 700; color: var(--text-primary); margin: 8px 0; }
    .de-kpi-sub { font-size: 0.78rem; color: var(--text-secondary); }
    .de-kpi-link { margin-top: auto; font-size: 0.82rem; font-weight: 600; color: var(--primary); text-decoration: none; }
    .de-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        margin-bottom: var(--space-md);
    }
    .de-card-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: var(--space-md) var(--space-lg);
        border-bottom: 1px solid var(--border);
    }
    .de-card-title { font-weight: 700; margin: 0; font-size: 1rem; }
    .de-card-body { padding: var(--space-lg); }
    .de-stat-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: var(--space-md); }
    .de-stat-value { font-size: 1.4rem; font-weight: 700; }
    .de-stat-label { font-size: 0.78rem; color: var(--text-secondary); }
    .de-actions-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--space-sm); }
    .de-action-btn {
        display: flex; align-items: center; gap: 8px;
        min-height: 44px;
        border: 1px solid var(--border);
        border-radius: var(--radius-small);
        padding: 10px 12px;
        color: var(--text-primary);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.86rem;
    }
    @media (max-width: 991px) {
        .de-kpi-grid, .de-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575px) {
        .de-kpi-grid, .de-stat-grid, .de-actions-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
@php
    $rate = $attendanceStats['attendance_rate'] ?? 0;
    $health = $academicHealth ?? [];
@endphp
<div class="main-content">
    <div class="de-header">
        <div class="de-header-inner">
            <div class="de-header-left">
                <div class="de-avatar"><i class="fas fa-graduation-cap"></i></div>
                <div>
                    <h1>Directeur des études</h1>
                    <p class="header-sub">Bonjour, <strong>{{ $user->name }}</strong>. Pilotage pédagogique de l'établissement.</p>
                </div>
            </div>
            <div class="de-header-actions">
                <span class="de-badge"><i class="fas fa-calendar me-1"></i>{{ $anneeEnCours->name ?? 'Année non définie' }}</span>
                <button class="de-btn-refresh" id="de-refresh-btn" type="button" onclick="deRefreshData()" title="Actualiser">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    @if(($pendingInscriptionsCount ?? 0) > 0)
        <div class="de-alert de-alert--warning">
            <div>
                <div class="de-alert-title">{{ $pendingInscriptionsCount }} inscription(s) en attente</div>
                <div class="de-alert-text">Ces dossiers doivent être validés pour finaliser l'admission.</div>
            </div>
            <a href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}" class="btn-acasi warning" style="margin-left:auto;">Consulter</a>
        </div>
    @endif

    @if(($evaluationsSansNotesCount ?? 0) > 0)
        <div class="de-alert de-alert--danger">
            <div>
                <div class="de-alert-title">{{ $evaluationsSansNotesCount }} évaluation(s) passée(s) sans notes</div>
                <div class="de-alert-text">Des évaluations déjà passées n'ont toujours aucune note enregistrée.</div>
            </div>
            <a href="{{ route('esbtp.evaluations.index') }}" class="btn-acasi primary" style="margin-left:auto;">Consulter</a>
        </div>
    @endif

    <div class="de-kpi-grid">
        <div class="de-kpi">
            <div class="de-kpi-label">Inscriptions en attente</div>
            <div class="de-kpi-value" data-kpi="pendingInscriptionsCount">{{ $pendingInscriptionsCount ?? 0 }}</div>
            <a class="de-kpi-link" href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}">Consulter</a>
        </div>
        @can('finance.unpaid_count.view')
        <div class="de-kpi">
            <div class="de-kpi-label">Etudiants non soldes</div>
            <div class="de-kpi-value" data-kpi="unpaidStudentsCount">{{ $unpaidStudentsCount ?? 0 }}</div>
            <div class="de-kpi-sub">Nombre uniquement, sans montant</div>
        </div>
        @endcan
        <div class="de-kpi">
            <div class="de-kpi-label">Inscrits {{ $anneeLabel ?? 'année courante' }}</div>
            <div class="de-kpi-value" data-kpi="totalStudents">{{ $totalStudents ?? 0 }}</div>
            <a class="de-kpi-link" href="{{ route('esbtp.etudiants.index') }}">Voir les étudiants</a>
        </div>
        <div class="de-kpi">
            <div class="de-kpi-label">Évaluations sans notes</div>
            <div class="de-kpi-value" data-kpi="evaluationsSansNotesCount">{{ $evaluationsSansNotesCount ?? 0 }}</div>
            <a class="de-kpi-link" href="{{ route('esbtp.evaluations.index') }}">Ouvrir les évaluations</a>
        </div>
        <div class="de-kpi">
            <div class="de-kpi-label">Taux de présence</div>
            <div class="de-kpi-value" data-kpi="attendanceRate">{{ $rate }}%</div>
            <a class="de-kpi-link" href="{{ route('esbtp.attendances.index') }}">Suivre les présences</a>
        </div>
        <div class="de-kpi">
            <div class="de-kpi-label">Classes actives</div>
            <div class="de-kpi-value" data-kpi="totalClasses">{{ $totalClasses ?? 0 }}</div>
            <a class="de-kpi-link" href="{{ route('esbtp.classes.index') }}">Voir les classes</a>
        </div>
        <div class="de-kpi">
            <div class="de-kpi-label">Enseignants</div>
            <div class="de-kpi-value" data-kpi="totalTeachers">{{ $totalTeachers ?? 0 }}</div>
            <a class="de-kpi-link" href="{{ route('esbtp.enseignants.index') }}">Gérer les enseignants</a>
        </div>
        <div class="de-kpi">
            <div class="de-kpi-label">Emplois du temps</div>
            <div class="de-kpi-value" data-kpi="totalEmploiTemps">{{ $totalEmploiTemps ?? 0 }}</div>
            <div class="de-kpi-sub">{{ $classesWithoutTimetable ?? 0 }} classe(s) sans EDT</div>
            <a class="de-kpi-link" href="{{ route('esbtp.emploi-temps.index') }}">Gérer les EDT</a>
        </div>
        <div class="de-kpi">
            <div class="de-kpi-label">Santé académique</div>
            <div class="de-kpi-value" data-kpi="academicScore">{{ $health['academic_score'] !== null ? $health['academic_score'] : '—' }}</div>
            <div class="de-kpi-sub">{{ $health['open_alerts'] ?? 0 }} alerte(s) ouverte(s)</div>
            <a class="de-kpi-link" href="{{ route('esbtp.pilotage-academique.index') }}">Ouvrir le pilotage</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="de-card">
                <div class="de-card-header">
                    <h2 class="de-card-title">Emplois du temps</h2>
                    <a href="{{ route('esbtp.emploi-temps.index') }}">Tout voir</a>
                </div>
                <div class="de-card-body">
                    <div class="de-stat-grid">
                        <div>
                            <div class="de-stat-value">{{ $activeEmploiTemps ?? 0 }}</div>
                            <div class="de-stat-label">Actifs cette semaine</div>
                        </div>
                        <div>
                            <div class="de-stat-value">{{ $expiredEmploiTemps ?? 0 }}</div>
                            <div class="de-stat-label">Expirés</div>
                        </div>
                        <div>
                            <div class="de-stat-value">{{ $classesWithoutTimetable ?? 0 }}</div>
                            <div class="de-stat-label">Classes sans EDT</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="de-card">
                <div class="de-card-header">
                    <h2 class="de-card-title">Pilotage académique</h2>
                    <a href="{{ route('esbtp.pilotage-academique.index') }}">Ouvrir</a>
                </div>
                <div class="de-card-body">
                    <div class="de-stat-grid">
                        <div>
                            <div class="de-stat-value">{{ $health['sheets_pending'] ?? 0 }}</div>
                            <div class="de-stat-label">Fiches à traiter</div>
                        </div>
                        <div>
                            <div class="de-stat-value">{{ $health['blocking_alerts'] ?? 0 }}</div>
                            <div class="de-stat-label">Alertes bloquantes</div>
                        </div>
                        <div>
                            <div class="de-stat-value">{{ $health['bulletin_blockers'] ?? 0 }}</div>
                            <div class="de-stat-label">Blocages bulletin</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="de-card">
                <div class="de-card-header">
                    <h2 class="de-card-title">Actions rapides</h2>
                </div>
                <div class="de-card-body">
                    <div class="de-actions-grid">
                        <a class="de-action-btn" href="{{ route('esbtp.emploi-temps.index') }}"><i class="fas fa-calendar-alt"></i>Emploi du temps</a>
                        <a class="de-action-btn" href="{{ route('esbtp.planning-general.index') }}"><i class="fas fa-calendar-check"></i>Planning general</a>
                        <a class="de-action-btn" href="{{ route('esbtp.rapports.rentree') }}"><i class="fas fa-door-open"></i>Rapport de rentree</a>
                        <a class="de-action-btn" href="{{ route('esbtp.rapports.trimestre') }}"><i class="fas fa-calendar-week"></i>Fin de trimestre</a>
                        <a class="de-action-btn" href="{{ route('esbtp.rapports.annuel') }}"><i class="fas fa-book"></i>Rapport annuel</a>
                        <a class="de-action-btn" href="{{ route('esbtp.resultats.index') }}"><i class="fas fa-list-ol"></i>Resultats</a>
                        <a class="de-action-btn" href="{{ route('esbtp.notes.index') }}"><i class="fas fa-pen"></i>Notes</a>
                        <a class="de-action-btn" href="{{ route('esbtp.personnel.unified.index') }}"><i class="fas fa-users"></i>Personnel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deRefreshData() {
    var btn = document.getElementById('de-refresh-btn');
    btn.classList.add('de-spinning');
    fetch('{{ route("dashboard.directeur-etudes.data") }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var health = data.academicHealth || {};
        var kpiMap = {
            pendingInscriptionsCount: data.pendingInscriptionsCount,
            unpaidStudentsCount: data.unpaidStudentsCount,
            totalStudents: data.totalStudents,
            evaluationsSansNotesCount: data.evaluationsSansNotesCount,
            attendanceRate: ((data.attendanceStats || {}).attendance_rate || 0) + '%',
            totalClasses: data.totalClasses,
            totalTeachers: data.totalTeachers,
            totalEmploiTemps: data.totalEmploiTemps,
            academicScore: health.academic_score !== null && health.academic_score !== undefined ? health.academic_score : '—'
        };
        Object.keys(kpiMap).forEach(function(key) {
            var el = document.querySelector('[data-kpi="' + key + '"]');
            if (el) el.textContent = kpiMap[key];
        });
        btn.classList.remove('de-spinning');
    })
    .catch(function() {
        btn.classList.remove('de-spinning');
    });
}
</script>
@endpush
