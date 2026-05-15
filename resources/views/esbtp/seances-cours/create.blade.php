@extends(request()->boolean('embed') ? 'layouts.embedded' : 'layouts.app')

@section('title', 'Ajouter une séance - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<style>
    .session-type-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    .session-type-card:hover {
        transform: translateY(-5px);
    }
    .session-type-card.selected {
        border-color: var(--bs-primary);
        background-color: var(--bs-primary-bg-subtle);
    }
    .color-picker {
        width: 40px;
        height: 40px;
        padding: 0;
        border: none;
        border-radius: 50%;
        cursor: pointer;
    }
    .recurrence-days {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .day-checkbox {
        display: none;
    }
    .day-label {
        padding: 8px 16px;
        border-radius: 20px;
        background-color: var(--bs-gray-200);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .day-checkbox:checked + .day-label {
        background-color: var(--bs-primary);
        color: white;
    }
    .teacher-modal-context {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
        background: #f8fafc;
        margin-bottom: 16px;
    }
    .teacher-modal-context .context-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .teacher-modal-context .context-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        font-weight: 600;
    }
    .teacher-modal-context .context-value {
        font-weight: 600;
        color: #1e293b;
    }

    .form-input-moderne,
    .form-select-moderne,
    .form-textarea-moderne {
        width: 100%;
        padding: var(--space-sm);
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background: var(--surface);
        color: var(--text-primary);
    }

    .form-input-moderne:focus,
    .form-select-moderne:focus,
    .form-textarea-moderne:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .teacher-availability-panel {
        margin-top: 24px;
        padding: 1.5rem;
        background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }

    .teacher-availability-panel .teacher-availability-grid {
        background: #fff;
        border-radius: 12px;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);
    }

    .teacher-availability-panel .availability-cell.unavailable {
        cursor: pointer;
    }
    /* Allow au-select dropdown to escape the main-card overflow:hidden */
    #courseFields {
        overflow: visible;
    }
    #courseFields .main-card-header {
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }

    /* ═══════════════════════════════════════════════════════════════
       SCE (Seance Creation Edit) — premium namespace, monochrome bleu
       Pattern hero+cards inspire de planning-header (ph-*) + premium-redesign rule
       ═══════════════════════════════════════════════════════════════ */
    .sce-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 1.65rem 2rem 1.4rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .sce-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .sce-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
    .sce-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .sce-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .sce-hero p { color: rgba(255,255,255,.78); font-size: .88rem; margin: .25rem 0 0; display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; }
    .sce-hero-sep { opacity: .55; }
    .sce-hero-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        background: rgba(255,255,255,.16); color: #fff;
        border: 1px solid rgba(255,255,255,.22);
        padding: .22rem .55rem; border-radius: 6px;
        font-size: .68rem; font-weight: 700; letter-spacing: .4px; margin-left: .3rem;
    }
    .sce-hero-actions { display: flex; align-items: center; gap: .6rem; flex-shrink: 0; }
    .sce-btn {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .55rem 1rem; border-radius: 10px;
        font-size: .82rem; font-weight: 600; text-decoration: none;
        border: 1px solid transparent; transition: all .15s;
    }
    .sce-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
    .sce-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
    .sce-hero-kpis { display: flex; gap: .75rem; margin-top: 1.4rem; flex-wrap: wrap; }
    .sce-hero-kpi {
        flex: 1; min-width: 160px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px;
        padding: .85rem 1rem;
        display: flex; align-items: center; gap: .75rem;
    }
    .sce-hero-kpi-icon {
        width: 36px; height: 36px; border-radius: 9px;
        background: rgba(255,255,255,.14);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .9rem; flex-shrink: 0;
    }
    .sce-hero-kpi-value { font-size: 1.25rem; font-weight: 700; color: #fff; line-height: 1.1; }
    .sce-hero-kpi-label { font-size: .68rem; color: rgba(255,255,255,.7); margin-top: .12rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }

    /* Type seance LMD radio cards */
    .sce-type-seance { margin-top: .25rem; }
    .sce-type-radio-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .75rem; }
    .sce-type-radio {
        position: relative;
        display: flex; align-items: flex-start; gap: .65rem;
        padding: .85rem 1rem;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        cursor: pointer;
        text-align: left;
        transition: all .15s;
    }
    .sce-type-radio:hover { border-color: rgba(4,83,203,.4); background: rgba(4,83,203,.02); }
    .sce-type-radio.is-active {
        border-color: #0453cb;
        background: rgba(4,83,203,.04);
        box-shadow: 0 4px 16px rgba(4,83,203,.08);
    }
    .sce-type-radio-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .85rem; flex-shrink: 0;
    }
    .sce-type-radio--primary .sce-type-radio-icon { background: linear-gradient(135deg, #033a8e, #0453cb); }
    .sce-type-radio--accent  .sce-type-radio-icon { background: linear-gradient(135deg, #0453cb, #3b7ddb); }
    .sce-type-radio--muted   .sce-type-radio-icon { background: linear-gradient(135deg, #3b7ddb, #5e91de); }
    .sce-type-radio-body { flex: 1; min-width: 0; }
    .sce-type-radio-label { font-family: 'Courier New', monospace; font-size: .72rem; font-weight: 700; color: #0453cb; background: rgba(4,83,203,.08); padding: .12rem .4rem; border-radius: 4px; display: inline-block; margin-bottom: .25rem; }
    .sce-type-radio-name { font-size: .9rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
    .sce-type-radio-desc { font-size: .72rem; color: #64748b; margin-top: .2rem; }
    .sce-type-radio-check { position: absolute; top: .6rem; right: .65rem; color: #0453cb; font-size: 1rem; }

    /* Matiere select premium custom */
    .sce-form-label { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; font-size: .82rem; font-weight: 600; color: #1e293b; margin-bottom: .5rem; }
    .sce-form-label-chip {
        display: inline-flex; align-items: center; gap: .3rem;
        background: rgba(4,83,203,.08); color: #0453cb;
        border: 1px solid rgba(4,83,203,.2);
        padding: .15rem .5rem; border-radius: 5px;
        font-size: .65rem; font-weight: 700; letter-spacing: .3px;
    }
    .sce-select-wrap { position: relative; }
    .sce-select-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .85rem; pointer-events: none; z-index: 2; }
    .sce-select-caret { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .75rem; pointer-events: none; }
    .sce-matiere-select {
        width: 100%;
        padding: .75rem 2.5rem .75rem 2.65rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: .88rem;
        color: #1e293b;
        font-weight: 500;
        appearance: none;
        cursor: pointer;
        transition: all .15s;
    }
    .sce-matiere-select:hover { border-color: rgba(4,83,203,.35); }
    .sce-matiere-select:focus {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.12);
    }
    .sce-form-info {
        display: inline-flex; align-items: center; gap: .4rem;
        background: rgba(16,185,129,.08); color: #047857;
        border: 1px solid rgba(16,185,129,.25);
        padding: .35rem .65rem; border-radius: 6px;
        font-size: .76rem; font-weight: 500; margin-top: .55rem;
    }

    @media (max-width: 768px) {
        .sce-hero { padding: 1.25rem; }
        .sce-hero h1 { font-size: 1.2rem; }
        .sce-hero-kpis { gap: .5rem; }
        .sce-type-radio-group { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        @php
            $isClasseLmd = ($emploiTemps->classe->systeme_academique ?? '') === 'LMD';
        @endphp
        {{-- Hero premium namespace sce-* (Seance Creation Edit) --}}
        <div class="sce-hero">
            <div class="sce-hero-top">
                <div class="sce-hero-left">
                    <div class="sce-hero-icon"><i class="fas fa-plus-circle"></i></div>
                    <div>
                        <h1>Nouvelle séance de cours</h1>
                        <p>
                            {{ $emploiTemps->classe->name }}
                            @if($emploiTemps->classe->filiere)
                                <span class="sce-hero-sep">·</span> {{ $emploiTemps->classe->filiere->name }}
                            @endif
                            @if($emploiTemps->classe->niveau)
                                <span class="sce-hero-sep">·</span> {{ $emploiTemps->classe->niveau->name }}
                            @endif
                            @if($isClasseLmd)
                                <span class="sce-hero-badge"><i class="fas fa-university"></i>LMD</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="sce-hero-actions">
                    <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}" class="sce-btn sce-btn--glass">
                        <i class="fas fa-arrow-left"></i>Retour à l'emploi du temps
                    </a>
                </div>
            </div>
            @if(($planificationData['planifications_configurees'] ?? false))
                <div class="sce-hero-kpis">
                    <div class="sce-hero-kpi">
                        <div class="sce-hero-kpi-icon"><i class="fas fa-book-open"></i></div>
                        <div class="sce-hero-kpi-body">
                            <div class="sce-hero-kpi-value">{{ $matieres->count() }}</div>
                            <div class="sce-hero-kpi-label">{{ $isClasseLmd ? 'ECUE configurées' : 'Matières configurées' }}</div>
                        </div>
                    </div>
                    <div class="sce-hero-kpi">
                        <div class="sce-hero-kpi-icon"><i class="fas fa-clock"></i></div>
                        <div class="sce-hero-kpi-body">
                            <div class="sce-hero-kpi-value">{{ $planificationData['heures_totales'] }}h</div>
                            <div class="sce-hero-kpi-label">Volume horaire total</div>
                        </div>
                    </div>
                    <div class="sce-hero-kpi">
                        <div class="sce-hero-kpi-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="sce-hero-kpi-body">
                            <div class="sce-hero-kpi-value">{{ $planificationData['heures_restantes'] }}h</div>
                            <div class="sce-hero-kpi-label">Heures restantes</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-start border-danger border-4 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-exclamation-circle fs-4"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading">Erreur de validation</h5>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        @if(request()->boolean('embed'))
            <div class="alert alert-danger border-start border-danger border-4 mb-4" id="embedError" style="display: none;"></div>
        @endif
        <form action="{{ route('esbtp.seances-cours.store') }}" method="POST" id="sessionForm">
            @csrf
            <input type="hidden" name="emploi_temps_id" value="{{ $emploiTemps->id }}">
            <input type="hidden" name="embed" value="{{ request()->boolean('embed') ? 1 : 0 }}">

            <div class="form-sections">
                <!-- Section 1: Type de séance -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-clipboard-list"></i>
                            Type de séance
                        </div>
                        <div class="main-card-subtitle">Sélectionnez le type de séance à programmer</div>
                    </div>
                    <div class="main-card-body">
                        <div class="session-types-container">
                            @foreach($sessionTypes as $type => $label)
                            <div class="session-type-card" data-type="{{ $type }}" onclick="selectSessionType('{{ $type }}')">
                                <div class="session-type-icon">
                                    @if($type === 'course')
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    @elseif($type === 'homework')
                                        <i class="fas fa-clipboard-check"></i>
                                    @elseif($type === 'break')
                                        <i class="fas fa-coffee"></i>
                                    @else
                                        <i class="fas fa-utensils"></i>
                                    @endif
                                </div>
                                <div class="session-type-label">{{ $label }}</div>
                            </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="type" id="sessionType" required>
                    </div>
                </div>

                <!-- Section 2: Informations de base -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-calendar-alt"></i>
                            Informations temporelles
                        </div>
                        <div class="main-card-subtitle">Jour, horaires et récurrence</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="jour" class="form-label">Jour <span class="text-danger">*</span></label>
                                <select name="jour" id="jour" class="form-select @error('jour') error @enderror" required>
                                    <option value="">Sélectionner un jour</option>
                                    @foreach($joursSemaine as $value => $label)
                                        <option value="{{ $value }}" {{ old('jour', $request->jour) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('jour')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="heure_debut" class="form-label">Heure de début <span class="text-danger">*</span></label>
                                @if(request()->boolean('embed'))
                                    <div class="d-flex align-items-center gap-2">
                                        <select id="heure_debut_h" class="form-input">
                                            @for($h = 7; $h <= 18; $h++)
                                                @php $hourValue = str_pad($h, 2, '0', STR_PAD_LEFT); @endphp
                                                <option value="{{ $hourValue }}">{{ $hourValue }}</option>
                                            @endfor
                                        </select>
                                        <span class="text-muted">:</span>
                                        <select id="heure_debut_m" class="form-input">
                                            @foreach(['00', '15', '30', '45'] as $minute)
                                                <option value="{{ $minute }}">{{ $minute }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <input type="hidden" class="@error('heure_debut') error @enderror"
                                           id="heure_debut" name="heure_debut"
                                           value="{{ old('heure_debut', $request->heure_debut) }}" required>
                                @else
                                    <input type="time" class="form-input @error('heure_debut') error @enderror"
                                           id="heure_debut" name="heure_debut"
                                           value="{{ old('heure_debut', $request->heure_debut) }}" required>
                                @endif
                                @error('heure_debut')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="heure_fin" class="form-label">Heure de fin <span class="text-danger">*</span></label>
                                @if(request()->boolean('embed'))
                                    <div class="d-flex align-items-center gap-2">
                                        <select id="heure_fin_h" class="form-input">
                                            @for($h = 7; $h <= 18; $h++)
                                                @php $hourValue = str_pad($h, 2, '0', STR_PAD_LEFT); @endphp
                                                <option value="{{ $hourValue }}">{{ $hourValue }}</option>
                                            @endfor
                                        </select>
                                        <span class="text-muted">:</span>
                                        <select id="heure_fin_m" class="form-input">
                                            @foreach(['00', '15', '30', '45'] as $minute)
                                                <option value="{{ $minute }}">{{ $minute }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <input type="hidden" class="@error('heure_fin') error @enderror"
                                           id="heure_fin" name="heure_fin"
                                           value="{{ old('heure_fin') }}" required>
                                @else
                                    <input type="time" class="form-input @error('heure_fin') error @enderror"
                                           id="heure_fin" name="heure_fin"
                                           value="{{ old('heure_fin') }}" required>
                                @endif
                                @error('heure_fin')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>

                        <div class="form-options">
                            <div class="form-check-custom">
                                <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring"
                                    {{ old('is_recurring') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_recurring">
                                    <i class="fas fa-repeat me-2"></i>Séance récurrente
                                </label>
                            </div>
                        </div>

                        <!-- Recurrence Days -->
                        <div id="recurrenceDays" class="recurrence-section" style="display: none;">
                            <label class="form-label">Jours de récurrence</label>
                            <div class="days-selector">
                                @foreach($joursSemaine as $value => $label)
                                    <div class="day-option">
                                        <input type="checkbox" class="day-checkbox" name="recurrence_days[]"
                                            id="day_{{ $value }}" value="{{ $value }}"
                                            {{ in_array($value, old('recurrence_days', [])) ? 'checked' : '' }}>
                                        <label class="day-label" for="day_{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Matières et Enseignants -->
                <div class="main-card" id="courseFields" style="display: none;">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-graduation-cap"></i>
                            Matière et Enseignant
                        </div>
                        <div class="main-card-subtitle">Configuration pédagogique de la séance</div>
                    </div>
                    <div class="main-card-body">
                        @if($planificationData['planifications_configurees'])
                            <!-- Contexte de la classe -->
                            <div class="context-card">
                                <div class="context-header">
                                    <i class="fas fa-school"></i>
                                    <span>{{ $emploiTemps->classe->name }}</span>
                                </div>
                                <div class="context-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Filière</span>
                                        <span class="stat-value">{{ $emploiTemps->classe->filiere->name }}</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Niveau</span>
                                        <span class="stat-value">{{ $emploiTemps->classe->niveau->name }}</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Matières</span>
                                        <span class="stat-value">{{ $matieres->count() }} configurées</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Volume</span>
                                        <span class="stat-value">{{ $planificationData['heures_totales'] }}h totales</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Type seance LMD : radio cards premium CM/TD/TP (LMD uniquement) --}}
                            @if(in_array($emploiTemps->classe->niveau->type ?? '', ['Licence', 'Master', 'Doctorat', 'Bachelor']))
                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                    <label class="sce-form-label">
                                        Type de séance <span class="text-danger">*</span>
                                        <span class="sce-form-label-chip"><i class="fas fa-university"></i>LMD — UEMOA</span>
                                    </label>
                                    @include('esbtp.seances-cours.partials._form_type_seance_lmd')
                                </div>
                            @endif

                            <div class="form-grid">
                                {{-- Picker matiere (LMD enrichi vs BTS flat) --}}
                                @include('esbtp.seances-cours.partials._form_matiere')

                                <div class="form-group" id="teacherFieldGroup">
                                    <label for="teacher_id" class="form-label">Enseignant assigné <span class="text-danger">*</span></label>
                                    <select name="teacher_id" id="teacher_id" class="form-select @error('teacher_id') error @enderror" onchange="showTeacherAvailability()" required>
                                        <option value="">Sélectionner d'abord une matière</option>
                                    </select>
                                    <div class="teacher-create-actions" id="teacherCreateActions" style="display: none;">
                                        <div class="alert alert-info mt-2 mb-0" id="teacherEmptyState" style="display: none;">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="fas fa-info-circle mt-1"></i>
                                                <div>
                                                    <strong>Ajouter un nouvel enseignant à cette matière</strong>
                                                    <div class="small text-muted">Le professeur sera automatiquement lié au planning général.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2 mt-2 flex-wrap">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="openTeacherModalBtn">
                                                <i class="fas fa-user-plus me-1"></i>Créer un enseignant
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="openManageTeachersBtn">
                                                <i class="fas fa-cogs me-1"></i>Gérer les enseignants
                                            </button>
                                        </div>
                                    </div>
                                    <div id="teacher-info" class="form-info" style="display: none;">
                                        <i class="fas fa-check-circle"></i>
                                        <span id="teacher-assignment-text"></span>
                                    </div>
                                    @error('teacher_id')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="salle" class="form-label">Salle</label>
                                    <input type="text" class="form-input @error('salle') error @enderror"
                                           id="salle" name="salle" value="{{ old('salle') }}"
                                           placeholder="Ex: Salle A101">
                                    @error('salle')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Grille de disponibilité de l'enseignant sélectionné -->
                            <div id="teacher-availability" class="availability-section" style="display: none;">
                                <div class="availability-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-calendar-check"></i>
                                        <span>Disponibilité de <span id="selected-teacher-name">l'enseignant</span></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <button type="button" id="btn-refresh-availability"
                                                onclick="forceRefreshAvailability()"
                                                title="Rafraîchir la disponibilité"
                                                style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                                            <i class="fas fa-sync-alt" id="refresh-icon"></i>
                                            <span class="d-none d-sm-inline">Rafraîchir</span>
                                        </button>
                                        <a id="btn-edit-teacher-availability"
                                           href="#"
                                           target="_blank"
                                           style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border-radius: 8px; font-size: 0.8rem; font-weight: 600; text-decoration: none; box-shadow: 0 2px 4px rgba(37,99,235,0.3);">
                                            <i class="fas fa-edit"></i> Modifier
                                        </a>
                                    </div>
                                </div>

                                <!-- Légende des couleurs -->
                                <div class="availability-legend mb-3">
                                    <h6 class="legend-title"><i class="fas fa-palette me-2"></i>Légende :</h6>
                                    <div class="legend-items">
                                        <div class="legend-item">
                                            <div class="legend-color preferred"><i class="fas fa-star"></i></div>
                                            <span>Préféré</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color available"><i class="fas fa-check"></i></div>
                                            <span>Disponible</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color unavailable"><i class="fas fa-ban"></i></div>
                                            <span>Non disponible</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color occupied"><i class="fas fa-lock"></i></div>
                                            <span>Occupé (autre séance)</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color selected-time"><i class="fas fa-bullseye"></i></div>
                                            <span>Créneaux sélectionnés</span>
                                        </div>
                                    </div>
                                    <div class="availability-hint">
                                        <i class="fas fa-mouse-pointer"></i>
                                        Cliquez puis glissez sur la grille pour définir un créneau.
                                    </div>
                                </div>

                                <div class="availability-grid-container">
                                    <div id="availability-inline-error" class="availability-inline-error" style="display: none;"></div>
                                    <div id="availability-grid" class="teacher-availability-grid"></div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="fas fa-exclamation-triangle fs-4"></i>
                                    </div>
                                    <div>
                                        <h6>Configuration requise</h6>
                                        <p class="mb-2">{{ $planificationData['message_configuration'] }}</p>
                                        @if($planificationData['lien_configuration'])
                                            <a href="{{ $planificationData['lien_configuration'] }}"
                                               class="btn-acasi primary small"
                                               @if(request()->boolean('embed')) target="_blank" rel="noopener noreferrer" @endif>
                                                <i class="fas fa-cog"></i>Configurer maintenant
                                            </a>
                                            @if(request()->boolean('embed'))
                                                <div class="text-muted small mt-2">S'ouvre dans un nouvel onglet. Revenez ici après configuration.</div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Section 4: Informations complémentaires -->
                <div class="main-card" id="homeworkFields" style="display: none;">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-clipboard-check"></i>
                            Informations sur le devoir
                        </div>
                        <div class="main-card-subtitle">Détails spécifiques au devoir</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="homework_description" class="form-label">Description du devoir</label>
                                <textarea class="form-input @error('homework_description') error @enderror"
                                          id="homework_description" name="homework_description" rows="4"
                                          placeholder="Décrivez le devoir à donner...">{{ old('homework_description') }}</textarea>
                                @error('homework_description')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="homework_due_date" class="form-label">Date de remise</label>
                                <input type="date" class="form-input @error('homework_due_date') error @enderror"
                                       id="homework_due_date" name="homework_due_date"
                                       value="{{ old('homework_due_date') }}">
                                @error('homework_due_date')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="evaluation-slot-card" id="homeworkTimingInfo" style="display: none;">
                            <div class="slot-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="slot-content">
                                <div class="slot-title">Créneau de l'évaluation générée</div>
                                <div class="slot-times">
                                    <span class="slot-label">Début</span>
                                    <span class="slot-time" id="homeworkStartTime">--:--</span>
                                    <span class="slot-separator">•</span>
                                    <span class="slot-label">Fin</span>
                                    <span class="slot-time" id="homeworkEndTime">--:--</span>
                                </div>
                                <div class="slot-duration">
                                    <i class="fas fa-hourglass-half"></i>
                                    Durée calculée : <span id="homeworkDuration">0</span> minutes
                                    <small>(utilisée pour l'évaluation automatique)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions du formulaire -->
                <div class="form-actions">
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save"></i>
                        Enregistrer la séance
                    </button>
                    <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}" class="btn-acasi secondary">
                        <i class="fas fa-times"></i>
                        Annuler
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="teacherCreateModal" tabindex="-1" aria-labelledby="teacherCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="teacherCreateModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Créer un enseignant
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="teacherCreateForm" action="{{ route('esbtp.enseignants.quick-create') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="planification_id" id="teacher_planification_id" value="">
                <input type="hidden" name="matiere_id" id="teacher_matiere_id" value="">
                <input type="hidden" name="emploi_temps_id" id="teacher_emploi_temps_id" value="{{ $emploiTemps->id }}">
                <div class="modal-body">
                    <div class="alert alert-danger" id="teacherCreateErrors" style="display: none;"></div>

                    <div class="teacher-modal-context">
                        <div class="context-item">
                            <span class="context-label">Classe</span>
                            <span class="context-value">{{ $emploiTemps->classe->name }}</span>
                        </div>
                        <div class="context-item">
                            <span class="context-label">Matière</span>
                            <span class="context-value" id="teacher_modal_matiere">--</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="teacher_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="teacher_email" class="form-control">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="phone" id="teacher_phone" class="form-control">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Titre académique</label>
                                <select name="titre_academique" id="teacher_titre" class="form-select">
                                    <option value="">Sélectionner</option>
                                    @foreach($titres_academiques as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Grade académique</label>
                                <select name="grade_academique" id="teacher_grade" class="form-select">
                                    <option value="">Sélectionner</option>
                                    @foreach($grades_academiques as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="form-label">Spécialisation <span class="text-danger">*</span></label>
                                <input type="text" name="specialization" id="teacher_specialization" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Régime <span class="text-danger">*</span></label>
                                <select name="regime" id="teacher_regime" class="form-select" required>
                                    @foreach(\App\Enums\TeacherRegime::cases() as $regime)
                                        <option value="{{ $regime->value }}" {{ $regime === \App\Enums\TeacherRegime::Vacataire ? 'selected' : '' }}>
                                            {{ $regime->label() }} — {{ $regime->description() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Date de début d'activité</label>
                                <input type="date" name="date_debut_activite" id="teacher_hire_date" class="form-control" value="{{ now()->toDateString() }}">
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Charge horaire max/semaine</label>
                                <input type="number" name="charge_horaire_max_semaine" id="teacher_weekly_hours" class="form-control" min="1" max="60" value="40">
                            </div>
                        </div>
                    </div>

                    <div class="teacher-availability-panel">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h6 class="mb-1"><i class="fas fa-calendar-check me-2"></i>Disponibilité de l'enseignant</h6>
                                <div class="text-muted small">Cliquez sur les cases pour définir les créneaux.</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="availabilityAllAvailable">Tout disponible</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="availabilityAllUnavailable">Tout indisponible</button>
                            </div>
                        </div>
                        <div class="teacher-availability-grid" id="teacherAvailabilityGrid"></div>
                        <div class="availability-legend">
                            <h6 class="legend-title"><i class="fas fa-palette me-2"></i>Légende :</h6>
                            <div class="legend-items">
                                <div class="legend-item">
                                    <div class="legend-color preferred"><i class="fas fa-star"></i></div>
                                    <span>Préféré</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color available"><i class="fas fa-check"></i></div>
                                    <span>Disponible</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color unavailable"><i class="fas fa-ban"></i></div>
                                    <span>Indisponible</span>
                                </div>
                            </div>
                        </div>
                        <div id="teacherAvailabilityInputs"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="teacherCreateSubmit">
                        <i class="fas fa-save me-1"></i>Créer et assigner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Gérer les enseignants -->
<div class="modal fade" id="manageTeachersModal" tabindex="-1" aria-labelledby="manageTeachersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content mgt-modal">
            <div class="modal-header mgt-header">
                <div class="mgt-header-content">
                    <div class="mgt-header-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mgt-title" id="manageTeachersModalLabel">Gérer les enseignants</h5>
                        <p class="mgt-subtitle" id="mgtMatiereLabel">—</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body mgt-body">
                <!-- Loading spinner -->
                <div id="mgtLoading" class="mgt-loading">
                    <div class="mgt-spinner"></div>
                    <span>Chargement des enseignants...</span>
                </div>

                <!-- Content -->
                <div id="mgtContent" style="display: none;">
                    <!-- Section: Enseignants associés -->
                    <div class="mgt-section">
                        <div class="mgt-section-header">
                            <i class="fas fa-link"></i>
                            <span>Enseignants associés</span>
                            <span class="mgt-count" id="mgtLinkedCount">0</span>
                        </div>
                        <div id="mgtLinkedList" class="mgt-linked-list">
                            <!-- Populated dynamically -->
                        </div>
                        <div id="mgtLinkedEmpty" class="mgt-empty-state" style="display: none;">
                            <i class="fas fa-user-slash"></i>
                            <span>Aucun enseignant associé à cette matière</span>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="mgt-divider">
                        <span>Ajouter un enseignant existant</span>
                    </div>

                    <!-- Section: Ajouter -->
                    <div class="mgt-section">
                        <div class="mgt-add-card">
                            <div class="mgt-add-select-wrap">
                                <i class="fas fa-search mgt-add-search-icon"></i>
                                <select id="mgtTeacherSelect" class="form-select mgt-select">
                                    <option value="">Rechercher un enseignant...</option>
                                </select>
                            </div>
                            <button type="button" class="mgt-btn-associate" id="mgtAssociateBtn" disabled>
                                <i class="fas fa-plus-circle me-1"></i>
                                <span>Associer</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dialog d'erreur pour dissociation bloquée -->
<div class="modal fade" id="mgtErrorDialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content mgt-error-dialog">
            <div class="mgt-error-icon-wrap">
                <div class="mgt-error-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
            <div class="mgt-error-body">
                <h6 class="mgt-error-title">Action impossible</h6>
                <p class="mgt-error-message" id="mgtErrorMessage">—</p>
            </div>
            <div class="mgt-error-footer">
                <button type="button" class="btn mgt-btn-understand" data-bs-dismiss="modal">
                    <i class="fas fa-check me-1"></i>Compris
                </button>
            </div>
        </div>
    </div>
</div>

<div id="seance-data"
     data-default-colors='@json($defaultColors)'
     data-availability='@json($availabilityData ?? [])'
     data-teachers='@json($teachers->keyBy("id"))'
     data-embed="{{ request()->boolean('embed') ? 1 : 0 }}"
     style="display: none;"></div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script>
const currentTeacherId = "{{ old('teacher_id') }}";
const seanceDataElement = document.getElementById('seance-data');
const isEmbedded = seanceDataElement ? seanceDataElement.dataset.embed === '1' : false;
const teacherQuickCreateUrl = "{{ route('esbtp.enseignants.quick-create') }}";
const manageTeachersBaseUrl = "{{ url('/esbtp/planning-general/planifications') }}";
const seanceData = seanceDataElement
    ? {
        defaultColors: JSON.parse(seanceDataElement.dataset.defaultColors || '{}'),
        availability: JSON.parse(seanceDataElement.dataset.availability || '{}'),
        teachers: JSON.parse(seanceDataElement.dataset.teachers || '{}')
    }
    : { defaultColors: {}, availability: {}, teachers: {} };

const debugLog = typeof window !== 'undefined' && typeof window.debugLog === 'function'
    ? window.debugLog
    : () => {};

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5'
    });

    // Initialize Flatpickr for time inputs (skip in embed mode)
    if (!isEmbedded) {
        flatpickr("input[type=time]", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            locale: "fr"
        });
    }

    // Initialize Flatpickr for date inputs (skip in embed mode)
    if (!isEmbedded) {
        flatpickr("input[type=date]:not(#teacher_hire_date)", {
            locale: "fr",
            minDate: "today"
        });
    }




    if (isEmbedded) {
        if (!document.getElementById('sessionType').value) {
            selectSessionType('course');
        }

        const ensureEndAfterStart = () => {
            const startInput = document.getElementById('heure_debut');
            const endInput = document.getElementById('heure_fin');
            if (!startInput || !endInput) {
                return;
            }
            const start = startInput.value || '';
            const end = endInput.value || '';
            if (!start.includes(':') || !end.includes(':')) {
                return;
            }
            const [sh, sm] = start.split(':').map(Number);
            const [eh, em] = end.split(':').map(Number);
            if (Number.isNaN(sh) || Number.isNaN(sm) || Number.isNaN(eh) || Number.isNaN(em)) {
                return;
            }
            const startMinutes = sh * 60 + sm;
            const endMinutes = eh * 60 + em;
            if (endMinutes < startMinutes) {
                endInput.value = start;
                if (typeof setEmbedTimeSelect === 'function') {
                    setEmbedTimeSelect('heure_fin', start);
                }
            }
        };

        const initTimeSelects = (prefix, initialValue) => {
            const hourSelect = document.getElementById(`${prefix}_h`);
            const minuteSelect = document.getElementById(`${prefix}_m`);
            const hiddenInput = document.getElementById(prefix);

            if (!hourSelect || !minuteSelect || !hiddenInput) {
                return;
            }

            const value = initialValue || hiddenInput.value || '';
            if (value.includes(':')) {
                const [hour, minute] = value.split(':');
                if (hour) hourSelect.value = hour.padStart(2, '0');
                if (minute) minuteSelect.value = minute.padStart(2, '0');
            }

            const sync = () => {
                hiddenInput.value = `${hourSelect.value}:${minuteSelect.value}`;
                ensureEndAfterStart();
                if (typeof updateSelectedTimeInGrid === 'function') {
                    updateSelectedTimeInGrid();
                }
                if (typeof updateHomeworkTimingInfo === 'function') {
                    updateHomeworkTimingInfo();
                }
            };

            hourSelect.addEventListener('change', sync);
            minuteSelect.addEventListener('change', sync);
            sync();
        };

        initTimeSelects('heure_debut', "{{ old('heure_debut', $request->heure_debut) }}");
        initTimeSelects('heure_fin', "{{ old('heure_fin') }}");
    }

    const teacherSelect = document.getElementById('teacher_id');
    if (teacherSelect) {
        teacherSelect.addEventListener('change', showTeacherAvailability);
        if (window.jQuery && window.$) {
            $('#teacher_id').on('change', showTeacherAvailability);
        }
    }

    let matiereSelect = document.getElementById('matiere_id');
    if (matiereSelect) {
        matiereSelect.addEventListener('change', function () {
            updateTeachersForSubject();
            showTeacherAvailability();
            syncTeacherModalContext();
        });
    }

    if (isEmbedded) {
        setTimeout(() => {
            showTeacherAvailability();
        }, 150);
    }

    const errorFields = document.querySelectorAll('.form-error');
    if (errorFields.length > 0) {
        const courseFields = document.getElementById('courseFields');
        const hasCourseError = Array.from(errorFields).some((el) => {
            const field = el.closest('.form-group');
            const input = field ? field.querySelector('[name="matiere_id"], [name="teacher_id"]') : null;
            return !!input;
        });

        if (hasCourseError && courseFields) {
            courseFields.classList.add('error-highlight');
            courseFields.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Handle recurring checkbox
    document.getElementById('is_recurring').addEventListener('change', function() {
        document.getElementById('recurrenceDays').style.display = this.checked ? 'block' : 'none';
    });

    // Écouter les changements d'horaires et de jour pour mettre à jour la grille
    const heureDebutInput = document.getElementById('heure_debut');
    const heureFinInput = document.getElementById('heure_fin');

    heureDebutInput.addEventListener('change', () => {
        updateSelectedTimeInGrid();
        updateHomeworkTimingInfo();
    });
    heureFinInput.addEventListener('change', () => {
        updateSelectedTimeInGrid();
        updateHomeworkTimingInfo();
    });
    document.getElementById('jour').addEventListener('change', updateSelectedTimeInGrid);

    const initialType = "{{ old('type', $request->type ?? 'course') }}";
    if (initialType) {
        selectSessionType(initialType);
    }
    updateHomeworkTimingInfo();

    // Pré-remplir les enseignants si une matière est déjà sélectionnée
    if (matiereSelect && matiereSelect.value) {
        updateTeachersForSubject();
        if (currentTeacherId && document.getElementById('sessionType').value === 'course') {
            const teacherSelect = document.getElementById('teacher_id');
            teacherSelect.value = currentTeacherId;
            showTeacherAvailability();
        }
    }

    initTeacherAvailabilityGrid();
    const openTeacherModalBtn = document.getElementById('openTeacherModalBtn');
    if (openTeacherModalBtn) {
        openTeacherModalBtn.addEventListener('click', openTeacherCreateModal);
    }

    const teacherCreateForm = document.getElementById('teacherCreateForm');
    if (teacherCreateForm) {
        teacherCreateForm.addEventListener('submit', handleTeacherCreateSubmit);
    }

    const openManageTeachersBtn = document.getElementById('openManageTeachersBtn');
    if (openManageTeachersBtn) {
        openManageTeachersBtn.addEventListener('click', openManageTeachersModal);
    }

    const mgtAssociateBtn = document.getElementById('mgtAssociateBtn');
    if (mgtAssociateBtn) {
        mgtAssociateBtn.addEventListener('click', handleAssociateTeacher);
    }

    const mgtTeacherSelect = document.getElementById('mgtTeacherSelect');
    if (mgtTeacherSelect) {
        mgtTeacherSelect.addEventListener('change', function () {
            const btn = document.getElementById('mgtAssociateBtn');
            if (btn) btn.disabled = !this.value;
        });
    }

    const availabilityAllAvailableBtn = document.getElementById('availabilityAllAvailable');
    if (availabilityAllAvailableBtn) {
        availabilityAllAvailableBtn.addEventListener('click', () => setAllTeacherAvailability('available'));
    }
    const availabilityAllUnavailableBtn = document.getElementById('availabilityAllUnavailable');
    if (availabilityAllUnavailableBtn) {
        availabilityAllUnavailableBtn.addEventListener('click', () => setAllTeacherAvailability('unavailable'));
    }
});

function selectSessionType(type) {
    // Update hidden input
    document.getElementById('sessionType').value = type;

    // Update UI
    document.querySelectorAll('.session-type-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`[data-type="${type}"]`).classList.add('selected');

    // Show/hide relevant fields
    document.getElementById('courseFields').style.display =
        (type === 'course' || type === 'homework') ? 'block' : 'none';
    document.getElementById('homeworkFields').style.display =
        (type === 'homework') ? 'block' : 'none';

    // Update required fields
    const teacherField = document.getElementById('teacher_id');
    const teacherGroup = document.getElementById('teacherFieldGroup');
    const teacherInfo = document.getElementById('teacher-info');
    const teacherAvailability = document.getElementById('teacher-availability');
    const matiereField = document.getElementById('matiere_id');
    const salleField = document.getElementById('salle');
    const homeworkDescField = document.getElementById('homework_description');
    const homeworkDueDateField = document.getElementById('homework_due_date');

    const requiresTeacher = type === 'course';
    const requiresSalle = (type === 'course' || type === 'homework');

    if (teacherField) {
        teacherField.required = requiresTeacher;
        teacherField.disabled = !requiresTeacher;
        if (!requiresTeacher) {
            if (typeof $ !== 'undefined') {
                $('#teacher_id').val(null).trigger('change.select2');
                $('#teacher_id').prop('disabled', true).trigger('change.select2');
            } else {
                teacherField.value = '';
            }
            if (teacherInfo) {
                teacherInfo.style.display = 'none';
            }
            if (teacherAvailability) {
                teacherAvailability.style.display = 'none';
            }
        } else if (typeof $ !== 'undefined') {
            $('#teacher_id').prop('disabled', false).trigger('change.select2');
        }
    }

    if (teacherGroup) {
        teacherGroup.style.display = requiresTeacher ? 'block' : 'none';
    }

    matiereField.required = type === 'course' || type === 'homework';
    salleField.required = requiresSalle;

    if (type === 'homework') {
        homeworkDescField.required = true;
        homeworkDueDateField.required = true;
    } else {
        homeworkDescField.required = false;
        homeworkDueDateField.required = false;
    }

    if (typeof updateTeachersForSubject === 'function') {
        updateTeachersForSubject();
    }

    updateHomeworkTimingInfo();
}

// Fonction pour mettre à jour les créneaux sélectionnés dans la grille
function updateSelectedTimeInGrid() {
    const jourSelect = document.getElementById('jour');
    const heureDebut = document.getElementById('heure_debut');
    const heureFin = document.getElementById('heure_fin');

    // Nettoyer les anciennes sélections
    document.querySelectorAll('.availability-cell.selected-time').forEach(cell => {
        cell.classList.remove('selected-time');
    });
    document.querySelectorAll('.availability-cell.selecting').forEach(cell => {
        cell.classList.remove('selecting');
    });
    clearAvailabilityErrors();

    // Si tous les champs sont remplis, surligner les créneaux
    if (jourSelect.value && heureDebut.value && heureFin.value) {
        const selectedDay = parseInt(jourSelect.value);
        const startHour = parseInt(heureDebut.value.split(':')[0]);
        const endHour = parseInt(heureFin.value.split(':')[0]);
        const dayColumnIndex = getDayColumnIndex(selectedDay);
        if (dayColumnIndex !== undefined) {
            // Parcourir chaque ligne d'heure pour surligner les cellules correspondantes
            for (let hour = startHour; hour < endHour; hour++) {
                if (hour >= 8 && hour < 18) {
                    const rowIndex = hour - 8; // 8h = row 0
                    const cell = getAvailabilityCell(selectedDay, hour);
                    if (cell) {
                        cell.classList.add('selected-time');
                    }
                }
            }
        }
    }

    updateHomeworkTimingInfo();
}

function updateHomeworkTimingInfo() {
    const infoBox = document.getElementById('homeworkTimingInfo');
    if (!infoBox) {
        return;
    }

    const currentType = document.getElementById('sessionType').value;
    if (currentType !== 'homework') {
        infoBox.style.display = 'none';
        return;
    }

    const heureDebut = document.getElementById('heure_debut').value;
    const heureFin = document.getElementById('heure_fin').value;

    if (!heureDebut || !heureFin) {
        infoBox.style.display = 'none';
        return;
    }

    const [startHour, startMinute] = heureDebut.split(':').map(Number);
    const [endHour, endMinute] = heureFin.split(':').map(Number);

    let startTotal = startHour * 60 + startMinute;
    let endTotal = endHour * 60 + endMinute;
    if (endTotal <= startTotal) {
        endTotal += 24 * 60;
    }

    const duration = endTotal - startTotal;
    document.getElementById('homeworkStartTime').textContent = heureDebut;
    document.getElementById('homeworkEndTime').textContent = heureFin;
    document.getElementById('homeworkDuration').textContent = duration;
    infoBox.style.display = 'flex';
}

function showAvailabilityToast(message, type = 'danger') {
    const containerId = 'availability-toast-container';
    let container = document.getElementById(containerId);
    if (!container) {
        container = document.createElement('div');
        container.id = containerId;
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1080';
        document.body.appendChild(container);
    }

    const toastId = `availability-toast-${Date.now()}`;
    const safeMessage = message.replace(/\n/g, '<br>');
    container.insertAdjacentHTML('beforeend', `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${safeMessage}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 6000 });
    toast.show();
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

function showFormError(message) {
    showAvailabilityToast(message, 'danger');
    debugAlert(message);
}

function setAvailabilityErrorMessage(message) {
    const inlineError = document.getElementById('availability-inline-error');
    if (!inlineError) {
        return;
    }

    if (!message) {
        inlineError.textContent = '';
        inlineError.style.display = 'none';
        return;
    }

    inlineError.textContent = message.replace(/\n/g, ' ');
    inlineError.style.display = 'flex';
}

function getDayColumnIndex(dayNumber) {
    const dayMapping = {
        1: 0,
        2: 1,
        3: 2,
        4: 3,
        5: 4,
        6: 5
    };
    return dayMapping[dayNumber];
}

function getAvailabilityCell(dayNumber, hour) {
    const dayColumnIndex = getDayColumnIndex(dayNumber);
    if (dayColumnIndex === undefined) {
        return null;
    }

    const rowIndex = hour - 8;
    const timeRows = document.querySelectorAll('.availability-time-row');
    if (!timeRows[rowIndex]) {
        return null;
    }

    const cells = timeRows[rowIndex].querySelectorAll('.availability-cell');
    return cells[dayColumnIndex] || null;
}

function clearAvailabilityErrors() {
    document.querySelectorAll('.availability-cell.error').forEach(cell => {
        cell.classList.remove('error');
    });
    setAvailabilityErrorMessage('');
}

function markAvailabilityError(dayNumber, hour, message) {
    const cell = getAvailabilityCell(dayNumber, hour);
    if (!cell) {
        return;
    }

    if (message) {
        cell.dataset.error = message;
        cell.setAttribute('title', message);
    }
    cell.classList.add('error');
    setTimeout(() => {
        cell.classList.remove('error');
        if (message) {
            delete cell.dataset.error;
            cell.removeAttribute('title');
        }
    }, 3500);
}

function markAvailabilityErrorRange(dayNumber, startHour, endHour, message) {
    for (let hour = startHour; hour < endHour; hour++) {
        markAvailabilityError(dayNumber, hour, message);
    }
}

let availabilitySelection = {
    active: false,
    day: null,
    startHour: null,
    endHour: null,
};

function bindAvailabilityGridHandlers() {
    const grid = document.getElementById('availability-grid');
    if (!grid || grid.dataset.bound === 'true') {
        return;
    }

    grid.dataset.bound = 'true';
    grid.addEventListener('mousedown', handleAvailabilityMouseDown);
    grid.addEventListener('mouseover', handleAvailabilityMouseOver);
    document.addEventListener('mouseup', handleAvailabilityMouseUp);
}

function handleAvailabilityMouseDown(event) {
    const cell = event.target.closest('.availability-cell');
    if (!cell) {
        return;
    }

    const status = cell.dataset.status;
    const day = parseInt(cell.dataset.day, 10);
    const hour = parseInt(cell.dataset.hour, 10);
    if (Number.isNaN(day) || Number.isNaN(hour)) {
        return;
    }

    clearAvailabilityErrors();

    if (status === 'unavailable' || status === 'occupied') {
        const errorMessage = status === 'occupied'
            ? 'Ce créneau est déjà occupé. Choisissez un autre horaire.'
            : 'Ce créneau est indisponible. Choisissez un autre horaire.';
        markAvailabilityError(day, hour, errorMessage);
        setAvailabilityErrorMessage(errorMessage);
        return;
    }

    availabilitySelection = {
        active: true,
        day,
        startHour: hour,
        endHour: hour,
    };

    updateAvailabilitySelectionPreview();
    event.preventDefault();
}

function handleAvailabilityMouseOver(event) {
    if (!availabilitySelection.active) {
        return;
    }

    const cell = event.target.closest('.availability-cell');
    if (!cell) {
        return;
    }

    const day = parseInt(cell.dataset.day, 10);
    const hour = parseInt(cell.dataset.hour, 10);
    if (Number.isNaN(day) || Number.isNaN(hour) || day !== availabilitySelection.day) {
        return;
    }

    availabilitySelection.endHour = hour;
    updateAvailabilitySelectionPreview();
}

function handleAvailabilityMouseUp() {
    if (!availabilitySelection.active) {
        return;
    }

    const selectedDay = availabilitySelection.day;
    const startHour = Math.min(availabilitySelection.startHour, availabilitySelection.endHour);
    const endHour = Math.max(availabilitySelection.startHour, availabilitySelection.endHour) + 1;

    availabilitySelection = {
        active: false,
        day: null,
        startHour: null,
        endHour: null,
    };

    finalizeAvailabilitySelection(selectedDay, startHour, endHour);
}

function updateAvailabilitySelectionPreview() {
    document.querySelectorAll('.availability-cell.selecting').forEach(cell => {
        cell.classList.remove('selecting');
    });

    if (!availabilitySelection.active) {
        return;
    }

    const startHour = Math.min(availabilitySelection.startHour, availabilitySelection.endHour);
    const endHour = Math.max(availabilitySelection.startHour, availabilitySelection.endHour) + 1;

    for (let hour = startHour; hour < endHour; hour++) {
        const cell = getAvailabilityCell(availabilitySelection.day, hour);
        if (cell) {
            cell.classList.add('selecting');
        }
    }
}

function finalizeAvailabilitySelection(day, startHour, endHour) {
    const invalidCells = [];
    for (let hour = startHour; hour < endHour; hour++) {
        const cell = getAvailabilityCell(day, hour);
        if (!cell) {
            continue;
        }

        const status = cell.dataset.status;
        if (status === 'unavailable' || status === 'occupied') {
            invalidCells.push({ hour, status });
        }
    }

    if (invalidCells.length > 0) {
        invalidCells.forEach(({ hour, status }) => {
            const errorMessage = status === 'occupied'
                ? 'Ce créneau est déjà occupé. Choisissez un autre horaire.'
                : 'Ce créneau est indisponible. Choisissez un autre horaire.';
            markAvailabilityError(day, hour, errorMessage);
            setAvailabilityErrorMessage(errorMessage);
        });
        return;
    }

    const jourSelect = document.getElementById('jour');
    const heureDebut = document.getElementById('heure_debut');
    const heureFin = document.getElementById('heure_fin');
    if (!jourSelect || !heureDebut || !heureFin) {
        return;
    }

    jourSelect.value = day.toString();
    heureDebut.value = `${startHour.toString().padStart(2, '0')}:00`;
    heureFin.value = `${endHour.toString().padStart(2, '0')}:00`;

    jourSelect.dispatchEvent(new Event('change'));
    heureDebut.dispatchEvent(new Event('change'));
    heureFin.dispatchEvent(new Event('change'));
    if (typeof setEmbedTimeSelect === 'function') {
        setEmbedTimeSelect('heure_debut', heureDebut.value);
        setEmbedTimeSelect('heure_fin', heureFin.value);
    }
    updateSelectedTimeInGrid();
}

// Form validation
document.getElementById('sessionForm').addEventListener('submit', function(e) {
    const type = document.getElementById('sessionType').value;
    if (!type) {
        e.preventDefault();
        showFormError('Veuillez sélectionner un type de séance');
        return;
    }

    const startTime = document.getElementById('heure_debut').value;
    const endTime = document.getElementById('heure_fin').value;
    if (startTime && endTime && startTime >= endTime) {
        e.preventDefault();
        showFormError('L\'heure de fin doit être postérieure à l\'heure de début');
        return;
    }

    // Validation de disponibilité de l'enseignant
    if (type === 'course' && !validateTeacherAvailability()) {
        e.preventDefault();
        return;
    }
});

function setEmbedTimeSelect(prefix, value) {
    const hourSelect = document.getElementById(`${prefix}_h`);
    const minuteSelect = document.getElementById(`${prefix}_m`);
    if (!hourSelect || !minuteSelect || !value || !value.includes(':')) {
        return;
    }
    const parts = value.split(':');
    hourSelect.value = parts[0].padStart(2, '0');
    minuteSelect.value = parts[1].padStart(2, '0');
}

document.getElementById('sessionForm').addEventListener('submit', async function(e) {
    if (!isEmbedded) {
        return;
    }
    if (e.defaultPrevented) {
        return;
    }
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    formData.set('embed', '1');

    const errorBox = document.getElementById('embedError');
    if (errorBox) {
        errorBox.style.display = 'none';
        errorBox.innerHTML = '';
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const payload = await response.json();

            if (!response.ok) {
                const messages = payload.errors || payload.message || 'Une erreur est survenue.';
                if (errorBox) {
                const list = Array.isArray(messages)
                    ? messages
                    : (typeof messages === 'object' ? Object.values(messages).flat() : [messages]);
                errorBox.innerHTML = `<strong>Erreur de validation</strong><ul class="mb-0 ps-3">${list.map((msg) => `<li>${msg}</li>`).join('')}</ul>`;
                    errorBox.style.display = 'block';
                } else {
                    alert('Erreur : ' + (payload.message || 'Validation échouée'));
                }

                if (payload.errors && (payload.errors.matiere_id || payload.errors.teacher_id)) {
                    const courseFields = document.getElementById('courseFields');
                    if (courseFields) {
                        courseFields.classList.add('error-highlight');
                        courseFields.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                return;
            }

        if (window.parent) {
            const emploiTempsId = payload.emploi_temps_id || formData.get('emploi_temps_id');
            window.parent.postMessage({
                type: 'seance-created',
                emploiTempsId: emploiTempsId,
                seanceId: payload.seance_id || null
            }, window.location.origin);
        }
    } catch (error) {
        if (errorBox) {
            errorBox.textContent = 'Une erreur est survenue lors de l\'enregistrement.';
            errorBox.style.display = 'block';
        }
    }
});

// Fonction pour valider la disponibilité de l'enseignant
function validateTeacherAvailability() {
    const teacherSelect = document.getElementById('teacher_id');
    const jourSelect = document.getElementById('jour');
    const heureDebut = document.getElementById('heure_debut');
    const heureFin = document.getElementById('heure_fin');
    const currentType = document.getElementById('sessionType').value;

    if (currentType !== 'course') {
        return true;
    }

    if (!teacherSelect.value || !jourSelect.value || !heureDebut.value || !heureFin.value) {
        return true; // Pas assez d'infos pour valider
    }

    const teacherId = teacherSelect.value;
    const selectedDay = parseInt(jourSelect.value);
    const startHour = parseInt(heureDebut.value.split(':')[0]);
    const endHour = parseInt(heureFin.value.split(':')[0]);

    const availabilityData = seanceData.availability;

    clearAvailabilityErrors();

    if (!availabilityData[teacherId]) {
        showFormError('Aucune disponibilité configurée pour cet enseignant.\n\nVeuillez configurer ses disponibilités avant de programmer cette séance.');
        return false;
    }

    // Mapping jour numérique vers clé jour
    const dayMapping = {
        1: 'monday',
        2: 'tuesday',
        3: 'wednesday',
        4: 'thursday',
        5: 'friday',
        6: 'saturday'
    };

    const dayKey = dayMapping[selectedDay];
    if (!dayKey || !availabilityData[teacherId][dayKey]) {
        const errorMessage = 'L\'enseignant n\'est pas disponible ce jour-là.\n\nVeuillez choisir un autre jour ou un autre enseignant.';
        markAvailabilityErrorRange(selectedDay, startHour, endHour, errorMessage);
        showFormError(errorMessage);
        setAvailabilityErrorMessage(errorMessage);
        return false;
    }

    // Vérifier chaque heure du créneau
    const teacherDayAvailability = availabilityData[teacherId][dayKey];
    for (let hour = startHour; hour < endHour; hour++) {
        const hourIndex = hour - 8; // 8h = index 0
        if (hourIndex >= 0 && hourIndex < teacherDayAvailability.length) {
            const status = teacherDayAvailability[hourIndex];
            const jourNoms = {1: 'lundi', 2: 'mardi', 3: 'mercredi', 4: 'jeudi', 5: 'vendredi', 6: 'samedi'};

            if (status === 'unavailable') {
                const errorMessage = `L'enseignant n'est pas disponible ${jourNoms[selectedDay]} à ${hour}:00.\n\nVeuillez ajuster les horaires ou choisir un autre enseignant.`;
                markAvailabilityError(selectedDay, hour, errorMessage);
                showFormError(errorMessage);
                setAvailabilityErrorMessage(errorMessage);
                return false;
            } else if (status === 'occupied') {
                const errorMessage = `L'enseignant a déjà une séance programmée ${jourNoms[selectedDay]} à ${hour}:00 dans un autre emploi du temps.\n\nVeuillez choisir un autre créneau.`;
                markAvailabilityError(selectedDay, hour, errorMessage);
                showFormError(errorMessage);
                setAvailabilityErrorMessage(errorMessage);
                return false;
            }
        }
    }

    return true; // Tout est OK
}


// Fonction pour mettre à jour les enseignants selon la matière sélectionnée
function updateTeachersForSubject() {
    const matiereSelect = document.getElementById('matiere_id');
    const teacherSelect = document.getElementById('teacher_id');
    const matiereInfo = document.getElementById('matiere-info');
    const heuresRestantesText = document.getElementById('heures-restantes-text');
    const teacherInfo = document.getElementById('teacher-info');
    const teacherAvailability = document.getElementById('teacher-availability');
    const teacherCreateActions = document.getElementById('teacherCreateActions');
    const teacherEmptyState = document.getElementById('teacherEmptyState');
    const currentType = document.getElementById('sessionType').value;
    const requiresTeacher = currentType === 'course';

    // Reset teacher select
    teacherSelect.innerHTML = requiresTeacher
        ? '<option value="">Sélectionner un enseignant</option>'
        : '<option value="">Aucun enseignant requis pour un devoir</option>';

    if (matiereSelect.value) {
        const selectedOption = matiereSelect.options[matiereSelect.selectedIndex];
        const enseignantsIds = JSON.parse(selectedOption.dataset.enseignants || '[]');
        const heuresRestantes = selectedOption.dataset.heuresRestantes;
        const volumeTotal = selectedOption.dataset.volumeTotal;
        const heuresRestantesFormatted = selectedOption.dataset.heuresRestantesFormatted || (heuresRestantes + 'h');
        const volumeTotalFormatted = selectedOption.dataset.volumeTotalFormatted || (volumeTotal + 'h');

        // Afficher les informations sur la matière
        heuresRestantesText.textContent = `${heuresRestantesFormatted} restantes sur ${volumeTotalFormatted}`;
        matiereInfo.style.display = 'block';

        if (!requiresTeacher) {
            if (teacherInfo) {
                teacherInfo.style.display = 'none';
            }
            if (teacherAvailability) {
                teacherAvailability.style.display = 'none';
            }
            if (teacherCreateActions) {
                teacherCreateActions.style.display = 'none';
            }
            return;
        }

        // Ajouter les enseignants assignés à cette matière
        const allTeachers = seanceData.teachers;
        enseignantsIds.forEach(rawId => {
            const teacherId = rawId?.toString();
            if (teacherId && allTeachers[teacherId]) {
                const teacher = allTeachers[teacherId];
                const teacherName = teacher?.user?.name
                    ?? teacher?.name
                    ?? teacher?.matricule
                    ?? `Enseignant ${teacherId}`;
                const option = new Option(teacherName, teacherId);
                if (currentTeacherId && teacherId === currentTeacherId) {
                    option.selected = true;
                }
                teacherSelect.add(option);
            }
        });

        if (enseignantsIds.length === 0) {
            teacherSelect.innerHTML = '<option value="">Aucun enseignant assigné à cette matière</option>';
        }

        if (teacherCreateActions) {
            teacherCreateActions.style.display = 'block';
        }
        if (teacherEmptyState) {
            teacherEmptyState.style.display = enseignantsIds.length === 0 ? 'block' : 'none';
        }
    } else {
        matiereInfo.style.display = 'none';
        teacherSelect.innerHTML = '<option value="">Sélectionner d\'abord une matière</option>';
        if (teacherCreateActions) {
            teacherCreateActions.style.display = 'none';
        }
        if (teacherEmptyState) {
            teacherEmptyState.style.display = 'none';
        }
    }

    // Reset teacher availability
    document.getElementById('teacher-availability').style.display = 'none';
}

function syncTeacherModalContext() {
    const matiereSelect = document.getElementById('matiere_id');
    const matiereLabel = document.getElementById('teacher_modal_matiere');
    const planificationInput = document.getElementById('teacher_planification_id');
    const matiereInput = document.getElementById('teacher_matiere_id');
    if (!matiereSelect || !matiereLabel || !planificationInput || !matiereInput) {
        return;
    }

    if (!matiereSelect.value) {
        matiereLabel.textContent = '--';
        planificationInput.value = '';
        matiereInput.value = '';
        return;
    }

    const selectedOption = matiereSelect.options[matiereSelect.selectedIndex];
    matiereLabel.textContent = selectedOption.textContent?.trim() || '--';
    planificationInput.value = selectedOption.dataset.planificationId || '';
    matiereInput.value = matiereSelect.value;
}

function openTeacherCreateModal() {
    const matiereSelect = document.getElementById('matiere_id');
    if (!matiereSelect || !matiereSelect.value) {
        alert('Sélectionnez une matière avant de créer un enseignant.');
        return;
    }
    const selectedOption = matiereSelect.options[matiereSelect.selectedIndex];
    if (!selectedOption.dataset.planificationId) {
        alert('Cette matière n\'est pas encore configurée dans le planning général.');
        return;
    }
    syncTeacherModalContext();
    const modalElement = document.getElementById('teacherCreateModal');
    if (!modalElement) {
        return;
    }
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

function initTeacherAvailabilityGrid() {
    const grid = document.getElementById('teacherAvailabilityGrid');
    const inputsContainer = document.getElementById('teacherAvailabilityInputs');
    if (!grid || !inputsContainer || grid.dataset.ready === 'true') {
        return;
    }

    const days = [
        { key: 0, label: 'Lun' },
        { key: 1, label: 'Mar' },
        { key: 2, label: 'Mer' },
        { key: 3, label: 'Jeu' },
        { key: 4, label: 'Ven' },
        { key: 5, label: 'Sam' }
    ];
    const hours = Array.from({ length: 10 }, (_, i) => 8 + i);

    let html = '';
    html += '<div class="availability-header-row">';
    html += '<div class="time-header">Heure</div>';
    days.forEach(day => {
        html += `<div class="day-header">${day.label}</div>`;
    });
    html += '</div>';

    hours.forEach(hour => {
        html += '<div class="availability-time-row">';
        html += `<div class="time-label">${hour}:00</div>`;
        days.forEach(day => {
            const inputName = `availability[${day.key}_${hour}]`;
            const inputId = `availability_${day.key}_${hour}`;
            inputsContainer.insertAdjacentHTML('beforeend', `<input type="hidden" id="${inputId}" name="${inputName}" value="available">`);
            html += `<div class="availability-cell available" data-day="${day.key}" data-hour="${hour}" data-input="${inputId}" title="Disponible"></div>`;
        });
        html += '</div>';
    });

    grid.innerHTML = html;
    grid.dataset.ready = 'true';

    grid.addEventListener('click', (event) => {
        const cell = event.target.closest('.availability-cell');
        if (!cell) {
            return;
        }
        toggleAvailabilityCell(cell);
    });
}

function toggleAvailabilityCell(cell) {
    const cycle = ['available', 'preferred', 'unavailable'];
    const current = cycle.find(status => cell.classList.contains(status)) || 'available';
    const next = cycle[(cycle.indexOf(current) + 1) % cycle.length];
    cell.classList.remove('available', 'preferred', 'unavailable');
    cell.classList.add(next);
    const inputId = cell.dataset.input;
    const input = inputId ? document.getElementById(inputId) : null;
    if (input) {
        input.value = next;
    }
}

function setAllTeacherAvailability(status) {
    const grid = document.getElementById('teacherAvailabilityGrid');
    if (!grid) {
        return;
    }
    grid.querySelectorAll('.availability-cell').forEach(cell => {
        cell.classList.remove('available', 'preferred', 'unavailable');
        cell.classList.add(status);
        const inputId = cell.dataset.input;
        const input = inputId ? document.getElementById(inputId) : null;
        if (input) {
            input.value = status;
        }
    });
}

function handleTeacherCreateSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = document.getElementById('teacherCreateSubmit');
    const errorBox = document.getElementById('teacherCreateErrors');
    if (!form || !submitBtn) {
        return;
    }

    if (errorBox) {
        errorBox.style.display = 'none';
        errorBox.innerHTML = '';
    }

    submitBtn.disabled = true;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Création...';

    const formData = new FormData(form);

    fetch(teacherQuickCreateUrl, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
        .then(async response => {
            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw payload;
            }
            return response.json();
        })
        .then(payload => {
            if (!payload || !payload.success) {
                throw payload;
            }
            const teacher = payload.teacher;
            const availability = payload.availability || {};
            if (teacher && teacher.id) {
                seanceData.teachers[teacher.id] = teacher;
                seanceData.availability[teacher.id] = availability;
                attachTeacherToCurrentMatiere(teacher.id);
                updateTeachersForSubject();
                const teacherSelect = document.getElementById('teacher_id');
                if (teacherSelect) {
                    const teacherValue = teacher.id.toString();
                    if (window.jQuery && window.$) {
                        $('#teacher_id').val(teacherValue).trigger('change.select2');
                    } else {
                        teacherSelect.value = teacherValue;
                        teacherSelect.dispatchEvent(new Event('change'));
                    }
                    showTeacherAvailability();
                }
            }
            form.reset();
            setAllTeacherAvailability('available');
            const modalElement = document.getElementById('teacherCreateModal');
            if (modalElement) {
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        })
        .catch(error => {
            if (!errorBox) {
                alert('Impossible de créer l\'enseignant.');
                return;
            }
            const messages = [];
            if (error && error.errors) {
                Object.values(error.errors).forEach(list => {
                    list.forEach(item => messages.push(`<li>${item}</li>`));
                });
            }
            if (error && error.message) {
                messages.push(`<li>${error.message}${error.file ? ' — ' + error.file + ':' + error.line : ''}</li>`);
            }
            if (messages.length === 0) {
                messages.push('<li>Impossible de créer l\'enseignant. Vérifiez les champs.</li>');
            }
            errorBox.innerHTML = `<ul class="mb-0">${messages.join('')}</ul>`;
            errorBox.style.display = 'block';
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
}

function attachTeacherToCurrentMatiere(teacherId) {
    const matiereSelect = document.getElementById('matiere_id');
    if (!matiereSelect || !matiereSelect.value) {
        return;
    }
    const selectedOption = matiereSelect.options[matiereSelect.selectedIndex];
    const enseignantsIds = JSON.parse(selectedOption.dataset.enseignants || '[]');
    const normalizedId = teacherId.toString();
    if (!enseignantsIds.map(String).includes(normalizedId)) {
        enseignantsIds.push(teacherId);
        selectedOption.dataset.enseignants = JSON.stringify(enseignantsIds);
    }
}

// ─── Gestion des enseignants (modal) ────────────────────────────────
let currentPlanificationId = null;

function openManageTeachersModal() {
    const matiereSelect = document.getElementById('matiere_id');
    if (!matiereSelect || !matiereSelect.value) {
        alert('Sélectionnez une matière avant de gérer les enseignants.');
        return;
    }
    const selectedOption = matiereSelect.options[matiereSelect.selectedIndex];
    const planifId = selectedOption.dataset.planificationId;
    if (!planifId) {
        alert("Cette matière n'est pas encore configurée dans le planning général.");
        return;
    }
    currentPlanificationId = planifId;

    // Set matière label
    const label = document.getElementById('mgtMatiereLabel');
    if (label) label.textContent = selectedOption.textContent.trim();

    // Show loading, hide content
    const loading = document.getElementById('mgtLoading');
    const content = document.getElementById('mgtContent');
    if (loading) loading.style.display = 'flex';
    if (content) content.style.display = 'none';

    const modalEl = document.getElementById('manageTeachersModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    // Fetch data
    fetch(`${manageTeachersBaseUrl}/${planifId}/teachers`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw data;
        renderManageTeachersContent(data.linked_teachers, data.available_teachers);
        if (loading) loading.style.display = 'none';
        if (content) content.style.display = 'block';
    })
    .catch(() => {
        if (loading) loading.innerHTML = '<i class="fas fa-exclamation-triangle text-danger"></i> Erreur de chargement';
    });
}

function renderManageTeachersContent(linked, available) {
    // Linked teachers list
    const listEl = document.getElementById('mgtLinkedList');
    const emptyEl = document.getElementById('mgtLinkedEmpty');
    const countEl = document.getElementById('mgtLinkedCount');
    if (countEl) countEl.textContent = linked.length;

    if (linked.length === 0) {
        if (listEl) listEl.innerHTML = '';
        if (emptyEl) emptyEl.style.display = 'flex';
    } else {
        if (emptyEl) emptyEl.style.display = 'none';
        if (listEl) {
            listEl.innerHTML = linked.map(t => {
                const hasSeances = t.seance_count > 0;
                const seanceBadge = hasSeances
                    ? `<span class="mgt-seance-badge" title="${t.seance_count} séance(s) programmée(s)"><i class="fas fa-calendar-check me-1"></i>${t.seance_count}</span>`
                    : '';
                const removeBtn = hasSeances
                    ? `<button class="mgt-btn-remove mgt-btn-locked" disabled title="Cet enseignant a ${t.seance_count} séance(s) programmée(s)"><i class="fas fa-lock"></i></button>`
                    : `<button class="mgt-btn-remove" onclick="handleDissociateTeacher(${t.id})" title="Retirer de la planification"><i class="fas fa-times"></i></button>`;
                return `
                    <div class="mgt-teacher-row" data-teacher-id="${t.id}">
                        <div class="mgt-teacher-avatar">${(t.name || '?')[0].toUpperCase()}</div>
                        <div class="mgt-teacher-info">
                            <span class="mgt-teacher-name">${t.name}</span>
                            <span class="mgt-teacher-spec">${t.specialization || ''}</span>
                        </div>
                        ${seanceBadge}
                        ${removeBtn}
                    </div>
                `;
            }).join('');
        }
    }

    // Available teachers select
    const select = document.getElementById('mgtTeacherSelect');
    if (select) {
        select.innerHTML = '<option value="">Rechercher un enseignant...</option>';
        available.forEach(t => {
            const opt = new Option(`${t.name}${t.specialization ? ' — ' + t.specialization : ''}`, t.id);
            select.add(opt);
        });
        // Re-init Select2 — dropdownParent on modal body (NOT document.body)
        // Bootstrap .modal has overflow-y:auto but the modal-body itself works fine
        // if we ensure the modal-content allows overflow.
        if (window.jQuery) {
            if ($(select).data('select2')) {
                $(select).select2('destroy');
            }
            $(select).select2({
                dropdownParent: $('#manageTeachersModal .modal-content'),
                placeholder: 'Rechercher un enseignant...',
                allowClear: true,
                theme: 'bootstrap-5',
                width: '100%',
                language: 'fr'
            }).on('change', function () {
                console.log('[MGT] Select2 change:', this.value);
                const btn = document.getElementById('mgtAssociateBtn');
                if (btn) btn.disabled = !this.value;
            }).on('select2:open', function () {
                console.log('[MGT] Select2 opened');
            }).on('select2:close', function () {
                console.log('[MGT] Select2 closed');
            }).on('select2:select', function (e) {
                console.log('[MGT] Select2 selected:', e.params.data);
            });
        }
    }
    const btn = document.getElementById('mgtAssociateBtn');
    if (btn) btn.disabled = true;
}

function handleAssociateTeacher() {
    const select = document.getElementById('mgtTeacherSelect');
    const teacherId = select ? select.value : null;
    if (!teacherId || !currentPlanificationId) return;

    const btn = document.getElementById('mgtAssociateBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Association...';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(`${manageTeachersBaseUrl}/${currentPlanificationId}/manage-teachers`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'associate', teacher_id: teacherId })
    })
    .then(async r => {
        const payload = await r.json().catch(() => ({}));
        if (!r.ok) throw payload;
        return payload;
    })
    .then(data => {
        // Re-fetch full data to refresh both lists
        refreshManageTeachersContent();
        // Update matière data-enseignants attribute for the main form
        syncLinkedTeachersToForm(data.linked_ids || []);
        updateTeachersForSubject();
    })
    .catch(err => {
        alert(err.message || "Erreur lors de l'association.");
    })
    .finally(() => {
        if (btn) {
            btn.innerHTML = '<i class="fas fa-link me-1"></i>Associer';
            btn.disabled = true;
        }
    });
}

function handleDissociateTeacher(teacherId) {
    if (!currentPlanificationId) return;

    const row = document.querySelector(`.mgt-teacher-row[data-teacher-id="${teacherId}"]`);
    if (row) row.classList.add('mgt-removing');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(`${manageTeachersBaseUrl}/${currentPlanificationId}/manage-teachers`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'dissociate', teacher_id: teacherId })
    })
    .then(async r => {
        const payload = await r.json().catch(() => ({}));
        if (!r.ok) throw payload;
        return payload;
    })
    .then(data => {
        refreshManageTeachersContent();
        syncLinkedTeachersToForm(data.linked_ids || []);
        updateTeachersForSubject();
    })
    .catch(err => {
        if (row) row.classList.remove('mgt-removing');
        if (err.blocked) {
            showMgtErrorDialog(err.message);
        } else {
            alert(err.message || 'Erreur lors de la dissociation.');
        }
    });
}

function showMgtErrorDialog(message) {
    const msgEl = document.getElementById('mgtErrorMessage');
    if (msgEl) msgEl.textContent = message;
    const dialogEl = document.getElementById('mgtErrorDialog');
    if (dialogEl) {
        const dialog = new bootstrap.Modal(dialogEl);
        dialog.show();
    }
}

function refreshManageTeachersContent() {
    if (!currentPlanificationId) return;
    fetch(`${manageTeachersBaseUrl}/${currentPlanificationId}/teachers`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderManageTeachersContent(data.linked_teachers, data.available_teachers);
        }
    });
}

function syncLinkedTeachersToForm(linkedIds) {
    const matiereSelect = document.getElementById('matiere_id');
    if (!matiereSelect || !matiereSelect.value) return;
    const selectedOption = matiereSelect.options[matiereSelect.selectedIndex];
    selectedOption.dataset.enseignants = JSON.stringify(linkedIds);
}

// Fonction pour afficher la disponibilité de l'enseignant sélectionné
function showTeacherAvailability() {
    const teacherSelect = document.getElementById('teacher_id');
    const teacherAvailability = document.getElementById('teacher-availability');
    const selectedTeacherName = document.getElementById('selected-teacher-name');
    const availabilityGrid = document.getElementById('availability-grid');
    const teacherInfo = document.getElementById('teacher-info');
    const teacherAssignmentText = document.getElementById('teacher-assignment-text');
    const currentType = document.getElementById('sessionType').value;

    if (currentType !== 'course') {
        teacherAvailability.style.display = 'none';
        teacherInfo.style.display = 'none';
        return;
    }

    if (teacherSelect.value) {
        const teacherId = teacherSelect.value;
        const teacherName = teacherSelect.options[teacherSelect.selectedIndex].text;
        const availabilityData = seanceData.availability;

        selectedTeacherName.textContent = teacherName;
        teacherAssignmentText.textContent = `Enseignant assigné: ${teacherName}`;
        teacherInfo.style.display = 'block';

        // Mettre à jour le lien "Modifier" disponibilité
        const editBtn = document.getElementById('btn-edit-teacher-availability');
        if (editBtn) {
            editBtn.href = `/esbtp/enseignants/bulk-availability?ids[]=${teacherId}`;
        }

        debugLog('🔍 Availability data for teacher', teacherId, ':', availabilityData[teacherId]);

        if (availabilityData[teacherId]) {
            // Construire la grille de disponibilité
            const rawAvailability = availabilityData[teacherId];
            let gridHtml = '';

            // Header avec les jours
            gridHtml += '<div class="availability-header-row">';
            gridHtml += '<div class="time-header">Heure</div>';
            const dayHeaders = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            for (let i = 0; i < dayHeaders.length; i++) {
                gridHtml += `<div class="day-header">${dayHeaders[i]}</div>`;
            }
            gridHtml += '</div>';

            // Créer les lignes pour chaque heure (8h-18h)
            for (let hour = 8; hour < 18; hour++) {
                gridHtml += '<div class="availability-time-row">';
                gridHtml += `<div class="time-label">${hour}:00</div>`;

                // Clés des jours dans l'ordre: monday, tuesday, wednesday, thursday, friday, saturday
                const dayKeys = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

                dayKeys.forEach((dayKey, dayIndex) => {
                    let cellClass = 'unavailable';
                    let cellTitle = 'Non disponible';
                    let cellStatus = 'unavailable';

                    // Le format est: rawAvailability[dayKey][hourIndex] = 'available'/'preferred'/'unavailable'/'occupied'
                    if (rawAvailability[dayKey]) {
                        const hourIndex = hour - 8; // 8h = index 0
                        if (hourIndex >= 0 && hourIndex < rawAvailability[dayKey].length) {
                            const status = rawAvailability[dayKey][hourIndex];
                            if (status === 'occupied') {
                                cellClass = 'occupied';
                                cellTitle = 'Occupé par une autre séance';
                                cellStatus = 'occupied';
                            } else if (status === 'preferred') {
                                cellClass = 'preferred';
                                cellTitle = 'Préféré';
                                cellStatus = 'preferred';
                            } else if (status === 'available') {
                                cellClass = 'available';
                                cellTitle = 'Disponible';
                                cellStatus = 'available';
                            }
                        }
                    }

                    gridHtml += `<div class="availability-cell ${cellClass}" data-day="${dayIndex + 1}" data-hour="${hour}" data-status="${cellStatus}" title="${cellTitle}"></div>`;
                });
                gridHtml += '</div>';
            }

            availabilityGrid.innerHTML = gridHtml;
            teacherAvailability.style.display = 'block';
            bindAvailabilityGridHandlers();

            // Mettre à jour les créneaux sélectionnés après construction de la grille
            setTimeout(updateSelectedTimeInGrid, 100);
        } else {
            availabilityGrid.innerHTML = '<div class="no-availability">Aucune disponibilité configurée pour cet enseignant</div>';
            teacherAvailability.style.display = 'block';
        }

        // Démarrer le polling pour détecter les changements BDD
        startAvailabilityPolling(teacherId);
    } else {
        teacherAvailability.style.display = 'none';
        teacherInfo.style.display = 'none';
        stopAvailabilityPolling();
    }
}

// ============================
// POLLING DISPONIBILITÉS
// ============================
let availabilityPollInterval = null;
let lastAvailabilityTimestamp = null;
let currentPolledTeacherId = null;

function startAvailabilityPolling(teacherId) {
    stopAvailabilityPolling();
    currentPolledTeacherId = teacherId;
    lastAvailabilityTimestamp = null;

    // Timestamp initial silencieux (pas de rendu)
    fetchAvailabilityData(teacherId, true);

    // Puis polling toutes les 10 secondes
    availabilityPollInterval = setInterval(() => {
        fetchAvailabilityData(teacherId, false);
    }, 10000);
}

function stopAvailabilityPolling() {
    if (availabilityPollInterval) {
        clearInterval(availabilityPollInterval);
        availabilityPollInterval = null;
    }
    currentPolledTeacherId = null;
    lastAvailabilityTimestamp = null;
}

function fetchAvailabilityData(teacherId, isInitial = false) {
    const url = `/esbtp/enseignants/${teacherId}/availability-data`;

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(json => {
        if (!json.success) return;

        if (isInitial) {
            // Premier appel : on stocke juste le timestamp de référence
            lastAvailabilityTimestamp = json.updated_at;
            return;
        }

        // Comparer avec le timestamp précédent
        if (lastAvailabilityTimestamp === json.updated_at) {
            return;
        }

        lastAvailabilityTimestamp = json.updated_at;

        // Mettre à jour la grille discrètement
        refreshAvailabilityGridFromPolling(teacherId, json.data);
    })
    .catch(() => {});
}

function refreshAvailabilityGridFromPolling(teacherId, newData) {
    const availabilityGrid = document.getElementById('availability-grid');
    if (!availabilityGrid) return;

    // Désactiver visuellement pendant le refresh
    availabilityGrid.style.opacity = '0.5';
    availabilityGrid.style.pointerEvents = 'none';

    // Icône refresh en rotation
    const refreshIcon = document.getElementById('refresh-icon');
    if (refreshIcon) refreshIcon.classList.add('fa-spin');

    // Mettre à jour les données globales seanceData
    if (window.seanceData && window.seanceData.availability) {
        window.seanceData.availability[teacherId] = newData;
    }

    // Reconstruire le HTML de la grille (même logique que showTeacherAvailability)
    const rawAvailability = newData;
    let gridHtml = '';

    gridHtml += '<div class="availability-header-row">';
    gridHtml += '<div class="time-header">Heure</div>';
    const dayHeaders = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    dayHeaders.forEach(d => { gridHtml += `<div class="day-header">${d}</div>`; });
    gridHtml += '</div>';

    const dayKeys = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    for (let hour = 8; hour < 18; hour++) {
        gridHtml += '<div class="availability-time-row">';
        gridHtml += `<div class="time-label">${hour}:00</div>`;

        dayKeys.forEach((dayKey, dayIndex) => {
            let cellClass = 'unavailable';
            let cellTitle = 'Non disponible';
            let cellStatus = 'unavailable';

            if (rawAvailability[dayKey]) {
                const hourIndex = hour - 8;
                if (hourIndex >= 0 && hourIndex < rawAvailability[dayKey].length) {
                    const status = rawAvailability[dayKey][hourIndex];
                    if (status === 'occupied') {
                        cellClass = 'occupied'; cellTitle = 'Occupé par une autre séance'; cellStatus = 'occupied';
                    } else if (status === 'preferred') {
                        cellClass = 'preferred'; cellTitle = 'Préféré'; cellStatus = 'preferred';
                    } else if (status === 'available') {
                        cellClass = 'available'; cellTitle = 'Disponible'; cellStatus = 'available';
                    }
                }
            }

            gridHtml += `<div class="availability-cell ${cellClass}" data-day="${dayIndex + 1}" data-hour="${hour}" data-status="${cellStatus}" title="${cellTitle}"></div>`;
        });
        gridHtml += '</div>';
    }

    availabilityGrid.innerHTML = gridHtml;
    bindAvailabilityGridHandlers();
    setTimeout(updateSelectedTimeInGrid, 100);

    // Réactiver avec transition fluide
    setTimeout(() => {
        availabilityGrid.style.opacity = '1';
        availabilityGrid.style.pointerEvents = 'auto';
        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
    }, 300);
}

function forceRefreshAvailability() {
    if (!currentPolledTeacherId) return;

    // Forcer la mise à jour en ignorant le timestamp
    const savedTimestamp = lastAvailabilityTimestamp;
    lastAvailabilityTimestamp = null; // Reset pour forcer le rendu

    const url = `/esbtp/enseignants/${currentPolledTeacherId}/availability-data`;
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(r => r.json())
    .then(json => {
        if (!json.success) return;
        lastAvailabilityTimestamp = json.updated_at;
        refreshAvailabilityGridFromPolling(currentPolledTeacherId, json.data);
    })
    .catch(() => {
        lastAvailabilityTimestamp = savedTimestamp; // Restaurer si erreur
    });
}
</script>

<style>
/* === SECTIONS AVEC ESPACEMENT === */
.form-sections .main-card {
    margin-bottom: var(--space-xl);
}

.main-card.error-highlight {
    border: 2px solid var(--danger);
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
}

/* Styles pour les types de séance */
.session-types-container {
    display: flex;
    gap: var(--space-lg);
    flex-wrap: wrap;
    justify-content: space-between;
}

.session-type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--space-lg);
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: var(--radius-medium);
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--surface);
    flex: 1;
    min-width: 120px;
    text-align: center;
    box-shadow: var(--shadow-card);
}

.session-type-card:hover {
    background: var(--primary);
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.session-type-card:hover .session-type-icon,
.session-type-card:hover .session-type-label {
    color: white;
}

.session-type-card.selected {
    background: var(--primary);
    border-color: var(--primary);
}

.session-type-card.selected .session-type-icon,
.session-type-card.selected .session-type-label {
    color: white;
}

.evaluation-slot-card {
    display: flex;
    align-items: center;
    gap: var(--space-lg);
    padding: var(--space-lg);
    border: 1px solid rgba(30, 64, 175, 0.12);
    border-radius: var(--radius-medium);
    background: linear-gradient(135deg, rgba(30, 64, 175, 0.06), rgba(59, 130, 246, 0.08));
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
    margin-top: var(--space-lg);
}

.slot-icon {
    width: 52px;
    height: 52px;
    border-radius: var(--radius-circle);
    background: rgba(59, 130, 246, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--primary);
}

.slot-content {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.slot-title {
    font-weight: 700;
    color: var(--text-primary);
    font-size: 1.05rem;
}

.slot-times {
    display: flex;
    align-items: baseline;
    gap: var(--space-sm);
    font-size: 0.95rem;
}

.slot-label {
    font-weight: 600;
    color: var(--primary);
}

.slot-time {
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--text-primary);
}

.slot-separator {
    color: rgba(15, 23, 42, 0.4);
    font-weight: 600;
}

.slot-duration {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    color: rgba(15, 23, 42, 0.7);
    font-size: 0.9rem;
}

.slot-duration small {
    color: rgba(15, 23, 42, 0.6);
    font-style: italic;
}

.session-type-icon {
    font-size: 2rem;
    color: var(--primary);
    margin-bottom: var(--space-sm);
    transition: color 0.3s ease;
}

.session-type-label {
    font-weight: 600;
    color: var(--text-primary);
    transition: color 0.3s ease;
}

/* === CONTEXTE DE CLASSE - DESIGN MODERNE === */
.context-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
    border: 1px solid rgba(30, 58, 138, 0.08);
    border-radius: var(--radius-medium);
    padding: var(--space-xl);
    margin-bottom: var(--space-xl);
    box-shadow: 0 2px 8px rgba(30, 58, 138, 0.04);
    position: relative;
    overflow: hidden;
}

.context-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 50%, var(--accent-blue) 100%);
}

.context-header {
    display: flex;
    align-items: center;
    margin-bottom: var(--space-lg);
    padding-bottom: var(--space-md);
    border-bottom: 1px solid rgba(30, 58, 138, 0.1);
}

.context-header i {
    font-size: 1.5rem;
    color: var(--primary);
    margin-right: var(--space-md);
    padding: var(--space-sm);
    background: rgba(30, 58, 138, 0.1);
    border-radius: var(--radius-circle);
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.context-header span {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.context-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: var(--space-lg);
}

.stat-item {
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(30, 58, 138, 0.06);
    border-radius: var(--radius-small);
    padding: var(--space-lg);
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
}

.stat-item:hover {
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(30, 58, 138, 0.12);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.08);
}

.stat-label {
    display: block;
    font-size: var(--text-small);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-secondary);
    margin-bottom: var(--space-sm);
    font-weight: 600;
}

.stat-value {
    display: block;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}

/* Couleurs spécifiques par stat */
.stat-item:nth-child(1) {
    border-left: 3px solid var(--primary);
}

.stat-item:nth-child(2) {
    border-left: 3px solid var(--secondary);
}

.stat-item:nth-child(3) {
    border-left: 3px solid var(--accent-blue);
}

.stat-item:nth-child(4) {
    border-left: 3px solid var(--success);
}

/* Styles pour les disponibilités */
.availability-section {
    margin-top: 2rem;
    padding: 1.5rem;
    background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.availability-header {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.availability-header i {
    color: var(--primary);
    background: rgba(59, 130, 246, 0.12);
    padding: 0.4rem;
    border-radius: 999px;
}

.teacher-availability-grid {
    background: #fff;
    border-radius: 12px;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);
}

.availability-header-row,
.availability-time-row {
    display: grid;
    grid-template-columns: 60px repeat(6, 1fr);
    gap: 6px;
    margin-bottom: 6px;
}

.time-header,
.day-header,
.time-label,
.availability-cell {
    padding: 6px;
    text-align: center;
    font-size: 0.78rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.time-header,
.day-header {
    background: #f1f5f9;
    font-weight: 700;
    color: var(--text-primary);
}

.time-label {
    background: #f8fafc;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.74rem;
}

.availability-cell {
    height: 34px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 600;
    color: #fff;
    background: #f8fafc;
    border-color: #e2e8f0;
}

.availability-cell::after {
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 0.65rem;
    opacity: 0.9;
}

.availability-cell.available {
    background: #22c55e;
    border-color: #16a34a;
}

.availability-cell.available::after {
    content: '\f00c';
}

.availability-cell.available:hover {
    background: #16a34a;
    transform: translateY(-1px) scale(1.04);
}

.availability-cell.preferred {
    background: #2563eb;
    border-color: #1d4ed8;
}

.availability-cell.preferred::after {
    content: '\f005';
}

.availability-cell.preferred:hover {
    background: #1d4ed8;
    transform: translateY(-1px) scale(1.04);
}

.availability-cell.unavailable {
    background: #ef4444;
    border-color: #dc2626;
    cursor: not-allowed;
}

.availability-cell.unavailable::after {
    content: '\f05e';
}

.availability-cell.unavailable:hover {
    background: #dc2626;
}

.availability-cell.occupied {
    background: #64748b;
    border-color: #475569;
    cursor: not-allowed;
}

.availability-cell.occupied::after {
    content: '\f023';
}

.availability-cell.occupied:hover {
    background: #475569;
    transform: translateY(-1px) scale(1.02);
}

.availability-cell.selected-time {
    position: relative;
    animation: selectedPulse 1.8s infinite;
    transform: translateY(-1px);
    z-index: 1;
}

.availability-cell.selected-time::before {
    content: '';
    position: absolute;
    inset: 2px;
    border: 2px solid rgba(245, 158, 11, 0.9);
    border-radius: 6px;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25);
    z-index: 1;
}

.availability-cell.selected-time:hover::before {
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.4);
}

.availability-cell.selected-time::after {
    content: '\f140';
    color: #92400e;
}

.availability-cell.error {
    background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
    border-color: #dc2626;
    box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.35);
    animation: cellShake 0.35s ease-in-out 2;
    z-index: 2;
}

.availability-cell.error[data-error]::before {
    content: attr(data-error);
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: #fff;
    font-size: 0.7rem;
    line-height: 1.3;
    padding: 0.35rem 0.55rem;
    border-radius: 6px;
    width: max-content;
    max-width: 220px;
    white-space: normal;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
    z-index: 3;
}

.availability-cell.error[data-error]::after {
    content: '\f071';
}

.availability-cell.error[data-error]:hover::before {
    opacity: 1;
}

.availability-cell.selecting {
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.45);
    transform: translateY(-1px) scale(1.02);
    z-index: 1;
}

.availability-inline-error {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    padding: 0.65rem 0.85rem;
    border-radius: 10px;
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid rgba(185, 28, 28, 0.2);
    font-size: 0.85rem;
    font-weight: 600;
}

.availability-inline-error::before {
    content: '\f071';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
}

.availability-hint {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    font-size: 0.8rem;
    color: var(--text-secondary);
}

@keyframes selectedPulse {
    0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.45); }
    70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
    100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

@keyframes cellShake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-2px); }
    40% { transform: translateX(2px); }
    60% { transform: translateX(-2px); }
    80% { transform: translateX(2px); }
}

