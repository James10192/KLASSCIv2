{{-- Fenetres courtes : reinscrire, rejeter, proposer un creneau. --}}

<div class="dmi-voile" x-show="fenetre === 'reinscrire'" x-cloak x-transition.opacity x-on:keydown.escape.window="fenetre === 'reinscrire' && fermerFenetre()">
    <div class="dmi-fenetre" role="dialog" aria-modal="true" aria-labelledby="dmi-reins-titre" x-on:click.outside="fermerFenetre()">
        <div class="dmi-f-tete">
            <div><span class="dmi-p-type">Réinscription</span><h2 id="dmi-reins-titre">Réinscrire <span x-text="dossier?.nom"></span></h2></div>
            <button type="button" class="dmi-fermer" x-on:click="fermerFenetre()" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="dmi-f-corps">
            <div class="dmi-champ" :class="reins.erreurs.classe_id ? 'is-erreur' : ''">Classe de l'année
                @include('esbtp.admissions.demandes._picker-classe', ['modele' => 'reins.classe_id', 'liste' => 'classes', 'bloquer' => false])
                <span class="dmi-champ-aide">Proposée : la classe souhaitée par l'étudiant. C'est l'école qui affecte.</span>
                <span class="dmi-champ-erreur" x-text="reins.erreurs.classe_id" x-show="reins.erreurs.classe_id"></span>
            </div>
            <div class="dmi-champ" style="margin-top:.9rem" :class="reins.erreurs.decision ? 'is-erreur' : ''">Décision du conseil
                <div class="dmi-choix" role="radiogroup" aria-label="Décision">
                    <template x-for="opt in Object.entries(cfg.decisions || {})" :key="opt[0]">
                        <button type="button" role="radio" :aria-checked="reins.decision === opt[0]" :class="reins.decision === opt[0] ? 'is-actif' : ''" x-on:click="reins.decision = opt[0]" x-text="opt[1]"></button>
                    </template>
                </div>
                <span class="dmi-champ-erreur" x-text="reins.erreurs.decision" x-show="reins.erreurs.decision"></span>
            </div>
            <label class="dmi-champ" style="margin-top:.9rem">Observations <span class="dmi-champ-aide">(facultatif)</span>
                <textarea x-model="reins.observations" maxlength="1000"></textarea>
            </label>
            <p class="dmi-p-note" style="margin:.75rem 0 0">Les frais de l'année sont générés, le solde précédent est reporté, et le rendez-vous de la famille est clos.</p>
            <p class="dmi-champ-erreur" x-show="reins.message" x-text="reins.message" role="alert"></p>
        </div>
        <div class="dmi-f-pied">
            <button type="button" class="dmi-btn dmi-btn--ghost" x-on:click="fermerFenetre()">Annuler</button>
            <button type="button" class="dmi-btn dmi-btn--primary" x-on:click="reinscrire()" :disabled="occupe || !reins.classe_id || !reins.decision">
                <span x-show="!occupe"><i class="fas fa-user-check"></i> Réinscrire</span><span x-show="occupe" x-cloak><i class="fas fa-circle-notch fa-spin"></i> Réinscription…</span>
            </button>
        </div>
    </div>
</div>

<div class="dmi-voile" x-show="fenetre === 'rejeter'" x-cloak x-transition.opacity x-on:keydown.escape.window="fenetre === 'rejeter' && fermerFenetre()">
    <div class="dmi-fenetre" role="dialog" aria-modal="true" aria-labelledby="dmi-rej-titre" x-on:click.outside="fermerFenetre()">
        <div class="dmi-f-tete">
            <div><span class="dmi-p-type" style="color:#b91c1c">Rejet</span><h2 id="dmi-rej-titre">Rejeter la demande de <span x-text="dossier?.nom"></span></h2></div>
            <button type="button" class="dmi-fermer" x-on:click="fermerFenetre()" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="dmi-f-corps">
            <label class="dmi-champ" :class="rejet.erreur ? 'is-erreur' : ''">Motif, tel qu'il sera expliqué à la famille
                <textarea x-model="rejet.motif" maxlength="1000" placeholder="Ex. : dossier incomplet, relevé de notes du bac manquant"></textarea>
                <span class="dmi-champ-aide" x-text="rejet.motif.trim().length < 10 ? 'Au moins 10 caractères (' + rejet.motif.trim().length + '/10).' : 'Motif gardé avec le dossier.'"></span>
                <span class="dmi-champ-erreur" x-show="rejet.erreur" x-text="rejet.erreur"></span>
            </label>
            <p class="dmi-p-note" style="margin:.75rem 0 0">Si la famille a un rendez-vous à venir, son créneau est libéré pour une autre.</p>
        </div>
        <div class="dmi-f-pied">
            <button type="button" class="dmi-btn dmi-btn--ghost" x-on:click="fermerFenetre()">Annuler</button>
            <button type="button" class="dmi-btn dmi-btn--danger" x-on:click="rejeter()" :disabled="occupe || rejet.motif.trim().length < 10">
                <span x-show="!occupe">Rejeter la demande</span><span x-show="occupe" x-cloak><i class="fas fa-circle-notch fa-spin"></i> Rejet…</span>
            </button>
        </div>
    </div>
</div>

<div class="dmi-voile" x-show="fenetre === 'rendez-vous'" x-cloak x-transition.opacity x-on:keydown.escape.window="fenetre === 'rendez-vous' && fermerFenetre()">
    <div class="dmi-fenetre" role="dialog" aria-modal="true" aria-labelledby="dmi-rdv-titre" x-on:click.outside="fermerFenetre()">
        <div class="dmi-f-tete">
            <div><span class="dmi-p-type">Rendez-vous</span><h2 id="dmi-rdv-titre">Proposer un créneau à <span x-text="dossier?.nom"></span></h2></div>
            <button type="button" class="dmi-fermer" x-on:click="fermerFenetre()" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="dmi-f-corps">
            <div x-show="rdv.chargement" class="dmi-p-note"><i class="fas fa-circle-notch fa-spin"></i> Recherche des créneaux libres…</div>
            <div x-show="!rdv.chargement && !rdv.creneaux.length" class="dmi-encart dmi-encart--alerte"><i class="fas fa-calendar-xmark"></i><div>Aucun créneau libre. Générez ou ouvrez des créneaux depuis le planning des rendez-vous.</div></div>
            <div class="dmi-picker-liste" style="max-height:360px" role="listbox" aria-label="Créneaux libres">
                <template x-for="c in rdv.creneaux" :key="c.id">
                    <button type="button" class="dmi-picker-option" :class="rdv.choix === c.id ? 'is-focus' : ''" role="option" :aria-selected="rdv.choix === c.id" x-on:click="rdv.choix = c.id">
                        <span><strong x-text="c.libelle"></strong><small x-text="c.heure"></small></span>
                        <span class="dmi-p-note" x-text="c.libres + ' place' + (c.libres > 1 ? 's' : '')"></span>
                    </button>
                </template>
            </div>
        </div>
        <div class="dmi-f-pied">
            <button type="button" class="dmi-btn dmi-btn--ghost" x-on:click="fermerFenetre()">Annuler</button>
            <button type="button" class="dmi-btn dmi-btn--primary" x-on:click="fixerRendezVous()" :disabled="occupe || !rdv.choix">
                <span x-show="!occupe"><i class="fas fa-calendar-check"></i> Fixer et convoquer</span><span x-show="occupe" x-cloak><i class="fas fa-circle-notch fa-spin"></i> Envoi…</span>
            </button>
        </div>
    </div>
</div>
