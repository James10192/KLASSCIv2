<?php

namespace Tests\Feature\Bts;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPResultat;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Services\BulletinService;
use App\Services\ReeinscriptionService;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * Une ECUE notee dans une classe BTS ne doit ni figurer au bulletin, ni peser
 * sur la moyenne.
 *
 * POURQUOI CE TEST N'EST PAS UN DOUBLON D'`EcueLmdHorsBulletinBtsTest`. Celui-la
 * verifie `BtsBulletinSubjectResolver`, c'est-a-dire ce que la MAQUETTE ajoute
 * au bulletin. Or le bulletin BTS est pilote par les NOTES : une matiere notee y
 * figure meme absente de la maquette. Le chemin par lequel l'ECUE arrivait
 * reellement n'etait donc couvert par aucun test — et quatre passes de revue
 * l'ont manque.
 *
 * CHAQUE CAS PORTE UNE ASSERTION TEMOIN (la matiere BTS est bien la). Sans elle,
 * un test qui ne construit rien passerait au vert en ne trouvant rien : c'est
 * exactement la forme de faux-vert que ce chantier a deja payee une fois.
 */
class EcueLmdHorsDuBulletinNoteTest extends TestCase
{
    use RefreshDatabase;
    use MonteUneClasseBts;
    use SeedsConfiguredBulletin;

    protected function setUp(): void
    {
        parent::setUp();
        CoherenceSystemeAcademique::oublierLesEcartsJournalises();
    }

    /** Une ECUE, telle qu'une maquette LMD en importe. */
    private function uneEcue(string $code = 'TPGC641'): ESBTPMatiere
    {
        $ue = ESBTPUniteEnseignement::create([
            'name' => 'UE Ouvrages en genie civil',
            'code' => 'UE-'.$code,
            'credit' => 6,
            'semestre' => 1,
            'is_active' => true,
        ]);

        return ESBTPMatiere::factory()->create([
            'name' => 'OGC',
            'code' => $code,
            'is_active' => true,
            'unite_enseignement_id' => $ue->id,
        ]);
    }

    /**
     * Une evaluation ANTERIEURE au garde de `ESBTPEvaluation` (aout 2026).
     *
     * Elle se pose sur la matiere BTS puis bascule sans evenement : c'est la
     * seule facon de reproduire une ligne que le garde d'aujourd'hui refuserait,
     * et c'est bien de ces lignes-la qu'il s'agit en production.
     */
    private function evaluationHeritee(ESBTPMatiere $matiereBts, ESBTPMatiere $ecue): ESBTPEvaluation
    {
        $evaluation = ESBTPEvaluation::factory()->make([
            'matiere_id' => $matiereBts->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'status' => 'published',
            'bareme' => 20,
            'coefficient' => 1,
        ]);
        $evaluation->save();

        ESBTPEvaluation::withoutEvents(fn () => $evaluation->update(['matiere_id' => $ecue->id]));

        return $evaluation->refresh();
    }

    public function test_une_ecue_notee_ne_sort_pas_sur_l_apercu_et_ne_pese_pas_sur_la_moyenne(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue();

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($bts), 14);
        $this->noter($etudiant, $this->evaluationHeritee($bts, $ecue), 4);

