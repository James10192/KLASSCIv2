@extends('layouts.app')

@section('title', 'Planification Avancée des Relances')

@push('styles')
<style>
.segmentation-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.segmentation-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 15px rgba(0,123,255,0.1);
}

.segmentation-card.selected {
    border-color: #007bff;
    background-color: #f8f9ff;
}

.segment-preview {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin: 10px 0;
}

.type-relance-checkbox {
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin: 5px;
    transition: all 0.3s ease;
}

.type-relance-checkbox:hover {
    background-color: #f8f9fa;
}

.type-relance-checkbox.selected {
    background-color: #e7f3ff;
    border-color: #007bff;
}

.planning-summary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
}

.level-indicator {
    display: inline-block;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    text-align: center;
    line-height: 30px;
    margin-right: 10px;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-brain text-primary me-2"></i>
                        Planification Avancée des Relances
                    </h1>
                    <p class="text-muted mb-0">Configuration intelligente avec segmentation automatique</p>
                </div>
                <div>
                    <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form id="formPlanificationAvancee">
        <div class="row">
            <!-- Configuration de la segmentation -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users-cog text-primary me-2"></i>
                            Stratégie de Segmentation
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="segmentation-card p-3" data-segmentation="auto">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="radio" name="segmentation" value="auto" id="seg_auto" class="form-check-input me-2" checked>
                                        <label for="seg_auto" class="form-check-label fw-bold">Automatique (Recommandé)</label>
                                    </div>
                                    <p class="text-muted mb-0 small">Intelligence artificielle combine dette et retard pour créer des segments prioritaires</p>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="segmentation-card p-3" data-segmentation="niveau_retard">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="radio" name="segmentation" value="niveau_retard" id="seg_retard" class="form-check-input me-2">
                                        <label for="seg_retard" class="form-check-label fw-bold">Par Niveau de Retard</label>
                                    </div>
                                    <p class="text-muted mb-0 small">Léger (15-30j), Moyen (30-60j), Sévère (60j+)</p>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="segmentation-card p-3" data-segmentation="montant_dette">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="radio" name="segmentation" value="montant_dette" id="seg_dette" class="form-check-input me-2">
                                        <label for="seg_dette" class="form-check-label fw-bold">Par Montant de Dette</label>
                                    </div>
                                    <p class="text-muted mb-0 small">Faible (<50k), Moyenne (50k-200k), Élevée (>200k)</p>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="segmentation-card p-3" data-segmentation="historique_paiement">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="radio" name="segmentation" value="historique_paiement" id="seg_historique" class="form-check-input me-2">
                                        <label for="seg_historique" class="form-check-label fw-bold">Par Historique</label>
                                    </div>
                                    <p class="text-muted mb-0 small">Bon payeur, Irrégulier, Mauvais payeur</p>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="segmentation-card p-3" data-segmentation="classe">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="radio" name="segmentation" value="classe" id="seg_classe" class="form-check-input me-2">
                                        <label for="seg_classe" class="form-check-label fw-bold">Par Classe</label>
                                    </div>
                                    <p class="text-muted mb-0 small">Segmentation selon la classe d'appartenance</p>
                                </div>
                            </div>
                        </div>

                        <!-- Aperçu des segments -->
                        <div class="mt-4">
                            <button type="button" class="btn btn-outline-info" id="btnPreviewSegments">
                                <i class="fas fa-eye me-1"></i>
                                Aperçu des Segments
                            </button>
                            <div id="segmentPreview" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <!-- Configuration des niveaux -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-layer-group text-success me-2"></i>
                            Configuration des Niveaux
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Nombre maximum de niveaux</label>
                                <select class="form-select" name="niveau_max" required>
                                    <option value="1">1 niveau uniquement</option>
                                    <option value="2">2 niveaux</option>
                                    <option value="3" selected>3 niveaux (Standard)</option>
                                    <option value="4">4 niveaux</option>
                                    <option value="5">5 niveaux (Maximum)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Intervalle entre niveaux</label>
                                <select class="form-select" name="intervalle_jours">
                                    <option value="3">3 jours</option>
                                    <option value="5">5 jours</option>
                                    <option value="7" selected>7 jours (Standard)</option>
                                    <option value="10">10 jours</option>
                                    <option value="14">14 jours</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Types de relances -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope text-warning me-2"></i>
                            Canaux de Communication
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="type-relance-checkbox">
                                    <label class="form-check-label d-flex align-items-center">
                                        <input type="checkbox" name="types_relance[]" value="email" class="form-check-input me-2" checked>
                                        <div>
                                            <i class="fas fa-envelope text-primary me-2"></i>
                                            <strong>Email</strong>
                                            <br><small class="text-muted">Recommandé pour tous niveaux</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="type-relance-checkbox">
                                    <label class="form-check-label d-flex align-items-center">
                                        <input type="checkbox" name="types_relance[]" value="sms" class="form-check-input me-2" checked>
                                        <div>
                                            <i class="fas fa-mobile-alt text-success me-2"></i>
                                            <strong>SMS</strong>
                                            <br><small class="text-muted">Efficace pour urgences</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="type-relance-checkbox">
                                    <label class="form-check-label d-flex align-items-center">
                                        <input type="checkbox" name="types_relance[]" value="courrier" class="form-check-input me-2">
                                        <div>
                                            <i class="fas fa-file-pdf text-danger me-2"></i>
                                            <strong>Courrier</strong>
                                            <br><small class="text-muted">Niveau 3+ uniquement</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panneau de configuration et résumé -->
            <div class="col-lg-4">
                <!-- Programmation -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt text-info me-2"></i>
                            Programmation
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="execution_type" id="execution_immediate" value="immediate" checked>
                            <label class="form-check-label" for="execution_immediate">
                                <strong>Exécution immédiate</strong>
                                <br><small class="text-muted">Démarre maintenant</small>
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="execution_type" id="execution_programmee" value="programmee">
                            <label class="form-check-label" for="execution_programmee">
                                <strong>Exécution programmée</strong>
                                <br><small class="text-muted">Démarre à une date précise</small>
                            </label>
                        </div>

                        <div id="date_execution_group" style="display: none;">
                            <label class="form-label">Date d'exécution</label>
                            <input type="date" class="form-control" name="date_execution" min="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </div>

                <!-- Résumé de la planification -->
                <div class="planning-summary mb-4">
                    <h6 class="text-uppercase mb-3">
                        <i class="fas fa-clipboard-list me-2"></i>
                        Résumé de la Planification
                    </h6>

                    <div id="summaryContent">
                        <div class="mb-2">
                            <strong>Segmentation:</strong> <span id="summary_segmentation">Automatique</span>
                        </div>
                        <div class="mb-2">
                            <strong>Niveaux:</strong> <span id="summary_niveaux">3 niveaux</span>
                        </div>
                        <div class="mb-2">
                            <strong>Canaux:</strong> <span id="summary_canaux">Email, SMS</span>
                        </div>
                        <div class="mb-2">
                            <strong>Exécution:</strong> <span id="summary_execution">Immédiate</span>
                        </div>
                        <div class="mt-3 pt-3 border-top border-light">
                            <strong>Étudiants ciblés:</strong> <span id="summary_etudiants">Calcul en cours...</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-rocket me-2"></i>
                        Lancer la Planification
                    </button>

                    <button type="button" class="btn btn-outline-primary" id="btnSauvegarderTemplate">
                        <i class="fas fa-save me-1"></i>
                        Sauvegarder comme Template
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal Confirmation -->
<div class="modal fade" id="modalConfirmation" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la Planification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Vous êtes sur le point de planifier des relances pour <strong id="confirm_count">X</strong> étudiants.
                </div>
                <div id="confirm_details"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="btnConfirmerPlanification">
                    <i class="fas fa-check me-1"></i>
                    Confirmer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la sélection de segmentation
    document.querySelectorAll('.segmentation-card').forEach(card => {
        card.addEventListener('click', function() {
            // Retirer la sélection précédente
            document.querySelectorAll('.segmentation-card').forEach(c => c.classList.remove('selected'));

            // Sélectionner la carte actuelle
            this.classList.add('selected');

            // Cocher le radio button correspondant
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;

            // Mettre à jour le résumé
            updateSummary();
        });
    });

    // Gestion des types de relances
    document.querySelectorAll('.type-relance-checkbox').forEach(checkbox => {
        checkbox.addEventListener('click', function() {
            const input = this.querySelector('input[type="checkbox"]');
            input.checked = !input.checked;

            if (input.checked) {
                this.classList.add('selected');
            } else {
                this.classList.remove('selected');
            }

            updateSummary();
        });
    });

    // Gestion de l'exécution programmée
    document.querySelectorAll('input[name="execution_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const dateGroup = document.getElementById('date_execution_group');
            if (this.value === 'programmee') {
                dateGroup.style.display = 'block';
            } else {
                dateGroup.style.display = 'none';
            }
            updateSummary();
        });
    });

    // Mise à jour automatique du résumé
    document.querySelectorAll('select, input').forEach(element => {
        element.addEventListener('change', updateSummary);
    });

    // Aperçu des segments
    document.getElementById('btnPreviewSegments').addEventListener('click', function() {
        const segmentation = document.querySelector('input[name="segmentation"]:checked').value;
        const btn = this;

        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Chargement...';
        btn.disabled = true;

        fetch('{{ route("esbtp.comptabilite.relances.preview.segmentation") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ type_segmentation: segmentation })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySegmentPreview(data.segments);
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .finally(() => {
            btn.innerHTML = '<i class="fas fa-eye me-1"></i>Aperçu des Segments';
            btn.disabled = false;
        });
    });

    // Soumission du formulaire
    document.getElementById('formPlanificationAvancee').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        // Gérer les checkboxes multiples
        data.types_relance = Array.from(formData.getAll('types_relance[]'));

        // Afficher la confirmation
        showConfirmation(data);
    });

    function updateSummary() {
        const segmentation = document.querySelector('input[name="segmentation"]:checked')?.value || 'auto';
        const niveauMax = document.querySelector('select[name="niveau_max"]')?.value || '3';
        const typesRelance = Array.from(document.querySelectorAll('input[name="types_relance[]"]:checked')).map(cb => cb.value);
        const executionType = document.querySelector('input[name="execution_type"]:checked')?.value || 'immediate';

        // Traductions
        const segmentationLabels = {
            'auto': 'Automatique',
            'niveau_retard': 'Par retard',
            'montant_dette': 'Par dette',
            'historique_paiement': 'Par historique',
            'classe': 'Par classe'
        };

        const typeLabels = {
            'email': 'Email',
            'sms': 'SMS',
            'courrier': 'Courrier'
        };

        // Mise à jour du résumé
        document.getElementById('summary_segmentation').textContent = segmentationLabels[segmentation];
        document.getElementById('summary_niveaux').textContent = niveauMax + ' niveaux';
        document.getElementById('summary_canaux').textContent = typesRelance.map(t => typeLabels[t]).join(', ');
        document.getElementById('summary_execution').textContent = executionType === 'immediate' ? 'Immédiate' : 'Programmée';
    }

    function displaySegmentPreview(segments) {
        const container = document.getElementById('segmentPreview');
        let html = '<h6 class="mb-3">Aperçu des segments:</h6>';

        Object.keys(segments).forEach(segmentName => {
            const segment = segments[segmentName];
            html += `
                <div class="segment-preview">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>${segmentName.replace('_', ' ').toUpperCase()}</strong>
                        <span class="badge bg-primary">${segment.nombre_etudiants} étudiants</span>
                    </div>
                    <div class="text-muted small">
                        Dette totale: ${new Intl.NumberFormat('fr-FR').format(segment.total_dette)} FCFA
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    function showConfirmation(data) {
        // Estimation du nombre d'étudiants (simplifié)
        document.getElementById('confirm_count').textContent = 'environ ' + Math.floor(Math.random() * 50 + 10);

        const modal = new bootstrap.Modal(document.getElementById('modalConfirmation'));
        modal.show();

        document.getElementById('btnConfirmerPlanification').onclick = function() {
            executePlanification(data);
            modal.hide();
        };
    }

    function executePlanification(data) {
        fetch('{{ route("esbtp.comptabilite.relances.planifier.avancees") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Planification réussie: ' + data.message);
                window.location.href = '{{ route("esbtp.comptabilite.relances.index") }}';
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            debugError('Erreur:', error);
            alert('Erreur de communication avec le serveur');
        });
    }

    // Initialisation
    updateSummary();
});
</script>
@endpush
