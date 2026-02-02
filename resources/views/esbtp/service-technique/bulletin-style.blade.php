@extends('layouts.app')

@section('title', 'Style Bulletin PDF')

@push('styles')
<style>
    .style-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        background: #fff;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
    }
    .style-card.is-selected {
        border-color: #0f766e;
        box-shadow: 0 10px 24px rgba(15, 118, 110, 0.18);
    }
    .style-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        color: #0f766e;
        background: #e6fffa;
        border: 1px solid #b2f5ea;
    }
    .style-preview {
        border-radius: 10px;
        border: 1px dashed #cbd5f5;
        background: #f8fafc;
        padding: 12px;
        font-size: 12px;
        color: #475569;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="main-card">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="main-card-title">Style Bulletin PDF</div>
                        <div class="main-card-subtitle">Choisir le format de bulletin utilise pour les PDFs et les previews.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('esbtp.bulletin-style.update') }}">
        @csrf
        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <label class="w-100">
                    <div class="style-card {{ $currentStyle === 'yakro' ? 'is-selected' : '' }}">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-bold">ESBTP Yakro</div>
                            <span class="style-badge">Modele actuel</span>
                        </div>
                        <div class="style-preview mb-3">
                            Style classique existant (layout Yakro), avec table et structure actuelles.
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="bulletin_style" id="bulletin_style_yakro" value="yakro" {{ $currentStyle === 'yakro' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_style_yakro">Activer ce style</label>
                        </div>
                    </div>
                </label>
            </div>
            <div class="col-12 col-lg-6">
                <label class="w-100">
                    <div class="style-card {{ $currentStyle === 'abidjan' ? 'is-selected' : '' }}">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-bold">ESBTP Abidjan</div>
                            <span class="style-badge">Nouveau</span>
                        </div>
                        <div class="style-preview mb-3">
                            Style moderne et epure avec en-tete, logo a gauche, informations a droite et blocs arrondis.
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="bulletin_style" id="bulletin_style_abidjan" value="abidjan" {{ $currentStyle === 'abidjan' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_style_abidjan">Activer ce style</label>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-primary" type="submit">
                <i class="fas fa-save me-2"></i>Enregistrer
            </button>
        </div>
    </form>
</div>
@endsection
