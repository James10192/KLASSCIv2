@extends('layouts.app')

@section('title', 'Paramètres Analytics')

@section('content')
@php
    // Shell mobile : le DOM de bureau reste dans .m-only-desktop, le formulaire
    // mobile (m-field / m-opt) vit à côté, sur le MÊME état Alpine (une seule
    // fabrique, deux rendus). Shell coupé : rien ne change au bureau.
    $asShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);

    // Champs du modèle de risque et de la détection d'anomalies : une seule
    // table pour les deux rendus. [clé, libellé, aide, min, max, pas]
    $riskFields = [
        ['weight_solde', 'Poids — Solde restant', 'Importance du solde non payé', 0, 10, 0.1],
        ['weight_retard', 'Poids — Jours de retard', 'Pondération du retard de paiement', 0, 10, 0.1],
        ['weight_engagement', 'Poids — Engagement', 'Signal du nombre de paiements effectués', 0, 10, 0.1],
        ['weight_montant', 'Poids — Montant attendu', 'Effet du montant total à recouvrer', 0, 10, 0.1],
        ['bias', 'Biais (intercept)', 'Décalage de base du score', -10, 10, 0.1],
    ];
    $thresholdFields = [
        ['threshold_high', 'Seuil — Haut risque', 'Score minimal pour classer "haut risque"', 0.5, 0.95, 0.01],
        ['threshold_medium', 'Seuil — Risque moyen', 'Score minimal pour "surveillance"', 0.05, 0.5, 0.01],
    ];
    $anomalyFields = [
        ['z_warning', 'Seuil Warning (Z-score)', 'Écart à la moyenne déclenchant un avertissement', 1, 5, 0.1],
        ['z_critical', 'Seuil Critical (Z-score)', 'Écart déclenchant une alerte critique', 1.5, 6, 0.1],
        ['payment_outlier_multiplier', 'Multiplicateur paiement aberrant', 'Un paiement > N × moyenne déclenche une alerte', 1.5, 10, 0.1],
        ['recouvrement_gap_warning_pct', 'Écart recouvrement — seuil warning (%)', 'Mois clos où l\'encaissé est inférieur d\'au moins X % à ce qui était attendu via les échéanciers', 5, 80, 1],
        ['recouvrement_gap_critical_pct', 'Écart recouvrement — seuil critique (%)', 'Au-delà de ce pourcentage, alerte critique + notification', 10, 95, 1],
        ['recouvrement_gap_min_expected', 'Écart recouvrement — montant minimal attendu (FCFA)', 'On ignore les mois où le montant attendu est inférieur à ce seuil (évite le bruit sur petits volumes)', 0, 100000000, 50000],
    ];

    // Fiabilité des données : seuils du bandeau affiché avant toute prévision.
    $fiabiliteFields = [
        ['stale_days', 'Données anciennes après (jours)', 'Sans paiement saisi depuis ce nombre de jours, les prévisions sont signalées comme non fiables', 7, 365, 1],
        ['min_sample', 'Échantillon minimal (paiements)', 'En dessous, pas assez de paiements pour dégager une tendance', 5, 1000, 1],
        ['lookback_months', 'Période analysée (mois)', 'Nombre de mois de saisies examinés', 3, 24, 1],
        ['catchup_min_per_day', 'Rattrapage : saisies par jour et par compte', 'Un compte qui saisit au moins ce nombre de paiements dans la journée…', 5, 1000, 1],
        ['catchup_lag_days', 'Rattrapage : ancienneté des paiements (jours)', '… dont la majorité date de plus de ce nombre de jours fait une saisie de rattrapage', 1, 180, 1],
        ['catchup_alert_pct', "Rattrapage : seuil d'alerte (%)", 'Part des paiements saisis en rattrapage au-delà de laquelle le bandeau alerte', 5, 100, 1],
    ];

    $asCfg = [
        'defaults' => $defaults,
        'settings' => [
            'default_risk' => $settings['default_risk'],
            'anomaly' => $settings['anomaly'],
            'recouvrement' => $settings['recouvrement'],
            'fiabilite' => $settings['fiabilite'],
        ],
        'flash' => session('success'),
        'csrf' => csrf_token(),
        'urls' => [
            'update' => route('esbtp.comptabilite.analytics.settings.update'),
        ],
    ];
@endphp
<div class="container-fluid as-page" x-data="settingsPage({{ \Illuminate\Support\Js::from($asCfg) }})">

