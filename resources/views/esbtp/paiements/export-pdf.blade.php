<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Export Paiements</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.4;
        }

        .page-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #0453cb;
        }

        .school-name {
            font-size: 16pt;
            font-weight: bold;
            color: #0453cb;
            margin-bottom: 5px;
        }

        .school-info {
            font-size: 8pt;
            color: #666;
        }

        h1 {
            font-size: 14pt;
            color: #0453cb;
            margin: 15px 0 10px;
            text-align: center;
        }

        .info-section {
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 3px solid #0453cb;
        }

        .info-row {
            display: flex;
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            min-width: 100px;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 8pt;
        }

        th {
            background-color: #0453cb;
            color: white;
            font-weight: bold;
            padding: 8px 4px;
            text-align: left;
            border: 1px solid #0453cb;
        }

        td {
            padding: 6px 4px;
            border: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .stats-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .stat-item {
            padding: 8px;
            background-color: white;
            border-left: 3px solid #0453cb;
        }

        .stat-label {
            font-size: 8pt;
            color: #666;
            margin-bottom: 3px;
        }

        .stat-value {
            font-size: 11pt;
            font-weight: bold;
            color: #0453cb;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 7pt;
            color: #666;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    {{-- En-tête --}}
    <div class="page-header">
        <div class="school-name">{{ $settings['school_name'] ?? 'KLASSCI' }}</div>
        <div class="school-info">
            {{ $settings['school_address'] ?? '' }}<br>
            Tél: {{ $settings['school_phone'] ?? '' }} | Email: {{ $settings['school_email'] ?? '' }}
        </div>
    </div>

    <h1>Liste des Paiements</h1>

    {{-- Informations sur les filtres appliqués --}}
    @if(!empty(array_filter($filters)))
    <div class="info-section">
        <strong style="display: block; margin-bottom: 8px;">Filtres appliqués:</strong>
        @if(!empty($filters['search']))
        <div class="info-row">
            <span class="info-label">Recherche:</span>
            <span class="info-value">{{ $filters['search'] }}</span>
        </div>
        @endif
        @if(!empty($filters['status']))
        <div class="info-row">
            <span class="info-label">Statut:</span>
            <span class="info-value">
                @switch($filters['status'])
                    @case('en_attente') En attente @break
                    @case('validé') Validé @break
                    @case('rejeté') Rejeté @break
                    @default {{ $filters['status'] }}
                @endswitch
            </span>
        </div>
        @endif
        @if(!empty($filters['date_debut']))
        <div class="info-row">
            <span class="info-label">Du:</span>
            <span class="info-value">{{ \Carbon\Carbon::parse($filters['date_debut'])->format('d/m/Y') }}</span>
        </div>
        @endif
        @if(!empty($filters['date_fin']))
        <div class="info-row">
            <span class="info-label">Au:</span>
            <span class="info-value">{{ \Carbon\Carbon::parse($filters['date_fin'])->format('d/m/Y') }}</span>
        </div>
        @endif
    </div>
    @endif

    {{-- Tableau des paiements --}}
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">N°</th>
                <th style="width: 8%;">Date</th>
                <th style="width: 10%;">Matricule</th>
                <th style="width: 15%;">Étudiant</th>
                <th style="width: 12%;">Classe</th>
                <th style="width: 12%;">Catégorie</th>
                <th style="width: 10%;" class="text-right">Montant</th>
                <th style="width: 10%;">Mode</th>
                <th style="width: 8%;">Statut</th>
                <th style="width: 12%;">N° Reçu</th>
            </tr>
        </thead>
        <tbody>
            @forelse($paiements as $index => $paiement)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $paiement->etudiant ? $paiement->etudiant->matricule : 'N/A' }}</td>
                <td>
                    @if($paiement->etudiant)
                        {{ $paiement->etudiant->nom }} {{ $paiement->etudiant->prenoms }}
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    @if($paiement->inscription && $paiement->inscription->classe)
                        {{ $paiement->inscription->classe->nom }}
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    @if($paiement->fraisCategory)
                        {{ $paiement->fraisCategory->name }}
                    @elseif($paiement->categorie)
                        {{ $paiement->categorie->nom }}
                    @else
                        {{ $paiement->motif ?? 'N/A' }}
                    @endif
                </td>
                <td class="text-right">{{ number_format($paiement->montant, 0, ',', ' ') }}</td>
                <td>{{ $paiement->mode_paiement ?? 'N/A' }}</td>
                <td>
                    @switch($paiement->status)
                        @case('validé')
                            <span class="badge badge-success">Validé</span>
                            @break
                        @case('en_attente')
                            <span class="badge badge-warning">En attente</span>
                            @break
                        @case('rejeté')
                            <span class="badge badge-danger">Rejeté</span>
                            @break
                        @default
                            {{ ucfirst($paiement->status) }}
                    @endswitch
                </td>
                <td>{{ $paiement->numero_recu ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center">Aucun paiement trouvé.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Statistiques --}}
    <div class="stats-section">
        <strong style="display: block; margin-bottom: 10px; font-size: 11pt;">Résumé des paiements</strong>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-label">Nombre total</div>
                <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Montant total</div>
                <div class="stat-value">{{ number_format($stats['montant_total'] ?? 0, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Paiements validés</div>
                <div class="stat-value">{{ $stats['valides'] ?? 0 }} ({{ number_format($stats['montant_valide'] ?? 0, 0, ',', ' ') }} FCFA)</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Paiements en attente</div>
                <div class="stat-value">{{ $stats['en_attente'] ?? 0 }} ({{ number_format($stats['montant_en_attente'] ?? 0, 0, ',', ' ') }} FCFA)</div>
            </div>
            @if(isset($stats['recovery_rate']))
            <div class="stat-item">
                <div class="stat-label">Taux de recouvrement</div>
                <div class="stat-value">{{ $stats['recovery_rate'] }}%</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Pied de page --}}
    <div class="footer">
        <p>Document généré le {{ $dateExport->format('d/m/Y à H:i') }}</p>
        <p>{{ $settings['school_name'] ?? 'KLASSCI' }} - Système de gestion scolaire</p>
    </div>
</body>
</html>
