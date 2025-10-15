<div class="card-moderne">
    <div class="main-card-header">
        <h3 class="main-card-title">
            <i class="fas fa-table me-2"></i>Liste des Matières
        </h3>
        <p class="main-card-subtitle">
            @if($matieres->total() > 0)
                {{ $matieres->firstItem() ?? 0 }} - {{ $matieres->lastItem() ?? 0 }} sur {{ $matieres->total() }} matière(s)
            @else
                Aucune matière ne correspond à vos filtres.
            @endif
        </p>
    </div>
    <div class="main-card-body">
        <div class="table-responsive">
            <table class="table datatable align-middle" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 50px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="matieres-select-all">
                                <label class="form-check-label" for="matieres-select-all"></label>
                            </div>
                        </th>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Coefficient</th>
                        <th>Total heures</th>
                        <th>Filières</th>
                        <th>Niveaux</th>
                        <th>Statut</th>
                        <th style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matieres as $matiere)
                        @include('esbtp.matieres.partials.matiere-row', ['matiere' => $matiere])
                    @empty
                        <tr>
                            <td colspan="9" class="py-5 text-center">
                                <div class="d-flex flex-column align-items-center gap-2 text-muted">
                                    <i class="fas fa-inbox fa-2x"></i>
                                    <span>Aucune matière trouvée avec ces critères.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $matieres->appends(request()->query())->links() }}
        </div>
    </div>
</div>
