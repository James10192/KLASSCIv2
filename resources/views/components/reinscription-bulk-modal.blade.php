@props([
    // Collection d'étudiants éligibles (id, matricule, nom_complet, classe, inscription précédente)
    'students' => null,
    // Décision pré-filtrée : 'passage'|'rattrapage'|'redoublement' ou null pour mode libre
    'decisionContext' => null,
    // ID du modal — permet plusieurs instances sur même page (1 par onglet décision)
    'modalId' => 'bulkReinscriptionModal',
    // Label du bouton trigger (vide = pas de bouton, juste le modal)
    'triggerLabel' => null,
    'triggerClass' => 'btn-acasi success',
    // Label du titre modal — fallback générique si null
    'title' => null,
])

@php
    $students = $students ?? collect();
    $brmStudentsData = collect($students)->map(function ($e) {
        $insc = method_exists($e, 'inscriptions') ? $e->inscriptions->first() : null;
        return [
            'id' => $e->id,
            'matricule' => $e->matricule,
            'nom_complet' => $e->nom_complet ?? trim(($e->nom ?? '') . ' ' . ($e->prenoms ?? '')),
            'classe' => optional($insc)->classe?->name,
            'telephone' => $e->telephone ?? null,
            'email' => $e->email ?? null,
            'fiche_complete' => !empty($e->telephone) && !empty($e->email),
        ];
    })->values();

    $decisionLabel = match($decisionContext) {
        'passage' => 'Passage classe supérieure',
        'rattrapage' => 'Rattrapage',
        'redoublement' => 'Redoublement',
        default => null,
    };
    $effectiveTitle = $title ?? ($decisionLabel
        ? 'Réinscription groupée — ' . $decisionLabel
        : 'Réinscription groupée');

    $alpineFactory = 'reinscriptionBulkModal_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $modalId);
@endphp

@if($triggerLabel)
    <button type="button"
            class="{{ $triggerClass }}"
            data-bs-toggle="modal"
            data-bs-target="#{{ $modalId }}"
            @if($students->isEmpty()) disabled title="Aucun étudiant éligible" @endif>
        <i class="fas fa-layer-group"></i>{{ $triggerLabel }}
    </button>
