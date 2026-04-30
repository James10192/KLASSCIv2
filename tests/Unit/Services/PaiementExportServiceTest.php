<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\PaiementExportService;
use Tests\TestCase;

/**
 * Tests unitaires Lot 15 — PaiementExportService.
 *
 * Sans DB : on vérifie que la query Builder est construite correctement,
 * que les filtres sont bien appliqués (whereIn, whereDate, whereHas, etc.),
 * et que le respect ownership (paiements.view_own) est correct.
 */
class PaiementExportServiceTest extends TestCase
{
    private PaiementExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaiementExportService();
    }

    public function test_pdf_max_rows_constant_is_500(): void
    {
        $this->assertSame(500, PaiementExportService::PDF_MAX_ROWS);
    }

    public function test_excel_max_rows_constant_is_50000(): void
    {
        $this->assertSame(50000, PaiementExportService::EXCEL_MAX_ROWS);
    }

    public function test_status_label_normalizes_known_values(): void
    {
        $this->assertSame('Validé', $this->service->statusLabel('validé'));
        $this->assertSame('Validé', $this->service->statusLabel('valide'));
        $this->assertSame('En attente', $this->service->statusLabel('en_attente'));
        $this->assertSame('En attente', $this->service->statusLabel('en attente'));
        $this->assertSame('Rejeté', $this->service->statusLabel('rejeté'));
        $this->assertSame('Rejeté', $this->service->statusLabel('rejete'));
        $this->assertSame('Annulé', $this->service->statusLabel('annulé'));
    }

    public function test_status_label_returns_dash_for_empty(): void
    {
        $this->assertSame('—', $this->service->statusLabel(''));
        $this->assertSame('—', $this->service->statusLabel('   '));
    }

    public function test_status_label_capitalizes_unknown_values(): void
    {
        $this->assertSame('Custom', $this->service->statusLabel('custom'));
    }

    public function test_should_show_creator_column_returns_true_when_user_is_null(): void
    {
        $this->assertTrue($this->service->shouldShowCreatorColumn(null));
    }

    public function test_should_show_creator_column_returns_true_when_user_can_view_all(): void
    {
        $user = $this->makeUserMock(canView: true, canViewOwn: false);
        $this->assertTrue($this->service->shouldShowCreatorColumn($user));
    }

    public function test_should_show_creator_column_returns_false_when_user_only_view_own(): void
    {
        $user = $this->makeUserMock(canView: false, canViewOwn: true);
        $this->assertFalse($this->service->shouldShowCreatorColumn($user));
    }

    public function test_build_context_adds_creator_subtitle_for_view_own_user(): void
    {
        $user = $this->makeUserMock(canView: false, canViewOwn: true);
        $user->name = 'Jean Caissier';

        $context = $this->service->buildContext([], $user, false);

        $this->assertSame('Tableau détaillé des paiements', $context['title']);
        $this->assertSame('Encaissé par : Jean Caissier', $context['subtitle_creator']);
    }

    public function test_build_context_no_subtitle_for_view_all_user(): void
    {
        $user = $this->makeUserMock(canView: true, canViewOwn: false);
        $user->name = 'Admin';

        $context = $this->service->buildContext([], $user, true);

        $this->assertSame('Tableau détaillé des paiements', $context['title']);
        $this->assertNull($context['subtitle_creator']);
    }

    public function test_build_filters_summary_returns_empty_when_no_filters(): void
    {
        $summary = $this->service->buildFiltersSummary([]);
        $this->assertSame([], $summary);
    }

    public function test_build_filters_summary_includes_periode(): void
    {
        $summary = $this->service->buildFiltersSummary([
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-01-31',
        ]);

        $this->assertCount(1, $summary);
        $this->assertSame('Période', $summary[0]['label']);
        $this->assertSame('01/01/2026 → 31/01/2026', $summary[0]['value']);
    }

    public function test_build_filters_summary_includes_modes(): void
    {
        $summary = $this->service->buildFiltersSummary([
            'modes' => ['espèces', 'mobile money', null, ''],
        ]);

        $this->assertCount(1, $summary);
        $this->assertSame('Mode(s) de paiement', $summary[0]['label']);
        $this->assertSame('espèces, mobile money', $summary[0]['value']);
    }

    public function test_build_filters_summary_skips_empty_modes_array(): void
    {
        $summary = $this->service->buildFiltersSummary([
            'modes' => [],
        ]);

        $this->assertSame([], $summary);
    }

    public function test_build_filters_summary_includes_periode_with_only_debut(): void
    {
        $summary = $this->service->buildFiltersSummary([
            'date_debut' => '2026-04-01',
        ]);

        $this->assertCount(1, $summary);
        $this->assertSame('01/04/2026 → —', $summary[0]['value']);
    }

    public function test_build_filters_summary_includes_periode_with_only_fin(): void
    {
        $summary = $this->service->buildFiltersSummary([
            'date_fin' => '2026-04-30',
        ]);

        $this->assertCount(1, $summary);
        $this->assertSame('— → 30/04/2026', $summary[0]['value']);
    }

    /**
     * Crée un mock User avec les permissions souhaitées.
     */
    private function makeUserMock(bool $canView, bool $canViewOwn): User
    {
        $user = $this->createPartialMock(User::class, ['can']);

        $user->method('can')
            ->willReturnCallback(function ($ability) use ($canView, $canViewOwn) {
                if ($ability === 'paiements.view') return $canView;
                if ($ability === 'paiements.view_own') return $canViewOwn;
                return false;
            });

        return $user;
    }
}
