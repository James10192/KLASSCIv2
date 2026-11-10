<?php

namespace Tests\Feature\API\CLI;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

class BulletinClassementDiagnosticTest extends TestCase
{
    use RefreshDatabase, MonteUneClasseBts;

    protected function setUp(): void
    {
        parent::setUp();

        // L'instance d'essai n'est pas installee : ces gardes redirigent
        // en 302 avant les routes API. Conserver Sanctum et le throttle.
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
    }

    public function test_diagnostic_s2_retrouve_un_eleve_du_classement_absent_de_students(): void
    {
        $this->monterLaClasse();
        $this->filiere->forceFill(['is_tronc_commun' => false])->save();
        SettingsHelper::setOrCreate('bulletin_show_attendance_note', '0');
        SettingsHelper::setOrCreate('bulletin_moyenne_mode', 'ponderee');

        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        ESBTPConfigMatiere::create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre2',
            'config' => ['type' => 'general', 'coefficient' => 1],
        ]);
        ESBTPMatiereCoefficient::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre2',
            'coefficient' => 1,
        ]);
        $evaluation = ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre2',
            'status' => 'published',
            'bareme' => 20,
            'coefficient' => 1,
        ]);

        $premierDeLaListe = $this->etudiantInscrit();
        $secondDeLaListe = $this->etudiantInscrit();
        $this->noter($premierDeLaListe, $evaluation, 14);
        $this->noter($secondDeLaListe, $evaluation, 10);

        // Inscrit administrativement dans une autre classe, mais sa phase S2
        // l'attribue a la classe cible : students(class_id) ne le verra pas.
        $autreClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
        $horsListe = \App\Models\ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $horsListe->id,
            'classe_id' => $autreClasse->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $this->classe->id,
            'filiere_id' => $this->filiere->id,
            'semestre_debut' => 2,
            'semestre_fin' => 2,
            'is_active' => true,
        ]);
        $this->noter($horsListe, $evaluation, 18);

        Sanctum::actingAs(User::findOrFail(1), ['cli:read']);
        $resultat = $this->getJson('/api/cli/diagnostics/bulletins/classement?'.http_build_query([
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre2',
            'etudiant_id' => $premierDeLaListe->id,
        ]));

        $resultat->assertOk()
            ->assertJsonPath('data.source', 'RankingService::calculerRangsClasse')
            ->assertJsonPath('data.cohorte.membres_canoniques', 3)
            ->assertJsonPath('data.cohorte.avec_moyenne', 3)
            ->assertJsonPath('data.cohorte.liste_students_total', 2)
            ->assertJsonPath('data.cohorte.hors_liste_students', 1)
            ->assertJsonPath('data.cohorte.liste_students_hors_cohorte', 0)
            ->assertJsonPath('data.statistiques.plus_forte_moyenne', 18)
            ->assertJsonPath('data.statistiques.plus_faible_moyenne', 10)
            ->assertJsonPath('data.statistiques.moyenne_classe', 14)
            ->assertJsonPath('data.ecart_liste_students.hors_liste_avec_moyenne', 1)
            ->assertJsonPath('data.ecart_liste_students.max_moyenne_hors_liste', 18)
            ->assertJsonPath('data.ecart_liste_students.rangs_hors_liste.0', 1)
            ->assertJsonPath('data.etudiant_cible.dans_cohorte', true)
            ->assertJsonPath('data.etudiant_cible.rang', 2)
            ->assertJsonPath('data.etudiant_cible.moyenne_canonique', 14);

        $bulletin = ESBTPBulletin::factory()->create([
            'etudiant_id' => $premierDeLaListe->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre2',
        ]);
        $this->getJson('/api/cli/diagnostics/bulletins/classement?bulletin_id='.$bulletin->id)
            ->assertOk()
            ->assertJsonPath('data.bulletin_id_source', $bulletin->id)
            ->assertJsonPath('data.cohorte.membres_canoniques', 3)
            ->assertJsonPath('data.statistiques.plus_forte_moyenne', 18)
            ->assertJsonPath('data.etudiant_cible.rang', 2);

        $json = $resultat->json('data');
        $this->assertArrayNotHasKey('matricule', $json);
        $this->assertArrayNotHasKey('nom', $json);
    }

    public function test_un_jeton_sans_cli_read_ne_peut_pas_consulter_les_statistiques(): void
    {
        $this->monterLaClasse();
        Sanctum::actingAs(User::findOrFail(1), ['cli:admin']);

        $this->getJson('/api/cli/diagnostics/bulletins/classement?'.http_build_query([
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre2',
        ]))->assertForbidden();
    }

    public function test_les_parametres_de_periode_et_de_classe_sont_valides(): void
    {
        $this->monterLaClasse();
        Sanctum::actingAs(User::findOrFail(1), ['cli:read']);

        $this->getJson('/api/cli/diagnostics/bulletins/classement?'.http_build_query([
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'trimestre7',
        ]))->assertUnprocessable()->assertJsonValidationErrors('periode');
    }
}
