@extends('layouts.app')

@section('title', 'Modifier l\'inscription - ' . $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .select2-container {
        width: 100% !important;
    }
    .select2-selection {
        height: 38px !important;
        border: 1px solid #ced4da !important;
    }
    .select2-selection__rendered {
        line-height: 36px !important;
    }
    .select2-selection__arrow {
        height: 36px !important;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</h1>
                <p class="header-subtitle">Modification de l'inscription - Matricule: {{ $inscription->etudiant->matricule }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" class="btn-acasi info me-2">
                    <i class="fas fa-eye"></i>Voir les détails
                </a>
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <!-- Formulaire de modification d'inscription -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Modifier l'inscription</h6>
            </div>
            <div class="card-body">
                @include('esbtp.inscriptions.partials.edit-form', [
                    'inscription' => $inscription,
                    'filieres' => $filieres,
                    'niveaux' => $niveaux,
                    'classes' => $classes,
                    'annees' => $annees,
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
