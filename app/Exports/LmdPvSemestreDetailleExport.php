<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Le PV de délibération détaillé d'un semestre, au format que les universités
 * tiennent sous Excel : identité, puis un bloc par UE (notes des ECUE, moyenne,
 * année d'obtention, session), puis le résultat semestriel. Les UE et les
 * statistiques sont rappelées sous le tableau.
 */
class LmdPvSemestreDetailleExport implements FromCollection, WithTitle, WithEvents
{
    private const IDENTITE = ['N°', 'IP', 'NOM', 'PRÉNOMS', 'DATE DE NAISSANCE', 'LIEU DE NAISSANCE', 'SEXE', 'NATIONALITÉ'];
    private const RESULTAT = ['Moy. sem.', 'Crédits', 'Décision', "Année d'obtention", 'Session'];
    private const ENTETE = 7;
    /** Une ligne vide doit porter une cellule : la feuille saute `[]`, et tout remonterait d'un cran. */
    private const LIGNE_VIDE = [''];

    public function __construct(private array $payload) {}

    public function title(): string
    {
        return 'Semestre '.($this->payload['semestre'] ?? '');
    }

    public function collection(): Collection
    {
        return collect($this->array());
    }

    public function array(): array
    {
        $p = $this->payload;
        $rows = [
            [mb_strtoupper((string) ($p['ecole']['name'] ?? 'Établissement'), 'UTF-8')],
            self::LIGNE_VIDE,
            ['', 'ANNÉE UNIVERSITAIRE', $p['annee'] ?? ''],
            ['', 'DÉPARTEMENT', $p['departement'] ?? ''],
            ['', 'PARCOURS', $p['parcours'] ?? ''],
            ['', 'NIVEAU', trim(($p['niveau'] ?? '').' · '.($p['classe'] ?? ''), ' ·')],
            ['', '', 'SEMESTRE '.($p['semestre'] ?? '')],
            $this->ligneUe(),
            $this->ligneEcue(),
        ];

        foreach ($p['rows'] as $row) {
            $line = [$row['ordre'], $row['matricule'], $row['nom'], $row['prenoms'], $row['date_naissance'],
                $row['lieu_naissance'], $row['sexe'], $row['nationalite']];
            foreach ($row['ues'] as $ue) {
                array_push($line, ...$ue['notes']);
                array_push($line, $ue['moyenne'], $ue['annee'], $ue['session']);
            }
            array_push($line, $row['moyenne'], $row['credits'].' / '.$row['credits_totaux'], $row['decision'], $row['annee'], $row['session']);
            $rows[] = $line;
        }

        $rows[] = self::LIGNE_VIDE;
        $rows[] = ['UNITÉS D’ENSEIGNEMENT', '', '', '', '', '', '', 'CRÉDITS', '', '', 'STATISTIQUES', 'NOMBRE', '%'];
        $max = max(count($p['ues']), count($p['statistiques']));
        for ($i = 0; $i < $max; $i++) {
            $ue = $p['ues'][$i] ?? null;
            $stat = $p['statistiques'][$i] ?? null;
            $rows[] = [$ue ? 'UE : '.$ue['code'] : '', $ue['name'] ?? '', '', '', '', '', '', $ue['credit'] ?? '', '', '',
                $stat['libelle'] ?? '', $stat['nombre'] ?? '', $stat ? $stat['pourcentage'] : ''];
        }
        $rows[] = self::LIGNE_VIDE;
        $lieu = trim((string) ($p['ecole']['city'] ?? ''));
        $rows[] = ['', '', '', '', '', '', '', '', '', '', mb_strtoupper(($lieu !== '' ? $lieu.', ' : '').'LE '.($p['date_jury'] ?? '…'), 'UTF-8')];

        return $rows;
    }

    private function ligneUe(): array
    {
        $line = array_fill(0, count(self::IDENTITE), '');
        foreach ($this->payload['ues'] as $i => $ue) {
            $line[] = sprintf('UE %d : %s %s', $i + 1, $ue['code'], $ue['name']);
            array_push($line, ...array_fill(0, count($ue['ecues']) + 2, ''));
        }
        $line[] = 'Résultats semestriels';

        return $line;
    }

    private function ligneEcue(): array
    {
        $line = self::IDENTITE;
        foreach ($this->payload['ues'] as $ue) {
            foreach ($ue['ecues'] as $ecue) {
                $line[] = $ecue['name'];
            }
            array_push($line, 'Moyenne', "Année d'obtention", 'Session');
        }

        return array_merge($line, self::RESULTAT);
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $primary = ltrim((string) (\App\Helpers\SettingsHelper::getPdfSettings()['primary_color'] ?? '#0453cb'), '#');
            $col = count(self::IDENTITE) + 1;
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

            foreach ($this->payload['ues'] as $ue) {
                $fin = $col + count($ue['ecues']) + 2;
                $sheet->mergeCells(Coordinate::stringFromColumnIndex($col).(self::ENTETE + 1).':'.Coordinate::stringFromColumnIndex($fin).(self::ENTETE + 1));
                $col = $fin + 1;
            }
            $sheet->mergeCells(Coordinate::stringFromColumnIndex($col).(self::ENTETE + 1).':'.Coordinate::stringFromColumnIndex($col + count(self::RESULTAT) - 1).(self::ENTETE + 1));

            $derniere = Coordinate::stringFromColumnIndex($col + count(self::RESULTAT) - 1);
            $finDonnees = self::ENTETE + 2 + count($this->payload['rows']);
            $entete = 'A'.(self::ENTETE + 1).':'.$derniere.(self::ENTETE + 2);
            $sheet->getStyle($entete)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($entete)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($primary);
            $sheet->getStyle($entete)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(self::ENTETE + 2)->setRowHeight(48);
            $sheet->getStyle('A'.(self::ENTETE + 1).':'.$derniere.$finDonnees)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->freezePane('I'.(self::ENTETE + 3));
            foreach (range(1, $col + count(self::RESULTAT) - 1) as $i) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth($i === 2 ? 22 : ($i <= 8 ? 14 : 11));
            }
        }];
    }
}
