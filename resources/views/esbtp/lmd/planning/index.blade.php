@extends('layouts.app')

@section('title', 'Planning LMD')

@push('styles')
<style>
    /* ====== Planning LMD — namespace lp-* ====== */
    .lp-page { padding: 1rem 0; }

    /* Hero gradient KLASSCI 2-rangs */
    .lp-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .lp-hero-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem;
    }
    .lp-hero-left { display: flex; align-items: center; gap: 1rem; }
    .lp-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .lp-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .lp-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }

    .lp-kpis {
        display: flex; gap: .75rem;
        margin-top: 1.5rem; flex-wrap: wrap;
    }
    .lp-kpi {
        flex: 1; min-width: 140px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px;
        padding: .9rem 1rem;
        display: flex; align-items: center; gap: .75rem;
    }
    .lp-kpi-icon {
        width: 36px; height: 36px; border-radius: 10px;
        background: rgba(255,255,255,.12);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem;
    }
    .lp-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
    .lp-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    /* Filters */
    .lp-filters {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    }
    .lp-filters-row { display: flex; gap: .75rem; flex-wrap: wrap; align-items: flex-end; }
    .lp-filter-group {
        flex: 1 1 220px;
        min-width: 200px;
        display: flex;
        flex-direction: column;
    }
    .lp-filter-label {
        display: block;
        font-size: .68rem; font-weight: 600;
        color: #64748b; text-transform: uppercase;
        letter-spacing: .04em; margin-bottom: .35rem;
    }
    /* Force <x-au-select> to fill the lp-filter-group (defaults to inline-flex/fit-content) */
    .lp-filter-group .au-select,
    .lp-filter-group .au-select-trigger { width: 100%; }

    /* Card listing */
    .lp-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        overflow: hidden;
    }
    .lp-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex; align-items: center; justify-content: space-between;
        gap: 1rem;
    }
    .lp-card-title {
        display: flex; align-items: center; gap: .75rem;
        font-size: 1rem; font-weight: 600; color: #1e293b; margin: 0;
    }
    .lp-card-title-icon {
        width: 32px; height: 32px; border-radius: 9px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .85rem;
    }
    .lp-card-meta { font-size: .8rem; color: #64748b; }

    /* Table */
    .lp-table { width: 100%; border-collapse: collapse; }
    .lp-table th {
        padding: .65rem 1rem; text-align: left;
        font-size: .68rem; font-weight: 600;
        color: #64748b; text-transform: uppercase;
        letter-spacing: .04em; background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .lp-table th.lp-th-num { text-align: right; }
    .lp-ue-row {
        background: #f8fafc;
        cursor: pointer;
        transition: background .15s ease;
    }
    .lp-ue-row:hover { background: #f1f5f9; }
    .lp-ue-row td {
        padding: .85rem 1rem;
        font-weight: 600;
        color: #0f172a;
        border-bottom: 1px solid #e2e8f0;
    }
    .lp-ue-caret {
        display: inline-block;
        width: 1rem; text-align: center;
        color: #0453cb;
        transition: transform .2s ease;
    }
    .lp-ue-caret--open { transform: rotate(90deg); }
    .lp-ue-code {
        font-family: 'SF Mono', Consolas, monospace;
        font-size: .78rem;
        background: rgba(4,83,203,.08);
        color: #0453cb;
        padding: .15rem .5rem;
        border-radius: 6px;
        margin-right: .5rem;
    }
    .lp-ue-code--virtual {
        background: rgba(100,116,139,.1);
        color: #64748b;
        font-style: italic;
        font-family: inherit;
    }

    .lp-ecue-row td {
        padding: .65rem 1rem;
        font-size: .87rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
    }
    .lp-ecue-indent { padding-left: 2.5rem !important; }
    .lp-ecue-code {
        font-family: 'SF Mono', Consolas, monospace;
        font-size: .76rem;
        color: #64748b;
        margin-right: .5rem;
    }
    .lp-volume {
        font-variant-numeric: tabular-nums;
        text-align: right;
        color: #1e293b;
        font-weight: 500;
    }
    .lp-volume--zero { color: #cbd5e1; }
    .lp-volume--total { font-weight: 700; color: #0453cb; }
    .lp-no-planif {
        font-size: .72rem;
        color: #94a3b8;
        font-style: italic;
    }

    .lp-type-chip {
        display: inline-block;
        padding: .15rem .55rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        background: rgba(4,83,203,.08);
        color: #0453cb;
        border: 1px solid rgba(4,83,203,.15);
    }

    /* Empty state */
    .lp-empty {
        background: #fff;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 3rem 2rem;
        text-align: center;
    }
    .lp-empty-icon {
        width: 64px; height: 64px;
        border-radius: 16px;
        background: rgba(4,83,203,.08);
        color: #0453cb;
        display: inline-flex;
        align-items: center; justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    .lp-empty h3 {
        font-size: 1.15rem; font-weight: 600;
        color: #1e293b; margin: 0 0 .5rem;
    }
    .lp-empty p {
        color: #64748b; font-size: .9rem; margin: 0 0 1.25rem;
        max-width: 480px; margin-left: auto; margin-right: auto;
    }
    .lp-empty-cta {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .55rem 1.1rem;
        background: #0453cb;
        color: #fff; font-size: .85rem; font-weight: 600;
        border-radius: 10px;
        text-decoration: none;
        transition: all .2s ease;
    }
    .lp-empty-cta:hover {
        background: #033a8e;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(4,83,203,.25);
    }

    @@media (max-width: 768px) {
        .lp-hero { padding: 1.5rem 1.25rem 1rem; }
        .lp-hero h1 { font-size: 1.2rem; }
        .lp-table th, .lp-table td { padding: .5rem .75rem; font-size: .8rem; }
        .lp-ecue-indent { padding-left: 1.5rem !important; }
    }
</style>
@endpush

@section('content')
<div class="lp-page">
    {{-- Hero rangée 1 + KPIs --}}
    <div class="lp-hero">
        <div class="lp-hero-top">
            <div class="lp-hero-left">
                <div class="lp-hero-icon"><i class="fas fa-sitemap"></i></div>
                <div>
                    <h1>Planning LMD</h1>
                    <p>Maquette pédagogique UE / ECUE par parcours et semestre</p>
                </div>
            </div>
        </div>
        <div class="lp-kpis">
            <div class="lp-kpi">
                <div class="lp-kpi-icon"><i class="fas fa-cubes"></i></div>
                <div>
                    <div class="lp-kpi-value">{{ $kpis['ue_count'] }}</div>
                    <div class="lp-kpi-label">Unités d'enseignement</div>
                </div>
            </div>
            <div class="lp-kpi">
                <div class="lp-kpi-icon"><i class="fas fa-list"></i></div>
                <div>
                    <div class="lp-kpi-value">{{ $kpis['ecue_count'] }}</div>
                    <div class="lp-kpi-label">ECUE / matières</div>
                </div>
            </div>
            <div class="lp-kpi">
                <div class="lp-kpi-icon"><i class="fas fa-award"></i></div>
                <div>
                    <div class="lp-kpi-value">{{ $kpis['cect_total'] }}</div>
                    <div class="lp-kpi-label">Crédits CECT total</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('esbtp.lmd.planning.index') }}" class="lp-filters" id="lpFilters">
        <div class="lp-filters-row">
            <div class="lp-filter-group">
                <label class="lp-filter-label" for="lp-parcours">Parcours</label>
                <x-au-select
                    name="parcours_id"
                    icon="fa-route"
                    placeholder="Tous les parcours"
                    :value="$filters['parcours_id']"
                    :searchable="$parcours->count() > 8"
                    :options="$parcours->mapWithKeys(fn ($p) => [$p->id => $p->label_complet])->all()"
                    onchange="document.getElementById('lpFilters').submit()" />
            </div>
            <div class="lp-filter-group">
                <label class="lp-filter-label">Niveau</label>
                <x-au-select
                    name="niveau_id"
                    icon="fa-layer-group"
                    placeholder="Tous niveaux"
                    :value="$filters['niveau_id']"
                    :options="$niveaux->mapWithKeys(fn ($n) => [$n->id => $n->name])->all()"
                    onchange="document.getElementById('lpFilters').submit()" />
            </div>
            <div class="lp-filter-group">
                <label class="lp-filter-label">Semestre</label>
                <x-au-select
                    name="semestre"
                    icon="fa-calendar-alt"
                    placeholder="Tous semestres"
                    :value="$filters['semestre']"
                    :options="collect($semestres)->mapWithKeys(fn ($s) => [$s => 'Semestre ' . $s])->all()"
                    onchange="document.getElementById('lpFilters').submit()" />
            </div>
        </div>
    </form>

    {{-- Listing ou empty state --}}
    @if($parcours->isEmpty())
        <div class="lp-empty">
            <div class="lp-empty-icon"><i class="fas fa-route"></i></div>
            <h3>Aucun parcours configuré</h3>
            <p>Pour utiliser le planning LMD, commencez par configurer au moins un domaine, une mention et un parcours.</p>
            <a href="{{ route('esbtp.lmd.parcours-domain.index') }}" class="lp-empty-cta">
                <i class="fas fa-plus"></i> Configurer un parcours
            </a>
        </div>
    @elseif(!$parcoursSelected)
        <div class="lp-empty">
            <div class="lp-empty-icon"><i class="fas fa-hand-pointer"></i></div>
            <h3>Sélectionnez un parcours</h3>
            <p>Choisissez un parcours dans le filtre ci-dessus pour voir sa maquette pédagogique (UE et ECUE par semestre).</p>
        </div>
    @elseif($rows->isEmpty())
        <div class="lp-empty">
            <div class="lp-empty-icon"><i class="fas fa-cubes"></i></div>
            <h3>Aucune UE liée à ce parcours</h3>
            <p>Le parcours <strong>{{ $parcoursSelected->name }}</strong> n'a pas encore d'unités d'enseignement associées{{ $filters['semestre'] ? ' pour le semestre ' . $filters['semestre'] : '' }}.</p>
            <a href="{{ route('esbtp.lmd.ue.index', ['parcours_id' => $parcoursSelected->id]) }}" class="lp-empty-cta">
                <i class="fas fa-link"></i> Lier des UE au parcours
            </a>
        </div>
    @else
        <div class="lp-card" x-data="{ expanded: {} }">
            <div class="lp-card-header">
                <h2 class="lp-card-title">
                    <span class="lp-card-title-icon"><i class="fas fa-book"></i></span>
                    {{ $parcoursSelected->name }}
                </h2>
                <span class="lp-card-meta">{{ $rows->count() }} UE · {{ $kpis['ecue_count'] }} ECUE</span>
            </div>

            <div style="overflow-x: auto;">
                <table class="lp-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Intitulé</th>
                            <th>Type</th>
                            <th class="lp-th-num">CM</th>
                            <th class="lp-th-num">TD</th>
                            <th class="lp-th-num">TP</th>
                            <th class="lp-th-num">Projet</th>
                            <th class="lp-th-num">TPE</th>
                            <th class="lp-th-num">Total</th>
                            <th class="lp-th-num">CECT</th>
                            <th>Enseignant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $idx => $row)
                            @php
                                $ue = $row['ue'];
                                $hasCode = !empty($ue->code);
                                $typeLabel = $ue->type_ue?->label() ?? '—';
                            @endphp
                            <tr class="lp-ue-row" x-on:click="expanded[{{ $idx }}] = !expanded[{{ $idx }}]">
                                <td>
                                    <span class="lp-ue-caret" :class="{ 'lp-ue-caret--open': expanded[{{ $idx }}] }">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                    @if($hasCode)
                                        <span class="lp-ue-code">{{ $ue->code }}</span>
                                    @else
                                        <span class="lp-ue-code lp-ue-code--virtual">virtuelle</span>
                                    @endif
                                    {{ $ue->name }}
                                </td>
                                <td><span class="lp-type-chip">{{ $typeLabel }}</span></td>
                                <td colspan="5" class="lp-volume">{{ $row['ecues']->count() }} ECUE</td>
                                <td class="lp-volume lp-volume--total">—</td>
                                <td class="lp-volume lp-volume--total">{{ $row['cect'] }}</td>
                                <td><span class="lp-no-planif">UE</span></td>
                            </tr>
                            @foreach($row['ecues'] as $entry)
                                @php
                                    $ecue = $entry['ecue'];
                                    $planif = $entry['planif'];
                                @endphp
                                <tr class="lp-ecue-row" x-show="expanded[{{ $idx }}]" x-cloak>
                                    <td class="lp-ecue-indent">
                                        @if(!empty($ecue->code))
                                            <span class="lp-ecue-code">{{ $ecue->code }}</span>
                                        @endif
                                        {{ $ecue->name }}
                                    </td>
                                    <td>—</td>
                                    @if($planif)
                                        <td class="lp-volume {{ $planif->volume_horaire_cm ? '' : 'lp-volume--zero' }}">{{ $planif->volume_horaire_cm ?? 0 }}</td>
                                        <td class="lp-volume {{ $planif->volume_horaire_td ? '' : 'lp-volume--zero' }}">{{ $planif->volume_horaire_td ?? 0 }}</td>
                                        <td class="lp-volume {{ $planif->volume_horaire_tp ? '' : 'lp-volume--zero' }}">{{ $planif->volume_horaire_tp ?? 0 }}</td>
                                        <td class="lp-volume {{ $planif->volume_horaire_projet ? '' : 'lp-volume--zero' }}">{{ $planif->volume_horaire_projet ?? 0 }}</td>
                                        <td class="lp-volume {{ $planif->volume_horaire_tpe ? '' : 'lp-volume--zero' }}">{{ $planif->volume_horaire_tpe ?? 0 }}</td>
                                        <td class="lp-volume lp-volume--total">{{ $planif->volume_horaire_total }}</td>
                                        <td class="lp-volume">{{ $ecue->credit_ecue ?? '—' }}</td>
                                        <td>{{ $planif->enseignantPrincipal?->name ?? '—' }}</td>
                                    @else
                                        <td colspan="6" class="lp-no-planif">Non planifié</td>
                                        <td class="lp-volume">{{ $ecue->credit_ecue ?? '—' }}</td>
                                        <td class="lp-no-planif">—</td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
