@if($inscriptions->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light">
                <tr>
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
        <a href="{{ route('esbtp.inscriptions.administration') }}" class="btn-acasi primary">
            <i class="fas fa-refresh"></i>Réinitialiser les filtres
        </a>
    </div>
@endif
