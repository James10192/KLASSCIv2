<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClasseEtudiantsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $classe;
    protected $etudiants;
    protected $anneeCourante;

    public function __construct($classe, $etudiants, $anneeCourante)
    {
        $this->classe = $classe;
        $this->etudiants = $etudiants;
        $this->anneeCourante = $anneeCourante;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->etudiants;
    }

    /**
     * Définir les en-têtes de colonnes
     */
    public function headings(): array
    {
        return [
            'N°',
            'Matricule',
            'Nom',
            'Prénom',
            'Sexe',
            'Date de naissance',
            'Lieu de naissance',
            'Téléphone',
            'Email',
            'Adresse',
            'Nom du parent/tuteur',
            'Prénom du parent/tuteur',
            'Téléphone parent',
            'Email parent',
            'Profession parent',
            'Statut inscription',
            'Date inscription',
            'Classe',
            'Filière',
            'Niveau',
            'Année universitaire'
        ];
    }

    /**
     * Mapper chaque étudiant vers une ligne du fichier Excel
     */
    public function map($etudiant): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $etudiant->matricule ?? 'N/A',
            $etudiant->nom ?? '',
            $etudiant->prenom ?? '',
            $etudiant->sexe ?? 'N/A',
            $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'N/A',
            $etudiant->lieu_naissance ?? 'N/A',
            $etudiant->telephone ?? 'N/A',
            $etudiant->email ?? 'N/A',
            $etudiant->adresse ?? 'N/A',
            $etudiant->parent ? $etudiant->parent->nom : 'N/A',
            $etudiant->parent ? $etudiant->parent->prenom : 'N/A',
            $etudiant->parent ? $etudiant->parent->telephone : 'N/A',
            $etudiant->parent ? $etudiant->parent->email : 'N/A',
            $etudiant->parent ? $etudiant->parent->profession : 'N/A',
            'Actif', // Statut inscription (tous sont actifs dans cette liste)
            $etudiant->created_at ? $etudiant->created_at->format('d/m/Y') : 'N/A',
            $this->classe->name,
            $this->classe->filiere ? $this->classe->filiere->name : 'N/A',
            $this->classe->niveau ? $this->classe->niveau->name : 'N/A',
            $this->anneeCourante ? $this->anneeCourante->name : 'N/A'
        ];
    }

    /**
     * Titre de la feuille Excel
     */
    public function title(): string
    {
        return 'Liste ' . \Str::limit($this->classe->name, 20);
    }

    /**
     * Styles pour la feuille Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style pour l'en-tête
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFE2E3E5',
                    ],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }
}