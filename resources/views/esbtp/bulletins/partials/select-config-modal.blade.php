{{-- Modal « Configuration requise du bulletin » : types / coefficients / professeurs
     + copie inter-semestres (Écraser / Compléter) + portée d'enregistrement S1/S2.
     Extrait de select.blade.php (état Alpine : busSelect() dans select-scripts). --}}
    <div class="bus-config-backdrop"
         x-show="configModal.open"
         x-transition.opacity
         x-cloak
         role="dialog"
         aria-modal="true"
         aria-labelledby="busConfigTitle"
         @keydown.escape.window="closeConfigModal()">
        <div class="bus-config-modal" @click.outside="closeConfigModal()">
            <div class="bus-config-modal__head">
                <div class="bus-config-modal__head-left">
                    <div class="bus-config-modal__head-icon"><i class="fas fa-sliders"></i></div>
                    <div>
                        <h2 class="bus-config-modal__title" id="busConfigTitle">Configuration requise du bulletin</h2>
                        <p class="bus-config-modal__subtitle" x-text="configModal.subtitle"></p>
                    </div>
                </div>
                <button type="button" class="bus-config-modal__close" @click="closeConfigModal()" aria-label="Fermer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div class="bus-config-modal__body">
                <div class="bus-config-empty" x-show="configModal.loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    Chargement de la configuration...
                </div>
                <div class="bus-config-error" x-show="configModal.error" x-text="configModal.error"></div>

                <template x-if="!configModal.loading && !configModal.error">
                    <div>
                        <div class="bus-config-summary">
                            <div class="bus-config-summary__item">
                                <span class="bus-config-summary__label">Types matieres</span>
                                <span class="bus-config-summary__value" x-text="configModal.matieres.filter(m => m.selected_type && m.selected_type !== 'none').length + ' / ' + configModal.matieres.length"></span>
                            </div>
                            <div class="bus-config-summary__item">
                                <span class="bus-config-summary__label">Coefficients</span>
                                <span class="bus-config-summary__value" x-text="configModal.matieres.filter(m => m.coefficient !== null && String(m.coefficient).trim() !== '').length + ' / ' + configModal.matieres.length"></span>
                            </div>
                            <div class="bus-config-summary__item">
                                <span class="bus-config-summary__label">Professeurs</span>
                                <span class="bus-config-summary__value" x-text="configModal.matieres.filter(m => String(m.professeur || '').trim() !== '').length + ' / ' + configModal.matieres.length"></span>
                            </div>
                        </div>

                        {{-- Barre d'outils : copie depuis l'autre semestre --}}
                        <div class="bus-config-toolbar" x-show="canCopyOtherSemestre()">
                            <button type="button"
                                    class="bus-config-copy-btn"
                                    @click="fetchOtherSemester()"
                                    :disabled="copyState.loading"
                                    :aria-expanded="copyState.open ? 'true' : 'false'">
                                <i class="fas" :class="copyState.loading ? 'fa-spinner fa-spin' : 'fa-copy'"></i>
                                <span x-text="'Copier depuis le ' + periodeLabel(otherPeriode())"></span>
                            </button>
                            <span class="bus-config-toolbar__hint">
                                Réutilisez les types, coefficients et professeurs déjà saisis sur l'autre semestre.
                                La copie et l'enregistrement s'appliquent aux matières listées ici.
                            </span>
                        </div>

                        {{-- Mini-panneau de choix Écraser / Compléter --}}
                        <div class="bus-config-copy-panel" x-show="copyState.open" x-cloak role="group" aria-label="Mode de copie">
                            <div class="bus-config-copy-panel__title">
                                <i class="fas fa-circle-question"></i>
                                <span x-text="'Comment appliquer les valeurs du ' + periodeLabel(otherPeriode()) + ' ?'"></span>
                            </div>
                            <div class="bus-config-copy-panel__choices">
                                <button type="button" class="bus-config-copy-choice" @click="applyCopy('overwrite')">
                                    <span class="bus-config-copy-choice__name"><i class="fas fa-arrows-rotate"></i> Écraser</span>
                                    <span class="bus-config-copy-choice__desc">Remplacer les valeurs actuelles par celles de l'autre semestre (les champs vides là-bas ne touchent à rien ici).</span>
                                </button>
                                <button type="button" class="bus-config-copy-choice" @click="applyCopy('merge')">
                                    <span class="bus-config-copy-choice__name"><i class="fas fa-object-group"></i> Compléter</span>
                                    <span class="bus-config-copy-choice__desc">Garder tout ce qui est déjà saisi ici, et remplir uniquement les champs vides.</span>
                                </button>
                            </div>
                            <button type="button" class="bus-config-copy-panel__cancel" @click="copyState.open = false">Annuler la copie</button>
                        </div>

                        <div class="bus-config-empty" x-show="!configModal.matieres.length">
                            Aucune matiere a configurer pour cette classe et cette periode.
                        </div>

                        <div class="bus-config-table-wrap" x-show="configModal.matieres.length">
                            <table class="bus-config-table">
                                <thead>
                                    <tr>
                                        <th>Matiere</th>
                                        <th>Source</th>
                                        <th>Type</th>
                                        <th>Coeff.</th>
                                        <th>Professeur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="matiere in configModal.matieres" :key="matiere.id">
                                        <tr>
                                            <td>
                                                <strong x-text="matiere.name"></strong>
                                                <div style="color:var(--bus-muted); font-size:.72rem;" x-show="matiere.code" x-text="matiere.code"></div>
                                            </td>
                                            <td><span class="bus-config-source" x-text="matiere.source === 'evaluations' ? 'notes' : 'classe'"></span></td>
                                            <td :class="matiere._copied && matiere._copied.type ? 'bus-config-cell--copied' : ''">
                                                <select x-model="matiere.selected_type" :aria-label="'Type de ' + matiere.name">
                                                    <option value="general">Generale</option>
                                                    <option value="technique">Technique</option>
                                                    <option value="none">Ignorer</option>
                                                </select>
                                            </td>
                                            <td :class="matiere._copied && matiere._copied.coeff ? 'bus-config-cell--copied' : ''">
                                                <input type="number"
                                                       min="0.1"
                                                       step="0.1"
                                                       x-model="matiere.coefficient"
                                                       :aria-label="'Coefficient de ' + matiere.name"
                                                       placeholder="Coeff.">
                                            </td>
                                            <td :class="matiere._copied && matiere._copied.prof ? 'bus-config-cell--copied' : ''">
                                                <input type="text"
                                                       maxlength="255"
                                                       x-model="matiere.professeur"
                                                       :aria-label="'Professeur de ' + matiere.name"
                                                       placeholder="Nom du professeur">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
            <div class="bus-config-modal__footer">
                {{-- Portée du save : semestre courant seul, ou les deux semestres --}}
                <div class="bus-config-scope" x-show="canCopyOtherSemestre()" role="radiogroup" aria-label="Portée de l'enregistrement">
                    <span class="bus-config-scope__label">Enregistrer pour :</span>
                    <label class="bus-config-scope__opt" :class="saveScope === 'selected' ? 'is-active' : ''">
                        <input type="radio" value="selected" x-model="saveScope">
                        <span x-text="periodeLabel(configModal.context.periode) + ' uniquement'"></span>
                    </label>
                    <label class="bus-config-scope__opt" :class="saveScope === 'both' ? 'is-active' : ''">
                        <input type="radio" value="both" x-model="saveScope">
                        <span>Les deux semestres</span>
                    </label>
                </div>
                <div class="bus-config-modal__footer-actions">
                    <button type="button" class="bus-config-action" @click="closeConfigModal()" :disabled="configModal.saving">Annuler</button>
                    <button type="button"
                            class="bus-config-action bus-config-action--primary"
                            @click="saveConfigModal()"
                            :disabled="configModal.loading || configModal.saving || configModal.error || !configModal.matieres.length">
                        <i class="fas" :class="configModal.saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                        <span x-text="configModal.saving ? 'Enregistrement...' : (saveScope === 'both' ? 'Enregistrer (S1 + S2)' : 'Enregistrer la configuration')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
