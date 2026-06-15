@extends('layouts.app')

@section('title', 'Paie des enseignants — KLASSCI')

@push('styles')
<style>
    .pay-wrap { max-width: 1180px; margin: 0 auto; }
    .pay-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 1.9rem 2.25rem 1.5rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
    .pay-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .pay-hero-left { display: flex; align-items: center; gap: 1rem; }
    .pay-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; color: #fff; flex-shrink: 0; }
    .pay-hero h1 { font-size: 1.4rem; font-weight: 700; color: #fff; margin: 0; }
    .pay-hero p { color: rgba(255,255,255,.72); font-size: .85rem; margin: .15rem 0 0; }
    .pay-hero-actions { display: flex; gap: .55rem; flex-wrap: wrap; }
    .pay-btn { display: inline-flex; align-items: center; gap: .45rem; border: none; cursor: pointer; border-radius: 10px; padding: .55rem 1rem; font-size: .82rem; font-weight: 700; text-decoration: none; }
    .pay-btn--white { background: #fff; color: #0453cb; }
    .pay-btn--glass { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.2); }
    .pay-btn--glass:hover { background: rgba(255,255,255,.25); }
    .pay-btn--primary { background: #0453cb; color: #fff; }
    .pay-btn--ghost { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
    .pay-btn:disabled { opacity: .6; cursor: wait; }

    .pay-kpis { display: flex; gap: .75rem; margin-top: 1.4rem; flex-wrap: wrap; }
    .pay-kpi { flex: 1; min-width: 150px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .85rem 1rem; display: flex; align-items: center; gap: .7rem; }
    .pay-kpi-ico { width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center; font-size: .95rem; color: #fff; }
    .pay-kpi--ok .pay-kpi-ico { background: rgba(16,185,129,.3); }
    .pay-kpi-val { font-size: 1.25rem; font-weight: 700; color: #fff; line-height: 1; }
    .pay-kpi-cur { font-size: .75rem; font-weight: 600; color: rgba(255,255,255,.6); margin-left: .2rem; }
    .pay-kpi-lbl { font-size: .66rem; color: rgba(255,255,255,.65); margin-top: .2rem; }

    .pay-filters { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: .85rem 1.1rem; margin-bottom: 1.25rem; display: flex; gap: .8rem; flex-wrap: wrap; align-items: flex-end; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
    .pay-field { display: flex; flex-direction: column; gap: .25rem; min-width: 150px; }
    .pay-field-lbl { font-size: .68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
    .pay-spin { color: #0453cb; font-size: .82rem; display: none; align-items: center; gap: .4rem; margin-left: auto; }
    .pay-spin.show { display: inline-flex; }

    .pay-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
    .pay-panel-body { padding: .5rem 1rem; }

    .pay-row { display: flex; align-items: center; gap: 1rem; padding: .85rem .4rem; border-bottom: 1px solid #f1f5f9; text-decoration: none; transition: background .15s; }
    .pay-row:last-child { border-bottom: none; }
    .pay-row:hover { background: rgba(4,83,203,.03); }
    .pay-row-avatar { width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0; background: linear-gradient(135deg, #0453cb, #5e91de); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; }
    .pay-row-main { flex: 1; min-width: 0; }
    .pay-row-name { font-size: .9rem; font-weight: 700; color: #1e293b; }
    .pay-row-meta { display: flex; flex-wrap: wrap; gap: .65rem; font-size: .72rem; color: #64748b; margin-top: .2rem; }
    .pay-row-meta i { color: #94a3b8; margin-right: .12rem; }
    .pay-row-net { text-align: right; flex-shrink: 0; }
    .pay-row-net-val { font-size: 1.05rem; font-weight: 800; color: #0f172a; }
    .pay-row-net-lbl { font-size: .6rem; color: #94a3b8; text-transform: uppercase; }
    .pay-row-badge { font-size: .68rem; font-weight: 700; padding: .2rem .55rem; border-radius: 6px; white-space: nowrap; flex-shrink: 0; }
    .pay-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
    .pay-empty i { font-size: 2rem; display: block; margin-bottom: .6rem; color: #cbd5e1; }

    /* ── Récap enseignants à payer ─────────────────────────── */
    .pay-field--grow { flex: 1; min-width: 200px; }
    .pay-search { position: relative; display: flex; align-items: center; }
    .pay-search > i { position: absolute; left: .7rem; color: #94a3b8; font-size: .8rem; }
    .pay-search .pay-input { padding-left: 2rem; }
    .pay-recap-head { display: flex; align-items: center; gap: .65rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
    .pay-recap-head-ico { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }
    .pay-recap-head-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
    .pay-recap-head-sub { font-size: .73rem; color: #94a3b8; }

    .pay-rrow { display: flex; align-items: center; gap: 1rem; padding: .9rem .4rem; border-bottom: 1px solid #f1f5f9; }
    .pay-rrow:last-child { border-bottom: none; }
    .pay-rrow:hover { background: rgba(4,83,203,.025); }
    .pay-rrow-avatar { width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0; background: linear-gradient(135deg, #0453cb, #5e91de); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; }
    .pay-rrow-id { flex: 1; min-width: 0; }
    .pay-rrow-name { font-size: .9rem; font-weight: 700; color: #1e293b; }
    .pay-rrow-types { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .3rem; align-items: center; }
    .pay-rrow-h { font-size: .72rem; font-weight: 700; color: #334155; }
    .pay-rrow-h i { color: #94a3b8; margin-right: .15rem; }
    .pay-rrow-chip { display: inline-flex; align-items: center; gap: .25rem; font-size: .66rem; font-weight: 700; padding: .12rem .4rem; border-radius: 6px; }
    .pay-rrow-amounts { display: flex; gap: 1.25rem; flex-shrink: 0; }
    .pay-rrow-amt { text-align: right; font-size: .9rem; font-weight: 700; color: #334155; }
    .pay-rrow-amt-lbl { display: block; font-size: .58rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .3px; font-weight: 600; margin-bottom: .1rem; }
    .pay-rrow-amt--neg { color: #b91c1c; }
    .pay-rrow-amt--net strong { font-size: 1.1rem; font-weight: 800; color: #0453cb; }
    .pay-rrow-end { display: flex; flex-direction: column; align-items: flex-end; gap: .4rem; flex-shrink: 0; min-width: 110px; }
    .pay-rrow-badge { font-size: .66rem; font-weight: 700; padding: .2rem .55rem; border-radius: 6px; white-space: nowrap; }
    .pay-rrow-btn { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; border-radius: 8px; padding: .35rem .7rem; font-size: .74rem; font-weight: 700; text-decoration: none; transition: all .15s; }
    .pay-rrow-btn--prep { color: #0453cb; border-color: rgba(4,83,203,.3); }
    .pay-rrow-btn--prep:hover { background: #0453cb; color: #fff; }
    .pay-rrow-btn--view { color: #475569; }
    .pay-rrow-btn--view:hover { border-color: #0453cb; color: #0453cb; }
    @media (max-width: 860px) {
        .pay-rrow { flex-wrap: wrap; }
        .pay-rrow-amounts { gap: .9rem; width: 100%; justify-content: space-between; padding-left: 54px; }
        .pay-rrow-end { width: 100%; flex-direction: row; justify-content: space-between; padding-left: 54px; }
    }

    /* ── Sélecteur de période (présets segmentés) ─────────── */
    .pay-presets { display: inline-flex; background: #f1f5f9; border-radius: 10px; padding: .22rem; gap: .15rem; flex-wrap: wrap; }
    .pay-preset { border: none; background: transparent; color: #475569; font-size: .76rem; font-weight: 700; padding: .42rem .8rem; border-radius: 8px; cursor: pointer; transition: background .15s, color .15s, box-shadow .15s; }
    .pay-preset--on { background: #fff; color: #0453cb; box-shadow: 0 1px 3px rgba(15,23,42,.1); }
    .pay-preset:hover:not(.pay-preset--on) { color: #0453cb; }
    .pay-field--anchor { min-width: 130px; }

    /* ── Bandeau mensuel cliquable (récap période) ────────── */
    .pay-rrow-id { flex: 1 1 210px; }
    .pay-rrow-months { display: flex; flex-wrap: wrap; gap: .4rem; flex: 2 1 300px; align-content: center; }
    .pay-mchip { display: inline-flex; flex-direction: column; align-items: stretch; min-width: 60px; border-radius: 9px; padding: .3rem .5rem; text-decoration: none; border: 1px solid transparent; cursor: pointer; font-family: inherit; transition: box-shadow .12s, filter .12s; }
    .pay-mchip:hover { box-shadow: 0 4px 12px rgba(15,23,42,.12); filter: brightness(1.02); }
    .pay-mchip-top { display: flex; align-items: center; justify-content: space-between; gap: .3rem; }
    .pay-mchip-m { font-size: .63rem; font-weight: 800; text-transform: uppercase; letter-spacing: .3px; }
    .pay-mchip-top i { font-size: .58rem; opacity: .75; }
    .pay-mchip-net { font-size: .78rem; font-weight: 800; margin-top: .12rem; white-space: nowrap; }
    .pay-mchip--a_preparer { background: rgba(245,158,11,.1); border-color: rgba(245,158,11,.32); color: #b45309; }
    .pay-mchip--a_preparer:hover { background: rgba(245,158,11,.18); }
    .pay-mchip--brouillon { background: rgba(100,116,139,.1); border-color: rgba(100,116,139,.26); color: #475569; }
    .pay-mchip--valide { background: rgba(4,83,203,.1); border-color: rgba(4,83,203,.3); color: #0453cb; }
    .pay-mchip--paye { background: rgba(16,185,129,.12); border-color: rgba(16,185,129,.32); color: #065f46; }
    .pay-mchip--annule { background: rgba(220,38,38,.1); border-color: rgba(220,38,38,.3); color: #b91c1c; }
    .pay-mchip--annule .pay-mchip-net { text-decoration: line-through; }

    .pay-rrow-netcol { flex-shrink: 0; min-width: 132px; display: flex; flex-direction: column; align-items: flex-end; gap: .25rem; }
    .pay-rrow-net-lbl { font-size: .57rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .3px; font-weight: 700; }
    .pay-rrow-net-val { font-size: 1.15rem; font-weight: 800; color: #0453cb; }
    .pay-rrow-net-val small { font-size: .58rem; color: #94a3b8; font-weight: 700; }
    .pay-rrow-statusline { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; justify-content: flex-end; }
    .pay-rrow-hint { font-size: .62rem; color: #b45309; font-weight: 700; white-space: nowrap; }

    .pay-legend { display: flex; flex-wrap: wrap; gap: .8rem; padding: .5rem 1.25rem .7rem; border-bottom: 1px solid #f1f5f9; }
    .pay-legend-item { display: inline-flex; align-items: center; gap: .35rem; font-size: .66rem; color: #64748b; font-weight: 600; }
    .pay-legend-dot { width: 10px; height: 10px; border-radius: 3px; }
    @media (max-width: 860px) {
        .pay-rrow-months { flex-basis: 100%; padding-left: 54px; }
        .pay-rrow-netcol { width: 100%; flex-direction: row; justify-content: space-between; align-items: center; padding-left: 54px; }
    }

    /* Modals */
    .pay-modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); backdrop-filter: blur(2px); display: flex; align-items: flex-start; justify-content: center; padding: 3rem 1rem; z-index: 1080; overflow-y: auto; }
    .pay-modal { background: #fff; border-radius: 16px; width: 100%; max-width: 760px; box-shadow: 0 24px 60px rgba(15,23,42,.25); }
    .pay-modal--narrow { max-width: 540px; }
    .pay-modal-head { display: flex; align-items: center; gap: .7rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid #f1f5f9; }
    .pay-modal-head-ico { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; }
    .pay-modal-title { font-size: 1rem; font-weight: 700; color: #1e293b; }
    .pay-modal-sub { font-size: .74rem; color: #94a3b8; }
    .pay-modal-close { margin-left: auto; border: none; background: #f1f5f9; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; color: #64748b; }
    .pay-modal-body { padding: 1.25rem 1.4rem; }
    .pay-modal-foot { padding: 1rem 1.4rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: .6rem; }

    .pay-input { border: 1px solid #e2e8f0; border-radius: 9px; padding: .5rem .7rem; font-size: .82rem; color: #1e293b; width: 100%; }
    .pay-input:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
    .pay-grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .7rem; }
    .pay-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; }
    /* Force le picker premium à remplir sa colonne (sinon inline-flex rétrécit → menu trop étroit, noms tronqués). */
    .pay-au-full { display: flex !important; width: 100%; }
    .pay-au-full .au-select-trigger { width: 100%; }

    .pay-prev { margin-top: 1.1rem; border: 1px solid #e9eef5; border-radius: 12px; overflow: hidden; }
    .pay-prev-sec { padding: .8rem 1rem; }
    .pay-prev-sec + .pay-prev-sec { border-top: 1px solid #f1f5f9; }
    .pay-prev-h { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; color: #64748b; margin-bottom: .5rem; }
    .pay-line { display: flex; align-items: center; justify-content: space-between; font-size: .82rem; padding: .25rem 0; }
    .pay-line-lbl { color: #334155; }
    .pay-line-detail { font-size: .68rem; color: #94a3b8; }
    .pay-line-amt { font-weight: 700; color: #0f172a; }
    .pay-line-amt--neg { color: #b91c1c; }
    .pay-net { display: flex; align-items: center; justify-content: space-between; padding: .9rem 1rem; background: linear-gradient(135deg, rgba(4,83,203,.06), rgba(59,125,219,.08)); }
    .pay-net-lbl { font-size: .8rem; font-weight: 700; color: #0453cb; }
    .pay-net-val { font-size: 1.35rem; font-weight: 800; color: #0453cb; }

    .pay-mini-add { border: 1px dashed #cbd5e1; background: #fff; color: #0453cb; border-radius: 8px; padding: .4rem .7rem; font-size: .76rem; font-weight: 600; cursor: pointer; }
    .pay-mini-row { display: flex; gap: .5rem; align-items: center; margin-top: .4rem; }
    .pay-mini-row .pay-input { flex: 1; }
    .pay-mini-del { border: none; background: rgba(220,38,38,.08); color: #b91c1c; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; flex-shrink: 0; }
    .pay-override { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; margin-top: .8rem; }
    .pay-help { font-size: .72rem; color: #94a3b8; margin-top: .3rem; }
    [x-cloak] { display: none !important; }

    @media (max-width: 768px) { .pay-hero { padding: 1.4rem 1.25rem; } .pay-grid3 { grid-template-columns: 1fr; } .pay-grid2 { grid-template-columns: 1fr; } .pay-override { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="pay-wrap" x-data="salairesPage()" data-url-data="{{ route('esbtp.comptabilite.salaires.data') }}"
     data-url-prepare="{{ route('esbtp.comptabilite.salaires.prepare') }}"
     data-url-store="{{ route('esbtp.comptabilite.salaires.store') }}"
     data-url-config="{{ route('esbtp.comptabilite.salaires.config') }}">

    {{-- Hero --}}
    <div class="pay-hero">
        <div class="pay-hero-top">
            <div class="pay-hero-left">
                <div class="pay-hero-icon"><i class="fas fa-money-check-dollar"></i></div>
                <div>
                    <h1>Paie des enseignants</h1>
                    <p>Heures réellement effectuées × taux par type — net à verser, retenues et impôts</p>
                </div>
            </div>
            <div class="pay-hero-actions">
                @if($canExport)
                    <x-export-modal
                        :preview-url="route('esbtp.comptabilite.salaires.export.preview-pdf')"
                        :pdf-url="route('esbtp.comptabilite.salaires.export.pdf')"
                        :excel-url="route('esbtp.comptabilite.salaires.export.excel')"
                        button-class="pay-btn pay-btn--glass" />
                @endif
                @if($canConfigure)
                    <button type="button" class="pay-btn pay-btn--glass" @click="openConfig()"><i class="fas fa-sliders"></i> Paramètres</button>
                @endif
                @if($canCreate)
                    <button type="button" class="pay-btn pay-btn--white" @click="openPrepare()"><i class="fas fa-plus"></i> Préparer un bulletin</button>
                @endif
            </div>
        </div>
        <div class="pay-kpis" id="payKpis">
            @include('esbtp.comptabilite.salaires.partials._kpis', ['kpis' => $kpis])
        </div>
    </div>

    {{-- Filtres période --}}
    <div class="pay-filters">
        <div class="pay-field">
            <span class="pay-field-lbl">Période</span>
            <div class="pay-presets">
                @foreach($presets as $key => $lbl)
                    <button type="button" class="pay-preset" :class="filters.preset==='{{ $key }}' ? 'pay-preset--on' : ''" @click="setPreset('{{ $key }}')">{{ $lbl }}</button>
                @endforeach
            </div>
        </div>

        {{-- Ancrage mois/année (présets relatifs : la période se termine à ce mois) --}}
        <div class="pay-field pay-field--anchor" x-show="['month','quarter','semester'].includes(filters.preset)">
            <span class="pay-field-lbl" x-text="filters.preset==='month' ? 'Mois' : 'Jusqu’au mois'">Mois</span>
            <x-au-select name="mois_filter" :value="(string) $filtres['mois']" :options="$moisOptions" icon="fa-calendar" />
        </div>
        <div class="pay-field pay-field--anchor" x-show="['month','quarter','semester'].includes(filters.preset)">
            <span class="pay-field-lbl">Année</span>
            <x-au-select name="annee_filter" :value="(string) $filtres['annee']"
                :options="collect(range(now()->year - 3, now()->year + 1))->mapWithKeys(fn($y) => [$y => (string) $y])->toArray()" icon="fa-calendar-days" />
        </div>

        {{-- Plage libre --}}
        <div class="pay-field pay-field--anchor" x-show="filters.preset==='custom'" x-cloak>
            <span class="pay-field-lbl">Du (mois)</span>
            <input type="month" class="pay-input" x-model="filters.from" @change="filters.preset==='custom' && filters.from && filters.to && applyFilters()">
        </div>
        <div class="pay-field pay-field--anchor" x-show="filters.preset==='custom'" x-cloak>
            <span class="pay-field-lbl">Au (mois)</span>
            <input type="month" class="pay-input" x-model="filters.to" @change="filters.preset==='custom' && filters.from && filters.to && applyFilters()">
        </div>

        <div class="pay-field">
            <span class="pay-field-lbl">Statut</span>
            <x-au-select name="statut_filter" :value="$filtres['statut'] ?? ''" placeholder="Tous statuts" icon="fa-filter" :options="$statutLabels" />
        </div>
        <div class="pay-field pay-field--grow">
            <span class="pay-field-lbl">Rechercher un enseignant</span>
            <div class="pay-search">
                <i class="fas fa-search"></i>
                <input type="text" class="pay-input" placeholder="Nom de l'enseignant…" x-model="filters.q" @input.debounce.400ms="applyFilters()">
            </div>
        </div>
        <span class="pay-spin" :class="loading ? 'show' : ''"><i class="fas fa-circle-notch fa-spin"></i> Mise à jour…</span>
    </div>

    {{-- Récap : tous les enseignants à payer ce mois --}}
    <div class="pay-panel">
        <div class="pay-recap-head">
            <div class="pay-recap-head-ico"><i class="fas fa-list-check"></i></div>
            <div>
                <div class="pay-recap-head-title">Enseignants à payer — <span x-text="periodLabel">{{ $periodLabel }}</span></div>
                <div class="pay-recap-head-sub">Heures réalisées × taux par type · chaque mois est cliquable (préparer ou voir le bulletin)</div>
            </div>
        </div>
        <div class="pay-legend">
            <span class="pay-legend-item"><span class="pay-legend-dot" style="background:#f59e0b;"></span> À préparer (estimé)</span>
            <span class="pay-legend-item"><span class="pay-legend-dot" style="background:#64748b;"></span> Brouillon</span>
            <span class="pay-legend-item"><span class="pay-legend-dot" style="background:#0453cb;"></span> Validé</span>
            <span class="pay-legend-item"><span class="pay-legend-dot" style="background:#10b981;"></span> Payé</span>
        </div>
        <div class="pay-panel-body" id="payList">
            @include('esbtp.comptabilite.salaires.partials._recap', ['recap' => $recap, 'statutLabels' => $statutLabels, 'canCreate' => $canCreate])
        </div>
    </div>

    {{-- ===== Modal Préparer ===== --}}
    <div class="pay-modal-overlay" x-show="showPrepare" x-cloak @keydown.escape.window="showPrepare=false" style="display:none;">
        <div class="pay-modal" @click.outside="showPrepare=false">
            <div class="pay-modal-head">
                <div class="pay-modal-head-ico"><i class="fas fa-calculator"></i></div>
                <div>
                    <div class="pay-modal-title">Préparer un bulletin de paie</div>
                    <div class="pay-modal-sub">Calcul automatique des heures réalisées × taux</div>
                </div>
                <button type="button" class="pay-modal-close" @click="showPrepare=false"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="pay-modal-body">
                <div class="pay-field" style="margin-bottom:.8rem;">
                    <span class="pay-field-lbl">Enseignant</span>
                    <x-au-select name="prep_teacher" class="pay-au-full" placeholder="Choisir un enseignant…" icon="fa-user-tie" :searchable="true"
                        :options="$teachers->pluck('name','id')->toArray()"
                        x-on:change="prep.teacher_id = $event.target.value; preview=null" />
                </div>
                <div class="pay-grid2">
                    <div class="pay-field">
                        <span class="pay-field-lbl">Mois</span>
                        <x-au-select name="prep_mois" class="pay-au-full" :value="(string) $filtres['mois']" :options="$moisOptions" icon="fa-calendar"
                            x-on:change="prep.mois = $event.target.value; preview=null" />
                    </div>
                    <div class="pay-field">
                        <span class="pay-field-lbl">Année</span>
                        <x-au-select name="prep_annee" class="pay-au-full" :value="(string) $filtres['annee']"
                            :options="collect(range(now()->year - 3, now()->year + 1))->mapWithKeys(fn($y) => [$y => (string) $y])->toArray()" icon="fa-calendar-days"
                            x-on:change="prep.annee = $event.target.value; preview=null" />
                    </div>
                </div>
                <div style="margin-top:.8rem;text-align:right;">
                    <button type="button" class="pay-btn pay-btn--primary" :disabled="calculating || !prep.teacher_id" @click="calculate()">
                        <span x-show="!calculating"><i class="fas fa-bolt"></i> Calculer</span>
                        <span x-show="calculating" x-cloak><i class="fas fa-circle-notch fa-spin"></i> Calcul…</span>
                    </button>
                </div>

                {{-- Aperçu --}}
                <template x-if="preview">
                    <div>
                        <div class="pay-prev">
                            <div class="pay-prev-sec">
                                <div class="pay-prev-h">Gains — heures réalisées × taux</div>
                                <template x-for="(g, i) in preview.gains" :key="'g'+i">
                                    <div class="pay-line">
                                        <span class="pay-line-lbl">
                                            <span x-text="g.libelle"></span>
                                            <span class="pay-line-detail" x-show="g.heures" x-text="fmtH(g.heures) + ' × ' + fmt(g.taux)"></span>
                                        </span>
                                        <span class="pay-line-amt" x-text="fmt(g.montant) + ' FCFA'"></span>
                                    </div>
                                </template>
                                <div class="pay-line" style="border-top:1px solid #f1f5f9;margin-top:.3rem;padding-top:.4rem;">
                                    <span class="pay-line-lbl"><strong>Brut</strong></span>
                                    <span class="pay-line-amt" x-text="fmt(preview.brut) + ' FCFA'"></span>
                                </div>
                            </div>
                            <div class="pay-prev-sec">
                                <div class="pay-prev-h">Retenues (dont impôt ITS)</div>
                                <template x-for="(r, i) in preview.retenues" :key="'r'+i">
                                    <div class="pay-line">
                                        <span class="pay-line-lbl" x-text="r.libelle"></span>
                                        <span class="pay-line-amt pay-line-amt--neg" x-text="'− ' + fmt(r.montant) + ' FCFA'"></span>
                                    </div>
                                </template>
                                <div class="pay-override">
                                    <div>
                                        <span class="pay-field-lbl">Impôt ITS (modifiable)</span>
                                        <input type="number" min="0" step="100" class="pay-input" x-model="prep.impot_its" @change="calculate()" :placeholder="preview.impot_its">
                                    </div>
                                    <div>
                                        <span class="pay-field-lbl">CNPS (modifiable)</span>
                                        <input type="number" min="0" step="100" class="pay-input" x-model="prep.cnps" @change="calculate()" :placeholder="preview.cnps">
                                    </div>
                                </div>
                                <div class="pay-help">Laissez vide pour conserver le calcul automatique (barème ITS + taux CNPS configurés).</div>

                                {{-- Primes manuelles --}}
                                <div style="margin-top:.8rem;">
                                    <span class="pay-field-lbl">Primes</span>
                                    <template x-for="(p, i) in prep.primes" :key="'p'+i">
                                        <div class="pay-mini-row">
                                            <input type="text" class="pay-input" placeholder="Libellé" x-model="p.libelle">
                                            <input type="number" min="0" step="100" class="pay-input" style="max-width:130px;" placeholder="Montant" x-model="p.montant">
                                            <button type="button" class="pay-mini-del" @click="prep.primes.splice(i,1); calculate()"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </template>
                                    <button type="button" class="pay-mini-add" style="margin-top:.4rem;" @click="prep.primes.push({libelle:'',montant:''})"><i class="fas fa-plus"></i> Ajouter une prime</button>
                                </div>

                                {{-- Retenues manuelles --}}
                                <div style="margin-top:.8rem;">
                                    <span class="pay-field-lbl">Autres retenues (avance…)</span>
                                    <template x-for="(r, i) in prep.retenues" :key="'mr'+i">
                                        <div class="pay-mini-row">
                                            <input type="text" class="pay-input" placeholder="Libellé" x-model="r.libelle">
                                            <input type="number" min="0" step="100" class="pay-input" style="max-width:130px;" placeholder="Montant" x-model="r.montant">
                                            <button type="button" class="pay-mini-del" @click="prep.retenues.splice(i,1); calculate()"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </template>
                                    <button type="button" class="pay-mini-add" style="margin-top:.4rem;" @click="prep.retenues.push({type:'avance',libelle:'',montant:''})"><i class="fas fa-plus"></i> Ajouter une retenue</button>
                                </div>
                            </div>
                            <div class="pay-net">
                                <span class="pay-net-lbl">Net à payer</span>
                                <span class="pay-net-val" x-text="fmt(preview.net) + ' FCFA'"></span>
                            </div>
                        </div>
                        <div class="pay-help" x-show="exists" x-cloak><i class="fas fa-circle-info"></i> Un bulletin existe déjà pour cette période — il sera mis à jour (remis en brouillon).</div>
                    </div>
                </template>
            </div>
            <div class="pay-modal-foot">
                <button type="button" class="pay-btn pay-btn--ghost" @click="showPrepare=false">Annuler</button>
                <button type="button" class="pay-btn pay-btn--primary" :disabled="!preview || saving || locked" @click="save()">
                    <span x-show="!saving"><i class="fas fa-floppy-disk"></i> Enregistrer le bulletin</span>
                    <span x-show="saving" x-cloak><i class="fas fa-circle-notch fa-spin"></i> Enregistrement…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Modal Configuration ===== --}}
    @if($canConfigure)
    <div class="pay-modal-overlay" x-show="showConfig" x-cloak @keydown.escape.window="showConfig=false" style="display:none;">
        <div class="pay-modal pay-modal--narrow" @click.outside="showConfig=false">
            <div class="pay-modal-head">
                <div class="pay-modal-head-ico"><i class="fas fa-sliders"></i></div>
                <div>
                    <div class="pay-modal-title">Paramètres de paie</div>
                    <div class="pay-modal-sub">Barème de l'impôt (ITS) et taux CNPS</div>
                </div>
                <button type="button" class="pay-modal-close" @click="showConfig=false"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="pay-modal-body">
                <span class="pay-field-lbl">Taux CNPS (part salariale, %)</span>
                <input type="number" min="0" max="100" step="0.1" class="pay-input" x-model="config.cnps_taux">

                <div style="margin-top:1rem;">
                    <span class="pay-field-lbl">Barème ITS (tranches progressives)</span>
                    <template x-for="(t, i) in config.bareme" :key="'t'+i">
                        <div class="pay-mini-row">
                            <input type="number" min="0" step="1000" class="pay-input" placeholder="De (FCFA)" x-model="t.from">
                            <input type="number" min="0" step="1000" class="pay-input" placeholder="À (vide = ∞)" x-model="t.to">
                            <input type="number" min="0" max="100" step="0.5" class="pay-input" style="max-width:90px;" placeholder="Taux %" x-model="t.taux">
                            <button type="button" class="pay-mini-del" @click="config.bareme.splice(i,1)"><i class="fas fa-trash"></i></button>
                        </div>
                    </template>
                    <button type="button" class="pay-mini-add" style="margin-top:.4rem;" @click="config.bareme.push({from:'',to:'',taux:''})"><i class="fas fa-plus"></i> Ajouter une tranche</button>
                </div>
                <div class="pay-help">L'impôt calculé reste une suggestion modifiable sur chaque bulletin.</div>
            </div>
            <div class="pay-modal-foot">
                <button type="button" class="pay-btn pay-btn--ghost" @click="showConfig=false">Annuler</button>
                <button type="button" class="pay-btn pay-btn--primary" :disabled="savingConfig" @click="saveConfig()">
                    <span x-show="!savingConfig"><i class="fas fa-floppy-disk"></i> Enregistrer</span>
                    <span x-show="savingConfig" x-cloak><i class="fas fa-circle-notch fa-spin"></i> …</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function salairesPage() {
    return {
        urls: {},
        loading: false,
        filters: { preset: @json($filtres['preset']), mois: @json((string) $filtres['mois']), annee: @json((string) $filtres['annee']), from: @json($filtres['from'] ?? ''), to: @json($filtres['to'] ?? ''), statut: @json($filtres['statut'] ?? ''), q: '' },
        periodLabel: @json($periodLabel),
        showPrepare: false, showConfig: false,
        calculating: false, saving: false, savingConfig: false,
        preview: null, exists: false, locked: false,
        prep: { teacher_id: '', mois: @json((string) $filtres['mois']), annee: @json((string) $filtres['annee']), impot_its: '', cnps: '', primes: [], retenues: [] },
        config: { cnps_taux: @json((string) $cnpsTaux), bareme: @json(collect($bareme)->map(fn($t) => ['from' => (string) $t['from'], 'to' => $t['to'] === null ? '' : (string) $t['to'], 'taux' => (string) $t['taux']])->values()) },

        init() {
            const d = this.$root.dataset;
            this.urls = { data: d.urlData, prepare: d.urlPrepare, store: d.urlStore, config: d.urlConfig };
            this.$watch('filters.mois', () => { if (this.filters.preset !== 'custom') this.applyFilters(); });
            this.$watch('filters.annee', () => { if (this.filters.preset !== 'custom') this.applyFilters(); });
            this.$watch('filters.statut', () => this.applyFilters());
            this.bindFilterSelects();
            // Mois cliquable sur une ligne du récap → ouvre le modal pré-rempli sur CE mois.
            window.addEventListener('paie:prepare-teacher', (ev) => this.preparePour(ev.detail.id, ev.detail.mois, ev.detail.annee));
            // Filtres repris par les exports PDF/Excel.
            window.exportFilters = () => ({ preset: this.filters.preset, mois: this.filters.mois, annee: this.filters.annee, from: this.filters.from, to: this.filters.to, statut: this.filters.statut, q: this.filters.q });
        },

        setPreset(p) {
            this.filters.preset = p;
            if (p !== 'custom' || (this.filters.from && this.filters.to)) this.applyFilters();
        },

        bindFilterSelects() {
            const map = { mois_filter: 'mois', annee_filter: 'annee', statut_filter: 'statut' };
            Object.keys(map).forEach((name) => {
                const el = document.querySelector('[name="' + name + '"]');
                if (el) el.addEventListener('change', (e) => { this.filters[map[name]] = e.target.value; });
            });
        },

        csrf() { return document.querySelector('meta[name="csrf-token"]').content; },
        fmt(v) { return new Intl.NumberFormat('fr-FR').format(Math.round(v || 0)); },
        fmtH(v) { const h = Math.floor(v); const m = Math.round((v - h) * 60); return h + 'h' + (m > 0 ? String(m).padStart(2, '0') : ''); },

        async applyFilters() {
            this.loading = true;
            try {
                const p = new URLSearchParams(this.filters);
                const res = await fetch(this.urls.data + '?' + p.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const d = await res.json();
                document.getElementById('payList').innerHTML = d.list_html;
                document.getElementById('payKpis').innerHTML = d.kpis_html;
                if (d.period_label) this.periodLabel = d.period_label;
            } catch (e) { this.toast('error', e.message); } finally { this.loading = false; }
        },

        // Ouvre le modal de préparation pré-rempli pour un enseignant + un mois précis (clic sur la pastille mensuelle).
        preparePour(teacherId, mois, annee) {
            this.preview = null; this.exists = false; this.locked = false;
            this.prep = { teacher_id: String(teacherId), mois: String(mois || this.filters.mois), annee: String(annee || this.filters.annee), impot_its: '', cnps: '', primes: [], retenues: [] };
            this.showPrepare = true;
            // pré-sélectionne enseignant + mois + année dans les pickers du modal, puis calcule.
            this.$nextTick(() => {
                const setSel = (name, val) => {
                    const sel = document.querySelector('select[name="' + name + '"]');
                    if (sel) { sel.value = String(val); sel.dispatchEvent(new Event('change', { bubbles: true })); }
                };
                setSel('prep_teacher', teacherId);
                setSel('prep_mois', this.prep.mois);
                setSel('prep_annee', this.prep.annee);
                this.calculate();
            });
        },

        openPrepare() {
            this.preview = null; this.exists = false; this.locked = false;
            this.prep = { teacher_id: '', mois: this.filters.mois, annee: this.filters.annee, impot_its: '', cnps: '', primes: [], retenues: [] };
            this.showPrepare = true;
        },

        async calculate() {
            if (!this.prep.teacher_id) return;
            this.calculating = true;
            try {
                const res = await fetch(this.urls.prepare, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify(this.payload()),
                });
                if (!res.ok) { const b = await res.json().catch(() => ({})); throw new Error(b.message || ('Erreur ' + res.status)); }
                const d = await res.json();
                this.preview = d.preview; this.exists = d.exists; this.locked = d.locked;
                if (d.locked) this.toast('error', 'Ce bulletin est payé/annulé : non modifiable.');
            } catch (e) { this.toast('error', e.message); } finally { this.calculating = false; }
        },

        payload() {
            return {
                teacher_id: this.prep.teacher_id, mois: this.prep.mois, annee: this.prep.annee,
                impot_its: this.prep.impot_its === '' ? null : this.prep.impot_its,
                cnps: this.prep.cnps === '' ? null : this.prep.cnps,
                primes: this.prep.primes.filter((p) => p.libelle && p.montant),
                retenues: this.prep.retenues.filter((r) => r.libelle && r.montant),
            };
        },

        async save() {
            if (!this.preview || this.locked) return;
            this.saving = true;
            try {
                const res = await fetch(this.urls.store, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify(this.payload()),
                });
                if (!res.ok) { const b = await res.json().catch(() => ({})); throw new Error(b.message || ('Erreur ' + res.status)); }
                const d = await res.json();
                this.toast('success', d.message || 'Enregistré.');
                window.location.href = d.redirect;
            } catch (e) { this.toast('error', e.message); } finally { this.saving = false; }
        },

        openConfig() { this.showConfig = true; },

        async saveConfig() {
            this.savingConfig = true;
            try {
                const bareme = this.config.bareme
                    .filter((t) => t.from !== '' && t.taux !== '')
                    .map((t) => ({ from: Number(t.from), to: t.to === '' ? null : Number(t.to), taux: Number(t.taux) }));
                const res = await fetch(this.urls.config, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify({ cnps_taux: Number(this.config.cnps_taux), bareme }),
                });
                if (!res.ok) { const b = await res.json().catch(() => ({})); throw new Error(b.message || ('Erreur ' + res.status)); }
                const d = await res.json();
                this.toast('success', d.message || 'Enregistré.');
                this.showConfig = false;
            } catch (e) { this.toast('error', e.message); } finally { this.savingConfig = false; }
        },

        toast(type, message) { window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } })); },
    };
}
</script>
@endpush
