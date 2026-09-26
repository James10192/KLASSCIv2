<?php

namespace App\Domain\Assistant\Pieces;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Un fichier joint à l'assistant → un tableau (en-têtes + lignes de texte).
 *
 *  - Excel (xlsx, xls) et CSV : la première feuille ; la première ligne non vide
 *    donne les en-têtes. Les valeurs sont lues telles qu'affichées (formatées),
 *    jamais recalculées : une formule rend sa valeur.
 *  - Word (docx) : le premier tableau du document. Le texte libre n'est pas lu :
 *    une note se lit dans un tableau, pas dans une phrase.
 *
 * Rien n'est interprété ici (ni note, ni étudiant) : c'est l'action qui décide,
 * avec ses propres gardes.
 */
class LectureDePiece
{
    public const MAX_LIGNES = 500;
    public const MAX_COLONNES = 30;
    private const MAX_CELLULE = 200;

    /** @return array{colonnes: string[], lignes: array<int, string[]>} */
    public function lire(UploadedFile $fichier): array
    {
        $extension = strtolower($fichier->getClientOriginalExtension());
        $brut = $extension === 'docx'
            ? $this->tableauWord($fichier->getRealPath())
            : $this->feuille($fichier->getRealPath(), $extension);

        return $this->normaliser($brut);
    }

    /** @return array<int, array<int, mixed>> */
    private function feuille(string $chemin, string $extension): array
    {
        $lecteur = IOFactory::createReader(match ($extension) {
            'xlsx' => 'Xlsx',
            'xls' => 'Xls',
            'csv' => 'Csv',
            default => throw new PieceIllisible('Format non pris en charge.'),
        });
        // Seules les cellules utiles sont chargées : une feuille de 100 000 lignes
        // ne doit pas remplir la mémoire pour en lire 500.
        $lecteur->setReadFilter(new class (self::MAX_LIGNES + 1, self::MAX_COLONNES) implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
            public function __construct(private int $lignes, private int $colonnes)
            {
            }

            public function readCell($colonne, $ligne, $feuille = ''): bool
            {
                return $ligne <= $this->lignes
                    && \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colonne) <= $this->colonnes;
            }
        });
        if ($extension === 'csv') {
            // Séparateur détecté sur la première ligne : les exports français utilisent « ; ».
            // L'encodage se juge sur le début du fichier, pas sur la seule ligne d'en-têtes
            // (souvent sans accent) : Excel enregistre encore en Windows-1252.
            $debut = (string) file_get_contents($chemin, false, null, 0, 65536);
            $premiere = strtok($debut, "\n") ?: '';
            $lecteur->setDelimiter(substr_count($premiere, ';') > substr_count($premiere, ',') ? ';' : ',');
            // Coupé à 64 Ko, le dernier caractère peut l'être aussi : on ne juge pas sur lui.
            $echantillon = strlen($debut) === 65536 ? substr($debut, 0, -4) : $debut;
            $lecteur->setInputEncoding(mb_check_encoding($echantillon, 'UTF-8') ? 'UTF-8' : 'Windows-1252');
        }
        $lecteur->setReadDataOnly(false);

        try {
            $feuille = $lecteur->load($chemin)->getSheet(0);
        } catch (\Throwable $e) {
            throw new PieceIllisible('Le fichier n\'a pas pu être lu.');
        }

        $lignes = [];
        foreach ($feuille->getRowIterator(1, self::MAX_LIGNES + 1) as $rang) {
            $cellules = [];
            foreach ($rang->getCellIterator('A', $this->colonneMax()) as $cellule) {
                $cellules[] = $cellule->getFormattedValue();
            }
            $lignes[] = $cellules;
        }

        return $lignes;
    }

    /** @return array<int, array<int, string>> */
    private function tableauWord(string $chemin): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($chemin) !== true || ($xml = $zip->getFromName('word/document.xml')) === false) {
            throw new PieceIllisible('Le document Word n\'a pas pu être ouvert.');
        }
        $zip->close();

        $dom = new \DOMDocument();
        // LIBXML_NONET : aucune ressource externe (pas d'entité réseau dans un fichier reçu).
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
            if (count($lignes) > self::MAX_LIGNES) {
                break;
            }
        }

        return $lignes;
    }

    /** @return array{colonnes: string[], lignes: array<int, string[]>} */
    private function normaliser(array $brut): array
    {
        $texte = fn ($v) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $v)), 0, self::MAX_CELLULE);
        $lignes = array_values(array_filter(
            array_map(fn ($l) => array_map($texte, array_slice((array) $l, 0, self::MAX_COLONNES)), $brut),
            fn ($l) => implode('', $l) !== ''
        ));
        if (count($lignes) < 2) {
            throw new PieceIllisible('Le fichier doit contenir une ligne d\'en-têtes et au moins une ligne de données.');
        }

        $colonnes = array_shift($lignes);
        // Colonnes vides à droite retirées ; une en-tête vide reçoit un nom (« Colonne C »).
        $largeur = max(array_map(fn ($l) => count(array_filter($l, fn ($c) => $c !== '')) ? max(array_keys(array_filter($l, fn ($c) => $c !== ''))) + 1 : 0, array_merge([$colonnes], $lignes)));
        $colonnes = array_map(fn ($i) => ($colonnes[$i] ?? '') !== '' ? $colonnes[$i] : 'Colonne ' . $this->lettre($i), range(0, $largeur - 1));
        $lignes = array_map(fn ($l) => array_map(fn ($i) => $l[$i] ?? '', range(0, $largeur - 1)), $lignes);

        return ['colonnes' => $colonnes, 'lignes' => array_slice($lignes, 0, self::MAX_LIGNES)];
    }

    private function colonneMax(): string
    {
        return $this->lettre(self::MAX_COLONNES - 1);
    }

    private function lettre(int $i): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
    }
}
