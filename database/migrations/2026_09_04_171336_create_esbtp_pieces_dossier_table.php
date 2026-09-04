<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue des pièces qu'une école réclame au dossier d'inscription.
 *
 * Ce catalogue dit ce que l'école exige, et à qui. Il ne dit jamais où en est
 * un étudiant : cet état-là vivra dans les tables de dépôt et de consommation
 * du lot suivant (voir docs/lot-2-pieces-a-reprendre.md).
 *
 * UNE PIÈCE APPARTIENT D'ABORD À L'ÉTUDIANT, PAS À L'ANNÉE. Un extrait de
 * naissance, des photos d'identité, un diplôme sont déposés une fois et durent.
 * Ce qui est propre à l'année, c'est ce qu'une inscription en CONSOMME : six
 * photos déposées, deux consommées par la première année, deux par la deuxième,
 * il en reste deux. C'est la colonne `appartenance` qui tranche, pièce par
 * pièce, et c'est l'école qui la fixe.
 *
 * (Une version antérieure de ce commentaire affirmait l'inverse — « trois
 * années d'études, trois lignes extrait de naissance, et c'est voulu ». C'était
 * faux, et le dire ici pour que personne ne le rétablisse en croyant réparer.)
 *
 * Pièce à fournir n'est pas réserve. La réserve, portée par
 * esbtp_inscriptions.is_sous_reserve, vise un document qui N'EXISTE PAS ENCORE
 * et sera délivré plus tard. Ici le document existe, seule sa remise est
 * attendue. Les deux mécanismes restent séparés.
 *
 * La table est vide à la création d'une instance, et c'est la garantie que ce
 * lot est inoffensif : tant qu'une école n'a rien configuré, aucun écran
 * existant ne change de comportement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_pieces_dossier', function (Blueprint $table) {
            $table->id();

            // Identifiant stable, indépendant du libellé. Le libellé se retouche
            // à tout moment ; le code sert de point d'ancrage aux lots suivants
            // et rend l'installation du jeu proposé idempotente.
            //
            // Unique GLOBALEMENT, jamais unique « par portée » : sur MySQL, un
            // index unique portant une colonne nulle laisse passer les doublons
            // sans rien dire, et une école qui croit tenir un garde-fou n'en a
            // pas. Deux pièces homonymes sur deux filières portent donc deux
            // codes distincts, ce dont le générateur de code se charge seul.
            $table->string('code', 60)->unique();

            $table->string('libelle');

            // Consigne au guichet : « copie légalisée de moins de trois mois ».
            // Ce n'est pas de la décoration, c'est ce qui évite qu'un agent
            // accepte une pièce que le ministère refusera ensuite.
            $table->text('description')->nullable();

            $table->boolean('is_obligatoire')->default(true);

            // original / copie / indifférent. Une école qui garde l'original du
            // relevé de notes et rend la copie de la pièce d'identité ne peut
            // pas être servie par un seul booléen. Sans valeur par défaut au
            // niveau du schéma : la valeur proposée au guichet est un réglage
            // d'école (pieces_dossier.forme_defaut), pas une constante de base.
            $table->string('forme_attendue', 20);

            // Ce qu'UNE INSCRIPTION consomme du dépôt de l'étudiant : deux
            // photos d'identité, trois copies du diplôme. C'est l'école qui le
            // fixe, jamais le code.
            //
            // Le nom dit « par inscription » et non « nombre d'exemplaires »,
            // parce que la seconde formule ne dit pas si c'est par an ou en
            // tout : une école qui réclame deux photos ne sait plus, en lisant
            // « 2 », si elle en attend deux à chaque rentrée ou deux pour la
            // scolarité entière. Selon l'appartenance ci-dessous, un étudiant
            // dépose une fois de quoi couvrir plusieurs années, ou redonne
            // chaque année.
            $table->unsignedTinyInteger('exemplaires_par_inscription')->default(1);

            // La pièce dure-t-elle, ou se redonne-t-elle chaque année ?
            //
            //  - `etudiant`    : le dépôt est un stock qui sert plusieurs
            //                    inscriptions (extrait de naissance, photos) ;
            //  - `inscription` : la pièce est redonnée à chaque rentrée (un
            //                    certificat médical de l'année, un reçu).
            //
            // Aucune valeur par défaut au niveau du schéma n'aurait de sens
            // universel : c'est une décision d'établissement, et elle est
            // écrite pièce par pièce.
            $table->string('appartenance', 20);

            // Combien de temps une pièce déposée reste valable, en mois.
            //
            // NULLABLE, et NULL veut dire « ne périme jamais » : un extrait de
            // naissance ne se périme pas, un certificat médical si. Surtout pas
            // un zéro en guise de « jamais » — `0` se lit naturellement « valide
            // zéro mois », donc périmé à l'instant du dépôt, l'exact contraire
            // de l'intention ; et le code qui l'oublie ne plante pas, il
            // redemande simplement toutes les pièces à tout le monde. Le nul
            // force à traiter le cas.
            //
            // La validité court depuis la DÉLIVRANCE du document, pas depuis
            // son dépôt : un extrait délivré en 2019 et déposé en 2026 est déjà
            // périmé sous une validité de trois mois. La date de délivrance vit
            // sur le dépôt, avec l'exemplaire qu'elle date.
            $table->unsignedSmallInteger('duree_validite_mois')->nullable();

            // Une pièce attendue avant la fin de l'année n'est pas une pièce
            // manquante le jour de l'inscription. Sans cette nuance, le dossier
            // de tout le monde serait rouge à la rentrée, et un signal toujours
            // rouge n'est plus un signal.
            $table->string('echeance', 30);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('ordre')->default(0);

            // Clés étrangères en ON DELETE SET NULL : garder la trace de
            // l'auteur ne doit pas empêcher la suppression d'un compte.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Retirer une pièce du catalogue ne doit pas effacer l'historique
            // des dossiers déjà constitués : les lignes de dépôt continueront
            // de pointer vers une pièce archivée.
            $table->softDeletes();

            $table->index(['is_active', 'ordre'], 'esbtp_pieces_dossier_actif_ordre_idx');
        });

        // Portée : deux pivots ADDITIFS, jamais une colonne « précision ».
        //
        // Aucune ligne de portée = la pièce vaut pour tout le monde, y compris
        // pour une filière créée demain. Restreindre, c'est ajouter des lignes.
        // Ce choix évite l'arbitrage « la règle la plus précise gagne », qui
        // n'est pas déterministe dès que deux règles sont également précises
        // (l'une sur la filière, l'autre sur le niveau) et que rien ne les
        // départage.
        Schema::create('esbtp_piece_dossier_filiere', function (Blueprint $table) {
            $table->id();

            // La portée appartient à la pièce : quand la pièce disparaît pour
            // de bon, sa portée n'a plus d'objet.
            $table->foreignId('piece_dossier_id')
                ->constrained('esbtp_pieces_dossier')
                ->cascadeOnDelete();

            // En restriction, et non en cascade. Une pièce restreinte à une
            // seule filière dont on effacerait la ligne de portée se
            // retrouverait sans portée du tout, c'est-à-dire réclamée à TOUTE
            // l'école : un élargissement silencieux, jamais une erreur visible.
            // Les filières étant archivées et non supprimées, cette contrainte
            // ne gêne aucun usage courant.
            $table->foreignId('filiere_id')
                ->constrained('esbtp_filieres')
                ->restrictOnDelete();

            $table->unique(['piece_dossier_id', 'filiere_id'], 'esbtp_piece_filiere_unique');
        });

        Schema::create('esbtp_piece_dossier_niveau', function (Blueprint $table) {
            $table->id();

            $table->foreignId('piece_dossier_id')
                ->constrained('esbtp_pieces_dossier')
                ->cascadeOnDelete();

            $table->foreignId('niveau_id')
                ->constrained('esbtp_niveau_etudes')
                ->restrictOnDelete();

            $table->unique(['piece_dossier_id', 'niveau_id'], 'esbtp_piece_niveau_unique');
        });
    }

    /**
     * Un retour en arrière ne doit pas emporter le catalogue d'une école avec
     * lui. Sur une base qui contient déjà des pièces, on refuse bruyamment
     * plutôt que de détruire en silence une liste que la scolarité a saisie à
     * la main. Vider la table reste possible, mais c'est alors une décision.
     */
    public function down(): void
    {
        if (Schema::hasTable('esbtp_pieces_dossier') && DB::table('esbtp_pieces_dossier')->exists()) {
            throw new RuntimeException(
                "Le catalogue des pieces a fournir contient des lignes saisies par l'ecole. "
                . 'Videz esbtp_pieces_dossier avant de revenir en arriere, ou conservez la table.'
            );
        }

        Schema::dropIfExists('esbtp_piece_dossier_niveau');
        Schema::dropIfExists('esbtp_piece_dossier_filiere');
        Schema::dropIfExists('esbtp_pieces_dossier');
    }
};
