@extends('layouts.app')

@section('title', 'Mes Bulletins')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ============================================================
   MES BULLETINS (systeme LMD) — namespace mbl-*
   ============================================================ */
.mbl-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 30px rgba(4, 83, 203, .18);
}
.mbl-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.mbl-hero-left { display: flex; align-items: center; gap: 1rem; }
.mbl-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.mbl-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.mbl-hero p { color: rgba(255, 255, 255, .72); font-size: .88rem; margin: 0; }
.mbl-hero-badge {
    display: inline-flex; align-items: center; gap: .45rem;
    background: rgba(255, 255, 255, .15);
    border: 1px solid rgba(255, 255, 255, .2);
    border-radius: 10px;
    padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600; color: #fff;
}
.mbl-hero-meta {
    display: flex; flex-wrap: wrap; gap: .5rem;
    margin-top: 1.25rem;
}
.mbl-hero-chip {
    background: rgba(255, 255, 255, .1);
    border: 1px solid rgba(255, 255, 255, .15);
    border-radius: 10px;
    padding: .45rem .85rem;
    font-size: .78rem; color: rgba(255, 255, 255, .9);
}
.mbl-hero-chip strong { color: #fff; font-weight: 700; }

.mbl-count {
    display: inline-flex; align-items: center; gap: .5rem;
    font-size: .85rem; font-weight: 600; color: #64748b;
    margin-bottom: 1rem;
}

.mbl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
    gap: 1.25rem;
}
.mbl-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 1px 2px rgba(15, 23, 42, .06);
    transition: box-shadow .2s ease;
}
.mbl-card:hover {
    box-shadow: 0 8px 30px rgba(4, 83, 203, .08), 0 2px 8px rgba(15, 23, 42, .04);
}
.mbl-card-stripe { height: 4px; background: linear-gradient(90deg, #0453cb, #5e91de); }
.mbl-card-stripe--fail { background: linear-gradient(90deg, #dc2626, #f08a8a); }
.mbl-card-body { padding: 1.25rem 1.35rem 1.35rem; }
.mbl-card-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: .75rem; margin-bottom: 1rem;
}
.mbl-card-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; }
.mbl-card-sub {
    font-size: .78rem; color: #64748b;
    margin-top: .2rem;
    display: flex; flex-wrap: wrap; align-items: center; gap: .4rem;
}
.mbl-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .3rem .65rem; border-radius: 8px;
    font-size: .72rem; font-weight: 700; white-space: nowrap;
    background: rgba(4, 83, 203, .1); color: #0453cb;
    border: 1px solid rgba(4, 83, 203, .2);
}
.mbl-badge--wait {
    background: rgba(100, 116, 139, .1); color: #64748b;
    border-color: rgba(100, 116, 139, .2);
}
.mbl-stats {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: .65rem; margin-bottom: 1.1rem;
}
.mbl-stat {
    background: #f8fafc; border: 1px solid #eef2f7;
    border-radius: 10px; padding: .6rem .5rem; text-align: center;
}
.mbl-stat-label {
    font-size: .64rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .5px;
}
.mbl-stat-value { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin-top: .2rem; }
.mbl-stat-value--ok { color: #10b981; }
.mbl-stat-value--ko { color: #dc2626; }
.mbl-stat-unit { font-size: .7rem; font-weight: 500; color: #64748b; }

.mbl-progress {
    height: 6px; border-radius: 99px; background: #eef2f7;
    overflow: hidden; margin-bottom: 1.1rem;
}
.mbl-progress span { display: block; height: 100%; background: #0453cb; }

.mbl-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.mbl-btn {
    flex: 1; min-width: 130px;
    display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
    padding: .55rem .9rem; border-radius: 10px;
    font-size: .82rem; font-weight: 600; text-decoration: none;
    background: #0453cb; color: #fff; border: 1px solid #0453cb;
    transition: background .2s ease;
}
.mbl-btn:hover { background: #033a8e; color: #fff; }

.mbl-empty {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    padding: 3rem 2rem; text-align: center;
}
.mbl-empty-icon {
    width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 1rem;
    background: rgba(4, 83, 203, .08); color: #0453cb;
    display: flex; align-items: center; justify-content: center; font-size: 1.6rem;
}
.mbl-empty h3 { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: .5rem; }
.mbl-empty p { font-size: .88rem; color: #64748b; margin: 0; }

@media (max-width: 768px) {
    .mbl-hero { padding: 1.5rem 1.25rem; }
    .mbl-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
@php
    $mblAnnee = $anneeCourante?->display_name ?? (date('Y').'-'.(date('Y') + 1));
    $mblClasse = $inscription?->classe?->name ?? null;
@endphp

<div class="dashboard-acasi">
    <div class="main-content">

        <div class="mbl-hero">
            <div class="mbl-hero-top">
                <div class="mbl-hero-left">
                    <div class="mbl-hero-icon"><i class="fas fa-file-alt"></i></div>
                    <div>
                        <h1>Mes bulletins</h1>
                        <p>Consultez vos résultats semestriels publiés par l'établissement</p>
                    </div>
                </div>
                <div class="mbl-hero-badge">
                    <i class="fas fa-calendar-alt"></i>Année {{ $mblAnnee }}
                </div>
            </div>

            @if($mblClasse)
                <div class="mbl-hero-meta">
                    <span class="mbl-hero-chip">Classe <strong>{{ $mblClasse }}</strong></span>
                    @if($inscription?->classe?->parcours?->name)
                        <span class="mbl-hero-chip">@rang('parcours') <strong>{{ $inscription->classe->parcours->name }}</strong></span>
                    @endif
                </div>
            @endif
        </div>

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

        @if($bulletins->isEmpty())
            <div class="mbl-empty">
                <div class="mbl-empty-icon"><i class="fas fa-file-alt"></i></div>
                <h3>Aucun bulletin disponible</h3>
                <p>
                    Vos bulletins apparaîtront ici dès qu'ils auront été publiés par l'administration,
                    après la délibération du semestre.
                </p>
            </div>
        @else
            <div class="mbl-count">
                <i class="fas fa-layer-group"></i>
                {{ $bulletins->count() }} bulletin{{ $bulletins->count() > 1 ? 's' : '' }} disponible{{ $bulletins->count() > 1 ? 's' : '' }}
            </div>

            <div class="mbl-grid">
                @foreach($bulletins as $bulletin)
                    @php
                        $moy = $bulletin->moyenne_generale === null ? null : (float) $bulletin->moyenne_generale;
                        $moyOk = $moy !== null && $moy >= 10;
                        $creditsTotaux = (int) ($bulletin->credits_totaux ?? 0);
                        $creditsAcquis = (int) ($bulletin->credits_capitalises ?? 0);
                        $tauxCredits = $creditsTotaux > 0 ? min(100, round(($creditsAcquis / $creditsTotaux) * 100)) : 0;
                        $mentionLabel = $bulletin->mention_generale;
                    @endphp

                    <div class="mbl-card">
                        <div class="mbl-card-stripe {{ $moy !== null && ! $moyOk ? 'mbl-card-stripe--fail' : '' }}"></div>
                        <div class="mbl-card-body">
                            <div class="mbl-card-head">
                                <div>
                                    <div class="mbl-card-title">Semestre {{ $bulletin->semestre }}</div>
                                    <div class="mbl-card-sub">
                                        <span>{{ $bulletin->anneeUniversitaire->display_name ?? $mblAnnee }}</span>
                                        @if($bulletin->classe?->name)
                                            <span>•</span>
                                            <span>{{ $bulletin->classe->name }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if($mentionLabel)
                                    <span class="mbl-badge"><i class="fas fa-medal"></i>{{ $mentionLabel }}</span>
                                @else
                                    <span class="mbl-badge mbl-badge--wait"><i class="fas fa-clock"></i>En attente</span>
                                @endif
                            </div>

                            <div class="mbl-stats">
                                <div class="mbl-stat">
                                    <div class="mbl-stat-label">Moyenne</div>
                                    <div class="mbl-stat-value {{ $moy === null ? '' : ($moyOk ? 'mbl-stat-value--ok' : 'mbl-stat-value--ko') }}">
                                        @if($moy !== null)
                                            {{ number_format($moy, 2) }}<span class="mbl-stat-unit">/20</span>
                                        @else
                                            <span class="mbl-stat-unit">—</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mbl-stat">
                                    <div class="mbl-stat-label">Crédits</div>
                                    <div class="mbl-stat-value">
                                        {{ $creditsAcquis }}<span class="mbl-stat-unit">/{{ $creditsTotaux ?: '—' }}</span>
                                    </div>
                                </div>
                                <div class="mbl-stat">
                                    <div class="mbl-stat-label">Rang</div>
                                    <div class="mbl-stat-value">
                                        {{ $bulletin->rang ?? '—' }}<span class="mbl-stat-unit">/{{ $bulletin->effectif ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mbl-progress" title="{{ $tauxCredits }}% des crédits capitalisés">
                                <span style="width: {{ $tauxCredits }}%;"></span>
                            </div>

                            <div class="mbl-actions">
                                <a href="{{ route('esbtp.mon-bulletin-lmd.show', $bulletin) }}" class="mbl-btn">
                                    <i class="fas fa-eye"></i>Consulter
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
