<div class="sfc-table-scroll">
    <table class="sfc-table">
        <thead>
        <tr>
            <th>Classe</th><th>Effectif</th><th class="sfc-money">Attendu</th><th class="sfc-money">Réglé</th>
            @unless($rapport['filtered_category'])<th class="sfc-money">Caisse nette</th>@endunless
            <th class="sfc-money">Reste</th><th class="sfc-money">Échu impayé</th><th>Recouvrement</th>
            @can('comptabilite.suivi_classes.view_details')<th class="sfc-action-col">Détail</th>@endcan
        </tr>
        </thead>
        <tbody>
        @forelse($rapport['classes'] as $ligne)
            <tr>
                <td><strong>{{ $ligne['classe'] }}</strong><small>{{ $ligne['filiere'] }} · {{ $ligne['niveau'] }}</small></td>
                <td class="sfc-figure">{{ $ligne['effectif'] }}</td>
                <td class="sfc-money">{{ number_format($ligne['attendu'], 0, ',', ' ') }}</td>
                <td class="sfc-money">{{ number_format($ligne['regle'], 0, ',', ' ') }}</td>
                @unless($rapport['filtered_category'])<td class="sfc-money">{{ number_format($ligne['encaisse'], 0, ',', ' ') }}</td>@endunless
                <td class="sfc-money sfc-strong">{{ number_format($ligne['restant'], 0, ',', ' ') }}</td>
                <td class="sfc-money">{{ number_format($ligne['echu'], 0, ',', ' ') }}</td>
                <td class="sfc-progress-cell">
                    <div class="sfc-progress-label">{{ $ligne['taux'] === null ? '—' : number_format($ligne['taux'], 1, ',', ' ') . ' %' }}</div>
                    <div class="sfc-progress"><i style="width:{{ $ligne['taux'] ?? 0 }}%"></i></div>
                </td>
                @can('comptabilite.suivi_classes.view_details')
                    <td><button class="sfc-details-btn" type="button" @click="openDetails({{ $ligne['classe_id'] }})" aria-label="Afficher le détail de {{ $ligne['classe'] }}"><i class="fas fa-eye"></i> Voir</button></td>
                @endcan
            </tr>
        @empty
            <tr><td colspan="{{ 7 + ($rapport['filtered_category'] ? 0 : 1) + (auth()->user()?->can('comptabilite.suivi_classes.view_details') ? 1 : 0) }}" class="sfc-empty">Aucune classe trouvée pour ces filtres. Essayez une autre année ou réinitialisez la recherche.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="sfc-table-footer">
    <span>{{ $rapport['totaux']['classes'] }} classe(s) · {{ $rapport['totaux']['effectif'] }} inscription(s) distincte(s) par classe</span>
    @if($rapport['classes'] instanceof \Illuminate\Pagination\LengthAwarePaginator && $rapport['classes']->hasPages())
        <div class="sfc-pagination">{{ $rapport['classes']->links() }}</div>
    @endif
</div>
