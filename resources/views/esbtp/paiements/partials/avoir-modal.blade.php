@can('paiements.avoir')
@if($paiement->status == 'validé' && ! $paiement->isAvoir() && $paiement->avoir_disponible > 0)
@php
    $avMax = (int) $paiement->avoir_disponible;
    $avEtudiant = trim(($paiement->etudiant->nom ?? '').' '.($paiement->etudiant->prenoms ?? ''));
@endphp
<div class="modal fade av-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content av-content">
            <form method="POST" action="{{ route('esbtp.paiements.avoir.store', $paiement->id) }}"
                  x-data="avoirForm({{ $avMax }})" @submit="if (!confirme) { $event.preventDefault(); confirme = true; }">
                @csrf

                <div class="av-head">
                    <div class="av-head-ic"><i class="fas fa-rotate-left"></i></div>
                    <div>
                        <div class="av-head-t">Annuler tout ou partie du versement</div>
                        <div class="av-head-s">Reçu {{ $paiement->numero_recu }}@if($avEtudiant) &middot; {{ $avEtudiant }}@endif</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="av-body">
                    {{-- Etape 1 : la saisie. Masquee des que l'agent demande a confirmer,
                         pour qu'il relise ce qu'il a decide au lieu de le survoler. --}}
                    <div x-show="!confirme">
                        <div class="av-solde">
                            <span>Reste annulable sur ce reçu</span>
                            <strong>{{ number_format($avMax, 0, ',', ' ') }} FCFA</strong>
                        </div>

                        <div class="av-lbl">Que devient l'argent ?</div>
                        <label class="av-kind" :class="kind === 'credit' ? 'av-kind--on' : ''">
                            <input type="radio" name="avoir_kind" value="credit" x-model="kind" required>
                            <span class="av-kind-ic"><i class="fas fa-wallet"></i></span>
                            <span>
                                <span class="av-kind-t">Crédit sur compte</span>
                                <span class="av-kind-d">L'argent reste à l'école. L'étudiant le réutilise sur un autre frais. Rien ne sort de la caisse.</span>
                            </span>
                        </label>
                        <label class="av-kind" :class="kind === 'refund' ? 'av-kind--on' : ''">
                            <input type="radio" name="avoir_kind" value="refund" x-model="kind" required>
                            <span class="av-kind-ic"><i class="fas fa-money-bill-transfer"></i></span>
                            <span>
                                <span class="av-kind-t">Remboursement en espèces</span>
                                <span class="av-kind-d">L'argent est rendu à l'étudiant. La sortie apparaît au journal de caisse du jour.</span>
                            </span>
                        </label>

                        <div class="av-grid">
                            <div>
                                <div class="av-lbl">Montant à annuler</div>
                                <input type="number" name="montant" class="av-input" min="1" max="{{ $avMax }}"
                                       x-model.number="montant" required>
                                <button type="button" class="av-link" @click="montant = {{ $avMax }}">Tout annuler</button>
                            </div>
                            <div>
                                <div class="av-lbl">Motif <span class="av-req">obligatoire</span></div>
                                <textarea name="motif" class="av-input av-area" rows="3" minlength="10" maxlength="500"
                                          x-model="motif" required
                                          placeholder="Pourquoi ce versement est-il annulé ? (10 caractères minimum)"></textarea>
                                <div class="av-hint" :class="motif.length >= 10 ? 'av-hint--ok' : ''"
                                     x-text="motif.length >= 10 ? 'Motif suffisant' : (10 - motif.length) + ' caractère(s) manquant(s)'"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Etape 2 : la relecture. On repete ce qui va se passer en toutes
                         lettres, parce qu'un avoir ne se defait pas d'un clic. --}}
                    <div x-show="confirme" x-cloak>
                        <div class="av-confirm">
                            <div class="av-confirm-ic"><i class="fas fa-triangle-exclamation"></i></div>
                            <div>
                                <div class="av-confirm-t">Confirmez avant d'enregistrer</div>
                                <p class="av-confirm-p">
                                    Vous allez annuler <strong x-text="format(montant) + ' FCFA'"></strong>
                                    sur le reçu <strong>{{ $paiement->numero_recu }}</strong>@if($avEtudiant) de <strong>{{ $avEtudiant }}</strong>@endif.
                                </p>
                                <p class="av-confirm-p" x-show="kind === 'refund'" x-cloak>
                                    Cette somme <strong>sortira de la caisse</strong> et figurera au journal du jour.
                                </p>
                                <p class="av-confirm-p" x-show="kind === 'credit'" x-cloak>
                                    Cette somme <strong>reste à l'école</strong> en crédit réutilisable. Le frais concerné redeviendra dû.
                                </p>
                                <p class="av-confirm-p av-confirm-trace">
                                    L'opération est inscrite au journal d'audit avec votre nom, l'heure et votre adresse. Elle ne s'efface pas.
                                </p>
                                <div class="av-confirm-motif">
                                    <span>Motif enregistré</span>
                                    <em x-text="motif"></em>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="av-foot">
                    <button type="button" class="av-btn av-btn--ghost" x-show="!confirme" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="av-btn av-btn--ghost" x-show="confirme" x-cloak @click="confirme = false">Revenir</button>
                    <button type="submit" class="av-btn av-btn--danger" :disabled="!valide()">
                        <span x-show="!confirme">Continuer</span>
                        <span x-show="confirme" x-cloak><i class="fas fa-check"></i> Enregistrer l'annulation</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .av-content { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(15,23,42,.22); }
    .av-head { display:flex; align-items:center; gap:.8rem; padding:1.05rem 1.3rem;
        background:linear-gradient(135deg,#0a3d8f,#0453cb 60%,#3b7ddb); color:#fff; }
    .av-head-ic { width:38px; height:38px; border-radius:11px; background:rgba(255,255,255,.15);
        border:1px solid rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .av-head-t { font-weight:700; font-size:1rem; line-height:1.2; }
    .av-head-s { font-size:.76rem; color:rgba(255,255,255,.72); margin-top:.1rem; }
    .av-body { padding:1.1rem 1.3rem; }
    .av-solde { display:flex; align-items:center; justify-content:space-between; gap:1rem;
        background:#f8fafc; border:1px solid #e2e8f0; border-radius:11px; padding:.6rem .85rem; margin-bottom:1rem; }
    .av-solde span { font-size:.78rem; color:#64748b; }
    .av-solde strong { font-size:1.05rem; color:#0f172a; }
    .av-lbl { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
        color:#64748b; margin-bottom:.4rem; }
    .av-req { color:#b45309; letter-spacing:0; text-transform:none; font-weight:600; }
    .av-kind { display:flex; align-items:flex-start; gap:.7rem; border:1px solid #e2e8f0; border-radius:11px;
        padding:.65rem .8rem; margin-bottom:.5rem; cursor:pointer; transition:border-color .15s, background .15s; }
    .av-kind:hover { border-color:#c7d4e5; background:#f8fafc; }
    .av-kind--on { border-color:#0453cb; background:rgba(4,83,203,.05); }
    .av-kind input { margin-top:.25rem; }
    .av-kind-ic { width:30px; height:30px; border-radius:8px; background:rgba(4,83,203,.1); color:#0453cb;
        display:flex; align-items:center; justify-content:center; font-size:.82rem; flex-shrink:0; }
    .av-kind-t { display:block; font-weight:700; font-size:.88rem; color:#1e293b; }
    .av-kind-d { display:block; font-size:.76rem; color:#64748b; line-height:1.35; margin-top:.1rem; }
    .av-grid { display:grid; grid-template-columns:1fr 1.4fr; gap:.9rem; margin-top:.9rem; }
    .av-input { width:100%; border:1px solid #e2e8f0; border-radius:9px; padding:.5rem .7rem;
        font-size:.9rem; color:#1e293b; background:#fff; }
    .av-input:focus { outline:none; border-color:#0453cb; box-shadow:0 0 0 3px rgba(4,83,203,.1); }
    .av-area { resize:vertical; min-height:74px; font-size:.85rem; }
    .av-link { background:none; border:none; padding:0; margin-top:.3rem; font-size:.75rem;
        color:#0453cb; font-weight:600; cursor:pointer; }
    .av-hint { font-size:.72rem; color:#b45309; margin-top:.25rem; }
    .av-hint--ok { color:#065f46; }
    .av-confirm { display:flex; gap:.8rem; background:#fff7ed; border:1px solid #fed7aa;
        border-radius:12px; padding:.9rem 1rem; }
    .av-confirm-ic { width:34px; height:34px; border-radius:10px; background:#f59e0b; color:#fff;
        display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .av-confirm-t { font-weight:700; color:#9a3412; margin-bottom:.35rem; }
    .av-confirm-p { font-size:.85rem; color:#7c2d12; margin:0 0 .4rem; line-height:1.45; }
    .av-confirm-trace { font-size:.78rem; color:#9a3412; opacity:.85; }
    .av-confirm-motif { margin-top:.5rem; border-top:1px solid #fed7aa; padding-top:.5rem; }
    .av-confirm-motif span { display:block; font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:#9a3412; }
    .av-confirm-motif em { font-size:.84rem; color:#7c2d12; }
    .av-foot { display:flex; justify-content:flex-end; gap:.6rem; padding:.85rem 1.3rem;
        border-top:1px solid #e2e8f0; background:#f8fafc; }
    .av-btn { border:none; border-radius:9px; padding:.5rem 1rem; font-size:.85rem; font-weight:600; cursor:pointer; }
    .av-btn--ghost { background:#fff; border:1px solid #e2e8f0; color:#475569; }
    .av-btn--danger { background:#dc2626; color:#fff; }
    .av-btn--danger:hover:not(:disabled) { background:#b91c1c; }
    .av-btn--danger:disabled { opacity:.5; cursor:not-allowed; }
    @@media (max-width:576px) { .av-grid { grid-template-columns:1fr; } }
</style>
@endpush
@endonce

<script>
    // Garde d'idempotence : ce partial est rendu une fois par ligne de paiement,
    // et peut revenir par une reponse AJAX.
    if (typeof window.avoirForm !== 'function') {
        window.avoirForm = function (max) {
            return {
                confirme: false,
                kind: 'credit',
                montant: max,
                motif: '',
                format(n) {
                    return new Intl.NumberFormat('fr-FR').format(n || 0);
                },
                valide() {
                    return this.kind !== ''
                        && this.montant > 0
                        && this.montant <= max
                        && this.motif.trim().length >= 10;
                },
            };
        };
    }
</script>
@endif
@endcan
