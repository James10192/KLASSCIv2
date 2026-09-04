{{--
    Panneau « Pièces du dossier » de la fiche d'inscription.

    Ne s'affiche que si l'école a configuré son catalogue : tant qu'il est vide,
    la fiche reste exactement ce qu'elle était.

    Le vocabulaire est volontairement distinct de celui des réserves : une pièce
    « non fournie » existe et n'a pas été déposée ; une inscription « sous
    réserve » attend un document qui n'est pas encore délivré.
--}}
@php
    $pdiEtat = $piecesDossier ?? null;
    $pdiVisible = is_array($pdiEtat)
        && ($pdiEtat['actif'] ?? false)
        && auth()->check()
        && auth()->user()->can('inscriptions.pieces.view');
    $pdiModifiable = $pdiVisible && auth()->user()->can('inscriptions.pieces.manage');
@endphp

@if($pdiVisible)
@php
    // Chargé une seule fois dans la fabrique Alpine : l'état vit ensuite côté
    // navigateur et se met à jour à chaque bascule, sans rechargement.
    $pdiPayload = [
        'etat' => $pdiEtat,
        'modifiable' => $pdiModifiable,
        'url' => route('esbtp.inscriptions.pieces.basculer', $inscription),
        'csrf' => csrf_token(),
    ];
@endphp

<div class="is-card pdi-card"
     data-pdi-payload='@json($pdiPayload)'
     x-data="pdiPanneauPieces"
     x-init="init()">
    <div class="is-card-body">
        <div class="is-section-header">
            <div class="is-section-icon"><i class="fas fa-folder-open"></i></div>
            <div class="is-section-title">Pièces du dossier</div>
            <span class="pdi-annee">
                Exemplaire {{ $inscription->anneeUniversitaire->name ?? 'de l\'année' }}
            </span>
        </div>

        <p class="pdi-intro">
            Les documents que l'établissement conserve pour <strong>cette inscription</strong>.
            Un exemplaire est repris à chaque année : ce qui est coché ici ne concerne
            que {{ $inscription->anneeUniversitaire->name ?? 'l\'année en cours' }}.
        </p>

        {{-- Compteur : lisible d'un coup d'œil, c'est la question que le
             secrétariat se pose en ouvrant la fiche. --}}
        <div class="pdi-compteur">
            <div class="pdi-compteur-chiffre">
                <span x-text="etat.compteur.fournies"></span><span class="pdi-compteur-sur">/</span><span x-text="etat.compteur.total"></span>
            </div>
            <div class="pdi-compteur-corps">
                <div class="pdi-compteur-legende">
                    <span x-text="libelleCompteur()"></span>
                </div>
                <div class="pdi-jauge">
                    <div class="pdi-jauge-remplie" :style="`width:${pourcentage()}%`"></div>
                </div>
                <div class="pdi-compteur-detail">
                    <span class="pdi-etiquette pdi-etiquette--manque" x-show="etat.obligatoires_manquantes > 0" x-cloak>
                        <i class="fas fa-exclamation-circle"></i>
                        <span x-text="libelleManquantes()"></span>
                    </span>
                    <span class="pdi-etiquette pdi-etiquette--complet" x-show="etat.obligatoires_manquantes === 0" x-cloak>
                        <i class="fas fa-check-circle"></i>
                        Toutes les pièces obligatoires sont fournies
                    </span>
                </div>
            </div>
        </div>

        <ul class="pdi-liste">
            <template x-for="piece in etat.pieces" :key="piece.code">
                <li class="pdi-ligne" :class="piece.fournie ? 'pdi-ligne--fournie' : (piece.obligatoire ? 'pdi-ligne--manque' : '')">
                    <button type="button"
                            class="pdi-bascule"
                            :class="piece.fournie ? 'pdi-bascule--fournie' : ''"
                            :disabled="!modifiable || enCours === piece.code"
                            :aria-pressed="piece.fournie"
                            :title="modifiable
                                ? (piece.fournie ? 'Repasser en non fournie' : 'Marquer comme fournie')
                                : 'Vous n\'avez pas le droit de modifier les pièces'"
                            @click="basculer(piece)">
                        <i class="fas" :class="enCours === piece.code ? 'fa-spinner fa-spin' : (piece.fournie ? 'fa-check' : 'fa-square')"></i>
                    </button>

                    <div class="pdi-ligne-corps">
                        <div class="pdi-ligne-titre">
                            <span x-text="piece.libelle"></span>
                            <span class="pdi-tag" :class="piece.obligatoire ? 'pdi-tag--obligatoire' : 'pdi-tag--facultative'"
                                  x-text="piece.obligatoire ? 'Obligatoire' : 'Facultative'"></span>
                        </div>
                        <div class="pdi-ligne-sous" x-show="piece.description" x-cloak x-text="piece.description"></div>
                        <div class="pdi-ligne-trace" x-show="piece.fournie" x-cloak>
                            <i class="fas fa-clock"></i>
                            <span x-text="traceDepot(piece)"></span>
                        </div>
                    </div>

                    <span class="pdi-statut" :class="piece.fournie ? 'pdi-statut--fournie' : 'pdi-statut--non'"
                          x-text="piece.fournie ? 'Fournie' : 'Non fournie'"></span>
                </li>
            </template>
        </ul>

        {{-- La note qui évite la confusion la plus coûteuse de cette page. --}}
        <div class="pdi-note">
            <i class="fas fa-circle-info"></i>
            <div>
                <strong>Pièce non fournie</strong> : le document existe, il n'a pas encore été déposé au secrétariat.
                À ne pas confondre avec une <strong>inscription sous réserve</strong>, qui attend un document
                <em>pas encore délivré</em> (un relevé de baccalauréat, par exemple) et se gère depuis la page des réserves.
                Une pièce manquante <strong>ne bloque pas</strong> la validation de l'inscription.
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Namespace pdi-* : pièces du dossier d'inscription. Palette monochrome bleu
   KLASSCI ; le vert et l'orange ne servent qu'au statut, jamais à décorer. */