@endif

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true"
     x-data="{{ $alpineFactory }}()" x-init="init()">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content brm-modal">
            <div class="modal-header brm-modal-header">
                <div class="brm-header-icon"><i class="fas fa-layer-group"></i></div>
                <div class="brm-header-text">
                    <h5 class="modal-title" id="{{ $modalId }}Label">{{ $effectiveTitle }}</h5>
                    <p class="brm-header-sub">
                        @if($decisionContext)
                            Tous les étudiants seront réinscrits avec la décision <strong>{{ $decisionLabel }}</strong>
                        @else
                            Diagnostic automatique de la situation académique et financière par étudiant
                        @endif
                    </p>
                </div>
                <button type="button" class="brm-close-btn" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body brm-modal-body">
                {{-- ÉTAPE 1 : Sélection --}}
                <div x-show="step === 'select'" x-cloak>
                    <div class="brm-section-bar">
                        <i class="fas fa-user-check"></i>
                        <span><strong>Étape 1 :</strong> sélectionne les étudiants à analyser</span>
                    </div>

                    <div class="brm-select-actions">
                        <button type="button" class="brm-btn brm-btn--ghost"
                                @click="selectAll()"
                                :disabled="visibleStudents.length === 0">
                            <i class="fas fa-check-double"></i>
                            <span x-text="allSelected ? 'Tout désélectionner' : 'Tout sélectionner'"></span>
                        </button>
                        <input type="search" class="brm-search" placeholder="Rechercher matricule/nom..."
                               x-model="searchQuery">
                        <div class="brm-counter">
                            <strong x-text="selectedIds.length"></strong> / <span x-text="visibleStudents.length"></span>
                        </div>
                    </div>

                    <div class="brm-students-list">
                        <template x-for="student in visibleStudents" :key="student.id">
                            <label class="brm-student-row" :class="selectedIds.includes(student.id) ? 'brm-student-row--selected' : ''">
                                <input type="checkbox" :value="student.id"
                                       :checked="selectedIds.includes(student.id)"
                                       @change="toggleSelect(student.id)" class="brm-checkbox">
                                <div class="brm-student-info">
                                    <div class="brm-student-name" x-text="student.nom_complet"></div>
                                    <div class="brm-student-meta">
                                        <span class="brm-meta-chip" x-text="student.matricule"></span>
                                        <span class="brm-meta-chip brm-meta-chip--muted" x-text="student.classe || 'Sans classe'"></span>
                                        <span x-show="!student.fiche_complete" class="brm-meta-chip brm-meta-chip--warn" title="Fiche incomplète (téléphone ou email manquant)">
                                            <i class="fas fa-circle-exclamation"></i> Fiche
                                        </span>
                                    </div>
                                </div>
                            </label>
                        </template>
                        <div x-show="visibleStudents.length === 0" x-cloak class="brm-empty">
                            <i class="fas fa-inbox"></i>
                            <p>Aucun étudiant éligible.</p>
                            <small>Filtre actuel : inscription année passée active sans réinscription année courante.</small>
                        </div>
                    </div>
                </div>

                {{-- ÉTAPE 2 : Loading --}}
                <div x-show="step === 'loading'" x-cloak class="brm-loading">
                    <div class="brm-loading-spinner"><i class="fas fa-spinner fa-spin"></i></div>
                    <p>Analyse de <strong x-text="selectedIds.length"></strong> étudiant(s) en cours...</p>
                </div>

                {{-- ÉTAPE 3 : Résultats avec stats live + override décision --}}
                <div x-show="step === 'results'" x-cloak>
                    <div class="brm-section-bar">
                        <i class="fas fa-clipboard-check"></i>
                        <span><strong>Étape 2 :</strong> diagnostic — révise et confirme</span>
                    </div>

                    {{-- Stats live --}}
                    <div class="brm-stats-grid">
                        <div class="brm-stats-card brm-stats-card--total">
                            <div class="brm-stats-label">Total analysé</div>
                            <div class="brm-stats-value" x-text="results.length"></div>
                        </div>
                        <div class="brm-stats-card brm-stats-card--ok">
                            <div class="brm-stats-label">Éligibles</div>
                            <div class="brm-stats-value" x-text="stats.eligible"></div>
                        </div>
                        <div class="brm-stats-card brm-stats-card--warn">
                            <div class="brm-stats-label">Bloqué solde</div>
                            <div class="brm-stats-value" x-text="stats.blockedSolde"></div>
                        </div>
                        <div class="brm-stats-card brm-stats-card--warn">
                            <div class="brm-stats-label">Fiche incomplète</div>
                            <div class="brm-stats-value" x-text="stats.ficheIncomplete"></div>
                        </div>
                    </div>

                    {{-- Breakdown par décision --}}
                    <div class="brm-decisions-row" x-show="!decisionContext">
                        <span class="brm-decision-chip brm-decision-chip--passage">
                            <i class="fas fa-arrow-up"></i> <span x-text="stats.byDecision.passage"></span> Passages
                        </span>
                        <span class="brm-decision-chip brm-decision-chip--rattrapage">
                            <i class="fas fa-rotate"></i> <span x-text="stats.byDecision.rattrapage"></span> Rattrapages
                        </span>
                        <span class="brm-decision-chip brm-decision-chip--redoublement">
                            <i class="fas fa-arrows-rotate"></i> <span x-text="stats.byDecision.redoublement"></span> Redoublements
                        </span>
                        <span class="brm-decision-chip brm-decision-chip--inconnu" x-show="stats.byDecision.inconnu > 0">
                            <i class="fas fa-question"></i> <span x-text="stats.byDecision.inconnu"></span> Inconnus
                        </span>
                    </div>

                    <div class="brm-results-grid">
                        <template x-for="r in results" :key="r.etudiant_id">
                            <div class="brm-result-card" :class="r.peut_reinscrire ? 'brm-result-card--ok' : 'brm-result-card--blocked'">
                                <div class="brm-result-head">
                                    <div class="brm-result-name" x-text="r.nom_complet"></div>
                                    <span class="brm-result-badge"
                                          :class="r.peut_reinscrire ? 'brm-result-badge--ok' : 'brm-result-badge--blocked'"
                                          x-text="r.peut_reinscrire ? 'Éligible' : 'Bloqué'"></span>
                                </div>
                                <div class="brm-result-meta" x-text="(r.matricule || '') + ' · ' + (r.classe_origine || '—')"></div>

                                <div class="brm-result-stats">
                                    <div class="brm-stat">
                                        <div class="brm-stat-label">Moyenne</div>
                                        <div class="brm-stat-value"
                                             :class="r.moyenne !== null && r.moyenne >= 10 ? 'brm-stat-value--ok' : 'brm-stat-value--warn'"
                                             x-text="r.moyenne !== null ? Number(r.moyenne).toFixed(2) + '/20' : '—'"></div>
                                    </div>
                                    <div class="brm-stat">
                                        <div class="brm-stat-label">Décision</div>
                                        <select class="brm-stat-select" x-model="r.decision_override" @change="recomputeStats()">
                                            <option value="">Auto (<span x-text="r.decision || '—'"></span>)</option>
                                            <option value="passage">Passage</option>
                                            <option value="rattrapage">Rattrapage</option>
                                            <option value="redoublement">Redoublement</option>
                                        </select>
                                    </div>
                                    <div class="brm-stat">
                                        <div class="brm-stat-label">Solde</div>
                                        <div class="brm-stat-value brm-stat-value--small"
                                             :class="r.solde_restant <= 0 ? 'brm-stat-value--ok' : 'brm-stat-value--warn'"
                                             x-text="r.solde_restant !== null ? (r.solde_restant <= 0 ? 'Soldé ✓' : Math.round(r.solde_restant).toLocaleString('fr-FR') + ' FCFA') : '—'"></div>
                                    </div>
                                </div>

                                <div class="brm-result-flags" x-show="!r.fiche_complete">
                                    <span class="brm-flag brm-flag--warn">
                                        <i class="fas fa-id-card"></i> Fiche incomplète
                                    </span>
                                </div>
                            </div>
                        </template>

                        <template x-for="r in errors" :key="'err-' + r.etudiant_id">
                            <div class="brm-result-card brm-result-card--error">
                                <div class="brm-result-head">
                                    <div class="brm-result-name" x-text="r.nom_complet || ('Étudiant #' + r.etudiant_id)"></div>
                                    <span class="brm-result-badge brm-result-badge--blocked">Erreur</span>
                                </div>
                                <div class="brm-result-meta" x-text="r.message || r.error"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="modal-footer brm-modal-footer">
                <button type="button" class="brm-btn brm-btn--ghost"
                        @click="backToSelect()"
                        x-show="step === 'results'" x-cloak>
                    <i class="fas fa-arrow-left"></i> Modifier la sélection
                </button>
                <button type="button" class="brm-btn brm-btn--ghost"
                        data-bs-dismiss="modal"
                        x-show="step === 'select'" x-cloak>
                    Annuler
                </button>
                <button type="button" class="brm-btn brm-btn--primary"
                        @click.prevent="analyze()"
                        :disabled="selectedIds.length === 0 || loading"
                        x-show="step === 'select'" x-cloak>
                    <i class="fas fa-magnifying-glass-chart"></i>
                    Analyser <span x-text="selectedIds.length"></span> étudiant(s)
                </button>
                <button type="button" class="brm-btn brm-btn--success"
                        @click.prevent="executeBulk()"
                        :disabled="stats.eligible === 0 || executing"
                        x-show="step === 'results'" x-cloak>
                    <i class="fas fa-check-double"></i>
                    Réinscrire <span x-text="stats.eligible"></span> étudiant(s)
                </button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
