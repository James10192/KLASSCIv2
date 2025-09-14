@extends('layouts.app')

@section('title', 'Gestion des Réinscriptions')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
.table-moderne {
    width: 100%;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;
    font-size: 14px;
}

.table-moderne table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.table-moderne thead th {
    padding: 16px 12px;
    background-color: #f8fafc;
    color: #64748b;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid rgba(0, 0, 0, 0.05);
}

.table-moderne tbody tr {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.table-moderne tbody tr:hover {
    background-color: #f8fafc;
}

.table-moderne tbody td {
    padding: 16px 12px;
    vertical-align: middle;
}

.table-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.table-badge.primary {
    background-color: rgba(59, 130, 246, 0.1);
    color: rgb(59, 130, 246);
}

.table-badge.success {
    background-color: rgba(34, 197, 94, 0.1);
    color: rgb(34, 197, 94);
}

.table-badge.warning {
    background-color: rgba(245, 158, 11, 0.1);
    color: rgb(245, 158, 11);
}

.table-badge.danger {
    background-color: rgba(239, 68, 68, 0.1);
    color: rgb(239, 68, 68);
}

.table-actions {
    display: flex;
    gap: 4px;
    justify-content: center;
}

.btn-table-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 14px;
}

.btn-table-action.primary {
    background-color: rgba(59, 130, 246, 0.1);
    color: rgb(59, 130, 246);
}

.btn-table-action.primary:hover {
    background-color: rgba(59, 130, 246, 0.2);
}

.btn-table-action.warning {
    background-color: rgba(245, 158, 11, 0.1);
    color: rgb(245, 158, 11);
}

.btn-table-action.warning:hover {
    background-color: rgba(245, 158, 11, 0.2);
}

/* SPINNER ISOLÉ - Force tous les styles */
.reinscription-spinner {
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    min-height: 200px !important;
    text-align: center !important;
    padding: 40px !important;
}

.reinscription-spinner.hidden {
    display: none !important;
}

.reinscription-spinner-icon {
    display: block !important;
    margin-bottom: 20px !important;
    text-align: center !important;
}

.reinscription-spinner-icon i {
    font-size: 48px !important;
    color: #3b82f6 !important;
    animation: reinscription-spin 1s linear infinite !important;
    transform-origin: center center !important;
}

.reinscription-spinner-text {
    display: block !important;
    position: static !important;
    animation: none !important;
    transform: none !important;
    color: #64748b !important;
    margin: 0 !important;
    padding: 0 !important;
    font-size: 14px !important;
    font-weight: normal !important;
    text-align: center !important;
}

@keyframes reinscription-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Container pour le contenu des onglets */
.content-container {
    width: 100% !important;
    min-height: 200px;
}

/* S'assurer que les tab-panes prennent toute la largeur */
.tab-pane {
    width: 100% !important;
}

.tab-content {
    width: 100% !important;
}

/* S'assurer que les tables prennent toute la largeur */
.content-container .table-responsive {
    width: 100% !important;
    margin: 0;
}

.content-container .table-responsive table {
    width: 100% !important;
    margin: 0;
}

