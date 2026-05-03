@extends('layouts.app')

@section('title', "Journal d'audit & sécurité")

@section('content')
<div class="container-fluid au-page" x-data="auditPage()" x-init="init()">

    {{-- ═══════════════════════════════ HERO ═══════════════════════════════ --}}
    <div class="au-hero">
        <div class="au-hero-top">
            <div class="au-hero-left">
                <div class="au-hero-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="au-hero-info">
                    <h1>Journal d'audit & sécurité</h1>
                    <p>Surveillance et traçabilité des actions système</p>
                </div>
            </div>
            <div class="au-hero-actions">
                <button type="button" class="au-btn au-btn--glass" @click="advancedFiltersOpen = true">
                    <i class="fas fa-sliders-h"></i> Filtres avancés
                </button>
                @can('comptabilite.audit.view')
                    <a href="{{ route('esbtp.audit.comptabilite') }}" class="au-btn au-btn--glass" title="Audit comptabilité">
                        <i class="fas fa-coins"></i> <span class="d-none d-md-inline">Comptabilité</span>
                    </a>
                @endcan
                @can('security.users.monitor')
                    <a href="{{ route('esbtp.audit.user-activity') }}" class="au-btn au-btn--glass" title="Activité utilisateurs">
                        <i class="fas fa-user-clock"></i> <span class="d-none d-md-inline">Activité</span>
                    </a>
                @endcan
                @can('security.audit.export')
                    <div class="dropdown">
                        <button type="button" class="au-btn au-btn--white dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                        <ul class="dropdown-menu au-dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" @click.prevent="exportData('pdf')"><i class="fas fa-file-pdf text-danger me-2"></i> Format PDF</a></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="exportData('excel')"><i class="fas fa-file-excel text-success me-2"></i> Format Excel</a></li>
                        </ul>
                    </div>
                @endcan
            </div>
        </div>

        <div class="au-kpis">
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-database"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($stats['total_audits']) }}</div>
                    <div class="au-kpi-label">Total audits</div>
                </div>
            </div>
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-calendar-day"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($stats['today_audits']) }}</div>
                    <div class="au-kpi-label">Aujourd'hui</div>
                </div>
            </div>
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-calendar-week"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($stats['week_audits']) }}</div>
                    <div class="au-kpi-label">Cette semaine</div>
                </div>
            </div>
            <div class="au-kpi au-kpi--alert">
                <div class="au-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($stats['critical_events']) }}</div>
                    <div class="au-kpi-label">Événements critiques</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ FILTRES RAPIDES ═══════════════════════════════ --}}
    <div class="au-filters">
        <div class="au-filters-row">
            <div class="au-filter-field au-filter-field--grow">
                <label><i class="fas fa-search"></i></label>
                <input type="text" x-model="filters.search" @input.debounce.300ms="reload()"
                       placeholder="Rechercher : ID entité, IP, contenu valeur…">
            </div>
            <div class="au-filter-field">
                <x-au-select
                    x-model="filters.event"
                    @change="reload()"
                    placeholder="Tous les événements"
                    :options="[
                        'created' => 'Création',
                        'updated' => 'Modification',
                        'deleted' => 'Suppression',
                        'restored' => 'Restauration',
                        'retrieved' => 'Consultation',
                    ]" />
            </div>
            <div class="au-filter-field">
                <x-au-select
                    x-model="filters.model_type"
                    @change="reload()"
                    placeholder="Tous les modèles"
                    :searchable="count($auditableModels) > 8"
                    :options="$auditableModels" />
            </div>
            <div class="au-filter-field">
                <input type="date" x-model="filters.date_from" @change="reload()" title="Date début">
            </div>
            <div class="au-filter-field">
                <input type="date" x-model="filters.date_to" @change="reload()" title="Date fin">
            </div>
            <button type="button" class="au-filter-reset" @click="resetFilters()" title="Réinitialiser">
                <i class="fas fa-undo"></i>
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════ TABLEAU ═══════════════════════════════ --}}
    <div class="au-card">
        <div class="au-card-header">
            <div class="au-card-title">
                <i class="fas fa-list-ul"></i> Logs d'audit
                <span class="au-badge-count" x-show="audits.length > 0" x-cloak x-text="totalCount + ' résultats'"></span>
            </div>
            <button type="button" class="au-icon-btn" @click="reload()" title="Actualiser">
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
            </button>
        </div>

        <div class="au-table-wrap">
            {{-- Loading --}}
            <div class="au-loading" x-show="loading" x-cloak>
                <div class="au-spinner"></div>
                <p>Chargement des données…</p>
            </div>

            {{-- Empty --}}
            <div class="au-empty" x-show="!loading && audits.length === 0" x-cloak>
                <i class="fas fa-search"></i>
                <h3>Aucun audit trouvé</h3>
                <p>Essayez de modifier vos critères de recherche.</p>
            </div>

            {{-- Table --}}
            <table class="au-table" x-show="!loading && audits.length > 0" x-cloak>
                <thead>
                    <tr>
                        <th>Date / Heure</th>
                        <th>Utilisateur</th>
                        <th>Action</th>
                        <th>Modèle</th>
                        <th>ID Entité</th>
                        <th>Changements</th>
                        <th>Risque</th>
                        <th class="au-th-actions">Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="audit in audits" :key="audit.id">
                        <tr @click="openQuickModal(audit)">
                            <td>
                                <div class="au-cell-date">
                                    <i class="far fa-clock"></i>
                                    <span x-text="audit.created_at"></span>
                                </div>
                            </td>
                            <td>
                                <div class="au-cell-user">
                                    <span class="au-avatar" x-text="userInitial(audit.user)"></span>
                                    <span x-text="audit.user"></span>
                                </div>
                            </td>
                            <td><span class="au-chip" :class="'au-chip--' + audit.event_raw" x-text="audit.event"></span></td>
                            <td><span class="au-chip au-chip--neutral" x-text="audit.auditable_type"></span></td>
                            <td><code class="au-code" x-text="'#' + audit.auditable_id"></code></td>
                            <td>
                                <span class="au-changes" x-show="audit.changes && audit.changes.length > 0"
                                      x-text="audit.changes.length + ' champ' + (audit.changes.length > 1 ? 's' : '') + ' modifié' + (audit.changes.length > 1 ? 's' : '')"></span>
                                <span class="au-changes au-changes--empty" x-show="!audit.changes || audit.changes.length === 0">Aucun</span>
                            </td>
                            <td><span class="au-chip" :class="'au-chip--risk-' + riskClass(audit.risk_level)" x-text="audit.risk_level"></span></td>
                            <td class="au-td-actions" @click.stop>
                                <a :href="`/esbtp/audit/${audit.id}`" class="au-icon-btn au-icon-btn--primary" title="Voir détail complet">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="au-pagination" x-show="!loading && lastPage > 1" x-cloak>
            <button class="au-page-btn" :disabled="currentPage <= 1" @click="changePage(currentPage - 1)">
                <i class="fas fa-chevron-left"></i> Précédent
            </button>
            <div class="au-page-info">
                Page <strong x-text="currentPage"></strong> sur <strong x-text="lastPage"></strong>
            </div>
            <button class="au-page-btn" :disabled="currentPage >= lastPage" @click="changePage(currentPage + 1)">
                Suivant <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════ MODAL DIFF RAPIDE ═══════════════════════════════ --}}
    <div class="au-modal-backdrop" x-show="quickModalOpen" x-cloak @click.self="quickModalOpen = false" x-transition.opacity>
        <div class="au-modal" x-show="quickModalOpen" x-transition>
            <div class="au-modal-header">
                <div class="au-modal-title">
                    <i class="fas fa-info-circle"></i>
                    <span>Aperçu de l'audit</span>
                    <span class="au-chip" :class="quickModalAudit ? 'au-chip--' + quickModalAudit.event_raw : ''" x-text="quickModalAudit?.event ?? ''"></span>
                </div>
                <button type="button" class="au-icon-btn" @click="quickModalOpen = false"><i class="fas fa-times"></i></button>
            </div>
            <div class="au-modal-body" x-show="quickModalAudit">
                <div class="au-meta-grid">
                    <div><strong>Date</strong><span x-text="quickModalAudit?.created_at"></span></div>
                    <div><strong>Utilisateur</strong><span x-text="quickModalAudit?.user"></span></div>
                    <div><strong>IP</strong><code x-text="quickModalAudit?.ip_address"></code></div>
                    <div><strong>Navigateur</strong><span x-text="quickModalAudit?.user_agent"></span></div>
                    <div><strong>Modèle</strong><span x-text="quickModalAudit?.auditable_type + ' #' + quickModalAudit?.auditable_id"></span></div>
                    <div><strong>Risque</strong><span class="au-chip" :class="quickModalAudit ? 'au-chip--risk-' + riskClass(quickModalAudit.risk_level) : ''" x-text="quickModalAudit?.risk_level"></span></div>
                </div>

                <div class="au-diff-list" x-show="quickModalAudit?.changes && quickModalAudit.changes.length > 0">
                    <h4><i class="fas fa-exchange-alt"></i> Différences</h4>
                    <table class="au-diff-table">
                        <thead>
                            <tr><th>Champ</th><th>Avant</th><th>Après</th></tr>
                        </thead>
                        <tbody>
                            <template x-for="(c, i) in quickModalAudit.changes" :key="i">
                                <tr>
                                    <td><strong x-text="c.field"></strong></td>
                                    <td><span class="au-diff-old" x-text="c.old"></span></td>
                                    <td><span class="au-diff-new" x-text="c.new"></span></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="au-empty au-empty--small" x-show="!quickModalAudit?.changes || quickModalAudit.changes.length === 0">
                    <i class="fas fa-info"></i>
                    <p>Aucun changement enregistré</p>
                </div>
            </div>
            <div class="au-modal-footer">
                <button type="button" class="au-btn au-btn--ghost" @click="quickModalOpen = false">Fermer</button>
                <a :href="quickModalAudit ? `/esbtp/audit/${quickModalAudit.id}` : '#'" class="au-btn au-btn--primary">
                    <i class="fas fa-external-link-alt"></i> Voir détail complet
                </a>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ MODAL FILTRES AVANCÉS ═══════════════════════════════ --}}
    <div class="au-modal-backdrop" x-show="advancedFiltersOpen" x-cloak @click.self="advancedFiltersOpen = false" x-transition.opacity>
        <div class="au-modal" x-show="advancedFiltersOpen" x-transition>
            <div class="au-modal-header">
                <div class="au-modal-title"><i class="fas fa-sliders-h"></i> Filtres avancés</div>
                <button type="button" class="au-icon-btn" @click="advancedFiltersOpen = false"><i class="fas fa-times"></i></button>
            </div>
            <div class="au-modal-body">
                <div class="au-form-grid">
                    <div>
                        <label>Utilisateur</label>
                        <x-au-user-picker
                            x-model="filters.user_id"
                            :users="$users"
                            placeholder="Tous les utilisateurs" />
                    </div>
                    <div>
                        <label>Adresse IP</label>
                        <input type="text" x-model="filters.ip_address" placeholder="Ex : 192.168.1.10">
                    </div>
                    <div>
                        <label>Date début</label>
                        <input type="date" x-model="filters.date_from">
                    </div>
                    <div>
                        <label>Date fin</label>
                        <input type="date" x-model="filters.date_to">
                    </div>
                </div>
            </div>
            <div class="au-modal-footer">
                <button type="button" class="au-btn au-btn--ghost" @click="resetFilters(); advancedFiltersOpen = false;">Réinitialiser</button>
                <button type="button" class="au-btn au-btn--primary" @click="reload(); advancedFiltersOpen = false;">
                    <i class="fas fa-check"></i> Appliquer
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
/* ════════════════════════════════════════════════════════════════════
   AUDIT — Premium Redesign
   Namespace : au-*
   Palette : monochrome KLASSCI bleu + sémantiques (event, risk)
   ════════════════════════════════════════════════════════════════════ */

