@props(['seances' => collect(), 'emploiTemps' => null, 'timeSlots' => [], 'days' => []])

@php
    // Définir les créneaux horaires par défaut si non fournis
    $defaultTimeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
    $timeSlots = empty($timeSlots) ? $defaultTimeSlots : $timeSlots;
    
    // Définir les jours par défaut si non fournis
    $defaultDays = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $days = empty($days) ? $defaultDays : $days;
    
    // Mapping jour numérique -> nom du jour (1 = lundi, 2 = mardi, etc.)
    $jourMapping = [
        1 => 'lundi',
        2 => 'mardi', 
        3 => 'mercredi',
        4 => 'jeudi',
        5 => 'vendredi',
        6 => 'samedi',
        0 => 'dimanche', // Au cas où
        7 => 'dimanche'  // Au cas où
    ];
    
    // Mapping inverse nom du jour -> numérique
    $dayToNumber = array_flip($jourMapping);
    
    // Créer une grille pour suivre les cellules occupées par des rowspans
    $occupiedCells = [];
    foreach ($days as $day) {
        foreach ($timeSlots as $slotIndex => $slot) {
            $occupiedCells[$day][$slotIndex] = false;
        }
    }

    // Pré-traiter les séances pour déterminer les rowspans
    $seancesWithRowspans = [];
    if ($seances && $seances->count() > 0) {
        foreach ($seances as $seance) {
            // Convertir le jour numérique en nom de jour
            $jourNumeric = $seance->jour;
            $jour = isset($jourMapping[$jourNumeric]) ? $jourMapping[$jourNumeric] : null;
            
            if (!$jour) continue; // Skip si on ne peut pas mapper le jour
            
            $heureDebut = $seance->heure_debut ? $seance->heure_debut->format('H:i') : null;
            $heureFin = $seance->heure_fin ? $seance->heure_fin->format('H:i') : null;

            if (!$heureDebut || !$heureFin) continue;

            // Trouver l'index du créneau de début
            $startSlotIndex = array_search($heureDebut, $timeSlots);
            if ($startSlotIndex === false) continue;

            // Calculer combien de créneaux cette séance occupe
            $endSlotIndex = null;
            foreach ($timeSlots as $index => $slot) {
                // Check if this is the last slot
                $nextSlotIndex = $index + 1;
                $nextSlot = isset($timeSlots[$nextSlotIndex]) ? $timeSlots[$nextSlotIndex] : null;

                // If this is the last slot or the end time is before the next slot starts
                if ($nextSlot === null || $heureFin <= $nextSlot) {
                    if ($index >= $startSlotIndex) {
                        $endSlotIndex = $index;
                        break; // Found the ending slot, no need to continue
                    }
                }
            }

            if ($endSlotIndex === null) $endSlotIndex = $startSlotIndex;

            // Calculer le rowspan
            $rowspan = $endSlotIndex - $startSlotIndex + 1;
            if ($rowspan < 1) $rowspan = 1;

            // Stocker les informations
            $seancesWithRowspans[] = [
                'seance' => $seance,
                'jour' => $jour,
                'startSlotIndex' => $startSlotIndex,
                'endSlotIndex' => $endSlotIndex,
                'rowspan' => $rowspan
            ];

            // Marquer les cellules comme occupées
            for ($i = $startSlotIndex; $i <= $endSlotIndex; $i++) {
                $occupiedCells[$jour][$i] = true;
            }
        }
    }
@endphp

