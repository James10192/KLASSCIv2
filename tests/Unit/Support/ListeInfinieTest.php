<?php

namespace Tests\Unit\Support;

use App\Support\ListeInfinie;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Tests\TestCase;

class ListeInfinieTest extends TestCase
{
    public function test_une_tranche_du_milieu_annonce_la_suivante_et_compte_ce_qui_est_affiche(): void
    {
        $p = new LengthAwarePaginator(range(16, 30), 40, 15, 2);

        $this->assertSame(
            ['current_page' => 2, 'next_page' => 3, 'has_more' => true, 'total' => 40, 'affiches' => 30, 'par_page' => 15],
            ListeInfinie::pagination($p),
        );
    }

    public function test_la_derniere_tranche_n_annonce_rien(): void
    {
        $p = new LengthAwarePaginator(range(31, 40), 40, 15, 3);

        $pagination = ListeInfinie::pagination($p);

        $this->assertFalse($pagination['has_more']);
        $this->assertNull($pagination['next_page']);
        $this->assertSame(40, $pagination['affiches']);
    }

    public function test_un_paginateur_sans_total_ne_l_invente_pas(): void
    {
        // simplePaginate : une ligne de plus que la tranche signale la suite.
        $p = new Paginator(range(1, 51), 50, 1);

        $pagination = ListeInfinie::pagination($p);

        $this->assertNull($pagination['total']);
        $this->assertTrue($pagination['has_more']);
        $this->assertSame(50, $pagination['affiches']);
    }

    public function test_la_reponse_porte_les_lignes_rendues_et_la_pagination(): void
    {
        $p = new LengthAwarePaginator(['a', 'b'], 2, 15, 1);

        $json = ListeInfinie::reponse($p, fn (string $x) => '<tr><td>'.$x.'</td></tr>', ['stats' => ['n' => 2]])->getData(true);

        $this->assertSame('<tr><td>a</td></tr><tr><td>b</td></tr>', $json['rows_html']);
        $this->assertFalse($json['pagination']['has_more']);
        $this->assertSame(['n' => 2], $json['stats']);
    }

    public function test_seul_un_appel_ajax_en_mode_lignes_est_une_tranche(): void
    {
        $ajax = Request::create('/x', 'GET', ['mode' => 'rows']);
        $ajax->headers->set('X-Requested-With', 'XMLHttpRequest');
        $navigateur = Request::create('/x', 'GET', ['mode' => 'rows']);

        $this->assertTrue(ListeInfinie::demandee($ajax));
        $this->assertFalse(ListeInfinie::demandee($navigateur), 'Une adresse copiée avec mode=rows rend la page, pas du JSON.');
    }
}
