<?php

namespace Tests\Unit\Enums;

use App\Enums\ModePaiement;
use Tests\TestCase;

/**
 * Les modes de paiement ont deux sources : `config/payment_modes.php`, que lisent
 * les ecrans de caisse, et l'enum `ModePaiement`, que lit la RECONCILIATION.
 *
 * Quand elles divergent, rien ne casse bruyamment — et c'est le probleme. En
 * septembre 2026, `carte`, `djamo` et `autre` existaient dans la config et dans
 * l'interface, mais pas dans l'enum : un encaissement par carte etait accepte au
 * guichet, puis **invisible du rapprochement**. De l'argent recu qu'aucun
 * comptage ne pouvait justifier, et un ecart de caisse inexplicable.
 *
 * Ce test ne verifie pas un comportement : il verifie que les deux listes
 * disent la meme chose. C'est la seule facon d'empecher la derive de revenir,
 * puisqu'elle ne se voit pas a l'execution.
 */
class ModePaiementAccordeALaConfigTest extends TestCase
{
    /** @return list<string> */
    private function modesDeLaConfig(): array
    {
        $modes = array_keys(config('payment_modes.labels', []));
        sort($modes);

        return $modes;
    }

    /** @return list<string> */
    private function modesDeLEnum(): array
    {
        $modes = ModePaiement::values();
        sort($modes);

        return $modes;
    }

    public function test_les_deux_sources_listent_exactement_les_memes_modes(): void
    {
        $config = $this->modesDeLaConfig();
        $enum = $this->modesDeLEnum();

        $this->assertNotEmpty($config, 'config/payment_modes.php ne declare aucun libelle de mode');

        $this->assertSame(
            $config,
            $enum,
            "Un mode present d'un seul cote est un mode que la caisse accepte et que "
            ."la reconciliation ignore, ou l'inverse. Ajoutez-le des deux cotes."
        );
    }

    public function test_chaque_mode_porte_un_libelle_et_une_icone(): void
    {
        foreach (ModePaiement::cases() as $mode) {
            $this->assertNotSame('', trim($mode->label()), $mode->value);
            $this->assertNotSame('', trim($mode->icon()), $mode->value);
        }
    }

    public function test_seules_les_especes_vont_dans_le_tiroir(): void
    {
        // La reconciliation compte le tiroir physique. Un virement, une carte ou
        // un paiement mobile n'y laissent rien : les compter fausserait l'ecart.
        foreach (ModePaiement::cases() as $mode) {
            $this->assertSame(
                $mode === ModePaiement::ESPECES,
                $mode->isDrawer(),
                $mode->value
            );
        }
    }

    public function test_les_libelles_verbeux_de_la_config_retombent_sur_un_mode_canonique(): void
    {
        // La table d'alias de la config sert a rattraper les valeurs libres
        // historiques. Chacune doit viser un mode qui existe vraiment.
        $canoniques = ModePaiement::values();

        foreach ((array) config('payment_modes.aliases', []) as $alias => $cible) {
            $this->assertContains(
                $cible,
                $canoniques,
                "L'alias « {$alias} » pointe vers « {$cible} », qui n'est pas un mode canonique."
            );
        }
    }
}