/* Correction pour mobile */
@media (max-width: 768px) {
    .table-moderne {
        min-width: unset;
        width: 100%;
    }
    
    .content-container {
        padding: 0 !important;
    }
    
    .table-responsive {
        border-radius: var(--radius-medium);
        -webkit-overflow-scrolling: touch;
    }
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Réinscriptions</h1>
                <p class="header-subtitle">Gestion des passages, rattrapages et redoublements</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.reinscription.regles.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-cogs"></i>Règles Académiques
                </a>
                <button class="btn-acasi primary" onclick="exportResults()">
                    <i class="fas fa-download"></i>Exporter
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="card-moderne mb-md" style="border-left: 4px solid var(--danger); background-color: rgba(239, 68, 68, 0.05);">
                <div class="p-lg">
                    <ul style="margin: 0; padding-left: 20px; color: var(--danger);">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="card-moderne mb-md" style="border-left: 4px solid var(--success); background-color: rgba(16, 185, 129, 0.05);">
                <div class="p-lg">
                    <p style="margin: 0; color: var(--success); font-weight: 500;">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Filtres de réinscription -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-filter me-2"></i>Filtres de réinscription
                </div>
                <form method="GET" action="{{ route('esbtp.reinscription.index') }}" id="reinscriptionFiltersForm">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-md); margin-bottom: var(--space-md);">
                        <!-- Recherche -->
                        <div>
                            <label for="search" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Recherche</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom, matricule..." class="form-control" style="width: 100%;">
                        </div>
                        
                        
                        <!-- Filière -->
                        <div>
                            <label for="filiere_id" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Filière</label>
                            <select name="filiere_id" id="filiere_id" class="form-control" style="width: 100%;">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Niveau -->
                        <div>
                            <label for="niveau_id" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Niveau</label>
                            <select name="niveau_id" id="niveau_id" class="form-control" style="width: 100%;">
                                <option value="">Tous les niveaux</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Statut de réinscription -->
                        <div>
                            <label for="statut_reinscription" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Statut</label>
                            <select name="statut_reinscription" id="statut_reinscription" class="form-control" style="width: 100%;">
                                <option value="">Tous les statuts</option>
                                <option value="passage" {{ request('statut_reinscription') == 'passage' ? 'selected' : '' }}>Passage</option>
                                <option value="rattrapage" {{ request('statut_reinscription') == 'rattrapage' ? 'selected' : '' }}>Rattrapage</option>
                                <option value="redoublement" {{ request('statut_reinscription') == 'redoublement' ? 'selected' : '' }}>Redoublement</option>
                                <option value="abandon" {{ request('statut_reinscription') == 'abandon' ? 'selected' : '' }}>Abandon</option>
                                <option value="valide" {{ request('statut_reinscription') == 'valide' ? 'selected' : '' }}>Validé</option>
                            </select>
                        </div>
                        
                        <!-- Statut paiement -->
                        <div>
                            <label for="statut_paiement" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Paiement</label>
                            <select name="statut_paiement" id="statut_paiement" class="form-control" style="width: 100%;">
                                <option value="">Tous</option>
                                <option value="solde" {{ request('statut_paiement') == 'solde' ? 'selected' : '' }}>Soldé</option>
                                <option value="impaye" {{ request('statut_paiement') == 'impaye' ? 'selected' : '' }}>Impayé</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: var(--space-md); align-items: center;">
                        <button type="submit" class="btn-acasi primary">
                            <i class="fas fa-search me-1"></i>Filtrer
                        </button>
                        <a href="{{ route('esbtp.reinscription.index') }}" class="btn-acasi secondary">
                            <i class="fas fa-times me-1"></i>Réinitialiser
                        </a>
                        <div style="margin-left: auto; font-size: var(--text-small); color: var(--text-muted);">
                            <i class="fas fa-calendar me-1"></i>Année: {{ $anneeAcademique }}
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <!-- Information sur le nouveau système de réinscription -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="alert alert-info">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-info-circle me-3 mt-1" style="color: var(--primary);"></i>
                        <div>
                            <h6 class="mb-2" style="color: var(--primary);">Nouveau Système de Réinscription</h6>
                            <p class="mb-2">
                                <strong>Principe :</strong> Chaque réinscription crée une <strong>nouvelle inscription</strong> pour la nouvelle année universitaire 
                                avec recalcul complet des frais selon la nouvelle classe assignée.
                            </p>
                            <ul class="mb-0" style="padding-left: 20px;">
                                <li><strong>Condition requise :</strong> L'étudiant doit être <strong>entièrement soldé</strong> (100%) avant de pouvoir se réinscrire</li>
                                <li><strong>Historique préservé :</strong> Les anciennes inscriptions restent visibles dans le profil étudiant</li>
                                <li><strong>Nouveaux frais :</strong> Possibilité de sélectionner de nouveaux frais optionnels lors de la réinscription</li>
                                <li><strong>Facture automatique :</strong> Une nouvelle facture est générée automatiquement pour la nouvelle inscription</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="kpi-grid mb-xl">
            <div class="card-moderne kpi-card">
                <div class="kpi-title">Passages</div>
                <div class="kpi-value color-success">{{ $statistiques['passages'] ?? 0 }}</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>Admis niveau supérieur</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Rattrapages</div>
                <div class="kpi-value color-warning">{{ $statistiques['rattrapages'] ?? 0 }}</div>
                <div class="kpi-trend">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Session de rattrapage</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Redoublements</div>
                <div class="kpi-value color-danger">{{ $statistiques['redoublements'] ?? 0 }}</div>
                <div class="kpi-trend negative">
                    <i class="fas fa-redo"></i>
                    <span>Reprise de l'année</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Abandons Année</div>
                <div class="kpi-value color-danger">{{ $statistiques['abandons_annee'] ?? 0 }}</div>
                <div class="kpi-trend negative">
                    <i class="fas fa-user-slash"></i>
                    <span>Année abandonnée</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Abandons École</div>
                <div class="kpi-value color-neutral">{{ $statistiques['abandons_ecole'] ?? 0 }}</div>
                <div class="kpi-trend">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Quittent l'établissement</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Validés</div>
                <div class="kpi-value color-success">{{ $statistiques['valides'] ?? 0 }}</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-check-double"></i>
                    <span>Réinscriptions confirmées</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Non validés</div>
                <div class="kpi-value color-neutral">{{ $statistiques['errors'] ?? 0 }}</div>
                <div class="kpi-trend">
                    <i class="fas fa-user-clock"></i>
                    <span>Inscriptions en cours</span>
                </div>
            </div>
        </div>

        <!-- Onglets pour les différentes catégories -->
        <div class="card-moderne">
            <div class="p-lg" style="border-bottom: 1px solid rgba(0, 0, 0, 0.05);">
                <ul class="nav nav-tabs" id="myTab" role="tablist" style="border: none; display: flex; gap: var(--space-md);">
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="passages-tab" data-toggle="tab" href="#passages" role="tab" 
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-arrow-up"></i> Passages ({{ $statistiques['passages'] ?? 0 }})
                        </a>
                    </li>
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="rattrapages-tab" data-toggle="tab" href="#rattrapages" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-exclamation-triangle"></i> Rattrapages ({{ $statistiques['rattrapages'] ?? 0 }})
                        </a>
                    </li>
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="redoublements-tab" data-toggle="tab" href="#redoublements" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-redo"></i> Redoublements ({{ $statistiques['redoublements'] ?? 0 }})
                        </a>
                    </li>
                    @if(($statistiques['valides'] ?? 0) > 0)
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="valides-tab" data-toggle="tab" href="#valides" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-check-double"></i> Validés ({{ $statistiques['valides'] ?? 0 }})
                        </a>
                    </li>
                    @endif
                    @if(($statistiques['abandons_annee'] ?? 0) > 0)
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="abandons-annee-tab" data-toggle="tab" href="#abandons-annee" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-user-slash"></i> Abandons Année ({{ $statistiques['abandons_annee'] ?? 0 }})
                        </a>
                    </li>
                    @endif
                    @if(($statistiques['abandons_ecole'] ?? 0) > 0)
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="abandons-ecole-tab" data-toggle="tab" href="#abandons-ecole" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-graduation-cap"></i> Abandons École ({{ $statistiques['abandons_ecole'] ?? 0 }})
                        </a>
                    </li>
                    @endif
                    @if(($statistiques['errors'] ?? 0) > 0)
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="errors-tab" data-toggle="tab" href="#errors" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-user-clock"></i> Non validés ({{ $statistiques['errors'] ?? 0 }})
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            <div class="p-lg">
                <div class="tab-content" id="myTabContent">
                <!-- Onglet Passages -->
                <div class="tab-pane fade" id="passages" role="tabpanel" data-category="passages">
                    <div class="reinscription-spinner">
                        <div class="reinscription-spinner-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="reinscription-spinner-text">Chargement des passages...</div>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>

                <!-- Onglet Rattrapages -->
                <div class="tab-pane fade" id="rattrapages" role="tabpanel" data-category="rattrapages">
                    <div class="reinscription-spinner">
                        <div class="reinscription-spinner-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="reinscription-spinner-text">Chargement des rattrapages...</div>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>

                <!-- Onglet Redoublements -->
                <div class="tab-pane fade" id="redoublements" role="tabpanel" data-category="redoublements">
                    <div class="reinscription-spinner">
                        <div class="reinscription-spinner-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="reinscription-spinner-text">Chargement des redoublements...</div>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>

                <!-- Onglet Validés -->
                @if(($statistiques['valides'] ?? 0) > 0)
                <div class="tab-pane fade" id="valides" role="tabpanel" data-category="valides">
                    <div class="reinscription-spinner">
                        <div class="reinscription-spinner-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="reinscription-spinner-text">Chargement des validés...</div>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>
                @endif

                <!-- Onglet Abandons Année -->
                @if(($statistiques['abandons_annee'] ?? 0) > 0)
                <div class="tab-pane fade" id="abandons-annee" role="tabpanel" data-category="abandons_annee">
                    <div class="reinscription-spinner">
                        <div class="reinscription-spinner-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="reinscription-spinner-text">Chargement des abandons année...</div>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>
                @endif

                <!-- Onglet Abandons École -->
                @if(($statistiques['abandons_ecole'] ?? 0) > 0)
                <div class="tab-pane fade" id="abandons-ecole" role="tabpanel" data-category="abandons_ecole">
                    <div class="reinscription-spinner">
                        <div class="reinscription-spinner-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="reinscription-spinner-text">Chargement des abandons école...</div>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>
                @endif

                <!-- Onglet Erreurs -->
                @if(($statistiques['errors'] ?? 0) > 0)
                <div class="tab-pane fade" id="errors" role="tabpanel" data-category="errors">
                    <div class="reinscription-spinner">
                        <div class="reinscription-spinner-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="reinscription-spinner-text">Chargement des non validés...</div>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>
                @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour informations changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Changement d'année académique</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour changer d'année académique :</strong></p>
                <ol>
                    <li>Accédez au menu <strong>"Gestion" → "Années Universitaires"</strong></li>
                    <li>Activez l'année souhaitée en cliquant sur <strong>"Définir comme courante"</strong></li>
                    <li>Revenez sur cette page pour voir les données de la nouvelle année</li>
                </ol>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    Seule l'année marquée comme "courante" est affichée dans les réinscriptions.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" class="btn btn-primary">
                    Gérer les Années
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// LOGS IMMÉDIATS AVANT CHARGEMENT JQUERY (PAGE REINSCRIPTIONS)
console.log('🟢 DEBUG REINSCRIPTIONS: Script debug DÉBUT');
console.log('🟢 DEBUG REINSCRIPTIONS: jQuery disponible avant chargement?', typeof $ !== 'undefined');
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// LOGS APRÈS JQUERY (PAGE REINSCRIPTIONS)
console.log('🟢 DEBUG REINSCRIPTIONS: jQuery chargé?', typeof $ !== 'undefined');
console.log('🟢 DEBUG REINSCRIPTIONS: jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'N/A');
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// LOGS APRÈS BOOTSTRAP (PAGE REINSCRIPTIONS)
console.log('🟢 DEBUG REINSCRIPTIONS: Bootstrap 4.6.2 chargé?', typeof $.fn.modal !== 'undefined');
console.log('🟢 DEBUG REINSCRIPTIONS: Bootstrap version:', typeof bootstrap !== 'undefined' ? bootstrap : 'N/A');

$(document).ready(function() {
    console.log('🟢 DEBUG REINSCRIPTIONS: Document ready');
    
    // Debug du modal "Changer d'année"
    const yearChangeModal = $('#yearChangeModal');
    console.log('🟢 DEBUG REINSCRIPTIONS: Modal changement année trouvé?', yearChangeModal.length > 0);
    
    const yearChangeButton = $('button[onclick="showYearChangeInfo()"]');
    console.log('🟢 DEBUG REINSCRIPTIONS: Bouton changement année trouvé?', yearChangeButton.length > 0);
    
    // Debug du bouton "Changer d'année" existant
    const changeYearBtn = $('button[onclick="showYearChangeInfo()"]');
    console.log('🟢 DEBUG REINSCRIPTIONS: Bouton "Changer d\'année" trouvé?', changeYearBtn.length > 0);
    if (changeYearBtn.length > 0) {
        console.log('🟢 DEBUG REINSCRIPTIONS: Texte du bouton:', changeYearBtn.text().trim());
        console.log('🟢 DEBUG REINSCRIPTIONS: Attribut onclick:', changeYearBtn.attr('onclick'));
    }
    
    // Intercepter les clics sur le bouton "Changer d'année" 
    changeYearBtn.on('click', function() {
        console.log('🖱️ DEBUG REINSCRIPTIONS: Clic détecté sur bouton "Changer d\'année"');
        console.log('🎯 DEBUG REINSCRIPTIONS: Tentative d\'ouverture du modal #yearChangeModal');
    });
    
    // Écouter les événements du modal
    $('#yearChangeModal').on('show.bs.modal', function (e) {
        console.log('🎭 DEBUG REINSCRIPTIONS: Événement show.bs.modal déclenché');
    });
    
    $('#yearChangeModal').on('shown.bs.modal', function (e) {
        console.log('✅ DEBUG REINSCRIPTIONS: Modal affiché avec succès');
    });
});
</script>

<script>
// Variables pour le système de lazy loading
let loadedTabs = {};
let currentPage = {};

$(document).ready(function() {
    console.log("🚀 DEBUG: Page ready, initialisation du lazy loading");
    
    // CORRECTION: Charger automatiquement l'onglet avec le plus d'étudiants
    const statistiques = {
        passages: {{ $statistiques['passages'] ?? 0 }},
        redoublements: {{ $statistiques['redoublements'] ?? 0 }},
        rattrapages: {{ $statistiques['rattrapages'] ?? 0 }},
        valides: {{ $statistiques['valides'] ?? 0 }},
        abandons_annee: {{ $statistiques['abandons_annee'] ?? 0 }},
        abandons_ecole: {{ $statistiques['abandons_ecole'] ?? 0 }},
        errors: {{ $statistiques['errors'] ?? 0 }}
    };
    
    console.log("📊 DEBUG: Statistiques reçues:", statistiques);
    
    // Trouver la catégorie avec le plus d'étudiants
    let maxCategory = 'passages';
    let maxCount = 0;
    for (const [category, count] of Object.entries(statistiques)) {
        console.log(`📈 DEBUG: Catégorie "${category}": ${count} étudiants`);
        if (count > maxCount) {
            maxCount = count;
            maxCategory = category;
        }
    }
    
    console.log(`🎯 DEBUG: Catégorie principale détectée: "${maxCategory}" avec ${maxCount} étudiants`);
    
    // Charger cette catégorie au démarrage
    if (maxCount > 0) {
        console.log(`🔄 DEBUG: Activation de l'onglet "${maxCategory}"`);
        
        // Activer l'onglet correspondant
        $('a[data-toggle="tab"]').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
        const tabLink = $(`a[href="#${maxCategory}"]`);
        const tabPane = $(`#${maxCategory}`);
        
        console.log(`🔍 DEBUG: Tab link trouvé:`, tabLink.length > 0);
        console.log(`🔍 DEBUG: Tab pane trouvé:`, tabPane.length > 0);
        
        tabLink.addClass('active');
        tabPane.addClass('show active');
        
        // Cacher le spinner de cette catégorie car elle va être chargée
        const maxTabPane = $(`#${maxCategory}`);
        const maxSpinner = maxTabPane.find('.reinscription-spinner');
        maxSpinner.addClass('hidden');
        
        console.log(`📞 DEBUG: Appel loadTabContent("${maxCategory}")`);
        loadTabContent(maxCategory);
        
        // Marquer cette catégorie comme chargée
        loadedTabs[maxCategory] = true;
    } else {
        console.log("⚠️ DEBUG: Aucune catégorie avec des étudiants trouvée");
    }
    
    // Gérer les clics sur les onglets - Approche multiple pour plus de robustesse
    console.log(`🔍 DEBUG: Configuration des gestionnaires d'onglets`);
    
    // Méthode 1: Bootstrap shown.bs.tab
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        console.log(`🔗 DEBUG: Bootstrap shown.bs.tab détecté`);
        const targetTab = $(e.target).attr('href').substring(1);
        console.log(`🎯 DEBUG: targetTab: "${targetTab}"`);
        
        const tabPane = $('#' + targetTab);
        const category = tabPane.data('category');
        console.log(`📂 DEBUG: category extraite: "${category}"`);
        console.log(`💾 DEBUG: loadedTabs status:`, loadedTabs);
        console.log(`❓ DEBUG: "${category}" déjà chargé?`, loadedTabs[category] || false);
        
        if (category) {
            if (loadedTabs[category]) {
                console.log(`✅ DEBUG: Catégorie "${category}" déjà en cache, pas de rechargement`);
            } else {
                console.log(`🚀 DEBUG: Chargement Bootstrap de la catégorie "${category}"`);
                loadTabContent(category);
            }
        }
    });
    
    // Méthode 2: Clic direct comme fallback
    $('a[data-toggle="tab"]').on('click', function (e) {
        console.log(`👆 DEBUG: Clic direct sur onglet détecté`);
        const targetTab = $(this).attr('href').substring(1);
        console.log(`🎯 DEBUG: targetTab: "${targetTab}"`);
        
        // Attendre un peu que l'onglet soit activé
        setTimeout(() => {
            const tabPane = $('#' + targetTab);
            const category = tabPane.data('category');
            console.log(`📂 DEBUG: category extraite après timeout: "${category}"`);
            console.log(`💾 DEBUG: loadedTabs status:`, loadedTabs);
            console.log(`❓ DEBUG: "${category}" déjà chargé?`, loadedTabs[category] || false);
            
            if (category) {
                if (loadedTabs[category]) {
                    console.log(`✅ DEBUG: Catégorie "${category}" déjà en cache, pas de rechargement`);
                } else {
                    console.log(`🚀 DEBUG: Chargement par clic de la catégorie "${category}"`);
                    loadTabContent(category);
                }
            }
        }, 100);
    });
});

