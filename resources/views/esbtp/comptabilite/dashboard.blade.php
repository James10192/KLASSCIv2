@extends('layouts.app')

@section('title', 'Analyse financière')

@push('styles')
<style>
/* ═══════════ Analyse financière — namespace af- — maquette B (cockpit bleu)
   Rule premium-dashboard. Même calcul que l'accueil comptable
   (BuildDashboardDataAction) ; les filtres rechargent en AJAX. ═══════════ */
.af-wrap { padding: 1.5rem; max-width: 1360px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.1rem; }
.af-num { font-variant-numeric: tabular-nums; }
.af-busy { opacity: .55; transition: opacity .2s ease; pointer-events: none; }

.af-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%); border-radius: 20px; padding: 1.5rem 1.6rem 1.3rem; color: #fff; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.3fr); gap: 1.5rem; }
.af-crumb { font-size: .76rem; color: rgba(255,255,255,.65); }
.af-crumb a { color: rgba(255,255,255,.8); text-decoration: none; }
.af-hero h1 { margin: .2rem 0 0; font-size: 1.5rem; font-weight: 800; color: #fff; letter-spacing: -.02em; display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
.af-pill { font-size: .72rem; font-weight: 700; padding: .2rem .6rem; border-radius: 99px; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.2); }
.af-big-lbl { margin-top: 1rem; font-size: .76rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: rgba(255,255,255,.72); }
.af-big { display: flex; align-items: baseline; gap: .5rem; white-space: nowrap; }
.af-big b { font-size: clamp(1.9rem, 3.4vw, 2.7rem); font-weight: 800; letter-spacing: -.03em; }
.af-big small { font-size: .9rem; font-weight: 700; color: rgba(255,255,255,.7); }
.af-delta { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; font-size: .82rem; color: rgba(255,255,255,.8); }
.af-delta-chip { background: #fff; font-size: .74rem; font-weight: 800; padding: .2rem .55rem; border-radius: 99px; }
.af-delta-chip.is-up { color: #047857; } .af-delta-chip.is-down { color: #b91c1c; } .af-delta-chip.is-flat { color: #475569; }
.af-tiles { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .6rem; margin-top: 1rem; }
.af-tile { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); border-radius: 14px; padding: .7rem .8rem; color: #fff; display: flex; flex-direction: column; gap: .15rem; text-decoration: none; min-width: 0; }
a.af-tile:hover { background: rgba(255,255,255,.18); color: #fff; }
.af-tile span { font-size: .66rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: rgba(255,255,255,.72); }
.af-tile b { font-size: 1.05rem; font-weight: 800; white-space: nowrap; }
.af-tile small { font-size: .7rem; color: rgba(255,255,255,.72); }
.af-tile--white { background: #fff; border-color: #fff; color: #0f172a; }
a.af-tile--white:hover { background: #f8fafc; color: #0f172a; }
.af-tile--white span { color: #b91c1c; } .af-tile--white small { color: #64748b; }
.af-hero-chart { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.14); border-radius: 16px; padding: .9rem 1rem .6rem; display: flex; flex-direction: column; gap: .4rem; min-width: 0; }
.af-hero-chart-h { display: flex; justify-content: space-between; align-items: center; gap: .5rem; font-size: .86rem; font-weight: 700; }
.af-seg { display: inline-flex; background: rgba(255,255,255,.12); border-radius: 8px; padding: 3px; }
.af-seg button { border: 0; background: transparent; color: rgba(255,255,255,.8); font-size: .74rem; font-weight: 700; padding: .3rem .65rem; border-radius: 6px; cursor: pointer; }
.af-seg button.is-on { background: #fff; color: #0453cb; }
.af-hero-canvas { position: relative; height: 210px; }

.af-filters { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: .8rem 1rem; display: flex; align-items: flex-end; gap: .9rem; flex-wrap: wrap; }
.af-field { display: flex; flex-direction: column; gap: .3rem; flex: 1 1 200px; min-width: 0; }
.af-field > label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
.af-field .au-select { display: flex; width: 100%; }
.af-reset { border: 1px solid #dbe5f3; background: #fff; color: #0453cb; font-weight: 700; font-size: .82rem; border-radius: 10px; padding: .6rem .9rem; cursor: pointer; white-space: nowrap; }
.af-reset:hover { border-color: #0453cb; }

.af-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .9rem; }
.af-kpi { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: .35rem; color: #1e293b; text-decoration: none; }
a.af-kpi[href]:hover { border-color: #b9cdee; box-shadow: 0 8px 26px rgba(4,83,203,.08); color: #1e293b; }
.af-kpi-l { font-size: .8rem; font-weight: 600; color: #64748b; }
.af-kpi-v { display: flex; align-items: baseline; gap: .35rem; white-space: nowrap; }
.af-kpi-v b { font-size: clamp(1.15rem, 1.7vw, 1.45rem); font-weight: 800; color: #0f172a; }
.af-kpi-v small { font-size: .72rem; font-weight: 700; color: #94a3b8; }
.af-kpi-r { font-size: .75rem; color: #64748b; }
.af-meter { height: 6px; background: #eef2f7; border-radius: 99px; overflow: hidden; }
.af-meter > i { display: block; height: 100%; background: linear-gradient(90deg, #0453cb, #5e91de); border-radius: 99px; }

.af-row { display: grid; gap: 1rem; }
.af-row--3 { grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr) minmax(0, 1fr); }
.af-row--2 { grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); }
.af-card { background: #fff; border-radius: 16px; padding: 1.1rem 1.2rem; box-shadow: 0 1px 3px rgba(15,23,42,.05); border: 1px solid #edf1f7; display: flex; flex-direction: column; gap: .75rem; min-width: 0; }
.af-card-h { display: flex; justify-content: space-between; align-items: center; gap: .6rem; flex-wrap: wrap; }
.af-card-t { font-size: .95rem; font-weight: 800; color: #0f172a; }
.af-card-s { font-size: .76rem; color: #64748b; }
.af-lnk { font-size: .8rem; font-weight: 700; color: #0453cb; text-decoration: none; white-space: nowrap; }
.af-lnk:hover { color: #033a8e; }
.af-empty { font-size: .84rem; color: #64748b; padding: 1rem 0; text-align: center; }
.af-clear { display: flex; gap: .6rem; align-items: center; padding: .8rem; border-radius: 12px; background: #f0fdf4; color: #047857; font-weight: 600; font-size: .86rem; }

.af-todo { display: flex; gap: .7rem; align-items: center; padding: .65rem .75rem; border-radius: 12px; border: 1px solid #eef2f7; color: #1e293b; text-decoration: none; }
a.af-todo:hover { border-color: #b9cdee; color: #1e293b; }
.af-dot { width: 9px; height: 9px; border-radius: 99px; flex-shrink: 0; }
.af-dot--bad { background: #dc2626; } .af-dot--warn { background: #f59e0b; } .af-dot--ok { background: #10b981; } .af-dot--info { background: #0453cb; }
.af-todo-t { flex: 1; font-size: .84rem; font-weight: 600; min-width: 0; }
.af-todo-t small { display: block; font-weight: 500; color: #64748b; font-size: .74rem; }
.af-todo-n { font-size: .9rem; font-weight: 800; color: #0f172a; white-space: nowrap; }

.af-donut { display: flex; align-items: center; gap: 1rem; }
.af-donut-c { position: relative; width: 150px; height: 150px; flex-shrink: 0; }
.af-donut-mid { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; }
.af-donut-mid b { font-size: 1rem; font-weight: 800; color: #0f172a; }
.af-donut-mid small { font-size: .68rem; color: #64748b; }
.af-leg { display: flex; flex-direction: column; gap: .45rem; font-size: .8rem; min-width: 0; }
.af-leg span { display: flex; align-items: center; gap: .5rem; }
.af-leg i { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
.af-leg b { margin-left: auto; padding-left: .5rem; font-variant-numeric: tabular-nums; }

.af-age { display: flex; flex-direction: column; gap: .3rem; }
.af-age-l { display: flex; justify-content: space-between; gap: .5rem; font-size: .8rem; }
.af-age-l span { color: #475569; font-weight: 600; }
.af-age-l b { color: #0f172a; white-space: nowrap; }
.af-age-t { height: 8px; background: #eef2f7; border-radius: 99px; overflow: hidden; }
.af-age-t > i { display: block; height: 100%; border-radius: 99px; }

.af-heat { display: grid; grid-template-columns: 34px repeat(12, minmax(0, 1fr)); gap: 4px; font-size: .7rem; color: #94a3b8; align-items: center; }
.af-heat .c { height: 20px; border-radius: 5px; }
.af-heat-leg { display: flex; align-items: center; gap: 4px; font-size: .7rem; color: #94a3b8; justify-content: flex-end; }
.af-heat-leg i { width: 14px; height: 10px; border-radius: 3px; display: inline-block; }

.af-cls { display: grid; grid-template-columns: minmax(90px, 140px) minmax(0, 1fr) 52px; gap: .6rem; align-items: center; font-size: .8rem; }
.af-cls b { color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.af-cls .t { height: 10px; background: #eef2f7; border-radius: 99px; overflow: hidden; }
.af-cls .t > i { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, #0453cb, #5e91de); }
.af-cls em { font-style: normal; font-weight: 800; text-align: right; color: #0f172a; }

.af-pend { display: flex; align-items: center; gap: .75rem; padding: .6rem 0; border-top: 1px solid #f1f5f9; }
.af-pend:first-of-type { border-top: 0; }
.af-av { width: 34px; height: 34px; border-radius: 10px; background: #e8f0fc; color: #0453cb; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .74rem; flex-shrink: 0; }
.af-pend-b { flex: 1; min-width: 0; font-size: .84rem; }
.af-pend-b b { display: block; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.af-pend-b small { color: #64748b; font-size: .74rem; }
.af-pend-m { font-weight: 800; color: #0f172a; white-space: nowrap; font-size: .86rem; }
.af-btn-s { font-size: .76rem; font-weight: 700; padding: .35rem .65rem; border: 1px solid #dbe5f3; border-radius: 8px; color: #0453cb; text-decoration: none; white-space: nowrap; }
.af-btn-s:hover { border-color: #0453cb; color: #033a8e; }

.af-qa { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; }
.af-qa a { display: flex; align-items: center; gap: .6rem; padding: .7rem; border: 1px solid #eef2f7; border-radius: 12px; color: #1e293b; text-decoration: none; font-size: .82rem; font-weight: 700; }
.af-qa a:hover { border-color: #b9cdee; color: #0453cb; }
.af-qa i { width: 32px; height: 32px; border-radius: 9px; background: #e8f0fc; color: #0453cb; display: inline-flex; align-items: center; justify-content: center; font-size: .82rem; flex-shrink: 0; }
.af-qa small { display: block; font-weight: 500; color: #64748b; font-size: .72rem; }

@media (max-width: 1180px) { .af-hero { grid-template-columns: 1fr; } .af-row--3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 860px) { .af-row--3, .af-row--2 { grid-template-columns: 1fr; } .af-tiles { grid-template-columns: 1fr; } }
@media (max-width: 576px) { .af-wrap { padding: 1rem .75rem; } .af-donut { flex-direction: column; align-items: stretch; } .af-donut-c { margin: 0 auto; } }
</style>
@endpush

@section('content')
@php
    // Shell mobile (issue #963, maquette S['comptable:dash']) : le DOM de bureau
    // reste dans .m-only-desktop, l'écran m-* vit à côté, sous 992px.
    $dmShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);
    $dmUser = auth()->user();

    // Santé réconciliation : un seul snapshot, partagé entre le widget de bureau
    // et la ligne « À faire » du mobile (le widget le recalcule s'il est absent).
    $dmRecMetrics = ($dmUser && $dmUser->can('comptabilite.reconciliation.view'))
        ? app(\App\Domain\Comptabilite\Reconciliation\Services\ReconciliationMetricsService::class)->snapshot()
        : null;

    $afAnneeCourante = $anneeActive ? (string) ($anneeActive->name ?? $anneeActive->libelle) : null;
    $afOptionsAnnee = ['' => $afAnneeCourante ? 'Année en cours ('.$afAnneeCourante.')' : 'Année en cours'];
    foreach ($annees as $a) {
        $afOptionsAnnee[(string) $a->id] = (string) ($a->name ?? $a->libelle);
    }
    $afOptionsFiliere = ['' => 'Toutes les filières'];
    foreach ($filieres as $f) {
        $afOptionsFiliere[(string) $f->id] = (string) ($f->name ?? $f->nom);
    }
    $afOptionsClasse = ['' => 'Toutes les classes'];
    foreach ($classes as $cl) {
        $afOptionsClasse[(string) $cl->id] = (string) ($cl->name ?? $cl->nom);
    }

    $afAujourdhui = now()->toDateString();
    // État initial du composant : exactement la forme que renvoie dashboardData(),
    // pour qu'un filtre ne fasse que remplacer l'objet.
    $afInit = [
        'donnees' => [
            'totalDue' => $totalDue, 'countDue' => $countDue, 'totalPaid' => $totalPaid,
            'totalOverdue' => $totalOverdue, 'countOverdueTotal' => $countOverdueTotal,
            'countToValidate' => $countToValidate, 'totalPending' => $totalPending,
            'countValidatedToday' => $countValidatedToday, 'totalValidatedToday' => $totalValidatedToday,
            'totalPaidYesterday' => $totalPaidYesterday, 'totalPaidMonth' => $totalPaidMonth,
            'totalPaidPrevMonthToDate' => $totalPaidPrevMonthToDate,
            'labelsMois' => $labelsMois, 'dataEncaissements' => $dataEncaissements,
            'labelAnneePrecedente' => $labelAnneePrecedente, 'dataEncaissementsPrecedente' => $dataEncaissementsPrecedente,
            'serieJours' => $serieJours, 'modes' => $modes, 'recouvrementParClasse' => $recouvrementParClasse,
            'agingBuckets' => $agingBuckets,
            'paiementsEnAttente' => \App\Actions\Comptabilite\BuildDashboardDataAction::pendingPaymentsToArray($paiementsEnAttente),
            'anneeLabel' => $annee ? (string) ($annee->name ?? $annee->libelle) : '',
        ],
        'url' => route('esbtp.comptabilite.dashboard.data'),
        'liens' => [
            'paiements' => route('esbtp.paiements.index'),
            'aValider' => route('esbtp.paiements.index', ['status' => 'en_attente']),
            'duJour' => route('esbtp.paiements.index', ['status' => 'validé', 'date_debut' => $afAujourdhui, 'date_fin' => $afAujourdhui]),
            'duMois' => route('esbtp.paiements.index', ['status' => 'validé', 'date_debut' => now()->startOfMonth()->toDateString(), 'date_fin' => $afAujourdhui]),
            'valides' => route('esbtp.paiements.index', ['status' => 'validé']),
            'relances' => route('esbtp.comptabilite.relances.index'),
            'suivi' => route('esbtp.paiements.suivi-categories'),
        ],
        'droits' => [
            'voir' => $dmUser?->canany(['paiements.view', 'paiements.view_own']) ?? false,
            'valider' => $dmUser?->can('paiements.validate') ?? false,
            'relancer' => $dmUser?->can('comptabilite.relances.send') ?? false,
        ],
    ];
@endphp

<div class="{{ $dmShell ? 'm-only-desktop' : '' }}" id="dm-bureau">
<script type="application/json" id="af-init">@json($afInit)</script>
<div class="af-wrap" x-data="afDash()" :class="{ 'af-busy': charge }">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-0">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    {{-- ─── Hero cockpit : le mois et sa tendance ─── --}}
    <div class="af-hero">
        <div>
            <div class="af-crumb"><a href="{{ route('dashboard') }}">Accueil</a> · Analyse financière</div>
            <h1>Analyse financière <span class="af-pill" x-text="d.anneeLabel || 'Année en cours'">{{ $annee ? ($annee->name ?? $annee->libelle) : 'Année en cours' }}</span></h1>
            <div class="af-big-lbl">Encaissé ce mois</div>
            <div class="af-big af-num"><b x-text="fmt(d.totalPaidMonth)">{{ number_format($totalPaidMonth, 0, ',', ' ') }}</b><small>FCFA</small></div>
            <div class="af-delta">
                <span class="af-delta-chip" :class="chip(d.totalPaidMonth, d.totalPaidPrevMonthToDate).cls" x-text="chip(d.totalPaidMonth, d.totalPaidPrevMonthToDate).txt"></span>
                <span>vs <span class="af-num" x-text="fmt(d.totalPaidPrevMonthToDate)"></span> au même jour du mois dernier</span>
            </div>
            <div class="af-tiles">
                <a class="af-tile" :href="droits.voir ? liens.duJour : null">
                    <span>Aujourd'hui</span><b class="af-num" x-text="fmt(d.totalValidatedToday)"></b>
                    <small x-text="chip(d.totalValidatedToday, d.totalPaidYesterday).txt + ' vs hier'"></small>
                </a>
                <a class="af-tile" :href="droits.voir ? liens.suivi : null">
                    <span>Recouvrement</span><b class="af-num" x-text="taux() === null ? '—' : taux().toFixed(1).replace('.', ',') + ' %'"></b>
                    <small x-text="'sur ' + fmtCourt(d.totalDue) + ' dus'"></small>
                </a>
                <a class="af-tile af-tile--white" :href="droits.voir ? liens.aValider : null">
                    <span>À valider</span><b class="af-num" x-text="d.countToValidate"></b>
                    <small x-text="fmt(d.totalPending) + ' FCFA'"></small>
                </a>
            </div>
        </div>
        <div class="af-hero-chart">
            <div class="af-hero-chart-h">
                <span x-text="vue === 'mois' ? 'Encaissements nets par mois' : 'Encaissements nets par semaine'"></span>
                <span class="af-seg" role="group" aria-label="Période du graphique">
                    <button type="button" :class="{ 'is-on': vue === 'mois' }" @click="vue = 'mois'; dessiner()">Mois</button>
                    <button type="button" :class="{ 'is-on': vue === 'semaines' }" @click="vue = 'semaines'; dessiner()">12 semaines</button>
                </span>
            </div>
            <div class="af-hero-canvas"><canvas id="afHeroChart" aria-label="Encaissements nets"></canvas></div>
        </div>
    </div>

    @include('esbtp.comptabilite.partials._reconciliation_health_widget', ['recMetrics' => $dmRecMetrics])

    {{-- ─── Filtres (AJAX, sans rechargement) ─── --}}
    <div class="af-filters">
        <div class="af-field">
            <label for="f-annee">Année</label>
            <x-au-select id="f-annee" name="annee" icon="fa-calendar" :options="$afOptionsAnnee" :value="request('annee', '')" :placeholder-is-first-option="false" />
        </div>
        <div class="af-field">
            <label for="f-filiere">Filière</label>
            <x-au-select id="f-filiere" name="filiere" icon="fa-layer-group" :searchable="count($afOptionsFiliere) > 8" :options="$afOptionsFiliere" :value="request('filiere', '')" :placeholder-is-first-option="false" />
        </div>
        <div class="af-field">
            <label for="f-classe">Classe</label>
            <x-au-select id="f-classe" name="classe" icon="fa-users" :searchable="true" :options="$afOptionsClasse" :value="request('classe', '')" :placeholder-is-first-option="false" />
        </div>
        <button type="button" class="af-reset" @click="reinitialiser()"><i class="fas fa-rotate-left"></i> Réinitialiser</button>
    </div>

    {{-- ─── Les quatre chiffres de la période filtrée ─── --}}
    <div class="af-kpis">
        <a class="af-kpi" :href="droits.voir ? liens.suivi : null">
            <span class="af-kpi-l">Total dû</span>
            <span class="af-kpi-v af-num"><b x-text="fmt(d.totalDue)"></b><small>FCFA</small></span>
            <span class="af-kpi-r" x-text="d.countDue + ' inscription' + (d.countDue > 1 ? 's' : '') + ' avec des frais'"></span>
        </a>
        <a class="af-kpi" :href="droits.voir ? liens.valides : null">
            <span class="af-kpi-l">Encaissé sur la période</span>
            <span class="af-kpi-v af-num"><b x-text="fmt(d.totalPaid)"></b><small>FCFA</small></span>
            <span class="af-meter"><i :style="'width:' + (taux() || 0) + '%'"></i></span>
            <span class="af-kpi-r" x-text="taux() === null ? 'aucun frais dû sur ce périmètre' : taux().toFixed(1).replace('.', ',') + ' % du total dû'"></span>
        </a>
        <a class="af-kpi" :href="droits.relancer ? liens.relances : null">
            <span class="af-kpi-l">Reste à percevoir (total)</span>
            <span class="af-kpi-v af-num"><b x-text="fmt(Math.max(0, d.totalDue - d.totalPaid))"></b><small>FCFA</small></span>
            <span class="af-kpi-r">échu ou non</span>
        </a>
        <a class="af-kpi" :href="droits.relancer ? liens.relances : null">
            <span class="af-kpi-l">Impayés échus</span>
            <span class="af-kpi-v af-num"><b x-text="fmt(d.totalOverdue)"></b><small>FCFA</small></span>
            <span class="af-kpi-r" x-text="d.countOverdueTotal + ' étudiant' + (d.countOverdueTotal > 1 ? 's' : '') + ' en retard'"></span>
        </a>
    </div>

    {{-- ─── À faire | modes | ancienneté ─── --}}
    <div class="af-row af-row--3">
        <div class="af-card">
            <div class="af-card-h"><span class="af-card-t">À faire maintenant</span><span class="af-card-s">{{ now()->format('H:i') }}</span></div>
            <template x-if="droits.valider && d.countToValidate > 0">
                <a class="af-todo" :href="liens.aValider"><span class="af-dot af-dot--bad"></span><span class="af-todo-t">Versements à valider<small x-text="fmt(d.totalPending) + ' FCFA en attente'"></small></span><span class="af-todo-n" x-text="d.countToValidate"></span></a>
            </template>
            <template x-if="droits.relancer && d.countOverdueTotal > 0">
                <a class="af-todo" :href="liens.relances"><span class="af-dot af-dot--warn"></span><span class="af-todo-t">Étudiants à relancer<small x-text="fmt(d.totalOverdue) + ' FCFA échus'"></small></span><span class="af-todo-n" x-text="d.countOverdueTotal"></span></a>
            </template>
            <a class="af-todo" :href="droits.voir ? liens.duJour : null"><span class="af-dot af-dot--ok"></span><span class="af-todo-t">Validés aujourd'hui<small x-text="fmt(d.totalValidatedToday) + ' FCFA'"></small></span><span class="af-todo-n" x-text="d.countValidatedToday"></span></a>
            <template x-if="!(droits.valider && d.countToValidate > 0) && !(droits.relancer && d.countOverdueTotal > 0)">
                <div class="af-clear"><i class="fas fa-circle-check"></i> Rien d’urgent sur ce périmètre.</div>
            </template>
        </div>

        <div class="af-card">
            <div class="af-card-h"><span class="af-card-t">Modes de paiement</span><span class="af-card-s">sur la période</span></div>
            <div class="af-donut" x-show="d.modes.length">
                <div class="af-donut-c"><canvas id="afDonut" aria-label="Répartition par mode de paiement"></canvas>
                    <div class="af-donut-mid"><b class="af-num" x-text="fmtCourt(d.totalPaid)"></b><small>FCFA</small></div></div>
                <div class="af-leg">
                    <template x-for="(m, i) in d.modes.slice(0, 5)" :key="m.mode">
                        <span><i :style="'background:' + teinte(i)"></i><span x-text="m.mode"></span><b x-text="part(m.total) + ' %'"></b></span>
                    </template>
                </div>
            </div>
            <div class="af-empty" x-show="!d.modes.length">Aucun encaissement validé sur ce périmètre.</div>
        </div>

        <div class="af-card">
            <div class="af-card-h"><span class="af-card-t">Impayés par ancienneté</span>
                <a class="af-lnk" :href="liens.relances" x-show="droits.relancer">Relancer →</a></div>
            <template x-for="b in anciennete()" :key="b.cle">
                <div class="af-age">
                    <div class="af-age-l"><span x-text="b.lib + ' · ' + b.count + ' étud.'"></span><b class="af-num" x-text="fmt(b.amount)"></b></div>
                    <div class="af-age-t"><i :style="'width:' + b.pct + '%;background:' + b.teinte"></i></div>
                </div>
            </template>
        </div>
    </div>

    {{-- ─── Activité (carte de chaleur) | classes les moins recouvrées ─── --}}
    <div class="af-row af-row--2">
        <div class="af-card">
            <div class="af-card-h"><span class="af-card-t">Activité de la caisse · 12 dernières semaines</span>
                <span class="af-heat-leg">moins <i style="background:#eef3fa"></i><i style="background:#c9dbf6"></i><i style="background:#8fb3ec"></i><i style="background:#3b7ddb"></i><i style="background:#033a8e"></i> plus</span></div>
            <div class="af-heat">
                <template x-for="(ligne, j) in chaleur()" :key="j">
                    <div style="display: contents">
                        <span x-text="['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'][j]"></span>
                        <template x-for="(c, k) in ligne" :key="k">
                            <span class="c" :style="'background:' + c.teinte" :title="c.titre"></span>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="af-card">
            <div class="af-card-h"><span class="af-card-t">Classes les moins recouvrées</span>
                <a class="af-lnk" :href="liens.suivi" x-show="droits.voir">Suivi par frais →</a></div>
            <template x-for="cl in d.recouvrementParClasse" :key="cl.classe_id">
                <div class="af-cls" :title="fmt(cl.paye) + ' encaissés sur ' + fmt(cl.du) + ' FCFA dus'">
                    <b x-text="cl.classe"></b><span class="t"><i :style="'width:' + cl.taux + '%'"></i></span><em class="af-num" x-text="Math.round(cl.taux) + ' %'"></em>
                </div>
            </template>
            <div class="af-empty" x-show="!d.recouvrementParClasse.length">Aucun frais dû sur ce périmètre.</div>
        </div>
    </div>

    {{-- ─── Paiements en attente | accès rapides ─── --}}
    <div class="af-row af-row--2">
        <div class="af-card">
            <div class="af-card-h"><span class="af-card-t">Paiements en attente de validation</span>
                <a class="af-lnk" :href="liens.aValider" x-show="droits.voir">Voir tout →</a></div>
            <template x-for="p in d.paiementsEnAttente" :key="p.url">
                <div class="af-pend">
                    <span class="af-av" x-text="((p.nom || '').charAt(0) + (p.prenoms || '').charAt(0)).toUpperCase()"></span>
                    <span class="af-pend-b"><b x-text="(p.nom || '') + ' ' + (p.prenoms || '')"></b><small x-text="p.categorie + ' · ' + p.date"></small></span>
                    <span class="af-pend-m af-num" x-text="fmt(p.montant) + ' FCFA'"></span>
                    <a class="af-btn-s" :href="p.url">Ouvrir</a>
                </div>
            </template>
            <div class="af-clear" x-show="!d.paiementsEnAttente.length"><i class="fas fa-circle-check"></i> Aucun paiement en attente.</div>
        </div>

        <div class="af-card">
            <div class="af-card-h"><span class="af-card-t">Accès rapides</span></div>
            <div class="af-qa">
                @canany(['paiements.view', 'paiements.view_own'])
                    <a href="{{ route('esbtp.paiements.index') }}"><i class="fas fa-money-bill-wave"></i><span>Paiements<small>Historique</small></span></a>
                    <a href="{{ route('esbtp.paiements.suivi-categories') }}"><i class="fas fa-chart-pie"></i><span>Suivi<small>Par catégorie</small></span></a>
                @endcanany
                @can('comptabilite.relances.send')
                    <a href="{{ route('esbtp.comptabilite.relances.index') }}"><i class="fas fa-paper-plane"></i><span>Relances<small>Impayés</small></span></a>
                @endcan
                @can('frais.view')
                    <a href="{{ route('esbtp.frais.index') }}"><i class="fas fa-tags"></i><span>Frais<small>Catégories</small></span></a>
                @endcan
                @can('frais.configure')
                    <a href="{{ route('esbtp.frais.configure') }}"><i class="fas fa-sliders-h"></i><span>Configuration<small>Frais et tarifs</small></span></a>
                @endcan
                @can('comptabilite.reports.export')
                    <a href="{{ route('esbtp.paiements.index', ['format' => 'export-excel']) }}"><i class="fas fa-file-excel"></i><span>Export<small>Excel</small></span></a>
                @endcan
            </div>
        </div>
    </div>
</div>
</div>

@if($dmShell)
@php
    // ── Écran mobile (maquette S['comptable:dash']) ──────────────────────────
    $dmEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $dmEcoleNom = $dmEcole['name'] ?: ($dmEcole['acronym'] ?: config('app.name'));
    $dmAnneeLabel = $annee ? (string) ($annee->name ?? $annee->libelle ?? '') : '';
    $dmAnneeEnCours = $annee && $anneeActive && (int) $annee->id === (int) $anneeActive->id;
    $dmDateJour = now()->translatedFormat('j M');
    $dmTaux = $totalDue > 0 ? min(100, round(($totalPaid / $totalDue) * 100, 1)) : null;
    $dmAujourdhui = now()->format('Y-m-d');

    // Montant compact lisible : « 207,4 M », « 112 M », « 850 k », « 9 500 ».
    // Miroir exact côté navigateur : mCompact() dans la fabrique dashComptaMobile.
    $dmCompact = function (float $n): string {
        $abs = abs($n);
        if ($abs >= 1_000_000_000) {
            $s = number_format($n / 1_000_000_000, 1, ',', ' ') . ' Md';
        } elseif ($abs >= 1_000_000) {
            $s = number_format($n / 1_000_000, 1, ',', ' ') . ' M';
        } elseif ($abs >= 10_000) {
            $s = number_format($n / 1_000, 0, ',', ' ') . ' k';
        } else {
            return number_format($n, 0, ',', ' ');
        }

        return str_replace(',0 ', ' ', $s);
    };

    // Chaque KPI et chaque ligne « À faire » n'est un lien que si la personne a le
    // droit d'arriver sur la page visée (mêmes gardes que les routes).
    $dmPeutVoirPaiements = $dmUser->can('paiements.view') || $dmUser->can('paiements.view_own');
    $dmPeutRelancer = $dmUser->can('comptabilite.relances.send');
    $dmPeutRecouvrer = $dmUser->can('comptabilite.recouvrement.access');
    $dmPeutValider = $dmUser->can('paiements.validate');

    $dmHrefDu = $dmPeutVoirPaiements ? route('esbtp.paiements.suivi-categories') : null;
    $dmHrefImpaye = $dmPeutRelancer
        ? route('esbtp.comptabilite.relances.index')
        : ($dmPeutRecouvrer ? route('esbtp.comptabilite.recouvrement.index') : null);
    $dmHrefAValider = $dmPeutValider ? route('esbtp.paiements.index', ['status' => 'en_attente']) : null;
    $dmHrefValides = $dmPeutVoirPaiements
        ? route('esbtp.paiements.index', ['status' => 'validé', 'date_debut' => $dmAujourdhui, 'date_fin' => $dmAujourdhui])
        : null;

    // Ligne réconciliation : l'état et le seuil viennent du snapshot du service,
    // jamais d'un nombre écrit ici. Absente sans la permission de voir la réconciliation.
    $dmRec = null;
    if ($dmRecMetrics !== null) {
        $dmRecEnRetard = (int) ($dmRecMetrics['overdue_draft_count'] ?? 0);
        $dmRecBrouillons = (int) ($dmRecMetrics['sessions_by_status']['draft'] ?? 0);
        $dmRecEnRevue = (int) ($dmRecMetrics['sessions_by_status']['review'] ?? 0);
        $dmRecDernier = $dmRecMetrics['days_since_last_close'] ?? null;
        if ($dmRecEnRetard > 0) {
            $dmRec = [
                'title' => $dmRecEnRetard . ' réconciliation' . ($dmRecEnRetard > 1 ? 's' : '') . ' en retard',
                'sub' => 'Ouverte' . ($dmRecEnRetard > 1 ? 's' : '') . ' depuis plus de ' . (int) ($dmRecMetrics['overdue_threshold_days'] ?? 0) . ' j sans clôture',
                'chip' => 'En attente',
                'tone' => 'warn',
                'href' => route('esbtp.comptabilite.reconciliation.index', ['status' => 'draft']),
            ];
        } elseif ($dmRecBrouillons + $dmRecEnRevue > 0) {
            $dmRecEnCours = $dmRecBrouillons + $dmRecEnRevue;
            $dmRec = [
                'title' => $dmRecEnCours . ' réconciliation' . ($dmRecEnCours > 1 ? 's' : '') . ' en cours',
                'sub' => $dmRecBrouillons . ' brouillon' . ($dmRecBrouillons > 1 ? 's' : '') . ' · ' . $dmRecEnRevue . ' en revue',
                'chip' => 'En cours',
                'tone' => 'info',
                'href' => route('esbtp.comptabilite.reconciliation.index'),
            ];
        } else {
            $dmRec = [
                'title' => 'Réconciliation caisse à jour',
                'sub' => $dmRecDernier !== null
                    ? 'Dernière clôture il y a ' . (int) $dmRecDernier . ' j' . (!empty($dmRecMetrics['last_close_code']) ? ' · ' . $dmRecMetrics['last_close_code'] : '')
                    : 'Aucune clôture enregistrée pour le moment',
                'chip' => 'OK',
                'tone' => 'ok',
                'href' => route('esbtp.comptabilite.reconciliation.index'),
            ];
        }
    }
    $dmRienAFaire = !$dmPeutRecouvrer && $dmRec === null && !$dmPeutValider;

    $dmSerie = $serieRecente ?? ['labels' => [], 'data' => [], 'total' => 0.0, 'jours' => 0];
    $dmConfig = [
        'dataUrl' => route('esbtp.comptabilite.dashboard.data'),
        'dateJour' => $dmDateJour,
        'filtres' => [
            'annee' => (string) request('annee', ''),
            'filiere' => (string) request('filiere', ''),
            'classe' => (string) request('classe', ''),
        ],
        'd' => [
            'totalDue' => (float) $totalDue,
            'totalPaid' => (float) $totalPaid,
            'totalOverdue' => (float) $totalOverdue,
            'countDue' => (int) $countDue,
            'countOverdue' => (int) $countOverdue,
            'countToValidate' => (int) ($countToValidate ?? 0),
            'countOverdueTotal' => (int) ($countOverdueTotal ?? 0),
            'countValidatedToday' => (int) ($countValidatedToday ?? 0),
            'totalValidatedToday' => (float) ($totalValidatedToday ?? 0),
            'anneeLabel' => $dmAnneeLabel,
            'serieRecente' => $dmSerie,
        ],
    ];
@endphp
<div class="m-only-mobile m-screen dm-screen" x-data="dashComptaMobile({{ \Illuminate\Support\Js::from($dmConfig) }})">
    <x-m.appbar :title="$dmEcoleNom . ' · Finances'"
                :sub="$dmAnneeLabel !== '' ? 'Année ' . $dmAnneeLabel . ($dmAnneeEnCours ? ' · en cours' : '') : null"
                action="grid"
                action-label="Filtrer par année, filière ou classe"
                x-on:click="mOuvrirFiltres()">
        @if($dmPeutRelancer)
            <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="m-ib ghost" aria-label="Relances des impayés">
                <x-m.icon name="bell" />
            </a>
        @endif
    </x-m.appbar>

    <div class="m-body" data-m-ptr="reload">
        <section class="m-hero" aria-live="polite">
            <span class="k" x-text="mHeroLabel()">Encaissé{{ $dmAnneeLabel !== '' ? ' · ' . $dmAnneeLabel : '' }} · au {{ $dmDateJour }}</span>
            <span class="v"><span x-text="mFmt(d.totalPaid)">{{ number_format((float) $totalPaid, 0, ',', ' ') }}</span><small>FCFA</small></span>
            <div class="row">
                <span class="pill" x-text="mPillTaux()">{{ $dmTaux !== null ? str_replace('.', ',', (string) $dmTaux) . ' % recouvré' : 'Aucun frais dû' }}</span>
                <span class="pill" x-text="mPluriel(d.countDue, 'souscription')">{{ $countDue }} souscription{{ $countDue > 1 ? 's' : '' }}</span>
                <span class="pill" x-show="mFiltreActif()" x-cloak>Filtré</span>
            </div>
        </section>

        {{-- Squelette pendant le rechargement des chiffres (filtres). --}}
        <div class="m-skel" x-show="loading" x-cloak aria-hidden="true"><i></i><i></i><i></i></div>

        <div class="dm-contenu" x-show="!loading">
            <div class="m-kpi">
                <a @if($dmHrefDu) href="{{ $dmHrefDu }}" @endif class="m-kpi-link">
                    <span class="v" x-text="mCompact(d.totalDue)">{{ $dmCompact((float) $totalDue) }}</span>
                    <span class="l">Total frais dus</span>
                    <span class="d mute" x-text="'FCFA · ' + mPluriel(d.countDue, 'souscription')">FCFA · {{ $countDue }} souscription{{ $countDue > 1 ? 's' : '' }}</span>
                </a>
                <a @if($dmHrefImpaye) href="{{ $dmHrefImpaye }}" @endif class="m-kpi-link">
                    <span class="v" x-text="mCompact(d.totalOverdue)">{{ $dmCompact((float) $totalOverdue) }}</span>
                    <span class="l">Reste impayé</span>
                    <span class="d {{ $countOverdue > 0 ? 'bad' : 'ok' }}"
                          x-bind:class="d.countOverdue > 0 ? 'bad' : 'ok'"
                          x-text="mPluriel(d.countOverdue, 'étudiant') + (d.countOverdue > 1 ? ' concernés' : ' concerné')">{{ $countOverdue }} étudiant{{ $countOverdue > 1 ? 's concernés' : ' concerné' }}</span>
                </a>
                <a @if($dmHrefAValider) href="{{ $dmHrefAValider }}" @endif class="m-kpi-link">
                    <span class="v" x-text="mFmt(d.countToValidate)">{{ (int) ($countToValidate ?? 0) }}</span>
                    <span class="l">À valider</span>
                    <span class="d {{ ($countToValidate ?? 0) > 0 ? 'warn' : 'ok' }}"
                          x-bind:class="d.countToValidate > 0 ? 'warn' : 'ok'"
                          x-text="d.countToValidate > 0 ? 'En attente de validation' : 'Tout est validé'">{{ ($countToValidate ?? 0) > 0 ? 'En attente de validation' : 'Tout est validé' }}</span>
                </a>
                <a @if($dmHrefValides) href="{{ $dmHrefValides }}" @endif class="m-kpi-link">
                    <span class="v" x-text="mFmt(d.countValidatedToday)">{{ (int) ($countValidatedToday ?? 0) }}</span>
                    <span class="l">Validés aujourd'hui</span>
                    <span class="d ok" x-text="mFmt(d.totalValidatedToday) + ' FCFA'">{{ number_format((float) ($totalValidatedToday ?? 0), 0, ',', ' ') }} FCFA</span>
                </a>
            </div>

            <div class="m-sec"><b>À faire aujourd'hui</b></div>
            @if($dmRienAFaire)
                <x-m.empty icon="check" title="Rien à faire ici" text="Les actions du jour (relances, réconciliation, validation) s'affichent selon vos droits." />
            @else
                <div class="m-list">
                    @if($dmPeutRecouvrer)
                        <a href="{{ route('esbtp.comptabilite.recouvrement.index') }}" class="m-row">
                            <div class="av ic" aria-hidden="true"><x-m.icon name="phone" /></div>
                            <div class="tt">
                                <b x-text="d.countOverdueTotal > 0 ? 'Relancer ' + mPluriel(d.countOverdueTotal, 'retard') + ' de paiement' : 'Aucun retard à relancer'">{{ ($countOverdueTotal ?? 0) > 0 ? 'Relancer ' . ($countOverdueTotal ?? 0) . ' retard' . (($countOverdueTotal ?? 0) > 1 ? 's' : '') . ' de paiement' : 'Aucun retard à relancer' }}</b>
                                <span>File de recouvrement du jour · WhatsApp</span>
                            </div>
                            <div class="tr">
                                <span class="m-chip {{ ($countOverdueTotal ?? 0) > 0 ? 'info' : 'ok' }}"
                                      x-bind:class="d.countOverdueTotal > 0 ? 'info' : 'ok'"
                                      x-text="d.countOverdueTotal > 0 ? 'Ouvrir' : 'À jour'">{{ ($countOverdueTotal ?? 0) > 0 ? 'Ouvrir' : 'À jour' }}</span>
                            </div>
                        </a>
                    @endif
                    @if($dmRec !== null)
                        <x-m.row :href="$dmRec['href']" icon="scale" :title="$dmRec['title']" :sub="$dmRec['sub']" :chip="$dmRec['chip']" :chip-type="$dmRec['tone']" />
                    @endif
                    @if($dmPeutValider)
                        <a href="{{ route('esbtp.paiements.index', ['status' => 'en_attente']) }}" class="m-row">
                            <div class="av ic" aria-hidden="true"><x-m.icon name="check" /></div>
                            <div class="tt">
                                <b x-text="d.countToValidate > 0 ? 'Valider ' + mPluriel(d.countToValidate, 'paiement') + ' en attente' : 'Aucun paiement à valider'">{{ ($countToValidate ?? 0) > 0 ? 'Valider ' . ($countToValidate ?? 0) . ' paiement' . (($countToValidate ?? 0) > 1 ? 's' : '') . ' en attente' : 'Aucun paiement à valider' }}</b>
                                <span>Passer en revue, puis valider ou rejeter</span>
                            </div>
                            <div class="tr">
                                <span class="m-chip {{ ($countToValidate ?? 0) > 0 ? 'warn' : 'ok' }}"
                                      x-bind:class="d.countToValidate > 0 ? 'warn' : 'ok'"
                                      x-text="d.countToValidate > 0 ? 'À faire' : 'À jour'">{{ ($countToValidate ?? 0) > 0 ? 'À faire' : 'À jour' }}</span>
                            </div>
                        </a>
                    @endif
                </div>
            @endif

            {{-- Courbe légère en SVG : le tracé est calculé depuis les données, pas de Chart.js en mobile. --}}
            <div class="m-chart">
                <b x-text="'Encaissements · ' + d.serieRecente.jours + ' derniers jours'">Encaissements · {{ (int) ($dmSerie['jours'] ?? 0) }} derniers jours</b>
                <svg viewBox="0 0 300 110" role="img" x-bind:aria-label="mChartLabel()">
                    <defs>
                        <linearGradient id="dm-grad" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0" stop-color="#0453cb" stop-opacity=".28"/>
                            <stop offset="1" stop-color="#0453cb" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <g stroke="#e9edf5"><line x1="0" y1="30" x2="300" y2="30"/><line x1="0" y1="60" x2="300" y2="60"/><line x1="0" y1="90" x2="300" y2="90"/></g>
                    <path x-bind:d="mAire()" fill="url(#dm-grad)"/>
                    <path x-bind:d="mLigne()" fill="none" stroke="#0453cb" stroke-width="2.5" stroke-linejoin="round"/>
                    <circle x-bind:cx="mDernierPoint().x" x-bind:cy="mDernierPoint().y" r="4" fill="#0453cb"/>
                    <text x="4" y="26" font-size="9" fill="#64748b" x-text="mEtiquette(30)"></text>
                    <text x="4" y="56" font-size="9" fill="#64748b" x-text="mEtiquette(60)"></text>
                </svg>
                <div class="dm-axe"><span x-text="mPremierJour()">{{ $dmSerie['labels'][0] ?? '' }}</span><span>Aujourd'hui</span></div>
                <p class="dm-vide" x-show="!(d.serieRecente.total > 0)" x-cloak>Aucun encaissement validé sur la période.</p>
                <p class="dm-total" x-show="d.serieRecente.total > 0">Total sur la période : <b x-text="mFmt(d.serieRecente.total) + ' FCFA'">{{ number_format((float) ($dmSerie['total'] ?? 0), 0, ',', ' ') }} FCFA</b></p>
            </div>
        </div>
    </div>

    {{-- Filtres en feuille : mêmes paramètres que le bureau (annee, filiere, classe), même endpoint JSON. --}}
    <x-m.sheet id="dm-filtres" title="Filtrer le tableau de bord" sub="Année, filière, classe">
        <div class="m-field">
            <span class="dm-lbl" id="dm-f-annee-lbl">Année universitaire</span>
            <div class="m-opt dm-opt" role="radiogroup" aria-labelledby="dm-f-annee-lbl">
                <label>
                    <input type="radio" name="dm_annee" value="" x-model="sheet.annee">
                    <span class="rd" aria-hidden="true"></span>
                    <b>{{ $anneeActive ? ($anneeActive->name ?? $anneeActive->libelle) : 'Année par défaut' }}</b>
                    @if($anneeActive)<span>En cours · par défaut</span>@endif
                </label>
                @foreach($annees as $a)
                    <label>
                        <input type="radio" name="dm_annee" value="{{ $a->id }}" x-model="sheet.annee">
                        <span class="rd" aria-hidden="true"></span>
                        <b>{{ $a->name ?? $a->libelle }}</b>
                        @if($a->is_current)<span>En cours</span>@endif
                    </label>
                @endforeach
            </div>
        </div>
        <div class="m-field">
            <span class="dm-lbl" id="dm-f-filiere-lbl">Filière</span>
            <div class="m-opt dm-opt" role="radiogroup" aria-labelledby="dm-f-filiere-lbl">
                <label>
                    <input type="radio" name="dm_filiere" value="" x-model="sheet.filiere" x-on:change="sheet.classe = ''">
                    <span class="rd" aria-hidden="true"></span>
                    <b>Toutes les filières</b>
                </label>
                @foreach($filieres as $f)
                    <label>
                        <input type="radio" name="dm_filiere" value="{{ $f->id }}" x-model="sheet.filiere" x-on:change="sheet.classe = ''">
                        <span class="rd" aria-hidden="true"></span>
                        <b>{{ $f->name ?? $f->nom }}</b>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="m-field">
            <span class="dm-lbl" id="dm-f-classe-lbl">Classe</span>
            <div class="m-opt dm-opt" role="radiogroup" aria-labelledby="dm-f-classe-lbl">
                <label>
                    <input type="radio" name="dm_classe" value="" x-model="sheet.classe">
                    <span class="rd" aria-hidden="true"></span>
                    <b>Toutes les classes</b>
                </label>
                @foreach($classes as $c)
                    <label x-show="sheet.filiere === '' || String(sheet.filiere) === '{{ (int) $c->filiere_id }}'">
                        <input type="radio" name="dm_classe" value="{{ $c->id }}" x-model="sheet.classe">
                        <span class="rd" aria-hidden="true"></span>
                        <b>{{ $c->name ?? $c->nom }}</b>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="dm-actions">
            <button type="button" class="m-btn g" x-on:click="mReinitialiser()" x-bind:disabled="loading">Réinitialiser</button>
            <button type="button" class="m-btn p" x-bind:disabled="loading" x-on:click="mAppliquer().then(function (ok) { if (ok) { hide(); } })">
                <span x-show="!loading">Appliquer</span>
                <span x-show="loading" x-cloak>Chargement…</span>
            </button>
        </div>
    </x-m.sheet>
</div>
@endif

<x-fab-encaisser />
@endsection

@push('styles')
<style>
/* Écran mobile du tableau de bord comptable (shell m-*, préfixe dm-). Le socle
   mobile-shell.css porte les classes m-* ; ici seulement ce qui est propre à l'écran. */
.dm-contenu { display: grid; gap: 14px; }
.dm-screen .m-kpi .d { min-height: 14px; }
.dm-screen .m-row .tt b { white-space: normal; line-height: 1.25; }
.dm-axe { display: flex; justify-content: space-between; font-size: 11px; color: #94a3b8; margin-top: -4px; }
.dm-vide, .dm-total { margin: 0; font-size: 12.5px; color: #64748b; }
.dm-total b { color: #0f172a; font-variant-numeric: tabular-nums; }
.dm-lbl { display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px; }
.dm-opt { max-height: 40vh; overflow-y: auto; -webkit-overflow-scrolling: touch; }
.dm-opt label { position: relative; }
.dm-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 4px; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';
    // Les instances Chart.js restent hors d'Alpine : un proxy réactif autour
    // d'un graphique le ralentit et casse ses mises à jour.
    let graphe = null;
    let anneau = null;
    const TEINTES = ['#033a8e', '#0453cb', '#3b7ddb', '#5e91de', '#a9c5ee', '#d6e4f8'];
    const nf = new Intl.NumberFormat('fr-FR');

    window.afDash = function () {
        const init = JSON.parse(document.getElementById('af-init').textContent);
        return {
            d: init.donnees,
            liens: init.liens,
            droits: init.droits,
            url: init.url,
            vue: 'mois',
            charge: false,
            _ecoute: null,

            _requete: null,
            _reinit: false,

            init() {
                this._ecoute = (ev) => {
                    if (this._reinit) return;
                    if (['f-annee', 'f-filiere', 'f-classe'].includes(ev.target && ev.target.id)) this.recharger();
                };
                document.addEventListener('change', this._ecoute);
                this.$nextTick(() => this.dessiner());
            },
            destroy() {
                document.removeEventListener('change', this._ecoute);
            },

            fmt(n) { return nf.format(Math.round(Number(n) || 0)); },
            fmtCourt(n) {
                const v = Math.abs(Number(n) || 0);
                if (v >= 1e9) return (n / 1e9).toFixed(1).replace('.', ',').replace(',0', '') + ' Md';
                if (v >= 1e6) return (n / 1e6).toFixed(1).replace('.', ',').replace(',0', '') + ' M';
                if (v >= 1e4) return Math.round(n / 1e3) + ' k';
                return this.fmt(n);
            },
            taux() { return this.d.totalDue > 0 ? Math.min(100, this.d.totalPaid / this.d.totalDue * 100) : null; },
            chip(actuel, reference) {
                if (Math.abs(reference) < 0.01) return { cls: 'is-flat', txt: '—' };
                const v = (actuel - reference) / Math.abs(reference) * 100;
                const txt = (v >= 0 ? '▲ ' : '▼ ') + Math.abs(v).toFixed(Math.abs(v) < 10 ? 1 : 0).replace('.', ',') + ' %';
                return { cls: Math.abs(v) < 0.05 ? 'is-flat' : (v > 0 ? 'is-up' : 'is-down'), txt };
            },
            teinte(i) { return TEINTES[Math.min(i, TEINTES.length - 1)]; },
            part(total) {
                const somme = this.d.modes.reduce((s, m) => s + Math.max(0, m.total), 0);
                return somme > 0 ? Math.round(Math.max(0, total) / somme * 100) : 0;
            },
            anciennete() {
                const libs = { '0-30': 'Moins d’un mois', '31-60': '1 à 2 mois', '61-90': '2 à 3 mois', '90+': 'Plus de 3 mois' };
                const teintes = { '0-30': '#a9c5ee', '31-60': '#5e91de', '61-90': '#0453cb', '90+': '#033a8e' };
                const b = this.d.agingBuckets || {};
                const max = Math.max(1, ...Object.values(b).map((x) => x.amount || 0));
                return Object.keys(libs).map((cle) => ({
                    cle, lib: libs[cle], teinte: teintes[cle],
                    count: (b[cle] && b[cle].count) || 0,
                    amount: (b[cle] && b[cle].amount) || 0,
                    pct: Math.round(((b[cle] && b[cle].amount) || 0) / max * 100),
                }));
            },
            // 12 semaines × 7 jours, lundi en haut, la semaine en cours à droite.
            chaleur() {
                const jours = this.d.serieJours || [];
                const max = Math.max(1, ...jours.map((j) => j.total));
                const paliers = ['#eef3fa', '#c9dbf6', '#8fb3ec', '#3b7ddb', '#033a8e'];
                const lignes = Array.from({ length: 7 }, () => []);
                if (!jours.length) return lignes;
                const premier = new Date(jours[0].jour + 'T00:00:00');
                const decalage = (premier.getDay() + 6) % 7;
                const cases = Array(decalage).fill(null).concat(jours);
                while (cases.length % 7) cases.push(null);
                const colonnes = cases.length / 7;
                const debut = Math.max(0, colonnes - 12) * 7;
                for (let i = debut; i < cases.length; i++) {
                    const j = cases[i];
                    const palier = !j ? 0 : (j.total <= 0 ? 0 : Math.min(4, 1 + Math.floor(j.total / max * 3.999)));
                    const date = j ? new Date(j.jour + 'T00:00:00').toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }) : '';
                    lignes[i % 7].push({ teinte: j ? paliers[palier] : 'transparent', titre: j ? date + ' : ' + this.fmt(j.total) + ' FCFA' : '' });
                }
                return lignes;
            },

            async recharger() {
                const p = new URLSearchParams();
                const v = (id) => (document.getElementById(id) || {}).value || '';
                if (v('f-annee')) p.set('annee', v('f-annee'));
                if (v('f-filiere')) p.set('filiere', v('f-filiere'));
                if (v('f-classe')) p.set('classe', v('f-classe'));
                // Deux filtres changés vite : seule la dernière requête compte,
                // sinon la plus lente écrasait la plus récente.
                if (this._requete) this._requete.abort();
                const requete = new AbortController();
                this._requete = requete;
                this.charge = true;
                try {
                    const r = await fetch(this.url + '?' + p.toString(), { signal: requete.signal, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    this.d = await r.json();
                    history.replaceState(null, '', location.pathname + (p.toString() ? '?' + p.toString() : ''));
                    this.$nextTick(() => this.dessiner());
                } catch (e) {
                    if (e.name === 'AbortError') return;
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Les chiffres n’ont pas pu être rechargés. Réessayez.' } }));
                } finally {
                    if (this._requete === requete) {
                        this._requete = null;
                        this.charge = false;
                    }
                }
            },
            reinitialiser() {
                // Le composant de sélection ne relit sa valeur que sur « change » :
                // sans l'événement, il gardait l'ancien libellé sur des chiffres
                // non filtrés. On l'émet, en ignorant l'écoute, puis un seul rechargement.
                this._reinit = true;
                ['f-annee', 'f-filiere', 'f-classe'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.value = '';
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                });
                this._reinit = false;
                this.recharger();
            },

            dessiner() {
                if (typeof Chart === 'undefined') return;
                this.dessinerTendance();
                this.dessinerAnneau();
            },
            dessinerTendance() {
                const el = document.getElementById('afHeroChart');
                if (!el) return;
                let labels = this.d.labelsMois || [];
                let data = this.d.dataEncaissements || [];
                let prec = this.vue === 'mois' ? (this.d.dataEncaissementsPrecedente || []) : [];
                if (this.vue === 'semaines') {
                    const jours = this.d.serieJours || [];
                    labels = []; data = [];
                    for (let i = 0; i < jours.length; i += 7) {
                        const tranche = jours.slice(i, i + 7);
                        labels.push(new Date(tranche[0].jour + 'T00:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }));
                        data.push(tranche.reduce((s, j) => s + j.total, 0));
                    }
                }
                const ctx = el.getContext('2d');
                const grad = ctx.createLinearGradient(0, 0, 0, 210);
                grad.addColorStop(0, 'rgba(255,255,255,0.35)');
                grad.addColorStop(1, 'rgba(255,255,255,0)');
                const sets = [{ label: this.vue === 'mois' ? (this.d.anneeLabel || 'Année') : 'Semaine', data, borderColor: '#fff', backgroundColor: grad,
                    fill: true, tension: 0.35, borderWidth: 2.4, pointRadius: 0, pointHoverRadius: 5, pointHoverBackgroundColor: '#fff' }];
                if (prec.length) {
                    sets.push({ label: this.d.labelAnneePrecedente || 'Année précédente', data: prec, borderColor: 'rgba(255,255,255,.55)',
                        borderDash: [5, 5], borderWidth: 1.6, fill: false, tension: 0.35, pointRadius: 0 });
                }
                if (graphe) graphe.destroy();
                graphe = new Chart(ctx, {
                    type: 'line',
                    data: { labels, datasets: sets },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 10,
                            callbacks: { label: (i) => ' ' + i.dataset.label + ' : ' + nf.format(Math.round(i.raw)) + ' FCFA' } } },
                        scales: {
                            y: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(255,255,255,.12)' },
                                ticks: { color: 'rgba(255,255,255,.7)', callback: (v) => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v) } },
                            x: { grid: { display: false }, border: { display: false }, ticks: { color: 'rgba(255,255,255,.7)', maxRotation: 0, autoSkip: true } },
                        },
                    },
                });
            },
            dessinerAnneau() {
                const el = document.getElementById('afDonut');
                if (!el) return;
                const modes = this.d.modes.slice(0, 5).filter((m) => m.total > 0);
                if (anneau) anneau.destroy();
                anneau = null;
                if (!modes.length) return;
                anneau = new Chart(el.getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: modes.map((m) => m.mode), datasets: [{ data: modes.map((m) => m.total), backgroundColor: modes.map((_, i) => TEINTES[i]), borderWidth: 2, borderColor: '#fff' }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '72%',
                        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', callbacks: { label: (i) => ' ' + i.label + ' : ' + nf.format(Math.round(i.raw)) + ' FCFA' } } } },
                });
            },
        };
    };
})();
</script>
@endpush


@push('scripts')
<script>
/* Écran mobile du tableau de bord comptable : une fabrique Alpine exposée sous
   garde (patron du projet), branchée sur le même endpoint JSON que les filtres
   de bureau. La courbe est un SVG tracé depuis les données, pas Chart.js. */
if (typeof window.dashComptaMobile !== 'function') {
window.dashComptaMobile = function (config) {
    return {
        d: config.d,
        filtres: Object.assign({}, config.filtres),
        sheet: Object.assign({}, config.filtres),
        loading: false,
        dateJour: config.dateJour,

        /* ---------- formats ---------- */
        mFmt(n) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(Number(n) || 0));
        },
        // Miroir de $dmCompact côté Blade : « 207,4 M », « 112 M », « 850 k », « 9 500 ».
        mCompact(n) {
            n = Number(n) || 0;
            const abs = Math.abs(n);
            const avec = function (v, dec, suffixe) {
                return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: dec }).format(v) + suffixe;
            };
            if (abs >= 1e9) return avec(n / 1e9, 1, ' Md');
            if (abs >= 1e6) return avec(n / 1e6, 1, ' M');
            if (abs >= 1e4) return avec(n / 1e3, 0, ' k');
            return this.mFmt(n);
        },
        mPluriel(n, mot) {
            n = Number(n) || 0;
            return this.mFmt(n) + ' ' + mot + (n > 1 ? 's' : '');
        },

        /* ---------- héro ---------- */
        mHeroLabel() {
            return 'Encaissé' + (this.d.anneeLabel ? ' · ' + this.d.anneeLabel : '') + ' · au ' + this.dateJour;
        },
        mPillTaux() {
            if (!(Number(this.d.totalDue) > 0)) return 'Aucun frais dû';
            const taux = Math.min(100, (Number(this.d.totalPaid) / Number(this.d.totalDue)) * 100);
            return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 }).format(taux) + ' % recouvré';
        },
        mFiltreActif() {
            return !!(this.filtres.annee || this.filtres.filiere || this.filtres.classe);
        },

        /* ---------- courbe SVG (viewBox 300 × 110, tracé entre y = 8 et y = 100) ---------- */
        mValeurs() {
            const serie = this.d.serieRecente;
            return (serie && Array.isArray(serie.data)) ? serie.data.map(Number) : [];
        },
        mMax() {
            const v = this.mValeurs();
            return v.length ? Math.max(0, Math.max.apply(null, v)) : 0;
        },
        mPoints() {
            const v = this.mValeurs();
            const n = v.length;
            if (n === 0) return [];
            const max = this.mMax();
            const pas = n > 1 ? 300 / (n - 1) : 0;
            return v.map(function (val, i) {
                const y = max > 0 ? 100 - (val / max) * 92 : 100;
                return { x: Math.round(i * pas * 10) / 10, y: Math.round(y * 10) / 10 };
            });
        },
        mLigne() {
            const p = this.mPoints();
            if (!p.length) return '';
            return 'M' + p.map(function (q) { return q.x + ' ' + q.y; }).join(' L');
        },
        mAire() {
            const ligne = this.mLigne();
            return ligne ? ligne + ' L300 110 L0 110Z' : '';
        },
        mDernierPoint() {
            const p = this.mPoints();
            return p.length ? p[p.length - 1] : { x: -10, y: -10 };
        },
        // Valeur représentée par une ligne de grille (y = 30, 60 ou 90).
        mEtiquette(y) {
            const max = this.mMax();
            if (max <= 0) return '';
            return this.mCompact(max * (100 - y) / 92);
        },
        mPremierJour() {
            const l = this.d.serieRecente && this.d.serieRecente.labels;
            return (l && l.length) ? l[0] : '';
        },
        mChartLabel() {
            const s = this.d.serieRecente || {};
            return 'Courbe des encaissements validés sur ' + (s.jours || 0) + ' jours, total ' + this.mFmt(s.total) + ' FCFA';
        },

        /* ---------- filtres (feuille) ---------- */
        mOuvrirFiltres() {
            this.sheet = Object.assign({}, this.filtres);
            window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: 'dm-filtres' } }));
        },
        mReinitialiser() {
            this.sheet = { annee: '', filiere: '', classe: '' };
        },
        // Renvoie true si les chiffres ont été rechargés (la feuille se ferme alors).
        async mAppliquer() {
            const params = new URLSearchParams();
            ['annee', 'filiere', 'classe'].forEach((k) => { if (this.sheet[k]) params.set(k, this.sheet[k]); });
            this.loading = true;
            try {
                const url = config.dataUrl + (params.toString() ? '?' + params.toString() : '');
                const res = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error('le serveur a répondu ' + res.status);
                const data = await res.json();
                this.d = Object.assign({}, this.d, data, {
                    serieRecente: data.serieRecente || this.d.serieRecente,
                });
                this.filtres = Object.assign({}, this.sheet);
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Tableau de bord mis à jour.' } }));
                return true;
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Impossible de charger les chiffres : ' + e.message + '.' } }));
                return false;
            } finally {
                this.loading = false;
            }
        },
    };
};
}
</script>
@endpush