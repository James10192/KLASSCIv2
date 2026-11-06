<?php

namespace Tests\Unit\Comptabilite;

use App\Domain\Comptabilite\SuiviClasses\SituationClasseMontants;
use App\Models\ESBTPInscription;
use PHPUnit\Framework\TestCase;

final class SituationClasseMontantsTest extends TestCase
{
    private function echeances(): array
    {
        return [
            'total_paid' => 99999, // Le paiement brut de l'ancien moteur n'est pas retenu.
            'remaining_total' => 1,
            'due_lines' => [
                ['category_id' => 17, 'amount' => 90000, 'paid_amount' => 90000,
                    'remaining_amount' => 0, 'due_date' => '2000-02-01', 'grace_days' => 0],
                ['category_id' => 17, 'amount' => 10000, 'paid_amount' => 10000,
                    'remaining_amount' => 0, 'due_date' => '2099-01-01', 'grace_days' => 0],
            ],
        ];
    }

    public function test_les_avoirs_et_reliquats_ne_fausseront_pas_le_net_canonique(): void
    {
        $inscription = new ESBTPInscription();
        $inscription->setRelation('paiements', collect());
        // Le montant 70000 est celui du moteur net par frais (après avoir,
        // hors paiement de reliquat). Il prime sur total_paid = 99999.
        $resultat = (new SituationClasseMontants())->calculer(
            $this->echeances(), $inscription, 0, [17 => 70000], 70000
        );

        $this->assertEquals(100000, $resultat['attendu']);
        $this->assertEquals(70000, $resultat['regle']);
        $this->assertEquals(30000, $resultat['restant']);
        $this->assertEquals(20000, $resultat['echu']);
        $this->assertEquals(-70000, $resultat['ajustements']);
    }

    public function test_un_paiement_non_ventile_couvre_le_reste_sans_fausser_un_filtre_de_frais(): void
    {
        $inscription = new ESBTPInscription();
        $inscription->setRelation('paiements', collect());
        $calc = new SituationClasseMontants();

        $global = $calc->calculer($this->echeances(), $inscription, 0, [17 => 70000], 90000);
        $categorie = $calc->calculer($this->echeances(), $inscription, 17, [17 => 70000], 90000);

        $this->assertEquals(90000, $global['regle']);
        $this->assertEquals(10000, $global['restant']);
        $this->assertEquals(70000, $categorie['regle']);
        $this->assertEquals(30000, $categorie['restant']);
        $this->assertEquals(0, $categorie['encaisse']);
    }
}
