<?php

namespace Tests\Feature\API\CLI;

use App\Http\Controllers\API\CLI\CLIStudentController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Lire les eleves et les inscriptions d'une annee ecoulee.
 *
 * Sans `annee_id`, les deux listes ne lisaient que l'annee en cours : un eleve
 * inscrit seulement l'an passe ne sortait d'aucune recherche, et on concluait
 * qu'il n'existait pas avant de le recreer en double.
 */
class LectureAnneeEcouleeCliTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $courante;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
        $this->annee->update(['is_current' => false, 'name' => '2025-2026']);
        $this->courante = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true, 'name' => '2026-2027']);
    }

    /** @test */
    public function sans_annee_la_liste_reste_celle_de_l_annee_en_cours(): void
    {
        $this->inscrireLanPasse('ANCIEN', 'terminée');

        $donnees = $this->eleves(['search' => 'ANCIEN']);

        $this->assertSame('2026-2027', $donnees['annee']);
        $this->assertCount(0, $donnees['students']);
    }

    /** @test */
    public function l_annee_ecoulee_rend_l_eleve_meme_si_son_inscription_est_close(): void
    {
        $eleve = $this->inscrireLanPasse('ANCIEN', 'terminée');

        $donnees = $this->eleves(['search' => 'ANCIEN', 'annee_id' => $this->annee->id]);

        $this->assertSame('2025-2026', $donnees['annee']);
        $this->assertSame([$eleve->id], array_column($donnees['students'], 'id'));
        $this->assertSame($this->classe->id, $donnees['students'][0]['classe_id']);
    }

    /** @test */
    public function une_inscription_annulee_ne_sort_pas_de_l_annee_ecoulee(): void
    {
        $this->inscrireLanPasse('ANNULE', 'annulée');

        $donnees = $this->eleves(['search' => 'ANNULE', 'annee_id' => $this->annee->id]);

        $this->assertCount(0, $donnees['students']);
    }

    /** @test */
    public function un_dossier_qui_n_a_pas_abouti_n_est_pas_un_eleve_de_l_an_passe(): void
    {
        $this->inscrireLanPasse('PROSPECT', 'active', 'en_validation');

        $donnees = $this->eleves(['search' => 'PROSPECT', 'annee_id' => $this->annee->id]);

        $this->assertCount(0, $donnees['students']);
    }

    /** @test */
    public function apres_une_specialisation_la_classe_affichee_est_celle_qui_reste_active(): void
    {
        $specialite = \App\Models\ESBTPClasse::factory()->create([
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
        $eleve = $this->inscrireLanPasse('SPECIALISE', 'terminée');
        ESBTPInscription::factory()->create([
            'etudiant_id' => $eleve->id,
            'classe_id' => $specialite->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        $donnees = $this->eleves(['search' => 'SPECIALISE', 'annee_id' => $this->annee->id]);

        $this->assertCount(1, $donnees['students']);
        $this->assertSame($specialite->id, $donnees['students'][0]['classe_id']);
    }

    /** @test */
    public function les_inscriptions_d_une_annee_ecoulee_se_lisent_par_classe(): void
    {
        $eleve = $this->inscrireLanPasse('ANCIEN', 'terminée');

        $reponse = app(CLIStudentController::class)->inscriptions(
            $this->requete(['class_id' => $this->classe->id, 'annee_id' => $this->annee->id])
        );
        $donnees = $reponse->getData(true)['data'];

        $this->assertSame('2025-2026', $donnees['annee']);
        $this->assertSame([$eleve->id], array_column($donnees['inscriptions'], 'etudiant_id'));
    }

    /** @test */
    public function une_annee_inconnue_est_refusee_au_lieu_de_lire_l_annee_en_cours(): void
    {
        $reponse = app(CLIStudentController::class)->students($this->requete(['annee_id' => 999999]));

        $this->assertSame(422, $reponse->getStatusCode());
    }

    private function inscrireLanPasse(string $nom, string $statut, string $etape = 'etudiant_cree'): ESBTPEtudiant
    {
        $eleve = ESBTPEtudiant::factory()->create(['nom' => $nom]);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $eleve->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => $statut,
            'workflow_step' => $etape,
        ]);

        return $eleve;
    }

    private function eleves(array $charge): array
    {
        $reponse = app(CLIStudentController::class)->students($this->requete($charge));
        $this->assertSame(200, $reponse->getStatusCode(), json_encode($reponse->getData(true)));

        return $reponse->getData(true)['data'];
    }

    private function requete(array $charge): Request
    {
        $requete = Request::create('/', 'GET', $charge);
        $requete->setUserResolver(fn () => new class extends User
        {
            public function tokenCan(string $ability): bool
            {
                return $ability === 'cli:read';
            }
        });

        return $requete;
    }
}
