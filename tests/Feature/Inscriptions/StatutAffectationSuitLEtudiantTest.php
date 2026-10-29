<?php

namespace Tests\Feature\Inscriptions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\ReeinscriptionService;
use App\Services\TroncCommunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le statut d'affectation MESRS suit l'étudiant : le ministère l'a placé, ou ne
 * l'a pas placé, et ni une réinscription ni une sortie de tronc commun n'y
 * change quoi que ce soit.
 *
 * Quatre chemins l'écrivaient pourtant en dur à « affecté » — précisément le
 * statut qui ouvre droit à la subvention. Sur ISLG, où la scolarité vaut 0 F
 * pour un affecté et 160 000 F pour un non affecté, un étudiant traversant l'un
 * de ces chemins cessait de devoir sa scolarité. L'écran affichait « situation
 * apurée » et personne ne voyait rien.
 */
class StatutAffectationSuitLEtudiantTest extends TestCase
{
    use RefreshDatabase;

    private const NON_AFFECTE = 'non_affecté';

    private ESBTPAnneeUniversitaire $anneePassee;

    private ESBTPAnneeUniversitaire $anneeCourante;

    private ESBTPNiveauEtude $premiereAnnee;

    private ESBTPNiveauEtude $deuxiemeAnnee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->premiereAnnee = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 1', 'year' => 1]);
        $this->deuxiemeAnnee = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 2', 'year' => 2]);

        $this->anneePassee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_current' => false,
        ]);
        $this->anneeCourante = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true,
        ]);
    }

    /* ─────────────── Guichet caisse ─────────────── */

    /** @test */
    public function le_guichet_doit_indiquer_le_statut(): void
    {
        $this->actingAs($this->agentDeCaisse());
        $classe = $this->classe($this->premiereAnnee, ESBTPFiliere::factory()->create());

        $this->post(route('esbtp.inscriptions.store-pre-inscription'), [
            'nom' => 'KOUASSI', 'prenoms' => 'Ama', 'classe_id' => $classe->id,
        ])->assertSessionHasErrors('affectation_status');

        $this->assertSame(0, ESBTPInscription::count(), 'Rien ne doit être créé sans le statut.');
    }

    /** @test */
    public function le_guichet_enregistre_le_statut_saisi(): void
    {
        $this->actingAs($this->agentDeCaisse());
        $classe = $this->classe($this->premiereAnnee, ESBTPFiliere::factory()->create());

        $this->post(route('esbtp.inscriptions.store-pre-inscription'), [
            'nom' => 'KOUASSI',
            'prenoms' => 'Ama',
            'classe_id' => $classe->id,
            'affectation_status' => self::NON_AFFECTE,
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            self::NON_AFFECTE,
            ESBTPInscription::latest('id')->firstOrFail()->affectation_status,
            'Le guichet écrivait « affecté » quoi que dise l\'agent.'
        );
    }

    /** @test */
    public function le_guichet_refuse_un_statut_inconnu(): void
    {
        $this->actingAs($this->agentDeCaisse());
        $classe = $this->classe($this->premiereAnnee, ESBTPFiliere::factory()->create());

        $this->post(route('esbtp.inscriptions.store-pre-inscription'), [
            'nom' => 'KOUASSI', 'prenoms' => 'Ama', 'classe_id' => $classe->id,
            'affectation_status' => 'boursier',
        ])->assertSessionHasErrors('affectation_status');
    }

    /* ─────────────── Réinscription ─────────────── */

    /** @test */
    public function une_reinscription_sans_statut_reprend_celui_de_l_annee_quittee(): void
    {
        $this->actingAs($this->agentDeCaisse());
        $filiere = ESBTPFiliere::factory()->create();
        $etudiant = ESBTPEtudiant::factory()->create();

        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $this->premiereAnnee->id,
            'classe_id' => $this->classe($this->premiereAnnee, $filiere)->id,
            'annee_universitaire_id' => $this->anneePassee->id,
            'affectation_status' => self::NON_AFFECTE,
            'status' => 'active',
        ]);

        $nouvelle = app(ReeinscriptionService::class)->effectuerReinscription(
            $etudiant->id,
            $this->classe($this->deuxiemeAnnee, $filiere)->id,
            'passage',
            null,
            [],
            null, // l'appelant ne sait pas : le service doit reprendre l'existant
            $this->anneeCourante->id
        );

        $this->assertSame(
            self::NON_AFFECTE,
            $nouvelle->affectation_status,
            'Se réinscrire ne fait pas placer un étudiant par le MESRS.'
        );
    }

    /** @test */
    public function une_reinscription_respecte_un_statut_explicitement_corrige(): void
    {
        $this->actingAs($this->agentDeCaisse());
        $filiere = ESBTPFiliere::factory()->create();
        $etudiant = ESBTPEtudiant::factory()->create();

        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $this->premiereAnnee->id,
            'classe_id' => $this->classe($this->premiereAnnee, $filiere)->id,
            'annee_universitaire_id' => $this->anneePassee->id,
            'affectation_status' => self::NON_AFFECTE,
            'status' => 'active',
        ]);

        $nouvelle = app(ReeinscriptionService::class)->effectuerReinscription(
            $etudiant->id,
            $this->classe($this->deuxiemeAnnee, $filiere)->id,
            'passage',
            null,
            [],
            'réaffecté', // la DOB l'a replacé entre-temps
            $this->anneeCourante->id
        );

        $this->assertSame('réaffecté', $nouvelle->affectation_status);
    }

    /* ─────────────── Sortie de tronc commun ─────────────── */

    /** @test */
    public function la_specialisation_herite_du_statut_du_tronc_commun(): void
    {
        $this->actingAs($this->agentDeCaisse());

        $troncCommun = ESBTPFiliere::factory()->create(['is_tronc_commun' => true]);
        $specialite = ESBTPFiliere::factory()->create(['parent_id' => $troncCommun->id]);

        $inscriptionTc = ESBTPInscription::factory()->create([
            'filiere_id' => $troncCommun->id,
            'niveau_id' => $this->premiereAnnee->id,
            'classe_id' => $this->classe($this->premiereAnnee, $troncCommun)->id,
            'annee_universitaire_id' => $this->anneeCourante->id,
            'affectation_status' => self::NON_AFFECTE,
            'status' => 'active',
        ]);

        $classeSpec = $this->classe($this->premiereAnnee, $specialite);

        $specialisation = app(TroncCommunService::class)
            ->creerInscriptionSpecialisation($inscriptionTc, $classeSpec->id);

        $this->assertSame(
            self::NON_AFFECTE,
            $specialisation->affectation_status,
            'Changer de filière ne fait pas placer un étudiant par le MESRS.'
        );
    }

    /* ─────────────── Outils ─────────────── */

    private function classe(ESBTPNiveauEtude $niveau, ESBTPFiliere $filiere): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $this->anneeCourante->id,
            'is_active' => true,
            'places_totales' => 50,
        ]);
    }

    private function agentDeCaisse(): User
    {
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        foreach (['admin.access', 'inscriptions.create'] as $nom) {
            $role->givePermissionTo(Permission::findOrCreate($nom, 'web'));
        }

        return User::factory()->create()->assignRole($role);
    }
}
