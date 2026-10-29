<?php

namespace Database\Factories;

use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Un versement, pour les tests.
 *
 * Par defaut : un encaissement VALIDE, sans type — donc dans le perimetre que
 * la repartition ecrit et que le calcul par frais relit. Les cas qui en sortent
 * (reliquat, avoir, en attente) sont des etats explicites, pour qu'un test qui
 * les exerce le dise dans son propre code.
 *
 * `numero_recu` est unique en base : il est derive d'un identifiant unique, pas
 * du faker, pour ne pas produire d'echec intermittent.
 */
class ESBTPPaiementFactory extends Factory
{
    protected $model = ESBTPPaiement::class;

    public function definition(): array
    {
        return [
            'inscription_id' => ESBTPInscription::factory(),
            'montant' => 10000,
            'mode_paiement' => 'espèces',
            'date_paiement' => now()->toDateString(),
            'status' => 'validé',
            'nature' => 'encaissement',
            'numero_recu' => 'REC-TEST-'.uniqid(),
        ];
    }

    /**
     * Rattache le versement a une inscription et hérite de son etudiant et de
     * son annee : trois colonnes qui doivent rester coherentes entre elles.
     */
    public function pour(ESBTPInscription $inscription): static
    {
        return $this->state(fn () => [
            'inscription_id' => $inscription->id,
            'etudiant_id' => $inscription->etudiant_id,
            'annee_universitaire_id' => $inscription->annee_universitaire_id,
        ]);
    }

    /**
     * Le frais que le caissier a designe. `null` est un etat legitime : la
     * colonne est nullable et sa cle etrangere est en `set null`.
     */
    public function surCategorie(?int $categoryId): static
    {
        return $this->state(fn () => ['frais_category_id' => $categoryId]);
    }

    public function montant(float $montant): static
    {
        return $this->state(fn () => ['montant' => $montant]);
    }

    /**
     * Un versement qui eteint une dette d'une annee anterieure. Hors perimetre
     * du calcul par frais : ce qu'il solde vit dans esbtp_reliquat_details.
     */
    public function reliquat(): static
    {
        return $this->state(fn () => ['type_paiement' => 'reliquat']);
    }

    public function enAttente(): static
    {
        return $this->state(fn () => ['status' => 'en_attente']);
    }
}
