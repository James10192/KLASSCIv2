@extends('layouts.app')

@section('title', 'Planning Général - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Mobile-First Responsive Design */
    .planning-container {
        container-type: inline-size;
        width: 100%;
    }
    
    /* Modern Typography - Bold & Large (Tendance 2025) */
    .planning-title {
        font-size: clamp(1.75rem, 4vw, 3rem);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: -0.025em;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: var(--space-xl);
    }
    
    /* Improved Time Picker Interface */
    .time-selector-modern {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: var(--space-lg);
        padding: var(--space-xl);
        background: var(--surface);
        border-radius: var(--radius-large);
        box-shadow: var(--shadow-card);
        margin-bottom: var(--space-xl);
    }
    
    .selector-card {
        position: relative;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .selector-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border-color: var(--primary);
    }
    
    .selector-label {
        font-size: var(--text-lg);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .selector-modern {
        width: 100%;
        padding: var(--space-md);
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        background: var(--background);
        font-size: var(--text-base);
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .selector-modern:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }
    
    /* Emotionally Intelligent Design - Status Indicators */
    .status-indicator {
        position: absolute;
        top: var(--space-sm);
        right: var(--space-sm);
        width: 12px;
        height: 12px;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    
    .status-indicator.active { background: var(--success); }
    .status-indicator.pending { background: var(--warning); }
    .status-indicator.inactive { background: var(--danger); }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    /* Smart KPI Cards with Micro-interactions */
    .kpi-grid-modern {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-xl);
        margin: var(--space-xl) 0;
    }
    
    .kpi-card-modern {
        position: relative;
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        box-shadow: var(--shadow-card);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        cursor: pointer;
    }
    
    .kpi-card-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.6s ease;
    }
    
    .kpi-card-modern:hover::before {
        transform: scaleX(1);
    }
    
    .kpi-card-modern:hover {
        transform: translateY(-12px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    }
    
    .kpi-icon-modern {
        width: 64px;
        height: 64px;
        border-radius: var(--radius-circle);
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: var(--space-lg);
        box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.3);
    }
    
    .kpi-value-modern {
        font-size: 3rem;
        font-weight: 900;
        color: var(--text-primary);
        line-height: 1;
        margin-bottom: var(--space-sm);
    }
    
    .kpi-label-modern {
        font-size: var(--text-base);
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .kpi-trend {
        font-size: var(--text-sm);
        font-weight: 500;
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        margin-top: var(--space-sm);
    }
    
    .kpi-trend.positive {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }
    
    .kpi-trend.negative {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }
    
    /* Planning Context Card */
    .context-card {
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05));
        border: 2px solid rgba(var(--primary-rgb), 0.2);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
    }
    
    .context-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), transparent);
        pointer-events: none;
    }
    
    .context-header {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }
    
    .context-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-medium);
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    
    .context-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
    }
    
    .context-item {
        background: rgba(255, 255, 255, 0.8);
        padding: var(--space-md);
        border-radius: var(--radius-medium);
        border-left: 4px solid var(--primary);
    }
    
    .context-item-label {
        font-size: var(--text-sm);
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: var(--space-xs);
    }
    
    .context-item-value {
        font-size: var(--text-lg);
        font-weight: 700;
        color: var(--text-primary);
    }
    
    /* Quick Actions with Improved Accessibility */
    .quick-actions-modern {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--space-xl);
        margin-top: var(--space-xl);
    }
    
    .action-card-modern {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        box-shadow: var(--shadow-card);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
    }
    
    .action-card-modern:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        text-decoration: none;
        color: inherit;
    }
    
    .action-card-modern::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: radial-gradient(circle, rgba(var(--primary-rgb), 0.1), transparent);
        transform: translate(50%, -50%);
        transition: all 0.4s ease;
    }
    
    .action-card-modern:hover::after {
        transform: translate(30%, -30%) scale(1.5);
    }
    
    .action-icon-modern {
        width: 56px;
        height: 56px;
        border-radius: var(--radius-medium);
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: var(--space-lg);
        box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.3);
    }
    
    .action-title-modern {
        font-size: var(--text-xl);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
    }
    
    .action-description-modern {
        font-size: var(--text-base);
        color: var(--text-secondary);
        line-height: 1.6;
    }
    
    /* Container Queries for Advanced Responsiveness */
    @container (max-width: 768px) {
        .time-selector-modern {
            grid-template-columns: 1fr;
            padding: var(--space-lg);
        }
        
        .kpi-grid-modern {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
        
        .context-info {
            grid-template-columns: 1fr;
        }
        
        .quick-actions-modern {
            grid-template-columns: 1fr;
        }
    }
    
    /* Dark Mode Support (Auto-adaptative) */
    @media (prefers-color-scheme: dark) {
        .selector-card {
            background: linear-gradient(135deg, rgba(30, 30, 30, 0.9), rgba(50, 50, 50, 0.7));
        }
        
        .context-item {
            background: rgba(255, 255, 255, 0.1);
        }
    }
    
    /* Reduced Motion Support */
    @media (prefers-reduced-motion: reduce) {
        .kpi-card-modern,
        .action-card-modern,
        .selector-card {
            transition: none;
        }
        
        .status-indicator {
            animation: none;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi planning-container">
    <div class="main-content">
        <!-- Modern Planning Header -->
        <div class="d-flex align-items-center justify-content-between mb-xl">
            <h1 class="planning-title">Planning Général</h1>
            <div class="d-flex gap-md">
                <button class="btn-acasi secondary" onclick="toggleView('calendar')">
                    <i class="fas fa-calendar-alt me-2"></i>Vue Calendrier
                </button>
                <button class="btn-acasi primary" onclick="toggleView('timeline')">
                    <i class="fas fa-clock me-2"></i>Vue Timeline
                </button>
            </div>
        </div>

        <!-- Smart Time Selector -->
        <div class="time-selector-modern">
            <div class="selector-card">
                <div class="status-indicator active"></div>
                <label class="selector-label">
                    <i class="fas fa-calendar-check"></i>
                    Année Universitaire
                </label>
                <select class="selector-modern" id="annee_id_modern" onchange="handleSmartFilter()">
                    <option value="">Sélectionner une année</option>
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id }}" {{ request('annee_id') == $annee->id ? 'selected' : '' }}>
                            {{ $annee->name }}
                            @if(optional($annee)->is_current) 🟢 En cours @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="selector-card">
                <div class="status-indicator {{ request('filiere_id') ? 'active' : 'pending' }}"></div>
                <label class="selector-label">
                    <i class="fas fa-graduation-cap"></i>
                    Filière d'Études
                </label>
                <select class="selector-modern" id="filiere_id_modern" onchange="handleSmartFilter()">
                    <option value="">Toutes les filières</option>
                    @foreach($filieres as $filiere)
                        <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                            {{ $filiere->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="selector-card">
                <div class="status-indicator {{ request('niveau_id') ? 'active' : 'pending' }}"></div>
                <label class="selector-label">
                    <i class="fas fa-layer-group"></i>
                    Niveau d'Étude
                </label>
                <select class="selector-modern" id="niveau_id_modern" onchange="handleSmartFilter()">
                    <option value="">Tous les niveaux</option>
                    @foreach($niveaux as $niveau)
                        <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                            {{ $niveau->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="selector-card">
                <div class="status-indicator active"></div>
                <label class="selector-label">
                    <i class="fas fa-calendar-week"></i>
                    Semestre
                </label>
                <select class="selector-modern" id="semestre_modern" onchange="handleSmartFilter()">
                    <option value="1" {{ request('semestre', 1) == 1 ? 'selected' : '' }}>1er Semestre</option>
                    <option value="2" {{ request('semestre', 1) == 2 ? 'selected' : '' }}>2nd Semestre</option>
                </select>
            </div>
        </div>

        <!-- KPI Dashboard with Micro-interactions -->
        <div class="kpi-grid-modern">
            <div class="kpi-card-modern" onclick="showDetail('seances')">
                <div class="kpi-icon-modern">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="kpi-value-modern">{{ number_format($stats['total_seances']) }}</div>
                <div class="kpi-label-modern">Séances Planifiées</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-arrow-up me-1"></i>+12% vs mois dernier
                </div>
            </div>

            <div class="kpi-card-modern" onclick="showDetail('heures')">
                <div class="kpi-icon-modern">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="kpi-value-modern">{{ number_format($stats['total_heures'], 0) }}h</div>
                <div class="kpi-label-modern">Volume Horaire</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-arrow-up me-1"></i>{{ round($stats['total_heures'] / 40, 1) }} semaines
                </div>
            </div>

            <div class="kpi-card-modern" onclick="showDetail('enseignants')">
                <div class="kpi-icon-modern">
                    <i class="fas fa-users"></i>
                </div>
                <div class="kpi-value-modern">{{ $stats['total_enseignants'] }}</div>
                <div class="kpi-label-modern">Enseignants Actifs</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-check-circle me-1"></i>100% assignés
                </div>
            </div>

            <div class="kpi-card-modern" onclick="showDetail('classes')">
                <div class="kpi-icon-modern">
                    <i class="fas fa-school"></i>
                </div>
                <div class="kpi-value-modern">{{ $stats['total_classes'] }}</div>
                <div class="kpi-label-modern">Classes Actives</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-chart-line me-1"></i>Optimisé à 85%
                </div>
            </div>
        </div>

        @if(request('annee_id') && request('filiere_id') && request('niveau_id'))
        <!-- Planning Context -->
        <div class="context-card">
            <div class="context-header">
                <div class="context-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div>
                    <h3 class="mb-1">Contexte de Planification Sélectionné</h3>
                    <p class="text-muted mb-0">Configuration active pour cette combinaison</p>
                </div>
            </div>
            
            <div class="context-info">
                <div class="context-item">
                    <div class="context-item-label">Filière</div>
                    <div class="context-item-value">{{ $filiereSelectionnee->name }}</div>
                </div>
                <div class="context-item">
                    <div class="context-item-label">Niveau</div>
                    <div class="context-item-value">{{ $niveauSelectionne->name }}</div>
                </div>
                <div class="context-item">
                    <div class="context-item-label">Semestre</div>
                    <div class="context-item-value">Semestre {{ $semestre }}</div>
                </div>
                <div class="context-item">
                    <div class="context-item-label">Matières Disponibles</div>
                    <div class="context-item-value">{{ $matieres->count() }} matières</div>
                </div>
                <div class="context-item">
                    <div class="context-item-label">Planifications</div>
                    <div class="context-item-value">{{ $planifications->count() }} actives</div>
                </div>
                <div class="context-item">
                    <div class="context-item-label">Taux de Completion</div>
                    <div class="context-item-value">{{ $statistiques['taux_completion'] }}%</div>
                </div>
            </div>
        </div>
        @endif

        <!-- Quick Actions with Modern Design -->
        <div class="quick-actions-modern">
            <a href="{{ route('esbtp.planning-general.annuel') }}" class="action-card-modern">
                <div class="action-icon-modern">
                    <i class="fas fa-calendar-year"></i>
                </div>
                <div class="action-title-modern">Vue Annuelle</div>
                <div class="action-description-modern">
                    Calendrier complet de l'année universitaire avec événements académiques
                </div>
            </a>

            <a href="{{ route('esbtp.planning-general.repartition-matieres') }}" class="action-card-modern">
                <div class="action-icon-modern">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="action-title-modern">Répartition Matières</div>
                <div class="action-description-modern">
                    Analyse détaillée de la répartition horaire par matière et objectifs
                </div>
            </a>

            <a href="{{ route('esbtp.planning-general.emargement') }}" class="action-card-modern">
                <div class="action-icon-modern">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="action-title-modern">Émargement</div>
                <div class="action-description-modern">
                    Gestion des codes d'émargement et suivi de présence des enseignants
                </div>
            </a>

            @can('planning.manage')
            <a href="{{ route('esbtp.planning-general.coordinateur') }}" class="action-card-modern">
                <div class="action-icon-modern">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="action-title-modern">Interface Coordinateur</div>
                <div class="action-description-modern">
                    Outils avancés de gestion et coordination des plannings
                </div>
            </a>
            @endcan

            <a href="{{ route('esbtp.planning-general.impact-emargements') }}" class="action-card-modern">
                <div class="action-icon-modern">
                    <i class="fas fa-analytics"></i>
                </div>
                <div class="action-title-modern">Impact Émargements</div>
                <div class="action-description-modern">
                    Analyse de l'impact des émargements sur la progression académique
                </div>
            </a>

            <div class="action-card-modern" onclick="openQuickPlanModal()">
                <div class="action-icon-modern">
                    <i class="fas fa-magic"></i>
                </div>
                <div class="action-title-modern">Planification Rapide</div>
                <div class="action-description-modern">
                    Assistant intelligent pour créer rapidement de nouveaux plannings
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Smart Filter Handling with Debouncing
let filterTimeout;
function handleSmartFilter() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        const anneeId = document.getElementById('annee_id_modern').value;
        const filiereId = document.getElementById('filiere_id_modern').value;
        const niveauId = document.getElementById('niveau_id_modern').value;
        const semestre = document.getElementById('semestre_modern').value;
        
        // Construct URL with parameters
        const params = new URLSearchParams();
        if (anneeId) params.append('annee_id', anneeId);
        if (filiereId) params.append('filiere_id', filiereId);
        if (niveauId) params.append('niveau_id', niveauId);
        params.append('semestre', semestre);
        
        // Show loading state
        showLoadingState();
        
        // Navigate with smooth transition
        window.location.href = `{{ route('esbtp.planning-general.index') }}?${params.toString()}`;
    }, 300);
}

// View Toggle Functionality
function toggleView(viewType) {
    const params = new URLSearchParams(window.location.search);
    params.set('view', viewType);
    
    // Add smooth transition
    document.body.style.opacity = '0.7';
    setTimeout(() => {
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    }, 200);
}

// KPI Detail Modal
function showDetail(type) {
    // Implementation for detailed KPI views
    debugLog(`Showing details for: ${type}`);
    // Could open modal with detailed charts/tables
}

// Quick Planning Modal
function openQuickPlanModal() {
    // Implementation for quick planning assistant
    debugLog('Opening quick plan modal');
    // Could open modal with AI-assisted planning
}

// Loading State Management
function showLoadingState() {
    document.querySelectorAll('.selector-card').forEach(card => {
        card.style.opacity = '0.6';
        card.style.pointerEvents = 'none';
    });
}

// Enhanced Accessibility
document.addEventListener('DOMContentLoaded', function() {
    // Add keyboard navigation
    document.querySelectorAll('.action-card-modern').forEach(card => {
        card.setAttribute('tabindex', '0');
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    
    // Auto-save user preferences
    document.querySelectorAll('.selector-modern').forEach(select => {
        select.addEventListener('change', function() {
            localStorage.setItem(`planning_${this.id}`, this.value);
        });
        
        // Restore saved values
        const saved = localStorage.getItem(`planning_${select.id}`);
        if (saved && !select.value) {
            select.value = saved;
        }
    });
});

// Progressive Enhancement - Service Worker for Offline Support
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(console.error);
}

// Performance Monitoring
const observer = new PerformanceObserver((list) => {
    for (const entry of list.getEntries()) {
        if (entry.entryType === 'navigation') {
            debugLog('Page Load Time:', entry.loadEventEnd - entry.loadEventStart);
        }
    }
});
observer.observe({entryTypes: ['navigation']});
</script>
@endpush