@extends('layouts.app')

@section('title', 'Configuration des Frais Optionnels - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* =====================================================
   OPTIONAL-CONFIG — Design System KLASSCI
   ===================================================== */

/* Couleurs par type de frais */
.oc-type-transport { --oc-color: #0ea5e9; --oc-bg: #f0f9ff; --oc-border: #bae6fd; }
.oc-type-cantine   { --oc-color: #10b981; --oc-bg: #f0fdf4; --oc-border: #a7f3d0; }
.oc-type-service   { --oc-color: #8b5cf6; --oc-bg: #f5f3ff; --oc-border: #c4b5fd; }
.oc-type-autre     { --oc-color: #f59e0b; --oc-bg: #fffbeb; --oc-border: #fde68a; }

/* ── KPI grid ── */
.oc-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
.oc-kpi-card {
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: box-shadow 0.2s;
}
.oc-kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.oc-kpi-icon {
    width: 44px; height: 44px;
    border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    font-size: 19px; flex-shrink: 0;
}
.oc-kpi-num  { font-size: 26px; font-weight: 800; color: #1e293b; line-height: 1; margin-bottom: 4px; }
.oc-kpi-lbl  { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4px; }

/* ── Category card ── */
.oc-category-card {
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 20px;
    transition: box-shadow 0.2s;
}
.oc-category-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.07); }

/* Card header */
.oc-card-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 24px 16px;
    border-bottom: 1px solid #f1f5f9;
}
.oc-card-icon {
    width: 50px; height: 50px;
    border-radius: 13px;
    background: var(--oc-bg);
    color: var(--oc-color);
    display: flex; align-items: center; justify-content: center;
    font-size: 21px; flex-shrink: 0;
}
.oc-card-meta  { flex: 1; min-width: 0; }
.oc-card-meta h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 5px; }
.oc-card-meta p  { font-size: 12px; color: #94a3b8; margin: 0; }
.oc-type-badge {
    display: inline-block;
    font-size: 10px; font-weight: 700; letter-spacing: 0.4px;
    text-transform: uppercase;
    color: var(--oc-color);
    background: var(--oc-bg);
    border: 1px solid var(--oc-border);
    padding: 3px 9px; border-radius: 20px;
}
.oc-status-pill {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700;
    padding: 4px 11px; border-radius: 20px;
}
.oc-status-pill.active   { background: #d1fae5; color: #065f46; border: 1.5px solid rgba(16,185,129,0.25); }
.oc-status-pill.inactive { background: #f3f4f6; color: #9ca3af; border: 1.5px solid #e5e7eb; }

/* Options body */
.oc-options-body { padding: 0 24px; }
.oc-options-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 0 8px;
}
.oc-options-section-label {
    font-size: 11px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.oc-options-counter {
    font-size: 11px; color: #94a3b8;
    background: #f8fafc; padding: 2px 9px;
    border-radius: 10px; border: 1px solid #e5e7eb;
    font-weight: 600;
}

/* Option row */
.oc-option-row {
    display: flex; align-items: center; gap: 12px;
    padding: 13px 0;
    border-bottom: 1px solid #f1f5f9;
}
.oc-option-row:last-child { border-bottom: none; }
.oc-option-info { flex: 1; min-width: 0; }
.oc-option-name { font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 3px; }
.oc-option-desc { font-size: 12px; color: #94a3b8; margin-bottom: 5px; }
.oc-option-assignments { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 2px; }
.oc-assign-tag {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10px; font-weight: 600;
    padding: 2px 8px; border-radius: 10px;
    background: #d1fae5; color: #065f46;
    border: 1px solid rgba(16,185,129,0.2);
}
.oc-assign-tag.empty { background: #f3f4f6; color: #9ca3af; border-color: #e5e7eb; }
.oc-option-price {
    font-size: 15px; font-weight: 800; color: #0453cb;
    white-space: nowrap; text-align: right; min-width: 120px;
}
.oc-option-price small { display: block; font-size: 10px; font-weight: 500; color: #94a3b8; }
.oc-option-actions { display: flex; gap: 5px; flex-shrink: 0; }
.oc-btn-icon {
    width: 32px; height: 32px;
    border-radius: 8px; border: 1.5px solid #e5e7eb;
    background: #fff; color: #94a3b8;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px; cursor: pointer; transition: all 0.18s;
}
.oc-btn-icon:hover        { border-color: #bae6fd; color: #0ea5e9; background: #f0f9ff; }
.oc-btn-icon.oc-edit:hover   { border-color: #bfdbfe; color: #0453cb; background: #eff6ff; }
.oc-btn-icon.oc-assign:hover { border-color: #a7f3d0; color: #059669; background: #ecfdf5; }
.oc-btn-icon.oc-delete:hover { border-color: #fca5a5; color: #dc2626; background: #fef2f2; }

/* Empty state */
.oc-options-empty {
    text-align: center; padding: 28px 16px;
    color: #94a3b8;
}
.oc-options-empty i { font-size: 28px; margin-bottom: 10px; opacity: 0.4; display: block; }
.oc-options-empty p  { font-size: 13px; margin: 0 0 4px; font-weight: 500; }
.oc-options-empty small { font-size: 12px; }

/* Add-option zone */
.oc-add-zone {
    border-top: 1px solid #f1f5f9;
    padding: 0 24px 22px;
}
.oc-add-trigger {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 14px 0 0;
    font-size: 13px; font-weight: 700; color: #0453cb;
    cursor: pointer; background: none; border: none; outline: none;
    transition: opacity 0.15s;
}
.oc-add-trigger:hover { opacity: 0.7; }
.oc-add-form {
    display: none;
    margin-top: 14px;
    background: #eff6ff;
    border: 1.5px dashed #bfdbfe;
    border-radius: 10px;
    padding: 18px;
}
.oc-add-form.open { display: block; }
.oc-add-form label {
    font-size: 12px; font-weight: 600; color: #374151;
    margin-bottom: 5px; display: block;
}
.oc-add-form .form-control {
    border: 1.5px solid #e5e7eb; border-radius: 8px;
    padding: 8px 12px; font-size: 13px;
    background: #fff; width: 100%;
    transition: border-color 0.15s; outline: none;
}
.oc-add-form .form-control:focus {
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,0.08);
}
.oc-add-form .row { margin: 0 -6px; }
.oc-add-form .col-md-4 { padding: 0 6px; }

/* Modal backdrop fix */
#editOptionModal.modal,
#assignmentsModal.modal {
    z-index: 9999 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}
.modal-backdrop {
    z-index: 1040 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}
body.modal-open * {
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* Assignment badges (modal & page) */
.assignment-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 8px; border-radius: 12px;
    font-size: 11px; font-weight: 500;
}
.assignment-badge.success  { background: rgba(16,185,129,.1); color: #065f46; border: 1px solid rgba(16,185,129,.2); }
.assignment-badge.secondary { background: rgba(107,114,128,.1); color: #374151; border: 1px solid rgba(107,114,128,.2); }

/* Modal styles */
.oc-modal-content { border-radius: 14px !important; border: none !important; box-shadow: 0 20px 40px rgba(0,0,0,0.18) !important; }
.oc-modal-header {
    border-bottom: 1px solid #f1f5f9;
    padding: 18px 22px 14px;
}
.oc-modal-header h5 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; }
.oc-modal-body { padding: 20px 22px; }
.oc-modal-footer { border-top: 1px solid #f1f5f9; padding: 14px 22px; }
.oc-form-group { margin-bottom: 16px; }
.oc-form-group label { font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block; }
.oc-form-control {
    border: 1.5px solid #e5e7eb; border-radius: 8px;
    padding: 9px 13px; font-size: 13px; width: 100%;
    background: #fff; outline: none; transition: border-color 0.15s;
}
.oc-form-control:focus { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,0.08); }

/* Assignment type selector (modal) */
.oc-assign-type-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-bottom: 16px;
}
.oc-assign-type-option {
    position: relative;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 12px 14px;
    cursor: pointer;
    transition: all 0.18s;
    display: flex; align-items: center; gap: 8px;
}
.oc-assign-type-option input[type="radio"] { display: none; }
.oc-assign-type-option:hover { border-color: #bfdbfe; background: #eff6ff; }
.oc-assign-type-option:has(input:checked) {
    border-color: #0453cb;
    background: #eff6ff;
    color: #0453cb;
}
.oc-assign-type-option i { font-size: 16px; flex-shrink: 0; }
.oc-assign-type-option span { font-size: 12px; font-weight: 600; }

@media (max-width: 768px) {
    .oc-kpi-grid { grid-template-columns: 1fr; }
    .oc-card-head { flex-wrap: wrap; }
    .oc-option-row { flex-wrap: wrap; gap: 8px; }
    .oc-option-price { min-width: auto; text-align: left; }
    .oc-assign-type-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ── Header ──────────────────────────────────────────────────── --}}
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Frais Optionnels</h1>
                <p class="header-subtitle">Configuration des services à la carte : transport, cantine, activités…</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Frais par Classe
                </a>
                <a href="{{ route('esbtp.frais.index') }}" class="btn-acasi primary">
                    <i class="fas fa-list"></i>Catégories
                </a>
            </div>
        </div>

        {{-- ── Flash success ────────────────────────────────────────────── --}}
        @if(session('success'))
            <div class="alert-kl alert-kl-success mb-4" style="border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;border:1px solid rgba(16,185,129,.25);">
                <i class="fas fa-check-circle" style="font-size:16px;"></i>
                <span style="font-weight:600;">{{ session('success') }}</span>
            </div>
        @endif

        {{-- ── KPI row ──────────────────────────────────────────────────── --}}
        <div class="oc-kpi-grid">
            <div class="oc-kpi-card">
                <div class="oc-kpi-icon" style="background:#eff6ff;color:#0453cb;">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <div>
                    <div class="oc-kpi-num">{{ $stats['total_optional'] }}</div>
                    <div class="oc-kpi-lbl">Catégories optionnelles</div>
                </div>
            </div>
            <div class="oc-kpi-card">
                <div class="oc-kpi-icon" style="background:#f0f9ff;color:#0ea5e9;">
                    <i class="fas fa-bus"></i>
                </div>
                <div>
                    <div class="oc-kpi-num">{{ $stats['transport_stops'] }}</div>
                    <div class="oc-kpi-lbl">Arrêts transport</div>
                </div>
            </div>
            <div class="oc-kpi-card">
                <div class="oc-kpi-icon" style="background:#f0fdf4;color:#10b981;">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <div class="oc-kpi-num">{{ $stats['cantine_menus'] }}</div>
                    <div class="oc-kpi-lbl">Menus cantine</div>
                </div>
            </div>
        </div>

        {{-- ── Categories list ─────────────────────────────────────────── --}}
        @if($optionalCategories->count() > 0)
            @foreach($optionalCategories as $category)
                @php
                    $typeClass = match($category->category_type) {
                        'transport' => 'oc-type-transport',
                        'cantine'   => 'oc-type-cantine',
                        'service'   => 'oc-type-service',
                        default     => 'oc-type-autre',
                    };
                    $typeIcon = match($category->category_type) {
                        'transport' => 'fas fa-bus',
                        'cantine'   => 'fas fa-utensils',
                        'service'   => 'fas fa-concierge-bell',
                        default     => $category->icon ?? 'fas fa-puzzle-piece',
                    };
                    if ($category->icon) $typeIcon = $category->icon;
                @endphp

                <div class="oc-category-card {{ $typeClass }}">

                    {{-- Card header --}}
                    <div class="oc-card-head">
                        <div class="oc-card-icon">
                            <i class="{{ $typeIcon }}"></i>
                        </div>
                        <div class="oc-card-meta">
                            <h3>{{ $category->name }}</h3>
                            @if($category->description)
                                <p>{{ $category->description }}</p>
                            @endif
                            <div class="mt-1">
                                <span class="oc-type-badge">{{ ucfirst($category->category_type ?? 'autre') }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <span class="oc-status-pill {{ $category->is_active ? 'active' : 'inactive' }}">
                                <i class="fas fa-circle" style="font-size:7px;"></i>
                                {{ $category->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>

                    {{-- Options list --}}
                    <div class="oc-options-body">
                        <div class="oc-options-header">
                            <span class="oc-options-section-label">
                                <i class="fas fa-layer-group me-1"></i>Formules disponibles
                            </span>
                            <span class="oc-options-counter">{{ $category->options->count() }}</span>
                        </div>

                        @if($category->options->count() > 0)
                            @foreach($category->options as $option)
                                @php
                                    $assignments = $option->assignments ?? collect();
                                @endphp
                                <div class="oc-option-row" data-option-id="{{ $option->id }}">
                                    <div class="oc-option-info">
                                        <div class="oc-option-name">{{ $option->name }}</div>
                                        @if($option->description)
                                            <div class="oc-option-desc">{{ $option->description }}</div>
                                        @endif
                                        {{-- Assignment badges — id préservé pour refreshOptionAssignments() JS --}}
                                        <div class="oc-option-assignments" id="assignment-badges-{{ $option->id }}">
                                            @if($assignments->count() > 0)
                                                @foreach($assignments as $assignment)
                                                    <span class="oc-assign-tag">
                                                        <i class="fas fa-users"></i>{{ $assignment->display_label }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="oc-assign-tag empty">
                                                    <i class="fas fa-minus"></i>Non assigné
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="oc-option-price">
                                        {{ number_format($option->additional_amount, 0, ',', ' ') }}
                                        <small>F CFA / an</small>
                                    </div>
                                    <div class="oc-option-actions">
                                        <button type="button"
                                                class="oc-btn-icon oc-edit"
                                                onclick="editOption({{ $option->id }}, '{{ addslashes($option->name) }}', {{ $option->additional_amount }}, '{{ addslashes($option->description ?? '') }}')"
                                                title="Modifier">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button type="button"
                                                class="oc-btn-icon oc-assign"
                                                onclick="manageAssignments({{ $option->id }}, '{{ addslashes($option->name) }}')"
                                                title="Gérer les assignations">
                                            <i class="fas fa-users"></i>
                                        </button>
                                        <button type="button"
                                                class="oc-btn-icon oc-delete"
                                                onclick="deleteOption({{ $option->id }})"
                                                title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="oc-options-empty">
                                <i class="fas fa-inbox"></i>
                                <p>Aucune formule configurée</p>
                                <small>Utilisez le bouton ci-dessous pour ajouter votre première formule.</small>
                            </div>
                        @endif
                    </div>

                    {{-- Add-option zone --}}
                    <div class="oc-add-zone">
                        <button type="button"
                                class="oc-add-trigger"
                                onclick="toggleAddForm({{ $category->id }})">
                            <i class="fas fa-plus-circle"></i>
                            Ajouter une formule
                        </button>
                        <div class="oc-add-form" id="add-form-{{ $category->id }}">
                            <form method="POST" action="{{ route('esbtp.frais.variants.store') }}">
                                @csrf
                                <input type="hidden" name="category_id" value="{{ $category->id }}">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label>Nom de la formule *</label>
                                            <input type="text" name="name" class="form-control"
                                                   placeholder="Ex : Arrêt Centre-ville" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label>Montant (F CFA) *</label>
                                            <input type="number" name="additional_amount" class="form-control"
                                                   placeholder="15 000" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn-acasi primary" style="width:100%;">
                                            <i class="fas fa-plus"></i>Ajouter
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group mt-2">
                                    <label>Description <span style="font-weight:400;color:#94a3b8;">(optionnel)</span></label>
                                    <input type="text" name="description" class="form-control"
                                           placeholder="Brève description de la formule">
                                </div>
                            </form>
                        </div>
                    </div>

                </div>{{-- /.oc-category-card --}}
            @endforeach

        @else
            {{-- Empty state global --}}
            <div style="background:#fff;border:1.5px solid #e5e7eb;border-radius:14px;padding:56px 24px;text-align:center;">
                <div style="width:64px;height:64px;border-radius:16px;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                    <i class="fas fa-sliders-h" style="font-size:26px;color:#94a3b8;"></i>
                </div>
                <h3 style="font-size:17px;font-weight:700;color:#1e293b;margin:0 0 8px;">Aucune catégorie optionnelle</h3>
                <p style="font-size:13px;color:#94a3b8;margin:0 0 20px;max-width:380px;margin-inline:auto;">
                    Créez d'abord des catégories de frais optionnels (transport, cantine…) depuis la gestion des catégories.
                </p>
                <a href="{{ route('esbtp.frais.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus"></i>Créer une catégorie
                </a>
            </div>
        @endif

    </div>{{-- /.main-content --}}
</div>{{-- /.dashboard-acasi --}}

{{-- ── Modal : modifier une option ────────────────────────────────────── --}}
<div class="modal fade" id="editOptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content oc-modal-content">
            <div class="oc-modal-header">
                <h5><i class="fas fa-pencil-alt me-2" style="color:#0453cb;"></i>Modifier la formule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editOptionForm">
                <div class="oc-modal-body">
                    <input type="hidden" id="editOptionId" name="option_id">
                    <div class="oc-form-group">
                        <label for="editOptionName">Nom de la formule</label>
                        <input type="text" class="oc-form-control" id="editOptionName" name="name" required>
                    </div>
                    <div class="oc-form-group">
                        <label for="editOptionDescription">Description <span style="font-weight:400;color:#94a3b8;">(optionnel)</span></label>
                        <textarea class="oc-form-control" id="editOptionDescription" name="description" rows="2"></textarea>
                    </div>
                    <div class="oc-form-group" style="margin-bottom:0;">
                        <label for="editOptionAmount">Montant (F CFA)</label>
                        <input type="number" class="oc-form-control" id="editOptionAmount" name="additional_amount" min="0" required>
                    </div>
                </div>
                <div class="oc-modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>Annuler
                    </button>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Modal : gérer les assignations ─────────────────────────────────── --}}
<div class="modal fade" id="assignmentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content oc-modal-content">
            <div class="oc-modal-header">
                <h5>
                    <i class="fas fa-users me-2" style="color:#059669;"></i>
                    Assignations — <span id="assignmentOptionName" style="color:#0453cb;"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="oc-modal-body">
                <input type="hidden" id="assignmentOptionId" name="option_id">

                {{-- Type d'assignation --}}
                <div class="oc-form-group">
                    <label>Type d'assignation</label>
                    <div class="oc-assign-type-grid">
                        <label class="oc-assign-type-option">
                            <input type="radio" name="modal_assignment_type" value="all">
                            <i class="fas fa-globe-africa"></i>
                            <span>Tous les étudiants</span>
                        </label>
                        <label class="oc-assign-type-option">
                            <input type="radio" name="modal_assignment_type" value="filiere">
                            <i class="fas fa-sitemap"></i>
                            <span>Par filière</span>
                        </label>
                        <label class="oc-assign-type-option">
                            <input type="radio" name="modal_assignment_type" value="niveau">
                            <i class="fas fa-layer-group"></i>
                            <span>Par niveau</span>
                        </label>
                        <label class="oc-assign-type-option">
                            <input type="radio" name="modal_assignment_type" value="classe">
                            <i class="fas fa-school"></i>
                            <span>Par classe</span>
                        </label>
                    </div>
                </div>

                {{-- Détails filières / niveaux --}}
                <div id="modal_assignment_details" style="display:none;" class="oc-form-group">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Filières</label>
                            <select multiple class="oc-form-control" id="modal_filieres" style="height:120px;">
                                @php $filieres = \App\Models\ESBTPFiliere::where('is_active', true)->get(); @endphp
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}">{{ $filiere->name }}</option>
                                @endforeach
                            </select>
                            <small style="color:#94a3b8;font-size:11px;">Ctrl+clic pour multi-sélection</small>
                        </div>
                        <div class="col-md-6">
                            <label>Niveaux d'étude</label>
                            <select multiple class="oc-form-control" id="modal_niveaux" style="height:120px;">
                                @php $niveaux = \App\Models\ESBTPNiveauEtude::where('is_active', true)->get(); @endphp
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}">{{ $niveau->name }}</option>
                                @endforeach
                            </select>
                            <small style="color:#94a3b8;font-size:11px;">Ctrl+clic pour multi-sélection</small>
                        </div>
                    </div>
                </div>

                {{-- Assignations actuelles --}}
                <div class="oc-form-group" style="margin-bottom:0;">
                    <label><i class="fas fa-list me-1"></i>Assignations actuelles</label>
                    <div id="assignmentsList"
                         style="min-height:48px;background:#f8fafc;border:1.5px solid #e5e7eb;border-radius:8px;padding:12px;">
                        {{-- chargé dynamiquement --}}
                    </div>
                </div>
            </div>
            <div class="oc-modal-footer d-flex align-items-center justify-content-between">
                <button type="button" class="btn-acasi danger" onclick="clearAllAssignments()"
                        id="clearAssignmentsBtn" style="display:none;">
                    <i class="fas fa-trash"></i>Tout supprimer
                </button>
                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>Fermer
                    </button>
                    <button type="button" class="btn-acasi primary" onclick="saveOptionAssignment()">
                        <i class="fas fa-save"></i>Sauvegarder
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Afficher/masquer le formulaire d'ajout d'une formule
function toggleAddForm(categoryId) {
    const form = document.getElementById('add-form-' + categoryId);
    if (!form) return;
    form.classList.toggle('open');
}

document.addEventListener('DOMContentLoaded', function() {

    // Gérer l'affichage des détails d'assignation dans la modal
    document.querySelectorAll('input[name="modal_assignment_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const detailsDiv = document.getElementById('modal_assignment_details');
            
            if (this.value === 'all') {
                detailsDiv.style.display = 'none';
            } else {
                detailsDiv.style.display = 'block';
                
                // Gérer la visibilité des select selon le type
                const filieresSelect = document.getElementById('modal_filieres');
                const niveauxSelect = document.getElementById('modal_niveaux');
                
                if (this.value === 'filiere') {
                    filieresSelect.parentElement.parentElement.style.display = 'block';
                    niveauxSelect.parentElement.parentElement.style.display = 'none';
                } else if (this.value === 'niveau') {
                    filieresSelect.parentElement.parentElement.style.display = 'none';
                    niveauxSelect.parentElement.parentElement.style.display = 'block';
                } else if (this.value === 'classe') {
                    filieresSelect.parentElement.parentElement.style.display = 'block';
                    niveauxSelect.parentElement.parentElement.style.display = 'block';
                }
            }
        });
    });

    // Gérer la soumission du formulaire d'édition d'option
    const editOptionForm = document.getElementById('editOptionForm');
    if (editOptionForm) {
        editOptionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const optionId = document.getElementById('editOptionId').value;
            
            fetch(`/esbtp/frais/variants/${optionId}`, {
                method: 'PUT',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editOptionModal')).hide();
                    location.reload();
                } else {
                    alert('Erreur lors de la modification : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                debugError('Erreur:', error);
                alert('Erreur de connexion');
            });
        });
    }
});

// Fonction pour basculer entre les vues simple et détaillée
function toggleOptionsView(categoryId) {
    const simpleView = document.getElementById(`simple-view-${categoryId}`);
    const detailedView = document.getElementById(`detailed-view-${categoryId}`);
    const viewIcon = document.getElementById(`view-icon-${categoryId}`);
    const viewText = document.getElementById(`view-text-${categoryId}`);
    
    if (simpleView.style.display !== 'none') {
        // Passer à la vue détaillée
        simpleView.style.display = 'none';
        detailedView.style.display = 'block';
        viewIcon.className = 'fas fa-list';
        viewText.textContent = 'Vue simple';
    } else {
        // Passer à la vue simple
        simpleView.style.display = 'block';
        detailedView.style.display = 'none';
        viewIcon.className = 'fas fa-eye';
        viewText.textContent = 'Vue détaillée';
    }
}

// Fonction pour éditer une option
function editOption(optionId, name, amount, description) {
    document.getElementById('editOptionId').value = optionId;
    document.getElementById('editOptionName').value = name;
    document.getElementById('editOptionAmount').value = amount;
    document.getElementById('editOptionDescription').value = description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editOptionModal'));
    modal.show();
}

// Fonction pour gérer les assignations d'une option
function manageAssignments(optionId, optionName) {
    document.getElementById('assignmentOptionId').value = optionId;
    document.getElementById('assignmentOptionName').textContent = optionName;
    
    // Charger les assignations existantes
    loadCurrentAssignments(optionId);
    
    const modal = new bootstrap.Modal(document.getElementById('assignmentsModal'));
    modal.show();
}

// Fonction pour charger les assignations actuelles
function loadCurrentAssignments(optionId) {
    const assignmentsList = document.getElementById('assignmentsList');
    const clearBtn = document.getElementById('clearAssignmentsBtn');
    
    assignmentsList.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
    
    fetch(`{{ url('esbtp/frais/options') }}/${optionId}/assignments`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.assignments.length > 0) {
                let html = '<div style="display: flex; flex-wrap: wrap; gap: var(--space-xs);">';
                
                data.assignments.forEach(assignment => {
                    html += `
                        <span class="assignment-badge success" style="position: relative; padding-right: 25px;">
                            ${assignment.display_label}
                            <button type="button" onclick="removeAssignment(${assignment.id})" style="position: absolute; right: 5px; top: 2px; background: none; border: none; color: #065f46; font-size: 10px;">
                                <i class="fas fa-times"></i>
                            </button>
                        </span>
                    `;
                });
                
                html += '</div>';
                assignmentsList.innerHTML = html;
                clearBtn.style.display = 'inline-block';
            } else {
                assignmentsList.innerHTML = '<div style="color: var(--text-muted); font-style: italic; text-align: center; padding: var(--space-sm);">Aucune assignation configurée</div>';
                clearBtn.style.display = 'none';
            }
        })
        .catch(error => {
            debugError('Erreur:', error);
            assignmentsList.innerHTML = '<div class="text-danger">Erreur lors du chargement des assignations</div>';
            clearBtn.style.display = 'none';
        });
}

// Fonction pour supprimer une assignation spécifique
function removeAssignment(assignmentId) {
    if (confirm('Supprimer cette assignation ?')) {
        fetch(`{{ url('esbtp/frais/assignments') }}/${assignmentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const optionId = document.getElementById('assignmentOptionId').value;
                loadCurrentAssignments(optionId);
                refreshOptionAssignments(optionId);
                
                // Afficher un message de succès
                showSuccessMessage(data.message || 'Assignation supprimée avec succès !');
            } else {
                alert('Erreur lors de la suppression: ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            debugError('Erreur:', error);
            alert('Erreur de connexion');
        });
    }
}

// Fonction pour supprimer toutes les assignations
function clearAllAssignments() {
    if (confirm('Supprimer toutes les assignations de cette option ?')) {
        const optionId = document.getElementById('assignmentOptionId').value;
        
        fetch(`{{ url('esbtp/frais/options') }}/${optionId}/assignments`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCurrentAssignments(optionId);
                refreshOptionAssignments(optionId);
                // Réinitialiser le formulaire
                document.querySelectorAll('input[name="modal_assignment_type"]').forEach(radio => radio.checked = false);
                document.getElementById('modal_assignment_details').style.display = 'none';
                
                showSuccessMessage(data.message || 'Toutes les assignations ont été supprimées !');
            } else {
                alert('Erreur lors de la suppression: ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            debugError('Erreur:', error);
            alert('Erreur de connexion');
        });
    }
}

// Fonction pour sauvegarder les assignations d'une option
function saveOptionAssignment() {
    const optionId = document.getElementById('assignmentOptionId').value;
    const assignmentType = document.querySelector('input[name="modal_assignment_type"]:checked');
    
    if (!assignmentType) {
        alert('Veuillez sélectionner un type d\'assignation');
        return;
    }
    
    let data = {
        option_id: optionId,
        assignment_type: assignmentType.value,
        _token: document.querySelector('meta[name="csrf-token"]').content
    };
    
    if (assignmentType.value !== 'all') {
        const filieresSelect = document.getElementById('modal_filieres');
        const niveauxSelect = document.getElementById('modal_niveaux');
        
        if (assignmentType.value === 'filiere' || assignmentType.value === 'classe') {
            data.filieres = Array.from(filieresSelect.selectedOptions).map(option => option.value);
            if (data.filieres.length === 0) {
                alert('Veuillez sélectionner au moins une filière');
                return;
            }
        }
        
        if (assignmentType.value === 'niveau' || assignmentType.value === 'classe') {
            data.niveaux = Array.from(niveauxSelect.selectedOptions).map(option => option.value);
            if (data.niveaux.length === 0) {
                alert('Veuillez sélectionner au moins un niveau');
                return;
            }
        }
    }
    
    fetch('{{ url("esbtp/frais/options/assignments") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': data._token
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('assignmentsModal'));
            modal.hide();
            
            // Rafraîchir seulement l'option concernée
            refreshOptionAssignments(optionId);
            
            showSuccessMessage(data.message || 'Assignations sauvegardées avec succès !');
        } else {
            alert('Erreur : ' + (data.message || 'Impossible de sauvegarder'));
        }
    })
    .catch(error => {
        debugError('Erreur:', error);
        alert('Erreur de connexion');
    });
}

// Fonction helper pour afficher les messages de succès
function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    // Auto-supprimer l'alerte après 3 secondes
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

// Fonction pour rafraîchir les assignations d'une option spécifique
function refreshOptionAssignments(optionId) {
    fetch(`{{ url('esbtp/frais/options') }}/${optionId}/assignments`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour les badges d'assignation dans la vue simple
                const assignmentBadgesContainer = document.getElementById(`assignment-badges-${optionId}`);
                if (assignmentBadgesContainer) {
                    let badgesHtml = '';
                    
                    if (data.assignments.length > 0) {
                        data.assignments.forEach(assignment => {
                            badgesHtml += `
                                <span class="assignment-badge success" style="margin-right: var(--space-xs); margin-bottom: var(--space-xs);">
                                    <i class="fas fa-users"></i>${assignment.display_label}
                                </span>
                            `;
                        });
                    } else {
                        badgesHtml = `
                            <span class="assignment-badge secondary">
                                <i class="fas fa-users"></i>Non assigné
                            </span>
                        `;
                    }
                    
                    assignmentBadgesContainer.innerHTML = badgesHtml;
                }
                
                // Si on est en vue détaillée, rafraîchir aussi cette partie
                const detailedView = document.getElementById(`detailed-view-${getCategoryIdForOption(optionId)}`);
                if (detailedView && detailedView.style.display !== 'none') {
                    // Pour la vue détaillée, on pourrait recharger juste cette carte
                    // Pour l'instant, on laisse comme ça car c'est plus complexe à implémenter
                    debugLog('Vue détaillée nécessite un rafraîchissement complet');
                }
            }
        })
        .catch(error => {
            debugError('Erreur lors du rafraîchissement des assignations:', error);
        });
}

// Fonction helper pour trouver l'ID de catégorie d'une option (approximatif)
function getCategoryIdForOption(optionId) {
    // Cette fonction pourrait être améliorée en stockant l'ID de catégorie dans les attributs HTML
    // Pour l'instant, on retourne une valeur par défaut
    return 1; // Placeholder
}


function deleteOption(optionId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette option ?')) {
        // Créer un formulaire pour la suppression
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/esbtp/frais/variants/${optionId}`;
        
        // Ajouter le token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
        
        // Ajouter la méthode DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Ajouter au DOM et soumettre
        document.body.appendChild(form);
        form.submit();
    }
}

// === FIX MODAL Z-INDEX DYNAMIQUE ===
document.addEventListener('DOMContentLoaded', function() {
    debugLog('🚀 Initialisation du fix des modals pour les frais optionnels');
    
    // Liste des modals à corriger
    const modals = ['assignModal', 'editModal', 'deleteModal', 'addFeeModal'];
    
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            // Événement pour forcer z-index correct à l'ouverture
            modal.addEventListener('show.bs.modal', function(e) {
                debugLog(`🔧 Préparation modal ${modalId}`);
                
                // Désactiver toutes les animations pendant l'ouverture
                document.body.style.setProperty('overflow', 'hidden', 'important');
                
                // Ajouter style anti-cursor
                const antiCursorStyle = document.createElement('style');
                antiCursorStyle.id = `anti-cursor-${modalId}`;
                antiCursorStyle.textContent = `
                    * { animation: none !important; transition: none !important; }
                    *:hover { transform: none !important; }
                `;
                document.head.appendChild(antiCursorStyle);
            });
            
            modal.addEventListener('shown.bs.modal', function(e) {
                debugLog(`✅ Modal ${modalId} ouvert - Application des corrections`);
                
                // Forcer z-index très élevé
                modal.style.setProperty('z-index', '9999', 'important');
                modal.style.setProperty('backdrop-filter', 'none', 'important');
                modal.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                
                const modalDialog = modal.querySelector('.modal-dialog');
                const modalContent = modal.querySelector('.modal-content');
                
                if (modalDialog) {
                    modalDialog.style.setProperty('z-index', '10000', 'important');
                    modalDialog.style.setProperty('backdrop-filter', 'none', 'important');
                    modalDialog.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                }
                
                if (modalContent) {
                    modalContent.style.setProperty('z-index', '10001', 'important');
                    modalContent.style.setProperty('backdrop-filter', 'none', 'important');
                    modalContent.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                    modalContent.style.setProperty('background', 'white', 'important');
                }
                
                // Forcer backdrop en arrière
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.style.setProperty('z-index', '1040', 'important');
                    backdrop.style.setProperty('backdrop-filter', 'none', 'important');
                    backdrop.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                }
            });
            
            // Nettoyer à la fermeture
            modal.addEventListener('hidden.bs.modal', function(e) {
                debugLog(`🧹 Nettoyage modal ${modalId}`);
                
                // Supprimer style anti-cursor
                const antiCursorStyle = document.getElementById(`anti-cursor-${modalId}`);
                if (antiCursorStyle) {
                    antiCursorStyle.remove();
                }
                
                // Rétablir overflow
                document.body.style.overflow = '';
            });
        }
    });
    
    debugLog('✅ Fix modals configuré pour:', modals);
});
</script>
@endpush