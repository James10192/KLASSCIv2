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
