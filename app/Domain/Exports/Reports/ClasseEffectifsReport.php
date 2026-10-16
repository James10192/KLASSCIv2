<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use App\Exports\ClasseEffectifsExport;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;

class ClasseEffectifsReport extends ExportableReport
{
    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data, private readonly array $labels)
    {
    }

    public function title(): string
    {
        return 'Rapport des effectifs par classe';
    }

    public function subtitle(): ?string
    {
        return 'Élèves inscrits et validés, effectifs distincts par classe';
    }

    public function pdfView(): string
    {
        return 'esbtp.rapports.effectifs-pdf';
    }

    public function viewData(): array
    {
        return ['report' => $this->data];
    }

    public function filters(): array
    {
        return $this->labels;
    }

    public function excelExport(): FromCollection
    {
        return new ClasseEffectifsExport($this->data['rows']);
    }

    /**
     * Un nouveau dossier ou des couleurs tenant changées doivent invalider
     * immédiatement le PDF ; la clé par défaut ne contient que les filtres.
     */
    public function cacheKey(): string
    {
        return hash('sha256', serialize([
            'base' => parent::cacheKey(),
            'database' => DB::connection()->getDatabaseName(),
            'school' => SettingsHelper::getSchoolInfo(),
            'theme' => SettingsHelper::getPdfSettings(),
            'rows' => $this->data['rows']->map(fn ($row) => [
                $row->id, $row->name, $row->effectif, $row->filiere, $row->niveau,
            ])->all(),
        ]));
    }
}
