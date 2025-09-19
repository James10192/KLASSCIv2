@extends('layouts.app')

@section('title', 'Situation Financière - ' . $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .preview-container {
        max-width: 900px;
        margin: 0 auto;
        background: white;
    }

    .preview-toolbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-lg);
        display: flex;
        justify-content: between;
        align-items: center;
        gap: var(--space-md);
    }

    .preview-actions {
        display: flex;
        gap: var(--space-sm);
        margin-left: auto;
    }

    .preview-content {
        border: 1px solid #ddd;
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        padding: 0;
        background: white;
        min-height: 800px;
    }

    /* Styles pour le document - similaires au PDF mais adaptés pour l'affichage HTML */
    .financial-document {
        font-family: Arial, sans-serif;
        font-size: 14px;
        line-height: 1.5;
        color: #333;
        padding: 30px;
    }

    .document-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #007bff;
        padding-bottom: 20px;
    }

    .document-title {
        font-size: 24px;
        font-weight: bold;
        color: #007bff;
        margin-bottom: 10px;
    }

    .document-subtitle {
        font-size: 16px;
        color: #666;
        margin-bottom: 15px;
    }

    .student-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 25px;
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
    }

    .info-section h4 {
        color: #007bff;
        margin-bottom: 10px;
        font-size: 16px;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 5px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .info-label {
        font-weight: 600;
        color: #495057;
    }

    .info-value {
        color: #212529;
    }

    .financial-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
    }

    .financial-table th,
    .financial-table td {
        padding: 10px;
        text-align: left;
        border: 1px solid #dee2e6;
    }

    .financial-table th {
        background-color: #007bff;
        color: white;
        font-weight: 600;
    }

    .financial-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .financial-table tbody tr:hover {
        background-color: #e3f2fd;
    }

    .amount {
        font-weight: 600;
        text-align: right;
    }

    .amount.positive {
        color: #28a745;
    }

    .amount.negative {
        color: #dc3545;
    }

    .amount.neutral {
        color: #6c757d;
    }

    .summary-card {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding: 5px 0;
    }

    .summary-row.total {
        border-top: 2px solid rgba(255,255,255,0.3);
        padding-top: 15px;
        margin-top: 15px;
        font-size: 18px;
        font-weight: bold;
    }

    .section-title {
        font-size: 18px;
        font-weight: bold;
        color: #007bff;
        margin: 25px 0 15px 0;
        padding-bottom: 8px;
        border-bottom: 2px solid #e9ecef;
    }

    .no-data {
        text-align: center;
        color: #6c757d;
        font-style: italic;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        margin: 10px 0;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-badge.paye {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.partiel {
        background: #fff3cd;
        color: #856404;
    }

    .status-badge.impaye {
        background: #f8d7da;
        color: #721c24;
    }

    .progress-bar {
        width: 100%;
        height: 20px;
        background: #e9ecef;
        border-radius: 10px;
        overflow: hidden;
        margin: 10px 0;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="preview-container">
            <!-- Toolbar de prévisualisation -->
            <div class="preview-toolbar">
                <div class="toolbar-info">
                    <i class="fas fa-file-invoice-dollar me-2"></i>
                    <strong>Prévisualisation - Situation Financière</strong>
                </div>
                <div class="preview-actions">
                    <a href="{{ route('esbtp.inscriptions.show', $inscription) }}" class="btn-acasi secondary">
                        <i class="fas fa-arrow-left"></i>Retour
                    </a>
                    <a href="{{ route('esbtp.inscriptions.situation-financiere.pdf', $inscription) }}" class="btn-acasi primary">
                        <i class="fas fa-download"></i>Télécharger PDF
                    </a>
                </div>
            </div>

            <!-- Contenu du document -->
            <div class="preview-content">
                <div class="financial-document">
                    <!-- En-tête du document -->
                    <div class="document-header">
                        <div class="document-title">SITUATION FINANCIÈRE</div>
                        <div class="document-subtitle">
                            {{ $inscription->etudiant->prenoms }} {{ $inscription->etudiant->nom }}
                        </div>
                        <div style="color: #666; font-size: 14px;">
                            Année Universitaire: {{ $inscription->anneeUniversitaire->name }} |
                            Classe: {{ $inscription->classe->nom ?? 'N/A' }} |
                            Généré le {{ now()->format('d/m/Y à H:i') }}
                        </div>
                    </div>

                    <!-- Informations générales -->
                    <div class="student-info">
                        <div class="info-section">
                            <h4><i class="fas fa-user"></i> Informations Étudiant</h4>
                            <div class="info-item">
                                <span class="info-label">Matricule:</span>
                                <span class="info-value">{{ $inscription->etudiant->matricule ?? 'N/A' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value">{{ $inscription->etudiant->email ?? 'N/A' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Téléphone:</span>
                                <span class="info-value">{{ $inscription->etudiant->telephone ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="info-section">
                            <h4><i class="fas fa-graduation-cap"></i> Informations Académiques</h4>
                            <div class="info-item">
                                <span class="info-label">Filière:</span>
                                <span class="info-value">{{ $inscription->filiere->nom ?? 'N/A' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Niveau:</span>
                                <span class="info-value">{{ $inscription->niveau->nom ?? 'N/A' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Statut:</span>
                                <span class="info-value">{{ ucfirst($inscription->affectation_status ?? 'affecté') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Synthèse financière -->
                    <div class="summary-card">
                        <h4 style="margin: 0 0 15px 0; color: white;"><i class="fas fa-chart-pie"></i> Synthèse Financière</h4>
                        <div class="summary-row">
                            <span>Total des frais attendus:</span>
                            <span>{{ number_format($statistiques['total_attendu'], 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div class="summary-row">
                            <span>Total payé:</span>
                            <span>{{ number_format($statistiques['total_paye'], 0, ',', ' ') }} FCFA</span>
                        </div>
                        @if($statistiques['total_reliquats'] > 0)
                        <div class="summary-row">
                            <span>Reliquats à payer:</span>
                            <span>{{ number_format($statistiques['total_reliquats'], 0, ',', ' ') }} FCFA</span>
                        </div>
                        @endif
                        <div class="summary-row total">
                            <span>Solde restant:</span>
                            <span class="{{ $statistiques['solde_restant'] > 0 ? 'text-warning' : 'text-success' }}">
                                {{ number_format($statistiques['solde_restant'], 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                        <div class="progress-bar" style="margin-top: 15px;">
                            <div class="progress-fill" style="width: {{ $statistiques['pourcentage_paye'] }}%">
                                {{ $statistiques['pourcentage_paye'] }}%
                            </div>
                        </div>
                    </div>

                    <!-- Détail des frais souscrits -->
                    <div class="section-title">
                        <i class="fas fa-list-alt"></i> Détail des Frais Souscrits
                    </div>
                    @if($fraisSouscrits->count() > 0)
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th>Catégorie de Frais</th>
                                <th>Montant Attendu</th>
                                <th>Montant Payé</th>
                                <th>Solde</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fraisSouscrits as $frais)
                            @php
                                $montantPaye = $inscription->paiements
                                    ->where('frais_category_id', $frais->frais_category_id)
                                    ->where('status', 'validé')
                                    ->sum('montant');
                                $solde = $frais->amount - $montantPaye;
                                $pourcentagePaye = $frais->amount > 0 ? ($montantPaye / $frais->amount) * 100 : 0;
                            @endphp
                            <tr>
                                <td>{{ $frais->fraisCategory->name ?? 'N/A' }}</td>
                                <td class="amount">{{ number_format($frais->amount, 0, ',', ' ') }} FCFA</td>
                                <td class="amount positive">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</td>
                                <td class="amount {{ $solde > 0 ? 'negative' : 'positive' }}">
                                    {{ number_format($solde, 0, ',', ' ') }} FCFA
                                </td>
                                <td>
                                    @if($solde <= 0)
                                        <span class="status-badge paye">Soldé</span>
                                    @elseif($montantPaye > 0)
                                        <span class="status-badge partiel">Partiel</span>
                                    @else
                                        <span class="status-badge impaye">Impayé</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i> Aucun frais souscrit pour cette inscription.
                    </div>
                    @endif

                    <!-- Reliquats (s'il y en a) -->
                    @if($reliquats->count() > 0)
                    <div class="section-title">
                        <i class="fas fa-history"></i> Reliquats d'Années Précédentes
                    </div>
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th>Année d'Origine</th>
                                <th>Catégorie de Frais</th>
                                <th>Montant Attendu</th>
                                <th>Montant Payé</th>
                                <th>Reliquat</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reliquats as $reliquat)
                            <tr>
                                <td>{{ $reliquat->inscriptionSource->anneeUniversitaire->name ?? 'N/A' }}</td>
                                <td>{{ $reliquat->fraisSubscription->fraisCategory->name ?? 'N/A' }}</td>
                                <td class="amount">{{ number_format($reliquat->montant_attendu, 0, ',', ' ') }} FCFA</td>
                                <td class="amount">{{ number_format($reliquat->montant_paye, 0, ',', ' ') }} FCFA</td>
                                <td class="amount negative">{{ number_format($reliquat->montant_reliquat, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    <span class="status-badge impaye">{{ ucfirst($reliquat->statut) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif

                    <!-- Historique des paiements -->
                    <div class="section-title">
                        <i class="fas fa-history"></i> Historique des Paiements
                    </div>
                    @if($inscription->paiements->count() > 0)
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Catégorie</th>
                                <th>Mode de Paiement</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Référence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inscription->paiements as $paiement)
                            <tr>
                                <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'N/A' }}</td>
                                <td>{{ $paiement->fraisCategory->name ?? 'N/A' }}</td>
                                <td>{{ ucfirst($paiement->mode_paiement ?? 'N/A') }}</td>
                                <td class="amount positive">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    @if($paiement->status === 'validé')
                                        <span class="status-badge paye">Validé</span>
                                    @elseif($paiement->status === 'en_attente')
                                        <span class="status-badge partiel">En attente</span>
                                    @else
                                        <span class="status-badge impaye">{{ ucfirst($paiement->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $paiement->numero_recu ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i> Aucun paiement enregistré pour cette inscription.
                    </div>
                    @endif

                    <!-- Pied de page -->
                    <div style="margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #dee2e6; padding-top: 20px;">
                        <p>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
                        <p>{{ setting('school_name', 'ESBTP-yAKRO') }} - Système de Gestion des Inscriptions</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection