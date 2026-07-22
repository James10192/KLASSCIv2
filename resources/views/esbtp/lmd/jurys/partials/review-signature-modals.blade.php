<div class="juy-modal" x-show="reviewOpen" x-cloak @keydown.escape.window="closeReview()">
    <div class="juy-modal-body" @click.outside="closeReview()">
        <h2><i class="fas fa-shield-halved"></i> <span x-text="review.title"></span></h2>
        <p style="color:#475569;font-size:.9rem;line-height:1.55;" x-text="review.message"></p>
        <div style="margin-top:1.25rem;display:flex;gap:.5rem;justify-content:flex-end;">
            <button type="button" class="juy-btn juy-btn--secondary h-11" @click="closeReview()">Annuler</button>
            <button type="button" class="juy-btn juy-btn--primary h-11" @click="confirmReview()" :disabled="busy">
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
