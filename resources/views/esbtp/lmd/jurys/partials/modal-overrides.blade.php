<div class="juy-modal" x-show="overrideOpen" x-cloak @keydown.escape.window="closeOverride()">
    <div class="juy-modal-body" @click.outside="closeOverride()">
        <h2><i class="fas fa-pen-to-square"></i> Override décision</h2>
        <div style="background:#f8fafc;padding:.75rem;border-radius:8px;margin-bottom:1rem;">
            <div style="font-weight:600;color:#1e293b;" x-text="form.etudiant_name"></div>
            <div style="font-size:.78rem;color:#64748b;">
                Auto: <span :class="'juy-dec-chip juy-dec-chip--'+form.decision_auto" x-text="form.decision_auto"></span>
            </div>
        </div>
        <div>
            <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;display:block;margin-bottom:.3rem;">Nouvelle décision *</label>
            <x-au-select
                name="override_decision"
                placeholder="Nouvelle décision"
                icon="fa-scale-balanced"
                :options="[
                    'admis' => 'Admis',
                    'admission_rattrapage' => 'Admission rattrapage',
                    'ajourne' => 'Ajourné',
                    'exclu' => 'Exclu',
                    'admis_sous_condition' => 'Admis sous condition',
                    'defere' => 'Différé',
                ]"
                x-model="form.decision" />
        </div>
        <div style="margin-top:.75rem;">
            <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;display:block;margin-bottom:.3rem;">Motif * (min 5 caractères)</label>
            <textarea x-model="form.motif" rows="3" required maxlength="1000" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.88rem;" placeholder="Ex: Cas exceptionnel, situation médicale documentée, vote majoritaire."></textarea>
        </div>
        <div style="margin-top:.75rem;">
            <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;display:block;margin-bottom:.3rem;">Résultat vote</label>
            <x-au-select
                name="override_vote"
                placeholder="Aucun (consensus)"
                icon="fa-check-to-slot"
                :options="[
                    'unanime' => 'Unanime',
                    'majorite' => 'Majorité',
                    'partage_voix_president' => 'Voix du président',
                ]"
                x-model="form.vote_resultat" />
        </div>
        <div style="margin-top:1.25rem;display:flex;gap:.5rem;justify-content:flex-end;">
            <button type="button" @click="closeOverride()" class="juy-btn juy-btn--secondary h-11">Annuler</button>
            <button type="button" @click="saveOverride()" class="juy-btn juy-btn--primary h-11" :disabled="busy || !form.motif || form.motif.length < 5">
                <i class="fas fa-check"></i> <span x-text="busy ? 'Enregistrement…' : 'Enregistrer'"></span>
            </button>
        </div>
    </div>
</div>
