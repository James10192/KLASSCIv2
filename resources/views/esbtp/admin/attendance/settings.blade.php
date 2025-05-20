@extends('layouts.app')

@section('title', 'Configuration du système d\'émargement')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Configuration du système d'émargement</h4>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('esbtp.admin.attendance.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Code Expiration Settings -->
                <div class="mb-4">
                    <h5>Configuration des codes d'émargement</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="code_expiration_hours" class="form-label">Durée de validité du code (heures)</label>
                            <input type="number" class="form-control" id="code_expiration_hours" name="settings[code_expiration_hours]"
                                value="{{ old('settings.code_expiration_hours', $settings['code_expiration_hours'] ?? 24) }}" min="1" max="72">
                            <small class="text-muted">Durée de validité des codes générés (1-72 heures)</small>
                        </div>
                        <div class="col-md-6">
                            <label for="max_attempts" class="form-label">Nombre maximum de tentatives</label>
                            <input type="number" class="form-control" id="max_attempts" name="settings[max_attempts]"
                                value="{{ old('settings.max_attempts', $settings['max_attempts'] ?? 3) }}" min="1" max="10">
                            <small class="text-muted">Nombre de tentatives avant blocage (1-10)</small>
                        </div>
                    </div>
                </div>

                <!-- Time Window Settings -->
                <div class="mb-4">
                    <h5>Fenêtre temporelle d'émargement</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="early_marking_minutes" class="form-label">Minutes avant le cours (anticipation)</label>
                            <input type="number" class="form-control" id="early_marking_minutes" name="settings[early_marking_minutes]"
                                value="{{ old('settings.early_marking_minutes', $settings['early_marking_minutes'] ?? 15) }}" min="0" max="60">
                            <small class="text-muted">Autoriser l'émargement X minutes avant le début du cours</small>
                        </div>
                        <div class="col-md-6">
                            <label for="late_marking_minutes" class="form-label">Minutes après le cours (retard)</label>
                            <input type="number" class="form-control" id="late_marking_minutes" name="settings[late_marking_minutes]"
                                value="{{ old('settings.late_marking_minutes', $settings['late_marking_minutes'] ?? 30) }}" min="0" max="120">
                            <small class="text-muted">Autoriser l'émargement jusqu'à X minutes après le début</small>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="mb-4">
                    <h5>Configuration des notifications</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="notify_admin_on_failure" name="settings[notify_admin_on_failure]"
                                    {{ old('settings.notify_admin_on_failure', $settings['notify_admin_on_failure'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_admin_on_failure">
                                    Notifier l'administrateur en cas d'échecs répétés
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="notify_teacher_reminder" name="settings[notify_teacher_reminder]"
                                    {{ old('settings.notify_teacher_reminder', $settings['notify_teacher_reminder'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_teacher_reminder">
                                    Envoyer des rappels aux enseignants
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="mb-4">
                    <h5>Paramètres de sécurité</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="enforce_ip_validation" name="settings[enforce_ip_validation]"
                                    {{ old('settings.enforce_ip_validation', $settings['enforce_ip_validation'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enforce_ip_validation">
                                    Activer la validation par IP
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="enforce_device_validation" name="settings[enforce_device_validation]"
                                    {{ old('settings.enforce_device_validation', $settings['enforce_device_validation'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enforce_device_validation">
                                    Activer la validation par appareil
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Form validation
        $('form').on('submit', function(e) {
            let isValid = true;

            // Validate expiration hours
            const expirationHours = $('#code_expiration_hours').val();
            if (expirationHours < 1 || expirationHours > 72) {
                alert('La durée de validité doit être comprise entre 1 et 72 heures.');
                isValid = false;
            }

            // Validate attempts
            const maxAttempts = $('#max_attempts').val();
            if (maxAttempts < 1 || maxAttempts > 10) {
                alert('Le nombre de tentatives doit être compris entre 1 et 10.');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush
