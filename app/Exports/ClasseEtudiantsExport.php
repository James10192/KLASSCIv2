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
    protected $etablissement;

    public function __construct($classe, $etudiants, $anneeCourante, $etablissement = null)
    {
        $this->classe = $classe;
        $this->etudiants = $etudiants;
        $this->anneeCourante = $anneeCourante;
        $this->etablissement = $etablissement;
    }

    /**
     * Résout le parent/tuteur principal depuis la relation déjà chargée
     * (évite un N+1 via l'accesseur getTuteurAttribute). Priorité au tuteur.
     */
    private function resolveParent($etudiant)
    {
        $parents = $etudiant->relationLoaded('parents') ? $etudiant->parents : collect();

        if ($parents->isEmpty()) {
            return null;
        }

        return $parents->first(fn ($p) => (bool) optional($p->pivot)->is_tuteur) ?? $parents->first();
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
        $h = [
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

        if ($this->canIncludeAccessibility()) {
            $h[] = 'Accessibilité';
            $h[] = 'Aménagements';
        }

        return $h;
    }

    private function canIncludeAccessibility(): bool
    {
        $user = auth()->user();
        return $user !== null && $user->can('students.accessibility.export');
    }

    /**
     * Mapper chaque étudiant vers une ligne du fichier Excel
     */
    public function map($etudiant): array
    {
        static $index = 0;
        $index++;

        $parent = $this->resolveParent($etudiant);

        $row = [
            $index,
            $etudiant->matricule ?? 'N/A',
            $etudiant->nom ?? '',
            $etudiant->prenoms ?? '',
            $etudiant->sexe ?? 'N/A',
            $etudiant->date_naissance ? \Carbon\Carbon::parse($etudiant->date_naissance)->format('d/m/Y') : 'N/A',
            $etudiant->lieu_naissance ?? 'N/A',
            $etudiant->telephone ?? 'N/A',
            $etudiant->email_personnel ?: 'N/A',
            $etudiant->adresse ?? 'N/A',
            $parent ? ($parent->nom ?? 'N/A') : 'N/A',
            $parent ? ($parent->prenoms ?? 'N/A') : 'N/A',
            $parent ? ($parent->telephone ?? 'N/A') : 'N/A',
            $parent ? ($parent->email ?? 'N/A') : 'N/A',
            $parent ? ($parent->profession ?? 'N/A') : 'N/A',
            'Actif', // Statut inscription (la requête ne remonte que les inscriptions actives)
            $etudiant->created_at ? $etudiant->created_at->format('d/m/Y') : 'N/A',
            $this->classe->name,
            $this->classe->filiere ? $this->classe->filiere->name : 'N/A',
            $this->classe->niveau ? $this->classe->niveau->name : 'N/A',
            $this->anneeCourante ? $this->anneeCourante->name : 'N/A'
        ];

        if ($this->canIncludeAccessibility()) {
            $profile = $etudiant->accessibilityProfile ?? null;
            $row[] = $profile ? $profile->summaryBadge() : '—';
            $row[] = $profile ? implode(', ', $profile->accommodationLabels()) : '—';
        }

        return $row;
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