<x-pdf-document
    title="Liste des enseignants"
    subtitle="Annuaire des enseignants et affectations saisies sur les bulletins BTS"
    :filters="$reportFilters ?? []"
    orientation="landscape">
    {{-- Deux tableaux courts plutot qu'un seul de 9 colonnes : en A4 paysage,
         telephones et adresses email ne doivent jamais sortir de la page. --}}
    <table class="pdf-kpi-table" style="page-break-inside:avoid">
        <tr>
            <td class="pdf-kpi-cell">
                <div class="pdf-kpi-label">Enseignants distincts</div>
                <div class="pdf-kpi-value">{{ $total }}</div>
            </td>
            <td class="pdf-kpi-cell">
                <div class="pdf-kpi-label">Affectations bulletin (classe / matière / semestre)</div>
                <div class="pdf-kpi-value">{{ $affectationsTotal ?? count($affectations ?? []) }}</div>
            </td>
        </tr>
    </table>

    <p style="font-size:8px;color:#475569;margin:5px 0 10px">
        Les fiches enregistrées restent dans l'annuaire quelle que soit l'année.
        Les affectations manuelles proviennent de l'année universitaire sélectionnée ;
        elles ne constituent pas des comptes utilisateurs.
    </p>

    <table class="pdf-detail-table" style="width:100%;table-layout:fixed;border-collapse:collapse;">
        <thead>
            <tr>
                <th style="width:5%">N°</th>
                <th style="width:22%">Nom et prénoms</th>
                <th style="width:12%">Matricule</th>
                <th style="width:25%">Coordonnées</th>
                <th style="width:18%">Spécialisation / régime</th>
                <th style="width:18%">Origine</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr style="page-break-inside:avoid">
                <td>{{ $loop->iteration }}</td>
                <td style="word-wrap:break-word"><strong>{{ $row['nom'] }}</strong><div style="font-size:7px">{{ $row['statut'] }}</div></td>
                <td style="word-wrap:break-word">{{ $row['matricule'] }}</td>
                <td style="word-wrap:break-word">
                    {{ $row['telephone'] }}
                    <div style="font-size:7px;word-wrap:break-word">{{ $row['email'] }}</div>
                </td>
                <td style="word-wrap:break-word">{{ $row['specialisation'] }}
                    <div style="font-size:7px">{{ $row['regime'] }}</div>
                </td>
                <td style="word-wrap:break-word;font-size:8px">{{ $row['origine'] ?? 'Enregistré' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;padding:15px">Aucun enseignant pour ces filtres.</td></tr>
        @endforelse
        </tbody>
    </table>

    @if(($affectationsTotal ?? count($affectations ?? [])) > count($affectations ?? []))
        <div style="margin:12px 0;padding:11px 13px;border-left:3px solid #0453cb;background:#eff6ff;font-size:9px;color:#1e293b;">
            L'annuaire des enseignants ci-dessus est complet. Les
            {{ $affectationsTotal }} affectations par matière dépassent la limite
            d'impression du détail dans un seul PDF.
            Pour obtenir le détail intégral, sélectionnez une classe (et au besoin un semestre)
            sur la page Enseignants, puis relancez l'export PDF.
        </div>
    @else
    <h3 style="font-size:11px;margin:14px 0 7px;page-break-after:avoid">
        Affectations par classe et semestre — année universitaire sélectionnée
    </h3>
    <table class="pdf-detail-table" style="width:100%;table-layout:fixed;border-collapse:collapse">
        <thead>
            <tr>
                <th style="width:27%">Classe</th>
                <th style="width:10%">Période</th>
                <th style="width:33%">Matière</th>
                <th style="width:30%">Professeur renseigné</th>
            </tr>
        </thead>
        <tbody>
        @forelse(($affectations ?? []) as $affectation)
            <tr style="page-break-inside:avoid">
                <td style="word-wrap:break-word">{{ $affectation['classe'] }}</td>
                <td>{{ $affectation['semestre'] }}</td>
                <td style="word-wrap:break-word">{{ $affectation['matiere'] }}</td>
                <td style="word-wrap:break-word">{{ $affectation['nom'] }}</td>
            </tr>
        @empty
            <tr><td colspan="4" style="text-align:center;padding:12px">Aucune affectation saisie sur les bulletins de cette année.</td></tr>
        @endforelse
        </tbody>
    </table>
    @endif
</x-pdf-document>
