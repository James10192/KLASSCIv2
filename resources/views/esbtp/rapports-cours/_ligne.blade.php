{{-- Un rapport de cours : rendu par la page et par la suite chargee au defilement. --}}
@php
    $seance = $report->seanceCours;
    $dateSeance = $seance?->date_seance
        ? \Carbon\Carbon::parse($seance->date_seance)->format('d/m/Y')
        : '—';
    $heure = $seance && $seance->heure_debut
        ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i')
        : null;
@endphp
<tr data-li-cle="{{ $report->id }}">
    <td>
        <div class="rc-meta-strong">{{ $dateSeance }}</div>
        @if($heure)<div class="rc-meta-muted">{{ $heure }}</div>@endif
    </td>
    <td>
        <div class="rc-meta-strong">{{ $seance?->matiere?->name ?? '—' }}</div>
        <div class="rc-meta-muted">{{ $seance?->classe?->name ?? '—' }}</div>
    </td>
    <td>
        <div class="rc-meta-strong">{{ $report->teacher?->name ?? '—' }}</div>
    </td>
    <td>
        <span class="rc-badge rc-badge--{{ $report->student_behavior }}">
            {{ $report->student_behavior_label }}
        </span>
    </td>
    <td>
        <div class="rc-summary">{{ $report->content_summary }}</div>
    </td>
    <td style="text-align:right;">
        <a href="{{ route('esbtp.rapports-cours.show', $report->id) }}" class="rc-action-btn">
            <i class="fas fa-eye"></i>Voir
        </a>
    </td>
</tr>
