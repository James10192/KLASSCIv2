@props(['filters' => []])

@if(!empty($filters))
<div class="pdf-filters-recap">
    <span class="pdf-filters-recap-title">Filtres appliqués :</span>
    @foreach($filters as $label => $value)
        @if($value !== null && $value !== '')
            <span class="pdf-filters-recap-item">
                <strong>{{ $label }} :</strong> {{ $value }}
            </span>
        @endif
    @endforeach
</div>
@endif
