@extends('layouts.app')

@section('title', 'Tableau de bord enseignant')

@push('styles')
<style>
    .ted { --ted-p:#0453cb; --ted-pd:#033a8e; --ted-a:#3b7ddb; --ted-s:#5e91de; --ted-dark:#0f172a; --ted-text:#1e293b; --ted-muted:#64748b; --ted-line:#e2e8f0; --ted-surface:#f8fafc; --ted-ok:#10b981; --ted-warn:#f59e0b; --ted-ko:#dc2626; }
    .ted-hero { background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%); border-radius:18px; padding:2rem 2.5rem 1.5rem; color:#fff; margin-bottom:1.25rem; }
    .ted-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
    .ted-hero-left { display:flex; align-items:center; gap:1rem; min-width:0; }
    .ted-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .ted-hero h1 { font-size:1.45rem; font-weight:700; color:#fff; margin:0; }
    .ted-hero p { color:rgba(255,255,255,.75); font-size:.88rem; margin:0; }
    .ted-hero-actions { display:flex; gap:.5rem; flex-wrap:wrap; }
    .ted-btn { display:inline-flex; align-items:center; gap:.45rem; border-radius:10px; padding:.55rem 1rem; font-size:.84rem; font-weight:600; text-decoration:none; border:1px solid transparent; white-space:nowrap; transition:background .2s ease, color .2s ease; }
    .ted-btn--white { background:#fff; color:var(--ted-p); }
    .ted-btn--white:hover { background:#eef4fd; color:var(--ted-pd); }
    .ted-btn--glass { background:rgba(255,255,255,.15); color:#fff; border-color:rgba(255,255,255,.2); }
    .ted-btn--glass:hover { background:rgba(255,255,255,.25); color:#fff; }
    .ted-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:.75rem; margin-top:1.5rem; }
    .ted-kpi { display:block; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:12px; padding:.9rem 1rem; color:#fff; text-decoration:none; transition:background .2s ease; }
    .ted-kpi:hover { background:rgba(255,255,255,.18); color:#fff; }
    .ted-kpi-label { font-size:.72rem; color:rgba(255,255,255,.7); text-transform:uppercase; letter-spacing:.05em; font-weight:600; }
    .ted-kpi-value { font-size:clamp(1.3rem,2.4vw,1.65rem); font-weight:700; line-height:1.2; margin-top:.2rem; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .ted-kpi-value small { font-size:.8rem; font-weight:600; color:rgba(255,255,255,.75); margin-left:.2rem; }
    .ted-kpi-rep { font-size:.76rem; color:rgba(255,255,255,.78); margin-top:.25rem; display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; }
    .ted-delta { display:inline-flex; align-items:center; gap:.2rem; padding:.05rem .45rem; border-radius:999px; font-weight:700; font-size:.72rem; background:rgba(255,255,255,.14); }
    .ted-delta--up { color:#bbf7d0; }
    .ted-delta--down { color:#fecaca; }

    .ted-grid { display:grid; grid-template-columns:minmax(0,1.05fr) minmax(0,1fr); gap:1.25rem; margin-bottom:1.25rem; }
    .ted-card { background:#fff; border:1px solid var(--ted-line); border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04),0 1px 2px rgba(15,23,42,.06); padding:1.25rem 1.35rem; min-width:0; }
    .ted-card-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:1rem; flex-wrap:wrap; }
    .ted-card-title { display:flex; align-items:center; gap:.65rem; }
    .ted-card-icon { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.9rem; flex-shrink:0; }
    .ted-card h2 { font-size:1rem; font-weight:700; color:var(--ted-text); margin:0; }
    .ted-card-sub { font-size:.78rem; color:var(--ted-muted); }
    .ted-link { font-size:.8rem; font-weight:600; color:var(--ted-p); text-decoration:none; }
    .ted-link:hover { text-decoration:underline; }

    .ted-queue { display:flex; flex-direction:column; gap:.6rem; }
    .ted-task { display:grid; grid-template-columns:auto minmax(0,1fr) auto; gap:.8rem; align-items:center; padding:.75rem .85rem; border:1px solid var(--ted-line); border-left:4px solid var(--ted-p); border-radius:12px; background:var(--ted-surface); }
    .ted-task--success { border-left-color:var(--ted-ok); }
    .ted-task--warning { border-left-color:var(--ted-warn); background:rgba(245,158,11,.05); }
    .ted-task--danger { border-left-color:var(--ted-ko); background:rgba(220,38,38,.04); }
    .ted-task-ico { width:34px; height:34px; border-radius:9px; background:rgba(4,83,203,.1); color:var(--ted-p); display:flex; align-items:center; justify-content:center; font-size:.85rem; }
    .ted-task-title { font-size:.9rem; font-weight:700; color:var(--ted-text); line-height:1.3; }
    .ted-task-detail { font-size:.78rem; color:var(--ted-muted); margin-top:.1rem; }
    .ted-task-btn { background:var(--ted-p); color:#fff; border-radius:9px; padding:.45rem .85rem; font-size:.8rem; font-weight:600; text-decoration:none; white-space:nowrap; }
    .ted-task-btn:hover { background:var(--ted-pd); color:#fff; }
    .ted-calme { display:flex; align-items:center; gap:.9rem; padding:1.1rem; border:1px dashed #bfd3f2; border-radius:12px; background:rgba(4,83,203,.03); }
    .ted-calme i { font-size:1.4rem; color:var(--ted-ok); }
    .ted-calme strong { display:block; color:var(--ted-text); font-size:.92rem; }
    .ted-calme span { font-size:.78rem; color:var(--ted-muted); }

    .ted-day { position:relative; display:flex; flex-direction:column; gap:.55rem; }
    .ted-slot { display:grid; grid-template-columns:64px 14px minmax(0,1fr); gap:.7rem; align-items:start; }
    .ted-slot-time { font-variant-numeric:tabular-nums; font-weight:700; color:var(--ted-text); font-size:.92rem; text-align:right; line-height:1.2; padding-top:.55rem; }
    .ted-slot-time small { display:block; font-weight:500; color:var(--ted-muted); font-size:.74rem; }
    .ted-rail { position:relative; align-self:stretch; display:flex; justify-content:center; }
    .ted-rail::before { content:''; position:absolute; top:0; bottom:-.6rem; width:2px; background:#dbe5f3; }
    .ted-slot:last-child .ted-rail::before { bottom:0; }
    .ted-dot { position:relative; margin-top:.75rem; width:12px; height:12px; border-radius:50%; background:#fff; border:3px solid #94a3b8; }
    .ted-dot--success { border-color:var(--ted-ok); }
    .ted-dot--warning { border-color:var(--ted-warn); }
    .ted-dot--danger { border-color:var(--ted-ko); }
    .ted-dot--primary { border-color:var(--ted-p); background:var(--ted-p); }
    .ted-slot-body { border:1px solid var(--ted-line); border-radius:12px; padding:.65rem .85rem; display:flex; justify-content:space-between; gap:.75rem; align-items:center; flex-wrap:wrap; }
    .ted-slot-name { font-weight:700; color:var(--ted-text); font-size:.9rem; }
    .ted-slot-meta { font-size:.78rem; color:var(--ted-muted); display:flex; gap:.75rem; flex-wrap:wrap; margin-top:.15rem; }
    .ted-chip { display:inline-flex; align-items:center; gap:.3rem; font-size:.72rem; font-weight:700; border-radius:999px; padding:.2rem .6rem; border:1px solid; white-space:nowrap; }
    .ted-chip--success { color:#047857; background:rgba(16,185,129,.08); border-color:rgba(16,185,129,.3); }
    .ted-chip--warning { color:#b45309; background:rgba(245,158,11,.08); border-color:rgba(245,158,11,.35); }
    .ted-chip--danger { color:#b91c1c; background:rgba(220,38,38,.06); border-color:rgba(220,38,38,.3); }
    .ted-chip--primary { color:var(--ted-p); background:rgba(4,83,203,.07); border-color:rgba(4,83,203,.25); }
    .ted-chip--muted { color:var(--ted-muted); background:var(--ted-surface); border-color:var(--ted-line); }
    .ted-slot-actions { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; }
    .ted-mini { font-size:.76rem; font-weight:600; color:var(--ted-p); text-decoration:none; border:1px solid #bfd3f2; border-radius:8px; padding:.3rem .6rem; background:#fff; white-space:nowrap; }
    .ted-mini:hover { background:#eef4fd; color:var(--ted-pd); }
    .ted-now { display:grid; grid-template-columns:64px 14px minmax(0,1fr); gap:.7rem; align-items:center; }
    .ted-now-time { text-align:right; font-size:.74rem; font-weight:700; color:var(--ted-p); font-variant-numeric:tabular-nums; }
    .ted-now-dot { width:14px; height:14px; border-radius:50%; background:var(--ted-p); box-shadow:0 0 0 4px rgba(4,83,203,.15); justify-self:center; }
    .ted-now-line { height:2px; background:linear-gradient(90deg,var(--ted-p),rgba(4,83,203,0)); }
    .ted-empty { text-align:center; padding:1.75rem 1rem; color:var(--ted-muted); font-size:.88rem; }
    .ted-empty i { display:block; font-size:1.6rem; color:#94a3b8; margin-bottom:.5rem; }

    .ted-grid--bas { grid-template-columns:minmax(0,1.4fr) minmax(0,1fr); }
    .ted-chart { width:100%; height:auto; display:block; }
    .ted-chart text { font-family:inherit; }
    .ted-mois { display:flex; flex-direction:column; gap:.9rem; }
    .ted-bar { display:flex; height:12px; border-radius:999px; overflow:hidden; background:var(--ted-surface); border:1px solid var(--ted-line); }
    .ted-bar span { display:block; height:100%; }
    .ted-legend { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; }
    .ted-legend a { display:block; text-decoration:none; border:1px solid var(--ted-line); border-radius:10px; padding:.55rem .65rem; }
    .ted-legend a:hover { border-color:#bfd3f2; }
    .ted-legend strong { display:block; font-size:1.15rem; color:var(--ted-text); font-variant-numeric:tabular-nums; }
    .ted-legend span { font-size:.74rem; color:var(--ted-muted); display:flex; align-items:center; gap:.35rem; }
    .ted-sw { width:8px; height:8px; border-radius:2px; display:inline-block; }
    .ted-next { display:flex; flex-direction:column; }
    .ted-next-row { display:grid; grid-template-columns:92px minmax(0,1fr); gap:.75rem; padding:.6rem 0; border-top:1px solid var(--ted-line); }
    .ted-next-row:first-child { border-top:0; padding-top:0; }
    .ted-next-when { font-size:.8rem; font-weight:700; color:var(--ted-p); font-variant-numeric:tabular-nums; }
    .ted-next-when small { display:block; color:var(--ted-muted); font-weight:500; }
    .ted-next-what { font-size:.86rem; color:var(--ted-text); font-weight:600; }
    .ted-next-what small { display:block; color:var(--ted-muted); font-weight:500; font-size:.76rem; }

    .ted-quick { display:grid; grid-template-columns:repeat(auto-fit,minmax(135px,1fr)); gap:.6rem; }
    .ted-quick a { display:flex; align-items:center; gap:.6rem; padding:.75rem .85rem; border:1px solid var(--ted-line); border-radius:12px; text-decoration:none; color:var(--ted-text); font-size:.85rem; font-weight:600; background:#fff; transition:border-color .2s ease, box-shadow .2s ease; }
    .ted-quick a:hover { border-color:#bfd3f2; box-shadow:0 4px 16px rgba(4,83,203,.08); color:var(--ted-p); }
    .ted-quick i { width:30px; height:30px; border-radius:8px; background:rgba(4,83,203,.08); color:var(--ted-p); display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0; }

    .ted-flash { border-radius:12px; padding:.75rem 1rem; margin-bottom:1rem; font-size:.88rem; }
    .ted-flash--ok { background:rgba(16,185,129,.08); border:1px solid rgba(16,185,129,.3); color:#065f46; }
    .ted-flash--ko { background:rgba(220,38,38,.06); border:1px solid rgba(220,38,38,.25); color:#991b1b; }

    @@media (max-width: 992px) {
        .ted-grid, .ted-grid--bas { grid-template-columns:1fr; }
    }
    @@media (max-width: 576px) {
        .ted-hero { padding:1.35rem 1.1rem 1.1rem; border-radius:14px; }
        .ted-hero h1 { font-size:1.2rem; }
        .ted-hero-icon { display:none; }
        .ted-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.5rem; }
        .ted-kpi { padding:.7rem .75rem; }
        .ted-card { padding:1rem; }
        .ted-task { grid-template-columns:minmax(0,1fr); }
        .ted-task-ico { display:none; }
        .ted-task-btn { text-align:center; padding:.6rem; }
        .ted-slot, .ted-now { grid-template-columns:48px 12px minmax(0,1fr); gap:.5rem; }
        .ted-legend { grid-template-columns:1fr 1fr 1fr; }
        .ted-chart text { font-size:17px; }
        .ted-quick { grid-template-columns:1fr 1fr; }
        .ted-quick a:last-child:nth-child(odd) { grid-column:1 / -1; }
    }
</style>
@endpush

@section('content')
@php
    $tedEmarges = $coursDuJour->filter->estEmarge()->count();
    $tedAVenir = $coursDuJour->where('etat', \App\Domain\EmploiTemps\CoursDuJour::A_VENIR)->count();
    $tedPresse = $coursDuJour->first->demandeUnEmargement();
    $tedNotes = $evaluationsANoter->take(5);
    $tedNotesPlus = $evaluationsANoter->count() > 5;
    $tedPonct = $bilan['ponctualite'];
    $tedPonctPrec = $bilan['precedent']['ponctualite'];
    $tedPonctDelta = ($tedPonct !== null && $tedPonctPrec !== null) ? $tedPonct - $tedPonctPrec : null;
    $tedHeuresDelta = round($bilan['heures'] - $bilan['precedent']['heures'], 1);
    $tedHistorique = route('esbtp.teacher.attendance.history');
    $tedEmargement = route('esbtp.teacher-attendance.index');
    $tedHeure = fn ($v) => rtrim(rtrim(number_format($v, 1, ',', ' '), '0'), ',');
    $tedMaintenantPose = false;
@endphp
<div class="ted container-fluid">
    @if(session('success'))<div class="ted-flash ted-flash--ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="ted-flash ted-flash--ko">{{ session('error') }}</div>@endif

    <div class="ted-hero">
        <div class="ted-hero-top">
            <div class="ted-hero-left">
                <div class="ted-hero-icon"><i class="fas fa-chalkboard-user"></i></div>
                <div>
                    <h1>Bonjour, {{ Auth::user()->name }}</h1>
                    <p>{{ $maintenant->locale('fr')->isoFormat('dddd D MMMM YYYY') }} · année {{ $anneeEnCours->name ?? 'non définie' }}</p>
                </div>
            </div>
            <div class="ted-hero-actions">
                @if($tedPresse)
                    <a href="{{ $tedEmargement }}#cours-{{ $tedPresse->seance->id }}" class="ted-btn ted-btn--white"><i class="fas fa-signature"></i>Émarger maintenant</a>
                @endif
                <a href="{{ route('teacher.timetable') }}" class="ted-btn ted-btn--glass"><i class="fas fa-calendar-week"></i>Mon emploi du temps</a>
            </div>
        </div>

        <div class="ted-kpis">
            <a href="{{ $tedEmargement }}" class="ted-kpi">
                <div class="ted-kpi-label">Cours aujourd’hui</div>
                <div class="ted-kpi-value">{{ $coursDuJour->count() }}</div>
                <div class="ted-kpi-rep">
                    @if($coursDuJour->isEmpty())
                        Aucun cours au programme
                    @else
                        {{ $tedEmarges }} émargé{{ $tedEmarges > 1 ? 's' : '' }} · {{ $tedAVenir }} à venir
                    @endif
                </div>
            </a>
            <a href="{{ $tedHistorique }}" class="ted-kpi">
                <div class="ted-kpi-label">À l’heure ce mois</div>
                <div class="ted-kpi-value">{{ $tedPonct === null ? '—' : $tedPonct.' %' }}</div>
                <div class="ted-kpi-rep">
                    @if($tedPonct === null)
                        Aucun émargement ce mois
                    @elseif($tedPonctDelta === null)
                        {{ $bilan['present'] }} sur {{ $bilan['total'] }} émargements
                    @else
                        <span class="ted-delta {{ $tedPonctDelta >= 0 ? 'ted-delta--up' : 'ted-delta--down' }}">{{ $tedPonctDelta >= 0 ? '▲ +' : '▼ ' }}{{ $tedPonctDelta }} pts</span>
                        vs {{ $bilan['libelle_precedent'] }}
                    @endif
                </div>
            </a>
            <a href="{{ $tedHistorique }}" class="ted-kpi">
                <div class="ted-kpi-label">Heures émargées ce mois</div>
                <div class="ted-kpi-value">{{ $tedHeure($bilan['heures']) }}<small>h</small></div>
                <div class="ted-kpi-rep">
                    @if($bilan['precedent']['total'] === 0 && $bilan['total'] === 0)
                        Rien d’émargé ces deux derniers mois
                    @else
                        <span class="ted-delta {{ $tedHeuresDelta >= 0 ? 'ted-delta--up' : 'ted-delta--down' }}">{{ $tedHeuresDelta >= 0 ? '▲ +' : '▼ ' }}{{ $tedHeure(abs($tedHeuresDelta)) }} h</span>
                        vs {{ $tedHeure($bilan['precedent']['heures']) }} h en {{ $bilan['libelle_precedent'] }}
                    @endif
                </div>
            </a>
            <a href="{{ $tedNotes->isEmpty() ? route('teacher.grades') : route('teacher.grades', ['evaluation' => $tedNotes->first()->id]) }}" class="ted-kpi">
                <div class="ted-kpi-label">Notes à saisir</div>
                <div class="ted-kpi-value">{{ $tedNotes->count() }}{{ $tedNotesPlus ? '+' : '' }}</div>
                <div class="ted-kpi-rep">
                    {{ $tedNotes->isEmpty() ? 'Toutes vos évaluations passées ont des notes' : 'évaluation(s) passée(s) sans note' }}
                </div>
            </a>
        </div>
    </div>

    <div class="ted-grid">
        <section class="ted-card" id="a-faire" aria-labelledby="ted-afaire-titre">
            <div class="ted-card-head">
                <div class="ted-card-title">
                    <div class="ted-card-icon"><i class="fas fa-bolt"></i></div>
                    <div>
                        <h2 id="ted-afaire-titre">À faire maintenant</h2>
                        <div class="ted-card-sub">Du plus pressé au moins pressé</div>
                    </div>
                </div>
            </div>
            @if(count($fileDeTravail) > 0)
                <div class="ted-queue">
                    @foreach($fileDeTravail as $tache)
                        <div class="ted-task ted-task--{{ $tache['ton'] }}">
                            <div class="ted-task-ico"><i class="fas {{ $tache['icone'] }}"></i></div>
                            <div>
                                <div class="ted-task-title">{{ $tache['titre'] }}</div>
                                <div class="ted-task-detail">{{ $tache['detail'] }}</div>
                            </div>
                            <a href="{{ $tache['url'] }}" class="ted-task-btn">{{ $tache['action'] }}</a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="ted-calme">
                    <i class="fas fa-circle-check"></i>
                    <div>
                        <strong>Rien en attente</strong>
                        <span>
                            @if($tedAVenir > 0)
                                Prochain émargement à {{ $coursDuJour->firstWhere('etat', \App\Domain\EmploiTemps\CoursDuJour::A_VENIR)->ouverture->format('H:i') }}.
                            @endif
                            Vérifié à {{ $maintenant->format('H:i') }}.
                        </span>
                    </div>
                </div>
            @endif
        </section>

        <section class="ted-card" aria-labelledby="ted-jour-titre">
            <div class="ted-card-head">
                <div class="ted-card-title">
                    <div class="ted-card-icon"><i class="fas fa-calendar-day"></i></div>
                    <div>
                        <h2 id="ted-jour-titre">Ma journée</h2>
                        <div class="ted-card-sub">{{ $coursDuJour->count() }} cours · heures d’émargement réglées par l’école</div>
                    </div>
                </div>
                <a href="{{ $tedEmargement }}" class="ted-link">Écran d’émargement</a>
            </div>
            @if($coursDuJour->isEmpty())
                <div class="ted-empty"><i class="fas fa-mug-hot"></i>Aucun cours au programme aujourd’hui.</div>
            @else
                <div class="ted-day">
                    @foreach($coursDuJour as $cours)
                        @if(! $tedMaintenantPose && $cours->debut->gt($maintenant))
                            @php $tedMaintenantPose = true; @endphp
                            <div class="ted-now" aria-hidden="true">
                                <div class="ted-now-time">{{ $maintenant->format('H:i') }}</div>
                                <div class="ted-now-dot"></div>
                                <div class="ted-now-line"></div>
                            </div>
                        @endif
                        <div class="ted-slot">
                            <div class="ted-slot-time">{{ $cours->debut->format('H:i') }}<small>{{ $cours->fin->format('H:i') }}</small></div>
                            <div class="ted-rail"><span class="ted-dot ted-dot--{{ $cours->ton() }}"></span></div>
                            <div class="ted-slot-body">
                                <div style="min-width:0">
                                    <div class="ted-slot-name">{{ $cours->matiere() }}</div>
                                    <div class="ted-slot-meta">
                                        @if($cours->classe())<span><i class="fas fa-users me-1"></i>{{ $cours->classe() }}</span>@endif
                                        @if($cours->salle())<span><i class="fas fa-door-open me-1"></i>{{ $cours->salle() }}</span>@endif
                                    </div>
                                </div>
                                <div class="ted-slot-actions">
                                    <span class="ted-chip ted-chip--{{ $cours->ton() }}">{{ $cours->libelle() }}</span>
                                    @if($cours->demandeUnEmargement())
                                        <a href="{{ $tedEmargement }}#cours-{{ $cours->seance->id }}" class="ted-mini">Émarger</a>
                                    @elseif($cours->estEmarge())
                                        <a href="{{ route('teacher.select-call-type', $cours->seance->id) }}" class="ted-mini">{{ $cours->appelAFaire() ? 'Faire l’appel' : 'Gérer la séance' }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="ted-grid ted-grid--bas">
        <section class="ted-card" aria-labelledby="ted-tendance-titre">
            @php
                $tedMax = max(1, collect($tendance)->max('heures'));
                $tedPas = $tedMax <= 4 ? 1 : ($tedMax <= 10 ? 2 : ($tedMax <= 20 ? 5 : 10));
                $tedHaut = (int) (ceil($tedMax / $tedPas) * $tedPas);
                $tedW = 560; $tedH = 190; $tedG = 34; $tedB = 26; $tedT = 18;
                $tedLargeur = ($tedW - $tedG) / max(1, count($tendance));
                $tedTotal8 = collect($tendance)->sum('heures');
            @endphp
            <div class="ted-card-head">
                <div class="ted-card-title">
                    <div class="ted-card-icon"><i class="fas fa-chart-column"></i></div>
                    <div>
                        <h2 id="ted-tendance-titre">Heures émargées</h2>
                        <div class="ted-card-sub">{{ count($tendance) }} dernières semaines · {{ $tedHeure($tedTotal8) }} h au total</div>
                    </div>
                </div>
                <a href="{{ $tedHistorique }}" class="ted-link">Mon historique</a>
            </div>
            @if($tedTotal8 <= 0)
                <div class="ted-empty"><i class="fas fa-chart-column"></i>Aucun cours émargé sur ces {{ count($tendance) }} semaines.</div>
            @else
                <svg class="ted-chart" viewBox="0 0 {{ $tedW }} {{ $tedH }}" role="img" aria-label="Heures émargées par semaine">
                    @for($v = 0; $v <= $tedHaut; $v += $tedPas)
                        @php $tedY = $tedH - $tedB - ($v / $tedHaut) * ($tedH - $tedB - $tedT); @endphp
                        <line x1="{{ $tedG }}" x2="{{ $tedW }}" y1="{{ $tedY }}" y2="{{ $tedY }}" stroke="#e2e8f0" stroke-width="1"/>
                        <text x="{{ $tedG - 8 }}" y="{{ $tedY + 4 }}" text-anchor="end" font-size="11" fill="#64748b">{{ $v }}</text>
                    @endfor
                    @foreach($tendance as $i => $semaine)
                        @php
                            $tedBarH = ($semaine['heures'] / $tedHaut) * ($tedH - $tedB - $tedT);
                            $tedX = $tedG + $i * $tedLargeur + $tedLargeur * 0.22;
                            $tedBw = $tedLargeur * 0.56;
                            $tedDerniere = $i === count($tendance) - 1;
                        @endphp
                        <g>
                            <title>Semaine du {{ $semaine['du'] }} : {{ $tedHeure($semaine['heures']) }} h</title>
                            <rect x="{{ $tedX }}" y="{{ $tedH - $tedB - $tedBarH }}" width="{{ $tedBw }}" height="{{ max($tedBarH, $semaine['heures'] > 0 ? 2 : 0) }}" rx="4" fill="{{ $tedDerniere ? '#0453cb' : '#9dbcec' }}"/>
                            @if($semaine['heures'] > 0)
                                <text x="{{ $tedX + $tedBw / 2 }}" y="{{ $tedH - $tedB - $tedBarH - 5 }}" text-anchor="middle" font-size="11" font-weight="700" fill="#1e293b">{{ $tedHeure($semaine['heures']) }}</text>
                            @endif
                            <text x="{{ $tedX + $tedBw / 2 }}" y="{{ $tedH - 8 }}" text-anchor="middle" font-size="10.5" fill="{{ $tedDerniere ? '#0453cb' : '#64748b' }}" font-weight="{{ $tedDerniere ? '700' : '400' }}">{{ $tedDerniere ? 'Cette sem.' : $semaine['libelle'] }}</text>
                        </g>
                    @endforeach
                </svg>
            @endif
        </section>

        <section class="ted-card" aria-labelledby="ted-mois-titre">
            <div class="ted-card-head">
                <div class="ted-card-title">
                    <div class="ted-card-icon"><i class="fas fa-user-check"></i></div>
                    <div>
                        <h2 id="ted-mois-titre">Mes émargements — {{ $maintenant->locale('fr')->isoFormat('MMMM') }}</h2>
                        <div class="ted-card-sub">{{ $bilan['total'] }} cours émargé{{ $bilan['total'] > 1 ? 's' : '' }} · {{ $bilan['precedent']['total'] }} en {{ $bilan['libelle_precedent'] }}</div>
                    </div>
                </div>
            </div>
            <div class="ted-mois">
                @if($bilan['total'] > 0)
                    <div class="ted-bar" role="img" aria-label="{{ $bilan['present'] }} à l’heure, {{ $bilan['retard'] }} en retard, {{ $bilan['absent'] }} absences">
                        <span style="width:{{ $bilan['present'] * 100 / $bilan['total'] }}%;background:#10b981"></span>
                        <span style="width:{{ $bilan['retard'] * 100 / $bilan['total'] }}%;background:#f59e0b"></span>
                        <span style="width:{{ $bilan['absent'] * 100 / $bilan['total'] }}%;background:#dc2626"></span>
                    </div>
                @endif
                <div class="ted-legend">
                    <a href="{{ $tedHistorique }}"><strong>{{ $bilan['present'] }}</strong><span><i class="ted-sw" style="background:#10b981"></i>À l’heure</span></a>
                    <a href="{{ $tedHistorique }}"><strong>{{ $bilan['retard'] }}</strong><span><i class="ted-sw" style="background:#f59e0b"></i>En retard</span></a>
                    <a href="{{ $tedHistorique }}"><strong>{{ $bilan['absent'] }}</strong><span><i class="ted-sw" style="background:#dc2626"></i>Absences</span></a>
                </div>
                <div>
                    <div class="ted-card-sub" style="font-weight:700;color:#1e293b;margin-bottom:.4rem">Prochains cours</div>
                    @if($prochainsCours->isEmpty())
                        <div class="ted-card-sub">Aucun autre cours dans votre emploi du temps.</div>
                    @else
                        <div class="ted-next">
                            @foreach($prochainsCours as $prochain)
                                <div class="ted-next-row">
                                    <div class="ted-next-when">{{ $prochain['jour'] }}<small>{{ $prochain['debut'] }} – {{ $prochain['fin'] }}</small></div>
                                    <div class="ted-next-what">{{ $prochain['matiere'] }}<small>{{ collect([$prochain['classe'], $prochain['salle']])->filter()->implode(' · ') }}</small></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    <section class="ted-card" aria-labelledby="ted-acces-titre" style="margin-bottom:1.25rem">
        <div class="ted-card-head">
            <div class="ted-card-title">
                <div class="ted-card-icon"><i class="fas fa-compass"></i></div>
                <h2 id="ted-acces-titre">Accès rapides</h2>
            </div>
        </div>
        <div class="ted-quick">
            <a href="{{ $tedEmargement }}"><i class="fas fa-signature"></i>Émargement</a>
            <a href="{{ route('teacher.timetable') }}"><i class="fas fa-calendar-alt"></i>Emploi du temps</a>
            <a href="{{ route('teacher.grades') }}"><i class="fas fa-pen-to-square"></i>Saisir des notes</a>
            <a href="{{ $tedHistorique }}"><i class="fas fa-clock-rotate-left"></i>Mon historique</a>
            <a href="{{ route('teacher.availability') }}"><i class="fas fa-calendar-check"></i>Mes disponibilités</a>
            <a href="{{ route('esbtp.annonces.index') }}"><i class="fas fa-bullhorn"></i>Annonces</a>
            <a href="{{ route('teacher.profile') }}"><i class="fas fa-user-circle"></i>Mon profil</a>
        </div>
    </section>
</div>
@endsection
