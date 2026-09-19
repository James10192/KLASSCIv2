<?php

namespace Tests\Feature\Bts;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPClasse;
use App\Models\ESBTPNiveauEtude;
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
     * La bande KPI traite chaque matiere a egalite, comme la colonne.
     *
     * CE TEST NE COMPARE PAS LES DEUX CHIFFRES, et le nom qu'il portait
     * (« dit la meme chose que la colonne ») le laissait croire. Il n'appelle
     * que `computeResultatsKpis()` et verifie la valeur attendue a la main : si
     * la colonne changeait d'agregation demain, il resterait vert.
     *
     * ET L'EGALITE ANNONCEE N'ETAIT PAS VRAIE. Les deux chiffres ne couvrent pas
     * la meme population : la bande agrege TOUTE la cohorte depuis
     * `esbtp_resultats`, la colonne agrege LA PAGE depuis les notes. Elles ne
     * coincident que quand les deux sources concordent — c'est une propriete des
     * donnees, pas de l'ecran, et l'affirmer etait un absolu non mesure.
     *
     * Ce que ce test verrouille vraiment : la bande ne PONDERE plus par
     * `esbtp_resultats.coefficient`. Avec 8 (coef 2) et 18 (coef 1), ponderee
     * elle rendait 11,33 la ou la colonne, qui traite chaque matiere a egalite,
     * donne 13,00.
     */
    public function test_la_bande_kpi_traite_chaque_matiere_a_egalite(): void
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
            'La bande traite chaque matiere a egalite. Ponderee, elle rendrait 11,33 '
            .'la ou la colonne, elle, donne 13,00.'
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
     * LE PANNEAU D'IMPACT, SOUS LA MAIN DE L'ENSEIGNANT QUI SAISIT.
     *
     * `previewImpact()` calcule sa propre moyenne generale ponderee — il ne
     * passe ni par `BulletinService` ni par le snapshot. Aucun des douze
     * filtres poses ailleurs ne le couvrait, et personne ne l'avait remarque
     * parce qu'il ne ressemble pas aux autres : il ne moyenne pas en SQL, il
     * delegue a `NoteCalculationService` matiere par matiere.
     *
     * TANT QUE TOUS LES ECRANS MENTAIENT D'ACCORD, cela ne se voyait pas. C'est
     * cette branche qui rend le defaut visible : elle corrige le bulletin,
     * `/esbtp/resultats`, la fiche etudiant et l'accueil mobile — et ce panneau
     * aurait continue d'annoncer 10,67 pendant que tout le reste disait 14,00,
     * au moment le plus sensible du parcours.
     */
    public function test_l_apercu_d_impact_ecarte_l_ecue_de_la_moyenne_generale(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC654');

        $etudiant = $this->etudiantInscrit();
        $evalBts = $this->evaluationDe($bts);
        $evalEcue = $this->evaluationHeritee($bts, $ecue);
        $this->noter($etudiant, $evalBts, 14);
        $this->noter($etudiant, $evalEcue, 4);

        // `previewImpact()` ne lit que les evaluations PUBLIEES, et la fabrique
        // ne renseigne pas `is_published`. Pose en masse : passer par le modele
        // reveillerait le garde de coherence sur la ligne heritee, qui est
        // precisement celle qu'on veut voir entrer puis etre ecartee.
        ESBTPEvaluation::whereIn('id', [$evalBts->id, $evalEcue->id])
            ->update(['is_published' => true]);

        $reponse = $this->actingAs($this->unSuperAdminPourNotes())
            ->postJson(route('esbtp.notes.preview-impact'), [
                'etudiant_id' => $etudiant->id,
                'classe_id' => $this->classe->id,
                'matiere_id' => $bts->id,
                'periode' => 'semestre1',
                'evaluation_id' => $evalBts->id,
                'hypothetical_note' => 14,
            ]);

        $reponse->assertOk();
        $charge = $reponse->json();

        $this->assertNotNull(
            $charge['moyenne_generale_avant'] ?? null,
            'Temoin : sans moyenne generale rendue, ce test ne prouve rien.'
        );

        $this->assertEqualsWithDelta(
            14.0,
            (float) $charge['moyenne_generale_avant'],
            0.01,
            'Le panneau d\'impact ne doit compter que la matiere BTS. Sans le '
            .'filtre : 9,00 — et il contredisait alors tous les autres ecrans, '
            .'qui annoncent 14,00 depuis cette branche.'
        );
    }

    /** Un compte autorise a consulter l'apercu d'impact. */
    private function unSuperAdminPourNotes(): User
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $utilisateur = User::factory()->create();
        $utilisateur->assignRole('superAdmin');

        return $utilisateur;
    }

    /**
     * EFFACER LA MATIERE DESARMAIT AUSSI LA DECISION DE REINSCRIPTION.
     *
     * Le balayage `withTrashed()` a d'abord couvert les lecteurs de moyennes et
     * annonce « sept sites ». Il en manquait trois, ecrits dans la meme
     * branche — et celui-ci est le plus cher, parce qu'il ne montre rien : il
     * tranche entre passage, rattrapage et redoublement.
     *
     * Le filtre est en ECHEC OUVERT (`! $matiere || retenue(...)`), ce qui est
     * juste quand on ne peut pas juger. Mais une matiere effacee en douceur
     * n'est pas « injugeable » : elle est seulement invisible d'un eager-load
     * nu. Le `null` court-circuitait le `||`, et la note etrangere revenait.
     */
    public function test_effacer_la_matiere_ne_desarme_pas_la_decision_de_reinscription(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC652');

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($bts), 14);
        $this->noter($etudiant, $this->evaluationHeritee($bts, $ecue), 4);

        ESBTPNote::where('etudiant_id', $etudiant->id)
            ->update(['annee_universitaire' => $this->annee->name]);

        $ecue->delete();
        $this->assertNotNull(
            $ecue->fresh()?->deleted_at,
            'Temoin de montage : sans effacement en douceur, ce test ne prouve rien.'
        );

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
            'Sans `withTrashed()`, la matiere effacee rendait le filtre aveugle '
            .'et le 4/20 revenait dans la decision (9,00).'
        );
    }

    /**
     * Et la fiche etudiant affichait alors DEUX moyennes differentes.
     *
     * `EtudiantAcademicJourneyPresenter` est rendu sur le meme ecran que le
     * snapshot BTS. Le snapshot etait passe en `withTrashed()`, le presentateur
     * non : sur une matiere effacee, l'un ecartait et l'autre gardait.
     */
    public function test_effacer_la_matiere_ne_desarme_pas_le_parcours_etudiant(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC653');
        $etudiant = $this->etudiantInscrit();

        ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $bts->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 14,
            'coefficient' => 2,
        ]);

        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $ecue->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 4,
            'coefficient' => 1,
        ]));

        $ecue->delete();
        $this->assertNotNull($ecue->fresh()?->deleted_at, 'Temoin de montage.');

        $parcours = app(\App\Services\EtudiantAcademicJourneyPresenter::class)
            ->present($etudiant->fresh());

        // Aucun bulletin genere : `btsMetrics()` retombe sur « Resultats
        // saisis », c'est-a-dire exactement le cas que le filtre protege.
        $metrics = collect($parcours['items'])->pluck('metrics')->firstWhere('moyenne', '!==', null);

        $this->assertNotNull($metrics, 'Temoin : sans moyenne rendue, ce test ne prouve rien.');
        $this->assertSame(
            'Résultats saisis',
            $metrics['source'],
            'Temoin : le repli d\'avant-bulletin doit bien etre celui qu\'on mesure.'
        );

        $this->assertEqualsWithDelta(
            14.0,
            (float) $metrics['moyenne'],
            0.01,
            'Sans `withTrashed()`, la matiere effacee court-circuitait le filtre '
            .'et le 4/20 revenait (9,00) — pendant que le snapshot affiche a cote, '
            .'lui, annoncait 14,00.'
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

    /**
     * L'ecran « Modifier les moyennes » ecrit UNE LIGNE PAR MATIERE.
     *
     * Le garde de `ESBTPResultat` levait au milieu de cette boucle : les
     * matieres deja traitees restaient enregistrees, les suivantes jamais. Ce
     * test poste volontairement l'ECUE EN DEUXIEME, pour qu'une ligne BTS soit
     * deja ecrite quand le refus tombe — sans transaction, elle survivrait.
     */
    public function test_l_enregistrement_des_moyennes_ne_laisse_aucun_etat_partiel(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC646');
        $etudiant = $this->etudiantInscrit();

        $this->actingAs($this->unSuperAdmin());

        $reponse = $this->post(route('esbtp.bulletins.moyennes-update'), [
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'resultats' => [
                ['matiere_id' => $bts->id, 'moyenne' => 14, 'coefficient' => 2],
                ['matiere_id' => $ecue->id, 'moyenne' => 4, 'coefficient' => 1],
            ],
        ]);

        // La CLE compte : `assertSessionHasErrors()` nu passe pour n'importe
        // quelle erreur de validation, y compris une regle du `FormRequest` qui
        // changerait demain. Ce test resterait vert en ne prouvant plus rien.
        $reponse->assertSessionHasErrors('matiere_id');

        $this->assertSame(
            0,
            ESBTPResultat::where('etudiant_id', $etudiant->id)->count(),
            'Tout ou rien : sans transaction, la ligne BTS postee AVANT l\'ECUE '
            .'restait enregistree et l\'ecran devenait insauvegardable.'
        );
    }

    /**
     * TEMOIN du test precedent.
     *
     * Sans lui, un `count() === 0` serait vrai aussi bien parce que la
     * transaction a fonctionne que parce que la requete n'a jamais atteint le
     * controleur — la forme de faux-vert que ce fichier s'interdit.
     */
    public function test_l_enregistrement_des_moyennes_passe_sur_une_matiere_bts(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $this->actingAs($this->unSuperAdmin());

        $reponse = $this->post(route('esbtp.bulletins.moyennes-update'), [
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'resultats' => [
                ['matiere_id' => $bts->id, 'moyenne' => 14, 'coefficient' => 2],
            ],
        ]);

        $reponse->assertSessionHasNoErrors();

        $this->assertSame(
            1,
            ESBTPResultat::where('etudiant_id', $etudiant->id)->count(),
            'Le meme envoi, sans l\'ECUE, doit bien enregistrer sa ligne.'
        );
    }

    /**
     * L'ecran ne PROPOSE plus l'ECUE, meme quand elle porte des notes.
     *
     * L'apercu a QUATRE chemins d'ingestion vers la meme liste — deux revues
     * successives en ont compte trois, puis deux, et se sont trompees les deux
     * fois : les lignes DEJA ENREGISTREES, les notes, le catalogue, le snapshot.
     * Chacun n'AJOUTE que ce que le precedent n'a pas pose, donc filtrer un
     * chemin tardif ne retire rien de ce qu'un chemin plus tot a deja mis.
     *
     * Ce test monte les DEUX cas qui comptent : une ECUE qui porte seulement des
     * notes (chemin « notes »), et une ECUE qui porte en plus une ligne
     * `esbtp_resultats` heritee (chemin « deja enregistre », celui qui a la
     * preseance et que les deux premiers correctifs ne pouvaient pas voir).
     *
     * CE QUI SE PASSE SANS LE CORRECTIF, MESURE en retirant le filtre : ce n'est
     * pas que l'ECUE s'ajoute a la liste, c'est que **l'ecran ne s'ouvre plus du
     * tout**. L'ECUE n'a pas de coefficient sur ce couple (filiere, niveau), donc
     * `getCoefficientForCombination()` leve, et l'apercu redirige vers « Configurez
     * les coefficients avant de continuer » — pour une matiere qui n'a rien a
     * faire la, et dont configurer le coefficient ne reglerait rien. C'est
     * `assertOk()` qui echoue en premier, avant l'assertion sur la liste ; les
     * deux sont gardees, dans cet ordre.
     */
    public function test_l_apercu_des_moyennes_ne_propose_pas_une_ecue_qui_porte_des_notes(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC648');
        $etudiant = $this->etudiantInscrit();

        // L'ECUE est rattachee a la classe par les deux pivots PLATS, comme le
        // laisse une ECUE retiree de la maquette : `retirer()` ne les nettoie pas.
        $ecue->filieres()->syncWithoutDetaching([$this->filiere->id]);
        $ecue->niveaux()->syncWithoutDetaching([$this->niveau->id]);

        $evaluationBts = ESBTPEvaluation::factory()->create([
            'matiere_id' => $bts->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'status' => 'published',
            'bareme' => 20,
            'coefficient' => 2,
        ]);
        $this->noter($etudiant, $evaluationBts, 14);

        $evaluationEcue = $this->evaluationHeritee($bts, $ecue);
        $this->noter($etudiant, $evaluationEcue, 4);

        // Seconde ECUE : elle porte une ligne `esbtp_resultats` heritee, donc
        // elle entre par le chemin qui a la PRESEANCE. Sans son correctif, elle
        // n'est pas seulement proposee : `getCoefficientForCombination()` leve
        // (une ECUE n'a pas de coefficient sur un couple BTS) et l'ecran rend
        // 302 au lieu de s'ouvrir.
        $ecueEnregistree = $this->uneEcue('TPGC649');
        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $ecueEnregistree->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 4,
            'coefficient' => 1,
        ]));

        $this->actingAs($this->unSuperAdmin());

        $reponse = $this->get(route('esbtp.bulletins.moyennes-preview', [
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
        ]));

        $reponse->assertOk();

        $proposees = collect($reponse->viewData('resultatsData'))->keys()->map(fn ($id) => (int) $id);

        $this->assertTrue(
            $proposees->contains($bts->id),
            'Temoin : la matiere BTS notee doit bien etre proposee.'
        );

        $this->assertFalse(
            $proposees->contains($ecue->id),
            'L\'ECUE porte des notes : elle passait par le chemin « depuis les '
            .'notes », que le filtre pose sur l\'autre chemin ne pouvait pas retirer.'
        );

        // Celle qui porte une ligne enregistree reste AFFICHEE — la cacher
        // emporterait son bouton de suppression et la rendrait inextirpable —
        // mais elle est marquee, donc ni modifiable ni renvoyee au serveur.
        $lignes = collect($reponse->viewData('resultatsData'));

        $this->assertTrue(
            $lignes->has($ecueEnregistree->id),
            'La ligne heritee doit rester visible pour rester supprimable.'
        );

        $this->assertTrue(
            (bool) ($lignes[$ecueEnregistree->id]['intruse'] ?? false),
            'La ligne heritee doit etre marquee « hors systeme ».'
        );

        $this->assertFalse(
            (bool) ($lignes[$bts->id]['intruse'] ?? false),
            'Temoin : la matiere BTS ne doit pas etre marquee intruse.'
        );

        // L'AFFIRMATION CENTRALE DU CORRECTIF, et elle vit dans le gabarit, pas
        // dans le controleur : aucun champ de la ligne intruse n'est renvoye au
        // serveur. Sans cette assertion, qui retablirait demain un champ
        // coefficient casserait TOUT l'envoi et la suite resterait verte.
        $reponse->assertDontSee('resultats['.$ecueEnregistree->id.']', false);

        $reponse->assertSee('resultats['.$bts->id.']', false);
    }

    /**
     * La moyenne de l'accueil mobile, quand aucun bulletin n'est configure.
     *
     * Ce repli est emprunte precisement avant qu'un bulletin n'existe — le
     * moment ou une ECUE mal rangee se voit le plus. Il lisait toutes les notes
     * de l'annee sans regarder le systeme academique de la classe.
     */
    public function test_la_moyenne_de_l_accueil_mobile_ecarte_l_ecue(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC647');
        $etudiant = $this->etudiantInscrit();

        $evaluationBts = ESBTPEvaluation::factory()->create([
            'matiere_id' => $bts->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'status' => 'published',
            'bareme' => 20,
            'coefficient' => 2,
        ]);
        $this->noter($etudiant, $evaluationBts, 14);

        $evaluationEcue = $this->evaluationHeritee($bts, $ecue);
        $this->noter($etudiant, $evaluationEcue, 4);

        // ON FORCE LE REPLI, sinon ce test ne prouve rien. Le snapshot repond
        // des qu'une configuration de bulletin existe, et il est DEJA filtre :
        // une premiere version de ce test le laissait repondre et validait donc
        // le filtre d'un AUTRE calcul, pas celui qu'on corrige ici. C'est
        // exactement la forme d'assertion creuse que ce chantier a payee.
        $this->instance(
            \App\Services\ESBTP\BtsCurrentResultSnapshotService::class,
            new class extends \App\Services\ESBTP\BtsCurrentResultSnapshotService
            {
                // Le constructeur du parent exige deux services dont ce double
                // n'a que faire : il ne fait que lever.
                public function __construct()
                {
                }

                public function getAnnualSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId): array
                {
                    throw new \RuntimeException('Configuration de bulletin absente.');
                }
            }
        );

        $methode = new \ReflectionMethod(\App\Http\Controllers\DashboardController::class, 'moyenneCourante');
        $methode->setAccessible(true);

        $moyenne = $methode->invoke(
            app(\App\Http\Controllers\DashboardController::class),
            $etudiant->id,
            $this->classe->id,
            $this->annee->id,
            false
        );

        $this->assertSame(
            14.0,
            $moyenne,
            'Sans le correctif, le 4/20 de l\'ECUE tombait dans la moyenne BTS de '
            .'l\'accueil mobile et rendait 9,00.'
        );
    }

    /**
     * LES QUATRE ECRANS QUI ECRIVENT `esbtp_resultats` REFUSENT UNE CLASSE LMD.
     *
     * Aucun test ne couvrait ces gardes, et c'est PRECISEMENT pour cela qu'un
     * defaut y a survecu a quatre passes de revue : le `abort_if` de l'apercu
     * etait pose DANS un `try` dont le `catch (\RuntimeException)` attrape aussi
     * les `HttpException` de Symfony — le 422 devenait un 302 portant
     * « Cette classe est LMD. […] Configurez les coefficients avant de
     * continuer. », c'est-a-dire l'inverse du service rendu.
     *
     * Un test d'une ligne par garde l'aurait attrape du premier coup.
     */
    public function test_les_ecrans_de_moyennes_refusent_une_classe_lmd(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();

        // LE SYSTEME SE DEDUIT DU NIVEAU, il ne se pose pas a la main.
        // `ESBTPClasse::saving()` rappelle
        // `ClasseManagementService::determinerSystemeAcademique($niveau->type)`
        // des que `niveau_etude_id` est sale — donc a la creation. Une premiere
        // version de ce test posait `systeme_academique => 'LMD'` sur un niveau
        // BTS : la classe naissait BTS, l'ecran l'acceptait, et le test echouait
        // — a raison. C'est le montage qui etait faux, pas le garde.
        $niveauLmd = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'Licence']);

        $classeLmd = ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $niveauLmd->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);

        $this->assertTrue(
            $classeLmd->fresh()->isLMD(),
            'Temoin de montage : sans une classe reellement LMD, ce test ne prouve rien.'
        );

        $this->actingAs($this->unSuperAdmin());

        $apercu = $this->get(route('esbtp.bulletins.moyennes-preview', [
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classeLmd->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
        ]));

        $apercu->assertStatus(422);

        $enregistrement = $this->post(route('esbtp.bulletins.moyennes-update'), [
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classeLmd->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'resultats' => [
                ['matiere_id' => $this->matiereConfiguree()->id, 'moyenne' => 14, 'coefficient' => 2],
            ],
        ]);

        $enregistrement->assertStatus(422);

        // TEMOIN : la meme classe en BTS passe. Sans lui, un 422 rendu pour une
        // toute autre raison (route absente, permission) ferait un test vert qui
        // ne prouve rien.
        $temoin = $this->get(route('esbtp.bulletins.moyennes-preview', [
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
        ]));

        $temoin->assertOk();
    }

    /**
     * EFFACER LA MATIERE NE DOIT PAS DESARMER LE FILTRE.
     *
     * `ESBTPMatiere` est en `SoftDeletes`, et `ESBTPMatiereController::destroy()`
     * fait un `delete()`. La ligne `esbtp_resultats` survit a l'effacement.
     *
     * Les deux gardes d'ECRITURE de ce chantier resolvent deja leur matiere en
     * `withTrashed()` — les lecteurs, eux, ne le faisaient pas : un eager-load
     * nu rendait `null`, le `&& $resultat->matiere` du filtre court-circuitait,
     * et l'ECUE rentrait dans la moyenne. Le bulletin, lui, l'ecartait : 14,00
     * au PDF, 9,00 a l'ecran, pour le meme eleve. Exactement les deux chiffres
     * contradictoires que cette branche supprime par ailleurs.
     */
    public function test_effacer_la_matiere_ne_reintegre_pas_l_ecue_dans_les_moyennes(): void
    {
        $this->monterLaClasse();
        $bts = $this->matiereConfiguree();
        $ecue = $this->uneEcue('TPGC650');
        $etudiant = $this->etudiantInscrit();

        ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $bts->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 14,
            'coefficient' => 2,
        ]);

        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $ecue->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 4,
            'coefficient' => 1,
        ]));

        // LE GESTE QUI DESARMAIT TOUT : un effacement en douceur, celui que
        // l'ecran des matieres produit.
        $ecue->delete();

        $this->assertNotNull(
            $ecue->fresh()?->deleted_at,
            'Temoin de montage : sans effacement en douceur, ce test ne prouve rien.'
        );

        $moyennes = [];
        $rangs = [];

        app(BulletinService::class)->getPreCalculatedResults(
            collect([$etudiant]),
            $this->classe->id,
            $this->annee->id,
            'semestre1',
            $moyennes,
            $rangs
        );

        $this->assertSame(
            14.0,
            round((float) $moyennes[$etudiant->id], 2),
            'Sans le correctif, la matiere effacee rendait le filtre aveugle et '
            .'le 4/20 de l\'ECUE retombait dans la moyenne (9,00).'
        );
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
