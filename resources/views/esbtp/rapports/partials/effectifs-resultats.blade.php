<section class="eff-results" aria-label="Résultats du rapport des effectifs">
    <div class="eff-active" aria-label="Filtres actifs">
        <strong>Filtres actifs :</strong>
        @foreach($activeFilters as $label => $value)
            <span class="eff-filter-chip"><span>{{ $label }}</span> : {{ $value }}</span>
        @endforeach
    </div>
    <div class="eff-summary" role="status">
        <div class="eff-stat"><span>Classes correspondantes</span><strong>{{ number_format($report['eligible_count'], 0, ',', ' ') }}</strong><small>Avant le plafond demandé</small></div>
        <div class="eff-stat"><span>Classes affichables</span><strong>{{ number_format($report['retained_count'], 0, ',', ' ') }}</strong><small>Après plafond, toutes pages confondues</small></div>
        <div class="eff-stat"><span>Élèves uniques</span><strong>{{ number_format($report['unique_students'], 0, ',', ' ') }}</strong><small>Dans les classes retenues</small></div>
        <div class="eff-stat"><span>Inscriptions par classe</span><strong>{{ number_format($report['sum_class_enrollments'], 0, ',', ' ') }}</strong><small>Somme des effectifs par classe</small></div>
    </div>

    <div class="eff-panel">
        <div class="eff-panel-heading">
            <div>
                <h2>Répartition par classe</h2>
                <p>Une même personne inscrite dans deux classes compte une fois dans chacune, mais une seule fois dans « Élèves uniques ».</p>
            </div>
            <span class="eff-count">{{ $report['retained_count'] }} classe{{ $report['retained_count'] > 1 ? 's' : '' }}</span>
        </div>

        @if(!$report['filters']['annee_universitaire_id'])
            <div class="eff-empty"><i class="fas fa-calendar-alt" aria-hidden="true"></i><h3>Choisissez une année universitaire</h3><p>Le rapport se construit à partir des inscriptions validées sur cette année.</p></div>
        @elseif($report['retained_count'] === 0 && $report['has_classes_below_threshold'])
            <div class="eff-empty"><i class="fas fa-filter" aria-hidden="true"></i><h3>Aucune classe n’atteint le seuil</h3><p>Réduisez « Minimum d’inscrits » pour voir les classes dont l’effectif est plus faible.</p></div>
        @elseif($report['retained_count'] === 0)
            <div class="eff-empty"><i class="fas fa-chalkboard" aria-hidden="true"></i><h3>Aucune classe pour ces filtres</h3><p>Vérifiez l’année, la filière, le niveau ou affichez les classes vides avec un minimum de 0.</p></div>
        @else
            <div class="eff-table-scroll">
                <table class="eff-table">
                    <thead><tr><th scope="col">Rang</th><th scope="col">Classe</th><th scope="col">Filière / mention</th><th scope="col">Niveau</th><th scope="col" class="eff-right">Inscrits</th></tr></thead>
                    <tbody>
                    @foreach($report['rows'] as $classe)
                        <tr>
                            <td class="eff-rank">{{ ($report['rows']->currentPage() - 1) * $report['rows']->perPage() + $loop->iteration }}</td>
                            <td><strong>{{ $classe->name }}</strong>@if($classe->code)<small class="eff-code">{{ $classe->code }}</small>@endif</td>
                            <td>{{ $classe->filiere ?: '—' }}</td>
                            <td>{{ $classe->niveau ?: '—' }}</td>
                            <td class="eff-right"><span class="eff-badge">{{ number_format($classe->effectif, 0, ',', ' ') }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="eff-table-footer">
                <small>Page {{ $report['rows']->currentPage() }} sur {{ $report['rows']->lastPage() }} · {{ $report['retained_count'] }} classes retenues au total (les compteurs couvrent toutes les pages).</small>
                @if($report['rows']->hasPages())
                    <nav aria-label="Pages de résultats" class="eff-pages">{{ $report['rows']->onEachSide(1)->links() }}</nav>
                @endif
            </div>
        @endif
    </div>
</section>
