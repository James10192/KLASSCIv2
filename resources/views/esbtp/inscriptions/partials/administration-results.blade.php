@php
    $currentSort = request('sort', 'created_at');
    $currentDir = request('dir', 'desc');
    $sortLink = function ($column, $label) use ($currentSort, $currentDir) {
        $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
        $ariaSort = $currentSort !== $column ? 'none' : ($currentDir === 'asc' ? 'ascending' : 'descending');
        $icon = 'fa-sort';
        if ($currentSort === $column) {
            $icon = $currentDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
        }
        return [
            'column' => $column,
            'label' => $label,
            'nextDir' => $nextDir,
            'ariaSort' => $ariaSort,
            'icon' => $icon,
        ];
    };
@endphp

@if($inscriptions->count() === 0)
    <div class="ii-empty">
        <div class="ii-empty-icon"><i class="fas fa-inbox"></i></div>
        <h4>Aucune inscription en attente</h4>
        <p>Aucune inscription ne correspond aux filtres appliqués.</p>
    </div>
@else
    <div class="ii-table-wrap">
        <table class="ii-table">
            <thead>
                <tr>
                    @can('admin.access')
                    <th style="width:38px;">
                        <input type="checkbox" id="select-all-inscriptions" class="form-check-input" aria-label="Tout sélectionner">
                    </th>
                    @endcan
                    <th>Étudiant</th>
                    <th>Filière · Niveau</th>
                    <th>Classe</th>
                    @php $s = $sortLink('workflow_step', 'Étape'); @endphp
                    <th class="is-sortable" data-sort="{{ $s['column'] }}" data-next-dir="{{ $s['nextDir'] }}" aria-sort="{{ $s['ariaSort'] }}">
                        {{ $s['label'] }} <i class="fas {{ $s['icon'] }} ii-sort-icon"></i>
                    </th>
                    <th>Paiement</th>
                    @php $s = $sortLink('created_at', 'Date'); @endphp
                    <th class="is-sortable" data-sort="{{ $s['column'] }}" data-next-dir="{{ $s['nextDir'] }}" aria-sort="{{ $s['ariaSort'] }}">
                        {{ $s['label'] }} <i class="fas {{ $s['icon'] }} ii-sort-icon"></i>
                    </th>
                    <th style="width:130px; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inscriptions as $inscription)
                    @include('esbtp.inscriptions.partials.administration-ligne', ['inscription' => $inscription])
                @endforeach
            </tbody>
        </table>
    </div>

    @if($inscriptions->hasPages())
        <div class="ii-pagination">
            {{ $inscriptions->links() }}
        </div>
    @endif
@endif
