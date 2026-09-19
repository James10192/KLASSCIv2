<?php

namespace Tests\Feature\Bts;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPResultat;
use App\Models\ESBTPUniteEnseignement;
use App\Services\BulletinService;
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
        $this->assertEqualsWithDelta(
            ($meilleure + $plusFaible) / 2,
            $moyenneClasse,
            0.01,
            'La moyenne de la classe est la moyenne des deux eleves.'
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
