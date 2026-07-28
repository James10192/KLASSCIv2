@php
    $journeyPayload = $academicJourney ?? ['items' => collect(), 'summary' => []];
    $journeyItems = collect($journeyPayload['items'] ?? []);
    $journeySummary = $journeyPayload['summary'] ?? [];
    $compact = (bool) ($compact ?? false);
    $duplicateYears = collect($journeySummary['duplicate_years'] ?? []);
@endphp

@if($journeyItems->isNotEmpty())
    <section class="s-card student-journey-card {{ $compact ? 'student-journey-card-compact' : '' }}" aria-labelledby="student-journey-title-{{ $compact ? 'compact' : 'full' }}">
        <div class="s-card-header student-journey-header">
            <div class="s-card-title" id="student-journey-title-{{ $compact ? 'compact' : 'full' }}">
                <div class="s-card-title-icon"><i class="fas fa-route"></i></div>
                Parcours académique
            </div>
            <div class="student-journey-summary">
                <span class="student-journey-summary-pill">{{ $journeySummary['total'] ?? $journeyItems->count() }} inscription{{ ($journeySummary['total'] ?? $journeyItems->count()) > 1 ? 's' : '' }}</span>
                @if(!empty($journeySummary['has_mixed_path']))
                    <span class="student-journey-summary-pill bridge">BTS + LMD</span>
                @elseif(!empty($journeySummary['has_lmd']))
                    <span class="student-journey-summary-pill lmd">LMD</span>
                @else
                    <span class="student-journey-summary-pill bts">BTS</span>
                @endif
                @if($duplicateYears->isNotEmpty())
                    <span class="student-journey-summary-pill warning">Année en doublon</span>
                @endif
            </div>
        </div>

        @if($duplicateYears->isNotEmpty() && !$compact)
            <div class="student-journey-alert">
                <i class="fas fa-layer-group"></i>
                <span>
                    Plusieurs inscriptions existent sur {{ $duplicateYears->pluck('annee')->implode(', ') }}.
                    Le diagramme les conserve côte à côte pour éviter de masquer un doublon.
                </span>
            </div>
        @endif

        <div class="student-journey-timeline" role="list" aria-label="Chronologie des inscriptions de l'étudiant">
            @foreach($journeyItems as $journeyItem)
                @php
                    $metrics = $journeyItem['metrics'] ?? [];
                    $transition = $journeyItem['transition'] ?? ['label' => null, 'icon' => 'fa-arrow-right'];
                    $lmdPath = array_filter($journeyItem['lmd_path'] ?? []);
                    $isLmd = ($journeyItem['system'] ?? 'BTS') === 'LMD';
                    $isLast = $loop->last;
                @endphp
                <article class="student-journey-item {{ $journeyItem['tone'] ?? 'bts' }} {{ $isLast ? 'current' : '' }}" role="listitem">
                    <div class="student-journey-node" aria-hidden="true">
                        <i class="fas {{ $isLmd ? 'fa-university' : 'fa-graduation-cap' }}"></i>
                    </div>
                    <div class="student-journey-body">
                        <div class="student-journey-topline">
                            <span class="student-journey-year">{{ $journeyItem['annee'] }}</span>
                            <span class="student-journey-system {{ $isLmd ? 'lmd' : 'bts' }}">{{ $journeyItem['system_label'] }}</span>
                            @if(!$loop->first && !empty($transition['label']))
                                <span class="student-journey-transition {{ $transition['type'] ?? 'progression' }}">
                                    <i class="fas {{ $transition['icon'] ?? 'fa-arrow-right' }}"></i>
                                    {{ $transition['label'] }}
                                </span>
                            @endif
                        </div>

                        <div class="student-journey-main">
                            <div>
                                <h4>{{ $journeyItem['classe'] ?? 'Classe non renseignée' }}</h4>
                                <p>
                                    {{ $journeyItem['niveau'] ?? 'Niveau non renseigné' }}
                                    @if(!empty($journeyItem['filiere']))
                                        · {{ $journeyItem['filiere'] }}
                                    @endif
                                </p>
                                @if($isLmd && !empty($lmdPath))
                                    <p class="student-journey-lmd-path">{{ implode(' · ', $lmdPath) }}</p>
                                @endif
                            </div>

                            @if(!$compact)
                                <div class="student-journey-metrics">
                                    @if(!empty($metrics['moyenne_label']))
                                        <span><strong>{{ $metrics['moyenne_label'] }}</strong>Moyenne</span>
                                    @endif
                                    @if(!empty($metrics['credits_label']))
                                        <span><strong>{{ $metrics['credits_label'] }}</strong>Crédits</span>
                                    @endif
                                    @if(!empty($metrics['rang_label']))
                                        <span><strong>{{ $metrics['rang_label'] }}</strong>Rang</span>
                                    @endif
                                    @if(empty($metrics['moyenne_label']) && empty($metrics['credits_label']) && empty($metrics['rang_label']))
                                        <span><strong>En attente</strong>{{ $metrics['source'] ?? 'Aucune donnée' }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="student-journey-footer">
                            <span>{{ $journeyItem['status'] }}</span>
                            @if(!empty($journeyItem['workflow']))
                                <span>{{ $journeyItem['workflow'] }}</span>
                            @endif
                            @if(!empty($journeyItem['affectation']))
                                <span>{{ $journeyItem['affectation'] }}</span>
                            @endif
                            @if(!empty($journeyItem['is_sous_reserve']))
                                <span class="reserve">Sous réserve</span>
                            @endif
                            @if(!empty($journeyItem['date_inscription']))
                                <span>Inscrit le {{ $journeyItem['date_inscription'] }}</span>
                            @endif
                            @if(!$compact && !empty($metrics['source']))
                                <span>{{ $metrics['source'] }}</span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
