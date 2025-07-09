/**
 * Gestionnaire de prévisualisation temps réel pour les bons de sortie
 * ESBTP-yAKROv2Pascal - Tâche #5
 */

class BonSortiePreview {
    constructor() {
        this.previewTimeout = null;
        this.previewContainer = null;
        this.formFields = {};
        this.isInitialized = false;
    }

    /**
     * Initialise la prévisualisation
     */
    init() {
        if (this.isInitialized) return;

        this.previewContainer = document.getElementById("preview-container");
        if (!this.previewContainer) {
            console.warn("Container de prévisualisation non trouvé");
            return;
        }

        this.initFormFields();
        this.attachEventListeners();
        this.updatePreview();
        this.isInitialized = true;

        console.log("BonSortiePreview initialisé");
    }

    /**
     * Initialise les références aux champs du formulaire
     */
    initFormFields() {
        this.formFields = {
            libelle: document.getElementById("libelle"),
            montant: document.getElementById("montant"),
            date_depense: document.getElementById("date_depense"),
            categorie_id: document.getElementById("categorie_id"),
            fournisseur_id: document.getElementById("fournisseur_id"),
            mode_paiement: document.getElementById("mode_paiement"),
            description: document.getElementById("description"),
        };

        // Vérifier que tous les champs existent
        for (const [key, field] of Object.entries(this.formFields)) {
            if (!field) {
                console.warn(`Champ ${key} non trouvé`);
            }
        }
    }

    /**
     * Attache les écouteurs d'événements
     */
    attachEventListeners() {
        // Événements pour mise à jour temps réel
        Object.values(this.formFields).forEach((field) => {
            if (field) {
                field.addEventListener("input", () => this.scheduleUpdate());
                field.addEventListener("change", () => this.scheduleUpdate());
            }
        });

        // Validation en temps réel
        if (this.formFields.montant) {
            this.formFields.montant.addEventListener("input", () =>
                this.validateMontant()
            );
        }

        if (this.formFields.date_depense) {
            this.formFields.date_depense.addEventListener("change", () =>
                this.validateDate()
            );
        }
    }

    /**
     * Programme une mise à jour avec délai
     */
    scheduleUpdate() {
        clearTimeout(this.previewTimeout);
        this.previewTimeout = setTimeout(() => {
            this.updatePreview();
        }, 300);
    }

    /**
     * Met à jour la prévisualisation
     */
    updatePreview() {
        if (!this.previewContainer) return;

        const formData = this.getFormData();

        if (this.isValidForPreview(formData)) {
            this.renderPreview(formData);
        } else {
            this.renderEmptyPreview();
        }
    }

    /**
     * Récupère les données du formulaire
     */
    getFormData() {
        const data = {};

        for (const [key, field] of Object.entries(this.formFields)) {
            if (field) {
                data[key] = field.value;
            }
        }

        return data;
    }

    /**
     * Vérifie si les données sont suffisantes pour la prévisualisation
     */
    isValidForPreview(data) {
        return (
            data.libelle &&
            data.montant &&
            data.date_depense &&
            data.categorie_id &&
            data.mode_paiement
        );
    }

