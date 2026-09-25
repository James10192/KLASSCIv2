@extends('layouts.app')

@section('title', 'Résultats de recherche')

@push('styles')
<style>
    .srp-wrap { max-width: 960px; margin: 0 auto; }
    .srp-form { display: flex; gap: .5rem; margin-bottom: 1rem; }
    .srp-form input {
        flex: 1; min-width: 0; height: 44px; padding: 0 1rem 0 2.6rem;
        border: 1px solid #e2e8f0; border-radius: 12px; font-size: .95rem; color: #0f172a;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='m20 20-3.5-3.5'/%3E%3C/svg%3E") no-repeat 14px 50%;
    }
    .srp-form input:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .srp-form button { height: 44px; padding: 0 1.1rem; border: 0; border-radius: 12px; background: #0453cb; color: #fff; font-weight: 600; }
    .srp-chips { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1.25rem; }
    .srp-chip { padding: .35rem .8rem; border-radius: 999px; border: 1px solid #dbe4f0; background: #fff; color: #334155; font-size: .82rem; font-weight: 600; text-decoration: none; }
    .srp-chip:hover { border-color: #0453cb; color: #0453cb; }
    .srp-chip--active { background: #0453cb; border-color: #0453cb; color: #fff; }
    .srp-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); margin-bottom: 1rem; overflow: hidden; }
    .srp-card h2 { margin: 0; padding: .8rem 1.1rem; font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #eef2f7; }
    .srp-item { display: flex; align-items: center; gap: .85rem; padding: .7rem 1.1rem; text-decoration: none; color: inherit; border-bottom: 1px solid #f1f5f9; }
    .srp-item:last-child { border-bottom: 0; }
    .srp-item:hover { background: #f5f8fd; }
    .srp-ico { width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: rgba(4,83,203,.08); color: #0453cb; font-size: .9rem; }
    .srp-txt { min-width: 0; }
    .srp-title { font-weight: 600; color: #0f172a; font-size: .92rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .srp-sub { color: #64748b; font-size: .8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .srp-empty { text-align: center; padding: 3rem 1rem; color: #64748b; }
    .srp-empty i { font-size: 2rem; color: #94a3b8; margin-bottom: .75rem; }
    .srp-note { padding: .7rem 1rem; border-radius: 10px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: .85rem; margin-bottom: 1rem; }
</style>
@endpush

@section('content')
@php
    $libellesTypes = [
        'all' => 'Tout',
        'pages' => 'Pages',
        'etudiants' => 'Étudiants',
        'paiements' => 'Paiements',
        'classes' => 'Classes',
        'filieres' => 'Filières',
        'matieres' => 'Matières',
        'enseignants' => 'Enseignants',
    ];
    $typesVisibles = array_merge(['all', 'pages'], $groupesOuverts);
@endphp
<div class="srp-wrap">
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-magnifying-glass me-2"></i>Recherche</h1>
            <p class="header-subtitle">
                @if(mb_strlen($query) >= 2)
                    Résultats pour « {{ $query }} »
                @else
                    Tapez au moins deux caractères. Astuce : Ctrl K (⌘ K sur Mac) ouvre la recherche depuis n'importe quelle page.
                @endif
            </p>
        </div>
    </div>

    <form class="srp-form" method="GET" action="{{ route('search.results') }}" role="search">
        <input type="search" name="q" value="{{ $query }}" minlength="2" required autocomplete="off" aria-label="Rechercher dans l'application" placeholder="Rechercher un étudiant, un reçu, une page…">
        <input type="hidden" name="type" value="{{ $type }}">
        <button type="submit">Rechercher</button>
    </form>

    @if(mb_strlen($query) >= 2)
        <nav class="srp-chips" aria-label="Filtrer les résultats">
            @foreach($typesVisibles as $t)
                <a href="{{ route('search.results', ['q' => $query, 'type' => $t]) }}"
                   class="srp-chip {{ $type === $t ? 'srp-chip--active' : '' }}"
                   @if($type === $t) aria-current="true" @endif>{{ $libellesTypes[$t] ?? $t }}</a>
            @endforeach
        </nav>

        @if($partiel)
            <div class="srp-note" role="status">
                <i class="fas fa-triangle-exclamation me-1"></i>Une partie des résultats est momentanément indisponible. Réessayez dans un instant.
            </div>
        @endif

        @forelse($groupes as $nomGroupe => $resultats)
            <section class="srp-card" aria-label="{{ $nomGroupe }}">
                <h2>{{ $nomGroupe }} <span class="text-muted">({{ $resultats->count() }})</span></h2>
                @foreach($resultats as $resultat)
                    <a href="{{ $resultat['url'] }}" class="srp-item">
                        <span class="srp-ico"><i class="fas {{ $resultat['icon'] }}" aria-hidden="true"></i></span>
                        <span class="srp-txt">
                            <span class="srp-title d-block">{{ $resultat['title'] }}</span>
                            @if($resultat['subtitle'] !== '')
                                <span class="srp-sub d-block">{{ $resultat['subtitle'] }}</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </section>
        @empty
            <div class="srp-card srp-empty">
                <i class="fas fa-magnifying-glass d-block"></i>
                Aucun résultat pour « {{ $query }} ».
            </div>
        @endforelse
    @endif
</div>
@endsection
