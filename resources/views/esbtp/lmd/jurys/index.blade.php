@extends('layouts.app')
@section('title', 'Jurys de délibération LMD')

@push('styles')
<style>
[x-cloak]{display:none !important;}
.juy-hero{background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb);border-radius:18px;padding:2rem 2.5rem 1.5rem;color:#fff;margin-bottom:1.25rem;}
.juy-hero-top{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;}
.juy-hero-left{display:flex;align-items:center;gap:1rem;}
.juy-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.35rem;color:#fff;}
.juy-hero h1{margin:0;font-size:1.45rem;font-weight:700;color:#fff;}
.juy-hero p{margin:0;color:rgba(255,255,255,.7);font-size:.88rem;}
.juy-btn{padding:.5rem 1rem;border-radius:10px;font-size:.82rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;border:1px solid;}
.juy-btn--glass{background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.2);}
.juy-btn--white{background:#fff;color:#0453cb;border-color:transparent;}
.juy-kpis{display:flex;gap:.75rem;margin-top:1.5rem;flex-wrap:wrap;}
.juy-kpi{flex:1;min-width:160px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:12px;padding:.9rem 1rem;display:flex;align-items:center;gap:.75rem;}
.juy-kpi-icon{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;}
.juy-kpi-value{font-size:1.35rem;font-weight:700;color:#fff;line-height:1;}
.juy-kpi-label{font-size:.72rem;color:rgba(255,255,255,.65);margin-top:.2rem;text-transform:uppercase;letter-spacing:.5px;}
.juy-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;}
.juy-table{width:100%;border-collapse:separate;border-spacing:0;font-size:.85rem;}
.juy-table th{background:#f8fafc;color:#475569;font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;padding:.7rem .9rem;text-align:left;border-bottom:1px solid #e2e8f0;}
.juy-table td{padding:.85rem .9rem;border-bottom:1px solid #f1f5f9;}
.juy-table tbody tr:hover{background:#f8fafc;}
.juy-status{display:inline-flex;padding:.18rem .5rem;border-radius:5px;font-size:.68rem;font-weight:700;text-transform:uppercase;}
.juy-status--preparation{background:rgba(100,116,139,.10);color:#475569;}
.juy-status--en_cours{background:rgba(245,158,11,.10);color:#b45309;}
.juy-status--clos{background:rgba(4,83,203,.10);color:#0453cb;}
.juy-status--publie{background:rgba(16,185,129,.15);color:#047857;}
.juy-status--archive{background:rgba(100,116,139,.10);color:#475569;}
.juy-empty{padding:3rem 1.5rem;text-align:center;color:#64748b;}
.juy-empty i{font-size:2.5rem;color:#cbd5e1;margin-bottom:1rem;}
.juy-filters{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.85rem 1.25rem;display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;margin-bottom:1.25rem;}
.juy-filter{display:flex;align-items:center;gap:.4rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.4rem .65rem;font-size:.82rem;}
.juy-filter label{color:#64748b;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;}
.juy-filter select{border:none;background:transparent;outline:none;}
.juy-modal{position:fixed;inset:0;background:rgba(15,23,42,.65);z-index:1050;display:flex;align-items:center;justify-content:center;padding:1rem;}
.juy-modal-body{background:#fff;border-radius:16px;padding:1.5rem;max-width:560px;width:100%;box-shadow:0 25px 60px rgba(0,0,0,.3);}
</style>
@endpush

@section('content')
<div x-data="juryIndex()" x-init="init()">

<div class="juy-hero">
    <div class="juy-hero-top">
        <div class="juy-hero-left">
            <div class="juy-hero-icon"><i class="fas fa-gavel"></i></div>
            <div>
                <h1>Jurys de délibération LMD</h1>
                <p>Année <strong>{{ $annee->libelle ?? '—' }}</strong> · workflow UEMOA + PV légal archivé 5 ans</p>
            </div>
        </div>
        <div style="display:flex;gap:.5rem;">
            @can('lmd.jury.preside')
            <button type="button" @click="modalCreate=true" class="juy-btn juy-btn--white">
                <i class="fas fa-plus"></i> Nouveau jury
            </button>
            @endcan
        </div>
    </div>

    <div class="juy-kpis">
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-gavel"></i></div>
            <div><div class="juy-kpi-value">{{ $kpis['total'] }}</div><div class="juy-kpi-label">Total jurys</div></div></div>
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-hourglass-start"></i></div>
            <div><div class="juy-kpi-value">{{ $kpis['preparation'] }}</div><div class="juy-kpi-label">En préparation</div></div></div>
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-scale-balanced"></i></div>
            <div><div class="juy-kpi-value">{{ $kpis['en_cours'] }}</div><div class="juy-kpi-label">En cours</div></div></div>
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-flag-checkered"></i></div>
            <div><div class="juy-kpi-value">{{ $kpis['publies'] }}</div><div class="juy-kpi-label">Publiés</div></div></div>
    </div>
</div>

<form method="GET" class="juy-filters">
    <div class="juy-filter">
        <label>Année</label>
        <select name="annee_universitaire_id" onchange="this.form.submit()">
            @foreach($annees as $a)<option value="{{ $a->id }}" @selected($a->id == $annee->id)>{{ $a->libelle }}</option>@endforeach
        </select>
    </div>
</form>

<div class="juy-card">
    @if($jurys->isEmpty())
    <div class="juy-empty">
        <i class="fas fa-gavel"></i>
        <h3 style="margin:.25rem 0;color:#1e293b;">Aucun jury</h3>
        <p style="margin:0;">Créez un jury de délibération pour démarrer le workflow.</p>
    </div>
    @else
    <table class="juy-table">
        <thead><tr><th>Libellé</th><th>Date</th><th>Parcours/Classe</th><th>Membres</th><th>Statut</th><th>PV</th><th></th></tr></thead>
        <tbody>
        @foreach($jurys as $j)
        <tr>
            <td style="font-weight:600;color:#0453cb;">{{ $j->libelle }}</td>
            <td>{{ optional($j->date_jury)->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $j->parcours?->nom ?? '—' }} @if($j->classe) · {{ $j->classe->name }}@endif</td>
            <td><span style="background:#f1f5f9;padding:.15rem .45rem;border-radius:5px;font-size:.72rem;color:#475569;font-weight:600;">{{ $j->membres->count() }}</span></td>
            <td><span class="juy-status juy-status--{{ $j->status }}">{{ str_replace('_',' ',$j->status) }}</span></td>
            <td>
                @if($j->pv_numero)
                <span style="font-family:'Courier New',monospace;font-size:.72rem;color:#0453cb;font-weight:700;">{{ $j->pv_numero }}</span>
                @else
                <span style="color:#94a3b8;font-size:.78rem;">—</span>
                @endif
            </td>
            <td>
                <a href="{{ route('esbtp.lmd.jurys.show', $j) }}" style="padding:.3rem .7rem;border-radius:6px;background:#f1f5f9;color:#0453cb;text-decoration:none;font-size:.78rem;font-weight:600;">
                    <i class="fas fa-eye"></i>
                </a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @if($jurys->hasPages())
        <div style="padding:1rem 1.25rem;border-top:1px solid #e2e8f0;">{{ $jurys->links() }}</div>
    @endif
    @endif
</div>

{{-- Modal Create --}}
<div class="juy-modal" x-show="modalCreate" x-cloak @keydown.escape.window="modalCreate=false">
    <div class="juy-modal-body" @click.outside="modalCreate=false">
        <h2 style="margin:0 0 1rem;color:#0453cb;font-size:1.15rem;"><i class="fas fa-gavel"></i> Nouveau jury</h2>
        <form method="POST" action="{{ route('esbtp.lmd.jurys.store') }}">
            @csrf
            <input type="hidden" name="annee_universitaire_id" value="{{ $annee->id }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div style="grid-column:1/-1;">
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Libellé *</label>
                    <input type="text" name="libelle" required maxlength="255" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;" placeholder="Ex: Délibération S1 L1 Droit 2025-2026">
                </div>
                <div>
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Parcours</label>
                    <select name="parcours_id" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;">
                        <option value="">— Tous —</option>
                        @foreach($parcours as $p)<option value="{{ $p->id }}">{{ $p->nom }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Classe</label>
                    <select name="classe_id" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;">
                        <option value="">— Toutes —</option>
                        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Session liée</label>
                    <select name="session_id" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;">
                        <option value="">— Aucune —</option>
                        @foreach($sessions as $s)<option value="{{ $s->id }}">{{ $s->libelle }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Semestre</label>
                    <select name="semestre" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;">
                        <option value="">—</option>
                        @foreach([1,2,3,4,5,6,7,8] as $sm)<option value="{{ $sm }}">S{{ $sm }}</option>@endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Date du jury</label>
                    <input type="date" name="date_jury" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;">
                </div>
            </div>
            <div style="margin-top:1.25rem;display:flex;gap:.5rem;justify-content:flex-end;">
                <button type="button" @click="modalCreate=false" style="padding:.5rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#f1f5f9;color:#475569;font-weight:600;cursor:pointer;">Annuler</button>
                <button type="submit" style="padding:.5rem 1rem;border-radius:8px;border:none;background:#0453cb;color:#fff;font-weight:600;cursor:pointer;">Créer</button>
            </div>
        </form>
    </div>
</div>

</div>

@push('scripts')
<script>
function juryIndex(){ return { modalCreate: false, init(){} }; }
</script>
@endpush
@endsection
