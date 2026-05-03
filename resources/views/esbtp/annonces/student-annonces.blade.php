@extends('layouts.app')

@section('title', 'Mes Annonces')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ══════════════════════════════════════════════
   Mes Annonces — Premium Redesign
   Prefix: mm- (mes annonces / messaging)
   ══════════════════════════════════════════════ */

.mm-page {
    padding: 1.5rem;
    background: #f8fafc;
    min-height: calc(100vh - 60px);
}

/* ── Hero (planning-header pattern) ── */
.mm-hero {
    position: relative;
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    overflow: hidden;
    animation: mm-fade-down .4s ease-out;
}
@keyframes mm-fade-down {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.mm-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.mm-hero-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 0;
}
.mm-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.mm-hero-info h1 {
    font-size: 1.45rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 .2rem;
    letter-spacing: -.02em;
}
.mm-hero-info p {
    margin: 0;
    color: rgba(255,255,255,.72);
    font-size: .88rem;
}

.mm-hero-actions {
    display: flex;
    gap: .5rem;
    align-items: center;
    flex-wrap: wrap;
}
.mm-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem 1rem;
    min-height: 44px;
    border-radius: 10px;
    font-size: .82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s ease;
    border: 1px solid rgba(255,255,255,.2);
    cursor: pointer;
    background: rgba(255,255,255,.15);
    color: #fff;
}
.mm-btn:hover, .mm-btn:focus-visible {
    background: rgba(255,255,255,.22);
    color: #fff;
    outline: none;
}
.mm-btn:focus-visible {
    box-shadow: 0 0 0 3px rgba(255,255,255,.35);
}
.mm-btn--white {
    background: #fff;
    color: #0453cb;
    border-color: transparent;
}
.mm-btn--white:hover, .mm-btn--white:focus-visible {
    background: #f0f4ff;
    color: #0453cb;
}

/* ── KPIs in hero ── */
.mm-kpis {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .75rem;
    margin-top: 1.5rem;
}
.mm-kpi {
    background: rgba(255,255,255,.10);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex;
    align-items: center;
    gap: .75rem;
    transition: background .2s ease;
    min-width: 0;
}
.mm-kpi:hover {
    background: rgba(255,255,255,.16);
}
.mm-kpi-icon {
    width: 38px; height: 38px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
    flex-shrink: 0;
    background: rgba(255,255,255,.18);
    color: #fff;
}
.mm-kpi--unread .mm-kpi-icon { background: rgba(94,145,222,.32); color: #cfe1ff; }
.mm-kpi--urgent .mm-kpi-icon { background: rgba(220,38,38,.30); color: #fecaca; }
.mm-kpi-text { min-width: 0; }
.mm-kpi-value {
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1;
    color: #fff;
}
.mm-kpi-label {
    font-size: .72rem;
    color: rgba(255,255,255,.65);
    margin-top: .2rem;
    text-transform: uppercase;
    letter-spacing: .3px;
    font-weight: 500;
}

/* ── Filter tabs ── */
.mm-tabs {
    display: flex;
    gap: .35rem;
    align-items: center;
    flex-wrap: wrap;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: .35rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.mm-tab {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .55rem 1rem;
    min-height: 40px;
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 600;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all .2s ease;
}
.mm-tab:hover {
    background: #f1f5f9;
    color: #1e293b;
}
.mm-tab.is-active {
    background: linear-gradient(135deg, #0453cb 0%, #3b7ddb 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
}
.mm-tab.is-active .mm-tab-pill {
    background: rgba(255,255,255,.25);
    color: #fff;
}
.mm-tab:focus-visible {
    outline: 2px solid #0453cb;
    outline-offset: 2px;
}
.mm-tab-pill {
    background: #e2e8f0;
    color: #475569;
    padding: 1px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    min-width: 22px;
    text-align: center;
}

/* ── Messages list ── */
.mm-list {
    display: flex;
    flex-direction: column;
    gap: .85rem;
}

.mm-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
    overflow: hidden;
    transition: all .2s ease;
    position: relative;
    cursor: pointer;
}
.mm-card:hover {
    box-shadow: 0 8px 30px rgba(4,83,203,.10), 0 2px 8px rgba(15,23,42,.04);
    transform: translateY(-2px);
    border-color: #cbd5e1;
}
.mm-card:focus-visible {
    outline: 2px solid #0453cb;
    outline-offset: 2px;
}

.mm-card.is-unread {
    border-left: 3px solid #0453cb;
}
.mm-card.is-urgent {
    border-left: 3px solid #dc2626;
}
.mm-card.is-urgent.is-unread {
    border-left: 3px solid #dc2626;
    background: linear-gradient(135deg, rgba(220,38,38,.025), rgba(220,38,38,.00) 60%);
}

.mm-card-body {
    padding: 1.15rem 1.25rem;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 1rem;
    align-items: flex-start;
}

/* ── Avatar ── */
.mm-avatar {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
    background: linear-gradient(135deg, rgba(4,83,203,.10), rgba(94,145,222,.18));
    color: #0453cb;
    border: 1px solid rgba(4,83,203,.12);
}
.mm-avatar--classe {
    background: linear-gradient(135deg, rgba(16,185,129,.10), rgba(16,185,129,.20));
    color: #047857;
    border-color: rgba(16,185,129,.18);
}
.mm-avatar--etudiant {
    background: linear-gradient(135deg, rgba(245,158,11,.10), rgba(245,158,11,.22));
    color: #b45309;
    border-color: rgba(245,158,11,.18);
}

/* ── Message content ── */
.mm-content { min-width: 0; }
.mm-title-row {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
    margin-bottom: .35rem;
}
.mm-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    line-height: 1.35;
    letter-spacing: -.01em;
}
.mm-unread-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.18);
    flex-shrink: 0;
    animation: mm-pulse 2s ease-in-out infinite;
}
@keyframes mm-pulse {
    0%, 100% { box-shadow: 0 0 0 3px rgba(4,83,203,.18); }
    50%      { box-shadow: 0 0 0 6px rgba(4,83,203,.08); }
}
@media (prefers-reduced-motion: reduce) {
    .mm-unread-dot { animation: none; }
    .mm-hero { animation: none; }
}

