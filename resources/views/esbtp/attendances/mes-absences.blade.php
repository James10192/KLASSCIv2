@extends('layouts.app')

@section('title', 'Mes Absences')

@section('page-title', 'Mes Absences')

@section('content')
<div class="container-fluid">
    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('esbtp.mes-absences.index') }}" method="GET" class="row">
                <div class="col-md-3 mb-2">
                    <label for="annee_universitaire_id">Année Universitaire</label>
                    <select name="annee_universitaire_id" id="annee_universitaire_id" class="form-control">
                        @foreach($anneesUniversitaires as $annee)
                            <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                                {{ $annee->annee_debut }}-{{ $annee->annee_fin }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="mois">Mois</label>
                    <select name="mois" id="mois" class="form-control">
                        <option value="">Tous les mois</option>
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $mois == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="justifie">Justification</label>
                    <select name="justifie" id="justifie" class="form-control">
                        <option value="">Toutes les absences</option>
                        <option value="1" {{ $justifie === '1' ? 'selected' : '' }}>Justifiées</option>
                        <option value="0" {{ $justifie === '0' ? 'selected' : '' }}>Non justifiées</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('esbtp.mes-absences.index') }}" class="btn btn-secondary ml-2">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-4">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Total des absences</h5>
                    <h2 class="mb-0">{{ $totalAbsences }}</h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <small class="text-white">Toutes les absences enregistrées</small>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Absences justifiées</h5>
                    <h2 class="mb-0">{{ $absencesJustifiees }}</h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <small class="text-white">{{ $totalAbsences > 0 ? round(($absencesJustifiees / $totalAbsences) * 100, 2) : 0 }}% du total</small>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Absences non justifiées</h5>
                    <h2 class="mb-0">{{ $absencesNonJustifiees }}</h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <small class="text-white">{{ $totalAbsences > 0 ? round(($absencesNonJustifiees / $totalAbsences) * 100, 2) : 0 }}% du total</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des absences -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Liste de mes absences</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Matière</th>
                                    <th>Heure</th>
                                    <th>Justifiée</th>
                                    <th>Commentaire</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($absences as $absence)
                                    <tr>
                                        <td>{{ $absence->seance->date ? $absence->seance->date->format('d/m/Y') : 'N/A' }}</td>
                                        <td>{{ $absence->seance->matiere->nom ?? 'N/A' }}</td>
                                        <td>{{ $absence->seance->heure_debut ? $absence->seance->heure_debut->format('H:i') : 'N/A' }} - {{ $absence->seance->heure_fin ? $absence->seance->heure_fin->format('H:i') : 'N/A' }}</td>
                                        <td>
                                            @if($absence->justifie)
                                                <span class="badge badge-success">Oui</span>
                                            @else
                                                <span class="badge badge-danger">Non</span>
                                            @endif
                                        </td>
                                        <td>{{ $absence->commentaire ?? 'Aucun commentaire' }}</td>
                                        <td>
                                            @if(!$absence->justifie)
                                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#justifierModal{{ $absence->id }}">
                                                    Justifier
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Aucune absence trouvée.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $absences->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar mr-2"></i>Évolution des absences</h5>
                </div>
                <div class="card-body">
                    <canvas id="absencesChart" width="100%" height="300"></canvas>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Règlement des absences</h5>
                </div>
                <div class="card-body">
                    <p>
                        <strong>Comment justifier une absence :</strong>
                    </p>
                    <ol>
                        <li>Cliquez sur le bouton "Justifier" à côté de l'absence concernée.</li>
                        <li>Téléchargez un document justificatif (certificat médical, convocation administrative, etc.).</li>
                        <li>Ajoutez un commentaire expliquant la raison de votre absence.</li>
                        <li>Soumettez votre demande de justification.</li>
                    </ol>
                    <p class="mt-3 mb-0 text-danger">
                        <strong>Attention :</strong> Les justifications sont soumises à validation par l'administration.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals pour justifier les absences -->
@foreach($absences as $absence)
    @if(!$absence->justifie)
        <div class="modal fade" id="justifierModal{{ $absence->id }}" tabindex="-1" role="dialog" aria-labelledby="justifierModalLabel{{ $absence->id }}" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="justifierModalLabel{{ $absence->id }}">Justifier l'absence du {{ $absence->seance->date ? $absence->seance->date->format('d/m/Y') : 'N/A' }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('esbtp.mes-absences.justify', $absence->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="attendance_id" value="{{ $absence->id }}">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="motif">Motif de l'absence</label>
                                <select name="motif" id="motif" class="form-control" required>
                                    <option value="">Sélectionnez un motif</option>
                                    <option value="Maladie">Maladie</option>
                                    <option value="Accident">Accident</option>
                                    <option value="Rendez-vous médical">Rendez-vous médical</option>
                                    <option value="Problème de transport">Problème de transport</option>
                                    <option value="Cas de force majeure">Cas de force majeure</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="commentaire">Détails / Commentaire</label>
                                <textarea name="commentaire" id="commentaire" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="document">Document justificatif (PDF, JPG, PNG)</label>
                                <input type="file" name="document" id="document" class="form-control-file" required>
                                <small class="form-text text-muted">Téléchargez un certificat médical ou tout autre document justifiant votre absence.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Soumettre la justification</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('absencesChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($moisLabels),
                datasets: [{
                    label: 'Nombre d\'absences',
                    data: @json($absencesData),
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
