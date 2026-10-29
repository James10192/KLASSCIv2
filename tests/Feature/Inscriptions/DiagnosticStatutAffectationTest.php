<?php

namespace Tests\Feature\Inscriptions;

use App\Domain\Students\DiagnosticStatutAffectation;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le correctif du statut d'affectation empêche de nouveaux cas ; il ne répare
 * pas ceux déjà en base. Ce diagnostic les retrouve — et surtout, chiffre ce
 * que l'école ne réclame pas, puisque c'est la seule chose qui rend le problème
 * visible : un dossier écrasé affiche « situation apurée ».
 */
class DiagnosticStatutAffectationTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $anneePassee;

    private ESBTPAnneeUniversitaire $anneeCourante;

    private ESBTPFiliere $filiere;

    private ESBTPNiveauEtude $niveau;

    protected function setUp(): void
    {
        parent::setUp();

        $this->anneePassee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_current' => false,
        ]);
        $this->anneeCourante = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true,
        ]);
        $this->filiere = ESBTPFiliere::factory()->create();
        $this->niveau = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 2', 'year' => 2]);

        // Le barème d'ISLG : la scolarité est subventionnée pour un affecté.
        $scolarite = ESBTPFraisCategory::factory()->create([
            'name' => 'FRAIS DE SCOLARITE', 'is_mandatory' => true, 'is_active' => true, 'default_amount' => 160000,
        ]);
        ESBTPFraisConfiguration::create([
            'frais_category_id' => $scolarite->id,
            'systeme_academique' => 'BTS',
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
            'annee_universitaire_id' => null,
            'amount' => 160000,
            'amount_affecte' => 0,
            'amount_reaffecte' => 0,
            'amount_non_affecte' => 160000,
            'payment_deadline_days' => 30,
            'is_active' => true,
            'created_by' => User::factory()->create()->id,
        ]);
    }

    /** @test */
    public function un_retournement_vers_affecte_est_signale_et_chiffre(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create(['matricule' => 'GBAD1310040001']);
        $this->inscrire($etudiant, $this->anneePassee, 'non_affecté');
        $this->inscrire($etudiant, $this->anneeCourante, 'affecté');

        $rapport = $this->rapport();

        $this->assertSame(1, $rapport['retournements']['total']);
        $cas = $rapport['retournements']['cas'][0];
        $this->assertSame('GBAD1310040001', $cas['matricule']);
        $this->assertSame('non_affecté', $cas['statut_precedent']);
        $this->assertEqualsWithDelta(160000, $cas['manque_a_gagner_fcfa'], 0.01);
        $this->assertEqualsWithDelta(160000, $rapport['resume']['manque_a_gagner_fcfa'], 0.01);
    }

    /** @test */
    public function un_statut_inchange_n_est_pas_signale(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->anneePassee, 'affecté');
        $this->inscrire($etudiant, $this->anneeCourante, 'affecté');

        $this->assertSame(0, $this->rapport()['retournements']['total']);
    }

    /** @test */
    public function un_etudiant_devenu_non_affecte_n_est_pas_signale(): void
    {
        // Le sens inverse ne coûte rien à l'école : on ne le remonte pas.
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->anneePassee, 'affecté');
        $this->inscrire($etudiant, $this->anneeCourante, 'non_affecté');

        $this->assertSame(0, $this->rapport()['retournements']['total']);
    }

    /** @test */
    public function une_specialisation_en_desaccord_avec_son_tronc_commun_est_signalee(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $troncCommun = $this->inscrire($etudiant, $this->anneeCourante, 'non_affecté');

        $specialisation = $this->inscrire($etudiant, $this->anneeCourante, 'affecté');
        $specialisation->update(['inscription_origine_id' => $troncCommun->id]);

        $rapport = $this->rapport();

        $this->assertSame(1, $rapport['specialisations']['total']);
        $this->assertSame('non_affecté', $rapport['specialisations']['cas'][0]['statut_origine']);
        $this->assertEqualsWithDelta(160000, $rapport['specialisations']['cas'][0]['manque_a_gagner_fcfa'], 0.01);
    }

    /** @test */
    public function la_repartition_compte_chaque_statut(): void
    {
        $this->inscrire(ESBTPEtudiant::factory()->create(), $this->anneeCourante, 'affecté');
        $this->inscrire(ESBTPEtudiant::factory()->create(), $this->anneeCourante, 'affecté');
        $this->inscrire(ESBTPEtudiant::factory()->create(), $this->anneeCourante, 'non_affecté');

        $repartition = $this->rapport()['repartition'];

        $this->assertSame(2, $repartition['affecté']);
        $this->assertSame(1, $repartition['non_affecté']);
    }

    /** @test */
    public function le_diagnostic_ne_modifie_aucun_dossier(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->anneePassee, 'non_affecté');
        $actuelle = $this->inscrire($etudiant, $this->anneeCourante, 'affecté');

        $this->rapport();

        $this->assertSame('affecté', $actuelle->fresh()->affectation_status, 'Le diagnostic est en lecture seule.');
        $this->assertSame(0, \App\Models\ESBTPFraisSubscription::count());
    }

    /** @test */
    public function un_zero_sans_reference_comparable_est_annonce_comme_tel(): void
    {
        // Premiere annee d'une instance : personne n'a d'annee precedente. Le
        // signal ne vaut rien ici, et le rapport doit le dire au lieu de
        // laisser lire « aucun cas » comme « tout va bien ».
        $this->inscrire(ESBTPEtudiant::factory()->create(), $this->anneeCourante, 'affecté');

        $bloc = $this->rapport()['retournements'];

        $this->assertSame(0, $bloc['total']);
        $this->assertSame(1, $bloc['inscriptions_examinees']);
        $this->assertSame(0, $bloc['comparables']);
    }

    /** @test */
    public function une_reference_comparable_est_comptee_meme_sans_ecart(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $this->anneePassee, 'affecté');
        $this->inscrire($etudiant, $this->anneeCourante, 'affecté');

        $bloc = $this->rapport()['retournements'];

        $this->assertSame(0, $bloc['total'], 'Statut inchangé : rien à signaler.');
        $this->assertSame(1, $bloc['comparables'], 'Mais la comparaison a bien eu lieu.');
    }

    private function rapport(): array
    {
        return app(DiagnosticStatutAffectation::class)->pour($this->anneeCourante);
    }

    private function inscrire(ESBTPEtudiant $etudiant, ESBTPAnneeUniversitaire $annee, string $statut): ESBTPInscription
    {
        return ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
            'classe_id' => ESBTPClasse::factory()->create([
                'filiere_id' => $this->filiere->id,
                'niveau_etude_id' => $this->niveau->id,
            ])->id,
            'annee_universitaire_id' => $annee->id,
            'affectation_status' => $statut,
            'status' => 'active',
        ]);
    }
}
