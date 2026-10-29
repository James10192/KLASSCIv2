{{-- Suppression d'un versement, motif obligatoire : le geste laisse une trace
     (qui, quand, pourquoi). Une seule modale, incluse par la fiche du
     paiement, chaque ligne de la liste et chaque versement de la fiche
     d'inscription. `retour` : chemin local ou revenir apres suppression. --}}
@can('paiements.delete')
@php $spRetour = $retour ?? null; @endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow:0 10px 40px rgba(0,0,0,.2);">
            <div class="modal-header" style="background:linear-gradient(135deg, #dc2626, #b91c1c); color:#fff; border-radius:15px 15px 0 0; padding:1.25rem 1.5rem; border:none;">
                <h5 class="modal-title fw-bold"><i class="fas fa-trash me-2"></i>Supprimer le versement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form action="{{ route('esbtp.paiements.destroy', $paiement->id) }}" method="POST">
                @csrf
                @method('DELETE')
                @if($spRetour)
                    <input type="hidden" name="retour" value="{{ $spRetour }}">
                @endif
                <div class="modal-body text-start" style="padding:1.5rem;">
                    <div style="background:#fef2f2; border:1.5px solid #fca5a5; border-radius:10px; padding:12px 16px; margin-bottom:16px;">
                        <div style="display:flex; gap:10px; align-items:flex-start;">
                            <i class="fas fa-exclamation-triangle" style="color:#dc2626; margin-top:2px;"></i>
                            <div style="color:#991b1b; font-size:.88rem;">
                                <strong>Le versement sera retiré des comptes de l'étudiant.</strong>
                                Le reçu {{ $paiement->numero_recu }} ne sera plus valable. Votre nom, la date et le motif restent lisibles au journal d'audit.
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:16px; font-size:.88rem;">
                        <div><strong>Montant :</strong> {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</div>
                        <div><strong>Reçu :</strong> {{ $paiement->numero_recu }}</div>
                    </div>
                    <div x-data="{ count: 0 }">
                        <label for="{{ $modalId }}-motif" class="form-label fw-semibold" style="font-size:.88rem;">
                            Motif de la suppression <span class="text-danger">*</span>
                            <span style="font-weight:400; color:#64748b; font-size:.78rem;">(min. {{ \App\Domain\Comptabilite\Paiements\Actions\SupprimerPaiement::MOTIF_MIN }} caractères)</span>
                        </label>
                        <textarea name="motif" id="{{ $modalId }}-motif" rows="4" class="form-control" required
                                  minlength="{{ \App\Domain\Comptabilite\Paiements\Actions\SupprimerPaiement::MOTIF_MIN }}" maxlength="500"
                                  placeholder="Ex : Encaissé par erreur, le paquet de rames a été déposé en nature."
                                  x-on:input="count = $event.target.value.length"
                                  style="border:2px solid #dee2e6; border-radius:10px; resize:none;"></textarea>
                        <div style="display:flex; justify-content:flex-end; margin-top:6px; font-size:.74rem; color:#94a3b8;">
                            <span x-text="count + ' / 500'" :style="count < {{ \App\Domain\Comptabilite\Paiements\Actions\SupprimerPaiement::MOTIF_MIN }} ? 'color:#dc2626;font-weight:600' : ''">0 / 500</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f8f9fa; border-radius:0 0 15px 15px; padding:1rem 1.5rem; border:none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px; font-weight:600;">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-danger" style="border-radius:8px; font-weight:600;">
                        <i class="fas fa-trash me-1"></i>Supprimer le versement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
