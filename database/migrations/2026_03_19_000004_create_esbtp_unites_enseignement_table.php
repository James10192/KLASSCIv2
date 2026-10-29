<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('esbtp_unites_enseignement')) {
            // Table existe deja, ajouter les colonnes LMD manquantes
            Schema::table('esbtp_unites_enseignement', function (Blueprint $table) {
                if (!Schema::hasColumn('esbtp_unites_enseignement', 'semestre')) {
                    $table->unsignedTinyInteger('semestre')->nullable()->after('credit');  // S1-S10
                }
                if (!Schema::hasColumn('esbtp_unites_enseignement', 'type_ue')) {
                    $table->string('type_ue')->default('fondamentale')->after('semestre');
                    // fondamentale, methodologique, decouverte, transversale
                }
                if (!Schema::hasColumn('esbtp_unites_enseignement', 'parcours_id')) {
                    $table->foreignId('parcours_id')->nullable()->after('niveau_id')
                          ->constrained('esbtp_lmd_parcours')->nullOnDelete();
                }
            });
            return;
        }

        Schema::create('esbtp_unites_enseignement', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                     // Ex: Technologie de Construction du Batiment
            $table->string('code')->unique();                           // Ex: UE:BTCB1
            $table->text('description')->nullable();
            $table->unsignedInteger('credit')->default(0);              // Credits CECT de l'UE
            $table->unsignedTinyInteger('semestre')->nullable();        // S1-S10
            $table->string('type_ue')->default('fondamentale');         // fondamentale|methodologique|decouverte|transversale
            $table->foreignId('filiere_id')->nullable()->constrained('esbtp_filieres')->nullOnDelete();
            $table->foreignId('niveau_id')->nullable()->constrained('esbtp_niveau_etudes')->nullOnDelete();
            $table->foreignId('parcours_id')->nullable()->constrained('esbtp_lmd_parcours')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['filiere_id', 'niveau_id', 'semestre']);
            $table->index(['parcours_id', 'semestre']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('esbtp_unites_enseignement')) {
            Schema::table('esbtp_unites_enseignement', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parcours_id');
                $table->dropColumn(['semestre', 'type_ue']);
            });
        }
    }
};
