{{-- Modal d'assignation enseignant à une planification ECUE.
     S'ouvre via window CustomEvent `lpt:open` avec detail.
     Reuses <x-au-user-picker> pour la sélection (recherche + groupement par rôle). --}}
@can('lmd.planning.edit')
<div id="lptBackdrop"
     class="lpt-backdrop"
     x-data="lptModal()"
     :class="{ 'lpt-backdrop--open': open }"
     @lpt:open.window="onOpen($event.detail)"
     @keydown.escape.window="open = false"
     @click.self="open = false"
     x-cloak>
    <div class="lpt-modal" role="dialog" aria-labelledby="lptTitle">
        <div class="lpt-header">
            <div>
                <h3 id="lptTitle"><i class="fas fa-chalkboard-teacher"></i> Assigner un enseignant</h3>
                <div class="lpt-header-meta" x-text="ecueLabel"></div>
            </div>
            <button type="button" class="lpt-close" @click="open = false" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="lpt-body">
            <x-au-user-picker
                name="lpt_user_id"
                :users="$enseignants"
                placeholder="— Sélectionner un enseignant —" />
        </div>
        <div class="lpt-actions">
            <button type="button" class="lpt-btn lpt-btn-secondary" @click="open = false">
                Annuler
            </button>
            <button type="button" class="lpt-btn lpt-btn-danger"
                    x-show="currentTeacherId"
                    @click="unassign()"
                    :disabled="saving">
                <i class="fas fa-user-times"></i> Désassigner
            </button>
            <button type="button" class="lpt-btn lpt-btn-primary"
                    @click="commit()"
                    :disabled="saving">
                <span x-show="!saving"><i class="fas fa-check"></i> Enregistrer</span>
                <span x-show="saving"><i class="fas fa-spinner fa-spin"></i> Enregistrement…</span>
            </button>
        </div>
    </div>
</div>
@endcan
