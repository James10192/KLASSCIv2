{{-- Modal d'affectation rapide de classe --}}
<div class="modal fade" id="affectationClasseModal" tabindex="-1" aria-labelledby="affectationClasseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="affectationClasseModalLabel">
                    <i class="fas fa-user-plus"></i>
                    <span>Affecter à une classe</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                {{-- Contexte étudiant --}}
                <div class="d-flex align-items-center gap-3 mb-3 pb-3" style="border-bottom: 1px solid #f1f5f9;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #5e91de); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.9rem;">
                        {{ strtoupper(substr($inscription->etudiant->nom, 0, 1)) }}{{ strtoupper(substr($inscription->etudiant->prenoms, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #1e293b; font-size: 0.92rem;">
                            {{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}
                        </div>
                        <div style="font-size: 0.78rem; color: #64748b;">
                            {{ $inscription->filiere->name ?? 'N/A' }} &middot; {{ $inscription->niveau->name ?? 'N/A' }}
                        </div>
                    </div>
                </div>

                {{-- Sélection de classe --}}
                <div class="mb-3">
                    <label style="font-weight: 600; color: #1e293b; font-size: 0.85rem; margin-bottom: 8px; display: block;">
                        <i class="fas fa-users me-1" style="color: #0453cb;"></i> Classe
                    </label>

                    @if(isset($classesDisponibles) && $classesDisponibles->count() > 0)
                        <div id="classes-list" style="max-height: 240px; overflow-y: auto; padding-right: 4px;">
                            @foreach($classesDisponibles as $classe)
                                @php
                                    $places = $classe['places_disponibles'];
                                    $total = $classe['places_totales'];
                                    $ratio = $total > 0 ? ($places / $total) : 0;
                                    $colorClass = $ratio > 0.3 ? 'green' : ($ratio > 0 ? 'yellow' : 'red');
                                    $isDisabled = $places <= 0;
                                @endphp
                                <div class="classe-option-card {{ $isDisabled ? 'disabled' : '' }}"
                                     data-classe-id="{{ $classe['id'] }}"
                                     data-places="{{ $places }}"
                                     @if(!$isDisabled) onclick="selectClasse(this, {{ $classe['id'] }})" @endif>
                                    <div class="classe-option-radio"></div>
                                    <div class="classe-option-info">
                                        <div class="classe-option-name">{{ $classe['name'] }}</div>
                                    </div>
                                    <div class="classe-option-places">
                                        <span class="places-indicator {{ $colorClass }}">
                                            <i class="fas fa-{{ $colorClass === 'red' ? 'ban' : 'chair' }}"></i>
                                            {{ $places }} / {{ $total }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align: center; padding: 24px; background: #f8fafc; border-radius: 10px; border: 1px dashed #cbd5e1;">
                            <i class="fas fa-inbox" style="font-size: 1.5rem; color: #94a3b8; margin-bottom: 8px; display: block;"></i>
                            <div style="font-weight: 600; color: #64748b; font-size: 0.88rem;">Aucune classe disponible</div>
                            <div style="font-size: 0.78rem; color: #94a3b8; margin-top: 4px;">
                                Aucune classe active pour la filière {{ $inscription->filiere->name ?? '' }} / {{ $inscription->niveau->name ?? '' }}
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Statut d'affectation --}}
                <div class="mb-2">
                    <label style="font-weight: 600; color: #1e293b; font-size: 0.85rem; margin-bottom: 8px; display: block;">
                        <i class="fas fa-tag me-1" style="color: #0453cb;"></i> Statut d'affectation
                    </label>
                    <div class="affectation-status-group">
                        <div class="affectation-status-chip active" data-status="affecté" onclick="selectAffectationStatus(this, 'affecté')">
                            <i class="fas fa-check-circle"></i> Affecté
                        </div>
                        <div class="affectation-status-chip" data-status="réaffecté" onclick="selectAffectationStatus(this, 'réaffecté')">
                            <i class="fas fa-exchange-alt"></i> Réaffecté
                        </div>
                    </div>
                </div>

                {{-- Info frais --}}
                <div style="background: rgba(4,83,203,0.04); border: 1px solid rgba(4,83,203,0.1); border-radius: 8px; padding: 10px 14px; margin-top: 12px;">
                    <div class="d-flex align-items-center gap-2" style="font-size: 0.8rem; color: #0453cb;">
                        <i class="fas fa-info-circle"></i>
                        <span>Les frais de scolarité seront automatiquement recalculés selon le statut d'affectation choisi.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm" style="color: #64748b;" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-sm btn-affecter-classe btn-affecter-submit" id="btn-confirmer-affectation"
                        onclick="confirmerAffectation()" disabled>
                    <i class="fas fa-check"></i>
                    <span>Confirmer l'affectation</span>
                </button>
            </div>
        </div>
    </div>
</div>
