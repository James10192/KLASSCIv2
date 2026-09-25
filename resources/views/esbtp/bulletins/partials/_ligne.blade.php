{{-- Une ligne de la liste des bulletins. Rendue par _table et par chaque
     tranche du defilement ; ses boutons appellent le composant Alpine bulIndex(). --}}
@php
    $etu = $bulletin->etudiant;
    $initials = $etu
        ? mb_strtoupper(mb_substr($etu->prenoms ?? $etu->nom ?? '?', 0, 1, 'UTF-8') . mb_substr($etu->nom ?? '?', 0, 1, 'UTF-8'), 'UTF-8')
        : '?';
    $periode = strtolower($bulletin->periode ?? '');
    $periodeLabel = match ($periode) {
        'semestre1' => 'Semestre 1',
        'semestre2' => 'Semestre 2',
        'annuel'    => 'Annuel (legacy)',
        default     => $bulletin->periode,
    };
    $periodeCls = match ($periode) {
        'semestre1' => 's1',
        'semestre2' => 's2',
        'annuel'    => 'annuel',
        default     => 's1',
    };
    $moy = $bulletin->moyenne_generale;
    $moyCls = $moy === null ? 'na' : ($moy >= 12 ? 'good' : ($moy >= 10 ? 'mid' : 'bad'));
@endphp
<tr data-li-cle="{{ $bulletin->id }}">
    <td class="checkbox-col">
        <input type="checkbox" :checked="selected.includes({{ $bulletin->id }})" @change="toggle({{ $bulletin->id }})" />
    </td>
    <td>
        <div class="bul-etu">
            <div class="bul-etu-avatar">{{ $initials }}</div>
            <div class="bul-etu-meta">
                <div class="bul-etu-name">{{ $etu ? trim($etu->prenoms . ' ' . $etu->nom) : '—' }}</div>
                <div class="bul-etu-matricule">{{ $etu->matricule ?? '—' }}</div>
            </div>
        </div>
    </td>
    <td>{{ $bulletin->classe?->name ?? '—' }}</td>
    <td><span class="bul-periode-badge {{ $periodeCls }}">{{ $periodeLabel }}</span></td>
    <td class="center">
        @if($moy !== null)
            <span class="bul-moy-pill {{ $moyCls }}">{{ number_format($moy, 2) }}<small style="font-weight:500; opacity:.7;">/20</small></span>
        @else
            <span class="bul-moy-pill na">—</span>
        @endif
    </td>
    <td class="center">
        @if($bulletin->rang)
            <span class="bul-rang-display">
                {{ $bulletin->rang }}<sup>{{ $bulletin->rang == 1 ? 'er' : 'ème' }}</sup>
                @if($bulletin->effectif_classe)<span class="over">/ {{ $bulletin->effectif_classe }}</span>@endif
            </span>
        @else
            <span class="bul-moy-pill na">—</span>
        @endif
    </td>
    <td>
        @if($bulletin->is_published)
            <span class="bul-status-badge published"><i class="fas fa-circle-check" style="font-size:.65rem;"></i> Publié</span>
        @else
            <span class="bul-status-badge pending"><i class="fas fa-clock" style="font-size:.65rem;"></i> En attente</span>
        @endif
    </td>
    <td style="white-space:nowrap; font-size:.78rem; color:var(--bul-muted);">
        {{ optional($bulletin->created_at)->format('d/m/Y') ?? '—' }}
    </td>
    <td class="center">
        <div class="bul-actions">
            <a href="{{ route('esbtp.bulletins.show', $bulletin) }}" class="bul-action" title="Voir détail">
                <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('esbtp.bulletins.preview-pdf', $bulletin) }}"
               target="_blank" class="bul-action" title="Aperçu PDF">
                <i class="fas fa-file-pdf"></i>
            </a>
            <a href="{{ route('esbtp.bulletins.download', $bulletin) }}"
               target="_blank" class="bul-action" title="Télécharger PDF">
                <i class="fas fa-download"></i>
            </a>
            @can('bulletins.edit')
            <a href="{{ route('esbtp.bulletins.edit', $bulletin) }}" class="bul-action" title="Modifier">
                <i class="fas fa-pen"></i>
            </a>
            @endcan
            @can('bulletins.delete')
            <button type="button" class="bul-action danger" title="Supprimer"
                    @click="confirmDelete({{ $bulletin->id }})">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>
    </td>
</tr>