[x-cloak] { display: none !important; }

.au-page { padding: 1rem 0; }

/* ───── HERO ───── */
.au-hero {
    position: relative;
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.15);
    animation: au-fadeDown .5s ease-out;
}
@keyframes au-fadeDown { from { opacity: 0; transform: translateY(-15px); } to { opacity: 1; transform: translateY(0); } }
.au-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.au-hero-left { display: flex; align-items: center; gap: 1rem; }
.au-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; border: 1px solid rgba(255,255,255,.15); flex-shrink: 0; color: #fff;
}
.au-hero-info h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem; color: #fff; letter-spacing: -.02em; }
.au-hero-info p { margin: 0; opacity: .8; font-size: .88rem; color: rgba(255,255,255,.7); }
.au-hero-actions { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }

/* Boutons hero */
.au-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem 1rem; border-radius: 10px; font-size: .82rem;
    font-weight: 600; text-decoration: none; transition: all .2s;
    border: 1px solid transparent; cursor: pointer; white-space: nowrap;
}
.au-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.au-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.au-btn--white { background: #fff; color: #0453cb; }
.au-btn--white:hover { background: #f0f4ff; color: #0453cb; }
.au-btn--primary { background: #0453cb; color: #fff; }
.au-btn--primary:hover { background: #033a8e; color: #fff; }
.au-btn--ghost { background: transparent; color: #1e293b; border-color: #e2e8f0; }
.au-btn--ghost:hover { background: #f1f5f9; color: #0453cb; border-color: #cbd5e1; }

.au-dropdown-menu {
    background: #fff; border: 1px solid #e8ecf1; border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,.12); padding: .35rem; min-width: 200px; z-index: 1050;
}
.au-dropdown-menu .dropdown-item { color: #1e293b; padding: .5rem .85rem; border-radius: 8px; font-size: .85rem; transition: all .15s; }
.au-dropdown-menu .dropdown-item:hover { background: #f1f5f9; }

/* KPIs */
.au-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.au-kpi {
    flex: 1; min-width: 140px;
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem;
    transition: background .2s;
}
.au-kpi:hover { background: rgba(255,255,255,.15); }
.au-kpi-icon {
    width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .95rem; color: #fff;
}
.au-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
.au-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; text-transform: uppercase; letter-spacing: .04em; }
.au-kpi--alert { border-color: rgba(252,165,165,.4); background: rgba(220,38,38,.18); }
.au-kpi--alert .au-kpi-icon { background: rgba(220,38,38,.3); }

/* ───── FILTRES ───── */
.au-filters {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    padding: 1rem 1.25rem; margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.au-filters-row { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; }
.au-filter-field {
    position: relative; display: flex; align-items: center;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
    transition: all .2s; min-width: 140px;
}
.au-filter-field--grow { flex: 1; min-width: 220px; }
.au-filter-field:focus-within { background: #fff; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08); }
.au-filter-field label { padding: 0 .65rem; color: #64748b; font-size: .85rem; }
.au-filter-field input, .au-filter-field select {
    border: none; background: transparent; outline: none; padding: .55rem .65rem .55rem 0;
    font-size: .85rem; color: #1e293b; flex: 1; min-width: 0;
}
.au-filter-field select { padding-left: .65rem; cursor: pointer; }
.au-filter-reset {
    width: 38px; height: 38px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff;
    display: inline-flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer;
    transition: all .15s;
}
.au-filter-reset:hover { background: #fee2e2; border-color: #fecaca; color: #dc2626; }

/* ───── CARD TABLEAU ───── */
.au-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); overflow: hidden;
}
.au-card-header {
    padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid #f1f5f9; background: #fafbfc;
}
.au-card-title {
    display: flex; align-items: center; gap: .6rem;
    font-size: 1rem; font-weight: 700; color: #0f172a;
}
.au-card-title i { color: #0453cb; }
.au-badge-count {
    background: #eff6ff; color: #0453cb; padding: .2rem .55rem; border-radius: 8px;
    font-size: .72rem; font-weight: 600; border: 1px solid #dbeafe;
}
.au-icon-btn {
    width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff;
    display: inline-flex; align-items: center; justify-content: center; color: #64748b;
    cursor: pointer; transition: all .15s; text-decoration: none;
}
.au-icon-btn:hover { background: #f1f5f9; color: #0453cb; border-color: #cbd5e1; }
.au-icon-btn--primary { background: #eff6ff; border-color: #dbeafe; color: #0453cb; }
.au-icon-btn--primary:hover { background: #dbeafe; color: #033a8e; }

/* ───── TABLEAU ───── */
.au-table-wrap { position: relative; min-height: 240px; overflow-x: auto; }
.au-table { width: 100%; border-collapse: collapse; }
.au-table thead th {
    background: #f8fafc; color: #475569; font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em; padding: .85rem 1rem; text-align: left;
    border-bottom: 1px solid #e2e8f0; white-space: nowrap;
}
.au-table tbody tr {
    border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background .15s;
}
.au-table tbody tr:hover { background: #f8fafc; }
.au-table tbody tr:last-child { border-bottom: none; }
.au-table tbody td { padding: .85rem 1rem; font-size: .85rem; color: #1e293b; vertical-align: middle; }
.au-th-actions, .au-td-actions { text-align: center; width: 60px; }

.au-cell-date { display: flex; align-items: center; gap: .45rem; color: #64748b; font-variant-numeric: tabular-nums; font-size: .82rem; }
.au-cell-date i { color: #94a3b8; font-size: .8rem; }
.au-cell-user { display: flex; align-items: center; gap: .55rem; }
.au-avatar {
    width: 28px; height: 28px; border-radius: 50%;
    background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .72rem; font-weight: 700; flex-shrink: 0;
}
.au-code {
    font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: .78rem;
    background: #f1f5f9; padding: .15rem .45rem; border-radius: 6px; color: #475569;
}
.au-changes { font-size: .8rem; color: #475569; }
.au-changes--empty { color: #94a3b8; font-style: italic; }

/* ───── CHIPS (sémantiques) ───── */
.au-chip {
    display: inline-flex; align-items: center; padding: .25rem .6rem;
    border-radius: 999px; font-size: .72rem; font-weight: 600; line-height: 1.2;
    border: 1px solid transparent; white-space: nowrap;
}
.au-chip--created { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
.au-chip--updated { background: #dbeafe; color: #1e3a8a; border-color: #bfdbfe; }
.au-chip--deleted { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.au-chip--restored { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.au-chip--retrieved { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.au-chip--neutral { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

.au-chip--risk-critique { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.au-chip--risk-eleve { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.au-chip--risk-moyen { background: #dbeafe; color: #1e3a8a; border-color: #bfdbfe; }
.au-chip--risk-faible { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

/* ───── PAGINATION ───── */
.au-pagination {
    padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    border-top: 1px solid #f1f5f9; background: #fafbfc; flex-wrap: wrap;
}
.au-page-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem .85rem; border-radius: 10px; border: 1px solid #e2e8f0;
    background: #fff; color: #475569; font-size: .82rem; font-weight: 600; cursor: pointer; transition: all .15s;
}
.au-page-btn:hover:not(:disabled) { background: #eff6ff; color: #0453cb; border-color: #dbeafe; }
.au-page-btn:disabled { opacity: .4; cursor: not-allowed; }
.au-page-info { font-size: .85rem; color: #64748b; }
.au-page-info strong { color: #0f172a; }

/* ───── LOADING / EMPTY ───── */
.au-loading {
    padding: 3rem 1rem; text-align: center; color: #64748b;
    display: flex; flex-direction: column; align-items: center; gap: .85rem;
}
.au-spinner {
    width: 36px; height: 36px; border-radius: 50%;
    border: 3px solid #e2e8f0; border-top-color: #0453cb; animation: au-spin 1s linear infinite;
}
@keyframes au-spin { to { transform: rotate(360deg); } }
.au-empty {
    padding: 3rem 1rem; text-align: center; color: #64748b;
    display: flex; flex-direction: column; align-items: center; gap: .65rem;
}
.au-empty i { font-size: 2.5rem; color: #cbd5e1; }
.au-empty h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }
.au-empty p { margin: 0; font-size: .85rem; }
.au-empty--small { padding: 1.5rem 1rem; }
.au-empty--small i { font-size: 1.5rem; }

/* ───── MODAL ───── */
.au-modal-backdrop {
    position: fixed; inset: 0; background: rgba(15,23,42,.55);
    display: flex; align-items: center; justify-content: center; z-index: 1080; padding: 1rem;
    overflow-y: auto;
}
.au-modal {
    background: #fff; border-radius: 16px; width: 100%; max-width: 760px;
    box-shadow: 0 20px 50px rgba(15,23,42,.25); max-height: 90vh; display: flex; flex-direction: column;
}
.au-modal-header {
    padding: 1.1rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;
}
.au-modal-title {
    display: flex; align-items: center; gap: .65rem; font-size: 1.05rem; font-weight: 700; color: #0f172a;
}
.au-modal-title i { color: #0453cb; }
.au-modal-body { padding: 1.5rem; overflow-y: auto; flex: 1; }
.au-modal-footer {
    padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; display: flex; gap: .65rem; justify-content: flex-end;
    background: #fafbfc;
}

.au-meta-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: .85rem 1.5rem; margin-bottom: 1.25rem;
}
.au-meta-grid > div { display: flex; flex-direction: column; gap: .15rem; }
.au-meta-grid strong { font-size: .7rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
.au-meta-grid span, .au-meta-grid code { font-size: .88rem; color: #1e293b; }

.au-diff-list h4 {
    font-size: .9rem; font-weight: 700; color: #0f172a; margin: 0 0 .65rem;
    display: flex; align-items: center; gap: .5rem;
}
.au-diff-list h4 i { color: #0453cb; }
.au-diff-table { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.au-diff-table thead th {
    background: #f8fafc; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    color: #475569; padding: .65rem .85rem; text-align: left; border-bottom: 1px solid #e2e8f0;
}
.au-diff-table tbody td { padding: .65rem .85rem; font-size: .82rem; vertical-align: top; border-bottom: 1px solid #f1f5f9; }
.au-diff-table tbody tr:last-child td { border-bottom: none; }
.au-diff-old { display: inline-block; color: #991b1b; background: #fee2e2; padding: .15rem .45rem; border-radius: 6px; font-family: ui-monospace, "SF Mono", monospace; font-size: .78rem; }
.au-diff-new { display: inline-block; color: #065f46; background: #d1fae5; padding: .15rem .45rem; border-radius: 6px; font-family: ui-monospace, "SF Mono", monospace; font-size: .78rem; }

.au-form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.au-form-grid label { display: block; font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .35rem; }
.au-form-grid input, .au-form-grid select {
    width: 100%; padding: .55rem .75rem; border: 1px solid #e2e8f0; border-radius: 10px;
    font-size: .85rem; color: #1e293b; transition: all .2s;
}
.au-form-grid input:focus, .au-form-grid select:focus {
    outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08);
}

/* ───── RESPONSIVE ───── */
@media (max-width: 992px) {
    .au-hero { padding: 1.5rem 1.5rem 1rem; }
    .au-hero-info h1 { font-size: 1.2rem; }
    .au-meta-grid { grid-template-columns: 1fr; }
    .au-form-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .au-filters-row { flex-direction: column; align-items: stretch; }
    .au-filter-field { min-width: 0; width: 100%; }
    .au-filter-reset { align-self: flex-end; }
    .au-table thead { display: none; }
    .au-table tbody, .au-table tr, .au-table td { display: block; width: 100%; }
    .au-table tbody tr { padding: .85rem; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: .65rem; }
    .au-table tbody td { padding: .35rem 0; border: none; }
    .au-table tbody td:before {
        content: attr(data-label); display: block; font-size: .68rem;
        color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: .15rem;
    }
}
@media (max-width: 576px) {
    .au-hero-actions { width: 100%; }
    .au-hero-actions .au-btn { flex: 1; justify-content: center; }
    .au-kpis { gap: .5rem; }
    .au-kpi { min-width: calc(50% - .25rem); padding: .65rem .75rem; }
    .au-kpi-value { font-size: 1.1rem; }
}
</style>
@endpush

@push('scripts')
<script>
function auditPage() {
    return {
        loading: true,
        audits: [],
        currentPage: 1,
        lastPage: 1,
        totalCount: 0,
        filters: {
            search: '',
            event: '',
            model_type: '',
            user_id: '',
            ip_address: '',
            date_from: '',
            date_to: '',
        },
        advancedFiltersOpen: false,
        quickModalOpen: false,
        quickModalAudit: null,

        init() {
            this.reload();
        },

        reload() {
            this.currentPage = 1;
            this.fetchData();
        },

        changePage(page) {
            if (page < 1 || page > this.lastPage) return;
            this.currentPage = page;
            this.fetchData();
        },

        fetchData() {
            this.loading = true;
            const params = { page: this.currentPage };
            Object.keys(this.filters).forEach(k => {
                if (this.filters[k]) params[k] = this.filters[k];
            });

            fetch('{{ route("esbtp.audit.data") }}?' + new URLSearchParams(params), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(data => {
                    // Garder event brut pour les classes CSS
                    this.audits = (data.data || []).map(a => ({
                        ...a,
                        event_raw: this.eventRaw(a.event),
                    }));
                    this.currentPage = data.current_page || 1;
                    this.lastPage = data.last_page || 1;
                    this.totalCount = data.total || 0;
                    this.loading = false;
                })
                .catch(err => {
                    console.error('Audit fetch error:', err);
                    this.loading = false;
                    if (window.toastr) toastr.error('Erreur lors du chargement des audits');
                });
        },

        eventRaw(label) {
            const map = {
                'Création': 'created',
                'Modification': 'updated',
                'Suppression': 'deleted',
                'Restauration': 'restored',
                'Consultation': 'retrieved',
            };
            return map[label] || 'neutral';
        },

        riskClass(level) {
            const map = { 'Critique': 'critique', 'Élevé': 'eleve', 'Moyen': 'moyen', 'Faible': 'faible' };
            return map[level] || 'faible';
        },

        userInitial(name) {
            if (!name) return '?';
            return name.charAt(0).toUpperCase();
        },

        resetFilters() {
            Object.keys(this.filters).forEach(k => this.filters[k] = '');
            this.reload();
        },

        openQuickModal(audit) {
            this.quickModalAudit = audit;
            this.quickModalOpen = true;
        },

        exportData(format) {
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(k => {
                if (this.filters[k]) params.set(k, this.filters[k]);
            });
            const url = format === 'pdf'
                ? '{{ route("esbtp.audit.export.pdf") }}'
                : '{{ route("esbtp.audit.export.excel") }}';
            window.open(url + '?' + params.toString(), '_blank');
        },
    };
}
</script>
@endpush
