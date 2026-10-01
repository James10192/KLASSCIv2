@extends('layouts.app')

@section('title', 'Dossiers d\'inscription en cours - KLASSCI')

@push('styles')
<style>
.miw{max-width:1180px;margin:0 auto;padding:24px}
.miw-head h1{font-size:1.5rem;font-weight:700;color:#0f172a;margin:0}
.miw-head p{margin:4px 0 0;color:#64748b;font-size:.9rem}
.miw-search{display:flex;gap:8px;margin-top:16px}
.miw-search input{flex:1;border:1px solid #dce3ed;border-radius:10px;padding:10px 12px;min-height:44px}
.miw-search button{border:0;border-radius:10px;padding:0 16px;background:#0453cb;color:#fff;font-weight:600;min-height:44px}
.miw-list{margin-top:16px;background:#fff;border:1px solid #e2e8f0;border-radius:14px}
.miw-row{display:grid;grid-template-columns:1.5fr 1.2fr 1fr auto;gap:16px;align-items:center;padding:14px 18px;border-bottom:1px solid #f1f5f9}
.miw-row:last-child{border-bottom:0}
.miw-name{font-weight:600;color:#0f172a}
.miw-muted{font-size:.82rem;color:#64748b;margin-top:2px}
.miw-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 14px;border-radius:10px;background:#0453cb;color:#fff;text-decoration:none;font-weight:600;min-height:44px}
.miw-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-top:16px}.miw-tab{padding:8px 12px;border-radius:999px;border:1px solid #dce3ed;color:#475569;text-decoration:none;font-size:.85rem;font-weight:600;min-height:40px;display:inline-flex;align-items:center}.miw-tab.actif{background:#0453cb;border-color:#0453cb;color:#fff}.miw-step{font-size:.82rem;color:#0453cb;font-weight:600}.miw-empty{text-align:center;padding:40px;color:#64748b}
@media(max-width:800px){.miw{padding:14px}.miw-row{grid-template-columns:1fr;gap:6px}}
</style>
@endpush

@section('content')
@inject('presentateur', 'App\\Services\\Admissions\\ManagedWorkflowPresenter')
<div class="miw">
    <div class="miw-head">
        <h1>Dossiers d'inscription en cours</h1>
        <p>Candidatures acceptées en ligne. L'identité vient de la candidature : rien à ressaisir.</p>
    </div>

    <nav class="miw-tabs" aria-label="Étape">
        @foreach($etapes as $cle => $libelle)
            <a class="miw-tab {{ $etape === $cle ? 'actif' : '' }}" href="{{ route('esbtp.admissions.workflow.index', ['etape' => $cle, 'q' => $recherche ?: null]) }}" @if($etape === $cle) aria-current="page" @endif>{{ $libelle }}</a>
        @endforeach
    </nav>

    @if(session('success'))<div class="alert alert-success mt-3">{{ session('success') }}</div>@endif
    @if(session('info'))<div class="alert alert-info mt-3">{{ session('info') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning mt-3">{{ session('warning') }}</div>@endif

    <form class="miw-search" method="GET" role="search">
        <input type="hidden" name="etape" value="{{ $etape }}">
        <label class="visually-hidden" for="miw-q">Rechercher un candidat</label>
        <input id="miw-q" name="q" value="{{ $recherche }}" placeholder="Nom, téléphone ou référence">
        <button type="submit"><i class="fas fa-search" aria-hidden="true"></i> Rechercher</button>
    </form>

    <section class="miw-list" aria-label="Dossiers en attente">
        @forelse($candidatures as $candidature)
            <div class="miw-row">
                <div>
                    <div class="miw-name">{{ $candidature->nom }} {{ $candidature->prenoms }}</div>
                    <div class="miw-muted">{{ $candidature->reference_publique ?: 'Dossier #'.$candidature->id }} · {{ $candidature->telephone ?: 'sans téléphone' }}</div>
                </div>
                <div>
                    <div>{{ $candidature->filiere?->name ?: 'Filière à confirmer' }}</div>
                    <div class="miw-muted">{{ $candidature->niveau?->name ?: 'Niveau à confirmer' }} · {{ $candidature->anneeUniversitaire?->name ?: 'année ?' }}</div>
                </div>
                <div>
                    <div class="miw-step">{{ $candidature->managedWorkflow ? $presentateur->prochaineEtape($candidature->managedWorkflow) : 'Premier passage au guichet.' }}</div>
                    <div class="miw-muted">Acceptée le {{ optional($candidature->traite_at)->format('d/m/Y') ?: '—' }}</div>
                </div>
                <a class="miw-btn" href="{{ route('esbtp.admissions.workflow.show', $candidature) }}">
                    <i class="fas fa-folder-open" aria-hidden="true"></i> Ouvrir
                </a>
            </div>
        @empty
            <div class="miw-empty">
                <i class="fas fa-check-circle fa-2x mb-2" aria-hidden="true"></i>
                <div><strong>{{ $recherche !== '' ? 'Aucun dossier ne correspond.' : 'Aucun dossier à cette étape.' }}</strong></div>
                <div class="miw-muted">Dernier contrôle : {{ now()->format('H:i') }}</div>
            </div>
        @endforelse
    </section>

    @if($candidatures->hasPages())
        <div class="mt-3">{{ $candidatures->links() }}</div>
    @endif
</div>
@endsection
