@extends('layouts.app')

@section('title', 'Modifier Enseignant — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ═══════════════════════════════════════════════════════════
   ENSEIGNANT EDIT — Premium (namespace ee-*)
   Hero conservé (.es-edit-*), formulaire refondu sans wizard.
   ═══════════════════════════════════════════════════════════ */

/* -- Hero (identique create/show) ---------------------------------- */
.es-edit-hero {
    position: relative;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    padding: 0; margin-bottom: 24px;
    border-radius: 0 0 20px 20px;
}
.es-edit-hero-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    padding: 28px 32px 24px;
    display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
}
.es-edit-avatar {
    width: 72px; height: 72px; border-radius: 50%;
    border: 3px solid rgba(255,255,255,.6);
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; font-weight: 700; color: rgba(255,255,255,.9);
    overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,.2);
    backdrop-filter: blur(4px); flex-shrink: 0;
}
.es-edit-avatar img { width: 100%; height: 100%; object-fit: cover; }
.es-edit-text { flex: 1; min-width: 200px; color: #fff; }
.es-edit-name { font-size: 1.4rem; font-weight: 800; margin: 0 0 2px; letter-spacing: -.02em; }
.es-edit-sub { font-size: .84rem; opacity: .8; margin: 0 0 8px; }
.es-edit-pills { display: flex; gap: 6px; flex-wrap: wrap; }
.es-edit-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.18); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.28);
    color: #fff; font-size: .74rem; font-weight: 600;
    padding: 3px 10px; border-radius: 20px; white-space: nowrap;
}
.es-edit-pill.green { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.4); }
.es-edit-btns { display: flex; gap: 8px; margin-left: auto; flex-shrink: 0; }
.es-edit-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 8px; font-size: .8rem; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer; transition: all .18s; white-space: nowrap;
}
.es-edit-btn.primary { background: rgba(255,255,255,.95); color: #0453cb; }
.es-edit-btn.primary:hover { background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.es-edit-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.35); }
.es-edit-btn.ghost:hover { background: rgba(255,255,255,.25); }

@include('esbtp.enseignants.partials._form-styles', ['ns' => 'ee'])

/* -- Edit-only : textarea + status toggle + availability grid ------ */
.ee-textarea {
    width: 100%;
    padding: .6rem .8rem;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    font-size: .9rem;
    color: #0f172a;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
    min-height: 90px;
    resize: vertical;
    font-family: inherit;
}
.ee-textarea:focus {
    outline: none; border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}

