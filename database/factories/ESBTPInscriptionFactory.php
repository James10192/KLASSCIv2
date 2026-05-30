<?php

namespace Database\Factories;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Database\Eloquent\Factories\Factory;

class ESBTPInscriptionFactory extends Factory
{
    protected $model = ESBTPInscription::class;

    public function definition(): array
    {
        return [
            'etudiant_id' => ESBTPEtudiant::factory(),
            'filiere_id' => ESBTPFiliere::factory(),
            'niveau_id' => ESBTPNiveauEtude::factory(),
            'classe_id' => ESBTPClasse::factory(),
            'annee_universitaire_id' => ESBTPAnneeUniversitaire::factory(),
            'date_inscription' => now()->subDays($this->faker->numberBetween(1, 365)),
            'type_inscription' => 'première_inscription',
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'is_sous_reserve' => false,
            'affectation_status' => 'affecté',
            'comptabilite_activee' => false,
            'montant_scolarite' => 0,
            'frais_inscription' => 0,
        ];
    }
}
