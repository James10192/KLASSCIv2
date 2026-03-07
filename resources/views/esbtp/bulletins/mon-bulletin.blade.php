@extends('layouts.app')

@section('title', 'Mes Bulletins')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ============================================================
   PAGE MES BULLETINS — Design Premium KLASSCI 2025
   ============================================================ */

/* ---- Variables locales ---- */
.bulletins-page {
    --card-radius: 20px;
    --card-shadow: 0 2px 16px rgba(4, 83, 203, 0.07);
    --card-shadow-hover: 0 12px 40px rgba(4, 83, 203, 0.14);
    --border: rgba(4, 83, 203, 0.08);
    --stripe-h: 4px;
}

/* ---- Layout ---- */
.bulletins-page .main-content {
    max-width: 100%;
}

/* ---- Hero header ---- */
.bulletins-hero {
    background: linear-gradient(135deg, #0453cb 0%, #1b64d4 60%, #3b82f6 100%);
    border-radius: var(--card-radius);
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    position: relative;
    overflow: hidden;
}

.bulletins-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}

.bulletins-hero-icon {
    width: 64px;
    height: 64px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    flex-shrink: 0;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.bulletins-hero-text {
    flex: 1;
}

.bulletins-hero-text h1 {
    font-size: 1.75rem;
    font-weight: 800;
    margin: 0 0 0.25rem;
    letter-spacing: -0.02em;
    color: white;
}

.bulletins-hero-text p {
    margin: 0;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
}

.bulletins-hero-badge {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 12px;
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

/* ---- Summary bar (total bulletins) ---- */
.bulletins-summary {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.bulletins-count {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: rgba(4, 83, 203, 0.07);
    color: var(--primary);
    border-radius: 50px;
    padding: 0.35rem 0.9rem;
    font-size: 0.82rem;
    font-weight: 600;
}

/* ---- Grid de bulletins ---- */
.bulletins-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}

/* ---- Carte bulletin ---- */
.bulletin-card {
    background: white;
    border-radius: var(--card-radius);
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border);
    overflow: hidden;
    transition: transform 0.22s ease, box-shadow 0.22s ease;
    position: relative;
}

.bulletin-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--card-shadow-hover);
}

/* Stripe colorée selon mention */
.bulletin-card-stripe {
    height: var(--stripe-h);
    width: 100%;
}

