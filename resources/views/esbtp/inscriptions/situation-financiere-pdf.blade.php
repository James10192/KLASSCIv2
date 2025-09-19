<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Situation Financière - {{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }

        .header-logo {
            max-height: 60px;
            margin-bottom: 10px;
        }

        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }

        .school-info {
            font-size: 10px;
            color: #666;
            margin-bottom: 15px;
        }

        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 8px;
        }

        .document-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .document-info {
            font-size: 10px;
            color: #666;
        }

        .student-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .student-info-grid {
            display: table;
            width: 100%;
        }

        .student-info-row {
            display: table-row;
        }

        .student-info-cell {
            display: table-cell;
            padding: 3px 15px 3px 0;
            vertical-align: top;
            width: 50%;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            margin-right: 10px;
        }

        .info-value {
            color: #212529;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-box {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .summary-label {
            display: table-cell;
            width: 70%;
        }

        .summary-value {
            display: table-cell;
            text-align: right;
            font-weight: 600;
        }

        .summary-total {
            border-top: 1px solid rgba(255,255,255,0.3);
            margin-top: 10px;
            padding-top: 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .progress-bar {
            width: 100%;
            height: 15px;
            background: rgba(255,255,255,0.3);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: rgba(255,255,255,0.9);
            border-radius: 8px;
            text-align: center;
            line-height: 15px;
            font-size: 10px;
            color: #007bff;
            font-weight: bold;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }

        .table th,
        .table td {
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #dee2e6;
        }

        .table th {
            background-color: #007bff;
            color: white;
            font-weight: 600;
            font-size: 10px;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .amount {
            text-align: right;
            font-weight: 600;
        }

        .amount.positive {
            color: #28a745;
        }

        .amount.negative {
            color: #dc3545;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
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

        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin: 10px 0;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 10px;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }

        .page-break {
            page-break-before: always;
        }

        /* Éviter les coupures de page dans les éléments importants */
        .summary-box, .student-info {
            page-break-inside: avoid;
        }

        .section-title {
            page-break-after: avoid;
        }
    </style>
</head>
<body>
    <!-- En-tête du document -->
    <div class="header">
        @if($etablissement['logo'] && file_exists(storage_path('app/public/' . $etablissement['logo'])))
            <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}" class="header-logo" alt="Logo">
        @endif

        <div class="school-name">{{ $etablissement['nom'] }}</div>

        @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
        <div class="school-info">
            @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
            @if($etablissement['telephone'] && $etablissement['adresse']) | @endif
            @if($etablissement['telephone'])Tél: {{ $etablissement['telephone'] }}@endif
            @if($etablissement['email'] && ($etablissement['adresse'] || $etablissement['telephone'])) | @endif
            @if($etablissement['email'])Email: {{ $etablissement['email'] }}@endif
        </div>
        @endif

        <div class="document-title">SITUATION FINANCIÈRE</div>
        <div class="document-subtitle">
            {{ $inscription->etudiant->prenoms }} {{ $inscription->etudiant->nom }}
        </div>
        <div class="document-info">
            Année Universitaire: {{ $inscription->anneeUniversitaire->name }} |
            Classe: {{ $inscription->classe->nom ?? 'N/A' }} |
            Généré le {{ now()->format('d/m/Y à H:i') }}
        </div>
    </div>

    <!-- Informations générales -->
    <div class="student-info">
        <div class="student-info-grid">
            <div class="student-info-row">
                <div class="student-info-cell">
                    <span class="info-label">Matricule:</span>
                    <span class="info-value">{{ $inscription->etudiant->matricule ?? 'N/A' }}</span>
                </div>
                <div class="student-info-cell">
                    <span class="info-label">Filière:</span>
                    <span class="info-value">{{ $inscription->filiere->nom ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="student-info-row">
                <div class="student-info-cell">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $inscription->etudiant->email ?? 'N/A' }}</span>
                </div>
                <div class="student-info-cell">
                    <span class="info-label">Niveau:</span>
                    <span class="info-value">{{ $inscription->niveau->nom ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="student-info-row">
                <div class="student-info-cell">
                    <span class="info-label">Téléphone:</span>
                    <span class="info-value">{{ $inscription->etudiant->telephone ?? 'N/A' }}</span>
                </div>
                <div class="student-info-cell">
                    <span class="info-label">Statut:</span>
                    <span class="info-value">{{ ucfirst($inscription->affectation_status ?? 'affecté') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Synthèse financière -->
    <div class="summary-box">
        <div style="font-size: 14px; font-weight: bold; margin-bottom: 10px;">
            <i class="fas fa-chart-pie"></i> SYNTHÈSE FINANCIÈRE
        </div>
        <div class="summary-row">
            <div class="summary-label">Total des frais attendus:</div>
            <div class="summary-value">{{ number_format($statistiques['total_attendu'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total payé:</div>
            <div class="summary-value">{{ number_format($statistiques['total_paye'], 0, ',', ' ') }} FCFA</div>
        </div>
        @if($statistiques['total_reliquats'] > 0)
        <div class="summary-row">
            <div class="summary-label">Reliquats à payer:</div>
            <div class="summary-value">{{ number_format($statistiques['total_reliquats'], 0, ',', ' ') }} FCFA</div>
        </div>
        @endif
        <div class="summary-row summary-total">
            <div class="summary-label">SOLDE RESTANT:</div>
            <div class="summary-value">{{ number_format($statistiques['solde_restant'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $statistiques['pourcentage_paye'] }}%">
                {{ $statistiques['pourcentage_paye'] }}% payé
            </div>
        </div>
    </div>

    <!-- Détail des frais souscrits -->
    <div class="section-title">DÉTAIL DES FRAIS SOUSCRITS</div>
    @if($fraisSouscrits->count() > 0)
    <table class="table">
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
    <div class="no-data">Aucun frais souscrit pour cette inscription.</div>
    @endif

    <!-- Reliquats (s'il y en a) -->
    @if($reliquats->count() > 0)
    <div class="section-title">RELIQUATS D'ANNÉES PRÉCÉDENTES</div>
    <table class="table">
        <thead>
            <tr>
                <th>Année d'Origine</th>
                <th>Catégorie</th>
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
                <td><span class="status-badge impaye">{{ ucfirst($reliquat->statut) }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Historique des paiements -->
    <div class="section-title">HISTORIQUE DES PAIEMENTS</div>
    @if($inscription->paiements->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Catégorie</th>
                <th>Mode</th>
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
    <div class="no-data">Aucun paiement enregistré pour cette inscription.</div>
    @endif

    <!-- Pied de page -->
    <div class="footer">
        <p><strong>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</strong></p>
        <p>{{ $etablissement['nom'] }} - Système de Gestion des Inscriptions</p>
        @if($statistiques['solde_restant'] > 0)
            <p style="color: #dc3545; font-weight: bold;">
                ⚠️ Solde restant à payer: {{ number_format($statistiques['solde_restant'], 0, ',', ' ') }} FCFA
            </p>
        @else
            <p style="color: #28a745; font-weight: bold;">
                ✅ Situation financière à jour - Tous les frais sont soldés
            </p>
        @endif
    </div>
</body>
</html>