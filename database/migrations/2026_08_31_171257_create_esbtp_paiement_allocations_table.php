<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un versement se repartit sur plusieurs frais.
 *
 * `esbtp_paiements` ne portait qu'une seule `frais_category_id`. Un etudiant qui
 * paie 255 000 F pour couvrir son inscription, sa scolarite et sa ramette voyait
 * donc la totalite atterrir sur une seule ligne — celle que le caissier avait
 * choisie — et le reste de sa dette rester intact.
 *
 * Pire : le calcul du restant fait `max(0, du - paye)` par categorie. L'excedent
 * etait donc ECRETE. Sur ISLG, 105 000 F d'un etudiant sont ainsi devenus
 * invisibles : ni imputes, ni signales.
 *
 * Cette table dit ou l'argent est reellement alle. Un paiement sans ligne ici
 * garde son comportement d'avant — sa categorie unique fait foi — donc rien de
 * l'existant ne bouge tant qu'on n'a pas reparti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_paiement_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paiement_id')
                ->constrained('esbtp_paiements')
                ->cascadeOnDelete();

            $table->foreignId('frais_category_id')
                ->constrained('esbtp_frais_categories')
                ->cascadeOnDelete();

            $table->decimal('montant', 10, 2);

            $table->timestamps();

            // Une seule ligne par frais et par versement : deux lignes pour la
            // meme categorie se additionneraient sans que rien ne dise pourquoi
            // elles sont deux.
            $table->unique(['paiement_id', 'frais_category_id'], 'paiement_allocation_unique');

            // La lecture se fait toujours par categorie, pour savoir ce qui a
            // ete encaisse sur chaque frais.
            $table->index('frais_category_id', 'idx_allocations_categorie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_paiement_allocations');
    }
};
