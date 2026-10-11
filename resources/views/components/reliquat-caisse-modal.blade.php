@props(['inscriptionId' => null, 'initiallyVisible' => true])

@php
    $allowed = auth()->check() ? app(\App\Services\MobileMoneyPaymentGuard::class)->allowedModes(auth()->user()) : [];
    $modeOptions = collect(\App\Enums\ModePaiement::cases())
        ->filter(fn ($m) => $m !== \App\Enums\ModePaiement::AUTRE && in_array($m->value, $allowed, true))
        ->mapWithKeys(fn ($m) => [$m->value => $m->label()])->all();
    $peutEncaisser = !empty($modeOptions);
@endphp
@if($peutEncaisser)
<div class="rc-launcher" x-data="reliquatCaisseModal({{ (int) ($inscriptionId ?? 0) }})"
     @caisse-inscription-changed.window="setInscription($event.detail.inscriptionId)"
     @keydown.escape.window="if (open) fermer()">
    <button type="button" class="rc-open" @click="ouvrir()" :disabled="!inscriptionId"
        title="Régler une dette ancienne, distincte de la scolarité actuelle">
        <i class="fas fa-history"></i> Régler un arriéré
    </button>
    <span class="rc-hint" x-show="!inscriptionId" x-cloak>Sélectionnez d'abord une inscription</span>
    <template x-teleport="body">
        <div class="rc-overlay" x-show="open" x-cloak @click.self="fermer()">
            <section class="rc-dialog" role="dialog" aria-modal="true"
                aria-labelledby="rc-title" tabindex="-1" x-ref="dialog">
                <div class="rc-head">
                    <div class="rc-head-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="rc-heading">
                        <h2 id="rc-title">Encaisser une dette antérieure</h2>
                        <p>Choisir la créance exacte avant d'enregistrer le versement.</p>
                    </div>
                    <button type="button" class="rc-close" @click="fermer()" aria-label="Fermer"><i class="fas fa-times"></i></button>
                </div>
                <form class="rc-form" @submit.prevent="envoyer()" novalidate>
                    @csrf
                    <div class="rc-content">
                        <div class="rc-error" x-show="error" x-cloak role="alert" x-text="error"></div>
                        <div class="rc-success" x-show="success" x-cloak role="status">
                            <i class="fas fa-check-circle"></i> <span x-text="success"></span>
                            <span x-show="receipt" class="rc-receipt" x-text="receipt"></span>
                            <p>Ce versement reste en attente de validation et ne solde pas encore la dette.</p>
                        </div>
                        <p class="rc-loading" x-show="loading"><i class="fas fa-spinner fa-spin"></i> Lecture des dettes…</p>
                        <template x-if="!loading && lignes.length === 0">
                            <p class="rc-empty">Aucun reliquat reporté à régler pour cet étudiant. Les frais ordinaires restent dans l'encaissement habituel.</p>
                        </template>
                        <div x-show="!loading && lignes.length">
                            <div class="rc-section-title">1. Choisir la dette à rembourser</div>
                            <div class="rc-debts">
                                <template x-for="r in lignes" :key="r.id">
                                    <button type="button" class="rc-debt"
                                        :class="{ 'rc-debt--active': Number(reliquatId) === r.id, 'rc-debt--disabled': !r.payable }"
                                        :disabled="!r.payable || sending"
                                        @click="selectionner(r)">
                                        <span><strong x-text="r.frais"></strong>
                                            <small><span x-text="r.origine"></span> → <span x-text="r.destination"></span></small>
                                            <small x-show="r.anomalie" class="rc-danger">Vérification comptable requise</small>
                                        </span>
                                        <span class="rc-debt-money">
                                            <strong x-text="argent(r.disponible)"></strong>
                                            <small x-text="'Initial : ' + argent(r.initial) + ' · Payé : ' + argent(r.regle)"></small>
                                            <small x-show="r.attente > 0" x-text="'En attente : ' + argent(r.attente)"></small>
                                        </span>
                                    </button>
                                </template>
                            </div>
                            <div class="rc-section-title">2. Enregistrer le versement</div>
                            <div class="rc-fields">
                                <div class="rc-field" :class="{ 'rc-field--invalid': field === 'montant' }">
                                    <label for="rc-montant">Montant à encaisser (FCFA)</label>
                                    <input id="rc-montant" type="number" name="montant" min="1" step="0.01"
                                        x-model="montant" :max="selection?.disponible || null" required
                                        placeholder="Ex. 25 000" :disabled="!selection || sending || !!success">
                                    <small x-show="selection" x-text="'Disponible après réservations : ' + argent(selection?.disponible || 0)"></small>
                                </div>
                                <div class="rc-field" :class="{ 'rc-field--invalid': field === 'mode_paiement' }">
                                    <span class="rc-label">Mode de paiement</span>
                                    <x-au-select name="mode_paiement" label="Mode d'encaissement" icon="fa-wallet"
                                        :options="$modeOptions" placeholder="Choisir un mode" searchable />
                                </div>
                            </div>
                            <div class="rc-field rc-notes">
                                <label for="rc-notes">Observations <span>(facultatif)</span></label>
                                <textarea id="rc-notes" name="notes" rows="2" maxlength="1000"
                                    placeholder="Précision pour la caisse..." :disabled="sending || !!success"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="rc-foot">
                        <button type="button" class="rc-btn rc-btn--ghost" @click="fermer()">Fermer</button>
                        <button type="button" class="rc-btn rc-btn--outline"
                            :disabled="loading || sending" @click="charger()">Actualiser les montants</button>
                        <button type="submit" class="rc-btn rc-btn--primary"
                            :disabled="loading || sending || !selection || !!success">
                            <i class="fas fa-check"></i>
                            <span x-text="sending ? 'Enregistrement…' : 'Enregistrer le versement'"></span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </template>
