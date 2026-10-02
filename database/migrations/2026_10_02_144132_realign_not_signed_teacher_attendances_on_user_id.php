<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Remet sur le bon compte les « non émargé » écrits par la tâche
 * attendance:mark-unattended-teacher-sessions avant octobre 2026.
 *
 * Elle recopiait l'id du PROFIL enseignant de la séance (esbtp_teachers.id)
 * dans esbtp_teacher_attendances.teacher_id, qui désigne un COMPTE (users.id).
 * Quand ce numéro existait comme compte, l'émargement atterrissait chez
 * quelqu'un d'autre ; la migration du 5 septembre 2026 avait réaligné
 * l'historique, mais la tâche a continué d'écrire de travers ensuite.
 *
 * Repérage exact, sans deviner : une ligne « not_signed » dont teacher_id est
 * l'id du profil de SA séance, alors que ce profil appartient à un autre
 * compte. On la passe sur ce compte, sauf si la ligne juste existe déjà
 * (contrainte unique) : elle est alors comptée et laissée. Rien n'est supprimé.
 * Idempotente : une ligne réalignée ne répond plus au critère.
 */
return new class extends Migration
{
    private const TABLE = 'esbtp_teacher_attendances';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasTable('esbtp_teachers') || ! Schema::hasTable('esbtp_seance_cours')) {
            return;
        }

        $lignes = DB::table(self::TABLE.' as ta')
            ->join('esbtp_seance_cours as s', 's.id', '=', 'ta.course_id')
            ->join('esbtp_teachers as t', 't.id', '=', 's.teacher_id')
            ->where('ta.status', 'not_signed')
            ->whereColumn('ta.teacher_id', 's.teacher_id')
            ->whereNotNull('t.user_id')
            ->whereColumn('t.user_id', '!=', 'ta.teacher_id')
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('users')->whereColumn('users.id', 't.user_id'))
            ->orderBy('ta.id')
            ->get(['ta.id', 'ta.course_id', 'ta.date', 'ta.type', 't.user_id']);

        $realignees = 0;
        $conflits = [];
        foreach ($lignes as $ligne) {
            $doublon = DB::table(self::TABLE)
                ->where('teacher_id', $ligne->user_id)
                ->where('course_id', $ligne->course_id)
                ->whereDate('date', $ligne->date)
                ->where('type', $ligne->type)
                ->where('id', '!=', $ligne->id)
                ->exists();
            if ($doublon) {
                $conflits[] = $ligne->id;
                continue;
            }
            DB::table(self::TABLE)->where('id', $ligne->id)->update(['teacher_id' => $ligne->user_id]);
            $realignees++;
        }

        Log::info('[realign_not_signed_teacher_attendances_on_user_id] Non émargés réattribués au compte de l\'enseignant', [
            'realignees' => $realignees,
            'conflits' => count($conflits),
            'conflits_ids' => array_slice($conflits, 0, 50),
        ]);
    }

    /** Non réversible : l'ancienne valeur désignait le mauvais compte. Rien à défaire. */
    public function down(): void
    {
    }
};
