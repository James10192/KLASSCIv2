<?php

namespace App\Exports;

use App\Domain\Notifications\PhoneFormatter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export tableur du suivi : une ligne par piece manquante, pas par etudiant.
 *
 * Pourquoi a plat : c'est la forme qu'on trie et qu'on filtre pour preparer
 * physiquement les dossiers (« toutes les lignes extrait de naissance »).
 */
class SuiviPiecesExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithStyles,
    WithColumnFormatting,
    ShouldAutoSize
{
    /**
     * @param  array<int, array>  $lignes  lignes de suivi (une par inscription)
     */
    public function __construct(private readonly array $lignes) {}

    public function collection(): Collection
    {
        return collect(self::aplatir($this->lignes));
    }

    /**
     * Deplie les lignes « une par inscription » en lignes « une par piece ».
     * Fonction pure : testable sans base.
     *
     * @param  array<int, array>  $lignes
     * @return array<int, array>
     */
    public static function aplatir(array $lignes): array
    {
        $plat = [];

        foreach ($lignes as $ligne) {
            foreach ($ligne['manquantes'] as $manquante) {
                $plat[] = [
                    'matricule' => $ligne['matricule'] ?? '',
                    'etudiant' => $ligne['etudiant'] ?? '',
                    'classe' => $ligne['classe'] ?? '',
                    'filiere' => $ligne['filiere'] ?? '',
                    'niveau' => $ligne['niveau'] ?? '',
                    'annee' => $ligne['annee'] ?? '',
                    'piece' => $manquante['libelle'] ?? '',
                    'obligatoire' => ! empty($manquante['obligatoire']),
                    'exemplaires_manquants' => (int) ($manquante['exemplaires_manquants'] ?? 0),
                    'telephone' => $ligne['telephone'] ?? null,
                    'email' => $ligne['email'] ?? null,
                ];
            }
        }

        return $plat;
    }

    public function title(): string
    {
        return 'Pieces manquantes';
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Étudiant',
            'Classe',
            'Filière',
            'Niveau',
            'Année',
            'Pièce manquante',
            'Obligatoire',
            'Exemplaires manquants',
            'Téléphone',
            'Email',
        ];
    }

    public function map($row): array
    {
        return [
            $row['matricule'],
            $row['etudiant'],
            $row['classe'] ?: '—',
            $row['filiere'] ?: '—',
            $row['niveau'] ?: '—',
            $row['annee'] ?: '—',
            $row['piece'],
            $row['obligatoire'] ? 'Oui' : 'Non',
            $row['exemplaires_manquants'],
            PhoneFormatter::toReadable($row['telephone']) ?? '—',
            $row['email'] ?: '—',
        ];
    }

    public function columnFormats(): array
    {
        return [
            // Le telephone reste du texte, sinon Excel mange le « + » du format E.164.
            'J' => NumberFormat::FORMAT_TEXT,
            'I' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
