@extends('layouts.app')

@section('title', 'Configuration Matricules - Système Multi-Établissements')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="main-content">
    <!-- Header moderne -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-cogs me-2"></i>Configuration Matricules</h1>
            <p class="header-subtitle">Système Multi-Établissements • Génération des matricules étudiants</p>
        </div>
        <div class="header-actions">
            <span class="badge primary">ADMIN CONFIG</span>
        </div>
    </div>

    <!-- Section Configuration Principale -->
    <div class="card-moderne mb-lg">
        <div class="section-card-header">
            <h3 class="section-card-title">
                <i class="fas fa-sliders-h"></i>
                Configuration Système
            </h3>
        </div>
        <div class="section-card-body">
            <div class="form-grid-2">
                <div class="form-group-moderne">
                    <label class="form-label-moderne">
                        <i class="fas fa-cogs me-1"></i>Mode de génération
                    </label>
                    <select class="form-select-moderne" id="matriculeMode">
                        <option value="automatique" {{ $matriculeMode == 'automatique' ? 'selected' : '' }}>
                            <i class="fas fa-robot"></i> Automatique (selon configurations)
                        </option>
                        <option value="manuel" {{ $matriculeMode == 'manuel' ? 'selected' : '' }}>
                            <i class="fas fa-edit"></i> Manuel (saisie libre avec vérification)
                        </option>
                    </select>
                </div>
                <div class="form-group-moderne">
                    <label class="form-label-moderne">
                        <i class="fas fa-university me-1"></i>Établissement actuel
                    </label>
                    <select class="form-select-moderne" id="currentEtablissement">
                        @foreach($etablissements as $etab)
                            <option value="{{ $etab->id }}" {{ $currentEtablissementId == $etab->id ? 'selected' : '' }}>
                                {{ $etab->nom }} ({{ $etab->ville }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="alert alert-info mt-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        <strong id="modeDescription">
                            @if($matriculeMode == 'automatique')
                                <i class="fas fa-robot"></i> Mode Automatique Activé
                            @else
                                <i class="fas fa-edit"></i> Mode Manuel Activé
                            @endif
                        </strong><br>
                        <small id="modeExplanation">
                            @if($matriculeMode == 'automatique')
                                Les matricules sont générés automatiquement selon les configurations définies pour chaque niveau d'études.
                            @else
                                Les matricules sont saisis manuellement lors de l'inscription avec vérification anti-doublons automatique.
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Nomenclature Actuelle -->
    <div class="card-moderne mb-lg" id="nomenclatureSection">
        <div class="section-card-header">
            <h3 class="section-card-title">
                <i class="fas fa-eye"></i>
                Nomenclature Actuelle
            </h3>
        </div>
        <div class="section-card-body">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>Application automatique</h6>
                <p class="mb-0">Cette configuration s'appliquera automatiquement lors de la création de <strong>nouvelles inscriptions</strong> d'étudiants. Les réinscriptions ne sont pas concernées car les étudiants possèdent déjà un matricule.</p>
            </div>

            <div id="currentNomenclature">
                @if($configurations->count() > 0)
                    <div class="kpi-grid">
                        @foreach($configurations as $config)
                            <div class="kpi-card card-moderne">
                                <div class="kpi-title">{{ $config->niveau_etude_name }}</div>
                                <div class="kpi-value color-primary">
                                    @if($config->exemples_generes)
                                        <div class="mb-sm">
                                            <span class="badge success me-1"><i class="fas fa-mars"></i> {{ $config->exemples_generes['masculin'] }}</span>
                                            <span class="badge warning"><i class="fas fa-venus"></i> {{ $config->exemples_generes['feminin'] }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="kpi-trend">
                                    <small class="text-muted">
                                        {{ $config->annee_format }}{{ $config->annee_format == 2 ? ' chiffres' : ' chiffres' }} •
                                        {{ $config->numero_digits }} digits •
                                        {{ $config->etablissement_code }}
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Aucune configuration définie pour cet établissement</p>
                        <small>Les configurations sont gérées automatiquement en backend selon les niveaux d'études disponibles.</small>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Alerte si aucune configuration en mode automatique -->
    <div class="card-moderne" id="configurationAlert" style="display: {{ ($matriculeMode == 'automatique' && $configurations->count() == 0) ? 'block' : 'none' }};">
        <div class="section-card-body">
            <div class="alert alert-warning">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Configuration requise</h6>
                <p>Aucune configuration n'est définie pour cet établissement. En mode automatique, les matricules ne pourront pas être générés lors des nouvelles inscriptions.</p>
                <p class="mb-0">Contactez l'administrateur système pour configurer les nomenclatures des matricules pour chaque niveau d'études.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const matriculeModeSelect = document.getElementById('matriculeMode');
    const etablissementSelect = document.getElementById('currentEtablissement');

    if (!matriculeModeSelect || !etablissementSelect) {
        debugError('Elements not found!');
        return;
    }

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
                updateNomenclatureSection(); // Mettre à jour l'affichage selon le nouveau mode

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
            debugError(error);
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
                    // Recharger la section nomenclature sans recharger toute la page
                    updateNomenclatureSection();
                });
            } else {
                Swal.fire('Erreur', data.message, 'error');
            }
        })
        .catch(error => {
            debugError(error);
            Swal.fire('Erreur', 'Une erreur est survenue', 'error');
        });
    });

    // Fonction pour mettre à jour la description du mode
    function updateModeDescription(mode) {
        const description = document.getElementById('modeDescription');
        const explanation = document.getElementById('modeExplanation');

        if (mode === 'automatique') {
            description.innerHTML = '<i class="fas fa-robot"></i> Mode Automatique Activé';
            explanation.innerHTML = 'Les matricules sont générés automatiquement selon les configurations définies pour chaque niveau d\'études.';
        } else {
            description.innerHTML = '<i class="fas fa-edit"></i> Mode Manuel Activé';
            explanation.innerHTML = 'Les matricules sont saisis manuellement lors de l\'inscription avec vérification anti-doublons automatique.';
        }
    }

    // Initialiser l'interface

    // Charger les nomenclatures au démarrage
    updateNomenclatureSection();
});

