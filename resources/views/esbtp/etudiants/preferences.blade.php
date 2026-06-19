@extends('layouts.app')

@section('title', 'Préférences')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ============================================================
   PAGE PRÉFÉRENCES ÉTUDIANT — PWA — Design Premium KLASSCI
   Namespace dédié : pref-*
   ============================================================ */

.pref-page {
    max-width: 760px;
    margin: 0 auto;
    padding-bottom: 2rem;
}

/* --- Hero --- */
.pref-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #5e91de 100%);
    border-radius: 18px;
    padding: 1.75rem 1.75rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4, 83, 203, .18);
}

.pref-hero-top {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.pref-hero-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .18);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
    color: #fff;
}

.pref-hero h1 {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0;
    color: #fff;
}

.pref-hero p {
    margin: .15rem 0 0;
    font-size: .85rem;
    color: rgba(255, 255, 255, .72);
}

/* --- Cartes --- */
.pref-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 1px 2px rgba(15, 23, 42, .06);
    padding: 1.25rem 1.35rem;
    margin-bottom: 1rem;
}

.pref-card-head {
    display: flex;
    align-items: flex-start;
    gap: .85rem;
    margin-bottom: .35rem;
}

.pref-card-icon {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1rem;
    flex-shrink: 0;
}

.pref-card-title {
    font-size: 1.02rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    line-height: 1.25;
}

.pref-card-sub {
    font-size: .82rem;
    color: #64748b;
    margin: .15rem 0 0;
}

.pref-card-body {
    margin-top: .9rem;
}

/* --- Ligne toggle --- */
.pref-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: .85rem 0;
}

.pref-row + .pref-row {
    border-top: 1px solid #f1f5f9;
}

.pref-row-label {
    font-size: .9rem;
    font-weight: 600;
    color: #1e293b;
}

.pref-row-hint {
    font-size: .78rem;
    color: #64748b;
    margin-top: .1rem;
}

/* --- Switch premium --- */
.pref-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
    width: 52px;
    height: 30px;
    cursor: pointer;
}

.pref-switch input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.pref-switch-track {
    position: absolute;
    inset: 0;
    background: #cbd5e1;
    border-radius: 999px;
    transition: background .2s ease;
}

.pref-switch-thumb {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 24px;
    height: 24px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .25);
    transition: transform .2s ease;
}

.pref-switch input:checked ~ .pref-switch-track {
    background: #10b981;
}

.pref-switch input:checked ~ .pref-switch-thumb {
    transform: translateX(22px);
}

.pref-switch input:disabled ~ .pref-switch-track {
    opacity: .55;
    cursor: wait;
}

/* --- Badge état --- */
.pref-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    font-size: .72rem;
    font-weight: 700;
    padding: .22rem .6rem;
    border-radius: 999px;
    letter-spacing: .2px;
}

.pref-badge--on {
    background: rgba(16, 185, 129, .12);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, .25);
}

.pref-badge--off {
    background: rgba(100, 116, 139, .1);
    color: #475569;
    border: 1px solid rgba(100, 116, 139, .2);
}

/* --- Note d'info / dégradation gracieuse --- */
.pref-note {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    background: rgba(245, 158, 11, .08);
    border: 1px solid rgba(245, 158, 11, .25);
    border-radius: 11px;
    padding: .8rem .9rem;
    font-size: .82rem;
    color: #92400e;
    line-height: 1.45;
}

.pref-note i {
    color: #f59e0b;
    margin-top: .15rem;
    flex-shrink: 0;
}

.pref-note--info {
    background: rgba(4, 83, 203, .06);
    border-color: rgba(4, 83, 203, .18);
    color: #1e3a8a;
}

.pref-note--info i {
    color: #0453cb;
}

/* --- Boutons --- */
.pref-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    min-height: 46px;
    padding: .65rem 1.2rem;
    border-radius: 11px;
    border: none;
    font-size: .9rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s ease, box-shadow .15s ease, opacity .15s ease;
}

.pref-btn--primary {
    background: #0453cb;
    color: #fff;
}

.pref-btn--primary:hover:not(:disabled) {
    background: #033a8e;
    box-shadow: 0 6px 18px rgba(4, 83, 203, .25);
}

.pref-btn--ghost {
    background: #f1f5f9;
    color: #0453cb;
}

