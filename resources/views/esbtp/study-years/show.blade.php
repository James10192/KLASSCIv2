@extends('layouts.app')

@section('title', 'Détails de l\'Année d\'études')
@section('page_title', 'Informations de l\'Année d\'études')

@section('content')
<div class="row">
    <div class="col-md-4">
        <!-- Carte d'information de l'année d'études -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">{{ $studyYear->name }}</h5>
                <span class="badge {{ $studyYear->is_active ? 'bg-success' : 'bg-danger' }}">
                    {{ $studyYear->is_active ? 'Actif' : 'Inactif' }}
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column">
                    <div class="mb-3">
                        <strong><i class="fas fa-graduation-cap me-2"></i> Cycle:</strong>
                        <p>{{ $studyYear->cycle->name ?? 'Non défini' }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-book me-2"></i> Spécialité:</strong>
                        <p>{{ $studyYear->specialty->name ?? 'Non défini' }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-calendar-alt me-2"></i> Année académique:</strong>
                        <p>{{ $studyYear->academic_year ?? 'Non définie' }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-list-ol me-2"></i> Nombre de semestres:</strong>
                        <p>{{ $studyYear->semesters->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('esbtp.study-years.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <div>
                        <a href="{{ route('esbtp.study-years.edit', $studyYear) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Description et détails -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Description</h5>
            </div>
            <div class="card-body">
                @if($studyYear->description)
                    <div class="mb-4">
                        {!! nl2br(e($studyYear->description)) !!}
                    </div>
                @else
                    <p class="text-muted">Aucune description disponible pour cette année d'études.</p>
                @endif
                
                <hr>
                
                <h5>Informations complémentaires</h5>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong><i class="fas fa-calendar-alt me-2"></i> Créé le:</strong>
                            <p>{{ $studyYear->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong><i class="fas fa-edit me-2"></i> Dernière modification:</strong>
                            <p>{{ $studyYear->updated_at->format('d/m/Y à H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Liste des semestres -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title">Semestres</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Ordre</th>
                                <th>Nombre de cours</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($studyYear->semesters as $semester)
                                <tr>
                                    <td>{{ $semester->name }}</td>
                                    <td>{{ $semester->order }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $semester->courses_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        @if($semester->is_active)
                                            <span class="badge bg-success">Actif</span>
                                        @else
                                            <span class="badge bg-warning">Inactif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('esbtp.semesters.show', $semester) }}" class="btn btn-info btn-sm" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('esbtp.semesters.edit', $semester) }}" class="btn btn-warning btn-sm" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Aucun semestre associé à cette année d'études</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'année d'études <strong>{{ $studyYear->name }}</strong> ?</p>
                <p class="text-danger">Cette action est réversible, mais supprimera temporairement l'accès à cette année d'études et à ses semestres associés.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('esbtp.study-years.destroy', $studyYear) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 