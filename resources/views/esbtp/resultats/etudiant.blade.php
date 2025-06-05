@extends('layouts.app')

@section('title', 'Résultats de ' . $etudiant->nom . ' ' . $etudiant->prenoms)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-graduate me-2 text-primary"></i>Résultats de {{ $etudiant->nom }} {{ $etudiant->prenoms }}</h5>
                    @if(isset($classe) && $classe)
                    <a href="{{ route('esbtp.resultats.classe', ['classe' => $classe->id]) }}?periode={{ $periode }}&annee_universitaire_id={{ $annee_id }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la classe
                    </a>
                    @else
                    <a href="{{ route('esbtp.resultats.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour aux résultats
                    </a>
                    @endif
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Filtres -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="fas fa-filter me-2 text-secondary"></i>Filtrer les résultats</h6>
                                    <form action="{{ route('esbtp.resultats.etudiant', $etudiant) }}" method="GET" class="row">
                                        <div class="col-md-3 mb-2">
                                            <label for="classe_id" class="form-label">Classe :</label>
                                            <select class="form-select shadow-sm" id="classe_id" name="classe_id">
                                                @foreach($classes ?? [] as $c)
                                                    <option value="{{ $c->id }}" {{ isset($classe_id) && $classe_id == $c->id ? 'selected' : '' }}>
                                                        {{ $c->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="annee_universitaire_id" class="form-label">Année Universitaire :</label>
                                            <select class="form-select shadow-sm" id="annee_universitaire_id" name="annee_universitaire_id">
                                                @foreach($anneesUniversitaires ?? [] as $annee)
                                                    <option value="{{ $annee->id }}" {{ isset($annee_id) && $annee_id == $annee->id ? 'selected' : '' }}>
                                                        {{ $annee->annee_debut }}-{{ $annee->annee_fin }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="periode" class="form-label">Période :</label>
                                            <select class="form-select shadow-sm" id="periode" name="periode">
                                                @foreach($periodes ?? [] as $p)
                                                    <option value="{{ $p->id }}" {{ isset($periode) && $periode == $p->id ? 'selected' : '' }}>
                                                        {{ $p->nom }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                                <i class="fas fa-filter me-1"></i>Appliquer
                                            </button>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="include_all_statuses" name="include_all_statuses" value="1" {{ isset($include_all_statuses) && $include_all_statuses ? 'checked' : '' }}>
                                                <label class="form-check-label" for="include_all_statuses">
                                                    Inclure tous les étudiants (même ceux avec des inscriptions inactives)
                                                </label>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-5">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Informations de l'étudiant</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                            <i class="fas fa-user text-primary fs-4"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</h5>
                                            <p class="text-muted mb-0">{{ $etudiant->matricule }}</p>
                                        </div>
                                    </div>
                                    <div class="border-start border-4 border-primary ps-3 py-1 mb-3">
                                        <p class="text-muted mb-0">Classe</p>
                                        <h6 class="mb-0">{{ isset($classe) && $classe ? $classe->name : 'Non définie' }}</h6>
                                    </div>
                                    <div class="d-flex mb-2">
                                        <div class="me-3">
                                            <p class="mb-1 text-muted small">Filière</p>
                                            <p class="mb-0 fw-bold">{{ isset($classe) && $classe && isset($classe->filiere) ? $classe->filiere->name : 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="mb-1 text-muted small">Niveau</p>
                                            <p class="mb-0 fw-bold">{{ isset($classe) && $classe && isset($classe->niveau) ? $classe->niveau->name : 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header {{ $moyenneGenerale >= 10 ? 'bg-success' : 'bg-danger' }} text-white">
                                    <h6 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Résultats {{ $periodes[array_search($periode, array_column($periodes, 'id'))]->nom ?? $periode }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="text-center mb-3">
                                                <div class="bg-light p-3 rounded">
                                                    <h6 class="text-muted mb-1">Moyenne générale</h6>
                                                    <h1 class="{{ $moyenneGenerale >= 10 ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($moyenneGenerale, 2) }}<small>/20</small>
                                                    </h1>
                                                    <div class="progress mt-2" style="height: 8px;">
                                                        <div class="progress-bar {{ $moyenneGenerale >= 10 ? 'bg-success' : 'bg-danger' }}" role="progressbar" style="width: {{ min($moyenneGenerale * 5, 100) }}%" aria-valuenow="{{ $moyenneGenerale }}" aria-valuemin="0" aria-valuemax="20"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex flex-column justify-content-center h-100">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="text-muted">Matières évaluées</span>
                                                    <span class="badge bg-primary px-3 py-2 rounded-pill">{{ count($notesByMatiere) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="text-muted">Nombre d'évaluations</span>
                                                    <span class="badge bg-info px-3 py-2 rounded-pill">{{ $notes->count() }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted">Décision</span>
                                                    <span class="badge {{ $moyenneGenerale >= 10 ? 'bg-success' : 'bg-danger' }} px-3 py-2 rounded-pill">
                                                        {{ $moyenneGenerale >= 10 ? 'Admis' : 'Non admis' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Résultats par matière -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-book me-2 text-primary"></i>Résultats par matière</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%">#</th>
                                            <th style="width: 30%">Matière</th>
                                            <th style="width: 15%" class="text-center">Nombre d'évaluations</th>
                                            <th style="width: 15%" class="text-center">Coefficient total</th>
                                            <th style="width: 15%" class="text-center">Moyenne</th>
                                            <th style="width: 20%" class="text-center">Observations</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $i = 1; @endphp
                                        @forelse($notesByMatiere as $matiere_id => $matiereData)
                                            <tr class="{{ $i % 2 == 0 ? 'bg-white' : 'bg-light' }}">
                                                <td>{{ $i++ }}</td>
                                                <td>{{ $matiereData['matiere']->name }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-info px-3 py-2 rounded-pill">{{ count($matiereData['notes']) }}</span>
                                                </td>
                                                <td class="text-center">{{ $matiereData['total_coefficients'] }}</td>
                                                <td class="text-center">
                                                    <span class="badge rounded-pill {{ $matiereData['moyenne'] >= 10 ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                                                        {{ number_format($matiereData['moyenne'], 2) }}/20
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @if($matiereData['moyenne'] >= 16)
                                                        <span class="badge bg-success px-3 py-2">Excellent</span>
                                                    @elseif($matiereData['moyenne'] >= 14)
                                                        <span class="badge bg-primary px-3 py-2">Très bien</span>
                                                    @elseif($matiereData['moyenne'] >= 12)
                                                        <span class="badge bg-info px-3 py-2">Bien</span>
                                                    @elseif($matiereData['moyenne'] >= 10)
                                                        <span class="badge bg-warning text-dark px-3 py-2">Passable</span>
                                                    @else
                                                        <span class="badge bg-danger px-3 py-2">Insuffisant</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                                                        <p>Aucune note trouvée pour cet étudiant</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        @if(count($notesByMatiere) > 0)
                                            <tr class="bg-light">
                                                <th colspan="3" class="text-end">Total des coefficients :</th>
                                                <th class="text-center">{{ array_sum(array_column($notesByMatiere, 'total_coefficients')) }}</th>
                                                <th colspan="2"></th>
                                            </tr>
                                            <tr class="bg-primary text-white">
                                                <th colspan="4" class="text-end">Moyenne générale :</th>
                                                <th class="text-center" colspan="2">{{ number_format($moyenneGenerale, 2) }}/20</th>
                                            </tr>
                                        @endif
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Détail des notes par matière -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-list-check me-2 text-primary"></i>Détail des évaluations</h5>
                        </div>
                        <div class="card-body">
                            @forelse($notesByMatiere as $matiere_id => $matiereData)
                                <div class="card mb-3 border">
                                    <div class="card-header {{ $matiereData['moyenne'] >= 10 ? 'bg-success' : 'bg-danger' }} text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">{{ $matiereData['matiere']->name }}</h6>
                                            <span class="badge bg-white text-{{ $matiereData['moyenne'] >= 10 ? 'success' : 'danger' }} px-3 py-2">
                                                Moyenne: {{ number_format($matiereData['moyenne'], 2) }}/20
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 35%">Évaluation</th>
                                                        <th style="width: 15%">Type</th>
                                                        <th style="width: 15%">Date</th>
                                                        <th style="width: 10%" class="text-center">Coefficient</th>
                                                        <th style="width: 10%" class="text-center">Note</th>
                                                        <th style="width: 15%" class="text-center">Note pondérée</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($matiereData['notes'] as $note)
                                                        <tr>
                                                            <td>{{ $note->evaluation->title }}</td>
                                                            <td><span class="badge bg-secondary">{{ $note->evaluation->type }}</span></td>
                                                            <td>{{ $note->evaluation->date_evaluation ? $note->evaluation->date_evaluation->format('d/m/Y') : 'N/A' }}</td>
                                                            <td class="text-center">
                                                                <span class="badge bg-primary px-2 py-1 rounded-pill">{{ $note->evaluation->coefficient }}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge {{ (is_numeric($note->valeur) ? $note->valeur : ($note->note ?? 0)) >= 10 ? 'bg-success' : 'bg-danger' }} px-2 py-1 rounded-pill">
                                                                    @if(is_numeric($note->valeur))
                                                                        {{ $note->valeur }}/{{ $note->evaluation->bareme }}
                                                                    @elseif(is_numeric($note->note))
                                                                        {{ $note->note }}/{{ $note->evaluation->bareme }}
                                                                    @else
                                                                        {{ $note->valeur }}
                                                                    @endif
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                @php
                                                                    // Utiliser la bonne source de note avec fallback
                                                                    $noteValue = is_numeric($note->note) ? $note->note : $note->valeur;
                                                                    $bareme = $note->evaluation->bareme > 0 ? $note->evaluation->bareme : 20;
                                                                    $coefficient = $note->evaluation->coefficient ?? 1;

                                                                    if(is_numeric($noteValue)) {
                                                                        $ponderation = ($noteValue / $bareme) * 20 * $coefficient;
                                                                    } else {
                                                                        $ponderation = 0; // Gestion des valeurs non numériques
                                                                    }
                                                                @endphp
                                                                {{ number_format($ponderation, 2) }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucune note trouvée pour cet étudiant dans cette classe pour cette période.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            @if(isset($classe) && $classe)
                            <a href="{{ route('esbtp.resultats.classe', ['classe' => $classe->id]) }}?periode={{ $periode }}&annee_universitaire_id={{ $annee_id }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour aux résultats de la classe
                            </a>
                            @else
                            <a href="{{ route('esbtp.resultats.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour aux résultats
                            </a>
                            @endif
                        </div>
                        <div class="col-md-6 text-end">
                            @if(isset($classe) && $classe)
                                @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                                <a href="{{ route('esbtp.bulletins.moyennes-preview', ['etudiant_id' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}" class="btn btn-warning shadow-sm me-2">
                                    <i class="fas fa-edit me-2"></i>Modifier les moyennes
                                </a>
                                @endif

                                @if(auth()->user()->hasRole('superAdmin'))
                                <a href="{{ route('esbtp.bulletins.config-matieres', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}" class="btn btn-info shadow-sm me-2">
                                    <i class="fas fa-cog me-2"></i>Configurer Matières
                                </a>
                                <a href="{{ route('esbtp.bulletins.edit-professeurs', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}" class="btn btn-primary shadow-sm me-2">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>Éditer Professeurs
                                </a>
                                @endif

                                <a href="#" class="btn btn-danger shadow-sm" onclick="window.open('{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}', '_blank')">
                                    <i class="fas fa-file-pdf me-2"></i>Générer le bulletin
                                </a>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Pour accéder aux options avancées (modification des moyennes, configuration des matières, etc.), veuillez sélectionner une classe et une période dans les filtres ci-dessus.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Alertes d'information pour guider l'utilisateur -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Instructions pour la génération du bulletin</h5>
                                <hr>
                                <p>Pour générer un bulletin complet et correct, veuillez suivre ces étapes :</p>
                                <ol>
                                    <li><strong>Configurer les matières</strong> - Classez les matières par type d'enseignement (général et technique)</li>
                                    <li><strong>Modifier les moyennes</strong> - Vérifiez et ajustez les moyennes calculées si nécessaire</li>
                                    <li><strong>Éditer les professeurs</strong> - Ajoutez les noms des professeurs pour chaque matière</li>
                                    <li><strong>Générer le bulletin</strong> - Créez le PDF final avec toutes les informations configurées</li>
                                </ol>
                                <p class="mb-0 text-danger"><i class="fas fa-exclamation-triangle me-1"></i> <strong>Important :</strong> Si vous ne configurez pas les matières et les professeurs, le bulletin généré pourrait être incomplet ou incorrect.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Activer les tooltips Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endpush
@endsection
