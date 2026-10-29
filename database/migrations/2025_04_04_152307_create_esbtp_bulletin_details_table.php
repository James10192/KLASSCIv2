<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpBulletinDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_bulletin_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_id')->constrained('esbtp_bulletins')->onDelete('cascade');
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('restrict');
            $table->decimal('note_cc', 5, 2)->nullable();
            $table->decimal('note_examen', 5, 2)->nullable();
            $table->decimal('moyenne', 5, 2)->nullable();
            $table->decimal('moyenne_classe', 5, 2)->nullable();
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->integer('credits')->nullable();
            $table->integer('credits_valides')->nullable();
            $table->integer('rang')->nullable();
            $table->integer('effectif')->nullable();
            $table->text('appreciation')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            // Create index for performance
            $table->index('bulletin_id');
            $table->index('matiere_id');

            // Ensure unique matiere per bulletin
            $table->unique(['bulletin_id', 'matiere_id'], 'bulletin_matiere_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_bulletin_details');
    }
}
