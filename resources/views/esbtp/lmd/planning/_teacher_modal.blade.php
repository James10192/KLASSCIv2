{{-- Modal d'assignation enseignant — modes :
     - role='enseignant_ecue' : pool de plusieurs enseignants possibles pour l'ECUE
     - role='responsable_ue'  : un responsable unique pour l'UE (UEMOA 03/2007/CM)
     Le professeur REEL d'une classe est ensuite resolu/confirmé sur les evaluations et seances. --}}
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
                <h3 id="lptTitle">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span x-show="role === 'responsable_ue'">Assigner le responsable de l'UE</span>
                    <span x-show="role !== 'responsable_ue'">Professeurs possibles pour l'ECUE</span>
                </h3>
                <div class="lpt-header-meta" x-text="targetLabel"></div>
            </div>
            <button type="button" class="lpt-close" @click="open = false" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="lpt-body">
            <div x-show="role !== 'responsable_ue'">
                <div class="alert alert-info py-2 mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Un parcours peut avoir plusieurs classes : cochez ici tous les professeurs susceptibles d'assurer cet ECUE. KLASSCI déterminera ensuite le professeur réel de chaque classe à partir de ses évaluations et séances.
                </div>
                <input type="search" class="form-control mb-3" x-model="teacherSearch" placeholder="Rechercher un enseignant…">
                <div class="lpt-teacher-pool">
                    @foreach($enseignants as $enseignant)
                        @php $lptSearch = mb_strtolower(trim(($enseignant->name ?? '').' '.($enseignant->email ?? '')), 'UTF-8'); @endphp
                        <label class="lpt-teacher-option"
                               x-show="!teacherSearch || @js($lptSearch).includes(teacherSearch.toLocaleLowerCase('fr'))">
                            <input type="checkbox" value="{{ $enseignant->id }}" x-model.number="selectedIds">
                            <span>
                                <strong>{{ $enseignant->name }}</strong>
                                @if($enseignant->email)<small>{{ $enseignant->email }}</small>@endif
                            </span>
                        </label>
                    @endforeach
                </div>
                <div class="lpt-empty-hint" x-show="selectedIds.length === 0" x-cloak>
                    <i class="fas fa-info-circle"></i>
                    <span>Aucun professeur n'est encore prévu pour cet ECUE.</span>
                </div>
            </div>

            <div x-show="role === 'responsable_ue'" x-cloak>
                <x-au-user-picker
                    name="lpt_user_id"
                    :users="$enseignants"
                    placeholder="— Sélectionner un responsable —"
                    empty-label="Aucun enseignant"
                    empty-icon="fa-user-slash"
                    empty-hint="Enregistrer ce choix retire l'assignation actuelle" />
                <div class="lpt-empty-hint" x-show="!currentTeacherId" x-cloak>
                    <i class="fas fa-info-circle"></i>
                    <span>Le responsable d'UE apparaîtra ici une fois sélectionné.</span>
                </div>
            </div>
        </div>
        <div class="lpt-actions">
            <button type="button" class="lpt-btn lpt-btn-secondary" @click="open = false">Annuler</button>
            <button type="button" class="lpt-btn lpt-btn-danger"
                    x-show="role === 'responsable_ue' && currentTeacherId"
                    @click="unassign()"
                    :disabled="saving">
                <i class="fas fa-user-times"></i> Désassigner
            </button>
            <button type="button" class="lpt-btn lpt-btn-primary" @click="commit()" :disabled="saving">
                <span x-show="!saving"><i class="fas fa-check"></i> Enregistrer</span>
                <span x-show="saving"><i class="fas fa-spinner fa-spin"></i> Enregistrement…</span>
            </button>
        </div>
    </div>
</div>

<style>
.lpt-teacher-pool{max-height:320px;overflow:auto;border:1px solid #e2e8f0;border-radius:12px}.lpt-teacher-option{display:flex;gap:.7rem;align-items:center;padding:.7rem .85rem;border-bottom:1px solid #eef2f7;cursor:pointer}.lpt-teacher-option:last-child{border-bottom:0}.lpt-teacher-option:hover{background:#f8fafc}.lpt-teacher-option span{display:flex;flex-direction:column;min-width:0}.lpt-teacher-option small{font-size:.72rem;color:#64748b}.lpt-teacher-option input{width:18px;height:18px;accent-color:#0453cb;flex-shrink:0}
</style>
@endcan