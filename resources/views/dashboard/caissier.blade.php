@extends('layouts.app')

@section('title', 'Tableau de bord Caisse')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ═══════════ Caisse — namespace cx- ═══════════ */
    .cx-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
    }
    .cx-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .cx-hero-left { display: flex; align-items: center; gap: 1rem; }
    .cx-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15);
        backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; color: #fff; flex-shrink: 0;
    }
    .cx-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .cx-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
    .cx-hero-meta { display: flex; flex-direction: column; align-items: flex-end; gap: .3rem; }
    .cx-chip {
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2);
        border-radius: 20px; padding: .25rem .8rem; font-size: .76rem; color: #fff;
    }
    .cx-date { font-size: .76rem; color: rgba(255,255,255,.65); }

    .cx-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .cx-kpi {
        flex: 1; min-width: 150px;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px; padding: .9rem 1rem;
        display: flex; align-items: center; gap: .75rem;
    }
    .cx-kpi-ic {
        width: 34px; height: 34px; border-radius: 10px;
        background: rgba(255,255,255,.14);
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem; flex-shrink: 0;
    }
    .cx-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1.1; }
    .cx-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }
    .cx-kpi--alert { background: rgba(245,158,11,.22); border-color: rgba(245,158,11,.4); }

    /* Accès rapides : chaque tuile est conditionnée par une permission.
       Une école qui en accorde davantage voit la grille se remplir d'elle-même,
       sans qu'aucune ligne de code ne change. */
    .cx-actions { display: grid; grid-template-columns: repeat(auto-fill, minmax(206px, 1fr)); gap: .75rem; margin-bottom: 1.25rem; }
    .cx-act {
        display: flex; align-items: center; gap: .75rem;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        padding: .85rem 1rem; text-decoration: none;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        transition: border-color .2s, box-shadow .2s;
    }
    .cx-act:hover {
        border-color: #c7d4e5;
        box-shadow: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
    }
    .cx-act-ic {
        width: 38px; height: 38px; border-radius: 11px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: .92rem; flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(4,83,203,.25);
    }
    .cx-act--soft .cx-act-ic { background: rgba(4,83,203,.1); color: #0453cb; box-shadow: none; }
    .cx-act-t { font-weight: 700; font-size: .88rem; color: #1e293b; line-height: 1.2; }
    .cx-act-d { font-size: .74rem; color: #64748b; margin-top: .1rem; line-height: 1.3; }

    .cx-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .cx-card-hd { display: flex; align-items: center; justify-content: space-between;
        gap: 1rem; padding: .9rem 1.15rem; border-bottom: 1px solid #f1f5f9; }
    .cx-card-hd-l { display: flex; align-items: center; gap: .7rem; }
    .cx-card-ic { width: 34px; height: 34px; border-radius: 10px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: .82rem; }
    .cx-card-t { font-weight: 700; font-size: .95rem; color: #0f172a; }
    .cx-card-lnk { font-size: .78rem; color: #0453cb; font-weight: 600; text-decoration: none; }

    .cx-row { display: flex; align-items: center; gap: .8rem; padding: .7rem 1.15rem; border-bottom: 1px solid #f8fafc; }
    .cx-row:last-child { border-bottom: none; }
    .cx-av { width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, #0453cb, #5e91de); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: .78rem; font-weight: 700; flex-shrink: 0; }
    .cx-row-info { flex: 1; min-width: 0; }
    .cx-row-name { font-weight: 600; font-size: .88rem; color: #1e293b;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cx-row-meta { display: flex; align-items: center; gap: .45rem; margin-top: .15rem; flex-wrap: wrap; }
    .cx-pill { display: inline-flex; align-items: center; gap: .25rem;
        padding: .1rem .45rem; border-radius: 20px; font-size: .68rem; font-weight: 700; }
    .cx-pill--ok { background: #d1fae5; color: #065f46; }
    .cx-pill--wait { background: #ffedd5; color: #9a3412; }
    .cx-row-mode { font-size: .72rem; color: #64748b; }
    .cx-row-right { text-align: right; flex-shrink: 0; }
    .cx-amount { font-weight: 700; font-size: .92rem; color: #1e293b; }
    .cx-amount--ok { color: #10b981; }
    .cx-time { font-size: .72rem; color: #94a3b8; }

    .cx-empty { text-align: center; padding: 2.2rem 1.2rem; color: #64748b; }
    .cx-empty-ic { width: 52px; height: 52px; border-radius: 15px; margin: 0 auto .7rem;
        background: rgba(4,83,203,.07); color: #0453cb;
        display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    .cx-empty-t { font-weight: 700; color: #1e293b; font-size: .92rem; }
    .cx-empty-d { font-size: .8rem; margin-top: .2rem; }
    .cx-empty a { color: #0453cb; font-weight: 600; text-decoration: none; }

    @@media (max-width: 768px) {
        .cx-hero { padding: 1.5rem 1.25rem 1.25rem; }
        .cx-hero-meta { align-items: flex-start; }
    }
</style>
@endpush

@section('content')
@php
    $peutVoirPaiements = auth()->user()?->canany(['paiements.view', 'paiements.view_own']) ?? false;
    $preInscriptionOuverte = app(\App\Services\TenantScolariteSettings::class)->cashierPreEnrollmentEnabled();
@endphp

<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">

        <div class="cx-hero">
            <div class="cx-hero-top">
                <div class="cx-hero-left">
                    <div class="cx-hero-icon"><i class="fas fa-cash-register"></i></div>
                    <div>
                        <h1>Caisse</h1>
                        <p>Bonjour {{ $user->name }}, voici votre journée.</p>
                    </div>
                </div>
                <div class="cx-hero-meta">
                    <span class="cx-chip"><i class="fas fa-calendar me-1"></i>{{ $anneeEnCours->name ?? 'Année non définie' }}</span>
                    <span class="cx-date">{{ \Carbon\Carbon::now()->isoFormat('dddd D MMMM YYYY') }}</span>
                </div>
            </div>

            <div class="cx-kpis">
                <div class="cx-kpi">
                    <div class="cx-kpi-ic"><i class="fas fa-receipt"></i></div>
                    <div>
                        <div class="cx-kpi-value">{{ $paiementsAujourdhuiCount }}</div>
                        <div class="cx-kpi-label">Versements aujourd'hui</div>
                    </div>
                </div>
                <div class="cx-kpi">
                    <div class="cx-kpi-ic"><i class="fas fa-coins"></i></div>
                    <div>
                        <div class="cx-kpi-value">{{ number_format($montantEncaisseAujourdhui, 0, ',', ' ') }}<span style="font-size:.72rem;font-weight:600;opacity:.7;"> FCFA</span></div>
                        <div class="cx-kpi-label">Encaissé aujourd'hui</div>
                    </div>
                </div>
                @if($preInscriptionOuverte)
                <div class="cx-kpi">
                    <div class="cx-kpi-ic"><i class="fas fa-user-plus"></i></div>
                    <div>
                        <div class="cx-kpi-value">{{ $preInscriptionsAujourdhui }}</div>
                        <div class="cx-kpi-label">Pré-inscriptions du jour</div>
                    </div>
                </div>
                <div class="cx-kpi {{ $preInscriptionsEnAttente > 0 ? 'cx-kpi--alert' : '' }}">
                    <div class="cx-kpi-ic"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <div class="cx-kpi-value">{{ $preInscriptionsEnAttente }}</div>
                        <div class="cx-kpi-label">En attente de validation</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Chaque tuile dépend d'une permission. La grille se remplit d'elle-même
             à mesure que l'établissement en accorde, sans toucher au code. --}}
        <div class="cx-actions">
            @can('paiements.create')
            <a href="{{ route('esbtp.paiements.create') }}" class="cx-act">
                <div class="cx-act-ic"><i class="fas fa-plus"></i></div>
                <div>
                    <div class="cx-act-t">Encaisser</div>
                    <div class="cx-act-d">Enregistrer un versement</div>
                </div>
            </a>
            @endcan

            @if($preInscriptionOuverte)
            @can('inscriptions.create')
            <a href="{{ route('esbtp.inscriptions.pre-inscription') }}" class="cx-act">
                <div class="cx-act-ic"><i class="fas fa-user-plus"></i></div>
                <div>
                    <div class="cx-act-t">Pré-inscrire</div>
                    <div class="cx-act-d">Ouvrir un dossier étudiant</div>
                </div>
            </a>
            @endcan
            @endif

            @can('comptabilite.access')
            <a href="{{ route('esbtp.caisse.ma-caisse') }}" class="cx-act cx-act--soft">
                <div class="cx-act-ic"><i class="fas fa-vault"></i></div>
                <div>
                    <div class="cx-act-t">Ma caisse</div>
                    <div class="cx-act-d">Point de ma journée</div>
                </div>
            </a>
            @endcan

            @if($peutVoirPaiements)
            <a href="{{ route('esbtp.paiements.index') }}" class="cx-act cx-act--soft">
                <div class="cx-act-ic"><i class="fas fa-list"></i></div>
                <div>
                    <div class="cx-act-t">Les versements</div>
                    <div class="cx-act-d">@can('paiements.view')Tous les versements@else Ceux que j'ai saisis @endcan</div>
                </div>
            </a>
            @endif

            @can('comptabilite.journal.view')
            <a href="{{ route('esbtp.comptabilite.journal-caisse.index') }}" class="cx-act cx-act--soft">
                <div class="cx-act-ic"><i class="fas fa-book"></i></div>
                <div>
                    <div class="cx-act-t">Journal de caisse</div>
                    <div class="cx-act-d">Point du jour détaillé</div>
                </div>
            </a>
            @endcan

            @can('comptabilite.reconciliation.open')
            <a href="{{ route('esbtp.comptabilite.reconciliation.create') }}" class="cx-act cx-act--soft">
                <div class="cx-act-ic"><i class="fas fa-scale-balanced"></i></div>
                <div>
                    <div class="cx-act-t">Réconcilier</div>
                    <div class="cx-act-d">Comparer caisse et système</div>
                </div>
            </a>
            @endcan
        </div>

        <div class="cx-card">
            <div class="cx-card-hd">
                <div class="cx-card-hd-l">
                    <div class="cx-card-ic"><i class="fas fa-history"></i></div>
                    <div class="cx-card-t">Derniers versements</div>
                </div>
                @if($peutVoirPaiements)
                <a href="{{ route('esbtp.paiements.index') }}" class="cx-card-lnk">Tout voir <i class="fas fa-arrow-right ms-1"></i></a>
                @endif
            </div>

            @forelse($paiementsRecents as $paiement)
                @php
                    $etudiant = $paiement->etudiant;
                    $initiales = $etudiant
                        ? mb_strtoupper(mb_substr($etudiant->nom ?? '', 0, 1, 'UTF-8').mb_substr($etudiant->prenoms ?? '', 0, 1, 'UTF-8'), 'UTF-8')
                        : '?';
                    $nomComplet = $etudiant ? trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')) : 'Étudiant inconnu';
                    $estValide = $paiement->status === 'validé';
                @endphp
                <div class="cx-row">
                    <div class="cx-av">{{ $initiales }}</div>
                    <div class="cx-row-info">
                        <div class="cx-row-name">{{ $nomComplet }}</div>
                        <div class="cx-row-meta">
                            <span class="cx-pill {{ $estValide ? 'cx-pill--ok' : 'cx-pill--wait' }}">
                                <i class="fas fa-{{ $estValide ? 'check' : 'clock' }}" style="font-size:.55rem;"></i>
                                {{ $estValide ? 'Validé' : ucfirst(str_replace('_', ' ', $paiement->status)) }}
                            </span>
                            @if($paiement->mode_paiement)
                                <span class="cx-row-mode">{{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement)) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="cx-row-right">
                        <div class="cx-amount {{ $estValide ? 'cx-amount--ok' : '' }}">
                            {{ number_format($paiement->montant, 0, ',', ' ') }} F
                        </div>
                        <div class="cx-time">{{ optional($paiement->created_at)->format('H:i') }}</div>
                    </div>
                </div>
            @empty
                <div class="cx-empty">
                    <div class="cx-empty-ic"><i class="fas fa-receipt"></i></div>
                    <div class="cx-empty-t">Aucun versement pour l'instant</div>
                    <div class="cx-empty-d">
                        @can('paiements.create')
                            Le premier encaissement de la journée apparaîtra ici.
                            <a href="{{ route('esbtp.paiements.create') }}">Encaisser maintenant</a>
                        @else
                            Les versements enregistrés apparaîtront ici.
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

    </div>
</div>
@endsection