</div>
@endif

@once
@push('styles')
<style>
.rc-launcher{display:inline-flex;align-items:center;gap:.6rem;flex-wrap:wrap}
.rc-open{background:#0453cb;color:#fff;border:0;border-radius:10px;padding:.65rem .95rem;cursor:pointer;font-weight:700;font-size:.8rem}
.rc-open:hover{background:#033a8e}.rc-open:disabled{opacity:.48;cursor:not-allowed}.rc-hint{color:#64748b;font-size:.75rem}
.rc-overlay{position:fixed;inset:0;z-index:1095;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.55)}
.rc-dialog{width:min(800px,100%);max-height:94dvh;display:flex;flex-direction:column;border-radius:16px;background:#fff;box-shadow:0 20px 60px #0f172a44;color:#1e293b}
.rc-head{display:flex;align-items:center;gap:.9rem;padding:1.25rem 1.4rem;border-bottom:1px solid #e2e8f0}.rc-head-icon{background:#eff6ff;color:#0453cb;width:42px;height:42px;display:grid;place-items:center;border-radius:11px}
.rc-heading{flex:1}.rc-heading h2{font-size:1.08rem;font-weight:800;color:#0f172a;margin:0}.rc-heading p{font-size:.78rem;color:#64748b;margin:.3rem 0 0}.rc-close{border:0;background:#f1f5f9;border-radius:8px;width:36px;height:36px;color:#475569;cursor:pointer}
.rc-form{display:flex;flex-direction:column;min-height:0}.rc-content{padding:1.25rem 1.4rem;overflow-y:auto}.rc-section-title{font-size:.85rem;font-weight:800;margin:.45rem 0 .85rem;color:#0f172a}
.rc-debts{display:grid;gap:.5rem;margin-bottom:1.2rem}.rc-debt{width:100%;display:flex;justify-content:space-between;gap:1rem;text-align:left;padding:.85rem;border:1px solid #dbe4f0;background:#fff;border-radius:11px;cursor:pointer}.rc-debt:hover,.rc-debt--active{border-color:#0453cb;background:#eff6ff}.rc-debt--disabled{opacity:.55;cursor:default}
.rc-debt span,.rc-debt-money{display:flex;flex-direction:column;gap:.2rem}.rc-debt strong{font-size:.82rem}.rc-debt small{font-size:.7rem;color:#64748b}.rc-debt-money{text-align:right;align-items:flex-end}.rc-danger{color:#b91c1c!important}
.rc-fields{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}.rc-field{display:flex;min-width:0;flex-direction:column;gap:.3rem}.rc-field label,.rc-label{font-weight:700;font-size:.77rem;color:#475569}
.rc-field input,.rc-field textarea{border:1px solid #cbd5e1;border-radius:9px;padding:.6rem .75rem;min-height:44px;max-width:100%;color:#1e293b}.rc-field .au-select{width:100%;display:flex}.rc-field .au-select-trigger{width:100%;min-height:44px}
.rc-field--invalid input,.rc-field--invalid .au-select-trigger{border:2px solid #dc2626!important;background:#fff7f7}.rc-field small,.rc-field label span{font-size:.69rem;color:#64748b}.rc-notes{margin-top:.9rem}
.rc-error,.rc-success{padding:.7rem .8rem;border-radius:9px;margin-bottom:1rem;font-size:.81rem}.rc-error{background:#fff1f2;color:#9f1239;border:1px solid #fecdd3}.rc-success{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}.rc-success p{margin:.35rem 0 0;font-size:.73rem}.rc-receipt{font-weight:700}
.rc-loading,.rc-empty{padding:1rem;color:#64748b;text-align:center;font-size:.82rem}
.rc-foot{display:flex;justify-content:flex-end;gap:.5rem;flex-wrap:wrap;border-top:1px solid #e2e8f0;padding:1rem 1.4rem}.rc-btn{border:1px solid transparent;border-radius:9px;min-height:40px;padding:.6rem .85rem;font-weight:700;cursor:pointer;font-size:.77rem}.rc-btn:disabled{opacity:.5;cursor:default}.rc-btn--ghost{background:#f8fafc;color:#475569}.rc-btn--outline{background:white;border-color:#bfdbfe;color:#0453cb}.rc-btn--primary{background:#0453cb;color:white}
@media(max-width:650px){.rc-overlay{padding:.5rem}.rc-dialog{max-height:97dvh}.rc-head,.rc-content,.rc-foot{padding:1rem}.rc-debt,.rc-fields{display:flex;flex-direction:column}.rc-foot .rc-btn{flex:1}}
</style>
@endpush
@push('scripts')
<script>
if (typeof window.reliquatCaisseModal !== 'function') {
    window.reliquatCaisseModal = function (initialInscription) {
        return {
            inscriptionId: Number(initialInscription) || 0,
            open: false, loading: false, sending: false, lignes: [],
            reliquatId: null, selection: null, montant: '', error: '',
            field: '', success: '', receipt: '', requestKey: null,
            argent(n) { return new Intl.NumberFormat('fr-FR').format(Number(n || 0)) + ' FCFA'; },
            setInscription(id) {
                const next = Number(id) || 0;
                if (this.inscriptionId !== next) {
                    this.inscriptionId = next;
                    this.lignes = []; this.selection = null; this.reliquatId = null;
                    if (this.open) this.charger();
                }
            },
            async ouvrir() {
                if (!this.inscriptionId) return;
                this.open = true; this.error = ''; this.success = ''; this.receipt = '';
                this.requestKey = null;
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => this.$refs.dialog?.focus());
                await this.charger();
            },
            fermer() {
                this.open = false;
                document.body.style.overflow = '';
                this.$nextTick(() => this.$el.querySelector('.rc-open')?.focus());
            },
            destroy() { document.body.style.overflow = ''; },
            selectionner(r) {
                this.selection = r; this.reliquatId = r.id; this.error = ''; this.field = '';
                this.montant = ''; this.requestKey = null;
            },
            async charger() {
                if (!this.inscriptionId) return;
                this.loading = true; this.error = '';
                const requested = this.inscriptionId;
                try {
                    const url = @json(route('esbtp.paiements.reliquats.etat', ['inscription' => '__ID__'])).replace('__ID__', String(requested));
                    const response = await fetch(url, {headers:{'Accept':'application/json'},credentials:'same-origin'});
                    if (!response.ok) throw Error('Consultation des dettes indisponible.');
                    const json = await response.json();
                    if (requested !== this.inscriptionId || !this.open) return;
                    this.lignes = json.reliquats || [];
                    this.selection = this.lignes.find(r => r.id === Number(this.reliquatId) && r.payable) || null;
                    if (!this.selection) { this.reliquatId = null; this.montant = ''; }
                    this.$dispatch('reliquat-encaissement-refresh', {inscriptionId:requested, soldes:json.soldes});
                } catch (e) { this.error = e.message || 'Lecture impossible.'; }
                finally { this.loading = false; }
            },
            async envoyer() {
                if (this.sending || this.success) return;
                const form = this.$refs.dialog?.querySelector('form');
                if (!form) return;
                const data = new FormData(form);
                const montant = Number(this.montant);
                const mode = data.get('mode_paiement');
                this.error = ''; this.field = '';
                if (!this.selection || !this.selection.payable) { this.error = 'Choisissez une dette disponible.'; return; }
                if (!Number.isFinite(montant) || montant < 1 || montant > this.selection.disponible) {
                    this.error = 'Le montant doit rester dans le disponible de la dette.'; this.field = 'montant'; return;
                }
                if (!mode) { this.error = 'Choisissez un mode de paiement.'; this.field = 'mode_paiement'; return; }
                if (!this.requestKey) {
                    this.requestKey = window.crypto?.randomUUID?.() || null;
                }
                data.set('reliquat_id', String(this.reliquatId));
                data.set('montant', String(montant));
                if (this.requestKey) data.set('request_key', this.requestKey);
                this.sending = true;
                try {
                    const response = await fetch(@json(route('esbtp.reliquats.pay')), {
                        method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
                        credentials:'same-origin',body:data
                    });
                    const json = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const field = Object.keys(json.errors || {})[0];
                        this.field = field || '';
                        throw Error(field ? json.errors[field][0] : json.message || 'Versement refusé.');
                    }
                    this.success = json.message || 'Versement enregistré.';
                    this.receipt = json.numero_recu || '';
                    await this.charger();
                } catch (e) { this.error = e.message || 'Vérifiez le paiement avant de réessayer.'; }
                finally { this.sending = false; }
            },
        };
    };
}
</script>
@endpush
@endonce
