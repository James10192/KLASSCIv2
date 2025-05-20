<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport d'Émargement</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            margin-top: 0;
        }
        .stats-container {
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .stat-box {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        .stat-box h3 {
            margin: 0;
            color: #333;
            font-size: 14px;
        }
        .stat-box p {
            margin: 5px 0 0;
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            color: white;
        }
        .status-present {
            background-color: #28a745;
        }
        .status-late {
            background-color: #ffc107;
            color: #000;
        }
        .status-absent {
            background-color: #dc3545;
        }
        .status-validated {
            background-color: #28a745;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .status-rejected {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport d'Émargement des Enseignants</h1>
        <p>Période : {{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</p>
    </div>

    <div class="stats-container">
        <h2>Statistiques Globales</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <h3>Total des émargements</h3>
                <p>{{ $stats['total'] }}</p>
            </div>
            <div class="stat-box">
                <h3>Taux de présence</h3>
                <p>{{ $stats['attendance_rate'] }}%</p>
                <small>
                    Présents: {{ $stats['present'] }} |
                    En retard: {{ $stats['late'] }} |
                    Absents: {{ $stats['absent'] }}
                </small>
            </div>
            <div class="stat-box">
                <h3>Taux de validation</h3>
                <p>{{ $stats['validation_rate'] }}%</p>
                <small>
                    Validés: {{ $stats['validated'] }} |
                    En attente: {{ $stats['pending'] }} |
                    Rejetés: {{ $stats['rejected'] }}
                </small>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Enseignant</th>
                <th>Matière</th>
                <th>Status</th>
                <th>Heure d'arrivée</th>
                <th>Code</th>
                <th>Validation</th>
                <th>Validé par</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $attendance->enseignant->nom_complet }}</td>
                    <td>{{ $attendance->matiere->nom }}</td>
                    <td>
                        <span class="status-badge status-{{ $attendance->status }}">
                            {{ ucfirst($attendance->status) }}
                        </span>
                    </td>
                    <td>{{ $attendance->marked_at ? $attendance->marked_at->format('H:i') : 'N/A' }}</td>
                    <td>{{ $attendance->code }}</td>
                    <td>
                        <span class="status-badge status-{{ $attendance->validation_status }}">
                            {{ ucfirst($attendance->validation_status) }}
                        </span>
                    </td>
                    <td>{{ $attendance->validator ? $attendance->validator->name : 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Rapport généré le {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
