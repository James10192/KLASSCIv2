@props([
    'type' => 'info',
    'title' => '',
    'message' => '',
    'details' => []
])

<div class="alert-card {{ $type }}">
    <div class="d-flex">
        <i class="fas fa-{{ $type === 'warning' ? 'exclamation-triangle' : ($type === 'danger' ? 'times-circle' : 'info-circle') }} me-2 mt-1"></i>
        <div class="flex-grow-1">
            <strong>{{ $title }}</strong>
            <p class="mb-1 small">{{ $message }}</p>
            @if(!empty($details))
                <ul class="mb-0 small text-muted">
                    @foreach(array_slice($details, 0, 3) as $detail)
                    <li>{{ $detail }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>