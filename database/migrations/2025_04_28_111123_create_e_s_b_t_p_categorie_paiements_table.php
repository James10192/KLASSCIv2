<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateESBTPCategoriePaiementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_categorie_paiements', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icone')->default('fas fa-money-bill-alt');
            $table->string('couleur')->default('#3498db');
            $table->boolean('est_actif')->default(true);
            $table->boolean('est_obligatoire')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('esbtp_categorie_paiements')->onDelete('set null');
            $table->integer('ordre')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Ajouter les catégories par défaut
        $this->seedDefaultCategories();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_categorie_paiements');
    }

    /**
     * Seed default payment categories.
     */
    private function seedDefaultCategories()
    {
        $categories = [
            [
                'nom' => 'Frais de scolarité',
                'code' => 'SCOLARITE',
                'slug' => 'frais-de-scolarite',
                'description' => 'Frais couvrant la formation académique pour l\'année universitaire',
                'icone' => 'fas fa-graduation-cap',
                'couleur' => '#3498db',
                'est_actif' => true,
                'est_obligatoire' => true,
                'ordre' => 1
            ],
            [
                'nom' => 'Frais d\'inscription',
                'code' => 'INSCRIPTION',
                'slug' => 'frais-inscription',
                'description' => 'Frais administratifs pour l\'inscription à l\'établissement',
                'icone' => 'fas fa-file-signature',
                'couleur' => '#2ecc71',
                'est_actif' => true,
                'est_obligatoire' => true,
                'ordre' => 2
            ],
            [
                'nom' => 'Frais de dossier',
                'code' => 'DOSSIER',
                'slug' => 'frais-dossier',
                'description' => 'Frais pour le traitement du dossier administratif',
                'icone' => 'fas fa-folder-open',
                'couleur' => '#f39c12',
                'est_actif' => true,
                'est_obligatoire' => false,
                'ordre' => 3
            ],
            [
                'nom' => 'Frais d\'examen',
                'code' => 'EXAMEN',
                'slug' => 'frais-examen',
                'description' => 'Frais relatifs aux examens et évaluations',
                'icone' => 'fas fa-edit',
                'couleur' => '#e74c3c',
                'est_actif' => true,
                'est_obligatoire' => false,
                'ordre' => 4
            ],
            [
                'nom' => 'Frais de laboratoire',
                'code' => 'LABORATOIRE',
                'slug' => 'frais-laboratoire',
                'description' => 'Frais pour l\'utilisation des laboratoires et équipements scientifiques',
                'icone' => 'fas fa-flask',
                'couleur' => '#9b59b6',
                'est_actif' => true,
                'est_obligatoire' => false,
                'ordre' => 5
            ],
            [
                'nom' => 'Frais de stage',
                'code' => 'STAGE',
                'slug' => 'frais-stage',
                'description' => 'Frais liés à l\'organisation et au suivi des stages professionnels',
                'icone' => 'fas fa-briefcase',
                'couleur' => '#34495e',
                'est_actif' => true,
                'est_obligatoire' => false,
                'ordre' => 6
            ],
            [
                'nom' => 'Frais de soutenance',
                'code' => 'SOUTENANCE',
                'slug' => 'frais-soutenance',
                'description' => 'Frais pour l\'organisation et l\'évaluation des soutenances',
                'icone' => 'fas fa-user-graduate',
                'couleur' => '#16a085',
                'est_actif' => true,
                'est_obligatoire' => false,
                'ordre' => 7
            ],
            [
                'nom' => 'Frais de diplôme',
                'code' => 'DIPLOME',
                'slug' => 'frais-diplome',
                'description' => 'Frais pour l\'établissement et la délivrance du diplôme',
                'icone' => 'fas fa-award',
                'couleur' => '#27ae60',
                'est_actif' => true,
                'est_obligatoire' => false,
                'ordre' => 8
            ],
            [
                'nom' => 'Location de matériel',
                'code' => 'LOCATION',
                'slug' => 'location-materiel',
                'description' => 'Frais pour la location d\'équipements et de matériel pédagogique',
                'icone' => 'fas fa-tools',
                'couleur' => '#f1c40f',
                'est_actif' => true,
                'est_obligatoire' => false,
                'ordre' => 9
            ],
            [
                'nom' => 'Services annexes',
                'code' => 'SERVICES',
                'slug' => 'services-annexes',
                'description' => 'Services annexes tels que photocopies, impression, cartes étudiantes, etc.',
                'icone' => 'fas fa-print',
                'couleur' => '#e67e22',
                'est_actif' => true,
                'est_obligatoire' => false,
                'ordre' => 10
            ],
            [
                'nom' => 'Pénalités de retard',
                'code' => 'PENALITES',
                'slug' => 'penalites-retard',
                'description' => 'Pénalités appliquées en cas de retard de paiement',
                'icone' => 'fas fa-exclamation-triangle',
                'couleur' => '#c0392b',
                'est_actif' => true,
                'est_obligatoire' => false,
                'ordre' => 11
            ],
            [
                'nom' => 'Autres recettes',
                'code' => 'AUTRES',
                'slug' => 'autres-recettes',
                'description' => 'Autres types de paiements et recettes divers',
                'icone' => 'fas fa-ellipsis-h',
                'couleur' => '#7f8c8d',
                'est_actif' => true,
                'est_obligatoire' => false,
                'ordre' => 12
            ],
        ];

        $now = now();

        foreach ($categories as $category) {
            DB::table('esbtp_categorie_paiements')->insert(array_merge($category, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
