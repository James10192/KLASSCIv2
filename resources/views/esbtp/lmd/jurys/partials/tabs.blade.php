<div class="juy-tabs" role="tablist">
    <button :class="tab==='composition' ? 'juy-tab juy-tab--active' : 'juy-tab'" @click="tab='composition'">
        <i class="fas fa-users"></i> Composition <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.05rem .4rem;font-size:.7rem;" x-text="membres.length">{{ $jury->membres->count() }}</span>
    </button>
    <button :class="tab==='deliberation' ? 'juy-tab juy-tab--active' : 'juy-tab'" @click="tab='deliberation'">
        <i class="fas fa-scale-balanced"></i> Délibération <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.05rem .4rem;font-size:.7rem;" x-text="decisions.length">{{ $jury->decisions->count() }}</span>
    </button>
    <button :class="tab==='statistiques' ? 'juy-tab juy-tab--active' : 'juy-tab'" @click="tab='statistiques'">
        <i class="fas fa-chart-pie"></i> Statistiques
    </button>
    <button :class="tab==='pv' ? 'juy-tab juy-tab--active' : 'juy-tab'" @click="tab='pv'">
        <i class="fas fa-file-signature"></i> PV
    </button>
</div>
