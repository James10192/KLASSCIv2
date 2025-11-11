@extends('layouts.app')

@section('title', 'Configuration Paywall - Système Multi-Établissements')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div class="main-content">
    <!-- Header moderne -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-shield-alt me-2"></i>Configuration Paywall</h1>
            <p class="header-subtitle">Système Multi-Établissements • Gestion des abonnements et limites</p>
        </div>
        <div class="header-actions">
            <span class="badge {{ $status['is_blocked'] ? 'danger' : 'success' }}">
                {{ $status['is_blocked'] ? 'BLOQUÉ' : 'ACTIF' }}
            </span>
        </div>
    </div>

    <!-- Section Statut Actuel -->
    <div class="card-moderne mb-lg">
        <div class="section-card-header">
            <h3 class="section-card-title">
                <i class="fas fa-chart-line"></i>
                Statut Actuel - {{ $etablissement->nom ?? 'Établissement' }}
            </h3>
        </div>
        <div class="section-card-body">
            @if($status['is_blocked'])
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Accès Bloqué :</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($status['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(count($status['warnings']) > 0)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Avertissements :</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($status['warnings'] as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card {{ $currentStats['total_users'] > $paywallConfig['max_users'] ? 'danger' : ($currentStats['total_users'] >= $paywallConfig['max_users'] * 0.9 ? 'warning' : 'success') }}">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ $currentStats['total_users'] }}/{{ $paywallConfig['max_users'] }}</div>
                        <div class="stat-label">Utilisateurs</div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: {{ min(($currentStats['total_users'] / $paywallConfig['max_users']) * 100, 100) }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="stat-card {{ $currentStats['total_inscriptions_current_year'] > $paywallConfig['max_inscriptions_per_year'] ? 'danger' : ($currentStats['total_inscriptions_current_year'] >= $paywallConfig['max_inscriptions_per_year'] * 0.9 ? 'warning' : 'success') }}">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ $currentStats['total_inscriptions_current_year'] }}/{{ $paywallConfig['max_inscriptions_per_year'] }}</div>
                        <div class="stat-label">Inscriptions {{ $currentStats['current_year_name'] ?? 'année courante' }}</div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: {{ min(($currentStats['total_inscriptions_current_year'] / $paywallConfig['max_inscriptions_per_year']) * 100, 100) }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="stat-card {{ $status['is_expired'] ? 'danger' : ($status['days_remaining'] && $status['days_remaining'] <= 7 ? 'warning' : 'success') }}">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">
                            @if($paywallConfig['subscription_end'])
                                @if($status['is_expired'])
                                    EXPIRÉ
                                @elseif($status['days_remaining'])
                                    {{ $status['days_remaining'] }}j
                                @else
                                    Valide
                                @endif
                            @else
                                Illimité
                            @endif
                        </div>
                        <div class="stat-label">Abonnement</div>
                        @if($paywallConfig['subscription_end'])
                            <div class="stat-date">
                                Jusqu'au {{ \Carbon\Carbon::parse($paywallConfig['subscription_end'])->format('d/m/Y') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ number_format($paywallConfig['plan_price'], 0, ',', ' ') }}</div>
                        <div class="stat-label">{{ $paywallConfig['plan_name'] }}</div>
                        <div class="stat-date">FCFA</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Configuration -->
    <div class="card-moderne mb-lg">
        <div class="section-card-header">
            <h3 class="section-card-title">
                <i class="fas fa-cogs"></i>
                Configuration Paywall
            </h3>
        </div>
        <div class="section-card-body">
            <form id="paywallConfigForm">
                @csrf
                <div class="form-grid-2">
                    <div class="form-group-moderne">
                        <label class="form-label-moderne">
                            <i class="fas fa-power-off me-1"></i>Statut du paywall
                        </label>
                        <div class="form-switch-container">
                            <input type="checkbox"
                                   class="form-switch"
                                   id="paywall_active"
                                   name="is_active"
                                   value="1"
                                   {{ $paywallConfig['is_active'] ? 'checked' : '' }}>
                            <label for="paywall_active" class="form-switch-label">
                                <span class="switch-text-on">Activé</span>
                                <span class="switch-text-off">Désactivé</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group-moderne">
                        <label class="form-label-moderne">
                            <i class="fas fa-calendar-check me-1"></i>Date d'expiration
                        </label>
                        <input type="date"
                               class="form-input-moderne"
                               id="subscription_end"
                               name="subscription_end"
                               value="{{ $paywallConfig['subscription_end'] }}">
                        <small class="form-help">Laissez vide pour un abonnement illimité</small>
                    </div>

                    <div class="form-group-moderne">
                        <label class="form-label-moderne">
                            <i class="fas fa-users me-1"></i>Limite d'utilisateurs
                        </label>
                        <input type="number"
                               class="form-input-moderne"
                               id="max_users"
                               name="max_users"
                               value="{{ $paywallConfig['max_users'] }}"
                               min="1"
                               required>
                        <small class="form-help">Nombre maximum d'enseignants, coordinateurs et secrétaires</small>
                    </div>

                    <div class="form-group-moderne">
                        <label class="form-label-moderne">
                            <i class="fas fa-user-plus me-1"></i>Limite d'inscriptions par année
                        </label>
                        <input type="number"
                               class="form-input-moderne"
                               id="max_inscriptions_per_year"
                               name="max_inscriptions_per_year"
                               value="{{ $paywallConfig['max_inscriptions_per_year'] }}"
                               min="1"
                               required>
                        <small class="form-help">Nombre maximum d'inscriptions actives pour l'année universitaire courante</small>
                    </div>

                    <div class="form-group-moderne">
                        <label class="form-label-moderne">
                            <i class="fas fa-crown me-1"></i>Plan d'abonnement
                        </label>
                        <select class="form-select-moderne"
                                id="subscription_plan"
                                name="subscription_plan"
                                onchange="updatePlanFields()">
                            <option value="custom" {{ $paywallConfig['plan_name'] && !in_array($paywallConfig['plan_name'], ['Plan Essentiel', 'Plan Pro', 'Plan Elite']) ? 'selected' : '' }}>Configuration personnalisée</option>
                            <option value="essentiel" {{ $paywallConfig['plan_name'] == 'Plan Essentiel' ? 'selected' : '' }}>Plan Essentiel</option>
                            <option value="pro" {{ $paywallConfig['plan_name'] == 'Plan Pro' ? 'selected' : '' }}>Plan Pro</option>
                            <option value="elite" {{ $paywallConfig['plan_name'] == 'Plan Elite' ? 'selected' : '' }}>Plan Elite</option>
                        </select>
                        <small class="form-help">Sélectionnez un plan prédéfini ou configurez manuellement</small>
                    </div>

                    <div class="form-group-moderne">
                        <label class="form-label-moderne">
                            <i class="fas fa-tag me-1"></i>Nom du plan
                        </label>
                        <input type="text"
                               class="form-input-moderne"
                               id="plan_name"
                               name="plan_name"
                               value="{{ $paywallConfig['plan_name'] }}"
                               required>
                    </div>

                    <div class="form-group-moderne">
                        <label class="form-label-moderne">
                            <i class="fas fa-money-bill me-1"></i>Prix (FCFA)
                        </label>
                        <input type="number"
                               class="form-input-moderne"
                               id="plan_price"
                               name="plan_price"
                               value="{{ $paywallConfig['plan_price'] }}"
                               min="0"
                               step="1000"
                               required>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Sauvegarder Configuration
                    </button>

                    @if($paywallConfig['subscription_end'])
                        <button type="button" class="btn btn-success" onclick="showExtendModal()">
                            <i class="fas fa-calendar-plus me-2"></i>Prolonger Abonnement
                        </button>
                    @endif

                    <button type="button" class="btn btn-warning" onclick="generateEmergencyCode()">
                        <i class="fas fa-key me-2"></i>Générer Code d'Urgence
                    </button>
                </div>

                <!-- Section Code d'Urgence -->
                <div id="emergencyCodeSection" class="alert alert-warning mt-4" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-key me-2"></i>Code d'Urgence Généré
                            </h5>
                            <p class="mb-2">
                                <strong>Code :</strong>
                                <span id="emergencyCodeDisplay" class="text-monospace fs-5 fw-bold"></span>
                                <button type="button" class="btn btn-sm btn-outline-dark ms-2" onclick="copyEmergencyCode()">
                                    <i class="fas fa-copy"></i> Copier
                                </button>
                            </p>
                            <p class="mb-2">
                                <strong>URL d'accès :</strong><br>
                                <code id="emergencyUrlDisplay" class="user-select-all"></code>
                                <button type="button" class="btn btn-sm btn-outline-dark ms-2" onclick="copyEmergencyUrl()">
                                    <i class="fas fa-copy"></i> Copier URL
                                </button>
                            </p>
                            <p class="mb-0 small">
                                <i class="fas fa-info-circle me-1"></i>
                                Ce code permet à l'établissement d'accéder temporairement au système (1 heure) même si le paywall est bloqué.
                                <strong>À utiliser uniquement en cas d'urgence.</strong>
                            </p>
                        </div>
                        <button type="button" class="btn-close" onclick="hideEmergencyCode()"></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de prolongation -->
<div class="modal fade" id="extendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus me-2"></i>Prolonger l'abonnement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="extendForm">
                    @csrf
                    <div class="form-group-moderne">
                        <label class="form-label-moderne">Durée de prolongation</label>
                        <select class="form-select-moderne" name="months" required>
                            <option value="1">1 mois</option>
                            <option value="3">3 mois</option>
                            <option value="6" selected>6 mois</option>
                            <option value="12">12 mois</option>
                            <option value="24">24 mois</option>
                        </select>
                    </div>

                    @if($paywallConfig['subscription_end'])
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Date actuelle d'expiration :</strong>
                            {{ \Carbon\Carbon::parse($paywallConfig['subscription_end'])->format('d/m/Y') }}
                        </div>
                    @endif
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" onclick="extendSubscription()">
                    <i class="fas fa-check me-2"></i>Prolonger
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSS spécifique -->
<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 2px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.stat-card.success {
    border-color: #10b981;
    background: linear-gradient(135deg, #f0fdf4, #ffffff);
}

.stat-card.warning {
    border-color: #f59e0b;
    background: linear-gradient(135deg, #fffbeb, #ffffff);
}

.stat-card.danger {
    border-color: #ef4444;
    background: linear-gradient(135deg, #fef2f2, #ffffff);
}

.stat-card.info {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #eff6ff, #ffffff);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-card.success .stat-icon {
    background: linear-gradient(135deg, #10b981, #059669);
}

.stat-card.warning .stat-icon {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.stat-card.danger .stat-icon {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.stat-card.info .stat-icon {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: bold;
    color: #1f2937;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
    font-weight: 500;
}

.stat-date {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 0.25rem;
}

.stat-progress {
    width: 100%;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    margin-top: 0.5rem;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    transition: width 0.3s ease;
    border-radius: 3px;
}

.stat-card.warning .progress-bar {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.stat-card.danger .progress-bar {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.form-switch-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.form-switch {
    width: 60px;
    height: 30px;
    -webkit-appearance: none;
    appearance: none;
    background: #e5e7eb;
    border-radius: 15px;
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
}

.form-switch:checked {
    background: #10b981;
}

.form-switch::before {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 24px;
    height: 24px;
    background: white;
    border-radius: 50%;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.form-switch:checked::before {
    transform: translateX(30px);
}

.form-switch-label {
    font-weight: 500;
    color: #374151;
    margin: 0;
}

.form-help {
    color: #6b7280;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: block;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // DEBUG: Afficher les informations de chargement de la page
    const pageLoadTime = new Date().toISOString();
    const pageId = Math.random().toString(36).substr(2, 9);

    debugLog('🔥 PAGE PAYWALL-CONFIG CHARGÉE', {
        timestamp: pageLoadTime,
        pageId: pageId,
        url: window.location.href,
        userAgent: navigator.userAgent,
        fromCache: performance.navigation.type === 2 ? 'YES' : 'NO'
    });


    // Configuration form
    document.getElementById('paywallConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveConfiguration();
    });
});

// Plan templates
const planTemplates = {
    essentiel: {
        name: 'Plan Essentiel',
        price: 1200000,
        max_users: 20,
        max_inscriptions_per_year: 700
    },
    pro: {
        name: 'Plan Pro',
        price: 2400000,
        max_users: 30,
        max_inscriptions_per_year: 3000
    },
    elite: {
        name: 'Plan Elite',
        price: 4800000,
        max_users: 999999,
        max_inscriptions_per_year: 999999
    }
};

function updatePlanFields() {
    const planSelect = document.getElementById('subscription_plan');
    const selectedPlan = planSelect.value;

    if (selectedPlan !== 'custom' && planTemplates[selectedPlan]) {
        const template = planTemplates[selectedPlan];

        // Update form fields
        document.getElementById('plan_name').value = template.name;
        document.getElementById('plan_price').value = template.price;
        document.getElementById('max_users').value = template.max_users;
        document.getElementById('max_inscriptions_per_year').value = template.max_inscriptions_per_year;

        // Show feedback
        debugLog('Plan template applied:', template.name);
    }
}

function saveConfiguration() {
    debugLog('saveConfiguration called');

    // Empêcher les multiples clics
    const submitBtn = document.querySelector('#paywallConfigForm button[type="submit"]');
    if (submitBtn.disabled) {
        debugLog('Save already in progress, ignoring click');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sauvegarde...';

    const form = document.getElementById('paywallConfigForm');
    const formData = new FormData(form);

    // Convert form data to JSON
    const data = {};
    for (let [key, value] of formData.entries()) {
        if (key === 'is_active') {
            data[key] = true;
        } else {
            data[key] = value;
        }
    }

    // If checkbox is not checked, set is_active to false
    if (!formData.has('is_active')) {
        data['is_active'] = false;
    }

    debugLog('Data to send:', data);

    fetch('{{ route("esbtp.paywall-config.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        debugLog('Response:', data);
        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Sauvegarder Configuration';

        if (data.success) {
            alert('Succès: ' + data.message);
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        debugError('Error:', error);
        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Sauvegarder Configuration';

        alert('Erreur lors de la sauvegarde: ' + error.message);
    });
}

function showExtendModal() {
    new bootstrap.Modal(document.getElementById('extendModal')).show();
}

function extendSubscription() {
    const form = document.getElementById('extendForm');
    const formData = new FormData(form);

    fetch('{{ route("esbtp.paywall-config.extend") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Succès',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: data.message
            });
        }
    })
    .catch(error => {
        debugError('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Erreur lors de la prolongation'
        });
    });
}

function generateEmergencyCode() {
    fetch('{{ route("esbtp.paywall-config.generate-emergency") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('emergencyCodeDisplay').textContent = data.code;
            document.getElementById('emergencyUrlDisplay').textContent = data.url;
            document.getElementById('emergencyCodeSection').style.display = 'block';

            // Auto-hide after 30 minutes for security
            setTimeout(hideEmergencyCode, 30 * 60 * 1000);
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        debugError('Error:', error);
        alert('Erreur lors de la génération du code d\'urgence');
    });
}

function copyEmergencyCode() {
    const code = document.getElementById('emergencyCodeDisplay').textContent;
    navigator.clipboard.writeText(code).then(() => {
        // Show brief success feedback
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copié!';
        btn.classList.remove('btn-outline-dark');
        btn.classList.add('btn-success');

        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-dark');
        }, 2000);
    });
}

function copyEmergencyUrl() {
    const url = document.getElementById('emergencyUrlDisplay').textContent;
    navigator.clipboard.writeText(url).then(() => {
        // Show brief success feedback
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copié!';
        btn.classList.remove('btn-outline-dark');
        btn.classList.add('btn-success');

        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-dark');
        }, 2000);
    });
}

function hideEmergencyCode() {
    document.getElementById('emergencyCodeSection').style.display = 'none';
}
</script>
@endsection