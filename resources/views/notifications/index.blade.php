@extends('layouts.app')

@section('title', 'Notifications')

@php
    $hasTimetableShortcut = ! empty($timetableShortcut) && ($timetableShortcut['show'] ?? false);
    $hasEvaluationShortcut = ! empty($evaluationShortcut) && ($evaluationShortcut['show'] ?? false);
    $hasEvaluationGradingShortcut = ! empty($evaluationGradingShortcut) && ($evaluationGradingShortcut['show'] ?? false);
    $gradingCtaUrl = null;
    if (auth()->user()?->can('exams.view') || auth()->user()?->can('evaluations.view')) {
        $gradingCtaUrl = route('esbtp.evaluations.index');
    } elseif (auth()->user()?->can('notes.view') || auth()->user()?->can('notes.create') || auth()->user()?->can('notes.edit') || auth()->user()?->can('notes.manage_own')) {
        $gradingCtaUrl = route('esbtp.notes.index');
    }
    $ntfShowGrading = $hasEvaluationGradingShortcut && $gradingCtaUrl;
    $ntfHasWork = $ntfShowGrading || $hasEvaluationShortcut || $hasTimetableShortcut;
    $ntfCanCoordinate = auth()->user()?->can('identity.coordinate');

    $ntfTypes = [
        'info' => ['label' => 'Informations', 'icon' => 'fa-circle-info'],
        'success' => ['label' => 'Succès', 'icon' => 'fa-circle-check'],
        'warning' => ['label' => 'À surveiller', 'icon' => 'fa-triangle-exclamation'],
        'alerte' => ['label' => 'Alertes', 'icon' => 'fa-circle-exclamation'],
    ];
    $ntfSujets = [
        'émargement' => ['label' => 'Émargements', 'icon' => 'fa-signature'],
        'appel' => ['label' => 'Appels', 'icon' => 'fa-users'],
        'retard' => ['label' => 'Retards', 'icon' => 'fa-clock'],
    ];

    $ntfConfig = [
        'base' => url()->current(),
        'readUrl' => route('notifications.mark-as-read', ['id' => '__ID__']),
        'deleteUrl' => route('notifications.delete', ['id' => '__ID__']),
        'readAllUrl' => route('notifications.mark-all-as-read'),
        'filters' => $filters,
        'counts' => $counts,
        'nextUrl' => $notifications->hasMorePages()
            ? $notifications->appends(['fragment' => 1, 'after_group' => optional($notifications->getCollection()->last())->display_group])->nextPageUrl()
            : null,
    ];
@endphp

