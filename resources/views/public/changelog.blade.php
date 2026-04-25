@extends('layouts.landing')

@section('title', 'Changelog')
@section('description', 'Toutes les évolutions de KLASSCI, regroupées par mois — nouveautés, améliorations, corrections, sécurité.')

@push('styles')
<style>
    .cl-prose h2 {
        scroll-margin-top: 80px;
    }
    .cl-prose h3 {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-weight: 600;
        color: var(--text-muted);
        margin-top: 2rem;
        margin-bottom: 0.85rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border);
    }
    .cl-prose h2 + h3 { margin-top: 1.5rem; }
    .cl-prose ul {
        list-style: none;
        padding: 0;
        margin: 0 0 1.5rem;
    }
    .cl-prose ul li {
        position: relative;
        padding-left: 1.5rem;
        margin-bottom: 0.85rem;
        line-height: 1.65;
    }
    .cl-prose ul li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0.65rem;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--accent);
    }
    .cl-prose hr {
        margin: 3rem 0;
        border: none;
        border-top: 1px solid var(--border);
    }
    .cl-prose em {
        font-style: italic;
        color: var(--text-muted);
        font-size: 0.85rem;
    }
    .cl-stat {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.25rem 0.6rem;
        border-radius: var(--radius);
        background: var(--accent-light);
        color: var(--accent);
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.72rem;
        font-weight: 500;
        margin-left: 0.5rem;
        vertical-align: middle;
    }
    .cl-rss {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.78rem;
        color: var(--text-muted);
        margin-top: 1rem;
    }
    .cl-rss i { color: #f59e0b; }
    .cl-empty {
        padding: 3rem 0;
        text-align: center;
        color: var(--text-muted);
        font-family: 'IBM Plex Sans', sans-serif;
    }
</style>
@endpush

@section('content')

<section class="page-hero">
    <div class="container">
        <div class="page-hero-eyebrow">Historique des livraisons</div>
        <h1>Tout ce que nous avons livré</h1>
        <p>
            Chaque ligne est un changement réel poussé en production sur les établissements
            qui utilisent KLASSCI. Mises à jour automatiques, sans intervention de votre part.
        </p>
        <div class="page-hero-meta">
            <span><i class="fas fa-clock-rotate-left" style="margin-right:.4rem"></i>Mis à jour le {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('D MMMM YYYY') }}</span>
            <span class="dot"></span>
            <span>Curé manuellement depuis l'historique Git</span>
        </div>
    </div>
</section>

<section class="page-with-sidebar">
    <div class="container">
        <div class="page-with-sidebar-inner">
            <aside class="page-sidebar" aria-label="Navigation par mois">
                <h4>Sommaire</h4>
                @if(empty($months))
                    <p style="font-size:.85rem;color:var(--text-muted)">Aucune entrée</p>
                @else
                    <ul>
                        @foreach($months as $month)
                            <li><a href="#{{ $month['anchor'] }}">{{ $month['label'] }}</a></li>
                        @endforeach
                    </ul>
                @endif

                <h4 style="margin-top:2rem">Liens utiles</h4>
                <ul>
                    <li><a href="{{ Route::has('docs.index') ? route('docs.index') : '#' }}">Documentation</a></li>
                    <li><a href="{{ Route::has('api-reference') ? route('api-reference') : '#' }}">API Reference</a></li>
                    <li><a href="{{ route('welcome') }}#fonctionnalites">Fonctionnalités</a></li>
                </ul>
            </aside>

            <article class="page-content">
                <div class="prose cl-prose">
                    @if(empty($html))
                        <div class="cl-empty">
                            <i class="fas fa-file-circle-question" style="font-size:1.5rem;display:block;margin-bottom:.5rem"></i>
                            Le changelog n'est pas disponible pour le moment.
                        </div>
                    @else
                        {!! $html !!}
                    @endif
                </div>
            </article>
        </div>
    </div>
</section>

@endsection
