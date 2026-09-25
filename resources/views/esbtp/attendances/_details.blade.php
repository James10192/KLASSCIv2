{{-- Detail d'une presence, ouvert depuis sa ligne. Rendu dans la derniere cellule de la ligne : la suite chargee au defilement apporte ses propres modales. --}}
<div class="modal fade" id="detailsModal{{ $attendance->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check me-2"></i>Détails de la Présence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <dl class="row detail-dl">
                    <dt class="col-sm-4">Étudiant</dt>
                    <dd class="col-sm-8">{{ $attendance->etudiant->nom_complet }}</dd>

                    <dt class="col-sm-4">Classe</dt>
                    <dd class="col-sm-8">{{ $attendance->classe->name ?? ($attendance->etudiant->classe->name ?? 'N/A') }}</dd>

                    <dt class="col-sm-4">Matière</dt>
                    <dd class="col-sm-8">{{ $attendance->matiere->name ?? ($attendance->seanceCours->matiere->name ?? 'N/A') }}</dd>

                    <dt class="col-sm-4">Date</dt>
                    <dd class="col-sm-8">{{ $attendance->date->format('d/m/Y') }}</dd>

                    <dt class="col-sm-4">Statut</dt>
                    <dd class="col-sm-8">
                        @if($attendance->statut === 'present')
                            <span class="att-status-pill sp-present"><i class="fas fa-check-circle"></i>Présent</span>
                        @elseif($attendance->statut === 'absent')
                            <span class="att-status-pill sp-absent"><i class="fas fa-times-circle"></i>Absent</span>
                        @elseif($attendance->statut === 'retard' || $attendance->statut === 'late')
                            <span class="att-status-pill sp-retard"><i class="fas fa-clock"></i>Retard</span>
                        @elseif($attendance->statut === 'excuse')
                            <span class="att-status-pill sp-excuse"><i class="fas fa-file-medical"></i>Excusé</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Enseignant</dt>
                    <dd class="col-sm-8">{{ $attendance->teacher->user->name ?? 'N/A' }}</dd>

                    <dt class="col-sm-4">Créé le</dt>
                    <dd class="col-sm-8">{{ $attendance->created_at->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
