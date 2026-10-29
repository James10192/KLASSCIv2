<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_resultats_matieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_id')->constrained('esbtp_bulletins')->onDelete('cascade');
            $table->foreignId('matiere_id')->constrained('esbtp_matieres');
            $table->decimal('moyenne', 5, 2);
            $table->integer('coefficient');
            $table->integer('rang')->nullable();
            $table->string('appreciation')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['bulletin_id', 'matiere_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_resultats_matieres');
    }
};
