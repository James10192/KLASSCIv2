@extends('layouts.app')
@section('title', 'Pilotage académique')
@php
    $anneeOptions = $annees->mapWithKeys(fn ($annee) => [$annee->id => $annee->display_name])->all();
    $classeOptions = $classes->mapWithKeys(fn ($classe) => [$classe->id => trim(($classe->code ? $classe->code.' · ' : '').$classe->name)])->all();
@endphp
@include('esbtp.pilotage-academique._styles')
@section('content')
<div class="container-fluid cpa-shell"
     x-data="cpaDashboard({
        dataUrl: @js(route('esbtp.pilotage-academique.data')),
        syncUrl: @js(route('esbtp.pilotage-academique.synchronize')),
        alertTransitionUrl: @js(url('/esbtp/academic-alerts')),
        classUrl: @js(url('/esbtp/pilotage-academique/classes')),
        studentUrl: @js(url('/esbtp/pilotage-academique/etudiants')),
        sheetUrl: @js(url('/esbtp/academic-sheets')),
        assignmentUrl: @js(url('/esbtp/academic-assignments')),
        canManageAssignments: @js(auth()->user()->can('academic_sheets.assign')),
        initialFilters: @js($initialFilters),
     })"
     x-init="init()">
    <header class="cpa-hero">
        <div class="cpa-hero-top">
            <div class="cpa-hero-title">
                <div class="cpa-hero-icon"><i class="fas fa-chart-line"></i></div>
                <div>
                    <h1>Centre de pilotage académique</h1>
                    <p>Suivi des fiches, alertes, scores et blocages académiques BTS et LMD</p>
                </div>
            </div>
            <div class="cpa-hero-scope"><i class="fas fa-shield-halved"></i>Données académiques réelles</div>
        </div>
        <div class="cpa-hero-kpis">
            <div class="cpa-hero-kpi">
                <div class="cpa-hero-kpi-icon"><i class="fas fa-chart-simple"></i></div>
                <div>
                    <div class="cpa-hero-kpi-value" x-text="percent(data.summary?.academic_score)"></div>
                    <div class="cpa-hero-kpi-label">Santé académique</div>
                </div>
            </div>
            <div class="cpa-hero-kpi">
                <div class="cpa-hero-kpi-icon"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <div class="cpa-hero-kpi-value" x-text="percent(data.summary?.operational_score)"></div>
                    <div class="cpa-hero-kpi-label">Préparation opérationnelle</div>
                </div>
            </div>
            <div class="cpa-hero-kpi">
                <div class="cpa-hero-kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
                <div>
                    <div class="cpa-hero-kpi-value" x-text="data.summary?.open_alerts ?? 0"></div>
                    <div class="cpa-hero-kpi-label">Alertes ouvertes</div>
                </div>
            </div>
            <div class="cpa-hero-kpi">
                <div class="cpa-hero-kpi-icon"><i class="fas fa-list-check"></i></div>
                <div>
                    <div class="cpa-hero-kpi-value" x-text="data.summary?.sheets_pending ?? 0"></div>
                    <div class="cpa-hero-kpi-label">Fiches à suivre</div>
                </div>
            </div>
        </div>
    </header>
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
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'direction' }" @click="setTab('direction')"><i class="fas fa-gauge-high"></i>Direction</button>
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'activity' }" @click="setTab('activity')"><i class="fas fa-users-gear"></i>Acteurs</button>
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'sheets' }" @click="setTab('sheets')"><i class="fas fa-clipboard-check"></i>Notes et fiches</button>
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'alerts' }" @click="setTab('alerts')"><i class="fas fa-triangle-exclamation"></i>Alertes</button>
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'classes' }" @click="setTab('classes')"><i class="fas fa-school"></i>Santé classe</button>
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'students' }" @click="setTab('students')"><i class="fas fa-user-graduate"></i>Santé étudiant</button>
            <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'mine' }" @click="setTab('mine')"><i class="fas fa-user-check"></i>Mon suivi</button>
            @can('academic_sheets.assign')
                <button type="button" class="cpa-tab" :class="{ 'is-active': tab === 'assignments' }" @click="setTab('assignments')"><i class="fas fa-user-tag"></i>Affectations</button>
            @endcan
        </div>
    </section>
    <template x-if="error">
        <div class="cpa-state cpa-error"><i class="fas fa-circle-exclamation"></i><span x-text="error"></span></div>
    </template>
    <template x-if="data.prerequisites && !data.prerequisites.year_configured">
        <div class="cpa-state cpa-error"><i class="fas fa-calendar-xmark"></i><span x-text="data.prerequisites.message"></span></div>
    </template>
    {{-- Vue direction. Les quatre chiffres de synthese sont deja dans le hero :
         les repeter ici ne dirait rien de plus. Cet onglet repond a trois
         questions d'affilee : quelles classes decrochent, ou en sont les
         alertes, et ou bloque le circuit des fiches. --}}
    <section class="cpa-direction" :class="{ 'cpa-loading': loading }" x-show="tab === 'direction'">

        <div class="cpa-panel cpa-direction-focal">
            <div class="cpa-panel-head">
                <div>
                    <h2 class="cpa-panel-title"><i class="fas fa-school"></i>Quelles classes décrochent</h2>
                    <p class="cpa-muted mt-1">Score académique par classe, du plus faible au plus élevé. Cliquez une barre pour filtrer toute la page sur cette classe.</p>
                </div>
                <p class="cpa-muted" x-text="freshnessLabel()"></p>
            </div>

            <div class="cpa-chart-wrap" x-show="classesTriees().length > 0">
                <canvas x-ref="chartClasses" height="280"></canvas>
            </div>

            <div class="cpa-state" x-show="classesTriees().length === 0 && !loading">
                <i class="fas fa-hourglass-half"></i>
                <span>Aucun score calculé pour ce périmètre. Lancez « Synchroniser la vue » pour produire les premiers snapshots.</span>
            </div>

            <div class="cpa-chart-legende" x-show="classesTriees().length > 0">
                <span class="cpa-legende-item"><span class="cpa-legende-pastille" style="background:#dc2626"></span>Critique, sous 50 %</span>
                <span class="cpa-legende-item"><span class="cpa-legende-pastille" style="background:#f59e0b"></span>À surveiller, sous 70 %</span>
                <span class="cpa-legende-item"><span class="cpa-legende-pastille" style="background:#0453cb"></span>Satisfaisant</span>
                <span class="cpa-legende-item cpa-muted" x-show="classesSansScore() > 0">
                    <span x-text="classesSansScore()"></span>&nbsp;classe(s) sans score calculé, non représentée(s)
                </span>
                <span class="cpa-legende-item cpa-muted" x-show="data.classes && data.classes.length >= 12">
                    Les 12 classes calculées le plus récemment
                </span>
            </div>
        </div>

        <div class="cpa-direction-rail">
            <div class="cpa-panel">
                <div class="cpa-panel-head">
                    <h3 class="cpa-panel-title"><i class="fas fa-triangle-exclamation"></i>Alertes par gravité</h3>
                </div>
                <div class="cpa-chart-wrap cpa-chart-wrap--court" x-show="totalAlertes() > 0">
                    <canvas x-ref="chartAlertes" height="210"></canvas>
                </div>
                <div class="cpa-state" x-show="totalAlertes() === 0 && !loading">
                    <i class="fas fa-circle-check"></i><span>Aucune alerte active sur ce périmètre.</span>
                </div>
                <button type="button" class="cpa-lien-bloc" x-show="totalAlertes() > 0" @click="tab = 'alerts'">
                    Ouvrir la liste des alertes<i class="fas fa-arrow-right"></i>
                </button>
            </div>

            <div class="cpa-panel">
                <div class="cpa-panel-head">
                    <h3 class="cpa-panel-title"><i class="fas fa-clipboard-check"></i>Où bloque le circuit</h3>
                </div>
                <div class="cpa-chart-wrap cpa-chart-wrap--court" x-show="totalFiches() > 0">
                    <canvas x-ref="chartFiches" height="210"></canvas>
                </div>
                <div class="cpa-state" x-show="totalFiches() === 0 && !loading">
                    <i class="fas fa-circle-check"></i><span>Aucune fiche en cours : tout est validé ou annulé.</span>
                </div>
                <button type="button" class="cpa-lien-bloc" x-show="totalFiches() > 0" @click="tab = 'sheets'">
                    Ouvrir le suivi des fiches<i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </section>
    <section class="cpa-split" x-show="tab === 'sheets'">
        <div class="cpa-panel">
            <div class="cpa-panel-head">
                <div>
                    <h2 class="cpa-panel-title"><i class="fas fa-clipboard-check"></i>Suivi des fiches de notes</h2>
                    <p class="cpa-muted mt-1">Une fiche correspond &agrave; une &eacute;valuation, une mati&egrave;re et une classe. Elle suit la remise, la saisie, le contr&ocirc;le et la validation des r&eacute;sultats.</p>
                </div>
            </div>
            <section class="cpa-note-coverage">
                <template x-if="data.note_coverage?.message">
                    <div class="cpa-state cpa-note-coverage-empty">
                        <i class="fas fa-chart-simple"></i>
                        <span x-text="data.note_coverage.message"></span>
                    </div>
                </template>
                <template x-if="!data.note_coverage?.message">
                    <div class="cpa-note-cockpit">
                        <div class="cpa-note-command">
                            <div class="cpa-note-command-copy">
                                <span class="cpa-eyebrow"><i class="fas fa-table-list"></i> Couverture des notes de la classe</span>
                                <h3 x-text="noteCoverageStatusLabel()"></h3>
                                <p x-text="noteCoverageClassLabel()"></p>
                            </div>
                            <div class="cpa-note-command-score">
                                <span :class="noteCoverageStatusClass()" x-text="noteCoverageStatusLabel()"></span>
                                <strong x-text="noteCoverageCompletionPercent()"></strong>
                                <small>couverture traitée</small>
                            </div>
                        </div>

                        <div class="cpa-coverage-kpis" aria-label="Indicateurs de couverture des notes">
                            <template x-for="item in noteCoverageKpis()" :key="item.label">
                                <div class="cpa-coverage-kpi">
                                    <span x-text="item.label"></span>
                                    <strong x-text="item.value"></strong>
                                    <small x-text="item.help"></small>
                                </div>
                            </template>
                        </div>

                        <div class="cpa-note-workbench">
                            <section class="cpa-note-priorities">
                                <div class="cpa-note-section-head">
                                    <div>
                                        <h4>Priorités à traiter</h4>
                                        <p>Les évaluations qui bloquent la complétude des bulletins.</p>
                                    </div>
                                    <span x-text="`${noteCoveragePriorities().length} priorité(s)`"></span>
                                </div>

                                <div class="cpa-note-empty-compact" x-show="!noteCoveragePriorities().length">
                                    <i class="fas fa-circle-check"></i>
                                    <span>Aucun résultat manquant pour les évaluations suivies.</span>
                                </div>

                                <template x-for="subject in noteCoveragePriorities()" :key="`priority-${subject.id || subject.name}`">
                                    <article class="cpa-note-priority-card">
                                        <div class="cpa-note-priority-main">
                                            <div>
                                                <strong x-text="subject.name"></strong>
                                                <span>
                                                    <span x-text="subject.code || 'Sans code'"></span>
                                                    <span class="cpa-note-orphan" x-show="subject.is_orphan">Hors référentiel</span>
                                                </span>
                                            </div>
                                            <div class="cpa-note-priority-count">
                                                <strong x-text="subject.missing_count"></strong>
                                                <small>manquant(s)</small>
                                            </div>
                                        </div>

                                        <div class="cpa-note-priority-progress">
                                            <div>
                                                <span>Avancement</span>
                                                <strong x-text="`${subject.treated_count}/${subject.expected_count}`"></strong>
                                            </div>
                                            <div class="cpa-progress-track">
                                                <div class="cpa-progress-bar" :style="`width: ${noteCoverageRate(subject.treated_count, subject.expected_count)}%`"></div>
                                            </div>
                                        </div>

                                        <div class="cpa-note-blockers">
                                            <template x-for="evaluation in noteCoverageBlockingEvaluations(subject).slice(0, 3)" :key="`blocker-${subject.id || subject.name}-${evaluation.id}`">
                                                <div class="cpa-note-blocker-row">
                                                    <div>
                                                        <strong x-text="evaluation.title"></strong>
                                                        <span x-text="evaluation.type || evaluation.date || 'Évaluation'"></span>
                                                    </div>
                                                    <span x-text="`${evaluation.missing_count}/${evaluation.expected_count} à compléter`"></span>
                                                </div>
                                            </template>
                                        </div>

                                        <details class="cpa-note-details">
                                            <summary>Voir les étudiants concernés</summary>
                                            <div class="cpa-note-chip-list">
                                                <template x-for="student in (subject.missing_students || []).slice(0, 12)" :key="`priority-student-${subject.id || subject.name}-${student.id}`">
                                                    <span class="cpa-note-chip cpa-note-chip--warn" x-text="student.name"></span>
                                                </template>
                                                <span class="cpa-note-chip cpa-note-chip--muted" x-show="(subject.missing_students || []).length > 12" x-text="`+${(subject.missing_students || []).length - 12}`"></span>
                                            </div>
                                        </details>
                                    </article>
                                </template>
                            </section>

                            <section class="cpa-note-progress-panel">
                                <div class="cpa-note-section-head">
                                    <div>
                                        <h4>Avancement par matière</h4>
                                        <p>Vue de synthèse, puis détail par évaluation si nécessaire.</p>
                                    </div>
                                </div>

                                <div class="cpa-note-subjects" x-show="data.note_coverage && data.note_coverage.subjects?.length">
                                    <template x-for="subject in noteCoverageSubjects()" :key="`subject-${subject.id || subject.name}`">
                                        <details class="cpa-note-subject">
                                            <summary>
                                                <div class="cpa-note-subject-main">
                                                    <strong x-text="subject.name"></strong>
                                                    <span>
                                                        <span x-text="subject.code || 'Sans code'"></span>
                                                        <span class="cpa-note-orphan" x-show="subject.is_orphan">Hors référentiel</span>
                                                    </span>
                                                </div>
                                                <div class="cpa-note-subject-meter">
                                                    <div class="cpa-progress-track">
                                                        <div class="cpa-progress-bar" :class="{ 'is-complete': subject.missing_count === 0 && subject.expected_count > 0, 'is-warning': subject.missing_count > 0 }" :style="`width: ${noteCoverageRate(subject.treated_count, subject.expected_count)}%`"></div>
                                                    </div>
                                                    <span :class="{ 'is-danger': subject.missing_count > 0 }" x-text="noteCoverageSubjectLabel(subject)"></span>
                                                </div>
                                            </summary>
                                            <div class="cpa-note-subject-body">
                                                <div class="cpa-state cpa-note-empty-compact" x-show="!subject.evaluations?.length">
                                                    <i class="fas fa-clipboard-question"></i>Aucune évaluation non annulée pour cette matière.
                                                </div>
                                                <div class="cpa-note-evaluations" x-show="subject.evaluations?.length">
                                                    <template x-for="evaluation in subject.evaluations" :key="`evaluation-${evaluation.id}`">
                                                        <details class="cpa-note-evaluation">
                                                            <summary>
                                                                <div>
                                                                    <strong x-text="evaluation.title"></strong>
                                                                    <span x-text="evaluation.type || evaluation.date || 'Évaluation'"></span>
                                                                </div>
                                                                <div class="cpa-note-subject-stats">
                                                                    <span x-text="`${evaluation.treated_count}/${evaluation.expected_count}`"></span>
                                                                    <span :class="{ 'is-danger': evaluation.missing_count > 0 }" x-text="`${evaluation.missing_count} manquant(s)`"></span>
                                                                    <span x-text="actorListLabel(evaluation.actors)"></span>
                                                                </div>
                                                            </summary>
                                                            <div class="cpa-note-evaluation-body">
                                                                <div class="cpa-note-missing" x-show="evaluation.missing_students?.length">
                                                                    <div class="cpa-note-missing-title">Étudiants sans résultat traité</div>
                                                                    <div class="cpa-note-chip-list">
                                                                        <template x-for="student in evaluation.missing_students" :key="`missing-${evaluation.id}-${student.id}`">
                                                                            <span class="cpa-note-chip" x-text="student.name"></span>
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                                <div class="cpa-note-students">
                                                                    <template x-for="row in evaluation.students" :key="`student-${evaluation.id}-${row.student.id}`">
                                                                        <div class="cpa-note-student-row" :class="{ 'is-missing': row.status === 'missing' }">
                                                                            <span x-text="row.student.name"></span>
                                                                            <strong x-text="studentResultLabel(row)"></strong>
                                                                            <small x-text="studentActorLabel(row)"></small>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </details>
                                                    </template>
                                                </div>
                                            </div>
                                        </details>
                                    </template>
                                </div>

                                <details class="cpa-note-incomplete" x-show="data.note_coverage?.incomplete_students?.length">
                                    <summary>
                                        <span>&Eacute;tudiants incomplets</span>
                                        <strong x-text="`${data.note_coverage.incomplete_students.length} étudiant(s)`"></strong>
                                    </summary>
                                    <div class="cpa-note-chip-list">
                                        <template x-for="student in data.note_coverage.incomplete_students" :key="`student-incomplete-${student.id}`">
                                            <span class="cpa-note-chip cpa-note-chip--warn" x-text="studentMissingLabel(student)"></span>
                                        </template>
                                    </div>
                                </details>
                            </section>
                        </div>
                    </div>
                </template>
            </section>
            <div class="cpa-list" x-show="data.sheets?.length">
                <template x-for="sheet in data.sheets" :key="sheet.id">
                    <article class="cpa-row cpa-sheet-card">
                        <div class="cpa-row-main">
                            <div class="cpa-sheet-title-line">
                                <div>
                                    <div class="cpa-row-title" x-text="sheetTitle(sheet)"></div>
                                    <div class="cpa-sheet-reference"><span>R&eacute;f&eacute;rence fiche</span><code x-text="sheet.code"></code></div>
                                </div>
                                <div class="cpa-sheet-stage">
                                    <span class="cpa-badge" :class="badgeClass(sheet.status)" x-text="sheet.status_label"></span>
                                    <small x-text="statusProgressLabel(sheet)"></small>
                                </div>
                            </div>
                            <div class="cpa-row-meta">
                                <span x-text="sheet.classe || 'Classe non disponible'"></span>
                                <span x-text="entryModeLabel(sheet)"></span>
                                <span x-text="sheet.teacher ? `Enseignant : ${sheet.teacher}` : 'Enseignant non affecté'"></span>
                            </div>
                            <div class="cpa-sheet-progress" role="progressbar" :aria-valuenow="entryProgressPercent(sheet)" aria-valuemin="0" aria-valuemax="100">
                                <div class="cpa-sheet-progress-head">
                                    <span>R&eacute;sultats renseign&eacute;s</span>
                                    <strong x-text="entryProgress(sheet)"></strong>
                                </div>
                                <div class="cpa-progress-track"><div class="cpa-progress-bar" :style="`width: ${entryProgressPercent(sheet)}%`"></div></div>
                                <small>Une entr&eacute;e est renseign&eacute;e lorsqu&rsquo;elle contient une note, une absence, une dispense ou un statut non applicable.</small>
                            </div>
                            <div class="cpa-audit">
                                <div class="cpa-audit-item">
                                    <span class="cpa-audit-icon"><i class="fas fa-keyboard"></i></span>
                                    <span class="cpa-audit-label">Saisie effectu&eacute;e par</span>
                                    <span class="cpa-audit-value" x-text="entryActorsLabel(sheet)"></span>
                                </div>
                                <div class="cpa-audit-item">
                                    <span class="cpa-audit-icon"><i class="fas fa-inbox"></i></span>
                                    <span class="cpa-audit-label">R&eacute;ception de la fiche</span>
                                    <span class="cpa-audit-value" x-text="receptionLabel(sheet)"></span>
                                </div>
                                <div class="cpa-audit-item">
                                    <span class="cpa-audit-icon"><i class="fas fa-magnifying-glass-check"></i></span>
                                    <span class="cpa-audit-label">Contr&ocirc;le par</span>
                                    <span class="cpa-audit-value" x-text="actorLabel(sheet.controlled_by, null, 'Pas encore contrôlée')"></span>
                                </div>
                                <div class="cpa-audit-item">
                                    <span class="cpa-audit-icon"><i class="fas fa-circle-check"></i></span>
                                    <span class="cpa-audit-label">Validation par</span>
                                    <span class="cpa-audit-value" x-text="actorLabel(sheet.validated_by, null, 'Pas encore validée')"></span>
                                </div>
                                <div class="cpa-audit-item">
                                    <span class="cpa-audit-icon"><i class="fas fa-clock-rotate-left"></i></span>
                                    <span class="cpa-audit-label">Derni&egrave;re action</span>
                                    <span class="cpa-audit-value" x-text="latestAuditLabel(sheet)"></span>
                                </div>
                            </div>
                        </div>
                        <div class="cpa-row-actions">
                            <button type="button" class="cpa-btn cpa-btn--compact" @click="openSheet(sheet.id)" :disabled="sheetWorkspace.loading && sheetWorkspace.id === sheet.id">
                                <i class="fas" :class="sheetWorkspace.loading && sheetWorkspace.id === sheet.id ? 'fa-spinner fa-spin' : 'fa-eye'"></i>
                                <span>Voir le d&eacute;tail</span>
                            </button>
                            <template x-for="action in sheetActions(sheet)" :key="`${sheet.id}-${action.key}`">
                                <button type="button" class="cpa-btn cpa-btn--compact" :class="actionButtonClass(action)" @click="requestSheetTransition(action, sheet)" :disabled="transitioning">
                                    <i class="fas" :class="actionIcon(action)"></i>
                                    <span x-text="action.label"></span>
                                </button>
                            </template>
                        </div>
                    </article>
                </template>
            </div>
            <div class="cpa-state" x-show="!data.sheets?.length"><i class="fas fa-clipboard"></i>Aucune fiche sur ce périmètre.</div>
        </div>
        <div class="cpa-drawer cpa-sheet-drawer" :class="{ 'cpa-loading': sheetWorkspace.loading }">
            <div x-show="!sheetWorkspace.data && !sheetWorkspace.loading && !sheetWorkspace.error">
            <h3 class="cpa-panel-title"><i class="fas fa-circle-info"></i>Préparation bulletin</h3>
            <p class="cpa-muted">Les générations BTS et LMD sont bloquées lorsque les fiches ou les notes attendues sont incomplètes. Les overrides restent soumis à permission et motif audité.</p>
            </div>
            <template x-if="sheetWorkspace.error">
                <div class="cpa-state cpa-error"><i class="fas fa-circle-exclamation"></i><span x-text="sheetWorkspace.error"></span></div>
            </template>
            <template x-if="sheetWorkspace.success">
                <div class="cpa-state"><i class="fas fa-circle-check"></i><span x-text="sheetWorkspace.success"></span></div>
            </template>
            <template x-if="sheetWorkspace.data">
                <div>
                    <div class="cpa-sheet-head">
                        <div>
                            <h3 class="cpa-panel-title" x-text="sheetWorkspace.data.grade_sheet?.code || 'Fiche de notes'"></h3>
                            <p class="cpa-muted mt-1" x-text="sheetMeta(sheetWorkspace.data.grade_sheet)"></p>
                        </div>
                        <span class="cpa-badge" :class="badgeClass(sheetWorkspace.data.grade_sheet?.status)" x-text="sheetWorkspace.data.grade_sheet?.status_label || sheetWorkspace.data.grade_sheet?.status"></span>
                    </div>
                    <div class="cpa-row-actions mt-3">
                        <template x-for="action in sheetActions(sheetWorkspace.data.grade_sheet, sheetWorkspace.data.allowed_actions)" :key="`detail-${action.key}`">
                            <button type="button" class="cpa-btn cpa-btn--compact" :class="actionButtonClass(action)" @click="requestSheetTransition(action, sheetWorkspace.data.grade_sheet)" :disabled="transitioning">
                                <i class="fas" :class="actionIcon(action)"></i><span x-text="action.label"></span>
                            </button>
                        </template>
                    </div>
                    <section class="cpa-detail-section" x-show="sheetWorkspace.data.progress">
                        <h4>Avancement</h4>
                        <div class="cpa-progress-track"><div class="cpa-progress-bar" :style="`width: ${progressPercent(sheetWorkspace.data.progress)}%`"></div></div>
                        <p class="cpa-muted mt-2" x-text="progressLabel(sheetWorkspace.data.progress)"></p>
                    </section>
                    <section class="cpa-detail-section">
                        <h4>Notes saisies</h4>
                        <div class="cpa-detail-list" x-show="sheetWorkspace.data.entries?.length">
                            <template x-for="entry in sheetWorkspace.data.entries" :key="entry.id || entry.student_id || entry.name">
                                <div class="cpa-detail-item"><div class="cpa-detail-item-title" x-text="entryLabel(entry)"></div><div class="cpa-detail-item-meta" x-text="entryMeta(entry)"></div></div>
                            </template>
                        </div>
                        <p class="cpa-muted" x-show="!sheetWorkspace.data.entries?.length">Aucune note disponible pour cette fiche.</p>
                    </section>
                    <section class="cpa-detail-section">
                        <h4>Historique</h4>
                        <div class="cpa-detail-list" x-show="sheetWorkspace.data.events?.length">
                            <template x-for="event in sheetWorkspace.data.events" :key="event.id || `${event.action}-${event.occurred_at}`">
                                <div class="cpa-detail-item"><div class="cpa-detail-item-title" x-text="eventLabel(event)"></div><div class="cpa-detail-item-meta" x-text="eventMeta(event)"></div></div>
                            </template>
                        </div>
                        <p class="cpa-muted" x-show="!sheetWorkspace.data.events?.length">Aucun &eacute;v&eacute;nement enregistr&eacute;.</p>
                    </section>
                    <section class="cpa-detail-section">
                        <h4>Documents</h4>
                        <div class="cpa-detail-list" x-show="sheetWorkspace.data.documents?.length">
                            <template x-for="document in sheetWorkspace.data.documents" :key="document.id || document.url || document.name">
                                <div class="cpa-detail-item"><a class="cpa-document-link" :href="document.url || document.download_url || '#'" target="_blank" rel="noopener" x-text="document.original_name || document.name || document.label || 'Document joint'"></a><div class="cpa-detail-item-meta" x-text="documentMeta(document)"></div></div>
                            </template>
                        </div>
                        <p class="cpa-muted" x-show="!sheetWorkspace.data.documents?.length">Aucun document joint.</p>
                    </section>
                </div>
            </template>
        </div>
    </section>
    <section class="cpa-panel" x-show="tab === 'activity'">
        <div class="cpa-panel-head">
            <div>
                <h2 class="cpa-panel-title"><i class="fas fa-users-gear"></i>Acteurs des notes</h2>
                <p class="cpa-muted">Saisies et corrections réellement enregistrées, regroupées par personne, matière et classe.</p>
            </div>
        </div>
        <div class="cpa-list" x-show="data.actor_activity?.actors?.length">
            <template x-for="actor in data.actor_activity.actors" :key="actor.id">
                <article class="cpa-row">
                    <div class="cpa-row-main">
                        <div class="cpa-row-title" x-text="actor.name"></div>
                        <div class="cpa-row-meta">
                            <span x-text="`${actor.notes_entered} note(s) saisie(s)`"></span>
                            <span x-show="actor.notes_updated" x-text="`${actor.notes_updated} note(s) corrigée(s)`"></span>
                            <span x-text="`${actor.subjects_count} matière(s)`"></span>
                            <span x-text="`${actor.classes_count} classe(s)`"></span>
                            <span x-text="`${actor.sheets_completed} fiche(s) finalisée(s)`"></span>
                        </div>
                    </div>
                    <span class="cpa-badge" x-text="activityDate(actor.last_activity_at)"></span>
                </article>
            </template>
        </div>
        <div class="cpa-state" x-show="!data.actor_activity?.actors?.length">
            <i class="fas fa-users-gear"></i>Aucun acteur de saisie tracé sur ce périmètre.
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
                    @can('academic_alerts.acknowledge')
                    <button type="button" class="cpa-btn" @click="openAlertTransition(alert, 'acknowledged', 'Prendre en charge')" x-show="alert.status === 'open'"><i class="fas fa-check"></i>Prendre en charge</button>
                    <button type="button" class="cpa-btn" @click="openAlertTransition(alert, 'in_progress', 'Commencer le traitement')" x-show="alert.status === 'acknowledged'"><i class="fas fa-play"></i>Commencer</button>
                    @endcan
                    @can('academic_alerts.resolve')
                    <button type="button" class="cpa-btn cpa-btn--primary" @click="openAlertTransition(alert, 'resolved', 'Résoudre')" x-show="['acknowledged', 'in_progress'].includes(alert.status)"><i class="fas fa-check-double"></i>Résoudre</button>
                    <button type="button" class="cpa-btn" @click="openAlertTransition(alert, 'dismissed', 'Classer sans suite')" x-show="['open', 'acknowledged', 'in_progress'].includes(alert.status)"><i class="fas fa-box-archive"></i>Classer</button>
                    @endcan
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
    <section class="cpa-split" x-show="tab === 'students'">
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
    <section class="cpa-panel" x-show="tab === 'mine'">
        <div class="cpa-panel-head">
            <div>
                <h2 class="cpa-panel-title"><i class="fas fa-user-check"></i>Mon suivi</h2>
                <p class="cpa-muted">Votre activité réelle et les classes reconnues à partir des affectations, cours, évaluations, fiches et saisies.</p>
            </div>
        </div>
        <template x-if="data.actor_activity?.current_actor">
            <div>
                <div class="cpa-grid">
                    <div class="cpa-kpi"><div class="cpa-kpi-label">Notes saisies</div><div class="cpa-kpi-value" x-text="data.actor_activity.current_actor.notes_entered"></div><small>Entrées tracées</small></div>
                    <div class="cpa-kpi"><div class="cpa-kpi-label">Notes corrigées</div><div class="cpa-kpi-value" x-text="data.actor_activity.current_actor.notes_updated"></div><small>Modifications tracées</small></div>
                    <div class="cpa-kpi"><div class="cpa-kpi-label">Matières</div><div class="cpa-kpi-value" x-text="data.actor_activity.current_actor.subjects_count"></div><small>Matières distinctes</small></div>
                    <div class="cpa-kpi"><div class="cpa-kpi-label">Classes</div><div class="cpa-kpi-value" x-text="data.actor_activity.current_actor.classes_count"></div><small>Classes concernées</small></div>
                    <div class="cpa-kpi"><div class="cpa-kpi-label">Fiches finalisées</div><div class="cpa-kpi-value" x-text="data.actor_activity.current_actor.sheets_completed"></div><small>Fiches marquées saisies</small></div>
                </div>
                <div class="cpa-state mt-3">
                    <i class="fas fa-sitemap"></i>
                    <span x-text="scopeLabel()"></span>
                </div>
                <div class="cpa-detail-section mt-3">
                    <h4>Ma file de travail</h4>
                    <div class="cpa-list" x-show="data.my_sheets?.length">
                        <template x-for="sheet in data.my_sheets" :key="`mine-${sheet.id}`">
                            <article class="cpa-row">
                                <div class="cpa-row-main">
                                    <div class="cpa-row-title" x-text="sheetTitle(sheet)"></div>
                                    <div class="cpa-sheet-reference"><span>R&eacute;f&eacute;rence fiche</span><code x-text="sheet.code"></code></div>
                                    <div class="cpa-row-meta">
                                        <span x-text="sheet.classe || 'Classe non disponible'"></span>
                                        <span x-text="entryModeLabel(sheet)"></span>
                                        <span x-text="`${sheet.status_label} · ${statusProgressLabel(sheet)}`"></span>
                                    </div>
                                </div>
                                <button type="button" class="cpa-btn cpa-btn--compact" @click="setTab('sheets'); openSheet(sheet.id)">
                                    <i class="fas fa-arrow-right"></i>Traiter
                                </button>
                            </article>
                        </template>
                    </div>
                    <div class="cpa-state" x-show="!data.my_sheets?.length"><i class="fas fa-circle-check"></i>Aucune fiche rattachée à votre activité sur ces filtres.</div>
                </div>
            </div>
        </template>
    </section>
    @include('esbtp.pilotage-academique._assignments')
    <div class="cpa-modal-backdrop" x-cloak x-show="transitionModal.open" x-transition.opacity @keydown.escape.window="closeTransitionModal()" @click.self="closeTransitionModal()" role="presentation">
        <section class="cpa-modal" role="dialog" aria-modal="true" aria-labelledby="sheet-transition-title">
            <div class="cpa-modal-head">
                <div>
                    <h3 id="sheet-transition-title" class="cpa-panel-title">Confirmer la transition</h3>
                    <p class="cpa-muted mt-2"><span x-text="transitionModal.action?.label"></span> la fiche <strong x-text="transitionModal.sheet?.code"></strong>.</p>
                </div>
                <button type="button" class="cpa-icon-btn" @click="closeTransitionModal()" :disabled="transitioning" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
            </div>
            <template x-if="transitionModal.error">
                <div class="cpa-state cpa-error mt-3"><i class="fas fa-circle-exclamation"></i><span x-text="transitionModal.error"></span></div>
            </template>
            <label class="cpa-field-label" for="sheet-transition-reason">Motif de la transition <span x-show="transitionModal.action?.reason_required">obligatoire</span></label>
            <textarea id="sheet-transition-reason" class="cpa-textarea" x-model="transitionModal.reason" :required="transitionModal.action?.reason_required" placeholder="Pr&eacute;cisez le motif pour l'historique et l'audit."></textarea>
            <div class="cpa-modal-actions">
                <button type="button" class="cpa-btn" @click="closeTransitionModal()" :disabled="transitioning">Annuler</button>
                <button type="button" class="cpa-btn cpa-btn--primary" @click="confirmSheetTransition()" :disabled="transitioning || (transitionModal.action?.reason_required && !transitionModal.reason.trim())">
                    <i class="fas" :class="transitioning ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="transitioning ? 'Mise &agrave; jour...' : 'Confirmer'"></span>
                </button>
            </div>
        </section>
    </div>
    <div class="cpa-modal-backdrop" x-cloak x-show="alertTransitionModal.open" x-transition.opacity @keydown.escape.window="closeAlertTransition()" @click.self="closeAlertTransition()" role="presentation">
        <section class="cpa-modal" role="dialog" aria-modal="true" aria-labelledby="alert-transition-title">
            <div class="cpa-modal-head">
                <div>
                    <h3 id="alert-transition-title" class="cpa-panel-title" x-text="alertTransitionModal.label || 'Mettre à jour l’alerte'"></h3>
                    <p class="cpa-muted mt-2" x-text="alertTransitionModal.alert?.message"></p>
                </div>
                <button type="button" class="cpa-icon-btn" @click="closeAlertTransition()" :disabled="alertTransitioning" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
            </div>
            <template x-if="alertTransitionModal.error">
                <div class="cpa-state cpa-error mt-3"><i class="fas fa-circle-exclamation"></i><span x-text="alertTransitionModal.error"></span></div>
            </template>
            <label class="cpa-field-label" for="alert-transition-reason">Motif obligatoire</label>
            <textarea id="alert-transition-reason" class="cpa-textarea" x-model="alertTransitionModal.reason" required placeholder="Précisez la décision ou l’action réalisée pour conserver une piste d’audit claire."></textarea>
            <div class="cpa-modal-actions">
                <button type="button" class="cpa-btn" @click="closeAlertTransition()" :disabled="alertTransitioning">Annuler</button>
                <button type="button" class="cpa-btn cpa-btn--primary" @click="confirmAlertTransition()" :disabled="alertTransitioning || !alertTransitionModal.reason.trim()">
                    <i class="fas" :class="alertTransitioning ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="alertTransitioning ? 'Mise à jour...' : 'Confirmer'"></span>
                </button>
            </div>
        </section>
    </div>
</div>
@endsection

{{-- Moteur de graphique partage avec les tableaux de bord de role : meme
     palette, memes tooltips. Cette page pilote ses canvas depuis Alpine via
     window.klassciGraphique, elle n'utilise donc pas le gabarit statique. --}}
<x-chart-engine />

@include('esbtp.pilotage-academique._dashboard-script')
