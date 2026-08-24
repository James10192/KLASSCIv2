<?php

namespace Tests\Feature\Bts\Concerns;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\User;

/**
 * L'echafaudage commun aux tests du pre-controle BTS : une classe de tronc
 * commun sur une annee, et de quoi y poser des matieres, des notes et des
 * etudiants inscrits.
 *
 * Ce que chaque test MET EN SCENE lui reste propre : c'est son sujet, il doit
 * se lire sur place. Seul le decor est partage.
 */
trait MonteUneClasseBts
{
    protected ESBTPAnneeUniversitaire $annee;

    protected ESBTPNiveauEtude $niveau;

    protected ESBTPFiliere $filiere;

    protected ESBTPClasse $classe;

    protected function monterLaClasse(): void
    {
        // La fabrique d'evaluation signe `created_by = 1` en dur. L'identifiant
        // est donc impose : l'auto-increment ne repart pas de un entre deux
        // tests, et un auteur cree librement porterait le numero suivant.
        User::factory()->create(['id' => 1]);

        $this->annee = ESBTPAnneeUniversitaire::factory()->create();
        $this->niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $this->filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null]);
        $this->classe = ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
    }

    /** Une matiere du semestre, retenue au bulletin et coefficientee. */
    protected function matiereConfiguree(): ESBTPMatiere
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        ESBTPConfigMatiere::create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'config' => ['type' => 'general', 'coefficient' => 2],
        ]);

        ESBTPMatiereCoefficient::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'coefficient' => 2,
        ]);

        return $matiere;
    }

    protected function evaluationDe(ESBTPMatiere $matiere): ESBTPEvaluation
    {
        return ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'status' => 'published',
            'bareme' => 20,
            'coefficient' => 1,
        ]);
    }

    protected function etudiantInscrit(): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        return $etudiant;
    }

    /**
     * Note posee sans la fabrique : celle-ci ecrit une colonne `observation`
     * que la table ne porte plus.
     *
     * `esbtp_notes.classe_id` est denormalisee : elle doit suivre la classe de
     * l'evaluation, sinon le decor ment sur l'etat des donnees et un test qui
     * s'appuierait dessus virerait au faux vert.
     */
    protected function noter(ESBTPEtudiant $etudiant, ESBTPEvaluation $evaluation, float $note = 13, ?ESBTPClasse $classe = null): void
    {
        ESBTPNote::create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $evaluation->matiere_id,
            'classe_id' => ($classe ?? $this->classe)->id,
            'note' => $note,
            'is_absent' => false,
        ]);
    }
}
