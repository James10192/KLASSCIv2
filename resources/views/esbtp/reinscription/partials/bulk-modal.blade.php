{{-- Modal Réinscription Groupée — namespace brm-* (Bulk Reinscription Modal).
     AJAX no-reload : sélection étudiants → fetch /api/bulk-summary → affiche par row
     {moyenne, decision, frais_soldes, solde_restant}. Aucune mutation, juste diagnostic. --}}
@include('partials._klassci_toast')
<div class="modal fade" id="bulkReinscriptionModal" tabindex="-1" aria-labelledby="bulkReinscriptionModalLabel" aria-hidden="true"
     x-data="bulkReinscriptionModal()" x-init="init()">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content brm-modal">
            <div class="modal-header brm-modal-header">
                <div class="brm-header-icon"><i class="fas fa-layer-group"></i></div>
                <div class="brm-header-text">
                    <h5 class="modal-title" id="bulkReinscriptionModalLabel">Réinscription groupée</h5>
                    <p class="brm-header-sub">Diagnostic automatique de la situation académique et financière par étudiant</p>
                </div>
                <button type="button" class="brm-close-btn" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body brm-modal-body">
                {{-- ÉTAPE 1 : Sélection des étudiants --}}
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
                        <input type="search"
                               class="brm-search"
                               placeholder="Rechercher par matricule ou nom..."
                               x-model="searchQuery">
                        <div class="brm-counter">
                            <strong x-text="selectedIds.length"></strong> / <span x-text="visibleStudents.length"></span> sélectionné(s)
                        </div>
                    </div>

                    <div class="brm-students-list">
                        <template x-for="student in visibleStudents" :key="student.id">
                            <label class="brm-student-row" :class="selectedIds.includes(student.id) ? 'brm-student-row--selected' : ''">
                                <input type="checkbox"
                                       :value="student.id"
                                       :checked="selectedIds.includes(student.id)"
                                       @change="toggleSelect(student.id)"
                                       class="brm-checkbox">
                                <div class="brm-student-info">
                                    <div class="brm-student-name" x-text="student.nom_complet"></div>
                                    <div class="brm-student-meta">
                                        <span class="brm-meta-chip" x-text="student.matricule"></span>
                                        <span class="brm-meta-chip brm-meta-chip--muted" x-text="student.classe || 'Sans classe'"></span>
                                    </div>
                                </div>
                            </label>
                        </template>
                        <div x-show="visibleStudents.length === 0" x-cloak class="brm-empty">
                            <i class="fas fa-inbox"></i>
                            <p>Aucun étudiant ne correspond à la recherche.</p>
                        </div>
                    </div>
                </div>

                {{-- ÉTAPE 2 : Loading --}}
                <div x-show="step === 'loading'" x-cloak class="brm-loading">
                    <div class="brm-loading-spinner"><i class="fas fa-spinner fa-spin"></i></div>
                    <p>Analyse de <strong x-text="selectedIds.length"></strong> étudiant(s) en cours...</p>
                </div>

                {{-- ÉTAPE 3 : Résultats --}}
                <div x-show="step === 'results'" x-cloak>
                    <div class="brm-section-bar">
                        <i class="fas fa-clipboard-check"></i>
                        <span><strong>Étape 2 :</strong> diagnostic — <strong x-text="results.filter(r => r.peut_reinscrire).length"></strong> éligible(s) sur <strong x-text="results.length"></strong></span>
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
                                <div class="brm-result-meta" x-text="(r.matricule || '') + ' · ' + (r.classe || '—')"></div>

                                <div class="brm-result-stats">
                                    <div class="brm-stat">
                                        <div class="brm-stat-label">Moyenne</div>
                                        <div class="brm-stat-value"
                                             :class="r.moyenne !== null && r.moyenne >= 10 ? 'brm-stat-value--ok' : 'brm-stat-value--warn'"
                                             x-text="r.moyenne !== null ? r.moyenne.toFixed(2) + '/20' : '—'"></div>
                                    </div>
                                    <div class="brm-stat">
                                        <div class="brm-stat-label">Décision</div>
                                        <div class="brm-stat-value brm-stat-value--small" x-text="r.decision_label || '—'"></div>
                                    </div>
                                    <div class="brm-stat">
                                        <div class="brm-stat-label">Frais</div>
                                        <div class="brm-stat-value brm-stat-value--small"
                                             :class="r.frais_soldes ? 'brm-stat-value--ok' : 'brm-stat-value--warn'"
                                             x-text="r.frais_soldes ? 'Soldés ✓' : ('Solde ' + Math.round(r.solde_restant).toLocaleString('fr-FR') + ' FCFA')"></div>
                                    </div>
                                </div>

                                <div class="brm-result-actions">
                                    <a :href="'{{ route('esbtp.reinscription.show', ['etudiant' => 0]) }}'.replace('/0', '/' + r.etudiant_id)"
                                       class="brm-btn brm-btn--primary brm-btn--sm"
                                       target="_blank">
                                        <i class="fas fa-arrow-right"></i>
                                        Détail
                                    </a>
                                </div>
                            </div>
                        </template>

                        <template x-for="r in errors" :key="'err-' + r.etudiant_id">
                            <div class="brm-result-card brm-result-card--error">
                                <div class="brm-result-head">
                                    <div class="brm-result-name">Étudiant #<span x-text="r.etudiant_id"></span></div>
                                    <span class="brm-result-badge brm-result-badge--blocked">Erreur</span>
                                </div>
                                <div class="brm-result-meta" x-text="r.message"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="modal-footer brm-modal-footer">
                <button type="button" class="brm-btn brm-btn--ghost"
                        @click="step === 'results' ? backToSelect() : null"
                        data-bs-dismiss="modal"
                        x-show="step === 'select'" x-cloak>
                    Annuler
                </button>
                <button type="button" class="brm-btn brm-btn--ghost"
                        @click="backToSelect()"
                        x-show="step === 'results'" x-cloak>
                    <i class="fas fa-arrow-left"></i>
                    Modifier la sélection
                </button>
                <button type="button" class="brm-btn brm-btn--primary"
                        @click.prevent="analyze()"
                        :disabled="selectedIds.length === 0 || loading"
                        x-show="step === 'select'" x-cloak>
                    <i class="fas fa-magnifying-glass-chart"></i>
                    Analyser <span x-text="selectedIds.length"></span> étudiant(s)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function bulkReinscriptionModal() {
    return {
        students: @json($students->map(fn($e) => [
            'id'          => $e->id,
            'matricule'   => $e->matricule,
            'nom_complet' => $e->nom_complet ?? trim(($e->nom ?? '') . ' ' . ($e->prenoms ?? '')),
            'classe'      => optional($e->inscriptions->first())->classe?->name,
        ])->values()),
        selectedIds: [],
        searchQuery: '',
        step: 'select', // 'select' | 'loading' | 'results'
        loading: false,
        results: [],
        errors: [],

        init() {
            const modalEl = document.getElementById('bulkReinscriptionModal');
            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', () => {
                    this.backToSelect();
                });
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
            if (this.selectedIds.includes(id)) {
                this.selectedIds = this.selectedIds.filter(x => x !== id);
            } else {
                this.selectedIds.push(id);
            }
        },

        selectAll() {
            if (this.allSelected) {
                this.selectedIds = this.selectedIds.filter(id =>
                    !this.visibleStudents.some(s => s.id === id)
                );
            } else {
                const newIds = this.visibleStudents.map(s => s.id);
                this.selectedIds = [...new Set([...this.selectedIds, ...newIds])];
            }
        },

        backToSelect() {
            this.step = 'select';
            this.results = [];
            this.errors = [];
            this.loading = false;
        },

        async analyze() {
            if (this.selectedIds.length === 0 || this.loading) return;
            if (this.selectedIds.length > 100) {
                window.klassciToast('warning', 'Limite : 100 étudiants par analyse. Réduis la sélection.');
                return;
            }
            this.loading = true;
            this.step = 'loading';
            try {
                const res = await fetch('{{ route('esbtp.reinscription.api.bulk-summary') }}', {
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
                this.results = (data.results || []).filter(r => r.status === 'ok');
                this.errors = (data.results || []).filter(r => r.status === 'error');
                this.step = 'results';
                const okCount = this.results.filter(r => r.peut_reinscrire).length;
                window.klassciToast(
                    okCount > 0 ? 'success' : 'info',
                    `<strong>${this.results.length}</strong> étudiant(s) analysé(s)<br><strong>${okCount}</strong> éligible(s) à la réinscription`
                );
            } catch (err) {
                window.klassciToast('error', err.message || 'Erreur lors de l\'analyse');
                this.step = 'select';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>

<style>
.brm-modal { border-radius: 16px; overflow: hidden; border: 1px solid rgba(4,83,203,.12); }
.brm-modal-header {
    background: linear-gradient(135deg, #0453cb 0%, #3b7ddb 100%);
    color: #fff;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: .85rem;
    border-bottom: none;
}
.brm-header-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: rgba(255,255,255,.16);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.brm-header-text { flex: 1; min-width: 0; }
.brm-header-text .modal-title { color: #fff; font-size: 1.05rem; font-weight: 700; margin: 0; }
.brm-header-sub { color: rgba(255,255,255,.78); font-size: .76rem; margin: .15rem 0 0; }
.brm-close-btn {
    background: rgba(255,255,255,.16);
    color: #fff;
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 8px;
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all .15s ease;
}
.brm-close-btn:hover { background: rgba(255,255,255,.28); }

.brm-modal-body { padding: 1rem 1.25rem; background: #f8fafc; max-height: 70vh; overflow-y: auto; }

.brm-section-bar {
    display: flex; align-items: center; gap: .55rem;
    padding: .55rem .8rem;
    background: rgba(4,83,203,.06);
    border-left: 3px solid #0453cb;
    border-radius: 6px;
    font-size: .82rem;
    color: #1e293b;
    margin-bottom: .85rem;
}
.brm-section-bar i { color: #0453cb; }

.brm-select-actions {
    display: flex;
    align-items: center;
    gap: .55rem;
    flex-wrap: wrap;
    margin-bottom: .75rem;
}
.brm-search {
    flex: 1; min-width: 200px;
    padding: .4rem .75rem;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: .82rem;
    background: #fff;
}
.brm-search:focus { outline: 2px solid rgba(4,83,203,.4); outline-offset: 1px; border-color: #0453cb; }
.brm-counter {
    font-size: .78rem;
    color: #475569;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    padding: .4rem .75rem;
}

.brm-students-list {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    max-height: 380px;
    overflow-y: auto;
}
.brm-student-row {
    display: flex; align-items: center; gap: .65rem;
    padding: .55rem .85rem;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background .12s ease;
}
.brm-student-row:last-child { border-bottom: none; }
.brm-student-row:hover { background: #f8fafc; }
.brm-student-row--selected { background: rgba(4,83,203,.06); }
.brm-checkbox { width: 16px; height: 16px; accent-color: #0453cb; flex-shrink: 0; }
.brm-student-info { flex: 1; min-width: 0; }
.brm-student-name { font-size: .85rem; font-weight: 600; color: #1e293b; line-height: 1.2; }
.brm-student-meta { display: flex; gap: .35rem; margin-top: .2rem; flex-wrap: wrap; }
.brm-meta-chip {
    font-size: .68rem; font-weight: 600;
    padding: .12rem .5rem;
    border-radius: 999px;
    background: rgba(4,83,203,.1);
    color: #0453cb;
}
.brm-meta-chip--muted { background: #f1f5f9; color: #64748b; }

.brm-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: #94a3b8;
}
.brm-empty i { font-size: 1.5rem; margin-bottom: .5rem; display: block; }
.brm-empty p { margin: 0; font-size: .82rem; }

.brm-loading {
    padding: 3rem 1rem;
    text-align: center;
    color: #475569;
}
.brm-loading-spinner { font-size: 2rem; color: #0453cb; margin-bottom: 1rem; }
.brm-loading p { margin: 0; font-size: .9rem; }

.brm-results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: .75rem;
}
.brm-result-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
    padding: .75rem .85rem;
    transition: all .15s ease;
}
.brm-result-card--ok { border-left: 3px solid #10b981; }
.brm-result-card--blocked { border-left: 3px solid #f59e0b; opacity: .92; }
.brm-result-card--error { border-left: 3px solid #dc2626; background: rgba(220,38,38,.04); }
.brm-result-card:hover { box-shadow: 0 4px 16px rgba(15,23,42,.06); }

.brm-result-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .15rem; }
.brm-result-name { font-size: .9rem; font-weight: 700; color: #1e293b; line-height: 1.15; }
.brm-result-badge {
    font-size: .62rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .3px;
    padding: .18rem .55rem;
    border-radius: 999px;
    white-space: nowrap;
}
.brm-result-badge--ok { background: rgba(16,185,129,.18); color: #047857; }
.brm-result-badge--blocked { background: rgba(245,158,11,.22); color: #92400e; }
.brm-result-meta { font-size: .72rem; color: #64748b; margin-bottom: .55rem; }

.brm-result-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .35rem;
    margin-bottom: .55rem;
    padding: .45rem .55rem;
    background: #f8fafc;
    border-radius: 7px;
}
.brm-stat { text-align: center; min-width: 0; }
.brm-stat-label { font-size: .58rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; }
.brm-stat-value { font-size: .82rem; font-weight: 700; color: #1e293b; margin-top: .12rem; line-height: 1.15; overflow: hidden; text-overflow: ellipsis; }
.brm-stat-value--ok { color: #047857; }
.brm-stat-value--warn { color: #92400e; }
.brm-stat-value--small { font-size: .7rem; }

.brm-result-actions { display: flex; justify-content: flex-end; gap: .4rem; }

.brm-modal-footer {
    background: #fff;
    border-top: 1px solid #e2e8f0;
    padding: .75rem 1.25rem;
    display: flex;
    justify-content: flex-end;
    gap: .5rem;
}

.brm-btn {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .45rem 1rem;
    border-radius: 8px;
    font-size: .8rem; font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all .15s ease;
    white-space: nowrap;
    text-decoration: none;
}
.brm-btn--primary { background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; }
.brm-btn--primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(4,83,203,.28); color: #fff; }
.brm-btn--primary:disabled { background: #cbd5e1; cursor: not-allowed; transform: none; box-shadow: none; }
.brm-btn--ghost { background: #fff; color: #475569; border-color: #cbd5e1; }
.brm-btn--ghost:hover { background: #f1f5f9; color: #0453cb; border-color: #0453cb; }
.brm-btn--sm { padding: .3rem .75rem; font-size: .72rem; }

@media (max-width: 768px) {
    .brm-results-grid { grid-template-columns: 1fr; }
    .brm-result-stats { grid-template-columns: 1fr 1fr; }
}
</style>
