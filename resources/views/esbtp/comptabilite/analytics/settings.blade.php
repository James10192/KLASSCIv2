@extends('layouts.app')

@section('title', 'Paramètres Analytics')

@section('content')
<div class="container-fluid as-page" x-data="settingsPage()">

    {{-- ============================ HERO PREMIUM ============================ --}}
    <div class="as-hero">
        <div class="as-hero-top">
            <div class="as-hero-left">
                <div class="as-hero-icon"><i class="fas fa-sliders-h"></i></div>
                <div>
                    <h1>Paramètres Analytics</h1>
                    <p>Réglez le moteur de prédiction & les seuils d'alerte selon vos pratiques.</p>
                </div>
            </div>
            <div class="as-hero-right">
                <a href="{{ route('esbtp.comptabilite.analytics.index') }}" class="as-btn as-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour Analytics
                </a>
            </div>
        </div>

        {{-- Quick recap des valeurs actuelles --}}
        <div class="as-recap">
            <div class="as-recap-item">
                <div class="as-recap-label">Seuil haut risque</div>
                <div class="as-recap-value" x-text="(form.default_risk.threshold_high * 100).toFixed(0) + ' %'"></div>
            </div>
            <div class="as-recap-item">
                <div class="as-recap-label">Top-N affiché</div>
                <div class="as-recap-value" x-text="form.default_risk.top_n + ' étudiants'"></div>
            </div>
            <div class="as-recap-item">
                <div class="as-recap-label">Z critique anomalies</div>
                <div class="as-recap-value" x-text="form.anomaly.z_critical.toFixed(1) + ' σ'"></div>
            </div>
            <div class="as-recap-item">
                <div class="as-recap-label">Notifications</div>
                <div class="as-recap-value" x-text="form.anomaly.notifications_enabled ? 'Activées' : 'Désactivées'"></div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="as-banner as-banner--success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="as-banner as-banner--error">
            <i class="fas fa-exclamation-circle"></i> Veuillez corriger les erreurs ci-dessous.
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('esbtp.comptabilite.analytics.settings.update') }}">
        @csrf

        {{-- ===== Default risk weights ===== --}}
        <div class="as-card">
            <div class="as-card-head">
                <div class="as-card-icon as-card-icon--risk"><i class="fas fa-user-shield"></i></div>
                <div class="as-card-title-block">
                    <h2>Modèle de risque de défaut</h2>
                    <p>Pondérations & seuils du score logistique appliqué à chaque étudiant.</p>
                </div>
                <button type="button" class="as-card-reset" @click="resetSection('default_risk')">
                    <i class="fas fa-undo"></i> Restaurer défauts
                </button>
            </div>

            <div class="as-form-grid">
                @php
                    $riskFields = [
                        ['weight_solde', 'Poids — Solde restant', 'Importance du solde non payé', 0, 10, 0.1],
                        ['weight_retard', 'Poids — Jours de retard', 'Pondération du retard de paiement', 0, 10, 0.1],
                        ['weight_engagement', 'Poids — Engagement', 'Signal du nombre de paiements effectués', 0, 10, 0.1],
                        ['weight_montant', 'Poids — Montant attendu', 'Effet du montant total à recouvrer', 0, 10, 0.1],
                        ['bias', 'Biais (intercept)', 'Décalage de base du score', -10, 10, 0.1],
                    ];
                @endphp

                @foreach($riskFields as [$key, $label, $help, $min, $max, $step])
                    <div class="as-field">
                        <label class="as-field-label">
                            <span>{{ $label }}</span>
                            <span class="as-recommended">recommandé : {{ $defaults['default_risk'][$key] }}</span>
                        </label>
                        <div class="as-field-help">{{ $help }}</div>
                        <div class="as-slider-row">
                            <input type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}"
                                   x-model.number="form.default_risk.{{ $key }}"
                                   class="as-range">
                            <input type="number" step="{{ $step }}" min="{{ $min }}" max="{{ $max }}"
                                   name="default_risk[{{ $key }}]"
                                   x-model.number="form.default_risk.{{ $key }}"
                                   class="as-number" required>
                        </div>
                    </div>
                @endforeach

                <div class="as-field">
                    <label class="as-field-label">
                        <span>Top-N étudiants prioritaires</span>
                        <span class="as-recommended">recommandé : {{ $defaults['default_risk']['top_n'] }}</span>
                    </label>
                    <div class="as-field-help">Nombre d'étudiants affichés dans la table Recouvrement (10–500).</div>
                    <div class="as-slider-row">
                        <input type="range" min="10" max="500" step="10"
                               x-model.number="form.default_risk.top_n"
                               class="as-range">
                        <input type="number" step="1" min="10" max="500"
                               name="default_risk[top_n]"
                               x-model.number="form.default_risk.top_n"
                               class="as-number" required>
                    </div>
                </div>

                @php
                    $thresholdFields = [
                        ['threshold_high', 'Seuil — Haut risque', 'Score minimal pour classer "haut risque"', 0.5, 0.95, 0.01],
                        ['threshold_medium', 'Seuil — Risque moyen', 'Score minimal pour "surveillance"', 0.05, 0.5, 0.01],
                    ];
                @endphp
                @foreach($thresholdFields as [$key, $label, $help, $min, $max, $step])
                    <div class="as-field">
                        <label class="as-field-label">
                            <span>{{ $label }}</span>
                            <span class="as-recommended">recommandé : {{ $defaults['default_risk'][$key] }}</span>
                        </label>
                        <div class="as-field-help">{{ $help }} (entre {{ $min }} et {{ $max }})</div>
                        <div class="as-slider-row">
                            <input type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}"
                                   x-model.number="form.default_risk.{{ $key }}"
                                   class="as-range">
                            <input type="number" step="{{ $step }}" min="{{ $min }}" max="{{ $max }}"
                                   name="default_risk[{{ $key }}]"
                                   x-model.number="form.default_risk.{{ $key }}"
                                   class="as-number" required>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ===== Anomaly detection ===== --}}
        <div class="as-card">
            <div class="as-card-head">
                <div class="as-card-icon as-card-icon--anomaly"><i class="fas fa-radiation"></i></div>
                <div class="as-card-title-block">
                    <h2>Détection d'anomalies</h2>
                    <p>Seuils Z-score sur les flux et notifications email aux administrateurs.</p>
                </div>
                <button type="button" class="as-card-reset" @click="resetSection('anomaly')">
                    <i class="fas fa-undo"></i> Restaurer défauts
                </button>
            </div>

            <div class="as-form-grid">
                @php
                    $anomalyFields = [
                        ['z_warning', 'Seuil Warning (Z-score)', 'Écart à la moyenne déclenchant un avertissement', 1, 5, 0.1],
                        ['z_critical', 'Seuil Critical (Z-score)', 'Écart déclenchant une alerte critique', 1.5, 6, 0.1],
                        ['payment_outlier_multiplier', 'Multiplicateur paiement aberrant', 'Un paiement > N × moyenne déclenche une alerte', 1.5, 10, 0.1],
                    ];
                @endphp
                @foreach($anomalyFields as [$key, $label, $help, $min, $max, $step])
                    <div class="as-field">
                        <label class="as-field-label">
                            <span>{{ $label }}</span>
                            <span class="as-recommended">recommandé : {{ $defaults['anomaly'][$key] }}</span>
                        </label>
                        <div class="as-field-help">{{ $help }}</div>
                        <div class="as-slider-row">
                            <input type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}"
                                   x-model.number="form.anomaly.{{ $key }}"
                                   class="as-range">
                            <input type="number" step="{{ $step }}" min="{{ $min }}" max="{{ $max }}"
                                   name="anomaly[{{ $key }}]"
                                   x-model.number="form.anomaly.{{ $key }}"
                                   class="as-number" required>
                        </div>
                    </div>
                @endforeach

                <div class="as-field as-field--toggle">
                    <label class="as-toggle">
                        <input type="hidden" name="anomaly[notifications_enabled]" value="0">
                        <input type="checkbox" name="anomaly[notifications_enabled]" value="1"
                               x-model="form.anomaly.notifications_enabled">
                        <span class="as-toggle-track">
                            <span class="as-toggle-dot"></span>
                        </span>
                        <span class="as-toggle-label">
                            <strong>Notifications email pour alertes critiques</strong>
                            <small>Envoyé aux superAdmin + comptables · déduplication 24h</small>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        {{-- ===== Recouvrement WhatsApp template ===== --}}
        <div class="as-card">
            <div class="as-card-head">
                <div class="as-card-icon as-card-icon--whatsapp"><i class="fab fa-whatsapp"></i></div>
                <div class="as-card-title-block">
                    <h2>Modèle de message Recouvrement</h2>
                    <p>Texte WhatsApp pré-rempli sur la page Recouvrement quotidien.</p>
                </div>
                <button type="button" class="as-card-reset" @click="resetWhatsappTemplate()">
                    <i class="fas fa-undo"></i> Restaurer défaut
                </button>
            </div>

            <div class="as-form-grid">
                <div class="as-field as-field--full">
                    <label class="as-field-label">
                        <span>Message WhatsApp</span>
                    </label>
                    <div class="as-field-help">
                        Variables : <code>{prenom}</code>, <code>{nom}</code>, <code>{solde}</code>,
                        <code>{retard}</code>, <code>{ecole}</code>
                    </div>
                    <textarea name="recouvrement[whatsapp_template]" rows="4"
                              x-model="form.recouvrement.whatsapp_template"
                              class="as-textarea" maxlength="1000"></textarea>
                    <div class="as-textarea-counter">
                        <span x-text="form.recouvrement.whatsapp_template.length"></span> / 1000 caractères
                    </div>
                </div>
            </div>
        </div>

        <div class="as-actions">
            <a href="{{ route('esbtp.comptabilite.analytics.index') }}" class="as-btn as-btn--ghost">
                Annuler
            </a>
            <button type="submit" class="as-btn as-btn--primary">
                <i class="fas fa-save"></i> Enregistrer les paramètres
            </button>
        </div>
    </form>
