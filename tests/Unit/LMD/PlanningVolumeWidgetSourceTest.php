<?php

namespace Tests\Unit\LMD;

use PHPUnit\Framework\TestCase;

class PlanningVolumeWidgetSourceTest extends TestCase
{
    /** @test */
    public function lpv_row_bulk_fetch_always_notifies_waiting_rows(): void
    {
        $script = file_get_contents(__DIR__.'/../../../resources/views/esbtp/lmd/planning/_scripts.blade.php');

        $this->assertStringContainsString('_finish(store = {})', $script);
        $this->assertStringContainsString("window.dispatchEvent(new CustomEvent('lpv:ready'", $script);
        $this->assertStringContainsString('this._finish(json.budgets || {})', $script);
        $this->assertStringContainsString('this._finish({});', $script);
        $this->assertStringContainsString('if (!ctx.filiere_id || !ctx.niveau_id || !ctx.semestre)', $script);
        $this->assertStringContainsString('if (!resp.ok)', $script);
        $this->assertStringContainsString("console.error('lpvRow bulk fetch failed:'", $script);
    }
}
