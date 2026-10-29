{{-- Modal: Configuration des matières --}}
<div class="modal fade" id="modalEditMatieres" tabindex="-1" aria-labelledby="modalEditMatieresLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: var(--sr-radius, 16px); overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #64748b, #475569); color: white; border: none; padding: 1.25rem 1.5rem;">
                <div>
                    <h5 class="modal-title" id="modalEditMatieresLabel" style="font-weight: 700; margin: 0 0 0.2rem;">
                        <i class="fas fa-cog me-2"></i>Configuration des Matières
                    </h5>
                    <p style="margin: 0; font-size: 0.82rem; opacity: 0.8;">Coefficients et type d'enseignement</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 1rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; margin-bottom: 1.25rem; font-size: 0.82rem;">
                    <i class="fas fa-exclamation-triangle" style="color: #d97706; margin-top: 0.15rem;"></i>
                    <span style="color: #92400e;">La modification des coefficients et du type d'enseignement affectera le calcul et l'affichage des bulletins.</span>
                </div>

                <div class="accordion" id="accordionMatieres">
                    {{-- Section 1: Coefficients --}}
                    <div class="accordion-item" style="border: 1.5px solid #e5e7eb; border-radius: 12px; margin-bottom: 0.75rem; overflow: hidden;">
                        <h2 class="accordion-header" id="headingCoefficients">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCoefficients" style="background: linear-gradient(135deg, #0453cb, #5e91de); color: white; font-weight: 700; padding: 1rem 1.25rem; font-size: 0.95rem;">
                                <i class="fas fa-balance-scale me-2"></i>Coefficients des matières
                            </button>
                        </h2>
                        <div id="collapseCoefficients" class="accordion-collapse collapse" data-bs-parent="#accordionMatieres">
                            <div class="accordion-body" style="padding: 1.25rem;">
                                <form id="formMatieres">
                                    @foreach($matieres as $matiere)
                                        <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; border: 1.5px solid #e5e7eb; border-radius: 10px; margin-bottom: 0.5rem; transition: border-color 0.2s;">
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="font-weight: 700; color: #1e293b; font-size: 0.88rem;">{{ $matiere->name }}</div>
                                                @if($matiere->code)
                                                    <span style="font-size: 0.65rem; background: #f1f5f9; color: #6b7280; padding: 0.1rem 0.4rem; border-radius: 4px;">{{ $matiere->code }}</span>
                                                @endif
                                            </div>
                                            <div style="width: 120px; flex-shrink: 0;">
                                                <input type="number" class="form-control" min="0" step="0.5"
                                                       name="coeff_{{ $matiere->id }}"
                                                       data-matiere-id="{{ $matiere->id }}"
                                                       value="{{ $matiere->pivot->coefficient ?? 1 }}"
                                                       style="border-radius: 8px; border: 1.5px solid #e5e7eb; font-weight: 700; text-align: center; font-size: 1rem;">
                                            </div>
                                        </div>
                                    @endforeach

                                    @if($matieres->isEmpty())
                                        <div style="text-align: center; padding: 1.5rem; color: #6b7280;">
                                            <i class="fas fa-info-circle" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
                                            Aucune matière configurée.
                                        </div>
                                    @endif

                                    @if($matieres->isNotEmpty())
                                        <div style="background: linear-gradient(135deg, #0453cb, #5e91de); border-radius: 12px; padding: 1rem 1.25rem; color: white; margin-top: 1rem; display: flex; align-items: center; justify-content: space-between;">
                                            <div>
                                                <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.8;">Somme des coefficients</div>
                                                <div style="font-size: 2rem; font-weight: 800; line-height: 1;" id="totalCoefficient">
                                                    {{ $matieres->sum(function($m) { return $m->pivot->coefficient ?? 1; }) }}
                                                </div>
                                            </div>
                                            <i class="fas fa-calculator" style="font-size: 1.5rem; opacity: 0.4;"></i>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Type d'enseignement --}}
                    <div class="accordion-item" style="border: 1.5px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                        <h2 class="accordion-header" id="headingTypeEnseignement">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTypeEnseignement" style="background: linear-gradient(135deg, #10b981, #059669); color: white; font-weight: 700; padding: 1rem 1.25rem; font-size: 0.95rem;">
                                <i class="fas fa-graduation-cap me-2"></i>Type d'enseignement
                            </button>
                        </h2>
                        <div id="collapseTypeEnseignement" class="accordion-collapse collapse" data-bs-parent="#accordionMatieres">
                            <div class="accordion-body" style="padding: 1.25rem;">
                                <div style="display: flex; gap: 0.5rem; justify-content: center; margin-bottom: 1rem;">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnToutesGenerales" style="border-radius: 20px; font-size: 0.78rem;">
                                        <i class="fas fa-book me-1"></i>Toutes générales
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="btnToutesTechniques" style="border-radius: 20px; font-size: 0.78rem;">
                                        <i class="fas fa-tools me-1"></i>Toutes techniques
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAucuneType" style="border-radius: 20px; font-size: 0.78rem;">
                                        <i class="fas fa-times me-1"></i>Aucune
                                    </button>
                                </div>

                                <form id="formTypeEnseignement">
                                    @foreach($matieres as $matiere)
                                        <div style="display: flex; align-items: center; gap: 1rem; padding: 0.65rem 0.75rem; border-bottom: 1px solid #f3f4f6;">
                                            <div style="flex: 1; font-weight: 600; color: #1e293b; font-size: 0.85rem;">{{ $matiere->name }}</div>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <input type="radio" class="btn-check matiere-type-radio" name="matiere_type_{{ $matiere->id }}" id="general_{{ $matiere->id }}" value="general" data-matiere-id="{{ $matiere->id }}">
                                                <label class="btn btn-outline-primary" for="general_{{ $matiere->id }}" style="font-size: 0.75rem; border-radius: 6px 0 0 6px;">Général</label>

                                                <input type="radio" class="btn-check matiere-type-radio" name="matiere_type_{{ $matiere->id }}" id="technique_{{ $matiere->id }}" value="technique" data-matiere-id="{{ $matiere->id }}">
                                                <label class="btn btn-outline-success" for="technique_{{ $matiere->id }}" style="font-size: 0.75rem;">Technique</label>

                                                <input type="radio" class="btn-check matiere-type-radio" name="matiere_type_{{ $matiere->id }}" id="none_{{ $matiere->id }}" value="none" data-matiere-id="{{ $matiere->id }}" checked>
                                                <label class="btn btn-outline-secondary" for="none_{{ $matiere->id }}" style="font-size: 0.75rem; border-radius: 0 6px 6px 0;">Exclure</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </form>

                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; margin-top: 1rem;">
                                    <div style="background: #dbeafe; border-radius: 10px; padding: 0.75rem; text-align: center;">
                                        <div style="font-size: 1.25rem; font-weight: 800; color: #0453cb;" id="generalCount">0</div>
                                        <div style="font-size: 0.72rem; color: #6b7280;">Générales</div>
                                    </div>
                                    <div style="background: #d1fae5; border-radius: 10px; padding: 0.75rem; text-align: center;">
                                        <div style="font-size: 1.25rem; font-weight: 800; color: #065f46;" id="techniqueCount">0</div>
                                        <div style="font-size: 0.72rem; color: #6b7280;">Techniques</div>
                                    </div>
                                    <div style="background: #f1f5f9; border-radius: 10px; padding: 0.75rem; text-align: center;">
                                        <div style="font-size: 1.25rem; font-weight: 800; color: #64748b;" id="excludedCount">{{ count($matieres) }}</div>
                                        <div style="font-size: 0.72rem; color: #6b7280;">Exclues</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f3f4f6; padding: 1rem 1.5rem;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Annuler</button>
                <button type="button" class="btn" onclick="saveMatieres()" style="border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #64748b, #475569); color: white; border: none;">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
