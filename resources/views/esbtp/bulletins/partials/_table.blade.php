@if($bulletins->count() === 0)
    <div class="bul-empty">
        <div class="bul-empty-icon"><i class="fas fa-file-circle-question"></i></div>
        <div class="bul-empty-title">Aucun bulletin trouvé</div>
        <div class="bul-empty-msg">
            @if($classe_id || $periode_id || ($published !== null && $published !== '') || $search)
                Aucun bulletin ne correspond à vos filtres. Essayez de les réinitialiser pour voir tous les bulletins.
            @else
                Vous n'avez pas encore généré de bulletins pour cette année. Cliquez sur « Générer » pour commencer.
            @endif
        </div>
        @can('bulletins.generate')
        <a href="{{ route('esbtp.bulletins.select', array_filter([
                    'classe_id' => $classe_id ?? null,
                    'periode' => $periode_id ?? null,
                    'annee_universitaire_id' => $annee_id ?? null,
                ])) }}" class="bul-btn bul-btn--primary">
            <i class="fas fa-magic-wand-sparkles"></i> Générer mes premiers bulletins
        </a>
        @endcan
    </div>
@else
    <table class="bul-table">
        <thead>
            <tr>
                <th class="checkbox-col">
                    <input type="checkbox" @change="toggleAll($event)" :checked="allSelected()" />
                </th>
                <th>Étudiant</th>
                <th>Classe</th>
                <th>Période</th>
                <th class="center">Moyenne</th>
                <th class="center">Rang</th>
                <th>Statut</th>
                <th>Généré le</th>
                <th class="center">Actions</th>
            </tr>
        </thead>
        <tbody id="bul-tbody">
            @foreach($bulletins as $bulletin)
                @include('esbtp.bulletins.partials._ligne')
            @endforeach
        </tbody>
    </table>

    <x-liste-infinie :paginateur="$bulletins" cible="#bul-tbody" libelle="bulletins"
                     :url="route('esbtp.bulletins.index')" />
@endif
