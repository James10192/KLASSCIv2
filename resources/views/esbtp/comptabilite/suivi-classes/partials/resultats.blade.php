<div class="sf-table-scroll">
    <table class="sf-table">
        <thead>
        <tr>
            <th>Classe</th><th>Effectif</th><th class="sf-money">Attendu</th><th class="sf-money">Réglé</th>
            @unless($rapport['filtered_category'])<th class="sf-money">Caisse nette</th>@endunless
            <th class="sf-money">Reste</th><th class="sf-money">Échu impayé</th><th>Recouvrement</th>
            @can('comptabilite.suivi_classes.view_details')<th class="sf-action-col">Détail</th>@endcan
        </tr>
        </thead>
        <tbody>
        @forelse($rapport['classes'] as $ligne)
            <tr>
                <td><strong>{{ $ligne['classe'] }}</strong><small>{{ $ligne['filiere'] }} · {{ $ligne['niveau'] }}</small></td>
                <td class="sf-figure">{{ $ligne['effectif'] }}</td>
                <td class="sf-money">{{ number_format($ligne['attendu'], 0, ',', ' ') }}</td>
                <td class="sf-money">{{ number_format($ligne['regle'], 0, ',', ' ') }}</td>
                @unless($rapport['filtered_category'])<td class="sf-money">{{ number_format($ligne['encaisse'], 0, ',', ' ') }}</td>@endunless
                <td class="sf-money sf-strong">{{ number_format($ligne['restant'], 0, ',', ' ') }}</td>
                <td class="sf-money">{{ number_format($ligne['echu'], 0, ',', ' ') }}</td>
                <td class="sf-progress-cell">
                    <div class="sf-progress-label">{{ $ligne['taux'] === null ? '—' : number_format($ligne['taux'], 1, ',', ' ') . ' %' }}</div>
                    <div class="sf-progress"><i style="width:{{ $ligne['taux'] ?? 0 }}%"></i></div>
                </td>
                @can('comptabilite.suivi_classes.view_details')
                    <td><button class="sf-details-btn" type="button" @click="openDetails({{ $ligne['classe_id'] }})" aria-label="Afficher le détail de {{ $ligne['classe'] }}"><i class="fas fa-eye"></i> Voir</button></td>
                @endcan
            </tr>
        @empty
            <tr><td colspan="{{ 7 + ($rapport['filtered_category'] ? 0 : 1) + (auth()->user()?->can('comptabilite.suivi_classes.view_details') ? 1 : 0) }}" class="sf-empty">Aucune classe trouvée pour ces filtres. Essayez une autre année ou réinitialisez la recherche.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="sf-table-footer">
    <span>{{ $rapport['totaux']['classes'] }} classe(s) · {{ $rapport['totaux']['effectif'] }} inscription(s) distincte(s) par classe</span>
    @if($rapport['classes'] instanceof \Illuminate\Pagination\LengthAwarePaginator && $rapport['classes']->hasPages())
        <div class="sf-pagination">{{ $rapport['classes']->links() }}</div>
    @endif
</div>
