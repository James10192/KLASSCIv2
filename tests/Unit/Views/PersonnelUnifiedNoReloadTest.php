<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

class PersonnelUnifiedNoReloadTest extends TestCase
{
    public function test_statuts_sur_le_meme_ecran_sans_rechargement(): void
    {
        $view = file_get_contents(__DIR__ . '/../../../resources/views/esbtp/personnel/unified-index.blade.php');

        $this->assertIsString($view);
        $this->assertStringContainsString('function togglePersonnelStatus(', $view);
        $this->assertStringContainsString("button.closest('.personnel-card')", $view);
        $this->assertStringContainsString("new CustomEvent('toast'", $view);
        $this->assertStringNotContainsString('location.reload()', $view);

        foreach (['Teacher', 'Comptable', 'Secretaire', 'Caissier'] as $type) {
            $this->assertStringContainsString("function toggle{$type}Status(id, button)", $view);
        }
    }
}
