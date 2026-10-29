@php
    /** @var array $widget */
    $annonces = \App\Models\ESBTPAnnonce::query()
        ->where('is_published', true)
        ->orderByDesc('date_publication')
        ->limit(5)
        ->get(['id', 'titre', 'date_publication', 'priorite', 'type']);
@endphp

<x-dw-widget-list
    :icon="$widget['icon'] ?? 'fa-bullhorn'"
    :label="$widget['label']"
    :color="$widget['color'] ?? 'primary'"
>
    @forelse ($annonces as $annonce)
        <li class="dw-widget-list-item">
            <div class="dw-widget-list-title">{{ $annonce->titre }}</div>
            <div class="dw-widget-list-meta">
                <i class="fas fa-calendar-alt"></i>
                @if ($annonce->date_publication)
                    {{ \Carbon\Carbon::parse($annonce->date_publication)->locale('fr')->isoFormat('D MMM YYYY') }}
                @else
                    Date non précisée
                @endif
            </div>
        </li>
    @empty
        <li class="dw-widget-list-empty">
            <i class="fas fa-inbox"></i>
            Aucune annonce récente
        </li>
    @endforelse
</x-dw-widget-list>
