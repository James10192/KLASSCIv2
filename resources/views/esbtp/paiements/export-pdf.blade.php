<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Export paiements - {{ $settings['school_name'] ?? 'Établissement' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            color: #1f2937;
            line-height: 1.4;
        }

        .page {
            padding: 12px;
        }

        .container {
            background: #ffffff;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .header-section {
            background: #0453cb;
            color: #ffffff;
            padding: 14px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 14px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .header-logo {
            max-height: 38px;
            margin-bottom: 8px;
        }

        .school-name {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .school-meta {
            font-size: 9px;
            opacity: 0.9;
        }

        .document-title {
            display: inline-block;
            margin-top: 8px;
            background: rgba(255, 255, 255, 0.15);
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.6px;
        }

        .kpi-grid {
            text-align: center;
            font-size: 0;
            margin-bottom: 12px;
        }

        .kpi-card {
            display: inline-block;
            vertical-align: top;
            width: 19%;
            min-width: 140px;
            margin: 0 0.5% 8px;
            font-size: 10px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        }

        .kpi-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 6px;
        }

        .kpi-value {
            font-size: 15px;
            font-weight: 700;
            color: #0453cb;
            margin-bottom: 4px;
        }

        .kpi-sub {
            font-size: 9px;
            color: #6b7280;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: #0f172a;
            margin: 16px 0 8px;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.05);
        }

        .payments-table thead th {
            background: #0453cb;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-size: 8px;
            padding: 6px 4px;
            text-align: center;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .payments-table thead th:last-child {
            border-right: none;
        }

        .payments-table tbody td {
            padding: 6px 4px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            font-size: 8.5px;
        }

        .payments-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .payments-table tbody tr:last-child td {
            border-bottom: none;
        }

        .payments-table td:nth-child(1),
        .payments-table th:nth-child(1) {
            width: 28px;
            text-align: center;
        }

        .payments-table td:nth-child(2),
        .payments-table th:nth-child(2) {
            width: 60px;
            text-align: center;
        }

        .payments-table td:nth-child(3),
        .payments-table th:nth-child(3) {
            width: 70px;
            font-family: 'Courier New', monospace;
        }

        .payments-table td:nth-child(10),
        .payments-table th:nth-child(10) {
            text-align: right;
            width: 70px;
        }

        .payments-table td:nth-child(12),
        .payments-table th:nth-child(12) {
            width: 70px;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 8px;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .status-valid {
            background: #16a34a;
        }

        .status-pending {
            background: #f59e0b;
        }

        .status-rejected {
            background: #dc2626;
        }

        .status-default {
            background: #6b7280;
        }

        .stats-section {
            margin-top: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
        }

        .stats-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .stats-item {
            flex: 1 1 200px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
        }

        .stats-item-title {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #475569;
            margin-bottom: 4px;
        }

        .stats-item-value {
            font-size: 12px;
            font-weight: 700;
            color: #0453cb;
        }

        .filters-section {
            margin-top: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            background: #ffffff;
        }

        .filters-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .filter-chip {
            flex: 1 1 220px;
            background: #f8fafc;
            border: 1px dashed #cbd5f5;
            border-radius: 8px;
            padding: 8px 10px;
        }

        .filter-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }

        .filter-value {
            font-size: 10px;
            color: #1f2937;
            font-weight: 600;
        }

        .empty-state {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            color: #92400e;
            margin-top: 12px;
        }

        .export-info {
            margin-top: 18px;
            text-align: center;
            font-size: 9px;
            color: #64748b;
        }

        .export-info strong {
            color: #1f2937;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .page {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
@php
    $formatMontant = function ($montant) {
        return number_format((float) $montant, 0, ',', ' ') . ' FCFA';
    };

    $formatDate = function ($date, $withTime = false) {
        if (!$date) {
            return 'N/A';
        }

        if ($date instanceof \Carbon\Carbon) {
            return $withTime ? $date->format('d/m/Y H:i') : $date->format('d/m/Y');
        }

        try {
            $parsed = \Carbon\Carbon::parse($date);
            return $withTime ? $parsed->format('d/m/Y H:i') : $parsed->format('d/m/Y');
        } catch (\Exception $e) {
            return $date;
        }
    };

    $totalPaiements = $stats['total'] ?? $paiements->count();
    $montantTotal = $stats['montant_total'] ?? $paiements->sum('montant');
    $valides = $stats['valides'] ?? 0;
    $montantValide = $stats['montant_valide'] ?? 0;
    $enAttente = $stats['en_attente'] ?? 0;
    $montantEnAttente = $stats['montant_en_attente'] ?? 0;
    $recoveryRate = $stats['recovery_rate'] ?? null;

    $filterItems = [];
    if (!empty($filters['search'])) {
        $filterItems[] = ['label' => 'Recherche', 'value' => $filters['search']];
    }
    if (!empty($filters['status'])) {
        $statusMap = [
            'en_attente' => 'En attente',
            'validé' => 'Validé',
            'valide' => 'Validé',
            'rejeté' => 'Rejeté',
            'rejete' => 'Rejeté',
        ];
        $filterItems[] = [
            'label' => 'Statut',
            'value' => $statusMap[$filters['status']] ?? ucfirst($filters['status']),
        ];
    }
    if (!empty($filters['date_debut'])) {
        $filterItems[] = ['label' => 'Date début', 'value' => $formatDate($filters['date_debut'])];
    }
    if (!empty($filters['date_fin'])) {
        $filterItems[] = ['label' => 'Date fin', 'value' => $formatDate($filters['date_fin'])];
    }
    if (empty($filterItems)) {
        $filterItems[] = ['label' => 'Filtres', 'value' => 'Aucun filtre spécifique appliqué'];
    }
@endphp

<div class="page">
    <div class="container">
        <div class="header-section">
            @if(($settings['show_logo'] ?? false) && !empty($settings['logo_base64']))
                <img src="{{ $settings['logo_base64'] }}" alt="Logo établissement" class="header-logo">
            @endif
            <div class="school-name">{{ $settings['school_name'] ?? 'Établissement' }}</div>
            <div class="school-meta">
                @if(!empty($settings['school_address'])){{ $settings['school_address'] }}@endif
                @if(!empty($settings['school_phone'])) &nbsp;•&nbsp; Tel: {{ $settings['school_phone'] }}@endif
                @if(!empty($settings['school_email'])) &nbsp;•&nbsp; Email: {{ $settings['school_email'] }}@endif
            </div>
            <div class="document-title">Tableau détaillé des paiements</div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Total paiements</div>
                <div class="kpi-value">{{ $totalPaiements }}</div>
                <div class="kpi-sub">Nombre d'opérations enregistrées</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Montant cumulé</div>
                <div class="kpi-value">{{ $formatMontant($montantTotal) }}</div>
                <div class="kpi-sub">Somme de tous les paiements</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Paiements validés</div>
                <div class="kpi-value">{{ $valides }}</div>
                <div class="kpi-sub">{{ $formatMontant($montantValide) }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">En attente</div>
                <div class="kpi-value">{{ $enAttente }}</div>
                <div class="kpi-sub">{{ $formatMontant($montantEnAttente) }}</div>
            </div>
            @if(!is_null($recoveryRate))
                <div class="kpi-card">
                    <div class="kpi-label">Taux de recouvrement</div>
                    <div class="kpi-value">{{ $recoveryRate }}%</div>
                    <div class="kpi-sub">Sur la période filtrée</div>
                </div>
            @endif
        </div>

        @if($paiements->count() > 0)
            <div class="section-title">Détails des paiements</div>
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Date</th>
                        <th>Matricule</th>
                        <th>Nom</th>
                        <th>Prénoms</th>
                        <th>Classe</th>
                        <th>Filière</th>
                        <th>Niveau</th>
                        <th>Catégorie</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Statut</th>
                        <th>N° reçu</th>
                        <th>Validé par</th>
                        <th>Date validation</th>
                        <th>Commentaire</th>
                        <th>Année univ.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paiements as $index => $paiement)
                        @php
                            $status = $paiement->status;
                            $statusLabel = 'En attente';
                            $statusClass = 'status-pending';

                            switch ($status) {
                                case 'validé':
                                case 'valide':
                                    $statusLabel = 'Validé';
                                    $statusClass = 'status-valid';
                                    break;
                                case 'rejeté':
                                case 'rejete':
                                    $statusLabel = 'Rejeté';
                                    $statusClass = 'status-rejected';
                                    break;
                                case 'en_attente':
                                case 'en attente':
                                case 'pending':
                                    $statusLabel = 'En attente';
                                    $statusClass = 'status-pending';
                                    break;
                                default:
                                    $statusLabel = $status ? ucfirst($status) : 'Non défini';
                                    $statusClass = 'status-default';
                                    break;
                            }
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $formatDate($paiement->date_paiement ?? null) }}</td>
                            <td>{{ $paiement->etudiant->matricule ?? 'N/A' }}</td>
                            <td>{{ $paiement->etudiant->nom ?? '' }}</td>
                            <td>{{ $paiement->etudiant->prenoms ?? '' }}</td>
                            <td>{{ optional(optional($paiement->inscription)->classe)->name ?? 'N/A' }}</td>
                            <td>{{ optional(optional($paiement->inscription)->filiere)->name ?? 'N/A' }}</td>
                            <td>{{ optional(optional($paiement->inscription)->niveauEtude)->name ?? 'N/A' }}</td>
                            <td>{{ $paiement->fraisCategory->name ?? ($paiement->categorie->nom ?? ($paiement->motif ?? 'N/A')) }}</td>
                            <td>{{ $formatMontant($paiement->montant ?? 0) }}</td>
                            <td>{{ $paiement->mode_paiement ?? 'N/A' }}</td>
                            <td><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>{{ $paiement->numero_recu ?? 'N/A' }}</td>
                            <td>{{ optional($paiement->validatedBy)->name ?? 'N/A' }}</td>
                            <td>{{ $formatDate($paiement->date_validation ?? null, true) }}</td>
                            <td>{{ $paiement->commentaire ?? '-' }}</td>
                            <td>{{ optional(optional($paiement->inscription)->anneeUniversitaire)->name ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                Aucun paiement correspondant aux critères sélectionnés n'a été trouvé.
            </div>
        @endif

        <div class="section-title">Statistiques détaillées</div>
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stats-item">
                    <div class="stats-item-title">Total des paiements</div>
                    <div class="stats-item-value">{{ $totalPaiements }}</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-title">Montant total encaissé</div>
                    <div class="stats-item-value">{{ $formatMontant($montantTotal) }}</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-title">Paiements validés</div>
                    <div class="stats-item-value">{{ $valides }} &nbsp;|&nbsp; {{ $formatMontant($montantValide) }}</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-title">Paiements en attente</div>
                    <div class="stats-item-value">{{ $enAttente }} &nbsp;|&nbsp; {{ $formatMontant($montantEnAttente) }}</div>
                </div>
                @if(!is_null($recoveryRate))
                    <div class="stats-item">
                        <div class="stats-item-title">Taux de recouvrement</div>
                        <div class="stats-item-value">{{ $recoveryRate }}%</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="section-title">Filtres appliqués</div>
        <div class="filters-section">
            <div class="filters-grid">
                @foreach($filterItems as $filter)
                    <div class="filter-chip">
                        <div class="filter-label">{{ $filter['label'] }}</div>
                        <div class="filter-value">{{ $filter['value'] ?: 'N/A' }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="export-info">
            <strong>Export généré automatiquement le {{ $formatDate($dateExport ?? now(), true) }}</strong><br>
            {{ $settings['school_name'] ?? 'Établissement' }} &nbsp;|&nbsp; Gestion intégrée des paiements
        </div>
    </div>
</div>
</body>
</html>
