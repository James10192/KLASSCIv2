@extends('layouts.app')

@section('title', 'Unités d\'Enseignement (UE) — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD UE Index — Full AJAX with Alpine.js
       Prefix: lu- (lmd-ue)
       ══════════════════════════════════════════════ */

    .lu-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }

    /* ── Hero ── */
    .lu-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 2rem 2.5rem 1.5rem;
        color: #fff; margin-bottom: 1.5rem; overflow: hidden;
        animation: lu-fadeDown .5s ease-out;
    }
    .lu-hero::before { content: ''; position: absolute; top: -60%; right: -10%; width: 420px; height: 420px; background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%); pointer-events: none; }
    .lu-hero::after { content: ''; position: absolute; bottom: -40%; left: 5%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%); pointer-events: none; }
    .lu-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; position: relative; z-index: 1; }
    .lu-hero-left { display: flex; align-items: center; gap: 1rem; }
    .lu-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; border: 1px solid rgba(255,255,255,.15); flex-shrink: 0; }
    .lu-hero-info h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem; color: #fff; letter-spacing: -.02em; }
    .lu-hero-info p { margin: 0; opacity: .8; font-size: .88rem; }
    .lu-hero-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1.1rem; border-radius: 10px; font-size: .84rem; font-weight: 600; border: 1.5px solid rgba(255,255,255,.3); color: #fff; background: rgba(255,255,255,.08); text-decoration: none; transition: all .2s; backdrop-filter: blur(4px); cursor: pointer; }
    .lu-hero-btn:hover { background: rgba(255,255,255,.18); color: #fff; }
    .lu-hero-btn--solid { background: #fff; color: #0453cb; border-color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.12); }
    .lu-hero-btn--solid:hover { background: #edf2fc; color: #0453cb; }
    .lu-hero-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; position: relative; z-index: 1; flex-wrap: wrap; }
    .lu-kpi { flex: 1; min-width: 150px; background: rgba(255,255,255,.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem; transition: background .2s; }
    .lu-kpi:hover { background: rgba(255,255,255,.15); }
    .lu-kpi-icon { width: 38px; height: 38px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; }
    .lu-kpi--ue .lu-kpi-icon { background: rgba(255,255,255,.18); color: #fff; }
    .lu-kpi--ecue .lu-kpi-icon { background: rgba(129,140,248,.25); color: #a5b4fc; }
    .lu-kpi--credits .lu-kpi-icon { background: rgba(16,185,129,.25); color: #6ee7b7; }
    .lu-kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1; color: #fff; }
    .lu-kpi-label { font-size: .75rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    /* ── Filters ── */
    .lu-filters { background: #fff; border-radius: 14px; padding: 1rem 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03); border: 1px solid #e8ecf1; display: flex; align-items: flex-end; gap: .85rem; flex-wrap: wrap; animation: lu-fadeUp .45s ease-out .1s both; }
    .lu-filter-group { display: flex; flex-direction: column; gap: .3rem; flex: 1; min-width: 140px; }
    .lu-filter-label { font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
    .lu-filter-control { padding: .5rem .75rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: .86rem; color: #1e293b; background: #f8fafc; transition: all .2s; width: 100%; }
    .lu-filter-control:focus { outline: none; border-color: #0453cb; background: #fff; box-shadow: 0 0 0 3px rgba(4,83,203,.08); }

    /* ── Table card ── */
    .lu-table-card { background: #fff; border-radius: 14px; border: 1px solid #e8ecf1; box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03); overflow: hidden; animation: lu-fadeUp .45s ease-out .2s both; }
    .lu-table-header { padding: 1.15rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; }
    .lu-table-title { font-size: 1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: .5rem; }
    .lu-table-title i { color: #0453cb; font-size: .9rem; }
    .lu-table-count { font-size: .8rem; color: #94a3b8; font-weight: 500; }
    .lu-table-wrapper { overflow-x: auto; }
    .lu-table { width: 100%; border-collapse: collapse; }
    .lu-table thead th { padding: .75rem 1rem; font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; background: #fafbfc; border-bottom: 1px solid #f1f5f9; white-space: nowrap; }
    .lu-table tbody tr { transition: background .15s; border-bottom: 1px solid #f8fafc; }
    .lu-table tbody td { padding: .8rem 1rem; font-size: .87rem; color: #475569; vertical-align: middle; }
    .lu-ue-row { cursor: pointer; user-select: none; }
    .lu-ue-row:hover { background: #f8fbff; }
    .lu-arrow { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 6px; background: #f1f5f9; color: #0453cb; font-size: .65rem; transition: transform .2s, background .2s; }
    .lu-arrow.lu-open { transform: rotate(90deg); background: #e0ecff; }
    .lu-code { font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace; font-size: .82rem; font-weight: 600; color: #1e293b; letter-spacing: .02em; }
    .lu-name { font-weight: 600; color: #1e293b; }
    .lu-type-badge { display: inline-flex; align-items: center; padding: .2rem .6rem; border-radius: 20px; font-size: .72rem; font-weight: 600; letter-spacing: .01em; }
    .lu-type--fondamentale { background: #dbeafe; color: #1e40af; }
    .lu-type--methodologique { background: #d1fae5; color: #065f46; }
    .lu-type--decouverte { background: #fef3c7; color: #92400e; }
    .lu-type--transversale { background: #e0e7ff; color: #3730a3; }
    .lu-credit-pill { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; padding: .15rem .5rem; border-radius: 6px; font-size: .82rem; font-weight: 700; background: #ecfdf5; color: #059669; }
    .lu-ecue-count { display: inline-flex; align-items: center; justify-content: center; min-width: 26px; padding: .15rem .45rem; border-radius: 6px; font-size: .82rem; font-weight: 700; background: #f1f5f9; color: #334155; }
    .lu-actions { display: flex; gap: .3rem; justify-content: flex-end; }
    .lu-act { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e8ecf1; background: #fff; color: #64748b; font-size: .8rem; cursor: pointer; transition: all .2s; text-decoration: none; }
    .lu-act:hover { color: #fff; text-decoration: none; }
    .lu-act--edit:hover { background: #0453cb; border-color: #0453cb; color: #fff; }
    .lu-act--delete:hover { background: #dc2626; border-color: #dc2626; color: #fff; }
    .lu-sub-row td { background: #fafbfc; border-top: 1px dashed #e8ecf1; padding-top: .6rem !important; padding-bottom: .6rem !important; }
    .lu-ecue-indent { display: inline-flex; align-items: center; gap: .4rem; padding-left: 1.5rem; color: #64748b; font-size: .84rem; }
    .lu-ecue-indent::before { content: '└'; color: #cbd5e1; font-size: .9rem; }
    .lu-ecue-code { font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace; font-size: .78rem; color: #64748b; }
    .lu-ecue-coeff { font-size: .78rem; color: #94a3b8; }
    .lu-parcours-badges { display: flex; gap: .25rem; flex-wrap: wrap; }
    .lu-parcours-badge { display: inline-flex; align-items: center; gap: .2rem; padding: .12rem .4rem; border-radius: 5px; font-size: .7rem; font-weight: 600; background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; }
    .lu-parcours-badge-sem { font-size: .6rem; color: #818cf8; }
    .lu-empty { text-align: center; padding: 4rem 2rem; }
    .lu-empty-icon { width: 76px; height: 76px; border-radius: 20px; background: #f1f5f9; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; color: #cbd5e1; margin-bottom: 1.15rem; }
    .lu-empty-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: .4rem; }
    .lu-empty-text { font-size: .88rem; color: #94a3b8; margin-bottom: 1.25rem; }
    .lu-pagination { padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: center; gap: .35rem; }
    .lu-page-btn { padding: .35rem .7rem; border-radius: 7px; font-size: .82rem; font-weight: 600; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: all .15s; }
    .lu-page-btn:hover { background: #f1f5f9; }
    .lu-page-btn--active { background: #0453cb; color: #fff; border-color: #0453cb; }
    .lu-page-btn:disabled { opacity: .4; cursor: default; }

    /* ── Modals ── */
    .lu-modal .modal-content { border-radius: 18px; border: none; box-shadow: 0 25px 80px rgba(0,0,0,.18), 0 8px 24px rgba(4,83,203,.08); overflow: hidden; }
    .lu-modal .modal-header { position: relative; padding: 0; border: none; }
    .lu-modal-hero { padding: 1.75rem 2rem 1.5rem; background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%); color: #fff; position: relative; overflow: hidden; }
    .lu-modal-hero::before { content: ''; position: absolute; top: -50%; right: -15%; width: 320px; height: 320px; background: radial-gradient(circle, rgba(255,255,255,.08) 0%, transparent 70%); pointer-events: none; }
    .lu-modal-hero-top { display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 1; }
    .lu-modal-hero-left { display: flex; align-items: center; gap: .85rem; }
    .lu-modal-icon { width: 46px; height: 46px; border-radius: 12px; background: rgba(255,255,255,.15); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff; flex-shrink: 0; }
    .lu-modal-title { font-size: 1.2rem; font-weight: 700; margin: 0; color: #fff; }
    .lu-modal-subtitle { font-size: .8rem; opacity: .7; margin-top: .15rem; }
    .lu-modal .btn-close { filter: brightness(0) invert(1); opacity: .7; position: relative; z-index: 2; }
    .lu-modal .btn-close:hover { opacity: 1; }
    .lu-modal .modal-body { padding: 1.75rem 2rem; }
    .lu-field-group { background: #f8fafc; border-radius: 12px; border: 1px solid #e8ecf1; padding: 1.25rem; margin-bottom: 1rem; }
    .lu-field-group:last-child { margin-bottom: 0; }
    .lu-field-group-title { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #0453cb; margin-bottom: .85rem; display: flex; align-items: center; gap: .4rem; }
    .lu-field-group-title i { font-size: .65rem; }
    .lu-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem 1.25rem; }
    .lu-field-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .75rem 1.25rem; }
    .lu-field-full { grid-column: 1 / -1; }
    .lu-modal label { font-size: .82rem; font-weight: 600; color: #334155; margin-bottom: .3rem; display: flex; align-items: center; gap: .3rem; }
    .lu-modal label i { font-size: .7rem; color: #94a3b8; }
    .lu-modal .form-control, .lu-modal .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: .55rem .85rem; font-size: .88rem; transition: all .2s; background: #fff; }
    .lu-modal .form-control:focus, .lu-modal .form-select:focus { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08); background: #fff; }
    .lu-modal textarea.form-control { min-height: 70px; resize: vertical; }
    .lu-modal .form-text { font-size: .76rem; color: #94a3b8; margin-top: .25rem; }
    .lu-modal .modal-footer { border-top: 1px solid #e8ecf1; padding: 1rem 2rem; background: #fafbfc; display: flex; gap: .5rem; justify-content: flex-end; }
    .lu-modal-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1.2rem; border-radius: 10px; font-size: .85rem; font-weight: 600; border: none; cursor: pointer; transition: all .2s; }
    .lu-modal-btn--cancel { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
    .lu-modal-btn--cancel:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .lu-modal-btn--submit { background: #0453cb; color: #fff; box-shadow: 0 2px 8px rgba(4,83,203,.2); }
    .lu-modal-btn--submit:hover { background: #0340a0; }
    .lu-modal.fade .modal-dialog { transform: translateY(20px) scale(.98); transition: transform .25s ease-out, opacity .2s; }
    .lu-modal.show .modal-dialog { transform: translateY(0) scale(1); }

    /* Sem chips */
    .lp-sem-chip { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; padding: .15rem .35rem; border-radius: 5px; font-size: .7rem; font-weight: 700; cursor: pointer; transition: all .15s; border: 1px solid #e2e8f0; background: #f8fafc; color: #94a3b8; user-select: none; }
    .lp-sem-chip--on { border-color: #4338ca; background: #4338ca; color: #fff; }
    .lp-sem-chip:hover:not(.lp-sem-chip--on) { background: #eef2ff; border-color: #c7d2fe; color: #4338ca; }

    /* Toast notification */
    .lu-toast { position: fixed; top: 1rem; right: 1rem; z-index: 9999; padding: .65rem 1.1rem; border-radius: 10px; font-size: .85rem; font-weight: 600; color: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.15); transition: all .3s; }
    .lu-toast--success { background: #059669; }
    .lu-toast--error { background: #dc2626; }

    @keyframes lu-fadeDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes lu-fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    [x-cloak] { display: none !important; }

    @media (max-width: 768px) {
        .lu-hero { padding: 1.5rem; border-radius: 14px; }
        .lu-hero-top { flex-direction: column; }
        .lu-hero-kpis { flex-direction: column; }
        .lu-filters { flex-direction: column; align-items: stretch; }
        .lu-field-row, .lu-field-row-3 { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="lu-page" x-data="ueManager()" x-init="loadUes()">

    {{-- ══ Toast ══ --}}
    <div class="lu-toast" :class="toast.type === 'error' ? 'lu-toast--error' : 'lu-toast--success'"
         x-show="toast.show" x-transition x-cloak
         x-text="toast.message"></div>

    {{-- ══ Hero ══ --}}
    <div class="lu-hero">
        <div class="lu-hero-top">
            <div class="lu-hero-left">
                <div class="lu-hero-icon"><i class="fas fa-cubes"></i></div>
                <div class="lu-hero-info">
                    <h1>Unités d'Enseignement</h1>
                    <p>Gestion des UE et de leurs ECUEs associés</p>
                </div>
            </div>
            <div style="display:flex; gap:.5rem;">
                <button type="button" class="lu-hero-btn--solid lu-hero-btn" @click="openCreateModal()">
                    <i class="fas fa-plus"></i>Nouvelle UE
                </button>
            </div>
        </div>
        <div class="lu-hero-kpis">
            <div class="lu-kpi lu-kpi--ue">
                <div class="lu-kpi-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="lu-kpi-value" x-text="pagination.total ?? 0"></div>
                    <div class="lu-kpi-label">Unités (UE)</div>
                </div>
            </div>
            <div class="lu-kpi lu-kpi--ecue">
                <div class="lu-kpi-icon"><i class="fas fa-book"></i></div>
                <div>
                    <div class="lu-kpi-value" x-text="ues.reduce((s,u) => s + (u.matieres_count || 0), 0)"></div>
                    <div class="lu-kpi-label">Matières (ECUE)</div>
                </div>
            </div>
            <div class="lu-kpi lu-kpi--credits">
                <div class="lu-kpi-icon"><i class="fas fa-award"></i></div>
                <div>
                    <div class="lu-kpi-value" x-text="ues.reduce((s,u) => s + (u.credit || 0), 0)"></div>
                    <div class="lu-kpi-label">Crédits ECTS</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Filters ══ --}}
    <div class="lu-filters">
        <div class="lu-filter-group" style="flex:2;">
            <label class="lu-filter-label">Recherche</label>
            <input type="text" class="lu-filter-control" x-model.debounce.400ms="filters.search" placeholder="Code ou intitulé..." @input="loadUes()">
        </div>
        <div class="lu-filter-group">
            <label class="lu-filter-label">Parcours</label>
            <select class="lu-filter-control" x-model="filters.parcours_id" @change="loadUes()">
                <option value="">Tous</option>
                @foreach($parcours as $p)
                    <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="lu-filter-group" style="max-width:160px;">
            <label class="lu-filter-label">Type UE</label>
            <select class="lu-filter-control" x-model="filters.type_ue" @change="loadUes()">
                <option value="">Tous</option>
                <option value="fondamentale">Fondamentale</option>
                <option value="methodologique">Méthodologique</option>
                <option value="decouverte">Découverte</option>
                <option value="transversale">Transversale</option>
            </select>
        </div>
    </div>

    {{-- ══ Table ══ --}}
    <div class="lu-table-card">
        <div class="lu-table-header">
            <div class="lu-table-title"><i class="fas fa-list-ul"></i> Liste des UEs</div>
            <div class="lu-table-count" x-text="(pagination.total ?? 0) + ' unité' + ((pagination.total ?? 0) > 1 ? 's' : '')"></div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" style="text-align:center; padding:3rem; color:#94a3b8;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <div style="margin-top:.75rem;">Chargement...</div>
        </div>

        {{-- Empty --}}
        <template x-if="!loading && ues.length === 0">
            <div class="lu-empty">
                <div class="lu-empty-icon"><i class="fas fa-cubes"></i></div>
                <div class="lu-empty-title">Aucune Unité d'Enseignement</div>
                <div class="lu-empty-text">Créez votre première UE pour structurer votre offre LMD.</div>
                <button type="button" class="lu-hero-btn--solid lu-hero-btn" @click="openCreateModal()" style="display:inline-flex;">
                    <i class="fas fa-plus"></i>Créer une UE
                </button>
            </div>
        </template>

        {{-- Table --}}
        <div class="lu-table-wrapper" x-show="!loading && ues.length > 0">
            <table class="lu-table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Code</th>
                        <th>Intitulé</th>
                        <th>Type UE</th>
                        <th>Parcours</th>
                        <th style="text-align:center;">Crédits</th>
                        <th style="text-align:center;">ECUEs</th>
                        <th style="text-align:right; width:120px;">Actions</th>
                    </tr>
                </thead>
                <template x-for="ue in ues" :key="ue.id">
                    <tbody>
                        {{-- UE row --}}
                        <tr class="lu-ue-row" @click="openRow = openRow === ue.id ? null : ue.id">
                            <td>
                                <span class="lu-arrow" :class="{ 'lu-open': openRow === ue.id }">&#9654;</span>
                            </td>
                            <td><span class="lu-code" x-text="ue.code"></span></td>
                            <td><span class="lu-name" x-text="ue.name"></span></td>
                            <td>
                                <span x-show="ue.type_ue" class="lu-type-badge"
                                      :class="'lu-type--' + ue.type_ue"
                                      x-text="ue.type_ue ? ue.type_ue.charAt(0).toUpperCase() + ue.type_ue.slice(1) : ''"></span>
                                <span x-show="!ue.type_ue" style="color:#94a3b8;">—</span>
                            </td>
                            <td>
                                <div class="lu-parcours-badges" x-show="ue.parcours && ue.parcours.length">
                                    <template x-for="p in (ue.parcours || [])" :key="p.id">
                                        <span class="lu-parcours-badge">
                                            <span x-text="p.code"></span>
                                            <span class="lu-parcours-badge-sem" x-text="'(' + (p.semestres || []).map(s => 'S'+s).join(',') + ')'"></span>
                                        </span>
                                    </template>
                                </div>
                                <span x-show="!ue.parcours || !ue.parcours.length" style="color:#94a3b8; font-size:.8rem;">—</span>
                            </td>
                            <td style="text-align:center;"><span class="lu-credit-pill" x-text="ue.credit ?? 0"></span></td>
                            <td style="text-align:center;"><span class="lu-ecue-count" x-text="ue.matieres_count ?? 0"></span></td>
                            <td @click.stop>
                                <div class="lu-actions">
                                    <button type="button" class="lu-act" title="Ajouter un ECUE" style="color:#059669;" @click="openEcueModal(ue)">
                                        <i class="fas fa-plus-circle"></i>
                                    </button>
                                    <button type="button" class="lu-act" title="Lier à des parcours" style="color:#4338ca;" @click="openLinkParcoursModal(ue)">
                                        <i class="fas fa-route"></i>
                                    </button>
                                    <button type="button" class="lu-act lu-act--edit" title="Modifier" @click="openEditModal(ue)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="lu-act lu-act--delete" title="Supprimer" @click="deleteUe(ue)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        {{-- ECUE sub-rows --}}
                        <template x-for="ecue in (ue.ecues || [])" :key="ecue.id">
                            <tr class="lu-sub-row" x-show="openRow === ue.id" x-cloak>
                                <td></td>
                                <td><span class="lu-ecue-code" x-text="ecue.code || '—'"></span></td>
                                <td><span class="lu-ecue-indent" x-text="ecue.name"></span></td>
                                <td><span class="lu-ecue-coeff" x-text="'Coeff. ' + (ecue.coefficient ?? '—')"></span></td>
                                <td></td>
                                <td style="text-align:center;"><span class="lu-credit-pill" style="background:#f1f5f9; color:#334155;" x-text="ecue.credit ?? '—'"></span></td>
                                <td style="text-align:center;">
                                    <span x-show="ecue.ordre > 0" style="font-size:.75rem; color:#94a3b8;" x-text="'#' + ecue.ordre"></span>
                                </td>
                                <td @click.stop>
                                    <div class="lu-actions">
                                        <button type="button" class="lu-act lu-act--edit" title="Modifier ECUE" @click="openEcueEditModal(ue, ecue)">
                                            <i class="fas fa-pen" style="font-size:.7rem;"></i>
                                        </button>
                                        <button type="button" class="lu-act lu-act--delete" title="Détacher" @click="deleteEcue(ue, ecue)">
                                            <i class="fas fa-unlink" style="font-size:.7rem;"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr class="lu-sub-row" x-show="openRow === ue.id && (!ue.ecues || ue.ecues.length === 0)" x-cloak>
                            <td></td>
                            <td colspan="7" style="color:#94a3b8; font-style:italic; font-size:.84rem;">Aucun ECUE rattaché.</td>
                        </tr>
                    </tbody>
                </template>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="lu-pagination" x-show="pagination.last_page > 1">
            <button class="lu-page-btn" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <template x-for="p in paginationPages()" :key="p">
                <button class="lu-page-btn" :class="{ 'lu-page-btn--active': p === pagination.current_page }"
                        x-text="p" @click="goPage(p)"></button>
            </template>
            <button class="lu-page-btn" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

{{-- ══ MODAL UE — Create / Edit ══ --}}
<div class="modal fade lu-modal" id="modalUE" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="formUE" method="POST">
                @csrf
                <input type="hidden" name="_method" id="ue_method" value="POST">
                <div class="modal-header">
                    <div class="lu-modal-hero w-100">
                        <div class="lu-modal-hero-top">
                            <div class="lu-modal-hero-left">
                                <div class="lu-modal-icon"><i class="fas fa-cubes"></i></div>
                                <div>
                                    <h5 class="lu-modal-title"><span id="modalUETitleText">Nouvelle Unité d'Enseignement</span></h5>
                                    <div class="lu-modal-subtitle">Configurer les paramètres de l'UE</div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="lu-field-group">
                        <div class="lu-field-group-title"><i class="fas fa-circle"></i> Identité de l'UE</div>
                        <div class="lu-field-row">
                            <div>
                                <label for="ue_name"><i class="fas fa-font"></i> Intitulé <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ue_name" name="name" required placeholder="Ex: Technologie de Construction">
                            </div>
                            <div>
                                <label for="ue_code"><i class="fas fa-hashtag"></i> Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ue_code" name="code" required placeholder="Ex: UE:BTCB1" style="font-family: 'SF Mono', 'Consolas', monospace;">
                            </div>
                        </div>
                    </div>
                    <div class="lu-field-group">
                        <div class="lu-field-group-title"><i class="fas fa-circle"></i> Paramètres académiques</div>
                        <div class="lu-field-row-3">
                            <div>
                                <label for="ue_credit"><i class="fas fa-award"></i> Crédits CECT <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="ue_credit" name="credit" required min="1" max="30" value="3">
                            </div>
                            <div>
                                <label for="ue_type_ue"><i class="fas fa-tag"></i> Type UE <span class="text-danger">*</span></label>
                                <select class="form-select" id="ue_type_ue" name="type_ue" required>
                                    <option value="fondamentale">Fondamentale</option>
                                    <option value="methodologique">Méthodologique</option>
                                    <option value="decouverte">Découverte</option>
                                    <option value="transversale">Transversale</option>
                                </select>
                            </div>
                            <div>
                                <label for="ue_semestre"><i class="fas fa-calendar-alt"></i> Semestre <span class="text-danger">*</span></label>
                                <select class="form-select" id="ue_semestre" name="semestre" required>
                                    @for($s = 1; $s <= 10; $s++)
                                        <option value="{{ $s }}">S{{ $s }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="lu-field-group">
                        <div class="lu-field-group-title"><i class="fas fa-circle"></i> Rattachement</div>
                        <div class="lu-field-row">
                            <div>
                                <label for="ue_filiere_id"><i class="fas fa-graduation-cap"></i> Filière</label>
                                <select class="form-select" id="ue_filiere_id" name="filiere_id">
                                    <option value="">— Aucune —</option>
                                    @foreach($filieres as $f)
                                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="ue_niveau_id"><i class="fas fa-layer-group"></i> Niveau</label>
                                <select class="form-select" id="ue_niveau_id" name="niveau_id">
                                    <option value="">— Aucun —</option>
                                    @foreach($niveaux as $n)
                                        <option value="{{ $n->id }}">{{ $n->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="lu-field-group">
                        <div class="lu-field-group-title"><i class="fas fa-circle"></i> Description</div>
                        <label for="ue_description"><i class="fas fa-align-left"></i> Description</label>
                        <textarea class="form-control" id="ue_description" name="description" rows="2" placeholder="Optionnel..."></textarea>
                    </div>
                    <div id="ue_errors" class="alert alert-danger d-none" style="border-radius: 10px; margin-top: 1rem;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lu-modal-btn lu-modal-btn--cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Annuler</button>
                    <button type="submit" class="lu-modal-btn lu-modal-btn--submit" id="ue_submit_btn"><i class="fas fa-save"></i> <span id="ue_submit_text">Enregistrer</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ MODAL ECUE — Create / Edit ══ --}}
<div class="modal fade lu-modal" id="modalECUE" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="ecue_form" method="POST" action="">
                @csrf
                <input type="hidden" name="_method" id="ecue_method" value="POST">
                <div class="modal-header">
                    <div class="lu-modal-hero w-100" style="background: linear-gradient(135deg, #065f46 0%, #059669 50%, #34d399 100%);">
                        <div class="lu-modal-hero-top">
                            <div class="lu-modal-hero-left">
                                <div class="lu-modal-icon"><i class="fas fa-puzzle-piece"></i></div>
                                <div>
                                    <h5 class="lu-modal-title" id="ecue_modal_title">Nouvel ECUE</h5>
                                    <div class="lu-modal-subtitle">UE : <strong id="ecue_ue_label">—</strong></div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div id="ecue_error" style="display:none; padding:.6rem 1rem; border-radius:10px; background:#fef2f2; color:#dc2626; font-size:.85rem; margin-bottom:1rem; border:1px solid #fecaca;"></div>
                    <div class="lu-field-group">
                        <div class="lu-field-row">
                            <div>
                                <label><i class="fas fa-tag"></i> Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="ecue_name" required placeholder="Ex: Résistance des Matériaux">
                            </div>
                            <div>
                                <label><i class="fas fa-barcode"></i> Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="code" id="ecue_code" required placeholder="Ex: RDM101">
                            </div>
                            <div>
                                <label><i class="fas fa-balance-scale"></i> Coefficient</label>
                                <input type="number" class="form-control" name="coefficient_ecue" id="ecue_coefficient" min="0" step="0.5" placeholder="1">
                            </div>
                            <div>
                                <label><i class="fas fa-award"></i> Crédits</label>
                                <input type="number" class="form-control" name="credit_ecue" id="ecue_credit" min="0" placeholder="2">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lu-modal-btn lu-modal-btn--cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Annuler</button>
                    <button type="submit" id="ecue_submit" class="lu-modal-btn lu-modal-btn--submit" style="background:#059669;">
                        <i class="fas fa-check"></i> <span id="ecue_submit_text">Créer l'ECUE</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ MODAL Lier Parcours ══ --}}
<div class="modal fade lu-modal" id="modalLinkParcours" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="lu-modal-hero w-100" style="background: linear-gradient(135deg, #312e81 0%, #4338ca 50%, #6366f1 100%);">
                    <div class="lu-modal-hero-top">
                        <div class="lu-modal-hero-left">
                            <div class="lu-modal-icon"><i class="fas fa-route"></i></div>
                            <div>
                                <h5 class="lu-modal-title">Lier à des Parcours</h5>
                                <div class="lu-modal-subtitle">UE : <strong id="lp_ue_label">—</strong></div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
            </div>
            <div class="modal-body" style="padding:1.5rem;">
                <div id="lp_error" style="display:none; padding:.65rem 1rem; border-radius:10px; background:#fef2f2; color:#dc2626; font-size:.85rem; margin-bottom:1rem; border:1px solid #fecaca;"></div>
                <div id="lp_loading" style="padding:2rem; text-align:center; color:#94a3b8;"><i class="fas fa-spinner fa-spin fa-2x"></i><div style="margin-top:.75rem;">Chargement...</div></div>
                <div id="lp_content" style="display:none;">
                    <p style="font-size:.82rem; color:#64748b; margin-bottom:1rem;"><i class="fas fa-info-circle me-1"></i>Cochez les parcours. Cliquez sur les semestres.</p>
                    <div id="lp_checkboxes" style="display:flex; flex-direction:column; gap:.4rem; max-height:400px; overflow-y:auto;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="lu-modal-btn lu-modal-btn--cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Annuler</button>
                <button type="button" class="lu-modal-btn lu-modal-btn--submit" id="lp_submit" style="background:#4338ca;">
                    <i class="fas fa-check"></i> <span id="lp_submit_text">Enregistrer</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';
const BASE = '/esbtp/lmd/ue';

function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function ueManager() {
    return {
        ues: [],
        loading: true,
        openRow: null,
        filters: { search: '', parcours_id: '', type_ue: '' },
        pagination: { current_page: 1, last_page: 1, total: 0 },
        toast: { show: false, message: '', type: 'success' },

        // ── Load UEs via AJAX ──
        async loadUes(page) {
            this.loading = true;
            const p = page || 1;
            const params = new URLSearchParams({ format: 'json', page: p });
            if (this.filters.search) params.set('search', this.filters.search);
            if (this.filters.parcours_id) params.set('parcours_id', this.filters.parcours_id);
            if (this.filters.type_ue) params.set('type_ue', this.filters.type_ue);

            try {
                const resp = await fetch(`${BASE}?${params}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await resp.json();
                this.ues = data.ues || [];
                this.pagination = data.pagination || { current_page: 1, last_page: 1, total: 0 };
            } catch (e) { console.error(e); this.showToast('Erreur de chargement', 'error'); }
            this.loading = false;
        },

        goPage(p) { if (p >= 1 && p <= this.pagination.last_page) this.loadUes(p); },

        paginationPages() {
            const c = this.pagination.current_page, l = this.pagination.last_page;
            const pages = [];
            for (let i = Math.max(1, c - 2); i <= Math.min(l, c + 2); i++) pages.push(i);
            return pages;
        },

        showToast(msg, type = 'success') {
            this.toast = { show: true, message: msg, type };
            setTimeout(() => this.toast.show = false, 3000);
        },

        // ── Create UE ──
        openCreateModal() {
            document.getElementById('formUE').reset();
            document.getElementById('ue_method').value = 'POST';
            document.getElementById('formUE').action = `${BASE}`;
            document.getElementById('modalUETitleText').textContent = 'Nouvelle Unité d\'Enseignement';
            document.getElementById('ue_submit_text').textContent = 'Enregistrer';
            document.getElementById('ue_credit').value = '3';
            document.getElementById('ue_errors').classList.add('d-none');
            new bootstrap.Modal(document.getElementById('modalUE')).show();
        },

        // ── Edit UE ──
        async openEditModal(ue) {
            document.getElementById('formUE').reset();
            document.getElementById('ue_method').value = 'PUT';
            document.getElementById('formUE').action = `${BASE}/${ue.id}`;
            document.getElementById('modalUETitleText').textContent = 'Modifier l\'UE';
            document.getElementById('ue_submit_text').textContent = 'Mettre à jour';
            document.getElementById('ue_errors').classList.add('d-none');

            try {
                const resp = await fetch(`${BASE}/${ue.id}/json`, { headers: { 'Accept': 'application/json' } });
                const data = await resp.json();
                document.getElementById('ue_name').value = data.name || '';
                document.getElementById('ue_code').value = data.code || '';
                document.getElementById('ue_credit').value = data.credit || '';
                document.getElementById('ue_semestre').value = data.semestre || '1';
                document.getElementById('ue_type_ue').value = data.type_ue || 'fondamentale';
                document.getElementById('ue_filiere_id').value = data.filiere_id || '';
                document.getElementById('ue_niveau_id').value = data.niveau_id || '';
                document.getElementById('ue_description').value = data.description || '';
            } catch (e) { console.error(e); }

            new bootstrap.Modal(document.getElementById('modalUE')).show();
        },

        // ── Delete UE ──
        async deleteUe(ue) {
            if (!confirm(`Supprimer l'UE "${ue.name}" et ses ECUEs ?`)) return;
            try {
                const resp = await fetch(`${BASE}/${ue.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await resp.json();
                if (data.success) {
                    this.ues = this.ues.filter(u => u.id !== ue.id);
                    this.pagination.total--;
                    this.showToast('UE supprimée');
                } else {
                    this.showToast(data.message || 'Erreur', 'error');
                }
            } catch (e) { this.showToast('Erreur réseau', 'error'); }
        },

        // ── ECUE modals ──
        _currentEcueUeId: null,

        openEcueModal(ue) {
            this._currentEcueUeId = ue.id;
            document.getElementById('ecue_form').action = `${BASE}/${ue.id}/ecue`;
            document.getElementById('ecue_method').value = 'POST';
            document.getElementById('ecue_modal_title').textContent = 'Nouvel ECUE';
            document.getElementById('ecue_ue_label').textContent = ue.name;
            document.getElementById('ecue_submit_text').textContent = 'Créer l\'ECUE';
            document.getElementById('ecue_form').reset();
            document.getElementById('ecue_method').value = 'POST';
            document.getElementById('ecue_error').style.display = 'none';
            new bootstrap.Modal(document.getElementById('modalECUE')).show();
        },

        openEcueEditModal(ue, ecue) {
            this._currentEcueUeId = ue.id;
            document.getElementById('ecue_form').action = `${BASE}/${ue.id}/ecue/${ecue.id}`;
            document.getElementById('ecue_method').value = 'PUT';
            document.getElementById('ecue_modal_title').textContent = 'Modifier l\'ECUE';
            document.getElementById('ecue_ue_label').textContent = ue.name;
            document.getElementById('ecue_submit_text').textContent = 'Mettre à jour';
            document.getElementById('ecue_name').value = ecue.name || '';
            document.getElementById('ecue_code').value = ecue.code || '';
            document.getElementById('ecue_coefficient').value = ecue.coefficient || '';
            document.getElementById('ecue_credit').value = ecue.credit || '';
            document.getElementById('ecue_error').style.display = 'none';
            new bootstrap.Modal(document.getElementById('modalECUE')).show();
        },

        // ── Delete ECUE ──
        async deleteEcue(ue, ecue) {
            if (!confirm(`Détacher l'ECUE "${ecue.name}" ?`)) return;
            try {
                const resp = await fetch(`${BASE}/${ue.id}/ecue/${ecue.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await resp.json();
                if (data.success) {
                    ue.ecues = (ue.ecues || []).filter(e => e.id !== ecue.id);
                    ue.matieres_count = (ue.matieres_count || 1) - 1;
                    this.showToast('ECUE détaché');
                } else {
                    this.showToast(data.message || 'Erreur', 'error');
                }
            } catch (e) { this.showToast('Erreur réseau', 'error'); }
        },

        // ── Link Parcours ──
        _linkParcoursUeId: null,

        async openLinkParcoursModal(ue) {
            this._linkParcoursUeId = ue.id;
            document.getElementById('lp_ue_label').textContent = ue.name;
            document.getElementById('lp_loading').style.display = 'block';
            document.getElementById('lp_content').style.display = 'none';
            document.getElementById('lp_error').style.display = 'none';
            new bootstrap.Modal(document.getElementById('modalLinkParcours')).show();

            try {
                const resp = await fetch(`${BASE}/${ue.id}/parcours-disponibles`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                const data = await resp.json();
                const container = document.getElementById('lp_checkboxes');
                container.innerHTML = '';
                (data.lies || []).forEach(p => container.insertAdjacentHTML('beforeend', buildParcoursCheckbox(p, true)));
                (data.disponibles || []).forEach(p => container.insertAdjacentHTML('beforeend', buildParcoursCheckbox(p, false)));
                if ((data.lies || []).length === 0 && (data.disponibles || []).length === 0) {
                    container.innerHTML = '<div style="padding:1.5rem; text-align:center; color:#94a3b8;">Aucun parcours trouvé.</div>';
                }
                document.getElementById('lp_loading').style.display = 'none';
                document.getElementById('lp_content').style.display = 'block';
            } catch (e) {
                document.getElementById('lp_loading').style.display = 'none';
                document.getElementById('lp_error').style.display = 'block';
            }
        },
    };
}

// ── Build parcours checkbox with sem chips ──
function buildParcoursCheckbox(p, checked) {
    const activeSems = p.semestres || [];
    const hasAnySem = activeSems.length > 0;
    const semChips = [1,2,3,4,5,6,7,8,9,10].map(s => {
        const active = activeSems.includes(s);
        return `<span class="lp-sem-chip ${active ? 'lp-sem-chip--on' : ''}" data-parcours-id="${p.id}" data-sem="${s}" onclick="this.classList.toggle('lp-sem-chip--on')">S${s}</span>`;
    }).join('');
    return `<div style="display:flex; align-items:center; gap:.65rem; padding:.6rem .85rem; border-radius:10px; background:${hasAnySem ? '#eef2ff' : '#f8fafc'}; border:1.5px solid ${hasAnySem ? '#4338ca' : '#e8ecf1'}; margin-bottom:.1rem;">
        <input type="checkbox" class="lp-parcours-check" value="${p.id}" ${hasAnySem ? 'checked' : ''} style="width:1.1em; height:1.1em; accent-color:#4338ca; cursor:pointer; flex-shrink:0;">
        <div style="flex:1; min-width:0;">
            <div style="font-size:.86rem; font-weight:600; color:#1e293b;">${escHtml(p.code || '')} — ${escHtml(p.name)}</div>
            <div style="display:flex; gap:.25rem; flex-wrap:wrap; margin-top:.35rem;">${semChips}</div>
        </div>
    </div>`;
}

// ── Save Link Parcours (global, called by button onclick) ──
document.getElementById('lp_submit').addEventListener('click', async function() {
    const mgr = Alpine.$data(document.querySelector('[x-data]'));
    const btn = this;
    btn.disabled = true;
    document.getElementById('lp_submit_text').textContent = 'Enregistrement...';

    const checkboxes = document.querySelectorAll('#lp_checkboxes .lp-parcours-check:checked');
    const parcours = Array.from(checkboxes).map(cb => {
        const semChips = document.querySelectorAll(`.lp-sem-chip--on[data-parcours-id="${cb.value}"]`);
        return { id: cb.value, semestres: Array.from(semChips).map(c => parseInt(c.dataset.sem)) || [1] };
    }).filter(p => p.semestres.length > 0);

    try {
        const resp = await fetch(`${BASE}/${mgr._linkParcoursUeId}/sync-parcours`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ parcours })
        });
        const data = await resp.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalLinkParcours')).hide();
            mgr.loadUes(mgr.pagination.current_page);
            mgr.showToast('Parcours liés');
        }
    } catch (e) { document.getElementById('lp_error').style.display = 'block'; }
    btn.disabled = false;
    document.getElementById('lp_submit_text').textContent = 'Enregistrer';
});

// ── UE Form submit (create/edit) ──
document.getElementById('formUE').addEventListener('submit', async function(e) {
    e.preventDefault();
    const errorsDiv = document.getElementById('ue_errors');
    errorsDiv.classList.add('d-none');
    const btn = document.getElementById('ue_submit_btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...';

    try {
        const resp = await fetch(this.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(this),
        });
        const data = await resp.json();
        if (!resp.ok) {
            const msgs = data.errors ? Object.values(data.errors).flat().map(m => escHtml(m)).join('<br>') : escHtml(data.message || 'Erreur');
            errorsDiv.innerHTML = msgs;
            errorsDiv.classList.remove('d-none');
            return;
        }
        bootstrap.Modal.getInstance(document.getElementById('modalUE')).hide();
        const mgr = Alpine.$data(document.querySelector('[x-data]'));
        mgr.loadUes(mgr.pagination.current_page);
        mgr.showToast(data.message || 'UE enregistrée');
    } catch (err) {
        errorsDiv.textContent = 'Erreur réseau.';
        errorsDiv.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> <span id="ue_submit_text">Enregistrer</span>';
    }
});

// ── ECUE Form submit (create/edit) ──
document.getElementById('ecue_form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const errBox = document.getElementById('ecue_error');
    const btn = document.getElementById('ecue_submit');
    btn.disabled = true;
    errBox.style.display = 'none';

    const formData = new FormData(this);
    const isPut = document.getElementById('ecue_method').value === 'PUT';

    // Build body without _method (we'll use actual HTTP method or query param)
    const body = {};
    formData.forEach((v, k) => { if (k !== '_method') body[k] = v; });

    try {
        const url = isPut ? this.action + '?_method=PUT' : this.action;
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await resp.json();
        if (resp.ok && data.success !== false) {
            bootstrap.Modal.getInstance(document.getElementById('modalECUE')).hide();
            const mgr = Alpine.$data(document.querySelector('[x-data]'));
            mgr.loadUes(mgr.pagination.current_page);
            mgr.showToast(data.message || 'ECUE enregistré');
        } else {
            const msgs = data.errors ? Object.values(data.errors).flat().map(m => escHtml(m)).join('<br>') : escHtml(data.message || 'Erreur');
            errBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>' + msgs;
            errBox.style.display = 'block';
        }
    } catch (err) {
        errBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Erreur réseau';
        errBox.style.display = 'block';
    }
    btn.disabled = false;
});
</script>
@endpush
