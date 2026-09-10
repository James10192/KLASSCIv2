{{--
    Bandeau « matières prévues » : dit si les semestres de ce combo ont été
    validés, et propose de les reprendre du planning général.

    Tant que le combo n'est pas validé, rien ne change nulle part : le bulletin
    et la couverture des notes se comportent exactement comme avant.
--}}
<div class="mtc-maquette">
    <div class="mtc-maquette-txt">
        <template x-if="maquette.renseignee">
            <span>
                <strong>Matières prévues</strong> :
                <span x-text="maquette.semestre_1"></span> au semestre 1,
                <span x-text="maquette.semestre_2"></span> au semestre 2.
                <small>Une matière sans semestre est prévue aux deux.</small>
            </span>
        </template>
        <template x-if="!maquette.renseignee">
            <span>
                <strong>Semestres non renseignés</strong> pour cette filière et ce niveau.
                <small>Indiquez à quel semestre chaque matière est prévue. Cette saisie prépare le suivi des notes reçues et la composition du bulletin ; elle ne les modifie pas encore.</small>
            </span>
        </template>
    </div>

    <template x-if="planning">
        <button type="button" class="mtc-chip" @click="ouvrirApercuPlanning()" :disabled="saving">
            <i class="fas fa-diagram-project"></i>
            D'après le planning général :
            <span x-text="planning.lignes"></span> ligne(s) · voir
        </button>
    </template>
    <template x-if="!planning">
        <span class="mtc-chip mtc-chip--muted">
            <i class="fas fa-diagram-project"></i> Aucun planning général pour l'année en cours
        </span>
    </template>

    <button type="button" class="mtc-btn" @click="save(true)" :disabled="saving">
        <i class="fas fa-check"></i> Valider les semestres
    </button>
</div>

{{-- Aperçu de l'import : montre ce qui changerait, avant d'écrire quoi que ce soit. --}}
<div class="mtc-modal" x-show="apercuOuvert" x-cloak
     @keydown.escape.window="fermerApercu()"
     role="dialog" aria-modal="true" aria-labelledby="mtc-apercu-titre">
    <div class="mtc-modal-box" @click.outside="fermerApercu()">
        <div class="mtc-modal-head">
            <h2 id="mtc-apercu-titre">Reprendre les semestres du planning général</h2>
            <p>
                <span x-text="apercu.changements"></span> matière(s) changeraient.
                Le planning est annuel, la maquette ne l'est pas : ce que vous appliquez ici vaut pour toutes les années.
            </p>
        </div>
        <div class="mtc-modal-body">
            <template x-if="apercu.lignes.length === 0">
                <div class="mtc-empty"><i class="fas fa-inbox"></i>Rien à reprendre.</div>
            </template>
            <template x-for="ligne in apercu.lignes" :key="ligne.matiere_id">
                <div class="mtc-diff-row">
                    <span x-text="ligne.name"></span>
                    <span :class="ligne.change ? 'mtc-diff-move' : 'mtc-diff-same'"
                          x-text="libelleSemestre(ligne.actuel) + ' → ' + libelleSemestre(ligne.propose)"></span>
                </div>
            </template>
            <template x-if="apercu.hors_maquette.length > 0">
                <div class="mtc-banner mtc-banner--info" style="margin-top:.8rem">
                    <i class="fas fa-info-circle"></i>
                    <span>
                        <span x-text="apercu.hors_maquette.length"></span> matière(s) du planning ne sont pas rattachées à cette filière et ce niveau.
                        Elles ne seront pas ajoutées : rattachez-les d'abord depuis la fiche matière.
                    </span>
                </div>
            </template>
        </div>
        <div class="mtc-modal-foot">
            <button type="button" class="mtc-mini" @click="fermerApercu()">Annuler</button>
            <button type="button" class="mtc-btn" @click="appliquerPlanning()" :disabled="saving">
                <span x-show="!saving"><i class="fas fa-download"></i> Appliquer</span>
                <span x-show="saving" x-cloak>Application…</span>
            </button>
        </div>
    </div>
</div>
