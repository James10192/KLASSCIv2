@if(!empty($timetableShortcut) && ($timetableShortcut['show'] ?? false))
    <tr class="table-shortcut-row">
        <td colspan="8">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <strong><i class="fas fa-calendar-exclamation me-2"></i>Raccourci emplois du temps</strong>
                    <div class="text-muted small">
                        @if($timetableShortcut['missing'] > 0)
                            {{ $timetableShortcut['missing'] }} classe(s) sans emploi du temps
                        @endif
                        @if($timetableShortcut['expired'] > 0)
                            {{ $timetableShortcut['missing'] > 0 ? ' • ' : '' }}{{ $timetableShortcut['expired'] }} expiré(s)
                        @endif
                        @if($timetableShortcut['expiring_soon'] > 0)
                            {{ ($timetableShortcut['missing'] > 0 || $timetableShortcut['expired'] > 0) ? ' • ' : '' }}{{ $timetableShortcut['expiring_soon'] }} expire(nt) bientôt
                        @endif
                    </div>
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1" data-bs-toggle="modal" data-bs-target="#quickGenerateHelpModal">
                        <i class="fas fa-info-circle me-1"></i>Voir le fonctionnement
                    </button>
                </div>
                @if(auth()->user()->hasAnyPermission(['access_admin', 'can_manage_school']) || auth()->user()->can('create_timetable'))
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#quickGenerateModal">
                        <i class="fas fa-bolt me-1"></i>Créer maintenant
                    </button>
                @endif
            </div>
        </td>
    </tr>
@endif

@forelse($emploisTemps as $emploiTemps)
    @php
        $today = \Carbon\Carbon::today();
        $startDate = $emploiTemps->date_debut ? \Carbon\Carbon::parse($emploiTemps->date_debut) : null;
        $endDate = $emploiTemps->date_fin ? \Carbon\Carbon::parse($emploiTemps->date_fin) : null;
        $isExpired = $endDate && $endDate->lt($today);
        $isUpcoming = $startDate && $startDate->gt($today);
        $isCurrentPeriod = $startDate && $endDate && $today->between($startDate, $endDate);
        $isExpiringSoon = $endDate && $endDate->gte($today) && $endDate->diffInDays($today) <= 3;
    @endphp
    <tr>
        <td class="col-classe">{{ $emploiTemps->classe->name ?? 'Non définie' }}</td>
        <td class="col-filiere">{{ $emploiTemps->classe->filiere->name ?? 'Non définie' }}</td>
        <td class="col-niveau">{{ $emploiTemps->classe->niveau->name ?? 'Non défini' }}</td>
        <td class="col-annee">{{ $emploiTemps->annee->name ?? 'Non définie' }}</td>
        <td class="col-periode">
            @if($emploiTemps->semestre == 'Semestre 1')
                <span class="badge-moderne primary">Semestre 1</span>
            @elseif($emploiTemps->semestre == 'Semestre 2')
                <span class="badge-moderne primary">Semestre 2</span>
            @else
                <span class="badge-moderne primary">Année complète</span>
            @endif
        </td>
        <td class="col-dates">
            @if($emploiTemps->date_debut && $emploiTemps->date_fin)
                <small>
                    {{ \Carbon\Carbon::parse($emploiTemps->date_debut)->format('d/m/Y') }}<br>
                    au {{ \Carbon\Carbon::parse($emploiTemps->date_fin)->format('d/m/Y') }}
                </small>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>
        <td class="col-statut">
            @if($isExpired)
                <span class="badge-moderne danger">Expiré</span>
            @elseif($isCurrentPeriod)
                <span class="badge-moderne success">Actif</span>
            @elseif($isUpcoming)
                <span class="badge-moderne secondary">Inactif</span>
            @else
                <span class="badge-moderne secondary">Inactif</span>
            @endif
            @if($isExpiringSoon && !$isExpired)
                <span class="badge-moderne warning">Expire bientôt</span>
            @endif
        </td>
        <td class="col-actions">
            <div class="btn-group-moderne">
                <a href="{{ route('esbtp.emploi-temps.show', ['emploi_temp' => $emploiTemps->id]) }}" class="btn-moderne info" title="Voir">
                    <i class="fas fa-eye"></i>
                </a>
                @if(auth()->user()->hasAnyPermission(['access_admin', 'can_manage_school']) || auth()->user()->can('edit_timetables'))
                <a href="{{ route('esbtp.emploi-temps.edit', ['emploi_temp' => $emploiTemps->id]) }}" class="btn-moderne warning" title="Modifier">
                    <i class="fas fa-edit"></i>
                </a>
                @endif
                @if(auth()->user()->can('access_admin') && auth()->user()->can('delete_timetables'))
                <button type="button" class="btn-moderne danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $emploiTemps->id }}" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
                @endif
            </div>

            @if(auth()->user()->can('access_admin') && auth()->user()->can('delete_timetables'))
            <!-- Modal de confirmation de suppression -->
            <div class="modal fade" id="deleteModal{{ $emploiTemps->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $emploiTemps->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel{{ $emploiTemps->id }}">Confirmation de suppression</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attention :</strong> Cette action est irréversible.
                            </div>
                            <p>Êtes-vous sûr de vouloir supprimer cet emploi du temps ?</p>
                            <p><strong>Classe :</strong> {{ $emploiTemps->classe->name ?? 'Non définie' }}</p>
                            <p><strong>Année universitaire :</strong> {{ $emploiTemps->annee->name ?? 'Non définie' }}</p>
                            <p class="text-danger"><strong>Attention :</strong> Cette action supprimera également toutes les séances de cours associées à cet emploi du temps.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <form action="{{ route('esbtp.emploi-temps.destroy', ['emploi_temp' => $emploiTemps->id]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash me-2"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center">
            <div class="py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <p class="mb-0">Aucun emploi du temps n'a été créé.</p>
                <a href="{{ route('esbtp.emploi-temps.create') }}" class="btn-acasi primary mt-3">
                    <i class="fas fa-plus-circle me-1"></i>Créer un emploi du temps
                </a>
            </div>
        </td>
    </tr>
@endforelse
