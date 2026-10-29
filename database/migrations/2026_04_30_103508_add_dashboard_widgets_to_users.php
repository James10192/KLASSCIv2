<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lot 9 — Dashboard widgets configurables par utilisateur.
     *
     * Ajoute la colonne `dashboard_widgets` (JSON nullable) à la table users.
     *
     * - NULL → l'utilisateur n'a jamais configuré son dashboard, on tombe sur
     *   les défauts basés sur ses rôles (config/dashboard_widgets.php
     *   `default_for_roles`).
     * - Array → liste explicite ordonnée [['key' => '...', 'enabled' => true], ...]
     *   gérée par DashboardWidgetRegistry::userLayout().
     *
     * Les widgets dont la permission est révoquée disparaissent automatiquement
     * du layout au render (filter via availableFor()).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'dashboard_widgets')) {
                $table->json('dashboard_widgets')->nullable()->after('first_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dashboard_widgets')) {
                $table->dropColumn('dashboard_widgets');
            }
        });
    }
};
