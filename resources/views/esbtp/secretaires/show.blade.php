@extends('layouts.app')

@section('title', 'Détails du Secrétaire')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">Profil du Secrétaire</h1>
        <div>
            <a href="{{ route('esbtp.secretaires.edit', $secretaire->id) }}" class="btn btn-primary me-2">
                <i class="fas fa-edit me-2"></i>Modifier
            </a>
            <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour à la liste
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i>
                    Photo de profil
                </div>
                <div class="card-body text-center">
                    @if($secretaire->profile_photo_path)
                        <img src="{{ Storage::url($secretaire->profile_photo_path) }}" alt="Photo de profil" class="img-fluid rounded-circle mb-3" style="max-width: 200px; max-height: 200px;">
                    @else
                        <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 200px; height: 200px;">
                            <i class="fas fa-user fa-5x text-secondary"></i>
                        </div>
                    @endif
                    <h4>{{ $secretaire->first_name }} {{ $secretaire->last_name }}</h4>
                    <p class="text-muted">{{ $secretaire->username }}</p>
                    <div class="mb-2">
                        <span class="badge {{ $secretaire->is_active ? 'bg-success' : 'bg-danger' }}">
                            {{ $secretaire->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                    <p class="text-muted">
                        <i class="fas fa-calendar-alt me-2"></i>Membre depuis {{ $secretaire->created_at->format('d/m/Y') }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    Informations personnelles
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th style="width: 30%"><i class="fas fa-user me-2"></i>Nom complet</th>
                                    <td>{{ $secretaire->first_name }} {{ $secretaire->last_name }}</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-envelope me-2"></i>Email</th>
                                    <td>{{ $secretaire->email }}</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-phone me-2"></i>Téléphone</th>
                                    <td>{{ $secretaire->phone ?? 'Non renseigné' }}</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-map-marker-alt me-2"></i>Adresse</th>
                                    <td>
                                        @if($secretaire->address || $secretaire->city)
                                            {{ $secretaire->address ?? 'Non renseignée' }}
                                            @if($secretaire->address && $secretaire->city), @endif
                                            {{ $secretaire->city ?? '' }}
                                        @else
                                            Non renseignée
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-user-tag me-2"></i>Rôle</th>
                                    <td>Secrétaire Académique</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-calendar-check me-2"></i>Dernière connexion</th>
                                    <td>
                                        @if($secretaire->last_login_at)
                                            {{ \Carbon\Carbon::parse($secretaire->last_login_at)->format('d/m/Y à H:i') }}
                                        @else
                                            Jamais connecté
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Activités récentes
                </div>
                <div class="card-body">
                    @if(isset($activites) && count($activites) > 0)
                        <ul class="timeline">
                            @foreach($activites as $activite)
                                <li class="timeline-item mb-3">
                                    <span class="timeline-point"></span>
                                    <div class="timeline-content">
                                        <h5 class="mb-1">{{ $activite->description }}</h5>
                                        <p class="text-muted mb-0">{{ $activite->created_at->format('d/m/Y à H:i') }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                            <p class="text-muted">Aucune activité récente enregistrée.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 30px;
        list-style: none;
    }
    
    .timeline-item {
        position: relative;
    }
    
    .timeline-point {
        position: absolute;
        left: -30px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #0d6efd;
        top: 5px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: -24px;
        top: 0;
        height: 100%;
        width: 2px;
        background-color: #e9ecef;
    }
</style>
@endsection 