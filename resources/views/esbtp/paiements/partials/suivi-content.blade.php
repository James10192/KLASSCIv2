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

            {{-- Statistiques de la catégorie --}}
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

            {{-- Navigation par onglets --}}
            <div class="student-tabs-container">
                <ul class="nav nav-tabs" id="myTab_{{ $detailsCategorie['category']->id }}" role="tablist" style="border: none; display: flex; gap: var(--space-md);">
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link active" data-bs-toggle="tab" href="#non_payes_{{ $detailsCategorie['category']->id }}" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Aucun paiement ({{ $detailsCategorie['etudiants_non_payes']->count() }})
                        </a>
                    </li>
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" data-bs-toggle="tab" href="#en_retard_{{ $detailsCategorie['category']->id }}" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-clock me-2"></i>
                            Paiements partiels ({{ $detailsCategorie['etudiants_en_retard']->count() }})
                        </a>
                    </li>
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" data-bs-toggle="tab" href="#a_jour_{{ $detailsCategorie['category']->id }}" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-check-circle me-2"></i>
                            À jour ({{ $detailsCategorie['etudiants_a_jour']->count() }})
                        </a>
                    </li>
                </ul>

                {{-- Contenu des onglets --}}
                <div class="tab-content">
                    {{-- Onglet Aucun paiement --}}
                    <div class="tab-pane fade show active" id="non_payes_{{ $detailsCategorie['category']->id }}" role="tabpanel">
                        @if($detailsCategorie['etudiants_non_payes']->count() > 0)
                            @include('esbtp.paiements.partials.liste-etudiants', [
                                'etudiants' => $detailsCategorie['etudiants_non_payes'],
                                'statut' => 'non_payes',
                                'category' => $detailsCategorie['category']
                            ])
                        @else
                            <div style="padding: 40px; text-align: center; color: #9ca3af;">
                                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 16px; color: #10b981;"></i>
                                <p style="font-size: 16px; font-weight: 500;">Aucun étudiant sans paiement</p>
                            </div>
                        @endif
                    </div>

                    {{-- Onglet Paiements partiels --}}
                    <div class="tab-pane fade" id="en_retard_{{ $detailsCategorie['category']->id }}" role="tabpanel">
                        @if($detailsCategorie['etudiants_en_retard']->count() > 0)
                            @include('esbtp.paiements.partials.liste-etudiants', [
                                'etudiants' => $detailsCategorie['etudiants_en_retard'],
                                'statut' => 'en_retard',
                                'category' => $detailsCategorie['category']
                            ])
                        @else
                            <div style="padding: 40px; text-align: center; color: #9ca3af;">
                                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 16px; color: #10b981;"></i>
                                <p style="font-size: 16px; font-weight: 500;">Aucun étudiant avec paiement partiel</p>
                            </div>
                        @endif
                    </div>

                    {{-- Onglet À jour --}}
                    <div class="tab-pane fade" id="a_jour_{{ $detailsCategorie['category']->id }}" role="tabpanel">
                        @if($detailsCategorie['etudiants_a_jour']->count() > 0)
                            @include('esbtp.paiements.partials.liste-etudiants', [
                                'etudiants' => $detailsCategorie['etudiants_a_jour'],
                                'statut' => 'a_jour',
                                'category' => $detailsCategorie['category']
                            ])
                        @else
                            <div style="padding: 40px; text-align: center; color: #9ca3af;">
                                <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 16px; color: #f59e0b;"></i>
                                <p style="font-size: 16px; font-weight: 500;">Aucun étudiant à jour</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endif
