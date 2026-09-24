@extends('layouts.app')

@section('title', 'Tableau de bord Caisse')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ═══════════ Accueil caisse (bureau) — namespace cx- — maquette C « guichet »
       Rule premium-dashboard : le montant du jour et son repère, les gestes du
       guichet en grosses tuiles, les saisies du jour avec leurs actions. ═══════════ */
    .cx-wrap { padding: 1.5rem; max-width: 1320px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.1rem; }
    .cx-num { font-variant-numeric: tabular-nums; }
    .cx-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; }
    .cx-head-s { font-size: .82rem; color: #64748b; font-weight: 600; }
    .cx-head h1 { margin: .15rem 0 0; font-size: 1.65rem; font-weight: 800; color: #0f172a; letter-spacing: -.02em; }
    .cx-head-r { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; }
    .cx-sess { display: inline-flex; gap: .5rem; align-items: center; font-weight: 700; font-size: .82rem; padding: .5rem .9rem; border-radius: 99px; white-space: nowrap; }
    .cx-sess::before { content: ''; width: 8px; height: 8px; border-radius: 99px; background: currentColor; }
    .cx-sess--on { background: #dcfce7; color: #047857; }
    .cx-sess--off { background: #fef3c7; color: #92400e; }
    .cx-sess--done { background: #e8f0fc; color: #0453cb; }
    .cx-btn { display: inline-flex; align-items: center; gap: .45rem; font-weight: 700; font-size: .84rem; padding: .6rem 1rem; border: 1px solid #cfdcf0; border-radius: 10px; background: #fff; color: #0453cb; text-decoration: none; white-space: nowrap; }
    .cx-btn:hover { border-color: #0453cb; color: #033a8e; }

    .cx-top { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 1rem; }
    .cx-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%); border-radius: 20px; padding: 1.4rem 1.5rem; color: #fff; display: flex; gap: 1.4rem; align-items: center; min-width: 0; }
    .cx-ring { position: relative; width: 150px; height: 150px; flex-shrink: 0; }
    .cx-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
    .cx-ring-mid { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    .cx-ring-mid b { font-size: 1.5rem; font-weight: 800; }
    .cx-ring-mid small { font-size: .68rem; color: rgba(255,255,255,.78); max-width: 100px; line-height: 1.2; }
    .cx-hero-b { display: flex; flex-direction: column; gap: .45rem; min-width: 0; flex: 1; }
    .cx-hero-l { font-size: .74rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: rgba(255,255,255,.75); }
    .cx-big { display: flex; align-items: baseline; gap: .45rem; white-space: nowrap; }
    .cx-big b { font-size: clamp(1.8rem, 3.2vw, 2.5rem); font-weight: 800; letter-spacing: -.03em; }
    .cx-big small { font-weight: 700; color: rgba(255,255,255,.72); }
    .cx-hero-r { font-size: .82rem; color: rgba(255,255,255,.85); }
    .cx-modes { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; margin-top: .35rem; }
    .cx-mode { background: rgba(255,255,255,.12); border-radius: 12px; padding: .55rem .65rem; min-width: 0; }
    .cx-mode span { display: block; font-size: .68rem; font-weight: 700; color: rgba(255,255,255,.72); }
    .cx-mode b { font-size: .95rem; font-weight: 800; white-space: nowrap; }

    .cx-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
    .cx-tile { border-radius: 18px; padding: 1rem 1.1rem; display: flex; flex-direction: column; justify-content: space-between; gap: .35rem; min-height: 112px; text-decoration: none; background: #fff; border: 1px solid #e2e8f0; color: #0f172a; transition: border-color .2s ease, box-shadow .2s ease; }
    a.cx-tile[href]:hover { border-color: #b9cdee; box-shadow: 0 8px 26px rgba(4,83,203,.08); color: #0f172a; }
    .cx-tile i { font-size: 1.2rem; color: #0453cb; }
    .cx-tile b { font-size: 1rem; font-weight: 800; }
    .cx-tile small { font-size: .74rem; color: #64748b; }
    .cx-tile--main { background: #0453cb; border-color: #0453cb; color: #fff; }
    a.cx-tile--main:hover { background: #033a8e; color: #fff; }
    .cx-tile--main i, .cx-tile--main small { color: rgba(255,255,255,.85); }
    .cx-tile-n { font-size: 1.7rem; font-weight: 800; line-height: 1; }
    .cx-tile--warn { background: #fff7ed; border-color: #fde2c2; }
    .cx-tile--warn .cx-tile-n { color: #9a3412; }
    .cx-tile--info .cx-tile-n { color: #0453cb; }

    .cx-alert { display: flex; gap: .7rem; align-items: center; padding: .75rem 1rem; border-radius: 12px; background: #fff; border: 1px solid #e2e8f0; color: #1e293b; text-decoration: none; font-size: .86rem; }
    .cx-alert i { color: #0453cb; width: 18px; text-align: center; }
    .cx-alert--warn { background: #fff7ed; border-color: #fde2c2; }
    .cx-alert--warn i { color: #b45309; }
    .cx-alert b { color: #0f172a; }
    .cx-alert em { margin-left: auto; font-style: normal; font-weight: 700; color: #0453cb; white-space: nowrap; }
    .cx-alerts { display: flex; flex-direction: column; gap: .5rem; }

    .cx-bottom { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 1rem; align-items: start; }
    .cx-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 1.1rem 1.2rem; display: flex; flex-direction: column; gap: .7rem; min-width: 0; }
    .cx-card-h { display: flex; justify-content: space-between; align-items: center; gap: .6rem; flex-wrap: wrap; }
    .cx-card-t { font-size: .98rem; font-weight: 800; color: #0f172a; }
    .cx-card-s { font-size: .76rem; color: #64748b; }
    .cx-lnk { font-size: .82rem; font-weight: 700; color: #0453cb; text-decoration: none; white-space: nowrap; }
    .cx-side { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }

    .cx-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
    .cx-table td { padding: .65rem .4rem; border-top: 1px solid #f1f5f9; vertical-align: middle; }
    .cx-h { color: #64748b; font-weight: 700; white-space: nowrap; width: 1%; }
    .cx-who-n { font-weight: 700; color: #0f172a; }
    .cx-who-s { font-size: .74rem; color: #64748b; }
    .cx-amount { text-align: right; font-weight: 800; color: #0f172a; white-space: nowrap; }
    .cx-pill { font-size: .72rem; font-weight: 700; padding: .18rem .5rem; border-radius: 99px; white-space: nowrap; }
    .cx-pill--ok { background: #dcfce7; color: #047857; }
    .cx-pill--wait { background: #fef3c7; color: #92400e; }
    .cx-pill--bad { background: #fee2e2; color: #b91c1c; }
    .cx-pill--info { background: #e8f0fc; color: #0453cb; }
    .cx-left { display: inline-block; margin-top: .2rem; font-size: .7rem; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: .1rem .45rem; border-radius: 99px; }
    .cx-act { text-align: right; white-space: nowrap; width: 1%; }
    .cx-empty { text-align: center; padding: 1.5rem .5rem; color: #64748b; font-size: .88rem; }
    .cx-empty a { font-weight: 700; }

    .cx-bars { display: flex; align-items: flex-end; gap: 6px; height: 130px; }
    .cx-bars > div { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 5px; height: 100%; min-width: 0; }
    .cx-bars i { display: block; width: 100%; border-radius: 6px 6px 2px 2px; background: #bcd3f5; min-height: 3px; }
    .cx-bars i.is-now { background: #0453cb; }
    .cx-bars span { font-size: .66rem; color: #94a3b8; }
    .cx-spark { width: 100%; height: 80px; display: block; }
    .cx-axe { display: flex; justify-content: space-between; font-size: .68rem; color: #94a3b8; }
    .cx-warn-strip { display: flex; gap: .6rem; align-items: center; background: #fff7ed; border: 1px solid #fde2c2; color: #9a3412; border-radius: 11px; padding: .7rem 1rem; font-size: .85rem; }

    /* « Voir plus de détails » : la richesse de l'ancien accueil, repliée. */
    .cx-more-btn { align-self: center; display: inline-flex; align-items: center; gap: .5rem; border: 1px solid #cfdcf0; background: #fff; color: #0453cb; font-weight: 700; font-size: .86rem; padding: .6rem 1.2rem; border-radius: 99px; cursor: pointer; }
    .cx-more-btn:hover { border-color: #0453cb; }
    .cx-more-btn i { transition: transform .2s ease; }
    .cx-more-btn.is-open i { transform: rotate(180deg); }
    .cx-more { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; align-items: start; }
    .cx-todo { display: flex; align-items: center; gap: .7rem; padding: .7rem .75rem; border-radius: 12px; border: 1px solid #eef2f7; background: #f8fafc; color: #1e293b; text-decoration: none; }
    a.cx-todo:hover { border-color: #b9cdee; color: #1e293b; }
    .cx-todo-ic { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #e8f0fc; color: #0453cb; }
    .cx-todo--warn .cx-todo-ic { background: #fef3c7; color: #92400e; }
    .cx-todo-t { font-size: .84rem; font-weight: 700; color: #0f172a; }
    .cx-todo-d { font-size: .74rem; color: #64748b; }
    .cx-ok { display: flex; gap: .6rem; align-items: center; padding: .8rem; border-radius: 12px; background: #f0fdf4; color: #047857; font-size: .86rem; }
    .cx-mline { display: flex; flex-direction: column; gap: .3rem; padding: .55rem 0; border-top: 1px solid #f1f5f9; }
    .cx-mline:first-of-type { border-top: 0; }
    .cx-mline-h { display: flex; justify-content: space-between; gap: .5rem; font-size: .84rem; }
    .cx-mline-h span { color: #475569; font-weight: 600; }
    .cx-mline-h b { color: #0f172a; white-space: nowrap; }
    .cx-mline small { font-size: .72rem; color: #64748b; }
    .cx-mbar { height: 6px; background: #eef2f7; border-radius: 99px; overflow: hidden; }
    .cx-mbar > i { display: block; height: 100%; background: #0453cb; border-radius: 99px; }
    .cx-week { display: flex; align-items: flex-end; gap: 6px; height: 150px; }
    .cx-week > div { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 4px; height: 100%; min-width: 0; }
    .cx-week i { display: block; width: 100%; border-radius: 6px 6px 2px 2px; background: #bcd3f5; min-height: 3px; }
    .cx-week i.is-now { background: #0453cb; }
    .cx-week em { font-style: normal; font-size: .64rem; font-weight: 700; color: #475569; white-space: nowrap; }
    .cx-week span { font-size: .66rem; color: #94a3b8; }
    .cx-foot { display: flex; justify-content: space-between; gap: .5rem; font-size: .76rem; color: #64748b; flex-wrap: wrap; }
    .cx-foot b { color: #0f172a; }
    @@media (max-width: 1100px) { .cx-more { grid-template-columns: 1fr; } }

    @@media (max-width: 1100px) { .cx-top, .cx-bottom { grid-template-columns: 1fr; } }
    @@media (max-width: 640px) { .cx-wrap { padding: 1rem .75rem; } .cx-hero { flex-direction: column; align-items: stretch; } .cx-ring { margin: 0 auto; } .cx-modes { grid-template-columns: 1fr; } }

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

    // Repère du jour : la moyenne des jours travaillés parmi les six précédents.
    $cxSerie = $serieSemaine ?? [];
    $cxPrecedents = collect($cxSerie)->slice(0, -1)->pluck('total')->filter(fn ($v) => $v > 0);
    $cxMoyenne = $cxPrecedents->isNotEmpty() ? $cxPrecedents->avg() : null;
    $cxPart = $cxMoyenne ? $cxJ['total'] / $cxMoyenne * 100 : null;
    $cxAnneau = $cxPart === null ? 0 : min(100, $cxPart);
    $cxTotalSemaine = collect($cxSerie)->sum('total');

    // Courbe des sept jours (SVG, viewBox 300×80).
    $cxPts = '';
    if (count($cxSerie) > 1) {
        $cxMax = max(1, collect($cxSerie)->max('total'));
        $cxPts = collect($cxSerie)->values()->map(fn ($j, $i) => round($i * 300 / (count($cxSerie) - 1), 1).','.round(72 - $j['total'] / $cxMax * 62, 1))->implode(' ');
    }

    $cxAffluence = $affluence ?? [];
    $cxAffMax = max(1, collect($cxAffluence)->max('count') ?? 0);
    $cxHeure = (int) now()->format('G');

    $cxFenetre = (int) $cxJ['fenetre_annulation_minutes'];
    $cxSessLib = match ($cxSession) {
        'open' => ['cx-sess--on', 'Caisse ouverte à '.($cxJ['session']['ouverte_a'] ?? '—')],
        'closed', 'auto_closed' => ['cx-sess--done', 'Caisse clôturée'.($cxJ['session']['fermee_a'] ? ' à '.$cxJ['session']['fermee_a'] : '')],
        default => ['cx-sess--off', 'Caisse non ouverte'],
    };
    // Ouvrir l'écran d'encaissement (au moins un mode) ; la caisse physique, elle,
    // ne concerne que qui encaisse des espèces (`paiements.create`).
    $cxPeutEncaisser = auth()->user()?->can('porte:esbtp.paiements.create') ?? false;
    $cxEncaisseEspeces = auth()->user()?->can('paiements.create') ?? false;
@endphp

<div class="dashboard-acasi {{ $cxShellMobile ? 'm-only-desktop' : '' }}">
<div class="cx-wrap">

    <div class="cx-head">
        <div>
            <div class="cx-head-s">{{ ucfirst(now()->locale('fr')->isoFormat('dddd D MMMM')) }} · {{ $user->name }}</div>
            <h1>Ma caisse du jour</h1>
        </div>
        <div class="cx-head-r">
            <span class="cx-sess {{ $cxSessLib[0] }}">{{ $cxSessLib[1] }}</span>
            @if($cxPeutMaCaisse)
                <a href="{{ route('esbtp.caisse.ma-caisse') }}" class="cx-btn"><i class="fas fa-vault"></i> {{ $cxSession === 'open' ? 'Compter et clôturer' : 'Ma caisse' }}</a>
            @endif
            @can('comptabilite.access')
            @can('comptabilite.journal.view')
                <a href="{{ route('esbtp.comptabilite.journal-caisse.index') }}" class="cx-btn"><i class="fas fa-book"></i> Journal de caisse</a>
            @endcan
            @endcan
        </div>
    </div>

    @if($indisponible)
        <div class="cx-warn-strip"><i class="fas fa-triangle-exclamation"></i> Les chiffres du jour sont momentanément indisponibles. L’incident est journalisé ; vos versements restent consultables dans la liste.</div>
    @endif

    <div class="cx-top">
        <div class="cx-hero">
            <div class="cx-ring" role="img" aria-label="{{ $cxPart === null ? 'Pas encore de moyenne' : round($cxPart).' % de ma moyenne' }}">
                <svg viewBox="0 0 42 42"><circle cx="21" cy="21" r="17" fill="none" stroke="rgba(255,255,255,.16)" stroke-width="3.2"></circle><circle cx="21" cy="21" r="17" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" pathLength="100" stroke-dasharray="{{ round($cxAnneau, 1) }} 100"></circle></svg>
                <div class="cx-ring-mid">
                    @if($cxPart === null)<b>—</b><small>pas encore de moyenne</small>
                    @else<b class="cx-num">{{ round($cxPart) }} %</b><small>de ma moyenne journalière</small>@endif
                </div>
            </div>
            <div class="cx-hero-b">
                <span class="cx-hero-l">Encaissé validé aujourd'hui</span>
                <span class="cx-big cx-num"><b>{{ $indisponible ? '—' : $cxFmt($cxJ['total']) }}</b><small>FCFA</small></span>
                <span class="cx-hero-r">
                    @if($cxVariation === null) Hier : {{ $cxFmt($cxJ['hier']) }} FCFA
                    @else {{ $cxVariation >= 0 ? '▲' : '▼' }} {{ number_format(abs($cxVariation), abs($cxVariation) < 10 ? 1 : 0, ',', ' ') }} % vs hier ({{ $cxFmt($cxJ['hier']) }})
                    @endif
                    · {{ $cxJ['count'] }} saisie{{ $cxJ['count'] > 1 ? 's' : '' }} validée{{ $cxJ['count'] > 1 ? 's' : '' }}
                    @if($cxMoyenne) · moyenne {{ $cxFmt($cxMoyenne) }} @endif
                </span>
                <div class="cx-modes">
                    <div class="cx-mode"><span>Espèces · tiroir</span><b class="cx-num">{{ $cxFmt($cxJ['especes']['total']) }}</b></div>
                    <div class="cx-mode"><span>Mobile money</span><b class="cx-num">{{ $cxFmt($cxJ['mobile']['total']) }}</b></div>
                    <div class="cx-mode"><span>Virement, chèque…</span><b class="cx-num">{{ $cxFmt($cxJ['autres']['total']) }}</b></div>
                </div>
            </div>
        </div>

        <div class="cx-tiles">
            @if($cxPeutEncaisser)
                <a href="{{ route('esbtp.paiements.create') }}" class="cx-tile cx-tile--main"><i class="fas fa-plus"></i><b>Encaisser</b><small>Ctrl + E</small></a>
            @endif
            @if($peutVoirPaiements)
                <a href="{{ route('esbtp.paiements.index') }}" class="cx-tile"><i class="fas fa-magnifying-glass"></i><b>Retrouver un versement</b><small>nom, matricule, n° de reçu</small></a>
            @endif
            <a class="cx-tile cx-tile--warn" @if($cxLienAValider) href="{{ $cxLienAValider }}" @endif>
                <span class="cx-tile-n cx-num">{{ $cxJ['a_valider'] }}</span><b>En attente de validation</b><small>{{ $cxFmt($cxJ['a_valider_total']) }} FCFA saisis aujourd'hui</small>
            </a>
            @if($cxJ['peut_annuler'])
                <a href="#cx-derniers" class="cx-tile cx-tile--info"><span class="cx-tile-n cx-num">{{ $cxJ['annulables'] }}</span><b>Encore annulable{{ $cxJ['annulables'] > 1 ? 's' : '' }}</b><small>une erreur ? {{ $cxFenetre }} min après la saisie</small></a>
            @else
                <a class="cx-tile cx-tile--info" @if($cxLienJour) href="{{ $cxLienJour }}" @endif><span class="cx-tile-n cx-num">{{ $paiementsAujourdhuiCount }}</span><b>Saisies du jour</b><small>validées et en attente</small></a>
            @endif
        </div>
    </div>

    @php
        $cxAlertes = [];
        if ($cxPeutMaCaisse && $cxSession === null && $cxEncaisseEspeces) {
            $cxAlertes[] = ['warn', 'fa-lock-open', 'Votre caisse n’est pas ouverte.', 'Le premier encaissement en espèces l’ouvre, ou ouvrez-la maintenant.', route('esbtp.caisse.ma-caisse'), 'Ouvrir'];
        }
        if ($preInscriptionOuverte && $preInscriptionsEnAttente > 0 && (auth()->user()?->can('inscriptions.view') ?? false)) {
            $cxAlertes[] = ['info', 'fa-user-clock', $preInscriptionsEnAttente.' pré-inscription'.($preInscriptionsEnAttente > 1 ? 's' : '').' à finaliser.', 'Dossiers ouverts à la caisse, en attente du secrétariat.', route('esbtp.inscriptions.index', ['status' => 'en_attente']), 'Voir'];
        }
    @endphp
    @if($cxAlertes)
        <div class="cx-alerts">
            @foreach($cxAlertes as [$ton, $ic, $t, $d, $href, $cta])
                <a href="{{ $href }}" class="cx-alert {{ $ton === 'warn' ? 'cx-alert--warn' : '' }}"><i class="fas {{ $ic }}"></i><span><b>{{ $t }}</b> {{ $d }}</span><em>{{ $cta }} →</em></a>
            @endforeach
        </div>
    @endif

    <div class="cx-bottom">
        <div class="cx-card" id="cx-derniers">
            <div class="cx-card-h">
                <div><div class="cx-card-t">Mes dernières saisies</div><div class="cx-card-s">Voir, reçu, annuler ma saisie ou annuler le versement, sans quitter l'accueil</div></div>
                @if($peutVoirPaiements)<a href="{{ route('esbtp.paiements.index') }}" class="cx-lnk">Toutes →</a>@endif
            </div>
            @if($paiementsRecents->isEmpty())
                <div class="cx-empty">
                    <strong>Aucun versement pour l'instant.</strong><br>
                    @if($cxPeutEncaisser) Le premier encaissement apparaîtra ici. <a href="{{ route('esbtp.paiements.create') }}">Encaisser maintenant</a>
                    @else Les versements que vous enregistrez apparaîtront ici. @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="cx-table">
                        <tbody>
                        @foreach($paiementsRecents as $paiement)
                            @php
                                $et = $paiement->etudiant;
                                $nom = $et ? trim(($et->nom ?? '').' '.($et->prenoms ?? '')) : 'Étudiant inconnu';
                                [$pLib, $pTon] = $paiement->isAvoir()
                                    ? ['Avoir', 'info']
                                    : match ($paiement->status) {
                                        'validé' => ['Validé', 'ok'],
                                        'en_attente' => ['À valider', 'wait'],
                                        'rejeté' => ['Rejeté', 'bad'],
                                        default => [ucfirst((string) $paiement->status), 'info'],
                                    };
                                $cxReste = null;
                                if ($cxJ['peut_annuler'] && $paiement->created_at && auth()->user()->can('cancelOwnRecent', $paiement)) {
                                    $cxReste = max(1, (int) ceil($cxFenetre - $paiement->created_at->diffInSeconds(now()) / 60));
                                }
                                $cxModeLib = \App\Enums\ModePaiement::fromLegacy((string) $paiement->mode_paiement)?->label() ?? ($paiement->mode_paiement ?: '—');
                            @endphp
                            <tr>
                                <td class="cx-h cx-num">{{ optional($paiement->created_at)->isToday() ? $paiement->created_at->format('H:i') : optional($paiement->created_at)->format('d/m') }}</td>
                                <td>
                                    <div class="cx-who-n">{{ $nom }}</div>
                                    <div class="cx-who-s">{{ $paiement->numero_recu ?? '—' }} · {{ $paiement->fraisCategory?->name ?? 'Versement' }} · {{ $cxModeLib }}</div>
                                    @if($cxReste)<span class="cx-left">annulable encore {{ $cxReste }} min</span>@endif
                                </td>
                                <td><span class="cx-pill cx-pill--{{ $pTon }}">{{ $pLib }}</span></td>
                                <td class="cx-amount cx-num">{{ $paiement->isAvoir() ? '−' : '' }}{{ $cxFmt($paiement->montant) }} F</td>
                                <td class="cx-act">@include('esbtp.paiements.partials.actions-versement', ['paiement' => $paiement, 'retour' => '/dashboard', 'avSuffixe' => '-cx'])</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="cx-side">
            <div class="cx-card">
                <div class="cx-card-h"><span class="cx-card-t">Affluence du jour</span><span class="cx-card-s">saisies par heure</span></div>
                @if(collect($cxAffluence)->sum('count') === 0)
                    <div class="cx-empty">Aucune saisie aujourd’hui pour l’instant.</div>
                @else
                    <div class="cx-bars">
                        @foreach($cxAffluence as $b)
                            <div title="{{ $b['heure'] }} h : {{ $b['count'] }} saisie{{ $b['count'] > 1 ? 's' : '' }}"><i class="{{ $b['heure'] === $cxHeure ? 'is-now' : '' }}" style="height: {{ max(2, round($b['count'] / $cxAffMax * 100)) }}%"></i><span>{{ $b['heure'] }}h</span></div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="cx-card">
                <div class="cx-card-h"><span class="cx-card-t">Mes 7 derniers jours</span><span class="cx-card-s cx-num">{{ $cxFmt($cxTotalSemaine) }} FCFA</span></div>
                @if($cxPts === '')
                    <div class="cx-empty">Pas encore d’historique.</div>
                @else
                    <svg class="cx-spark" viewBox="0 0 300 80" preserveAspectRatio="none" role="img" aria-label="Encaissé validé par jour sur sept jours">
                        <polyline points="{{ $cxPts }}" fill="none" stroke="#0453cb" stroke-width="2.5" stroke-linejoin="round" vector-effect="non-scaling-stroke"></polyline>
                    </svg>
                    <div class="cx-axe">
                        @foreach($cxSerie as $j)<span title="{{ $cxFmt($j['total']) }} FCFA">{{ $loop->last ? 'auj.' : \Carbon\Carbon::parse($j['jour'])->locale('fr')->isoFormat('ddd') }}</span>@endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>


    {{-- ─── Voir plus de détails : la richesse de l'ancien accueil, repliée par
         défaut pour garder l'accueil efficace. Le choix est retenu par
         navigateur (confort personnel, rien de plus). ─── --}}
    @php
        // Les alertes déjà affichées au-dessus (caisse non ouverte,
        // pré-inscriptions) ne sont pas répétées ici.
        $cxTodo = [];
        if ($cxJ['annulables'] > 0) {
            $cxTodo[] = ['warn', 'fa-rotate-left', $cxJ['annulables'].' saisie'.($cxJ['annulables'] > 1 ? 's' : '').' encore annulable'.($cxJ['annulables'] > 1 ? 's' : ''), 'Une erreur ? Vous avez '.$cxFenetre.' minutes après la saisie pour l’annuler vous-même.', '#cx-derniers'];
        }
        if ($cxJ['a_valider'] > 0 && $cxLienAValider) {
            $cxTodo[] = ['warn', 'fa-hourglass-half', $cxJ['a_valider'].' versement'.($cxJ['a_valider'] > 1 ? 's' : '').' en attente de validation', $cxFmt($cxJ['a_valider_total']).' FCFA saisis aujourd’hui, pas encore comptabilisés.', $cxLienAValider];
        }
        if ($cxSession === 'open' && $cxPeutMaCaisse) {
            $cxTodo[] = ['info', 'fa-vault', 'Caisse ouverte depuis '.($cxJ['session']['ouverte_a'] ?? '—'), 'Pensez à compter et clôturer en fin de journée.', route('esbtp.caisse.ma-caisse')];
        }
        $cxTotalModes = max(1, $cxJ['especes']['total'] + $cxJ['mobile']['total'] + $cxJ['autres']['total']);
        $cxMaxJour = max(1, collect($cxSerie)->max('total') ?? 0);
        $cxMeilleur = collect($cxSerie)->sortByDesc('total')->first();
    @endphp
    <div x-data="{ ouvert: (() => { try { return localStorage.getItem('cx-details') === '1'; } catch (e) { return false; } })() }" style="display: flex; flex-direction: column; gap: 1rem;">
        <button type="button" class="cx-more-btn" :class="{ 'is-open': ouvert }" :aria-expanded="ouvert.toString()"
                x-on:click="ouvert = !ouvert; try { localStorage.setItem('cx-details', ouvert ? '1' : '0'); } catch (e) {}">
            <span x-text="ouvert ? 'Masquer les détails' : 'Voir plus de détails'">Voir plus de détails</span> <i class="fas fa-chevron-down"></i>
        </button>
        <div class="cx-more" x-show="ouvert" x-cloak x-transition.opacity>
            <div class="cx-card">
                <div class="cx-card-h"><span class="cx-card-t">À faire</span><span class="cx-card-s">contrôlé à {{ now()->format('H:i') }}</span></div>
                @forelse($cxTodo as [$ton, $ic, $t, $d, $href])
                    <a href="{{ $href }}" class="cx-todo cx-todo--{{ $ton }}"><span class="cx-todo-ic"><i class="fas {{ $ic }}"></i></span><span><span class="cx-todo-t">{{ $t }}</span><br><span class="cx-todo-d">{{ $d }}</span></span></a>
                @empty
                    <div class="cx-ok"><i class="fas fa-circle-check"></i> Rien en attente.</div>
                @endforelse
            </div>

            <div class="cx-card">
                <div class="cx-card-h"><span class="cx-card-t">Par moyen de paiement</span>
                    @if($cxPeutMaCaisse)<a href="{{ route('esbtp.caisse.ma-caisse') }}" class="cx-lnk">Compter ma caisse →</a>@endif</div>
                <span class="cx-card-s">Ce que vous devez retrouver dans le tiroir et sur les comptes.</span>
                @foreach([['especes', 'Espèces · tiroir'], ['mobile', 'Mobile money'], ['autres', 'Virement, chèque…']] as [$cle, $lib])
                    @php $bloc = $cxJ[$cle]; @endphp
                    <div class="cx-mline">
                        <div class="cx-mline-h"><span>{{ $lib }}</span><b class="cx-num">{{ $cxFmt($bloc['total']) }} FCFA</b></div>
                        <div class="cx-mbar"><i style="width: {{ round($bloc['total'] / $cxTotalModes * 100) }}%"></i></div>
                        <small>{{ $bloc['count'] }} versement{{ $bloc['count'] > 1 ? 's' : '' }} · {{ round($bloc['total'] / $cxTotalModes * 100) }} % du jour</small>
                    </div>
                @endforeach
            </div>

            <div class="cx-card">
                <div class="cx-card-h"><span class="cx-card-t">Mes 7 derniers jours, jour par jour</span></div>
                @if(count($cxSerie) === 0)
                    <div class="cx-empty">Pas encore d’historique.</div>
                @else
                    <div class="cx-week" role="img" aria-label="Encaissé validé par jour">
                        @foreach($cxSerie as $j)
                            <div title="{{ $cxFmt($j['total']) }} FCFA">
                                <em>{{ $j['total'] > 0 ? ($j['total'] >= 1000000 ? number_format($j['total'] / 1000000, 1, ',', ' ').' M' : $cxFmt(round($j['total'] / 1000)).' k') : '' }}</em>
                                <i class="{{ $loop->last ? 'is-now' : '' }}" style="height: {{ $j['total'] > 0 ? max(4, round($j['total'] / $cxMaxJour * 100)) : 2 }}%"></i>
                                <span>{{ $loop->last ? 'auj.' : $j['libelle'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="cx-foot"><span>Total : <b class="cx-num">{{ $cxFmt($cxTotalSemaine) }} FCFA</b></span>
                        @if($cxMeilleur && $cxMeilleur['total'] > 0)<span>Meilleur jour : <b>{{ $cxMeilleur['libelle'] }}</b>, {{ $cxFmt($cxMeilleur['total']) }} FCFA</span>@endif</div>
                @endif
            </div>
        </div>
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
                @can('porte:esbtp.paiements.create')
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

    @can('porte:esbtp.paiements.create')
        <a href="{{ route('esbtp.paiements.create') }}" class="m-fab" aria-label="Encaisser un paiement">
            <x-m.icon name="plus" />
        </a>
    @endcan
</div>
@endif
@endsection
