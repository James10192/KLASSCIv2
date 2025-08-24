@extends('layouts.app')

@section('title', 'Gestion des Codes d\'Émargement - Planning')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .code-display-large {
        font-family: 'Courier New', monospace;
        font-size: 4rem;
        font-weight: 900;
        background: linear-gradient(135deg, var(--primary), var(--accent-blue));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-align: center;
        padding: var(--space-xl);
        border: 3px dashed var(--primary);
        border-radius: var(--radius-medium);
        margin: var(--space-lg) 0;
        box-shadow: var(--shadow-elevated);
        background-color: white;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin: var(--space-xl) 0;
    }

    .stat-item {
        text-align: center;
        padding: var(--space-lg);
        border-radius: var(--radius-medium);
        background: var(--surface);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-card);
        transition: all 0.3s ease;
    }

    .stat-item:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: var(--space-xs);
    }

    .stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        text-transform: uppercase;
        font-weight: 500;
        letter-spacing: 0.05em;
    }

    .action-buttons {
        display: flex;
        gap: var(--space-md);
        justify-content: center;
        margin: var(--space-lg) 0;
        flex-wrap: wrap;
    }

    .status-badge-modern {
        display: inline-flex;
        align-items: center;
        gap: var(--space-xs);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        font-size: var(--text-small);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-badge-modern.active {
        background: rgba(34, 197, 94, 0.1);
        color: var(--success);
        border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .status-badge-modern.expired {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .status-badge-modern.used {
        background: rgba(156, 163, 175, 0.1);
        color: var(--text-muted);
        border: 1px solid rgba(156, 163, 175, 0.3);
    }

    .code-history {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        box-shadow: var(--shadow-card);
    }

    .code-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-md);
        border-bottom: 1px solid var(--border);
        transition: all 0.2s ease;
    }

    .code-item:last-child {
        border-bottom: none;
    }

    .code-item:hover {
        background: var(--background);
        border-radius: var(--radius-small);
    }

    .code-value {
        font-family: 'Courier New', monospace;
        font-size: var(--text-large);
        font-weight: 700;
        color: var(--primary);
    }

    .code-meta {
        font-size: var(--text-small);
        color: var(--text-secondary);
    }

    .no-code-state {
        text-align: center;
        padding: var(--space-xl);
        color: var(--text-secondary);
    }

    .no-code-icon {
        font-size: 3rem;
        margin-bottom: var(--space-md);
        color: var(--text-muted);
    }

    .expired-warning {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin: var(--space-md) 0;
        color: var(--danger);
        text-align: center;
    }

    .emargement-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-medium);
        margin-bottom: var(--space-lg);
        text-align: center;
    }

    .emargement-header h2 {
        margin: 0;
        font-size: var(--title-main);
        font-weight: 700;
    }

    .emargement-header p {
        margin: var(--space-sm) 0 0 0;
        opacity: 0.9;
        font-size: var(--text-normal);
    }

    @media (max-width: 768px) {
        .code-display-large {
            font-size: 2.5rem;
            padding: var(--space-lg);
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
            align-items: center;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header et navigation du planning -->
        <x-planning-header 
            title="Codes d'Émargement" 
            subtitle="Génération et gestion des codes d'émargement pour le suivi des présences"
            active-tab="emargement"
            :annee-selectionnee="$anneeSelectionnee"
            :annees="$annees"
        />

        <!-- En-tête de la section émargement -->
        <div class="emargement-header">
            <h2>
                <i class="fas fa-qrcode me-3"></i>
                Gestion des Codes d'Émargement
            </h2>
            <p>Interface de génération et de suivi des codes d'émargement quotidiens</p>
        </div>

        <!-- Statistiques d'émargement -->
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value">{{ $stats['total_emargements_aujourd_hui'] }}</div>
                <div class="stat-label">Émargements Aujourd'hui</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $stats['enseignants_emarges_aujourd_hui'] }}</div>
                <div class="stat-label">Enseignants Émargés</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $stats['codes_generes_semaine'] }}</div>
                <div class="stat-label">Codes Cette Semaine</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $stats['taux_emargement_semaine'] }}%</div>
                <div class="stat-label">Taux d'Émargement</div>
            </div>
        </div>

        <!-- Grille principale pour les codes d'émargement -->
        <div class="dashboard-main-grid">
            <!-- Code d'émargement actuel -->
            <div class="main-card">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-qrcode"></i>
                        Code d'Émargement Actuel
                    </div>
                    <p class="main-card-subtitle">Code actif pour les émargements enseignants</p>
                </div>
                <div class="main-card-body">
                    @if($activeCodes->isNotEmpty())
                        @if($activeCodes->count() == 1)
                            <!-- Code unique actif -->
                            <div class="text-center mb-lg">
                                <div class="badge primary" style="font-size: 1.5rem; padding: var(--space-md) var(--space-lg);">
                                    <i class="fas fa-circle me-2" style="color: var(--success);"></i>
                                    {{ $activeCodes->first()->code }}
                                </div>
                            </div>
                            
                            <div class="form-grid-2">
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-calendar-plus me-1"></i>
                                        Créé le
                                    </div>
                                    <div class="info-value">{{ $activeCodes->first()->created_at->format('d/m/Y à H:i') }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-clock me-1"></i>
                                        Valide jusqu'à
                                    </div>
                                    <div class="info-value">{{ $activeCodes->first()->valid_until->format('d/m/Y à H:i') }}</div>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-user me-1"></i>
                                    Générateur
                                </div>
                                <div class="info-value">
                                    {{ $activeCodes->first()->generator->name ?? 'Système' }}
                                    @if($activeCodes->first()->description)
                                        - {{ $activeCodes->first()->description }}
                                    @endif
                                </div>
                            </div>
                            
                            @if($activeCodes->first()->seance)
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        Séance liée
                                    </div>
                                    <div class="info-value">
                                        {{ $activeCodes->first()->seance->matiere?->name }} - {{ $activeCodes->first()->seance->classe?->nom }}
                                    </div>
                                </div>
                            @endif
                        @else
                            <!-- Plusieurs codes actifs -->
                            <div class="kpi-grid">
                                @foreach($activeCodes as $code)
                                    <div class="kpi-card">
                                        <div class="kpi-title">
                                            @if($code->seance)
                                                <i class="fas fa-calendar-check me-1"></i>
                                                {{ $code->seance->matiere?->name ?? 'Séance' }}
                                            @else
                                                <i class="fas fa-globe me-1"></i>
                                                Code général
                                            @endif
                                        </div>
                                        <div class="kpi-value color-primary">{{ $code->code }}</div>
                                        <div class="kpi-trend">
                                            <i class="fas fa-clock me-1"></i>
                                            Jusqu'à {{ $code->valid_until->format('H:i') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Aucun code d'émargement actif. Générez un nouveau code pour permettre aux enseignants de s'émarger.</p>
                        </div>
                    @endif

                    <!-- Séances de cours disponibles -->
                    @if($seancesAVenir->isNotEmpty())
                        @php
                            // Filtrer les vraies séances à venir (pas celles qui sont passées)
                            $now = \Carbon\Carbon::now();
                            $vraiSeancesAVenir = $seancesAVenir->filter(function($seance) use ($now) {
                                // Si date_seance existe, utiliser cette date
                                if ($seance->date_seance) {
                                    // heure_debut peut être un datetime complet, extraire juste l'heure
                                    $heureDebut = \Carbon\Carbon::parse($seance->heure_debut)->format('H:i:s');
                                    $seanceDateTime = \Carbon\Carbon::parse($seance->date_seance . ' ' . $heureDebut);
                                    return $seanceDateTime->gt($now);
                                }
                                
                                // Sinon, pour les séances récurrentes, vérifier si l'heure n'est pas passée aujourd'hui
                                $heureDebut = \Carbon\Carbon::parse($seance->heure_debut)->format('H:i:s');
                                $today = \Carbon\Carbon::today();
                                $seanceToday = $today->copy()->setTimeFromTimeString($heureDebut);
                                
                                // Si c'est aujourd'hui et que l'heure est passée, ne pas inclure
                                if ($now->isToday() && $seanceToday->lt($now)) {
                                    return false;
                                }
                                
                                return true;
                            });
                        @endphp
                        
                        @if($vraiSeancesAVenir->isNotEmpty())
                            <div class="section-header mt-lg">
                                <div class="section-title">
                                    <i class="fas fa-calendar-plus me-2"></i>
                                    Séances de cours disponibles
                                </div>
                                <small class="text-muted">{{ $vraiSeancesAVenir->count() }} séance(s) à venir pour générer des codes</small>
                            </div>
                            
                            <div class="course-list">
                                @foreach($vraiSeancesAVenir->take(6) as $seance)
                                    @php
                                        $now = \Carbon\Carbon::now();
                                        $isUpcoming = true;
                                        
                                        if ($seance->date_seance) {
                                            $heureDebut = \Carbon\Carbon::parse($seance->heure_debut)->format('H:i:s');
                                            $seanceDateTime = \Carbon\Carbon::parse($seance->date_seance . ' ' . $heureDebut);
                                            $isUpcoming = $seanceDateTime->gt($now);
                                        } else {
                                            $heureDebut = \Carbon\Carbon::parse($seance->heure_debut)->format('H:i:s');
                                            $seanceTime = \Carbon\Carbon::today()->setTimeFromTimeString($heureDebut);
                                            $isUpcoming = $seanceTime->gt($now);
                                        }
                                    @endphp
                                    
                                    @if($isUpcoming)
                                        <form method="POST" action="{{ route('esbtp.planning-general.generer-code-emargement') }}" style="display: contents;">
                                            @csrf
                                            <input type="hidden" name="annee_id" value="{{ $anneeSelectionnee?->id }}">
                                            <input type="hidden" name="type" value="session">
                                            <input type="hidden" name="seance_id" value="{{ $seance->id }}">
                                            <input type="hidden" name="duree" value="{{ ceil($seance->getDuration() / 60) }}">
                                            <input type="hidden" name="activation" value="immediate">
                                            <input type="hidden" name="description" value="Code pour {{ $seance->matiere?->name }} - {{ $seance->classe?->nom }}">
                                            
                                            <button type="submit" class="course-item" style="background: none; border: none; width: 100%; text-align: left;"
                                                    onclick="return confirm('Générer un code d\'émargement pour cette séance ?')">
                                                <div class="course-time">
                                                    <div class="time-display">{{ $seance->heure_debut->format('H:i') }} - {{ $seance->heure_fin->format('H:i') }}</div>
                                                    <div class="course-day">
                                                        @if($seance->date_seance)
                                                            {{ \Carbon\Carbon::parse($seance->date_seance)->format('d/m') }}
                                                        @else
                                                            Prochaine fois
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="course-info">
                                                    <div class="course-subject">{{ $seance->matiere?->name ?? 'Matière inconnue' }}</div>
                                                    <div class="course-class">{{ $seance->classe?->nom ?? 'Classe inconnue' }}</div>
                                                    @if($seance->teacher)
                                                        <div class="course-type">{{ $seance->teacher->name }}</div>
                                                    @endif
                                                </div>
                                                <div class="course-status">
                                                    <div class="badge success">
                                                        <i class="fas fa-plus-circle me-1"></i>
                                                        Générer Code
                                                    </div>
                                                </div>
                                            </button>
                                        </form>
                                    @else
                                        <!-- Cours passé - non cliquable -->
                                        <div class="course-item" style="opacity: 0.5; background-color: rgba(156, 163, 175, 0.1);">
                                            <div class="course-time">
                                                <div class="time-display">{{ $seance->heure_debut->format('H:i') }} - {{ $seance->heure_fin->format('H:i') }}</div>
                                                <div class="course-day">
                                                    @if($seance->date_seance)
                                                        {{ \Carbon\Carbon::parse($seance->date_seance)->format('d/m') }}
                                                    @else
                                                        Aujourd'hui
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="course-info">
                                                <div class="course-subject">{{ $seance->matiere?->name ?? 'Matière inconnue' }}</div>
                                                <div class="course-class">{{ $seance->classe?->nom ?? 'Classe inconnue' }}</div>
                                                @if($seance->teacher)
                                                    <div class="course-type">{{ $seance->teacher->name }}</div>
                                                @endif
                                            </div>
                                            <div class="course-status">
                                                <div class="badge neutral">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Cours passé
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            
                            @if($vraiSeancesAVenir->count() > 6)
                                <div class="text-center mt-md">
                                    <small style="color: var(--text-muted);">{{ $vraiSeancesAVenir->count() - 6 }} autre(s) séance(s) disponible(s)...</small>
                                </div>
                            @endif
                        @else
                            <div class="empty-state mt-lg">
                                <i class="fas fa-info-circle"></i>
                                <p>Aucune séance de cours à venir trouvée.</p>
                            </div>
                        @endif
                    @else
                        <div class="empty-state mt-lg">
                            <i class="fas fa-info-circle"></i>
                            <p>Aucune séance de cours trouvée pour aujourd'hui et les prochains jours.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions rapides et historique -->
            <div class="main-card">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-bolt"></i>
                        Actions Rapides
                    </div>
                    <p class="main-card-subtitle">Génération de codes d'émargement</p>
                </div>
                <div class="main-card-body">
                    <div class="quick-actions-grid">
                        <form method="POST" action="{{ route('esbtp.planning-general.generer-code-emargement') }}" style="display: contents;">
                            @csrf
                            <input type="hidden" name="annee_id" value="{{ $anneeSelectionnee?->id }}">
                            <input type="hidden" name="type" value="journee">
                            <input type="hidden" name="duree" value="8">
                            <input type="hidden" name="activation" value="immediate">
                            <button type="submit" class="quick-action-card" 
                                    onclick="return confirm('Générer un code général pour la journée (8h) ?')">
                                <i class="fas fa-sun"></i>
                                <span>Code Journée</span>
                            </button>
                        </form>
                        
                        <button type="button" class="quick-action-card" data-bs-toggle="modal" data-bs-target="#codeModal">
                            <i class="fas fa-cogs"></i>
                            <span>Code Personnalisé</span>
                        </button>
                        
                        <a href="{{ route('esbtp.attendance-codes.index') }}" class="quick-action-card">
                            <i class="fas fa-external-link-alt"></i>
                            <span>Interface Complète</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Historique des codes récents -->
            <div class="main-card">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-history"></i>
                        Codes Récents
                    </div>
                    <p class="main-card-subtitle">Historique des derniers codes générés</p>
                </div>
                <div class="main-card-body">
                    @forelse($recentCodes as $code)
                        <div class="course-item" style="grid-template-columns: auto 1fr auto;">
                            <div style="text-align: center;">
                                <div class="time-display" style="font-size: 1rem;">{{ $code->code }}</div>
                                <div class="course-day">{{ $code->created_at->format('d/m') }}</div>
                            </div>
                            <div class="course-info">
                                <div class="course-subject">
                                    @if($code->seance)
                                        {{ $code->seance->matiere?->name }} - {{ $code->seance->classe?->nom }}
                                    @else
                                        {{ $code->description ?? 'Code général' }}
                                    @endif
                                </div>
                                <div class="course-class">{{ $code->created_at->format('d/m/Y H:i') }}</div>
                                @if($code->generator)
                                    <div class="course-type">par {{ $code->generator->name }}</div>
                                @endif
                            </div>
                            <div class="course-status">
                                @if($code->status === 'active' && $code->valid_until > now())
                                    <div class="badge success">
                                        <i class="fas fa-circle me-1"></i>
                                        Actif
                                    </div>
                                @elseif($code->status === 'active' && $code->valid_until <= now())
                                    <div class="badge danger">
                                        <i class="fas fa-clock me-1"></i>
                                        Expiré
                                    </div>
                                @elseif($code->status === 'expired')
                                    <div class="badge danger">
                                        <i class="fas fa-times-circle me-1"></i>
                                        Expiré
                                    </div>
                                @else
                                    <div class="badge neutral">
                                        <i class="fas fa-check-circle me-1"></i>
                                        {{ ucfirst($code->status) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Aucun code généré récemment</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Modal pour code personnalisé -->
        <div class="modal fade" id="codeModal" tabindex="-1" aria-labelledby="codeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content modal-moderne">
                    <div class="modal-header">
                        <h5 class="modal-title" id="codeModalLabel">
                            <i class="fas fa-cogs me-2"></i>
                            Génération de Code Personnalisé
                        </h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('esbtp.planning-general.generer-code-emargement') }}">
                        @csrf
                        <input type="hidden" name="annee_id" value="{{ $anneeSelectionnee?->id }}">
                        <input type="hidden" name="type" value="personnalise">
                        
                        <div class="modal-body">
                            <div class="form-grid-2">
                                <div class="form-group-moderne">
                                    <label for="duree_heures" class="form-label-moderne">
                                        <i class="fas fa-clock me-1"></i>
                                        Durée de validité
                                    </label>
                                    <select class="form-select-moderne" id="duree_heures" name="duree" required>
                                        <option value="1">1 heure</option>
                                        <option value="2" selected>2 heures (séance standard)</option>
                                        <option value="3">3 heures</option>
                                        <option value="4">4 heures (demi-journée)</option>
                                        <option value="8">8 heures (journée complète)</option>
                                        <option value="12">12 heures</option>
                                        <option value="24">24 heures</option>
                                        <option value="48">48 heures (2 jours)</option>
                                        <option value="72">72 heures (3 jours)</option>
                                    </select>
                                </div>
                                
                                <div class="form-group-moderne">
                                    <label for="activation_retardee" class="form-label-moderne">
                                        <i class="fas fa-play me-1"></i>
                                        Activation
                                    </label>
                                    <select class="form-select-moderne" id="activation_retardee" name="activation">
                                        <option value="immediate" selected>Immédiate</option>
                                        <option value="1">Dans 1 heure</option>
                                        <option value="2">Dans 2 heures</option>
                                        <option value="4">Dans 4 heures</option>
                                        <option value="24">Dans 24 heures (demain)</option>
                                    </select>
                                    <small style="color: var(--text-muted); font-style: italic;">
                                        Le code sera activé automatiquement au moment choisi
                                    </small>
                                </div>
                            </div>
                            
                            <div class="form-group-moderne">
                                <label for="seance_personnalisee" class="form-label-moderne">
                                    <i class="fas fa-calendar-check me-1"></i>
                                    Séance de cours
                                </label>
                                <select class="form-select-moderne" id="seance_personnalisee" name="seance_id">
                                    <option value="">Aucune séance spécifique</option>
                                    @if($seancesAVenir->isNotEmpty())
                                        <optgroup label="Séances à venir">
                                            @foreach($seancesAVenir as $seance)
                                                <option value="{{ $seance->id }}" 
                                                        data-duree="{{ ceil($seance->getDuration() / 60) }}"
                                                        data-description="Code pour {{ $seance->matiere?->name }} - {{ $seance->classe?->nom }}">
                                                    {{ $seance->heure_debut->format('H:i') }} - {{ $seance->heure_fin->format('H:i') }} : 
                                                    {{ $seance->matiere?->name ?? 'Matière inconnue' }} 
                                                    ({{ $seance->classe?->nom ?? 'Classe inconnue' }})
                                                    @if($seance->date_seance)
                                                        - {{ \Carbon\Carbon::parse($seance->date_seance)->format('d/m/Y') }}
                                                    @else
                                                        - {{ $seance->jour_semaine_texte }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                <small style="color: var(--text-muted); font-style: italic;">
                                    Si vous sélectionnez une séance, la durée et la description seront automatiquement ajustées
                                </small>
                            </div>
                            
                            <div class="form-group-moderne">
                                <label for="description_code" class="form-label-moderne">
                                    <i class="fas fa-tag me-1"></i>
                                    Description
                                </label>
                                <input type="text" class="form-input-moderne" id="description_code" name="description" 
                                       placeholder="Ex: Code pour TP Électronique - Groupe A" maxlength="255">
                                <small style="color: var(--text-muted); font-style: italic;">
                                    Cette description apparaîtra dans l'historique pour identifier le code
                                </small>
                            </div>
                            
                            <div style="background-color: rgba(59, 130, 246, 0.1); padding: var(--space-md); border-radius: var(--radius-small); border-left: 4px solid #3b82f6;">
                                <div style="display: flex; align-items: center; gap: var(--space-sm);">
                                    <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
                                    <strong style="color: #3b82f6;">Rappel :</strong>
                                    <span style="color: var(--text-primary);">Seuls les codes du même type seront invalidés (séance ou général).</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>
                                Annuler
                            </button>
                            <button type="submit" class="btn-acasi primary">
                                <i class="fas fa-qrcode me-2"></i>
                                Générer le Code
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section rapide pour les liens d'interface -->
        {{-- 
        <div class="quick-actions-section">
            <div class="quick-actions-grid">
                <a href="{{ route('esbtp.attendance-codes.report') }}" class="quick-action-card">
                    <i class="fas fa-chart-bar"></i>
                    <span>Rapports d'Émargement</span>
                </a>
                <a href="{{ route('esbtp.attendance-codes.settings') }}" class="quick-action-card">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </a>
                <a href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee?->id]) }}" class="quick-action-card">
                    <i class="fas fa-user-tie"></i>
                    <span>Interface Coordinateur</span>
                </a>
                <a href="{{ route('teacher.attendance') }}" class="quick-action-card">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Interface Enseignant</span>
                </a>
            </div>
        </div>
        --}}
    </div>
