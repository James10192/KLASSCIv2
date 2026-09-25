{{-- « Accepter et inscrire » : verifier, affecter, voir l'apercu, inscrire.
     Les donnees viennent de ESBTPDemandesInscriptionController::preparerInscription ;
     l'inscription part par ESBTPInscriptionController::store, en JSON. --}}
<div class="dmi-voile" x-show="fenetre === 'inscrire'" x-cloak x-transition.opacity
     x-on:keydown.escape.window="fenetre === 'inscrire' && fermerFenetre()">
    <div class="dmi-fenetre dmi-fenetre--large" role="dialog" aria-modal="true" aria-labelledby="dmi-ins-titre" x-on:click.outside="fermerFenetre()">
        <div class="dmi-f-tete">
            <div>
                <span class="dmi-p-type">Nouvelle inscription<span x-show="ins.prep?.candidature?.reference"> · demande <span class="dmi-mono" x-text="ins.prep?.candidature?.reference"></span></span></span>
                <h2 id="dmi-ins-titre">Accepter et inscrire <span x-text="ins.f.nom + ' ' + ins.f.prenoms"></span></h2>
            </div>
            <ol class="dmi-ins-etapes" aria-label="Étapes">
                <li :class="insIdentiteOk() ? 'is-ok' : ''"><span class="rond"><i class="fas fa-check" x-show="insIdentiteOk()"></i><span x-show="!insIdentiteOk()">1</span></span><span class="libelle">Vérifier</span></li>
                <li class="sep" aria-hidden="true"></li>
                <li :class="insAffectationOk() ? 'is-ok' : ''"><span class="rond"><i class="fas fa-check" x-show="insAffectationOk()"></i><span x-show="!insAffectationOk()">2</span></span><span class="libelle">Affecter</span></li>
                <li class="sep" aria-hidden="true"></li>
                <li :class="insPret() ? 'is-ok' : ''"><span class="rond"><i class="fas fa-check" x-show="insPret()"></i><span x-show="!insPret()">3</span></span><span class="libelle">Aperçu</span></li>
            </ol>
            <button type="button" class="dmi-fermer" x-on:click="fermerFenetre()" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="dmi-f-corps">
            <div x-show="ins.chargement" class="dmi-panneau-vide"><i class="fas fa-circle-notch fa-spin"></i>Préparation du dossier…</div>
            <div class="dmi-ins" x-show="!ins.chargement && ins.prep">
                <div>
                    <section class="dmi-ins-bloc">
                        <h3>1 · Identité déclarée par le candidat <small>Corrigez ce qui ne correspond pas à la pièce présentée.</small></h3>
                        <div class="dmi-grille">
                            <label class="dmi-champ" :class="ins.erreurs.nom ? 'is-erreur' : ''">Nom<input type="text" x-model="ins.f.nom" maxlength="100" autocomplete="off"><span class="dmi-champ-erreur" x-text="ins.erreurs.nom" x-show="ins.erreurs.nom"></span></label>
                            <label class="dmi-champ" :class="ins.erreurs.prenoms ? 'is-erreur' : ''">Prénoms<input type="text" x-model="ins.f.prenoms" maxlength="100" autocomplete="off"><span class="dmi-champ-erreur" x-text="ins.erreurs.prenoms" x-show="ins.erreurs.prenoms"></span></label>
                            <label class="dmi-champ" :class="ins.erreurs.date_naissance ? 'is-erreur' : ''">Date de naissance<input type="date" x-model="ins.f.date_naissance"><span class="dmi-champ-erreur" x-text="ins.erreurs.date_naissance" x-show="ins.erreurs.date_naissance"></span></label>
                            <label class="dmi-champ">Lieu de naissance<input type="text" x-model="ins.f.lieu_naissance" maxlength="100"></label>
                            <div class="dmi-champ" :class="ins.erreurs.sexe ? 'is-erreur' : ''">Sexe
                                <div class="dmi-choix" role="radiogroup" aria-label="Sexe">
                                    <button type="button" :class="ins.f.sexe === 'M' ? 'is-actif' : ''" x-on:click="ins.f.sexe = 'M'" :aria-checked="ins.f.sexe === 'M'" role="radio">Masculin</button>
                                    <button type="button" :class="ins.f.sexe === 'F' ? 'is-actif' : ''" x-on:click="ins.f.sexe = 'F'" :aria-checked="ins.f.sexe === 'F'" role="radio">Féminin</button>
                                </div>
                                <span class="dmi-champ-erreur" x-text="ins.erreurs.sexe" x-show="ins.erreurs.sexe"></span>
                            </div>
                            <label class="dmi-champ" :class="ins.erreurs.telephone ? 'is-erreur' : ''">Téléphone<input type="tel" x-model="ins.f.telephone" maxlength="20"><span class="dmi-champ-erreur" x-text="ins.erreurs.telephone" x-show="ins.erreurs.telephone"></span></label>
                        </div>
                        <label class="dmi-case" style="margin-top:.8rem" x-show="naissanceModifiee()">
                            <input type="checkbox" x-model="ins.f.candidature_naissance_confirmee">
                            <span>La date de naissance a été vérifiée sur une pièce : elle remplace celle de la candidature (<span x-text="dateFr(ins.prep?.identite?.date_naissance)"></span>).</span>
                        </label>
                    </section>

                    <section class="dmi-ins-bloc" :class="doublonsBloquants().length && !ins.f.duplicate_override ? 'dmi-ins-bloc--alerte' : ''">
                        <h3>
                            <span x-text="ins.prep?.doublons?.length ? 'Analyse des doublons · ' + ins.prep.doublons.length + ' fiche' + (ins.prep.doublons.length > 1 ? 's' : '') + ' proche' + (ins.prep.doublons.length > 1 ? 's' : '') : 'Analyse des doublons'"></span>
                            <small x-show="!ins.prep?.doublons?.length">Aucun étudiant proche n'existe déjà.</small>
                            <small x-show="doublonsBloquants().length && ins.f.duplicate_override" class="dmi-ok">Tranché : personne différente</small>
                        </h3>
                        <template x-for="d in (ins.prep?.doublons || [])" :key="d.id">
                            <div class="dmi-doublon">
                                <span class="dmi-av" x-text="initiales(d.full_name)"></span>
                                <div class="dmi-doublon-qui">
                                    <strong><span x-text="d.full_name"></span> <small x-text="'· ressemblance ' + Math.round(d.score) + ' %'"></small></strong>
                                    <small x-text="[d.date_naissance ? 'Né(e) le ' + d.date_naissance : '', d.matricule].filter(Boolean).join(' · ')"></small>
                                </div>
                                <a class="dmi-btn dmi-btn--ghost dmi-btn--sm" x-show="d.fiche && d.fiche !== '#'" :href="d.fiche" target="_blank" rel="noopener">C'est lui : ouvrir sa fiche</a>
                            </div>
                        </template>
                        <label class="dmi-case" style="margin-top:.7rem" x-show="doublonsBloquants().length">
                            <input type="checkbox" x-model="ins.f.duplicate_override">
                            <span>J'ai vérifié : c'est une <strong>autre personne</strong>, je crée un nouvel étudiant.</span>
                        </label>
                    </section>

                    <section class="dmi-ins-bloc">
                        <h3>2 · Affectation <small x-show="ins.prep?.candidature?.voeu" x-text="'Vœu du candidat : ' + ins.prep?.candidature?.voeu"></small></h3>
                        <div class="dmi-grille">
                            <div class="dmi-champ" :class="ins.erreurs.classe_id ? 'is-erreur' : ''">Classe
                                @include('esbtp.admissions.demandes._picker-classe', ['modele' => 'ins.f.classe_id', 'liste' => 'ins.prep?.classes'])
                                <span class="dmi-champ-erreur" x-text="ins.erreurs.classe_id" x-show="ins.erreurs.classe_id"></span>
                            </div>
                            <div class="dmi-champ" :class="ins.erreurs.matricule ? 'is-erreur' : ''">Matricule
                                <template x-if="ins.prep?.matricule_automatique">
                                    <div class="dmi-info" style="background:#f1f6ff"><strong>Attribué automatiquement</strong><span>à l'enregistrement, selon le réglage de l'école</span></div>
                                </template>
                                <template x-if="!ins.prep?.matricule_automatique">
                                    <input type="text" x-model="ins.f.matricule" maxlength="20" placeholder="Saisi par l'école" autocomplete="off">
                                </template>
                                <span class="dmi-champ-erreur" x-text="ins.erreurs.matricule" x-show="ins.erreurs.matricule"></span>
                            </div>
                        </div>
                        <div class="dmi-champ" style="margin-top:.75rem" x-show="ins.prep?.statut_etablissement_requis" :class="ins.erreurs.statut_etablissement ? 'is-erreur' : ''">Déjà inscrit dans l'établissement ?
                            <div class="dmi-choix" role="radiogroup" aria-label="Statut dans l'établissement">
                                <button type="button" role="radio" :aria-checked="ins.f.statut_etablissement === 'nouveau'" :class="ins.f.statut_etablissement === 'nouveau' ? 'is-actif' : ''" x-on:click="ins.f.statut_etablissement = 'nouveau'; chargerFrais()">Nouvel étudiant</button>
                                <button type="button" role="radio" :aria-checked="ins.f.statut_etablissement === 'ancien'" :class="ins.f.statut_etablissement === 'ancien' ? 'is-actif' : ''" x-on:click="ins.f.statut_etablissement = 'ancien'; chargerFrais()">Ancien de l'école</button>
                            </div>
                            <span class="dmi-champ-erreur" x-text="ins.erreurs.statut_etablissement" x-show="ins.erreurs.statut_etablissement"></span>
                        </div>
                    </section>

                    <section class="dmi-ins-bloc">
                        <h3>Parent ou tuteur <small x-show="ins.prep?.tuteur?.declare" x-text="'Saisi en un seul champ sur la candidature : « ' + ins.prep?.tuteur?.declare + ' »'"></small></h3>
                        <label class="dmi-case" style="margin-bottom:.75rem"><input type="checkbox" x-model="ins.sansTuteur"><span>Ne pas enregistrer de tuteur maintenant</span></label>
                        <div class="dmi-grille" x-show="!ins.sansTuteur">
                            <label class="dmi-champ" :class="ins.erreurs['parents.0.nom'] ? 'is-erreur' : ''">Nom<input type="text" x-model="ins.tuteur.nom" maxlength="100"><span class="dmi-champ-aide" x-show="ins.prep?.tuteur?.declare && !ins.tuteur.prenoms">Séparez le nom et les prénoms.</span>
                                <button type="button" class="dmi-lien" style="font-size:.74rem;text-align:left" x-show="coupeTuteur()" x-on:click="appliquerCoupeTuteur()"
                                        x-text="coupeTuteur() ? 'Nom « ' + coupeTuteur()[0] + ' », prénoms « ' + coupeTuteur()[1] + ' » ? Séparer ainsi' : ''"></button></label>
                            <label class="dmi-champ" :class="ins.erreurs['parents.0.prenoms'] ? 'is-erreur' : ''">Prénoms<input type="text" x-model="ins.tuteur.prenoms" maxlength="100"></label>
                            <label class="dmi-champ" :class="ins.erreurs['parents.0.telephone'] ? 'is-erreur' : ''">Téléphone<input type="tel" x-model="ins.tuteur.telephone" maxlength="20"></label>
                            <div class="dmi-champ" :class="ins.erreurs['parents.0.relation'] ? 'is-erreur' : ''">Lien
                                <div class="dmi-choix" role="radiogroup" aria-label="Lien avec l'étudiant">
                                    <template x-for="lien in ['Père', 'Mère', 'Tuteur', 'Autre']" :key="lien">
                                        <button type="button" role="radio" :aria-checked="ins.tuteur.relation === lien" :class="ins.tuteur.relation === lien ? 'is-actif' : ''" x-on:click="ins.tuteur.relation = lien" x-text="lien"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <span :class="Object.keys(ins.erreurs).some((k) => k.startsWith('parents.')) ? 'dmi-champ-erreur' : 'dmi-champ-aide'" x-show="erreurTuteur()" x-text="erreurTuteur()"></span>
                    </section>
                </div>

                <aside class="dmi-apercu" aria-label="Aperçu de l'inscription">
                    <h3>3 · Aperçu</h3>
                    <div class="dmi-qui">
                        <span class="dmi-av" x-text="initiales(ins.f.nom + ' ' + ins.f.prenoms)"></span>
                        <div><strong x-text="(ins.f.nom + ' ' + ins.f.prenoms).trim()"></strong><div class="dmi-p-note" x-text="ins.prep?.matricule_automatique ? 'Matricule attribué à l\'enregistrement' : (ins.f.matricule || 'Matricule à saisir')"></div></div>
                    </div>
                    <dl>
                        <dt>Classe</dt><dd x-text="classeChoisie(ins.prep?.classes, ins.f.classe_id)?.nom || 'À choisir'"></dd>
                        <dt>Année</dt><dd x-text="ins.prep?.candidature?.annee || '—'"></dd>
                        <dt>Tuteur</dt><dd x-text="ins.sansTuteur || !ins.tuteur.nom ? '—' : (ins.tuteur.nom + ' ' + ins.tuteur.prenoms).trim() + (ins.tuteur.relation ? ' · ' + ins.tuteur.relation.toLowerCase() : '')"></dd>
                    </dl>
                    <div class="dmi-frais" x-show="ins.f.classe_id">
                        <div class="dmi-p-note" style="font-weight:700;margin-bottom:.35rem">Frais à régler</div>
                        <template x-if="ins.frais.chargement"><div class="dmi-p-note"><i class="fas fa-circle-notch fa-spin"></i> Calcul…</div></template>
                        <template x-if="!ins.frais.chargement && ins.frais.masques"><div class="dmi-p-note">Montants masqués pour votre profil.</div></template>
                        <template x-if="!ins.frais.chargement && !ins.frais.masques">
                            <div>
                                <template x-for="l in ins.frais.lignes" :key="l.libelle"><div class="dmi-frais-ligne"><span x-text="l.libelle"></span><span x-text="fcfa(l.montant)"></span></div></template>
                                <div class="dmi-frais-ligne dmi-frais-total"><span>Total dû</span><span x-text="fcfa(ins.frais.total)"></span></div>
                                <div class="dmi-p-note" style="margin-top:.35rem" x-show="ins.frais.incomplet">Certains frais ne sont pas configurés pour cette classe.</div>
                            </div>
                        </template>
                    </div>
                    <ul class="dmi-suites">
                        <li>l'étudiant et son compte sont créés ;</li>
                        <li>la demande passe à « inscrite » ;</li>
                        <li x-show="ins.prep?.rendez_vous" x-text="ins.prep?.rendez_vous?.aujourdhui ? 'le rendez-vous de ' + ins.prep.rendez_vous.heure + ' est marqué honoré ;' : 'le rendez-vous du ' + ins.prep?.rendez_vous?.jour + ' est libéré ;'"></li>
                        <li>la fiche de l'inscription s'ouvre, avec les identifiants du compte.</li>
                    </ul>
                    <p class="dmi-champ-erreur" x-show="ins.message" x-text="ins.message" role="alert"></p>
                    <button type="button" class="dmi-btn dmi-btn--primary dmi-btn--bloc" x-on:click="inscrire()" :disabled="!insPret() || occupe">
                        <span x-show="!occupe"><i class="fas fa-user-plus"></i> <span x-text="ins.prep?.candidature?.statut === 'acceptee' ? 'Inscrire' : 'Accepter et inscrire'"></span></span>
                        <span x-show="occupe" x-cloak><i class="fas fa-circle-notch fa-spin"></i> Inscription…</span>
                    </button>
                    <p class="dmi-p-note" style="margin:.5rem 0 0" x-show="!insPret()" x-text="raisonNonPret()"></p>
                    <div style="display:flex;justify-content:space-between;margin-top:.75rem">
                        <button type="button" class="dmi-lien" x-on:click="fermerFenetre()">Retour</button>
                        <button type="button" class="dmi-lien" x-on:click="formulaireComplet()" title="Ouvre le formulaire d'inscription pré-rempli, avec photo, frais optionnels et dépôts en nature">Formulaire complet</button>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>
