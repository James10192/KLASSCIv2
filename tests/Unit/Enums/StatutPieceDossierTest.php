<?php

namespace Tests\Unit\Enums;

use App\Enums\StatutPieceDossier;
use PHPUnit\Framework\TestCase;

class StatutPieceDossierTest extends TestCase
{
    public function test_expose_les_trois_etats_du_dossier(): void
    {
        $valeurs = StatutPieceDossier::values();

        $this->assertCount(3, $valeurs);
        $this->assertContains('manquante', $valeurs);
        $this->assertContains('fournie', $valeurs);
        $this->assertContains('non_applicable', $valeurs);
    }

    public function test_le_defaut_est_manquante(): void
    {
        // La matérialisation crée les lignes à « manquante » : au moment où le
        // dossier s'ouvre, rien n'a encore été remis.
        $this->assertSame(StatutPieceDossier::MANQUANTE, StatutPieceDossier::defaut());
    }

    public function test_seule_manquante_alimente_le_compteur(): void
    {
        $this->assertTrue(StatutPieceDossier::MANQUANTE->estDue());
        $this->assertFalse(StatutPieceDossier::FOURNIE->estDue());
        $this->assertFalse(StatutPieceDossier::NON_APPLICABLE->estDue());
    }

    public function test_chaque_etat_a_un_libelle_non_vide(): void
    {
        foreach (StatutPieceDossier::cases() as $case) {
            $this->assertNotSame('', trim($case->label()));
        }
    }

    public function test_les_options_de_select_couvrent_tous_les_etats(): void
    {
        $options = StatutPieceDossier::selectOptions();

        $this->assertCount(3, $options);
        $this->assertSame(StatutPieceDossier::values(), array_keys($options));
    }

    /**
     * Le point sensible : une valeur inconnue ne doit jamais être interprétée
     * comme « fournie ». En cas de doute on réclame la pièce, on ne la déclare
     * pas reçue — sinon un dossier incomplet passe pour complet devant le
     * ministère.
     */
    public function test_une_valeur_inconnue_retombe_sur_manquante_jamais_sur_fournie(): void
    {
        foreach ([null, '', 'inconnu', 'FOURNI', 'reçue'] as $brut) {
            $this->assertSame(
                StatutPieceDossier::MANQUANTE,
                StatutPieceDossier::depuis($brut),
                'La valeur '.var_export($brut, true).' ne doit pas être lue comme fournie',
            );
        }
    }

    public function test_les_valeurs_canoniques_sont_reconnues_meme_avec_casse_ou_espaces(): void
    {
        $this->assertSame(StatutPieceDossier::FOURNIE, StatutPieceDossier::depuis('fournie'));
        $this->assertSame(StatutPieceDossier::FOURNIE, StatutPieceDossier::depuis('  Fournie '));
        $this->assertSame(StatutPieceDossier::NON_APPLICABLE, StatutPieceDossier::depuis('NON_APPLICABLE'));
    }
}
