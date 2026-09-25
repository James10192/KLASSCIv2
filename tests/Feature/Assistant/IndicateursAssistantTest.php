<?php

namespace Tests\Feature\Assistant;

use App\Domain\Students\StudentCountService;
use App\Models\User;
use App\Services\Chatbot\Tools\GetDashboardKpisTool;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * L'assistant annonçait le total de la base (262) pour les inscrits de l'année
 * (214) : l'outil des indicateurs doit rendre les mêmes chiffres que le tableau
 * de bord, chacun avec sa portée dans le nom.
 */
class IndicateursAssistantTest extends TestCase
{
    use DatabaseTransactions;

    public function test_les_inscrits_sont_ceux_de_l_annee_courante_comme_au_tableau_de_bord(): void
    {
        $comptes = app(StudentCountService::class)->counts();

        $kpis = (new GetDashboardKpisTool())->execute(['focus' => 'general'], User::factory()->create())['kpis'];

        $this->assertSame($comptes['inscrits_annee_courante'], $kpis['inscrits_annee_courante']);
        $this->assertSame($comptes['total_base'], $kpis['etudiants_en_base_toutes_annees']);
        $this->assertArrayHasKey('annee_courante', $kpis);
        $this->assertArrayNotHasKey('etudiants', $kpis, 'un total sans portée est pris pour les inscrits');
        $this->assertArrayNotHasKey('inscriptions_actives', $kpis);
    }
}
