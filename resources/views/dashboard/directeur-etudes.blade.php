@extends('layouts.app')

@section('title', 'Tableau de bord Directeur des études - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
@php
    $rate = $attendanceStats['attendance_rate'] ?? 0;
    $health = $academicHealth ?? [];
    $sansEdt = $classesWithoutTimetable ?? 0;
    $score = $health['academic_score'] ?? null;
@endphp
<div class="main-content">

    <x-role-hero
        icon="fa-graduation-cap"
        :title="'Directeur des études'"
        :subtitle="'Bonjour '.$user->name.'. Pilotage pédagogique de l\'établissement, sans accès aux montants.'"
        :kpis="[
            [
                'icon' => 'fa-user-graduate',
                'value' => $totalStudents ?? 0,
                'label' => 'Inscrits '.($anneeLabel ?? 'année courante'),
                'data_kpi' => 'totalStudents',
                'href' => route('esbtp.etudiants.index'),
            ],
            [
                'icon' => 'fa-chalkboard',
                'value' => $totalClasses ?? 0,
                'label' => 'Classes actives',
                'data_kpi' => 'totalClasses',
                'href' => route('esbtp.classes.index'),
            ],
            [
                'icon' => 'fa-user-tie',
                'value' => $totalTeachers ?? 0,
                'label' => 'Enseignants',
                'data_kpi' => 'totalTeachers',
                'href' => route('esbtp.enseignants.index'),
            ],
            [
                'icon' => 'fa-heart-pulse',
                'value' => $score !== null ? $score : '—',
                'label' => 'Santé académique',
                'data_kpi' => 'academicScore',
                'href' => route('esbtp.pilotage-academique.index'),
            ],
        ]">
        <x-slot:actions>
            <span class="rdx-btn de-year">
                <i class="fas fa-calendar"></i>{{ $anneeEnCours->name ?? 'Année non définie' }}
            </span>
            <button class="rdx-btn" id="de-refresh-btn" type="button" onclick="deRefreshData()" title="Actualiser les indicateurs">
                <i class="fas fa-rotate"></i>Actualiser
            </button>
        </x-slot:actions>
    </x-role-hero>

    {{-- Alertes : uniquement ce qui demande une action --}}
    @if(($pendingInscriptionsCount ?? 0) > 0 || ($evaluationsSansNotesCount ?? 0) > 0 || $sansEdt > 0)
        <div class="de-alerts">
            @if(($pendingInscriptionsCount ?? 0) > 0)
                <a class="de-alert de-alert--warning" href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}">
                    <span class="de-alert-icon"><i class="fas fa-clipboard-list"></i></span>
                    <span class="de-alert-body">
                        <span class="de-alert-title">{{ $pendingInscriptionsCount }} inscription{{ $pendingInscriptionsCount > 1 ? 's' : '' }} en attente</span>
                        <span class="de-alert-text">À valider pour finaliser l'admission.</span>
                    </span>
                    <i class="fas fa-chevron-right de-alert-go"></i>
                </a>
            @endif
            @if(($evaluationsSansNotesCount ?? 0) > 0)
                <a class="de-alert de-alert--danger" href="{{ route('esbtp.evaluations.index') }}">
                    <span class="de-alert-icon"><i class="fas fa-pen-clip"></i></span>
                    <span class="de-alert-body">
                        <span class="de-alert-title">{{ $evaluationsSansNotesCount }} évaluation{{ $evaluationsSansNotesCount > 1 ? 's' : '' }} sans notes</span>
                        <span class="de-alert-text">Déjà passées, aucune note enregistrée.</span>
                    </span>
                    <i class="fas fa-chevron-right de-alert-go"></i>
                </a>
            @endif
            @if($sansEdt > 0)
                <a class="de-alert de-alert--info" href="{{ route('esbtp.emploi-temps.index') }}">
                    <span class="de-alert-icon"><i class="fas fa-calendar-xmark"></i></span>
                    <span class="de-alert-body">
                        <span class="de-alert-title">{{ $sansEdt }} classe{{ $sansEdt > 1 ? 's' : '' }} sans emploi du temps</span>
                        <span class="de-alert-text">Les séances ne peuvent pas être planifiées.</span>
                    </span>
                    <i class="fas fa-chevron-right de-alert-go"></i>
                </a>
            @endif
        </div>
    @endif

    {{-- Indicateurs de suivi --}}
    <div class="de-kpis">
        <a class="de-kpi" href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}">
            <span class="de-kpi-icon"><i class="fas fa-clipboard-list"></i></span>
            <span class="de-kpi-value" data-kpi="pendingInscriptionsCount">{{ $pendingInscriptionsCount ?? 0 }}</span>
            <span class="de-kpi-label">Inscriptions en attente</span>
        </a>
        @can('finance.unpaid_count.view')
            <div class="de-kpi">
                <span class="de-kpi-icon"><i class="fas fa-user-clock"></i></span>
                <span class="de-kpi-value" data-kpi="unpaidStudentsCount">{{ $unpaidStudentsCount ?? 0 }}</span>
                <span class="de-kpi-label">Étudiants non soldés</span>
                <span class="de-kpi-hint">Effectif seul, aucun montant</span>
            </div>
        @endcan
        <a class="de-kpi" href="{{ route('esbtp.evaluations.index') }}">
            <span class="de-kpi-icon"><i class="fas fa-pen-clip"></i></span>
            <span class="de-kpi-value" data-kpi="evaluationsSansNotesCount">{{ $evaluationsSansNotesCount ?? 0 }}</span>
            <span class="de-kpi-label">Évaluations sans notes</span>
        </a>
        <a class="de-kpi" href="{{ route('esbtp.attendances.index') }}">
            <span class="de-kpi-icon"><i class="fas fa-user-check"></i></span>
            <span class="de-kpi-value" data-kpi="attendanceRate">{{ $rate }}%</span>
            <span class="de-kpi-label">Taux de présence</span>
        </a>
        <a class="de-kpi" href="{{ route('esbtp.emploi-temps.index') }}">
            <span class="de-kpi-icon"><i class="fas fa-calendar-days"></i></span>
            <span class="de-kpi-value" data-kpi="totalEmploiTemps">{{ $totalEmploiTemps ?? 0 }}</span>
            <span class="de-kpi-label">Emplois du temps</span>
            <span class="de-kpi-hint">{{ $sansEdt }} classe{{ $sansEdt > 1 ? 's' : '' }} sans EDT</span>
        </a>
        <a class="de-kpi" href="{{ route('esbtp.pilotage-academique.index') }}">
            <span class="de-kpi-icon"><i class="fas fa-triangle-exclamation"></i></span>
            <span class="de-kpi-value">{{ $health['open_alerts'] ?? 0 }}</span>
            <span class="de-kpi-label">Alertes ouvertes</span>
        </a>
    </div>

    <div class="rdx-grid">

        <x-role-panel
            icon="fa-calendar-days"
            title="Emplois du temps"
            subtitle="État de la planification sur la semaine en cours">
            <x-slot:actions>
                <a class="rdx-act rdx-act--ghost" href="{{ route('esbtp.emploi-temps.index') }}">Tout voir</a>
            </x-slot:actions>
            <div class="de-stats">
                <div class="de-stat">
                    <span class="de-stat-value">{{ $activeEmploiTemps ?? 0 }}</span>
                    <span class="de-stat-label">Actifs cette semaine</span>
                </div>
                <div class="de-stat">
                    <span class="de-stat-value">{{ $expiredEmploiTemps ?? 0 }}</span>
                    <span class="de-stat-label">Expirés</span>
                </div>
                <div class="de-stat {{ $sansEdt > 0 ? 'de-stat--warn' : '' }}">
                    <span class="de-stat-value">{{ $sansEdt }}</span>
                    <span class="de-stat-label">Classes sans EDT</span>
                </div>
            </div>
        </x-role-panel>

        <x-role-panel
            icon="fa-heart-pulse"
            title="Pilotage académique"
            subtitle="Ce qui bloque la production des bulletins">
            <x-slot:actions>
                <a class="rdx-act rdx-act--ghost" href="{{ route('esbtp.pilotage-academique.index') }}">Ouvrir</a>
            </x-slot:actions>
            <div class="de-stats">
                <div class="de-stat">
                    <span class="de-stat-value">{{ $health['sheets_pending'] ?? 0 }}</span>
                    <span class="de-stat-label">Fiches à traiter</span>
                </div>
                <div class="de-stat {{ ($health['blocking_alerts'] ?? 0) > 0 ? 'de-stat--warn' : '' }}">
                    <span class="de-stat-value">{{ $health['blocking_alerts'] ?? 0 }}</span>
                    <span class="de-stat-label">Alertes bloquantes</span>
                </div>
                <div class="de-stat {{ ($health['bulletin_blockers'] ?? 0) > 0 ? 'de-stat--warn' : '' }}">
                    <span class="de-stat-value">{{ $health['bulletin_blockers'] ?? 0 }}</span>
                    <span class="de-stat-label">Blocages bulletin</span>
                </div>
            </div>
        </x-role-panel>

        <x-role-panel
            icon="fa-file-lines"
            title="Rapports de pilotage"
            subtitle="Effectifs et suivi pédagogique, sans aucun montant">
            <div class="de-links">
                <a class="de-link" href="{{ route('esbtp.rapports.rentree') }}">
                    <span class="de-link-icon"><i class="fas fa-door-open"></i></span>
                    <span class="de-link-body">
                        <span class="de-link-title">Rapport de rentrée</span>
                        <span class="de-link-hint">Effectifs et couverture à l'ouverture</span>
                    </span>
                    <i class="fas fa-chevron-right de-link-go"></i>
                </a>
                <a class="de-link" href="{{ route('esbtp.rapports.trimestre') }}">
                    <span class="de-link-icon"><i class="fas fa-calendar-week"></i></span>
                    <span class="de-link-body">
                        <span class="de-link-title">Rapport de fin de trimestre</span>
                        <span class="de-link-hint">Notes manquantes et assiduité</span>
                    </span>
                    <i class="fas fa-chevron-right de-link-go"></i>
                </a>
                <a class="de-link" href="{{ route('esbtp.rapports.annuel') }}">
                    <span class="de-link-icon"><i class="fas fa-book"></i></span>
                    <span class="de-link-body">
                        <span class="de-link-title">Rapport annuel</span>
                        <span class="de-link-hint">Synthèse de l'année académique</span>
                    </span>
                    <i class="fas fa-chevron-right de-link-go"></i>
                </a>
            </div>
        </x-role-panel>

        <x-role-panel
            icon="fa-bolt"
            title="Accès rapides"
            subtitle="Les écrans que vous ouvrez le plus souvent">
            <div class="de-shortcuts">
                <a class="de-shortcut" href="{{ route('esbtp.emploi-temps.index') }}"><i class="fas fa-calendar-days"></i>Emplois du temps</a>
                <a class="de-shortcut" href="{{ route('esbtp.planning-general.index') }}"><i class="fas fa-calendar-check"></i>Planning général</a>
                <a class="de-shortcut" href="{{ route('esbtp.resultats.index') }}"><i class="fas fa-list-ol"></i>Résultats</a>
                <a class="de-shortcut" href="{{ route('esbtp.notes.index') }}"><i class="fas fa-pen"></i>Notes</a>
                <a class="de-shortcut" href="{{ route('esbtp.personnel.unified.index') }}"><i class="fas fa-users"></i>Personnel</a>
                <a class="de-shortcut" href="{{ route('esbtp.attendances.index') }}"><i class="fas fa-user-check"></i>Présences</a>
            </div>
        </x-role-panel>

    </div>