<div class="{{ $asShell ? 'm-only-desktop' : '' }}">
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

        {{-- ===== Fiabilité des données ===== --}}
        <div class="as-card">
            <div class="as-card-head">
                <div class="as-card-icon as-card-icon--anomaly"><i class="fas fa-shield-alt"></i></div>
                <div class="as-card-title-block">
                    <h2>Fiabilité des données</h2>
                    <p>Avant toute prévision, la page vérifie que les paiements saisis permettent d'en faire une.</p>
                </div>
                <button type="button" class="as-card-reset" @click="resetSection('fiabilite')">
                    <i class="fas fa-undo"></i> Restaurer défauts
                </button>
            </div>

            <div class="as-form-grid">
                @foreach($fiabiliteFields as [$key, $label, $help, $min, $max, $step])
                    <div class="as-field">
                        <label class="as-field-label">
                            <span>{{ $label }}</span>
                            <span class="as-recommended">recommandé : {{ $defaults['fiabilite'][$key] }}</span>
                        </label>
                        <div class="as-field-help">{{ $help }}</div>
                        <div class="as-slider-row">
                            <input type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}"
                                   x-model.number="form.fiabilite.{{ $key }}"
                                   class="as-range">
                            <input type="number" step="{{ $step }}" min="{{ $min }}" max="{{ $max }}"
                                   name="fiabilite[{{ $key }}]"
                                   x-model.number="form.fiabilite.{{ $key }}"
                                   class="as-number">
                        </div>
                    </div>
                @endforeach
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
</div>{{-- /.m-only-desktop --}}

@if($asShell)
@php
    // ---- Formulaire mobile (shell m-*) — namespace CSS asm-* ----
    $asmEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $asmEcoleNom = $asmEcole['name'] ?: ($asmEcole['acronym'] ?: config('app.name'));
    $asmTopN = ['top_n', 'Top-N étudiants prioritaires', 'Nombre d\'étudiants affichés dans la liste Recouvrement', 10, 500, 10];
