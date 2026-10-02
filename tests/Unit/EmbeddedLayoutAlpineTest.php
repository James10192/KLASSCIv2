<?php

namespace Tests\Unit;

use Tests\TestCase;

class EmbeddedLayoutAlpineTest extends TestCase
{
    public function test_embedded_layout_loads_alpine_before_stacked_scripts(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/embedded.blade.php'));

        $this->assertStringContainsString('@alpinejs/focus', $layout);
        // Version épinglée : « 3.x.x » laissait le CDN servir une version différente d'un jour à l'autre.
        $this->assertMatchesRegularExpression('#npm/alpinejs@3\.\d+\.\d+/#', $layout);
        $this->assertStringContainsString('[x-cloak]', $layout);

        $alpinePosition = strpos($layout, 'npm/alpinejs@3.');
        $stackPosition = strpos($layout, '@stack(\'scripts\')');

        $this->assertIsInt($alpinePosition);
        $this->assertIsInt($stackPosition);
        $this->assertLessThan($stackPosition, $alpinePosition);
    }
}
