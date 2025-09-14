@extends('layouts.app')

@section('title', 'Suivi des Paiements par Catégorie - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .btn-acasi.small {
        padding: var(--space-xs) var(--space-sm);
        font-size: var(--text-small);
        border-radius: var(--radius-small);
    }
    
    /* Styles pour les cartes d'étudiants */
    .student-card {
        padding: var(--space-md);
        border-left: 4px solid transparent;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    
    .student-card.success { 
        border-left-color: var(--success); 
        background: rgba(16, 185, 129, 0.05); 
    }
    .student-card.warning { 
        border-left-color: var(--warning); 
        background: rgba(245, 158, 11, 0.05); 
    }
    .student-card.danger { 
        border-left-color: var(--danger); 
        background: rgba(239, 68, 68, 0.05); 
    }
    
    .student-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
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
    }
    .percentage-badge.warning { 
        background: rgba(245, 158, 11, 0.1); 
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    .percentage-badge.danger { 
        background: rgba(239, 68, 68, 0.1); 
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .students-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--space-md);
    }
    
    /* Styles pour les catégories - ancien style visuel */
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
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
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
    
    /* Barre de progression moderne */
    .progress-bar-modern {
        height: 12px;
        background: linear-gradient(90deg, rgba(0, 0, 0, 0.05) 0%, rgba(0, 0, 0, 0.08) 100%);
        border-radius: 8px;
        overflow: hidden;
        margin: var(--space-lg) 0;
        position: relative;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .progress-fill-modern {
        height: 100%;
        border-radius: 8px;
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
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

        <!-- Information année académique courante -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-calendar me-2"></i>Contexte d'affichage
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ date('Y') . '-' . (date('Y') + 1) }}" selected>
                                {{ date('Y') . '-' . (date('Y') + 1) }} (Année en cours)
                            </option>
                        </select>
                    </div>
                    <button type="button" class="btn-acasi secondary" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                        <i class="fas fa-info-circle"></i>Changer d'année
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Les paiements par catégorie affichés correspondent à l'année académique courante.
                    </small>
                </div>
            </div>
        </div>

        <!-- Filtres et Actions -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <form action="{{ route('esbtp.paiements.suivi-categories') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-2">
                            <label for="filiere_id" class="form-label">Filière</label>
                            <select name="filiere_id" id="filiere_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ $filiereId == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="niveau_id" class="form-label">Niveau d'étude</label>
                            <select name="niveau_id" id="niveau_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Tous les niveaux</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ $niveauId == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="category_id" class="form-label">Catégorie détaillée</label>
                            <select name="category_id" id="category_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Vue d'ensemble</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn-acasi primary w-100">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- KPI Cards comme dans la page index -->
        <div class="kpi-grid">
            <div class="card-moderne kpi-card">
                <div class="kpi-title">Étudiants en Règle</div>
                <div class="kpi-value color-success">{{ $vueEnsemble['etudiants_en_regle'] }}</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-check-circle me-2"></i>
                    @php
                        $totalEtudiants = $vueEnsemble['etudiants_en_regle'] + $vueEnsemble['etudiants_en_retard'] + $vueEnsemble['etudiants_non_payes'];
                    @endphp
                    @if($totalEtudiants > 0)
                        {{ round(($vueEnsemble['etudiants_en_regle'] / $totalEtudiants) * 100, 1) }}% du total
                    @else
                        Aucun étudiant
                    @endif
                </div>
            </div>
            
            <div class="card-moderne kpi-card">
                <div class="kpi-title">Paiements Partiels</div>
                <div class="kpi-value color-warning">{{ $vueEnsemble['etudiants_en_retard'] }}</div>
                <div class="kpi-trend">
                    <i class="fas fa-clock me-2"></i>
                    @if($totalEtudiants > 0)
                        {{ round(($vueEnsemble['etudiants_en_retard'] / $totalEtudiants) * 100, 1) }}% du total
                    @else
                        Aucun étudiant
                    @endif
                </div>
            </div>
            
            <div class="card-moderne kpi-card">
                <div class="kpi-title">Impayés</div>
                <div class="kpi-value color-danger">{{ $vueEnsemble['etudiants_non_payes'] }}</div>
                <div class="kpi-trend negative">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    @if($totalEtudiants > 0)
                        {{ round(($vueEnsemble['etudiants_non_payes'] / $totalEtudiants) * 100, 1) }}% du total
                    @else
                        Aucun étudiant
                    @endif
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Taux de Recouvrement Global</div>
                <div class="kpi-value color-primary">{{ $vueEnsemble['taux_recouvrement_global'] }}%</div>
                <div class="kpi-trend {{ $vueEnsemble['taux_recouvrement_global'] >= 75 ? 'positive' : ($vueEnsemble['taux_recouvrement_global'] >= 50 ? '' : 'negative') }}">
                    <i class="fas fa-chart-line me-2"></i>
                    {{ number_format($vueEnsemble['montant_total_recu'], 0, ',', ' ') }} / {{ number_format($vueEnsemble['montant_total_attendu'], 0, ',', ' ') }} FCFA
                </div>
            </div>
        </div>

        <!-- Répartition des étudiants - Style moderne avec barre pleine largeur -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="d-flex justify-content-between align-items-center mb-md">
                    <div class="section-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Répartition des Étudiants et Recouvrement
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="resultat-card border-start border-success border-3">
                            <div class="resultat-title">Répartition par Statut</div>
                            @php
                                $totalConcernes = $vueEnsemble['etudiants_en_regle'] + $vueEnsemble['etudiants_en_retard'] + $vueEnsemble['etudiants_non_payes'];
                                $enReglePercent = $totalConcernes > 0 ? ($vueEnsemble['etudiants_en_regle'] / $totalConcernes) * 100 : 0;
                                $enRetardPercent = $totalConcernes > 0 ? ($vueEnsemble['etudiants_en_retard'] / $totalConcernes) * 100 : 0;
                                $nonPayesPercent = 100 - $enReglePercent - $enRetardPercent;
                            @endphp
                            
                            <div class="progress mb-3" style="height: 20px;">
                                <div class="progress-bar bg-success" style="width: {{ $enReglePercent }}%" 
                                     title="{{ $vueEnsemble['etudiants_en_regle'] }} étudiants en règle"></div>
                                <div class="progress-bar bg-warning" style="width: {{ $enRetardPercent }}%" 
                                     title="{{ $vueEnsemble['etudiants_en_retard'] }} paiements partiels"></div>
                                <div class="progress-bar bg-danger" style="width: {{ $nonPayesPercent }}%" 
                                     title="{{ $vueEnsemble['etudiants_non_payes'] }} impayés"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between text-small">
                                <span class="text-success">{{ round($enReglePercent, 1) }}% en règle</span>
                                <span class="text-warning">{{ round($enRetardPercent, 1) }}% partiels</span>
                                <span class="text-danger">{{ round($nonPayesPercent, 1) }}% impayés</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="resultat-card border-start border-primary border-3">
                            <div class="resultat-title">Recouvrement Financier</div>
                            <div class="progress mb-3" style="height: 20px;">
                                <div class="progress-bar bg-primary" style="width: {{ $vueEnsemble['taux_recouvrement_global'] }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-success fw-bold">{{ number_format($vueEnsemble['montant_total_recu'], 0, ',', ' ') }} FCFA</span>
                                <span class="text-muted">/ {{ number_format($vueEnsemble['montant_total_attendu'], 0, ',', ' ') }} FCFA</span>
                            </div>
                            <small class="text-muted">
                                Restant : {{ number_format($vueEnsemble['montant_total_attendu'] - $vueEnsemble['montant_total_recu'], 0, ',', ' ') }} FCFA
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques par catégorie avec style visuel original -->
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
                        $categoryIcons = [
                            'academic' => 'fas fa-graduation-cap',
                            'service' => 'fas fa-cogs',
                            'administrative' => 'fas fa-file-alt'
                        ];
                        $categoryType = $category->category_type ?? 'academic';
                        $icon = $categoryIcons[$categoryType] ?? 'fas fa-money-bill';
                    @endphp
                    <div class="category-card" onclick="window.location.href='{{ route('esbtp.paiements.suivi-categories') }}?{{ http_build_query(array_merge(request()->query(), ['category_id' => $category->id])) }}'">
                        <div class="p-lg">
                            <div class="category-header">
                                <div>
                                    <div class="category-icon">
                                        <i class="{{ $icon }}"></i>
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
                                <span class="color-success font-medium">{{ number_format($stats['montant_total_recu'], 0, ',', ' ') }} FCFA</span>
                                <span class="color-secondary">/ {{ number_format($stats['montant_total_attendu'], 0, ',', ' ') }} FCFA</span>
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
    // Tooltip pour les pourcentages
    $('.percentage-badge').each(function() {
        const percentage = $(this).text();
        $(this).attr('title', `Taux de paiement: ${percentage}`);
    });
    
    // Animation simple au hover des cartes
    $('.card').hover(
        function() { $(this).addClass('shadow-lg'); },
        function() { $(this).removeClass('shadow-lg'); }
    );
});
</script>
@endpush

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les paiements par catégorie se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des paiements dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les paiements de 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les paiements de 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#yearChangeModal').modal('hide');">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function showYearChangeInfo() {
    $('#yearChangeModal').modal('show');
}

// Gérer la fermeture de la modal d'info année
$(document).ready(function() {
    // Gérer la fermeture avec le bouton X
    $('#yearChangeModal .close[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });
    
    // Gérer la fermeture avec le bouton Fermer
    $('#yearChangeModal button[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });
});
</script>