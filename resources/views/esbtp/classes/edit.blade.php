@extends('layouts.app')

@section('title', 'Modifier une classe - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Modifier la classe</h1>
                <p class="header-subtitle">{{ $classe->name }}</p>
            </div>
            <div class="header-actions">
                @php
                    $returnUrl = request()->input('return_url', route('esbtp.classes.show', ['classe' => $classe->id]));
                    $queryParams = request()->query();
                    unset($queryParams['return_url']);
                @endphp
                <a href="{{ route('esbtp.classes.show', array_merge(['classe' => $classe->id], $queryParams)) }}" class="btn-acasi info">
                    <i class="fas fa-eye"></i>Voir les détails
                </a>
                <a href="{{ $returnUrl }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour
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
                    'classe' => $classe,
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
    // Note: le bouton submit, le code auto-generation, et la gestion x-data
    // sont desormais geres par le partial 'esbtp.classes.partials.form'.
</script>
@endsection
