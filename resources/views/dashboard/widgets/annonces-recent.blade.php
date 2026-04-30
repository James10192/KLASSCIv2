@php
    /**
     * Widget : Annonces récentes (liste, taille lg)
     */
    $annonces = \App\Models\ESBTPAnnonce::query()
        ->where('is_published', true)
        ->orderByDesc('date_publication')
        ->limit(5)
        ->get(['id', 'titre', 'date_publication', 'priorite', 'type']);
    $color = $widget['color'] ?? 'info';
@endphp

<div class="dw-widget dw-widget--list dw-widget--{{ $color }}">
    <div class="dw-widget-list-header">
        <div class="dw-widget-icon dw-widget-icon--small">
            <i class="fas {{ $widget['icon'] ?? 'fa-bullhorn' }}"></i>
        </div>
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
    </div>

    <ul class="dw-widget-list">
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
    </ul>
</div>
