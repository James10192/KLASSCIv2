<?php

namespace Tests\Feature\Admissions;

use App\Http\Controllers\ESBTP\ManagedCashierEntryController;
use App\Http\Requests\Inscription\StorePreInscriptionRequest;
use App\Models\Setting;
use App\Services\Admissions\InscriptionWorkflowSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ManagedCashierEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $settings = app(InscriptionWorkflowSettings::class);
        $settings->ensureDefaults();

        $this->set(InscriptionWorkflowSettings::ENABLED, '1');
        $this->set(
            InscriptionWorkflowSettings::MODE,
            InscriptionWorkflowSettings::MODE_CAISSE_AVANT_PIECES,
        );
    }

    /** @test */
    public function lentree_historique_de_caisse_ouvre_la_file_des_candidatures_quand_le_workflow_est_active(): void
    {
        $response = app(ManagedCashierEntryController::class)->show();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            route('esbtp.admissions.workflow.index'),
            $response->getTargetUrl(),
        );
        $this->assertSame(
            'Les preinscriptions sont alimentees par les candidatures en ligne acceptees.',
            session('info'),
        );
    }

    /** @test */
    public function la_ressaisie_manuelle_est_refusee_quand_le_workflow_est_active(): void
    {
        $request = StorePreInscriptionRequest::create(
            '/esbtp/inscriptions/pre-inscription',
            'POST',
            [
                'nom' => 'KOUASSI',
                'prenoms' => 'Awa',
            ],
        );

        $response = app(ManagedCashierEntryController::class)->store($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            route('esbtp.admissions.workflow.index'),
            $response->getTargetUrl(),
        );
        $this->assertStringContainsString(
            'ressaisie manuelle est desactivee',
            (string) session('warning'),
        );
    }

    private function set(string $key, string $value): void
    {
        Setting::where('key', $key)->update(['value' => $value]);
        Cache::forget('setting_'.$key);
    }
}
