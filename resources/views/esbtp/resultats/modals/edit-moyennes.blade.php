{{-- Modal: Édition groupée des moyennes --}}
<div class="modal fade" id="modalEditMoyennes" tabindex="-1" aria-labelledby="modalEditMoyennesLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: var(--sr-radius, 16px); overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: var(--sr-primary-gradient, linear-gradient(135deg, #0453cb, #5e91de)); color: white; border: none; padding: 1.25rem 1.5rem;">
                <div>
                    <h5 class="modal-title" id="modalEditMoyennesLabel" style="font-weight: 700; margin: 0 0 0.2rem;">
                        <i class="fas fa-calculator me-2"></i>Édition des Moyennes
                    </h5>
                    <p style="margin: 0; font-size: 0.82rem; opacity: 0.8;">Saisir les moyennes par matière ou par étudiant</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                {{-- Mode selection --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.5rem;">
                    <label class="d-block" style="cursor: pointer;" id="modeCardMatiere"
                           onmouseover="this.style.borderColor='#0453cb'" onmouseout="this.style.borderColor=document.getElementById('modeMatiere').checked ? '#0453cb' : '#e5e7eb'">
                        <input type="radio" name="editMode" id="modeMatiere" value="matiere" checked class="d-none"
                               onchange="document.getElementById('sectionMatiere').style.display='block'; document.getElementById('sectionEtudiant').style.display='none'; updateModeCardStyles();">
                        <div style="border: 2px solid #0453cb; border-radius: 12px; padding: 1rem; text-align: center; transition: all 0.2s; background: rgba(4,83,203,0.04);">
                            <i class="fas fa-book" style="font-size: 1.5rem; color: #0453cb; margin-bottom: 0.5rem; display: block;"></i>
                            <div style="font-weight: 700; color: #1e293b; font-size: 0.9rem;">Par Matière</div>
                            <div style="font-size: 0.75rem; color: #6b7280;">Sélectionnez une matière, éditez toutes les notes</div>
                        </div>
                    </label>
                    <label class="d-block" style="cursor: pointer;" id="modeCardEtudiant"
                           onmouseover="this.style.borderColor='#0453cb'" onmouseout="this.style.borderColor=document.getElementById('modeEtudiant').checked ? '#0453cb' : '#e5e7eb'">
                        <input type="radio" name="editMode" id="modeEtudiant" value="etudiant" class="d-none"
                               onchange="document.getElementById('sectionMatiere').style.display='none'; document.getElementById('sectionEtudiant').style.display='block'; updateModeCardStyles();">
                        <div style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 1rem; text-align: center; transition: all 0.2s;">
                            <i class="fas fa-user-graduate" style="font-size: 1.5rem; color: #6b7280; margin-bottom: 0.5rem; display: block;"></i>
                            <div style="font-weight: 700; color: #1e293b; font-size: 0.9rem;">Par Étudiant</div>
                            <div style="font-size: 0.75rem; color: #6b7280;">Accordéon par étudiant, toutes les matières</div>
                        </div>
                    </label>
                </div>

                {{-- Section: Par Matière --}}
                <div id="sectionMatiere">
                    <div style="margin-bottom: 1rem;">
                        <label style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; margin-bottom: 0.35rem; display: block;">Matière</label>
                        <select class="form-select" id="matiereSelector" onchange="populateGradesTable(this.value)" style="border-radius: 10px; border: 1.5px solid #e5e7eb; padding: 0.6rem 1rem;">
                            <option value="">— Sélectionner une matière —</option>
                            @foreach($matieres as $matiere)
                                <option value="{{ $matiere->id }}" data-coeff="{{ $matiere->pivot->coefficient ?? 1 }}" data-name="{{ $matiere->name }}">
                                    {{ $matiere->name }} (coeff. {{ $matiere->pivot->coefficient ?? 1 }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="selectedMatiereInfo" style="display: none; padding: 0.75rem 1rem; background: #dbeafe; border-radius: 10px; margin-bottom: 1rem;">
                        <span style="font-weight: 700; color: #0453cb;" id="selectedMatiereName"></span>
                        <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280;">Coefficient: <strong id="selectedMatiereCoeff"></strong></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="gradesTable">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Matricule</th>
                                    <th style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Nom</th>
                                    <th style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;" class="text-center">Moy. calculée</th>
                                    <th style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;" class="text-center">Moyenne à enregistrer</th>
                                </tr>
                            </thead>
                            <tbody id="gradesTableBody">
                                <tr><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-arrow-up me-2"></i>Sélectionnez une matière</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Section: Par Étudiant --}}
                <div id="sectionEtudiant" style="display: none;">
                    <div style="padding: 0.75rem 1rem; background: #f8fafc; border-radius: 10px; margin-bottom: 1rem; font-size: 0.85rem; color: #6b7280;">
                        <i class="fas fa-users me-1"></i> <span id="studentAccordionCount">0</span> étudiant(s) sélectionné(s)
                    </div>
                    <div class="accordion" id="studentAccordion"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f3f4f6; padding: 1rem 1.5rem;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveMoyennes()" style="border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #0453cb, #5e91de); border: none;">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
