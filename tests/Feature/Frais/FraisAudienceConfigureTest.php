<?php

namespace Tests\Feature\Frais;

use App\Models\ESBTPFraisCategory;
use App\Models\User;
use App\Services\FraisConfigurationWriter;
use App\Services\FraisScopeResolver;
use App\Services\TenantScolariteSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FraisAudienceConfigureTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('superAdmin', 'web');
    }

    public function test_cocher_nouveaux_sans_montant_persiste_l_audience(): void
    {
        $category = ESBTPFraisCategory::factory()->create([
            'audience' => ESBTPFraisCategory::AUDIENCE_TOUS,
        ]);

        app(FraisConfigurationWriter::class)->persistCategories(
            [
                'systeme' => FraisScopeResolver::SYSTEME_BTS,
                'filiere_id' => 1,
                'parcours_id' => null,
                'niveau_id' => 1,
            ],
            [
                $category->id => ['audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX],
            ],
            'global',
            null,
            User::factory()->create()->id,
        );

        $this->assertSame(
            ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
            $category->fresh()->audience,
        );
    }

    public function test_un_frais_nouveaux_oblige_la_question_statut_meme_sans_setting(): void
    {
        ESBTPFraisCategory::factory()->create([
            'is_active' => true,
            'audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
        ]);

        $this->assertTrue(app(TenantScolariteSettings::class)->confirmerStatutEtablissement());
    }
}
