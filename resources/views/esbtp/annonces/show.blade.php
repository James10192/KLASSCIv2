@extends('layouts.app')

@section('title', 'Annonce : ' . $annonce->titre . ' — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">

<style>
/* ============================================================
   /esbtp/annonces/{id} — Namespace aps-* (Annonce Premium Show)
   Design system KLASSCI : monochrome bleu, hero gradient,
   cards 14px, ombres multicouches, mobile-first.
   ============================================================ */

/* ----- Layout 2 colonnes (main + aside sticky) ----- */
.aps-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 1.25rem;
    align-items: start;
}
.aps-main { display: grid; gap: 1.25rem; min-width: 0; }
.aps-aside {
    display: grid; gap: 1rem;
    position: sticky; top: 92px;
    align-self: start;
}
@media (max-width: 1199.98px) {
    .aps-grid { grid-template-columns: 1fr; }
    .aps-aside { position: static; top: auto; }
}

/* ----- Hero gradient KLASSCI ----- */
.aps-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 1.75rem 2rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(4,83,203,.18), 0 2px 8px rgba(15,23,42,.08);
}
.aps-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(circle at 85% 15%, rgba(255,255,255,.12) 0%, transparent 60%),
                      radial-gradient(circle at 10% 90%, rgba(255,255,255,.06) 0%, transparent 50%);
    pointer-events: none;
}
.aps-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 1.25rem; flex-wrap: wrap;
    position: relative;
}
.aps-hero-left {
    display: flex; align-items: flex-start; gap: 1rem;
    min-width: 0; flex: 1;
}
.aps-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.aps-hero-title-wrap { min-width: 0; flex: 1; }
.aps-hero h1 {
    font-size: 1.4rem; font-weight: 700; color: #fff; margin: 0;
    line-height: 1.25;
    word-wrap: break-word;
}
.aps-hero-meta {
    display: flex; align-items: center; gap: .5rem;
    flex-wrap: wrap;
    margin-top: .65rem;
}
.aps-chip {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .65rem;
    border-radius: 999px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.20);
    color: #fff;
    font-size: .73rem; font-weight: 600;
}
.aps-chip i { font-size: .72rem; }
.aps-chip--draft {
    background: rgba(255,255,255,.20);
    border-color: rgba(255,255,255,.30);
}
.aps-chip--published {
    background: rgba(16,185,129,.25);
    border-color: rgba(110,231,183,.40);
}
.aps-chip--expired {
    background: rgba(248,113,113,.25);
    border-color: rgba(252,165,165,.40);
}
.aps-chip--urgent {
    background: rgba(248,113,113,.30);
    border-color: rgba(252,165,165,.50);
    animation: apsPulse 2.4s ease-in-out infinite;
}
.aps-chip--important {
    background: rgba(251,191,36,.28);
    border-color: rgba(253,224,71,.45);
}
@keyframes apsPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(248,113,113,.30); }
    50%      { box-shadow: 0 0 0 6px rgba(248,113,113,0); }
}

.aps-hero-actions {
    display: flex; align-items: center; gap: .5rem;
    flex-shrink: 0;
}
.aps-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
    padding: .55rem 1rem;
    border-radius: 10px;
    font-size: .82rem; font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    transition: transform .12s ease, box-shadow .15s, background .15s, border-color .15s;
    line-height: 1.2;
    text-decoration: none;
}
.aps-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(255,255,255,.45);
}
.aps-btn--glass {
    background: rgba(255,255,255,.15);
    color: #fff;
    border-color: rgba(255,255,255,.22);
}
.aps-btn--glass:hover {
    background: rgba(255,255,255,.25);
    color: #fff;
}
.aps-btn--white {
    background: #fff;
    color: #0453cb;
    border-color: transparent;
}
.aps-btn--white:hover {
    background: #f8fafc;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(0,0,0,.18);
    color: #0453cb;
}
.aps-btn--danger-glass {
    background: rgba(248,113,113,.22);
    color: #fff;
    border-color: rgba(252,165,165,.35);
}
.aps-btn--danger-glass:hover {
    background: rgba(248,113,113,.32);
    color: #fff;
}
.aps-btn--disabled {
    background: rgba(255,255,255,.08);
    color: rgba(255,255,255,.55);
    border-color: rgba(255,255,255,.12);
    cursor: not-allowed;
}

