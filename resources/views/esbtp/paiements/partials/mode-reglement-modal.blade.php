@can('paiements.correct_mode')
@if($paiement->status === 'validé' && ! $paiement->isAvoir() && ! $paiement->reconciliation_locked_at)
@php
    $mrModes = \App\Http\Controllers\Comptabilite\ModeReglementController::MODES;
    $mrActuel = (string) $paiement->mode_paiement;
    $mrEtudiant = trim(($paiement->etudiant->nom ?? '').' '.($paiement->etudiant->prenoms ?? ''));
@endphp
<div class="modal fade mr-modal" id="modeReglementModal{{ $paiement->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content mr-content">
            <form method="POST" action="{{ route('esbtp.paiements.mode-reglement.update', $paiement->id) }}"
                  x-data="modeReglementForm(@js($mrActuel))"
                  @submit="if (!confirme) { $event.preventDefault(); confirme = true; }">
                @csrf
                @method('PATCH')

                <div class="mr-head">
                    <div class="mr-head-ic"><i class="fas fa-right-left"></i></div>
                    <div>
                        <div class="mr-head-t">Corriger le mode de règlement</div>
                        <div class="mr-head-s">Reçu {{ $paiement->numero_recu }}@if($mrEtudiant) &middot; {{ $mrEtudiant }}@endif</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="mr-body">
                    <div class="mr-note">
                        Seule l'étiquette du règlement change. Le montant
                        (<strong>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong>),
                        le frais et la date restent intacts : ce qui touche à ce que l'étudiant a payé
                        passe par la réconciliation de caisse.
                    </div>

                    <div x-show="!confirme">
                        <div class="mr-lbl">Mode enregistré aujourd'hui</div>
                        <div class="mr-actuel">{{ $mrModes[$mrActuel] ?? ($mrActuel ?: 'non renseigné') }}</div>

                        <div class="mr-lbl">Mode réel</div>
                        <select name="mode_paiement" class="mr-input" x-model="mode" required>
                            @foreach($mrModes as $cle => $libelle)
                                <option value="{{ $cle }}" @selected($cle === $mrActuel)>{{ $libelle }}</option>
                            @endforeach
                        </select>

                        <div class="mr-lbl">Motif <span class="mr-req">obligatoire</span></div>
                        <textarea name="motif" class="mr-input mr-area" rows="3" minlength="10" maxlength="500"
                                  x-model="motif" required
                                  placeholder="Pourquoi cette correction ? (10 caractères minimum)"></textarea>
                        <div class="mr-hint" :class="motif.length >= 10 ? 'mr-hint--ok' : ''"
                             x-text="motif.length >= 10 ? 'Motif suffisant' : (10 - motif.length) + ' caractère(s) manquant(s)'"></div>
                    </div>

                    <div x-show="confirme" x-cloak>
                        <div class="mr-confirm">
                            <div class="mr-confirm-ic"><i class="fas fa-circle-question"></i></div>
                            <div>
                                <div class="mr-confirm-t">Confirmez la correction</div>
                                <p class="mr-confirm-p">
                                    Le règlement de <strong>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong>
                                    passe de <strong>{{ $mrModes[$mrActuel] ?? $mrActuel }}</strong>
                                    à <strong x-text="libelle(mode)"></strong>.
                                </p>
                                <p class="mr-confirm-p">
                                    Le journal de caisse du jour changera de colonne en conséquence.
                                    L'ancienne et la nouvelle valeur, votre nom et l'heure sont inscrits
                                    au journal d'audit.
                                </p>
                                <div class="mr-confirm-motif">
                                    <span>Motif enregistré</span>
                                    <em x-text="motif"></em>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mr-foot">
                    <button type="button" class="mr-btn mr-btn--ghost" x-show="!confirme" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="mr-btn mr-btn--ghost" x-show="confirme" x-cloak @click="confirme = false">Revenir</button>
                    <button type="submit" class="mr-btn mr-btn--primary" :disabled="!valide()">
                        <span x-show="!confirme">Continuer</span>
                        <span x-show="confirme" x-cloak><i class="fas fa-check"></i> Enregistrer la correction</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .mr-content { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(15,23,42,.22); }
    .mr-head { display:flex; align-items:center; gap:.8rem; padding:1.05rem 1.3rem;
        background:linear-gradient(135deg,#0a3d8f,#0453cb 60%,#3b7ddb); color:#fff; }
    .mr-head-ic { width:38px; height:38px; border-radius:11px; background:rgba(255,255,255,.15);
        border:1px solid rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .mr-head-t { font-weight:700; font-size:1rem; line-height:1.2; }
    .mr-head-s { font-size:.76rem; color:rgba(255,255,255,.72); margin-top:.1rem; }
    .mr-body { padding:1.1rem 1.3rem; }
    .mr-note { background:#f8fafc; border:1px solid #e2e8f0; border-left:3px solid #0453cb;
        border-radius:9px; padding:.6rem .8rem; font-size:.8rem; color:#475569; line-height:1.45; margin-bottom:1rem; }
    .mr-lbl { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
        color:#64748b; margin:.85rem 0 .35rem; }
    .mr-req { color:#b45309; letter-spacing:0; text-transform:none; font-weight:600; }
    .mr-actuel { font-weight:700; font-size:.95rem; color:#0f172a; }
    .mr-input { width:100%; border:1px solid #e2e8f0; border-radius:9px; padding:.5rem .7rem;
        font-size:.9rem; color:#1e293b; background:#fff; }
    .mr-input:focus { outline:none; border-color:#0453cb; box-shadow:0 0 0 3px rgba(4,83,203,.1); }
    .mr-area { resize:vertical; min-height:74px; font-size:.85rem; }
    .mr-hint { font-size:.72rem; color:#b45309; margin-top:.25rem; }
    .mr-hint--ok { color:#065f46; }
    .mr-confirm { display:flex; gap:.8rem; background:#eff6ff; border:1px solid #bfdbfe;
        border-radius:12px; padding:.9rem 1rem; }
    .mr-confirm-ic { width:34px; height:34px; border-radius:10px; background:#0453cb; color:#fff;
        display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .mr-confirm-t { font-weight:700; color:#1e3a8a; margin-bottom:.35rem; }
    .mr-confirm-p { font-size:.85rem; color:#1e40af; margin:0 0 .4rem; line-height:1.45; }
    .mr-confirm-motif { margin-top:.5rem; border-top:1px solid #bfdbfe; padding-top:.5rem; }
    .mr-confirm-motif span { display:block; font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:#1e3a8a; }
    .mr-confirm-motif em { font-size:.84rem; color:#1e40af; }
    .mr-foot { display:flex; justify-content:flex-end; gap:.6rem; padding:.85rem 1.3rem;
        border-top:1px solid #e2e8f0; background:#f8fafc; }
    .mr-btn { border:none; border-radius:9px; padding:.5rem 1rem; font-size:.85rem; font-weight:600; cursor:pointer; }
    .mr-btn--ghost { background:#fff; border:1px solid #e2e8f0; color:#475569; }
    .mr-btn--primary { background:#0453cb; color:#fff; }
    .mr-btn--primary:hover:not(:disabled) { background:#033a8e; }
    .mr-btn--primary:disabled { opacity:.5; cursor:not-allowed; }
</style>
@endpush
@endonce

<script>
    // Garde d'idempotence : ce partial est rendu une fois par ligne, et peut
    // revenir par une reponse AJAX.
    if (typeof window.modeReglementForm !== 'function') {
        window.modeReglementForm = function (modeActuel) {
            return {
                confirme: false,
                mode: modeActuel,
                motif: '',
                libelle(cle) {
                    const el = this.$root.querySelector(`option[value="${cle}"]`);
                    return el ? el.textContent.trim() : cle;
                },
                valide() {
                    return this.mode !== ''
                        && this.mode !== modeActuel
                        && this.motif.trim().length >= 10;
                },
            };
        };
    }
</script>
@endif
@endcan
