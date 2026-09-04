<?php

namespace Tests\Unit\Photos;

use App\Services\Photos\StockagePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Une photo s'ecrit d'une seule facon et se lit sous toutes ses formes.
 *
 * Ce test existe parce que trois ecrans ecrivaient la meme colonne de trois
 * facons — nom de fichier nu, URL, chemin relatif — et que deux lecteurs les
 * interpretaient differemment. Une photo posee depuis l'ecran d'edition
 * ressortait re-prefixee, donc cassee, sur les tableaux de bord, les bulletins
 * et la messagerie.
 */
class StockagePhotoTest extends TestCase
{
    private StockagePhoto $stockage;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->stockage = new StockagePhoto();
    }

    public function test_une_photo_se_range_toujours_au_meme_endroit(): void
    {
        $chemin = $this->stockage->enregistrer(UploadedFile::fake()->image('portrait.jpg'), 'etudiant');

        $this->assertStringStartsWith(StockagePhoto::DOSSIER.'/', $chemin);
        $this->assertStringEndsWith('.jpg', $chemin);
        Storage::disk('public')->assertExists($chemin);

        // La valeur rendue est un chemin relatif, jamais une URL : c'est la forme
        // que tous les lecteurs comprennent.
        $this->assertStringNotContainsString('/storage/', $chemin);
        $this->assertStringNotContainsString('http', $chemin);
    }

    public function test_l_extension_vient_du_contenu_et_non_du_nom_envoye(): void
    {
        // Le nom annonce un script, le contenu est une image. L'ancien code
        // composait le nom du fichier avec l'extension du client, sur un disque
        // public : c'etait au deposant de choisir l'extension de son depot.
        $piege = UploadedFile::fake()->create('innocent.php', 12, 'image/png');

        $chemin = $this->stockage->enregistrer($piege, 'etudiant');

        $this->assertStringEndsWith('.png', $chemin);
        $this->assertStringNotContainsString('.php', $chemin);
    }

    public function test_une_extension_inconnue_retombe_sur_jpg(): void
    {
        $chemin = $this->stockage->enregistrer(UploadedFile::fake()->create('x.bin', 4, 'application/octet-stream'), 'p');

        $this->assertStringEndsWith('.jpg', $chemin);
    }

    /**
     * @dataProvider formesHistoriques
     */
    public function test_les_formes_historiques_restent_lisibles(string $stockeEnBase, string $ouEstLeFichier): void
    {
        Storage::disk('public')->put($ouEstLeFichier, 'donnees');

        $url = $this->stockage->url($stockeEnBase);

        $this->assertNotNull($url, 'La forme « '.$stockeEnBase.' » doit rester lisible.');
        $this->assertStringContainsString(basename($ouEstLeFichier), $url);
        // Le defaut repare : plus de double prefixe.
        $this->assertStringNotContainsString('storage//storage', $url);
        $this->assertStringNotContainsString('/storage/storage/', $url);
    }

    public static function formesHistoriques(): array
    {
        return [
            'chemin canonique' => [StockagePhoto::DOSSIER.'/a.jpg', StockagePhoto::DOSSIER.'/a.jpg'],
            'nom nu (inscription)' => ['b.jpg', StockagePhoto::DOSSIER.'/b.jpg'],
            'URL (ecran d edition)' => ['/storage/etudiants/photos/c.jpg', 'etudiants/photos/c.jpg'],
            'chemin relatif (bouton camera)' => ['photos/d.jpg', 'photos/d.jpg'],
            'URL absolue' => ['https://ecole.klassci.com/storage/photos/e.jpg', 'photos/e.jpg'],
        ];
    }

    public function test_une_photo_introuvable_ne_rend_pas_une_url_fabriquee(): void
    {
        // L'ancien accesseur rendait une URL meme sans fichier : le navigateur
        // affichait une vignette brisee. Mieux vaut null, et l'image par defaut.
        $this->assertNull($this->stockage->url('photos/etudiants/absente.jpg'));
        $this->assertNull($this->stockage->url(null));
        $this->assertNull($this->stockage->url('   '));
    }

    public function test_la_suppression_retrouve_les_anciens_dossiers(): void
    {
        Storage::disk('public')->put('etudiants/photos/vieille.jpg', 'donnees');

        $this->stockage->supprimer('/storage/etudiants/photos/vieille.jpg');

        Storage::disk('public')->assertMissing('etudiants/photos/vieille.jpg');
    }

    public function test_supprimer_une_photo_absente_ne_leve_rien(): void
    {
        $this->stockage->supprimer('photos/etudiants/jamais-existe.jpg');
        $this->stockage->supprimer(null);

        $this->assertTrue(true);
    }
}
