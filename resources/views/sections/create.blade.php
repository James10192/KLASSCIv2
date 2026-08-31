@extends('layouts.app')

@section('title', 'Créer une Section')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-esbtp-green text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Créer une Nouvelle Section
                        @if(isset($class))
                            pour la classe <strong>{{ $class->name }}</strong>
                        @endif
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

                    <form action="{{ route('sections.store') }}" method="POST">
                        @csrf
                        
                        <!-- Classe associée -->
                        <div class="mb-3">
                            <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                            <select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required {{ isset($class) ? 'disabled' : '' }}>
                                <option value="">Sélectionner une classe</option>
                                @foreach($classes as $classOption)
                                    <option value="{{ $classOption->id }}" {{ (old('class_id') == $classOption->id || (isset($class) && $class->id == $classOption->id)) ? 'selected' : '' }}>
                                        {{ $classOption->name }} ({{ $classOption->level }}) - {{ $classOption->academic_year }}
                                    </option>
                                @endforeach
                            </select>
                            @if(isset($class))
                                <input type="hidden" name="class_id" value="{{ $class->id }}">
                            @endif
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
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
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
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', 30) }}" min="1">
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
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
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
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
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
                            <input type="text" class="form-control @error('room') is-invalid @enderror" id="room" name="room" value="{{ old('room') }}">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>La salle de classe principale pour cette section (optionnel)
                            </small>
                            @error('room')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Boutons -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ isset($class) ? route('esbtp.classes.edit', $class->id) : route('sections.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Enregistrer
                            </button>
                        </div>
                    </form>
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
            if (!classSelect.disabled && !classSelect.value) {
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