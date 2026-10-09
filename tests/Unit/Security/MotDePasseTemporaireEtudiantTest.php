<?php

namespace Tests\Unit\Security;

use App\Services\Admissions\MotDePasseTemporaireEtudiant;
use PHPUnit\Framework\TestCase;

final class MotDePasseTemporaireEtudiantTest extends TestCase
{
    public function test_le_secret_est_individuel_et_suffisamment_long(): void
    {
        $a = MotDePasseTemporaireEtudiant::generer();
        $b = MotDePasseTemporaireEtudiant::generer();

        $this->assertSame(24, strlen($a));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{24}$/', $a);
        $this->assertNotSame($a, $b);
        $this->assertNotSame('Bonjour@'.date('Y'), $a);
    }
}
