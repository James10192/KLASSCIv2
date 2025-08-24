@props(['seances' => collect(), 'emploiTemps'])

<div class="main-card mb-4">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-list-ul"></i>
            Liste des séances
        </div>
        <div class="main-card-subtitle">{{ $seances->count() }} séance(s) programmée(s)</div>
    </div>
    <div class="main-card-body">
        @if($seances && $seances->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="8%">#</th>
                            <th width="20%">Matière</th>
                            <th width="18%">Enseignant</th>
                            <th width="10%">Type</th>
                            <th width="12%">Jour</th>
                            <th width="12%">Heure</th>
                            <th width="8%">Durée</th>
                            <th width="7%">Statut</th>
                            <th width="5%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($seances->sortBy([['jour', 'asc'], ['heure_debut', 'asc']]) as $index => $seance)
                        <tr>
                            <td class="text-center fw-bold">{{ $index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 25px; height: 25px; flex-shrink: 0;">
                                        <i class="fas fa-book" style="font-size: 10px;"></i>
                                    </div>
                                    <strong>{{ $seance->matiere->name ?? 'Non définie' }}</strong>
                                </div>
                            </td>
                            <td>
                                @if($seance->teacher)
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-tie text-secondary me-1"></i>
                                        <small>{{ $seance->teacher->name }}</small>
                                    </div>
                                @else
                                    <small class="text-muted">
                                        <i class="fas fa-user-slash me-1"></i>Non assigné
                                    </small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $typeColors = [
                                        'course' => 'primary',
                                        'homework' => 'success', 
                                        'break' => 'warning',
                                        'lunch' => 'info'
                                    ];
                                    $typeLabels = [
                                        'course' => 'COURS',
                                        'homework' => 'DEVOIR', 
                                        'break' => 'RÉCRÉATION',
                                        'lunch' => 'PAUSE'
                                    ];
                                    $typeColor = $typeColors[$seance->type] ?? 'primary';
                                    $typeLabel = $typeLabels[$seance->type] ?? 'COURS';
                                @endphp
                                <span class="badge bg-{{ $typeColor }}">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $joursMapping = [
                                        1 => 'Lundi',
                                        2 => 'Mardi', 
                                        3 => 'Mercredi',
                                        4 => 'Jeudi',
                                        5 => 'Vendredi',
                                        6 => 'Samedi',
                                        0 => 'Dimanche',
                                        7 => 'Dimanche'
                                    ];
                                    $jourNom = $joursMapping[$seance->jour] ?? 'Jour ' . $seance->jour;
                                @endphp
                                <small class="fw-bold">{{ $jourNom }}</small>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-clock text-muted me-1"></i>
                                    {{ \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') }} - 
                                    {{ \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') }}
                                </small>
                            </td>
                            <td class="text-center">
                                @php
                                    $debut = \Carbon\Carbon::parse($seance->heure_debut);
                                    $fin = \Carbon\Carbon::parse($seance->heure_fin);
                                    $duree = $debut->diffInHours($fin);
                                @endphp
                                <span class="badge bg-secondary">{{ $duree }}h</span>
                            </td>
                            <td class="text-center">
                                @if($seance->is_active ?? true)
                                    <span class="badge bg-success">
                                        <i class="fas fa-check"></i> Actif
                                    </span>
                                @else
                                    <span class="badge bg-warning">
                                        <i class="fas fa-pause"></i> Inactif
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="tooltip" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="tooltip" title="Supprimer"
                                            onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cette séance ?')) { /* Action supprimer */ }">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Résumé par type de séance -->
            <div class="mt-3 p-3 bg-light rounded">
                <h6 class="mb-2">
                    <i class="fas fa-chart-bar text-primary me-1"></i>
                    Répartition par type de séance
                </h6>
                <div class="row">
                    @php
                        $repartition = [
                            'course' => $seances->where('type', 'course')->count(),
                            'homework' => $seances->where('type', 'homework')->count(),
                            'break' => $seances->where('type', 'break')->count(),
                            'lunch' => $seances->where('type', 'lunch')->count(),
                        ];
                        $total = $seances->count();
                        
                        $typeColors = [
                            'course' => 'primary',
                            'homework' => 'success', 
                            'break' => 'warning',
                            'lunch' => 'info'
                        ];
                        $typeLabels = [
                            'course' => 'COURS',
                            'homework' => 'DEVOIRS', 
                            'break' => 'RÉCRÉATIONS',
                            'lunch' => 'PAUSES'
                        ];
                    @endphp
                    
                    @foreach($repartition as $type => $count)
                        @if($count > 0)
                        <div class="col-md-3 col-6 mb-2">
                            <div class="text-center">
                                <div class="h4 mb-1 text-{{ $typeColors[$type] }}">
                                    {{ $count }}
                                </div>
                                <small class="text-muted">
                                    {{ $typeLabels[$type] }}
                                    <br>({{ $total > 0 ? round(($count / $total) * 100, 1) : 0 }}%)
                                </small>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <div class="alert alert-info mb-0">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calendar-plus fa-2x me-3 text-primary"></i>
                    <div>
                        <h6><strong>Aucune séance programmée</strong></h6>
                        <p class="mb-2">Cet emploi du temps ne contient aucune séance.</p>
                        <a href="{{ route('esbtp.emploi-temps.add-session', $emploiTemps->id) }}" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Ajouter la première séance
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>