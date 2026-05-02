<?php

namespace App\Exports;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Excel des notes par classe + matière + période.
 *
 * Format ergonomique pour saisie offline :
 *  - 1 ligne par étudiant (matricule, nom, evals…, moyenne)
 *  - 1 colonne par évaluation (avec barème dans le header)
 *  - Header bleu KLASSCI #0453cb texte blanc
 *  - Freeze pane sur ligne header + 2 colonnes (matricule + nom)
 *  - AutoFilter sur la ligne header
 *  - Matricule formaté en text pour préserver leading zeros
 *  - Ligne de marqueur évaluation_id cachée pour ré-import (ligne 1)
 */
class NotesClasseMatiereExport implements
    FromArray,
    WithHeadings,
    WithTitle,
    WithColumnWidths,
    WithColumnFormatting,
    WithEvents
{
    private int $classeId;
    private int $matiereId;
    private string $periode;
    private ?int $anneeUniversitaireId;

    /** @var Collection<ESBTPEvaluation> */
    private Collection $evaluations;

    /** @var Collection<ESBTPInscription> */
    private Collection $inscriptions;

    /** @var array<string, ESBTPNote> indexée par etudiant_id . '_' . evaluation_id */
    private array $notesIndex = [];

    private ?ESBTPClasse $classe = null;
    private ?ESBTPMatiere $matiere = null;
    private ?ESBTPAnneeUniversitaire $annee = null;

    public function __construct(int $classeId, int $matiereId, string $periode, ?int $anneeUniversitaireId = null)
    {
        $this->classeId = $classeId;
        $this->matiereId = $matiereId;
        $this->periode = $periode;
        $this->anneeUniversitaireId = $anneeUniversitaireId;

        $this->loadData();
    }

    private function loadData(): void
    {
        $this->classe = ESBTPClasse::find($this->classeId);
        $this->matiere = ESBTPMatiere::find($this->matiereId);

        if ($this->anneeUniversitaireId === null) {
            $current = ESBTPAnneeUniversitaire::where('is_current', 1)->first();
            $this->anneeUniversitaireId = $current?->id;
            $this->annee = $current;
        } else {
            $this->annee = ESBTPAnneeUniversitaire::find($this->anneeUniversitaireId);
        }

        // Évaluations triées par date
        $this->evaluations = ESBTPEvaluation::query()
            ->where('classe_id', $this->classeId)
            ->where('matiere_id', $this->matiereId)
            ->where('periode', $this->periode)
            ->when($this->anneeUniversitaireId, fn ($q) => $q->where('annee_universitaire_id', $this->anneeUniversitaireId))
            ->where('is_published', 1)
            ->orderBy('date_evaluation')
            ->orderBy('id')
            ->get();

        // Inscriptions actives triées par nom
        $this->inscriptions = ESBTPInscription::query()
            ->with(['etudiant:id,matricule,nom,prenoms'])
            ->where('classe_id', $this->classeId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->when($this->anneeUniversitaireId, fn ($q) => $q->where('annee_universitaire_id', $this->anneeUniversitaireId))
            ->get()
            ->filter(fn ($i) => $i->etudiant !== null)
            ->sortBy(fn ($i) => Str::lower(($i->etudiant->nom ?? '') . ' ' . ($i->etudiant->prenoms ?? '')))
            ->values();

        // Notes indexées
        $evalIds = $this->evaluations->pluck('id')->all();
        $etuIds = $this->inscriptions->pluck('etudiant_id')->all();

        if ($evalIds && $etuIds) {
            $notes = ESBTPNote::whereIn('evaluation_id', $evalIds)
                ->whereIn('etudiant_id', $etuIds)
                ->get();

            foreach ($notes as $note) {
                $this->notesIndex[$note->etudiant_id . '_' . $note->evaluation_id] = $note;
            }
        }
    }

    /**
     * Compte de cellules de saisie (étudiants × évaluations) — pour garde-fou volume.
     */
    public function cellsCount(): int
    {
        return $this->inscriptions->count() * max(1, $this->evaluations->count());
    }

    public function studentsCount(): int
    {
        return $this->inscriptions->count();
    }

    public function evaluationsCount(): int
    {
        return $this->evaluations->count();
    }

    public function classeName(): ?string
    {
        return $this->classe?->name;
    }

    public function matiereName(): ?string
    {
        return $this->matiere?->name;
    }

    public function title(): string
    {
        $matiereSlug = $this->matiere?->name ? Str::limit($this->matiere->name, 25, '') : 'Matiere';

        return Str::ascii($matiereSlug . ' - ' . $this->periodeLabel());
    }

    public function headings(): array
    {
        $headings = [
            'Matricule',
            'Nom & Prénoms',
        ];

        foreach ($this->evaluations as $eval) {
            $bareme = (float) ($eval->bareme ?? 20);
            $coef = (float) ($eval->coefficient ?? 1);
            $titre = $eval->titre ?? ('Eval #' . $eval->id);
            // Format compact "Titre (/20 ×1)" pour caser dans 12 colonnes
            $headings[] = sprintf('%s (/%s ×%s)', $titre, $this->formatNumber($bareme), $this->formatNumber($coef));
        }

        $headings[] = 'Moyenne /20';

        return $headings;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->inscriptions as $inscription) {
            $etu = $inscription->etudiant;
            $row = [
                (string) ($etu->matricule ?? ''),
                trim(($etu->nom ?? '') . ' ' . ($etu->prenoms ?? '')),
            ];

            // Notes par évaluation
            $totalPondere = 0.0;
            $totalCoef = 0.0;
            foreach ($this->evaluations as $eval) {
                $key = $etu->id . '_' . $eval->id;
                $note = $this->notesIndex[$key] ?? null;
                $bareme = max(0.01, (float) ($eval->bareme ?? 20));
                $coef = (float) ($eval->coefficient ?? 1);

                if ($note && $note->is_absent) {
                    $row[] = 'ABS';
                    // Les absences n'entrent pas dans la moyenne
                } elseif ($note) {
                    $val = (float) $note->note;
                    $row[] = $val;
                    $noteSur20 = ($val / $bareme) * 20;
                    $totalPondere += $noteSur20 * $coef;
                    $totalCoef += $coef;
                } else {
                    $row[] = null;
                }
            }

            // Moyenne calculée
            $moyenne = $totalCoef > 0 ? round($totalPondere / $totalCoef, 2) : null;
            $row[] = $moyenne;

            $rows[] = $row;
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 16,  // Matricule
            'B' => 32,  // Nom & Prénoms
        ];

        // Colonnes des évaluations + moyenne
        $col = 3; // Index colonne (1-based)
        foreach ($this->evaluations as $eval) {
            $widths[$this->columnLetter($col + 0)] = 14;
            $col++;
        }
        // Colonne moyenne
        $widths[$this->columnLetter($col)] = 13;

        return $widths;
    }

    public function columnFormatting(): array
    {
        // Matricule en text pour préserver leading zeros
        return [
            'A' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Ajouter une ligne marker en haut (cachée) avec les IDs pour ré-import
                $sheet->insertNewRowBefore(1, 1);
                $sheet->setCellValue('A1', '__KLASSCI_NOTES_EXPORT__');
                $sheet->setCellValue('B1', json_encode([
                    'classe_id' => $this->classeId,
                    'matiere_id' => $this->matiereId,
                    'periode' => $this->periode,
                    'annee_universitaire_id' => $this->anneeUniversitaireId,
                    'evaluations' => $this->evaluations->map(fn ($e) => [
                        'id' => $e->id,
                        'titre' => $e->titre,
                        'bareme' => (float) $e->bareme,
                        'coefficient' => (float) $e->coefficient,
                    ])->values()->all(),
                    'generated_at' => now()->toIso8601String(),
                ], JSON_UNESCAPED_UNICODE));
                $sheet->getRowDimension(1)->setVisible(false);

                $headingRow = 2;
                $highestColumn = $sheet->getHighestDataColumn();
                $highestRow = $sheet->getHighestRow();

                // Style header (ligne 2)
                $headerRange = "A{$headingRow}:{$highestColumn}{$headingRow}";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0F1F4B']],
                    ],
                ]);
                $sheet->getRowDimension($headingRow)->setRowHeight(36);

                $dataStartRow = $headingRow + 1;
                $dataEndRow = $highestRow;

                if ($dataEndRow >= $dataStartRow) {
                    // Style des lignes de données
                    $dataRange = "A{$dataStartRow}:{$highestColumn}{$dataEndRow}";
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'font' => ['size' => 10],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
                        ],
                    ]);

                    // Lignes alternées
                    for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                        if (($row - $dataStartRow) % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                                ->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setRGB('F8FAFC');
                        }
                    }

                    // Centrer matricule + notes + moyenne
                    $sheet->getStyle("A{$dataStartRow}:A{$dataEndRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Notes: centrées
                    $firstEvalCol = 'C';
                    $lastEvalCol = $highestColumn;
                    $sheet->getStyle("{$firstEvalCol}{$dataStartRow}:{$lastEvalCol}{$dataEndRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Colonne moyenne en gras + couleur
                    $sheet->getStyle("{$lastEvalCol}{$dataStartRow}:{$lastEvalCol}{$dataEndRow}")
                        ->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '0453CB']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
                        ]);
                }

                // Freeze pane sur 2 colonnes + header
                $sheet->freezePane('C' . ($headingRow + 1));

                // AutoFilter
                $sheet->setAutoFilter("A{$headingRow}:{$highestColumn}{$headingRow}");

                // Forcer A en text format pour matricule
                $sheet->getStyle("A{$dataStartRow}:A{$dataEndRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_TEXT);

                // Bandeau info en bas
                $infoRow = $dataEndRow + 2;
                $sheet->mergeCells("A{$infoRow}:{$highestColumn}{$infoRow}");
                $school = SettingsHelper::get('school_name', config('app.name'));
                $sheet->setCellValue("A{$infoRow}", sprintf(
                    '%s — %s — %s — %s — %s — Exporté le %s',
                    $school,
                    $this->classe?->name ?? 'Classe',
                    $this->matiere?->name ?? 'Matière',
                    $this->periodeLabel(),
                    $this->annee?->name ?? 'Année courante',
                    now()->format('d/m/Y H:i')
                ));
                $sheet->getStyle("A{$infoRow}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '64748B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // Note d'instructions
                $instructRow = $infoRow + 1;
                $sheet->mergeCells("A{$instructRow}:{$highestColumn}{$instructRow}");
                $sheet->setCellValue("A{$instructRow}",
                    'Saisie offline : tapez la note (0 — barème) ou « ABS » pour absent. Laissez vide pour ne pas modifier. La ligne 1 cachée contient les métadonnées d\'import — ne pas la supprimer.'
                );
                $sheet->getStyle("A{$instructRow}")->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '0453CB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
                ]);
                $sheet->getRowDimension($instructRow)->setRowHeight(20);
            },
        ];
    }

    private function periodeLabel(): string
    {
        return match ($this->periode) {
            'semestre1' => 'Semestre 1',
            'semestre2' => 'Semestre 2',
            default => ucfirst($this->periode),
        };
    }

    private function formatNumber(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    private function columnLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
