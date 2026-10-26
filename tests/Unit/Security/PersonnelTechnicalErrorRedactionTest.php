<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

/**
 * Régression : aucune exception SQL/interne n'est envoyée par les
 * anciens contrôleurs de gestion du personnel.
 *
 * Ce test de contrat statique complète les tests HTTP et n'en tient pas lieu.
 */
final class PersonnelTechnicalErrorRedactionTest extends TestCase
{
    public function test_legacy_personnel_controllers_do_not_echo_raw_exception_messages(): void
    {
        foreach ([
            'app/Http/Controllers/ESBTPCoordinateurController.php',
            'app/Http/Controllers/ESBTPSecretaireController.php',
            'app/Http/Controllers/Concerns/ResetsPersonnelPassword.php',
        ] as $path) {
            $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
            self::assertNotFalse($contents, "Fichier absent : {$path}");
            self::assertStringNotContainsString(
                '$e->getMessage()',
                $contents,
                "Le contrôleur {$path} ne doit jamais divulguer le détail d'une exception."
            );
        }
    }

    public function test_legacy_reset_paths_require_the_specific_revocable_permission(): void
    {
        foreach ([
            'app/Http/Controllers/ESBTPCoordinateurController.php',
            'app/Http/Controllers/ESBTPSecretaireController.php',
            'app/Http/Controllers/Concerns/ResetsPersonnelPassword.php',
        ] as $path) {
            $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
            self::assertNotFalse($contents, "Fichier absent : {$path}");
            self::assertStringContainsString(
                "can('personnel.users.password.reset')",
                $contents,
                "La réinitialisation historique {$path} exige un droit métier explicite."
            );
        }
    }
}
