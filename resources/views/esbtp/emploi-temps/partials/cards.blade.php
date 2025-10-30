@forelse($emploisTemps as $emploiTemps)
    <div class="emploi-card {{ $emploiTemps->is_active ? 'active' : '' }}">
        <div class="emploi-card-header">
            <h6 class="emploi-card-title">
                <i class="fas fa-calendar-alt me-2"></i>
                {{ $emploiTemps->titre ?? 'Emploi du temps' }}
            </h6>
            <div class="emploi-status-badges">
                @if($emploiTemps->is_active)
                    <span class="badge-moderne success">Actif</span>
                @else
                    <span class="badge-moderne secondary">Inactif</span>
                @endif
                @if(optional($emploiTemps)->is_current)
                    <span class="badge-moderne info">Courant</span>
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
