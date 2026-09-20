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
 * Les trois tests tombent si l'appel a `pourUnLotDePeriodes` est retire, ou si
 * le plafond redevient un plafond de LOT au lieu d'un plafond par classe.
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
        $this->posterDesNotesEnMasse($lourde, $etudiant->id, RecalculApresDeplacement::PLAFOND_NOTES_PAR_CLASSE + 1);

        $reponse = $this->deplacerVers([$lourde->id], 'semestre1');

        // Le deplacement, lui, est acquis : c'est le RECALCUL qui est reporte.
        $this->assertSame('semestre1', ESBTPEvaluation::find($lourde->id)->periode);

        $this->assertTrue($reponse['recalcul_reporte']);
        $this->assertSame(0, $reponse['recalculs_tentes']);

        // Et le perimetre reporte porte exactement ce qu'il faut rejouer.
        $this->assertCount(1, $reponse['perimetres_reportes']);
        $reporte = $reponse['perimetres_reportes'][0];
        $this->assertSame($this->classe->id, $reporte['classe_id']);
        $this->assertSame($this->annee->id, $reporte['annee_universitaire_id']);
        $this->assertSame(
            ['semestre1', 'semestre2'],
            collect($reporte['periodes'])->sort()->values()->all(),
            'les deux cotes du deplacement doivent etre rejoues'
        );
    }

    /** @test */
    public function le_plafond_porte_sur_la_classe_et_non_sur_le_lot(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        // Classe legere : une seule note, tres en dessous du plafond.
        $legere = $this->evaluationDe($matiere);
        $legere->update(['periode' => 'semestre2']);
        $this->noter($etudiant, $legere, 12);
        $this->assertSame(12.0, $this->moyenne($etudiant->id, $matiere->id, 'semestre2'));

        // Classe lourde, dans le MEME lot : a elle seule au-dela du plafond.
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
        $this->posterDesNotesEnMasse($lourde, $etudiant->id, RecalculApresDeplacement::PLAFOND_NOTES_PAR_CLASSE + 1, $autreClasse->id);

        $reponse = $this->deplacerVers([$legere->id, $lourde->id], 'semestre1');

        // Sur un plafond de LOT, ce lot-ci depassait et AUCUNE des deux classes
        // n'etait recalculee. C'est ce que ce test interdit : la classe legere
        // est traitee, seule la lourde est rendue a l'operateur.
        $this->assertSame(12.0, $this->moyenne($etudiant->id, $matiere->id, 'semestre1'));
        $this->assertGreaterThan(0, $reponse['recalculs_tentes']);

        $this->assertTrue($reponse['recalcul_reporte']);
        $this->assertCount(1, $reponse['perimetres_reportes']);
        $this->assertSame($autreClasse->id, $reponse['perimetres_reportes'][0]['classe_id']);
    }

    /**
     * Des notes posees par `insert()`, donc sans observer et sans recalcul : ce
     * qu'on veut ici est un VOLUME, pas un jeu de donnees realiste. Le plafond
     * compte des lignes d'`esbtp_notes` — c'est exactement ce que la requete
     * de production voit, et la table ne porte aucune unicite
     * (evaluation, etudiant) qui l'interdirait.
     */
    private function posterDesNotesEnMasse(ESBTPEvaluation $evaluation, int $etudiantId, int $combien, ?int $classeId = null): void
    {
        $lignes = [];
        for ($i = 0; $i < $combien; $i++) {
            $lignes[] = [
                'evaluation_id' => $evaluation->id,
                'etudiant_id' => $etudiantId,
                'matiere_id' => $evaluation->matiere_id,
                'classe_id' => $classeId ?? $this->classe->id,
                'note' => 10,
                'is_absent' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($lignes, 200) as $paquet) {
            ESBTPNote::insert($paquet);
        }
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
