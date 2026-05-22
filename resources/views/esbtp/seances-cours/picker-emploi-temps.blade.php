@extends('layouts.app')
@section('title', 'Choisir un emploi du temps')

@push('styles')
<style>
.scep2-hero { background: linear-gradient(135deg, #0a3d8f, #0453cb, #3b7ddb); border-radius: 18px; padding: 1.75rem 2.25rem; color: #fff; margin-bottom: 1.5rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
.scep2-hero h1 { margin: 0; font-size: 1.4rem; }
.scep2-hero p { margin: .3rem 0 0; color: rgba(255,255,255,.78); font-size: .88rem; }

.scep2-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
.scep2-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem; transition: all .15s; text-decoration: none; color: inherit; display: flex; flex-direction: column; gap: .75rem; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.scep2-card:hover { border-color: #0453cb; transform: translateY(-2px); box-shadow: 0 8px 30px rgba(4,83,203,.10); color: inherit; }
.scep2-card-head { display: flex; align-items: center; gap: .65rem; }
.scep2-card-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; }
.scep2-card-title { font-size: 1rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.scep2-card-sub { font-size: .75rem; color: #64748b; margin-top: .15rem; }
.scep2-card-meta { display: flex; gap: .5rem; flex-wrap: wrap; }
.scep2-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .2rem .55rem; border-radius: 5px; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
.scep2-chip--current { background: rgba(16,185,129,.12); color: #047857; }
.scep2-chip--active { background: rgba(4,83,203,.10); color: #0453cb; }
.scep2-chip--lmd { background: rgba(245,158,11,.12); color: #b45309; }
.scep2-chip--bts { background: rgba(94,145,222,.10); color: #3b7ddb; }
.scep2-card-period { font-size: .78rem; color: #475569; margin-top: auto; padding-top: .5rem; border-top: 1px solid #f1f5f9; }
.scep2-card-cta { font-size: .78rem; color: #0453cb; font-weight: 600; display: inline-flex; align-items: center; gap: .3rem; }
.scep2-empty { padding: 3rem 1.5rem; text-align: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; color: #64748b; }
.scep2-empty i { font-size: 2.5rem; color: #cbd5e1; display: block; margin-bottom: 1rem; }
</style>
@endpush

@section('content')
<div class="scep2-hero">
    <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
        <div>
            <h1><i class="fas fa-calendar-plus me-2"></i>Choisir un emploi du temps</h1>
            <p>Sélectionnez la classe pour laquelle vous voulez ajouter une séance · Année <strong>{{ $annee->libelle ?? '—' }}</strong></p>
        </div>
        <a href="{{ route('esbtp.emploi-temps.index') }}"
           style="padding:.55rem 1rem; border-radius:10px; background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.2); text-decoration:none; font-weight:600; font-size:.82rem;">
            <i class="fas fa-arrow-left me-1"></i>Tous les emplois du temps
        </a>
    </div>
</div>

@if($emploisTemps->isEmpty())
    <div class="scep2-empty">
        <i class="fas fa-calendar-xmark"></i>
        <h3 style="font-size:1.1rem; color:#1e293b; margin:0 0 .5rem;">Aucun emploi du temps actif</h3>
        <p style="margin:0;">Créez d'abord un emploi du temps pour une classe.</p>
        <a href="{{ route('esbtp.emploi-temps.create') }}" style="margin-top:1rem; display:inline-block; padding:.55rem 1.2rem; background:#0453cb; color:#fff; border-radius:9px; text-decoration:none; font-weight:600;">
            <i class="fas fa-plus me-1"></i>Nouvel emploi du temps
        </a>
    </div>
@else
    <div class="scep2-grid">
        @foreach($emploisTemps as $edt)
            @php
                $classe = $edt->classe;
                $isLmd = ($classe->systeme_academique ?? '') === 'LMD'
                    || in_array($classe->niveau->type ?? '', ['Licence', 'Master', 'Doctorat'], true);
            @endphp
            <a href="{{ route('esbtp.seances-cours.create', ['emploi_temps_id' => $edt->id]) }}" class="scep2-card">
                <div class="scep2-card-head">
                    <div class="scep2-card-icon"><i class="fas fa-{{ $isLmd ? 'university' : 'graduation-cap' }}"></i></div>
                    <div style="flex:1; min-width:0;">
                        <div class="scep2-card-title">{{ $classe->name ?? '—' }}</div>
                        <div class="scep2-card-sub">
                            {{ $classe->filiere->name ?? '' }}
                            @if($classe->niveau) · {{ $classe->niveau->name }}@endif
                        </div>
                    </div>
                </div>
                <div class="scep2-card-meta">
                    @if($edt->is_current)
                        <span class="scep2-chip scep2-chip--current"><i class="fas fa-circle-dot"></i>En cours</span>
                    @elseif($edt->is_active)
                        <span class="scep2-chip scep2-chip--active">Actif</span>
                    @endif
                    @if($isLmd)
                        <span class="scep2-chip scep2-chip--lmd">LMD</span>
                    @else
                        <span class="scep2-chip scep2-chip--bts">BTS</span>
                    @endif
                    @if($edt->semestre)
                        <span class="scep2-chip" style="background:#f1f5f9; color:#475569;">S{{ $edt->semestre }}</span>
                    @endif
                </div>
                <div class="scep2-card-period">
                    @if($edt->date_debut && $edt->date_fin)
                        <i class="fas fa-calendar-week me-1" style="color:#94a3b8;"></i>
                        {{ \Carbon\Carbon::parse($edt->date_debut)->format('d/m') }} → {{ \Carbon\Carbon::parse($edt->date_fin)->format('d/m/Y') }}
                    @else
                        <span style="color:#94a3b8;">—</span>
                    @endif
                </div>
                <span class="scep2-card-cta">
                    <i class="fas fa-plus-circle"></i> Ajouter une séance
                </span>
            </a>
        @endforeach
    </div>
@endif
@endsection
