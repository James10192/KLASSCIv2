@extends('layouts.app')

@section('title', 'Résultats de la classe ' . $classe->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Résultats de la classe {{ $classe->name }}</h5>
                    <a href="{{ route('esbtp.resultats.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                    </a>
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
                                    <form action="{{ route('esbtp.resultats.classe', $classe) }}" method="GET" class="row">
                                        <div class="col-md-5 mb-2">
                                            <label for="annee_universitaire_id" class="form-label">Année Universitaire :</label>
                                            <select class="form-select shadow-sm" id="annee_universitaire_id" name="annee_universitaire_id">
                                                @foreach($anneesUniversitaires ?? [] as $annee)
                                                    <option value="{{ $annee->id }}" {{ isset($annee_id) && $annee_id == $annee->id ? 'selected' : '' }}>
                                                        {{ $annee->annee_debut }}-{{ $annee->annee_fin }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-2">
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
                                                <input class="form-check-input" type="checkbox" id="include_all_statuses" name="include_all_statuses" value="1" {{ $include_all_statuses ? 'checked' : '' }}>
                                                <label class="form-check-label" for="include_all_statuses">
                                                    Afficher uniquement les étudiants actifs
                                                </label>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Infos classe -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations de la classe</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                            <i class="fas fa-graduation-cap text-primary fs-4"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0">{{ $classe->name }}</h5>
                                            <p class="text-muted mb-0">{{ $classe->filiere->name ?? 'N/A' }} - {{ $classe->niveau->name ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-6">
                                            <div class="border-start border-4 border-primary ps-3 py-1">
                                                <p class="text-muted mb-0">Étudiants</p>
                                                <h4 class="mb-0">{{ count($resultats) }}</h4>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border-start border-4 border-success ps-3 py-1">
                                                <p class="text-muted mb-0">Période</p>
                                                <h4 class="mb-0">
                                                    @foreach($periodes ?? [] as $p)
                                                        @if(isset($periode) && $periode == $p->id)
                                                            {{ $p->nom }}
                                                        @endif
                                                    @endforeach
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Statistiques</h6>
                                </div>
                                <div class="card-body">
                                    @php
                                        $totalMoyennes = 0;
                                        $countMoyennes = 0;
                                        $min = 20;
                                        $max = 0;
                                        $countSucces = 0;
                                        $countEchec = 0;

                                        foreach ($resultats as $resultat) {
                                            if ($resultat['notes_count'] > 0) {
                                                $totalMoyennes += $resultat['moyenne'];
                                                $countMoyennes++;

                                                $min = min($min, $resultat['moyenne']);
                                                $max = max($max, $resultat['moyenne']);

                                                if ($resultat['moyenne'] >= 10) {
                                                    $countSucces++;
                                                } else {
                                                    $countEchec++;
                                                }
                                            }
                                        }

                                        $moyenneClasse = $countMoyennes > 0 ? $totalMoyennes / $countMoyennes : 0;
                                        $tauxReussite = $countMoyennes > 0 ? ($countSucces / $countMoyennes) * 100 : 0;
                                    @endphp

                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <div class="bg-light p-3 rounded text-center mb-3">
                                                <h6 class="text-muted mb-1">Moyenne de la classe</h6>
                                                <h2 class="mb-0 {{ $moyenneClasse >= 10 ? 'text-success' : 'text-danger' }}">{{ number_format($moyenneClasse, 2) }}<small>/20</small></h2>
                                                <div class="progress mt-2" style="height: 8px;">
                                                    <div class="progress-bar {{ $moyenneClasse >= 10 ? 'bg-success' : 'bg-danger' }}" role="progressbar" style="width: {{ min($moyenneClasse * 5, 100) }}%" aria-valuenow="{{ $moyenneClasse }}" aria-valuemin="0" aria-valuemax="20"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="text-muted">Note minimale</span>
                                                    <span class="text-danger fw-bold">{{ $countMoyennes > 0 ? number_format($min, 2) : 'N/A' }}/20</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted">Note maximale</span>
                                                    <span class="text-success fw-bold">{{ $countMoyennes > 0 ? number_format($max, 2) : 'N/A' }}/20</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center">
                                                <div class="d-inline-block position-relative" style="width: 80px; height: 80px;">
                                                    <div class="position-absolute top-50 start-50 translate-middle text-center">
                                                        <h3 class="mb-0">{{ number_format($tauxReussite, 0) }}%</h3>
                                                        <small class="text-muted">Réussite</small>
                                                    </div>
                                                    <svg width="80" height="80" viewBox="0 0 80 80">
                                                        <circle cx="40" cy="40" r="36" fill="none" stroke="#e9ecef" stroke-width="8"/>
                                                        <circle cx="40" cy="40" r="36" fill="none" stroke="{{ $tauxReussite >= 50 ? '#28a745' : '#dc3545' }}" stroke-width="8" stroke-dasharray="{{ 226.2 * $tauxReussite / 100 }} 226.2" stroke-dashoffset="0" transform="rotate(-90 40 40)"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tableau des résultats -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Liste des étudiants et leurs résultats</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%">#</th>
                                            <th style="width: 15%">Matricule</th>
                                            <th style="width: 20%">Nom</th>
                                            <th style="width: 20%">Prénom</th>
                                            <th style="width: 15%" class="text-center">Moyenne</th>
                                            <th style="width: 15%" class="text-center">Nb. Notes</th>
                                            <th style="width: 10%" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($resultats as $index => $resultat)
                                            <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-light' }}">
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $resultat['etudiant']->matricule }}</td>
                                                <td>{{ $resultat['etudiant']->nom }}</td>
                                                <td>{{ $resultat['etudiant']->prenom }}</td>
                                                <td class="text-center">
                                                    <span class="badge rounded-pill {{ $resultat['moyenne'] >= 10 ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                                                        {{ number_format($resultat['moyenne'], 2) }}/20
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info px-3 py-2 rounded-pill">{{ $resultat['notes_count'] }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('esbtp.resultats.etudiant', $resultat['etudiant']) }}?classe_id={{ $classe->id }}&periode={{ $periode }}&annee_universitaire_id={{ $annee_id }}"
                                                       class="btn btn-sm btn-primary rounded-circle" data-bs-toggle="tooltip" title="Voir les détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                                                    <a href="{{ route('esbtp.bulletins.moyennes-preview', ['etudiant_id' => $resultat['etudiant']->id, 'classe_id' => $classe->id, 'periode' => ($periode == '1' ? 'semestre1' : ($periode == '2' ? 'semestre2' : $periode)), 'annee_universitaire_id' => $annee_id]) }}"
                                                       class="btn btn-sm btn-warning rounded-circle" data-bs-toggle="tooltip" title="Modifier les moyennes">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @endif
                                                    <a href="#" class="btn btn-sm btn-success rounded-circle" data-bs-toggle="tooltip" title="Générer le bulletin"
                                                       onclick="window.open('{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $resultat['etudiant']->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}', '_blank')">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                                                        <p>Aucun résultat trouvé pour cette classe</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Ajouter un bouton pour générer tous les bulletins PDF en bas de page -->
                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('esbtp.bulletins.generer-classe', ['classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}"
                           class="btn btn-danger shadow-sm"
                           onclick="event.preventDefault(); document.getElementById('generate-bulletins-form').submit();">
                            <i class="fas fa-file-pdf me-2"></i>Générer tous les bulletins
                        </a>
                        <form id="generate-bulletins-form" action="{{ route('esbtp.bulletins.generer-classe') }}" method="POST" style="display: none;">
                            @csrf
                            <input type="hidden" name="classe_id" value="{{ $classe->id }}">
                            <input type="hidden" name="periode" value="{{ $periode }}">
                            <input type="hidden" name="annee_universitaire_id" value="{{ $annee_id }}">
                        </form>
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
