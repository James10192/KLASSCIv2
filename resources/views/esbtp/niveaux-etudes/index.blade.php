@extends('layouts.app')

@section('title', 'Niveaux d\'études')

@push('styles')
<style>
.ne-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.75rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
.ne-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.ne-hero-left { display: flex; align-items: center; gap: 1rem; }
.ne-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; color: #fff; }
.ne-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.ne-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
.ne-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.ne-kpi { flex: 1; min-width: 130px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem; }
.ne-kpi-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .9rem; color: #fff; }
.ne-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
.ne-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }
.ne-btn-white { background: #fff; color: #0453cb; border: none; border-radius: 10px; padding: .55rem 1.1rem; font-size: .84rem; font-weight: 600; display: inline-flex; align-items: center; gap: .45rem; text-decoration: none; transition: all .2s; box-shadow: 0 2px 8px rgba(0,0,0,.12); }
.ne-btn-white:hover { background: #eff6ff; color: #0453cb; transform: translateY(-1px); }
.ne-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .7rem; border-radius: 20px; font-size: .76rem; font-weight: 600; }
.ne-chip-lmd { background: #eff6ff; color: #0453cb; border: 1px solid #bfdbfe; }
.ne-chip-bts { background: #e0eaff; color: #3b7ddb; border: 1px solid #c7d9f8; }
.ne-chip-other { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
.ne-chip-null { background: #fff7ed; color: #b45309; border: 1px solid #fed7aa; }
.ne-table { border-collapse: collapse; width: 100%; }
.ne-table th { padding: .75rem 1rem; font-size: .72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; border-bottom: 2px solid #e2e8f0; background: #f8fafc; white-space: nowrap; }
.ne-table td { padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.ne-table tbody tr:hover { background: #f8fafc; }
.ne-table tbody tr:last-child td { border-bottom: none; }
.ne-row-avatar { width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; }
.ne-name { font-weight: 600; color: #1e293b; font-size: .9rem; }
.ne-code { font-size: .76rem; color: #64748b; margin-top: .1rem; }
.ne-status-on { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .65rem; border-radius:20px; font-size:.74rem; font-weight:600; background:#d1fae5; color:#065f46; }
.ne-status-off { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .65rem; border-radius:20px; font-size:.74rem; font-weight:600; background:#fee2e2; color:#991b1b; }
.ne-actions { display:flex; gap:.35rem; justify-content:flex-end; }
.ne-action-btn { width:32px; height:32px; border-radius:8px; border:1px solid #e2e8f0; background:#fff; color:#64748b; display:inline-flex; align-items:center; justify-content:center; font-size:.8rem; text-decoration:none; transition:all .15s; cursor:pointer; }
.ne-action-btn:hover { background:#f8fafc; color:#0453cb; border-color:#bfdbfe; }
.ne-action-danger:hover { background:#fee2e2; border-color:#fca5a5; color:#dc2626; }
.ne-empty { text-align:center; padding:3rem 1rem; }
.ne-empty i { font-size:2.5rem; color:#cbd5e1; margin-bottom:1rem; display:block; }
.ne-empty h5 { color:#64748b; margin-bottom:.5rem; }
.ne-empty p { color:#94a3b8; font-size:.88rem; }
@@media (max-width: 768px) {
    .ne-hero { padding:1.5rem 1.25rem 1.25rem; }
    .ne-kpis { gap:.5rem; }
    .ne-kpi { min-width: calc(50% - .25rem); }
    .ne-hero h1 { font-size:1.2rem; }
}
</style>
@endpush

@section('content')
<div class="main-content">

<div class="ne-hero">
    <div class="ne-hero-top">
        <div class="ne-hero-left">
            <div class="ne-hero-icon"><i class="fas fa-layer-group"></i></div>
            <div>
                <h1>Niveaux d'études</h1>
                <p>Configuration des niveaux et cycles d'études de l'établissement</p>
            </div>
        </div>
        <a href="{{ route('esbtp.niveaux-etudes.create') }}" class="ne-btn-white">
            <i class="fas fa-plus-circle"></i> Nouveau niveau
        </a>
    </div>
    <div class="ne-kpis">
        <div class="ne-kpi">
            <div class="ne-kpi-icon"><i class="fas fa-layer-group"></i></div>
            <div><div class="ne-kpi-value">{{ $totalCount }}</div><div class="ne-kpi-label">Total</div></div>
        </div>
        <div class="ne-kpi">
            <div class="ne-kpi-icon"><i class="fas fa-graduation-cap"></i></div>
            <div><div class="ne-kpi-value">{{ $lmdCount }}</div><div class="ne-kpi-label">Cycle LMD</div></div>
        </div>
        <div class="ne-kpi">
            <div class="ne-kpi-icon"><i class="fas fa-briefcase"></i></div>
            <div><div class="ne-kpi-value">{{ $btsCount }}</div><div class="ne-kpi-label">Cycle BTS</div></div>
        </div>
        @if($untypedCount > 0)
        <div class="ne-kpi" style="border-color:rgba(251,191,36,.4); background:rgba(251,191,36,.12);">
            <div class="ne-kpi-icon" style="background:rgba(251,191,36,.2);"><i class="fas fa-exclamation-triangle" style="color:#fbbf24;"></i></div>
            <div><div class="ne-kpi-value" style="color:#fbbf24;">{{ $untypedCount }}</div><div class="ne-kpi-label">Non typés</div></div>
        </div>
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-dismissible fade show mb-3" style="background:rgba(16,185,129,.1); border:1px solid #10b981; border-radius:12px; padding:.85rem 1rem;" role="alert">
    <i class="fas fa-check-circle" style="color:#10b981;"></i>
    <span style="color:#065f46; font-weight:600; margin-left:.4rem;">{{ session('success') }}</span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="main-card">
    <div style="padding:1.1rem 1.5rem .75rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; border-bottom:1px solid #f1f5f9;">
        <span style="font-weight:700; color:#1e293b; font-size:.95rem;">
            <i class="fas fa-list" style="color:#0453cb; margin-right:.5rem;"></i>
            Liste des niveaux
        </span>
        <form method="GET" action="{{ route('esbtp.niveaux-etudes.index') }}" style="display:flex; flex-wrap:wrap; gap:.5rem; align-items:center;">
            <x-au-select
                name="type"
                :value="request('type')"
                icon="fa-tag"
                placeholder="Tous les types"
                :options="['BTS' => 'BTS', 'Bachelor' => 'Bachelor', 'Licence' => 'Licence', 'Master' => 'Master', 'Doctorat' => 'Doctorat', 'Diplôme' => 'Diplôme', 'Certificat' => 'Certificat']"
                onchange="this.form.submit()" />
            <x-au-select
                name="status"
                :value="request('status')"
                icon="fa-toggle-on"
                placeholder="Tous statuts"
                :options="['active' => 'Actifs', 'inactive' => 'Inactifs']"
                onchange="this.form.submit()" />
            @if(request()->hasAny(['type', 'status']))
            <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="btn-acasi secondary" style="padding:.45rem .85rem; font-size:.82rem;">
                <i class="fas fa-times"></i> Réinitialiser
            </a>
            @endif
        </form>
    </div>

    @if($niveauxEtudes->isEmpty())
    <div class="ne-empty">
        <i class="fas fa-graduation-cap"></i>
        <h5>Aucun niveau d'étude trouvé</h5>
        <p>Aucun niveau ne correspond aux filtres sélectionnés.</p>
        <a href="{{ route('esbtp.niveaux-etudes.create') }}" class="btn-acasi primary mt-2">
            <i class="fas fa-plus-circle"></i> Créer le premier niveau
        </a>
    </div>
    @else
    <div class="table-responsive">
        <table class="ne-table">
            <thead>
                <tr>
                    <th style="padding-left:1.5rem;">Niveau</th>
                    <th>Type de formation</th>
                    <th>Année</th>
                    <th>Classes</th>
                    <th>Statut</th>
                    <th style="text-align:right; padding-right:1.5rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($niveauxEtudes as $niveau)
                @php
                    $nType = $niveau->type;
                    $lmdTypes = ['Licence', 'Master', 'Doctorat', 'Bachelor'];
                    if (!$nType) {
                        $chipCls = 'ne-chip-null'; $chipIco = 'fa-exclamation-triangle'; $chipLbl = 'Non typé';
                    } elseif (in_array($nType, $lmdTypes)) {
                        $chipCls = 'ne-chip-lmd'; $chipIco = 'fa-graduation-cap'; $chipLbl = $nType;
                    } elseif ($nType === 'BTS') {
                        $chipCls = 'ne-chip-bts'; $chipIco = 'fa-briefcase'; $chipLbl = 'BTS';
                    } else {
                        $chipCls = 'ne-chip-other'; $chipIco = 'fa-tag'; $chipLbl = $nType;
                    }
                    $initials = mb_strtoupper(mb_substr($niveau->code ?: $niveau->name, 0, 2, 'UTF-8'), 'UTF-8');
                @endphp
                <tr>
                    <td style="padding-left:1.5rem;">
                        <div style="display:flex; align-items:center; gap:.75rem;">
                            <div class="ne-row-avatar">{{ $initials }}</div>
                            <div>
                                <div class="ne-name">{{ $niveau->name }}</div>
                                <div class="ne-code">{{ $niveau->libelle ?: $niveau->code }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="ne-chip {{ $chipCls }}">
                            <i class="fas {{ $chipIco }}" style="font-size:.68rem;"></i>
                            {{ $chipLbl }}
                        </span>
                    </td>
                    <td>
                        @if($niveau->year)
                        <span style="font-size:.85rem; color:#1e293b; font-weight:500;">Année {{ $niveau->year }}</span>
                        @else
                        <span style="color:#94a3b8; font-size:.82rem;">—</span>
                        @endif
                    </td>
                    <td>
                        <span style="font-size:.85rem; font-weight:600; color:#1e293b;">{{ $niveau->classes ? $niveau->classes->count() : 0 }}</span>
                        <span style="font-size:.76rem; color:#64748b; margin-left:.25rem;">classe(s)</span>
                    </td>
                    <td>
                        @if($niveau->is_active)
                        <span class="ne-status-on"><i class="fas fa-circle" style="font-size:.5rem;"></i> Actif</span>
                        @else
                        <span class="ne-status-off"><i class="fas fa-circle" style="font-size:.5rem;"></i> Inactif</span>
                        @endif
                    </td>
                    <td style="text-align:right; padding-right:1.5rem;">
                        <div class="ne-actions">
                            <a href="{{ route('esbtp.niveaux-etudes.show', $niveau) }}" class="ne-action-btn" title="Voir"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('esbtp.niveaux-etudes.edit', $niveau) }}" class="ne-action-btn" title="Modifier"><i class="fas fa-edit"></i></a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

</div>
@endsection
