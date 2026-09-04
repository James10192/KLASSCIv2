<?php

namespace Tests\Unit\Dossiers;

use App\Exports\SuiviPiecesExport;
use App\Models\ESBTPPieceDossier;
use App\Services\Dossiers\SuiviPiecesService;
use PHPUnit\Framework\TestCase;

/**
 * Le coeur du suivi est pur : resolution du catalogue et agregation se testent
 * sans base de donnees.
 */
class SuiviPiecesServiceTest extends TestCase
{
    private function piece(array $attributs): ESBTPPieceDossier
    {
        return new ESBTPPieceDossier(array_merge([
            'code' => 'extrait_naissance',
            'libelle' => 'Extrait de naissance',
            'filiere_id' => null,
            'niveau_id' => null,
            'est_obligatoire' => true,
            'nombre_exemplaires' => 1,
            'ordre' => 0,
            'is_active' => true,
        ], $attributs));
    }

    public function test_le_defaut_etablissement_s_applique_a_tout_le_monde(): void
    {
        $attendues = SuiviPiecesService::piecesAttendues([$this->piece([])], 7, 3);

        $this->assertArrayHasKey('extrait_naissance', $attendues);
    }

    public function test_une_piece_de_filiere_ne_fuit_pas_sur_une_autre_filiere(): void
    {
        $catalogue = [$this->piece(['code' => 'attestation_transfert', 'filiere_id' => 7])];

        $this->assertArrayHasKey('attestation_transfert', SuiviPiecesService::piecesAttendues($catalogue, 7, 1));
        $this->assertSame([], SuiviPiecesService::piecesAttendues($catalogue, 9, 1));
    }

    public function test_la_definition_la_plus_specifique_l_emporte(): void
    {
        $catalogue = [
            $this->piece(['nombre_exemplaires' => 1]),                                   // defaut ecole
            $this->piece(['filiere_id' => 7, 'nombre_exemplaires' => 2]),                // filiere
            $this->piece(['filiere_id' => 7, 'niveau_id' => 3, 'nombre_exemplaires' => 3]), // filiere + niveau
        ];

        $attendues = SuiviPiecesService::piecesAttendues($catalogue, 7, 3);

        $this->assertSame(3, $attendues['extrait_naissance']->nombre_exemplaires);
    }

    public function test_une_ligne_inactive_de_scope_exempte_la_filiere(): void
    {
        // L'ecole exige la piece partout, sauf dans cette filiere : elle cree une
        // ligne de scope inactive plutot que de modifier le defaut commun.
        $catalogue = [
            $this->piece([]),
            $this->piece(['filiere_id' => 7, 'is_active' => false]),
        ];

        $this->assertSame([], SuiviPiecesService::piecesAttendues($catalogue, 7, 1));
        $this->assertArrayHasKey('extrait_naissance', SuiviPiecesService::piecesAttendues($catalogue, 9, 1));
    }

    public function test_les_pieces_attendues_suivent_l_ordre_configure(): void
    {
        $catalogue = [
            $this->piece(['code' => 'photo', 'libelle' => 'Photo', 'ordre' => 5]),
            $this->piece(['code' => 'extrait_naissance', 'ordre' => 1]),
        ];

        $this->assertSame(
            ['extrait_naissance', 'photo'],
            array_keys(SuiviPiecesService::piecesAttendues($catalogue, null, null))
        );
    }

    public function test_l_agregat_compte_les_etudiants_et_les_exemplaires(): void
    {
        $lignes = [
            [
                'manquantes' => [
                    ['code' => 'extrait_naissance', 'libelle' => 'Extrait de naissance', 'obligatoire' => true, 'exemplaires_manquants' => 2],
                    ['code' => 'photo', 'libelle' => 'Photo', 'obligatoire' => false, 'exemplaires_manquants' => 1],
                ],
            ],
            [
                'manquantes' => [
                    ['code' => 'extrait_naissance', 'libelle' => 'Extrait de naissance', 'obligatoire' => true, 'exemplaires_manquants' => 1],
                ],
            ],
        ];

        $agrege = SuiviPiecesService::agregerParPiece($lignes);

        $this->assertCount(2, $agrege);
        // Les obligatoires remontent en tete : c'est ce qui bloque la remise au ministere.
        $this->assertSame('extrait_naissance', $agrege[0]['code']);
        $this->assertSame(2, $agrege[0]['etudiants']);
        $this->assertSame(3, $agrege[0]['exemplaires']);
        $this->assertSame(1, $agrege[1]['etudiants']);
    }

    public function test_l_agregat_est_vide_quand_rien_ne_manque(): void
    {
        $this->assertSame([], SuiviPiecesService::agregerParPiece([]));
    }

    public function test_l_export_deplie_une_ligne_par_piece(): void
    {
        $plat = SuiviPiecesExport::aplatir([
            [
                'matricule' => 'M1',
                'etudiant' => 'KOUAME Yao',
                'classe' => 'BTS1 A',
                'filiere' => 'Genie civil',
                'niveau' => 'BTS 1',
                'annee' => '2026-2027',
                'telephone' => null,
                'email' => null,
                'manquantes' => [
                    ['libelle' => 'Extrait de naissance', 'obligatoire' => true, 'exemplaires_manquants' => 2],
                    ['libelle' => 'Photo', 'obligatoire' => false, 'exemplaires_manquants' => 1],
                ],
            ],
        ]);

        $this->assertCount(2, $plat);
        $this->assertSame('KOUAME Yao', $plat[0]['etudiant']);
        $this->assertSame('Extrait de naissance', $plat[0]['piece']);
        $this->assertTrue($plat[0]['obligatoire']);
        $this->assertFalse($plat[1]['obligatoire']);
    }
}
