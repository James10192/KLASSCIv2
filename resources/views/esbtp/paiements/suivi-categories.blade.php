@extends('layouts.app')

@section('title', 'Suivi des Paiements par Catégorie - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .stats-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .stat-card {
        text-align: center;
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, var(--surface) 0%, rgba(255, 255, 255, 0.9) 100%);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .stat-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }
    
    .stat-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at 50% 0%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        pointer-events: none;
    }
    
    .stat-card.success::before { background: linear-gradient(90deg, var(--success), #34d399); }
    .stat-card.warning::before { background: linear-gradient(90deg, var(--warning), #fbbf24); }
    .stat-card.danger::before { background: linear-gradient(90deg, var(--danger), #f87171); }
    .stat-card.primary::before { background: linear-gradient(90deg, var(--primary), #60a5fa); }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-sm);
        font-size: 20px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .stat-card.success .stat-icon { color: var(--success); }
    .stat-card.warning .stat-icon { color: var(--warning); }
    .stat-card.danger .stat-icon { color: var(--danger); }
    .stat-card.primary .stat-icon { color: var(--primary); }
    
    .stat-value {
        font-size: var(--amount-large);
        font-weight: 800;
        margin-bottom: var(--space-xs);
        background: linear-gradient(135deg, var(--text-primary), #6b7280);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }
    
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: var(--space-xl);
        margin-bottom: var(--space-xl);
    }
    
    .category-card {
        position: relative;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        background: linear-gradient(135deg, var(--surface) 0%, rgba(255, 255, 255, 0.95) 100%);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    
    .category-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, transparent 0%, rgba(99, 102, 241, 0.02) 100%);
        opacity: 0;
        transition: opacity 0.4s ease;
    }
    
    .category-card:hover::before {
        opacity: 1;
    }
    
    .category-card:hover {
        transform: translateY(-6px) rotate(0.5deg);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        border-color: rgba(99, 102, 241, 0.2);
    }
    
    .category-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--space-lg);
        position: relative;
        z-index: 2;
    }
    
    .category-icon {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-medium);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        position: relative;
    }
    
    .category-icon::after {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: var(--radius-medium);
        z-index: -1;
        opacity: 0.3;
        filter: blur(8px);
    }
    
    .category-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
        position: relative;
        z-index: 2;
    }
    
    .mini-stat {
        text-align: center;
        padding: var(--space-md);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.4));
        border-radius: var(--radius-medium);
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }
    
    .mini-stat:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.6));
    }
    
    .mini-stat-value {
        font-size: var(--amount-medium);
        font-weight: 800;
        display: block;
        margin-bottom: var(--space-xs);
    }
    
    .mini-stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-top: var(--space-xs);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Barre de progression moderne avec animations */
    .progress-bar-modern {
        height: 12px;
        background: linear-gradient(90deg, rgba(0, 0, 0, 0.05) 0%, rgba(0, 0, 0, 0.08) 100%);
        border-radius: 8px;
        overflow: hidden;
        margin: var(--space-lg) 0;
        position: relative;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .progress-bar-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 50%;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.3) 0%, transparent 100%);
        border-radius: 8px 8px 0 0;
    }
    
    .progress-fill-modern {
        height: 100%;
        border-radius: 8px;
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .progress-fill-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 50%;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.4) 0%, transparent 100%);
        border-radius: 8px 8px 0 0;
    }
    
    .progress-fill-modern::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.4) 50%, transparent 100%);
        animation: shimmer 2s infinite;
    }
    
    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .progress-fill-modern.success { 
        background: linear-gradient(135deg, #10b981, #34d399, #6ee7b7);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    
    .progress-fill-modern.warning { 
        background: linear-gradient(135deg, #f59e0b, #fbbf24, #fcd34d);
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
    }
    
    .progress-fill-modern.danger { 
        background: linear-gradient(135deg, #ef4444, #f87171, #fca5a5);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }
    
    /* Barre de progression segmentée pour la vue d'ensemble */
    .segmented-progress {
        height: 16px;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 10px;
        overflow: hidden;
        display: flex;
        position: relative;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .progress-segment {
        height: 100%;
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .progress-segment::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 50%;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.4) 0%, transparent 100%);
    }
    
    .progress-segment.success {
        background: linear-gradient(135deg, #10b981, #34d399);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }
    
    .progress-segment.warning {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }
    
    .progress-segment.danger {
        background: linear-gradient(135deg, #ef4444, #f87171);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }
    
    .filter-bar {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
    }
    
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        align-items: end;
    }
    
    .form-group {
        position: relative;
    }
    
    .form-group label {
        display: block;
        font-size: var(--text-small);
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: var(--space-xs);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-group select {
        width: 100%;
        padding: var(--space-sm) var(--space-md);
        border: 2px solid #e5e7eb;
        border-radius: var(--radius-small);
        background: var(--surface);
        font-size: var(--text-normal);
        transition: all 0.2s ease;
    }
    
    .form-group select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }
    
    .details-section {
        margin-top: var(--space-xl);
    }
    
    .students-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--space-md);
    }
    
    .student-card {
        padding: var(--space-md);
        border-left: 4px solid transparent;
        transition: all 0.2s ease;
    }
    
    .student-card.success { border-left-color: var(--success); background: rgba(16, 185, 129, 0.05); }
    .student-card.warning { border-left-color: var(--warning); background: rgba(245, 158, 11, 0.05); }
    .student-card.danger { border-left-color: var(--danger); background: rgba(239, 68, 68, 0.05); }
    
    .student-info {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        margin-bottom: var(--space-sm);
    }
    
    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-circle);
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }
    
    .student-details h6 {
        font-weight: 600;
        margin: 0 0 var(--space-xs) 0;
        color: var(--text-primary);
    }
    
    .student-details p {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin: 0;
    }
    
    .payment-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: var(--text-small);
    }
    
    .amount-info {
        text-align: right;
    }
    
    .amount-paid {
        font-weight: 600;
        color: var(--success);
    }
    
    .amount-due {
        color: var(--text-secondary);
    }
    
    .percentage-badge {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: var(--text-small);
        font-weight: 600;
    }
    
    .percentage-badge.success { 
        background: rgba(16, 185, 129, 0.1); 
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1);
    }
    .percentage-badge.warning { 
        background: rgba(245, 158, 11, 0.1); 
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
        box-shadow: 0 2px 4px rgba(245, 158, 11, 0.1);
    }
    .percentage-badge.danger { 
        background: rgba(239, 68, 68, 0.1); 
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1);
    }
    
    /* Animation pulse pour les stats importantes */
    .stat-card.danger .stat-value {
        animation: pulse-danger 2s infinite;
    }
    
    @keyframes pulse-danger {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    /* Hover effects améliorés pour les mini-stats */
    .mini-stat:hover {
        transform: translateY(-3px) scale(1.05);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.8));
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    /* Effect de brillance sur les icônes */
    .category-icon:hover::after {
        animation: glow-pulse 1.5s ease-in-out infinite alternate;
    }
    
    @keyframes glow-pulse {
        from { opacity: 0.3; filter: blur(8px); }
        to { opacity: 0.6; filter: blur(12px); }
    }
    
    /* Amélioration des student cards avec gradients */
    .student-card {
        position: relative;
        overflow: hidden;
    }
    
    .student-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.1) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .student-card:hover::before {
        opacity: 1;
    }
    
    .student-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Suivi des Paiements par Catégorie</h1>
                <p class="header-subtitle">Vue d'ensemble des frais académiques et de leur recouvrement</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.paiements.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-list"></i>Liste des paiements
                </a>
                <a href="{{ route('esbtp.paiements.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus"></i>Nouveau paiement
                </a>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-bar">
            <form method="GET" action="{{ route('esbtp.paiements.suivi-categories') }}">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="annee_id">Année universitaire</label>
                        <select name="annee_id" id="annee_id" onchange="this.form.submit()">
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                                    {{ $annee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="filiere_id">Filière</label>
                        <select name="filiere_id" id="filiere_id" onchange="this.form.submit()">
                            <option value="">Toutes les filières</option>
                            @foreach($filieres as $filiere)
                                <option value="{{ $filiere->id }}" {{ $filiereId == $filiere->id ? 'selected' : '' }}>
                                    {{ $filiere->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="niveau_id">Niveau d'étude</label>
                        <select name="niveau_id" id="niveau_id" onchange="this.form.submit()">
                            <option value="">Tous les niveaux</option>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" {{ $niveauId == $niveau->id ? 'selected' : '' }}>
                                    {{ $niveau->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Catégorie détaillée</label>
                        <select name="category_id" id="category_id" onchange="this.form.submit()">
                            <option value="">Vue d'ensemble</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <!-- Vue d'ensemble globale -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-lg">
                    <i class="fas fa-chart-pie me-2"></i>
                    Vue d'Ensemble Globale
                </div>
                
                <div class="stats-overview">
                    <div class="card-moderne stat-card success">
                        <div class="p-lg">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-value">{{ $vueEnsemble['etudiants_en_regle'] }}</div>
                            <div class="stat-label">Étudiants en règle</div>
                        </div>
                    </div>
                    
                    <div class="card-moderne stat-card warning">
                        <div class="p-lg">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-value">{{ $vueEnsemble['etudiants_en_retard'] }}</div>
                            <div class="stat-label">Paiements partiels</div>
                        </div>
                    </div>
                    
                    <div class="card-moderne stat-card danger">
                        <div class="p-lg">
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-value">{{ $vueEnsemble['etudiants_non_payes'] }}</div>
                            <div class="stat-label">Impayés (souscrits)</div>
                        </div>
                    </div>
                    
                    <div class="card-moderne stat-card primary">
                        <div class="p-lg">
                            <div class="stat-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-value">{{ $vueEnsemble['taux_recouvrement_global'] }}%</div>
                            <div class="stat-label">Taux de recouvrement</div>
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-xl); align-items: center;">
                    <div>
                        <h6 class="font-semibold mb-sm">Répartition des étudiants concernés</h6>
                        @php
                            $totalConcernes = $vueEnsemble['etudiants_en_regle'] + $vueEnsemble['etudiants_en_retard'] + $vueEnsemble['etudiants_non_payes'];
                            $enReglePercent = $totalConcernes > 0 ? ($vueEnsemble['etudiants_en_regle'] / $totalConcernes) * 100 : 0;
                            $enRetardPercent = $totalConcernes > 0 ? ($vueEnsemble['etudiants_en_retard'] / $totalConcernes) * 100 : 0;
                            $nonPayesPercent = 100 - $enReglePercent - $enRetardPercent;
                        @endphp
                        
                        <!-- Barre de progression segmentée -->
                        <div class="segmented-progress">
                            <div class="progress-segment success" style="width: {{ $enReglePercent }}%;"></div>
                            <div class="progress-segment warning" style="width: {{ $enRetardPercent }}%;"></div>
                            <div class="progress-segment danger" style="width: {{ $nonPayesPercent }}%;"></div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; font-size: var(--text-small); margin-top: var(--space-xs);">
                            <span class="color-success">{{ round($enReglePercent, 1) }}% en règle</span>
                            <span class="color-warning">{{ round($enRetardPercent, 1) }}% partiels</span>
                            <span class="color-danger">{{ round($nonPayesPercent, 1) }}% impayés</span>
                        </div>
                        
                        <!-- Barre de progression traditionnelle pour le taux global -->
                        <div style="margin-top: var(--space-md);">
                            <h6 class="font-semibold mb-sm">Taux de recouvrement global</h6>
                            <div class="progress-bar-modern">
                                <div class="progress-fill-modern {{ $vueEnsemble['taux_recouvrement_global'] >= 80 ? 'success' : ($vueEnsemble['taux_recouvrement_global'] >= 50 ? 'warning' : 'danger') }}" 
                                     style="width: {{ $vueEnsemble['taux_recouvrement_global'] }}%;"></div>
                            </div>
                            <div style="text-align: center; font-size: var(--text-small); margin-top: var(--space-xs);">
                                <span class="font-semibold">{{ $vueEnsemble['taux_recouvrement_global'] }}%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h6 class="font-semibold mb-sm">Montants financiers</h6>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                            <div class="text-center">
                                <div class="color-success font-bold" style="font-size: var(--amount-medium);">{{ number_format($vueEnsemble['montant_total_recu']) }}</div>
                                <div class="text-small color-secondary">FCFA reçus</div>
                            </div>
                            <div class="text-center">
                                <div class="color-secondary font-bold" style="font-size: var(--amount-medium);">{{ number_format($vueEnsemble['montant_total_attendu']) }}</div>
                                <div class="text-small color-secondary">FCFA attendus</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques par catégorie -->
        @if(!$categoryId)
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-lg">
                    <i class="fas fa-tags me-2"></i>
                    Suivi par Catégorie de Frais
                </div>
                
                <div class="categories-grid">
                    @foreach($statistiquesCategories as $stats)
                    @php
                        $category = $stats['category'];
                        $progressClass = $stats['taux_recouvrement'] >= 80 ? 'success' : ($stats['taux_recouvrement'] >= 50 ? 'warning' : 'danger');
                    @endphp
                    <div class="card-moderne category-card" onclick="window.location.href='{{ route('esbtp.paiements.suivi-categories') }}?{{ http_build_query(array_merge(request()->query(), ['category_id' => $category->id])) }}'">
                        <div class="p-lg">
                            <div class="category-header">
                                <div>
                                    <div class="category-icon bg-primary">
                                        <i class="{{ $category->icon ?? 'fas fa-money-bill' }}"></i>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold color-primary" style="font-size: var(--amount-medium);">{{ $stats['taux_recouvrement'] }}%</div>
                                    <div class="text-small color-secondary">Recouvrement</div>
                                </div>
                            </div>
                            
                            <h5 class="font-semibold mb-sm">{{ $category->name }}</h5>
                            
                            <div class="category-stats">
                                <div class="mini-stat">
                                    <span class="mini-stat-value color-success">{{ $stats['etudiants_a_jour'] }}</span>
                                    <div class="mini-stat-label">À jour</div>
                                </div>
                                <div class="mini-stat">
                                    <span class="mini-stat-value color-warning">{{ $stats['etudiants_en_retard'] }}</span>
                                    <div class="mini-stat-label">Partiels</div>
                                </div>
                                <div class="mini-stat">
                                    <span class="mini-stat-value color-danger">{{ $stats['etudiants_non_payes'] }}</span>
                                    <div class="mini-stat-label">{{ $category->is_mandatory ? 'Impayés' : 'Souscrits impayés' }}</div>
                                </div>
                            </div>
                            
                            <!-- Indicateur du type de frais -->
                            <div style="margin-bottom: var(--space-sm);">
                                @if($category->is_mandatory)
                                    <span class="badge bg-primary">
                                        <i class="fas fa-star me-1"></i>Frais obligatoire
                                    </span>
                                    <small class="text-muted d-block mt-1">{{ $stats['total_etudiants'] }} étudiants concernés</small>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-plus-circle me-1"></i>Service optionnel
                                    </span>
                                    <small class="text-muted d-block mt-1">{{ $stats['etudiants_concernes'] }} souscriptions sur {{ $stats['total_etudiants'] }} étudiants</small>
                                @endif
                            </div>
                            
                            <div class="progress-bar-modern">
                                <div class="progress-fill-modern {{ $progressClass }}" style="width: {{ $stats['taux_recouvrement'] }}%;"></div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; font-size: var(--text-small);">
                                <span class="color-success font-medium">{{ number_format($stats['montant_total_recu']) }} FCFA</span>
                                <span class="color-secondary">/ {{ number_format($stats['montant_total_attendu']) }} FCFA</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Détails d'une catégorie spécifique -->
        @if($detailsCategorie)
        <div class="details-section">
            <div class="card-moderne mb-lg">
                <div class="p-lg">
                    <div class="section-title mb-lg">
                        <i class="{{ $detailsCategorie['category']->icon ?? 'fas fa-money-bill' }} me-2"></i>
                        Détails : {{ $detailsCategorie['category']->name }}
                    </div>
                    
                    <!-- Statistiques de la catégorie -->
                    <div class="stats-overview">
                        <div class="card-moderne stat-card success">
                            <div class="p-lg">
                                <div class="stat-value color-success">{{ $detailsCategorie['etudiants_a_jour']->count() }}</div>
                                <div class="stat-label">Étudiants à jour</div>
                            </div>
                        </div>
                        <div class="card-moderne stat-card warning">
                            <div class="p-lg">
                                <div class="stat-value color-warning">{{ $detailsCategorie['etudiants_en_retard']->count() }}</div>
                                <div class="stat-label">Paiements partiels</div>
                            </div>
                        </div>
                        <div class="card-moderne stat-card danger">
                            <div class="p-lg">
                                <div class="stat-value color-danger">{{ $detailsCategorie['etudiants_non_payes']->count() }}</div>
                                <div class="stat-label">Aucun paiement</div>
                            </div>
                        </div>
                        <div class="card-moderne stat-card primary">
                            <div class="p-lg">
                                <div class="stat-value color-primary">
                                    {{ $detailsCategorie['montant_total_attendu'] > 0 ? round(($detailsCategorie['montant_total_recu'] / $detailsCategorie['montant_total_attendu']) * 100, 1) : 0 }}%
                                </div>
                                <div class="stat-label">Taux de recouvrement</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Listes des étudiants par statut -->
                    @if($detailsCategorie['etudiants_non_payes']->count() > 0)
                    <div class="mb-xl">
                        <h6 class="font-semibold color-danger mb-md">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Étudiants sans aucun paiement ({{ $detailsCategorie['etudiants_non_payes']->count() }})
                        </h6>
                        <div class="students-list">
                            @foreach($detailsCategorie['etudiants_non_payes'] as $etudiant)
                            <div class="card-moderne student-card danger">
                                <div class="student-info">
                                    <div class="student-avatar">
                                        {{ substr($etudiant['inscription']->etudiant->user->name, 0, 2) }}
                                    </div>
                                    <div class="student-details">
                                        <h6>{{ $etudiant['inscription']->etudiant->user->name }}</h6>
                                        <p>{{ $etudiant['inscription']->filiere->name }} - {{ $etudiant['inscription']->niveauEtude->name }}</p>
                                    </div>
                                </div>
                                <div class="payment-summary">
                                    <span class="percentage-badge danger">0%</span>
                                    <div class="amount-info">
                                        <div class="amount-paid">0 FCFA payé</div>
                                        <div class="amount-due">{{ number_format($etudiant['montant_attendu']) }} FCFA dû</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    @if($detailsCategorie['etudiants_en_retard']->count() > 0)
                    <div class="mb-xl">
                        <h6 class="font-semibold color-warning mb-md">
                            <i class="fas fa-clock me-2"></i>
                            Étudiants avec paiements partiels ({{ $detailsCategorie['etudiants_en_retard']->count() }})
                        </h6>
                        <div class="students-list">
                            @foreach($detailsCategorie['etudiants_en_retard'] as $etudiant)
                            <div class="card-moderne student-card warning">
                                <div class="student-info">
                                    <div class="student-avatar">
                                        {{ substr($etudiant['inscription']->etudiant->user->name, 0, 2) }}
                                    </div>
                                    <div class="student-details">
                                        <h6>{{ $etudiant['inscription']->etudiant->user->name }}</h6>
                                        <p>{{ $etudiant['inscription']->filiere->name }} - {{ $etudiant['inscription']->niveauEtude->name }}</p>
                                    </div>
                                </div>
                                <div class="payment-summary">
                                    <span class="percentage-badge warning">{{ $etudiant['pourcentage'] }}%</span>
                                    <div class="amount-info">
                                        <div class="amount-paid">{{ number_format($etudiant['montant_paye']) }} FCFA payé</div>
                                        <div class="amount-due">{{ number_format($etudiant['solde']) }} FCFA restant</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    @if($detailsCategorie['etudiants_a_jour']->count() > 0)
                    <div class="mb-xl">
                        <h6 class="font-semibold color-success mb-md">
                            <i class="fas fa-check-circle me-2"></i>
                            Étudiants à jour ({{ $detailsCategorie['etudiants_a_jour']->count() }})
                        </h6>
                        <div class="students-list">
                            @foreach($detailsCategorie['etudiants_a_jour'] as $etudiant)
                            <div class="card-moderne student-card success">
                                <div class="student-info">
                                    <div class="student-avatar">
                                        {{ substr($etudiant['inscription']->etudiant->user->name, 0, 2) }}
                                    </div>
                                    <div class="student-details">
                                        <h6>{{ $etudiant['inscription']->etudiant->user->name }}</h6>
                                        <p>{{ $etudiant['inscription']->filiere->name }} - {{ $etudiant['inscription']->niveauEtude->name }}</p>
                                    </div>
                                </div>
                                <div class="payment-summary">
                                    <span class="percentage-badge success">100%</span>
                                    <div class="amount-info">
                                        <div class="amount-paid">{{ number_format($etudiant['montant_paye']) }} FCFA payé</div>
                                        <div class="amount-due">Soldé</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Animation des cartes au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 100);
            }
        });
    }, observerOptions);
    
    // Observer toutes les cartes
    $('.card-moderne').each(function() {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)',
            'transition': 'all 0.6s ease-out'
        });
        observer.observe(this);
    });
    
    // Tooltip pour les pourcentages
    $('.percentage-badge').each(function() {
        const percentage = $(this).text();
        $(this).attr('title', `Taux de paiement: ${percentage}`);
    });
});
</script>
@endpush