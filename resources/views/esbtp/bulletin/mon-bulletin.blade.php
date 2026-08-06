@extends('layouts.app')

@section('title', 'Mon Bulletin')

@section('page-title', 'Mon Bulletin')

@php
    $studentAppreciations = app(\App\Services\AppreciationScaleService::class);
    $studentBadgeClass = static function (?string $slug): string {
        return match ($slug) {
            'excellent', 'tres-bien', 'bien' => 'badge-success',
            'assez-bien', 'passable' => 'badge-info',
            default => 'badge-danger',
        };
    };
@endphp

@section('content')
<div class="container-fluid">
    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('mon-bulletin.index') }}" method="GET" class="row">
                <div class="col-md-4 mb-2">
                    <label for="annee_universitaire_id">Année Universitaire</label>
                    <select name="annee_universitaire_id" id="annee_universitaire_id" class="form-control">
                        @foreach($anneesUniversitaires as $annee)
                            <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                                {{ $annee->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label for="periode">Période</label>
                    <select name="periode" id="periode" class="form-control">
                        <option value="semestre1" {{ $periode == 'semestre1' ? 'selected' : '' }}>Semestre 1</option>
                        <option value="semestre2" {{ $periode == 'semestre2' ? 'selected' : '' }}>Semestre 2</option>
                        <option value="annuel" {{ $periode == 'annuel' ? 'selected' : '' }}>Annuel</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('mon-bulletin.index') }}" class="btn btn-secondary ml-2">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    @if(session('warning'))
        <div class="alert alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if(isset($bulletin))
    <!-- Informations générales -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Informations générales</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr>
                                <th>Étudiant</th>
                                <td>{{ $etudiant->nom }} {{ $etudiant->prenom }}</td>
                            </tr>
                            <tr>
                                <th>Matricule</th>
                                <td>{{ $etudiant->matricule }}</td>
                            </tr>
                            <tr>
                                <th>Classe</th>
                                <td>{{ $classe->nom }}</td>
                            </tr>
                            <tr>
                                <th>Année Universitaire</th>
                                <td>
                                    @foreach($anneesUniversitaires as $annee)
                                        @if($annee->id == $anneeId)
                                            {{ $annee->display_name }}
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr>
                                <th>Période</th>
                                <td>{{ $bulletin->periode }}</td>
                            </tr>
                            <tr>
                                <th>Moyenne Générale</th>
                                @if($moyenneGenerale === null)
                                    <td class="font-weight-bold text-muted">En attente</td>
                                @else
                                    <td class="font-weight-bold {{ $moyenneGenerale >= 12 ? 'text-success' : ($moyenneGenerale >= 10 ? 'text-info' : 'text-danger') }}">
                                        {{ number_format($moyenneGenerale, 2) }}/20
                                    </td>
                                @endif
                            </tr>
                            <tr>
                                <th>Rang</th>
                                <td>{{ $rangGeneral }}/{{ $effectifClasse }}</td>
                            </tr>
                            <tr>
                                <th>Crédits validés</th>
                                <td>{{ $creditsTotaux }} crédits</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Résultats détaillés -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Résultats détaillés</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr class="bg-light">
                            <th>Code</th>
                            <th>Matière</th>
                            <th class="text-center">Crédits</th>
                            <th class="text-center">Coef</th>
                            <th class="text-center">Note CC</th>
                            <th class="text-center">Note Exam</th>
                            <th class="text-center">Moyenne</th>
                            <th class="text-center">Moyenne Classe</th>
                            <th class="text-center">Rang</th>
                            <th class="text-center">Mention</th>
                            <th class="text-center">Validation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($detailsParUE) > 0)
                            @foreach($detailsParUE as $ueId => $data)
                                @if($data['ue'])
                                    <tr class="bg-secondary text-white">
                                        <td colspan="3"><strong>UE: {{ $data['ue']->code }} - {{ $data['ue']->nom }}</strong></td>
                                        <td class="text-center">{{ $data['ue']->coefficient }}</td>
                                        <td colspan="7"></td>
                                    </tr>
                                @endif

                                @foreach($data['details'] as $detail)
                                    <tr>
                                        <td>{{ $detail->matiere->code ?? 'N/A' }}</td>
                                        <td>{{ $detail->matiere->nom ?? 'N/A' }}</td>
                                        <td class="text-center">{{ $detail->credits }}</td>
                                        <td class="text-center">{{ $detail->coefficient }}</td>
                                        <td class="text-center">{{ number_format($detail->note_cc, 2) }}</td>
                                        <td class="text-center">{{ number_format($detail->note_examen, 2) }}</td>
                                        <td class="text-center font-weight-bold {{ $detail->moyenne >= 12 ? 'text-success' : ($detail->moyenne >= 10 ? 'text-info' : 'text-danger') }}">
                                            {{ number_format($detail->moyenne, 2) }}
                                        </td>
                                        <td class="text-center">{{ number_format($detail->moyenne_classe, 2) }}</td>
                                        <td class="text-center">{{ $detail->rang }}/{{ $detail->effectif }}</td>
                                        <td class="text-center">
                                            @php $detailAppreciation = $studentAppreciations->classificationFor($detail->moyenne === null ? null : (float) $detail->moyenne, 'bts'); @endphp
                                            <span class="badge {{ $studentBadgeClass($detailAppreciation['slug']) }}">{{ $detailAppreciation['label'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($detail->moyenne >= 10)
                                                <span class="badge badge-success">Validé</span>
                                            @else
                                                <span class="badge badge-danger">Non validé</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- Moyenne de l'UE -->
                                @if($data['ue'])
                                    <tr class="bg-light">
                                        <td colspan="6" class="text-right"><strong>Moyenne UE</strong></td>
                                        <td class="text-center font-weight-bold">
                                            @php
                                                $moyenneUE = $data['details']->sum(function($detail) {
                                                    return $detail->moyenne * $detail->coefficient;
                                                }) / $data['details']->sum('coefficient');
                                            @endphp
                                            {{ number_format($moyenneUE, 2) }}
                                        </td>
                                        <td colspan="4"></td>
                                    </tr>
                                @endif
                            @endforeach
                        @else
                            <tr>
                                <td colspan="11" class="text-center">Aucun résultat disponible pour le moment.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Décision du conseil -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Décision du conseil</h5>
        </div>
        <div class="card-body">
            @if($moyenneGenerale === null && $decisionConseil === null)
                {{-- Bulletin pas encore finalisé : ne jamais afficher AJOURNÉ par défaut sur un calcul absent. --}}
                <div class="alert alert-info">
                    <h5>En attente de génération</h5>
                    <p>Votre bulletin de cette période n'a pas encore été finalisé par la scolarité. Vos résultats seront disponibles ici une fois le bulletin généré.</p>
                </div>
            @else
                <div class="alert {{ $moyenneGenerale >= 10 ? 'alert-success' : 'alert-danger' }}">
                    <h5>{{ $decisionConseil ?? ($moyenneGenerale >= 10 ? 'ADMIS' : 'AJOURNÉ') }}</h5>
                    <p>
                        @if($moyenneGenerale >= 10)
                            @php $generalAppreciation = $studentAppreciations->classificationFor((float) $moyenneGenerale, 'bts'); @endphp
                            Vous avez validé cette période avec l'appréciation {{ $generalAppreciation['label'] }}.
                        @else
                            Vous n'avez pas validé cette période. Veuillez consulter la scolarité pour plus d'informations.
                        @endif
                    </p>
                </div>
            @endif

            @if($bulletin->observations)
                <div class="mt-3">
                    <h6>Observations:</h6>
                    <p>{{ $bulletin->observations }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="text-center mb-4">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print mr-2"></i> Imprimer le bulletin
        </button>

        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Retour au tableau de bord
        </a>
    </div>
    @else
        <div class="alert alert-info">
            <h5><i class="fas fa-info-circle mr-2"></i> Aucun bulletin disponible</h5>
            <p>Aucun bulletin n'est disponible pour les critères sélectionnés. Veuillez vérifier les filtres ou contacter l'administration.</p>
        </div>
    @endif
</div>

@endsection

@section('styles')
<style>
    @media print {
        .sidebar, header, footer, .card-header, form, .btn, nav {
            display: none !important;
        }

        body {
            padding: 0;
            margin: 0;
        }

        .container-fluid {
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .card {
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .alert {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
        }
    }
</style>
@endsection
