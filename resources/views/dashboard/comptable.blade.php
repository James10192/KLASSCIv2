@extends('layouts.app')

@section('title', 'Tableau de bord Comptable')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ═══════════ Accueil comptable — namespace cb- — maquette A (claire, façon Stripe)
       Rule premium-dashboard : repère sur chaque chiffre, lien filtré, file de travail,
       tendance, actions sur les lignes. ═══════════ */
    .cb-wrap { padding: 1.75rem 1.5rem 2rem; max-width: 1320px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem; }
    .cb-num { font-variant-numeric: tabular-nums; }

    .cb-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; }
    .cb-head-date { font-size: .82rem; color: #64748b; font-weight: 500; }
    .cb-head h1 { margin: .15rem 0 0; font-size: 1.75rem; font-weight: 800; color: #0f172a; letter-spacing: -.02em; }
    .cb-head-actions { display: flex; gap: .6rem; flex-wrap: wrap; }
    .cb-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .65rem 1.1rem; border-radius: 10px; font-weight: 700; font-size: .86rem; text-decoration: none; border: 1px solid transparent; white-space: nowrap; transition: background .2s ease, border-color .2s ease; }
    .cb-btn--primary { background: #0453cb; color: #fff; }
    .cb-btn--primary:hover { background: #033a8e; color: #fff; }
    .cb-btn--ghost { background: #fff; color: #0453cb; border-color: #dbe5f3; }
    .cb-btn--ghost:hover { border-color: #0453cb; color: #033a8e; }

    .cb-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; }
    .cb-kpi { display: flex; flex-direction: column; gap: .55rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.1rem 1.15rem .95rem; color: #1e293b; text-decoration: none; transition: border-color .2s ease, box-shadow .2s ease; }
    a.cb-kpi[href]:hover { border-color: #b9cdee; box-shadow: 0 8px 26px rgba(4,83,203,.08); color: #1e293b; }
    .cb-kpi-top { display: flex; justify-content: space-between; align-items: center; gap: .5rem; }
    .cb-kpi-label { font-size: .82rem; font-weight: 600; color: #64748b; }
    .cb-chip { font-size: .74rem; font-weight: 700; padding: .18rem .5rem; border-radius: 99px; white-space: nowrap; }
    .cb-chip--up { background: #dcfce7; color: #047857; }
    .cb-chip--down { background: #fee2e2; color: #b91c1c; }
    .cb-chip--flat { background: #eef2f7; color: #475569; }
    .cb-kpi-val { display: flex; align-items: baseline; gap: .4rem; white-space: nowrap; flex-wrap: wrap; }
    .cb-kpi-val .cb-chip { margin-left: auto; align-self: center; }
    .cb-kpi-val b { font-size: clamp(1.25rem, 1.9vw, 1.65rem); font-weight: 800; color: #0f172a; letter-spacing: -.02em; }
    .cb-kpi-val small { font-size: .74rem; font-weight: 700; color: #94a3b8; }
    .cb-spark { width: 100%; height: 34px; display: block; }
    .cb-kpi-ref { font-size: .76rem; color: #64748b; }

    .cb-row { display: grid; gap: 1rem; }
    .cb-row--2-1 { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); }
    .cb-row--1-1 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cb-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.2rem 1.3rem; display: flex; flex-direction: column; gap: .85rem; min-width: 0; }
    .cb-card-head { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; }
    .cb-card-t { font-size: 1rem; font-weight: 700; color: #0f172a; }
    .cb-card-s { font-size: .8rem; color: #64748b; }
    .cb-card-lnk { font-size: .82rem; font-weight: 700; color: #0453cb; text-decoration: none; white-space: nowrap; }
    .cb-card-lnk:hover { color: #033a8e; }
    .cb-legend { display: flex; gap: 1rem; font-size: .76rem; color: #475569; font-weight: 600; }
    .cb-legend span { display: inline-flex; align-items: center; gap: .4rem; }
    .cb-legend i { display: inline-block; width: 14px; height: 3px; border-radius: 2px; background: #0453cb; }
    .cb-legend i.is-prev { height: 0; border-top: 2px dashed #94a3b8; background: none; }
    .cb-chart { position: relative; height: 260px; }
    .cb-empty { font-size: .86rem; color: #64748b; padding: 1.5rem 0; text-align: center; }

    .cb-todo { display: flex; align-items: center; gap: .8rem; padding: .75rem; border-radius: 12px; background: #f8fafc; border: 1px solid #eef2f7; text-decoration: none; color: #1e293b; transition: border-color .2s ease; }
    a.cb-todo:hover { border-color: #b9cdee; color: #1e293b; }
    .cb-todo-n { min-width: 38px; height: 38px; padding: 0 .35rem; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .86rem; flex-shrink: 0; }
    .cb-todo-n--bad { background: #fee2e2; color: #b91c1c; }
    .cb-todo-n--warn { background: #fef3c7; color: #92400e; }
    .cb-todo-n--info { background: #e8f0fc; color: #0453cb; }
    .cb-todo-b { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .1rem; }
    .cb-todo-b b { font-size: .88rem; color: #0f172a; }
    .cb-todo-b span { font-size: .76rem; color: #64748b; }
    .cb-todo-cta { font-size: .82rem; font-weight: 700; color: #0453cb; white-space: nowrap; }
    .cb-clear { display: flex; align-items: center; gap: .7rem; padding: .9rem; border-radius: 12px; background: #f0fdf4; color: #047857; font-weight: 600; font-size: .88rem; }

    .cb-bar { display: flex; align-items: center; gap: .75rem; }
    .cb-bar-track { flex: 1; position: relative; height: 34px; background: #f1f5fb; border-radius: 8px; overflow: hidden; }
    .cb-bar-fill { position: absolute; top: 0; bottom: 0; left: 0; border-radius: 8px; background: #c9dbf6; }
    .cb-bar-lbl { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); font-size: .82rem; font-weight: 700; color: #0f172a; white-space: nowrap; }
    .cb-bar-val { width: 128px; text-align: right; font-size: .86rem; font-weight: 700; color: #0f172a; white-space: nowrap; }

    .cb-rank { display: flex; align-items: center; gap: .75rem; text-decoration: none; color: #1e293b; }
    .cb-avatar { width: 34px; height: 34px; border-radius: 99px; background: #e8f0fc; color: #0453cb; display: flex; align-items: center; justify-content: center; font-size: .74rem; font-weight: 800; flex-shrink: 0; }
    .cb-rank-b { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .3rem; }
    .cb-rank-l { display: flex; justify-content: space-between; gap: .5rem; font-size: .84rem; }
    .cb-rank-l b { color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cb-rank-l .cb-num { font-weight: 700; color: #b91c1c; white-space: nowrap; }
    .cb-rank-track { display: block; height: 5px; background: #eef2f7; border-radius: 99px; }
    .cb-rank-fill { display: block; height: 5px; border-radius: 99px; background: #0453cb; }
    .cb-rank-s { font-size: .74rem; color: #64748b; }

    .cb-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
    .cb-table th { text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; font-weight: 700; padding: .55rem .6rem; border-bottom: 1px solid #eef2f7; }
    .cb-table td { padding: .7rem .6rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .cb-table tr:last-child td { border-bottom: 0; }
    .cb-table .cb-amt { text-align: right; font-weight: 800; color: #0f172a; white-space: nowrap; }
    .cb-table .cb-act { width: 1%; white-space: nowrap; }
    .cb-st { font-size: .74rem; font-weight: 700; padding: .2rem .55rem; border-radius: 99px; white-space: nowrap; }
    .cb-st--ok { background: #dcfce7; color: #047857; }
    .cb-st--wait { background: #fef3c7; color: #92400e; }
    .cb-st--no { background: #fee2e2; color: #b91c1c; }
    .cb-st--av { background: #e8f0fc; color: #0453cb; }
    .cb-table-wrap { overflow-x: auto; width: 100%; max-width: 100%; min-width: 0; }
    .cb-sub { font-size: .74rem; color: #64748b; }

    .cb-links { display: flex; gap: .6rem; flex-wrap: wrap; }
    .cb-link { display: inline-flex; align-items: center; gap: .5rem; padding: .55rem .9rem; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0; color: #1e293b; font-size: .82rem; font-weight: 600; text-decoration: none; }
    .cb-link i { color: #0453cb; }
    .cb-link:hover { border-color: #b9cdee; color: #0453cb; }
    .cb-warn { display: flex; gap: .7rem; align-items: center; padding: 1rem 1.2rem; border-radius: 12px; background: #fff7ed; border: 1px solid #fde2c2; color: #9a3412; font-size: .88rem; }

    /* Sur une colonne, la file de travail passe avant la courbe (exigence 10). */
    @media (max-width: 1100px) { .cb-row--2-1 { grid-template-columns: 1fr; } .cb-card--todo { order: -1; } }
    @media (max-width: 860px) { .cb-row--1-1 { grid-template-columns: 1fr; } }
    /* Téléphone : chaque versement devient une carte (le tableau élargissait
       la page à 630 px). Montant et statut en tête, actions en pied. */
    @media (max-width: 640px) {
        .cb-table thead { display: none; }
        .cb-table, .cb-table tbody { display: block; }
        .cb-table tr { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .2rem .75rem; padding: .8rem 0; border-top: 1px solid #f1f5f9; }
        .cb-table tr:first-child { border-top: 0; }
        .cb-table td { display: block; padding: 0; border: 0; }
        .cb-table td:nth-child(1) { grid-column: 1; grid-row: 3; font-size: .74rem; }
        .cb-table td:nth-child(2) { grid-column: 1; grid-row: 1; }
        .cb-table td:nth-child(3) { grid-column: 1; grid-row: 2; font-size: .78rem; }
        .cb-table td:nth-child(4) { grid-column: 2; grid-row: 2; justify-self: end; }
        .cb-table .cb-amt { grid-column: 2; grid-row: 1; }
        .cb-table .cb-act { grid-column: 1 / -1; grid-row: 4; width: auto; white-space: normal; margin-top: .35rem; }
        .cb-head-actions { width: 100%; }
        .cb-head-actions .cb-btn { flex: 1 1 auto; justify-content: center; }
    }
    @media (max-width: 576px) {
        .cb-wrap { padding: 1rem .75rem 1.5rem; }
        .cb-head h1 { font-size: 1.4rem; }
        .cb-bar-val { width: 104px; }
    }
</style>
@endpush

@section('content')
@php
    $u = auth()->user();
    $c = $compta ?? null;
    $cbFmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $cbPeutVoir = $u?->canany(['paiements.view', 'paiements.view_own']) ?? false;
    $cbPeutRelancer = $u?->can('comptabilite.relances.send') ?? false;
    $cbPeutAnalyser = $u?->can('comptabilite.dashboard.view') ?? false;
    $cbAujourdhui = now()->toDateString();
    $cbDebutMois = now()->startOfMonth()->toDateString();
    $cbMoisPrec = now()->subMonthNoOverflow()->locale('fr')->isoFormat('MMMM');
    $cbAnneeLib = $anneeEnCours ? (string) ($anneeEnCours->name ?? $anneeEnCours->libelle) : 'Cette année';

    // Delta sémantique : une hausse d'encaissement est une bonne nouvelle.
    $cbChip = function (?float $var): array {
        if ($var === null) {
            return ['cls' => 'cb-chip--flat', 'txt' => '—'];
        }
        $txt = ($var >= 0 ? '▲ ' : '▼ ').number_format(abs($var), abs($var) < 10 ? 1 : 0, ',', ' ').' %';

        return ['cls' => abs($var) < 0.05 ? 'cb-chip--flat' : ($var > 0 ? 'cb-chip--up' : 'cb-chip--down'), 'txt' => $txt];
    };

    // Mini-courbe SVG (viewBox 200×34) à partir d'une liste de valeurs.
    $cbSpark = function (array $valeurs): string {
        $n = count($valeurs);
        if ($n < 2) {
            return '';
        }
        $min = min($valeurs);
        $amp = (max($valeurs) - $min) ?: 1;
        $pts = [];
        foreach (array_values($valeurs) as $i => $v) {
            $pts[] = round($i * 200 / ($n - 1), 1).','.round(31 - (($v - $min) / $amp) * 28, 1);
        }

        return implode(' ', $pts);
    };

    $todo = [];
    if ($c) {
        $jours = collect($c['serieJours'])->slice(-30)->values();
        $totalDu = (float) $c['totalDue'];
        $totalPaye = (float) $c['totalPaid'];
        $reste = max(0, $totalDu - $totalPaye);
        $taux = $totalDu > 0 ? min(100, $totalPaye / $totalDu * 100) : null;
        $gainTaux = $totalDu > 0 ? $c['totalPaidMonth'] / $totalDu * 100 : null;

        // Ce qui était encaissé à la fin de chaque jour de la fenêtre.
        $cumul = [];
        $payeAvant = $totalPaye - $jours->sum('total');
        foreach ($jours as $j) {
            $payeAvant += $j['total'];
            $cumul[] = $payeAvant;
        }
        $sparkEncaisse = $cbSpark($jours->pluck('total')->all());
        $sparkTaux = $totalDu > 0 ? $cbSpark(array_map(fn ($v) => $v / $totalDu, $cumul)) : '';
        $sparkReste = $cbSpark(array_map(fn ($v) => max(0, $totalDu - $v), $cumul));

        $varMois = \App\Domain\Comptabilite\TableauDeBord\IndicateursDeCaisse::variation((float) $c['totalPaidMonth'], (float) $c['totalPaidPrevMonthToDate']);
        $varJour = \App\Domain\Comptabilite\TableauDeBord\IndicateursDeCaisse::variation((float) $c['totalValidatedToday'], (float) $c['totalPaidYesterday']);

        $modes = collect($c['modesMois']);
        $modesMax = max(1, (float) $modes->max('total'));
        $top = collect($c['topEchus']);
        $topMax = max(1, (float) $top->max('solde'));

        // File de travail, du plus urgent au moins urgent (exigence 3).
        if ($c['countToValidate'] > 0 && $cbPeutVoir) {
            $age = $plusVieilleAttente ? \Carbon\Carbon::parse($plusVieilleAttente)->locale('fr')->diffForHumans(['parts' => 1]) : null;
            $todo[] = ['ton' => 'bad', 'n' => $c['countToValidate'], 't' => 'Versement'.($c['countToValidate'] > 1 ? 's' : '').' à valider',
                'd' => $cbFmt($c['totalPending']).' FCFA'.($age ? ' · le plus ancien '.$age : ''),
                'cta' => 'Valider', 'href' => route('esbtp.paiements.index', ['status' => 'en_attente'])];
        }
        if ($c['countOverdueTotal'] > 0 && $cbPeutRelancer) {
            $todo[] = ['ton' => 'warn', 'n' => $c['countOverdueTotal'], 't' => 'Étudiant'.($c['countOverdueTotal'] > 1 ? 's' : '').' en retard de paiement',
                'd' => $cbFmt($c['totalOverdue']).' FCFA échus à relancer',
                'cta' => 'Relancer', 'href' => route('esbtp.comptabilite.relances.index')];
        }
        if ($u?->can('comptabilite.reconciliation.view')) {
            $jr = $derniereReconciliation ? (int) \Carbon\Carbon::parse($derniereReconciliation)->diffInDays(now()) : null;
            if ($jr === null || $jr > 7) {
                $todo[] = ['ton' => 'info', 'n' => $jr === null ? '—' : $jr.' j', 't' => 'Réconciliation de caisse',
                    'd' => $jr === null ? 'Aucune session clôturée pour l’instant' : 'Dernière clôture il y a '.$jr.' jours',
                    'cta' => 'Ouvrir', 'href' => route('esbtp.comptabilite.reconciliation.index')];
            }
        }
    }
@endphp

<div class="cb-wrap">

    <div class="cb-head">
        <div>
            <div class="cb-head-date">{{ ucfirst(now()->locale('fr')->isoFormat('dddd D MMMM YYYY')) }}@if($anneeEnCours) · Année {{ $cbAnneeLib }}@endif</div>
            <h1>Tableau de bord comptable</h1>
        </div>
        <div class="cb-head-actions">
            @if($cbPeutAnalyser)
                <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="cb-btn cb-btn--ghost"><i class="fas fa-chart-line"></i> Analyse financière</a>
            @endif
            @can('comptabilite.access')
            @can('comptabilite.journal.view')
                <a href="{{ route('esbtp.comptabilite.journal-caisse.index') }}" class="cb-btn cb-btn--ghost"><i class="fas fa-book"></i> Journal de caisse</a>
            @endcan
            @endcan
            @can('porte:esbtp.paiements.create')
                <a href="{{ route('esbtp.paiements.create') }}" class="cb-btn cb-btn--primary"><i class="fas fa-plus"></i> Encaisser</a>
            @endcan
        </div>
    </div>

    @if(!$c)
        <div class="cb-warn"><i class="fas fa-triangle-exclamation"></i> Les indicateurs sont momentanément indisponibles. L’incident est journalisé ; les listes restent accessibles depuis le menu.</div>
    @else

    {{-- ─── Indicateurs : chacun avec son repère (exigence 1) et sa liste filtrée (2) ─── --}}
    <div class="cb-kpis">
        @php $ch = $cbChip($varMois); @endphp
        <a class="cb-kpi" @if($cbPeutVoir) href="{{ route('esbtp.paiements.index', ['status' => 'validé', 'date_debut' => $cbDebutMois, 'date_fin' => $cbAujourdhui]) }}" @endif>
            <span class="cb-kpi-top"><span class="cb-kpi-label">Encaissé ce mois</span></span>
            <span class="cb-kpi-val cb-num"><b>{{ $cbFmt($c['totalPaidMonth']) }}</b><small>FCFA</small><span class="cb-chip {{ $ch['cls'] }}">{{ $ch['txt'] }}</span></span>
            @if($sparkEncaisse)<svg class="cb-spark" viewBox="0 0 200 34" preserveAspectRatio="none" aria-hidden="true"><polyline points="{{ $sparkEncaisse }}" fill="none" stroke="#0453cb" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke"></polyline></svg>@endif
            <span class="cb-kpi-ref">vs {{ $cbFmt($c['totalPaidPrevMonthToDate']) }} au même jour de {{ $cbMoisPrec }}</span>
        </a>

        <a class="cb-kpi" @if($cbPeutVoir) href="{{ route('esbtp.paiements.suivi-categories') }}" @endif>
            <span class="cb-kpi-top"><span class="cb-kpi-label">Taux de recouvrement</span></span>
            <span class="cb-kpi-val cb-num"><b>{{ $taux === null ? '—' : number_format($taux, 1, ',', ' ') }}</b><small>%</small>@if($gainTaux !== null)<span class="cb-chip {{ $gainTaux > 0.05 ? 'cb-chip--up' : 'cb-chip--flat' }}">+{{ number_format($gainTaux, 1, ',', ' ') }} pts ce mois</span>@endif</span>
            @if($sparkTaux)<svg class="cb-spark" viewBox="0 0 200 34" preserveAspectRatio="none" aria-hidden="true"><polyline points="{{ $sparkTaux }}" fill="none" stroke="#0453cb" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke"></polyline></svg>@endif
            <span class="cb-kpi-ref">{{ $cbFmt($totalPaye) }} encaissés sur {{ $cbFmt($totalDu) }} FCFA dus</span>
        </a>

        <a class="cb-kpi" @if($cbPeutRelancer) href="{{ route('esbtp.comptabilite.relances.index') }}" @endif>
            <span class="cb-kpi-top"><span class="cb-kpi-label">Reste à percevoir (total)</span></span>
            <span class="cb-kpi-val cb-num"><b>{{ $cbFmt($reste) }}</b><small>FCFA</small>@if($c['totalPaidMonth'] > 0)<span class="cb-chip cb-chip--up">− {{ $cbFmt($c['totalPaidMonth']) }} ce mois</span>@endif</span>
            @if($sparkReste)<svg class="cb-spark" viewBox="0 0 200 34" preserveAspectRatio="none" aria-hidden="true"><polyline points="{{ $sparkReste }}" fill="none" stroke="#0453cb" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke"></polyline></svg>@endif
            <span class="cb-kpi-ref">dont {{ $cbFmt($c['totalOverdue']) }} FCFA échus · {{ $c['countOverdueTotal'] }} étudiant{{ $c['countOverdueTotal'] > 1 ? 's' : '' }}</span>
        </a>

        @php $ch = $cbChip($varJour); @endphp
        <a class="cb-kpi" @if($cbPeutVoir) href="{{ route('esbtp.paiements.index', ['status' => 'validé', 'date_debut' => $cbAujourdhui, 'date_fin' => $cbAujourdhui]) }}" @endif>
            <span class="cb-kpi-top"><span class="cb-kpi-label">Encaissé aujourd'hui</span></span>
            <span class="cb-kpi-val cb-num"><b>{{ $cbFmt($c['totalValidatedToday']) }}</b><small>FCFA</small><span class="cb-chip {{ $ch['cls'] }}">{{ $ch['txt'] }} vs hier</span></span>
            <span class="cb-kpi-ref">{{ $c['countValidatedToday'] }} versement{{ $c['countValidatedToday'] > 1 ? 's' : '' }} · hier {{ $cbFmt($c['totalPaidYesterday']) }} FCFA</span>
            <span class="cb-kpi-ref">{{ $c['countToValidate'] }} en attente de validation</span>
        </a>
    </div>

    {{-- ─── Tendance (4) | À traiter (3) ─── --}}
    <div class="cb-row cb-row--2-1">
        <div class="cb-card">
            <div class="cb-card-head">
                <div><div class="cb-card-t">Encaissements nets</div><div class="cb-card-s">Par mois, remboursements déduits</div></div>
                <div class="cb-legend">
                    <span><i></i>{{ $cbAnneeLib }}</span>
                    @if($c['labelAnneePrecedente'])<span><i class="is-prev"></i>{{ $c['labelAnneePrecedente'] }}</span>@endif
                </div>
            </div>
            @if(count($c['labelsMois']) === 0)
                <div class="cb-empty">Les dates de l’année universitaire ne sont pas renseignées : la courbe ne peut pas être tracée.</div>
            @else
                <div class="cb-chart"><canvas id="cbChart" aria-label="Encaissements nets par mois"></canvas></div>
            @endif
        </div>

        <div class="cb-card cb-card--todo">
            <div class="cb-card-head"><span class="cb-card-t">À traiter</span><span class="cb-card-s">contrôlé à {{ now()->format('H:i') }}</span></div>
            @forelse($todo as $t)
                <a href="{{ $t['href'] }}" class="cb-todo">
                    <span class="cb-todo-n cb-todo-n--{{ $t['ton'] }} cb-num">{{ $t['n'] }}</span>
                    <span class="cb-todo-b"><b>{{ $t['t'] }}</b><span>{{ $t['d'] }}</span></span>
                    <span class="cb-todo-cta">{{ $t['cta'] }} →</span>
                </a>
            @empty
                <div class="cb-clear"><i class="fas fa-circle-check"></i> Rien en attente. Tout est à jour.</div>
            @endforelse
        </div>
    </div>

    {{-- ─── Répartition | Plus gros impayés échus ─── --}}
    <div class="cb-row cb-row--1-1">
        <div class="cb-card">
            <div class="cb-card-head"><span class="cb-card-t">Par mode de paiement</span><span class="cb-card-s">{{ now()->locale('fr')->isoFormat('MMMM') }}</span></div>
            @forelse($modes as $m)
                <div class="cb-bar">
                    <div class="cb-bar-track"><div class="cb-bar-fill" style="width: {{ max(2, round($m['total'] / $modesMax * 100)) }}%"></div><span class="cb-bar-lbl">{{ $m['mode'] }} · {{ $m['count'] }}</span></div>
                    <span class="cb-bar-val cb-num">{{ $cbFmt($m['total']) }}</span>
                </div>
            @empty
                <div class="cb-empty">Aucun encaissement validé ce mois-ci.</div>
            @endforelse
        </div>

        <div class="cb-card">
            <div class="cb-card-head">
                <span class="cb-card-t">Plus gros impayés échus</span>
                @if($cbPeutRelancer)<a href="{{ route('esbtp.comptabilite.relances.index') }}" class="cb-card-lnk">Relancer →</a>@endif
            </div>
            @forelse($top as $e)
                @php $ini = collect(preg_split('/\s+/', trim($e['nom'])))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8'))->implode(''); @endphp
                <a class="cb-rank" @can('students.view') @if($e['id']) href="{{ route('esbtp.etudiants.show', $e['id']) }}" @endif @endcan>
                    <span class="cb-avatar">{{ $ini }}</span>
                    <span class="cb-rank-b">
                        <span class="cb-rank-l"><b>{{ $e['nom'] }}</b><span class="cb-num">{{ $cbFmt($e['solde']) }}</span></span>
                        <span class="cb-rank-track"><span class="cb-rank-fill" style="width: {{ max(3, round($e['solde'] / $topMax * 100)) }}%"></span></span>
                        <span class="cb-rank-s">{{ $e['classe'] ?? 'Sans classe' }} · en retard de {{ $e['jours'] }} jour{{ $e['jours'] > 1 ? 's' : '' }}</span>
                    </span>
                </a>
            @empty
                <div class="cb-clear"><i class="fas fa-circle-check"></i> Aucun impayé échu.</div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- ─── Derniers versements, avec leurs actions (exigence 6) ─── --}}
    <div class="cb-card">
        <div class="cb-card-head">
            <span class="cb-card-t">Derniers versements</span>
            @if($cbPeutVoir)<a href="{{ route('esbtp.paiements.index') }}" class="cb-card-lnk">Liste complète →</a>@endif
        </div>
        @if($recentPaiements === null)
            <div class="cb-warn"><i class="fas fa-triangle-exclamation"></i> Liste momentanément indisponible.</div>
        @elseif($recentPaiements->isEmpty())
            <div class="cb-empty">Aucun versement enregistré cette année.</div>
        @else
        <div class="cb-table-wrap">
            <table class="cb-table">
                <thead><tr><th>Reçu</th><th>Étudiant</th><th>Frais · mode</th><th>Statut</th><th style="text-align:right">Montant</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                @foreach($recentPaiements as $paiement)
                    @php
                        $estAvoir = ($paiement->nature ?? null) === 'avoir';
                        [$stCls, $stTxt] = $estAvoir ? ['cb-st--av', 'Avoir'] : match ($paiement->status) {
                            'validé' => ['cb-st--ok', 'Validé'],
                            'en_attente' => ['cb-st--wait', 'À valider'],
                            'rejeté' => ['cb-st--no', 'Rejeté'],
                            default => ['cb-st--av', ucfirst((string) $paiement->status)],
                        };
                        $etu = $paiement->etudiant ?? $paiement->inscription?->etudiant;
                    @endphp
                    <tr>
                        <td class="cb-num" style="color:#64748b;font-weight:600;white-space:nowrap">{{ $paiement->numero_recu ?? '—' }}</td>
                        <td><b style="color:#0f172a">{{ $etu ? trim(($etu->nom ?? '').' '.($etu->prenoms ?? '')) : '—' }}</b><div class="cb-sub">{{ $paiement->inscription?->classe?->name }}</div></td>
                        <td style="color:#475569">{{ $paiement->fraisCategory->name ?? $paiement->motif ?? '—' }}<div class="cb-sub">{{ \App\Enums\ModePaiement::fromLegacy((string) $paiement->mode_paiement)?->label() ?? $paiement->mode_paiement }}</div></td>
                        <td><span class="cb-st {{ $stCls }}">{{ $stTxt }}</span></td>
                        <td class="cb-amt cb-num">{{ $estAvoir && $paiement->avoir_kind === 'refund' ? '− ' : '' }}{{ $cbFmt($paiement->montant) }} FCFA</td>
                        <td class="cb-act">@include('esbtp.paiements.partials.actions-versement', ['paiement' => $paiement, 'retour' => '/dashboard', 'avSuffixe' => '-cb'])</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ─── Raccourcis : seulement ce que le rôle peut ouvrir (exigence 5) ─── --}}
    <div class="cb-links">
        @if($cbPeutVoir)
            <a href="{{ route('esbtp.paiements.index') }}" class="cb-link"><i class="fas fa-money-bill-wave"></i> Paiements</a>
            <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="cb-link"><i class="fas fa-chart-pie"></i> Suivi par frais</a>
        @endif
        @can('frais.view')<a href="{{ route('esbtp.frais.index') }}" class="cb-link"><i class="fas fa-tags"></i> Frais</a>@endcan
        @if($cbPeutRelancer)<a href="{{ route('esbtp.comptabilite.relances.index') }}" class="cb-link"><i class="fas fa-bell"></i> Relances</a>@endif
        @can('comptabilite.reconciliation.view')<a href="{{ route('esbtp.comptabilite.reconciliation.index') }}" class="cb-link"><i class="fas fa-scale-balanced"></i> Réconciliation</a>@endcan
    </div>
</div>
@endsection

@if($compta && count($compta['labelsMois']) > 0)
@push('scripts')
@php
    $cbGraph = [
        'labels' => $compta['labelsMois'],
        'actuel' => $compta['dataEncaissements'],
        'precedent' => $compta['dataEncaissementsPrecedente'],
        'libActuel' => $anneeEnCours ? (string) ($anneeEnCours->name ?? $anneeEnCours->libelle) : 'Cette année',
        'libPrecedent' => (string) ($compta['labelAnneePrecedente'] ?? ''),
    ];
@endphp
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const d = @json($cbGraph);
    const el = document.getElementById('cbChart');
    if (!el || typeof Chart === 'undefined') return;
    const ctx = el.getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, 'rgba(4,83,203,0.22)');
    grad.addColorStop(1, 'rgba(4,83,203,0)');
    const fmt = (n) => new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FCFA';
    const sets = [{
        label: d.libActuel, data: d.actuel, borderColor: '#0453cb', backgroundColor: grad,
        fill: true, tension: 0.35, borderWidth: 2.6, pointRadius: 0, pointHoverRadius: 5,
        pointHoverBackgroundColor: '#fff', pointHoverBorderColor: '#0453cb', pointHoverBorderWidth: 2.5,
    }];
    if (d.precedent && d.precedent.length) {
        sets.push({ label: d.libPrecedent, data: d.precedent, borderColor: '#94a3b8', borderDash: [5, 5],
            borderWidth: 1.8, fill: false, tension: 0.35, pointRadius: 0, pointHoverRadius: 3 });
    }
    new Chart(ctx, {
        type: 'line',
        data: { labels: d.labels, datasets: sets },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', titleColor: '#94a3b8', bodyColor: '#fff', padding: 10, cornerRadius: 10,
                    callbacks: { label: (i) => ' ' + i.dataset.label + ' : ' + fmt(i.raw) } },
            },
            scales: {
                y: { beginAtZero: true, border: { display: false }, grid: { color: '#eef2f7' },
                    ticks: { color: '#94a3b8', callback: (v) => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v) } },
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#94a3b8' } },
            },
        },
    });
})();
</script>
@endpush
@endif
