@php
    // Lot 13 — colonne "Encaissé par" visible UNIQUEMENT pour les users
    // qui voient TOUS les paiements (paiements.view).
    $showCreatorColumn = auth()->user()?->can('paiements.view') ?? false;
    $colspan = $showCreatorColumn ? 10 : 9;
    $hasMore = method_exists($paiements, 'hasMorePages') ? $paiements->hasMorePages() : false;
    $nextPage = method_exists($paiements, 'currentPage') ? ($paiements->currentPage() + 1) : 2;
    $totalRows = method_exists($paiements, 'total') ? $paiements->total() : $paiements->count();
@endphp
<div class="pi-table-card"
     data-has-more="{{ $hasMore ? '1' : '0' }}"
     data-next-page="{{ $nextPage }}"
     data-total="{{ $totalRows }}">
    <div class="pi-table-head">
        <div class="pi-table-title">
            <i class="fas fa-list"></i>
            <span>Liste des Paiements</span>
        </div>
        <div class="pi-table-count">
            <span id="pi-rows-shown">{{ $paiements->count() }}</span> / <span id="pi-rows-total">{{ $totalRows }}</span>
            <span class="pi-table-count-label">résultats</span>
        </div>
    </div>

    <div class="pi-table-wrap">
        <table class="pi-table">
            <thead>
                <tr>
                    <th class="pi-th-checkbox">
                        <input type="checkbox" id="select-all" class="form-check-input" title="Tout sélectionner">
                    </th>
                    <th>N° Reçu</th>
                    <th>Étudiant</th>
                    <th class="d-none d-md-table-cell">Catégorie</th>
                    <th>Date</th>
                    <th>Montant</th>
                    <th class="d-none d-md-table-cell">Mode</th>
                    @if($showCreatorColumn)
                        <th class="d-none d-lg-table-cell">Encaissé par</th>
                    @endif
                    <th>Statut</th>
                    <th class="pi-th-actions">Actions</th>
                </tr>
            </thead>
            <tbody id="pi-tbody">
                @forelse($paiements as $paiement)
                    @include('esbtp.paiements.partials.ligne-paiement', ['paiement' => $paiement, 'showCreatorColumn' => $showCreatorColumn])
                @empty
                    <tr id="pi-empty-row">
                        <td colspan="{{ $colspan }}" class="pi-table-empty">
                            <i class="fas fa-inbox"></i>
                            <span>Aucun paiement trouvé</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Sentinel infinite scroll + loader skeleton --}}
    <div id="pi-infinite-zone" class="pi-infinite-zone">
        <div id="pi-loader" class="pi-loader" style="display:none;" aria-hidden="true">
            <div class="pi-skeleton-row"></div>
            <div class="pi-skeleton-row"></div>
            <div class="pi-skeleton-row"></div>
        </div>
        <div id="pi-sentinel" class="pi-sentinel" aria-hidden="true"></div>
        <div id="pi-end-marker" class="pi-end-marker" style="display:{{ $hasMore ? 'none' : 'flex' }};">
            <i class="fas fa-check-circle"></i>
            <span>{{ $totalRows > 0 ? 'Tous les résultats sont affichés' : '' }}</span>
        </div>
    </div>
</div>
