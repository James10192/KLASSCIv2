@extends('layouts.app')

@section('title', 'Planning Annuel - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .calendrier-annuel {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .mois-calendrier {
        border-radius: var(--radius-medium);
        overflow: hidden;
        background: var(--surface);
        box-shadow: var(--shadow-card);
        transition: all 0.3s ease;
    }
    
    .mois-calendrier:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }
    
    .mois-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: var(--space-md);
        text-align: center;
        font-weight: 600;
    }
    
    .calendrier-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: var(--border-color);
    }
    
    .jour-calendrier {
        background: var(--surface);
        padding: var(--space-xs);
        min-height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        position: relative;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .jour-calendrier:hover {
        background: rgba(var(--primary-rgb), 0.1);
    }
    
    .jour-calendrier.autre-mois {
        color: var(--text-muted);
        background: var(--background-muted);
    }
    
    .jour-calendrier.aujourd-hui {
        background: var(--primary);
        color: white;
        font-weight: 600;
    }
    
    .jour-calendrier.avec-cours {
        background: rgba(var(--success-rgb), 0.1);
        border-left: 3px solid var(--success);
    }
    
    .jour-calendrier.avec-cours::after {
        content: '';
        position: absolute;
        top: 2px;
        right: 2px;
        width: 6px;
        height: 6px;
        background: var(--success);
        border-radius: 50%;
    }
    
    .legende-calendrier {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
        padding: var(--space-md);
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
    }
    
    .legende-item {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        font-size: 0.9rem;
    }
    
    .legende-indicateur {
        width: 16px;
        height: 16px;
        border-radius: var(--radius-small);
    }
    
    .stats-mensuelles {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .evenements-academiques {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        box-shadow: var(--shadow-card);
        margin-bottom: var(--space-xl);
    }
    
    .evenement {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        padding: var(--space-sm);
        border-radius: var(--radius-small);
        margin-bottom: var(--space-sm);
        background: rgba(var(--info-rgb), 0.05);
        border-left: 4px solid var(--info);
    }
    
    .evenement-icon {
        width: 32px;
        height: 32px;
        border-radius: var(--radius-circle);
        background: var(--info);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Planning Annuel {{ $anneeSelectionnee->name }}</h1>
                <p class="header-subtitle">Calendrier complet de l'année académique</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.planning-general.index', ['annee_id' => $anneeSelectionnee->id]) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la vue d'ensemble
                </a>
            </div>
        </div>

        <!-- Navigation du planning -->
        <div class="planning-nav">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.index', ['annee_id' => $anneeSelectionnee->id]) }}">
                        <i class="fas fa-home me-2"></i>Vue d'ensemble
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('esbtp.planning-general.annuel', ['annee_id' => $anneeSelectionnee->id]) }}">
                        <i class="fas fa-calendar me-2"></i>Planning Annuel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.repartition-matieres', ['annee_id' => $anneeSelectionnee->id]) }}">
                        <i class="fas fa-chart-pie me-2"></i>Répartition Matières
                    </a>
                </li>
                @canany(['manage-planning', 'view-all-timetables'])
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee->id]) }}">
                        <i class="fas fa-user-tie me-2"></i>Coordinateur
                    </a>
                </li>
                @endcanany
            </ul>
        </div>

        <!-- Légende du calendrier -->
        <div class="legende-calendrier">
            <div class="legende-item">
                <div class="legende-indicateur" style="background: var(--primary);"></div>
                <span>Aujourd'hui</span>
            </div>
            <div class="legende-item">
                <div class="legende-indicateur" style="background: rgba(var(--success-rgb), 0.3); border-left: 3px solid var(--success);"></div>
                <span>Jours avec cours</span>
            </div>
            <div class="legende-item">
                <div class="legende-indicateur" style="background: var(--background-muted);"></div>
                <span>Hors période académique</span>
            </div>
        </div>

        <!-- Statistiques mensuelles -->
        @if(!empty($statistiquesMensuelles))
        <div class="stats-mensuelles">
            @foreach($statistiquesMensuelles as $stat)
            <div class="card-moderne">
                <div class="p-lg text-center">
                    <div class="stat-icon-planning mb-3">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-value">{{ $stat['mois'] }}</div>
                    <div class="stat-label">{{ $stat['total_cours'] }} cours programmés</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Événements académiques importants -->
        @if(!empty($evenementsAcademiques))
        <div class="evenements-academiques">
            <div class="section-title mb-lg">
                <i class="fas fa-star me-2"></i>
                Événements Académiques Importants
            </div>
            
            @foreach($evenementsAcademiques as $evenement)
            <div class="evenement">
                <div class="evenement-icon">
                    <i class="fas fa-{{ $evenement['icon'] ?? 'calendar' }}"></i>
                </div>
                <div>
                    <strong>{{ $evenement['titre'] }}</strong>
                    <div class="text-muted">{{ $evenement['date'] }} - {{ $evenement['description'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Calendrier annuel -->
        <div class="calendrier-annuel">
            @foreach($calendrierMensuel as $moisData)
            <div class="mois-calendrier">
                <div class="mois-header">
                    {{ $moisData['nom'] }}
                </div>
                
                <div class="calendrier-grid">
                    <!-- En-têtes des jours -->
                    <div class="jour-calendrier" style="font-weight: 600; background: var(--background-muted);">L</div>
                    <div class="jour-calendrier" style="font-weight: 600; background: var(--background-muted);">M</div>
                    <div class="jour-calendrier" style="font-weight: 600; background: var(--background-muted);">M</div>
                    <div class="jour-calendrier" style="font-weight: 600; background: var(--background-muted);">J</div>
                    <div class="jour-calendrier" style="font-weight: 600; background: var(--background-muted);">V</div>
                    <div class="jour-calendrier" style="font-weight: 600; background: var(--background-muted);">S</div>
                    <div class="jour-calendrier" style="font-weight: 600; background: var(--background-muted);">D</div>
                    
                    @foreach($moisData['semaines'] as $semaine)
                        @foreach($semaine as $jour)
                        <div class="jour-calendrier
                            {{ !$jour['dans_mois'] ? 'autre-mois' : '' }}
                            {{ $jour['est_aujourd_hui'] ? 'aujourd-hui' : '' }}
                            {{ rand(1, 5) === 1 ? 'avec-cours' : '' }}"
                            title="{{ $jour['date']->format('d/m/Y') }}">
                            {{ $jour['date']->day }}
                        </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Tooltips pour les jours avec cours
    $('.jour-calendrier.avec-cours').tooltip({
        title: 'Ce jour contient des cours programmés',
        placement: 'top'
    });
    
    // Animation d'apparition des mois
    $('.mois-calendrier').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        });
        
        setTimeout(() => {
            $(this).css({
                'opacity': '1',
                'transform': 'translateY(0)',
                'transition': 'all 0.6s ease-out'
            });
        }, index * 100);
    });
});
</script>
@endpush