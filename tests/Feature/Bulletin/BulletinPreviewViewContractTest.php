<?php

namespace Tests\Feature\Bulletin;

use Tests\TestCase;

class BulletinPreviewViewContractTest extends TestCase
{
    public function test_preview_views_exist_and_are_wired(): void
    {
        $service = file_get_contents(app_path('Services/BulletinService.php'));

        $this->assertStringContainsString("esbtp.bulletins.preview-abidjan", $service);
        $this->assertStringContainsString("esbtp.bulletins.preview';", $service);
        $this->assertStringNotContainsString('preview-configurable', $service);
        $this->assertTrue(view()->exists('esbtp.bulletins.preview'));
        $this->assertTrue(view()->exists('esbtp.bulletins.preview-abidjan'));
        $this->assertFalse(view()->exists('esbtp.bulletins.preview-configurable'));
        $this->assertFalse(view()->exists('esbtp.bulletins.preview-configurable-abidjan'));
    }
}
