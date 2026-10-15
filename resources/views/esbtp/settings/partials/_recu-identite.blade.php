{{-- Réglage indépendant du bulletin : n'affecte que le libellé « Encaissé par » sur le reçu. --}}
<div class="settings-section" id="pdf-receipt-cashier" x-data="{ choix: @js(old('setting_receipt_cashier_display', \App\Helpers\SettingsHelper::get(\App\Domain\Comptabilite\Paiements\Services\IdentiteEncaisseurRecu::KEY, 'nom'))) }">
    <div class="section-header">
        <div class="section-icon pdf"><i class="fas fa-receipt" aria-hidden="true"></i></div>
        <div>
            <h3 class="section-title">Identité de l'encaisseur sur les reçus</h3>
            <p class="section-description">Choisissez ce qui s'imprime à la ligne « Encaissé par ». Le signataire et le nom complet dans le journal de caisse restent inchangés.</p>
        </div>
    </div>
    <div class="settings-grid-2" style="margin-top:16px;">
        <label class="form-group" style="cursor:pointer;padding:14px;border:1px solid #dce3ef;border-radius:12px;">
            <span style="display:flex;gap:10px;align-items:center;font-weight:700;color:#17243b;">
                <input type="radio" name="setting_receipt_cashier_display" value="nom" x-model="choix">
                Nom complet
            </span>
            <small class="text-muted" style="display:block;margin-top:6px;">Exemple : Jean Christ Adai</small>
        </label>
        <label class="form-group" style="cursor:pointer;padding:14px;border:1px solid #dce3ef;border-radius:12px;">
            <span style="display:flex;gap:10px;align-items:center;font-weight:700;color:#17243b;">
                <input type="radio" name="setting_receipt_cashier_display" value="initiales" x-model="choix">
                Initiales
            </span>
            <small class="text-muted" style="display:block;margin-top:6px;">Exemple : J. C. A.</small>
        </label>
    </div>
    <div style="padding:12px 14px;background:#f6f9ff;border:1px solid #dbe8fa;border-radius:10px;margin-top:12px;">
        <span class="text-muted" style="font-size:.82rem;">Aperçu du reçu :</span>
        <strong style="margin-left:8px;color:#0453cb;" x-text="choix === 'initiales' ? 'Encaissé par : J. C. A.' : 'Encaissé par : Jean Christ Adai'"></strong>
    </div>
</div>
