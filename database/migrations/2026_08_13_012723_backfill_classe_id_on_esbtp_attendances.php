<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill esbtp_attendances.classe_id / matiere_id / annee_universitaire_id.
 *
 * Contexte : la colonne classe_id a été ajoutée nullable en octobre 2025
 * (2025_10_05_011928) mais la saisie manuelle des présences ne la renseignait
 * pas jusqu'à la feature « présences tronc commun après orientation ». Toutes
 * les lignes antérieures ont donc classe_id NULL.
 *
 * Or ESBTPAbsenceService filtre désormais les séances par classe_id. Sans ce
 * backfill, ces faits historiques sortent du calcul et les bulletins existants
 * sous-comptent les heures d'absence, silencieusement.
 *
 * On dérive le contexte manquant depuis la séance et son emploi du temps.
 * Idempotent : ne touche que les lignes dont la colonne cible est NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'UPDATE esbtp_attendances a '
            .'INNER JOIN esbtp_seance_cours s ON s.id = a.seance_cours_id '
            .'LEFT JOIN esbtp_emploi_temps et ON et.id = s.emploi_temps_id '
            .'SET '
            .'a.classe_id = COALESCE(a.classe_id, s.classe_id, et.classe_id), '
            .'a.matiere_id = COALESCE(a.matiere_id, s.matiere_id), '
            .'a.teacher_id = COALESCE(a.teacher_id, s.teacher_id), '
            .'a.annee_universitaire_id = COALESCE(a.annee_universitaire_id, s.annee_universitaire_id, et.annee_universitaire_id) '
            .'WHERE a.classe_id IS NULL '
            .'OR a.matiere_id IS NULL '
            .'OR a.annee_universitaire_id IS NULL'
        );
    }

    public function down(): void
    {
        // Non réversible : on ne peut pas distinguer les valeurs backfillées des
        // valeurs d'origine sans perdre de l'information. No-op volontaire.
    }
};
