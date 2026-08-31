@can('paiements.avoir')
@if($paiement->status == 'validé' && ! $paiement->isAvoir() && $paiement->avoir_disponible > 0)
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('esbtp.paiements.avoir.store', $paiement->id) }}">
                @csrf
                <div class="modal-header" style="background:#0453cb;color:#fff;">
                    <h5 class="modal-title">Émettre un avoir — {{ $paiement->numero_recu }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Reliquat disponible : <strong>{{ number_format($paiement->avoir_disponible, 0, ',', ' ') }} FCFA</strong></p>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="avoir_kind" class="form-select" required>
                            <option value="credit">Crédit sur compte (pas de sortie caisse)</option>
                            <option value="refund">Remboursement cash (sortie caisse)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Montant</label>
                        <input type="number" name="montant" class="form-control" min="1" max="{{ (int) $paiement->avoir_disponible }}" value="{{ (int) $paiement->avoir_disponible }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motif</label>
                        <textarea name="motif" class="form-control" rows="3" minlength="5" required placeholder="Motif de l'avoir (5 caractères min.)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Enregistrer l'avoir</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endcan
