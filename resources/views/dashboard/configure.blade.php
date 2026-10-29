@extends('layouts.app')

@section('title', 'Configurer mon dashboard')

@push('styles')
<style>
.dwc-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
</style>
@endpush

@section('content')
<div class="main-content">
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-sliders-h me-2"></i>Configurer mon dashboard</h1>
            <p class="header-subtitle">Choisissez les widgets visibles sur votre tableau de bord et leur ordre</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('dashboard.widgets.index') }}" class="btn-acasi secondary">
                <i class="fas fa-arrow-left"></i>Retour au tableau de bord
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <div class="dwc-card">
        @include('dashboard._configure-modal', [
            'availableGrouped' => $availableGrouped,
            'activeKeys' => $activeKeys,
        ])

        @if ($availableGrouped->isEmpty())
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle"></i>
                Aucun widget disponible avec vos permissions actuelles.
            </div>
        @else
            <p>Cliquez sur le bouton ci-dessous pour ouvrir la modal de configuration.</p>
            <button type="button" class="btn-acasi primary" data-bs-toggle="modal" data-bs-target="#dwConfigureModal">
                <i class="fas fa-cog"></i> Ouvrir la configuration
            </button>
        @endif
    </div>
</div>
@endsection
