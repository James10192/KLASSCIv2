@extends('layouts.app')

@section('title', 'Prolongations de cours - KLASSCI')

@push('styles')
<style>
    .pli-hero { background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%); border-radius:18px; padding:2rem 2.5rem 1.5rem; color:#fff; margin-bottom:1.25rem; }
    .pli-hero-left { display:flex; align-items:center; gap:1rem; }
    .pli-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .pli-hero h1 { font-size:1.45rem; font-weight:700; color:#fff; margin:0; }
    .pli-hero p { color:rgba(255,255,255,.75); font-size:.88rem; margin:0; }
    .pli-kpis { display:flex; gap:.75rem; margin-top:1.5rem; flex-wrap:wrap; }
    .pli-kpi { flex:1; min-width:140px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:12px; padding:.9rem 1rem; }
    .pli-kpi-value { font-size:1.35rem; font-weight:700; }
    .pli-kpi-label { font-size:.72rem; color:rgba(255,255,255,.7); }
    .pli-list { display:flex; flex-direction:column; gap:.75rem; }
    .pli-row { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1rem 1.25rem; display:grid; grid-template-columns:1fr auto; gap:1rem; align-items:center; }
    .pli-row h3 { font-size:.98rem; font-weight:700; color:#1e293b; margin:0 0 .2rem; }
    .pli-meta { font-size:.82rem; color:#64748b; }
    .pli-motif { font-size:.85rem; color:#1e293b; margin-top:.35rem; }
    .pli-conflits { margin-top:.5rem; font-size:.82rem; color:#b91c1c; background:rgba(220,38,38,.05); border:1px solid rgba(220,38,38,.2); border-radius:8px; padding:.5rem .75rem; }
    .pli-libre { margin-top:.5rem; font-size:.82rem; color:#047857; }
    .pli-actions { display:flex; gap:.5rem; flex-wrap:wrap; justify-content:flex-end; }
    .pli-btn { border-radius:10px; padding:.5rem 1rem; font-size:.82rem; font-weight:600; border:1px solid transparent; }
    .pli-btn--primary { background:#0453cb; color:#fff; }
    .pli-btn--ghost { background:#fff; color:#0453cb; border-color:#bfd3f2; }
    .pli-btn:disabled { opacity:.5; }
    .pli-badge { display:inline-block; border-radius:999px; padding:.2rem .65rem; font-size:.74rem; font-weight:700; }
    .pli-badge--en_attente { background:rgba(4,83,203,.1); color:#0453cb; }
    .pli-badge--accordee { background:rgba(16,185,129,.12); color:#047857; }
    .pli-badge--refusee, .pli-badge--annulee { background:rgba(100,116,139,.12); color:#475569; }
    .pli-vide { background:#fff; border:1px dashed #cbd5e1; border-radius:14px; padding:2rem; text-align:center; color:#64748b; }
    @@media (max-width: 768px) { .pli-hero { padding:1.5rem 1.25rem; } .pli-row { grid-template-columns:1fr; } .pli-actions { justify-content:flex-start; } }
</style>
@endpush

@section('content')
@php
    $pliEnAttente = $prolongations->where('statut', \App\Models\ESBTPProlongationSeance::EN_ATTENTE)->count();
    $pliAccordees = $prolongations->where('statut', \App\Models\ESBTPProlongationSeance::ACCORDEE)->count();
    $pliRefusees = $prolongations->where('statut', \App\Models\ESBTPProlongationSeance::REFUSEE)->count();
@endphp
<div class="container-fluid" x-data="{ statuts: {}, messages: {}, envoi: null,
    async decider(id, action) {
        this.envoi = id;
        try {
            const r = await fetch('{{ url('esbtp/prolongations') }}/' + id + '/' + action, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: '{}' });
            const d = await r.json().catch(() => ({}));
            this.messages[id] = d.message || ('Erreur ' + r.status);
            if (d.prolongation) this.statuts[id] = d.prolongation.statut;
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: r.ok ? 'success' : 'error', message: this.messages[id] } }));
        } finally { this.envoi = null; }
    } }">
    <div class="pli-hero">
        <div class="pli-hero-left">
            <div class="pli-hero-icon"><i class="fas fa-clock-rotate-left"></i></div>
            <div>
                <h1>Prolongations de cours</h1>
                <p>Chaque demande est vérifiée avant d’être accordée : séance suivante de la classe, salle, autres cours de l’enseignant.</p>
            </div>
        </div>
        <div class="pli-kpis">
            <div class="pli-kpi"><div class="pli-kpi-value">{{ $pliEnAttente }}</div><div class="pli-kpi-label">En attente de décision</div></div>
            <div class="pli-kpi"><div class="pli-kpi-value">{{ $pliAccordees }}</div><div class="pli-kpi-label">Accordées (100 dernières)</div></div>
            <div class="pli-kpi"><div class="pli-kpi-value">{{ $pliRefusees }}</div><div class="pli-kpi-label">Refusées (100 dernières)</div></div>
        </div>
    </div>

    @if($prolongations->isEmpty())
        <div class="pli-vide"><i class="fas fa-circle-check me-2"></i>Aucune demande de prolongation. Les enseignants la font depuis l’écran « Gestion de la séance ».</div>
    @else
        <div class="pli-list">
            @foreach($prolongations as $p)
                @php
                    $pliSeance = $p->seance;
                    $pliConflits = $conflits[$p->id] ?? [];
                @endphp
                <div class="pli-row">
                    <div>
                        <h3>{{ $pliSeance->matiere->name ?? 'Cours' }} — {{ $pliSeance->emploiTemps->classe->name ?? 'classe inconnue' }}</h3>
                        <div class="pli-meta">
                            {{ $p->demandeur->name ?? 'Enseignant' }} · {{ $p->date->format('d/m/Y') }} ·
                            fin {{ substr((string) $p->heure_fin_initiale, 0, 5) }} → <strong>{{ substr((string) $p->heure_fin_demandee, 0, 5) }}</strong> (+{{ $p->minutes }} min)
                            @if($pliSeance && $pliSeance->salle) · salle {{ $pliSeance->salle }} @endif
                        </div>
                        <div class="pli-motif">« {{ $p->motif }} »</div>
                        @if($p->statut === \App\Models\ESBTPProlongationSeance::EN_ATTENTE)
                            @if(count($pliConflits))
                                <div class="pli-conflits"><i class="fas fa-triangle-exclamation me-1"></i>{{ implode(' ', $pliConflits) }}</div>
                            @else
                                <div class="pli-libre"><i class="fas fa-check me-1"></i>Créneau libre : aucune séance, salle ni cours de l’enseignant sur le temps ajouté.</div>
                            @endif
                        @elseif($p->motif_decision)
                            <div class="pli-meta" style="margin-top:.35rem">Décision de {{ $p->decideur->name ?? '—' }} : {{ $p->motif_decision }}</div>
                        @endif
                        <div class="pli-meta" x-show="messages[{{ $p->id }}]" x-text="messages[{{ $p->id }}]" x-cloak style="margin-top:.35rem"></div>
                    </div>
                    <div class="pli-actions">
                        <span class="pli-badge pli-badge--{{ $p->statut }}" x-show="!statuts[{{ $p->id }}]">{{ $p->libelleStatut() }}</span>
                        <span class="pli-badge" x-show="statuts[{{ $p->id }}]" x-cloak :class="'pli-badge--' + statuts[{{ $p->id }}]" x-text="statuts[{{ $p->id }}] === 'accordee' ? 'Accordée' : 'Refusée'"></span>
                        @if($p->statut === \App\Models\ESBTPProlongationSeance::EN_ATTENTE)
                            <template x-if="!statuts[{{ $p->id }}]">
                                <div class="pli-actions">
                                    <button type="button" class="pli-btn pli-btn--primary" :disabled="envoi === {{ $p->id }} || {{ count($pliConflits) ? 'true' : 'false' }}" x-on:click="decider({{ $p->id }}, 'accorder')" title="{{ count($pliConflits) ? 'Le créneau ajouté est occupé' : 'Accorder la prolongation' }}">Accorder</button>
                                    <button type="button" class="pli-btn pli-btn--ghost" :disabled="envoi === {{ $p->id }}" x-on:click="decider({{ $p->id }}, 'refuser')">Refuser</button>
                                </div>
                            </template>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
