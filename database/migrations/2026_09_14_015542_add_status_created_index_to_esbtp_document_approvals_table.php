<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La file d'approbation se lit « les demandes en attente, la plus recente
 * d'abord ». Aucun index ne repondait a cette question.
 *
 * Le seul index de la table est (document_type, etudiant_id, status) : `status`
 * y est en troisieme position, donc inutilisable seul — la regle du prefixe le
 * plus a gauche — et `created_at` n'y figure pas, donc le tri etait un filesort.
 * Ironie du sort, la deduplication, elle, tombe pile dessus : le seul chemin qui
 * n'en avait pas besoin.
 *
 * La table ne se purge jamais : approuver ou refuser change le statut, cela ne
 * supprime rien. Sur une instance a plus de deux mille inscriptions dont six
 * secretaires ont desormais un lien permanent vers cet ecran, elle ne fait que
 * grossir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_document_approvals', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_approbations_file');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_document_approvals', function (Blueprint $table) {
            $table->dropIndex('idx_approbations_file');
        });
    }
};
