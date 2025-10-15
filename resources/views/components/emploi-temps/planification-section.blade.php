@props(['planificationData' => null, 'emploiTemps'])

@if(isset($planificationData) && !empty($planificationData))
<div class="main-card mb-4">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-calendar-check"></i>
            Planification Académique
        </div>
        <div class="main-card-subtitle">Suivi des heures planifiées pour cette classe</div>
    </div>
    <div class="main-card-body">
        @if($planificationData['planifications_configurees'])
            <div class="row">
                <div class="col-lg-8">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light text-dark">
                                <tr>
                                    <th width="35%">Matière</th>
                                    <th width="25%">Enseignant</th>
                                    <th width="15%" class="text-center">H. Total</th>
                                    <th width="15%" class="text-center">H. Restantes</th>
                                    <th width="10%" class="text-center">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($planificationData['matieres_planifiees'] as $matiere)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 25px; height: 25px; flex-shrink: 0;">
                                                <i class="fas fa-book" style="font-size: 10px;"></i>
                                            </div>
                                            <strong>{{ $matiere['matiere']->name }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        @if($matiere['enseignant_affiche'])
                                            <small><i class="fas fa-user-tie text-secondary me-1"></i>{{ $matiere['enseignant_affiche']->name }}</small>
                                        @else
                                            <small class="text-muted"><i class="fas fa-user-slash me-1"></i>Non assigné</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $matiere['volume_horaire_total'] }}h</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $matiere['heures_restantes'] > 0 ? 'success' : 'warning' }}">
                                            {{ $matiere['heures_restantes'] }}h
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small><strong>{{ $matiere['pourcentage_utilise'] ?? 0 }}%</strong></small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card bg-light border-0">
                        <div class="card-body text-center">
                            <h6 class="text-primary"><i class="fas fa-chart-pie me-2"></i>Résumé</h6>
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="text-primary mb-1">{{ $planificationData['heures_totales'] }}</h4>
                                    <small>H. planifiées</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success mb-1">{{ $planificationData['heures_restantes'] }}</h4>
                                    <small>H. restantes</small>
                                </div>
                            </div>
                            <hr>
                            <small class="text-muted">
                                <i class="fas fa-graduation-cap me-1"></i>{{ $emploiTemps->classe->name }}<br>
                                <i class="fas fa-calendar me-1"></i>{{ $emploiTemps->semestre ?? 'Année complète' }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning mb-0">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <h6><strong>Planification non configurée</strong></h6>
                        <p class="mb-2">{{ $planificationData['message_configuration'] ?? 'Aucune planification académique configurée pour cette classe.' }}</p>
                        <small class="text-muted">Vous devez d'abord définir les volumes horaires des matières pour cette classe.</small>
                        @if($emploiTemps->classe && $emploiTemps->annee)
                            <button type="button" class="btn btn-warning btn-sm" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#volumeConfigModal"
                                    data-filiere-id="{{ $emploiTemps->classe->filiere_id }}"
                                    data-niveau-id="{{ $emploiTemps->classe->niveau_etude_id }}"
                                    data-annee-id="{{ $emploiTemps->annee->id }}"
                                    data-combination-name="{{ $emploiTemps->classe->filiere->name ?? 'Filière' }} - {{ $emploiTemps->classe->niveau->name ?? 'Niveau' }}"
                                    onclick="openVolumeConfigModal(this)">
                                <i class="fas fa-cog me-1"></i>Configurer les volumes horaires
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endif
