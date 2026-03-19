@extends('layouts.app')

@section('title', 'Configuration des Bulletins - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════════
       Bulletin Configuration — Style Secrétaire Dashboard
       Prefix: cfg-
       ══════════════════════════════════════════════════ */

    body { background-color: var(--background); }

    /* ── Header (same as sec-header) ── */
    .cfg-header {
        background: linear-gradient(135deg, var(--primary) 0%, #5e91de 100%);
        color: #fff;
        border-radius: var(--radius-medium);
        padding: var(--space-xl) var(--space-lg);
        margin-bottom: var(--space-lg);
        position: relative;
        overflow: hidden;
    }
    .cfg-header::before {
        content: '';
        position: absolute;
        top: -40%; right: -10%;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .cfg-header-inner {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: var(--space-md); position: relative; z-index: 1;
    }
    .cfg-header-left { display: flex; align-items: center; gap: var(--space-lg); }
    .cfg-avatar {
        width: 64px; height: 64px; border-radius: var(--radius-circle);
        background: rgba(255,255,255,0.15); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem; color: #fff; flex-shrink: 0;
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    .cfg-header h1 { color: #fff; margin: 0; font-size: 1.4rem; font-weight: 700; }
    .cfg-header .header-sub { color: rgba(255,255,255,0.8); margin: 4px 0 0; font-size: 0.88rem; }
    .cfg-header-actions { display: flex; align-items: center; gap: var(--space-sm); }
    .cfg-header-btn {
        background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3);
        color: #fff; border-radius: var(--radius-small);
        padding: 7px 14px; font-size: 0.82rem; font-weight: 600;
        cursor: pointer; transition: all 0.2s; backdrop-filter: blur(4px);
        text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .cfg-header-btn:hover { background: rgba(255,255,255,0.3); color: #fff; text-decoration: none; }

    /* ── KPI Summary (same as sec-kpi-grid) ── */
    .cfg-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }
    .cfg-kpi {
        border-radius: var(--radius-medium);
        padding: var(--space-lg) var(--space-md);
        text-align: center;
        color: #fff;
        position: relative;
        overflow: hidden;
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s;
    }
    .cfg-kpi:hover { transform: translateY(-4px); }
    .cfg-kpi::after {
        content: '';
        position: absolute; top: -30%; right: -20%;
        width: 120px; height: 120px;
        background: rgba(255,255,255,0.08); border-radius: 50%;
        pointer-events: none;
    }
    .cfg-kpi-icon { font-size: 1.5rem; margin-bottom: var(--space-sm); opacity: 0.9; }
    .cfg-kpi-value { font-size: 1.6rem; font-weight: 800; line-height: 1.1; margin-bottom: 4px; }
    .cfg-kpi-label {
        font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.8px;
        opacity: 0.85; font-weight: 600;
    }
    .cfg-kpi--primary { background: linear-gradient(135deg, var(--primary), #3b7ddb); box-shadow: 0 4px 16px rgba(4,83,203,0.25); }
    .cfg-kpi--primary:hover { box-shadow: 0 8px 28px rgba(4,83,203,0.35); }
    .cfg-kpi--success { background: linear-gradient(135deg, var(--success), #34d399); box-shadow: 0 4px 16px rgba(16,185,129,0.25); }
    .cfg-kpi--success:hover { box-shadow: 0 8px 28px rgba(16,185,129,0.35); }
    .cfg-kpi--cyan { background: linear-gradient(135deg, #0891b2, #06b6d4); box-shadow: 0 4px 16px rgba(6,182,212,0.25); }
    .cfg-kpi--cyan:hover { box-shadow: 0 8px 28px rgba(6,182,212,0.35); }
    .cfg-kpi--neutral { background: linear-gradient(135deg, #4b5563, #6b7280); box-shadow: 0 4px 16px rgba(107,114,128,0.25); }
    .cfg-kpi--neutral:hover { box-shadow: 0 8px 28px rgba(107,114,128,0.35); }

    /* ── Section cards (same as sec-card) ── */
    .cfg-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        overflow: hidden;
        transition: box-shadow 0.2s;
        margin-bottom: var(--space-lg);
    }
    .cfg-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .cfg-card-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: var(--space-lg) var(--space-lg) var(--space-md);
    }
    .cfg-card-title {
        font-size: 0.9rem; font-weight: 700; color: var(--text-primary);
        display: flex; align-items: center; gap: var(--space-sm);
    }
    .cfg-card-title i { color: var(--primary); font-size: 1rem; }
    .cfg-card-badge {
        font-size: 0.7rem; font-weight: 600; color: var(--text-secondary);
        background: var(--background); padding: 3px 10px; border-radius: 20px;
    }
    .cfg-card-body { padding: 0 var(--space-lg) var(--space-lg); }

    /* ── Form fields ── */
    .cfg-label {
        display: block; font-size: 0.72rem; font-weight: 700; color: #94a3b8;
        text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.3rem;
    }
    .cfg-input {
        width: 100%; padding: 0.55rem 0.85rem;
        border: 1.5px solid #e2e8f0; border-radius: 8px;
        font-size: 0.88rem; color: var(--text-primary);
        background: var(--background); transition: all 0.2s;
    }
    .cfg-input:focus {
        outline: none; border-color: var(--primary); background: var(--surface);
        box-shadow: 0 0 0 3px rgba(4,83,203,0.08);
    }

    /* ── Toggle grid (premium switches) ── */
    .cfg-toggles {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 0.5rem;
    }
    .cfg-toggle {
        display: flex; align-items: center; gap: 0.65rem;
        padding: 0.65rem 0.85rem; border-radius: 8px;
        background: var(--background); border: 1px solid var(--border);
        transition: all 0.2s; cursor: pointer;
    }
    .cfg-toggle:hover { background: rgba(4,83,203,0.03); border-color: rgba(4,83,203,0.15); }
    .cfg-toggle-label {
        font-size: 0.84rem; font-weight: 500; color: var(--text-primary);
        flex: 1; cursor: pointer;
    }
    .cfg-toggle .form-check-input {
        width: 2.2em; height: 1.15em; cursor: pointer;
        flex-shrink: 0; margin: 0;
    }
    .cfg-toggle .form-check-input:checked {
        background-color: var(--primary); border-color: var(--primary);
    }

    /* ── Sticky footer ── */
    .cfg-footer {
        display: flex; justify-content: flex-end; gap: 0.75rem;
        padding: var(--space-lg) 0;
        position: sticky; bottom: 0;
        background: var(--background);
        border-top: 1px solid var(--border);
        margin-top: var(--space-sm);
        z-index: 10;
    }
    .cfg-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 0.6rem 1.4rem; border-radius: 8px;
        font-size: 0.85rem; font-weight: 600; border: none;
        cursor: pointer; transition: all 0.2s; text-decoration: none;
    }
    .cfg-btn--cancel { background: var(--surface); color: var(--text-secondary); border: 1px solid var(--border); }
    .cfg-btn--cancel:hover { background: var(--background); color: var(--text-primary); text-decoration: none; }
    .cfg-btn--save {
        background: linear-gradient(135deg, var(--primary), #3b7ddb);
        color: #fff; box-shadow: 0 4px 16px rgba(4,83,203,0.25);
    }
    .cfg-btn--save:hover { box-shadow: 0 8px 28px rgba(4,83,203,0.35); transform: translateY(-1px); }

    @media (max-width: 768px) {
        .cfg-header { padding: var(--space-lg); }
        .cfg-header-inner { flex-direction: column; align-items: flex-start; }
        .cfg-kpi-grid { grid-template-columns: 1fr 1fr; }
        .cfg-toggles { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ══ Header (style secrétaire) ══ --}}
        @php
            $totalSettings = count($settings ?? []);
            $toggleCount = collect($settings ?? [])->filter(fn($v, $k) => str_starts_with($k, 'bulletin_show_'))->count();
            $enabledCount = collect($settings ?? [])->filter(fn($v, $k) => str_starts_with($k, 'bulletin_show_') && $v == '1')->count();
        @endphp

        <div class="cfg-header">
            <div class="cfg-header-inner">
                <div class="cfg-header-left">
                    <div class="cfg-avatar"><i class="fas fa-cogs"></i></div>
                    <div>
                        <h1>Configuration des Bulletins</h1>
                        <p class="header-sub">Personnalisez l'apparence et le contenu des bulletins de notes</p>
                    </div>
                </div>
                <div class="cfg-header-actions">
                    <a href="{{ route('esbtp.resultats.index') }}" class="cfg-header-btn">
                        <i class="fas fa-arrow-left"></i>Retour aux résultats
                    </a>
                </div>
            </div>
        </div>

        {{-- ══ KPI Summary ══ --}}
        <div class="cfg-kpi-grid">
            <div class="cfg-kpi cfg-kpi--primary">
                <div class="cfg-kpi-icon"><i class="fas fa-sliders-h"></i></div>
                <div class="cfg-kpi-value">{{ $totalSettings }}</div>
                <div class="cfg-kpi-label">Paramètres</div>
            </div>
            <div class="cfg-kpi cfg-kpi--success">
                <div class="cfg-kpi-icon"><i class="fas fa-toggle-on"></i></div>
                <div class="cfg-kpi-value">{{ $enabledCount }}/{{ $toggleCount }}</div>
                <div class="cfg-kpi-label">Options activées</div>
            </div>
            <div class="cfg-kpi cfg-kpi--cyan">
                <div class="cfg-kpi-icon"><i class="fas fa-file-alt"></i></div>
                <div class="cfg-kpi-value">7</div>
                <div class="cfg-kpi-label">Sections</div>
            </div>
            <div class="cfg-kpi cfg-kpi--neutral">
                <div class="cfg-kpi-icon"><i class="fas fa-font"></i></div>
                <div class="cfg-kpi-value">{{ $settings['bulletin_font_size'] ?? '11' }}pt</div>
                <div class="cfg-kpi-label">Taille police</div>
            </div>
        </div>

        {{-- Flash --}}
        @foreach(['success' => 'check-circle', 'error' => 'exclamation-circle'] as $type => $icon)
            @if(session($type))
                <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show" role="alert" style="border-radius:var(--radius-small);">
                    <i class="fas fa-{{ $icon }} me-2"></i>{{ session($type) }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        @endforeach

        {{-- ══ Tabs BTS / LMD ══ --}}
        <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 1.25rem; border-bottom: 2px solid #e2e8f0;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-bts" data-bs-toggle="tab" data-bs-target="#pane-bts" type="button" role="tab" style="font-weight:600; font-size:.92rem;">
                    <i class="fas fa-building me-1"></i> Bulletin BTS
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-lmd" data-bs-toggle="tab" data-bs-target="#pane-lmd" type="button" role="tab" style="font-weight:600; font-size:.92rem;">
                    <i class="fas fa-graduation-cap me-1"></i> Bulletin LMD
                </button>
            </li>
        </ul>

        <form method="POST" action="{{ route('esbtp.bulletins.save-configuration') }}">
            @csrf

            <div class="tab-content">
            {{-- ══════════════════════════════════════════
                 TAB BTS
                 ══════════════════════════════════════════ --}}
            <div class="tab-pane fade show active" id="pane-bts" role="tabpanel">

            {{-- ══ 1. Établissement ══ --}}
            <div class="cfg-card">
                <div class="cfg-card-header">
                    <div class="cfg-card-title"><i class="fas fa-university"></i>Informations de l'établissement</div>
                    <span class="cfg-card-badge">6 champs</span>
                </div>
                <div class="cfg-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="cfg-label">Nom de l'école</label>
                            <input type="text" class="cfg-input" name="school_name" value="{{ $settings['school_name'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="cfg-label">Nom personnalisé pour bulletin</label>
                            <input type="text" class="cfg-input" name="bulletin_school_name_custom" value="{{ $settings['bulletin_school_name_custom'] ?? '' }}" placeholder="Vide = nom par défaut">
                        </div>
                        <div class="col-md-8">
                            <label class="cfg-label">Adresse</label>
                            <input type="text" class="cfg-input" name="school_address" value="{{ $settings['school_address'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="cfg-label">Téléphone</label>
                            <input type="text" class="cfg-input" name="school_phone" value="{{ $settings['school_phone'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="cfg-label">Email</label>
                            <input type="email" class="cfg-input" name="school_email" value="{{ $settings['school_email'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="cfg-label">Nom du directeur</label>
                            <input type="text" class="cfg-input" name="director_name" value="{{ $settings['director_name'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="cfg-label">Titre du directeur</label>
                            <input type="text" class="cfg-input" name="director_title" value="{{ $settings['director_title'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ 2. En-tête officielle ══ --}}
            <div class="cfg-card">
                <div class="cfg-card-header">
                    <div class="cfg-card-title"><i class="fas fa-flag"></i>En-tête et informations officielles</div>
                    <span class="cfg-card-badge">4 toggles + 3 champs</span>
                </div>
                <div class="cfg-card-body">
                    <div class="cfg-toggles" style="margin-bottom:1rem;">
                        <label class="cfg-toggle" for="bulletin_show_header">
                            <span class="cfg-toggle-label">En-tête complet</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_header" name="bulletin_show_header" value="1" {{ ($settings['bulletin_show_header'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_logo">
                            <span class="cfg-toggle-label">Logo</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_logo" name="bulletin_show_logo" value="1" {{ ($settings['bulletin_show_logo'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_republic_info">
                            <span class="cfg-toggle-label">Informations République</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_republic_info" name="bulletin_show_republic_info" value="1" {{ ($settings['bulletin_show_republic_info'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_ministry_info">
                            <span class="cfg-toggle-label">Informations Ministère</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_ministry_info" name="bulletin_show_ministry_info" value="1" {{ ($settings['bulletin_show_ministry_info'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="cfg-label">Texte République</label>
                            <input type="text" class="cfg-input" name="bulletin_republic_text" value="{{ $settings['bulletin_republic_text'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="cfg-label">Devise nationale</label>
                            <input type="text" class="cfg-input" name="bulletin_union_text" value="{{ $settings['bulletin_union_text'] ?? '' }}">
                        </div>
                        <div class="col-12">
                            <label class="cfg-label">Texte Ministère</label>
                            <input type="text" class="cfg-input" name="bulletin_ministry_text" value="{{ $settings['bulletin_ministry_text'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ 3. Cycle ══ --}}
            <div class="cfg-card">
                <div class="cfg-card-header">
                    <div class="cfg-card-title"><i class="fas fa-graduation-cap"></i>Cycle et formation</div>
                </div>
                <div class="cfg-card-body">
                    <div class="cfg-toggles" style="margin-bottom:1rem;">
                        <label class="cfg-toggle" for="bulletin_show_cycle_info">
                            <span class="cfg-toggle-label">Afficher les informations du cycle</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_cycle_info" name="bulletin_show_cycle_info" value="1" {{ ($settings['bulletin_show_cycle_info'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="cfg-label">Nom du cycle</label>
                            <input type="text" class="cfg-input" name="bulletin_cycle_text" value="{{ $settings['bulletin_cycle_text'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="cfg-label">Abréviation</label>
                            <input type="text" class="cfg-input" name="bulletin_cycle_abbreviation" value="{{ $settings['bulletin_cycle_abbreviation'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ 4. Tableau des matières ══ --}}
            <div class="cfg-card">
                <div class="cfg-card-header">
                    <div class="cfg-card-title"><i class="fas fa-table"></i>Tableau des matières et notes</div>
                    <span class="cfg-card-badge">5 toggles</span>
                </div>
                <div class="cfg-card-body">
                    <div class="cfg-toggles">
                        <label class="cfg-toggle" for="bulletin_show_subjects_table">
                            <span class="cfg-toggle-label">Tableau des matières</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_subjects_table" name="bulletin_show_subjects_table" value="1" {{ ($settings['bulletin_show_subjects_table'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_subject_average">
                            <span class="cfg-toggle-label">Moyennes par matière</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_subject_average" name="bulletin_show_subject_average" value="1" {{ ($settings['bulletin_show_subject_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_coefficient">
                            <span class="cfg-toggle-label">Coefficients</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_coefficient" name="bulletin_show_coefficient" value="1" {{ ($settings['bulletin_show_coefficient'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_teachers">
                            <span class="cfg-toggle-label">Professeurs</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_teachers" name="bulletin_show_teachers" value="1" {{ ($settings['bulletin_show_teachers'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_appreciations">
                            <span class="cfg-toggle-label">Appréciations</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_appreciations" name="bulletin_show_appreciations" value="1" {{ ($settings['bulletin_show_appreciations'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ══ 5. Moyennes et statistiques ══ --}}
            <div class="cfg-card">
                <div class="cfg-card-header">
                    <div class="cfg-card-title"><i class="fas fa-chart-bar"></i>Moyennes et statistiques</div>
                    <span class="cfg-card-badge">10 toggles</span>
                </div>
                <div class="cfg-card-body">
                    <div class="cfg-toggles">
                        <label class="cfg-toggle" for="bulletin_show_general_average">
                            <span class="cfg-toggle-label">Moyenne générale</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_general_average" name="bulletin_show_general_average" value="1" {{ ($settings['bulletin_show_general_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_technical_average">
                            <span class="cfg-toggle-label">Moyenne technique</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_technical_average" name="bulletin_show_technical_average" value="1" {{ ($settings['bulletin_show_technical_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_class_rank">
                            <span class="cfg-toggle-label">Rang de classe</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_class_rank" name="bulletin_show_class_rank" value="1" {{ ($settings['bulletin_show_class_rank'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_class_size">
                            <span class="cfg-toggle-label">Effectif de classe</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_class_size" name="bulletin_show_class_size" value="1" {{ ($settings['bulletin_show_class_size'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_attendance">
                            <span class="cfg-toggle-label">Informations d'assiduité</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_attendance" name="bulletin_show_attendance" value="1" {{ ($settings['bulletin_show_attendance'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_attendance_note">
                            <span class="cfg-toggle-label">Note d'assiduité (bonus/malus)</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_attendance_note" name="bulletin_show_attendance_note" value="1" {{ ($settings['bulletin_show_attendance_note'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_highest_average">
                            <span class="cfg-toggle-label">Plus forte moyenne</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_highest_average" name="bulletin_show_highest_average" value="1" {{ ($settings['bulletin_show_highest_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_lowest_average">
                            <span class="cfg-toggle-label">Plus faible moyenne</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_lowest_average" name="bulletin_show_lowest_average" value="1" {{ ($settings['bulletin_show_lowest_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_class_average">
                            <span class="cfg-toggle-label">Moyenne de classe</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_class_average" name="bulletin_show_class_average" value="1" {{ ($settings['bulletin_show_class_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_council_decision">
                            <span class="cfg-toggle-label">Décision du conseil de classe</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_council_decision" name="bulletin_show_council_decision" value="1" {{ ($settings['bulletin_show_council_decision'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ══ 6. Signatures ══ --}}
            <div class="cfg-card">
                <div class="cfg-card-header">
                    <div class="cfg-card-title"><i class="fas fa-signature"></i>Signatures et validation</div>
                </div>
                <div class="cfg-card-body">
                    <div class="cfg-toggles">
                        <label class="cfg-toggle" for="bulletin_show_signatures">
                            <span class="cfg-toggle-label">Section signatures</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_signatures" name="bulletin_show_signatures" value="1" {{ ($settings['bulletin_show_signatures'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="cfg-toggle" for="bulletin_show_director_signature">
                            <span class="cfg-toggle-label">Signature du directeur</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_director_signature" name="bulletin_show_director_signature" value="1" {{ ($settings['bulletin_show_director_signature'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ══ 7. Apparence ══ --}}
            <div class="cfg-card">
                <div class="cfg-card-header">
                    <div class="cfg-card-title"><i class="fas fa-palette"></i>Apparence et style</div>
                </div>
                <div class="cfg-card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="cfg-label">Taille de police</label>
                            <select class="cfg-input" name="bulletin_font_size">
                                @foreach([9, 10, 11, 12, 13, 14] as $size)
                                    <option value="{{ $size }}" {{ ($settings['bulletin_font_size'] ?? '11') == $size ? 'selected' : '' }}>{{ $size }}pt</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            </div>{{-- /pane-bts --}}

            {{-- ══════════════════════════════════════════
                 TAB LMD
                 ══════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="pane-lmd" role="tabpanel">

                {{-- ══ LMD 1. En-tête officiel ══ --}}
                <div class="cfg-card">
                    <div class="cfg-card-header">
                        <div class="cfg-card-title"><i class="fas fa-flag"></i>En-tête officiel</div>
                        <span class="cfg-card-badge">République / Ministère</span>
                    </div>
                    <div class="cfg-card-body">
                        <div class="cfg-toggle-grid" style="margin-bottom:1rem;">
                            <label class="cfg-toggle" for="lmd_bulletin_show_republic_info">
                                <span class="cfg-toggle-label">Informations de la République</span>
                                <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_republic_info" name="lmd_bulletin_show_republic_info" value="1"
                                       {{ ($settings['lmd_bulletin_show_republic_info'] ?? '1') == '1' ? 'checked' : '' }}>
                            </label>
                            <label class="cfg-toggle" for="lmd_bulletin_show_ministry_info">
                                <span class="cfg-toggle-label">Informations du ministère</span>
                                <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_ministry_info" name="lmd_bulletin_show_ministry_info" value="1"
                                       {{ ($settings['lmd_bulletin_show_ministry_info'] ?? '1') == '1' ? 'checked' : '' }}>
                            </label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="cfg-label">Texte République</label>
                                <input type="text" class="cfg-input" name="lmd_bulletin_republic_text"
                                       value="{{ $settings['lmd_bulletin_republic_text'] ?? 'REPUBLIQUE DE COTE D\'IVOIRE' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="cfg-label">Devise nationale</label>
                                <input type="text" class="cfg-input" name="lmd_bulletin_union_text"
                                       value="{{ $settings['lmd_bulletin_union_text'] ?? 'Union - Discipline - Travail' }}">
                            </div>
                            <div class="col-12">
                                <label class="cfg-label">Texte Ministère</label>
                                <input type="text" class="cfg-input" name="lmd_bulletin_ministry_text"
                                       value="{{ $settings['lmd_bulletin_ministry_text'] ?? 'MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE' }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══ LMD 2. Encadré Établissement ══ --}}
                <div class="cfg-card">
                    <div class="cfg-card-header">
                        <div class="cfg-card-title"><i class="fas fa-university"></i>Encadré Établissement</div>
                        <span class="cfg-card-badge">Code / Statut / Direction</span>
                    </div>
                    <div class="cfg-card-body">
                        <div class="cfg-toggle-grid" style="margin-bottom:1rem;">
                            <label class="cfg-toggle" for="lmd_bulletin_show_etablissement_box">
                                <span class="cfg-toggle-label">Afficher l'encadré établissement</span>
                                <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_etablissement_box" name="lmd_bulletin_show_etablissement_box" value="1"
                                       {{ ($settings['lmd_bulletin_show_etablissement_box'] ?? '1') == '1' ? 'checked' : '' }}>
                            </label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="cfg-label">Code établissement</label>
                                <input type="text" class="cfg-input" name="lmd_bulletin_code_etablissement"
                                       value="{{ $settings['lmd_bulletin_code_etablissement'] ?? '' }}" placeholder="Ex: 2720328001">
                            </div>
                            <div class="col-md-4">
                                <label class="cfg-label">Statut</label>
                                <select class="cfg-input" name="lmd_bulletin_statut">
                                    <option value="Privé" {{ ($settings['lmd_bulletin_statut'] ?? 'Privé') === 'Privé' ? 'selected' : '' }}>Privé</option>
                                    <option value="Public" {{ ($settings['lmd_bulletin_statut'] ?? '') === 'Public' ? 'selected' : '' }}>Public</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="cfg-label">Direction</label>
                                <input type="text" class="cfg-input" name="lmd_bulletin_direction"
                                       value="{{ $settings['lmd_bulletin_direction'] ?? '' }}" placeholder="Ex: DOREX">
                                <div class="cfg-hint">Si vide, utilisera le nom du directeur des paramètres généraux</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══ LMD 3. Champs Bulletin (Domaine/Mention/Spécialité/Parcours) ══ --}}
                <div class="cfg-card">
                    <div class="cfg-card-header">
                        <div class="cfg-card-title"><i class="fas fa-id-card"></i>Champs sur le bulletin</div>
                        <span class="cfg-card-badge">Hiérarchie UEMOA</span>
                    </div>
                    <div class="cfg-card-body">
                        <div class="cfg-toggle-grid" style="margin-bottom:1rem;">
                            <label class="cfg-toggle" for="lmd_bulletin_show_domaine">
                                <span class="cfg-toggle-label">Afficher Domaine</span>
                                <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_domaine" name="lmd_bulletin_show_domaine" value="1"
                                       {{ ($settings['lmd_bulletin_show_domaine'] ?? '1') == '1' ? 'checked' : '' }}>
                            </label>
                            <label class="cfg-toggle" for="lmd_bulletin_show_mention">
                                <span class="cfg-toggle-label">Afficher Mention</span>
                                <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_mention" name="lmd_bulletin_show_mention" value="1"
                                       {{ ($settings['lmd_bulletin_show_mention'] ?? '1') == '1' ? 'checked' : '' }}>
                            </label>
                            <label class="cfg-toggle" for="lmd_bulletin_show_specialite">
                                <span class="cfg-toggle-label">Afficher Spécialité</span>
                                <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_specialite" name="lmd_bulletin_show_specialite" value="1"
                                       {{ ($settings['lmd_bulletin_show_specialite'] ?? '0') == '1' ? 'checked' : '' }}>
                            </label>
                            <label class="cfg-toggle" for="lmd_bulletin_show_parcours">
                                <span class="cfg-toggle-label">Afficher Parcours</span>
                                <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_parcours" name="lmd_bulletin_show_parcours" value="1"
                                       {{ ($settings['lmd_bulletin_show_parcours'] ?? '1') == '1' ? 'checked' : '' }}>
                            </label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="cfg-label">Libellé Domaine</label>
                                <input type="text" class="cfg-input" name="lmd_bulletin_label_domaine" value="{{ $settings['lmd_bulletin_label_domaine'] ?? 'DOMAINE' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="cfg-label">Libellé Mention</label>
                                <input type="text" class="cfg-input" name="lmd_bulletin_label_mention" value="{{ $settings['lmd_bulletin_label_mention'] ?? 'MENTION' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="cfg-label">Libellé Spécialité</label>
                                <input type="text" class="cfg-input" name="lmd_bulletin_label_specialite" value="{{ $settings['lmd_bulletin_label_specialite'] ?? 'SPÉCIALITÉ' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="cfg-label">Libellé Parcours</label>
                                <input type="text" class="cfg-input" name="lmd_bulletin_label_parcours" value="{{ $settings['lmd_bulletin_label_parcours'] ?? 'PARCOURS' }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══ LMD 4. Textes bas de page ══ --}}
                <div class="cfg-card">
                    <div class="cfg-card-header">
                        <div class="cfg-card-title"><i class="fas fa-file-alt"></i>Textes du bulletin</div>
                    </div>
                    <div class="cfg-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="cfg-label">Notice importante</label>
                                <textarea class="cfg-input" name="lmd_bulletin_notice_text" rows="2" style="resize:vertical;">{{ $settings['lmd_bulletin_notice_text'] ?? 'Pour les UE non acquises il vous sera délivré une attestation de réussite après validation de celles-ci. Un ECUE n\'est ni transférable ni capitalisable.' }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="cfg-label">Texte de pied de page</label>
                                <input type="text" class="cfg-input" name="lmd_bulletin_bottom_text"
                                       value="{{ $settings['lmd_bulletin_bottom_text'] ?? 'Conservez soigneusement ce bulletin de notes. Aucun duplicata ne sera délivré.' }}">
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /pane-lmd --}}
            </div>{{-- /tab-content --}}

            {{-- ══ Footer sticky ══ --}}
            <div class="cfg-footer">
                <a href="{{ route('esbtp.resultats.index') }}" class="cfg-btn cfg-btn--cancel">
                    <i class="fas fa-times"></i>Annuler
                </a>
                <button type="submit" class="cfg-btn cfg-btn--save">
                    <i class="fas fa-save"></i>Sauvegarder la configuration
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
