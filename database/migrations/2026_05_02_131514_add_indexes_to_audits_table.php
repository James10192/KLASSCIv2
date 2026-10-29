<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute des index sur la table `audits` pour les requêtes critiques :
 *  - filtre par created_at (timeline globale, activité 30j)
 *  - filtre par event (created/updated/deleted/restored)
 *  - filtre par tags (settings, permissions)
 *
 * NOTE : les index `auditable_type + auditable_id` et `user_id + user_type`
 * existent déjà depuis la migration de création (`morphs()` + index user).
 * On les recrée seulement s'ils sont absents (idempotent multi-tenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $existing = $this->existingIndexes();

            // (auditable_type, auditable_id) — déjà créé par morphs(), skip si présent
            if (! $this->indexCovers($existing, ['auditable_type', 'auditable_id'])) {
                $table->index(['auditable_type', 'auditable_id'], 'audits_auditable_idx');
            }

            // created_at — pour les listes paginées par date desc
            if (! $this->indexCovers($existing, ['created_at'])) {
                $table->index('created_at', 'audits_created_at_idx');
            }

            // (user_type, user_id) — déjà créé en (user_id, user_type), skip
            if (! $this->indexCovers($existing, ['user_type', 'user_id'])
                && ! $this->indexCovers($existing, ['user_id', 'user_type'])) {
                $table->index(['user_type', 'user_id'], 'audits_user_idx');
            }

            // event — pour filtrer "tous les deleted des 7 derniers jours"
            if (! $this->indexCovers($existing, ['event'])) {
                $table->index('event', 'audits_event_idx');
            }

            // tags — pour filtrer settings/permissions
            if (! $this->indexCovers($existing, ['tags'])) {
                $table->index('tags', 'audits_tags_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $existing = $this->existingIndexes();

            // On dropIndex uniquement les indexes qu'on a créés (suffix _idx)
            // pour ne pas casser les indexes natifs de la migration originelle.
            foreach (['audits_auditable_idx', 'audits_created_at_idx', 'audits_user_idx', 'audits_event_idx', 'audits_tags_idx'] as $name) {
                if (isset($existing[$name])) {
                    $table->dropIndex($name);
                }
            }
        });
    }

    /**
     * Récupère les indexes existants groupés par nom.
     *
     * @return array<string, array<int, string>>
     */
    private function existingIndexes(): array
    {
        try {
            $rows = DB::select('SHOW INDEX FROM audits');
        } catch (\Throwable $e) {
            return [];
        }

        $byName = [];
        foreach ($rows as $row) {
            $byName[$row->Key_name][] = $row->Column_name;
        }

        return $byName;
    }

    /**
     * Vérifie si un index existant couvre exactement les colonnes demandées
     * (dans le bon ordre, en préfixe ou exact match).
     */
    private function indexCovers(array $existing, array $columns): bool
    {
        foreach ($existing as $cols) {
            // Préfixe match : un index sur (a, b, c) couvre une recherche sur (a) ou (a, b)
            if (count($cols) >= count($columns)) {
                $prefix = array_slice($cols, 0, count($columns));
                if ($prefix === $columns) {
                    return true;
                }
            }
        }

        return false;
    }
};
