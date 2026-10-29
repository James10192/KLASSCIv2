<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::create('esbtp_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants');
            $table->foreignId('matiere_id')->constrained('esbtp_matieres');
            $table->date('date');
            $table->decimal('hours', 5, 2)->default(1.00);
            $table->boolean('justified')->default(false);
            $table->text('justification_text')->nullable();
            $table->timestamp('justification_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_absences');
    }
};
