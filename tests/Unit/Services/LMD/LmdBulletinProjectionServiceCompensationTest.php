<?php

namespace Tests\Unit\Services\LMD;

use App\Models\ESBTPLMDResultatUE;
use App\Services\AppreciationScaleService;
use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMD\LmdBulletinProjectionService;
use App\Services\LMDBulletinService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class LmdBulletinProjectionServiceCompensationTest extends TestCase
{
    private function projection(array $settings): LmdBulletinProjectionService
    {
        $profile = new LmdAcademicRuleProfile(
            fn (string $key, mixed $default = null): mixed => $settings[$key] ?? $default
        );

        return new LmdBulletinProjectionService(
            new LMDBulletinService($profile),
            $profile,
            new AppreciationScaleService(fn (string $key, mixed $default = null): mixed => $default),
        );
    }

    public function test_projection_live_applique_le_meme_plancher_apc_que_le_bulletin(): void
    {
        $resultats = [
            ['moyenne' => 14.0, 'credit' => 6, 'statut' => ESBTPLMDResultatUE::STATUT_AQ, 'credits_capitalises' => 0],
            ['moyenne' => 7.5, 'credit' => 4, 'statut' => ESBTPLMDResultatUE::STATUT_NAQ, 'credits_capitalises' => 0],
            ['moyenne' => 8.5, 'credit' => 4, 'statut' => ESBTPLMDResultatUE::STATUT_NAQ, 'credits_capitalises' => 0],
        ];

        $method = new ReflectionMethod(LmdBulletinProjectionService::class, 'appliquerCompensationLive');
        $credits = $method->invokeArgs(
            $this->projection(['lmd_compensation_inter_ue_minimum' => '8']),
            [&$resultats, 11.0],
        );

        $this->assertSame(10, $credits);
        $this->assertSame(ESBTPLMDResultatUE::STATUT_NAQ, $resultats[1]['statut']);
        $this->assertSame(ESBTPLMDResultatUE::STATUT_APC, $resultats[2]['statut']);
    }
}
