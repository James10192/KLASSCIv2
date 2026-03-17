<?php

namespace App\Exports;

use App\Helpers\SettingsHelper;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EtudiantsExport implements WithMultipleSheets
{
    use Exportable;

    protected Collection $etudiants;
    protected ?string $groupBy;
    protected array $filters;

    /**
     * @param Collection $etudiants Collection of ['etudiant' => ..., 'inscription' => ...]
     * @param string|null $groupBy null|'classe'|'filiere'|'niveau'
     * @param array $filters Active filters for display
     */
    public function __construct(Collection $etudiants, ?string $groupBy = null, array $filters = [])
    {
        $this->etudiants = $etudiants;
        $this->groupBy = $groupBy;
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        if (!$this->groupBy) {
            return [new EtudiantsSheetExport($this->etudiants, 'Tous les étudiants')];
        }

        $grouped = $this->etudiants->groupBy(function ($item) {
            $inscription = $item['inscription'] ?? null;

            return match ($this->groupBy) {
                'classe' => $inscription?->classe?->name ?? 'Sans classe',
                'filiere' => $inscription?->filiere?->name ?? 'Sans filière',
                'niveau' => $inscription?->niveau?->name ?? 'Sans niveau',
                default => 'Tous',
            };
        });

        $sheets = [];
        foreach ($grouped as $groupName => $items) {
            $sheets[] = new EtudiantsSheetExport(
                collect($items),
                $groupName . ' (' . $items->count() . ')'
            );
        }

        return $sheets;
    }
}
