<?php

namespace Tests\Unit\Components;

use PHPUnit\Framework\TestCase;
use Tests\Unit\Components\Concerns\EvalueLeScriptAuSelect;

/**
 * Verifie que <x-au-select> affiche une valeur posee par `x-model` avant
 * toute interaction.
 *
 * Ce qu'il protege : Alpine ecrit la valeur du parent dans le <select> cache
 * APRES l'init du composant, et sans evenement. Le composant lisait la valeur
 * une seule fois, a l'init : l'etat du parent etait bon, l'ecran affichait
 * encore l'invite. Constate sur le pre-controle des bulletins, ouvert par un
 * lien portant la classe et la periode dans l'adresse : trois selecteurs
 * « Choisir… » au-dessus d'un pre-controle qui, lui, tournait bien sur la
 * classe demandee.
 *
 * Le test execute le script REELLEMENT livre par le composant.
 */
class AuSelectValeurInitialeTest extends TestCase
{
    use EvalueLeScriptAuSelect;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->cheminNode() === null) {
            $this->markTestSkipped('Node introuvable : impossible d\'executer le script du composant.');
        }
    }

    public function test_une_valeur_posee_par_x_model_apres_l_init_est_affichee(): void
    {
        $this->assertSame('98', $this->valeurAfficheeApres(''), 'La valeur posee par x-model doit etre reprise.');
    }

    public function test_une_valeur_deja_presente_a_l_init_reste_telle_quelle(): void
    {
        $this->assertSame('97', $this->valeurAfficheeApres('97', posee: null));
    }

    private function valeurAfficheeApres(string $valeurALInit, ?string $posee = '98'): string
    {
        $posee = $posee === null ? 'null' : json_encode($posee);

        return $this->evalueAvecLeComposant(<<<JS
            globalThis.addEventListener = () => {};
            const composant = window.auSelect();
            const aTick = [];
            const natif = { value: '{$valeurALInit}', options: [], addEventListener() {}, dispatchEvent() {} };
            Object.assign(composant, {
                \$refs: { native: natif, menu: {} },
                \$el: { querySelector() { return null; } },
                \$nextTick: (fn) => aTick.push(fn),
                \$watch() {},
            });
            composant.init();
            // Ce que fait x-model, apres l'init et sans evenement.
            if ({$posee} !== null) { natif.value = {$posee}; }
            aTick.forEach((fn) => fn());
            console.log(composant._value);
        JS);
    }
}
