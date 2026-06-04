<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KLASSCI - Saisie des notes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
    <style>
        body {
            background-color: var(--background);
            min-height: 100vh;
        }
        .external-container {
            max-width: 1200px;
        }
        .info-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
            background: rgba(4, 83, 203, 0.12);
            color: #0f172a;
            border: 1px solid rgba(4, 83, 203, 0.25);
        }
        .info-pill.secondary {
            background: rgba(100, 116, 139, 0.12);
            color: #334155;
            border-color: rgba(100, 116, 139, 0.25);
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
        .table thead th {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="dashboard-acasi">
        <div class="main-content external-container mx-auto" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">
            <div class="dashboard-header mb-4">
                <div class="header-left">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="Logo KLASSCI" style="width: 52px; height: auto;">
                            <div>
                                <div class="fw-bold text-uppercase" style="letter-spacing: 0.6px;">KLASSCI</div>
                                <div class="small text-muted">Saisie externe sécurisée</div>
                            </div>
                        </div>
                    </div>
                    <h1 class="mt-3"><i class="fas fa-clipboard-list me-2"></i>Saisie des notes</h1>
                    <p class="header-subtitle">{{ $evaluation->titre }}</p>
                </div>
                <div class="header-actions">
                    <span class="info-pill">
                        <i class="fas fa-calendar"></i>
                        {{ $evaluation->date_evaluation?->format('d/m/Y') ?? 'Date à confirmer' }}
                    </span>
                    <span class="info-pill secondary">
                        <i class="fas fa-clock"></i>
                        Expire le {{ $evaluation->token_expire_at->format('d/m/Y à H:i') }}
                    </span>
                </div>
            </div>

            <div class="card-moderne mb-4">
                <div class="p-lg">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="info-pill">
                            <i class="fas fa-users"></i>{{ $evaluation->classe->name ?? 'Classe' }}
                        </span>
                        <span class="info-pill">
                            <i class="fas fa-book"></i>{{ $evaluation->matiere->name ?? 'Matière' }}
                        </span>
                        <span class="info-pill">
                            <i class="fas fa-info-circle"></i>Barème {{ $evaluation->bareme }} pts
                        </span>
                        <span class="info-pill secondary">
                            <i class="fas fa-user-graduate"></i>{{ $etudiants->count() }} étudiants
                        </span>
                        @if($evaluation->enseignant_externe_nom)
                            <span class="info-pill secondary">
                                <i class="fas fa-user-tie"></i>{{ $evaluation->enseignant_externe_nom }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-moderne">
                <div class="p-lg">
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

                        <div class="row g-3 mb-4">
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
                            <table class="table table-hover align-middle">
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
                                                   step="0.01"
                                                   inputmode="decimal"
                                                   lang="fr"
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

                        <div class="d-flex flex-column align-items-center gap-2 pt-4 border-top">
                            <button type="submit" class="btn-acasi primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Enregistrer les notes
                            </button>
                            <p class="text-muted mb-0">
                                <i class="fas fa-shield-alt me-1"></i>
                                Données sécurisées via KLASSCI
                            </p>
                        </div>
                    </form>
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
