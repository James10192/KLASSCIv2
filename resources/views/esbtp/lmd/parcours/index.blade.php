@extends('layouts.app')

@section('title', 'Parcours LMD | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD Parcours Index — Premium Redesign
       Prefix: lp- (lmd-parcours)
       ══════════════════════════════════════════════ */

    .lp-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }

    /* ── Hero ── */
    .lp-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.5rem;
        overflow: hidden;
        animation: lp-fadeDown .5s ease-out;
    }
    .lp-hero::before {
        content: '';
        position: absolute;
        top: -60%;
        right: -10%;
        width: 420px;
        height: 420px;
        background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
        pointer-events: none;
    }
    .lp-hero::after {
        content: '';
        position: absolute;
        bottom: -40%;
        left: 5%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%);
        pointer-events: none;
    }
    .lp-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        position: relative;
        z-index: 1;
    }
    .lp-hero-left { display: flex; align-items: center; gap: 1rem; }
    .lp-hero-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        border: 1px solid rgba(255,255,255,.15);
        flex-shrink: 0;
    }
    .lp-hero-info h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem; color: #fff; letter-spacing: -.02em; }
    .lp-hero-info p { margin: 0; opacity: .8; font-size: .88rem; }
    .lp-hero-actions { display: flex; gap: .5rem; position: relative; z-index: 1; }
    .lp-hero-btn {
        display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1.1rem;
        border-radius: 10px; font-size: .84rem; font-weight: 600;
        border: 1.5px solid rgba(255,255,255,.3); color: #fff;
        background: rgba(255,255,255,.08); text-decoration: none; transition: all .2s;
        backdrop-filter: blur(4px); cursor: pointer;
    }
    .lp-hero-btn:hover { background: rgba(255,255,255,.18); color: #fff; text-decoration: none; }
    .lp-hero-btn--solid { background: #fff; color: #0453cb; border-color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.12); }
    .lp-hero-btn--solid:hover { background: #edf2fc; color: #0453cb; }

    /* KPIs inside hero */
    .lp-hero-kpis {
        display: flex; gap: .75rem; margin-top: 1.5rem;
        position: relative; z-index: 1; flex-wrap: wrap;
    }
    .lp-kpi {
        flex: 1; min-width: 150px;
        background: rgba(255,255,255,.1); backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.15); border-radius: 12px;
        padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem;
        transition: background .2s;
    }
    .lp-kpi:hover { background: rgba(255,255,255,.15); }
    .lp-kpi-icon {
        width: 38px; height: 38px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; flex-shrink: 0;
    }
    .lp-kpi--domaines .lp-kpi-icon  { background: rgba(255,255,255,.18); color: #fff; }
    .lp-kpi--mentions .lp-kpi-icon  { background: rgba(16,185,129,.25); color: #6ee7b7; }
    .lp-kpi--parcours .lp-kpi-icon  { background: rgba(129,140,248,.25); color: #a5b4fc; }
    .lp-kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1; color: #fff; }
    .lp-kpi-label { font-size: .75rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    /* ── Action bar ── */
    .lp-action-bar {
        display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.25rem;
        animation: lp-fadeUp .45s ease-out .1s both;
    }
    .lp-add-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .5rem 1rem; border-radius: 9px; font-size: .84rem;
        font-weight: 600; border: none; cursor: pointer; transition: all .2s;
        text-decoration: none;
    }
    .lp-add-btn--domaine { background: #0453cb; color: #fff; }
    .lp-add-btn--domaine:hover { background: #0340a0; }
    .lp-add-btn--mention { background: #fff; color: #059669; border: 1.5px solid #d1fae5; }
    .lp-add-btn--mention:hover { background: #ecfdf5; border-color: #10b981; }
    .lp-add-btn--parcours { background: #fff; color: #4338ca; border: 1.5px solid #e0e7ff; }
    .lp-add-btn--parcours:hover { background: #eef2ff; border-color: #6366f1; }

    /* ── Accordion ── */
    .lp-accordion {
        display: flex; flex-direction: column; gap: .75rem;
        animation: lp-fadeUp .45s ease-out .2s both;
    }
    [x-cloak] { display: none !important; }

    /* Domaine card */
    .lp-domaine-card {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        overflow: hidden; transition: all .25s ease;
    }
    .lp-domaine-card:hover { box-shadow: 0 4px 16px rgba(4,83,203,.06); }

    .lp-domaine-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 1rem 1.25rem; cursor: pointer; user-select: none;
        border-bottom: 1px solid transparent; transition: all .2s;
    }
    .lp-domaine-header:hover { background: #f8fbff; }
    .lp-domaine-header.lp-open { border-bottom-color: #e8ecf1; background: #f8fbff; }
    .lp-domaine-header-left { display: flex; align-items: center; gap: .75rem; }

    .lp-code-badge {
        display: inline-flex; align-items: center; justify-content: center;
        padding: .2rem .6rem; border-radius: 6px; font-size: .72rem;
        font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
    }
    .lp-code-badge--domaine { background: rgba(4,83,203,.1); color: #0453cb; }
    .lp-code-badge--mention { background: rgba(16,185,129,.1); color: #059669; }
    .lp-code-badge--parcours { background: rgba(99,102,241,.1); color: #4338ca; }

    .lp-item-name { font-size: 1rem; font-weight: 600; color: #1e293b; }
    .lp-item-meta { font-size: .78rem; color: #94a3b8; margin-top: .1rem; }

    .lp-chevron {
        transition: transform .25s ease; color: #94a3b8; font-size: .8rem;
    }
    .lp-open .lp-chevron { transform: rotate(180deg); }

    /* Action btns */
    .lp-actions { display: flex; gap: .25rem; }
    .lp-act {
        width: 30px; height: 30px; display: inline-flex; align-items: center;
        justify-content: center; border-radius: 7px; border: 1px solid transparent;
        background: transparent; color: #94a3b8; cursor: pointer;
        transition: all .15s; font-size: .78rem;
    }
    .lp-act:hover { background: #f1f5f9; color: #1e293b; border-color: #e2e8f0; }
    .lp-act--delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    /* Mention rows */
    .lp-mention-item { border-bottom: 1px solid #f1f5f9; }
    .lp-mention-item:last-child { border-bottom: none; }
    .lp-mention-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: .75rem 1.25rem .75rem 2.25rem; cursor: pointer;
        user-select: none; transition: background .15s;
    }
    .lp-mention-header:hover { background: #f8fbff; }
    .lp-mention-header-left { display: flex; align-items: center; gap: .6rem; }
    .lp-mention-name { font-size: .92rem; font-weight: 600; color: #1e293b; }
    .lp-mention-count {
        font-size: .72rem; color: #94a3b8; background: #f1f5f9;
        padding: .15rem .5rem; border-radius: 20px; font-weight: 600;
    }

    /* Parcours rows */
    .lp-parcours-list { background: #fafbfc; border-top: 1px solid #f1f5f9; }
    .lp-parcours-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: .6rem 1.25rem .6rem 3.5rem; border-bottom: 1px solid #f1f5f9;
        transition: background .15s;
    }
    .lp-parcours-row:last-child { border-bottom: none; }
    .lp-parcours-row:hover { background: #f0f5ff; }
    .lp-parcours-info { display: flex; align-items: center; gap: .6rem; }
    .lp-parcours-name { font-size: .88rem; font-weight: 500; color: #1e293b; }
    .lp-parcours-responsable { font-size: .76rem; color: #94a3b8; margin-top: .1rem; }

    /* ── Empty state ── */
    .lp-empty-card {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
    }
    .lp-empty { text-align: center; padding: 4rem 2rem; }
    .lp-empty-icon {
        width: 76px; height: 76px; border-radius: 20px; background: #f1f5f9;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 2rem; color: #cbd5e1; margin-bottom: 1.15rem;
    }
    .lp-empty-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: .4rem; }
    .lp-empty-text { font-size: .88rem; color: #94a3b8; margin-bottom: 1.25rem; max-width: 380px; margin-left: auto; margin-right: auto; line-height: 1.5; }
    .lp-empty-btn {
        display: inline-flex; align-items: center; gap: .4rem; padding: .6rem 1.2rem;
        background: #0453cb; color: #fff; border-radius: 10px; font-size: .85rem;
        font-weight: 600; text-decoration: none; transition: background .2s;
        border: none; cursor: pointer;
    }
    .lp-empty-btn:hover { background: #0340a0; color: #fff; }

    /* ── Premium Modals ── */
    .lmd-modal .modal-content {
        border-radius: 18px; border: none;
        box-shadow: 0 25px 80px rgba(0,0,0,.18), 0 8px 24px rgba(4,83,203,.08);
        overflow: hidden;
    }
    .lmd-modal .modal-header { position: relative; padding: 0; border: none; }
    .lp-modal-hero {
        padding: 1.75rem 2rem 1.5rem;
        color: #fff; position: relative; overflow: hidden;
    }
    .lp-modal-hero::before {
        content: ''; position: absolute; top: -50%; right: -15%;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,.08) 0%, transparent 70%);
        pointer-events: none;
    }
    .lp-modal-hero--domaine { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%); }
    .lp-modal-hero--mention { background: linear-gradient(135deg, #065f46 0%, #059669 50%, #34d399 100%); }
    .lp-modal-hero--parcours { background: linear-gradient(135deg, #312e81 0%, #4338ca 50%, #6366f1 100%); }

    .lp-modal-hero-top {
        display: flex; align-items: center; justify-content: space-between;
        position: relative; z-index: 1;
    }
    .lp-modal-hero-left { display: flex; align-items: center; gap: .85rem; }
    .lp-modal-icon {
        width: 46px; height: 46px; border-radius: 12px;
        background: rgba(255,255,255,.15); backdrop-filter: blur(6px);
        border: 1px solid rgba(255,255,255,.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; color: #fff; flex-shrink: 0;
    }
    .lp-modal-title { font-size: 1.2rem; font-weight: 700; margin: 0; color: #fff; }
    .lp-modal-subtitle { font-size: .8rem; opacity: .7; margin-top: .15rem; }
    .lmd-modal .btn-close {
        filter: brightness(0) invert(1); opacity: .7;
        position: relative; z-index: 2;
    }
    .lmd-modal .btn-close:hover { opacity: 1; }

    .lmd-modal .modal-body { padding: 1.75rem 2rem; }

    /* Field groups */
    .lp-field-group {
        background: #f8fafc; border-radius: 12px; border: 1px solid #e8ecf1;
        padding: 1.25rem; margin-bottom: 1rem;
    }
    .lp-field-group:last-child { margin-bottom: 0; }
    .lp-field-group-title {
        font-size: .72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .06em; margin-bottom: .85rem; display: flex;
        align-items: center; gap: .4rem;
    }
    .lp-field-group-title--domaine { color: #0453cb; }
    .lp-field-group-title--mention { color: #059669; }
    .lp-field-group-title--parcours { color: #4338ca; }
    .lp-field-group-title i { font-size: .65rem; }

    .lp-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem 1.25rem; }
    .lp-field-full { grid-column: 1 / -1; }

    .lmd-modal label {
        font-size: .82rem; font-weight: 600; color: #334155;
        margin-bottom: .3rem; display: flex; align-items: center; gap: .3rem;
    }
    .lmd-modal label i { font-size: .7rem; color: #94a3b8; }
    .lmd-modal .form-control,
    .lmd-modal .form-select {
        border-radius: 10px; border: 1.5px solid #e2e8f0; padding: .55rem .85rem;
        font-size: .88rem; transition: all .2s; background: #fff;
    }
    .lmd-modal .form-control:focus,
    .lmd-modal .form-select:focus {
        border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08); background: #fff;
    }
    .lmd-modal textarea.form-control { min-height: 80px; resize: vertical; }
    .lmd-modal .form-text { font-size: .76rem; color: #94a3b8; margin-top: .25rem; }

    /* Modal footer */
    .lmd-modal .modal-footer {
        border-top: 1px solid #e8ecf1; padding: 1rem 2rem;
        background: #fafbfc; display: flex; gap: .5rem; justify-content: flex-end;
    }
    .lp-modal-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .55rem 1.2rem; border-radius: 10px; font-size: .85rem;
        font-weight: 600; border: none; cursor: pointer; transition: all .2s;
    }
    .lp-modal-btn--cancel { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
    .lp-modal-btn--cancel:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .lp-modal-btn--domaine { background: #0453cb; color: #fff; box-shadow: 0 2px 8px rgba(4,83,203,.2); }
    .lp-modal-btn--domaine:hover { background: #0340a0; }
    .lp-modal-btn--mention { background: #059669; color: #fff; box-shadow: 0 2px 8px rgba(5,150,105,.2); }
    .lp-modal-btn--mention:hover { background: #047857; }
    .lp-modal-btn--parcours { background: #4338ca; color: #fff; box-shadow: 0 2px 8px rgba(67,56,202,.2); }
    .lp-modal-btn--parcours:hover { background: #3730a3; }

    /* Modal animation */
    .lmd-modal.fade .modal-dialog {
        transform: translateY(20px) scale(.98); transition: transform .25s ease-out, opacity .2s;
    }
    .lmd-modal.show .modal-dialog { transform: translateY(0) scale(1); }

    @media (max-width: 768px) {
        .lp-field-row { grid-template-columns: 1fr; }
        .lmd-modal .modal-body { padding: 1.25rem; }
        .lp-modal-hero { padding: 1.25rem 1.25rem 1rem; }
    }

    /* ── Animations ── */
    @keyframes lp-fadeDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes lp-fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .lp-hero { padding: 1.5rem; border-radius: 14px; }
        .lp-hero-top { flex-direction: column; }
        .lp-hero-kpis { flex-direction: column; }
        .lp-mention-header { padding-left: 1.25rem; }
        .lp-parcours-row { padding-left: 2rem; }
    }
</style>
@endpush

@section('page_title', 'Parcours LMD')

@section('content')
<div class="lp-page">

    {{-- ══ Hero ══ --}}
    @php
        $totalDomaines = $domaines->count();
        $totalMentions = $domaines->sum(fn($d) => $d->mentions->count());
        $totalParcours = $domaines->sum(fn($d) => $d->mentions->sum(fn($m) => $m->parcours->count()));
    @endphp

    <div class="lp-hero">
        <div class="lp-hero-top">
            <div class="lp-hero-left">
                <div class="lp-hero-icon"><i class="fas fa-sitemap"></i></div>
                <div class="lp-hero-info">
                    <h1>Parcours LMD</h1>
                    <p>Domaines, Mentions et Parcours — organisation hiérarchique</p>
                </div>
            </div>
            <div class="lp-hero-actions">
                <a href="{{ route('esbtp.lmd.parcours-domain.index') }}" class="lp-hero-btn">
                    <i class="fas fa-sync-alt"></i>Actualiser
                </a>
            </div>
        </div>

        <div class="lp-hero-kpis">
            <div class="lp-kpi lp-kpi--domaines">
                <div class="lp-kpi-icon"><i class="fas fa-globe-africa"></i></div>
                <div>
                    <div class="lp-kpi-value">{{ $totalDomaines }}</div>
                    <div class="lp-kpi-label">Domaines</div>
                </div>
            </div>
            <div class="lp-kpi lp-kpi--mentions">
                <div class="lp-kpi-icon"><i class="fas fa-bookmark"></i></div>
                <div>
                    <div class="lp-kpi-value">{{ $totalMentions }}</div>
                    <div class="lp-kpi-label">Mentions</div>
                </div>
            </div>
            <div class="lp-kpi lp-kpi--parcours">
                <div class="lp-kpi-icon"><i class="fas fa-route"></i></div>
                <div>
                    <div class="lp-kpi-value">{{ $totalParcours }}</div>
                    <div class="lp-kpi-label">Parcours</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @foreach(['success' => 'check-circle', 'error' => 'exclamation-circle', 'info' => 'info-circle'] as $type => $icon)
        @if(session($type))
            <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show" role="alert" style="border-radius:10px;">
                <i class="fas fa-{{ $icon }} me-2"></i>{{ session($type) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    {{-- ══ Action Buttons ══ --}}
    <div class="lp-action-bar">
        <button type="button" class="lp-add-btn lp-add-btn--domaine" data-bs-toggle="modal" data-bs-target="#modalDomaine" onclick="resetModal('domaine')">
            <i class="fas fa-plus"></i>Domaine
        </button>
        <button type="button" class="lp-add-btn lp-add-btn--mention" data-bs-toggle="modal" data-bs-target="#modalMention" onclick="resetModal('mention')">
            <i class="fas fa-plus"></i>Mention
        </button>
        <button type="button" class="lp-add-btn lp-add-btn--parcours" data-bs-toggle="modal" data-bs-target="#modalParcours" onclick="resetModal('parcours')">
            <i class="fas fa-plus"></i>Parcours
        </button>
    </div>

    {{-- ══ Accordion: Domaines → Mentions → Parcours ══ --}}
    <div class="lp-accordion" x-data="{ openDomaine: null, openMention: null }">
        @forelse($domaines as $domaine)
            <div class="lp-domaine-card">
                {{-- Level 1: Domaine --}}
                <div class="lp-domaine-header"
                     :class="{ 'lp-open': openDomaine === {{ $domaine->id }} }"
                     @click="openDomaine = openDomaine === {{ $domaine->id }} ? null : {{ $domaine->id }}; if(openDomaine !== {{ $domaine->id }}) openMention = null;">
                    <div class="lp-domaine-header-left">
                        <span class="lp-code-badge lp-code-badge--domaine">{{ $domaine->code }}</span>
                        <div>
                            <div class="lp-item-name">{{ $domaine->name }}</div>
                            <div class="lp-item-meta">
                                {{ $domaine->mentions->count() }} mention(s) &middot; {{ $domaine->mentions->sum(fn($m) => $m->parcours->count()) }} parcours
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:.5rem;">
                        <div class="lp-actions" @click.stop>
                            <button class="lp-act" title="Modifier" onclick="editDomaine({{ json_encode($domaine) }})">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form action="{{ route('esbtp.lmd.domaines.destroy', $domaine) }}" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce domaine et tout son contenu ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="lp-act lp-act--delete" title="Supprimer">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                        <i class="fas fa-chevron-down lp-chevron"></i>
                    </div>
                </div>

                {{-- Domaine body: Mentions --}}
                <div x-show="openDomaine === {{ $domaine->id }}" x-collapse x-cloak>
                    @forelse($domaine->mentions as $mention)
                        <div class="lp-mention-item">
                            <div class="lp-mention-header"
                                 @click="openMention = openMention === {{ $mention->id }} ? null : {{ $mention->id }}">
                                <div class="lp-mention-header-left">
                                    <span class="lp-code-badge lp-code-badge--mention">{{ $mention->code }}</span>
                                    <span class="lp-mention-name">{{ $mention->name }}</span>
                                    <span class="lp-mention-count">{{ $mention->parcours->count() }} parcours</span>
                                </div>
                                <div style="display:flex; align-items:center; gap:.5rem;">
                                    <div class="lp-actions" @click.stop>
                                        <button class="lp-act" title="Modifier" onclick="editMention({{ json_encode($mention) }})">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <form action="{{ route('esbtp.lmd.mentions.destroy', $mention) }}" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette mention et ses parcours ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="lp-act lp-act--delete" title="Supprimer">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <i class="fas fa-chevron-down lp-chevron" style="transition:transform .25s;" :style="openMention === {{ $mention->id }} ? 'transform:rotate(180deg)' : ''"></i>
                                </div>
                            </div>

                            {{-- Parcours --}}
                            <div class="lp-parcours-list" x-show="openMention === {{ $mention->id }}" x-collapse x-cloak>
                                @forelse($mention->parcours as $parcours)
                                    <div class="lp-parcours-row">
                                        <div class="lp-parcours-info">
                                            <span class="lp-code-badge lp-code-badge--parcours">{{ $parcours->code }}</span>
                                            <div>
                                                <div class="lp-parcours-name">{{ $parcours->name }}</div>
                                                @if($parcours->responsable)
                                                    <div class="lp-parcours-responsable">
                                                        <i class="fas fa-user-tie me-1"></i>{{ $parcours->responsable->name ?? $parcours->responsable->nom_complet ?? '-' }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="lp-actions">
                                            <button class="lp-act" title="Créer une classe" onclick="openCreateClasseModal({{ $parcours->id }}, '{{ addslashes($parcours->name) }}')" style="color:#0453cb;">
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                            <button class="lp-act" title="Lier/Délier des classes" onclick="openLinkClassesModal({{ $parcours->id }}, '{{ addslashes($parcours->name) }}')" style="color:#059669;">
                                                <i class="fas fa-link"></i>
                                            </button>
                                            <button class="lp-act" title="Lier/Délier des UEs" onclick="openLinkUesModal({{ $parcours->id }}, '{{ addslashes($parcours->name) }}')" style="color:#4338ca;">
                                                <i class="fas fa-book"></i>
                                            </button>
                                            <button class="lp-act" title="Modifier" onclick="editParcours({{ json_encode($parcours) }})">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <form action="{{ route('esbtp.lmd.parcours.destroy', $parcours) }}" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce parcours ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="lp-act lp-act--delete" title="Supprimer">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Détails parcours : Classes liées + UE par semestre --}}
                                    @if($parcours->classes->isNotEmpty() || $parcours->unitesEnseignement->isNotEmpty())
                                    <div style="padding: 0.5rem 2.25rem 0.75rem 3.5rem; background: #fafbfc; border-bottom: 1px solid #f1f5f9;">
                                        {{-- Classes liées --}}
                                        @if($parcours->classes->isNotEmpty())
                                        <div style="margin-bottom: 0.4rem;">
                                            <span style="font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em;">
                                                <i class="fas fa-layer-group me-1"></i>Classes ({{ $parcours->classes->count() }})
                                            </span>
                                            <div style="display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .25rem;">
                                                @foreach($parcours->classes as $cls)
                                                    <span style="display: inline-block; padding: .15rem .5rem; border-radius: 6px; font-size: .78rem; font-weight: 600; background: #dbeafe; color: #0453cb; border: 1px solid #bfdbfe;">
                                                        {{ $cls->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif

                                        {{-- UE par semestre --}}
                                        @if($parcours->unitesEnseignement->isNotEmpty())
                                        <div>
                                            <span style="font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em;">
                                                <i class="fas fa-book me-1"></i>UE ({{ $parcours->unitesEnseignement->count() }})
                                            </span>
                                            @foreach($parcours->unitesEnseignement->sortBy('pivot.semestre')->groupBy('pivot.semestre') as $sem => $ues)
                                                <div style="margin-top: .3rem;">
                                                    <span style="font-size: .7rem; font-weight: 700; color: #64748b;">S{{ $sem }}</span>
                                                    @foreach($ues->sortBy('pivot.ordre') as $ue)
                                                        <span style="display: inline-block; padding: .1rem .45rem; border-radius: 5px; font-size: .75rem; background: #f0fdf4; color: #059669; border: 1px solid #bbf7d0; margin-left: .2rem;">
                                                            {{ $ue->code }}
                                                            @if($ue->pivot->ordre > 0)<sup style="font-size:.6em; color:#94a3b8;">#{{ $ue->pivot->ordre }}</sup>@endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                    @endif
                                @empty
                                    <div class="lp-parcours-row" style="justify-content:center; color:#94a3b8;">
                                        <em>Aucun parcours dans cette mention</em>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <div style="padding:1rem 2.25rem; color:#94a3b8; font-style:italic; font-size:.85rem;">
                            Aucune mention dans ce domaine
                        </div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="lp-empty-card">
                <div class="lp-empty">
                    <div class="lp-empty-icon"><i class="fas fa-sitemap"></i></div>
                    <div class="lp-empty-title">Aucun domaine LMD enregistré</div>
                    <div class="lp-empty-text">
                        Commencez par créer un domaine académique, puis ajoutez des mentions et des parcours pour structurer votre offre LMD.
                    </div>
                    <button type="button" class="lp-empty-btn" data-bs-toggle="modal" data-bs-target="#modalDomaine" onclick="resetModal('domaine')">
                        <i class="fas fa-plus"></i>Créer un Domaine
                    </button>
                </div>
            </div>
        @endforelse
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- ══ MODALS — Créer Classe + Lier Classes ══ --}}
{{-- ══════════════════════════════════════════════════════════ --}}

{{-- Modal: Créer une classe LMD --}}
<div class="modal fade lmd-modal" id="modalCreateClasse" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="cc_form" method="POST" action="">
                @csrf
                <input type="hidden" name="parcours_id" id="cc_parcours_id">
                <div class="modal-header">
                    <div class="lp-modal-hero w-100" style="background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%);">
                        <div class="lp-modal-hero-top">
                            <div class="lp-modal-hero-left">
                                <div class="lp-modal-icon"><i class="fas fa-plus-circle"></i></div>
                                <div>
                                    <h5 class="lp-modal-title">Nouvelle classe LMD</h5>
                                    <div class="lp-modal-subtitle">Parcours : <strong id="cc_parcours_label">—</strong></div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div id="cc_error" style="display:none; padding:.65rem 1rem; border-radius:10px; background:#fef2f2; color:#dc2626; font-size:.85rem; margin-bottom:1rem; border:1px solid #fecaca;"></div>
                    <div class="lp-field-group">
                        <div class="lp-field-group-title lp-field-group-title--domaine">
                            <i class="fas fa-circle"></i> Informations de la classe
                        </div>
                        <div class="lp-field-row">
                            <div>
                                <label><i class="fas fa-tag"></i> Nom de la classe</label>
                                <input type="text" class="form-control" name="name" required placeholder="Ex: L1 Génie Civil">
                            </div>
                            <div>
                                <label><i class="fas fa-barcode"></i> Code</label>
                                <input type="text" class="form-control" name="code" required placeholder="Ex: L1-GC">
                            </div>
                        </div>
                        <div class="lp-field-row" style="margin-top:.75rem;">
                            <div>
                                <label><i class="fas fa-graduation-cap"></i> Niveau d'études</label>
                                <select class="form-select" name="niveau_etude_id" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach(\App\Models\ESBTPNiveauEtude::whereIn('type', ['Licence', 'Master', 'Doctorat'])->orderBy('name')->get() as $niv)
                                        <option value="{{ $niv->id }}">{{ $niv->name }} ({{ $niv->type }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label><i class="fas fa-calendar"></i> Année universitaire</label>
                                <select class="form-select" name="annee_universitaire_id" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach(\App\Models\ESBTPAnneeUniversitaire::orderByDesc('annee_debut')->get() as $a)
                                        <option value="{{ $a->id }}" {{ ($a->is_current ?? false) ? 'selected' : '' }}>{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="lp-field-row" style="margin-top:.75rem;">
                            <div>
                                <label><i class="fas fa-users"></i> Capacité maximale</label>
                                <input type="number" class="form-control" name="places_totales" value="30" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lp-modal-btn lp-modal-btn--cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="lp-modal-btn lp-modal-btn--domaine" id="cc_submit">
                        <i class="fas fa-plus-circle"></i> <span id="cc_submit_text">Créer la classe</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Lier/Délier des classes --}}
<div class="modal fade lmd-modal" id="modalLinkClasses" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="lp-modal-hero w-100" style="background: linear-gradient(135deg, #065f46 0%, #059669 50%, #34d399 100%);">
                    <div class="lp-modal-hero-top">
                        <div class="lp-modal-hero-left">
                            <div class="lp-modal-icon"><i class="fas fa-link"></i></div>
                            <div>
                                <h5 class="lp-modal-title">Lier des classes</h5>
                                <div class="lp-modal-subtitle">Parcours : <strong id="lc_parcours_label">—</strong></div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <div id="lc_error" style="display:none; padding:.65rem 1rem; border-radius:10px; background:#fef2f2; color:#dc2626; font-size:.85rem; margin-bottom:1rem; border:1px solid #fecaca;">
                    <i class="fas fa-exclamation-triangle me-1"></i>Erreur de chargement. Réessayez.
                </div>

                <div id="lc_loading" style="padding:2rem; text-align:center; color:#94a3b8;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <div style="margin-top:.75rem; font-size:.88rem;">Chargement des classes...</div>
                </div>

                <div id="lc_content" style="display:none;">
                    <p style="font-size:.82rem; color:#64748b; margin-bottom:1rem;">
                        <i class="fas fa-info-circle me-1"></i>Cochez les classes à lier à ce parcours. Décochez pour délier.
                    </p>
                    <div id="lc_checkboxes" style="display:grid; grid-template-columns: 1fr 1fr; gap:.5rem; max-height:350px; overflow-y:auto; padding-right:.25rem;">
                        {{-- Filled dynamically --}}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="lp-modal-btn lp-modal-btn--cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" class="lp-modal-btn lp-modal-btn--mention" id="lc_submit" onclick="saveLinkClasses()">
                    <i class="fas fa-check"></i> <span id="lc_submit_text">Enregistrer</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Lier/Délier des UEs --}}
<div class="modal fade lmd-modal" id="modalLinkUes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="lp-modal-hero lp-modal-hero--parcours w-100">
                    <div class="lp-modal-hero-top">
                        <div class="lp-modal-hero-left">
                            <div class="lp-modal-icon"><i class="fas fa-book"></i></div>
                            <div>
                                <h5 class="lp-modal-title">Lier des UEs</h5>
                                <div class="lp-modal-subtitle">Parcours : <strong id="lu_parcours_label">—</strong></div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
            </div>
            <div class="modal-body" style="padding:1.5rem;">
                <div id="lu_error" style="display:none; padding:.65rem 1rem; border-radius:10px; background:#fef2f2; color:#dc2626; font-size:.85rem; margin-bottom:1rem; border:1px solid #fecaca;">
                    <i class="fas fa-exclamation-triangle me-1"></i>Erreur de chargement.
                </div>
                <div id="lu_loading" style="padding:2rem; text-align:center; color:#94a3b8;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <div style="margin-top:.75rem; font-size:.88rem;">Chargement des UEs...</div>
                </div>
                <div id="lu_content" style="display:none;">
                    <p style="font-size:.82rem; color:#64748b; margin-bottom:1rem;">
                        <i class="fas fa-info-circle me-1"></i>Cochez les UEs à lier. Indiquez le semestre pour chaque UE liée.
                    </p>
                    <div id="lu_checkboxes" style="display:flex; flex-direction:column; gap:.4rem; max-height:400px; overflow-y:auto; padding-right:.25rem;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="lp-modal-btn lp-modal-btn--cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" class="lp-modal-btn lp-modal-btn--parcours" id="lu_submit" onclick="saveLinkUes()">
                    <i class="fas fa-check"></i> <span id="lu_submit_text">Enregistrer</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- ══ MODALS — Domaine / Mention / Parcours ══ --}}
{{-- ══════════════════════════════════════════════════════════ --}}

{{-- Modal Domaine --}}
<div class="modal fade lmd-modal" id="modalDomaine" tabindex="-1" aria-labelledby="modalDomaineLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="formDomaine" method="POST" action="{{ route('esbtp.lmd.domaines.store') }}">
                @csrf
                <input type="hidden" name="_method" id="domaine_method" value="POST">
                <div class="modal-header">
                    <div class="lp-modal-hero lp-modal-hero--domaine w-100">
                        <div class="lp-modal-hero-top">
                            <div class="lp-modal-hero-left">
                                <div class="lp-modal-icon"><i class="fas fa-layer-group"></i></div>
                                <div>
                                    <h5 class="lp-modal-title" id="modalDomaineLabel">Nouveau Domaine</h5>
                                    <div class="lp-modal-subtitle">Définir un domaine académique LMD</div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="lp-field-group">
                        <div class="lp-field-group-title lp-field-group-title--domaine">
                            <i class="fas fa-circle"></i> Informations du domaine
                        </div>
                        <div class="lp-field-row">
                            <div>
                                <label for="domaine_code"><i class="fas fa-hashtag"></i> Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="domaine_code" name="code" required placeholder="Ex: ST, SHS, DEG...">
                                <div class="form-text">Code court du domaine (2-5 caractères)</div>
                            </div>
                            <div>
                                <label for="domaine_name"><i class="fas fa-font"></i> Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="domaine_name" name="name" required placeholder="Ex: Sciences et Technologies">
                            </div>
                            <div class="lp-field-full">
                                <label for="domaine_description"><i class="fas fa-align-left"></i> Description</label>
                                <textarea class="form-control" id="domaine_description" name="description" rows="3" placeholder="Description optionnelle du domaine..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lp-modal-btn lp-modal-btn--cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="lp-modal-btn lp-modal-btn--domaine">
                        <i class="fas fa-save"></i> <span id="domaine_submit_text">Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Mention --}}
<div class="modal fade lmd-modal" id="modalMention" tabindex="-1" aria-labelledby="modalMentionLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="formMention" method="POST" action="{{ route('esbtp.lmd.mentions.store') }}">
                @csrf
                <input type="hidden" name="_method" id="mention_method" value="POST">
                <div class="modal-header">
                    <div class="lp-modal-hero lp-modal-hero--mention w-100">
                        <div class="lp-modal-hero-top">
                            <div class="lp-modal-hero-left">
                                <div class="lp-modal-icon"><i class="fas fa-bookmark"></i></div>
                                <div>
                                    <h5 class="lp-modal-title" id="modalMentionLabel">Nouvelle Mention</h5>
                                    <div class="lp-modal-subtitle">Rattacher une mention à un domaine existant</div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="lp-field-group">
                        <div class="lp-field-group-title lp-field-group-title--mention">
                            <i class="fas fa-circle"></i> Rattachement
                        </div>
                        <div class="lp-field-row">
                            <div class="lp-field-full">
                                <label for="mention_domaine_id"><i class="fas fa-globe-africa"></i> Domaine <span class="text-danger">*</span></label>
                                <select class="form-select" id="mention_domaine_id" name="domaine_id" required>
                                    <option value="">-- Sélectionner un domaine --</option>
                                    @foreach($domaines as $d)
                                        <option value="{{ $d->id }}">{{ $d->code }} — {{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="lp-field-group">
                        <div class="lp-field-group-title lp-field-group-title--mention">
                            <i class="fas fa-circle"></i> Informations de la mention
                        </div>
                        <div class="lp-field-row">
                            <div>
                                <label for="mention_code"><i class="fas fa-hashtag"></i> Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mention_code" name="code" required placeholder="Ex: INFO, GC, MATH...">
                                <div class="form-text">Code court de la mention</div>
                            </div>
                            <div>
                                <label for="mention_name"><i class="fas fa-font"></i> Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mention_name" name="name" required placeholder="Ex: Informatique">
                            </div>
                            <div class="lp-field-full">
                                <label for="mention_description"><i class="fas fa-align-left"></i> Description</label>
                                <textarea class="form-control" id="mention_description" name="description" rows="3" placeholder="Description optionnelle de la mention..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lp-modal-btn lp-modal-btn--cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="lp-modal-btn lp-modal-btn--mention">
                        <i class="fas fa-save"></i> <span id="mention_submit_text">Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Parcours --}}
<div class="modal fade lmd-modal" id="modalParcours" tabindex="-1" aria-labelledby="modalParcoursLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="formParcours" method="POST" action="{{ route('esbtp.lmd.parcours.store') }}">
                @csrf
                <input type="hidden" name="_method" id="parcours_method" value="POST">
                <div class="modal-header">
                    <div class="lp-modal-hero lp-modal-hero--parcours w-100">
                        <div class="lp-modal-hero-top">
                            <div class="lp-modal-hero-left">
                                <div class="lp-modal-icon"><i class="fas fa-route"></i></div>
                                <div>
                                    <h5 class="lp-modal-title" id="modalParcoursLabel">Nouveau Parcours</h5>
                                    <div class="lp-modal-subtitle">Créer un parcours rattaché à une mention</div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    {{-- Groupe 1: Rattachement --}}
                    <div class="lp-field-group">
                        <div class="lp-field-group-title lp-field-group-title--parcours">
                            <i class="fas fa-circle"></i> Rattachement hiérarchique
                        </div>
                        <div class="lp-field-row">
                            <div>
                                <label for="parcours_mention_id"><i class="fas fa-bookmark"></i> Mention <span class="text-danger">*</span></label>
                                <select class="form-select" id="parcours_mention_id" name="mention_id" required>
                                    <option value="">-- Sélectionner une mention --</option>
                                    @foreach($domaines as $d)
                                        <optgroup label="{{ $d->code }} — {{ $d->name }}">
                                            @foreach($d->mentions as $m)
                                                <option value="{{ $m->id }}">{{ $m->code }} — {{ $m->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="parcours_filiere_id"><i class="fas fa-link"></i> Filière associée</label>
                                <select class="form-select" id="parcours_filiere_id" name="filiere_id">
                                    <option value="">-- Aucune filière liée --</option>
                                    @php $filieres = \App\Models\ESBTPFiliere::where('is_active', true)->orderBy('name')->get(); @endphp
                                    @foreach($filieres as $fil)
                                        <option value="{{ $fil->id }}">{{ $fil->code ? $fil->code . ' — ' : '' }}{{ $fil->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Lier à une filière pour le label du bulletin</div>
                            </div>
                        </div>
                    </div>

                    {{-- Groupe 2: Identité --}}
                    <div class="lp-field-group">
                        <div class="lp-field-group-title lp-field-group-title--parcours">
                            <i class="fas fa-circle"></i> Identité du parcours
                        </div>
                        <div class="lp-field-row">
                            <div>
                                <label for="parcours_code"><i class="fas fa-hashtag"></i> Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="parcours_code" name="code" required placeholder="Ex: GCV-BU, TP, GT...">
                                <div class="form-text">Code court du parcours</div>
                            </div>
                            <div>
                                <label for="parcours_name"><i class="fas fa-font"></i> Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="parcours_name" name="name" required placeholder="Ex: GCV Bâtiment & Urbanisme">
                            </div>
                            <div>
                                <label for="parcours_responsable_id"><i class="fas fa-user-tie"></i> Responsable</label>
                                <select class="form-select" id="parcours_responsable_id" name="responsable_id">
                                    <option value="">-- Aucun responsable --</option>
                                    @if(isset($enseignants))
                                        @foreach($enseignants as $ens)
                                            <option value="{{ $ens->id }}">{{ $ens->name ?? $ens->nom_complet ?? $ens->id }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="lp-field-full">
                                <label for="parcours_description"><i class="fas fa-align-left"></i> Description</label>
                                <textarea class="form-control" id="parcours_description" name="description" rows="3" placeholder="Description optionnelle du parcours..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lp-modal-btn lp-modal-btn--cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="lp-modal-btn lp-modal-btn--parcours">
                        <i class="fas fa-save"></i> <span id="parcours_submit_text">Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    /**
     * Reset modal to "create" mode
     */
    function resetModal(type) {
        if (type === 'domaine') {
            document.getElementById('formDomaine').action = "{{ route('esbtp.lmd.domaines.store') }}";
            document.getElementById('domaine_method').value = 'POST';
            document.getElementById('modalDomaineLabel').textContent = 'Nouveau Domaine';
            document.getElementById('domaine_submit_text').textContent = 'Enregistrer';
            document.getElementById('domaine_code').value = '';
            document.getElementById('domaine_name').value = '';
            document.getElementById('domaine_description').value = '';
        } else if (type === 'mention') {
            document.getElementById('formMention').action = "{{ route('esbtp.lmd.mentions.store') }}";
            document.getElementById('mention_method').value = 'POST';
            document.getElementById('modalMentionLabel').textContent = 'Nouvelle Mention';
            document.getElementById('mention_submit_text').textContent = 'Enregistrer';
            document.getElementById('mention_domaine_id').value = '';
            document.getElementById('mention_code').value = '';
            document.getElementById('mention_name').value = '';
            document.getElementById('mention_description').value = '';
        } else if (type === 'parcours') {
            document.getElementById('formParcours').action = "{{ route('esbtp.lmd.parcours.store') }}";
            document.getElementById('parcours_method').value = 'POST';
            document.getElementById('modalParcoursLabel').textContent = 'Nouveau Parcours';
            document.getElementById('parcours_submit_text').textContent = 'Enregistrer';
            document.getElementById('parcours_mention_id').value = '';
            document.getElementById('parcours_filiere_id').value = '';
            document.getElementById('parcours_code').value = '';
            document.getElementById('parcours_name').value = '';
            document.getElementById('parcours_responsable_id').value = '';
            document.getElementById('parcours_description').value = '';
        }
    }

    /**
     * Edit Domaine — populate modal and switch to PUT
     */
    function editDomaine(domaine) {
        document.getElementById('formDomaine').action = "{{ url('esbtp/lmd/domaines') }}/" + domaine.id;
        document.getElementById('domaine_method').value = 'PUT';
        document.getElementById('modalDomaineLabel').textContent = 'Modifier le Domaine';
        document.getElementById('domaine_submit_text').textContent = 'Mettre à jour';
        document.getElementById('domaine_code').value = domaine.code || '';
        document.getElementById('domaine_name').value = domaine.name || '';
        document.getElementById('domaine_description').value = domaine.description || '';
        new bootstrap.Modal(document.getElementById('modalDomaine')).show();
    }

    /**
     * Edit Mention — populate modal and switch to PUT
     */
    function editMention(mention) {
        document.getElementById('formMention').action = "{{ url('esbtp/lmd/mentions') }}/" + mention.id;
        document.getElementById('mention_method').value = 'PUT';
        document.getElementById('modalMentionLabel').textContent = 'Modifier la Mention';
        document.getElementById('mention_submit_text').textContent = 'Mettre à jour';
        document.getElementById('mention_domaine_id').value = mention.domaine_id || '';
        document.getElementById('mention_code').value = mention.code || '';
        document.getElementById('mention_name').value = mention.name || '';
        document.getElementById('mention_description').value = mention.description || '';
        new bootstrap.Modal(document.getElementById('modalMention')).show();
    }

    // ================================================================
    //  MODAL: Créer une classe LMD
    // ================================================================
    function openCreateClasseModal(parcoursId, parcoursName) {
        document.getElementById('cc_form').action = '/esbtp/lmd/parcours/' + parcoursId + '/classe-rapide';
        document.getElementById('cc_parcours_id').value = parcoursId;
        document.getElementById('cc_parcours_label').textContent = parcoursName;
        document.getElementById('cc_form').reset();
        document.getElementById('cc_parcours_id').value = parcoursId;
        document.getElementById('cc_error').style.display = 'none';
        document.getElementById('cc_submit').disabled = false;
        document.getElementById('cc_submit_text').textContent = 'Créer la classe';
        new bootstrap.Modal(document.getElementById('modalCreateClasse')).show();
    }

    document.getElementById('cc_form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('cc_submit');
        const errBox = document.getElementById('cc_error');
        btn.disabled = true;
        document.getElementById('cc_submit_text').textContent = 'Création...';
        errBox.style.display = 'none';

        try {
            const resp = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(Object.fromEntries(new FormData(form)))
            });
            const data = await resp.json();
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalCreateClasse')).hide();
                window.location.reload();
            } else {
                errBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>' + (data.message || Object.values(data.errors || {}).flat().join('<br>'));
                errBox.style.display = 'block';
                btn.disabled = false;
                document.getElementById('cc_submit_text').textContent = 'Créer la classe';
            }
        } catch (err) {
            errBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Erreur réseau';
            errBox.style.display = 'block';
            btn.disabled = false;
            document.getElementById('cc_submit_text').textContent = 'Créer la classe';
        }
    });

    // ================================================================
    //  MODAL: Lier/Délier classes
    // ================================================================
    let linkParcoursId = null;

    async function openLinkClassesModal(parcoursId, parcoursName) {
        linkParcoursId = parcoursId;
        document.getElementById('lc_parcours_label').textContent = parcoursName;
        document.getElementById('lc_loading').style.display = 'block';
        document.getElementById('lc_content').style.display = 'none';
        document.getElementById('lc_error').style.display = 'none';
        new bootstrap.Modal(document.getElementById('modalLinkClasses')).show();

        try {
            const resp = await fetch(`/esbtp/lmd/parcours/${parcoursId}/classes-disponibles`);
            const data = await resp.json();

            const container = document.getElementById('lc_checkboxes');
            container.innerHTML = '';

            // Classes liées (checked)
            data.liees.forEach(c => {
                container.insertAdjacentHTML('beforeend', buildClasseCheckbox(c, true));
            });
            // Classes disponibles (unchecked)
            data.disponibles.forEach(c => {
                container.insertAdjacentHTML('beforeend', buildClasseCheckbox(c, false));
            });

            if (data.liees.length === 0 && data.disponibles.length === 0) {
                container.innerHTML = '<div style="padding:1.5rem; text-align:center; color:#94a3b8;"><i class="fas fa-info-circle me-1"></i>Aucune classe LMD trouvée. Créez d\'abord une classe avec un niveau Licence/Master/Doctorat.</div>';
            }

            document.getElementById('lc_loading').style.display = 'none';
            document.getElementById('lc_content').style.display = 'block';
        } catch (err) {
            document.getElementById('lc_loading').style.display = 'none';
            document.getElementById('lc_error').style.display = 'block';
        }
    }

    function buildClasseCheckbox(classe, checked) {
        return `<label style="display:flex; align-items:center; gap:.65rem; padding:.6rem .85rem; border-radius:10px; background:${checked ? '#eff6ff' : '#f8fafc'}; border:1.5px solid ${checked ? '#0453cb' : '#e8ecf1'}; cursor:pointer; transition:all .2s;">
            <input type="checkbox" name="classe_ids[]" value="${classe.id}" ${checked ? 'checked' : ''} style="width:1.1em; height:1.1em; accent-color:#0453cb; cursor:pointer;">
            <div>
                <div style="font-size:.88rem; font-weight:600; color:#1e293b;">${classe.name}</div>
                <div style="font-size:.72rem; color:#94a3b8;">${classe.code}</div>
            </div>
        </label>`;
    }

    async function saveLinkClasses() {
        const btn = document.getElementById('lc_submit');
        btn.disabled = true;
        document.getElementById('lc_submit_text').textContent = 'Enregistrement...';

        const checkboxes = document.querySelectorAll('#lc_checkboxes input[type="checkbox"]:checked');
        const classeIds = Array.from(checkboxes).map(cb => cb.value);

        try {
            const resp = await fetch(`/esbtp/lmd/parcours/${linkParcoursId}/sync-classes`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ classe_ids: classeIds })
            });
            const data = await resp.json();
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalLinkClasses')).hide();
                window.location.reload();
            }
        } catch (err) {
            document.getElementById('lc_error').style.display = 'block';
        }
        btn.disabled = false;
        document.getElementById('lc_submit_text').textContent = 'Enregistrer';
    }

    /**
     * Edit Parcours — populate modal and switch to PUT
     */
    function editParcours(parcours) {
        document.getElementById('formParcours').action = "{{ url('esbtp/lmd/parcours') }}/" + parcours.id;
        document.getElementById('parcours_method').value = 'PUT';
        document.getElementById('modalParcoursLabel').textContent = 'Modifier le Parcours';
        document.getElementById('parcours_submit_text').textContent = 'Mettre à jour';
        document.getElementById('parcours_mention_id').value = parcours.mention_id || '';
        document.getElementById('parcours_filiere_id').value = parcours.filiere_id || '';
        document.getElementById('parcours_code').value = parcours.code || '';
        document.getElementById('parcours_name').value = parcours.name || '';
        document.getElementById('parcours_responsable_id').value = parcours.responsable_id || '';
        document.getElementById('parcours_description').value = parcours.description || '';
        new bootstrap.Modal(document.getElementById('modalParcours')).show();
    }

    // ================================================================
    //  MODAL: Lier/Délier des UEs à un parcours
    // ================================================================
    let linkUesParcoursId = null;

    async function openLinkUesModal(parcoursId, parcoursName) {
        linkUesParcoursId = parcoursId;
        document.getElementById('lu_parcours_label').textContent = parcoursName;
        document.getElementById('lu_loading').style.display = 'block';
        document.getElementById('lu_content').style.display = 'none';
        document.getElementById('lu_error').style.display = 'none';
        new bootstrap.Modal(document.getElementById('modalLinkUes')).show();

        try {
            const resp = await fetch(`/esbtp/lmd/parcours/${parcoursId}/ues-disponibles`);
            const data = await resp.json();
            const container = document.getElementById('lu_checkboxes');
            container.innerHTML = '';

            // UEs liées (checked, with semestre)
            data.liees.forEach(ue => {
                container.insertAdjacentHTML('beforeend', buildUeCheckbox(ue, true));
            });
            // UEs disponibles (unchecked)
            data.disponibles.forEach(ue => {
                container.insertAdjacentHTML('beforeend', buildUeCheckbox(ue, false));
            });

            if (data.liees.length === 0 && data.disponibles.length === 0) {
                container.innerHTML = '<div style="padding:1.5rem; text-align:center; color:#94a3b8;"><i class="fas fa-info-circle me-1"></i>Aucune UE trouvée. Créez d\'abord des UEs.</div>';
            }

            document.getElementById('lu_loading').style.display = 'none';
            document.getElementById('lu_content').style.display = 'block';
        } catch (err) {
            document.getElementById('lu_loading').style.display = 'none';
            document.getElementById('lu_error').style.display = 'block';
        }
    }

    function buildUeCheckbox(ue, checked) {
        const sem = ue.semestre || 1;
        return `<label style="display:flex; align-items:center; gap:.65rem; padding:.6rem .85rem; border-radius:10px; background:${checked ? '#eef2ff' : '#f8fafc'}; border:1.5px solid ${checked ? '#4338ca' : '#e8ecf1'}; cursor:pointer; transition:all .2s;">
            <input type="checkbox" class="lu-ue-check" value="${ue.id}" ${checked ? 'checked' : ''} style="width:1.1em; height:1.1em; accent-color:#4338ca; cursor:pointer;">
            <div style="flex:1;">
                <div style="font-size:.88rem; font-weight:600; color:#1e293b;">${ue.code || ''} — ${ue.name}</div>
            </div>
            <select class="lu-sem-select" data-ue-id="${ue.id}" style="width:70px; padding:.25rem .4rem; border-radius:6px; border:1px solid #e2e8f0; font-size:.78rem; font-weight:600; background:#fff;">
                ${[1,2,3,4,5,6,7,8,9,10].map(s => `<option value="${s}" ${s == sem ? 'selected' : ''}>S${s}</option>`).join('')}
            </select>
        </label>`;
    }

    async function saveLinkUes() {
        const btn = document.getElementById('lu_submit');
        btn.disabled = true;
        document.getElementById('lu_submit_text').textContent = 'Enregistrement...';

        const checkboxes = document.querySelectorAll('#lu_checkboxes .lu-ue-check:checked');
        const ues = Array.from(checkboxes).map(cb => {
            const semSelect = document.querySelector(`.lu-sem-select[data-ue-id="${cb.value}"]`);
            return { id: cb.value, semestre: semSelect ? semSelect.value : 1 };
        });

        try {
            const resp = await fetch(`/esbtp/lmd/parcours/${linkUesParcoursId}/sync-ues`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ ues: ues })
            });
            const data = await resp.json();
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalLinkUes')).hide();
                window.location.reload();
            }
        } catch (err) {
            document.getElementById('lu_error').style.display = 'block';
        }
        btn.disabled = false;
        document.getElementById('lu_submit_text').textContent = 'Enregistrer';
    }
</script>
@endpush
