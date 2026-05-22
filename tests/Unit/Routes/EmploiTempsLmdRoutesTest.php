<?php

namespace Tests\Unit\Routes;

use PHPUnit\Framework\TestCase;

/**
 * Smoke test : les routes définies dans routes/web.php pour PR8-PR13
 * sont bien des chaînes valides et correspondent à des noms attendus.
 *
 * Ne charge pas Laravel — parse simplement le fichier de routes.
 */
class EmploiTempsLmdRoutesTest extends TestCase
{
    private string $routes;

    protected function setUp(): void
    {
        $this->routes = file_get_contents(__DIR__ . '/../../../routes/web.php');
    }

    /**
     * @dataProvider expectedRoutesProvider
     */
    public function test_route_name_is_registered(string $routeName): void
    {
        $this->assertStringContainsString(
            "->name('{$routeName}')",
            $this->routes,
            "La route '{$routeName}' devrait être déclarée dans routes/web.php"
        );
    }

    public static function expectedRoutesProvider(): array
    {
        return [
            // PR9 examens
            ['kpis'],
            ['convocations.preview'],
            ['convocations.download'],
            ['bulk-generate'],
            ['surveillants.assign'],
            ['lock-notes'],

            // PR10 rattrapage
            ['lancer'],
            ['recalculer'],
            ['inscrire'],
            ['publier'],

            // PR12 jury
            ['decisions.auto'],
            ['decisions.override'],
            ['membres.store'],
            ['membres.signer'],
            ['pv.generer'],
            ['pv-preview'],
            ['pv-download'],
        ];
    }

    public function test_examens_controller_referenced(): void
    {
        $this->assertStringContainsString('ESBTPExamenPlanifieController', $this->routes);
    }

    public function test_jury_controller_referenced(): void
    {
        $this->assertStringContainsString('ESBTPLMDJuryController', $this->routes);
    }

    public function test_session_controller_referenced(): void
    {
        $this->assertStringContainsString('ESBTPLMDSessionController', $this->routes);
    }
}
