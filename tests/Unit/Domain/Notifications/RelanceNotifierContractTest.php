<?php

namespace Tests\Unit\Domain\Notifications;

use App\Domain\Notifications\AbstractNotifier;
use App\Domain\Notifications\Contracts\NotifierInterface;
use App\Domain\Notifications\Notifiers\RelanceNotifier;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests Unit du contrat RelanceNotifier (architecture, sans DB).
 *
 * Valide :
 * - Implémente NotifierInterface
 * - Hérite de AbstractNotifier
 * - Expose le domain() identifiant
 * - Méthodes publiques attendues présentes (extraction depuis NotificationService)
 *
 * Note: tests métier (envoi réel) couverts par Feature tests en Phase 8b
 * quand l'extraction sera complète et la DB disponible.
 */
class RelanceNotifierContractTest extends TestCase
{
    public function test_implements_notifier_interface(): void
    {
        $reflection = new ReflectionClass(RelanceNotifier::class);

        $this->assertTrue(
            $reflection->implementsInterface(NotifierInterface::class),
            'RelanceNotifier doit implémenter NotifierInterface'
        );
    }

    public function test_extends_abstract_notifier(): void
    {
        $reflection = new ReflectionClass(RelanceNotifier::class);

        $this->assertSame(
            AbstractNotifier::class,
            $reflection->getParentClass()?->getName(),
            'RelanceNotifier doit étendre AbstractNotifier'
        );
    }

    public function test_domain_returns_relance(): void
    {
        $reflection = new ReflectionClass(RelanceNotifier::class);
        $method = $reflection->getMethod('domain');

        // Vérification statique sans instanciation (évite DI des services).
        $instance = $reflection->newInstanceWithoutConstructor();
        $this->assertSame('relance', $method->invoke($instance));
    }

    /**
     * @dataProvider expectedPublicMethodsProvider
     */
    public function test_exposes_expected_public_methods(string $methodName, bool $shouldExist = true): void
    {
        $reflection = new ReflectionClass(RelanceNotifier::class);

        if ($shouldExist) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "Méthode publique attendue : RelanceNotifier::{$methodName}()"
            );

            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "{$methodName} doit être publique");
        } else {
            $this->assertFalse($reflection->hasMethod($methodName));
        }
    }

    public static function expectedPublicMethodsProvider(): array
    {
        return [
            'envoyerEmail' => ['envoyerEmail'],
            'envoyerSMS' => ['envoyerSMS'],
            'planifier' => ['planifier'],
            'executerEnAttente' => ['executerEnAttente'],
            'getEtudiantsARelancer' => ['getEtudiantsARelancer'],
            'calculerDette' => ['calculerDette'],
            'domain' => ['domain'],
        ];
    }
}