</div>
@endsection


@push('scripts')
<script>
$(document).ready(function() {
    // Gestion de la sélection de séance dans le modal personnalisé
    $('#seance_personnalisee').on('change', function() {
        const selectedOption = $(this).find(':selected');
        const duree = selectedOption.data('duree');
        const description = selectedOption.data('description');
        
        if (duree) {
            $('#duree_heures').val(duree);
        }
        
        if (description) {
            $('#description_code').val(description);
        } else {
            $('#description_code').val('');
        }
    });

    // Auto-refresh de la page toutes les 2 minutes pour actualiser les statuts des cours
    setInterval(function() {
        // Ne pas rafraîchir si l'utilisateur est en train d'interagir ou si un modal est ouvert
        const modals = document.querySelectorAll('.modal.show');
        const activeInputs = document.querySelectorAll('input:focus, textarea:focus, select:focus');
        
        if (!document.hidden && modals.length === 0 && activeInputs.length === 0) {
            window.location.reload();
        }
    }, 120000); // 2 minutes
    
    // Mise à jour en temps réel des statuts de cours toutes les minutes
    setInterval(function() {
        updateCourseStatuses();
    }, 60000); // 1 minute
    
    function updateCourseStatuses() {
        const now = new Date();
        
        $('.course-item').each(function() {
            const timeDisplay = $(this).find('.time-display').text();
            const badge = $(this).find('.badge');
            
            if (timeDisplay && timeDisplay.includes(' - ')) {
                const [startTime, endTime] = timeDisplay.split(' - ');
                
                // Parse time
                const today = new Date();
                const [startHour, startMin] = startTime.split(':');
                
                const courseStart = new Date(today);
                courseStart.setHours(parseInt(startHour), parseInt(startMin), 0, 0);
                
                // Si le cours est passé et que le badge dit encore "Générer Code"
                if (now > courseStart && badge.hasClass('success') && badge.text().includes('Générer Code')) {
                    badge.removeClass('success').addClass('neutral');
                    badge.html('<i class="fas fa-clock me-1"></i> Cours passé');
                    
                    // Désactiver le bouton parent si c'est un bouton
                    const button = $(this).closest('button');
                    if (button.length) {
                        button.prop('disabled', true);
                        button.css('opacity', '0.5');
                        button.css('cursor', 'not-allowed');
                        button.off('click');
                    }
                }
            }
        });
    }

    // Affichage du temps restant pour le code actif
    @if($activeCode && !$activeCode->valid_until->isPast())
        function updateTimeRemaining() {
            const validUntil = new Date('{{ $activeCode->valid_until->toISOString() }}');
            const now = new Date();
            const timeLeft = validUntil - now;
            
            if (timeLeft <= 0) {
                $('#time-remaining-display').removeClass('alert-info').addClass('alert-danger');
                $('#time-remaining').html('<i class="fas fa-exclamation-triangle me-2"></i>Code expiré');
                setTimeout(() => location.reload(), 2000);
                return;
            }
            
            const hours = Math.floor(timeLeft / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            let display = '';
            if (hours > 0) {
                display = hours + 'h ' + minutes + 'm ' + seconds + 's';
            } else if (minutes > 0) {
                display = minutes + 'm ' + seconds + 's';
            } else {
                display = seconds + 's';
                $('#time-remaining-display').removeClass('alert-info').addClass('alert-warning');
            }
            
            const timeDisplay = document.getElementById('time-remaining');
            if (timeDisplay) {
                timeDisplay.innerHTML = '<i class="fas fa-hourglass-half me-2"></i>Expire dans : ' + display;
            }
            
            // Changer la couleur si moins de 10 minutes restantes
            if (timeLeft < 600000) { // 10 minutes
                $('#time-remaining-display').removeClass('alert-info').addClass('alert-warning');
            }
            
            // Changer la couleur si moins de 2 minutes restantes
            if (timeLeft < 120000) { // 2 minutes
                $('#time-remaining-display').removeClass('alert-warning').addClass('alert-danger');
            }
        }
        
        // Mettre à jour toutes les secondes
        setInterval(updateTimeRemaining, 1000);
        updateTimeRemaining();
    @else
        $('#time-remaining-display').hide();
    @endif

    // Validation du formulaire personnalisé
    $('#codeModal form').on('submit', function(e) {
        const duree = parseInt($('#duree_heures').val());
        const activation = $('#activation_retardee').val();
        
        let confirmMessage = `Générer un code valide pendant ${duree}h`;
        
        if (activation !== 'immediate') {
            const heures = parseInt(activation);
            confirmMessage += ` avec activation dans ${heures}h`;
        }
        
        confirmMessage += ' ?';
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
        }
    });
});
</script>
@endpush