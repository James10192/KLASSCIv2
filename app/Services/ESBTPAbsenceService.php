<?php

namespace App\Services;

use App\Models\ESBTPAbsence;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPClasse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ESBTPAbsenceService
{
    /**
     * Enregistrer une absence
     */
    public function enregistrerAbsence(array $data)
    {
        return DB::transaction(function () use ($data) {
            return ESBTPAbsence::create([
                'etudiant_id' => $data['etudiant_id'],
                'matiere_id' => $data['matiere_id'],
                'date_absence' => $data['date_absence'],
                'nombre_heures' => $data['nombre_heures'],
                'motif' => $data['motif'] ?? null,
                'est_justifiee' => $data['est_justifiee'] ?? false,
                'justification' => $data['justification'] ?? null,
                'date_justification' => $data['est_justifiee'] ? now() : null,
                'periode' => $data['periode'],
                'annee_universitaire_id' => $data['annee_universitaire_id'],
                'created_by' => auth()->id()
            ]);
        });
    }

    /**
     * Justifier une absence
     */
    public function justifierAbsence(ESBTPAbsence $absence, string $justification)
    {
        return DB::transaction(function () use ($absence, $justification) {
            $absence->update([
                'est_justifiee' => true,
                'justification' => $justification,
                'date_justification' => now(),
                'updated_by' => auth()->id()
            ]);
            return $absence;
        });
    }

    /**
     * Annuler une justification d'absence
     */
    public function annulerJustification(ESBTPAbsence $absence)
    {
        return DB::transaction(function () use ($absence) {
            $absence->update([
                'est_justifiee' => false,
                'justification' => null,
                'date_justification' => null,
                'updated_by' => auth()->id()
            ]);
            return $absence;
        });
    }

    /**
     * Calculer le total des absences pour un étudiant sur une période
     */
    public function calculerTotalAbsences(ESBTPEtudiant $etudiant, $periode, $anneeUniversitaireId)
    {
        $absences = ESBTPAbsence::where('etudiant_id', $etudiant->id)
            ->where('periode', $periode)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->get();

        return [
            'total' => $absences->sum('nombre_heures'),
            'justifiees' => $absences->where('est_justifiee', true)->sum('nombre_heures'),
            'non_justifiees' => $absences->where('est_justifiee', false)->sum('nombre_heures')
        ];
    }

    /**
     * Calculer le pourcentage d'absences par matière
     */
    public function calculerPourcentageAbsencesParMatiere(ESBTPEtudiant $etudiant, ESBTPMatiere $matiere, $periode, $anneeUniversitaireId)
    {
        $totalHeuresMatiere = $matiere->heures_cm + $matiere->heures_td + $matiere->heures_tp;

        $totalAbsences = ESBTPAbsence::where('etudiant_id', $etudiant->id)
            ->where('matiere_id', $matiere->id)
            ->where('periode', $periode)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->sum('nombre_heures');

        if ($totalHeuresMatiere === 0) {
            return 0;
        }

        return round(($totalAbsences / $totalHeuresMatiere) * 100, 2);
    }

    /**
     * Générer un rapport d'absences pour une classe
     */
    public function genererRapportClasse(ESBTPClasse $classe, $periode, $anneeUniversitaireId)
    {
        $rapport = [];
        $etudiants = $classe->etudiants;
        $matieres = $classe->matieres;

        foreach ($etudiants as $etudiant) {
            $absencesEtudiant = [
                'etudiant' => $etudiant->nom_complet,
                'matricule' => $etudiant->matricule,
                'total_absences' => 0,
                'absences_justifiees' => 0,
                'absences_non_justifiees' => 0,
                'matieres' => []
            ];

            foreach ($matieres as $matiere) {
                $absencesMatiere = ESBTPAbsence::where('etudiant_id', $etudiant->id)
                    ->where('matiere_id', $matiere->id)
                    ->where('periode', $periode)
                    ->where('annee_universitaire_id', $anneeUniversitaireId)
                    ->get();

                $totalMatiere = $absencesMatiere->sum('nombre_heures');
                $justifieesMatiere = $absencesMatiere->where('est_justifiee', true)->sum('nombre_heures');
                $nonJustifieesMatiere = $absencesMatiere->where('est_justifiee', false)->sum('nombre_heures');

                $absencesEtudiant['matieres'][$matiere->name] = [
                    'total' => $totalMatiere,
                    'justifiees' => $justifieesMatiere,
                    'non_justifiees' => $nonJustifieesMatiere,
                    'pourcentage' => $this->calculerPourcentageAbsencesParMatiere($etudiant, $matiere, $periode, $anneeUniversitaireId)
                ];

                $absencesEtudiant['total_absences'] += $totalMatiere;
                $absencesEtudiant['absences_justifiees'] += $justifieesMatiere;
                $absencesEtudiant['absences_non_justifiees'] += $nonJustifieesMatiere;
            }

            $rapport[] = $absencesEtudiant;
        }

        return $rapport;
    }
}
