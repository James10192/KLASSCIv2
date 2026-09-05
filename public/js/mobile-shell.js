/* =============================================================
   KLASSCI — Shell mobile (PWA) — comportements, préfixe `m-`
   -------------------------------------------------------------
   Vanilla + Alpine 3 (Alpine.data). Aucun bundler : à charger APRÈS
   Alpine (defer) ou avant — l'enregistrement attend `alpine:init`.
   Idempotent : `window.__mShell` empêche un second chargement.

   Expose :
     Alpine.data('mSheet', id)     feuille montante (x-m.sheet)
     window.mToast(msg, type, ms)  toast sombre (type: success|error|warning|info)
     window.__mShell.refresh()     déclenche manuellement le rafraîchissement PTR

   Mouvement (comme sur iOS) : feuille par transform + courbe cubic-bezier(.32,.72,0,1)
   qui suit le doigt 1:1, se ferme à la vitesse ou à 35 % de sa hauteur, sinon
   revient par ressort ; tirer-pour-rafraîchir à résistance progressive ;
   pastille de la barre d'onglets qui glisse vers l'onglet touché avant la
   navigation (initTabs). prefers-reduced-motion : tout est immédiat.

   Évènements window :
     'm-sheet:open'   {detail:{id}}   ouvre la feuille `id` depuis n'importe où
     'm-sheet:close'  {detail:{id}}   ferme la feuille `id` (sans id : toutes)
     'm-sheet:opened' {detail:{id}}   dès l'affichage (avant la montée du panneau)
     'm-sheet:closed' {detail:{id}}   une fois le panneau redescendu (≈ 460 ms)
     'm-ptr:refresh'  {detail:{el}}   tirer-pour-rafraîchir sur un .m-body
                                      sans data-m-ptr="reload"
     'toast'          {detail:{type,message}}  déjà utilisé dans le projet,
                                      relayé vers mToast quand body.has-m-shell
     'm-online' / 'm-offline'         état réseau
   ============================================================= */
