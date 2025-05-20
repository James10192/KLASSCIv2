<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpComptabiliteTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Table de configuration de la comptabilité
        if (!Schema::hasTable('esbtp_comptabilite_configurations')) {
            Schema::create('esbtp_comptabilite_configurations', function (Blueprint $table) {
                $table->id();
                $table->string('cle');
                $table->text('valeur')->nullable();
                $table->string('type')->default('string'); // string, integer, float, json, boolean
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Table des frais de scolarité
        if (!Schema::hasTable('esbtp_frais_scolarite')) {
            Schema::create('esbtp_frais_scolarite', function (Blueprint $table) {
                $table->id();
                $table->foreignId('filiere_id')->constrained('esbtp_filieres');
                $table->foreignId('niveau_etude_id')->constrained('esbtp_niveau_etudes');
                $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires');
                $table->decimal('montant_total', 10, 2);
                $table->decimal('frais_inscription', 10, 2);
                $table->integer('nombre_tranches')->default(1);
                $table->json('details_tranches')->nullable(); // Stocke les montants et dates d'échéance pour chaque tranche
                $table->timestamps();
                $table->softDeletes();

                // Index unique pour éviter les doublons
                $table->unique(['filiere_id', 'niveau_etude_id', 'annee_universitaire_id'], 'frais_scolarite_unique');
            });
        }

        // Table des paiements
        if (!Schema::hasTable('esbtp_paiements')) {
            Schema::create('esbtp_paiements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('etudiant_id')->constrained('esbtp_etudiants');
                $table->foreignId('inscription_id')->nullable()->constrained('esbtp_inscriptions');
                $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires');
                $table->string('type_paiement'); // inscription, scolarité, examen, autre
                $table->decimal('montant', 10, 2);
                $table->string('reference_paiement');
                $table->string('mode_paiement'); // espèces, chèque, carte, virement, mobile money
                $table->string('numero_transaction')->nullable();
                $table->date('date_paiement');
                $table->date('date_echeance')->nullable(); // Pour les paiements programmés
                $table->text('description')->nullable();
                $table->string('statut')->default('completé'); // en attente, completé, annulé, remboursé
                $table->foreignId('createur_id')->nullable()->constrained('users');
                $table->foreignId('validateur_id')->nullable()->constrained('users');
                $table->timestamp('date_validation')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Table des factures
        if (!Schema::hasTable('esbtp_factures')) {
            Schema::create('esbtp_factures', function (Blueprint $table) {
                $table->id();
                $table->string('numero_facture')->unique();
                $table->foreignId('etudiant_id')->constrained('esbtp_etudiants');
                $table->foreignId('inscription_id')->nullable()->constrained('esbtp_inscriptions');
                $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires');
                $table->date('date_emission');
                $table->date('date_echeance');
                $table->decimal('montant_ht', 10, 2)->default(0);
                $table->decimal('taux_taxe', 5, 2)->default(0);
                $table->decimal('montant_taxe', 10, 2)->default(0);
                $table->decimal('montant_ttc', 10, 2);
                $table->decimal('montant_regle', 10, 2)->default(0);
                $table->decimal('montant_du', 10, 2);
                $table->string('statut'); // brouillon, émise, payée, partiellement payée, annulée
                $table->text('notes')->nullable();
                $table->string('path_pdf')->nullable(); // Chemin vers le fichier PDF de la facture
                $table->foreignId('createur_id')->nullable()->constrained('users');
                $table->foreignId('validateur_id')->nullable()->constrained('users');
                $table->timestamp('date_validation')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Table des détails de facture
        if (!Schema::hasTable('esbtp_facture_details')) {
            Schema::create('esbtp_facture_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('facture_id')->constrained('esbtp_factures')->onDelete('cascade');
                $table->string('designation');
                $table->text('description')->nullable();
                $table->decimal('montant', 10, 2);
                $table->integer('quantite')->default(1);
                $table->decimal('total_ligne', 10, 2);
                $table->timestamps();
            });
        }

        // Table des catégories de dépenses
        if (!Schema::hasTable('esbtp_categories_depenses')) {
            Schema::create('esbtp_categories_depenses', function (Blueprint $table) {
                $table->id();
                $table->string('nom');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->foreignId('parent_id')->nullable()->references('id')->on('esbtp_categories_depenses');
                $table->boolean('est_actif')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Table des fournisseurs
        if (!Schema::hasTable('esbtp_fournisseurs')) {
            Schema::create('esbtp_fournisseurs', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('nom');
                $table->string('type')->nullable(); // personne physique, entreprise
                $table->string('adresse')->nullable();
                $table->string('ville')->nullable();
                $table->string('pays')->default('Cote Ivoire');
                $table->string('telephone')->nullable();
                $table->string('email')->nullable();
                $table->string('site_web')->nullable();
                $table->string('numero_fiscal')->nullable();
                $table->string('compte_bancaire')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('est_actif')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Table des dépenses
        if (!Schema::hasTable('esbtp_depenses')) {
            Schema::create('esbtp_depenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('categorie_id')->constrained('esbtp_categories_depenses');
                $table->string('reference')->unique();
                $table->string('libelle');
                $table->text('description')->nullable();
                $table->decimal('montant', 10, 2);
                $table->date('date_depense');
                $table->string('mode_paiement');
                $table->string('numero_transaction')->nullable();
                $table->foreignId('fournisseur_id')->nullable()->constrained('esbtp_fournisseurs');
                $table->string('statut')->default('validée'); // en attente, validée, annulée
                $table->foreignId('createur_id')->nullable()->constrained('users');
                $table->foreignId('validateur_id')->nullable()->constrained('users');
                $table->timestamp('date_validation')->nullable();
                $table->string('path_justificatif')->nullable(); // Chemin vers le document justificatif
                $table->text('notes_internes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Table des bourses
        if (!Schema::hasTable('esbtp_bourses')) {
            Schema::create('esbtp_bourses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
                $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires');
                $table->string('type_bourse'); // complète, partielle, mérite, sociale
                $table->decimal('montant', 10, 2)->nullable();
                $table->decimal('pourcentage', 5, 2)->nullable(); // pourcentage de réduction
                $table->date('date_debut');
                $table->date('date_fin')->nullable();
                $table->string('statut')->default('active'); // active, suspendue, terminée
                $table->string('organisme_financeur')->nullable();
                $table->text('conditions')->nullable();
                $table->text('commentaires')->nullable();
                $table->foreignId('createur_id')->nullable()->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Table des salaires
        if (!Schema::hasTable('esbtp_salaires')) {
            Schema::create('esbtp_salaires', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires');
                $table->integer('mois'); // 1-12
                $table->integer('annee');
                $table->decimal('salaire_base', 10, 2);
                $table->decimal('heures_supplementaires', 10, 2)->default(0);
                $table->decimal('primes', 10, 2)->default(0);
                $table->decimal('retenues', 10, 2)->default(0);
                $table->decimal('net_a_payer', 10, 2);
                $table->string('statut')->default('en attente'); // en attente, payé, annulé
                $table->date('date_paiement')->nullable();
                $table->string('mode_paiement')->nullable();
                $table->string('reference_paiement')->nullable();
                $table->text('commentaires')->nullable();
                $table->foreignId('createur_id')->nullable()->constrained('users');
                $table->foreignId('validateur_id')->nullable()->constrained('users');
                $table->timestamp('date_validation')->nullable();
                $table->timestamps();
                $table->softDeletes();

                // Index unique pour éviter les doublons de salaire
                $table->unique(['user_id', 'annee_universitaire_id', 'mois', 'annee'], 'salaire_unique');
            });
        }

        // Table des transactions financières (journal)
        if (!Schema::hasTable('esbtp_transactions_financieres')) {
            Schema::create('esbtp_transactions_financieres', function (Blueprint $table) {
                $table->id();
                $table->string('type'); // revenus, dépenses, ajustement
                $table->string('transactionable_type');
                $table->unsignedBigInteger('transactionable_id');
                $table->index(['transactionable_type', 'transactionable_id'], 'idx_transactionable');
                $table->decimal('montant', 10, 2);
                $table->string('sens'); // débit, crédit
                $table->string('categorie');
                $table->string('reference');
                $table->date('date_transaction');
                $table->text('description')->nullable();
                $table->foreignId('compte_id')->nullable(); // Pour intégration future avec plan comptable
                $table->foreignId('createur_id')->nullable()->constrained('users');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_transactions_financieres');
        Schema::dropIfExists('esbtp_salaires');
        Schema::dropIfExists('esbtp_bourses');
        Schema::dropIfExists('esbtp_depenses');
        Schema::dropIfExists('esbtp_fournisseurs');
        Schema::dropIfExists('esbtp_categories_depenses');
        Schema::dropIfExists('esbtp_facture_details');
        Schema::dropIfExists('esbtp_factures');
        Schema::dropIfExists('esbtp_paiements');
        Schema::dropIfExists('esbtp_frais_scolarite');
        Schema::dropIfExists('esbtp_comptabilite_configurations');
    }
}
