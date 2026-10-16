<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/** Adaptateur du contrat commun d'export ; l'interface n'expose que le PDF. */
class ClasseEffectifsExport implements FromCollection
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [
            'Classe' => $row->name,
            'Effectif' => $row->effectif,
            'Filière / mention' => $row->filiere,
            'Niveau' => $row->niveau,
        ]);
    }
}
