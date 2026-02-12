@extends('layouts.app')

@section('title', 'Détails de la séance de cours - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ── Hero card séance ─────────────────────────────── */
    .seance-hero {
        background: linear-gradient(135deg, #0453cb 0%, #1b64d4 60%, #5e91de 100%);
        border-radius: 18px;
        padding: 28px 32px;
        color: white;
        margin-bottom: 24px;
        box-shadow: 0 6px 28px rgba(4,83,203,.28);
        position: relative;
        overflow: hidden;
    }
    .seance-hero::after {
        content: '';
        position: absolute;
        right: -60px; top: -60px;
        width: 220px; height: 220px;
        border-radius: 50%;
        background: rgba(255,255,255,.06);
        pointer-events: none;
    }
    .seance-hero-title {
        font-size: 1.45rem;
        font-weight: 800;
        margin-bottom: 4px;
        line-height: 1.2;
    }
    .seance-hero-sub {
        font-size: .92rem;
        opacity: .8;
        margin-bottom: 20px;
    }

    /* Badge "À venir" */
    .badge-avenir {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,.22);
        border: 1px solid rgba(255,255,255,.4);
        color: white;
        border-radius: 99px;
        padding: 5px 14px;
        font-size: .82rem;
        font-weight: 700;
        letter-spacing: .02em;
        margin-bottom: 14px;
    }

    /* Info grid inside hero */
    .seance-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-top: 4px;
    }
    .seance-info-item {
        background: rgba(255,255,255,.13);
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 12px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .seance-info-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: rgba(255,255,255,.18);
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .seance-info-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        opacity: .72;
        margin-bottom: 2px;
    }
    .seance-info-value {
        font-size: .95rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .seance-info-value small {
        font-size: .78rem;
        font-weight: 400;
        opacity: .8;
        display: block;
    }

    /* Status pill inside hero */
    .seance-status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        border-radius: 99px;
        padding: 5px 14px;
        font-size: .82rem;
        font-weight: 700;
    }
    .seance-status-pill.present  { background: rgba(16,185,129,.22); border:1px solid rgba(16,185,129,.4); }
    .seance-status-pill.absent   { background: rgba(239,68,68,.22); border:1px solid rgba(239,68,68,.4); }
    .seance-status-pill.late     { background: rgba(245,158,11,.22); border:1px solid rgba(245,158,11,.4); }
    .seance-status-pill.not_signed { background: rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); }

    /* Quick action btns in hero — rapport (pill) vs modifier (icon-only) */
    .hero-action-btn {
        background: rgba(255,255,255,.15);
        border: 1px solid rgba(255,255,255,.3);
        color: white;
        border-radius: 99px;
        padding: 6px 16px;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }
    .hero-action-btn:hover { background: rgba(255,255,255,.28); }

    /* Séparateur "Modifier :" dans la zone d'action */
    .hero-edit-label {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .07em; opacity: .5; white-space: nowrap;
    }
    /* Boutons icon-only — clairement distincts du label statut */
    .hero-icon-btn {
        width: 34px; height: 34px;
        border-radius: 9px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.2);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: .88rem;
        cursor: pointer;
        transition: background .15s, transform .1s;
        flex-shrink: 0;
    }
    .hero-icon-btn:hover { background: rgba(255,255,255,.25); border-color: rgba(255,255,255,.4); transform: scale(1.1); }
    .hero-icon-btn.success-btn:hover { background: rgba(16,185,129,.35); border-color: rgba(16,185,129,.6); }
    .hero-icon-btn.danger-btn:hover  { background: rgba(239,68,68,.35);  border-color: rgba(239,68,68,.6); }

    /* ── Workflow card ─────────────────────────────────── */
    .workflow-step {
        display: flex; flex-direction: column; align-items: center;
        flex: 1;
        padding: 16px 8px;
        border-radius: 14px;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        transition: box-shadow .2s;
        min-width: 0;
    }
    .workflow-step:hover { box-shadow: 0 4px 12px rgba(0,0,0,.07); }
    .workflow-step.done  { border-color: #86efac; background: #f0fdf4; }
    .workflow-step.info  { border-color: #93c5fd; background: #eff6ff; }
    .workflow-icon {
        width: 48px; height: 48px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
        margin-bottom: 10px;
    }
    .workflow-arrow {
        color: #cbd5e1;
        font-size: 1.2rem;
        align-self: center;
        flex-shrink: 0;
        padding: 0 4px;
    }
    .workflow-label {
        font-size: .78rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
        text-align: center;
    }
    .workflow-time {
        font-size: .72rem;
        color: #64748b;
        margin-top: 4px;
    }

    /* Progress bar */
    .progress-seance {
        height: 6px;
        border-radius: 99px;
        background: #e2e8f0;
        overflow: hidden;
        margin-top: 16px;
    }
    .progress-seance-fill {
        height: 100%;
        border-radius: 99px;
        background: linear-gradient(90deg, #0453cb, #5e91de);
        transition: width .5s ease;
    }

    /* ── Rapport modal styles ───────────────────────── */
    .rapport-section {
        border-left: 4px solid;
        border-radius: 0 10px 10px 0;
        padding: 12px 16px;
        margin-bottom: 12px;
        background: #f8fafc;
    }
    .rapport-section h6 {
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 6px;
    }
    .rapport-section p { margin: 0; font-size: .9rem; color: #334155; }

    /* Responsive */
    @media (max-width: 768px) {
        .seance-hero { padding: 20px 18px; }
        .seance-info-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
        .seance-info-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        @php
            /* ── Détection séance future ── */
            $today  = \Carbon\Carbon::today();
            $seanceEstFuture = false;
            if ($seancesCour->date_seance) {
                $dateSeance = \Carbon\Carbon::parse($seancesCour->date_seance)->startOfDay();
                if ($dateSeance->gt($today)) {
                    $seanceEstFuture = true;
                } elseif ($dateSeance->eq($today) && $seancesCour->heure_fin) {
                    $seanceEstFuture = \Carbon\Carbon::parse($seancesCour->heure_fin)->gt(now());
                }
            }

            /* ── Résolution statut enseignant ── */
            $seanceDate = \Carbon\Carbon::parse($seancesCour->date_seance);

            $emargementDebutTemp = $seancesCour->teacherAttendances()
                ->whereDate('date', $today)->where('type', 'start')->first();
            if (!$emargementDebutTemp) {
                $emargementDebutTemp = $seancesCour->teacherAttendances()
                    ->whereDate('date', $seanceDate)->where('type', 'start')->first();
            }

            $emargementFinTemp = $seancesCour->teacherAttendances()
                ->whereDate('date', $today)->where('type', 'end')->first();
            if (!$emargementFinTemp) {
                $emargementFinTemp = $seancesCour->teacherAttendances()
                    ->whereDate('date', $seanceDate)->where('type', 'end')->first();
            }

            $teacherGlobalStatus = 'not_signed';
            if ($emargementDebutTemp || $emargementFinTemp) {
                $hasAbsent = ($emargementDebutTemp && $emargementDebutTemp->status === 'absent')
                          || ($emargementFinTemp  && $emargementFinTemp->status === 'absent');
                $hasLate   = ($emargementDebutTemp && $emargementDebutTemp->status === 'late')
                          || ($emargementFinTemp  && $emargementFinTemp->status === 'late');
                $teacherGlobalStatus = $hasAbsent ? 'absent' : ($hasLate ? 'late' : 'present');
            }

            /* ── Labels statut ── */
            $statusLabels = [
                'present'    => ['icon' => 'check-circle',  'label' => 'Présent',    'pill' => 'present'],
                'late'       => ['icon' => 'clock',         'label' => 'En retard',  'pill' => 'late'],
                'absent'     => ['icon' => 'times-circle',  'label' => 'Absent',     'pill' => 'absent'],
                'not_signed' => ['icon' => 'minus-circle',  'label' => 'Non émargé', 'pill' => 'not_signed'],
            ];
            $sl = $statusLabels[$teacherGlobalStatus] ?? $statusLabels['not_signed'];

            /* ── Session report ── */
            $hasReport = isset($seancesCour->sessionReport)
                      && $seancesCour->sessionReport
                      && $seancesCour->sessionReport->status === 'submitted';
        @endphp

        {{-- Header --}}
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-calendar-day me-2"></i>Détails de la séance</h1>
                <p class="header-subtitle">{{ $seancesCour->matiere?->name ?? 'N/A' }} · {{ $seancesCour->emploiTemps?->classe?->name ?? '' }}</p>
            </div>
            <div class="header-actions d-flex gap-2 flex-wrap">
                @if($hasReport)
                    <button type="button" class="btn-acasi warning" data-bs-toggle="modal" data-bs-target="#rapportModal">
                        <i class="fas fa-file-alt"></i>Rapport de cours
                    </button>
                @endif
                <a href="{{ route('esbtp.teacher-attendance.report') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour au rapport
                </a>
            </div>
        </div>

        {{-- ══ HERO CARD ══════════════════════════════════ --}}
        <div class="seance-hero mb-4">

            {{-- Badge "À venir" --}}
            @if($seanceEstFuture)
                <div class="badge-avenir">
                    <i class="fas fa-hourglass-half"></i>Cette séance n'a pas encore eu lieu
                </div>
            @endif

            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <div class="seance-hero-title">{{ $seancesCour->matiere?->name ?? 'Séance de cours' }}</div>
                    <div class="seance-hero-sub">
                        {{ $seancesCour->emploiTemps?->classe?->name ?? '' }}
                        @if($seancesCour->emploiTemps?->classe?->filiere?->name)
                            · {{ $seancesCour->emploiTemps->classe->filiere->name }}
                        @endif
                    </div>
                </div>

                {{-- Statut enseignant --}}
                <div class="d-flex flex-column align-items-end gap-2">

                    {{-- Ligne 1 : statut (information) + bouton rapport --}}
                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                        <span class="seance-status-pill {{ $sl['pill'] }}" id="teacher-status-badge">
                            <i class="fas fa-{{ $sl['icon'] }}" style="font-size:.8rem;"></i>{{ $sl['label'] }}
                        </span>
                        @if($hasReport)
                            <button type="button"
                                    class="hero-action-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#rapportModal"
                                    title="Voir rapport de cours">
                                <i class="fas fa-file-alt me-1"></i>Rapport
                            </button>
                        @endif
                    </div>

                    {{-- Ligne 2 : boutons icon-only pour modifier le statut --}}
                    @if(!$seanceEstFuture)
                    <div class="d-flex align-items-center gap-2" id="teacher-quick-actions">
                        <span class="hero-edit-label">Modifier :</span>
                        @if($teacherGlobalStatus !== 'present')
                            <button type="button"
                                    class="hero-icon-btn success-btn mark-teacher-status-btn"
                                    data-seance-id="{{ $seancesCour->id }}"
                                    data-status="present"
                                    data-type="start"
                                    title="Marquer présent">
                                <i class="fas fa-check"></i>
                            </button>
                        @endif
                        @if($teacherGlobalStatus !== 'absent')
                            <button type="button"
                                    class="hero-icon-btn danger-btn mark-teacher-status-btn"
                                    data-seance-id="{{ $seancesCour->id }}"
                                    data-status="absent"
                                    data-type="start"
                                    title="Marquer absent">
                                <i class="fas fa-user-times"></i>
                            </button>
                        @endif
                    </div>
                    <div class="teacher-status-spinner d-none">
                        <div class="spinner-border spinner-border-sm text-white" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Info grid --}}
            <div class="seance-info-grid">
                {{-- Enseignant --}}
                <div class="seance-info-item">
                    <div class="seance-info-icon"><i class="fas fa-user-tie"></i></div>
                    <div>
                        <div class="seance-info-label">Enseignant</div>
                        <div class="seance-info-value">
                            {{ $seancesCour->teacher?->user?->name ?? 'N/A' }}
                            @if($seancesCour->teacher?->user?->email)
                                <small>{{ $seancesCour->teacher->user->email }}</small>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Horaires --}}
                <div class="seance-info-item">
                    <div class="seance-info-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="seance-info-label">Horaires</div>
                        <div class="seance-info-value">
                            {{ $seancesCour->heure_debut ? \Carbon\Carbon::parse($seancesCour->heure_debut)->format('H:i') : 'N/A' }}
                            –
                            {{ $seancesCour->heure_fin ? \Carbon\Carbon::parse($seancesCour->heure_fin)->format('H:i') : 'N/A' }}
                        </div>
                    </div>
                </div>

                {{-- Date --}}
                <div class="seance-info-item">
                    <div class="seance-info-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div>
                        <div class="seance-info-label">Date</div>
                        <div class="seance-info-value">{{ $seancesCour->getDateCompleteFormattee() }}</div>
                    </div>
                </div>

                {{-- Classe --}}
                <div class="seance-info-item">
                    <div class="seance-info-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="seance-info-label">Classe</div>
                        <div class="seance-info-value">{{ $seancesCour->emploiTemps?->classe?->name ?? 'N/A' }}</div>
                    </div>
                </div>

                @if($seancesCour->salle)
                {{-- Salle --}}
                <div class="seance-info-item">
                    <div class="seance-info-icon"><i class="fas fa-door-open"></i></div>
                    <div>
                        <div class="seance-info-label">Salle</div>
                        <div class="seance-info-value">{{ $seancesCour->salle }}</div>
                    </div>
                </div>
                @endif

                {{-- Emploi du temps --}}
                <div class="seance-info-item">
                    <div class="seance-info-icon">
                        <i class="fas fa-{{ $seancesCour->emploiTemps?->is_active ? 'check' : 'pause' }}"></i>
                    </div>
                    <div>
                        <div class="seance-info-label">Emploi du temps</div>
                        <div class="seance-info-value">
                            @if($seancesCour->emploiTemps?->is_active)
                                <span style="color:#86efac; font-size:.82rem;">● Actif</span>
                            @else
                                <span style="color:rgba(255,255,255,.55); font-size:.82rem;">● Inactif</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            /* ── Workflow ── */
            $emargementDebut = $seancesCour->teacherAttendances()
                ->whereDate('date', $seanceDate)->where('type', 'start')->first();
            $emargementFin = $seancesCour->teacherAttendances()
                ->whereDate('date', $seanceDate)->where('type', 'end')->first();
            $workflow = \App\Models\ESBTPSessionWorkflow::where('seance_cours_id', $seancesCour->id)->first();

            $etapesTerminees = 0;
            if ($emargementDebut)                         $etapesTerminees++;
            if ($workflow && $workflow->call_start_done)  $etapesTerminees++;
            if ($emargementFin)                           $etapesTerminees++;
            if ($workflow && $workflow->call_end_done)    $etapesTerminees++;
            $progressionPct = ($etapesTerminees / 4) * 100;
        @endphp

        {{-- ══ WORKFLOW ══════════════════════════════════ --}}
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-tasks"></i>État du workflow
                </div>
                <div class="main-card-subtitle">
                    <strong class="text-primary">{{ number_format($progressionPct, 0) }}%</strong>
                    · {{ $etapesTerminees }}/4 étapes
                </div>
            </div>
            <div class="main-card-body">
                {{-- Steps --}}
                <div class="d-flex gap-2 align-items-stretch overflow-auto pb-1">

                    {{-- Émargement Début --}}
                    <div class="workflow-step {{ $emargementDebut ? 'done' : '' }}">
                        <div class="workflow-icon {{ $emargementDebut ? 'bg-success text-white' : 'bg-light text-muted' }}" style="background: {{ $emargementDebut ? '#bbf7d0' : '#f1f5f9' }} !important; color: {{ $emargementDebut ? '#15803d' : '#94a3b8' }} !important;">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <div class="workflow-label">Émargement Début</div>
                        @if($emargementDebut)
                            <span class="badge" style="background:#bbf7d0; color:#15803d; font-size:.72rem;">
                                <i class="fas fa-check me-1"></i>{{ ucfirst($emargementDebut->status) }}
                            </span>
                            <div class="workflow-time">{{ $emargementDebut->validated_at?->format('H:i') ?? $emargementDebut->created_at?->format('H:i') }}</div>
                        @else
                            <span class="badge bg-light text-muted border" style="font-size:.72rem;">Non fait</span>
                        @endif
                    </div>

                    <div class="workflow-arrow d-none d-md-flex"><i class="fas fa-chevron-right"></i></div>

                    {{-- Appel Début --}}
                    @php $callStartDone = $workflow && $workflow->call_start_done; @endphp
                    <div class="workflow-step {{ $callStartDone ? 'info' : '' }}">
                        <div class="workflow-icon" style="background: {{ $callStartDone ? '#bfdbfe' : '#f1f5f9' }}; color: {{ $callStartDone ? '#1d4ed8' : '#94a3b8' }};">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="workflow-label">Appel Début</div>
                        @if($callStartDone)
                            <span class="badge" style="background:#bfdbfe; color:#1d4ed8; font-size:.72rem;">
                                <i class="fas fa-check me-1"></i>Terminé
                            </span>
                            @if($workflow->call_start_at)
                                <div class="workflow-time">{{ \Carbon\Carbon::parse($workflow->call_start_at)->format('H:i') }}</div>
                            @endif
                        @else
                            <span class="badge bg-light text-muted border" style="font-size:.72rem;">En attente</span>
                        @endif
                    </div>

                    <div class="workflow-arrow d-none d-md-flex"><i class="fas fa-chevron-right"></i></div>

                    {{-- Émargement Fin --}}
                    <div class="workflow-step {{ $emargementFin ? 'done' : '' }}">
                        <div class="workflow-icon" style="background: {{ $emargementFin ? '#bbf7d0' : '#f1f5f9' }}; color: {{ $emargementFin ? '#15803d' : '#94a3b8' }};">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <div class="workflow-label">Émargement Fin</div>
                        @if($emargementFin)
                            <span class="badge" style="background:#bbf7d0; color:#15803d; font-size:.72rem;">
                                <i class="fas fa-check me-1"></i>{{ ucfirst($emargementFin->status) }}
                            </span>
                            <div class="workflow-time">{{ $emargementFin->validated_at?->format('H:i') ?? $emargementFin->created_at?->format('H:i') }}</div>
                        @else
                            <span class="badge bg-light text-muted border" style="font-size:.72rem;">Non fait</span>
                        @endif
                    </div>

                    <div class="workflow-arrow d-none d-md-flex"><i class="fas fa-chevron-right"></i></div>

                    {{-- Appel Fin --}}
                    @php $callEndDone = $workflow && $workflow->call_end_done; @endphp
                    <div class="workflow-step {{ $callEndDone ? 'info' : '' }}">
                        <div class="workflow-icon" style="background: {{ $callEndDone ? '#bfdbfe' : '#f1f5f9' }}; color: {{ $callEndDone ? '#1d4ed8' : '#94a3b8' }};">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="workflow-label">Appel Fin</div>
                        @if($callEndDone)
                            <span class="badge" style="background:#bfdbfe; color:#1d4ed8; font-size:.72rem;">
                                <i class="fas fa-check me-1"></i>Terminé
                            </span>
                            @if($workflow->call_end_at)
                                <div class="workflow-time">{{ \Carbon\Carbon::parse($workflow->call_end_at)->format('H:i') }}</div>
                            @endif
                        @else
                            <span class="badge bg-light text-muted border" style="font-size:.72rem;">En attente</span>
                        @endif
                    </div>
                </div>

                {{-- Progress bar --}}
                <div class="progress-seance">
                    <div class="progress-seance-fill" style="width: {{ $progressionPct }}%;"></div>
                </div>
            </div>
        </div>

        {{-- ══ DÉTAILS ÉMARGEMENTS ═══════════════════════ --}}
        @if($emargementDebut || $emargementFin)
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-clipboard-check"></i>Détails des émargements
                </div>
                <div class="main-card-subtitle">Informations techniques</div>
            </div>
            <div class="main-card-body">
                <div class="row g-4">
                    @if($emargementDebut)
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3" style="color:#15803d;"><i class="fas fa-sign-in-alt me-2"></i>Émargement Début</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered rounded-3 overflow-hidden">
                                <tbody>
                                    <tr><th style="width:40%;">Statut</th>
                                        <td>
                                            @if($emargementDebut->status === 'present') <span class="badge bg-success">Présent</span>
                                            @elseif($emargementDebut->status === 'late') <span class="badge bg-warning">En retard</span>
                                            @else <span class="badge bg-secondary">{{ ucfirst($emargementDebut->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><th>Date/Heure</th><td>{{ $emargementDebut->validated_at?->format('d/m/Y H:i:s') ?? $emargementDebut->created_at?->format('d/m/Y H:i:s') }}</td></tr>
                                    <tr><th>Adresse IP</th><td>{{ $emargementDebut->ip_address ?? 'N/A' }}</td></tr>
                                    <tr><th>Appareil</th><td>{{ $emargementDebut->device_info ?? 'N/A' }}</td></tr>
                                    @if($emargementDebut->latitude && $emargementDebut->longitude)
                                    <tr><th>Localisation</th><td>{{ $emargementDebut->latitude }}, {{ $emargementDebut->longitude }}</td></tr>
                                    @endif
                                    @if($emargementDebut->notes)
                                    <tr><th>Notes</th><td>{{ $emargementDebut->notes }}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($emargementFin)
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3" style="color:#15803d;"><i class="fas fa-sign-out-alt me-2"></i>Émargement Fin</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered rounded-3 overflow-hidden">
                                <tbody>
                                    <tr><th style="width:40%;">Statut</th>
                                        <td>
                                            @if($emargementFin->status === 'present') <span class="badge bg-success">Présent</span>
                                            @elseif($emargementFin->status === 'late') <span class="badge bg-warning">En retard</span>
                                            @else <span class="badge bg-secondary">{{ ucfirst($emargementFin->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><th>Date/Heure</th><td>{{ $emargementFin->validated_at?->format('d/m/Y H:i:s') ?? $emargementFin->created_at?->format('d/m/Y H:i:s') }}</td></tr>
                                    <tr><th>Adresse IP</th><td>{{ $emargementFin->ip_address ?? 'N/A' }}</td></tr>
                                    <tr><th>Appareil</th><td>{{ $emargementFin->device_info ?? 'N/A' }}</td></tr>
                                    @if($emargementFin->latitude && $emargementFin->longitude)
                                    <tr><th>Localisation</th><td>{{ $emargementFin->latitude }}, {{ $emargementFin->longitude }}</td></tr>
                                    @endif
                                    @if($emargementFin->notes)
                                    <tr><th>Notes</th><td>{{ $emargementFin->notes }}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ══ STATS PRÉSENCES ÉTUDIANTS ═══════════════════ --}}
        @php
            $attendancesStart = \App\Models\ESBTPAttendance::where('seance_cours_id', $seancesCour->id)->where('call_type', 'start')->get();
            $attendancesEnd   = \App\Models\ESBTPAttendance::where('seance_cours_id', $seancesCour->id)->where('call_type', 'end')->get();
            $statsStart = ['present' => $attendancesStart->where('status','present')->count(), 'absent' => $attendancesStart->where('status','absent')->count(), 'late' => $attendancesStart->where('status','late')->count(), 'excused' => $attendancesStart->where('status','excused')->count()];
            $statsEnd   = ['present' => $attendancesEnd->where('status','present')->count(),   'absent' => $attendancesEnd->where('status','absent')->count(),   'late' => $attendancesEnd->where('status','late')->count(),   'excused' => $attendancesEnd->where('status','excused')->count()];
            $hasAttendanceData = $attendancesStart->count() > 0 || $attendancesEnd->count() > 0;
        @endphp

        @if($hasAttendanceData)
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title"><i class="fas fa-users"></i>Présences étudiants</div>
                <div class="main-card-subtitle">État des appels</div>
            </div>
            <div class="main-card-body">
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px;">
                    @php
                        $atkpis = [
                            ['label'=>'Présents',  'color'=>'#10b981', 'accent'=>'rgba(16,185,129,.1)',  'icon'=>'check-circle',  'val'=>$statsStart['present']+$statsEnd['present'],  'sub'=>"Début:{$statsStart['present']} · Fin:{$statsEnd['present']}"],
                            ['label'=>'Absents',   'color'=>'#ef4444', 'accent'=>'rgba(239,68,68,.1)',   'icon'=>'user-times',     'val'=>$statsStart['absent']+$statsEnd['absent'],   'sub'=>"Début:{$statsStart['absent']} · Fin:{$statsEnd['absent']}"],
                            ['label'=>'Retards',   'color'=>'#f59e0b', 'accent'=>'rgba(245,158,11,.1)',  'icon'=>'clock',          'val'=>$statsStart['late']+$statsEnd['late'],       'sub'=>"Début:{$statsStart['late']} · Fin:{$statsEnd['late']}"],
                            ['label'=>'Excusés',   'color'=>'#6366f1', 'accent'=>'rgba(99,102,241,.1)',  'icon'=>'user-check',     'val'=>$statsStart['excused']+$statsEnd['excused'], 'sub'=>"Début:{$statsStart['excused']} · Fin:{$statsEnd['excused']}"],
                        ];
                    @endphp
                    @foreach($atkpis as $kpi)
                    <div style="background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:18px 20px; position:relative; overflow:hidden;">
                        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:{{ $kpi['color'] }};border-radius:14px 14px 0 0;"></div>
                        <div style="width:36px;height:36px;border-radius:10px;background:{{ $kpi['accent'] }};color:{{ $kpi['color'] }};display:flex;align-items:center;justify-content:center;margin-bottom:10px;">
                            <i class="fas fa-{{ $kpi['icon'] }}"></i>
                        </div>
                        <div style="font-size:1.8rem;font-weight:800;color:{{ $kpi['color'] }};line-height:1;">{{ $kpi['val'] }}</div>
                        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-top:4px;">{{ $kpi['label'] }}</div>
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:8px;padding-top:8px;border-top:1px solid #f1f5f9;">{{ $kpi['sub'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

{{-- ══ MODAL RAPPORT DE COURS ══════════════════════════ --}}
@if($hasReport)
@php $report = $seancesCour->sessionReport; @endphp
<div class="modal fade" id="rapportModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg,#0453cb,#5e91de); color:white;">
                <div>
                    <h5 class="modal-title mb-1"><i class="fas fa-file-alt me-2"></i>Rapport de cours</h5>
                    <div style="font-size:.85rem; opacity:.85;">
                        {{ $seancesCour->matiere?->name }} · {{ $seancesCour->emploiTemps?->classe?->name }}
                        · {{ $seancesCour->getDateCompleteFormattee() }}
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Rapport soumis</span>
                    @if($report->student_behavior ?? null)
                        <span class="badge bg-{{ $report->behavior_badge_color ?? 'secondary' }}">
                            Comportement : {{ $report->student_behavior_label ?? $report->student_behavior }}
                        </span>
                    @endif
                    @if($report->submitted_at ?? null)
                        <span class="badge bg-light text-dark border">
                            <i class="fas fa-calendar me-1"></i>Soumis le {{ \Carbon\Carbon::parse($report->submitted_at)->format('d/m/Y à H:i') }}
                        </span>
                    @endif
                </div>

                @if($report->content_summary ?? null)
                <div class="rapport-section" style="border-color:#3b82f6;">
                    <h6 style="color:#1d4ed8;"><i class="fas fa-book-open me-2"></i>Contenu enseigné</h6>
                    <p>{{ $report->content_summary }}</p>
                </div>
                @endif

                @if($report->teaching_methods ?? null)
                <div class="rapport-section" style="border-color:#06b6d4;">
                    <h6 style="color:#0e7490;"><i class="fas fa-chalkboard-teacher me-2"></i>Méthodes pédagogiques</h6>
                    <p>{{ $report->teaching_methods }}</p>
                </div>
                @endif

                @if($report->difficulties_encountered ?? null)
                <div class="rapport-section" style="border-color:#f59e0b;">
                    <h6 style="color:#92400e;"><i class="fas fa-exclamation-triangle me-2"></i>Difficultés rencontrées</h6>
                    <p>{{ $report->difficulties_encountered }}</p>
                </div>
                @endif

                @if($report->homework_assigned ?? null)
                <div class="rapport-section" style="border-color:#10b981;">
                    <h6 style="color:#065f46;"><i class="fas fa-tasks me-2"></i>Devoirs assignés</h6>
                    <p>{{ $report->homework_assigned }}</p>
                </div>
                @endif

                @if($report->next_session_preparation ?? null)
                <div class="rapport-section" style="border-color:#8b5cf6;">
                    <h6 style="color:#5b21b6;"><i class="fas fa-forward me-2"></i>Préparation prochaine séance</h6>
                    <p>{{ $report->next_session_preparation }}</p>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    function setLoading(isLoading) {
        const actions = document.getElementById('teacher-quick-actions');
        const spinner = document.querySelector('.teacher-status-spinner');
        if (actions) actions.classList.toggle('d-none', Boolean(isLoading));
        if (spinner) spinner.classList.toggle('d-none', !Boolean(isLoading));
    }

    function updateBadge(status) {
        const badge = document.getElementById('teacher-status-badge');
        if (!badge) return;
        const map = {
            present:    { icon:'check-circle',  label:'Présent',    cls:'present' },
            absent:     { icon:'times-circle',  label:'Absent',     cls:'absent' },
            late:       { icon:'clock',         label:'En retard',  cls:'late' },
            not_signed: { icon:'minus-circle',  label:'Non émargé', cls:'not_signed' },
        };
        const m = map[status] || map.not_signed;
        badge.className = `seance-status-pill ${m.cls}`;
        badge.innerHTML = `<i class="fas fa-${m.icon}" style="font-size:.8rem;"></i>${m.label}`;
    }

    function rebuildButtons(status) {
        const wrap = document.getElementById('teacher-quick-actions');
        if (!wrap) return;
        const id = '{{ $seancesCour->id }}';
        // Séparateur "Modifier :" + boutons icon-only (distincts du label statut)
        let html = '<span class="hero-edit-label">Modifier :</span>';
        if (status !== 'present') {
            html += `<button type="button" class="hero-icon-btn success-btn mark-teacher-status-btn"
                data-seance-id="${id}" data-status="present" data-type="start" title="Marquer présent">
                <i class="fas fa-check"></i></button>`;
        }
        if (status !== 'absent') {
            html += `<button type="button" class="hero-icon-btn danger-btn mark-teacher-status-btn"
                data-seance-id="${id}" data-status="absent" data-type="start" title="Marquer absent">
                <i class="fas fa-user-times"></i></button>`;
        }
        wrap.innerHTML = html;
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.mark-teacher-status-btn');
        if (!btn) return;
        e.preventDefault();

        const seanceId = btn.dataset.seanceId;
        const status   = btn.dataset.status;
        const type     = btn.dataset.type || 'start';

        if (!confirm(`Marquer cet enseignant ${status === 'present' ? 'présent' : 'absent'} ?`)) return;

        setLoading(true);

        fetch(`{{ url('/esbtp/teacher-attendance/seance') }}/${seanceId}/update-status`, {
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
            setLoading(false);
            if (data.success) {
                updateBadge(status);
                rebuildButtons(status);
            } else {
                alert('Erreur : ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(err => {
            setLoading(false);
            alert('Erreur réseau : ' + err.message);
        });
    }, true);
})();
</script>
@endpush
