@extends('layouts.app')

@section('title', 'Mon espace familial')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h1 class="h3">Mon espace familial</h1>
        <p class="text-muted">Les dossiers que votre établissement vous autorise explicitement à consulter.</p>
    </div>
    <div class="row g-3">
        @forelse($grants as $grant)
            <div class="col-12 col-lg-6">
                <article class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-user-graduate text-primary" aria-hidden="true"></i>
                            <h2 class="h5 mb-0">{{ $grant->etudiant->nom_complet }}</h2>
                        </div>
                        <p class="mb-2">Matricule : <strong>{{ $grant->etudiant->matricule ?: 'Non attribué' }}</strong></p>
                        <p class="mb-0 text-muted small">Accès d'identité uniquement. Les notes, documents et informations financières ne sont pas accessibles depuis cet espace tant que leurs droits spécifiques ne sont pas définis.</p>
                        <p class="mt-3 mb-0 text-muted small">Autorisation valable jusqu'au {{ $grant->expires_at->format('d/m/Y') }}</p>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info" role="status">
                    Aucun dossier n'est actuellement autorisé pour votre compte. Contactez la scolarité pour faire vérifier votre relation avec l'étudiant.
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
