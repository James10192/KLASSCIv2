<tr class="summary-row">
    <td>{{ $label }}</td>
    @if($showSubjectAverage)
        <td class="center">{{ $summary['moyenne'] === null ? '-' : number_format($summary['moyenne'], 2) }}</td>
    @endif
    @if($showCoefficient)
        <td class="center">{{ $summary['coefficient'] == 0 ? '-' : rtrim(rtrim(number_format($summary['coefficient'], 2, '.', ''), '0'), '.') }}</td>
    @endif
    @if($showWeightedAverage)
        <td class="center">{{ $summary['coefficient'] == 0 ? '-' : number_format($summary['weighted'], 2) }}</td>
    @endif
    @if($showRankPerSubject)
        <td class="center">{{ $summary['rang'] ?: '-' }}</td>
    @endif
    @if($showAbsencesParMatiere)
        <td class="center">{{ $summary['absences'] == 0 ? 0 : rtrim(rtrim(number_format($summary['absences'], 2, '.', ''), '0'), '.') }}</td>
    @endif
    @if($showTeachers)
        <td></td>
    @endif
    @if($showAppreciations)
        <td class="center">
            @include('esbtp.bulletins.partials.appreciation', [
                'moyenne' => $summary['moyenne'],
                'badgeClass' => 'appreciation-badge',
            ])
        </td>
    @endif
</tr>
