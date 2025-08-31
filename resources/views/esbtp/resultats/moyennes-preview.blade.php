@extends('layouts.app')

@section('title', 'Modification des moyennes - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Modification des moyennes</h1>
                <p class="header-subtitle">Ajustez les moyennes et coefficients pour {{ $etudiant->nom }} {{ $etudiant->prenoms }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.resultats.etudiant', $etudiant) }}?classe_id={{ $classe->id }}&periode={{ $periode }}&annee_universitaire_id={{ $anneeUniversitaire->id }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Annuler
                </a>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Étudiant</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">{{ $etudiant->nom }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-user"></i>
                    {{ $etudiant->prenoms }}
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Classe</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">{{ $classe->name }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    {{ $anneeUniversitaire->annee_debut }}-{{ $anneeUniversitaire->annee_fin }}
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Période</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">
                    @if($periode == 'semestre1')
                        1er Semestre
                    @elseif($periode == 'semestre2')
                        2e Semestre
                    @else
                        Année complète
                    @endif
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calendar-alt"></i>
                    Modification en cours
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Matières</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ count($resultatsData) }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-book"></i>
                    À modifier
                </div>
            </div>
        </div>

        <!-- Alertes de session -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Guide d'utilisation -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-info-circle"></i>
                    Instructions
                </div>
            </div>
            <div class="main-card-body">
                <div class="alert alert-warning mb-0">
                    <div class="row">
                        <div class="col-md-6">
                            <p><i class="fas fa-exclamation-triangle me-2"></i><strong>Attention :</strong> La modification des moyennes a un impact direct sur les bulletins générés.</p>
                            <p><i class="fas fa-check-circle text-success me-2"></i>Vous pouvez modifier les moyennes calculées automatiquement.</p>
                        </div>
                        <div class="col-md-6">
                            <p><i class="fas fa-check-circle text-success me-2"></i>Vous pouvez ajuster les coefficients des matières si nécessaire.</p>
                            <p><i class="fas fa-info-circle text-primary me-2"></i>Les moyennes doivent être comprises entre 0 et 20.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire de modification -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Moyennes par matière
                </div>
                <div class="main-card-subtitle">Modifiez les moyennes et coefficients pour chaque matière</div>
            </div>

            <div class="main-card-body">
                <form method="POST" action="{{ route('esbtp.bulletins.moyennes-update') }}">
                    @csrf
                    <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
                    <input type="hidden" name="classe_id" value="{{ $classe->id }}">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="25%">Matière</th>
                                    <th width="15%" class="text-center">Moyenne calculée</th>
                                    <th width="15%" class="text-center">Moyenne à enregistrer</th>
                                    <th width="10%" class="text-center">Coefficient</th>
                                    <th width="30%">Appréciation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 1; @endphp
                                @forelse($resultatsData as $matiereId => $resultat)
                                    @php
                                        $calculatedMoyenne = isset($notesByMatiere[$matiereId]) ? $notesByMatiere[$matiereId]['moyenne'] : 0;
                                        $existingMoyenne = $resultat['moyenne'] ?? $calculatedMoyenne;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <span class="badge bg-primary rounded-pill">{{ $i++ }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-book text-primary me-3"></i>
                                                <div>
                                                    <div class="fw-medium">{{ $resultat['matiere']->name }}</div>
                                                    @if($resultat['matiere']->code)
                                                        <small class="text-muted">{{ $resultat['matiere']->code }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
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
                                            <input type="text" class="form-control" name="resultats[{{ $matiereId }}][appreciation]" value="{{ old('resultats.' . $matiereId . '.appreciation', $resultat['appreciation'] ?? '') }}" placeholder="Appréciation optionnelle">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-folder-open fa-2x mb-3 d-block"></i>
                                            Aucune matière trouvée pour cet étudiant
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div>
                            <a href="{{ route('esbtp.resultats.etudiant', $etudiant) }}?classe_id={{ $classe->id }}&periode={{ $periode }}&annee_universitaire_id={{ $anneeUniversitaire->id }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annuler
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-acasi primary">
                                <i class="fas fa-save"></i>Enregistrer les modifications
                            </button>
                            <a href="#" class="btn-acasi danger" onclick="window.open('{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $anneeUniversitaire->id]) }}', '_blank')">
                                <i class="fas fa-file-pdf"></i>Générer le bulletin
                            </a>
                        </div>
                    </div>
                </form>
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
