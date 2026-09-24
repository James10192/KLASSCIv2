{{-- Verification du contact des demandes du portail (docs/api/PORTAIL_VERIFICATION_CONTACT.md). --}}
@php $_cleVerification = \App\Services\TenantScolariteSettings::VERIFICATION_CONTACT; @endphp
<div class="bc-card" id="verification-contact-reglage">
    <div class="bc-icon"><i class="fas fa-user-shield"></i></div>
    <div class="bc-body">
        <div class="bc-label">Vérifier le contact des demandes en ligne</div>
        <div class="bc-desc">
            Après une candidature ou une demande de réinscription sur klassci.com, la famille reçoit un code par e-mail, ou par WhatsApp sans adresse joignable.
            La demande reste visible ; tant que le contact n'est pas vérifié, elle porte un badge, n'est ni placée en rendez-vous ni convoquée par courriel, et vous pouvez la « Confirmer » après l'avoir appelée.
            Désactivé : aucun code n'est envoyé, le portail fonctionne comme avant.
        </div>
    </div>
    <div class="bc-toggle">
        <label class="form-switch-modern">
            <input type="checkbox" name="{{ $_cleVerification }}" value="1"
                   {{ \App\Helpers\SettingsHelper::get($_cleVerification, '0') == '1' ? 'checked' : '' }}>
            <span class="slider"></span>
        </label>
    </div>
</div>
