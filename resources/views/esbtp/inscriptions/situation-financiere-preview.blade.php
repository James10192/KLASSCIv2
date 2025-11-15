@extends('layouts.app')

@section('title', 'Situation Financière - ' . $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .info-group {
        margin-bottom: 1rem;
    }

    .info-label {
        font-weight: 600;
        color: #374151;
        margin-right: 0.5rem;
    }

    .info-value {
        color: #6b7280;
    }

    .financial-table {
        width: 100%;
    }

    .section-title {
        background: #f3f4f6;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        color: #374151;
        margin: 1.5rem 0 1rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-badge.soldé { background: #d1fae5; color: #065f46; }
    .status-badge.partiel { background: #fef3c7; color: #92400e; }
    .status-badge.impayé { background: #fee2e2; color: #991b1b; }
    .status-badge.reliquat { background: #fef3c7; color: #d97706; }

    @media print {
        .dashboard-header, .btn-acasi, .no-print {
            display: none !important;
        }

        .main-content {
            padding: 0 !important;
        }

        .main-card {
            box-shadow: none !important;
            border: 1px solid #e5e7eb !important;
            margin-bottom: 20px !important;
        }

        .kpi-grid {
            display: none !important;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chart-line me-2"></i>Situation Financière</h1>
                <p class="header-subtitle">{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }} - {{ $inscription->etudiant->matricule }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.situation-financiere.pdf', $inscription->id) }}" class="btn-acasi danger">
                    <i class="fas fa-file-pdf"></i>Télécharger PDF
                </a>
                <button onclick="window.print()" class="btn-acasi primary">
                    <i class="fas fa-print"></i>Imprimer
                </button>
                @php
                    // Récupérer l'URL de la page précédente (referrer)
                    $returnUrl = request()->headers->get('referer')
                        ? request()->headers->get('referer')
                        : route('esbtp.inscriptions.show', $inscription->id);

                    // Sécurité : Vérifier que le referrer est sur le même domaine
                    $parsedUrl = parse_url($returnUrl);
                    $appUrl = parse_url(config('app.url'));
                    if (isset($parsedUrl['host']) && $parsedUrl['host'] !== ($appUrl['host'] ?? '')) {
                        $returnUrl = route('esbtp.inscriptions.show', $inscription->id); // Fallback sécurisé
                    }
                @endphp
                <a href="{{ $returnUrl }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Attendu</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2rem; font-weight: bold;">{{ number_format($statistiques['total_attendu'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-money-bill-wave"></i>
                    Frais année + reliquats
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Payé</div>
                <div class="kpi-value" style="color: var(--success); font-size: 2rem; font-weight: bold;">{{ number_format($statistiques['total_paye'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-check-circle"></i>
                    Tous paiements validés
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Solde</div>
                <div class="kpi-value" style="color: {{ $statistiques['solde_restant'] > 0 ? 'var(--danger)' : 'var(--success)' }}; font-size: 2rem; font-weight: bold;">{{ number_format($statistiques['solde_restant'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-balance-scale"></i>
                    {{ $statistiques['solde_restant'] > 0 ? 'Restant à payer' : 'Soldé' }}
                </div>
            </div>

            @if($statistiques['total_reliquats'] > 0)
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Reliquats</div>
                <div class="kpi-value" style="color: var(--warning); font-size: 2rem; font-weight: bold;">{{ number_format($statistiques['total_reliquats'], 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-history"></i>
                    Années précédentes
                </div>
            </div>
            @endif
        </div>

        <!-- En-tête de l'établissement -->
        <div class="main-card">
            <div class="main-card-header" style="background: #007bff; color: white; border-radius: 15px 15px 0 0;">
                <div style="text-align: center; padding: 1rem;">
                    @if($etablissement['logo'] && file_exists(storage_path('app/public/' . $etablissement['logo'])))
                        <div style="margin-bottom: 15px;">
                            <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}"
                                 style="max-height: 60px; max-width: 150px; filter: brightness(0) invert(1);" alt="Logo">
                        </div>
                    @endif

                    <h2 style="margin: 0 0 8px 0; font-size: 1.8rem; font-weight: 700;">{{ $etablissement['nom'] ?? 'ESBTP-yAKRO' }}</h2>

                    @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 15px;">
                        @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
                        @if($etablissement['telephone'] && $etablissement['adresse']) | @endif
                        @if($etablissement['telephone'])Tel: {{ $etablissement['telephone'] }}@endif
                        @if($etablissement['email'] && ($etablissement['adresse'] || $etablissement['telephone'])) | @endif
                        @if($etablissement['email'])Email: {{ $etablissement['email'] }}@endif
                    </div>
                    @endif

                    <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; backdrop-filter: blur(10px);">
                        <h3 style="margin: 0 0 10px 0; font-size: 1.4rem; font-weight: 600;">BULLETIN DE SITUATION FINANCIÈRE</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 0.95rem;">
                            <div><strong>Année:</strong> <span style="background: rgba(255,255,255,0.3); padding: 3px 8px; border-radius: 12px;">{{ $inscription->anneeUniversitaire->name ?? 'N/A' }}</span></div>
                            <div><strong>Date:</strong> <span style="background: rgba(255,255,255,0.3); padding: 3px 8px; border-radius: 12px;">{{ now()->format('d/m/Y') }}</span></div>
                            <div><strong>Classe:</strong> <span style="background: rgba(255,255,255,0.3); padding: 3px 8px; border-radius: 12px;">{{ $inscription->classe->name ?? 'N/A' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations de l'étudiant -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-user"></i>
                    Informations de l'étudiant
                </div>
                <div class="main-card-subtitle">Données personnelles et académiques</div>
            </div>

            <div class="main-card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        @if($inscription->etudiant->photo_url)
                            <img src="{{ $inscription->etudiant->photo_url }}" alt="Photo de profil" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width: 150px; height: 150px;">
                                <i class="fas fa-user fa-5x text-secondary"></i>
                            </div>
                        @endif
                        <h5 class="mt-3">{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</h5>
                        <p class="text-muted">
                            Matricule: <strong>{{ $inscription->etudiant->matricule }}</strong>
                        </p>
                    </div>

                    <div class="col-md-4">
                        <h6 style="color: #007bff; margin-bottom: 15px;"><i class="fas fa-info-circle me-2"></i>Informations personnelles</h6>
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Genre:</strong></div>
                            <div class="col-sm-7">{{ $inscription->etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Date de naissance:</strong></div>
                            <div class="col-sm-7">{{ $inscription->etudiant->date_naissance ? \Carbon\Carbon::parse($inscription->etudiant->date_naissance)->format('d/m/Y') : 'Non renseigné' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Lieu de naissance:</strong></div>
                            <div class="col-sm-7">{{ $inscription->etudiant->lieu_naissance ?? 'Non renseigné' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Téléphone:</strong></div>
                            <div class="col-sm-7">{{ $inscription->etudiant->telephone ?? 'Non renseigné' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Email:</strong></div>
                            <div class="col-sm-7">{{ $inscription->etudiant->email ?? 'Non renseigné' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Adresse:</strong></div>
                            <div class="col-sm-7">{{ $inscription->etudiant->adresse ?? 'Non renseigné' }}</div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <h6 style="color: #007bff; margin-bottom: 15px;"><i class="fas fa-graduation-cap me-2"></i>Informations académiques</h6>
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Filière:</strong></div>
                            <div class="col-sm-7">{{ $inscription->classe->filiere->name ?? 'Non renseigné' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Niveau:</strong></div>
                            <div class="col-sm-7">{{ $inscription->classe->niveau->name ?? 'Non renseigné' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Classe:</strong></div>
                            <div class="col-sm-7">{{ $inscription->classe->name ?? 'Non renseigné' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Statut:</strong></div>
                            <div class="col-sm-7">
                                <span class="badge {{ $inscription->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($inscription->status) }}
                                </span>
                            </div>
                        </div>

                        @if($inscription->etudiant->parents && $inscription->etudiant->parents->count() > 0)
                        <h6 style="color: #007bff; margin-bottom: 15px; margin-top: 20px;"><i class="fas fa-users me-2"></i>Contact parent/tuteur</h6>
                        @php $parent = $inscription->etudiant->parents->first(); @endphp
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Nom:</strong></div>
                            <div class="col-sm-7">{{ $parent->nom ?? 'Non renseigné' }} {{ $parent->prenoms ?? '' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5"><strong>Téléphone:</strong></div>
                            <div class="col-sm-7">{{ $parent->telephone ?? 'Non renseigné' }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Frais Souscrits - Année Courante -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Détail des Frais Souscrits - Année {{ $inscription->anneeUniversitaire->name }}
                </div>
                <div class="main-card-subtitle">Frais de l'année universitaire en cours</div>
            </div>
            <div class="main-card-body">
                @if($fraisSouscrits->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover financial-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Catégorie de Frais</th>
                                    <th width="100" class="text-center">Type</th>
                                    <th width="150" class="text-end">Montant Attendu</th>
                                    <th width="150" class="text-end">Montant Payé</th>
                                    <th width="150" class="text-end">Solde</th>
                                    <th width="100" class="text-center">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fraisSouscrits as $frais)
                                @php
                                    $montantPaye = $inscription->paiements
                                        ->where('frais_category_id', $frais->frais_category_id)
                                        ->where('status', 'validé')
                                        ->filter(function($paiement) {
                                            return $paiement->type_paiement != 'reliquat' || is_null($paiement->type_paiement);
                                        })
                                        ->sum('montant');
                                    $solde = $frais->amount - $montantPaye;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-tag me-2 text-primary"></i>
                                            <strong>{{ $frais->fraisCategory->name ?? 'Non renseigné' }}</strong>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($frais->fraisCategory->is_mandatory)
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-circle me-1"></i>Obligatoire
                                            </span>
                                        @else
                                            <span class="badge bg-info">
                                                <i class="fas fa-star me-1"></i>Optionnel
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold">{{ number_format($frais->amount, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-success fw-bold">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold {{ $solde > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($solde, 0, ',', ' ') }} FCFA
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($solde <= 0)
                                            <span class="status-badge soldé">Soldé</span>
                                        @elseif($montantPaye > 0)
                                            <span class="status-badge partiel">Partiel</span>
                                        @else
                                            <span class="status-badge impayé">Impayé</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach

                                {{-- Intégrer les reliquats comme des lignes de frais --}}
                                @if($reliquatsEntrants->count() > 0)
                                    @foreach($reliquatsEntrants as $reliquat)
                                        @if($reliquat->solde_restant > 0)
                                            <tr class="table-warning">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-history me-2 text-warning"></i>
                                                        <div>
                                                            <strong>{{ $reliquat->fraisSubscription->fraisCategory->name ?? 'Non renseigné' }}</strong>
                                                            <br><small class="text-muted">Reliquat {{ $reliquat->inscriptionSource->anneeUniversitaire->name ?? 'N/A' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    @if($reliquat->fraisSubscription->fraisCategory->is_mandatory)
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-circle me-1"></i>Obligatoire
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-star me-1"></i>Optionnel
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold">{{ number_format($reliquat->montant_reliquat, 0, ',', ' ') }} FCFA</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="text-success fw-bold">{{ number_format($reliquat->montant_regle, 0, ',', ' ') }} FCFA</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold text-warning">{{ number_format($reliquat->solde_restant, 0, ',', ' ') }} FCFA</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="status-badge reliquat">Reliquat</span>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x mb-3"></i>
                        <h5>Aucun frais souscrit</h5>
                        <p class="text-muted">Aucun frais souscrit pour cette inscription.</p>
                    </div>
                @endif
            </div>
        </div>


        <!-- Historique des Paiements -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-receipt"></i>
                    Historique des Paiements
                </div>
                <div class="main-card-subtitle">{{ $inscription->paiements->where('status', 'validé')->count() }} paiement(s) validé(s)</div>
            </div>
            <div class="main-card-body">
                @if($inscription->paiements->where('status', 'validé')->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Catégorie</th>
                                    <th>Mode de Paiement</th>
                                    <th width="120" class="text-end">Montant</th>
                                    <th width="100" class="text-center">Statut</th>
                                    <th>Référence</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inscription->paiements->where('status', 'validé')->sortByDesc('date_paiement') as $paiement)
                                <tr>
                                    <td>{{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : 'Non renseigné' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($paiement->type_paiement == 'reliquat')
                                                <i class="fas fa-history me-2 text-warning"></i>
                                                {{ $paiement->fraisCategory->name ?? 'Non renseigné' }}
                                                <span class="badge bg-warning ms-2">Reliquat</span>
                                            @else
                                                <i class="fas fa-money-bill me-2 text-success"></i>
                                                {{ $paiement->fraisCategory->name ?? 'Non renseigné' }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $paiement->mode_paiement ?? 'Non renseigné' }}</td>
                                    <td class="text-end">
                                        <span class="fw-bold text-success">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ strtoupper($paiement->status) }}</span>
                                    </td>
                                    <td>
                                        <code class="text-muted">{{ $paiement->numero_recu ?? '-' }}</code>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-receipt text-muted fa-2x mb-3"></i>
                        <h5>Aucun paiement</h5>
                        <p class="text-muted">Aucun paiement validé pour cette inscription.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}
            </small>
        </div>
    </div>
</div>
@endsection