<?php

namespace App\Domain\Assistant\Pieces;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Un fichier joint à l'assistant → un tableau (en-têtes + lignes de texte).
 *
 *  - Excel (xlsx, xls) et CSV : la première feuille. Les en-têtes sont la première
 *    ligne d'au moins deux cellules (un titre seul au-dessus du tableau est sauté).
 *    Un nombre est lu tel qu'il est STOCKÉ, jamais tel que le format l'affiche
 *    (12,5 formaté « 0 » s'afficherait 13). Une formule rend la dernière valeur
 *    enregistrée par Excel, jamais un recalcul (le recalcul verrait des cellules
 *    hors lecture, ou d'autres classeurs, et rendrait 0) ; les dates se formatent
 *    à partir de cette même valeur. Une formule sans valeur enregistrée fait
 *    refuser le fichier. Limite connue : un outil qui enregistre 0 comme valeur de
 *    formule sans calculer ne se distingue pas d'un vrai 0 — la carte de validation
 *    montre chaque note avant écriture.
 *  - Word (docx) : le premier tableau du document, lu en flux (XMLReader) : la
 *    mémoire ne dépend pas de la taille du document. Le texte libre n'est pas lu.
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
    /**
     * Une feuille lue en SimpleXML occupe des dizaines de fois sa taille, hors
     * memory_limit (mesuré : 8 Mo de XML → 570 Mo de mémoire). Une feuille de notes
     * réelle tient sous 1 Mo : chaque fichier XML de l'archive est borné à 2 Mo.
     */
    private const MAX_ENTREE = 2 * 1024 * 1024;

    /** La source dépasse ce qui est lu (lignes ou colonnes que le filtre ne charge pas). */
    private bool $auDela = false;

    /** @return array{colonnes: string[], lignes: array<int, string[]>, tronque: bool} */
    public function lire(UploadedFile $fichier): array
    {
        $format = $this->format($fichier);
        $chemin = $fichier->getRealPath();
        if ($format !== 'csv') {
            $this->verifierArchive($chemin);
        }

        $this->auDela = false;
        $tableau = $this->normaliser($format === 'docx' ? $this->tableauWord($chemin) : $this->feuille($chemin, $format));
        $tableau['tronque'] = $tableau['tronque'] || $this->auDela;

        return $tableau;
    }

    private function format(UploadedFile $fichier): string
    {
        $contenu = strtolower((string) $fichier->guessExtension());
        $nom = strtolower($fichier->getClientOriginalExtension());

        // libmagic varie d'un serveur à l'autre : un xlsx peut sortir « zip », un xls « cdf ».
        // Le nom ne sert alors qu'à départager, jamais à contredire le contenu.
        if (in_array($contenu, ['zip', 'bin', ''], true) && in_array($nom, ['xlsx', 'docx'], true) && $this->estOpenXml($fichier->getRealPath())) {
            return $nom;
        }
        if (in_array($contenu, ['cdf', 'cdfv2', 'bin', ''], true) && $nom === 'xls') {
            return 'xls';
        }

        return match (true) {
            in_array($contenu, ['xlsx', 'xls', 'docx'], true) => $contenu,
            // Un CSV est du texte : son contenu ne dit rien de plus que « txt ».
            in_array($contenu, ['csv', 'txt'], true) && $nom === 'csv' => 'csv',
            default => throw new PieceIllisible('Format non pris en charge : joignez un fichier Excel (.xlsx, .xls), CSV ou Word (.docx).'),
        };
    }

    private function estOpenXml(string $chemin): bool
    {
        $zip = new \ZipArchive();
        $ok = $zip->open($chemin) === true && $zip->locateName('[Content_Types].xml') !== false;
        if ($ok) {
            $zip->close();
        }

        return $ok;
    }

    private function verifierArchive(string $chemin): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($chemin) !== true) {
            // Un .xls (ancien format) n'est pas une archive zip : rien à vérifier.
            return;
        }
        $total = 0;
        $plusGrosse = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $taille = (int) ($stat['size'] ?? 0);
            $total += $taille;
            // Le document Word se lit en flux ; les images ne sont pas lues : seuls les
            // XML d'un classeur sont bornés un à un.
            $nomEntree = (string) ($stat['name'] ?? '');
            if ($nomEntree !== 'word/document.xml' && str_ends_with($nomEntree, '.xml')) {
                $plusGrosse = max($plusGrosse, $taille);
            }
        }
        $zip->close();
        if ($total > self::MAX_DECOMPRESSE || $plusGrosse > self::MAX_ENTREE) {
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
            $this->auDela = $format === 'xlsx' ? $this->valeursAuDela($chemin) : $this->dimensionAuDela($lecteur, $chemin);
            $feuille = $lecteur->load($chemin)->getSheet(0);
        } catch (\Throwable $e) {
            throw new PieceIllisible('Le fichier n\'a pas pu être lu.');
        }

        $lignes = [];
        foreach ($feuille->getRowIterator(1, self::MAX_LIGNES + 2) as $rang) {
            $cellules = [];
            foreach ($rang->getCellIterator('A', Coordinate::stringFromColumnIndex(self::MAX_COLONNES + 1)) as $cellule) {
                $cellules[] = $this->valeur($cellule, $format === 'csv');
            }
            $lignes[] = $cellules;
        }

        return $lignes;
    }

    /**
     * Une cellule PORTANT une valeur au-delà des limites ? Passage en flux sur la
     * première feuille : les lignes vides mises en forme (fréquentes dans Excel) ne
     * comptent pas, contrairement aux dimensions déclarées.
     */
    private function valeursAuDela(string $chemin): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($chemin) !== true) {
            return false;
        }
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml', self::MAX_ENTREE + 1);
        $zip->close();
        if (! is_string($xml) || $xml === '') {
            return false;
        }

        $lecteur = \XMLReader::XML($xml, null, LIBXML_NONET);
        $cellule = null;
        while (@$lecteur->read()) {
            if ($lecteur->nodeType !== \XMLReader::ELEMENT) {
                continue;
            }
            if ($lecteur->name === 'c') {
                $cellule = (string) $lecteur->getAttribute('r');
            } elseif (in_array($lecteur->name, ['v', 'is'], true) && $cellule && preg_match('/^([A-Z]+)(\d+)$/', $cellule, $m)
                && (Coordinate::columnIndexFromString($m[1]) > self::MAX_COLONNES || (int) $m[2] > self::MAX_LIGNES + 2)) {
                return true;
            }
        }

        return false;
    }

    private function dimensionAuDela($lecteur, string $chemin): bool
    {
        $info = $lecteur->listWorksheetInfo($chemin)[0] ?? [];

        return (int) ($info['totalColumns'] ?? 0) > self::MAX_COLONNES || (int) ($info['totalRows'] ?? 0) > self::MAX_LIGNES + 2;
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

    private function valeur(Cell $cellule, bool $csv): string
    {
        if ($cellule->getDataType() === DataType::TYPE_FORMULA) {
            if ($csv) {
                // En CSV, « =… » est du texte saisi, pas une formule.
                return (string) $cellule->getValue();
            }
            $valeur = $cellule->getOldCalculatedValue();
            if ($valeur === null) {
                throw new PieceIllisible('Ce fichier contient des formules jamais calculées : ouvrez-le dans Excel, enregistrez-le, puis joignez-le de nouveau.');
            }
        } else {
            $valeur = $cellule->getValue();
        }
        // Texte mis en forme (gras sur une partie, chaîne en ligne) : son texte brut.
        if ($valeur instanceof RichText) {
            $valeur = $valeur->getPlainText();
        }
        // Date : formatée à partir de la valeur lue, sans recalcul.
        if ((is_int($valeur) || is_float($valeur)) && Date::isDateTime($cellule, $valeur)) {
            return (string) NumberFormat::toFormattedString($valeur, (string) $cellule->getStyle()->getNumberFormat()->getFormatCode());
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
        // Lu par getFromName avec une longueur maximale : la taille DÉCLARÉE dans l'archive
        // peut mentir, pas celle-ci. zip:// ne respectait ni l'une ni l'autre.
        $zip = new \ZipArchive();
        $ouvert = $zip->open($chemin) === true;
        $xml = $ouvert ? $zip->getFromName('word/document.xml', self::MAX_DECOMPRESSE + 1) : false;
        if ($ouvert) {
            $zip->close();
        }
        if (! is_string($xml) || $xml === '') {
            throw new PieceIllisible('Le document Word n\'a pas pu être ouvert.');
        }
        if (strlen($xml) > self::MAX_DECOMPRESSE) {
            throw new PieceIllisible('Ce fichier est trop volumineux une fois ouvert. Enregistrez seulement le tableau utile.');
        }

        $erreursAvant = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $lecteur = \XMLReader::XML($xml, null, LIBXML_NONET);
        unset($xml);

        $lignes = [];
        $abime = false;
        $profondeur = 0;   // imbrication des w:tbl : seul le premier tableau (niveau 1) est lu
        $ligne = null;
        $cellule = null;
        try {
            while (@$lecteur->read()) {
                if ($lecteur->nodeType === \XMLReader::DOC_TYPE) {
                    // Un document Word ne porte jamais de DOCTYPE : c'est un fichier fabriqué.
                    throw new PieceIllisible('Le document Word est endommagé.');
                }
                $ouvre = $lecteur->nodeType === \XMLReader::ELEMENT;
                $vide = $ouvre && $lecteur->isEmptyElement;
                $ferme = $lecteur->nodeType === \XMLReader::END_ELEMENT || $vide;
                $nom = $lecteur->name;

                if ($nom === 'w:tbl') {
                    if ($ouvre && ! $vide) {
                        $profondeur++;
                    } elseif ($ferme && ! $vide && --$profondeur === 0) {
                        break;
                    }
                    continue;
                }
                if ($profondeur !== 1 && $nom !== 'w:t') {
                    continue;
                }
                if ($nom === 'w:tr' && $ouvre) {
                    if (count($lignes) > self::MAX_LIGNES) {
                        $this->auDela = true;
                        break;
                    }
                    $ligne = [];
                }
                if ($nom === 'w:tr' && $ferme && $ligne !== null) {
                    $lignes[] = $ligne;
                    $ligne = null;
                } elseif ($nom === 'w:tc' && $ouvre) {
                    $cellule = '';
                }
                if ($nom === 'w:tc' && $ferme && $ligne !== null) {
                    // Au-delà de la dernière colonne lue, on ne garde plus rien : une ligne
                    // de millions de cellules vides ne doit pas remplir la mémoire.
                    if (count($ligne) < self::MAX_COLONNES) {
                        $ligne[] = trim((string) $cellule);
                    } elseif (trim((string) $cellule) !== '') {
                        $this->auDela = true;
                    }
                    $cellule = null;
                } elseif ($nom === 'w:t' && $ouvre && ! $vide && $cellule !== null && mb_strlen($cellule) < self::MAX_CELLULE) {
                    $cellule .= ($cellule === '' ? '' : ' ') . mb_substr($lecteur->readString(), 0, self::MAX_CELLULE);
                }
            }
            // Une erreur de lecture arrête la boucle comme une fin de fichier : il faut
            // la distinguer, sinon un document abîmé passerait pour complet.
            $abime = libxml_get_errors() !== [] || $profondeur > 0 && count($lignes) <= self::MAX_LIGNES;
        } finally {
            $lecteur->close();
            libxml_clear_errors();
            libxml_use_internal_errors($erreursAvant);
        }
        if ($abime) {
            throw new PieceIllisible('Le document Word est endommagé.');
        }

        if ($lignes === []) {
            throw new PieceIllisible('Aucun tableau dans ce document : mettez les notes dans un tableau (une ligne par étudiant).');
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

        // L'en-tête est la première ligne d'au moins deux cellules : un titre seul au-dessus
        // du tableau (« Notes du devoir de maths ») n'est ni une en-tête ni une donnée.
        while (count($lignes) > 1 && count(array_filter($lignes[0], fn ($c) => $c !== '')) < 2) {
            array_shift($lignes);
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