(function () {
    'use strict';
    if (window.__mShell) { return; }

    /* ---------- icônes minimales (mêmes tracés que x-m.icon) ---------- */
    var PATHS = {
        check: 'M20 6L9 17l-5-5',
        alert: 'M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0zM12 9v4M12 17h.01',
        x: 'M18 6L6 18M6 6l12 12',
        wifi: 'M1 9a16 16 0 0 1 22 0M5 13a10 10 0 0 1 14 0M8.5 16.5a5 5 0 0 1 7 0M12 20h.01M2 2l20 20',
        refresh: 'M23 4v6h-6M1 20v-6h6M3.5 9a9 9 0 0 1 14.9-3.4L23 10M1 14l4.6 4.4A9 9 0 0 0 20.5 15',
        chd: 'M6 9l6 6 6-6'
    };
    function svg(name) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' + (PATHS[name] || PATHS.check) + '"/></svg>';
    }
    function hasShell() {
        return !!(document.body && document.body.classList.contains('has-m-shell'));
    }
    function storage(op, key, value) {
        try {
            if (op === 'get') { return window.localStorage.getItem(key); }
            if (op === 'set') { window.localStorage.setItem(key, value); return true; }
            if (op === 'del') { window.localStorage.removeItem(key); return true; }
        } catch (e) { /* navigation privée, quota, contexte bloqué */ }
        return null;
    }

    /* =========================================================
       1. Feuille montante — Alpine.data('mSheet')
       ========================================================= */
    var FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';
    var SHEET_MS = 420;          // = --m-sheet-ms (ouverture / fermeture, courbe iOS)
    var SPRING_MS = 500;         // = --m-spring-ms (retour par ressort)
    var CLOSE_VELOCITY = 0.4;    // px/ms, mesurée sur les 100 derniers ms du glissé
    var CLOSE_RATIO = 0.35;      // ou 35 % de la hauteur du panneau
    var DRAG_START = 6;          // px avant de considérer qu'on glisse
    var RUBBER = 0.25;           // résistance vers le haut

    function reducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }
    function sheetIsCentered() {
        return !!(window.matchMedia && window.matchMedia('(min-width: 768px)').matches);
    }
    function stamp(ev) {
        return (ev && ev.timeStamp) || (window.performance ? window.performance.now() : Date.now());
    }

    /* Le panneau porte son transform en ligne (x-bind:style="dragStyle") : c'est
       la seule source de vérité du mouvement. La racine reçoit .is-open
       (voile) et .is-dragging (voile sans transition) ; le panneau .is-spring
       au retour d'un glissé qui ne ferme pas. Ouverture : la racine est
       affichée (x-show) avec le panneau hors écran, puis, deux images plus
       tard, `shown` passe à vrai et la transition CSS emmène le panneau.
       'm-sheet:opened' part dès l'affichage (le contenu peut se préparer avant
       que le panneau monte) ; 'm-sheet:closed' une fois le panneau redescendu. */
    function mSheet(id) {
        return {
            id: id || null,
            open: false,      // racine affichée (x-show)
            shown: false,     // panneau en place (transition jouée)
            dy: 0,
            dragging: false,
            _startY: null,
            _pointerId: null,
            _samples: [],
            _closeTimer: null,
            _springTimer: null,
            _showToken: null,
            _prevFocus: null,
            _onOpen: null,
            _onClose: null,

            init: function () {
                var self = this;
                this._onOpen = function (ev) {
                    var wanted = ev.detail && ev.detail.id;
                    if (wanted && wanted === self.id) { self.show(); }
                };
                this._onClose = function (ev) {
                    var wanted = ev.detail && ev.detail.id;
                    if (!wanted || wanted === self.id) { self.hide(); }
                };
                window.addEventListener('m-sheet:open', this._onOpen);
                window.addEventListener('m-sheet:close', this._onClose);
            },
            destroy: function () {
                window.removeEventListener('m-sheet:open', this._onOpen);
                window.removeEventListener('m-sheet:close', this._onClose);
                clearTimeout(this._closeTimer);
                clearTimeout(this._springTimer);
                if (this.open) { document.body.classList.remove('m-sheet-open'); }
            },

            get dragStyle() {
                if (sheetIsCentered()) {
                    return { transform: this.shown ? 'none' : 'scale(.96)', opacity: this.shown ? '1' : '0' };
                }
                if (this.dragging) { return { transform: 'translateY(' + this.dy + 'px)' }; }
                return { transform: this.shown ? 'translateY(0)' : 'translateY(100%)' };
            },

            _root: function () {
                var panel = this.$refs.panel;
                return panel && panel.closest ? panel.closest('.m-sheet-root') : null;
            },
            _scrim: function () {
                var root = this._root();
                return root ? root.querySelector('.m-scrim') : null;
            },

            toggle: function () { this.open ? this.hide() : this.show(); },

            show: function () {
                if (this.open && this.shown) { return; }
                var self = this;
                clearTimeout(this._closeTimer);
                this._closeTimer = null;
                if (!this.open) { this._prevFocus = document.activeElement; }
                this.dy = 0;
                this.dragging = false;
                this.open = true;
                var token = this._showToken = {};
                document.body.classList.add('m-sheet-open');
                window.dispatchEvent(new CustomEvent('m-sheet:opened', { detail: { id: this.id } }));
                this.$nextTick(function () {
                    var root = self._root();
                    var panel = self.$refs.panel;
                    if (root) { root.classList.remove('is-dragging'); }
                    if (panel) { panel.classList.remove('is-spring'); void panel.offsetWidth; } // style initial (hors écran) peint avant la transition
                    var reveal = function () {
                        if (!self.open || self._showToken !== token) { return; }
                        self.shown = true;
                        if (root) { root.classList.add('is-open'); }
                    };
                    if (reducedMotion()) { reveal(); }
                    else { requestAnimationFrame(function () { requestAnimationFrame(reveal); }); }
                    if (!panel) { return; }
                    var first = panel.querySelector('[data-m-autofocus]') || panel.querySelector(FOCUSABLE);
                    (first || panel).focus({ preventScroll: true });
                });
            },

            hide: function () {
                if (!this.open) { return; }
                var self = this;
                var root = this._root();
                var scrim = this._scrim();
                var panel = this.$refs.panel;
                this.shown = false;
                this._showToken = null;
                this.dragging = false;
                this.dy = 0;
                this._startY = null;
                this._pointerId = null;
                if (root) { root.classList.remove('is-open', 'is-dragging'); }
                if (scrim) { scrim.style.opacity = ''; }
                if (panel) { panel.classList.remove('is-spring'); }
                document.body.classList.remove('m-sheet-open');
                var prev = this._prevFocus;
                if (prev && typeof prev.focus === 'function' && document.contains(prev)) {
                    try { prev.focus({ preventScroll: true }); } catch (e) { /* noop */ }
                }
                this._prevFocus = null;
                clearTimeout(this._closeTimer);
                this._closeTimer = setTimeout(function () {
                    self._closeTimer = null;
                    self.open = false;
                    window.dispatchEvent(new CustomEvent('m-sheet:closed', { detail: { id: self.id } }));
                }, reducedMotion() ? 0 : SHEET_MS + 40);
            },

            onKeydown: function (ev) {
                if (ev.key === 'Escape') { ev.preventDefault(); this.hide(); return; }
                if (ev.key !== 'Tab') { return; }
                var panel = this.$refs.panel;
                if (!panel) { return; }
                var nodes = Array.prototype.filter.call(panel.querySelectorAll(FOCUSABLE), function (n) {
                    return n.offsetParent !== null || n === document.activeElement;
                });
                if (!nodes.length) { ev.preventDefault(); panel.focus(); return; }
                var first = nodes[0], last = nodes[nodes.length - 1];
                if (ev.shiftKey && (document.activeElement === first || document.activeElement === panel)) {
                    ev.preventDefault(); last.focus();
                } else if (!ev.shiftKey && document.activeElement === last) {
                    ev.preventDefault(); first.focus();
                }
            },

            /* Glisser pour fermer (pointer events). Zone fiable : .m-sheet-drag
               (poignée + en-tête, touch-action:none) ; sur le corps, seulement
               si le panneau n'est pas défilé. Le panneau suit le doigt 1:1, se
               freine vers le haut (rubber-band), et au relâché : fermeture si la
               vitesse dépasse 0,4 px/ms ou le déplacement 35 % de sa hauteur,
               sinon retour par ressort. Le voile suit la progression. */
            onPointerDown: function (ev) {
                if (ev.pointerType === 'mouse' && ev.button !== 0) { return; }
                if (sheetIsCentered() || !this.shown) { return; }
                var target = ev.target;
                if (target.closest && target.closest('input,textarea,select,[contenteditable]')) { return; }
                var panel = this.$refs.panel;
                var inHandle = target.closest && target.closest('.m-sheet-drag');
                if (!inHandle && panel && panel.scrollTop > 0) { return; }
                clearTimeout(this._springTimer);
                if (panel) { panel.classList.remove('is-spring'); }
                this._startY = ev.clientY;
                this._pointerId = ev.pointerId;
                this._samples = [{ t: stamp(ev), y: ev.clientY }];
                this.dragging = false;
            },
            onPointerMove: function (ev) {
                if (this._startY === null || ev.pointerId !== this._pointerId) { return; }
                var raw = ev.clientY - this._startY;
                var t = stamp(ev);
                this._samples.push({ t: t, y: ev.clientY });
                while (this._samples.length > 2 && t - this._samples[1].t > 100) { this._samples.shift(); }
                if (!this.dragging) {
                    if (raw < -DRAG_START) { this._startY = null; return; }   // vers le haut : on laisse défiler
                    if (raw <= DRAG_START) { return; }
                    this.dragging = true;
                    var root = this._root();
                    if (root) { root.classList.add('is-dragging'); }
                    try { ev.currentTarget.setPointerCapture(ev.pointerId); } catch (e) { /* noop */ }
                }
                this.dy = raw < 0 ? raw * RUBBER : raw;
                var panel = this.$refs.panel;
                var scrim = this._scrim();
                if (panel && scrim) {
                    var h = panel.offsetHeight || 1;
                    scrim.style.opacity = String(Math.max(0, Math.min(1, 1 - Math.max(0, this.dy) / h)));
                }
                if (ev.cancelable) { ev.preventDefault(); }
            },
            onPointerUp: function (ev) {
                if (this._startY === null || (this._pointerId !== null && ev.pointerId !== this._pointerId)) { return; }
                var wasDragging = this.dragging;
                var dy = this.dy;
                this._startY = null;
                this._pointerId = null;
                try { ev.currentTarget.releasePointerCapture(ev.pointerId); } catch (e) { /* noop */ }
                if (!wasDragging) { return; }
                var panel = this.$refs.panel;
                var root = this._root();
                var scrim = this._scrim();
                var h = panel ? panel.offsetHeight : 0;
                var v = this._velocity();
                this.dragging = false;
                if (root) { root.classList.remove('is-dragging'); }
                if (scrim) { scrim.style.opacity = ''; }
                if (v > CLOSE_VELOCITY || (h > 0 && dy > h * CLOSE_RATIO)) { this.hide(); return; }
                if (panel && !reducedMotion()) {
                    panel.classList.add('is-spring');
                    clearTimeout(this._springTimer);
                    this._springTimer = setTimeout(function () { panel.classList.remove('is-spring'); }, SPRING_MS + 20);
                }
                this.dy = 0;
            },
            _velocity: function () {
                var s = this._samples;
                if (!s || s.length < 2) { return 0; }
                var last = s[s.length - 1];
                var ref = s[0];
                for (var i = s.length - 2; i >= 0; i--) {
                    ref = s[i];
                    if (last.t - s[i].t >= 100) { break; }
                }
                var dt = last.t - ref.t;
                return dt > 0 ? (last.y - ref.y) / dt : 0;
            }
        };
    }

    // Fabrique globale : x-data="mSheet('id')" se resout meme si Alpine a demarre
    // avant l'enregistrement par Alpine.data (patron window.auMentionPicker du projet).
    if (typeof window.mSheet !== 'function') { window.mSheet = mSheet; }

    function registerAlpine() {
        if (!window.Alpine || typeof window.Alpine.data !== 'function') { return; }
        if (window.__mShell.alpineRegistered) { return; }
        window.__mShell.alpineRegistered = true;
        window.Alpine.data('mSheet', mSheet);
    }

    /* =========================================================
       2. Toast — window.mToast + relais de l'évènement 'toast'
       ========================================================= */
    var toastTimer = null;
    var ICON_BY_TYPE = { success: 'check', error: 'alert', warning: 'alert', info: 'check' };

    function mToast(message, type, ms) {
        if (!message) { return; }
        type = (type || 'success').toString().toLowerCase();
        if (type === 'danger') { type = 'error'; }
        if (!ICON_BY_TYPE[type]) { type = 'info'; }
        ms = typeof ms === 'number' ? ms : 3200;

        var host = document.getElementById('m-toast-host');
        if (host) {
            clearTimeout(toastTimer);
            host.remove();
        }
        host = document.createElement('div');
        host.id = 'm-toast-host';
        host.className = 'm-toast ' + type;
        host.setAttribute('role', type === 'error' ? 'alert' : 'status');
        host.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        host.innerHTML = svg(ICON_BY_TYPE[type]) + '<span></span>';
        host.querySelector('span').textContent = String(message);
        host.addEventListener('click', function () { dismissToast(host); });
        document.body.appendChild(host);
        toastTimer = setTimeout(function () { dismissToast(host); }, ms);
        return host;
    }
    function dismissToast(host) {
        if (!host || !host.parentNode) { return; }
        host.classList.add('is-leaving');
        setTimeout(function () { if (host.parentNode) { host.remove(); } }, 220);
    }

    window.mToast = mToast;
    window.addEventListener('toast', function (ev) {
        /* Relais uniquement dans le shell mobile, et désactivable par une page
           qui affiche déjà ses propres toasts : <body data-m-toast="off">. */
        if (!hasShell()) { return; }
        if (document.body.getAttribute('data-m-toast') === 'off') { return; }
        var d = ev.detail || {};
        if (d.message) { mToast(d.message, d.type); }
    });

    /* =========================================================
       3. Bandeau hors-ligne — sous .m-appbar
       ========================================================= */
    var OFF_ID = 'm-offline-banner';
    function showOffline() {
        if (document.getElementById(OFF_ID)) { return; }
        var bar = document.createElement('div');
        bar.id = OFF_ID;
        bar.className = 'm-off';
        bar.setAttribute('role', 'status');
        bar.innerHTML = svg('wifi') + '<span>Hors connexion — lecture seule, l’envoi attendra le réseau</span>';
        var appbar = document.querySelector('.m-appbar');
        if (appbar && appbar.parentNode) {
            appbar.parentNode.insertBefore(bar, appbar.nextSibling);
        } else if (document.body) {
            document.body.insertBefore(bar, document.body.firstChild);
        }
        document.body.classList.add('m-is-offline');
        window.dispatchEvent(new CustomEvent('m-offline'));
    }
    function hideOffline(announce) {
        var bar = document.getElementById(OFF_ID);
        if (bar) { bar.remove(); }
        document.body.classList.remove('m-is-offline');
        if (announce && hasShell()) { mToast('Connexion rétablie', 'success', 2000); }
        window.dispatchEvent(new CustomEvent('m-online'));
    }
    window.addEventListener('offline', showOffline);
    window.addEventListener('online', function () { hideOffline(true); });

    /* =========================================================
       4. Tirer-pour-rafraîchir — sur chaque .m-body (tactile)
       Le contenu suit le doigt avec une résistance progressive
       (offset = 80·(1 − e^(−dy/120)) : 36px au seuil de 72px, 80px à
       l'infini), la flèche tourne avec la progression puis devient un
       spinner ; au relâché, ressort de retour (450ms). Pendant le
       rafraîchissement le spinner reste visible 400ms au moins. Le geste n'est
       pas capturé si le .m-body n'est pas en haut, si une feuille est ouverte,
       si un champ a le focus, ni si le doigt est posé sur un élément qui
       défile horizontalement (tableau, ruban de jours).
       ========================================================= */
    var PTR_THRESHOLD = 72;
    var PTR_HOLD = 52;          // hauteur à laquelle le contenu reste pendant l'actualisation
    var PTR_MIN_MS = 400;
    var PTR_SETTLE_MS = 450;    // = transition .m-body.is-settling

    function ptrOffset(dy) {
        return 80 * (1 - Math.exp(-dy / 120));
    }
    function scrollTopOf(el) {
        var cs = window.getComputedStyle(el);
        var scrollsItself = (cs.overflowY === 'auto' || cs.overflowY === 'scroll') && el.scrollHeight > el.clientHeight;
        if (scrollsItself) { return el.scrollTop; }
        var se = document.scrollingElement || document.documentElement;
        return se.scrollTop;
    }
    function scrollsSideways(el, stop) {
        while (el && el !== stop && el.nodeType === 1) {
            if (el.scrollWidth > el.clientWidth + 1) {
                var ox = window.getComputedStyle(el).overflowX;
                if (ox === 'auto' || ox === 'scroll') { return true; }
            }
            el = el.parentNode;
        }
        return false;
    }
    function ensureIndicator(body) {
        var ind = body.querySelector(':scope > .m-ptr');
        if (!ind) {
            ind = document.createElement('div');
            ind.className = 'm-ptr';
            ind.setAttribute('aria-hidden', 'true');
            ind.innerHTML = '<span class="m-ptr-ind">' + svg('chd') + '<span>Tirer pour actualiser</span></span>';
            body.insertBefore(ind, body.firstChild);
        }
        return ind;
    }
    function ptrLabel(ind, text) {
        var span = ind.querySelector('.m-ptr-ind > span');
        if (span) { span.textContent = text; }
    }
    function ptrSet(body, ind, y, progress) {
        body.style.setProperty('--m-ptr-y', y + 'px');
        ind.style.setProperty('--m-ptr-p', String(Math.max(0, Math.min(1, progress))));
    }
    function ptrSettle(body, ind, done) {
        body.classList.remove('is-pulling');
        body.classList.add('is-settling');
        ptrSet(body, ind, 0, 0);
        ind.classList.remove('is-visible');
        var ms = reducedMotion() ? 0 : PTR_SETTLE_MS + 30;
        clearTimeout(body.__mPtrTimer);
        body.__mPtrTimer = setTimeout(function () {
            body.classList.remove('is-settling');
            body.style.removeProperty('--m-ptr-y');
            ind.classList.remove('is-loading');
            ind.style.removeProperty('--m-ptr-p');
            ptrLabel(ind, 'Tirer pour actualiser');
            body.__mPtrBusy = false;
            if (done) { done(); }
        }, ms);
    }
    function triggerRefresh(body, ind) {
        body.__mPtrBusy = true;
        ind.classList.add('is-visible', 'is-loading');
        ptrLabel(ind, 'Actualisation…');
        /* le contenu se cale à PTR_HOLD par ressort et y reste le temps du rafraîchissement */
        body.classList.remove('is-pulling');
        body.classList.add('is-settling');
        ptrSet(body, ind, PTR_HOLD, 1);
        if (body.getAttribute('data-m-ptr') === 'reload') {
            setTimeout(function () { window.location.reload(); }, 120);
            return;
        }
        var started = Date.now();
        var ev = new CustomEvent('m-ptr:refresh', { bubbles: true, detail: { el: body } });
        body.dispatchEvent(ev);
        window.dispatchEvent(new CustomEvent('m-ptr:refresh', { detail: { el: body } }));
        var wait = Math.max(PTR_MIN_MS, 900 - (Date.now() - started));
        clearTimeout(body.__mPtrTimer);
        body.__mPtrTimer = setTimeout(function () { ptrSettle(body, ind); }, wait);
    }
    function bindPtr(body) {
        if (body.__mPtr) { return; }
        body.__mPtr = true;
        var startY = null, dy = 0, active = false, ind = null;

        body.addEventListener('touchstart', function (ev) {
            if (!hasShell() || ev.touches.length !== 1 || body.__mPtrBusy) { return; }
            if (document.body.classList.contains('m-sheet-open')) { return; }
            var a = document.activeElement;
            if (a && a.matches && a.matches('input,textarea,select,[contenteditable]')) { return; }
            if (scrollTopOf(body) > 0) { return; }
            if (scrollsSideways(ev.target, body)) { return; }
            startY = ev.touches[0].clientY;
            dy = 0; active = false;
        }, { passive: true });

        body.addEventListener('touchmove', function (ev) {
            if (startY === null) { return; }
            dy = ev.touches[0].clientY - startY;
            if (dy <= 0 || scrollTopOf(body) > 0) {
                if (active && ind) { ptrSettle(body, ind); }
                active = false;
                startY = dy <= 0 ? startY : null;
                return;
            }
            if (!active && dy > 12) {
                active = true;
                ind = ensureIndicator(body);
                clearTimeout(body.__mPtrTimer);
                body.classList.remove('is-settling');
                body.classList.add('is-pulling');
                ind.classList.add('is-visible');
            }
            if (active && ind) {
                var progress = dy / PTR_THRESHOLD;
                ptrSet(body, ind, ptrOffset(dy), progress);
                ptrLabel(ind, progress >= 1 ? 'Relâcher pour actualiser' : 'Tirer pour actualiser');
            }
        }, { passive: true });

        function end() {
            if (startY === null) { return; }
            var fire = active && dy >= PTR_THRESHOLD;
            startY = null;
            if (!active || !ind) { return; }
            active = false;
            if (fire) { triggerRefresh(body, ind); } else { ptrSettle(body, ind); }
        }
        body.addEventListener('touchend', end, { passive: true });
        body.addEventListener('touchcancel', end, { passive: true });
    }
    function bindAllPtr() {
        Array.prototype.forEach.call(document.querySelectorAll('.m-body'), bindPtr);
    }
    function refreshNow() {
        var body = document.querySelector('.m-body');
        if (!body) { window.location.reload(); return; }
        if (body.__mPtrBusy) { return; }
        triggerRefresh(body, ensureIndicator(body));
    }

    /* =========================================================
       4b. Barre d'onglets — pastille « verre liquide »
       La pastille .m-bottomnav-pill (une seule, rendue par le Blade sous
       l'onglet actif via --m-pill-i / --m-pill-n, ou créée ici) glisse vers
       l'onglet touché (transform, 480ms, courbe iOS) : l'onglet touché prend
       la couleur active, l'ancien redevient gris, puis la navigation part.
       Sa position est en CSS (fraction de la largeur) : le redimensionnement
       ne demande rien ; seul le décalage du glissé est recalculé. Entre deux
       pages, la transition de vue (view-transition-name: m-tab-pill) morphe
       l'ancienne pastille vers la nouvelle.
       ========================================================= */
    var PILL_LEAD_MS = 240;      // temps laissé à la pastille avant de naviguer

    function initTabs() {
        var nav = document.querySelector('.m-bottomnav');
        if (!nav || nav.__mTabs) { return; }
        nav.__mTabs = true;
        var pill = nav.querySelector('.m-bottomnav-pill');
        if (!pill) {
            pill = document.createElement('span');
            pill.className = 'm-bottomnav-pill is-hidden';
            pill.setAttribute('aria-hidden', 'true');
            nav.insertBefore(pill, nav.firstChild);
        }
        var tx = 0;            // décalage courant du glissé
        var navigating = false;

        function tabs() {
            return Array.prototype.filter.call(nav.children, function (n) {
                return n.nodeType === 1 && (n.tagName === 'A' || n.tagName === 'BUTTON');
            });
        }
        function setStatic(index, count) {
            pill.style.transition = 'none';
            pill.style.transform = '';
            tx = 0;
            pill.style.setProperty('--m-pill-i', String(index));
            pill.style.setProperty('--m-pill-n', String(count));
            pill.classList.remove('is-hidden');
            void pill.offsetWidth;
            pill.style.transition = '';
        }
        function slideTo(tab) {
            var list = tabs();
            var i = list.indexOf(tab);
            if (i < 0) { return; }
            if (pill.classList.contains('is-hidden') || reducedMotion()) { setStatic(i, list.length); return; }
            var pr = pill.getBoundingClientRect();
            var tr = tab.getBoundingClientRect();
            tx += (tr.left + tr.width / 2) - (pr.left + pr.width / 2);
            pill.style.transform = 'translateX(' + tx + 'px)';
        }
        function markActive(tab) {
            tabs().forEach(function (t) { t.classList.toggle('on', t === tab); });
        }

        /* Position initiale si le Blade ne l'a pas posée. */
        var current = nav.querySelector('a.on, button.on');
        if (current && pill.classList.contains('is-hidden')) {
            var list0 = tabs();
            setStatic(list0.indexOf(current), list0.length);
        }

        nav.addEventListener('click', function (ev) {
            var a = ev.target.closest ? ev.target.closest('a[href]') : null;
            if (!a || !nav.contains(a) || ev.defaultPrevented) { return; }
            if (ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) { return; }
            if (a.target && a.target !== '_self') { return; }
            var href = a.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#' || /^javascript:/i.test(href)) { return; }
            if (a.classList.contains('on') || navigating) { ev.preventDefault(); return; }
            ev.preventDefault();
            navigating = true;
            markActive(a);
            slideTo(a);
            setTimeout(function () { window.location.href = a.href; }, reducedMotion() ? 0 : PILL_LEAD_MS);
        });

        /* Retour depuis le cache navigateur (bfcache) : l'état d'avant le tap. */
        window.addEventListener('pageshow', function (ev) {
            if (!ev.persisted) { return; }
            navigating = false;
            var back = nav.querySelector('a[aria-current="page"]');
            if (back) { markActive(back); var l = tabs(); setStatic(l.indexOf(back), l.length); }
        });
    }

    /* =========================================================
       5. Invitation à installer — beforeinstallprompt
       ========================================================= */
    var INSTALL_KEY = 'm-install-dismissed';
    var INSTALL_TTL = 30 * 24 * 60 * 60 * 1000;
    var deferredPrompt = null;

    function installDismissedRecently() {
        var raw = storage('get', INSTALL_KEY);
        var ts = raw ? parseInt(raw, 10) : 0;
        return ts && (Date.now() - ts) < INSTALL_TTL;
    }
    function isStandalone() {
        return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    }
    function installCards() { return document.querySelectorAll('.m-install'); }
    function showInstall() {
        if (!deferredPrompt || installDismissedRecently() || isStandalone()) { return; }
        Array.prototype.forEach.call(installCards(), function (c) { c.hidden = false; });
    }
    function hideInstall() {
        Array.prototype.forEach.call(installCards(), function (c) { c.hidden = true; });
    }
    window.addEventListener('beforeinstallprompt', function (ev) {
        ev.preventDefault();
        deferredPrompt = ev;
        showInstall();
    });
    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        hideInstall();
        storage('del', INSTALL_KEY);
        if (hasShell()) { mToast('Application installée', 'success'); }
    });
    document.addEventListener('click', function (ev) {
        var t = ev.target;
        if (!t || !t.closest) { return; }
        var accept = t.closest('[data-m-install-accept]');
        var dismiss = t.closest('[data-m-install-dismiss]');
        if (accept) {
            ev.preventDefault();
            if (!deferredPrompt) { hideInstall(); return; }
            var p = deferredPrompt;
            deferredPrompt = null;
            p.prompt();
            if (p.userChoice && p.userChoice.then) {
                p.userChoice.then(function (choice) {
                    if (!choice || choice.outcome !== 'accepted') { storage('set', INSTALL_KEY, String(Date.now())); }
                    hideInstall();
                }).catch(function () { hideInstall(); });
            } else { hideInstall(); }
        } else if (dismiss) {
            ev.preventDefault();
            storage('set', INSTALL_KEY, String(Date.now()));
            hideInstall();
        }
    });

    /* =========================================================
       6. Amorçage
       ========================================================= */
    function boot() {
        if (isStandalone()) { document.documentElement.classList.add('m-standalone'); }
        if (navigator.onLine === false) { showOffline(); }
        bindAllPtr();
        initTabs();
        /* .m-body injectés plus tard (AJAX) */
        if (window.MutationObserver) {
            new MutationObserver(function (muts) {
                var need = false;
                muts.forEach(function (m) {
                    Array.prototype.forEach.call(m.addedNodes, function (n) {
                        if (n.nodeType === 1 && ((n.matches && n.matches('.m-body')) || (n.querySelector && n.querySelector('.m-body')))) { need = true; }
                    });
                });
                if (need) { bindAllPtr(); }
            }).observe(document.documentElement, { childList: true, subtree: true });
        }
        /* Fermeture à la navigation arrière si une feuille est ouverte. */
        window.addEventListener('popstate', function () {
            window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: {} }));
        });
    }

    window.__mShell = {
        version: 1,
        alpineRegistered: false,
        toast: mToast,
        refresh: refreshNow,
        openSheet: function (id) { window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: id } })); },
        closeSheet: function (id) { window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: { id: id } })); }
    };

    document.addEventListener('alpine:init', registerAlpine);
    registerAlpine();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
