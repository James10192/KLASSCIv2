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

{{-- Styles portés par paiements.index : ils restent disponibles après un rafraîchissement AJAX. --}}


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
