<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogue des pieces a fournir a l'inscription.
     *
     * Ce catalogue ne dit PAS ou en est un etudiant : il dit seulement ce que
     * l'ecole reclame, et a qui. L'etat « fourni / manquant » vit sur
     * l'inscription (lot 2), parce que l'ecole reprend un exemplaire de chaque
     * piece CHAQUE ANNEE pour le ministere : le meme etudiant en licence 3 a
     * donc trois lignes « extrait de naissance », une par annee, et c'est voulu.
     *
     * Table volontairement vide a la creation d'une instance : tant qu'une ecole
     * n'a rien configure, aucun ecran existant ne change de comportement. Le jeu
     * de pieces par defaut est PROPOSE (bouton dans l'ecran de configuration),
     * jamais impose.
     */
    public function up(): void
    {
        Schema::create('esbtp_documents_requis', function (Blueprint $table) {
            $table->id();

            // Identifiant stable, independant du libelle. Le libelle est
            // retouchable par l'ecole a tout moment ; le code, lui, sert de point
            // d'ancrage aux lots suivants et rend le jeu par defaut idempotent
            // (on ne recree pas « photo » si « photo » existe deja).
            $table->string('code', 60)->unique();

            $table->string('libelle');

            // Consigne au secretariat : « copie legalisee de moins de 3 mois ».
            // Ce n'est pas de la decoration : c'est ce qui evite qu'un agent
            // accepte une piece que le ministere refusera ensuite.
            $table->text('description')->nullable();

            $table->boolean('is_obligatoire')->default(true);

            // original / copie / indifferent. Une ecole qui reclame l'original
            // du releve de notes et une simple copie de la piece d'identite ne
            // peut pas etre servie par un seul booleen.
            $table->string('forme_attendue', 20)->default('copie');

            // Piste tranchee : l'ecole reprend un exemplaire chaque annee pour le
            // ministere, mais elle peut aussi reclamer plusieurs exemplaires d'un
            // coup a l'inscription (typiquement 4 photos d'identite). Le nombre
            // porte donc sur CETTE inscription, pas sur le cycle : le cumul sur
            // trois ans se lit en comptant les lignes d'inscription.
            $table->unsignedTinyInteger('nombre_exemplaires')->default(1);

            // Piste tranchee : une colonne, pas deux tables. Une piece attendue
            // « avant la fin de l'annee » n'est pas une piece manquante le jour
            // de l'inscription — sans cette nuance le secretariat verrait une
            // alerte des le premier jour sur une piece qui n'est pas encore due.
            // A ne pas confondre avec la RESERVE (esbtp_inscriptions.is_sous_reserve),
            // qui vise un document qui N'EXISTE PAS ENCORE (releve de bac non
            // delivre) : ici le document existe, seul son depot est differe.
            $table->string('echeance', 30)->default('inscription');

            // Portee. Tableau vide ou nul = « toutes les filieres » / « tous les
            // niveaux ». Stocke en JSON plutot qu'en table pivot parce qu'un
            // catalogue compte quelques dizaines de lignes, qu'il est lu en bloc
            // et mis en cache, et que la semantique « vide = toutes » se lit mal
            // sur un pivot (absence de ligne = aucune, ou toutes ?).
            $table->json('filiere_ids')->nullable();
            $table->json('niveau_ids')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('ordre')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Retirer une piece du catalogue ne doit pas effacer l'historique des
            // dossiers deja constitues : les lignes d'etat du lot 2 continuent de
            // pointer vers une piece archivee.
            $table->softDeletes();

            $table->index(['is_active', 'ordre'], 'esbtp_docreq_actif_ordre_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_documents_requis');
    }
};
