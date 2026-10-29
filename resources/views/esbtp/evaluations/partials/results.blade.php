{{-- Header (titre + per_page) --}}
<div class="ev-results-header">
    <div class="ev-results-summary" data-summary-range>
        @if($evaluations->total() > 0)
            <strong>{{ $evaluations->firstItem() ?? 0 }}</strong> – <strong>{{ $evaluations->lastItem() ?? 0 }}</strong> sur <strong>{{ $evaluations->total() }}</strong> évaluation(s)
        @else
            Aucune évaluation ne correspond à vos filtres.
        @endif
    </div>
    <select class="ev-per-page" id="evaluations-per-page" name="per_page" title="Évaluations par page">
        @foreach([15, 30, 50] as $size)
            <option value="{{ $size }}" {{ $evaluations->perPage() == $size ? 'selected' : '' }}>{{ $size }} / page</option>
        @endforeach
    </select>
</div>

{{-- Table --}}
<div class="ev-table-wrap">
    <table class="ev-table">
        <thead>
            <tr>
                <th class="ev-col-check">
                    <input class="ev-check" type="checkbox" id="evaluations-select-all">
                </th>
                <th>Titre &amp; statut</th>
                <th>Classe</th>
                <th>Matière</th>
                <th>Type</th>
                <th>Date</th>
                <th class="ev-col-notes">Notes</th>
                <th class="ev-col-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evaluations as $evaluation)
                @include('esbtp.evaluations.partials.evaluation-row', ['evaluation' => $evaluation])
            @empty
                <tr>
                    <td colspan="8" class="ev-empty">
                        <i class="fas fa-inbox"></i>
                        <div class="ev-empty-text">Aucune évaluation trouvée avec ces critères.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="evaluations-infinite-sentinel" class="ev-infinite-sentinel"
     data-next-page="{{ $evaluations->hasMorePages() ? $evaluations->currentPage() + 1 : '' }}"
     data-has-more="{{ $evaluations->hasMorePages() ? '1' : '0' }}" aria-live="polite">
    @if($evaluations->hasMorePages())
        <span><i class="fas fa-circle-notch fa-spin"></i> Faites défiler pour charger la suite</span>
    @else
        <span>{{ $evaluations->total() ? 'Toutes les évaluations sont affichées.' : '' }}</span>
    @endif
</div>
