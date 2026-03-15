@extends('layouts.app')

@section('title', 'Bulletin de Notes')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-body">
            <!-- Boutons d'action -->
            <div class="mb-4 text-end">
                <button class="btn btn-primary me-2" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimer
                </button>
                <a href="{{ route('grades.download-pdf', ['student' => $student->id, 'semester' => $semester]) }}" 
                   class="btn btn-success">
                    <i class="fas fa-download"></i> Télécharger PDF
                </a>
            </div>

            <!-- Bulletin -->
            <div class="bulletin-container">
                <!-- En-tête -->
                <div class="text-center mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <img src="{{ asset('images/armoiries.png') }}" alt="Armoiries" class="img-fluid" style="max-height: 100px;">
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-0">République de Côte d'Ivoire</h5>
                            <p class="mb-0">Union-Discipline-Travail</p>
                            <hr class="my-2">
                            <h6>Ministère de l'Enseignement Supérieur<br>et de la Recherche Scientifique</h6>
                        </div>
                        <div class="col-md-3">
                            <div class="text-end">
                                <h5 class="mb-0">BULLETIN DE NOTES</h5>
                                <p class="mb-0">{{ $semester == 1 ? 'Premier' : 'Deuxième' }} semestre</p>
                                <p class="mb-0">Edition du: {{ now()->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logo et informations de l'école -->
                <div class="text-center mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <img src="{{ asset('images/esbtp_logo.png') }}" alt="{{ \App\Helpers\SettingsHelper::get('school_acronym', config('app.name')) }} Logo" class="img-fluid" style="max-height: 120px;">
                        </div>
                        <div class="col-md-9">
                            <h4>Ecole Spéciale<br>du Bâtiment et des Travaux Publics</h4>
                            <p class="mb-0">BP 2541 Yamoussoukro - Email: esbtp@aviso.ci</p>
                            <p class="mb-0">Tél/Fax: 30 64 36 93 - Cel: 07 72 88 56</p>
                        </div>
                    </div>
                </div>

                <!-- Informations de l'étudiant -->
                <div class="student-info mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="150">Matricule</th>
                                    <td>: {{ $student->admission_no }}</td>
                                </tr>
                                <tr>
                                    <th>Nom et Prénoms</th>
                                    <td>: {{ $student->last_name }} {{ $student->first_name }}</td>
                                </tr>
                                <tr>
                                    <th>Date de Naissance</th>
                                    <td>: {{ \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Redoublant</th>
                                    <td>: {{ $student->is_repeating ? 'OUI' : 'NON' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="150">Classe</th>
                                    <td>: {{ $student->class->name }}</td>
                                </tr>
                                <tr>
                                    <th>Année d'étude</th>
                                    <td>: {{ $student->year_of_study }}ème année</td>
                                </tr>
                                <tr>
                                    <th>Effectif</th>
                                    <td>: {{ $classSize }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="grades-section mb-4">
                    <!-- Enseignement général -->
                    <h6 class="section-title">Enseignement général</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Matière</th>
                                    <th width="60">Moyenne</th>
                                    <th width="60">Coef</th>
                                    <th width="60">Moy Pond</th>
                                    <th>Professeurs</th>
                                    <th>Appréciations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalGeneral = 0; $totalCoefGeneral = 0; @endphp
                                @foreach($generalSubjects as $grade)
                                    <tr>
                                        <td>{{ $grade->subject->name }}</td>
                                        <td class="text-center">{{ number_format($grade->average, 2) }}</td>
                                        <td class="text-center">{{ $grade->subject->coefficient }}</td>
                                        <td class="text-center">{{ number_format($grade->weighted_average, 2) }}</td>
                                        <td>{{ $grade->subject->teacher->full_name ?? 'N/A' }}</td>
                                        <td>{{ $grade->appreciation }}</td>
                                    </tr>
                                    @php 
                                        $totalGeneral += $grade->weighted_average;
                                        $totalCoefGeneral += $grade->subject->coefficient;
                                    @endphp
                                @endforeach
                                <tr class="table-light">
                                    <td colspan="3" class="text-end"><strong>Moyenne</strong></td>
                                    <td class="text-center">
                                        <strong>
                                            {{ $totalCoefGeneral > 0 ? number_format($totalGeneral / $totalCoefGeneral, 2) : '0.00' }}
                                        </strong>
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Enseignement Technique -->
                    <h6 class="section-title">Enseignement Technique</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Matière</th>
                                    <th width="60">Moyenne</th>
                                    <th width="60">Coef</th>
                                    <th width="60">Moy Pond</th>
                                    <th>Professeurs</th>
                                    <th>Appréciations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalTechnical = 0; $totalCoefTechnical = 0; @endphp
                                @foreach($technicalSubjects as $grade)
                                    <tr>
                                        <td>{{ $grade->subject->name }}</td>
                                        <td class="text-center">{{ number_format($grade->average, 2) }}</td>
                                        <td class="text-center">{{ $grade->subject->coefficient }}</td>
                                        <td class="text-center">{{ number_format($grade->weighted_average, 2) }}</td>
                                        <td>{{ $grade->subject->teacher->full_name ?? 'N/A' }}</td>
                                        <td>{{ $grade->appreciation }}</td>
                                    </tr>
                                    @php 
                                        $totalTechnical += $grade->weighted_average;
                                        $totalCoefTechnical += $grade->subject->coefficient;
                                    @endphp
                                @endforeach
                                <tr class="table-light">
                                    <td colspan="3" class="text-end"><strong>Moyenne</strong></td>
                                    <td class="text-center">
                                        <strong>
                                            {{ $totalCoefTechnical > 0 ? number_format($totalTechnical / $totalCoefTechnical, 2) : '0.00' }}
                                        </strong>
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Absences -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th colspan="2">Nombre d'heures d'absence</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Absences justifiées</td>
                                        <td width="100" class="text-center">{{ $absences->justified ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <td>Absences non justifiées</td>
                                        <td class="text-center">{{ $absences->unjustified ?? 0 }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Résultats -->
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <tbody>
                                    <tr>
                                        <th width="200">Moyenne Brute</th>
                                        <td class="text-center">{{ number_format($averages->raw ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Note d'assiduité</th>
                                        <td class="text-center">{{ number_format($averages->attendance ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Moyenne {{ $semester == 1 ? '1er' : '2ème' }} Semestre</th>
                                        <td class="text-center">{{ number_format($averages->semester ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Rang</th>
                                        <td class="text-center">{{ $rank ?? 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th colspan="2">MENTIONS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Félicitation</td>
                                        <td width="50" class="text-center">
                                            {{ $averages->semester >= 16 ? '✓' : '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Encouragement</td>
                                        <td class="text-center">
                                            {{ $averages->semester >= 14 && $averages->semester < 16 ? '✓' : '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tableau d'honneur</td>
                                        <td class="text-center">
                                            {{ $averages->semester >= 12 && $averages->semester < 14 ? '✓' : '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Avertissement (Travail)</td>
                                        <td class="text-center">
                                            {{ $averages->semester < 8 ? '✓' : '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Blâme (Discipline)</td>
                                        <td class="text-center">
                                            {{ $disciplinaryAction ? '✓' : '' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th colspan="2">STATISTIQUES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Plus forte moyenne</td>
                                        <td width="100" class="text-center">{{ number_format($statistics->highest ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Plus faible moyenne</td>
                                        <td class="text-center">{{ number_format($statistics->lowest ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Moyenne de la classe</td>
                                        <td class="text-center">{{ number_format($statistics->class_average ?? 0, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Décision et signature -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="decision-box p-3 border">
                                <h6 class="mb-3">Décision du conseil de classe</h6>
                                <p>{{ $decision ?? 'Assez bon travail' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="signature-box">
                                <p class="mb-4">Signature du Directeur des Études</p>
                                <div class="stamp-area">
                                    <img src="{{ asset('images/signature.png') }}" alt="Signature" class="img-fluid" style="max-height: 100px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body {
        background: white;
    }
    .bulletin-container {
        background: white;
        padding: 20px;
        margin: 0;
    }
    .btn, .navbar, .sidebar {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-body {
        padding: 0 !important;
    }
}

.bulletin-container {
    background: white;
    padding: 20px;
    font-size: 14px;
}

.section-title {
    background-color: #f8f9fa;
    padding: 5px 10px;
    margin-bottom: 10px;
    border-left: 4px solid #4e73df;
}

.decision-box {
    background-color: #f8f9fa;
    border-radius: 4px;
}

.signature-box {
    padding: 20px;
}

.stamp-area {
    min-height: 100px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

table {
    font-size: 14px;
}

.table > :not(caption) > * > * {
    padding: 0.5rem;
}

.table-sm > :not(caption) > * > * {
    padding: 0.25rem;
}
</style>
@endsection 