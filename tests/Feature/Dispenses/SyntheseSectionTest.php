<?php

namespace Tests\Feature\Dispenses;

use App\Domain\Dispenses\DispenseLookup;
use App\Domain\Dispenses\DispenseService;
use App\Models\ESBTPResultatMatiere;
use App\Models\User;
use App\Services\BulletinSectionSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * La ligne de synthese d'une section, et la portee d'une dispense de semestre.
 *
 * Deux defauts trouves par une revue adversariale APRES la premiere livraison.
 * Tous deux produisaient un chiffre faux sans lever la moindre erreur, sur un
 * document remis a une famille — exactement ce que ce chantier corrige
 * ailleurs.
 */
class SyntheseSectionTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    /** Une ligne de bulletin telle que le service la fabrique. */
    private function ligne(int $matiereId, ?float $moyenne, int $coef, string $statut): object
    {
        return (object) [
            'matiere_id' => $matiereId,
            'moyenne' => $moyenne,
            'coefficient' => $coef,
            'statut' => $statut,
            'rang' => null,
        ];
    }

    /**
     * @test
     *
     * Le defaut : le coefficient d'une matiere dispensee restait au
     * denominateur avec zero point au numerateur. La ligne imprimait une
     * moyenne, un coefficient et un produit qui ne se divisaient pas entre eux.
     */
    public function la_ligne_de_synthese_ne_compte_que_les_matieres_notees(): void
    {
        $lignes = [
            $this->ligne(1, 14.0, 2, ESBTPResultatMatiere::STATUT_NOTE),
            $this->ligne(2, 13.0, 2, ESBTPResultatMatiere::STATUT_NOTE),
            $this->ligne(3, null, 4, ESBTPResultatMatiere::STATUT_DISPENSE),
            $this->ligne(4, null, 3, ESBTPResultatMatiere::STATUT_NON_NOTE),
        ];

        $synthese = app(BulletinSectionSummary::class)->forSection($lignes, [], 13.5);

        // Seules les deux matieres notees comptent : 2 + 2.
        $this->assertSame(4.0, $synthese['coefficient']);
        // 14x2 + 13x2 = 54 ... mais sur 4 de coefficient, pas sur 9.
        $this->assertSame(54.0, $synthese['weighted']);

        // Le document doit se tenir : produit / coefficient == moyenne imprimee.
        $this->assertEqualsWithDelta(
            13.5,
            $synthese['weighted'] / $synthese['coefficient'],
            0.001,
            'La ligne de synthèse doit être arithmétiquement cohérente.'
        );
    }

    /** @test */
    public function une_section_entierement_dispensee_ne_divise_pas_par_zero(): void
    {
        $lignes = [$this->ligne(1, null, 4, ESBTPResultatMatiere::STATUT_DISPENSE)];

        $synthese = app(BulletinSectionSummary::class)->forSection($lignes, [], null);

        $this->assertSame(0.0, $synthese['coefficient']);
        $this->assertSame(0.0, $synthese['weighted']);
        $this->assertNull($synthese['moyenne']);
    }

    /**
     * @test
     *
     * Les heures d'absence restent comptees : ce sont des heures reellement
     * manquees, la ligne les affiche, et les retirer du total rendrait la
     * colonne fausse.
     */
    public function les_heures_d_absence_d_une_matiere_dispensee_restent_comptees(): void
    {
        $lignes = [
            $this->ligne(1, 12.0, 2, ESBTPResultatMatiere::STATUT_NOTE),
            $this->ligne(2, null, 4, ESBTPResultatMatiere::STATUT_DISPENSE),
        ];

        $synthese = app(BulletinSectionSummary::class)->forSection($lignes, [
            1 => ['total_heures' => 3],
            2 => ['total_heures' => 5],
        ], 12.0);

        $this->assertSame(8.0, $synthese['absences']);
        $this->assertSame(2.0, $synthese['coefficient']);
    }

    /**
     * @test
     *
     * Le second defaut : une dispense du seul premier semestre retirait la
     * matiere du bulletin ANNUEL. L'eleve note au second semestre voyait sa
     * moyenne annuelle monter sur un travail qu'il avait rendu.
     */
    public function une_dispense_de_semestre_ne_retire_pas_la_matiere_de_l_annee(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();
        $matiere = $this->matiereConfiguree();

        app(DispenseService::class)->accorder(
            (int) $etudiant->id, (int) $matiere->id, (int) $this->annee->id,
            'semestre1', 'Validée au premier semestre ailleurs', User::factory()->create(),
        );

        $lookup = app(DispenseLookup::class);

        // Elle porte bien sur le semestre 1, et pas sur le semestre 2.
        $this->assertArrayHasKey((int) $matiere->id,
            $lookup->pourEtudiantEtSemestre((int) $etudiant->id, (int) $this->annee->id, 1));
        $this->assertArrayNotHasKey((int) $matiere->id,
            $lookup->pourEtudiantEtSemestre((int) $etudiant->id, (int) $this->annee->id, 2));

        // Et surtout : elle ne retire PAS la matiere de l'annee.
        $this->assertArrayNotHasKey((int) $matiere->id,
            $lookup->pourEtudiantEtSemestre((int) $etudiant->id, (int) $this->annee->id, null),
            "Une dispense d'un seul semestre ne doit pas retirer la matière de l'année.");
    }

    /**
     * @test
     *
     * Deux dispenses de semestre qui couvrent les deux moities, elles, retirent
     * bien la matiere de l'annee. Cela ne se voit qu'en regardant l'ensemble
     * des lignes : une seule ne peut pas en decider.
     */
    public function deux_dispenses_de_semestre_retirent_la_matiere_de_l_annee(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();
        $matiere = $this->matiereConfiguree();
        $acteur = User::factory()->create();
        $service = app(DispenseService::class);

        foreach (['semestre1', 'semestre2'] as $periode) {
            $service->accorder(
                (int) $etudiant->id, (int) $matiere->id, (int) $this->annee->id,
                $periode, 'Validée ailleurs au '.$periode, $acteur,
            );
        }

        $this->assertArrayHasKey((int) $matiere->id,
            app(DispenseLookup::class)->pourEtudiantEtSemestre((int) $etudiant->id, (int) $this->annee->id, null));
    }

    /** @test */
    public function une_dispense_annuelle_retire_bien_la_matiere_de_l_annee(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();
        $matiere = $this->matiereConfiguree();

        app(DispenseService::class)->accorder(
            (int) $etudiant->id, (int) $matiere->id, (int) $this->annee->id,
            null, 'Dispensée pour toute l\'année', User::factory()->create(),
        );

        $lookup = app(DispenseLookup::class);

        foreach ([1, 2, null] as $semestre) {
            $this->assertArrayHasKey((int) $matiere->id,
                $lookup->pourEtudiantEtSemestre((int) $etudiant->id, (int) $this->annee->id, $semestre));
        }
    }
}
