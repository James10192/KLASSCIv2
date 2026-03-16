{{-- Modal: Édition groupée des absences --}}
<div class="modal fade" id="modalEditAbsences" tabindex="-1" aria-labelledby="modalEditAbsencesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: var(--sr-radius, 16px); overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none; padding: 1.25rem 1.5rem;">
                <div>
                    <h5 class="modal-title" id="modalEditAbsencesLabel" style="font-weight: 700; margin: 0 0 0.2rem;">
                        <i class="fas fa-calendar-times me-2"></i>Édition des Absences
                    </h5>
                    <p style="margin: 0; font-size: 0.82rem; opacity: 0.8;">Saisir les absences justifiées et non justifiées (en heures)</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.85rem 1rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; margin-bottom: 1.25rem; font-size: 0.85rem;">
                    <i class="fas fa-info-circle" style="color: #d97706; margin-top: 0.15rem; flex-shrink: 0;"></i>
                    <div>
                        <strong><span id="absencesStudentCount">0</span> étudiant(s) sélectionné(s)</strong><br>
                        <span style="color: #92400e;">La note d'assiduité sera calculée automatiquement selon le barème.</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Matricule</th>
                                <th style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Nom complet</th>
                                <th style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; width: 160px;" class="text-center">Justifiées (h)</th>
                                <th style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; width: 160px;" class="text-center">Non justifiées (h)</th>
                            </tr>
                        </thead>
                        <tbody id="absencesTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f3f4f6; padding: 1rem 1.5rem;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Annuler</button>
                <button type="button" class="btn" onclick="saveAbsences()" style="border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none;">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
