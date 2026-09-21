{{--
    Le suivi des notes reçues, posé là où l'action se décide.

    Le calcul vit derrière une seule adresse ; ce bandeau ne fait que la lire.
    C'est la condition pour que la page de saisie, celle des bulletins et celle
    du pilotage annoncent le même chiffre : il n'existe qu'un endroit où il est
    calculé, et qu'un endroit où il est affiché.

    Le bandeau ne s'affiche pas du tout si l'utilisateur n'a pas le droit de lire
    le suivi (403) : un message d'erreur sur un droit manquant n'apprend rien à
    quelqu'un qui ne peut rien y faire. Il ne s'affiche pas non plus tant
    qu'aucune classe n'est choisie.

    LE DROIT SE LIT AU PLURIEL. La garde ne connaissait que `academic_health.view`,
    que l'enseignant n'a pas : il a `academic_health.view_own`. Le bandeau était
    donc invisible pour la seule personne qui saisit les notes — y compris dans
    la fenêtre de saisie, où il avait pourtant été posé. Le périmètre reste tenu
    par l'endpoint, qui refuse en 403 une classe hors de celui de l'acteur.

    @param int|null    $classeId   null si la page fait choisir la classe
    @param int|null    $anneeId
    @param string      $periode    semestre1 | semestre2 | annuel
    @param bool        $replie     détail plié par défaut (true)
    @param string|null $titre      surcharge du libellé
--}}
@canany(['academic_health.view', 'academic_health.view_own'])
@php
    $_cvnClasseId = $classeId ?? null;
    $_cvnAnneeId = $anneeId ?? null;
    $_cvnPeriode = $periode ?? 'semestre1';
    // Gabarit d'adresse : la classe y est un marqueur, pour que le bandeau
    // puisse suivre un sélecteur sans recharger la page.
    $_cvnModele = route('esbtp.pilotage-academique.classes.couverture', ['classe' => '__CLASSE__']);
    $_cvnPilotage = \Illuminate\Support\Facades\Route::has('esbtp.pilotage-academique.index')
        ? route('esbtp.pilotage-academique.index')
        : null;
    $_cvnConfig = [
        'modele' => $_cvnModele,
        'urlPilotage' => $_cvnPilotage,
        'replie' => (bool) ($replie ?? true),
        'classeId' => $_cvnClasseId ? (int) $_cvnClasseId : null,
        'anneeId' => $_cvnAnneeId ? (int) $_cvnAnneeId : null,
        'periode' => $_cvnPeriode,
    ];
    $_cvnTitre = $titre ?? 'Notes reçues';
@endphp

