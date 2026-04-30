@extends('layouts.app')

@section('title', 'Relance — ' . $etudiant->nom_complet)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ═══════════════════════════════════════════════════════════════════
   RELANCE HERO — fiche d'urgence financière
   ═══════════════════════════════════════════════════════════════════ */
:root {
    --risk-critical: #1e293b;
    --risk-high:     #0453cb;
    --risk-medium:   #5e91de;
    --risk-low:      #10b981;
}

/* ── Hero principal ─────────────────────────────────────────────── */
.relance-hero {
    position: relative;
    background: linear-gradient(135deg, #0f172a 0%, #0c2460 60%, #1e293b 100%);
    border-radius: 20px;
    padding: 36px 40px;
    margin-bottom: 28px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.35);
}
.relance-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 85% 50%, rgba(94,145,222,.18) 0%, transparent 70%),
        radial-gradient(ellipse 30% 60% at 5% 80%, rgba(4,83,203,.22) 0%, transparent 70%);
    pointer-events: none;
}
.relance-hero::after {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 240px; height: 240px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,.05);
    pointer-events: none;
}

/* ── Badge alerte priorité ──────────────────────────────────────── */
.priority-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 5px 14px;
    border-radius: 24px;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    margin-bottom: 16px;
}
.priority-badge.critical { background: rgba(30,41,59,.4);   color: #94a3b8; border: 1px solid rgba(30,41,59,.6); }
.priority-badge.high     { background: rgba(4,83,203,.25);   color: #93c5fd; border: 1px solid rgba(4,83,203,.4); }
.priority-badge.medium   { background: rgba(94,145,222,.25); color: #bfdbfe; border: 1px solid rgba(94,145,222,.4); }
.priority-badge.low      { background: rgba(16,185,129,.25); color: #6ee7b7; border: 1px solid rgba(16,185,129,.4); }
.priority-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; animation: pulse-dot 1.8s ease-in-out infinite; }
@keyframes pulse-dot { 0%,100%{ opacity:1; transform:scale(1); } 50%{ opacity:.5; transform:scale(.7); } }

/* ── Nom étudiant ───────────────────────────────────────────────── */
.hero-student-name {
    font-size: 2rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.15;
    letter-spacing: -.02em;
    margin-bottom: 8px;
}
.hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
}
.hero-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .8rem;
    color: rgba(255,255,255,.6);
}
.hero-meta-item i { font-size: .75rem; color: rgba(255,255,255,.4); }

/* ── Solde héroïque ─────────────────────────────────────────────── */
.hero-solde-bloc {
    text-align: right;
    flex-shrink: 0;
}
.hero-solde-amount {
    font-size: 2.6rem;
    font-weight: 900;
    color: #fca5a5;
    line-height: 1;
    letter-spacing: -.03em;
}
.hero-solde-label {
    font-size: .72rem;
    color: rgba(255,255,255,.45);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-top: 4px;
}
.hero-solde-paye {
    font-size: .78rem;
    color: #6ee7b7;
    margin-top: 6px;
}
.hero-solde-paye i { margin-right: 4px; }

/* ── Progress ring SVG ──────────────────────────────────────────── */
.progress-ring-wrap {
    position: relative;
    width: 88px;
    height: 88px;
    flex-shrink: 0;
}
.progress-ring-wrap svg { transform: rotate(-90deg); }
.ring-track { fill: none; stroke: rgba(255,255,255,.08); stroke-width: 6; }
.ring-fill  { fill: none; stroke-width: 6; stroke-linecap: round; transition: stroke-dashoffset .6s ease; }
.ring-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: .65rem;
    color: rgba(255,255,255,.5);
    text-align: center;
}
.ring-center strong { font-size: 1.1rem; font-weight: 800; color: #fff; display: block; line-height: 1; }

/* ── Hero actions ───────────────────────────────────────────────── */
.hero-actions-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,.08);
}
.hero-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    border-radius: 10px;
    font-size: .82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all .18s;
    cursor: pointer;
    border: none;
    font-family: inherit;
}
.hero-action-btn.ghost {
    background: rgba(255,255,255,.08);
    color: rgba(255,255,255,.8);
    border: 1px solid rgba(255,255,255,.12);
}
.hero-action-btn.ghost:hover { background: rgba(255,255,255,.15); color: #fff; }
.hero-action-btn.primary-glow {
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    box-shadow: 0 4px 14px rgba(4,83,203,.4);
}
.hero-action-btn.primary-glow:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(4,83,203,.5); }

/* ═══════════════════════════════════════════════════════════════════
   KPI STRIP
   ═══════════════════════════════════════════════════════════════════ */
