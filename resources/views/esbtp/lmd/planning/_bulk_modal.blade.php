{{-- Modal d'edition en masse des ECUE selectionnes.
     S'ouvre via window CustomEvent `lpb:open` avec detail.ecueIds + labels.
     Chaque champ a un toggle "Appliquer" — seuls les champs actives sont envoyes
     dans le payload. Les autres ECUE ne voient leurs valeurs ni reset ni touchees.
     Reuses x-au-user-picker pour le picker enseignant. --}}
@can('lmd.planning.edit')
<div id="lpbBackdrop"
     class="lpb-backdrop"
     x-data="lpbModal()"
     :class="{ 'lpb-backdrop--open': open }"
     @lpb:open.window="onOpen($event.detail)"
     @keydown.escape.window="open = false"
     @click.self="open = false"
     x-cloak>
    <div class="lpb-modal" role="dialog" aria-labelledby="lpbTitle">
        <div class="lpb-header">
            <div>
                <h3 id="lpbTitle"><i class="fas fa-layer-group"></i> Modification en lot</h3>
                <div class="lpb-header-meta">
                    Appliquer les modifications cochees a
                    <strong x-text="ecueIds.length"></strong>
                    ECUE selectionn<span x-text="ecueIds.length > 1 ? 'es' : 'e'"></span>.
                </div>
            </div>
            <button type="button" class="lpb-close" @click="open = false" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="lpb-body">
            <div class="lpb-targets">
                <div class="lpb-targets-label">
                    <i class="fas fa-list"></i>
                    ECUE cibles (<span x-text="ecueLabels.length"></span>)
                </div>
                <div class="lpb-targets-list">
                    <template x-for="label in ecueLabels" :key="label">
                        <span class="lpb-target-chip" x-text="label"></span>
                    </template>
                </div>
            </div>

            <div class="lpb-fields-grid">
                <template x-for="field in numericFields" :key="field.key">
                    <div class="lpb-field" :class="{ 'lpb-field--enabled': enabled[field.key] }">
                        <label class="lpb-field-toggle">
                            <input type="checkbox" x-model="enabled[field.key]">
                            <span x-text="field.label"></span>
                        </label>
                        <input type="number"
                               class="lpb-field-input"
                               :min="field.min"
                               :max="field.max"
                               :step="field.step"
                               x-model="values[field.key]"
                               :disabled="!enabled[field.key]"
                               :placeholder="field.placeholder">
                    </div>
                </template>

                <div class="lpb-field lpb-field--full" :class="{ 'lpb-field--enabled': enabled.enseignant_principal_id }">
                    <label class="lpb-field-toggle">
                        <input type="checkbox" x-model="enabled.enseignant_principal_id">
                        Enseignant principal
                    </label>
                    <div :style="enabled.enseignant_principal_id ? '' : 'opacity:.5; pointer-events:none;'">
                        <x-au-user-picker
                            name="lpb_user_id"
                            :users="$enseignants"
                            placeholder="— Selectionner un enseignant (vide = clear) —" />
                    </div>
                </div>
            </div>

            <div class="lpb-warn" x-show="errorList.length > 0" x-cloak>
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong x-text="errorList.length + ' ECUE n\'ont pas pu etre mis a jour :'"></strong>
                    <ul style="margin: .35rem 0 0; padding-left: 1.2rem;">
                        <template x-for="err in errorList" :key="err.ecue_id">
                            <li x-text="'ECUE ' + err.ecue_id + ' : ' + err.message"></li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        <div class="lpb-actions">
            <button type="button" class="lpb-btn lpb-btn-secondary" @click="open = false">Annuler</button>
            <button type="button"
                    class="lpb-btn lpb-btn-primary"
                    @click="commit()"
                    :disabled="saving || enabledCount === 0">
                <span x-show="!saving">
                    <i class="fas fa-check"></i>
                    Appliquer aux <span x-text="ecueIds.length"></span> ECUE
                </span>
                <span x-show="saving"><i class="fas fa-spinner fa-spin"></i> Enregistrement…</span>
            </button>
        </div>
    </div>
</div>
@endcan
