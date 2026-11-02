<?php

namespace Tests\Unit\Annonces;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class JournalCourrielsAnnonceBladeTest extends TestCase
{
    public function test_le_scroll_infini_garde_le_pagineur_pour_ajax_mais_ne_l_affiche_pas(): void
    {
        $livraisons = collect([
            (object) [
                'id' => 91, 'email' => 'etudiant@example.test',
                'status' => 'retrying', 'attempts' => 1,
                'error_code' => 'local_rate_limit',
                'updated_at' => '2026-11-02 08:00:00',
            ],
        ]);
        $paginator = new LengthAwarePaginator($livraisons, 51, 25, 1, [
            'path' => 'https://example.test/esbtp/annonces/2',
            'pageName' => 'courriels_page',
        ]);

        $html = view('esbtp.annonces._courriels-liste', [
            'destinataires' => $paginator,
        ])->render();

        $this->assertStringContainsString('id="aps-email-pages" hidden', $html);
        $this->assertStringContainsString('style="display:none!important"', $html);
        $this->assertStringContainsString('rel="next"', $html);
        $this->assertStringContainsString('class="aps-deliveries-table', $html);
        $this->assertStringContainsString('Quota local temporaire', $html);
        $this->assertStringContainsString('data-livraison-id="91"', $html);
        $this->assertStringContainsString('<noscript>', $html);
    }
}
