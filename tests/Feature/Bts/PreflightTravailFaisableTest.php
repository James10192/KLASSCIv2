<?php

namespace Tests\Feature\Bts;

use App\Domain\AcademicPilotage\Services\BtsBulkBulletinGenerationService;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Le pre-controle ne promet et ne reclame que du faisable.
 *
 * Deux manquements observes sur esbtp-yakro, de meme nature :
 *
 * - il remettait au front la cohorte entiere, soixante-dix identifiants
 *   quand il en annoncait soixante-neuf generables. Le front decoupe la
 *   classe sur cette liste : une tranche composee uniquement d'etudiants
 *   refuses recevait 422, et la boucle s'arretait la, laissant les
 *   tranches suivantes sans partir, a chaque relance ;
 *
 * - il exigeait un professeur pour une matiere supprimee, sous le libelle
 *   « Matiere #56 » que personne ne peut retrouver, alors que le bulletin
 *   ignore cette matiere et ne l'affichera jamais.
 */
class PreflightTravailFaisableTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
    }

    private function preControle(bool $recalculate = false): array
    {
        return app(BtsBulkBulletinGenerationService::class)
            ->preflight($this->classe, (int) $this->annee->id, 'semestre1', null, $recalculate);
    }

    /**
     * Le professeur est lu depuis n'importe quel bulletin de la classe. Si
     * aucun n'existe encore, en poser un porteur : c'est le seul support
     * disponible pour cette donnee, qui est de classe et non d'etudiant.
     */
    private function professeurs(array $parMatiere): void
    {
        $porteur = ESBTPBulletin::where('classe_id', $this->classe->id)->first()
            ?? ESBTPBulletin::factory()->create([
                'etudiant_id' => ESBTPEtudiant::factory()->create()->id,
                'classe_id' => $this->classe->id,
                'annee_universitaire_id' => $this->annee->id,
                'periode' => 'semestre1',
            ]);

        $porteur->professeurs = json_encode($parMatiere);
        $porteur->save();
    }

    /**
     * La liste remise au front est celle des etudiants que la generation
     * tentera. Un bulletin deja genere n'en fait pas partie ; un blocage qu'un
     * motif peut lever, si, puisque avec le motif il passe.
     */
    public function test_la_liste_des_tranches_exclut_les_ecartes(): void
    {
        $matiere = $this->matiereConfiguree();
        $evaluation = $this->evaluationDe($matiere);

        $pret = $this->etudiantInscrit();
        $this->noter($pret, $evaluation);

        $deja = $this->etudiantInscrit();
        $this->noter($deja, $evaluation);
        ESBTPBulletin::factory()->create([
            'etudiant_id' => $deja->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne_generale' => 12.5,
        ]);

        $sansNote = $this->etudiantInscrit();

        $this->professeurs([$matiere->id => 'M. Kone']);

        $preflight = $this->preControle();

        $this->assertContains((int) $pret->id, $preflight['student_ids']);
        $this->assertContains((int) $sansNote->id, $preflight['student_ids'],
            'Un blocage qu un motif peut lever doit rester envoyable.');
        $this->assertNotContains((int) $deja->id, $preflight['student_ids'],
            'Un bulletin deja genere ne sera pas retouche : ne pas l envoyer.');
    }

    /**
     * Un blocage dur ne se leve pas : une tranche qui n'aurait que lui
     * recevrait 422 et arreterait la boucle du front.
     */
    public function test_la_liste_des_tranches_exclut_les_blocages_durs(): void
    {
        $matiere = $this->matiereConfiguree();
        $evaluation = $this->evaluationDe($matiere);

        $pret = $this->etudiantInscrit();
        $this->noter($pret, $evaluation);

        $verrouille = $this->etudiantInscrit();
        $this->noter($verrouille, $evaluation);
        ESBTPBulletin::factory()->create([
            'etudiant_id' => $verrouille->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne_generale' => 11,
            'is_published' => true,
        ]);

        $this->professeurs([$matiere->id => 'M. Kone']);

        $preflight = $this->preControle(recalculate: true);

        $this->assertSame('bulletin_locked',
            collect($preflight['blocking_errors'])->firstWhere('student_id', (int) $verrouille->id)['code'] ?? null);
        $this->assertContains((int) $pret->id, $preflight['student_ids']);
        $this->assertNotContains((int) $verrouille->id, $preflight['student_ids']);
    }

    /** Sans blocage, les deux nombres affiches cote a cote sont le meme nombre. */
    public function test_la_liste_et_le_nombre_de_generables_concordent(): void
    {
        $matiere = $this->matiereConfiguree();
        $evaluation = $this->evaluationDe($matiere);
        $this->noter($this->etudiantInscrit(), $evaluation);
        $this->noter($this->etudiantInscrit(), $evaluation);
        $this->professeurs([$matiere->id => 'M. Kone']);

        $preflight = $this->preControle();

        $this->assertSame('ready', $preflight['status']);
        $this->assertSame(2, $preflight['generatable_count']);
        $this->assertCount($preflight['generatable_count'], $preflight['student_ids']);
    }

    /**
     * L'equation que la derivation doit satisfaire, sur une classe melangee.
     *
     * La liste se calcule en SOUSTRAYANT les ecartes et les blocages durs de
     * la cohorte. Une branche ajoutee demain qui ferait `continue` sans rien
     * pousser dans `skipped` ni `blockingErrors` glisserait donc son etudiant
     * DANS la liste, pas hors d'elle : il partirait dans une tranche que le
     * serveur refuse, en 422, et la boucle du front s'arreterait la.
     *
     * C'est l'inverse du mode de panne de l'accumulateur qu'elle remplace, et
     * c'est pourquoi « aucun etudiant ne disparait » ne garderait rien ici.
     */
    public function test_la_liste_ne_contient_que_du_faisable(): void
    {
        $matiere = $this->matiereConfiguree();
        $evaluation = $this->evaluationDe($matiere);

        $generable = $this->etudiantInscrit();
        $this->noter($generable, $evaluation);

        $deja = $this->etudiantInscrit();
        $this->noter($deja, $evaluation);
        ESBTPBulletin::factory()->create([
            'etudiant_id' => $deja->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne_generale' => 12.5,
        ]);

        $souple = $this->etudiantInscrit();

        $this->professeurs([$matiere->id => 'M. Kone']);

        $preflight = $this->preControle();

        $souples = collect($preflight['blocking_errors'])
            ->reject(fn ($b) => in_array($b['code'] ?? '', BtsBulkBulletinGenerationService::HARD_BLOCK_CODES, true))
            ->pluck('student_id')
            ->filter()
            ->unique();

        $this->assertContains((int) $souple->id, $souples->all());
        $this->assertCount(
            $preflight['generatable_count'] + $souples->count(),
            $preflight['student_ids'],
            'La liste vaut les generables plus les blocages qu un motif leve, ni plus ni moins.'
        );
        $this->assertSame([], array_values(array_intersect(
            $preflight['student_ids'],
            collect($preflight['skipped'])->pluck('student_id')->all()
        )), 'Un ecarte ne doit jamais etre envoye.');
    }

    /**
     * Une matiere mise a la corbeille garde ses notes, mais le bulletin ne la
     * montrera pas. Exiger son professeur bloquait la classe sur un libelle
     * introuvable, pour une case que rien ne permet de remplir.
     */
    public function test_une_matiere_supprimee_ne_reclame_pas_de_professeur(): void
    {
        $vivante = $this->matiereConfiguree();
        $supprimee = $this->matiereConfiguree();

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($vivante));
        $this->noter($etudiant, $this->evaluationDe($supprimee), 11);

        $this->professeurs([$vivante->id => 'M. Kone']);
        $supprimee->delete();

        $preflight = $this->preControle();

        $this->assertSame([], $preflight['missing_professeurs']);
        $this->assertNotContains('professeurs_missing',
            collect($preflight['blocking_errors'])->pluck('code')->all());
    }
}
