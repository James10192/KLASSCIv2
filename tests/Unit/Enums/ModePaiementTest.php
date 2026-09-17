<?php

namespace Tests\Unit\Enums;

use App\Enums\ModePaiement;
use PHPUnit\Framework\TestCase;

class ModePaiementTest extends TestCase
{
    /**
     * Les modes qu'aucune évolution ne doit faire disparaître.
     *
     * Ce contrôle comptait les cases (`assertSame(8, …)`). Il était rouge
     * depuis longtemps sans que personne le remarque : trois modes légitimes
     * — carte, Djamo, autre — l'avaient fait passer à 11. Un compte se casse
     * à CHAQUE ajout justifié et ne dit jamais lequel manque ; il apprend donc
     * à ignorer l'échec, ce qui est le contraire de ce qu'un test doit faire.
     *
     * Ce qui compte vraiment ici : l'enum est lu par la RÉCONCILIATION de
     * caisse, partagée par les huit instances. Un mode retiré ne casse rien
     * bruyamment — il rend simplement les encaissements passés sous ce mode
     * invisibles du rapprochement. D'où la liste nommée plutôt qu'un total.
     */
    public function test_aucun_mode_en_service_ne_disparait(): void
    {
        $valeurs = ModePaiement::values();

        foreach ([
            'especes', 'mobile_money', 'virement', 'carte', 'cheque',
            'wave', 'orange_money', 'mtn_money', 'moov_money', 'djamo',
            'celtiis_cash', 'autre',
        ] as $attendu) {
            $this->assertContains($attendu, $valeurs, "Le mode « {$attendu} » a disparu de l'enum.");
        }
    }

    public function test_chaque_mode_est_presentable_et_sait_s_il_touche_la_caisse(): void
    {
        foreach (ModePaiement::cases() as $mode) {
            $this->assertNotSame('', $mode->label(), "Mode sans libellé : {$mode->value}");
            $this->assertNotSame('', $mode->icon(), "Mode sans icône : {$mode->value}");
        }

        // Le tiroir-caisse, c'est les espèces et rien d'autre : un mode
        // électronique compté comme physique fausserait le comptage.
        $this->assertTrue(ModePaiement::ESPECES->isDrawer());
        $this->assertFalse(ModePaiement::CELTIIS_CASH->isDrawer());
        $this->assertFalse(ModePaiement::WAVE->isDrawer());
    }

    public function test_values_returns_strings(): void
    {
        $values = ModePaiement::values();
        $this->assertContains('especes', $values);
        $this->assertContains('mobile_money', $values);
        $this->assertContains('wave', $values);
    }

    public function test_labels_are_french(): void
    {
        $this->assertSame('Espèces', ModePaiement::ESPECES->label());
        $this->assertSame('Orange Money', ModePaiement::ORANGE_MONEY->label());
    }

    public function test_select_options_format(): void
    {
        $options = ModePaiement::selectOptions();
        $this->assertArrayHasKey('especes', $options);
        $this->assertSame('Espèces', $options['especes']);
    }

    public function test_from_legacy_normalizes_variants(): void
    {
        $this->assertSame(ModePaiement::ESPECES, ModePaiement::fromLegacy('Espèces'));
        $this->assertSame(ModePaiement::ESPECES, ModePaiement::fromLegacy('ESP'));
        $this->assertSame(ModePaiement::ESPECES, ModePaiement::fromLegacy('cash'));
        $this->assertSame(ModePaiement::WAVE, ModePaiement::fromLegacy('Wave CI'));
        $this->assertSame(ModePaiement::ORANGE_MONEY, ModePaiement::fromLegacy('orange money'));
        $this->assertSame(ModePaiement::MTN_MONEY, ModePaiement::fromLegacy('MTN MoMo'));
        $this->assertSame(ModePaiement::MOOV_MONEY, ModePaiement::fromLegacy('Moov'));
        $this->assertSame(ModePaiement::MOOV_MONEY, ModePaiement::fromLegacy('flooz'));
        $this->assertSame(ModePaiement::CELTIIS_CASH, ModePaiement::fromLegacy('Celtiis Cash'));
        $this->assertSame(ModePaiement::MOBILE_MONEY, ModePaiement::fromLegacy('mobile générique'));
        $this->assertSame(ModePaiement::VIREMENT, ModePaiement::fromLegacy('virement bank'));
        $this->assertSame(ModePaiement::CHEQUE, ModePaiement::fromLegacy('chèque'));
    }

    /**
     * Celtiis Cash, le mobile money béninois — et les deux façons de le rater.
     *
     * « Celtiis Mobile Money » doit se lire Celtiis et non le générique
     * `mobile_money` : la branche `contains('celtiis')` passe donc AVANT le
     * repli `contains('mobile')`. Et le « cash » de la marque ne doit pas se
     * lire « espèces », ce qui le ferait compter au tiroir-caisse physique.
     */
    public function test_from_legacy_lit_celtiis_cash_sans_le_confondre(): void
    {
        $this->assertSame(ModePaiement::CELTIIS_CASH, ModePaiement::fromLegacy('Celtiis Cash'));
        $this->assertSame(ModePaiement::CELTIIS_CASH, ModePaiement::fromLegacy('CELTIIS'));
        $this->assertSame(ModePaiement::CELTIIS_CASH, ModePaiement::fromLegacy('Celtiis Mobile Money'));

        // Les deux confusions à ne pas faire.
        $this->assertNotSame(ModePaiement::ESPECES, ModePaiement::fromLegacy('Celtiis Cash'));
        $this->assertNotSame(ModePaiement::MOBILE_MONEY, ModePaiement::fromLegacy('Celtiis Mobile Money'));

        // Les opérateurs béninois voisins passent par les modes existants :
        // MTN Benin encaisse sous MoMo, Moov Africa Benin sous Flooz.
        $this->assertSame(ModePaiement::MTN_MONEY, ModePaiement::fromLegacy('MTN MoMo Benin'));
        $this->assertSame(ModePaiement::MOOV_MONEY, ModePaiement::fromLegacy('Moov Africa Flooz'));
    }

    public function test_from_legacy_returns_null_for_unknown(): void
    {
        $this->assertNull(ModePaiement::fromLegacy(null));
        $this->assertNull(ModePaiement::fromLegacy(''));
        $this->assertNull(ModePaiement::fromLegacy('xyz_unknown'));
    }
}
