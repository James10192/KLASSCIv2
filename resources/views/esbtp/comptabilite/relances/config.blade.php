@extends('layouts.app')

@section('title', 'Configuration des Relances')

@push('styles')
<style>
/* ══════════════════════════════════════════════════════════════════
   CONFIG HERO — même univers premium que la fiche relance
   ══════════════════════════════════════════════════════════════════ */
:root {
    --cfg-primary: #0453cb;
    --cfg-primary-light: #5e91de;
    --cfg-dark: #0f172a;
    --cfg-navy: #0c2460;
    --cfg-slate: #1e293b;
    --cfg-border: #e8edf5;
    --cfg-surface: #f8faff;
    --cfg-success: #10b981;
    --cfg-text: #1e293b;
    --cfg-muted: #64748b;
    --cfg-subtle: #94a3b8;
}

/* ── Hero ─────────────────────────────────────────────────────── */
.cfg-hero {
    position: relative;
    background: linear-gradient(135deg, #0f172a 0%, #0c2460 60%, #1e293b 100%);
    border-radius: 20px;
    padding: 36px 40px 32px;
    margin-bottom: 28px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.28);
}
.cfg-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 85% 50%, rgba(94,145,222,.18) 0%, transparent 70%),
        radial-gradient(ellipse 30% 60% at 5% 80%, rgba(4,83,203,.22) 0%, transparent 70%);
    pointer-events: none;
}
.cfg-hero::after {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 240px; height: 240px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,.05);
    pointer-events: none;
}
.cfg-hero-breadcrumb {
    font-size: .7rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: rgba(255,255,255,.45);
    margin-bottom: .6rem;
    display: flex;
    align-items: center;
    gap: .4rem;
    position: relative;
    z-index: 1;
}
.cfg-hero-breadcrumb a { color: rgba(255,255,255,.5); text-decoration: none; transition: color .2s; }
.cfg-hero-breadcrumb a:hover { color: #fff; }
.cfg-hero-title {
    font-size: 1.8rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 .35rem;
    letter-spacing: -.02em;
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: .6rem;
}
.cfg-hero-title-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.cfg-hero-sub {
    font-size: .87rem;
    color: rgba(255,255,255,.55);
    margin: 0;
    position: relative;
    z-index: 1;
}
.cfg-hero-back {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.18);
    color: rgba(255,255,255,.85);
    font-size: .8rem;
    font-weight: 500;
    padding: .5rem 1.1rem;
    border-radius: 10px;
    text-decoration: none;
    transition: all .2s;
    position: relative;
    z-index: 1;
    white-space: nowrap;
}
.cfg-hero-back:hover {
    background: rgba(255,255,255,.2);
    color: #fff;
    transform: translateY(-1px);
}

