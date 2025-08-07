@extends('layouts.app')

@section('title', 'Gestion des Codes d\'Émargement')

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
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: var(--space-md);
        margin: var(--space-md) 0;
    }

    .stat-item {
        text-align: center;
        padding: var(--space-md);
        border-radius: var(--radius-medium);
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border: 1px solid #e5e7eb;
    }

    .stat-value {
        font-size: var(--amount-large);
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
    }

    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-md) var(--space-lg);
        border: none;
        border-radius: var(--radius-medium);
        font-weight: 600;
        font-size: var(--text-normal);
        transition: all 0.3s ease;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-modern.primary {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        box-shadow: var(--shadow-card);
    }

    .btn-modern.primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: white;
    }

    .btn-modern.danger {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        color: white;
    }

    .btn-modern.danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        color: white;
    }

    .status-badge-modern {
        display: inline-flex;
        align-items: center;
        padding: var(--space-xs) var(--space-md);
        border-radius: var(--radius-large);
        font-size: var(--text-small);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-badge-modern.active { 
        background-color: rgba(16, 185, 129, 0.1); 
        color: var(--success); 
        border: 1px solid rgba(16, 185, 129, 0.2); 
    }

    .status-badge-modern.expired { 
        background-color: rgba(245, 158, 11, 0.1); 
        color: var(--warning); 
        border: 1px solid rgba(245, 158, 11, 0.2); 
    }

    .status-badge-modern.cancelled { 
        background-color: rgba(239, 68, 68, 0.1); 
        color: var(--danger); 
        border: 1px solid rgba(239, 68, 68, 0.2); 
    }

    .main-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: var(--space-lg);
    }

    .main-card-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: var(--space-lg);
        border-bottom: none;
    }

    .main-card-title {
        font-size: var(--title-main);
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }

    .countdown-timer {
        font-family: 'Courier New', monospace;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        text-align: center;
        margin: var(--space-md) 0;
        padding: var(--space-md);
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        border-radius: var(--radius-medium);
        border: 1px solid var(--accent-blue);
    }

    .history-table {
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    .history-table th {
        background: #f8fafc;
        color: var(--text-primary);
        font-weight: 600;
        padding: var(--space-md);
        border-bottom: 2px solid #e5e7eb;
        font-size: var(--text-small);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .history-table td {
        padding: var(--space-md);
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .history-table tr:hover {
        background-color: #f8fafc;
    }

    .empty-state {
        text-align: center;
        padding: var(--space-xl);
        color: var(--text-secondary);
    }

    .empty-state i {
        font-size: 3rem;
        color: var(--text-muted);
        margin-bottom: var(--space-md);
    }

    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: var(--space-lg);
    }

    .dashboard-header {
        text-align: center;
        margin-bottom: var(--space-xl);
    }

    .dashboard-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
    }

    .dashboard-subtitle {
        color: var(--text-secondary);
        font-size: var(--text-normal);
    }
</style>
@endsection

@section('content')
<div class="dashboard-container">
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

    <!-- Code Actif -->
    <div class="main-card">
        <div class="main-card-header">
            <h2 class="main-card-title">
                <i class="fas fa-code"></i>
                Code d'émargement du jour
            </h2>
        </div>
        <div class="p-4">
            @if($activeCode)
                <div class="code-display-large">
                    {{ $activeCode->code }}
                </div>
                
                <div class="countdown-timer" id="countdown-timer">
                    @if($activeCode->valid_until)
                        Expire le : {{ $activeCode->valid_until->format('d/m/Y à H:i') }}
                        <br>
                        <span id="remaining-time">{{ $activeCode->getRemainingValidityInMinutes() }} minutes restantes</span>
                    @else
                        Date d'expiration inconnue
                    @endif
                </div>

                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value">{{ $activeCode->total_attempts }}</div>
                        <div class="stat-label">Total tentatives</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ $activeCode->successful_attempts }}</div>
                        <div class="stat-label">Réussies</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ $activeCode->failed_attempts }}</div>
                        <div class="stat-label">Échouées</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ $activeCode->total_attempts > 0 ? round(($activeCode->successful_attempts / $activeCode->total_attempts) * 100, 1) : 0 }}%</div>
                        <div class="stat-label">Taux réussite</div>
                    </div>
                </div>

                <div class="action-buttons">
                    <form action="{{ route('esbtp.attendance-codes.invalidate', $activeCode->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn-modern danger" onclick="return confirm('Êtes-vous sûr de vouloir invalider ce code ?')">
                            <i class="fas fa-ban"></i>
                            Invalider le code
                        </button>
                    </form>
                    <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn-modern primary" onclick="return confirm('Générer un nouveau code invalidera l\'actuel. Continuer ?')">
                            <i class="fas fa-sync-alt"></i>
                            Renouveler le code
                        </button>
                    </form>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-code"></i>
                    <p>Aucun code d'émargement actif</p>
                    <p class="text-muted">Générez un nouveau code pour permettre aux enseignants de s'émarger aujourd'hui.</p>
                </div>
                
                <div class="action-buttons">
                    <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-modern primary">
                            <i class="fas fa-plus-circle"></i>
                            Générer le code du jour
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Historique des Codes -->
    <div class="main-card">
        <div class="main-card-header">
            <h2 class="main-card-title">
                <i class="fas fa-history"></i>
                Historique des codes
            </h2>
        </div>
        <div class="p-0">
            @if($recentCodes->count() > 0)
                <div class="table-responsive">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Généré le</th>
                                <th>Par</th>
                                <th>Statut</th>
                                <th>Tentatives</th>
                                <th>Taux</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentCodes as $code)
                                <tr>
                                    <td>
                                        <span style="font-family: 'Courier New', monospace; font-weight: 600; font-size: 16px;">
                                            {{ $code->code }}
                                        </span>
                                    </td>
                                    <td>{{ $code->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $code->generator->name ?? 'Système' }}</td>
                                    <td>
                                        @if($code->status === 'active')
                                            <span class="status-badge-modern active">
                                                <i class="fas fa-check-circle me-1"></i>Actif
                                            </span>
                                        @elseif($code->status === 'expired')
                                            <span class="status-badge-modern expired">
                                                <i class="fas fa-clock me-1"></i>Expiré
                                            </span>
                                        @else
                                            <span class="status-badge-modern cancelled">
                                                <i class="fas fa-ban me-1"></i>Annulé
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $code->successful_attempts }}/{{ $code->total_attempts }}</td>
                                    <td>{{ $code->total_attempts > 0 ? round(($code->successful_attempts / $code->total_attempts) * 100, 1) : 0 }}%</td>
                                    <td>
                                        @if($code->status === 'active')
                                            <form action="{{ route('esbtp.attendance-codes.invalidate', $code->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Invalider ce code ?')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>Aucun historique de codes</p>
                    <p class="text-muted">Les codes générés apparaîtront ici</p>
                </div>
            @endif
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
    
    // Add smooth animations to buttons
    document.querySelectorAll('.btn-modern').forEach(button => {
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