window.__brmSharedFactory = function(modalId, students, decisionContext) {
    return {
        modalId,
        decisionContext,
        students,
        selectedIds: [],
        searchQuery: '',
        step: 'select',
        loading: false,
        executing: false,
        results: [],
        errors: [],
        stats: { eligible: 0, blockedSolde: 0, ficheIncomplete: 0,
                 byDecision: { passage: 0, rattrapage: 0, redoublement: 0, inconnu: 0 } },

        init() {
            const modalEl = document.getElementById(this.modalId);
            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', () => this.backToSelect());
            }
        },

        get visibleStudents() {
            if (!this.searchQuery) return this.students;
            const q = this.searchQuery.toLowerCase();
            return this.students.filter(s =>
                (s.matricule || '').toLowerCase().includes(q) ||
                (s.nom_complet || '').toLowerCase().includes(q)
            );
        },
        get allSelected() {
            return this.visibleStudents.length > 0 &&
                   this.visibleStudents.every(s => this.selectedIds.includes(s.id));
        },
        toggleSelect(id) {
            if (this.selectedIds.includes(id)) this.selectedIds = this.selectedIds.filter(x => x !== id);
            else this.selectedIds.push(id);
        },
        selectAll() {
            if (this.allSelected) {
                this.selectedIds = this.selectedIds.filter(id => !this.visibleStudents.some(s => s.id === id));
            } else {
                this.selectedIds = [...new Set([...this.selectedIds, ...this.visibleStudents.map(s => s.id)])];
            }
        },
        backToSelect() {
            this.step = 'select';
            this.results = [];
            this.errors = [];
            this.loading = false;
            this.executing = false;
        },

        async analyze() {
            if (this.selectedIds.length === 0 || this.loading) return;
            if (this.selectedIds.length > 100) {
                window.klassciToast?.('warning', 'Limite : 100 étudiants par analyse');
                return;
            }
            this.loading = true;
            this.step = 'loading';
            try {
                const res = await fetch('/esbtp/reinscription/api/bulk-preview', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ etudiants_ids: this.selectedIds }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data?.message || 'Erreur réseau');
                const payload = data.data || data;
                this.results = (payload.rows || []).filter(r => !r.error).map(r => ({...r, decision_override: ''}));
                this.errors = (payload.rows || []).filter(r => r.error);
                this.step = 'results';
                this.recomputeStats();
            } catch (err) {
                window.klassciToast?.('error', err.message || 'Erreur analyse');
                this.step = 'select';
            } finally {
                this.loading = false;
            }
        },

        recomputeStats() {
            const s = { eligible: 0, blockedSolde: 0, ficheIncomplete: 0,
                        byDecision: { passage: 0, rattrapage: 0, redoublement: 0, inconnu: 0 } };
            this.results.forEach(r => {
                const dec = r.decision_override || r.decision || 'inconnu';
                if (s.byDecision[dec] !== undefined) s.byDecision[dec]++;
                else s.byDecision.inconnu++;
                if (r.peut_reinscrire && dec !== 'inconnu') s.eligible++;
                if (!r.peut_reinscrire) s.blockedSolde++;
                if (!r.fiche_complete) s.ficheIncomplete++;
            });
            this.stats = s;
        },

        async executeBulk() {
            if (this.stats.eligible === 0 || this.executing) return;
            if (!confirm(`Confirmer la réinscription de ${this.stats.eligible} étudiant(s) ? Action en transaction atomique — rollback complet si une erreur survient.`)) return;
            this.executing = true;
            const items = this.results
                .filter(r => r.peut_reinscrire && (r.decision_override || r.decision) !== 'inconnu')
                .map(r => ({
                    etudiant_id: r.etudiant_id,
                    decision: r.decision_override || r.decision,
                    // classe_id auto-déterminée backend selon décision + classe origine
                }));
            try {
                const res = await fetch('/esbtp/reinscription/api/bulk-execute', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ items, decision_context: this.decisionContext }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    const msg = data?.message || 'Échec batch';
                    const errs = (data?.errors || []).map(e => e.message).join(' · ');
                    window.klassciToast?.('error', msg + (errs ? ' — ' + errs : ''));
                } else {
                    window.klassciToast?.('success',
                        `<strong>${data.success_count}</strong> réinscription(s) effectuée(s). Notifications email queuées.`);
                    setTimeout(() => window.location.reload(), 1500);
                }
            } catch (err) {
                window.klassciToast?.('error', err.message || 'Erreur batch');
            } finally {
                this.executing = false;
            }
        },
    };
};
</script>
@endpush
@endonce

