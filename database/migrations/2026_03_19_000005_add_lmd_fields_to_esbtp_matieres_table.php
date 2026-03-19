<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_matieres', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_matieres', 'unite_enseignement_id')) {
                $table->foreignId('unite_enseignement_id')->nullable()->after('id')
                      ->constrained('esbtp_unites_enseignement')->nullOnDelete();
            }
            if (!Schema::hasColumn('esbtp_matieres', 'credit_ecue')) {
                $table->unsignedInteger('credit_ecue')->nullable()->after('coefficient');
                // Credits CECT propres a l'ECUE (ex: 2 credits sur 3 de l'UE)
            }
            if (!Schema::hasColumn('esbtp_matieres', 'coefficient_ecue')) {
                $table->decimal('coefficient_ecue', 5, 2)->nullable()->after('credit_ecue');
                // Coefficient de l'ECUE dans l'UE (pour calcul moyenne UE)
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_matieres', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unite_enseignement_id');
            $table->dropColumn(['credit_ecue', 'coefficient_ecue']);
        });
    }
};
