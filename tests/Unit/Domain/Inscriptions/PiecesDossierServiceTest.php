<?php

namespace Tests\Unit\Domain\Inscriptions;

use App\Domain\Inscriptions\Pieces\PiecesDossierService;
use App\Models\ESBTPInscriptionPiece;
use App\Models\ESBTPPieceDossier;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Les deux fonctions pures du panneau : quelle piece est attendue, et ce que
 * dit le compteur. Aucune base de donnees, pour rester executable partout.
 */
class PiecesDossierServiceTest extends TestCase
{
    private function ligne(array $attributs): ESBTPPieceDossier
    {
        return new ESBTPPieceDossier(array_merge([
            'code' => 'extrait_naissance',
            'libelle' => 'Extrait de naissance',
            'est_obligatoire' => true,
            'filiere_id' => null,
            'niveau_id' => null,
            'ordre' => 0,
            'is_active' => true,
        ], $attributs));
    }

    private function recue(string $code, bool $fournie): ESBTPInscriptionPiece
    {
        // setRawAttributes plutot que fill : ecrire une date castee demande le
        // format de la connexion, or ce test tourne sans base. La lecture, elle,
        // n'en a pas besoin.
        $piece = new ESBTPInscriptionPiece();
        $piece->setRawAttributes([
            'piece_code' => $code,
            'est_fournie' => $fournie,
            'fournie_le' => $fournie ? '2026-09-01' : null,
        ]);
        // La relation est chargee en production ; on la pose vide ici pour
        // qu'aucune lecture n'aille chercher une base de donnees.
        $piece->setRelation('marqueePar', null);

        return $piece;
    }

    public function test_la_declinaison_de_filiere_prime_sur_le_defaut_ecole(): void
    {
        $lignes = new Collection([
            $this->ligne(['libelle' => 'Extrait (défaut école)']),
            $this->ligne(['libelle' => 'Extrait (filière 7)', 'filiere_id' => 7]),
        ]);

        $retenues = PiecesDossierService::retenirLesPlusPrecises($lignes, 7, 3);

        $this->assertCount(1, $retenues);
        $this->assertSame('Extrait (filière 7)', $retenues->first()->libelle);
    }

    public function test_la_declinaison_d_une_autre_filiere_est_ignoree(): void
    {
        $lignes = new Collection([
            $this->ligne(['libelle' => 'Extrait (défaut école)']),
            $this->ligne(['libelle' => 'Extrait (filière 9)', 'filiere_id' => 9]),
        ]);

        $retenues = PiecesDossierService::retenirLesPlusPrecises($lignes, 7, 3);

        $this->assertCount(1, $retenues);
        $this->assertSame('Extrait (défaut école)', $retenues->first()->libelle);
    }

    public function test_une_ligne_desactivee_ne_sort_pas_du_catalogue(): void
    {
        $lignes = new Collection([
            $this->ligne(['is_active' => false]),
        ]);

        $this->assertCount(0, PiecesDossierService::retenirLesPlusPrecises($lignes, 7, 3));
    }

    public function test_les_obligatoires_sont_listees_avant_les_facultatives(): void
    {
        $lignes = new Collection([
            $this->ligne(['code' => 'photo', 'libelle' => 'Photo', 'est_obligatoire' => false, 'ordre' => 0]),
            $this->ligne(['code' => 'bac', 'libelle' => 'Diplôme du bac', 'est_obligatoire' => true, 'ordre' => 5]),
        ]);

        $retenues = PiecesDossierService::retenirLesPlusPrecises($lignes, null, null);

        $this->assertSame(['bac', 'photo'], $retenues->pluck('code')->all());
    }

    public function test_le_compteur_porte_par_defaut_sur_toutes_les_pieces(): void
    {
        $catalogue = new Collection([
            $this->ligne(['code' => 'extrait', 'libelle' => 'Extrait']),
            $this->ligne(['code' => 'bac', 'libelle' => 'Bac']),
            $this->ligne(['code' => 'photo', 'libelle' => 'Photo', 'est_obligatoire' => false]),
        ]);
        $recues = (new Collection([$this->recue('extrait', true)]))->keyBy('piece_code');

        $etat = PiecesDossierService::composerEtat($catalogue, $recues);

        $this->assertTrue($etat['actif']);
        $this->assertSame(1, $etat['compteur']['fournies']);
        $this->assertSame(3, $etat['compteur']['total']);
        $this->assertSame(1, $etat['obligatoires_manquantes']);
        $this->assertTrue($etat['signal']);
    }

    public function test_le_compteur_peut_se_limiter_aux_obligatoires(): void
    {
        $catalogue = new Collection([
            $this->ligne(['code' => 'extrait', 'libelle' => 'Extrait']),
            $this->ligne(['code' => 'photo', 'libelle' => 'Photo', 'est_obligatoire' => false]),
        ]);
        $recues = (new Collection([$this->recue('extrait', true)]))->keyBy('piece_code');

        $etat = PiecesDossierService::composerEtat(
            $catalogue,
            $recues,
            PiecesDossierService::BASE_OBLIGATOIRES
        );

        $this->assertSame(1, $etat['compteur']['fournies']);
        $this->assertSame(1, $etat['compteur']['total']);
        $this->assertSame(0, $etat['obligatoires_manquantes']);
        $this->assertFalse($etat['signal']);
    }

    public function test_une_piece_facultative_manquante_ne_declenche_pas_le_signal(): void
    {
        $catalogue = new Collection([
            $this->ligne(['code' => 'extrait', 'libelle' => 'Extrait']),
            $this->ligne(['code' => 'photo', 'libelle' => 'Photo', 'est_obligatoire' => false]),
        ]);
        $recues = (new Collection([$this->recue('extrait', true)]))->keyBy('piece_code');

        $etat = PiecesDossierService::composerEtat($catalogue, $recues);

        $this->assertSame(0, $etat['obligatoires_manquantes']);
        $this->assertFalse($etat['signal']);
    }

    public function test_le_signal_peut_etre_eteint_par_reglage(): void
    {
        $catalogue = new Collection([$this->ligne(['code' => 'extrait', 'libelle' => 'Extrait'])]);

        $etat = PiecesDossierService::composerEtat(
            $catalogue,
            new Collection(),
            PiecesDossierService::BASE_TOUTES,
            false
        );

        $this->assertSame(1, $etat['obligatoires_manquantes']);
        $this->assertFalse($etat['signal']);
    }

    public function test_un_catalogue_vide_rend_le_panneau_inactif(): void
    {
        $etat = PiecesDossierService::composerEtat(new Collection(), new Collection());

        $this->assertFalse($etat['actif']);
        $this->assertFalse($etat['signal']);
        $this->assertSame(0, $etat['compteur']['total']);
    }

    public function test_une_piece_non_fournie_n_affiche_aucune_trace_de_depot(): void
    {
        $catalogue = new Collection([$this->ligne(['code' => 'extrait', 'libelle' => 'Extrait'])]);
        $recues = (new Collection([$this->recue('extrait', false)]))->keyBy('piece_code');

        $etat = PiecesDossierService::composerEtat($catalogue, $recues);

        $this->assertFalse($etat['pieces'][0]['fournie']);
        $this->assertNull($etat['pieces'][0]['fournie_le']);
    }
}
