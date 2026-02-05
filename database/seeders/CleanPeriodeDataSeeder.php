<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CleanPeriodeDataSeeder
 *
 * Nettoie et normalise les données de périodes/semestres AVANT la migration vers periode_id.
 *
 * Objectif: Uniformiser tous les formats (INT, VARCHAR avec variantes) vers 'semestre1'/'semestre2'
 * pour garantir une migration propre sans perte de données.
 *
 * Formats détectés en production (dumps esbtp-abidjan + esbtp-yakro):
 * - esbtp_emploi_temps.semestre: 'Semestre 1', 'Semestre 2' (VARCHAR)
 * - esbtp_evaluations.periode: 'semestre1', 'semestre2' (VARCHAR)
 * - esbtp_notes.semestre: '1', '2' (VARCHAR - converti par auto-sync bug)
 * - esbtp_planifications_academiques.semestre: 1, 2 (INT)
 * - esbtp_config_matieres.semestre: 1, 2 (INT)
 * - esbtp_config_matiere_type_formations.semestre: 1, 2 (INT)
 * - esbtp_resultats.periode: Format inconnu (table vide en prod)
 * - esbtp_bulletins.periode: Format inconnu (vérification nécessaire)
 *
 * Normalisation cible:
 * - 'Semestre 1' → 'semestre1'
 * - 'Semestre 2' → 'semestre2'
 * - '1' → 'semestre1'
 * - '2' → 'semestre2'
 * - 1 (INT) → 'semestre1'
 * - 2 (INT) → 'semestre2'
 */
class CleanPeriodeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('🧹 Démarrage du nettoyage des données de périodes...');

        $this->checkEmploiTemps();
        $this->checkEvaluations();
        $this->checkNotes();
        $this->checkPlanificationsAcademiques();
        $this->checkConfigMatieres();
        $this->checkConfigMatiereTypeFormations();
        $this->checkResultats();
        $this->checkBulletins();

        Log::info('✅ Nettoyage des données de périodes terminé avec succès.');
    }

    /**
     * Normaliser esbtp_emploi_temps.semestre (VARCHAR)
     * Format détecté: 'Semestre 1', 'Semestre 2'
     */
    private function checkEmploiTemps(): void
    {
        Log::info('📋 Vérification esbtp_emploi_temps...');

        // Semestre 1
        $updatedS1 = DB::table('esbtp_emploi_temps')
            ->where('semestre', 'Semestre 1')
            ->update(['semestre' => 'semestre1']);

        // Semestre 2
        $updatedS2 = DB::table('esbtp_emploi_temps')
            ->where('semestre', 'Semestre 2')
            ->update(['semestre' => 'semestre2']);

        Log::info("  → Semestre 1: {$updatedS1} lignes normalisées");
        Log::info("  → Semestre 2: {$updatedS2} lignes normalisées");
    }

    /**
     * Normaliser esbtp_evaluations.periode (VARCHAR)
     * Format détecté: 'semestre1', 'semestre2' (déjà OK normalement)
     */
    private function checkEvaluations(): void
    {
        Log::info('📋 Vérification esbtp_evaluations...');

        // Vérifier s'il y a des formats non standards
        $nonStandard = DB::table('esbtp_evaluations')
            ->whereNotNull('periode')
            ->whereNotIn('periode', ['semestre1', 'semestre2'])
            ->get();

        if ($nonStandard->count() > 0) {
            Log::warning("  ⚠️  {$nonStandard->count()} évaluations avec format non standard détectées:");
            foreach ($nonStandard as $eval) {
                Log::warning("     - ID {$eval->id}: periode='{$eval->periode}'");
            }

            // Tentative de normalisation (1 → semestre1, 2 → semestre2)
            $updated1 = DB::table('esbtp_evaluations')
                ->where('periode', '1')
                ->update(['periode' => 'semestre1']);

            $updated2 = DB::table('esbtp_evaluations')
                ->where('periode', '2')
                ->update(['periode' => 'semestre2']);

            Log::info("  → '1': {$updated1} lignes normalisées");
            Log::info("  → '2': {$updated2} lignes normalisées");
        } else {
            Log::info('  ✅ Toutes les évaluations sont déjà au format standard.');
        }
    }

    /**
     * Normaliser esbtp_notes.semestre (VARCHAR)
     * Format détecté: '1', '2' (généré par auto-sync bug dans ESBTPNote::booted())
     */
    private function checkNotes(): void
    {
        Log::info('📋 Vérification esbtp_notes...');

        // '1' → 'semestre1'
        $updated1 = DB::table('esbtp_notes')
            ->where('semestre', '1')
            ->update(['semestre' => 'semestre1']);

        // '2' → 'semestre2'
        $updated2 = DB::table('esbtp_notes')
            ->where('semestre', '2')
            ->update(['semestre' => 'semestre2']);

        Log::info("  → '1': {$updated1} lignes normalisées");
        Log::info("  → '2': {$updated2} lignes normalisées");
    }

    /**
     * Normaliser esbtp_planifications_academiques.semestre (INT)
     * Format détecté: 1, 2
     *
     * Note: Cette table utilise un INT, donc on va créer une colonne VARCHAR temporaire
     * pour la normalisation, puis la migration finale remplacera par periode_id.
     */
    private function checkPlanificationsAcademiques(): void
    {
        Log::info('📋 Vérification esbtp_planifications_academiques...');

        // Vérifier si la colonne semestre_str existe déjà
        $columnExists = DB::getSchemaBuilder()->hasColumn('esbtp_planifications_academiques', 'semestre_str');

        if (!$columnExists) {
            // Créer colonne temporaire VARCHAR
            DB::statement('ALTER TABLE esbtp_planifications_academiques ADD COLUMN semestre_str VARCHAR(20) NULL AFTER semestre');
            Log::info('  → Colonne temporaire semestre_str créée');
        }

        // Copier et normaliser les valeurs
        $updated1 = DB::table('esbtp_planifications_academiques')
            ->where('semestre', 1)
            ->update(['semestre_str' => 'semestre1']);

        $updated2 = DB::table('esbtp_planifications_academiques')
            ->where('semestre', 2)
            ->update(['semestre_str' => 'semestre2']);

        Log::info("  → Semestre 1: {$updated1} lignes normalisées");
        Log::info("  → Semestre 2: {$updated2} lignes normalisées");
    }

    /**
     * Normaliser esbtp_config_matieres.semestre (INT ou NULL)
     * Format détecté: 1, 2, NULL
     */
    private function checkConfigMatieres(): void
    {
        Log::info('📋 Vérification esbtp_config_matieres...');

        $columnExists = DB::getSchemaBuilder()->hasColumn('esbtp_config_matieres', 'semestre_str');

        if (!$columnExists) {
            DB::statement('ALTER TABLE esbtp_config_matieres ADD COLUMN semestre_str VARCHAR(20) NULL AFTER semestre');
            Log::info('  → Colonne temporaire semestre_str créée');
        }

        $updated1 = DB::table('esbtp_config_matieres')
            ->where('semestre', 1)
            ->update(['semestre_str' => 'semestre1']);

        $updated2 = DB::table('esbtp_config_matieres')
            ->where('semestre', 2)
            ->update(['semestre_str' => 'semestre2']);

        Log::info("  → Semestre 1: {$updated1} lignes normalisées");
        Log::info("  → Semestre 2: {$updated2} lignes normalisées");

        // Compter les NULL (matières annuelles)
        $nullCount = DB::table('esbtp_config_matieres')
            ->whereNull('semestre')
            ->count();
        Log::info("  → {$nullCount} matières annuelles détectées (semestre NULL)");
    }

    /**
     * Normaliser esbtp_config_matiere_type_formations.semestre (INT ou NULL)
     * Format détecté: 1, 2, NULL
     */
    private function checkConfigMatiereTypeFormations(): void
    {
        Log::info('📋 Vérification esbtp_config_matiere_type_formations...');

        $columnExists = DB::getSchemaBuilder()->hasColumn('esbtp_config_matiere_type_formations', 'semestre_str');

        if (!$columnExists) {
            DB::statement('ALTER TABLE esbtp_config_matiere_type_formations ADD COLUMN semestre_str VARCHAR(20) NULL AFTER semestre');
            Log::info('  → Colonne temporaire semestre_str créée');
        }

        $updated1 = DB::table('esbtp_config_matiere_type_formations')
            ->where('semestre', 1)
            ->update(['semestre_str' => 'semestre1']);

        $updated2 = DB::table('esbtp_config_matiere_type_formations')
            ->where('semestre', 2)
            ->update(['semestre_str' => 'semestre2']);

        Log::info("  → Semestre 1: {$updated1} lignes normalisées");
        Log::info("  → Semestre 2: {$updated2} lignes normalisées");

        $nullCount = DB::table('esbtp_config_matiere_type_formations')
            ->whereNull('semestre')
            ->count();
        Log::info("  → {$nullCount} configurations annuelles détectées (semestre NULL)");
    }

    /**
     * Normaliser esbtp_resultats.periode (VARCHAR ou NULL)
     * Table vide en production, mais on prépare la structure.
     */
    private function checkResultats(): void
    {
        Log::info('📋 Vérification esbtp_resultats...');

        $total = DB::table('esbtp_resultats')->count();

        if ($total === 0) {
            Log::info('  ℹ️  Table vide, normalisation ignorée.');
            return;
        }

        // Tentative de normalisation (au cas où)
        $updated1 = DB::table('esbtp_resultats')
            ->where('periode', '1')
            ->orWhere('periode', 'Semestre 1')
            ->update(['periode' => 'semestre1']);

        $updated2 = DB::table('esbtp_resultats')
            ->where('periode', '2')
            ->orWhere('periode', 'Semestre 2')
            ->update(['periode' => 'semestre2']);

        Log::info("  → Semestre 1: {$updated1} lignes normalisées");
        Log::info("  → Semestre 2: {$updated2} lignes normalisées");
    }

    /**
     * Normaliser esbtp_bulletins.periode (VARCHAR ou NULL)
     * Vérification nécessaire du format réel.
     */
    private function checkBulletins(): void
    {
        Log::info('📋 Vérification esbtp_bulletins...');

        // Vérifier les formats existants
        $distinctPeriodes = DB::table('esbtp_bulletins')
            ->whereNotNull('periode')
            ->distinct()
            ->pluck('periode');

        if ($distinctPeriodes->isEmpty()) {
            Log::info('  ℹ️  Aucune période définie dans les bulletins.');
            return;
        }

        Log::info("  → Formats détectés: " . $distinctPeriodes->implode(', '));

        // Normaliser tous les formats possibles
        $patterns = [
            '1' => 'semestre1',
            '2' => 'semestre2',
            'Semestre 1' => 'semestre1',
            'Semestre 2' => 'semestre2',
            'S1' => 'semestre1',
            'S2' => 'semestre2',
        ];

        foreach ($patterns as $old => $new) {
            $updated = DB::table('esbtp_bulletins')
                ->where('periode', $old)
                ->update(['periode' => $new]);

            if ($updated > 0) {
                Log::info("  → '{$old}': {$updated} lignes normalisées");
            }
        }
    }
}
