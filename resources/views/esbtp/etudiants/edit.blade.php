@extends('layouts.app')

@section('title', 'Modifier un étudiant - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Modifier l'étudiant</h1>
                <p class="header-subtitle">{{ $etudiant->nom }} {{ $etudiant->prenoms }} - Matricule: {{ $etudiant->matricule ?? 'N/A' }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.etudiants.show', $etudiant) }}" class="btn-acasi info me-2">
                    <i class="fas fa-eye"></i>Voir les détails
                </a>
                <a href="{{ route('esbtp.etudiants.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <div class="card-moderne">
            <div class="p-lg">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @include('esbtp.etudiants.partials.edit-form', ['etudiant' => $etudiant, 'isEmbedded' => false])

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('esbtp.etudiants.partials.edit-form-scripts')
@endpush
