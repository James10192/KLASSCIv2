@php
    $currentSort = request('sort', 'created_at');
    $currentDir = request('dir', 'desc');
    $sortLink = function($column, $label) use ($currentSort, $currentDir) {
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

@if($inscriptions->isEmpty())
    <div class="ii-empty">
        <div class="ii-empty-icon"><i class="fas fa-check-double"></i></div>
        <h4>Aucune inscription sous réserve</h4>
        <p>Toutes les inscriptions pour les filtres choisis sont confirmées.</p>
    </div>
@else
    <div class="ii-table-wrap">
        <table class="ii-table">
            <thead>
                <tr>
                    <th style="width:38px;">
                        <input type="checkbox" id="isr-select-all" class="form-check-input" aria-label="Tout sélectionner">
                    </th>
                    <th>Étudiant</th>
                    <th>Classe</th>
                    <th>Année</th>
                    @php $s = $sortLink('condition_reserve', 'Condition'); @endphp
                    <th class="is-sortable" data-sort="{{ $s['column'] }}" data-next-dir="{{ $s['nextDir'] }}" aria-sort="{{ $s['ariaSort'] }}">
                        {{ $s['label'] }} <i class="fas {{ $s['icon'] }} ii-sort-icon"></i>
                    </th>
                    <th>Paiement</th>
                    @php $s = $sortLink('workflow_step', 'Workflow'); @endphp
                    <th class="is-sortable" data-sort="{{ $s['column'] }}" data-next-dir="{{ $s['nextDir'] }}" aria-sort="{{ $s['ariaSort'] }}">
                        {{ $s['label'] }} <i class="fas {{ $s['icon'] }} ii-sort-icon"></i>
                    </th>
                    @php $s = $sortLink('created_at', 'Date'); @endphp
                    <th class="is-sortable" data-sort="{{ $s['column'] }}" data-next-dir="{{ $s['nextDir'] }}" aria-sort="{{ $s['ariaSort'] }}">
                        {{ $s['label'] }} <i class="fas {{ $s['icon'] }} ii-sort-icon"></i>
                    </th>
                    <th style="width:130px; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody id="isr-tbody">
                @foreach($inscriptions as $inscription)
                    @include('esbtp.inscriptions.partials.sous-reserve-ligne')
                @endforeach
            </tbody>
        </table>
    </div>

    <x-liste-infinie :paginateur="$inscriptions" cible="#isr-tbody" libelle="inscriptions"
                     :url="route('esbtp.inscriptions.sous-reserve')" />
@endif
