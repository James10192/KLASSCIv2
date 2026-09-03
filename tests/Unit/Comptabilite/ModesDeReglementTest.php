<?php

namespace Tests\Unit\Comptabilite;

use App\Http\Controllers\Comptabilite\ModeReglementController;
use PHPUnit\Framework\TestCase;

/**
 * Les modes proposes a la correction doivent s'ecrire comme la saisie les
 * enregistre.
 *
 * La colonne `mode_paiement` stocke un LIBELLE, pas un code : l'ecran
 * d'encaissement y pose « Especes », « Cheque », « Mobile Money ». Le journal
 * de caisse, lui, regroupe par ce champ.
 *
 * Corriger un versement vers « especes » en minuscules ne le deplacerait donc
 * pas dans la colonne Especes : cela fonderait une TROISIEME colonne, avec une
 * seule ligne dedans, que personne ne rapprocherait ensuite. Le journal
 * cesserait d'etre juste sans afficher la moindre erreur.
 *
 * Ce test fige la liste. Il echoue si quelqu'un ajoute un mode dans une autre
 * casse, ou renomme un libelle d'un cote sans l'autre.
 */
class ModesDeReglementTest extends TestCase
{
    /**
     * Recopie de `$allModeOptions` dans esbtp/paiements/create.blade.php.
     *
     * Un tableau inline dans une vue Blade ne s'importe pas ; le figer ici est
     * ce qui permet au moins de faire crier la divergence, faute de pouvoir
     * l'empecher.
     */
    private const LIBELLES_DE_LA_SAISIE = [
        'Espèces',
        'Chèque',
        'Virement',
        'Mobile Money',
        'Orange Money',
        'MTN Money',
        'Moov Money',
        'Wave',
        'Carte bancaire',
    ];

    public function test_les_modes_correspondent_a_ceux_de_la_saisie(): void
    {
        $this->assertSame(
            self::LIBELLES_DE_LA_SAISIE,
            array_keys(ModeReglementController::MODES),
            'La correction propose des modes que la saisie n\'enregistre pas, ou en oublie.'
        );
    }

    /**
     * La cle EST la valeur ecrite en base : si les deux divergent, la
     * correction ecrit autre chose que ce que l'ecran annonce.
     */
    public function test_chaque_cle_est_sa_propre_valeur(): void
    {
        foreach (ModeReglementController::MODES as $cle => $valeur) {
            $this->assertSame($cle, $valeur, "Le mode « {$cle} » n'ecrit pas ce qu'il affiche.");
        }
    }

    /**
     * Deux modes qui ne different que par la casse ou les accents feraient deux
     * colonnes distinctes dans le journal, pour une meme realite.
     */
    public function test_aucun_doublon_de_casse(): void
    {
        $normalises = array_map(
            fn (string $m) => mb_strtolower($m, 'UTF-8'),
            array_keys(ModeReglementController::MODES)
        );

        $this->assertSame(
            count($normalises),
            count(array_unique($normalises)),
            'Deux modes se confondent une fois la casse ignoree.'
        );
    }
}
