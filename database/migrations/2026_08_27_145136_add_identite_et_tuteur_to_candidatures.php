<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce que le formulaire d'inscription demande vraiment.
 *
 * La premiere version de esbtp_candidatures a ete ecrite d'apres l'idee que je
 * me faisais d'une candidature, pas d'apres l'ecran que la scolarite remplit
 * tous les jours. En regardant inscription/create.blade.php, il manquait le
 * lieu de naissance, la nationalite, la ville et la commune, le statut
 * d'affectation — et surtout le tuteur, sans qui l'ecole ne peut pas rappeler
 * un candidat mineur ou joignable par sa famille.
 *
 * Le statut d'affectation merite un mot : en Cote d'Ivoire, un bachelier est
 * affecte par l'Etat ou ne l'est pas, et cela change ce qu'il paie —
 * esbtp_echeancier_rules porte deja cette distinction. Le candidat le declare,
 * l'ecole le verifie ; c'est donc une aide a l'instruction du dossier, jamais
 * une valeur qu'on reprendrait telle quelle a l'inscription.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            $table->string('lieu_naissance', 120)->nullable()->after('date_naissance');
            $table->string('nationalite', 60)->nullable()->after('sexe');
            $table->string('ville', 100)->nullable()->after('email');
            $table->string('commune', 100)->nullable()->after('ville');

            // Declare par le candidat, verifie par l'ecole. Nullable : beaucoup
            // de bacheliers ne savent pas encore, et les forcer a choisir
            // produirait une reponse au hasard, pire qu'une case vide.
            //
            // Les trois valeurs sont ecrites en clair ici, et nulle part
            // ailleurs dans le code vivant : une migration est de l'histoire
            // figee, elle doit decrire la colonne telle qu'elle a ete creee,
            // meme si les constantes evoluent apres. La source de verite pour
            // tout le reste est ESBTPCandidature::affectationsDeclarables(),
            // qui delegue aux constantes de ESBTPEcheancierRule.
            $table->enum('affectation_status', ['affecté', 'réaffecté', 'non_affecté'])
                ->nullable()->after('annee_bac');

            // Le tuteur. Stocke a plat plutot qu'en relation : une candidature
            // n'est pas encore un dossier, et creer un ESBTPParent pour
            // quelqu'un que l'ecole n'a pas encore admis polluerait la base de
            // parents avec des gens qui ne viendront jamais.
            $table->string('tuteur_nom', 150)->nullable()->after('affectation_status');
            $table->string('tuteur_telephone', 30)->nullable()->after('tuteur_nom');
            $table->string('tuteur_lien', 60)->nullable()->after('tuteur_telephone');
            $table->string('tuteur_profession', 120)->nullable()->after('tuteur_lien');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            $table->dropColumn([
                'lieu_naissance', 'nationalite', 'ville', 'commune',
                'affectation_status',
                'tuteur_nom', 'tuteur_telephone', 'tuteur_lien', 'tuteur_profession',
            ]);
        });
    }
};
