@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Rapport des Présences des Enseignants</h3>
                    <div class="card-tools">
                        <form class="form-inline">
                            <div class="input-group mr-2">
                                <label class="mr-2">Du</label>
                                <input type="date" name="date_debut" value="{{ $dateDebut }}" class="form-control">
                            </div>
                            <div class="input-group mr-2">
                                <label class="mr-2">Au</label>
                                <input type="date" name="date_fin" value="{{ $dateFin }}" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Enseignant</th>
                                    <th>Matière</th>
                                    <th>Heure d'Arrivée</th>
                                    <th>Heure de Départ</th>
                                    <th>Statut</th>
                                    <th>Remarques</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($presences as $presence)
                                <tr>
                                    <td>{{ $presence->date->format('d/m/Y') }}</td>
                                    <td>{{ $presence->enseignant->nom }} {{ $presence->enseignant->prenom }}</td>
                                    <td>{{ $presence->matiere->nom }}</td>
                                    <td>{{ $presence->heure_arrivee }}</td>
                                    <td>{{ $presence->heure_depart }}</td>
                                    <td>
                                        <span class="badge badge-{{ $presence->statut === 'present' ? 'success' : ($presence->statut === 'retard' ? 'warning' : 'danger') }}">
                                            {{ $presence->statut === 'present' ? 'Présent' : ($presence->statut === 'retard' ? 'En retard' : 'Absent') }}
                                        </span>
                                    </td>
                                    <td>{{ $presence->remarques }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">Aucune présence enregistrée pour cette période</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <h4>Statistiques</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Présences</span>
                                        <span class="info-box-number">{{ $presences->where('statut', 'present')->count() }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Retards</span>
                                        <span class="info-box-number">{{ $presences->where('statut', 'retard')->count() }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Absences</span>
                                        <span class="info-box-number">{{ $presences->where('statut', 'absent')->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
