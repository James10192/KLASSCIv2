<?php

namespace Tests\Feature\Notes;

use App\Domain\Notes\RecalculApresDeplacement;
use App\Http\Controllers\API\CLI\CLIEvaluationDeplacementController;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Ce qui arrive a `esbtp_resultats` quand une evaluation change de SEMESTRE.
 *
 * `periode` est une coordonnee de la cle d'`esbtp_resultats` au meme titre que
 * `matiere_id` : un changement de semestre laisse exactement le meme agregat
 * perime des deux cotes. Les deux endpoints qui le font en lot ont ete manques
 * a la premiere passe du correctif, puis corriges — mais sans une seule
 * assertion, ce qui revenait a annoncer un correctif sans le prouver.
 *
 * **CINQ des six tests** tombent si l'appel a `pourUnLotDePeriodes` est retire —
 * mesure en le retirant, marqueur verifie avant et apres le run. Le sixieme,
 * « le semestre des notes est ecrit en entier », reste vert : il garde
 * l'ENCODAGE de `esbtp_notes.semestre`, pas le recalcul. Lui tombe si
 * `ESBTPNote::realignerLeSemestre()` reecrit la chaine, et il n'est alors pas
 * seul : `DeplacementPeriodeCliTest` tombe avec lui.
 *
 * **Ce docbloc a dit « trois » alors qu'il y en avait six**, parce qu'il datait
 * d'avant l'ajout de trois tests et n'a pas suivi. C'est le defaut que ce
 * chantier a lui-meme traite comme bloquant un tour plus tot : un compte faux
 * ici ferme l'enquete suivante — le prochain qui ne verra que deux tests tomber
 * croira qu'il en manque un, et cherchera au mauvais endroit. Rejouez le retrait
 * avant de toucher ce chiffre.
 */
class DeplacementDePeriodeEnLotTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    /** @test */
    public function un_changement_de_semestre_rafraichit_les_deux_cotes(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        // Semestre 1 : deux notes, moyenne 15.
        $reste = $this->evaluationDe($matiere);
        $this->noter($etudiant, $reste, 10);
        $partante = $this->evaluationDe($matiere);
        $this->noter($etudiant, $partante, 20);

        // Semestre 2 : une note, moyenne 8.
        $enS2 = $this->evaluationDe($matiere);
        $enS2->update(['periode' => 'semestre2']);
        $this->noter($etudiant, $enS2, 8);

        $this->assertSame(15.0, $this->moyenne($etudiant->id, $matiere->id, 'semestre1'));
        $this->assertSame(8.0, $this->moyenne($etudiant->id, $matiere->id, 'semestre2'));

        $reponse = $this->deplacerVers([$partante->id], 'semestre2');

        // Le cote qu'on QUITTE : il reste une note (10), donc il est recalcule.
        // Sans recalcul il serait reste a 15, et cette valeur perimee l'emporte
        // sur les notes a l'affichage comme au bulletin.
        $this->assertSame(10.0, $this->moyenne($etudiant->id, $matiere->id, 'semestre1'));

        // Le cote qu'on REJOINT : (8 + 20) / 2.
        $this->assertSame(14.0, $this->moyenne($etudiant->id, $matiere->id, 'semestre2'));

        $this->assertFalse($reponse['recalcul_reporte']);
        $this->assertSame([], $reponse['perimetres_reportes']);
        $this->assertGreaterThan(0, $reponse['recalculs_tentes']);

        // Les deux identifiants qu'exige `POST /api/cli/notes/recompute` sont
        // dans la reponse : le message de repli renvoie vers cet endpoint, il
        // faut donc pouvoir le composer sans aller rechercher la classe.
        $this->assertSame($this->classe->id, $reponse['traitees'][0]['classe_id']);
        $this->assertSame($this->annee->id, $reponse['traitees'][0]['annee_universitaire_id']);
    }

    /** @test */
    public function au_dela_du_plafond_rien_n_est_recalcule_et_le_perimetre_est_rendu(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $lourde = $this->evaluationDe($matiere);
        $lourde->update(['periode' => 'semestre2']);
        $this->posterDesNotesEnMasse($lourde, $etudiant->id, RecalculApresDeplacement::PLAFOND_NOTES_PAR_APPEL + 1);

        $reponse = $this->deplacerVers([$lourde->id], 'semestre1');

        // Le deplacement, lui, est acquis : c'est le RECALCUL qui est reporte.
        $this->assertSame('semestre1', ESBTPEvaluation::find($lourde->id)->periode);

        $this->assertTrue($reponse['recalcul_reporte']);
        $this->assertSame(0, $reponse['recalculs_tentes']);

        // Et le perimetre reporte porte exactement ce qu'il faut rejouer.
        $this->assertCount(1, $reponse['perimetres_reportes']);
        $reporte = $reponse['perimetres_reportes'][0];
        $this->assertSame('perimetre_trop_lourd', $reporte['raison']);
        $this->assertSame($this->classe->id, $reporte['classe_id']);
        $this->assertSame($this->annee->id, $reporte['annee_universitaire_id']);
        $this->assertSame(
            ['semestre1', 'semestre2'],
            collect($reporte['periodes'])->sort()->values()->all(),
            'les deux cotes du deplacement doivent etre rejoues'
        );
    }

    /** @test */
    public function une_classe_lourde_placee_en_tete_ne_bloque_pas_les_classes_legeres(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        // Classe lourde, creee EN PREMIER pour passer en tete du lot : elle
        // pese exactement le budget de la requete. Servie dans l'ordre
        // d'arrivee, elle l'epuisait a elle seule et la classe legere derriere
        // elle etait reportee — le defaut qu'un plafond par classe avait
        // d'abord tente de fermer, et qu'il ne fermait plus.
        $autreClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
        $autreMatiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $lourde = ESBTPEvaluation::factory()->create([
            'matiere_id' => $autreMatiere->id,
            'classe_id' => $autreClasse->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre2',
            'status' => 'published',
            'bareme' => 20,
            'coefficient' => 1,
        ]);
        $this->posterDesNotesEnMasse($lourde, $etudiant->id, RecalculApresDeplacement::PLAFOND_NOTES_PAR_APPEL, $autreClasse->id);

        // Classe legere : une seule note.
        $legere = $this->evaluationDe($matiere);
        $legere->update(['periode' => 'semestre2']);
        $this->noter($etudiant, $legere, 12);
        $this->assertSame(12.0, $this->moyenne($etudiant->id, $matiere->id, 'semestre2'));

        $reponse = $this->deplacerVers([$lourde->id, $legere->id], 'semestre1');

        $this->assertSame(12.0, $this->moyenne($etudiant->id, $matiere->id, 'semestre1'));
        $this->assertGreaterThan(0, $reponse['recalculs_tentes']);

        $this->assertTrue($reponse['recalcul_reporte']);
        $this->assertCount(1, $reponse['perimetres_reportes']);
        $this->assertSame($autreClasse->id, $reponse['perimetres_reportes'][0]['classe_id']);
        $this->assertSame('budget_de_la_requete_epuise', $reponse['perimetres_reportes'][0]['raison']);
    }

    /** @test */
    public function le_budget_global_de_la_requete_reporte_ce_qui_depasse(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        // Plusieurs classes, chacune bien sous le budget, mais dont la somme le
        // depasse. Sans borne globale, toutes partaient en recalcul dans une
        // seule requete HTTP.
        $parClasse = intdiv(RecalculApresDeplacement::PLAFOND_NOTES_PAR_APPEL, 3) + 1;
        $combien = (int) ceil(RecalculApresDeplacement::PLAFOND_NOTES_PAR_APPEL / $parClasse) + 1;

        $ids = [];
        for ($i = 0; $i < $combien; $i++) {
            $classe = ESBTPClasse::factory()->create([
                'filiere_id' => $this->filiere->id,
                'niveau_etude_id' => $this->niveau->id,
                'annee_universitaire_id' => $this->annee->id,
            ]);
            $evaluation = ESBTPEvaluation::factory()->create([
                'matiere_id' => $matiere->id,
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $this->annee->id,
                'periode' => 'semestre2',
                'status' => 'published',
                'bareme' => 20,
                'coefficient' => 1,
            ]);
            $this->posterDesNotesEnMasse($evaluation, $etudiant->id, $parClasse, $classe->id);
            $ids[] = $evaluation->id;
        }

        $reponse = $this->deplacerVers($ids, 'semestre1');

        $this->assertTrue($reponse['recalcul_reporte']);

        // Aucune classe ne depasse a elle seule le budget : tout ce qui est
        // reporte l'est donc parce que le budget de la requete est epuise.
        $raisons = array_column($reponse['perimetres_reportes'], 'raison');
        $this->assertNotEmpty($raisons);
        $this->assertSame(['budget_de_la_requete_epuise'], array_values(array_unique($raisons)));

        // Et ce qui tenait dans le budget a bien ete traite : le report n'est
        // pas un refus global deguise.
        $this->assertGreaterThan(0, $reponse['recalculs_tentes']);
        $this->assertLessThan($combien, count($reponse['perimetres_reportes']));
    }

    /** @test */
    public function le_semestre_des_notes_est_ecrit_en_entier_et_non_en_chaine(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $evaluation = $this->evaluationDe($matiere);
        $evaluation->update(['periode' => 'semestre2']);
        $this->noter($etudiant, $evaluation, 11);

        $this->deplacerVers([$evaluation->id], 'semestre1');

        // C'est le predicat exact de la categorie 2 d'
        // `evaluations:sync-notes --clean-resultats`, qui SUPPRIME ce qu'elle ne
        // retrouve pas. En MySQL `'semestre1' = 1` vaut 0 : ecrire la chaine
        // rendait la note introuvable et l'agregat supprimable.
        $this->assertSame(
            1,
            DB::table('esbtp_notes')->where('evaluation_id', $evaluation->id)->where('semestre', 1)->count(),
            'la note doit etre retrouvee par une comparaison a l entier 1'
        );
    }

    /** @test */
    public function un_agregat_orphelin_n_est_signale_qu_une_fois(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        // Deux evaluations de la MEME coordonnee, deplacees dans le meme lot.
        $une = $this->evaluationDe($matiere);
        $this->noter($etudiant, $une, 10);
        $deux = $this->evaluationDe($matiere);
        $this->noter($etudiant, $deux, 14);

        $this->assertSame(12.0, $this->moyenne($etudiant->id, $matiere->id, 'semestre1'));

        $reponse = $this->deplacerVers([$une->id, $deux->id], 'semestre2');

        // Le semestre 1 n'a plus aucune note : son agregat devient orphelin. Une
        // seule ligne existe en base, elle ne doit etre signalee qu'une fois —
        // `pour()` etant appele par evaluation, elle l'etait deux fois.
        $this->assertCount(1, $reponse['agregats_orphelins']);
        $this->assertSame(1, ESBTPResultat::where('etudiant_id', $etudiant->id)
            ->where('matiere_id', $matiere->id)
            ->where('periode', 'semestre1')
            ->count());
    }

    /**
     * Des notes posees par `insert()`, donc sans observer et sans recalcul : ce
     * qu'on veut ici est un VOLUME, pas un jeu de donnees realiste. Le plafond
     * compte des lignes d'`esbtp_notes` — c'est exactement ce que la requete
     * de production voit.
     *
     * Une note par eleve, pas N notes du meme : la base garantit depuis
     * septembre 2026 une seule note vivante par (eleve, evaluation). Les eleves
     * au-dela du premier sont des identifiants sans fiche, d'ou les cles
     * etrangeres suspendues le temps de l'insertion.
     */
    private function posterDesNotesEnMasse(ESBTPEvaluation $evaluation, int $etudiantId, int $combien, ?int $classeId = null): void
    {
        $lignes = [];
        for ($i = 0; $i < $combien; $i++) {
            $lignes[] = [
                'evaluation_id' => $evaluation->id,
                'etudiant_id' => $i === 0 ? $etudiantId : 900000 + $evaluation->id * 1000 + $i,
                'matiere_id' => $evaluation->matiere_id,
                'classe_id' => $classeId ?? $this->classe->id,
                'note' => 10,
                'is_absent' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Schema::disableForeignKeyConstraints();
        foreach (array_chunk($lignes, 200) as $paquet) {
            ESBTPNote::insert($paquet);
        }
        Schema::enableForeignKeyConstraints();
    }

    /**
     * @param  array<int,int>  $evaluationIds
     * @return array<string,mixed>
     */
    private function deplacerVers(array $evaluationIds, string $periode): array
    {
        $requete = Request::create('/', 'POST', [
            'evaluation_ids' => $evaluationIds,
            'periode' => $periode,
            'dry_run' => false,
        ]);

        $requete->setUserResolver(fn () => new class extends User
        {
            public function __construct()
            {
                parent::__construct();
                $this->setRawAttributes(['id' => 1]);
            }

            public function tokenCan(string $ability): bool
            {
                return $ability === 'cli:admin';
            }
        });

        $reponse = app(CLIEvaluationDeplacementController::class)->deplacer($requete);
        $donnees = $reponse->getData(true);
        $this->assertSame(200, $reponse->getStatusCode(), json_encode($donnees));

        return $donnees['data'];
    }

    private function moyenne(int $etudiantId, int $matiereId, string $periode): ?float
    {
        $valeur = ESBTPResultat::where('etudiant_id', $etudiantId)
            ->where('classe_id', $this->classe->id)
            ->where('matiere_id', $matiereId)
            ->where('periode', $periode)
            ->where('annee_universitaire_id', $this->annee->id)
            ->value('moyenne');

        return $valeur === null ? null : (float) $valeur;
    }
}
