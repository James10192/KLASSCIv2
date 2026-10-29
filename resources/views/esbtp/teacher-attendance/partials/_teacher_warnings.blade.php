{{-- Alertes de ponctualité d'un enseignant. Reçoit $summary. --}}
@php $warnings = $summary['warnings']; @endphp
@if(count($warnings) === 0)
    <div class="tdr-noalert"><i class="fas fa-circle-check"></i> Aucune alerte de ponctualité sur la période.</div>
@else
    @foreach($warnings as $w)
        <div class="tdr-alert tdr-alert--{{ $w['severite'] }}">
            <i class="fas {{ $w['type'] === 'retard' ? 'fa-clock' : 'fa-circle-exclamation' }}"></i>
            <span>{{ $w['message'] }}</span>
        </div>
    @endforeach
@endif