/* ----- Cards (sous le hero) ----- */
.aps-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    overflow: hidden;
    transition: box-shadow .2s ease, border-color .2s ease;
}
.aps-card:hover { border-color: #cbd5e1; }
.aps-card-head {
    display: flex; align-items: center; gap: .75rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}
.aps-card-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: inline-flex; align-items: center; justify-content: center;
    color: #fff; font-size: .9rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
}
.aps-card-title { font-size: .95rem; font-weight: 600; color: #0f172a; line-height: 1.2; }
.aps-card-sub { font-size: .76rem; color: #64748b; margin-top: 2px; }
.aps-card-body { padding: 1.25rem; }

/* ----- Message body ----- */
.aps-message {
    font-size: .92rem;
    line-height: 1.65;
    color: #1e293b;
    word-wrap: break-word;
    white-space: pre-wrap;
}

/* ----- Pièce jointe row ----- */
.aps-attach {
    display: flex; align-items: center; gap: .85rem;
    padding: .85rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    transition: border-color .15s, background .15s, transform .15s;
    text-decoration: none;
}
.aps-attach:hover {
    border-color: #0453cb;
    background: #eff6ff;
    transform: translateY(-1px);
    text-decoration: none;
}
.aps-attach-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(4,83,203,.20);
}
.aps-attach-meta { flex: 1; min-width: 0; }
.aps-attach-name {
    font-size: .88rem; font-weight: 600; color: #0f172a;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.aps-attach-sub {
    font-size: .72rem; color: #64748b;
    margin-top: 2px;
}
.aps-attach-cta {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .35rem .75rem;
    border-radius: 8px;
    background: #fff;
    border: 1px solid #cbd5e1;
    color: #0453cb;
    font-size: .76rem; font-weight: 600;
    flex-shrink: 0;
}

/* ----- Recipients ----- */
.aps-chips-wrap {
    display: flex; flex-wrap: wrap; gap: .4rem;
}
.aps-recipient-chip {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .35rem .75rem;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e40af;
    border-radius: 999px;
    font-size: .78rem; font-weight: 600;
}
.aps-recipient-chip i { font-size: .72rem; }

.aps-recipient-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: .85rem;
}
.aps-recipient-table thead th {
    text-align: left;
    padding: .65rem .85rem;
    background: #f8fafc;
    color: #475569;
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    border-bottom: 1px solid #e2e8f0;
}
.aps-recipient-table thead th:first-child { border-top-left-radius: 10px; }
.aps-recipient-table thead th:last-child { border-top-right-radius: 10px; }
.aps-recipient-table tbody td {
    padding: .7rem .85rem;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    vertical-align: middle;
}
.aps-recipient-table tbody tr:last-child td { border-bottom: none; }
.aps-recipient-table tbody tr:hover { background: #f8fafc; }
.aps-recipient-table .aps-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-weight: 600;
    color: #0f172a;
    font-size: .82rem;
}

.aps-read-pill {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .2rem .55rem;
    border-radius: 999px;
    font-size: .72rem; font-weight: 600;
    border: 1px solid transparent;
}
.aps-read-pill--read {
    background: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}
.aps-read-pill--unread {
    background: #f8fafc;
    color: #64748b;
    border-color: #e2e8f0;
}

.aps-empty {
    text-align: center;
    padding: 1.25rem 1rem;
    font-size: .82rem;
    color: #64748b;
    font-style: italic;
}

