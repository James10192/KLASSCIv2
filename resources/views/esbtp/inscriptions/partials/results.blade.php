{{--
    Partial : table résultats des inscriptions.
    Inclus par esbtp.inscriptions.index (render initial) ET retourné par l'endpoint
    AJAX filtre. La ligne <tr> provient de `partials/ligne-inscription.blade.php`
    (source unique de vérité — fix du bug de permission drift pré-existant).

    Paramètres requis :
      - $inscriptions : LengthAwarePaginator
      - $sort : string (default 'created_at')
      - $dir : string (default 'desc')
      - $perPage : int (default 15)
--}}
@php
    $sort = $sort ?? 'created_at';
    $dir = $dir ?? 'desc';
    $perPage = $perPage ?? 15;

    $sortUrl = function (string $column) use ($sort, $dir) {
        $params = request()->query();
        $params['sort'] = $column;
        $params['dir'] = ($sort === $column && $dir === 'asc') ? 'desc' : 'asc';
        unset($params['page']);
        return request()->url().'?'.http_build_query($params);
    };

    $sortIndicator = function (string $column) use ($sort, $dir) {
        if ($sort !== $column) {
            return '<i class="fas fa-sort ii-sort-icon ii-sort-icon--neutral"></i>';
        }
        return '<i class="fas fa-sort-'.($dir === 'asc' ? 'up' : 'down').' ii-sort-icon ii-sort-icon--active"></i>';
    };

    $ariaSort = function (string $column) use ($sort, $dir) {
        if ($sort !== $column) {
            return 'none';
        }
        return $dir === 'asc' ? 'ascending' : 'descending';
    };
@endphp

@if($inscriptions->count() > 0)
    <div class="ii-table-wrap">
        <table class="ii-table">
            <thead>
                <tr>
                    @can('inscriptions.validate')
                        <th class="ii-col-check" style="width:40px;">
                            <input type="checkbox"
                                   id="select-all-inscriptions"
                                   class="form-check-input"
                                   aria-label="Tout sélectionner">
                        </th>
                    @endcan
                    <th class="ii-col-etu" aria-sort="{{ $ariaSort('nom') }}">
                        <a href="{{ $sortUrl('nom') }}" class="ii-sort-link">
                            Étudiant {!! $sortIndicator('nom') !!}
                        </a>
                    </th>
                    <th class="ii-col-filiere" aria-sort="{{ $ariaSort('filiere_id') }}">
                        <a href="{{ $sortUrl('filiere_id') }}" class="ii-sort-link">
                            Filière · Niveau {!! $sortIndicator('filiere_id') !!}
                        </a>
                    </th>
                    <th class="ii-col-annee">Année</th>
                    <th class="ii-col-status" aria-sort="{{ $ariaSort('status') }}">
                        <a href="{{ $sortUrl('status') }}" class="ii-sort-link">
                            Statut {!! $sortIndicator('status') !!}
                        </a>
                    </th>
                    <th class="ii-col-date" aria-sort="{{ $ariaSort('created_at') }}">
                        <a href="{{ $sortUrl('created_at') }}" class="ii-sort-link">
                            Inscription {!! $sortIndicator('created_at') !!}
                        </a>
                    </th>
                    <th class="ii-col-actions" style="width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inscriptions as $inscription)
                    @include('esbtp.inscriptions.partials.ligne-inscription', ['inscription' => $inscription])
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="ii-table-footer">
        <div class="ii-per-page">
            <label for="ii-per-page-select" class="ii-per-page-label">Afficher</label>
            <select id="ii-per-page-select" class="ii-per-page-select" aria-label="Nombre d'inscriptions par page">
                @foreach([15, 25, 50, 100] as $option)
                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <span class="ii-per-page-hint">par page · {{ $inscriptions->total() }} inscription{{ $inscriptions->total() > 1 ? 's' : '' }} au total</span>
        </div>
        <div class="ii-pagination">
            {{ $inscriptions->appends(request()->query())->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    </div>
@else
    <div class="ii-empty">
        <div class="ii-empty-icon"><i class="fas fa-file-circle-question"></i></div>
        <div class="ii-empty-title">Aucune inscription trouvée</div>
        <div class="ii-empty-text">Essayez d'ajuster vos filtres ou de vider la recherche.</div>
    </div>
@endif
