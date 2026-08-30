@extends('layouts.app')
@section('title', 'Étudiants ajournés')

@push('styles')
<style>
[x-cloak]{display:none !important;}
.juy-hero{background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb);border-radius:18px;padding:2rem 2.5rem 1.5rem;color:#fff;margin-bottom:1.25rem;}
.juy-hero-top{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;}
.juy-hero-left{display:flex;align-items:center;gap:1rem;}
.juy-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.35rem;color:#fff;}
.juy-hero h1{margin:0;font-size:1.45rem;font-weight:700;color:#fff;}
.juy-hero p{margin:0;color:rgba(255,255,255,.7);font-size:.88rem;}
.juy-kpis{display:flex;gap:.75rem;margin-top:1.5rem;flex-wrap:wrap;}
.juy-kpi{flex:1;min-width:160px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:12px;padding:.9rem 1rem;display:flex;align-items:center;gap:.75rem;}
.juy-kpi-icon{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;}
.juy-kpi-value{font-size:1.35rem;font-weight:700;color:#fff;line-height:1;}
.juy-kpi-label{font-size:.72rem;color:rgba(255,255,255,.65);margin-top:.2rem;text-transform:uppercase;letter-spacing:.5px;}
.juy-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;}
.juy-table{width:100%;border-collapse:separate;border-spacing:0;font-size:.85rem;}
.juy-table th{background:#f8fafc;color:#475569;font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;padding:.7rem .9rem;text-align:left;border-bottom:1px solid #e2e8f0;}
.juy-table td{padding:.85rem .9rem;border-bottom:1px solid #f1f5f9;vertical-align:top;}
.juy-table tbody tr:hover{background:#f8fafc;}
.juy-empty{padding:3rem 1.5rem;text-align:center;color:#64748b;}
.juy-empty i{font-size:2.5rem;color:#cbd5e1;margin-bottom:1rem;display:block;}
.juy-chip{display:inline-flex;padding:.15rem .5rem;border-radius:5px;font-size:.68rem;font-weight:700;background:rgba(220,38,38,.10);color:#b91c1c;margin:.1rem .15rem .1rem 0;}
.juy-muted{color:#64748b;font-size:.78rem;}
</style>
@endpush

@section('content')
<div class="juy-hero">
    <div class="juy-hero-top">
        <div class="juy-hero-left">
            <div class="juy-hero-icon"><i class="fas fa-user-times"></i></div>
            <div>
                <h1>Étudiants ajournés</h1>
                <p>Année <strong>{{ $annee->name ?? $annee->display_name ?? '—' }}</strong> · UE et IE (ECUE) non validées</p>
            </div>
        </div>
        @can('lmd.jury.view')
        <a href="{{ route('esbtp.lmd.jurys.index') }}" class="juy-btn" style="padding:.5rem 1rem;border-radius:10px;font-size:.82rem;font-weight:600;background:#fff;color:#0453cb;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;">
            <i class="fas fa-gavel"></i> Jurys
        </a>
        @endcan
    </div>
    <div class="juy-kpis">
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-users"></i></div>
            <div><div class="juy-kpi-value">{{ $kpis['total'] }}</div><div class="juy-kpi-label">Ajournés</div></div></div>
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-layer-group"></i></div>
            <div><div class="juy-kpi-value">{{ $kpis['ues'] }}</div><div class="juy-kpi-label">UE non validées</div></div></div>
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-book"></i></div>
            <div><div class="juy-kpi-value">{{ $kpis['ie'] }}</div><div class="juy-kpi-label">IE &lt; 10</div></div></div>
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-sitemap"></i></div>
            <div><div class="juy-kpi-value">{{ $kpis['parcours'] }}</div><div class="juy-kpi-label">Parcours</div></div></div>
    </div>
</div>

@include('esbtp.lmd.partials.premium-filters', [
    'action' => route('esbtp.lmd.ajournes.index'),
    'annees' => $annees,
    'annee' => $annee,
    'classes' => $classes,
    'parcours' => $parcours,
    'showSearch' => true,
])

<div class="juy-card">
    @if($lignes->isEmpty())
    <div class="juy-empty">
        <i class="fas fa-user-check"></i>
        <h3 style="margin:.25rem 0;color:#1e293b;">Aucun ajourné</h3>
        <p style="margin:0;">Aucun étudiant ajourné pour cette année. Les décisions de jury apparaîtront ici.</p>
    </div>
    @else
    <table class="juy-table">
        <thead>
            <tr>
                <th>Étudiant</th>
                <th>Parcours / classe</th>
                <th>UE non validées</th>
                <th>IE (ECUE) &lt; 10</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($lignes as $ligne)
            <tr>
                <td>
                    <div style="font-weight:600;color:#0453cb;">{{ $ligne['etudiant']->nom ?? '' }} {{ $ligne['etudiant']->prenoms ?? '' }}</div>
                    <div class="juy-muted">{{ $ligne['etudiant']->matricule ?? '—' }}</div>
                </td>
                <td>
                    {{ $ligne['jury']->parcours->name ?? '—' }}
                    @if($ligne['jury']->classe)
                        <div class="juy-muted">{{ $ligne['jury']->classe->name }}</div>
                    @endif
                </td>
                <td>
                    @forelse ($ligne['ues_non_validees'] as $ue)
                        <div><span class="juy-chip">{{ $ue['statut'] }}</span> {{ $ue['ue'] }} <span class="juy-muted">({{ $ue['moyenne'] }})</span></div>
                    @empty
                        <span class="juy-muted">—</span>
                    @endforelse
                </td>
                <td>
                    @php $ies = collect($ligne['ues_non_validees'])->pluck('ie')->flatten()->filter(); @endphp
                    {{ $ies->isEmpty() ? '—' : $ies->implode(', ') }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