</div>

<script>
function settingsPage() {
    return {
        defaults: @json($defaults),
        form: {
            default_risk: @json($settings['default_risk']),
            anomaly: {
                ...@json($settings['anomaly']),
                notifications_enabled: {{ $settings['anomaly']['notifications_enabled'] ? 'true' : 'false' }},
            },
            recouvrement: @json($settings['recouvrement']),
        },
        resetSection(section) {
            const def = this.defaults[section];
            for (const k of Object.keys(def)) {
                this.form[section][k] = def[k];
            }
        },
        resetWhatsappTemplate() {
            this.form.recouvrement.whatsapp_template = this.defaults.recouvrement.whatsapp_template;
        },
    };
}
</script>
@endsection

@push('styles')
<style>
:root {
    --as-primary: #0453cb;
    --as-primary-d: #033a8e;
    --as-secondary: #5e91de;
    --as-dark: #0f172a;
    --as-text: #1e293b;
    --as-muted: #64748b;
    --as-border: #e2e8f0;
    --as-success: #10b981;
    --as-warning: #f59e0b;
    --as-danger: #dc2626;
    --as-whatsapp: #25D366;
}

.as-page { padding: 1rem 0 3rem; }

.as-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff; margin-bottom: 1.5rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.as-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;
}
.as-hero-left { display: flex; align-items: center; gap: 1rem; }
.as-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.as-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.as-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: 0; }

