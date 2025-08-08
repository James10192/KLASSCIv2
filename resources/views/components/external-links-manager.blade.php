{{-- Composant de gestion des liens externes --}}
<div class="external-links-manager">
    <!-- Section de génération de liens -->
    <div class="card shadow-sm border-0 mb-4" id="generate-links-section">
        <div class="card-header bg-gradient-warning text-white">
            <h5 class="mb-0">
                <i class="fas fa-link me-2"></i>Génération de liens externes temporaires
            </h5>
            <p class="mb-0 opacity-75">Créer des liens sécurisés pour la saisie de notes par des enseignants externes</p>
        </div>
        <div class="card-body">
            <!-- Sélecteur d'évaluation -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="evaluation-select" class="form-label fw-medium">Sélectionner une évaluation</label>
                    <select id="evaluation-select" class="form-select">
                        <option value="">-- Choisir une évaluation --</option>
                        @foreach($evaluations ?? [] as $evaluation)
                            <option value="{{ $evaluation->id }}" data-title="{{ $evaluation->titre }}" 
                                    data-classe="{{ $evaluation->classe->name ?? '' }}" 
                                    data-matiere="{{ $evaluation->matiere->name ?? '' }}">
                                {{ $evaluation->titre }} - {{ $evaluation->classe->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="enseignant-externe-nom" class="form-label fw-medium">Nom de l'enseignant externe</label>
                    <input type="text" id="enseignant-externe-nom" class="form-control" 
                           placeholder="Ex: Dr. Martin Dupont">
                </div>
            </div>

            <!-- Configuration de la durée -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="duree-heures" class="form-label fw-medium">Durée de validité</label>
                    <select id="duree-heures" class="form-select">
                        <option value="24">24 heures</option>
                        <option value="48">48 heures</option>
                        <option value="72" selected>3 jours</option>
                        <option value="120">5 jours</option>
                        <option value="168">7 jours</option>
                    </select>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <button type="button" id="generate-link-btn" class="btn btn-warning me-2">
                        <i class="fas fa-magic me-1"></i>Générer le lien
                    </button>
                    <div class="text-muted">
                        <small><i class="fas fa-info-circle me-1"></i>Le lien sera automatiquement révoqué après expiration</small>
                    </div>
                </div>
            </div>

            <!-- Résultat de génération -->
            <div id="generated-link-result" class="d-none">
                <div class="alert alert-success border-0 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="alert-heading"><i class="fas fa-check-circle me-1"></i>Lien généré avec succès</h6>
                            <div class="input-group mt-2">
                                <input type="text" id="generated-link-input" class="form-control" readonly>
                                <button class="btn btn-outline-primary" type="button" id="copy-link-btn">
                                    <i class="fas fa-copy me-1"></i>Copier
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-clock me-1"></i>Expire le : <span id="expire-date"></span>
                            </small>
                        </div>
                        <button type="button" class="btn-close" id="close-result"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section de monitoring des liens actifs -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient-info text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                    <i class="fas fa-monitor-heart-rate me-2"></i>Liens externes actifs
                </h5>
                <p class="mb-0 opacity-75">Surveillance et gestion des liens temporaires</p>
            </div>
            <button type="button" id="refresh-links-btn" class="btn btn-light btn-sm">
                <i class="fas fa-refresh me-1"></i>Actualiser
            </button>
        </div>
        <div class="card-body">
            <div id="active-links-container">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Chargement des liens actifs...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.external-links-manager .link-item {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.3s ease;
    background: #fff;
}

.external-links-manager .link-item:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.1);
    transform: translateY(-1px);
}

.external-links-manager .link-item.expiring {
    border-color: #fd7e14;
    background: rgba(253, 126, 20, 0.05);
}

.external-links-manager .link-item.expired {
    border-color: #dc3545;
    background: rgba(220, 53, 69, 0.05);
    opacity: 0.7;
}