    /**
     * Génère le HTML de prévisualisation
     */
    renderPreview(data) {
        const categorieText =
            this.getSelectedText("categorie_id") || "Non spécifiée";
        const fournisseurText =
            this.getSelectedText("fournisseur_id") || "Non spécifié";
        const modeText =
            this.getSelectedText("mode_paiement") || "Non spécifié";
        const montantFormate = this.formatMontant(data.montant);
        const dateFormatee = this.formatDate(data.date_depense);
        const numeroBon = this.generateNumeroBon();

        const html = `
            <div class="preview-card border rounded p-3 shadow-sm">
                <div class="text-center mb-3">
                    <h6 class="text-primary mb-1">
                        <i class="fas fa-file-export"></i> BON DE SORTIE
                    </h6>
                    <small class="text-muted badge bg-light">${numeroBon}</small>
                </div>

                <div class="preview-content">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="fw-bold text-muted" style="width: 40%;">Libellé:</td>
                            <td>${this.escapeHtml(data.libelle)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Montant:</td>
                            <td class="text-success fw-bold">${montantFormate}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Date:</td>
                            <td>${dateFormatee}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Catégorie:</td>
                            <td><span class="badge bg-secondary">${categorieText}</span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Fournisseur:</td>
                            <td>${fournisseurText}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Mode:</td>
                            <td><span class="badge bg-info">${modeText}</span></td>
                        </tr>
                    </table>

                    ${
                        data.description
                            ? `
                        <div class="mt-3 p-2 bg-light rounded">
                            <small class="fw-bold text-muted">Description:</small><br>
                            <small>${this.escapeHtml(data.description)}</small>
                        </div>
                    `
                            : ""
                    }

                    <div class="text-center mt-3">
                        <span class="badge bg-warning">
                            <i class="fas fa-edit"></i> Brouillon
                        </span>
                    </div>
                </div>

                <div class="preview-actions mt-3 text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="bonSortiePreview.previewPDF()">
                        <i class="fas fa-eye"></i> Aperçu PDF
                    </button>
                </div>
            </div>
        `;

        this.previewContainer.innerHTML = html;
    }

