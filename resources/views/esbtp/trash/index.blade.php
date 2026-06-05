@extends('layouts.app')

@section('title', 'Corbeille - KLASSCI')

@push('styles')
<style>
/* ═══════════════ NAMESPACE tr-* (Trash multi-entité premium) ═══════════════ */
.tr-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.tr-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.tr-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; flex: 1; }
.tr-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.tr-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; line-height: 1.2; }
.tr-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: .25rem 0 0; }

.tr-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    border: 1px solid transparent; border-radius: 10px;
    padding: .55rem 1rem; font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer; transition: all .15s; white-space: nowrap;
}
.tr-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.tr-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.tr-btn--white { background: #fff; color: #0453cb; }
.tr-btn--white:hover { background: #f1f5fc; color: #0453cb; }
.tr-btn--success { background: #10b981; color: #fff; }
.tr-btn--success:hover { background: #059669; color: #fff; }
.tr-btn--danger { background: #dc2626; color: #fff; }
.tr-btn--danger:hover { background: #b91c1c; color: #fff; }
.tr-btn--ghost { background: #fff; color: #0453cb; border-color: rgba(4,83,203,.25); }
.tr-btn--ghost:hover { background: rgba(4,83,203,.05); }
.tr-btn:disabled { opacity: .55; cursor: not-allowed; }

.tr-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.tr-kpi {
    flex: 1; min-width: 150px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .85rem;
}
.tr-kpi-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(255,255,255,.15); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.tr-kpi-value { font-size: 1.5rem; font-weight: 700; color: #fff; line-height: 1; }
.tr-kpi-label { font-size: .72rem; color: rgba(255,255,255,.7); margin-top: .25rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }

.tr-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
    margin-bottom: 1.25rem; overflow: hidden;
}

/* Tabs */
.tr-tabs {
    display: flex; gap: 0;
    background: #f8fafc;
    padding: .35rem;
    border-radius: 10px;
    width: fit-content;
    margin: 1rem 1.4rem;
}
.tr-tab {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .55rem 1.1rem;
    border-radius: 8px;
    background: transparent; border: none;
    color: #64748b; font-size: .85rem; font-weight: 700;
    cursor: pointer; transition: all .15s;
}
.tr-tab:hover:not(.active) { color: #1e293b; background: rgba(0,0,0,.03); }
.tr-tab.active {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    box-shadow: 0 2px 6px rgba(4,83,203,.25);
}
.tr-tab-count {
    background: rgba(255,255,255,.22);
    padding: .1rem .45rem;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 700;
}
.tr-tab:not(.active) .tr-tab-count { background: rgba(0,0,0,.06); color: #475569; }

/* Filters */
.tr-toolbar {
    display: flex; gap: .65rem; flex-wrap: wrap; align-items: center;
    padding: 0 1.4rem 1rem;
}
.tr-search { flex: 1; min-width: 220px; position: relative; }
.tr-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .85rem; }
.tr-search input {
    width: 100%; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: .55rem .8rem .55rem 2.1rem;
    background: #fff; font-size: .88rem; color: #1e293b;
}
.tr-range-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .45rem .85rem;
    border-radius: 999px;
    background: #fff; border: 1px solid #e2e8f0;
    cursor: pointer; font-size: .78rem; font-weight: 600;
    color: #64748b; transition: all .15s;
}
.tr-range-pill.active { background: #0453cb; color: #fff; border-color: #0453cb; }
.tr-range-pill:hover:not(.active) { color: #0453cb; border-color: rgba(4,83,203,.3); }

/* Table */
.tr-table-wrap { overflow-x: auto; padding: 0 1.4rem 1rem; }
.tr-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .87rem; }
.tr-table thead th {
    background: linear-gradient(180deg, #f8fafc, #f1f5f9);
    color: #475569;
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em;
    padding: .8rem 1rem; text-align: left;
    border-bottom: 1px solid #e2e8f0; white-space: nowrap;
}
.tr-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .12s; }
.tr-table tbody tr:hover { background: rgba(4,83,203,.03); }
.tr-table tbody td { padding: .85rem 1rem; vertical-align: middle; color: #1e293b; }

.tr-etu {
    display: flex; align-items: center; gap: .75rem;
}
.tr-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: .85rem;
    flex-shrink: 0;
}
.tr-etu-name { font-weight: 700; color: #1e293b; }
.tr-etu-matricule { font-size: .72rem; color: #94a3b8; }

.tr-deleted-cell { display: flex; flex-direction: column; gap: .15rem; }
.tr-deleted-when { font-size: .82rem; font-weight: 600; color: #1e293b; }
.tr-deleted-by { font-size: .72rem; color: #94a3b8; display: inline-flex; align-items: center; gap: .3rem; }

.tr-actions { display: inline-flex; gap: .35rem; }
.tr-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid transparent; background: transparent;
    cursor: pointer; transition: all .12s;
    color: #64748b;
}
.tr-action-btn--success:hover { background: rgba(16,185,129,.08); color: #047857; border-color: rgba(16,185,129,.3); }
.tr-action-btn--danger:hover { background: rgba(220,38,38,.08); color: #b91c1c; border-color: rgba(220,38,38,.3); }

.tr-orphan-badge {
    display: inline-flex; align-items: center; gap: .25rem;
    background: rgba(245,158,11,.1);
    color: #b45309;
    border: 1px solid rgba(245,158,11,.3);
    border-radius: 999px;
    padding: .1rem .45rem;
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
}

.tr-empty {
    text-align: center; padding: 3.5rem 1rem;
    color: #64748b;
}
.tr-empty i { font-size: 2.5rem; color: #cbd5e1; margin-bottom: .75rem; }

.tr-loading {
    text-align: center; padding: 2rem;
    color: #64748b;
}

/* Dialog dépendances (pour Restaurer + Supprimer définitivement) */
.tr-modal-bg {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.6);
    z-index: 99990;
    display: none; align-items: center; justify-content: center;
    padding: 1rem;
}
.tr-modal-bg.show { display: flex; }
.tr-modal {
    background: #fff; border-radius: 14px;
    max-width: 560px; width: 100%;
    max-height: 90vh; overflow: hidden;
    box-shadow: 0 25px 60px rgba(0,0,0,.3);
    display: flex; flex-direction: column;
}
.tr-modal-header {
    padding: 1.2rem 1.5rem;
    color: #fff;
    flex-shrink: 0;
}
.tr-modal-header--restore { background: linear-gradient(135deg, #047857, #10b981); }
.tr-modal-header--force { background: linear-gradient(135deg, #dc2626, #ef4444); }
.tr-modal-header h4 { margin: 0; font-size: 1rem; font-weight: 700; display: inline-flex; align-items: center; gap: .5rem; }
.tr-modal-header p { margin: .3rem 0 0; font-size: .82rem; color: rgba(255,255,255,.85); }
.tr-modal-body { padding: 1.4rem 1.5rem; font-size: .9rem; color: #475569; overflow-y: auto; flex: 1; }
.tr-modal-body strong { color: #1e293b; }
.tr-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f1f5f9;
    display: flex; justify-content: flex-end; gap: .65rem;
    flex-shrink: 0;
}

.tr-dep-section { margin-top: 1rem; }
.tr-dep-section:first-child { margin-top: 0; }
.tr-dep-title {
    display: inline-flex; align-items: center; gap: .5rem;
    font-size: .82rem; font-weight: 700;
    margin-bottom: .55rem;
    text-transform: uppercase; letter-spacing: .04em;
}
.tr-dep-title--blocking { color: #b91c1c; }
.tr-dep-title--cascading { color: #b45309; }
.tr-dep-title--info { color: #0453cb; }
.tr-dep-list {
    list-style: none; padding: 0; margin: 0;
    display: flex; flex-direction: column; gap: .4rem;
}
.tr-dep-item {
    display: flex; align-items: center; gap: .65rem;
    padding: .6rem .8rem;
    border-radius: 9px;
    background: #f8fafc; border: 1px solid #e2e8f0;
    font-size: .85rem; color: #1e293b;
}
.tr-dep-item--blocking { background: rgba(220,38,38,.06); border-color: rgba(220,38,38,.25); }
.tr-dep-item--cascading { background: rgba(245,158,11,.06); border-color: rgba(245,158,11,.25); }
.tr-dep-item--info { background: rgba(4,83,203,.05); border-color: rgba(4,83,203,.2); }
.tr-dep-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: .85rem;
}
.tr-dep-icon--blocking { background: rgba(220,38,38,.12); color: #b91c1c; }
.tr-dep-icon--cascading { background: rgba(245,158,11,.15); color: #b45309; }
.tr-dep-icon--info { background: rgba(4,83,203,.12); color: #0453cb; }

.tr-dep-banner {
    padding: .85rem 1rem; border-radius: 10px;
    display: flex; align-items: flex-start; gap: .65rem;
    font-size: .85rem; line-height: 1.5;
    margin-bottom: 1rem;
}
.tr-dep-banner--blocked { background: rgba(220,38,38,.07); color: #7f1d1d; border: 1px solid rgba(220,38,38,.2); }
.tr-dep-banner--warn { background: rgba(245,158,11,.07); color: #78350f; border: 1px solid rgba(245,158,11,.2); }
.tr-dep-banner--ok { background: rgba(16,185,129,.07); color: #065f46; border: 1px solid rgba(16,185,129,.2); }
.tr-dep-banner i { font-size: 1rem; margin-top: .15rem; flex-shrink: 0; }

.tr-dep-loading {
    text-align: center; padding: 2rem 1rem;
    color: #64748b; font-size: .9rem;
}
.tr-dep-loading i { font-size: 1.5rem; margin-bottom: .5rem; display: block; color: #0453cb; }

/* Toast premium */
.tr-toast {
    position: fixed; top: 1.5rem; right: 1.5rem;
    z-index: 99995;
    padding: .85rem 1.2rem;
    border-radius: 10px;
    box-shadow: 0 12px 30px rgba(0,0,0,.18);
    font-size: .88rem; font-weight: 600;
    display: flex; align-items: center; gap: .65rem;
    min-width: 280px; max-width: 420px;
    color: #fff;
}
.tr-toast--success { background: #10b981; }
.tr-toast--error   { background: #dc2626; }
.tr-toast--info    { background: #0453cb; }

/* Section cascade dangereuse (force_delete_cascade) */
.tr-cascade-section {
    margin-top: 1.25rem;
    padding: 1rem 1.1rem;
    background: linear-gradient(135deg, rgba(220,38,38,.05), rgba(239,68,68,.08));
    border: 1px solid rgba(220,38,38,.25);
    border-radius: 11px;
}
.tr-cascade-title {
    display: inline-flex; align-items: center; gap: .5rem;
    font-size: .82rem; font-weight: 700;
    color: #7f1d1d;
    text-transform: uppercase; letter-spacing: .04em;
    margin-bottom: .6rem;
}
.tr-cascade-desc {
    font-size: .82rem; line-height: 1.55;
    color: #7f1d1d;
    margin-bottom: .85rem;
}
.tr-motif-label {
    display: block;
    font-size: .78rem; font-weight: 600;
    color: #7f1d1d; margin-bottom: .35rem;
}
.tr-motif-input {
    width: 100%;
    border: 1px solid rgba(220,38,38,.3);
    border-radius: 9px;
    padding: .7rem .85rem;
    font-size: .88rem;
    color: #1e293b;
    background: #fff;
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
    transition: border-color .15s, box-shadow .15s;
}
.tr-motif-input:focus {
    outline: none;
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220,38,38,.12);
}
.tr-motif-counter {
    font-size: .72rem;
    color: #64748b;
    margin-top: .35rem;
    font-variant-numeric: tabular-nums;
}
.tr-motif-counter--ok { color: #047857; font-weight: 600; }
.tr-motif-counter--low { color: #b91c1c; font-weight: 600; }
.tr-btn--danger-cascade {
    background: linear-gradient(135deg, #7f1d1d, #b91c1c);
    color: #fff; border: 1px solid #7f1d1d;
}
.tr-btn--danger-cascade:hover:not(:disabled) {
    background: linear-gradient(135deg, #991b1b, #dc2626);
}
.tr-btn--danger-cascade:disabled {
    opacity: .5; cursor: not-allowed;
}

[x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" x-data="trashIndex()" x-init="init()">

        {{-- Hero --}}
        <div class="tr-hero">
            <div class="tr-hero-top">
                <div class="tr-hero-left">
                    <div class="tr-hero-icon"><i class="fas fa-trash-restore"></i></div>
                    <div>
                        <h1>Corbeille</h1>
                        <p>Étudiants, inscriptions et paiements supprimés — restauration ou suppression définitive</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('esbtp.etudiants.index') }}" class="tr-btn tr-btn--glass">
                        <i class="fas fa-arrow-left"></i>Retour étudiants
                    </a>
                </div>
            </div>
            <div class="tr-kpis">
                <div class="tr-kpi">
                    <div class="tr-kpi-icon"><i class="fas fa-trash"></i></div>
                    <div>
                        <div class="tr-kpi-value" x-text="kpis.total">—</div>
                        <div class="tr-kpi-label">Total <span x-text="activeTabLabel"></span></div>
                    </div>
                </div>
                <div class="tr-kpi">
                    <div class="tr-kpi-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="tr-kpi-value" x-text="kpis.this_week">—</div>
                        <div class="tr-kpi-label">Cette semaine</div>
                    </div>
                </div>
                <div class="tr-kpi">
                    <div class="tr-kpi-icon"><i class="fas fa-hourglass-end"></i></div>
                    <div>
                        <div class="tr-kpi-value" x-text="kpis.older_than_30">—</div>
                        <div class="tr-kpi-label">Plus de 30 jours</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs + filters + table --}}
        <div class="tr-card">
            <div class="tr-tabs">
                <button class="tr-tab" :class="{ active: tab === 'etudiants' }" @click="switchTab('etudiants')">
                    <i class="fas fa-user-graduate"></i><span>Étudiants</span>
                </button>
                <button class="tr-tab" :class="{ active: tab === 'inscriptions' }" @click="switchTab('inscriptions')">
                    <i class="fas fa-file-signature"></i><span>Inscriptions</span>
                </button>
                <button class="tr-tab" :class="{ active: tab === 'paiements' }" @click="switchTab('paiements')">
                    <i class="fas fa-money-bill-wave"></i><span>Paiements</span>
                </button>
            </div>

            <div class="tr-toolbar">
                <div class="tr-search">
                    <i class="fas fa-search"></i>
                    <input type="search" placeholder="Rechercher nom / prénoms / matricule…" x-model.debounce.300ms="search" @input="reload()">
                </div>
                <button class="tr-range-pill" :class="{ active: range === '' }" @click="range = ''; reload()">Tout</button>
                <button class="tr-range-pill" :class="{ active: range === 'this_week' }" @click="range = 'this_week'; reload()">Cette semaine</button>
                <button class="tr-range-pill" :class="{ active: range === 'this_month' }" @click="range = 'this_month'; reload()">30 derniers j.</button>
                <button class="tr-range-pill" :class="{ active: range === 'older' }" @click="range = 'older'; reload()">Plus anciens</button>
            </div>

            <div x-show="loading" class="tr-loading" x-cloak>
                <i class="fas fa-circle-notch fa-spin"></i> Chargement…
            </div>

            {{-- Tab Étudiants --}}
            <div x-show="!loading && tab === 'etudiants'" x-cloak class="tr-table-wrap">
                <template x-if="items.length === 0">
                    <div class="tr-empty">
                        <i class="fas fa-folder-open"></i>
                        <div>Aucun étudiant dans la corbeille.</div>
                    </div>
                </template>
                <template x-if="items.length > 0">
                    <table class="tr-table">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Supprimé</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in items" :key="item.id">
                                <tr>
                                    <td>
                                        <div class="tr-etu">
                                            <div class="tr-avatar" x-text="(item.nom?.[0] || '?') + (item.prenoms?.[0] || '?')"></div>
                                            <div>
                                                <div class="tr-etu-name"><span x-text="item.nom"></span> <span x-text="item.prenoms"></span></div>
                                                <div class="tr-etu-matricule" x-text="item.matricule || '—'"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tr-deleted-cell">
                                            <span class="tr-deleted-when">il y a <span x-text="item.deleter?.ago_human || '—'"></span></span>
                                            <span class="tr-deleted-by"><i class="fas fa-user-shield"></i><span x-text="item.deleter?.user_name || '—'"></span></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tr-actions">
                                            @can('students.restore')
                                            <button class="tr-action-btn tr-action-btn--success" title="Restaurer" @click="restore('etudiants', item.id, item.nom + ' ' + item.prenoms)">
                                                <i class="fas fa-rotate-left"></i>
                                            </button>
                                            @endcan
                                            @can('students.force_delete')
                                            <button class="tr-action-btn tr-action-btn--danger" title="Supprimer définitivement" @click="askForceDelete('etudiants', item.id, item.nom + ' ' + item.prenoms)">
                                                <i class="fas fa-fire"></i>
                                            </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>
            </div>

            {{-- Tab Inscriptions --}}
            <div x-show="!loading && tab === 'inscriptions'" x-cloak class="tr-table-wrap">
                <template x-if="items.length === 0">
                    <div class="tr-empty">
                        <i class="fas fa-folder-open"></i>
                        <div>Aucune inscription dans la corbeille.</div>
                    </div>
                </template>
                <template x-if="items.length > 0">
                    <table class="tr-table">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Classe / Année</th>
                                <th>Statut</th>
                                <th>Supprimé</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in items" :key="item.id">
                                <tr>
                                    <td>
                                        <div class="tr-etu">
                                            <div class="tr-avatar" x-text="(item.etudiant?.nom?.[0] || '?') + (item.etudiant?.prenoms?.[0] || '?')"></div>
                                            <div>
                                                <div class="tr-etu-name"><span x-text="item.etudiant?.nom"></span> <span x-text="item.etudiant?.prenoms"></span></div>
                                                <div class="tr-etu-matricule" x-text="item.etudiant?.matricule || '—'"></div>
                                                <template x-if="item.etudiant_soft_deleted">
                                                    <span class="tr-orphan-badge"><i class="fas fa-triangle-exclamation"></i>Étudiant aussi supprimé</span>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><strong x-text="item.classe || '—'"></strong></div>
                                        <small style="color:#94a3b8;" x-text="item.annee || '—'"></small>
                                    </td>
                                    <td><span x-text="item.status || '—'" style="text-transform:capitalize;"></span></td>
                                    <td>
                                        <div class="tr-deleted-cell">
                                            <span class="tr-deleted-when">il y a <span x-text="item.deleter?.ago_human || '—'"></span></span>
                                            <span class="tr-deleted-by"><i class="fas fa-user-shield"></i><span x-text="item.deleter?.user_name || '—'"></span></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tr-actions">
                                            @can('inscriptions.restore')
                                            <button class="tr-action-btn tr-action-btn--success" title="Restaurer (cascade étudiant si nécessaire)" @click="restore('inscriptions', item.id, 'inscription #' + item.id)">
                                                <i class="fas fa-rotate-left"></i>
                                            </button>
                                            @endcan
                                            @can('inscriptions.force_delete')
                                            <button class="tr-action-btn tr-action-btn--danger" title="Supprimer définitivement" @click="askForceDelete('inscriptions', item.id, 'inscription #' + item.id)">
                                                <i class="fas fa-fire"></i>
                                            </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>
            </div>

            {{-- Tab Paiements --}}
            <div x-show="!loading && tab === 'paiements'" x-cloak class="tr-table-wrap">
                <template x-if="items.length === 0">
                    <div class="tr-empty">
                        <i class="fas fa-folder-open"></i>
                        <div>Aucun paiement dans la corbeille.</div>
                    </div>
                </template>
                <template x-if="items.length > 0">
                    <table class="tr-table">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Référence / Montant</th>
                                <th>Mode / Date</th>
                                <th>Supprimé</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in items" :key="item.id">
                                <tr>
                                    <td>
                                        <div class="tr-etu">
                                            <div class="tr-avatar" x-text="(item.etudiant?.nom?.[0] || '?') + (item.etudiant?.prenoms?.[0] || '?')"></div>
                                            <div>
                                                <div class="tr-etu-name"><span x-text="item.etudiant?.nom || '—'"></span> <span x-text="item.etudiant?.prenoms || ''"></span></div>
                                                <div class="tr-etu-matricule" x-text="item.etudiant?.matricule || '—'"></div>
                                                <template x-if="item.etudiant_soft_deleted">
                                                    <span class="tr-orphan-badge"><i class="fas fa-triangle-exclamation"></i>Étudiant aussi supprimé</span>
                                                </template>
                                                <template x-if="item.inscription_soft_deleted">
                                                    <span class="tr-orphan-badge"><i class="fas fa-triangle-exclamation"></i>Inscription aussi supprimée</span>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><strong x-text="item.reference || '#' + item.id"></strong></div>
                                        <div style="font-size:.85rem;color:#0453cb;font-weight:700;" x-text="formatMoney(item.montant)"></div>
                                    </td>
                                    <td>
                                        <div x-text="item.mode_paiement || '—'"></div>
                                        <small style="color:#94a3b8;" x-text="item.date_paiement || '—'"></small>
                                    </td>
                                    <td>
                                        <div class="tr-deleted-cell">
                                            <span class="tr-deleted-when">il y a <span x-text="item.deleter?.ago_human || '—'"></span></span>
                                            <span class="tr-deleted-by"><i class="fas fa-user-shield"></i><span x-text="item.deleter?.user_name || '—'"></span></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tr-actions">
                                            @can('paiements.restore')
                                            <button class="tr-action-btn tr-action-btn--success" title="Restaurer (cascade étudiant/inscription si nécessaire)" @click="restore('paiements', item.id, 'paiement #' + item.id)">
                                                <i class="fas fa-rotate-left"></i>
                                            </button>
                                            @endcan
                                            @can('paiements.force_delete')
                                            <button class="tr-action-btn tr-action-btn--danger" title="Supprimer définitivement" @click="askForceDelete('paiements', item.id, 'paiement #' + item.id)">
                                                <i class="fas fa-fire"></i>
                                            </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>
            </div>
        </div>

        {{-- Dialog dépendances unifié (Restore + Force delete) --}}
        <div class="tr-modal-bg" :class="{ show: depModalOpen }" x-cloak
             @click.self="closeDepModal()"
             @keydown.escape.window="depModalOpen && closeDepModal()">
            <div class="tr-modal">
                <div class="tr-modal-header" :class="depAction === 'restore' ? 'tr-modal-header--restore' : 'tr-modal-header--force'">
                    <h4>
                        <i :class="depAction === 'restore' ? 'fas fa-rotate-left' : 'fas fa-fire'"></i>
                        <span x-text="depAction === 'restore' ? 'Restaurer' : 'Supprimer définitivement'"></span>
                    </h4>
                    <p x-text="depData?.entity_label || '…'"></p>
                </div>
                <div class="tr-modal-body">
                    {{-- Loading state --}}
                    <template x-if="depLoading">
                        <div class="tr-dep-loading">
                            <i class="fas fa-circle-notch fa-spin"></i>
                            <div>Analyse des dépendances en cours…</div>
                        </div>
                    </template>

                    {{-- Loaded state --}}
                    <template x-if="!depLoading && depData">
                        <div>
                            {{-- Banner status --}}
                            <template x-if="depAction === 'force' && depData.has_blocking">
                                <div class="tr-dep-banner tr-dep-banner--blocked">
                                    <i class="fas fa-ban"></i>
                                    <div>
                                        <strong>Suppression définitive impossible.</strong><br>
                                        Des entités liées empêchent cette action. Supprimez ou restaurez d'abord les éléments listés ci-dessous.
                                    </div>
                                </div>
                            </template>

                            <template x-if="depAction === 'force' && !depData.has_blocking && (depData.cascading_force_delete?.length > 0)">
                                <div class="tr-dep-banner tr-dep-banner--warn">
                                    <i class="fas fa-triangle-exclamation"></i>
                                    <div>
                                        <strong>Action irréversible.</strong> Vérifiez les conséquences avant de confirmer.
                                    </div>
                                </div>
                            </template>

                            <template x-if="depAction === 'force' && !depData.has_blocking && (depData.cascading_force_delete?.length === 0)">
                                <div class="tr-dep-banner tr-dep-banner--warn">
                                    <i class="fas fa-triangle-exclamation"></i>
                                    <div>
                                        <strong>Suppression définitive.</strong> Cette action est irréversible. Aucune dépendance détectée — l'entité sera supprimée seule.
                                    </div>
                                </div>
                            </template>

                            <template x-if="depAction === 'restore' && (depData.cascading_restore?.length > 0)">
                                <div class="tr-dep-banner tr-dep-banner--ok">
                                    <i class="fas fa-circle-info"></i>
                                    <div>
                                        <strong>Restauration en cascade.</strong> D'autres entités liées seront aussi restaurées automatiquement pour préserver l'intégrité.
                                    </div>
                                </div>
                            </template>

                            <template x-if="depAction === 'restore' && (depData.cascading_restore?.length === 0) && (depData.blocking_restore?.length === 0)">
                                <div class="tr-dep-banner tr-dep-banner--ok">
                                    <i class="fas fa-circle-check"></i>
                                    <div>
                                        Aucune dépendance détectée. L'entité sera simplement restaurée.
                                    </div>
                                </div>
                            </template>

                            {{-- Blocking restore (rare) --}}
                            <template x-if="depAction === 'restore' && depData.blocking_restore?.length > 0">
                                <div class="tr-dep-section">
                                    <div class="tr-dep-title tr-dep-title--blocking">
                                        <i class="fas fa-ban"></i>Restauration bloquée par
                                    </div>
                                    <ul class="tr-dep-list">
                                        <template x-for="dep in depData.blocking_restore" :key="dep.type">
                                            <li class="tr-dep-item tr-dep-item--blocking">
                                                <span class="tr-dep-icon tr-dep-icon--blocking">
                                                    <i :class="'fas ' + (dep.icon || 'fa-exclamation')"></i>
                                                </span>
                                                <span x-text="dep.label"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            {{-- Cascading restore --}}
                            <template x-if="depAction === 'restore' && depData.cascading_restore?.length > 0">
                                <div class="tr-dep-section">
                                    <div class="tr-dep-title tr-dep-title--info">
                                        <i class="fas fa-arrow-rotate-left"></i>Restauré en cascade
                                    </div>
                                    <ul class="tr-dep-list">
                                        <template x-for="dep in depData.cascading_restore" :key="dep.type">
                                            <li class="tr-dep-item tr-dep-item--info">
                                                <span class="tr-dep-icon tr-dep-icon--info">
                                                    <i :class="'fas ' + (dep.icon || 'fa-link')"></i>
                                                </span>
                                                <span x-text="dep.label"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            {{-- Blocking force-delete --}}
                            <template x-if="depAction === 'force' && depData.blocking_force_delete?.length > 0">
                                <div class="tr-dep-section">
                                    <div class="tr-dep-title tr-dep-title--blocking">
                                        <i class="fas fa-ban"></i>Suppression bloquée par
                                    </div>
                                    <ul class="tr-dep-list">
                                        <template x-for="dep in depData.blocking_force_delete" :key="dep.type">
                                            <li class="tr-dep-item tr-dep-item--blocking">
                                                <span class="tr-dep-icon tr-dep-icon--blocking">
                                                    <i :class="'fas ' + (dep.icon || 'fa-exclamation')"></i>
                                                </span>
                                                <span x-text="dep.label"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            {{-- Cascading force-delete --}}
                            <template x-if="depAction === 'force' && depData.cascading_force_delete?.length > 0">
                                <div class="tr-dep-section">
                                    <div class="tr-dep-title tr-dep-title--cascading">
                                        <i class="fas fa-triangle-exclamation"></i>Conséquences en cascade
                                    </div>
                                    <ul class="tr-dep-list">
                                        <template x-for="dep in depData.cascading_force_delete" :key="dep.type">
                                            <li class="tr-dep-item tr-dep-item--cascading">
                                                <span class="tr-dep-icon tr-dep-icon--cascading">
                                                    <i :class="'fas ' + (dep.icon || 'fa-link')"></i>
                                                </span>
                                                <span x-text="dep.label"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            {{-- Section cascade avancée (étudiants uniquement, permission requise) :
                                 visible quand l'étudiant a soit des inscriptions/paiements en corbeille à purger,
                                 soit des dépendances bypassables (notes), tant qu'il n'y a PAS de blocage dur OHADA. --}}
                            @can('students.force_delete_cascade')
                            <template x-if="depAction === 'force'
                                && depTarget?.type === 'etudiants'
                                && !depData.has_hard_blocking
                                && (depData.cascade_counts?.inscriptions_trashed > 0
                                    || depData.cascade_counts?.paiements_trashed > 0
                                    || depData.has_bypassable_blocking)">
                                <div class="tr-cascade-section">
                                    <div class="tr-cascade-title">
                                        <i class="fas fa-skull-crossbones"></i>Forcer la suppression cascade
                                    </div>
                                    <div class="tr-cascade-desc">
                                        Cette action supprime <strong>DÉFINITIVEMENT</strong> l'étudiant ET ses
                                        dépendances cascade (inscriptions et paiements de la corbeille, présences,
                                        souscriptions de frais). <strong>Irréversible.</strong>
                                        Justification écrite obligatoire (audit OHADA).
                                    </div>

                                    {{-- Bypass blocking (notes) — affiché uniquement si l'utilisateur a la perm --}}
                                    @can('students.force_delete_bypass_blocking')
                                    <template x-if="depData.has_bypassable_blocking">
                                        <label style="display:flex;gap:.55rem;align-items:flex-start;padding:.65rem .75rem;background:rgba(220,38,38,.06);border:1px solid rgba(220,38,38,.3);border-radius:9px;margin-bottom:.85rem;cursor:pointer;">
                                            <input type="checkbox" x-model="bypassBlocking" :disabled="actionSaving" style="margin-top:.2rem;flex-shrink:0;accent-color:#dc2626;">
                                            <span style="font-size:.83rem;color:#7f1d1d;line-height:1.5;">
                                                <strong>Forcer malgré dépendances bloquantes.</strong>
                                                Supprime aussi physiquement les notes liées à l'étudiant.
                                                Action exceptionnelle journalisée — violation explicite de l'intégrité notes.
                                            </span>
                                        </label>
                                    </template>
                                    @endcan

                                    <label class="tr-motif-label" for="tr-motif-input">
                                        Motif de la suppression (≥ 30 caractères) <span style="color:#dc2626">*</span>
                                    </label>
                                    <textarea
                                        id="tr-motif-input"
                                        class="tr-motif-input"
                                        x-model="cascadeMotif"
                                        rows="3"
                                        placeholder="Ex : Étudiant inscrit par erreur, jamais venu en cours, aucun paiement validé, validation directrice 05/06/2026."
                                        :disabled="actionSaving"></textarea>
                                    <div class="tr-motif-counter"
                                         :class="cascadeMotif.trim().length >= 30 ? 'tr-motif-counter--ok' : 'tr-motif-counter--low'"
                                         x-text="cascadeMotif.trim().length + ' / 30 caractères minimum'"></div>
                                </div>
                            </template>
                            @endcan
                        </div>
                    </template>

                    {{-- Error state --}}
                    <template x-if="!depLoading && !depData && depError">
                        <div class="tr-dep-banner tr-dep-banner--blocked">
                            <i class="fas fa-exclamation-circle"></i>
                            <div x-text="depError"></div>
                        </div>
                    </template>
                </div>
                <div class="tr-modal-footer">
                    <button class="tr-btn tr-btn--ghost" @click="closeDepModal()" :disabled="actionSaving">Annuler</button>

                    {{-- Bouton cascade (étudiants, permission, pas de blocage dur OHADA,
                         et présence de cascadables OU de bypassables) --}}
                    @can('students.force_delete_cascade')
                    <template x-if="depAction === 'force'
                        && depTarget?.type === 'etudiants'
                        && !(depData?.has_hard_blocking)
                        && (depData?.cascade_counts?.inscriptions_trashed > 0
                            || depData?.cascade_counts?.paiements_trashed > 0
                            || depData?.has_bypassable_blocking)">
                        <button
                            class="tr-btn tr-btn--danger-cascade"
                            :disabled="depLoading || actionSaving
                                || cascadeMotif.trim().length < 30
                                || (depData?.has_bypassable_blocking && !bypassBlocking)"
                            @click="confirmForceDeleteCascade()">
                            <i class="fas fa-skull-crossbones"></i>
                            <span x-show="!actionSaving">Forcer la suppression</span>
                            <span x-show="actionSaving" x-cloak>Suppression…</span>
                        </button>
                    </template>
                    @endcan

                    {{-- Bouton standard restore / force delete (caché si la section cascade est active) --}}
                    <button
                        class="tr-btn"
                        :class="depAction === 'restore' ? 'tr-btn--success' : 'tr-btn--danger'"
                        :disabled="depLoading || actionSaving || (depAction === 'force' && depData?.has_blocking)"
                        x-show="!(depAction === 'force'
                            && depTarget?.type === 'etudiants'
                            && !depData?.has_hard_blocking
                            && (depData?.cascade_counts?.inscriptions_trashed > 0
                                || depData?.cascade_counts?.paiements_trashed > 0
                                || depData?.has_bypassable_blocking))"
                        @click="confirmAction()">
                        <i :class="depAction === 'restore' ? 'fas fa-rotate-left' : 'fas fa-fire'"></i>
                        <span x-show="!actionSaving" x-text="depAction === 'restore' ? 'Confirmer la restauration' : 'Supprimer définitivement'"></span>
                        <span x-show="actionSaving" x-cloak>Traitement…</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast notifications (CSS class statique + :class toggle, jamais mix style+:style) --}}
        <div class="tr-toast" :class="'tr-toast--' + toast.type" x-show="toast.show" x-cloak x-transition.opacity>
            <i :class="toast.type === 'success' ? 'fas fa-check-circle' : (toast.type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle')"></i>
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
function trashIndex() {
    return {
        tab: 'etudiants',
        search: '',
        range: '',
        loading: false,
        items: [],
        kpis: { total: '—', this_week: '—', older_than_30: '—' },

        // Dialog unifié (restore + force delete)
        depModalOpen: false,
        depLoading: false,
        depData: null,
        depError: null,
        depAction: 'force',          // 'restore' | 'force'
        depTarget: null,             // { type, id }
        actionSaving: false,
        cascadeMotif: '',            // Motif pour suppression cascade (≥30 chars)
        bypassBlocking: false,       // Coché pour bypass notes (permission requise)

        // Toast
        toast: { show: false, type: 'info', message: '', timer: null },

        get activeTabLabel() {
            return this.tab === 'etudiants' ? 'étudiants' : this.tab === 'inscriptions' ? 'inscriptions' : 'paiements';
        },

        init() { this.reload(); },

        switchTab(t) {
            if (t === this.tab) return;
            this.tab = t;
            this.search = '';
            this.range = '';
            this.reload();
        },

        showToast(type, message, durationMs = 4000) {
            if (this.toast.timer) clearTimeout(this.toast.timer);
            this.toast = { show: true, type, message, timer: null };
            this.toast.timer = setTimeout(() => { this.toast.show = false; }, durationMs);
        },

        async reload() {
            this.loading = true;
            try {
                const url = new URL(`{{ url('/esbtp/trash') }}/${this.tab}`, window.location.origin);
                if (this.search) url.searchParams.append('search', this.search);
                if (this.range) url.searchParams.append('range', this.range);
                const res = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Erreur de chargement');
                this.items = data.items || [];
                this.kpis = data.kpis || { total: 0, this_week: 0, older_than_30: 0 };
            } catch (e) {
                this.showToast('error', 'Erreur de chargement : ' + e.message);
            } finally {
                this.loading = false;
            }
        },

        formatMoney(v) {
            return new Intl.NumberFormat('fr-FR').format(v || 0) + ' FCFA';
        },

        /**
         * Point d'entrée unifié pour Restaurer.
         * Ouvre le dialog dépendances en mode 'restore'.
         */
        restore(type, id /*, label (ignoré, on récupère du backend) */) {
            this.openDepModal('restore', type, id);
        },

        /**
         * Point d'entrée unifié pour Supprimer définitivement.
         * Ouvre le dialog dépendances en mode 'force'.
         */
        askForceDelete(type, id /*, label */) {
            this.openDepModal('force', type, id);
        },

        async openDepModal(action, type, id) {
            this.depAction = action;
            this.depTarget = { type, id };
            this.depData = null;
            this.depError = null;
            this.cascadeMotif = '';  // reset motif à chaque ouverture
            this.bypassBlocking = false;  // reset bypass checkbox
            this.depLoading = true;
            this.depModalOpen = true;

            try {
                const res = await fetch(`{{ url('/esbtp/trash') }}/${type}/${id}/dependencies`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    const errBody = await res.json().catch(() => ({}));
                    throw new Error(errBody.message || `Erreur HTTP ${res.status}`);
                }
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'Erreur lors de l\'analyse');
                this.depData = json.data;
            } catch (e) {
                this.depError = 'Impossible d\'analyser les dépendances : ' + e.message;
            } finally {
                this.depLoading = false;
            }
        },

        closeDepModal() {
            if (this.actionSaving) return;
            this.depModalOpen = false;
            this.depData = null;
            this.depError = null;
            this.depTarget = null;
            this.cascadeMotif = '';
            this.bypassBlocking = false;
        },

        /**
         * POST /esbtp/trash/etudiants/{id}/force-delete-cascade avec motif obligatoire.
         * Action exceptionnelle gardée par permission students.force_delete_cascade.
         */
        async confirmForceDeleteCascade() {
            if (!this.depTarget || this.depTarget.type !== 'etudiants') return;
            if (this.actionSaving) return;
            if (this.cascadeMotif.trim().length < 30) {
                this.showToast('error', 'Motif obligatoire ≥ 30 caractères.');
                return;
            }

            this.actionSaving = true;
            const { id } = this.depTarget;
            const url = `{{ url('/esbtp/trash') }}/etudiants/${id}/force-delete-cascade`;

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        motif: this.cascadeMotif.trim(),
                        bypass_blocking: this.bypassBlocking,
                    }),
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Erreur cascade');
                this.showToast('success', data.message || 'Suppression cascade effectuée.', 6000);
                this.depModalOpen = false;
                this.depData = null;
                this.depTarget = null;
                this.cascadeMotif = '';
                this.bypassBlocking = false;
                await this.reload();
            } catch (e) {
                this.showToast('error', 'Erreur : ' + e.message, 8000);
            } finally {
                this.actionSaving = false;
            }
        },

        async confirmAction() {
            if (!this.depTarget) return;
            if (this.actionSaving) return;
            if (this.depAction === 'force' && this.depData?.has_blocking) return;

            this.actionSaving = true;
            const { type, id } = this.depTarget;
            const isRestore = this.depAction === 'restore';
            const url = `{{ url('/esbtp/trash') }}/${type}/${id}/${isRestore ? 'restore' : 'force'}`;
            const method = isRestore ? 'POST' : 'DELETE';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Erreur lors de l\'action');
                this.showToast('success', data.message || 'Action effectuée.');
                this.depModalOpen = false;
                this.depData = null;
                this.depTarget = null;
                await this.reload();
            } catch (e) {
                this.showToast('error', 'Erreur : ' + e.message, 6000);
            } finally {
                this.actionSaving = false;
            }
        },
    };
}
</script>
@endsection
