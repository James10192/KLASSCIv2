<?php

namespace Tests\Feature\Performance\Concerns;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Une classe BTS notee, avec autant d'eleves qu'on veut : la donnee qui
 * grandit sur une ecole de 2000 inscrits, et que les tests de compte de
 * requetes font varier pour verifier que le nombre de requetes, lui, ne
 * bouge pas.
 */
trait ConstruitUneClasseNotee
{
    /**
     * @return array{classe: ESBTPClasse, etudiants: Collection<int, ESBTPEtudiant>, matieres: Collection<int, ESBTPMatiere>}
     */
    protected function classeNotee(ESBTPAnneeUniversitaire $annee, int $eleves, ?ESBTPClasse $classe = null): array
    {
        $classe ??= $this->classeBts();
        $matieres = collect([ESBTPMatiere::factory()->create(), ESBTPMatiere::factory()->create()]);

        $evaluations = collect();
        foreach ($matieres as $matiere) {
            foreach (['semestre1', 'semestre2'] as $periode) {
                $evaluations->push(ESBTPEvaluation::factory()->create([
                    'matiere_id' => $matiere->id,
                    'classe_id' => $classe->id,
                    'annee_universitaire_id' => $annee->id,
                    'periode' => $periode,
                    'coefficient' => 1,
                    'bareme' => 20,
                    'status' => 'completed',
                ]));
            }
        }

        $etudiants = collect();
        for ($i = 0; $i < $eleves; $i++) {
            $etudiant = ESBTPEtudiant::factory()->create();
            $this->inscrire($etudiant, $classe, $annee);
            foreach ($evaluations as $rang => $evaluation) {
                // Des notes differentes d'un eleve a l'autre : le rang en depend.
                ESBTPNote::factory()->create([
                    'evaluation_id' => $evaluation->id,
                    'etudiant_id' => $etudiant->id,
                    'matiere_id' => $evaluation->matiere_id,
                    'classe_id' => $classe->id,
                    'annee_universitaire' => $annee->name,
                    'note' => $n = 6 + (($i * 3 + $rang) % 13),
                    'valeur' => $n,
                    'is_absent' => false,
                ]);
            }
            $etudiants->push($etudiant);
        }

        return ['classe' => $classe, 'etudiants' => $etudiants, 'matieres' => $matieres];
    }

    protected function classeBts(): ESBTPClasse
    {
        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        return ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'is_active' => true,
            'systeme_academique' => 'BTS',
        ]);
    }

    protected function inscrire(ESBTPEtudiant $etudiant, ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee): ESBTPInscription
    {
        return ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);
    }

    /**
     * Le nombre de requetes SQL que coute une operation.
     */
    protected function requetesDe(callable $operation): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $operation();
        } finally {
            $compte = count(DB::getQueryLog());
            DB::disableQueryLog();
        }

        return $compte;
    }
}
