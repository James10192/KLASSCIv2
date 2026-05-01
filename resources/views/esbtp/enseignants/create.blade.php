@extends('layouts.app')

@section('title', 'Nouvel Enseignant — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ═══════════════════════════════════════════════════════════
   ENSEIGNANT CREATE — Premium (namespace ec-*)
   Hero gradient bleu KLASSCI + form premium
   ═══════════════════════════════════════════════════════════ */

/* -- Hero premium ------------------------------------------- */
.ec-hero {
    position: relative;
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 0 0 20px 20px;
    margin-bottom: 24px;
    overflow: hidden;
}
.ec-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(circle at 85% 20%, rgba(255,255,255,.12) 0%, transparent 50%);
    pointer-events: none;
}
.ec-hero-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    padding: 28px 32px 24px;
    display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
}
.ec-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; color: #fff; flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
}
.ec-hero-text { flex: 1; min-width: 220px; color: #fff; }
.ec-hero-title { font-size: 1.45rem; font-weight: 800; margin: 0 0 4px; letter-spacing: -.02em; color: #fff; }
.ec-hero-sub { font-size: .85rem; opacity: .8; margin: 0 0 10px; }
.ec-hero-pills { display: flex; gap: 6px; flex-wrap: wrap; }
.ec-hero-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.15);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.25);
    color: #fff; font-size: .73rem; font-weight: 600;
    padding: 4px 10px; border-radius: 20px; white-space: nowrap;
}
.ec-hero-btns { display: flex; gap: 8px; margin-left: auto; flex-shrink: 0; }
.ec-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 10px; font-size: .82rem; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
    transition: all .18s ease; white-space: nowrap;
}
.ec-hero-btn.ghost {
    background: rgba(255,255,255,.15); color: #fff;
    border: 1px solid rgba(255,255,255,.3);
}
.ec-hero-btn.ghost:hover { background: rgba(255,255,255,.25); }

@media (max-width: 768px) {
    .ec-hero-inner { padding: 20px 16px; flex-direction: column; align-items: flex-start; }
    .ec-hero-btns { margin-left: 0; width: 100%; }
    .ec-hero-btn { flex: 1; justify-content: center; }
}

.ec-form { max-width: 1100px; margin: 0 auto; }

.ec-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    margin-bottom: 1.25rem;
    transition: box-shadow .2s ease;
}
.ec-card:hover {
    box-shadow: 0 4px 16px rgba(4,83,203,.06), 0 1px 3px rgba(15,23,42,.04);
}

.ec-card-body { padding: 1.5rem 1.75rem; }

