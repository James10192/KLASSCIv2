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
            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #f1f5f9;display:grid;grid-template-columns:2fr 1fr auto;gap:.5rem;align-items:end;">
                <div>
                    <label style="font-size:.7rem;color:#475569;font-weight:600;text-transform:uppercase;">Utilisateur</label>
                    <select x-model="newMembreUserId" style="width:100%;padding:.4rem;border:1px solid #e2e8f0;border-radius:7px;font-size:.85rem;">
                        <option value="">— Sélectionner —</option>
                        @foreach($enseignants as $e)
                        <option value="{{ $e->id }}">{{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:.7rem;color:#475569;font-weight:600;text-transform:uppercase;">Rôle</label>
                    <select x-model="newMembreRole" style="width:100%;padding:.4rem;border:1px solid #e2e8f0;border-radius:7px;font-size:.85rem;">
                        <option value="president">Président</option>
                        <option value="assesseur">Assesseur</option>
                        <option value="secretaire">Secrétaire</option>
                        <option value="consultatif">Consultatif</option>
                    </select>
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
