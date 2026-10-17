{{-- Pool ECUE multi-enseignants et responsable UE unique. --}}
@can('lmd.planning.edit')
<div id="lptBackdrop"
     class="lpt-backdrop"
     x-data="lptModal()"
     :class="{ 'lpt-backdrop--open': open }"
     @lpt:open.window="onOpen($event.detail)"
     @keydown.escape.window="handleEscape($event)"
     @click.self="closeModal()"
     x-cloak>
    <div class="lpt-modal" role="dialog" aria-modal="true" aria-labelledby="lptTitle" :aria-busy="saving || loadingPool" @keydown.tab="trapFocus($event)">
        <div class="lpt-header">
            <div class="lpt-header-text">
                <h3 id="lptTitle">
                    <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                    <span x-show="role === 'responsable_ue'">Responsable de l'UE</span>
                    <span x-show="role !== 'responsable_ue'">Professeurs de l'ECUE</span>
                </h3>
                <div class="lpt-header-meta" x-text="targetLabel"></div>
            </div>
            <button type="button" class="lpt-close" @click="closeModal()" :disabled="saving" aria-label="Fermer">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="lpt-body">
            <div x-show="role !== 'responsable_ue'">
                <p class="lpt-context-hint">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    Plusieurs enseignants peuvent être prévus pour cet ECUE. Le professeur réel reste confirmé par classe.
                </p>
                <div class="lpt-loading" x-show="loadingPool" role="status" aria-live="polite" x-cloak>
                    <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
                    Lecture des affectations…
                </div>
                <div class="lpt-load-error" x-show="loadError && !loadingPool" role="alert" x-cloak>
                    <span x-text="loadError"></span>
                    <button type="button" @click="retryPool()">Réessayer</button>
                </div>
                <div x-show="!loadingPool && !loadError" x-cloak>
                    <div class="lpt-section-heading">
                        <span>Enseignants sélectionnés</span>
                        <span class="lpt-count" x-text="selectedIds.length + ' sélectionné(s)'"></span>
                    </div>
                    <div class="lpt-selected-list" aria-live="polite">
                        <template x-for="teacher in selectedTeachers" :key="teacher.id">
                            <div class="lpt-selected-option">
                                <span class="lpt-avatar" x-text="initials(teacher.name)"></span>
                                <span class="lpt-option-details">
                                    <strong x-text="teacher.name"></strong>
                                    <small x-text="teacher.email || 'Enseignant'"></small>
                                </span>
                                <button type="button" class="lpt-remove" @click="removeTeacher(teacher.id)"
                                        :aria-label="'Retirer ' + teacher.name" title="Retirer cet enseignant">
                                    <i class="fas fa-times" aria-hidden="true"></i>
                                </button>
                            </div>
                        </template>
                        <div class="lpt-empty-hint" x-show="selectedIds.length === 0" x-cloak>
                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                            Aucun enseignant sélectionné.
                        </div>
                    </div>

                    <label class="lpt-section-heading lpt-available-title" for="lptTeacherSearch">
                        <span>Autres enseignants disponibles</span>
                    </label>
                    <div class="lpt-search-wrap">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input id="lptTeacherSearch" x-ref="search" type="search" x-model.debounce.150ms="teacherSearch"
                               placeholder="Rechercher par nom ou e-mail…" autocomplete="off"
                               aria-label="Rechercher parmi les enseignants disponibles">
                    </div>
                    <div class="lpt-teacher-pool" aria-label="Enseignants disponibles">
                        <template x-for="teacher in availableTeachers" :key="teacher.id">
                            <button type="button" class="lpt-available-option" @click="addTeacher(teacher.id)">
                                <span class="lpt-avatar" x-text="initials(teacher.name)"></span>
                                <span class="lpt-option-details">
                                    <strong x-text="teacher.name"></strong>
                                    <small x-text="teacher.email || 'Enseignant'"></small>
                                </span>
                                <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                <span class="sr-only" x-text="'Ajouter ' + teacher.name"></span>
                            </button>
                        </template>
                        <div class="lpt-empty-hint" x-show="availableTeachers.length === 0" x-cloak>
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span x-text="teacherSearch ? 'Aucun résultat pour cette recherche.' : 'Tous les enseignants disponibles sont sélectionnés.'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="role === 'responsable_ue'" x-cloak>
                <p class="lpt-context-hint">Un seul enseignant peut être responsable de cette UE.</p>
                <x-au-user-picker
                    name="lpt_user_id"
                    :users="$enseignants"
                    placeholder="— Sélectionner un responsable —"
                    empty-label="Aucun enseignant"
                    empty-icon="fa-user-slash"
                    empty-hint="Enregistrer ce choix retire l'assignation actuelle" />
                <div class="lpt-empty-hint" x-show="!currentTeacherId" x-cloak>
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    Aucun responsable affecté pour cette UE.
                </div>
            </div>
        </div>
        <div class="lpt-actions">
            <button type="button" class="lpt-btn lpt-btn-secondary" @click="closeModal()" :disabled="saving">Annuler</button>
            <button type="button" class="lpt-btn lpt-btn-danger"
                    x-show="role === 'responsable_ue' && currentTeacherId"
                    @click="unassign()" :disabled="saving">
                <i class="fas fa-user-times" aria-hidden="true"></i> Désassigner
            </button>
            <button type="button" class="lpt-btn lpt-btn-primary" @click="commit()" :disabled="saving || loadingPool || !!loadError">
                <span x-show="!saving"><i class="fas fa-check" aria-hidden="true"></i> Enregistrer</span>
                <span x-show="saving"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Enregistrement…</span>
            </button>
        </div>
    </div>
</div>
@endcan
