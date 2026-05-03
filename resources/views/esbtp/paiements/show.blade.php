@extends('layouts.app')

@section('title', 'Paiement #' . $paiement->numero_recu . ' — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   PAIEMENT SHOW PREMIUM — KLASSCI Design System 2025
   Namespace: ps-  (payment-show)
=================================================================== */

:root {
    --k-blue:      #0453cb;
    --k-blue-2:    #5e91de;
    --k-surface:   #f4f7fb;
    --k-card:      #ffffff;
    --k-border:    #e2e8f0;
    --k-text:      #1e293b;
    --k-muted:     #64748b;
    --k-success:   #10b981;
    --k-warning:   #f59e0b;
    --k-danger:    #ef4444;
    --k-radius:    12px;
    --k-radius-lg: 20px;
    --k-shadow:    0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
    --k-shadow-lg: 0 8px 32px rgba(4,83,203,.12);
}

.ps-page { background: var(--k-surface); min-height: 100vh; }

/* ── HERO ────────────────────────────────────────────────────── */
@php
    $statusColor = match($paiement->status) {
        'validé' => '#10b981',
        'en_attente' => '#f59e0b',
        'rejeté' => '#ef4444',
        default => '#94a3b8',
    };
@endphp

.ps-hero {
    position: relative;
    background: linear-gradient(135deg, var(--k-blue) 0%, var(--k-blue-2) 100%);
}
.ps-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='12' cy='12' r='1.5' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E");
    pointer-events: none;
}
.ps-hero::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 48px;
    background: linear-gradient(to top, var(--k-surface), transparent);
}
.ps-hero-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    padding: 32px 32px 44px;
}
.ps-hero-top {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 20px; flex-wrap: wrap;
}
.ps-hero-left { flex: 1; min-width: 250px; }
.ps-hero-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-start; }

