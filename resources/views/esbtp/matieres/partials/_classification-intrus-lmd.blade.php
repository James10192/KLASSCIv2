{{--
    Les éléments constitutifs LMD posés par erreur dans cette maquette BTS.

    Normalement ce bloc ne s'affiche jamais. Quand il s'affiche, c'est qu'un
    écran BTS a posé la ligne sans garde — cas mesuré sur esbtp-abidjan, où
    « Alimentation en eau et QTE » portait Travaux Publics 2e année et sortait
    donc sur les bulletins de la classe.

    Ils sont tenus HORS de la liste des matières : ni décomptés, ni ordonnables,
    ni classables. La seule action qui ait du sens sur eux est le retrait — et
    c'est précisément ce qui manquait : la ligne sortait au bulletin sans être
    visible nulle part où on aurait pu l'en retirer.
--}}
<div class="mtc-intrus" x-show="loaded && intrusLmd.length > 0" x-cloak>
    <div class="mtc-intrus-head">
        <i class="fas fa-triangle-exclamation"></i>
        <div>
            <strong>
                <span x-text="intrusLmd.length"></span>
                élément(s) LMD dans cette maquette BTS
            </strong>
            <small>
                Ce sont des éléments constitutifs d'unités d'enseignement LMD. Ils n'ont
                rien à faire dans une maquette BTS et sortent sur les bulletins de ce
                niveau. Retirez-les ici ; ils restent gérés dans le module LMD.
            </small>
        </div>
    </div>

    <template x-for="m in intrusLmd" :key="'lmd-' + m.matiere_id">
        <div class="mtc-intrus-row">
            <span class="mtc-row-name" x-text="m.name"></span>
            <span class="mtc-row-code" x-show="m.code" x-text="m.code"></span>
            <button type="button" class="mtc-retirer" :disabled="saving"
                :aria-label="'Retirer ' + m.name + ' de la maquette'"
                title="Retirer de la maquette"
                @click="retirerDeLaMaquette(m)">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </template>
</div>
