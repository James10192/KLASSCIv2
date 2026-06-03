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

/* Confirm modal force-delete */
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
    max-width: 480px; width: 100%;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0,0,0,.3);
}
.tr-modal-header {
    padding: 1.2rem 1.5rem;
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: #fff;
}
.tr-modal-header h4 { margin: 0; font-size: 1rem; font-weight: 700; display: inline-flex; align-items: center; gap: .5rem; }
.tr-modal-body { padding: 1.4rem 1.5rem; font-size: .9rem; color: #475569; }
.tr-modal-body strong { color: #1e293b; }
.tr-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f1f5f9;
    display: flex; justify-content: flex-end; gap: .65rem;
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

        {{-- Confirm modal force delete --}}
        <div class="tr-modal-bg" :class="{ show: forceModalOpen }" x-cloak @click.self="forceModalOpen = false">
            <div class="tr-modal">
                <div class="tr-modal-header">
                    <h4><i class="fas fa-fire"></i>Suppression définitive</h4>
                </div>
                <div class="tr-modal-body">
                    Vous êtes sur le point de supprimer <strong x-text="forceTarget?.label"></strong> de manière <strong style="color:#dc2626;">irréversible</strong>.
                    <br><br>
                    Cette action ne peut pas être annulée. Confirmez-vous ?
                </div>
                <div class="tr-modal-footer">
                    <button class="tr-btn tr-btn--ghost" @click="forceModalOpen = false">Annuler</button>
                    <button class="tr-btn tr-btn--danger" :disabled="forceSaving" @click="confirmForceDelete()">
                        <i class="fas fa-fire"></i><span x-text="forceSaving ? 'Suppression…' : 'Supprimer définitivement'"></span>
                    </button>
                </div>
            </div>
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
        forceModalOpen: false,
        forceTarget: null,
        forceSaving: false,

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

        async reload() {
            this.loading = true;
            try {
                const url = new URL(`{{ route('esbtp.trash.index') }}/${this.tab}`, window.location.origin);
                if (this.search) url.searchParams.append('search', this.search);
                if (this.range) url.searchParams.append('range', this.range);
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'erreur');
                this.items = data.items || [];
                this.kpis = data.kpis || { total: 0, this_week: 0, older_than_30: 0 };
            } catch (e) {
                alert('Erreur de chargement : ' + e.message);
            } finally {
                this.loading = false;
            }
        },

        formatMoney(v) {
            return new Intl.NumberFormat('fr-FR').format(v || 0) + ' FCFA';
        },

        async restore(type, id, label) {
            if (!confirm(`Restaurer ${label} ? ${type === 'inscriptions' || type === 'paiements' ? '(Si l\'étudiant est aussi supprimé, il sera restauré aussi)' : ''}`)) return;
            try {
                const res = await fetch(`{{ url('/esbtp/trash') }}/${type}/${id}/restore`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message);
                alert(data.message);
                await this.reload();
            } catch (e) {
                alert('Erreur : ' + e.message);
            }
        },

        askForceDelete(type, id, label) {
            this.forceTarget = { type, id, label };
            this.forceModalOpen = true;
        },

        async confirmForceDelete() {
            if (!this.forceTarget) return;
            this.forceSaving = true;
            try {
                const { type, id, label } = this.forceTarget;
                const res = await fetch(`{{ url('/esbtp/trash') }}/${type}/${id}/force`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message);
                alert(data.message);
                this.forceModalOpen = false;
                await this.reload();
            } catch (e) {
                alert('Erreur : ' + e.message);
            } finally {
                this.forceSaving = false;
            }
        },
    };
}
</script>
@endsection
