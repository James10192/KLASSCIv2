<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPDocumentApproval;
use App\Models\User;
use App\Services\DocumentPrintGuard;
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
        $guard = $this->guard(solde: 25000, approved: false);
        $user = $this->printer();

        $this->assertSame(DocumentPrintGuard::DENY_SOLDE, $guard->denyReason($user, 'certificat', 1));
        $this->assertFalse($guard->canPrint($user, 'certificat', 1));
    }

    public function test_paid_without_approval_is_blocked(): void
    {
        $guard = $this->guard(solde: 0, approved: false);
        $user = $this->printer();

        $this->assertSame(DocumentPrintGuard::DENY_APPROVAL, $guard->denyReason($user, 'bulletin', 1, 9));
    }

    public function test_paid_and_approved_can_print(): void
    {
        $guard = $this->guard(solde: 0, approved: true);
        $user = $this->printer();

        $this->assertTrue($guard->canPrint($user, 'bulletin', 1, 9));
    }

    public function test_setting_off_skips_both_gates(): void
    {
        $guard = $this->guard(solde: 40000, approved: false, settingOn: false);
        $user = $this->printer();

        $this->assertTrue($guard->canPrint($user, 'certificat', 1));
    }

    public function test_export_keeps_printable_and_counts_blocks(): void
    {
        $guard = $this->guard(solde: 0, approved: false);
        $user = $this->printer();
        $docs = [
            (object) ['id' => 1, 'etudiant_id' => 10],
            (object) ['id' => 2, 'etudiant_id' => 11],
        ];

        $filtre = $guard->filtrerExport($user, 'bulletin', $docs);

        $this->assertSame([], $filtre['allowed_ids']);
        $this->assertSame(0, $filtre['bloques_solde']);
        $this->assertSame(2, $filtre['bloques_approbation']);
    }

    private function printer(): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->andReturn(true);

        return $user;
    }

    private function guard(float $solde, bool $approved, bool $settingOn = true): DocumentPrintGuard
    {
        $settings = Mockery::mock(TenantScolariteSettings::class);
        $settings->shouldReceive('printRequiresApproval')->andReturn($settingOn);

        return new class($settings, $solde, $approved) extends DocumentPrintGuard {
            public function __construct(
                TenantScolariteSettings $settings,
                private readonly float $soldeFixe,
                private readonly bool $estApprouve,
            ) {
                parent::__construct($settings);
            }

            public function soldeImpaye(int $etudiantId): float
            {
                return $this->soldeFixe;
            }

            public function latestApproved(string $documentType, int $etudiantId, ?int $documentId = null): ?ESBTPDocumentApproval
            {
                if (! $this->estApprouve) {
                    return null;
                }

                $row = new ESBTPDocumentApproval();
                $row->status = ESBTPDocumentApproval::STATUS_APPROVED;

                return $row;
            }
        };
    }
}
