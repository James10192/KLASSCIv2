<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Étape 1: Renommer les colonnes existantes
        Schema::table('esbtp_session_workflow_status', function (Blueprint $table) {
            $table->renameColumn('attendance_signed', 'attendance_start_signed');
            $table->renameColumn('attendance_signed_at', 'attendance_start_signed_at');
        });

        // Étape 2: Ajouter les nouveaux champs
        Schema::table('esbtp_session_workflow_status', function (Blueprint $table) {
            $table->boolean('attendance_end_signed')->default(false)->after('attendance_start_signed');
            $table->timestamp('attendance_end_signed_at')->nullable()->after('attendance_start_signed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_session_workflow_status', function (Blueprint $table) {
            // Supprimer les nouveaux champs
            $table->dropColumn(['attendance_end_signed', 'attendance_end_signed_at']);

            // Renommer les colonnes à leur nom d'origine
            $table->renameColumn('attendance_start_signed', 'attendance_signed');
            $table->renameColumn('attendance_start_signed_at', 'attendance_signed_at');
        });
    }
};
