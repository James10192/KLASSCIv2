@extends('layouts.app')

@section('title', 'Mes Absences')

@section('page_title', 'Mes Absences')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-info">
                <h1 class="page-title">Mes Absences</h1>
                <p class="page-description">Consultez vos absences et justifiez-les</p>
            </div>
        </div>

        <!-- Filtres -->
        <div class="main-card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter"></i>
                    Filtres de recherche
                </h3>
            </div>
            <div class="card-body">
                <form action="{{ route('esbtp.mes-absences.index') }}" method="GET" class="row">
                    <div class="col-md-3 mb-3">
                        <label for="annee_universitaire_id" class="form-label">Année Universitaire</label>
                        <select name="annee_universitaire_id" id="annee_universitaire_id" class="form-control">
                            @foreach($anneesUniversitaires as $annee)
                                <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                                    {{ $annee->annee_debut }}-{{ $annee->annee_fin }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="mois" class="form-label">Mois</label>
                        <select name="mois" id="mois" class="form-control">
                            <option value="">Tous les mois</option>
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $mois == $m ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="justifie" class="form-label">Justification</label>
                        <select name="justifie" id="justifie" class="form-control">
                            <option value="">Toutes les absences</option>
                            <option value="1" {{ $justifie === '1' ? 'selected' : '' }}>Justifiées</option>
                            <option value="0" {{ $justifie === '0' ? 'selected' : '' }}>Non justifiées</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn-acasi btn-acasi-primary me-2">
                            <i class="fas fa-search"></i>
                            Filtrer
                        </button>
                        <a href="{{ route('esbtp.mes-absences.index') }}" class="btn-acasi btn-acasi-secondary">
                            <i class="fas fa-redo"></i>
                            Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card stat-card-primary">
                <div class="stat-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-label">Total des absences</span>
                    <span class="stat-value">{{ $totalAbsences }}</span>
                    <span class="stat-description">Toutes les absences enregistrées</span>
                </div>
            </div>

            <div class="stat-card stat-card-success">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-label">Absences justifiées</span>
                    <span class="stat-value">{{ $absencesJustifiees }}</span>
                    <span class="stat-description">{{ $totalAbsences > 0 ? round(($absencesJustifiees / $totalAbsences) * 100, 2) : 0 }}% du total</span>
                </div>
            </div>

            <div class="stat-card stat-card-danger">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-label">Absences non justifiées</span>
                    <span class="stat-value">{{ $absencesNonJustifiees }}</span>
                    <span class="stat-description">{{ $totalAbsences > 0 ? round(($absencesNonJustifiees / $totalAbsences) * 100, 2) : 0 }}% du total</span>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="dashboard-main-grid" style="grid-template-columns: 2fr 1fr;">
            <!-- Liste des absences -->
            <div class="main-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i>
                        Liste de mes absences
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-modern">
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
                                                <span class="status-badge status-badge-success">Oui</span>
                                            @else
                                                <span class="status-badge status-badge-danger">Non</span>
                                            @endif
                                        </td>
                                        <td>{{ $absence->commentaire ?? 'Aucun commentaire' }}</td>
                                        <td>
                                            @if(!$absence->justifie)
                                                <button type="button" class="btn-acasi btn-acasi-primary btn-acasi-sm" data-bs-toggle="modal" data-bs-target="#justifierModal{{ $absence->id }}">
                                                    <i class="fas fa-file-upload"></i>
                                                    Justifier
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="empty-state">
                                                <i class="fas fa-inbox"></i>
                                                <p>Aucune absence trouvée.</p>
                                            </div>
                                        </td>
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

            <!-- Panneau latéral -->
            <div class="dashboard-content-area">
                <!-- Graphique des absences -->
                <div class="main-card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar"></i>
                            Évolution des absences
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="absencesChart" width="100%" height="300"></canvas>
                    </div>
                </div>

                <!-- Règlement des absences -->
                <div class="main-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i>
                            Règlement des absences
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <div class="alert-content">
                                <i class="fas fa-question-circle alert-icon"></i>
                                <div>
                                    <h4 class="alert-title">Comment justifier une absence :</h4>
                                    <ol class="mb-0 ps-3">
                                        <li>Cliquez sur le bouton "Justifier" à côté de l'absence concernée.</li>
                                        <li>Téléchargez un document justificatif (certificat médical, convocation administrative, etc.).</li>
                                        <li>Ajoutez un commentaire expliquant la raison de votre absence.</li>
                                        <li>Soumettez votre demande de justification.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-warning mt-3">
                            <div class="alert-content">
                                <i class="fas fa-exclamation-triangle alert-icon"></i>
                                <div>
                                    <p class="mb-0"><strong>Attention :</strong> Les justifications sont soumises à validation par l'administration.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals pour justifier les absences -->
@foreach($absences as $absence)
    @if(!$absence->justifie)
        <div class="modal fade" id="justifierModal{{ $absence->id }}" tabindex="-1" aria-labelledby="justifierModalLabel{{ $absence->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="justifierModalLabel{{ $absence->id }}">Justifier l'absence du {{ $absence->seance->date ? $absence->seance->date->format('d/m/Y') : 'N/A' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('esbtp.mes-absences.justify', $absence->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="attendance_id" value="{{ $absence->id }}">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="motif{{ $absence->id }}" class="form-label">Motif de l'absence</label>
                                <select name="motif" id="motif{{ $absence->id }}" class="form-control" required>
                                    <option value="">Sélectionnez un motif</option>
                                    <option value="Maladie">Maladie</option>
                                    <option value="Accident">Accident</option>
                                    <option value="Rendez-vous médical">Rendez-vous médical</option>
                                    <option value="Problème de transport">Problème de transport</option>
                                    <option value="Cas de force majeure">Cas de force majeure</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="commentaire{{ $absence->id }}" class="form-label">Détails / Commentaire</label>
                                <textarea name="commentaire" id="commentaire{{ $absence->id }}" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="document{{ $absence->id }}" class="form-label">Document justificatif (PDF, JPG, PNG)</label>
                                <input type="file" name="document" id="document{{ $absence->id }}" class="form-control" required>
                                <small class="form-text text-muted">Téléchargez un certificat médical ou tout autre document justifiant votre absence.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-acasi btn-acasi-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn-acasi btn-acasi-primary">
                                <i class="fas fa-paper-plane"></i>
                                Soumettre la justification
                            </button>
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
