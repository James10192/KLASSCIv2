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

    /* Modals — keep shared .lmd-modal style */
    .lmd-modal .modal-header {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 60%, #3b7ddb 100%);
        color: #fff; border-radius: 14px 14px 0 0; padding: 1rem 1.5rem;
    }
    .lmd-modal .modal-header .btn-close { filter: brightness(0) invert(1); }
    .lmd-modal .modal-content { border-radius: 14px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.15); }
    .lmd-modal .modal-body { padding: 1.5rem; }
    .lmd-modal .modal-footer { border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem; }
    .lmd-modal label { font-size: .85rem; font-weight: 600; color: #1e293b; margin-bottom: .35rem; }
    .lmd-modal .form-control,
    .lmd-modal .form-select {
        border-radius: 9px; border: 1.5px solid #e2e8f0; padding: .5rem .75rem;
        font-size: .9rem; transition: border-color .2s, box-shadow .2s;
    }
    .lmd-modal .form-control:focus,
    .lmd-modal .form-select:focus { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08); }

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
{{-- ══ MODALS ══ --}}
{{-- ══════════════════════════════════════════════════════════ --}}

{{-- Modal Domaine --}}
<div class="modal fade lmd-modal" id="modalDomaine" tabindex="-1" aria-labelledby="modalDomaineLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formDomaine" method="POST" action="{{ route('esbtp.lmd.domaines.store') }}">
                @csrf
                <input type="hidden" name="_method" id="domaine_method" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDomaineLabel">
                        <i class="fas fa-layer-group me-2"></i>Nouveau Domaine
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="domaine_code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="domaine_code" name="code" required placeholder="Ex: ST, SHS, DEG...">
                    </div>
                    <div class="mb-3">
                        <label for="domaine_name" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="domaine_name" name="name" required placeholder="Ex: Sciences et Technologies">
                    </div>
                    <div class="mb-3">
                        <label for="domaine_description" class="form-label">Description</label>
                        <textarea class="form-control" id="domaine_description" name="description" rows="3" placeholder="Description optionnelle..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save me-1"></i><span id="domaine_submit_text">Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Mention --}}
<div class="modal fade lmd-modal" id="modalMention" tabindex="-1" aria-labelledby="modalMentionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formMention" method="POST" action="{{ route('esbtp.lmd.mentions.store') }}">
                @csrf
                <input type="hidden" name="_method" id="mention_method" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMentionLabel">
                        <i class="fas fa-bookmark me-2"></i>Nouvelle Mention
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="mention_domaine_id" class="form-label">Domaine <span class="text-danger">*</span></label>
                        <select class="form-select" id="mention_domaine_id" name="domaine_id" required>
                            <option value="">-- Sélectionner un domaine --</option>
                            @foreach($domaines as $d)
                                <option value="{{ $d->id }}">{{ $d->code }} — {{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="mention_code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="mention_code" name="code" required placeholder="Ex: INFO, GC, MATH...">
                    </div>
                    <div class="mb-3">
                        <label for="mention_name" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="mention_name" name="name" required placeholder="Ex: Informatique">
                    </div>
                    <div class="mb-3">
                        <label for="mention_description" class="form-label">Description</label>
                        <textarea class="form-control" id="mention_description" name="description" rows="3" placeholder="Description optionnelle..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save me-1"></i><span id="mention_submit_text">Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Parcours --}}
