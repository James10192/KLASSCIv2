<div class="juy-modal" x-show="reviewOpen" x-cloak @keydown.escape.window="closeReview()">
    <div class="juy-modal-body" @click.outside="closeReview()">
        <h2><i class="fas fa-shield-halved"></i> <span x-text="review.title"></span></h2>
        <p style="color:#475569;font-size:.9rem;line-height:1.55;" x-text="review.message"></p>
        <div x-show="review.kind === 'rectify'" style="margin-top:1rem;">
            <label for="rectification_motif" style="display:block;font-size:.78rem;font-weight:700;color:#475569;margin-bottom:.35rem;">Motif de rectification</label>
            <textarea id="rectification_motif" x-model="review.motif" rows="4" maxlength="1000" style="width:100%;padding:.65rem;border:1px solid #cbd5e1;border-radius:10px;font-size:.9rem;" placeholder="Ex: erreur matérielle détectée après publication, correction validée par le jury."></textarea>
            <p style="margin:.35rem 0 0;color:#64748b;font-size:.78rem;">Le motif sera conservé dans le cycle de vie du document remplacé.</p>
        </div>
        <div style="margin-top:1.25rem;display:flex;gap:.5rem;justify-content:flex-end;">
            <button type="button" class="juy-btn juy-btn--secondary h-11" @click="closeReview()">Annuler</button>
            <button type="button" class="juy-btn juy-btn--primary h-11" @click="confirmReview()" :disabled="busy || (review.kind === 'rectify' && (!review.motif || review.motif.length < 12))">
                <span x-text="busy ? 'Traitement…' : review.actionLabel"></span>
            </button>
        </div>
    </div>
</div>

<div class="juy-modal" x-show="signatureOpen" x-cloak @keydown.escape.window="closeSignature()">
    <div class="juy-modal-body" @click.outside="closeSignature()">
        <h2><i class="fas fa-signature"></i> Signer ma présence</h2>
        <p style="color:#475569;font-size:.85rem;line-height:1.5;">
            Cette signature sera liée à votre compte, à la date, à l'adresse IP et à votre navigateur.
        </p>
        <canvas x-ref="signatureCanvas" class="juy-signature-canvas"
                @pointerdown="startSignature($event)" @pointermove="drawSignature($event)"
                @pointerup="stopSignature()" @pointerleave="stopSignature()"></canvas>
        <div style="margin-top:1rem;display:flex;gap:.5rem;justify-content:space-between;">
            <button type="button" class="juy-btn juy-btn--secondary h-11" @click="clearSignature()">Effacer</button>
            <div style="display:flex;gap:.5rem;">
                <button type="button" class="juy-btn juy-btn--secondary h-11" @click="closeSignature()">Annuler</button>
                <button type="button" class="juy-btn juy-btn--primary h-11" @click="saveSignature()" :disabled="busy || !signatureDrawn">
                    <i class="fas fa-check"></i> Enregistrer ma signature
                </button>
            </div>
        </div>
    </div>
</div>
