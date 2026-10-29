<x-pdf-document :title="$reportTitle" :subtitle="$reportSubtitle" :filters="$reportFilters">
    <style>
        .eff-pdf-info { font-size: 8pt; margin: 9px 0; color: #475569; }
        .eff-pdf-table { table-layout: fixed; }
        .eff-pdf-table thead { display: table-header-group; }
        .eff-pdf-table tr { page-break-inside: avoid; }
        .eff-pdf-table .num { width: 8%; text-align: center; }
        .eff-pdf-table .classe { width: 33%; }
        .eff-pdf-table .filiere { width: 30%; }
        .eff-pdf-table .niveau { width: 17%; }
        .eff-pdf-table .effectif { width: 12%; text-align: right; }
        .eff-pdf-table td { overflow-wrap: break-word; word-wrap: break-word; }
    </style>

    <table class="pdf-kpi-table" aria-label="Totaux du rapport">
        <tr>
            <td class="pdf-kpi-cell"><div class="pdf-kpi-label">Classes correspondantes</div><div class="pdf-kpi-value">{{ $report['eligible_count'] }}</div></td>
            <td class="pdf-kpi-cell"><div class="pdf-kpi-label">Classes retenues</div><div class="pdf-kpi-value">{{ $report['retained_count'] }}</div></td>
            <td class="pdf-kpi-cell"><div class="pdf-kpi-label">Élèves uniques</div><div class="pdf-kpi-value">{{ $report['unique_students'] }}</div></td>
            <td class="pdf-kpi-cell"><div class="pdf-kpi-label">Effectifs cumulés</div><div class="pdf-kpi-value">{{ $report['sum_class_enrollments'] }}</div></td>
        </tr>
    </table>
    <div class="eff-pdf-info">Périmètre : toutes les classes retenues par les filtres, sans pagination de l'écran. Les élèves présents dans plusieurs classes ne sont comptés qu'une fois dans le total « Élèves uniques ».</div>

    <table class="pdf-detail-table eff-pdf-table">
        <thead><tr><th class="num">Rang</th><th class="classe">Classe</th><th class="filiere">Filière / mention</th><th class="niveau">Niveau</th><th class="effectif">Inscrits</th></tr></thead>
        <tbody>
        @forelse($report['rows'] as $classe)
            <tr><td class="num">{{ $loop->iteration }}</td><td>{{ $classe->name }}</td><td>{{ $classe->filiere ?: '—' }}</td><td>{{ $classe->niveau ?: '—' }}</td><td class="effectif">{{ $classe->effectif }}</td></tr>
        @empty
            <tr><td colspan="5" style="text-align:center">Aucune classe ne correspond aux filtres.</td></tr>
        @endforelse
        </tbody>
    </table>
</x-pdf-document>
