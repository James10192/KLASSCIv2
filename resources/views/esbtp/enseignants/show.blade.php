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
    
    /* Section de disponibilité principale */
    .availability-main-section {
        grid-column: 1 / -1;
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        margin: var(--space-xl) 0;
        border: 1px solid var(--border-light);
        box-shadow: var(--shadow-card);
    }
    
    .availability-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--space-lg);
    }
    
    .availability-title {
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }
    
    .availability-title h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }
    
    .availability-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--accent-blue));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: var(--shadow-medium);
    }
    
    .availability-grid {
        display: grid;
        grid-template-columns: 100px repeat(7, 1fr);
        gap: var(--space-sm);
        background: white;
        padding: var(--space-lg);
        border-radius: var(--radius-medium);
        border: 1px solid var(--border-light);
    }
    
    .availability-time-header {
        grid-column: 1;
        font-weight: 600;
        text-align: center;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .availability-day-header {
        text-align: center;
        font-weight: 700;
        color: var(--primary);
        padding: var(--space-sm);
        background: rgba(var(--primary-rgb), 0.1);
        border-radius: var(--radius-small);
        font-size: 0.9rem;
    }
    
    .availability-time-slot {
        text-align: center;
        padding: var(--space-sm);
        font-size: 0.8rem;
        color: var(--text-secondary);
        border-right: 1px solid var(--border-light);
    }
    
    .availability-slot {
        padding: var(--space-sm);
        text-align: center;
        border-radius: var(--radius-small);
        font-size: 0.8rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .availability-slot.available {
        background: var(--success);
        color: white;
        font-weight: 600;
    }
    
    .availability-slot.preferred {
        background: var(--primary);
        color: white;
        font-weight: 600;
    }
    
    .availability-slot.unavailable {
        background: var(--border);
        color: var(--text-muted);
    }
    
    .availability-legend {
        display: flex;
        justify-content: center;
        gap: var(--space-lg);
        margin-top: var(--space-lg);
        flex-wrap: wrap;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.9rem;
    }
    
    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: var(--radius-small);
    }
    
    /* Styles pour les boutons d'édition de disponibilité */
    .availability-actions {
        display: flex;
        gap: var(--space-sm);
        align-items: center;
    }
    
    .btn-edit-availability {
        background: var(--primary);
        color: white;
        border: none;
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .btn-edit-availability:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.3);
    }
    
    .btn-save-availability {
        background: var(--success);
        color: white;
        border: none;
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .btn-save-availability:hover {
        background: var(--success-dark);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(var(--success-rgb), 0.3);
    }
    
    .btn-cancel-availability {
        background: var(--danger);
        color: white;
        border: none;
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .btn-cancel-availability:hover {
        background: var(--danger-dark);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(var(--danger-rgb), 0.3);
    }
    
    /* Mode édition pour les créneaux */
    .availability-slot.editable {
        cursor: pointer;
        border: 2px solid transparent;
        position: relative;
    }
    
    .availability-slot.editable:hover {
        border-color: var(--primary);
        transform: scale(1.05);
    }
    
    .availability-slot.editable.available:hover {
        border-color: white;
        box-shadow: 0 0 0 2px var(--success);
    }
    
    .availability-slot.editable.preferred:hover {
        border-color: white;
        box-shadow: 0 0 0 2px var(--primary);
    }
    
    .availability-slot.editable.unavailable:hover {
        border-color: var(--primary);
        box-shadow: 0 0 0 2px var(--primary);
    }
    
    /* Indicateur de modification */
    .availability-slot.modified::after {
        content: '●';
        position: absolute;
        top: 2px;
        right: 2px;
        color: var(--warning);
        font-size: 0.6rem;
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
                        {{ $teacher->user ? substr($teacher->user->name, 0, 2) : 'NN' }}
                    </div>
                    <div class="profile-info">
                        <h1>{{ $teacher->user->name ?? 'Nom non disponible' }}</h1>
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
                                <span>Depuis {{ $teacher->created_at ? $teacher->created_at->format('M Y') : 'Non disponible' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <a href="{{ route('esbtp.enseignants.edit', ['enseignant' => $teacher->id]) }}" class="btn-acasi primary">
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
                        <span class="stat-number">{{ (int)($teacher->teaching_hours_due ?? 0) }}</span>
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
                                <span class="info-value">{{ $teacher->user->name ?? 'Non disponible' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value">{{ $teacher->user->email ?? 'Non disponible' }}</span>
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
                                <span class="info-value">{{ $teacher->laboratory->name ?? 'Non disponible' }}</span>
                            </div>
                            @endif
                            <div class="info-row">
                                <span class="info-label">Heures d'enseignement</span>
                                <span class="info-value">{{ (int)($teacher->teaching_hours_due ?? 0) }}h/semaine</span>
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
                                <a href="{{ route('esbtp.enseignants.edit', ['enseignant' => $teacher->id]) }}" class="btn-acasi primary">
                                    <i class="fas fa-edit me-2"></i>Modifier le profil
                                </a>
                                
                                <a href="{{ route('esbtp.enseignants.matieres', ['teacher' => $teacher]) }}" class="btn-acasi secondary">
                                    <i class="fas fa-book me-2"></i>Gérer les matières
                                </a>
                                
                                @if($teacher->status === 'active')
                                <form action="{{ route('esbtp.enseignants.toggleStatus', ['teacher' => $teacher]) }}" method="POST" 
                                      onsubmit="return confirm('Désactiver cet enseignant ?')">
                                    @csrf
                                    <button type="submit" class="btn-acasi warning w-100">
                                        <i class="fas fa-pause me-2"></i>Désactiver
                                    </button>
                                </form>
                                @else
                                <form action="{{ route('esbtp.enseignants.toggleStatus', ['teacher' => $teacher]) }}" method="POST" 
                                      onsubmit="return confirm('Activer cet enseignant ?')">
                                    @csrf
                                    <button type="submit" class="btn-acasi success w-100">
                                        <i class="fas fa-play me-2"></i>Activer
                                    </button>
                                </form>
                                @endif
                                
                                <form action="{{ route('esbtp.enseignants.destroy', ['enseignant' => $teacher]) }}" method="POST" 
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
                                <a href="mailto:{{ $teacher->user->email ?? '#' }}" class="info-value">
                                    {{ $teacher->user->email ?? 'Non disponible' }}
                                </a>
                            </div>
                            
                            @if($teacher->user && $teacher->user->phone)
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
                    </div>
                    
                    <!-- Section Disponibilités Principale -->
                    <div class="availability-main-section">
                        <div class="availability-header">
                            <div class="availability-title">
                                <div class="availability-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h3>Disponibilités Hebdomadaires</h3>
                            </div>
                            <div class="availability-status">
                                <span class="status-badge {{ $teacher->status === 'active' ? 'success' : 'warning' }}">
                                    {{ $teacher->status === 'active' ? 'Disponible' : 'Non disponible' }}
                                </span>
                                <div class="availability-actions">
                                    <button id="editAvailabilityBtn" class="btn-edit-availability" onclick="toggleEditMode()">
                                        <i class="fas fa-edit me-1"></i>
                                        <span class="edit-text">Modifier</span>
                                    </button>
                                    <button id="saveAvailabilityBtn" class="btn-save-availability" onclick="saveAvailability()" style="display: none;">
                                        <i class="fas fa-save me-1"></i>
                                        Sauvegarder
                                    </button>
                                    <button id="cancelAvailabilityBtn" class="btn-cancel-availability" onclick="cancelEditMode()" style="display: none;">
                                        <i class="fas fa-times me-1"></i>
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="availability-grid">
                            <!-- En-têtes -->
                            <div class="availability-time-header">Horaires</div>
                            <div class="availability-day-header">Lundi</div>
                            <div class="availability-day-header">Mardi</div>
                            <div class="availability-day-header">Mercredi</div>
                            <div class="availability-day-header">Jeudi</div>
                            <div class="availability-day-header">Vendredi</div>
                            <div class="availability-day-header">Samedi</div>
                            <div class="availability-day-header">Dimanche</div>
                            
                            <!-- Créneaux horaires -->
                            @php
                                // Créneaux horaires par heure pour cohérence avec la page edit
                                $hours = range(8, 18); // 08:00 à 18:00
                                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                
                                // Utiliser les vraies données de disponibilité préparées par le contrôleur
                                $availability = $realAvailability ?? [
                                    'monday' => array_fill(0, 11, 'unavailable'),    // 8h à 18h = 11 heures
                                    'tuesday' => array_fill(0, 11, 'unavailable'),
                                    'wednesday' => array_fill(0, 11, 'unavailable'),
                                    'thursday' => array_fill(0, 11, 'unavailable'),
                                    'friday' => array_fill(0, 11, 'unavailable'),
                                    'saturday' => array_fill(0, 11, 'unavailable'),
                                    'sunday' => array_fill(0, 11, 'unavailable')
                                ];
                            @endphp
                            
                            @foreach($hours as $index => $hour)
                                <div class="availability-time-slot">{{ sprintf('%02d:00', $hour) }}</div>
                                @foreach($days as $day)
                                    @php
                                        $status = $availability[$day][$index] ?? 'unavailable';
                                        $icon = $status === 'preferred' ? '★' : ($status === 'available' ? '✓' : '✗');
                                    @endphp
                                    <div class="availability-slot {{ $status }}" 
                                         id="slot-{{ $index }}-{{ array_search($day, $days) }}"
                                         data-day="{{ array_search($day, $days) }}" 
                                         data-hour="{{ $hour }}" 
                                         data-time-index="{{ $index }}" 
                                         data-original-status="{{ $status }}"
                                         title="{{ ucfirst($day) }} {{ sprintf('%02d:00', $hour) }} - {{ ucfirst($status) }}">
                                        {{ $icon }}
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                        
                        <!-- DEBUG VISIBLE PAGE SHOW -->
                        <div style="background: #fff3cd; padding: 10px; margin: 10px 0; border: 2px solid #ffc107; border-radius: 5px;">
                            <h4>🔍 DEBUG PAGE SHOW - Données de disponibilité</h4>
                            <p><strong>Timestamp:</strong> {{ date('Y-m-d H:i:s') }}</p>
                            <p><strong>$realAvailability:</strong> {{ $realAvailability ? 'EXISTE' : 'NULL' }}</p>
                            <p><strong>Données depuis teacher->availabilities:</strong> {{ $teacher->availabilities ? $teacher->availabilities->count() : '0' }} éléments</p>
                            @if($teacher->availabilities && $teacher->availabilities->count() > 0)
                                <details>
                                    <summary>Voir les données brutes ({{ $teacher->availabilities->count() }} entrées)</summary>
                                    <pre style="background: white; padding: 5px; overflow-x: auto;">{{ json_encode($teacher->availabilities->toArray(), JSON_PRETTY_PRINT) }}</pre>
                                </details>
                            @endif
                            <details>
                                <summary>Voir les données finales utilisées pour l'affichage</summary>
                                <pre style="background: white; padding: 5px; overflow-x: auto;">{{ json_encode($availability, JSON_PRETTY_PRINT) }}</pre>
                            </details>
                            
                            <script>
                            // DEBUG JavaScript sur page SHOW
                            document.addEventListener('DOMContentLoaded', function() {
                                console.log('🔍 DEBUG PAGE SHOW CHARGÉE à {{ date('H:i:s') }}');
                                
                                @if($teacher->availabilities && $teacher->availabilities->count() > 0)
                                let availCount = {{ $teacher->availabilities->count() }};
                                let debugShowInfo = `🔍 PAGE SHOW CHARGÉE\n\n`;
                                debugShowInfo += `Heure: {{ date('H:i:s') }}\n`;
                                debugShowInfo += `Disponibilités en DB: ${availCount} entrées\n`;
                                
                                // Compter les créneaux par statut dans les données finales
                                let finalData = @json($availability);
                                let countByStatus = {available: 0, preferred: 0, unavailable: 0};
                                Object.keys(finalData).forEach(day => {
                                    finalData[day].forEach(status => {
                                        countByStatus[status]++;
                                    });
                                });
                                
                                debugShowInfo += `Créneaux finaux:\n`;
                                debugShowInfo += `- Disponible: ${countByStatus.available}\n`;
                                debugShowInfo += `- Préféré: ${countByStatus.preferred}\n`;
                                debugShowInfo += `- Indisponible: ${countByStatus.unavailable}`;
                                
                                // Afficher après 1 seconde pour laisser la page se charger
                                setTimeout(() => {
                                    if(confirm(debugShowInfo + '\n\nVoulez-vous voir les détails dans la console ?')) {
                                        console.log('🔍 Données brutes DB:', @json($teacher->availabilities->toArray()));
                                        console.log('🔍 Données finales:', finalData);
                                    }
                                }, 1000);
                                @endif
                            });
                            </script>
                        </div>
                        
                        <!-- Légende -->
                        <div class="availability-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background: var(--primary);"></div>
                                <span>★ Créneaux préférés</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: var(--success);"></div>
                                <span>✓ Disponible</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: var(--border);"></div>
                                <span>✗ Non disponible</span>
                            </div>
                        </div>
                        
                        <!-- JavaScript pour l'édition de disponibilités -->
                        <script>
                        let isEditMode = false;
                        let originalData = {};
                        let modifiedSlots = new Set();
                        
                        function toggleEditMode() {
                            isEditMode = !isEditMode;
                            const slots = document.querySelectorAll('.availability-slot');
                            const editBtn = document.getElementById('editAvailabilityBtn');
                            const saveBtn = document.getElementById('saveAvailabilityBtn');
                            const cancelBtn = document.getElementById('cancelAvailabilityBtn');
                            
                            if (isEditMode) {
                                // Activer le mode édition
                                slots.forEach(slot => {
                                    slot.classList.add('editable');
                                    slot.onclick = () => toggleSlotStatus(slot);
                                    // Sauvegarder l'état original
                                    originalData[slot.id] = slot.dataset.originalStatus;
                                });
                                
                                editBtn.style.display = 'none';
                                saveBtn.style.display = 'flex';
                                cancelBtn.style.display = 'flex';
                                
                                // Changer le style du header pour indiquer le mode édition
                                document.querySelector('.availability-main-section').style.background = 'linear-gradient(135deg, #fef3c7, #fde68a)';
                                
                                // Afficher un message d'aide
                                showNotification('Mode édition activé. Cliquez sur les créneaux pour modifier la disponibilité.', 'info');
                            } else {
                                // Désactiver le mode édition
                                slots.forEach(slot => {
                                    slot.classList.remove('editable');
                                    slot.onclick = null;
                                });
                                
                                editBtn.style.display = 'flex';
                                saveBtn.style.display = 'none';
                                cancelBtn.style.display = 'none';
                                
                                document.querySelector('.availability-main-section').style.background = 'linear-gradient(135deg, #f8fafc, #e2e8f0)';
                            }
                        }
                        
                        function toggleSlotStatus(slot) {
                            if (!isEditMode) return;
                            
                            const statuses = ['unavailable', 'available', 'preferred'];
                            const icons = ['✗', '✓', '★'];
                            const currentClasses = Array.from(slot.classList);
                            let currentStatus = statuses.find(status => currentClasses.includes(status)) || 'unavailable';
                            
                            // Passer au statut suivant
                            const currentIndex = statuses.indexOf(currentStatus);
                            const nextIndex = (currentIndex + 1) % statuses.length;
                            const nextStatus = statuses[nextIndex];
                            
                            // Supprimer l'ancienne classe de statut
                            statuses.forEach(status => slot.classList.remove(status));
                            
                            // Ajouter la nouvelle classe
                            slot.classList.add(nextStatus);
                            
                            // Changer l'icône
                            slot.textContent = icons[nextIndex];
                            
                            // Marquer comme modifié si différent de l'original
                            if (nextStatus !== originalData[slot.id]) {
                                slot.classList.add('modified');
                                modifiedSlots.add(slot.id);
                            } else {
                                slot.classList.remove('modified');
                                modifiedSlots.delete(slot.id);
                            }
                            
                            // Mettre à jour le tooltip
                            const dayNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                            const hours = Array.from({length: 11}, (_, i) => String(i + 8).padStart(2, '0') + ':00'); // 08:00 à 18:00
                            const statusNames = { unavailable: 'Non disponible', available: 'Disponible', preferred: 'Préféré' };
                            
                            const dayIndex = parseInt(slot.dataset.day);
                            const timeIndex = parseInt(slot.dataset.timeIndex);
                            slot.title = `${dayNames[dayIndex]} ${hours[timeIndex]} - ${statusNames[nextStatus]}`;
                        }
                        
                        function cancelEditMode() {
                            // Restaurer les états originaux
                            modifiedSlots.forEach(slotId => {
                                const slot = document.getElementById(slotId);
                                const originalStatus = originalData[slotId];
                                const statuses = ['unavailable', 'available', 'preferred'];
                                const icons = ['✗', '✓', '★'];
                                
                                // Supprimer toutes les classes de statut
                                statuses.forEach(status => slot.classList.remove(status));
                                
                                // Restaurer le statut original
                                slot.classList.add(originalStatus);
                                slot.textContent = icons[statuses.indexOf(originalStatus)];
                                slot.classList.remove('modified');
                            });
                            
                            modifiedSlots.clear();
                            toggleEditMode();
                            showNotification('Modifications annulées', 'warning');
                        }
                        
                        function saveAvailability() {
                            if (modifiedSlots.size === 0) {
                                showNotification('Aucune modification à sauvegarder', 'warning');
                                return;
                            }
                            
                            // Préparer les données à envoyer
                            const changedSlots = [];
                            modifiedSlots.forEach(slotId => {
                                const slot = document.getElementById(slotId);
                                const statuses = ['unavailable', 'available', 'preferred'];
                                const currentStatus = statuses.find(status => slot.classList.contains(status));
                                
                                // Calculer les heures réelles à partir du timeIndex
                                const timeIndex = parseInt(slot.dataset.timeIndex);
                                const startHour = 8 + timeIndex; // timeIndex 0 = 8h, timeIndex 1 = 9h, etc.
                                const endHour = startHour + 1;   // Créneau d'1 heure
                                
                                changedSlots.push({
                                    day: parseInt(slot.dataset.day),
                                    startTime: String(startHour).padStart(2, '0') + ':00',
                                    endTime: String(endHour).padStart(2, '0') + ':00',
                                    status: currentStatus
                                });
                            });
                            
                            // Envoyer les données via AJAX
                            const teacherId = {{ $teacher->id }};
                            console.log('Données à envoyer:', { changes: changedSlots });
                            
                            fetch(`/esbtp/enseignants/${teacherId}/update-availability`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ changes: changedSlots })
                            })
                            .then(response => {
                                console.log('Réponse reçue:', response);
                                return response.json();
                            })
                            .then(data => {
                                console.log('Données reçues:', data);
                                if (data.success) {
                                    showNotification('Disponibilités mises à jour avec succès !', 'success');
                                    
                                    // Mettre à jour les données originales
                                    modifiedSlots.forEach(slotId => {
                                        const slot = document.getElementById(slotId);
                                        const statuses = ['unavailable', 'available', 'preferred'];
                                        const currentStatus = statuses.find(status => slot.classList.contains(status));
                                        originalData[slotId] = currentStatus;
                                        slot.dataset.originalStatus = currentStatus;
                                        slot.classList.remove('modified');
                                    });
                                    
                                    modifiedSlots.clear();
                                    toggleEditMode();
                                } else {
                                    showNotification('Erreur lors de la sauvegarde : ' + (data.message || 'Erreur inconnue'), 'danger');
                                }
                            })
                            .catch(error => {
                                console.error('Erreur complète:', error);
                                showNotification('Erreur de connexion lors de la sauvegarde: ' + error.message, 'danger');
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
                        </script>
                    </div>
                    
                    <div class="info-grid">
                        
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
                                <span class="info-value">{{ $teacher->created_at ? $teacher->created_at->format('d/m/Y') : 'Non disponible' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Modifié le</span>
                                <span class="info-value">{{ $teacher->updated_at ? $teacher->updated_at->format('d/m/Y') : 'Non disponible' }}</span>
                            </div>
                            @if($teacher->createdBy)
                            <div class="info-row">
                                <span class="info-label">Créé par</span>
                                <span class="info-value">{{ $teacher->createdBy->name ?? 'Non disponible' }}</span>
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