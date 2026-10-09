<x-pdf-document
    title="Liste des enseignants"
    subtitle="Annuaire du corps enseignant"
    :filters="$reportFilters ?? []"
    orientation="landscape">
    <table class="pdf-kpi-table" style="page-break-inside:avoid">
        <tr>
            <td class="pdf-kpi-cell">
                <div class="pdf-kpi-label">Enseignants concernés</div>
                <div class="pdf-kpi-value">{{ $total }}</div>
            </td>
        </tr>
    </table>

    <table class="pdf-detail-table" style="width:100%; table-layout:fixed;">
        <thead>
            <tr>
                <th style="width:4%">N°</th>
                <th style="width:11%">Matricule</th>
                <th style="width:19%">Nom et prénoms</th>
                <th style="width:15%">Téléphone</th>
                <th style="width:19%">Email</th>
                <th style="width:17%">Spécialisation</th>
                <th style="width:8%">Régime</th>
                <th style="width:7%">Statut</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr style="page-break-inside:avoid;">
                <td>{{ $loop->iteration }}</td>
                <td style="overflow-wrap:break-word;">{{ $row['matricule'] }}</td>
                <td style="overflow-wrap:break-word;"><strong>{{ $row['nom'] }}</strong></td>
                <td>{{ $row['telephone'] }}</td>
                <td style="overflow-wrap:break-word; font-size:8px;">{{ $row['email'] }}</td>
                <td style="overflow-wrap:break-word;">{{ $row['specialisation'] }}</td>
                <td>{{ $row['regime'] }}</td>
                <td>{{ $row['statut'] }}</td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center; padding:20px;">Aucun enseignant pour ces filtres.</td></tr>
        @endforelse
        </tbody>
    </table>
</x-pdf-document>