.pref-btn--ghost:hover:not(:disabled) {
    background: #e2e8f0;
}

.pref-btn:disabled {
    opacity: .6;
    cursor: wait;
}

.pref-btn--block {
    width: 100%;
}

/* --- Instructions installation --- */
.pref-steps {
    margin: .6rem 0 0;
    padding-left: 1.15rem;
    font-size: .85rem;
    color: #334155;
    line-height: 1.6;
}

.pref-steps li {
    margin-bottom: .25rem;
}

.pref-kbd {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: .05rem .4rem;
    font-size: .8rem;
    font-weight: 600;
    color: #0453cb;
}

/* --- Toast --- */
.pref-toasts {
    position: fixed;
    left: 50%;
    bottom: calc(env(safe-area-inset-bottom, 0px) + 84px);
    transform: translateX(-50%);
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: .5rem;
    width: calc(100% - 2rem);
    max-width: 420px;
    pointer-events: none;
}

.pref-toast {
    pointer-events: auto;
    display: flex;
    align-items: center;
    gap: .6rem;
    background: #0f172a;
    color: #fff;
    border-radius: 12px;
    padding: .75rem .95rem;
    font-size: .85rem;
    box-shadow: 0 10px 30px rgba(15, 23, 42, .3);
}

.pref-toast--success { background: #065f46; }
.pref-toast--error { background: #991b1b; }
.pref-toast--info { background: #0f172a; }

.pref-toast i { font-size: 1rem; flex-shrink: 0; }

[x-cloak] { display: none !important; }

@@media (max-width: 768px) {
    .pref-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .pref-hero h1 { font-size: 1.2rem; }
    .pref-card { padding: 1.1rem 1.1rem; }
}
</style>
@endpush

@section('content')
<div class="pref-page"
     x-data="studentPreferences()"
     x-init="init()"
     data-vapid-public-key="{{ $vapidPublicKey }}"
     data-has-subscription="{{ $hasPushSubscription ? '1' : '0' }}"
     data-subscribe-url="{{ route('esbtp.push.subscribe') }}"
     data-unsubscribe-url="{{ route('esbtp.push.unsubscribe') }}">

    {{-- Hero --}}
    <div class="pref-hero">
        <div class="pref-hero-top">
            <div class="pref-hero-icon"><i class="fas fa-sliders"></i></div>
            <div>
                <h1>Préférences</h1>
                <p>Gérez vos notifications et installez l'application sur votre appareil.</p>
            </div>
        </div>
    </div>

    {{-- Carte Notifications push --}}
    <div class="pref-card">
        <div class="pref-card-head">
            <div class="pref-card-icon"><i class="fas fa-bell"></i></div>
            <div style="flex:1;">
                <p class="pref-card-title">Notifications push</p>
                <p class="pref-card-sub">Recevez vos notes, bulletins et annonces directement sur cet appareil.</p>
            </div>
            <span class="pref-badge"
                  :class="pushEnabled ? 'pref-badge--on' : 'pref-badge--off'"
                  x-show="pushSupported"
                  x-cloak>
                <i class="fas" :class="pushEnabled ? 'fa-check' : 'fa-minus'"></i>
                <span x-text="pushEnabled ? 'Activées' : 'Désactivées'"></span>
            </span>
        </div>

        <div class="pref-card-body">
            {{-- Toggle (uniquement si push supporté) --}}
            <div class="pref-row" x-show="pushSupported" x-cloak>
                <div>
                    <div class="pref-row-label">Activer les notifications</div>
                    <div class="pref-row-hint">Vous pouvez les désactiver à tout moment.</div>
                </div>
                <label class="pref-switch" aria-label="Activer les notifications push">
                    <input type="checkbox"
                           :checked="pushEnabled"
                           :disabled="pushBusy"
                           @change.prevent="togglePush($event)">
                    <span class="pref-switch-track"></span>
                    <span class="pref-switch-thumb"></span>
                </label>
            </div>

            {{-- Dégradation gracieuse : push non supporté (iOS Safari non installé, etc.) --}}
            <div class="pref-note" x-show="!pushSupported" x-cloak>
                <i class="fas fa-circle-info"></i>
                <div>
                    Les notifications push ne sont pas disponibles dans ce navigateur.
                    Sur iPhone et iPad, installez d'abord l'application sur l'écran d'accueil
                    (voir ci-dessous), puis revenez activer les notifications.
                </div>
            </div>
        </div>
    </div>

    {{-- Carte Installer l'application --}}
    <div class="pref-card">
        <div class="pref-card-head">
            <div class="pref-card-icon"><i class="fas fa-mobile-screen-button"></i></div>
            <div style="flex:1;">
                <p class="pref-card-title">Installer l'application</p>
                <p class="pref-card-sub">Accès rapide depuis votre écran d'accueil, même hors connexion.</p>
            </div>
        </div>

        <div class="pref-card-body">
            {{-- Déjà installée --}}
            <div class="pref-note pref-note--info" x-show="isInstalled" x-cloak>
                <i class="fas fa-circle-check"></i>
                <div>L'application est déjà installée sur cet appareil.</div>
            </div>

            {{-- Bouton install natif (Android / Chrome desktop) --}}
            <button type="button"
                    class="pref-btn pref-btn--primary pref-btn--block"
                    x-show="!isInstalled && canInstall"
                    x-cloak
                    @click="promptInstall()">
                <i class="fas fa-download"></i>
                Installer maintenant
            </button>

            {{-- Instructions manuelles (iOS, ou navigateur sans prompt) --}}
            <div x-show="!isInstalled && !canInstall" x-cloak>
                <div class="pref-note pref-note--info" style="margin-bottom:.75rem;">
                    <i class="fas fa-circle-info"></i>
                    <div>Pour installer l'application, suivez les étapes selon votre appareil.</div>
                </div>

                <div class="pref-row-label" style="margin-bottom:.2rem;">
                    <i class="fab fa-apple"></i> iPhone / iPad (Safari)
                </div>
                <ol class="pref-steps">
                    <li>Touchez le bouton <span class="pref-kbd"><i class="fas fa-arrow-up-from-bracket"></i> Partager</span></li>
                    <li>Choisissez <span class="pref-kbd">Sur l'écran d'accueil</span></li>
                    <li>Validez avec <span class="pref-kbd">Ajouter</span></li>
                </ol>

                <div class="pref-row-label" style="margin:.75rem 0 .2rem;">
                    <i class="fab fa-android"></i> Android (Chrome)
                </div>
                <ol class="pref-steps">
                    <li>Ouvrez le menu <span class="pref-kbd"><i class="fas fa-ellipsis-vertical"></i></span></li>
                    <li>Choisissez <span class="pref-kbd">Installer l'application</span> ou <span class="pref-kbd">Ajouter à l'écran d'accueil</span></li>
                </ol>
            </div>
        </div>
    </div>

    {{-- Toasts --}}
    <div class="pref-toasts" aria-live="polite" aria-atomic="true">
        <template x-for="t in toasts" :key="t.id">
            <div class="pref-toast" :class="'pref-toast--' + t.type" x-transition.opacity>
                <i class="fas"
                   :class="t.type === 'success' ? 'fa-circle-check' : (t.type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info')"></i>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
if (typeof window.studentPreferences !== 'function') {
    window.studentPreferences = function () {
        return {
            // Etat
            pushSupported: false,
            pushEnabled: false,
            pushBusy: false,
            canInstall: false,
            isInstalled: false,
            deferredPrompt: null,
            toasts: [],
            _toastSeq: 0,
            // Config (depuis data-*)
            vapidPublicKey: '',
            subscribeUrl: '',
            unsubscribeUrl: '',

            init() {
                const el = this.$root;
                this.vapidPublicKey = el.dataset.vapidPublicKey || '';
                this.subscribeUrl = el.dataset.subscribeUrl || '';
                this.unsubscribeUrl = el.dataset.unsubscribeUrl || '';

                // Support push : SW + PushManager + Notification + clé VAPID présente
                this.pushSupported = ('serviceWorker' in navigator)
                    && ('PushManager' in window)
                    && ('Notification' in window)
                    && this.vapidPublicKey.length > 0;

                // Etat d'abonnement réel côté navigateur (prime sur le snapshot serveur)
                if (this.pushSupported) {
                    this.refreshSubscriptionState();
                } else {
                    this.pushEnabled = (el.dataset.hasSubscription === '1');
                }

                // Détection installation (standalone)
                this.isInstalled = window.matchMedia('(display-mode: standalone)').matches
                    || window.navigator.standalone === true;

                // Prompt d'installation natif (Android / Chrome desktop)
                window.addEventListener('beforeinstallprompt', this._onBeforeInstall = (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;
                    this.canInstall = true;
                });

                window.addEventListener('appinstalled', this._onAppInstalled = () => {
                    this.isInstalled = true;
                    this.canInstall = false;
                    this.deferredPrompt = null;
                    this.toast('success', 'Application installée.');
                });
            },

            destroy() {
                if (this._onBeforeInstall) window.removeEventListener('beforeinstallprompt', this._onBeforeInstall);
                if (this._onAppInstalled) window.removeEventListener('appinstalled', this._onAppInstalled);
            },

            async refreshSubscriptionState() {
                try {
                    const reg = await navigator.serviceWorker.ready;
                    const sub = await reg.pushManager.getSubscription();
                    this.pushEnabled = !!sub;
                } catch (e) {
                    this.pushEnabled = false;
                }
            },

            async togglePush(event) {
                // event.target.checked = état souhaité
                const wantOn = event.target.checked;
                if (this.pushBusy) return;

                if (wantOn) {
                    await this.enablePush();
                } else {
                    await this.disablePush();
                }
                // Resynchronise la case avec l'état réel
                event.target.checked = this.pushEnabled;
            },

            async enablePush() {
                this.pushBusy = true;
                try {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        this.toast('error', 'Autorisation refusée par le navigateur.');
                        return;
                    }

                    const reg = await navigator.serviceWorker.ready;
                    let sub = await reg.pushManager.getSubscription();
                    if (!sub) {
                        sub = await reg.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey),
                        });
                    }

                    const payload = sub.toJSON();
                    const res = await fetch(this.subscribeUrl, {
                        method: 'POST',
                        headers: this.jsonHeaders(),
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            endpoint: payload.endpoint,
                            keys: payload.keys,
                            contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
                        }),
                    });

                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        throw new Error(err.message || 'Erreur HTTP ' + res.status);
                    }

                    this.pushEnabled = true;
                    this.toast('success', 'Notifications activées sur cet appareil.');
                } catch (e) {
                    this.pushEnabled = false;
                    this.toast('error', e.message || "Impossible d'activer les notifications.");
                } finally {
                    this.pushBusy = false;
                }
            },

            async disablePush() {
                this.pushBusy = true;
                try {
                    const reg = await navigator.serviceWorker.ready;
                    const sub = await reg.pushManager.getSubscription();
                    const endpoint = sub ? sub.endpoint : null;

                    if (sub) {
                        await sub.unsubscribe();
                    }

                    if (endpoint) {
                        await fetch(this.unsubscribeUrl, {
                            method: 'POST',
                            headers: this.jsonHeaders(),
                            credentials: 'same-origin',
                            body: JSON.stringify({ endpoint: endpoint }),
                        });
                    }

                    this.pushEnabled = false;
                    this.toast('info', 'Notifications désactivées.');
                } catch (e) {
                    this.toast('error', e.message || 'Impossible de désactiver les notifications.');
                    await this.refreshSubscriptionState();
                } finally {
                    this.pushBusy = false;
                }
            },

            async promptInstall() {
                if (!this.deferredPrompt) {
                    this.toast('info', "Utilisez le menu du navigateur pour installer l'application.");
                    return;
                }
                this.deferredPrompt.prompt();
                try {
                    const choice = await this.deferredPrompt.userChoice;
                    if (choice && choice.outcome === 'accepted') {
                        this.toast('success', "Installation lancée.");
                    }
                } catch (e) {
                    /* ignore */
                } finally {
                    this.deferredPrompt = null;
                    this.canInstall = false;
                }
            },

            // --- Helpers ---
            jsonHeaders() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': meta ? meta.getAttribute('content') : '',
                };
            },

            urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
                const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                const rawData = window.atob(base64);
                const outputArray = new Uint8Array(rawData.length);
                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            },

            toast(type, message) {
                const id = ++this._toastSeq;
                this.toasts.push({ id: id, type: type, message: message });
                setTimeout(() => {
                    this.toasts = this.toasts.filter((t) => t.id !== id);
                }, 4000);
            },
        };
    };
}
</script>
@endpush
