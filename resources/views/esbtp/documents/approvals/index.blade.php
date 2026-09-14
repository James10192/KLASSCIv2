@extends('layouts.app')

@section('title', "File d'approbation - KLASSCI")

@push('styles')
<style>
    .apv-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
    }
    .apv-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .apv-hero-left { display: flex; align-items: center; gap: 1rem; }
    .apv-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .apv-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .apv-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
    .apv-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .apv-kpi {
        flex: 1; min-width: 140px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px; padding: .9rem 1rem;
    }
    .apv-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
    .apv-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    .apv-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        overflow-x: auto;
    }
    .apv-table { width: 100%; border-collapse: collapse; }
    .apv-table th {
        text-align: left; font-size: .7rem; font-weight: 700; color: #64748b;
        text-transform: uppercase; letter-spacing: .5px;
        padding: .85rem 1rem; border-bottom: 1px solid #e2e8f0; white-space: nowrap;
    }
    .apv-table td { padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: .88rem; color: #1e293b; }
    .apv-table tr:last-child td { border-bottom: none; }
    .apv-nom { font-weight: 600; }
    .apv-meta { font-size: .76rem; color: #64748b; margin-top: .1rem; }
    .apv-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .22rem .6rem; border-radius: 6px;
        font-size: .72rem; font-weight: 700;
        background: rgba(4,83,203,.08); color: #0453cb; border: 1px solid rgba(4,83,203,.2);
    }
    .apv-actions { display: flex; gap: .5rem; justify-content: flex-end; }
    .apv-btn {
        border: none; border-radius: 8px; padding: .45rem .85rem;
        font-size: .8rem; font-weight: 600; cursor: pointer;
        display: inline-flex; align-items: center; gap: .4rem;
        transition: all .2s ease;
    }
    .apv-btn--ok { background: #0453cb; color: #fff; }
    .apv-btn--ok:hover { background: #033a8e; }
    .apv-btn--no { background: #fff; color: #dc2626; border: 1px solid #fecaca; }
    .apv-btn--no:hover { background: #fef2f2; }
    .apv-empty { padding: 3rem 1rem; text-align: center; color: #64748b; }
    .apv-empty i { font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: .75rem; }

    @@media (max-width: 768px) {
        .apv-hero { padding: 1.25rem 1rem 1rem; }
        .apv-table th:nth-child(3), .apv-table td:nth-child(3) { display: none; }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="apv-hero">
        <div class="apv-hero-top">
            <div class="apv-hero-left">
                <div class="apv-hero-icon"><i class="fas fa-stamp"></i></div>
                <div>
                    <h1>File d'approbation</h1>
                    <p>Les documents qu'un collègue demande à imprimer et qui attendent votre accord.</p>
                </div>
            </div>
        </div>
        <div class="apv-kpis">
            <div class="apv-kpi">
                <div class="apv-kpi-value">{{ $demandes->total() }}</div>
                <div class="apv-kpi-label">Demande(s) en attente</div>
            </div>
        </div>
    </div>

    {{-- Les messages flash sont rendus par le layout : les redoubler ici les affichait deux fois a chaque clic. --}}

    <div class="apv-card">
        @if($demandes->isEmpty())
            <div class="apv-empty">
                <i class="fas fa-circle-check"></i>
                Aucune demande en attente.<br>
                <span style="font-size:.82rem">Les demandes de certificat, d'attestation ou de bulletin apparaîtront ici.</span>
            </div>
        @else
            <table class="apv-table">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Document</th>
                        <th>Demandé par</th>
                        <th style="text-align:right">Décision</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($demandes as $demande)
                        <tr>
                            <td>
                                <div class="apv-nom">
                                    {{ optional($demande->etudiant)->nom }} {{ optional($demande->etudiant)->prenoms }}
                                </div>
                                <div class="apv-meta">
                                    {{ optional($demande->etudiant)->matricule ?? 'Étudiant #'.$demande->etudiant_id }}
                                </div>
                            </td>
                            <td>
                                <span class="apv-chip">
                                    <i class="fas fa-file-signature"></i>{{ ucfirst($demande->document_type) }}
                                </span>
                            </td>
                            <td>
                                <div>{{ optional($demande->demandeur)->name ?? '—' }}</div>
                                <div class="apv-meta">{{ $demande->created_at?->diffForHumans() }}</div>
                            </td>
                            <td>
                                <div class="apv-actions">
                                    <form method="POST" action="{{ route('esbtp.documents.approvals.approve', $demande) }}">
                                        @csrf
                                        <button class="apv-btn apv-btn--ok" type="submit">
                                            <i class="fas fa-check"></i>Approuver
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('esbtp.documents.approvals.reject', $demande) }}">
                                        @csrf
                                        <button class="apv-btn apv-btn--no" type="submit">
                                            <i class="fas fa-xmark"></i>Refuser
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if($demandes->hasPages())
        <div style="margin-top:1rem">{{ $demandes->links() }}</div>
    @endif
</div>
@endsection