/* ── KPI Strip sous le hero ───────────────────────────────────── */
.cfg-kpi-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
.cfg-kpi-card {
    background: #fff;
    border: 1px solid var(--cfg-border);
    border-radius: 16px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 2px 12px rgba(4,83,203,.06);
    transition: box-shadow .2s, transform .2s;
}
.cfg-kpi-card:hover {
    box-shadow: 0 8px 28px rgba(4,83,203,.13);
    transform: translateY(-2px);
}
.cfg-kpi-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
    flex-shrink: 0;
}
.cfg-kpi-icon.lvl1 { background: rgba(16,185,129,.12); color: #059669; }
.cfg-kpi-icon.lvl2 { background: rgba(4,83,203,.1); color: #0453cb; }
.cfg-kpi-icon.lvl3 { background: rgba(30,41,59,.1); color: #1e293b; }
.cfg-kpi-value {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--cfg-text);
    line-height: 1;
    letter-spacing: -.03em;
}
.cfg-kpi-value span { font-size: .85rem; font-weight: 500; color: var(--cfg-subtle); margin-left: 2px; }
.cfg-kpi-label {
    font-size: .75rem;
    font-weight: 600;
    color: var(--cfg-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-top: 2px;
}

/* ── Section Card ─────────────────────────────────────────────── */
.cfg-card {
    background: #fff;
    border: 1px solid var(--cfg-border);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(4,83,203,.08);
    margin-bottom: 20px;
    overflow: hidden;
    transition: box-shadow .2s;
}
.cfg-card:hover { box-shadow: 0 8px 32px rgba(4,83,203,.13); }
.cfg-card-header {
    padding: 1.2rem 1.6rem;
    border-bottom: 1px solid var(--cfg-border);
    display: flex;
    align-items: center;
    gap: .85rem;
    background: linear-gradient(90deg, #f8faff 0%, #fff 100%);
}
.cfg-card-icon {
    width: 40px; height: 40px;
    border-radius: 11px;
    background: linear-gradient(135deg, var(--cfg-primary), var(--cfg-primary-light));
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-size: .9rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
}
.cfg-card-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--cfg-text);
    margin: 0;
}
.cfg-card-subtitle {
    font-size: .75rem;
    color: var(--cfg-muted);
    margin-top: .1rem;
}
.cfg-card-body { padding: 1.6rem; }

/* ── Template Tabs ────────────────────────────────────────────── */
.cfg-tabs {
    display: flex;
    gap: .35rem;
    border-bottom: 2px solid var(--cfg-border);
    margin-bottom: 1.5rem;
}
.cfg-tab-btn {
    background: none;
    border: none;
    padding: .6rem 1.1rem;
    font-size: .83rem;
    font-weight: 600;
    color: var(--cfg-muted);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all .2s;
    display: flex;
    align-items: center;
    gap: .45rem;
    border-radius: 8px 8px 0 0;
}
.cfg-tab-btn:hover { color: var(--cfg-primary); background: rgba(4,83,203,.04); }
.cfg-tab-btn.active {
    color: var(--cfg-primary);
    border-bottom-color: var(--cfg-primary);
    background: rgba(4,83,203,.05);
}

/* ── Template Level Badge ─────────────────────────────────────── */
.lvl-badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: .28rem .8rem;
    border-radius: 20px;
}
.lvl-badge .lvl-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    animation: pulse-dot 2s infinite;
}
.lvl-1 { background: rgba(16,185,129,.12); color: #059669; }
.lvl-1 .lvl-dot { background: #059669; }
.lvl-2 { background: rgba(4,83,203,.1); color: var(--cfg-primary); }
.lvl-2 .lvl-dot { background: var(--cfg-primary); }
.lvl-3 { background: rgba(30,41,59,.1); color: var(--cfg-slate); }
.lvl-3 .lvl-dot { background: var(--cfg-slate); animation: none; }
@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .5; transform: scale(.75); }
}

