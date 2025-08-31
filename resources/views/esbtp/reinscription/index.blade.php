@extends('layouts.app')

@section('title', 'Gestion des Réinscriptions')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
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

        <!-- Filtre année académique -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-filter me-2"></i>Filtres d'analyse
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ $anneeAcademique }}" selected>
                                {{ $anneeAcademique }} (Année en cours)
                            </option>
                        </select>
                    </div>
                    <button type="button" class="btn-acasi secondary" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                        <i class="fas fa-info-circle"></i>Changer d'année
                    </button>
                </div>
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
                    <div class="loading-spinner text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">Chargement des passages...</p>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>

                <!-- Onglet Rattrapages -->
                <div class="tab-pane fade" id="rattrapages" role="tabpanel" data-category="rattrapages">
                    <div class="loading-spinner text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">Chargement des rattrapages...</p>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>

                <!-- Onglet Redoublements -->
                <div class="tab-pane fade" id="redoublements" role="tabpanel" data-category="redoublements">
                    <div class="loading-spinner text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">Chargement des redoublements...</p>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>

                <!-- Onglet Validés -->
                @if(($statistiques['valides'] ?? 0) > 0)
                <div class="tab-pane fade" id="valides" role="tabpanel" data-category="valides">
                    <div class="loading-spinner text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">Chargement des validés...</p>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>
                @endif

                <!-- Onglet Abandons Année -->
                @if(($statistiques['abandons_annee'] ?? 0) > 0)
                <div class="tab-pane fade" id="abandons-annee" role="tabpanel" data-category="abandons_annee">
                    <div class="loading-spinner text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">Chargement des abandons année...</p>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>
                @endif

                <!-- Onglet Abandons École -->
                @if(($statistiques['abandons_ecole'] ?? 0) > 0)
                <div class="tab-pane fade" id="abandons-ecole" role="tabpanel" data-category="abandons_ecole">
                    <div class="loading-spinner text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">Chargement des abandons école...</p>
                    </div>
                    <div class="content-container" style="display: none;"></div>
                </div>
                @endif

                <!-- Onglet Erreurs -->
                @if(($statistiques['errors'] ?? 0) > 0)
                <div class="tab-pane fade" id="errors" role="tabpanel" data-category="errors">
                    <div class="loading-spinner text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">Chargement des non validés...</p>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Variables pour le système de lazy loading
let loadedTabs = {};
let currentPage = {};

$(document).ready(function() {
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
    
    // Trouver la catégorie avec le plus d'étudiants
    let maxCategory = 'passages';
    let maxCount = 0;
    for (const [category, count] of Object.entries(statistiques)) {
        if (count > maxCount) {
            maxCount = count;
            maxCategory = category;
        }
    }
    
    // Charger cette catégorie au démarrage
    if (maxCount > 0) {
        // Activer l'onglet correspondant
        $('a[data-toggle="tab"]').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
        $(`a[href="#${maxCategory}"]`).addClass('active');
        $(`#${maxCategory}`).addClass('show active');
        
        loadTabContent(maxCategory);
        console.log(`Chargement automatique de la catégorie "${maxCategory}" avec ${maxCount} étudiants`);
    }
    
    // Gérer les clics sur les onglets
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        const targetTab = $(e.target).attr('href').substring(1); // Enlever le #
        const category = $('#' + targetTab).data('category');
        
        if (category && !loadedTabs[category]) {
            loadTabContent(category);
        }
    });
});

// Fonction principale de chargement lazy
function loadTabContent(category, page = 1) {
    const tabPane = $(`[data-category="${category}"]`);
    const loadingSpinner = tabPane.find('.loading-spinner');
    const contentContainer = tabPane.find('.content-container');
    
    // Afficher le spinner si c'est la première page
    if (page === 1) {
        loadingSpinner.show();
        contentContainer.hide();
    }
    
    // Faire la requête AJAX
    $.ajax({
        url: `{{ route('esbtp.reinscription.load-category', '') }}/${category}`,
        method: 'GET',
        data: {
            page: page,
            per_page: 50
        },
        success: function(response) {
            if (page === 1) {
                // Première page : remplacer le contenu
                loadingSpinner.hide();
                
                // CORRECTION: Gérer les catégories vides
                if (response.total === 0) {
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
                    contentContainer.html(response.html);
                }
                
                contentContainer.show();
                loadedTabs[category] = true;
                currentPage[category] = 1;
            } else {
                // Pages suivantes : ajouter le contenu
                contentContainer.append(response.html);
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
            console.error('Erreur lors du chargement:', error);
            
            const errorHtml = `
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                    </div>
                    <h5 class="text-danger">Erreur de chargement</h5>
                    <p class="text-muted">Impossible de charger les données. 
                        <button class="btn-link" onclick="loadTabContent('${category}')">Réessayer</button>
                    </p>
                </div>
            `;
            
            if (page === 1) {
                loadingSpinner.hide();
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
    $('#yearChangeModal').modal('show');
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