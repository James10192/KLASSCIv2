<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('esbtp_teacher_attendances', function (Blueprint $table) {
            // Ajouter colonne type pour différencier émargement début/fin
            $table->enum('type', ['start', 'end'])->default('start')->after('status');
        });

        // Mettre à jour les enregistrements existants pour être de type 'start'
        DB::table('esbtp_teacher_attendances')->update(['type' => 'start']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_teacher_attendances', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
