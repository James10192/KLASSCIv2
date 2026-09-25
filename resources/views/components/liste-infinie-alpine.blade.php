{{--
    Bas de liste d'une liste qu'Alpine dessine (journal d'audit, corbeille).
    Se place dans un composant qui a mixe ListeInfinie.alpine() (liste-infinie.js) :
    memes classes, meme texte et memes etats que x-liste-infinie.
--}}
<div {{ $attributes->merge(['class' => 'li-bas']) }} x-ref="basDeListe" x-show="!loading && !liVide()" x-cloak :data-etat="liEtat()">
    <span class="li-compteur" aria-live="polite" x-text="compteurBas()"></span>
    <button type="button" class="li-plus" x-show="liAPlus || liErreur" :disabled="liSuite"
            @click="chargerSuite()" x-text="liErreur ? 'Réessayer' : 'Charger la suite'"></button>
</div>
