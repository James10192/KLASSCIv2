@extends('layouts.landing')

@section('title', 'Documentation')
@section('description', 'Apprenez à utiliser KLASSCI : guides par rôle (super-administrateur, secrétaire, enseignant, comptable, étudiant) et par module (LMD, emploi du temps, notes, comptabilité, présences, inscriptions).')

@push('styles')
<style>
    .docs-section { margin-bottom: 4rem; }
    .docs-section:last-child { margin-bottom: 0; }

    .docs-section-header {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border);
    }

    .docs-section-header h2 {
        font-family: 'IBM Plex Serif', Georgia, serif;
        font-style: normal;
        font-weight: 400;
        font-size: 1.5rem;
        color: var(--accent);
        letter-spacing: -0.015em;
    }

    .docs-section-header p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.9rem;
        color: var(--text-muted);
        max-width: 540px;
        text-align: right;
        line-height: 1.55;
    }

    .docs-featured {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 2.5rem;
        align-items: center;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2.5rem;
        margin-bottom: 4rem;
        text-decoration: none;
        color: inherit;
        transition: all var(--duration-normal) var(--ease-out);
    }

    .docs-featured:hover {
        border-color: var(--accent);
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(4,83,203,0.08);
        color: inherit;
    }

    .docs-featured-eyebrow {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.72rem;
        color: var(--accent);
        text-transform: uppercase;
        letter-spacing: 0.12em;
        margin-bottom: 0.75rem;
    }

    .docs-featured h3 {
        font-family: 'IBM Plex Serif', Georgia, serif;
        font-size: 1.65rem;
        font-style: normal;
        font-weight: 400;
        color: var(--text);
        margin-bottom: 0.75rem;
        letter-spacing: -0.015em;
    }

    .docs-featured p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.95rem;
        color: var(--text-secondary);
        line-height: 1.65;
        margin-bottom: 1rem;
    }

    .docs-featured-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--accent);
    }

    .docs-featured-image {
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid var(--border);
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }

    .docs-featured-image img {
        width: 100%;
        display: block;
    }

    @media (max-width: 768px) {
        .docs-featured { grid-template-columns: 1fr; padding: 1.75rem; }
        .docs-section-header { flex-direction: column; }
        .docs-section-header p { text-align: left; }
    }

    .docs-role-badge {
        display: inline-block;
        padding: 0.15rem 0.5rem;
        background: var(--accent-light);
        color: var(--accent);
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border-radius: 3px;
        margin-bottom: 0.5rem;
        align-self: flex-start;
    }
</style>
@endpush

@section('content')

<section class="page-hero">
    <div class="container">
        <div class="page-hero-eyebrow">Documentation</div>
        <h1>Apprenez à utiliser KLASSCI</h1>
        <p>
            Guides pratiques pour chaque profil et chaque module. Pas de jargon technique inutile,
            pas de captures floues : chaque article décrit un workflow réel sur la plateforme.
        </p>
    </div>
</section>

<section class="page-with-sidebar" style="padding-top:3rem">
    <div class="container">

        @php
            $featured = $sections['getting-started']['articles'][0] ?? null;
        @endphp

        @if($featured && ($featured['available'] ?? false))
            <a href="{{ route('docs.show', $featured['slug']) }}" class="docs-featured">
                <div>
                    <div class="docs-featured-eyebrow">À commencer ici</div>
                    <h3>{{ $featured['title'] }}</h3>
                    <p>{{ $featured['description'] }}</p>
                    <span class="docs-featured-cta">
                        Lire l'article
                        <i class="fas fa-arrow-right" style="font-size:.7rem"></i>
                    </span>
                </div>
                @if(!empty($featured['hero_image']))
                    <div class="docs-featured-image">
                        <img src="{{ asset($featured['hero_image']) }}" alt="{{ $featured['title'] }}" loading="lazy">
                    </div>
                @endif
            </a>
        @endif

        @foreach(['roles', 'modules'] as $sectionKey)
            @if(isset($sections[$sectionKey]))
                @php $sec = $sections[$sectionKey]; @endphp
                <div class="docs-section">
                    <div class="docs-section-header">
                        <h2>{{ $sec['title'] }}</h2>
                        <p>{{ $sec['description'] }}</p>
                    </div>

                    <div class="card-grid">
                        @foreach($sec['articles'] as $article)
                            @if($article['available'])
                                <a href="{{ route('docs.show', $article['slug']) }}" class="card-tile">
                                    @if(!empty($article['role_label']))
                                        <span class="docs-role-badge">{{ $article['role_label'] }}</span>
                                    @endif
                                    <h3>{{ $article['title'] }}</h3>
                                    <p>{{ $article['description'] }}</p>
                                    <span class="card-tile-foot">
                                        Lire <i class="fas fa-arrow-right"></i>
                                    </span>
                                </a>
                            @else
                                <div class="card-tile is-disabled">
                                    @if(!empty($article['role_label']))
                                        <span class="docs-role-badge">{{ $article['role_label'] }}</span>
                                    @endif
                                    <h3>{{ $article['title'] }}</h3>
                                    <p>{{ $article['description'] }}</p>
                                    <span class="card-tile-foot">
                                        Bientôt disponible <i class="fas fa-clock" style="opacity:.6"></i>
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

    </div>
</section>

@endsection
