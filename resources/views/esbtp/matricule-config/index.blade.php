@extends('layouts.app')

@section('title', 'Configuration Matricules - Système Multi-Établissements')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --warning-gradient: linear-gradient(135deg, #fdbb2d 0%, #22c1c3 100%);
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.18);
        --shadow-soft: 0 8px 32px rgba(31, 38, 135, 0.37);
        --border-radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .config-container {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-soft);
    }

    .config-card {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: var(--transition);
    }

    .config-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .example-badge {
        background: var(--success-gradient);
        color: white;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        margin: 2px;
        display: inline-block;
    }

    .level-badge {
        background: var(--primary-gradient);
        color: white;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
    }

    .alert-custom {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 87, 34, 0.1) 100%);
        border: 1px solid rgba(255, 193, 7, 0.3);
        color: #856404;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .form-control, .form-select {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        transition: var(--transition);
    }

    .form-control:focus, .form-select:focus {
        background: white;
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .btn-gradient-primary {
        background: var(--primary-gradient);
        border: none;
        color: white;
        font-weight: 600;
        border-radius: 8px;
        transition: var(--transition);
    }

    .btn-gradient-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .btn-gradient-danger {
        background: var(--danger-gradient);
        border: none;
        color: white;
        font-weight: 600;
        border-radius: 8px;
        transition: var(--transition);
    }

    .btn-gradient-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(250, 112, 154, 0.4);
        color: white;
    }

    .preview-section {
        background: linear-gradient(135deg, rgba(79, 172, 254, 0.1) 0%, rgba(0, 242, 254, 0.1) 100%);
        border: 1px solid rgba(79, 172, 254, 0.3);
        border-radius: 12px;
        padding: 15px;
        margin-top: 15px;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in {
        animation: fadeInUp 0.5s ease-out;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Header avec alerte de confidentialité -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="config-container p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h1 class="h3 mb-1 text-primary fw-bold">🔧 Configuration Matricules</h1>
                        <p class="text-muted mb-0">Système Multi-Établissements • Génération des matricules étudiants</p>
                    </div>
                    <div class="text-end">
                        <span class="level-badge">ADMIN CONFIG</span>
                    </div>
                </div>

                <!-- Configuration globale du système -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-cogs me-1"></i>Mode de génération</label>
                        <select class="form-select" id="matriculeMode">
                            <option value="automatique" {{ $matriculeMode == 'automatique' ? 'selected' : '' }}>
                                🤖 Automatique (selon configurations)
                            </option>
                            <option value="manuel" {{ $matriculeMode == 'manuel' ? 'selected' : '' }}>
                                ✏️ Manuel (saisie libre avec vérification)
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-university me-1"></i>Établissement actuel</label>
                        <select class="form-select" id="currentEtablissement">
                            @foreach($etablissements as $etab)
                                <option value="{{ $etab->id }}" {{ $currentEtablissementId == $etab->id ? 'selected' : '' }}>
                                    {{ $etab->nom }} ({{ $etab->ville }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="alert-custom">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <strong id="modeDescription">
                                @if($matriculeMode == 'automatique')
                                    🤖 Mode Automatique Activé
                                @else
                                    ✏️ Mode Manuel Activé
                                @endif
                            </strong><br>
                            <small id="modeExplanation">
                                @if($matriculeMode == 'automatique')
                                    Les matricules sont générés automatiquement selon les configurations définies ci-dessous pour chaque niveau d'études.
                                @else
                                    Les matricules sont saisis manuellement lors de l'inscription avec vérification anti-doublons automatique.
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Formulaire de configuration -->
        <div class="col-lg-6">
            <div class="config-card p-4 h-100" id="configFormSection">
                <h5 class="mb-3">
                    <i class="fas fa-plus-circle text-primary me-2"></i>Configuration Automatique
                    <small class="text-muted">(Active uniquement en mode automatique)</small>
                </h5>

                <form id="configForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Niveau d'études</label>
                        <select class="form-select" name="niveau_etude_code" required>
                            <option value="">Sélectionner un niveau</option>
                            <option value="BTS">BTS (Brevet de Technicien Supérieur)</option>
                            <option value="LICENCE">LICENCE</option>
                            <option value="MASTER">MASTER</option>
                            <option value="DOCTORAT">DOCTORAT</option>
                            @foreach($niveauxEtudes as $niveau)
                                <option value="{{ $niveau->code ?? strtoupper($niveau->name) }}">
                                    {{ $niveau->name }} ({{ $niveau->type ?? 'Non défini' }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Le niveau d'études pour cette configuration</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Préfixe niveau (optionnel)</label>
                        <input type="text" class="form-control" name="prefixe" maxlength="10" placeholder="Ex: L pour Licence">
                        <small class="text-muted">Lettre(s) ajoutée(s) après le genre (M/F)</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Format année</label>
                                <select class="form-select" name="annee_format" required>
                                    <option value="2">2 chiffres (24, 25)</option>
                                    <option value="4">4 chiffres (2024, 2025)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Chiffres numéro</label>
                                <select class="form-select" name="numero_digits" required>
                                    <option value="3">3 chiffres (001)</option>
                                    <option value="4" selected>4 chiffres (0001)</option>
                                    <option value="5">5 chiffres (00001)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Code établissement</label>
                        <input type="text" class="form-control" name="etablissement_code" value="ESBTP" required maxlength="20">
                        <small class="text-muted">Généralement "ESBTP" pour cet établissement</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description (optionnel)</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Notes sur cette configuration..."></textarea>
                    </div>

                    <!-- Section de prévisualisation -->
                    <div id="previewSection" class="preview-section" style="display: none;">
                        <h6><i class="fas fa-eye text-info me-2"></i>Prévisualisation</h6>
                        <div id="previewExamples"></div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-gradient-primary flex-fill">
                            <i class="fas fa-save me-2"></i>Sauvegarder
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                            <i class="fas fa-undo me-1"></i>Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des configurations existantes -->
        <div class="col-lg-6">
            <div class="config-card p-4 h-100">
                <h5 class="mb-3"><i class="fas fa-list text-success me-2"></i>Configurations Existantes</h5>

                <div id="configsList">
                    @forelse($configurations as $config)
                        <div class="config-item mb-3 p-3" style="background: rgba(255, 255, 255, 0.5); border-radius: 8px; border: 1px solid rgba(0, 0, 0, 0.1);">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">{{ $config->niveau_etude_name }}</h6>
                                    <small class="text-muted">{{ $config->description ?? 'Aucune description' }}</small>
                                </div>
                                <button class="btn btn-sm btn-gradient-danger" onclick="deleteConfig({{ $config->id }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                            <div class="mb-2">
                                <strong>Configuration:</strong><br>
                                <small>
                                    Format: {{ $config->annee_format }} chiffres année, {{ $config->numero_digits }} chiffres numéro<br>
                                    Préfixe: {{ $config->prefixe ?? 'Aucun' }} | Établissement: {{ $config->etablissement_code }}
                                </small>
                            </div>

                            <div>
                                <strong>Exemples:</strong><br>
                                @if($config->exemples_generes)
                                    <span class="example-badge">♂️ {{ $config->exemples_generes['masculin'] }}</span>
                                    <span class="example-badge">♀️ {{ $config->exemples_generes['feminin'] }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucune configuration définie</p>
                            <small>Ajoutez votre première configuration pour commencer</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('configForm');
    const previewSection = document.getElementById('previewSection');
    const previewExamples = document.getElementById('previewExamples');
    const matriculeModeSelect = document.getElementById('matriculeMode');
    const etablissementSelect = document.getElementById('currentEtablissement');
    const configFormSection = document.getElementById('configFormSection');

    // Gestion du changement de mode
    matriculeModeSelect.addEventListener('change', function() {
        const mode = this.value;

        fetch('{{ route("esbtp.matricule-config.change-mode") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ mode: mode })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateModeDescription(mode);
                toggleConfigForm(mode === 'automatique');

                Swal.fire({
                    title: 'Mode changé!',
                    text: `Mode ${mode} activé`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Erreur', data.message, 'error');
            }
        })
        .catch(error => {
            console.error(error);
            Swal.fire('Erreur', 'Une erreur est survenue', 'error');
        });
    });

    // Gestion du changement d'établissement
    etablissementSelect.addEventListener('change', function() {
        const etablissementId = this.value;

        fetch('{{ route("esbtp.matricule-config.change-etablissement") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ etablissement_id: etablissementId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Établissement changé!',
                    text: `Configurations pour ${data.etablissement}`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload(); // Recharger pour afficher les configs de cet établissement
                });
            } else {
                Swal.fire('Erreur', data.message, 'error');
            }
        })
        .catch(error => {
            console.error(error);
            Swal.fire('Erreur', 'Une erreur est survenue', 'error');
        });
    });

    // Fonction pour mettre à jour la description du mode
    function updateModeDescription(mode) {
        const description = document.getElementById('modeDescription');
        const explanation = document.getElementById('modeExplanation');

        if (mode === 'automatique') {
            description.innerHTML = '🤖 Mode Automatique Activé';
            explanation.innerHTML = 'Les matricules sont générés automatiquement selon les configurations définies ci-dessous pour chaque niveau d\'études.';
        } else {
            description.innerHTML = '✏️ Mode Manuel Activé';
            explanation.innerHTML = 'Les matricules sont saisis manuellement lors de l\'inscription avec vérification anti-doublons automatique.';
        }
    }

    // Fonction pour activer/désactiver le formulaire de configuration
    function toggleConfigForm(enabled) {
        const inputs = configFormSection.querySelectorAll('input, select, textarea, button');
        inputs.forEach(input => {
            input.disabled = !enabled;
        });

        if (enabled) {
            configFormSection.style.opacity = '1';
            configFormSection.style.pointerEvents = 'auto';
        } else {
            configFormSection.style.opacity = '0.5';
            configFormSection.style.pointerEvents = 'none';
        }
    }

    // Initialiser l'état du formulaire selon le mode actuel
    const currentMode = '{{ $matriculeMode }}';
    toggleConfigForm(currentMode === 'automatique');

    // Prévisualisation en temps réel
    form.addEventListener('change', updatePreview);
    form.addEventListener('keyup', debounce(updatePreview, 300));

    function updatePreview() {
        const formData = new FormData(form);

        if (!formData.get('niveau_etude_code') || !formData.get('annee_format') || !formData.get('numero_digits') || !formData.get('etablissement_code')) {
            previewSection.style.display = 'none';
            return;
        }

        fetch('{{ route("esbtp.matricule-config.preview") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                prefixe: formData.get('prefixe'),
                annee_format: parseInt(formData.get('annee_format')),
                numero_digits: parseInt(formData.get('numero_digits')),
                etablissement_code: formData.get('etablissement_code')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                previewExamples.innerHTML = `
                    <span class="example-badge">♂️ ${data.exemples.masculin}</span>
                    <span class="example-badge">♀️ ${data.exemples.feminin}</span>
                `;
                previewSection.style.display = 'block';
                previewSection.classList.add('fade-in');
            }
        })
        .catch(console.error);
    }

    // Soumission du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.niveau_etude_name = form.querySelector('select[name="niveau_etude_code"] option:checked').text;

        fetch('{{ route("esbtp.matricule-config.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Succès!',
                    text: data.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Erreur', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Erreur', 'Une erreur est survenue', 'error');
            console.error(error);
        });
    });
});

function deleteConfig(id) {
    Swal.fire({
        title: 'Êtes-vous sûr?',
        text: 'Cette action supprimera définitivement la configuration',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/esbtp/matricule-config/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire('Erreur', data.message, 'error');
                }
            });
        }
    });
}

function resetForm() {
    document.getElementById('configForm').reset();
    document.getElementById('previewSection').style.display = 'none';
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>
@endsection