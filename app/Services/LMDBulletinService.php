<?php

namespace App\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDDeliberation;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\ESBTPLMDResultatUE;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPUniteEnseignement;
use App\Helpers\SettingsHelper;
use App\Services\LMD\CompositionDuBulletin;
use App\Services\LMD\Exceptions\MaquetteSansCompositionException;
use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMD\VocabulaireStructure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LMDBulletinService
{
    /**
     * Texte par defaut de la notice imprimee en bas du bulletin LMD.
     *
     * Volontairement neutre : il rappelle la regle UEMOA sans engager
     * l'etablissement a delivrer un document que l'application ne sait pas
     * produire. Une ecole qui delivre reellement une attestation ecrit sa
     * propre phrase dans le reglage « lmd_bulletin_notice_text »
     * (Configuration des bulletins > Textes du bulletin).
     */
    public const NOTICE_DEFAUT = "Un ECUE n'est ni transférable ni capitalisable. Les crédits d'une UE non acquise ne sont capitalisés qu'après validation de celle-ci.";

    /**
     * Les deux dependances portent un defaut, et ce n'est pas de la commodite :
     * les quatre tests unitaires du service l'instancient avec le seul profil de
     * regles (`new LMDBulletinService($profil)`). Un parametre exige ici leve un
     * `ArgumentCountError` dans ces quatre fichiers, sans qu'aucun ne soit
     * modifie. Les deux classes se construisent sans argument et ne lisent leurs
     * reglages qu'a l'appel, donc le defaut ne coute rien au conteneur, qui les
     * resout normalement en production.
     */
    public function __construct(
        private readonly LmdAcademicRuleProfile $rules,
        private readonly VocabulaireStructure $vocabulaire = new VocabulaireStructure,
        private readonly CompositionDuBulletin $composition = new CompositionDuBulletin,
    ) {}

    /** Cache des settings LMD pour eviter des requetes repetees. */
    protected array $settings = [];

    /** Pre-loaded notes grouped by matiere_id (avoid N+1). */
    protected ?\Illuminate\Support\Collection $preloadedNotes = null;

    /** Pre-loaded enseignant mapping matiere_id => enseignant_id (avoid N+1). */
    protected ?\Illuminate\Support\Collection $preloadedEnseignants = null;

    protected function getSetting(string $key, $default = null)
    {
        if (!isset($this->settings[$key])) {
            $this->settings[$key] = SettingsHelper::get($key, $default);
        }
        return $this->settings[$key];
    }

    private function libelleOuVocabulaire(string $cle, string $vocabulaire): string
    {
        $regle = trim((string) $this->getSetting($cle, ''));

        return $regle !== '' ? $regle : mb_strtoupper($vocabulaire, 'UTF-8');
    }

    protected function getValidationThreshold(): float
    {
        return $this->rules->validationThreshold();
    }

    /**
     * Get all possible periode label variants for a given semestre number.
     * Evaluations may store periode as '3', 'semestre3', 'S3', 'Semestre 3', etc.
     */
    public function getPeriodeVariants(int $semestre): array
    {
        return [
            (string) $semestre,
            'semestre' . $semestre,
            'S' . $semestre,
            'Semestre ' . $semestre,
            'semestre ' . $semestre,
        ];
    }

    /**
     * Generer le bulletin LMD complet pour un etudiant.
     *
     * @param int $etudiantId
     * @param int $classeId
     * @param int $anneeUniversitaireId
     * @param int $semestre (1-10)
     * @return ESBTPLMDBulletin
     */
    public function genererBulletinLMD(
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        int $semestre,
        bool $skipRanksAndStats = false
    ): ESBTPLMDBulletin {
        return DB::transaction(function () use ($etudiantId, $classeId, $anneeUniversitaireId, $semestre, $skipRanksAndStats) {

            $classe = ESBTPClasse::with(['parcours.mention.domaine', 'parcours.filiere', 'niveau'])->findOrFail($classeId);

            // 0. La composition du semestre, lue AVANT la moindre écriture.
            // Un bulletin qu'on va refuser de recalculer ne doit pas non plus
            // voir son en-tête réécrit ni son horodatage bougé : ce sont
            // justement les deux signaux qui feraient croire qu'il a été traité.
            $ues = $this->getUEsForSemestre($classe, $semestre);
            $this->refuserSurUneMaquetteVide($etudiantId, $classeId, $anneeUniversitaireId, $semestre, $ues);

            // 1. Creer ou mettre a jour le bulletin
            $bulletin = $this->poserEnTeteDuBulletin($classe, $etudiantId, $classeId, $anneeUniversitaireId, $semestre);

            $this->prechargerNotesEtEnseignants($etudiantId, $classeId, $semestre, $anneeUniversitaireId);

            // 3. Calculer les resultats par UE et ECUE
            $resultatsUEs = [];
            $creditsTotaux = 0;

            foreach ($ues as $ue) {
                $resultatUE = $this->calculerResultatUE($bulletin, $ue, $etudiantId, $classeId, $semestre, $anneeUniversitaireId, $classe->parcours_id ? (int) $classe->parcours_id : null);
                $resultatsUEs[] = $resultatUE;
                $creditsTotaux += $ue->creditEffectif();
            }

            $this->composition->elaguerLesUnites($bulletin, $resultatsUEs);

            // 4. Calculer la moyenne generale ponderee par credits
            $moyenneGenerale = $this->calculerMoyenneGenerale($resultatsUEs);

            // 5. Appliquer la compensation inter-UE
            $creditsCapitalises = $this->appliquerCompensation($resultatsUEs, $moyenneGenerale);

            // 6. Mettre a jour le bulletin
            $bulletin->update([
                'moyenne_generale' => $moyenneGenerale,
                'credits_capitalises' => $creditsCapitalises,
                'credits_totaux' => $creditsTotaux,
                'updated_by' => auth()->id(),
            ]);

            // 7. Calculer les rangs et stats (skip si batch — sera fait une seule fois apres la boucle)
            if (!($skipRanksAndStats ?? false)) {
                $this->calculerRangsClasse($classeId, $anneeUniversitaireId, $semestre);
                $this->calculerStatsPromo($classeId, $anneeUniversitaireId, $semestre);
            }

            return $bulletin->fresh([
                'resultatsUEs.uniteEnseignement',
                'resultatsUEs.resultatsECUEs.matiere',
                'etudiant',
                'classe',
                'deliberation',
            ]);
        });
    }

    public function studentIdsForGenerationCohort(int $classeId, int $anneeUniversitaireId): Collection
    {
        return DB::table('esbtp_inscriptions')
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->orderBy('etudiant_id')
            ->pluck('etudiant_id')
            ->map(fn ($id): int => (int) $id);
    }

    /**
     * Generer les bulletins pour toute une classe.
     */
    public function genererBulletinsClasse(int $classeId, int $anneeUniversitaireId, int $semestre): array
    {
        ESBTPClasse::findOrFail($classeId);

        $bulletins = [];
        $errors = [];
        foreach ($this->studentIdsForGenerationCohort($classeId, $anneeUniversitaireId) as $studentId) {
            try {
                $bulletins[] = $this->genererBulletinLMD(
                    $studentId,
                    $classeId,
                    $anneeUniversitaireId,
                    $semestre,
                    skipRanksAndStats: true // Calculer une seule fois apres la boucle
                );
            } catch (MaquetteSansCompositionException $e) {
                // Sans ce rattrapage, le `catch (\Exception)` juste en dessous
                // l'avalerait : la maquette vide vaut pour TOUTE la classe, donc
                // l'écran afficherait « 0/25 bulletins générés » sans jamais dire
                // pourquoi. On laisse remonter, le contrôleur le dit une fois.
                throw $e;
            } catch (\Exception $e) {
                Log::error("LMD Bulletin generation failed for etudiant {$studentId}: {$e->getMessage()}");
                $errors[] = $studentId;
            }
        }

        // Calculer rangs et stats une seule fois pour toute la classe
        if (count($bulletins) > 0) {
            $this->calculerRangsClasse($classeId, $anneeUniversitaireId, $semestre);
            $this->calculerStatsPromo($classeId, $anneeUniversitaireId, $semestre);
        }

        return $bulletins;
    }

    /**
     * Le bulletin et son en-tête : rangs de la structure, libellé du parcours.
     *
     * Le libellé se compose automatiquement à partir du niveau et de la filière
     * (« LICENCE 3 GCV BATIMENT & URBANISME ») quand le réglage
     * `lmd_bulletin_parcours_auto` est posé ; sinon on imprime le nom du
     * parcours tel que l'école l'a saisi.
     */
    private function poserEnTeteDuBulletin(
        ESBTPClasse $classe,
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        int $semestre
    ): ESBTPLMDBulletin {
        $parcours = $classe->parcours;
        $parcoursAuto = $this->getSetting('lmd_bulletin_parcours_auto', '1') == '1';
        $parcoursLabel = $parcours
            ? ($parcoursAuto
                ? $parcours->genererLabelBulletin($classe->niveau)
                : (string) $parcours->name)
            : ($classe->niveau?->name ?? '');

        return ESBTPLMDBulletin::updateOrCreate(
            [
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'annee_universitaire_id' => $anneeUniversitaireId,
                'semestre' => $semestre,
            ],
            [
                'parcours_id' => $parcours?->id,
                'niveau' => $classe->niveau?->name,
                'domaine_label' => $parcours?->mention?->domaine?->name,
                'mention_label' => $parcours?->mention?->name,
                'parcours_label' => $parcoursLabel,
                'updated_by' => auth()->id(),
            ]
        );
    }

    /**
     * Les notes et les enseignants du semestre, chargés une fois pour toutes.
     *
     * Sans ce préchargement, chaque élément constitutif irait chercher ses notes
     * et son enseignant tout seul : une requête par élément et par étudiant.
     */
    private function prechargerNotesEtEnseignants(
        int $etudiantId,
        int $classeId,
        int $semestre,
        int $anneeUniversitaireId
    ): void {
        $periodeVariants = $this->getPeriodeVariants($semestre);

        $this->preloadedNotes = ESBTPNote::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->whereHas('evaluation', function ($q) use ($periodeVariants, $anneeUniversitaireId) {
                $q->whereIn('periode', $periodeVariants)
                  ->where('annee_universitaire_id', $anneeUniversitaireId)
                  ->where('status', ESBTPEvaluation::STATUS_COMPLETED);
            })
            ->with('evaluation')
            ->get()
            ->groupBy('matiere_id');

        $this->preloadedEnseignants = ESBTPEvaluation::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereNotNull('enseignant_id')
            ->distinct()
            ->pluck('enseignant_id', 'matiere_id');
    }

    /**
     * Une maquette vide ne recalcule pas un bulletin qui porte déjà des lignes.
     *
     * Le pivot de maquette ne rend momentanément rien dans des cas parfaitement
     * ordinaires : le nettoyage avant réimport supprime les liens parcours-unité
     * PUIS met les unités à la corbeille, et désactiver les matières d'une unité
     * vide sa composition. Recalculer dans cette fenêtre écrirait une moyenne
     * nulle et des crédits à zéro sous une liste d'unités intacte, et sortirait
     * l'étudiant du classement — décalant les rangs de toute sa classe.
     *
     * On refuse, et on refuse BRUYAMMENT. La condition vaut pour la classe
     * entière : rendre le bulletin tel quel ferait annoncer « 25 bulletins
     * générés » à une génération en masse qui n'en a recalculé aucun. Une donnée
     * fausse remplacée par un message faux n'est pas un progrès.
     *
     * La PREMIÈRE génération, elle, passe : il n'y a encore rien à protéger, et
     * une école qui prépare sa maquette garde le comportement d'avant.
     */
    private function refuserSurUneMaquetteVide(
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        int $semestre,
        Collection $ues
    ): void {
        if ($ues->isNotEmpty()) {
            return;
        }

        $bulletin = ESBTPLMDBulletin::query()
            ->where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('semestre', $semestre)
            ->first();

        if ($bulletin === null) {
            return;
        }

        $lignes = ESBTPLMDResultatUE::query()->where('bulletin_id', $bulletin->id)->count();

        if ($lignes === 0) {
            return;
        }

        Log::warning('Maquette sans composition : recalcul du bulletin refusé', [
            'bulletin_id' => $bulletin->id,
            'classe_id' => $classeId,
            'semestre' => $semestre,
            'lignes_conservees' => $lignes,
        ]);

        throw new MaquetteSansCompositionException($semestre);
    }

    /**
     * Recuperer les UEs pour un semestre donne.
     */
    public function getUEsForSemestre(ESBTPClasse $classe, int $semestre): \Illuminate\Support\Collection
    {
        // On charge le pivot ENTIER, sans filtrer par maquette.
        //
        // Contraindre ici semblait economique, mais c'etait le contraire d'un
        // gain : le repli sur la cle etrangere de getEcuesEffectifs() ne peut
        // reconnaitre un element deja porte par le pivot que s'il le VOIT. Filtre
        // en SQL, l'element reserve a un autre parcours disparaissait de la
        // collection, le repli le prenait pour un element sans pivot, et le
        // reintroduisait — dans la maquette d'a cote, et sans son pivot, donc
        // avec les valeurs de la matiere au lieu de celles de la maquette.
        //
        // La maquette se tranche a un seul endroit, en memoire, dans
        // getEcuesEffectifs(). Le cout reste celui d'un chargement anticipe par
        // requete : les seize mille requetes redoutees viendraient d'une requete
        // PAR UNITE ET PAR ETUDIANT, que personne ne fait ici.
        $eagerLoad = [
            'ecues' => fn($q) => $q->where('esbtp_matieres.is_active', true)->orderBy('esbtp_ue_matiere.ordre_bulletin')->orderBy('esbtp_matieres.code'),
            'matieres' => fn($q) => $q->where('is_active', true)->orderBy('ordre_bulletin')->orderBy('code'),
        ];

        if ($classe->parcours_id) {
            $pivotData = DB::table('esbtp_lmd_parcours_ue')
                ->where('parcours_id', $classe->parcours_id)
                ->where('semestre', $semestre)
                ->orderBy('ordre')
                ->get();

            if ($pivotData->isNotEmpty()) {
                $ueIds = $pivotData->pluck('unite_enseignement_id');
                $ues = ESBTPUniteEnseignement::active()
                    ->with($eagerLoad)
                    ->whereIn('id', $ueIds)
                    ->get()
                    ->keyBy('id');

                return $pivotData->map(function ($p) use ($ues) {
                    $ue = $ues->get($p->unite_enseignement_id);
                    if ($ue === null) {
                        return null;
                    }

                    // Le crédit de CETTE maquette, quand elle en grave un.
                    //
                    // Sans cette ligne, le bulletin d'un parcours calculait son
                    // total avec le crédit de la FICHE — c'est-à-dire celui du
                    // premier parcours qui a importé l'unité. Sur esbtp-abidjan,
                    // Bâtiment et Travaux Publics partagent des unités à crédits
                    // divergents : le second parcours comparait ses crédits
                    // obtenus à un dénominateur emprunté au premier, et la
                    // frontière entre « admis » et « admis sous condition » se
                    // déplaçait, sans qu'aucune erreur ne soit levée.
                    //
                    // `!== null` et non `?:` ni `??` sur la valeur brute : un
                    // crédit de 0 est une décision de l'école (unité qui ne
                    // rapporte rien dans ce parcours), pas une absence.
                    $ue->creditDeLaMaquette = $p->credit !== null ? (int) $p->credit : null;

                    return $ue;
                })->filter()->values();
            }
        }

        // Repli : classe sans parcours. Le pivot parcours -> UE, qui porte
        // l'ordre du cas nominal, n'existe pas ici. On respecte l'ordre pose
        // sur l'UE elle-meme quand il y en a un, et on retombe sur le code
        // sinon — donc a l'identique tant qu'aucun ordre n'est renseigne.
        return ESBTPUniteEnseignement::active()
            ->with($eagerLoad)
            ->where('semestre', $semestre)
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_id', $classe->niveau_etude_id)
            ->orderByRaw('ordre IS NULL, ordre')
            ->orderBy('code')
            ->get();
    }

    /**
     * Calculer le resultat d'une UE pour un etudiant.
     */
    protected function calculerResultatUE(
        ESBTPLMDBulletin $bulletin,
        ESBTPUniteEnseignement $ue,
        int $etudiantId,
        int $classeId,
        int $semestre,
        int $anneeUniversitaireId,
        ?int $parcoursId = null
    ): ESBTPLMDResultatUE {

        // Creer/mettre a jour le resultat UE
        $resultatUE = $this->composition->reprendreOuCreer(
            ESBTPLMDResultatUE::query(),
            [
                'bulletin_id' => $bulletin->id,
                'unite_enseignement_id' => $ue->id,
            ],
            [
                'etudiant_id' => $etudiantId,
                // Le crédit de la maquette lue, pas celui de la fiche : c'est ce
                // résultat qui pondère ensuite la moyenne générale et qui sert de
                // dénominateur aux crédits capitalisés.
                'credit' => $ue->creditEffectif(),
                'updated_by' => auth()->id(),
            ]
        );

        // Calculer les resultats de chaque ECUE — pivot prioritaire, fallback HasMany.
        // Le parcours decide de la composition : sans lui, un element propre a une
        // autre maquette entrerait dans cette moyenne, et un element surcharge y
        // entrerait DEUX fois, avec son coefficient compte deux fois.
        [$resultatsECUEs, $totalPoints, $totalCoefficients] = $this->calculerLesElementsDeLUnite(
            $bulletin, $resultatUE, $ue, $etudiantId, $classeId, $semestre, $anneeUniversitaireId, $parcoursId
        );

        // La maquette ne rattache plus aucun élément à cette unité alors qu'elle
        // en portait : sa moyenne, son statut et sa mention sont laissés intacts.
        // (Son crédit, lui, vient d'être réécrit avec celui de la maquette — c'est
        // voulu : il ne dépend pas des notes.) Écrire ici une moyenne vide et un
        // « non acquis » retirerait ses crédits du capitalisé alors que le total
        // continue de les compter — et ferait bouger la moyenne générale du
        // bulletin, donc la frontière entre admis et admis sous condition.
        if ($this->composition->elaguerLesElements($resultatUE, $resultatsECUEs)) {
            return $resultatUE;
        }

        // Moyenne UE = Σ(moyenne_ecue × coeff_ecue) / Σ coeff_ecue
        $moyenneUE = $totalCoefficients > 0
            ? round($totalPoints / $totalCoefficients, 2)
            : null;

        // Statut initial (AQ si >= seuil, NAQ sinon — APC sera applique apres)
        $threshold = $this->getValidationThreshold();
        $statut = ESBTPLMDResultatUE::STATUT_NAQ;
        if ($moyenneUE !== null && $moyenneUE >= $threshold) {
            $statut = ESBTPLMDResultatUE::STATUT_AQ;
        }

        $resultatUE->update([
            'moyenne' => $moyenneUE,
            'statut' => $statut,
            'mention' => $this->determinerMentionUE($moyenneUE),
            'updated_by' => auth()->id(),
        ]);

        return $resultatUE;
    }

    /**
     * Les elements constitutifs d'une unite, et les deux sommes qui font sa moyenne.
     *
     * @return array{0: array<int, ESBTPLMDResultatECUE>, 1: float, 2: float}
     *         les resultats calcules, la somme des points ponderes, la somme des coefficients
     */
    private function calculerLesElementsDeLUnite(
        ESBTPLMDBulletin $bulletin,
        ESBTPLMDResultatUE $resultatUE,
        ESBTPUniteEnseignement $ue,
        int $etudiantId,
        int $classeId,
        int $semestre,
        int $anneeUniversitaireId,
        ?int $parcoursId
    ): array {
        // Le parcours decide de la composition : sans lui, un element propre a une
        // autre maquette entrerait dans cette moyenne, et un element surcharge y
        // entrerait DEUX fois, avec son coefficient compte deux fois.
        $ecues = $ue->getEcuesEffectifs($parcoursId);
        $resultats = [];
        $totalPoints = 0.0;
        $totalCoefficients = 0.0;

        foreach ($ecues as $ecue) {
            $resultatECUE = $this->calculerResultatECUE(
                $bulletin, $resultatUE, $ecue, $etudiantId, $classeId, $semestre, $anneeUniversitaireId
            );
            $resultats[] = $resultatECUE;

            $noteEffective = $this->noteEffectiveECUE($resultatECUE);

            if ($noteEffective !== null) {
                // Priorité: pivot coefficient > matière coefficient_ecue > matière coefficient > 1
                $coeff = $ecue->pivot?->coefficient_ecue ?? $ecue->coefficient_ecue ?? $ecue->coefficient ?? 1;
                $totalPoints += $noteEffective * (float) $coeff;
                $totalCoefficients += (float) $coeff;
            }
        }

        return [$resultats, $totalPoints, $totalCoefficients];
    }

    /**
     * Calculer le resultat d'un ECUE (matiere) pour un etudiant.
     */
    protected function calculerResultatECUE(
        ESBTPLMDBulletin $bulletin,
        ESBTPLMDResultatUE $resultatUE,
        ESBTPMatiere $ecue,
        int $etudiantId,
        int $classeId,
        int $semestre,
        int $anneeUniversitaireId
    ): ESBTPLMDResultatECUE {

        // Calculer la moyenne ECUE depuis les notes (use preloaded data if available)
        $moyenneECUE = $this->calculerMoyenneECUE(
            $etudiantId, $ecue->id, $classeId, $semestre, $anneeUniversitaireId,
            $this->preloadedNotes?->get($ecue->id, collect())
        );

        // Trouver l'enseignant principal (use preloaded map if available)
        $enseignantId = $this->getEnseignantForECUE($ecue->id, $classeId, $anneeUniversitaireId, $this->preloadedEnseignants);

        $attributs = [
            'resultat_ue_id' => $resultatUE->id,
            'etudiant_id' => $etudiantId,
            'moyenne' => $moyenneECUE,
            'credit' => $ecue->pivot?->credit_ecue ?? $ecue->credit_ecue ?? 0,
            'enseignant_id' => $enseignantId,
            'updated_by' => auth()->id(),
        ];

        // `withTrashed()` : un élément retiré puis remis à la maquette doit
        // retrouver sa note de seconde session, pas repartir de zéro.
        $existant = ESBTPLMDResultatECUE::withTrashed()
            ->where('bulletin_id', $bulletin->id)
            ->where('matiere_id', $ecue->id)
            ->first();

        // Une seconde session a eu lieu : « note_finale » doit être rejouée sur la
        // moyenne de premiere session COURANTE. Sans cela, elle reste figee sur la
        // valeur calculee le jour du rattrapage : corriger apres coup une note de
        // premiere session (un 8 rectifie en 15) ne servirait plus a rien, la
        // regeneration du bulletin continuerait de retenir l'ancien max, et
        // l'etudiant perdrait des points sans message ni trace.
        if ($existant && $existant->note_rattrapage !== null) {
            $attributs['note_session_normale'] = $moyenneECUE;
            $attributs['note_finale'] = $this->noteFinaleApresRattrapage(
                $moyenneECUE === null ? null : (float) $moyenneECUE,
                (float) $existant->note_rattrapage
            );
        }

        return $this->composition->reprendreOuCreer(
            ESBTPLMDResultatECUE::query(),
            [
                'bulletin_id' => $bulletin->id,
                'matiere_id' => $ecue->id,
            ],
            $attributs
        );
    }

    /**
     * Note retenue apres seconde session, selon le reglage d'instance
     * `lmd_rattrapage_replace` : la meilleure des deux notes (defaut), ou celle
     * du rattrapage. Source unique de la regle, partagee avec la planification
     * des sessions de rattrapage.
     *
     * Un zero est une note : seul `null` signifie « pas de note ».
     */
    public function noteFinaleApresRattrapage(?float $noteSessionNormale, ?float $noteRattrapage): ?float
    {
        if ($noteRattrapage === null) {
            return null;
        }

        if ((bool) $this->getSetting('lmd_rattrapage_replace', false)) {
            return $noteRattrapage;
        }

        return $noteSessionNormale === null
            ? $noteRattrapage
            : max($noteSessionNormale, $noteRattrapage);
    }

    /**
     * Note retenue pour un ECUE dans les agregats du bulletin.
     *
     * Quand une seconde session a eu lieu, « note_finale » porte le resultat que l'ecole
     * a decide de retenir (la meilleure des deux notes, ou celle du rattrapage, selon le
     * reglage « lmd_rattrapage_replace »). Elle prime alors sur la moyenne de premiere
     * session : c'est par ce seul point que le rattrapage se propage a la moyenne de
     * l'unite, a son statut acquis/non acquis, aux credits capitalises, a la moyenne
     * generale, au rang, au releve et a la deliberation.
     *
     * Seul « null » signifie « pas de seconde session ». Une note finale de zero est une
     * note comme une autre et doit remplacer la moyenne de premiere session : on ne teste
     * donc jamais cette valeur par sa verite.
     */
    public function noteEffectiveECUE(object $resultatECUE): ?float
    {
        $noteFinale = $resultatECUE->note_finale ?? null;

        if ($noteFinale !== null) {
            return (float) $noteFinale;
        }

        $moyenne = $resultatECUE->moyenne ?? null;

        return $moyenne === null ? null : (float) $moyenne;
    }

    /**
     * Calculer la moyenne d'un ECUE depuis les notes des evaluations.
     *
     * Moyenne ECUE = Σ(note_normalized × coeff_eval) / Σ coeff_eval
     */
    public function calculerMoyenneECUE(
        int $etudiantId,
        int $matiereId,
        int $classeId,
        int $semestre,
        int $anneeUniversitaireId,
        ?\Illuminate\Support\Collection $preloadedNotes = null
    ): ?float {
        if ($preloadedNotes !== null) {
            $notes = $preloadedNotes;
        } else {
            $periodeVariants = $this->getPeriodeVariants($semestre);
            $notes = ESBTPNote::where('etudiant_id', $etudiantId)
                ->where('matiere_id', $matiereId)
                ->where('classe_id', $classeId)
                ->whereHas('evaluation', function ($q) use ($periodeVariants, $anneeUniversitaireId) {
                    $q->whereIn('periode', $periodeVariants)
                      ->where('annee_universitaire_id', $anneeUniversitaireId)
                      ->where('status', ESBTPEvaluation::STATUS_COMPLETED);
                })
                ->with('evaluation')
                ->get();
        }

        if ($notes->isEmpty()) return null;

        $totalPoints = 0;
        $totalCoeff = 0;

        foreach ($notes as $note) {
            $eval = $note->evaluation;
            if (!$eval) continue;

            $bareme = $eval->bareme ?: 20;
            $coeffEval = $eval->coefficient ?: 1;

            // Normaliser la note sur 20
            $noteNormalisee = $note->is_absent ? 0 : (($note->note / $bareme) * 20);

            $totalPoints += $noteNormalisee * $coeffEval;
            $totalCoeff += $coeffEval;
        }

        if ($totalCoeff == 0) return null;

        return round($totalPoints / $totalCoeff, 2);
    }

    /**
     * Calculer la moyenne generale ponderee par credits.
     *
     * Moyenne Generale = Σ(moyenne_ue × credits_ue) / Σ credits_ue
     */
    public function calculerMoyenneGenerale(array $resultatsUEs): ?float
    {
        $totalPoints = 0;
        $totalCredits = 0;

        foreach ($resultatsUEs as $resultat) {
            if ($resultat->moyenne !== null && $resultat->credit > 0) {
                $totalPoints += (float) $resultat->moyenne * $resultat->credit;
                $totalCredits += $resultat->credit;
            }
        }

        if ($totalCredits == 0) return null;

        return round($totalPoints / $totalCredits, 2);
    }

    /**
     * Appliquer la compensation inter-UE et calculer les credits capitalises.
     *
     * Regles:
     * - AQ: moyenne_ue >= 10 → credits capitalises
     * - APC: moyenne_ue < 10 MAIS moyenne_generale >= 10 → credits capitalises
     * - NAQ: sinon → pas de credits
     */
    public function appliquerCompensation(array $resultatsUEs, ?float $moyenneGenerale): int
    {
        $threshold = $this->getValidationThreshold();
        $compensationEnabled = $this->rules->interUeCompensationEnabled();
        $creditsCapitalises = 0;
        $apcIds = [];

        foreach ($resultatsUEs as $resultat) {
            if ($resultat->moyenne === null) continue;

            if ((float) $resultat->moyenne >= $threshold) {
                // Deja AQ
                $creditsCapitalises += $resultat->credit;
            } elseif ($compensationEnabled && $moyenneGenerale !== null && $moyenneGenerale >= $threshold) {
                // Compensation: APC
                $apcIds[] = $resultat->id;
                $creditsCapitalises += $resultat->credit;
            }
            // Sinon reste NAQ, pas de credits
        }

        // Batch update APC au lieu d'un update par UE
        if (!empty($apcIds)) {
            ESBTPLMDResultatUE::whereIn('id', $apcIds)
                ->update(['statut' => ESBTPLMDResultatUE::STATUT_APC]);
        }

        return $creditsCapitalises;
    }

    /**
     * Calculer les rangs de tous les etudiants d'une classe pour un semestre.
     */
    public function calculerRangsClasse(int $classeId, int $anneeUniversitaireId, int $semestre): void
    {
        $bulletins = ESBTPLMDBulletin::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('semestre', $semestre)
            ->whereNotNull('moyenne_generale')
            ->orderByDesc('moyenne_generale')
            ->get();

        $effectif = $bulletins->count();
        $position = 0;
        $rang = 0;
        $lastMoyenne = null;

        foreach ($bulletins as $bulletin) {
            $position++;
            $moy = (float) $bulletin->moyenne_generale;
            if ($moy !== $lastMoyenne) {
                $rang = $position;
                $lastMoyenne = $moy;
            }
            $bulletin->update([
                'rang' => $rang,
                'effectif' => $effectif,
            ]);
        }
    }

    /**
     * Calculer les stats promo (min/moy/max) pour chaque UE et ECUE.
     */
    public function calculerStatsPromo(int $classeId, int $anneeUniversitaireId, int $semestre): void
    {
        $bulletinIds = ESBTPLMDBulletin::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('semestre', $semestre)
            ->pluck('id');

        if ($bulletinIds->isEmpty()) return;

        // Stats par UE
        $statsUE = ESBTPLMDResultatUE::whereIn('bulletin_id', $bulletinIds)
            ->whereNotNull('moyenne')
            ->select('unite_enseignement_id')
            ->selectRaw('MIN(moyenne) as min_moy, AVG(moyenne) as avg_moy, MAX(moyenne) as max_moy')
            ->groupBy('unite_enseignement_id')
            ->get()
            ->keyBy('unite_enseignement_id');

        foreach ($statsUE as $ueId => $stat) {
            ESBTPLMDResultatUE::whereIn('bulletin_id', $bulletinIds)
                ->where('unite_enseignement_id', $ueId)
                ->update([
                    'stat_min' => round($stat->min_moy, 2),
                    'stat_moy' => round($stat->avg_moy, 2),
                    'stat_max' => round($stat->max_moy, 2),
                ]);
        }

        // Stats par ECUE
        $statsECUE = ESBTPLMDResultatECUE::whereIn('bulletin_id', $bulletinIds)
            ->whereNotNull('moyenne')
            ->select('matiere_id')
            ->selectRaw('MIN(moyenne) as min_moy, AVG(moyenne) as avg_moy, MAX(moyenne) as max_moy')
            ->groupBy('matiere_id')
            ->get()
            ->keyBy('matiere_id');

        foreach ($statsECUE as $matiereId => $stat) {
            ESBTPLMDResultatECUE::whereIn('bulletin_id', $bulletinIds)
                ->where('matiere_id', $matiereId)
                ->update([
                    'stat_min' => round($stat->min_moy, 2),
                    'stat_moy' => round($stat->avg_moy, 2),
                    'stat_max' => round($stat->max_moy, 2),
                ]);
        }

        // Rangs par ECUE
        $ecueIds = ESBTPLMDResultatECUE::whereIn('bulletin_id', $bulletinIds)
            ->distinct()->pluck('matiere_id');

        foreach ($ecueIds as $matiereId) {
            $resultats = ESBTPLMDResultatECUE::whereIn('bulletin_id', $bulletinIds)
                ->where('matiere_id', $matiereId)
                ->whereNotNull('moyenne')
                ->orderByDesc('moyenne')
                ->get();

            $position = 0;
            $rang = 0;
            $lastMoy = null;
            foreach ($resultats as $r) {
                $position++;
                $m = (float) $r->moyenne;
                if ($m !== $lastMoy) {
                    $rang = $position;
                    $lastMoy = $m;
                }
                $r->update(['rang' => $rang]);
            }
        }
    }

    /**
     * Determiner la mention d'une UE selon sa moyenne.
     */
    public function determinerMentionUE(?float $moyenne): ?string
    {
        if ($moyenne === null) return null;

        $classification = app(AppreciationScaleService::class)->classificationFor($moyenne, 'lmd', '');
        $slug = $classification['slug'];

        return match (true) {
            in_array($slug, ['excellent', 'tres-bien'], true) => 'TB',
            $slug === 'bien' => 'B',
            $slug === 'assez-bien' => 'AB',
            $slug === 'passable' => 'P',
            $slug === 'insuffisant' => 'INS',
            default => 'F',
        };
    }

    /**
     * Determiner la decision de deliberation.
     */
    public function determinerDecisionDeliberation(
        ?float $moyenneGenerale,
        int $creditsCapitalises,
        int $creditsTotaux
    ): string {
        if ($moyenneGenerale === null) return '';

        $tauxCapitalisation = $creditsTotaux > 0
            ? ($creditsCapitalises / $creditsTotaux) * 100
            : 0;

        if ($tauxCapitalisation == 100 && $moyenneGenerale >= 16) {
            return 'Félicitations du jury';
        }
        if ($tauxCapitalisation == 100 && $moyenneGenerale >= 14) {
            return 'Tableau d\'honneur';
        }
        if ($tauxCapitalisation == 100 && $moyenneGenerale >= 12) {
            return 'Encouragement pour le travail fourni';
        }
        if ($tauxCapitalisation == 100) {
            return 'Passage';
        }
        if ($tauxCapitalisation >= 70) {
            return 'Passage conditionnel';
        }
        return 'Ajourné(e)';
    }

    /**
     * Trouver l'enseignant d'un ECUE pour une classe donnee.
     */
    protected function getEnseignantForECUE(int $matiereId, int $classeId, int $anneeUniversitaireId, ?\Illuminate\Support\Collection $enseignantMap = null): ?int
    {
        if ($enseignantMap !== null) {
            return $enseignantMap->get($matiereId);
        }

        // Chercher dans les evaluations completees
        $eval = ESBTPEvaluation::where('matiere_id', $matiereId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereNotNull('enseignant_id')
            ->first();

        return $eval?->enseignant_id;
    }

    /**
     * Preparer les donnees pour le rendu du bulletin PDF/preview.
     */
    public function preparerDonneesBulletin(ESBTPLMDBulletin $bulletin): array
    {
        $bulletin->load([
            'etudiant',
            'classe.niveau',
            'classe.filiere',
            'parcours.mention.domaine',
            'anneeUniversitaire',
            'resultatsUEs.uniteEnseignement',
            'resultatsUEs.resultatsECUEs.matiere',
            'resultatsUEs.resultatsECUEs.enseignant',
            'deliberation',
        ]);

        // Bulletin field visibility & labels (configurable per tenant)
        $bulletinFields = [
            ['key' => 'domaine', 'show' => $this->getSetting('lmd_bulletin_show_domaine', '1') == '1', 'label' => $this->libelleOuVocabulaire('lmd_bulletin_label_domaine', $this->vocabulaire->natureDe($bulletin->parcours?->mention?->domaine)), 'value' => $bulletin->domaine_label],
            ['key' => 'mention', 'show' => $this->getSetting('lmd_bulletin_show_mention', '1') == '1', 'label' => $this->libelleOuVocabulaire('lmd_bulletin_label_mention', $this->vocabulaire->mention()), 'value' => $bulletin->mention_label],
            ['key' => 'specialite', 'show' => $this->getSetting('lmd_bulletin_show_specialite', '0') == '1', 'label' => $this->getSetting('lmd_bulletin_label_specialite', 'SPÉCIALITÉ'), 'value' => $bulletin->specialite_label ?? ''],
            ['key' => 'parcours', 'show' => $this->getSetting('lmd_bulletin_show_parcours', '1') == '1', 'label' => $this->libelleOuVocabulaire('lmd_bulletin_label_parcours', $this->vocabulaire->parcours()), 'value' => $bulletin->parcours_label],
        ];

        return [
            'bulletin' => $bulletin,
            'etudiant' => $bulletin->etudiant,
            'classe' => $bulletin->classe,
            'annee' => $bulletin->anneeUniversitaire,
            'parcours' => $bulletin->parcours,
            'domaine' => $bulletin->domaine_label,
            'mention' => $bulletin->mention_label,
            'parcours_label' => $bulletin->parcours_label,
            'niveau' => $bulletin->niveau,
            'semestre' => $bulletin->semestre,
            'resultats_ues' => $bulletin->resultatsUEs,
            'moyenne_generale' => $bulletin->moyenne_generale,
            'mention_generale' => $bulletin->mention_generale,
            'credits_capitalises' => $bulletin->credits_capitalises,
            'credits_totaux' => $bulletin->credits_totaux,
            'rang' => $bulletin->rang,
            'effectif' => $bulletin->effectif,
            'decision' => $bulletin->decision_deliberation,
            'deliberation' => $bulletin->deliberation,
            'bulletin_fields' => $bulletinFields,
        ];
    }
}
