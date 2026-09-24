{{--
    Fiabilité des données + ancienneté des impayés.
    Chargé à part (AJAX) : le calcul lit tous les étudiants actifs et ne doit
    pas retarder l'affichage de la page. Source : AnalyticsReliabilityService,
    la même que la commande analytics:diagnose.
--}}
<div class="anf" x-data="anFiabilite('{{ route('esbtp.comptabilite.analytics.fiabilite') }}')" x-init="charger()">

    {{-- Chargement --}}
    <div class="anf-card anf-card--loading" x-show="etat === 'chargement'">
        <div class="anf-skeleton anf-skeleton--title"></div>
        <div class="anf-skeleton"></div>
        <div class="anf-skeleton anf-skeleton--short"></div>
    </div>

    {{-- Erreur --}}
    <div class="anf-card anf-card--error" x-show="etat === 'erreur'" x-cloak>
        <i class="fas fa-exclamation-circle"></i>
        <span x-text="erreur"></span>
        <button type="button" class="anf-link" @click="charger()">Réessayer</button>
    </div>

    <template x-if="etat === 'pret'">
        <div class="anf-grid">
            {{-- Fiabilité --}}
            <div class="anf-card" :class="'anf-card--' + d.fiabilite.niveau">
                <div class="anf-head">
                    <span class="anf-dot"></span>
                    <div>
                        <div class="anf-kicker">Fiabilité des données</div>
                        <div class="anf-title" x-text="libelleNiveau()"></div>
                    </div>
                </div>
                <template x-if="d.fiabilite.constats.length === 0">
                    <p class="anf-text">Les paiements sont saisis régulièrement : les prévisions de cette page reposent sur des données à jour.</p>
                </template>
                <ul class="anf-list">
                    <template x-for="c in d.fiabilite.constats" :key="c.code">
                        <li>
                            <strong x-text="c.titre"></strong>
                            <span x-text="c.detail"></span>
                        </li>
                    </template>
                </ul>
                <div class="anf-meta">
                    <span x-text="nombre(d.fiabilite.mesures.paiements_analyses) + ' paiements saisis sur ' + d.fiabilite.periode_analysee.mois + ' mois'"></span>
                    <span x-show="d.fiabilite.mesures.derniere_saisie" x-text="'Dernière saisie : ' + dateFr(d.fiabilite.mesures.derniere_saisie)"></span>
                </div>
            </div>

            {{-- Ancienneté des impayés --}}
            <div class="anf-card">
                <div class="anf-head">
                    <i class="fas fa-hourglass-half anf-icon"></i>
                    <div>
                        <div class="anf-kicker">Ancienneté des impayés</div>
                        <div class="anf-title" x-text="fcfa(d.anciennete.total_en_retard) + ' en retard'"></div>
                    </div>
                </div>
                <template x-if="d.anciennete.etudiants === 0">
                    <p class="anf-text">Aucun étudiant actif sur ce périmètre.</p>
                </template>
                <template x-if="d.anciennete.etudiants > 0">
                    <div>
                        <div class="anf-stack" role="img" :aria-label="'Répartition du retard par ancienneté'">
                            <template x-for="t in trancheEnRetard()" :key="t.tranche">
                                <span :class="'anf-seg anf-seg--' + t.tranche" :style="'width:' + part(t) + '%'" :title="t.label + ' : ' + fcfa(t.montant_en_retard)"></span>
                            </template>
                        </div>
                        <table class="anf-table">
                            <template x-for="t in d.anciennete.tranches" :key="t.tranche">
                                <tr>
                                    <td><i :class="'anf-swatch anf-seg--' + t.tranche"></i><span x-text="t.label"></span></td>
                                    <td class="anf-num" x-text="nombre(t.etudiants) + ' étud.'"></td>
                                    <td class="anf-num anf-strong" x-text="t.tranche === 'a_jour' ? '—' : fcfa(t.montant_en_retard)"></td>
                                </tr>
                            </template>
                        </table>
                        <p class="anf-warn" x-show="d.anciennete.mode_degrade" x-text="d.anciennete.avertissement"></p>
                        <p class="anf-foot" x-text="'Reste à payer au total : ' + fcfa(d.anciennete.total_restant_du) + ' · ' + nombre(d.anciennete.etudiants) + ' étudiants actifs'"></p>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>