<div class="modal fade lmd-modal" id="modalParcours" tabindex="-1" aria-labelledby="modalParcoursLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formParcours" method="POST" action="{{ route('esbtp.lmd.parcours.store') }}">
                @csrf
                <input type="hidden" name="_method" id="parcours_method" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalParcoursLabel">
                        <i class="fas fa-route me-2"></i>Nouveau Parcours
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="parcours_mention_id" class="form-label">Mention <span class="text-danger">*</span></label>
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
                    <div class="mb-3">
                        <label for="parcours_filiere_id" class="form-label">Filière ESBTP <span class="text-muted">(lié au bulletin)</span></label>
                        <select class="form-select" id="parcours_filiere_id" name="filiere_id">
                            <option value="">-- Aucune filière liée --</option>
                            @php $filieres = \App\Models\ESBTPFiliere::where('is_active', true)->orderBy('name')->get(); @endphp
                            @foreach($filieres as $fil)
                                <option value="{{ $fil->id }}">{{ $fil->code ? $fil->code . ' — ' : '' }}{{ $fil->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Le label bulletin sera : "LICENCE 3 {Code Filière} {Nom Filière}"</small>
                    </div>
                    <div class="mb-3">
                        <label for="parcours_code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="parcours_code" name="code" required placeholder="Ex: GCV-BU, TP, GT...">
                    </div>
                    <div class="mb-3">
                        <label for="parcours_name" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="parcours_name" name="name" required placeholder="Ex: GCV Bâtiment & Urbanisme">
                    </div>
                    <div class="mb-3">
                        <label for="parcours_responsable_id" class="form-label">Responsable</label>
                        <select class="form-select" id="parcours_responsable_id" name="responsable_id">
                            <option value="">-- Aucun responsable --</option>
                            {{-- Populated by controller: $enseignants or similar --}}
                            @if(isset($enseignants))
                                @foreach($enseignants as $ens)
                                    <option value="{{ $ens->id }}">{{ $ens->name ?? $ens->nom_complet ?? $ens->id }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="parcours_description" class="form-label">Description</label>
                        <textarea class="form-control" id="parcours_description" name="description" rows="3" placeholder="Description optionnelle..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save me-1"></i><span id="parcours_submit_text">Enregistrer</span>
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
            document.getElementById('modalDomaineLabel').innerHTML = '<i class="fas fa-layer-group me-2"></i>Nouveau Domaine';
            document.getElementById('domaine_submit_text').textContent = 'Enregistrer';
            document.getElementById('domaine_code').value = '';
            document.getElementById('domaine_name').value = '';
            document.getElementById('domaine_description').value = '';
        } else if (type === 'mention') {
            document.getElementById('formMention').action = "{{ route('esbtp.lmd.mentions.store') }}";
            document.getElementById('mention_method').value = 'POST';
            document.getElementById('modalMentionLabel').innerHTML = '<i class="fas fa-bookmark me-2"></i>Nouvelle Mention';
            document.getElementById('mention_submit_text').textContent = 'Enregistrer';
            document.getElementById('mention_domaine_id').value = '';
            document.getElementById('mention_code').value = '';
            document.getElementById('mention_name').value = '';
            document.getElementById('mention_description').value = '';
        } else if (type === 'parcours') {
            document.getElementById('formParcours').action = "{{ route('esbtp.lmd.parcours.store') }}";
            document.getElementById('parcours_method').value = 'POST';
            document.getElementById('modalParcoursLabel').innerHTML = '<i class="fas fa-route me-2"></i>Nouveau Parcours';
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
        document.getElementById('modalDomaineLabel').innerHTML = '<i class="fas fa-layer-group me-2"></i>Modifier le Domaine';
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
        document.getElementById('modalMentionLabel').innerHTML = '<i class="fas fa-bookmark me-2"></i>Modifier la Mention';
        document.getElementById('mention_submit_text').textContent = 'Mettre à jour';
        document.getElementById('mention_domaine_id').value = mention.domaine_id || '';
        document.getElementById('mention_code').value = mention.code || '';
        document.getElementById('mention_name').value = mention.name || '';
        document.getElementById('mention_description').value = mention.description || '';
        new bootstrap.Modal(document.getElementById('modalMention')).show();
    }

    /**
     * Edit Parcours — populate modal and switch to PUT
     */
    function editParcours(parcours) {
        document.getElementById('formParcours').action = "{{ url('esbtp/lmd/parcours') }}/" + parcours.id;
        document.getElementById('parcours_method').value = 'PUT';
        document.getElementById('modalParcoursLabel').innerHTML = '<i class="fas fa-route me-2"></i>Modifier le Parcours';
        document.getElementById('parcours_submit_text').textContent = 'Mettre à jour';
        document.getElementById('parcours_mention_id').value = parcours.mention_id || '';
        document.getElementById('parcours_filiere_id').value = parcours.filiere_id || '';
        document.getElementById('parcours_code').value = parcours.code || '';
        document.getElementById('parcours_name').value = parcours.name || '';
        document.getElementById('parcours_responsable_id').value = parcours.responsable_id || '';
        document.getElementById('parcours_description').value = parcours.description || '';
        new bootstrap.Modal(document.getElementById('modalParcours')).show();
    }
</script>
@endpush
