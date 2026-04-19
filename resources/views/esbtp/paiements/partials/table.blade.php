<div class="card-moderne">
    <div class="p-lg">
        <div class="section-title mb-md d-flex align-items-center justify-content-between">
            <div>
                <i class="fas fa-list me-2"></i>
                Liste des Paiements
            </div>
            <div class="text-muted small">
                {{ $paiements->total() }} résultat(s)
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all" class="form-check-input" title="Tout sélectionner">
                        </th>
                        <th>N° Reçu</th>
                        <th>Étudiant</th>
                        <th class="d-none d-md-table-cell">Catégorie</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th class="d-none d-md-table-cell">Mode</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paiements as $paiement)
                        @include('esbtp.paiements.partials.ligne-paiement', ['paiement' => $paiement])
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <br><span class="text-muted">Aucun paiement trouvé</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $paiements->appends(request()->query())->links() }}
        </div>
    </div>
</div>