.no-availability {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
    font-style: italic;
}

/* Styles pour la légende des disponibilités */
.availability-legend {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
}

.legend-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.75rem;
}

.legend-items {
    display: flex;
    gap: 1.25rem;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 1px solid rgba(0,0,0,0.1);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.legend-color i {
    font-size: 0.6rem;
}

.legend-color.preferred {
    background: #2563eb;
    border-color: #1d4ed8;
}

.legend-color.available {
    background: #22c55e;
    border-color: #16a34a;
}

.legend-color.unavailable {
    background: #ef4444;
    border-color: #dc2626;
}

.legend-color.selected-time {
    background: #f59e0b;
    border-color: #d97706;
    position: relative;
}

.legend-color.occupied {
    background: #64748b;
    border-color: #475569;
}

.legend-item span {
    font-size: 0.85rem;
    color: #495057;
    font-weight: 500;
}

/* Sélecteur de jours pour récurrence */
.days-selector {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.day-option {
    position: relative;
}

.day-checkbox {
    display: none;
}

.day-label {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    background: #e9ecef;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.875rem;
    font-weight: 500;
}

.day-checkbox:checked + .day-label {
    background: var(--primary-color);
    color: white;
}

/* Color picker wrapper */
.color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.color-picker {
    width: 40px;
    height: 40px;
    padding: 0;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
}

.color-label {
    font-size: 0.875rem;
    color: var(--text-muted);
}

/* === STYLES FORMULAIRES MODERNES === */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--space-lg);
}

