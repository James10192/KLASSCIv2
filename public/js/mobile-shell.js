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

   Évènements window :
     'm-sheet:open'   {detail:{id}}   ouvre la feuille `id` depuis n'importe où
     'm-sheet:close'  {detail:{id}}   ferme la feuille `id` (sans id : toutes)
     'm-sheet:opened' / 'm-sheet:closed' {detail:{id}}   notifications
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
    var DRAG_THRESHOLD = 80;

    function mSheet(id) {
        return {
            id: id || null,
            open: false,
            dy: 0,
            dragging: false,
            _startY: null,
            _pointerId: null,
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
                if (this.open) { document.body.classList.remove('m-sheet-open'); }
            },

            get dragStyle() {
                return this.dy > 0 ? { transform: 'translateY(' + this.dy + 'px)' } : {};
            },

            toggle: function () { this.open ? this.hide() : this.show(); },

            show: function () {
                if (this.open) { return; }
                var self = this;
                this._prevFocus = document.activeElement;
                this.dy = 0;
                this.open = true;
                document.body.classList.add('m-sheet-open');
                this.$nextTick(function () {
                    var panel = self.$refs.panel;
                    if (!panel) { return; }
                    var first = panel.querySelector('[data-m-autofocus]') || panel.querySelector(FOCUSABLE);
                    (first || panel).focus({ preventScroll: true });
                });
                window.dispatchEvent(new CustomEvent('m-sheet:opened', { detail: { id: this.id } }));
            },

            hide: function () {
                if (!this.open) { return; }
                this.open = false;
                this.dy = 0;
                this.dragging = false;
                document.body.classList.remove('m-sheet-open');
                var prev = this._prevFocus;
                if (prev && typeof prev.focus === 'function' && document.contains(prev)) {
                    try { prev.focus({ preventScroll: true }); } catch (e) { /* noop */ }
                }
                this._prevFocus = null;
                window.dispatchEvent(new CustomEvent('m-sheet:closed', { detail: { id: this.id } }));
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

            /* Glisser vers le bas pour fermer (pointer events). La zone fiable est
               .m-sheet-drag (poignée + en-tête, touch-action:none) ; sur le corps,
               le geste ne démarre que si le panneau n'est pas défilé. */
            onPointerDown: function (ev) {
                if (ev.pointerType === 'mouse' && ev.button !== 0) { return; }
                if (window.matchMedia && window.matchMedia('(min-width: 768px)').matches) { return; }
                var target = ev.target;
                if (target.closest && target.closest('input,textarea,select,[contenteditable]')) { return; }
                var panel = this.$refs.panel;
                var inHandle = target.closest && target.closest('.m-sheet-drag');
                if (!inHandle && panel && panel.scrollTop > 0) { return; }
                this._startY = ev.clientY;
                this._pointerId = ev.pointerId;
                this.dragging = false;
            },
            onPointerMove: function (ev) {
                if (this._startY === null || ev.pointerId !== this._pointerId) { return; }
                var dy = ev.clientY - this._startY;
                if (dy < 0) { dy = 0; }
                if (!this.dragging && dy > 6) {
                    this.dragging = true;
                    try { ev.currentTarget.setPointerCapture(ev.pointerId); } catch (e) { /* noop */ }
                }
                if (this.dragging) {
                    this.dy = dy;
                    if (ev.cancelable) { ev.preventDefault(); }
                }
            },
            onPointerUp: function (ev) {
                if (this._startY === null) { return; }
                var shouldClose = this.dragging && this.dy >= DRAG_THRESHOLD;
                this._startY = null;
                this._pointerId = null;
                this.dragging = false;
                try { ev.currentTarget.releasePointerCapture(ev.pointerId); } catch (e) { /* noop */ }
                if (shouldClose) { this.hide(); } else { this.dy = 0; }
            }
        };
    }

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
       ========================================================= */
    var PTR_THRESHOLD = 70;
    var PTR_MAX = 110;

    function scrollTopOf(el) {
        var cs = window.getComputedStyle(el);
        var scrollsItself = (cs.overflowY === 'auto' || cs.overflowY === 'scroll') && el.scrollHeight > el.clientHeight;
        if (scrollsItself) { return el.scrollTop; }
        var se = document.scrollingElement || document.documentElement;
        return se.scrollTop;
    }
    function ensureIndicator(body) {
        var ind = body.querySelector(':scope > .m-ptr');
        if (!ind) {
            ind = document.createElement('div');
            ind.className = 'm-ptr';
            ind.setAttribute('aria-hidden', 'true');
            ind.innerHTML = svg('chd') + '<span>Tirer pour actualiser</span>';
            body.insertBefore(ind, body.firstChild);
        }
        return ind;
    }
    function triggerRefresh(body, ind) {
        ind.classList.add('is-loading');
        ind.querySelector('span').textContent = 'Actualisation…';
        if (body.getAttribute('data-m-ptr') === 'reload') {
            setTimeout(function () { window.location.reload(); }, 120);
            return;
        }
        var ev = new CustomEvent('m-ptr:refresh', { bubbles: true, detail: { el: body } });
        body.dispatchEvent(ev);
        window.dispatchEvent(new CustomEvent('m-ptr:refresh', { detail: { el: body } }));
        setTimeout(function () { resetIndicator(ind); }, 900);
    }
    function resetIndicator(ind) {
        ind.classList.remove('is-visible', 'is-ready', 'is-loading');
        ind.style.height = '';
        var span = ind.querySelector('span');
        if (span) { span.textContent = 'Tirer pour actualiser'; }
    }
    function bindPtr(body) {
        if (body.__mPtr) { return; }
        body.__mPtr = true;
        var startY = null, dy = 0, active = false, ind = null;

        body.addEventListener('touchstart', function (ev) {
            if (!hasShell() || ev.touches.length !== 1) { return; }
            if (document.body.classList.contains('m-sheet-open')) { return; }
            var a = document.activeElement;
            if (a && a.matches && a.matches('input,textarea,select,[contenteditable]')) { return; }
            if (scrollTopOf(body) > 0) { return; }
            startY = ev.touches[0].clientY;
            dy = 0; active = false;
        }, { passive: true });

        body.addEventListener('touchmove', function (ev) {
            if (startY === null) { return; }
            dy = ev.touches[0].clientY - startY;
            if (dy <= 0 || scrollTopOf(body) > 0) {
                if (active && ind) { resetIndicator(ind); }
                active = false;
                return;
            }
            if (!active && dy > 12) {
                active = true;
                ind = ensureIndicator(body);
                ind.classList.add('is-visible');
            }
            if (active && ind) {
                var h = Math.min(dy * 0.55, PTR_MAX);
                ind.style.height = h + 'px';
                var ready = dy >= PTR_THRESHOLD;
                ind.classList.toggle('is-ready', ready);
                ind.querySelector('span').textContent = ready ? 'Relâcher pour actualiser' : 'Tirer pour actualiser';
            }
        }, { passive: true });

        function end() {
            if (startY === null) { return; }
            var fire = active && dy >= PTR_THRESHOLD;
            startY = null;
            if (!active || !ind) { return; }
            if (fire) { triggerRefresh(body, ind); } else { resetIndicator(ind); }
            active = false;
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
        triggerRefresh(body, ensureIndicator(body));
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
