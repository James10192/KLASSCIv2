@extends('layouts.app')

@section('title', 'Profil Enseignant - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .teacher-profile {
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
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
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
    
    .bio-text {
        background: var(--background);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        color: var(--text-secondary);
        font-style: italic;
        line-height: 1.6;
        border-left: 4px solid var(--primary);
    }
    
    .timeline-item {
        display: flex;
        gap: var(--space-md);
        padding: var(--space-md) 0;
        border-bottom: 1px solid var(--border);
    }
    
    .timeline-item:last-child {
        border-bottom: none;
    }
    
    .timeline-date {
        flex-shrink: 0;
        width: 80px;
        font-size: 0.8rem;
        color: var(--text-secondary);
        font-weight: 500;
    }
    
    .timeline-content {
        flex: 1;
    }
    
    .timeline-title {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .timeline-desc {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }
    
    .skills-grid {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
    }
    
    .skill-tag {
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        font-size: 0.8rem;
        font-weight: 500;
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
    
    .availability-mini-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
        margin-top: var(--space-sm);
    }
    
    .availability-mini-day {
        text-align: center;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--text-secondary);
        padding: var(--space-xs);
    }
    
    .availability-mini-slot {
        height: 4px;
        background: var(--border);
        border-radius: var(--radius-small);
    }
    
    .availability-mini-slot.available {
        background: var(--success);
    }
    
    .availability-mini-slot.preferred {
        background: var(--primary);
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
        <div class="teacher-profile">
            <!-- En-tête du profil -->
            <div class="profile-header">
                <div class="profile-hero">
                    <div class="profile-avatar">
                        {{ substr($teacher->user->name, 0, 2) }}
                    </div>
                    <div class="profile-info">
                        <h1>{{ $teacher->user->name }}</h1>
                        <p>{{ $teacher->specialization }}</p>
                        <div class="profile-meta">
                            <div class="meta-item">
                                <i class="fas fa-id-card"></i>
                                <span>{{ $teacher->matricule }}</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-building"></i>
                                <span>{{ $teacher->department->name ?? 'Aucun département' }}</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>Depuis {{ $teacher->created_at->format('M Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <a href="{{ route('esbtp.enseignants.edit', $teacher) }}" class="btn-acasi primary">
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
                        <span class="stat-number">{{ $teacher->teaching_hours_due ?? 0 }}</span>
                        <div class="stat-label">Heures dues</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">{{ $teacher->seancesCours->count() ?? 0 }}</span>
                        <div class="stat-label">Séances données</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">{{ $profileData->annees_experience_enseignement ?? 0 }}</span>
                        <div class="stat-label">Années d'expérience</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number status-badge {{ $teacher->status === 'active' ? 'status-active' : 'status-inactive' }}">
                            {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
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
                                <span class="info-value">{{ $teacher->user->name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value">{{ $teacher->user->email }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Téléphone</span>
                                <span class="info-value">{{ $teacher->user->phone ?? 'Non renseigné' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Titre académique</span>
                                <span class="info-value">{{ $profileData->titre_academique ?? 'Non renseigné' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Grade académique</span>
                                <span class="info-value">{{ $profileData->grade_academique ?? 'Non renseigné' }}</span>
                            </div>
                        </div>
                        
                        <!-- Qualifications -->
                        @if($profileData)
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                Qualifications & Formation
                            </div>
                            
                            @if($profileData->diplome_principal)
                            <div class="info-row">
                                <span class="info-label">Diplôme principal</span>
                                <span class="info-value">{{ $profileData->diplome_principal }}</span>
                            </div>
                            @endif
                            
                            @if($profileData->universite_diplome)
                            <div class="info-row">
                                <span class="info-label">Université</span>
                                <span class="info-value">{{ $profileData->universite_diplome }}</span>
                            </div>
                            @endif
                            
                            @if($profileData->annee_diplome)
                            <div class="info-row">
                                <span class="info-label">Année d'obtention</span>
                                <span class="info-value">{{ $profileData->annee_diplome }}</span>
                            </div>
                            @endif
                            
                            @if($profileData->annees_experience_enseignement)
                            <div class="info-row">
                                <span class="info-label">Expérience enseignement</span>
                                <span class="info-value">{{ $profileData->annees_experience_enseignement }} années</span>
                            </div>
                            @endif
                            
                            @if($profileData->annees_experience_professionnelle)
                            <div class="info-row">
                                <span class="info-label">Expérience professionnelle</span>
                                <span class="info-value">{{ $profileData->annees_experience_professionnelle }} années</span>
                            </div>
                            @endif
                        </div>
                        @endif
                        
                        <!-- Informations professionnelles -->
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                Informations Professionnelles
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">Spécialisation</span>
                                <span class="info-value">{{ $teacher->specialization }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Département</span>
                                <span class="info-value">{{ $teacher->department->name ?? 'Non assigné' }}</span>
                            </div>
                            @if($teacher->laboratory)
                            <div class="info-row">
                                <span class="info-label">Laboratoire</span>
                                <span class="info-value">{{ $teacher->laboratory->name }}</span>
                            </div>
                            @endif
                            <div class="info-row">
                                <span class="info-label">Heures d'enseignement</span>
                                <span class="info-value">{{ $teacher->teaching_hours_due ?? 0 }}h/semaine</span>
                            </div>
                            @if($profileData && $profileData->type_contrat)
                            <div class="info-row">
                                <span class="info-label">Type de contrat</span>
                                <span class="info-value">{{ ucfirst($profileData->type_contrat) }}</span>
                            </div>
                            @endif
                            @if($profileData && $profileData->statut_emploi)
                            <div class="info-row">
                                <span class="info-label">Statut d'emploi</span>
                                <span class="info-value">{{ str_replace('_', ' ', ucfirst($profileData->statut_emploi)) }}</span>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Biographie -->
                        @if($teacher->bio)
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                À propos
                            </div>
                            
                            <div class="bio-text">
                                {{ $teacher->bio }}
                            </div>
                        </div>
                        @endif
                        
                        <!-- Motivation et objectifs -->
                        @if($profileData && ($profileData->motivation || $profileData->objectifs_pedagogiques))
                        <div class="info-section">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-lightbulb"></i>
                                </div>
                                Motivation & Objectifs
                            </div>
                            
                            @if($profileData->motivation)
                            <div class="info-row">
                                <span class="info-label">Motivation</span>
                                <span class="info-value">{{ $profileData->motivation }}</span>
                            </div>
                            @endif
                            
                            @if($profileData->objectifs_pedagogiques)
                            <div class="info-row">
                                <span class="info-label">Objectifs pédagogiques</span>
                                <span class="info-value">{{ $profileData->objectifs_pedagogiques }}</span>
                            </div>
                            @endif
                        </div>
                        @endif
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
                                <a href="{{ route('esbtp.enseignants.edit', $teacher) }}" class="btn-acasi primary">
                                    <i class="fas fa-edit me-2"></i>Modifier le profil
                                </a>
                                
                                <a href="{{ route('esbtp.enseignants.matieres', $teacher) }}" class="btn-acasi secondary">
                                    <i class="fas fa-book me-2"></i>Gérer les matières
                                </a>
                                
                                @if($teacher->status === 'active')
                                <form action="{{ route('esbtp.enseignants.toggleStatus', $teacher) }}" method="POST" 
                                      onsubmit="return confirm('Désactiver cet enseignant ?')">
                                    @csrf
                                    <button type="submit" class="btn-acasi warning w-100">
                                        <i class="fas fa-pause me-2"></i>Désactiver
                                    </button>
                                </form>
                                @else
                                <form action="{{ route('esbtp.enseignants.toggleStatus', $teacher) }}" method="POST" 
                                      onsubmit="return confirm('Activer cet enseignant ?')">
                                    @csrf
                                    <button type="submit" class="btn-acasi success w-100">
                                        <i class="fas fa-play me-2"></i>Activer
                                    </button>
                                </form>
                                @endif
                                
                                <form action="{{ route('esbtp.enseignants.destroy', $teacher) }}" method="POST" 
                                      onsubmit="return confirm('Supprimer définitivement cet enseignant ? Cette action est irréversible.')">
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
                                <a href="mailto:{{ $teacher->user->email }}" class="info-value">
                                    {{ $teacher->user->email }}
                                </a>
                            </div>
                            
                            @if($teacher->user->phone)
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <a href="tel:{{ $teacher->user->phone }}" class="info-value">
                                    {{ $teacher->user->phone }}
                                </a>
                            </div>
                            @endif
                            
                            @if($teacher->website)
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <a href="{{ $teacher->website }}" target="_blank" class="info-value">
                                    Site web
                                </a>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Disponibilités (version simplifiée) -->
                        <div class="action-card">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                Disponibilités
                            </div>
                            
                            <div class="availability-mini-grid">
                                <div class="availability-mini-day">L</div>
                                <div class="availability-mini-day">M</div>
                                <div class="availability-mini-day">M</div>
                                <div class="availability-mini-day">J</div>
                                <div class="availability-mini-day">V</div>
                                <div class="availability-mini-day">S</div>
                                <div class="availability-mini-day">D</div>
                                
                                @for($i = 0; $i < 77; $i++)
                                    <div class="availability-mini-slot {{ $i % 3 == 0 ? 'available' : ($i % 7 == 0 ? 'preferred' : '') }}"></div>
                                @endfor
                            </div>
                            
                            <div class="form-help-text" style="margin-top: var(--space-sm); text-align: center;">
                                <i class="fas fa-info-circle me-1"></i>
                                Grille simplifiée des disponibilités
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
                                <span class="info-value">#{{ $teacher->id }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Créé le</span>
                                <span class="info-value">{{ $teacher->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Modifié le</span>
                                <span class="info-value">{{ $teacher->updated_at->format('d/m/Y') }}</span>
                            </div>
                            @if($teacher->createdBy)
                            <div class="info-row">
                                <span class="info-label">Créé par</span>
                                <span class="info-value">{{ $teacher->createdBy->name }}</span>
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