.form-group {
    margin-bottom: var(--space-lg);
}

.form-label {
    display: block;
    margin-bottom: var(--space-sm);
    font-weight: 600;
    font-size: var(--text-small);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: var(--space-md);
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: var(--radius-small);
    font-size: var(--text-normal);
    font-family: inherit;
    transition: all 0.2s ease;
    background-color: var(--surface);
    color: var(--text-primary);
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
}

.form-input.error, .form-select.error, .form-textarea.error {
    border-color: var(--danger);
}

.form-error {
    margin-top: var(--space-xs);
    font-size: var(--text-small);
    color: var(--danger);
    font-weight: 500;
}

.form-info {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    margin-top: var(--space-sm);
    padding: var(--space-sm);
    background: rgba(6, 182, 212, 0.1);
    border-radius: var(--radius-small);
    font-size: var(--text-small);
    color: var(--accent-blue);
}

/* === BOUTONS D'ACTIONS === */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-md);
    padding-top: var(--space-lg);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    margin-top: var(--space-xl);
}

/* Utiliser les boutons du dashboard-moderne.css */
.btn-acasi {
    display: inline-flex;
    align-items: center;
    padding: var(--space-sm) var(--space-lg);
    border: none;
    border-radius: var(--radius-small);
    font-size: var(--text-normal);
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    gap: var(--space-xs);
}

