@extends('layouts.app')

@section('title', 'Créer une classe - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Créer une nouvelle classe</h1>
                <p class="header-subtitle">Formulaire de création (LMD-aware : Mention/Parcours en Licence/Master, Filière en BTS)</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.student.classes.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <div class="card-moderne">
            <div class="p-lg">
                @if ($errors->any())
                    <div class="alert alert-danger d-flex align-items-center glass-alert mb-4">
                        <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @include('esbtp.classes.partials.form', [
                    'isModal' => false,
                    'classe' => null,
                    'filieres' => $filieres,
                    'niveaux' => $niveaux,
                    'annees' => $annees,
                    'mentions' => $mentions ?? collect(),
                    'parcours' => $parcours ?? collect(),
                ])
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Auto-genere code de classe depuis le nom (preservation du comportement legacy)
        const nameInput = document.getElementById('create-classe-form_name');
        const codeInput = document.getElementById('create-classe-form_code');
        if (nameInput && codeInput) {
            nameInput.addEventListener('blur', function () {
                if (codeInput.value === '') {
                    const name = nameInput.value;
                    if (name) {
                        codeInput.value = name.split(' ')
                            .map(word => word.charAt(0).toUpperCase())
                            .join('');
                    }
                }
            });
        }
    });
</script>
@endsection