<style>
.anf { margin: 1rem 0 0; }
.anf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.anf-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.1rem 1.25rem; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.anf-card--fiable { border-left: 4px solid #10b981; }
.anf-card--a_verifier { border-left: 4px solid #f59e0b; }
.anf-card--insuffisant { border-left: 4px solid #dc2626; }
.anf-card--error { display: flex; align-items: center; gap: .6rem; color: #b91c1c; }
.anf-head { display: flex; align-items: center; gap: .75rem; margin-bottom: .6rem; }
.anf-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; background: #94a3b8; }
.anf-card--fiable .anf-dot { background: #10b981; }
.anf-card--a_verifier .anf-dot { background: #f59e0b; }
.anf-card--insuffisant .anf-dot { background: #dc2626; }
.anf-icon { color: #0453cb; font-size: 1.1rem; width: 12px; text-align: center; }
.anf-kicker { font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
.anf-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; }
.anf-text { font-size: .86rem; color: #475569; margin: 0; }
.anf-list { list-style: none; padding: 0; margin: 0; }
.anf-list li { padding: .5rem 0; border-top: 1px solid #f1f5f9; font-size: .84rem; color: #475569; }
.anf-list li strong { display: block; color: #0f172a; font-size: .88rem; margin-bottom: .15rem; }
.anf-meta { display: flex; flex-wrap: wrap; gap: .4rem 1.2rem; margin-top: .6rem; font-size: .75rem; color: #64748b; }
.anf-stack { display: flex; height: 12px; border-radius: 6px; overflow: hidden; background: #f1f5f9; margin: .3rem 0 .8rem; }
.anf-seg { display: block; height: 100%; }
.anf-seg--a_jour { background: #cbd5e1; }
.anf-seg--1_30 { background: #93b4ea; }
.anf-seg--31_60 { background: #5e91de; }
.anf-seg--61_90 { background: #0453cb; }
.anf-seg--plus_90 { background: #033a8e; }
.anf-swatch { display: inline-block; width: 9px; height: 9px; border-radius: 2px; margin-right: .45rem; vertical-align: 0; }
.anf-table { width: 100%; font-size: .82rem; border-collapse: collapse; }
.anf-table td { padding: .32rem 0; border-top: 1px solid #f1f5f9; color: #334155; }
.anf-num { text-align: right; font-variant-numeric: tabular-nums; padding-left: .8rem !important; white-space: nowrap; }
.anf-strong { font-weight: 700; color: #0f172a !important; }
.anf-warn { margin: .7rem 0 0; font-size: .78rem; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: .5rem .7rem; }
.anf-foot { margin: .6rem 0 0; font-size: .75rem; color: #64748b; }
.anf-link { background: none; border: none; color: #0453cb; font-weight: 600; cursor: pointer; margin-left: auto; }
.anf-skeleton { height: 12px; border-radius: 6px; background: linear-gradient(90deg, #f1f5f9, #e2e8f0, #f1f5f9); background-size: 200% 100%; animation: anf-shine 1.2s linear infinite; margin: .55rem 0; }
.anf-skeleton--title { width: 40%; height: 16px; }
.anf-skeleton--short { width: 60%; }
@keyframes anf-shine { to { background-position: -200% 0; } }
@media (max-width: 992px) { .anf-grid { grid-template-columns: 1fr; } }
</style>

<script>
if (typeof window.anFiabilite !== 'function') {
    window.anFiabilite = function (url) {
        return {
            etat: 'chargement',
            erreur: '',
            d: null,

            async charger() {
                this.etat = 'chargement';
                try {
                    const res = await fetch(url + window.location.search, { headers: { 'Accept': 'application/json' } });
                    const body = await res.json().catch(() => ({}));
                    if (!res.ok || !body.success) {
                        throw new Error(body.message || 'La fiabilité des données n\'a pas pu être chargée.');
                    }
                    this.d = body.data;
                    this.etat = 'pret';
                } catch (e) {
                    this.erreur = e.message;
                    this.etat = 'erreur';
                }
            },
            libelleNiveau() {
                return { fiable: 'Données fiables', a_verifier: 'À vérifier avant de lire les prévisions', insuffisant: 'Données insuffisantes pour prévoir' }[this.d.fiabilite.niveau] || '';
            },
            trancheEnRetard() {
                return this.d.anciennete.tranches.filter(t => t.tranche !== 'a_jour' && t.montant_en_retard > 0);
            },
            part(t) {
                const total = this.d.anciennete.total_en_retard;
                return total > 0 ? (100 * t.montant_en_retard / total).toFixed(2) : 0;
            },
            nombre(v) { return new Intl.NumberFormat('fr-FR').format(v || 0); },
            fcfa(v) { return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v || 0) + ' FCFA'; },
            dateFr(iso) { return new Date(iso + 'T00:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }); },
        };
    };
}
</script>
