<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>PV de surveillance</title>
    <style>
        @page { margin: 24px 26px; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 11px; line-height: 1.35; }
        .top { border-bottom: 2px solid #0453cb; padding-bottom: 10px; margin-bottom: 14px; }
        .eyebrow { color: #0453cb; text-transform: uppercase; font-size: 10px; letter-spacing: .08em; font-weight: 700; }
        h1 { margin: 3px 0 4px; font-size: 20px; color: #0f172a; }
        .muted { color: #64748b; }
        .grid { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .grid td { width: 50%; vertical-align: top; padding: 7px 8px; border: 1px solid #dbe3ef; }
        .label { display: block; color: #64748b; font-size: 9px; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px; }
        .value { font-weight: 700; color: #111827; }
        table.list { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.list th { background: #eef4ff; color: #0f3d8a; border: 1px solid #cbdaf5; padding: 6px; font-size: 9px; text-transform: uppercase; }
        table.list td { border: 1px solid #dbe3ef; padding: 6px; vertical-align: middle; }
        .section { margin-top: 14px; }
        .section-title { font-weight: 800; color: #0453cb; margin-bottom: 6px; font-size: 12px; }
        .signature-box { height: 46px; }
        .incident-box { height: 78px; border: 1px solid #dbe3ef; padding: 8px; color: #64748b; }
        .footer { margin-top: 14px; color: #64748b; font-size: 9px; text-align: right; }
    </style>
</head>
<body>
    <div class="top">
        <div class="eyebrow">ExamOps · Document de salle</div>
        <h1>PV de surveillance et feuille d'émargement</h1>
        <div class="muted">Généré le {{ $generated_at->format('d/m/Y H:i') }}</div>
    </div>

    <table class="grid">
        <tr>
            <td><span class="label">Examen</span><span class="value">{{ $examen->titre }}</span></td>
            <td><span class="label">Convocation</span><span class="value">{{ $examen->numero_convocation ?? 'À générer' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Matière</span><span class="value">{{ $examen->matiere?->name ?? 'Non renseignée' }}</span></td>
            <td><span class="label">UE</span><span class="value">{{ $examen->uniteEnseignement?->name ?? 'Non renseignée' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Date et horaire</span><span class="value">{{ $examen->date_debut?->format('d/m/Y H:i') }} à {{ $examen->date_fin?->format('H:i') }}</span></td>
            <td><span class="label">Salle</span><span class="value">{{ $examen->salle ?? 'À définir' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Durée</span><span class="value">{{ $examen->duree_minutes ?? 'À calculer' }} minutes</span></td>
            <td><span class="label">Anonymat</span><span class="value">{{ $examen->is_anonymous ? 'Copies anonymisées' : 'Copies nominatives' }}</span></td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Équipe de surveillance</div>
        <table class="list">
            <thead>
                <tr><th>Nom</th><th>Rôle</th><th>Confirmation</th><th>Signature</th></tr>
            </thead>
            <tbody>
                @forelse($examen->surveillants as $surveillant)
                    <tr>
                        <td>{{ $surveillant->user?->name ?? 'Utilisateur supprimé' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $surveillant->role)) }}</td>
                        <td>{{ $surveillant->confirmed ? 'Confirmé' : 'À confirmer' }}</td>
                        <td class="signature-box"></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">Aucun surveillant assigné.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Étudiants attendus</div>
        <table class="list">
            <thead>
                <tr><th style="width: 18px;">#</th><th>Matricule</th><th>Nom et prénoms</th><th>Classe</th><th>Présence</th><th>Signature</th></tr>
            </thead>
            <tbody>
                @forelse($students as $index => $student)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $student->matricule ?? 'N/A' }}</td>
                        <td>{{ trim(($student->nom ?? '').' '.($student->prenoms ?? '')) }}</td>
                        <td>{{ $student->classe_name ?? 'Classe non renseignée' }}</td>
                        <td>Présent · Absent</td>
                        <td class="signature-box"></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Aucun étudiant actif trouvé pour les classes ciblées.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Incidents, retards, exclusions, observations</div>
        <div class="incident-box">Renseigner ici tout incident constaté pendant l'épreuve.</div>
    </div>

    <div class="footer">Document opérationnel ExamOps · {{ $examen->id }}</div>
</body>
</html>