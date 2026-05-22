<?php

namespace Tests\Feature\SeanceCours;

use Tests\TestCase;

/**
 * Test Feature pour la matrice type_seance BTS vs LMD (PR5).
 *
 * Verifier que :
 * - LMD classe (Licence/Master/Doctorat) → partial _form_type_seance_lmd inclus
 * - BTS classe → partial _form_type_seance_bts inclus
 * - Les sous-types sont coherents avec le systeme academique
 *
 * @see resources/views/esbtp/seances-cours/partials/_form_type_seance_lmd.blade.php
 * @see resources/views/esbtp/seances-cours/partials/_form_type_seance_bts.blade.php
 * @see docs/MASTER-PLAN-emploi-temps-lmd-unification.md PR5
 */
class TypeSeanceMatrixTest extends TestCase
{
    /** @test */
    public function bts_partial_file_exists(): void
    {
        $path = resource_path('views/esbtp/seances-cours/partials/_form_type_seance_bts.blade.php');
        $this->assertFileExists($path, 'Le partial BTS doit exister apres PR5');
    }

    /** @test */
    public function lmd_partial_file_exists(): void
    {
        $path = resource_path('views/esbtp/seances-cours/partials/_form_type_seance_lmd.blade.php');
        $this->assertFileExists($path, 'Le partial LMD doit exister (avant PR5)');
    }

    /** @test */
    public function bts_partial_has_only_cm_td_tp(): void
    {
        $path = resource_path('views/esbtp/seances-cours/partials/_form_type_seance_bts.blade.php');
        $content = file_get_contents($path);

        $this->assertStringContainsString("'CM'", $content);
        $this->assertStringContainsString("'TD'", $content);
        $this->assertStringContainsString("'TP'", $content);
        $this->assertStringNotContainsString("'PROJET'", $content, 'PROJET est LMD-only');
        $this->assertStringNotContainsString("'EXAMEN'", $content, 'EXAMEN est LMD-only en PR5 (changera en PR6+)');
        $this->assertStringNotContainsString("'RATTRAPAGE'", $content, 'RATTRAPAGE est LMD-only');
    }

    /** @test */
    public function create_blade_has_conditional_include(): void
    {
        $path = resource_path('views/esbtp/seances-cours/create.blade.php');
        $content = file_get_contents($path);

        $this->assertStringContainsString(
            '_form_type_seance_lmd',
            $content,
            'create.blade.php doit inclure _form_type_seance_lmd'
        );
        $this->assertStringContainsString(
            '_form_type_seance_bts',
            $content,
            'create.blade.php doit inclure _form_type_seance_bts (PR5)'
        );
        $this->assertStringContainsString(
            "\$isLmdClasse",
            $content,
            'create.blade.php doit utiliser variable \$isLmdClasse pour conditional include'
        );
    }
}
