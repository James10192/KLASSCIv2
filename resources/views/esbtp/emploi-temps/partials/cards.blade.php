@if(!empty($timetableShortcut) && ($timetableShortcut['show'] ?? false))
    <div class="emploi-card emploi-shortcut-card">
        <div class="emploi-card-body">
            <div class="emploi-shortcut-title">
                <i class="fas fa-calendar-exclamation me-2"></i>Raccourci emplois du temps
            </div>
            <div class="emploi-shortcut-meta">
                Créez rapidement les emplois du temps pour les classes sans planning ou en fin de validité.
                <button type="button" class="btn btn-link btn-sm p-0 ms-1" data-bs-toggle="modal" data-bs-target="#quickGenerateHelpModal">
                    <i class="fas fa-info-circle me-1"></i>Voir le fonctionnement
                </button>
            </div>
            <div class="emploi-shortcut-stats">
                @if($timetableShortcut['missing'] > 0)
                    <span class="emploi-shortcut-chip">{{ $timetableShortcut['missing'] }} sans emploi du temps</span>
                @endif
                @if($timetableShortcut['expired'] > 0)
                    <span class="emploi-shortcut-chip">{{ $timetableShortcut['expired'] }} expiré(s)</span>
                @endif
                @if($timetableShortcut['expiring_soon'] > 0)
                    <span class="emploi-shortcut-chip">{{ $timetableShortcut['expiring_soon'] }} expire(nt) bientôt</span>
                @endif
            </div>
            @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->can('create_timetable'))
                <div class="emploi-actions" style="border-top: none; padding-top: 0;">
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#quickGenerateModal">
                        <i class="fas fa-bolt me-1"></i>Créer maintenant
                    </button>
                </div>
            @endif
        </div>
    </div>
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
    <div class="emploi-card {{ $isCurrentPeriod ? 'active' : '' }} {{ $isExpired ? 'expired' : '' }} {{ $isUpcoming ? 'upcoming' : '' }}">
        <div class="emploi-card-header">
            <h6 class="emploi-card-title">
                <i class="fas fa-calendar-alt me-2"></i>
                {{ $emploiTemps->titre ?? 'Emploi du temps' }}
            </h6>
            <div class="emploi-status-badges">
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
            </div>
        </div>

        <div class="emploi-card-body">
            <div class="emploi-info-grid">
                <div class="emploi-info-item">
                    <div class="emploi-info-label">Classe</div>
                    <div class="emploi-info-value">{{ $emploiTemps->classe->name ?? 'Non définie' }}</div>
                </div>
                <div class="emploi-info-item">
                    <div class="emploi-info-label">Filière</div>
                    <div class="emploi-info-value">{{ $emploiTemps->classe->filiere->name ?? 'Non définie' }}</div>
                </div>
                <div class="emploi-info-item">
                    <div class="emploi-info-label">Niveau</div>
                    <div class="emploi-info-value">{{ $emploiTemps->classe->niveau->name ?? 'Non défini' }}</div>
                </div>
                <div class="emploi-info-item">
                    <div class="emploi-info-label">Année</div>
                    <div class="emploi-info-value">{{ Str::limit($emploiTemps->annee->name ?? 'Non définie', 15) }}</div>
                </div>
                <div class="emploi-info-item">
                    <div class="emploi-info-label">Période</div>
                    <div class="emploi-info-value">
                        @if($emploiTemps->semestre == 'Semestre 1')
                            S1
                        @elseif($emploiTemps->semestre == 'Semestre 2')
                            S2
                        @else
                            Année
                        @endif
                    </div>
                </div>
                @if($emploiTemps->date_debut && $emploiTemps->date_fin)
                <div class="emploi-info-item" style="grid-column: 1 / -1;">
                    <div class="emploi-info-label">
                        <i class="fas fa-calendar-day me-1"></i>Dates
                    </div>
                    <div class="emploi-info-value">
                        <small>
                            {{ \Carbon\Carbon::parse($emploiTemps->date_debut)->format('d/m/Y') }}
                            <i class="fas fa-arrow-right mx-1"></i>
                            {{ \Carbon\Carbon::parse($emploiTemps->date_fin)->format('d/m/Y') }}
                        </small>
                    </div>
                </div>
                @endif
            </div>

            <div class="emploi-actions">
                <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}"
                   class="btn btn-sm btn-outline-primary" title="Voir">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="{{ route('esbtp.emploi-temps.edit', $emploiTemps->id) }}"
                   class="btn btn-sm btn-outline-warning" title="Modifier">
                    <i class="fas fa-edit"></i>
                </a>
                <form action="{{ route('esbtp.emploi-temps.destroy', $emploiTemps->id) }}"
                      method="POST" style="display: inline;"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet emploi du temps ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
@empty
    <div class="empty-state-card">
        <div class="text-center py-5">
            <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
            <h5 class="text-muted mb-2">Aucun emploi du temps trouvé</h5>
            <p class="text-muted mb-4">Créez votre premier emploi du temps pour commencer.</p>
            <a href="{{ route('esbtp.emploi-temps.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Créer un emploi du temps
            </a>
        </div>
    </div>
@endforelse
