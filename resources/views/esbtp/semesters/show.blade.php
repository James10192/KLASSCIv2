@extends('layouts.app')

@section('title', 'Détails du Semestre')
@section('page_title', 'Informations du Semestre')

@section('content')
<div class="row">
    <div class="col-md-4">
        <!-- Carte d'information du semestre -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">{{ $semester->name }}</h5>
                <span class="badge {{ $semester->is_active ? 'bg-success' : 'bg-danger' }}">
                    {{ $semester->is_active ? 'Actif' : 'Inactif' }}
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column">
                    <div class="mb-3">
                        <strong><i class="fas fa-graduation-cap me-2"></i> Année d'études:</strong>
                        <p>
                            @if($semester->studyYear)
                                <a href="{{ route('esbtp.study-years.show', $semester->studyYear) }}">
                                    {{ $semester->studyYear->name }}
                                </a>
                            @else
                                <span class="text-muted">Non définie</span>
                            @endif
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-sort-numeric-up me-2"></i> Ordre:</strong>
                        <p>{{ $semester->order }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-book me-2"></i> Nombre de cours:</strong>
                        <p>{{ $semester->courses_count ?? $semester->courses->count() ?? 0 }}</p>
                    </div>
                    
                    @if($semester->studyYear && $semester->studyYear->specialty)
                    <div class="mb-3">
                        <strong><i class="fas fa-bookmark me-2"></i> Spécialité:</strong>
                        <p>{{ $semester->studyYear->specialty->name }}</p>
                    </div>
                    @endif
                    
                    @if($semester->studyYear && $semester->studyYear->cycle)
                    <div class="mb-3">
                        <strong><i class="fas fa-sync-alt me-2"></i> Cycle:</strong>
                        <p>{{ $semester->studyYear->cycle->name }}</p>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('esbtp.semesters.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <div>
                        <a href="{{ route('esbtp.semesters.edit', $semester) }}" class="btn btn-warning">
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
                @if($semester->description)
                    <div class="mb-4">
                        {!! nl2br(e($semester->description)) !!}
                    </div>
                @else
                    <p class="text-muted">Aucune description disponible pour ce semestre.</p>
                @endif
                
                <hr>
                
                <h5>Informations complémentaires</h5>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong><i class="fas fa-calendar-alt me-2"></i> Créé le:</strong>
                            <p>{{ $semester->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong><i class="fas fa-edit me-2"></i> Dernière modification:</strong>
                            <p>{{ $semester->updated_at->format('d/m/Y à H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Liste des cours -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Cours du semestre</h5>
                <a href="#" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Ajouter un cours
                </a>
            </div>
            <div class="card-body">
                @if($semester->courses && $semester->courses->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Intitulé</th>
                                    <th>Crédits</th>
                                    <th>Heures</th>
                                    <th>Enseignant</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($semester->courses as $course)
                                    <tr>
                                        <td>{{ $course->code }}</td>
                                        <td>{{ $course->name }}</td>
                                        <td>{{ $course->credits }}</td>
                                        <td>{{ $course->hours }}</td>
                                        <td>{{ $course->teacher_name ?? 'Non assigné' }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="#" class="btn btn-info btn-sm" title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="#" class="btn btn-warning btn-sm" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Aucun cours n'est associé à ce semestre pour le moment.
                    </div>
                    <div class="text-center">
                        <a href="#" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Ajouter le premier cours
                        </a>
                    </div>
                @endif
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
                <p>Êtes-vous sûr de vouloir supprimer le semestre <strong>{{ $semester->name }}</strong> ?</p>
                <p class="text-danger">Cette action est réversible, mais supprimera temporairement l'accès à ce semestre et à ses cours associés.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('esbtp.semesters.destroy', $semester) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 