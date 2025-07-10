/**
 * KLASSCI Modal Custom - Solution Alternative
 * Contournement des problèmes Bootstrap avec modal natif JavaScript
 */

class KlassciModal {
    constructor(id, options = {}) {
        this.id = id;
        this.options = {
            backdrop: true,
            keyboard: true,
            focus: true,
            ...options
        };
        this.isOpen = false;
        this.backdrop = null;
        this.modal = null;
        
        this.init();
    }
    
    init() {
        // Créer le backdrop
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'klassci-modal-backdrop';
        this.backdrop.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        `;
        
        // Récupérer le modal existant
        this.modal = document.getElementById(this.id);
        if (this.modal) {
            // Extraire le contenu du modal Bootstrap
            const modalDialog = this.modal.querySelector('.modal-dialog');
            const modalContent = this.modal.querySelector('.modal-content');
            
            if (modalContent) {
                // Créer le nouveau modal
                const newModal = modalContent.cloneNode(true);
                newModal.style.cssText = `
                    position: relative;
                    z-index: 10001;
                    max-width: 90vw;
                    max-height: 90vh;
                    overflow-y: auto;
                    transform: scale(0.8);
                    transition: transform 0.3s ease;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                `;
                
                // Ajouter le modal au backdrop
                this.backdrop.appendChild(newModal);
                this.newModal = newModal;
                
                // Cacher l'ancien modal Bootstrap
                this.modal.style.display = 'none';
                
                // Ajouter les événements
                this.bindEvents();
                
                // Ajouter au body
                document.body.appendChild(this.backdrop);
            }
        }
    }
    
    bindEvents() {
        // Fermer au clic sur backdrop
        if (this.options.backdrop) {
            this.backdrop.addEventListener('click', (e) => {
                if (e.target === this.backdrop) {
                    this.hide();
                }
            });
        }
        
        // Fermer avec Escape
        if (this.options.keyboard) {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.hide();
                }
            });
        }
        
        // Gérer les boutons de fermeture
        const closeButtons = this.newModal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => this.hide());
        });
        
        // Gérer le formulaire s'il existe
        const form = this.newModal.querySelector('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit(form);
            });
        }
    }
    
    show() {
        if (this.isOpen) return;
        
        this.isOpen = true;
        document.body.style.overflow = 'hidden';
        
        // Afficher le backdrop
        this.backdrop.style.visibility = 'visible';
        this.backdrop.style.opacity = '1';
        
        // Animer le modal
        setTimeout(() => {
            this.newModal.style.transform = 'scale(1)';
        }, 10);
        
        // Focus sur le premier input
        if (this.options.focus) {
            setTimeout(() => {
                const firstInput = this.newModal.querySelector('input:not([type="hidden"]), textarea, select');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 300);
        }
    }
    
    hide() {
        if (!this.isOpen) return;
        
        this.isOpen = false;
        document.body.style.overflow = '';
        
        // Animer la fermeture
        this.newModal.style.transform = 'scale(0.8)';
        this.backdrop.style.opacity = '0';
        
        setTimeout(() => {
            this.backdrop.style.visibility = 'hidden';
        }, 300);
    }
    
    handleFormSubmit(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Désactiver le bouton et afficher le loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Création...';
        
        // Nettoyer les erreurs précédentes
        const errorElements = form.querySelectorAll('.invalid-feedback');
        errorElements.forEach(el => el.textContent = '');
        const invalidInputs = form.querySelectorAll('.is-invalid');
        invalidInputs.forEach(el => el.classList.remove('is-invalid'));

        // URL du formulaire depuis l'action ou data attribute
        const url = form.action || form.dataset.action || '/esbtp/comptabilite/fournisseurs/ajax/store';

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Ajouter le nouveau fournisseur au select principal
                const fournisseurSelect = document.getElementById('fournisseur_selection');
                if (fournisseurSelect && data.fournisseur) {
                    const newOption = new Option(data.fournisseur.nom, data.fournisseur.id, true, true);
                    // Insérer avant l'option "Nouveau fournisseur"
                    const lastOption = fournisseurSelect.lastElementChild;
                    fournisseurSelect.insertBefore(newOption, lastOption);
                }
                
                // Fermer le modal
                this.hide();
                
                // Réinitialiser le formulaire
                form.reset();
                
                // Afficher un message de succès
                this.showAlert('success', data.message || 'Fournisseur créé avec succès !');
            } else {
                // Afficher les erreurs de validation
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        const feedback = input ? input.parentNode.querySelector('.invalid-feedback') : null;
                        if (input && feedback) {
                            input.classList.add('is-invalid');
                            feedback.textContent = data.errors[field][0];
                        }
                    });
                } else {
                    this.showAlert('danger', data.message || 'Une erreur est survenue.');
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            this.showAlert('danger', 'Une erreur est survenue lors de la création du fournisseur.');
        })
        .finally(() => {
            // Réactiver le bouton
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
    
    showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10002;
            max-width: 400px;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Animer l'entrée
        setTimeout(() => {
            alertDiv.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto-remove après 5 secondes
        setTimeout(() => {
            alertDiv.style.transform = 'translateX(100%)';
            setTimeout(() => alertDiv.remove(), 300);
        }, 5000);
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', function() {
    // Remplacer le modal Bootstrap par le modal custom
    const fournisseurModal = new KlassciModal('modalNouveauFournisseur');
    
    // Intercepter les clics sur les boutons de déclenchement
    const triggerButtons = document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#modalNouveauFournisseur"]');
    triggerButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fournisseurModal.show();
        });
    });
    
    // Exposer globalement pour le debug
    window.klassciModal = fournisseurModal;
    
    console.log('✅ KLASSCI Modal Custom initialisé - Contournement Bootstrap appliqué');
});
