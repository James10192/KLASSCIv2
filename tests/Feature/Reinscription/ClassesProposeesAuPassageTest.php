<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\ReeinscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La classe proposee a un etudiant admis se cherche sur l'annee suivante.
 *
 * En LMD l'annee est comptee en continu (Licence 1-3, Master 4-5, Doctorat
 * 6-8). Avant cette convention, une Licence 3 ne trouvait son Master 1 que par
 * un repli — « premiere annee d'un autre type » — qui cesse de fonctionner des
 * que le Master 1 porte son annee 4. Ces tests fixent le passage d'un cycle a
 * l'autre, et ce qu'un etudiant en fin de Master ne doit PAS se voir proposer.
 */
class ClassesProposeesAuPassageTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPFiliere $filiere;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['id' => 1]);
        $this->actingAs(User::find(1));
        $this->filiere = ESBTPFiliere::factory()->create();
    }

    public function test_une_licence_3_passe_en_master_1(): void
    {
        $licence3 = $this->classe('Licence', 3);
        $master1 = $this->classe('Master', 4);
        $this->classe('BTS', 1);

        $proposees = $this->proposees($this->etudiantEn($licence3));

        $this->assertSame([$master1->id], $proposees);
    }

    public function test_un_master_1_passe_en_master_2(): void
    {
        $master1 = $this->classe('Master', 4);
        $master2 = $this->classe('Master', 5);

        $this->assertSame([$master2->id], $this->proposees($this->etudiantEn($master1)));
    }

    public function test_un_master_2_ne_se_voit_pas_proposer_une_premiere_annee(): void
    {
        $master2 = $this->classe('Master', 5);
        $this->classe('Licence', 1);
        $this->classe('BTS', 1);

        $this->assertSame([], $this->proposees($this->etudiantEn($master2)));
    }

    public function test_le_bts_garde_son_repli_vers_une_premiere_annee_d_un_autre_type(): void
    {
        // Comportement anterieur, que ce correctif ne doit pas toucher.
        $bts2 = $this->classe('BTS', 2);
        $licence1 = $this->classe('Licence', 1);

        $this->assertSame([$licence1->id], $this->proposees($this->etudiantEn($bts2)));
    }

    /** @return list<int> */
    private function proposees(ESBTPEtudiant $etudiant): array
    {
        return collect(app(ReeinscriptionService::class)->proposerNouvellesClasses($etudiant->id, 'passage'))
            ->pluck('id')->sort()->values()->all();
    }

    private function classe(string $type, int $annee): ESBTPClasse
    {
        $niveau = ESBTPNiveauEtude::factory()->create([
            'name' => "{$type} {$annee}",
            'type' => $type,
            'year' => $annee,
        ]);

        return ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $niveau->id,
            'is_active' => true,
        ]);
    }

    private function etudiantEn(ESBTPClasse $classe): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create();

        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => ESBTPAnneeUniversitaire::factory()->create()->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        return $etudiant;
    }
}
