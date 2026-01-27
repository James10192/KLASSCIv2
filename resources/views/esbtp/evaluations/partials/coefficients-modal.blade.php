<div class="coeff-modal-header">
    <div>
        <h5 class="mb-1"><i class="fas fa-sliders-h me-2"></i>Coefficients par combinaison</h5>
        <div class="text-muted">Année {{ $anneeUniversitaire->name ?? 'courante' }}</div>
    </div>
    <span class="badge bg-light text-dark">{{ $cards->count() }} combinaisons</span>
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
                        {{ strtoupper($card['status']) }}
                    </span>
                </div>
                <div class="coeff-card-body">
                    @if($card['total'] === 0)
                        <div class="text-muted">Aucune matière liée à cette combinaison.</div>
                    @else
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