/* Receipt number */
.ps-receipt {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 5px 14px; border-radius: 50px;
    background: rgba(255,255,255,.12);
    color: rgba(255,255,255,.9);
    font-size: .8rem; font-weight: 600;
    border: 1px solid rgba(255,255,255,.2);
    backdrop-filter: blur(4px);
    margin-bottom: 12px;
    cursor: pointer;
    transition: background .2s;
}
.ps-receipt:hover { background: rgba(255,255,255,.22); }
.ps-receipt code { background: none; color: #fff; font-size: .8rem; font-weight: 700; }

/* Amount */
.ps-amount {
    font-size: 2.8rem; font-weight: 900; color: #fff;
    letter-spacing: -.03em; line-height: 1.1;
    text-shadow: 0 2px 8px rgba(0,0,0,.15);
    margin-bottom: 8px;
}
.ps-amount-currency { font-size: 1.2rem; font-weight: 600; opacity: .7; margin-left: 6px; }

.ps-date {
    font-size: .88rem; color: rgba(255,255,255,.7); font-weight: 500;
    display: flex; align-items: center; gap: 6px;
}

/* Status pill */
.ps-status {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border-radius: 50px;
    font-size: .82rem; font-weight: 700; letter-spacing: .03em;
    text-transform: uppercase;
    background: {{ $statusColor }}15;
    color: {{ $statusColor }};
    border: 2px solid {{ $statusColor }};
    margin-bottom: 8px;
}

/* Hero buttons */
.ps-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 8px;
    font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer;
    transition: all .2s; border: none;
}
.ps-btn.ghost { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.3); }
.ps-btn.ghost:hover { background: rgba(255,255,255,.25); color: #fff; }
.ps-btn.success { background: var(--k-success); color: #fff; }
.ps-btn.success:hover { box-shadow: 0 4px 12px rgba(16,185,129,.4); }
.ps-btn.danger { background: var(--k-danger); color: #fff; }
.ps-btn.danger:hover { box-shadow: 0 4px 12px rgba(239,68,68,.4); }
.ps-btn.warning { background: var(--k-warning); color: #fff; }
.ps-btn.primary { background: linear-gradient(135deg, var(--k-blue), var(--k-blue-2)); color: #fff; }
.ps-btn.primary:hover { box-shadow: 0 4px 12px rgba(4,83,203,.4); }

/* Dropdown fix */
.ps-dropdown { position: relative; }
.ps-dropdown .dropdown-menu {
    min-width: 180px; border-radius: 10px; border: 1px solid var(--k-border);
    box-shadow: 0 8px 24px rgba(0,0,0,.12); overflow: hidden;
}
.ps-dropdown .dropdown-item { padding: 10px 16px; font-size: .85rem; font-weight: 500; transition: background .15s; }
.ps-dropdown .dropdown-item:hover { background: rgba(4,83,203,.06); color: var(--k-blue); }
.ps-dropdown .dropdown-item i { width: 18px; text-align: center; margin-right: 8px; }

/* ── CONTENT ─────────────────────────────────────────────────── */
.ps-content {
    max-width: 1280px; margin: -20px auto 0;
    padding: 0 24px 40px;
    position: relative; z-index: 3;
}

/* ── CARDS ────────────────────────────────────────────────────── */
.ps-card {
    background: var(--k-card);
    border: 1px solid var(--k-border);
    border-radius: var(--k-radius-lg);
    box-shadow: var(--k-shadow);
    overflow: hidden;
    animation: psFadeUp .5s ease both;
}
.ps-card:nth-child(2) { animation-delay: .06s; }
.ps-card:nth-child(3) { animation-delay: .12s; }

.ps-card-header {
    display: flex; align-items: center; gap: 12px;
    padding: 18px 24px;
    border-bottom: 1px solid var(--k-border);
    background: linear-gradient(135deg, rgba(4,83,203,.02), rgba(94,145,222,.02));
}
.ps-card-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--k-blue), var(--k-blue-2));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .85rem; flex-shrink: 0;
}
.ps-card-title { font-size: 1rem; font-weight: 700; color: var(--k-text); }
.ps-card-body { padding: 24px; }

/* Info rows */
.ps-info { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
.ps-info:last-child { border-bottom: none; }
.ps-info-lbl { font-size: .84rem; font-weight: 600; color: var(--k-muted); }
.ps-info-val { font-size: .88rem; font-weight: 600; color: var(--k-text); text-align: right; }

/* Student block */
.ps-student {
    display: flex; align-items: center; gap: 16px;
    padding: 16px; margin-bottom: 16px;
    background: linear-gradient(135deg, rgba(4,83,203,.03), rgba(94,145,222,.03));
    border-radius: 14px; border: 1px solid rgba(4,83,203,.08);
}
.ps-student-avatar {
    width: 56px; height: 56px; border-radius: 50%;
    background: linear-gradient(135deg, var(--k-blue), var(--k-blue-2));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: 1.1rem;
    flex-shrink: 0; overflow: hidden;
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
}
.ps-student-avatar img { width: 100%; height: 100%; object-fit: cover; }
.ps-student-name { font-size: 1rem; font-weight: 700; color: var(--k-text); margin: 0 0 2px; }
.ps-student-mat { font-size: .82rem; color: var(--k-muted); font-weight: 500; }

/* Badges */
.ps-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 50px;
    font-size: .76rem; font-weight: 700; letter-spacing: .02em;
}
.ps-badge.blue { background: rgba(4,83,203,.08); color: var(--k-blue); }
.ps-badge.green { background: rgba(16,185,129,.08); color: var(--k-success); }
.ps-badge.amber { background: rgba(245,158,11,.08); color: #d97706; }
.ps-badge.info { background: rgba(14,165,233,.08); color: #0284c7; }

/* Action links */
.ps-action-links {
    display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px;
    padding-top: 16px; border-top: 1px solid #f1f5f9;
}
.ps-link {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 8px;
    font-size: .82rem; font-weight: 600;
    text-decoration: none; transition: all .2s;
    background: rgba(4,83,203,.06); color: var(--k-blue);
    border: 1px solid rgba(4,83,203,.12);
}
.ps-link:hover { background: var(--k-blue); color: #fff; }

/* Timeline */
.ps-timeline { display: flex; flex-direction: column; gap: 0; }
.ps-tl-item {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
}
.ps-tl-item:last-child { border-bottom: none; }
.ps-tl-dot {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: .7rem;
}
.ps-tl-dot.created { background: rgba(16,185,129,.1); color: var(--k-success); }
.ps-tl-dot.updated { background: rgba(245,158,11,.1); color: var(--k-warning); }
.ps-tl-dot.validated { background: rgba(4,83,203,.1); color: var(--k-blue); }
.ps-tl-text { font-size: .84rem; color: var(--k-text); line-height: 1.5; }
.ps-tl-text strong { font-weight: 700; }
.ps-tl-text .ps-tl-date { color: var(--k-muted); font-size: .78rem; }

/* Comment */
.ps-comment {
    background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
    border: 1.5px solid #bae6fd;
    border-left: 5px solid var(--k-blue);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 16px;
}
.ps-comment-label { font-size: .78rem; font-weight: 700; color: var(--k-blue); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
.ps-comment-text { font-size: .88rem; color: var(--k-text); line-height: 1.6; }

/* Pending alert */
.ps-pending-alert {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1.5px solid #fbbf24;
    border-left: 5px solid #f59e0b;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex; align-items: center; gap: 14px;
    flex-wrap: wrap;
}
.ps-pending-icon {
    width: 38px; height: 38px; background: #f59e0b; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.ps-pending-icon i { color: #fff; font-size: .85rem; }
.ps-pending-text { flex: 1; min-width: 180px; }
.ps-pending-text strong { color: #92400e; font-size: .9rem; }
.ps-pending-text p { color: #a16207; font-size: .82rem; margin: 2px 0 0; }
.ps-pending-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* ── Animation ───────────────────────────────────────────────── */
@keyframes psFadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 768px) {
    .ps-hero-inner { padding: 20px 16px 36px; }
    .ps-amount { font-size: 2rem; }
    .ps-content { padding: 0 12px 32px; }
    .ps-card-body { padding: 16px; }
    .ps-hero-top { flex-direction: column; }
    .ps-hero-right { width: 100%; }
}
</style>
@endsection

@section('content')
<div class="ps-page">

{{-- ═══ HERO ═══ --}}
<div class="ps-hero">
    <div class="ps-hero-inner">
        <div class="ps-hero-top">
            <div class="ps-hero-left">
                <div class="ps-receipt" onclick="navigator.clipboard.writeText('{{ $paiement->numero_recu }}'); this.querySelector('.ps-copy-hint').textContent='Copié !';" title="Cliquer pour copier">
                    <i class="fas fa-receipt"></i>
                    <code>{{ $paiement->numero_recu }}</code>
                    <span class="ps-copy-hint" style="font-size:.7rem; opacity:.6;">Copier</span>
                </div>
                <div class="ps-amount">
                    {{ number_format($paiement->montant, 0, ',', ' ') }}<span class="ps-amount-currency">FCFA</span>
                </div>
                <div class="ps-date">
                    <i class="fas fa-calendar-day"></i>
                    {{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y à H:i') : '—' }}
                </div>
                <div style="margin-top:14px;">
                    <span class="ps-status">
                        <i class="fas fa-{{ $paiement->status == 'validé' ? 'check-circle' : ($paiement->status == 'en_attente' ? 'clock' : 'times-circle') }}"></i>
                        {{ $paiement->status == 'validé' ? 'Validé' : ($paiement->status == 'en_attente' ? 'En attente' : 'Rejeté') }}
                    </span>
                </div>
            </div>

            <div class="ps-hero-right">
                <a href="{{ route('esbtp.paiements.index') }}" class="ps-btn ghost">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>

                @can('messages.send')
                    <x-share-to-chat kind="paiement" :id="$paiement->id" label="Envoyer" class="ps-btn ghost" />
                @endcan

                @if($paiement->status == 'validé')
                <div class="dropdown ps-dropdown">
                    <button class="ps-btn primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-pdf"></i> Reçu PDF
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('esbtp.paiements.preview', $paiement->id) }}"><i class="fas fa-eye"></i>Prévisualiser</a></li>
                        <li><a class="dropdown-item" href="{{ route('esbtp.paiements.recu', $paiement->id) }}"><i class="fas fa-download"></i>Télécharger</a></li>
                    </ul>
                </div>
                @endif

                @can('paiements.edit')
                <a href="{{ route('esbtp.paiements.edit', $paiement->id) }}" class="ps-btn warning">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                @endcan
                @can('paiements.delete')
                <form id="ps-form-delete-{{ $paiement->id }}" action="{{ route('esbtp.paiements.destroy', $paiement->id) }}" method="POST" style="margin:0">
                    @csrf @method('DELETE')
                    <button type="button"
                            class="ps-btn danger"
                            data-ii-confirm-form="ps-form-delete-{{ $paiement->id }}"
                            data-ii-confirm-title="Supprimer le paiement"
                            data-ii-confirm-message="Supprimer définitivement ce paiement ? Cette action est irréversible."
                            data-ii-confirm-label="Supprimer"
                            data-ii-confirm-danger="1">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                @endcan

                @if($paiement->status === 'en_attente')
                @can('paiements.validate')
                <form id="ps-form-valider-{{ $paiement->id }}" action="{{ route('esbtp.paiements.valider', $paiement->id) }}" method="POST" style="margin:0">
                    @csrf
                    <button type="button"
                            class="ps-btn success"
                            data-ii-confirm-form="ps-form-valider-{{ $paiement->id }}"
                            data-ii-confirm-title="Valider le paiement"
                            data-ii-confirm-message="Valider ce paiement ? Le montant sera comptabilisé immédiatement."
                            data-ii-confirm-label="Valider le paiement">
                        <i class="fas fa-check"></i> Valider
                    </button>
                </form>
                <button type="button" class="ps-btn danger" data-bs-toggle="modal" data-bs-target="#modalRejeter">
                    <i class="fas fa-times"></i> Rejeter
                </button>
                @endcan
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ═══ CONTENT ═══ --}}
<div class="ps-content">

    {{-- Alerte en attente --}}
    @if($paiement->status === 'en_attente')
    <div class="ps-pending-alert" style="margin-bottom:20px;">
        <div class="ps-pending-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="ps-pending-text">
            <strong>Ce paiement est en attente de validation</strong>
            <p>Il sera comptabilisé une fois validé par un administrateur.</p>
        </div>
    </div>
    @endif

    <div class="row g-4">
        {{-- ═══ Informations du paiement ═══ --}}
        <div class="col-lg-6">
            <div class="ps-card">
                <div class="ps-card-header">
                    <div class="ps-card-icon"><i class="fas fa-receipt"></i></div>
                    <div class="ps-card-title">Informations du paiement</div>
                </div>
                <div class="ps-card-body">
                    <div class="ps-info">
                        <span class="ps-info-lbl">Numéro de reçu</span>
                        <span class="ps-info-val"><code style="background:rgba(4,83,203,.06); padding:3px 10px; border-radius:6px; color:var(--k-blue); font-weight:700;">{{ $paiement->numero_recu }}</code></span>
                    </div>
                    <div class="ps-info">
                        <span class="ps-info-lbl">Catégorie de frais</span>
                        <span class="ps-info-val">
                            @if($paiement->fraisCategory)
                                <span class="ps-badge blue"><i class="fas fa-tag"></i> {{ $paiement->fraisCategory->name }}</span>
                            @else
                                <span class="ps-badge" style="background:#f1f5f9; color:var(--k-muted);">Non définie</span>
                            @endif
                        </span>
                    </div>
                    <div class="ps-info">
                        <span class="ps-info-lbl">Mode de paiement</span>
                        <span class="ps-info-val">
                            <span class="ps-badge info">
                                <i class="fas fa-{{ match($paiement->mode_paiement) { 'especes' => 'money-bill-wave', 'cheque' => 'money-check', 'virement' => 'exchange-alt', 'mobile_money' => 'mobile-alt', default => 'credit-card' } }}"></i>
                                {{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement)) }}
                            </span>
                        </span>
                    </div>
                    <div class="ps-info">
                        <span class="ps-info-lbl">Référence</span>
                        <span class="ps-info-val">
                            @if($paiement->reference_paiement)
                                <code style="background:#f8fafc; padding:3px 10px; border-radius:6px; font-size:.82rem;">{{ $paiement->reference_paiement }}</code>
                            @else
                                <span style="color:var(--k-muted); font-style:italic;">—</span>
                            @endif
                        </span>
                    </div>
                    @if($paiement->motif)
                    <div class="ps-info">
                        <span class="ps-info-lbl">Motif</span>
                        <span class="ps-info-val">{{ $paiement->motif }}</span>
                    </div>
                    @endif
                    {{-- Lot 13 — Encaisseur (créateur du paiement) --}}
                    <div class="ps-info">
                        <span class="ps-info-lbl">Encaissé par</span>
                        <span class="ps-info-val">
                            @if($paiement->creator)
                                <span class="ps-badge blue"><i class="fas fa-user-circle"></i> {{ $paiement->creator->name }}</span>
                            @else
                                <span style="color:var(--k-muted); font-style:italic;">—</span>
                            @endif
                        </span>
                    </div>
                    @if($paiement->status == 'validé' && $paiement->date_validation)
                    <div class="ps-info">
                        <span class="ps-info-lbl">Validé le</span>
                        <span class="ps-info-val">{{ $paiement->date_validation->format('d/m/Y à H:i') }}</span>
                    </div>
                    <div class="ps-info">
                        <span class="ps-info-lbl">Validé par</span>
                        <span class="ps-info-val">
                            <span class="ps-badge green"><i class="fas fa-user-shield"></i> {{ $paiement->validatedBy?->name ?? '—' }}</span>
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══ Informations de l'étudiant ═══ --}}
        <div class="col-lg-6">
            <div class="ps-card">
                <div class="ps-card-header">
                    <div class="ps-card-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="ps-card-title">Étudiant</div>
                </div>
                <div class="ps-card-body">
                    <div class="ps-student">
                        <div class="ps-student-avatar">
                            @if($paiement->etudiant->photo)
                                <img src="{{ asset('storage/photos/etudiants/' . $paiement->etudiant->photo) }}" alt=""
                                     onerror="this.parentElement.innerHTML='{{ strtoupper(substr($paiement->etudiant->prenoms ?? 'E', 0, 1)) }}{{ strtoupper(substr($paiement->etudiant->nom, 0, 1)) }}'">
                            @else
                                {{ strtoupper(substr($paiement->etudiant->prenoms ?? 'E', 0, 1)) }}{{ strtoupper(substr($paiement->etudiant->nom, 0, 1)) }}
                            @endif
                        </div>
                        <div>
                            <div class="ps-student-name">{{ $paiement->etudiant->nom_complet ?? ($paiement->etudiant->user->name ?? '—') }}</div>
                            <div class="ps-student-mat"><i class="fas fa-id-badge me-1"></i>{{ $paiement->etudiant->matricule ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="ps-info">
                        <span class="ps-info-lbl">Email personnel</span>
                        <span class="ps-info-val">
                            @if($paiement->etudiant->email_personnel)
                                <a href="mailto:{{ $paiement->etudiant->email_personnel }}" style="color:var(--k-blue); text-decoration:none;">{{ $paiement->etudiant->email_personnel }}</a>
                            @else <span style="color:var(--k-muted); font-style:italic;">—</span> @endif
                        </span>
                    </div>
                    <div class="ps-info">
                        <span class="ps-info-lbl">Filière</span>
                        <span class="ps-info-val">
                            <span class="ps-badge blue">{{ $paiement->inscription->filiere->name ?? '—' }}</span>
                        </span>
                    </div>
                    <div class="ps-info">
                        <span class="ps-info-lbl">Niveau</span>
                        <span class="ps-info-val">{{ $paiement->inscription->niveauEtude->name ?? '—' }}</span>
                    </div>
                    <div class="ps-info">
                        <span class="ps-info-lbl">Année universitaire</span>
                        <span class="ps-info-val">
                            @if($paiement->inscription->anneeUniversitaire)
                                <span class="ps-badge amber"><i class="fas fa-calendar-alt"></i> {{ $paiement->inscription->anneeUniversitaire->libelle ?: ($paiement->inscription->anneeUniversitaire->annee_debut . '-' . $paiement->inscription->anneeUniversitaire->annee_fin) }}</span>
                            @else <span style="color:var(--k-muted);">—</span> @endif
                        </span>
                    </div>

                    <div class="ps-action-links">
                        <a href="{{ route('esbtp.etudiants.show', $paiement->etudiant_id) }}" class="ps-link">
                            <i class="fas fa-user"></i> Voir le profil
                        </a>
                        <a href="{{ route('esbtp.inscriptions.show', $paiement->inscription_id) }}" class="ps-link">
                            <i class="fas fa-eye"></i> Voir l'inscription
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Commentaires & Historique ═══ --}}
    <div class="ps-card" style="margin-top:20px;">
        <div class="ps-card-header">
            <div class="ps-card-icon"><i class="fas fa-history"></i></div>
            <div class="ps-card-title">Historique & commentaires</div>
        </div>
        <div class="ps-card-body">
            @if($paiement->commentaire)
            <div class="ps-comment">
                <div class="ps-comment-label"><i class="fas fa-comment-alt me-1"></i> Commentaire</div>
                <div class="ps-comment-text">{{ $paiement->commentaire }}</div>
            </div>
            @endif

            <div class="ps-timeline">
                <div class="ps-tl-item">
                    <div class="ps-tl-dot created"><i class="fas fa-plus"></i></div>
                    <div class="ps-tl-text">
                        Paiement créé
                        @if($paiement->creator) par <strong>{{ $paiement->creator->name }}</strong>@endif
                        <br><span class="ps-tl-date"><i class="fas fa-clock me-1"></i>{{ $paiement->created_at->format('d/m/Y à H:i') }}</span>
                    </div>
                </div>

                @if($paiement->updated_at->gt($paiement->created_at))
                <div class="ps-tl-item">
                    <div class="ps-tl-dot updated"><i class="fas fa-edit"></i></div>
                    <div class="ps-tl-text">
                        Dernière modification
                        @if($paiement->updatedBy) par <strong>{{ $paiement->updatedBy->name }}</strong>@endif
                        <br><span class="ps-tl-date"><i class="fas fa-clock me-1"></i>{{ $paiement->updated_at->format('d/m/Y à H:i') }}</span>
                    </div>
                </div>
                @endif

                @if($paiement->status == 'validé' && $paiement->date_validation)
                <div class="ps-tl-item">
                    <div class="ps-tl-dot validated"><i class="fas fa-check"></i></div>
                    <div class="ps-tl-text">
                        Paiement validé
                        @if($paiement->validatedBy) par <strong>{{ $paiement->validatedBy->name }}</strong>@endif
                        <br><span class="ps-tl-date"><i class="fas fa-clock me-1"></i>{{ $paiement->date_validation->format('d/m/Y à H:i') }}</span>
                    </div>
                </div>
                @endif

                @if($paiement->status == 'rejeté' && $paiement->date_validation)
                <div class="ps-tl-item">
                    <div class="ps-tl-dot rejected" style="background:rgba(220,38,38,.12);color:#dc2626;"><i class="fas fa-times"></i></div>
                    <div class="ps-tl-text">
                        <strong style="color:#dc2626;">Paiement rejeté</strong>
                        @if($paiement->validatedBy) par <strong>{{ $paiement->validatedBy->name }}</strong>@endif
                        <br><span class="ps-tl-date"><i class="fas fa-clock me-1"></i>{{ $paiement->date_validation->format('d/m/Y à H:i') }}</span>
                        @if($paiement->commentaire)
                        <div class="ps-tl-reason" style="margin-top:8px;padding:10px 12px;background:#fef2f2;border-left:3px solid #dc2626;border-radius:6px;font-size:.85rem;color:#7f1d1d;line-height:1.5;">
                            <strong style="color:#dc2626;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:4px;">
                                <i class="fas fa-quote-left me-1" style="font-size:.65rem;"></i> Motif du rejet
                            </strong>
                            {{ $paiement->commentaire }}
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>
</div>

{{-- ═══ MODAL REJETER ═══ --}}
@if($paiement->status === 'en_attente')
<div class="modal fade" id="modalRejeter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow:0 10px 40px rgba(0,0,0,.2);">
            <div class="modal-header" style="background:linear-gradient(135deg, #ef4444, #dc2626); color:#fff; border-radius:15px 15px 0 0; padding:1.25rem 1.5rem; border:none;">
                <h5 class="modal-title fw-bold"><i class="fas fa-times-circle me-2"></i>Rejeter le paiement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('esbtp.paiements.rejeter', $paiement->id) }}" method="POST">
                @csrf
                <div class="modal-body" style="padding:1.5rem;">
                    <div style="background:#fef2f2; border:1.5px solid #fca5a5; border-radius:10px; padding:12px 16px; margin-bottom:16px;">
                        <div style="display:flex; gap:10px; align-items:flex-start;">
                            <i class="fas fa-exclamation-triangle" style="color:#ef4444; margin-top:2px;"></i>
                            <div>
                                <strong style="color:#991b1b;">Attention :</strong>
                                <span style="color:#991b1b; font-size:.88rem;"> Cette action est irréversible.</span>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; gap:16px; margin-bottom:16px; font-size:.88rem;">
                        <div><strong>Montant :</strong> {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</div>
                        <div><strong>Réf :</strong> {{ $paiement->numero_recu }}</div>
                    </div>
                    <div x-data="{ count: 0 }">
                        <label for="motif_rejet" class="form-label fw-semibold" style="font-size:.88rem;">
                            Motif du rejet <span class="text-danger">*</span>
                            <span style="font-weight:400; color:#64748b; font-size:.78rem;">(min. 10 caractères)</span>
                        </label>
                        <textarea name="motif_rejet" id="motif_rejet" rows="4" class="form-control" required
                                  minlength="10" maxlength="500"
                                  placeholder="Ex : Montant incorrect, doit être 50&nbsp;000 FCFA au lieu de 5&nbsp;000."
                                  x-on:input="count = $event.target.value.length"
                                  style="border:2px solid #dee2e6; border-radius:10px; resize:none;"></textarea>
                        <div style="display:flex; justify-content:space-between; margin-top:6px; font-size:.74rem; color:#94a3b8;">
                            <span>Le caissier qui a saisi ce paiement verra ce motif pour corriger sa saisie.</span>
                            <span x-text="count + ' / 500'" :style="count < 10 ? 'color:#dc2626;font-weight:600' : (count > 480 ? 'color:#d97706;font-weight:600' : '')">0 / 500</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f8f9fa; border-radius:0 0 15px 15px; padding:1rem 1.5rem; border:none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px; font-weight:600;">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-danger" style="border-radius:8px; font-weight:600;">
                        <i class="fas fa-times me-1"></i>Confirmer le rejet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Historique d'audit (production audit log) --}}
<div class="ps-content" style="margin-top: 1.5rem;">
    <x-entity-history :model="$paiement" :limit="10" />
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/inscriptions/common.js') }}"></script>
<script>
// Copy receipt number on click
document.querySelectorAll('.ps-receipt').forEach(el => {
    el.addEventListener('click', function() {
        const hint = this.querySelector('.ps-copy-hint');
        if (hint) { hint.textContent = 'Copié !'; setTimeout(() => hint.textContent = 'Copier', 2000); }
    });
});

// Intercept [data-ii-confirm-form] buttons — show iiConfirm modal, then submit
// the referenced form programmatically. Replaces native confirm() dialogs
// which don't always render in modern Chrome and lack KLASSCI styling.
document.querySelectorAll('[data-ii-confirm-form]').forEach(btn => {
    btn.addEventListener('click', async function (e) {
        e.preventDefault();
        const formId = this.getAttribute('data-ii-confirm-form');
        const form = document.getElementById(formId);
        if (!form || typeof window.iiConfirm !== 'function') {
            return;
        }

        const confirmed = await window.iiConfirm({
            title: this.getAttribute('data-ii-confirm-title') || 'Confirmer',
            message: this.getAttribute('data-ii-confirm-message') || 'Voulez-vous continuer ?',
            confirmLabel: this.getAttribute('data-ii-confirm-label') || 'Confirmer',
            cancelLabel: this.getAttribute('data-ii-confirm-cancel') || 'Annuler',
            danger: this.getAttribute('data-ii-confirm-danger') === '1',
        });

        if (confirmed) {
            form.submit();
        }
    });
});
</script>
@endpush
