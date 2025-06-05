@extends('layouts.app')

@section('title', 'Modification des moyennes de ' . $etudiant->nom . ' ' . $etudiant->prenoms)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- DEBUG TEMPORAIRE -->
            <div class="alert alert-info">
                <h6>🔍 Debug des données reçues :</h6>
                <p><strong>Nombre de résultats dans $resultatsData :</strong> {{ count($resultatsData) }}</p>
                @if(count($resultatsData) > 0)
                    <p><strong>Détails des résultats :</strong></p>
                    <ul>
                        @foreach($resultatsData as $matiereId => $resultat)
                            <li>
                                <strong>Matière ID {{ $matiereId }} :</strong> {{ $resultat['matiere']->name ?? 'N/A' }}
                                - Moyenne: {{ $resultat['moyenne'] ?? 'N/A' }}
                                - Coefficient: {{ $resultat['coefficient'] ?? 'N/A' }}
                            </li>
                        @endforeach
                    </ul>
                @endif
                <p><strong>Nombre de matières dans $notesByMatiere :</strong> {{ count($notesByMatiere ?? []) }}</p>
            </div>
            <!-- FIN DEBUG -->

            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Modification des moyennes de {{ $etudiant->nom }} {{ $etudiant->prenoms }}</h5>
                    <a href="{{ route('esbtp.resultats.etudiant', $etudiant) }}?classe_id={{ $classe->id }}&periode={{ $periode }}&annee_universitaire_id={{ $anneeUniversitaire->id }}" class="btn btn-outline-dark">
                        <i class="fas fa-times me-1"></i>Annuler
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

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Informations de l'étudiant</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">Nom et prénoms :</div>
                                        <div class="col-md-8">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">Matricule :</div>
                                        <div class="col-md-8">{{ $etudiant->matricule }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">Classe :</div>
                                        <div class="col-md-8">{{ $classe->name }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">Année universitaire :</div>
                                        <div class="col-md-8">{{ $anneeUniversitaire->annee_debut }}-{{ $anneeUniversitaire->annee_fin }}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 fw-bold">Période :</div>
                                        <div class="col-md-8">
                                            @if($periode == 'semestre1')
                                                Premier semestre
                                            @elseif($periode == 'semestre2')
                                                Deuxième semestre
                                            @else
                                                Année complète
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Instructions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Attention :</strong> La modification des moyennes a un impact direct sur les bulletins générés.
                                    </div>
                                    <p><i class="fas fa-check-circle text-success me-2"></i>Vous pouvez modifier les moyennes calculées automatiquement.</p>
                                    <p><i class="fas fa-check-circle text-success me-2"></i>Vous pouvez ajuster les coefficients des matières si nécessaire.</p>
                                    <p><i class="fas fa-check-circle text-success me-2"></i>Vous pouvez ajouter des appréciations pour chaque matière.</p>
                                    <p><i class="fas fa-info-circle text-primary me-2"></i>Les moyennes doivent être comprises entre 0 et 20.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('esbtp.bulletins.moyennes-update') }}">
                        @csrf
                        <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
                        <input type="hidden" name="classe_id" value="{{ $classe->id }}">
                        <input type="hidden" name="periode" value="{{ $periode }}">
                        <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Moyennes par matière</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th style="width: 25%">Matière</th>
                                                <th style="width: 15%" class="text-center">Moyenne calculée</th>
                                                <th style="width: 15%" class="text-center">Moyenne à enregistrer</th>
                                                <th style="width: 10%" class="text-center">Coefficient</th>
                                                <th style="width: 30%">Appréciation</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $i = 1; @endphp
                                            @forelse($resultatsData as $matiereId => $resultat)
                                                @php
                                                    $calculatedMoyenne = isset($notesByMatiere[$matiereId]) ? $notesByMatiere[$matiereId]['moyenne'] : 0;
                                                    $existingMoyenne = $resultat['moyenne'] ?? $calculatedMoyenne;
                                                @endphp
                                                <!-- DEBUG DÉTAILLÉ -->
                                                <tr style="background-color: #f8f9fa;">
                                                    <td colspan="6">
                                                        <small>
                                                            <strong>🔍 Debug pour matière ID {{ $matiereId }}:</strong><br>
                                                            - calculatedMoyenne: {{ $calculatedMoyenne }}<br>
                                                            - resultat['moyenne']: {{ $resultat['moyenne'] ?? 'NULL' }}<br>
                                                            - existingMoyenne: {{ $existingMoyenne }}<br>
                                                            - number_format($existingMoyenne, 2): {{ number_format($existingMoyenne, 2) }}<br>
                                                            - resultat['coefficient']: {{ $resultat['coefficient'] ?? 'NULL' }}
                                                        </small>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>{{ $i++ }}</td>
                                                    <td>{{ $resultat['matiere']->name }} {{ $resultat['matiere']->code ? '(' . $resultat['matiere']->code . ')' : '(ID: ' . $resultat['matiere']->id . ')' }}</td>
                                                    <td class="text-center">
                                                        <span class="badge rounded-pill {{ $calculatedMoyenne >= 10 ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                                                            {{ number_format($calculatedMoyenne, 2) }}/20
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <input type="hidden" name="resultats[{{ $matiereId }}][matiere_id]" value="{{ $matiereId }}">
                                                        <input type="hidden" name="resultats[{{ $matiereId }}][id]" value="{{ $resultat['id'] }}">
                                                        <input type="number" class="form-control text-center" name="resultats[{{ $matiereId }}][moyenne]" value="{{ old('resultats.' . $matiereId . '.moyenne', number_format($existingMoyenne, 2)) }}" min="0" max="20" step="0.01" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control text-center" name="resultats[{{ $matiereId }}][coefficient]" value="{{ old('resultats.' . $matiereId . '.coefficient', $resultat['coefficient'] ?? (isset($notesByMatiere[$matiereId]) ? $notesByMatiere[$matiereId]['total_coefficients'] : 1)) }}" min="0" step="0.5" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="resultats[{{ $matiereId }}][appreciation]" value="{{ old('resultats.' . $matiereId . '.appreciation', $resultat['appreciation'] ?? '') }}" placeholder="Appréciation">
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                                                            <p>Aucune matière trouvée pour cet étudiant</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <a href="{{ route('esbtp.resultats.etudiant', $etudiant) }}?classe_id={{ $classe->id }}&periode={{ $periode }}&annee_universitaire_id={{ $anneeUniversitaire->id }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Annuler les modifications
                                </a>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn btn-primary shadow-sm">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                                <a href="#" class="btn btn-danger shadow-sm ms-2" onclick="window.open('{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $anneeUniversitaire->id]) }}', '_blank')">
                                    <i class="fas fa-file-pdf me-2"></i>Générer le bulletin
                                </a>
                            </div>
                        </div>
                    </form>
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
