<div x-show="tab==='composition'" x-cloak>
    <div class="juy-card">
        <h2><i class="fas fa-users"></i> Quorum &amp; membres</h2>
        <template x-if="quorum.ok">
            <div class="juy-quorum-ok">
                <i class="fas fa-check-circle"></i>
                Quorum atteint, <span x-text="quorum.present"></span> membres présents (min <span x-text="quorum.min"></span>)
                <template x-if="quorum.has_president"><span style="background:rgba(16,185,129,.15);padding:.1rem .4rem;border-radius:4px;font-size:.7rem;">Président présent</span></template>
            </div>
        </template>
        <template x-if="!quorum.ok">
            <div class="juy-quorum-ko">
                <div><i class="fas fa-triangle-exclamation"></i> Quorum non atteint</div>
                <template x-for="r in quorum.reasons" :key="r">
                    <div style="font-weight:400;font-size:.78rem;color:#92400e;">• <span x-text="r"></span></div>
                </template>
            </div>
        </template>

        <div style="margin-top:1rem;">
            <template x-for="m in membres" :key="m.id">
                <div class="juy-membre-row">
                    <div class="juy-membre-info">
                        <i :class="m.has_signed ? 'fas fa-check-circle' : 'far fa-circle'" :style="m.has_signed ? 'color:#10b981' : 'color:#94a3b8'"></i>
                        <div>
                            <div style="font-weight:600;color:#1e293b;" x-text="m.user_name"></div>
                            <div style="font-size:.7rem;color:#64748b;" x-text="m.present ? 'Présent' : 'Absent'"></div>
                        </div>
                        <span :class="'juy-role-chip juy-role-chip--'+m.role" x-text="m.role"></span>
                    </div>
                    <template x-if="m.can_sign">
                        <button type="button" class="juy-btn juy-btn--primary h-11" @click="openSignature(m)" :disabled="busy">
                            <i class="fas fa-signature"></i> Signer ma présence
                        </button>
                    </template>
                    @can('lmd.jury.preside')
                    @if(!$jury->isLocked())
                    <button type="button" @click="requestRemoveMembre(m)" style="background:none;border:none;color:#b91c1c;cursor:pointer;font-size:.85rem;padding:.3rem;">
                        <i class="fas fa-times"></i>
                    </button>
                    @endif
                    @endcan
                </div>
            </template>

            @can('lmd.jury.preside')
            @if(!$jury->isLocked())
            <div class="juy-add-membre">
                <div>
                    <label class="juy-field-label">Utilisateur</label>
                    <x-au-user-picker
                        name="new_membre_user_id"
                        :users="$enseignants"
                        placeholder="Sélectionner un membre"
                        x-on:change="newMembreUserId = $event.target.value" />
                </div>
                <div>
                    <label class="juy-field-label">Rôle</label>
                    <x-au-select
                        name="new_membre_role"
                        placeholder="Rôle"
                        icon="fa-user-tie"
                        :options="['president' => 'Président', 'assesseur' => 'Assesseur', 'secretaire' => 'Secrétaire', 'consultatif' => 'Consultatif']"
                        x-model="newMembreRole" />
                </div>
                <button type="button" class="juy-btn juy-btn--primary h-11" @click="addMembre()" :disabled="!newMembreUserId || busy">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
            @endif
            @endcan
        </div>
    </div>
</div>
