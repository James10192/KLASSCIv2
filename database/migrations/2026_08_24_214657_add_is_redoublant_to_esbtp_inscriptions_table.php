<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les bulletins affichent une mention « Redoublant » depuis toujours, mais la
 * colonne qu'ils lisent n'a jamais existe : elle n'etait declaree que dans
 * database/migrations_optimized/, un dossier de quatre fichiers reference nulle
 * part. Tous les etudiants etaient donc affiches non-redoublants.
 *
 * Cette colonne est le socle de la regle « nombre maximum de redoublements »
 * deja saisissable dans esbtp_regles_academiques mais jamais applicable faute
 * de donnee. Elle est posee a la reinscription lorsque l'etudiant reste sur le
 * meme niveau d'etude.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('esbtp_inscriptions', 'is_redoublant')) {
            return;
        }

        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->boolean('is_redoublant')
                ->default(false)
                ->after('type_inscription');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('esbtp_inscriptions', 'is_redoublant')) {
            return;
        }

        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->dropColumn('is_redoublant');
        });
    }
};
