@php
    // Récupérer l'émargement pour cette séance (s'il existe)
    // Priorité : 1) attendance d'aujourd'hui, 2) attendance de la date de séance, 3) la plus récente
    $today = \Carbon\Carbon::today();

    // Chercher d'abord une attendance d'aujourd'hui (pour les updates manuels faits aujourd'hui)
    $attendance = $seance->teacherAttendances
        ->first(function($attendance) use ($today) {
            $attendanceDate = $attendance->date instanceof \Carbon\Carbon
                ? $attendance->date
                : \Carbon\Carbon::parse($attendance->date);
            return $attendanceDate->isSameDay($today);
        });

    // Si pas d'attendance aujourd'hui, chercher celle de la date de séance
    if (!$attendance) {
        $attendance = $seance->teacherAttendances
            ->first(function($attendance) use ($seance) {
                $attendanceDate = $attendance->date instanceof \Carbon\Carbon
                    ? $attendance->date
                    : \Carbon\Carbon::parse($attendance->date);
                return $attendanceDate->isSameDay(\Carbon\Carbon::parse($seance->date_seance));
            });
    }

    // Si toujours rien, prendre la plus récente
    if (!$attendance) {
        $attendance = $seance->teacherAttendances->sortByDesc('created_at')->first();
    }

    $hasAttendance = $attendance !== null;
    $attendanceStatus = $hasAttendance ? $attendance->status : 'not_signed';
@endphp
<tr data-seance-id="{{ $seance->id }}">
    <td>
        <div class="d-flex align-items-center">
            <div class="user-avatar me-2">
                <i class="fas fa-user-tie"></i>
            </div>
            <div>
                <div class="fw-semibold">{{ $seance->teacher?->user?->name ?? 'N/A' }}</div>
                <small class="text-muted">{{ $seance->teacher?->user?->email ?? '' }}</small>
            </div>
        </div>
    </td>
    <td>
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 25px; height: 25px; flex-shrink: 0;">
                <i class="fas fa-book" style="font-size: 10px;"></i>
            </div>
            <span class="fw-semibold">{{ $seance->matiere?->name ?? 'N/A' }}</span>
        </div>
    </td>
    <td>
        <span class="badge bg-light text-dark border">
            {{ $seance->emploiTemps?->classe?->name ?? 'N/A' }}
        </span>
    </td>
    <td>
        <div class="small">
            <div><i class="fas fa-clock text-muted me-1"></i>
                {{ $seance->heure_debut ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') : 'N/A' }} -
                {{ $seance->heure_fin ? \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') : 'N/A' }}
            </div>
            @if($seance->salle)
                <div><i class="fas fa-door-open text-muted me-1"></i>{{ $seance->salle }}</div>
            @endif
            <div><i class="fas fa-calendar text-muted me-1"></i>
                {{ $seance->getDateCompleteFormattee() }}
            </div>
        </div>
    </td>
    <td>
        @if($hasAttendance)
            <div class="small">
                <div>{{ $attendance->validated_at?->format('d/m/Y') ?? $attendance->created_at?->format('d/m/Y') }}</div>
                <div class="text-muted">{{ $attendance->validated_at?->format('H:i') ?? $attendance->created_at?->format('H:i') }}</div>
            </div>
        @else
            <div class="small text-muted">
                <div>Pas d'émargement</div>
            </div>
        @endif
    </td>
    <td>
        @if($seance->emploiTemps?->is_active)
            <span class="badge bg-success">
                <i class="fas fa-check me-1"></i>Actif
            </span>
        @else
            <span class="badge bg-secondary">
                <i class="fas fa-pause me-1"></i>Inactif
            </span>
        @endif
    </td>
    <td>
        <div class="seance-actions-wrapper">
            <!-- Badges de statut -->
            <div class="seance-status-badges">
                @if($attendanceStatus === 'present')
                    <span class="badge bg-success">
                        <i class="fas fa-check me-1"></i>Présent
                    </span>
                @elseif($attendanceStatus === 'late')
                    <span class="badge bg-warning">
                        <i class="fas fa-clock me-1"></i>En retard
                    </span>
                @elseif($attendanceStatus === 'absent')
                    <span class="badge bg-danger">
                        <i class="fas fa-user-times me-1"></i>Absent
                    </span>
                @elseif($attendanceStatus === 'not_signed')
                    <span class="badge bg-danger">
                        <i class="fas fa-times me-1"></i>Non émargé
                    </span>
                @else
                    <span class="badge bg-secondary">
                        <i class="fas fa-question me-1"></i>{{ ucfirst($attendanceStatus) }}
                    </span>
                @endif
            </div>

            <!-- Boutons d'action rapide (pour coordinateur/admin) -->
            {{-- DEBUG: Temporairement sans restriction de rôle --}}
            <div class="seance-quick-actions d-flex gap-1 mt-1">
                @if($attendanceStatus !== 'present')
                <button type="button"
                        class="btn btn-sm btn-outline-success mark-status-btn"
                        data-seance-id="{{ $seance->id }}"
                        data-status="present"
                        data-type="start"
                        title="Marquer présent">
                    <i class="fas fa-check"></i>
                </button>
                @endif

                @if($attendanceStatus !== 'absent')
                <button type="button"
                        class="btn btn-sm btn-outline-danger mark-status-btn"
                        data-seance-id="{{ $seance->id }}"
                        data-status="absent"
                        data-type="start"
                        title="Marquer absent">
                    <i class="fas fa-user-times"></i>
                </button>
                @endif
            </div>

            <!-- Spinner de chargement -->
            <div class="seance-actions-spinner d-none">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>
    </td>
    <td>
        <div class="btn-group btn-group-sm">
            @if($hasAttendance)
                <button type="button" class="btn btn-outline-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#detailModal{{ $seance->id }}"
                        title="Voir détails émargement">
                    <i class="fas fa-eye"></i>
                </button>
            @endif
            <a href="{{ route('esbtp.seances-cours.show', $seance->id) }}"
               class="btn btn-outline-info btn-sm"
               title="Voir la séance">
                <i class="fas fa-calendar-day"></i>
            </a>
        </div>
    </td>
</tr>
