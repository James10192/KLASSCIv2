<?php

namespace Tests\Feature\Dispenses;

use App\Domain\Dispenses\DispenseService;
use App\Domain\Dispenses\Models\ESBTPDispense;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Les invariants d'une dispense : pas de recouvrement, pas de suppression.
 *
 * BTS uniquement.
 */
class DispenseServiceTest extends TestCase
{
    use RefreshDatabase;

    private DispenseService $service;

    private ESBTPEtudiant $etudiant;

    private ESBTPMatiere $matiere;

    private ESBTPAnneeUniversitaire $annee;

    private User $acteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DispenseService::class);
        $this->acteur = User::factory()->create();
        $this->etudiant = ESBTPEtudiant::factory()->create();
        $this->matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create();
    }

    private function accorder(?string $periode, string $motif = 'Validée au parcours antérieur'): ESBTPDispense
    {
        return $this->service->accorder(
            (int) $this->etudiant->id,
            (int) $this->matiere->id,
            (int) $this->annee->id,
            $periode,
            $motif,
            $this->acteur,
        );
    }

    /** @test */
    public function deux_semestres_differents_ne_se_recouvrent_pas(): void
    {
        $s1 = $this->accorder('semestre1', 'Validée au premier semestre ailleurs');
        $s2 = $this->accorder('semestre2', 'Validée au second semestre ailleurs');

        $this->assertNotSame($s1->id, $s2->id);
        $this->assertSame(2, ESBTPDispense::query()->active()->count());
    }

    /** @test */
    public function une_dispense_annuelle_recouvre_un_semestre_deja_dispense(): void
    {
        $this->accorder('semestre1');

        $this->expectException(ValidationException::class);

        $this->accorder(null, 'Dispense annuelle demandée par la direction');
    }

    /** @test */
    public function un_semestre_ne_peut_pas_etre_dispense_deux_fois(): void
    {
        $this->accorder('semestre1');

        $this->expectException(ValidationException::class);

        $this->accorder('semestre1', 'Second motif, même semestre');
    }

    /**
     * @test
     *
     * Le point qui distingue une revocation d'une suppression : la ligne reste,
     * son motif d'origine aussi, et la place se libere pour une nouvelle
     * decision.
     */
    public function revoquer_conserve_la_ligne_et_libere_la_periode(): void
    {
        $dispense = $this->accorder(null, 'Motif initial de la dispense');

        $revoquee = $this->service->revoquer($dispense, 'Accordée par erreur, à corriger', $this->acteur);

        $this->assertFalse($revoquee->estActive());
        $this->assertSame('Motif initial de la dispense', $revoquee->motif);
        $this->assertSame('Accordée par erreur, à corriger', $revoquee->motif_revocation);
        $this->assertSame((int) $this->acteur->id, (int) $revoquee->revoquee_par);
        $this->assertDatabaseHas('esbtp_dispenses', ['id' => $dispense->id]);

        // La periode est de nouveau libre.
        $nouvelle = $this->accorder(null, 'Nouvelle décision après vérification');
        $this->assertTrue($nouvelle->estActive());
    }

    /** @test */
    public function revoquer_deux_fois_ne_reecrit_pas_la_premiere_revocation(): void
    {
        $dispense = $this->accorder(null, 'Motif initial de la dispense');
        $this->service->revoquer($dispense, 'Première révocation motivée', $this->acteur);

        $autre = User::factory()->create();
        $rendu = $this->service->revoquer($dispense->fresh(), 'Deuxième tentative de révocation', $autre);

        $this->assertSame('Première révocation motivée', $rendu->motif_revocation);
        $this->assertSame((int) $this->acteur->id, (int) $rendu->revoquee_par);
    }

    /** @test */
    public function annuel_et_chaine_vide_designent_la_meme_portee(): void
    {
        $dispense = $this->accorder('annuel', 'Dispense pour toute l\'année scolaire');

        $this->assertNull($dispense->periode);
        $this->assertSame('Année complète', $dispense->porteeLisible());
        $this->assertTrue($dispense->couvreLeSemestre(1));
        $this->assertTrue($dispense->couvreLeSemestre(2));
    }

    /** @test */
    public function une_periode_inconnue_est_refusee(): void
    {
        $this->expectException(ValidationException::class);

        $this->accorder('trimestre1', 'Une portée que le bulletin ne sait pas rendre');
    }
}