.mm-snippet {
    font-size: .88rem;
    color: #64748b;
    line-height: 1.5;
    margin: 0 0 .55rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.mm-meta {
    display: flex;
    align-items: center;
    gap: .55rem;
    flex-wrap: wrap;
    font-size: .78rem;
}

.mm-chip {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .25rem .6rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 600;
    line-height: 1.2;
    border: 1px solid transparent;
}
.mm-chip i { font-size: .68rem; }

.mm-chip--type-general {
    background: rgba(4,83,203,.08);
    color: #0453cb;
    border-color: rgba(4,83,203,.15);
}
.mm-chip--type-classe {
    background: rgba(16,185,129,.10);
    color: #047857;
    border-color: rgba(16,185,129,.18);
}
.mm-chip--type-etudiant {
    background: rgba(245,158,11,.10);
    color: #b45309;
    border-color: rgba(245,158,11,.20);
}
.mm-chip--prio-high {
    background: rgba(220,38,38,.08);
    color: #b91c1c;
    border-color: rgba(220,38,38,.20);
}
.mm-chip--prio-medium {
    background: rgba(245,158,11,.10);
    color: #b45309;
    border-color: rgba(245,158,11,.20);
}
.mm-chip--prio-normal {
    background: rgba(16,185,129,.10);
    color: #047857;
    border-color: rgba(16,185,129,.18);
}
.mm-chip--time {
    background: #f1f5f9;
    color: #64748b;
    border-color: #e2e8f0;
}

.mm-card-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: .5rem;
    flex-shrink: 0;
}
.mm-icon-btn {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    transition: all .2s ease;
}
.mm-icon-btn:hover, .mm-icon-btn:focus-visible {
    background: #f1f5f9;
    color: #0453cb;
    border-color: #cbd5e1;
    outline: none;
}
.mm-icon-btn:focus-visible {
    box-shadow: 0 0 0 3px rgba(4,83,203,.20);
}
.mm-icon-btn--read {
    background: rgba(4,83,203,.06);
    color: #0453cb;
    border-color: rgba(4,83,203,.18);
}
.mm-icon-btn--read:hover {
    background: rgba(4,83,203,.12);
}

