{{-- Une ligne de la liste des presences : rendue par la page et par la suite chargee au defilement. --}}
@php $avatarColors = ['#0453cb','#10b981','#f97316','#06b6d4','#0891b2','#dc6803','#0369a1']; $avatarBg = $avatarColors[abs(crc32($attendance->etudiant->nom_complet)) % count($avatarColors)]; @endphp
<tr data-li-cle="{{ $attendance->id }}">
    <td>
        <div style="font-weight:700;font-size:.875rem;">{{ $attendance->date->format('d/m/Y') }}</div>
        <div style="font-size:.75rem;color:var(--text-secondary);">{{ $attendance->created_at->format('H:i') }}</div>
    </td>
    <td>
        <div style="display:flex;align-items:center;gap:.6rem;">
            <div class="att-avatar" style="background:{{ $attendance->etudiant->photo_url ? 'transparent' : $avatarBg }};">
                @if($attendance->etudiant->photo_url)
                    <img src="{{ $attendance->etudiant->photo_url }}" alt="{{ $attendance->etudiant->nom_complet }}" onerror="this.parentElement.style.background='{{ $avatarBg }}';this.outerHTML='{{ strtoupper(substr($attendance->etudiant->nom_complet, 0, 2)) }}';">
                @else
                    {{ strtoupper(substr($attendance->etudiant->nom_complet, 0, 2)) }}
                @endif
            </div>
            <div>
                <div class="att-student-name">{{ $attendance->etudiant->nom_complet }}</div>
                <div class="att-student-id">#{{ $attendance->etudiant->id }}</div>
            </div>
        </div>
    </td>
    <td>
        <span class="att-class-badge">
            {{ $attendance->classe->name ?? ($attendance->etudiant->classe->name ?? 'N/A') }}
        </span>
    </td>
    <td style="color:var(--text-secondary);">
        {{ $attendance->matiere->name ?? ($attendance->seanceCours->matiere->name ?? 'N/A') }}
    </td>
    <td>
        @if($attendance->statut === 'present')
            <span class="att-status-pill sp-present"><i class="fas fa-check-circle"></i>Présent</span>
        @elseif($attendance->statut === 'absent')
            <span class="att-status-pill sp-absent"><i class="fas fa-times-circle"></i>Absent</span>
        @elseif($attendance->statut === 'retard' || $attendance->statut === 'late')
            <span class="att-status-pill sp-retard"><i class="fas fa-clock"></i>Retard</span>
        @elseif($attendance->statut === 'excuse')
            <span class="att-status-pill sp-excuse"><i class="fas fa-file-medical"></i>Excusé</span>
        @else
            <span class="att-status-pill" style="background:#f1f5f9;color:var(--text-secondary);">{{ ucfirst($attendance->statut) }}</span>
        @endif
    </td>
    <td style="font-size:.875rem;color:var(--text-secondary);">
        {{ $attendance->teacher->user->name ?? 'N/A' }}
    </td>
    <td>
        <div style="display:flex;gap:.4rem;justify-content:center;">
            <button type="button" class="att-btn-icon abi-view"
                    data-bs-toggle="modal"
                    data-bs-target="#detailsModal{{ $attendance->id }}"
                    title="Voir détails">
                <i class="fas fa-eye"></i>
            </button>
            <a href="{{ route('esbtp.attendances.edit', $attendance) }}"
               class="att-btn-icon abi-edit" title="Modifier">
                <i class="fas fa-edit"></i>
            </a>
        </div>
        @include('esbtp.attendances._details')
    </td>
</tr>
