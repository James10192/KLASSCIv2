{{--
    Composant : Compte à rebours expiration contrat
    Affiché uniquement si session('contract_expiry_should_show') === true
    Se ferme via AJAX POST /contract-expiry/dismiss
--}}
@php
    $expiry = session('contract_expiry');
    $shouldShow = session('contract_expiry_should_show', false);

    if (!$expiry || !$shouldShow) return;

    $urgency    = $expiry['urgency'];        // green | orange | red | expired
    $days       = $expiry['days_remaining'];
    $endDate    = $expiry['end_date_formatted'] ?? $expiry['end_date'];
    $isExpired  = $expiry['is_expired'];
    $plan       = $expiry['plan'];
    $tenantName = $expiry['tenant_name'];

    // SVG circle : rayon 54, circumférence ≈ 339.3
    $radius = 54;
    $circ   = round(2 * M_PI * $radius, 1); // 339.3
    $ratio  = $isExpired ? 0 : min(1, max(0, $days / 30));
    $dash   = round($circ * $ratio, 1);
    $gap    = $circ - $dash;

    // Icône selon urgence
    $icons = [
        'green'   => 'fa-clock',
        'orange'  => 'fa-exclamation-triangle',
        'red'     => 'fa-fire',
        'expired' => 'fa-ban',
    ];
    $icon = $icons[$urgency] ?? 'fa-clock';

    // Titre header
    $titles = [
        'green'   => 'Renouvellement à planifier',
        'orange'  => 'Contrat bientôt expiré',
        'red'     => 'Expiration imminente !',
        'expired' => 'Contrat expiré',
    ];
    $title = $titles[$urgency] ?? 'Alerte contrat';

    // Message principal
    if ($isExpired) {
        $message = 'Votre abonnement <strong>' . e($plan) . '</strong> a expiré. Contactez-nous pour le renouveler et éviter toute interruption de service.';
    } elseif ($days <= 1) {
        $message = 'Votre abonnement <strong>' . e($plan) . '</strong> expire <strong>demain</strong>. Renouvelez maintenant pour ne pas perdre l\'accès.';
    } else {
        $message = 'Votre abonnement <strong>' . e($plan) . '</strong> expire dans <strong>' . $days . ' jours</strong>. Prenez contact avec nous pour le renouveler sans interruption.';
    }

    // Lien WhatsApp avec message pré-rempli
    $whatsappMsg = urlencode(
        "Bonjour, je souhaite renouveler l'abonnement de {$tenantName} (Plan {$plan}). " .
        ($isExpired ? "Mon contrat a expiré le {$endDate}." : "Mon contrat expire le {$endDate} ({$days} jours restants).")
    );
    $whatsappUrl = "https://wa.me/2250595459843?text={$whatsappMsg}";
@endphp

{{-- CSS chargé une seule fois --}}
<link rel="stylesheet" href="{{ asset('css/contract-expiry.css') }}">

{{-- Bande rouge clignotante si < 7 jours --}}
@if(!$isExpired && $days <= 7)
    <div class="ce-urgency-strip ce-urgency-strip--visible" id="ce-strip">
        ⚠ URGENT — Votre contrat expire dans {{ $days }} jour{{ $days > 1 ? 's' : '' }} — Renouvelez maintenant
    </div>
@endif

{{-- Overlay principal --}}
<div class="ce-overlay" id="contractExpiryModal" role="dialog" aria-modal="true" aria-labelledby="ce-title">
    <div class="ce-card">

        {{-- Header coloré --}}
        <div class="ce-header ce-header--{{ $urgency }}">
            <div class="ce-icon ce-icon--{{ $urgency }}">
                <i class="fas {{ $icon }}"></i>
            </div>
            <p class="ce-header-title" id="ce-title">{{ $title }}</p>
            <p class="ce-header-sub">{{ $tenantName }}</p>
        </div>

        {{-- Corps --}}
        <div class="ce-body">

            {{-- Compte à rebours SVG --}}
            <div class="ce-countdown ce-countdown--{{ $urgency }}" aria-label="{{ $days }} jours restants">
                <svg width="140" height="140" viewBox="0 0 140 140">
                    {{-- Piste de fond --}}
                    <circle
                        class="ce-track"
                        cx="70" cy="70" r="{{ $radius }}"
                        fill="none"
                        stroke-width="10"
                        stroke-linecap="round"
                    />
                    {{-- Arc de progression --}}
                    <circle
                        class="ce-progress"
                        cx="70" cy="70" r="{{ $radius }}"
                        fill="none"
                        stroke-width="10"
                        stroke-linecap="round"
                        stroke-dasharray="{{ $dash }} {{ $gap }}"
                    />
                </svg>
                <div class="ce-countdown-inner">
                    @if($isExpired)
                        <span class="ce-days-number">Expiré</span>
                    @else
                        <span class="ce-days-number">{{ $days }}</span>
                        <span class="ce-days-label">{{ $days > 1 ? 'jours' : 'jour' }}</span>
                    @endif
                </div>
            </div>

            {{-- Message --}}
            <p class="ce-message">{!! $message !!}</p>

            @if($endDate)
                <p class="ce-date">
                    Date d'expiration : <strong>{{ $endDate }}</strong>
                </p>
            @endif

            {{-- Actions --}}
            <div class="ce-actions">
                <a href="{{ $whatsappUrl }}"
                   target="_blank"
                   rel="noopener"
                   class="ce-btn-primary ce-btn-primary--whatsapp"
                   onclick="ceDismissAndNavigate(this)"
                >
                    <i class="fab fa-whatsapp" style="font-size:1.1rem;"></i>
                    Contacter le support WhatsApp
                </a>

                @if(!$isExpired)
                    <button type="button"
                            class="ce-btn-secondary"
                            id="ceContinueBtn"
                            onclick="ceDismiss()"
                    >
                        Continuer vers l'application
                        <span id="ceCountSpan" style="margin-left:4px; opacity:0.6; font-size:0.8em;"></span>
                    </button>
                @else
                    <button type="button"
                            class="ce-btn-secondary"
                            id="ceContinueBtn"
                            onclick="ceDismiss()"
                            disabled
                    >
                        Accès restreint — Renouvelez pour continuer
                    </button>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="ce-footer">
            <p class="ce-footer-text">
                Ce message sera à nouveau affiché dans 12 heures.
                &nbsp;·&nbsp; Plan actuel : <strong>{{ ucfirst($plan) }}</strong>
            </p>
        </div>

    </div>{{-- /.ce-card --}}
