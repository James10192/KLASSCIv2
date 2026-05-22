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
            <x-au-select
                x-model="filters.event"
                @change="reload()"
                icon="fa-bolt"
                placeholder="Tous les événements"
                :options="[
                    'created' => 'Création',
                    'updated' => 'Modification',
                    'deleted' => 'Suppression',
                    'restored' => 'Restauration',
                    'retrieved' => 'Consultation',
                ]" />
            <x-au-select
                x-model="filters.model_type"
                @change="reload()"
                icon="fa-cubes"
                placeholder="Tous les modèles"
                :searchable="count($auditableModels) > 8"
                :options="$auditableModels" />
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
            <table class="au-table au-table--clickable" x-show="!loading && audits.length > 0" x-cloak>
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

                {{-- ═══════════════════════════════ LIENS ENTITÉS LIÉES (AJAX) ═══════════════════════════════ --}}
                <div class="au-quick-links-section">
                    <h4>
                        <i class="fas fa-project-diagram"></i> Liens vers les entités liées
                        <span class="au-meta-sub" x-show="quickLinks.length > 0" x-cloak>
                            • <span x-text="quickLinks.length"></span>
                        </span>
                    </h4>
                    <div class="au-quick-links-loading" x-show="quickLinksLoading" x-cloak>
                        <div class="au-spinner au-spinner--sm"></div>
                        <span>Chargement des liens…</span>
                    </div>
                    <div class="al-grid al-grid--compact" x-show="!quickLinksLoading && quickLinks.length > 0" x-cloak>
                        <template x-for="link in quickLinks" :key="link.key + '_' + (link.value || '')">
                            <a :href="link.route || null"
                               :class="'al-item al-item--' + (link.emphasis || 'normal') + (link.route ? ' al-item--linkable' : '')"
                               :title="link.route ? ('Ouvrir : ' + link.value) : ''">
                                <span class="al-icon"><i class="fas" :class="link.icon || 'fa-link'"></i></span>
                                <div class="al-body">
                                    <div class="al-label" x-text="link.label"></div>
                                    <div class="al-value" x-text="link.value"></div>
                                    <div class="al-sub" x-show="link.sublabel" x-text="link.sublabel"></div>
                                </div>
                                <span class="al-arrow" x-show="link.route"><i class="fas fa-arrow-up-right-from-square"></i></span>
                            </a>
                        </template>
                    </div>
                    <div class="au-empty au-empty--small" x-show="!quickLinksLoading && quickLinks.length === 0" x-cloak>
                        <i class="fas fa-link-slash"></i>
                        <p>Aucune entité liée détectée</p>
                    </div>
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
   Styles partagés : @include('esbtp.audit._styles')
   ════════════════════════════════════════════════════════════════════ */
@include('esbtp.audit._styles')

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
        quickLinks: [],
        quickLinksLoading: false,

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
                    // event_raw est servi directement par le backend (slug
                    // Eloquent : created/updated/deleted/...) — pas de
                    // reverse-map fragile depuis le label FR.
                    this.audits = data.data || [];
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
            this.fetchQuickLinks(audit.id);
        },

        fetchQuickLinks(auditId) {
            this.quickLinks = [];
            this.quickLinksLoading = true;
            fetch(`/esbtp/audit/${auditId}/related-links`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.ok ? r.json() : Promise.reject(r))
                .then(data => {
                    this.quickLinks = data.links || [];
                    this.quickLinksLoading = false;
                })
                .catch(() => {
                    this.quickLinks = [];
                    this.quickLinksLoading = false;
                });
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