@push('styles')
<style>
    .ntf-page { max-width: 1080px; margin: 0 auto; }

    /* ===== Hero (pattern planning-header) ===== */
    .ntf-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4, 83, 203, .18);
    }
    .ntf-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .ntf-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
    .ntf-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255, 255, 255, .12); border: 1px solid rgba(255, 255, 255, .15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .ntf-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .ntf-hero p { color: rgba(255, 255, 255, .78); font-size: .88rem; margin: .2rem 0 0; }
    .ntf-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .ntf-btn-hero {
        display: inline-flex; align-items: center; gap: .45rem;
        border-radius: 10px; padding: .55rem 1rem; font-size: .82rem; font-weight: 600;
        border: 1px solid rgba(255, 255, 255, .22); background: rgba(255, 255, 255, .15);
        color: #fff; text-decoration: none; cursor: pointer; transition: all .2s ease;
    }
    .ntf-btn-hero:hover { background: rgba(255, 255, 255, .24); color: #fff; }
    .ntf-btn-hero--white { background: #fff; color: #0453cb; border-color: transparent; }
    .ntf-btn-hero--white:hover { background: #eef4ff; color: #033a8e; }
    .ntf-btn-hero:disabled { opacity: .6; cursor: wait; }

    .ntf-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .75rem; margin-top: 1.5rem; }
    .ntf-kpi {
        background: rgba(255, 255, 255, .1); border: 1px solid rgba(255, 255, 255, .15);
        border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem;
        color: #fff; text-align: left; cursor: pointer; transition: background .2s ease, border-color .2s ease;
        font: inherit; width: 100%;
    }
    .ntf-kpi:hover { background: rgba(255, 255, 255, .18); }
    .ntf-kpi.is-active { background: #fff; color: #0453cb; border-color: #fff; }
    .ntf-kpi.is-active .ntf-kpi-label, .ntf-kpi.is-active .ntf-kpi-hint { color: #475569; }
    .ntf-kpi-ico { width: 38px; height: 38px; border-radius: 10px; background: rgba(255, 255, 255, .14); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ntf-kpi.is-active .ntf-kpi-ico { background: rgba(4, 83, 203, .1); }
    .ntf-kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1.1; white-space: nowrap; }
    .ntf-kpi-label, .ntf-kpi-hint { display: block; }
    .ntf-kpi-label { font-size: .74rem; color: rgba(255, 255, 255, .78); font-weight: 600; }
    .ntf-kpi-hint { font-size: .68rem; color: rgba(255, 255, 255, .6); }

    /* ===== File de travail ===== */
    .ntf-work { margin-bottom: 1.25rem; }
    .ntf-section-title { display: flex; align-items: center; gap: .6rem; font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin: 0 0 .6rem; }
    .ntf-work-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: .75rem; }
    .ntf-work-card {
        display: flex; gap: .85rem; align-items: flex-start; padding: 1rem 1.1rem;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; text-decoration: none; color: #1e293b;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 1px 2px rgba(15, 23, 42, .06);
        transition: box-shadow .2s ease, border-color .2s ease;
    }
    .ntf-work-card:hover { border-color: #b9cdf0; box-shadow: 0 8px 26px rgba(4, 83, 203, .1); color: #1e293b; }
    .ntf-work-ico { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ntf-work-title { font-weight: 700; font-size: .92rem; }
    .ntf-work-sub { font-size: .78rem; color: #64748b; margin-top: .1rem; }
    .ntf-work-meta { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .ntf-work-go { margin-left: auto; color: #0453cb; align-self: center; }

    /* ===== Carte liste + chips ===== */
    .ntf-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 1px 2px rgba(15, 23, 42, .06); }
    .ntf-toolbar { padding: .9rem 1.1rem; border-bottom: 1px solid #eef2f7; display: flex; flex-direction: column; gap: .6rem; }
    .ntf-chips { display: flex; gap: .45rem; overflow-x: auto; padding-bottom: 2px; scrollbar-width: thin; }
    .ntf-chip {
        display: inline-flex; align-items: center; gap: .4rem; white-space: nowrap;
        border: 1px solid #dbe5f2; background: #f8fafc; color: #334155; border-radius: 999px;
        padding: .42rem .85rem; font-size: .8rem; font-weight: 600; cursor: pointer; transition: all .2s ease;
    }
    .ntf-chip:hover { border-color: #0453cb; color: #0453cb; }
    .ntf-chip.is-active { background: #0453cb; border-color: #0453cb; color: #fff; }
    .ntf-chip-count { background: rgba(4, 83, 203, .1); color: #0453cb; border-radius: 999px; padding: 0 .45rem; font-size: .72rem; }
    .ntf-chip.is-active .ntf-chip-count { background: rgba(255, 255, 255, .22); color: #fff; }
    .ntf-chip--clear { background: #eef4ff; border-color: #c7d8f5; color: #0453cb; }

    .ntf-new {
        display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%;
        border: 0; border-bottom: 1px solid #eef2f7; background: #eef4ff; color: #0453cb;
        font-weight: 600; font-size: .85rem; padding: .65rem; cursor: pointer;
    }

    /* ===== Lignes ===== */
    .ntf-list { padding: .35rem 0 .5rem; transition: opacity .2s ease; }
    .ntf-list.is-loading { opacity: .45; pointer-events: none; }
    .ntf-group { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #64748b; margin: 0; padding: .9rem 1.1rem .35rem; }
    .ntf-row {
        display: flex; gap: .85rem; align-items: flex-start; padding: .85rem 1.1rem; margin: 0 .5rem;
        border-radius: 12px; cursor: pointer; position: relative; transition: background .2s ease, opacity .25s ease;
    }
    .ntf-row:hover { background: #f5f8fe; }
    .ntf-row.is-unread { background: #f0f5ff; }
    .ntf-row.is-unread::before { content: ''; position: absolute; left: 0; top: .9rem; bottom: .9rem; width: 3px; border-radius: 3px; background: #0453cb; }
    .ntf-row.is-leaving { opacity: 0; }
    .ntf-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1rem; background: rgba(4, 83, 203, .09); color: #0453cb; }
    .ntf-row--success .ntf-icon { background: rgba(16, 185, 129, .12); color: #047857; }
    .ntf-row--warning .ntf-icon { background: rgba(245, 158, 11, .14); color: #b45309; }
    .ntf-row--alerte .ntf-icon { background: rgba(220, 38, 38, .1); color: #b91c1c; }
    .ntf-body { flex: 1; min-width: 0; }
    .ntf-title-line { display: flex; align-items: center; gap: .5rem; }
    .ntf-title { font-size: .92rem; font-weight: 600; color: #1e293b; margin: 0; overflow-wrap: anywhere; }
    .ntf-row.is-unread .ntf-title { font-weight: 700; color: #0f172a; }
    .ntf-dot { width: 8px; height: 8px; border-radius: 50%; background: #0453cb; flex-shrink: 0; }
    .ntf-excerpt { font-size: .84rem; color: #475569; margin: .2rem 0 0; line-height: 1.45; overflow-wrap: anywhere; }
    .ntf-pills { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .45rem; }
    .ntf-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .18rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600; background: rgba(4, 83, 203, .07); color: #1e3a8a; border: 1px solid rgba(4, 83, 203, .14); }
    .ntf-pill--success { background: rgba(16, 185, 129, .1); color: #047857; border-color: rgba(16, 185, 129, .22); }
    .ntf-pill--warning { background: rgba(245, 158, 11, .12); color: #92400e; border-color: rgba(245, 158, 11, .25); }
    .ntf-pill--danger { background: rgba(220, 38, 38, .08); color: #b91c1c; border-color: rgba(220, 38, 38, .2); }
    .ntf-meta { font-size: .75rem; color: #64748b; margin-top: .4rem; display: flex; gap: .35rem; flex-wrap: wrap; }
    .ntf-actions { display: flex; align-items: center; gap: .4rem; flex-shrink: 0; }
    .ntf-btn {
        display: inline-flex; align-items: center; gap: .4rem; border-radius: 9px; padding: .45rem .8rem;
        font-size: .78rem; font-weight: 600; border: 1px solid #dbe5f2; background: #fff; color: #0453cb;
        text-decoration: none; cursor: pointer; white-space: nowrap; transition: all .2s ease;
    }
    .ntf-btn:hover { border-color: #0453cb; color: #033a8e; }
    .ntf-btn--primary { background: #0453cb; border-color: #0453cb; color: #fff; }
    .ntf-btn--primary:hover { background: #033a8e; border-color: #033a8e; color: #fff; }
    .ntf-icon-btn {
        display: inline-flex; align-items: center; gap: .35rem; height: 34px; min-width: 34px; justify-content: center;
        border-radius: 9px; border: 1px solid transparent; background: transparent; color: #94a3b8;
        cursor: pointer; padding: 0 .55rem; font-size: .78rem; font-weight: 600; transition: all .2s ease;
    }
    .ntf-icon-btn:hover { color: #b91c1c; background: rgba(220, 38, 38, .07); }
    .ntf-confirm-label { display: none; }
    .ntf-icon-btn.is-confirming { color: #fff; background: #dc2626; border-color: #dc2626; }
    .ntf-icon-btn.is-confirming .ntf-confirm-label { display: inline; }

    .ntf-more { display: flex; justify-content: center; padding: .5rem 1rem 1.1rem; }

    /* ===== États vides ===== */
    .ntf-empty { text-align: center; padding: 3rem 1.5rem; }
    .ntf-empty-ico { width: 72px; height: 72px; margin: 0 auto 1rem; border-radius: 50%; background: rgba(16, 185, 129, .1); color: #047857; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
    .ntf-empty h2 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0 0 .35rem; }
    .ntf-empty p { font-size: .86rem; color: #64748b; margin: 0 0 1rem; }

    @media (max-width: 768px) {
        .ntf-hero { padding: 1.4rem 1.2rem 1.2rem; border-radius: 16px; }
        .ntf-hero h1 { font-size: 1.25rem; }
        /* La grille des compteurs suit la règle commune du shell mobile. */
        .ntf-kpi-ico { display: none; }
        .ntf-row { margin: 0 .25rem; padding: .8rem .75rem; flex-wrap: wrap; }
        .ntf-actions { width: 100%; padding-left: calc(40px + .85rem); }
        .ntf-btn--primary { flex: 1; justify-content: center; }
    }
    @media (max-width: 576px) {
        .ntf-hero-actions { width: 100%; }
        .ntf-hero-actions .ntf-btn-hero { flex: 1; justify-content: center; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
<div class="ntf-page" x-data="ntfPage()" data-ntf-config='@json($ntfConfig)'>

    {{-- ===== Hero ===== --}}
    <section class="ntf-hero">
        <div class="ntf-hero-top">
            <div class="ntf-hero-left">
                <div class="ntf-hero-icon"><i class="fas fa-bell" aria-hidden="true"></i></div>
                <div>
                    <h1>Notifications</h1>
                    <p x-text="counts.unread > 0
                        ? (counts.unread === 1 ? 'Une notification attend votre lecture.' : counts.unread + ' notifications attendent votre lecture.')
                        : 'Vous êtes à jour. Rien ne vous attend.'">
                        {{ $counts['unread'] > 0 ? $counts['unread'].' notification(s) attendent votre lecture.' : 'Vous êtes à jour. Rien ne vous attend.' }}
                    </p>
                </div>
            </div>
            <div class="ntf-hero-actions">
                @if($ntfCanCoordinate)
                    <a href="{{ route('esbtp.attendances.index') }}" class="ntf-btn-hero">
                        <i class="fas fa-chart-bar" aria-hidden="true"></i> Présences
                    </a>
                @endif
                <button type="button" class="ntf-btn-hero ntf-btn-hero--white mark-all-read"
                        x-show="counts.unread > 0" x-cloak
                        @click="markAllRead()" :disabled="busyAll">
                    <i class="fas fa-check-double" aria-hidden="true"></i>
                    <span x-text="busyAll ? 'Un instant…' : 'Tout marquer comme lu'">Tout marquer comme lu</span>
                </button>
            </div>
        </div>

        <div class="ntf-kpis">
            <button type="button" class="ntf-kpi" :class="isActive('non_lues') ? 'is-active' : ''" @click="apply({ filtre: 'non_lues', type: null, periode: null, sujet: null })">
                <span class="ntf-kpi-ico"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                <span>
                    <span class="ntf-kpi-value" x-text="counts.unread">{{ $counts['unread'] }}</span>
                    <span class="ntf-kpi-label">Non lues</span>
                    <span class="ntf-kpi-hint" x-text="'sur ' + counts.total + ' au total'">sur {{ $counts['total'] }} au total</span>
                </span>
            </button>
            <button type="button" class="ntf-kpi" :class="isActive('aujourdhui') ? 'is-active' : ''" @click="apply({ filtre: 'toutes', type: null, periode: 'aujourdhui', sujet: null })">
                <span class="ntf-kpi-ico"><i class="fas fa-sun" aria-hidden="true"></i></span>
                <span>
                    <span class="ntf-kpi-value" x-text="counts.today">{{ $counts['today'] }}</span>
                    <span class="ntf-kpi-label">Aujourd'hui</span>
                    <span class="ntf-kpi-hint">reçues depuis ce matin</span>
                </span>
            </button>
            <button type="button" class="ntf-kpi" :class="isActive('semaine') ? 'is-active' : ''" @click="apply({ filtre: 'toutes', type: null, periode: 'semaine', sujet: null })">
                <span class="ntf-kpi-ico"><i class="fas fa-calendar-week" aria-hidden="true"></i></span>
                <span>
                    <span class="ntf-kpi-value" x-text="counts.week">{{ $counts['week'] }}</span>
                    <span class="ntf-kpi-label">Cette semaine</span>
                    <span class="ntf-kpi-hint">depuis lundi</span>
                </span>
            </button>
        </div>
    </section>

    {{-- ===== À traiter maintenant (raccourcis existants) ===== --}}
    @if($ntfHasWork)
        <section class="ntf-work" aria-label="À traiter maintenant">
            <h2 class="ntf-section-title"><i class="fas fa-list-check" aria-hidden="true"></i> À traiter maintenant</h2>
            <div class="ntf-work-grid">
                @if($ntfShowGrading)
                    <a href="{{ $gradingCtaUrl }}" class="ntf-work-card evaluation-grading-shortcut-item">
                        <span class="ntf-work-ico"><i class="fas fa-pen-to-square" aria-hidden="true"></i></span>
                        <span>
                            <span class="ntf-work-title d-block">Notes à saisir</span>
                            <span class="ntf-work-sub d-block">Évaluations passées, saisie attendue</span>
                            <span class="ntf-work-meta">
                                <span class="ntf-pill ntf-pill--danger"><i class="fas fa-calendar-xmark" aria-hidden="true"></i>À noter : {{ $evaluationGradingShortcut['total'] ?? 0 }}</span>
                                @if(($evaluationGradingShortcut['missing_notes'] ?? 0) > 0)
                                    <span class="ntf-pill ntf-pill--danger"><i class="fas fa-clipboard-list" aria-hidden="true"></i>Sans notes : {{ $evaluationGradingShortcut['missing_notes'] }}</span>
                                @endif
                                @if(($evaluationGradingShortcut['notes_unpublished'] ?? 0) > 0)
                                    <span class="ntf-pill ntf-pill--warning"><i class="fas fa-eye-slash" aria-hidden="true"></i>Non publiées : {{ $evaluationGradingShortcut['notes_unpublished'] }}</span>
                                @endif
                            </span>
                        </span>
                        <i class="fas fa-chevron-right ntf-work-go" aria-hidden="true"></i>
                    </a>
                @endif
                @if($hasEvaluationShortcut)
                    <a href="{{ route('esbtp.evaluations.index') }}" class="ntf-work-card evaluation-shortcut-item">
                        <span class="ntf-work-ico"><i class="fas fa-clipboard-check" aria-hidden="true"></i></span>
                        <span>
                            <span class="ntf-work-title d-block">Évaluations à activer</span>
                            <span class="ntf-work-sub d-block">Publiez-les pour ouvrir la saisie</span>
                            <span class="ntf-work-meta">
                                <span class="ntf-pill"><i class="fas fa-layer-group" aria-hidden="true"></i>Brouillons : {{ $evaluationShortcut['total'] ?? 0 }}</span>
                                @if(($evaluationShortcut['overdue'] ?? 0) > 0)
                                    <span class="ntf-pill ntf-pill--danger"><i class="fas fa-calendar-times" aria-hidden="true"></i>En retard : {{ $evaluationShortcut['overdue'] }}</span>
                                @endif
                                @if(($evaluationShortcut['soon'] ?? 0) > 0)
                                    <span class="ntf-pill ntf-pill--warning"><i class="fas fa-hourglass-half" aria-hidden="true"></i>Bientôt : {{ $evaluationShortcut['soon'] }}</span>
                                @endif
                                @if(($evaluationShortcut['undated'] ?? 0) > 0)
                                    <span class="ntf-pill"><i class="fas fa-question-circle" aria-hidden="true"></i>Sans date : {{ $evaluationShortcut['undated'] }}</span>
                                @endif
                            </span>
                        </span>
                        <i class="fas fa-chevron-right ntf-work-go" aria-hidden="true"></i>
                    </a>
                @endif
                @if($hasTimetableShortcut)
                    <a href="{{ route('esbtp.emploi-temps.index', ['quick_generate' => 1]) }}" class="ntf-work-card timetable-shortcut-item">
                        <span class="ntf-work-ico"><i class="fas fa-calendar-days" aria-hidden="true"></i></span>
                        <span>
                            <span class="ntf-work-title d-block">Emplois du temps à renouveler</span>
                            <span class="ntf-work-sub d-block">Génération rapide disponible</span>
                            <span class="ntf-work-meta">
                                @if(($timetableShortcut['missing'] ?? 0) > 0)
                                    <span class="ntf-pill"><i class="fas fa-layer-group" aria-hidden="true"></i>Sans emploi du temps : {{ $timetableShortcut['missing'] }}</span>
                                @endif
                                @if(($timetableShortcut['expired'] ?? 0) > 0)
                                    <span class="ntf-pill ntf-pill--danger"><i class="fas fa-calendar-times" aria-hidden="true"></i>Expirés : {{ $timetableShortcut['expired'] }}</span>
                                @endif
                                @if(($timetableShortcut['expiring_soon'] ?? 0) > 0)
                                    <span class="ntf-pill ntf-pill--warning"><i class="fas fa-clock" aria-hidden="true"></i>Expirent bientôt : {{ $timetableShortcut['expiring_soon'] }}</span>
                                @endif
                            </span>
                        </span>
                        <i class="fas fa-chevron-right ntf-work-go" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        </section>
    @endif

    {{-- ===== Liste ===== --}}
    <section class="ntf-card">
        <div class="ntf-toolbar">
            <div class="ntf-chips" role="toolbar" aria-label="Filtrer les notifications">
                <button type="button" class="ntf-chip" :class="isActive('toutes') ? 'is-active' : ''" @click="apply({ filtre: 'toutes', type: null, periode: null, sujet: null })">
                    Toutes <span class="ntf-chip-count" x-text="counts.total">{{ $counts['total'] }}</span>
                </button>
                <button type="button" class="ntf-chip" :class="isActive('non_lues') ? 'is-active' : ''" @click="apply({ filtre: 'non_lues', type: null, periode: null, sujet: null })">
                    Non lues <span class="ntf-chip-count" x-text="counts.unread">{{ $counts['unread'] }}</span>
                </button>
                @foreach($ntfTypes as $typeKey => $type)
                    <button type="button" class="ntf-chip"
                            x-show="counts.types['{{ $typeKey }}'] > 0" @if(($counts['types'][$typeKey] ?? 0) === 0) x-cloak @endif
                            :class="filters.type === '{{ $typeKey }}' ? 'is-active' : ''"
                            @click="apply({ filtre: 'toutes', type: '{{ $typeKey }}', periode: null, sujet: null })">
                        <i class="fas {{ $type['icon'] }}" aria-hidden="true"></i>{{ $type['label'] }}
                        <span class="ntf-chip-count" x-text="counts.types['{{ $typeKey }}']">{{ $counts['types'][$typeKey] ?? 0 }}</span>
                    </button>
                @endforeach
                @if($ntfCanCoordinate)
                    @foreach($ntfSujets as $sujetKey => $sujet)
                        <button type="button" class="ntf-chip filter-notifications" data-filter="{{ $sujetKey }}"
                                :class="filters.sujet === '{{ $sujetKey }}' ? 'is-active' : ''"
                                @click="apply({ filtre: 'toutes', type: null, periode: null, sujet: '{{ $sujetKey }}' })">
                            <i class="fas {{ $sujet['icon'] }}" aria-hidden="true"></i>{{ $sujet['label'] }}
                        </button>
                    @endforeach
                @endif
                <button type="button" class="ntf-chip ntf-chip--clear" x-show="filters.periode" x-cloak @click="apply({ periode: null })">
                    <span x-text="filters.periode === 'aujourdhui' ? 'Aujourd\'hui' : 'Cette semaine'"></span>
                    <i class="fas fa-xmark" aria-hidden="true"></i><span class="visually-hidden">Retirer la période</span>
                </button>
            </div>
        </div>

        <button type="button" class="ntf-new" x-show="fresh > 0" x-cloak @click="apply({})">
            <i class="fas fa-arrow-rotate-right" aria-hidden="true"></i>
            <span x-text="fresh === 1 ? 'Une nouvelle notification, afficher' : fresh + ' nouvelles notifications, afficher'"></span>
        </button>

        <div class="ntf-list" :class="loading ? 'is-loading' : ''" x-ref="list" @click="onListClick($event)" aria-live="polite">
            @include('notifications.partials.rows', ['notifications' => $notifications, 'previousGroup' => null])
        </div>

        <div class="ntf-empty" x-show="empty" @if($notifications->isNotEmpty()) x-cloak @endif>
            <div class="ntf-empty-ico"><i class="fas fa-check" aria-hidden="true"></i></div>
            <h2 x-text="filtered ? 'Rien ici pour ce filtre' : 'Vous êtes à jour'">
                {{ $notifications->isEmpty() && $filters['filtre'] === 'toutes' && ! $filters['type'] && ! $filters['periode'] && ! $filters['sujet'] ? 'Vous êtes à jour' : 'Rien ici pour ce filtre' }}
            </h2>
            <p x-text="filtered ? 'Aucune notification ne correspond. Les autres restent consultables.' : 'Les nouvelles notifications apparaîtront ici dès leur arrivée.'">
                Les nouvelles notifications apparaîtront ici dès leur arrivée.
            </p>
            <button type="button" class="ntf-btn" x-show="filtered" x-cloak @click="apply({ filtre: 'toutes', type: null, periode: null, sujet: null })">
                <i class="fas fa-list" aria-hidden="true"></i> Voir toutes les notifications
            </button>
        </div>

        <div class="ntf-more" x-show="nextUrl" @if(! $notifications->hasMorePages()) x-cloak @endif>
            <button type="button" class="ntf-btn" @click="loadMore()" :disabled="loadingMore">
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                <span x-text="loadingMore ? 'Chargement…' : 'Afficher les plus anciennes'">Afficher les plus anciennes</span>
            </button>
        </div>
    </section>
</div>
</div>

@include('partials._klassci_toast')
@endsection

@push('scripts')
<script>
if (typeof window.ntfPage !== 'function') {
    window.ntfPage = function () {
        return {
            cfg: {},
            filters: { filtre: 'toutes', type: null, periode: null, sujet: null },
            counts: { total: 0, unread: 0, today: 0, week: 0, types: {} },
            nextUrl: null,
            lastGroup: null,
            loading: false,
            loadingMore: false,
            busyAll: false,
            empty: false,
            fresh: 0,
            _poll: null,
            _visibility: null,

            init() {
                try { this.cfg = JSON.parse(this.$root.dataset.ntfConfig || '{}'); } catch (e) { this.cfg = {}; }
                this.filters = Object.assign(this.filters, this.cfg.filters || {});
                this.counts = this.cfg.counts || this.counts;
                this.nextUrl = this.cfg.nextUrl || null;
                this.syncListState();

                // Nouvelles notifications : on le dit, on ne recharge pas sous les yeux.
                this._poll = setInterval(() => { if (!document.hidden) this.pollCounts(); }, 60000);
                this._visibility = () => { if (!document.hidden) this.pollCounts(); };
                document.addEventListener('visibilitychange', this._visibility);
            },

            destroy() {
                clearInterval(this._poll);
                if (this._visibility) document.removeEventListener('visibilitychange', this._visibility);
            },

            get filtered() {
                return this.filters.filtre !== 'toutes' || !!this.filters.type || !!this.filters.periode || !!this.filters.sujet;
            },

            isActive(key) {
                const f = this.filters;
                if (key === 'non_lues') return f.filtre === 'non_lues' && !f.type && !f.periode && !f.sujet;
                if (key === 'toutes') return f.filtre === 'toutes' && !f.type && !f.periode && !f.sujet;
                return f.periode === key && !f.type && !f.sujet && f.filtre === 'toutes';
            },

            syncListState() {
                const rows = this.$refs.list.querySelectorAll('.ntf-row');
                this.empty = rows.length === 0;
                const groups = this.$refs.list.querySelectorAll('[data-ntf-group]');
                this.lastGroup = groups.length ? groups[groups.length - 1].dataset.ntfGroup : null;
            },

            query(extra) {
                const params = new URLSearchParams();
                const f = this.filters;
                if (f.filtre === 'non_lues') params.set('filtre', 'non_lues');
                if (f.type) params.set('type', f.type);
                if (f.periode) params.set('periode', f.periode);
                if (f.sujet) params.set('sujet', f.sujet);
                Object.keys(extra || {}).forEach((k) => params.set(k, extra[k]));
                return params.toString();
            },

            async request(url, options) {
                const opts = Object.assign({ credentials: 'same-origin' }, options || {});
                opts.headers = Object.assign({
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                }, opts.headers || {});
                const res = await fetch(url, opts);
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Une erreur est survenue. Réessayez.');
                return data;
            },

            toast(message, type) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: type || 'success', message } }));
            },

            async apply(patch) {
                this.filters = Object.assign({}, this.filters, patch);
                this.loading = true;
                try {
                    const data = await this.request(this.cfg.base + '?' + this.query({ fragment: 1 }));
                    this.$refs.list.innerHTML = data.html;
                    this.counts = data.counts;
                    this.nextUrl = data.next_url;
                    this.fresh = 0;
                    this.syncListState();
                    const visible = this.query();
                    window.history.replaceState({}, '', this.cfg.base + (visible ? '?' + visible : ''));
                } catch (e) {
                    this.toast(e.message, 'error');
                } finally {
                    this.loading = false;
                }
            },

            async loadMore() {
                if (!this.nextUrl) return;
                this.loadingMore = true;
                try {
                    const data = await this.request(this.nextUrl);
                    this.$refs.list.insertAdjacentHTML('beforeend', data.html);
                    this.nextUrl = data.next_url;
                    this.syncListState();
                } catch (e) {
                    this.toast(e.message, 'error');
                } finally {
                    this.loadingMore = false;
                }
            },

            async pollCounts() {
                try {
                    const data = await this.request(this.cfg.base + '?fragment=1&counts_only=1');
                    const diff = (data.counts.total || 0) - (this.counts.total || 0);
                    if (diff > 0) this.fresh = diff;
                    this.counts = data.counts;
                } catch (e) { /* silencieux : une prochaine tentative suivra */ }
            },

            async refreshCounts() {
                try {
                    const data = await this.request(this.cfg.base + '?fragment=1&counts_only=1');
                    this.counts = data.counts;
                } catch (e) { /* les compteurs se remettront au prochain passage */ }
            },

            url(template, id) {
                return template.replace('__ID__', encodeURIComponent(id));
            },

            markRowRead(row) {
                row.classList.remove('is-unread');
                row.dataset.ntfUnread = '0';
                row.querySelector('[data-ntf-dot]')?.remove();
                row.querySelector('[data-ntf-read]')?.remove();
            },

            onListClick(ev) {
                const row = ev.target.closest('.ntf-row');
                if (!row) return;
                const id = row.dataset.ntfId;

                const del = ev.target.closest('[data-ntf-delete]');
                if (del) { ev.preventDefault(); this.remove(row, id, del); return; }

                const read = ev.target.closest('[data-ntf-read]');
                if (read) { ev.preventDefault(); this.read(row, id, true); return; }

                const link = ev.target.closest('[data-ntf-open]');
                const url = row.dataset.ntfUrl;
                if (link) {
                    // Ctrl/Cmd/clic milieu : nouvel onglet, on marque lu sans retenir la page.
                    if (ev.ctrlKey || ev.metaKey || ev.shiftKey || ev.button === 1) { this.read(row, id, false); return; }
                    ev.preventDefault();
                    this.openAndRead(row, id, url);
                    return;
                }
                if (ev.target.closest('a, button')) return;
                if (url) { this.openAndRead(row, id, url); return; }
                if (row.dataset.ntfUnread === '1') this.read(row, id, false);
            },

            openAndRead(row, id, url) {
                if (row.dataset.ntfUnread === '1') {
                    // keepalive : la requête part même si la page change tout de suite.
                    fetch(this.url(this.cfg.readUrl, id), {
                        method: 'POST', keepalive: true, credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    }).catch(() => {});
                }
                window.location.href = url;
            },

            async read(row, id, withToast) {
                if (row.dataset.ntfUnread !== '1') return;
                this.markRowRead(row);
                this.counts.unread = Math.max(0, this.counts.unread - 1);
                try {
                    await this.request(this.url(this.cfg.readUrl, id), { method: 'POST' });
                    if (withToast) this.toast('Notification marquée comme lue.');
                    if (this.filters.filtre === 'non_lues') this.leave(row);
                } catch (e) {
                    this.toast(e.message, 'error');
                    this.refreshCounts();
                }
            },

            async remove(row, id, btn) {
                // Deux temps, sans fenêtre : le premier appui arme, le second supprime.
                if (!btn.classList.contains('is-confirming')) {
                    btn.classList.add('is-confirming');
                    btn.setAttribute('aria-label', 'Confirmer la suppression');
                    clearTimeout(btn._ntfTimer);
                    btn._ntfTimer = setTimeout(() => {
                        btn.classList.remove('is-confirming');
                        btn.setAttribute('aria-label', 'Supprimer cette notification');
                    }, 3500);
                    return;
                }
                clearTimeout(btn._ntfTimer);
                btn.disabled = true;
                try {
                    await this.request(this.url(this.cfg.deleteUrl, id), { method: 'DELETE' });
                    this.leave(row);
                    this.toast('Notification supprimée.');
                    this.refreshCounts();
                } catch (e) {
                    btn.disabled = false;
                    btn.classList.remove('is-confirming');
                    this.toast(e.message, 'error');
                }
            },

            leave(row) {
                row.classList.add('is-leaving');
                setTimeout(() => {
                    const header = row.previousElementSibling;
                    const next = row.nextElementSibling;
                    row.remove();
                    // Un titre de groupe qui n'a plus de ligne disparaît avec elle.
                    if (header && header.matches('[data-ntf-group]') && (!next || next.matches('[data-ntf-group]'))) header.remove();
                    this.syncListState();
                }, 250);
            },

            async markAllRead() {
                this.busyAll = true;
                try {
                    await this.request(this.cfg.readAllUrl, { method: 'POST' });
                    this.$refs.list.querySelectorAll('.ntf-row.is-unread').forEach((row) => this.markRowRead(row));
                    this.toast('Toutes vos notifications sont lues.');
                    if (this.filters.filtre === 'non_lues') {
                        await this.apply({});
                    } else {
                        await this.refreshCounts();
                    }
                } catch (e) {
                    this.toast(e.message, 'error');
                } finally {
                    this.busyAll = false;
                }
            },
        };
    };
}
</script>
@endpush