/* ── Empty states ── */
.mm-empty {
    text-align: center;
    padding: 3.5rem 1.5rem;
    background: #fff;
    border: 1px dashed #cbd5e1;
    border-radius: 14px;
}
.mm-empty-icon {
    width: 80px; height: 80px;
    border-radius: 22px;
    background: linear-gradient(135deg, rgba(4,83,203,.08), rgba(94,145,222,.15));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    color: #0453cb;
    font-size: 32px;
    border: 1px solid rgba(4,83,203,.12);
}
.mm-empty--up-to-date .mm-empty-icon {
    background: linear-gradient(135deg, rgba(16,185,129,.10), rgba(16,185,129,.18));
    color: #10b981;
    border-color: rgba(16,185,129,.18);
}
.mm-empty h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 .35rem;
}
.mm-empty p {
    color: #64748b;
    font-size: .9rem;
    margin: 0;
    max-width: 420px;
    margin-left: auto;
    margin-right: auto;
}

/* ── Modal premium ── */
.mm-modal .modal-content {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(15,23,42,.20);
}
.mm-modal .modal-header {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%);
    color: #fff;
    border-bottom: none;
    padding: 1.25rem 1.5rem;
    align-items: flex-start;
    gap: .75rem;
}
.mm-modal .modal-header.is-urgent {
    background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 50%, #dc2626 100%);
}
.mm-modal-title-wrap { flex: 1; min-width: 0; }
.mm-modal-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.25);
    padding: .25rem .6rem;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: .55rem;
}
.mm-modal h5.modal-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0;
    color: #fff;
    letter-spacing: -.01em;
    line-height: 1.3;
}
.mm-modal .btn-close {
    filter: invert(1) brightness(2);
    opacity: .85;
}
.mm-modal .btn-close:hover { opacity: 1; }

