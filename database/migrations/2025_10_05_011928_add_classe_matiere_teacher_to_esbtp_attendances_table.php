<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            // Add classe_id, matiere_id, and teacher_id columns
            $table->foreignId('classe_id')->nullable()->after('etudiant_id')->constrained('esbtp_classes')->onDelete('cascade');
            $table->foreignId('matiere_id')->nullable()->after('classe_id')->constrained('esbtp_matieres')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->after('matiere_id')->constrained('esbtp_teachers')->onDelete('set null');

            // Add is_justified column for consistency with code
            $table->boolean('is_justified')->default(false)->after('statut');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['classe_id']);
            $table->dropForeign(['matiere_id']);
            $table->dropForeign(['teacher_id']);

            // Drop columns
            $table->dropColumn(['classe_id', 'matiere_id', 'teacher_id', 'is_justified']);
        });
    }
};
