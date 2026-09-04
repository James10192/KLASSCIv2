<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Où en est une pièce, pour une inscription donnée.
 *
 * Une ligne par pièce et par inscription. L'inscription portant déjà l'année
 * universitaire, la reprise annuelle des pièces pour le ministère se lit
 * naturellement : trois années d'études, trois lignes « extrait de naissance ».
 *
 * Cinq états, et non un booléen « fournie ». Un booléen ne sait pas dire la
 * différence entre une pièce jamais apportée et une pièce apportée puis
 * refusée : dans les deux cas il vaut faux, et le guichet rappelle un étudiant
 * qui s'est pourtant déplacé. C'est le manque central des essais précédents.
 *
 * Le refus exige un motif. Un dossier refusé sans raison écrite est un dossier
 * que personne ne peut débloquer : ni l'étudiant, qui ignore ce qu'on lui
 * reproche, ni le collègue qui reprendra le guichet demain. La règle est tenue
 * par le modèle (App\Models\ESBTPInscriptionPiece), là où toutes les écritures
 * passent.
 *
 * Le geste normal est la COCHE, pas le téléversement. L'agent regarde le
 * dossier papier qu'on lui tend et coche ce qu'il a reçu. Le fichier est un
 * surplus, offert à l'école qui veut numériser : la colonne fichier_chemin
 * reste nullable POUR TOUJOURS, et l'état « déposée » n'exige aucun fichier.
 * Ce n'est pas une étape à finir plus tard — c'est le fonctionnement voulu.
 * Une école qui ne numérise rien doit pouvoir suivre ses dossiers de bout en
 * bout sans jamais téléverser quoi que ce soit.
 *
 * Table vide tant que le catalogue l'est : ce lot ne crée aucune ligne d'état,
 * et ne touche donc aucune inscription existante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_inscription_pieces', function (Blueprint $table) {
            $table->id();

            // L'état suit l'inscription : si l'inscription disparaît pour de
            // bon, l'état de son dossier n'a plus de sujet.
            $table->foreignId('inscription_id')
                ->constrained('esbtp_inscriptions')
                ->cascadeOnDelete();

            // En restriction : retirer une pièce du catalogue l'archive
            // (soft delete) et n'atteint jamais cette clé. Si une suppression
            // définitive était tentée un jour, elle serait refusée plutôt que
            // d'emporter l'historique des dossiers déjà constitués.
            $table->foreignId('piece_dossier_id')
                ->constrained('esbtp_pieces_dossier')
                ->restrictOnDelete();

            // attendue / deposee / validee / refusee / non_applicable.
            // « non_applicable » n'est pas un état de confort : une pièce du
            // catalogue peut ne pas concerner un étudiant précis (un transfert
            // qui n'a pas de certificat de scolarité du même établissement), et
            // sans cet état il figurerait éternellement comme manquant.
            $table->string('etat', 20)->default('attendue');

            // Obligatoire au refus, facultatif ailleurs. La contrainte est
            // métier, pas structurelle : une école qui reprend une décision doit
            // pouvoir effacer un motif devenu faux sans que la base l'en empêche.
            $table->text('motif')->nullable();

            // Qui a validé ou refusé, et quand. Sans ces deux colonnes, une
            // décision contestée n'a pas d'auteur, et une contestation sans
            // auteur ne se tranche pas.
            $table->foreignId('decidee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decidee_at')->nullable();

            // Combien d'exemplaires l'agent a réellement reçus, face aux
            // esbtp_pieces_dossier.nombre_exemplaires que l'école réclame. Sans
            // ce compteur, l'étudiant qui apporte une photo sur les deux
            // demandées est soit compté comme n'ayant rien apporté, soit compté
            // comme réglé : les deux sont faux, et il repart sans le savoir.
            $table->unsignedTinyInteger('exemplaires_recus')->default(0);

            // Le téléversement est un SURPLUS, jamais le chemin normal : la
            // colonne reste nullable pour toujours, et rien n'exige qu'elle
            // soit remplie pour qu'une pièce soit déposée, validée ou soldée.
            // Ne la rendez pas obligatoire en croyant terminer le travail.
            $table->string('fichier_chemin')->nullable();

            $table->timestamps();

            // Une pièce ne peut figurer qu'une fois dans un dossier : sans cette
            // unicité, deux agents au guichet créeraient deux lignes pour la
            // même pièce, l'une validée et l'autre refusée, et aucune des deux
            // ne serait fausse.
            $table->unique(['inscription_id', 'piece_dossier_id'], 'esbtp_inscription_piece_unique');

            // Le comptage des dossiers incomplets lit cette colonne en premier.
            $table->index(['etat'], 'esbtp_inscription_pieces_etat_idx');
        });
    }

    /**
     * Comme pour le catalogue : on refuse bruyamment plutôt que d'emporter en
     * silence des décisions de guichet qui ne se retrouvent nulle part ailleurs.
     */
    public function down(): void
    {
        if (Schema::hasTable('esbtp_inscription_pieces') && DB::table('esbtp_inscription_pieces')->exists()) {
            throw new RuntimeException(
                "L'etat des pieces contient des decisions prises au guichet. "
                . 'Videz esbtp_inscription_pieces avant de revenir en arriere, ou conservez la table.'
            );
        }

        Schema::dropIfExists('esbtp_inscription_pieces');
    }
};
