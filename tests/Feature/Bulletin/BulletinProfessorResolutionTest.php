<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\User;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class BulletinProfessorResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_academic_planning_teacher_when_no_manual_bulletin_template_exists(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $matiere = ESBTPMatiere::factory()->create();
        $enseignant = User::factory()->create(['name' => 'Mme Awa Koné']);

        ESBTPPlanificationAcademique::create([
            'annee_universitaire_id' => $annee->id,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'semestre' => 1,
            'matiere_id' => $matiere->id,
            'volume_horaire_total' => 30,
            'volume_horaire_cm' => 30,
            'volume_horaire_td' => 0,
            'volume_horaire_tp' => 0,
            'coefficient' => 2,
            'credits_ects' => 0,
            'enseignant_principal_id' => $enseignant->id,
            'statut' => ESBTPPlanificationAcademique::STATUT_VALIDE,
            'is_active' => true,
        ]);

        $service = app(BulletinService::class);
        $method = new ReflectionMethod($service, 'professeursPayloadForBulletin');
        $method->setAccessible(true);

        $professeurs = $method->invoke($service, $classe->id, $annee->id, 'semestre1');

        $this->assertSame([$matiere->id => 'Mme Awa Koné'], $professeurs);
    }
}
