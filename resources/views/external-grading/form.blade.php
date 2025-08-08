<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saisie des notes - {{ $evaluation->titre }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .external-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .header-section {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
        }
        .grade-input {
            max-width: 100px;
            text-align: center;
            font-weight: 600;
        }
        .student-row {
            transition: all 0.3s ease;
            border-radius: 10px;
            margin-bottom: 0.5rem;
        }
        .student-row:hover {
            background-color: rgba(79, 70, 229, 0.05);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                <div class="external-card">
                    <!-- Header -->
                    <div class="header-section">
                        <div class="text-center">
                            <i class="fas fa-clipboard-list fa-3x mb-3 opacity-75"></i>
                            <h1 class="h3 mb-2">{{ $evaluation->titre }}</h1>
                            <p class="mb-1 opacity-90">
                                <i class="fas fa-calendar me-2"></i>{{ $evaluation->date_evaluation->format('d/m/Y') }}
                            </p>
                            <p class="mb-1 opacity-90">
                                <i class="fas fa-users me-2"></i>{{ $evaluation->classe->name ?? 'Classe' }}
                            </p>
                            <p class="mb-0 opacity-90">
                                <i class="fas fa-book me-2"></i>{{ $evaluation->matiere->name ?? 'Matière' }}
                            </p>
                            @if($evaluation->enseignant_externe_nom)
                                <p class="mb-0 opacity-75 mt-2">
                                    <small><i class="fas fa-user-tie me-1"></i>Enseignant : {{ $evaluation->enseignant_externe_nom }}</small>
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-4">
                        @if(session('success'))
                            <div class="alert alert-success border-0 shadow-sm">
                                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger border-0 shadow-sm">
                                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-warning border-0 shadow-sm">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Veuillez corriger les erreurs suivantes :
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('external-grading.store', $evaluation->token_saisie_externe) }}">
                            @csrf
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center p-3 bg-light rounded">
                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                        <div>
                                            <strong>Barème :</strong> {{ $evaluation->bareme }} points<br>
                                            <small class="text-muted">Saisissez les notes de 0 à {{ $evaluation->bareme }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center p-3 bg-light rounded">
                                        <i class="fas fa-users text-success me-2"></i>
                                        <div>
                                            <strong>Étudiants :</strong> {{ $etudiants->count() }}<br>
                                            <small class="text-muted">Laissez vide si l'étudiant était absent</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-primary">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Nom et Prénoms</th>
                                            <th style="width: 150px;" class="text-center">Note / {{ $evaluation->bareme }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($etudiants as $index => $etudiant)
                                        <tr class="student-row">
                                            <td class="align-middle fw-bold text-muted">{{ $index + 1 }}</td>
                                            <td class="align-middle">
                                                <div>
                                                    <span class="fw-medium">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</span>
                                                    @if($etudiant->numero_etudiant)
                                                        <br><small class="text-muted">{{ $etudiant->numero_etudiant }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <input type="number" 
                                                       name="notes[{{ $etudiant->id }}]" 
                                                       value="{{ $notes[$etudiant->id] ?? '' }}"
                                                       class="form-control grade-input @error('notes.'.$etudiant->id) is-invalid @enderror"
                                                       min="0" 
                                                       max="{{ $evaluation->bareme }}" 
                                                       step="0.25"
                                                       placeholder="--">
                                                @error('notes.'.$etudiant->id)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-center pt-4 border-top">
                                <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm">
                                    <i class="fas fa-save me-2"></i>Enregistrer les notes
                                </button>
                                <p class="text-muted mt-3 mb-0">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Toutes les données sont sécurisées et cryptées
                                </p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-4">
                    <p class="text-white mb-1">
                        <i class="fas fa-university me-2"></i>ESBTP - Saisie de notes sécurisée
                    </p>
                    <small class="text-white opacity-75">
                        Ce lien est temporaire et expire le {{ $evaluation->token_expire_at->format('d/m/Y à H:i') }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto focus on first empty grade input
        document.addEventListener('DOMContentLoaded', function() {
            const firstEmptyInput = document.querySelector('input[name^="notes"]:not([value])');
            if (firstEmptyInput) {
                firstEmptyInput.focus();
            }
        });

        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.matches('input[name^="notes"]')) {
                e.preventDefault();
                const inputs = Array.from(document.querySelectorAll('input[name^="notes"]'));
                const currentIndex = inputs.indexOf(e.target);
                const nextInput = inputs[currentIndex + 1];
                if (nextInput) {
                    nextInput.focus();
                    nextInput.select();
                }
            }
        });
    </script>
</body>
</html>