/* ----- Sidebar — meta KV ----- */
.aps-kv {
    display: grid;
    grid-template-columns: 1fr;
    gap: .75rem;
}
.aps-kv-row {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: .75rem;
    padding-bottom: .75rem;
    border-bottom: 1px dashed #e2e8f0;
}
.aps-kv-row:last-child { border-bottom: none; padding-bottom: 0; }
.aps-kv-label {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .73rem; color: #64748b; font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.aps-kv-label i { color: #0453cb; }
.aps-kv-value {
    font-size: .85rem; color: #0f172a; font-weight: 600;
    text-align: right;
    word-break: break-word;
    max-width: 60%;
}
.aps-kv-value--muted { color: #64748b; font-weight: 500; }

/* ----- Stats card (lecture) ----- */
.aps-stats {
    display: grid; grid-template-columns: 1fr 1fr; gap: .65rem;
}
.aps-stat {
    text-align: center;
    padding: .75rem .5rem;
    background: linear-gradient(180deg, #f8fafc, #ffffff);
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}
.aps-stat-value {
    font-size: 1.4rem; font-weight: 700; color: #0453cb; line-height: 1.1;
}
.aps-stat-label {
    font-size: .68rem; color: #64748b; margin-top: 4px;
    text-transform: uppercase; letter-spacing: .04em; font-weight: 600;
}
.aps-progress-track {
    height: 8px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    margin-top: .85rem;
}
.aps-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #0453cb, #3b7ddb);
    border-radius: 999px;
    transition: width .35s ease;
}
.aps-progress-label {
    font-size: .72rem; color: #64748b;
    margin-top: .35rem;
    text-align: center;
}

/* ----- Modal delete ----- */
.aps-modal .modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 24px 60px rgba(15,23,42,.20);
    overflow: hidden;
}
.aps-modal .modal-header {
    background: linear-gradient(135deg, #7f1d1d, #b91c1c 60%, #dc2626);
    border-bottom: none;
    padding: 1.1rem 1.25rem;
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 1rem;
    color: #fff;
}
.aps-modal-title {
    display: inline-flex; align-items: center; gap: .55rem;
    font-size: 1rem; font-weight: 700; color: #fff;
}
.aps-modal-sub { font-size: .75rem; color: rgba(255,255,255,.75); display:block; margin-top:2px; }
.aps-modal-close {
    background: rgba(255,255,255,.15);
    border: none; color: #fff;
    width: 30px; height: 30px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .15s;
}
.aps-modal-close:hover { background: rgba(255,255,255,.30); }
.aps-modal .modal-body { padding: 1.25rem; }
.aps-modal .modal-footer {
    border-top: 1px solid #f1f5f9;
    padding: .85rem 1.25rem;
    background: #fafbfc;
    justify-content: flex-end;
    gap: .5rem;
}

.aps-btn--ghost {
    background: transparent; color: #64748b; border-color: transparent;
    padding: .55rem 1rem; border-radius: 10px;
    font-size: .82rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: .45rem;
    cursor: pointer;
}
.aps-btn--ghost:hover { background: #f1f5f9; color: #0f172a; }

.aps-btn--danger {
    background: linear-gradient(135deg, #b91c1c, #dc2626);
    color: #fff; border-color: transparent;
    padding: .55rem 1rem; border-radius: 10px;
    font-size: .82rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: .45rem;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(220,38,38,.25);
}
.aps-btn--danger:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(220,38,38,.32);
    color: #fff;
}

/* ----- Mobile polish ----- */
@media (max-width: 768px) {
    .aps-hero { padding: 1.4rem 1.25rem 1.25rem; }
    .aps-hero h1 { font-size: 1.2rem; }
    .aps-hero-actions { width: 100%; justify-content: stretch; }
    .aps-hero-actions .aps-btn { flex: 1; }
    .aps-card-body { padding: 1rem; }
    .aps-stats { grid-template-columns: 1fr 1fr; }
}
</style>
@endsection

@php
    // Helpers d'état
    $isPublished = (bool) $annonce->is_published;
    $isExpired   = $annonce->isExpired();
    $priorite    = (int) $annonce->priorite;

    $typeLabels = [
        'general'  => ['label' => 'Tous les étudiants', 'icon' => 'fa-globe'],
        'classe'   => ['label' => 'Classes ciblées',     'icon' => 'fa-chalkboard'],
        'etudiant' => ['label' => 'Étudiants nominatifs','icon' => 'fa-user-graduate'],
    ];
    $typeData = $typeLabels[$annonce->type] ?? ['label' => ucfirst($annonce->type ?? '—'), 'icon' => 'fa-bullhorn'];

    // Règle 15 minutes (cf. controller canEditAnnonce)
    $publishedAt = $annonce->created_at;
    if ($annonce->date_publication && $annonce->created_at && $annonce->date_publication > $annonce->created_at) {
        $publishedAt = $annonce->date_publication;
    }
    $minutesSincePublish = $publishedAt ? $publishedAt->diffInMinutes(now()) : 0;
    $canEdit = !$isExpired && (!$isPublished || $minutesSincePublish <= 15);

    // Stats lecture (étudiants nominatifs uniquement — on a le pivot is_read)
    $totalRecipients = 0;
    $readCount = 0;
    if ($annonce->type === 'etudiant') {
        $totalRecipients = $annonce->etudiants->count();
        $readCount = $annonce->etudiants->filter(fn ($e) => (bool) ($e->pivot->is_read ?? false))->count();
    }
    $readRate = $totalRecipients > 0 ? round(($readCount / $totalRecipients) * 100) : 0;

    $piece = $annonce->piece_jointe ?? null;
    $pieceName = $piece ? basename($piece) : null;
@endphp

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        @if(session('success'))
            <div class="alert-modern success">
                <i class="fas fa-check-circle"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="alert-modern error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        {{-- =================== HERO PREMIUM =================== --}}
        <div class="aps-hero">
            <div class="aps-hero-top">
                <div class="aps-hero-left">
                    <div class="aps-hero-icon"><i class="fas fa-bullhorn"></i></div>
                    <div class="aps-hero-title-wrap">
                        <h1>{{ $annonce->titre }}</h1>
                        <div class="aps-hero-meta">
                            {{-- Statut publication --}}
                            @if($isExpired)
                                <span class="aps-chip aps-chip--expired">
                                    <i class="fas fa-hourglass-end"></i>Expirée
                                </span>
                            @elseif($isPublished)
                                <span class="aps-chip aps-chip--published">
                                    <i class="fas fa-check-circle"></i>Publiée
                                </span>
                            @else
                                <span class="aps-chip aps-chip--draft">
                                    <i class="fas fa-file-lines"></i>Brouillon
                                </span>
                            @endif

                            {{-- Type d'audience --}}
                            <span class="aps-chip">
                                <i class="fas {{ $typeData['icon'] }}"></i>{{ $typeData['label'] }}
                            </span>

                            {{-- Priorité --}}
                            @if($priorite === 2)
                                <span class="aps-chip aps-chip--urgent">
                                    <i class="fas fa-bolt"></i>Urgente
                                </span>
                            @elseif($priorite === 1)
                                <span class="aps-chip aps-chip--important">
                                    <i class="fas fa-thumbtack"></i>Importante
                                </span>
                            @else
                                <span class="aps-chip">
                                    <i class="fas fa-flag"></i>Priorité normale
                                </span>
                            @endif

                            {{-- Date d'expiration courte --}}
                            @if($annonce->date_expiration)
                                <span class="aps-chip">
                                    <i class="fas fa-calendar-day"></i>
                                    Expire le {{ $annonce->date_expiration->format('d/m/Y') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="aps-hero-actions">
                    <a href="{{ route('esbtp.annonces.index') }}" class="aps-btn aps-btn--glass">
                        <i class="fas fa-arrow-left"></i>Retour
                    </a>
                    @if($canEdit)
                        <a href="{{ route('esbtp.annonces.edit', $annonce) }}" class="aps-btn aps-btn--white">
                            <i class="fas fa-pen-to-square"></i>Modifier
                        </a>
                    @else
                        <span class="aps-btn aps-btn--disabled"
                              title="@if($isExpired)Annonce expirée @else Modifications limitées à 15 min après publication @endif">
                            <i class="fas fa-lock"></i>Modification verrouillée
                        </span>
                    @endif
                    <button type="button" class="aps-btn aps-btn--danger-glass" id="aps-open-delete">
                        <i class="fas fa-trash"></i>Supprimer
                    </button>
                </div>
            </div>
        </div>

        {{-- =================== GRID 2 COLONNES =================== --}}
        <div class="aps-grid">

            {{-- COLONNE PRINCIPALE --}}
            <div class="aps-main">

                {{-- ===== Carte 1 : Message ===== --}}
                <div class="aps-card">
                    <div class="aps-card-head">
                        <span class="aps-card-icon"><i class="fas fa-pen-nib"></i></span>
                        <div>
                            <div class="aps-card-title">Message</div>
                            <div class="aps-card-sub">Contenu de l'annonce diffusée</div>
                        </div>
                    </div>
                    <div class="aps-card-body">
                        <div class="aps-message">{!! nl2br(e($annonce->contenu)) !!}</div>
                    </div>
                </div>

                {{-- ===== Carte 2 : Pièce jointe ===== --}}
                @if($piece)
                    <div class="aps-card">
                        <div class="aps-card-head">
                            <span class="aps-card-icon"><i class="fas fa-paperclip"></i></span>
                            <div>
                                <div class="aps-card-title">Pièce jointe</div>
                                <div class="aps-card-sub">Document attaché à cette annonce</div>
                            </div>
                        </div>
                        <div class="aps-card-body">
                            <a href="{{ \Storage::disk('public')->url($piece) }}"
                               target="_blank" rel="noopener"
                               class="aps-attach">
                                <div class="aps-attach-icon"><i class="fas fa-file-arrow-down"></i></div>
                                <div class="aps-attach-meta">
                                    <div class="aps-attach-name" title="{{ $pieceName }}">{{ $pieceName }}</div>
                                    <div class="aps-attach-sub">Cliquez pour ouvrir dans un nouvel onglet</div>
                                </div>
                                <span class="aps-attach-cta">
                                    <i class="fas fa-external-link-alt"></i>Ouvrir
                                </span>
                            </a>
                        </div>
                    </div>
                @endif

                {{-- ===== Carte 3 : Destinataires (classes ou étudiants) ===== --}}
                @if($annonce->type === 'classe')
                    <div class="aps-card">
                        <div class="aps-card-head">
                            <span class="aps-card-icon"><i class="fas fa-chalkboard"></i></span>
                            <div>
                                <div class="aps-card-title">Classes destinataires</div>
                                <div class="aps-card-sub">{{ $annonce->classes->count() }} classe(s) ciblée(s)</div>
                            </div>
                        </div>
                        <div class="aps-card-body">
                            @if($annonce->classes->count() > 0)
                                <div class="aps-chips-wrap">
                                    @foreach($annonce->classes as $classe)
                                        <span class="aps-recipient-chip">
                                            <i class="fas fa-chalkboard-user"></i>{{ $classe->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <div class="aps-empty">
                                    <i class="fas fa-info-circle me-1"></i>Aucune classe attachée à cette annonce.
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif($annonce->type === 'etudiant')
                    <div class="aps-card">
                        <div class="aps-card-head">
                            <span class="aps-card-icon"><i class="fas fa-user-graduate"></i></span>
                            <div>
                                <div class="aps-card-title">Étudiants destinataires</div>
                                <div class="aps-card-sub">{{ $annonce->etudiants->count() }} étudiant(s) — suivi de lecture en temps réel</div>
                            </div>
                        </div>
                        <div class="aps-card-body" style="padding:0;">
                            @if($annonce->etudiants->count() > 0)
                                <div style="overflow-x:auto;">
                                    <table class="aps-recipient-table">
                                        <thead>
                                            <tr>
                                                <th>Matricule</th>
                                                <th>Nom complet</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($annonce->etudiants as $etu)
                                                <tr>
                                                    <td><span class="aps-mono">{{ $etu->matricule ?? '—' }}</span></td>
                                                    <td>
                                                        <strong style="color:#0f172a;">{{ $etu->nom }}</strong>
                                                        <span style="color:#475569;">{{ $etu->prenoms }}</span>
                                                    </td>
                                                    <td>
                                                        @if($etu->pivot->is_read)
                                                            <span class="aps-read-pill aps-read-pill--read">
                                                                <i class="fas fa-check-circle"></i>
                                                                Lu @if($etu->pivot->read_at) le {{ \Carbon\Carbon::parse($etu->pivot->read_at)->format('d/m/Y H:i') }} @endif
                                                            </span>
                                                        @else
                                                            <span class="aps-read-pill aps-read-pill--unread">
                                                                <i class="far fa-circle"></i>Non lu
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="aps-empty" style="padding:1.5rem 1rem;">
                                    <i class="fas fa-info-circle me-1"></i>Aucun étudiant attaché à cette annonce.
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

            </div>

            {{-- COLONNE LATÉRALE --}}
            <aside class="aps-aside">

                {{-- ===== Sidebar — Diffusion ===== --}}
                <div class="aps-card">
                    <div class="aps-card-head">
                        <span class="aps-card-icon"><i class="fas fa-broadcast-tower"></i></span>
                        <div>
                            <div class="aps-card-title">Diffusion</div>
                            <div class="aps-card-sub">Aperçu rapide</div>
                        </div>
                    </div>
                    <div class="aps-card-body">
                        <div class="aps-kv">
                            <div class="aps-kv-row">
                                <span class="aps-kv-label"><i class="fas fa-bullseye"></i>Type</span>
                                <span class="aps-kv-value">{{ $typeData['label'] }}</span>
                            </div>
                            @if($annonce->type === 'classe')
                                <div class="aps-kv-row">
                                    <span class="aps-kv-label"><i class="fas fa-layer-group"></i>Classes</span>
                                    <span class="aps-kv-value">{{ $annonce->classes->count() }}</span>
                                </div>
                            @elseif($annonce->type === 'etudiant')
                                <div class="aps-kv-row">
                                    <span class="aps-kv-label"><i class="fas fa-user-check"></i>Étudiants</span>
                                    <span class="aps-kv-value">{{ $annonce->etudiants->count() }}</span>
                                </div>
                            @endif
                            <div class="aps-kv-row">
                                <span class="aps-kv-label"><i class="fas fa-flag"></i>Priorité</span>
                                <span class="aps-kv-value">
                                    @if($priorite === 2) Urgente
                                    @elseif($priorite === 1) Importante
                                    @else Normale
                                    @endif
                                </span>
                            </div>
                            <div class="aps-kv-row">
                                <span class="aps-kv-label"><i class="fas fa-paperclip"></i>Pièce jointe</span>
                                <span class="aps-kv-value @if(!$piece) aps-kv-value--muted @endif">
                                    {{ $piece ? 'Oui' : 'Aucune' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== Sidebar — Stats lecture (étudiants nominatifs uniquement) ===== --}}
                @if($annonce->type === 'etudiant' && $totalRecipients > 0)
                    <div class="aps-card">
                        <div class="aps-card-head">
                            <span class="aps-card-icon"><i class="fas fa-eye"></i></span>
                            <div>
                                <div class="aps-card-title">Suivi de lecture</div>
                                <div class="aps-card-sub">Statistiques en temps réel</div>
                            </div>
                        </div>
                        <div class="aps-card-body">
                            <div class="aps-stats">
                                <div class="aps-stat">
                                    <div class="aps-stat-value">{{ $readCount }}</div>
                                    <div class="aps-stat-label">Lus</div>
                                </div>
                                <div class="aps-stat">
                                    <div class="aps-stat-value">{{ $totalRecipients - $readCount }}</div>
                                    <div class="aps-stat-label">Non lus</div>
                                </div>
                            </div>
                            <div class="aps-progress-track" role="progressbar" aria-valuenow="{{ $readRate }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="aps-progress-bar" style="width: {{ $readRate }}%;"></div>
                            </div>
                            <div class="aps-progress-label">{{ $readRate }}% des destinataires ont lu cette annonce</div>
                        </div>
                    </div>
                @endif

                {{-- ===== Sidebar — Métadonnées ===== --}}
                <div class="aps-card">
                    <div class="aps-card-head">
                        <span class="aps-card-icon"><i class="fas fa-circle-info"></i></span>
                        <div>
                            <div class="aps-card-title">Informations</div>
                            <div class="aps-card-sub">Historique &amp; auteur</div>
                        </div>
                    </div>
                    <div class="aps-card-body">
                        <div class="aps-kv">
                            <div class="aps-kv-row">
                                <span class="aps-kv-label"><i class="fas fa-user"></i>Créée par</span>
                                <span class="aps-kv-value">{{ $annonce->user?->name ?? 'Système' }}</span>
                            </div>
                            <div class="aps-kv-row">
                                <span class="aps-kv-label"><i class="fas fa-calendar-plus"></i>Créée le</span>
                                <span class="aps-kv-value">{{ $annonce->created_at?->format('d/m/Y à H:i') ?? '—' }}</span>
                            </div>
                            @if($annonce->date_publication && $isPublished)
                                <div class="aps-kv-row">
                                    <span class="aps-kv-label"><i class="fas fa-paper-plane"></i>Publiée le</span>
                                    <span class="aps-kv-value">{{ $annonce->date_publication->format('d/m/Y à H:i') }}</span>
                                </div>
                            @endif
                            @if($annonce->date_expiration)
                                <div class="aps-kv-row">
                                    <span class="aps-kv-label"><i class="fas fa-hourglass-end"></i>Expire le</span>
                                    <span class="aps-kv-value @if($isExpired) aps-kv-value--muted @endif">
                                        {{ $annonce->date_expiration->format('d/m/Y à H:i') }}
                                    </span>
                                </div>
                            @endif
                            <div class="aps-kv-row">
                                <span class="aps-kv-label"><i class="fas fa-pen"></i>Modifiée le</span>
                                <span class="aps-kv-value aps-kv-value--muted">{{ $annonce->updated_at?->format('d/m/Y à H:i') ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </aside>
        </div>

        {{-- Form delete (caché, soumis depuis le modal) --}}
        <form action="{{ route('esbtp.annonces.destroy', $annonce) }}" method="POST" id="apsDeleteForm" style="display:none;">
            @csrf
            @method('DELETE')
        </form>

        {{-- =================== MODAL DELETE =================== --}}
        <div class="modal fade aps-modal" id="apsDeleteModal" tabindex="-1"
             aria-labelledby="apsDeleteModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <span class="aps-modal-title" id="apsDeleteModalLabel">
                                <i class="fas fa-triangle-exclamation"></i> Supprimer cette annonce ?
                            </span>
                            <span class="aps-modal-sub">Action irréversible — toutes les relations seront détachées.</span>
                        </div>
                        <button type="button" class="aps-modal-close" data-bs-dismiss="modal" aria-label="Fermer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p style="margin:0 0 .75rem 0; font-size:.88rem; color:#1e293b; line-height:1.5;">
                            Vous êtes sur le point de supprimer définitivement&nbsp;:
                        </p>
                        <div style="padding:.75rem 1rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:1rem;">
                            <div style="font-size:.72rem; color:#64748b; text-transform:uppercase; letter-spacing:.5px; font-weight:600;">Annonce</div>
                            <div style="font-size:.95rem; font-weight:600; color:#0f172a; margin-top:.2rem;">{{ $annonce->titre }}</div>
                        </div>
                        <p style="margin:0; font-size:.8rem; color:#b91c1c; line-height:1.45;">
                            <i class="fas fa-info-circle me-1"></i>
                            Les étudiants ne verront plus cette annonce. Les statistiques de lecture seront perdues.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="aps-btn--ghost" data-bs-dismiss="modal">
                            Annuler
                        </button>
                        <button type="button" class="aps-btn--danger" id="aps-confirm-delete">
                            <i class="fas fa-trash"></i>Supprimer définitivement
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const openBtn = document.getElementById('aps-open-delete');
        const modalEl = document.getElementById('apsDeleteModal');
        const confirmBtn = document.getElementById('aps-confirm-delete');
        const form = document.getElementById('apsDeleteForm');

        openBtn?.addEventListener('click', function () {
            if (modalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });

        confirmBtn?.addEventListener('click', function () {
            if (form) form.submit();
        });
    });
})();
</script>
@endpush
