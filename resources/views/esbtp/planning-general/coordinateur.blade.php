@extends('layouts.app')

@section('title', 'Gestion Planning - Coordinateur - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .coordinateur-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-xl);
        margin-bottom: var(--space-xl);
    }
    
    @media (max-width: 768px) {
        .coordinateur-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .widget-coordinateur {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        box-shadow: var(--shadow-card);
        transition: all 0.3s ease;
    }
    
    .widget-coordinateur:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }
    
    .widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--space-lg);
        padding-bottom: var(--space-md);
        border-bottom: 2px solid var(--border-color);
    }
    
    .widget-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .widget-actions {
        display: flex;
        gap: var(--space-sm);
    }
    
    .allocation-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-md);
        margin-bottom: var(--space-sm);
        background: rgba(var(--primary-rgb), 0.05);
        border-radius: var(--radius-small);
        border-left: 4px solid var(--primary);
    }
    
    .allocation-info {
        flex: 1;
    }
    
    .allocation-hours {
        font-weight: 600;
        color: var(--primary);
    }
    
    .programmation-semaine {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: var(--space-xs);
        margin-bottom: var(--space-lg);
    }
    
    .jour-semaine {
        text-align: center;
        padding: var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
        background: var(--primary);
    }
    
    .seance-programmee {
        background: var(--surface);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-small);
        padding: var(--space-sm);
        margin-bottom: var(--space-xs);
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .seance-programmee:hover {
        background: rgba(var(--primary-rgb), 0.1);
        border-color: var(--primary);
    }
    
    .code-emargement {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-md);
        background: rgba(var(--success-rgb), 0.1);
        border: 1px solid var(--success);
        border-radius: var(--radius-small);
        margin-bottom: var(--space-sm);
    }
    
    .code-emargement.expire {
        background: rgba(var(--danger-rgb), 0.1);
        border-color: var(--danger);
    }
    
    .code-value {
        font-family: 'Courier New', monospace;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--success);
    }
    
    .code-emargement.expire .code-value {
        color: var(--danger);
    }
    
    .taux-presence {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-md);
        margin-bottom: var(--space-sm);
    }
    
    .taux-cercle {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: white;
        font-size: 0.9rem;
    }
    
    .taux-excellent { background: var(--success); }
    .taux-bon { background: var(--info); }
    .taux-moyen { background: var(--warning); }
    .taux-faible { background: var(--danger); }
    
    .actions-coordinateur {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .action-coordinateur {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: var(--space-lg);
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        text-decoration: none;
        color: var(--text-primary);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .action-coordinateur:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-hover);
        color: var(--text-primary);
        text-decoration: none;
    }
    
    .action-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        margin-bottom: var(--space-md);
    }
    
    .action-icon.planning { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
    .action-icon.validation { background: linear-gradient(135deg, #10b981, #34d399); }
    .action-icon.analytics { background: linear-gradient(135deg, #06b6d4, #67e8f9); }
    .action-icon.settings { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .action-icon.reports { background: linear-gradient(135deg, #ef4444, #f87171); }
    .action-icon.notifications { background: linear-gradient(135deg, #6366f1, #818cf8); }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Gestion du Planning</h1>
                <p class="header-subtitle">Interface coordinateur pour la gestion avancée du planning académique</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-acasi warning" onclick="genererRapport()">
                    <i class="fas fa-file-alt"></i>Rapport
                </button>
                <a href="{{ route('esbtp.planning-general.index', ['annee_id' => $anneeSelectionnee?->id]) }}" class="btn-acasi primary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
            </div>
        </div>

        <!-- Navigation du planning -->
        <div class="planning-nav">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.index', ['annee_id' => $anneeSelectionnee?->id]) }}">
                        <i class="fas fa-home me-2"></i>Vue d'ensemble
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.annuel', ['annee_id' => $anneeSelectionnee?->id]) }}">
                        <i class="fas fa-calendar me-2"></i>Planning Annuel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.repartition-matieres', ['annee_id' => $anneeSelectionnee?->id]) }}">
                        <i class="fas fa-chart-pie me-2"></i>Répartition Matières
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee?->id]) }}">
                        <i class="fas fa-user-tie me-2"></i>Coordinateur
                    </a>
                </li>
            </ul>
        </div>

        <!-- Actions rapides coordinateur -->
        <div class="actions-coordinateur">
            <div class="action-coordinateur" onclick="ouvrirGestionPlanning()">
                <div class="action-icon planning">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h6>Créer Planning</h6>
                <p class="text-muted text-center mb-0">Nouvelle programmation de cours</p>
            </div>
            
            <div class="action-coordinateur" onclick="ouvrirValidationSeances()">
                <div class="action-icon validation">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h6>Valider Séances</h6>
                <p class="text-muted text-center mb-0">Approuver les séances de cours</p>
            </div>
            
            <div class="action-coordinateur" onclick="ouvrirAnalytics()">
                <div class="action-icon analytics">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h6>Analytics</h6>
                <p class="text-muted text-center mb-0">Statistiques et tableaux de bord</p>
            </div>
            
            <div class="action-coordinateur" onclick="ouvrirParametres()">
                <div class="action-icon settings">
                    <i class="fas fa-cog"></i>
                </div>
                <h6>Paramètres</h6>
                <p class="text-muted text-center mb-0">Configuration du planning</p>
            </div>
            
            <div class="action-coordinateur" onclick="ouvrirRapports()">
                <div class="action-icon reports">
                    <i class="fas fa-file-chart"></i>
                </div>
                <h6>Rapports</h6>
                <p class="text-muted text-center mb-0">Génération de rapports détaillés</p>
            </div>
            
            <div class="action-coordinateur" onclick="ouvrirNotifications()">
                <div class="action-icon notifications">
                    <i class="fas fa-bell"></i>
                </div>
                <h6>Notifications</h6>
                <p class="text-muted text-center mb-0">Alertes et communications</p>
            </div>
        </div>

        <!-- Widgets coordinateur -->
        <div class="coordinateur-grid">
            <!-- Allocation horaire par module -->
            <div class="widget-coordinateur">
                <div class="widget-header">
                    <div class="widget-title">
                        <i class="fas fa-clock text-primary"></i>
                        Allocation Horaire
                    </div>
                    <div class="widget-actions">
                        <button class="btn btn-sm btn-outline-primary" onclick="editerAllocations()">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>
                
                @forelse($allocationHoraire as $allocation)
                <div class="allocation-item">
                    <div class="allocation-info">
                        <strong>{{ $allocation['module'] ?? 'Module' }}</strong>
                        <div class="text-muted small">{{ $allocation['description'] ?? 'Description' }}</div>
                    </div>
                    <div class="allocation-hours">
                        {{ $allocation['heures'] ?? '0' }}h
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-clock fa-2x mb-3"></i>
                    <p>Aucune allocation horaire définie</p>
                    <button class="btn btn-primary btn-sm" onclick="creerAllocation()">
                        Créer une allocation
                    </button>
                </div>
                @endforelse
            </div>

            <!-- Programmation hebdomadaire -->
            <div class="widget-coordinateur">
                <div class="widget-header">
                    <div class="widget-title">
                        <i class="fas fa-calendar-week text-info"></i>
                        Programmation Semaine
                    </div>
                    <div class="widget-actions">
                        <select class="form-select form-select-sm" onchange="changerSemaine(this.value)">
                            <option value="current">Semaine actuelle</option>
                            <option value="next">Semaine prochaine</option>
                        </select>
                    </div>
                </div>
                
                <div class="programmation-semaine">
                    <div class="jour-semaine">Lun</div>
                    <div class="jour-semaine">Mar</div>
                    <div class="jour-semaine">Mer</div>
                    <div class="jour-semaine">Jeu</div>
                    <div class="jour-semaine">Ven</div>
                    <div class="jour-semaine">Sam</div>
                    <div class="jour-semaine">Dim</div>
                </div>
                
                @forelse($programmationHebdomadaire as $jour => $seances)
                <div class="mb-3">
                    @foreach($seances as $seance)
                    <div class="seance-programmee" onclick="voirDetailSeance('{{ $seance['id'] ?? '' }}')">
                        <strong>{{ $seance['matiere'] ?? 'Matière' }}</strong>
                        <div class="text-muted">{{ $seance['horaire'] ?? 'Horaire' }} - {{ $seance['classe'] ?? 'Classe' }}</div>
                    </div>
                    @endforeach
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-calendar-times fa-2x mb-3"></i>
                    <p>Aucune séance programmée</p>
                </div>
                @endforelse
            </div>

            <!-- Codes d'émargement actifs -->
            <div class="widget-coordinateur">
                <div class="widget-header">
                    <div class="widget-title">
                        <i class="fas fa-qrcode text-success"></i>
                        Codes Émargement
                    </div>
                    <div class="widget-actions">
                        <button class="btn btn-sm btn-success" onclick="genererNouveauCode()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                
                @forelse($codesEmargement as $code)
                <div class="code-emargement {{ $code['expire'] ? 'expire' : '' }}">
                    <div>
                        <div class="code-value">{{ $code['code'] ?? 'XXXX' }}</div>
                        <small class="text-muted">{{ $code['cours'] ?? 'Cours' }} - {{ $code['expire_dans'] ?? 'Expiré' }}</small>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-danger" onclick="annulerCode('{{ $code['id'] ?? '' }}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-qrcode fa-2x mb-3"></i>
                    <p>Aucun code actif</p>
                    <button class="btn btn-success btn-sm" onclick="genererNouveauCode()">
                        Générer un code
                    </button>
                </div>
                @endforelse
            </div>

            <!-- Taux de présence par classe -->
            <div class="widget-coordinateur">
                <div class="widget-header">
                    <div class="widget-title">
                        <i class="fas fa-users text-warning"></i>
                        Taux de Présence
                    </div>
                    <div class="widget-actions">
                        <button class="btn btn-sm btn-outline-warning" onclick="voirRapportPresence()">
                            <i class="fas fa-chart-bar"></i>
                        </button>
                    </div>
                </div>
                
                @forelse($tauxPresenceClasses as $classe)
                <div class="taux-presence">
                    <div>
                        <strong>{{ $classe['nom'] ?? 'Classe' }}</strong>
                        <div class="text-muted small">{{ $classe['effectif'] ?? '0' }} étudiants</div>
                    </div>
                    <div class="taux-cercle 
                        @if(($classe['taux'] ?? 0) >= 90) taux-excellent
                        @elseif(($classe['taux'] ?? 0) >= 75) taux-bon
                        @elseif(($classe['taux'] ?? 0) >= 60) taux-moyen
                        @else taux-faible
                        @endif">
                        {{ $classe['taux'] ?? '0' }}%
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-users fa-2x mb-3"></i>
                    <p>Aucune donnée de présence</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Actions coordinateur
function ouvrirGestionPlanning() {
    alert('Redirection vers la gestion du planning');
}

function ouvrirValidationSeances() {
    alert('Redirection vers la validation des séances');
}

function ouvrirAnalytics() {
    alert('Redirection vers les analytics');
}

function ouvrirParametres() {
    alert('Redirection vers les paramètres');
}

function ouvrirRapports() {
    alert('Redirection vers les rapports');
}

function ouvrirNotifications() {
    alert('Redirection vers les notifications');
}

// Gestion des allocations
function editerAllocations() {
    alert('Édition des allocations horaires');
}

function creerAllocation() {
    alert('Création d\'une nouvelle allocation');
}

// Gestion de la programmation
function changerSemaine(semaine) {
    console.log('Changement vers la semaine:', semaine);
}

function voirDetailSeance(seanceId) {
    alert('Détail de la séance: ' + seanceId);
}

// Gestion des codes d'émargement
function genererNouveauCode() {
    alert('Génération d\'un nouveau code');
}

function annulerCode(codeId) {
    if (confirm('Êtes-vous sûr de vouloir annuler ce code ?')) {
        alert('Code annulé: ' + codeId);
    }
}

// Gestion des présences
function voirRapportPresence() {
    alert('Redirection vers le rapport de présence');
}

// Génération de rapport
function genererRapport() {
    alert('Génération du rapport coordinateur');
}

$(function() {
    // Animation des widgets
    $('.widget-coordinateur').each(function(index) {
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
    
    // Actualisation automatique des codes d'émargement
    setInterval(function() {
        // Logique d'actualisation des codes
        console.log('Actualisation des codes d\'émargement');
    }, 30000); // Toutes les 30 secondes
});
</script>
@endpush