    /**
     * Affiche la prévisualisation vide
     */
    renderEmptyPreview() {
        const html = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-file-export fa-3x mb-3 opacity-50"></i>
                <h6>Prévisualisation</h6>
                <p class="small">Remplissez les champs requis pour voir la prévisualisation</p>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-check text-success"></i> Libellé<br>
                        <i class="fas fa-check text-success"></i> Montant<br>
                        <i class="fas fa-check text-success"></i> Date<br>
                        <i class="fas fa-check text-success"></i> Catégorie<br>
                        <i class="fas fa-check text-success"></i> Mode de paiement
                    </small>
                </div>
            </div>
        `;

        this.previewContainer.innerHTML = html;
    }

    /**
     * Récupère le texte sélectionné d'un select
     */
    getSelectedText(fieldName) {
        const field = this.formFields[fieldName];
        if (!field || !field.selectedOptions || !field.selectedOptions[0]) {
            return null;
        }
        return field.selectedOptions[0].text;
    }

    /**
     * Formate un montant
     */
    formatMontant(montant) {
        if (!montant) return "0 FCFA";

        const nombre = parseFloat(montant);
        if (isNaN(nombre)) return "0 FCFA";

        return new Intl.NumberFormat("fr-FR").format(nombre) + " FCFA";
    }

    /**
     * Formate une date
     */
    formatDate(dateString) {
        if (!dateString) return "Non spécifiée";

        try {
            const date = new Date(dateString);
            return date.toLocaleDateString("fr-FR");
        } catch (e) {
            return "Date invalide";
        }
    }

    /**
     * Génère un numéro de bon temporaire
     */
    generateNumeroBon() {
        const date = new Date();
        const dateStr = date.toISOString().slice(0, 10).replace(/-/g, "");
        return `BON-${dateStr}-XXXX`;
    }

    /**
     * Échappe le HTML
     */
    escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Valide le montant
     */
    validateMontant() {
        const field = this.formFields.montant;
        if (!field) return;

        const montant = parseFloat(field.value);

        // Supprimer les classes de validation précédentes
        field.classList.remove("is-valid", "is-invalid");

        if (field.value && (isNaN(montant) || montant <= 0)) {
            field.classList.add("is-invalid");
            this.showFieldError(field, "Le montant doit être supérieur à 0");
        } else if (field.value) {
            field.classList.add("is-valid");
            this.hideFieldError(field);
        }
    }

    /**
     * Valide la date
     */
    validateDate() {
        const field = this.formFields.date_depense;
        if (!field) return;

        field.classList.remove("is-valid", "is-invalid");

        if (field.value) {
            const date = new Date(field.value);
            const today = new Date();

            if (date > today) {
                field.classList.add("is-invalid");
                this.showFieldError(
                    field,
                    "La date ne peut pas être dans le futur"
                );
            } else {
                field.classList.add("is-valid");
                this.hideFieldError(field);
            }
        }
    }

    /**
     * Affiche une erreur sur un champ
     */
    showFieldError(field, message) {
        this.hideFieldError(field);

        const feedback = document.createElement("div");
        feedback.className = "invalid-feedback";
        feedback.textContent = message;
        feedback.id = field.id + "_error";

        field.parentNode.appendChild(feedback);
    }

    /**
     * Masque l'erreur d'un champ
     */
    hideFieldError(field) {
        const existingError = document.getElementById(field.id + "_error");
        if (existingError) {
            existingError.remove();
        }
    }

    /**
     * Prévisualise le PDF
     */
    previewPDF() {
        const formData = this.getFormData();

        if (!this.isValidForPreview(formData)) {
            alert(
                "Veuillez remplir tous les champs obligatoires avant la prévisualisation PDF"
            );
            return;
        }

        // Créer et afficher le modal
        this.showPDFModal();
    }

    /**
     * Affiche le modal de prévisualisation PDF
     */
    showPDFModal() {
        // Vérifier si le modal existe déjà
        let modal = document.getElementById("previewPDFModal");

        if (!modal) {
            modal = this.createPDFModal();
            document.body.appendChild(modal);
        }

        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Simuler le chargement
        setTimeout(() => {
            const container = modal.querySelector("#pdf-preview-content");
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    La prévisualisation PDF complète sera disponible après enregistrement du bon.
                </div>
                <div class="text-center">
                    <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                    <h6>Aperçu du document PDF</h6>
                    <p class="text-muted">Le PDF final contiendra les signatures et le QR code de vérification.</p>
                </div>
            `;
        }, 1000);
    }

    /**
     * Crée le modal de prévisualisation PDF
     */
    createPDFModal() {
        const modal = document.createElement("div");
        modal.className = "modal fade";
        modal.id = "previewPDFModal";
        modal.tabIndex = -1;

        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-file-pdf text-danger"></i>
                            Prévisualisation PDF
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="pdf-preview-content" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-2">Génération de la prévisualisation...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Fermer
                        </button>
                    </div>
                </div>
            </div>
        `;

        return modal;
    }

    /**
     * Valide le formulaire complet
     */
    validateForm() {
        const formData = this.getFormData();
        const errors = [];

        // Validation libellé
        if (!formData.libelle || formData.libelle.trim().length < 3) {
            errors.push("Le libellé doit contenir au moins 3 caractères");
        }

        // Validation montant
        const montant = parseFloat(formData.montant);
        if (!formData.montant || isNaN(montant) || montant <= 0) {
            errors.push("Le montant doit être supérieur à 0");
        }

        // Validation date
        if (!formData.date_depense) {
            errors.push("La date de dépense est obligatoire");
        }

        // Validation catégorie
        if (!formData.categorie_id) {
            errors.push("La catégorie est obligatoire");
        }

        // Validation mode de paiement
        if (!formData.mode_paiement) {
            errors.push("Le mode de paiement est obligatoire");
        }

        return {
            isValid: errors.length === 0,
            errors: errors,
        };
    }

    /**
     * Affiche les erreurs de validation
     */
    showValidationErrors(errors) {
        const alertHtml = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6><i class="fas fa-exclamation-triangle"></i> Erreurs de validation :</h6>
                <ul class="mb-0">
                    ${errors.map((error) => `<li>${error}</li>`).join("")}
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Insérer l'alerte au début du formulaire
        const form = document.querySelector("#bonSortieForm, form");
        if (form) {
            form.insertAdjacentHTML("afterbegin", alertHtml);
        }
    }
}

// Instance globale
const bonSortiePreview = new BonSortiePreview();

// Auto-initialisation quand le DOM est prêt
document.addEventListener("DOMContentLoaded", function () {
    bonSortiePreview.init();
});

// Fonctions globales pour compatibilité
function updatePreview() {
    bonSortiePreview.scheduleUpdate();
}

function previewPDF() {
    bonSortiePreview.previewPDF();
}
