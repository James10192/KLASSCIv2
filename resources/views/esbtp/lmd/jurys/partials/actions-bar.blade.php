<div class="juy-actions-bar">
    @can('lmd.jury.deliberate')
    @if(!$jury->isLocked())
    <button type="button" class="juy-btn juy-btn--primary h-11" @click="requestAutoDecisions()" :disabled="busy">
        <i class="fas fa-bolt"></i> <span x-text="busy ? 'Calcul…' : 'Appliquer décisions auto'"></span>
    </button>
    @endif
    @endcan

    @can('lmd.jury.publish')
    @if(!$jury->pv_genere_at)
    <button type="button" class="juy-btn juy-btn--success h-11" @click="requestPv()" :disabled="busy || !readiness.ok">
        <i class="fas fa-file-signature"></i> <span x-text="busy ? 'Génération…' : 'Générer PV'"></span>
    </button>
    @elseif($officialDocument)
    <a href="{{ route('esbtp.lmd.jurys.pv-preview', $jury) }}" target="_blank" class="juy-btn juy-btn--secondary h-11">
        <i class="fas fa-file-pdf"></i> Aperçu PV
    </a>
    <a href="{{ route('esbtp.lmd.jurys.pv-download', $jury) }}" class="juy-btn juy-btn--secondary h-11">
        <i class="fas fa-download"></i> Télécharger PV
    </a>
    @if($jury->status !== 'publie')
    <button type="button" class="juy-btn juy-btn--success h-11" @click="requestPublication()" :disabled="busy || !readiness.ok">
        <i class="fas fa-flag-checkered"></i> <span x-text="busy ? 'Publication…' : 'Publier les décisions'"></span>
    </button>
    @endif
    @endif
    @endcan

    @can('lmd.pv.export')
    <a href="{{ route('esbtp.lmd.jurys.pv-annuel.pdf', $jury) }}?inline=1" target="_blank" rel="noopener" class="juy-btn juy-btn--secondary h-11">
        <i class="fas fa-eye"></i> Aperçu PV annuel
    </a>
    <a href="{{ route('esbtp.lmd.jurys.pv-annuel.pdf', $jury) }}" class="juy-btn juy-btn--secondary h-11">
        <i class="fas fa-file-pdf"></i> PV annuel PDF
    </a>
    <a href="{{ route('esbtp.lmd.jurys.pv-annuel.excel', $jury) }}" class="juy-btn juy-btn--secondary h-11">
        <i class="fas fa-file-excel"></i> PV annuel Excel
    </a>
    @endcan

    @if($jury->pv_numero)
    <span class="pv-numero"><i class="fas fa-stamp"></i> {{ $jury->pv_numero }}</span>
    @endif
</div>
