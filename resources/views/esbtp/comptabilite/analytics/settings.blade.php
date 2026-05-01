@extends('layouts.app')

@section('title', 'Paramètres Analytics')

@section('content')
<div class="container-fluid an-page">

    {{-- ============================ HEADER (no hero, page de configuration) ============================ --}}
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-sliders-h me-2"></i>Paramètres Analytics</h1>
            <p class="header-subtitle">Poids du modèle de risque & seuils de détection d'anomalies.</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.comptabilite.analytics.index') }}" class="btn-acasi secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success an-alert mt-3">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger an-alert mt-3">
            <i class="fas fa-exclamation-circle me-2"></i> Veuillez corriger les erreurs ci-dessous.
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('esbtp.comptabilite.analytics.settings.update') }}">
        @csrf

        {{-- ===== Default risk weights ===== --}}
        <div class="an-card mt-4">
            <div class="an-section-header">
                <div class="an-section-icon"><i class="fas fa-user-shield"></i></div>
                <div>
                    <h2 class="an-section-title">Modèle de risque de défaut</h2>
                    <p class="an-section-sub">Poids appliqués aux 4 caractéristiques financières et seuils de classification.</p>
                </div>
            </div>

            <div class="an-form-grid">
                <div class="an-field">
                    <label class="an-field-label">Poids — Solde restant
                        <span class="an-field-help">Importance du solde non payé (recommandé : {{ $defaults['default_risk']['weight_solde'] }})</span>
                    </label>
                    <input type="number" step="0.1" min="0" max="10"
                           name="default_risk[weight_solde]"
                           value="{{ old('default_risk.weight_solde', $settings['default_risk']['weight_solde']) }}"
                           class="an-input" required>
                </div>

                <div class="an-field">
                    <label class="an-field-label">Poids — Jours de retard
                        <span class="an-field-help">Pondération du retard de paiement (recommandé : {{ $defaults['default_risk']['weight_retard'] }})</span>
                    </label>
                    <input type="number" step="0.1" min="0" max="10"
                           name="default_risk[weight_retard]"
                           value="{{ old('default_risk.weight_retard', $settings['default_risk']['weight_retard']) }}"
                           class="an-input" required>
                </div>

                <div class="an-field">
                    <label class="an-field-label">Poids — Engagement
                        <span class="an-field-help">Signal du nombre de paiements effectués (recommandé : {{ $defaults['default_risk']['weight_engagement'] }})</span>
                    </label>
                    <input type="number" step="0.1" min="0" max="10"
                           name="default_risk[weight_engagement]"
                           value="{{ old('default_risk.weight_engagement', $settings['default_risk']['weight_engagement']) }}"
                           class="an-input" required>
                </div>

                <div class="an-field">
                    <label class="an-field-label">Poids — Montant attendu
                        <span class="an-field-help">Effet du montant total à recouvrer (recommandé : {{ $defaults['default_risk']['weight_montant'] }})</span>
                    </label>
                    <input type="number" step="0.1" min="0" max="10"
                           name="default_risk[weight_montant]"
                           value="{{ old('default_risk.weight_montant', $settings['default_risk']['weight_montant']) }}"
                           class="an-input" required>
                </div>

                <div class="an-field">
                    <label class="an-field-label">Biais (intercept)
                        <span class="an-field-help">Décalage de base du score (recommandé : {{ $defaults['default_risk']['bias'] }})</span>
                    </label>
                    <input type="number" step="0.1" min="-10" max="10"
                           name="default_risk[bias]"
                           value="{{ old('default_risk.bias', $settings['default_risk']['bias']) }}"
                           class="an-input" required>
                </div>

                <div class="an-field">
                    <label class="an-field-label">Top-N étudiants à afficher
                        <span class="an-field-help">Nombre d'étudiants prioritaires affichés dans la table (10-500, recommandé : {{ $defaults['default_risk']['top_n'] }})</span>
                    </label>
                    <input type="number" step="1" min="10" max="500"
                           name="default_risk[top_n]"
                           value="{{ old('default_risk.top_n', $settings['default_risk']['top_n']) }}"
                           class="an-input" required>
                </div>

                <div class="an-field">
                    <label class="an-field-label">Seuil — Haut risque
                        <span class="an-field-help">Score minimal pour classer "haut risque" (0.5-0.95, recommandé : {{ $defaults['default_risk']['threshold_high'] }})</span>
                    </label>
                    <input type="number" step="0.01" min="0.5" max="0.95"
                           name="default_risk[threshold_high]"
                           value="{{ old('default_risk.threshold_high', $settings['default_risk']['threshold_high']) }}"
                           class="an-input" required>
                </div>

                <div class="an-field">
                    <label class="an-field-label">Seuil — Risque moyen
                        <span class="an-field-help">Score minimal pour "surveillance" (0.05-0.5, recommandé : {{ $defaults['default_risk']['threshold_medium'] }})</span>
                    </label>
                    <input type="number" step="0.01" min="0.05" max="0.5"
                           name="default_risk[threshold_medium]"
                           value="{{ old('default_risk.threshold_medium', $settings['default_risk']['threshold_medium']) }}"
                           class="an-input" required>
                </div>
            </div>
        </div>

        {{-- ===== Anomaly detection ===== --}}
        <div class="an-card mt-4">
            <div class="an-section-header">
                <div class="an-section-icon"><i class="fas fa-radiation"></i></div>
                <div>
                    <h2 class="an-section-title">Détection d'anomalies</h2>
                    <p class="an-section-sub">Seuils de Z-score (écarts à la moyenne) et notifications email.</p>
                </div>
            </div>

            <div class="an-form-grid">
                <div class="an-field">
                    <label class="an-field-label">Seuil Warning (Z-score)
                        <span class="an-field-help">Écart à la moyenne déclenchant un avertissement (1-5, recommandé : {{ $defaults['anomaly']['z_warning'] }})</span>
                    </label>
                    <input type="number" step="0.1" min="1" max="5"
                           name="anomaly[z_warning]"
                           value="{{ old('anomaly.z_warning', $settings['anomaly']['z_warning']) }}"
                           class="an-input" required>
                </div>

                <div class="an-field">
                    <label class="an-field-label">Seuil Critical (Z-score)
                        <span class="an-field-help">Écart déclenchant une alerte critique (1.5-6, recommandé : {{ $defaults['anomaly']['z_critical'] }})</span>
                    </label>
                    <input type="number" step="0.1" min="1.5" max="6"
                           name="anomaly[z_critical]"
                           value="{{ old('anomaly.z_critical', $settings['anomaly']['z_critical']) }}"
                           class="an-input" required>
                </div>

                <div class="an-field">
                    <label class="an-field-label">Multiplicateur paiement aberrant
                        <span class="an-field-help">Un paiement {{ '>' }} N × moyenne déclenche une alerte (1.5-10, recommandé : {{ $defaults['anomaly']['payment_outlier_multiplier'] }})</span>
                    </label>
                    <input type="number" step="0.1" min="1.5" max="10"
                           name="anomaly[payment_outlier_multiplier]"
                           value="{{ old('anomaly.payment_outlier_multiplier', $settings['anomaly']['payment_outlier_multiplier']) }}"
                           class="an-input" required>
                </div>

                <div class="an-field an-field--checkbox">
                    <label class="an-field-label">
                        <input type="hidden" name="anomaly[notifications_enabled]" value="0">
                        <input type="checkbox" name="anomaly[notifications_enabled]" value="1"
                               {{ $settings['anomaly']['notifications_enabled'] ? 'checked' : '' }}>
                        Activer les notifications email pour les alertes critiques
                    </label>
                    <span class="an-field-help">Envoyé aux administrateurs et comptables lors d'une anomalie critique. Déduplication 24h.</span>
                </div>
            </div>
        </div>

        {{-- ===== Recouvrement / WhatsApp template ===== --}}
        <div class="an-card mt-4">
            <div class="an-section-header">
                <div class="an-section-icon"><i class="fab fa-whatsapp"></i></div>
                <div>
                    <h2 class="an-section-title">Modèle de message Recouvrement</h2>
                    <p class="an-section-sub">Template WhatsApp pré-rempli sur la page Recouvrement quotidien. Variables disponibles : <code>{prenom}</code>, <code>{nom}</code>, <code>{solde}</code>, <code>{retard}</code>, <code>{ecole}</code>.</p>
                </div>
            </div>

            <div class="an-form-grid">
                <div class="an-field" style="grid-column: 1 / -1;">
                    <label class="an-field-label">Message WhatsApp
                        <span class="an-field-help">Sera ouvert pré-rempli quand le comptable clique sur le bouton WhatsApp d'un étudiant</span>
                    </label>
                    <textarea name="recouvrement[whatsapp_template]" rows="4" class="an-input" maxlength="1000">{{ old('recouvrement.whatsapp_template', $settings['recouvrement']['whatsapp_template']) }}</textarea>
                    <div class="an-field-help" style="margin-top: .5rem;">
                        Recommandé : <em>« {{ $defaults['recouvrement']['whatsapp_template'] }} »</em>
                    </div>
                </div>
            </div>
        </div>

        <div class="an-form-actions mt-4">
            <button type="submit" class="btn-acasi primary">
                <i class="fas fa-save me-1"></i> Enregistrer les paramètres
            </button>
            <a href="{{ route('esbtp.comptabilite.analytics.index') }}" class="btn-acasi secondary">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
