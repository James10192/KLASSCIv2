<?php

namespace App\Domain\Assistant\Pieces;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Un fichier joint à l'assistant → un tableau (en-têtes + lignes de texte).
 *
 *  - Excel (xlsx, xls) et CSV : la première feuille ; la première ligne non vide
 *    donne les en-têtes. Un nombre est lu tel qu'il est STOCKÉ, jamais tel que le
 *    format l'affiche (12,5 formaté « 0 » s'afficherait 13). Une formule rend la
 *    dernière valeur calculée par Excel, jamais un recalcul (le recalcul verrait
 *    des cellules hors lecture, ou d'autres classeurs, et rendrait 0). Seules les
 *    dates passent par leur format.
 *  - Word (docx) : le premier tableau du document. Le texte libre n'est pas lu.
 *
 * Le format se décide sur le CONTENU du fichier (guessExtension), pas sur son nom.
 * Rien n'est interprété ici (ni note, ni étudiant) : c'est l'action qui décide.
 */
class LectureDePiece
{
    public const MAX_LIGNES = 500;
    public const MAX_COLONNES = 30;
    private const MAX_CELLULE = 200;
    /** Taille décompressée maximale d'un xlsx ou d'un docx : une archive de 2 Mo peut en cacher des centaines. */
    private const MAX_DECOMPRESSE = 40 * 1024 * 1024;

    /** La feuille a des colonnes au-delà de la limite (le filtre de lecture ne les charge pas). */
    private bool $colonnesAuDela = false;

    /** @return array{colonnes: string[], lignes: array<int, string[]>, tronque: bool} */
    public function lire(UploadedFile $fichier): array
    {
        $format = $this->format($fichier);
        $chemin = $fichier->getRealPath();
        if ($format !== 'csv') {
            $this->verifierArchive($chemin);
        }

        $this->colonnesAuDela = false;
        $tableau = $this->normaliser($format === 'docx' ? $this->tableauWord($chemin) : $this->feuille($chemin, $format));
        $tableau['tronque'] = $tableau['tronque'] || $this->colonnesAuDela;

        return $tableau;
    }

    private function format(UploadedFile $fichier): string
    {
        $contenu = strtolower((string) $fichier->guessExtension());
        $nom = strtolower($fichier->getClientOriginalExtension());

        return match (true) {
            in_array($contenu, ['xlsx', 'xls', 'docx'], true) => $contenu,
            // Un CSV est du texte : son contenu ne dit rien de plus que « txt ».
            in_array($contenu, ['csv', 'txt'], true) && $nom === 'csv' => 'csv',
            default => throw new PieceIllisible('Format non pris en charge : joignez un fichier Excel (.xlsx, .xls), CSV ou Word (.docx).'),
        };
    }

    private function verifierArchive(string $chemin): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($chemin) !== true) {
            // Un .xls (ancien format) n'est pas une archive zip : rien à vérifier.
            return;
        }
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $total += (int) ($zip->statIndex($i)['size'] ?? 0);
        }
        $zip->close();
        if ($total > self::MAX_DECOMPRESSE) {
            throw new PieceIllisible('Ce fichier est trop volumineux une fois ouvert. Enregistrez seulement la feuille utile.');
        }
    }

    /** @return array<int, array<int, string>> */
    private function feuille(string $chemin, string $format): array
    {
        $lecteur = IOFactory::createReader(['xlsx' => 'Xlsx', 'xls' => 'Xls', 'csv' => 'Csv'][$format]);
        $lecteur->setReadFilter(new class (self::MAX_LIGNES + 2, self::MAX_COLONNES + 1) implements IReadFilter {
            public function __construct(private int $lignes, private int $colonnes)
            {
            }

            public function readCell($colonne, $ligne, $feuille = ''): bool
            {
                return $ligne <= $this->lignes && Coordinate::columnIndexFromString($colonne) <= $this->colonnes;
            }
        });

        try {
            if ($format === 'csv') {
                $this->configurerCsv($lecteur, $chemin);
            } else {
                // Seule la première feuille est chargée : les autres ne coûtent rien.
                $lecteur->setLoadSheetsOnly([$lecteur->listWorksheetNames($chemin)[0] ?? '']);
            }
            $this->colonnesAuDela = (int) ($lecteur->listWorksheetInfo($chemin)[0]['totalColumns'] ?? 0) > self::MAX_COLONNES;
            $feuille = $lecteur->load($chemin)->getSheet(0);
        } catch (\Throwable $e) {
            throw new PieceIllisible('Le fichier n\'a pas pu être lu.');
        }

        $lignes = [];
        foreach ($feuille->getRowIterator(1, self::MAX_LIGNES + 2) as $rang) {
            $cellules = [];
            foreach ($rang->getCellIterator('A', Coordinate::stringFromColumnIndex(self::MAX_COLONNES + 1)) as $cellule) {
                $cellules[] = $this->valeur($cellule);
            }
            $lignes[] = $cellules;
        }

        return $lignes;
    }

    private function configurerCsv($lecteur, string $chemin): void
    {
        // L'encodage se juge sur le début du fichier, pas sur la seule ligne d'en-têtes
        // (souvent sans accent) : Excel enregistre encore en Windows-1252. Coupé à
        // 64 Ko, le dernier caractère peut l'être aussi : on ne juge pas sur lui.
        $debut = (string) file_get_contents($chemin, false, null, 0, 65536);
        $premiere = strtok($debut, "\n") ?: '';
        $echantillon = strlen($debut) === 65536 ? substr($debut, 0, -4) : $debut;
        $lecteur->setDelimiter(substr_count($premiere, ';') > substr_count($premiere, ',') ? ';' : ',');
        $lecteur->setInputEncoding(mb_check_encoding($echantillon, 'UTF-8') ? 'UTF-8' : 'Windows-1252');
    }

    private function valeur(Cell $cellule): string
    {
        if ($cellule->getDataType() === DataType::TYPE_FORMULA) {
            $valeur = $cellule->getOldCalculatedValue();
        } else {
            $valeur = $cellule->getValue();
        }
        if ((is_int($valeur) || is_float($valeur)) && Date::isDateTime($cellule)) {
            return (string) $cellule->getFormattedValue();
        }
        if (is_float($valeur)) {
            // La valeur stockée, sans arrondi d'affichage ni notation scientifique.
            return rtrim(rtrim(number_format($valeur, 10, '.', ''), '0'), '.');
        }

        return is_scalar($valeur) ? (string) $valeur : '';
    }

    /** @return array<int, array<int, string>> */
    private function tableauWord(string $chemin): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($chemin) !== true || ($xml = $zip->getFromName('word/document.xml')) === false) {
            throw new PieceIllisible('Le document Word n\'a pas pu être ouvert.');
        }
        $zip->close();

        // Un document Word ne porte jamais de DOCTYPE : en trouver un, c'est un fichier fabriqué.
        if (stripos($xml, '<!DOCTYPE') !== false) {
            throw new PieceIllisible('Le document Word est endommagé.');
        }
        $dom = new \DOMDocument();
        if (! @$dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new PieceIllisible('Le document Word est endommagé.');
        }
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $tableau = $xp->query('//w:tbl')->item(0);
        if (! $tableau) {
            throw new PieceIllisible('Aucun tableau dans ce document : mettez les notes dans un tableau (une ligne par étudiant).');
        }

        $lignes = [];
        foreach ($xp->query('./w:tr', $tableau) as $tr) {
            $ligne = [];
            foreach ($xp->query('./w:tc', $tr) as $tc) {
                $ligne[] = trim(implode(' ', array_map(fn ($t) => $t->textContent, iterator_to_array($xp->query('.//w:t', $tc)))));
            }
            $lignes[] = $ligne;
            if (count($lignes) > self::MAX_LIGNES + 1) {
                break;
            }
        }

        return $lignes;
    }

    /** @return array{colonnes: string[], lignes: array<int, string[]>, tronque: bool} */
    private function normaliser(array $brut): array
    {
        $texte = fn ($v) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $v)), 0, self::MAX_CELLULE);
        $tronque = false;
        $lignes = [];
        foreach ($brut as $l) {
            $tronque = $tronque || count((array) $l) > self::MAX_COLONNES && implode('', array_slice((array) $l, self::MAX_COLONNES)) !== '';
            $ligne = array_map($texte, array_slice((array) $l, 0, self::MAX_COLONNES));
            if (implode('', $ligne) !== '') {
                $lignes[] = $ligne;
            }
        }
        if (count($lignes) < 2) {
            throw new PieceIllisible('Le fichier doit contenir une ligne d\'en-têtes et au moins une ligne de données.');
        }

        $colonnes = array_shift($lignes);
        if (count($lignes) > self::MAX_LIGNES) {
            $tronque = true;
            $lignes = array_slice($lignes, 0, self::MAX_LIGNES);
        }

        // Largeur utile : dernière colonne non vide, en-têtes compris.
        $largeur = 0;
        foreach (array_merge([$colonnes], $lignes) as $l) {
            foreach ($l as $i => $c) {
                if ($c !== '') {
                    $largeur = max($largeur, $i + 1);
                }
            }
        }

        return [
            'colonnes' => $this->entetesUniques(array_map(fn ($i) => $colonnes[$i] ?? '', range(0, $largeur - 1))),
            'lignes' => array_map(fn ($l) => array_map(fn ($i) => $l[$i] ?? '', range(0, $largeur - 1)), $lignes),
            'tronque' => $tronque,
        ];
    }

    /**
     * Une en-tête vide devient « Colonne C » ; deux en-têtes identiques (« Note »
     * pour le contrôle et pour l'examen) deviennent « Note » et « Note (2) » :
     * désigner une colonne ne doit jamais en choisir une autre en silence.
     */
    private function entetesUniques(array $entetes): array
    {
        $vus = [];
        foreach ($entetes as $i => $nom) {
            $nom = $nom !== '' ? $nom : 'Colonne ' . Coordinate::stringFromColumnIndex($i + 1);
            $cle = mb_strtolower($nom);
            $vus[$cle] = ($vus[$cle] ?? 0) + 1;
            $entetes[$i] = $vus[$cle] > 1 ? $nom . ' (' . $vus[$cle] . ')' : $nom;
        }

        return $entetes;
    }
}
