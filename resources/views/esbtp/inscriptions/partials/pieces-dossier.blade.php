{{--
    Le suivi des pièces du dossier, au guichet.

    Le geste principal est UNE CASE À COCHER. Le téléversement est un surplus
    proposé à côté, jamais un passage obligé : l'original déposé reste ce qui
    fait foi, et une école qui ne numérise rien doit pouvoir suivre ses dossiers
    de bout en bout.

    Rien ne s'affiche tant que l'école n'a pas configuré son catalogue.
--}}
@php
    $_piecesLignes = collect($pieces ?? []);
@endphp

@if($_piecesLignes->isNotEmpty())
@php
    $_piecesCharge = [
        'lignes' => $_piecesLignes->map(function ($l) {
            return [
                'piece_id' => $l['piece']->id,
                'libelle' => $l['piece']->libelle,
                'description' => $l['piece']->description,
                'obligatoire' => (bool) $l['piece']->is_obligatoire,
                'annuelle' => $l['annuelle'],
                'requis' => $l['requis'],
                'depose' => $l['depose'],
                'disponible' => $l['disponible'],
                'manquant' => $l['manquant'],
                'satisfaite' => $l['satisfaite'],
                'non_applicable' => $l['non_applicable'],
                'motif_non_applicable' => $l['motif_non_applicable'],
                'etat' => $l['etat']->value,
                'etat_label' => $l['etat']->label(),
                'ton' => $l['etat']->ton(),
                'perimes' => $l['depots_perimes']->count(),
                'depots' => $l['depots']->map(fn ($d) => [
                    'id' => $d->id,
                    'quantite' => $d->quantite_deposee,
                    'etat' => $d->etat?->value,
                    'etat_label' => $d->etat?->label(),
                    'motif' => $d->motif,
                    'date_depot' => $d->date_depot?->format('d/m/Y'),
                ])->values()->all(),
            ];
        })->values()->all(),
        'synthese' => $piecesSynthese ?? [],
        'relecture' => (bool) ($piecesRelecture ?? false),
        'epuisement' => $piecesEpuisement ?? 'signaler',
        'peutSuivre' => auth()->user()?->can('pieces_dossier.suivre') ?? false,
        'urls' => [
            'base' => url('/esbtp/inscriptions/' . $inscription->id . '/pieces'),
            'decision' => url('/esbtp/pieces-deposees'),
        ],
    ];
@endphp