:root {
    --an-primary: #0453cb;
    --an-secondary: #5e91de;
    --an-dark: #0f172a;
    --an-text: #1e293b;
    --an-muted: #64748b;
    --an-border: #e2e8f0;
}

.an-card {
    background: #fff;
    border: 1px solid var(--an-border);
    border-radius: 14px;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.an-section-header {
    display: flex; align-items: center; gap: .85rem; margin-bottom: 1.25rem;
}
.an-section-icon {
    width: 42px; height: 42px; border-radius: 11px;
    background: linear-gradient(135deg, var(--an-primary), var(--an-secondary));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1rem; flex-shrink: 0;
}
.an-section-title { font-size: 1.1rem; font-weight: 700; color: var(--an-dark); margin: 0; }
.an-section-sub { font-size: .82rem; color: var(--an-muted); margin: .15rem 0 0; }

.an-form-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem;
}
.an-field { display: flex; flex-direction: column; gap: .35rem; }
.an-field--checkbox { grid-column: 1 / -1; }
.an-field--checkbox .an-field-label {
    display: flex; align-items: center; gap: .65rem; cursor: pointer;
    font-weight: 600; color: var(--an-text);
}
.an-field--checkbox input[type="checkbox"] {
    width: 18px; height: 18px; accent-color: var(--an-primary);
}
.an-field-label {
    font-size: .85rem; font-weight: 600; color: var(--an-text);
    display: flex; flex-direction: column; gap: .15rem;
}
.an-field-help {
    font-size: .72rem; color: var(--an-muted); font-weight: 400;
}
.an-input {
    padding: .55rem .85rem; border-radius: 10px;
    border: 1px solid var(--an-border); font-size: .9rem;
    transition: border-color .15s ease;
}
.an-input:focus {
    outline: none; border-color: var(--an-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}

.an-form-actions {
    display: flex; gap: .75rem; flex-wrap: wrap;
    padding: 1rem 0; justify-content: flex-end;
}

.an-alert { border-radius: 12px; border: none; }

@media (max-width: 768px) {
    .an-form-grid { grid-template-columns: 1fr; }
    .an-card { padding: 1.25rem 1rem; }
}
</style>
@endpush