.btn-acasi.primary {
    background-color: var(--primary);
    color: white;
}

.btn-acasi.primary:hover {
    background-color: var(--secondary);
    transform: translateY(-1px);
    box-shadow: var(--shadow-elevated);
}

.btn-acasi.secondary {
    background-color: transparent;
    color: var(--primary);
    border: 1px solid var(--primary);
}

.btn-acasi.secondary:hover {
    background-color: var(--primary);
    color: white;
}

.recurrence-section {
    margin-top: var(--space-lg);
    padding: var(--space-lg);
    background: rgba(6, 182, 212, 0.05);
    border-radius: var(--radius-small);
    border: 1px solid rgba(6, 182, 212, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .session-types-container {
        flex-direction: column;
        gap: 0.75rem;
    }

    .session-type-card {
        flex-direction: row;
        justify-content: flex-start;
        text-align: left;
        padding: 1rem;
    }

    .session-type-icon {
        margin-right: 1rem;
        margin-bottom: 0;
        font-size: 1.5rem;
    }

    .context-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .availability-header-row,
    .availability-time-row {
        grid-template-columns: 50px repeat(7, 1fr);
    }

    .time-label,
    .availability-cell {
        padding: 4px;
        font-size: 0.7rem;
    }
}

@media (max-width: 576px) {
    .session-types-container {
        gap: 0.5rem;
    }

    .session-type-card {
        padding: 0.75rem;
        min-width: auto;
    }

    .session-type-icon {
        font-size: 1.25rem;
        margin-right: 0.75rem;
    }

    .session-type-label {
        font-size: 0.875rem;
    }
}

/* ─── Manage Teachers Modal (mgt-*) ─────────────────────────────── */
.mgt-modal {
    border: none;
    border-radius: 20px;
    overflow: visible !important;
    box-shadow:
        0 24px 80px rgba(0, 0, 0, 0.18),
        0 4px 24px rgba(4, 83, 203, 0.08);
}

.mgt-header {
    background: linear-gradient(135deg, #0453cb 0%, #1e6fe0 50%, #5e91de 100%);
    border: none;
    padding: 1.5rem 1.75rem;
    position: relative;
    overflow: hidden;
}

.mgt-header::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -15%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
}

.mgt-header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
    z-index: 1;
}

