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
                <div class="kpi-value color-success">{{ count($resultats['passages']) }}</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>Admis niveau supérieur</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Rattrapages</div>
                <div class="kpi-value color-warning">{{ count($resultats['rattrapages']) }}</div>
                <div class="kpi-trend">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Session de rattrapage</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Redoublements</div>
                <div class="kpi-value color-danger">{{ count($resultats['redoublements']) }}</div>
                <div class="kpi-trend negative">
                    <i class="fas fa-redo"></i>
                    <span>Reprise de l'année</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Abandons Année</div>
                <div class="kpi-value color-danger">{{ count($resultats['abandons_annee'] ?? []) }}</div>
                <div class="kpi-trend negative">
                    <i class="fas fa-user-slash"></i>
                    <span>Année abandonnée</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Abandons École</div>
                <div class="kpi-value color-neutral">{{ count($resultats['abandons_ecole'] ?? []) }}</div>
                <div class="kpi-trend">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Quittent l'établissement</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Validés</div>
                <div class="kpi-value color-success">{{ count($resultats['valides'] ?? []) }}</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-check-double"></i>
                    <span>Réinscriptions confirmées</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Non validés</div>
                <div class="kpi-value color-neutral">{{ count($resultats['errors'] ?? []) }}</div>
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
                        <a class="nav-link active" id="passages-tab" data-toggle="tab" href="#passages" role="tab" 
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); background-color: rgba(16, 185, 129, 0.1); color: var(--success); font-weight: 600;">
                            <i class="fas fa-arrow-up"></i> Passages ({{ count($resultats['passages']) }})
                        </a>
                    </li>
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="rattrapages-tab" data-toggle="tab" href="#rattrapages" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-exclamation-triangle"></i> Rattrapages ({{ count($resultats['rattrapages']) }})
                        </a>
                    </li>
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="redoublements-tab" data-toggle="tab" href="#redoublements" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-redo"></i> Redoublements ({{ count($resultats['redoublements']) }})
                        </a>
                    </li>
                    @if(count($resultats['valides'] ?? []) > 0)
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="valides-tab" data-toggle="tab" href="#valides" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-check-double"></i> Validés ({{ count($resultats['valides'] ?? []) }})
                        </a>
                    </li>
                    @endif
                    @if(count($resultats['abandons_annee'] ?? []) > 0)
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="abandons-annee-tab" data-toggle="tab" href="#abandons-annee" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-user-slash"></i> Abandons Année ({{ count($resultats['abandons_annee'] ?? []) }})
                        </a>
                    </li>
                    @endif
                    @if(count($resultats['abandons_ecole'] ?? []) > 0)
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="abandons-ecole-tab" data-toggle="tab" href="#abandons-ecole" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-graduation-cap"></i> Abandons École ({{ count($resultats['abandons_ecole'] ?? []) }})
                        </a>
                    </li>
                    @endif
                    @if(count($resultats['errors']) > 0)
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="errors-tab" data-toggle="tab" href="#errors" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-user-clock"></i> Non validés ({{ count($resultats['errors']) }})
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            <div class="p-lg">
                <div class="tab-content" id="myTabContent">
                <!-- Onglet Passages -->
                <div class="tab-pane fade show active" id="passages" role="tabpanel">
                    @include('esbtp.reinscription.partials.liste-etudiants', ['etudiants' => $resultats['passages'], 'type' => 'passage'])
                </div>

                <!-- Onglet Rattrapages -->
                <div class="tab-pane fade" id="rattrapages" role="tabpanel">
                    @include('esbtp.reinscription.partials.liste-etudiants', ['etudiants' => $resultats['rattrapages'], 'type' => 'rattrapage'])
                </div>

                <!-- Onglet Redoublements -->
                <div class="tab-pane fade" id="redoublements" role="tabpanel">
                    @include('esbtp.reinscription.partials.liste-etudiants', ['etudiants' => $resultats['redoublements'], 'type' => 'redoublement'])
                </div>

                <!-- Onglet Validés -->
                @if(count($resultats['valides'] ?? []) > 0)
                <div class="tab-pane fade" id="valides" role="tabpanel">
                    <div class="section-title mb-md">
                        <i class="fas fa-check-double me-2"></i>Réinscriptions validées et confirmées
                    </div>
                    <div class="table-moderne">
                        <table>
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Décision</th>
                                    <th>Validé le</th>
                                    <th>Validé par</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultats['valides'] as $analyse)
                                <tr>
                                    <td>
                                        <img src="{{ $analyse['etudiant']->photo_url ?? asset('images/default-avatar.png') }}" 
                                             alt="Photo" 
                                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;">{{ $analyse['etudiant']->prenoms }} {{ $analyse['etudiant']->nom }}</div>
                                        <div style="font-size: var(--text-small); color: var(--text-secondary);">{{ $analyse['etudiant']->matricule }}</div>
                                    </td>
                                    <td>
                                        <span class="table-badge primary">{{ $analyse['classe']->nom ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="table-badge success">{{ $analyse['decision'] }}</span>
                                    </td>
                                    <td>
                                        <span style="color: var(--text-secondary); font-size: var(--text-small);">
                                            {{ $analyse['validated_at'] ? \Carbon\Carbon::parse($analyse['validated_at'])->format('d/m/Y H:i') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: var(--text-secondary); font-size: var(--text-small);">
                                            {{ $analyse['validated_by']->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-acasi small warning" 
                                                    onclick="marquerAbandonModal({{ $analyse['etudiant']->id }})" 
                                                    title="Marquer abandon">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Onglet Abandons Année Scolaire -->
                @if(count($resultats['abandons_annee'] ?? []) > 0)
                <div class="tab-pane fade" id="abandons-annee" role="tabpanel">
                    <div class="section-title mb-md">
                        <i class="fas fa-user-slash me-2"></i>Étudiants ayant abandonné leur année scolaire
                    </div>
                    <div class="table-moderne">
                        <table>
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Date abandon</th>
                                    <th>Motif</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultats['abandons_annee'] as $etudiant)
                                <tr>
                                    <td>
                                        <img src="{{ $etudiant->photo_url ?? asset('images/default-avatar.png') }}" 
                                             alt="Photo" 
                                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;">{{ $etudiant->prenoms }} {{ $etudiant->nom }}</div>
                                        <div style="font-size: var(--text-small); color: var(--text-secondary);">{{ $etudiant->matricule }}</div>
                                    </td>
                                    <td>
                                        @if($etudiant->inscription)
                                            <span class="table-badge secondary">{{ $etudiant->inscription->classe->nom ?? 'N/A' }}</span>
                                        @else
                                            <span class="table-badge neutral">Non assigné</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span style="color: var(--text-secondary); font-size: var(--text-small);">
                                            {{ $etudiant->date_abandon ? \Carbon\Carbon::parse($etudiant->date_abandon)->format('d/m/Y') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: var(--text-secondary); font-size: var(--text-small);">
                                            {{ $etudiant->motif_abandon ?? 'Non précisé' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="table-badge danger">Abandon année</span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-acasi small success" 
                                                    onclick="restaurerEtudiant({{ $etudiant->id }})"
                                                    title="Restaurer l'étudiant">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Onglet Abandons École -->
                @if(count($resultats['abandons_ecole'] ?? []) > 0)
                <div class="tab-pane fade" id="abandons-ecole" role="tabpanel">
                    <div class="section-title mb-md">
                        <i class="fas fa-graduation-cap me-2"></i>Étudiants quittant l'établissement après réussite
                    </div>
                    <div class="table-moderne">
                        <table>
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Date abandon</th>
                                    <th>Motif</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultats['abandons_ecole'] as $etudiant)
                                <tr>
                                    <td>
                                        <img src="{{ $etudiant->photo_url ?? asset('images/default-avatar.png') }}" 
                                             alt="Photo" 
                                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;">{{ $etudiant->prenoms }} {{ $etudiant->nom }}</div>
                                        <div style="font-size: var(--text-small); color: var(--text-secondary);">{{ $etudiant->matricule }}</div>
                                    </td>
                                    <td>
                                        @if($etudiant->inscription)
                                            <span class="table-badge secondary">{{ $etudiant->inscription->classe->nom ?? 'N/A' }}</span>
                                        @else
                                            <span class="table-badge neutral">Non assigné</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span style="color: var(--text-secondary); font-size: var(--text-small);">
                                            {{ $etudiant->date_abandon ? \Carbon\Carbon::parse($etudiant->date_abandon)->format('d/m/Y') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: var(--text-secondary); font-size: var(--text-small);">
                                            {{ $etudiant->motif_abandon ?? 'Non précisé' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="table-badge neutral">Quitte école</span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-acasi small success" 
                                                    onclick="restaurerEtudiant({{ $etudiant->id }})"
                                                    title="Restaurer l'étudiant">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Onglet Erreurs -->
                @if(count($resultats['errors']) > 0)
                <div class="tab-pane fade" id="errors" role="tabpanel">
                    <div class="table-moderne">
                        <table>
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Erreur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultats['errors'] as $error)
                                <tr>
                                    <td>{{ $error['etudiant']->prenoms }} {{ $error['etudiant']->nom }}</td>
                                    <td>{{ $error['etudiant']->classe->nom ?? 'N/A' }}</td>
                                    <td><span class="table-badge danger">{{ $error['error'] }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

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
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2024-2025)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les données se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte toute l'application.
                </p>
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

<!-- Modal pour choisir le type d'abandon -->
<div class="modal fade" id="abandonModal" tabindex="-1" role="dialog" aria-labelledby="abandonModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="abandonModalLabel">Marquer l'étudiant comme ayant abandonné</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="abandonForm">
                    <div class="form-group">
                        <label for="abandonType"><strong>Type d'abandon *</strong></label>
                        <select class="form-control" id="abandonType" name="abandon_type" required>
                            <option value="">-- Choisir le type d'abandon --</option>
                            <option value="annee_scolaire">Abandon de l'année scolaire</option>
                            <option value="ecole">Abandon de l'école</option>
                        </select>
                        <small class="form-text text-muted">
                            <strong>Année scolaire :</strong> L'étudiant n'a pas soldé ses frais, ne vient plus en cours<br>
                            <strong>École :</strong> L'étudiant a fini son année mais quitte l'établissement
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="motifAbandon">Motif (optionnel)</label>
                        <textarea class="form-control" id="motifAbandon" name="motif_abandon" rows="3" 
                                  placeholder="Précisez le motif de l'abandon..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#abandonModal').modal('hide');">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmerAbandon">Confirmer l'abandon</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Gestion des onglets
$(document).ready(function() {
    // Gérer les clics sur les onglets
    $('a[data-toggle="tab"]').on('click', function (e) {
        e.preventDefault();
        
        // Enlever l'état actif de tous les onglets
        $('a[data-toggle="tab"]').removeClass('active').css({
            'background-color': 'transparent',
            'color': 'var(--text-secondary)'
        });
        
        // Ajouter l'état actif à l'onglet cliqué
        $(this).addClass('active').css({
            'background-color': 'rgba(30, 58, 138, 0.1)',
            'color': 'var(--primary)',
            'font-weight': '600'
        });
        
        // Cacher tous les contenus d'onglets
        $('.tab-pane').removeClass('show active');
        
        // Afficher le contenu de l'onglet cliqué
        const target = $(this).attr('href');
        $(target).addClass('show active');
    });
});

function exportResults() {
    const anneeAcademique = document.getElementById('annee_academique').value;
    
    fetch(`{{ route('esbtp.reinscription.export') }}?annee_academique=${anneeAcademique}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Erreur: ' + data.error);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'export');
        });
}

// Variable globale pour stocker l'ID de l'étudiant en cours de traitement
let currentEtudiantId = null;

function marquerAbandonModal(etudiantId) {
    currentEtudiantId = etudiantId;
    $('#abandonModal').modal('show');
}

// Gestionnaire pour le bouton de confirmation dans la modal
$(document).ready(function() {
    $('#confirmerAbandon').on('click', function() {
        const abandonType = $('#abandonType').val();
        const motif = $('#motifAbandon').val();
        
        if (!abandonType) {
            alert('Veuillez choisir un type d\'abandon');
            return;
        }
        
        if (!currentEtudiantId) {
            alert('Erreur: ID étudiant manquant');
            return;
        }
        
        // Envoyer la requête AJAX
        fetch(`{{ url('esbtp/reinscription') }}/${currentEtudiantId}/abandon`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                motif_abandon: motif,
                abandon_type: abandonType
            })
        })
        .then(response => response.json())
        .then(data => {
            $('#abandonModal').modal('hide');
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'enregistrement de l\'abandon');
        });
    });
    
    // Gérer la fermeture de la modal avec le bouton X
    $('.close[data-dismiss="modal"]').on('click', function() {
        $('#abandonModal').modal('hide');
    });
    
    // Gérer la fermeture avec le bouton Annuler
    $('button[data-dismiss="modal"]').on('click', function() {
        $('#abandonModal').modal('hide');
    });
    
    // Réinitialiser le formulaire quand la modal se ferme
    $('#abandonModal').on('hidden.bs.modal', function () {
        $('#abandonForm')[0].reset();
        currentEtudiantId = null;
    });
});

function restaurerEtudiant(etudiantId) {
    if (confirm('Êtes-vous sûr de vouloir restaurer cet étudiant ? Il repassera au statut actif.')) {
        fetch(`{{ url('esbtp/reinscription') }}/${etudiantId}/restaurer`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); // Recharger la page pour voir les changements
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la restauration');
        });
    }
}

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


@endsection