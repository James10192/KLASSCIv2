@if(!isset($date_edition))
@extends('layouts.app')

@section('title', 'Prévisualisation Emploi du Temps - ' . ($emploiTemps->classe->name ?? 'Non défini'))

@section('styles')
@else
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Emploi du Temps - {{ $emploiTemps->classe->name ?? 'Non défini' }}</title>
@endif
@if(!isset($date_edition))
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@else
<!-- Mode PDF - Pas de liens externes -->
@endif
<style>
    /* Variables CSS pour compatibilité */
    :root {
        --primary: #1e3a8a;
        --secondary: #3b82f6;
        --success: #10b981;
        --danger: #ef4444;
        --warning: #f59e0b;
        --surface: #f8fafc;
        --border: #e2e8f0;
        --text-primary: #1f2937;
        --text-secondary: #6b7280;
        --space-sm: 0.5rem;
        --space-md: 1rem;
        --space-lg: 1.5rem;
        --space-xl: 2rem;
        --radius-small: 0.25rem;
        --radius-medium: 0.5rem;
        --radius-large: 0.75rem;
        --shadow-card: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }
    
    .preview-container {
        max-width: 100%;
        margin: 0;
        background: white;
        padding: var(--space-sm);
    }
    
    .preview-toolbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-lg);
        display: flex;
        justify-content: between;
        align-items: center;
        gap: var(--space-md);
    }
    
    .preview-actions {
        display: flex;
        gap: var(--space-sm);
        margin-left: auto;
    }
    
    .preview-content {
        border: 1px solid #ddd;
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        padding: var(--space-lg);
        background: white;
        min-height: 600px;
    }
    
    /* Styles pour l'emploi du temps - format paysage minimal */
    .timetable-document {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 12px;
        line-height: 1.4;
        color: #333;
    }
    
    /* Force toutes les polices pour PDF */
    @if(isset($date_edition))
    * {
        font-family: DejaVu Sans, Arial, sans-serif !important;
    }
    @endif
    
    /* En-tête minimal avec logo et nom école */
    .minimal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--space-lg);
        padding: var(--space-md);
        background: #1e3a8a;
        color: white;
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
    }
    
    .header-left {
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }
    
    .school-logo {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-small);
        object-fit: cover;
        background: white;
        padding: 4px;
    }
    
    .school-info h1 {
        font-size: 18px;
        font-weight: bold;
        margin: 0 0 2px 0;
        color: white;
    }
    
    .school-address {
        font-size: 12px;
        opacity: 0.9;
        margin: 0;
    }
    
    .header-title {
        font-size: 24px;
        font-weight: bold;
        color: white;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .header-info {
        font-size: 14px;
        display: flex;
        gap: var(--space-md);
        flex-direction: column;
        align-items: flex-end;
    }
    
    .info-badge {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        padding: 6px 12px;
        border-radius: var(--radius-small);
        font-size: 12px;
        font-weight: 500;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }


    /* Table emploi du temps - format paysage optimisé avec styles table-moderne */
    .table-moderne {
        min-width: 1000px;
        width: 100%;
        font-size: 11px;
    }

    .timetable {
        min-width: 1000px;
    }

    .timetable th, .timetable td {
        height: 70px;
        position: relative;
        padding: 6px;
        text-align: center;
        vertical-align: middle;
    }

    .timetable th {
        height: 45px;
        font-size: 12px;
    }

    .time-column {
        width: 100px;
        font-weight: bold;
        background-color: var(--surface);
        font-size: 11px;
        color: var(--text-primary);
        writing-mode: horizontal-tb;
    }

    /* Session cells - format compact paysage */
    .session-cell {
        padding: 4px;
        border-radius: var(--radius-small);
        font-size: 10px;
        color: #fff;
        height: calc(100% - 8px);
        display: flex;
        flex-direction: column;
        justify-content: center;
        margin: 1px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        line-height: 1.2;
    }

    .session-matiere {
        font-weight: bold;
        font-size: 11px;
        margin-bottom: 2px;
        text-align: center;
    }

    .session-enseignant {
        font-size: 9px;
        opacity: 0.9;
        margin-bottom: 1px;
        text-align: center;
    }

    .session-details {
        font-size: 8px;
        opacity: 0.8;
        text-align: center;
    }

    /* Couleurs simplifiées pour PDF - pas de gradients */
    .session-cours { background: #1e3a8a; color: white; }
    .session-td { background: #10b981; color: white; }
    .session-tp { background: #6b7280; color: white; }
    .session-examen { background: #ef4444; color: white; }
    .session-autre { background: #f59e0b; color: #1f2937; }
    .session-pause { background: #9ca3af; color: white; }
    .session-dejeuner { background: #f97316; color: #1f2937; }
    
    /* Styles table-moderne intégrés pour PDF */
    .table-moderne {
        overflow-x: auto;
        border-radius: var(--radius-medium);
        border: 1px solid rgba(0, 0, 0, 0.1);
        background: white;
    }
    
    .table-moderne table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }
    
    .table-moderne thead tr {
        background: #1e3a8a;
        color: white;
    }
    
    .table-moderne th {
        padding: var(--space-lg) var(--space-md);
        text-align: left;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table-moderne th.text-center {
        text-align: center;
    }
    
    .table-moderne tbody tr {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .table-moderne td {
        padding: var(--space-lg) var(--space-md);
        vertical-align: middle;
    }

    @media (max-width: 768px) {
        .timetable-container {
            margin-bottom: 20px;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .legend {
            flex-direction: column;
            align-items: center;
        }
        
        .logo-section {
            flex-direction: column;
            text-align: center;
        }
        
        .logo {
            margin: 0 0 15px 0;
        }
    }
    
    @media print {
        .preview-toolbar {
            display: none;
        }
        
        .preview-content {
            border: none;
            box-shadow: none;
        }
        
        .timetable-document {
            padding: 0;
        }
    }
</style>
@if(!isset($date_edition))
@endsection

@section('content')
@else
</head>
<body>
@endif
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="preview-container">
            <!-- Barre d'outils de prévisualisation (masquée pour PDF) -->
            @if(!isset($date_edition))
            <div class="preview-toolbar">
                <div class="toolbar-info">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Prévisualisation Emploi du Temps
                    </h4>
                    <small class="text-muted">{{ $emploiTemps->classe->name ?? 'Non définie' }} - {{ $emploiTemps->annee->name ?? 'Non définie' }}</small>
                </div>
                
                <div class="preview-actions">
                    <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}" class="btn-acasi secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                    
                    <a href="{{ route('esbtp.emploi-temps.export-pdf', $emploiTemps->id) }}" class="btn-acasi success">
                        <i class="fas fa-file-pdf me-1"></i>Générer PDF
                    </a>
                    
                    <button onclick="window.print()" class="btn-acasi info">
                        <i class="fas fa-print me-1"></i>Imprimer
                    </button>
                </div>
            </div>
            @endif

            <!-- Contenu de l'emploi du temps -->
            <div class="preview-content">
                <div class="timetable-document">
                    @php
                        use App\Helpers\SettingsHelper;
                        $schoolName = SettingsHelper::get('school_name', 'École Spéciale du Bâtiment et des Travaux Publics');
                        $schoolAddress = SettingsHelper::get('school_address', 'BP 2541 Yamoussoukro');
                        $schoolCity = SettingsHelper::get('school_city', 'Yamoussoukro');
                        $showLogo = SettingsHelper::get('timetable_show_logo', '1') === '1';
                        $logoPath = SettingsHelper::get('school_logo');
                        
                        $logoBase64 = null;
                        if ($showLogo && $logoPath) {
                            $paths = [
                                storage_path('app/public/' . $logoPath),
                                public_path($logoPath),
                                public_path('images/LOGO-KLASSCI-PNG.png'),
                            ];
                            
                            foreach ($paths as $path) {
                                if (file_exists($path)) {
                                    $imageData = file_get_contents($path);
                                    $extension = pathinfo($path, PATHINFO_EXTENSION);
                                    $logoBase64 = 'data:image/' . $extension . ';base64,' . base64_encode($imageData);
                                    break;
                                }
                            }
                        }
                    @endphp

                    <!-- En-tête avec logo et infos école -->
                    <div class="minimal-header">
                        <div class="header-left">
                            @if($logoBase64)
                                <img src="{{ $logoBase64 }}" alt="Logo École" class="school-logo">
                            @endif
                            <div class="school-info">
                                <h1>{{ $schoolName }}</h1>
                                @if($schoolAddress || $schoolCity)
                                    <p class="school-address">
                                        @if($schoolAddress){{ $schoolAddress }}@endif
                                        @if($schoolAddress && $schoolCity) - @endif
                                        @if($schoolCity){{ $schoolCity }}@endif
                                    </p>
                                @endif
                            </div>
                        </div>
                        
                        <div>
                            <h2 class="header-title">EMPLOI DU TEMPS</h2>
                        </div>
                        
                        <div class="header-info">
                            <span class="info-badge">{{ $emploiTemps->classe->name ?? 'Classe non définie' }}</span>
                            <span class="info-badge">{{ $emploiTemps->annee->name ?? 'Période non définie' }}</span>
                        </div>
                    </div>

                    <!-- Tableau emploi du temps -->
                    <div class="table-moderne">
                        <table class="timetable">
                            <thead>
                                <tr>
                                    <th class="time-column text-center">Heure</th>
                                    <th class="text-center">Lundi</th>
                                    <th class="text-center">Mardi</th>
                                    <th class="text-center">Mercredi</th>
                                    <th class="text-center">Jeudi</th>
                                    <th class="text-center">Vendredi</th>
                                    <th class="text-center">Samedi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timeSlots as $timeIndex => $time)
                                    <tr>
                                        <td class="time-column">{{ $time }}</td>
                                        @foreach($days as $dayIndex => $day)
                                            <td>
                                                @if(isset($seancesParJour[$dayIndex + 1][$timeIndex]))
                                                    @php
                                                        $seance = $seancesParJour[$dayIndex + 1][$timeIndex];
                                                        $typeClass = 'session-' . strtolower($seance->type ?? 'autre');
                                                    @endphp
                                                    <div class="session-cell {{ $typeClass }}">
                                                        <div class="session-matiere">
                                                            {{ $seance->matiere->name ?? 'Matière' }}
                                                        </div>
                                                        <div class="session-enseignant">
                                                            {{ $seance->enseignant_nom ?? 'Enseignant' }}
                                                        </div>
                                                        <div class="session-details">
                                                            {{ ucfirst($seance->type ?? 'Cours') }}
                                                            @if($seance->salle)
                                                                - {{ $seance->salle }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if(!isset($date_edition))
@endsection

@push('scripts')
<script>
// Gérer l'impression
window.addEventListener('beforeprint', function() {
    document.body.classList.add('printing');
});

window.addEventListener('afterprint', function() {
    document.body.classList.remove('printing');
});
</script>
@endpush
@else
</body>
</html>
@endif