@extends('layouts.app')

@section('title', 'MailPulse - KLASSCI')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
<style>
.mp-page { --mailpulse-signal: #ff5a1f; font-family: "Plus Jakarta Sans", system-ui, sans-serif; max-width: 1100px; }
.mp-page .mailpulse-brand-card {
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
    padding: 18px 20px; border-radius: 14px; background: #fff;
    border: 1px solid #e5e7eb; color: #09090b; margin-bottom: 20px;
}
.mp-page .mailpulse-logo-mark {
    position: relative; display: inline-flex; align-items: center; justify-content: center;
    width: 52px; height: 36px; flex-shrink: 0;
}
.mp-page .mailpulse-logo-mark img { width: 48px; height: auto; object-fit: contain; }
.mp-page .mailpulse-wordmark { font-size: 1.35rem; font-weight: 750; color: #09090b; line-height: 1; }
.mp-page .mailpulse-wordmark span { color: var(--mailpulse-signal); }
.mp-page .mailpulse-brand-subtitle { margin-top: 6px; color: #a1a1aa; font-size: .86rem; }
.mp-page .mailpulse-status-badge {
    display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px;
    background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; font-size: .78rem; font-weight: 700;
}
.mp-page .mailpulse-status-badge.configured {
    background: #ecfdf5; color: #047857; border-color: #a7f3d0;
}
.mp-page .mp-section {
    background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; margin-bottom: 16px;
}
.mp-page .mp-section-head { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.mp-page .section-icon.mailpulse {
    width: 42px; height: 42px; border-radius: 12px; background: #09090b; color: var(--mailpulse-signal);
    display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 10px 24px rgba(9,9,11,.18);
}
.mp-page .section-title { margin: 0; font-size: 1.05rem; font-weight: 700; color: #09090b; }
.mp-page .section-description { margin: 2px 0 0; color: #64748b; font-size: .86rem; }
.mp-page .mailpulse-field-card { padding: 16px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; }
.mp-page .mailpulse-info-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.mp-page .mailpulse-info-card {
    padding: 14px; border-radius: 12px; background: #fafafa; border: 1px solid #e5e7eb; color: #3f3f46; font-size: .82rem;
}
.mp-page .mailpulse-info-card strong { color: #09090b; display: block; margin-bottom: 4px; }
.mp-page .mailpulse-info-card i { color: var(--mailpulse-signal); margin-right: 6px; }
.mp-page .mailpulse-test-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; align-items: end; }
.mp-page .form-label-modern { display: block; font-weight: 700; color: #334155; font-size: .82rem; margin-bottom: 6px; }
.mp-page .form-label-modern i { color: #0453cb; margin-right: 6px; }
.mp-page .mailpulse-toggle { display: flex; align-items: center; gap: 10px; min-height: 44px; color: #334155; font-weight: 600; }
.mp-page .mailpulse-toggle input { position: absolute; opacity: 0; pointer-events: none; }
.mp-page .mailpulse-toggle-slider {
    width: 42px; height: 24px; border-radius: 999px; background: #cbd5e1; position: relative; flex: 0 0 auto;
}
.mp-page .mailpulse-toggle-slider::after {
    content: ''; width: 18px; height: 18px; border-radius: 50%; background: #fff; position: absolute; top: 3px; left: 3px;
    box-shadow: 0 1px 3px rgba(15,23,42,.24); transition: transform .2s ease;
}
.mp-page .mailpulse-toggle input:checked + .mailpulse-toggle-slider { background: #0453cb; }
.mp-page .mailpulse-toggle input:checked + .mailpulse-toggle-slider::after { transform: translateX(18px); }
.mp-page .mailpulse-test-result { margin-top: 14px; border-radius: 12px; border: 1px solid #dbe4f0; background: #f8fafc; padding: 14px; color: #334155; font-size: .86rem; }
.mp-page .mailpulse-test-result.is-success { border-color: #a7f3d0; background: #ecfdf5; color: #065f46; }
.mp-page .mailpulse-test-result.is-error { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
.mp-page .mailpulse-test-kv { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-top: 10px; }
.mp-page .mailpulse-test-kv span { display: block; padding: 8px 10px; border-radius: 8px; background: rgba(255,255,255,.75); border: 1px solid rgba(148,163,184,.24); }
.mp-page .mailpulse-test-kv strong { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
@media (max-width: 992px) {
    .mp-page .mailpulse-info-grid, .mp-page .mailpulse-test-grid, .mp-page .mailpulse-test-kv { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="main-content">
<div class="mp-page">

    <div class="mailpulse-brand-card">
        <div class="mailpulse-logo-mark" aria-hidden="true">
            <img src="{{ asset('images/mailpulse/mailpulse-mark-light.png') }}" alt="">
        </div>
        <div class="mailpulse-brand-copy">
            <div class="mailpulse-wordmark">Mail<span>Pulse</span></div>
            <div class="mailpulse-brand-subtitle">Email, WhatsApp et automatisations transactionnelles</div>
        </div>
        <span class="mailpulse-status-badge {{ $enabled ? 'configured' : '' }}">
            <i class="fas {{ $enabled ? 'fa-bolt' : 'fa-pause' }}"></i>
            {{ $enabled ? 'Canal activé' : 'Canal désactivé' }}
        </span>
        <span class="mailpulse-status-badge {{ $apiConfigured ? 'configured' : '' }}">
            <i class="fas {{ $apiConfigured ? 'fa-lock' : 'fa-key' }}"></i>
            {{ $apiConfigured ? 'Clé API configurée' : 'Clé API à configurer' }}
        </span>
    </div>

    <div class="mp-section">
        <div class="mp-section-head">
            <div class="section-icon mailpulse"><i class="fas fa-shield-alt"></i></div>
            <div>
                <h3 class="section-title">Envois protégés</h3>
                <p class="section-description">Les tests n’utilisent que les destinataires de test. La clé API se configure dans les paramètres système.</p>
            </div>
        </div>
        <div class="mailpulse-info-grid">
            <div class="mailpulse-info-card">
                <strong><i class="fas fa-address-card"></i>Contact parent test</strong>
                KLASSCI crée ou met à jour le contact MailPulse avant chaque notification simulée.
            </div>
            <div class="mailpulse-info-card">
                <strong><i class="fas fa-shield-alt"></i>Destinataires protégés</strong>
                Les tests utilisent uniquement l’email et le téléphone de test configurés.
            </div>
            <div class="mailpulse-info-card">
                <strong><i class="fab fa-whatsapp"></i>Limite WhatsApp</strong>
                Meta peut exiger un template approuvé hors fenêtre 24h.
            </div>
        </div>
    </div>

    @can('mailpulse.send')
    <div class="mp-section">
        <div class="mp-section-head">
            <div class="section-icon mailpulse"><i class="fas fa-vial"></i></div>
            <div>
                <h3 class="section-title">Test direct MailPulse</h3>
                <p class="section-description">Même test que dans les paramètres, sans exposer la configuration serveur.</p>
            </div>
        </div>
        <div class="mailpulse-field-card">
            @csrf
            <div class="mailpulse-test-grid">
                <div>
                    <label class="form-label-modern"><i class="fas fa-calendar-check"></i>Événement simulé</label>
                    <select class="form-control form-control-modern" data-mailpulse-test-event>
                        <option value="payment_received">Paiement reçu</option>
                        <option value="payment_submitted">Paiement en attente</option>
                        <option value="payment_rejected">Paiement rejeté</option>
                        <option value="absence_reported">Absence signalée</option>
                        <option value="grade_published">Note publiée</option>
                        <option value="fee_reminder" selected>Rappel de frais</option>
                        <option value="registration_confirmed">Inscription confirmée</option>
                        <option value="re_registration_confirmed">Réinscription confirmée</option>
                        <option value="bulletin_published">Bulletin disponible</option>
                        <option value="low_grades_alert">Alerte notes faibles</option>
                        <option value="low_attendance_alert">Alerte présence faible</option>
                    </select>
                </div>
                <div>
                    <label class="form-label-modern"><i class="fas fa-paper-plane"></i>Canal</label>
                    <select class="form-control form-control-modern" data-mailpulse-test-channel>
                        <option value="email">Email</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="both">Email et WhatsApp</option>
                    </select>
                </div>
                <div>
                    <label class="form-label-modern"><i class="fas fa-shield-alt"></i>Mode</label>
                    <label class="mailpulse-toggle mb-0">
                        <input type="checkbox" data-mailpulse-test-dry-run checked>
                        <span class="mailpulse-toggle-slider"></span>
                        Simulation uniquement
                    </label>
                </div>
                <button type="button" class="btn-acasi primary" data-mailpulse-test-submit>
                    <i class="fas fa-play me-2"></i>Lancer le test
                </button>
            </div>
            <small class="text-muted d-block mt-3">La simulation ne contacte pas de vrais parents. Décochez seulement si des destinataires de test sont configurés.</small>
            <div class="mailpulse-test-result d-none" data-mailpulse-test-result></div>
        </div>
    </div>
    @endcan

</div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const testButton = document.querySelector('[data-mailpulse-test-submit]');
    const resultBox = document.querySelector('[data-mailpulse-test-result]');
    if (!testButton || !resultBox) return;
    const csrf = document.querySelector('input[name=_token]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.content;
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    const channelLabel = (channel) => {
        if (!channel || !channel.attempted) return (channel && (channel.message || channel.status)) || 'Ignoré';
        if (channel.ok === false) return channel.message || channel.action || channel.status || 'Erreur';
        return channel.message || channel.status || 'Envoyé';
    };
    testButton.addEventListener('click', async () => {
        testButton.disabled = true;
        const res = await fetch(@json(route('esbtp.communication.mailpulse.test')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                event: document.querySelector('[data-mailpulse-test-event]').value,
                channel: document.querySelector('[data-mailpulse-test-channel]').value,
                dryRun: document.querySelector('[data-mailpulse-test-dry-run]').checked,
            }),
        });
        const payload = await res.json().catch(() => ({ message: 'Réponse invalide' }));
        const ok = res.ok && payload.ok !== false;
        resultBox.classList.remove('d-none', 'is-success', 'is-error');
        resultBox.classList.add(ok ? 'is-success' : 'is-error');
        const email = payload.email || {};
        const whatsapp = payload.whatsapp || {};
        resultBox.innerHTML = `
            <div><strong><i class="fas ${ok ? 'fa-check-circle' : 'fa-triangle-exclamation'} me-2"></i>${escapeHtml(payload.message || (ok ? 'Test MailPulse terminé.' : 'Le test MailPulse a échoué.'))}</strong></div>
            <div class="mailpulse-test-kv">
                <span><strong>Contact</strong>${escapeHtml(payload.contactId || payload.contact?.status || 'Non créé')}</span>
                <span><strong>Email</strong>${escapeHtml(channelLabel(email))}</span>
                <span><strong>WhatsApp</strong>${escapeHtml(channelLabel(whatsapp))}</span>
            </div>
            ${payload.errors ? `<pre class="mt-3 mb-0">${escapeHtml(JSON.stringify(payload.errors, null, 2))}</pre>` : ''}
        `;
        testButton.disabled = false;
    });
})();
</script>
@endpush
