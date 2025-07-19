@extends('layouts.app')

@section('title', 'Profil Coordinateur - ESBTP-yAKRO')

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
        font-weight: 600;
    }
    
    .profile-info p {
        margin: 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .profile-meta {
        display: flex;
        gap: var(--space-lg);
        margin-top: var(--space-md);
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.9rem;
    }
    
    .profile-actions {
        position: absolute;
        top: var(--space-lg);
        right: var(--space-lg);
        display: flex;
        gap: var(--space-sm);
        z-index: 3;
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
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }
    
    .stat-card {
        background: var(--background);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        text-align: center;
        border: 1px solid var(--border);
    }
    
    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        display: block;
    }
    
    .stat-label {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-top: var(--space-xs);
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
            position: static;
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
                    <a href="{{ route('esbtp.coordinateurs.index') }}" class="btn-acasi secondary">
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