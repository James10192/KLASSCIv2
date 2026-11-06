<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use App\Exports\SituationClassesExport;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;

final class SituationClassesReport extends ExportableReport
{
    /** @param array<string, mixed> $rapport */
    public function __construct(private readonly array $rapport, private readonly array $labels)
    {
    }

    public function title(): string
    {
        return 'Situation financière par classe';
    }

    public function subtitle(): ?string
    {
        return 'Prévisionnel, règlements, reste à recouvrer et échéances échues';
    }

    public function pdfView(): string
    {
        return 'esbtp.comptabilite.suivi-classes.pdf';
    }

    public function viewData(): array
    {
        return ['rapport' => $this->rapport];
    }

    public function excelExport(): FromCollection
    {
        return new SituationClassesExport($this->rapport, $this->labels);
    }

    public function filters(): array
    {
        return $this->labels;
    }

    public function orientation(): string
    {
        return 'landscape';
    }

    public function cacheKey(): string
    {
        return hash('sha256', serialize([
            parent::cacheKey(),
            DB::connection()->getDatabaseName(),
            SettingsHelper::getSchoolInfo(),
            SettingsHelper::getPdfSettings(),
            $this->rapport['classes']->map(fn ($c) => [
                $c['classe_id'], $c['attendu'], $c['regle'], $c['encaisse'],
                $c['ajustements'], $c['restant'], $c['echu'], $c['effectif'],
            ])->all(),
        ]));
    }
}