</div>
@endsection

@push('styles')
<style>
    /* Namespace de-* : tableau de bord directeur des études */
    .de-year { cursor: default; }
    #de-refresh-btn.de-spinning i { animation: de-spin .8s linear infinite; }
    @keyframes de-spin { to { transform: rotate(360deg); } }

    /* ===== Alertes actionnables ===== */
    .de-alerts { display: flex; flex-direction: column; gap: .6rem; margin-bottom: 1.25rem; }
    .de-alert {
        display: flex; align-items: center; gap: .85rem;
        padding: .85rem 1.1rem;
        background: #fff; border: 1px solid #e2e8f0;
        border-left: 4px solid #0453cb; border-radius: 12px;
        text-decoration: none;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
        transition: box-shadow .2s ease;
    }
    .de-alert:hover { box-shadow: 0 6px 20px rgba(4, 83, 203, .08); }
    .de-alert-icon {
        width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: .9rem;
    }
    .de-alert-body { display: flex; flex-direction: column; min-width: 0; }
    .de-alert-title { font-size: .9rem; font-weight: 700; color: #1e293b; }
    .de-alert-text { font-size: .78rem; color: #64748b; margin-top: .1rem; }
    .de-alert-go { margin-left: auto; color: #94a3b8; font-size: .75rem; }
    .de-alert--warning { border-left-color: #f59e0b; }
    .de-alert--warning .de-alert-icon { background: rgba(245, 158, 11, .12); color: #b45309; }
    .de-alert--danger { border-left-color: #dc2626; }
    .de-alert--danger .de-alert-icon { background: rgba(220, 38, 38, .1); color: #dc2626; }
    .de-alert--info .de-alert-icon { background: rgba(4, 83, 203, .08); color: #0453cb; }

    /* ===== Indicateurs ===== */
    /* 165px : les six indicateurs tiennent sur une seule ligne en poste de
       travail, au lieu de laisser une carte orpheline sur un second rang. */
    .de-kpis {
        display: grid; gap: 1rem; margin-bottom: 1.25rem;
        grid-template-columns: repeat(auto-fit, minmax(165px, 1fr));
    }
    .de-kpi {
        display: flex; flex-direction: column;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        padding: 1.1rem 1.15rem; text-decoration: none;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    a.de-kpi:hover { border-color: #c7d4e5; box-shadow: 0 8px 24px rgba(4, 83, 203, .08); }
    .de-kpi-icon {
        width: 38px; height: 38px; border-radius: 10px; margin-bottom: .6rem;
        background: rgba(4, 83, 203, .08); color: #0453cb;
        display: flex; align-items: center; justify-content: center; font-size: .9rem;
    }
    .de-kpi-value { font-size: 1.75rem; font-weight: 700; color: #1e293b; line-height: 1.1; }
    .de-kpi-label { font-size: .78rem; color: #64748b; font-weight: 600; margin-top: .2rem; }
    .de-kpi-hint { font-size: .72rem; color: #94a3b8; margin-top: .25rem; }

    /* ===== Statistiques dans une carte ===== */
    .de-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
    .de-stat {
        background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px;
        padding: .8rem .75rem; text-align: center;
    }
    .de-stat--warn { background: rgba(245, 158, 11, .07); border-color: rgba(245, 158, 11, .22); }
    .de-stat-value { display: block; font-size: 1.4rem; font-weight: 700; color: #1e293b; }
    .de-stat--warn .de-stat-value { color: #b45309; }
    .de-stat-label { display: block; font-size: .74rem; color: #64748b; margin-top: .15rem; }

    /* ===== Listes de liens ===== */
    .de-links { display: flex; flex-direction: column; gap: .55rem; }
    .de-link {
        display: flex; align-items: center; gap: .85rem;
        padding: .75rem .85rem; border: 1px solid #e2e8f0; border-radius: 10px;
        text-decoration: none; transition: border-color .2s ease, box-shadow .2s ease;
    }
    .de-link:hover { border-color: #c7d4e5; box-shadow: 0 4px 16px rgba(4, 83, 203, .06); }
    .de-link-icon {
        width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
        background: rgba(4, 83, 203, .08); color: #0453cb;
        display: flex; align-items: center; justify-content: center; font-size: .82rem;
    }
    .de-link-body { display: flex; flex-direction: column; min-width: 0; }
    .de-link-title { font-size: .86rem; font-weight: 600; color: #1e293b; }
    .de-link-hint { font-size: .74rem; color: #64748b; margin-top: .08rem; }
    .de-link-go { margin-left: auto; color: #94a3b8; font-size: .72rem; }

    /* ===== Raccourcis ===== */
    .de-shortcuts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
    .de-shortcut {
        display: flex; align-items: center; gap: .5rem;
        padding: .65rem .8rem; border: 1px solid #e2e8f0; border-radius: 9px;
        font-size: .82rem; font-weight: 600; color: #1e293b; text-decoration: none;
        transition: background .2s ease, border-color .2s ease;
    }
    .de-shortcut i { color: #0453cb; width: 16px; text-align: center; }
    .de-shortcut:hover { background: rgba(4, 83, 203, .05); border-color: #c7d4e5; color: #0453cb; }

    @media (max-width: 768px) {
        .de-stats, .de-shortcuts { grid-template-columns: 1fr; }
    }
</style>
@endpush

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
            document.querySelectorAll('[data-kpi="' + key + '"]').forEach(function(el) {
                el.textContent = kpiMap[key];
            });
        });
        btn.classList.remove('de-spinning');
    })
    .catch(function() {
        btn.classList.remove('de-spinning');
    });
}
</script>
@endpush
