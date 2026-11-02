<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPRegleAcademique;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReglesAcademiquesAjaxTest extends TestCase
{
    use RefreshDatabase;

    private string $niveau;
    private string $filiere;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->actingAs(User::factory()->create());
        $this->niveau = ESBTPNiveauEtude::factory()->create(['is_active' => true])->name;
        $this->filiere = ESBTPFiliere::factory()->create(['is_active' => true])->name;
    }

    private function payload(): array
    {
        return [
            'niveau' => $this->niveau,
            'filiere' => $this->filiere,
            'moyenne_passage' => 10,
            'moyenne_rattrapage' => 7,
            'max_matieres_rattrapage' => 3,
            'max_redoublements' => 2,
            'autoriser_redoublement' => true,
            'actif' => true,
            'conditions_speciales' => 'Validation après délibération du conseil',
        ];
    }

    public function test_la_page_de_regles_affiche_le_nouveau_parcours(): void
    {
        $this->get(route('esbtp.reinscription.regles.index'))
            ->assertOk()
            ->assertSee('Règles académiques')
            ->assertSee('Simplifions la vie de nos personnels dans l&#039;éducation.', false);
    }

    public function test_ajout_et_modification_repondent_en_json_sans_rechargement(): void
    {
        $created = $this->postJson(route('esbtp.reinscription.regles.store'), $this->payload())
            ->assertOk()
            ->assertJsonPath('regle.moyenne_passage', '10.00')
            ->json('regle.id');

        $this->putJson(route('esbtp.reinscription.regles.update', $created), [
            'moyenne_passage' => 11,
            'moyenne_rattrapage' => 8,
            'max_matieres_rattrapage' => 0,
            'max_redoublements' => 0,
            'autoriser_redoublement' => false,
            'actif' => false,
        ])->assertOk()->assertJsonPath('regle.actif', false);

        $this->assertDatabaseHas('esbtp_regles_academiques', [
            'id' => $created,
            'moyenne_passage' => 11,
            'max_matieres_rattrapage' => 0,
            'autoriser_redoublement' => false,
            'actif' => false,
        ]);

        $this->deleteJson(route('esbtp.reinscription.regles.destroy', $created))
            ->assertOk()->assertJsonPath('id', $created);
        $this->assertDatabaseMissing('esbtp_regles_academiques', ['id' => $created]);
    }

    public function test_interdit_deux_regles_identiques_sans_remplacer_les_seuils(): void
    {
        $this->postJson(route('esbtp.reinscription.regles.store'), $this->payload())->assertOk();
        $new = [...$this->payload(), 'moyenne_passage' => 15];

        $this->postJson(route('esbtp.reinscription.regles.store'), $new)
            ->assertUnprocessable()->assertJsonValidationErrors(['niveau']);

        $this->assertEquals(10, ESBTPRegleAcademique::firstOrFail()->moyenne_passage);
        $this->assertSame(1, ESBTPRegleAcademique::count());
    }

    public function test_les_seuils_incoherents_et_filieres_inexistantes_sont_refuses(): void
    {
        $this->postJson(route('esbtp.reinscription.regles.store'), [
            ...$this->payload(),
            'filiere' => 'NOM_INEXISTANT',
            'moyenne_passage' => 10,
            'moyenne_rattrapage' => 12,
        ])->assertUnprocessable()->assertJsonValidationErrors(['filiere', 'moyenne_rattrapage']);

        $this->assertSame(0, ESBTPRegleAcademique::count());
    }
}
