<div class="modal fade" id="rf-modal" tabindex="-1" aria-labelledby="rf-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:640px;">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 18px 40px rgba(15,23,42,.18);">
            <div class="rf-head">
                <div class="rf-head-icon"><i class="fas fa-rotate"></i></div>
                <div>
                    <h5 class="rf-title" id="rf-modal-title">Régénérer les frais</h5>
                    <p class="rf-sub" id="rf-modal-sub">Aperçu avant application</p>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="rf-term" id="rf-term" aria-live="polite"></div>
            <div class="rf-foot">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn-acasi primary" id="rf-confirm">
                    <i class="fas fa-check me-1"></i>Confirmer
                </button>
            </div>
        </div>
    </div>
</div>
<style>
.rf-head{display:flex;align-items:center;gap:12px;padding:16px 18px;background:linear-gradient(135deg,#0453cb,#5e91de);color:#fff;}
.rf-head-icon{width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.rf-title{margin:0;font-size:1rem;font-weight:700;letter-spacing:-.02em;}
.rf-sub{margin:2px 0 0;font-size:.78rem;opacity:.85;}
.rf-term{margin:0;padding:14px 16px;min-height:140px;max-height:280px;overflow:auto;background:#07111f;color:#dbeafe;font-family:SFMono-Regular,Consolas,"Liberation Mono",monospace;font-size:.8rem;line-height:1.65;white-space:pre-wrap;}
.rf-line-add{color:#6ee7b7;}
.rf-line-del{color:#93c5fd;}
.rf-line-adj{color:#fcd34d;}
.rf-line-warn{color:#fca5a5;}
.rf-line-muted{color:#64748b;}
.rf-foot{display:flex;justify-content:flex-end;gap:8px;padding:12px 16px;background:#f8fafc;border-top:1px solid #e2e8f0;}
</style>
