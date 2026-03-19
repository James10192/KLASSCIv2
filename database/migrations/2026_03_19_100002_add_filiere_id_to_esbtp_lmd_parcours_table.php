<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_lmd_parcours', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_lmd_parcours', 'filiere_id')) {
                $table->foreignId('filiere_id')->nullable()->after('mention_id')
                      ->constrained('esbtp_filieres')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_lmd_parcours', function (Blueprint $table) {
            $table->dropConstrainedForeignId('filiere_id');
        });
    }
};
