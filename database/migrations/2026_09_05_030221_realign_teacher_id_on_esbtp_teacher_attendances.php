<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Réaligne esbtp_teacher_attendances.teacher_id sur users.id.
 *
 * La colonne porte une FK vers users.id (migration 2024_06_10_000001) et le
 * modèle expose teacher() -> User. Pourtant plusieurs écrivains y inséraient
 * l'id du profil esbtp_teachers : violation de FK quand cet id n'existe pas
 * comme utilisateur, et rattachement à un compte arbitraire quand il existe.
 *
 * Pour chaque ligne dont teacher_id est l'id d'un profil esbtp_teachers dont
 * le user_id diffère ET existe dans users, on remplace teacher_id par ce
 * user_id — SAUF si la valeur actuelle est elle-même le user_id d'un
 * enseignant (la ligne peut déjà être juste : on ne touche pas, on journalise).
 *
 * Idempotente : une ligne réalignée retombe, au passage suivant, dans le cas
 * « ambigu » (sa valeur est désormais un user_id d'enseignant) et n'est plus
 * modifiée. Aucune ligne n'est supprimée.
 */
return new class extends Migration
{
    private const TABLE = 'esbtp_teacher_attendances';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasTable('esbtp_teachers')) {
            return;
        }

        $compteurs = [
            'realignees' => 0,
            'ambigues'   => 0,
            'conflits'   => 0,
            'laissees'   => 0,
        ];
        $ambigues = [];
        $conflits = [];

        // user_id de tous les profils : une valeur présente ici est déjà un users.id
        // légitime pour un émargement, on ne la réinterprète pas comme esbtp_teachers.id.
        $userIdsDesProfils = DB::table('esbtp_teachers')
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $candidates = DB::table(self::TABLE . ' as ta')
            ->join('esbtp_teachers as t', 't.id', '=', 'ta.teacher_id')
            ->whereNotNull('t.user_id')
            ->whereColumn('t.user_id', '!=', 'ta.teacher_id')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 't.user_id');
            })
            ->orderBy('ta.id')
            ->get(['ta.id', 'ta.teacher_id', 'ta.course_id', 'ta.date', 'ta.type', 't.user_id']);

        foreach ($candidates as $ligne) {
            $actuel = (int) $ligne->teacher_id;
            $cible = (int) $ligne->user_id;

            if ($userIdsDesProfils->has($actuel)) {
                $compteurs['ambigues']++;
                $ambigues[] = ['id' => $ligne->id, 'teacher_id' => $actuel, 'user_id_profil' => $cible];
                continue;
            }

            // Contrainte unique (teacher_id, course_id, date, type) : si la ligne
            // juste existe déjà, on ne peut pas réaligner sans doublon. On laisse.
            $doublon = DB::table(self::TABLE)
                ->where('teacher_id', $cible)
                ->where('course_id', $ligne->course_id)
                ->whereDate('date', $ligne->date)
                ->where('type', $ligne->type)
                ->where('id', '!=', $ligne->id)
                ->exists();

            if ($doublon) {
                $compteurs['conflits']++;
                $conflits[] = ['id' => $ligne->id, 'teacher_id' => $actuel, 'user_id_cible' => $cible];
                continue;
            }

            DB::table(self::TABLE)
                ->where('id', $ligne->id)
                ->update(['teacher_id' => $cible]);
            $compteurs['realignees']++;
        }

        // Lignes dont teacher_id ne correspond ni à un utilisateur ni à un profil
        // résoluble : on ne sait pas les réattribuer, on les compte seulement.
        $compteurs['laissees'] = DB::table(self::TABLE . ' as ta')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('users')->whereColumn('users.id', 'ta.teacher_id');
            })
            ->count();

        Log::info('[realign_teacher_id_on_esbtp_teacher_attendances] Réalignement teacher_id -> users.id', [
            'compteurs' => $compteurs,
            'ambigues'  => $ambigues,
            'conflits'  => $conflits,
        ]);
    }

    /**
     * No-op documenté : la valeur d'origine (id de profil) n'était pas une
     * référence valide et n'a pas été conservée ; la recréer réintroduirait
     * les violations de FK. Le journal de up() garde la trace des lignes touchées.
     */
    public function down(): void
    {
        Log::info('[realign_teacher_id_on_esbtp_teacher_attendances] down() : aucune action (réalignement non réversible, données conservées).');
    }
};
