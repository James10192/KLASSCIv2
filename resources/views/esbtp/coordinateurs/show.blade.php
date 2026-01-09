@extends('layouts.app')

@section('title', 'Profil Coordinateur - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .coordinator-profile {
        padding: 0;
    }
    
    .profile-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: var(--space-xl);
        position: relative;
        overflow: hidden;
        border-radius: var(--radius-large);
        box-shadow: var(--shadow-card);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-xl);
    }
    
    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transform: skewX(-15deg);
        transform-origin: top;
    }
    
    .profile-hero {
        display: flex;
        align-items: center;
        gap: var(--space-lg);
        position: relative;
        z-index: 2;
        flex: 1;
        min-width: 0;
        flex-wrap: wrap;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: bold;
        border: 4px solid rgba(255,255,255,0.3);
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    }
    
    .profile-info h1 {
        margin: 0 0 var(--space-xs) 0;
        font-size: 2rem;
        font-weight: 700;
    }
    
    .profile-info p {
        margin: 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .profile-meta {
        display: flex;
        gap: var(--space-md);
        margin-top: var(--space-md);
        flex-wrap: wrap;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.9rem;
    }
    
    .meta-item i {
        line-height: 1;
        transform: translateY(1px);
    }
    
    .profile-actions {
        position: static;
        display: flex;
        gap: var(--space-sm);
        z-index: 3;
        flex-shrink: 0;
    }
    
    .profile-content {
        padding: var(--space-xl);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: var(--space-xl);
    }
    
    .info-section {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        border: 1px solid var(--border);
    }
    
    .section-title {
        color: var(--primary);
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .section-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(var(--primary-rgb), 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 0.9rem;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-sm) 0;
        border-bottom: 1px solid var(--border);
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 500;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .info-value {
        color: var(--text-primary);
        font-weight: 500;
    }
    
    .status-badge {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-active {
        background: rgba(var(--success-rgb), 0.1);
        color: var(--success);
    }
    
    .status-inactive {
        background: rgba(var(--danger-rgb), 0.1);
        color: var(--danger);
    }
    
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .stat-card {
        background: linear-gradient(180deg, rgba(255,255,255,0.95), rgba(255,255,255,0.85));
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        text-align: center;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: var(--space-xs);
        min-height: 110px;
    }
    
    .stat-number {
        font-size: 1.7rem;
        font-weight: 700;
        color: var(--primary);
        display: block;
        line-height: 1.1;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-top: var(--space-xs);
        line-height: 1.3;
    }
    
    .sidebar-actions {
        position: sticky;
        top: var(--space-lg);
    }
    
    .action-card {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        border: 1px solid var(--border);
    }
    
    .action-grid {
        display: flex;
        flex-direction: column;
        gap: var(--space-sm);
    }
    
    .contact-item {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-sm) 0;
        border-bottom: 1px solid var(--border);
    }
    
    .contact-item:last-child {
        border-bottom: none;
    }
    
    .contact-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: rgba(var(--primary-rgb), 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    
    .activity-item {
        display: flex;
        gap: var(--space-md);
        padding: var(--space-md) 0;
        border-bottom: 1px solid var(--border);
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-date {
        flex-shrink: 0;
        width: 80px;
        font-size: 0.8rem;
        color: var(--text-secondary);
        font-weight: 500;
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-title {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .activity-desc {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }
    
    .empty-state {
        text-align: center;
        padding: var(--space-xl);
        color: var(--text-secondary);
    }
    
    .empty-state .icon {
        font-size: 3rem;
        margin-bottom: var(--space-md);
        opacity: 0.5;
    }
    
    @media (max-width: 1024px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .profile-hero {
            flex-direction: column;
            text-align: center;
        }
        
        .profile-actions {
            justify-content: center;
            margin-top: var(--space-md);
        }
    }
    
    @media (max-width: 768px) {
        .profile-meta {
            flex-direction: column;
            gap: var(--space-sm);
        }
        
        .quick-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="coordinator-profile">
            <!-- En-tête du profil -->
            <div class="profile-header">
                <div class="profile-hero">
                    <div class="profile-avatar">
                        {{ strtoupper(substr($coordinateur->name, 0, 2)) }}
                    </div>
                    <div class="profile-info">
                        <h1>{{ $coordinateur->name }}</h1>
                        <p>{{ $coordinateur->specialite ?? 'Coordinateur pédagogique' }}</p>
                        <div class="profile-meta">
                            <div class="meta-item">
                                <i class="fas fa-user-tie"></i>
                                <span>Coordinateur</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>Depuis {{ $coordinateur->created_at->format('M Y') }}</span>
                            </div>
                            @if($coordinateur->last_login_at)
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span>Dernière connexion {{ $coordinateur->last_login_at->diffForHumans() }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <a href="{{ route('esbtp.coordinateurs.edit', $coordinateur) }}" class="btn-acasi primary">
                        <i class="fas fa-edit me-1"></i>Modifier
                    </a>
                    <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                </div>
            </div>
            
            <!-- Contenu du profil -->
            <div class="profile-content">
                <!-- Statistiques rapides -->
                <div class="quick-stats">
                    <div class="stat-card">
                        <span class="stat-number">{{ $coordinationsCount ?? 0 }}</span>
                        <div class="stat-label">Coordinations</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">{{ $activitiesCount ?? 0 }}</span>
                        <div class="stat-label">Activités</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">{{ $coordinateur->created_at->diffInDays(now()) }}</span>
                        <div class="stat-label">Jours d'ancienneté</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number status-badge {{ $coordinateur->is_active ? 'status-active' : 'status-inactive' }}">
                            {{ $coordinateur->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                        <div class="stat-label">Statut</div>
                    </div>
                </div>
                
                <div class="info-grid">
                    <!-- Colonne principale -->
                    <div class="main-info">
                        <!-- Informations personnelles -->
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                Informations Personnelles
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">Nom complet</span>
                                <span class="info-value">{{ $coordinateur->name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value">{{ $coordinateur->email }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Téléphone</span>
                                <span class="info-value">{{ $coordinateur->phone ?? 'Non renseigné' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Nom d'utilisateur</span>
                                <span class="info-value">{{ $coordinateur->username }}</span>
                            </div>
                            @if($coordinateur->specialite)
                            <div class="info-row">
                                <span class="info-label">Spécialité</span>
                                <span class="info-value">{{ $coordinateur->specialite }}</span>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Informations professionnelles -->
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                Informations Professionnelles
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">Rôle</span>
                                <span class="info-value">Coordinateur pédagogique</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Statut du compte</span>
                                <span class="info-value">
                                    <span class="status-badge {{ $coordinateur->is_active ? 'status-active' : 'status-inactive' }}">
                                        {{ $coordinateur->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Première connexion</span>
                                <span class="info-value">{{ $coordinateur->first_login_at ? $coordinateur->first_login_at->format('d/m/Y H:i') : 'Jamais connecté' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Dernière connexion</span>
                                <span class="info-value">{{ $coordinateur->last_login_at ? $coordinateur->last_login_at->format('d/m/Y H:i') : 'Jamais connecté' }}</span>
                            </div>
                        </div>
                        
                        <!-- Activités récentes -->
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                Activités Récentes
                            </div>
                            
                            @if(isset($recentActivities) && $recentActivities->count() > 0)
                                @foreach($recentActivities as $activity)
                                <div class="activity-item">
                                    <div class="activity-date">
                                        {{ $activity->created_at->format('d/m') }}
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">{{ $activity->title }}</div>
                                        <div class="activity-desc">{{ $activity->description }}</div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="empty-state">
                                    <div class="icon">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <p>Aucune activité récente</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Barre latérale -->
                    <div class="sidebar-actions">
                        <!-- Actions rapides -->
                        <div class="action-card">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-tools"></i>
                                </div>
                                Actions Rapides
                            </div>
                            
                            <div class="action-grid">
                                <a href="{{ route('esbtp.coordinateurs.edit', $coordinateur) }}" class="btn-acasi primary">
                                    <i class="fas fa-edit me-2"></i>Modifier le profil
                                </a>
                                
                                @if($coordinateur->is_active)
                                <form action="{{ route('esbtp.coordinateurs.toggle-status', $coordinateur) }}" method="POST" 
                                      onsubmit="return confirm('Désactiver ce coordinateur ?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-acasi warning w-100">
                                        <i class="fas fa-pause me-2"></i>Désactiver
                                    </button>
                                </form>
                                @else
                                <form action="{{ route('esbtp.coordinateurs.toggle-status', $coordinateur) }}" method="POST" 
                                      onsubmit="return confirm('Activer ce coordinateur ?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-acasi success w-100">
                                        <i class="fas fa-play me-2"></i>Activer
                                    </button>
                                </form>
                                @endif
                                
                                <form action="{{ route('esbtp.coordinateurs.destroy', $coordinateur) }}" method="POST" 
                                      onsubmit="return confirm('Supprimer définitivement ce coordinateur ? Cette action est irréversible.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-acasi danger w-100">
                                        <i class="fas fa-trash me-2"></i>Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Informations de contact -->
                        <div class="action-card">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-address-card"></i>
                                </div>
                                Contact
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <a href="mailto:{{ $coordinateur->email }}" class="info-value">
                                    {{ $coordinateur->email }}
                                </a>
                            </div>
                            
                            @if($coordinateur->phone)
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <a href="tel:{{ $coordinateur->phone }}" class="info-value">
                                    {{ $coordinateur->phone }}
                                </a>
                            </div>
                            @endif
                        </div>

                        <!-- Compte utilisateur -->
                        <div class="action-card">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                Compte utilisateur
                            </div>

                            @if(session('new_password'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 1rem;">
                                    <h6 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Mot de passe réinitialisé!</h6>
                                    <hr>
                                    <p class="mb-0"><strong>Nouveau mot de passe:</strong> <code class="text-dark">{{ session('new_password') }}</code></p>
                                    <hr>
                                    <p class="mb-0 small"><i class="fas fa-info-circle me-1"></i>Communiquez ces identifiants au coordinateur.</p>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-success me-2">Actif</span>
                                <span>{{ $coordinateur->email }}</span>
                            </div>
                            <div class="mb-3">
                                <p><strong>Nom d'utilisateur:</strong> {{ $coordinateur->username }}</p>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="showResetPasswordModal()">
                                    <i class="fas fa-key me-1"></i>Réinitialiser le mot de passe
                                </button>
                            </div>
                        </div>

                        <!-- Informations système -->
                        <div class="action-card">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-cog"></i>
                                </div>
                                Informations Système
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">ID</span>
                                <span class="info-value">#{{ $coordinateur->id }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Créé le</span>
                                <span class="info-value">{{ $coordinateur->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Modifié le</span>
                                <span class="info-value">{{ $coordinateur->updated_at->format('d/m/Y') }}</span>
                            </div>
                            @if($coordinateur->created_by)
                            <div class="info-row">
                                <span class="info-label">Créé par</span>
                                <span class="info-value">{{ $coordinateur->createdBy->name ?? 'N/A' }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Réinitialisation Mot de Passe -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 15px 15px 0 0; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold" id="resetPasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Réinitialiser le mot de passe
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="resetPasswordForm" method="POST" action="{{ route('esbtp.coordinateurs.reset-password', ['coordinateur' => $coordinateur->id]) }}">
                @csrf
                <div class="modal-body" style="padding: 2rem;">
                    <!-- Alert warning -->
                    <div style="
                        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                        border-left: 4px solid #f59e0b;
                        border-radius: 10px;
                        padding: 1rem 1.25rem;
                        margin-bottom: 1.5rem;
                    ">
                        <div class="d-flex align-items-start gap-3">
                            <div style="
                                width: 40px;
                                height: 40px;
                                border-radius: 50%;
                                background: linear-gradient(135deg, #f59e0b, #d97706);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                flex-shrink: 0;
                            ">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="color: #92400e; font-weight: 500; margin-bottom: 0.25rem;">Attention</div>
                                <div style="color: #78350f; font-size: 0.9rem;">
                                    Cette action va réinitialiser le mot de passe à <strong>"Bonjour@2025"</strong> pour le coordinateur
                                    <strong>{{ $coordinateur->name }}</strong>. Le coordinateur devra changer son mot de passe à la première connexion.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info coordinateur -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                            <i class="fas fa-user me-1" style="color: #f59e0b;"></i>
                            Coordinateur concerné
                        </label>
                        <div style="
                            background: #f8f9fa;
                            border: 2px solid #dee2e6;
                            border-radius: 8px;
                            padding: 0.75rem;
                            font-weight: 500;
                        ">
                            {{ $coordinateur->name }} ({{ $coordinateur->email }})
                        </div>
                    </div>

                    <!-- Confirmation mot de passe réinitialisé -->
                    <div id="newPasswordDisplay" style="display: none;" class="mb-3">
                        <label class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                            <i class="fas fa-check-circle me-1" style="color: #10b981;"></i>
                            Mot de passe réinitialisé
                        </label>
                        <div style="
                            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                            border: 2px solid #10b981;
                            border-radius: 8px;
                            padding: 1rem;
                            font-family: monospace;
                            font-size: 1.2rem;
                            font-weight: 700;
                            text-align: center;
                            color: #047857;
                            letter-spacing: 2px;
                        " id="newPasswordValue"></div>
                        <div class="form-text text-center mt-2" style="color: #047857;">
                            <i class="fas fa-info-circle me-1"></i>
                            Communiquez ce mot de passe au coordinateur. Il devra le changer à la première connexion.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 15px 15px; padding: 1.25rem 2rem; border: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 500;
                        transition: all 0.2s;
                    ">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-warning" id="resetPasswordBtn" style="
                        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                        border: none;
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 600;
                        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
                        transition: all 0.2s;
                    ">
                        <i class="fas fa-key me-1"></i>Réinitialiser à Bonjour@2025
                    </button>
                    <button type="button" class="btn btn-primary" id="copyPasswordBtn" style="display: none;" onclick="copyPassword()">
                        <i class="fas fa-copy me-1"></i>Copier le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showResetPasswordModal() {
    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}

// Gérer la soumission du formulaire en AJAX
document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('resetPasswordBtn');
    const originalBtnText = submitBtn.innerHTML;

    // Désactiver le bouton et afficher un loader
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Réinitialisation...';

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Afficher le nouveau mot de passe
            document.getElementById('newPasswordValue').textContent = data.password;
            document.getElementById('newPasswordDisplay').style.display = 'block';

            // Cacher le bouton de génération, afficher celui de copie
            submitBtn.style.display = 'none';
            document.getElementById('copyPasswordBtn').style.display = 'inline-block';

            // Notification succès
            showNotification('Mot de passe réinitialisé avec succès !', 'success');
        } else {
            showNotification('Erreur : ' + (data.message || 'Une erreur est survenue'), 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(error => {
        debugError('Erreur:', error);
        showNotification('Erreur de connexion', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});

function copyPassword() {
    const password = document.getElementById('newPasswordValue').textContent;
    navigator.clipboard.writeText(password).then(() => {
        showNotification('Mot de passe copié dans le presse-papiers !', 'success');
    }).catch(err => {
        debugError('Erreur copie:', err);
        showNotification('Erreur lors de la copie', 'danger');
    });
}

function showNotification(message, type) {
    // Créer une notification toast
    const notification = document.createElement('div');
    notification.className = `notification toast-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'danger' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        font-weight: 500;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Animer l'entrée
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Supprimer après 4 secondes
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 4000);
}

// Réinitialiser le modal quand il est fermé
document.getElementById('resetPasswordModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('newPasswordDisplay').style.display = 'none';
    document.getElementById('newPasswordValue').textContent = '';
    document.getElementById('resetPasswordBtn').style.display = 'inline-block';
    document.getElementById('resetPasswordBtn').disabled = false;
    document.getElementById('resetPasswordBtn').innerHTML = '<i class="fas fa-key me-1"></i>Réinitialiser à Bonjour@2025';
    document.getElementById('copyPasswordBtn').style.display = 'none';
});
</script>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Animation d'entrée
    $('.info-section, .action-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        });
        
        setTimeout(() => {
            $(this).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 300);
        }, index * 100);
    });
    
    // Effet hover sur les cartes de statistiques
    $('.stat-card').hover(
        function() {
            $(this).css({
                'transform': 'translateY(-2px)',
                'box-shadow': 'var(--shadow-hover)'
            });
        },
        function() {
            $(this).css({
                'transform': 'translateY(0)',
                'box-shadow': 'none'
            });
        }
    );
});
</script>
@endpush
