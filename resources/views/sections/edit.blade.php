@extends('layouts.app')

@section('title', 'Modifier une Section')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-esbtp-green text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>Modifier la Section: {{ $section->name }}
                    </h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('sections.update', $section->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Classe associée -->
                        <div class="mb-3">
                            <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                            <select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required>
                                <option value="">Sélectionner une classe</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ old('class_id', $section->class_id) == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }} ({{ $class->level }}) - {{ $class->academic_year }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>La classe à laquelle cette section appartient
                            </small>
                            @error('class_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Nom de la section -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom de la section <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $section->name) }}" required>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>Exemple: Section A, Groupe 1, etc.
                            </small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Capacité -->
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacité maximale</label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', $section->capacity) }}" min="1">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>Nombre maximum d'étudiants dans cette section
                            </small>
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $section->description) }}</textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>Une brève description de cette section (optionnel)
                            </small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Enseignant responsable -->
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Enseignant responsable</label>
                            <select class="form-select @error('teacher_id') is-invalid @enderror" id="teacher_id" name="teacher_id">
                                <option value="">Sélectionner un enseignant (optionnel)</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id', $section->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }} ({{ $teacher->email }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>L'enseignant responsable de cette section (optionnel)
                            </small>
                            @error('teacher_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Salle de classe -->
                        <div class="mb-3">
                            <label for="room" class="form-label">Salle de classe</label>
                            <input type="text" class="form-control @error('room') is-invalid @enderror" id="room" name="room" value="{{ old('room', $section->room) }}">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>La salle de classe principale pour cette section (optionnel)
                            </small>
                            @error('room')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Statut -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="active" {{ old('status', $section->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $section->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>Le statut actuel de cette section
                            </small>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Boutons -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('esbtp.classes.edit', $section->class_id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Liste des étudiants dans cette section -->
            <div class="card mt-4">
                <div class="card-header bg-esbtp-orange text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-graduate me-2"></i>Étudiants dans cette section
                    </h5>
                    <a href="{{ route('students.create', ['section_id' => $section->id]) }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus-circle me-1"></i>Ajouter un étudiant
                    </a>
                </div>
                <div class="card-body">
                    @if($section->students && $section->students->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Date d'admission</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($section->students as $student)
                                        <tr>
                                            <td>{{ $student->registration_number }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($student->profile_image)
                                                        <img src="{{ asset('storage/' . $student->profile_image) }}" alt="{{ $student->name }}" class="rounded-circle me-2" width="32" height="32">
                                                    @else
                                                        <div class="bg-esbtp-orange-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                            <span class="text-esbtp-orange">{{ substr($student->name, 0, 1) }}</span>
                                                        </div>
                                                    @endif
                                                    {{ $student->name }}
                                                </div>
                                            </td>
                                            <td>{{ $student->email }}</td>
                                            <td>{{ $student->admission_date ? date('d/m/Y', strtotime($student->admission_date)) : 'N/A' }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('students.show', $student->id) }}" class="btn btn-sm btn-info text-white" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('students.edit', $student->id) }}" class="btn btn-sm btn-warning text-white" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Aucun étudiant n'est inscrit dans cette section.
                            <div class="mt-2">
                                <a href="{{ route('students.create', ['section_id' => $section->id]) }}" class="btn btn-sm btn-esbtp-orange">
                                    <i class="fas fa-plus-circle me-1"></i>Ajouter un étudiant
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Script pour valider le formulaire côté client
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validation de la classe
            const classSelect = document.getElementById('class_id');
            if (!classSelect.value) {
                isValid = false;
                classSelect.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.classList.add('invalid-feedback');
                feedback.textContent = 'La classe est requise';
                classSelect.parentNode.appendChild(feedback);
            }
            
            // Validation du nom
            const nameInput = document.getElementById('name');
            if (!nameInput.value.trim()) {
                isValid = false;
                nameInput.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.classList.add('invalid-feedback');
                feedback.textContent = 'Le nom de la section est requis';
                nameInput.parentNode.appendChild(feedback);
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    });
</script>
@endpush 