{{-- Un emargement de l'enseignant : rendu par la page et par la suite chargee au defilement. --}}
@php
    $libellesStatut ??= [
        'present' => ['Présent', 'success'],
        'fait' => ['Présent', 'success'],
        'late' => ['En retard', 'warning'],
        'absent' => ['Absent', 'danger'],
        'not_signed' => ['Non émargé', 'danger'],
        'bloqué' => ['Bloqué', 'danger'],
    ];
    $statut = (string) ($attendance->status ?? '');
    [$libelleStatut, $couleurStatut] = $libellesStatut[$statut] ?? [ucfirst($statut ?: 'Inconnu'), 'secondary'];
@endphp
<tr data-li-cle="{{ $attendance->id }}">
    <td>{{ optional($attendance->date)->format('d/m/Y') ?? '—' }}</td>
    <td>{{ optional(optional($attendance->course)->matiere)->name ?? '—' }}</td>
    <td>{{ optional(optional($attendance->course)->classe)->name ?? '—' }}</td>
    <td>
        @if($attendance->type === 'start')
            Début
        @elseif($attendance->type === 'end')
            Fin
        @else
            —
        @endif
    </td>
    <td>{{ optional($attendance->validated_at)->format('H:i') ?? '—' }}</td>
    <td>
        <span class="badge bg-{{ $couleurStatut }}">{{ $libelleStatut }}</span>
    </td>
    <td>
        <small class="text-muted">{{ optional($attendance->dailyCode)->code ?? '—' }}</small>
    </td>
</tr>