// Fonction pour forcer le rechargement d'un onglet (efface le cache)
function refreshTab(category) {
    console.log(`🔄 DEBUG: Forcer le rechargement de "${category}"`);
    loadedTabs[category] = false;
    
    // Remettre le spinner et cacher le contenu
    const tabPane = $(`[data-category="${category}"]`);
    const loadingSpinner = tabPane.find('.reinscription-spinner');
    const contentContainer = tabPane.find('.content-container');
    
    loadingSpinner.removeClass('hidden');
    contentContainer.hide().html('');
    
    // Recharger
    loadTabContent(category);
}

// Fonction principale de chargement lazy
function loadTabContent(category, page = 1) {
    console.log(`🔥 DEBUG: loadTabContent("${category}", ${page})`);
    
    const tabPane = $(`[data-category="${category}"]`);
    const loadingSpinner = tabPane.find('.reinscription-spinner');
    const contentContainer = tabPane.find('.content-container');
    
    console.log(`🔍 DEBUG: Éléments trouvés:`);
    console.log(`  - tabPane:`, tabPane.length > 0, tabPane);
    console.log(`  - loadingSpinner:`, loadingSpinner.length > 0, loadingSpinner);
    console.log(`  - contentContainer:`, contentContainer.length > 0, contentContainer);
    
    // DEBUG ULTRA: Vérifier les états avant/après
    console.log(`🔍 DEBUG ÉTATS AVANT:`);
    console.log(`  - spinner visible:`, loadingSpinner.is(':visible'));
    console.log(`  - container visible:`, contentContainer.is(':visible'));
    console.log(`  - spinner display:`, loadingSpinner.css('display'));
    console.log(`  - container display:`, contentContainer.css('display'));
    
    // Afficher le spinner si c'est la première page
    if (page === 1) {
        console.log(`🔄 DEBUG: Affichage du spinner pour page 1`);
        loadingSpinner.removeClass('hidden');
        contentContainer.hide();
    }
    
    const ajaxUrl = `{{ route('esbtp.reinscription.load-category', ':category') }}`.replace(':category', category);
    console.log(`📡 DEBUG: URL AJAX: ${ajaxUrl}`);
    
    // Faire la requête AJAX
    $.ajax({
        url: ajaxUrl,
        method: 'GET',
        data: {
            page: page,
            per_page: 50
        },
        success: function(response) {
            console.log(`✅ DEBUG: AJAX Success pour "${category}", page ${page}`);
            console.log(`📊 DEBUG: Response total:`, response.total);
            console.log(`📄 DEBUG: Response HTML length:`, response.html ? response.html.length : 0);
            console.log(`🔄 DEBUG: Response has_more:`, response.has_more);
            
            if (page === 1) {
                console.log(`🎯 DEBUG: Traitement première page`);
                // Première page : remplacer le contenu
                console.log(`🚫 DEBUG: Masquage du spinner`);
                console.log(`🔍 DEBUG AVANT addClass('hidden'):`, loadingSpinner.hasClass('hidden'));
                loadingSpinner.addClass('hidden');
                console.log(`🔍 DEBUG APRÈS addClass('hidden'):`, loadingSpinner.hasClass('hidden'));
                
                // CORRECTION: Gérer les catégories vides
                if (response.total === 0) {
                    console.log(`⚠️ DEBUG: Catégorie vide, affichage message`);
                    const emptyHtml = `
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-info-circle fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted">Aucun étudiant dans cette catégorie</h5>
                            <p class="text-muted">Tous les étudiants ont été traités ou il n'y a pas de données pour cette période.</p>
                        </div>
                    `;
                    contentContainer.html(emptyHtml);
                } else {
                    console.log(`📝 DEBUG: Injection du HTML (${response.html.length} chars)`);
                    contentContainer.html(response.html);
                }
                
                console.log(`👁️ DEBUG: Affichage du contenu`);
                console.log(`🔍 DEBUG AVANT show():`, contentContainer.is(':visible'));
                
                // FORCE l'affichage avec plusieurs méthodes
                contentContainer.show();
                contentContainer.css('display', 'block');
                contentContainer.css('width', '100%');
                contentContainer.css('visibility', 'visible');
                
                console.log(`🔍 DEBUG APRÈS show():`, contentContainer.is(':visible'));
                console.log(`🔍 DEBUG CSS display:`, contentContainer.css('display'));
                console.log(`🔍 DEBUG largeur:`, contentContainer.css('width'));
                
                // Vérifier que le contenu a bien été injecté
                const injectedContent = contentContainer.html();
                console.log(`📝 DEBUG contenu injecté (taille):`, injectedContent.length);
                console.log(`🎨 DEBUG contient table-responsive:`, injectedContent.includes('table-responsive'));
                
                // Forcer l'affichage de tous les éléments
                const tableResponsive = contentContainer.find('.table-responsive');
                const table = contentContainer.find('table');
                const thead = contentContainer.find('thead');
                const tbody = contentContainer.find('tbody');
                
                console.log(`📋 DEBUG éléments trouvés:`);
                console.log(`  - table-responsive: ${tableResponsive.length}`);
                console.log(`  - table: ${table.length}`);
                console.log(`  - thead: ${thead.length}`);
                console.log(`  - tbody: ${tbody.length}`);
                
                // Forcer l'affichage de chaque élément
                if (tableResponsive.length > 0) {
                    console.log(`🔧 Force affichage table-responsive`);
                    tableResponsive.css({
                        'display': 'block !important',
                        'width': '100% !important',
                        'visibility': 'visible !important'
                    });
                }
                
                if (table.length > 0) {
                    console.log(`🔧 Force affichage table`);
                    table.css({
                        'display': 'table !important',
                        'width': '100% !important',
                        'visibility': 'visible !important'
                    });
                }
                
                if (thead.length > 0) {
                    console.log(`🔧 Force affichage thead`);
                    thead.css({
                        'display': 'table-header-group !important',
                        'visibility': 'visible !important'
                    });
                }
                
                if (tbody.length > 0) {
                    console.log(`🔧 Force affichage tbody`);
                    tbody.css({
                        'display': 'table-row-group !important',
                        'visibility': 'visible !important'
                    });
                }
                loadedTabs[category] = true;
                currentPage[category] = 1;
                console.log(`💾 DEBUG: Catégorie "${category}" mise en cache pour éviter les rechargements`);
            } else {
                console.log(`➕ DEBUG: Ajout page ${page}`);
                // Pages suivantes : ajouter les lignes au tbody existant
                const existingTable = contentContainer.find('table tbody');
                if (existingTable.length > 0) {
                    console.log(`📝 DEBUG: Ajout des lignes au tbody existant`);
                    existingTable.append(response.html);
                } else {
                    console.log(`⚠️ DEBUG: Pas de tbody trouvé, ajout classique`);
                    contentContainer.append(response.html);
                }
            }
            
            // Gérer le bouton "Charger plus"
            const loadMoreBtn = contentContainer.find('.load-more-btn');
            loadMoreBtn.remove(); // Supprimer l'ancien bouton
            
            if (response.has_more) {
                const nextPage = page + 1;
                const btnHtml = `
                    <div class="text-center mt-4 load-more-container">
                        <button class="btn-acasi secondary load-more-btn" 
                                onclick="loadMore('${category}', ${nextPage})"
                                data-category="${category}" data-page="${nextPage}">
                            <i class="fas fa-plus-circle"></i>
                            Charger plus (${response.total - (page * 50)} restants)
                        </button>
                    </div>
                `;
                contentContainer.append(btnHtml);
            }
            
            currentPage[category] = page;
        },
        error: function(xhr, status, error) {
            console.error(`❌ DEBUG: AJAX Error pour "${category}", page ${page}`);
            console.error(`🔴 DEBUG: Status:`, status);
            console.error(`🔴 DEBUG: Error:`, error);
            console.error(`🔴 DEBUG: XHR Status:`, xhr.status);
            console.error(`🔴 DEBUG: XHR Response:`, xhr.responseText);
            
            const errorHtml = `
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                    </div>
                    <h5 class="text-danger">Erreur de chargement</h5>
                    <p class="text-muted">Impossible de charger les données. 
                        <button class="btn-link" onclick="loadTabContent('${category}')">Réessayer</button>
                    </p>
                    <small class="text-muted">Erreur: ${xhr.status} - ${error}</small>
                </div>
            `;
            
            if (page === 1) {
                console.log(`🛑 DEBUG: Masquage spinner et affichage erreur`);
                loadingSpinner.addClass('hidden');
                contentContainer.html(errorHtml).show();
            }
        }
    });
}

