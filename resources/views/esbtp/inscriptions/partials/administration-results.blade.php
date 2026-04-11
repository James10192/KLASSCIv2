@if($inscriptions->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light">
                <tr>
                    @can('access_admin')
                    <th style="width: 40px;">
                        <input type="checkbox" id="select-all-inscriptions" class="form-check-input">
                    </th>
                    @endcan
                    <th>Matricule</th>
                    <th>Nom complet</th>
                    <th>Filière</th>
                    <th>Niveau</th>
                    <th>Classe</th>
                    <th>Étape</th>
                    <th>Paiement</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inscriptions as $inscription)
                    @include('esbtp.inscriptions.partials.administration-ligne', ['inscription' => $inscription])
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $inscriptions->appends(request()->query())->links() }}
    </div>
@else
    <div class="text-center py-5">
        <div class="mb-3">
            <i class="fas fa-inbox fa-3x text-muted"></i>
        </div>
        <h4>Aucune inscription trouvée</h4>
        <p class="text-muted">Aucune inscription en attente ne correspond aux filtres appliqués.</p>
        <button type="button" class="btn-acasi primary" onclick="resetAdminFilters()">
            <i class="fas fa-refresh"></i>Réinitialiser les filtres
        </button>
    </div>
@endif
