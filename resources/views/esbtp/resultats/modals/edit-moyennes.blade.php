<!-- Modal: Édition groupée des moyennes -->
<div class="modal fade" id="modalEditMoyennes" tabindex="-1" aria-labelledby="modalEditMoyennesLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; border-radius: 12px 12px 0 0; padding: 1.5rem;">
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
                <div class="mb-4" style="
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    padding: 1.5rem;
                    border-radius: 12px;
                    border: 2px solid #dee2e6;
                ">
                    <label class="form-label fw-bold mb-3" style="
                        color: #495057;
                        font-size: 1rem;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    ">
                        <i class="fas fa-sliders-h me-2"></i>Mode d'édition
                    </label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="editMode" id="modeByMatiere" value="matiere" checked>
                            <label class="btn w-100 h-100 mode-card" id="labelModeByMatiere" for="modeByMatiere" style="
                                border: 2px solid #0d6efd;
                                background: white;
                                padding: 1.25rem;
                                border-radius: 10px;
                                transition: all 0.3s ease;
                                box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
                            " onmouseover="if(!this.previousElementSibling.checked) this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <div style="
                                        width: 50px;
                                        height: 50px;
                                        border-radius: 50%;
                                        background: linear-gradient(135deg, #0d6efd, #0a58ca);
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        color: white;
                                        font-size: 1.5rem;
                                    ">
                                        <i class="fas fa-book"></i>
                                    </div>
                                </div>
                                <h6 class="mb-2 fw-bold" style="color: #0d6efd;">Par Matière</h6>
                                <small class="text-muted" style="font-size: 0.8rem;">
                                    Sélectionner une matière et saisir les notes de tous les étudiants
                                </small>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="editMode" id="modeByStudent" value="student">
                            <label class="btn w-100 h-100 mode-card" id="labelModeByStudent" for="modeByStudent" style="
                                border: 2px solid #6c757d;
                                background: white;
                                padding: 1.25rem;
                                border-radius: 10px;
                                transition: all 0.3s ease;
                                box-shadow: 0 2px 8px rgba(108, 117, 125, 0.15);
                            " onmouseover="if(!this.previousElementSibling.checked) this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <div style="
                                        width: 50px;
                                        height: 50px;
                                        border-radius: 50%;
                                        background: linear-gradient(135deg, #6c757d, #495057);
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        color: white;
                                        font-size: 1.5rem;
                                    ">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                </div>
                                <h6 class="mb-2 fw-bold" style="color: #6c757d;">Par Étudiant</h6>
                                <small class="text-muted" style="font-size: 0.8rem;">
                                    Sélectionner des étudiants et saisir toutes leurs matières
                                </small>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Mode: Par Matière -->
                <div id="modeByMatiereContent">
                    <div class="mb-4" style="
                        background: white;
                        padding: 1.25rem;
                        border-radius: 12px;
                        border: 2px solid #e3f2fd;
                        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
                    ">
                        <label for="selectMatiere" class="form-label fw-bold mb-3" style="
                            color: #0d6efd;
                            font-size: 0.9rem;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        ">
                            <i class="fas fa-graduation-cap me-2"></i>Sélectionner une matière
                        </label>
                        <select class="form-select form-select-lg" id="selectMatiere" style="
                            border: 2px solid #bbdefb;
                            font-weight: 500;
                            color: #0d6efd;
                            transition: all 0.3s ease;
                        " onfocus="this.style.borderColor='#0d6efd'; this.style.boxShadow='0 0 0 0.25rem rgba(13, 110, 253, 0.25)'" onblur="this.style.borderColor='#bbdefb'; this.style.boxShadow='none'">
                            <option value="">-- Choisir une matière --</option>
                            @foreach($matieres as $matiere)
                                <option value="{{ $matiere->id }}" data-coeff="{{ $matiere->pivot->coefficient ?? 1 }}" data-name="{{ $matiere->name }}">
                                    {{ $matiere->name }} (Coeff: {{ $matiere->pivot->coefficient ?? 1 }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="studentsGradesTable" style="display: none;">
                        <div style="
                            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
                            color: white;
                            padding: 1.25rem;
                            border-radius: 12px;
                            margin-bottom: 1.5rem;
                            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
                        ">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1 fw-bold">
                                        <i class="fas fa-book-open me-2"></i>
                                        <span id="selectedMatiereName"></span>
                                    </h5>
                                    <p class="mb-0 opacity-90" style="font-size: 0.9rem;">
                                        Saisir les moyennes pour cette matière
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div style="
                                        background: rgba(255, 255, 255, 0.2);
                                        backdrop-filter: blur(10px);
                                        border: 2px solid rgba(255, 255, 255, 0.3);
                                        border-radius: 10px;
                                        padding: 0.75rem 1.25rem;
                                        display: inline-block;
                                    ">
                                        <div style="font-size: 0.75rem; opacity: 0.9; margin-bottom: 0.25rem;">COEFFICIENT</div>
                                        <div style="font-size: 1.75rem; font-weight: 700;" id="selectedMatiereCoeff"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive" style="
                            border-radius: 12px;
                            overflow: hidden;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                        ">
                            <table class="table table-hover align-middle mb-0" style="background: white;">
                                <thead style="
                                    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                                    border-bottom: 2px solid #dee2e6;
                                ">
                                    <tr>
                                        <th style="padding: 1rem; font-weight: 600; color: #495057;">
                                            <i class="fas fa-id-card me-2"></i>Matricule
                                        </th>
                                        <th style="padding: 1rem; font-weight: 600; color: #495057;">
                                            <i class="fas fa-user me-2"></i>Nom complet
                                        </th>
                                        <th class="text-center" style="width: 150px; padding: 1rem; font-weight: 600; color: #495057;">
                                            <i class="fas fa-calculator me-2"></i>Moyenne calculée
                                        </th>
                                        <th style="width: 180px; padding: 1rem; font-weight: 600; color: #495057;">
                                            <i class="fas fa-chart-line me-2"></i>Moyenne à enregistrer
                                        </th>
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
                    <div style="
                        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
                        color: white;
                        padding: 1.25rem;
                        border-radius: 12px;
                        margin-bottom: 1.5rem;
                        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
                    ">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-1 fw-bold">
                                    <i class="fas fa-users me-2"></i>
                                    <span id="studentModeCount">0</span> étudiant(s) sélectionné(s)
                                </h5>
                                <p class="mb-0 opacity-90" style="font-size: 0.9rem;">
                                    Saisir toutes les matières pour chaque étudiant
                                </p>
                            </div>
                            <div style="
                                width: 60px;
                                height: 60px;
                                border-radius: 50%;
                                background: rgba(255, 255, 255, 0.2);
                                backdrop-filter: blur(10px);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                border: 2px solid rgba(255, 255, 255, 0.3);
                                font-size: 1.5rem;
                            ">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        </div>
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

