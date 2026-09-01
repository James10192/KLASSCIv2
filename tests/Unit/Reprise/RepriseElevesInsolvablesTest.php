<?php

namespace Tests\Unit\Reprise;

use App\Services\Frais\ServirLesFrais;
use App\Services\Reprise\RepriseElevesInsolvables;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * La remise et l'ordre de service touchent a des montants reels. Ils se testent
 * sans base : ce sont des regles de calcul, pas des ecritures.
 */
class RepriseElevesInsolvablesTest extends TestCase
{
    private function remise(array $tarifs, float $remise): array
    {
        $service = new RepriseElevesInsolvables(new ServirLesFrais());

        return (new ReflectionMethod($service, 'appliquerLaRemise'))
            ->invoke($service, $tarifs, $remise);
    }

    public function test_sans_remise_les_tarifs_ne_bougent_pas(): void
    {
        $tarifs = [1 => 150000.0, 2 => 40000.0, 3 => 3000.0];

        $this->assertSame($tarifs, $this->remise($tarifs, 0));
    }

    public function test_la_remise_soulage_les_frais_les_moins_prioritaires(): void
    {
        // L'ordre du tableau est celui de l'ecole : la scolarite d'abord.
        $tarifs = [1 => 150000.0, 2 => 40000.0, 3 => 3000.0];

        $obtenu = $this->remise($tarifs, 43000);

        // Les deux derniers sont effaces, la scolarite est intacte.
        $this->assertSame([1 => 150000.0], $obtenu);
    }

    public function test_une_categorie_ramenee_a_zero_disparait(): void
    {
        // Une souscription nulle se confondrait avec une exemption.
        $obtenu = $this->remise([1 => 10000.0, 2 => 5000.0], 5000);

        $this->assertArrayNotHasKey(2, $obtenu);
        $this->assertSame([1 => 10000.0], $obtenu);
    }

    public function test_le_cas_reel_de_la_remise_islg(): void
    {
        // LOU singa ruth : 310 000 reclames, 160 000 de remise, 80 000 payes,
        // 70 000 restants. La somme souscrite doit tomber a 150 000.
        $tarifs = [1 => 150000.0, 2 => 120000.0, 3 => 40000.0];
        $this->assertSame(310000.0, array_sum($tarifs));

        $souscrit = $this->remise($tarifs, 160000);

        $this->assertSame(150000.0, array_sum($souscrit));

        // Puis 80 000 servent les frais dans l'ordre de l'ecole.
        $service = (new ServirLesFrais())->servir(80000, $souscrit);

        $this->assertSame(70000.0, round(array_sum($service['reste']), 2));
        $this->assertSame(0.0, round($service['reliquat'], 2));
    }

    public function test_le_service_respecte_l_ordre_de_l_ecole(): void
    {
        // 100 000 sur trois frais : le premier est solde avant que le second
        // ne recoive quoi que ce soit.
        $service = (new ServirLesFrais())->servir(100000, [7 => 60000.0, 4 => 50000.0, 9 => 3000.0]);

        $this->assertSame(60000.0, $service['allocations'][7]);
        $this->assertSame(40000.0, $service['allocations'][4]);
        $this->assertArrayNotHasKey(9, $service['allocations']);
        $this->assertSame(13000.0, round(array_sum($service['reste']), 2));
    }
}
