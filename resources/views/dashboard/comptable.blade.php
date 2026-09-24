@extends('layouts.app')

@section('title', 'Tableau de bord Comptable')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ═══════════ Accueil comptable — namespace cb- — rule premium-dashboard ═══════════ */
    .cb-wrap { padding: 1.5rem; max-width: 1400px; margin: 0 auto; }
    .cb-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 1.75rem 2rem 1.5rem; color: #fff; margin-bottom: 1.25rem; }
    .cb-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .cb-hero-left { display: flex; align-items: center; gap: 1rem; }
    .cb-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; }
    .cb-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .cb-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: .15rem 0 0; }
    .cb-hero-meta { display: flex; flex-direction: column; align-items: flex-end; gap: .45rem; }
    .cb-chip { display: inline-flex; align-items: center; gap: .35rem; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2); border-radius: 999px; padding: .28rem .7rem; font-size: .76rem; font-weight: 600; color: #fff; white-space: nowrap; }
    .cb-date { font-size: .8rem; color: rgba(255,255,255,.7); }
    .cb-hero-btns { display: flex; gap: .5rem; flex-wrap: wrap; justify-content: flex-end; }
    .cb-btn { display: inline-flex; align-items: center; gap: .4rem; border-radius: 10px; padding: .45rem .9rem; font-size: .8rem; font-weight: 600; text-decoration: none; white-space: nowrap; transition: background .2s ease; }
    .cb-btn--white { background: #fff; color: #0453cb; }
    .cb-btn--white:hover { background: #eef4ff; color: #033a8e; }
    .cb-btn--glass { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.22); }
    .cb-btn--glass:hover { background: rgba(255,255,255,.24); color: #fff; }
    .cb-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .75rem; margin-top: 1.4rem; }
    .cb-kpi { display: block; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.16); border-radius: 12px; padding: .9rem 1rem; color: #fff; text-decoration: none; transition: background .2s ease, border-color .2s ease; min-width: 0; }
    a.cb-kpi[href]:hover { background: rgba(255,255,255,.17); border-color: rgba(255,255,255,.3); color: #fff; }
    .cb-kpi-l { font-size: .72rem; color: rgba(255,255,255,.72); text-transform: uppercase; letter-spacing: .4px; font-weight: 600; display: flex; align-items: center; gap: .4rem; }
    .cb-kpi-v { font-size: clamp(1.1rem, 1.55vw, 1.45rem); font-weight: 700; white-space: nowrap; margin-top: .3rem; overflow: hidden; text-overflow: ellipsis; }
    .cb-kpi-v small { font-size: .7rem; font-weight: 600; opacity: .7; margin-left: .2rem; }
    .cb-kpi-r { font-size: .74rem; margin-top: .25rem; color: rgba(255,255,255,.78); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cb-kpi-bar { height: 5px; border-radius: 99px; background: rgba(255,255,255,.2); margin-top: .45rem; overflow: hidden; }
    .cb-kpi-bar span { display: block; height: 100%; background: #fff; border-radius: 99px; }
    .cb-up { color: #a7f3d0; font-weight: 700; } .cb-down { color: #fecaca; font-weight: 700; }

    .cb-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr); gap: 1.25rem; margin-bottom: 1.25rem; }
    .cb-grid--2 { grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); }
    .cb-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); min-width: 0; }
    .cb-card-hd { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #eef2f7; }
    .cb-card-hd-l { display: flex; align-items: center; gap: .65rem; min-width: 0; }
    .cb-card-ic { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }
    .cb-card-t { font-weight: 700; color: #1e293b; font-size: .95rem; }
    .cb-card-s { font-size: .76rem; color: #64748b; }
    .cb-card-lnk { font-size: .8rem; font-weight: 600; color: #0453cb; text-decoration: none; white-space: nowrap; }
    .cb-card-bd { padding: 1rem 1.25rem; }
    .cb-stack > .cb-card + .cb-card { margin-top: 1.25rem; }

    .cb-todo { display: flex; align-items: center; gap: .8rem; padding: .75rem .85rem; border: 1px solid #e2e8f0; border-radius: 11px; text-decoration: none; margin-bottom: .6rem; transition: border-color .2s ease, background .2s ease; }
    .cb-todo:hover { border-color: #b8cdee; background: #f8fbff; }
    .cb-todo-ic { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: rgba(4,83,203,.08); color: #0453cb; }
    .cb-todo--warn .cb-todo-ic { background: rgba(245,158,11,.12); color: #b45309; }
    .cb-todo--bad .cb-todo-ic { background: rgba(220,38,38,.1); color: #b91c1c; }
    .cb-todo-t { font-weight: 700; color: #1e293b; font-size: .86rem; }
    .cb-todo-d { font-size: .76rem; color: #64748b; }
    .cb-todo-go { margin-left: auto; color: #94a3b8; }
    .cb-ok { display: flex; align-items: center; gap: .75rem; padding: 1rem; border-radius: 11px; background: rgba(16,185,129,.07); color: #065f46; font-size: .86rem; }
    .cb-ok i { font-size: 1.2rem; color: #10b981; }

    .cb-chart { display: grid; gap: .6rem; align-items: end; height: 190px; padding-top: .5rem; }
    .cb-bar-col { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: .35rem; min-width: 0; }
    .cb-bar { width: 100%; max-width: 56px; border-radius: 8px 8px 3px 3px; background: linear-gradient(180deg, #3b7ddb, #0453cb); min-height: 3px; opacity: .8; }
    .cb-bar--now { opacity: 1; box-shadow: 0 6px 16px rgba(4,83,203,.25); }
    .cb-bar-v { font-size: .68rem; color: #475569; font-weight: 700; white-space: nowrap; }
    .cb-bar-l { font-size: .72rem; color: #64748b; white-space: nowrap; }
    .cb-chart-foot { display: flex; justify-content: space-between; flex-wrap: wrap; gap: .5rem; margin-top: .9rem; padding-top: .75rem; border-top: 1px dashed #e2e8f0; font-size: .8rem; color: #64748b; }
    .cb-chart-foot strong { color: #1e293b; }

    .cb-table { width: 100%; border-collapse: collapse; }
    .cb-table th { font-size: .7rem; text-transform: uppercase; letter-spacing: .4px; color: #64748b; font-weight: 700; padding: .6rem 1rem; background: #f8fafc; border-bottom: 1px solid #eef2f7; text-align: left; white-space: nowrap; }
    .cb-table td { padding: .65rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: .84rem; color: #1e293b; vertical-align: middle; }
    .cb-table tr:last-child td { border-bottom: 0; }
    .cb-who { display: flex; align-items: center; gap: .6rem; min-width: 0; }
    .cb-av { width: 32px; height: 32px; border-radius: 50%; background: rgba(4,83,203,.1); color: #0453cb; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .7rem; flex-shrink: 0; }
    .cb-who-n { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
    .cb-who-s { font-size: .73rem; color: #64748b; }
    .cb-amount { font-weight: 700; white-space: nowrap; text-align: right; }
    .cb-pill { display: inline-flex; align-items: center; font-size: .7rem; font-weight: 700; padding: .18rem .55rem; border-radius: 99px; white-space: nowrap; }
    .cb-pill--ok { background: rgba(16,185,129,.1); color: #047857; }
    .cb-pill--wait { background: rgba(245,158,11,.12); color: #b45309; }
    .cb-pill--bad { background: rgba(220,38,38,.1); color: #b91c1c; }
    .cb-pill--info { background: rgba(4,83,203,.08); color: #0453cb; }

    .cb-rank { display: flex; align-items: center; gap: .7rem; padding: .6rem 0; border-bottom: 1px solid #f1f5f9; text-decoration: none; }
    .cb-rank:last-child { border-bottom: 0; }
    .cb-rank-n { width: 24px; height: 24px; border-radius: 50%; background: rgba(4,83,203,.08); color: #0453cb; font-size: .72rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .cb-rank-mid { min-width: 0; flex: 1; }
    .cb-rank-t { font-weight: 700; color: #1e293b; font-size: .84rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cb-rank-bar { height: 4px; border-radius: 99px; background: #eef2f7; margin-top: .3rem; overflow: hidden; }
    .cb-rank-bar span { display: block; height: 100%; background: #0453cb; }
    .cb-rank-v { font-weight: 700; color: #b91c1c; font-size: .84rem; white-space: nowrap; text-align: right; }
    .cb-rank-v small { display: block; color: #94a3b8; font-weight: 600; font-size: .7rem; }

    .cb-mode { margin-bottom: .8rem; }
    .cb-mode-top { display: flex; justify-content: space-between; font-size: .84rem; color: #1e293b; gap: .5rem; }
    .cb-mode-top span:last-child { white-space: nowrap; font-weight: 700; }
    .cb-mode-bar { height: 6px; border-radius: 99px; background: #eef2f7; margin-top: .35rem; overflow: hidden; }
    .cb-mode-bar span { display: block; height: 100%; background: #0453cb; border-radius: 99px; }

    .cb-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .6rem; }
    .cb-link { display: flex; align-items: center; gap: .6rem; padding: .7rem .8rem; border: 1px solid #e2e8f0; border-radius: 11px; text-decoration: none; transition: border-color .2s ease, background .2s ease; }
    .cb-link:hover { border-color: #b8cdee; background: #f8fbff; }
    .cb-link i { width: 30px; height: 30px; border-radius: 8px; background: rgba(4,83,203,.08); color: #0453cb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .8rem; }
    .cb-link-t { font-weight: 700; color: #1e293b; font-size: .83rem; }
    .cb-link-d { font-size: .72rem; color: #64748b; }
    .cb-empty { text-align: center; padding: 1.75rem 1rem; color: #64748b; font-size: .86rem; }
    .cb-warn-strip { display: flex; gap: .6rem; align-items: center; background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.3); color: #92400e; border-radius: 11px; padding: .7rem 1rem; margin-bottom: 1rem; font-size: .85rem; }

    @@media (max-width: 1100px) { .cb-grid, .cb-grid--2 { grid-template-columns: 1fr; } }
    @@media (max-width: 768px) { .cb-wrap { padding: 1rem; } .cb-hero { padding: 1.25rem; } .cb-hero-meta { align-items: flex-start; } .cb-hero-btns { justify-content: flex-start; } }
</style>
@endpush

@section('content')
@php
    $cbFmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $cbCourt = function ($n) use ($cbFmt) {
        $n = (float) $n;
        if ($n >= 1000000) return number_format($n / 1000000, 1, ',', ' ').' M';
        if ($n >= 1000) return $cbFmt(round($n / 1000)).' k';
        return $cbFmt($n);
    };
    $cbIndisponible = $indisponible ?? false;
    $cbPeutVoir = auth()->user()?->canany(['paiements.view', 'paiements.view_own']) ?? false;
    $cbPeutCompta = auth()->user()?->canany(['comptabilite.access', 'comptabilite.manage']) ?? false;
    $cbAujourdhui = now()->toDateString();
    $cbDebutMois = now()->startOfMonth()->toDateString();

    $cbVarJour = $encaisseAujourdhui !== null ? \App\Domain\Comptabilite\TableauDeBord\IndicateursDeCaisse::variation((float) $encaisseAujourdhui, (float) $encaisseHier) : null;
    $cbVarMois = $encaisseMoisPrecedent !== null ? \App\Domain\Comptabilite\TableauDeBord\IndicateursDeCaisse::variation((float) $encaisseMois, (float) $encaisseMoisPrecedent) : null;

    $cbSerie = $serieMois ?? [];
    $cbMax = max(1, collect($cbSerie)->max('total') ?? 0);
    $cbTotalSerie = collect($cbSerie)->sum('total');

    $cbModesTotal = max(1, (float) collect($paiementsParMode)->sum('total'));

    // File de travail, du plus urgent au moins urgent.
    $cbTodo = [];
    if ($paiementsEnAttenteCount > 0 && $cbPeutVoir) {
        $cbTodo[] = ['ton' => 'warn', 'ic' => 'fa-hourglass-half',
            't' => $paiementsEnAttenteCount.' paiement'.($paiementsEnAttenteCount > 1 ? 's' : '').' à valider',
            'd' => $cbFmt($totalEnAttente).' FCFA pas encore comptabilisés.',
            'href' => route('esbtp.paiements.index', ['status' => 'en_attente'])];
    }
    if ($montantRestant > 0 && $cbPeutCompta) {
        $cbTodo[] = ['ton' => 'bad', 'ic' => 'fa-bell',
            't' => $cbFmt($montantRestant).' FCFA restent à percevoir',
            'd' => 'Relancer les familles en retard, par ordre de priorité.',
            'href' => route('esbtp.comptabilite.relances.index')];
    }
    if (auth()->user()?->can('comptabilite.reconciliation.view')) {
        $cbJours = $derniereReconciliation ? (int) \Carbon\Carbon::parse($derniereReconciliation)->diffInDays(now()) : null;
        if ($cbJours === null || $cbJours > 7) {
            $cbTodo[] = ['ton' => 'info', 'ic' => 'fa-scale-balanced',
                't' => $cbJours === null ? 'Aucune réconciliation de caisse clôturée' : 'Dernière réconciliation il y a '.$cbJours.' jours',
                'd' => 'Comparer la caisse physique et les versements enregistrés.',
                'href' => route('esbtp.comptabilite.reconciliation.index')];
        }
    }
@endphp

<div class="cb-wrap">

    {{-- ─── HERO ─── --}}
    <div class="cb-hero">
        <div class="cb-hero-top">
            <div class="cb-hero-left">
                <div class="cb-hero-icon"><i class="fas fa-calculator"></i></div>
                <div>
                    <h1>Tableau de bord comptable</h1>
                    <p>Bonjour {{ $user->name }} — la situation financière de l'école, à jour.</p>
                </div>
            </div>
            <div class="cb-hero-meta">
                <div class="cb-hero-btns">
                    @can('paiements.create')
                        <a href="{{ route('esbtp.paiements.create') }}" class="cb-btn cb-btn--white"><i class="fas fa-plus"></i> Encaisser</a>
                    @endcan
                    @can('comptabilite.access')
                    @can('comptabilite.journal.view')
                        <a href="{{ route('esbtp.comptabilite.journal-caisse.index') }}" class="cb-btn cb-btn--glass"><i class="fas fa-book"></i> Journal de caisse</a>
                    @endcan
                    @endcan
                    @can('comptabilite.dashboard.view')
                        <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="cb-btn cb-btn--glass"><i class="fas fa-chart-line"></i> Analyse détaillée</a>
                    @endcan
                </div>
                <span class="cb-date"><span class="cb-chip"><i class="fas fa-calendar"></i> {{ $anneeEnCours->name ?? 'Année non définie' }}</span> &nbsp;{{ ucfirst(\Carbon\Carbon::now()->isoFormat('dddd D MMMM YYYY')) }}</span>
            </div>
        </div>

        <div class="cb-kpis">
            <a class="cb-kpi" @if($cbPeutVoir) href="{{ route('esbtp.paiements.index', ['date_debut' => $cbAujourdhui, 'date_fin' => $cbAujourdhui]) }}" @endif>
                <div class="cb-kpi-l"><i class="fas fa-coins"></i> Encaissé aujourd'hui</div>
                <div class="cb-kpi-v">{{ $encaisseAujourdhui === null ? '—' : $cbFmt($encaisseAujourdhui) }}<small>FCFA</small></div>
                <div class="cb-kpi-r">
                    @if($encaisseAujourdhui === null) Indisponible
                    @elseif($cbVarJour === null) Hier : {{ $cbFmt($encaisseHier) }} FCFA
                    @else <span class="{{ $cbVarJour >= 0 ? 'cb-up' : 'cb-down' }}">{{ $cbVarJour >= 0 ? '▲ +' : '▼ ' }}{{ number_format($cbVarJour, 1, ',', ' ') }} %</span> vs hier
                    @endif
                </div>
            </a>
            <a class="cb-kpi" @if($cbPeutVoir) href="{{ route('esbtp.paiements.index', ['date_debut' => $cbDebutMois, 'date_fin' => $cbAujourdhui]) }}" @endif>
                <div class="cb-kpi-l"><i class="fas fa-calendar-check"></i> Encaissé ce mois</div>
                <div class="cb-kpi-v">{{ $cbFmt($encaisseMois) }}<small>FCFA</small></div>
                <div class="cb-kpi-r">
                    @if($cbVarMois === null) {{ ucfirst(now()->isoFormat('MMMM')) }} en cours
                    @else <span class="{{ $cbVarMois >= 0 ? 'cb-up' : 'cb-down' }}">{{ $cbVarMois >= 0 ? '▲ +' : '▼ ' }}{{ number_format($cbVarMois, 1, ',', ' ') }} %</span> vs mois dernier à date
                    @endif
                </div>
            </a>
            <a class="cb-kpi" @if($cbPeutVoir) href="{{ route('esbtp.paiements.index', ['status' => 'validé']) }}" @endif>
                <div class="cb-kpi-l"><i class="fas fa-circle-check"></i> Encaissé sur l'année</div>
                <div class="cb-kpi-v">{{ $cbIndisponible ? '—' : $cbFmt($totalEncaisse) }}<small>FCFA</small></div>
                <div class="cb-kpi-r" title="{{ $cbFmt($totalFraisDus) }} FCFA dus · {{ $validatedInscriptionsCount }} inscrits">{{ number_format((float) $tauxRecouvrement, 1, ',', ' ') }} % des {{ $cbCourt($totalFraisDus) }} dus</div>
                <div class="cb-kpi-bar"><span style="width: {{ min(100, (float) $tauxRecouvrement) }}%"></span></div>
            </a>
            <a class="cb-kpi" @if($cbPeutCompta) href="{{ route('esbtp.comptabilite.relances.index') }}" @endif>
                <div class="cb-kpi-l"><i class="fas fa-hourglass-half"></i> Reste à percevoir</div>
                <div class="cb-kpi-v">{{ $cbIndisponible ? '—' : $cbFmt($montantRestant) }}<small>FCFA</small></div>
                <div class="cb-kpi-r">{{ $validatedInscriptionsCount }} inscrits cette année</div>
            </a>
        </div>
    </div>

    @if($cbIndisponible)
        <div class="cb-warn-strip"><i class="fas fa-triangle-exclamation"></i> Certains totaux n'ont pas pu être calculés et sont affichés « — ». L'incident est signalé au support.</div>
    @endif

    {{-- ─── FILE DE TRAVAIL + TENDANCE ─── --}}
    <div class="cb-grid">
        <div class="cb-card">
            <div class="cb-card-hd">
                <div class="cb-card-hd-l">
                    <div class="cb-card-ic"><i class="fas fa-list-check"></i></div>
                    <div><div class="cb-card-t">À traiter</div><div class="cb-card-s">Ce qui attend la comptabilité</div></div>
                </div>
            </div>
            <div class="cb-card-bd">
                @forelse($cbTodo as $t)
                    <a href="{{ $t['href'] }}" class="cb-todo cb-todo--{{ $t['ton'] }}">
                        <div class="cb-todo-ic"><i class="fas {{ $t['ic'] }}"></i></div>
                        <div><div class="cb-todo-t">{{ $t['t'] }}</div><div class="cb-todo-d">{{ $t['d'] }}</div></div>
                        <i class="fas fa-chevron-right cb-todo-go"></i>
                    </a>
                @empty
                    <div class="cb-ok"><i class="fas fa-circle-check"></i><div><strong>Rien à traiter.</strong> Contrôlé à {{ now()->format('H:i') }}.</div></div>
                @endforelse
            </div>
        </div>

        <div class="cb-card">
            <div class="cb-card-hd">
                <div class="cb-card-hd-l">
                    <div class="cb-card-ic"><i class="fas fa-chart-column"></i></div>
                    <div><div class="cb-card-t">Encaissements, six derniers mois</div><div class="cb-card-s">Montant net par mois (remboursements déduits)</div></div>
                </div>
                @can('comptabilite.dashboard.view')
                    <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="cb-card-lnk">Analyse <i class="fas fa-arrow-right ms-1"></i></a>
                @endcan
            </div>
            <div class="cb-card-bd">
                @if(count($cbSerie) === 0)
                    <div class="cb-empty">Tendance indisponible.</div>
                @else
                    <div class="cb-chart" style="grid-template-columns: repeat({{ count($cbSerie) }}, 1fr);" role="img" aria-label="Encaissements des six derniers mois">
                        @foreach($cbSerie as $point)
                            @php $h = $point['total'] > 0 ? max(4, round($point['total'] / $cbMax * 145)) : 3; @endphp
                            <div class="cb-bar-col" title="{{ $point['libelle'] }} : {{ $cbFmt($point['total']) }} FCFA">
                                <span class="cb-bar-v">{{ $point['total'] > 0 ? $cbCourt($point['total']) : '' }}</span>
                                <div class="cb-bar {{ $loop->last ? 'cb-bar--now' : '' }}" style="height: {{ $h }}px;"></div>
                                <span class="cb-bar-l">{{ $point['libelle'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="cb-chart-foot">
                        <span>Sur six mois : <strong>{{ $cbFmt($cbTotalSerie) }} FCFA</strong></span>
                        <span>Moyenne : <strong>{{ $cbFmt($cbTotalSerie / max(1, count($cbSerie))) }} FCFA / mois</strong></span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ─── DERNIERS PAIEMENTS (avec actions) + IMPAYÉS / MODES ─── --}}
    <div class="cb-grid cb-grid--2">
        <div class="cb-card">
            <div class="cb-card-hd">
                <div class="cb-card-hd-l">
                    <div class="cb-card-ic"><i class="fas fa-clock-rotate-left"></i></div>
                    <div><div class="cb-card-t">Derniers paiements</div><div class="cb-card-s">Voir, reçu, annuler le versement ou supprimer, depuis l'accueil</div></div>
                </div>
                @if($cbPeutVoir)
                    <a href="{{ route('esbtp.paiements.index') }}" class="cb-card-lnk">Tout voir <i class="fas fa-arrow-right ms-1"></i></a>
                @endif
            </div>
            @if($recentPaiements->isEmpty())
                <div class="cb-empty">Aucun paiement sur l'année en cours.</div>
            @else
                <div class="table-responsive">
                    <table class="cb-table">
                        <thead><tr><th>Étudiant</th><th>Reçu</th><th>Statut</th><th style="text-align:right;">Montant</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>
                        @foreach($recentPaiements as $paiement)
                            @php
                                $et = $paiement->etudiant;
                                $nom = $et ? trim(($et->nom ?? '').' '.($et->prenoms ?? '')) : 'Étudiant inconnu';
                                $ini = $et ? mb_strtoupper(mb_substr($et->nom ?? '', 0, 1, 'UTF-8').mb_substr($et->prenoms ?? '', 0, 1, 'UTF-8'), 'UTF-8') : '?';
                                [$pLib, $pTon] = $paiement->isAvoir()
                                    ? ['Avoir', 'info']
                                    : match ($paiement->status) {
                                        'validé' => ['Validé', 'ok'],
                                        'en_attente' => ['À valider', 'wait'],
                                        'rejeté' => ['Rejeté', 'bad'],
                                        default => [ucfirst((string) $paiement->status), 'info'],
                                    };
                            @endphp
                            <tr>
                                <td>
                                    <div class="cb-who">
                                        <div class="cb-av">{{ $ini }}</div>
                                        <div style="min-width:0;">
                                            <div class="cb-who-n">{{ $nom }}</div>
                                            <div class="cb-who-s">{{ $paiement->inscription?->classe?->name ?? '—' }} · {{ $paiement->mode_paiement ? ucfirst($paiement->mode_paiement) : '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="white-space:nowrap;">{{ $paiement->numero_recu ?? '—' }}<div class="cb-who-s">{{ optional($paiement->date_paiement)->format('d/m/Y') }}</div></td>
                                <td><span class="cb-pill cb-pill--{{ $pTon }}">{{ $pLib }}</span></td>
                                <td class="cb-amount">{{ $paiement->isAvoir() ? '−' : '' }}{{ $cbFmt($paiement->montant) }} F</td>
                                <td style="text-align:right;">
                                    @include('esbtp.paiements.partials.actions-versement', ['paiement' => $paiement, 'retour' => '/dashboard', 'avSuffixe' => '-cb'])
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="cb-stack">
            @if($cbPeutCompta)
            <div class="cb-card">
                <div class="cb-card-hd">
                    <div class="cb-card-hd-l">
                        <div class="cb-card-ic"><i class="fas fa-user-clock"></i></div>
                        <div><div class="cb-card-t">Plus gros restes à payer</div><div class="cb-card-s">Les cinq soldes les plus élevés</div></div>
                    </div>
                    <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="cb-card-lnk">Relancer <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <div class="cb-card-bd" style="padding-top:.4rem;padding-bottom:.4rem;">
                    @forelse($topImpayes as $impaye)
                        @php $pct = $impaye->total_du > 0 ? round($impaye->total_paye / $impaye->total_du * 100) : 0; @endphp
                        <a class="cb-rank" @can('students.view') href="{{ route('esbtp.etudiants.show', $impaye->etudiant_id) }}" @endcan>
                            <span class="cb-rank-n">{{ $loop->iteration }}</span>
                            <div class="cb-rank-mid">
                                <div class="cb-rank-t">{{ trim($impaye->nom.' '.$impaye->prenoms) }}</div>
                                <div class="cb-who-s">{{ $impaye->matricule }} · {{ $pct }} % payé</div>
                                <div class="cb-rank-bar"><span style="width: {{ $pct }}%"></span></div>
                            </div>
                            <div class="cb-rank-v">{{ $cbFmt($impaye->solde_restant) }}<small>FCFA</small></div>
                        </a>
                    @empty
                        <div class="cb-ok" style="margin:.6rem 0;"><i class="fas fa-circle-check"></i><div>Aucun impayé.</div></div>
                    @endforelse
                </div>
            </div>
            @endif

            <div class="cb-card">
                <div class="cb-card-hd">
                    <div class="cb-card-hd-l">
                        <div class="cb-card-ic"><i class="fas fa-wallet"></i></div>
                        <div><div class="cb-card-t">Moyens de paiement</div><div class="cb-card-s">{{ ucfirst(now()->isoFormat('MMMM YYYY')) }}</div></div>
                    </div>
                </div>
                <div class="cb-card-bd">
                    @forelse($paiementsParMode as $mode)
                        @php $pctMode = round((float) $mode->total / $cbModesTotal * 100); @endphp
                        <div class="cb-mode">
                            <div class="cb-mode-top"><span>{{ ucfirst($mode->mode_paiement ?: 'Non précisé') }} · {{ $mode->count }}</span><span>{{ $cbFmt($mode->total) }} F · {{ $pctMode }} %</span></div>
                            <div class="cb-mode-bar"><span style="width: {{ $pctMode }}%"></span></div>
                        </div>
                    @empty
                        <div class="cb-empty" style="padding:.5rem;">Aucun encaissement ce mois-ci.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ─── ACCÈS RAPIDES : uniquement ce que le rôle peut ouvrir ─── --}}
    <div class="cb-card">
        <div class="cb-card-hd">
            <div class="cb-card-hd-l">
                <div class="cb-card-ic"><i class="fas fa-bolt"></i></div>
                <div class="cb-card-t">Accès rapides</div>
            </div>
        </div>
        <div class="cb-card-bd">
            <div class="cb-links">
                @if($cbPeutVoir)
                    <a href="{{ route('esbtp.paiements.index') }}" class="cb-link"><i class="fas fa-money-bill-wave"></i><div><div class="cb-link-t">Paiements</div><div class="cb-link-d">Valider, annuler, suivre</div></div></a>
                    <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="cb-link"><i class="fas fa-chart-pie"></i><div><div class="cb-link-t">Suivi par frais</div><div class="cb-link-d">Qui a payé quoi</div></div></a>
                @endif
                @can('frais.view')
                    <a href="{{ route('esbtp.frais.index') }}" class="cb-link"><i class="fas fa-tags"></i><div><div class="cb-link-t">Frais</div><div class="cb-link-d">Catégories et tarifs</div></div></a>
                @endcan
                @if($cbPeutCompta)
                    <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="cb-link"><i class="fas fa-bell"></i><div><div class="cb-link-t">Relances</div><div class="cb-link-d">Familles en retard</div></div></a>
                @endif
                @can('comptabilite.reconciliation.view')
                    <a href="{{ route('esbtp.comptabilite.reconciliation.index') }}" class="cb-link"><i class="fas fa-scale-balanced"></i><div><div class="cb-link-t">Réconciliation</div><div class="cb-link-d">Caisse et système</div></div></a>
                @endcan
                @can('comptabilite.dashboard.view')
                    <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="cb-link"><i class="fas fa-chart-line"></i><div><div class="cb-link-t">Analyse détaillée</div><div class="cb-link-d">Retards, catégories</div></div></a>
                @endcan
            </div>
        </div>
    </div>

</div>
@endsection