.kpi-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
.kpi-card {
    background: var(--card-bg, #fff);
    border-radius: 14px;
    padding: 20px 22px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    transition: transform .15s, box-shadow .15s;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 14px 14px 0 0;
}
.kpi-card.total::before  { background: linear-gradient(90deg, #0453cb, #5e91de); }
.kpi-card.paye::before   { background: linear-gradient(90deg, #059669, #10b981); }
.kpi-card.restant::before{ background: linear-gradient(90deg, #1e293b, #0453cb); }
.kpi-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .9rem;
    margin-bottom: 10px;
}
.kpi-card.total   .kpi-icon { background: rgba(4,83,203,.1);  color: #0453cb; }
.kpi-card.paye    .kpi-icon { background: rgba(5,150,105,.1); color: #059669; }
.kpi-card.restant .kpi-icon { background: rgba(220,38,38,.1); color: #dc2626; }
.kpi-value { font-size: 1.35rem; font-weight: 800; color: var(--text-primary, #1e293b); line-height: 1.1; }
.kpi-value span { font-size: .7rem; font-weight: 600; color: var(--text-secondary, #64748b); margin-left: 3px; }
.kpi-label { font-size: .74rem; color: var(--text-secondary, #64748b); margin-top: 3px; }

/* ═══════════════════════════════════════════════════════════════════
   MAIN LAYOUT
   ═══════════════════════════════════════════════════════════════════ */
.relance-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1100px) { .relance-grid { grid-template-columns: 1fr; } }

/* ═══════════════════════════════════════════════════════════════════
   SECTION CARDS
   ═══════════════════════════════════════════════════════════════════ */
.relance-section {
    background: var(--card-bg, #fff);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.section-title {
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--text-secondary, #64748b);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-title i { font-size: .85rem; }

/* ── Frais impayés ──────────────────────────────────────────────── */
.frais-row {
    padding: 12px 0;
    border-bottom: 1px solid var(--border-light, #f0f4f8);
}
.frais-row:last-child { border-bottom: none; padding-bottom: 0; }
.frais-name { font-size: .88rem; font-weight: 600; color: var(--text-primary, #1e293b); margin-bottom: 6px; }
.frais-bar-track {
    height: 5px;
    background: #f1f5f9;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 6px;
}
.frais-bar-fill { height: 100%; border-radius: 3px; transition: width .4s ease; }
.frais-amounts {
    display: flex;
    justify-content: space-between;
    font-size: .74rem;
    color: var(--text-secondary, #64748b);
}
.frais-amounts .restant { font-weight: 700; color: #dc2626; }
.frais-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: .65rem;
    font-weight: 700;
    flex-shrink: 0;
}
.frais-badge.solde   { background: rgba(5,150,105,.1);  color: #059669; }
.frais-badge.partiel { background: rgba(217,119,6,.1);  color: #d97706; }
.frais-badge.impaye  { background: rgba(220,38,38,.1);  color: #dc2626; }

/* ── Timeline historique ────────────────────────────────────────── */
.timeline { position: relative; padding-left: 28px; }
.timeline::before {
    content: '';
    position: absolute;
    left: 9px; top: 12px; bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, rgba(4,83,203,.3) 0%, transparent 100%);
}
.tl-item { position: relative; margin-bottom: 18px; }
.tl-item:last-child { margin-bottom: 0; }
.tl-dot {
    position: absolute;
    left: -24px; top: 3px;
    width: 20px; height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .55rem;
    color: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.tl-content { font-size: .83rem; }
.tl-header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 2px;
    gap: 8px;
}
.tl-type { font-weight: 700; color: var(--text-primary, #1e293b); }
.tl-date { font-size: .72rem; color: var(--text-secondary, #64748b); flex-shrink: 0; }
.tl-msg { color: var(--text-secondary, #64748b); font-size: .8rem; line-height: 1.4; }

/* ═══════════════════════════════════════════════════════════════════
   SIDEBAR DROITE
   ═══════════════════════════════════════════════════════════════════ */
.sidebar-sticky { position: sticky; top: 20px; }

/* ── Contact card ───────────────────────────────────────────────── */
.contact-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    background: var(--bg-light, #f8fafc);
    margin-bottom: 8px;
    text-decoration: none;
    transition: background .15s;
}
.contact-chip:hover { background: var(--border-light, #f0f4f8); }
.contact-chip .chip-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    flex-shrink: 0;
}
.contact-chip .chip-icon.email { background: rgba(4,83,203,.1);  color: #0453cb; }
.contact-chip .chip-icon.phone { background: rgba(16,185,129,.1); color: #10b981; }
.contact-chip .chip-icon.addr  { background: rgba(4,83,203,.1);  color: #0453cb; }
.contact-chip .chip-text { font-size: .82rem; font-weight: 500; color: var(--text-primary, #1e293b); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.contact-chip .chip-sub  { font-size: .7rem; color: var(--text-secondary, #64748b); }

.contact-person {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--text-secondary, #64748b);
    margin: 14px 0 6px;
}
.contact-person:first-child { margin-top: 0; }

/* ── Action buttons ─────────────────────────────────────────────── */
.action-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1.5px solid transparent;
    background: transparent;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    transition: all .18s;
    margin-bottom: 10px;
}
.action-trigger:last-child { margin-bottom: 0; }
.action-trigger .at-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .95rem;
    flex-shrink: 0;
    transition: transform .18s;
}
.action-trigger:hover .at-icon { transform: scale(1.08); }
.action-trigger .at-text { flex: 1; }
.action-trigger .at-title { font-size: .88rem; font-weight: 700; color: var(--text-primary, #1e293b); }
.action-trigger .at-sub   { font-size: .73rem; color: var(--text-secondary, #64748b); margin-top: 1px; }
.action-trigger .at-arrow { color: var(--text-secondary, #64748b); font-size: .75rem; transition: transform .18s; }
.action-trigger:hover .at-arrow { transform: translateX(3px); }

.action-trigger.email-action { border-color: rgba(4,83,203,.2); }
.action-trigger.email-action:hover { background: rgba(4,83,203,.04); border-color: rgba(4,83,203,.4); }
.action-trigger.email-action .at-icon { background: rgba(4,83,203,.1); color: #0453cb; }

.action-trigger.call-action { border-color: rgba(16,185,129,.2); }
.action-trigger.call-action:hover { background: rgba(16,185,129,.04); border-color: rgba(16,185,129,.4); }
.action-trigger.call-action .at-icon { background: rgba(16,185,129,.1); color: #10b981; }

.action-trigger.demeure-action { border-color: rgba(220,38,38,.2); }
.action-trigger.demeure-action:hover { background: rgba(220,38,38,.04); border-color: rgba(220,38,38,.4); }
.action-trigger.demeure-action .at-icon { background: rgba(220,38,38,.1); color: #dc2626; }

/* ── Paiement CTA ───────────────────────────────────────────────── */
.paiement-cta {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(5,150,105,.08) 0%, rgba(16,185,129,.04) 100%);
    border: 1.5px solid rgba(16,185,129,.25);
    text-decoration: none;
    transition: all .18s;
    margin-top: 16px;
}
.paiement-cta:hover {
    background: linear-gradient(135deg, rgba(5,150,105,.14) 0%, rgba(16,185,129,.08) 100%);
    border-color: rgba(16,185,129,.45);
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(16,185,129,.15);
}
.paiement-cta .cta-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    background: linear-gradient(135deg, #059669, #10b981);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: .95rem;
    flex-shrink: 0;
}
.paiement-cta .cta-label { font-size: .88rem; font-weight: 700; color: #059669; }
.paiement-cta .cta-sub   { font-size: .73rem; color: var(--text-secondary, #64748b); }

/* ═══════════════════════════════════════════════════════════════════
   MODAL overrides
   ═══════════════════════════════════════════════════════════════════ */
.modal-content { border-radius: 18px !important; border: none !important; }
.modal-header { padding: 22px 24px 16px; }
.modal-body   { padding: 0 24px 8px; }
.modal-footer { padding: 16px 24px 22px; }

/* ── Responsive ─────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .relance-hero { padding: 24px 20px; }
    .hero-student-name { font-size: 1.4rem; }
    .hero-solde-amount { font-size: 1.8rem; }
    .kpi-strip { grid-template-columns: 1fr; }
    .kpi-strip .kpi-card { display: flex; align-items: center; gap: 14px; }
    .kpi-strip .kpi-card .kpi-icon { margin-bottom: 0; flex-shrink: 0; }
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ── Breadcrumb / header row ───────────────────────────────── --}}
        <div class="dashboard-header mb-4">
            <div>
                <p style="font-size:.75rem;color:var(--text-secondary);margin-bottom:4px;text-transform:uppercase;letter-spacing:.08em;">
                    <i class="fas fa-bell me-1"></i>Module Comptabilité · Relances
                </p>
                <h1 class="page-title mb-0" style="font-size:1.4rem;">Dossier de relance</h1>
            </div>
            <div class="header-actions d-flex gap-2">
                <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left me-1"></i><span class="d-none d-sm-inline">Retour</span>
                </a>
                @php
                    $anneeHeaderLabel = $inscription->anneeUniversitaire ? ($inscription->anneeUniversitaire->name ?? $inscription->anneeUniversitaire->libelle ?? '') : '';
                    $anneeIsCurrent = $inscription->anneeUniversitaire && $inscription->anneeUniversitaire->is_current;
                @endphp
                <a href="{{ route('esbtp.inscriptions.show', $inscription) }}" class="btn-acasi primary" title="Fiche inscription {{ $anneeHeaderLabel }}">
                    <i class="fas fa-file-alt me-1"></i>
                    <span class="d-none d-sm-inline">Fiche inscription</span>
                    @if($anneeHeaderLabel)
                    <span class="d-none d-md-inline" style="font-size:.75em;opacity:.85;margin-left:4px;">({{ $anneeHeaderLabel }}{{ $anneeIsCurrent ? ' · en cours' : '' }})</span>
                    @endif
                </a>
            </div>
        </div>

        {{-- ── Flash messages ────────────────────────────────────────── --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" style="font-size:.85rem;">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" style="font-size:.85rem;">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════
             HERO
        ════════════════════════════════════════════════════════════ --}}
        @php
            $riskClass = match($riskLevel) {
                'critical' => 'critical',
                'high'     => 'high',
                'medium'   => 'medium',
                default    => 'low',
            };
            $riskIcon = match($riskLevel) {
                'critical' => 'fa-ban',
                'high'     => 'fa-exclamation-circle',
                'medium'   => 'fa-clock',
                default    => 'fa-check-circle',
            };
            $ringColor = match($riskLevel) {
                'critical' => '#94a3b8',
                'high'     => '#93c5fd',
                'medium'   => '#bfdbfe',
                default    => '#6ee7b7',
            };
            $circumference = 2 * M_PI * 36; // r=36
            $dashOffset = $circumference - ($pourcentagePaye / 100) * $circumference;
            $initials = strtoupper(substr($etudiant->nom ?? 'E', 0, 1)) . strtoupper(substr($etudiant->prenoms ?? '', 0, 1));
        @endphp

        <div class="relance-hero">
            <div class="d-flex align-items-start gap-4 flex-wrap position-relative">

                {{-- Avatar + ring ─────────────────────────────────────── --}}
                <div style="position:relative; flex-shrink:0;">
                    <div style="
                        width:72px; height:72px;
                        border-radius:50%;
                        background: linear-gradient(135deg,rgba(255,255,255,.18),rgba(255,255,255,.06));
                        border: 2px solid rgba(255,255,255,.2);
                        backdrop-filter: blur(8px);
                        display:flex; align-items:center; justify-content:center;
                        font-size:1.5rem; font-weight:800; color:#fff;
                        letter-spacing:-.02em;
                    ">{{ $initials }}</div>
                    {{-- Risk pulse ──────────────────────────────────────── --}}
                    <span style="
                        position:absolute; bottom:-2px; right:-2px;
                        width:20px; height:20px; border-radius:50%;
                        background: {{ $riskColor }};
                        border: 2px solid #0f172a;
                        display:flex; align-items:center; justify-content:center;
                        font-size:.55rem; color:#fff;
                    "><i class="fas {{ $riskIcon }}"></i></span>
                </div>

                {{-- Identité ───────────────────────────────────────────── --}}
                <div class="flex-grow-1">
                    <div class="priority-badge {{ $riskClass }}">
                        <span class="dot"></span>
                        {{ $riskLabel }}
                    </div>
                    <h2 class="hero-student-name">{{ $etudiant->nom_complet }}</h2>
                    <div class="hero-meta">
                        <span class="hero-meta-item">
                            <i class="fas fa-id-card"></i>{{ $etudiant->matricule }}
                        </span>
                        @if($inscription->classe)
                        <span class="hero-meta-item">
                            <i class="fas fa-graduation-cap"></i>{{ $inscription->classe->name ?? $inscription->classe->nom }}
                        </span>
                        @endif
                        @if($inscription->anneeUniversitaire)
                        <span class="hero-meta-item">
                            <i class="fas fa-calendar"></i>{{ $inscription->anneeUniversitaire->name ?? $inscription->anneeUniversitaire->libelle }}
                            @if($inscription->anneeUniversitaire->is_current)
                            <span style="display:inline-flex;align-items:center;gap:4px;background:rgba(16,185,129,.18);color:#10b981;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:20px;letter-spacing:.05em;border:1px solid rgba(16,185,129,.3);">
                                <i class="fas fa-circle" style="font-size:.45rem;"></i>EN COURS
                            </span>
                            @endif
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Progress ring + Solde ──────────────────────────────── --}}
                <div class="d-flex align-items-center gap-4 flex-wrap" style="margin-left:auto;">
                    {{-- Ring SVG --}}
                    <div class="progress-ring-wrap">
                        <svg width="88" height="88" viewBox="0 0 88 88">
                            <circle class="ring-track" cx="44" cy="44" r="36"/>
                            <circle class="ring-fill"
                                cx="44" cy="44" r="36"
                                stroke="{{ $ringColor }}"
                                stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $dashOffset }}"
                            />
                        </svg>
                        <div class="ring-center">
                            <strong>{{ $pourcentagePaye }}%</strong>
                            payé
                        </div>
                    </div>

                    {{-- Solde --}}
                    <div class="hero-solde-bloc">
                        <div class="hero-solde-amount">{{ number_format($soldeRestant, 0, ',', ' ') }}</div>
                        <div class="hero-solde-label">FCFA restant dû</div>
                        @if($totalPaye > 0)
                        <div class="hero-solde-paye"><i class="fas fa-check-circle"></i>{{ number_format($totalPaye, 0, ',', ' ') }} FCFA encaissé</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions bar ─────────────────────────────────────────────── --}}
            <div class="hero-actions-bar">
                @can('comptabilite.relances.send')
                @if($etudiant->email_personnel || ($etudiant->parents && $etudiant->parents->first() && $etudiant->parents->first()->email))
                <button class="hero-action-btn ghost" data-bs-toggle="modal" data-bs-target="#emailModal">
                    <i class="fas fa-envelope"></i>Email de relance
                </button>
                @endif
                <button class="hero-action-btn ghost" data-bs-toggle="modal" data-bs-target="#appelModal">
                    <i class="fas fa-phone"></i>Enregistrer un appel
                </button>
                @endcan
                @if($soldeRestant > 0)
                @can('paiements.create')
                <a href="{{ route('esbtp.inscriptions.show', $inscription) }}" class="hero-action-btn primary-glow ms-auto">
                    <i class="fas fa-plus-circle"></i>Enregistrer un paiement
                </a>
                @endcan
                @endif
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════════
             KPI STRIP
        ════════════════════════════════════════════════════════════ --}}
        <div class="kpi-strip">
            <div class="kpi-card total">
                <div class="kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="kpi-value">{{ number_format($totalDu, 0, ',', ' ') }} <span>FCFA</span></div>
                <div class="kpi-label">Total dû</div>
            </div>
            <div class="kpi-card paye">
                <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
                <div class="kpi-value">{{ number_format($totalPaye, 0, ',', ' ') }} <span>FCFA</span></div>
                <div class="kpi-label">Encaissé</div>
            </div>
            <div class="kpi-card restant">
                <div class="kpi-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="kpi-value">{{ number_format($soldeRestant, 0, ',', ' ') }} <span>FCFA</span></div>
                <div class="kpi-label">Reste à percevoir</div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════════
             GRID PRINCIPAL
        ════════════════════════════════════════════════════════════ --}}
        <div class="relance-grid">

            {{-- ──────────── COLONNE GAUCHE ──────────── --}}
            <div>

                {{-- Frais impayés ─────────────────────────────────────── --}}
                <div class="relance-section">
                    <div class="section-title">
                        <i class="fas fa-exclamation-circle text-danger"></i>
                        Détail des impayés
                    </div>

                    @if($fraisImpayés->isEmpty())
                    <div class="text-center py-4" style="color:var(--text-secondary);">
                        <i class="fas fa-check-circle fa-2x text-success mb-3 d-block"></i>
                        <span style="font-size:.88rem;">Tous les frais sont soldés.</span>
                    </div>
                    @else
                    @foreach($fraisImpayés as $frais)
                    @php
                        $restantFrais = max(0, $frais['amount'] - $frais['paye']);
                        $pctFrais = $frais['amount'] > 0 ? round($frais['paye'] / $frais['amount'] * 100) : 0;
                        $barColor = $restantFrais <= 0 ? '#10b981' : ($pctFrais > 0 ? '#5e91de' : '#0453cb');
                    @endphp
                    <div class="frais-row">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="frais-name">{{ $frais['name'] }}</span>
                            @if($restantFrais <= 0)
                                <span class="frais-badge solde"><i class="fas fa-check me-1"></i>Soldé</span>
                            @elseif($frais['paye'] > 0)
                                <span class="frais-badge partiel">{{ $pctFrais }}% payé</span>
                            @else
                                <span class="frais-badge impaye">Impayé</span>
                            @endif
                        </div>
                        <div class="frais-bar-track">
                            <div class="frais-bar-fill" style="width:{{ $pctFrais }}%; background:{{ $barColor }};"></div>
                        </div>
                        <div class="frais-amounts">
                            <span>{{ number_format($frais['amount'], 0, ',', ' ') }} F total</span>
                            <span class="text-success">{{ number_format($frais['paye'], 0, ',', ' ') }} F encaissé</span>
                            @if($restantFrais > 0)
                            <span class="restant">{{ number_format($restantFrais, 0, ',', ' ') }} F restant</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>

                {{-- Historique relances ───────────────────────────────── --}}
                <div class="relance-section">
                    <div class="section-title">
                        <i class="fas fa-history" style="color:#64748b;"></i>
                        Historique des relances
                    </div>

                    @if($historique->isEmpty())
                    <div class="text-center py-4" style="color:var(--text-secondary);">
                        <i class="fas fa-bell-slash fa-2x mb-3 d-block opacity-25"></i>
                        <span style="font-size:.85rem;">Aucune relance enregistrée pour cet étudiant.</span>
                    </div>
                    @else
                    <div class="timeline">
                        @foreach($historique as $item)
                        @php
                            $tlColor = match($item->type ?? 'email') {
                                'email'           => '#0453cb',
                                'sms'             => '#10b981',
                                'appel'           => '#5e91de',
                                'mise_en_demeure' => '#0453cb',
                                default           => '#64748b',
                            };
                            $tlIcon = match($item->type ?? 'email') {
                                'email'           => 'fa-envelope',
                                'sms'             => 'fa-comment-sms',
                                'appel'           => 'fa-phone',
                                'mise_en_demeure' => 'fa-gavel',
                                default           => 'fa-bell',
                            };
                            $tlLabel = match($item->type ?? 'email') {
                                'email'           => 'Email envoyé',
                                'sms'             => 'SMS envoyé',
                                'appel'           => 'Appel enregistré',
                                'mise_en_demeure' => 'Mise en demeure',
                                default           => ucfirst($item->type ?? 'Relance'),
                            };
                        @endphp
                        <div class="tl-item">
                            <div class="tl-dot" style="background:{{ $tlColor }};">
                                <i class="fas {{ $tlIcon }}"></i>
                            </div>
                            <div class="tl-content">
                                <div class="tl-header">
                                    <span class="tl-type">{{ $tlLabel }}</span>
                                    <span class="tl-date">{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</span>
                                </div>
                                @if($item->message ?? null)
                                <div class="tl-msg">{{ Str::limit($item->message, 120) }}</div>
                                @endif
                                @if($item->sent_by ?? null)
                                <div style="font-size:.71rem;color:var(--text-secondary);margin-top:3px;">
                                    <i class="fas fa-user me-1"></i>{{ $item->sent_by }}
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

            </div>

            {{-- ──────────── COLONNE DROITE (SIDEBAR) ──────────── --}}
            <div class="sidebar-sticky">

                {{-- Contacts ───────────────────────────────────────────── --}}
                <div class="relance-section mb-4">
                    <div class="section-title">
                        <i class="fas fa-address-book" style="color:#0453cb;"></i>
                        Informations contact
                    </div>

                    @php $parents = $etudiant->parents ?? collect(); @endphp

                    @if($etudiant->email_personnel || $etudiant->telephone)
                    <p class="contact-person">Étudiant</p>
                    @if($etudiant->email_personnel)
                    <a href="mailto:{{ $etudiant->email_personnel }}" class="contact-chip">
                        <div class="chip-icon email"><i class="fas fa-envelope"></i></div>
                        <div>
                            <div class="chip-text">{{ $etudiant->email_personnel }}</div>
                            <div class="chip-sub">Email personnel</div>
                        </div>
                    </a>
                    @endif
                    @if($etudiant->telephone)
                    <a href="tel:{{ $etudiant->telephone }}" class="contact-chip">
                        <div class="chip-icon phone"><i class="fas fa-phone"></i></div>
                        <div>
                            <div class="chip-text">{{ $etudiant->telephone }}</div>
                            <div class="chip-sub">Téléphone direct</div>
                        </div>
                    </a>
                    @endif
                    @endif

                    @forelse($parents as $parent)
                    <p class="contact-person">{{ $parent->type_lien ?? 'Parent / Tuteur' }} — {{ $parent->nom }} {{ $parent->prenoms }}</p>
                    @if($parent->telephone)
                    <a href="tel:{{ $parent->telephone }}" class="contact-chip">
                        <div class="chip-icon phone"><i class="fas fa-phone"></i></div>
                        <div>
                            <div class="chip-text">{{ $parent->telephone }}</div>
                            <div class="chip-sub">Téléphone</div>
                        </div>
                    </a>
                    @endif
                    @if($parent->email)
                    <a href="mailto:{{ $parent->email }}" class="contact-chip">
                        <div class="chip-icon email"><i class="fas fa-envelope"></i></div>
                        <div>
                            <div class="chip-text">{{ $parent->email }}</div>
                            <div class="chip-sub">Email</div>
                        </div>
                    </a>
                    @endif
                    @if($parent->adresse)
                    <div class="contact-chip" style="cursor:default;">
                        <div class="chip-icon addr"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div class="chip-text">{{ $parent->adresse }}</div>
                            <div class="chip-sub">Adresse</div>
                        </div>
                    </div>
                    @endif
                    @empty
                    @if(!$etudiant->email_personnel && !$etudiant->telephone)
                    <p style="font-size:.82rem;color:var(--text-secondary);">Aucun contact renseigné.</p>
                    @endif
                    @endforelse
                </div>

                {{-- Actions de relance ──────────────────────────────────── --}}
                @can('comptabilite.relances.send')
                <div class="relance-section">
                    <div class="section-title">
                        <i class="fas fa-paper-plane" style="color:#5e91de;"></i>
                        Actions
                    </div>

                    @if($etudiant->email_personnel || ($parents->first() && $parents->first()->email))
                    <button class="action-trigger email-action" data-bs-toggle="modal" data-bs-target="#emailModal">
                        <div class="at-icon"><i class="fas fa-envelope"></i></div>
                        <div class="at-text">
                            <div class="at-title">Email de relance</div>
                            <div class="at-sub">Rappel de paiement personnalisé</div>
                        </div>
                        <i class="fas fa-chevron-right at-arrow"></i>
                    </button>
                    @endif

                    <button class="action-trigger call-action" data-bs-toggle="modal" data-bs-target="#appelModal">
                        <div class="at-icon"><i class="fas fa-phone"></i></div>
                        <div class="at-text">
                            <div class="at-title">Enregistrer un appel</div>
                            <div class="at-sub">Log de contact téléphonique</div>
                        </div>
                        <i class="fas fa-chevron-right at-arrow"></i>
                    </button>

                    <button class="action-trigger demeure-action" data-bs-toggle="modal" data-bs-target="#demeurModal">
                        <div class="at-icon"><i class="fas fa-gavel"></i></div>
                        <div class="at-text">
                            <div class="at-title">Mise en demeure</div>
                            <div class="at-sub">Courrier formel officiel</div>
                        </div>
                        <i class="fas fa-chevron-right at-arrow"></i>
                    </button>

                    @if($soldeRestant > 0)
                    @can('paiements.create')
                    <a href="{{ route('esbtp.inscriptions.show', $inscription) }}" class="paiement-cta">
                        <div class="cta-icon"><i class="fas fa-plus"></i></div>
                        <div>
                            <div class="cta-label">Enregistrer un paiement</div>
                            <div class="cta-sub">
                                Inscription {{ $inscription->anneeUniversitaire->name ?? $inscription->anneeUniversitaire->libelle ?? '' }}
                                @if($inscription->anneeUniversitaire && $inscription->anneeUniversitaire->is_current)
                                <span style="color:#10b981;font-weight:600;">· en cours</span>
                                @endif
                            </div>
                        </div>
                        <i class="fas fa-arrow-right ms-auto" style="color:#10b981;"></i>
                    </a>
                    @endcan
                    @endif
                </div>
                @endcan

                {{-- ── Autres inscriptions (navigation multi-années) ────── --}}
                @if($autresInscriptions->isNotEmpty())
                <div class="relance-section mt-3">
                    <div class="section-title" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin-bottom:10px;">
                        <i class="fas fa-history me-1"></i>Autres années
                    </div>
                    <div class="d-flex flex-column gap-2">
                        @foreach($autresInscriptions as $autreInscription)
                        @php
                            $anneeLabel = $autreInscription->anneeUniversitaire->name ?? $autreInscription->anneeUniversitaire->libelle ?? 'Année inconnue';
                            $classeLabel = $autreInscription->classe->name ?? $autreInscription->classe->nom ?? '';
                        @endphp
                        <a href="{{ route('esbtp.comptabilite.relances.etudiant', $autreInscription) }}"
                           style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;text-decoration:none;color:#1e293b;transition:all .15s ease;"
                           onmouseover="this.style.background='#f0f7ff';this.style.borderColor='#0453cb';"
                           onmouseout="this.style.background='#f8fafc';this.style.borderColor='#e2e8f0';">
                            @php $autreIsCurrent = $autreInscription->anneeUniversitaire && $autreInscription->anneeUniversitaire->is_current; @endphp
                            <div style="width:34px;height:34px;border-radius:8px;background:{{ $autreIsCurrent ? 'rgba(16,185,129,.12)' : '#e9ecef' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-calendar-alt" style="font-size:.8rem;color:{{ $autreIsCurrent ? '#10b981' : '#64748b' }};"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ $anneeLabel }}
                                    @if($autreIsCurrent)
                                    <span style="font-size:.68rem;color:#10b981;font-weight:700;margin-left:4px;">EN COURS</span>
                                    @endif
                                </div>
                                @if($classeLabel)
                                <div style="font-size:.75rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $classeLabel }}</div>
                                @endif
                            </div>
                            <i class="fas fa-chevron-right" style="font-size:.7rem;color:#94a3b8;flex-shrink:0;"></i>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>{{-- /sidebar --}}
        </div>{{-- /grid --}}

    </div>{{-- /main-content --}}
</div>{{-- /dashboard-acasi --}}

{{-- ════════════════════════════════════════════════════════════════════
     MODALS
════════════════════════════════════════════════════════════════════ --}}

{{-- Modal Email ─────────────────────────────────────────────────────── --}}
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <span style="width:34px;height:34px;border-radius:9px;background:rgba(4,83,203,.1);color:#0453cb;display:inline-flex;align-items:center;justify-content:center;font-size:.85rem;">
                        <i class="fas fa-envelope"></i>
                    </span>
                    Email de relance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('esbtp.comptabilite.relances.renvoyer', $inscription->id) }}">
                @csrf
                <input type="hidden" name="type" value="email">
                <input type="hidden" name="inscription_id" value="{{ $inscription->id }}">
                <div class="modal-body pt-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Destinataire</label>
                        <input type="email" class="form-control rounded-3" name="email"
                               value="{{ $etudiant->email_personnel ?? ($parents->first()->email ?? '') }}"
                               placeholder="email@exemple.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Message personnalisé <span class="text-muted fw-normal">(optionnel)</span></label>
                        <textarea class="form-control rounded-3" name="message" rows="3"
                                  placeholder="Ajouter un message spécifique..."></textarea>
                    </div>
                    <div class="rounded-3 p-3 d-flex align-items-center gap-2" style="background:#f0f9ff;font-size:.8rem;color:#0369a1;">
                        <i class="fas fa-info-circle flex-shrink-0"></i>
                        Solde restant dû : <strong class="ms-1">{{ number_format($soldeRestant, 0, ',', ' ') }} FCFA</strong>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-paper-plane me-1"></i>Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Appel ─────────────────────────────────────────────────────── --}}
<div class="modal fade" id="appelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <span style="width:34px;height:34px;border-radius:9px;background:rgba(16,185,129,.1);color:#10b981;display:inline-flex;align-items:center;justify-content:center;font-size:.85rem;">
                        <i class="fas fa-phone"></i>
                    </span>
                    Enregistrer un appel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('esbtp.comptabilite.relances.renvoyer', $inscription->id) }}">
                @csrf
                <input type="hidden" name="type" value="appel">
                <input type="hidden" name="inscription_id" value="{{ $inscription->id }}">
                <div class="modal-body pt-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Numéro appelé</label>
                        <input type="text" class="form-control rounded-3" name="telephone"
                               value="{{ $etudiant->telephone ?? ($parents->first()->telephone ?? '') }}"
                               placeholder="+225 xx xx xx xx xx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">
                            Résultat de l'appel <span class="text-danger">*</span>
                        </label>
                        <select class="form-select rounded-3" name="resultat" required>
                            <option value="contact">Contact établi</option>
                            <option value="absent">Absent / messagerie</option>
                            <option value="promesse">Promesse de paiement</option>
                            <option value="refuse">Refus de payer</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">
                            Note de l'appel <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control rounded-3" name="message" rows="3" required
                                  placeholder="Résumé de la conversation, accord pris..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn-acasi success">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Mise en demeure ───────────────────────────────────────────── --}}
<div class="modal fade" id="demeurModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <span style="width:34px;height:34px;border-radius:9px;background:rgba(220,38,38,.1);color:#dc2626;display:inline-flex;align-items:center;justify-content:center;font-size:.85rem;">
                        <i class="fas fa-gavel"></i>
                    </span>
                    Mise en demeure formelle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('esbtp.comptabilite.relances.renvoyer', $inscription->id) }}">
                @csrf
                <input type="hidden" name="type" value="mise_en_demeure">
                <input type="hidden" name="inscription_id" value="{{ $inscription->id }}">
                <div class="modal-body pt-4">
                    <div class="rounded-3 p-3 mb-4 d-flex align-items-start gap-2" style="background:#fff5f5;border:1px solid rgba(220,38,38,.2);font-size:.8rem;color:#b91c1c;">
                        <i class="fas fa-exclamation-triangle flex-shrink-0 mt-1"></i>
                        <span>Cette action génère un courrier de mise en demeure officiel pour
                        <strong>{{ number_format($soldeRestant, 0, ',', ' ') }} FCFA</strong>.
                        Cette action sera enregistrée dans l'historique.</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Délai de paiement accordé</label>
                        <select class="form-select rounded-3" name="delai_jours">
                            <option value="7">7 jours</option>
                            <option value="14" selected>14 jours</option>
                            <option value="30">30 jours</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Observations</label>
                        <textarea class="form-control rounded-3" name="message" rows="2"
                                  placeholder="Informations complémentaires (optionnel)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn-acasi danger">
                        <i class="fas fa-paper-plane me-1"></i>Envoyer la mise en demeure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