@push('scripts')
<script>
window.{{ $alpineFactory }} = function() {
    return window.__brmSharedFactory(
        @json($modalId),
        @json($brmStudentsData),
        @json($decisionContext)
    );
};
</script>
@endpush

@once
@push('styles')
<style>
.brm-modal { border-radius: 16px; overflow: hidden; border: 1px solid rgba(4,83,203,.12); }
.brm-modal-header {
    background: linear-gradient(135deg, #0453cb 0%, #3b7ddb 100%);
    color: #fff; padding: 1rem 1.25rem;
    display: flex; align-items: center; gap: .85rem; border-bottom: none;
}
.brm-header-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(255,255,255,.16);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.brm-header-text { flex: 1; min-width: 0; }
.brm-header-text .modal-title { color: #fff; font-size: 1.05rem; font-weight: 700; margin: 0; }
.brm-header-sub { color: rgba(255,255,255,.85); font-size: .76rem; margin: .15rem 0 0; }
.brm-close-btn {
    background: rgba(255,255,255,.16); color: #fff;
    border: 1px solid rgba(255,255,255,.22); border-radius: 8px;
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .15s ease;
}
.brm-close-btn:hover { background: rgba(255,255,255,.28); }
.brm-modal-body { padding: 1rem 1.25rem; background: #f8fafc; max-height: 70vh; overflow-y: auto; }

.brm-section-bar {
    display: flex; align-items: center; gap: .55rem;
    padding: .55rem .8rem;
    background: rgba(4,83,203,.06); border-left: 3px solid #0453cb;
    border-radius: 6px; font-size: .82rem; color: #1e293b;
    margin-bottom: .85rem;
}
.brm-section-bar i { color: #0453cb; }

.brm-select-actions { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; margin-bottom: .75rem; }
.brm-search { flex: 1; min-width: 200px; padding: .4rem .75rem; border: 1px solid #cbd5e1; border-radius: 7px; font-size: .82rem; }
.brm-counter { font-size: .78rem; color: #475569; padding: .3rem .6rem; background: #fff; border-radius: 6px; border: 1px solid #e2e8f0; }

.brm-students-list { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; max-height: 320px; overflow-y: auto; }
.brm-student-row { display: flex; align-items: center; gap: .65rem; padding: .55rem .85rem; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background .15s; margin: 0; }
.brm-student-row:last-child { border-bottom: none; }
.brm-student-row:hover { background: #f8fafc; }
.brm-student-row--selected { background: rgba(4,83,203,.06); }
.brm-checkbox { width: 18px; height: 18px; cursor: pointer; flex-shrink: 0; accent-color: #0453cb; }
.brm-student-info { flex: 1; min-width: 0; }
.brm-student-name { font-weight: 600; font-size: .85rem; color: #0f172a; }
.brm-student-meta { display: flex; gap: .35rem; flex-wrap: wrap; margin-top: .15rem; }
.brm-meta-chip { font-size: .68rem; padding: .15rem .45rem; background: rgba(4,83,203,.08); color: #0453cb; border-radius: 4px; font-weight: 600; }
.brm-meta-chip--muted { background: #f1f5f9; color: #64748b; }
.brm-meta-chip--warn { background: rgba(245,158,11,.12); color: #92400e; }

.brm-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
.brm-empty i { font-size: 2rem; margin-bottom: .5rem; }
.brm-empty p { margin: 0; font-size: .85rem; font-weight: 600; }
.brm-empty small { font-size: .72rem; color: #94a3b8; }

.brm-loading { text-align: center; padding: 3rem 1rem; }
.brm-loading-spinner { font-size: 2.5rem; color: #0453cb; margin-bottom: .85rem; }
.brm-loading p { font-size: .9rem; color: #475569; margin: 0; }

.brm-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: .55rem; margin-bottom: .85rem; }
.brm-stats-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: .65rem .85rem; }
.brm-stats-card--ok { border-color: rgba(16,185,129,.3); background: linear-gradient(180deg, rgba(16,185,129,.04), #fff); }
.brm-stats-card--warn { border-color: rgba(245,158,11,.3); background: linear-gradient(180deg, rgba(245,158,11,.04), #fff); }
.brm-stats-card--total { border-color: rgba(4,83,203,.3); background: linear-gradient(180deg, rgba(4,83,203,.05), #fff); }
.brm-stats-label { font-size: .68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
.brm-stats-value { font-size: 1.4rem; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }

.brm-decisions-row { display: flex; gap: .45rem; flex-wrap: wrap; margin-bottom: .75rem; }
.brm-decision-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .65rem; border-radius: 999px; font-size: .76rem; font-weight: 700; }
.brm-decision-chip--passage { background: rgba(16,185,129,.12); color: #047857; }
.brm-decision-chip--rattrapage { background: rgba(245,158,11,.12); color: #b45309; }
.brm-decision-chip--redoublement { background: rgba(220,38,38,.1); color: #b91c1c; }
.brm-decision-chip--inconnu { background: rgba(148,163,184,.15); color: #475569; }

.brm-results-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: .65rem; }
.brm-result-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: .75rem .85rem; transition: border-color .15s; }
.brm-result-card--ok { border-color: rgba(16,185,129,.4); }
.brm-result-card--blocked { border-color: rgba(245,158,11,.4); background: rgba(245,158,11,.02); }
.brm-result-card--error { border-color: rgba(220,38,38,.4); background: rgba(220,38,38,.03); }
.brm-result-head { display: flex; justify-content: space-between; align-items: center; gap: .5rem; margin-bottom: .25rem; }
.brm-result-name { font-weight: 700; font-size: .85rem; color: #0f172a; }
.brm-result-badge { font-size: .68rem; padding: .15rem .5rem; border-radius: 999px; font-weight: 700; }
.brm-result-badge--ok { background: rgba(16,185,129,.15); color: #047857; }
.brm-result-badge--blocked { background: rgba(245,158,11,.15); color: #b45309; }
.brm-result-meta { font-size: .72rem; color: #64748b; margin-bottom: .55rem; }
.brm-result-stats { display: grid; grid-template-columns: 1fr 1.2fr 1fr; gap: .35rem; }
.brm-stat { background: #f8fafc; border-radius: 6px; padding: .35rem .5rem; }
.brm-stat-label { font-size: .62rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
.brm-stat-value { font-size: .82rem; font-weight: 700; color: #0f172a; }
.brm-stat-value--small { font-size: .72rem; }
.brm-stat-value--ok { color: #047857; }
.brm-stat-value--warn { color: #b45309; }
.brm-stat-select { width: 100%; padding: .15rem .3rem; font-size: .72rem; font-weight: 600; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff; color: #0f172a; }
.brm-result-flags { margin-top: .4rem; display: flex; gap: .3rem; flex-wrap: wrap; }
.brm-flag { display: inline-flex; align-items: center; gap: .3rem; font-size: .68rem; padding: .15rem .45rem; border-radius: 4px; font-weight: 600; }
.brm-flag--warn { background: rgba(245,158,11,.12); color: #92400e; }

.brm-modal-footer { padding: .85rem 1.25rem; border-top: 1px solid #e2e8f0; background: #fff; }
.brm-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1rem; border-radius: 8px; font-size: .82rem; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all .15s; }
.brm-btn--ghost { background: #fff; color: #475569; border-color: #e2e8f0; }
.brm-btn--ghost:hover { background: #f8fafc; color: #0f172a; }
.brm-btn--primary { background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; box-shadow: 0 4px 12px rgba(4,83,203,.2); }
.brm-btn--primary:hover { box-shadow: 0 8px 20px rgba(4,83,203,.3); }
.brm-btn--primary:disabled { opacity: .55; cursor: not-allowed; }
.brm-btn--success { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 4px 12px rgba(16,185,129,.25); }
.brm-btn--success:hover { box-shadow: 0 8px 20px rgba(16,185,129,.35); }
.brm-btn--success:disabled { opacity: .55; cursor: not-allowed; }
.brm-btn--sm { padding: .35rem .75rem; font-size: .75rem; }
</style>
@endpush
@endonce
