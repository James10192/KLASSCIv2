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
                        <div class="card mb-3" style="border: 1px solid #e5e7eb;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-1 fw-bold">{{ $matiere->name }}</h6>
                                        <small class="text-muted">
                                            Code: {{ $matiere->code }}
                                            @if($matiere->description)
                                                <br>{{ Str::limit($matiere->description, 50) }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <label class="form-label mb-0 text-muted" style="font-size: 0.875rem;">Coefficient:</label>
                                            </div>
                                            <div class="col">
                                                <input type="number" class="form-control" min="0" step="0.5"
                                                       name="coeff_{{ $matiere->id }}"
                                                       data-matiere-id="{{ $matiere->id }}"
                                                       value="{{ $matiere->pivot->coefficient ?? 1 }}"
                                                       placeholder="1.0">
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
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="mb-2"><i class="fas fa-calculator me-2"></i>Somme des coefficients</h6>
                                <p class="mb-0 fs-4 fw-bold text-primary" id="totalCoefficient">
                                    {{ $matieres->sum(function($m) { return $m->pivot->coefficient ?? 1; }) }}
                                </p>
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

