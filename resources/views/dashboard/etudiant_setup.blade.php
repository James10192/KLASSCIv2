@extends('layouts.app')

@section('title', 'Configuration du profil étudiant')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Configuration de votre profil étudiant</div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <h5 class="alert-heading">Bienvenue à ESBTP-yAKRO!</h5>
                        <p>Votre compte a été créé avec succès, mais vous devez compléter votre profil étudiant pour accéder à toutes les fonctionnalités.</p>
                    </div>

                    <form method="POST" action="{{ route('esbtp.student.profile.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="registration_number" class="form-label">Numéro d'étudiant</label>
                            <input type="text" class="form-control @error('registration_number') is-invalid @enderror" id="registration_number" name="registration_number" value="{{ old('registration_number') }}" required>
                            @error('registration_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="filiere_id" class="form-label">Filière</label>
                            <select class="form-select @error('filiere_id') is-invalid @enderror" id="filiere_id" name="filiere_id" required>
                                <option value="">Sélectionner une filière</option>
                                @foreach(\App\Models\Filiere::all() as $filiere)
                                    <option value="{{ $filiere->id }}" {{ old('filiere_id') == $filiere->id ? 'selected' : '' }}>{{ $filiere->name }}</option>
                                @endforeach
                            </select>
                            @error('filiere_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="niveau_etude_id" class="form-label">Niveau d'études</label>
                            <select class="form-select @error('niveau_etude_id') is-invalid @enderror" id="niveau_etude_id" name="niveau_etude_id" required>
                                <option value="">Sélectionner un niveau d'études</option>
                                @foreach(\App\Models\NiveauEtude::all() as $niveau)
                                    <option value="{{ $niveau->id }}" {{ old('niveau_etude_id') == $niveau->id ? 'selected' : '' }}>{{ $niveau->name }}</option>
                                @endforeach
                            </select>
                            @error('niveau_etude_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="classe_id" class="form-label">Classe</label>
                            <select class="form-select @error('classe_id') is-invalid @enderror" id="classe_id" name="classe_id" required>
                                <option value="">Sélectionner une classe</option>
                                @foreach(\App\Models\Classe::all() as $classe)
                                    <option value="{{ $classe->id }}" {{ old('classe_id') == $classe->id ? 'selected' : '' }}>{{ $classe->name }}</option>
                                @endforeach
                            </select>
                            @error('classe_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required>
                            @error('date_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="gender" class="form-label">Genre</label>
                            <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                                <option value="">Sélectionner un genre</option>
                                <option value="M" {{ old('gender') == 'M' ? 'selected' : '' }}>Masculin</option>
                                <option value="F" {{ old('gender') == 'F' ? 'selected' : '' }}>Féminin</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Numéro de téléphone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="profile_photo" class="form-label">Photo de profil</label>
                            <input type="file" class="form-control @error('profile_photo') is-invalid @enderror" id="profile_photo" name="profile_photo">
                            @error('profile_photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Enregistrer mon profil</button>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Filtre dynamique des classes en fonction de la filière et du niveau sélectionnés
        const filiereSelect = document.getElementById('filiere_id');
        const niveauSelect = document.getElementById('niveau_etude_id');
        const classeSelect = document.getElementById('classe_id');
        
        function updateClasseOptions() {
            const filiereId = filiereSelect.value;
            const niveauId = niveauSelect.value;
            
            if (filiereId && niveauId) {
                // Désactiver le sélecteur de classe pendant le chargement
                classeSelect.disabled = true;
                
                // Remplacer par un appel AJAX pour récupérer les classes correspondantes
                fetch(`/api/classes?filiere_id=${filiereId}&niveau_etude_id=${niveauId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Vider les options actuelles
                        classeSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
                        
                        // Ajouter les nouvelles options
                        data.forEach(classe => {
                            const option = document.createElement('option');
                            option.value = classe.id;
                            option.textContent = classe.name;
                            classeSelect.appendChild(option);
                        });
                        
                        // Réactiver le sélecteur
                        classeSelect.disabled = false;
                    })
                    .catch(error => {
                        debugError('Erreur lors du chargement des classes:', error);
                        classeSelect.disabled = false;
                    });
            }
        }
        
        filiereSelect.addEventListener('change', updateClasseOptions);
        niveauSelect.addEventListener('change', updateClasseOptions);
    });
</script>
@endpush 