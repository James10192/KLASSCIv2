<?php

namespace Tests\Unit\Enums;

use App\Enums\EcheanceDocumentRequis;
use App\Enums\FormeDocumentRequis;
use PHPUnit\Framework\TestCase;

class DocumentRequisEnumsTest extends TestCase
{
    public function test_la_forme_expose_ses_trois_cas(): void
    {
        $valeurs = FormeDocumentRequis::values();

        $this->assertCount(3, $valeurs);
        $this->assertContains('original', $valeurs);
        $this->assertContains('copie', $valeurs);
        $this->assertContains('indifferent', $valeurs);
    }

    public function test_chaque_forme_a_un_libelle_non_vide(): void
    {
        foreach (FormeDocumentRequis::cases() as $forme) {
            $this->assertNotSame('', trim($forme->label()));
        }
    }

    /**
     * Une donnee sale ne doit pas faire tomber le rendu d'une liste : on retombe
     * sur la forme la moins contraignante.
     */
    public function test_la_forme_retombe_sur_la_copie_quand_la_valeur_est_inconnue(): void
    {
        $this->assertSame(FormeDocumentRequis::COPIE, FormeDocumentRequis::fromLibre(null));
        $this->assertSame(FormeDocumentRequis::COPIE, FormeDocumentRequis::fromLibre(''));
        $this->assertSame(FormeDocumentRequis::COPIE, FormeDocumentRequis::fromLibre('n_importe_quoi'));
        $this->assertSame(FormeDocumentRequis::ORIGINAL, FormeDocumentRequis::fromLibre('  ORIGINAL '));
    }

    public function test_les_options_de_select_sont_indexees_par_valeur(): void
    {
        $options = FormeDocumentRequis::selectOptions();

        $this->assertArrayHasKey('original', $options);
        $this->assertSame('Original', $options['original']);
    }

    public function test_l_echeance_distingue_l_inscription_et_la_fin_d_annee(): void
    {
        $valeurs = EcheanceDocumentRequis::values();

        $this->assertCount(2, $valeurs);
        $this->assertContains('inscription', $valeurs);
        $this->assertContains('avant_fin_annee', $valeurs);
    }

    public function test_l_echeance_retombe_sur_l_inscription_quand_la_valeur_est_inconnue(): void
    {
        $this->assertSame(EcheanceDocumentRequis::INSCRIPTION, EcheanceDocumentRequis::fromLibre(null));
        $this->assertSame(EcheanceDocumentRequis::INSCRIPTION, EcheanceDocumentRequis::fromLibre('plus tard'));
        $this->assertSame(
            EcheanceDocumentRequis::AVANT_FIN_ANNEE,
            EcheanceDocumentRequis::fromLibre('avant_fin_annee')
        );
    }

    public function test_chaque_echeance_a_un_libelle_court_distinct(): void
    {
        $courts = array_map(
            static fn (EcheanceDocumentRequis $e) => $e->labelCourt(),
            EcheanceDocumentRequis::cases()
        );

        $this->assertSame($courts, array_unique($courts));
    }
}
