<?php

namespace Tests\Unit\Services\LMD;

use App\Domain\Reglages\ModificationDeReglages;
use PHPUnit\Framework\TestCase;

/**
 * La ponderation du systeme LMD est CC/Examen a l'interieur des ECUE ;
 * elle ne doit pas adopter la composition BTS General/Technique.
 */
class LmdPonderationConfigurationTest extends TestCase
{
    public function test_la_regle_activee_exige_exactement_cent_pour_cent(): void
    {
        foreach ([[40, 60], [50, 50], [0, 100], [100, 0]] as [$cc, $exam]) {
            self::assertNull(ModificationDeReglages::refusPonderationLmd([
                'active' => '1', 'cc' => $cc, 'examen' => $exam,
            ]));
        }

        foreach ([[40, 40], [0, 0], [60, 60], [101, -1], ['abc', 100]] as [$cc, $exam]) {
            self::assertNotNull(ModificationDeReglages::refusPonderationLmd([
                'active' => '1', 'cc' => $cc, 'examen' => $exam,
            ]), "Somme LMD acceptee: $cc / $exam");
        }
    }

    public function test_les_reglages_lmd_desactives_ne_produisent_aucune_ponderation(): void
    {
        self::assertNull(ModificationDeReglages::refusPonderationLmd([
            'active' => '0', 'cc' => 0, 'examen' => 0,
        ]));
    }

    public function test_le_formulaire_web_et_la_cli_partagent_le_meme_controle(): void
    {
        $controller = file_get_contents(__DIR__.'/../../../../app/Http/Controllers/ESBTP/ESBTPSettingsController.php');
        $policy = file_get_contents(__DIR__.'/../../../../app/Domain/Reglages/ModificationDeReglages.php');
        self::assertStringContainsString('ModificationDeReglages::refusPonderationLmd($poidsLmd)', $controller);
        self::assertStringContainsString('self::refusPonderationLmd($poidsLmd)', $policy);
        self::assertStringContainsString("'lmd_cc_weight' => [0, 100]", $policy);
        self::assertStringContainsString("'lmd_exam_weight' => [0, 100]", $policy);
    }
}
