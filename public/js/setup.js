/**
 * Setup.js - Gère l'interface d'installation de l'application
 * 
 * Ce script gère la navigation entre les étapes d'installation,
 * les vérifications système et les appels AJAX pour configurer l'application.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Variables pour les étapes
    const steps = document.querySelectorAll('.setup-step');
    const stepIndicators = document.querySelectorAll('.step-indicator');
    const nextButtons = document.querySelectorAll('.btn-next');
    const prevButtons = document.querySelectorAll('.btn-prev');
    let currentStep = 0;

    // Initialisation
    showStep(currentStep);
    checkSystemRequirements();

    // Gestion des boutons suivant
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Validation spécifique à l'étape
            if (currentStep === 2) { // Étape de migration
                runMigrations();
                return;
            } else if (currentStep === 3) { // Étape de création d'admin
                createAdmin();
                return;
            }
            
            // Navigation normale
            if (currentStep < steps.length - 1) {
                showStep(currentStep + 1);
            }
        });
    });

    // Gestion des boutons précédent
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (currentStep > 0) {
                showStep(currentStep - 1);
            }
        });
    });

    /**
     * Affiche l'étape spécifiée et met à jour les indicateurs
     * @param {number} stepIndex - L'index de l'étape à afficher
     */
    function showStep(stepIndex) {
        // Masquer toutes les étapes
        steps.forEach(step => step.style.display = 'none');
        
        // Afficher l'étape actuelle
        steps[stepIndex].style.display = 'block';
        
        // Mettre à jour les indicateurs d'étape
        stepIndicators.forEach((indicator, index) => {
            if (index < stepIndex) {
                indicator.classList.remove('active', 'current');
                indicator.classList.add('completed');
            } else if (index === stepIndex) {
                indicator.classList.remove('completed');
                indicator.classList.add('active', 'current');
            } else {
                indicator.classList.remove('completed', 'active', 'current');
            }
        });
        
        // Mettre à jour l'étape actuelle
        currentStep = stepIndex;
    }

    /**
     * Vérifie les prérequis système et affiche les résultats
     */
    function checkSystemRequirements() {
        const requirementsList = document.getElementById('requirements-list');
        
        // Simuler une vérification des prérequis (à remplacer par un appel AJAX réel)
        fetch('/setup/check-requirements')
            .then(response => response.json())
            .then(data => {
                // En cas d'erreur de connexion, utiliser des valeurs par défaut
                if (!data) {
                    data = {
                        php_version: { status: true, message: 'PHP 8.0+' },
                        database: { status: true, message: 'Connexion réussie' },
                        writable_dirs: { status: true, message: 'Dossiers accessibles en écriture' },
                        extensions: { status: true, message: 'Extensions PHP requises installées' }
                    };
                }
                
                // Afficher les résultats
                for (const [key, value] of Object.entries(data)) {
                    const item = document.createElement('li');
                    item.className = value.status ? 'text-success' : 'text-danger';
                    item.innerHTML = `<i class="fas fa-${value.status ? 'check' : 'times'}-circle"></i> ${value.message}`;
                    requirementsList.appendChild(item);
                }
            })
            .catch(error => {
                debugError('Erreur lors de la vérification des prérequis:', error);
                // Afficher un message d'erreur
                requirementsList.innerHTML = '<li class="text-danger"><i class="fas fa-exclamation-triangle"></i> Impossible de vérifier les prérequis. Vérifiez votre connexion.</li>';
            });
    }

    /**
     * Exécute les migrations de base de données
     */
    function runMigrations() {
        const consoleOutput = document.getElementById('console-output');
        const migrationButton = document.querySelector('.migration-btn');
        
        // Désactiver le bouton pendant l'opération
        migrationButton.disabled = true;
        migrationButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Migration en cours...';
        
        // Ajouter un message au début
        appendToConsole('Démarrage des migrations...', consoleOutput);
        
        // Appel AJAX pour exécuter les migrations
        fetch('/setup/migrate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                appendToConsole('✅ Migrations terminées avec succès!', consoleOutput);
                // Passer à l'étape suivante après un court délai
                setTimeout(() => {
                    showStep(currentStep + 1);
                }, 1500);
            } else {
                appendToConsole('❌ Erreur lors des migrations: ' + data.message, consoleOutput);
                // Réactiver le bouton
                migrationButton.disabled = false;
                migrationButton.innerHTML = 'Réessayer les migrations';
            }
        })
        .catch(error => {
            debugError('Erreur lors des migrations:', error);
            appendToConsole('❌ Erreur de connexion. Veuillez réessayer.', consoleOutput);
            // Réactiver le bouton
            migrationButton.disabled = false;
            migrationButton.innerHTML = 'Réessayer les migrations';
        });
    }

    /**
     * Crée un utilisateur administrateur
     */
    function createAdmin() {
        const adminForm = document.getElementById('admin-form');
        const createButton = document.querySelector('.create-admin-btn');
        const errorContainer = document.getElementById('admin-errors');
        
        // Vérifier la validité du formulaire
        if (!adminForm.checkValidity()) {
            adminForm.reportValidity();
            return;
        }
        
        // Récupérer les données du formulaire
        const formData = new FormData(adminForm);
        const adminData = {
            name: formData.get('admin_name'),
            email: formData.get('admin_email'),
            password: formData.get('admin_password'),
            password_confirmation: formData.get('admin_password_confirmation')
        };
        
        // Vérifier que les mots de passe correspondent
        if (adminData.password !== adminData.password_confirmation) {
            errorContainer.innerHTML = '<div class="alert alert-danger">Les mots de passe ne correspondent pas.</div>';
            return;
        }
        
        // Désactiver le bouton pendant l'opération
        createButton.disabled = true;
        createButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';
        
        // Appel AJAX pour créer l'administrateur
        fetch('/setup/create-admin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(adminData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Finaliser l'installation
                finalizeSetup();
            } else {
                // Afficher les erreurs
                let errorHtml = '<div class="alert alert-danger"><ul>';
                if (typeof data.errors === 'object') {
                    for (const [field, messages] of Object.entries(data.errors)) {
                        messages.forEach(message => {
                            errorHtml += `<li>${message}</li>`;
                        });
                    }
                } else {
                    errorHtml += `<li>${data.message || 'Une erreur est survenue'}</li>`;
                }
                errorHtml += '</ul></div>';
                errorContainer.innerHTML = errorHtml;
                
                // Réactiver le bouton
                createButton.disabled = false;
                createButton.innerHTML = 'Créer l\'administrateur';
            }
        })
        .catch(error => {
            debugError('Erreur lors de la création de l\'administrateur:', error);
            errorContainer.innerHTML = '<div class="alert alert-danger">Erreur de connexion. Veuillez réessayer.</div>';
            
            // Réactiver le bouton
            createButton.disabled = false;
            createButton.innerHTML = 'Créer l\'administrateur';
        });
    }

    /**
     * Finalise l'installation
     */
    function finalizeSetup() {
        const finalStep = document.getElementById('final-step-content');
        finalStep.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x mb-3"></i><p>Finalisation de l\'installation...</p></div>';
        
        // Appel AJAX pour finaliser l'installation
        fetch('/setup/finalize', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                finalStep.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                        <h3>Installation terminée!</h3>
                        <p>L'application a été installée avec succès.</p>
                        <a href="/login" class="btn btn-primary mt-3">Accéder à l'application</a>
                    </div>
                `;
            } else {
                finalStep.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle text-warning fa-5x mb-3"></i>
                        <h3>Installation partiellement terminée</h3>
                        <p>L'administrateur a été créé, mais certaines étapes n'ont pas pu être finalisées.</p>
                        <p class="text-danger">${data.message || 'Erreur inconnue'}</p>
                        <a href="/login" class="btn btn-primary mt-3">Accéder à l'application</a>
                    </div>
                `;
            }
        })
        .catch(error => {
            debugError('Erreur lors de la finalisation:', error);
            finalStep.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-exclamation-circle text-danger fa-5x mb-3"></i>
                    <h3>Erreur lors de la finalisation</h3>
                    <p>Une erreur est survenue lors de la finalisation de l'installation.</p>
                    <p>Vous pouvez tout de même essayer d'accéder à l'application.</p>
                    <a href="/login" class="btn btn-primary mt-3">Accéder à l'application</a>
                </div>
            `;
        });
    }

    /**
     * Ajoute un message à la console
     * @param {string} message - Le message à ajouter
     * @param {HTMLElement} consoleElement - L'élément console
     */
    function appendToConsole(message, consoleElement) {
        const line = document.createElement('div');
        line.textContent = message;
        consoleElement.appendChild(line);
        // Faire défiler vers le bas
        consoleElement.scrollTop = consoleElement.scrollHeight;
    }
}); 