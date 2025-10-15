<div class="card-moderne">
    <div class="main-card-header d-flex flex-wrap justify-content-between align-items-end gap-2">
        <div>
            <h3 class="main-card-title">
                <i class="fas fa-table me-2"></i>Liste des évaluations
            </h3>
            <p class="main-card-subtitle mb-0" data-summary-range>
                @if($evaluations->total() > 0)
                    {{ $evaluations->firstItem() ?? 0 }} - {{ $evaluations->lastItem() ?? 0 }} sur {{ $evaluations->total() }} évaluation(s)
                @else
                    Aucune évaluation ne correspond à vos filtres.
                @endif
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm" id="evaluations-per-page" name="per_page">
                @foreach([15, 30, 50] as $size)
                    <option value="{{ $size }}" {{ $evaluations->perPage() == $size ? 'selected' : '' }}>{{ $size }} / page</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="main-card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 40px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="evaluations-select-all">
                            </div>
                        </th>
                        <th>Titre & Statut</th>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Notes</th>
                        <th style="width: 220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($evaluations as $evaluation)
                        @include('esbtp.evaluations.partials.evaluation-row', ['evaluation' => $evaluation])
                    @empty
                        <tr>
                            <td colspan="8" class="py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <i class="fas fa-inbox fa-2x"></i>
                                    <span>Aucune évaluation trouvée avec ces critères.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $evaluations->links() }}
        </div>
    </div>
</div>
