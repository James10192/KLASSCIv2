<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil mobile d'un role custom.
 *
 * La barre d'onglets mobile se deduit des permissions (App\Services\Mobile\
 * MobileProfileResolver). Un role cree par l'ecole peut n'en porter aucune des
 * quatre et vouloir tout de meme un shell : l'ecole le declare ici. Nullable :
 * null = pas de shell mobile pour ce role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'mobile_profile')) {
                $table->string('mobile_profile', 20)->nullable()->after('is_custom');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'mobile_profile')) {
                $table->dropColumn('mobile_profile');
            }
        });
    }
};
