@extends('layouts.app')
@section('title', 'Sessions LMD & Rattrapage')

@push('styles')
<style>
[x-cloak]{display:none !important;}
.rtp-hero{background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb);border-radius:18px;padding:2rem 2.5rem 1.5rem;color:#fff;margin-bottom:1.25rem;}
.rtp-hero-top{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;}
.rtp-hero-left{display:flex;align-items:center;gap:1rem;}
.rtp-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.35rem;color:#fff;}
.rtp-hero h1{margin:0;font-size:1.45rem;font-weight:700;color:#fff;}
.rtp-hero p{margin:0;color:rgba(255,255,255,.7);font-size:.88rem;}
.rtp-btn{padding:.5rem 1rem;border-radius:10px;font-size:.82rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;border:1px solid;}
.rtp-btn--glass{background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.2);}
.rtp-btn--white{background:#fff;color:#0453cb;border-color:transparent;}
.rtp-kpis{display:flex;gap:.75rem;margin-top:1.5rem;flex-wrap:wrap;}
.rtp-kpi{flex:1;min-width:160px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:12px;padding:.9rem 1rem;display:flex;align-items:center;gap:.75rem;}
.rtp-kpi-icon{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;}
.rtp-kpi-value{font-size:1.35rem;font-weight:700;color:#fff;line-height:1;}
.rtp-kpi-label{font-size:.72rem;color:rgba(255,255,255,.65);margin-top:.2rem;text-transform:uppercase;letter-spacing:.5px;}
.rtp-filters{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem 1.25rem;display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1.25rem;align-items:center;}
.rtp-filter{display:flex;align-items:center;gap:.4rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.4rem .65rem;font-size:.82rem;}
.rtp-filter label{color:#64748b;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;}
.rtp-filter select{border:none;background:transparent;outline:none;font-size:.82rem;color:#1e293b;}
.rtp-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;}
.rtp-table{width:100%;border-collapse:separate;border-spacing:0;font-size:.85rem;}
.rtp-table th{background:#f8fafc;color:#475569;font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;padding:.7rem .9rem;text-align:left;border-bottom:1px solid #e2e8f0;}
.rtp-table td{padding:.85rem .9rem;border-bottom:1px solid #f1f5f9;}
.rtp-table tbody tr:hover{background:#f8fafc;}
.rtp-chip{display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .55rem;border-radius:6px;font-size:.7rem;font-weight:700;text-transform:uppercase;}
.rtp-chip--normale{background:rgba(4,83,203,.10);color:#0453cb;}
.rtp-chip--rattrapage{background:rgba(245,158,11,.10);color:#b45309;}
.rtp-chip--extra{background:rgba(100,116,139,.10);color:#475569;}
.rtp-status{display:inline-flex;padding:.18rem .5rem;border-radius:5px;font-size:.68rem;font-weight:700;text-transform:uppercase;}
.rtp-status--draft{background:rgba(100,116,139,.10);color:#475569;}
.rtp-status--planned{background:rgba(4,83,203,.10);color:#0453cb;}
.rtp-status--in_progress{background:rgba(245,158,11,.10);color:#b45309;}
.rtp-status--completed{background:rgba(16,185,129,.10);color:#047857;}
.rtp-status--published{background:rgba(4,83,203,.18);color:#0453cb;font-weight:800;}
.rtp-status--archived{background:rgba(100,116,139,.10);color:#475569;}
.rtp-empty{padding:3rem 1.5rem;text-align:center;color:#64748b;}
.rtp-empty i{font-size:2.5rem;color:#cbd5e1;margin-bottom:1rem;}
@media(max-width:768px){.rtp-hero{padding:1.25rem 1rem;}.rtp-hero-top{flex-direction:column;align-items:flex-start;}}
</style>
@endpush

@section('content')
<div x-data="rattrapageIndex()" x-init="init()">

<div class="rtp-hero">
    <div class="rtp-hero-top">
        <div class="rtp-hero-left">
            <div class="rtp-hero-icon"><i class="fas fa-rotate-right"></i></div>
            <div>
                <h1>Sessions & Rattrapage LMD</h1>
                <p>Année universitaire <strong>{{ $annee->libelle ?? '—' }}</strong> · workflow UEMOA 2 sessions</p>
            </div>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            @can('lmd.rattrapage.manage')
            <button type="button" @click="modalCreate=true" class="rtp-btn rtp-btn--white">
                <i class="fas fa-plus"></i> Nouvelle session
            </button>
            @endcan
        </div>
    </div>

    <div class="rtp-kpis">
        <div class="rtp-kpi"><div class="rtp-kpi-icon"><i class="fas fa-clipboard-check"></i></div>
            <div><div class="rtp-kpi-value">{{ $kpis['normales'] }}</div><div class="rtp-kpi-label">Sessions normales</div></div></div>
        <div class="rtp-kpi"><div class="rtp-kpi-icon"><i class="fas fa-rotate-right"></i></div>
            <div><div class="rtp-kpi-value">{{ $kpis['rattrapages'] }}</div><div class="rtp-kpi-label">Rattrapages</div></div></div>
        <div class="rtp-kpi"><div class="rtp-kpi-icon"><i class="fas fa-spinner"></i></div>
            <div><div class="rtp-kpi-value">{{ $kpis['en_cours'] }}</div><div class="rtp-kpi-label">En cours</div></div></div>
        <div class="rtp-kpi"><div class="rtp-kpi-icon"><i class="fas fa-flag-checkered"></i></div>
            <div><div class="rtp-kpi-value">{{ $kpis['publiees'] }}</div><div class="rtp-kpi-label">Publiées</div></div></div>
    </div>
</div>

<form method="GET" class="rtp-filters">
    <div class="rtp-filter">
        <label>Année</label>
        <select name="annee_universitaire_id" onchange="this.form.submit()">
            @foreach($annees as $a)<option value="{{ $a->id }}" @selected($a->id == $annee->id)>{{ $a->libelle }}</option>@endforeach
        </select>
    </div>
</form>

<div class="rtp-card">
    @if($sessions->isEmpty())
    <div class="rtp-empty">
        <i class="fas fa-rotate-right"></i>
        <h3 style="margin:.25rem 0;color:#1e293b;">Aucune session</h3>
        <p style="margin:0;">Créez une session normale, puis lancez le workflow rattrapage à la fin.</p>
    </div>
    @else
    <table class="rtp-table">
        <thead><tr>
            <th>Libellé</th><th>Type</th><th>Parcours</th><th>Semestre</th><th>Dates</th><th>Statut</th><th></th>
        </tr></thead>
        <tbody>
        @foreach($sessions as $s)
        <tr>
            <td style="font-weight:600;color:#0453cb;">{{ $s->libelle }}</td>
            <td><span class="rtp-chip rtp-chip--{{ $s->type }}">{{ $s->type }}</span></td>
            <td>{{ $s->parcours->name ?? '—' }}</td>
            <td>{{ $s->semestre ? 'S'.$s->semestre : '—' }}</td>
            <td>{{ optional($s->date_debut)->format('d/m/Y') }} — {{ optional($s->date_fin)->format('d/m/Y') }}</td>
            <td><span class="rtp-status rtp-status--{{ $s->status }}">{{ str_replace('_',' ',$s->status) }}</span></td>
            <td>
                <a href="{{ route('esbtp.lmd.rattrapage.show', $s) }}" style="padding:.3rem .7rem;border-radius:6px;background:#f1f5f9;color:#0453cb;text-decoration:none;font-size:.78rem;font-weight:600;">
                    <i class="fas fa-eye"></i>
                </a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @if($sessions->hasPages())
        <div style="padding:1rem 1.25rem;border-top:1px solid #e2e8f0;">{{ $sessions->links() }}</div>
    @endif
    @endif
</div>

{{-- Modal Create --}}
<div x-show="modalCreate" x-cloak @keydown.escape.window="modalCreate=false"
    style="position:fixed;inset:0;background:rgba(15,23,42,.65);z-index:1050;display:flex;align-items:center;justify-content:center;padding:1rem;">
    <div @click.outside="modalCreate=false" style="background:#fff;border-radius:16px;padding:1.5rem;max-width:520px;width:100%;box-shadow:0 25px 60px rgba(0,0,0,.3);">
        <h2 style="margin:0 0 1rem;color:#0453cb;font-size:1.15rem;"><i class="fas fa-plus-circle"></i> Nouvelle session</h2>
        <form method="POST" action="{{ route('esbtp.lmd.rattrapage.store') }}" @submit="modalCreate=false">
            @csrf
            <input type="hidden" name="annee_universitaire_id" value="{{ $annee->id }}">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div>
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Type *</label>
                    <select name="type" required style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;">
                        <option value="normale">Normale</option>
                        <option value="rattrapage">Rattrapage</option>
                        <option value="extra">Extra</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Semestre</label>
                    <select name="semestre" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;">
                        <option value="">—</option>
                        @foreach([1,2,3,4,5,6] as $sem)<option value="{{ $sem }}">S{{ $sem }}</option>@endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Parcours</label>
                    <select name="parcours_id" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;">
                        <option value="">— Tous parcours —</option>
                        @foreach($parcours as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Libellé *</label>
                    <input type="text" name="libelle" required maxlength="255" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;" placeholder="Ex: Session normale S1 2025-2026">
                </div>
                <div>
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Date début</label>
                    <input type="date" name="date_debut" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;">Date fin</label>
                    <input type="date" name="date_fin" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;">
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
function rattrapageIndex() {
    return {
        modalCreate: false,
        init(){}
    };
}
</script>
@endpush

@endsection
