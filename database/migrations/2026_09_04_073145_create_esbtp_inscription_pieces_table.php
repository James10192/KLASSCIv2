<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Etat des pieces, porte par l'INSCRIPTION et non par l'etudiant.
     *
     * L'ecole reprend un exemplaire de chaque piece a CHAQUE annee, pour le
     * transmettre aux ministeres. Un etudiant en troisieme annee a donc trois
     * lignes « extrait de naissance », une par inscription, et c'est voulu :
     * ranger cet etat sur l'etudiant effacerait la trace des annees passees.
     */
    public function up(): void
    {
        Schema::create('esbtp_inscription_pieces', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inscription_id')
                ->constrained('esbtp_inscriptions')->cascadeOnDelete();

            // Le code est copie ici : si l'ecole retire une ligne du catalogue,
            // l'historique de ce qui a ete recu reste lisible.
            $table->string('piece_code', 60);
            $table->foreignId('piece_id')->nullable()
                ->constrained('esbtp_pieces_dossier')->nullOnDelete();

            $table->boolean('est_fournie')->default(false);
            $table->date('fournie_le')->nullable();
            $table->foreignId('marquee_par')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('observation', 255)->nullable();

            $table->timestamps();

            $table->unique(['inscription_id', 'piece_code'], 'inscription_piece_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_inscription_pieces');
    }
};
