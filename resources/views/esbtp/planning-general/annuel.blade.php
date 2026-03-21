@extends('layouts.app')

@section('title', 'Planning Annuel — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       Planning Annuel — Premium Redesign
       Prefix: pa- (planning-annuel)
       KLASSCI palette: #0453cb primary, #10b981 success
       ══════════════════════════════════════════════ */

    .pa-page { max-width: 1440px; margin: 0 auto; }

    /* ── Stats row ── */
    .pa-stats {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: .75rem; margin-bottom: 1.25rem;
        animation: pa-fadeUp .5s ease-out;
    }
    .pa-stat {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        padding: 1.15rem 1.25rem; display: flex; align-items: center; gap: .85rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: all .25s;
    }
    .pa-stat:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(4,83,203,.08); }
    .pa-stat-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .pa-stat--seances .pa-stat-icon { background: rgba(4,83,203,.08); color: #0453cb; }
    .pa-stat--heures .pa-stat-icon  { background: rgba(16,185,129,.08); color: #10b981; }
    .pa-stat--planif .pa-stat-icon  { background: rgba(251,191,36,.08); color: #f59e0b; }
    .pa-stat--events .pa-stat-icon  { background: rgba(129,140,248,.08); color: #6366f1; }
    .pa-stat-value { font-size: 1.45rem; font-weight: 700; color: #1e293b; line-height: 1; }
    .pa-stat-label { font-size: .75rem; color: #64748b; margin-top: .15rem; }
    .pa-stat-sub { font-size: .7rem; color: #94a3b8; margin-top: .2rem; }

    /* ── Timeline ── */
    .pa-timeline-section {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        padding: 1.5rem; margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        animation: pa-fadeUp .5s ease-out .1s both;
    }
    .pa-section-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 1.25rem;
    }
    .pa-section-title {
        font-size: 1rem; font-weight: 700; color: #1e293b;
        display: flex; align-items: center; gap: .5rem;
    }
    .pa-section-title i { color: #0453cb; font-size: .9rem; }
    .pa-section-count {
        font-size: .75rem; color: #94a3b8; font-weight: 500;
        background: #f1f5f9; padding: .25rem .65rem; border-radius: 20px;
    }

    .pa-timeline { display: flex; flex-direction: column; gap: .65rem; }
    .pa-event {
        display: flex; align-items: center; gap: 1rem;
        padding: .85rem 1rem; border-radius: 12px;
        border: 1px solid #f1f5f9; transition: all .2s;
        position: relative;
    }
    .pa-event:hover { background: #f8fafc; border-color: #e2e8f0; }
    .pa-event::before {
        content: ''; position: absolute; left: 0; top: .5rem; bottom: .5rem;
        width: 3px; border-radius: 3px;
    }
    .pa-event[data-type="rentree"]::before   { background: #10b981; }
    .pa-event[data-type="examens"]::before   { background: #f59e0b; }
    .pa-event[data-type="ceremonie"]::before { background: #0453cb; }
    .pa-event[data-type="vacances"]::before  { background: #94a3b8; }

    .pa-event-date {
        min-width: 52px; text-align: center; flex-shrink: 0;
    }
    .pa-event-day { font-size: 1.35rem; font-weight: 700; color: #1e293b; line-height: 1; }
    .pa-event-month { font-size: .65rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-top: .15rem; }

    .pa-event-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem; flex-shrink: 0;
    }
    .pa-event[data-type="rentree"] .pa-event-icon   { background: rgba(16,185,129,.1); color: #10b981; }
    .pa-event[data-type="examens"] .pa-event-icon   { background: rgba(245,158,11,.1); color: #f59e0b; }
    .pa-event[data-type="ceremonie"] .pa-event-icon { background: rgba(4,83,203,.08); color: #0453cb; }
    .pa-event[data-type="vacances"] .pa-event-icon  { background: rgba(148,163,184,.1); color: #64748b; }

    .pa-event-info { flex: 1; min-width: 0; }
    .pa-event-title { font-size: .88rem; font-weight: 600; color: #1e293b; }
    .pa-event-desc { font-size: .78rem; color: #64748b; margin-top: .1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .pa-event-badge {
        font-size: .65rem; font-weight: 600; padding: .2rem .55rem;
        border-radius: 6px; text-transform: capitalize; flex-shrink: 0;
    }
    .pa-event[data-type="rentree"] .pa-event-badge   { background: rgba(16,185,129,.1); color: #059669; }
    .pa-event[data-type="examens"] .pa-event-badge   { background: rgba(245,158,11,.1); color: #d97706; }
    .pa-event[data-type="ceremonie"] .pa-event-badge { background: rgba(4,83,203,.08); color: #0453cb; }
    .pa-event[data-type="vacances"] .pa-event-badge  { background: rgba(148,163,184,.1); color: #64748b; }

    /* ── Calendar ── */
    .pa-cal-section {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.04);
        animation: pa-fadeUp .5s ease-out .2s both;
    }

    /* Légende — compact centered */
    .pa-legend {
        display: flex; gap: .75rem; margin-bottom: 1rem; flex-wrap: wrap;
        justify-content: center;
    }
    .pa-legend-item {
        display: flex; align-items: center; gap: .3rem; font-size: .7rem; color: #64748b;
    }
    .pa-legend-dot {
        width: 8px; height: 8px; border-radius: 2px;
    }

    /* Controls — compact centered */
    .pa-cal-controls {
        display: flex; align-items: center; justify-content: center;
        margin-bottom: .75rem; gap: 1.5rem; flex-wrap: wrap;
    }
    .pa-cal-nav {
        display: flex; align-items: center; gap: .4rem;
    }
    .pa-cal-nav-btn {
        width: 30px; height: 30px; border-radius: 8px;
        border: 1px solid #e2e8f0; background: #fff; color: #64748b;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all .2s; font-size: .72rem;
    }
    .pa-cal-nav-btn:hover:not(:disabled) { background: #f1f5f9; color: #0453cb; border-color: #0453cb; }
    .pa-cal-nav-btn:disabled { opacity: .4; cursor: default; }
    .pa-cal-month {
        font-size: .95rem; font-weight: 700; color: #1e293b;
        min-width: 160px; text-align: center; text-transform: capitalize;
    }
    .pa-cal-meta {
        display: flex; align-items: center; gap: .4rem;
        font-size: .72rem; color: #94a3b8;
    }

    /* Viewport & Slider */
    .pa-cal-viewport {
        overflow: hidden; border-radius: 12px;
    }
    .pa-cal-slider {
        display: flex; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .pa-cal-slide {
        min-width: 100%; flex-shrink: 0;
    }

    /* Grid — balanced */
    .pa-cal-grid {
        display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px;
        max-width: 900px; margin: 0 auto;
    }
    .pa-cal-header {
        padding: .45rem .25rem; text-align: center;
        font-size: .68rem; font-weight: 600; color: #94a3b8;
        text-transform: uppercase; letter-spacing: .06em;
    }
    .pa-cal-day {
        padding: .75rem 0; display: flex; align-items: center; justify-content: center;
        font-size: .85rem; font-weight: 500; color: #334155;
        border-radius: 8px; cursor: default; position: relative;
        transition: all .15s; min-height: 48px;
    }
    .pa-cal-day.autre-mois { color: #cbd5e1; }
    .pa-cal-day.aujourd-hui {
        background: #0453cb; color: #fff; font-weight: 700;
        box-shadow: 0 2px 6px rgba(4,83,203,.3);
    }
    .pa-cal-day.avec-evenement:not(.aujourd-hui) {
        background: rgba(245,158,11,.08); color: #92400e; font-weight: 600;
    }
    .pa-cal-day.avec-evenement .pa-cal-dot {
        position: absolute; bottom: 3px; left: 50%; transform: translateX(-50%);
        width: 3px; height: 3px; border-radius: 50%; background: #f59e0b;
    }
    .pa-cal-day.aujourd-hui .pa-cal-dot { background: #fff; }
    .pa-cal-day.avec-evenement:hover {
        background: rgba(245,158,11,.15);
    }

    /* Shortcuts — compact centered */
    .pa-cal-shortcuts {
        display: flex; gap: .35rem; margin-top: .75rem; flex-wrap: wrap;
        justify-content: center;
    }
    .pa-shortcut {
        padding: .3rem .7rem; border-radius: 6px;
        border: 1px solid #e2e8f0; background: #fff;
        font-size: .7rem; font-weight: 500; color: #64748b;
        cursor: pointer; transition: all .2s;
    }
    .pa-shortcut:hover, .pa-shortcut.active {
        background: #0453cb; color: #fff; border-color: #0453cb;
    }

    /* Animations */
    @keyframes pa-fadeUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }

    @media (max-width: 768px) {
        .pa-stats { grid-template-columns: repeat(2, 1fr); }
        .pa-event { flex-wrap: wrap; }
        .pa-cal-month { min-width: 120px; font-size: .9rem; }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <x-planning-header
            title="Planning Annuel"
            subtitle="Calendrier académique et répartition annuelle des cours"
            active-tab="annuel"
            :annee-selectionnee="$anneeSelectionnee"
            :annees="$annees"
            :stats="$stats ?? null"
        />

        <div id="pg-tab-content">
        <div class="pa-page">

        {{-- Stats row --}}
        <div class="pa-stats">
            @if(!empty($statistiquesMensuelles))
                @foreach(array_slice($statistiquesMensuelles, 0, 3) as $index => $stat)
                <div class="pa-stat pa-stat--{{ $index === 0 ? 'seances' : ($index === 1 ? 'heures' : 'planif') }}">
                    <div class="pa-stat-icon">
                        <i class="fas fa-{{ $index === 0 ? 'calendar-check' : ($index === 1 ? 'clock' : 'graduation-cap') }}"></i>
                    </div>
                    <div>
                        <div class="pa-stat-value">{{ $stat['total_seances'] }}</div>
                        <div class="pa-stat-label">Séances — {{ $stat['mois'] }}</div>
                        <div class="pa-stat-sub">{{ $stat['total_heures'] }}h &middot; {{ $stat['total_planifications'] }} planif.</div>
                    </div>
                </div>
                @endforeach
            @endif
            <div class="pa-stat pa-stat--events">
                <div class="pa-stat-icon"><i class="fas fa-star"></i></div>
                <div>
                    <div class="pa-stat-value">{{ count($evenementsAcademiques) }}</div>
                    <div class="pa-stat-label">Événements majeurs</div>
                    <div class="pa-stat-sub">
                        {{ collect($evenementsAcademiques)->where('type', 'examens')->count() }} examens &middot;
                        {{ collect($evenementsAcademiques)->whereIn('type', ['ceremonie', 'rentree'])->count() }} cérémonies
                    </div>
                </div>
            </div>
        </div>

        {{-- Timeline des événements --}}
        <div class="pa-timeline-section">
            <div class="pa-section-head">
                <div class="pa-section-title">
                    <i class="fas fa-stream"></i>Événements Académiques
                </div>
                <span class="pa-section-count">{{ count($evenementsAcademiques) }} événement{{ count($evenementsAcademiques) > 1 ? 's' : '' }}</span>
            </div>

            <div class="pa-timeline">
                @forelse($evenementsAcademiques as $evenement)
                <div class="pa-event" data-type="{{ $evenement['type'] }}">
                    <div class="pa-event-date">
                        <div class="pa-event-day">{{ \Carbon\Carbon::createFromFormat('d/m/Y', $evenement['date'])->format('d') }}</div>
                        <div class="pa-event-month">{{ \Carbon\Carbon::createFromFormat('d/m/Y', $evenement['date'])->translatedFormat('M Y') }}</div>
                    </div>
                    <div class="pa-event-icon"><i class="fas fa-{{ $evenement['icon'] }}"></i></div>
                    <div class="pa-event-info">
                        <div class="pa-event-title">{{ $evenement['titre'] }}</div>
                        <div class="pa-event-desc">{{ $evenement['description'] }}</div>
                    </div>
                    <div class="pa-event-badge">{{ ucfirst($evenement['type']) }}</div>
                </div>
                @empty
                <div style="text-align:center; padding:2rem; color:#94a3b8;">
                    <i class="fas fa-calendar-times" style="font-size:1.5rem; margin-bottom:.5rem; display:block;"></i>
                    Aucun événement académique configuré
                </div>
                @endforelse
            </div>
        </div>

        {{-- Calendrier interactif --}}
        <div class="pa-cal-section">
            {{-- Légende --}}
            <div class="pa-legend">
                <div class="pa-legend-item"><div class="pa-legend-dot" style="background:#0453cb;"></div>Aujourd'hui</div>
                <div class="pa-legend-item"><div class="pa-legend-dot" style="background:rgba(245,158,11,.3);"></div>Événements</div>
                <div class="pa-legend-item"><div class="pa-legend-dot" style="background:#10b981;"></div>Rentrée</div>
                <div class="pa-legend-item"><div class="pa-legend-dot" style="background:#f59e0b;"></div>Examens</div>
                <div class="pa-legend-item"><div class="pa-legend-dot" style="background:#0453cb;"></div>Cérémonies</div>
                <div class="pa-legend-item"><div class="pa-legend-dot" style="background:#94a3b8;"></div>Vacances</div>
            </div>

            {{-- Controls --}}
            <div class="pa-cal-controls">
                <div class="pa-cal-nav">
                    <button class="pa-cal-nav-btn" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                    <div class="pa-cal-month" id="currentMonth">{{ $calendrierMensuel[0]['nom'] ?? '' }}</div>
                    <button class="pa-cal-nav-btn" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="pa-cal-meta">
                    <span id="monthEvents">0 événement</span>
                    <span>&middot;</span>
                    <span id="monthProgress">0%</span>
                </div>
            </div>

            {{-- Slider --}}
            <div class="pa-cal-viewport">
                <div class="pa-cal-slider" id="calendarSlider">
                    @foreach($calendrierMensuel as $index => $moisData)
                    <div class="pa-cal-slide" data-month="{{ $index }}" data-month-id="{{ $moisData['mois'] }}">
                        <div class="pa-cal-grid">
                            <div class="pa-cal-header">Lun</div>
                            <div class="pa-cal-header">Mar</div>
                            <div class="pa-cal-header">Mer</div>
                            <div class="pa-cal-header">Jeu</div>
                            <div class="pa-cal-header">Ven</div>
                            <div class="pa-cal-header">Sam</div>
                            <div class="pa-cal-header">Dim</div>

                            @foreach($moisData['semaines'] as $semaine)
                                @foreach($semaine as $jour)
                                @php
                                    $dateJour = $jour['date']->format('d/m/Y');
                                    $evenementsJour = collect($evenementsAcademiques)->filter(fn($evt) => $evt['date'] === $dateJour);
                                    $aEvenement = $evenementsJour->count() > 0;
                                @endphp
                                <div class="pa-cal-day
                                    {{ !$jour['dans_mois'] ? 'autre-mois' : '' }}
                                    {{ $jour['est_aujourd_hui'] ? 'aujourd-hui' : '' }}
                                    {{ $aEvenement ? 'avec-evenement' : '' }}"
                                    title="{{ $dateJour }}{{ $aEvenement ? ' — ' . $evenementsJour->count() . ' événement(s)' : '' }}"
                                    data-date="{{ $dateJour }}"
                                    data-events="{{ $evenementsJour->count() }}">
                                    {{ $jour['date']->day }}
                                    @if($aEvenement)<div class="pa-cal-dot"></div>@endif
                                </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Shortcuts --}}
            <div class="pa-cal-shortcuts">
                <button class="pa-shortcut" data-action="today">Aujourd'hui</button>
                <button class="pa-shortcut" data-action="rentree">Rentrée</button>
                <button class="pa-shortcut" data-action="examens">Examens</button>
                <button class="pa-shortcut" data-action="ceremonie">Cérémonie</button>
            </div>
        </div>

        {{-- ══ Gestion des événements (fusionné depuis tab Événements) ══ --}}
        <div class="pa-timeline-section" style="margin-top:1.25rem;">
            <div class="pa-section-head">
                <div class="pa-section-title">
                    <i class="fas fa-calendar-check"></i>Gestion des Événements
                </div>
                <a href="{{ route('esbtp.evenements-academiques.create', ['annee_id' => $anneeSelectionnee->id]) }}"
                   style="display:inline-flex; align-items:center; gap:.4rem; padding:.45rem 1rem; border-radius:9px; font-size:.8rem; font-weight:600; color:#fff; background:linear-gradient(135deg,#0453cb,#3b7ddb); text-decoration:none; border:none; box-shadow:0 2px 6px rgba(4,83,203,.2); transition:all .2s;">
                    <i class="fas fa-plus" style="font-size:.7rem;"></i>Nouvel événement
                </a>
            </div>

            {{-- Alerte événements manquants --}}
            @php
                $hasRentree = ($evenementsModels ?? collect())->where('type', 'rentree')->count() > 0;
                $hasFermeture = ($evenementsModels ?? collect())->where('type', 'fermeture')->count() > 0;
            @endphp
            @if(!$hasRentree || !$hasFermeture)
            <div style="background:#fef3c7; border:1px solid #fcd34d; border-radius:10px; padding:.75rem 1rem; margin-bottom:1rem; display:flex; align-items:center; gap:.75rem; flex-wrap:wrap;">
                <i class="fas fa-exclamation-triangle" style="color:#d97706; font-size:.85rem;"></i>
                <span style="font-size:.82rem; color:#92400e; flex:1;">
                    @if(!$hasRentree && !$hasFermeture) Rentrée et fermeture non définies
                    @elseif(!$hasRentree) Rentrée non définie
                    @else Fermeture non définie @endif
                    pour {{ $anneeSelectionnee->name }}
                </span>
                <div style="display:flex; gap:.35rem;">
                    @if(!$hasRentree)
                    <button type="button" onclick="openQuickEventModal('rentree')"
                       style="padding:.3rem .7rem; border-radius:6px; font-size:.72rem; font-weight:600; color:#fff; background:#10b981; border:none; cursor:pointer;">
                        <i class="fas fa-plus" style="font-size:.6rem;"></i> Rentrée
                    </button>
                    @endif
                    @if(!$hasFermeture)
                    <button type="button" onclick="openQuickEventModal('fermeture')"
                       style="padding:.3rem .7rem; border-radius:6px; font-size:.72rem; font-weight:600; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0; cursor:pointer;">
                        <i class="fas fa-plus" style="font-size:.6rem;"></i> Fermeture
                    </button>
                    @endif
                </div>
            </div>
            @endif

            {{-- Table des événements --}}
            @if(($evenementsModels ?? collect())->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:separate; border-spacing:0;">
                    <thead>
                        <tr>
                            <th style="padding:.6rem .75rem; font-size:.7rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e8ecf1; text-align:left;">Événement</th>
                            <th style="padding:.6rem .75rem; font-size:.7rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e8ecf1; text-align:left;">Type</th>
                            <th style="padding:.6rem .75rem; font-size:.7rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e8ecf1; text-align:left;">Date</th>
                            <th style="padding:.6rem .75rem; font-size:.7rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e8ecf1; text-align:left;">Statut</th>
                            <th style="padding:.6rem .75rem; font-size:.7rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e8ecf1; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($evenementsModels as $evt)
                        @php
                            $typeColors = ['rentree'=>'#10b981','examens'=>'#f59e0b','ceremonie'=>'#0453cb','vacances'=>'#94a3b8','fermeture'=>'#64748b','reprise'=>'#10b981','soutenances'=>'#6366f1','stage'=>'#06b6d4','reunion'=>'#8b5cf6','orientation'=>'#06b6d4'];
                            $statutColors = ['planifie'=>'#f59e0b','confirme'=>'#10b981','annule'=>'#ef4444','reporte'=>'#f97316','termine'=>'#64748b'];
                            $tc = $typeColors[$evt->type] ?? '#64748b';
                            $sc = $statutColors[$evt->statut] ?? '#94a3b8';
                        @endphp
                        <tr style="transition:background .15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <td style="padding:.7rem .75rem; border-bottom:1px solid #f1f5f9;">
                                <div style="font-size:.85rem; font-weight:600; color:#1e293b;">{{ $evt->titre }}</div>
                                @if($evt->description)
                                <div style="font-size:.72rem; color:#94a3b8; margin-top:.1rem; max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $evt->description }}</div>
                                @endif
                            </td>
                            <td style="padding:.7rem .75rem; border-bottom:1px solid #f1f5f9;">
                                <span style="display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .5rem; border-radius:5px; font-size:.7rem; font-weight:600; background:{{ $tc }}15; color:{{ $tc }};">
                                    <i class="fas fa-{{ \App\Models\ESBTPEvenementAcademique::ICONES_TYPES[$evt->type] ?? 'calendar' }}" style="font-size:.6rem;"></i>
                                    {{ $evt->type_libelle }}
                                </span>
                            </td>
                            <td style="padding:.7rem .75rem; border-bottom:1px solid #f1f5f9; font-size:.82rem; color:#334155; white-space:nowrap;">
                                {{ $evt->date_debut?->format('d/m/Y') }}
                                @if($evt->date_fin && $evt->date_fin->ne($evt->date_debut))
                                    <span style="color:#94a3b8;">→</span> {{ $evt->date_fin->format('d/m/Y') }}
                                @endif
                            </td>
                            <td style="padding:.7rem .75rem; border-bottom:1px solid #f1f5f9;">
                                <span style="padding:.2rem .5rem; border-radius:5px; font-size:.68rem; font-weight:600; background:{{ $sc }}15; color:{{ $sc }};">
                                    {{ $evt->statut_libelle }}
                                </span>
                            </td>
                            <td style="padding:.7rem .75rem; border-bottom:1px solid #f1f5f9; text-align:right;">
                                <div style="display:flex; gap:.3rem; justify-content:flex-end;">
                                    <a href="{{ route('esbtp.evenements-academiques.edit', $evt) }}" title="Modifier"
                                       style="width:28px; height:28px; border-radius:6px; border:1px solid #e2e8f0; background:#fff; display:inline-flex; align-items:center; justify-content:center; color:#64748b; font-size:.7rem; text-decoration:none; transition:all .15s;"
                                       onmouseover="this.style.borderColor='#0453cb';this.style.color='#0453cb'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b'">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form method="POST" action="{{ route('esbtp.evenements-academiques.destroy', $evt) }}" style="display:inline;" onsubmit="return confirm('Supprimer cet événement ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Supprimer"
                                            style="width:28px; height:28px; border-radius:6px; border:1px solid #e2e8f0; background:#fff; display:inline-flex; align-items:center; justify-content:center; color:#64748b; font-size:.7rem; cursor:pointer; transition:all .15s;"
                                            onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b'">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align:center; padding:1.5rem; color:#94a3b8;">
                <i class="fas fa-calendar-plus" style="font-size:1.2rem; margin-bottom:.4rem; display:block;"></i>
                <div style="font-size:.85rem;">Aucun événement pour cette année</div>
                <div style="font-size:.75rem; margin-top:.2rem;">Utilisez le bouton ci-dessus pour en créer</div>
            </div>
            @endif
        </div>

        </div>
        </div>{{-- #pg-tab-content --}}
    </div>
</div>
{{-- Modal rapide rentrée/fermeture --}}
<div class="modal fade" id="quickEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.15);">
            <form method="POST" action="{{ route('esbtp.evenements-academiques.store') }}">
                @csrf
                <input type="hidden" name="annee_universitaire_id" value="{{ $anneeSelectionnee->id }}">
                <input type="hidden" name="type" id="qe-type">
                <input type="hidden" name="statut" value="confirme">

                <div class="modal-header" style="background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb); border:none; padding:1.25rem 1.5rem;">
                    <div>
                        <h5 class="modal-title" style="color:#fff; font-weight:700; font-size:1rem; margin:0;" id="qe-modal-title">Créer événement</h5>
                        <div style="font-size:.75rem; color:rgba(255,255,255,.7); margin-top:.15rem;">{{ $anneeSelectionnee->name }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:brightness(0) invert(1); opacity:.7;"></button>
                </div>

                <div class="modal-body" style="padding:1.25rem 1.5rem;">
                    <div style="margin-bottom:1rem;">
                        <label style="font-size:.75rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.4rem; display:block;">Titre</label>
                        <input type="text" name="titre" id="qe-titre" required
                            style="width:100%; padding:.5rem .75rem; border-radius:9px; border:1px solid #e2e8f0; font-size:.88rem;">
                    </div>

                    <div style="display:flex; gap:.75rem; margin-bottom:1rem;">
                        <div style="flex:1;">
                            <label style="font-size:.75rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.4rem; display:block;">Date début</label>
                            <input type="date" name="date_debut" id="qe-date-debut" required
                                style="width:100%; padding:.5rem .75rem; border-radius:9px; border:1px solid #e2e8f0; font-size:.85rem;">
                        </div>
                        <div style="flex:1;">
                            <label style="font-size:.75rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.4rem; display:block;">Date fin</label>
                            <input type="date" name="date_fin" id="qe-date-fin"
                                style="width:100%; padding:.5rem .75rem; border-radius:9px; border:1px solid #e2e8f0; font-size:.85rem;">
                        </div>
                    </div>

                    <div style="background:#f0f4ff; border-radius:9px; padding:.6rem .85rem; display:flex; align-items:center; gap:.5rem; cursor:pointer;" id="qe-sync-btn">
                        <input type="checkbox" id="qe-sync" checked style="accent-color:#0453cb;">
                        <label for="qe-sync" style="font-size:.78rem; color:#334155; cursor:pointer; margin:0;">
                            <span style="font-weight:600;">Synchroniser</span> avec la période de l'année universitaire
                        </label>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid #e8ecf1; padding:.85rem 1.5rem; background:#fff; gap:.4rem;">
                    <button type="button" class="btn" data-bs-dismiss="modal"
                        style="border-radius:9px; padding:.45rem 1rem; font-size:.82rem; font-weight:600; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0;">
                        Annuler
                    </button>
                    <button type="submit" class="btn"
                        style="border-radius:9px; padding:.45rem 1rem; font-size:.82rem; font-weight:600; color:#fff; background:linear-gradient(135deg,#0453cb,#3b7ddb); border:none; box-shadow:0 2px 6px rgba(4,83,203,.25);">
                        <i class="fas fa-check me-1" style="font-size:.7rem;"></i>Créer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Quick event modal
const anneeStart = '{{ $anneeSelectionnee->start_date }}';
const anneeEnd = '{{ $anneeSelectionnee->end_date }}';

function openQuickEventModal(type) {
    const titles = { rentree: 'Rentrée académique', fermeture: 'Fermeture de l\'année' };
    const modalTitles = { rentree: 'Créer la Rentrée', fermeture: 'Créer la Fermeture' };

    $('#qe-type').val(type);
    $('#qe-titre').val(titles[type] || '');
    $('#qe-modal-title').text(modalTitles[type] || 'Créer événement');

    // Sync dates avec l'année universitaire
    if (type === 'rentree') {
        $('#qe-date-debut').val(anneeStart);
        $('#qe-date-fin').val(anneeStart);
    } else if (type === 'fermeture') {
        $('#qe-date-debut').val(anneeEnd);
        $('#qe-date-fin').val(anneeEnd);
    }

    $('#qe-sync').prop('checked', true);
    new bootstrap.Modal(document.getElementById('quickEventModal')).show();
}

$('#qe-sync').on('change', function() {
    if (this.checked) {
        const type = $('#qe-type').val();
        if (type === 'rentree') {
            $('#qe-date-debut').val(anneeStart);
            $('#qe-date-fin').val(anneeStart);
        } else if (type === 'fermeture') {
            $('#qe-date-debut').val(anneeEnd);
            $('#qe-date-fin').val(anneeEnd);
        }
    }
});

$(function() {
    const slider = $('#calendarSlider');
    const prevBtn = $('#prevMonth');
    const nextBtn = $('#nextMonth');
    const currentMonthDisplay = $('#currentMonth');
    const monthEventsDisplay = $('#monthEvents');
    const monthProgressDisplay = $('#monthProgress');

    let currentMonthIndex = 0;
    const totalMonths = $('.pa-cal-slide').length;
    const evenements = @json($evenementsAcademiques);
    const monthNames = @json(array_column($calendrierMensuel, 'nom'));

    function updateMonthDisplay() {
        currentMonthDisplay.text(monthNames[currentMonthIndex] || '');
        const currentSlide = $(`.pa-cal-slide[data-month="${currentMonthIndex}"]`);
        const monthId = currentSlide.data('month-id');
        const monthEvents = evenements.filter(evt => {
            const eventMonth = evt.date.split('/').reverse().join('-').substring(0, 7);
            return eventMonth === monthId;
        });
        monthEventsDisplay.text(monthEvents.length + ' événement' + (monthEvents.length > 1 ? 's' : ''));
        monthProgressDisplay.text(Math.round((currentMonthIndex / Math.max(totalMonths - 1, 1)) * 100) + '%');
        prevBtn.prop('disabled', currentMonthIndex === 0);
        nextBtn.prop('disabled', currentMonthIndex === totalMonths - 1);
    }

    function navigateToMonth(index) {
        if (index < 0 || index >= totalMonths) return;
        currentMonthIndex = index;
        slider.css('transform', `translateX(${-index * 100}%)`);
        updateMonthDisplay();
    }

    prevBtn.on('click', () => currentMonthIndex > 0 && navigateToMonth(currentMonthIndex - 1));
    nextBtn.on('click', () => currentMonthIndex < totalMonths - 1 && navigateToMonth(currentMonthIndex + 1));

    $('.pa-shortcut').on('click', function() {
        const action = $(this).data('action');
        $('.pa-shortcut').removeClass('active');
        $(this).addClass('active');

        switch(action) {
            case 'today':
                const today = new Date();
                let target = 0;
                $('.pa-cal-slide').each(function(i) {
                    const mid = $(this).data('month-id');
                    if (mid) {
                        const [y, m] = mid.split('-');
                        if (parseInt(y) === today.getFullYear() && parseInt(m) === today.getMonth() + 1) {
                            target = i; return false;
                        }
                    }
                });
                navigateToMonth(target);
                break;
            case 'rentree': navigateToMonth(0); break;
            case 'examens':
                const ei = monthNames.findIndex(n => n.toLowerCase().includes('décembre'));
                if (ei !== -1) navigateToMonth(ei);
                break;
            case 'ceremonie': navigateToMonth(totalMonths - 1); break;
        }
        setTimeout(() => $(this).removeClass('active'), 1000);
    });

    // Keyboard
    $(document).on('keydown', function(e) {
        if (e.key === 'ArrowLeft') prevBtn.click();
        else if (e.key === 'ArrowRight') nextBtn.click();
    });

    // Swipe
    let startX = 0;
    $('.pa-cal-viewport').on('touchstart', e => startX = e.originalEvent.touches[0].clientX);
    $('.pa-cal-viewport').on('touchmove', e => e.preventDefault());
    $('.pa-cal-viewport').on('touchend', function(e) {
        const diff = startX - e.originalEvent.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) { diff > 0 ? nextBtn.click() : prevBtn.click(); }
    });

    // Tooltips
    $('.pa-cal-day.avec-evenement').each(function() {
        const date = $(this).data('date');
        const dayEvents = evenements.filter(evt => evt.date === date);
        let tt = date + '\n';
        dayEvents.forEach(evt => tt += '• ' + evt.titre + '\n');
        $(this).attr('title', tt);
    });

    // Stagger timeline entries
    $('.pa-event').each(function(i) {
        $(this).css({ opacity: 0, transform: 'translateX(-20px)' });
        setTimeout(() => {
            $(this).css({ opacity: 1, transform: 'translateX(0)', transition: 'all .4s ease-out' });
        }, i * 80);
    });

    updateMonthDisplay();
});
</script>
@endpush
