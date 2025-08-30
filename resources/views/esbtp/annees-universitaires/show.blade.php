@extends('layouts.app')

@section('title', 'Détails de l\'année universitaire : ' . $anneesUniversitaire->name)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-calendar-alt me-2"></i>Détails de l'année universitaire</h1>
                <p class="header-subtitle">{{ $anneesUniversitaire->name }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.annees-universitaires.edit', $anneesUniversitaire) }}" class="btn-acasi primary">
                    <i class="fas fa-edit"></i>Modifier
                </a>
                @if(!optional($anneesUniversitaire)->is_current)
                    <form action="{{ route('esbtp.annees-universitaires.set-current', $anneesUniversitaire) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn-acasi success">
                            <i class="fas fa-calendar-check"></i>Définir comme année en cours
                        </button>
                    </form>
                @endif
                <a href="{{ route('esbtp.annees-universitaires.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Période</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.8rem; font-weight: bold;">{{ $anneesUniversitaire->start_date ? $anneesUniversitaire->start_date->format('d/m/Y') : '-' }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="far fa-calendar-alt"></i>
                    au {{ $anneesUniversitaire->end_date->format('d/m/Y') }}
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Statut</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ optional($anneesUniversitaire)->is_current ? '✓' : '✗' }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calendar-check"></i>
                    {{ optional($anneesUniversitaire)->is_current ? 'Année en cours' : 'Année non courante' }}
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Inscriptions</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $anneesUniversitaire->inscriptions->count() }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    Étudiants inscrits
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Activation</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $anneesUniversitaire->is_active ? '✓' : '✗' }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-toggle-on"></i>
                    {{ $anneesUniversitaire->is_active ? 'Active' : 'Inactive' }}
                </div>
            </div>
        </div>

        <!-- Section principale des détails -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-info-circle"></i>
                    Informations détaillées
                </div>
                <div class="main-card-subtitle">Détails complets de l'année universitaire {{ $anneesUniversitaire->name }}</div>
            </div>

            <div class="main-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-align-left me-2"></i>Description</h6>
                        <div class="alert alert-light mb-4">
                            @if($anneesUniversitaire->description)
                                {{ $anneesUniversitaire->description }}
                            @else
                                <em class="text-muted">Aucune description disponible.</em>
                            @endif
                        </div>
                        
                        <h6><i class="fas fa-info-circle me-2"></i>Informations système</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <strong>Créée le :</strong>
                                <span class="badge bg-info px-3 py-2">{{ $anneesUniversitaire->created_at->format('d/m/Y à H:i') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <strong>Dernière modification :</strong>
                                <span class="badge bg-warning text-dark px-3 py-2">{{ $anneesUniversitaire->updated_at->format('d/m/Y à H:i') }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-users me-2"></i>Gestion des inscriptions</h6>
                        <div class="alert {{ $anneesUniversitaire->inscriptions->count() > 0 ? 'alert-info' : 'alert-warning' }}">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas {{ $anneesUniversitaire->inscriptions->count() > 0 ? 'fa-info-circle' : 'fa-exclamation-triangle' }} fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-2"><strong>{{ $anneesUniversitaire->inscriptions->count() }} inscription(s)</strong> pour cette année universitaire.</p>
                                    @if($anneesUniversitaire->inscriptions->count() > 0)
                                        <p class="mb-0">Cette année universitaire contient des inscriptions actives. La suppression ne sera possible qu'après suppression de toutes les inscriptions associées.</p>
                                    @else
                                        <p class="mb-0">Aucune inscription enregistrée pour cette année universitaire.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        @if($anneesUniversitaire->inscriptions->count() > 0)
                            <div class="d-grid gap-2">
                                <a href="{{ route('esbtp.inscriptions.index', ['annee_universitaire_id' => $anneesUniversitaire->id]) }}" class="btn-acasi primary">
                                    <i class="fas fa-list"></i>Voir toutes les inscriptions ({{ $anneesUniversitaire->inscriptions->count() }})
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions de gestion -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-cogs"></i>
                    Actions de gestion
                </div>
                <div class="main-card-subtitle">Opérations avancées sur l'année universitaire</div>
            </div>
            <div class="main-card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Zone de danger</h6>
                            <p class="mb-0">
                                La suppression d'une année universitaire est une action irréversible qui peut affecter 
                                de nombreuses données dans le système. Assurez-vous de bien comprendre les implications 
                                avant de procéder à cette opération.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <button type="button" class="btn-acasi danger w-100" data-bs-toggle="modal" data-bs-target="#deleteModal" {{ $anneesUniversitaire->inscriptions->count() > 0 ? 'disabled' : '' }}>
                            <i class="fas fa-trash"></i>Supprimer cette année
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Attention !</strong> Vous êtes sur le point de supprimer l'année universitaire :
                </div>
                <p class="text-center"><strong class="h5">{{ $anneesUniversitaire->name }}</strong></p>

                @if($anneesUniversitaire->inscriptions->count() > 0)
                    <div class="alert alert-danger">
                        <i class="fas fa-ban"></i>
                        <strong>Suppression impossible !</strong><br>
                        Cette année universitaire possède <strong>{{ $anneesUniversitaire->inscriptions->count() }}</strong> inscription(s). 
                        Vous devez d'abord supprimer toutes les inscriptions associées à cette année.
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-check-circle"></i>
                        Aucune inscription associée. La suppression est possible.
                    </div>
                    <p class="text-danger mb-0"><i class="fas fa-exclamation-circle"></i> <strong>Cette action est irréversible.</strong></p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                @if($anneesUniversitaire->inscriptions->count() == 0)
                    <form action="{{ route('esbtp.annees-universitaires.destroy', $anneesUniversitaire) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Supprimer définitivement
                        </button>
                    </form>
                @else
                    <a href="{{ route('esbtp.inscriptions.index', ['annee_universitaire_id' => $anneesUniversitaire->id]) }}" class="btn btn-warning">
                        <i class="fas fa-list"></i> Voir les inscriptions
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection