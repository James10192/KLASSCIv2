<?php

namespace Tests\Unit\Inscriptions;

use App\Services\Inscriptions\NormalisationTypeInscription;
use PHPUnit\Framework\TestCase;

/** Le type d'inscription s'affiche en français lisible, jamais avec sa valeur d'enum. */
class LibelleTypeInscriptionTest extends TestCase
{
    public function test_les_valeurs_de_l_enum_ont_leur_libelle(): void
    {
        $this->assertSame('Première inscription', NormalisationTypeInscription::libelle(NormalisationTypeInscription::PREMIERE));
        $this->assertSame('Réinscription', NormalisationTypeInscription::libelle(NormalisationTypeInscription::REINSCRIPTION));
        $this->assertSame('Réinscription', NormalisationTypeInscription::libelle('reinscription'));
        $this->assertSame('Transfert', NormalisationTypeInscription::libelle('transfert'));
    }

    public function test_une_valeur_inconnue_reste_lisible_et_rien_n_est_devine(): void
    {
        $this->assertSame('Non renseigné', NormalisationTypeInscription::libelle(null));
        $this->assertSame('Non renseigné', NormalisationTypeInscription::libelle(''));
        $this->assertSame('École partenaire', NormalisationTypeInscription::libelle('école_partenaire'));
    }
}