<div class="main-card mb-4">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-calendar-week"></i>
            Grille horaire
        </div>
        <div class="main-card-subtitle">Emploi du temps de la semaine</div>
    </div>
    <div class="main-card-body">

        <!-- Légende -->
        <div class="mb-4">
            <h6 class="mb-3"><i class="fas fa-palette me-2"></i>Légende des couleurs :</h6>
            <div class="d-flex flex-wrap gap-3">
                <div class="legend-item">
                    <div class="legend-color" style="background-color: var(--primary);"></div>
                    <small>Cours</small>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: var(--success);"></div>
                    <small>Devoirs</small>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: var(--warning);"></div>
                    <small>Récréations</small>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: var(--info);"></div>
                    <small>Pause déjeuner</small>
                </div>
            </div>
        </div>

        <!-- Grille horaire -->
        <div class="timetable-container">
            <table class="table table-bordered timetable">
                <thead>
                    <tr>
                        <th class="text-center time-column">Heure</th>
                        <th class="text-center">Lundi</th>
                        <th class="text-center">Mardi</th>
                        <th class="text-center">Mercredi</th>
                        <th class="text-center">Jeudi</th>
                        <th class="text-center">Vendredi</th>
                        <th class="text-center">Samedi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($timeSlots as $slotIndex => $timeSlot)
                    <tr>
                        <td class="text-center time-column">{{ $timeSlot }}</td>
                        @foreach($days as $day)
                            @php
                            // Vérifier si cette cellule est occupée par un rowspan d'une ligne précédente
                            $cellOccupied = $occupiedCells[$day][$slotIndex];

                            // Trouver la séance à afficher dans cette cellule (s'il y en a une)
                            $seanceToDisplay = null;
                            $rowspan = 1;

                            foreach ($seancesWithRowspans as $seanceData) {
                                if ($seanceData['jour'] == $day && $seanceData['startSlotIndex'] == $slotIndex) {
                                    $seanceToDisplay = $seanceData['seance'];
                                    $rowspan = $seanceData['rowspan'];
                                    break;
                                }
                            }
                            @endphp

                            @if($seanceToDisplay && $cellOccupied)
                                @php
                                    // Déterminer le type de séance pour l'affichage
                                    $typeSeance = $seanceToDisplay->type ?? 'course';
                                    $isBreakType = in_array($typeSeance, ['break', 'lunch']);
                                    
                                    // Mapping des couleurs par type (selon les constantes du modèle)
                                    $colorsByType = [
                                        'course' => 'var(--primary)',      // Bleu pour les cours
                                        'homework' => 'var(--success)',    // Vert pour les devoirs
                                        'break' => 'var(--warning)',       // Orange pour les récréations
                                        'lunch' => 'var(--info)'           // Cyan pour les pauses déjeuner
                                    ];
                                    
                                    $backgroundColor = $colorsByType[$typeSeance] ?? $seanceToDisplay->color ?? 'var(--primary)';
                                @endphp

                                <td class="align-middle" rowspan="{{ $rowspan }}">
                                    <div class="session-cell"
                                         style="background-color: {{ $backgroundColor }}"
                                         data-bs-toggle="tooltip"
                                         data-bs-placement="top"
                                         title="{{ $seanceToDisplay->matiere->name ?? 'Séance' }} - {{ $seanceToDisplay->heure_debut->format('H:i') }} à {{ $seanceToDisplay->heure_fin->format('H:i') }}">
                                        
                                        <!-- Type de séance -->
                                        @php
                                            $typeLabels = [
                                                'course' => 'COURS',
                                                'homework' => 'DEVOIR', 
                                                'break' => 'RÉCRÉATION',
                                                'lunch' => 'PAUSE'
                                            ];
                                            $typeLabel = $typeLabels[$typeSeance] ?? 'COURS';
                                        @endphp
                                        <div class="session-info session-type fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                            {{ $typeLabel }}
                                        </div>
                                        
                                        @if(!$isBreakType)
                                            <!-- Matière (seulement pour cours/devoirs) -->
                                            @if($seanceToDisplay->matiere)
                                                <div class="session-info session-matiere fw-bold">
                                                    {{ $seanceToDisplay->matiere->name }}
                                                </div>
                                            @endif
                                            
                                            <!-- Enseignant (seulement pour cours/devoirs) -->
                                            @if($seanceToDisplay->enseignant ?? $seanceToDisplay->teacher)
                                                <div class="session-info session-enseignant">
                                                    <i class="fas fa-user-tie me-1" style="font-size: 0.6rem;"></i>
                                                    {{ $seanceToDisplay->enseignant->name ?? $seanceToDisplay->teacher->name }}
                                                </div>
                                            @endif
                                        @endif
                                        
                                        <!-- Horaire (pour tous types) -->
                                        <div class="session-info session-time" style="font-size: 0.7rem;">
                                            <i class="fas fa-clock me-1" style="font-size: 0.6rem;"></i>
                                            {{ $seanceToDisplay->heure_debut->format('H:i') }} - {{ $seanceToDisplay->heure_fin->format('H:i') }}
                                        </div>
                                        
                                        <!-- Salle (pour tous types) -->
                                        @if($seanceToDisplay->salle)
                                            <div class="session-info session-salle" style="font-size: 0.7rem;">
                                                <i class="fas fa-door-open me-1" style="font-size: 0.6rem;"></i>
                                                {{ $seanceToDisplay->salle }}
                                            </div>
                                        @endif
                                        
                                        <div class="session-actions mt-1">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('esbtp.seances-cours.edit', $seanceToDisplay->id) }}" class="btn btn-sm btn-light">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('esbtp.seances-cours.destroy', $seanceToDisplay->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette séance ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            @elseif(!$cellOccupied)
                                <td>
                                    @if($emploiTemps)
                                        @php
                                            // Convertir le nom du jour en numérique pour le lien
                                            $dayNumber = isset($dayToNumber[$day]) ? $dayToNumber[$day] : 1;
                                        @endphp
                                        <a href="{{ route('esbtp.seances-cours.create', ['emploi_temps_id' => $emploiTemps->id, 'jour' => $dayNumber, 'heure_debut' => $timeSlot]) }}" class="btn-add-session">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                    @else
                                        <div class="btn-add-session" style="cursor: not-allowed; opacity: 0.5;">
                                            <i class="fas fa-plus"></i>
                                        </div>
                                    @endif
                                </td>
                            @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.timetable-container {
    overflow-x: auto;
}

.timetable {
    min-width: 900px;
}

.timetable th, .timetable td {
    min-width: 150px;
    height: 80px;
    position: relative;
    vertical-align: middle;
}

.time-column {
    width: 80px;
    font-weight: bold;
    background-color: #f8f9fa;
}

.session-cell {
    padding: 8px;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #fff;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    position: relative;
}

.session-info {
    line-height: 1.2;
    margin-bottom: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.session-type {
    font-size: 0.7rem !important;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
    background: rgba(255,255,255,0.2);
    padding: 1px 4px;
    border-radius: 3px;
    text-align: center;
}

.session-matiere {
    font-weight: bold;
    font-size: 0.85rem;
}

.session-enseignant {
    font-size: 0.75rem;
    opacity: 0.9;
}

.session-time, .session-salle {
    font-size: 0.7rem;
    opacity: 0.85;
}

.session-actions {
    position: absolute;
    top: 5px;
    right: 5px;
    display: none;
}

.session-cell:hover .session-actions {
    display: block;
}

.btn-add-session {
    border: 2px dashed #dee2e6;
    background-color: rgba(0,0,0,0.02);
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-add-session:hover {
    background-color: rgba(0,0,0,0.05);
    color: #343a40;
    text-decoration: none;
}

.legend-item {
    display: inline-flex;
    align-items: center;
    margin-right: 15px;
}

.legend-color {
    width: 15px;
    height: 15px;
    border-radius: 3px;
    margin-right: 5px;
}
</style>