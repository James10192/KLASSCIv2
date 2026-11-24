<?php

namespace App\Domain\Exports;

use App\Helpers\SettingsHelper;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Le gabarit des classeurs Excel, pendant du composant PDF : logo, nom et
 * coordonnées de l'établissement, couleurs des réglages PDF, page A4 dans
 * l'orientation choisie, ajustée à la largeur, en-têtes répétés à l'impression.
 *
 * Les couleurs viennent de SettingsHelper::getPdfSettings() : une école qui
 * règle son PDF règle du même geste son Excel.
 */
final class GabaritExcelEtablissement
{
    /** Lignes de bandeau insérées au-dessus de la ligne d'en-tête du tableau. */
    public const LIGNES_BANDEAU = 4;

    private array $theme;

    private array $ecole;

    public function __construct()
    {
        $this->theme = SettingsHelper::getPdfSettings();
        $this->ecole = SettingsHelper::getSchoolInfo();
    }

    /** Couleur hexadécimale sans dièse, au format attendu par PhpSpreadsheet. */
    public function couleur(string $cle, string $defaut): string
    {
        $hex = ltrim((string) ($this->theme[$cle] ?? $defaut), '#');

        return strtoupper(preg_match('/^[0-9a-fA-F]{6}$/', $hex) ? $hex : ltrim($defaut, '#'));
    }

    /**
     * Insère le bandeau de l'établissement avant $ligneEntete et rend la
     * nouvelle position de la ligne d'en-tête.
     */
    public function poserBandeau(Worksheet $sheet, int $ligneEntete, string $derniereColonne, string $titreDocument, string $sousTitre = ''): int
    {
        $sheet->insertNewRowBefore($ligneEntete, self::LIGNES_BANDEAU);
        $debut = $ligneEntete;
        $primaire = $this->couleur('primary_color', '#0453cb');

        $adresse = collect([$this->ecole['address'] ?? '', $this->ecole['city'] ?? '', $this->ecole['phone'] ?? '', $this->ecole['email'] ?? ''])
            ->map(fn ($v) => trim((string) $v))->filter()->join(' · ');

        // Le logo occupe la colonne A ; le texte part de B pour ne pas passer dessous.
        $lignes = [
            [$this->ecole['name'] ?? 'Établissement', ['bold' => true, 'size' => 14, 'color' => ['rgb' => $primaire]]],
            [$adresse, ['size' => 9, 'color' => ['rgb' => $this->couleur('secondary_color', '#64748b')]]],
            [mb_strtoupper($titreDocument, 'UTF-8'), ['bold' => true, 'size' => 11, 'color' => ['rgb' => $this->couleur('text_color', '#1f2937')]]],
            [$sousTitre, ['size' => 9, 'color' => ['rgb' => $this->couleur('secondary_color', '#64748b')]]],
        ];
        foreach ($lignes as $i => [$texte, $police]) {
            $r = $debut + $i;
            $sheet->mergeCells("B{$r}:{$derniereColonne}{$r}");
            $sheet->setCellValue("B{$r}", $texte);
            $sheet->getStyle("B{$r}")->applyFromArray([
                'font' => $police,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($r)->setRowHeight($i === 0 ? 22 : 16);
        }
        // Filet de couleur sous le bandeau.
        $derniere = $debut + self::LIGNES_BANDEAU - 1;
        $sheet->getStyle("A{$derniere}:{$derniereColonne}{$derniere}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($primaire);

        $this->poserLogo($sheet, "A{$debut}");

        return $ligneEntete + self::LIGNES_BANDEAU;
    }

    /** Style de la ligne d'en-tête du tableau, aux couleurs d'en-tête du PDF. */
    public function styleEntete(Worksheet $sheet, string $plage): void
    {
        $sheet->getStyle($plage)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $this->couleur('header_text_on_bg', '#ffffff')]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->couleur('header_bg_color', '#0453cb')]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
    }

    /** Mise en page A4 : orientation, ajusté en largeur, en-têtes répétés, pagination. */
    public function miseEnPage(Worksheet $sheet, string $orientation, int $ligneEntete): void
    {
        $page = $sheet->getPageSetup();
        $page->setPaperSize(PageSetup::PAPERSIZE_A4);
        $page->setOrientation($orientation === 'landscape' ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT);
        $page->setFitToWidth(1);
        $page->setFitToHeight(0);
        $page->setRowsToRepeatAtTopByStartAndEnd($ligneEntete, $ligneEntete);
        $page->setHorizontalCentered(true);

        $marges = $sheet->getPageMargins();
        $marges->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.6);

        $pied = '&L&8'.str_replace('&', '&&', (string) ($this->ecole['name'] ?? '')).'&R&8Page &P / &N';
        $sheet->getHeaderFooter()->setOddFooter($pied)->setEvenFooter($pied);
    }

    private function poserLogo(Worksheet $sheet, string $cellule): void
    {
        $chemin = SettingsHelper::resolveLogoPath();
        if (! $chemin || ! in_array(strtolower(pathinfo($chemin, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif'], true)) {
            return; // pas de logo exploitable : le classeur reste valable sans image
        }
        try {
            $logo = new Drawing();
            $logo->setName('Logo établissement');
            $logo->setPath($chemin);
            $logo->setHeight(60);
            $logo->setCoordinates($cellule);
            $logo->setOffsetX(4);
            $logo->setOffsetY(2);
            $logo->setWorksheet($sheet);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gabarit Excel : logo ignoré', ['chemin' => $chemin, 'error' => $e->getMessage()]);
        }
    }
}
