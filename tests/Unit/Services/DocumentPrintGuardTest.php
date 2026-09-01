<?php

namespace Tests\Unit\Services;

use App\Exceptions\ImpressionBloquee;
use App\Models\User;
use App\Services\DocumentPrintGuard;
use App\Services\PrintDecision;
use App\Services\SoldeEtudiant;
use App\Services\TenantScolariteSettings;
use Mockery;
use Tests\TestCase;

class DocumentPrintGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_solde_blocks_before_approval(): void
    {
        $decision = $this->guard(solde: 25000)->decide($this->printer(), 'certificat', 1);

        $this->assertSame(PrintDecision::SOLDE, $decision->reason);
        $this->assertFalse($decision->allowed);
    }

    public function test_setting_off_skips_both_gates(): void
    {
        $decision = $this->guard(solde: 40000, settingOn: false)->decide($this->printer(), 'certificat', 1);

        $this->assertTrue($decision->allowed);
        $this->assertFalse($decision->gated);
    }

    public function test_assert_printable_throws_impression_bloquee(): void
    {
        $this->expectException(ImpressionBloquee::class);

        $this->guard(solde: 1000)->assertPrintable($this->printer(), 'certificat', 1);
    }

    public function test_export_passes_through_when_setting_off(): void
    {
        $filtre = $this->guard(solde: 40000, settingOn: false)->filtrerExport($this->printer(), 'bulletin', [
            (object) ['id' => 1, 'etudiant_id' => 10],
        ]);

        $this->assertSame([1], $filtre['allowed_ids']);
        $this->assertSame(0, $filtre['bloques_solde']);
        $this->assertSame(0, $filtre['bloques_approbation']);
    }

    public function test_export_does_not_count_permission_as_approval(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->andReturn(false);

        $filtre = $this->guard(solde: 0)->filtrerExport($user, 'bulletin', [
            (object) ['id' => 1, 'etudiant_id' => 10],
        ]);

        $this->assertSame([], $filtre['allowed_ids']);
        $this->assertSame(0, $filtre['bloques_solde']);
        $this->assertSame(0, $filtre['bloques_approbation']);
    }

    private function printer(): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->andReturn(true);

        return $user;
    }

    private function settings(bool $on): TenantScolariteSettings
    {
        $settings = Mockery::mock(TenantScolariteSettings::class);
        $settings->shouldReceive('printRequiresApproval')->andReturn($on);

        return $settings;
    }

    private function guard(float $solde, bool $settingOn = true): DocumentPrintGuard
    {
        $soldes = Mockery::mock(SoldeEtudiant::class);
        $soldes->shouldReceive('impaye')->andReturn($solde);
        $soldes->shouldReceive('impayes')->andReturnUsing(
            fn (array $ids) => array_fill_keys($ids, $solde)
        );

        return new DocumentPrintGuard($this->settings($settingOn), $soldes);
    }
}
