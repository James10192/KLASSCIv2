/* KLASSCI Message Hub — progressive enhancement over the existing chat API. */
(function () {
    'use strict';

    var root = document.querySelector('[data-message-hub]');
    if (!root) return;

    var cfgNode = root.querySelector('[data-message-hub-config]');
    var cfg = {};
    try { cfg = JSON.parse(cfgNode ? cfgNode.textContent : '{}'); } catch (e) { cfg = {}; }

    var state = {
        view: 'inbox',
        conversationId: null,
        actionId: null,
        actionView: 'list',
        messages: [],
        conversation: null,
        polling: null,
        userSearchTimer: null
    };

    var $ = function (selector, parent) { return (parent || root).querySelector(selector); };
    var $$ = function (selector, parent) { return Array.prototype.slice.call((parent || root).querySelectorAll(selector)); };
    var csrf = function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function request(url, options) {
        options = options || {};
        options.headers = Object.assign({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }, options.headers || {});
        if ((options.method || 'GET').toUpperCase() !== 'GET') {
            options.headers['X-CSRF-TOKEN'] = csrf();
            options.headers['Content-Type'] = 'application/json';
        }
        return fetch(url, options).then(function (res) {
            if (!res.ok) {
                return res.json().catch(function () { return {}; }).then(function (data) {
                    throw new Error(data.message || data.error || 'Une erreur est survenue.');
                });
            }
            return res.json();
        });
    }

    function toast(message, isError) {
        var node = $('[data-mh-toast]');
        if (!node) return;
        node.classList.toggle('is-error', !!isError);
        node.innerHTML = '<i class="fas ' + (isError ? 'fa-circle-exclamation' : 'fa-circle-check') + '" aria-hidden="true"></i><span>' + escapeHtml(message) + '</span>';
        node.classList.add('is-visible');
        window.clearTimeout(node._timer);
        node._timer = window.setTimeout(function () { node.classList.remove('is-visible'); }, 3200);
    }

    function relativeTime(value) {
        if (!value) return '';
        var d = new Date(value);
        if (Number.isNaN(d.getTime())) return '';
        var now = new Date();
        var diff = now - d;
        if (diff < 60000) return 'maintenant';
        if (diff < 3600000) return Math.floor(diff / 60000) + ' min';
        if (d.toDateString() === now.toDateString()) return d.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});
        var yesterday = new Date(now); yesterday.setDate(now.getDate() - 1);
        if (d.toDateString() === yesterday.toDateString()) return 'Hier';
        return d.toLocaleDateString('fr-FR', {day: '2-digit', month: 'short'});
    }

    function formatDate(value) {
        if (!value) return '—';
        var d = new Date(value);
        return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('fr-FR', {day: '2-digit', month: 'short', year: 'numeric'});
    }

    function setView(view) {
        state.view = view;
        $$('.mh-tab').forEach(function (tab) {
            var active = tab.getAttribute('data-mh-view') === view;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        $('[data-mh-inbox]').style.display = view === 'inbox' ? '' : 'none';
        $('[data-mh-actions]').classList.toggle('is-active', view === 'actions');
        if (view === 'actions') root.classList.remove('is-thread-open');
    }

    function setLoadingThread() {
        var scroll = $('[data-mh-thread-scroll]');
        scroll.innerHTML = '<div class="mh-empty"><div class="mh-empty-card"><div class="mh-skeleton" style="width:180px;height:18px;margin:0 auto 10px"></div><div class="mh-skeleton" style="width:260px;height:12px;margin:0 auto 24px"></div><div class="mh-skeleton" style="width:78%;height:54px;margin:8px 0"></div><div class="mh-skeleton" style="width:66%;height:54px;margin:8px 0 8px auto"></div></div></div>';
    }

    function renderMessages(payload) {
        state.messages = payload.messages || [];
        state.conversation = payload.conversation || {};
        var scroll = $('[data-mh-thread-scroll]');
        var title = $('[data-mh-thread-title]');
        var sub = $('[data-mh-thread-sub]');
        var other = state.conversation.participants && state.conversation.participants[0];
        title.textContent = state.conversation.title || (other && other.name) || 'Conversation';
        sub.textContent = other && other.email ? other.email : (state.conversation.type === 'group' ? 'Conversation de groupe' : 'Messagerie KLASSCI');
        $('[data-mh-composer]').hidden = false;

        if (!state.messages.length) {
            scroll.innerHTML = '<div class="mh-empty"><div class="mh-empty-card"><div class="mh-empty-icon"><i class="fas fa-message"></i></div><h3>Commencez la conversation</h3><p>Les échanges restent ici. Les actions métier sont traitées séparément dans le Centre d’actions.</p></div></div>';
        } else {
            scroll.innerHTML = state.messages.map(renderMessage).join('');
        }
        scroll.scrollTop = scroll.scrollHeight;
        renderContext(state.conversation, state.messages);
        root.classList.add('is-thread-open');
    }

    function renderMessage(msg) {
        if (msg.type === 'action_card') return renderBusinessCard(msg);
        if (msg.type === 'system') {
            return '<div class="mh-message-row"><div class="mh-message" style="margin:auto;background:#fffaeb;border-color:#fedf89"><div class="mh-message-body">' + escapeHtml(msg.body || 'Mise à jour du dossier') + '</div></div></div>';
        }
        return '<div class="mh-message-row ' + (msg.mine ? 'is-mine' : '') + '">' +
            '<div class="mh-message">' +
                '<div class="mh-message-author">' + escapeHtml(msg.sender_name || 'KLASSCI') + '</div>' +
                '<div class="mh-message-body">' + escapeHtml(msg.body || '') + '</div>' +
                '<div class="mh-message-meta"><span>' + escapeHtml(relativeTime(msg.created_at)) + '</span>' + (msg.mine ? '<i class="fas ' + (msg.read_by_others ? 'fa-check-double' : 'fa-check') + '"></i>' : '') + '</div>' +
            '</div></div>';
    }

    function renderBusinessCard(msg) {
        var p = msg.payload || {};
        var kind = p.kind || p.type || 'dossier';
        var label = kind === 'paiement' ? 'Paiement' : (kind === 'inscription' ? 'Inscription' : 'Donnée métier');
        var person = p.etudiant || p.student_name || p.nom_complet || p.name || 'Dossier KLASSCI';
        var status = p.workflow_label || p.statut || p.status || 'À consulter';
        var amount = p.montant || p.amount;
        var reference = p.reference || p.reference_paiement || p.matricule;
        var meta = [
            ['Personne', person],
            ['Statut', status],
            [amount ? 'Montant' : 'Référence', amount ? formatMoney(amount) : (reference || '—')]
        ];
        var cta = msg.cta && msg.cta.url ? '<a class="mh-secondary" href="' + escapeHtml(msg.cta.url) + '">' + escapeHtml(msg.cta.label || 'Ouvrir le dossier') + ' <i class="fas fa-arrow-up-right-from-square"></i></a>' : '';
        return '<div class="mh-message-row ' + (msg.mine ? 'is-mine' : '') + '"><article class="mh-business-card">' +
            '<h4><i class="fas ' + (kind === 'paiement' ? 'fa-receipt' : 'fa-folder-open') + '" aria-hidden="true"></i> ' + escapeHtml(label) + '</h4>' +
            '<p>Contexte métier partagé dans la conversation. Les données essentielles restent compactes.</p>' +
            '<div class="mh-business-meta">' + meta.map(function (m) { return '<div><small>' + escapeHtml(m[0]) + '</small><strong>' + escapeHtml(m[1]) + '</strong></div>'; }).join('') + '</div>' + cta +
        '</article></div>';
    }

    function formatMoney(value) {
        var n = Number(value);
        if (!Number.isFinite(n)) return String(value || '—');
        return new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
    }

    function renderContext(conversation, messages) {
        var body = $('[data-mh-context-body]');
        var context = (conversation && conversation.context) || {};
        var business = null;
        for (var i = messages.length - 1; i >= 0; i -= 1) {
            if (messages[i].type === 'action_card' && messages[i].payload) { business = messages[i].payload; break; }
        }
        business = business || {};
        var person = business.etudiant || business.student_name || context.etudiant || context.student_name;
        var matricule = business.matricule || context.matricule;
        var classe = business.classe || context.classe;
        var paid = business.total_paye || business.paye || context.total_paye;
        var due = business.total_du || business.du || context.total_du;
        var balance = business.solde || context.solde;
        var participants = (conversation.participants || []).map(function (p) { return p.name; }).join(', ');

        body.innerHTML = '<section class="mh-context-section"><div class="mh-context-label">Conversation</div><div class="mh-context-card">' +
            '<div class="mh-context-name">' + escapeHtml(person || participants || 'Contexte du fil') + '</div>' +
            '<div class="mh-context-muted">' + escapeHtml(matricule || classe || 'Informations disponibles selon vos permissions') + '</div>' +
            '<div class="mh-kv">' +
                '<span>Classe / niveau</span><span>' + escapeHtml(classe || '—') + '</span>' +
                '<span>Total dû</span><span>' + escapeHtml(due ? formatMoney(due) : '—') + '</span>' +
                '<span>Total payé</span><span>' + escapeHtml(paid ? formatMoney(paid) : '—') + '</span>' +
                '<span>Solde</span><span>' + escapeHtml(balance ? formatMoney(balance) : '—') + '</span>' +
            '</div></div></section>' +
            '<section class="mh-context-section"><div class="mh-context-label">Actions rapides</div><div class="mh-context-links">' +
                '<button type="button" class="mh-context-link" data-mh-ai="Explique-moi clairement l’état de ce dossier et les prochaines étapes."><span><i class="fas fa-wand-magic-sparkles"></i> Expliquer le dossier avec Nanan</span><i class="fas fa-chevron-right"></i></button>' +
                '<button type="button" class="mh-context-link" data-mh-ai="À partir de cette conversation, propose une relance professionnelle et non agressive."><span><i class="fas fa-bell"></i> Préparer une relance</span><i class="fas fa-chevron-right"></i></button>' +
                '<button type="button" class="mh-context-link" data-mh-ai="Analyse cette conversation et propose l’action métier à créer, sans rien exécuter."><span><i class="fas fa-list-check"></i> Proposer une action</span><i class="fas fa-chevron-right"></i></button>' +
            '</div></section>';
    }

    function loadConversation(id) {
        if (!id) return;
        state.conversationId = String(id);
        $$('.mh-conversation').forEach(function (row) { row.classList.toggle('is-active', row.getAttribute('data-conversation-id') === String(id)); });
        setLoadingThread();
        request(cfg.conversationBase + '/' + encodeURIComponent(id)).then(renderMessages).catch(function (err) {
            toast(err.message, true);
            $('[data-mh-thread-scroll]').innerHTML = '<div class="mh-empty"><div class="mh-empty-card"><div class="mh-empty-icon"><i class="fas fa-triangle-exclamation"></i></div><h3>Impossible de charger la conversation</h3><p>' + escapeHtml(err.message) + '</p></div></div>';
        });
    }

    function sendMessage() {
        var textarea = $('[data-mh-compose-input]');
        var body = textarea.value.trim();
        if (!body || !state.conversationId) return;
        var optimistic = {
            id: 'tmp-' + Date.now(), body: body, sender_name: 'Vous', mine: true,
            created_at: new Date().toISOString(), type: 'text', read_by_others: false
        };
        state.messages.push(optimistic);
        var scroll = $('[data-mh-thread-scroll]');
        scroll.insertAdjacentHTML('beforeend', renderMessage(optimistic));
        scroll.scrollTop = scroll.scrollHeight;
        textarea.value = '';
        textarea.focus();
        request(cfg.conversationBase + '/' + encodeURIComponent(state.conversationId) + '/messages', {
            method: 'POST', body: JSON.stringify({body: body})
        }).then(function () { pollConversations(); }).catch(function (err) {
            toast('Message non envoyé : ' + err.message, true);
            textarea.value = body;
        });
    }

    function pollConversations() {
        if (!cfg.conversationsList) return Promise.resolve();
        return request(cfg.conversationsList).then(function (data) {
            (data.items || []).forEach(function (item) {
                var row = $('[data-conversation-id="' + item.id + '"]');
                if (!row || item.type === 'workflow') return;
                row.setAttribute('data-unread', String(item.unread_count || 0));
                var unread = $('.mh-unread', row); if (unread) unread.textContent = item.unread_count || '';
                var preview = $('.mh-conversation-preview', row); if (preview) preview.textContent = item.last_message || 'Aucun message';
                var time = $('.mh-conversation-meta time', row); if (time) time.textContent = relativeTime(item.last_message_at);
            });
        }).catch(function () {});
    }

    function filterConversations() {
        var q = ($('[data-mh-conversation-search]').value || '').toLowerCase().trim();
        var filter = ($('.mh-filter.is-active') || {}).dataset ? $('.mh-filter.is-active').dataset.filter : 'all';
        $$('.mh-conversation').forEach(function (row) {
            var haystack = (row.getAttribute('data-search') || '').toLowerCase();
            var unread = Number(row.getAttribute('data-unread') || 0) > 0;
            var type = row.getAttribute('data-type');
            var visible = !q || haystack.indexOf(q) !== -1;
            if (filter === 'unread') visible = visible && unread;
            if (filter === 'groups') visible = visible && type === 'group';
            if (filter === 'important') visible = visible && row.getAttribute('data-important') === '1';
            if (filter === 'archived') visible = visible && row.getAttribute('data-archived') === '1';
            row.hidden = !visible;
        });
    }

    function setActionView(view) {
        state.actionView = view;
        $$('[data-mh-action-view]').forEach(function (btn) { btn.classList.toggle('is-active', btn.getAttribute('data-mh-action-view') === view); });
        $('[data-mh-action-table-wrap]').style.display = view === 'list' ? '' : 'none';
        $('[data-mh-kanban]').classList.toggle('is-active', view === 'kanban');
    }

    function filterActions() {
        var q = ($('[data-mh-action-search]').value || '').toLowerCase().trim();
        var status = $('[data-mh-status-filter]').value;
        var priority = $('[data-mh-priority-filter]').value;
        $$('.mh-action-row').forEach(function (row) {
            var visible = !q || (row.getAttribute('data-search') || '').toLowerCase().indexOf(q) !== -1;
            if (status) visible = visible && row.getAttribute('data-status') === status;
            if (priority) visible = visible && row.getAttribute('data-priority') === priority;
            row.hidden = !visible;
        });
        $$('.mh-kanban-card').forEach(function (card) {
            var visible = !q || (card.getAttribute('data-search') || '').toLowerCase().indexOf(q) !== -1;
            if (status) visible = visible && card.getAttribute('data-status') === status;
            if (priority) visible = visible && card.getAttribute('data-priority') === priority;
            card.hidden = !visible;
        });
    }

    function openAction(id) {
        state.actionId = String(id);
        var row = $('[data-action-id="' + id + '"]');
        if (!row) return;
        $$('.mh-action-row').forEach(function (r) { r.classList.toggle('is-active', r === row); });
        var detail = $('[data-mh-action-detail]');
        var title = row.getAttribute('data-title') || 'Action à traiter';
        var subject = row.getAttribute('data-subject') || 'Dossier KLASSCI';
        var service = row.getAttribute('data-service') || 'Interne';
        var priority = row.getAttribute('data-priority') || 'normal';
        var status = row.getAttribute('data-status') || 'todo';
        var created = row.getAttribute('data-created');
        var conversationId = row.getAttribute('data-conversation-id');
        detail.innerHTML = '<div class="mh-action-detail-inner">' +
            '<button class="mh-ghost" type="button" data-mh-close-action><i class="fas fa-arrow-left"></i> Retour</button>' +
            '<div class="mh-context-label" style="margin-top:12px">Action métier</div><h3>' + escapeHtml(title) + '</h3><div class="mh-action-sub">' + escapeHtml(subject) + '</div>' +
            '<div class="mh-context-card" style="margin-top:14px"><div class="mh-kv"><span>Service</span><span>' + escapeHtml(service) + '</span><span>Priorité</span><span>' + escapeHtml(priority) + '</span><span>Statut</span><span>' + escapeHtml(statusLabel(status)) + '</span><span>Créée le</span><span>' + escapeHtml(formatDate(created)) + '</span></div></div>' +
            '<div class="mh-action-actions"><button class="mh-primary" type="button" data-mh-open-workflow="' + escapeHtml(conversationId) + '"><i class="fas fa-folder-open"></i> Ouvrir le dossier</button><button class="mh-secondary" type="button" data-mh-ai="Résume cette action, son contexte et ce qui doit être décidé. Ne déclenche aucune action."><i class="fas fa-wand-magic-sparkles"></i> Résumer avec Nanan</button></div>' +
            '<div class="mh-timeline"><div class="mh-context-label">Historique</div><div class="mh-timeline-item"><span class="mh-timeline-dot"></span><span>Action issue du workflow existant. La migration conserve l’historique de conversation sans casser les liens actuels.</span></div><div class="mh-timeline-item"><span class="mh-timeline-dot"></span><span>Dernière activité : ' + escapeHtml(relativeTime(row.getAttribute('data-updated'))) + '</span></div></div>' +
        '</div>';
        detail.classList.add('is-open');
    }

    function statusLabel(status) {
        return {todo: 'À faire', in_progress: 'En cours', waiting_info: 'En attente d’information', done: 'Terminé', rejected: 'Rejeté'}[status] || status;
    }

    function openLegacyWorkflow(conversationId) {
        if (!conversationId) return;
        request(cfg.conversationBase + '/' + encodeURIComponent(conversationId)).then(function (payload) {
            var messages = payload.messages || [];
            var lastCard = null;
            for (var i = messages.length - 1; i >= 0; i -= 1) if (messages[i].type === 'action_card') { lastCard = messages[i]; break; }
            var detail = $('[data-mh-action-detail]');
            var current = detail.innerHTML;
            var extra = '<div class="mh-context-section" style="padding:0 16px 16px"><div class="mh-context-label">Données du workflow</div>' + (lastCard ? renderBusinessCard(lastCard) : '<div class="mh-context-card">Aucune carte métier disponible.</div>') + '</div>';
            detail.innerHTML = current + extra;
        }).catch(function (err) { toast(err.message, true); });
    }

    function openModal() {
        $('[data-mh-modal]').classList.add('is-open');
        $('[data-mh-modal]').setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        $('[data-mh-modal]').classList.remove('is-open');
        $('[data-mh-modal]').setAttribute('aria-hidden', 'true');
        $('[data-mh-person-search-panel]').classList.remove('is-active');
        document.body.style.overflow = '';
    }

    function choosePersonIntent() {
        $('[data-mh-person-search-panel]').classList.add('is-active');
        window.setTimeout(function () { $('[data-mh-user-search]').focus(); }, 60);
    }

    function searchUsers(q) {
        var results = $('[data-mh-user-results]');
        if (!q || q.trim().length < 2) { results.innerHTML = ''; return; }
        results.innerHTML = '<div style="padding:12px;color:#667085;font-size:.76rem">Recherche…</div>';
        request(cfg.usersSearch + '?q=' + encodeURIComponent(q.trim())).then(function (data) {
            var users = data.users || [];
            if (!users.length) { results.innerHTML = '<div style="padding:12px;color:#667085;font-size:.76rem">Aucun utilisateur trouvé.</div>'; return; }
            results.innerHTML = users.map(function (u) {
                var initials = String(u.name || 'K').split(/\s+/).slice(0,2).map(function (p) { return p.charAt(0); }).join('').toUpperCase();
                return '<button type="button" class="mh-user-result" data-user-id="' + u.id + '"><span class="mh-avatar">' + escapeHtml(initials) + '</span><span><strong style="display:block;font-size:.8rem">' + escapeHtml(u.name) + '</strong><small style="color:#667085">' + escapeHtml(u.email || '') + '</small></span></button>';
            }).join('');
        }).catch(function (err) { results.innerHTML = '<div style="padding:12px;color:#b42318;font-size:.76rem">' + escapeHtml(err.message) + '</div>'; });
    }

    function startDm(userId) {
        request(cfg.startDm, {method: 'POST', body: JSON.stringify({user_id: Number(userId)})}).then(function (data) {
            closeModal(); setView('inbox'); loadConversation(data.conversation_id); pollConversations();
        }).catch(function (err) { toast(err.message, true); });
    }

    function openNanan(instruction) {
        var contextText = '';
        if (state.conversation) {
            var title = $('[data-mh-thread-title]').textContent || 'conversation';
            var excerpts = state.messages.filter(function (m) { return m.type === 'text' && m.body; }).slice(-8).map(function (m) { return (m.mine ? 'Moi: ' : (m.sender_name || 'Interlocuteur') + ': ') + m.body; }).join('\n');
            contextText = '\n\nContexte de la conversation « ' + title + ' » :\n' + excerpts;
        }
        var prompt = instruction + contextText + '\n\nNe déclenche aucune action sensible. Propose seulement un brouillon ou une recommandation que je validerai.';
        var launcher = document.querySelector('.ast-launcher');
        if (!launcher) { toast('Nanan n’est pas disponible sur cette page.', true); return; }
        launcher.click();
        window.setTimeout(function () {
            var input = document.querySelector('.ast-panel textarea');
            if (input) {
                input.value = prompt;
                input.dispatchEvent(new Event('input', {bubbles: true}));
                input.focus();
                toast('Demande préparée dans Nanan. Vérifiez puis envoyez-la.');
            }
        }, 120);
    }

    root.addEventListener('click', function (e) {
        var tab = e.target.closest('[data-mh-view]'); if (tab) { setView(tab.getAttribute('data-mh-view')); return; }
        var conv = e.target.closest('[data-conversation-id].mh-conversation'); if (conv) { loadConversation(conv.getAttribute('data-conversation-id')); return; }
        var filter = e.target.closest('.mh-filter'); if (filter) { $$('.mh-filter').forEach(function (b) { b.classList.remove('is-active'); }); filter.classList.add('is-active'); filterConversations(); return; }
        var newBtn = e.target.closest('[data-mh-new]'); if (newBtn) { openModal(); return; }
        var close = e.target.closest('[data-mh-modal-close]'); if (close) { closeModal(); return; }
        var person = e.target.closest('[data-mh-intent="person"]'); if (person) { choosePersonIntent(); return; }
        var user = e.target.closest('[data-user-id]'); if (user) { startDm(user.getAttribute('data-user-id')); return; }
        var send = e.target.closest('[data-mh-send]'); if (send) { sendMessage(); return; }
        var contextToggle = e.target.closest('[data-mh-context-toggle]'); if (contextToggle) { $('[data-mh-context]').classList.toggle('is-open'); return; }
        var contextClose = e.target.closest('[data-mh-context-close]'); if (contextClose) { $('[data-mh-context]').classList.remove('is-open'); return; }
        var mobileBack = e.target.closest('[data-mh-mobile-back]'); if (mobileBack) { root.classList.remove('is-thread-open'); return; }
        var ai = e.target.closest('[data-mh-ai]'); if (ai) { openNanan(ai.getAttribute('data-mh-ai')); return; }
        var actionView = e.target.closest('[data-mh-action-view]'); if (actionView) { setActionView(actionView.getAttribute('data-mh-action-view')); return; }
        var action = e.target.closest('[data-action-id]'); if (action) { openAction(action.getAttribute('data-action-id')); return; }
        var closeAction = e.target.closest('[data-mh-close-action]'); if (closeAction) { $('[data-mh-action-detail]').classList.remove('is-open'); return; }
        var workflow = e.target.closest('[data-mh-open-workflow]'); if (workflow) { openLegacyWorkflow(workflow.getAttribute('data-mh-open-workflow')); return; }
        var gotoActions = e.target.closest('[data-mh-goto-actions]'); if (gotoActions) { closeModal(); setView('actions'); return; }
    });

    $('[data-mh-compose-input]').addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    $('[data-mh-conversation-search]').addEventListener('input', filterConversations);
    $('[data-mh-action-search]').addEventListener('input', filterActions);
    $('[data-mh-status-filter]').addEventListener('change', filterActions);
    $('[data-mh-priority-filter]').addEventListener('change', filterActions);
    $('[data-mh-user-search]').addEventListener('input', function (e) {
        window.clearTimeout(state.userSearchTimer);
        state.userSearchTimer = window.setTimeout(function () { searchUsers(e.target.value); }, 220);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if ($('[data-mh-modal]').classList.contains('is-open')) closeModal();
        $('[data-mh-context]').classList.remove('is-open');
        $('[data-mh-action-detail]').classList.remove('is-open');
    });

    $$('.mh-conversation-meta time').forEach(function (time) { time.textContent = relativeTime(time.getAttribute('datetime')); });
    $$('.mh-action-row').forEach(function (row) {
        var status = row.getAttribute('data-status') || 'todo';
        var card = $('[data-kanban-for="' + row.getAttribute('data-action-id') + '"]');
        var column = $('[data-kanban-status="' + status + '"] .mh-kanban-body');
        if (card && column) column.appendChild(card);
    });

    var queryConversation = new URLSearchParams(window.location.search).get('conversation');
    if (queryConversation) loadConversation(queryConversation);
    state.polling = window.setInterval(pollConversations, 15000);
    pollConversations();
})();