.mgt-header-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    flex-shrink: 0;
}

.mgt-title {
    color: white;
    font-weight: 700;
    font-size: 1.15rem;
    margin: 0;
    letter-spacing: -0.01em;
}

.mgt-subtitle {
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.82rem;
    margin: 0.15rem 0 0;
    font-weight: 500;
}

.mgt-body {
    padding: 1.5rem 1.75rem;
    background: #fafbfd;
    border-radius: 0 0 20px 20px;
    overflow: visible !important;
}

.mgt-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 3rem 0;
    color: #64748b;
    font-size: 0.9rem;
}

.mgt-spinner {
    width: 22px;
    height: 22px;
    border: 2.5px solid #e2e8f0;
    border-top-color: #0453cb;
    border-radius: 50%;
    animation: mgtSpin 0.7s linear infinite;
}

@keyframes mgtSpin {
    to { transform: rotate(360deg); }
}

/* Section headers */
.mgt-section {
    margin-bottom: 1rem;
}

.mgt-section-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #475569;
    margin-bottom: 0.75rem;
}

.mgt-section-header i {
    color: #0453cb;
    font-size: 0.75rem;
}

.mgt-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 100px;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
}

/* Teacher rows */
.mgt-linked-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.mgt-teacher-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: white;
    border-radius: 12px;
    border: 1px solid #e8ecf1;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.mgt-teacher-row:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.mgt-teacher-row.mgt-removing {
    opacity: 0.4;
    transform: scale(0.97);
    pointer-events: none;
}

