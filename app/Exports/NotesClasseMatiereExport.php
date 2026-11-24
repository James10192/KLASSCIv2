<?php

namespace App\Exports;

use App\Domain\Exports\GabaritExcelEtablissement;
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

    /** Fiche vierge : une matière sans évaluation reçoit une colonne « Examen », créée à l'import. */
    private bool $colonneExamenSiVide;

    /** Nombre minimal de colonnes de notes ; les colonnes ajoutées deviennent des évaluations à l'import. */
    private int $colonnes;

    /** Orientation d'impression : 'portrait' ou 'landscape' (FormatFeuilleViergeA4). */
    private string $orientation;

    public function __construct(int $classeId, int $matiereId, string $periode, ?int $anneeUniversitaireId = null, bool $colonneExamenSiVide = false, int $colonnes = 1, string $orientation = 'portrait')
    {
        $this->colonnes = max(1, $colonnes);
        $this->orientation = $orientation === 'landscape' ? 'landscape' : 'portrait';
        $this->colonneExamenSiVide = $colonneExamenSiVide;
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

        $this->completerColonnes();

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
        $evalIds = $this->evaluations->pluck('id')->filter()->all();
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
     * Complète jusqu'au nombre de colonnes demandé avec des colonnes vierges,
     * créées comme évaluations à l'import si elles portent des notes. Une
     * matière qui a déjà plus d'évaluations les garde toutes.
     */
    private function completerColonnes(): void
    {
        $cible = $this->evaluations->isEmpty() && ! $this->colonneExamenSiVide && $this->colonnes === 1 ? 0 : $this->colonnes;
        $pris = $this->evaluations->map(fn ($e) => Str::lower(trim((string) $e->titre)))->all();
        $rang = $this->evaluations->count();
        while ($this->evaluations->count() < $cible) {
            $rang++;
            $titre = $this->evaluations->isEmpty() ? 'Examen' : 'Évaluation '.$rang;
            while (in_array(Str::lower($titre), $pris, true)) {
                $titre = 'Évaluation '.(++$rang);
            }
            $pris[] = Str::lower($titre);
            $this->evaluations->push(new ESBTPEvaluation(['titre' => $titre, 'bareme' => 20, 'coefficient' => 1]));
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

    private ?string $titreFeuille = null;

    /** Titre de feuille imposé par un classeur (31 caractères au plus, unique). */
    public function avecTitre(string $titre): self
    {
        $this->titreFeuille = $titre;

        return $this;
    }

    public function title(): string
    {
        if ($this->titreFeuille !== null) {
            return $this->titreFeuille;
        }

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

    public function columnFormats(): array
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
                    'orientation' => $this->orientation,
                    'evaluations' => $this->evaluations->map(fn ($e) => [
                        'id' => (int) $e->id,
                        'nouvelle' => ! $e->id,
                        'titre' => $e->titre,
                        'bareme' => (float) $e->bareme,
                        'coefficient' => (float) $e->coefficient,
                    ])->values()->all(),
                    'generated_at' => now()->toIso8601String(),
                ], JSON_UNESCAPED_UNICODE));
                $sheet->getRowDimension(1)->setVisible(false);

                $highestColumn = $sheet->getHighestDataColumn();
                // Bandeau de l'établissement entre la ligne cachée et l'en-tête :
                // l'import retrouve l'en-tête à son libellé « Matricule ».
                $gabarit = new GabaritExcelEtablissement();
                $headingRow = $gabarit->poserBandeau($sheet, 2, $highestColumn,
                    'Fiche de notes — '.($this->matiere?->name ?? 'Matière'),
                    sprintf('%s · %s · %s', $this->classe?->name ?? 'Classe', $this->periodeLabel(), $this->annee?->name ?? 'Année courante'));
                $highestRow = $sheet->getHighestRow();
                $primaire = $gabarit->couleur('primary_color', '#0453cb');

                $gabarit->styleEntete($sheet, "A{$headingRow}:{$highestColumn}{$headingRow}");
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
                            'font' => ['bold' => true, 'color' => ['rgb' => $primaire]],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
                        ]);
                }

                // Contrôle de saisie : un nombre entre 0 et le barème, ou ABS.
                foreach ($this->evaluations->values() as $i => $eval) {
                    $col = $this->columnLetter(3 + $i);
                    $bareme = $this->formatNumber((float) ($eval->bareme ?: 20));
                    // Colonne à créer : son barème peut être changé dans l'en-tête,
                    // le plafond est donc vérifié à l'import, pas dans Excel.
                    for ($r = $dataStartRow; $r <= $dataEndRow; $r++) {
                        $plafond = $eval->id ? ",{$col}{$r}<={$bareme}" : '';
                        $validation = $sheet->getCell("{$col}{$r}")->getDataValidation();
                        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_CUSTOM)
                            ->setFormula1("OR(UPPER({$col}{$r})=\"ABS\",AND(ISNUMBER({$col}{$r}),{$col}{$r}>=0{$plafond}))")
                            ->setAllowBlank(true)->setShowErrorMessage(true)
                            ->setErrorTitle('Note invalide')->setError("Tapez une note entre 0 et {$bareme}, ou ABS.");
                    }
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
                    'Saisie hors ligne : tapez la note (0 — barème) ou « ABS » pour absent, vide pour ne rien modifier. Une colonne « Évaluation n » remplie devient une évaluation à l\'import : vous pouvez renommer son en-tête, par exemple « Devoir 2 (/20 ×2) ». Ne supprimez pas la ligne 1 cachée.'
                );
                $sheet->getStyle("A{$instructRow}")->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => $primaire]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
                ]);
                $sheet->getRowDimension($instructRow)->setRowHeight(32);

                $gabarit->miseEnPage($sheet, $this->orientation, $headingRow);
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
