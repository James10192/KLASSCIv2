<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La place d'une matiere sur le bulletin, SEMESTRE PAR SEMESTRE.
 *
 * `esbtp_matiere_filiere_niveau` est unique sur (matiere, filiere, niveau) :
 * une matiere n'y a qu'une place et qu'un semestre. Or une matiere peut etre
 * enseignee aux deux semestres et n'y pas occuper le meme rang — chez ESBTP
 * Abidjan, Mathematiques generales est 4e au premier semestre et 1re au second
 * en Geometre Topographe. Charger les deux maquettes ecrasait la premiere.
 *
 * Relacher l'unicite du pivot aurait double chaque matiere sur l'ecran
 * Maquette et touche cinq sites de lecture. Cette table porte donc le seul
 * point qui varie d'un semestre a l'autre, et laisse le pivot a son role :
 * la liaison, la classification, et la place par defaut.
 *
 * Rien ne la lit tant qu'aucune ligne n'existe : `BulletinSubjectOrder` s'y
 * refere d'abord, et retombe sur `ordre_bulletin` du pivot. Une ecole qui n'a
 * jamais ouvert cet ecran garde exactement l'ordre qu'elle avait.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('esbtp_maquette_places_semestre')) {
            return;
        }

        Schema::create('esbtp_maquette_places_semestre', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('filiere_id');
            $table->unsignedBigInteger('niveau_etude_id');
            $table->unsignedTinyInteger('semestre');
            // Meme borne que `esbtp_matiere_filiere_niveau.ordre_bulletin`,
            // pour que BulletinSubjectOrder::RANG_MAX reste vrai des deux cotes.
            $table->unsignedSmallInteger('ordre_bulletin');
            $table->timestamps();

            $table->unique(
                ['filiere_id', 'niveau_etude_id', 'semestre', 'matiere_id'],
                'uq_maquette_place_semestre'
            );
            // La lecture du bulletin part toujours du couple + semestre.
            $table->index(['filiere_id', 'niveau_etude_id', 'semestre'], 'idx_maquette_place_combo');

            $table->foreign('matiere_id')->references('id')->on('esbtp_matieres')->onDelete('cascade');
            $table->foreign('filiere_id')->references('id')->on('esbtp_filieres')->onDelete('cascade');
            $table->foreign('niveau_etude_id')->references('id')->on('esbtp_niveau_etudes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_maquette_places_semestre');
    }
};
