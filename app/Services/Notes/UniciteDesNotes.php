<?php

namespace App\Services\Notes;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Une seule note vivante par élève et par évaluation.
 *
 * Les notes sont effacées en douceur et archivées : un index unique simple sur
 * (etudiant_id, evaluation_id) empêcherait de ressaisir une note effacée, que
 * le code ne voit plus. La contrainte porte donc sur une colonne générée qui
 * vaut 1 pour une note vivante et NULL sinon — MySQL ne compare pas les NULL
 * dans un index unique.
 *
 * Des doublons déjà en base ne sont jamais supprimés d'office : lequel des deux
 * est la bonne note est une décision d'école. Tant qu'il en reste, l'index
 * n'est pas posé, et `php artisan notes:unicite` les liste puis le repose.
 */
class UniciteDesNotes
{
    public const INDEX = 'esbtp_notes_etudiant_evaluation_vivante_unique';

    public function disponible(): bool
    {
        return DB::connection()->getDriverName() === 'mysql' && Schema::hasTable('esbtp_notes');
    }

    /**
     * Les notes archivées comptent : deux jumelles archivées, désarchivées
     * ensemble au retour de l'élève dans sa classe, heurteraient l'index.
     *
     * @return Collection<int, object{etudiant_id: int, evaluation_id: int, nombre: int, note_ids: string}>
     */
    public function doublons(): Collection
    {
        return collect(DB::select("
            SELECT etudiant_id, evaluation_id, COUNT(*) AS nombre, GROUP_CONCAT(id ORDER BY id) AS note_ids
            FROM esbtp_notes
            WHERE deleted_at IS NULL
            GROUP BY etudiant_id, evaluation_id
            HAVING COUNT(*) > 1
        "));
    }

    public function indexPose(): bool
    {
        return collect(DB::select('SHOW INDEX FROM esbtp_notes WHERE Key_name = ?', [self::INDEX]))->isNotEmpty();
    }

    /** Pose la colonne et, s'il ne reste aucun doublon, l'index. Rend vrai si l'index est en place. */
    public function poser(): bool
    {
        if (! $this->disponible()) {
            return false;
        }
        if (! Schema::hasColumn('esbtp_notes', 'note_vivante')) {
            DB::statement("ALTER TABLE esbtp_notes ADD COLUMN note_vivante TINYINT UNSIGNED
                GENERATED ALWAYS AS (IF({$this->conditionVivante()}, 1, NULL)) STORED");
        }
        if ($this->indexPose()) {
            return true;
        }

        $doublons = $this->doublons();
        if ($doublons->isNotEmpty()) {
            Log::warning('Notes en double : unicité non posée, à trancher par l\'école (php artisan notes:unicite)', [
                'paires' => $doublons->count(),
                'exemples' => $doublons->take(10)->all(),
            ]);

            return false;
        }

        DB::statement('ALTER TABLE esbtp_notes ADD UNIQUE INDEX '.self::INDEX.' (etudiant_id, evaluation_id, note_vivante)');

        return true;
    }

    public function retirer(): void
    {
        if (! $this->disponible()) {
            return;
        }
        if ($this->indexPose()) {
            // Posé, cet index sert aussi la clé étrangère sur etudiant_id, et
            // MySQL retire alors l'index qu'il avait créé pour elle : il refuse
            // ensuite de supprimer le nôtre (erreur 1553) tant qu'un autre ne
            // le remplace pas.
            if (collect(DB::select("SHOW INDEX FROM esbtp_notes WHERE Key_name = 'esbtp_notes_etudiant_id_foreign'"))->isEmpty()) {
                DB::statement('ALTER TABLE esbtp_notes ADD INDEX esbtp_notes_etudiant_id_foreign (etudiant_id)');
            }
            DB::statement('ALTER TABLE esbtp_notes DROP INDEX '.self::INDEX);
        }
        if (Schema::hasColumn('esbtp_notes', 'note_vivante')) {
            DB::statement('ALTER TABLE esbtp_notes DROP COLUMN note_vivante');
        }
    }

    private function conditionVivante(): string
    {
        return Schema::hasColumn('esbtp_notes', 'archived_at')
            ? 'deleted_at IS NULL AND archived_at IS NULL'
            : 'deleted_at IS NULL';
    }
}
