<?php

namespace Tests\Feature\Documents;

use App\Services\Documents\StockageDocumentEtudiant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Les pieces du dossier d'un etudiant ne doivent pas etre servies par le serveur
 * web.
 *
 * Elles etaient ecrites sur le disque `public`, c'est-a-dire sous
 * `storage/app/public`, que le lien symbolique `public/storage` expose. Le
 * serveur les servait donc DIRECTEMENT, sans passer par Laravel : ni
 * authentification, ni permission, ni trace. Les routes de telechargement
 * etaient pourtant gardees — le trou etait a cote d'elles.
 *
 * Ces cas tiennent trois promesses : on ecrit prive, on lit encore ce qui dort
 * sur le disque expose, et on efface des deux cotes.
 */
class PiecesEtudiantHorsDuServeurWebTest extends TestCase
{
    use DatabaseTransactions;

    private StockageDocumentEtudiant $stockage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockage = app(StockageDocumentEtudiant::class);
    }

    public function test_un_depot_n_atterrit_jamais_sur_le_disque_servi_par_le_serveur_web(): void
    {
        Storage::fake(StockageDocumentEtudiant::DISQUE);
        Storage::fake(StockageDocumentEtudiant::DISQUE_HERITE);

        $chemin = $this->stockage->enregistrer(
            UploadedFile::fake()->create('extrait-de-naissance.pdf', 12),
            42
        );

        Storage::disk(StockageDocumentEtudiant::DISQUE)->assertExists($chemin);
        Storage::disk(StockageDocumentEtudiant::DISQUE_HERITE)->assertMissing($chemin);
    }

    public function test_le_chemin_ne_se_devine_pas_a_partir_de_l_eleve_et_du_nom_du_document(): void
    {
        // L'ancien nom se reconstituait : identifiant sequentiel, horodatage dans
        // la journee, et le nom du fichier d'origine — « extrait-de-naissance ».
        Storage::fake(StockageDocumentEtudiant::DISQUE);

        $premier = $this->stockage->enregistrer(
            UploadedFile::fake()->create('extrait-de-naissance.pdf', 12),
            42
        );
        $second = $this->stockage->enregistrer(
            UploadedFile::fake()->create('extrait-de-naissance.pdf', 12),
            42
        );

        $this->assertNotSame($premier, $second, 'Deux depots identiques doivent donner deux chemins.');
    }

    public function test_une_piece_deposee_avant_la_mise_a_l_abri_reste_lisible(): void
    {
        // La reprise ne passe pas partout le meme jour. Tant qu'elle n'est pas
        // passee, ces fichiers doivent continuer de s'ouvrir : les rendre
        // introuvables casserait les dossiers en cours.
        Storage::fake(StockageDocumentEtudiant::DISQUE);
        Storage::fake(StockageDocumentEtudiant::DISQUE_HERITE);

        $herite = 'etudiants/42/documents/etudiant_42_1725462000_extrait.pdf';
        Storage::disk(StockageDocumentEtudiant::DISQUE_HERITE)->put($herite, 'contenu');

        $this->assertTrue($this->stockage->existe($herite));
        $this->assertSame(StockageDocumentEtudiant::DISQUE_HERITE, $this->stockage->disqueDe($herite));
        $this->assertTrue($this->stockage->estEncoreExpose($herite));
    }

    public function test_supprimer_efface_aussi_ce_qui_dort_sur_le_disque_expose(): void
    {
        // Ne l'effacer que sur le disque prive laisserait en place precisement le
        // fichier qu'on cherche a retirer.
        Storage::fake(StockageDocumentEtudiant::DISQUE);
        Storage::fake(StockageDocumentEtudiant::DISQUE_HERITE);

        $herite = 'etudiants/42/documents/etudiant_42_1725462000_extrait.pdf';
        Storage::disk(StockageDocumentEtudiant::DISQUE_HERITE)->put($herite, 'contenu');

        $this->stockage->supprimer($herite);

        Storage::disk(StockageDocumentEtudiant::DISQUE_HERITE)->assertMissing($herite);
    }

    public function test_un_chemin_vide_ou_remontant_ne_designe_rien(): void
    {
        // Un chemin degrade en chaine vide designerait le repertoire racine, qui
        // existe : le fichier passerait pour present. Et `..` doit rester sans
        // effet, sinon la suppression sortirait du dossier.
        Storage::fake(StockageDocumentEtudiant::DISQUE);

        $this->assertNull($this->stockage->disqueDe(''));
        $this->assertNull($this->stockage->disqueDe(null));
        $this->assertNull($this->stockage->disqueDe('   '));
        $this->assertNull($this->stockage->disqueDe('../../.env'));
        $this->assertFalse($this->stockage->existe(''));
    }
}
