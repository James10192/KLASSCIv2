{{-- Alertes du jour. Reçoit $stats. --}}
@php
    $alerts = $stats['alerts'] ?? [];
    $iconFor = ['danger' => 'fa-circle-exclamation', 'warning' => 'fa-triangle-exclamation', 'info' => 'fa-circle-info'];
@endphp
@if(empty($alerts))
    <div class="cad-noalert"><i class="fas fa-circle-check"></i> Aucune alerte — la journée se déroule normalement.</div>
@else
    @foreach($alerts as $a)
        <div class="cad-alert cad-alert--{{ $a['type'] ?? 'info' }}">
            <i class="fas {{ $iconFor[$a['type'] ?? 'info'] ?? 'fa-circle-info' }}"></i>
            <div>
                <div class="cad-alert-title">{{ $a['title'] ?? 'Alerte' }}</div>
                <div class="cad-alert-msg">{{ $a['message'] ?? '' }}</div>
                @if(!empty($a['details']))
                    <ul class="cad-alert-details">
                        @foreach($a['details'] as $d)<li>{{ $d }}</li>@endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endforeach
@endif
