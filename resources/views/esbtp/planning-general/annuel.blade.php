@extends('layouts.app')

@section('title', 'Calendrier Académique')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .calendrier-academique {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        color: white;
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
    }
    
    .calendrier-academique::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.1;
    }
    
    .calendrier-header {
        position: relative;
        z-index: 1;
        text-align: center;
    }
    
    .calendrier-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: var(--space-sm);
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    .calendrier-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: var(--space-lg);
    }
    
    .timeline-evenements {
        display: flex;
        flex-direction: column;
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .evenement-timeline {
        display: flex;
        align-items: center;
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        box-shadow: var(--shadow-card);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .evenement-timeline:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    
    .evenement-timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--couleur-evenement);
    }
    
    .evenement-date {
        min-width: 120px;
        text-align: center;
        margin-right: var(--space-lg);
    }
    
    .evenement-jour {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1;
    }
    
    .evenement-mois {
        font-size: 0.9rem;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .evenement-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-circle);
        background: var(--couleur-evenement);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-right: var(--space-lg);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .evenement-contenu {
        flex: 1;
    }
    
    .evenement-titre {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .evenement-description {
        color: var(--text-secondary);
        line-height: 1.5;
    }
    
    .evenement-badge {
        margin-left: auto;
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 0.8rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: var(--couleur-evenement);
        color: white;
    }
    
    /* Couleurs par type d'événement */
    .evenement-timeline[data-type="rentree"] { --couleur-evenement: #10b981; }
    .evenement-timeline[data-type="orientation"] { --couleur-evenement: #3b82f6; }
    .evenement-timeline[data-type="examens"] { --couleur-evenement: #f59e0b; }
    .evenement-timeline[data-type="vacances"] { --couleur-evenement: #6b7280; }
    .evenement-timeline[data-type="reprise"] { --couleur-evenement: #10b981; }
    .evenement-timeline[data-type="soutenances"] { --couleur-evenement: #5e91de; }
    .evenement-timeline[data-type="ceremonie"] { --couleur-evenement: #f59e0b; }
    .evenement-timeline[data-type="fermeture"] { --couleur-evenement: #374151; }
    
    .calendrier-interactif {
        background: var(--surface);
        border-radius: var(--radius-large);
        overflow: hidden;
        box-shadow: var(--shadow-card);
        margin-bottom: var(--space-xl);
        max-width: 100%;
        width: 100%;
        box-sizing: border-box;
    }
    
    .calendrier-controls {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: var(--space-lg);
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
    }
    
    .calendrier-nav {
        display: flex;
        align-items: center;
        gap: var(--space-lg);
    }
    
    .nav-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 16px;
    }
    
    .nav-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }
    
    .nav-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    .mois-actuel {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 var(--space-md);
        min-width: 200px;
        text-align: center;
    }
    
    .calendrier-info {
        font-size: 0.9rem;
        opacity: 0.9;
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }
    
    .calendrier-viewport {
        position: relative;
        overflow: hidden;
        height: 300px;
        max-width: 100%;
        width: 100%;
        box-sizing: border-box;
    }
    
    .calendrier-slider {
        display: flex;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
    }
    
    .calendrier-slide {
        min-width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .calendrier-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        height: 100%;
        width: 100%;
        box-sizing: border-box;
    }
    
    .jour-header {
        background: var(--background-muted);
        padding: var(--space-sm);
        text-align: center;
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text-secondary);
        border-bottom: 2px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .jour-cellule {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        border: 1px solid var(--border-color);
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        background: var(--surface);
        min-height: 35px;
        max-width: 100%;
        overflow: hidden;
        box-sizing: border-box;
    }
    
    .jour-cellule:hover {
        background: rgba(var(--primary-rgb), 0.1);
        transform: scale(1.02);
    }
    
    .jour-cellule.autre-mois {
        color: var(--text-muted);
        background: var(--background-muted);
        opacity: 0.5;
    }
    
    .jour-cellule.aujourd-hui {
        background: var(--primary);
        color: white;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
    }
    
    .jour-cellule.avec-evenement {
        background: linear-gradient(135deg, rgba(var(--warning-rgb), 0.1), rgba(var(--warning-rgb), 0.05));
        color: var(--warning);
        font-weight: 600;
        border-left: 3px solid var(--warning);
    }
    
    .jour-cellule.avec-evenement::after {
        content: '';
        position: absolute;
        bottom: 4px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 8px;
        background: var(--warning);
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(var(--warning-rgb), 0.4);
    }
    
    .jour-cellule.avec-evenement.multiple::after {
        background: linear-gradient(45deg, var(--warning), var(--danger));
        animation: pulse 2s infinite;
    }
    
    .evenement-preview {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--info);
        opacity: 0.7;
    }
    
    .calendrier-loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: var(--text-muted);
        font-size: 1.2rem;
    }
    
    .calendrier-shortcuts {
        padding: var(--space-md);
        background: var(--background-muted);
        display: flex;
        justify-content: center;
        gap: var(--space-sm);
        border-top: 1px solid var(--border-color);
    }
    
    .shortcut-btn {
        padding: var(--space-xs) var(--space-sm);
        border: none;
        background: transparent;
        color: var(--text-secondary);
        border-radius: var(--radius-small);
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.8rem;
    }
    
    .shortcut-btn:hover {
        background: var(--primary);
        color: white;
    }
    
    .shortcut-btn.active {
        background: var(--primary);
        color: white;
    }
    
    @keyframes pulse {
        0%, 100% { transform: translateX(-50%) scale(1); }
        50% { transform: translateX(-50%) scale(1.2); }
    }
    
    @media (max-width: 768px) {
        .calendrier-controls {
            flex-direction: column;
            gap: var(--space-md);
        }
        
        .calendrier-nav {
            order: 2;
        }
        
        .mois-actuel {
            font-size: 1.2rem;
            margin: 0;
        }
        
        .calendrier-info {
            order: 3;
            justify-content: center;
        }
        
        .calendrier-viewport {
            height: 280px;
        }
        
        .jour-cellule {
            min-height: 30px;
            font-size: 0.75rem;
        }
    }
    
    /* Très petits écrans */
    @media (max-width: 480px) {
        .calendrier-viewport {
            height: 240px;
        }
        
        .jour-cellule {
            min-height: 25px;
            font-size: 0.7rem;
        }
        
        .jour-header {
            font-size: 0.75rem;
            padding: 0.25rem;
        }
        
        .mois-actuel {
            font-size: 1.2rem;
            min-width: 150px;
        }
        
        .nav-btn {
            width: 35px;
            height: 35px;
            font-size: 0.9rem;
        }
    }

    /* Forcer le calendrier à rester dans ses limites */
    .calendrier-interactif * {
        box-sizing: border-box;
    }
    
    .calendrier-grid {
        min-width: 0;
        table-layout: fixed;
        width: 100%;
    }
    
    .calendrier-interactif .jour-cellule {
        min-width: 0;
        word-wrap: break-word;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex-shrink: 1;
    }
    
    .calendrier-interactif .jour-header {
        min-width: 0;
        word-wrap: break-word;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex-shrink: 1;
    }
    
    /* Assurer que le conteneur global ne déborde pas */
    .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
    }
    
    .stats-academiques {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .stat-academique {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        text-align: center;
        box-shadow: var(--shadow-card);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-academique::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--couleur-stat);
    }
    
    .stat-academique:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-circle);
        background: var(--couleur-stat);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin: 0 auto var(--space-md);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .stat-nombre {
        font-size: 2.2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .stat-libelle {
        color: var(--text-secondary);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-academique[data-type="total"] { --couleur-stat: #3b82f6; }
    .stat-academique[data-type="examens"] { --couleur-stat: #f59e0b; }
    .stat-academique[data-type="ceremonies"] { --couleur-stat: #0453cb; }
    .stat-academique[data-type="vacances"] { --couleur-stat: #10b981; }
    
    .legende-academique {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-lg);
        justify-content: center;
        padding: var(--space-lg);
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        margin-bottom: var(--space-xl);
    }
    
    .legende-item {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        font-size: 0.9rem;
    }
    
    .legende-couleur {
        width: 16px;
        height: 16px;
        border-radius: var(--radius-small);
    }
    
    @media (max-width: 768px) {
        .calendrier-title {
            font-size: 2rem;
        }
        
        .evenement-timeline {
            flex-direction: column;
            text-align: center;
        }
        
        .evenement-date {
            margin-right: 0;
            margin-bottom: var(--space-md);
        }
        
        .evenement-icon {
            margin-right: 0;
            margin-bottom: var(--space-md);
        }
        
        .calendrier-mensuel {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header et navigation du planning -->
        <x-planning-header 
            title="Planning Annuel" 
            subtitle="Calendrier académique et répartition annuelle des cours"
            active-tab="annuel"
            :annee-selectionnee="$anneeSelectionnee"
            :annees="$annees"
        />

        <!-- Header avec design modernisé -->
        <div class="calendrier-academique">
            <div class="calendrier-header">
                <h1 class="calendrier-title">📅 Calendrier Académique</h1>
                <p class="calendrier-subtitle">Année {{ $anneeSelectionnee->name }}</p>
            </div>
        </div>


        <!-- Statistiques mensuelles avec vraies données -->
        @if(!empty($statistiquesMensuelles))
        <div class="stats-academiques">
            @foreach(array_slice($statistiquesMensuelles, 0, 3) as $index => $stat)
            <div class="stat-academique" data-type="{{ $index === 0 ? 'total' : ($index === 1 ? 'examens' : 'ceremonies') }}">
                <div class="stat-icon">
                    <i class="fas fa-{{ $index === 0 ? 'calendar-check' : ($index === 1 ? 'clock' : 'graduation-cap') }}"></i>
                </div>
                <div class="stat-nombre">{{ $stat['total_seances'] }}</div>
                <div class="stat-libelle">Séances - {{ $stat['mois'] }}</div>
                <div class="text-muted mt-1" style="font-size: 0.8rem;">
                    {{ $stat['total_heures'] }}h • {{ $stat['total_planifications'] }} planifications
                </div>
            </div>
            @endforeach
            
            <div class="stat-academique" data-type="vacances">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-nombre">{{ count($evenementsAcademiques) }}</div>
                <div class="stat-libelle">Événements Majeurs</div>
                <div class="text-muted mt-1" style="font-size: 0.8rem;">
                    {{ collect($evenementsAcademiques)->where('type', 'examens')->count() }} examens • 
                    {{ collect($evenementsAcademiques)->whereIn('type', ['ceremonie', 'rentree'])->count() }} cérémonies
                </div>
                <div class="mt-2">
                    <a href="{{ route('esbtp.evenements-academiques.index', ['annee_id' => $anneeSelectionnee->id]) }}" 
                       class="btn btn-sm btn-primary">
                        <i class="fas fa-cog me-1"></i>Gérer
                    </a>
                </div>
            </div>
        </div>
        @else
        <!-- Statistiques académiques de base -->
        <div class="stats-academiques">
            <div class="stat-academique" data-type="total">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-nombre">{{ count($evenementsAcademiques) }}</div>
                <div class="stat-libelle">Événements Majeurs</div>
            </div>
            
            <div class="stat-academique" data-type="examens">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-nombre">{{ collect($evenementsAcademiques)->where('type', 'examens')->count() }}</div>
                <div class="stat-libelle">Périodes d'Examens</div>
            </div>
            
            <div class="stat-academique" data-type="ceremonies">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-nombre">{{ collect($evenementsAcademiques)->whereIn('type', ['ceremonie', 'rentree'])->count() }}</div>
                <div class="stat-libelle">Cérémonies</div>
            </div>
            
            <div class="stat-academique" data-type="vacances">
                <div class="stat-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="stat-nombre">{{ collect($evenementsAcademiques)->where('type', 'vacances')->count() }}</div>
                <div class="stat-libelle">Périodes de Vacances</div>
            </div>
        </div>
        @endif

        <!-- Timeline des événements académiques -->
        <div class="card-moderne mb-xl">
            <div class="p-lg">
                <div class="section-title mb-lg">
                    <i class="fas fa-star me-2"></i>
                    Événements Académiques Majeurs
                </div>
                
                <div class="timeline-evenements">
                    @foreach($evenementsAcademiques as $evenement)
                    <div class="evenement-timeline" data-type="{{ $evenement['type'] }}">
                        <div class="evenement-date">
                            <div class="evenement-jour">{{ \Carbon\Carbon::createFromFormat('d/m/Y', $evenement['date'])->format('d') }}</div>
                            <div class="evenement-mois">{{ \Carbon\Carbon::createFromFormat('d/m/Y', $evenement['date'])->translatedFormat('M Y') }}</div>
                        </div>
                        
                        <div class="evenement-icon">
                            <i class="fas fa-{{ $evenement['icon'] }}"></i>
                        </div>
                        
                        <div class="evenement-contenu">
                            <div class="evenement-titre">{{ $evenement['titre'] }}</div>
                            <div class="evenement-description">{{ $evenement['description'] }}</div>
                        </div>
                        
                        <div class="evenement-badge">
                            {{ ucfirst($evenement['type']) }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Légende du calendrier -->
        <div class="legende-academique">
            <div class="legende-item">
                <div class="legende-couleur" style="background: var(--primary);"></div>
                <span>Aujourd'hui</span>
            </div>
            <div class="legende-item">
                <div class="legende-couleur" style="background: rgba(var(--warning-rgb), 0.3);"></div>
                <span>Événements académiques</span>
            </div>
            <div class="legende-item">
                <div class="legende-couleur" style="background: #10b981;"></div>
                <span>Rentrée / Reprise</span>
            </div>
            <div class="legende-item">
                <div class="legende-couleur" style="background: #f59e0b;"></div>
                <span>Examens</span>
            </div>
            <div class="legende-item">
                <div class="legende-couleur" style="background: #0453cb;"></div>
                <span>Cérémonies</span>
            </div>
            <div class="legende-item">
                <div class="legende-couleur" style="background: #6b7280;"></div>
                <span>Vacances</span>
            </div>
        </div>

        <!-- Calendrier interactif modernisé -->
        <div class="calendrier-interactif">
            <div class="calendrier-controls">
                <div class="calendrier-nav">
                    <button class="nav-btn" id="prevMonth">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="mois-actuel" id="currentMonth">
                        {{ $calendrierMensuel[0]['nom'] ?? 'Chargement...' }}
                    </div>
                    <button class="nav-btn" id="nextMonth">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                
                <div class="calendrier-info">
                    <span id="monthEvents">0 événement</span>
                    <span>•</span>
                    <span id="monthProgress">0%</span>
                </div>
            </div>
            
            <div class="calendrier-viewport">
                <div class="calendrier-slider" id="calendarSlider">
                    @foreach($calendrierMensuel as $index => $moisData)
                    <div class="calendrier-slide" data-month="{{ $index }}" data-month-id="{{ $moisData['mois'] }}">
                        <div class="calendrier-grid">
                            <!-- En-têtes des jours -->
                            <div class="jour-header">Lun</div>
                            <div class="jour-header">Mar</div>
                            <div class="jour-header">Mer</div>
                            <div class="jour-header">Jeu</div>
                            <div class="jour-header">Ven</div>
                            <div class="jour-header">Sam</div>
                            <div class="jour-header">Dim</div>
                            
                            @foreach($moisData['semaines'] as $semaine)
                                @foreach($semaine as $jour)
                                @php
                                    $dateJour = $jour['date']->format('d/m/Y');
                                    $evenementsJour = collect($evenementsAcademiques)->filter(function($evt) use ($dateJour) {
                                        return $evt['date'] === $dateJour;
                                    });
                                    $aEvenement = $evenementsJour->count() > 0;
                                    $multipleEvenements = $evenementsJour->count() > 1;
                                @endphp
                                <div class="jour-cellule
                                    {{ !$jour['dans_mois'] ? 'autre-mois' : '' }}
                                    {{ $jour['est_aujourd_hui'] ? 'aujourd-hui' : '' }}
                                    {{ $aEvenement ? 'avec-evenement' : '' }}
                                    {{ $multipleEvenements ? 'multiple' : '' }}"
                                    title="{{ $jour['date']->format('d/m/Y') }}{{ $aEvenement ? ' - ' . $evenementsJour->count() . ' événement' . ($evenementsJour->count() > 1 ? 's' : '') : '' }}"
                                    data-date="{{ $dateJour }}"
                                    data-events="{{ $evenementsJour->count() }}">
                                    {{ $jour['date']->day }}
                                    @if($aEvenement)
                                        <div class="evenement-preview"></div>
                                    @endif
                                </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <div class="calendrier-loading" id="calendarLoading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    Chargement...
                </div>
            </div>
            
            <div class="calendrier-shortcuts">
                <button class="shortcut-btn" data-action="today">Aujourd'hui</button>
                <button class="shortcut-btn" data-action="rentree">Rentrée</button>
                <button class="shortcut-btn" data-action="examens">Examens</button>
                <button class="shortcut-btn" data-action="ceremonie">Cérémonie</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Variables du calendrier interactif
    const slider = $('#calendarSlider');
    const prevBtn = $('#prevMonth');
    const nextBtn = $('#nextMonth');
    const currentMonthDisplay = $('#currentMonth');
    const monthEventsDisplay = $('#monthEvents');
    const monthProgressDisplay = $('#monthProgress');
    
    let currentMonthIndex = 0;
    const totalMonths = $('.calendrier-slide').length;
    const evenements = @json($evenementsAcademiques);
    
    // Fonction pour obtenir les noms des mois
    function getMonthNames() {
        return @json(array_column($calendrierMensuel, 'nom'));
    }
    
    const monthNames = getMonthNames();
    
    // Fonction pour mettre à jour l'affichage du mois
    function updateMonthDisplay() {
        const monthName = monthNames[currentMonthIndex] || 'Mois inconnu';
        currentMonthDisplay.text(monthName);
        
        // Calculer les événements du mois actuel
        const currentSlide = $(`.calendrier-slide[data-month="${currentMonthIndex}"]`);
        const monthId = currentSlide.data('month-id');
        
        const monthEvents = evenements.filter(evt => {
            const eventMonth = evt.date.split('/').reverse().join('-').substring(0, 7);
            return eventMonth === monthId;
        });
        
        const eventCount = monthEvents.length;
        monthEventsDisplay.text(eventCount + ' événement' + (eventCount > 1 ? 's' : ''));
        
        // Calculer le pourcentage de progression dans l'année
        const progress = Math.round((currentMonthIndex / (totalMonths - 1)) * 100);
        monthProgressDisplay.text(progress + '%');
        
        // Mettre à jour les boutons de navigation
        prevBtn.prop('disabled', currentMonthIndex === 0);
        nextBtn.prop('disabled', currentMonthIndex === totalMonths - 1);
    }
    
    // Fonction pour naviguer vers un mois
    function navigateToMonth(index, animate = true) {
        if (index < 0 || index >= totalMonths) return;
        
        currentMonthIndex = index;
        const translateX = -index * 100;
        
        if (animate) {
            slider.css('transform', `translateX(${translateX}%)`);
        } else {
            slider.css({
                'transform': `translateX(${translateX}%)`,
                'transition': 'none'
            });
            setTimeout(() => {
                slider.css('transition', 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)');
            }, 50);
        }
        
        updateMonthDisplay();
    }
    
    // Gestionnaires d'événements pour la navigation
    prevBtn.on('click', function() {
        if (currentMonthIndex > 0) {
            navigateToMonth(currentMonthIndex - 1);
        }
    });
    
    nextBtn.on('click', function() {
        if (currentMonthIndex < totalMonths - 1) {
            navigateToMonth(currentMonthIndex + 1);
        }
    });
    
    // Gestionnaires pour les raccourcis
    $('.shortcut-btn').on('click', function() {
        const action = $(this).data('action');
        
        // Retirer la classe active de tous les boutons
        $('.shortcut-btn').removeClass('active');
        $(this).addClass('active');
        
        switch(action) {
            case 'today':
                // Naviguer vers le mois actuel
                const today = new Date();
                const currentYear = today.getFullYear();
                const currentMonth = today.getMonth();
                
                // Trouver l'index du mois actuel
                let targetIndex = 0;
                $('.calendrier-slide').each(function(index) {
                    const monthId = $(this).data('month-id');
                    if (monthId) {
                        const [year, month] = monthId.split('-');
                        if (parseInt(year) === currentYear && parseInt(month) === currentMonth + 1) {
                            targetIndex = index;
                            return false;
                        }
                    }
                });
                navigateToMonth(targetIndex);
                break;
                
            case 'rentree':
                // Naviguer vers le mois de la rentrée (septembre)
                navigateToMonth(0);
                break;
                
            case 'examens':
                // Naviguer vers les examens (décembre)
                const examensMonth = monthNames.findIndex(name => name.toLowerCase().includes('décembre'));
                if (examensMonth !== -1) {
                    navigateToMonth(examensMonth);
                }
                break;
                
            case 'ceremonie':
                // Naviguer vers la cérémonie (juin)
                navigateToMonth(totalMonths - 1);
                break;
        }
        
        // Retirer la classe active après un délai
        setTimeout(() => {
            $(this).removeClass('active');
        }, 1000);
    });
    
    // Gestion des touches du clavier
    $(document).on('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            prevBtn.click();
        } else if (e.key === 'ArrowRight') {
            nextBtn.click();
        }
    });
    
    // Gestion du swipe sur mobile
    let startX = 0;
    let endX = 0;
    
    $('.calendrier-viewport').on('touchstart', function(e) {
        startX = e.originalEvent.touches[0].clientX;
    });
    
    $('.calendrier-viewport').on('touchmove', function(e) {
        e.preventDefault();
    });
    
    $('.calendrier-viewport').on('touchend', function(e) {
        endX = e.originalEvent.changedTouches[0].clientX;
        const diff = startX - endX;
        
        if (Math.abs(diff) > 50) {
            if (diff > 0) {
                nextBtn.click();
            } else {
                prevBtn.click();
            }
        }
    });
    
    // Améliorer les tooltips des jours avec événements
    $('.jour-cellule.avec-evenement').each(function() {
        const date = $(this).data('date');
        const eventCount = $(this).data('events');
        
        if (eventCount > 0) {
            const dayEvents = evenements.filter(evt => evt.date === date);
            let tooltipText = `${date}\\n`;
            
            dayEvents.forEach(evt => {
                tooltipText += `• ${evt.titre}\\n`;
            });
            
            $(this).attr('title', tooltipText);
            
            // Améliorer le tooltip avec Bootstrap si disponible
            if (typeof $.fn.tooltip !== 'undefined') {
                $(this).tooltip({
                    placement: 'top',
                    html: true,
                    title: function() {
                        const dayEvents = evenements.filter(evt => evt.date === date);
                        let html = `<strong>${date}</strong><br>`;
                        dayEvents.forEach(evt => {
                            html += `<small>• ${evt.titre}</small><br>`;
                        });
                        return html;
                    }
                });
            }
        }
    });
    
    // Animation des événements de la timeline
    $('.evenement-timeline').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateX(-50px)'
        });
        
        setTimeout(() => {
            $(this).css({
                'opacity': '1',
                'transform': 'translateX(0)',
                'transition': 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)'
            });
        }, index * 150);
    });
    
    // Effet de survol sur les statistiques
    $('.stat-academique').hover(
        function() {
            $(this).find('.stat-icon').css('transform', 'scale(1.1)');
        },
        function() {
            $(this).find('.stat-icon').css('transform', 'scale(1)');
        }
    );
    
    // Initialisation
    updateMonthDisplay();
    
    // Animation d'entrée du calendrier
    $('.calendrier-interactif').css({
        'opacity': '0',
        'transform': 'translateY(30px)'
    });
    
    setTimeout(() => {
        $('.calendrier-interactif').css({
            'opacity': '1',
            'transform': 'translateY(0)',
            'transition': 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)'
        });
    }, 500);
});
</script>
@endpush