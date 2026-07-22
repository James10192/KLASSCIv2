<div x-show="tab==='statistiques'" x-cloak>
    <div class="juy-card">
        <h2><i class="fas fa-chart-pie"></i> Répartition des décisions</h2>
        <div class="juy-stats-grid">
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.admis">{{ $stats['admis'] }}</div><div class="juy-stat-label">Admis</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.admission_rattrapage">{{ $stats['admission_rattrapage'] }}</div><div class="juy-stat-label">Rattrapage</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.ajourne">{{ $stats['ajourne'] }}</div><div class="juy-stat-label">Ajournés</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.exclu">{{ $stats['exclu'] }}</div><div class="juy-stat-label">Exclus</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.admis_sous_condition">{{ $stats['admis_sous_condition'] }}</div><div class="juy-stat-label">Sous condition</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.defere">{{ $stats['defere'] }}</div><div class="juy-stat-label">Différés</div></div>
        </div>
    </div>

    <div class="juy-card">
        <h2><i class="fas fa-award"></i> Mentions</h2>
        <div class="juy-stats-grid">
            <div class="juy-stat-card"><div class="juy-stat-value">{{ $stats['mentions']['excellent'] }}</div><div class="juy-stat-label">Excellent</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value">{{ $stats['mentions']['tres_bien'] }}</div><div class="juy-stat-label">Très Bien</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value">{{ $stats['mentions']['bien'] }}</div><div class="juy-stat-label">Bien</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value">{{ $stats['mentions']['assez_bien'] }}</div><div class="juy-stat-label">Assez Bien</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value">{{ $stats['mentions']['passable'] }}</div><div class="juy-stat-label">Passable</div></div>
        </div>
        @if($stats['moyenne_promo'])
        <div style="margin-top:1rem;padding:.75rem;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;color:#075985;font-size:.85rem;">
            <i class="fas fa-chart-line"></i> Moyenne promo : <strong>{{ number_format((float) $stats['moyenne_promo'], 2) }} / 20</strong>
        </div>
        @endif
    </div>
</div>