<div class="cvn"
     x-data="couvertureNotes(@js($_cvnConfig))"
     x-show="!interdit && pret()"
     x-cloak>

    {{-- Chargement : une ligne discrète, pas un bloc qui saute --}}
    <template x-if="chargement && !donnees">
        <div class="cvn-bloc cvn--neutre">
            <div class="cvn-ligne">
                <i class="fas fa-circle-notch fa-spin"></i>
                <span>Suivi des notes…</span>
            </div>
        </div>
    </template>

    {{-- Échec : on le dit, sans bloquer la page --}}
    <template x-if="!donnees && erreur">
        <div class="cvn-bloc cvn--neutre">
            <div class="cvn-ligne">
                <i class="fas fa-plug-circle-xmark"></i>
                <span x-text="erreur"></span>
                <button type="button" class="cvn-lien" @click="charger()">Réessayer</button>
            </div>
        </div>
    </template>

    <template x-if="donnees">
        <div class="cvn-bloc" :class="ton()">
            <div class="cvn-ligne">
                <i class="fas" :class="icone()"></i>
                <div class="cvn-texte">
                    <strong>{{ $_cvnTitre }}</strong>
                    <span x-text="phrase()"></span>
                </div>
                <div class="cvn-periodes" role="tablist" aria-label="Période du suivi">
                    <button type="button" :class="{ 'is-active': periode === 'annuel' }" @click="changerPeriode('annuel')">Année</button>
                    <button type="button" :class="{ 'is-active': periode === 'semestre1' }" @click="changerPeriode('semestre1')">S1</button>
                    <button type="button" :class="{ 'is-active': periode === 'semestre2' }" @click="changerPeriode('semestre2')">S2</button>
                </div>

                <template x-if="aUneBarre()">
                    <div class="cvn-jauge" :title="`${pourcentage()} % des notes attendues sont reçues`">
                        <div class="cvn-jauge-piste">
                            <div class="cvn-jauge-barre" :style="`width:${pourcentage()}%`"></div>
                        </div>
                        <strong x-text="`${pourcentage()} %`"></strong>
                    </div>
                </template>

                <template x-if="prioritaires().length > 0">
                    <button type="button" class="cvn-lien" @click="basculer()">
                        <span x-show="replie">Voir quoi relancer</span>
                        <span x-show="!replie" x-cloak>Masquer</span>
                    </button>
                </template>
            </div>

            <div class="cvn-filtres" aria-label="Filtrer les matières du suivi">
                <button type="button" :class="{ 'is-active': filtre === 'tout' }" @click="filtre = 'tout'">Toutes <span x-text="matieresParCategorie('tout').length"></span></button>
                <button type="button" :class="{ 'is-active': filtre === 'sans_note' }" @click="filtre = 'sans_note'">Évaluées sans note <span x-text="compteur('sans_note')"></span></button>
                <button type="button" :class="{ 'is-active': filtre === 'partielle' }" @click="filtre = 'partielle'">Incomplètes <span x-text="compteur('partielle')"></span></button>
                <button type="button" :class="{ 'is-active': filtre === 'sans_evaluation' }" @click="filtre = 'sans_evaluation'">Sans évaluation <span x-text="compteur('sans_evaluation')"></span></button>
                <button type="button" :class="{ 'is-active': filtre === 'hors_maquette' }" @click="filtre = 'hors_maquette'">Hors maquette <span x-text="compteur('hors_maquette')"></span></button>
                <button type="button" :class="{ 'is-active': filtre === 'complete' }" @click="filtre = 'complete'">Complètes <span x-text="compteur('complete')"></span></button>
            </div>

            {{-- Le doublon probable, HORS du détail repliable : il ne dépend pas
                 d'une note manquante. Deux dossiers entièrement notés pour la
                 même personne ne font bouger aucun compteur et produisent
                 pourtant deux bulletins — c'est le cas qu'on ne verrait jamais
                 si ce bloc restait enfermé sous « Voir quoi relancer ». --}}
            <template x-if="doublons().length > 0">
                <div class="cvn-doublons">
                    <template x-for="d in doublons()" :key="d.cle">
                        <div class="cvn-doublon">
                            <i class="fas fa-user-group"></i>
                            <span>
                                <strong x-text="d.sans"></strong> et
                                <strong x-text="d.avec"></strong>
                                portent presque le même nom dans cette classe.
                                S'il s'agit de la même personne, une note peut avoir été
                                saisie sur l'autre dossier — et un bulletin sera édité
                                pour chacun des deux.
                            </span>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Le détail : quelles matières, et qui relancer --}}
            <div class="cvn-detail" x-show="!replie && matieresAffichees().length > 0" x-cloak x-transition.opacity>
                <div class="cvn-detail-titre" x-text="titreCategorie(filtre)"></div>
                <template x-for="m in matieresAffichees().slice(0, 8)" :key="`cvn-${m.id || m.name}`">
                    <div class="cvn-matiere">
                        <div class="cvn-matiere-nom">
                            <span x-text="m.name"></span>
                            <span class="cvn-chip" x-text="libelleStatut(m)"></span>
                        </div>
                        <div class="cvn-matiere-qui">
                            <template x-if="contact(m)">
                                {{-- Le nom venu du bulletin n'a pas de numéro : le téléphone
                                     reste conditionnel, et la source se lit au survol. --}}
                                <span :title="contact(m).source === 'bulletin'
                                        ? 'Nom saisi dans « Éditer les professeurs » du bulletin'
                                        : 'Enseignant principal au planning général'">
                                    <i class="fas fa-user"></i>
                                    <span x-text="contact(m).name"></span>
                                    <template x-if="contact(m).phone">
                                        <a :href="`tel:${contact(m).phone}`" x-text="contact(m).phone"></a>
                                    </template>
                                </span>
                            </template>
                            <template x-if="!contact(m)">
                                {{-- Ni le planning ni le bulletin ne répondent, ou ils se
                                     contredisent : on ne désigne personne au hasard. --}}
                                <span class="cvn-muet" title="Renseignez l'enseignant dans le planning général, ou dans « Éditer les professeurs ».">
                                    Aucun enseignant au planning ni dans la configuration des bulletins
                                </span>
                            </template>
                            <template x-if="m.saisie_url && m.statut !== 'programmee'">
                                <button type="button" class="cvn-action" @click="ouvrirSaisie(m)">
                                    <i class="fas fa-pen-to-square"></i> Ouvrir la saisie
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="matieresAffichees().length > 8">
                    <div class="cvn-muet" x-text="`et ${matieresAffichees().length - 8} autre(s) matière(s)`"></div>
                </template>

                <template x-if="urlPilotage">
                    <a class="cvn-lien" :href="urlPilotage">Ouvrir le pilotage académique</a>
                </template>
            </div>
        </div>
    </template>
