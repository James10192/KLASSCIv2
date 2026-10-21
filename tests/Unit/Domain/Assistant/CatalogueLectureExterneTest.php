<?php

namespace Tests\Unit\Domain\Assistant;

use App\Domain\Assistant\Mcp\CatalogueLectureExterne;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Models\User;
use App\Services\Chatbot\Tools\GetDashboardKpisTool;
use App\Services\Chatbot\Tools\SearchClassesTool;
use Mockery;
use Tests\TestCase;

class CatalogueLectureExterneTest extends TestCase
{
    private function utilisateur(array $permissions): User
    {
        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->andReturnUsing(
            fn (string $permission): bool => in_array($permission, $permissions, true)
        );

        return $user;
    }

    private function catalogue(): CatalogueOutils
    {
        /** @var CatalogueOutils $catalogue */
        $catalogue = Mockery::mock(CatalogueOutils::class);
        $catalogue->shouldReceive('outil')->with('search_classes')->andReturn(new SearchClassesTool());
        $catalogue->shouldReceive('outil')->with('get_dashboard_kpis')->andReturn(new GetDashboardKpisTool());

        return $catalogue;
    }

    public function test_desactive_par_defaut_et_ne_declenche_aucun_outil(): void
    {
        config()->set('mcp.read_only.enabled', false);
        $adaptateur = new CatalogueLectureExterne($this->catalogue());

        $this->assertSame([], $adaptateur->schemas($this->utilisateur(['classes.view'])));
        $this->assertSame(
            ['error' => 'Outil indisponible.'],
            $adaptateur->executer('search_classes', [], $this->utilisateur(['classes.view']))
        );
    }

    public function test_expose_seulement_les_lectures_autorisees_avec_leurs_annotations(): void
    {
        config()->set('mcp.read_only.enabled', true);
        $adaptateur = new CatalogueLectureExterne($this->catalogue());

        $schemas = $adaptateur->schemas($this->utilisateur(['classes.view']));
        $this->assertCount(1, $schemas);
        $this->assertSame('search_classes', $schemas[0]['name']);
        $this->assertSame(['readOnlyHint' => true, 'destructiveHint' => false], $schemas[0]['annotations']);
        $this->assertSame('object', $schemas[0]['inputSchema']['type']);

        $this->assertSame([], $adaptateur->schemas($this->utilisateur([])));
    }

    public function test_refuse_une_ecriture_et_une_lecture_sans_permission(): void
    {
        config()->set('mcp.read_only.enabled', true);
        $adaptateur = new CatalogueLectureExterne($this->catalogue());

        $user = $this->utilisateur(['classes.view', 'dashboard.view']);
        $this->assertSame(
            ['error' => 'Outil indisponible.'],
            $adaptateur->executer('proposer_creation_classes', ['nombre' => 100], $user)
        );

        $this->assertSame(
            ['error' => 'Outil indisponible.'],
            $adaptateur->executer('search_classes', [], $this->utilisateur([]))
        );
    }

    public function test_ne_transmet_pas_les_widgets_ni_les_liens_de_navigation(): void
    {
        config()->set('mcp.read_only.enabled', true);
        $catalogue = $this->catalogue();
        $catalogue->shouldReceive('executer')->once()
            ->with('search_classes', ['limit' => 1], Mockery::type(User::class))
            ->andReturn([
                'results' => [['nom' => 'L1']],
                'count' => 1,
                'total' => 1,
                'widget' => ['kind' => 'private'],
                'deep_link' => 'https://tenant.example/private',
                'private_token' => 'should-not-leak',
            ]);

        $resultat = (new CatalogueLectureExterne($catalogue))
            ->executer('search_classes', ['limit' => 1], $this->utilisateur(['classes.view']));

        $this->assertSame(['results' => [['nom' => 'L1']], 'count' => 1, 'total' => 1], $resultat);
        $this->assertArrayNotHasKey('widget', $resultat);
        $this->assertArrayNotHasKey('deep_link', $resultat);
    }
}
