<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Fiche de notes Excel d'une classe pour un semestre : une feuille par
 * matière, dans le format que l'import relit. Une matière sans évaluation
 * reçoit une colonne « Examen », créée au réimport.
 */
class NotesClasseurExport implements WithMultipleSheets
{
    public function __construct(private int $classeId, private Collection $matieres, private string $periode, private ?int $anneeId) {}

    public function sheets(): array
    {
        $titres = [];

        return $this->matieres->map(function ($matiere) use (&$titres) {
            $base = mb_substr(preg_replace('/[\\\/\?\*\[\]:]/', ' ', (string) ($matiere->code ?: $matiere->name)), 0, 28);
            $titre = $base;
            for ($i = 2; isset($titres[mb_strtolower($titre)]); $i++) {
                $titre = mb_substr($base, 0, 26).' '.$i;
            }
            $titres[mb_strtolower($titre)] = true;

            return (new NotesClasseMatiereExport($this->classeId, (int) $matiere->id, $this->periode, $this->anneeId, true))->avecTitre($titre);
        })->values()->all();
    }
}
