<?php

namespace Tests\Feature\Evaluation;

use App\Http\Controllers\ESBTPEvaluationController;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * Garde-fou non bloquant : saisie d'une matière classée « specialite » sur une classe
 * de tronc commun. Basé sur la classe cible, PAS sur le numéro de semestre.
 */
class EvaluationTcSpecialiteGuardTest extends TestCase
{
    use RefreshDatabase;

    private function warning(?ESBTPClasse $classe, ?ESBTPMatiere $matiere): ?string
    {
        $ref = new ReflectionClass(ESBTPEvaluationController::class);
        $controller = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('troncCommunSpecialiteWarning');
        $method->setAccessible(true);

        return $method->invoke($controller, $classe, $matiere);
    }

    private function makeCombo(bool $tronc, ?string $classification): array
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => $tronc,
            'parent_id' => null,
        ]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
        ]);
        $matiere = ESBTPMatiere::factory()->create(['name' => 'Securite', 'is_active' => true]);
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'classification' => $classification,
        ]);

        return [$classe, $matiere];
    }

    public function test_avertit_sur_classe_tc_avec_matiere_specialite(): void
    {
        [$classe, $matiere] = $this->makeCombo(true, ESBTPMatiereFilierNiveau::SPECIALITE);

        $warning = $this->warning($classe, $matiere);

        $this->assertNotNull($warning);
        $this->assertStringContainsString('Spécialité', $warning);
    }

    public function test_silencieux_sur_classe_tc_avec_matiere_tronc_commun_ou_null(): void
    {
        [$classeTc, $matiereTc] = $this->makeCombo(true, ESBTPMatiereFilierNiveau::TRONC_COMMUN);
        $this->assertNull($this->warning($classeTc, $matiereTc));

        [$classeNull, $matiereNull] = $this->makeCombo(true, null);
        $this->assertNull($this->warning($classeNull, $matiereNull));
    }

    public function test_silencieux_sur_classe_de_specialite(): void
    {
        [$classe, $matiere] = $this->makeCombo(false, ESBTPMatiereFilierNiveau::SPECIALITE);

        $this->assertNull($this->warning($classe, $matiere));
    }
}