<div id="dossier" class="is-card pdo" data-pdo='@json($_piecesCharge)' x-data="dossierPieces()" x-init="init()">
    <div class="is-card-body">
        <div class="is-section-header">
            <div class="is-section-icon"><i class="fas fa-folder-open"></i></div>
            <div class="is-section-title">Pièces du dossier</div>
            <span class="pdo-compteur"
                  :class="synthese.complet ? 'pdo-compteur--ok' : 'pdo-compteur--manque'"
                  x-text="synthese.complet
                        ? 'Dossier complet'
                        : (synthese.manquantes + (synthese.manquantes > 1 ? ' pièces manquantes' : ' pièce manquante'))"></span>
        </div>

        <p class="pdo-aide">
            Cochez ce que l'étudiant a remis. Le document original reste au dossier ;
            le fichier n'est qu'une copie de consultation, pour éviter d'aller au
            classeur quand quelqu'un veut relire la pièce.
        </p>

        <template x-if="lignes.length === 0">
            <div class="is-empty">
                <div class="is-empty-icon"><i class="fas fa-folder-open"></i></div>
                <div class="is-empty-text">Aucune pièce réclamée pour cette filière et ce niveau</div>
            </div>
        </template>

        <div class="pdo-liste">
            <template x-for="ligne in lignes" :key="ligne.piece_id">
                <div class="pdo-ligne" :class="{
                        'pdo-ligne--ok': ligne.satisfaite && !ligne.non_applicable,
                        'pdo-ligne--ecartee': ligne.non_applicable,
                        'pdo-ligne--manque': !ligne.satisfaite && !ligne.non_applicable
                     }">

                    <label class="pdo-coche" :title="peutSuivre ? '' : 'Vous n\'avez pas le droit de modifier ce suivi'">
                        <input type="checkbox"
                               :checked="ligne.depots.length > 0"
                               :disabled="!peutSuivre || occupe === ligne.piece_id || ligne.non_applicable"
                               @change="basculer(ligne, $event.target.checked)">
                        <span class="pdo-coche-boite"><i class="fas fa-check"></i></span>
                    </label>

                    <div class="pdo-corps">
                        <div class="pdo-titre">
                            <span x-text="ligne.libelle"></span>
                            <span class="pdo-tag pdo-tag--obligatoire" x-show="ligne.obligatoire">Obligatoire</span>
                            <span class="pdo-tag" x-show="ligne.annuelle">Chaque année</span>
                            <span class="pdo-tag" x-show="!ligne.annuelle && ligne.requis > 1"
                                  x-text="ligne.requis + ' exemplaires'"></span>
                        </div>

                        <div class="pdo-sous" x-show="ligne.description" x-text="ligne.description"></div>

                        {{-- Le stock : ce qui reste après ce que les autres années ont pris. --}}
                        <div class="pdo-sous" x-show="!ligne.annuelle && ligne.depose > 0">
                            <i class="fas fa-layer-group"></i>
                            <span x-text="ligne.depose + ' déposé' + (ligne.depose > 1 ? 's' : '')"></span>
                            <span x-text="'· ' + ligne.disponible + ' encore disponible' + (ligne.disponible > 1 ? 's' : '')"></span>
                        </div>

                        <div class="pdo-sous pdo-sous--alerte" x-show="ligne.perimes > 0">
                            <i class="fas fa-clock-rotate-left"></i>
                            <span x-text="ligne.perimes + ' exemplaire' + (ligne.perimes > 1 ? 's périmés' : ' périmé') + ' — à redemander'"></span>
                        </div>

                        <div class="pdo-sous pdo-sous--ecartee" x-show="ligne.non_applicable">
                            <i class="fas fa-ban"></i>
                            <span>Écartée cette année —</span>
                            <span x-text="ligne.motif_non_applicable"></span>
                        </div>

                        <template x-for="depot in ligne.depots" :key="depot.id">
                            <div class="pdo-depot" x-show="relecture || depot.etat === 'refusee'">
                                <span class="pdo-etat" :class="'pdo-etat--' + depot.etat" x-text="depot.etat_label"></span>
                                <span x-show="depot.date_depot" x-text="'remis le ' + depot.date_depot"></span>
                                <span class="pdo-motif" x-show="depot.motif" x-text="depot.motif"></span>

                                <span class="pdo-actions-depot" x-show="peutSuivre && depot.etat === 'deposee'">
                                    <button type="button" class="pdo-mini pdo-mini--ok"
                                            :disabled="occupe === ligne.piece_id"
                                            @click="decider(ligne, depot, 'validee')">Valider</button>
                                    <button type="button" class="pdo-mini pdo-mini--non"
                                            :disabled="occupe === ligne.piece_id"
                                            @click="demanderRefus(ligne, depot)">Refuser</button>
                                </span>
                            </div>
                        </template>
                    </div>

                    <div class="pdo-droite">
                        <span class="pdo-etat" :class="'pdo-etat--' + ligne.etat"
                              x-show="!ligne.non_applicable" x-text="ligne.etat_label"></span>

                        <button type="button" class="pdo-lien" x-show="peutSuivre && !ligne.non_applicable"
                                :disabled="occupe === ligne.piece_id"
                                @click="demanderEcart(ligne)">Ne s'applique pas</button>

                        <button type="button" class="pdo-lien" x-show="peutSuivre && ligne.non_applicable"
                                :disabled="occupe === ligne.piece_id"
                                @click="reintegrer(ligne)">Réclamer de nouveau</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Le motif se saisit dans un dialogue, pas dans un prompt du navigateur :
         un prompt n'a ni le style de l'application, ni la place d'ecrire une
         phrase, et il est bloque par certains navigateurs. --}}
    <div class="pdo-modal" x-show="motifOuvert" x-cloak
         @keydown.escape.window="fermerMotif()"
         @click.self="fermerMotif()">
        <div class="pdo-modal-boite">
            <div class="pdo-modal-titre" x-text="motifTitre"></div>
            <p class="pdo-modal-aide" x-text="motifAide"></p>
            <textarea class="pdo-modal-champ" rows="3" x-model="motifTexte"
                      x-ref="champMotif"
                      placeholder="En une phrase, pour la personne qui lira ce dossier apres vous."></textarea>
            <div class="pdo-modal-pied">
                <button type="button" class="pdo-modal-btn" @click="fermerMotif()">Annuler</button>
                <button type="button" class="pdo-modal-btn pdo-modal-btn--valider"
                        :disabled="motifTexte.trim().length < 3"
                        @click="confirmerMotif()">Confirmer</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .pdo-compteur { margin-left:auto; font-size:.74rem; font-weight:700; padding:.22rem .6rem; border-radius:6px; }
    .pdo-compteur--ok { background:rgba(16,185,129,.10); color:#047857; border:1px solid rgba(16,185,129,.25); }
    .pdo-compteur--manque { background:rgba(245,158,11,.10); color:#b45309; border:1px solid rgba(245,158,11,.25); }

    .pdo-aide { font-size:.78rem; color:#64748b; margin:.35rem 0 .9rem; line-height:1.5; }

    .pdo-liste { display:flex; flex-direction:column; gap:.4rem; }

    .pdo-ligne {
        display:flex; align-items:flex-start; gap:.75rem;
        padding:.7rem .8rem; border:1px solid #e2e8f0; border-radius:10px;
        background:#fff; transition:border-color .15s, background .15s;
    }
    .pdo-ligne--ok { border-color:rgba(16,185,129,.30); background:rgba(16,185,129,.03); }
    .pdo-ligne--manque { border-color:rgba(245,158,11,.30); background:rgba(245,158,11,.03); }
    .pdo-ligne--ecartee { opacity:.72; }

    .pdo-coche { position:relative; flex-shrink:0; cursor:pointer; margin-top:.1rem; }
    .pdo-coche input { position:absolute; opacity:0; width:0; height:0; }
    .pdo-coche-boite {
        display:flex; align-items:center; justify-content:center;
        width:22px; height:22px; border-radius:6px;
        border:2px solid #cbd5e1; background:#fff; color:transparent;
        font-size:.7rem; transition:all .15s;
    }
    .pdo-coche input:checked + .pdo-coche-boite { background:#0453cb; border-color:#0453cb; color:#fff; }
    .pdo-coche input:disabled + .pdo-coche-boite { opacity:.5; cursor:not-allowed; }
    .pdo-coche input:focus-visible + .pdo-coche-boite { outline:2px solid #0453cb; outline-offset:2px; }

    .pdo-corps { flex:1; min-width:0; }
    .pdo-titre { font-size:.86rem; font-weight:600; color:#1e293b; display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; }
    .pdo-sous { font-size:.73rem; color:#64748b; margin-top:.2rem; display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; }
    .pdo-sous--alerte { color:#b45309; }
    .pdo-sous--ecartee { color:#64748b; font-style:italic; }

    .pdo-tag {
        font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px;
        padding:.12rem .4rem; border-radius:4px;
        background:rgba(4,83,203,.08); color:#0453cb; border:1px solid rgba(4,83,203,.18);
    }
    .pdo-tag--obligatoire { background:rgba(4,83,203,.14); }

    .pdo-depot { font-size:.72rem; color:#64748b; margin-top:.3rem; display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; }
    .pdo-motif { font-style:italic; }

    .pdo-etat { font-size:.66rem; font-weight:700; padding:.14rem .45rem; border-radius:5px; white-space:nowrap; }
    .pdo-etat--attendue { background:#f1f5f9; color:#64748b; }
    .pdo-etat--deposee  { background:rgba(4,83,203,.10); color:#0453cb; }
    .pdo-etat--validee  { background:rgba(16,185,129,.10); color:#047857; }
    .pdo-etat--refusee  { background:rgba(220,38,38,.10); color:#b91c1c; }

    .pdo-droite { display:flex; flex-direction:column; align-items:flex-end; gap:.3rem; flex-shrink:0; }

    .pdo-lien {
        background:none; border:none; padding:0; cursor:pointer;
        font-size:.7rem; color:#64748b; text-decoration:underline;
    }
    .pdo-lien:hover:not(:disabled) { color:#0453cb; }
    .pdo-lien:disabled { opacity:.5; cursor:wait; }

    .pdo-actions-depot { display:inline-flex; gap:.3rem; }
    .pdo-mini {
        font-size:.66rem; font-weight:600; padding:.15rem .45rem;
        border-radius:5px; border:1px solid transparent; cursor:pointer;
    }
    .pdo-mini--ok { background:rgba(16,185,129,.10); color:#047857; border-color:rgba(16,185,129,.28); }
    .pdo-mini--non { background:rgba(220,38,38,.08); color:#b91c1c; border-color:rgba(220,38,38,.25); }
    .pdo-mini:disabled { opacity:.5; cursor:wait; }

    .pdo-modal {
        position:fixed; inset:0; z-index:1080;
        background:rgba(15,23,42,.45); backdrop-filter:blur(2px);
        display:flex; align-items:center; justify-content:center; padding:1rem;
    }
    .pdo-modal-boite {
        background:#fff; border-radius:14px; padding:1.25rem;
        width:100%; max-width:440px;
        box-shadow:0 20px 60px rgba(15,23,42,.25);
    }
    .pdo-modal-titre { font-size:1rem; font-weight:700; color:#1e293b; }
    .pdo-modal-aide { font-size:.78rem; color:#64748b; margin:.3rem 0 .75rem; line-height:1.5; }
    .pdo-modal-champ {
        width:100%; border:1px solid #cbd5e1; border-radius:8px;
        padding:.55rem .7rem; font-size:.85rem; font-family:inherit; resize:vertical;
    }
    .pdo-modal-champ:focus { outline:none; border-color:#0453cb; box-shadow:0 0 0 3px rgba(4,83,203,.10); }
    .pdo-modal-pied { display:flex; justify-content:flex-end; gap:.5rem; margin-top:.9rem; }
    .pdo-modal-btn {
        padding:.45rem .9rem; border-radius:8px; font-size:.8rem; font-weight:600;
        border:1px solid #e2e8f0; background:#fff; color:#475569; cursor:pointer;
    }
    .pdo-modal-btn--valider { background:#0453cb; border-color:#0453cb; color:#fff; }
    .pdo-modal-btn:disabled { opacity:.5; cursor:not-allowed; }

    [x-cloak] { display:none !important; }

    @@media (max-width: 576px) {
        .pdo-ligne { flex-wrap:wrap; }
        .pdo-droite { flex-direction:row; align-items:center; width:100%; justify-content:space-between; }
    }
</style>
@endpush

@push('scripts')
<script>
if (typeof window.dossierPieces !== 'function') {
    window.dossierPieces = function () {
        return {
            lignes: [],
            synthese: { complet: true, manquantes: 0 },
            relecture: false,
            peutSuivre: false,
            urls: {},
            occupe: null,

            motifOuvert: false,
            motifTitre: '',
            motifAide: '',
            motifTexte: '',
            motifLigne: null,
            motifDepot: null,

            init() {
                // La charge passe par un data-attribute plutot que par une
                // interpolation dans x-data : Blade et Alpine se disputent les
                // accolades, et le compilateur perd le compte.
                const brut = this.$root.dataset.pdo || '{}';
                const charge = JSON.parse(brut);
                this.appliquer(charge);
                this.peutSuivre = !!charge.peutSuivre;
                this.urls = charge.urls || {};
            },

            appliquer(charge) {
                if (Array.isArray(charge.lignes)) this.lignes = charge.lignes;
                if (charge.synthese) this.synthese = charge.synthese;
                if (typeof charge.relecture !== 'undefined') this.relecture = !!charge.relecture;
            },

            entetes() {
                return {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                };
            },

            async appeler(url, methode, corps) {
                const reponse = await fetch(url, {
                    method: methode,
                    headers: this.entetes(),
                    body: corps ? JSON.stringify(corps) : undefined,
                });
                const donnees = await reponse.json().catch(() => ({}));
                if (!reponse.ok) {
                    const premiere = donnees.errors ? Object.values(donnees.errors)[0][0] : null;
                    throw new Error(premiere || donnees.message || 'Erreur ' + reponse.status);
                }
                return donnees;
            },

            async agir(ligne, url, methode, corps) {
                this.occupe = ligne.piece_id;
                try {
                    const donnees = await this.appeler(url, methode, corps);
                    this.appliquer(donnees);
                    this.dire('success', donnees.message || 'Enregistré.');
                } catch (erreur) {
                    this.dire('error', erreur.message);
                    // On ne touche a rien : l'etat affiche reste celui du
                    // serveur, et la case revient d'elle-meme a sa valeur.
                    this.lignes = [...this.lignes];
                } finally {
                    this.occupe = null;
                }
            },

            basculer(ligne, coche) {
                const url = this.urls.base + '/' + ligne.piece_id;
                return this.agir(ligne, url, coche ? 'POST' : 'DELETE', {});
            },

            demanderEcart(ligne) {
                this.ouvrirMotif({
                    titre: 'Cette pièce ne s’applique pas',
                    aide: 'Dites pourquoi elle ne concerne pas cet étudiant cette année. Six mois plus tard, ce motif est la seule chose qui distinguera une dispense décidée d’un dossier qu’on a renoncé à réclamer.',
                    ligne: ligne,
                    depot: null,
                });
            },

            reintegrer(ligne) {
                return this.agir(ligne, this.urls.base + '/' + ligne.piece_id + '/ecarter', 'DELETE', {});
            },

            demanderRefus(ligne, depot) {
                this.ouvrirMotif({
                    titre: 'Refuser cette pièce',
                    aide: 'Dites pourquoi. L’étudiant devra la redéposer, et il a le droit de savoir ce qui n’allait pas.',
                    ligne: ligne,
                    depot: depot,
                });
            },

            ouvrirMotif(demande) {
                this.motifTitre = demande.titre;
                this.motifAide = demande.aide;
                this.motifLigne = demande.ligne;
                this.motifDepot = demande.depot;
                this.motifTexte = '';
                this.motifOuvert = true;
                this.$nextTick(() => this.$refs.champMotif?.focus());
            },

            fermerMotif() {
                this.motifOuvert = false;
                this.motifLigne = null;
                this.motifDepot = null;
            },

            confirmerMotif() {
                const motif = this.motifTexte.trim();
                if (motif.length < 3) return;

                const ligne = this.motifLigne;
                const depot = this.motifDepot;
                this.fermerMotif();

                return depot
                    ? this.decider(ligne, depot, 'refusee', motif)
                    : this.agir(ligne, this.urls.base + '/' + ligne.piece_id + '/ecarter', 'POST', { motif: motif });
            },

            decider(ligne, depot, etat, motif) {
                const url = this.urls.decision + '/' + depot.id + '/decision';
                return this.agir(ligne, url, 'POST', { etat: etat, motif: motif || null });
            },

            dire(type, message) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: message } }));
            },
        };
    };
}
</script>
@endpush
@endif