// Fonctions utilitaires supprimées - interface simplifiée

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

// Fonction pour mettre à jour la section nomenclature
function updateNomenclatureSection() {
    const currentEtablissementId = document.getElementById('currentEtablissement').value;
    const currentMode = document.getElementById('matriculeMode').value;

    fetch('{{ route("esbtp.matricule-config.get-configurations") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            etablissement_id: currentEtablissementId
        })
    })
    .then(response => response.json())
    .then(data => {
        const nomenclatureSection = document.getElementById('currentNomenclature');

        if (data.success && data.configurations.length > 0) {
            let html = '<div class="kpi-grid">';
            data.configurations.forEach(config => {
                html += `
                    <div class="kpi-card card-moderne">
                        <div class="kpi-title">${config.niveau_etude_name}</div>
                        <div class="kpi-value color-primary">
                            <div class="mb-sm">
                                <span class="badge success me-1"><i class="fas fa-mars"></i> ${config.exemples_generes.masculin}</span>
                                <span class="badge warning"><i class="fas fa-venus"></i> ${config.exemples_generes.feminin}</span>
                            </div>
                        </div>
                        <div class="kpi-trend">
                            <small class="text-muted">
                                ${config.annee_format} chiffres • ${config.numero_digits} digits • ${config.etablissement_code}
                            </small>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            nomenclatureSection.innerHTML = html;
        } else {
            nomenclatureSection.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Aucune configuration définie pour cet établissement</p>
                    <small>Les configurations sont gérées automatiquement en backend selon les niveaux d'études disponibles.</small>
                </div>
            `;
        }

        // Afficher/masquer l'alerte de configuration manquante
        const alertSection = document.getElementById('configurationAlert');
        if (currentMode === 'automatique' && (!data.success || data.configurations.length === 0)) {
            if (alertSection) {
                alertSection.style.display = 'block';
            }
        } else {
            if (alertSection) {
                alertSection.style.display = 'none';
            }
        }
    })
    .catch(error => {
        debugError('Erreur lors de la mise à jour des nomenclatures:', error);
    });
}
</script>
@endpush