<?php

namespace Database\Seeders;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultatMatiere;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ESBTPBulletinSeeder extends Seeder
{
    public function run()
    {
        $anneeUniversitaire = ESBTPAnneeUniversitaire::first();
        $classes = ESBTPClasse::all();
        $periodes = ['semestre1', 'semestre2'];

        foreach ($classes as $classe) {
            $etudiants = ESBTPEtudiant::where('classe_id', $classe->id)->get();
            $effectif = $etudiants->count();

            foreach ($periodes as $periode) {
                foreach ($etudiants as $etudiant) {
                    // Créer le bulletin
                    $bulletin = ESBTPBulletin::create([
                        'etudiant_id' => $etudiant->id,
                        'classe_id' => $classe->id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $anneeUniversitaire->id,
                        'effectif_classe' => $effectif,
                        'created_by' => 1,
                        'updated_by' => 1
                    ]);

                    // Calculer les résultats par matière
                    $this->calculerResultatsMatiere($bulletin, $periode);

                    // Calculer la moyenne générale et le rang
                    $this->calculerMoyenneGenerale($bulletin);
                    $this->calculerRang($bulletin);
                }
            }
        }
    }

    private function calculerResultatsMatiere($bulletin, $periode)
    {
        $matieres = $bulletin->classe->matieres;

        foreach ($matieres as $matiere) {
            $notes = ESBTPNote::whereHas('evaluation', function ($query) use ($matiere, $periode) {
                $query->where('matiere_id', $matiere->id)
                    ->where('periode', $periode);
            })->where('etudiant_id', $bulletin->etudiant_id)->get();

            if ($notes->isNotEmpty()) {
                $moyenne = $this->calculerMoyenneMatiere($notes);
                $rang = $this->calculerRangMatiere($bulletin->etudiant_id, $matiere->id, $periode);

                ESBTPResultatMatiere::create([
                    'bulletin_id' => $bulletin->id,
                    'matiere_id' => $matiere->id,
                    'moyenne' => $moyenne,
                    'coefficient' => $matiere->coefficient,
                    'rang' => $rang,
                    'appreciation' => $this->determinerAppreciation($moyenne),
                    'created_by' => 1,
                    'updated_by' => 1
                ]);
            }
        }
    }

    private function calculerMoyenneMatiere($notes)
    {
        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($notes as $note) {
            $totalPoints += $note->valeur * $note->evaluation->coefficient;
            $totalCoefficients += $note->evaluation->coefficient;
        }

        return $totalCoefficients > 0 ? round($totalPoints / $totalCoefficients, 2) : 0;
    }

    private function calculerRangMatiere($etudiantId, $matiereId, $periode)
    {
        $moyennes = DB::select("
            SELECT etudiant_id, AVG(valeur * e.coefficient) / SUM(e.coefficient) as moyenne
            FROM esbtp_notes n
            JOIN esbtp_evaluations e ON n.evaluation_id = e.id
            WHERE e.matiere_id = ? AND e.periode = ?
            GROUP BY etudiant_id
            ORDER BY moyenne DESC
        ", [$matiereId, $periode]);

        foreach ($moyennes as $index => $moyenne) {
            if ($moyenne->etudiant_id == $etudiantId) {
                return $index + 1;
            }
        }

        return null;
    }

    private function calculerMoyenneGenerale($bulletin)
    {
        $resultats = $bulletin->resultatsMatiere;
        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($resultats as $resultat) {
            $totalPoints += $resultat->moyenne * $resultat->coefficient;
            $totalCoefficients += $resultat->coefficient;
        }

        $moyenneGenerale = $totalCoefficients > 0 ? round($totalPoints / $totalCoefficients, 2) : 0;
        $bulletin->moyenne_generale = $moyenneGenerale;
        $bulletin->mention = $this->determinerMention($moyenneGenerale);
        $bulletin->decision = $this->determinerDecision($moyenneGenerale);
        $bulletin->save();
    }

    private function calculerRang($bulletin)
    {
        $rangs = DB::select("
            SELECT id, moyenne_generale,
                   RANK() OVER (ORDER BY moyenne_generale DESC) as rang
            FROM esbtp_bulletins
            WHERE classe_id = ? AND periode = ? AND annee_universitaire_id = ?
        ", [$bulletin->classe_id, $bulletin->periode, $bulletin->annee_universitaire_id]);

        foreach ($rangs as $rang) {
            if ($rang->id == $bulletin->id) {
                $bulletin->rang = $rang->rang;
                $bulletin->save();
                break;
            }
        }
    }

    private function determinerAppreciation($moyenne)
    {
        if ($moyenne >= 16) {
            return 'Excellent';
        } elseif ($moyenne >= 14) {
            return 'Très Bien';
        } elseif ($moyenne >= 12) {
            return 'Bien';
        } elseif ($moyenne >= 10) {
            return 'Passable';
        } else {
            return 'Insuffisant';
        }
    }

    private function determinerMention($moyenne)
    {
        if ($moyenne >= 16) {
            return 'Excellent';
        } elseif ($moyenne >= 14) {
            return 'Très Bien';
        } elseif ($moyenne >= 12) {
            return 'Bien';
        } elseif ($moyenne >= 10) {
            return 'Passable';
        } else {
            return 'Insuffisant';
        }
    }

    private function determinerDecision($moyenne)
    {
        return $moyenne >= 10 ? 'Admis' : 'Ajourné';
    }
}