@endphp
{{-- ============================ ÉCRAN MOBILE (shell m-*) ============================ --}}
{{-- La barre d'onglets et la navbar mobile sont rendues par le layout. --}}
<div class="m-only-mobile m-screen asm-screen">
    <x-m.appbar title="Paramètres" :sub="$asmEcoleNom" :back="route('esbtp.comptabilite.analytics.index')" back-label="Retour aux analytics" />

    <div class="m-body">
        <section class="m-hero">
            <span class="k">Moteur de prédiction</span>
            <span class="v"><span x-text="Math.round(Number(form.default_risk.threshold_high) * 100)"></span><small>% · seuil haut risque</small></span>
            <div class="row">
                <span class="pill" x-text="'Top ' + form.default_risk.top_n + ' étudiants'"></span>
                <span class="pill" x-text="'Z critique ' + Number(form.anomaly.z_critical).toFixed(1) + ' σ'"></span>
                <span class="pill" x-text="form.anomaly.notifications_enabled ? 'Notifications activées' : 'Notifications coupées'"></span>
            </div>
        </section>

        <div class="m-seg" role="tablist" aria-label="Sections des paramètres">
            <button type="button" role="tab" x-bind:aria-selected="mSeg === 'risque' ? 'true' : 'false'" x-bind:class="mSeg === 'risque' ? 'on' : ''" x-on:click="mSeg = 'risque'">Risque</button>
            <button type="button" role="tab" x-bind:aria-selected="mSeg === 'anomalies' ? 'true' : 'false'" x-bind:class="mSeg === 'anomalies' ? 'on' : ''" x-on:click="mSeg = 'anomalies'">Anomalies</button>
            <button type="button" role="tab" x-bind:aria-selected="mSeg === 'message' ? 'true' : 'false'" x-bind:class="mSeg === 'message' ? 'on' : ''" x-on:click="mSeg = 'message'">Message</button>
        </div>

        <form id="asm-form" class="asm-form" x-on:submit.prevent="mEnregistrer()" novalidate>

            {{-- Segment 1 : modèle de risque --}}
            <div class="asm-groupe" x-show="mSeg === 'risque'">
                <div class="m-sec">
                    <b>Modèle de risque d’impayé</b>
                    <button type="button" class="asm-reset" x-on:click="resetSection('default_risk')">Valeurs recommandées</button>
                </div>
                @foreach(array_merge($riskFields, [$asmTopN], $thresholdFields) as [$key, $label, $help, $min, $max, $step])
                    <div class="m-field asm-field">
                        <label for="asm-dr-{{ $key }}">{{ $label }}</label>
                        <input id="asm-dr-{{ $key }}" type="number" class="m-in"
                               inputmode="decimal" step="{{ $step }}" min="{{ $min }}" max="{{ $max }}"
                               x-model.number="form.default_risk.{{ $key }}"
                               x-bind:aria-invalid="erreurs['default_risk.{{ $key }}'] ? 'true' : 'false'"
                               required>
                        <small class="asm-help">{{ $help }} · entre {{ $min }} et {{ $max }} · recommandé : {{ $defaults['default_risk'][$key] }}</small>
                        <small class="asm-err" x-show="erreurs['default_risk.{{ $key }}']" x-text="erreurs['default_risk.{{ $key }}']"></small>
                    </div>
                @endforeach
            </div>

            {{-- Segment 2 : détection d'anomalies --}}
            <div class="asm-groupe" x-show="mSeg === 'anomalies'" x-cloak>
                <div class="m-sec">
                    <b>Détection d’anomalies</b>
                    <button type="button" class="asm-reset" x-on:click="resetSection('anomaly')">Valeurs recommandées</button>
                </div>
                @foreach($anomalyFields as [$key, $label, $help, $min, $max, $step])
                    <div class="m-field asm-field">
                        <label for="asm-an-{{ $key }}">{{ $label }}</label>
                        <input id="asm-an-{{ $key }}" type="number" class="m-in"
                               inputmode="decimal" step="{{ $step }}" min="{{ $min }}" max="{{ $max }}"
                               x-model.number="form.anomaly.{{ $key }}"
                               x-bind:aria-invalid="erreurs['anomaly.{{ $key }}'] ? 'true' : 'false'"
                               required>
                        <small class="asm-help">{{ $help }} · entre {{ $min }} et {{ $max }} · recommandé : {{ $defaults['anomaly'][$key] }}</small>
                        <small class="asm-err" x-show="erreurs['anomaly.{{ $key }}']" x-text="erreurs['anomaly.{{ $key }}']"></small>
                    </div>
                @endforeach

                <div class="m-opt">
                    <label x-bind:class="form.anomaly.notifications_enabled ? 'on' : ''">
                        <span class="rd" aria-hidden="true"></span>
                        <div>
                            <b>Notifications e-mail des alertes critiques</b>
                            <span>Envoyées aux administrateurs et aux comptables, sans doublon sur 24 h.</span>
                        </div>
                        <input type="checkbox" x-model="form.anomaly.notifications_enabled">
                        <span class="m-chip" x-bind:class="form.anomaly.notifications_enabled ? 'ok' : 'mute'" x-text="form.anomaly.notifications_enabled ? 'Activées' : 'Coupées'"></span>
                    </label>
                </div>
            </div>

            {{-- Segment 3 : modèle de message WhatsApp --}}
            <div class="asm-groupe" x-show="mSeg === 'message'" x-cloak>
                <div class="m-sec">
                    <b>Message de relance</b>
                    <button type="button" class="asm-reset" x-on:click="resetWhatsappTemplate()">Texte recommandé</button>
                </div>
                <div class="m-field asm-field">
                    <label for="asm-wa">Message WhatsApp pré-rempli</label>
                    <textarea id="asm-wa" class="m-in ta" rows="5" maxlength="1000"
                              x-model="form.recouvrement.whatsapp_template"
                              x-bind:aria-invalid="erreurs['recouvrement.whatsapp_template'] ? 'true' : 'false'"></textarea>
                    <small class="asm-help">
                        Variables : <code>{prenom}</code> <code>{nom}</code> <code>{solde}</code> <code>{retard}</code> <code>{ecole}</code>
                        · <span x-text="(form.recouvrement.whatsapp_template || '').length"></span> / 1000
                    </small>
                    <small class="asm-err" x-show="erreurs['recouvrement.whatsapp_template']" x-text="erreurs['recouvrement.whatsapp_template']"></small>
                </div>
            </div>
        </form>
    </div>

    <x-m.actionbar>
        <button type="submit" form="asm-form" class="m-btn p" x-bind:disabled="mBusy">
            <x-m.icon name="check" />
            <span x-text="mBusy ? 'Enregistrement…' : 'Enregistrer les paramètres'"></span>
        </button>
    </x-m.actionbar>
</div>
@endif
</div>{{-- /.as-page --}}
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
/* ===================== ÉCRAN MOBILE — namespace asm-* ===================== */
[x-cloak] { display: none !important; }
.asm-form { display: contents; }
.asm-groupe { display: grid; gap: 12px; }
.asm-field label { text-transform: none; letter-spacing: 0; font-size: 13.5px; color: #0f172a; }
.asm-field .m-in { font-variant-numeric: tabular-nums; }
.asm-field .m-in[aria-invalid="true"] { border-color: #b42318; }
.asm-help { font-size: 11.5px; color: #64748b; line-height: 1.35; }
.asm-help code { background: #eef2f7; border-radius: 4px; padding: 1px 5px; font-size: 11px; color: #0453cb; }
.asm-err { font-size: 12px; color: #b42318; font-weight: 600; }
.asm-reset { min-height: 44px; padding: 0 4px; border: 0; background: transparent; color: #0453cb; font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; -webkit-tap-highlight-color: transparent; }
.asm-screen .m-opt label { grid-template-columns: auto 1fr auto; }
.asm-screen .m-opt label > div { display: grid; gap: 2px; }
</style>
@endpush

@push('scripts')
<script>
/* Paramètres Analytics — une fabrique pour le formulaire de bureau et le
   formulaire mobile (même état `form`). Exposée sous garde : la vue peut être
   rendue plusieurs fois sans redéclarer la fabrique. */
if (typeof window.settingsPage !== 'function') {
window.settingsPage = function (cfg) {
    var settings = cfg.settings || {};
    return {
        defaults: cfg.defaults || {},
        form: {
            default_risk: Object.assign({}, settings.default_risk || {}),
            anomaly: Object.assign({}, settings.anomaly || {}, {
                notifications_enabled: !!(settings.anomaly && settings.anomaly.notifications_enabled),
            }),
            recouvrement: Object.assign({}, settings.recouvrement || {}),
            fiabilite: Object.assign({}, settings.fiabilite || {}),
        },

        /* ---------- écran mobile ---------- */
        mSeg: 'risque',
        mBusy: false,
        erreurs: {},

        init() {
            // Le bureau affiche déjà sa bannière ; en shell mobile, le message
            // de la redirection classique devient un toast.
            if (cfg.flash && this.mShellActif()) {
                this.mToast(cfg.flash, 'success');
            }
        },

        resetSection(section) {
            const def = this.defaults[section] || {};
            for (const k of Object.keys(def)) {
                this.form[section][k] = def[k];
            }
        },
        resetWhatsappTemplate() {
            this.form.recouvrement.whatsapp_template = this.defaults.recouvrement.whatsapp_template;
        },

        mShellActif() {
            return document.body.classList.contains('has-m-shell')
                && window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches;
        },
        mToast(message, type) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: type || 'info', message: message } }));
        },
        mSegmentDe(champ) {
            if (champ.indexOf('anomaly.') === 0) { return 'anomalies'; }
            if (champ.indexOf('recouvrement.') === 0) { return 'message'; }
            return 'risque';
        },

        /* Enregistrement sans rechargement : mêmes règles de validation que le
           formulaire de bureau (422 JSON → messages sous les champs). */
        async mEnregistrer() {
            if (this.mBusy) { return; }
            this.mBusy = true;
            this.erreurs = {};
            try {
                var response = await fetch(cfg.urls.update, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        default_risk: this.form.default_risk,
                        anomaly: Object.assign({}, this.form.anomaly, {
                            notifications_enabled: this.form.anomaly.notifications_enabled ? '1' : '0',
                        }),
                        recouvrement: this.form.recouvrement,
                    }),
                });
                if (response.status === 429) {
                    this.mToast('Trop d’enregistrements à la suite, patientez une minute.', 'warning');
                    return;
                }
                var data = await response.json().catch(function () { return {}; });
                if (response.status === 422 && data.errors) {
                    var self = this, premier = null;
                    Object.keys(data.errors).forEach(function (champ) {
                        self.erreurs[champ] = Array.isArray(data.errors[champ]) ? data.errors[champ][0] : String(data.errors[champ]);
                        if (premier === null) { premier = champ; }
                    });
                    if (premier !== null) { this.mSeg = this.mSegmentDe(premier); }
                    this.mToast('Vérifiez les champs signalés.', 'error');
                    return;
                }
                if (!response.ok || !data.success) {
                    this.mToast(data.message || ('Enregistrement impossible (' + response.status + ')'), 'error');
                    return;
                }
                if (data.settings) {
                    this.form.default_risk = Object.assign({}, data.settings.default_risk || this.form.default_risk);
                    this.form.anomaly = Object.assign({}, data.settings.anomaly || this.form.anomaly, {
                        notifications_enabled: !!(data.settings.anomaly && data.settings.anomaly.notifications_enabled),
                    });
                    this.form.recouvrement = Object.assign({}, data.settings.recouvrement || this.form.recouvrement);
                }
                this.mToast(data.message || 'Paramètres enregistrés.', 'success');
            } catch (e) {
                this.mToast('Enregistrement impossible (réseau)', 'error');
            } finally {
                this.mBusy = false;
            }
        },
    };
};
}
</script>
@endpush
