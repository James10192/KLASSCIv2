<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bug latent : `due_mode` était string(20) mais la constante
 * `ESBTPEcheancierRuleLine::DUE_MODE_DAYS_AFTER_INSCRIPTION` vaut
 * 'days_after_inscription' (22 caractères). MySQL strict mode
 * rejette l'insertion. Plus aucune règle ne pouvait être stockée
 * tant que la colonne reste à 20.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_echeancier_rule_lines', function (Blueprint $table) {
            $table->string('due_mode', 32)->change();
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_echeancier_rule_lines', function (Blueprint $table) {
            $table->string('due_mode', 20)->change();
        });
    }
};
