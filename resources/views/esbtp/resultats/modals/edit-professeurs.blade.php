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
                        <div class="professeur-card mb-3" style="
                            border: 2px solid #d1ecf1;
                            border-radius: 12px;
                            background: linear-gradient(135deg, #ffffff 0%, #d1ecf1 100%);
                            transition: all 0.3s ease;
                            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.1);
                        " onmouseover="this.style.borderColor='#17a2b8'; this.style.boxShadow='0 4px 12px rgba(23, 162, 184, 0.2)'" onmouseout="this.style.borderColor='#d1ecf1'; this.style.boxShadow='0 2px 8px rgba(23, 162, 184, 0.1)'">
                            <div class="card-body" style="padding: 1.5rem;">
                                <div class="row align-items-center">
                                    <div class="col-md-1 text-center">
                                        <div style="
                                            width: 50px;
                                            height: 50px;
                                            border-radius: 50%;
                                            background: linear-gradient(135deg, #17a2b8, #138496);
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            color: white;
                                            font-weight: bold;
                                            font-size: 1.2rem;
                                            box-shadow: 0 3px 8px rgba(23, 162, 184, 0.3);
                                        ">
                                            <i class="fas fa-book-open"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <h5 class="mb-1 fw-bold" style="color: #0c5460; font-size: 1.05rem;">
                                            {{ $matiere->name }}
                                        </h5>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                            <span class="badge" style="
                                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                                padding: 0.35rem 0.7rem;
                                                font-size: 0.75rem;
                                                font-weight: 500;
                                                border-radius: 6px;
                                            ">
                                                <i class="fas fa-hashtag me-1"></i>{{ $matiere->code }}
                                            </span>
                                            <span class="badge" style="
                                                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                                                padding: 0.35rem 0.7rem;
                                                font-size: 0.75rem;
                                                font-weight: 500;
                                                border-radius: 6px;
                                            ">
                                                <i class="fas fa-balance-scale me-1"></i>Coeff: {{ $matiere->pivot->coefficient ?? 1 }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div style="
                                            background: white;
                                            border-radius: 10px;
                                            padding: 0.5rem;
                                            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
                                        ">
                                            <label class="form-label mb-2 fw-semibold" style="
                                                color: #17a2b8;
                                                font-size: 0.8rem;
                                                text-transform: uppercase;
                                                letter-spacing: 0.5px;
                                                margin-left: 0.5rem;
                                            ">
                                                <i class="fas fa-user-tie me-1"></i>Enseignant
                                            </label>
                                            <select class="form-select" name="professeur_{{ $matiere->id }}"
                                                    data-matiere-id="{{ $matiere->id }}"
                                                    style="
                                                        border: 2px solid #bee5eb;
                                                        font-weight: 500;
                                                        color: #0c5460;
                                                        transition: all 0.3s ease;
                                                    "
                                                    onfocus="this.style.borderColor='#17a2b8'; this.style.boxShadow='0 0 0 0.2rem rgba(23, 162, 184, 0.25)'"
                                                    onblur="this.style.borderColor='#bee5eb'; this.style.boxShadow='none'">
                                                <option value="">-- Sélectionner un enseignant --</option>
                                                @foreach(($enseignantsParMatiere[$matiere->id] ?? []) as $enseignant)
                                                    <option value="{{ $enseignant->id }}">
                                                        {{ $enseignant->user->name ?? 'Enseignant #' . $enseignant->id }}
                                                        @if($enseignant->specialites_string)
                                                            - {{ Str::limit($enseignant->specialites_string, 25) }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
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

