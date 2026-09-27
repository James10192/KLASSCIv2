/* KLASSCI Message Hub — final UX hardening after shared-item model correction. */
(function () {
    'use strict';

    var root = document.querySelector('[data-message-hub-v2]');
    if (!root) return;

    var cfg = {};
    try { cfg = JSON.parse((root.querySelector('[data-message-hub-config]') || {}).textContent || '{}'); } catch (e) { cfg = {}; }

    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = '/css/messages-hub-v2-final.css';
    document.head.appendChild(css);

    var dismissed = new Set();
    var payloadCache = new Map();
    var loadingId = null;
    var archivedIds = new Set();
    var thread = root.querySelector('[data-thread]');
    var threadState = {nearBottom:true, scrollTop:0, childCount:0};
    var enhanceTimer = null;

    function esc(v) { return String(v == null ? '' : v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
    function csrf() { var m=document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; }
    function currentId() { var id=new URLSearchParams(location.search).get('conversation'); return /^\d+$/.test(String(id||'')) ? String(id) : null; }
    function isMobileDrawer() { return window.matchMedia('(max-width:1180px)').matches; }

    function toast(text, error) {
        var n=root.querySelector('[data-toast]'); if(!n) return;
        n.className='mh2-toast is-visible'+(error?' is-error':'');
        n.innerHTML='<i class="fas '+(error?'fa-circle-exclamation':'fa-circle-check')+'"></i><span>'+esc(text)+'</span>';
        clearTimeout(n._timer); n._timer=setTimeout(function(){n.classList.remove('is-visible');},3200);
    }

    function api(url, options) {
        options=options||{}; options.credentials='same-origin';
        options.headers=Object.assign({'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},options.headers||{});
        if((options.method||'GET').toUpperCase()!=='GET'){
            options.headers['X-CSRF-TOKEN']=csrf(); options.headers['Content-Type']='application/json';
        }
        return fetch(url,options).then(function(r){return r.json().catch(function(){return {};}).then(function(d){if(!r.ok)throw new Error(d.message||'Une erreur est survenue.');return d;});});
    }

    function accountLabel(type) {
        return {staff:'Personnel',teacher:'Enseignant',student:'Étudiant',parent:'Parent'}[type] || 'Compte KLASSCI';
    }

    function itemIcon(type) {
        return {paiement:'fa-receipt',inscription:'fa-file-signature',document:'fa-file-lines',reclamation:'fa-circle-exclamation'}[type] || 'fa-paperclip';
    }

    function loadPayload(force) {
        var id=currentId(); if(!id||!cfg.conversationBase) return Promise.resolve(null);
        if(!force&&payloadCache.has(id)) return Promise.resolve(payloadCache.get(id));
        if(loadingId===id) return Promise.resolve(null);
        loadingId=id;
        return api(cfg.conversationBase+'/'+encodeURIComponent(id)).then(function(data){
            payloadCache.set(id,data); renderFinalContext(data); decorateBusinessCards(data); renderFinalAi(data); return data;
        }).catch(function(){return null;}).finally(function(){loadingId=null;});
    }

    function renderFinalContext(data) {
        var box=root.querySelector('[data-context-body]'); if(!box||!data) return;
        var c=data.conversation||{}, participants=c.participants||[], items=data.linked_entities||[], history=data.action_history||[];
        var people=participants.length?participants.map(function(p){
            return '<div class="mh2-person-card"><span class="mh2-avatar">'+esc(initials(p.name))+'</span><div><strong>'+esc(p.name)+'</strong><span>'+esc(p.role_label||'Compte KLASSCI')+(p.department?' · '+esc(p.department):'')+'</span><small class="mh2-account-kind">'+esc(accountLabel(p.account_type))+'</small></div></div>';
        }).join(''):'<div class="mh2-muted">Aucun participant disponible.</div>';
        var shared=items.length?items.map(function(item){
            var by=item.shared_by||{}, purpose=item.share_purpose_label||'';
            var open=item.open_url?'<a class="mh2-context-action" target="_blank" rel="noopener" href="'+esc(item.open_url)+'"><i class="fas fa-arrow-up-right-from-square"></i><span>Ouvrir le dossier</span></a>':'';
            return '<article class="mh2-context-entity is-'+esc(item.type)+'"><div class="mh2-entity-title"><strong>'+esc(item.entity_label||'Élément partagé')+'</strong><span class="mh2-shared-badge"><i class="fas fa-share-nodes"></i> Élément partagé</span></div><div class="mh2-share-meta"><span>Type</span><b>'+esc(item.type||'élément')+'</b>'+(by.name?'<span>Partagé par</span><b>'+esc(by.name)+'</b>':'')+(purpose?'<span>But</span><b>'+esc(purpose)+'</b>':'')+'</div>'+open+'</article>';
        }).join(''):'<div class="mh2-muted">Aucun élément métier partagé dans cette conversation.</div>';
        var quick='';
        if(items.length){
            quick+='<button class="mh2-context-action" data-ai="Résume les éléments partagés dans cette conversation, leur auteur, leur objet et la demande explicite éventuelle, sans attribuer le dossier au participant."><i class="fas fa-wand-magic-sparkles"></i><span>Résumer le partage</span></button>';
            if(items.some(function(x){return x.type==='inscription';})) quick+='<button class="mh2-context-action" data-ai="Explique l’inscription partagée, uniquement à partir des données autorisées de cet élément. Ne suppose aucun lien personnel avec l’auteur du partage."><i class="fas fa-file-signature"></i><span>Expliquer l’inscription</span></button>';
            if(items.some(function(x){return x.type==='paiement'||x.type==='inscription';})) quick+='<button class="mh2-context-action" data-ai="Vérifie le paiement de l’étudiant concerné par l’élément partagé, seulement si mes permissions et les sources disponibles le permettent. Ne fais aucune déduction financière sur l’auteur du partage."><i class="fas fa-receipt"></i><span>Vérifier le paiement</span></button>';
            var peer=(participants[0]&&participants[0].name)||'l’interlocuteur';
            quick+='<button class="mh2-context-action" data-ai="Prépare une réponse professionnelle à '+esc(peer)+' au sujet de l’élément partagé. N’invente aucune relation entre cette personne et l’étudiant."><i class="fas fa-reply"></i><span>Préparer une réponse</span></button>';
            quick+='<button class="mh2-context-action" data-follow-up><i class="fas fa-list-check"></i><span>Créer une action de suivi</span></button>';
            quick+='<button class="mh2-context-action" data-ai="La demande liée à cet élément partagé n’est pas assez claire. Prépare une question courte pour demander s’il faut vérifier le paiement, l’état de l’inscription ou autre chose."><i class="fas fa-circle-question"></i><span>Demander une précision</span></button>';
        } else quick='<div class="mh2-muted">Les actions apparaîtront lorsqu’un élément sera partagé.</div>';
        var hist=history.length?history.map(function(a){return '<div class="mh2-history"><i class="fas fa-circle-dot"></i><div><strong>'+esc(a.title)+'</strong><span>'+esc(a.status)+' · '+esc(a.priority)+'</span></div></div>';}).join(''):'<div class="mh2-muted">Aucune action enregistrée dans ce fil.</div>';
        box.innerHTML='<section><h4>Participants</h4>'+people+'</section><section><h4>Éléments partagés</h4>'+shared+'</section><section><h4>Actions rapides</h4>'+quick+'</section><section><h4>Historique</h4>'+hist+'</section>';
    }

    function decorateBusinessCards(data) {
        var cards=Array.prototype.slice.call(root.querySelectorAll('.mh2-business'));
        var messages=(data&&data.messages||[]).filter(function(m){return m.type==='action_card';});
        cards.forEach(function(card,i){
            var b=(messages[i]&&messages[i].business_card)||{}; card.classList.remove('is-ambiguous');
            var status=card.querySelector('.mh2-link-status'); if(status){status.className='mh2-link-status is-ok';status.innerHTML='<i class="fas fa-share-nodes"></i> Élément partagé';}
            card.querySelectorAll('.mh2-inline-warning,.mh2-verify').forEach(function(n){n.remove();});
            var old=card.querySelector('.mh2-shared-by'); if(old)old.remove();
            var by=b.shared_by||{}, purpose=b.share_purpose_label||'';
            if(by.name||purpose){var meta=document.createElement('div');meta.className='mh2-shared-by';meta.innerHTML=(by.name?'<span>Partagé par : <strong>'+esc(by.name)+'</strong>'+(by.role_label?' · '+esc(by.role_label):'')+'</span>':'')+(purpose?'<span>But : <strong>'+esc(purpose)+'</strong></span>':'');card.appendChild(meta);}
        });
    }

    function renderFinalAi(data) {
        var box=root.querySelector('[data-ai-strip]'); if(!box||!data) return;
        var p=((data.conversation||{}).participants||[])[0]||{}, name=p.name||'l’interlocuteur';
        box.innerHTML='<button data-ai="Résume le partage : auteur, élément partagé, messages et demande explicite. Ne suppose aucun lien personnel entre l’auteur et l’étudiant."><i class="fas fa-wand-magic-sparkles"></i> Résumer le partage</button><button data-ai="Explique l’élément partagé à partir des seules données autorisées."><i class="fas fa-circle-info"></i> Expliquer</button><button data-ai="Vérifie le paiement de l’étudiant de l’élément partagé si les permissions et sources le permettent. Ne conclus rien sur la situation financière de '+esc(name)+'."><i class="fas fa-receipt"></i> Vérifier le paiement</button><button data-ai="Prépare une réponse professionnelle à '+esc(name)+' fondée uniquement sur la conversation et l’élément partagé."><i class="fas fa-reply"></i> Répondre</button>';
    }

    function initials(name){return String(name||'K').trim().split(/\s+/).slice(0,2).map(function(x){return x.charAt(0);}).join('').toUpperCase();}

    function enhanceFailedMessages() {
        root.querySelectorAll('.mh2-sendstate.is-failed').forEach(function(status){
            var row=status.closest('.mh2-msg-row'), retry=row&&row.querySelector('[data-retry]'); if(!row||!retry)return;
            var id=String(retry.getAttribute('data-retry')||''); if(dismissed.has(id)){row.remove();return;}
            if(!status.querySelector('[data-dismiss-failed]')){var b=document.createElement('button');b.type='button';b.dataset.dismissFailed=id;b.textContent='Retirer';status.appendChild(document.createTextNode(' · '));status.appendChild(b);}
        });
    }

    function pruneArchived() {
        var active=root.querySelector('.mh2-filter.is-active'); if(!active||active.dataset.filter==='archived')return;
        root.querySelectorAll('[data-conversation]').forEach(function(row){if(archivedIds.has(String(row.dataset.conversation)))row.style.display='none';else row.style.display='';});
    }

    function refreshArchived() {
        if(!cfg.bootstrap)return;
        api(cfg.bootstrap).then(function(d){archivedIds=new Set((d.conversations||[]).filter(function(c){return c.state&&c.state.archived;}).map(function(c){return String(c.id);}));pruneArchived();}).catch(function(){});
    }

    function patchConversationState(button,key) {
        var id=currentId(); if(!id)return;
        var next=button.getAttribute('aria-pressed')!=='true'; button.disabled=true;
        var body={};body[key]=next;
        api(cfg.stateBase+'/'+id+'/state',{method:'PATCH',body:JSON.stringify(body)}).then(function(d){
            button.disabled=false;button.setAttribute('aria-pressed',next?'true':'false');button.classList.toggle('is-on',next);
            if(key==='archived'){
                if(next)archivedIds.add(id);else archivedIds.delete(id);pruneArchived();
                toast(next?'Conversation archivée. Retrouvez-la dans « Archivés ».':'Conversation restaurée.');
            } else toast(next?'Conversation marquée importante.':'Marque importante retirée.');
        }).catch(function(e){button.disabled=false;toast(e.message,true);});
    }

    function toggleContext() {
        var panel=root.querySelector('[data-context]'); if(!panel)return;
        if(isMobileDrawer()){panel.classList.toggle('is-open');}
        else{root.classList.toggle('is-context-collapsed');}
        var btn=root.querySelector('[data-context-toggle]'); if(btn)btn.setAttribute('aria-expanded',isMobileDrawer()?String(panel.classList.contains('is-open')):String(!root.classList.contains('is-context-collapsed')));
    }

    function addNewMessageButton() {
        if(!thread)return null; var b=thread.querySelector('[data-new-messages]'); if(b)return b;
        b=document.createElement('button');b.type='button';b.className='mh2-new-messages';b.dataset.newMessages='';b.innerHTML='<i class="fas fa-arrow-down"></i> Nouveaux messages';thread.appendChild(b);return b;
    }

    if(thread){
        thread.addEventListener('scroll',function(){var distance=thread.scrollHeight-thread.scrollTop-thread.clientHeight;threadState.nearBottom=distance<90;threadState.scrollTop=thread.scrollTop;if(threadState.nearBottom){var b=thread.querySelector('[data-new-messages]');if(b)b.classList.remove('is-visible');}},{passive:true});
        threadState.childCount=thread.children.length;
    }

    root.addEventListener('click',function(e){
        var el=e.target.closest('[data-context-toggle]');if(el){e.preventDefault();e.stopImmediatePropagation();toggleContext();return;}
        el=e.target.closest('[data-context-close]');if(el){e.preventDefault();e.stopImmediatePropagation();var p=root.querySelector('[data-context]');if(p)p.classList.remove('is-open');if(!isMobileDrawer())root.classList.add('is-context-collapsed');return;}
        el=e.target.closest('[data-archive]');if(el){e.preventDefault();e.stopImmediatePropagation();patchConversationState(el,'archived');return;}
        el=e.target.closest('[data-important]');if(el){e.preventDefault();e.stopImmediatePropagation();patchConversationState(el,'important');return;}
        el=e.target.closest('[data-dismiss-failed]');if(el){e.preventDefault();e.stopImmediatePropagation();dismissed.add(String(el.dataset.dismissFailed||''));var row=el.closest('.mh2-msg-row');if(row)row.remove();return;}
        el=e.target.closest('[data-new-messages]');if(el&&thread){thread.scrollTop=thread.scrollHeight;threadState.nearBottom=true;el.classList.remove('is-visible');return;}
        el=e.target.closest('[data-follow-up]');if(el){e.preventDefault();var newButton=root.querySelector('[data-new]');if(newButton)newButton.click();setTimeout(function(){var internal=root.querySelector('[data-intent-action]');if(internal)internal.click();},30);return;}
    },true);

    function scheduleEnhance() {
        clearTimeout(enhanceTimer);enhanceTimer=setTimeout(function(){
            enhanceFailedMessages();pruneArchived();
            var id=currentId();if(id)loadPayload(true);
            if(thread&&!threadState.nearBottom){
                var count=thread.children.length;if(count>threadState.childCount){thread.scrollTop=threadState.scrollTop;addNewMessageButton().classList.add('is-visible');}threadState.childCount=count;
            }
        },80);
    }

    var observer=new MutationObserver(scheduleEnhance);observer.observe(root,{childList:true,subtree:true});
    window.addEventListener('popstate',function(){loadPayload(true);});
    window.addEventListener('resize',function(){var p=root.querySelector('[data-context]');if(p&&!isMobileDrawer())p.classList.remove('is-open');});
    refreshArchived();loadPayload(true);enhanceFailedMessages();
})();