/* La feuille qui porte [x-cloak] n'est pas chargée sur les écrans
   d'administration : on la reprend ici, limitée au panneau. */
.pdi-card [x-cloak], .is-hero [x-cloak] { display: none !important; }
.pdi-annee {
    margin-left: auto;
    font-size: .72rem;
    font-weight: 700;
    color: #0453cb;
    background: rgba(4,83,203,.08);
    border: 1px solid rgba(4,83,203,.18);
    border-radius: 6px;
    padding: .2rem .55rem;
    white-space: nowrap;
}
.pdi-intro { font-size: .85rem; color: #64748b; margin: .75rem 0 1rem; line-height: 1.55; }

.pdi-compteur {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, rgba(4,83,203,.05), rgba(94,145,222,.08));
    border: 1px solid rgba(4,83,203,.16);
    border-radius: 12px;
    padding: .9rem 1.1rem;
    margin-bottom: 1rem;
}
.pdi-compteur-chiffre { font-size: 1.6rem; font-weight: 800; color: #0453cb; line-height: 1; white-space: nowrap; }
.pdi-compteur-sur { color: #94a3b8; margin: 0 .15rem; font-weight: 600; }
.pdi-compteur-corps { flex: 1; min-width: 0; }
.pdi-compteur-legende { font-size: .8rem; font-weight: 600; color: #1e293b; margin-bottom: .4rem; }
.pdi-jauge { height: 6px; background: rgba(4,83,203,.12); border-radius: 999px; overflow: hidden; }
.pdi-jauge-remplie { height: 100%; background: linear-gradient(90deg, #0453cb, #5e91de); border-radius: 999px; transition: width .25s ease; }
.pdi-compteur-detail { margin-top: .45rem; }
.pdi-etiquette { display: inline-flex; align-items: center; gap: .35rem; font-size: .74rem; font-weight: 700; }
.pdi-etiquette--manque { color: #b45309; }
.pdi-etiquette--complet { color: #059669; }

.pdi-liste { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .45rem; }
.pdi-ligne {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    padding: .7rem .85rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    transition: border-color .15s ease, background .15s ease;
}
.pdi-ligne--fournie { border-color: rgba(16,185,129,.35); background: rgba(16,185,129,.04); }
.pdi-ligne--manque { border-color: rgba(245,158,11,.4); background: rgba(245,158,11,.05); }

.pdi-bascule {
    flex-shrink: 0;
    width: 30px; height: 30px;
    border-radius: 8px;
    border: 1.5px solid #cbd5e1;
    background: #fff;
    color: #94a3b8;
    font-size: .78rem;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s ease;
}
.pdi-bascule:hover:not(:disabled) { border-color: #0453cb; color: #0453cb; }
.pdi-bascule--fournie { background: #10b981; border-color: #10b981; color: #fff; }
.pdi-bascule:disabled { cursor: not-allowed; opacity: .65; }

.pdi-ligne-corps { flex: 1; min-width: 0; }
.pdi-ligne-titre { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; font-size: .88rem; font-weight: 600; color: #1e293b; }
.pdi-tag { font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: .12rem .4rem; border-radius: 5px; }
.pdi-tag--obligatoire { background: rgba(4,83,203,.10); color: #0453cb; border: 1px solid rgba(4,83,203,.22); }
.pdi-tag--facultative { background: rgba(100,116,139,.10); color: #64748b; border: 1px solid rgba(100,116,139,.20); }
.pdi-ligne-sous { font-size: .78rem; color: #64748b; margin-top: .2rem; line-height: 1.45; }
.pdi-ligne-trace { font-size: .72rem; color: #059669; margin-top: .25rem; display: flex; align-items: center; gap: .3rem; }

.pdi-statut { flex-shrink: 0; font-size: .72rem; font-weight: 700; padding: .25rem .55rem; border-radius: 6px; white-space: nowrap; }
.pdi-statut--fournie { background: rgba(16,185,129,.12); color: #059669; }
.pdi-statut--non { background: rgba(100,116,139,.12); color: #475569; }

.pdi-note {
    display: flex; gap: .6rem; align-items: flex-start;
    margin-top: 1rem;
    padding: .75rem .9rem;
    background: rgba(4,83,203,.04);
    border-left: 3px solid #0453cb;
    border-radius: 8px;
    font-size: .78rem; color: #475569; line-height: 1.55;
}
.pdi-note i { color: #0453cb; margin-top: .15rem; }

@media (max-width: 576px) {
    .pdi-compteur { flex-direction: column; align-items: stretch; gap: .6rem; }
    .pdi-ligne { flex-wrap: wrap; }
    .pdi-statut { margin-left: 2.4rem; }
}
</style>
@endpush

@push('scripts')
<script>
// Garde d'idempotence : la fabrique peut être réinjectée si le panneau est un
// jour rendu dans un modal chargé en AJAX.
if (typeof window.pdiPanneauPiecesEnregistre === 'undefined') {
    window.pdiPanneauPiecesEnregistre = true;
    document.addEventListener('alpine:init', function () {
        Alpine.data('pdiPanneauPieces', function () {
            return {
                etat: { pieces: [], compteur: { fournies: 0, total: 0, base: 'toutes' }, obligatoires_manquantes: 0 },
                modifiable: false,
                url: '',
                csrf: '',
                enCours: null,

                init() {
                    // L'état passe par un data-attribute et non par une
                    // interpolation dans x-data : Blade compte mal les accolades
                    // quand on mélange objet JS et {{ }}.
                    var charge = JSON.parse(this.$root.dataset.pdiPayload);
                    this.etat = charge.etat;
                    this.modifiable = charge.modifiable;
                    this.url = charge.url;
                    this.csrf = charge.csrf;
                },

                pourcentage() {
                    if (!this.etat.compteur.total) { return 0; }
                    return Math.round((this.etat.compteur.fournies / this.etat.compteur.total) * 100);
                },

                libelleCompteur() {
                    var singulier = this.etat.compteur.total <= 1;
                    if (this.etat.compteur.base === 'obligatoires') {
                        return singulier ? 'pièce obligatoire fournie' : 'pièces obligatoires fournies';
                    }
                    return singulier ? 'pièce fournie' : 'pièces fournies';
                },

                libelleManquantes() {
                    var n = this.etat.obligatoires_manquantes;
                    return n + (n > 1 ? ' pièces obligatoires non fournies' : ' pièce obligatoire non fournie');
                },

                traceDepot(piece) {
                    var morceaux = [];
                    if (piece.fournie_le) { morceaux.push('Reçue le ' + piece.fournie_le); }
                    if (piece.marquee_par) { morceaux.push('par ' + piece.marquee_par); }
                    return morceaux.length ? morceaux.join(' · ') : 'Reçue';
                },

                async basculer(piece) {
                    if (!this.modifiable || this.enCours) { return; }
                    this.enCours = piece.code;
                    try {
                        var reponse = await fetch(this.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ code: piece.code, fournie: !piece.fournie }),
                        });
                        var donnees = await reponse.json().catch(function () { return {}; });

                        // Même en cas de refus, le serveur renvoie l'état réel :
                        // l'écran se remet d'accord avec la base sans recharger.
                        if (donnees.etat) { this.etat = donnees.etat; }
                        this.signalerFiche();

                        if (!reponse.ok || !donnees.success) {
                            this.avertir(donnees.message || 'Enregistrement impossible.', 'error');
                        } else if (donnees.message) {
                            this.avertir(donnees.message, 'success');
                        }
                    } catch (e) {
                        this.avertir('Enregistrement impossible. Vérifiez votre connexion.', 'error');
                    } finally {
                        this.enCours = null;
                    }
                },

                // La pastille du haut de la fiche suit la case cochée : sans
                // cela, il faudrait recharger pour voir le signal disparaître.
                signalerFiche() {
                    var pastille = document.getElementById('pdi-pastille-fiche');
                    if (pastille) {
                        var n = this.etat.obligatoires_manquantes;
                        pastille.style.display = this.etat.signal ? '' : 'none';
                        var texte = document.getElementById('pdi-pastille-texte');
                        if (texte) {
                            texte.textContent = n + (n > 1 ? ' pièces manquantes' : ' pièce manquante');
                        }
                    }
                    window.dispatchEvent(new CustomEvent('pieces-dossier:maj', {
                        detail: { manquantes: this.etat.obligatoires_manquantes },
                    }));
                },

                avertir(message, type) {
                    if (typeof window.showToast === 'function') {
                        window.showToast(message, type);
                    } else if (type === 'error') {
                        alert(message);
                    }
                },
            };
        });
    });
}
</script>
@endpush
@endif
