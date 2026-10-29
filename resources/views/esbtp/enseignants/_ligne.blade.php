{{-- Une ligne de la liste des enseignants : rendue par la page et par la suite chargee au defilement. --}}
<tr data-li-cle="{{ $teacher->id }}">
    <td style="padding-left: 1.25rem;">
        <input type="checkbox" class="te-checkbox bulk-select-checkbox" value="{{ $teacher->id }}">
    </td>
    <td>
        <div class="te-teacher-info">
            <div class="te-avatar">
                {{ $teacher->user ? strtoupper(substr($teacher->user->name, 0, 2)) : 'NA' }}
            </div>
            <div>
                <div class="te-teacher-name">{{ $teacher->user->name ?? 'N/A' }}</div>
                <div class="te-teacher-matricule">{{ $teacher->matricule ?? '—' }}</div>
            </div>
        </div>
    </td>
    <td>
        <div class="te-teacher-email">{{ $teacher->user->email ?? 'N/A' }}</div>
        @if($teacher->user && $teacher->user->phone)
            <div class="te-teacher-phone">{{ $teacher->user->phone }}</div>
        @endif
    </td>
    <td>
        <span style="font-size: 0.85rem; color: #374151;">{{ $teacher->specialization ?? '—' }}</span>
    </td>
    <td>
        <span class="te-status {{ $teacher->status === 'active' ? 'te-status-active' : 'te-status-inactive' }}">
            {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
        </span>
    </td>
    <td>
        <div class="te-actions" style="justify-content: center;">
            <a href="{{ route('esbtp.enseignants.show', $teacher) }}"
               class="te-action-btn te-action-view" title="Voir le profil">
                <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('esbtp.enseignants.edit', $teacher) }}"
               class="te-action-btn te-action-edit" title="Modifier">
                <i class="fas fa-pen"></i>
            </a>
            <form action="{{ route('esbtp.enseignants.toggleStatus', $teacher) }}"
                  method="POST" class="d-inline">
                @csrf
                <button type="submit"
                        class="te-action-btn {{ $teacher->status === 'active' ? 'te-action-toggle-off' : 'te-action-toggle-on' }}"
                        title="{{ $teacher->status === 'active' ? 'Désactiver' : 'Activer' }}">
                    <i class="fas fa-{{ $teacher->status === 'active' ? 'pause' : 'play' }}"></i>
                </button>
            </form>
        </div>
    </td>
</tr>
