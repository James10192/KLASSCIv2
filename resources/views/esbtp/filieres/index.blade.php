@extends('layouts.app')

@section('title', 'Filières - KLASSCI')

@push('styles')
<style>
    .fl-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .fl-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .fl-hero-left { display: flex; align-items: center; gap: 1rem; }
    .fl-hero-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; color: #fff; flex-shrink: 0;
    }
    .fl-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .fl-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: 0; }

    .fl-hero-actions {
        display: flex; gap: .55rem; flex-wrap: wrap; align-items: center;
    }
    .fl-btn--glass {
        background: rgba(255,255,255,.15); color: #fff;
        border: 1px solid rgba(255,255,255,.2); border-radius: 10px;
        padding: .55rem 1rem; font-size: .82rem; font-weight: 600;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: .45rem;
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .fl-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; border-color: rgba(255,255,255,.35); }
    .fl-btn--white {
        background: #fff; color: #0453cb;
        border: 1px solid transparent; border-radius: 10px;
        padding: .55rem 1rem; font-size: .82rem; font-weight: 600;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: .45rem;
    }
    .fl-btn--white:hover { background: #f8fafc; color: #0453cb; }

    .fl-kpis {
        display: flex; gap: .75rem; margin-top: 1.4rem; flex-wrap: wrap;
    }
    .fl-kpi {
        flex: 1; min-width: 140px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px;
        padding: .85rem 1rem;
        display: flex; align-items: center; gap: .7rem;
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .fl-kpi:hover { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.32); }
    .fl-kpi--active {
        background: rgba(255,255,255,.28);
        border-color: rgba(255,255,255,.55);
        box-shadow: 0 0 0 1px rgba(255,255,255,.2);
    }
    .fl-kpi-icon {
        font-size: 1rem; color: rgba(255,255,255,.85);
        width: 28px; text-align: center;
    }
    .fl-kpi-body { display: flex; flex-direction: column; }
    .fl-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1.1; }
    .fl-kpi-label { font-size: .68rem; color: rgba(255,255,255,.7); margin-top: .2rem; text-transform: uppercase; letter-spacing: .35px; }

    .fl-card {
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1rem 1.15rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }
    .fl-filters-row {
        display: flex; gap: .75rem; flex-wrap: wrap; align-items: center;
    }
    .fl-search {
        flex: 1; min-width: 240px;
        position: relative;
    }
    .fl-search i {
        position: absolute; left: .85rem; top: 50%; transform: translateY(-50%);
        color: #94a3b8; font-size: .82rem;
    }
    .fl-search input {
        width: 100%;
        padding: .58rem .85rem .58rem 2.25rem;
        border: 1.5px solid #cbd5e1; border-radius: 8px;
        font-size: .88rem; color: #1e293b;
        transition: border-color .15s, box-shadow .15s;
    }
    .fl-search input:focus {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.1);
    }
    .fl-switch {
        display: inline-flex; align-items: center; gap: .45rem;
        font-size: .82rem; color: #1e293b; font-weight: 500;
        padding: .5rem .85rem; background: #f8fafc;
        border: 1px solid #e2e8f0; border-radius: 8px;
        cursor: pointer;
    }
    .fl-switch input { margin: 0; cursor: pointer; }
    .fl-reset {
        padding: .55rem .9rem; border-radius: 8px;
        font-size: .8rem; font-weight: 600;
        background: rgba(4,83,203,.08); color: #0453cb;
        border: 1px solid rgba(4,83,203,.2);
        cursor: pointer;
        display: inline-flex; align-items: center; gap: .35rem;
    }
    .fl-reset:hover { background: rgba(4,83,203,.14); }

    .fl-count {
        font-size: .8rem; color: #64748b;
        margin: .85rem 0;
        display: flex; align-items: center; gap: .5rem;
    }
    .fl-count strong { color: #0453cb; font-weight: 700; }

    .fl-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
    }
    .fl-fcard {
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1.15rem 1.2rem 1rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        position: relative;
        display: flex; flex-direction: column;
        transition: box-shadow .2s, border-color .2s;
    }
    .fl-fcard:hover {
        box-shadow: 0 8px 26px rgba(4,83,203,.08), 0 2px 6px rgba(15,23,42,.04);
        border-color: #c7d4e5;
    }
    .fl-fcard--inactive { opacity: .72; }
    .fl-fcard-head { display: flex; align-items: flex-start; gap: .75rem; }
    .fl-fcard-icon {
        width: 42px; height: 42px; border-radius: 10px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(4,83,203,.22);
    }
    .fl-fcard-icon--tc { background: linear-gradient(135deg, #033a8e, #0453cb); }
    .fl-fcard-icon--spe { background: linear-gradient(135deg, #0453cb, #3b7ddb); }
    .fl-fcard-icon--option { background: linear-gradient(135deg, #3b7ddb, #5e91de); }
    .fl-fcard-titles { flex: 1; min-width: 0; }
    .fl-fcard-title {
        font-size: .98rem; font-weight: 700; color: #1e293b;
        margin: 0 0 .2rem; line-height: 1.25;
        word-break: break-word;
    }
    .fl-fcard-code {
        font-size: .68rem; color: #64748b;
        text-transform: uppercase; letter-spacing: .5px;
        font-weight: 600;
        font-family: 'Courier New', monospace;
    }

    .fl-badges {
        display: flex; gap: .35rem; flex-wrap: wrap;
        margin-top: .85rem;
    }
    .fl-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .22rem .55rem; border-radius: 6px;
        font-size: .68rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .35px;
    }
    .fl-badge--tc {
        background: rgba(4,83,203,.12); color: #0453cb;
        border: 1px solid rgba(4,83,203,.28);
    }
    .fl-badge--spe {
        background: rgba(16,185,129,.12); color: #047857;
        border: 1px solid rgba(16,185,129,.28);
    }
    .fl-badge--option {
        background: rgba(94,145,222,.12); color: #3b7ddb;
        border: 1px solid rgba(94,145,222,.28);
    }
    .fl-badge--inactive {
        background: rgba(220,38,38,.1); color: #b91c1c;
        border: 1px solid rgba(220,38,38,.28);
    }

    .fl-subinfo {
        margin-top: .85rem;
        padding: .6rem .8rem;
        background: #f8fafc;
        border: 1px solid #eef2f7;
        border-radius: 8px;
        font-size: .78rem;
        color: #475569;
        display: flex; align-items: center; gap: .5rem;
        line-height: 1.4;
    }
    .fl-subinfo i { color: #0453cb; flex-shrink: 0; }
    .fl-subinfo a {
        color: #0453cb; font-weight: 600;
        text-decoration: none;
    }
    .fl-subinfo a:hover { text-decoration: underline; }
    .fl-subinfo--warn {
        background: rgba(245,158,11,.08);
        border-color: rgba(245,158,11,.25);
        color: #92400e;
    }
    .fl-subinfo--warn i { color: #d97706; }

    .fl-stats-row {
        display: flex; gap: .6rem; margin-top: .85rem;
        flex-wrap: wrap;
    }
    .fl-stat-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .55rem;
        background: rgba(4,83,203,.06);
        border: 1px solid rgba(4,83,203,.18);
        border-radius: 6px;
        font-size: .7rem; color: #475569;
    }
    .fl-stat-chip strong { color: #0453cb; font-weight: 700; }
    .fl-stat-chip i { font-size: .72rem; color: #0453cb; }

    .fl-fcard-actions {
        display: flex; gap: .35rem; justify-content: flex-end;
        margin-top: 1rem; padding-top: .75rem;
        border-top: 1px solid #f1f5f9;
    }
    .fl-action-btn {
        padding: .4rem .7rem; border-radius: 7px;
        font-size: .75rem; font-weight: 600;
        background: rgba(4,83,203,.08); color: #0453cb;
        border: 1px solid rgba(4,83,203,.2);
        text-decoration: none; cursor: pointer;
        display: inline-flex; align-items: center; gap: .3rem;
        transition: background .15s, border-color .15s;
    }
    .fl-action-btn:hover { background: rgba(4,83,203,.16); color: #0453cb; border-color: rgba(4,83,203,.32); }
    .fl-action-btn--danger {
        background: rgba(220,38,38,.08); color: #b91c1c;
        border-color: rgba(220,38,38,.22);
    }
    .fl-action-btn--danger:hover { background: rgba(220,38,38,.16); color: #b91c1c; border-color: rgba(220,38,38,.4); }
    .fl-action-btn:disabled {
        background: #f1f5f9; color: #94a3b8;
        border-color: #e2e8f0; cursor: not-allowed;
    }

    .fl-empty {
        padding: 3rem 1.5rem; text-align: center;
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }
    .fl-empty-icon { font-size: 2.5rem; color: #94a3b8; margin-bottom: .85rem; }
    .fl-empty-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin-bottom: .35rem; }
    .fl-empty-text { color: #64748b; font-size: .88rem; margin-bottom: 1rem; }

    .fl-toast {
        position: fixed; top: 1.5rem; right: 1.5rem;
        z-index: 100000;
        padding: .85rem 1.15rem;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 10px 36px rgba(15,23,42,.12), 0 2px 8px rgba(15,23,42,.06);
        border-left: 4px solid #0453cb;
        font-size: .88rem; color: #1e293b;
        display: flex; align-items: center; gap: .65rem;
        min-width: 280px; max-width: 420px;
    }
    .fl-toast--success { border-left-color: #10b981; }
    .fl-toast--success i { color: #10b981; }
    .fl-toast--error { border-left-color: #dc2626; }
    .fl-toast--error i { color: #dc2626; }
    .fl-toast i { font-size: 1.05rem; }

    @media (max-width: 768px) {
        .fl-hero { padding: 1.5rem 1.25rem 1.25rem; }
        .fl-hero h1 { font-size: 1.2rem; }
        .fl-hero p { font-size: .82rem; }
        .fl-hero-actions { width: 100%; }
        .fl-btn--white, .fl-btn--glass { flex: 1; justify-content: center; }
        .fl-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div x-data="flIndex()" x-init="init()" x-cloak class="container-fluid" style="padding:1.25rem;">

    <div class="fl-hero">
        <div class="fl-hero-top">
            <div class="fl-hero-left">
                <div class="fl-hero-icon"><i class="fas fa-sitemap"></i></div>
                <div>
                    <h1>Filières</h1>
                    <p>Gérez les filières BTS — Tronc Commun, Spécialités et Options indépendantes</p>
                </div>
            </div>
            <div class="fl-hero-actions">
                @can('bts_tronc_commun.manage_targets')
                    <a href="{{ route('esbtp.admin.orientation-targets.index') }}" class="fl-btn--glass" title="Configurer les sorties depuis le Tronc Commun BTS">
                        <i class="fas fa-route"></i> Sorties BTS Tronc Commun
                    </a>
                @endcan
                @can('filieres.create')
                    <a href="{{ route('esbtp.filieres.create') }}" class="fl-btn--white">
                        <i class="fas fa-plus"></i> Nouvelle filière
                    </a>
                @endcan
            </div>
        </div>

        <div class="fl-kpis">
            <div class="fl-kpi" :class="filters.type === '' ? 'fl-kpi--active' : ''" @click="setFilter('type', '')" role="button" aria-label="Voir toutes les filières">
                <i class="fas fa-list fl-kpi-icon"></i>
                <div class="fl-kpi-body">
                    <span class="fl-kpi-value">{{ $kpis['total'] }}</span>
                    <span class="fl-kpi-label">Total</span>
                </div>
            </div>
            <div class="fl-kpi" :class="filters.type === 'tc' ? 'fl-kpi--active' : ''" @click="setFilter('type', 'tc')" role="button" aria-label="Filtrer Tronc Commun">
                <i class="fas fa-sitemap fl-kpi-icon"></i>
                <div class="fl-kpi-body">
                    <span class="fl-kpi-value">{{ $kpis['tc'] }}</span>
                    <span class="fl-kpi-label">Tronc Commun</span>
                </div>
            </div>
            <div class="fl-kpi" :class="filters.type === 'specialite' ? 'fl-kpi--active' : ''" @click="setFilter('type', 'specialite')" role="button" aria-label="Filtrer Spécialités">
                <i class="fas fa-graduation-cap fl-kpi-icon"></i>
                <div class="fl-kpi-body">
                    <span class="fl-kpi-value">{{ $kpis['specialite'] }}</span>
                    <span class="fl-kpi-label">Spécialités</span>
                </div>
            </div>
            <div class="fl-kpi" :class="filters.type === 'option' ? 'fl-kpi--active' : ''" @click="setFilter('type', 'option')" role="button" aria-label="Filtrer Options">
                <i class="fas fa-tags fl-kpi-icon"></i>
                <div class="fl-kpi-body">
                    <span class="fl-kpi-value">{{ $kpis['option'] }}</span>
                    <span class="fl-kpi-label">Options</span>
                </div>
            </div>
            <div class="fl-kpi" :class="filters.type === 'inactive' ? 'fl-kpi--active' : ''" @click="setFilter('type', 'inactive')" role="button" aria-label="Filtrer Inactives">
                <i class="fas fa-pause fl-kpi-icon"></i>
                <div class="fl-kpi-body">
                    <span class="fl-kpi-value">{{ $kpis['inactives'] }}</span>
                    <span class="fl-kpi-label">Inactives</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages legacy (session) --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="fl-card" style="margin-bottom:1rem;">
        <div class="fl-filters-row">
            <div class="fl-search">
                <i class="fas fa-search"></i>
                <input type="search" x-model.debounce.250ms="filters.search" placeholder="Rechercher une filière par nom ou code…">
            </div>
            <label class="fl-switch">
                <input type="checkbox" x-model="filters.activeOnly">
                <span>Actives uniquement</span>
            </label>
            <button type="button" @click="resetFilters()" class="fl-reset" x-show="hasActiveFilters()" x-cloak>
                <i class="fas fa-redo"></i> Réinitialiser
            </button>
        </div>
    </div>

    <div class="fl-count">
        <span><strong x-text="filtered.length"></strong> filière(s) affichée(s) sur <strong>{{ count($filieresData) }}</strong></span>
    </div>

    {{-- Grid --}}
    <template x-if="filtered.length > 0">
        <div class="fl-grid">
            <template x-for="f in filtered" :key="f.id">
                <div class="fl-fcard" :class="!f.is_active ? 'fl-fcard--inactive' : ''">
                    <div class="fl-fcard-head">
                        <div class="fl-fcard-icon"
                             :class="f.is_tronc_commun ? 'fl-fcard-icon--tc' : (f.is_fille_de_tc ? 'fl-fcard-icon--spe' : 'fl-fcard-icon--option')">
                            <template x-if="f.is_tronc_commun"><i class="fas fa-sitemap"></i></template>
                            <template x-if="f.is_fille_de_tc"><i class="fas fa-graduation-cap"></i></template>
                            <template x-if="!f.is_tronc_commun && !f.is_fille_de_tc"><i class="fas fa-tags"></i></template>
                        </div>
                        <div class="fl-fcard-titles">
                            <h3 class="fl-fcard-title" x-text="f.name"></h3>
                            <span class="fl-fcard-code" x-text="f.code || '—'"></span>
                        </div>
                    </div>

                    <div class="fl-badges">
                        <template x-if="f.is_tronc_commun">
                            <span class="fl-badge fl-badge--tc"><i class="fas fa-sitemap"></i> Tronc Commun</span>
                        </template>
                        <template x-if="f.is_fille_de_tc">
                            <span class="fl-badge fl-badge--spe"><i class="fas fa-graduation-cap"></i> Spécialité</span>
                        </template>
                        <template x-if="!f.is_tronc_commun && !f.is_fille_de_tc">
                            <span class="fl-badge fl-badge--option"><i class="fas fa-tags"></i> Option</span>
                        </template>
                        <template x-if="!f.is_active">
                            <span class="fl-badge fl-badge--inactive"><i class="fas fa-pause"></i> Inactive</span>
                        </template>
                    </div>

                    {{-- Sous-info TC : nombre de filles --}}
                    <template x-if="f.is_tronc_commun && f.filles_count > 0">
                        <div class="fl-subinfo">
                            <i class="fas fa-list-tree"></i>
                            <span><strong x-text="f.filles_count"></strong>&nbsp;spécialité(s) fille(s) configurée(s)</span>
                        </div>
                    </template>
                    <template x-if="f.is_tronc_commun && f.filles_count === 0">
                        <div class="fl-subinfo fl-subinfo--warn">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>
                                Aucune spécialité fille configurée.
                                @can('filieres.create')
                                    <a :href="'/esbtp/filieres/create?parent_id=' + f.id">+ Créer une spécialité</a>
                                @endcan
                            </span>
                        </div>
                    </template>

                    {{-- Sous-info Spécialité : parent TC --}}
                    <template x-if="f.is_fille_de_tc && f.parent_id">
                        <div class="fl-subinfo">
                            <i class="fas fa-arrow-up-from-bracket"></i>
                            <span>
                                Sort de
                                <a :href="'/esbtp/filieres/' + f.parent_id" x-text="f.parent_name || 'le tronc commun parent'"></a>
                            </span>
                        </div>
                    </template>

                    {{-- Stats chips --}}
                    <div class="fl-stats-row">
                        <span class="fl-stat-chip" title="Classes rattachées">
                            <i class="fas fa-chalkboard"></i> <strong x-text="f.classes_count"></strong> classe(s)
                        </span>
                    </div>

                    <div class="fl-fcard-actions">
                        @can('filieres.view')
                            <a :href="'/esbtp/filieres/' + f.id" class="fl-action-btn" title="Voir les détails">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        @endcan
                        @can('filieres.edit')
                            <a :href="'/esbtp/filieres/' + f.id + '/edit'" class="fl-action-btn" title="Modifier la filière">
                                <i class="fas fa-pen"></i> Éditer
                            </a>
                        @endcan
                        @can('filieres.delete')
                            <button type="button"
                                    @click="askDelete(f)"
                                    class="fl-action-btn fl-action-btn--danger"
                                    :disabled="f.classes_count > 0 || f.filles_count > 0"
                                    :title="(f.classes_count > 0 || f.filles_count > 0) ? 'Suppression impossible : filière utilisée' : 'Supprimer cette filière'">
                                <i class="fas fa-trash"></i>
                            </button>
                        @endcan
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- Empty state --}}
    <template x-if="filtered.length === 0">
        <div class="fl-empty">
            <div class="fl-empty-icon"><i class="fas fa-sitemap"></i></div>
            <div class="fl-empty-title">Aucune filière trouvée</div>
            <p class="fl-empty-text">
                <template x-if="hasActiveFilters()">
                    <span>Essayez d'ajuster vos filtres ou de réinitialiser la recherche.</span>
                </template>
                <template x-if="!hasActiveFilters()">
                    <span>Aucune filière n'est encore enregistrée. Créez-en une pour commencer.</span>
                </template>
            </p>
            <template x-if="hasActiveFilters()">
                <button type="button" @click="resetFilters()" class="fl-reset">
                    <i class="fas fa-redo"></i> Réinitialiser les filtres
                </button>
            </template>
            @can('filieres.create')
                <template x-if="!hasActiveFilters()">
                    <a href="{{ route('esbtp.filieres.create') }}" class="fl-action-btn" style="background:#0453cb;color:#fff;border-color:transparent;">
                        <i class="fas fa-plus"></i> Nouvelle filière
                    </a>
                </template>
            @endcan
        </div>
    </template>

    {{-- Toast inline --}}
    <div x-show="toast.visible" x-cloak
         class="fl-toast"
         :class="toast.type === 'success' ? 'fl-toast--success' : (toast.type === 'error' ? 'fl-toast--error' : '')">
        <i class="fas"
           :class="toast.type === 'success' ? 'fa-check-circle' : (toast.type === 'error' ? 'fa-times-circle' : 'fa-info-circle')"></i>
        <span x-text="toast.message"></span>
    </div>
</div>

@push('scripts')
@php
    $_filieresJson = $filieresData;
@endphp
<script>
function flIndex() {
    return {
        filieres: @json($_filieresJson),
        filters: {
            search: '',
            type: '',
            activeOnly: false,
        },
        toast: { visible: false, type: 'info', message: '' },
        _toastTimer: null,

        init() {
            // Pas de listener global nécessaire — état local maintenu en mémoire.
        },

        get filtered() {
            const items = this.filieres;
            return items.filter(f => {
                if (this.filters.activeOnly && !f.is_active) return false;
                if (this.filters.type === 'tc' && !f.is_tronc_commun) return false;
                if (this.filters.type === 'specialite' && !f.is_fille_de_tc) return false;
                if (this.filters.type === 'option' && (f.is_tronc_commun || f.is_fille_de_tc)) return false;
                if (this.filters.type === 'inactive' && f.is_active) return false;
                if (this.filters.search) {
                    const s = this.filters.search.toLowerCase().trim();
                    const name = (f.name || '').toLowerCase();
                    const code = (f.code || '').toLowerCase();
                    if (!name.includes(s) && !code.includes(s)) return false;
                }
                return true;
            });
        },

        hasActiveFilters() {
            return this.filters.search !== '' || this.filters.type !== '' || this.filters.activeOnly;
        },

        setFilter(key, value) {
            this.filters[key] = (this.filters[key] === value) ? '' : value;
        },

        resetFilters() {
            this.filters.search = '';
            this.filters.type = '';
            this.filters.activeOnly = false;
        },

        showToast(type, message) {
            this.toast = { visible: true, type: type, message: message };
            if (this._toastTimer) clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toast.visible = false; }, 3500);
        },

        async askDelete(f) {
            if (f.classes_count > 0 || f.filles_count > 0) {
                this.showToast('error', 'Suppression impossible : la filière a des classes ou des filières filles rattachées.');
                return;
            }
            const confirmMsg = 'Supprimer définitivement la filière "' + f.name + '" ? Cette action est irréversible.';
            if (!window.confirm(confirmMsg)) return;
            try {
                const res = await fetch('/esbtp/filieres/' + f.id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                let body = null;
                try { body = await res.json(); } catch (_) { body = {}; }
                if (!res.ok) {
                    throw new Error(body.message || ('Erreur HTTP ' + res.status));
                }
                this.filieres = this.filieres.filter(x => x.id !== f.id);
                this.showToast('success', body.message || 'Filière supprimée.');
            } catch (err) {
                this.showToast('error', err.message || 'Impossible de supprimer la filière.');
            }
        },
    };
}
</script>
@endpush
@endsection
