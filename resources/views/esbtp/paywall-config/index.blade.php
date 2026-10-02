@extends('layouts.app')

@section('title', 'Abonnement de l\'instance - KLASSCI')

@push('styles')
@include('esbtp.paywall-config.partials._styles')
@endpush

@section('content')
@php
    $modeSecours = ! $etat['master_configure'];
@endphp
<div class="pwc" id="pwc-page"
     data-refresh-url="{{ route('esbtp.paywall-config.refresh') }}"
     data-store-url="{{ route('esbtp.paywall-config.store') }}"
     data-extend-url="{{ route('esbtp.paywall-config.extend') }}"
     data-emergency-url="{{ route('esbtp.paywall-config.generate-emergency') }}"
     data-mode-secours="{{ $modeSecours ? '1' : '0' }}">

    <div id="pwc-etat">
        @include('esbtp.paywall-config.partials._etat', ['etat' => $etat])
    </div>

    <div class="pwc-section-title">
        <div class="pwc-section-icon"><i class="fas fa-sliders"></i></div>
        <div>
            <h2>Sur cette instance</h2>
            <span>Ce qui reste réglé ici, et non dans adminKlassci</span>
        </div>
    </div>

    <div class="pwc-card">
        <div class="pwc-row">
            <div class="pwc-row-text">
                <h3>Appliquer le paywall</h3>
                <p>
                    Activé, l'école est bloquée quand son abonnement expire ou qu'une limite est dépassée.
                    Désactivé, rien n'est bloqué, mais les limites restent affichées ci-dessus.
                </p>
            </div>
            <label class="pwc-switch">
                <input type="checkbox" id="paywall_active" name="is_active" value="1" {{ $reglagesLocaux['is_active'] ? 'checked' : '' }}>
                <span class="pwc-switch-track"></span>
                <span id="pwc-switch-label">{{ $reglagesLocaux['is_active'] ? 'Appliqué' : 'Non appliqué' }}</span>
            </label>
        </div>
    </div>

    @if($modeSecours)
        <div class="pwc-card" style="margin-top:.9rem">
            <div class="pwc-row-text">
                <h3>Réglages locaux de secours</h3>
                <p>
                    adminKlassci n'étant pas configuré, le plan, l'échéance et les limites se règlent ici.
                    Dès que l'instance lit sa fiche, ces champs disparaissent : la fiche fait foi.
                </p>
            </div>
            <form id="paywallConfigForm" novalidate>
                <div class="pwc-form-grid">
                    <div class="pwc-field">
                        <label for="plan_name">Nom du plan</label>
                        <input type="text" id="plan_name" name="plan_name" value="{{ $reglagesLocaux['plan_name'] }}" maxlength="255" required>
                    </div>
                    <div class="pwc-field">
                        <label for="plan_price">Tarif mensuel (FCFA)</label>
                        <input type="number" id="plan_price" name="plan_price" value="{{ $reglagesLocaux['plan_price'] }}" min="0" step="1000" required>
                    </div>
                    <div class="pwc-field">
                        <label for="subscription_end">Date de fin</label>
                        <input type="date" id="subscription_end" name="subscription_end" value="{{ $reglagesLocaux['subscription_end'] }}">
                        <small>Vide : pas d'échéance.</small>
                    </div>
                    <div class="pwc-field">
                        <label for="max_users">Limite d'utilisateurs</label>
                        <input type="number" id="max_users" name="max_users" value="{{ $reglagesLocaux['max_users'] }}" min="1" required>
                        <small>Enseignants, coordinateurs et secrétaires.</small>
                    </div>
                    <div class="pwc-field">
                        <label for="max_inscriptions_per_year">Limite d'inscriptions par année</label>
                        <input type="number" id="max_inscriptions_per_year" name="max_inscriptions_per_year" value="{{ $reglagesLocaux['max_inscriptions_per_year'] }}" min="1" required>
                        <small>Inscriptions actives de l'année courante.</small>
                    </div>
                </div>
                <div class="pwc-actions">
                    <button type="submit" class="pwc-btn pwc-btn--primary"><i class="fas fa-floppy-disk"></i><span>Enregistrer</span></button>
                    @if($reglagesLocaux['subscription_end'])
                        <button type="button" class="pwc-btn pwc-btn--ghost" data-pwc-action="ouvrir-prolongation">
                            <i class="fas fa-calendar-plus"></i><span>Prolonger l'abonnement</span>
                        </button>
                    @endif
                </div>
            </form>
        </div>
    @endif

    <div class="pwc-card" style="margin-top:.9rem">
        <div class="pwc-row">
            <div class="pwc-row-text">
                <h3>Accès d'urgence</h3>
                <p>Un lien à usage unique qui ouvre l'école pendant une heure, même bloquée. À réserver aux urgences.</p>
            </div>
            <button type="button" class="pwc-btn pwc-btn--ghost" data-pwc-action="generer-code">
                <i class="fas fa-key"></i><span>Générer un code</span>
            </button>
        </div>

        <div id="emergencyCodeSection" class="pwc-code" style="display:none">
            <div class="pwc-code-line">
                <strong>Code</strong>
                <span id="emergencyCodeDisplay" class="pwc-mono"></span>
                <button type="button" class="pwc-btn pwc-btn--ghost" data-pwc-action="copier" data-cible="emergencyCodeDisplay"><i class="fas fa-copy"></i><span>Copier</span></button>
            </div>
            <div class="pwc-code-line">
                <strong>Lien</strong>
                <span id="emergencyUrlDisplay" class="pwc-mono"></span>
                <button type="button" class="pwc-btn pwc-btn--ghost" data-pwc-action="copier" data-cible="emergencyUrlDisplay"><i class="fas fa-copy"></i><span>Copier le lien</span></button>
            </div>
            <div class="pwc-usage-foot">Valable une heure, une seule utilisation.</div>
        </div>

        @if($codesActifs->isNotEmpty())
            <ul class="pwc-list" id="pwc-codes-actifs">
                @foreach($codesActifs as $code)
                    <li>
                        <span class="pwc-mono">{{ $code->code }}</span>
                        <span>par {{ $code->created_by }} · expire à {{ $code->expires_at->format('H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="pwc-empty" id="pwc-codes-actifs">Aucun code en cours de validité.</p>
        @endif
    </div>

    @if($modeSecours)
        <div class="pwc-modal-backdrop" id="extendModal" style="display:none" role="dialog" aria-modal="true" aria-labelledby="pwc-extend-title">
            <div class="pwc-modal">
                <h3 id="pwc-extend-title">Prolonger l'abonnement</h3>
                <form id="extendForm">
                    <div class="pwc-field">
                        <label for="pwc-months">Durée</label>
                        <x-au-select id="pwc-months" name="months" value="12" :placeholder-is-first-option="false"
                            :options="['1' => '1 mois', '3' => '3 mois', '6' => '6 mois', '12' => '12 mois', '24' => '24 mois']" />
                    </div>
                    <div class="pwc-actions">
                        <button type="submit" class="pwc-btn pwc-btn--primary"><i class="fas fa-check"></i><span>Prolonger</span></button>
                        <button type="button" class="pwc-btn pwc-btn--ghost" data-pwc-action="fermer-prolongation"><span>Annuler</span></button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
@include('partials._klassci_toast')
<script>
(function () {
    const page = document.getElementById('pwc-page');
    if (!page) { return; }
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const secours = page.dataset.modeSecours === '1';
    const toast = (type, message) => (window.klassciToast ? window.klassciToast(type, message) : alert(message));

    async function poster(url, corps) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(corps || {}),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            const premiere = data.errors ? Object.values(data.errors)[0] : null;
            throw new Error((premiere && premiere[0]) || data.message || ('Erreur ' + res.status));
        }
        return data;
    }

    function valeursLocales() {
        const form = document.getElementById('paywallConfigForm');
        if (!form) { return {}; }
        return {
            plan_name: form.plan_name.value,
            plan_price: form.plan_price.value,
            subscription_end: form.subscription_end.value || null,
            max_users: form.max_users.value,
            max_inscriptions_per_year: form.max_inscriptions_per_year.value,
        };
    }

    async function actualiser(bouton) {
        if (bouton) { bouton.disabled = true; }
        try {
            const data = await poster(page.dataset.refreshUrl);
            document.getElementById('pwc-etat').innerHTML = data.html;
            toast(data.source === 'master' ? 'success' : 'warning', data.message);
        } catch (e) {
            toast('error', e.message);
            if (bouton) { bouton.disabled = false; }
        }
    }

    const interrupteur = document.getElementById('paywall_active');
    interrupteur.addEventListener('change', async function () {
        const actif = interrupteur.checked;
        interrupteur.disabled = true;
        try {
            const data = await poster(page.dataset.storeUrl, Object.assign(secours ? valeursLocales() : {}, { is_active: actif }));
            document.getElementById('pwc-switch-label').textContent = actif ? 'Appliqué' : 'Non appliqué';
            toast('success', data.message);
            actualiser(null);
        } catch (e) {
            interrupteur.checked = !actif;
            toast('error', e.message);
        } finally {
            interrupteur.disabled = false;
        }
    });

    const form = document.getElementById('paywallConfigForm');
    if (form) {
        form.addEventListener('submit', async function (ev) {
            ev.preventDefault();
            const bouton = form.querySelector('button[type="submit"]');
            bouton.disabled = true;
            try {
                const data = await poster(page.dataset.storeUrl, Object.assign(valeursLocales(), { is_active: interrupteur.checked }));
                toast('success', data.message);
                actualiser(null);
            } catch (e) {
                toast('error', e.message);
            } finally {
                bouton.disabled = false;
            }
        });
    }

    const modale = document.getElementById('extendModal');
    const formProlongation = document.getElementById('extendForm');
    if (formProlongation) {
        formProlongation.addEventListener('submit', async function (ev) {
            ev.preventDefault();
            try {
                const data = await poster(page.dataset.extendUrl, { months: formProlongation.months.value });
                const champ = document.getElementById('subscription_end');
                if (champ && data.new_end_date) { champ.value = data.new_end_date; }
                modale.style.display = 'none';
                toast('success', data.message);
                actualiser(null);
            } catch (e) {
                toast('error', e.message);
            }
        });
    }

    async function genererCode(bouton) {
        if (!confirm('Générer un code d\'accès d\'urgence valable une heure ?')) { return; }
        bouton.disabled = true;
        try {
            const data = await poster(page.dataset.emergencyUrl);
            document.getElementById('emergencyCodeDisplay').textContent = data.code;
            document.getElementById('emergencyUrlDisplay').textContent = data.url;
            document.getElementById('emergencyCodeSection').style.display = 'block';
            toast('success', data.message);
        } catch (e) {
            toast('error', e.message);
        } finally {
            bouton.disabled = false;
        }
    }

    function copier(idCible) {
        const texte = document.getElementById(idCible).textContent;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(texte).then(() => toast('success', 'Copié.'), () => toast('error', 'Copie impossible.'));
        }
    }

    // Delegation : le bloc d'etat est remplace a chaque actualisation, ses
    // boutons n'ont donc pas d'ecouteur propre.
    page.addEventListener('click', function (ev) {
        const cible = ev.target.closest('[data-pwc-action]');
        if (!cible) { return; }
        const action = cible.dataset.pwcAction;
        if (action === 'actualiser') { actualiser(cible); }
        if (action === 'generer-code') { genererCode(cible); }
        if (action === 'copier') { copier(cible.dataset.cible); }
        if (action === 'ouvrir-prolongation' && modale) { modale.style.display = 'flex'; }
        if (action === 'fermer-prolongation' && modale) { modale.style.display = 'none'; }
    });

    if (modale) {
        modale.addEventListener('click', function (ev) { if (ev.target === modale) { modale.style.display = 'none'; } });
        document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') { modale.style.display = 'none'; } });
    }
})();
</script>
@endpush
