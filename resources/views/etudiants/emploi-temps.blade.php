@extends('layouts.app')

@section('title', 'Mon Emploi du Temps')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles spécifiques pour l'emploi du temps */
    .timetable-container {
        --timetable-primary: var(--primary);
        --timetable-secondary: var(--secondary);
        --timetable-surface: var(--surface);
        --timetable-border: rgba(0, 0, 0, 0.08);
    }
    
    
    
    .timetable-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: var(--text-sm);
        background: white;
        border-radius: var(--radius-medium);
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .timetable-table th {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: var(--space-lg) var(--space-md);
        text-align: center;
        font-weight: 700;
        font-size: var(--text-sm);
        border: none;
        position: relative;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 3px solid rgba(255, 255, 255, 0.2);
    }
    
    .timetable-table th:first-child {
        background: linear-gradient(135deg, var(--text-primary), var(--neutral));
        width: 140px;
        text-align: center;
        font-size: var(--text-xs);
    }
    
    .timetable-table th:not(:first-child)::after {
        content: '';
        position: absolute;
        bottom: -3px;
        left: 20%;
        right: 20%;
        height: 2px;
        background: rgba(255, 255, 255, 0.4);
        border-radius: 1px;
    }
    
    .timetable-table td {
        padding: var(--space-xs);
        border: 1px solid rgba(0, 0, 0, 0.05);
        vertical-align: middle;
        text-align: center;
        position: relative;
        height: 90px;
        min-height: 90px;
        max-height: 90px;
        background: #f8f9fa;
        transition: all 0.2s ease;
    }
    
    .timetable-table td:hover {
        background: rgba(var(--primary-rgb), 0.02);
    }
    
    .timetable-table td:first-child {
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08), rgba(var(--secondary-rgb), 0.04));
        font-weight: 700;
        color: var(--primary);
        border-right: 3px solid rgba(var(--primary-rgb), 0.2);
        font-size: var(--text-xs);
        text-align: center;
        position: relative;
    }
    
    .timetable-table td:first-child::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, var(--primary), var(--secondary));
        border-radius: 0 2px 2px 0;
    }
    
    .timetable-table tbody tr:nth-child(even) td:not(:first-child) {
        background: rgba(var(--primary-rgb), 0.01);
    }
    
    .seance-card {
        background: white;
        border: 2px solid rgba(var(--primary-rgb), 0.1);
        border-radius: var(--radius-medium);
        padding: var(--space-sm);
        height: 100%;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: left;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        border-left: 4px solid var(--primary);
    }
    
    .seance-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, 
            rgba(var(--primary-rgb), 0.02) 0%, 
            rgba(var(--text-primary-rgb), 0.01) 100%);
        z-index: 0;
    }
    
    .seance-card > * {
        position: relative;
        z-index: 1;
    }
    
    .seance-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.15);
        border-color: rgba(var(--primary-rgb), 0.3);
    }
    
    .seance-card:hover::before {
        background: linear-gradient(135deg, 
            rgba(var(--primary-rgb), 0.05) 0%, 
            rgba(var(--text-primary-rgb), 0.02) 100%);
    }
    
    .seance-matiere {
        font-weight: 700;
        color: var(--primary);
        font-size: var(--text-sm);
        margin-bottom: var(--space-xs);
        line-height: 1.1;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }
    
    .seance-time {
        color: var(--text-secondary);
        font-size: var(--text-xs);
        margin-bottom: var(--space-xs);
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-weight: 600;
    }
    
    .seance-time i {
        color: var(--primary);
        font-size: var(--text-xs);
    }
    
    .seance-details {
        font-size: var(--text-xs);
        color: var(--text-secondary);
        line-height: 1.2;
    }
    
    .seance-details > div {
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-weight: 500;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }
    
    .seance-details i {
        width: 12px;
        color: var(--neutral);
        font-size: var(--text-xs);
        flex-shrink: 0;
    }
    
    .seance-prof {
        font-weight: 600;
        color: var(--text-primary);
    }
    
    /* Styles différenciés par type de séance */
    .seance-type {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: var(--space-xs);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .seance-type-course {
        border-left-color: #2196F3;
    }
    
    .seance-type-homework {
        border-left-color: #4CAF50;
    }
    
    .seance-type-break {
        border-left-color: #FF9800;
    }
    
    .seance-type-lunch {
        border-left-color: #F44336;
    }
    
    .seance-type-course .seance-type {
        color: #2196F3;
    }
    
    .seance-type-homework .seance-type {
        color: #4CAF50;
    }
    
    .seance-type-break .seance-type {
        color: #FF9800;
    }
    
    .seance-type-lunch .seance-type {
        color: #F44336;
    }
    
    .timetable-footer {
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.05), rgba(var(--secondary-rgb), 0.02));
        padding: var(--space-lg);
        border-top: 1px solid var(--timetable-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-md);
    }
    
    
    .no-timetable-icon {
        width: 80px;
        height: 80px;
        border-radius: var(--radius-circle);
        background: linear-gradient(135deg, var(--warning), var(--secondary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto var(--space-lg);
    }
    
    .no-timetable-title {
        font-size: var(--text-xl);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
    }
    
    .no-timetable-text {
        color: var(--text-secondary);
        font-size: var(--text-base);
        line-height: 1.6;
        margin: 0;
    }
    
    /* Styles uniformes pour les jours de la semaine - Palette bleu/gris */
    .timetable-table th:nth-child(2),
    .timetable-table th:nth-child(3),
    .timetable-table th:nth-child(4),
    .timetable-table th:nth-child(5),
    .timetable-table th:nth-child(6),
    .timetable-table th:nth-child(7) {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }
    
    /* Animation d'apparition en cascade */
    .timetable-table tbody tr {
        animation: slideInUp 0.6s ease-out;
        animation-fill-mode: both;
    }
    
    .timetable-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
    .timetable-table tbody tr:nth-child(2) { animation-delay: 0.2s; }
    .timetable-table tbody tr:nth-child(3) { animation-delay: 0.3s; }
    .timetable-table tbody tr:nth-child(4) { animation-delay: 0.4s; }
    .timetable-table tbody tr:nth-child(5) { animation-delay: 0.5s; }
    .timetable-table tbody tr:nth-child(6) { animation-delay: 0.6s; }
    .timetable-table tbody tr:nth-child(7) { animation-delay: 0.7s; }
    .timetable-table tbody tr:nth-child(8) { animation-delay: 0.8s; }
    .timetable-table tbody tr:nth-child(9) { animation-delay: 0.9s; }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Responsive Design Amélioré */
    @media (max-width: 1024px) {
        .timetable-table {
            font-size: var(--text-xs);
        }
        
        .seance-card {
            padding: var(--space-sm);
        }
        
        .seance-matiere {
            font-size: var(--text-sm);
        }
    }
    
    @media (max-width: 768px) {
        .timetable-table th:first-child {
            width: 100px;
        }
        
        .timetable-table th,
        .timetable-table td {
            padding: var(--space-xs);
        }
        
        .timetable-table td {
            height: 80px;
            min-height: 80px;
        }
        
        .seance-card {
            padding: var(--space-xs);
            min-height: 70px;
        }
        
        .seance-matiere {
            font-size: var(--text-xs);
            font-weight: 700;
        }
        
        .seance-time {
            font-size: var(--text-xs);
        }
        
        .seance-details {
            font-size: var(--text-xs);
        }
    }
    
    @media (max-width: 480px) {
        .timetable-table {
            min-width: 600px;
        }
        
        .section-card-body {
            padding: 0;
        }
        
        .table-responsive {
            margin: 0;
            border-radius: 0;
        }
    }
    
    /* Animation pour l'apparition des éléments */
    .card-moderne {
        animation: fadeInUp 0.6s ease-out;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .seance-card {
        animation: fadeIn 0.8s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi timetable-container">
    <div class="main-content">
        <!-- Header Étudiant Moderne -->
        <div class="student-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1>
                        <i class="fas fa-calendar-alt me-3"></i>
                        Mon Emploi du Temps
                    </h1>
                    <p class="header-subtitle">
                        Consultez votre planning de cours et vos horaires hebdomadaires
                    </p>
                </div>
                @if($emploiTemps)
                    <div class="text-end">
                        <div class="badge" style="background: rgba(255, 255, 255, 0.2); color: white; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-medium); font-size: var(--text-sm);">
                            <i class="fas fa-calendar me-2"></i>
                            Période: {{ \Carbon\Carbon::parse($emploiTemps->date_debut)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($emploiTemps->date_fin)->format('d/m/Y') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if(session('warning'))
            <div class="alert alert-warning" style="margin: var(--space-lg) 0;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('warning') }}
            </div>
        @endif

        @if(isset($inscription) && $inscription)
            <!-- Informations de la classe -->
            <div class="card-moderne mb-lg">
                <div class="section-card-header">
                    <h6 class="section-card-title">
                        <i class="fas fa-graduation-cap"></i>
                        Informations sur ma classe
                    </h6>
                </div>
                <div class="section-card-body">
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-card-value">{{ $inscription->classe->name ?? 'Non définie' }}</div>
                            <div class="info-card-label">
                                <i class="fas fa-school me-1"></i>
                                Classe
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-card-value">{{ $inscription->classe->filiere->name ?? 'Non définie' }}</div>
                            <div class="info-card-label">
                                <i class="fas fa-book me-1"></i>
                                Filière
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-card-value">{{ $inscription->classe->niveauEtude->name ?? 'Non défini' }}</div>
                            <div class="info-card-label">
                                <i class="fas fa-layer-group me-1"></i>
                                Niveau d'études
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-card-value">{{ $inscription->anneeUniversitaire->name ?? $inscription->anneeUniversitaire->libelle ?? 'Non définie' }}</div>
                            <div class="info-card-label">
                                <i class="fas fa-calendar-check me-1"></i>
                                Année universitaire
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($emploiTemps)
            @php
                // ----- Données EDT pour la vue MOBILE (onglets par jour) -----
                // Convention identique à la grille desktop : jour 1=Lundi … 6=Samedi.
                $stuJours = [
                    1 => ['label' => 'Lundi',    'court' => 'Lun'],
                    2 => ['label' => 'Mardi',    'court' => 'Mar'],
                    3 => ['label' => 'Mercredi', 'court' => 'Mer'],
                    4 => ['label' => 'Jeudi',    'court' => 'Jeu'],
                    5 => ['label' => 'Vendredi', 'court' => 'Ven'],
                    6 => ['label' => 'Samedi',   'court' => 'Sam'],
                ];

                $stuSeancesParJour = [];
                foreach (array_keys($stuJours) as $jourNum) {
                    $items = [];
                    if (isset($seancesGroupees[$jourNum])) {
                        foreach ($seancesGroupees[$jourNum] as $s) {
                            $hd = $s->heure_debut;
                            $hf = $s->heure_fin;
                            $hd = ($hd instanceof \DateTime || $hd instanceof \Carbon\Carbon) ? $hd->format('H:i') : substr((string) $hd, 0, 5);
                            $hf = ($hf instanceof \DateTime || $hf instanceof \Carbon\Carbon) ? $hf->format('H:i') : substr((string) $hf, 0, 5);
                            $items[] = [
                                'seance' => $s,
                                'hd' => $hd,
                                'hf' => $hf,
                            ];
                        }
                        // Tri par heure de début pour un affichage chronologique.
                        usort($items, fn ($a, $b) => strcmp($a['hd'], $b['hd']));
                    }
                    $stuSeancesParJour[$jourNum] = $items;
                }

                // Jour actif par défaut = aujourd'hui (Carbon dayOfWeekIso : 1=Lun … 7=Dim).
                $stuToday = (int) now()->dayOfWeekIso;
                $stuDefaultDay = isset($stuJours[$stuToday]) ? $stuToday : 1;
            @endphp

            <!-- ===== Vue MOBILE : onglets par jour (mobile only) ===== -->
            <div class="card-moderne show-mobile stu-edt-mobile" x-data="{ jour: {{ $stuDefaultDay }} }">
                <div class="section-card-header">
                    <h6 class="section-card-title">
                        <i class="fas fa-calendar-day"></i>
                        Emploi du temps - {{ $inscription->classe->name ?? 'Classe non définie' }}
                    </h6>
                </div>
                <div class="section-card-body">
                    <!-- Sélecteur de jours -->
                    <div class="stu-edt-days" role="tablist" aria-label="Jours de la semaine">
                        @foreach($stuJours as $jourNum => $jourInfo)
                            @php $nb = count($stuSeancesParJour[$jourNum]); @endphp
                            <button type="button"
                                    class="stu-edt-day"
                                    :class="jour === {{ $jourNum }} ? 'is-active' : ''"
                                    @click="jour = {{ $jourNum }}"
                                    role="tab"
                                    :aria-selected="jour === {{ $jourNum }} ? 'true' : 'false'">
                                <span>{{ $jourInfo['court'] }}</span>
                                <small>{{ $nb }} {{ $nb > 1 ? 'cours' : 'cours' }}</small>
                                <span class="stu-edt-day-dot {{ $nb === 0 ? 'stu-edt-day-dot--empty' : '' }}"></span>
                            </button>
                        @endforeach
                    </div>

                    <!-- Liste des cours par jour -->
                    @foreach($stuJours as $jourNum => $jourInfo)
                        <div x-show="jour === {{ $jourNum }}" x-cloak role="tabpanel">
                            @if(count($stuSeancesParJour[$jourNum]) === 0)
                                <div class="stu-edt-empty">
                                    <i class="fas fa-mug-hot"></i>
                                    <p>Aucun cours le {{ $jourInfo['label'] }}.</p>
                                </div>
                            @else
                                <div class="stu-edt-list">
                                    @foreach($stuSeancesParJour[$jourNum] as $row)
                                        @php
                                            $s = $row['seance'];
                                            $type = $s->type ?? 'course';
                                            $isLesson = in_array($type, ['course', 'homework']);
                                        @endphp
                                        <div class="stu-edt-item stu-edt-item--{{ $type }}">
                                            <div class="stu-edt-item-head">
                                                <span class="stu-edt-item-time">
                                                    <i class="fas fa-clock"></i>
                                                    {{ $row['hd'] }} - {{ $row['hf'] }}
                                                </span>
                                                <span class="stu-edt-item-type">
                                                    <i class="fas {{ $s->getTypeIcon() }}"></i>
                                                    {{ $s->getSessionTypeText() }}
                                                </span>
                                            </div>

                                            @if($isLesson)
                                                <div class="stu-edt-item-matiere">{{ $s->matiere->name ?? 'Matière non définie' }}</div>
                                            @endif

                                            <div class="stu-edt-item-meta">
                                                @if($s->salle)
                                                    <span><i class="fas fa-door-open"></i> {{ $s->salle }}</span>
                                                @endif
                                                @if($isLesson && $s->teacher)
                                                    <span><i class="fas fa-user-tie"></i> {{ $s->teacher->name ?? 'Enseignant' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- ===== Vue DESKTOP : grille semaine (cachée en mobile) ===== -->
            <div class="card-moderne hide-mobile">
                <div class="section-card-header">
                    <h6 class="section-card-title">
                        <i class="fas fa-table"></i>
                        Emploi du temps - {{ $inscription->classe->name ?? 'Classe non définie' }}
                    </h6>
                </div>
                <div class="section-card-body p-0">
                    <div class="table-responsive">
                    <table class="timetable-table">
                        <thead>
                            <tr>
                                <th>Horaire</th>
                                @foreach(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'] as $index => $jour)
                                    <th>{{ $jour }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Define time slots
                                $timeSlots = [
                                    '08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00',
                                    '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00', '17:00-18:00'
                                ];

                                // Create a grid to track which cells are already occupied by rowspans
                                $occupiedCells = [];
                                foreach (range(1, 6) as $jour) {
                                    foreach ($timeSlots as $slotIndex => $slot) {
                                        $occupiedCells[$jour][$slotIndex] = false;
                                    }
                                }

                                // Pre-process seances to determine rowspans
                                $seancesWithRowspans = [];
                                foreach (range(1, 6) as $jour) {
                                    if (isset($seancesGroupees[$jour])) {
                                        foreach ($seancesGroupees[$jour] as $seance) {
                                            $heureDebut = $seance->heure_debut;
                                            $heureFin = $seance->heure_fin;

                                            if ($heureDebut instanceof \DateTime || $heureDebut instanceof \Carbon\Carbon) {
                                                $heureDebut = $heureDebut->format('H:i');
                                            } else {
                                                $heureDebut = substr($heureDebut, 0, 5);
                                            }

                                            if ($heureFin instanceof \DateTime || $heureFin instanceof \Carbon\Carbon) {
                                                $heureFin = $heureFin->format('H:i');
                                            } else {
                                                $heureFin = substr($heureFin, 0, 5);
                                            }

                                            // Find the starting and ending slot indices
                                            $startSlotIndex = null;
                                            $endSlotIndex = null;

                                            foreach ($timeSlots as $slotIndex => $slot) {
                                                list($slotStart, $slotEnd) = explode('-', $slot);

                                                // Find the first slot that overlaps with the seance
                                                if ($startSlotIndex === null &&
                                                    strtotime($heureDebut) < strtotime($slotEnd) &&
                                                    strtotime($heureFin) > strtotime($slotStart)) {
                                                    $startSlotIndex = $slotIndex;
                                                }

                                                // Find the last slot that overlaps with the seance
                                                if (strtotime($heureDebut) < strtotime($slotEnd) &&
                                                    strtotime($heureFin) > strtotime($slotStart)) {
                                                    $endSlotIndex = $slotIndex;
                                                }
                                            }

                                            if ($startSlotIndex !== null && $endSlotIndex !== null) {
                                                $rowspan = $endSlotIndex - $startSlotIndex + 1;
                                                $seancesWithRowspans[] = [
                                                    'seance' => $seance,
                                                    'jour' => $jour,
                                                    'startSlotIndex' => $startSlotIndex,
                                                    'endSlotIndex' => $endSlotIndex,
                                                    'rowspan' => $rowspan,
                                                    'heureDebut' => $heureDebut,
                                                    'heureFin' => $heureFin
                                                ];

                                                // Mark cells as occupied
                                                for ($i = $startSlotIndex; $i <= $endSlotIndex; $i++) {
                                                    $occupiedCells[$jour][$i] = true;
                                                }
                                            }
                                        }
                                    }
                                }
                            @endphp

                            @foreach($timeSlots as $slotIndex => $horaire)
                                <tr>
                                    <td>{{ $horaire }}</td>
                                    @foreach(range(1, 6) as $jour)
                                        @php
                                            $cellOccupied = false;
                                            $seanceToDisplay = null;
                                            $rowspan = 1;

                                            // Check if this cell is the starting point of a multi-row seance
                                            foreach ($seancesWithRowspans as $seanceData) {
                                                if ($seanceData['jour'] == $jour && $seanceData['startSlotIndex'] == $slotIndex) {
                                                    $cellOccupied = true;
                                                    $seanceToDisplay = $seanceData;
                                                    $rowspan = $seanceData['rowspan'];
                                                    break;
                                                }
                                            }

                                            // Check if this cell is covered by a rowspan from a previous row
                                            if (!$cellOccupied && $occupiedCells[$jour][$slotIndex] && $slotIndex > 0) {
                                                foreach ($seancesWithRowspans as $seanceData) {
                                                    if ($seanceData['jour'] == $jour &&
                                                        $slotIndex > $seanceData['startSlotIndex'] &&
                                                        $slotIndex <= $seanceData['endSlotIndex']) {
                                                        $cellOccupied = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        @endphp

                                        @if($cellOccupied && $seanceToDisplay)
                                            <td rowspan="{{ $rowspan }}" style="padding: var(--space-xs); height: {{ 60 * $rowspan }}px;">
                                                <div class="seance-card seance-type-{{ $seanceToDisplay['seance']->type ?? 'course' }}">
                                                    <!-- Type de séance -->
                                                    <div class="seance-type">
                                                        <i class="fas {{ $seanceToDisplay['seance']->getTypeIcon() }}"></i>
                                                        {{ $seanceToDisplay['seance']->getSessionTypeText() }}
                                                    </div>
                                                    
                                                    @if(in_array($seanceToDisplay['seance']->type ?? 'course', ['course', 'homework']))
                                                        <!-- Matière uniquement pour cours et devoirs -->
                                                        <div class="seance-matiere">{{ $seanceToDisplay['seance']->matiere->name ?? 'Matière non définie' }}</div>
                                                    @endif
                                                    
                                                    <div class="seance-time">
                                                        <i class="fas fa-clock"></i>
                                                        {{ $seanceToDisplay['heureDebut'] }} - {{ $seanceToDisplay['heureFin'] }}
                                                    </div>
                                                    
                                                    <div class="seance-details">
                                                        @if($seanceToDisplay['seance']->salle)
                                                            <div><i class="fas fa-door-open"></i> {{ $seanceToDisplay['seance']->salle }}</div>
                                                        @endif
                                                        
                                                        @if(in_array($seanceToDisplay['seance']->type ?? 'course', ['course', 'homework']) && $seanceToDisplay['seance']->teacher)
                                                            <!-- Professeur uniquement pour cours et devoirs -->
                                                            <div class="seance-prof">
                                                                <i class="fas fa-user-tie"></i> {{ $seanceToDisplay['seance']->teacher->name ?? 'Enseignant' }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        @elseif(!$cellOccupied)
                                            <td></td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
                
                <div class="timetable-footer">
                    <div style="display: flex; align-items: center; gap: var(--space-sm);">
                        <i class="fas fa-info-circle" style="color: var(--primary);"></i>
                        <span style="color: var(--text-secondary); font-size: var(--text-sm);">
                            Dernière mise à jour: {{ now()->format('d/m/Y à H:i') }}
                        </span>
                    </div>
                    <div style="color: var(--text-secondary); font-size: var(--text-sm);">
                        <i class="fas fa-calendar me-1"></i>
                        Année {{ $inscription->anneeUniversitaire->name ?? $inscription->anneeUniversitaire->libelle ?? 'N/A' }}
                    </div>
                </div>
            </div>
        @else
            <!-- Message d'absence d'emploi du temps -->
            <div class="card-moderne">
                <div class="p-lg text-center">
                    <div class="no-timetable-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div class="no-timetable-title">Aucun emploi du temps disponible</div>
                    <p class="no-timetable-text">
                        Aucun emploi du temps n'est actuellement disponible pour votre classe.
                        @if(isset($inscription) && $inscription)
                            <br><strong>Classe actuelle :</strong> {{ $inscription->classe->name ?? 'Non définie' }}
                        @endif
                        <br>Veuillez contacter l'administration si vous pensez qu'il s'agit d'une erreur.
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation d'apparition progressive des séances
        const seanceCards = document.querySelectorAll('.seance-card');
        seanceCards.forEach((card, index) => {
            card.style.animationDelay = `${(index * 0.05) + 1}s`;
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, (index * 50) + 1000);
        });
        
        // Effet hover amélioré
        seanceCards.forEach(card => {
            
            // Interactions hover
            card.addEventListener('mouseenter', function() {
                this.style.zIndex = '10';
                // Ajouter un effet de glow subtil
                this.style.boxShadow = '0 12px 30px rgba(var(--primary-rgb), 0.25), 0 0 20px rgba(var(--primary-rgb), 0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.zIndex = '1';
                this.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.1)';
            });
            
            // Clic pour plus de détails (optionnel)
            card.addEventListener('click', function(e) {
                e.preventDefault();
                showSeanceDetails(this);
            });
        });
        
        // Animation au scroll pour les lignes du tableau
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'slideInUp 0.6s ease-out';
                }
            });
        }, {
            threshold: 0.1
        });
        
        document.querySelectorAll('.timetable-table tbody tr').forEach(row => {
            observer.observe(row);
        });
        
        // Améliorer l'accessibilité
        document.querySelectorAll('.seance-card').forEach(card => {
            card.setAttribute('tabindex', '0');
            card.setAttribute('role', 'button');
            card.setAttribute('aria-label', 'Détails du cours');
            
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
    });
    
    // Fonction pour afficher les détails d'une séance (optionnel)
    function showSeanceDetails(card) {
        const matiere = card.querySelector('.seance-matiere')?.textContent;
        const time = card.querySelector('.seance-time')?.textContent;
        const prof = card.querySelector('.seance-prof')?.textContent;
        
        // Simple notification ou modal
        if (matiere) {
            // Effet visuel de sélection
            card.style.transform = 'scale(0.98)';
            setTimeout(() => {
                card.style.transform = '';
            }, 150);
            
            debugLog(`Cours sélectionné: ${matiere} - ${time} - ${prof}`);
            // Ici vous pourriez ouvrir un modal avec plus de détails
        }
    }
    
    // Optimisation des performances
    let ticking = false;
    function updateOnScroll() {
        if (!ticking) {
            requestAnimationFrame(() => {
                // Optimisations au scroll si nécessaire
                ticking = false;
            });
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', updateOnScroll, { passive: true });
</script>
@endpush