@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestion des Présences des Enseignants</h3>
                    <div class="card-tools">
                        <form class="form-inline">
                            <input type="date" name="date" value="{{ $date }}" class="form-control mr-2">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addPresenceModal">
                            <i class="fas fa-plus"></i> Nouvelle Présence
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Enseignant</th>
                                    <th>Matière</th>
                                    <th>Heure d'Arrivée</th>
                                    <th>Heure de Départ</th>
                                    <th>Statut</th>
                                    <th>Remarques</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($presences as $presence)
                                <tr>
                                    <td>{{ $presence->enseignant->name }}</td>
                                    <td>{{ $presence->matiere->nom }}</td>
                                    <td>{{ $presence->heure_arrivee }}</td>
                                    <td>{{ $presence->heure_depart }}</td>
                                    <td>
                                        <span class="badge badge-{{ $presence->statut === 'present' ? 'success' : ($presence->statut === 'retard' ? 'warning' : 'danger') }}">
                                            {{ $presence->statut === 'present' ? 'Présent' : ($presence->statut === 'retard' ? 'En retard' : 'Absent') }}
                                        </span>
                                    </td>
                                    <td>{{ $presence->remarques }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editPresenceModal{{ $presence->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">Aucune présence enregistrée pour cette date</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'ajout de présence -->
<div class="modal fade" id="addPresenceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('esbtp.admin.presence.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle Présence</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Enseignant</label>
                        <select name="enseignant_id" class="form-control" required>
                            @foreach($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}">{{ $enseignant->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Matière</label>
                        <select name="matiere_id" class="form-control" required>
                            @foreach($matieres as $matiere)
                            <option value="{{ $matiere->id }}">{{ $matiere->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="statut" class="form-control" required>
                            <option value="present">Présent</option>
                            <option value="retard">En retard</option>
                            <option value="absent">Absent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Remarques</label>
                        <textarea name="remarques" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($presences as $presence)
<!-- Modal de modification -->
<div class="modal fade" id="editPresenceModal{{ $presence->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('esbtp.admin.presence.update', $presence->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la Présence</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="statut" class="form-control" required>
                            <option value="present" {{ $presence->statut === 'present' ? 'selected' : '' }}>Présent</option>
                            <option value="retard" {{ $presence->statut === 'retard' ? 'selected' : '' }}>En retard</option>
                            <option value="absent" {{ $presence->statut === 'absent' ? 'selected' : '' }}>Absent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Remarques</label>
                        <textarea name="remarques" class="form-control" rows="3">{{ $presence->remarques }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
