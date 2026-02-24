<div class="coeff-modal-header">
    <div>
        <h5 class="mb-1"><i class="fas fa-sliders-h me-2"></i>Coefficients par combinaison</h5>
        <div class="text-muted">Année {{ $anneeUniversitaire->name ?? 'courante' }}</div>
    </div>
    <span class="badge bg-light text-dark">{{ $cards->count() }} combinaisons</span>
</div>

<div class="coeff-modal-intro">
    <div class="intro-icon">
        <i class="fas fa-lightbulb"></i>
    </div>
    <div>
        <div class="intro-title">Ces coefficients sont utilisés pour les bulletins</div>
        <div class="intro-text">Complétez chaque combinaison filière / niveau pour éviter les blocages lors des previews et PDFs.</div>
    </div>
</div>

@if($cards->isEmpty())
    <div class="alert alert-warning">Aucune combinaison filière/niveau trouvée.</div>
@else
    <div class="coeff-cards">
        @foreach($cards as $card)
            @php
                $statusClass = match($card['status']) {
                    'complete' => 'status-complete',
                    'partial' => 'status-partial',
                    'missing' => 'status-missing',
                    default => 'status-empty',
                };
                $statusLabel = match($card['status']) {
                    'complete' => 'Complet',
                    'partial' => 'Incomplet',
                    'missing' => 'Non configuré',
                    'empty' => 'Sans matières',
                    default => 'Inconnu',
                };
            @endphp
            <form class="coeff-card" data-filiere-id="{{ $card['filiere']->id }}" data-niveau-id="{{ $card['niveau']->id }}">
                <div class="coeff-card-header">
                    <div>
                        <div class="coeff-combo">
                            <span class="badge bg-primary">{{ $card['filiere']->name }}</span>
                            <span class="badge bg-secondary">{{ $card['niveau']->name }}</span>
                        </div>
                        <div class="text-muted small">{{ $card['configured'] }}/{{ $card['total'] }} matières configurées</div>
                    </div>
                    <span class="coeff-status {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>
                <div class="coeff-card-body">
                    @if($card['total'] === 0)
                        <div class="coeff-alert coeff-alert-empty">
                            Aucune matière liée à cette combinaison. Ajoutez d'abord les matières dans la classe.
                        </div>
                    @else
                        @if(in_array($card['status'], ['missing', 'partial'], true))
                            <div class="coeff-alert coeff-alert-warning">
                                Complétez les coefficients manquants pour activer la génération des bulletins.
                            </div>
                        @endif
                        <div class="coeff-grid">
                            @foreach($card['matieres'] as $matiere)
                                <div class="coeff-row">
                                    <div>
                                        <div class="fw-semibold">{{ $matiere['name'] }}</div>
                                        <div class="text-muted small">{{ $matiere['code'] ?? '—' }}</div>
                                    </div>
                                    <input type="number"
                                           name="coefficients[{{ $matiere['id'] }}]"
                                           value="{{ $matiere['coefficient'] }}"
                                           step="0.1"
                                           min="0.1"
                                           placeholder="—"
                                           class="form-control coeff-input">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="coeff-card-footer">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        @endforeach
    </div>
@endif
