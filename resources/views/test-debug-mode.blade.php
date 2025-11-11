@extends('layouts.app')

@section('title', 'Test Debug Mode')

@section('content')
<div class="container mt-5">
    <div class="main-card">
        <div class="main-card-header">
            <div class="main-card-title">
                <i class="fas fa-bug"></i>
                Test Debug Mode
            </div>
            <div class="main-card-subtitle">Vérification du masquage des logs en production</div>
        </div>
        <div class="main-card-body">
            <div class="alert alert-info mb-4">
                <h5><i class="fas fa-info-circle me-2"></i>État actuel</h5>
                <p class="mb-2"><strong>APP_DEBUG:</strong> {{ config('app.debug') ? 'true' : 'false' }}</p>
                <p class="mb-0"><strong>window.DEBUG_MODE:</strong> <span id="debug-mode-value">Loading...</span></p>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-terminal me-2"></i>Console Logs
                        </div>
                        <div class="card-body">
                            <p class="small text-muted">Les logs suivants devraient apparaître uniquement si APP_DEBUG=true</p>
                            <button onclick="testConsoleLogs()" class="btn btn-primary btn-sm">
                                <i class="fas fa-play me-1"></i>Tester Console Logs
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-warning text-dark">
                            <i class="fas fa-exclamation-triangle me-2"></i>Alerts
                        </div>
                        <div class="card-body">
                            <p class="small text-muted">L'alert devrait apparaître uniquement si APP_DEBUG=true</p>
                            <button onclick="testDebugAlert()" class="btn btn-warning btn-sm">
                                <i class="fas fa-bell me-1"></i>Tester Debug Alert
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-light">
                <div class="card-header">
                    <i class="fas fa-clipboard-check me-2"></i>Checklist de vérification
                </div>
                <div class="card-body">
                    <h6>Avec APP_DEBUG=true :</h6>
                    <ul>
                        <li>✅ Console doit afficher les logs</li>
                        <li>✅ Alert() doit s'afficher</li>
                        <li>✅ Badge orange "DEBUG MODE ACTIVÉ" dans console</li>
                    </ul>

                    <h6 class="mt-3">Avec APP_DEBUG=false :</h6>
                    <ul>
                        <li>❌ Console NE DOIT PAS afficher les logs</li>
                        <li>❌ Alert() NE DOIT PAS s'afficher</li>
                        <li>❌ Pas de badge orange dans console</li>
                    </ul>

                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Pour tester en mode production, modifiez <code>.env</code>:<br>
                        <code>APP_DEBUG=false</code><br>
                        Puis: <code>php artisan config:clear && php artisan cache:clear</code>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour au Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Afficher l'état de DEBUG_MODE
    document.getElementById('debug-mode-value').textContent = window.DEBUG_MODE ? 'true ✅' : 'false ❌';

    // Tester les console logs
    function testConsoleLogs() {
        debugLog('🧪 Test debugLog() - devrait être visible seulement en DEBUG_MODE=true');
        debugError('🧪 Test debugError() - devrait être visible seulement en DEBUG_MODE=true');
        debugWarn('🧪 Test debugWarn() - devrait être visible seulement en DEBUG_MODE=true');
        debugInfo('🧪 Test debugInfo() - devrait être visible seulement en DEBUG_MODE=true');

        if (window.DEBUG_MODE) {
            alert('✅ Les logs ont été affichés dans la console (F12 pour voir)');
        } else {
            alert('✅ Test réussi! Aucun log ne devrait apparaître dans la console en mode production.');
        }
    }

    // Tester les alerts debug
    function testDebugAlert() {
        debugAlert('🧪 Test debugAlert() - devrait apparaître seulement en DEBUG_MODE=true');

        if (!window.DEBUG_MODE) {
            alert('✅ Test réussi! debugAlert() est masqué en mode production.');
        }
    }

    // Logs de test au chargement de la page
    debugLog('🎯 Page de test chargée - DEBUG_MODE:', window.DEBUG_MODE);
    debugLog('📊 APP_DEBUG backend:', '{{ config("app.debug") ? "true" : "false" }}');
</script>
@endpush
