<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rapport de Présence - {{ $classe->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #007bff;
        }

        .header h1 {
            color: #007bff;
            font-size: 22pt;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 11pt;
        }

        .info-section {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .info-section table {
            width: 100%;
        }

        .info-section td {
            padding: 5px;
        }

        .info-label {
            font-weight: bold;
            color: #374151;
            width: 40%;
        }

        .info-value {
            color: #6b7280;
        }

        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .stats-row {
            display: table-row;
        }

        .stat-cell {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .stat-label {
            font-weight: 600;
            color: #374151;
            font-size: 9pt;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 18pt;
            font-weight: bold;
        }

        .stat-value.present { color: #10b981; }
        .stat-value.absent { color: #ef4444; }
        .stat-value.late { color: #f59e0b; }
        .stat-value.excuse { color: #3b82f6; }

        .table-container {
            margin-top: 20px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.data-table thead {
            background: #f3f4f6;
        }

        table.data-table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border: 1px solid #e5e7eb;
            font-size: 9pt;
            text-transform: uppercase;
        }

        table.data-table td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            font-size: 9pt;
        }

        table.data-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: 500;
            text-align: center;
            min-width: 25px;
        }

        .badge.present { background: #d1fae5; color: #065f46; }
        .badge.absent { background: #fee2e2; color: #991b1b; }
        .badge.late { background: #fef3c7; color: #92400e; }
        .badge.excuse { background: #dbeafe; color: #1e40af; }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
        }

        .progress-fill {
            height: 100%;
            background: #10b981;
        }

        .progress-fill.medium { background: #f59e0b; }
        .progress-fill.low { background: #ef4444; }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8pt;
            color: #6b7280;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Rapport de Présence</h1>
        <p>{{ $classe->name }}</p>
        <p style="font-size: 9pt;">Du {{ \Carbon\Carbon::parse($validatedData['date_debut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($validatedData['date_fin'])->format('d/m/Y') }}</p>
    </div>

    <!-- Informations générales -->
    <div class="info-section">
        <table>
            <tr>
                <td class="info-label">Classe :</td>
                <td class="info-value">{{ $classe->name }}</td>
                <td class="info-label">Nombre d'étudiants :</td>
                <td class="info-value">{{ count($statistiques) }}</td>
            </tr>
            <tr>
                <td class="info-label">Période :</td>
                <td class="info-value">Du {{ \Carbon\Carbon::parse($validatedData['date_debut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($validatedData['date_fin'])->format('d/m/Y') }}</td>
                <td class="info-label">Total d'enregistrements :</td>
                <td class="info-value">{{ collect($statistiques)->sum('present') + collect($statistiques)->sum('absent') + collect($statistiques)->sum('retard') + collect($statistiques)->sum('excuse') }}</td>
            </tr>
        </table>
    </div>

    <!-- Statistiques globales -->
    <div class="stats-grid">
        <div class="stats-row">
            <div class="stat-cell">
                <div class="stat-label">PRÉSENCES</div>
                <div class="stat-value present">{{ collect($statistiques)->sum('present') }}</div>
            </div>
            <div class="stat-cell">
                <div class="stat-label">ABSENCES</div>
                <div class="stat-value absent">{{ collect($statistiques)->sum('absent') }}</div>
            </div>
            <div class="stat-cell">
                <div class="stat-label">RETARDS</div>
                <div class="stat-value late">{{ collect($statistiques)->sum('retard') }}</div>
            </div>
            <div class="stat-cell">
                <div class="stat-label">EXCUSÉS</div>
                <div class="stat-value excuse">{{ collect($statistiques)->sum('excuse') }}</div>
            </div>
        </div>
    </div>

    <!-- Tableau détaillé -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Étudiant</th>
                    <th class="text-center" style="width: 12%;">Présences</th>
                    <th class="text-center" style="width: 12%;">Absences</th>
                    <th class="text-center" style="width: 12%;">Retards</th>
                    <th class="text-center" style="width: 12%;">Excusés</th>
                    <th class="text-center" style="width: 17%;">Taux</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statistiques as $stat)
                    <tr>
                        <td>
                            <strong>{{ $stat['etudiant']->nom_complet }}</strong><br>
                            @if($stat['etudiant']->matricule)
                                <small style="color: #6b7280;">{{ $stat['etudiant']->matricule }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge present">{{ $stat['present'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge absent">{{ $stat['absent'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge late">{{ $stat['retard'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge excuse">{{ $stat['excuse'] }}</span>
                        </td>
                        <td class="text-center">
                            <strong>{{ $stat['taux_presence'] }}%</strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 20px; color: #6b7280;">
                            Aucune donnée disponible pour cette période
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>Rapport de présence - {{ $classe->name }}</p>
    </div>
</body>
</html>
