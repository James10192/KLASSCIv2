<?php

namespace Tests\Unit\Domain\Academique;

use App\Domain\Academique\CoherenceSystemeAcademique;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Le memo de « je n'ai pas pu verifier » dedouble-t-il VRAIMENT ?
 *
 * Il ne le faisait pas. La cle etait construite sur le contexte entier, or
 * quatre appelants y passent un identifiant de LIGNE (`resultat_id`,
 * `note_id`) : chaque tour de boucle fabriquait sa propre cle. Une colonne
 * orpheline sur une classe de soixante rendait donc soixante avertissements
 * par generation — exactement le bruit que ce memo existe pour eviter — et la
 * table grossissait en O(lignes lues), sans purge, dans un worker de file.
 *
 * Le defaut ne se voyait pas : les deux methodes partagent le meme tableau, et
 * `matiereRetenue()`, elle, se bornait correctement. Aucun test ne separait
 * les deux.
 *
 * LE CONTROLE A REJOUER avant de toucher a la cle : remettre
 * `implode(',', $contexte)` a la place de la boucle sur `$portee`. Le premier
 * test ci-dessous doit virer au rouge — les deux autres, non.
 */
class CoherenceNonVerifiableMemoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CoherenceSystemeAcademique::oublierLesEcartsJournalises();
    }

    protected function tearDown(): void
    {
        CoherenceSystemeAcademique::oublierLesEcartsJournalises();
        parent::tearDown();
    }

    public function test_un_identifiant_de_ligne_ne_fabrique_plus_une_cle_par_tour(): void
    {
        Log::shouldReceive('warning')->once();

        foreach ([11, 12, 13] as $resultatId) {
            CoherenceSystemeAcademique::coherenceNonVerifiable('stats/ligne sans classe', [
                'resultat_id' => $resultatId,   // change a chaque tour
                'classe_id' => 7,               // la portee, elle, ne bouge pas
            ], portee: ['classe_id']);
        }
    }

    public function test_deux_portees_distinctes_se_disent_chacune_une_fois(): void
    {
        Log::shouldReceive('warning')->twice();

        CoherenceSystemeAcademique::coherenceNonVerifiable('stats/ligne sans classe', [
            'resultat_id' => 11,
            'classe_id' => 7,
        ], portee: ['classe_id']);

        CoherenceSystemeAcademique::coherenceNonVerifiable('stats/ligne sans classe', [
            'resultat_id' => 12,
            'classe_id' => 8,
        ], portee: ['classe_id']);
    }

    public function test_l_identifiant_de_ligne_part_quand_meme_au_journal(): void
    {
        // La portee decide du dedoublonnage, pas de ce qu'on publie : sans le
        // `resultat_id`, l'operateur ne retrouve pas l'enregistrement fautif.
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $contexte) {
                return $contexte['resultat_id'] === 11
                    && $contexte['classe_id'] === 7
                    && $contexte['provenance'] === 'stats/ligne sans classe';
            });

        CoherenceSystemeAcademique::coherenceNonVerifiable('stats/ligne sans classe', [
            'resultat_id' => 11,
            'classe_id' => 7,
        ], portee: ['classe_id']);
    }

    public function test_une_cle_de_portee_absente_du_contexte_ne_casse_pas_l_impression(): void
    {
        // Une faute de frappe ne doit ni lever, ni FAIRE TAIRE. La premiere
        // version de ce correctif remplacait la cle manquante par une chaine
        // vide : les deux appels ci-dessous, pourtant sur deux classes
        // differentes, obtenaient la meme cle et un seul avertissement
        // sortait. On perdait du signal sans que rien ne le dise. La portee
        // incalculable retombe donc sur l'absence de dedoublonnage.
        Log::shouldReceive('warning')->twice();

        CoherenceSystemeAcademique::coherenceNonVerifiable('stats/ligne sans classe', [
            'classe_id' => 7,
        ], portee: ['clase_id']);   // coquille volontaire

        CoherenceSystemeAcademique::coherenceNonVerifiable('stats/ligne sans classe', [
            'classe_id' => 8,
        ], portee: ['clase_id']);
    }
}
