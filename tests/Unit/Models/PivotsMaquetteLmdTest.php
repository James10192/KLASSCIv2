<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use Tests\TestCase;

/**
 * Garde le decoupage « elements d une unite, par maquette » lisible.
 *
 * Ce test existe parce que la panne qu il previent est MUETTE : si une colonne
 * de pivot n est pas declaree dans `withPivot()`, Eloquent ne la selectionne pas
 * et `$modele->pivot->la_colonne` rend `null` SANS LEVER D ERREUR. Un element
 * reserve a une maquette se lirait alors comme commun, et fuiterait dans toutes
 * les autres — sans une ligne de log, sans un ecran casse, sur des instances de
 * plus de 2000 inscrits.
 *
 * C est donc un test d ABSENCE : il ne verifie pas qu une valeur est correcte,
 * il verifie que la colonne est seulement demandee.
 *
 * AUCUNE base n est touchee : construire une relation belongsToMany n emet pas
 * de requete, la liste des colonnes de pivot est connue des la declaration. Le
 * test etend tout de meme Tests\TestCase et non PHPUnit\Framework\TestCase :
 * ces trois modeles sont Auditable, et amorcer ce trait appelle
 * `App::runningInConsole()`, donc exige une application. Sans elle, le test
 * echoue sur « A facade root has not been set » avant sa premiere assertion.
 */
class PivotsMaquetteLmdTest extends TestCase
{
    public function test_le_pivot_element_unite_expose_la_maquette_des_deux_cotes(): void
    {
        $depuisUnite = (new ESBTPUniteEnseignement())->ecues()->getPivotColumns();
        $depuisElement = (new ESBTPMatiere())->unitesEnseignementMultiple()->getPivotColumns();

        $this->assertContains('parcours_id', $depuisUnite, 'Sans parcours_id au SELECT, tout element reserve se lit comme commun.');
        $this->assertContains('parcours_id', $depuisElement, 'Sans parcours_id au SELECT, tout element reserve se lit comme commun.');
    }

    public function test_le_pivot_element_unite_conserve_ses_colonnes_de_bulletin(): void
    {
        // Le coefficient et le credit du pivot priment sur ceux de la matiere au
        // calcul de la moyenne d une unite. Les perdre en ajoutant parcours_id
        // changerait des moyennes deja delivrees.
        $colonnes = (new ESBTPUniteEnseignement())->ecues()->getPivotColumns();

        $this->assertContains('coefficient_ecue', $colonnes);
        $this->assertContains('credit_ecue', $colonnes);
        $this->assertContains('ordre_bulletin', $colonnes);
    }

    public function test_le_pivot_maquette_unite_expose_le_credit_propre_a_la_maquette(): void
    {
        $depuisUnite = (new ESBTPUniteEnseignement())->parcoursMultiple()->getPivotColumns();
        $depuisParcours = (new ESBTPLMDParcours())->unitesEnseignement()->getPivotColumns();

        $this->assertContains('credit', $depuisUnite);
        $this->assertContains('credit', $depuisParcours);

        // Le semestre fait partie de la cle : c est lui qui donne au credit sa
        // portee, (maquette, unite, semestre). S il disparaissait du SELECT, deux
        // lignes d une meme unite sur deux semestres deviendraient indiscernables.
        $this->assertContains('semestre', $depuisUnite);
        $this->assertContains('semestre', $depuisParcours);
    }
}