.stripe-excellent  { background: linear-gradient(90deg, #4f46e5, #7c3aed); }
.stripe-tres-bien  { background: linear-gradient(90deg, #0453cb, #3b82f6); }
.stripe-bien       { background: linear-gradient(90deg, #0891b2, #06b6d4); }
.stripe-assez-bien { background: linear-gradient(90deg, #059669, #10b981); }
.stripe-passable   { background: linear-gradient(90deg, #d97706, #f59e0b); }
.stripe-echec      { background: linear-gradient(90deg, #dc2626, #ef4444); }
.stripe-default    { background: linear-gradient(90deg, #0453cb, #1b64d4); }

.bulletin-card-body {
    padding: 1.5rem 1.75rem;
}

/* ---- En-tête carte ---- */
.bulletin-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.bulletin-card-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.3rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.bulletin-card-title .year-icon {
    width: 28px;
    height: 28px;
    background: rgba(4, 83, 203, 0.1);
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: var(--primary);
    flex-shrink: 0;
}

.bulletin-card-subtitle {
    font-size: 0.82rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin: 0;
}

.bulletin-periode-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: rgba(4, 83, 203, 0.06);
    color: var(--primary);
    border-radius: 50px;
    padding: 0.2rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 600;
}

/* ---- Mention badge ---- */
.mention-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.4rem 0.85rem;
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    white-space: nowrap;
    flex-shrink: 0;
}

.mention-excellent  { background: rgba(79, 70, 229, 0.1); color: #4f46e5; border: 1px solid rgba(79, 70, 229, 0.2); }
.mention-tres-bien  { background: rgba(4, 83, 203, 0.1);  color: #0453cb; border: 1px solid rgba(4, 83, 203, 0.2); }
.mention-bien       { background: rgba(8, 145, 178, 0.1); color: #0891b2; border: 1px solid rgba(8, 145, 178, 0.2); }
.mention-assez-bien { background: rgba(5, 150, 105, 0.1); color: #059669; border: 1px solid rgba(5, 150, 105, 0.2); }
.mention-passable   { background: rgba(217, 119, 6, 0.1); color: #d97706; border: 1px solid rgba(217, 119, 6, 0.2); }
.mention-echec      { background: rgba(220, 38, 38, 0.1); color: #dc2626; border: 1px solid rgba(220, 38, 38, 0.2); }

/* ---- Stats row ---- */
.bulletin-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.bulletin-stat {
    background: #f8fafc;
    border-radius: 14px;
    padding: 0.9rem 1rem;
    text-align: center;
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
}

.stat-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.4rem;
}

.stat-value {
    font-size: 1.4rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 0.15rem;
}

.stat-value.success { color: #059669; }
.stat-value.danger  { color: #dc2626; }
.stat-value.neutral { color: var(--text-primary); }

.stat-sub {
    font-size: 0.75rem;
    color: var(--text-secondary);
    font-weight: 500;
}

/* progression arrow */
.progression-up   { color: #059669; font-size: 1.3rem; font-weight: 800; }
.progression-down { color: #dc2626; font-size: 1.3rem; font-weight: 800; }
.progression-eq   { color: var(--text-secondary); font-size: 1.3rem; font-weight: 800; }
.progression-none { color: var(--text-muted); font-size: 1.3rem; }

/* ---- Actions ---- */
.bulletin-card-actions {
    display: flex;
    gap: 0.75rem;
    padding-top: 1.25rem;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
}

.btn-bulletin-consult {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.65rem 1.25rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.18s ease;
    background: rgba(4, 83, 203, 0.08);
    color: var(--primary);
    border: 1px solid rgba(4, 83, 203, 0.15);
}

.btn-bulletin-consult:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(4, 83, 203, 0.25);
}

.btn-bulletin-download {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.65rem 1.25rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.18s ease;
    background: linear-gradient(135deg, #059669, #10b981);
    color: white;
    border: 1px solid transparent;
    box-shadow: 0 2px 8px rgba(5, 150, 105, 0.2);
}

.btn-bulletin-download:hover {
    background: linear-gradient(135deg, #047857, #059669);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35);
}

/* ---- Empty state ---- */
.empty-state {
    background: white;
    border-radius: var(--card-radius);
    padding: 4rem 2rem;
    text-align: center;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border);
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    border-radius: 24px;
    background: linear-gradient(135deg, rgba(4, 83, 203, 0.08), rgba(27, 100, 212, 0.05));
    border: 1px solid rgba(4, 83, 203, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--primary);
    margin: 0 auto 1.5rem;
}

.empty-state h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--text-secondary);
    font-size: 0.9rem;
    line-height: 1.6;
    max-width: 400px;
    margin: 0 auto;
}

/* ---- Responsive ---- */
@media (max-width: 768px) {
    .bulletins-hero {
        flex-direction: column;
        align-items: flex-start;
        padding: 1.5rem;
    }

    .bulletins-hero-badge {
        align-self: flex-start;
    }

    .bulletin-stats {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }

    .stat-value {
        font-size: 1.15rem;
    }

    .bulletin-card-body {
        padding: 1.25rem;
    }

    .bulletin-card-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .bulletins-hero-text h1 {
        font-size: 1.35rem;
    }

    .bulletin-stats {
        grid-template-columns: 1fr;
        gap: 0.6rem;
    }

    .bulletin-stat {
        display: flex;
        align-items: center;
        text-align: left;
        gap: 1rem;
        padding: 0.75rem 1rem;
    }

    .stat-label { margin-bottom: 0; min-width: 90px; }
    .stat-value { font-size: 1.2rem; }
}
</style>
@endpush

@section('content')
@php
    /* ---- Fix année courante ---- */
    $anneeLabel = null;
    if ($anneeCourante) {
        if ($anneeCourante->annee_debut && $anneeCourante->annee_fin) {
            $anneeLabel = $anneeCourante->annee_debut . '-' . $anneeCourante->annee_fin;
        } elseif ($anneeCourante->name) {
            $anneeLabel = $anneeCourante->name;
        }
    }
    $anneeLabel = $anneeLabel ?? date('Y') . '-' . (date('Y') + 1);
@endphp

<div class="dashboard-acasi bulletins-page">
    <div class="main-content">

        {{-- ===== HERO HEADER ===== --}}
        <div class="bulletins-hero">
            <div class="bulletins-hero-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="bulletins-hero-text">
                <h1>Mes Bulletins</h1>
                <p>Consultez et téléchargez vos bulletins de notes semestriels</p>
            </div>
            <div class="bulletins-hero-badge">
                <i class="fas fa-calendar-alt"></i>
                Année {{ $anneeLabel }}
            </div>
        </div>

        {{-- ===== ALERTS ===== --}}
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ===== PAS D'INSCRIPTION ===== --}}
        @if(!isset($inscription) || !$inscription)
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i>
                </div>
                <h3>Aucune inscription active</h3>
                <p>
                    Vous n'avez pas d'inscription active pour l'année universitaire
                    <strong>{{ $anneeCourante->name ?? 'en cours' }}</strong>.<br>
                    Veuillez contacter l'administration pour régulariser votre situation.
                </p>
                <a href="{{ route('esbtp.mon-profil.index') }}" class="btn-acasi primary mt-4">
                    <i class="fas fa-user me-2"></i>Voir mon profil
                </a>
            </div>

        {{-- ===== AUCUN BULLETIN ===== --}}
        @elseif($bulletins->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>Aucun bulletin disponible</h3>
                <p>
                    Vos bulletins apparaîtront ici une fois publiés par l'administration.<br>
                    Revenez prochainement ou contactez l'administration.
                </p>
            </div>

        {{-- ===== LISTE DES BULLETINS ===== --}}
        @else
            {{-- Compteur --}}
            <div class="bulletins-summary">
                <span class="bulletins-count">
                    <i class="fas fa-layer-group"></i>
                    {{ $bulletins->count() }} bulletin{{ $bulletins->count() > 1 ? 's' : '' }} disponible{{ $bulletins->count() > 1 ? 's' : '' }}
                </span>
            </div>

            <div class="bulletins-grid">
                @foreach($bulletins as $bulletin)
                @php
                    /* -- Mention → classes CSS -- */
                    $mention = $bulletin->mention ?? null;
                    $mentionLower = Str::lower($mention ?? '');
                    if (Str::contains($mentionLower, 'excellent')) {
                        $stripeClass  = 'stripe-excellent';
                        $mentionClass = 'mention-excellent';
                        $mentionIcon  = 'fas fa-trophy';
                    } elseif (Str::contains($mentionLower, 'très bien') || Str::contains($mentionLower, 'tres bien')) {
                        $stripeClass  = 'stripe-tres-bien';
                        $mentionClass = 'mention-tres-bien';
                        $mentionIcon  = 'fas fa-star';
                    } elseif (Str::contains($mentionLower, 'bien')) {
                        $stripeClass  = 'stripe-bien';
                        $mentionClass = 'mention-bien';
                        $mentionIcon  = 'fas fa-thumbs-up';
                    } elseif (Str::contains($mentionLower, 'assez bien')) {
                        $stripeClass  = 'stripe-assez-bien';
                        $mentionClass = 'mention-assez-bien';
                        $mentionIcon  = 'fas fa-check-circle';
                    } elseif (Str::contains($mentionLower, 'passable')) {
                        $stripeClass  = 'stripe-passable';
                        $mentionClass = 'mention-passable';
                        $mentionIcon  = 'fas fa-minus-circle';
                    } elseif ($mention) {
                        $stripeClass  = 'stripe-echec';
                        $mentionClass = 'mention-echec';
                        $mentionIcon  = 'fas fa-times-circle';
                    } else {
                        $stripeClass  = 'stripe-default';
                        $mentionClass = '';
                        $mentionIcon  = '';
                    }

                    /* -- Libellé période -- */
                    $periodeLabel = match($bulletin->periode ?? '') {
                        'semestre1' => 'Semestre 1',
                        'semestre2' => 'Semestre 2',
                        'annuel'    => 'Annuel',
                        default     => ucfirst($bulletin->periode ?? 'Période')
                    };
                    $periodeIcon = match($bulletin->periode ?? '') {
                        'semestre1' => 'fas fa-hourglass-start',
                        'semestre2' => 'fas fa-hourglass-end',
                        'annuel'    => 'fas fa-calendar-check',
                        default     => 'fas fa-calendar'
                    };

                    /* -- Année du bulletin -- */
                    $bAnnee = null;
                    if ($bulletin->anneeUniversitaire) {
                        if ($bulletin->anneeUniversitaire->annee_debut && $bulletin->anneeUniversitaire->annee_fin) {
                            $bAnnee = $bulletin->anneeUniversitaire->annee_debut . '-' . $bulletin->anneeUniversitaire->annee_fin;
                        } elseif ($bulletin->anneeUniversitaire->name) {
                            $bAnnee = $bulletin->anneeUniversitaire->name;
                        }
                    }
                    $bAnnee = $bAnnee ?? 'Année non définie';

                    /* -- Classe -- */
                    $classeLabel = $bulletin->classe->name ?? ($bulletin->classe->libelle ?? 'N/A');

                    /* -- Moyenne -- */
                    $moy = isset($bulletin->moyenne_generale) ? (float) $bulletin->moyenne_generale : null;
                    $moyLabel    = $moy !== null ? number_format($moy, 2) . '/20' : 'N/A';
                    $moyClass    = $moy === null ? 'neutral' : ($moy >= 10 ? 'success' : 'danger');

                    /* -- Progression -- */
                    $progression = null;
                    $prevBulletin = $bulletins->where('periode', '<', $bulletin->periode)->sortByDesc('periode')->first();
                    if ($prevBulletin && isset($prevBulletin->moyenne_generale) && $moy !== null) {
                        $progression = $moy - (float) $prevBulletin->moyenne_generale;
                    }
                @endphp

                <div class="bulletin-card">
                    {{-- Stripe colorée --}}
                    <div class="bulletin-card-stripe {{ $stripeClass }}"></div>

                    <div class="bulletin-card-body">
                        {{-- En-tête --}}
                        <div class="bulletin-card-header">
                            <div>
                                <div class="bulletin-card-title">
                                    <span class="year-icon"><i class="fas fa-calendar-alt"></i></span>
                                    {{ $bAnnee }}
                                </div>
                                <div class="bulletin-card-subtitle">
                                    <span class="bulletin-periode-chip">
                                        <i class="{{ $periodeIcon }}"></i>
                                        {{ $periodeLabel }}
                                    </span>
                                    <span style="color: var(--text-muted);">•</span>
                                    <span>{{ $classeLabel }}</span>
                                </div>
                            </div>
                            @if($mention)
                                <span class="mention-badge {{ $mentionClass }}">
                                    <i class="{{ $mentionIcon }}"></i>
                                    {{ $mention }}
                                </span>
                            @else
                                <span class="mention-badge" style="background: rgba(107,114,128,0.08); color: var(--text-secondary); border: 1px solid rgba(107,114,128,0.15);">
                                    <i class="fas fa-clock"></i>
                                    En attente
                                </span>
                            @endif
                        </div>

                        {{-- Stats --}}
                        <div class="bulletin-stats">
                            {{-- Moyenne --}}
                            <div class="bulletin-stat">
                                <div class="stat-label">Moyenne</div>
                                <div class="stat-value {{ $moyClass }}">
                                    @if($moy !== null)
                                        {{ number_format($moy, 2) }}<span style="font-size: 0.75rem; font-weight: 500; color: var(--text-secondary);">/20</span>
                                    @else
                                        <span style="font-size: 1rem; color: var(--text-muted);">N/A</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Rang --}}
                            <div class="bulletin-stat">
                                <div class="stat-label">Rang</div>
                                <div class="stat-value neutral">
                                    {{ $bulletin->rang ?? '—' }}<span style="font-size: 0.75rem; font-weight: 500; color: var(--text-secondary);">/{{ $bulletin->effectif_classe ?? '?' }}</span>
                                </div>
                            </div>

                            {{-- Progression --}}
                            <div class="bulletin-stat">
                                <div class="stat-label">Évolution</div>
                                <div class="stat-value neutral">
                                    @if($progression !== null)
                                        @if($progression > 0)
                                            <span class="progression-up">
                                                <i class="fas fa-arrow-up" style="font-size: 0.75em;"></i> +{{ number_format($progression, 2) }}
                                            </span>
                                        @elseif($progression < 0)
                                            <span class="progression-down">
                                                <i class="fas fa-arrow-down" style="font-size: 0.75em;"></i> {{ number_format($progression, 2) }}
                                            </span>
                                        @else
                                            <span class="progression-eq">
                                                <i class="fas fa-minus" style="font-size: 0.75em;"></i> 0
                                            </span>
                                        @endif
                                    @else
                                        <span class="progression-none">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="bulletin-card-actions">
                            <a href="{{ route('esbtp.mon-bulletin.show', $bulletin->id) }}" class="btn-bulletin-consult">
                                <i class="fas fa-eye"></i>
                                Consulter
                            </a>
                            <a href="{{ route('esbtp.bulletins.download', $bulletin->id) }}" class="btn-bulletin-download">
                                <i class="fas fa-download"></i>
                                Télécharger PDF
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection
