@extends('layouts.app')

@section('title', 'Messages')

@push('scripts')
<script src="{{ asset('js/inscriptions/common.js') }}"></script>
@endpush

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
    --ms-bubble-them: #fff;
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
.ms-msg-group { display: flex; flex-direction: column; gap: .35rem; }
.ms-msg-group + .ms-msg-group { margin-top: 1rem; }
/* Wrapper <div> créé par Alpine x-for : sans flex, align-self sur l'enfant ne s'applique pas. */
.ms-msg-group > div { display: flex; flex-direction: column; }
.ms-msg-group > div + div { margin-top: .25rem; }
.ms-msg { max-width: 70%; padding: .55rem .85rem; border-radius: 14px; font-size: .9rem; line-height: 1.4; word-break: break-word; transition: opacity .2s; }
.ms-msg.pending { opacity: .55; }
.ms-msg.mine { align-self: flex-end; background: var(--ms-bubble-mine); color: #fff; border-bottom-right-radius: 4px; }
.ms-msg.theirs { align-self: flex-start; background: var(--ms-bubble-them); color: var(--ms-text); border-bottom-left-radius: 4px; border: 1px solid var(--ms-border); box-shadow: 0 1px 3px rgba(15,23,42,.04); }
/* Grouped messages : flatten corners on middle bubbles */
.ms-msg-group .ms-msg.mine:not(:last-child) { border-bottom-right-radius: 6px; }
.ms-msg-group .ms-msg.mine:not(:first-child) { border-top-right-radius: 6px; }
.ms-msg-group .ms-msg.theirs:not(:last-child) { border-bottom-left-radius: 6px; }
.ms-msg-group .ms-msg.theirs:not(:first-child) { border-top-left-radius: 6px; }
.ms-msg.system { align-self: center; background: rgba(245, 158, 11, 0.1); color: #92400e; border: 1px solid rgba(245,158,11,.3); font-size: .82rem; font-weight: 500; padding: .5rem .9rem; border-radius: 99px; }
.ms-msg.action_card { align-self: flex-start; background: var(--ms-surface); border: 1px solid var(--ms-border); padding: 1rem 1.1rem; max-width: 85%; box-shadow: 0 1px 3px rgba(15,23,42,.04); border-radius: 12px; }
/* WhatsApp pattern : timestamp + receipt DANS la bulle, en bas à droite */
.ms-msg { display: flex; flex-direction: column; }
.ms-msg-text { word-break: break-word; }
.ms-msg-time-inline {
    align-self: flex-end;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .62rem;
    opacity: .85;
    margin-top: .25rem;
    line-height: 1;
    white-space: nowrap;
}
.ms-msg.theirs .ms-msg-time-inline { color: var(--ms-muted); }
.ms-msg.mine .ms-msg-time-inline { color: rgba(255,255,255,.82); }
.ms-msg-receipt { font-size: .72rem; }
.ms-msg.mine .ms-msg-receipt { color: rgba(255,255,255,.65); }
.ms-msg.mine .ms-msg-receipt.read { color: #ffffff; }
.ms-msg.theirs .ms-msg-receipt { color: var(--ms-muted); }
.ms-msg.theirs .ms-msg-receipt.read { color: var(--ms-primary); }

/* Acard meta footer (in-card) */
.acard-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: .35rem;
    font-size: .62rem;
    opacity: .85;
    padding-top: .35rem;
    border-top: 1px solid rgba(15,23,42,.06);
}
.acard.mine .acard-footer { color: rgba(255,255,255,.78); border-top-color: rgba(255,255,255,.18); }
.acard.mine .acard-footer .ms-msg-receipt { color: rgba(255,255,255,.7); }
.acard.mine .acard-footer .ms-msg-receipt.read { color: #fff; }

/* Date dividers entre messages selon le jour */
.ms-date-divider {
    align-self: center;
    background: rgba(15,23,42,.06);
    color: var(--ms-muted);
    padding: .25rem .9rem;
    border-radius: 99px;
    font-size: .72rem;
    font-weight: 600;
    margin: .8rem 0 .4rem;
    letter-spacing: .02em;
    text-transform: capitalize;
}

/* Présence — dot vert pour en ligne + label sub */
.ms-presence-wrap { position: relative; }
.ms-presence-dot {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #94a3b8;
    border: 2px solid var(--ms-surface);
    transition: background .2s;
}
.ms-presence-dot.online { background: #10b981; box-shadow: 0 0 0 2px rgba(16,185,129,.18); }
.ms-presence-label { font-size: .72rem; color: var(--ms-muted); display: inline-flex; align-items: center; gap: .3rem; }
.ms-presence-label .ms-presence-mini-dot {
    width: 7px; height: 7px; border-radius: 50%; background: #94a3b8; flex-shrink: 0;
}
.ms-presence-label.online .ms-presence-mini-dot { background: #10b981; }
.ms-presence-label.online { color: #047857; font-weight: 600; }
/* "X new messages" banner — replaces auto-scroll anti-pattern */
.ms-new-banner { position: sticky; bottom: .5rem; align-self: center; background: var(--ms-primary); color: #fff; padding: .4rem .9rem; border-radius: 99px; font-size: .8rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(4,83,203,.3); z-index: 5; transition: all .15s; }
.ms-new-banner:hover { transform: translateY(-2px); }
.ms-action-card-title { font-weight: 700; color: var(--ms-text); margin-bottom: .35rem; font-size: .92rem; }
.ms-action-card-body { color: var(--ms-muted); font-size: .85rem; margin-bottom: .65rem; }
.ms-action-card-cta { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1rem; background: var(--ms-primary); color: #fff; border-radius: 8px; font-weight: 600; font-size: .85rem; text-decoration: none; transition: all .15s; }
.ms-action-card-cta:hover { background: var(--ms-primary-d); color: #fff; transform: translateY(-1px); }

/* ============================================================
   Action cards — namespace acard-* — share inscription/paiement
   ============================================================ */
.acard {
    align-self: flex-start;
    background: var(--ms-surface);
    border: 1px solid var(--ms-border);
    border-radius: 14px;
    padding: .85rem .95rem;
    max-width: 380px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 4px 16px rgba(4,83,203,.04);
    display: flex;
    flex-direction: column;
    gap: .65rem;
    transition: box-shadow .15s, transform .15s;
}
.acard:hover { box-shadow: 0 4px 16px rgba(4,83,203,.08), 0 1px 3px rgba(15,23,42,.06); transform: translateY(-1px); }

/* Pattern iMessage : reçue (par défaut) à gauche en blanc, envoyée à droite en bleu KLASSCI. */
.acard.mine {
    align-self: flex-end;
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%);
    border-color: rgba(255,255,255,.18);
    color: #fff;
    box-shadow: 0 4px 16px rgba(4,83,203,.18), 0 1px 3px rgba(4,83,203,.1);
}
.acard.mine:hover { box-shadow: 0 8px 24px rgba(4,83,203,.24), 0 2px 6px rgba(4,83,203,.12); }
.acard.mine .acard-title { color: #fff; }
.acard.mine .acard-sub { color: rgba(255,255,255,.78); }
.acard.mine .acard-meta-label { color: rgba(255,255,255,.65); }
.acard.mine .acard-meta-value { color: #fff; }
.acard.mine .acard-avatar {
    background: rgba(255,255,255,.18);
    color: #fff;
    border: 1px solid rgba(255,255,255,.25);
    backdrop-filter: blur(6px);
}
.acard.mine .acard-kind-badge {
    background: rgba(255,255,255,.18);
    color: #fff;
}
.acard.mine .acard-kind-badge--paiement {
    background: rgba(255,255,255,.18);
    color: #fff;
}
.acard.mine .acard-chip {
    background: rgba(255,255,255,.14);
    color: #fff;
    border: 1px solid rgba(255,255,255,.18);
}
.acard.mine .acard-chip--success { background: rgba(16,185,129,.32); color: #d1fae5; border-color: rgba(16,185,129,.4); }
.acard.mine .acard-chip--warning { background: rgba(245,158,11,.32); color: #fef3c7; border-color: rgba(245,158,11,.42); }
.acard.mine .acard-chip--danger  { background: rgba(220,38,38,.32); color: #fee2e2; border-color: rgba(220,38,38,.42); }
.acard.mine .acard-cta--primary {
    background: #fff;
    color: var(--ms-primary);
}
.acard.mine .acard-cta--primary:hover {
    color: var(--ms-primary-d);
    box-shadow: 0 6px 16px rgba(0,0,0,.18);
}
.acard.mine .acard-cta--ghost {
    background: rgba(255,255,255,.14);
    color: #fff;
    border-color: rgba(255,255,255,.28);
}
.acard.mine .acard-cta--ghost:hover {
    background: rgba(255,255,255,.22);
    color: #fff;
}
.acard.mine .acard-cta--disabled {
    background: rgba(255,255,255,.1);
    color: rgba(255,255,255,.55);
}
.acard.mine .acard-note {
    color: rgba(255,255,255,.7);
    border-top-color: rgba(255,255,255,.18);
}
.acard-head { display: flex; align-items: center; gap: .65rem; }
.acard-avatar {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, var(--ms-primary), #5e91de);
    color: #fff; font-weight: 700; font-size: .92rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.acard-avatar img { width: 100%; height: 100%; object-fit: cover; }
.acard-head-info { flex: 1; min-width: 0; }
.acard-title { font-weight: 700; color: var(--ms-text); font-size: .92rem; line-height: 1.2; }
.acard-sub { color: var(--ms-muted); font-size: .76rem; margin-top: .12rem; }
.acard-kind-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .15rem .5rem; border-radius: 99px;
    font-size: .68rem; font-weight: 700; letter-spacing: .02em; text-transform: uppercase;
    background: rgba(4,83,203,.1); color: var(--ms-primary);
    flex-shrink: 0;
}
.acard-kind-badge--paiement { background: rgba(16,185,129,.1); color: #047857; }
.acard-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
.acard-chip {
    padding: .2rem .55rem; border-radius: 99px; font-size: .72rem; font-weight: 600;
    background: rgba(15,23,42,.05); color: var(--ms-text);
    display: inline-flex; align-items: center; gap: .3rem;
}
.acard-chip--success { background: rgba(16,185,129,.12); color: #047857; }
.acard-chip--warning { background: rgba(245,158,11,.12); color: #92400e; }
.acard-chip--danger  { background: rgba(220,38,38,.12); color: #b91c1c; }
.acard-meta { display: grid; grid-template-columns: auto 1fr; gap: .25rem .85rem; font-size: .8rem; }
.acard-meta-label { color: var(--ms-muted); }
.acard-meta-value { font-weight: 600; color: var(--ms-text); }
.acard-cta {
    display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
    padding: .55rem 1rem; border-radius: 10px; font-weight: 600; font-size: .85rem;
    text-decoration: none; transition: all .15s; border: 1px solid transparent;
}
.acard-cta--primary { background: var(--ms-bubble-mine); color: #fff; }
.acard-cta--primary:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(4,83,203,.25); }
.acard-cta--ghost { background: rgba(4,83,203,.06); color: var(--ms-primary); border-color: rgba(4,83,203,.15); }
.acard-cta--ghost:hover { color: var(--ms-primary); background: rgba(4,83,203,.1); }
.acard-cta--disabled { background: rgba(15,23,42,.04); color: var(--ms-muted); cursor: not-allowed; pointer-events: none; }
.acard-note {
    font-size: .76rem; color: var(--ms-muted); padding-top: .35rem;
    border-top: 1px dashed var(--ms-border);
}

/* Composer 📎 attach button + dropdown */
.ms-composer-attach { position: relative; display: flex; align-items: flex-end; }
.ms-attach-btn {
    background: rgba(4,83,203,.06); color: var(--ms-primary);
    border: 1px solid rgba(4,83,203,.15);
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .15s; flex-shrink: 0;
}
.ms-attach-btn:hover { background: rgba(4,83,203,.12); transform: translateY(-1px); }
.ms-attach-menu {
    position: absolute; bottom: calc(100% + .5rem); left: 0;
    background: var(--ms-surface); border: 1px solid var(--ms-border);
    border-radius: 12px; padding: .35rem; min-width: 200px;
    box-shadow: 0 10px 30px rgba(15,23,42,.12);
    z-index: 1000;
}
/* Spécificité forte pour neutraliser les styles globaux <button> du dashboard. */
.ms-attach-menu .ms-attach-item,
.ms-attach-menu button.ms-attach-item {
    display: flex !important;
    width: 100% !important;
    align-items: center !important;
    gap: .65rem !important;
    padding: .6rem .8rem !important;
    background: transparent !important;
    border: 1px solid transparent !important;
    border-radius: 8px !important;
    color: var(--ms-text) !important;
    font-size: .88rem !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    transition: all .12s !important;
    text-align: left !important;
    box-shadow: none !important;
    line-height: 1.2 !important;
    white-space: nowrap;
}
.ms-attach-menu .ms-attach-item:hover,
.ms-attach-menu button.ms-attach-item:hover {
    background: rgba(4,83,203,.08) !important;
    border-color: rgba(4,83,203,.14) !important;
    color: var(--ms-primary) !important;
    transform: translateX(2px);
}
.ms-attach-menu .ms-attach-item i {
    width: 18px;
    text-align: center;
    color: var(--ms-muted);
    font-size: .92rem;
    flex-shrink: 0;
}
.ms-attach-menu .ms-attach-item:hover i { color: var(--ms-primary); }

/* Picker modal — réutilise .ms-modal mais avec resultat plus spécifique */
.ms-picker-result {
    display: flex; align-items: center; gap: .85rem;
    padding: .75rem .9rem; cursor: pointer;
    border: 1px solid transparent; border-radius: 10px; transition: all .12s;
}
.ms-picker-result:hover {
    background: rgba(4,83,203,.05); border-color: rgba(4,83,203,.15);
    transform: translateX(2px);
}
.ms-picker-meta { display: flex; gap: .35rem; flex-wrap: wrap; margin-top: .15rem; }
.ms-picker-status {
    padding: .12rem .45rem; border-radius: 99px; font-size: .68rem; font-weight: 700;
    background: rgba(15,23,42,.05); color: var(--ms-muted);
}
.ms-picker-status--ok { background: rgba(16,185,129,.12); color: #047857; }
.ms-picker-status--pending { background: rgba(245,158,11,.12); color: #92400e; }

.ms-composer { padding: 1rem 1.5rem; border-top: 1px solid var(--ms-border); background: var(--ms-surface); display: flex; gap: .65rem; }
.ms-composer textarea { flex: 1; resize: none; border: 1px solid var(--ms-border); border-radius: 10px; padding: .65rem .9rem; font-size: .9rem; min-height: 42px; max-height: 120px; transition: border-color .15s; font-family: inherit; }
.ms-composer textarea:focus { outline: none; border-color: var(--ms-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.08); }
.ms-composer button { background: var(--ms-primary); color: #fff; border: none; border-radius: 10px; padding: 0 1.2rem; font-weight: 600; cursor: pointer; transition: all .15s; }
.ms-composer button:hover:not(:disabled) { background: var(--ms-primary-d); }
.ms-composer button:disabled { opacity: .5; cursor: not-allowed; }

/* New DM modal — premium redesign */
.ms-modal .modal-dialog { max-width: 560px; }
.ms-modal .modal-content { border: none; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px rgba(15,23,42,.18); }
.ms-modal .modal-header {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%);
    color: #fff; padding: 1.5rem 1.75rem; border-bottom: none; align-items: flex-start;
}
.ms-modal-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    margin-right: 1rem; backdrop-filter: blur(8px);
}
.ms-modal-icon i { font-size: 1.1rem; color: #fff; }
.ms-modal-title-block { flex: 1; min-width: 0; }
.ms-modal-title-block h5 { font-weight: 700; font-size: 1.15rem; margin: 0 0 .2rem; color: #fff; }
.ms-modal-title-block p { margin: 0; font-size: .82rem; color: rgba(255,255,255,.78); }
.ms-modal .btn-close-white { filter: brightness(2); opacity: .85; }
.ms-modal .modal-body { padding: 1.5rem 1.75rem 1.25rem; }
.ms-search-wrap { position: relative; }
.ms-search-wrap i.fa-search {
    position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
    color: var(--ms-muted); font-size: .9rem; pointer-events: none;
}
.ms-modal-search-input {
    width: 100%; padding: .9rem 1rem .9rem 2.6rem; border: 1.5px solid var(--ms-border);
    border-radius: 12px; font-size: .95rem; background: var(--ms-bg);
    transition: all .15s; font-family: inherit;
}
.ms-modal-search-input:focus {
    outline: none; border-color: var(--ms-primary); background: #fff;
    box-shadow: 0 0 0 4px rgba(4,83,203,.1);
}
.ms-modal-results { margin-top: 1rem; max-height: 360px; overflow-y: auto; }
.ms-search-result {
    display: flex; align-items: center; gap: .85rem;
    padding: .8rem .85rem; cursor: pointer; border-radius: 10px;
    transition: all .12s; border: 1px solid transparent;
}
.ms-search-result:hover {
    background: rgba(4,83,203,.05);
    border-color: rgba(4,83,203,.15);
    transform: translateX(2px);
}
.ms-search-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    background: linear-gradient(135deg, var(--ms-primary), #5e91de);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .92rem; flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(4,83,203,.25);
}
.ms-search-info { flex: 1; min-width: 0; }
.ms-search-info strong { display: block; color: var(--ms-text); font-size: .92rem; font-weight: 600; }
.ms-search-info small { color: var(--ms-muted); font-size: .78rem; }
.ms-search-result-arrow { color: var(--ms-muted); transition: transform .15s; flex-shrink: 0; }
.ms-search-result:hover .ms-search-result-arrow { color: var(--ms-primary); transform: translateX(3px); }
.ms-modal-empty {
    padding: 2.5rem 1rem; text-align: center; color: var(--ms-muted);
    background: var(--ms-bg); border-radius: 12px; margin-top: .5rem;
}
.ms-modal-empty i { font-size: 2rem; opacity: .35; display: block; margin-bottom: .6rem; }
.ms-modal-empty .ms-empty-title { font-weight: 600; color: var(--ms-text); margin-bottom: .25rem; font-size: .92rem; }
.ms-modal-empty .ms-empty-hint { font-size: .82rem; }
.ms-modal-footer {
    padding: .9rem 1.75rem; border-top: 1px solid var(--ms-border);
    background: var(--ms-bg); display: flex; align-items: center; justify-content: space-between;
    font-size: .78rem; color: var(--ms-muted);
}
.ms-modal-footer kbd {
    background: #fff !important;
    color: var(--ms-text) !important;
    border: 1px solid var(--ms-border);
    border-radius: 5px;
    padding: 1px 6px;
    font-size: .72rem;
    font-family: ui-monospace, SFMono-Regular, monospace;
    box-shadow: 0 1px 0 rgba(15,23,42,.06);
    font-weight: 600;
}

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
                                    <div class="ms-presence-wrap">
                                        <div class="ms-conv-avatar" :class="{ workflow: c.type === 'workflow' }"
                                             x-text="(c.title || c.participants?.[0]?.name || '?').slice(0,2).toUpperCase()"></div>
                                        <span class="ms-presence-dot" x-show="c.type === 'dm' && c.participants?.[0]" :class="{ online: c.participants?.[0]?.is_online }"></span>
                                    </div>
                                    <div class="ms-conv-body">
                                        <div class="ms-conv-name" x-text="c.title || c.participants?.[0]?.name || 'Conversation'"></div>
                                        <div class="ms-conv-preview" x-text="c.last_message?.preview || c.last_message?.body || 'Aucun message'"></div>
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
                            <div class="ms-presence-wrap">
                                <div class="ms-conv-avatar" :class="{ workflow: activeConvo.type === 'workflow' }"
                                     x-text="(activeConvo.title || activeOther?.name || '?').slice(0,2).toUpperCase()"></div>
                                <span class="ms-presence-dot" x-show="activeConvo.type === 'dm' && activeOther" :class="{ online: activeOther?.is_online }"></span>
                            </div>
                            <div>
                                <div class="ms-thread-name" x-text="activeConvo.title || activeOther?.name || 'Conversation'"></div>
                                <template x-if="activeConvo.type === 'dm' && activeOther">
                                    <div class="ms-presence-label" :class="{ online: activeOther?.is_online }">
                                        <span class="ms-presence-mini-dot"></span>
                                        <span x-text="formatPresence(activeOther) || 'Discussion privée'"></span>
                                    </div>
                                </template>
                                <template x-if="activeConvo.type !== 'dm'">
                                    <div class="ms-thread-status" x-text="activeConvo.type === 'workflow' ? 'Conversation workflow' : 'Discussion'"></div>
                                </template>
                            </div>
                        </div>

                        <div class="ms-msgs" x-ref="msgs" @scroll="onScroll($event)" aria-live="polite" aria-atomic="false" aria-label="Messages de la conversation">
                            <template x-for="block in groupedMessages" :key="block.kind === 'divider' ? 'd_' + block.dayKey : 'g_' + block.items[0].id + '_' + block.items.length + '_' + block.last_t">
                                <div class="ms-block">
                                    <template x-if="block.kind === 'divider'">
                                        <div class="ms-date-divider" x-text="block.label"></div>
                                    </template>
                                    <template x-if="block.kind === 'group'">
                                <div class="ms-msg-group" :class="{ mine: block.mine }" x-data="{ group: block }">
                                    <template x-for="m in group.items" :key="m.id">
                                        <div>
                                            <template x-if="m.type === 'system'">
                                                <div class="ms-msg system" x-text="m.body"></div>
                                            </template>
                                            <template x-if="m.type === 'action_card' && m.payload?.kind">
                                                {{-- Action card riche : share inscription/paiement --}}
                                                <div class="acard" :class="{ mine: m.mine }">
                                                    <div class="acard-head">
                                                        <div class="acard-avatar" x-data="{ imgFailed: false }">
                                                            <template x-if="m.payload?.snapshot?.etudiant?.photo_url && !imgFailed">
                                                                <img :src="m.payload.snapshot.etudiant.photo_url"
                                                                     :alt="m.payload.snapshot.etudiant.name"
                                                                     x-on:error="imgFailed = true">
                                                            </template>
                                                            <template x-if="!m.payload?.snapshot?.etudiant?.photo_url || imgFailed">
                                                                <span x-text="(m.payload?.snapshot?.etudiant?.name || '?').trim().split(/\s+/).slice(0,2).map(w => w[0]).join('').toUpperCase() || '?'"></span>
                                                            </template>
                                                        </div>
                                                        <div class="acard-head-info">
                                                            <div class="acard-title" x-text="m.payload?.snapshot?.etudiant?.name || '—'"></div>
                                                            <div class="acard-sub">
                                                                <span x-text="m.payload?.snapshot?.classe || '—'"></span>
                                                                <template x-if="m.payload?.snapshot?.matricule">
                                                                    <span> · <span x-text="m.payload.snapshot.etudiant.matricule"></span></span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <span class="acard-kind-badge" :class="{ 'acard-kind-badge--paiement': m.payload.kind === 'paiement' }">
                                                            <i :class="m.payload.kind === 'paiement' ? 'fas fa-cash-register' : 'fas fa-user-graduate'"></i>
                                                            <span x-text="m.payload.kind === 'paiement' ? 'Paiement' : 'Inscription'"></span>
                                                        </span>
                                                    </div>

                                                    {{-- Inscription : montants + statut --}}
                                                    <template x-if="m.payload.kind === 'inscription'">
                                                        <div>
                                                            <div class="acard-chips">
                                                                <span class="acard-chip" :class="m.payload?.snapshot?.workflow_chip_class || 'acard-chip'" x-text="m.payload?.snapshot?.workflow_label || 'Statut inconnu'"></span>
                                                                <span class="acard-chip" x-show="m.payload?.snapshot?.is_sous_reserve">
                                                                    <i class="fas fa-exclamation-triangle"></i> Sous réserve
                                                                </span>
                                                            </div>
                                                            <div class="acard-meta">
                                                                <span class="acard-meta-label">Année</span>
                                                                <span class="acard-meta-value" x-text="m.payload?.snapshot?.annee || '—'"></span>
                                                                <span class="acard-meta-label">Total dû</span>
                                                                <span class="acard-meta-value" x-text="formatXof(m.payload?.snapshot?.montant_total)"></span>
                                                                <span class="acard-meta-label">Payé</span>
                                                                <span class="acard-meta-value" x-text="formatXof(m.payload?.snapshot?.montant_paye)"></span>
                                                                <span class="acard-meta-label">Solde</span>
                                                                <span class="acard-meta-value" x-text="formatXof(m.payload?.snapshot?.solde_restant)"></span>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    {{-- Paiement : montant + mode + statut --}}
                                                    <template x-if="m.payload.kind === 'paiement'">
                                                        <div>
                                                            <div class="acard-chips">
                                                                <span class="acard-chip" :class="m.payload?.snapshot?.is_validated ? 'acard-chip--success' : 'acard-chip--warning'">
                                                                    <i :class="m.payload?.snapshot?.is_validated ? 'fas fa-check-circle' : 'fas fa-clock'"></i>
                                                                    <span x-text="m.payload?.snapshot?.is_validated ? 'Validé' : 'En attente'"></span>
                                                                </span>
                                                                <template x-if="m.payload?.snapshot?.mode_paiement">
                                                                    <span class="acard-chip" x-text="m.payload.snapshot.mode_paiement"></span>
                                                                </template>
                                                            </div>
                                                            <div class="acard-meta">
                                                                <span class="acard-meta-label">Montant</span>
                                                                <span class="acard-meta-value" x-text="formatXof(m.payload?.snapshot?.montant)"></span>
                                                                <template x-if="m.payload?.snapshot?.reference">
                                                                    <span class="acard-meta-label">Référence</span>
                                                                </template>
                                                                <template x-if="m.payload?.snapshot?.reference">
                                                                    <span class="acard-meta-value" x-text="m.payload.snapshot.reference"></span>
                                                                </template>
                                                                <template x-if="m.payload?.snapshot?.date_paiement">
                                                                    <span class="acard-meta-label">Date</span>
                                                                </template>
                                                                <template x-if="m.payload?.snapshot?.date_paiement">
                                                                    <span class="acard-meta-value" x-text="formatDate(m.payload.snapshot.date_paiement)"></span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    {{-- CTA contextuel — calculé server-side selon viewer --}}
                                                    <template x-if="m.cta">
                                                        <a :href="m.cta.url" class="acard-cta" :class="'acard-cta--' + m.cta.variant">
                                                            <i :class="'fas ' + (m.cta.icon || 'fa-arrow-right')"></i>
                                                            <span x-text="m.cta.label"></span>
                                                        </a>
                                                    </template>
                                                    {{-- Footer in-card style WhatsApp : timestamp + receipt --}}
                                                    <div class="acard-footer">
                                                        <span x-text="formatTime(m.created_at)"></span>
                                                        <template x-if="m.mine && m.read_by_others">
                                                            <i class="fas fa-check-double ms-msg-receipt read"></i>
                                                        </template>
                                                        <template x-if="m.mine && !m.read_by_others">
                                                            <i class="fas fa-check ms-msg-receipt"></i>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="m.type === 'action_card' && !m.payload?.kind">
                                                {{-- Legacy format (notif workflow) --}}
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
                                                <div :class="['ms-msg', m.mine ? 'mine' : 'theirs', m.pending ? 'pending' : '']">
                                                    <span class="ms-msg-text" x-text="m.body"></span>
                                                    <span class="ms-msg-time-inline">
                                                        <span x-text="formatTime(m.created_at)"></span>
                                                        <template x-if="m.mine && m.read_by_others">
                                                            <i class="fas fa-check-double ms-msg-receipt read" title="Lu"></i>
                                                        </template>
                                                        <template x-if="m.mine && !m.read_by_others">
                                                            <i class="fas fa-check ms-msg-receipt" title="Envoyé"></i>
                                                        </template>
                                                    </span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                    </template>
                                </div>
                            </template>
                            {{-- "X new messages" banner — anti auto-scroll, respecte la lecture en cours --}}
                            <button type="button" class="ms-new-banner" x-show="newCount > 0" @click="scrollBottom(true); newCount = 0">
                                <i class="fas fa-arrow-down me-1"></i>
                                <span x-text="newCount + ' nouveau' + (newCount > 1 ? 'x' : '') + ' message' + (newCount > 1 ? 's' : '')"></span>
                            </button>
                        </div>

                        <form class="ms-composer" @submit.prevent="sendMessage()">
                            <div class="ms-composer-attach" @click.outside="attachOpen = false">
                                <button type="button" class="ms-attach-btn" @click="attachOpen = !attachOpen" title="Partager une inscription ou un paiement" aria-label="Partager">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                                <div class="ms-attach-menu" x-show="attachOpen" x-cloak x-transition>
                                    <button type="button" class="ms-attach-item" @click="openPicker('inscription'); attachOpen = false">
                                        <i class="fas fa-user-graduate"></i>
                                        <span>Partager une inscription</span>
                                    </button>
                                    <button type="button" class="ms-attach-item" @click="openPicker('paiement'); attachOpen = false">
                                        <i class="fas fa-cash-register"></i>
                                        <span>Partager un paiement</span>
                                    </button>
                                </div>
                            </div>
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

        {{-- Modal nouveau DM — design premium --}}
        <div class="modal fade ms-modal" id="newDmModal" tabindex="-1" aria-hidden="true" aria-labelledby="newDmModalLabel">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="ms-modal-icon"><i class="fas fa-pen"></i></div>
                        <div class="ms-modal-title-block">
                            <h5 id="newDmModalLabel">Nouveau message</h5>
                            <p>Démarre une conversation privée avec un membre de l'équipe</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="ms-search-wrap">
                            <i class="fas fa-search"></i>
                            <input
                                type="search"
                                class="ms-modal-search-input"
                                placeholder="Rechercher un utilisateur par nom ou email…"
                                x-model="dmQuery"
                                @input.debounce.300ms="searchUsers()"
                                x-ref="dmSearchInput"
                                aria-label="Rechercher un utilisateur"
                            >
                        </div>
                        <div class="ms-modal-results">
                            <template x-for="u in dmResults" :key="u.id">
                                <div class="ms-search-result" @click="startDm(u)" role="button" tabindex="0" @keydown.enter="startDm(u)">
                                    <div class="ms-search-avatar" x-text="(u.name || '?').slice(0, 2).toUpperCase()"></div>
                                    <div class="ms-search-info">
                                        <strong x-text="u.name"></strong>
                                        <small x-text="u.email"></small>
                                    </div>
                                    <i class="fas fa-arrow-right ms-search-result-arrow"></i>
                                </div>
                            </template>
                            <div class="ms-modal-empty" x-show="dmQuery.length >= 2 && dmResults.length === 0">
                                <i class="far fa-frown"></i>
                                <div class="ms-empty-title">Aucun résultat</div>
                                <div class="ms-empty-hint">Essaie un autre nom ou email.</div>
                            </div>
                            <div class="ms-modal-empty" x-show="dmQuery.length < 2">
                                <i class="far fa-user-circle"></i>
                                <div class="ms-empty-title">Tape au moins 2 caractères</div>
                                <div class="ms-empty-hint">La recherche couvre nom et email des membres actifs.</div>
                            </div>
                        </div>
                    </div>
                    <div class="ms-modal-footer">
                        <span><kbd>Entrée</kbd> pour ouvrir la conversation</span>
                        <span><kbd>Échap</kbd> pour fermer</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal picker — partager inscription/paiement --}}
        <div class="modal fade ms-modal" id="pickerModal" tabindex="-1" aria-hidden="true" aria-labelledby="pickerModalLabel">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="ms-modal-icon"><i class="fas" :class="pickerKind === 'paiement' ? 'fa-cash-register' : 'fa-user-graduate'"></i></div>
                        <div class="ms-modal-title-block">
                            <h5 id="pickerModalLabel" x-text="pickerKind === 'paiement' ? 'Partager un paiement' : 'Partager une inscription'"></h5>
                            <p>La carte sera envoyée dans la conversation active avec un CTA contextuel.</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="ms-search-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" class="ms-modal-search-input"
                                   x-model="pickerQuery"
                                   x-ref="pickerInput"
                                   @input.debounce.250="searchPicker()"
                                   :placeholder="pickerKind === 'paiement' ? 'Nom étudiant, matricule ou référence…' : 'Nom étudiant ou matricule…'"
                                   aria-label="Rechercher">
                        </div>
                        <div class="ms-modal-results">
                            <template x-for="item in pickerItems" :key="item.id">
                                <div class="ms-picker-result" @click="confirmShare(item)" role="button" tabindex="0" @keydown.enter="confirmShare(item)">
                                    <div class="ms-search-avatar" x-text="(item.etudiant || '?').slice(0, 2).toUpperCase()"></div>
                                    <div class="ms-search-info">
                                        <strong x-text="item.etudiant"></strong>
                                        <small>
                                            <span x-text="item.matricule || ''"></span>
                                            <template x-if="pickerKind === 'inscription'">
                                                <span> · <span x-text="item.classe"></span></span>
                                            </template>
                                            <template x-if="pickerKind === 'paiement'">
                                                <span> · <span x-text="formatXof(item.montant)"></span></span>
                                            </template>
                                        </small>
                                        <div class="ms-picker-meta">
                                            <template x-if="pickerKind === 'inscription'">
                                                <span class="ms-picker-status" :class="item.workflow_step === 'etudiant_cree' ? 'ms-picker-status--ok' : 'ms-picker-status--pending'" x-text="item.workflow_label || '—'"></span>
                                            </template>
                                            <template x-if="pickerKind === 'paiement'">
                                                <span class="ms-picker-status" :class="item.is_validated ? 'ms-picker-status--ok' : 'ms-picker-status--pending'" x-text="item.is_validated ? 'Validé' : 'En attente'"></span>
                                            </template>
                                        </div>
                                    </div>
                                    <i class="fas fa-arrow-right ms-search-result-arrow"></i>
                                </div>
                            </template>
                            <div class="ms-modal-empty" x-show="!pickerLoading && pickerItems.length === 0 && pickerQuery.length >= 1">
                                <i class="far fa-frown"></i>
                                <div class="ms-empty-title">Aucun résultat</div>
                                <div class="ms-empty-hint">Essaie un autre nom ou matricule.</div>
                            </div>
                            <div class="ms-modal-empty" x-show="pickerItems.length === 0 && pickerQuery.length === 0">
                                <i class="far fa-keyboard"></i>
                                <div class="ms-empty-title">Tape pour rechercher</div>
                                <div class="ms-empty-hint">Ou laisse vide pour voir les <span x-text="pickerKind === 'paiement' ? 'paiements' : 'inscriptions'"></span> récents.</div>
                            </div>
                        </div>
                    </div>
                    <div class="ms-modal-footer">
                        <span><kbd>Entrée</kbd> pour partager</span>
                        <span><kbd>Échap</kbd> pour fermer</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@php
    $conversationsPayload = $conversations->map(fn ($c) => [
        'id' => $c->id,
        'type' => $c->type,
        'title' => $c->title,
        'last_message_at' => $c->last_message_at?->toIso8601String(),
        'last_message' => \App\Services\ChatMessagePreview::forMessage($c->lastMessage),
        'participants' => $c->participants
            ->where('id', '!=', auth()->id())
            ->map(fn ($p) => \App\Services\ChatPresenceProjector::project($p))
            ->values(),
    ]);
@endphp
<script>
function messagesPage() {
    return {
        tab: 'all',
        conversations: @json($conversationsPayload),
        activeConvo: null,
        activeOther: null,
        messages: [],
        draft: '',
        sending: false,
        notifications: [],
        notifPollInterval: null,
        msgPollInterval: null,
        convPollInterval: null,
        clockInterval: null,
        now: Date.now(),
        seenNotifIds: new Set(),
        seenLastMessageIds: new Map(), // convId → lastMessage.id (pour diff)
        dmQuery: '',
        dmResults: [],
        dmModal: null,
        newCount: 0,           // "X new messages" banner counter
        atBottom: true,
        csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        // === Action cards ===
        attachOpen: false,
        pickerKind: 'inscription',
        pickerQuery: '',
        pickerItems: [],
        pickerLoading: false,
        pickerModal: null,

        init() {
            this.conversations.forEach(c => {
                if (c.last_message?.id) this.seenLastMessageIds.set(c.id, c.last_message.id);
            });
            this.loadNotifications(true);
            this.notifPollInterval = setInterval(() => this.loadNotifications(false), 30000);
            this.convPollInterval = setInterval(() => this.refreshConversations(), 30000);
            this.clockInterval = setInterval(() => { this.now = Date.now(); }, 60000);
        },

        /**
         * Wrapper fetch unifié : check r.ok, parse JSON safely, throw AppError sur échec
         * avec le message serveur en clair. Toast affiché sauf si silent=true.
         */
        async apiRequest(url, opts = {}, { silent = false } = {}) {
            const headers = { Accept: 'application/json', ...(opts.headers || {}) };
            if (opts.method && opts.method !== 'GET' && !headers['Content-Type']) {
                headers['Content-Type'] = 'application/json';
            }
            if (opts.method && opts.method !== 'GET') {
                headers['X-CSRF-TOKEN'] = this.csrf;
            }
            const r = await fetch(url, { ...opts, headers }).catch((e) => {
                throw new Error('Connexion impossible. Vérifie ta connexion réseau.');
            });
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
                const msg = data.message || data.error || (
                    r.status === 403 ? 'Action non autorisée.' :
                    r.status === 404 ? 'Ressource introuvable.' :
                    r.status === 422 ? 'Action refusée par le serveur.' :
                    `Erreur ${r.status}.`
                );
                if (!silent) window.showToast?.(msg, 'error');
                const err = new Error(msg);
                err.status = r.status;
                err.data = data;
                throw err;
            }
            return data;
        },

        // Groupement messages par sender consécutif < 5min (anti-clutter — finding research 2026)
        get groupedMessages() {
            const blocks = [];
            let currentGroup = null;
            let currentDayKey = null;
            for (const m of this.messages) {
                const d = new Date(m.created_at);
                const t = d.getTime();
                const dayKey = d.toISOString().slice(0, 10);

                // Date divider quand on passe à un nouveau jour
                if (dayKey !== currentDayKey) {
                    blocks.push({ kind: 'divider', dayKey, label: this.formatRelativeDate(m.created_at) });
                    currentDayKey = dayKey;
                    currentGroup = null;
                }

                if (currentGroup
                    && currentGroup.sender_id === m.sender_id
                    && currentGroup.msgType === m.type
                    && (t - currentGroup.last_t) < 5 * 60 * 1000) {
                    currentGroup.items.push(m);
                    currentGroup.last_at = m.created_at;
                    currentGroup.last_t = t;
                } else {
                    currentGroup = {
                        kind: 'group',
                        sender_id: m.sender_id,
                        sender_name: m.sender_name,
                        mine: !!m.mine,
                        msgType: m.type,
                        items: [m],
                        last_at: m.created_at,
                        last_t: t,
                    };
                    blocks.push(currentGroup);
                }
            }
            return blocks;
        },

        /**
         * Label relatif d'une date pour les dividers (Aujourd'hui / Hier / Mardi 28 mars 2026).
         * Recompute au tick de this.now (reactive).
         */
        formatRelativeDate(iso) {
            const d = new Date(iso);
            const today = new Date(this.now);
            today.setHours(0, 0, 0, 0);
            const target = new Date(d);
            target.setHours(0, 0, 0, 0);
            const diffDays = Math.round((today - target) / (1000 * 60 * 60 * 24));
            if (diffDays === 0) return "Aujourd'hui";
            if (diffDays === 1) return 'Hier';
            if (diffDays > 1 && diffDays < 7) {
                return d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
            }
            return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
        },

        /**
         * Label de présence : "En ligne" / "Vu il y a X" / "Vu hier" / "Vu le 28 mars".
         */
        formatPresence(participant) {
            if (!participant) return '';
            if (participant.is_online) return 'En ligne';
            if (!participant.last_seen_at) return '';
            const t = new Date(participant.last_seen_at).getTime();
            const diff = (this.now - t) / 60000;
            if (diff < 60) return 'Vu il y a ' + Math.max(1, Math.floor(diff)) + ' min';
            if (diff < 1440) return 'Vu il y a ' + Math.floor(diff / 60) + ' h';
            const d = new Date(participant.last_seen_at);
            const today = new Date(this.now);
            today.setHours(0, 0, 0, 0);
            const target = new Date(d);
            target.setHours(0, 0, 0, 0);
            const diffDays = Math.round((today - target) / 86400000);
            if (diffDays === 1) return 'Vu hier';
            if (diffDays < 7) return 'Vu ' + d.toLocaleDateString('fr-FR', { weekday: 'long' });
            return 'Vu le ' + d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
        },

        async openConvo(c) {
            this.activeConvo = c;
            this.activeOther = (c.participants || [])[0] || null;
            this.newCount = 0;
            const r = await fetch(`/messages/conversations/${c.id}`, { headers: { Accept: 'application/json' } });
            const data = await r.json();
            this.messages = data.messages;
            // refresh activeOther avec les données enrichies de show() (presence à jour)
            if (data.conversation?.participants?.[0]) this.activeOther = data.conversation.participants[0];
            this.atBottom = true;
            this.$nextTick(() => this.scrollBottom(false));
            if (this.msgPollInterval) clearInterval(this.msgPollInterval);
            this.msgPollInterval = setInterval(() => this.refreshMessages(), 30000);
        },

        async refreshMessages() {
            if (!this.activeConvo) return;
            try {
                const r = await fetch(`/messages/conversations/${this.activeConvo.id}`, { headers: { Accept: 'application/json' } });
                const data = await r.json();
                // refresh activeOther presence (online/last_seen) à chaque tick
                if (data.conversation?.participants?.[0]) this.activeOther = data.conversation.participants[0];
                const knownIds = new Set(this.messages.map(m => m.id));
                const incoming = data.messages.filter(m => !knownIds.has(m.id));
                // Toujours sync messages pour mettre à jour read_by_others (les receipts changent même sans nouveau msg)
                this.messages = data.messages;
                if (incoming.length === 0) return;
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
                const m = await this.apiRequest(`/messages/conversations/${this.activeConvo.id}/messages`, {
                    method: 'POST',
                    body: JSON.stringify({ body }),
                });
                const idx = this.messages.findIndex(x => x.id === tempId);
                if (idx >= 0) this.messages[idx] = { ...m, type: 'text', payload: null, pending: false };
                this.bumpConvPreview(this.activeConvo.id, { type: 'text', body, preview: body });
            } catch (e) {
                // Garde le message visible en mode dégradé pour permettre retry.
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
            const t = new Date(iso).getTime();
            const diff = (this.now - t) / 60000; // dépend de this.now reactive → re-render auto chaque minute
            if (diff < 1) return 'à l\'instant';
            if (diff < 60) return Math.floor(diff) + ' min';
            const d = new Date(iso);
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

        async loadNotifications(silent = false) {
            try {
                const r = await fetch('/messages/notifications', { headers: { Accept: 'application/json' } });
                const data = await r.json();
                if (!silent) {
                    // Diff : pour chaque nouvelle notif → toast cliquable
                    data.items.forEach(n => {
                        if (this.seenNotifIds.has(n.id)) return;
                        this.seenNotifIds.add(n.id);
                        const label = n.data?.label || this.formatType(n.data?.type) || 'Nouvelle notification';
                        const actor = n.data?.actor_name ? n.data.actor_name + ' · ' : '';
                        const url = n.data?.action_url || null;
                        this.showActionToast(actor + label, 'info', url ? () => { window.location.href = url; } : null);
                    });
                }
                data.items.forEach(n => this.seenNotifIds.add(n.id));
                this.notifications = data.items;
            } catch (e) { /* silent */ }
        },

        /** Polling sidebar : refresh la liste des conversations + détecte les nouveaux messages dans des convs inactives → toast cliquable. */
        async refreshConversations() {
            try {
                const r = await fetch('/messages/conversations-list', { headers: { Accept: 'application/json' } });
                const data = await r.json();

                // Diff : message id différent dans une conv non-active ⇒ nouveau message
                data.items.forEach(c => {
                    const last = c.last_message;
                    const seen = this.seenLastMessageIds.get(c.id);
                    if (!last) return;
                    if (seen !== last.id && this.activeConvo?.id !== c.id && last.sender_id !== -1) {
                        const sender = c.title || c.participants?.[0]?.name || 'Conversation';
                        const preview = last.preview || last.body || '';
                        this.showActionToast(`${sender} · ${preview}`, 'info', () => this.openConvo(c));
                    }
                    this.seenLastMessageIds.set(c.id, last.id);
                });

                this.conversations = data.items;
            } catch (e) { /* silent */ }
        },

        /** Toast cliquable réutilisable. Si onClick fourni → cursor pointer + click trigger. */
        showActionToast(message, type = 'info', onClick = null, duration = 6000) {
            if (typeof window.showToast !== 'function') return;
            window.showToast(message, type, duration);
            if (!onClick) return;
            // Attache onClick au dernier toast créé
            const container = document.getElementById('ii-common-toast-container');
            const toast = container?.lastElementChild;
            if (!toast) return;
            toast.style.cursor = 'pointer';
            toast.addEventListener('click', (e) => {
                if (e.target.closest('button')) return; // ne pas trigger sur le close button
                onClick();
                toast.remove();
            });
        },

        async markRead(id) {
            await fetch(`/messages/notifications/${id}/read`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
            });
            this.notifications = this.notifications.filter(n => n.id !== id);
        },

        openNewDm() {
            const el = document.getElementById('newDmModal');
            if (!this.dmModal) this.dmModal = new bootstrap.Modal(el);
            this.dmQuery = '';
            this.dmResults = [];
            this.dmModal.show();
            // Focus auto sur le champ search (a11y + UX)
            el.addEventListener('shown.bs.modal', () => this.$refs.dmSearchInput?.focus(), { once: true });
        },

        async searchUsers() {
            if (this.dmQuery.length < 2) { this.dmResults = []; return; }
            const r = await fetch(`/messages/users/search?q=${encodeURIComponent(this.dmQuery)}`, { headers: { Accept: 'application/json' } });
            const data = await r.json();
            this.dmResults = data.users;
        },

        async startDm(u) {
            let data;
            try {
                data = await this.apiRequest('/messages/dm/start', {
                    method: 'POST',
                    body: JSON.stringify({ user_id: u.id }),
                });
            } catch (e) {
                return; // toast déjà affiché par apiRequest
            }
            if (!data.conversation_id) {
                window.showToast?.('Réponse serveur invalide.', 'error');
                return;
            }

            this.dmModal.hide();
            const idx = this.conversations.findIndex(c => c.id === data.conversation_id);
            if (idx === -1) {
                this.conversations.unshift({
                    id: data.conversation_id, type: 'dm', title: null,
                    participants: [{ id: u.id, name: u.name, is_online: false, last_seen_at: null }],
                    last_message_at: new Date().toISOString(), last_message: null,
                    unread_count: 0,
                });
            }
            this.openConvo(this.conversations.find(c => c.id === data.conversation_id));
        },

        // === Action cards ===

        openPicker(kind) {
            if (!this.activeConvo) {
                window.showToast?.('Ouvre d\'abord une conversation pour partager.', 'warning');
                return;
            }
            this.pickerKind = kind;
            this.pickerQuery = '';
            this.pickerItems = [];
            const el = document.getElementById('pickerModal');
            if (!this.pickerModal) this.pickerModal = new bootstrap.Modal(el);
            this.pickerModal.show();
            el.addEventListener('shown.bs.modal', () => {
                this.$refs.pickerInput?.focus();
                this.searchPicker();
            }, { once: true });
        },

        async searchPicker() {
            this.pickerLoading = true;
            try {
                const url = `/messages/picker/${this.pickerKind === 'paiement' ? 'paiements' : 'inscriptions'}?q=${encodeURIComponent(this.pickerQuery)}`;
                const r = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await r.json();
                this.pickerItems = data.items || [];
            } catch (e) {
                this.pickerItems = [];
            } finally {
                this.pickerLoading = false;
            }
        },

        async confirmShare(item) {
            if (!this.activeConvo) return;
            const route = this.pickerKind === 'paiement'
                ? `/messages/share/paiement/${item.id}`
                : `/messages/share/inscription/${item.id}`;
            try {
                const r = await fetch(route, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                    body: JSON.stringify({ conversation_id: this.activeConvo.id }),
                });
                if (!r.ok) throw new Error('Share failed');
                this.pickerModal.hide();
                this.bumpConvPreview(this.activeConvo.id, {
                    type: 'action_card',
                    body: null,
                    preview: '📎 ' + (this.pickerKind === 'paiement' ? 'Paiement partagé' : 'Inscription partagée'),
                });
                await this.refreshMessages();
                this.$nextTick(() => this.scrollBottom(true));
                window.showToast?.('Card partagée', 'success', 2500);
            } catch (e) {
                window.showToast?.('Le partage a échoué. Réessaie.', 'error');
            }
        },

        /** Met à jour localement le dernier message d'une conversation (sidebar preview). */
        bumpConvPreview(convId, lastMessage) {
            const idx = this.conversations.findIndex(c => c.id === convId);
            if (idx < 0) return;
            this.conversations[idx] = {
                ...this.conversations[idx],
                last_message: lastMessage,
                last_message_at: new Date().toISOString(),
            };
            const [c] = this.conversations.splice(idx, 1);
            this.conversations.unshift(c);
        },

        // Helpers acard rendering

        formatXof(n) {
            if (n === null || n === undefined || isNaN(n)) return '—';
            return new Intl.NumberFormat('fr-FR').format(Math.round(Number(n))) + ' FCFA';
        },

        formatDate(iso) {
            if (!iso) return '—';
            const d = new Date(iso);
            return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
        },

    };
}
</script>
@endsection
