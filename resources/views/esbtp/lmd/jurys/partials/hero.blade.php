<div class="juy-hero">
    <div class="juy-hero-top">
        <div class="juy-hero-left">
            <div class="juy-hero-icon"><i class="fas fa-gavel"></i></div>
            <div>
                <h1>{{ $jury->libelle }}</h1>
                <p>
                    <span style="text-transform:uppercase;font-weight:700;font-size:.75rem;">{{ str_replace('_', ' ', $jury->status) }}</span>
                    @if($jury->parcours) · {{ $jury->parcours->name }} @endif
                    @if($jury->classe) · {{ $jury->classe->name }} @endif
                    @if($jury->semestre) · S{{ $jury->semestre }} @endif
                    @if($jury->date_jury) · {{ $jury->date_jury->format('d/m/Y') }} @endif
                </p>
            </div>
        </div>
        <a href="{{ route('esbtp.lmd.jurys.index', array_filter(['annee_universitaire_id' => $jury->annee_universitaire_id])) }}" class="juy-btn juy-btn--glass">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
    <div class="juy-kpis">
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-users"></i></div>
            <div><div class="juy-kpi-value" x-text="stats.total">{{ $stats['total'] }}</div><div class="juy-kpi-label">Étudiants</div></div></div>
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-check"></i></div>
            <div><div class="juy-kpi-value" x-text="stats.admis">{{ $stats['admis'] }}</div><div class="juy-kpi-label">Admis</div></div></div>
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-rotate"></i></div>
            <div><div class="juy-kpi-value" x-text="stats.admission_rattrapage">{{ $stats['admission_rattrapage'] }}</div><div class="juy-kpi-label">Rattrapage</div></div></div>
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-user-times"></i></div>
            <div><div class="juy-kpi-value" x-text="stats.ajourne">{{ $stats['ajourne'] }}</div><div class="juy-kpi-label">Ajournés</div></div></div>
        <div class="juy-kpi"><div class="juy-kpi-icon"><i class="fas fa-pen"></i></div>
            <div><div class="juy-kpi-value" x-text="stats.overrides">{{ $stats['overrides'] }}</div><div class="juy-kpi-label">Overrides</div></div></div>
    </div>
</div>
