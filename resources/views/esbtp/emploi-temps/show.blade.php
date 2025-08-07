@extends('layouts.app')

@section('title', 'Emploi du temps - ' . (is_object($emploiTemps) && is_object($emploiTemps->classe) ? $emploiTemps->classe->name : 'Non défini') . ' - ESBTP-yAKRO')

@section('styles')
<style>
    .timetable-container {
        overflow-x: auto;
    }

    .timetable {
        min-width: 900px;
    }

    .timetable th, .timetable td {
        min-width: 150px;
        height: 60px;
        position: relative;
    }

    .time-column {
        width: 80px;
        font-weight: bold;
        background-color: #f8f9fa;
    }

    .session-cell {
        padding: 5px;
        border-radius: 4px;
        font-size: 0.85rem;
        color: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* Styles pour les séances qui durent plus d'une heure */
    .session-long {
        position: relative;
        z-index: 10;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transform: scale(1.02);
        transition: transform 0.2s;
    }

    .session-long:hover {
        transform: scale(1.05);
        z-index: 20;
    }

    .session-cours {
        background-color: #3498db;
    }

    .session-td {
        background-color: #2ecc71;
    }

    .session-tp {
        background-color: #9b59b6;
    }

    .session-examen {
        background-color: #e74c3c;
    }

    .session-autre {
        background-color: #f39c12;
    }

    /* Nouveaux styles pour les pauses et déjeuners */
    .session-pause {
        background-color: #95a5a6;
    }

    .session-dejeuner {
        background-color: #e67e22;
    }

    .session-info {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .session-matiere {
        font-weight: bold;
        font-size: 0.9rem;
    }

    .session-enseignant {
        font-size: 0.8rem;
        opacity: 0.9;
    }

    .session-details {
        font-size: 0.75rem;
        opacity: 0.8;
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

    .session-inactive {
        opacity: 0.6;
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
    }

    .btn-add-session:hover {
        background-color: rgba(0,0,0,0.05);
        color: #343a40;
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

    .seance-list-item {
        border-left: 4px solid #3498db;
    }

    .seance-list-item.td {
        border-left-color: #2ecc71;
    }

    .seance-list-item.tp {
        border-left-color: #9b59b6;
    }

    .seance-list-item.examen {
        border-left-color: #e74c3c;
    }

    .seance-list-item.autre {
        border-left-color: #f39c12;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Emploi du temps : {{ $emploiTemps->titre ?? 'Non défini' }}</h5>
                    <div>
                        <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                        </a>
                        <div class="btn-group">
                            <a href="{{ route('esbtp.emploi-temps.export-pdf', ['emploi_temp' => $emploiTemps->id]) }}" class="btn btn-danger me-2" target="_blank">
                                <i class="fas fa-file-pdf me-1"></i>Générer PDF
                            </a>
                            <a href="{{ route('esbtp.emploi-temps.edit', ['emploi_temp' => $emploiTemps->id]) }}" class="btn btn-warning me-2">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </a>
                            <a href="{{ route('esbtp.seances-cours.create', ['emploi_temps_id' => $emploiTemps->id]) }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Ajouter une séance
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Section Planification Académique -->
                    @if(isset($planificationData) && !empty($planificationData))
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-primary shadow-lg" style="border-width: 2px !important;">
                                    <div class="card-header bg-primary text-white py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">
                                                    <i class="fas fa-calendar-check me-2"></i>
                                                    <strong>Planification Académique</strong>
                                                </h6>
                                                <small class="opacity-75">Suivi des heures planifiées pour cette classe</small>
                                            </div>
                                            <div>
                                                @if($planificationData['planifications_configurees'])
                                                    <span class="badge bg-success fs-6 px-3 py-2">
                                                        <i class="fas fa-check-circle me-1"></i>Configurée
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Non configurée
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-body p-3">
                                        @if($planificationData['planifications_configurees'])
                                            <div class="row">
                                                <div class="col-lg-8">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-hover mb-0">
                                                            <thead class="table-dark">
                                                                <tr>
                                                                    <th width="35%">Matière</th>
                                                                    <th width="25%">Enseignant</th>
                                                                    <th width="15%" class="text-center">H. Total</th>
                                                                    <th width="15%" class="text-center">H. Restantes</th>
                                                                    <th width="10%" class="text-center">%</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($planificationData['matieres_planifiees'] as $matiere)
                                                                <tr>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 25px; height: 25px; flex-shrink: 0;">
                                                                                <i class="fas fa-book" style="font-size: 10px;"></i>
                                                                            </div>
                                                                            <strong>{{ $matiere['matiere']->name }}</strong>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        @if($matiere['enseignant_principal'])
                                                                            <small><i class="fas fa-user-tie text-secondary me-1"></i>{{ $matiere['enseignant_principal']->name }}</small>
                                                                        @else
                                                                            <small class="text-muted"><i class="fas fa-user-slash me-1"></i>Non assigné</small>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-primary">{{ $matiere['volume_horaire_total'] }}h</span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-{{ $matiere['heures_restantes'] > 0 ? 'success' : 'warning' }}">
                                                                            {{ $matiere['heures_restantes'] }}h
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <small><strong>{{ $matiere['pourcentage_utilise'] ?? 0 }}%</strong></small>
                                                                    </td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-lg-4">
                                                    <div class="card bg-light border-0">
                                                        <div class="card-body text-center">
                                                            <h6 class="text-primary"><i class="fas fa-chart-pie me-2"></i>Résumé</h6>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <h4 class="text-primary mb-1">{{ $planificationData['heures_totales'] }}</h4>
                                                                    <small>H. planifiées</small>
                                                                </div>
                                                                <div class="col-6">
                                                                    <h4 class="text-success mb-1">{{ $planificationData['heures_restantes'] }}</h4>
                                                                    <small>H. restantes</small>
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <small class="text-muted">
                                                                <i class="fas fa-graduation-cap me-1"></i>{{ $emploiTemps->classe->name }}<br>
                                                                <i class="fas fa-calendar me-1"></i>{{ $emploiTemps->semestre ?? 'Année complète' }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-warning mb-0">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                                    <div>
                                                        <h6><strong>Planification non configurée</strong></h6>
                                                        <p class="mb-2">{{ $planificationData['message_configuration'] ?? 'Aucune planification académique configurée pour cette classe.' }}</p>
                                                        <small class="text-muted">Vous devez d'abord définir les volumes horaires des matières pour cette classe.</small>
                                                        @if(isset($planificationData['lien_configuration']))
                                                            <a href="{{ $planificationData['lien_configuration'] }}" class="btn btn-warning btn-sm" target="_blank">
                                                                <i class="fas fa-clock me-1"></i>Configurer les volumes horaires
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Attention</h5>
                            <p>{{ session('warning') }}</p>
                            @if (session('show_force_delete'))
                                <hr>
                                <div class="d-flex justify-content-end">
                                    @if(auth()->user()->hasRole('superAdmin') && auth()->user()->can('delete_timetables'))
                                    <form action="{{ route('esbtp.emploi-temps.destroy', ['emploi_temp' => $emploiTemps->id]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="force_delete" value="1">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash me-1"></i>Confirmer la suppression forcée
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            @endif
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="border-start border-primary ps-3">
                                <h6 class="text-primary">Informations sur l'emploi du temps</h6>
                                <p class="mb-1"><strong>Classe :</strong> {{ is_object($emploiTemps) && is_object($emploiTemps->classe) ? $emploiTemps->classe->name : 'Non définie' }}</p>
                                <p class="mb-1"><strong>Filière :</strong> {{ is_object($emploiTemps) && is_object($emploiTemps->classe) && is_object($emploiTemps->classe->filiere) ? $emploiTemps->classe->filiere->name : 'Non définie' }}</p>
                                <p class="mb-1"><strong>Niveau :</strong> {{ is_object($emploiTemps) && is_object($emploiTemps->classe) && is_object($emploiTemps->classe->niveau) ? $emploiTemps->classe->niveau->name : 'Non défini' }}</p>
                                <p class="mb-1"><strong>Année universitaire :</strong> {{ is_object($emploiTemps) && is_object($emploiTemps->annee) ? $emploiTemps->annee->name : 'Non définie' }}</p>
                                <p class="mb-1">
                                    <strong>Période :</strong>
                                    @if(isset($emploiTemps->semestre) && $emploiTemps->semestre == 'Semestre 1')
                                        Semestre 1
                                    @elseif(isset($emploiTemps->semestre) && $emploiTemps->semestre == 'Semestre 2')
                                        Semestre 2
                                    @else
                                        Année complète
                                    @endif
                                </p>
                                <p class="mb-1">
                                    <strong>Statut :</strong>
                                    @if(isset($emploiTemps->is_active) && $emploiTemps->is_active)
                                        <span class="badge bg-success">Actif</span>
                                        @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                                        <form action="{{ route('esbtp.emploi-temps.update', ['emploi_temp' => $emploiTemps->id]) }}" method="POST" class="d-inline ms-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_active" value="0">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Êtes-vous sûr de vouloir désactiver cet emploi du temps ?')">
                                                <i class="fas fa-toggle-off me-1"></i>Désactiver
                                            </button>
                                        </form>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                        @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                                        <form action="{{ route('esbtp.emploi-temps.update', ['emploi_temp' => $emploiTemps->id]) }}" method="POST" class="d-inline ms-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_active" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Êtes-vous sûr de vouloir activer cet emploi du temps ? Cela désactivera tous les autres emplois du temps pour cette classe.')">
                                                <i class="fas fa-toggle-on me-1"></i>Activer
                                            </button>
                                        </form>
                                        @endif
                                    @endif
                                    @if(isset($emploiTemps->is_current) && $emploiTemps->is_current)
                                        <span class="badge bg-info ms-1">Courant</span>
                                    @else
                                        @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                                        <form action="{{ route('esbtp.emploi-temps.set-current', ['id' => $emploiTemps->id]) }}" method="POST" class="d-inline ms-2">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-info" onclick="return confirm('Êtes-vous sûr de vouloir définir cet emploi du temps comme courant ? Cela désactivera tous les autres emplois du temps pour cette classe.')">
                                                <i class="fas fa-calendar-check me-1"></i>Définir comme courant
                                            </button>
                                        </form>
                                        @endif
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border-start border-info ps-3">
                                <h6 class="text-info">Statistiques des séances</h6>
                                <p class="mb-1"><strong>Nombre total de séances :</strong> {{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->count() : '0' }}</p>
                                <p class="mb-1">
                                    <strong>Types de séances :</strong>
                                    <span class="badge bg-secondary">{{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->where('type_seance', 'cours')->count() : '0' }} cours</span>
                                    <span class="badge bg-secondary">{{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->where('type_seance', 'td')->count() : '0' }} TD</span>
                                    <span class="badge bg-secondary">{{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->where('type_seance', 'tp')->count() : '0' }} TP</span>
                                    <span class="badge bg-secondary">{{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->where('type_seance', 'examen')->count() : '0' }} examens</span>
                                </p>
                                <p class="mb-1"><strong>Séances actives :</strong> {{ is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances->where('is_active', 1)->count() : '0' }}</p>
                                <p class="mb-1"><strong>Séances par matière :</strong></p>
                                <div style="max-height: 100px; overflow-y: auto;">
                                    @if(isset($matiereStats) && is_array($matiereStats))
                                        @foreach($matiereStats as $matiere => $count)
                                            <small>{{ $matiere }}: {{ $count }} séance(s)</small><br>
                                        @endforeach
                                    @else
                                        <small>Aucune donnée disponible</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                    $typeLabels = [
                        'course' => 'Cours magistral',
                        'homework' => 'Devoir',
                        'break' => 'Récréation',
                        'lunch' => 'Pause déjeuner',
                        'autre' => 'Autre',
                    ];
                    $typeColors = [
                        'course' => '#3498db',
                        'homework' => '#2ecc71',
                        'break' => '#f39c12',
                        'lunch' => '#e74c3c',
                        'autre' => '#95a5a6',
                    ];
                    $legendOrder = ['course', 'homework', 'break', 'lunch', 'autre'];
                    @endphp

                    <div class="mb-3">
                        <h6>Légende :</h6>
                        <div class="d-flex flex-wrap">
                            @foreach($legendOrder as $type)
                                <div class="legend-item" style="margin-right: 15px; align-items: center; display: flex;">
                                    <div class="legend-color" style="width: 15px; height: 15px; border-radius: 3px; margin-right: 5px; background-color: {{ $typeColors[$type] }};"></div>
                                    <small>{{ $typeLabels[$type] }}</small>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="timetable-container mb-4">
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
                                @php
                                // Définir les créneaux horaires
                                $timeSlots = isset($timeSlots) && is_array($timeSlots) ? $timeSlots : [];

                                // Créer une grille pour suivre les cellules occupées par des rowspans
                                $occupiedCells = [];
                                foreach ($days as $day) {
                                    foreach ($timeSlots as $slotIndex => $slot) {
                                        $occupiedCells[$day][$slotIndex] = false;
                                    }
                                }

                                // Pré-traiter les séances pour déterminer les rowspans
                                $seancesWithRowspans = [];
                                if (isset($emploiTemps) && $emploiTemps->seances) {
                                    foreach ($emploiTemps->seances as $seance) {
                                        $jour = $seance->jour;
                                        $heureDebut = $seance->heure_debut->format('H:i');
                                        $heureFin = $seance->heure_fin->format('H:i');

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
                                                // Log pour debug
                                                \Log::info('Affichage séance', [
                                                    'id' => $seanceToDisplay->id,
                                                    'type' => $seanceToDisplay->type,
                                                    'label' => $seanceToDisplay->getSessionTypeText(),
                                                    'color' => $seanceToDisplay->color,
                                                ]);
                                            @endphp
                                            <td class="align-middle" rowspan="{{ $rowspan }}">
                                                <div class="session-cell"
                                                     style="background-color: {{ $seanceToDisplay->color }}"
                                                     data-bs-toggle="tooltip"
                                                     data-bs-placement="top"
                                                     title="{{ $seanceToDisplay->getSessionDescription() }}">
                                                    @if($seanceToDisplay->isBreak() || $seanceToDisplay->isLunch())
                                                        <div class="session-info session-matiere text-center fw-bold">
                                                            {{ $seanceToDisplay->getSessionTypeText() }}
                                                        </div>
                                                    @elseif($seanceToDisplay->isHomework())
                                                        <div class="session-info session-matiere fw-bold">
                                                            {{ $seanceToDisplay->getSessionTypeText() }} : {{ $seanceToDisplay->matiere->name ?? 'Matière non définie' }}
                                                        </div>
                                                        @if($seanceToDisplay->homework_due_date)
                                                            <div class="session-info session-details">
                                                                À rendre le {{ $seanceToDisplay->homework_due_date->format('d/m/Y') }}
                                                            </div>
                                                        @endif
                                                    @elseif($seanceToDisplay->isCourse())
                                                        <div class="session-info session-matiere fw-bold">
                                                            {{ $seanceToDisplay->matiere->name ?? 'Matière non définie' }}
                                                    </div>
                                                    <div class="session-info session-enseignant">
                                                            {{ $seanceToDisplay->teacher ? $seanceToDisplay->teacher->name : 'Non défini' }}
                                                    </div>
                                                    <div class="session-info session-details">
                                                            {{ $seanceToDisplay->salle ?? 'Salle non définie' }}
                                                        </div>
                                                    @else
                                                        <div class="session-info session-matiere text-center fw-bold">
                                                            {{ $seanceToDisplay->getSessionTypeText() }}
                                                        </div>
                                                    @endif
                                                    <div class="session-info session-details mt-1">
                                                        {{ $seanceToDisplay->heure_debut->format('H:i') }} - {{ $seanceToDisplay->heure_fin->format('H:i') }}
                                                    </div>
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
                                                <a href="{{ route('esbtp.seances-cours.create', ['emploi_temps_id' => $emploiTemps->id ?? 0, 'jour' => $day, 'heure_debut' => $timeSlot]) }}" class="btn-add-session">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h6>Liste des séances</h6>
                            <div class="list-group">
                                @if(isset($emploiTemps) && is_object($emploiTemps) && is_object($emploiTemps->seances) && $emploiTemps->seances->count() > 0)
                                    @foreach($emploiTemps->seances->sortBy('jour')->sortBy('heure_debut') as $seance)
                                        <div class="list-group-item seance-list-item"
                                             style="border-left: 4px solid {{ $seance->color ?? '#3498db' }};">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    @if($seance->isBreak() || $seance->isLunch())
                                                        {{ $seance->getSessionTypeText() }}
                                                    @elseif($seance->isHomework())
                                                        {{ $seance->getSessionTypeText() }} : {{ $seance->matiere->name ?? 'Matière non définie' }}
                                                    @elseif($seance->isCourse())
                                                        {{ $seance->matiere->name ?? 'Matière non définie' }}
                                                    @else
                                                        {{ $seance->getSessionTypeText() }}
                                                    @endif
                                                    <span class="badge bg-secondary">{{ $seance->getSessionTypeText() }}</span>
                                                </h6>
                                                <small>
                                                    {{ $seance->getNomJour() }}, {{ $seance->heure_debut->format('H:i') }} - {{ $seance->heure_fin->format('H:i') }}
                                                </small>
                                            </div>
                                            <p class="mb-1">
                                                        @if($seance->isBreak() || $seance->isLunch())
                                                            <!-- Rien d'autre à afficher -->
                                                        @elseif($seance->isHomework())
                                                            <strong>À rendre le :</strong>
                                                            {{ $seance->homework_due_date ? $seance->homework_due_date->format('d/m/Y') : 'Non définie' }}
                                                        @elseif($seance->isCourse())
                                                            <strong>Enseignant :</strong> {{ $seance->teacher ? $seance->teacher->name : 'Non défini' }} |
                                                <strong>Salle :</strong> {{ $seance->salle ?? 'Non définie' }}
                                                        @endif
                                            </p>
                                            <div class="d-flex justify-content-end">
                                                <a href="{{ route('esbtp.seances-cours.edit', $seance->id) }}" class="btn btn-sm btn-warning me-2">
                                                    <i class="fas fa-edit me-1"></i>Modifier
                                                </a>
                                                <form action="{{ route('esbtp.seances-cours.destroy', $seance->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette séance ?')">
                                                        <i class="fas fa-trash me-1"></i>Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="list-group-item">
                                        <p class="mb-0 text-muted">Aucune séance n'a été ajoutée à cet emploi du temps.</p>
                                    </div>
                                @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser les tooltips Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endsection