.mgt-teacher-avatar {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    flex-shrink: 0;
    letter-spacing: -0.02em;
}

.mgt-teacher-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}

.mgt-teacher-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mgt-teacher-spec {
    font-size: 0.78rem;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mgt-seance-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.6rem;
    border-radius: 100px;
    background: #f0fdf4;
    color: #16a34a;
    font-size: 0.72rem;
    font-weight: 600;
    border: 1px solid #bbf7d0;
    flex-shrink: 0;
}

.mgt-btn-remove {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #fecaca;
    background: #fff5f5;
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
    font-size: 0.8rem;
}

.mgt-btn-remove:hover:not(:disabled) {
    background: #fef2f2;
    border-color: #f87171;
    color: #dc2626;
    transform: scale(1.08);
}

.mgt-btn-remove.mgt-btn-locked {
    border-color: #e2e8f0;
    background: #f8fafc;
    color: #94a3b8;
    cursor: not-allowed;
}

/* Empty state */
.mgt-empty-state {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 1.25rem;
    background: white;
    border-radius: 12px;
    border: 1px dashed #cbd5e1;
    color: #94a3b8;
    font-size: 0.85rem;
}

.mgt-empty-state i {
    font-size: 1.1rem;
    opacity: 0.6;
}

/* Divider */
.mgt-divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1.25rem 0;
    color: #94a3b8;
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.mgt-divider::before,
.mgt-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, transparent, #e2e8f0 50%, transparent);
}

