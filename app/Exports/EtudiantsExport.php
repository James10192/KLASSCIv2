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
     * @param string|null $groupBy null|'classe'|'filiere'|'niveau'|'filiere_niveau'
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
        $settings = $this->loadSettings();

        if (! $this->groupBy) {
            return [new EtudiantsSheetExport($this->etudiants, 'Tous les étudiants', $settings)];
        }

        $grouped = $this->etudiants->groupBy(function ($item) {
            $inscription = $item['inscription'] ?? null;

            return match ($this->groupBy) {
                'classe' => $inscription?->classe?->name ?? 'Sans classe',
                'filiere' => $inscription?->filiere?->name ?? 'Sans filière',
                'niveau' => $inscription?->niveau?->name ?? 'Sans niveau',
                'filiere_niveau' => ($inscription?->filiere?->name ?? 'Sans filière').' — '.($inscription?->niveau?->name ?? 'Sans niveau'),
                default => 'Tous',
            };
        });

        $sheets = [];
        foreach ($grouped as $groupName => $items) {
            $sheets[] = new EtudiantsSheetExport(
                $items,
                $groupName.' ('.$items->count().')',
                $settings
            );
        }

        return $sheets;
    }

    private function loadSettings(): array
    {
        return [
            'school_name' => SettingsHelper::get('school_name', config('app.name')),
            'school_address' => SettingsHelper::get('school_address'),
            'school_city' => SettingsHelper::get('school_city'),
            'school_phone' => SettingsHelper::get('school_phone'),
            'school_email' => SettingsHelper::get('school_email'),
        ];
    }
}
