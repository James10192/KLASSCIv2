@extends('layouts.app')

@section('title', 'Tableau de bord Caisse')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ═══════════ Accueil caisse (bureau) — namespace cx- — rule premium-dashboard ═══════════ */
    .cx-wrap { padding: 1.5rem; max-width: 1400px; margin: 0 auto; }
    .cx-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 1.75rem 2rem 1.5rem; color: #fff; margin-bottom: 1.25rem; }
    .cx-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .cx-hero-left { display: flex; align-items: center; gap: 1rem; }
    .cx-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; }
    .cx-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .cx-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: .15rem 0 0; }
    .cx-hero-meta { display: flex; flex-direction: column; align-items: flex-end; gap: .4rem; }
    .cx-chip { display: inline-flex; align-items: center; gap: .35rem; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2); border-radius: 999px; padding: .28rem .7rem; font-size: .76rem; font-weight: 600; color: #fff; white-space: nowrap; }
    .cx-chip--on::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.25); }
    .cx-chip--off::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: rgba(255,255,255,.55); }
    .cx-date { font-size: .8rem; color: rgba(255,255,255,.7); }
    .cx-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .75rem; margin-top: 1.4rem; }
    .cx-kpi { display: block; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.16); border-radius: 12px; padding: .9rem 1rem; color: #fff; text-decoration: none; transition: background .2s ease, border-color .2s ease; }
    a.cx-kpi[href]:hover { background: rgba(255,255,255,.17); border-color: rgba(255,255,255,.3); color: #fff; }
    .cx-kpi-l { font-size: .72rem; color: rgba(255,255,255,.72); text-transform: uppercase; letter-spacing: .4px; font-weight: 600; display: flex; align-items: center; gap: .4rem; }
    .cx-kpi-v { font-size: clamp(1.15rem, 1.6vw, 1.5rem); font-weight: 700; white-space: nowrap; margin-top: .3rem; }
    .cx-kpi-v small { font-size: .7rem; font-weight: 600; opacity: .7; margin-left: .2rem; }
    .cx-kpi-r { font-size: .74rem; margin-top: .25rem; color: rgba(255,255,255,.75); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cx-up { color: #a7f3d0; font-weight: 700; } .cx-down { color: #fecaca; font-weight: 700; }

    .cx-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .75rem; margin-bottom: 1.25rem; }
    .cx-act { display: flex; align-items: center; gap: .75rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: .85rem 1rem; text-decoration: none; box-shadow: 0 1px 3px rgba(15,23,42,.04); transition: border-color .2s ease, box-shadow .2s ease; }
    .cx-act:hover { border-color: #b8cdee; box-shadow: 0 8px 26px rgba(4,83,203,.08); }
    .cx-act-ic { width: 38px; height: 38px; border-radius: 10px; background: rgba(4,83,203,.08); color: #0453cb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .cx-act--main .cx-act-ic { background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; }
    .cx-act-t { font-weight: 700; color: #1e293b; font-size: .9rem; }
    .cx-act-d { font-size: .76rem; color: #64748b; }

    .cx-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.25fr); gap: 1.25rem; margin-bottom: 1.25rem; }
    .cx-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .cx-card-hd { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #eef2f7; }
    .cx-card-hd-l { display: flex; align-items: center; gap: .65rem; }
    .cx-card-ic { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .85rem; }
    .cx-card-t { font-weight: 700; color: #1e293b; font-size: .95rem; }
    .cx-card-s { font-size: .76rem; color: #64748b; }
    .cx-card-lnk { font-size: .8rem; font-weight: 600; color: #0453cb; text-decoration: none; white-space: nowrap; }
    .cx-card-bd { padding: 1rem 1.25rem; }

    .cx-todo { display: flex; align-items: center; gap: .8rem; padding: .75rem .85rem; border: 1px solid #e2e8f0; border-radius: 11px; text-decoration: none; margin-bottom: .6rem; transition: border-color .2s ease, background .2s ease; }
    .cx-todo:hover { border-color: #b8cdee; background: #f8fbff; }
    .cx-todo-ic { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: rgba(4,83,203,.08); color: #0453cb; }
    .cx-todo--warn .cx-todo-ic { background: rgba(245,158,11,.12); color: #b45309; }
    .cx-todo-t { font-weight: 700; color: #1e293b; font-size: .86rem; }
    .cx-todo-d { font-size: .76rem; color: #64748b; }
    .cx-todo-go { margin-left: auto; color: #94a3b8; }
    .cx-ok { display: flex; align-items: center; gap: .75rem; padding: 1rem; border-radius: 11px; background: rgba(16,185,129,.07); color: #065f46; font-size: .86rem; }
    .cx-ok i { font-size: 1.2rem; color: #10b981; }

    .cx-chart { display: grid; grid-template-columns: repeat(7, 1fr); gap: .5rem; align-items: end; height: 170px; padding-top: .5rem; }
    .cx-bar-col { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: .35rem; }
    .cx-bar { width: 100%; max-width: 46px; border-radius: 8px 8px 3px 3px; background: linear-gradient(180deg, #3b7ddb, #0453cb); min-height: 3px; opacity: .85; }
    .cx-bar--today { opacity: 1; box-shadow: 0 6px 16px rgba(4,83,203,.25); }
    .cx-bar-v { font-size: .66rem; color: #475569; font-weight: 700; white-space: nowrap; }
    .cx-bar-l { font-size: .7rem; color: #64748b; text-transform: capitalize; white-space: nowrap; }
    .cx-chart-foot { display: flex; justify-content: space-between; margin-top: .9rem; padding-top: .75rem; border-top: 1px dashed #e2e8f0; font-size: .8rem; color: #64748b; }
    .cx-chart-foot strong { color: #1e293b; }

    .cx-modes { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
    .cx-mode { border: 1px solid #e2e8f0; border-radius: 11px; padding: .8rem .9rem; }
    .cx-mode-l { font-size: .74rem; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: .4rem; }
    .cx-mode-v { font-size: 1.05rem; font-weight: 700; color: #1e293b; white-space: nowrap; margin-top: .2rem; }
    .cx-mode-n { font-size: .72rem; color: #94a3b8; }
    .cx-mode-bar { height: 5px; border-radius: 99px; background: #eef2f7; margin-top: .55rem; overflow: hidden; }
    .cx-mode-bar span { display: block; height: 100%; background: #0453cb; border-radius: 99px; }

    .cx-table { width: 100%; border-collapse: collapse; }
    .cx-table th { font-size: .7rem; text-transform: uppercase; letter-spacing: .4px; color: #64748b; font-weight: 700; padding: .6rem 1.25rem; background: #f8fafc; border-bottom: 1px solid #eef2f7; text-align: left; white-space: nowrap; }
    .cx-table td { padding: .7rem 1.25rem; border-bottom: 1px solid #f1f5f9; font-size: .85rem; color: #1e293b; vertical-align: middle; }
    .cx-table tr:last-child td { border-bottom: 0; }
    .cx-who { display: flex; align-items: center; gap: .65rem; }
    .cx-av { width: 34px; height: 34px; border-radius: 50%; background: rgba(4,83,203,.1); color: #0453cb; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .72rem; flex-shrink: 0; }
    .cx-who-n { font-weight: 700; } .cx-who-s { font-size: .74rem; color: #64748b; }
    .cx-amount { font-weight: 700; white-space: nowrap; text-align: right; }
    .cx-pill { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 700; padding: .18rem .55rem; border-radius: 99px; white-space: nowrap; }
    .cx-pill--ok { background: rgba(16,185,129,.1); color: #047857; }
    .cx-pill--wait { background: rgba(245,158,11,.12); color: #b45309; }
    .cx-pill--bad { background: rgba(220,38,38,.1); color: #b91c1c; }
    .cx-pill--info { background: rgba(4,83,203,.08); color: #0453cb; }
    .cx-empty { text-align: center; padding: 2rem 1rem; color: #64748b; font-size: .86rem; }
    .cx-empty-ic { width: 52px; height: 52px; border-radius: 14px; background: rgba(4,83,203,.08); color: #0453cb; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: .6rem; }
    .cx-warn-strip { display: flex; gap: .6rem; align-items: center; background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.3); color: #92400e; border-radius: 11px; padding: .7rem 1rem; margin-bottom: 1rem; font-size: .85rem; }

    @@media (max-width: 992px) { .cx-grid { grid-template-columns: 1fr; } }
    @@media (max-width: 768px) { .cx-wrap { padding: 1rem; } .cx-hero { padding: 1.25rem; } .cx-modes { grid-template-columns: 1fr; } .cx-hero-meta { align-items: flex-start; } }

    /* ═══════════ Accueil caisse — écran mobile du shell (namespace cxm-) ═══════════ */
    .cxm-screen { font-family: var(--m-font); }
    .cxm-hero-note { font-size: 12px; opacity: .85; margin: 0; }
    .cxm-hero-note a { color: #fff; font-weight: 700; text-decoration: underline; }
    .cxm-liens .m-row .av.ic { background: rgba(4,83,203,.08); color: #0453cb; }
</style>
@endpush

@section('content')
@php
    $peutVoirPaiements = auth()->user()?->canany(['paiements.view', 'paiements.view_own']) ?? false;
    $preInscriptionOuverte = app(\App\Services\TenantScolariteSettings::class)->cashierPreEnrollmentEnabled();
    // Shell mobile actif : le DOM de bureau reste rendu dans .m-only-desktop et
    // l'ecran mobile (m-*) est ajoute a cote. Shell coupe : rien ne change.
    $cxShellMobile = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);
    // « Ma caisse » : la route exige cash_session.manage OU module.caisse.access.
    $cxPeutMaCaisse = auth()->user()?->canany(['cash_session.manage', 'module.caisse.access']) ?? false;
@endphp

@php
    $cxJ = $caisseMobile ?? \App\Domain\Comptabilite\TableauDeBord\IndicateursDeCaisse::journeeVide($user);
    $cxFmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $cxVariation = \App\Domain\Comptabilite\TableauDeBord\IndicateursDeCaisse::variation((float) $cxJ['total'], (float) $cxJ['hier']);
    $cxAujourdhui = now()->toDateString();
    $cxLienJour = $peutVoirPaiements ? route('esbtp.paiements.index', ['date_debut' => $cxAujourdhui, 'date_fin' => $cxAujourdhui]) : null;
    $cxLienAValider = $peutVoirPaiements ? route('esbtp.paiements.index', ['status' => 'en_attente']) : null;
    $cxSession = $cxJ['session']['statut'] ?? null;
    $cxSerie = $serieSemaine ?? [];
    $cxMax = max(1, collect($cxSerie)->max('total') ?? 0);
    $cxTotalSemaine = collect($cxSerie)->sum('total');
    $cxModes = [
        ['cle' => 'especes', 'label' => 'Espèces', 'ic' => 'fa-money-bill-wave'],
        ['cle' => 'mobile', 'label' => 'Mobile money', 'ic' => 'fa-mobile-screen'],
        ['cle' => 'autres', 'label' => 'Virement, chèque…', 'ic' => 'fa-building-columns'],
    ];
    $cxTotalModes = max(1, $cxJ['especes']['total'] + $cxJ['mobile']['total'] + $cxJ['autres']['total']);

    // File de travail : ce qui attend l'agent, du plus urgent au moins urgent.
    $cxTodo = [];
    if ($cxPeutMaCaisse && $cxSession === null && (auth()->user()?->can('paiements.create') ?? false)) {
        $cxTodo[] = ['ton' => 'warn', 'ic' => 'fa-lock-open', 't' => "Votre caisse n'est pas ouverte", 'd' => 'Le premier encaissement en espèces l\'ouvre, ou ouvrez-la maintenant.', 'href' => route('esbtp.caisse.ma-caisse')];
    }
    if ($cxJ['annulables'] > 0) {
        $cxTodo[] = ['ton' => 'warn', 'ic' => 'fa-rotate-left', 't' => $cxJ['annulables'].' saisie'.($cxJ['annulables'] > 1 ? 's' : '').' encore annulable'.($cxJ['annulables'] > 1 ? 's' : ''), 'd' => 'Une erreur ? Vous avez '.$cxJ['fenetre_annulation_minutes'].' minutes après la saisie pour l\'annuler vous-même.', 'href' => '#cx-derniers'];
    }
    if ($cxJ['a_valider'] > 0) {
        $cxTodo[] = ['ton' => 'warn', 'ic' => 'fa-hourglass-half', 't' => $cxJ['a_valider'].' versement'.($cxJ['a_valider'] > 1 ? 's' : '').' en attente de validation', 'd' => $cxFmt($cxJ['a_valider_total']).' FCFA saisis aujourd\'hui, en attente d\'un validateur.', 'href' => $cxLienAValider];
    }
    if ($preInscriptionOuverte && $preInscriptionsEnAttente > 0 && (auth()->user()?->can('inscriptions.view') ?? false)) {
        $cxTodo[] = ['ton' => 'info', 'ic' => 'fa-user-clock', 't' => $preInscriptionsEnAttente.' pré-inscription'.($preInscriptionsEnAttente > 1 ? 's' : '').' à finaliser', 'd' => 'Dossiers ouverts à la caisse, en attente du secrétariat.', 'href' => route('esbtp.inscriptions.index', ['status' => 'en_attente'])];
    }
    if ($cxSession === 'open') {
        $cxTodo[] = ['ton' => 'info', 'ic' => 'fa-vault', 't' => 'Caisse ouverte depuis '.($cxJ['session']['ouverte_a'] ?? '—'), 'd' => 'Pensez à compter et clôturer en fin de journée.', 'href' => route('esbtp.caisse.ma-caisse')];
    }
@endphp

<div class="dashboard-acasi {{ $cxShellMobile ? 'm-only-desktop' : '' }}">
<div class="cx-wrap">

    {{-- ─── HERO + KPIs (chaque chiffre a son repère et son lien) ─── --}}
    <div class="cx-hero">
        <div class="cx-hero-top">
            <div class="cx-hero-left">
                <div class="cx-hero-icon"><i class="fas fa-cash-register"></i></div>
                <div>
                    <h1>Ma caisse du jour</h1>
                    <p>Bonjour {{ $user->name }} — voici où en est votre guichet.</p>
                </div>
            </div>
            <div class="cx-hero-meta">
                <span class="cx-chip {{ $cxSession === 'open' ? 'cx-chip--on' : 'cx-chip--off' }}">
                    @if($cxSession === 'open') Caisse ouverte à {{ $cxJ['session']['ouverte_a'] }}
                    @elseif(in_array($cxSession, ['closed', 'auto_closed'], true)) Caisse clôturée à {{ $cxJ['session']['fermee_a'] }}
                    @else Caisse non ouverte @endif
                </span>
                <span class="cx-date">{{ ucfirst(\Carbon\Carbon::now()->isoFormat('dddd D MMMM YYYY')) }} · {{ $anneeEnCours->name ?? 'Année non définie' }}</span>
            </div>
        </div>

        <div class="cx-kpis">
            <a class="cx-kpi" @if($cxLienJour) href="{{ $cxLienJour }}" @endif>
                <div class="cx-kpi-l"><i class="fas fa-coins"></i> Encaissé validé aujourd'hui</div>
                <div class="cx-kpi-v">{{ $indisponible ? '—' : $cxFmt($cxJ['total']) }}<small>FCFA</small></div>
                <div class="cx-kpi-r">
                    @if($indisponible) Indisponible pour le moment
                    @elseif($cxVariation === null) Hier : {{ $cxFmt($cxJ['hier']) }} FCFA
                    @else <span class="{{ $cxVariation >= 0 ? 'cx-up' : 'cx-down' }}">{{ $cxVariation >= 0 ? '▲ +' : '▼ ' }}{{ number_format($cxVariation, 1, ',', ' ') }} %</span> vs hier ({{ $cxFmt($cxJ['hier']) }})
                    @endif
                </div>
            </a>
            <a class="cx-kpi" @if($cxLienJour) href="{{ $cxLienJour }}" @endif>
                <div class="cx-kpi-l"><i class="fas fa-receipt"></i> Saisies du jour</div>
                <div class="cx-kpi-v">{{ $cxJ['count'] + $cxJ['a_valider'] }}</div>
                <div class="cx-kpi-r">{{ $cxJ['count'] }} validée{{ $cxJ['count'] > 1 ? 's' : '' }} · {{ $cxJ['a_valider'] }} à valider</div>
            </a>
            <a class="cx-kpi" @if($cxLienAValider) href="{{ $cxLienAValider }}" @endif>
                <div class="cx-kpi-l"><i class="fas fa-hourglass-half"></i> À valider</div>
                <div class="cx-kpi-v">{{ $cxJ['a_valider'] }}</div>
                <div class="cx-kpi-r">{{ $cxJ['a_valider'] > 0 ? $cxFmt($cxJ['a_valider_total']).' FCFA pas encore comptés' : 'tout est validé' }}</div>
            </a>
            <div class="cx-kpi">
                <div class="cx-kpi-l"><i class="fas fa-chart-column"></i> Sept derniers jours</div>
                <div class="cx-kpi-v">{{ $cxFmt($cxTotalSemaine) }}<small>FCFA</small></div>
                <div class="cx-kpi-r">moyenne {{ $cxFmt($cxTotalSemaine / max(1, count($cxSerie))) }} FCFA / jour</div>
            </div>
        </div>
    </div>

    @if($indisponible)
        <div class="cx-warn-strip"><i class="fas fa-triangle-exclamation"></i> Les chiffres de la journée n'ont pas pu être calculés. Les versements restent consultables dans la liste ; l'incident est signalé au support.</div>
    @endif

    {{-- ─── ACTIONS : uniquement ce que le rôle peut ouvrir ─── --}}
    <div class="cx-actions">
        @can('paiements.create')
        <a href="{{ route('esbtp.paiements.create') }}" class="cx-act cx-act--main">
            <div class="cx-act-ic"><i class="fas fa-plus"></i></div>
            <div><div class="cx-act-t">Encaisser</div><div class="cx-act-d">Enregistrer un versement</div></div>
        </a>
        @endcan
        @if($preInscriptionOuverte)
        @can('inscriptions.create')
        <a href="{{ route('esbtp.inscriptions.pre-inscription') }}" class="cx-act">
            <div class="cx-act-ic"><i class="fas fa-user-plus"></i></div>
            <div><div class="cx-act-t">Pré-inscrire</div><div class="cx-act-d">Ouvrir un dossier étudiant</div></div>
        </a>
        @endcan
        @endif
        @if($cxPeutMaCaisse)
        <a href="{{ route('esbtp.caisse.ma-caisse') }}" class="cx-act">
            <div class="cx-act-ic"><i class="fas fa-vault"></i></div>
            <div><div class="cx-act-t">Ma caisse</div><div class="cx-act-d">Compter et clôturer</div></div>
        </a>
        @endif
        @if($peutVoirPaiements)
        <a href="{{ route('esbtp.paiements.index') }}" class="cx-act">
            <div class="cx-act-ic"><i class="fas fa-list"></i></div>
            <div><div class="cx-act-t">Les versements</div><div class="cx-act-d">@can('paiements.view')Tous les versements @else Ceux que j'ai saisis @endcan</div></div>
        </a>
        @endif
        @can('comptabilite.access')
        @can('comptabilite.journal.view')
        <a href="{{ route('esbtp.comptabilite.journal-caisse.index') }}" class="cx-act">
            <div class="cx-act-ic"><i class="fas fa-book"></i></div>
            <div><div class="cx-act-t">Journal de caisse</div><div class="cx-act-d">Point du jour détaillé</div></div>
        </a>
        @endcan
        @endcan
    </div>

    {{-- ─── FILE DE TRAVAIL + TENDANCE ─── --}}
    <div class="cx-grid">
        <div class="cx-card">
            <div class="cx-card-hd">
                <div class="cx-card-hd-l">
                    <div class="cx-card-ic"><i class="fas fa-list-check"></i></div>
                    <div><div class="cx-card-t">À faire</div><div class="cx-card-s">Ce qui attend votre guichet</div></div>
                </div>
            </div>
            <div class="cx-card-bd">
                @forelse($cxTodo as $t)
                    <a href="{{ $t['href'] ?? '#' }}" class="cx-todo cx-todo--{{ $t['ton'] }}">
                        <div class="cx-todo-ic"><i class="fas {{ $t['ic'] }}"></i></div>
                        <div><div class="cx-todo-t">{{ $t['t'] }}</div><div class="cx-todo-d">{{ $t['d'] }}</div></div>
                        <i class="fas fa-chevron-right cx-todo-go"></i>
                    </a>
                @empty
                    <div class="cx-ok"><i class="fas fa-circle-check"></i><div><strong>Rien en attente.</strong> Contrôlé à {{ now()->format('H:i') }}.</div></div>
                @endforelse
            </div>
        </div>

        <div class="cx-card">
            <div class="cx-card-hd">
                <div class="cx-card-hd-l">
                    <div class="cx-card-ic"><i class="fas fa-chart-column"></i></div>
                    <div><div class="cx-card-t">Mes encaissements, 7 derniers jours</div><div class="cx-card-s">Montant net (remboursements déduits)</div></div>
                </div>
            </div>
            <div class="cx-card-bd">
                @if(count($cxSerie) === 0)
                    <div class="cx-empty">Tendance indisponible.</div>
                @else
                    <div class="cx-chart" role="img" aria-label="Encaissements des sept derniers jours">
                        @foreach($cxSerie as $point)
                            @php $h = $point['total'] > 0 ? max(4, round($point['total'] / $cxMax * 130)) : 3; @endphp
                            <div class="cx-bar-col" title="{{ ucfirst($point['libelle']) }} : {{ $cxFmt($point['total']) }} FCFA">
                                <span class="cx-bar-v">{{ $point['total'] > 0 ? ($point['total'] >= 1000000 ? number_format($point['total'] / 1000000, 1, ',', ' ').' M' : $cxFmt(round($point['total'] / 1000)).' k') : '' }}</span>
                                <div class="cx-bar {{ $loop->last ? 'cx-bar--today' : '' }}" style="height: {{ $h }}px;"></div>
                                <span class="cx-bar-l">{{ $loop->last ? 'auj.' : $point['libelle'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="cx-chart-foot">
                        <span>Total : <strong>{{ $cxFmt($cxTotalSemaine) }} FCFA</strong></span>
                        <span>Meilleur jour : <strong>{{ $cxFmt($cxMax > 1 ? $cxMax : 0) }} FCFA</strong></span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ─── RÉPARTITION DU JOUR ─── --}}
    <div class="cx-card" style="margin-bottom:1.25rem;">
        <div class="cx-card-hd">
            <div class="cx-card-hd-l">
                <div class="cx-card-ic"><i class="fas fa-wallet"></i></div>
                <div><div class="cx-card-t">Aujourd'hui, par moyen de paiement</div><div class="cx-card-s">Ce que vous devez retrouver en caisse et sur les comptes</div></div>
            </div>
            @if($cxPeutMaCaisse)<a href="{{ route('esbtp.caisse.ma-caisse') }}" class="cx-card-lnk">Compter ma caisse <i class="fas fa-arrow-right ms-1"></i></a>@endif
        </div>
        <div class="cx-card-bd">
            <div class="cx-modes">
                @foreach($cxModes as $m)
                    @php $bloc = $cxJ[$m['cle']]; @endphp
                    <div class="cx-mode">
                        <div class="cx-mode-l"><i class="fas {{ $m['ic'] }}"></i> {{ $m['label'] }}</div>
                        <div class="cx-mode-v">{{ $cxFmt($bloc['total']) }} FCFA</div>
                        <div class="cx-mode-n">{{ $bloc['count'] }} versement{{ $bloc['count'] > 1 ? 's' : '' }}</div>
                        <div class="cx-mode-bar"><span style="width: {{ round($bloc['total'] / $cxTotalModes * 100) }}%"></span></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ─── DERNIERS VERSEMENTS, avec leurs actions ─── --}}
    <div class="cx-card" id="cx-derniers">
        <div class="cx-card-hd">
            <div class="cx-card-hd-l">
                <div class="cx-card-ic"><i class="fas fa-clock-rotate-left"></i></div>
                <div><div class="cx-card-t">Mes derniers versements</div><div class="cx-card-s">Voir, reçu, annuler ma saisie ou annuler le versement, sans quitter l'accueil</div></div>
            </div>
            @if($peutVoirPaiements)
                <a href="{{ route('esbtp.paiements.index') }}" class="cx-card-lnk">Tout voir <i class="fas fa-arrow-right ms-1"></i></a>
            @endif
        </div>
        @if($paiementsRecents->isEmpty())
            <div class="cx-empty">
                <div class="cx-empty-ic"><i class="fas fa-receipt"></i></div>
                <div><strong>Aucun versement pour l'instant.</strong></div>
                <div>
                    @can('paiements.create')
                        Le premier encaissement apparaîtra ici. <a href="{{ route('esbtp.paiements.create') }}">Encaisser maintenant</a>
                    @else
                        Les versements que vous enregistrez apparaîtront ici.
                    @endcan
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="cx-table">
                    <thead><tr><th>Étudiant</th><th>Reçu</th><th>Mode</th><th>Statut</th><th style="text-align:right;">Montant</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>
                    @foreach($paiementsRecents as $paiement)
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
                                <div class="cx-who">
                                    <div class="cx-av">{{ $ini }}</div>
                                    <div>
                                        <div class="cx-who-n">{{ $nom }}</div>
                                        <div class="cx-who-s">{{ $paiement->inscription?->classe?->name ?? '—' }} · {{ $paiement->fraisCategory?->name ?? 'Versement' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="white-space:nowrap;">{{ $paiement->numero_recu ?? '—' }}<div class="cx-who-s">{{ optional($paiement->created_at)->format('d/m H:i') }}</div></td>
                            <td>{{ $paiement->mode_paiement ? ucfirst(str_replace('_', ' ', $paiement->mode_paiement)) : '—' }}</td>
                            <td><span class="cx-pill cx-pill--{{ $pTon }}">{{ $pLib }}</span></td>
                            <td class="cx-amount">{{ $paiement->isAvoir() ? '−' : '' }}{{ $cxFmt($paiement->montant) }} F</td>
                            <td style="text-align:right;">
                                @include('esbtp.paiements.partials.actions-versement', ['paiement' => $paiement, 'retour' => '/dashboard', 'avSuffixe' => '-cx'])
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
</div>

@if($cxShellMobile)
@php
    // ---------- Écran mobile (maquette S['caissier:accueil']) ----------
    $cxmEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $cxmNomEcole = trim((string) ($cxmEcole['name'] ?? '')) !== '' ? $cxmEcole['name'] : ($cxmEcole['acronym'] ?? config('app.name'));
    $cxmSousTitre = ucfirst(\Carbon\Carbon::now()->isoFormat('dddd D MMMM')) . ' · ' . $user->name;

    $cxm = $caisseMobile ?? [];
    $cxmSession = $cxm['session'] ?? ['statut' => null, 'ouverte_a' => null, 'fermee_a' => null];
    $cxmEtatSession = match ($cxmSession['statut'] ?? null) {
        'open' => 'session ouverte' . ($cxmSession['ouverte_a'] ? ' ' . $cxmSession['ouverte_a'] : ''),
        'closed', 'auto_closed' => 'caisse clôturée' . ($cxmSession['fermee_a'] ? ' ' . $cxmSession['fermee_a'] : ''),
        default => 'caisse non ouverte',
    };
    $cxmEspeces = $cxm['especes'] ?? ['count' => 0, 'total' => 0.0];
    $cxmMobile = $cxm['mobile'] ?? ['count' => 0, 'total' => 0.0];
    $cxmAutres = $cxm['autres'] ?? ['count' => 0, 'total' => 0.0];
    $cxmAValider = (int) ($cxm['a_valider'] ?? 0);
    $cxmAnnulables = (int) ($cxm['annulables'] ?? 0);
    $cxmFenetre = (int) ($cxm['fenetre_annulation_minutes'] ?? 0);
    $cxmPeutAnnuler = (bool) ($cxm['peut_annuler'] ?? false);
    $cxmFmt = fn ($n) => number_format((float) $n, 0, ',', ' ');

    $cxmPills = [
        $paiementsAujourdhuiCount . ' ' . ($paiementsAujourdhuiCount > 1 ? 'opérations' : 'opération'),
        'Espèces ' . $cxmEspeces['count'] . ' · Mobile ' . $cxmMobile['count'],
    ];
    if ($cxmAutres['count'] > 0) {
        $cxmPills[] = 'Autres modes ' . $cxmAutres['count'];
    }
    if ($preInscriptionOuverte && $preInscriptionsAujourdhui > 0) {
        $cxmPills[] = $preInscriptionsAujourdhui . ' pré-inscription' . ($preInscriptionsAujourdhui > 1 ? 's' : '');
    }

    $cxmKpis = [
        ['value' => (string) $cxmEspeces['count'], 'label' => 'Espèces', 'delta' => $cxmFmt($cxmEspeces['total']) . ' FCFA', 'tone' => 'ok'],
        ['value' => (string) $cxmMobile['count'], 'label' => 'Mobile money', 'delta' => $cxmFmt($cxmMobile['total']) . ' FCFA', 'tone' => 'info'],
        ['value' => (string) $cxmAValider, 'label' => 'À valider', 'delta' => $cxmAValider > 0 ? 'en attente' : 'tout est validé', 'tone' => $cxmAValider > 0 ? 'warn' : 'mute'],
    ];
    if ($cxmPeutAnnuler) {
        $cxmKpis[] = ['value' => (string) $cxmAnnulables, 'label' => 'Annulables · ' . $cxmFenetre . ' min', 'delta' => $cxmAnnulables > 0 ? 'fenêtre ouverte' : 'aucune', 'tone' => $cxmAnnulables > 0 ? 'warn' : 'mute'];
    } else {
        $cxmKpis[] = ['value' => (string) $paiementsAujourdhuiCount, 'label' => 'Versements du jour', 'delta' => $anneeEnCours->name ?? null, 'tone' => 'mute'];
    }

    $cxmDernieres = $paiementsRecents->take(5);
@endphp

<div class="m-only-mobile m-screen cxm-screen">

    <x-m.appbar :title="$cxmNomEcole . ' · Caisse'"
                :sub="$cxmSousTitre"
                :action="Route::has('notifications.index') ? 'bell' : null"
                :action-url="Route::has('notifications.index') ? route('notifications.index') : null"
                action-label="Notifications" />

    <div class="m-body" data-m-ptr="reload">

        <x-m.hero :label="'Encaissé aujourd\'hui · ' . $cxmEtatSession"
                  :value="$cxmFmt($montantEncaisseAujourdhui)"
                  unit="FCFA"
                  :pills="$cxmPills">
            @if(($cxmSession['statut'] ?? null) === null && $cxPeutMaCaisse)
                <p class="cxm-hero-note">La caisse s'ouvre au premier encaissement en espèces, ou depuis <a href="{{ route('esbtp.caisse.ma-caisse') }}">Ma caisse</a>.</p>
            @endif
        </x-m.hero>

        <x-m.kpi :items="$cxmKpis" />

        <div class="m-sec">
            <b>Dernières opérations</b>
            @if($peutVoirPaiements)
                <a href="{{ route('esbtp.paiements.index') }}">Tout voir</a>
            @endif
        </div>

        @if($cxmDernieres->isEmpty())
            <x-m.empty icon="inbox" title="Aucune opération pour l'instant" text="Le premier encaissement de la journée apparaîtra ici.">
                @can('paiements.create')
                    <a href="{{ route('esbtp.paiements.create') }}" class="m-btn g"><x-m.icon name="plus" />Encaisser</a>
                @endcan
            </x-m.empty>
        @else
            <div class="m-list">
                @foreach($cxmDernieres as $cxmP)
                    @php
                        $cxmEtu = $cxmP->etudiant;
                        $cxmNom = $cxmEtu ? trim(($cxmEtu->nom ?? '') . ' ' . ($cxmEtu->prenoms ?? '')) : 'Étudiant inconnu';
                        $cxmInitiales = $cxmEtu
                            ? mb_substr($cxmEtu->nom ?? '', 0, 1, 'UTF-8') . mb_substr($cxmEtu->prenoms ?? '', 0, 1, 'UTF-8')
                            : '?';
                        $cxmSousLigne = collect([
                            $cxmP->inscription?->classe?->name,
                            $cxmP->fraisCategory?->name,
                            optional($cxmP->created_at)->format('H:i'),
                        ])->filter(fn ($v) => $v !== null && $v !== '')->implode(' · ');
                        $cxmCanon = \App\Enums\ModePaiement::fromLegacy((string) $cxmP->mode_paiement);
                        $cxmEstAvoir = $cxmP->isAvoir();
                        if ($cxmEstAvoir) {
                            [$cxmChip, $cxmTone] = ['Avoir', 'info'];
                        } elseif ($cxmP->status === 'validé') {
                            [$cxmChip, $cxmTone] = ($cxmCanon && ! $cxmCanon->isDrawer())
                                ? [$cxmCanon->label(), 'info']
                                : ['Validé', 'ok'];
                        } elseif ($cxmP->status === 'en_attente') {
                            [$cxmChip, $cxmTone] = (auth()->user()?->can('cancelOwnRecent', $cxmP) ?? false)
                                ? ['Annulable ' . $cxmFenetre . ' min', 'warn']
                                : ['À valider', 'warn'];
                        } elseif ($cxmP->status === 'rejeté') {
                            [$cxmChip, $cxmTone] = ['Rejeté', 'bad'];
                        } else {
                            [$cxmChip, $cxmTone] = [ucfirst(str_replace('_', ' ', (string) $cxmP->status)), 'mute'];
                        }
                    @endphp
                    <x-m.row :href="$peutVoirPaiements ? route('esbtp.paiements.show', $cxmP->id) : null"
                             :av="$cxmInitiales"
                             :title="$cxmNom"
                             :sub="$cxmSousLigne !== '' ? $cxmSousLigne : null"
                             :amount="($cxmEstAvoir ? '-' : '') . $cxmFmt($cxmP->montant)"
                             :neg="$cxmEstAvoir || $cxmP->status === 'rejeté'"
                             :chip="$cxmChip"
                             :chip-type="$cxmTone" />
                @endforeach
            </div>
        @endif

        {{-- Accès rapides : chaque ligne sous la garde réelle de sa route. --}}
        @php
            $cxmPeutJournal = (auth()->user()?->can('comptabilite.access') ?? false) && (auth()->user()?->can('comptabilite.journal.view') ?? false);
            $cxmPeutReconcilier = auth()->user()?->can('comptabilite.reconciliation.open') ?? false;
            $cxmPeutPreInscrire = $preInscriptionOuverte && (auth()->user()?->can('inscriptions.create') ?? false);
            $cxmADesLiens = $cxPeutMaCaisse || $cxmPeutJournal || $cxmPeutReconcilier || $cxmPeutPreInscrire;
        @endphp
        @if($cxmADesLiens)
            <div class="m-sec"><b>Ma journée</b></div>
            <div class="m-list one cxm-liens">
                @if($cxPeutMaCaisse)
                    <x-m.row :href="route('esbtp.caisse.ma-caisse')" icon="wallet" title="Ma caisse" sub="Point du jour et clôture" />
                @endif
                @if($cxmPeutPreInscrire)
                    <x-m.row :href="route('esbtp.inscriptions.pre-inscription')" icon="user" title="Pré-inscrire" :sub="$preInscriptionsEnAttente > 0 ? $preInscriptionsEnAttente . ' en attente de validation' : 'Ouvrir un dossier étudiant'" />
                @endif
                @if($cxmPeutJournal)
                    <x-m.row :href="route('esbtp.comptabilite.journal-caisse.index')" icon="book" title="Journal de caisse" sub="Point du jour détaillé" />
                @endif
                @if($cxmPeutReconcilier)
                    <x-m.row :href="route('esbtp.comptabilite.reconciliation.create')" icon="scale" title="Réconcilier" sub="Comparer caisse et système" />
                @endif
            </div>
        @endif
    </div>

    @can('paiements.create')
        <a href="{{ route('esbtp.paiements.create') }}" class="m-fab" aria-label="Encaisser un paiement">
            <x-m.icon name="plus" />
        </a>
    @endcan
</div>
@endif
@endsection
