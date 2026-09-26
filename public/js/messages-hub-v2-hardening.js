/* KLASSCI Message Hub v2 — last-mile UI guards.
 * This file deliberately does not own business truth. It only consumes the typed
 * Message Hub API to hide controls the viewer cannot use and lets a user dismiss
 * an unsent optimistic message without touching server data.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-message-hub-v2]');
    if (!root) return;

    var cfg = {};
    try {
        var node = root.querySelector('[data-message-hub-config]');
        cfg = JSON.parse(node ? node.textContent : '{}');
    } catch (e) {
        cfg = {};
    }

    var dismissed = new Set();
    var permissionCache = new Map();
    var checkingConversation = null;
    var checkTimer = null;

    function currentConversationId() {
        var id = new URLSearchParams(window.location.search).get('conversation');
        return /^\d+$/.test(String(id || '')) ? String(id) : null;
    }

    function findFailedClientId(row) {
        var retry = row.querySelector('[data-retry]');
        return retry ? String(retry.getAttribute('data-retry') || '') : '';
    }

    function enhanceFailedMessages() {
        root.querySelectorAll('.mh2-sendstate.is-failed').forEach(function (status) {
            var row = status.closest('.mh2-msg-row');
            if (!row) return;
            var clientId = findFailedClientId(row);
            if (!clientId) return;

            if (dismissed.has(clientId)) {
                row.remove();
                return;
            }

            if (!status.querySelector('[data-dismiss-failed]')) {
                var button = document.createElement('button');
                button.type = 'button';
                button.setAttribute('data-dismiss-failed', clientId);
                button.textContent = 'Retirer';
                button.setAttribute('aria-label', 'Retirer ce message non envoyé');
                status.appendChild(document.createTextNode(' · '));
                status.appendChild(button);
            }
        });
    }

    function applyVerifyPermissions(entities) {
        var byId = new Map((entities || []).map(function (entity) {
            return [String(entity.id), entity];
        }));

        root.querySelectorAll('.mh2-verify').forEach(function (panel) {
            var relation = panel.querySelector('[data-link-relation]');
            if (!relation) return;
            var id = String(relation.getAttribute('data-link-relation') || '');
            var entity = byId.get(id);
            if (!entity || entity.can_verify !== false) return;

            var readonly = document.createElement('div');
            readonly.className = 'mh2-muted mh2-verify-readonly';
            readonly.innerHTML = '<i class="fas fa-lock" aria-hidden="true"></i> Vous pouvez consulter ce lien, mais vous n’avez pas le droit de valider sa relation.';
            panel.replaceWith(readonly);
        });
    }

    function refreshVerifyPermissions() {
        var conversationId = currentConversationId();
        if (!conversationId || !cfg.conversationBase) return;

        if (permissionCache.has(conversationId)) {
            applyVerifyPermissions(permissionCache.get(conversationId));
            return;
        }
        if (checkingConversation === conversationId) return;
        checkingConversation = conversationId;

        fetch(cfg.conversationBase + '/' + encodeURIComponent(conversationId), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) throw new Error('context permission lookup failed');
            return response.json();
        }).then(function (payload) {
            var entities = payload.linked_entities || [];
            permissionCache.set(conversationId, entities);
            applyVerifyPermissions(entities);
        }).catch(function () {
            // Backend remains authoritative. If this supplementary lookup fails,
            // the PATCH endpoint still rejects any forbidden verification.
        }).finally(function () {
            checkingConversation = null;
        });
    }

    function scheduleEnhance() {
        window.clearTimeout(checkTimer);
        checkTimer = window.setTimeout(function () {
            enhanceFailedMessages();
            refreshVerifyPermissions();
        }, 30);
    }

    root.addEventListener('click', function (event) {
        var button = event.target.closest('[data-dismiss-failed]');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        var clientId = String(button.getAttribute('data-dismiss-failed') || '');
        if (clientId) dismissed.add(clientId);
        var row = button.closest('.mh2-msg-row');
        if (row) row.remove();
    }, true);

    var observer = new MutationObserver(scheduleEnhance);
    observer.observe(root, {childList: true, subtree: true});

    window.addEventListener('popstate', scheduleEnhance);
    scheduleEnhance();
})();
