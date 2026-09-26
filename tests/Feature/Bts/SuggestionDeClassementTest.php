<?php

namespace Tests\Feature\Bts;

use App\Domain\BtsTroncCommun\SuggestionDeClassement;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La page « Maquette du bulletin » proposait « Spécialité » pour toute matière
 * du tronc commun aussi rattachée à une filière fille — donc, à Yakro, pour les
 * vingt et une matières, mathématiques comprises. Accepter la proposition les
 * retirait toutes du bulletin de tronc commun.
 *
 * La proposition se fonde désormais sur la planification du tronc commun quand
 * elle existe, sinon sur les évaluations non annulées de la majorité de ses classes.
 */
class SuggestionDeClassementTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPNiveauEtude $niveau;

    private ESBTPFiliere $tc;

    private ESBTPFiliere $specialite;

    private ESBTPClasse $classeTc;

    private ESBTPClasse $classeSpecialite;

    protected function setUp(): void
    {
        parent::setUp();

        // La fabrique d'evaluation signe `created_by = 1`.
        User::factory()->create(['id' => 1]);

        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $this->tc = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null, 'semestres_tronc_commun' => 1]);
        $this->specialite = ESBTPFiliere::factory()->create(['parent_id' => $this->tc->id]);
        $this->classeTc = $this->classe($this->tc);
        $this->classeSpecialite = $this->classe($this->specialite);
    }

    public function test_sans_planification_la_proposition_suit_les_evaluations_du_tronc_commun(): void
    {
        // Trois classes de tronc commun, aucune planification : le cas de Yakro.
        $autresTc = [$this->classe($this->tc), $this->classe($this->tc)];

        // Rattachees au tronc commun ET a la filiere fille, comme a Yakro.
        $maths = $this->matiere('Mathematiques', partagee: true);
        $securite = $this->matiere('Securite', partagee: true);
        $egaree = $this->matiere('Technique des engins', partagee: true);
        $annulee = $this->matiere('Hydraulique', partagee: true);
        $jamaisEvaluee = $this->matiere('Topographie', partagee: true);

        $this->evaluer($maths, $this->classeTc);
        $this->evaluer($maths, $autresTc[0]);
        $this->evaluer($securite, $this->classeSpecialite);
        // Une seule epreuve posee par erreur sur une classe de tronc commun sur trois.
        $this->evaluer($egaree, $this->classeTc);
        $this->evaluer($egaree, $this->classeSpecialite);
        foreach ([$this->classeTc, ...$autresTc] as $classe) {
            $this->evaluer($annulee, $classe, 'cancelled');
        }

        $suggestions = app(SuggestionDeClassement::class)->pourCouple($this->tc, $this->niveau->id);

        $this->assertSame(ESBTPMatiereFilierNiveau::TRONC_COMMUN, $suggestions[$maths->id]['valeur']);
        $this->assertStringContainsString('2 classe(s) de tronc commun sur 3', $suggestions[$maths->id]['raison']);
        $this->assertSame(ESBTPMatiereFilierNiveau::SPECIALITE, $suggestions[$securite->id]['valeur']);
        $this->assertArrayNotHasKey($egaree->id, $suggestions, 'Une épreuve égarée ne vaut pas preuve.');
        $this->assertArrayNotHasKey($annulee->id, $suggestions, 'Une évaluation annulée ne compte pas.');
        $this->assertArrayNotHasKey($jamaisEvaluee->id, $suggestions, 'Sans preuve, rien ne doit être proposé.');
    }

    public function test_sur_un_couple_a_une_seule_classe_une_epreuve_ne_suffit_pas(): void
    {
        // Petite ecole : une seule classe de tronc commun, aucune planification.
        $egaree = $this->matiere('Securite', partagee: true);
        $this->evaluer($egaree, $this->classeTc);

        $suggestions = app(SuggestionDeClassement::class)->pourCouple($this->tc, $this->niveau->id);

        $this->assertArrayNotHasKey($egaree->id, $suggestions, 'Une épreuve unique ne vaut pas preuve, même sur un petit couple.');
    }

    public function test_une_classe_desactivee_ne_compte_pas_dans_le_seuil(): void
    {
        $autresTc = [$this->classe($this->tc), $this->classe($this->tc)];
        $autresTc[1]->update(['is_active' => false]);
        $maths = $this->matiere('Mathematiques', partagee: true);

        $this->evaluer($maths, $this->classeTc);
        $this->evaluer($maths, $autresTc[0]);

        $suggestions = app(SuggestionDeClassement::class)->pourCouple($this->tc, $this->niveau->id);

        $this->assertStringContainsString('2 classe(s) de tronc commun sur 2', $suggestions[$maths->id]['raison']);
    }

    public function test_avec_une_planification_la_proposition_suit_le_critere_du_diagnostic(): void
    {
        $dessin = $this->matiere('Dessin technique', partagee: true);
        $securite = $this->matiere('Securite', partagee: true);
        $propreAuTc = $this->matiere('Expression francaise', partagee: false);

        $this->planifier($dessin);
        // Meme evaluee en tronc commun, une matiere non planifiee et rattachee a
        // une specialite est suspecte : c'est la regle du diagnostic des fuites.
        $this->evaluer($securite, $this->classeTc);

        $suggestions = app(SuggestionDeClassement::class)->pourCouple($this->tc, $this->niveau->id);

        $this->assertSame(ESBTPMatiereFilierNiveau::TRONC_COMMUN, $suggestions[$dessin->id]['valeur']);
        $this->assertSame(ESBTPMatiereFilierNiveau::SPECIALITE, $suggestions[$securite->id]['valeur']);
        $this->assertArrayNotHasKey($propreAuTc->id, $suggestions);
    }

    public function test_l_ecran_rend_la_proposition_sans_la_poser_comme_classement(): void
    {
        $maths = $this->matiere('Mathematiques', partagee: true);
        $this->evaluer($maths, $this->classeTc);

        $admin = User::find(1);
        $admin->assignRole(\Spatie\Permission\Models\Role::findOrCreate('superAdmin', 'web'));

        $ligne = collect(
            $this->actingAs($admin)
                ->getJson(route('esbtp.matieres.classification.combo', ['filiere_id' => $this->tc->id, 'niveau_id' => $this->niveau->id]))
                ->assertOk()
                ->json('matieres')
        )->firstWhere('matiere_id', $maths->id);

        $this->assertNull($ligne['classification'], 'Une proposition ne vaut pas classement.');
        $this->assertSame(ESBTPMatiereFilierNiveau::TRONC_COMMUN, $ligne['suggested']);
    }

    private function classe(ESBTPFiliere $filiere): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
    }

    private function matiere(string $nom, bool $partagee): ESBTPMatiere
    {
        $matiere = ESBTPMatiere::factory()->create(['name' => $nom, 'unite_enseignement_id' => null, 'is_active' => true]);

        foreach ($partagee ? [$this->tc, $this->specialite] : [$this->tc] as $filiere) {
            ESBTPMatiereFilierNiveau::create([
                'matiere_id' => $matiere->id,
                'filiere_id' => $filiere->id,
                'niveau_etude_id' => $this->niveau->id,
            ]);
        }

        return $matiere;
    }

    private function evaluer(ESBTPMatiere $matiere, ESBTPClasse $classe, string $status = 'published'): void
    {
        ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'status' => $status,
        ]);
    }

    private function planifier(ESBTPMatiere $matiere): void
    {
        ESBTPPlanificationAcademique::create([
            'annee_universitaire_id' => $this->annee->id,
            'filiere_id' => $this->tc->id,
            'niveau_etude_id' => $this->niveau->id,
            'matiere_id' => $matiere->id,
            'semestre' => 1,
            'volume_horaire_total' => 30,
            'is_active' => true,
        ]);
    }
}
