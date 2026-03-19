@extends('layouts.app')

@section('title', 'Unités d\'Enseignement (UE) — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD UE Index — Premium Redesign
       Prefix: lu- (lmd-ue)
       ══════════════════════════════════════════════ */

    .lu-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }

    /* ── Hero ── */
    .lu-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.5rem;
        overflow: hidden;
        animation: lu-fadeDown .5s ease-out;
    }
    .lu-hero::before {
        content: '';
        position: absolute;
        top: -60%;
        right: -10%;
        width: 420px;
        height: 420px;
        background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
        pointer-events: none;
    }
    .lu-hero::after {
        content: '';
        position: absolute;
        bottom: -40%;
        left: 5%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%);
        pointer-events: none;
    }

    .lu-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        position: relative;
        z-index: 1;
    }
    .lu-hero-left { display: flex; align-items: center; gap: 1rem; }
    .lu-hero-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        border: 1px solid rgba(255,255,255,.15);
        flex-shrink: 0;
    }
    .lu-hero-info h1 {
        font-size: 1.45rem;
        font-weight: 700;
        margin: 0 0 .2rem;
        color: #fff;
        letter-spacing: -.02em;
    }
    .lu-hero-info p { margin: 0; opacity: .8; font-size: .88rem; }
    .lu-hero-actions {
        display: flex;
        gap: .5rem;
        position: relative;
        z-index: 1;
    }
    .lu-hero-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .55rem 1.1rem;
        border-radius: 10px;
        font-size: .84rem;
        font-weight: 600;
        border: 1.5px solid rgba(255,255,255,.3);
        color: #fff;
        background: rgba(255,255,255,.08);
        text-decoration: none;
        transition: all .2s;
        backdrop-filter: blur(4px);
    }
    .lu-hero-btn:hover { background: rgba(255,255,255,.18); color: #fff; text-decoration: none; }
    .lu-hero-btn--solid {
        background: #fff;
        color: #0453cb;
        border-color: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.12);
    }
    .lu-hero-btn--solid:hover { background: #edf2fc; color: #0453cb; }

    /* KPIs inside hero */
    .lu-hero-kpis {
        display: flex;
        gap: .75rem;
        margin-top: 1.5rem;
        position: relative;
        z-index: 1;
        flex-wrap: wrap;
    }
    .lu-kpi {
        flex: 1;
        min-width: 150px;
        background: rgba(255,255,255,.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px;
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        transition: background .2s;
    }
    .lu-kpi:hover { background: rgba(255,255,255,.15); }
    .lu-kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .lu-kpi--ue .lu-kpi-icon      { background: rgba(255,255,255,.18); color: #fff; }
    .lu-kpi--ecue .lu-kpi-icon     { background: rgba(129,140,248,.25); color: #a5b4fc; }
    .lu-kpi--credits .lu-kpi-icon  { background: rgba(16,185,129,.25); color: #6ee7b7; }
    .lu-kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1;
        color: #fff;
    }
    .lu-kpi-label {
        font-size: .75rem;
        color: rgba(255,255,255,.65);
        margin-top: .15rem;
    }

    /* ── Filter bar ── */
    .lu-filters {
        background: #fff;
        border-radius: 14px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        border: 1px solid #e8ecf1;
        display: flex;
        align-items: flex-end;
        gap: .85rem;
        flex-wrap: wrap;
        animation: lu-fadeUp .45s ease-out .1s both;
    }
    .lu-filter-group {
        display: flex;
        flex-direction: column;
        gap: .3rem;
        flex: 1;
        min-width: 140px;
    }
    .lu-filter-label {
        font-size: .72rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    .lu-filter-control {
        padding: .5rem .75rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 9px;
        font-size: .86rem;
        color: #1e293b;
        background: #f8fafc;
        transition: all .2s;
        width: 100%;
    }
    .lu-filter-control:focus {
        outline: none;
        border-color: #0453cb;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(4,83,203,.08);
    }
    .lu-filter-actions {
        display: flex;
        gap: .4rem;
        flex-shrink: 0;
    }
    .lu-filter-btn {
        padding: .5rem .9rem;
        border-radius: 9px;
        font-size: .84rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }
    .lu-filter-btn--primary { background: #0453cb; color: #fff; }
    .lu-filter-btn--primary:hover { background: #0340a0; }
    .lu-filter-btn--reset { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .lu-filter-btn--reset:hover { background: #e2e8f0; }

    /* ── Table card ── */
    .lu-table-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        overflow: hidden;
        animation: lu-fadeUp .45s ease-out .2s both;
    }
    .lu-table-header {
        padding: 1.15rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .lu-table-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .lu-table-title i { color: #0453cb; font-size: .9rem; }
    .lu-table-count { font-size: .8rem; color: #94a3b8; font-weight: 500; }
    .lu-table-wrapper { overflow-x: auto; }

    .lu-table {
        width: 100%;
        border-collapse: collapse;
    }
    .lu-table thead th {
        padding: .75rem 1rem;
        font-size: .72rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .06em;
        background: #fafbfc;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
    }
    .lu-table tbody tr {
        transition: background .15s;
        border-bottom: 1px solid #f8fafc;
    }
    .lu-table tbody td {
        padding: .8rem 1rem;
        font-size: .87rem;
        color: #475569;
        vertical-align: middle;
    }

    /* UE row — clickable */
    .lu-ue-row {
        cursor: pointer;
        user-select: none;
    }
    .lu-ue-row:hover { background: #f8fbff; }
    .lu-ue-row td:first-child { padding-left: 1rem; }

    .lu-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #0453cb;
        font-size: .65rem;
        transition: transform .2s, background .2s;
    }
    .lu-arrow.lu-open { transform: rotate(90deg); background: #e0ecff; }

    .lu-code {
        font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
        font-size: .82rem;
        font-weight: 600;
        color: #1e293b;
        letter-spacing: .02em;
    }
    .lu-name {
        font-weight: 600;
        color: #1e293b;
    }

    /* Type UE badges */
    .lu-type-badge {
        display: inline-flex;
        align-items: center;
        padding: .2rem .6rem;
        border-radius: 20px;
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .01em;
    }
    .lu-type--fondamentale   { background: #dbeafe; color: #1e40af; }
    .lu-type--methodologique  { background: #d1fae5; color: #065f46; }
    .lu-type--decouverte      { background: #fef3c7; color: #92400e; }
    .lu-type--transversale    { background: #e0e7ff; color: #3730a3; }

    .lu-semestre-tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .2rem .55rem;
        border-radius: 6px;
        font-size: .78rem;
        font-weight: 700;
        background: #eef2ff;
        color: #4f46e5;
        min-width: 32px;
    }
    .lu-credit-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        padding: .15rem .5rem;
        border-radius: 6px;
        font-size: .82rem;
        font-weight: 700;
        background: #ecfdf5;
        color: #059669;
    }
    .lu-ecue-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 26px;
        padding: .15rem .45rem;
        border-radius: 6px;
        font-size: .82rem;
        font-weight: 700;
        background: #f1f5f9;
        color: #334155;
    }

    /* Action buttons */
    .lu-actions { display: flex; gap: .3rem; justify-content: flex-end; }
    .lu-act {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #e8ecf1;
        background: #fff;
        color: #64748b;
        font-size: .8rem;
        cursor: pointer;
        transition: all .2s;
        text-decoration: none;
    }
    .lu-act:hover { color: #fff; text-decoration: none; }
    .lu-act--edit:hover   { background: #0453cb; border-color: #0453cb; color: #fff; }
    .lu-act--delete:hover { background: #dc2626; border-color: #dc2626; color: #fff; }

    /* ECUE sub-rows */
    .lu-sub-row td {
        background: #fafbfc;
        border-top: 1px dashed #e8ecf1;
        padding-top: .6rem !important;
        padding-bottom: .6rem !important;
    }
    .lu-sub-row td:first-child { padding-left: 1rem; }
    .lu-ecue-indent {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding-left: 1.5rem;
        color: #64748b;
        font-size: .84rem;
    }
    .lu-ecue-indent::before {
        content: '└';
        color: #cbd5e1;
        font-size: .9rem;
    }
    .lu-ecue-code {
        font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
        font-size: .78rem;
        color: #64748b;
    }
    .lu-ecue-coeff {
        font-size: .78rem;
        color: #94a3b8;
    }

    /* ── Empty state ── */
    .lu-empty {
        text-align: center;
        padding: 4rem 2rem;
    }
    .lu-empty-icon {
        width: 76px;
        height: 76px;
        border-radius: 20px;
        background: #f1f5f9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #cbd5e1;
        margin-bottom: 1.15rem;
    }
    .lu-empty-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: .4rem;
    }
    .lu-empty-text {
        font-size: .88rem;
        color: #94a3b8;
        margin-bottom: 1.25rem;
    }
    .lu-empty-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .6rem 1.2rem;
        background: #0453cb;
        color: #fff;
        border-radius: 10px;
        font-size: .85rem;
        font-weight: 600;
        text-decoration: none;
        transition: background .2s;
    }
    .lu-empty-btn:hover { background: #0340a0; color: #fff; text-decoration: none; }

    /* Pagination */
    .lu-pagination {
        padding: 1rem 1.5rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: center;
    }
    .lu-pagination .pagination { margin: 0; }

    /* ── Animations ── */
    @keyframes lu-fadeDown {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes lu-fadeUp {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Alpine x-cloak */
    [x-cloak] { display: none !important; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .lu-hero { padding: 1.5rem; border-radius: 14px; }
        .lu-hero-top { flex-direction: column; }
        .lu-hero-kpis { flex-direction: column; }
        .lu-filters { flex-direction: column; align-items: stretch; }
        .lu-filter-group { min-width: 100%; }
        .lu-table thead th,
        .lu-table tbody td { padding: .6rem .7rem; }
    }
</style>
@endpush

@section('content')
<div class="lu-page">

    {{-- ══ Hero ══ --}}
    @php
        $totalUEs = $ues->total();
        $totalECUEs = $ues->sum(fn($ue) => $ue->matieres->count());
        $totalCredits = $ues->sum('credit');
    @endphp

    <div class="lu-hero">
        <div class="lu-hero-top">
            <div class="lu-hero-left">
                <div class="lu-hero-icon"><i class="fas fa-cubes"></i></div>
                <div class="lu-hero-info">
                    <h1>Unités d'Enseignement</h1>
                    <p>Gestion des UE et de leurs ECUEs associés</p>
                </div>
            </div>
            <div class="lu-hero-actions">
                <button type="button" class="lu-hero-btn--solid lu-hero-btn" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>Nouvelle UE
                </button>
            </div>
        </div>

        <div class="lu-hero-kpis">
            <div class="lu-kpi lu-kpi--ue">
                <div class="lu-kpi-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="lu-kpi-value">{{ $totalUEs }}</div>
                    <div class="lu-kpi-label">Unités (UE)</div>
                </div>
            </div>
            <div class="lu-kpi lu-kpi--ecue">
                <div class="lu-kpi-icon"><i class="fas fa-book"></i></div>
                <div>
                    <div class="lu-kpi-value">{{ $totalECUEs }}</div>
                    <div class="lu-kpi-label">Matières (ECUE)</div>
                </div>
            </div>
            <div class="lu-kpi lu-kpi--credits">
                <div class="lu-kpi-icon"><i class="fas fa-award"></i></div>
                <div>
                    <div class="lu-kpi-value">{{ $totalCredits }}</div>
                    <div class="lu-kpi-label">Crédits ECTS</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @foreach(['success' => 'check-circle', 'error' => 'exclamation-circle'] as $type => $icon)
        @if(session($type))
            <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show" role="alert" style="border-radius:10px;">
                <i class="fas fa-{{ $icon }} me-2"></i>{{ session($type) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    {{-- ══ Filters ══ --}}
    <form method="GET" action="{{ route('esbtp.lmd.ue.index') }}" id="lu-filter-form">
        <div class="lu-filters">
            <div class="lu-filter-group" style="flex:2;">
                <label class="lu-filter-label">Recherche</label>
                <input type="text" class="lu-filter-control" name="search" value="{{ request('search') }}" placeholder="Code ou intitulé...">
            </div>
            <div class="lu-filter-group">
                <label class="lu-filter-label">Parcours</label>
                <select class="lu-filter-control" name="parcours_id">
                    <option value="">Tous</option>
                    @if(isset($parcoursList))
                        @foreach($parcoursList as $id => $label)
                            <option value="{{ $id }}" {{ request('parcours_id') == $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="lu-filter-group" style="max-width:130px;">
                <label class="lu-filter-label">Semestre</label>
                <select class="lu-filter-control" name="semestre">
                    <option value="">Tous</option>
                    @for($s = 1; $s <= 10; $s++)
                        <option value="{{ $s }}" {{ request('semestre') == $s ? 'selected' : '' }}>S{{ $s }}</option>
                    @endfor
                </select>
            </div>
            <div class="lu-filter-group" style="max-width:160px;">
                <label class="lu-filter-label">Type UE</label>
                <select class="lu-filter-control" name="type_ue">
                    <option value="">Tous</option>
                    @foreach(['fondamentale', 'methodologique', 'decouverte', 'transversale'] as $type)
                        <option value="{{ $type }}" {{ request('type_ue') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lu-filter-actions">
                <button type="submit" class="lu-filter-btn lu-filter-btn--primary">
                    <i class="fas fa-search"></i>
                </button>
                @if(request()->hasAny(['search', 'parcours_id', 'semestre', 'type_ue']))
                    <a href="{{ route('esbtp.lmd.ue.index') }}" class="lu-filter-btn lu-filter-btn--reset">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- ══ Table ══ --}}
    <div class="lu-table-card">
        <div class="lu-table-header">
            <div class="lu-table-title">
                <i class="fas fa-list-ul"></i>
                Liste des UEs
            </div>
            <div class="lu-table-count">{{ $ues->total() }} unité{{ $ues->total() > 1 ? 's' : '' }}</div>
        </div>

        @if($ues->count())
            <div class="lu-table-wrapper">
                <table class="lu-table" x-data="{ openRow: null }">
                    <thead>
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Code</th>
                            <th>Intitulé</th>
                            <th>Type UE</th>
                            <th style="text-align:center;">Sem.</th>
                            <th style="text-align:center;">Crédits</th>
                            <th style="text-align:center;">ECUEs</th>
                            <th style="text-align:right; width:100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ues as $ue)
                            {{-- UE row --}}
                            <tr class="lu-ue-row"
                                @click="openRow = openRow === {{ $ue->id }} ? null : {{ $ue->id }}">
                                <td>
                                    <span class="lu-arrow"
                                          :class="{ 'lu-open': openRow === {{ $ue->id }} }">&#9654;</span>
                                </td>
                                <td><span class="lu-code">{{ $ue->code }}</span></td>
                                <td><span class="lu-name">{{ $ue->name }}</span></td>
                                <td>
                                    @if($ue->type_ue)
                                        <span class="lu-type-badge lu-type--{{ $ue->type_ue }}">{{ ucfirst($ue->type_ue) }}</span>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <span class="lu-semestre-tag">S{{ $ue->semestre ?? '—' }}</span>
                                </td>
                                <td style="text-align:center;">
                                    <span class="lu-credit-pill">{{ $ue->credit ?? 0 }}</span>
                                </td>
                                <td style="text-align:center;">
                                    <span class="lu-ecue-count">{{ $ue->matieres->count() }}</span>
                                </td>
                                <td @click.stop>
                                    <div class="lu-actions">
                                        <button type="button" class="lu-act lu-act--edit" title="Modifier" onclick="openEditModal({{ $ue->id }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('esbtp.lmd.ue.destroy', $ue) }}" method="POST" style="display:inline;"
                                              onsubmit="return confirm('Supprimer cette UE et ses ECUEs ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="lu-act lu-act--delete" title="Supprimer">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- ECUE sub-rows --}}
                            @if($ue->matieres->count())
                                @foreach($ue->matieres as $ecue)
                                    <tr class="lu-sub-row" x-show="openRow === {{ $ue->id }}" x-cloak>
                                        <td></td>
                                        <td>
                                            <span class="lu-ecue-code">{{ $ecue->code ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="lu-ecue-indent">{{ $ecue->name }}</span>
                                        </td>
                                        <td colspan="2">
                                            <span class="lu-ecue-coeff">Coeff. {{ $ecue->pivot->coefficient_ecue ?? $ecue->coefficient ?? '—' }}</span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span class="lu-credit-pill" style="background:#f1f5f9; color:#334155;">{{ $ecue->pivot->credit_ecue ?? $ecue->credit ?? '—' }}</span>
                                        </td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="lu-sub-row" x-show="openRow === {{ $ue->id }}" x-cloak>
                                    <td></td>
                                    <td colspan="7" style="color:#94a3b8; font-style:italic; font-size:.84rem;">
                                        Aucun ECUE rattaché à cette UE.
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($ues->hasPages())
                <div class="lu-pagination">
                    {{ $ues->withQueryString()->links() }}
                </div>
            @endif
        @else
            <div class="lu-empty">
                <div class="lu-empty-icon"><i class="fas fa-cubes"></i></div>
                <div class="lu-empty-title">Aucune Unité d'Enseignement</div>
                <div class="lu-empty-text">Créez votre première UE pour structurer votre offre LMD.</div>
                <button type="button" class="lu-empty-btn" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>Créer une UE
                </button>
            </div>
        @endif
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- ══ MODAL UE — Create / Edit ══ --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalUE" tabindex="-1" aria-labelledby="modalUELabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <form id="formUE" method="POST">
                @csrf
                <input type="hidden" name="_method" id="ue_method" value="POST">

                <div class="modal-header" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: #fff; border-radius: 14px 14px 0 0; padding: 1rem 1.5rem;">
                    <h5 class="modal-title" id="modalUELabel">
                        <i class="fas fa-cubes me-2"></i><span id="modalUETitleText">Nouvelle Unité d'Enseignement</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0) invert(1);"></button>
                </div>

                <div class="modal-body" style="padding: 1.5rem;">
                    {{-- Row 1: Name + Code --}}
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Intitulé <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ue_name" name="name" required
                                   placeholder="Ex: Technologie de Construction du Bâtiment"
                                   style="border-radius: 8px;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ue_code" name="code" required
                                   placeholder="Ex: UE:BTCB1"
                                   style="border-radius: 8px; font-family: monospace;">
                        </div>
                    </div>

                    {{-- Row 2: Credits + Semestre + Type --}}
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Crédits CECT <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="ue_credit" name="credit" required
                                   min="1" max="30" value="3"
                                   style="border-radius: 8px;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Semestre <span class="text-danger">*</span></label>
                            <select class="form-select" id="ue_semestre" name="semestre" required style="border-radius: 8px;">
                                @for($s = 1; $s <= 10; $s++)
                                    <option value="{{ $s }}">S{{ $s }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Type UE <span class="text-danger">*</span></label>
                            <select class="form-select" id="ue_type_ue" name="type_ue" required style="border-radius: 8px;">
                                <option value="fondamentale">Fondamentale</option>
                                <option value="methodologique">Méthodologique</option>
                                <option value="decouverte">Découverte</option>
                                <option value="transversale">Transversale</option>
                            </select>
                        </div>
                    </div>

                    {{-- Row 3: Parcours + Filiere + Niveau --}}
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Parcours</label>
                            <select class="form-select" id="ue_parcours_id" name="parcours_id" style="border-radius: 8px;">
                                <option value="">— Aucun —</option>
                                @foreach($parcours as $p)
                                    <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Filière</label>
                            <select class="form-select" id="ue_filiere_id" name="filiere_id" style="border-radius: 8px;">
                                <option value="">— Aucune —</option>
                                @foreach($filieres as $f)
                                    <option value="{{ $f->id }}">{{ $f->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Niveau</label>
                            <select class="form-select" id="ue_niveau_id" name="niveau_id" style="border-radius: 8px;">
                                <option value="">— Aucun —</option>
                                @foreach($niveaux as $n)
                                    <option value="{{ $n->id }}">{{ $n->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="ue_description" name="description" rows="2"
                                  placeholder="Description optionnelle..."
                                  style="border-radius: 8px;"></textarea>
                    </div>

                    {{-- Validation errors --}}
                    <div id="ue_errors" class="alert alert-danger d-none" style="border-radius: 8px;"></div>
                </div>

                <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Annuler</button>
                    <button type="submit" class="btn-acasi primary" id="ue_submit_btn" style="border-radius: 8px;">
                        <i class="fas fa-save me-1"></i><span id="ue_submit_text">Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const modalUE = new bootstrap.Modal(document.getElementById('modalUE'));
const formUE = document.getElementById('formUE');
const errorsDiv = document.getElementById('ue_errors');

function resetForm() {
    formUE.reset();
    document.getElementById('ue_method').value = 'POST';
    document.getElementById('ue_credit').value = '3';
    document.getElementById('ue_semestre').value = '1';
    document.getElementById('ue_type_ue').value = 'fondamentale';
    errorsDiv.classList.add('d-none');
}

function openCreateModal() {
    resetForm();
    document.getElementById('modalUETitleText').textContent = 'Nouvelle Unité d\'Enseignement';
    document.getElementById('ue_submit_text').textContent = 'Enregistrer';
    formUE.action = "{{ route('esbtp.lmd.ue.store') }}";
    modalUE.show();
}

async function openEditModal(ueId) {
    resetForm();
    document.getElementById('modalUETitleText').textContent = 'Modifier l\'Unité d\'Enseignement';
    document.getElementById('ue_submit_text').textContent = 'Mettre à jour';
    document.getElementById('ue_method').value = 'PUT';
    formUE.action = "{{ url('esbtp/lmd/ue') }}/" + ueId;

    try {
        const resp = await fetch("{{ url('esbtp/lmd/ue') }}/" + ueId + "/json", {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const ue = await resp.json();

        document.getElementById('ue_name').value = ue.name || '';
        document.getElementById('ue_code').value = ue.code || '';
        document.getElementById('ue_credit').value = ue.credit || '';
        document.getElementById('ue_semestre').value = ue.semestre || '1';
        document.getElementById('ue_type_ue').value = ue.type_ue || 'fondamentale';
        document.getElementById('ue_parcours_id').value = ue.parcours_id || '';
        document.getElementById('ue_filiere_id').value = ue.filiere_id || '';
        document.getElementById('ue_niveau_id').value = ue.niveau_id || '';
        document.getElementById('ue_description').value = ue.description || '';

        modalUE.show();
    } catch (err) {
        console.error('Erreur chargement UE:', err);
        alert('Erreur lors du chargement de l\'UE.');
    }
}

formUE.addEventListener('submit', async function(e) {
    e.preventDefault();
    errorsDiv.classList.add('d-none');

    const btn = document.getElementById('ue_submit_btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...';

    const formData = new FormData(formUE);
    const method = document.getElementById('ue_method').value;

    try {
        const resp = await fetch(formUE.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        });

        const data = await resp.json();

        if (!resp.ok) {
            // Validation errors
            if (data.errors) {
                const msgs = Object.values(data.errors).flat().join('<br>');
                errorsDiv.innerHTML = msgs;
                errorsDiv.classList.remove('d-none');
            } else {
                errorsDiv.innerHTML = data.message || 'Une erreur est survenue.';
                errorsDiv.classList.remove('d-none');
            }
            return;
        }

        // Success — close modal and refresh table
        modalUE.hide();
        window.location.reload();
        return; // Stop here — page will reload, no need to restore button

    } catch (err) {
        console.error('Submit error:', err);
        errorsDiv.innerHTML = 'Erreur réseau. Veuillez réessayer.';
        errorsDiv.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Enregistrer';
    }
});
</script>
@endpush
