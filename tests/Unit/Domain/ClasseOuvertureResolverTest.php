<?php

namespace Tests\Unit\Domain;

use App\Domain\BtsTroncCommun\ClasseOuvertureResolver;
use App\Models\ESBTPClasse;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * « À quel semestre cette classe s'ouvre-t-elle ? » était posée à trois
 * endroits, chacun avec sa propre requête brute. Elle vit maintenant ici, et
 * c'est ici qu'on la vérifie.
 */
class ClasseOuvertureResolverTest extends TestCase
{
    use RefreshDatabase;

    private ClasseOuvertureResolver $resolveur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolveur = new ClasseOuvertureResolver();
    }

    private function classe(): ESBTPClasse
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);

        return ESBTPClasse::factory()->create(['niveau_etude_id' => $niveau->id]);
    }

    private function cible(ESBTPClasse $source, ESBTPClasse $cible, int $semestre, bool $active = true): void
    {
        DB::table('esbtp_classe_orientation_targets')->insert([
            'source_classe_id' => $source->id,
            'target_classe_id' => $cible->id,
            'semestre_activation' => $semestre,
            'is_active' => $active,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_une_classe_sans_orientation_n_est_contrainte_par_rien(): void
    {
        $classe = $this->classe();

        $this->assertNull($this->resolveur->semestreDOuverture($classe->id));
        $this->assertTrue($this->resolveur->estOuverteAu($classe->id, 1));
        $this->assertTrue($this->resolveur->estOuverteAu($classe->id, 2));
    }

    public function test_une_classe_cible_s_ouvre_a_son_semestre(): void
    {
        $tc = $this->classe();
        $specialite = $this->classe();
        $this->cible($tc, $specialite, 2);

        $this->assertSame(2, $this->resolveur->semestreDOuverture($specialite->id));
        $this->assertFalse($this->resolveur->estOuverteAu($specialite->id, 1));
        $this->assertTrue($this->resolveur->estOuverteAu($specialite->id, 2));
    }

    /**
     * Une classe peut être la cible de plusieurs troncs communs. On retient le
     * semestre le plus tôt : c'est à partir de lui qu'elle peut recevoir un
     * étudiant.
     */
    public function test_le_semestre_le_plus_precoce_l_emporte(): void
    {
        $tc1 = $this->classe();
        $tc2 = $this->classe();
        $specialite = $this->classe();
        $this->cible($tc1, $specialite, 2);
        $this->cible($tc2, $specialite, 1);

        $this->assertSame(1, $this->resolveur->semestreDOuverture($specialite->id));
    }

    public function test_une_cible_desactivee_ne_contraint_plus(): void
    {
        $tc = $this->classe();
        $specialite = $this->classe();
        $this->cible($tc, $specialite, 2, active: false);

        $this->assertNull($this->resolveur->semestreDOuverture($specialite->id));
    }

    public function test_la_liste_des_classes_non_ouvertes_suit_le_semestre(): void
    {
        $tc = $this->classe();
        $specialite = $this->classe();
        $ordinaire = $this->classe();
        $this->cible($tc, $specialite, 2);

        $auSemestre1 = $this->resolveur->classesNonOuvertesAu(1);
        $this->assertContains($specialite->id, $auSemestre1);
        $this->assertNotContains($ordinaire->id, $auSemestre1);
        $this->assertNotContains($tc->id, $auSemestre1);

        $this->assertSame([], $this->resolveur->classesNonOuvertesAu(2));
    }

    /**
     * La mémoire est de portée requête : sans elle, la génération en masse
     * relancerait la requête pour chaque étudiant. Les tests qui créent des
     * cibles après une première résolution doivent pouvoir l'oublier.
     */
    public function test_la_memoire_peut_etre_oubliee(): void
    {
        $tc = $this->classe();
        $specialite = $this->classe();

        $this->assertNull($this->resolveur->semestreDOuverture($specialite->id));

        $this->cible($tc, $specialite, 2);
        $this->assertNull(
            $this->resolveur->semestreDOuverture($specialite->id),
            'La mémoire doit tenir tant qu\'on ne l\'oublie pas.'
        );

        $this->resolveur->oublier();
        $this->assertSame(2, $this->resolveur->semestreDOuverture($specialite->id));
    }
}
