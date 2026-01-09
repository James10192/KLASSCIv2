@extends('layouts.app')

@section('title', 'Séances de cours - KLASSCI')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Gestion des séances de cours</h5>
                    <a href="{{ route('esbtp.seances-cours.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Ajouter une séance de cours
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form action="{{ route('esbtp.seances-cours.index') }}" method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="emploi_temps_id" class="form-label">Emploi du temps</label>
                                    <select class="form-select select2" id="emploi_temps_id" name="emploi_temps_id">
                                        <option value="">Tous les emplois du temps</option>
                                        @foreach($emploisTemps as $emploiTemps)
                                            <option value="{{ $emploiTemps->id }}" {{ request('emploi_temps_id') == $emploiTemps->id ? 'selected' : '' }}>
                                                {{ $emploiTemps->titre }} ({{ $emploiTemps->classe->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="jour_semaine" class="form-label">Jour</label>
                                    <select class="form-select select2" id="jour_semaine" name="jour_semaine">
                                        <option value="">Tous les jours</option>
                                        <option value="1" {{ request('jour_semaine') == '1' ? 'selected' : '' }}>Lundi</option>
                                        <option value="2" {{ request('jour_semaine') == '2' ? 'selected' : '' }}>Mardi</option>
                                        <option value="3" {{ request('jour_semaine') == '3' ? 'selected' : '' }}>Mercredi</option>
                                        <option value="4" {{ request('jour_semaine') == '4' ? 'selected' : '' }}>Jeudi</option>
                                        <option value="5" {{ request('jour_semaine') == '5' ? 'selected' : '' }}>Vendredi</option>
                                        <option value="6" {{ request('jour_semaine') == '6' ? 'selected' : '' }}>Samedi</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="type_seance" class="form-label">Type de séance</label>
                                    <select class="form-select select2" id="type_seance" name="type_seance">
                                        <option value="">Tous les types</option>
                                        <option value="cours" {{ request('type_seance') == 'cours' ? 'selected' : '' }}>Cours magistral</option>
                                        <option value="td" {{ request('type_seance') == 'td' ? 'selected' : '' }}>Travaux dirigés</option>
                                        <option value="tp" {{ request('type_seance') == 'tp' ? 'selected' : '' }}>Travaux pratiques</option>
                                        <option value="examen" {{ request('type_seance') == 'examen' ? 'selected' : '' }}>Examen</option>
                                        <option value="autre" {{ request('type_seance') == 'autre' ? 'selected' : '' }}>Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="enseignant" class="form-label">Enseignant</label>
                                    <select class="form-select select2" id="enseignant" name="enseignant">
                                        <option value="">Tous les enseignants</option>
                                        @foreach($enseignants as $enseignant)
                                            <option value="{{ $enseignant->name }}" {{ request('enseignant') == $enseignant->name ? 'selected' : '' }}>
                                                {{ $enseignant->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-1"></i>Filtrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatable">
                            <thead>
                                <tr>
                                    <th>Jour</th>
                                    <th>Horaire</th>
                                    <th>Classe</th>
                                    <th>Matière</th>
                                    <th>Enseignant</th>
                                    <th>Salle</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $jours = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                                @endphp

                                @forelse($seancesCours as $seance)
                                    <tr @if(!$seance->is_active) class="table-secondary" @endif>
                                        <td>{{ $jours[$seance->jour] ?? 'Inconnu' }}</td>
                                        <td>{{ $seance->heure_debut }} - {{ $seance->heure_fin }}</td>
                                        <td>{{ $seance->emploiTemps->classe->name }}</td>
                                        <td>{{ $seance->matiere->name }}</td>
                                        <td>{{ $seance->enseignant }}</td>
                                        <td>{{ $seance->salle }}</td>
                                        <td>
                                            <span class="badge
                                                @if($seance->type_seance == 'cours') bg-primary
                                                @elseif($seance->type_seance == 'td') bg-success
                                                @elseif($seance->type_seance == 'tp') bg-purple
                                                @elseif($seance->type_seance == 'examen') bg-danger
                                                @else bg-warning
                                                @endif">
                                                @if($seance->type_seance == 'cours') Cours magistral
                                                @elseif($seance->type_seance == 'td') Travaux dirigés
                                                @elseif($seance->type_seance == 'tp') Travaux pratiques
                                                @elseif($seance->type_seance == 'examen') Examen
                                                @else Autre
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            @if($seance->is_active)
                                                <span class="badge bg-success">Actif</span>
                                            @else
                                                <span class="badge bg-secondary">Inactif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('esbtp.emploi-temps.show', $seance->emploi_temps_id) }}" class="btn btn-sm btn-info" title="Voir l'emploi du temps">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.seances-cours.edit', $seance->id) }}" class="btn btn-sm btn-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $seance->id }}" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                            <!-- Modal de confirmation de suppression -->
                                            <div class="modal fade" id="deleteModal{{ $seance->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $seance->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel{{ $seance->id }}">Confirmation de suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Êtes-vous sûr de vouloir supprimer cette séance de cours ?</p>
                                                            <p class="fw-bold">{{ $jours[$seance->jour] }} de {{ $seance->heure_debut }} à {{ $seance->heure_fin }} - {{ $seance->matiere->name }}</p>
                                                            <p class="fw-bold">Classe: {{ $seance->emploiTemps->classe->name }}</p>
                                                            <p class="text-danger">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                                <strong>Attention :</strong> Cette action est irréversible.
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <form action="{{ route('esbtp.seances-cours.destroy', $seance->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Aucune séance de cours trouvée.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $seancesCours->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Statistiques des séances</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Répartition par type</h6>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Cours magistraux
                                    <span class="badge bg-primary rounded-pill">{{ $statsCours['cours'] }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Travaux dirigés
                                    <span class="badge bg-success rounded-pill">{{ $statsCours['td'] }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Travaux pratiques
                                    <span class="badge bg-purple rounded-pill">{{ $statsCours['tp'] }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Examens
                                    <span class="badge bg-danger rounded-pill">{{ $statsCours['examen'] }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Autres
                                    <span class="badge bg-warning rounded-pill">{{ $statsCours['autre'] }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Répartition par jour</h6>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Lundi
                                    <span class="badge bg-info rounded-pill">{{ $statsJours[1] ?? 0 }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Mardi
                                    <span class="badge bg-info rounded-pill">{{ $statsJours[2] ?? 0 }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Mercredi
                                    <span class="badge bg-info rounded-pill">{{ $statsJours[3] ?? 0 }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Jeudi
                                    <span class="badge bg-info rounded-pill">{{ $statsJours[4] ?? 0 }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Vendredi
                                    <span class="badge bg-info rounded-pill">{{ $statsJours[5] ?? 0 }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Samedi
                                    <span class="badge bg-info rounded-pill">{{ $statsJours[6] ?? 0 }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Conflits potentiels</h5>
                </div>
                <div class="card-body">
                    @if(count($conflits) > 0)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Attention :</strong> {{ count($conflits) }} conflits d'horaires ont été détectés.
                        </div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <ul class="list-group">
                                @foreach($conflits as $conflit)
                                    <li class="list-group-item list-group-item-warning">
                                        <strong>{{ $jours[$conflit['jour']] }} - {{ $conflit['heure_debut'] }} à {{ $conflit['heure_fin'] }}</strong><br>
                                        <span class="text-danger">{{ $conflit['type'] }}</span> :
                                        @if($conflit['type'] == 'Enseignant')
                                            {{ $conflit['nom'] }} a plusieurs cours en même temps
                                        @elseif($conflit['type'] == 'Salle')
                                            Salle {{ $conflit['nom'] }} réservée plusieurs fois
                                        @else
                                            Classe {{ $conflit['nom'] }} a plusieurs cours en même temps
                                        @endif
                                        <div class="mt-1">
                                            <a href="{{ route('esbtp.seances-cours.edit', $conflit['seance_id']) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit me-1"></i>Modifier
                                            </a>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Excellent !</strong> Aucun conflit d'horaire n'a été détecté.
                        </div>
                        <p>Les points suivants sont vérifiés pour détecter les conflits :</p>
                        <ul>
                            <li>Un enseignant ne peut pas donner deux cours en même temps</li>
                            <li>Une salle ne peut pas accueillir deux cours en même temps</li>
                            <li>Une classe ne peut pas avoir deux cours en même temps</li>
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.datatable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/French.json"
            },
            "pageLength": 25,
            "order": [[0, 'asc'], [1, 'asc']],
            "searching": false,
            "paging": false,
            "info": false
        });

        // Amélioration des listes déroulantes avec Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });
</script>
@endsection
