@extends('layouts.app')

@section('title', 'Gestion des Codes d\'Émargement')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles pour le nouveau design de la page codes d'émargement */
    
    /* Section Code Actuel - Layout en grille */
    .code-display-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: var(--space-xl);
        align-items: start;
    }

    .code-main-display {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--space-md);
    }

    .code-value {
        font-family: 'Courier New', monospace;
        font-size: 3.5rem;
        font-weight: 900;
        background: linear-gradient(135deg, var(--primary), var(--accent-blue));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-align: center;
        padding: var(--space-xl);
        border: 3px dashed var(--primary);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-elevated);
        background-color: white;
        min-width: 200px;
    }

    .code-badge {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        padding: var(--space-sm) var(--space-md);
        background: var(--success);
        color: white;
        border-radius: var(--radius-full);
        font-weight: 600;
        font-size: 0.875rem;
    }

    /* Grille d'informations du code */
    .code-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-md);
    }

    .info-card {
        display: flex;
        gap: var(--space-md);
        padding: var(--space-lg);
        background: white;
        border-radius: var(--radius-medium);
        border: 1px solid var(--border-light);
        box-shadow: var(--shadow-light);
        transition: all 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }

    .info-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: var(--radius-medium);
        font-size: 1.25rem;
    }

    .info-card.expiry .info-icon {
        background: rgba(251, 191, 36, 0.1);
        color: #f59e0b;
    }

    .info-card.seance .info-icon {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary);
    }

    .info-card.generator .info-icon {
        background: rgba(139, 92, 246, 0.1);
        color: #8b5cf6;
    }

    .info-card.type .info-icon {
        background: rgba(34, 197, 94, 0.1);
        color: var(--success);
    }

    .info-content {
        flex: 1;
    }

    .info-label {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin-bottom: var(--space-xs);
        font-weight: 500;
    }

    .info-value {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }

    .info-extra {
        font-size: 0.875rem;
        color: var(--text-secondary);
        line-height: 1.4;
    }

    /* Tableau moderne pour les codes récents */
    .modern-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: var(--radius-medium);
        overflow: hidden;
        box-shadow: var(--shadow-light);
    }

    .modern-table thead {
        background: linear-gradient(135deg, var(--primary), var(--accent-blue));
        color: white;
    }

    .modern-table th {
        padding: var(--space-lg);
        text-align: left;
        font-weight: 600;
        font-size: 0.875rem;
        border: none;
    }

    .modern-table th i {
        margin-right: var(--space-xs);
        opacity: 0.8;
    }

    .modern-table tbody tr {
        border-bottom: 1px solid var(--border-light);
        transition: all 0.3s ease;
    }

    .modern-table tbody tr:hover {
        background: rgba(59, 130, 246, 0.05);
    }

    .modern-table tbody tr.row-active {
        background: rgba(34, 197, 94, 0.05);
        border-left: 4px solid var(--success);
    }

    .modern-table td {
        padding: var(--space-lg);
        vertical-align: top;
        border: none;
    }

    .code-badge-table {
        display: inline-block;
        font-family: 'Courier New', monospace;
        font-weight: 700;
        font-size: 1.125rem;
        padding: var(--space-sm) var(--space-md);
        background: linear-gradient(135deg, var(--primary), var(--accent-blue));
        color: white;
        border-radius: var(--radius-medium);
        letter-spacing: 1px;
    }

    .seance-info-table .seance-title {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }

    .seance-info-table .seance-details {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin-bottom: var(--space-xs);
    }

    .seance-info-table .seance-teacher {
        font-size: 0.875rem;
        color: var(--accent-blue);
        font-style: italic;
    }

    .date-info .date-main {
        font-weight: 600;
        color: var(--text-primary);
    }

    .date-info .date-time {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin-top: var(--space-xs);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        font-weight: 500;
        color: var(--text-primary);
    }

    .user-info i {
        color: var(--accent-blue);
    }

    .stats-info .stats-main {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 1.125rem;
    }

    .stats-info .stats-percent {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin-top: var(--space-xs);
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .code-display-grid {
            grid-template-columns: 1fr;
            gap: var(--space-lg);
        }

        .code-info-grid {
            grid-template-columns: 1fr;
        }

        .code-value {
            font-size: 2.5rem;
        }

        .modern-table {
            font-size: 0.875rem;
        }

        .modern-table th, 
        .modern-table td {
            padding: var(--space-md);
        }
    }

    .countdown {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        color: var(--primary);
    }

    .code-display-small {
        font-family: 'Courier New', monospace;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--primary);
        padding: var(--space-sm) var(--space-md);
        background: rgba(30, 58, 138, 0.05);
        border-radius: var(--radius-small);
        border: 1px solid var(--primary);
    }

    /* Action button manquant */
    .action-button {
        display: inline-flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-sm) var(--space-md);
        border: none;
        border-radius: var(--radius-small);
        font-size: var(--text-normal);
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .action-button.primary {
        background-color: var(--primary);
        color: white;
    }

    .action-button.secondary {
        background-color: transparent;
        color: var(--primary);
        border: 1px solid var(--primary);
    }

    .action-button.danger {
        background-color: var(--danger);
        color: white;
    }

    .action-button:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-elevated);
    }

    /* Meta items pour les listes */
    .meta-item {
        display: inline-flex;
        align-items: center;
        gap: var(--space-xs);
        margin-right: var(--space-md);
        font-size: var(--text-small);
        color: var(--text-secondary);
    }

    /* Course title et details */
    .course-title {
        font-size: var(--text-normal);
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }

    .course-details {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-top: var(--space-xs);
    }

    .course-meta {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-sm);
        margin-top: var(--space-sm);
    }

    /* Button icon manquant */
    .btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: var(--radius-circle);
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }

    .btn-icon:hover {
        background: var(--danger);
        color: white;
        transform: translateY(-1px);
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: var(--space-xl);
        color: var(--text-secondary);
    }

    .empty-icon {
        font-size: 3rem;
        color: var(--text-muted);
        margin-bottom: var(--space-md);
    }

    /* Modal fixes minimes */
    .modal-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-bottom: none;
    }

    .modal-title {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .form-group {
        margin-bottom: var(--space-lg);
    }

    .form-label {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
        display: block;
    }

    .form-text {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-top: var(--space-xs);
    }
</style>
@endsection

@section('content')
<div class="dashboard-main-grid">
    <div class="dashboard-header">
        <h1 class="dashboard-title">
            <i class="fas fa-qrcode"></i>
            Gestion des Codes d'Émargement
        </h1>
        <p class="dashboard-subtitle">Interface coordinateur pour la génération et gestion des codes quotidiens</p>
    </div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Code d'Émargement Actuel -->
    <div class="main-card">
        <div class="main-card-header">
            <h2 class="main-card-title">
                <i class="fas fa-code"></i>
                Code d'Émargement Actuel
            </h2>
        </div>
        <div class="main-card-body">
            @if($activeCode)
                <div class="code-display-grid">
                    <!-- Code principal avec design modernisé -->
                    <div class="code-main-display">
                        <div class="code-value">{{ $activeCode->code }}</div>
                        <div class="code-badge">
                            <i class="fas fa-check-circle"></i>
                            Code Actif
                        </div>
                    </div>
                    
                    <!-- Informations du code dans une grille -->
                    <div class="code-info-grid">
                        @if($activeCode->valid_until)
                            <div class="info-card expiry">
                                <div class="info-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Expiration</div>
                                    <div class="info-value">{{ $activeCode->valid_until->format('d/m/Y à H:i') }}</div>
                                    <div class="info-extra">
                                        <span id="remaining-time">{{ $activeCode->getRemainingValidityInMinutes() }} min restantes</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        @if($activeCode->seance)
                            <div class="info-card seance">
                                <div class="info-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Séance</div>
                                    <div class="info-value">{{ $activeCode->seance->matiere->name ?? 'Matière' }}</div>
                                    <div class="info-extra">
                                        {{ $activeCode->seance->classe->name ?? 'Classe' }} • {{ $activeCode->seance->heure_debut }} - {{ $activeCode->seance->heure_fin }}
                                        @if($activeCode->seance->teacher)
                                            <br>{{ $activeCode->seance->teacher->name }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <div class="info-card generator">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Générateur</div>
                                <div class="info-value">{{ $activeCode->generator->name ?? 'Système' }}</div>
                                <div class="info-extra">{{ $activeCode->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                        
                        <div class="info-card type">
                            <div class="info-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Type</div>
                                <div class="info-value">{{ ucfirst($activeCode->type) }}</div>
                                <div class="info-extra">{{ $activeCode->description ?? 'Code standard' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-value">{{ $activeCode->total_attempts }}</div>
                        <div class="kpi-label">Total tentatives</div>
                    </div>
                    <div class="kpi-card success">
                        <div class="kpi-value">{{ $activeCode->successful_attempts }}</div>
                        <div class="kpi-label">Réussies</div>
                    </div>
                    <div class="kpi-card danger">
                        <div class="kpi-value">{{ $activeCode->failed_attempts }}</div>
                        <div class="kpi-label">Échouées</div>
                    </div>
                    <div class="kpi-card info">
                        <div class="kpi-value">{{ $activeCode->total_attempts > 0 ? round(($activeCode->successful_attempts / $activeCode->total_attempts) * 100, 1) : 0 }}%</div>
                        <div class="kpi-label">Taux de réussite</div>
                    </div>
                </div>

                <div class="quick-actions-grid">
                    <form action="{{ route('esbtp.attendance-codes.invalidate', $activeCode->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="action-button danger" onclick="return confirm('Êtes-vous sûr de vouloir invalider ce code ?')">
                            <i class="fas fa-ban"></i>
                            <span>Invalider le code</span>
                        </button>
                    </form>
                    <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST">
                        @csrf
                        <button type="submit" class="action-button primary" onclick="return confirm('Générer un nouveau code invalidera l\'actuel. Continuer ?')">
                            <i class="fas fa-sync-alt"></i>
                            <span>Renouveler le code</span>
                        </button>
                    </form>
                    <button type="button" class="action-button secondary" data-bs-toggle="modal" data-bs-target="#customCodeModal">
                        <i class="fas fa-edit"></i>
                        <span>Code personnalisé</span>
                    </button>
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3>Aucun code d'émargement actif</h3>
                    <p>Générez un nouveau code pour permettre aux enseignants de s'émarger aujourd'hui.</p>
                    <div class="quick-actions-grid">
                        <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST">
                            @csrf
                            <button type="submit" class="action-button primary">
                                <i class="fas fa-plus-circle"></i>
                                <span>Générer le code du jour</span>
                            </button>
                        </form>
                        <button type="button" class="action-button secondary" data-bs-toggle="modal" data-bs-target="#customCodeModal">
                            <i class="fas fa-edit"></i>
                            <span>Code personnalisé</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Codes Récents -->
    <div class="main-card">
        <div class="main-card-header">
            <h2 class="main-card-title">
                <i class="fas fa-history"></i>
                Codes Récents
            </h2>
        </div>
        <div class="main-card-body">
            @if($recentCodes->count() > 0)
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th class="text-center">
                                    <i class="fas fa-code"></i>
                                    Code
                                </th>
                                <th>
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    Séance
                                </th>
                                <th>
                                    <i class="fas fa-calendar"></i>
                                    Création
                                </th>
                                <th>
                                    <i class="fas fa-user"></i>
                                    Générateur
                                </th>
                                <th class="text-center">
                                    <i class="fas fa-chart-bar"></i>
                                    Statistiques
                                </th>
                                <th class="text-center">
                                    <i class="fas fa-info-circle"></i>
                                    Statut
                                </th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentCodes as $code)
                                <tr class="table-row {{ $code->status === 'active' ? 'row-active' : '' }}">
                                    <td class="text-center">
                                        <span class="code-badge-table">{{ $code->code }}</span>
                                    </td>
                                    <td>
                                        @if($code->seance)
                                            <div class="seance-info-table">
                                                <div class="seance-title">{{ $code->seance->matiere->name ?? 'Matière' }}</div>
                                                <div class="seance-details">
                                                    {{ $code->seance->classe->name ?? 'Classe' }}
                                                    @if($code->seance->heure_debut && $code->seance->heure_fin)
                                                        • {{ $code->seance->heure_debut }} - {{ $code->seance->heure_fin }}
                                                    @endif
                                                </div>
                                                @if($code->seance->teacher)
                                                    <div class="seance-teacher">{{ $code->seance->teacher->name }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">
                                                <i class="fas fa-minus"></i>
                                                Code général
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="date-info">
                                            <div class="date-main">{{ $code->created_at->format('d/m/Y') }}</div>
                                            <div class="date-time">{{ $code->created_at->format('H:i') }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <i class="fas fa-user-circle"></i>
                                            {{ $code->generator->name ?? 'Système' }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="stats-info">
                                            <div class="stats-main">
                                                {{ $code->successful_attempts }}/{{ $code->total_attempts }}
                                            </div>
                                            <div class="stats-percent">
                                                {{ $code->total_attempts > 0 ? round(($code->successful_attempts / $code->total_attempts) * 100, 1) : 0 }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($code->status === 'active')
                                            <span class="status-badge success">
                                                <i class="fas fa-check-circle"></i>
                                                Actif
                                            </span>
                                        @elseif($code->status === 'expired')
                                            <span class="status-badge warning">
                                                <i class="fas fa-clock"></i>
                                                Expiré
                                            </span>
                                        @else
                                            <span class="status-badge danger">
                                                <i class="fas fa-ban"></i>
                                                Annulé
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($code->status === 'active')
                                            <form action="{{ route('esbtp.attendance-codes.invalidate', $code->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn-icon danger" onclick="return confirm('Invalider ce code ?')" title="Invalider">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted">
                                                <i class="fas fa-minus"></i>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3>Aucun historique de codes</h3>
                    <p>Les codes générés apparaîtront ici</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Code Personnalisé -->
    <div class="modal fade" id="customCodeModal" tabindex="-1" aria-labelledby="customCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modern-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="customCodeModalLabel">
                        <i class="fas fa-edit"></i>
                        Générer un Code Personnalisé
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="custom_description" class="form-label">Description du code</label>
                            <input type="text" class="form-control" id="custom_description" name="description" 
                                   placeholder="Ex: Code pour cours de mathématiques" maxlength="255">
                            <div class="form-text">Description optionnelle pour identifier ce code</div>
                        </div>

                        <div class="form-group">
                            <label for="custom_duration" class="form-label">Durée de validité</label>
                            <select class="form-select" id="custom_duration" name="duration_minutes">
                                <option value="30">30 minutes</option>
                                <option value="60" selected>1 heure</option>
                                <option value="90">1h30</option>
                                <option value="120">2 heures</option>
                                <option value="180">3 heures</option>
                                <option value="240">4 heures</option>
                                <option value="480">8 heures (journée)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="seance_select" class="form-label">Séance de cours (optionnel)</label>
                            <select class="form-select" id="seance_select" name="seance_id">
                                <option value="">Aucune séance spécifique</option>
                                @if(isset($seancesAVenir) && $seancesAVenir->count() > 0)
                                    @foreach($seancesAVenir as $seance)
                                        <option value="{{ $seance->id }}" 
                                                data-duration="{{ $seance->getDureeEnMinutes() }}" 
                                                data-description="{{ ($seance->matiere->name ?? 'Matière') }} - {{ ($seance->classe->name ?? 'Classe') }}">
                                            {{ $seance->matiere->name ?? 'Matière' }} - {{ $seance->classe->name ?? 'Classe' }} 
                                            ({{ \Carbon\Carbon::parse($seance->date_cours ?? 'today')->format('d/m') }} {{ $seance->heure_debut }}-{{ $seance->heure_fin }})
                                            @if($seance->teacher) - {{ $seance->teacher->name }}@endif
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="form-text">Sélectionner une séance remplira automatiquement la durée et la description</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Générer le Code
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        });
    }, 5000);

    @if($activeCode && $activeCode->valid_until)
    // Countdown timer
    const expirationTime = new Date("{{ $activeCode->valid_until->format('Y-m-d H:i:s') }}").getTime();
    const remainingTimeElement = document.getElementById('remaining-time');
    
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = expirationTime - now;
        
        if (distance < 0) {
            remainingTimeElement.innerHTML = '<span style="color: var(--danger);">⚠️ Code expiré - Actualisation en cours...</span>';
            setTimeout(() => window.location.reload(), 2000);
            return;
        }
        
        const hours = Math.floor(distance / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        if (hours > 0) {
            remainingTimeElement.innerHTML = `🕐 ${hours}h ${minutes}min ${seconds}s restantes`;
        } else if (minutes > 0) {
            if (minutes <= 5) {
                remainingTimeElement.innerHTML = `<span style="color: var(--warning);">⚠️ ${minutes}min ${seconds}s restantes</span>`;
            } else {
                remainingTimeElement.innerHTML = `🕐 ${minutes}min ${seconds}s restantes`;
            }
        } else {
            remainingTimeElement.innerHTML = `<span style="color: var(--danger);">🚨 ${seconds}s restantes</span>`;
        }
    }
    
    // Update countdown every second
    updateCountdown();
    const countdownInterval = setInterval(updateCountdown, 1000);
    @endif
    
    // Auto-refresh every 10 minutes to get updated data
    setTimeout(function() {
        console.log('Auto-refreshing page for updated data...');
        window.location.reload();
    }, 600000); // 10 minutes
    
    // Gestion de la sélection de séance dans le modal
    const seanceSelect = document.getElementById('seance_select');
    const durationSelect = document.getElementById('custom_duration');
    const descriptionInput = document.getElementById('custom_description');
    
    if (seanceSelect) {
        seanceSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                // Remplir automatiquement la durée
                const duration = selectedOption.getAttribute('data-duration');
                if (duration) {
                    durationSelect.value = duration;
                }
                
                // Remplir automatiquement la description
                const description = selectedOption.getAttribute('data-description');
                if (description && !descriptionInput.value) {
                    descriptionInput.value = 'Code pour ' + description;
                }
            }
        });
    }
    
    // Animation des boutons d'action
    document.querySelectorAll('.action-button').forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
@endsection
