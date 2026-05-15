@extends('layouts.app')

@section('title', 'Modifier l\'inscription - ' . $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* === INSCRIPTION EDIT — PREMIUM (ie-*) === */
.ie-hero {
    background: linear-gradient(135deg, #0453cb 0%, #1b64d4 50%, #5e91de 100%);
    padding: 24px 28px 20px;
    margin: -24px -24px 0;
    position: relative;
    overflow: hidden;
}
.ie-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-image: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.05) 0%, transparent 50%);
}
.ie-hero-inner {
    position: relative;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}
.ie-hero-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    background: rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: #fff;
    flex-shrink: 0;
}
.ie-hero-text { flex: 1; min-width: 200px; }
.ie-hero-label {
    font-size: 0.68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em;
    color: rgba(255,255,255,0.6); margin-bottom: 2px;
}
.ie-hero-name {
    font-size: 1.3rem; font-weight: 800;
    color: #fff; letter-spacing: -0.02em;
}
.ie-hero-sub {
    font-size: 0.82rem; color: rgba(255,255,255,0.75); margin-top: 2px;
}
.ie-hero-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-left: auto; }
.ie-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 8px;
    font-size: 0.82rem; font-weight: 600;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.12);
    color: #fff; text-decoration: none;
    transition: all 0.2s ease; cursor: pointer;
}
.ie-hero-btn:hover {
    background: rgba(255,255,255,0.22); color: #fff;
    transform: translateY(-1px);
}
.ie-hero-btn.primary {
    background: rgba(255,255,255,0.95); color: #0453cb;
    border-color: transparent; font-weight: 700;
}
.ie-hero-btn.primary:hover { background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: #0453cb; }

.ie-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
    margin-top: 24px;
    overflow: hidden;
}
.ie-card-header {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 24px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-bottom: 1px solid #e2e8f0;
}
.ie-card-header-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
}
.ie-card-header-title {
    font-size: 0.95rem; font-weight: 700; color: #1e293b;
}
.ie-card-body { padding: 24px; }

/* Form elements premium */
.ie-card .form-label, .ie-card label {
    font-size: 0.8rem; font-weight: 600; color: #475569;
    margin-bottom: 4px;
}
.ie-card .form-control, .ie-card .form-select {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.88rem;
    transition: all 0.15s ease;
}
.ie-card .form-control:focus, .ie-card .form-select:focus {
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
}
.ie-card .form-text { font-size: 0.75rem; color: #94a3b8; }
.ie-card .text-danger { color: #dc2626 !important; }

/* Student info box premium */
.ie-student-info {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 18px;
    background: rgba(4,83,203,0.04);
    border: 1px solid rgba(4,83,203,0.1);
    border-radius: 12px;
    margin-bottom: 20px;
}
.ie-student-info-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
}
.ie-student-info-text { font-size: 0.85rem; color: #334155; line-height: 1.5; }
.ie-student-info-text strong { color: #1e293b; }

/* Section dividers */
.ie-section-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 0.88rem; font-weight: 700; color: #1e293b;
    margin-bottom: 16px; padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}
.ie-section-title i {
    width: 28px; height: 28px; border-radius: 8px;
    background: rgba(4,83,203,0.08); color: #0453cb;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem;
}

/* Buttons premium */
.ie-card .btn-primary {
    background: linear-gradient(135deg, #0453cb, #1b64d4);
    border: none; border-radius: 10px;
    padding: 10px 24px; font-weight: 600;
    box-shadow: 0 2px 8px rgba(4,83,203,0.25);
    transition: all 0.2s ease;
}
.ie-card .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(4,83,203,0.35);
}
.ie-card .btn-secondary {
    border-radius: 10px; padding: 10px 20px;
    font-weight: 600; border: 1px solid #e2e8f0;
}

/* Alert premium in form */
.ie-card .alert { border-radius: 10px; font-size: 0.85rem; border: none; }
.ie-card .alert-info { background: rgba(59,130,246,0.06); color: #1e40af; border: 1px solid rgba(59,130,246,0.12); }
.ie-card .alert-warning { background: rgba(245,158,11,0.06); color: #92400e; border: 1px solid rgba(245,158,11,0.12); }
.ie-card .alert-danger { background: rgba(239,68,68,0.06); color: #991b1b; border: 1px solid rgba(239,68,68,0.12); }

/* Select2 premium */
.select2-container { width: 100% !important; }
.ie-card .select2-selection {
    height: 42px !important; border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;
}
.ie-card .select2-selection__rendered { line-height: 40px !important; padding-left: 14px !important; font-size: 0.88rem !important; }
.ie-card .select2-selection__arrow { height: 40px !important; }
.ie-card .select2-container--focus .select2-selection {
    border-color: #0453cb !important;
    box-shadow: 0 0 0 3px rgba(4,83,203,0.1) !important;
}

@media (max-width: 768px) {
    .ie-hero { padding: 16px; margin: -16px -16px 0; }
    .ie-hero-name { font-size: 1.1rem; }
    .ie-hero-actions { width: 100%; margin-left: 0; }
    .ie-card-body { padding: 16px; }
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Hero Premium -->
        <div class="ie-hero">
            <div class="ie-hero-inner">
                <div class="ie-hero-icon"><i class="fas fa-edit"></i></div>
                <div class="ie-hero-text">
                    <div class="ie-hero-label"><i class="fas fa-file-alt me-1"></i>Modification Inscription</div>
                    <div class="ie-hero-name">{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</div>
                    <div class="ie-hero-sub">Matricule : {{ $inscription->etudiant->matricule }}</div>
                </div>
                <div class="ie-hero-actions">
                    <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" class="ie-hero-btn primary">
                        <i class="fas fa-eye"></i>
                        <span class="d-none d-sm-inline">Voir les détails</span>
                    </a>
                    <a href="{{ route('esbtp.inscriptions.index') }}" class="ie-hero-btn">
                        <i class="fas fa-arrow-left"></i>
                        <span class="d-none d-sm-inline">Retour</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Form Card Premium -->
        <div class="ie-card">
            <div class="ie-card-header">
                <div class="ie-card-header-icon"><i class="fas fa-graduation-cap"></i></div>
                <div class="ie-card-header-title">Modifier l'inscription</div>
            </div>
            <div class="ie-card-body">
                @include('esbtp.inscriptions.partials.edit-form', [
                    'inscription' => $inscription,
                    'filieres' => $filieres,
                    'niveaux' => $niveaux,
                    'classes' => $classes,
                    'annees' => $annees,
                    'mentions' => $mentions ?? collect(),
                    'parcours' => $parcours ?? collect(),
                    'formId' => 'inscription-edit-form-page-' . $inscription->id,
                    'isEmbedded' => false,
                ])
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('esbtp.inscriptions.partials.edit-form-scripts')
@endpush