// Fonction pour charger plus d'étudiants
function loadMore(category, page) {
    const loadMoreBtn = $(`.load-more-btn[data-category="${category}"]`);
    const originalText = loadMoreBtn.html();
    
    // Afficher un spinner sur le bouton
    loadMoreBtn.html('<i class="fas fa-spinner fa-spin"></i> Chargement...')
              .prop('disabled', true);
    
    loadTabContent(category, page);
}

// Fonctions utilitaires pour les actions sur les étudiants
function validerReinscription(etudiantId, decision) {
    const observations = prompt(`Valider la réinscription avec décision: ${decision}\n\nObservations (optionnel):`);
    
    if (observations === null) return; // Annulé
    
    if (confirm(`Confirmer la validation de la réinscription ?\n\nDécision: ${decision}\nObservations: ${observations || 'Aucune'}`)) {
        $.ajax({
            url: `{{ url('esbtp/reinscription') }}/${etudiantId}/valider`,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify({
                decision: decision,
                observations: observations
            }),
            success: function(data) {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Recharger la page pour voir les changements
                } else {
                    alert('Erreur: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la validation');
            }
        });
    }
}

function marquerAbandonModal(etudiantId) {
    const typeAbandon = confirm('Type d\'abandon:\n\nOUI = Abandon année scolaire (n\'a pas soldé, ne vient plus)\nNON = Abandon école (année réussie mais quitte l\'établissement)') 
        ? 'annee_scolaire' : 'ecole';
    
    const motif = prompt('Motif de l\'abandon (optionnel):');
    if (motif === null) return; // Annulé
    
    if (confirm(`Confirmer l'abandon de type "${typeAbandon === 'annee_scolaire' ? 'Année scolaire' : 'École'}" ?\n\nMotif: ${motif || 'Non précisé'}`)) {
        $.ajax({
            url: `{{ url('esbtp/reinscription') }}/${etudiantId}/abandon`,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify({
                motif_abandon: motif,
                abandon_type: typeAbandon
            }),
            success: function(data) {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'enregistrement de l\'abandon');
            }
        });
    }
}

