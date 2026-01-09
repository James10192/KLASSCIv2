@extends('layouts.app')

@section('title', config('app.name', 'KLASSCI') . ' - Accès refusé')

@section('page_title', 'Accès refusé')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="display-1 fw-bold text-danger mb-3">403</div>
                    <h2 class="mb-4">Accès refusé</h2>
                    <p class="mb-4 text-muted">
                        {{ $exception->getMessage() ?: "Vous n'avez pas les autorisations nécessaires pour accéder à cette page." }}
                    </p>
                    <div class="mt-4">
                        <a href="{{ url('/') }}" class="btn btn-primary">Retour à l'accueil</a>
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary ms-2">Tableau de bord</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