        $this->seedConfiguredBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1', [$bts->id], []
        );

        $donnees = app(BulletinService::class)->genererDonneesBulletinPreview(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $ids = collect($donnees['resultatsGeneraux'] ?? [])
            ->merge(collect($donnees['resultatsTechniques'] ?? []))
            ->map(fn ($ligne) => (int) ($ligne->matiere_id ?? 0))
            ->all();

        $this->assertContains($bts->id, $ids, 'Temoin : sans la matiere BTS, le test ne prouve rien.');
        $this->assertNotContains($ecue->id, $ids, 'Une ECUE ne sort pas sur un bulletin BTS.');
        $this->assertEqualsWithDelta(
            14.0,
            (float) $donnees['moyenneGlobale'],
            0.01,
            'La moyenne ne compte que la matiere BTS (sans le garde : 10,67).'
        );
    }

    public function test_la_generation_officielle_ne_persiste_pas_l_ecue(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC642');

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($bts), 14);
        $this->noter($etudiant, $this->evaluationHeritee($bts, $ecue), 4);

        $bulletin = $this->seedConfiguredBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1', [$bts->id], []
        );

        app(BulletinService::class)->genererDonneesBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $resultats = DB::table('esbtp_resultats')
            ->where('etudiant_id', $etudiant->id)
            ->where('periode', 'semestre1')
            ->pluck('moyenne', 'matiere_id')
            ->all();

        $lignesOfficielles = DB::table('esbtp_resultats_matieres')
            ->where('bulletin_id', $bulletin->id)
            ->pluck('moyenne', 'matiere_id')
            ->all();

        $this->assertArrayHasKey($bts->id, $resultats, 'Temoin : la matiere BTS doit etre persistee.');
        $this->assertArrayNotHasKey($ecue->id, $resultats, 'Pas d ECUE dans esbtp_resultats.');
        $this->assertArrayNotHasKey($ecue->id, $lignesOfficielles, 'Pas d ECUE dans les lignes du bulletin.');

        $this->assertEqualsWithDelta(
            14.0,
            (float) DB::table('esbtp_bulletins')->where('id', $bulletin->id)->value('moyenne_generale'),
            0.01,
            'La moyenne figee au bulletin ne compte que la matiere BTS.'
        );

        // CETTE ASSERTION N'EST PAS UNE PREUVE DU FILTRE, ET C'EST ECRIT ICI POUR
        // QUE PERSONNE NE S'Y TROMPE. `buildBulletinPdf()` refait son propre
        // calcul depuis `esbtp_notes` et l'enregistre — une revue y a donc vu un
        // sixieme calcul non filtre. Le filtre a bien ete pose, puis RETIRE pour
        // mesurer : ce test restait vert. La raison est plus bas dans la methode,
        // `array_replace()` remplace le calcul du controleur par la projection du
        // service, deja filtree, laquelle re-enregistre la bonne moyenne.
        //
        // Ce qui est garde ici, c'est donc l'ETAT FINAL : apres un telechargement,
        // la base porte toujours la moyenne juste. Si quelqu'un deplace ce
        // `array_replace` ou retire une cle de la projection, ce test tombe.
        app(\App\Http\Controllers\ESBTPBulletinController::class)
            ->buildBulletinPdf($bulletin->fresh());

        $this->assertEqualsWithDelta(
            14.0,
            (float) DB::table('esbtp_bulletins')->where('id', $bulletin->id)->value('moyenne_generale'),
            0.01,
            'Apres un telechargement de PDF, la base doit toujours porter la moyenne filtree.'
        );
    }

    public function test_le_snapshot_courant_ecarte_l_ecue_comme_la_generation(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC643');

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($bts), 14);
        $this->noter($etudiant, $this->evaluationHeritee($bts, $ecue), 4);

        $snapshot = app(BtsCurrentResultSnapshotService::class)->getSemesterSnapshot(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $ids = array_map('intval', array_column($snapshot['subjects'] ?? [], 'matiere_id'));

        $this->assertContains($bts->id, $ids, 'Temoin : la matiere BTS doit etre dans le snapshot.');
        $this->assertNotContains(
            $ecue->id,
            $ids,
            "Le snapshot doit voir ce que la generation retient : sinon l'ecart « Officiel / Courant » "
            .'porte la meme erreur des deux cotes et n alerte personne.'
        );
    }

    public function test_les_statistiques_de_classe_ecartent_l_ecue_comme_la_moyenne_de_l_eleve(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC646');

        $evalBts = $this->evaluationDe($bts);
        $evalEcue = $this->evaluationHeritee($bts, $ecue);

        // DEUX eleves, et un seul porte l'ECUE. C'est ce qui rend le test
        // capable de voir le defaut : les statistiques de classe (moyenne de
        // la classe, plus forte, plus faible) s'impriment sur la MEME feuille
        // que la moyenne de l'eleve. Filtrer l'une sans l'autre laissait un
        // eleve depasser la « plus forte moyenne » de sa propre classe.
        $avecEcue = $this->etudiantInscrit();
        $this->noter($avecEcue, $evalBts, 14);
        $this->noter($avecEcue, $evalEcue, 4);

        $sansEcue = $this->etudiantInscrit();
        $this->noter($sansEcue, $evalBts, 10);

        // LA LIGNE HERITEE QUI FAIT LE DEFAUT, et sans elle le test ne prouve
        // rien. Les statistiques de classe lisent `esbtp_resultats` en
        // priorite, et n'en viennent aux notes que si la table est vide. Or
        // `persistResultats()` ecrit cette table a chaque generation — donc sur
        // une instance touchee, l'ECUE y a SA ligne, ecrite par une generation
        // anterieure au correctif. C'est elle qui empoisonne, et elle survit a
        // la regeneration : `persistResultats()` n'efface jamais la ligne d'une
        // matiere qui a disparu du bulletin.
        //
        // `withoutEvents` parce que le garde de `ESBTPResultat` refuse
        // desormais cette ecriture : on reproduit un heritage, pas un geste
        // encore possible.
        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $avecEcue->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $ecue->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 4,
            'coefficient' => 1,
        ]));

        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $avecEcue->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $bts->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 14,
            'coefficient' => 2,
        ]));

        $this->seedConfiguredBulletin(
            $avecEcue->id, $this->classe->id, $this->annee->id, 'semestre1', [$bts->id], []
        );

        $donnees = app(BulletinService::class)->genererDonneesBulletinPreview(
            $avecEcue->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $moyenneEleve = (float) $donnees['moyenneGlobale'];
        $meilleure = (float) $donnees['meilleure_moyenne'];
        $plusFaible = (float) $donnees['plus_faible_moyenne'];
        $moyenneClasse = (float) $donnees['moyenne_classe'];

        // Temoin : les statistiques sont bien calculees sur les deux eleves.
        $this->assertGreaterThan(0.0, $moyenneClasse, 'Temoin : sans statistiques, le test ne prouve rien.');

        // Tolerance a 0,2 et non a 0,01 : les statistiques de classe appliquent
        // la note d'assiduite (+0,13 pour zero absence) que la moyenne du
        // bulletin n'applique pas. Le test vise le DEFAUT — 14 au lieu de
        // 10,67 — pas le bareme d'assiduite, qui a ses propres tests et
        // dont la valeur est un reglage d'instance.
        $this->assertEqualsWithDelta(14.0, $meilleure, 0.2, 'La plus forte moyenne compte 14 (sans l ECUE), pas 10,67.');
        $this->assertEqualsWithDelta(10.0, $plusFaible, 0.2, 'La plus faible moyenne est celle de l eleve sans ECUE.');
        // Valeur ATTENDUE, et non `($meilleure + $plusFaible) / 2` : avec deux
        // eleves cette egalite-la est arithmetiquement vraie quoi qu'on casse,
        // donc elle ne prouvait rien. Sans le filtre, la classe tombe a 10,33.
        $this->assertEqualsWithDelta(
            12.0,
            $moyenneClasse,
            0.2,
            'La moyenne de la classe vaut 12 (14 et 10), pas 10,33.'
        );
        $this->assertGreaterThan(
            11.0,
            $meilleure,
            'Sans le filtre sur les statistiques, la plus forte moyenne vaut 10,67 : c est le defaut.'
        );

        $this->assertLessThanOrEqual(
            $meilleure + 0.01,
            $moyenneEleve,
            "Un eleve ne peut pas depasser la « plus forte moyenne » de sa classe : c'est le "
            .'symptome visible du filtre pose sur la moyenne mais pas sur les statistiques.'
        );
    }

    /**
     * Le QUATRIEME lecteur, et le seul qu'aucun test n'atteignait.
     *
     * `calculateStudentStatsFixed()` alimente les moyennes et les rangs de
     * `/esbtp/resultats`, et il porte DEUX chemins d'ingestion : les notes, puis
     * les moyennes manuelles — qui ECRASENT les premieres. Filtrer un seul des
     * deux ne filtre aucun des deux des qu'une ligne heritee existe. Le defaut
     * a survecu a une passe de revue parce que le correctif etait declare
     * couvert par un filtre large de 327 tests : un filtre stable prouve
     * l'absence de regression, jamais la presence d'une couverture.
     */
    public function test_les_moyennes_de_l_ecran_resultats_ecartent_l_ecue_par_ses_deux_chemins(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC647');

        $evalBts = $this->evaluationDe($bts);
        $evalEcue = $this->evaluationHeritee($bts, $ecue);

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $evalBts, 14);
        $this->noter($etudiant, $evalEcue, 4);

        // La ligne heritee : c'est elle qui emprunte le SECOND chemin, celui
        // qui ecrase. Sans elle, seul le chemin des notes serait exerce.
        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $ecue->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 4,
            'coefficient' => 1,
        ]));

        $moyennes = [];
        $rangs = [];

        app(BulletinService::class)->calculateStudentStatsFixed(
            ESBTPEtudiant::whereKey($etudiant->id)->get(),
            ESBTPNote::with('evaluation.matiere')->where('etudiant_id', $etudiant->id)->get(),
            $moyennes,
            $rangs,
            $this->classe->id,
            $this->annee->id,
            'semestre1'
        );

        $this->assertArrayHasKey($etudiant->id, $moyennes, 'Temoin : sans moyenne calculee, le test ne prouve rien.');
        $this->assertEqualsWithDelta(
            14.0,
            (float) $moyennes[$etudiant->id],
            0.01,
            "L'ecran des resultats doit rendre 14,00 comme le bulletin. Sans le filtre sur les "
            .'DEUX chemins, il rend 9,00 — et contredit le PDF pour le meme eleve.'
        );
    }

    public function test_un_eleve_dont_toutes_les_moyennes_enregistrees_sont_ecartees_retombe_sur_ses_notes(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC649');

        $avecEcue = $this->etudiantInscrit();
        $this->noter($avecEcue, $this->evaluationDe($bts), 14);

        // Sa SEULE ligne enregistree de la periode porte sur l'ECUE, et il
        // n'en a AUCUNE sur la matiere BTS : c'est ce qui rend la branche
        // atteignable. L'apercu n'ecrit rien (`persistOfficial: false`), donc
        // le decor tient jusqu'au calcul des statistiques.
        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $avecEcue->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $ecue->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 4,
            'coefficient' => 1,
        ]));

        // Saisir une note fait ecrire une ligne `esbtp_resultats` pour la
        // matiere BTS — c'est un observateur, pas l'apercu, qui la pose. On la
        // retire ici pour ISOLER la branche visee : l'eleve dont il ne reste
        // aucune moyenne enregistree retenue apres filtrage. Sans ce retrait,
        // la ligne BTS survit au filtre, `$resultatsParMatiere` n'est pas vide,
        // et la branche n'est jamais atteinte — c'est ce qui m'avait fait
        // conclure a tort qu'elle etait intestable.
        ESBTPResultat::where('etudiant_id', $avecEcue->id)
            ->where('matiere_id', $bts->id)
            ->forceDelete();

        $this->seedConfiguredBulletin(
            $avecEcue->id, $this->classe->id, $this->annee->id, 'semestre1', [$bts->id], []
        );

        $donnees = app(BulletinService::class)->genererDonneesBulletinPreview(
            $avecEcue->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $this->assertGreaterThan(
            0.0,
            (float) $donnees['moyenne_classe'],
            "L'eleve ne doit pas disparaitre des statistiques : ses notes existent, seule sa "
            .'moyenne enregistree etait posee sur une matiere etrangere.'
        );
        $this->assertEqualsWithDelta(
            14.0,
            (float) $donnees['meilleure_moyenne'],
            0.2,
            'Le repli sur les notes doit rendre 14, pas 0.'
        );
    }

    private function unSuperAdmin(): User
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->assignRole('superAdmin');

        return $user;
    }

    /**
     * Le CINQUIEME calcul de moyenne, et sa branche annuelle.
     *
     * `ESBTPResultatController::resultatEtudiant()` construit son propre
     * tableau de matieres. Les onglets semestriels le remplacent ensuite par le
     * snapshot (filtre) ; la branche annuelle d'une classe dont un seul
     * semestre est renseigne ne remplace RIEN — elle renomme les libelles. Une
     * ECUE y ressortait dans « Resultats par matiere » et pesait dans la
     * moyenne du pied, pendant que le KPI d'en-tete affichait la valeur filtree.
     */
    public function test_la_fiche_resultats_annuelle_n_affiche_pas_l_ecue(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC650');

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($bts), 14);
        $this->noter($etudiant, $this->evaluationHeritee($bts, $ecue), 4);

        $reponse = $this->actingAs($this->unSuperAdmin())
            ->get(route('esbtp.resultats.etudiant', [
                'etudiant' => $etudiant->id,
                'classe_id' => $this->classe->id,
                'annee_universitaire_id' => $this->annee->id,
                'periode' => 'annuel',
            ]));

        $reponse->assertOk();

        $matieres = $reponse->viewData('notesByMatiere') ?? [];

        // Temoin : sans la matiere BTS, le test ne prouve rien.
        $this->assertArrayHasKey($bts->id, $matieres, 'Temoin : la matiere BTS doit etre dans le tableau.');
        $this->assertArrayNotHasKey(
            $ecue->id,
            $matieres,
            'Une ECUE ne figure pas dans « Resultats par matiere » d une classe BTS.'
        );

        // La cle absente ne prouve que la LISTE. La moyenne, elle, est calculee
        // a part : un filtre pose sur l'affichage et oublie sur le calcul
        // laisserait ce test au vert avec une moyenne fausse a l'ecran.
        $this->assertEqualsWithDelta(
            14.0,
            (float) $reponse->viewData('moyenneGenerale'),
            0.01,
            'La moyenne annuelle affichee ne compte que la matiere BTS (sans le garde : 9,00).'
        );
    }

    /**
     * La bande KPI de `/esbtp/resultats`, et le defaut qu'elle cachait.
     *
     * `computeResultatsKpis()` appelle d'abord `getPreCalculatedResults()`, et
     * ne retombe sur le calcul filtre QUE si celui-ci ne rend rien. Sur une
     * instance en service `esbtp_resultats` est toujours peuplee : le chemin
     * filtre n'etait donc jamais emprunte.
     *
     * Pire : `esbtp_resultats` porte UNE LIGNE PAR MATIERE, et la boucle
     * ecrivait `$moyennes[$eleve] = $resultat->moyenne` sur chacune — la
     * DERNIERE matiere lue devenait la moyenne generale de l'eleve. Avec une
     * ECUE a 4 lue en dernier, le KPI affichait 4,00.
     */
    public function test_la_bande_kpi_agrege_toutes_les_matieres_et_ecarte_l_ecue(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC652');

        $etudiant = $this->etudiantInscrit();

        // Ordre volontaire : l'ECUE est creee EN DERNIER. C'est elle que
        // l'ancienne boucle retenait comme « moyenne generale ».
        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $bts->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 14,
            'coefficient' => 2,
        ]));

        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $ecue->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 4,
            'coefficient' => 1,
        ]));

        $kpis = app(BulletinService::class)->computeResultatsKpis(
            collect([$etudiant->id]),
            $this->classe->id,
            $this->annee->id,
            '1'
        );

        $this->assertNotNull(
            $kpis['moyenne_generale'] ?? null,
            'Temoin : sans KPI calcule, le test ne prouve rien.'
        );

        $this->assertEqualsWithDelta(
            14.0,
            (float) $kpis['moyenne_generale'],
            0.01,
            'La bande KPI ne compte que la matiere BTS. Sans le correctif elle rendait 4,00 '
            .'— la moyenne de la DERNIERE ligne lue, prise pour la moyenne generale.'
        );
    }

    /**
     * La bande KPI et la colonne « Moyenne » doivent dire la MEME chose.
     *
     * Elles s'affichent sur le meme ecran, l'une au-dessus de l'autre, et une
     * classe de moins de 50 eleves tient sur une page : l'ecole peut faire la
     * moyenne de la colonne a la main. La premiere version du correctif
     * ponderait la bande par `esbtp_resultats.coefficient` alors que la colonne
     * traite chaque matiere a egalite — deux chiffres plausibles et
     * contradictoires. Avec 8 (coef 2) et 18 (coef 1) : pondere 11,33, simple
     * 13,00.
     */
    public function test_la_bande_kpi_dit_la_meme_chose_que_la_colonne(): void
    {
        $this->monterLaClasse();
        $premiere = $this->matiereConfiguree();
        $seconde = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        $etudiant = $this->etudiantInscrit();

        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $premiere->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 8,
            'coefficient' => 2,
        ]));

        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $seconde->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 18,
            'coefficient' => 1,
        ]));

        $kpis = app(BulletinService::class)->computeResultatsKpis(
            collect([$etudiant->id]),
            $this->classe->id,
            $this->annee->id,
            '1'
        );

        $this->assertNotNull($kpis['moyenne_generale'] ?? null, 'Temoin : sans KPI, le test ne prouve rien.');
        $this->assertEqualsWithDelta(
            13.0,
            (float) $kpis['moyenne_generale'],
            0.01,
            'La bande traite chaque matiere a egalite, comme la colonne. Ponderee, elle rendrait 11,33 '
            .'sous une colonne qui affiche 13,00.'
        );
    }

    /**
     * Le mode « Toutes les classes » : le filtre y etait inerte.
     *
     * `ESBTPResultatController` transmet `classe_id = null` quand l'ecran est
     * sur « Toutes les classes ». La classe se resout donc par NOTE, via son
     * evaluation — que les deux appelants eager-loadent deja.
     */
    public function test_les_statistiques_sans_classe_selectionnee_ecartent_quand_meme_l_ecue(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC653');

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($bts), 14);
        $this->noter($etudiant, $this->evaluationHeritee($bts, $ecue), 4);

        $moyennes = [];
        $rangs = [];

        app(BulletinService::class)->calculateStudentStatsFixed(
            ESBTPEtudiant::whereKey($etudiant->id)->get(),
            ESBTPNote::with(['evaluation.matiere', 'evaluation.classe'])->where('etudiant_id', $etudiant->id)->get(),
            $moyennes,
            $rangs,
            null,   // « Toutes les classes »
            $this->annee->id,
            'semestre1'
        );

        $this->assertArrayHasKey($etudiant->id, $moyennes, 'Temoin : sans moyenne calculee, le test ne prouve rien.');
        $this->assertEqualsWithDelta(
            14.0,
            (float) $moyennes[$etudiant->id],
            0.01,
            'Sans classe selectionnee, le filtre doit tenir quand meme (sans le correctif : 9,00).'
        );
    }

    /**
     * La periode « annuel » : la branche de repli, et elle ECRIT.
     *
     * `calculateStudentAverageForPeriode()` delegue au snapshot filtre pour
     * `semestre1` / `semestre2`. Pour `annuel`, elle retombe sur un calcul
     * propre a deux chemins d'ingestion — et `BulletinAverageBackfillService`
     * ecrit son resultat dans `esbtp_bulletins.moyenne_generale`.
     *
     * NUANCE HONNETE SUR CE TEST : retire le correctif, il vire au rouge par
     * une `CoefficientMissingException`, pas par son assertion. L'ECUE atteint
     * la resolution de coefficient, qui n'en trouve aucun et leve. Rouge quand
     * meme — et l'exception prouve bien qu'elle etait comptee — mais ce n'est
     * pas le mecanisme annonce par le message d'assertion.
     */
    public function test_la_moyenne_annuelle_de_repli_ecarte_l_ecue(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC654');

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($bts), 14);
        $this->noter($etudiant, $this->evaluationHeritee($bts, $ecue), 4);

        $moyenne = app(BulletinService::class)->calculateStudentAverageForPeriode(
            $etudiant->id,
            $this->classe->id,
            $this->annee->id,
            'annuel'
        );

        $this->assertNotNull($moyenne, 'Temoin : sans moyenne, le test ne prouve rien.');
        $this->assertEqualsWithDelta(
            14.0,
            (float) $moyenne,
            0.01,
            'La moyenne annuelle de repli ne compte que la matiere BTS (sans le correctif : 9,00).'
        );
    }

    /**
     * Le SEPTIEME calcul : celui qui n'affiche pas, il DECIDE.
     *
     * `ReeinscriptionService` lit les notes de l'annee pour trancher entre
     * passage, rattrapage et redoublement. Une ECUE notee 4/20 y pesait dans la
     * moyenne ET comptait comme une matiere echouee — pour un eleve comme pour
     * une promotion entiere, via la reinscription groupee.
     */
    public function test_la_decision_de_reinscription_ne_compte_pas_l_ecue(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC651');

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($bts), 14);
        $this->noter($etudiant, $this->evaluationHeritee($bts, $ecue), 4);

        // Ce service lit la colonne TEXTE `annee_universitaire`, que le decor
        // commun ne renseigne pas. Mise a jour en masse : la passer par le
        // modele reveillerait l'observateur de notes pour rien.
        ESBTPNote::where('etudiant_id', $etudiant->id)
            ->update(['annee_universitaire' => $this->annee->name]);

        $inscription = ESBTPInscription::where('etudiant_id', $etudiant->id)->firstOrFail();

        $analyse = app(ReeinscriptionService::class)
            ->analyserSituationEtudiantParInscription($inscription);

        $this->assertNotEmpty(
            $analyse['notes'],
            'Temoin : sans note retenue, une moyenne de 14 ne prouverait rien.'
        );

        $this->assertEqualsWithDelta(
            14.0,
            (float) $analyse['moyenne_generale'],
            0.01,
            'La decision se prend sur la seule matiere BTS (sans le garde : 9,00).'
        );

        $matieresEchouees = collect($analyse['matieres_echouees'])
            ->map(fn ($ligne) => (int) $ligne['matiere']->id)
            ->all();

        $this->assertNotContains(
            $ecue->id,
            $matieresEchouees,
            "Une ECUE ne peut pas compter comme une matiere echouee d'une classe BTS : "
            .'elle plafonne le rattrapage et pousse au redoublement.'
        );
    }

    public function test_une_moyenne_manuelle_sur_une_ecue_est_refusee_dans_une_classe_bts(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC644');
        $etudiant = $this->etudiantInscrit();

        $lignePourLaMatiereBts = ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $bts->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 14,
            'coefficient' => 2,
        ]);

        $this->assertTrue(
            $lignePourLaMatiereBts->exists,
            'Temoin : une moyenne manuelle sur une matiere BTS doit passer.'
        );

        $this->expectException(ValidationException::class);

        ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $ecue->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 4,
            'coefficient' => 1,
        ]);
    }

    public function test_une_ligne_heritee_reste_modifiable_sur_sa_moyenne(): void
    {
        $this->monterLaClasse();
        $ecue = $this->uneEcue('TPGC645');
        $etudiant = $this->etudiantInscrit();

        $ligne = ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $ecue->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 4,
            'coefficient' => 1,
        ]));

        // Corriger la moyenne d'une ligne deja incoherente doit rester possible :
        // la refuser rendrait l'erreur historique inextirpable, exactement le
        // defaut que le retrait de maquette corrige par ailleurs.
        $ligne->update(['moyenne' => 12]);

        $this->assertSame(12.0, (float) $ligne->fresh()->moyenne);
    }
}