function exportResults() {
    window.location.href = '{{ route("esbtp.reinscription.export") }}';
}

function showYearChangeInfo() {
    console.log('🟢 DEBUG REINSCRIPTIONS: Fonction showYearChangeInfo() appelée');
    console.log('🟢 DEBUG REINSCRIPTIONS: Modal #yearChangeModal existe?', $('#yearChangeModal').length > 0);
    try {
        $('#yearChangeModal').modal('show');
        console.log('✅ DEBUG REINSCRIPTIONS: Commande modal(show) exécutée dans showYearChangeInfo()');
    } catch (error) {
        console.error('❌ DEBUG REINSCRIPTIONS: Erreur dans showYearChangeInfo():', error);
    }
}

// FONCTION TEST DEBUG TEMPORAIRE
function testInjection() {
    console.log('🧪 TEST INJECTION HTML');
    
    const testHtml = `
        <div class="table-responsive" style="width: 100% !important; border: 2px solid red;">
            <h3 style="color: red; text-align: center;">TEST INJECTION</h3>
            <table class="table table-hover" style="width: 100% !important; border: 2px solid blue;">
                <thead style="background-color: #0453cb !important; color: white !important;">
                    <tr>
                        <th style="padding: 16px !important;">TEST HEADER 1</th>
                        <th style="padding: 16px !important;">TEST HEADER 2</th>
                        <th style="padding: 16px !important;">TEST HEADER 3</th>
                    </tr>
                </thead>
                <tbody style="background-color: white;">
                    <tr>
                        <td style="padding: 16px;">TEST DATA 1</td>
                        <td style="padding: 16px;">TEST DATA 2</td>
                        <td style="padding: 16px;">TEST DATA 3</td>
                    </tr>
                    <tr>
                        <td style="padding: 16px;">TEST DATA 4</td>
                        <td style="padding: 16px;">TEST DATA 5</td>
                        <td style="padding: 16px;">TEST DATA 6</td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
    
    // Trouver le content-container actif
    const activeTabPane = $('.tab-pane.active, .tab-pane.show');
    let targetContainer;
    
    if (activeTabPane.length > 0) {
        targetContainer = activeTabPane.find('.content-container');
        console.log('🎯 Container actif trouvé:', activeTabPane.attr('id'));
    } else {
        // Si pas d'onglet actif, utiliser redoublements
        targetContainer = $('#redoublements .content-container');
        console.log('🎯 Utilise container redoublements par défaut');
    }
    
    if (targetContainer.length > 0) {
        console.log('✅ Container trouvé, injection du HTML test');
        
        // Masquer le spinner
        targetContainer.siblings('.reinscription-spinner').addClass('hidden');
        
        // Injecter le HTML test
        targetContainer.html(testHtml);
        
        // Forcer l'affichage
        targetContainer.show();
        targetContainer.css({
            'display': 'block !important',
            'width': '100% !important',
            'visibility': 'visible !important'
        });
        
        console.log('✅ HTML test injecté avec succès');
        alert('HTML TEST injecté! Regardez si la table s\'affiche avec les headers.');
        
    } else {
        console.log('❌ Container non trouvé');
        alert('Erreur: Container non trouvé');
    }
}

// Gérer la fermeture de la modal
$('#yearChangeModal .close[data-dismiss="modal"]').on('click', function() {
    $('#yearChangeModal').modal('hide');
});

$('#yearChangeModal button[data-dismiss="modal"]').on('click', function() {
    $('#yearChangeModal').modal('hide');
});
</script>

@endsection