<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `esbtp_resultats_recompute_log.source` etait un `enum('observer','command','manual')`.
 *
 * Toute source posee hors de cette liste fait LEVER l'INSERT — Laravel active
 * `STRICT_TRANS_TABLES` (`config/database.php`) — et `writeAuditLog()` avale
 * l'exception dans un `catch (\Throwable)` avec un simple avertissement. Le
 * recalcul se declare alors reussi, et sa trace n'existe pas.
 *
 * Mesure le 20 septembre 2026 : la suite de tests produisait 53 « audit log
 * write failed » pour 82 recalculs de source `deplacement` et 12 de source
 * `cli`. Zero ligne ecrite, aucune erreur visible.
 *
 * La colonne devient un `string(30)`, comme `cash_counts.mode_paiement` :
 * ajouter une source ne demandera plus ni `ALTER` d'enumeration ni verrou de
 * table sur les huit instances. La liste connue reste documentee ici et dans
 * le job, mais elle n'est plus une contrainte qui echoue en silence.
 */
return new class extends Migration
{
    public function up(): void
    {
        // `change()` sur un enum demande doctrine/dbal, absent du projet.
        DB::statement("ALTER TABLE esbtp_resultats_recompute_log MODIFY source VARCHAR(30) NOT NULL DEFAULT 'observer'");
    }

    public function down(): void
    {
        // Une source hors de l'enumeration d'origine empecherait le retour :
        // on la ramene a 'manual', qui est la plus proche d'un geste pilote.
        DB::table('esbtp_resultats_recompute_log')
            ->whereNotIn('source', ['observer', 'command', 'manual'])
            ->update(['source' => 'manual']);

        DB::statement("ALTER TABLE esbtp_resultats_recompute_log MODIFY source ENUM('observer','command','manual') NOT NULL DEFAULT 'observer'");
    }
};
