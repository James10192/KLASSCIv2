<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PwaServiceWorkerSafetyTest extends TestCase
{
    public function test_authenticated_pages_are_not_cached_by_the_service_worker(): void
    {
        $serviceWorker = file_get_contents(__DIR__ . '/../../public/sw.js');

        $this->assertStringContainsString('const VERSION = "klassci-v3"', $serviceWorker);
        $this->assertStringNotContainsString('pages: "klassci-pages-', $serviceWorker);
        $this->assertStringNotContainsString('studentData:', $serviceWorker);
        $this->assertStringNotContainsString('new strategies.NetworkFirst', $serviceWorker);
        $this->assertStringContainsString('new strategies.NetworkOnly()', $serviceWorker);
    }

    public function test_update_activation_preserves_the_current_url(): void
    {
        $layout = file_get_contents(__DIR__ . '/../../resources/views/layouts/app.blade.php');

        $this->assertStringContainsString("sessionStorage.setItem('klassci:pwa-return-url'", $layout);
        $this->assertStringContainsString("sessionStorage.setItem('klassci:pwa-update-dismissed'", $layout);
        $this->assertStringContainsString('if (returnUrl === window.location.href)', $layout);
        $this->assertStringContainsString('window.location.reload()', $layout);
        $this->assertStringContainsString('window.location.replace(returnUrl)', $layout);
        $this->assertStringContainsString("register('/sw.js?v=klassci-v3'", $layout);
        $this->assertStringContainsString("updateViaCache: 'none'", $layout);
    }
}
