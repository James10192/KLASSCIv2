@extends('layouts.app')

@section('title', 'Pilotage académique')

@php
    $anneeOptions = $annees->mapWithKeys(fn ($annee) => [$annee->id => $annee->name ?? (string) $annee->annee_debut])->all();
    $classeOptions = $classes->mapWithKeys(fn ($classe) => [$classe->id => trim(($classe->code ? $classe->code.' · ' : '').$classe->name)])->all();
    $initialSummary = $initialSummary ?? [];
@endphp

@push('styles')
<style>
.cpa-shell { display: grid; gap: 1rem; }
.cpa-filter-panel { overflow: visible; position: relative; z-index: 30; }
.cpa-filter-panel:has(.au-select-trigger--open) { z-index: 1400; }
.cpa-filter-panel .cpa-toolbar { overflow: visible; }
.cpa-filters { display: grid; grid-template-columns: minmax(170px, .9fr) minmax(150px, .75fr) minmax(140px, .7fr) minmax(280px, 1.65fr); gap: .75rem; align-items: end; overflow: visible; }
.cpa-filters .au-select,
.cpa-filters .au-select-trigger { width: 100%; }
.cpa-filters .au-select:has(.au-select-trigger--open) { z-index: 1300; }
.cpa-filters .au-select-menu { left: 0; right: auto; min-width: 100%; width: max(100%, 260px); max-height: min(380px, 46vh); z-index: 1301; }
.cpa-filters .cpa-filter-year .au-select-menu { width: max(100%, 260px); }
.cpa-filters .cpa-filter-class .au-select-menu { width: min(520px, calc(100vw - 48px)); }
.cpa-filters .au-select-option { align-items: flex-start; gap: .65rem; }
.cpa-filters .au-select-option-label { white-space: normal; line-height: 1.25; overflow: visible; text-overflow: clip; }
.cpa-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: .9rem; }
.cpa-tabs { display: flex; gap: .35rem; overflow-x: auto; padding: .35rem; background: #fff; border: 1px solid #e8ecf1; border-radius: 14px; }
.cpa-tab { border: 0; background: transparent; color: #64748b; border-radius: 10px; min-height: 44px; padding: .55rem .9rem; font-weight: 700; font-size: .82rem; display: inline-flex; align-items: center; gap: .45rem; white-space: nowrap; }
.cpa-tab:hover { background: #f1f5f9; color: #0453cb; }
.cpa-tab.is-active { background: #0453cb; color: #fff; box-shadow: 0 8px 20px rgba(4,83,203,.16); }
.cpa-panel { background: #fff; border: 1px solid #e8ecf1; border-radius: 16px; padding: 1rem; box-shadow: 0 12px 28px rgba(15,23,42,.05); }
.cpa-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .85rem; }
.cpa-panel-title { margin: 0; color: #0f172a; font-size: 1rem; font-weight: 800; display: flex; align-items: center; gap: .55rem; }
.cpa-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
.cpa-kpi { border: 1px solid #e8ecf1; border-radius: 10px; padding: .85rem; background: #f8fafc; min-height: 92px; }
.cpa-kpi-label { color: #64748b; font-size: .72rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0; }
.cpa-kpi-value { color: #0f172a; font-size: 1.55rem; line-height: 1.1; font-weight: 900; margin-top: .25rem; }
.cpa-kpi small { color: #64748b; font-weight: 600; }
.cpa-list { display: grid; gap: .6rem; }
.cpa-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .75rem; align-items: center; border: 1px solid #e8ecf1; border-radius: 10px; padding: .8rem; background: #fff; }
.cpa-row:hover { border-color: #cbd5e1; background: #fbfdff; }
.cpa-row-main { min-width: 0; }
.cpa-row-title { color: #0f172a; font-weight: 800; font-size: .9rem; overflow-wrap: anywhere; }
.cpa-row-meta { display: flex; gap: .45rem; flex-wrap: wrap; margin-top: .3rem; color: #64748b; font-size: .76rem; }
.cpa-audit { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; margin-top: .65rem; }
.cpa-audit-item { border: 1px solid #e8ecf1; border-radius: 8px; padding: .45rem .55rem; background: #f8fafc; min-width: 0; }
.cpa-audit-label { display: block; color: #64748b; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0; }
.cpa-audit-value { display: block; color: #0f172a; font-size: .76rem; font-weight: 800; margin-top: .1rem; overflow-wrap: anywhere; }
.cpa-badge { display: inline-flex; align-items: center; gap: .25rem; min-height: 24px; padding: .18rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 800; background: #eff6ff; color: #0453cb; }
.cpa-badge--ok { background: #ecfdf5; color: #047857; }
.cpa-badge--warn { background: #fff7ed; color: #c2410c; }
.cpa-badge--danger { background: #fef2f2; color: #b91c1c; }
.cpa-btn { border: 1px solid #dbe5f2; background: #fff; color: #0453cb; border-radius: 10px; min-height: 44px; padding: .55rem .85rem; font-weight: 800; display: inline-flex; align-items: center; gap: .45rem; }
.cpa-btn:hover { background: #eff6ff; border-color: #bfdbfe; }
.cpa-btn--primary { background: #0453cb; color: #fff; border-color: #0453cb; }
.cpa-btn--primary:hover { background: #0347b0; color: #fff; }
.cpa-state { border: 1px dashed #cbd5e1; border-radius: 12px; padding: 1.2rem; color: #64748b; text-align: center; background: #f8fafc; }
.cpa-state i { color: #0453cb; display: block; font-size: 1.35rem; margin-bottom: .4rem; }
.cpa-split { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(300px, .8fr); gap: 1rem; }
.cpa-drawer { border: 1px solid #dbe5f2; border-radius: 14px; background: #f8fafc; padding: 1rem; min-height: 220px; }
.cpa-muted { color: #64748b; font-size: .82rem; margin: 0; }
.cpa-error { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
.cpa-loading { opacity: .65; pointer-events: none; }
.cpa-btn[disabled] { opacity: .65; cursor: not-allowed; }
@media (max-width: 1100px) { .cpa-grid, .cpa-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cpa-split { grid-template-columns: 1fr; } }
@media (max-width: 900px) { .cpa-audit { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 640px) { .cpa-grid, .cpa-filters, .cpa-audit { grid-template-columns: 1fr; } .cpa-row { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="container-fluid cpa-shell"
     x-data="cpaDashboard({
        dataUrl: @js(route('esbtp.pilotage-academique.data')),
        syncUrl: @js(route('esbtp.pilotage-academique.synchronize')),
        classUrl: @js(url('/esbtp/pilotage-academique/classes')),
        studentUrl: @js(url('/esbtp/pilotage-academique/etudiants')),
        initialFilters: @js($initialFilters),
     })"
     x-init="init()">

    <x-planning-header
        title="Centre de pilotage académique"
        subtitle="Suivi réel des fiches, alertes, scores et blocages BTS/LMD"
        activeTab="overview"
        :anneeSelectionnee="$selectedYear"
        :annees="$annees"
        :stats="[
            'total_seances' => $initialSummary['sheets_pending'] ?? 0,
            'total_heures' => $initialSummary['operational_score'] ?? 0,
            'total_classes' => $classes->count(),
            'total_matieres' => $initialSummary['open_alerts'] ?? 0,
            'total_enseignants' => $initialSummary['blocking_alerts'] ?? 0,
        ]"
    />

    <section class="cpa-panel cpa-filter-panel">
        <div class="cpa-toolbar">
            <form class="cpa-filters" x-ref="filtersForm" @change.debounce.150ms="handleFilterChange()" @submit.prevent="applyFilters()">
                <x-au-select name="year_id" class="cpa-filter-year" :options="$anneeOptions" :value="$initialFilters['year_id']" placeholder="Année universitaire" icon="fa-calendar" searchable />
                <x-au-select name="period" :options="$periods" :value="$initialFilters['period']" placeholder="Période" icon="fa-layer-group" />
                <x-au-select name="system" :options="$systems" :value="$initialFilters['system']" placeholder="Système" icon="fa-graduation-cap" />
                <x-au-select name="class_id" class="cpa-filter-class" :options="$classeOptions" :value="$initialFilters['class_id']" placeholder="Toutes les classes" icon="fa-school" searchable />
            </form>
            <button type="button" class="cpa-btn cpa-btn--primary" @click="synchronize()" :disabled="loading || syncing">
                <i class="fas" :class="syncing ? 'fa-spinner fa-spin' : 'fa-rotate'"></i>
                <span x-text="syncing ? 'Synchronisation...' : 'Synchroniser la vue'"></span>
            </button>
        </div>

        <template x-if="syncResult">
            <div class="cpa-state mt-3" :class="{ 'cpa-error': syncResult.ok === false }">
                <i class="fas" :class="syncResult.ok === false ? 'fa-circle-exclamation' : 'fa-circle-check'"></i>
                <div>
                    <strong x-text="syncResult.message"></strong>
                    <p class="cpa-muted mt-1" x-text="syncSummary()"></p>
                </div>
            </div>
        </template>

        <div class="cpa-tabs" role="tablist">
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'direction' }" @click="tab = 'direction'"><i class="fas fa-gauge-high"></i>Direction</button>
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'sheets' }" @click="tab = 'sheets'"><i class="fas fa-clipboard-check"></i>Notes et fiches</button>
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'alerts' }" @click="tab = 'alerts'"><i class="fas fa-triangle-exclamation"></i>Alertes</button>
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'classes' }" @click="tab = 'classes'"><i class="fas fa-school"></i>Santé classe</button>
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'students' }" @click="tab = 'students'"><i class="fas fa-user-graduate"></i>Santé étudiant</button>
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'mine' }" @click="tab = 'mine'"><i class="fas fa-user-check"></i>Mon suivi</button>
        </div>
    </section>

    <template x-if="error">
        <div class="cpa-state cpa-error"><i class="fas fa-circle-exclamation"></i><span x-text="error"></span></div>
    </template>

    <section class="cpa-panel" :class="{ 'cpa-loading': loading }" x-show="tab === 'direction'">
        <div class="cpa-panel-head">
            <h2 class="cpa-panel-title"><i class="fas fa-gauge-high"></i>Vue direction</h2>
            <p class="cpa-muted" x-text="freshnessLabel()"></p>
        </div>
        <div class="cpa-grid">
            <template x-for="item in kpis()" :key="item.label">
                <div class="cpa-kpi">
                    <div class="cpa-kpi-label" x-text="item.label"></div>
                    <div class="cpa-kpi-value" x-text="item.value"></div>
                    <small x-text="item.help"></small>
                </div>
            </template>
        </div>
    </section>

    <section class="cpa-split" x-show="tab === 'sheets'">
        <div class="cpa-panel">
            <div class="cpa-panel-head"><h2 class="cpa-panel-title"><i class="fas fa-clipboard-check"></i>Suivi des fiches</h2></div>
            <div class="cpa-list" x-show="data.sheets?.length">
                <template x-for="sheet in data.sheets" :key="sheet.id">
                    <article class="cpa-row">
                        <div class="cpa-row-main">
                            <div class="cpa-row-title" x-text="sheet.code"></div>
                            <div class="cpa-row-meta">
                                <span x-text="sheet.classe || 'Classe non disponible'"></span>
                                <span x-text="sheet.matiere || 'Matière non renseignée'"></span>
                                <span x-text="sheet.teacher || 'Responsable non affecté'"></span>
                            </div>
                            <div class="cpa-audit">
                                <div class="cpa-audit-item">
                                    <span class="cpa-audit-label">Notes saisies</span>
                                    <span class="cpa-audit-value" x-text="entryProgress(sheet)"></span>
                                </div>
                                <div class="cpa-audit-item">
                                    <span class="cpa-audit-label">Notes par</span>
                                    <span class="cpa-audit-value" x-text="entryActorsLabel(sheet)"></span>
                                </div>
                                <div class="cpa-audit-item">
                                    <span class="cpa-audit-label">Contrôle par</span>
                                    <span class="cpa-audit-value" x-text="actorLabel(sheet.controlled_by, null, 'Non contrôlé')"></span>
                                </div>
                                <div class="cpa-audit-item">
                                    <span class="cpa-audit-label">Validation par</span>
                                    <span class="cpa-audit-value" x-text="actorLabel(sheet.validated_by, null, 'Non validé')"></span>
                                </div>
                                <div class="cpa-audit-item">
                                    <span class="cpa-audit-label">Réception par</span>
                                    <span class="cpa-audit-value" x-text="actorLabel(sheet.received_by, sheet.submitted_by, 'Non reçu')"></span>
                                </div>
                                <div class="cpa-audit-item">
                                    <span class="cpa-audit-label">Dernier audit</span>
                                    <span class="cpa-audit-value" x-text="latestAuditLabel(sheet)"></span>
                                </div>
                            </div>
                        </div>
                        <span class="cpa-badge" :class="badgeClass(sheet.status)" x-text="sheet.status_label"></span>
                    </article>
                </template>
            </div>
            <div class="cpa-state" x-show="!data.sheets?.length"><i class="fas fa-clipboard"></i>Aucune fiche sur ce périmètre.</div>
        </div>
        <div class="cpa-drawer">
            <h3 class="cpa-panel-title"><i class="fas fa-circle-info"></i>Préparation bulletin</h3>
            <p class="cpa-muted">Les générations BTS et LMD sont bloquées lorsque les fiches ou les notes attendues sont incomplètes. Les overrides restent soumis à permission et motif audité.</p>
        </div>
    </section>

    <section class="cpa-panel" x-show="tab === 'alerts'">
        <div class="cpa-panel-head"><h2 class="cpa-panel-title"><i class="fas fa-triangle-exclamation"></i>Alertes et blocages</h2></div>
        <div class="cpa-list" x-show="data.alerts?.length">
            <template x-for="alert in data.alerts" :key="alert.id">
                <article class="cpa-row">
                    <div class="cpa-row-main">
                        <div class="cpa-row-title" x-text="alert.message"></div>
                        <div class="cpa-row-meta">
                            <span x-text="alert.classe || 'Établissement'"></span>
                            <span x-show="alert.student" x-text="alert.student"></span>
                            <span x-text="alert.recommended_action || 'Action à préciser'"></span>
                        </div>
                    </div>
                    <span class="cpa-badge" :class="severityClass(alert.severity)" x-text="alert.severity_label"></span>
                </article>
            </template>
        </div>
        <div class="cpa-state" x-show="!data.alerts?.length"><i class="fas fa-shield-check"></i>Aucune alerte sur ce périmètre.</div>
    </section>

    <section class="cpa-split" x-show="tab === 'classes'">
        <div class="cpa-panel">
            <div class="cpa-panel-head"><h2 class="cpa-panel-title"><i class="fas fa-school"></i>Santé des classes</h2></div>
            <div class="cpa-list" x-show="data.classes?.length">
                <template x-for="classe in data.classes" :key="classe.id">
                    <article class="cpa-row">
                        <div class="cpa-row-main">
                            <div class="cpa-row-title" x-text="classe.name"></div>
                            <div class="cpa-row-meta">
                                <span x-text="classe.system"></span>
                                <span x-text="scoreLabel(classe.academic_score, 'score académique')"></span>
                                <span x-text="scoreLabel(classe.operational_score, 'préparation')"></span>
                                <span x-text="`${classe.coverage_pct}% couverture`"></span>
                            </div>
                        </div>
                        @can('academic_health.view')
                        <button type="button" class="cpa-btn" @click="openClass(classe.id)"><i class="fas fa-eye"></i>Voir</button>
                        @endcan
                    </article>
                </template>
            </div>
            <div class="cpa-state" x-show="!data.classes?.length"><i class="fas fa-school"></i>Aucune classe calculée sur ce périmètre.</div>
        </div>
        <div class="cpa-drawer">
            <template x-if="drawer.class">
                <div>
                    <h3 class="cpa-panel-title" x-text="drawer.class.classe"></h3>
                    <p class="cpa-muted" x-text="drawer.class.health ? scoreLabel(drawer.class.health.academic_score, 'score académique') : 'Données insuffisantes'"></p>
                    <div class="cpa-list mt-3">
                        <template x-for="alert in drawer.class.alerts" :key="alert.id">
                            <div class="cpa-row"><div class="cpa-row-main"><div class="cpa-row-title" x-text="alert.message"></div></div></div>
                        </template>
                    </div>
                </div>
            </template>
            <div class="cpa-state" x-show="!drawer.class"><i class="fas fa-arrow-left"></i>Sélectionnez une classe.</div>
        </div>
    </section>

    <section class="cpa-split" x-show="tab === 'students' || tab === 'mine'">
        <div class="cpa-panel">
            <div class="cpa-panel-head"><h2 class="cpa-panel-title"><i class="fas fa-user-graduate"></i>Étudiants à suivre</h2></div>
            <div class="cpa-list" x-show="data.students?.length">
                <template x-for="student in data.students" :key="student.id">
                    <article class="cpa-row">
                        <div class="cpa-row-main">
                            <div class="cpa-row-title" x-text="student.name"></div>
                            <div class="cpa-row-meta">
                                <span x-text="student.matricule || 'Sans matricule'"></span>
                                <span x-text="scoreLabel(student.score, 'score')"></span>
                                <span x-text="`${student.coverage_pct}% couverture`"></span>
                            </div>
                        </div>
                        @can('academic_health.view')
                        <button type="button" class="cpa-btn" @click="openStudent(student.id)"><i class="fas fa-eye"></i>Voir</button>
                        @endcan
                    </article>
                </template>
            </div>
            <div class="cpa-state" x-show="!data.students?.length"><i class="fas fa-user-graduate"></i>Aucun étudiant calculé sur ce périmètre.</div>
        </div>
        <div class="cpa-drawer">
            <template x-if="drawer.student">
                <div>
                    <h3 class="cpa-panel-title" x-text="drawer.student.student.name"></h3>
                    <p class="cpa-muted" x-text="drawer.student.health ? scoreLabel(drawer.student.health.academic_score, 'score académique') : 'Données insuffisantes'"></p>
                    <div class="cpa-list mt-3">
                        <template x-for="alert in drawer.student.alerts" :key="alert.id">
                            <div class="cpa-row"><div class="cpa-row-main"><div class="cpa-row-title" x-text="alert.message"></div></div></div>
                        </template>
                    </div>
                </div>
            </template>
            <div class="cpa-state" x-show="!drawer.student"><i class="fas fa-arrow-left"></i>Sélectionnez un étudiant.</div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cpaDashboard', (config) => ({
        tab: 'direction',
        loading: false,
        syncing: false,
        error: null,
        syncResult: null,
        suppressFilterChange: false,
        filterKeys: ['year_id', 'period', 'system', 'class_id'],
        data: { summary: {}, classes: [], alerts: [], sheets: [], students: [], freshness: {} },
        drawer: { class: null, student: null },
        filters: { ...config.initialFilters },
        init() {
            const params = new URLSearchParams(location.search);
            this.filters = { ...this.filters, ...this.pickFilters(Object.fromEntries(params.entries())) };
            this.syncFilterControls();
            this.load();
            window.addEventListener('popstate', () => this.loadFromUrl());
        },
        formFilters() {
            const form = this.$refs.filtersForm || this.$root.querySelector('form.cpa-filters');
            if (!form) {
                return { ...this.filters };
            }
            const values = Object.fromEntries(new FormData(form).entries());
            return this.pickFilters({ ...this.filters, ...values });
        },
        handleFilterChange() {
            if (this.suppressFilterChange) return;
            this.applyFilters();
        },
        applyFilters() {
            if (this.loading) return;
            this.closeFilterMenus();
            this.syncResult = null;
            this.filters = this.formFilters();
            const params = new URLSearchParams(this.compactFilters(this.filters));
            const nextUrl = params.toString() ? `${location.pathname}?${params.toString()}` : location.pathname;
            history.pushState({}, '', nextUrl);
            this.load();
        },
        async synchronize() {
            if (this.syncing || this.loading) return;
            this.closeFilterMenus();
            this.filters = this.formFilters();
            this.syncing = true;
            this.error = null;
            this.syncResult = null;

            try {
                const payload = await this.postJson(config.syncUrl, this.compactFilters(this.filters));
                this.syncResult = payload;
                if (payload.filters) {
                    this.filters = { ...this.filters, ...payload.filters };
                    this.syncFilterControls();
                }
                const params = new URLSearchParams(this.compactFilters(this.filters));
                history.pushState({}, '', params.toString() ? `${location.pathname}?${params.toString()}` : location.pathname);
                await this.load();
            } catch (e) {
                this.syncResult = { ok: false, message: e.message, sync: null };
            } finally {
                this.syncing = false;
            }
        },
        loadFromUrl() {
            const params = new URLSearchParams(location.search);
            this.filters = { ...config.initialFilters, ...this.pickFilters(Object.fromEntries(params.entries())) };
            this.syncFilterControls();
            this.load();
        },
        syncFilterControls() {
            this.suppressFilterChange = true;
            Object.entries(this.filters).forEach(([name, value]) => {
                const field = this.$root.querySelector(`[name="${name}"]`);
                if (field && field.value !== String(value ?? '')) {
                    field.value = value ?? '';
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
            this.$nextTick(() => { this.suppressFilterChange = false; });
        },
        async load() {
            this.filters = this.formFilters();
            this.loading = true;
            this.error = null;
            try {
                const params = new URLSearchParams(this.compactFilters(this.filters));
                const payload = await this.fetchJson(`${config.dataUrl}?${params.toString()}`);
                this.data = payload;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },
        async openClass(id) {
            try {
                const params = new URLSearchParams(this.compactFilters(this.filters));
                this.drawer.class = await this.fetchJson(`${config.classUrl}/${id}?${params.toString()}`);
            } catch (e) {
                this.error = e.message;
            }
        },
        async openStudent(id) {
            try {
                const params = new URLSearchParams(this.compactFilters(this.filters));
                this.drawer.student = await this.fetchJson(`${config.studentUrl}/${id}?${params.toString()}`);
            } catch (e) {
                this.error = e.message;
            }
        },
        async fetchJson(url) {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || !payload.ok) {
                throw new Error(payload.message || 'Impossible de charger les données de pilotage.');
            }
            return payload;
        },
        async postJson(url, body) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(body),
            });
            let payload;
            try {
                payload = await response.json();
            } catch (e) {
                throw new Error('La synchronisation a répondu dans un format inattendu.');
            }
            if (!response.ok) {
                throw new Error(payload.message || 'Impossible de synchroniser le pilotage académique.');
            }
            return payload;
        },
        compactFilters(filters) {
            return Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== null && value !== undefined && value !== ''));
        },
        pickFilters(filters) {
            return Object.fromEntries(this.filterKeys.map((key) => [key, filters[key] ?? '']));
        },
        closeFilterMenus() {
            this.$root.querySelectorAll('.cpa-filters .au-select-trigger--open').forEach((trigger) => {
                trigger.click();
            });
        },
        syncSummary() {
            const sync = this.syncResult?.sync;
            if (!sync) return '';
            return `${sync.classes_processed || 0} classe(s), ${sync.students_processed || 0} étudiant(s), ${sync.class_snapshots || 0} snapshot(s) classe, ${sync.student_snapshots || 0} snapshot(s) étudiant, ${sync.alerts_seen || 0} alerte(s) vérifiée(s).`;
        },
        actorLabel(primary, fallback, emptyLabel) {
            return primary || fallback || emptyLabel;
        },
        entryProgress(sheet) {
            const resolved = Number(sheet.resolved_entries_count || 0);
            const total = Number(sheet.entries_count || 0);
            if (!total) return 'Aucune note attendue';
            return `${resolved}/${total}`;
        },
        entryActorsLabel(sheet) {
            if (sheet.entry_actors?.length) {
                return sheet.entry_actors.join(', ');
            }
            return this.actorLabel(sheet.entered_by, sheet.assigned_processor, 'Non renseigné');
        },
        latestAuditLabel(sheet) {
            const event = sheet.latest_event;
            if (!event) return 'Aucun événement';
            const actor = event.actor || 'Système';
            const date = event.occurred_at ? new Date(event.occurred_at).toLocaleString('fr-FR') : '';
            return date ? `${actor}, ${date}` : actor;
        },
        kpis() {
            const s = this.data.summary || {};
            return [
                { label: 'Score académique', value: this.percent(s.academic_score), help: 'Classes avec données suffisantes' },
                { label: 'Préparation opérationnelle', value: this.percent(s.operational_score), help: 'Fiches, saisie et validation' },
                { label: 'Alertes ouvertes', value: s.open_alerts ?? 0, help: `${s.blocking_alerts ?? 0} blocage(s)` },
                { label: 'Fiches à suivre', value: s.sheets_pending ?? 0, help: `${s.bulletin_blockers ?? 0} bulletin(s) bloqué(s)` },
            ];
        },
        percent(value) { return value === null || value === undefined || Number.isNaN(Number(value)) ? '—' : `${Number(value).toFixed(0)}%`; },
        scoreLabel(value, label) { return value === null || value === undefined ? `${label} indisponible` : `${label} ${Number(value).toFixed(0)}%`; },
        freshnessLabel() {
            const f = this.data.freshness || {};
            return f.last_updated_at ? `Dernière mise à jour ${new Date(f.last_updated_at).toLocaleString('fr-FR')} · ${f.stale_count || 0} snapshot(s) obsolète(s)` : 'Aucune donnée calculée';
        },
        badgeClass(status) {
            if (['validated', 'controlled', 'entered'].includes(status)) return 'cpa-badge--ok';
            if (['rejected', 'correction_requested'].includes(status)) return 'cpa-badge--danger';
            return 'cpa-badge--warn';
        },
        severityClass(severity) {
            if (severity === 'blocking' || severity === 'critical') return 'cpa-badge--danger';
            if (severity === 'warning') return 'cpa-badge--warn';
            return 'cpa-badge--ok';
        },
    }));
});
</script>
@endpush
