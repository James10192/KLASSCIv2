{{-- Composant Liens Externes — premium namespace xl-* (collapsible compact) --}}
@push('styles')
<style>
/* ═══════════ XL (eXternal Links) premium namespace ═══════════ */
.xl-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.xl-card-header {
    padding: 1rem 1.4rem;
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.06));
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
    cursor: pointer; user-select: none;
    transition: background .15s;
}
.xl-card-header:hover { background: linear-gradient(135deg, rgba(4,83,203,.08), rgba(59,125,219,.1)); }
.xl-section-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .9rem; flex-shrink: 0;
}
.xl-section-title { font-size: .92rem; font-weight: 700; color: #1e293b; margin: 0; }
.xl-section-subtitle { font-size: .76rem; color: #64748b; margin: .1rem 0 0; }
.xl-toggle-icon {
    color: #94a3b8; font-size: .9rem;
    transition: transform .2s;
}
.xl-toggle-icon.open { transform: rotate(180deg); color: #0453cb; }
.xl-body { padding: 1.25rem 1.4rem; display: none; }
.xl-body.open { display: block; }

.xl-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: .85rem;
    margin-bottom: 1rem;
}
.xl-field { display: flex; flex-direction: column; gap: .35rem; }
.xl-field-label {
    font-size: .7rem; font-weight: 700; color: #475569;
    text-transform: uppercase; letter-spacing: .04em;
}
.xl-input, .xl-select {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: .55rem .75rem;
    font-size: .88rem; color: #1e293b;
    transition: border-color .15s, box-shadow .15s;
}
.xl-input:focus, .xl-select:focus {
    outline: none; border-color: rgba(4,83,203,.5);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}

.xl-actions {
    display: flex; gap: .65rem; align-items: center;
    margin-top: .75rem; flex-wrap: wrap;
}
.xl-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    border-radius: 10px;
    padding: .55rem 1rem;
    font-size: .82rem; font-weight: 700;
    cursor: pointer; border: 1px solid transparent;
    transition: all .15s;
}
.xl-btn--primary {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    box-shadow: 0 2px 6px rgba(4,83,203,.25);
}
.xl-btn--primary:hover { box-shadow: 0 4px 12px rgba(4,83,203,.35); transform: translateY(-1px); color: #fff; }
.xl-btn--ghost {
    background: #fff; color: #0453cb;
    border-color: rgba(4,83,203,.25);
}
.xl-btn--ghost:hover { background: rgba(4,83,203,.05); }
.xl-info-text {
    font-size: .76rem; color: #94a3b8;
    display: inline-flex; align-items: center; gap: .3rem;
}

.xl-result {
    background: linear-gradient(135deg, rgba(16,185,129,.05), rgba(16,185,129,.08));
    border: 1px solid rgba(16,185,129,.25);
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1rem;
}
.xl-result-title {
    font-weight: 700; color: #047857;
    display: inline-flex; align-items: center; gap: .4rem;
    font-size: .85rem;
}
.xl-result-input-group {
    display: flex; gap: .5rem; margin: .65rem 0 .35rem; align-items: stretch;
}
.xl-result-input-group input {
    flex: 1; min-width: 0;
    background: #fff; border: 1px solid #d1fae5;
    border-radius: 8px;
    padding: .5rem .7rem;
    font-size: .82rem; color: #1e293b;
    font-family: 'Courier New', monospace;
}
.xl-result-expire {
    font-size: .72rem; color: #64748b;
    display: inline-flex; align-items: center; gap: .3rem;
}

.xl-active-section {
    border-top: 1px solid #f1f5f9;
    margin-top: 1.25rem; padding-top: 1.25rem;
}
.xl-active-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;
}
.xl-active-title { font-size: .85rem; font-weight: 700; color: #1e293b; display: inline-flex; align-items: center; gap: .4rem; }

.xl-link-item {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .85rem 1rem;
    margin-bottom: .65rem;
    transition: all .15s;
}
.xl-link-item:hover {
    border-color: rgba(4,83,203,.4);
    box-shadow: 0 4px 12px rgba(4,83,203,.08);
}
.xl-link-item.expiring { border-color: rgba(245,158,11,.5); background: rgba(245,158,11,.04); }
.xl-link-item.expired { border-color: rgba(220,38,38,.4); background: rgba(220,38,38,.04); opacity: .8; }
.xl-link-actions { display: flex; gap: .4rem; flex-wrap: wrap; }

.xl-empty {
    text-align: center;
    padding: 2rem 1rem;
    color: #94a3b8;
}
.xl-empty i { font-size: 2rem; color: #cbd5e1; margin-bottom: .5rem; }

/* legacy rules retained for backward compat */
.external-links-manager .link-item { background: #fff; border: 1px solid #e9ecef; border-radius: 12px; padding: 1rem; margin-bottom: .75rem; transition: all .3s; }
.external-links-manager .link-item:hover { border-color: #0453cb; box-shadow: 0 4px 12px rgba(4,83,203,.1); transform: translateY(-1px); }
.external-links-manager .link-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
</style>
@endpush

<div class="external-links-manager" x-data="{ open: false }">
    <div class="xl-card">
        {{-- Collapsible header --}}
        <div class="xl-card-header" @click="open = !open">
            <div style="display:flex;align-items:center;gap:.85rem;flex:1;min-width:0;">
                <div class="xl-section-icon"><i class="fas fa-link"></i></div>
                <div>
                    <h5 class="xl-section-title">Liens externes temporaires (admin)</h5>
                    <p class="xl-section-subtitle">Générer des liens pour la saisie de notes par enseignants externes</p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:.65rem;">
                <span style="font-size:.72rem;color:#64748b;" x-show="!open" x-cloak>Cliquer pour ouvrir</span>
                <i class="fas fa-chevron-down xl-toggle-icon" :class="{ open: open }"></i>
            </div>
        </div>

        <div class="xl-body" :class="{ open: open }" x-cloak>
            {{-- Génération --}}
            <div class="xl-form-grid">
                <div class="xl-field">
                    <label for="evaluation-select" class="xl-field-label">Évaluation</label>
                    <select id="evaluation-select" class="xl-select">
                        <option value="">— Choisir une évaluation —</option>
                        @foreach($evaluations ?? [] as $evaluation)
                            <option value="{{ $evaluation->id }}" data-title="{{ $evaluation->titre }}"
                                    data-classe="{{ $evaluation->classe->name ?? '' }}"
                                    data-matiere="{{ $evaluation->matiere->name ?? '' }}">
                                {{ $evaluation->titre }} — {{ $evaluation->classe->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="xl-field">
                    <label for="enseignant-externe-nom" class="xl-field-label">Nom enseignant externe</label>
                    <input type="text" id="enseignant-externe-nom" class="xl-input" placeholder="Ex: Dr. Martin Dupont">
                </div>
                <div class="xl-field">
                    <label for="duree-heures" class="xl-field-label">Durée de validité</label>
                    <select id="duree-heures" class="xl-select">
                        <option value="24">24 heures</option>
                        <option value="48">48 heures</option>
                        <option value="72" selected>3 jours</option>
                        <option value="120">5 jours</option>
                        <option value="168">7 jours</option>
                    </select>
                </div>
            </div>
            <div class="xl-actions">
                <button type="button" id="generate-link-btn" class="xl-btn xl-btn--primary">
                    <i class="fas fa-wand-magic-sparkles"></i>Générer le lien
                </button>
                <span class="xl-info-text"><i class="fas fa-info-circle"></i>Révoqué automatiquement après expiration</span>
            </div>

            {{-- Résultat génération --}}
            <div id="generated-link-result" class="d-none">
                <div class="xl-result">
                    <div class="xl-result-title"><i class="fas fa-check-circle"></i>Lien généré avec succès</div>
                    <div class="xl-result-input-group">
                        <input type="text" id="generated-link-input" readonly>
                        <button class="xl-btn xl-btn--ghost" type="button" id="copy-link-btn">
                            <i class="fas fa-copy"></i>Copier
                        </button>
                    </div>
                    <div class="xl-result-expire"><i class="fas fa-clock"></i>Expire le <span id="expire-date"></span></div>
                </div>
            </div>

            {{-- Liens actifs --}}
            <div class="xl-active-section">
                <div class="xl-active-header">
                    <div class="xl-active-title"><i class="fas fa-broadcast-tower"></i>Liens actifs</div>
                    <button type="button" id="refresh-links-btn" class="xl-btn xl-btn--ghost">
                        <i class="fas fa-arrows-rotate"></i>Actualiser
                    </button>
                </div>
                <div id="active-links-container">
                    <div class="xl-empty">
                        <i class="fas fa-circle-notch fa-spin"></i>
                        <div>Chargement des liens actifs…</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
            if (this.generateBtn) {
                this.generateBtn.addEventListener('click', () => this.generateLink());
            }
            if (this.copyBtn) {
                this.copyBtn.addEventListener('click', () => this.copyLink());
            }
            if (this.closeResult) {
                this.closeResult.addEventListener('click', () => this.hideResult());
            }
            if (this.refreshBtn) {
                this.refreshBtn.addEventListener('click', () => this.loadActiveLinks());
            }
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
                debugError(error);
            } finally {
                this.setLoading(this.generateBtn, false);
            }
        },

        async loadActiveLinks() {
            if (!this.linksContainer) {
                debugLog('Container des liens non trouvé, skip loadActiveLinks');
                return;
            }

            this.setLoading(this.refreshBtn, true);

            try {
                const response = await fetch('/esbtp/evaluations/active-external-links');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const links = await response.json();
                this.renderActiveLinks(links);
            } catch (error) {
                debugError('Erreur lors du chargement des liens actifs:', error);
                
                if (this.linksContainer) {
                    this.linksContainer.innerHTML = `
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-info-circle fa-2x"></i>
                            <p class="mt-2">Aucun lien externe actif pour le moment</p>
                        </div>
                    `;
                }
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
                debugError(error);
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
            if (!button) return;
            
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
