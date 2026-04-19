{{-- Répartition des étudiants - Style moderne avec barre pleine largeur --}}
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

{{-- Statistiques par catégorie avec style visuel original --}}
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
            <div class="category-card category-card-ajax" data-category-id="{{ $category->id }}" style="cursor: pointer;">
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

                    {{-- Indicateur du type de frais --}}
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

                    <div style="display: flex; justify-content-between; font-size: var(--text-small);">
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

{{-- Détails d'une catégorie spécifique --}}
@if($detailsCategorie)
<div class="details-section">
    <div class="card-moderne mb-lg">
        <div class="p-lg">
            <div class="section-title mb-lg">
                <i class="{{ $detailsCategorie['category']->icon ?? 'fas fa-money-bill' }} me-2"></i>
                Détails : {{ $detailsCategorie['category']->name }}
            </div>

            {{-- Statistiques de la catégorie — premium cards sc-stat* --}}
            @php
                $tauxCatRecouvrement = $detailsCategorie['montant_total_attendu'] > 0
                    ? round(($detailsCategorie['montant_total_recu'] / $detailsCategorie['montant_total_attendu']) * 100, 1)
                    : 0;
            @endphp
            <div class="sc-stats-grid">
                <div class="sc-stat is-success">
                    <div class="sc-stat-head">
                        <i class="fas fa-check-circle"></i>
                        <span>Étudiants à jour</span>
                    </div>
                    <div class="sc-stat-value">{{ $detailsCategorie['etudiants_a_jour']->count() }}</div>
                    <div class="sc-stat-label">Paiement complet</div>
                </div>
                <div class="sc-stat is-warning">
                    <div class="sc-stat-head">
                        <i class="fas fa-clock"></i>
                        <span>Paiements partiels</span>
                    </div>
                    <div class="sc-stat-value">{{ $detailsCategorie['etudiants_en_retard']->count() }}</div>
                    <div class="sc-stat-label">Solde restant</div>
                </div>
                <div class="sc-stat is-danger">
                    <div class="sc-stat-head">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Aucun paiement</span>
                    </div>
                    <div class="sc-stat-value">{{ $detailsCategorie['etudiants_non_payes']->count() }}</div>
                    <div class="sc-stat-label">À relancer</div>
                </div>
                <div class="sc-stat is-primary">
                    <div class="sc-stat-head">
                        <i class="fas fa-chart-line"></i>
                        <span>Taux de recouvrement</span>
                    </div>
                    <div class="sc-stat-value">{{ $tauxCatRecouvrement }}%</div>
                    <div class="sc-stat-label">{{ number_format($detailsCategorie['montant_total_recu'], 0, ',', ' ') }} / {{ number_format($detailsCategorie['montant_total_attendu'], 0, ',', ' ') }} FCFA</div>
                </div>
            </div>

            {{-- Barre de recherche intelligente --}}
            <div class="suivi-search-container" style="margin-bottom: 16px; position: relative;">
                <div style="display: flex; align-items: center; gap: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 16px;">
                    <i class="fas fa-search" style="color: #9ca3af; font-size: 14px;"></i>
                    <input type="text"
                           id="suivi-search-input"
                           class="suivi-search-field"
                           placeholder="Rechercher par nom, prénom ou matricule..."
                           data-category-id="{{ $detailsCategorie['category']->id }}"
                           style="border: none; background: transparent; outline: none; flex: 1; font-size: 14px; color: #1f2937; font-weight: 400;"
                    >
                    <span id="suivi-search-count" style="font-size: 12px; color: #6b7280; white-space: nowrap; display: none;">
                        <i class="fas fa-filter me-1"></i><span id="suivi-search-count-value">0</span> résultat(s)
                    </span>
                    <button type="button" id="suivi-search-clear" style="display: none; border: none; background: #e5e7eb; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; color: #6b7280; font-size: 12px; padding: 0; line-height: 24px; text-align: center;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            {{-- Navigation par onglets avec lazy loading --}}
            <div class="student-tabs-container">
                <ul class="nav nav-tabs students-tabs" id="myTab_{{ $detailsCategorie['category']->id }}" role="tablist" style="border: none; display: flex; gap: var(--space-md);">
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link active"
                           data-bs-toggle="tab"
                           href="#non_payes_{{ $detailsCategorie['category']->id }}"
                           role="tab"
                           data-statut="non_payes"
                           data-category-id="{{ $detailsCategorie['category']->id }}"
                           data-count="{{ $detailsCategorie['etudiants_non_payes']->count() }}"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Aucun paiement (<span class="student-count">{{ $detailsCategorie['etudiants_non_payes']->count() }}</span>)
                        </a>
                    </li>
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link"
                           data-bs-toggle="tab"
                           href="#en_retard_{{ $detailsCategorie['category']->id }}"
                           role="tab"
                           data-statut="en_retard"
                           data-category-id="{{ $detailsCategorie['category']->id }}"
                           data-count="{{ $detailsCategorie['etudiants_en_retard']->count() }}"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-clock me-2"></i>
                            Paiements partiels (<span class="student-count">{{ $detailsCategorie['etudiants_en_retard']->count() }}</span>)
                        </a>
                    </li>
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link"
                           data-bs-toggle="tab"
                           href="#a_jour_{{ $detailsCategorie['category']->id }}"
                           role="tab"
                           data-statut="a_jour"
                           data-category-id="{{ $detailsCategorie['category']->id }}"
                           data-count="{{ $detailsCategorie['etudiants_a_jour']->count() }}"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-check-circle me-2"></i>
                            À jour (<span class="student-count">{{ $detailsCategorie['etudiants_a_jour']->count() }}</span>)
                        </a>
                    </li>
                </ul>

                {{-- Contenu des onglets avec lazy loading --}}
                <div class="tab-content">
                    {{-- Onglet Aucun paiement --}}
                    <div class="tab-pane fade show active" id="non_payes_{{ $detailsCategorie['category']->id }}" role="tabpanel" data-loaded="false">
                        <div class="students-list-container" id="students-list-non_payes_{{ $detailsCategorie['category']->id }}">
                            <div class="text-center" style="padding: 40px 0;">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p style="margin-top: 16px; color: #6b7280; font-weight: 500;">Chargement des étudiants...</p>
                            </div>
                        </div>
                        {{-- Barre d'export --}}
                        <div class="suivi-export-bar d-flex justify-content-between align-items-center"
                             style="padding: 10px 16px; margin-top: 8px; background: #fff8f8; border: 1px solid #fecaca; border-radius: 8px;">
                            <div style="font-size: 13px; color: #991b1b; font-weight: 500;">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <strong>{{ $detailsCategorie['etudiants_non_payes']->count() }}</strong> étudiant(s) sans paiement
                            </div>
                            <div class="d-flex gap-2">
                                <span style="font-size: 12px; color: #6b7280; margin-right: 4px; align-self: center;">Exporter :</span>
                                <button class="btn btn-sm btn-suivi-export"
                                        data-statut="non_payes"
                                        data-category-id="{{ $detailsCategorie['category']->id }}"
                                        style="background: #10b981; color: #fff; border: none; border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 500; cursor: pointer;">
                                    <i class="fas fa-file-excel me-1"></i>Excel
                                </button>
                                <button class="btn btn-sm btn-suivi-export-pdf"
                                        data-statut="non_payes"
                                        data-category-id="{{ $detailsCategorie['category']->id }}"
                                        style="background: #dc2626; color: #fff; border: none; border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 500; cursor: pointer;">
                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Onglet Paiements partiels --}}
                    <div class="tab-pane fade" id="en_retard_{{ $detailsCategorie['category']->id }}" role="tabpanel" data-loaded="false">
                        <div class="students-list-container" id="students-list-en_retard_{{ $detailsCategorie['category']->id }}">
                            <div class="text-center" style="padding: 40px 0;">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p style="margin-top: 16px; color: #6b7280; font-weight: 500;">Chargement des étudiants...</p>
                            </div>
                        </div>
                        {{-- Barre d'export --}}
                        <div class="suivi-export-bar d-flex justify-content-between align-items-center"
                             style="padding: 10px 16px; margin-top: 8px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px;">
                            <div style="font-size: 13px; color: #92400e; font-weight: 500;">
                                <i class="fas fa-clock me-1"></i>
                                <strong>{{ $detailsCategorie['etudiants_en_retard']->count() }}</strong> étudiant(s) en paiement partiel
                            </div>
                            <div class="d-flex gap-2">
                                <span style="font-size: 12px; color: #6b7280; margin-right: 4px; align-self: center;">Exporter :</span>
                                <button class="btn btn-sm btn-suivi-export"
                                        data-statut="en_retard"
                                        data-category-id="{{ $detailsCategorie['category']->id }}"
                                        style="background: #10b981; color: #fff; border: none; border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 500; cursor: pointer;">
                                    <i class="fas fa-file-excel me-1"></i>Excel
                                </button>
                                <button class="btn btn-sm btn-suivi-export-pdf"
                                        data-statut="en_retard"
                                        data-category-id="{{ $detailsCategorie['category']->id }}"
                                        style="background: #d97706; color: #fff; border: none; border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 500; cursor: pointer;">
                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Onglet À jour --}}
                    <div class="tab-pane fade" id="a_jour_{{ $detailsCategorie['category']->id }}" role="tabpanel" data-loaded="false">
                        <div class="students-list-container" id="students-list-a_jour_{{ $detailsCategorie['category']->id }}">
                            <div class="text-center" style="padding: 40px 0;">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p style="margin-top: 16px; color: #6b7280; font-weight: 500;">Chargement des étudiants...</p>
                            </div>
                        </div>
                        {{-- Barre d'export --}}
                        <div class="suivi-export-bar d-flex justify-content-between align-items-center"
                             style="padding: 10px 16px; margin-top: 8px; background: #f0fdf4; border: 1px solid #6ee7b7; border-radius: 8px;">
                            <div style="font-size: 13px; color: #065f46; font-weight: 500;">
                                <i class="fas fa-check-circle me-1"></i>
                                <strong>{{ $detailsCategorie['etudiants_a_jour']->count() }}</strong> étudiant(s) à jour
                            </div>
                            <div class="d-flex gap-2">
                                <span style="font-size: 12px; color: #6b7280; margin-right: 4px; align-self: center;">Exporter :</span>
                                <button class="btn btn-sm btn-suivi-export"
                                        data-statut="a_jour"
                                        data-category-id="{{ $detailsCategorie['category']->id }}"
                                        style="background: #10b981; color: #fff; border: none; border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 500; cursor: pointer;">
                                    <i class="fas fa-file-excel me-1"></i>Excel
                                </button>
                                <button class="btn btn-sm btn-suivi-export-pdf"
                                        data-statut="a_jour"
                                        data-category-id="{{ $detailsCategorie['category']->id }}"
                                        style="background: #0453cb; color: #fff; border: none; border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 500; cursor: pointer;">
                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endif
