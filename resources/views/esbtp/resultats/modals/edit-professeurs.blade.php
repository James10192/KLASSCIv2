<!-- Modal: Assignation groupée des professeurs -->
<div class="modal fade" id="modalEditProfesseurs" tabindex="-1" aria-labelledby="modalEditProfesseursLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white; border-radius: 12px 12px 0 0; padding: 1.5rem;">
                <div>
                    <h4 class="modal-title mb-1" id="modalEditProfesseursLabel" style="font-weight: 600;">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Assignation des Professeurs
                    </h4>
                    <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">
                        Assigner un enseignant à chaque matière pour cette classe
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Les enseignants assignés seront automatiquement associés à tous les résultats de la matière pour la période sélectionnée.
                </div>

                <form id="formProfesseurs">
                    @foreach($matieres as $matiere)
                        <div class="card mb-3" style="border: 1px solid #e5e7eb;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-5">
                                        <h6 class="mb-1 fw-bold">{{ $matiere->name }}</h6>
                                        <small class="text-muted">Code: {{ $matiere->code }} | Coeff: {{ $matiere->pivot->coefficient ?? 1 }}</small>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-select" name="professeur_{{ $matiere->id }}" data-matiere-id="{{ $matiere->id }}">
                                            <option value="">-- Sélectionner un enseignant --</option>
                                            @foreach($enseignants as $enseignant)
                                                <option value="{{ $enseignant->id }}">
                                                    {{ $enseignant->user->name ?? 'Enseignant #' . $enseignant->id }}
                                                    @if($enseignant->specialites_string)
                                                        ({{ Str::limit($enseignant->specialites_string, 30) }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if($matieres->isEmpty())
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Aucune matière n'est configurée pour cette classe.
                        </div>
                    @endif
                </form>
            </div>
            <div class="modal-footer" style="padding: 1rem 1.5rem; background-color: #f8f9fa; border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-info" onclick="saveProfesseurs()">
                    <i class="fas fa-save me-2"></i>Enregistrer les assignations
                </button>
            </div>
        </div>
    </div>
</div>

