@extends('layouts.app')

@section('title', 'Bulletin LMD — ' . ($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '') . ' | KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/student-results.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD Bulletin Preview — Using sr- design system
       Only additions/overrides for LMD-specific elements
       ══════════════════════════════════════════════ */

    /* Info grid in hero */
    .bp-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: .6rem 1.5rem;
        margin-top: 1.25rem;
    }
    .bp-info-label {
        font-size: .62rem; font-weight: 700;
        color: rgba(255,255,255,.45);
        text-transform: uppercase; letter-spacing: .08em;
        margin-bottom: .1rem;
    }
    .bp-info-value {
        font-size: .88rem; font-weight: 500;
        color: rgba(255,255,255,.92);
    }

    /* ── UE/ECUE table overrides ── */
    .bp-ue td {
        background: var(--sr-primary-light) !important;
        font-weight: 700 !important;
        color: var(--sr-primary) !important;
        font-size: .88rem;
        border-bottom: 1px solid #bfdbfe;
    }
    .bp-ecue td {
        padding-left: 2rem !important;
        font-size: .85rem;
        color: #475569;
    }
    .bp-ecue:hover td { background: #fafcff; }

    .bp-code {
        font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
        font-size: .8rem; letter-spacing: .02em;
    }
    .bp-ue .bp-code { color: var(--sr-primary); }
    .bp-ecue .bp-code { color: var(--sr-muted-light); }

    /* Moyenne coloring */
    .bp-moy--pass { color: var(--sr-success); font-weight: 700; }
    .bp-moy--fail { color: var(--sr-danger); font-weight: 700; }

    /* Status badges */
    .bp-badge {
        display: inline-flex; align-items: center;
        padding: .2rem .55rem; border-radius: 20px;
        font-size: .7rem; font-weight: 700; letter-spacing: .02em;
    }
    .bp-badge--aq  { background: var(--sr-success-light); color: #059669; }
    .bp-badge--apc { background: #ecfeff; color: #0891b2; }
    .bp-badge--naq { background: var(--sr-danger-light); color: #dc2626; }

    .bp-mention {
        display: inline-flex; padding: .15rem .45rem;
        border-radius: var(--sr-radius-xs); font-size: .7rem;
        font-weight: 700; background: var(--sr-bg-alt); color: #475569;
    }

    .bp-stat-col {
        font-size: .78rem; color: var(--sr-muted-light);
        font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
    }

    .bp-credit {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 24px; padding: .1rem .4rem; border-radius: 5px;
        font-size: .8rem; font-weight: 700;
    }
    .bp-ue .bp-credit { background: #dbeafe; color: var(--sr-primary); }
    .bp-ecue .bp-credit { color: var(--sr-muted-light); background: transparent; }

    .bp-enseignant { font-size: .78rem; color: var(--sr-muted); white-space: nowrap; }

    /* Decision card */
    .bp-decision-icon {
        width: 44px; height: 44px; border-radius: var(--sr-radius-sm);
        background: var(--sr-primary-gradient);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1rem; flex-shrink: 0;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ══ Hero (sr- design system) ══ --}}
        @php $moyPass = ($moyenne_generale ?? 0) >= 10; @endphp

        <div class="sr-hero sr-animate">
            <div class="sr-hero-content">
                <div class="sr-hero-left">
                    <div class="sr-hero-avatar">
                        @if($etudiant->photo_url ?? false)
                            <img src="{{ $etudiant->photo_url }}" alt="{{ $etudiant->nom }}">
                        @else
                            <i class="fas fa-user-graduate"></i>
                        @endif
                    </div>
                    <div class="sr-hero-info">
                        <h1>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</h1>
                        <p>Bulletin semestriel LMD — {{ $niveau ?? '' }} — Semestre {{ $semestre }}</p>
                        <div class="sr-breadcrumb">
                            <a href="{{ route('esbtp.lmd.bulletins.index') }}">Bulletins LMD</a>
                            <i class="fas fa-chevron-right"></i>
                            <span>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</span>
                        </div>
                    </div>
                </div>
                <div class="sr-hero-actions">
                    <a href="{{ route('esbtp.lmd.bulletins.index') }}" class="sr-hero-btn">
                        <i class="fas fa-arrow-left"></i>Retour
                    </a>
                    <a href="{{ route('esbtp.lmd.bulletins.pdf', $bulletin) }}" class="sr-hero-btn--solid sr-hero-btn">
                        <i class="fas fa-file-pdf"></i>PDF
                    </a>
                    <form action="{{ route('esbtp.lmd.bulletins.toggle-publication', $bulletin) }}" method="POST" style="display:inline;">
                        @csrf @method('PUT')
                        <button type="submit" class="sr-hero-btn">
                            <i class="fas fa-{{ $bulletin->is_published ? 'eye-slash' : 'eye' }}"></i>
                            {{ $bulletin->is_published ? 'Dépublier' : 'Publier' }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Info grid --}}
            <div class="bp-info-grid">
                @if(isset($bulletin_fields))
                    @foreach($bulletin_fields as $field)
                        @if($field['show'] && $field['value'])
                            <div>
                                <div class="bp-info-label">{{ $field['label'] }}</div>
                                <div class="bp-info-value">{{ $field['value'] }}</div>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div>
                        <div class="bp-info-label">Domaine</div>
                        <div class="bp-info-value">{{ $domaine ?? '--' }}</div>
                    </div>
                    <div>
                        <div class="bp-info-label">Mention</div>
                        <div class="bp-info-value">{{ $mention ?? '--' }}</div>
                    </div>
                    <div>
                        <div class="bp-info-label">Parcours</div>
                        <div class="bp-info-value">{{ $parcours_label ?? '--' }}</div>
                    </div>
                @endif
                <div>
                    <div class="bp-info-label">Année universitaire</div>
                    <div class="bp-info-value">{{ $annee->name ?? '--' }}</div>
                </div>
            </div>
        </div>

        {{-- ══ KPIs (sr-stats style) ══ --}}
        <div class="sr-stats sr-animate sr-animate-delay-1" style="margin-bottom: 1.5rem;">
            <div class="sr-stat sr-stat--{{ $moyPass ? 'success' : 'danger' }}">
                <div class="sr-stat-icon"><i class="fas fa-calculator"></i></div>
                <div class="sr-stat-value">{{ $moyenne_generale !== null ? number_format($moyenne_generale, 2) : '--' }}</div>
                <div class="sr-stat-label">Moyenne Générale</div>
            </div>
            <div class="sr-stat sr-stat--primary">
                <div class="sr-stat-icon"><i class="fas fa-award"></i></div>
                <div class="sr-stat-value">{{ $credits_capitalises }}/{{ $credits_totaux }}</div>
                <div class="sr-stat-label">Crédits Capitalisés</div>
            </div>
            <div class="sr-stat sr-stat--info">
                <div class="sr-stat-icon"><i class="fas fa-trophy"></i></div>
                <div class="sr-stat-value">{{ $rang ?? '--' }}<sup style="font-size:.55em;">e</sup>/{{ $effectif ?? '--' }}</div>
                <div class="sr-stat-label">Rang</div>
            </div>
            <div class="sr-stat sr-stat--warning">
                <div class="sr-stat-icon"><i class="fas fa-medal"></i></div>
                <div class="sr-stat-value">{{ $mention_generale ?? '--' }}</div>
                <div class="sr-stat-label">Mention</div>
            </div>
        </div>

        {{-- ══ Results table (sr-table-card) ══ --}}
        <div class="sr-table-card sr-animate sr-animate-delay-2">
            <div class="sr-table-header">
                <div class="sr-table-header-left">
                    <i class="fas fa-table"></i>
                    <h3>Résultats détaillés</h3>
                </div>
                <span class="sr-table-count">
                    {{ $resultats_ues->count() }} UE{{ $resultats_ues->count() > 1 ? 's' : '' }}
                </span>
            </div>
            <div class="sr-table-responsive">
                    <table class="sr-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Intitulé</th>
                                <th style="text-align:center;">Moy /20</th>
                                <th style="text-align:center;">Statut</th>
                                <th style="text-align:center;">Mention</th>
                                <th style="text-align:center;">CECT</th>
                                <th style="text-align:center;">Min</th>
                                <th style="text-align:center;">Moy</th>
                                <th style="text-align:center;">Max</th>
                                <th>Enseignant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resultats_ues as $resUE)
                                @php
                                    $ue = $resUE->uniteEnseignement;
                                    $ueMoy = $resUE->moyenne;
                                    $uePass = $ueMoy !== null && (float)$ueMoy >= 10;
                                @endphp
                                {{-- UE row --}}
                                <tr class="bp-ue">
                                    <td><span class="bp-code">{{ $ue->code ?? '' }}</span></td>
                                    <td>{{ $ue->name ?? '' }}</td>
                                    <td style="text-align:center;">
                                        <span class="{{ $uePass ? 'bp-moy--pass' : 'bp-moy--fail' }}" style="font-size:.92rem;">
                                            {{ $ueMoy !== null ? number_format($ueMoy, 2) : '' }}
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <span class="bp-badge bp-badge--{{ strtolower($resUE->statut) }}">{{ $resUE->statut }}</span>
                                    </td>
                                    <td style="text-align:center;">
                                        @if($resUE->mention)
                                            <span class="bp-mention">{{ $resUE->mention }}</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;"><span class="bp-credit">{{ $resUE->credit }}</span></td>
                                    <td style="text-align:center;"><span class="bp-stat-col">{{ $resUE->stat_min !== null ? number_format($resUE->stat_min, 2) : '' }}</span></td>
                                    <td style="text-align:center;"><span class="bp-stat-col">{{ $resUE->stat_moy !== null ? number_format($resUE->stat_moy, 2) : '' }}</span></td>
                                    <td style="text-align:center;"><span class="bp-stat-col">{{ $resUE->stat_max !== null ? number_format($resUE->stat_max, 2) : '' }}</span></td>
                                    <td></td>
                                </tr>
                                {{-- ECUE rows --}}
                                @foreach($resUE->resultatsECUEs as $resECUE)
                                    <tr class="bp-ecue">
                                        <td><span class="bp-code">{{ $resECUE->matiere->code ?? '' }}</span></td>
                                        <td>{{ $resECUE->matiere->name ?? '' }}</td>
                                        <td style="text-align:center;">
                                            <span style="font-weight:600;">{{ $resECUE->moyenne !== null ? number_format($resECUE->moyenne, 2) : '' }}</span>
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td style="text-align:center;"><span class="bp-credit">{{ $resECUE->credit > 0 ? $resECUE->credit : '' }}</span></td>
                                        <td style="text-align:center;"><span class="bp-stat-col">{{ $resECUE->stat_min !== null ? number_format($resECUE->stat_min, 2) : '' }}</span></td>
                                        <td style="text-align:center;"><span class="bp-stat-col">{{ $resECUE->stat_moy !== null ? number_format($resECUE->stat_moy, 2) : '' }}</span></td>
                                        <td style="text-align:center;"><span class="bp-stat-col">{{ $resECUE->stat_max !== null ? number_format($resECUE->stat_max, 2) : '' }}</span></td>
                                        <td><span class="bp-enseignant">{{ $resECUE->enseignant->name ?? '' }}</span></td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
        </div>

        {{-- ══ Decision footer (sr-table-card) ══ --}}
        <div class="sr-table-card sr-animate sr-animate-delay-3">
            <div style="padding:1.25rem 1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <div style="display:flex; align-items:center; gap:.75rem;">
                        <div class="bp-decision-icon"><i class="fas fa-gavel"></i></div>
                        <div>
                            <div style="font-size:.72rem; color:var(--sr-muted); font-weight:700; text-transform:uppercase; letter-spacing:.04em;">Décision du conseil</div>
                            <div style="font-size:1.1rem; font-weight:800; color:var(--sr-primary);">{{ $decision ?? $bulletin->decision_deliberation ?? '--' }}</div>
                        </div>
                    </div>
                    @if($deliberation)
                        <div style="font-size:.82rem; color:var(--sr-muted);">
                            Jury du {{ $deliberation->jury_date?->format('d/m/Y') ?? '' }}
                            @if($deliberation->president_jury)
                                — Président : {{ $deliberation->president_jury }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
