{{-- Modaux réutilisés depuis l'ancienne version --}}

<!-- Modal pour les variants d'une catégorie -->
<div class="modal fade" id="categoryVariantsModal" tabindex="-1" aria-labelledby="categoryVariantsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryVariantsModalLabel">Variants - <span id="categoryTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="categoryVariantsContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un variant -->
<div class="modal fade" id="addVariantModal" tabindex="-1" aria-labelledby="addVariantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVariantModalLabel">Ajouter un Variant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="addVariantForm">
                <div class="modal-body">
                    <input type="hidden" id="variantCategoryId" name="category_id">
                    <div class="mb-3">
                        <label for="variantName" class="form-label">Nom du variant</label>
                        <input type="text" class="form-control" id="variantName" name="name" required>
                        <div class="form-text">Ex: "Arrêt Centre-ville", "Menu Standard"</div>
                    </div>
                    <div class="mb-3">
                        <label for="variantDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="variantDescription" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="variantAmount" class="form-label">Montant (FCFA)</label>
                        <input type="number" class="form-control" id="variantAmount" name="amount" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="variantIsDefault" name="is_default">
                            <label class="form-check-label" for="variantIsDefault">
                                Variant par défaut
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour tous les variants -->
<div class="modal fade" id="allVariantsModal" tabindex="-1" aria-labelledby="allVariantsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="allVariantsModalLabel">Tous les Variants</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="allVariantsContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>