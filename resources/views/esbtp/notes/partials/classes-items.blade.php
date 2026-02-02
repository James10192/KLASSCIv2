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
        $className = strtolower($classe->name ?: '');
        $classFiliere = strtolower(optional($classe->filiere)->name ?: '');
        $classNiveau = strtolower(optional($classe->niveau)->name ?: '');
    @endphp
    <div class="card-moderne resultat-card class-card animate-slide-up @if($classe->is_active) border-active @else border-inactive @endif"
         data-classe-id="{{ $classe->id }}"
         data-class-name="{{ $className }}"
         data-class-filiere="{{ $classFiliere }}"
         data-class-niveau="{{ $classNiveau }}"
         data-class-label="{{ $classe->name }}">
        <div class="class-card-header">
            <div class="classe-icon @if($classe->is_active) bg-success @else bg-inactive @endif">
                <i class="fas fa-graduation-cap" style="color: white; font-size: 16px;"></i>
            </div>
            <div class="class-header-text">
                <div class="class-title">{{ $classe->name }}</div>
                <div class="class-code">Code: {{ $classe->code }}</div>
            </div>
            <div class="class-badges">
                <span class="badge {{ $classe->is_active ? 'success' : 'danger' }}">
                    {{ $classe->is_active ? 'Active' : 'Inactive' }}
                </span>
                <span class="badge badge-notes ms-1">
                    <i class="fas fa-clipboard-check me-1"></i>Notes
                </span>
            </div>
        </div>

        <div class="class-meta">
            @if ($classe->filiere)
                <div class="meta-line">
                    <i class="fas fa-layer-group me-1"></i><strong>{{ $classe->filiere->name }}</strong>
                    @if ($classe->filiere->parent)
                        <span class="meta-sub">Option de {{ $classe->filiere->parent->name }}</span>
                    @endif
                </div>
            @endif
            @if ($classe->niveau)
                <div class="meta-line text-muted">
                    <i class="fas fa-level-up-alt me-1"></i>{{ $classe->niveau->name }}
                </div>
            @endif
        </div>

        <div class="notes-kpi-grid">
            <div class="notes-kpi-item">
                <div class="notes-kpi-label">Matières</div>
                <div class="notes-kpi-value">{{ $stats['matieres_total'] }}</div>
            </div>
            <div class="notes-kpi-item">
                <div class="notes-kpi-label">Moyennes configurées</div>
                <div class="notes-kpi-value">{{ $stats['matieres_configured'] }}</div>
            </div>
            <div class="notes-kpi-item">
                <div class="notes-kpi-label">Complétude</div>
                <div class="notes-kpi-value">{{ $stats['completion'] }}%</div>
            </div>
        </div>

        <div class="notes-averages">
            <span class="avg-chip">S1: {{ $stats['moyenne_s1'] !== null ? number_format($stats['moyenne_s1'], 2) : '--' }}</span>
            <span class="avg-chip">S2: {{ $stats['moyenne_s2'] !== null ? number_format($stats['moyenne_s2'], 2) : '--' }}</span>
            <span class="avg-chip highlight">Annuel: {{ $stats['moyenne_annuelle'] !== null ? number_format($stats['moyenne_annuelle'], 2) : '--' }}</span>
        </div>

        <div class="class-card-footer">
            <div class="notes-hint">
                @if ($classe->annee)
                    <i class="fas fa-calendar me-1"></i>{{ $classe->annee->name }}
                @endif
                <div class="notes-action-text">
                    <i class="fas fa-pen-alt me-1"></i>Saisir les notes
                </div>
            </div>
            <button type="button" class="btn-acasi primary class-select-btn" title="Saisir les notes">
                <i class="fas fa-edit"></i>
            </button>
        </div>
    </div>
@empty
    <div class="text-center" style="padding: var(--space-xl); color: var(--text-secondary); grid-column: 1 / -1;">
        <i class="fas fa-graduation-cap" style="font-size: 48px; margin-bottom: var(--space-lg); color: var(--neutral);"></i>
        <h5 style="color: var(--text-secondary); margin-bottom: var(--space-sm);">Aucune classe trouvée</h5>
        <p style="color: var(--text-muted);">Aucune classe ne correspond aux filtres sélectionnés.</p>
    </div>
@endforelse
