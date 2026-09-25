{{-- Un jury publié ou archivé n'a plus de pré-requis à remplir : le contrôle
     d'émission renvoyait alors un message interne en rouge sur un jury terminé. --}}
@if(in_array($jury->status, ['publie', 'archive'], true))
<div class="juy-readiness juy-readiness--ok">
    <i class="fas fa-circle-check" style="margin-top:.15rem;"></i>
    <div><strong>{{ $jury->libelleStatut() }}</strong> — les décisions sont définitives. Une correction passe par la rectification du procès-verbal.</div>
</div>
@else
<div :class="readiness.ok ? 'juy-readiness juy-readiness--ok' : 'juy-readiness juy-readiness--ko'">
    <i :class="readiness.ok ? 'fas fa-circle-check' : 'fas fa-triangle-exclamation'" style="margin-top:.15rem;"></i>
    <div>
        <strong x-text="readiness.ok ? 'Prêt pour le procès-verbal' : 'Pré-requis incomplets'"></strong>
        <template x-if="!readiness.ok">
            <div style="margin-top:.25rem;">
                <template x-for="reason in readiness.reasons" :key="reason">
                    <div>• <span x-text="reason"></span></div>
                </template>
            </div>
        </template>
    </div>
</div>
@endif
