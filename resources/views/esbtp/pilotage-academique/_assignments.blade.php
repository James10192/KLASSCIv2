@can('academic_sheets.assign')
<section class="cpa-panel cpa-assignment-panel" x-show="tab === 'assignments'">
    <div class="cpa-panel-head">
        <div>
            <h2 class="cpa-panel-title"><i class="fas fa-user-tag"></i>Affectation des acteurs</h2>
            <p class="cpa-muted">Définissez qui reçoit, saisit, contrôle ou suit les fiches d'une classe pour l'année sélectionnée.</p>
        </div>
    </div>
    <form class="cpa-assignment-form cpa-assignment-composer" x-ref="assignmentForm" @submit.prevent="saveAssignment()">
        <div class="cpa-field cpa-assignment-user">
            <label class="cpa-field-label">Utilisateur</label>
            <x-au-user-picker name="user_id" :users="$assignmentUsers" placeholder="Choisir un utilisateur" :empty-option="false" />
        </div>
        <div class="cpa-field cpa-assignment-class">
            <label class="cpa-field-label">Classe</label>
            <x-au-select name="classe_id" :options="$classeOptions" placeholder="Choisir une classe" icon="fa-school" searchable />
        </div>
        <div class="cpa-field cpa-assignment-responsibility">
            <label class="cpa-field-label">Responsabilité</label>
            <x-au-select name="responsibility" :options="$responsibilityOptions" placeholder="Choisir une responsabilité" icon="fa-list-check" />
        </div>
        <button type="submit" class="cpa-btn cpa-btn--primary cpa-assignment-submit" :disabled="assignmentState.saving || !filters.year_id">
            <i class="fas" :class="assignmentState.saving ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
            <span x-text="assignmentState.saving ? 'Enregistrement…' : 'Affecter'"></span>
        </button>
    </form>
    <template x-if="assignmentState.error"><div class="cpa-state cpa-error mt-3"><i class="fas fa-circle-exclamation"></i><span x-text="assignmentState.error"></span></div></template>
    <template x-if="assignmentState.success"><div class="cpa-state mt-3"><i class="fas fa-circle-check"></i><span x-text="assignmentState.success"></span></div></template>
    <div class="cpa-list mt-3" x-show="assignmentState.items.length">
        <template x-for="assignment in assignmentState.items" :key="assignment.id">
            <article class="cpa-row">
                <div class="cpa-row-main">
                    <div class="cpa-row-title" x-text="assignment.user?.name || 'Utilisateur supprimé'"></div>
                    <div class="cpa-row-meta">
                        <span x-text="assignment.classe?.name || 'Classe supprimée'"></span>
                        <span x-text="assignment.responsibility_label || assignment.responsibility"></span>
                        <span x-text="assignment.annee_universitaire?.name || 'Année non disponible'"></span>
                    </div>
                </div>
                <button type="button" class="cpa-icon-btn" title="Désactiver l'affectation" @click="deactivateAssignment(assignment)" :disabled="assignmentState.saving">
                    <i class="fas fa-trash"></i>
                </button>
            </article>
        </template>
    </div>
    <div class="cpa-state cpa-assignment-empty mt-3" x-show="!assignmentState.loading && !assignmentState.items.length"><i class="fas fa-user-tag"></i>Aucune affectation active pour ces filtres.</div>
</section>
@endcan
