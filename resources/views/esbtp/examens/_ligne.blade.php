{{-- Une ligne de la liste des examens : rendue par la page et par la suite chargee au defilement. Attend $e et $hasMixedSystemes. --}}
@php
    $statusLabel = \App\Enums\ExamenStatus::labelFor($e->status);
    $statusClass = \App\Enums\ExamenStatus::badgeClassFor($e->status);
@endphp
<tr data-li-cle="{{ $e->id }}" onclick="window.location='{{ route('esbtp.examens.show', $e) }}'">
    <td style="font-family:'Courier New',monospace;font-size:.78rem;color:#0453cb;font-weight:700;">
        {{ $e->numero_convocation ?? '—' }}
    </td>
    <td>
        <div style="font-weight:600;">{{ optional($e->date_debut)->format('d/m/Y') }}</div>
        <div style="color:#64748b;font-size:.75rem;">
            {{ optional($e->date_debut)->format('H:i') }}–{{ optional($e->date_fin)->format('H:i') }}
            @if($e->duree_minutes) · {{ $e->duree_minutes }}min @endif
        </div>
    </td>
    <td>
        @php
            $classeNames = $e->classes->pluck('name');
            $primaryClasse = $classeNames->first() ?? $e->classe?->name ?? '—';
            $extras = max(0, $classeNames->count() - 1);
            $ueName = $e->uniteEnseignement?->name;
            $examenSysteme = $e->hasConsistentSysteme() ? $e->systeme : 'MIXTE';
        @endphp
        <div style="font-weight:600;display:inline-flex;align-items:center;gap:.4rem;flex-wrap:wrap;">
            <span>{{ $primaryClasse }}</span>
            @if($extras > 0)
                <span title="{{ $classeNames->skip(1)->join(', ') }}" style="display:inline-block;padding:.05rem .35rem;background:rgba(4,83,203,.10);color:#0453cb;border-radius:5px;font-size:.65rem;font-weight:700;">+{{ $extras }}</span>
            @endif
            @if ($hasMixedSystemes || $examenSysteme === 'MIXTE')
                <x-systeme-chip :systeme="$examenSysteme" size="sm" />
            @endif
        </div>
        <div style="color:#64748b;font-size:.75rem;">
            {{ $e->matiere->name ?? '—' }}
            @if($ueName) · <em style="color:#3b7ddb;">{{ $ueName }}</em> @endif
        </div>
    </td>
    <td><span class="exp-chip exp-chip--{{ strtolower($e->type_examen) }}">{{ \App\Enums\TypeExamen::labelFor($e->type_examen) }}</span></td>
    <td>{{ $e->salle ?? '—' }}</td>
    <td style="color:#64748b;font-size:.78rem;">coef {{ rtrim(rtrim(number_format($e->coefficient, 2, '.', ''), '0'), '.') }} × /{{ (int) $e->bareme }}</td>
    <td>
        <span class="exp-status {{ $statusClass }}">
            @if($e->notes_locked) <i class="fas fa-lock"></i> @endif
            {{ $statusLabel }}
        </span>
    </td>
    <td onclick="event.stopPropagation()">
        <a href="{{ route('esbtp.examens.show', $e) }}" class="exp-btn exp-btn--secondary" style="padding:.3rem .7rem;font-size:.78rem;">
            <i class="fas fa-eye"></i>
        </a>
    </td>
</tr>
