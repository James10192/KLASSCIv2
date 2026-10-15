<?php

namespace Tests\Unit\Payments;

use PHPUnit\Framework\TestCase;

class PaymentValidatedAtContractTest extends TestCase
{
    private function source(string $path): string
    {
        $source = file_get_contents(__DIR__.'/../../../'.$path);
        $this->assertNotFalse($source);

        return $source;
    }

    public function test_new_validation_timestamp_is_nullable_and_historical_date_is_not_rewritten(): void
    {
        $migration = $this->source('database/migrations/2026_10_15_160000_add_validated_at_to_esbtp_paiements.php');
        $model = $this->source('app/Models/ESBTPPaiement.php');

        $this->assertStringContainsString("dateTime('validated_at')->nullable()", $migration);
        $this->assertStringNotContainsString('UPDATE esbtp_paiements', $migration);
        $this->assertStringContainsString("'validated_at' => 'datetime'", $model);
        $this->assertStringContainsString("'validated_at',", $model);
    }

    public function test_each_known_validation_writer_preserves_the_real_clock(): void
    {
        foreach ([
            'app/Domain/Comptabilite/Paiements/Actions/ValiderPaiement.php',
            'app/Services/AvoirService.php',
            'app/Domain/Comptabilite/Reconciliation/Actions/ResolveDiscrepancy.php',
            'app/Http/Controllers/ESBTPInscriptionPaiementController.php',
        ] as $path) {
            $content = $this->source($path);
            $this->assertStringContainsString("'validated_at' => now()", $content, $path);
        }

        $enrollment = $this->source('app/Http/Controllers/ESBTPInscriptionPaiementController.php');
        $this->assertSame(2, substr_count($enrollment, '"validated_at" => now()'));

        $controller = $this->source('app/Http/Controllers/ESBTPPaiementController.php');
        $this->assertStringContainsString("'heure_validation' => optional($paiement->fresh()->validated_at)->format('H:i')", $controller);
        $this->assertStringNotContainsString("date_validation->format('d/m/Y H:i')", $controller);
    }
}
