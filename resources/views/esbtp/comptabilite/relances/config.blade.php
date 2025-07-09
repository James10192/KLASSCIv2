@extends('layouts.app')

@section('title', 'Configuration des Relances')

@push('styles')
<style>
.template-editor {
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.variable-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 2px 8px;
    border-radius: 15px;
    font-size: 0.875rem;
    margin: 2px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.variable-tag:hover {
    background: #1976d2;
    color: white;
}

.config-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.preview-container {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-top: 15px;
}

.template-tabs .nav-link {
    border-radius: 10px 10px 0 0;
    border: 1px solid transparent;
    color: #495057;
}

.template-tabs .nav-link.active {
    background: white;
    border-color: #dee2e6 #dee2e6 white;
    color: #007bff;
    font-weight: 600;
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
                        <i class="fas fa-cog text-primary me-2"></i>
                        Configuration des Relances
                    </h1>
                    <p class="text-muted mb-0">Gestion des templates et paramètres de relance</p>
                </div>
                <div>
                    <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Retour aux relances
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Templates de relance -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        Templates de Relance
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Tabs pour les types de templates -->
                    <ul class="nav nav-tabs template-tabs mb-3" id="templateTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                                <i class="fas fa-envelope me-1"></i>
                                Templates Email
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms" type="button" role="tab">
                                <i class="fas fa-sms me-1"></i>
                                Templates SMS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="courrier-tab" data-bs-toggle="tab" data-bs-target="#courrier" type="button" role="tab">
                                <i class="fas fa-file-pdf me-1"></i>
                                Templates Courrier
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="templateTabsContent">
                        <!-- Templates Email -->
                        <div class="tab-pane fade show active" id="email" role="tabpanel">
                            <form id="formTemplatesEmail">
                                @foreach([1 => '1er rappel', 2 => '2ème rappel', 3 => 'Dernière relance'] as $niveau => $label)
                                    <div class="template-editor mb-4">
                                        <div class="d-flex justify-content-between align-items-center bg-light p-3 border-bottom">
                                            <h6 class="mb-0">
                                                <span class="badge bg-{{ $niveau == 1 ? 'success' : ($niveau == 2 ? 'warning' : 'danger') }} me-2">
                                                    Niveau {{ $niveau }}
                                                </span>
                                                {{ $label }}
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="previewTemplate('email', {{ $niveau }})">
                                                <i class="fas fa-eye me-1"></i>
                                                Aperçu
                                            </button>
                                        </div>

                                        <div class="p-3">
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <label for="email_sujet_{{ $niveau }}" class="form-label">Sujet de l'email</label>
                                                    <input type="text" class="form-control" id="email_sujet_{{ $niveau }}"
                                                           name="email_sujet[{{ $niveau }}]"
                                                           value="{{ $templates['email'][$niveau]['sujet'] ?? '' }}"
                                                           placeholder="Ex: Rappel de paiement - ESBTP">
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-12">
                                                    <label for="email_contenu_{{ $niveau }}" class="form-label">Contenu de l'email</label>
                                                    <textarea class="form-control" id="email_contenu_{{ $niveau }}"
                                                              name="email_contenu[{{ $niveau }}]" rows="8"
                                                              placeholder="Contenu du template avec variables...">{!! $templates['email'][$niveau]['contenu'] ?? '' !!}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        Sauvegarder les Templates Email
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Templates SMS -->
                        <div class="tab-pane fade" id="sms" role="tabpanel">
                            <form id="formTemplatesSMS">
                                @foreach([1 => '1er rappel', 2 => '2ème rappel', 3 => 'Dernière relance'] as $niveau => $label)
                                    <div class="template-editor mb-4">
                                        <div class="d-flex justify-content-between align-items-center bg-light p-3 border-bottom">
                                            <h6 class="mb-0">
                                                <span class="badge bg-{{ $niveau == 1 ? 'success' : ($niveau == 2 ? 'warning' : 'danger') }} me-2">
                                                    Niveau {{ $niveau }}
                                                </span>
                                                {{ $label }}
                                            </h6>
                                            <div>
                                                <small class="text-muted me-3" id="sms_count_{{ $niveau }}">0/160 caractères</small>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="previewTemplate('sms', {{ $niveau }})">
                                                    <i class="fas fa-eye me-1"></i>
                                                    Aperçu
                                                </button>
                                            </div>
                                        </div>

                                        <div class="p-3">
                                            <label for="sms_contenu_{{ $niveau }}" class="form-label">Message SMS</label>
                                            <textarea class="form-control sms-template" id="sms_contenu_{{ $niveau }}"
                                                      name="sms_contenu[{{ $niveau }}]" rows="4" maxlength="160"
                                                      data-counter="sms_count_{{ $niveau }}"
                                                      placeholder="Message SMS court avec variables...">{!! $templates['sms'][$niveau]['contenu'] ?? '' !!}</textarea>
                                            <small class="text-muted">Maximum 160 caractères. Utilisez les variables pour personnaliser.</small>
                                        </div>
                                    </div>
                                @endforeach

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        Sauvegarder les Templates SMS
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Templates Courrier -->
                        <div class="tab-pane fade" id="courrier" role="tabpanel">
                            <form id="formTemplatesCourrier">
                                @foreach([1 => '1er rappel', 2 => '2ème rappel', 3 => 'Dernière relance'] as $niveau => $label)
                                    <div class="template-editor mb-4">
                                        <div class="d-flex justify-content-between align-items-center bg-light p-3 border-bottom">
                                            <h6 class="mb-0">
                                                <span class="badge bg-{{ $niveau == 1 ? 'success' : ($niveau == 2 ? 'warning' : 'danger') }} me-2">
                                                    Niveau {{ $niveau }}
                                                </span>
                                                {{ $label }}
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="previewTemplate('courrier', {{ $niveau }})">
                                                <i class="fas fa-eye me-1"></i>
                                                Aperçu PDF
                                            </button>
                                        </div>

                                        <div class="p-3">
                                            <label for="courrier_contenu_{{ $niveau }}" class="form-label">Contenu du courrier</label>
                                            <textarea class="form-control" id="courrier_contenu_{{ $niveau }}"
                                                      name="courrier_contenu[{{ $niveau }}]" rows="12"
                                                      placeholder="Contenu du courrier avec mise en forme HTML...">{!! $templates['courrier'][$niveau]['contenu'] ?? '' !!}</textarea>
                                        </div>
                                    </div>
                                @endforeach

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        Sauvegarder les Templates Courrier
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Variables disponibles -->
            <div class="card sticky-top">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tags me-2"></i>
                        Variables Disponibles
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Cliquez sur une variable pour l'insérer dans le template actif.</p>

                    <div class="mb-3">
                        <h6 class="fw-bold">Informations Étudiant</h6>
                        <div class="d-flex flex-wrap">
                            <span class="variable-tag" onclick="insertVariable('{nom}')">{nom}</span>
                            <span class="variable-tag" onclick="insertVariable('{prenom}')">{prenom}</span>
                            <span class="variable-tag" onclick="insertVariable('{nom_complet}')">{nom_complet}</span>
                            <span class="variable-tag" onclick="insertVariable('{email}')">{email}</span>
                            <span class="variable-tag" onclick="insertVariable('{telephone}')">{telephone}</span>
                            <span class="variable-tag" onclick="insertVariable('{numero_etudiant}')">{numero_etudiant}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="fw-bold">Informations Financières</h6>
                        <div class="d-flex flex-wrap">
                            <span class="variable-tag" onclick="insertVariable('{montant_dette}')">{montant_dette}</span>
                            <span class="variable-tag" onclick="insertVariable('{montant_dette_formatte}')">{montant_dette_formatte}</span>
                            <span class="variable-tag" onclick="insertVariable('{date_echeance}')">{date_echeance}</span>
                            <span class="variable-tag" onclick="insertVariable('{jours_retard}')">{jours_retard}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="fw-bold">Informations Relance</h6>
                        <div class="d-flex flex-wrap">
                            <span class="variable-tag" onclick="insertVariable('{niveau_relance}')">{niveau_relance}</span>
                            <span class="variable-tag" onclick="insertVariable('{type_relance}')">{type_relance}</span>
                            <span class="variable-tag" onclick="insertVariable('{date_relance}')">{date_relance}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="fw-bold">Informations Établissement</h6>
                        <div class="d-flex flex-wrap">
                            <span class="variable-tag" onclick="insertVariable('{nom_ecole}')">{nom_ecole}</span>
                            <span class="variable-tag" onclick="insertVariable('{adresse_ecole}')">{adresse_ecole}</span>
                            <span class="variable-tag" onclick="insertVariable('{telephone_ecole}')">{telephone_ecole}</span>
                            <span class="variable-tag" onclick="insertVariable('{email_ecole}')">{email_ecole}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="fw-bold">Dates et Formatage</h6>
                        <div class="d-flex flex-wrap">
                            <span class="variable-tag" onclick="insertVariable('{date_aujourdhui}')">{date_aujourdhui}</span>
                            <span class="variable-tag" onclick="insertVariable('{heure_actuelle}')">{heure_actuelle}</span>
                            <span class="variable-tag" onclick="insertVariable('{annee_academique}')">{annee_academique}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paramètres de relance -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sliders-h me-2"></i>
                        Paramètres de Relance
                    </h5>
                </div>
                <div class="card-body">
                    <form id="formParametres">
                        <div class="mb-3">
                            <label for="delai_niveau_1" class="form-label">Délai niveau 1 (jours)</label>
                            <input type="number" class="form-control" id="delai_niveau_1" name="delai_niveau_1"
                                   value="{{ $parametres['delai_niveau_1'] ?? 30 }}" min="1" max="365">
                            <small class="text-muted">Jours de retard avant 1er rappel</small>
                        </div>

                        <div class="mb-3">
                            <label for="delai_niveau_2" class="form-label">Délai niveau 2 (jours)</label>
                            <input type="number" class="form-control" id="delai_niveau_2" name="delai_niveau_2"
                                   value="{{ $parametres['delai_niveau_2'] ?? 45 }}" min="1" max="365">
                            <small class="text-muted">Jours de retard avant 2ème rappel</small>
                        </div>

                        <div class="mb-3">
                            <label for="delai_niveau_3" class="form-label">Délai niveau 3 (jours)</label>
                            <input type="number" class="form-control" id="delai_niveau_3" name="delai_niveau_3"
                                   value="{{ $parametres['delai_niveau_3'] ?? 60 }}" min="1" max="365">
                            <small class="text-muted">Jours de retard avant dernière relance</small>
                        </div>

                        <div class="mb-3">
                            <label for="montant_minimum" class="form-label">Montant minimum (FCFA)</label>
                            <input type="number" class="form-control" id="montant_minimum" name="montant_minimum"
                                   value="{{ $parametres['montant_minimum'] ?? 10000 }}" min="0" step="1000">
                            <small class="text-muted">Montant minimum pour déclencher une relance</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="relances_automatiques"
                                       name="relances_automatiques" {{ ($parametres['relances_automatiques'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="relances_automatiques">
                                    Activer les relances automatiques
                                </label>
                            </div>
                            <small class="text-muted">Planification automatique des relances selon les délais</small>
                        </div>

                        <div class="mb-3">
                            <label for="heure_envoi" class="form-label">Heure d'envoi automatique</label>
                            <input type="time" class="form-control" id="heure_envoi" name="heure_envoi"
                                   value="{{ $parametres['heure_envoi'] ?? '09:00' }}">
                            <small class="text-muted">Heure quotidienne pour l'envoi automatique</small>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-save me-1"></i>
                            Sauvegarder Paramètres
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aperçu Template -->
<div class="modal fade" id="modalApercu" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>
                    Aperçu du Template
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="apercu-content">
                    <!-- Contenu de l'aperçu chargé dynamiquement -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="envoyerTestTemplate()">
                    <i class="fas fa-paper-plane me-1"></i>
                    Envoyer un test
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Compteur de caractères pour SMS
    document.querySelectorAll('.sms-template').forEach(textarea => {
        const counterId = textarea.getAttribute('data-counter');
        const counter = document.getElementById(counterId);

        function updateCounter() {
            const length = textarea.value.length;
            counter.textContent = `${length}/160 caractères`;
            counter.className = length > 160 ? 'text-danger me-3' : 'text-muted me-3';
        }

        textarea.addEventListener('input', updateCounter);
        updateCounter(); // Initial count
    });

    // Soumission des formulaires
    ['Email', 'SMS', 'Courrier'].forEach(type => {
        document.getElementById(`formTemplates${type}`).addEventListener('submit', function(e) {
            e.preventDefault();
            sauvegarderTemplates(type.toLowerCase());
        });
    });

    document.getElementById('formParametres').addEventListener('submit', function(e) {
        e.preventDefault();
        sauvegarderParametres();
    });
});

let currentFocusedTextarea = null;

// Détection du textarea actif pour l'insertion de variables
document.addEventListener('focusin', function(e) {
    if (e.target.tagName === 'TEXTAREA') {
        currentFocusedTextarea = e.target;
    }
});

function insertVariable(variable) {
    if (!currentFocusedTextarea) {
        showAlert('warning', 'Veuillez cliquer dans un champ de texte avant d\'insérer une variable.');
        return;
    }

    const start = currentFocusedTextarea.selectionStart;
    const end = currentFocusedTextarea.selectionEnd;
    const text = currentFocusedTextarea.value;

    currentFocusedTextarea.value = text.substring(0, start) + variable + text.substring(end);
    currentFocusedTextarea.focus();

    // Repositionner le curseur après la variable insérée
    const newPos = start + variable.length;
    currentFocusedTextarea.setSelectionRange(newPos, newPos);

    // Déclencher l'événement input pour mettre à jour les compteurs
    currentFocusedTextarea.dispatchEvent(new Event('input'));
}

function sauvegarderTemplates(type) {
    const form = document.getElementById(`formTemplates${type.charAt(0).toUpperCase() + type.slice(1)}`);
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');

    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sauvegarde...';
    submitBtn.disabled = true;

    fetch(`{{ route('esbtp.comptabilite.relances.config.templates') }}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        showAlert(data.success ? 'success' : 'error', data.message);
    })
    .catch(error => {
        showAlert('error', 'Erreur lors de la sauvegarde');
    })
    .finally(() => {
        submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Sauvegarder les Templates ' + type.charAt(0).toUpperCase() + type.slice(1);
        submitBtn.disabled = false;
    });
}

function sauvegarderParametres() {
    const form = document.getElementById('formParametres');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');

    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sauvegarde...';
    submitBtn.disabled = true;

    fetch(`{{ route('esbtp.comptabilite.relances.config.parametres') }}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        showAlert(data.success ? 'success' : 'error', data.message);
    })
    .catch(error => {
        showAlert('error', 'Erreur lors de la sauvegarde');
    })
    .finally(() => {
        submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Sauvegarder Paramètres';
        submitBtn.disabled = false;
    });
}

function previewTemplate(type, niveau) {
    const container = document.getElementById('apercu-content');
    const modal = new bootstrap.Modal(document.getElementById('modalApercu'));

    container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Génération de l\'aperçu...</div>';
    modal.show();

    // Récupérer le contenu du template actuel
    let contenu = '';
    if (type === 'email') {
        const sujet = document.getElementById(`email_sujet_${niveau}`).value;
        contenu = document.getElementById(`email_contenu_${niveau}`).value;
    } else if (type === 'sms') {
        contenu = document.getElementById(`sms_contenu_${niveau}`).value;
    } else if (type === 'courrier') {
        contenu = document.getElementById(`courrier_contenu_${niveau}`).value;
    }

    fetch(`{{ route('esbtp.comptabilite.relances.config.preview') }}`, {
        method: 'POST',
        body: JSON.stringify({
            type: type,
            niveau: niveau,
            contenu: contenu,
            sujet: type === 'email' ? document.getElementById(`email_sujet_${niveau}`).value : null
        }),
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.text())
    .then(html => {
        container.innerHTML = html;
    })
    .catch(error => {
        container.innerHTML = '<div class="text-center text-danger">Erreur lors de la génération de l\'aperçu</div>';
    });
}

function envoyerTestTemplate() {
    showAlert('info', 'Fonctionnalité d\'envoi de test en développement...');
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'danger')} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endpush
