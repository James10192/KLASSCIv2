{{--
    Vérifier et annoter une capture avant de la joindre. La toile et ses outils
    reprennent les classes sp-* de la fenêtre classique (lanceur.blade.php) :
    l'éditeur (js/support/capture.js) est le même.
--}}
<div class="nsp-corps nsp-corps--capture" x-show="vue === 'capture'" x-cloak>
    <p class="nsp-question" tabindex="-1" data-nsp-focus="capture">Vérifiez la capture avant de la joindre.</p>
    <p class="nsp-note"><i class="fas fa-eye-slash" aria-hidden="true"></i>
        <span x-text="captureProvenance === 'ecran'
            ? 'Les champs de saisie et les valeurs des listes sont déjà masqués sur une capture de la page. Masquez ce qui reste de sensible : un nom, un montant, une photo.'
            : 'Masquez ce qui est personnel : un nom, un montant, une photo.'"></span></p>
    <div class="sp-outils" role="toolbar" aria-label="Outils d'annotation">
        <template x-for="o in [['cadre','far fa-square','Cadre'],['fleche','fas fa-arrow-right-long','Flèche'],['masquer','fas fa-eye-slash','Masquer'],['texte','fas fa-font','Texte']]" x-bind:key="o[0]">
            <button type="button" class="sp-outil" x-bind:aria-pressed="captureOutil === o[0] ? 'true' : 'false'" x-on:click="choisirOutil(o[0])">
                <i x-bind:class="o[1]" aria-hidden="true"></i><span x-text="o[2]"></span>
            </button>
        </template>
        <button type="button" class="sp-outil sp-outil--droite" x-bind:aria-pressed="captureReelle ? 'true' : 'false'" x-on:click="basculerTaille()">
            <i class="fas fa-magnifying-glass-plus" aria-hidden="true"></i>Taille réelle
        </button>
        <button type="button" class="sp-outil" x-bind:disabled="!captureAnnulable" x-on:click="annulerTrait()">
            <i class="fas fa-rotate-left" aria-hidden="true"></i>Annuler
        </button>
    </div>
    <div class="sp-toile-cadre" x-ref="toile"></div>
    <p class="nsp-erreur" x-show="captureMessage" role="alert"><span x-text="captureMessage"></span></p>
    <div class="nsp-actions">
        <button type="button" class="nsp-btn nsp-btn--secondaire" x-on:click="abandonnerCapture()">Ne pas joindre</button>
        <button type="button" class="nsp-btn nsp-btn--primaire" x-on:click="retenirCapture()"><i class="fas fa-paperclip" aria-hidden="true"></i>Joindre</button>
    </div>
</div>
