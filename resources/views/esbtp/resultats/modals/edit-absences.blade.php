<!-- Modal: Édition groupée des absences -->
<div class="modal fade" id="modalEditAbsences" tabindex="-1" aria-labelledby="modalEditAbsencesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #ffc107, #ff9800); color: white; border-radius: 12px 12px 0 0; padding: 1.5rem;">
                <div>
                    <h4 class="modal-title mb-1" id="modalEditAbsencesLabel" style="font-weight: 600;">
                        <i class="fas fa-calendar-times me-2"></i>Édition Groupée des Absences
                    </h4>
                    <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">
                        Saisir les absences pour les étudiants sélectionnés
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong><span id="absencesStudentCount">0</span> étudiant(s) sélectionné(s)</strong>
                    <br>
                    Saisissez les absences justifiées et non justifiées (en heures) pour chaque étudiant.
                    <br>
                    <small class="text-muted">La note d'assiduité sera calculée automatiquement selon le barème ESBTP.</small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Matricule</th>
                                <th>Nom complet</th>
                                <th style="width: 180px;">Justifiées (h)</th>
                                <th style="width: 180px;">Non justifiées (h)</th>
                            </tr>
                        </thead>
                        <tbody id="absencesTableBody">
                            <!-- Will be populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="padding: 1rem 1.5rem; background-color: #f8f9fa; border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-warning" onclick="saveAbsences()">
                    <i class="fas fa-save me-2"></i>Enregistrer les absences
                </button>
            </div>
        </div>
    </div>
</div>
