<?php

namespace Tests\Feature\Frais;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\ApplicableFraisResolver;
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

    public function test_audience_est_isolee_par_combinaison_filiere_niveau(): void
    {
        $user = User::factory()->create();
        $filiere = ESBTPFiliere::factory()->create();
        $niveau1 = ESBTPNiveauEtude::factory()->create(['type' => 'BTS', 'year' => 1]);
        $niveau2 = ESBTPNiveauEtude::factory()->create(['type' => 'BTS', 'year' => 2]);
        $category = ESBTPFraisCategory::factory()->create([
            'audience' => ESBTPFraisCategory::AUDIENCE_TOUS,
            'is_active' => true,
            'is_mandatory' => true,
        ]);

        foreach ([$niveau1, $niveau2] as $niveau) {
            $configuration = ESBTPFraisConfiguration::create([
                'frais_category_id' => $category->id,
                'systeme_academique' => FraisScopeResolver::SYSTEME_BTS,
                'filiere_id' => $filiere->id,
                'niveau_id' => $niveau->id,
                'amount' => 60000,
                'amount_affecte' => 60000,
                'amount_reaffecte' => 60000,
                'amount_non_affecte' => 60000,
                'payment_deadline_days' => 30,
                'effective_date' => now()->toDateString(),
                'is_active' => true,
                'created_by' => $user->id,
            ]);
            $configuration->audience = ESBTPFraisCategory::AUDIENCE_TOUS;
            $configuration->save();
        }

        app(FraisConfigurationWriter::class)->persistCategories(
            [
                'systeme' => FraisScopeResolver::SYSTEME_BTS,
                'filiere_id' => $filiere->id,
                'parcours_id' => null,
                'niveau_id' => $niveau1->id,
            ],
            [
                $category->id => [
                    'amount_affecte' => 60000,
                    'amount_reaffecte' => 60000,
                    'amount_non_affecte' => 60000,
                    'deadline_days' => 30,
                    'audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
                ],
            ],
            'global',
            null,
            $user->id,
        );

        $config1 = ESBTPFraisConfiguration::queryForScope([
            'systeme' => FraisScopeResolver::SYSTEME_BTS,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau1->id,
        ])->where('frais_category_id', $category->id)->firstOrFail();
        $config2 = ESBTPFraisConfiguration::queryForScope([
            'systeme' => FraisScopeResolver::SYSTEME_BTS,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau2->id,
        ])->where('frais_category_id', $category->id)->firstOrFail();

        $this->assertSame(ESBTPFraisCategory::AUDIENCE_NOUVEAUX, $config1->audience);
        $this->assertSame(ESBTPFraisCategory::AUDIENCE_TOUS, $config2->audience);
        $this->assertSame(ESBTPFraisCategory::AUDIENCE_TOUS, $category->fresh()->audience, 'le catalogue ne doit plus être modifié par une combinaison');
    }

    public function test_configuration_tout_le_niveau_peut_conserver_des_audiences_differentes(): void
    {
        $user = User::factory()->create();
        $filiereA = ESBTPFiliere::factory()->create();
        $filiereB = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS', 'year' => 1]);
        $category = ESBTPFraisCategory::factory()->create([
            'audience' => ESBTPFraisCategory::AUDIENCE_TOUS,
            'is_active' => true,
            'is_mandatory' => true,
        ]);
        $writer = app(FraisConfigurationWriter::class);

        foreach ([
            [$filiereA, ESBTPFraisCategory::AUDIENCE_NOUVEAUX],
            [$filiereB, ESBTPFraisCategory::AUDIENCE_TOUS],
        ] as [$filiere, $audience]) {
            $writer->persistCategories([
                'systeme' => FraisScopeResolver::SYSTEME_BTS,
                'filiere_id' => $filiere->id,
                'parcours_id' => null,
                'niveau_id' => $niveau->id,
            ], [
                $category->id => [
                    'amount_affecte' => 60000,
                    'amount_reaffecte' => 60000,
                    'amount_non_affecte' => 60000,
                    'deadline_days' => 30,
                    'audience' => $audience,
                ],
            ], 'global', null, $user->id);
        }

        foreach ([$filiereA, $filiereB] as $filiere) {
            $writer->persistCategories([
                'systeme' => FraisScopeResolver::SYSTEME_BTS,
                'filiere_id' => $filiere->id,
                'parcours_id' => null,
                'niveau_id' => $niveau->id,
            ], [
                $category->id => [
                    'amount_affecte' => 65000,
                    'amount_reaffecte' => 65000,
                    'amount_non_affecte' => 65000,
                    'deadline_days' => 30,
                    'audience' => FraisConfigurationWriter::AUDIENCE_CONSERVER,
                ],
            ], 'global', null, $user->id);
        }

        $this->assertSame(
            ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
            ESBTPFraisConfiguration::queryForScope([
                'systeme' => FraisScopeResolver::SYSTEME_BTS,
                'filiere_id' => $filiereA->id,
                'niveau_id' => $niveau->id,
            ])->where('frais_category_id', $category->id)->value('audience')
        );
        $this->assertSame(
            ESBTPFraisCategory::AUDIENCE_TOUS,
            ESBTPFraisConfiguration::queryForScope([
                'systeme' => FraisScopeResolver::SYSTEME_BTS,
                'filiere_id' => $filiereB->id,
                'niveau_id' => $niveau->id,
            ])->where('frais_category_id', $category->id)->value('audience')
        );
        $this->assertSame(65000.0, (float) ESBTPFraisConfiguration::queryForScope([
            'systeme' => FraisScopeResolver::SYSTEME_BTS,
            'filiere_id' => $filiereA->id,
            'niveau_id' => $niveau->id,
        ])->where('frais_category_id', $category->id)->value('amount'));
    }

    public function test_resolver_utilise_l_audience_de_la_configuration_effective(): void
    {
        $category = ESBTPFraisCategory::factory()->create([
            'audience' => ESBTPFraisCategory::AUDIENCE_TOUS,
        ]);
        $configuration = new ESBTPFraisConfiguration();
        $configuration->audience = ESBTPFraisCategory::AUDIENCE_NOUVEAUX;
        $resolver = app(ApplicableFraisResolver::class);

        $this->assertTrue($resolver->categoryAppliesToStudent(
            $category,
            ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU,
            $configuration,
        ));
        $this->assertFalse($resolver->categoryAppliesToStudent(
            $category,
            ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN,
            $configuration,
        ));
    }

    public function test_un_frais_nouveaux_sur_une_configuration_oblige_la_question_statut(): void
    {
        $user = User::factory()->create();
        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $category = ESBTPFraisCategory::factory()->create([
            'is_active' => true,
            'audience' => ESBTPFraisCategory::AUDIENCE_TOUS,
        ]);

        $configuration = ESBTPFraisConfiguration::create([
            'frais_category_id' => $category->id,
            'systeme_academique' => FraisScopeResolver::SYSTEME_BTS,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'amount' => 60000,
            'payment_deadline_days' => 30,
            'effective_date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        $configuration->audience = ESBTPFraisCategory::AUDIENCE_NOUVEAUX;
        $configuration->save();

        $this->assertTrue(app(TenantScolariteSettings::class)->confirmerStatutEtablissement());
    }

    public function test_modal_presente_les_audiences_et_protege_le_mode_niveau(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/frais/partials/mandatory-categories.blade.php'));

        $this->assertStringContainsString('Audience de cette configuration', $view);
        $this->assertStringContainsString('Tous les étudiants', $view);
        $this->assertStringContainsString('Nouveaux uniquement', $view);
        $this->assertStringContainsString('Anciens uniquement', $view);
        $this->assertStringContainsString('Conserver chaque audience', $view);
        $this->assertStringContainsString('Les autres niveaux ne sont pas modifiés', $view);
        $this->assertStringContainsString('AUDIENCE_CONSERVER', $view);
        $this->assertStringNotContainsString('ce niveau et les autres où ce frais est configuré', $view);
    }
}
