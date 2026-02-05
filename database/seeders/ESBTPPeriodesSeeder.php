<?php

namespace Database\Seeders;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPPeriode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * ESBTPPeriodesSeeder
 *
 * Crée 2 périodes par défaut (Semestre 1 et Semestre 2) pour chaque année universitaire existante.
 *
 * Garantit que toutes les données actuelles (basées sur semestre1/semestre2) pourront être
 * migrées vers le nouveau système de periode_id.
 *
 * Règles:
 * - Chaque année universitaire a 2 périodes (ordre: 1, 2)
 * - Poids par défaut: 1 (moyenne équipondérée)
 * - Dates automatiques basées sur annee_universitaire.date_debut/date_fin
 *   - Semestre 1: date_debut → mi-année
 *   - Semestre 2: mi-année → date_fin
 * - is_active: true par défaut
 */
class ESBTPPeriodesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('🌱 Démarrage du seeding des périodes académiques...');

        $anneesUniversitaires = ESBTPAnneeUniversitaire::all();

        if ($anneesUniversitaires->isEmpty()) {
            Log::warning('⚠️  Aucune année universitaire trouvée. Seeding ignoré.');
            return;
        }

        foreach ($anneesUniversitaires as $annee) {
            $this->createPeriodesForAnnee($annee);
        }

        $totalPeriodes = ESBTPPeriode::count();
        Log::info("✅ {$totalPeriodes} périodes académiques créées avec succès.");
    }

    /**
     * Créer 2 périodes (Semestre 1 et Semestre 2) pour une année universitaire.
     *
     * @param ESBTPAnneeUniversitaire $annee
     * @return void
     */
    private function createPeriodesForAnnee(ESBTPAnneeUniversitaire $annee): void
    {
        Log::info("📅 Création des périodes pour l'année universitaire: {$annee->annee}");

        // Calculer les dates de début/fin des semestres
        $dateDebut = \Carbon\Carbon::parse($annee->date_debut);
        $dateFin = \Carbon\Carbon::parse($annee->date_fin);

        // Milieu de l'année (approximatif)
        $milieuAnnee = $dateDebut->copy()->addMonths(6);

        // Vérifier si les périodes existent déjà (idempotence)
        $existingCount = ESBTPPeriode::where('annee_universitaire_id', $annee->id)->count();

        if ($existingCount >= 2) {
            Log::info("  ℹ️  Les périodes existent déjà pour cette année universitaire. Ignoré.");
            return;
        }

        // Créer Semestre 1
        $periode1 = ESBTPPeriode::create([
            'annee_universitaire_id' => $annee->id,
            'nom' => 'Semestre 1',
            'ordre' => 1,
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $milieuAnnee->format('Y-m-d'),
            'poids' => 1,
            'is_active' => true,
        ]);

        Log::info("  ✅ Semestre 1 créé (ID: {$periode1->id}) - {$periode1->date_debut} à {$periode1->date_fin}");

        // Créer Semestre 2
        $periode2 = ESBTPPeriode::create([
            'annee_universitaire_id' => $annee->id,
            'nom' => 'Semestre 2',
            'ordre' => 2,
            'date_debut' => $milieuAnnee->copy()->addDay()->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'poids' => 1,
            'is_active' => true,
        ]);

        Log::info("  ✅ Semestre 2 créé (ID: {$periode2->id}) - {$periode2->date_debut} à {$periode2->date_fin}");
    }
}