.mm-modal-body { padding: 1.5rem; background: #fff; }
.mm-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: .75rem;
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}
.mm-info-item {
    display: flex;
    align-items: center;
    gap: .55rem;
    font-size: .82rem;
    color: #475569;
}
.mm-info-item i {
    width: 28px; height: 28px;
    border-radius: 8px;
    background: rgba(4,83,203,.08);
    color: #0453cb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
}
.mm-info-item strong { color: #1e293b; font-weight: 600; }

.mm-modal-content {
    color: #1e293b;
    font-size: .95rem;
    line-height: 1.65;
    padding: 0;
}
.mm-modal-content p { margin-bottom: .85rem; }

.mm-modal-footer {
    border-top: 1px solid #e2e8f0;
    padding: 1rem 1.5rem;
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
    gap: .5rem;
    flex-wrap: wrap;
}
.mm-modal-footer .btn-acasi { min-height: 44px; }

/* ── Pagination ── */
.mm-pagination {
    display: flex;
    justify-content: center;
    margin-top: 1.5rem;
}
.mm-pagination .pagination { margin: 0; }

/* ── Responsive ── */
@media (max-width: 992px) {
    .mm-hero { padding: 1.75rem 1.5rem 1.25rem; }
    .mm-hero-info h1 { font-size: 1.3rem; }
    .mm-kpis { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .mm-page { padding: 1rem; }
    .mm-hero {
        padding: 1.5rem 1.25rem 1.15rem;
        border-radius: 14px;
    }
    .mm-hero-top { flex-direction: column; align-items: flex-start; }
    .mm-hero-actions { width: 100%; }
    .mm-hero-actions .mm-btn { flex: 1; justify-content: center; }
    .mm-card-body {
        grid-template-columns: auto 1fr;
        gap: .85rem;
    }
    .mm-card-actions {
        grid-column: 1 / -1;
        flex-direction: row;
        justify-content: flex-start;
        align-items: center;
        padding-top: .5rem;
        border-top: 1px solid #f1f5f9;
        margin-top: .5rem;
    }
    .mm-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
        -webkit-overflow-scrolling: touch;
    }
    .mm-tab { white-space: nowrap; flex-shrink: 0; }
}
@media (max-width: 576px) {
    .mm-kpis { grid-template-columns: 1fr; }
    .mm-hero-info h1 { font-size: 1.15rem; }
    .mm-title { font-size: .95rem; }
    .mm-modal .modal-header { padding: 1rem 1.15rem; }
    .mm-modal-body { padding: 1.15rem; }
}
</style>
@endpush

@section('content')
<div class="mm-page">

    {{-- Hero premium --}}
    <div class="mm-hero">
        <div class="mm-hero-top">
            <div class="mm-hero-left">
                <div class="mm-hero-icon" aria-hidden="true">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="mm-hero-info">
                    <h1>Mes Annonces</h1>
                    <p>Consultez les annonces et communications de votre établissement.</p>
                </div>
            </div>

            @if($stats['unread'] > 0)
            <div class="mm-hero-actions">
                <button type="button"
                        class="mm-btn mm-btn--white"
                        id="mmMarkAllRead"
                        aria-label="Marquer tous les messages comme lus">
                    <i class="fas fa-check-double" aria-hidden="true"></i>
                    Tout marquer lu
                </button>
            </div>
            @endif
        </div>

        {{-- KPIs --}}
        <div class="mm-kpis" role="group" aria-label="Statistiques messages">
            <div class="mm-kpi mm-kpi--total">
                <div class="mm-kpi-icon" aria-hidden="true"><i class="fas fa-inbox"></i></div>
                <div class="mm-kpi-text">
                    <div class="mm-kpi-value" data-mm-kpi="total">{{ $stats['total'] }}</div>
                    <div class="mm-kpi-label">Total messages</div>
                </div>
            </div>
            <div class="mm-kpi mm-kpi--unread">
                <div class="mm-kpi-icon" aria-hidden="true"><i class="fas fa-envelope"></i></div>
                <div class="mm-kpi-text">
                    <div class="mm-kpi-value" data-mm-kpi="unread">{{ $stats['unread'] }}</div>
                    <div class="mm-kpi-label">Non lus</div>
                </div>
            </div>
            <div class="mm-kpi mm-kpi--urgent">
                <div class="mm-kpi-icon" aria-hidden="true"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="mm-kpi-text">
                    <div class="mm-kpi-value" data-mm-kpi="urgent">{{ $stats['urgent'] }}</div>
                    <div class="mm-kpi-label">Urgents</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs filtres --}}
    <div class="mm-tabs" role="tablist" aria-label="Filtrer les messages">
        <button type="button"
                class="mm-tab is-active"
                data-mm-filter="all"
                role="tab"
                aria-selected="true">
            <i class="fas fa-list" aria-hidden="true"></i>
            Tous
            <span class="mm-tab-pill" data-mm-pill="total">{{ $stats['total'] }}</span>
        </button>
        <button type="button"
                class="mm-tab"
                data-mm-filter="unread"
                role="tab"
                aria-selected="false">
            <i class="fas fa-envelope" aria-hidden="true"></i>
            Non lus
            <span class="mm-tab-pill" data-mm-pill="unread">{{ $stats['unread'] }}</span>
        </button>
        <button type="button"
                class="mm-tab"
                data-mm-filter="urgent"
                role="tab"
                aria-selected="false">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            Urgents
            <span class="mm-tab-pill" data-mm-pill="urgent">{{ $stats['urgent'] }}</span>
        </button>
    </div>

    {{-- Liste des messages --}}
    <div class="mm-list" id="mmList">
        @forelse($messages as $message)
            @php
                $isUrgent = (int) ($message->priorite ?? 0) === 2;
                $isMedium = (int) ($message->priorite ?? 0) === 1;
                $isUnread = !$message->is_read;
                $cardClasses = 'mm-card';
                if ($isUnread) { $cardClasses .= ' is-unread'; }
                if ($isUrgent) { $cardClasses .= ' is-urgent'; }

                $type = $message->type ?? 'general';
                $avatarClass = match($type) {
                    'classe'   => 'mm-avatar--classe',
                    'etudiant' => 'mm-avatar--etudiant',
                    default    => '',
                };
                $avatarIcon = match($type) {
                    'classe'   => 'fa-users',
                    'etudiant' => 'fa-user',
                    default    => 'fa-bullhorn',
                };
                $typeLabel = match($type) {
                    'classe'   => 'Classe',
                    'etudiant' => 'Personnel',
                    default    => 'Général',
                };

                $prioLabel = $isUrgent ? 'Urgent' : ($isMedium ? 'Important' : 'Normal');
                $prioClass = $isUrgent ? 'mm-chip--prio-high' : ($isMedium ? 'mm-chip--prio-medium' : 'mm-chip--prio-normal');
                $prioIcon  = $isUrgent ? 'fa-exclamation-triangle' : ($isMedium ? 'fa-flag' : 'fa-circle-check');

                $relative = $message->created_at ? $message->created_at->locale('fr')->diffForHumans(['short' => true]) : '';
                $absolute = $message->created_at ? $message->created_at->format('d/m/Y à H:i') : '';
            @endphp

            <article class="{{ $cardClasses }}"
                     data-mm-id="{{ $message->id }}"
                     data-mm-state="{{ $isUnread ? 'unread' : 'read' }}"
                     data-mm-priority="{{ $isUrgent ? 'urgent' : ($isMedium ? 'medium' : 'normal') }}"
                     role="button"
                     tabindex="0"
                     aria-label="Message {{ $message->titre }}, {{ $isUnread ? 'non lu' : 'lu' }}, priorité {{ $prioLabel }}"
                     onclick="mmOpenMessage({{ $message->id }})"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();mmOpenMessage({{ $message->id }});}">
                <div class="mm-card-body">
                    <div class="mm-avatar {{ $avatarClass }}" aria-hidden="true">
                        <i class="fas {{ $avatarIcon }}"></i>
                    </div>

                    <div class="mm-content">
                        <div class="mm-title-row">
                            @if($isUnread)
                                <span class="mm-unread-dot" aria-label="Non lu" title="Non lu"></span>
                            @endif
                            <h3 class="mm-title">{{ $message->titre }}</h3>
                        </div>

                        <p class="mm-snippet">
                            {{ Str::limit(strip_tags($message->contenu), 180) }}
                        </p>

                        <div class="mm-meta">
                            <span class="mm-chip mm-chip--type-{{ $type }}">
                                <i class="fas {{ $avatarIcon }}" aria-hidden="true"></i>
                                {{ $typeLabel }}
                            </span>
                            <span class="mm-chip {{ $prioClass }}">
                                <i class="fas {{ $prioIcon }}" aria-hidden="true"></i>
                                {{ $prioLabel }}
                            </span>
                            <span class="mm-chip mm-chip--time" title="{{ $absolute }}">
                                <i class="far fa-clock" aria-hidden="true"></i>
                                {{ $relative }}
                            </span>
                        </div>
                    </div>

                    <div class="mm-card-actions" onclick="event.stopPropagation();">
                        @if($isUnread)
                        <button type="button"
                                class="mm-icon-btn mm-icon-btn--read"
                                data-mm-mark-read="{{ $message->id }}"
                                aria-label="Marquer comme lu"
                                title="Marquer comme lu">
                            <i class="fas fa-check" aria-hidden="true"></i>
                        </button>
                        @endif
                        <button type="button"
                                class="mm-icon-btn"
                                onclick="mmOpenMessage({{ $message->id }})"
                                aria-label="Lire le message"
                                title="Lire">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </article>

            {{-- Modal détail message --}}
            <div class="modal fade mm-modal"
                 id="mmModal{{ $message->id }}"
                 tabindex="-1"
                 aria-labelledby="mmModalLabel{{ $message->id }}"
                 aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header {{ $isUrgent ? 'is-urgent' : '' }}">
                            <div class="mm-modal-title-wrap">
                                <span class="mm-modal-eyebrow">
                                    <i class="fas {{ $avatarIcon }}" aria-hidden="true"></i>
                                    {{ $typeLabel }}
                                    @if($isUrgent) , urgent @elseif($isMedium) , important @endif
                                </span>
                                <h5 class="modal-title" id="mmModalLabel{{ $message->id }}">
                                    {{ $message->titre }}
                                </h5>
                            </div>
                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Fermer"></button>
                        </div>
                        <div class="mm-modal-body modal-body">
                            <div class="mm-info-grid">
                                <div class="mm-info-item">
                                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                    <span><strong>Publié</strong><br>{{ $absolute }}</span>
                                </div>
                                @if($message->date_expiration)
                                <div class="mm-info-item">
                                    <i class="fas fa-hourglass-end" aria-hidden="true"></i>
                                    <span><strong>Expire</strong><br>{{ $message->date_expiration->format('d/m/Y') }}</span>
                                </div>
                                @endif
                                <div class="mm-info-item">
                                    <i class="fas fa-signal" aria-hidden="true"></i>
                                    <span>
                                        <strong>Priorité</strong><br>
                                        <span class="mm-chip {{ $prioClass }}" style="margin-top:.15rem;">
                                            <i class="fas {{ $prioIcon }}" aria-hidden="true"></i>
                                            {{ $prioLabel }}
                                        </span>
                                    </span>
                                </div>
                            </div>

                            <div class="mm-modal-content">
                                {!! nl2br(e($message->contenu)) !!}
                            </div>
                        </div>
                        <div class="mm-modal-footer">
                            @if($isUnread)
                            <button type="button"
                                    class="btn-acasi primary"
                                    data-mm-mark-read="{{ $message->id }}"
                                    data-bs-dismiss="modal">
                                <i class="fas fa-check"></i>
                                Marquer comme lu
                            </button>
                            @endif
                            <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i>
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="mm-empty">
                <div class="mm-empty-icon" aria-hidden="true">
                    <i class="far fa-envelope-open"></i>
                </div>
                <h3>Aucun message pour le moment</h3>
                <p>Les annonces et notifications de l'établissement apparaîtront ici dès leur publication.</p>
            </div>
        @endforelse

        {{-- Empty states pour les filtres (gérés en JS) --}}
        <div class="mm-empty mm-empty--up-to-date" id="mmEmptyUnread" hidden>
            <div class="mm-empty-icon" aria-hidden="true">
                <i class="fas fa-circle-check"></i>
            </div>
            <h3>Vous êtes à jour</h3>
            <p>Tous vos messages ont été lus. Revenez plus tard pour les nouvelles annonces.</p>
        </div>
        <div class="mm-empty" id="mmEmptyUrgent" hidden>
            <div class="mm-empty-icon" aria-hidden="true">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h3>Aucun message urgent</h3>
            <p>Vous n'avez pas de message marqué comme urgent en ce moment.</p>
        </div>
    </div>

    {{-- Pagination --}}
    @if($messages->hasPages())
    <div class="mm-pagination">
        {{ $messages->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const markReadUrlBase = @json(rtrim(route('esbtp.mes-annonces.read', ['id' => '__ID__']), '/'));
    const markAllReadUrl  = @json(route('esbtp.mes-annonces.mark-all-read'));

    function showFeedback(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'info', 3000);
        }
    }

    function getCounts() {
        const cards = document.querySelectorAll('.mm-card[data-mm-id]');
        let total = cards.length;
        let unread = 0;
        let urgent = 0;
        cards.forEach(c => {
            if (c.dataset.mmState === 'unread') unread++;
            if (c.dataset.mmPriority === 'urgent') urgent++;
        });
        return { total, unread, urgent };
    }

    function refreshCounts() {
        const c = getCounts();
        document.querySelectorAll('[data-mm-kpi="total"]').forEach(el => el.textContent = c.total);
        document.querySelectorAll('[data-mm-kpi="unread"]').forEach(el => el.textContent = c.unread);
        document.querySelectorAll('[data-mm-kpi="urgent"]').forEach(el => el.textContent = c.urgent);
        document.querySelectorAll('[data-mm-pill="total"]').forEach(el => el.textContent = c.total);
        document.querySelectorAll('[data-mm-pill="unread"]').forEach(el => el.textContent = c.unread);
        document.querySelectorAll('[data-mm-pill="urgent"]').forEach(el => el.textContent = c.urgent);

        // Cacher le bouton "Tout marquer lu" si plus rien à lire
        const btn = document.getElementById('mmMarkAllRead');
        if (btn && c.unread === 0) btn.style.display = 'none';
    }

    function applyFilter(filter) {
        const cards = document.querySelectorAll('.mm-card[data-mm-id]');
        let visible = 0;
        cards.forEach(card => {
            let show = true;
            if (filter === 'unread' && card.dataset.mmState !== 'unread') show = false;
            if (filter === 'urgent' && card.dataset.mmPriority !== 'urgent') show = false;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        // Empty states ciblés
        const emptyUnread = document.getElementById('mmEmptyUnread');
        const emptyUrgent = document.getElementById('mmEmptyUrgent');
        if (emptyUnread) emptyUnread.hidden = !(filter === 'unread' && visible === 0 && cards.length > 0);
        if (emptyUrgent) emptyUrgent.hidden = !(filter === 'urgent' && visible === 0 && cards.length > 0);

        // Tabs aria
        document.querySelectorAll('.mm-tab').forEach(t => {
            const isActive = t.dataset.mmFilter === filter;
            t.classList.toggle('is-active', isActive);
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    function markAsRead(messageId, options) {
        const url = markReadUrlBase.replace('__ID__', messageId);
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(resp => resp.ok ? resp.json() : Promise.reject(new Error('HTTP ' + resp.status)))
        .then(data => {
            if (data && data.success) {
                const card = document.querySelector('.mm-card[data-mm-id="' + messageId + '"]');
                if (card) {
                    card.dataset.mmState = 'read';
                    card.classList.remove('is-unread');
                    const dot = card.querySelector('.mm-unread-dot');
                    if (dot) dot.remove();
                    const markBtns = card.querySelectorAll('[data-mm-mark-read]');
                    markBtns.forEach(b => b.remove());
                    // Update aria-label
                    const title = card.querySelector('.mm-title')?.textContent?.trim() || '';
                    card.setAttribute('aria-label', 'Message ' + title + ', lu');
                }
                // Retirer aussi le bouton dans le modal correspondant
                const modal = document.getElementById('mmModal' + messageId);
                if (modal) {
                    modal.querySelectorAll('[data-mm-mark-read]').forEach(b => b.remove());
                }
                refreshCounts();
                if (!options || !options.silent) {
                    showFeedback('Message marqué comme lu', 'success');
                }
            }
        })
        .catch(err => {
            showFeedback('Impossible de marquer le message comme lu', 'error');
            // eslint-disable-next-line no-console
            console.error('mes-annonces markAsRead', err);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Tabs
        document.querySelectorAll('.mm-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                applyFilter(this.dataset.mmFilter);
            });
        });

        // Click "Marquer lu" sur card et modal (event delegation)
        document.body.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-mm-mark-read]');
            if (!btn) return;
            e.preventDefault();
            const id = btn.dataset.mmMarkRead;
            markAsRead(id);
        });

        // Tout marquer lu
        const allBtn = document.getElementById('mmMarkAllRead');
        if (allBtn) {
            allBtn.addEventListener('click', function () {
                allBtn.disabled = true;
                fetch(markAllReadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(resp => resp.ok ? resp.json() : Promise.reject(new Error('HTTP ' + resp.status)))
                .then(data => {
                    if (data && data.success) {
                        document.querySelectorAll('.mm-card[data-mm-id]').forEach(card => {
                            card.dataset.mmState = 'read';
                            card.classList.remove('is-unread');
                            const dot = card.querySelector('.mm-unread-dot');
                            if (dot) dot.remove();
                            card.querySelectorAll('[data-mm-mark-read]').forEach(b => b.remove());
                        });
                        // Retirer aussi tous les boutons dans les modals
                        document.querySelectorAll('.mm-modal [data-mm-mark-read]').forEach(b => b.remove());
                        refreshCounts();
                        showFeedback('Tous les messages sont marqués comme lus', 'success');
                    } else {
                        allBtn.disabled = false;
                        showFeedback('Une erreur est survenue', 'error');
                    }
                })
                .catch(err => {
                    allBtn.disabled = false;
                    showFeedback('Impossible de marquer tous les messages comme lus', 'error');
                    // eslint-disable-next-line no-console
                    console.error('mes-annonces markAllRead', err);
                });
            });
        }
    });

    // Ouverture modal exposée globalement (utilisée dans onclick blade)
    window.mmOpenMessage = function (messageId) {
        const modalEl = document.getElementById('mmModal' + messageId);
        if (!modalEl) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        // Auto-mark as read on open (silencieux pour ne pas spam le toast)
        const card = document.querySelector('.mm-card[data-mm-id="' + messageId + '"]');
        if (card && card.dataset.mmState === 'unread') {
            markAsRead(messageId, { silent: true });
        }
    };
})();
</script>

{{-- Toast helper (window.showToast) --}}
<script src="{{ asset('js/inscriptions/common.js') }}" defer></script>
@endpush
