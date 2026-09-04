<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retire esbtp_inscriptions.documents_fournis.
 *
 * Colonne morte : les deux seules occurrences du depot etaient sa propre
 * declaration dans le $fillable et dans les casts du modele. Elle n'etait ni lue
 * ni ecrite nulle part, donc aucune reprise de donnees n'est necessaire.
 *
 * Migration a part, et posterieure a la creation de esbtp_inscription_pieces, pour
 * qu'un tenant puisse deployer la nouvelle table sans retirer la colonne tout de
 * suite s'il veut d'abord verifier lui-meme qu'elle est bien vide.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('esbtp_inscriptions', 'documents_fournis')) {
            return;
        }

        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->dropColumn('documents_fournis');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('esbtp_inscriptions', 'documents_fournis')) {
            return;
        }

        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->json('documents_fournis')->nullable()->after('observations');
        });
    }
};