/* Add card */
/* Add card */
.mgt-add-card {
    display: flex;
    gap: 0;
    align-items: stretch;
    background: linear-gradient(135deg, #f0f4ff 0%, #e8eef9 100%);
    border-radius: 14px;
    border: 2px solid rgba(4, 83, 203, 0.12);
    overflow: hidden;
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.mgt-add-card:focus-within {
    border-color: rgba(4, 83, 203, 0.3);
    box-shadow: 0 0 0 4px rgba(4, 83, 203, 0.06);
}

.mgt-add-select-wrap {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    min-width: 0;
}

.mgt-add-search-icon {
    position: absolute;
    left: 16px;
    color: #0453cb;
    font-size: 0.8rem;
    z-index: 1;
    pointer-events: none;
    opacity: 0.5;
    transition: opacity 0.2s ease;
}

.mgt-add-select-wrap:focus-within .mgt-add-search-icon {
    opacity: 1;
}

.mgt-select {
    flex: 1;
    width: 100% !important;
    border: none !important;
    padding: 0.85rem 1rem 0.85rem 2.6rem !important;
    font-size: 0.88rem !important;
    background: transparent !important;
    border-radius: 0 !important;
    transition: background 0.2s ease !important;
    color: #1e293b !important;
    font-weight: 500 !important;
    height: auto !important;
}

.mgt-select:focus {
    background: rgba(255, 255, 255, 0.5) !important;
    box-shadow: none !important;
    outline: none !important;
}

.mgt-btn-associate {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 0.85rem 1.75rem;
    border-radius: 0 12px 12px 0;
    background: linear-gradient(135deg, #0453cb 0%, #1e6fe0 100%);
    color: white;
    font-weight: 700;
    font-size: 0.85rem;
    letter-spacing: 0.01em;
    border: none;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: -4px 0 16px rgba(4, 83, 203, 0.15);
    flex-shrink: 0;
}

.mgt-btn-associate:hover:not(:disabled) {
    background: linear-gradient(135deg, #033ea0, #0453cb);
    box-shadow: -4px 0 24px rgba(4, 83, 203, 0.3);
    color: white;
}

.mgt-btn-associate:active:not(:disabled) {
    box-shadow: -2px 0 8px rgba(4, 83, 203, 0.2);
}

.mgt-btn-associate:disabled {
    background: linear-gradient(135deg, #cbd5e1, #b0bec5);
    color: rgba(255, 255, 255, 0.7);
    cursor: not-allowed;
    box-shadow: none;
}

.mgt-btn-associate:disabled i {
    opacity: 0.7;
}

/* Error dialog */
.mgt-error-dialog {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    text-align: center;
    padding: 2rem 1.5rem 1.5rem;
}

.mgt-error-icon-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.mgt-error-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #ef4444;
    box-shadow: 0 0 0 8px rgba(239, 68, 68, 0.06);
}

.mgt-error-body {
    margin-bottom: 1.5rem;
}

.mgt-error-title {
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.5rem;
    font-size: 1.05rem;
}

.mgt-error-message {
    color: #64748b;
    font-size: 0.88rem;
    line-height: 1.5;
    margin: 0;
}

.mgt-error-footer {
    display: flex;
    justify-content: center;
}

.mgt-btn-understand {
    padding: 0.6rem 2rem;
    border-radius: 10px;
    background: linear-gradient(135deg, #1e293b, #334155);
    color: white;
    font-weight: 600;
    font-size: 0.88rem;
    border: none;
    transition: all 0.2s ease;
}

.mgt-btn-understand:hover {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    transform: translateY(-1px);
    color: white;
}

/* Override Bootstrap modal overflow to allow Select2 dropdown to extend */
#manageTeachersModal.modal {
    overflow: visible !important;
}

#manageTeachersModal .modal-dialog {
    overflow: visible !important;
}

/* Select2 inside modal — flush with card */
#manageTeachersModal .select2-container {
    width: 100% !important;
}

#manageTeachersModal .select2-container--bootstrap-5 .select2-selection {
    border: none !important;
    border-radius: 0 !important;
    background: transparent !important;
    min-height: 44px !important;
    padding-left: 2.6rem !important;
    font-weight: 500 !important;
    box-shadow: none !important;
    display: flex !important;
    align-items: center !important;
}

#manageTeachersModal .select2-container--bootstrap-5 .select2-selection--single:focus,
#manageTeachersModal .select2-container--bootstrap-5.select2-container--focus .select2-selection {
    background: rgba(255, 255, 255, 0.5) !important;
    box-shadow: none !important;
}