</div>

<style>
.cvn { margin-bottom: 1rem; }
.cvn-bloc { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: .7rem .9rem; }
.cvn-bloc.cvn--ok { border-color: rgba(16,185,129,.35); background: rgba(16,185,129,.05); }
.cvn-bloc.cvn--alerte { border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.05); }
.cvn-bloc.cvn--neutre { border-color: #e2e8f0; background: #f8fafc; }
.cvn-ligne { display: flex; align-items: center; gap: .65rem; flex-wrap: wrap; font-size: .82rem; color: #1e293b; }
.cvn-ligne > i { font-size: .95rem; color: #0453cb; flex-shrink: 0; }
.cvn--ok .cvn-ligne > i { color: #10b981; }
.cvn--alerte .cvn-ligne > i { color: #b45309; }
.cvn-texte { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.cvn-texte strong { font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: #64748b; }
.cvn-periodes, .cvn-filtres { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
.cvn-periodes { padding: .18rem; border: 1px solid #dbe5f2; background: #f8fafc; border-radius: 8px; }
.cvn-periodes button, .cvn-filtres button { border: 1px solid #dbe5f2; border-radius: 999px; background: #fff; color: #475569; padding: .23rem .52rem; font-size: .7rem; font-weight: 700; cursor: pointer; }
.cvn-periodes button { border: 0; border-radius: 6px; }
.cvn-periodes button.is-active, .cvn-filtres button.is-active { background: #0453cb; border-color: #0453cb; color: #fff; }
.cvn-filtres { margin-top: .65rem; padding-top: .6rem; border-top: 1px solid rgba(15,23,42,.08); }
.cvn-filtres span { display: inline-flex; min-width: 1.15rem; justify-content: center; margin-left: .18rem; padding: 0 .2rem; border-radius: 999px; background: rgba(4,83,203,.09); color: #0453cb; }
.cvn-filtres button.is-active span { background: rgba(255,255,255,.18); color: #fff; }
.cvn-jauge { display: flex; align-items: center; gap: .5rem; min-width: 150px; }
.cvn-jauge-piste { flex: 1; height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
.cvn-jauge-barre { height: 100%; border-radius: 999px; background: #0453cb; transition: width .25s ease; }
.cvn--ok .cvn-jauge-barre { background: #10b981; }
.cvn--alerte .cvn-jauge-barre { background: #f59e0b; }
.cvn-jauge strong { font-size: .8rem; font-weight: 700; color: #1e293b; }
.cvn-lien { background: none; border: none; padding: 0; color: #0453cb; font-size: .78rem; font-weight: 600; cursor: pointer; text-decoration: underline; }
.cvn-detail { margin-top: .7rem; padding-top: .7rem; border-top: 1px solid rgba(15,23,42,.08); display: flex; flex-direction: column; gap: .45rem; }
.cvn-detail-titre { color: #475569; font-size: .73rem; font-weight: 800; text-transform: uppercase; letter-spacing: .3px; }
.cvn-matiere { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; font-size: .8rem; }
.cvn-matiere-nom { display: flex; align-items: center; gap: .5rem; font-weight: 600; color: #1e293b; }
.cvn-chip { font-size: .66rem; font-weight: 700; text-transform: uppercase; padding: .12rem .45rem; border-radius: 5px; background: rgba(4,83,203,.08); color: #0453cb; border: 1px solid rgba(4,83,203,.2); }
.cvn-matiere-qui { font-size: .76rem; color: #64748b; display: flex; align-items: center; gap: .4rem; }
.cvn-action { margin-left: auto; border: 1px solid rgba(4,83,203,.28); border-radius: 6px; padding: .25rem .45rem; color: #0453cb; background: #fff; text-decoration: none; font-size: .72rem; font-weight: 700; white-space: nowrap; }
.cvn-matiere-qui a { color: #0453cb; text-decoration: none; }
.cvn-muet { color: #94a3b8; font-size: .76rem; }
.cvn-doublons { display: flex; flex-direction: column; gap: .35rem; margin-top: .6rem; }
.cvn-doublon { display: flex; align-items: flex-start; gap: .5rem; font-size: .78rem; color: #b45309; background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.25); border-radius: 8px; padding: .45rem .6rem; }
.cvn-doublon > i { margin-top: .15rem; flex-shrink: 0; }
.cvn-doublon strong { color: #92400e; }
@@media (max-width: 576px) {
    .cvn-jauge { width: 100%; }
    .cvn-matiere { flex-direction: column; align-items: flex-start; gap: .2rem; }
}
</style>

@include('esbtp.partials._couverture-notes-script')
@endcanany
