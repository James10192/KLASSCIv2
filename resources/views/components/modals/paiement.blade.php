<!-- resources/views/components/modals/paiement.blade.php -->
<div class="modal fade" id="paiementModal" tabindex="-1" aria-labelledby="paiementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paiementModalLabel">Enregistrer un Paiement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Le formulaire de paiement ira ici -->
                <form id="paiementForm">
                    @csrf
                    <input type="hidden" id="inscriptionId" name="inscription_id">

                    <div class="mb-3">
                        <label for="montant" class="form-label">Montant à payer</label>
                        <input type="number" class="form-control" id="montant" name="montant" required>
                    </div>

                    <div class="mb-3">
                        <label for="methode_paiement" class="form-label">Méthode de paiement</label>
                        <select class="form-select" id="methode_paiement" name="methode_paiement">
                            <option value="espece">Espèce</option>
                            <option value="cheque">Chèque</option>
                            <option value="virement">Virement</option>
                            <option value="mobile_money">Mobile Money</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="reference_paiement" class="form-label">Référence (si applicable)</label>
                        <input type="text" class="form-control" id="reference_paiement" name="reference_paiement">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="submitPaiement">Valider le Paiement</button>
            </div>
        </div>
    </div>
</div> 