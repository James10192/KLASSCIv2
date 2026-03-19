@extends('layouts.app')

@section('title', 'Générer un bulletin LMD — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD Bulletin Select — Premium Wizard Redesign
       Prefix: bs- (bulletin-select)
       ══════════════════════════════════════════════ */

    .bs-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }

    /* ── Hero ── */
    .bs-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.75rem;
        overflow: hidden;
        animation: bs-fadeDown .5s ease-out;
    }
    .bs-hero::before {
        content: '';
        position: absolute;
        top: -60%;
        right: -10%;
        width: 420px;
        height: 420px;
        background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
        pointer-events: none;
    }
    .bs-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        position: relative;
        z-index: 1;
    }
    .bs-hero-left { display: flex; align-items: center; gap: 1rem; }
    .bs-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; border: 1px solid rgba(255,255,255,.15);
    }
    .bs-hero-info h1 {
        font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem;
        color: #fff; letter-spacing: -.02em;
    }
    .bs-hero-info p { margin: 0; opacity: .8; font-size: .88rem; }
    .bs-hero-crumbs {
        display: flex; align-items: center; gap: .4rem;
        margin-top: .4rem; font-size: .78rem; opacity: .7;
    }
    .bs-hero-crumbs a { color: #fff; text-decoration: underline; }
    .bs-hero-crumbs a:hover { opacity: 1; }
    .bs-hero-actions { position: relative; z-index: 1; }
    .bs-hero-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .55rem 1.1rem; border-radius: 10px; font-size: .84rem;
        font-weight: 600; border: 1.5px solid rgba(255,255,255,.3);
        color: #fff; background: rgba(255,255,255,.08);
        text-decoration: none; transition: all .2s; backdrop-filter: blur(4px);
    }
    .bs-hero-btn:hover { background: rgba(255,255,255,.18); color: #fff; text-decoration: none; }

    /* ── Wizard container ── */
    .bs-wizard {
        max-width: 780px;
        margin: 0 auto;
        animation: bs-fadeUp .5s ease-out .1s both;
    }

    /* ── Stepper ── */
    .bs-stepper {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        margin-bottom: 1.5rem;
        padding: 1rem 1.5rem;
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
    }
    .bs-step {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .6rem 1rem;
        border-radius: 10px;
        transition: all .3s;
        flex-shrink: 0;
    }
    .bs-step-num {
        width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .78rem; font-weight: 800;
        background: #e2e8f0; color: #94a3b8;
        transition: all .3s; flex-shrink: 0;
    }
    .bs-step-text { display: flex; flex-direction: column; }
    .bs-step-label {
        font-size: .82rem; font-weight: 600; color: #94a3b8;
        transition: color .3s;
    }
    .bs-step-hint {
        font-size: .68rem; color: #cbd5e1;
        transition: color .3s;
    }
    .bs-step-line {
        flex: 1; height: 2px; background: #e2e8f0;
        margin: 0 .5rem; min-width: 30px;
        transition: background .3s;
        border-radius: 1px;
    }

    /* Step states */
    .bs-step--active .bs-step-num {
        background: #0453cb; color: #fff;
        box-shadow: 0 0 0 4px rgba(4,83,203,.15);
    }
    .bs-step--active .bs-step-label { color: #1e293b; }
    .bs-step--active .bs-step-hint { color: #64748b; }

    .bs-step--done .bs-step-num {
        background: #10b981; color: #fff;
    }
    .bs-step--done .bs-step-label { color: #059669; }
    .bs-step--done .bs-step-hint { color: #10b981; }
    .bs-step-line--done { background: #10b981; }

    /* ── Card ── */
    .bs-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 8px 24px rgba(0,0,0,.04);
        overflow: hidden;
    }
    .bs-card-header {
        padding: 1.35rem 2rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .bs-card-header-icon {
        width: 40px; height: 40px; border-radius: 10px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem; flex-shrink: 0;
    }
    .bs-card-title {
        font-size: 1.08rem; font-weight: 700; color: #1e293b; margin: 0;
    }
    .bs-card-subtitle {
        font-size: .82rem; color: #94a3b8; margin: .1rem 0 0;
    }
    .bs-card-body { padding: 2rem; }

    /* ── Form fields ── */
    .bs-row { display: grid; gap: 1.25rem; margin-bottom: 1.25rem; }
    .bs-row--2 { grid-template-columns: 1fr 1fr; }
    .bs-row--1 { grid-template-columns: 1fr; }

    .bs-label {
        display: block;
        font-size: .72rem; font-weight: 700; color: #94a3b8;
        text-transform: uppercase; letter-spacing: .06em;
        margin-bottom: .35rem;
    }
    .bs-label .bs-req { color: #dc2626; }
    .bs-select {
        width: 100%; padding: .6rem 1rem;
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: .9rem; color: #1e293b;
        background: #f8fafc; transition: all .2s;
        appearance: auto;
    }
    .bs-select:focus {
        outline: none; border-color: #0453cb; background: #fff;
        box-shadow: 0 0 0 3px rgba(4,83,203,.08);
    }
    .bs-hint {
        font-size: .72rem; color: #94a3b8; margin-top: .3rem;
    }

    /* ── Semester chips ── */
    .bs-semester-grid {
        display: flex; flex-wrap: wrap; gap: .4rem;
    }
    .bs-sem-chip {
        display: inline-flex; align-items: center; justify-content: center;
        padding: .4rem .85rem; border-radius: 8px;
        font-size: .82rem; font-weight: 600;
        border: 1.5px solid #e2e8f0; background: #f8fafc;
        color: #64748b; cursor: pointer;
        transition: all .2s; user-select: none;
        min-width: 48px;
    }
    .bs-sem-chip:hover { border-color: #94b8e8; background: #f0f5ff; color: #0453cb; }
    .bs-sem-chip--active {
        border-color: #0453cb; background: #0453cb; color: #fff;
        box-shadow: 0 2px 8px rgba(4,83,203,.2);
    }
    .bs-sem-chip--active:hover {
        background: #0340a0; border-color: #0340a0; color: #fff;
    }
    .bs-sem-chip input { position: absolute; opacity: 0; pointer-events: none; }

    .bs-sem-group {
        display: flex; align-items: center; gap: .5rem;
    }
    .bs-sem-group-label {
        font-size: .68rem; font-weight: 700; color: #94a3b8;
        text-transform: uppercase; letter-spacing: .05em;
        white-space: nowrap;
        min-width: 24px;
    }
    .bs-sem-divider {
        width: 1px; height: 24px; background: #e2e8f0;
        margin: 0 .3rem;
    }

    /* ── Target radio cards ── */
    .bs-target-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem; }
    .bs-target-card {
        position: relative;
        border: 2px solid #e8ecf1; border-radius: 14px;
        padding: 1.5rem 1.25rem; cursor: pointer;
        transition: all .25s; text-align: center;
        background: #fff;
    }
    .bs-target-card:hover { border-color: #b4ccea; background: #fafcff; }
    .bs-target-card--active {
        border-color: #0453cb; background: #f0f5ff;
        box-shadow: 0 0 0 3px rgba(4,83,203,.08);
    }
    .bs-target-card input { position: absolute; opacity: 0; pointer-events: none; }
    .bs-target-icon {
        width: 52px; height: 52px; border-radius: 14px;
        margin: 0 auto .85rem; display: flex;
        align-items: center; justify-content: center;
        font-size: 1.3rem; background: #f1f5f9; color: #64748b;
        transition: all .25s;
    }
    .bs-target-card--active .bs-target-icon {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff; box-shadow: 0 4px 12px rgba(4,83,203,.2);
    }
    .bs-target-title {
        font-size: .95rem; font-weight: 700; color: #1e293b;
        margin-bottom: .2rem;
    }
    .bs-target-desc { font-size: .8rem; color: #94a3b8; line-height: 1.4; }

    /* ── Student field (animated) ── */
    .bs-student-wrap {
        overflow: hidden;
        max-height: 0; opacity: 0;
        transition: max-height .35s ease, opacity .3s ease, margin .3s ease;
        margin-bottom: 0;
    }
    .bs-student-wrap--visible {
        max-height: 180px; opacity: 1; margin-bottom: 1.25rem;
    }

    /* ── Summary bar ── */
    .bs-summary {
        display: flex; align-items: center; gap: .75rem;
        padding: .85rem 1.25rem; margin-top: 1.5rem;
        background: #f8fafc; border: 1px solid #e8ecf1;
        border-radius: 10px; flex-wrap: wrap;
    }
    .bs-summary-label {
        font-size: .72rem; font-weight: 700; color: #94a3b8;
        text-transform: uppercase; letter-spacing: .06em;
        white-space: nowrap;
    }
    .bs-chip {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .25rem .65rem; border-radius: 7px;
        font-size: .78rem; font-weight: 600;
        background: #eff6ff; color: #0453cb;
        border: 1px solid #bfdbfe;
    }
    .bs-chip i { font-size: .68rem; opacity: .7; }
    .bs-chip--empty { background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; }

    /* ── Footer ── */
    .bs-footer {
        padding: 1.25rem 2rem;
        border-top: 1px solid #f1f5f9;
        display: flex; justify-content: space-between;
        align-items: center; flex-wrap: wrap; gap: .75rem;
    }
    .bs-btn {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .65rem 1.5rem; border-radius: 10px;
        font-size: .88rem; font-weight: 600; border: none;
        cursor: pointer; transition: all .2s; text-decoration: none;
    }
    .bs-btn--cancel {
        background: #f1f5f9; color: #64748b;
    }
    .bs-btn--cancel:hover { background: #e2e8f0; color: #1e293b; text-decoration: none; }
    .bs-btn--primary {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff; box-shadow: 0 2px 8px rgba(4,83,203,.2);
    }
    .bs-btn--primary:hover {
        box-shadow: 0 4px 16px rgba(4,83,203,.3);
        transform: translateY(-1px);
    }
    .bs-btn--primary:disabled {
        background: #cbd5e1; color: #fff;
        box-shadow: none; transform: none; cursor: not-allowed;
    }
    .bs-btn--primary i { transition: transform .2s; }
    .bs-btn--primary:not(:disabled):hover i { transform: translateX(2px); }

    /* ── Animations ── */
    @keyframes bs-fadeDown {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes bs-fadeUp {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .bs-hero { padding: 1.5rem; border-radius: 14px; }
        .bs-hero-top { flex-direction: column; }
        .bs-stepper { flex-direction: column; gap: .35rem; align-items: stretch; }
        .bs-step-line { display: none; }
        .bs-card-body { padding: 1.5rem; }
        .bs-row--2 { grid-template-columns: 1fr; }
        .bs-target-grid { grid-template-columns: 1fr; }
        .bs-footer { padding: 1rem 1.5rem; }
    }
</style>
@endpush

@section('content')
<div class="bs-page" x-data="lmdBulletinSelect()">
    <div class="main-content">

        {{-- ══ Hero ══ --}}
        <div class="bs-hero">
            <div class="bs-hero-top">
                <div class="bs-hero-left">
                    <div class="bs-hero-icon"><i class="fas fa-file-export"></i></div>
                    <div class="bs-hero-info">
                        <h1>Générer un bulletin LMD</h1>
                        <p>Sélectionnez les paramètres pour lancer la génération</p>
                        <div class="bs-hero-crumbs">
                            <a href="{{ route('esbtp.lmd.bulletins.index') }}">Bulletins LMD</a>
                            <i class="fas fa-chevron-right" style="font-size:.55rem;"></i>
                            <span>Générer</span>
                        </div>
                    </div>
                </div>
                <div class="bs-hero-actions">
                    <a href="{{ route('esbtp.lmd.bulletins.index') }}" class="bs-hero-btn">
                        <i class="fas fa-arrow-left"></i>Retour
                    </a>
                </div>
            </div>
        </div>

        {{-- Flash messages --}}
        @foreach(['success' => 'check-circle', 'error' => 'exclamation-circle'] as $type => $icon)
            @if(session($type))
                <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show" role="alert" style="border-radius:10px; max-width:780px; margin:0 auto 1rem;">
                    <i class="fas fa-{{ $icon }} me-2"></i>{{ session($type) }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        @endforeach
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:10px; max-width:780px; margin:0 auto 1rem;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erreurs de validation :</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="bs-wizard">

            {{-- ══ Stepper ══ --}}
            <div class="bs-stepper">
                {{-- Step 1: Paramètres --}}
                <div class="bs-step"
                     :class="(classeId && anneeId && semestre) ? 'bs-step--done' : ((classeId || anneeId || semestre) ? 'bs-step--active' : '')">
                    <div class="bs-step-num">
                        <template x-if="classeId && anneeId && semestre"><i class="fas fa-check" style="font-size:.7rem;"></i></template>
                        <template x-if="!(classeId && anneeId && semestre)"><span>1</span></template>
                    </div>
                    <div class="bs-step-text">
                        <span class="bs-step-label">Paramètres</span>
                        <span class="bs-step-hint">Classe, année, semestre</span>
                    </div>
                </div>

                <div class="bs-step-line" :class="(classeId && anneeId && semestre) ? 'bs-step-line--done' : ''"></div>

                {{-- Step 2: Cible --}}
                <div class="bs-step"
                     :class="(classeId && anneeId && semestre && (mode === 'classe' || (mode === 'etudiant' && etudiantId))) ? 'bs-step--done' : ((classeId && anneeId && semestre) ? 'bs-step--active' : '')">
                    <div class="bs-step-num">
                        <template x-if="classeId && anneeId && semestre && (mode === 'classe' || (mode === 'etudiant' && etudiantId))"><i class="fas fa-check" style="font-size:.7rem;"></i></template>
                        <template x-if="!(classeId && anneeId && semestre && (mode === 'classe' || (mode === 'etudiant' && etudiantId)))"><span>2</span></template>
                    </div>
                    <div class="bs-step-text">
                        <span class="bs-step-label">Cible</span>
                        <span class="bs-step-hint">Étudiant ou classe</span>
                    </div>
                </div>

                <div class="bs-step-line" :class="(classeId && anneeId && semestre && (mode === 'classe' || (mode === 'etudiant' && etudiantId))) ? 'bs-step-line--done' : ''"></div>

                {{-- Step 3: Confirmer --}}
                <div class="bs-step"
                     :class="(classeId && anneeId && semestre && (mode === 'classe' || (mode === 'etudiant' && etudiantId))) ? 'bs-step--active' : ''">
                    <div class="bs-step-num"><span>3</span></div>
                    <div class="bs-step-text">
                        <span class="bs-step-label">Confirmer</span>
                        <span class="bs-step-hint">Lancer la génération</span>
                    </div>
                </div>
            </div>

            {{-- ══ Card ══ --}}
            <div class="bs-card">
                <div class="bs-card-header">
                    <div class="bs-card-header-icon"><i class="fas fa-cog"></i></div>
                    <div>
                        <div class="bs-card-title">Paramètres de génération</div>
                        <div class="bs-card-subtitle">Remplissez les champs puis lancez la génération</div>
                    </div>
                </div>

                <div class="bs-card-body">

                    {{-- Row 1: Classe + Année --}}
                    <div class="bs-row bs-row--2">
                        <div class="bs-field">
                            <label class="bs-label">Classe <span class="bs-req">*</span></label>
                            <select class="bs-select" x-model="classeId" @change="onClasseChange()" required>
                                <option value="">— Sélectionner —</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">
                                        {{ $c->name }}@if($c->filiere) ({{ $c->filiere->name }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bs-field">
                            <label class="bs-label">Année universitaire <span class="bs-req">*</span></label>
                            <select class="bs-select" x-model="anneeId" required>
                                <option value="">— Sélectionner —</option>
                                @foreach($annees as $annee)
                                    <option value="{{ $annee->id }}" {{ ($annee->is_current ?? false) ? 'selected' : '' }}>
                                        {{ $annee->name ?? $annee->libelle ?? $annee->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Row 2: Semestre chips --}}
                    <div class="bs-row bs-row--1">
                        <div class="bs-field">
                            <label class="bs-label">Semestre <span class="bs-req">*</span></label>
                            <div class="bs-semester-grid">
                                <div class="bs-sem-group">
                                    <span class="bs-sem-group-label">L1</span>
                                    @for($s = 1; $s <= 2; $s++)
                                        <label class="bs-sem-chip" :class="semestre == '{{ $s }}' ? 'bs-sem-chip--active' : ''" @click="semestre = '{{ $s }}'">
                                            <input type="radio" name="sem_visual" value="{{ $s }}" x-model="semestre">
                                            S{{ $s }}
                                        </label>
                                    @endfor
                                </div>
                                <div class="bs-sem-divider"></div>
                                <div class="bs-sem-group">
                                    <span class="bs-sem-group-label">L2</span>
                                    @for($s = 3; $s <= 4; $s++)
                                        <label class="bs-sem-chip" :class="semestre == '{{ $s }}' ? 'bs-sem-chip--active' : ''" @click="semestre = '{{ $s }}'">
                                            <input type="radio" name="sem_visual" value="{{ $s }}" x-model="semestre">
                                            S{{ $s }}
                                        </label>
                                    @endfor
                                </div>
                                <div class="bs-sem-divider"></div>
                                <div class="bs-sem-group">
                                    <span class="bs-sem-group-label">L3</span>
                                    @for($s = 5; $s <= 6; $s++)
                                        <label class="bs-sem-chip" :class="semestre == '{{ $s }}' ? 'bs-sem-chip--active' : ''" @click="semestre = '{{ $s }}'">
                                            <input type="radio" name="sem_visual" value="{{ $s }}" x-model="semestre">
                                            S{{ $s }}
                                        </label>
                                    @endfor
                                </div>
                                <div class="bs-sem-divider"></div>
                                <div class="bs-sem-group">
                                    <span class="bs-sem-group-label">M1</span>
                                    @for($s = 7; $s <= 8; $s++)
                                        <label class="bs-sem-chip" :class="semestre == '{{ $s }}' ? 'bs-sem-chip--active' : ''" @click="semestre = '{{ $s }}'">
                                            <input type="radio" name="sem_visual" value="{{ $s }}" x-model="semestre">
                                            S{{ $s }}
                                        </label>
                                    @endfor
                                </div>
                                <div class="bs-sem-divider"></div>
                                <div class="bs-sem-group">
                                    <span class="bs-sem-group-label">M2</span>
                                    @for($s = 9; $s <= 10; $s++)
                                        <label class="bs-sem-chip" :class="semestre == '{{ $s }}' ? 'bs-sem-chip--active' : ''" @click="semestre = '{{ $s }}'">
                                            <input type="radio" name="sem_visual" value="{{ $s }}" x-model="semestre">
                                            S{{ $s }}
                                        </label>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Row 3: Target mode --}}
                    <div class="bs-row bs-row--1">
                        <div class="bs-field">
                            <label class="bs-label">Cible de génération</label>
                            <div class="bs-target-grid">
                                <label class="bs-target-card" :class="mode === 'classe' ? 'bs-target-card--active' : ''" @click="mode = 'classe'">
                                    <input type="radio" name="generation_mode" value="classe" x-model="mode">
                                    <div class="bs-target-icon"><i class="fas fa-users"></i></div>
                                    <div class="bs-target-title">Toute la classe</div>
                                    <div class="bs-target-desc">Générer les bulletins de tous les étudiants actifs de la classe</div>
                                </label>
                                <label class="bs-target-card" :class="mode === 'etudiant' ? 'bs-target-card--active' : ''" @click="mode = 'etudiant'">
                                    <input type="radio" name="generation_mode" value="etudiant" x-model="mode">
                                    <div class="bs-target-icon"><i class="fas fa-user"></i></div>
                                    <div class="bs-target-title">Un étudiant</div>
                                    <div class="bs-target-desc">Générer le bulletin d'un seul étudiant de la classe</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Student select (conditional) --}}
                    <div class="bs-student-wrap" :class="mode === 'etudiant' ? 'bs-student-wrap--visible' : ''">
                        <div class="bs-field">
                            <label class="bs-label">Étudiant <span class="bs-req">*</span></label>
                            <select class="bs-select" x-ref="etudiantSelect"
                                    :required="mode === 'etudiant'" :disabled="mode !== 'etudiant'"
                                    @change="etudiantId = $event.target.value">
                                <option value="">— Sélectionner un étudiant —</option>
                            </select>
                            <div class="bs-hint" x-show="loading">
                                <i class="fas fa-spinner fa-spin me-1"></i>Chargement des étudiants...
                            </div>
                            <div class="bs-hint" x-show="!loading && classeId && etudiants.length === 0 && mode === 'etudiant'">
                                Aucun étudiant inscrit dans cette classe.
                            </div>
                        </div>
                    </div>

                    {{-- Summary bar --}}
                    <div class="bs-summary">
                        <span class="bs-summary-label">Résumé</span>

                        <template x-if="classeId">
                            <span class="bs-chip">
                                <i class="fas fa-layer-group"></i>
                                <span x-text="document.querySelector('[x-model=classeId] option:checked')?.textContent?.trim() || '...'"></span>
                            </span>
                        </template>
                        <template x-if="!classeId">
                            <span class="bs-chip bs-chip--empty">Classe ?</span>
                        </template>

                        <template x-if="semestre">
                            <span class="bs-chip"><i class="fas fa-calendar"></i> S<span x-text="semestre"></span></span>
                        </template>
                        <template x-if="!semestre">
                            <span class="bs-chip bs-chip--empty">Semestre ?</span>
                        </template>

                        <template x-if="mode === 'classe'">
                            <span class="bs-chip"><i class="fas fa-users"></i> Toute la classe</span>
                        </template>
                        <template x-if="mode === 'etudiant' && etudiantId">
                            <span class="bs-chip"><i class="fas fa-user"></i> 1 étudiant</span>
                        </template>
                        <template x-if="mode === 'etudiant' && !etudiantId">
                            <span class="bs-chip bs-chip--empty">Étudiant ?</span>
                        </template>
                    </div>

                </div>

                {{-- ══ Footer ══ --}}
                <div class="bs-footer">
                    <a href="{{ route('esbtp.lmd.bulletins.index') }}" class="bs-btn bs-btn--cancel">
                        <i class="fas fa-arrow-left"></i>Annuler
                    </a>

                    <div>
                        {{-- Form classe --}}
                        <form x-show="mode === 'classe'" action="{{ route('esbtp.lmd.bulletins.generer-classe') }}" method="POST" style="display:inline;">
                            @csrf
                            <input type="hidden" name="classe_id" :value="classeId">
                            <input type="hidden" name="annee_universitaire_id" :value="anneeId">
                            <input type="hidden" name="semestre" :value="semestre">
                            <button type="submit" class="bs-btn bs-btn--primary"
                                    :disabled="!classeId || !anneeId || !semestre">
                                <i class="fas fa-file-export"></i>Générer les bulletins
                            </button>
                        </form>

                        {{-- Form etudiant --}}
                        <form x-show="mode === 'etudiant'" action="{{ route('esbtp.lmd.bulletins.generer') }}" method="POST" style="display:inline;">
                            @csrf
                            <input type="hidden" name="classe_id" :value="classeId">
                            <input type="hidden" name="annee_universitaire_id" :value="anneeId">
                            <input type="hidden" name="semestre" :value="semestre">
                            <input type="hidden" name="etudiant_id" :value="etudiantId">
                            <button type="submit" class="bs-btn bs-btn--primary"
                                    :disabled="!classeId || !anneeId || !semestre || !etudiantId">
                                <i class="fas fa-user-check"></i>Générer le bulletin
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function lmdBulletinSelect() {
    return {
        mode: 'classe',
        classeId: '',
        anneeId: '{{ $annees->where("is_current", true)->first()?->id ?? "" }}',
        semestre: '',
        etudiantId: '',
        etudiants: [],
        loading: false,

        onClasseChange() {
            this.etudiants = [];
            this.etudiantId = '';
            if (!this.classeId) return;
            this.fetchEtudiants();
        },

        async fetchEtudiants() {
            this.loading = true;
            try {
                const resp = await fetch(`/esbtp/classes/${this.classeId}/etudiants`);
                const data = await resp.json();
                this.etudiants = data.etudiants || [];
                this.populateStudentSelect();
            } catch (err) {
                console.error('Erreur chargement étudiants:', err);
            } finally {
                this.loading = false;
            }
        },

        populateStudentSelect() {
            const sel = this.$refs.etudiantSelect;
            if (!sel) return;
            // Remove all options except the first placeholder
            while (sel.options.length > 1) sel.remove(1);
            // Add student options
            this.etudiants.forEach(etu => {
                const opt = document.createElement('option');
                opt.value = etu.id;
                opt.textContent = etu.matricule + ' — ' + etu.nom + ' ' + (etu.prenoms || etu.prenom || '');
                sel.appendChild(opt);
            });
        },

        init() {
            this.$watch('mode', (val) => {
                if (val === 'etudiant' && this.classeId && this.etudiants.length === 0) {
                    this.fetchEtudiants();
                }
            });
        }
    };
}
</script>
@endpush
