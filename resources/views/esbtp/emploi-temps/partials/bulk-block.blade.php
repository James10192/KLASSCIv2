<div class="main-card mb-4 bulk-emploi-temps-block" data-emploi-temps-id="{{ $emploiTemps->id }}">
    <div class="main-card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="main-card-title">
                <i class="fas fa-calendar-alt"></i>
                {{ $emploiTemps->titre ?? 'Emploi du temps' }}
            </div>
            <div class="text-muted small">
                {{ $emploiTemps->classe->name ?? 'Classe non définie' }}
                @if($emploiTemps->date_debut && $emploiTemps->date_fin)
                    · {{ \Carbon\Carbon::parse($emploiTemps->date_debut)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($emploiTemps->date_fin)->format('d/m/Y') }}
                @endif
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($emploiTemps->is_current)
                <span class="badge bg-success">Actuel</span>
            @elseif($emploiTemps->is_active)
                <span class="badge bg-info">Actif</span>
            @else
                <span class="badge bg-secondary">Inactif</span>
            @endif
            <a href="{{ route('esbtp.emploi-temps.show', ['emploi_temp' => $emploiTemps->id]) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                <i class="fas fa-external-link-alt me-1"></i>Ouvrir
            </a>
        </div>
    </div>
    <div class="main-card-body">
        @if(!empty($planificationData) && ($planificationData['planifications_configurees'] ?? false))
            <x-emploi-temps.planification-section
                :planificationData="$planificationData"
                :emploiTemps="$emploiTemps" />
        @elseif(!empty($planificationData))
            <div class="alert alert-warning mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <strong>Planification non configurée</strong>
                        <div class="small text-muted">{{ $planificationData['message_configuration'] ?? 'Veuillez configurer les volumes horaires pour cette classe.' }}</div>
                    </div>
                </div>
            </div>
        @endif

        <x-emploi-temps.grille-horaire
            :seances="$emploiTemps->seances ?? collect()"
            :emploiTemps="$emploiTemps"
            :timeSlots="$timeSlots"
            :days="$days"
            :interactive="true" />

        <x-emploi-temps.liste-seances
            :seances="$emploiTemps->seances ?? collect()"
            :emploiTemps="$emploiTemps" />
    </div>
</div>
