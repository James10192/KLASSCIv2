<div class="juy-hero">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-start;">
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
        <a href="{{ route('esbtp.lmd.jurys.index', ['annee_universitaire_id' => $jury->annee_universitaire_id]) }}" style="padding:.5rem .9rem;border-radius:9px;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.2);text-decoration:none;font-weight:600;font-size:.82rem;">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="juy-meta">
        <div class="juy-meta-item"><div class="juy-meta-label">Étudiants</div><div class="juy-meta-value" x-text="stats.total">{{ $stats['total'] }}</div></div>
        <div class="juy-meta-item"><div class="juy-meta-label">Admis</div><div class="juy-meta-value" x-text="stats.admis">{{ $stats['admis'] }}</div></div>
        <div class="juy-meta-item"><div class="juy-meta-label">Rattrapage</div><div class="juy-meta-value" x-text="stats.admission_rattrapage">{{ $stats['admission_rattrapage'] }}</div></div>
        <div class="juy-meta-item"><div class="juy-meta-label">Ajournés</div><div class="juy-meta-value" x-text="stats.ajourne">{{ $stats['ajourne'] }}</div></div>
        <div class="juy-meta-item"><div class="juy-meta-label">Overrides</div><div class="juy-meta-value" x-text="stats.overrides">{{ $stats['overrides'] }}</div></div>
    </div>
</div>
