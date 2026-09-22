@extends('layouts.app')

@section('title', 'Demande '.$reference)

@push('styles')
<style>
    .sd-entete { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
    .sd-entete h1 { font-size: 1.3rem; font-weight: 700; color: #1e293b; margin: .25rem 0 0; }
    .sd-ref { font-family: 'Courier New', monospace; font-size: .8rem; color: #0453cb; font-weight: 700; }
    .sd-retour { font-size: .84rem; color: #0453cb; text-decoration: none; font-weight: 600; }
    .sd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem 1.5rem; margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .sd-card h2 { font-size: .8rem; text-transform: uppercase; letter-spacing: .5px; color: #64748b; font-weight: 700; margin: 0 0 .75rem; }
    .sd-texte { white-space: pre-line; color: #1e293b; font-size: .92rem; margin: 0; }
    .sd-meta { font-size: .78rem; color: #64748b; margin-top: .75rem; }
    .sd-fil { display: flex; flex-direction: column; gap: .75rem; }
    .sd-msg { padding: .85rem 1rem; border-radius: 12px; font-size: .9rem; }
    .sd-msg--support { background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.15); }
    .sd-msg--ecole { background: #f8fafc; border: 1px solid #e2e8f0; }
    .sd-msg-auteur { font-size: .76rem; color: #64748b; margin-bottom: .3rem; }
    .sd-msg-auteur strong { color: #1e293b; }
    .sd-msg p { white-space: pre-line; margin: 0; color: #1e293b; }
    .sd-vide { color: #64748b; font-size: .88rem; margin: 0; }
    .sd-statut { font-size: .78rem; font-weight: 700; padding: .35rem .7rem; border-radius: 999px; white-space: nowrap; }
    .sd-statut--info { background: rgba(4,83,203,.08); color: #0453cb; }
    .sd-statut--attention { background: rgba(245,158,11,.12); color: #b45309; }
    .sd-statut--succes { background: rgba(16,185,129,.12); color: #047857; }
    .sd-statut--neutre { background: #f1f5f9; color: #64748b; }
</style>
@endpush

@section('content')
<a href="{{ route('support.demandes.index', request('portee') === 'ecole' ? ['portee' => 'ecole'] : []) }}" class="sd-retour"><i class="fas fa-arrow-left me-1"></i> Toutes mes demandes</a>

@if($indisponible)
    <div class="sd-card mt-3">
        <p class="sd-vide"><strong>Le support est momentanément injoignable.</strong> La demande {{ $reference }} est en sécurité ; réessayez dans un instant.</p>
    </div>
@else
    <div class="sd-entete mt-2">
        <div>
            <span class="sd-ref">{{ $demande['reference'] }}</span>
            <h1>{{ $demande['titre'] }}</h1>
        </div>
        @include('support.demandes._statut', ['statut' => $demande['statut']])
    </div>

    <div class="sd-card">
        <h2>Votre signalement</h2>
        <p class="sd-texte">{{ $demande['description'] }}</p>
        <div class="sd-meta">
            {{ $demande['categorie']['libelle'] ?? '' }}
            · envoyé le {{ \App\Domain\Support\Services\DateDuMaster::afficher($demande['cree_le'] ?? null, 'd M Y à H:i') }}
            @if(!empty($demande['rapporteur']['nom'])) · par {{ $demande['rapporteur']['nom'] }} @endif
        </div>
    </div>

    <div class="sd-card">
        <h2>Échanges</h2>
        @if(empty($demande['messages']))
            <p class="sd-vide">Le support n'a pas encore répondu. Vous serez prévenu ici dès que ce sera le cas.</p>
        @else
            <div class="sd-fil">
                @foreach($demande['messages'] as $m)
                    <div class="sd-msg {{ $m['auteur'] === 'SUPPORT' ? 'sd-msg--support' : 'sd-msg--ecole' }}">
                        <div class="sd-msg-auteur"><strong>{{ $m['nom'] }}</strong> · {{ \App\Domain\Support\Services\DateDuMaster::afficher($m['le'] ?? null, 'd M Y à H:i') }}</div>
                        <p>{{ $m['corps'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
@endsection
