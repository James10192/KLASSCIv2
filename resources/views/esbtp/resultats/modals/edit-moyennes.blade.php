<!-- Modal: Édition groupée des moyennes -->
<div class="modal fade" id="modalEditMoyennes" tabindex="-1" aria-labelledby="modalEditMoyennesLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border-radius: 12px 12px 0 0; padding: 1.5rem;">
                <div>
                    <h4 class="modal-title mb-1" id="modalEditMoyennesLabel" style="font-weight: 600;">
                        <i class="fas fa-calculator me-2"></i>Édition Groupée des Moyennes
                    </h4>
                    <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">
                        Saisir les moyennes par matière pour les étudiants sélectionnés
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <!-- Mode selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Mode d'édition</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="editMode" id="modeByMatiere" value="matiere" checked>
                        <label class="btn btn-outline-primary" for="modeByMatiere">
                            <i class="fas fa-book me-2"></i>Par Matière
                        </label>

                        <input type="radio" class="btn-check" name="editMode" id="modeByStudent" value="student">
                        <label class="btn btn-outline-primary" for="modeByStudent">
                            <i class="fas fa-user-graduate me-2"></i>Par Étudiant
                        </label>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <strong>Par Matière:</strong> Sélectionner une matière et saisir les notes de tous les étudiants<br>
                        <strong>Par Étudiant:</strong> Sélectionner des étudiants et saisir toutes leurs matières
                    </small>
                </div>

                <!-- Mode: Par Matière -->
                <div id="modeByMatiereContent">
                    <div class="mb-3">
                        <label for="selectMatiere" class="form-label fw-bold">Sélectionner une matière</label>
                        <select class="form-select" id="selectMatiere">
                            <option value="">-- Choisir une matière --</option>
                            @foreach($matieres as $matiere)
                                <option value="{{ $matiere->id }}" data-coeff="{{ $matiere->pivot->coefficient ?? 1 }}">
                                    {{ $matiere->name }} (Coeff: {{ $matiere->pivot->coefficient ?? 1 }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="studentsGradesTable" style="display: none;">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Matière sélectionnée:</strong> <span id="selectedMatiereName"></span>
                            <br>
                            <strong>Coefficient:</strong> <span id="selectedMatiereCoeff"></span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Matricule</th>
                                        <th>Nom complet</th>
                                        <th style="width: 150px;">Moyenne /20</th>
                                        <th style="width: 100px;">Statut</th>
                                    </tr>
                                </thead>
                                <tbody id="gradesTableBody">
                                    <!-- Will be populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Mode: Par Étudiant -->
                <div id="modeByStudentContent" style="display: none;">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong><span id="studentModeCount">0</span> étudiant(s) sélectionné(s)</strong>
                    </div>

                    <div class="accordion" id="studentAccordion">
                        <!-- Will be populated dynamically -->
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 1rem 1.5rem; background-color: #f8f9fa; border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-primary" onclick="saveMoyennes()">
                    <i class="fas fa-save me-2"></i>Enregistrer les moyennes
                </button>
            </div>
        </div>
    </div>
</div>

