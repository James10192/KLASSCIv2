<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prendre la photo d'un étudiant avec un téléphone.
 *
 * Le poste du guichet n'a souvent pas de webcam, ou en a une mauvaise. Le
 * téléphone qui est dans la poche, lui, photographie très bien. Cette table est
 * le pont entre les deux : l'écran affiche un code QR, quelqu'un le scanne, la
 * photo arrive, et le guichet valide ou refait.
 *
 * ─── Ce que le jeton ouvre, et ce qu'il n'ouvre pas ───
 *
 * L'adresse portée par le code QR est PUBLIQUE : le téléphone qui scanne n'a pas
 * de compte KLASSCI, et l'étudiant qui se photographie lui-même n'en aura jamais.
 * Le jeton est donc la seule clé, et il est construit pour ne valoir presque
 * rien :
 *
 *  - il ne donne accès qu'à UNE page, celle de la prise de vue, et à UN envoi ;
 *  - il expire en quelques minutes (réglage d'école) ;
 *  - il meurt à la première photo reçue — une seconde tentative demande un
 *    nouveau code, affiché par le guichet ;
 *  - il ne montre qu'un nom, pour que la personne sache qui elle photographie.
 *    Rien du dossier, rien de la scolarité, rien des paiements.
 *
 * ─── Pourquoi la photo n'écrase pas directement celle de l'étudiant ───
 *
 * Parce qu'une photo prise au téléphone est floue une fois sur trois. Elle
 * atterrit d'abord ici, en attente ; le guichet la voit et décide. Sans cette
 * étape, la seule façon de rattraper une photo ratée serait d'en reprendre une
 * autre — en ayant déjà détruit la précédente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_captures_photo', function (Blueprint $table) {
            $table->id();

            // Le jeton de l'adresse publique. Indexe et unique : c'est par lui
            // que la page du telephone se retrouve, et deux captures ne doivent
            // jamais partager la meme cle.
            $table->string('jeton', 64)->unique();

            $table->foreignId('etudiant_id')
                ->constrained('esbtp_etudiants')
                ->cascadeOnDelete();

            // Qui a ouvert la capture. Une photo d'eleve arrive dans le dossier
            // sans qu'aucun compte ne se soit identifie au moment de l'envoi :
            // la trace de qui a ouvert la porte est ce qui rend l'operation
            // imputable.
            $table->foreignId('ouverte_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('expire_at');

            $table->string('etat', 20)->default('en_attente');

            // Le fichier recu, sur le disque PRIVE, tant que personne ne l'a
            // valide. Une photo d'eleve — souvent mineur — n'a rien a faire sur
            // un chemin que le serveur web sert directement.
            $table->string('fichier_provisoire')->nullable();

            $table->timestamp('recue_at')->nullable();
            $table->ipAddress('ip_capture')->nullable();

            $table->timestamp('decidee_at')->nullable();
            $table->foreignId('decidee_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['etudiant_id', 'etat'], 'captures_photo_etudiant_idx');
            $table->index('expire_at', 'captures_photo_expiration_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_captures_photo');
    }
};
