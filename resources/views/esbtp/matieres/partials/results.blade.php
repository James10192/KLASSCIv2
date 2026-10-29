{{-- Le wrapper #matieres-results.mi-results-card est défini dans index.blade.php.
     Ce partial rend uniquement le contenu interne (header de card + table + paginator),
     car il est ré-injecté via AJAX (innerHTML) à chaque filtre/pagination.    --}}

<div class="table-responsive">
    <table class="table datatable mi-table align-middle" style="width:100%;">
        <thead>
            <tr>
                <th style="width: 44px;">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" id="matieres-select-all" aria-label="Tout sélectionner sur cette page">
                        <label class="form-check-label visually-hidden" for="matieres-select-all">Tout sélectionner</label>
                    </div>
                </th>
                <th>Code</th>
                <th>Nom</th>
                <th>Coefficient</th>
                <th>Volume horaire</th>
                <th>Liaisons</th>
                <th>Statut</th>
                <th style="width: 110px;">Actions</th>
            </tr>
        </thead>
        <tbody id="matieres-tbody"
               data-has-more="{{ $matieres->hasMorePages() ? '1' : '0' }}"
               data-next-page="{{ $matieres->currentPage() + 1 }}"
               data-current-page="{{ $matieres->currentPage() }}">
            @forelse($matieres as $matiere)
                @include('esbtp.matieres.partials.matiere-row', ['matiere' => $matiere])
            @empty
                <tr>
                    <td colspan="8" class="p-0">
                        <div class="mi-empty-state">
                            <div class="mi-empty-state-icon" aria-hidden="true">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h4>Aucune matière trouvée</h4>
                            <p>Aucune matière ne correspond aux filtres actuels. Essayez d'élargir votre recherche ou créez une nouvelle matière.</p>
                            <div class="d-flex gap-2 justify-content-center flex-wrap">
                                <button type="button" id="mi-empty-clear-filters" class="mi-empty-cta" style="background:#fff;color:#0453cb;border:1px solid #0453cb;">
                                    <i class="fas fa-eraser" aria-hidden="true"></i>
                                    Effacer les filtres
                                </button>
                                <a href="{{ route('esbtp.matieres.create') }}" class="mi-empty-cta">
                                    <i class="fas fa-plus" aria-hidden="true"></i>
                                    Nouvelle matière
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Sentinel infinite scroll : IntersectionObserver détecte sa visibilité et trigger loadMore() --}}
@if($matieres->total() > 0)
    <div id="matieres-sentinel"
         class="mi-sentinel"
         data-current-page="{{ $matieres->currentPage() }}"
         data-last-page="{{ $matieres->lastPage() }}"
         data-total="{{ $matieres->total() }}">
        <div class="mi-sentinel-spinner" style="display:none;">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Chargement...</span>
        </div>
        @if(! $matieres->hasMorePages() && $matieres->total() > 0)
            <div class="mi-sentinel-end">
                <i class="fas fa-check-circle"></i>
                <span>Toutes les {{ $matieres->total() }} matière(s) affichée(s).</span>
            </div>
        @endif
    </div>
@endif
