<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Le rang d'une classe de tronc commun se calcule sur la cohorte des phases.
 *
 * Un etudiant oriente en specialite voit son inscription suivre : sa colonne
 * `classe_id` ne designe plus le tronc commun. Le rang etait calcule sur
 * `inscriptions.classe_id`, donc la cohorte du tronc commun etait VIDE et la
 * colonne « Rang » affichait « N/A » pour toute la classe -- pendant que les
 * moyennes, elles, s'affichaient, puisqu'elles viennent d'une autre source.
 */
class RangTroncCommunTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
    }

    /**
     * Oriente un etudiant : son inscription passe en specialite, une phase
     * garde la trace de son semestre 1 en tronc commun.
     */
    private function etudiantOriente(ESBTPClasse $specialite, float $note): int
    {
        $etudiant = $this->etudiantInscrit();
        $inscription = ESBTPInscription::where('etudiant_id', $etudiant->id)->firstOrFail();

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $this->classe->id,
            'filiere_id' => $this->filiere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $specialite->id,
            'filiere_id' => $specialite->filiere_id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        $inscription->update(['classe_id' => $specialite->id]);
        $this->noter($etudiant, $this->evaluationDe($this->matiereConfiguree()), $note);

        return (int) $etudiant->id;
    }

    private function classeDeSpecialite(): ESBTPClasse
    {
        $filiere = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => false,
            'parent_id' => $this->filiere->id,
        ]);

        return ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
    }

    public function test_le_rang_du_tronc_commun_survit_a_l_orientation(): void
    {
        $specialite = $this->classeDeSpecialite();
        $premier = $this->etudiantOriente($specialite, 16);
        $second = $this->etudiantOriente($specialite, 11);

        $rangs = app(RankingService::class)
            ->calculerRangsClasse((int) $this->classe->id, (int) $this->annee->id, 'semestre1');

        $this->assertSame(2, $rangs['total'], 'La cohorte du tronc commun ne doit pas etre vide apres orientation.');

        $parEtudiant = $rangs['rows']->keyBy('etudiant_id');
        $this->assertSame(1, $parEtudiant[$premier]['rang']);
        $this->assertSame(2, $parEtudiant[$second]['rang']);
    }

    /**
     * L'ecran fautif etait en periode « Annuel », et c'est le cas le plus
     * fragile : les cohortes semestrielles font valoir « annuel » pour le
     * semestre 2, ou ces etudiants ne sont plus dans le tronc commun.
     */
    public function test_le_rang_annuel_du_tronc_commun_n_est_pas_vide(): void
    {
        $specialite = $this->classeDeSpecialite();
        $this->etudiantOriente($specialite, 16);
        $this->etudiantOriente($specialite, 11);

        $rangs = app(RankingService::class)
            ->calculerRangsClasse((int) $this->classe->id, (int) $this->annee->id, 'annuel');

        $this->assertSame(2, $rangs['total'],
            'En annuel aussi, le tronc commun garde les etudiants qu il a portes au semestre 1.');
        $this->assertSame([1, 2], $rangs['rows']->pluck('rang')->sort()->values()->all());
    }

    /**
     * Une classe ORDINAIRE garde exactement la cohorte d'aujourd'hui.
     *
     * C'est la quasi-totalite des classes des six ecoles, et elle emprunte
     * l'autre branche du resolveur : sans phase ni lien d'origine, la
     * chronologie est vide et la classe de rattachement retombe sur celle de
     * l'inscription. Monter ce test sur une filiere de tronc commun, comme le
     * fait le decor par defaut, ne prouverait donc rien de ce qu'il annonce.
     */
    public function test_une_classe_ordinaire_est_inchangee(): void
    {
        $ordinaire = ESBTPClasse::factory()->create([
            'filiere_id' => ESBTPFiliere::factory()->create([
                'is_tronc_commun' => false,
                'parent_id' => null,
            ])->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);

        $attendus = [];
        foreach ([14, 9] as $note) {
            $etudiant = ESBTPEtudiant::factory()->create();
            ESBTPInscription::factory()->create([
                'etudiant_id' => $etudiant->id,
                'classe_id' => $ordinaire->id,
                'annee_universitaire_id' => $this->annee->id,
                'status' => 'active',
                'workflow_step' => 'etudiant_cree',
            ]);
            $attendus[] = (int) $etudiant->id;
        }
        sort($attendus);

        foreach (['semestre1', 'semestre2', 'annuel'] as $periode) {
            $rangs = app(RankingService::class)
                ->calculerRangsClasse((int) $ordinaire->id, (int) $this->annee->id, $periode);

            $this->assertSame(
                $attendus,
                $rangs['rows']->pluck('etudiant_id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
                "Periode $periode : la cohorte doit etre exactement les inscrits de la classe."
            );
        }
    }
}
