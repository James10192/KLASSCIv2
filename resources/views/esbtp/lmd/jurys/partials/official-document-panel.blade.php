<div class="juy-card">
    <h2><i class="fas fa-file-shield"></i> Registre documentaire officiel</h2>

    <template x-if="officialDocument">
        <div>
            <table style="width:100%;border-collapse:collapse;font-size:.88rem;color:#1e293b;">
                <tbody>
                    <tr><td style="padding:.55rem;color:#64748b;">Numéro du PV</td><td style="padding:.55rem;font-weight:700;" x-text="officialDocument.pv_numero || juryPvPathExists && '{{ $jury->pv_numero }}' || 'Non défini'"></td></tr>
                    <tr><td style="padding:.55rem;color:#64748b;">Référence</td><td style="padding:.55rem;font-family:'Courier New',monospace;color:#0453cb;font-weight:700;" x-text="officialDocument.reference"></td></tr>
                    <tr><td style="padding:.55rem;color:#64748b;">Version et statut</td><td style="padding:.55rem;font-weight:700;">v<span x-text="officialDocument.version"></span> · <span x-text="officialDocument.status"></span></td></tr>
                    <tr><td style="padding:.55rem;color:#64748b;">Empreinte SHA-256</td><td style="padding:.55rem;"><code x-text="officialDocument.checksum_sha256 ? officialDocument.checksum_sha256.substring(0, 20) + '...' : 'Non disponible'"></code></td></tr>
                    <tr><td style="padding:.55rem;color:#64748b;">Émis le</td><td style="padding:.55rem;font-weight:700;" x-text="officialDocument.issued_at ? new Date(officialDocument.issued_at).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Non défini'"></td></tr>
                </tbody>
            </table>
            <div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap;">
                <a href="{{ route('esbtp.lmd.jurys.pv-preview', $jury) }}" target="_blank" x-show="officialDocument?.actions?.canPreview !== false" class="juy-btn juy-btn--secondary h-11">
                    <i class="fas fa-eye"></i> Aperçu sécurisé
                </a>
                <a href="{{ route('esbtp.lmd.jurys.pv-download', $jury) }}" x-show="officialDocument?.actions?.canDownload !== false" class="juy-btn juy-btn--primary h-11">
                    <i class="fas fa-download"></i> Télécharger le PDF archivé
                </a>
            </div>
        </div>
    </template>

    <template x-if="!officialDocument && juryPvPathExists">
        <div>
            <div style="padding:1rem;border:1px solid #fed7aa;background:#fff7ed;border-radius:10px;color:#9a3412;">
                Ce PV historique n'est pas encore inscrit dans le registre. La réconciliation conserve ses octets d'origine et ne génère aucun snapshot.
            </div>
            @can('lmd.jury.documents.reconcile')
                <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.5rem;">
                    <button type="button" class="juy-btn juy-btn--primary h-11" @click="reconcilePv()" :disabled="busy || reconciliationState === 'loading'">
                        <i class="fas fa-link"></i>
                        <span x-text="reconciliationState === 'loading' ? 'Réconciliation en cours...' : 'Réconcilier le PV historique'"></span>
                    </button>
                    <template x-if="reconciliationState === 'loading'">
                        <div style="padding:.65rem;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;border-radius:8px;font-size:.85rem;">Validation en cours, veuillez patienter.</div>
                    </template>
                    <template x-if="reconciliationState === 'success'">
                        <div style="padding:.65rem;border:1px solid #a7f3d0;background:#ecfdf5;color:#047857;border-radius:8px;font-size:.85rem;" x-text="reconciliationMessage"></div>
                    </template>
                    <template x-if="reconciliationState === 'error'">
                        <div style="padding:.65rem;border:1px solid #fecdd3;background:#fff1f2;color:#be123c;border-radius:8px;font-size:.85rem;" x-text="reconciliationMessage"></div>
                    </template>
                </div>
            @endcan
        </div>
    </template>

    <template x-if="!officialDocument && !juryPvPathExists">
        <div style="padding:2rem;text-align:center;color:#64748b;font-size:.88rem;">
            <i class="fas fa-hourglass" style="font-size:2rem;color:#cbd5e1;display:block;margin-bottom:.5rem;"></i>
            Le PV sera disponible après validation du quorum, des signatures, de la cohorte, des décisions et des feuilles de notes.
        </div>
    </template>
</div>
