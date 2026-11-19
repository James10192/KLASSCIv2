{{-- Un jury publié ou archivé n'a plus de pré-requis à remplir : le contrôle
     d'émission renvoyait alors un message interne en rouge sur un jury terminé. --}}
@if(in_array($jury->status, ['publie', 'archive'], true))
<div class="juy-readiness juy-readiness--ok">
    <i class="fas fa-circle-check" style="margin-top:.15rem;"></i>
    <div><strong>{{ $jury->libelleStatut() }}</strong> — les décisions sont définitives. Une correction passe par la rectification du procès-verbal.</div>
</div>
@else
<section class="juy-prep" aria-labelledby="juy-prep-titre">
    <header class="juy-prep__tete">
        <i :class="readiness.ok ? 'fas fa-circle-check juy-prep__ok' : 'fas fa-list-check'"></i>
        <div>
            <h2 id="juy-prep-titre" class="juy-prep__titre" x-text="readiness.ok ? 'Prêt pour le procès-verbal' : 'Avant le procès-verbal'"></h2>
            <p class="juy-prep__sous" x-show="!readiness.ok"
               x-text="(() => { const e = readiness.etapes || []; const n = e.filter(x => x.ok).length; return (n <= 1 ? n + ' étape terminée' : n + ' étapes terminées') + ' sur ' + e.length + '. « Générer PV » s’active quand tout est vert.'; })()"></p>
        </div>
    </header>

    <ol class="juy-prep__liste" x-show="(readiness.etapes || []).length">
        <template x-for="(etape, i) in (readiness.etapes || [])" :key="etape.cle">
            <li class="juy-prep__etape" :class="etape.ok ? 'juy-prep__etape--ok' : 'juy-prep__etape--ko'">
                <span class="juy-prep__num">
                    <i x-show="etape.ok" class="fas fa-check"></i>
                    <span x-show="!etape.ok" x-text="i + 1"></span>
                </span>
                <div class="juy-prep__corps">
                    <strong x-text="etape.titre"></strong>
                    <span class="juy-prep__aide" x-show="etape.ok || !etape.raisons.length" x-text="etape.ok ? 'Fait.' : etape.aide"></span>
                    <template x-for="raison in etape.raisons" :key="raison">
                        <span class="juy-prep__raison" x-text="raison"></span>
                    </template>
                </div>
                <button type="button" class="juy-prep__aller h-11"
                        x-show="!etape.ok && etape.onglet"
                        @click="tab = etape.onglet; $nextTick(() => document.querySelector('.juy-tabs')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
                    Corriger <i class="fas fa-arrow-right"></i>
                </button>
                @can('academic_pilotage.view')
                {{-- L'étape « notes » se règle hors de l'écran du jury : dans les fiches de notes. --}}
                <a href="{{ route('esbtp.pilotage-academique.fiches') }}" target="_blank" rel="noopener"
                   class="juy-prep__aller h-11" style="display:inline-flex;align-items:center;gap:.35rem;text-decoration:none;"
                   x-show="!etape.ok && etape.cle === 'notes'">
                    Ouvrir les fiches de notes <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
                @endcan
            </li>
        </template>
    </ol>

    {{-- Repli : un motif hors étape (jury introuvable, état inattendu). --}}
    <template x-if="!readiness.ok && !(readiness.etapes || []).length">
        <div class="juy-prep__raison" style="margin-top:.5rem;">
            <template x-for="reason in readiness.reasons" :key="reason"><div x-text="reason"></div></template>
        </div>
    </template>
</section>
@endif