/* Toggle status */
.ee-status-toggle {
    display: inline-flex; align-items: center; gap: .75rem;
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: .35rem .8rem .35rem .35rem;
}
.ee-status-toggle input[type="checkbox"] { display: none; }
.ee-status-switch {
    width: 40px; height: 22px;
    background: #cbd5e1; border-radius: 999px;
    position: relative;
    transition: background .2s;
    cursor: pointer;
}
.ee-status-switch::before {
    content: '';
    position: absolute;
    top: 2px; left: 2px;
    width: 18px; height: 18px;
    background: #fff; border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,.15);
    transition: transform .2s;
}
input[type="checkbox"]:checked + .ee-status-switch { background: #10b981; }
input[type="checkbox"]:checked + .ee-status-switch::before { transform: translateX(18px); }
.ee-status-label { font-size: .85rem; font-weight: 600; color: #1e293b; }

/* Disponibilités grid */
.ee-avail-table { width: 100%; border-collapse: separate; border-spacing: 4px; margin-top: .5rem; }
.ee-avail-table th { font-size: .72rem; color: #64748b; font-weight: 600; padding: .35rem; text-align: center; }
.ee-avail-table td.ee-time { font-size: .76rem; color: #475569; font-weight: 600; text-align: right; padding-right: .65rem; white-space: nowrap; }
.ee-avail-cell {
    height: 34px;
    border-radius: 7px;
    cursor: pointer;
    background: #f1f5f9;
    border: 1.5px solid transparent;
    transition: all .15s;
}
.ee-avail-cell:hover { transform: scale(1.05); border-color: #0453cb; }
.ee-avail-cell.available { background: rgba(16,185,129,.18); }
.ee-avail-cell.preferred { background: rgba(4,83,203,.25); }
.ee-avail-cell.unavailable { background: #f1f5f9; }
.ee-avail-legend { display: flex; gap: 1.25rem; font-size: .8rem; color: #475569; margin-top: 1rem; flex-wrap: wrap; }
.ee-avail-legend-dot { width: 14px; height: 14px; border-radius: 4px; display: inline-block; margin-right: .35rem; vertical-align: middle; }
.ee-avail-legend-dot.unavailable { background: #f1f5f9; border: 1px solid #cbd5e1; }
.ee-avail-legend-dot.available { background: rgba(16,185,129,.4); }
.ee-avail-legend-dot.preferred { background: rgba(4,83,203,.5); }

/* Edit-specific actions layout (justify-content: space-between vs flex-end du partial) */
.ee-actions { justify-content: space-between; align-items: center; }
.ee-actions-right { display: flex; gap: .6rem; }

@media (max-width: 768px) {
    .es-edit-hero-inner { padding: 20px 16px; flex-direction: column; text-align: center; }
    .es-edit-pills { justify-content: center; }
    .es-edit-btns { margin-left: 0; justify-content: center; }
    .ee-actions-right { width: 100%; }
    .ee-avail-table { font-size: .7rem; }
}
</style>
@endsection

@section('content')
{{-- Hero --}}
<div class="es-edit-hero">
    <div class="es-edit-hero-inner">
        <div class="es-edit-avatar">
            @if($teacher->user && $teacher->user->photo_url)
                <img src="{{ $teacher->user->photo_url }}" alt="{{ $teacher->user->name }}">
            @else
                {{ $teacher->user ? strtoupper(substr($teacher->user->name, 0, 2)) : 'NN' }}
            @endif
        </div>
        <div class="es-edit-text">
            <h1 class="es-edit-name">{{ $teacher->user->name ?? 'Nom non disponible' }}</h1>
            <p class="es-edit-sub"><i class="fas fa-user-edit" style="margin-right:4px;"></i> Modification du profil enseignant</p>
            <div class="es-edit-pills">
                <span class="es-edit-pill"><i class="fas fa-id-card"></i> {{ $teacher->matricule ?? 'N/A' }}</span>
                <span class="es-edit-pill {{ $teacher->status === 'active' ? 'green' : '' }}">
                    <i class="fas fa-circle" style="font-size:.5rem"></i>
                    {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                </span>
                @if($teacher->specialization)
                    <span class="es-edit-pill"><i class="fas fa-star"></i> {{ $teacher->specialization }}</span>
                @endif
            </div>
        </div>
        <div class="es-edit-btns">
            <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $teacher->id]) }}" class="es-edit-btn primary">
                <i class="fas fa-eye"></i> Voir le profil
            </a>
            <a href="{{ route('esbtp.personnel.unified.index') }}" class="es-edit-btn ghost">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>
</div>

<div class="dashboard-acasi">
    <div class="main-content">

        @if($errors->any())
            <div class="ee-alert ee-alert-warning">
                <i class="fas fa-exclamation-triangle ee-alert-icon"></i>
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

        @if(session('success'))
            <div class="ee-alert ee-alert-success">
                <i class="fas fa-check-circle ee-alert-icon"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        <form action="{{ route('esbtp.enseignants.update', ['enseignant' => $teacher->id]) }}"
              method="POST" id="teacherForm" class="ee-form">
            @csrf
            @method('PUT')

            {{-- Section : Identité & contact --}}
            <div class="ee-card">
                <div class="ee-card-body">
                    <div class="ee-section-header">
                        <div class="ee-section-icon"><i class="fas fa-id-card"></i></div>
                        <div>
                            <h3 class="ee-section-title">Identité & contact</h3>
                            <p class="ee-section-sub">Informations principales de l'enseignant.</p>
                        </div>
                    </div>

                    <div class="ee-grid">
                        <div class="ee-field">
                            <label for="name" class="ee-label">Nom complet <span class="req">*</span></label>
                            <input type="text" name="name" id="name" required
                                   value="{{ old('name', $teacher->user->name ?? '') }}"
                                   class="ee-input @error('name') is-invalid @enderror">
                            @error('name') <div class="ee-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="ee-field">
                            <label for="phone" class="ee-label">Téléphone</label>
                            <input type="tel" name="phone" id="phone"
                                   value="{{ old('phone', $teacher->user->phone ?? '') }}"
                                   class="ee-input @error('phone') is-invalid @enderror">
                            @error('phone') <div class="ee-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="ee-field">
                            <label for="email" class="ee-label">Email</label>
                            <input type="email" name="email" id="email"
                                   value="{{ old('email', $teacher->user->email ?? '') }}"
                                   class="ee-input @error('email') is-invalid @enderror">
                            <small class="ee-help">Optionnel, utilisé pour les notifications.</small>
                            @error('email') <div class="ee-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="ee-field">
                            <label for="titre_academique" class="ee-label">Titre</label>
                            <select name="titre_academique" id="titre_academique" class="ee-select">
                                <option value="">—</option>
                                @foreach($titres_academiques as $key => $value)
                                    <option value="{{ $key }}" {{ old('titre_academique', $teacher->title) == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="ee-field ee-field-wide">
                            <label for="specialization" class="ee-label">Spécialisation <span class="req">*</span></label>
                            <input type="text" name="specialization" id="specialization" required
                                   value="{{ old('specialization', $teacher->specialization) }}"
                                   placeholder="ex : Mathématiques, Génie civil, Réseaux"
                                   class="ee-input @error('specialization') is-invalid @enderror">
                            <small class="ee-help">La discipline principale enseignée.</small>
                            @error('specialization') <div class="ee-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section : Régime --}}
            <div class="ee-card">
                <div class="ee-card-body">
                    <div class="ee-section-header">
                        <div class="ee-section-icon"><i class="fas fa-briefcase"></i></div>
                        <div>
                            <h3 class="ee-section-title">Régime d'engagement</h3>
                            <p class="ee-section-sub">Mode de collaboration avec l'école.</p>
                        </div>
                    </div>

                    @php
                        $selectedRegime = old('regime', $teacher->regime ?? \App\Enums\TeacherRegime::Vacataire->value);
                        $currentDate = old('date_debut_activite', $teacher->date_debut_activite ? $teacher->date_debut_activite->format('Y-m-d') : '');
                        $currentTaux = old('taux_horaire', $teacher->taux_horaire ?? '');
                        $currentCharge = old('charge_horaire_max_semaine', $teacher->teaching_hours_due ?? 18);
                        $currentTauxParType = old('taux_par_type', $teacher->tauxParTypeMap());
                    @endphp

                    <div class="ee-regime-grid" id="regimeGrid">
                        @foreach(\App\Enums\TeacherRegime::cases() as $regime)
                            <label class="ee-regime-card {{ $selectedRegime === $regime->value ? 'active' : '' }}" data-regime="{{ $regime->value }}">
                                <input type="radio" name="regime" value="{{ $regime->value }}" {{ $selectedRegime === $regime->value ? 'checked' : '' }}>
                                <div class="ee-regime-icon"><i class="fas {{ $regime->icon() }}"></i></div>
                                <p class="ee-regime-name">{{ $regime->label() }}</p>
                                <p class="ee-regime-desc">{{ $regime->description() }}</p>
                            </label>
                        @endforeach
                    </div>

                    <div class="ee-grid" style="margin-top: 1.25rem;">
                        @can('comptabilite.salaires.set_rate')
                        <div class="ee-field ee-conditional {{ $selectedRegime !== 'permanent' ? 'show' : '' }}" id="tauxField">
                            <label for="taux_horaire" class="ee-label">Taux horaire par défaut (FCFA/heure)</label>
                            <input type="number" name="taux_horaire" id="taux_horaire"
                                   value="{{ $currentTaux }}"
                                   min="0" step="500"
                                   class="ee-input @error('taux_horaire') is-invalid @enderror">
                            <small class="ee-help">Appliqué quand aucun taux par type n'est défini.</small>
                            @error('taux_horaire') <div class="ee-error">{{ $message }}</div> @enderror
                        </div>
                        @endcan

                        <div class="ee-field ee-conditional {{ $selectedRegime === 'permanent' ? 'show' : '' }}" id="chargeField">
                            <label for="charge_horaire_max_semaine" class="ee-label">Charge hebdomadaire (h/sem)</label>
                            <input type="number" name="charge_horaire_max_semaine" id="charge_horaire_max_semaine"
                                   value="{{ $currentCharge }}"
                                   min="1" max="60"
                                   class="ee-input @error('charge_horaire_max_semaine') is-invalid @enderror">
                            @error('charge_horaire_max_semaine') <div class="ee-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="ee-field">
                            <label for="date_debut_activite" class="ee-label">Date de début d'activité</label>
                            <input type="date" name="date_debut_activite" id="date_debut_activite"
                                   value="{{ $currentDate }}"
                                   class="ee-input @error('date_debut_activite') is-invalid @enderror">
                            @error('date_debut_activite') <div class="ee-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    @can('comptabilite.salaires.set_rate')
                    {{-- Taux par type de séance (LMD) — gated comptabilite.salaires.set_rate --}}
                    <div class="ee-taux-types" id="tauxTypesBlock">
                        <div class="ee-taux-types-head">
                            <i class="fas fa-coins"></i>
                            <div>
                                <p class="ee-taux-types-title">Taux par type de séance <span class="ee-taux-badge">LMD</span></p>
                                <p class="ee-taux-types-sub">Optionnel — un taux distinct par CM / TD / TP. Laissez vide pour utiliser le taux par défaut.</p>
                            </div>
                        </div>
                        <div class="ee-taux-grid">
                            @foreach(\App\Enums\TypeSeance::cases() as $t)
                                @if($t->isVolumeTracked())
                                    <div class="ee-taux-cell">
                                        <label for="taux_{{ $t->value }}" class="ee-taux-label">
                                            <span class="ee-taux-chip" style="{{ $t->badgeInlineStyle() }}">
                                                <i class="fas {{ $t->badgeIcon() }}"></i> {{ $t->value }}
                                            </span>
                                            {{ $t->label() }}
                                        </label>
                                        <div class="ee-taux-input-wrap">
                                            <input type="number" name="taux_par_type[{{ $t->value }}]" id="taux_{{ $t->value }}"
                                                   value="{{ $currentTauxParType[$t->value] ?? '' }}"
                                                   min="0" step="500" placeholder="défaut"
                                                   class="ee-input @error('taux_par_type.' . $t->value) is-invalid @enderror">
                                            <span class="ee-taux-unit">FCFA/h</span>
                                        </div>
                                        @error('taux_par_type.' . $t->value) <div class="ee-error">{{ $message }}</div> @enderror
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endcan
                </div>
            </div>

            {{-- Section : Profil détaillé (collapsable) --}}
            <div class="ee-card" data-collapsed="{{ ($teacher->diplome_principal || $teacher->grade) ? 'false' : 'true' }}" id="profileCard">
                <div class="ee-card-body">
                    <button type="button" class="ee-collapse-toggle" id="profileToggle" aria-expanded="{{ ($teacher->diplome_principal || $teacher->grade) ? 'true' : 'false' }}">
                        <div class="ee-section-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <h3 class="ee-section-title">Profil détaillé</h3>
                            <p class="ee-section-sub">Diplômes, grade académique, biographie.</p>
                        </div>
                        <i class="fas fa-chevron-down ee-toggle-chevron"></i>
                    </button>

                    <div class="ee-collapse-body">
                        <div class="ee-grid">
                            <div class="ee-field">
                                <label for="grade_academique" class="ee-label">Grade académique</label>
                                <select name="grade_academique" id="grade_academique" class="ee-select">
                                    <option value="">—</option>
                                    @foreach($grades_academiques as $key => $value)
                                        <option value="{{ $key }}" {{ old('grade_academique', $teacher->grade ?? '') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="ee-field">
                                <label for="diplome_principal" class="ee-label">Diplôme principal</label>
                                <input type="text" name="diplome_principal" id="diplome_principal"
                                       value="{{ old('diplome_principal', $teacher->diplome_principal ?? '') }}"
                                       class="ee-input">
                            </div>

                            <div class="ee-field">
                                <label for="universite_diplome" class="ee-label">Université / Institut</label>
                                <input type="text" name="universite_diplome" id="universite_diplome"
                                       value="{{ old('universite_diplome', $teacher->universite_diplome ?? '') }}"
                                       class="ee-input">
                            </div>

                            <div class="ee-field">
                                <label for="annee_diplome" class="ee-label">Année d'obtention</label>
                                <input type="number" name="annee_diplome" id="annee_diplome"
                                       value="{{ old('annee_diplome', $teacher->annee_diplome ?? '') }}"
                                       min="1950" max="{{ date('Y') }}"
                                       class="ee-input">
                            </div>

                            <div class="ee-field ee-field-wide">
                                <label for="bio" class="ee-label">Biographie</label>
                                <textarea name="bio" id="bio" rows="3"
                                          class="ee-textarea @error('bio') is-invalid @enderror">{{ old('bio', $teacher->bio) }}</textarea>
                                @error('bio') <div class="ee-error">{{ $message }}</div> @enderror
                            </div>

                            <div class="ee-field ee-field-wide">
                                <label for="website" class="ee-label">Site web / Portfolio</label>
                                <input type="url" name="website" id="website"
                                       value="{{ old('website', $teacher->website) }}"
                                       placeholder="https://"
                                       class="ee-input @error('website') is-invalid @enderror">
                                @error('website') <div class="ee-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section : Disponibilités --}}
            <div class="ee-card">
                <div class="ee-card-body">
                    <div class="ee-section-header">
                        <div class="ee-section-icon"><i class="fas fa-calendar-week"></i></div>
                        <div>
                            <h3 class="ee-section-title">Disponibilités hebdomadaires</h3>
                            <p class="ee-section-sub">Cliquez sur un créneau pour faire défiler : indisponible → disponible → préféré.</p>
                        </div>
                    </div>

                    @php
                        $hours = range(8, 18);
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        $dayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
                    @endphp

                    <table class="ee-avail-table">
                        <thead>
                            <tr>
                                <th></th>
                                @foreach($dayLabels as $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hours as $i => $hour)
                                <tr>
                                    <td class="ee-time">{{ sprintf('%02d:00', $hour) }}</td>
                                    @foreach($days as $dayIdx => $day)
                                        @php
                                            $status = $availabilityData[$day][$i] ?? 'unavailable';
                                            $key = $dayIdx . '_' . $hour;
                                        @endphp
                                        <td>
                                            <div class="ee-avail-cell {{ $status }}"
                                                 data-key="{{ $key }}"
                                                 data-status="{{ $status }}"></div>
                                            <input type="hidden" name="availability[{{ $key }}]" value="{{ $status }}">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="ee-avail-legend">
                        <span><i class="ee-avail-legend-dot unavailable"></i>Indisponible</span>
                        <span><i class="ee-avail-legend-dot available"></i>Disponible</span>
                        <span><i class="ee-avail-legend-dot preferred"></i>Préféré</span>
                    </div>
                </div>
            </div>

            {{-- Section : Statut --}}
            <div class="ee-card">
                <div class="ee-card-body" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div class="ee-section-header" style="margin-bottom: 0;">
                        <div class="ee-section-icon" style="background: linear-gradient(135deg, #10b981, #34d399);"><i class="fas fa-toggle-on"></i></div>
                        <div>
                            <h3 class="ee-section-title">Statut du compte</h3>
                            <p class="ee-section-sub">Désactiver bloque l'accès à l'application.</p>
                        </div>
                    </div>
                    <label class="ee-status-toggle">
                        <input type="checkbox" name="status_toggle" id="statusToggle" {{ ($teacher->status === 'active') ? 'checked' : '' }}>
                        <span class="ee-status-switch"></span>
                        <span class="ee-status-label" id="statusLabel">{{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}</span>
                    </label>
                    <input type="hidden" name="status" id="statusHidden" value="{{ $teacher->status }}">
                </div>
            </div>

            <div class="ee-actions">
                <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $teacher->id]) }}" class="btn-acasi secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <div class="ee-actions-right">
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-check me-1"></i> Enregistrer les modifications
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // ─── Régime cards ──────────────────────────────────────────────
    const regimeGrid = document.getElementById('regimeGrid');
    const tauxField = document.getElementById('tauxField');
    const chargeField = document.getElementById('chargeField');

    function applyRegime(regime) {
        regimeGrid.querySelectorAll('.ee-regime-card').forEach(c => {
            c.classList.toggle('active', c.dataset.regime === regime);
        });
        tauxField.classList.toggle('show', regime !== 'permanent');
        chargeField.classList.toggle('show', regime === 'permanent');
    }
    regimeGrid.querySelectorAll('.ee-regime-card').forEach(card => {
        card.addEventListener('click', () => {
            const radio = card.querySelector('input[type="radio"]');
            radio.checked = true;
            applyRegime(card.dataset.regime);
        });
    });

    // ─── Profil détaillé (collapse) ────────────────────────────────
    const profileToggle = document.getElementById('profileToggle');
    const profileCard = document.getElementById('profileCard');
    profileToggle.addEventListener('click', () => {
        const collapsed = profileCard.dataset.collapsed === 'true';
        profileCard.dataset.collapsed = collapsed ? 'false' : 'true';
        profileToggle.setAttribute('aria-expanded', collapsed ? 'true' : 'false');
    });

    // ─── Disponibilités : click cycle ───────────────────────────────
    const cycle = ['unavailable', 'available', 'preferred'];
    document.querySelectorAll('.ee-avail-cell').forEach(cell => {
        cell.addEventListener('click', () => {
            const current = cell.dataset.status;
            const next = cycle[(cycle.indexOf(current) + 1) % cycle.length];
            cell.dataset.status = next;
            cell.classList.remove('unavailable', 'available', 'preferred');
            cell.classList.add(next);
            const hidden = cell.parentElement.querySelector('input[type="hidden"]');
            if (hidden) hidden.value = next;
        });
    });

    // ─── Statut toggle ──────────────────────────────────────────────
    const statusToggle = document.getElementById('statusToggle');
    const statusLabel = document.getElementById('statusLabel');
    const statusHidden = document.getElementById('statusHidden');
    statusToggle.addEventListener('change', () => {
        const active = statusToggle.checked;
        statusHidden.value = active ? 'active' : 'inactive';
        statusLabel.textContent = active ? 'Actif' : 'Inactif';
    });
})();
</script>
@endpush