.ec-section-header {
    display: flex; align-items: center; gap: .75rem;
    margin-bottom: 1.25rem;
}
.ec-section-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .9rem; flex-shrink: 0;
}
.ec-section-title { margin: 0; font-size: 1.05rem; font-weight: 700; color: #0f172a; }
.ec-section-sub { margin: 0; font-size: .8rem; color: #64748b; }

/* Grid de champs */
.ec-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1rem 1.25rem;
}
.ec-field { display: flex; flex-direction: column; gap: .35rem; }
.ec-field-wide { grid-column: 1 / -1; }

.ec-label {
    font-size: .8rem; font-weight: 600;
    color: #1e293b; letter-spacing: .01em;
}
.ec-label .req { color: #dc2626; margin-left: 2px; }

.ec-input,
.ec-select {
    width: 100%;
    padding: .6rem .8rem;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    font-size: .9rem;
    color: #0f172a;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.ec-input:focus,
.ec-select:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.ec-input.is-invalid,
.ec-select.is-invalid { border-color: #dc2626; }

.ec-help { font-size: .73rem; color: #64748b; line-height: 1.4; }
.ec-error { font-size: .76rem; color: #dc2626; font-weight: 500; }

/* Cards radio "Régime" */
.ec-regime-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: .75rem;
}
.ec-regime-card {
    position: relative;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem 1.1rem;
    cursor: pointer;
    transition: all .15s ease;
    background: #fff;
}
.ec-regime-card:hover { border-color: #94a3b8; transform: translateY(-1px); }
.ec-regime-card.active {
    border-color: #0453cb;
    background: rgba(4,83,203,.04);
    box-shadow: 0 0 0 3px rgba(4,83,203,.08);
}
.ec-regime-card input[type="radio"] {
    position: absolute; opacity: 0; pointer-events: none;
}
.ec-regime-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: #eef2f7;
    color: #475569;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem;
    margin-bottom: .55rem;
    transition: all .15s ease;
}
.ec-regime-card.active .ec-regime-icon {
    background: #0453cb; color: #fff;
}
.ec-regime-name { font-weight: 700; font-size: .92rem; color: #0f172a; margin: 0 0 .15rem; }
.ec-regime-desc { font-size: .73rem; color: #64748b; margin: 0; line-height: 1.4; }

/* Section pliable (profil détaillé) */
.ec-collapse-toggle {
    width: 100%;
    background: transparent; border: none;
    display: flex; align-items: center; gap: .75rem;
    padding: 0; cursor: pointer; text-align: left;
}
.ec-collapse-toggle:hover .ec-section-title { color: #0453cb; }
.ec-toggle-chevron {
    margin-left: auto;
    color: #94a3b8;
    transition: transform .25s ease;
}
.ec-card[data-collapsed="false"] .ec-toggle-chevron { transform: rotate(180deg); }

.ec-collapse-body {
    overflow: hidden;
    max-height: 0;
    transition: max-height .3s ease, margin-top .3s ease;
    margin-top: 0;
}
.ec-card[data-collapsed="false"] .ec-collapse-body {
    max-height: 1500px;
    margin-top: 1.25rem;
}

/* Conditional fields */
.ec-conditional { display: none; }
.ec-conditional.show { display: flex; }

/* Actions */
.ec-actions {
    display: flex;
    justify-content: flex-end;
    gap: .6rem;
    padding: 1.25rem 0;
}

/* Alerts */
.ec-alert {
    border-radius: 10px;
    padding: .85rem 1rem;
    margin-bottom: 1rem;
    display: flex; align-items: flex-start; gap: .65rem;
    font-size: .87rem; line-height: 1.5;
    border: 1px solid transparent;
}
.ec-alert-warning {
    background: rgba(245,158,11,.08);
    border-color: rgba(245,158,11,.25);
    color: #78350f;
}
.ec-alert-info {
    background: rgba(4,83,203,.06);
    border-color: rgba(4,83,203,.18);
    color: #1e293b;
}
.ec-alert-icon { margin-top: 2px; flex-shrink: 0; }

/* Responsive */
@media (max-width: 768px) {
    .ec-card-body { padding: 1.1rem 1.1rem; }
    .ec-grid { grid-template-columns: 1fr; }
    .ec-regime-grid { grid-template-columns: 1fr; }
    .ec-actions { flex-direction: column-reverse; }
    .ec-actions .btn-acasi { width: 100%; justify-content: center; }
}
</style>
@endsection

@section('content')
<div class="ec-hero">
    <div class="ec-hero-inner">
        <div class="ec-hero-icon"><i class="fas fa-user-plus"></i></div>
        <div class="ec-hero-text">
            <h1 class="ec-hero-title">Nouvel Enseignant</h1>
            <p class="ec-hero-sub"><i class="fas fa-info-circle" style="margin-right:4px;"></i> Créez le profil de l'enseignant. Vous pourrez compléter les détails plus tard.</p>
            <div class="ec-hero-pills">
                <span class="ec-hero-pill"><i class="fas fa-asterisk" style="font-size:.55rem"></i> 3 champs requis</span>
                <span class="ec-hero-pill"><i class="fas fa-briefcase"></i> Régime à choisir</span>
                <span class="ec-hero-pill"><i class="fas fa-user-graduate"></i> Profil détaillé optionnel</span>
            </div>
        </div>
        <div class="ec-hero-btns">
            <a href="{{ route('esbtp.personnel.unified.index') }}" class="ec-hero-btn ghost">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
</div>

<div class="dashboard-acasi">
    <div class="main-content">


        @if($errors->any())
            <div class="ec-alert ec-alert-warning">
                <i class="fas fa-exclamation-triangle ec-alert-icon"></i>
                <div>
                    <strong>Veuillez corriger les erreurs suivantes :</strong>
                    <ul style="margin: .35rem 0 0; padding-left: 1.15rem;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('esbtp.enseignants.store') }}" method="POST" id="teacherForm" class="ec-form">
            @csrf

            {{-- Section 1 : Essentiel --}}
            <div class="ec-card">
                <div class="ec-card-body">
                    <div class="ec-section-header">
                        <div class="ec-section-icon"><i class="fas fa-id-card"></i></div>
                        <div>
                            <h3 class="ec-section-title">Informations essentielles</h3>
                            <p class="ec-section-sub">Trois champs suffisent pour démarrer.</p>
                        </div>
                    </div>

                    <div class="ec-grid">
                        <div class="ec-field">
                            <label for="name" class="ec-label">Nom complet <span class="req">*</span></label>
                            <input type="text" name="name" id="name" required
                                   value="{{ old('name') }}"
                                   class="ec-input @error('name') is-invalid @enderror"
                                   autocomplete="name" autofocus>
                            @error('name') <div class="ec-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="ec-field">
                            <label for="phone" class="ec-label">Téléphone <span class="req">*</span></label>
                            <input type="tel" name="phone" id="phone" required
                                   value="{{ old('phone') }}"
                                   placeholder="07 00 00 00 00"
                                   class="ec-input @error('phone') is-invalid @enderror"
                                   autocomplete="tel">
                            <small class="ec-help">Indispensable pour les communications école ↔ enseignant.</small>
                            @error('phone') <div class="ec-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="ec-field ec-field-wide">
                            <label for="specialization" class="ec-label">Spécialisation <span class="req">*</span></label>
                            <input type="text" name="specialization" id="specialization" required
                                   value="{{ old('specialization') }}"
                                   placeholder="ex : Mathématiques, Génie civil, Réseaux informatiques"
                                   class="ec-input @error('specialization') is-invalid @enderror">
                            <small class="ec-help">La discipline principale enseignée. Pas de liste fermée — saisissez librement.</small>
                            @error('specialization') <div class="ec-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="ec-alert ec-alert-info" style="margin-top: 1rem; margin-bottom: 0;">
                        <i class="fas fa-info-circle ec-alert-icon"></i>
                        <div>
                            <strong>Identifiants générés automatiquement.</strong>
                            Le nom d'utilisateur et le mot de passe par défaut seront créés à la sauvegarde.
                            L'enseignant devra changer son mot de passe à la première connexion.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2 : Régime --}}
            <div class="ec-card">
                <div class="ec-card-body">
                    <div class="ec-section-header">
                        <div class="ec-section-icon"><i class="fas fa-briefcase"></i></div>
                        <div>
                            <h3 class="ec-section-title">Régime d'engagement</h3>
                            <p class="ec-section-sub">Définit le mode de collaboration avec l'école.</p>
                        </div>
                    </div>

                    <div class="ec-regime-grid" id="regimeGrid">
                        @php
                            $regimeOptions = [
                                'vacataire' => ['label' => 'Vacataire', 'desc' => 'Heure facturée, contrat semestriel', 'icon' => 'fa-clock'],
                                'permanent' => ['label' => 'Permanent', 'desc' => 'Salaire mensuel, charge fixe', 'icon' => 'fa-user-tie'],
                                'consultant' => ['label' => 'Consultant', 'desc' => 'Mission ponctuelle, expertise', 'icon' => 'fa-handshake'],
                            ];
                            $selectedRegime = old('regime', 'vacataire');
                        @endphp
                        @foreach($regimeOptions as $key => $opt)
                            <label class="ec-regime-card {{ $selectedRegime === $key ? 'active' : '' }}" data-regime="{{ $key }}">
                                <input type="radio" name="regime" value="{{ $key }}" {{ $selectedRegime === $key ? 'checked' : '' }}>
                                <div class="ec-regime-icon"><i class="fas {{ $opt['icon'] }}"></i></div>
                                <p class="ec-regime-name">{{ $opt['label'] }}</p>
                                <p class="ec-regime-desc">{{ $opt['desc'] }}</p>
                            </label>
                        @endforeach
                    </div>

                    <div class="ec-grid" style="margin-top: 1.25rem;">
                        <div class="ec-field ec-conditional show" id="tauxField">
                            <label for="taux_horaire" class="ec-label">Taux horaire (FCFA/heure)</label>
                            <input type="number" name="taux_horaire" id="taux_horaire"
                                   value="{{ old('taux_horaire') }}"
                                   min="0" step="500"
                                   placeholder="ex: 5000"
                                   class="ec-input @error('taux_horaire') is-invalid @enderror">
                            <small class="ec-help">Tarif facturé par heure de cours. Optionnel.</small>
                            @error('taux_horaire') <div class="ec-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="ec-field ec-conditional" id="chargeField">
                            <label for="charge_horaire_max_semaine" class="ec-label">Charge hebdomadaire (h/sem)</label>
                            <input type="number" name="charge_horaire_max_semaine" id="charge_horaire_max_semaine"
                                   value="{{ old('charge_horaire_max_semaine', 18) }}"
                                   min="1" max="60"
                                   class="ec-input @error('charge_horaire_max_semaine') is-invalid @enderror">
                            <small class="ec-help">Heures maximales par semaine pour ce permanent.</small>
                            @error('charge_horaire_max_semaine') <div class="ec-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="ec-field">
                            <label for="date_debut_activite" class="ec-label">Date de début d'activité</label>
                            <input type="date" name="date_debut_activite" id="date_debut_activite"
                                   value="{{ old('date_debut_activite', date('Y-m-d')) }}"
                                   class="ec-input @error('date_debut_activite') is-invalid @enderror">
                            <small class="ec-help">Pré-rempli à aujourd'hui. Modifiable.</small>
                            @error('date_debut_activite') <div class="ec-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 3 : Profil détaillé (collapsable, optionnel) --}}
            <div class="ec-card" data-collapsed="true" id="profileCard">
                <div class="ec-card-body">
                    <button type="button" class="ec-collapse-toggle" id="profileToggle" aria-expanded="false">
                        <div class="ec-section-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <h3 class="ec-section-title">Profil détaillé</h3>
                            <p class="ec-section-sub">Email, titre, diplômes — optionnel, modifiable plus tard.</p>
                        </div>
                        <i class="fas fa-chevron-down ec-toggle-chevron"></i>
                    </button>

                    <div class="ec-collapse-body">
                        <div class="ec-grid">
                            <div class="ec-field">
                                <label for="email" class="ec-label">Email</label>
                                <input type="email" name="email" id="email"
                                       value="{{ old('email') }}"
                                       class="ec-input @error('email') is-invalid @enderror"
                                       autocomplete="email">
                                <small class="ec-help">Pour les notifications et la connexion (optionnel).</small>
                                @error('email') <div class="ec-error">{{ $message }}</div> @enderror
                            </div>

                            <div class="ec-field">
                                <label for="titre_academique" class="ec-label">Titre</label>
                                <select name="titre_academique" id="titre_academique" class="ec-select">
                                    <option value="">—</option>
                                    @foreach($titres_academiques as $key => $value)
                                        <option value="{{ $key }}" {{ old('titre_academique') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="ec-field">
                                <label for="grade_academique" class="ec-label">Grade académique</label>
                                <select name="grade_academique" id="grade_academique" class="ec-select">
                                    <option value="">—</option>
                                    @foreach($grades_academiques as $key => $value)
                                        <option value="{{ $key }}" {{ old('grade_academique') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="ec-field">
                                <label for="diplome_principal" class="ec-label">Diplôme principal</label>
                                <input type="text" name="diplome_principal" id="diplome_principal"
                                       value="{{ old('diplome_principal') }}"
                                       placeholder="ex : Master en Génie civil"
                                       class="ec-input">
                            </div>

                            <div class="ec-field">
                                <label for="universite_diplome" class="ec-label">Université / Institut</label>
                                <input type="text" name="universite_diplome" id="universite_diplome"
                                       value="{{ old('universite_diplome') }}"
                                       placeholder="ex : Université Félix Houphouët-Boigny"
                                       class="ec-input">
                            </div>

                            <div class="ec-field">
                                <label for="annee_diplome" class="ec-label">Année d'obtention</label>
                                <input type="number" name="annee_diplome" id="annee_diplome"
                                       value="{{ old('annee_diplome') }}"
                                       min="1950" max="{{ date('Y') }}"
                                       class="ec-input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Avertissement doublon --}}
            <div id="duplicate-warning" class="ec-alert ec-alert-warning" style="display: none;">
                <i class="fas fa-exclamation-triangle ec-alert-icon"></i>
                <div>
                    <strong>Attention : doublon potentiel détecté</strong>
                    <div id="duplicate-warning-text" style="margin-top: .25rem;"></div>
                    <button type="button" class="btn-acasi secondary" id="show-duplicates-modal" style="margin-top: .5rem; padding: .35rem .85rem; font-size: .82rem;">
                        Voir les doublons
                    </button>
                </div>
            </div>

            <input type="hidden" name="duplicate_override" id="duplicate_override" value="0">

            <div class="ec-actions">
                <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-acasi secondary">Annuler</a>
                <button type="submit" class="btn-acasi primary" id="submitBtn">
                    <i class="fas fa-check me-1"></i> Créer l'enseignant
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal doublons --}}
<div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateModalLabel">Doublons potentiels détectés</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Nous avons trouvé des enseignants similaires dans la base :</p>
                <div id="duplicate-modal-content">
                    <div class="ec-alert ec-alert-info">
                        <i class="fas fa-info-circle ec-alert-icon"></i>
                        <div>Aucun doublon détecté.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn-acasi primary" id="continue-with-duplicate">Continuer la création</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // ─── Régime cards : radio behavior + conditional fields ──────────
    const regimeGrid = document.getElementById('regimeGrid');
    const tauxField = document.getElementById('tauxField');
    const chargeField = document.getElementById('chargeField');

    function applyRegime(regime) {
        regimeGrid.querySelectorAll('.ec-regime-card').forEach(card => {
            card.classList.toggle('active', card.dataset.regime === regime);
        });
        // Vacataire / Consultant → taux horaire visible
        // Permanent → charge horaire visible
        tauxField.classList.toggle('show', regime !== 'permanent');
        chargeField.classList.toggle('show', regime === 'permanent');
    }

    regimeGrid.querySelectorAll('.ec-regime-card').forEach(card => {
        card.addEventListener('click', () => {
            const radio = card.querySelector('input[type="radio"]');
            radio.checked = true;
            applyRegime(card.dataset.regime);
        });
    });

    // Apply initial state from old() value
    const checkedRadio = regimeGrid.querySelector('input[name="regime"]:checked');
    if (checkedRadio) applyRegime(checkedRadio.value);

    // ─── Section pliable : profil détaillé ──────────────────────────
    const profileToggle = document.getElementById('profileToggle');
    const profileCard = document.getElementById('profileCard');
    profileToggle.addEventListener('click', () => {
        const collapsed = profileCard.dataset.collapsed === 'true';
        profileCard.dataset.collapsed = collapsed ? 'false' : 'true';
        profileToggle.setAttribute('aria-expanded', collapsed ? 'true' : 'false');
    });

    // ─── Détection de doublons (debounced) ──────────────────────────
    const duplicateForm = document.getElementById('teacherForm');
    const duplicateOverride = document.getElementById('duplicate_override');
    const duplicateWarning = document.getElementById('duplicate-warning');
    const duplicateWarningText = document.getElementById('duplicate-warning-text');
    const duplicateModalEl = document.getElementById('duplicateModal');
    const duplicateModalContent = document.getElementById('duplicate-modal-content');
    const showDuplicatesBtn = document.getElementById('show-duplicates-modal');
    const continueBtn = document.getElementById('continue-with-duplicate');
    const duplicateUrl = "{{ route('esbtp.enseignants.duplicates') }}";

    let duplicateModalInstance = null;
    if (duplicateModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        duplicateModalInstance = new bootstrap.Modal(duplicateModalEl);
    }

    const state = { results: [], override: false };
    let timer = null;

    function resetOverride() {
        state.override = false;
        if (duplicateOverride) duplicateOverride.value = '0';
    }

    function schedule() {
        if (!duplicateUrl) return;
        if (timer) clearTimeout(timer);
        timer = setTimeout(check, 600);
        resetOverride();
    }

    function check() {
        const nameValue = (document.getElementById('name').value || '').trim();
        if (nameValue.length < 3) {
            state.results = [];
            renderUI();
            return;
        }

        const params = new URLSearchParams({ name: nameValue });
        const spec = (document.getElementById('specialization').value || '').trim();
        if (spec) params.append('specialization', spec);

        fetch(`${duplicateUrl}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        })
            .then(r => r.json())
            .then(data => {
                state.results = Array.isArray(data.duplicates) ? data.duplicates : [];
                resetOverride();
                renderUI();
            })
            .catch(() => {
                state.results = [];
                renderUI();
            });
    }

    function renderUI() {
        if (state.results.length === 0) {
            duplicateWarning.style.display = 'none';
            if (duplicateOverride) duplicateOverride.value = '0';
            if (duplicateModalInstance) duplicateModalInstance.hide();
            return;
        }

        if (state.override) {
            duplicateWarning.style.display = 'none';
            if (duplicateOverride) duplicateOverride.value = '1';
            return;
        }

        duplicateWarning.style.display = 'flex';
        duplicateWarningText.textContent = `${state.results.length} enseignant(s) avec un profil similaire ont été trouvés.`;
        renderModal();
    }

    function renderModal() {
        if (!duplicateModalContent) return;
        if (state.results.length === 0) {
            duplicateModalContent.innerHTML = '';
            return;
        }
        let html = '<div class="list-group">';
        state.results.forEach(t => {
            const name = (t.name || '').replace(/[<>]/g, '');
            const email = (t.email || '').replace(/[<>]/g, '');
            const spec = (t.specialization || 'N/A').replace(/[<>]/g, '');
            const matricule = (t.matricule || '').replace(/[<>]/g, '');
            html += `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">${name}</h6>
                            <p class="mb-1 text-muted"><small>Email : ${email || '—'}</small></p>
                            <p class="mb-1 text-muted"><small>Spécialisation : ${spec}</small></p>
                            <p class="mb-0 text-muted"><small>Matricule : ${matricule}</small></p>
                        </div>
                        <a href="${t.show_url}" target="_blank" class="btn-acasi secondary" style="padding: .25rem .65rem; font-size: .78rem;">
                            <i class="fas fa-eye"></i> Voir
                        </a>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        duplicateModalContent.innerHTML = html;
    }

    document.getElementById('name').addEventListener('input', schedule);
    document.getElementById('specialization').addEventListener('input', schedule);

    if (showDuplicatesBtn) {
        showDuplicatesBtn.addEventListener('click', () => {
            if (duplicateModalInstance) duplicateModalInstance.show();
        });
    }
    if (continueBtn) {
        continueBtn.addEventListener('click', () => {
            state.override = true;
            renderUI();
            if (duplicateModalInstance) duplicateModalInstance.hide();
        });
    }
})();
</script>
@endpush
