{{-- Modal: Assignation groupée des professeurs --}}
<div class="modal fade" id="modalEditProfesseurs" tabindex="-1" aria-labelledby="modalEditProfesseursLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: var(--sr-radius, 16px); overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #06b6d4, #0891b2); color: white; border: none; padding: 1.25rem 1.5rem;">
                <div>
                    <h5 class="modal-title" id="modalEditProfesseursLabel" style="font-weight: 700; margin: 0 0 0.2rem;">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Assignation des Professeurs
                    </h5>
                    <p style="margin: 0; font-size: 0.82rem; opacity: 0.8;">Associez un enseignant à chaque matière</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem; max-height: 65vh; overflow-y: auto;">
                @if($matieres->count() > 0)
                    <div style="display: grid; gap: 0.75rem;">
                        @foreach($matieres as $matiere)
                            @php
                                $enseignantsMatiere = $enseignantsParMatiere[$matiere->id] ?? collect();
                                $currentProf = $professeursGroupes[$matiere->id] ?? null;
                            @endphp
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.85rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 12px; transition: border-color 0.2s;">
                                <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #cffafe, #a5f3fc); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-book" style="color: #0891b2; font-size: 0.85rem;"></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 700; color: #1e293b; font-size: 0.88rem;">{{ $matiere->name }}</div>
                                    <span style="font-size: 0.65rem; background: #dbeafe; color: #0453cb; padding: 0.1rem 0.4rem; border-radius: 4px;">Coeff. {{ $matiere->pivot->coefficient ?? 1 }}</span>
                                </div>
                                <div style="flex: 1; min-width: 180px;">
                                    <select class="form-select" name="professeur_{{ $matiere->id }}" data-matiere-id="{{ $matiere->id }}"
                                            style="border-radius: 8px; border: 1.5px solid #e5e7eb; font-size: 0.85rem;">
                                        <option value="">— Aucun —</option>
                                        @foreach($enseignantsMatiere as $enseignant)
                                            <option value="{{ $enseignant->id }}" {{ $currentProf == $enseignant->id ? 'selected' : '' }}>{{ $enseignant->name }}</option>
                                        @endforeach
                                        @if($enseignantsMatiere->isEmpty())
                                            @foreach($enseignants as $ens)
                                                <option value="{{ $ens->id }}" {{ $currentProf == $ens->id ? 'selected' : '' }}>{{ $ens->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align: center; padding: 2rem; color: #6b7280;">
                        <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 0.75rem; display: block;"></i>
                        <p>Aucune matière configurée pour cette classe.</p>
                    </div>
                @endif
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f3f4f6; padding: 1rem 1.5rem;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Annuler</button>
                <button type="button" class="btn" onclick="saveProfesseurs()" style="border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #06b6d4, #0891b2); color: white; border: none;">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
