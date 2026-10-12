{{-- Fragments de modales d'un paiement : rendu hors <tbody> sur l'index,
     et dans un fragment dédié lors du refresh AJAX d'une ligne. --}}
<!-- Modal de rejet individuel -->
@if($paiement->status == 'en_attente' && auth()->user()->can('paiements.validate'))
<div class="modal fade" id="rejetModal{{ $paiement->id }}" tabindex="-1" aria-labelledby="rejetModalLabel{{ $paiement->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('esbtp.paiements.rejeter', $paiement->id) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejetModalLabel{{ $paiement->id }}">
                        <i class="fas fa-times-circle me-2"></i>
                        Rejeter le paiement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Vous êtes sur le point de rejeter le paiement <strong>{{ $paiement->numero_recu }}</strong>
                        de <strong>{{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet }}</strong>.
                    </p>
                    <div class="mb-3">
                        <label for="motif_rejet{{ $paiement->id }}" class="form-label" style="cursor: default;">Motif du rejet <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="motif_rejet{{ $paiement->id }}" name="motif_rejet" rows="4"
                                  placeholder="Expliquez pourquoi ce paiement est rejeté..." style="cursor: text;"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmer_rejet{{ $paiement->id }}" style="cursor: pointer;">
                        <label class="form-check-label" for="confirmer_rejet{{ $paiement->id }}" style="cursor: pointer;">
                            Je confirme le rejet de ce paiement
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-danger rejeter-paiement-submit-btn" data-paiement-id="{{ $paiement->id }}">
                        <i class="fas fa-times-circle me-1"></i>Rejeter le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@include('esbtp.paiements.partials.avoir-modal', ['paiement' => $paiement, 'modalId' => 'avoirModal'.$paiement->id])
@include('esbtp.paiements.partials.mode-reglement-modal', ['paiement' => $paiement])
@include('esbtp.paiements.partials.supprimer-modal', [
    'paiement' => $paiement,
    'modalId' => 'supprimerPaiementModal'.$paiement->id,
    // Rendue en AJAX (filtre, rafraichissement de ligne), l'adresse courante
    // serait celle du point d'acces : on revient alors a la liste.
    'retour' => request()->ajax() ? null : request()->getRequestUri(),
])
