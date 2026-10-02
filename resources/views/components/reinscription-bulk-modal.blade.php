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
    // Adresse JSON des étudiants éligibles. Renseignée, la liste n'est pas
    // sérialisée dans la page : elle se charge à la première ouverture du modal
    // (réponse { students: [...] }, mêmes lignes que lignesPourModale()).
    'studentsUrl' => null,
])

@php
    $students = $students ?? collect();
    $brmStudentsData = $studentsUrl ? [] : \App\Services\Reinscription\BulkReinscriptionService::lignesPourModale($students);

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
            @if(! $studentsUrl && $students->isEmpty()) disabled title="Aucun étudiant éligible" @endif>
        <i class="fas fa-layer-group"></i>{{ $triggerLabel }}
    </button>
@endif

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true"
     x-data="{{ $alpineFactory }}()">
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
                        <div x-show="studentsLoading" x-cloak class="brm-empty" role="status">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Chargement des étudiants éligibles…</p>
                        </div>
                        <div x-show="studentsError && !studentsLoading" x-cloak class="brm-empty" role="alert">
                            <i class="fas fa-triangle-exclamation"></i>
                            <p x-text="studentsError"></p>
                            <button type="button" class="brm-btn brm-btn--ghost" @click="loadStudents(true)">
                                <i class="fas fa-rotate"></i> Réessayer
                            </button>
                        </div>
                        <div x-show="visibleStudents.length === 0 && !studentsLoading && !studentsError" x-cloak class="brm-empty">
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
                                        <div class="brm-dd" x-data="brmDropdown()" @click.outside="open = false">
                                            <button type="button" class="brm-dd-trigger"
                                                    @click="open = !open"
                                                    :class="{ 'brm-dd-trigger--open': open }">
                                                <span x-text="r.decision_override
                                                    ? (r.decision_override === 'passage' ? 'Passage' : (r.decision_override === 'rattrapage' ? 'Rattrapage' : 'Redoublement'))
                                                    : ('Auto (' + (r.decision || '—') + ')')"></span>
                                                <i class="fas fa-chevron-down brm-dd-caret" :class="{ 'brm-dd-caret--open': open }"></i>
                                            </button>
                                            <div class="brm-dd-menu" x-show="open" x-cloak x-transition.opacity>
                                                <button type="button" class="brm-dd-item"
                                                        @click="r.decision_override = ''; open = false; recomputeStats()">
                                                    Auto (<span x-text="r.decision || '—'"></span>)
                                                </button>
                                                <button type="button" class="brm-dd-item"
                                                        @click="r.decision_override = 'passage'; open = false; recomputeStats()">
                                                    <i class="fas fa-arrow-up"></i> Passage
                                                </button>
                                                <button type="button" class="brm-dd-item"
                                                        @click="r.decision_override = 'rattrapage'; open = false; recomputeStats()">
                                                    <i class="fas fa-rotate"></i> Rattrapage
                                                </button>
                                                <button type="button" class="brm-dd-item"
                                                        @click="r.decision_override = 'redoublement'; open = false; recomputeStats()">
                                                    <i class="fas fa-arrows-rotate"></i> Redoublement
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="brm-stat">
                                        <div class="brm-stat-label">Solde</div>
                                        <div class="brm-stat-value brm-stat-value--small"
                                             :class="(r.solde_restant !== null ? r.solde_restant <= 0 : r.peut_reinscrire) ? 'brm-stat-value--ok' : 'brm-stat-value--warn'"
                                             x-text="r.solde_restant !== null ? (r.solde_restant <= 0 ? 'Soldé ✓' : Math.round(r.solde_restant).toLocaleString('fr-FR') + ' FCFA') : (r.peut_reinscrire ? 'Soldé ✓' : 'À régler')"></div>
                                    </div>
                                </div>

                                {{-- Configuration par étudiant : classe cible + affectation + observations --}}
                                <div class="brm-result-config">
                                    <div class="brm-config-row">
                                        <label class="brm-config-label">Classe cible</label>
                                        <div class="brm-dd" x-data="brmDropdown()" @click.outside="open = false">
                                            <button type="button" class="brm-dd-trigger"
                                                    @click="open = !open"
                                                    :class="{ 'brm-dd-trigger--open': open }">
                                                <span x-text="(() => {
                                                    if (!r.target_classe_id) return '— Aucune —';
                                                    const suggested = (r.suggested_classes || []).find(c => c.id === r.target_classe_id);
                                                    if (suggested) return suggested.name + ' (' + (suggested.filiere || '—') + ' · ' + (suggested.niveau || '—') + ')';
                                                    const all = (allClasses || []).find(c => c.id === r.target_classe_id);
                                                    return all ? all.name : ('Classe #' + r.target_classe_id);
                                                })()"></span>
                                                <i class="fas fa-chevron-down brm-dd-caret" :class="{ 'brm-dd-caret--open': open }"></i>
                                            </button>
                                            <div class="brm-dd-menu brm-dd-menu--scroll" x-show="open" x-cloak x-transition.opacity>
                                                <template x-if="r.suggested_classes && r.suggested_classes.length > 0">
                                                    <div>
                                                        <div class="brm-dd-group">Suggestions auto</div>
                                                        <template x-for="c in r.suggested_classes" :key="'sug-' + c.id">
                                                            <button type="button" class="brm-dd-item"
                                                                    :class="{ 'brm-dd-item--active': r.target_classe_id === c.id }"
                                                                    @click="r.target_classe_id = c.id; open = false">
                                                                <span x-text="c.name + ' (' + (c.filiere || '—') + ' · ' + (c.niveau || '—') + ')'"></span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </template>
                                                <template x-if="allClasses && allClasses.length > 0">
                                                    <div>
                                                        <div class="brm-dd-group">Toutes les classes</div>
                                                        <template x-for="c in allClasses" :key="'all-' + c.id">
                                                            <button type="button" class="brm-dd-item"
                                                                    :class="{ 'brm-dd-item--active': r.target_classe_id === c.id }"
                                                                    @click="r.target_classe_id = c.id; open = false">
                                                                <span x-text="c.name"></span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="brm-config-row">
                                        <label class="brm-config-label">Affectation</label>
                                        <div class="brm-dd" x-data="brmDropdown()" @click.outside="open = false">
                                            <button type="button" class="brm-dd-trigger"
                                                    @click="open = !open"
                                                    :class="{ 'brm-dd-trigger--open': open }">
                                                <span x-text="r.affectation_status === 'non-affecté' ? 'Non-affecté' : (r.affectation_status === 'réaffecté' ? 'Réaffecté' : 'Affecté')"></span>
                                                <i class="fas fa-chevron-down brm-dd-caret" :class="{ 'brm-dd-caret--open': open }"></i>
                                            </button>
                                            <div class="brm-dd-menu" x-show="open" x-cloak x-transition.opacity>
                                                <button type="button" class="brm-dd-item"
                                                        :class="{ 'brm-dd-item--active': r.affectation_status === 'affecté' }"
                                                        @click="r.affectation_status = 'affecté'; open = false">Affecté</button>
                                                <button type="button" class="brm-dd-item"
                                                        :class="{ 'brm-dd-item--active': r.affectation_status === 'non-affecté' }"
                                                        @click="r.affectation_status = 'non-affecté'; open = false">Non-affecté</button>
                                                <button type="button" class="brm-dd-item"
                                                        :class="{ 'brm-dd-item--active': r.affectation_status === 'réaffecté' }"
                                                        @click="r.affectation_status = 'réaffecté'; open = false">Réaffecté</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="brm-config-row brm-config-row--wide">
                                        <label class="brm-config-label">Observations</label>
                                        <input type="text" class="brm-config-input" x-model="r.observations" placeholder="Note interne (optionnel)">
                                    </div>
                                </div>

                                <div class="brm-result-flags" x-show="!r.fiche_complete">
                                    <span class="brm-flag brm-flag--warn">
                                        <i class="fas fa-id-card"></i> Fiche incomplète — utilise <a :href="'/esbtp/reinscription/' + r.etudiant_id + '/finaliser'" target="_blank" class="brm-flag-link">Quick-Fiche</a> pour compléter
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
                        @click.prevent="requestExecuteBulk()"
                        :disabled="stats.eligible === 0 || executing"
                        x-show="step === 'results'" x-cloak>
                    <i class="fas fa-check-double"></i>
                    <span x-show="!executing">Réinscrire <span x-text="stats.eligible"></span> étudiant(s)</span>
                    <span x-show="executing" x-cloak><i class="fas fa-spinner fa-spin"></i> Traitement…</span>
                </button>
            </div>

            {{-- Dialog confirmation premium (remplace window.confirm() natif disruptif) --}}
            <div class="brm-confirm-overlay" x-show="showConfirmDialog" x-cloak x-transition.opacity
                 @keydown.escape.window="cancelConfirm()">
                <div class="brm-confirm-dialog" @click.outside="cancelConfirm()">
                    <div class="brm-confirm-icon"><i class="fas fa-circle-question"></i></div>
                    <h6 class="brm-confirm-title">Confirmer la réinscription</h6>
                    <p class="brm-confirm-text">
                        Vous allez réinscrire <strong x-text="stats.eligible"></strong> étudiant(s).
                        Chaque réinscription est traitée individuellement — un échec sur un étudiant
                        n'annule pas les autres. Continuer ?
                    </p>
                    <div class="brm-confirm-actions">
                        <button type="button" class="brm-btn brm-btn--ghost" @click="cancelConfirm()">
                            Annuler
                        </button>
                        <button type="button" class="brm-btn brm-btn--success" @click="executeBulk()">
                            <i class="fas fa-check-double"></i> Confirmer
                        </button>
                    </div>
                </div>
            </div>

            {{-- Panneau résultats post-exécution (visible si executionResults non null) --}}
            <div class="brm-exec-results" x-show="executionResults && executionResults.error_count > 0" x-cloak>
                <div class="brm-exec-section-bar brm-exec-section-bar--warn">
                    <i class="fas fa-triangle-exclamation"></i>
                    <span><strong>Résultat :</strong>
                        <span x-text="executionResults?.success_count ?? 0"></span> succès,
                        <span x-text="executionResults?.error_count ?? 0"></span> échec(s)
                    </span>
                </div>
                <div class="brm-exec-errors-list">
                    <template x-for="err in (executionResults?.error_list || [])" :key="'exec-err-' + err.etudiant_id">
                        <div class="brm-exec-error-row">
                            <div class="brm-exec-error-icon"><i class="fas fa-circle-xmark"></i></div>
                            <div class="brm-exec-error-body">
                                <div class="brm-exec-error-name">
                                    <span x-text="err.matricule || ('#' + err.etudiant_id)"></span>
                                    <span x-show="err.nom_complet" x-text="' — ' + err.nom_complet"></span>
                                </div>
                                <div class="brm-exec-error-msg" x-text="err.message"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script src="{{ asset('js/reinscription-bulk-modal.js') }}?v={{ @filemtime(public_path('js/reinscription-bulk-modal.js')) ?: '1' }}"></script>
@endpush
@endonce

@push('scripts')
<script>
window.{{ $alpineFactory }} = function() {
    return window.__brmSharedFactory(
        @json($modalId),
        @json($brmStudentsData),
        @json($decisionContext),
        @json($studentsUrl)
    );
};
</script>
@endpush

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('css/reinscription-bulk-modal.css') }}?v={{ @filemtime(public_path('css/reinscription-bulk-modal.css')) ?: '1' }}">
@endpush
@endonce
