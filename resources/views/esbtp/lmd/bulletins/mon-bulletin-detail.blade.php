@extends('layouts.app')

@section('title', 'Mon bulletin — Semestre ' . ($semestre ?? '') . ' | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ============================================================
   MON BULLETIN — detail (systeme LMD) — namespace mbd-*
   ============================================================ */
.mbd-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4, 83, 203, .18);
}
.mbd-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.mbd-hero-left { display: flex; align-items: center; gap: 1rem; }
.mbd-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.mbd-hero h1 { font-size: 1.4rem; font-weight: 700; color: #fff; margin: 0; }
.mbd-hero p { color: rgba(255, 255, 255, .72); font-size: .87rem; margin: 0; }
.mbd-btn-back {
    display: inline-flex; align-items: center; gap: .45rem;
    background: rgba(255, 255, 255, .15);
    border: 1px solid rgba(255, 255, 255, .2);
    border-radius: 10px; padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600; color: #fff; text-decoration: none;
}
.mbd-btn-back:hover { background: rgba(255, 255, 255, .25); color: #fff; }

.mbd-info-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: .75rem; margin-top: 1.5rem;
}
.mbd-info {
    background: rgba(255, 255, 255, .1);
    border: 1px solid rgba(255, 255, 255, .15);
    border-radius: 12px; padding: .7rem .9rem;
}
.mbd-info-label {
    font-size: .66rem; font-weight: 700; letter-spacing: .5px;
    text-transform: uppercase; color: rgba(255, 255, 255, .65);
}
.mbd-info-value { font-size: .9rem; font-weight: 700; color: #fff; margin-top: .15rem; }

.mbd-kpis {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: .9rem; margin-bottom: 1.25rem;
}
.mbd-kpi {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    padding: 1rem 1.15rem;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
}
.mbd-kpi-label {
    font-size: .66rem; font-weight: 700; letter-spacing: .5px;
    text-transform: uppercase; color: #64748b;
}
.mbd-kpi-value { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin-top: .25rem; }
.mbd-kpi-value--ok { color: #10b981; }
.mbd-kpi-value--ko { color: #dc2626; }
.mbd-kpi-unit { font-size: .78rem; font-weight: 500; color: #64748b; }

.mbd-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
    margin-bottom: 1.25rem; overflow: hidden;
}
.mbd-card-head {
    display: flex; align-items: center; justify-content: space-between;
    gap: .75rem; padding: 1rem 1.35rem;
    border-bottom: 1px solid #eef2f7;
}
.mbd-card-head h3 {
    font-size: .98rem; font-weight: 700; color: #1e293b; margin: 0;
    display: flex; align-items: center; gap: .55rem;
}
.mbd-card-head h3 i { color: #0453cb; }
.mbd-card-count { font-size: .75rem; font-weight: 600; color: #64748b; }
.mbd-table-wrap { overflow-x: auto; }
.mbd-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.mbd-table th {
    text-align: left; padding: .6rem .8rem;
    font-size: .68rem; font-weight: 700; letter-spacing: .4px;
    text-transform: uppercase; color: #64748b;
    background: #f8fafc; border-bottom: 1px solid #eef2f7;
    white-space: nowrap;
}
.mbd-table td { padding: .6rem .8rem; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.mbd-row-ue td { background: rgba(4, 83, 203, .04); font-weight: 700; }
.mbd-row-ecue td { color: #475569; }
.mbd-row-ecue td:first-child { padding-left: 1.6rem; }
.mbd-code {
    font-family: 'Courier New', monospace; font-size: .72rem; font-weight: 700;
    color: #0453cb; background: rgba(4, 83, 203, .08);
    padding: .12rem .45rem; border-radius: 5px; white-space: nowrap;
}
.mbd-num { text-align: center; white-space: nowrap; }
.mbd-moy--ok { color: #10b981; font-weight: 700; }
.mbd-moy--ko { color: #dc2626; font-weight: 700; }
.mbd-tag {
    display: inline-block; padding: .18rem .5rem; border-radius: 6px;
    font-size: .68rem; font-weight: 700;
    background: rgba(4, 83, 203, .1); color: #0453cb;
    border: 1px solid rgba(4, 83, 203, .2);
}
.mbd-decision {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem; padding: 1.15rem 1.35rem;
}
.mbd-decision-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center; color: #fff;
}
.mbd-decision-label {
    font-size: .68rem; font-weight: 700; letter-spacing: .5px;
    text-transform: uppercase; color: #64748b;
}
.mbd-decision-value { font-size: 1.05rem; font-weight: 800; color: #0453cb; }
.mbd-note { font-size: .8rem; color: #64748b; padding: 0 1.35rem 1.15rem; }

@media (max-width: 768px) {
    .mbd-hero { padding: 1.5rem 1.25rem; }
}
</style>
@endpush

@section('content')
@php
    $mbdMoy = $moyenne_generale === null ? null : (float) $moyenne_generale;
    $mbdMoyOk = $mbdMoy !== null && $mbdMoy >= 10;
@endphp

<div class="dashboard-acasi">
    <div class="main-content">

        <div class="mbd-hero">
            <div class="mbd-hero-top">
                <div class="mbd-hero-left">
                    <div class="mbd-hero-icon"><i class="fas fa-file-alt"></i></div>
                    <div>
                        <h1>Bulletin — Semestre {{ $semestre }}</h1>
                        <p>
                            {{ $etudiant->nom ?? '' }} {{ $etudiant->prenoms ?? '' }}
                            @if($classe?->name) — {{ $classe->name }} @endif
                            @if($annee?->display_name) — {{ $annee->display_name }} @endif
                        </p>
                    </div>
                </div>
                <a href="{{ route('esbtp.mon-bulletin.index') }}" class="mbd-btn-back">
                    <i class="fas fa-arrow-left"></i>Mes bulletins
                </a>
            </div>

            <div class="mbd-info-grid">
                @foreach(($bulletin_fields ?? []) as $field)
                    @if(($field['show'] ?? false) && ($field['value'] ?? null))
                        <div class="mbd-info">
                            <div class="mbd-info-label">{{ $field['label'] }}</div>
                            <div class="mbd-info-value">{{ $field['value'] }}</div>
                        </div>
                    @endif
                @endforeach
                @if($niveau)
                    <div class="mbd-info">
                        <div class="mbd-info-label">Niveau</div>
                        <div class="mbd-info-value">{{ $niveau }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="mbd-kpis">
            <div class="mbd-kpi">
                <div class="mbd-kpi-label">Moyenne générale</div>
                <div class="mbd-kpi-value {{ $mbdMoy === null ? '' : ($mbdMoyOk ? 'mbd-kpi-value--ok' : 'mbd-kpi-value--ko') }}">
                    @if($mbdMoy !== null)
                        {{ number_format($mbdMoy, 2) }}<span class="mbd-kpi-unit">/20</span>
                    @else
                        <span class="mbd-kpi-unit">—</span>
                    @endif
                </div>
            </div>
            <div class="mbd-kpi">
                <div class="mbd-kpi-label">Crédits capitalisés</div>
                <div class="mbd-kpi-value">
                    {{ $credits_capitalises }}<span class="mbd-kpi-unit">/{{ $credits_totaux ?: '—' }}</span>
                </div>
            </div>
            <div class="mbd-kpi">
                <div class="mbd-kpi-label">Rang</div>
                <div class="mbd-kpi-value">
                    {{ $rang ?? '—' }}<span class="mbd-kpi-unit">/{{ $effectif ?? '—' }}</span>
                </div>
            </div>
            <div class="mbd-kpi">
                <div class="mbd-kpi-label">Mention</div>
                <div class="mbd-kpi-value">{{ $mention_generale ?? '—' }}</div>
            </div>
        </div>

        <div class="mbd-card">
            <div class="mbd-card-head">
                <h3><i class="fas fa-table"></i>Résultats détaillés</h3>
                <span class="mbd-card-count">
                    {{ $resultats_ues->count() }} unité{{ $resultats_ues->count() > 1 ? 's' : '' }} d'enseignement
                </span>
            </div>
            <div class="mbd-table-wrap">
                <table class="mbd-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Intitulé</th>
                            <th class="mbd-num">Moyenne</th>
                            <th class="mbd-num">Statut</th>
                            <th class="mbd-num">Mention</th>
                            <th class="mbd-num">Crédits</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resultats_ues as $resUE)
                            @php
                                $ue = $resUE->uniteEnseignement;
                                $ueMoy = $resUE->moyenne === null ? null : (float) $resUE->moyenne;
                            @endphp
                            <tr class="mbd-row-ue">
                                <td><span class="mbd-code">{{ $ue->code ?? '' }}</span></td>
                                <td>{{ $ue->name ?? '' }}</td>
                                <td class="mbd-num">
                                    <span class="{{ $ueMoy !== null && $ueMoy >= 10 ? 'mbd-moy--ok' : 'mbd-moy--ko' }}">
                                        {{ $ueMoy !== null ? number_format($ueMoy, 2) : '—' }}
                                    </span>
                                </td>
                                <td class="mbd-num"><span class="mbd-tag">{{ $resUE->statut }}</span></td>
                                <td class="mbd-num">{{ $resUE->mention ?: '—' }}</td>
                                <td class="mbd-num">{{ $resUE->credit }}</td>
                            </tr>
                            @foreach($resUE->resultatsECUEs as $resECUE)
                                @php
                                    $ecueMoy = $resECUE->moyenne === null ? null : (float) $resECUE->moyenne;
                                @endphp
                                <tr class="mbd-row-ecue">
                                    <td><span class="mbd-code">{{ $resECUE->matiere->code ?? '' }}</span></td>
                                    <td>{{ $resECUE->matiere->name ?? '' }}</td>
                                    <td class="mbd-num">{{ $ecueMoy !== null ? number_format($ecueMoy, 2) : '—' }}</td>
                                    <td class="mbd-num">—</td>
                                    <td class="mbd-num">—</td>
                                    <td class="mbd-num">{{ $resECUE->credit > 0 ? $resECUE->credit : '—' }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; color:#64748b; padding:1.5rem;">
                                    Aucun résultat détaillé n'est enregistré sur ce bulletin.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mbd-card">
            <div class="mbd-decision">
                <div style="display:flex; align-items:center; gap:.75rem;">
                    <div class="mbd-decision-icon"><i class="fas fa-gavel"></i></div>
                    <div>
                        <div class="mbd-decision-label">Décision du conseil</div>
                        <div class="mbd-decision-value">{{ $decision ?? $bulletin->decision_deliberation ?? '—' }}</div>
                    </div>
                </div>
                @if($deliberation)
                    <div style="font-size:.82rem; color:#64748b;">
                        Jury du {{ $deliberation->jury_date?->format('d/m/Y') ?? '' }}
                        @if($deliberation->president_jury)
                            — Président : {{ $deliberation->president_jury }}
                        @endif
                    </div>
                @endif
            </div>
            <div class="mbd-note">
                Pour obtenir un exemplaire officiel de ce bulletin, adressez-vous à la scolarité.
            </div>
        </div>

    </div>
</div>
@endsection
