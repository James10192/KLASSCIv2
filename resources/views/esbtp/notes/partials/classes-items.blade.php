@forelse($classes as $classe)
    @php
        $stats = $classStatsById[$classe->id] ?? [
            'matieres_total' => 0,
            'matieres_configured' => 0,
            'completion' => 0,
            'moyenne_s1' => null,
            'moyenne_s2' => null,
            'moyenne_annuelle' => null,
        ];
        $completion = $stats['completion'];
        $progressClass = $completion >= 75 ? 'high' : ($completion >= 40 ? 'medium' : '');
    @endphp
    <div class="nm-class-card {{ $classe->is_active ? '' : 'inactive' }} class-card"
         data-classe-id="{{ $classe->id }}"
         data-class-name="{{ strtolower($classe->name ?: '') }}"
         data-class-filiere="{{ strtolower(optional($classe->filiere)->name ?: '') }}"
         data-class-niveau="{{ strtolower(optional($classe->niveau)->name ?: '') }}"
         data-class-label="{{ $classe->name }}">

        <div class="nm-card-header">
            <div class="nm-card-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="nm-card-title-group">
                <div class="nm-card-title">{{ $classe->name }}</div>
                <div class="nm-card-code">{{ $classe->code }}</div>
            </div>
            <div class="nm-card-badges">
                <span class="nm-badge {{ $classe->is_active ? 'success' : 'danger' }}">
                    {{ $classe->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>

        <div class="nm-card-meta">
            @if ($classe->filiere)
                <div class="nm-meta-line">
                    <i class="fas fa-layer-group"></i>
                    <strong>{{ $classe->filiere->name }}</strong>
                </div>
                @if ($classe->filiere->parent)
                    <div class="nm-meta-sub">Option de {{ $classe->filiere->parent->name }}</div>
                @endif
            @endif
            @if ($classe->niveau)
                <div class="nm-meta-line">
                    <i class="fas fa-level-up-alt"></i>
                    {{ $classe->niveau->name }}
                </div>
            @endif
        </div>

        <div class="nm-card-kpis">
            <div class="nm-card-kpi">
                <div class="nm-card-kpi-value">{{ $stats['matieres_total'] }}</div>
                <div class="nm-card-kpi-label">Matières</div>
            </div>
            <div class="nm-card-kpi">
                <div class="nm-card-kpi-value">{{ $stats['matieres_configured'] }}</div>
                <div class="nm-card-kpi-label">Évaluées</div>
            </div>
            <div class="nm-card-kpi">
                <div class="nm-card-kpi-value">{{ $classe->inscriptions_count ?? 0 }}</div>
                <div class="nm-card-kpi-label">Effectif</div>
            </div>
        </div>

        <div class="nm-card-progress">
            <div class="nm-progress-bar">
                <div class="nm-progress-fill {{ $progressClass }}" style="width: {{ min($completion, 100) }}%"></div>
            </div>
            <div class="nm-progress-text">
                <span>Complétude</span>
                <span>{{ $completion }}%</span>
            </div>
        </div>

        <div class="nm-card-averages">
            <span class="nm-avg-chip">S1: {{ $stats['moyenne_s1'] !== null ? number_format($stats['moyenne_s1'], 2) : '--' }}</span>
            <span class="nm-avg-chip">S2: {{ $stats['moyenne_s2'] !== null ? number_format($stats['moyenne_s2'], 2) : '--' }}</span>
            <span class="nm-avg-chip annual">Annuel: {{ $stats['moyenne_annuelle'] !== null ? number_format($stats['moyenne_annuelle'], 2) : '--' }}</span>
        </div>

        <div class="nm-card-footer">
            <div class="nm-card-hint">
                @if ($classe->annee)
                    <i class="fas fa-calendar"></i>{{ $classe->annee->name }}
                @endif
            </div>
            <button type="button" class="nm-card-action class-select-btn" title="Saisir les notes">
                <i class="fas fa-edit"></i>
            </button>
        </div>
    </div>
@empty
    <div class="nm-empty">
        <div class="nm-empty-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <h5>Aucune classe trouvée</h5>
        <p>Aucune classe ne correspond aux filtres sélectionnés.</p>
    </div>
@endforelse
