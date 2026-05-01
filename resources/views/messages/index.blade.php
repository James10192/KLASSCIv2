@extends('layouts.app')

@section('title', 'Messages')

@push('styles')
<style>
/* ============================================================
   Chat namespace ms-* — design Linear-inspired monochrome bleu
   ============================================================ */
:root {
    --ms-primary: #0453cb;
    --ms-primary-d: #033a8e;
    --ms-bg: #f8fafc;
    --ms-surface: #fff;
    --ms-border: #e2e8f0;
    --ms-text: #0f172a;
    --ms-muted: #64748b;
    --ms-bubble-mine: linear-gradient(135deg, #0453cb, #3b7ddb);
    --ms-bubble-them: #f1f5f9;
}
.ms-shell { display: grid; grid-template-columns: 320px 1fr; height: calc(100vh - 80px); background: var(--ms-bg); border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(15,23,42,.06); }
.ms-sidebar { background: var(--ms-surface); border-right: 1px solid var(--ms-border); display: flex; flex-direction: column; }
.ms-sidebar-head { padding: 1rem 1.25rem; border-bottom: 1px solid var(--ms-border); display: flex; justify-content: space-between; align-items: center; }
.ms-sidebar-head h2 { font-size: 1.05rem; font-weight: 700; margin: 0; color: var(--ms-text); }
.ms-newdm-btn { background: var(--ms-primary); color: #fff; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .15s; }
.ms-newdm-btn:hover { background: var(--ms-primary-d); transform: translateY(-1px); }
.ms-tabs { display: flex; gap: .25rem; padding: .5rem .75rem; border-bottom: 1px solid var(--ms-border); }
.ms-tab { flex: 1; padding: .5rem .6rem; border: none; background: transparent; color: var(--ms-muted); font-weight: 600; font-size: .82rem; border-radius: 8px; cursor: pointer; position: relative; }
.ms-tab.active { background: rgba(4,83,203,.08); color: var(--ms-primary); }
.ms-tab-badge { position: absolute; top: 4px; right: 6px; background: #ef4444; color: #fff; font-size: .65rem; padding: 1px 6px; border-radius: 99px; font-weight: 700; }
.ms-list { flex: 1; overflow-y: auto; }
.ms-list-empty { padding: 2rem 1.25rem; text-align: center; color: var(--ms-muted); font-size: .85rem; }
.ms-conv { display: flex; gap: .75rem; padding: .85rem 1.1rem; cursor: pointer; border-bottom: 1px solid #f1f5f9; transition: background .12s; }
.ms-conv:hover { background: #f8fafc; }
.ms-conv.active { background: rgba(4,83,203,.06); border-left: 3px solid var(--ms-primary); padding-left: calc(1.1rem - 3px); }
.ms-conv-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #94a3b8, #64748b); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .9rem; flex-shrink: 0; }
.ms-conv-avatar.workflow { background: linear-gradient(135deg, var(--ms-primary), #5e91de); }
.ms-conv-body { flex: 1; min-width: 0; }
.ms-conv-name { font-weight: 600; color: var(--ms-text); font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ms-conv-preview { color: var(--ms-muted); font-size: .78rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: .15rem; }
.ms-conv-time { color: var(--ms-muted); font-size: .7rem; flex-shrink: 0; }
.ms-conv-unread { width: 8px; height: 8px; border-radius: 50%; background: var(--ms-primary); margin-left: auto; align-self: center; flex-shrink: 0; }

.ms-thread { display: flex; flex-direction: column; background: var(--ms-bg); }
.ms-thread-head { padding: 1rem 1.5rem; border-bottom: 1px solid var(--ms-border); background: var(--ms-surface); display: flex; align-items: center; gap: .75rem; }
.ms-thread-name { font-weight: 700; color: var(--ms-text); }
.ms-thread-status { color: var(--ms-muted); font-size: .78rem; }
.ms-thread-empty { flex: 1; display: flex; align-items: center; justify-content: center; color: var(--ms-muted); padding: 2rem; text-align: center; }
.ms-msgs { flex: 1; overflow-y: auto; padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: .25rem; position: relative; }
/* Message grouping : remove margin between consecutive same-sender messages within 5min */
.ms-msg-group { display: flex; flex-direction: column; gap: .15rem; }
.ms-msg-group + .ms-msg-group { margin-top: .65rem; }
.ms-msg { max-width: 70%; padding: .55rem .85rem; border-radius: 14px; font-size: .9rem; line-height: 1.4; word-break: break-word; transition: opacity .2s; }
.ms-msg.pending { opacity: .55; }
.ms-msg.mine { align-self: flex-end; background: var(--ms-bubble-mine); color: #fff; border-bottom-right-radius: 4px; }
.ms-msg.theirs { align-self: flex-start; background: var(--ms-bubble-them); color: var(--ms-text); border-bottom-left-radius: 4px; }
/* Grouped messages : flatten corners on middle bubbles */
.ms-msg-group .ms-msg.mine:not(:last-child) { border-bottom-right-radius: 6px; }
.ms-msg-group .ms-msg.mine:not(:first-child) { border-top-right-radius: 6px; }
.ms-msg-group .ms-msg.theirs:not(:last-child) { border-bottom-left-radius: 6px; }
.ms-msg-group .ms-msg.theirs:not(:first-child) { border-top-left-radius: 6px; }
.ms-msg.system { align-self: center; background: rgba(245, 158, 11, 0.1); color: #92400e; border: 1px solid rgba(245,158,11,.3); font-size: .82rem; font-weight: 500; padding: .5rem .9rem; border-radius: 99px; }
.ms-msg.action_card { align-self: flex-start; background: var(--ms-surface); border: 1px solid var(--ms-border); padding: 1rem 1.1rem; max-width: 85%; box-shadow: 0 1px 3px rgba(15,23,42,.04); border-radius: 12px; }
.ms-msg-meta { font-size: .68rem; color: var(--ms-muted); margin: .2rem .5rem 0; }
.ms-msg-group:has(.ms-msg.mine) .ms-msg-meta { text-align: right; }
/* "X new messages" banner — replaces auto-scroll anti-pattern */
.ms-new-banner { position: sticky; bottom: .5rem; align-self: center; background: var(--ms-primary); color: #fff; padding: .4rem .9rem; border-radius: 99px; font-size: .8rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(4,83,203,.3); z-index: 5; transition: all .15s; }
.ms-new-banner:hover { transform: translateY(-2px); }
.ms-action-card-title { font-weight: 700; color: var(--ms-text); margin-bottom: .35rem; font-size: .92rem; }
.ms-action-card-body { color: var(--ms-muted); font-size: .85rem; margin-bottom: .65rem; }
.ms-action-card-cta { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1rem; background: var(--ms-primary); color: #fff; border-radius: 8px; font-weight: 600; font-size: .85rem; text-decoration: none; transition: all .15s; }
.ms-action-card-cta:hover { background: var(--ms-primary-d); color: #fff; transform: translateY(-1px); }

.ms-composer { padding: 1rem 1.5rem; border-top: 1px solid var(--ms-border); background: var(--ms-surface); display: flex; gap: .65rem; }
.ms-composer textarea { flex: 1; resize: none; border: 1px solid var(--ms-border); border-radius: 10px; padding: .65rem .9rem; font-size: .9rem; min-height: 42px; max-height: 120px; transition: border-color .15s; font-family: inherit; }
.ms-composer textarea:focus { outline: none; border-color: var(--ms-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.08); }
.ms-composer button { background: var(--ms-primary); color: #fff; border: none; border-radius: 10px; padding: 0 1.2rem; font-weight: 600; cursor: pointer; transition: all .15s; }
.ms-composer button:hover:not(:disabled) { background: var(--ms-primary-d); }
.ms-composer button:disabled { opacity: .5; cursor: not-allowed; }

/* New DM modal */
.ms-modal-search { padding: 0; }
.ms-modal-search input { width: 100%; padding: .75rem 1rem; border: 1px solid var(--ms-border); border-radius: 10px; font-size: .92rem; }
.ms-modal-search input:focus { outline: none; border-color: var(--ms-primary); }
.ms-search-result { padding: .65rem 1rem; cursor: pointer; border-radius: 8px; }
.ms-search-result:hover { background: rgba(4,83,203,.06); }
.ms-search-result strong { display: block; color: var(--ms-text); font-size: .9rem; }
.ms-search-result small { color: var(--ms-muted); font-size: .78rem; }

/* Notifs panel */
.ms-notif-panel { width: 380px; border-left: 1px solid var(--ms-border); background: var(--ms-surface); display: flex; flex-direction: column; }
.ms-notif-head { padding: 1rem 1.25rem; border-bottom: 1px solid var(--ms-border); }
.ms-notif-head h3 { font-size: 1rem; font-weight: 700; margin: 0; color: var(--ms-text); }
.ms-notif-list { flex: 1; overflow-y: auto; padding: .5rem; }
.ms-notif-item { display: block; padding: .85rem; border-radius: 10px; margin-bottom: .35rem; background: rgba(4,83,203,.04); border: 1px solid rgba(4,83,203,.1); cursor: pointer; text-decoration: none; color: inherit; transition: all .15s; }
.ms-notif-item:hover { background: rgba(4,83,203,.08); transform: translateX(-2px); }
.ms-notif-actor { font-weight: 700; font-size: .85rem; color: var(--ms-text); }
.ms-notif-label { font-size: .82rem; color: var(--ms-muted); margin: .2rem 0; }
.ms-notif-cta { display: inline-block; margin-top: .35rem; padding: .3rem .7rem; background: var(--ms-primary); color: #fff; border-radius: 6px; font-size: .74rem; font-weight: 600; text-decoration: none; }
.ms-notif-empty { text-align: center; padding: 2rem 1rem; color: var(--ms-muted); font-size: .85rem; }

@media (max-width: 768px) {
    .ms-shell { grid-template-columns: 1fr; height: calc(100vh - 80px); }
    .ms-sidebar { display: none; }
    .ms-shell.show-list .ms-sidebar { display: flex; }
    .ms-shell.show-list .ms-thread { display: none; }
    .ms-notif-panel { display: none; }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi" x-data="messagesPage()" x-init="init()">
    <div class="main-content" style="padding: 1rem;">

        <div class="ms-shell" :class="{ 'show-list': !activeConvo }">
            {{-- Sidebar : conversations + tabs --}}
            <aside class="ms-sidebar">
                <div class="ms-sidebar-head">
                    <h2>Messages</h2>
                    <button class="ms-newdm-btn" @click="openNewDm()" title="Nouveau message">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>

                <div class="ms-tabs">
                    <button class="ms-tab" :class="{ active: tab === 'all' }" @click="tab = 'all'">Tous</button>
                    <button class="ms-tab" :class="{ active: tab === 'workflow' }" @click="tab = 'workflow'">
                        Workflow
                        <span class="ms-tab-badge" x-show="notifications.length > 0" x-text="notifications.length"></span>
                    </button>
                </div>

                <div class="ms-list">
                    <template x-if="tab === 'all'">
                        <div>
                            <template x-if="conversations.length === 0">
                                <div class="ms-list-empty">
                                    <i class="far fa-comment-dots fa-2x" style="opacity:.3"></i>
                                    <div style="margin-top:.6rem">Aucune conversation pour l'instant.</div>
                                </div>
                            </template>
                            <template x-for="c in conversations" :key="c.id">
                                <div class="ms-conv" :class="{ active: activeConvo?.id === c.id }" @click="openConvo(c)">
                                    <div class="ms-conv-avatar" :class="{ workflow: c.type === 'workflow' }"
                                         x-text="(c.title || c.participants?.[0]?.name || '?').slice(0,2).toUpperCase()"></div>
                                    <div class="ms-conv-body">
                                        <div class="ms-conv-name" x-text="c.title || c.participants?.[0]?.name || 'Conversation'"></div>
                                        <div class="ms-conv-preview" x-text="c.last_message?.body || 'Aucun message'"></div>
                                    </div>
                                    <span class="ms-conv-time" x-text="formatTime(c.last_message_at)"></span>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="tab === 'workflow'">
                        <div>
                            <template x-if="notifications.length === 0">
                                <div class="ms-list-empty">
                                    <i class="far fa-bell fa-2x" style="opacity:.3"></i>
                                    <div style="margin-top:.6rem">Aucune notif workflow.</div>
                                </div>
                            </template>
                            <template x-for="n in notifications" :key="n.id">
                                <div class="ms-notif-item">
                                    <div class="ms-notif-actor" x-text="n.data.actor_name + ' a complété : ' + formatType(n.data.type)"></div>
                                    <div class="ms-notif-label" x-text="n.data.next_label || 'Étape suivante du workflow'"></div>
                                    <a class="ms-notif-cta" :href="n.data.next_url" @click="markRead(n.id)" x-show="n.data.next_url">
                                        <i class="fas fa-arrow-right me-1"></i>Aller
                                    </a>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </aside>

            {{-- Thread principal --}}
            <main class="ms-thread">
                <template x-if="!activeConvo">
                    <div class="ms-thread-empty">
                        <div>
                            <i class="far fa-comments fa-3x" style="opacity:.2"></i>
                            <div style="margin-top:1rem; font-weight:600">Sélectionne une conversation</div>
                            <div style="font-size:.85rem; margin-top:.4rem">ou démarre-en une nouvelle avec le bouton ✏️</div>
                        </div>
                    </div>
                </template>
                <template x-if="activeConvo">
                    <div style="display: contents">
                        <div class="ms-thread-head">
                            <div class="ms-conv-avatar" :class="{ workflow: activeConvo.type === 'workflow' }"
                                 x-text="(activeConvo.title || activeOther?.name || '?').slice(0,2).toUpperCase()"></div>
                            <div>
                                <div class="ms-thread-name" x-text="activeConvo.title || activeOther?.name || 'Conversation'"></div>
                                <div class="ms-thread-status" x-text="activeConvo.type === 'workflow' ? 'Conversation workflow' : 'Discussion privée'"></div>
                            </div>
                        </div>

                        <div class="ms-msgs" x-ref="msgs" @scroll="onScroll($event)" aria-live="polite" aria-atomic="false" aria-label="Messages de la conversation">
                            <template x-for="(group, gi) in groupedMessages" :key="gi">
                                <div class="ms-msg-group">
                                    <template x-for="m in group.items" :key="m.id">
                                        <div>
                                            <template x-if="m.type === 'system'">
                                                <div class="ms-msg system" x-text="m.body"></div>
                                            </template>
                                            <template x-if="m.type === 'action_card'">
                                                <div class="ms-msg action_card">
                                                    <div class="ms-action-card-title" x-text="m.payload?.title || m.body"></div>
                                                    <div class="ms-action-card-body" x-text="m.payload?.body" x-show="m.payload?.body"></div>
                                                    <a class="ms-action-card-cta" :href="m.payload?.url" x-show="m.payload?.url">
                                                        <i class="fas fa-arrow-right"></i>
                                                        <span x-text="m.payload?.label || 'Ouvrir'"></span>
                                                    </a>
                                                </div>
                                            </template>
                                            <template x-if="m.type === 'text'">
                                                <div :class="['ms-msg', m.mine ? 'mine' : 'theirs', m.pending ? 'pending' : '']" x-text="m.body"></div>
                                            </template>
                                        </div>
                                    </template>
                                    <div class="ms-msg-meta" x-text="(group.mine ? 'Toi' : group.sender_name) + ' · ' + formatTime(group.last_at)"></div>
                                </div>
                            </template>
                            {{-- "X new messages" banner — anti auto-scroll, respecte la lecture en cours --}}
                            <button type="button" class="ms-new-banner" x-show="newCount > 0" @click="scrollBottom(true); newCount = 0">
                                <i class="fas fa-arrow-down me-1"></i>
                                <span x-text="newCount + ' nouveau' + (newCount > 1 ? 'x' : '') + ' message' + (newCount > 1 ? 's' : '')"></span>
                            </button>
                        </div>

                        <form class="ms-composer" @submit.prevent="sendMessage()">
                            <textarea x-model="draft" rows="1"
                                      placeholder="Écris un message… (Entrée pour envoyer, Shift+Entrée pour saut de ligne)"
                                      aria-label="Composer un message"
                                      @keydown.enter.prevent="if (!$event.shiftKey) sendMessage(); else draft += '\n'"></textarea>
                            <button type="submit" :disabled="!draft.trim() || sending">
                                <span x-show="!sending"><i class="fas fa-paper-plane"></i></span>
                                <span x-show="sending"><i class="fas fa-spinner fa-spin"></i></span>
                            </button>
                        </form>
                    </div>
                </template>
            </main>
        </div>

        {{-- Modal nouveau DM --}}
        <div class="modal fade" id="newDmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau message</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body ms-modal-search">
                        <input type="search" placeholder="Rechercher un utilisateur (nom ou email)…" x-model="dmQuery" @input.debounce.300ms="searchUsers()">
                        <div style="margin-top: .75rem">
                            <template x-for="u in dmResults" :key="u.id">
                                <div class="ms-search-result" @click="startDm(u)">
                                    <strong x-text="u.name"></strong>
                                    <small x-text="u.email"></small>
                                </div>
                            </template>
                            <div class="ms-list-empty" x-show="dmQuery.length >= 2 && dmResults.length === 0">
                                Aucun résultat
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@php
    $conversationsPayload = $conversations->map(function ($c) {
        return [
            'id' => $c->id,
            'type' => $c->type,
            'title' => $c->title,
            'last_message_at' => $c->last_message_at?->toIso8601String(),
            'last_message' => $c->lastMessage ? ['body' => $c->lastMessage->body, 'type' => $c->lastMessage->type] : null,
            'participants' => $c->participants->where('id', '!=', auth()->id())->map(function ($p) {
                return ['id' => $p->id, 'name' => $p->name];
            })->values(),
        ];
    });
@endphp
<script>
function messagesPage() {
    return {
        tab: 'all',
        conversations: {!! json_encode($conversationsPayload) !!},
        activeConvo: null,
        activeOther: null,
        messages: [],
        draft: '',
        sending: false,
        notifications: [],
        notifPollInterval: null,
        msgPollInterval: null,
        dmQuery: '',
        dmResults: [],
        dmModal: null,
        newCount: 0,           // "X new messages" banner counter
        atBottom: true,
        csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        init() {
            this.loadNotifications();
            this.notifPollInterval = setInterval(() => this.loadNotifications(), 30000);
        },

        // Groupement messages par sender consécutif < 5min (anti-clutter — finding research 2026)
        get groupedMessages() {
            const groups = [];
            let current = null;
            for (const m of this.messages) {
                const t = new Date(m.created_at).getTime();
                if (current
                    && current.sender_id === m.sender_id
                    && current.type === m.type
                    && (t - current.last_t) < 5 * 60 * 1000) {
                    current.items.push(m);
                    current.last_at = m.created_at;
                    current.last_t = t;
                } else {
                    current = {
                        sender_id: m.sender_id,
                        sender_name: m.sender_name,
                        mine: !!m.mine,
                        type: m.type,
                        items: [m],
                        last_at: m.created_at,
                        last_t: t,
                    };
                    groups.push(current);
                }
            }
            return groups;
        },

        async openConvo(c) {
            this.activeConvo = c;
            this.activeOther = (c.participants || [])[0] || null;
            this.newCount = 0;
            const r = await fetch(`/messages/conversations/${c.id}`, { headers: { Accept: 'application/json' } });
            const data = await r.json();
            this.messages = data.messages;
            this.atBottom = true;
            this.$nextTick(() => this.scrollBottom(false));
            // Restart message polling pour la conversation active (30s — bandwidth-friendly Tecno 3G/4G per research)
            if (this.msgPollInterval) clearInterval(this.msgPollInterval);
            this.msgPollInterval = setInterval(() => this.refreshMessages(), 30000);
        },

        async refreshMessages() {
            if (!this.activeConvo) return;
            try {
                const r = await fetch(`/messages/conversations/${this.activeConvo.id}`, { headers: { Accept: 'application/json' } });
                const data = await r.json();
                const knownIds = new Set(this.messages.map(m => m.id));
                const incoming = data.messages.filter(m => !knownIds.has(m.id));
                if (incoming.length === 0) return;
                this.messages = data.messages;
                if (this.atBottom) {
                    this.$nextTick(() => this.scrollBottom(false));
                } else {
                    this.newCount += incoming.filter(m => !m.mine).length;
                }
            } catch (e) { /* silent */ }
        },

        async sendMessage() {
            if (!this.draft.trim() || this.sending || !this.activeConvo) return;
            const body = this.draft.trim();
            this.draft = '';
            // Optimistic UI : on push immédiatement avec pending=true (research finding 2026)
            const tempId = 'tmp-' + Date.now();
            this.messages.push({
                id: tempId, sender_id: -1, sender_name: 'Toi', mine: true,
                type: 'text', body, payload: null,
                created_at: new Date().toISOString(), pending: true,
            });
            this.$nextTick(() => this.scrollBottom(false));
            this.sending = true;
            try {
                const r = await fetch(`/messages/conversations/${this.activeConvo.id}/messages`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                    body: JSON.stringify({ body }),
                });
                if (!r.ok) throw new Error('Send failed');
                const m = await r.json();
                // Replace temp message with confirmed one
                const idx = this.messages.findIndex(x => x.id === tempId);
                if (idx >= 0) this.messages[idx] = { ...m, type: 'text', payload: null, pending: false };
            } catch (e) {
                // Mark as failed — keep visible avec UI dégradée
                const idx = this.messages.findIndex(x => x.id === tempId);
                if (idx >= 0) {
                    this.messages[idx].pending = false;
                    this.messages[idx].body = body + ' (échec — réessayer)';
                }
                this.draft = body;
            } finally {
                this.sending = false;
            }
        },

        onScroll(e) {
            const el = e.target;
            this.atBottom = (el.scrollHeight - el.scrollTop - el.clientHeight) < 50;
            if (this.atBottom) this.newCount = 0;
        },

        scrollBottom(smooth = true) {
            if (!this.$refs.msgs) return;
            this.$refs.msgs.scrollTo({ top: this.$refs.msgs.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
            this.atBottom = true;
            this.newCount = 0;
        },

        formatTime(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            const now = new Date();
            const diff = (now - d) / 1000 / 60;
            if (diff < 1) return 'à l\'instant';
            if (diff < 60) return Math.floor(diff) + ' min';
            if (diff < 1440) return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
        },

        formatType(t) {
            return ({
                'inscription.created': 'Création d\'inscription',
                'paiement.created': 'Encaissement de paiement',
                'paiement.validated': 'Validation de paiement',
                'inscription.validated': 'Validation d\'inscription',
            })[t] || t;
        },

        async loadNotifications() {
            try {
                const r = await fetch('/messages/notifications', { headers: { Accept: 'application/json' } });
                const data = await r.json();
                this.notifications = data.items;
            } catch (e) { /* silent */ }
        },

        async markRead(id) {
            await fetch(`/messages/notifications/${id}/read`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
            });
            this.notifications = this.notifications.filter(n => n.id !== id);
        },

        openNewDm() {
            if (!this.dmModal) this.dmModal = new bootstrap.Modal(document.getElementById('newDmModal'));
            this.dmQuery = '';
            this.dmResults = [];
            this.dmModal.show();
        },

        async searchUsers() {
            if (this.dmQuery.length < 2) { this.dmResults = []; return; }
            const r = await fetch(`/messages/users/search?q=${encodeURIComponent(this.dmQuery)}`, { headers: { Accept: 'application/json' } });
            const data = await r.json();
            this.dmResults = data.users;
        },

        async startDm(u) {
            const r = await fetch('/messages/dm/start', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                body: JSON.stringify({ user_id: u.id }),
            });
            const data = await r.json();
            this.dmModal.hide();
            // Reload conversations + open new one
            const idx = this.conversations.findIndex(c => c.id === data.conversation_id);
            if (idx === -1) {
                this.conversations.unshift({
                    id: data.conversation_id, type: 'dm',
                    title: null, participants: [{ id: u.id, name: u.name }],
                    last_message_at: new Date().toISOString(), last_message: null,
                });
            }
            this.openConvo(this.conversations.find(c => c.id === data.conversation_id));
        },
    };
}
</script>
@endsection
