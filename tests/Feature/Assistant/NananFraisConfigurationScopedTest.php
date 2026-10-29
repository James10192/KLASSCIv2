<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\Frais\PoserBareme;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotConversation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\FraisConfigurationWriter;
use App\Services\FraisScopeResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NananFraisConfigurationScopedTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            PaywallMiddleware::class,
            EnsureInstalled::class,
            CheckInstalled::class,
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);

        Role::findOrCreate('superAdmin', 'web');
        foreach (['frais.configure', 'frais.create', 'frais.edit'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = User::withoutEvents(fn () => User::factory()->create([
            'username' => 'nanan_frais_'.Str::lower(Str::random(6)),
        ]));
        $this->admin->assignRole('superAdmin');
        $this->admin->givePermissionTo(['frais.configure', 'frais.create', 'frais.edit']);
        $this->actingAs($this->admin);

        app(ContexteDEchange::class)->conversation = ChatbotConversation::create([
            'user_id' => $this->admin->id,
            'session_id' => (string) Str::uuid(),
            'last_activity_at' => now(),
        ]);
    }

    public function test_nanan_change_nouveaux_et_echeance_sur_un_niveau_sans_toucher_l_autre(): void
    {
        $filiere = ESBTPFiliere::factory()->create(['code' => 'NF-BAT']);
        $niveau1 = ESBTPNiveauEtude::factory()->create(['code' => 'NF-BTS1', 'year' => 1, 'type' => 'BTS']);
        $niveau2 = ESBTPNiveauEtude::factory()->create(['code' => 'NF-BTS2', 'year' => 2, 'type' => 'BTS']);
        $category = ESBTPFraisCategory::factory()->create([
            'code' => 'NF-TENUE',
            'name' => 'Tenue Nanan frais',
            'audience' => ESBTPFraisCategory::AUDIENCE_TOUS,
        ]);

        $writer = app(FraisConfigurationWriter::class);
        foreach ([$niveau1, $niveau2] as $niveau) {
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
                    'audience' => ESBTPFraisCategory::AUDIENCE_TOUS,
                    'deadline_days' => 30,
                ],
            ], 'global', null, $this->admin->id);
        }

        $proposition = app(PoserBareme::class)->executeAuthorized([
            'configurations' => [[
                'categorie' => 'NF-TENUE',
                'filiere' => 'NF-BAT',
                'niveau' => 'NF-BTS1',
                'montant' => 60000,
                'audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
                'echeance_jours' => 45,
            ]],
        ], $this->admin);

        $this->assertSame('approbation', $proposition['widget']['kind'] ?? null);
        $this->assertStringContainsString('Nouveaux uniquement', json_encode($proposition, JSON_UNESCAPED_UNICODE));
        $this->assertSame(
            ESBTPFraisCategory::AUDIENCE_TOUS,
            ESBTPFraisConfiguration::where('filiere_id', $filiere->id)->where('niveau_id', $niveau1->id)->value('audience'),
            'Nanan ne doit rien écrire avant Valider'
        );

        $this->postJson($proposition['widget']['valider_url'], ['jeton' => $proposition['widget']['jeton']])
            ->assertJson(['statut' => 'executee']);

        $niveau1Config = ESBTPFraisConfiguration::where('filiere_id', $filiere->id)->where('niveau_id', $niveau1->id)->firstOrFail();
        $niveau2Config = ESBTPFraisConfiguration::where('filiere_id', $filiere->id)->where('niveau_id', $niveau2->id)->firstOrFail();

        $this->assertSame(ESBTPFraisCategory::AUDIENCE_NOUVEAUX, $niveau1Config->audience);
        $this->assertSame(45, (int) $niveau1Config->payment_deadline_days);
        $this->assertSame(ESBTPFraisCategory::AUDIENCE_TOUS, $niveau2Config->audience);
        $this->assertSame(30, (int) $niveau2Config->payment_deadline_days);
    }

    public function test_nanan_peut_changer_seulement_l_audience_sans_redemander_ni_ecraser_les_montants(): void
    {
        $filiere = ESBTPFiliere::factory()->create(['code' => 'NF-GC']);
        $niveau = ESBTPNiveauEtude::factory()->create(['code' => 'NF-BTS1-GC', 'year' => 1, 'type' => 'BTS']);
        $category = ESBTPFraisCategory::factory()->create([
            'code' => 'NF-INS',
            'name' => 'Inscription Nanan frais',
            'audience' => ESBTPFraisCategory::AUDIENCE_TOUS,
        ]);

        app(FraisConfigurationWriter::class)->persistCategories([
            'systeme' => FraisScopeResolver::SYSTEME_BTS,
            'filiere_id' => $filiere->id,
            'parcours_id' => null,
            'niveau_id' => $niveau->id,
        ], [
            $category->id => [
                'amount_affecte' => 120000,
                'amount_reaffecte' => 110000,
                'amount_non_affecte' => 130000,
                'audience' => ESBTPFraisCategory::AUDIENCE_TOUS,
                'deadline_days' => 47,
            ],
        ], 'global', null, $this->admin->id);

        $avant = ESBTPFraisConfiguration::where('filiere_id', $filiere->id)
            ->where('niveau_id', $niveau->id)
            ->where('frais_category_id', $category->id)
            ->firstOrFail();

        $proposition = app(PoserBareme::class)->executeAuthorized([
            'configurations' => [[
                'categorie' => 'NF-INS',
                'filiere' => 'NF-GC',
                'niveau' => 'NF-BTS1-GC',
                'audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
            ]],
        ], $this->admin);

        $this->assertSame('approbation', $proposition['widget']['kind'] ?? null);
        $this->assertStringNotContainsString('Quel montant', json_encode($proposition, JSON_UNESCAPED_UNICODE));

        $this->postJson($proposition['widget']['valider_url'], ['jeton' => $proposition['widget']['jeton']])
            ->assertJson(['statut' => 'executee']);

        $apres = $avant->fresh();
        $this->assertSame(ESBTPFraisCategory::AUDIENCE_NOUVEAUX, $apres->audience);
        $this->assertSame(120000.0, (float) $apres->amount_affecte);
        $this->assertSame(110000.0, (float) $apres->amount_reaffecte);
        $this->assertSame(130000.0, (float) $apres->amount_non_affecte);
        $this->assertSame(47, (int) $apres->payment_deadline_days);
    }
}
