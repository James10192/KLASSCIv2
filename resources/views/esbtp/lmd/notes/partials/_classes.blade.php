{{-- Cartes de classes LMD également rendues lors du changement d'année AJAX. --}}
    @if($classes->isEmpty())
        <div class="ln-empty-card">
            <div class="ln-empty">
                <div class="ln-empty-icon"><i class="fas fa-layer-group"></i></div>
                <div class="ln-empty-title">Aucune classe LMD</div>
                <div class="ln-empty-text">Aucune classe utilisant le système LMD n'a été trouvée. Configurez d'abord vos classes.</div>
            </div>
        </div>
    @else
        <div class="ln-section-header">
            <div class="ln-section-title">
                <i class="fas fa-th-large"></i>
                Classes LMD
            </div>
            <span class="ln-section-count">{{ $totalClasses }} classe{{ $totalClasses > 1 ? 's' : '' }}</span>
        </div>

        <div class="ln-cards">
            @foreach($classes as $classe)
                @php $nbEvals = $evalCounts[$classe->id] ?? 0; @endphp
                <div class="ln-card">
                    <div class="ln-card-head">
                        <div class="ln-card-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <div class="ln-card-title">{{ $classe->name }}</div>
                            <div class="ln-card-sub">
                                {{ $classe->filiere->name ?? '' }}
                                @if($classe->niveau) &middot; {{ $classe->niveau->name ?? '' }} @endif
                            </div>
                        </div>
                    </div>

                    <div class="ln-card-metrics">
                        <div class="ln-metric">
                            <span class="ln-metric-label">Étudiants</span>
                            <span class="ln-metric-value">{{ $classe->etudiants_count }}</span>
                        </div>
                        <div class="ln-metric">
                            <span class="ln-metric-label">Évaluations</span>
                            <span class="ln-metric-value">{{ $nbEvals }}</span>
                        </div>
                        <div class="ln-metric">
                            <span class="ln-metric-label">Année</span>
                            <span class="ln-metric-value" style="font-size:.82rem; color:#64748b;">{{ $anneeSelectionnee?->name ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="ln-card-foot">
                        <button type="button" class="ln-card-btn ln-card-btn--primary"
                                onclick="openNotesModal({{ $classe->id }}, @js($classe->name))">
                            <i class="fas fa-edit"></i>Gérer les notes
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
