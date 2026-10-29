<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_classes', 'systeme_academique')) {
                $table->string('systeme_academique')->default('BTS')->after('is_active');
                // BTS = systeme classique (matieres directes)
                // LMD = systeme UE/ECUE avec credits
            }
            if (!Schema::hasColumn('esbtp_classes', 'parcours_id')) {
                $table->foreignId('parcours_id')->nullable()->after('systeme_academique')
                      ->constrained('esbtp_lmd_parcours')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parcours_id');
            $table->dropColumn('systeme_academique');
        });
    }
};
