<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lot 8 — Métadonnées pour les rôles custom (créés via UI).
     *
     * Ajoute label_fr, icon, description, is_custom, created_by_user_id à la table
     * `roles` pour permettre au superAdmin de créer des rôles personnalisés depuis
     * /esbtp/personnel/unified avec un label utilisateur lambda et une icône.
     *
     * Les rôles système restent en config/permissions.php (source de vérité)
     * et fournissent les valeurs fallback si les colonnes DB sont vides.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'label_fr')) {
                $table->string('label_fr', 255)->nullable()->after('name');
            }
            if (! Schema::hasColumn('roles', 'icon')) {
                $table->string('icon', 64)->nullable()->after('label_fr');
            }
            if (! Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('icon');
            }
            if (! Schema::hasColumn('roles', 'is_custom')) {
                $table->boolean('is_custom')->default(false)->after('description');
            }
            if (! Schema::hasColumn('roles', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('is_custom');
                $table->index('created_by_user_id', 'roles_created_by_user_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'created_by_user_id')) {
                $table->dropIndex('roles_created_by_user_id_idx');
                $table->dropColumn('created_by_user_id');
            }
            if (Schema::hasColumn('roles', 'is_custom')) {
                $table->dropColumn('is_custom');
            }
            if (Schema::hasColumn('roles', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('roles', 'icon')) {
                $table->dropColumn('icon');
            }
            if (Schema::hasColumn('roles', 'label_fr')) {
                $table->dropColumn('label_fr');
            }
        });
    }
};
