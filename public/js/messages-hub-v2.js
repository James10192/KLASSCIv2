/* KLASSCI Message Hub v2 — server-truth, persistent actions and resilient sync. */
(function () {
    'use strict';

    var root = document.querySelector('[data-message-hub-v2]');
    if (!root) return;

    var cfg = {};
    try { cfg = JSON.parse(root.querySelector('[data-message-hub-config]').textContent || '{}'); } catch (e) { cfg = {}; }

    var state = {
        space: 'inbox',
        conversations: [],
        actions: [],
        conversation: null,
        messages: [],
        linkedEntities: [],
        actionHistory: [],
        activeAction: null,
        actionView: 'list',
        poll: null,
        searchTimer: null,
        sending: Object.create(null)
    };

    var $ = function (s, p) { return (p || root).querySelector(s); };
    var $$ = function (s, p) { return Array.prototype.slice.call((p || root).querySelectorAll(s)); };
    var esc = function (v) { return String(v == null ? '' : v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); };
    var csrf = function () { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; };

    function api(url, options) {
        options = options || {};
        options.credentials = 'same-origin';
        options.headers = Object.assign({'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, options.headers || {});
        if ((options.method || 'GET').toUpperCase() !== 'GET') {
            options.headers['X-CSRF-TOKEN'] = csrf();
            options.headers['Content-Type'] = 'application/json';
        }
        return fetch(url, options).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) throw new Error(data.message || data.error || 'Une erreur est survenue.');
                return data;
            });
        });
    }

    function toast(text, error) {
        var n = $('[data-toast]');
        if (!n) return;
        n.className = 'mh2-toast is-visible' + (error ? ' is-error' : '');
        n.innerHTML = '<i class="fas ' + (error ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i><span>' + esc(text) + '</span>';
        clearTimeout(n._timer);
        n._timer = setTimeout(function () { n.classList.remove('is-visible'); }, 3500);
    }

    function relative(value) {
        if (!value) return '';
        var d = new Date(value), now = new Date();
        if (isNaN(d.getTime())) return '';
        var diff = now - d;
        if (diff < 60000) return 'maintenant';
        if (diff < 3600000) return Math.floor(diff / 60000) + ' min';
        if (d.toDateString() === now.toDateString()) return d.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'});
        return d.toLocaleDateString('fr-FR',{day:'2-digit',month:'short'});
    }

    function money(value) {
        var n = Number(value);
        return Number.isFinite(n) ? new Intl.NumberFormat('fr-FR').format(n) + ' FCFA' : 'Données indisponibles';
    }

    function preview(c) {
        var p = c.last_message_preview;
        if (!p) return 'Aucun message';
        if (typeof p.preview === 'string') return p.preview;
        if (typeof p.body === 'string') return p.body;
        return p.type === 'action_card' ? 'Donnée métier partagée' : 'Message';
    }

    function setSpace(space) {
        state.space = space;
        $$('.mh2-tab').forEach(function (b) { b.classList.toggle('is-active', b.dataset.space === space); b.setAttribute('aria-selected', b.dataset.space === space ? 'true':'false'); });
        $('[data-inbox]').hidden = space !== 'inbox';
        $('[data-actions]').hidden = space !== 'actions';
        if (space === 'actions') root.classList.remove('is-thread-open');
    }

    function bootstrap(silent) {
        return api(cfg.bootstrap).then(function (data) {
            var previous = state.conversation && state.conversation.id;
            var previousStamp = state.conversation && state.conversation.last_message_at;
            state.conversations = data.conversations || [];
            state.actions = data.actions || [];
            renderConversationList();
            renderActions();
            $('[data-inbox-count]').textContent = data.counts ? data.counts.conversations : state.conversations.length;
            $('[data-action-count]').textContent = data.counts ? data.counts.actions_open : state.actions.filter(function(a){return ['done','rejected'].indexOf(a.status)<0;}).length;
            if (previous) {
                var current = state.conversations.find(function (c) { return String(c.id) === String(previous); });
                if (!current) {
                    state.conversation = null; state.messages = []; root.classList.remove('is-thread-open'); renderEmptyThread();
                } else if (current.last_message_at !== previousStamp && !state.sending[previous]) {
                    loadConversation(previous, true);
                }
            }
            return data;
        }).catch(function (err) { if (!silent) toast(err.message, true); });
    }

    function renderConversationList() {
        var box = $('[data-conversation-list]');
        var q = (($('[data-conversation-search]').value || '') + '').toLowerCase().trim();
        var activeFilter = $('.mh2-filter.is-active');
        var filter = activeFilter ? activeFilter.dataset.filter : 'all';
        var list = state.conversations.filter(function (c) {
            var hay = [c.title,c.subtitle,preview(c)].concat((c.participants||[]).map(function(p){return [p.name,p.role_label,p.department].join(' ');})).join(' ').toLowerCase();
            if (q && hay.indexOf(q) < 0) return false;
            if (filter === 'unread' && !c.unread_count) return false;
            if (filter === 'important' && !(c.state && c.state.important)) return false;
            if (filter === 'groups' && c.type !== 'group') return false;
            if (filter === 'archived' && !(c.state && c.state.archived)) return false;
            if (filter !== 'archived' && filter !== 'all' && c.state && c.state.archived) return false;
            return true;
        });
        if (!list.length) {
            box.innerHTML = '<div class="mh2-zero"><i class="fas fa-inbox"></i><strong>Aucune conversation</strong><span>Aucun résultat pour ces filtres.</span></div>';
            return;
        }
        box.innerHTML = list.map(function(c){
            var p = (c.participants||[])[0] || {};
            var badges = '';
            if (c.state && c.state.important) badges += '<i class="fas fa-star" title="Important"></i>';
            if (c.state && c.state.pinned) badges += '<i class="fas fa-thumbtack" title="Épinglé"></i>';
            return '<button type="button" class="mh2-conversation ' + (state.conversation && String(state.conversation.id)===String(c.id)?'is-active':'') + '" data-conversation="'+c.id+'">' +
                '<span class="mh2-avatar '+(c.type==='group'?'is-group':'')+'">'+esc(c.initials||'K')+'</span>'+
                '<span class="mh2-conversation-body"><span class="mh2-conversation-name">'+esc(c.title)+' '+badges+'</span>'+
                '<span class="mh2-conversation-role">'+esc(c.subtitle || p.role_label || 'Personnel de l’école')+'</span>'+
                '<span class="mh2-conversation-preview">'+esc(preview(c))+'</span></span>'+
                '<span class="mh2-conversation-meta"><time>'+esc(relative(c.last_message_at))+'</time>'+(c.unread_count?'<b>'+c.unread_count+'</b>':'')+'</span></button>';
        }).join('');
    }

    function renderEmptyThread() {
        $('[data-thread-title]').textContent = 'Sélectionnez une conversation';
        $('[data-thread-sub]').textContent = 'Les personnes et les dossiers liés sont volontairement séparés.';
        $('[data-thread]').innerHTML = '<div class="mh2-empty"><div class="mh2-empty-icon"><i class="fas fa-message"></i></div><h3>Boîte de réception</h3><p>Ouvrez un fil pour voir ses participants, ses dossiers liés et leur relation vérifiée — ou à vérifier.</p></div>';
        $('[data-composer]').hidden = true;
    }

    function loadConversation(id, background) {
        if (!id) return Promise.resolve();
        if (!background) {
            $('[data-thread]').innerHTML = '<div class="mh2-loading"><span></span><span></span><span></span></div>';
            root.classList.add('is-thread-open');
        }
        return api(cfg.conversationBase + '/' + encodeURIComponent(id)).then(function (data) {
            state.conversation = data.conversation;
            state.messages = data.messages || [];
            state.linkedEntities = data.linked_entities || [];
            state.actionHistory = data.action_history || [];
            history.replaceState({}, '', window.location.pathname + '?conversation=' + encodeURIComponent(id));
            renderConversationList(); renderThread(); renderContext();
            $('[data-composer]').hidden = false;
            root.classList.add('is-thread-open');
        }).catch(function (err) {
            toast(err.message,true);
            $('[data-thread]').innerHTML = '<div class="mh2-empty is-danger"><i class="fas fa-triangle-exclamation"></i><h3>Conversation indisponible</h3><p>'+esc(err.message)+'</p></div>';
        });
    }

    function renderThread() {
        var c = state.conversation || {}, p = (c.participants||[])[0] || {};
        $('[data-thread-title]').textContent = c.title || p.name || 'Conversation';
        $('[data-thread-sub]').textContent = p.name ? ((p.role_label || 'Personnel de l’école') + (p.department ? ' · '+p.department : '')) : 'Conversation de groupe';
        var important = $('[data-important]'), archived = $('[data-archive]');
        important.classList.toggle('is-on', !!(c.state&&c.state.important)); important.setAttribute('aria-pressed', c.state&&c.state.important?'true':'false');
        archived.classList.toggle('is-on', !!(c.state&&c.state.archived)); archived.setAttribute('aria-pressed', c.state&&c.state.archived?'true':'false');
        var box = $('[data-thread]');
        if (!state.messages.length) { box.innerHTML='<div class="mh2-empty"><h3>Commencez la conversation</h3><p>Aucun message pour le moment.</p></div>'; return; }
        box.innerHTML = state.messages.map(renderMessage).join('');
        box.scrollTop = box.scrollHeight;
        renderAiStrip();
    }

    function renderMessage(m) {
        if (m.type === 'action_card') return renderBusinessCard(m);
        if (m.type === 'system') return '<div class="mh2-system">'+esc(m.body || 'Mise à jour système')+'</div>';
        var stateHtml = '';
        if (m.send_state === 'sending') stateHtml='<span class="mh2-sendstate"><i class="fas fa-clock"></i> Envoi…</span>';
        if (m.send_state === 'failed') stateHtml='<span class="mh2-sendstate is-failed"><i class="fas fa-circle-exclamation"></i> Échec <button type="button" data-retry="'+esc(m.client_id)+'">Réessayer</button></span>';
        if (m.send_state === 'sent') stateHtml='<span class="mh2-sendstate"><i class="fas fa-check"></i> Envoyé</span>';
        return '<div class="mh2-msg-row '+(m.mine||m.direction==='outgoing'?'is-mine':'')+'"><div class="mh2-msg">'+
            (!(m.mine||m.direction==='outgoing')?'<div class="mh2-author">'+esc(m.sender_name || 'Système')+'</div>':'')+
            '<div class="mh2-body">'+esc(m.body||'')+'</div><div class="mh2-meta"><time>'+esc(relative(m.created_at))+'</time>'+stateHtml+'</div></div></div>';
    }

    function renderBusinessCard(m) {
        var b = m.business_card || {entity_label:'Données indisponibles',relation_label:'Lien à vérifier',details:{},relation_verified:false};
        var d = b.details || {}, warning = !b.relation_verified;
        var fields = [];
        if (d.student_name) fields.push(['Étudiant',d.student_name]);
        if (d.matricule) fields.push(['Matricule',d.matricule]);
        if (d.classe) fields.push(['Classe',d.classe]);
        if (d.amount !== undefined) fields.push(['Montant',money(d.amount)]);
        if (d.reference) fields.push(['Référence',d.reference]);
        if (d.status) fields.push(['Statut',d.status]);
        if (!fields.length) fields.push(['Données','Données indisponibles']);
        var open = b.open_url ? '<a class="mh2-btn is-ghost" target="_blank" rel="noopener" href="'+esc(withReturn(b.open_url))+'"><i class="fas fa-arrow-up-right-from-square"></i> Ouvrir le dossier</a>' : '';
        return '<div class="mh2-msg-row"><article class="mh2-business '+(warning?'is-ambiguous':'')+'"><div class="mh2-business-head"><span class="mh2-business-icon"><i class="fas '+(b.type==='paiement'?'fa-receipt':'fa-file-signature')+'"></i></span><div><small>'+esc(b.type==='paiement'?'Paiement':'Inscription')+'</small><h4>'+esc(b.entity_label || 'Données indisponibles')+'</h4></div><span class="mh2-link-status '+(warning?'is-warning':'is-ok')+'"><i class="fas '+(warning?'fa-triangle-exclamation':'fa-circle-check')+'"></i> '+esc(b.relation_label || 'Lien à vérifier')+'</span></div>'+
            '<div class="mh2-business-grid">'+fields.slice(0,4).map(function(f){return '<div><small>'+esc(f[0])+'</small><strong>'+esc(f[1])+'</strong></div>';}).join('')+'</div>'+
            (warning?'<div class="mh2-inline-warning"><i class="fas fa-shield-halved"></i><span>Ce dossier est bien lié au fil, mais sa relation avec l’interlocuteur n’est pas démontrée. Aucune relance ou validation ne doit viser cette personne avant vérification.</span></div>':'')+
            '<div class="mh2-card-actions">'+open+'</div></article></div>';
    }

    function withReturn(url) {
        if (!url) return '';
        try {
            var u = new URL(url, window.location.origin);
            u.searchParams.set('return_to', window.location.pathname + (state.conversation ? '?conversation='+state.conversation.id : ''));
            return u.pathname + u.search;
        } catch(e) { return url; }
    }

    function renderContext() {
        var box = $('[data-context-body]'), c = state.conversation || {};
        var participants = c.participants || [];
        var participantHtml = participants.length ? participants.map(function(p){
            return '<div class="mh2-person-card"><span class="mh2-avatar">'+esc(initials(p.name))+'</span><div><strong>'+esc(p.name)+'</strong><span>'+esc(p.role_label || 'Personnel de l’école')+'</span><small>'+esc(p.account_type==='staff'?'Compte personnel':'Compte '+p.account_type)+'</small></div></div>';
        }).join('') : '<div class="mh2-muted">Aucun participant disponible.</div>';
        var entityHtml = state.linkedEntities.length ? state.linkedEntities.map(function(e){
            var warning = !e.relation_verified;
            var verify = warning ? renderVerifyLink(e, participants) : '';
            return '<article class="mh2-context-entity '+(warning?'is-ambiguous':'')+'"><div class="mh2-entity-title"><strong>'+esc(e.entity_label)+'</strong><span class="mh2-link-status '+(warning?'is-warning':'is-ok')+'">'+esc(e.relation_label)+'</span></div>'+
                '<div class="mh2-kv"><span>Type</span><b>'+esc(e.type)+'</b><span>Identifiant</span><b>#'+esc(e.entity_id)+'</b><span>Confiance</span><b>'+esc(e.confidence)+'</b></div>'+verify+'</article>';
        }).join('') : '<div class="mh2-muted">Aucun dossier métier explicitement lié.</div>';
        var ambiguous = !!c.has_ambiguous_link;
        var quick = ambiguous
            ? '<button class="mh2-context-action" data-ai="Je veux vérifier le lien entre l’interlocuteur et le dossier lié. Dis-moi uniquement ce qui est certain et ce qu’il faut vérifier, sans recommandation financière."><i class="fas fa-link"></i><span>Vérifier le lien du dossier</span></button><button class="mh2-context-action" data-create-verification><i class="fas fa-list-check"></i><span>Créer une action de vérification</span></button><button class="mh2-context-action" data-ai="Rédige une demande de précision neutre pour clarifier la relation entre l’interlocuteur et le dossier, sans supposer qu’il est l’étudiant ou le payeur."><i class="fas fa-circle-question"></i><span>Demander une précision</span></button>'
            : '<button class="mh2-context-action" data-ai="Explique le dossier lié à cette conversation en citant les sources autorisées et les limites éventuelles."><i class="fas fa-wand-magic-sparkles"></i><span>Expliquer le dossier</span></button>' +
              (state.linkedEntities.some(function(e){return e.sensitive_actions_allowed;})?'<button class="mh2-context-action" data-ai="Prépare une relance professionnelle fondée uniquement sur le dossier lié vérifié. Ne l’envoie pas et précise les données utilisées."><i class="fas fa-bell"></i><span>Préparer une relance</span></button>':'') +
              '<button class="mh2-context-action" data-ai="Propose une action métier à partir du contexte vérifié. Donne un niveau de confiance et ne l’exécute pas."><i class="fas fa-list-check"></i><span>Proposer une action</span></button>';
        var historyHtml = state.actionHistory.length ? state.actionHistory.map(function(a){return '<div class="mh2-history"><i class="fas fa-circle-dot"></i><div><strong>'+esc(a.title)+'</strong><span>'+esc(a.status)+' · '+esc(a.priority)+'</span></div></div>';}).join('') : '<div class="mh2-muted">Aucune décision enregistrée dans ce fil.</div>';
        box.innerHTML = '<section><h4>Participants</h4>'+participantHtml+'</section><section><h4>Dossiers / entités liés</h4>'+entityHtml+'</section><section><h4>Relation et actions sûres</h4>'+quick+'</section><section><h4>Historique des actions</h4>'+historyHtml+'</section>';
    }

    function renderVerifyLink(e, participants) {
        if (!participants.length) return '';
        return '<div class="mh2-verify"><label>Relation à confirmer<select data-link-relation="'+e.id+'"><option value="">Choisir…</option><option value="concerns">Ce dossier concerne cette personne</option><option value="shared_by">Dossier partagé par cette personne</option><option value="responsible_for">Responsable du dossier</option><option value="parent_of">Parent de l’étudiant</option><option value="administrative_contact">Contact administratif</option></select></label><label>Participant<select data-link-user="'+e.id+'">'+participants.map(function(p){return '<option value="'+p.id+'">'+esc(p.name)+'</option>';}).join('')+'</select></label><button type="button" class="mh2-btn" data-verify-link="'+e.id+'"><i class="fas fa-shield-check"></i> Vérifier le lien</button></div>';
    }

    function renderAiStrip() {
        var ambiguous = !!(state.conversation && state.conversation.has_ambiguous_link);
        var box = $('[data-ai-strip]');
        box.innerHTML = '<button data-ai="Résume cette conversation en distinguant clairement les auteurs, faits établis, décisions et points incertains."><i class="fas fa-wand-magic-sparkles"></i> Résumer</button><button data-ai="Propose une réponse professionnelle fondée uniquement sur les faits établis de cette conversation."><i class="fas fa-reply"></i> Répondre</button>' +
            (ambiguous?'<button class="is-warning" data-ai="Analyse uniquement la liaison entre interlocuteur et dossier. Si elle est ambiguë, refuse toute conclusion financière ou d’inscription et dis ce qu’il faut vérifier."><i class="fas fa-link"></i> Vérifier le lien</button>':'<button data-ai="Propose une prochaine action métier à partir des seules données liées et vérifiées. Ne l’exécute pas."><i class="fas fa-list-check"></i> Proposer une action</button>');
    }

    function initials(name) { return String(name||'K').trim().split(/\s+/).slice(0,2).map(function(x){return x.charAt(0);}).join('').toUpperCase(); }

    function sendMessage(body, previousClientId) {
        body = String(body || $('[data-compose]').value || '').trim();
        if (!body || !state.conversation) return;
        var conversationId = state.conversation.id;
        var clientId = previousClientId || ('tmp-'+Date.now()+'-'+Math.random().toString(16).slice(2));
        if (!previousClientId) {
            state.messages.push({client_id:clientId,id:null,type:'text',body:body,sender_name:'Vous',mine:true,direction:'outgoing',created_at:new Date().toISOString(),send_state:'sending'});
        } else {
            var retry = state.messages.find(function(m){return m.client_id===clientId;}); if (retry) retry.send_state='sending';
        }
        state.sending[conversationId] = true;
        $('[data-compose]').value=''; renderThread();
        api(cfg.sendBase + '/' + conversationId + '/messages',{method:'POST',body:JSON.stringify({body:body})}).then(function(real){
            var temp = state.messages.find(function(m){return m.client_id===clientId;});
            if (temp) Object.assign(temp,{id:real.id,created_at:real.created_at,sender_name:real.sender_name||'Vous',send_state:'sent'});
            renderThread();
            setTimeout(function(){ delete state.sending[conversationId]; loadConversation(conversationId,true); bootstrap(true); },500);
        }).catch(function(err){
            delete state.sending[conversationId]; var temp=state.messages.find(function(m){return m.client_id===clientId;}); if(temp) temp.send_state='failed'; renderThread(); toast('Message non envoyé : '+err.message,true);
        });
    }

    function retryMessage(clientId) {
        var m = state.messages.find(function(x){return x.client_id===clientId;});
        if (m && m.send_state==='failed') sendMessage(m.body,clientId);
    }

    function updateConversationState(key) {
        if (!state.conversation) return;
        var next = !(state.conversation.state && state.conversation.state[key]);
        var payload={}; payload[key]=next;
        api(cfg.stateBase+'/'+state.conversation.id+'/state',{method:'PATCH',body:JSON.stringify(payload)}).then(function(data){state.conversation.state=data.state;renderThread();bootstrap(true);}).catch(function(e){toast(e.message,true);});
    }

    function verifyLink(id) {
        var rel=$('[data-link-relation="'+id+'"]'), user=$('[data-link-user="'+id+'"]');
        if(!rel||!rel.value){toast('Choisissez la nature de la relation.',true);return;}
        api(cfg.linkBase+'/'+id,{method:'PATCH',body:JSON.stringify({relation:rel.value,related_user_id:Number(user.value)})}).then(function(){toast('Relation vérifiée.');loadConversation(state.conversation.id,true);}).catch(function(e){toast(e.message,true);});
    }

    function renderActions() {
        var q=String(($('[data-action-search]').value||'')).toLowerCase().trim(), status=$('[data-action-status]').value, priority=$('[data-action-priority]').value;
        var rows=state.actions.filter(function(a){var hay=[a.title,a.subject,a.service,a.creator,a.assignee].join(' ').toLowerCase();return (!q||hay.indexOf(q)>=0)&&(!status||a.status===status)&&(!priority||a.priority===priority);});
        var table=$('[data-action-list]');
        table.innerHTML=rows.length?rows.map(function(a){return '<button type="button" class="mh2-action-row" data-action="'+esc(a.id)+'"><span><strong>'+esc(a.title)+'</strong><small>'+esc(a.subject||'Demande interne')+'</small></span><span class="mh2-status is-'+esc(a.status)+'">'+esc(statusLabel(a.status))+'</span><span class="mh2-priority is-'+esc(a.priority)+'">'+esc(priorityLabel(a.priority))+'</span><span class="mh2-service">'+esc(a.service||'Interne')+'</span><time>'+esc(relative(a.updated_at||a.created_at))+'</time></button>';}).join(''):'<div class="mh2-zero"><i class="fas fa-circle-check"></i><strong>Aucune action</strong><span>Aucune action ne correspond aux filtres.</span></div>';
        renderKanban(rows);
    }

    function renderKanban(rows) {
        var statuses=['todo','in_progress','waiting_info','done'];
        $('[data-kanban]').innerHTML=statuses.map(function(s){var cards=rows.filter(function(a){return a.status===s;});return '<section class="mh2-kanban-col"><header><strong>'+statusLabel(s)+'</strong><b>'+cards.length+'</b></header><div>'+cards.map(function(a){return '<article class="mh2-kanban-card" data-action="'+esc(a.id)+'"><strong>'+esc(a.title)+'</strong><span>'+esc(a.subject||'Demande interne')+'</span><div><em class="mh2-priority is-'+esc(a.priority)+'">'+priorityLabel(a.priority)+'</em>'+(a.editable&&a.numeric_id?'<select data-kanban-status="'+a.numeric_id+'">'+statuses.map(function(x){return '<option value="'+x+'" '+(x===a.status?'selected':'')+'>'+statusLabel(x)+'</option>';}).join('')+'</select>':'')+'</div></article>';}).join('')+'</div></section>';}).join('');
    }

    function statusLabel(s){return {todo:'À faire',in_progress:'En cours',waiting_info:'En attente',done:'Terminé',rejected:'Rejeté'}[s]||s;}
    function priorityLabel(p){return {low:'Faible',normal:'Normale',high:'Haute',urgent:'Urgente'}[p]||p;}

    function setActionView(v){state.actionView=v;$('[data-action-list]').hidden=v!=='list';$('[data-kanban]').hidden=v!=='kanban';$$('[data-action-view]').forEach(function(b){b.classList.toggle('is-active',b.dataset.actionView===v);});}

    function openAction(id) {
        var item=state.actions.find(function(a){return String(a.id)===String(id);}); if(!item)return;
        state.activeAction=item;
        if(item.source==='workflow_action'&&item.numeric_id){api(cfg.actionBase+'/'+item.numeric_id).then(function(d){renderActionDetail(d.action,item);}).catch(function(e){toast(e.message,true);});}
        else renderActionDetail(item,item);
    }

    function renderActionDetail(a,listItem){
        var box=$('[data-action-detail]');
        var acts=(a.activities||[]).map(function(x){return '<div class="mh2-activity"><i class="fas fa-circle"></i><div><strong>'+esc(x.user||'KLASSCI')+'</strong><span>'+esc(x.comment||x.event)+'</span><small>'+esc(relative(x.created_at))+'</small></div></div>';}).join('');
        box.innerHTML='<div class="mh2-detail-head"><div><small>'+esc(listItem.service||a.service||'Interne')+'</small><h3>'+esc(a.title)+'</h3></div><button data-close-detail class="mh2-icon"><i class="fas fa-xmark"></i></button></div><div class="mh2-detail-body"><div class="mh2-detail-badges"><span class="mh2-status is-'+esc(a.status)+'">'+statusLabel(a.status)+'</span><span class="mh2-priority is-'+esc(a.priority)+'">'+priorityLabel(a.priority)+'</span></div><p>'+esc(a.description||listItem.description||'Aucune description.')+'</p>'+(listItem.source==='legacy_notification'?'<div class="mh2-inline-warning"><i class="fas fa-clock-rotate-left"></i><span>Action historique du workflow KLASSCI, conservée pour éviter toute perte après la refonte.</span></div>':'')+(listItem.editable&&listItem.numeric_id?'<label class="mh2-field">Statut<select data-action-status-edit="'+listItem.numeric_id+'"><option value="todo">À faire</option><option value="in_progress">En cours</option><option value="waiting_info">En attente</option><option value="done">Terminé</option><option value="rejected">Rejeté</option></select></label>':'')+(listItem.open_url?'<a target="_blank" rel="noopener" class="mh2-btn" href="'+esc(withReturn(listItem.open_url))+'">Ouvrir le dossier <i class="fas fa-arrow-up-right-from-square"></i></a>':'')+'<section><h4>Historique</h4>'+ (acts||'<div class="mh2-muted">Aucun événement supplémentaire.</div>')+'</section></div>';
        box.classList.add('is-open'); var sel=$('[data-action-status-edit]');if(sel)sel.value=a.status;
    }

    function updateAction(id,status){api(cfg.actionBase+'/'+id,{method:'PATCH',body:JSON.stringify({status:status,comment:'Statut modifié depuis le Centre d’actions.'})}).then(function(){toast('Statut mis à jour.');bootstrap(true);if(state.activeAction)openAction('action:'+id);}).catch(function(e){toast(e.message,true);});}

    function openModal(mode){var m=$('[data-modal]');m.classList.add('is-open');m.setAttribute('aria-hidden','false');document.body.classList.add('mh2-lock');showModalPanel(mode||'intents');}
    function closeModal(){var m=$('[data-modal]');m.classList.remove('is-open');m.setAttribute('aria-hidden','true');document.body.classList.remove('mh2-lock');}
    function showModalPanel(name){$$('[data-modal-panel]').forEach(function(p){p.hidden=p.dataset.modalPanel!==name;});if(name==='action')loadAssignees();}

    function loadAssignees(){api(cfg.assignees).then(function(d){var s=$('[data-action-assignee]');s.innerHTML='<option value="">Non affectée</option>'+(d.users||[]).map(function(u){return '<option value="'+u.id+'">'+esc(u.name)+'</option>';}).join('');}).catch(function(){});}

    function createAction(kind){
        var form=$('[data-action-form]'); var fd=new FormData(form); var payload={action_type:kind||fd.get('action_type'),title:fd.get('title'),description:fd.get('description')||null,priority:fd.get('priority'),service:fd.get('service')||'Interne',assigned_to:fd.get('assigned_to')?Number(fd.get('assigned_to')):null,due_at:fd.get('due_at')||null,chat_conversation_id:state.conversation?state.conversation.id:null,context_data:{subject:fd.get('subject')||'Demande interne'}};
        api(cfg.actionBase,{method:'POST',body:JSON.stringify(payload)}).then(function(){closeModal();form.reset();toast('Action créée et journalisée.');setSpace('actions');bootstrap(true);}).catch(function(e){toast(e.message,true);});
    }

    function createVerificationAction(){openModal('action');var f=$('[data-action-form]');f.elements.action_type.value='verification';f.elements.title.value='Vérifier la liaison du dossier';f.elements.description.value='Confirmer la relation entre l’interlocuteur et le dossier métier lié avant toute relance, validation ou décision.';f.elements.priority.value='high';f.elements.subject.value=state.linkedEntities.map(function(e){return e.entity_label;}).join(', ')||'Dossier lié';}

    function searchUsers(q){var box=$('[data-user-results]');if(!q||q.trim().length<2){box.innerHTML='';return;}box.innerHTML='<div class="mh2-muted">Recherche…</div>';api(cfg.usersSearch+'?q='+encodeURIComponent(q.trim())).then(function(d){box.innerHTML=(d.users||[]).map(function(u){return '<button type="button" class="mh2-user" data-user="'+u.id+'"><span class="mh2-avatar">'+esc(initials(u.name))+'</span><span><strong>'+esc(u.name)+'</strong><small>'+esc(u.email||'')+'</small></span></button>';}).join('')||'<div class="mh2-muted">Aucun utilisateur trouvé.</div>';}).catch(function(e){box.innerHTML='<div class="mh2-muted">'+esc(e.message)+'</div>';});}
    function startDm(id){api(cfg.startDm,{method:'POST',body:JSON.stringify({user_id:Number(id)})}).then(function(d){closeModal();setSpace('inbox');bootstrap(true).then(function(){loadConversation(d.conversation_id);});}).catch(function(e){toast(e.message,true);});}

    function openNanan(instruction){
        if(!state.conversation){toast('Ouvrez d’abord une conversation.',true);return;}
        // No client-built transcript here. Nanan receives only the requested instruction;
        // SafeMessageHubPrompt reconstructs authors, roles and linked entities server-side.
        var launcher=document.querySelector('.ast-launcher');if(!launcher){toast('Nanan n’est pas disponible sur cette page.',true);return;}
        launcher.click();setTimeout(function(){var input=document.querySelector('.ast-panel textarea');if(input){input.value=instruction;input.dispatchEvent(new Event('input',{bubbles:true}));input.focus();toast('Demande préparée. Le contexte sera vérifié côté serveur à l’envoi.');}},120);
    }

    root.addEventListener('click',function(e){
        var el=e.target.closest('[data-space]');if(el){setSpace(el.dataset.space);return;}
        el=e.target.closest('[data-conversation]');if(el){loadConversation(el.dataset.conversation);return;}
        el=e.target.closest('.mh2-filter');if(el){$$('.mh2-filter').forEach(function(x){x.classList.remove('is-active');});el.classList.add('is-active');renderConversationList();return;}
        el=e.target.closest('[data-send]');if(el){sendMessage();return;}
        el=e.target.closest('[data-retry]');if(el){retryMessage(el.dataset.retry);return;}
        el=e.target.closest('[data-important]');if(el){updateConversationState('important');return;}
        el=e.target.closest('[data-archive]');if(el){updateConversationState('archived');return;}
        el=e.target.closest('[data-context-toggle]');if(el){$('[data-context]').classList.toggle('is-open');return;}
        el=e.target.closest('[data-context-close]');if(el){$('[data-context]').classList.remove('is-open');return;}
        el=e.target.closest('[data-mobile-back]');if(el){root.classList.remove('is-thread-open');return;}
        el=e.target.closest('[data-ai]');if(el){openNanan(el.dataset.ai);return;}
        el=e.target.closest('[data-verify-link]');if(el){verifyLink(el.dataset.verifyLink);return;}
        el=e.target.closest('[data-create-verification]');if(el){createVerificationAction();return;}
        el=e.target.closest('[data-new]');if(el){openModal('intents');return;}
        el=e.target.closest('[data-modal-close]');if(el){closeModal();return;}
        el=e.target.closest('[data-intent-person]');if(el){showModalPanel('person');setTimeout(function(){$('[data-user-search]').focus();},30);return;}
        el=e.target.closest('[data-intent-action]');if(el){showModalPanel('action');return;}
        el=e.target.closest('[data-intent-internal]');if(el){showModalPanel('action');setTimeout(function(){var f=$('[data-action-form]');f.elements.action_type.value='internal_request';},0);return;}
        el=e.target.closest('[data-back-intents]');if(el){showModalPanel('intents');return;}
        el=e.target.closest('[data-user]');if(el){startDm(el.dataset.user);return;}
        el=e.target.closest('[data-action-submit]');if(el){createAction();return;}
        el=e.target.closest('[data-action-view]');if(el){setActionView(el.dataset.actionView);return;}
        el=e.target.closest('[data-action]');if(el&&!e.target.closest('select')){openAction(el.dataset.action);return;}
        el=e.target.closest('[data-close-detail]');if(el){$('[data-action-detail]').classList.remove('is-open');return;}
    });

    root.addEventListener('change',function(e){var el=e.target.closest('[data-kanban-status]');if(el){updateAction(el.dataset.kanbanStatus,el.value);return;}el=e.target.closest('[data-action-status-edit]');if(el){updateAction(el.dataset.actionStatusEdit,el.value);return;}});
    $('[data-compose]').addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey&&!e.isComposing){e.preventDefault();sendMessage();}});
    $('[data-conversation-search]').addEventListener('input',renderConversationList);
    $('[data-action-search]').addEventListener('input',renderActions); $('[data-action-status]').addEventListener('change',renderActions); $('[data-action-priority]').addEventListener('change',renderActions);
    $('[data-user-search]').addEventListener('input',function(e){clearTimeout(state.searchTimer);state.searchTimer=setTimeout(function(){searchUsers(e.target.value);},220);});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeModal();$('[data-context]').classList.remove('is-open');$('[data-action-detail]').classList.remove('is-open');}});

    renderEmptyThread(); setActionView('list'); bootstrap(false).then(function(){var id=new URLSearchParams(window.location.search).get('conversation');if(id)loadConversation(id);});
    state.poll=setInterval(function(){bootstrap(true);},12000);
})();