/* ── Template Editor ──────────────────────────────────────────── */
.tpl-editor {
    border: 1px solid var(--cfg-border);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 1.25rem;
    transition: border-color .2s, box-shadow .2s;
}
.tpl-editor:focus-within {
    border-color: rgba(4,83,203,.35);
    box-shadow: 0 0 0 3px rgba(4,83,203,.07);
}
.tpl-editor-head {
    background: var(--cfg-surface);
    padding: .8rem 1.2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--cfg-border);
}
.tpl-editor-body { padding: 1.2rem; }
.tpl-preview-btn {
    background: #fff;
    border: 1px solid #d1ddf8;
    color: var(--cfg-primary);
    font-size: .75rem;
    font-weight: 600;
    padding: .32rem .8rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all .2s;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.tpl-preview-btn:hover {
    background: var(--cfg-primary);
    color: #fff;
    border-color: var(--cfg-primary);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
}

/* ── Form Fields ──────────────────────────────────────────────── */
.cfg-label {
    font-size: .75rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: .4rem;
    display: block;
}
.cfg-input {
    border: 1.5px solid #dde5f0;
    border-radius: 10px;
    padding: .6rem .9rem;
    font-size: .88rem;
    color: var(--cfg-text);
    width: 100%;
    transition: border-color .2s, box-shadow .2s;
    background: #fff;
}
.cfg-input:focus {
    outline: none;
    border-color: var(--cfg-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.cfg-input.is-invalid { border-color: #dc3545; }
.cfg-hint { font-size: .73rem; color: var(--cfg-subtle); margin-top: .3rem; }
.invalid-feedback { font-size: .75rem; color: #dc3545; margin-top: .25rem; }

/* ── Save Buttons ─────────────────────────────────────────────── */
.cfg-save-btn {
    background: linear-gradient(135deg, var(--cfg-primary), var(--cfg-primary-light));
    border: none;
    color: #fff;
    font-weight: 700;
    font-size: .88rem;
    padding: .7rem 1.6rem;
    border-radius: 10px;
    cursor: pointer;
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    letter-spacing: .01em;
    box-shadow: 0 4px 14px rgba(4,83,203,.3);
}
.cfg-save-btn:hover {
    opacity: .93;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(4,83,203,.35);
}
.cfg-save-btn-full {
    width: 100%;
    justify-content: center;
}

/* ── Variables Sidebar ────────────────────────────────────────── */
.var-section { margin-bottom: 1.35rem; }
.var-section:last-child { margin-bottom: 0; }
.var-section-header {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: .65rem;
}
.var-section-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.var-section-title {
    font-size: .68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--cfg-subtle);
}
.var-section-student .var-section-dot { background: var(--cfg-primary); }
.var-section-finance .var-section-dot { background: var(--cfg-success); }
.var-section-relance .var-section-dot { background: #f59e0b; }
.var-section-school .var-section-dot { background: #8b5cf6; }
.var-tag {
    display: inline-block;
    background: #eef3ff;
    color: var(--cfg-primary);
    border: 1px solid #c7d8f8;
    padding: .22rem .65rem;
    border-radius: 6px;
    font-size: .71rem;
    font-weight: 600;
    margin: .18rem .12rem;
    cursor: pointer;
    font-family: 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
    transition: all .18s;
    letter-spacing: -.01em;
}
.var-tag:hover {
    background: var(--cfg-primary);
    color: #fff;
    border-color: var(--cfg-primary);
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(4,83,203,.25);
}
.var-section-finance .var-tag { background: #f0fdf4; color: #059669; border-color: #a7f3d0; }
.var-section-finance .var-tag:hover { background: #059669; color: #fff; border-color: #059669; }
.var-section-relance .var-tag { background: #fffbeb; color: #b45309; border-color: #fcd34d; }
.var-section-relance .var-tag:hover { background: #b45309; color: #fff; border-color: #b45309; }
.var-section-school .var-tag { background: #f5f3ff; color: #6d28d9; border-color: #c4b5fd; }
.var-section-school .var-tag:hover { background: #6d28d9; color: #fff; border-color: #6d28d9; }

/* ── Toggle ───────────────────────────────────────────────────── */
.cfg-toggle-wrap {
    display: flex;
    align-items: center;
    gap: .85rem;
    padding: .95rem 1.1rem;
    background: var(--cfg-surface);
    border: 1px solid var(--cfg-border);
    border-radius: 12px;
    transition: border-color .2s;
}
.cfg-toggle-wrap:focus-within { border-color: rgba(4,83,203,.35); }
.cfg-toggle-wrap input[type=checkbox] {
    width: 42px; height: 23px;
    accent-color: var(--cfg-primary);
    cursor: pointer;
    flex-shrink: 0;
}
.cfg-toggle-label { font-size: .87rem; font-weight: 600; color: var(--cfg-text); margin: 0; }
.cfg-toggle-hint { font-size: .73rem; color: var(--cfg-subtle); margin-top: .12rem; }

/* ── Delay Meter ──────────────────────────────────────────────── */
.delay-row {
    display: flex;
    align-items: center;
    gap: .9rem;
    padding: .95rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.delay-row:last-child { border-bottom: none; padding-bottom: 0; }
.delay-num {
    width: 30px; height: 30px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 800;
    flex-shrink: 0;
}
.delay-num-1 { background: rgba(16,185,129,.12); color: #059669; }
.delay-num-2 { background: rgba(4,83,203,.1); color: var(--cfg-primary); }
.delay-num-3 { background: rgba(30,41,59,.1); color: var(--cfg-slate); }
.delay-label { flex: 1; font-size: .83rem; font-weight: 600; color: #475569; }
.delay-input-wrap { position: relative; width: 110px; }
.delay-input-wrap input {
    border: 1.5px solid #dde5f0;
    border-radius: 9px;
    padding: .48rem .6rem;
    font-size: .85rem;
    font-weight: 700;
    color: var(--cfg-text);
    width: 100%;
    text-align: center;
    transition: border-color .2s, box-shadow .2s;
}
.delay-input-wrap input:focus {
    outline: none;
    border-color: var(--cfg-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.delay-unit {
    font-size: .7rem;
    color: var(--cfg-subtle);
    text-align: center;
    margin-top: .2rem;
    font-weight: 500;
}

/* ── Alerts ───────────────────────────────────────────────────── */
.cfg-alert-warning {
    background: linear-gradient(90deg, #fffbeb, #fef9e7);
    border: 1.5px solid #fbbf24;
    border-radius: 12px;
    padding: .9rem 1.1rem;
    margin-bottom: 1.1rem;
    display: flex; align-items: flex-start; gap: .65rem;
    color: #92400e; font-size: .83rem;
}
.cfg-alert-success {
    background: linear-gradient(90deg, #f0fdf4, #dcfce7);
    border: 1.5px solid var(--cfg-success);
    border-radius: 12px;
    padding: .9rem 1.1rem;
    margin-bottom: 1.1rem;
    display: flex; align-items: flex-start; gap: .65rem;
    color: #065f46; font-size: .83rem;
}

/* ── Modal ────────────────────────────────────────────────────── */
.cfg-modal-header {
    background: linear-gradient(135deg, #0f172a, var(--cfg-primary));
    padding: 1.2rem 1.6rem;
    border: none;
}
.cfg-modal-title { color: #fff; font-weight: 700; font-size: 1rem; }
.cfg-modal-header .btn-close { filter: invert(1) brightness(2); }

/* ── Sticky Sidebar ───────────────────────────────────────────── */
@media (min-width: 992px) {
    .cfg-sticky { position: sticky; top: 80px; }
}

/* ── Responsive ───────────────────────────────────────────────── */
@media (max-width: 768px) {
    .cfg-hero { padding: 24px 20px 20px; border-radius: 14px; }
    .cfg-hero-title { font-size: 1.35rem; }
    .cfg-kpi-strip { grid-template-columns: 1fr; gap: 10px; }
}
@media (max-width: 576px) {
    .cfg-kpi-strip { grid-template-columns: repeat(3, 1fr); }
    .cfg-kpi-value { font-size: 1.3rem; }
}
</style>
@endpush

@section('content')

{{-- ── HERO ── --}}
<div class="cfg-hero">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
            <div class="cfg-hero-breadcrumb">
                <a href="{{ route('esbtp.comptabilite.relances.index') }}">Relances</a>
                <i class="fas fa-chevron-right" style="font-size:.5rem;"></i>
                <span>Configuration</span>
            </div>
            <h1 class="cfg-hero-title">
                <div class="cfg-hero-title-icon">
                    <i class="fas fa-sliders-h"></i>
                </div>
                Configuration des Relances
            </h1>
            <p class="cfg-hero-sub">Paramètres de délais, templates de messages et règles de déclenchement</p>
        </div>
        <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="cfg-hero-back">
            <i class="fas fa-arrow-left"></i> Retour aux relances
        </a>
    </div>
</div>

{{-- ── KPI STRIP ── --}}
@php
    $d1 = $parametres['delai_niveau_1'] ?? null;
    $d2 = $parametres['delai_niveau_2'] ?? null;
    $d3 = $parametres['delai_niveau_3'] ?? null;
@endphp
<div class="cfg-kpi-strip">
    <div class="cfg-kpi-card">
        <div class="cfg-kpi-icon lvl1"><i class="fas fa-bell"></i></div>
        <div>
            <div class="cfg-kpi-value">
                {{ $d1 ?? '—' }}<span>{{ $d1 ? 'j' : '' }}</span>
            </div>
            <div class="cfg-kpi-label">1er Rappel</div>
        </div>
    </div>
    <div class="cfg-kpi-card">
        <div class="cfg-kpi-icon lvl2"><i class="fas fa-bell-slash"></i></div>
        <div>
            <div class="cfg-kpi-value">
                {{ $d2 ?? '—' }}<span>{{ $d2 ? 'j' : '' }}</span>
            </div>
            <div class="cfg-kpi-label">2ème Rappel</div>
        </div>
    </div>
    <div class="cfg-kpi-card">
        <div class="cfg-kpi-icon lvl3"><i class="fas fa-exclamation-circle"></i></div>
        <div>
            <div class="cfg-kpi-value">
                {{ $d3 ?? '—' }}<span>{{ $d3 ? 'j' : '' }}</span>
            </div>
            <div class="cfg-kpi-label">Dernière Relance</div>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- ── COL GAUCHE : Templates ── --}}
    <div class="col-lg-8">

        <div class="cfg-card">
            <div class="cfg-card-header">
                <div class="cfg-card-icon"><i class="fas fa-file-alt"></i></div>
                <div>
                    <h5 class="cfg-card-title">Templates de Relance</h5>
                    <div class="cfg-card-subtitle">Personnalisez les messages selon le canal et le niveau d'urgence</div>
                </div>
            </div>
            <div class="cfg-card-body">

                {{-- Tabs --}}
                <div class="cfg-tabs" id="templateTabs" role="tablist">
                    <button class="cfg-tab-btn active" id="tab-email" data-tab="email" type="button">
                        <i class="fas fa-envelope"></i> Email
                    </button>
                    <button class="cfg-tab-btn" id="tab-sms" data-tab="sms" type="button">
                        <i class="fas fa-sms"></i> SMS
                    </button>
                    <button class="cfg-tab-btn" id="tab-courrier" data-tab="courrier" type="button">
                        <i class="fas fa-file-pdf"></i> Courrier
                    </button>
                </div>

                {{-- Email --}}
                <div id="pane-email">
                    <form id="formTemplatesEmail">
                        @foreach([1 => ['1er rappel', 'lvl-1'], 2 => ['2ème rappel', 'lvl-2'], 3 => ['Dernière relance', 'lvl-3']] as $niveau => $info)
                            <div class="tpl-editor">
                                <div class="tpl-editor-head">
                                    <span class="lvl-badge {{ $info[1] }}">
                                        <span class="lvl-dot"></span>
                                        Niveau {{ $niveau }} — {{ $info[0] }}
                                    </span>
                                    <button type="button" class="tpl-preview-btn" onclick="previewTemplate('email', {{ $niveau }})">
                                        <i class="fas fa-eye"></i> Aperçu
                                    </button>
                                </div>
                                <div class="tpl-editor-body">
                                    <div class="mb-3">
                                        <label class="cfg-label" for="email_sujet_{{ $niveau }}">Sujet de l'email</label>
                                        <input type="text" class="cfg-input" id="email_sujet_{{ $niveau }}"
                                               name="email_sujet[{{ $niveau }}]"
                                               value="{{ $templates['email'][$niveau]['sujet'] ?? '' }}"
                                               placeholder="Ex: Rappel de paiement — ESBTP">
                                    </div>
                                    <div>
                                        <label class="cfg-label" for="email_contenu_{{ $niveau }}">Contenu</label>
                                        <textarea class="cfg-input" id="email_contenu_{{ $niveau }}"
                                                  name="email_contenu[{{ $niveau }}]" rows="7"
                                                  style="resize:vertical;"
                                                  placeholder="Contenu du template avec variables…">{!! $templates['email'][$niveau]['contenu'] ?? '' !!}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="cfg-save-btn">
                                <i class="fas fa-save"></i> Sauvegarder templates Email
                            </button>
                        </div>
                    </form>
                </div>

                {{-- SMS --}}
                <div id="pane-sms" style="display:none;">
                    <form id="formTemplatesSms">
                        @foreach([1 => ['1er rappel', 'lvl-1'], 2 => ['2ème rappel', 'lvl-2'], 3 => ['Dernière relance', 'lvl-3']] as $niveau => $info)
                            <div class="tpl-editor">
                                <div class="tpl-editor-head">
                                    <span class="lvl-badge {{ $info[1] }}">
                                        <span class="lvl-dot"></span>
                                        Niveau {{ $niveau }} — {{ $info[0] }}
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span id="sms_count_{{ $niveau }}" style="font-size:.72rem;color:var(--cfg-subtle);">0/160</span>
                                        <button type="button" class="tpl-preview-btn" onclick="previewTemplate('sms', {{ $niveau }})">
                                            <i class="fas fa-eye"></i> Aperçu
                                        </button>
                                    </div>
                                </div>
                                <div class="tpl-editor-body">
                                    <label class="cfg-label" for="sms_contenu_{{ $niveau }}">Message SMS</label>
                                    <textarea class="cfg-input sms-template" id="sms_contenu_{{ $niveau }}"
                                              name="sms_contenu[{{ $niveau }}]" rows="4" maxlength="160"
                                              data-counter="sms_count_{{ $niveau }}"
                                              style="resize:vertical;"
                                              placeholder="Message court…">{!! $templates['sms'][$niveau]['contenu'] ?? '' !!}</textarea>
                                    <div class="cfg-hint">Maximum 160 caractères</div>
                                </div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="cfg-save-btn">
                                <i class="fas fa-save"></i> Sauvegarder templates SMS
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Courrier --}}
                <div id="pane-courrier" style="display:none;">
                    <form id="formTemplatesCourrier">
                        @foreach([1 => ['1er rappel', 'lvl-1'], 2 => ['2ème rappel', 'lvl-2'], 3 => ['Dernière relance', 'lvl-3']] as $niveau => $info)
                            <div class="tpl-editor">
                                <div class="tpl-editor-head">
                                    <span class="lvl-badge {{ $info[1] }}">
                                        <span class="lvl-dot"></span>
                                        Niveau {{ $niveau }} — {{ $info[0] }}
                                    </span>
                                    <button type="button" class="tpl-preview-btn" onclick="previewTemplate('courrier', {{ $niveau }})">
                                        <i class="fas fa-eye"></i> Aperçu PDF
                                    </button>
                                </div>
                                <div class="tpl-editor-body">
                                    <label class="cfg-label" for="courrier_contenu_{{ $niveau }}">Contenu du courrier</label>
                                    <textarea class="cfg-input" id="courrier_contenu_{{ $niveau }}"
                                              name="courrier_contenu[{{ $niveau }}]" rows="10"
                                              style="resize:vertical;"
                                              placeholder="Contenu avec mise en forme HTML…">{!! $templates['courrier'][$niveau]['contenu'] ?? '' !!}</textarea>
                                </div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="cfg-save-btn">
                                <i class="fas fa-save"></i> Sauvegarder templates Courrier
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    {{-- ── COL DROITE : Variables + Paramètres ── --}}
    <div class="col-lg-4">
        <div class="cfg-sticky">

            {{-- Variables disponibles --}}
            <div class="cfg-card">
                <div class="cfg-card-header">
                    <div class="cfg-card-icon"><i class="fas fa-tags"></i></div>
                    <div>
                        <h5 class="cfg-card-title">Variables</h5>
                        <div class="cfg-card-subtitle">Clic pour insérer dans le champ actif</div>
                    </div>
                </div>
                <div class="cfg-card-body" style="padding:1.2rem 1.4rem;">

                    <div class="var-section var-section-student">
                        <div class="var-section-header">
                            <div class="var-section-dot"></div>
                            <div class="var-section-title">Étudiant</div>
                        </div>
                        <span class="var-tag" onclick="insertVariable('{nom}')">{nom}</span>
                        <span class="var-tag" onclick="insertVariable('{prenom}')">{prenom}</span>
                        <span class="var-tag" onclick="insertVariable('{nom_complet}')">{nom_complet}</span>
                        <span class="var-tag" onclick="insertVariable('{email}')">{email}</span>
                        <span class="var-tag" onclick="insertVariable('{telephone}')">{telephone}</span>
                        <span class="var-tag" onclick="insertVariable('{numero_etudiant}')">{numero_etudiant}</span>
                    </div>

                    <div class="var-section var-section-finance">
                        <div class="var-section-header">
                            <div class="var-section-dot"></div>
                            <div class="var-section-title">Financier</div>
                        </div>
                        <span class="var-tag" onclick="insertVariable('{montant_dette}')">{montant_dette}</span>
                        <span class="var-tag" onclick="insertVariable('{montant_dette_formatte}')">{montant_dette_formatte}</span>
                        <span class="var-tag" onclick="insertVariable('{date_echeance}')">{date_echeance}</span>
                        <span class="var-tag" onclick="insertVariable('{jours_retard}')">{jours_retard}</span>
                    </div>

                    <div class="var-section var-section-relance">
                        <div class="var-section-header">
                            <div class="var-section-dot"></div>
                            <div class="var-section-title">Relance</div>
                        </div>
                        <span class="var-tag" onclick="insertVariable('{niveau_relance}')">{niveau_relance}</span>
                        <span class="var-tag" onclick="insertVariable('{type_relance}')">{type_relance}</span>
                        <span class="var-tag" onclick="insertVariable('{date_relance}')">{date_relance}</span>
                    </div>

                    <div class="var-section var-section-school">
                        <div class="var-section-header">
                            <div class="var-section-dot"></div>
                            <div class="var-section-title">Établissement</div>
                        </div>
                        <span class="var-tag" onclick="insertVariable('{nom_ecole}')">{nom_ecole}</span>
                        <span class="var-tag" onclick="insertVariable('{adresse_ecole}')">{adresse_ecole}</span>
                        <span class="var-tag" onclick="insertVariable('{telephone_ecole}')">{telephone_ecole}</span>
                        <span class="var-tag" onclick="insertVariable('{email_ecole}')">{email_ecole}</span>
                        <span class="var-tag" onclick="insertVariable('{date_aujourdhui}')">{date_aujourdhui}</span>
                        <span class="var-tag" onclick="insertVariable('{annee_academique}')">{annee_academique}</span>
                    </div>

                </div>
            </div>

            {{-- Paramètres --}}
            <div class="cfg-card">
                <div class="cfg-card-header">
                    <div class="cfg-card-icon"><i class="fas fa-cog"></i></div>
                    <div>
                        <h5 class="cfg-card-title">Paramètres</h5>
                        <div class="cfg-card-subtitle">Délais et règles de déclenchement</div>
                    </div>
                </div>
                <div class="cfg-card-body">

                    @php
                        $nonConfigured = is_null($parametres['delai_niveau_1'])
                                      && is_null($parametres['delai_niveau_2'])
                                      && is_null($parametres['delai_niveau_3']);
                    @endphp

                    @if($nonConfigured)
                    <div class="cfg-alert-warning">
                        <i class="fas fa-exclamation-triangle" style="margin-top:.15rem;flex-shrink:0;"></i>
                        <div>Aucune configuration enregistrée. Renseignez les délais ci-dessous pour activer le système de relances.</div>
                    </div>
                    @endif

                    @if(session('success'))
                    <div class="cfg-alert-success">
                        <i class="fas fa-check-circle" style="margin-top:.15rem;flex-shrink:0;"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('esbtp.comptabilite.relances.config.parametres') }}">
                        @csrf

                        {{-- Délais --}}
                        <div class="mb-3">
                            <label class="cfg-label">Délais de relance (jours de retard)</label>
                            <div style="border:1px solid var(--cfg-border);border-radius:12px;padding:.25rem .9rem;">
                                @foreach([1 => ['1er rappel', 'delay-num-1', 'delai_niveau_1'], 2 => ['2ème rappel', 'delay-num-2', 'delai_niveau_2'], 3 => ['Dernière relance', 'delay-num-3', 'delai_niveau_3']] as $n => $d)
                                <div class="delay-row">
                                    <div class="delay-num {{ $d[1] }}">{{ $n }}</div>
                                    <div class="delay-label">{{ $d[0] }}</div>
                                    <div class="delay-input-wrap">
                                        <input type="number"
                                               name="{{ $d[2] }}"
                                               value="{{ old($d[2], $parametres[$d[2]]) }}"
                                               min="1" max="365"
                                               placeholder="—"
                                               class="@error($d[2]) is-invalid @enderror">
                                        <div class="delay-unit">jours</div>
                                        @error($d[2])<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="cfg-hint">Nombre de jours de retard avant chaque niveau</div>
                        </div>

                        {{-- Montant minimum --}}
                        <div class="mb-3">
                            <label class="cfg-label" for="montant_minimum">Montant minimum (FCFA)</label>
                            <input type="number"
                                   class="cfg-input @error('montant_minimum') is-invalid @enderror"
                                   id="montant_minimum" name="montant_minimum"
                                   value="{{ old('montant_minimum', $parametres['montant_minimum']) }}"
                                   min="0" step="1000"
                                   placeholder="Ex : 10 000">
                            <div class="cfg-hint">Seuil minimum pour déclencher une relance</div>
                            @error('montant_minimum')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Toggle relances auto --}}
                        <div class="mb-3">
                            <div class="cfg-toggle-wrap">
                                <input class="form-check-input" type="checkbox" id="relances_automatiques"
                                       name="relances_automatiques"
                                       {{ $parametres['relances_automatiques'] ? 'checked' : '' }}>
                                <div>
                                    <div class="cfg-toggle-label">Relances automatiques</div>
                                    <div class="cfg-toggle-hint">Planification auto selon les délais configurés</div>
                                </div>
                            </div>
                        </div>

                        {{-- Heure d'envoi --}}
                        <div class="mb-4">
                            <label class="cfg-label" for="heure_envoi">Heure d'envoi automatique</label>
                            <input type="time"
                                   class="cfg-input @error('heure_envoi') is-invalid @enderror"
                                   id="heure_envoi" name="heure_envoi"
                                   value="{{ old('heure_envoi', $parametres['heure_envoi'] ?? '') }}">
                            <div class="cfg-hint">Heure quotidienne pour l'envoi automatique</div>
                            @error('heure_envoi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="cfg-save-btn cfg-save-btn-full">
                            <i class="fas fa-save"></i> Enregistrer les paramètres
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ── MODAL APERÇU ── --}}
<div class="modal fade" id="modalApercu" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:none;border-radius:18px;overflow:hidden;">
            <div class="modal-header cfg-modal-header">
                <h5 class="modal-title cfg-modal-title">
                    <i class="fas fa-eye me-2"></i> Aperçu du Template
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1) brightness(2);"></button>
            </div>
            <div class="modal-body" style="padding:1.6rem;">
                <div id="apercu-content"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--cfg-border);padding:.9rem 1.6rem;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:9px;font-size:.85rem;">Fermer</button>
                <button type="button" onclick="envoyerTestTemplate()" class="cfg-save-btn" style="padding:.5rem 1.3rem;box-shadow:none;">
                    <i class="fas fa-paper-plane"></i> Envoyer un test
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── Tab switching ──
    document.querySelectorAll('.cfg-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.cfg-tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const tab = this.getAttribute('data-tab');
            ['email','sms','courrier'].forEach(t => {
                document.getElementById('pane-' + t).style.display = (t === tab) ? '' : 'none';
            });
        });
    });

    // ── SMS character counter ──
    document.querySelectorAll('.sms-template').forEach(textarea => {
        const counterId = textarea.getAttribute('data-counter');
        const counter   = document.getElementById(counterId);
        function updateCounter() {
            const len = textarea.value.length;
            counter.textContent = `${len}/160`;
            counter.style.color = len > 140 ? (len > 160 ? '#dc3545' : '#f59e0b') : 'var(--cfg-subtle)';
        }
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });

    // ── Template form submissions ──
    ['Email', 'Sms', 'Courrier'].forEach(type => {
        const form = document.getElementById('formTemplates' + type);
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                sauvegarderTemplates(type.toLowerCase());
            });
        }
    });

    // ── Active textarea tracking ──
    document.addEventListener('focusin', function(e) {
        if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
            if (e.target.tagName === 'TEXTAREA') {
                currentFocusedTextarea = e.target;
            }
        }
    });
});

let currentFocusedTextarea = null;

function insertVariable(variable) {
    if (!currentFocusedTextarea) {
        showToast('warning', 'Cliquez dans un champ texte avant d\'insérer une variable.');
        return;
    }
    const start = currentFocusedTextarea.selectionStart;
    const end   = currentFocusedTextarea.selectionEnd;
    const text  = currentFocusedTextarea.value;
    currentFocusedTextarea.value = text.substring(0, start) + variable + text.substring(end);
    currentFocusedTextarea.focus();
    const newPos = start + variable.length;
    currentFocusedTextarea.setSelectionRange(newPos, newPos);
    currentFocusedTextarea.dispatchEvent(new Event('input'));
}

function sauvegarderTemplates(type) {
    const key  = 'formTemplates' + type.charAt(0).toUpperCase() + type.slice(1);
    const form = document.getElementById(key);
    const btn  = form.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sauvegarde…';
    btn.disabled  = true;

    fetch(`{{ route('esbtp.comptabilite.relances.config.templates') }}`, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => showToast(data.success ? 'success' : 'error', data.message))
    .catch(() => showToast('error', 'Erreur lors de la sauvegarde'))
    .finally(() => { btn.innerHTML = orig; btn.disabled = false; });
}

function previewTemplate(type, niveau) {
    const container = document.getElementById('apercu-content');
    const modal = new bootstrap.Modal(document.getElementById('modalApercu'));
    container.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-primary" style="font-size:1.5rem;"></i></div>';
    modal.show();

    let contenu = '', sujet = null;
    if (type === 'email') {
        sujet   = document.getElementById(`email_sujet_${niveau}`)?.value;
        contenu = document.getElementById(`email_contenu_${niveau}`)?.value;
    } else if (type === 'sms') {
        contenu = document.getElementById(`sms_contenu_${niveau}`)?.value;
    } else if (type === 'courrier') {
        contenu = document.getElementById(`courrier_contenu_${niveau}`)?.value;
    }

    fetch(`{{ route('esbtp.comptabilite.relances.config.preview') }}`, {
        method: 'POST',
        body: JSON.stringify({ type, niveau, contenu, sujet }),
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.text())
    .then(html => { container.innerHTML = html; })
    .catch(() => { container.innerHTML = '<div class="text-center text-danger py-3">Erreur lors de la génération de l\'aperçu</div>'; });
}

function envoyerTestTemplate() {
    showToast('info', 'Fonctionnalité d\'envoi de test en développement…');
}

function showToast(type, message) {
    const colors = {
        success: { bg: '#dcfce7', border: '#10b981', text: '#065f46', icon: 'check-circle' },
        error:   { bg: '#fee2e2', border: '#dc3545', text: '#7f1d1d', icon: 'times-circle' },
        warning: { bg: '#fef9e7', border: '#f59e0b', text: '#92400e', icon: 'exclamation-triangle' },
        info:    { bg: '#eff6ff', border: '#0453cb', text: '#1e3a8a', icon: 'info-circle' },
    };
    const c = colors[type] || colors.info;
    const div = document.createElement('div');
    div.style.cssText = `position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:420px;
        background:${c.bg};border:1.5px solid ${c.border};border-radius:14px;
        padding:.9rem 1.1rem;display:flex;align-items:flex-start;gap:.65rem;
        color:${c.text};font-size:.83rem;font-weight:500;
        box-shadow:0 10px 35px rgba(0,0,0,.14);animation:slideInRight .25s ease;`;
    div.innerHTML = `<i class="fas fa-${c.icon}" style="margin-top:.15rem;flex-shrink:0;"></i><span>${message}</span>`;
    document.body.appendChild(div);
    setTimeout(() => { div.style.opacity = '0'; div.style.transition = 'opacity .3s'; setTimeout(() => div.remove(), 300); }, 4500);
}
</script>
<style>
@keyframes slideInRight {
    from { transform: translateX(30px); opacity: 0; }
    to   { transform: translateX(0);    opacity: 1; }
}
</style>
@endpush
