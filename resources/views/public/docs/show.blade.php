@extends('layouts.landing')

@section('title', $article['title'])
@section('description', $article['description'] ?? 'Documentation KLASSCI.')
@if(!empty($article['hero_image']))
    @section('og_image', asset($article['hero_image']))
@endif

@push('styles')
<style>
    .doc-breadcrumb {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.78rem;
        color: var(--text-muted);
        margin-bottom: 1rem;
    }
    .doc-breadcrumb a { color: var(--text-muted); }
    .doc-breadcrumb a:hover { color: var(--accent); }
    .doc-breadcrumb .sep { opacity: 0.5; }

    .doc-header {
        margin-bottom: 2rem;
    }
    .doc-header h1 {
        font-family: 'IBM Plex Serif', Georgia, serif;
        font-size: clamp(1.85rem, 4vw, 2.5rem);
        font-style: normal;
        font-weight: 400;
        line-height: 1.2;
        margin-bottom: 0.85rem;
        color: var(--accent);
        letter-spacing: -0.015em;
    }
    .doc-header p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 1.05rem;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    .doc-toc {
        font-size: 0.82rem;
    }

    .doc-toc-l3 {
        padding-left: 0.85rem !important;
        font-size: 0.78rem !important;
    }
</style>
@endpush

@section('content')

<section class="page-with-sidebar" style="padding-top:6rem">
    <div class="container">
        <div class="page-with-sidebar-inner">

            <aside class="page-sidebar" aria-label="Sommaire de la documentation">
                <h4>Documentation</h4>
                @foreach($sidebar as $sectionKey => $sec)
                    <h4 style="margin-top:1.25rem">{{ $sec['title'] }}</h4>
                    <ul>
                        @foreach($sec['articles'] as $a)
                            @if($a['available'])
                                <li><a href="{{ route('docs.show', $a['slug']) }}" class="{{ $a['slug'] === $slug ? 'is-active' : '' }}">{{ $a['title'] }}</a></li>
                            @else
                                <li><span style="display:block;padding:0.35rem 0.6rem;font-size:0.84rem;color:var(--text-muted);opacity:0.6">{{ $a['title'] }}</span></li>
                            @endif
                        @endforeach
                    </ul>
                @endforeach

                @if(!empty($toc))
                    <h4 style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border)">Sur cette page</h4>
                    <ul class="doc-toc">
                        @foreach($toc as $entry)
                            <li>
                                <a href="#{{ $entry['anchor'] }}" class="{{ $entry['level'] === 3 ? 'doc-toc-l3' : '' }}">{{ $entry['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </aside>

            <article class="page-content">

                <nav class="doc-breadcrumb" aria-label="Fil d'ariane">
                    <a href="{{ route('docs.index') }}">Documentation</a>
                    <span class="sep">/</span>
                    @if(!empty($article['role_label']))
                        <span>{{ $article['role_label'] }}</span>
                    @else
                        <span>{{ $sidebar[$article['section']]['title'] ?? '' }}</span>
                    @endif
                </nav>

                <header class="doc-header">
                    <h1>{{ $article['title'] }}</h1>
                    @if(!empty($article['description']))
                        <p>{{ $article['description'] }}</p>
                    @endif
                </header>

                <div class="prose">
                    {!! $html !!}
                </div>

                @if($prev || $next)
                    <nav class="prev-next" aria-label="Article précédent / suivant">
                        @if($prev)
                            <a href="{{ route('docs.show', $prev['slug']) }}">
                                <span class="prev-next-label">← Précédent</span>
                                <span class="prev-next-title">{{ $prev['title'] }}</span>
                            </a>
                        @else
                            <span></span>
                        @endif
                        @if($next)
                            <a href="{{ route('docs.show', $next['slug']) }}" class="is-next">
                                <span class="prev-next-label">Suivant →</span>
                                <span class="prev-next-title">{{ $next['title'] }}</span>
                            </a>
                        @endif
                    </nav>
                @endif

            </article>
        </div>
    </div>
</section>

@endsection