.as-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    border-radius: 10px; padding: .6rem 1.1rem;
    font-size: .85rem; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
    transition: all .2s ease;
}
.as-btn--glass { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.2); }
.as-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; transform: translateY(-1px); }
.as-btn--primary { background: var(--as-primary); color: #fff; }
.as-btn--primary:hover { background: var(--as-primary-d); transform: translateY(-1px); }
.as-btn--ghost { background: #fff; color: var(--as-muted); border: 1px solid var(--as-border); }
.as-btn--ghost:hover { background: #f8fafc; color: var(--as-text); }

.as-recap { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.as-recap-item {
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px; padding: .85rem 1rem;
}
.as-recap-label {
    font-size: .68rem; color: rgba(255,255,255,.65);
    text-transform: uppercase; letter-spacing: .04em;
}
.as-recap-value { font-size: 1.05rem; font-weight: 700; color: #fff; margin-top: .2rem; }

.as-banner {
    border-radius: 12px; padding: .9rem 1.15rem;
    display: flex; align-items: flex-start; gap: .65rem;
    margin-bottom: 1rem; font-size: .9rem;
}
.as-banner i { font-size: 1.05rem; flex-shrink: 0; margin-top: .1rem; }
.as-banner ul { margin: .35rem 0 0; padding-left: 1.5rem; font-size: .82rem; }
.as-banner--success { background: rgba(16,185,129,.06); border: 1px solid rgba(16,185,129,.2); color: #047857; }
.as-banner--success i { color: var(--as-success); }
.as-banner--error { background: rgba(220,38,38,.06); border: 1px solid rgba(220,38,38,.2); color: var(--as-danger); }

.as-card {
    background: #fff; border: 1px solid var(--as-border);
    border-radius: 14px; margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    overflow: hidden;
}
.as-card-head {
    display: flex; align-items: center; gap: 1rem;
    padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--as-border);
    background: linear-gradient(180deg, #fafbfc, #fff);
}
.as-card-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.05rem; flex-shrink: 0;
}
.as-card-icon--risk { background: linear-gradient(135deg, #dc2626, #f59e0b); }
.as-card-icon--anomaly { background: linear-gradient(135deg, #f59e0b, #b45309); }
.as-card-icon--whatsapp { background: linear-gradient(135deg, #25D366, #128c7e); }
.as-card-title-block { flex: 1; }
.as-card-title-block h2 { font-size: 1.05rem; font-weight: 700; color: var(--as-dark); margin: 0; }
.as-card-title-block p { font-size: .82rem; color: var(--as-muted); margin: .15rem 0 0; }
.as-card-reset {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .4rem .75rem; border-radius: 8px;
    background: transparent; border: 1px solid var(--as-border);
    font-size: .75rem; color: var(--as-muted); cursor: pointer;
    transition: all .15s ease;
}
.as-card-reset:hover { background: #f1f5f9; color: var(--as-text); border-color: #cbd5e1; }

.as-form-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;
    padding: 1.5rem;
}
.as-field { display: flex; flex-direction: column; gap: .35rem; }
.as-field--full { grid-column: 1 / -1; }
.as-field--toggle { grid-column: 1 / -1; }

.as-field-label {
    display: flex; justify-content: space-between; align-items: baseline;
    font-size: .9rem; font-weight: 600; color: var(--as-text);
}
.as-recommended { font-size: .7rem; color: var(--as-muted); font-weight: 500; font-style: italic; }
.as-field-help { font-size: .75rem; color: var(--as-muted); margin-bottom: .25rem; }
.as-field-help code {
    background: #f1f5f9; padding: 1px 6px; border-radius: 4px;
    font-size: .72rem; color: var(--as-primary); border: 1px solid #e2e8f0;
}

.as-slider-row {
    display: flex; align-items: center; gap: .85rem;
    background: #fafbfc; border: 1px solid var(--as-border);
    border-radius: 10px; padding: .6rem .85rem;
}
.as-range {
    flex: 1; height: 4px; -webkit-appearance: none; appearance: none;
    background: linear-gradient(90deg, var(--as-primary) 0%, var(--as-secondary) 100%);
    border-radius: 99px; outline: none; cursor: pointer;
}
.as-range::-webkit-slider-thumb {
    -webkit-appearance: none; appearance: none;
    width: 18px; height: 18px; border-radius: 50%;
    background: #fff; border: 2px solid var(--as-primary);
    cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,.15);
    transition: transform .15s;
}
.as-range::-webkit-slider-thumb:hover { transform: scale(1.15); }
.as-range::-moz-range-thumb {
    width: 18px; height: 18px; border-radius: 50%;
    background: #fff; border: 2px solid var(--as-primary);
    cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,.15);
}
.as-number {
    width: 90px; padding: .35rem .5rem; text-align: center;
    border: 1px solid var(--as-border); border-radius: 8px;
    font-size: .9rem; font-weight: 600; color: var(--as-primary); background: #fff;
}
.as-number:focus {
    outline: none; border-color: var(--as-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}

.as-textarea {
    width: 100%; padding: .85rem 1rem;
    border: 1px solid var(--as-border); border-radius: 10px;
    font-size: .92rem; font-family: inherit; color: var(--as-text);
    background: #fafbfc; resize: vertical; min-height: 100px;
    transition: border-color .15s;
}
.as-textarea:focus {
    outline: none; border-color: var(--as-primary); background: #fff;
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.as-textarea-counter { margin-top: .35rem; font-size: .72rem; color: var(--as-muted); text-align: right; }

.as-toggle {
    display: flex; align-items: center; gap: 1rem; cursor: pointer;
    padding: 1rem 1.25rem; background: #fafbfc;
    border: 1px solid var(--as-border); border-radius: 12px;
    transition: all .15s;
}
.as-toggle:hover { background: #f1f5f9; }
.as-toggle input[type="checkbox"] { display: none; }
.as-toggle-track {
    position: relative; display: inline-block;
    width: 44px; height: 24px; border-radius: 99px;
    background: #cbd5e1; transition: background .2s; flex-shrink: 0;
}
.as-toggle-dot {
    position: absolute; top: 2px; left: 2px;
    width: 20px; height: 20px; border-radius: 50%;
    background: #fff; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.as-toggle input:checked + .as-toggle-track { background: var(--as-primary); }
.as-toggle input:checked + .as-toggle-track .as-toggle-dot { transform: translateX(20px); }
.as-toggle-label strong { display: block; font-size: .9rem; color: var(--as-text); }
.as-toggle-label small { display: block; font-size: .75rem; color: var(--as-muted); margin-top: .15rem; }

.as-actions {
    display: flex; justify-content: flex-end; gap: .75rem; padding: 1.25rem 0;
}

@media (max-width: 992px) {
    .as-recap { grid-template-columns: repeat(2, 1fr); }
    .as-form-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .as-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .as-hero h1 { font-size: 1.2rem; }
    .as-card-head { flex-wrap: wrap; }
    .as-card-reset { width: 100%; justify-content: center; margin-top: .5rem; }
    .as-recap { grid-template-columns: 1fr; }
}
</style>
@endpush