#manageTeachersModal .select2-container--bootstrap-5 .select2-selection__rendered {
    color: #1e293b !important;
    padding-left: 0 !important;
    font-weight: 500 !important;
    line-height: 1.4 !important;
}

#manageTeachersModal .select2-container--bootstrap-5 .select2-selection__placeholder {
    color: #94a3b8 !important;
}

#manageTeachersModal .select2-container--bootstrap-5 .select2-selection__clear {
    margin-right: 0.5rem !important;
    color: #94a3b8 !important;
    font-size: 1.1rem !important;
}

#manageTeachersModal .select2-container--bootstrap-5 .select2-selection__clear:hover {
    color: #ef4444 !important;
}

/* Select2 dropdown premium styling */
.select2-container--bootstrap-5 .select2-dropdown {
    border: none !important;
    border-radius: 14px !important;
    box-shadow:
        0 12px 40px rgba(0, 0, 0, 0.14),
        0 2px 12px rgba(4, 83, 203, 0.06) !important;
    overflow: hidden !important;
    margin-top: 6px !important;
    padding: 6px !important;
}

.select2-container--bootstrap-5 .select2-results__option {
    padding: 0.7rem 1rem !important;
    font-size: 0.88rem !important;
    border-radius: 8px !important;
    margin-bottom: 2px !important;
    transition: all 0.15s ease !important;
    color: #334155 !important;
    font-weight: 500 !important;
}

.select2-container--bootstrap-5 .select2-results__option--highlighted {
    background: linear-gradient(135deg, #0453cb, #1e6fe0) !important;
    color: white !important;
}

.select2-container--bootstrap-5 .select2-results__option--selected {
    background: #f0f4ff !important;
    color: #0453cb !important;
}

.select2-container--bootstrap-5 .select2-search--dropdown {
    padding: 6px 6px 4px !important;
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
    border: 2px solid #e2e8f0 !important;
    border-radius: 10px !important;
    padding: 0.6rem 0.85rem !important;
    font-size: 0.88rem !important;
    width: 100% !important;
    background: #fafbfd !important;
    transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field:focus {
    border-color: #0453cb !important;
    box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1) !important;
    background: white !important;
}

/* Select2 dropdown inside modal-content — needs to be above other modal elements */
#manageTeachersModal .select2-dropdown {
    z-index: 10 !important;
}
</style>
@endpush
