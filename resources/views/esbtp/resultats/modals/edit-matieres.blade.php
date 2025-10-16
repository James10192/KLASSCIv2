<!-- Modal: Configuration des matières -->
<div class="modal fade" id="modalEditMatieres" tabindex="-1" aria-labelledby="modalEditMatieresLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #6c757d, #495057); color: white; border-radius: 12px 12px 0 0; padding: 1.5rem;">
                <div>
                    <h4 class="modal-title mb-1" id="modalEditMatieresLabel" style="font-weight: 600;">
                        <i class="fas fa-cog me-2"></i>Configuration des Matières
                    </h4>
                    <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">
                        Modifier les coefficients des matières pour cette classe
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention:</strong> La modification des coefficients affectera le calcul des moyennes générales pour cette classe.
                </div>

                <form id="formMatieres">
                    @foreach($matieres as $matiere)
                        <div class="matiere-card mb-3" style="
                            border: 2px solid #e5e7eb;
                            border-radius: 12px;
                            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                            transition: all 0.3s ease;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                        " onmouseover="this.style.borderColor='#6c757d'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.05)'">
                            <div class="card-body" style="padding: 1.5rem;">
                                <div class="row align-items-center">
                                    <div class="col-md-1 text-center">
                                        <div style="
                                            width: 50px;
                                            height: 50px;
                                            border-radius: 50%;
                                            background: linear-gradient(135deg, #6c757d, #495057);
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            color: white;
                                            font-weight: bold;
                                            font-size: 1.2rem;
                                            box-shadow: 0 3px 8px rgba(108, 117, 125, 0.3);
                                        ">
                                            <i class="fas fa-book"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <h5 class="mb-1 fw-bold" style="color: #2d3748; font-size: 1.1rem;">
                                            {{ $matiere->name }}
                                        </h5>
                                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                            <span class="badge" style="
                                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                                padding: 0.4rem 0.8rem;
                                                font-size: 0.8rem;
                                                font-weight: 500;
                                                border-radius: 6px;
                                            ">
                                                <i class="fas fa-hashtag me-1"></i>{{ $matiere->code }}
                                            </span>
                                            @if($matiere->description)
                                                <span class="text-muted" style="font-size: 0.85rem; font-style: italic;">
                                                    {{ Str::limit($matiere->description, 40) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="
                                            background: white;
                                            border-radius: 10px;
                                            padding: 1rem;
                                            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
                                        ">
                                            <label class="form-label mb-2 fw-semibold" style="
                                                color: #6c757d;
                                                font-size: 0.85rem;
                                                text-transform: uppercase;
                                                letter-spacing: 0.5px;
                                            ">
                                                <i class="fas fa-balance-scale me-1"></i>Coefficient
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text" style="
                                                    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                                                    border: 2px solid #dee2e6;
                                                    border-right: none;
                                                ">
                                                    <i class="fas fa-arrow-up" style="color: #6c757d;"></i>
                                                </span>
                                                <input type="number" class="form-control" min="0" step="0.5"
                                                       name="coeff_{{ $matiere->id }}"
                                                       data-matiere-id="{{ $matiere->id }}"
                                                       value="{{ $matiere->pivot->coefficient ?? 1 }}"
                                                       placeholder="1.0"
                                                       style="
                                                           border: 2px solid #dee2e6;
                                                           border-left: none;
                                                           font-weight: 600;
                                                           font-size: 1.1rem;
                                                           text-align: center;
                                                           color: #495057;
                                                       ">
                                            </div>
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
                            <a href="{{ route('esbtp.classes.matieres', $classe->id) }}" class="alert-link">Configurer les matières</a>
                        </div>
                    @endif
                </form>

                @if($matieres->isNotEmpty())
                    <div class="mt-4">
                        <div style="
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            border-radius: 12px;
                            padding: 1.5rem;
                            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                            color: white;
                        ">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div style="
                                        display: flex;
                                        align-items: center;
                                        gap: 0.5rem;
                                        margin-bottom: 0.5rem;
                                        opacity: 0.95;
                                    ">
                                        <i class="fas fa-calculator" style="font-size: 1.2rem;"></i>
                                        <h6 class="mb-0" style="font-weight: 500; letter-spacing: 0.5px; text-transform: uppercase; font-size: 0.9rem;">
                                            Somme des coefficients
                                        </h6>
                                    </div>
                                    <p class="mb-0" style="font-size: 3rem; font-weight: 700; line-height: 1; text-shadow: 0 2px 8px rgba(0,0,0,0.2);" id="totalCoefficient">
                                        {{ $matieres->sum(function($m) { return $m->pivot->coefficient ?? 1; }) }}
                                    </p>
                                </div>
                                <div style="
                                    width: 80px;
                                    height: 80px;
                                    border-radius: 50%;
                                    background: rgba(255, 255, 255, 0.2);
                                    backdrop-filter: blur(10px);
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    border: 3px solid rgba(255, 255, 255, 0.3);
                                ">
                                    <i class="fas fa-equals" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                            <div class="mt-3" style="
                                background: rgba(255, 255, 255, 0.15);
                                backdrop-filter: blur(10px);
                                padding: 0.75rem;
                                border-radius: 8px;
                                font-size: 0.85rem;
                                border: 1px solid rgba(255, 255, 255, 0.2);
                            ">
                                <i class="fas fa-info-circle me-2"></i>
                                Cette valeur est calculée automatiquement en temps réel
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer" style="padding: 1rem 1.5rem; background-color: #f8f9fa; border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-dark" onclick="saveMatieres()">
                    <i class="fas fa-save me-2"></i>Enregistrer la configuration
                </button>
            </div>
        </div>
    </div>
</div>

