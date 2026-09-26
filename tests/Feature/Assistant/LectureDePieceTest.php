<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Pieces\LectureDePiece;
use App\Domain\Assistant\Pieces\PieceIllisible;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Fichiers joints à l'assistant : un tableau fidèle, rien d'interprété.
 */
class LectureDePieceTest extends TestCase
{
    private function fichier(string $nom, string $contenu): UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), 'piece');
        file_put_contents($chemin, $contenu);

        return new UploadedFile($chemin, $nom, null, null, true);
    }

    public function test_un_excel_rend_les_valeurs_affichees_et_nomme_les_en_tetes_vides(): void
    {
        $classeur = new Spreadsheet();
        $f = $classeur->getActiveSheet();
        $f->fromArray([['Matricule', 'Nom', 'Note', ''], ['MAT-001', 'KOUASSI Aya', 14.5, 'x'], ['MAT-002', 'KONAN Jean', '=10+2', '']]);
        $chemin = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($classeur))->save($chemin);

        $t = app(LectureDePiece::class)->lire(new UploadedFile($chemin, 'notes.xlsx', null, null, true));

        $this->assertSame(['Matricule', 'Nom', 'Note', 'Colonne D'], $t['colonnes']);
        $this->assertSame(['MAT-001', 'KOUASSI Aya', '14.5', 'x'], $t['lignes'][0]);
        $this->assertSame('12', $t['lignes'][1][2]); // la formule rend sa valeur
    }

    public function test_une_note_est_lue_telle_que_stockee_jamais_arrondie_par_le_format(): void
    {
        $classeur = new Spreadsheet();
        $f = $classeur->getActiveSheet();
        $f->fromArray([['Matricule', 'Note', 'Note', 'Total'], ['MAT-001', 12.5, 12.75, '=SUM(AZ2:BA2)']]);
        $f->getStyle('B2')->getNumberFormat()->setFormatCode('0');
        $f->getStyle('C2')->getNumberFormat()->setFormatCode('0.0');
        $f->setCellValue('AZ2', 3);
        $f->setCellValue('BA2', 4);
        $chemin = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($classeur))->save($chemin);

        $t = app(LectureDePiece::class)->lire(new UploadedFile($chemin, 'notes.xlsx', null, null, true));

        $this->assertSame(['MAT-001', '12.5', '12.75', '7'], array_slice($t['lignes'][0], 0, 4));
        // Deux en-têtes « Note » ne se confondent pas ; la feuille dépasse 30 colonnes : c'est dit.
        $this->assertSame(['Matricule', 'Note', 'Note (2)', 'Total'], array_slice($t['colonnes'], 0, 4));
        $this->assertTrue($t['tronque']);
    }

    public function test_un_fichier_se_juge_sur_son_contenu_et_une_archive_trop_grosse_est_refusee(): void
    {
        // Un classeur renommé en .csv n'est pas lu comme un CSV.
        $classeur = new Spreadsheet();
        $classeur->getActiveSheet()->fromArray([['Matricule', 'Note'], ['MAT-001', 14]]);
        $chemin = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($classeur))->save($chemin);
        $t = app(LectureDePiece::class)->lire(new UploadedFile($chemin, 'notes.csv', null, null, true));
        $this->assertSame(['MAT-001', '14'], $t['lignes'][0]);

        // Une archive de quelques Ko qui se déplie en 50 Mo : refusée avant lecture.
        $bombe = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new \ZipArchive();
        $zip->open($bombe, \ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', str_repeat('A', 50 * 1024 * 1024));
        $zip->close();
        $this->expectException(PieceIllisible::class);
        $this->expectExceptionMessage('trop volumineux');
        app(LectureDePiece::class)->lire(new UploadedFile($bombe, 'notes.docx', null, null, true));
    }

    public function test_un_csv_francais_au_point_virgule_et_en_windows_1252(): void
    {
        $contenu = mb_convert_encoding("Matricule;Nom;Note\nMAT-001;KOUASSI Aïcha;12,5\n\nMAT-002;KONAN Jean;9\n", 'Windows-1252', 'UTF-8');

        $t = app(LectureDePiece::class)->lire($this->fichier('notes.csv', $contenu));

        $this->assertSame(['Matricule', 'Nom', 'Note'], $t['colonnes']);
        $this->assertCount(2, $t['lignes']); // la ligne vide est ignorée
        $this->assertSame(['MAT-001', 'KOUASSI Aïcha', '12,5'], $t['lignes'][0]);
    }

    public function test_un_word_rend_son_premier_tableau(): void
    {
        $cellule = fn (string $t) => "<w:tc><w:p><w:r><w:t>{$t}</w:t></w:r></w:p></w:tc>";
        $xml = '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . '<w:p><w:r><w:t>Notes du devoir</w:t></w:r></w:p><w:tbl>'
            . '<w:tr>' . $cellule('Étudiant') . $cellule('Note') . '</w:tr>'
            . '<w:tr>' . $cellule('KOUASSI Aya') . $cellule('15') . '</w:tr>'
            . '</w:tbl></w:body></w:document>';
        $chemin = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new \ZipArchive();
        $zip->open($chemin, \ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        $t = app(LectureDePiece::class)->lire(new UploadedFile($chemin, 'notes.docx', null, null, true));

        $this->assertSame(['Étudiant', 'Note'], $t['colonnes']);
        $this->assertSame([['KOUASSI Aya', '15']], $t['lignes']);
    }

    public function test_un_fichier_sans_donnees_est_refuse_avec_un_message_clair(): void
    {
        $this->expectException(PieceIllisible::class);
        $this->expectExceptionMessage('au moins une ligne de données');
        app(LectureDePiece::class)->lire($this->fichier('vide.csv', "Matricule;Note\n"));
    }
}