.external-links-manager .link-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const externalLinksManager = {
        generateBtn: document.getElementById('generate-link-btn'),
        evaluationSelect: document.getElementById('evaluation-select'),
        dureeHeures: document.getElementById('duree-heures'),
        enseignantNom: document.getElementById('enseignant-externe-nom'),
        resultDiv: document.getElementById('generated-link-result'),
        linkInput: document.getElementById('generated-link-input'),
        copyBtn: document.getElementById('copy-link-btn'),
        expireDate: document.getElementById('expire-date'),
        closeResult: document.getElementById('close-result'),
        refreshBtn: document.getElementById('refresh-links-btn'),
        linksContainer: document.getElementById('active-links-container'),

        init() {
            this.bindEvents();
            this.loadActiveLinks();
            // Auto-refresh toutes les 2 minutes
            setInterval(() => this.loadActiveLinks(), 120000);
        },

        bindEvents() {
            this.generateBtn.addEventListener('click', () => this.generateLink());
            this.copyBtn.addEventListener('click', () => this.copyLink());
            this.closeResult.addEventListener('click', () => this.hideResult());
            this.refreshBtn.addEventListener('click', () => this.loadActiveLinks());
        },

        async generateLink() {
            const evaluationId = this.evaluationSelect.value;
            if (!evaluationId) {
                this.showError('Veuillez sélectionner une évaluation');
                return;
            }

            const dureeHeures = this.dureeHeures.value;
            const enseignantNom = this.enseignantNom.value;

            this.setLoading(this.generateBtn, true);

            try {
                const response = await fetch(`/esbtp/evaluations/${evaluationId}/generate-external-link`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        duree_heures: dureeHeures,
                        enseignant_externe_nom: enseignantNom
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showGeneratedLink(data.link, data.expires_at);
                    this.loadActiveLinks(); // Refresh la liste
                } else {
                    this.showError(data.message || 'Erreur lors de la génération');
                }
            } catch (error) {
                this.showError('Erreur de connexion');
                console.error(error);
            } finally {
                this.setLoading(this.generateBtn, false);
            }
        },

        async loadActiveLinks() {
            this.setLoading(this.refreshBtn, true);

            try {
                const response = await fetch('/esbtp/evaluations/active-external-links');
                const links = await response.json();

                this.renderActiveLinks(links);
            } catch (error) {
                console.error('Erreur lors du chargement des liens actifs:', error);
                this.linksContainer.innerHTML = `
                    <div class="text-center text-danger py-4">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                        <p class="mt-2">Erreur lors du chargement des liens</p>
                    </div>
                `;
            } finally {
                this.setLoading(this.refreshBtn, false);
            }
        },

        renderActiveLinks(links) {
            if (links.length === 0) {
                this.linksContainer.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-link-slash fa-2x"></i>
                        <p class="mt-2">Aucun lien externe actif</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="row g-3">';
            links.forEach(link => {
                const isExpiring = link.expires_in_hours <= 24 && link.expires_in_hours > 0;
                const isExpired = link.expires_in_hours <= 0;
                
                let statusClass = '';
                let statusIcon = 'fa-check-circle text-success';
                let statusText = 'Actif';

                if (isExpired) {
                    statusClass = 'expired';
                    statusIcon = 'fa-times-circle text-danger';
                    statusText = 'Expiré';
                } else if (isExpiring) {
                    statusClass = 'expiring';
                    statusIcon = 'fa-exclamation-circle text-warning';
                    statusText = 'Expire bientôt';
                }

                html += `
                    <div class="col-12">
                        <div class="link-item ${statusClass}">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h6 class="mb-1">${link.titre}</h6>
                                    <div class="text-muted small">
                                        <i class="fas fa-users me-1"></i>${link.classe} • 
                                        <i class="fas fa-book me-1"></i>${link.matiere}
                                    </div>
                                    ${link.enseignant_externe_nom ? `
                                        <div class="text-muted small">
                                            <i class="fas fa-user-tie me-1"></i>${link.enseignant_externe_nom}
                                        </div>
                                    ` : ''}
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas ${statusIcon} me-2"></i>
                                        <div>
                                            <div class="fw-medium">${statusText}</div>
                                            <small class="text-muted">Expire le ${link.expires_at}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="link-actions justify-content-end">
                                        <button class="btn btn-outline-primary btn-sm" onclick="externalLinksManager.copyToClipboard('${link.link}')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" onclick="externalLinksManager.revokeLink(${link.id})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            this.linksContainer.innerHTML = html;
        },

        async revokeLink(evaluationId) {
            if (!confirm('Êtes-vous sûr de vouloir révoquer ce lien ? Cette action est irréversible.')) {
                return;
            }

            try {
                const response = await fetch(`/esbtp/evaluations/${evaluationId}/revoke-external-link`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess('Lien révoqué avec succès');
                    this.loadActiveLinks();
                } else {
                    this.showError(data.message || 'Erreur lors de la révocation');
                }
            } catch (error) {
                this.showError('Erreur de connexion');
                console.error(error);
            }
        },

        showGeneratedLink(link, expiresAt) {
            this.linkInput.value = link;
            this.expireDate.textContent = expiresAt;
            this.resultDiv.classList.remove('d-none');
        },

        hideResult() {
            this.resultDiv.classList.add('d-none');
        },

        async copyLink() {
            await this.copyToClipboard(this.linkInput.value);
        },

        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.showSuccess('Lien copié dans le presse-papiers');
            } catch (error) {
                // Fallback pour les navigateurs plus anciens
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showSuccess('Lien copié dans le presse-papiers');
            }
        },

        setLoading(button, loading) {
            if (loading) {
                button.disabled = true;
                const icon = button.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-spinner fa-spin me-1';
                }
            } else {
                button.disabled = false;
                const icon = button.querySelector('i');
                if (icon) {
                    if (button === this.generateBtn) {
                        icon.className = 'fas fa-magic me-1';
                    } else if (button === this.refreshBtn) {
                        icon.className = 'fas fa-refresh me-1';
                    }
                }
            }
        },

        showSuccess(message) {
            this.showToast(message, 'success');
        },

        showError(message) {
            this.showToast(message, 'error');
        },

        showToast(message, type) {
            // Créer un toast Bootstrap ou une notification simple
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
    };

    // Initialiser le gestionnaire
    window.externalLinksManager = externalLinksManager;
    externalLinksManager.init();
});
</script>