</div>{{-- /.ce-overlay --}}

<script>
(function () {
    'use strict';

    var DISMISS_URL = '{{ route('contract-expiry.dismiss') }}';
    var IS_EXPIRED  = {{ $isExpired ? 'true' : 'false' }};

    /* ── Compte à rebours avant d'autoriser "Continuer" (5 secondes) ── */
    var continueBtn = document.getElementById('ceContinueBtn');
    var countSpan   = document.getElementById('ceCountSpan');

    if (continueBtn && !IS_EXPIRED) {
        var sec = 5;
        continueBtn.disabled = true;
        countSpan.textContent = '(' + sec + 's)';

        var timer = setInterval(function () {
            sec--;
            if (sec <= 0) {
                clearInterval(timer);
                continueBtn.disabled = false;
                countSpan.textContent = '';
            } else {
                countSpan.textContent = '(' + sec + 's)';
            }
        }, 1000);
    }

    /* ── Dismiss via AJAX ── */
    window.ceDismiss = function () {
        var modal = document.getElementById('contractExpiryModal');
        var token = document.querySelector('meta[name="csrf-token"]')
                    ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    : '';

        fetch(DISMISS_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: JSON.stringify({}),
        })
        .then(function () {
            // Fermer le modal avec animation
            modal.style.animation = 'ce-overlay-out 0.25s ease forwards';
            setTimeout(function () { modal.remove(); }, 280);

            // Supprimer la bande urgence si présente
            var strip = document.getElementById('ce-strip');
            if (strip) strip.remove();
        })
        .catch(function () {
            // En cas d'erreur réseau : fermer quand même
            modal.remove();
        });
    };

    /* ── Dismiss + navigation WhatsApp ── */
    window.ceDismissAndNavigate = function (el) {
        var token = document.querySelector('meta[name="csrf-token"]')
                    ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    : '';
        fetch(DISMISS_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: JSON.stringify({}),
        }).catch(function(){});
        // Laisser le navigateur ouvrir le lien normalement
    };

    /* ── CSS animation out ── */
    var style = document.createElement('style');
    style.textContent = '@keyframes ce-overlay-out{to{opacity:0;}}';
    document.head.appendChild(style);

    /* ── Empêcher fermeture en cliquant sur l'overlay si pas expiré ── */
    var overlay = document.getElementById('contractExpiryModal');
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay && !IS_EXPIRED) {
                // Petit shake pour indiquer que c'est bloquant
                var card = overlay.querySelector('.ce-card');
                card.style.animation = 'none';
                card.style.transform = 'translateX(-8px)';
                setTimeout(function() {
                    card.style.transition = 'transform 0.3s ease';
                    card.style.transform = 'translateX(0)';
                }, 50);
            }
        });
    }

    /* ── Mobile bottom-sheet : swipe vers le bas pour fermer (forme uniquement) ──
       Reprend EXACTEMENT la même règle que le clic overlay : autorisé seulement
       si le contrat n'est pas expiré. Ne change ni la condition d'affichage ni
       la fréquence (12h). Actif sous 768px uniquement. */
    var card = overlay ? overlay.querySelector('.ce-card') : null;
    if (card && window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches) {
        var startY = null;
        var deltaY = 0;
        var dragging = false;

        card.addEventListener('touchstart', function (e) {
            // On ne démarre le drag que si le contenu est en haut (pas de scroll interne en cours).
            if (card.scrollTop > 0) { startY = null; return; }
            startY = e.touches[0].clientY;
            deltaY = 0;
            dragging = true;
            card.style.transition = 'none';
        }, { passive: true });

        card.addEventListener('touchmove', function (e) {
            if (!dragging || startY === null) return;
            deltaY = e.touches[0].clientY - startY;
            if (deltaY > 0) {
                card.style.transform = 'translateY(' + deltaY + 'px)';
            }
        }, { passive: true });

        card.addEventListener('touchend', function () {
            if (!dragging) return;
            dragging = false;
            card.style.transition = 'transform 0.25s ease';

            // Seuil de fermeture : 110px de glissement vers le bas.
            if (deltaY > 110 && !IS_EXPIRED) {
                card.style.transform = 'translateY(100%)';
                setTimeout(function () { window.ceDismiss(); }, 200);
            } else {
                // Retour en place (et rappel discret du blocage si expiré).
                card.style.transform = 'translateY(0)';
            }
            startY = null;
            deltaY = 0;
        });
    }
})();
</script>
