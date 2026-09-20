<?php

namespace Tests\Feature\Bulletin;

use App\Domain\BtsTroncCommun\BtsMaquette;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Ce que la maquette repond, et surtout ce qu'elle REFUSE de repondre.
 *
 * La question « cette matiere est-elle prevue a ce semestre ? » a trois
 * reponses, pas deux : oui, non, et « on n'en sait rien ». La troisieme compte
 * autant que les autres : une classe de specialite s'appuie sur DEUX couples
 * filiere x niveau (le sien et celui du tronc commun parent), et tant qu'un
 * seul des deux est indefini, conclure « cette matiere n'est pas prevue » ferait
 * disparaitre du bulletin une matiere reellement enseignee.
 */
class BtsMaquetteTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
    }

    private function maquette(): BtsMaquette
    {
        return app(BtsMaquette::class);
    }

    private function lier(ESBTPMatiere $matiere, ?ESBTPFiliere $filiere = null, ?int $semestre = null, bool $valide = false): void
    {
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => ($filiere ?? $this->filiere)->id,
            'niveau_etude_id' => $this->niveau->id,
            'semestre' => $semestre,
            'semestre_renseigne' => $valide,
        ]);
    }

    public function test_sans_validation_la_maquette_n_est_pas_appliquee(): void
    {
        $matiere = $this->matiereConfiguree();
        $this->lier($matiere, semestre: 2);

        $classe = $this->classe->fresh();

        self::assertSame(BtsMaquette::ETAT_AUCUN, $this->maquette()->etatPourClasse($classe));
        self::assertFalse($this->maquette()->estAppliquee($classe));
        // Rien a conclure : surtout pas que la matiere est exclue du semestre 1.
        self::assertNull($this->maquette()->isPrevue($classe, (int) $matiere->id, 1));
    }

    public function test_une_maquette_validee_repond_sur_le_semestre(): void
    {
        $auSecond = $this->matiereConfiguree();
        $auxDeux = $this->matiereConfiguree();
        $this->lier($auSecond, semestre: 2, valide: true);
        $this->lier($auxDeux, semestre: null, valide: true);

        $classe = $this->classe->fresh();

        self::assertSame(BtsMaquette::ETAT_COMPLET, $this->maquette()->etatPourClasse($classe));
        self::assertFalse($this->maquette()->isPrevue($classe, (int) $auSecond->id, 1));
        self::assertTrue($this->maquette()->isPrevue($classe, (int) $auSecond->id, 2));
        // « Les deux » veut dire les deux.
        self::assertTrue($this->maquette()->isPrevue($classe, (int) $auxDeux->id, 1));
        self::assertTrue($this->maquette()->isPrevue($classe, (int) $auxDeux->id, 2));
    }

    public function test_les_matieres_du_semestre_sont_filtrees(): void
    {
        $s1 = $this->matiereConfiguree();
        $s2 = $this->matiereConfiguree();
        $this->lier($s1, semestre: 1, valide: true);
        $this->lier($s2, semestre: 2, valide: true);

        $classe = $this->classe->fresh();

        self::assertSame(
            [(int) $s1->id],
            $this->maquette()->subjectsForClasseAndSemestre($classe, 1)->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_un_seul_combo_valide_sur_deux_ne_suffit_pas(): void
    {
        $specialite = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => false,
            'parent_id' => $this->filiere->id,
        ]);
        $classeSpecialite = ESBTPClasse::factory()->create([
            'filiere_id' => $specialite->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);

        $deLaSpecialite = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $duTroncCommun = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        // La specialite est validee, le tronc commun parent ne l'est pas.
        $this->lier($deLaSpecialite, $specialite, semestre: 1, valide: true);
        $this->lier($duTroncCommun, $this->filiere, semestre: null, valide: false);

        $classe = $classeSpecialite->fresh();

        self::assertSame(BtsMaquette::ETAT_PARTIEL, $this->maquette()->etatPourClasse($classe));
        self::assertFalse($this->maquette()->estAppliquee($classe));
        // Une matiere du combo non valide ne doit surtout pas etre declaree absente.
        self::assertNull($this->maquette()->isPrevue($classe, (int) $duTroncCommun->id, 2));
    }

    public function test_les_semestres_de_deux_combos_s_additionnent(): void
    {
        $specialite = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => false,
            'parent_id' => $this->filiere->id,
        ]);
        $classeSpecialite = ESBTPClasse::factory()->create([
            'filiere_id' => $specialite->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);

        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        // Les deux combos se contredisent : S1 d'un cote, S2 de l'autre.
        $this->lier($matiere, $specialite, semestre: 1, valide: true);
        $this->lier($matiere, $this->filiere, semestre: 2, valide: true);

        $classe = $classeSpecialite->fresh();

        // Union, pas intersection : une matiere reellement notee ne peut pas
        // disparaitre du bulletin a cause d'une saisie divergente.
        self::assertTrue($this->maquette()->isPrevue($classe, (int) $matiere->id, 1));
        self::assertTrue($this->maquette()->isPrevue($classe, (int) $matiere->id, 2));
    }

    public function test_une_ligne_non_validee_vaut_les_deux_semestres(): void
    {
        $valide = $this->matiereConfiguree();
        $ajouteeApres = $this->matiereConfiguree();

        $this->lier($valide, semestre: 1, valide: true);
        // Rattachee au combo apres la validation : personne ne s'est prononce
        // sur son semestre. Elle doit rester visible partout, pas disparaitre.
        $this->lier($ajouteeApres, semestre: null, valide: false);

        $classe = $this->classe->fresh();

        self::assertSame(BtsMaquette::ETAT_COMPLET, $this->maquette()->etatPourClasse($classe));
        self::assertTrue($this->maquette()->isPrevue($classe, (int) $ajouteeApres->id, 1));
        self::assertTrue($this->maquette()->isPrevue($classe, (int) $ajouteeApres->id, 2));
    }
}
