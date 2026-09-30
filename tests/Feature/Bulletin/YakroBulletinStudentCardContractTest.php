<?php

namespace Tests\Feature\Bulletin;

use Tests\TestCase;

class YakroBulletinStudentCardContractTest extends TestCase
{
    public function test_yakro_student_card_uses_dompdf_safe_photo_fallback_and_table_layout(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/bulletins/pdf-configurable.blade.php'));

        $this->assertStringContainsString("public_path('images/placeholders/student-avatar-fallback.png')", $view);
        $this->assertStringContainsString('base64_encode(file_get_contents($avatarFallbackPath))', $view);
        $this->assertStringContainsString('class="student-photo-cell"', $view);
        $this->assertStringContainsString('class="student-photo"', $view);
        $this->assertStringContainsString('class="info-section-title">Identité</div>', $view);
        $this->assertStringContainsString('class="info-section-title">Scolarité</div>', $view);
        $this->assertStringContainsString('student-academic-group', $view);
        $this->assertStringContainsString('info-value--primary', $view);
    }
}
